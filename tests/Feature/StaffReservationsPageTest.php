<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffReservationsPageTest extends TestCase
{
    use RefreshDatabase;

    private function staffSession(): void
    {
        session(['auth_user' => ['id' => 1, 'name' => 'Staff', 'email' => 'staff@example.com', 'role' => 'staff']]);
    }

    private function createAmenity(string $id): void
    {
        Amenity::create([
            'id' => $id,
            'amenities_name' => 'Picnic Area ' . $id,
            'daytime_price' => '500',
            'nighttime_price' => '700',
            'daytime_aircon_price' => '800',
            'nighttime_aircon_price' => '900',
            'additional_per_head' => '100',
            'minimum_capacity' => '10',
            'maximum_capacity' => '20',
            'description' => 'Test amenity',
            'image' => null,
            'status' => true,
        ]);
    }

    private function createReservation(string $date): Reservation
    {
        return Reservation::create([
            'booker_name' => 'Online Booker',
            'phone' => '09170000000',
            'email' => 'online@example.com',
            'reservation_date' => $date,
            'check_in' => null,
            'number_of_guests' => 2,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 1500,
            'amount_paid' => 750,
            'remaining_balance' => 750,
            'payment_status' => 'Partially Paid',
        ]);
    }

    public function test_staff_reservations_page_shows_pending_online_reservations(): void
    {
        session(['auth_user' => ['id' => 1, 'name' => 'Staff', 'email' => 'staff@example.com', 'role' => 'staff']]);

        Reservation::create([
            'booker_name' => 'Online Booker',
            'phone' => '09170000000',
            'email' => 'online@example.com',
            'check_in' => null, // not yet checked in — the page lists online bookings awaiting action
            'number_of_guests' => 2,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 1500,
            'amount_paid' => 750,
            'remaining_balance' => 750,
            'payment_status' => 'Partially Paid',
        ]);

        Reservation::create([
            'booker_name' => 'Checked In Guest',
            'phone' => '09170000001',
            'email' => 'checked@example.com',
            'check_in' => now()->toDateString(),
            'number_of_guests' => 1,
            'reservation_type' => 'online',
            'status' => 'Checked In',
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $response = $this->get('/staff/reservations');

        $response->assertOk();
        $response->assertViewHas('reservations', function ($reservations) {
            return $reservations->count() === 1 && $reservations->first()->booker_name === 'Online Booker';
        });
    }

    public function test_reservation_data_includes_computed_checkout_datetime_per_slot(): void
    {
        $this->staffSession();
        $this->createAmenity('amenity-1');

        // Daytime ends at 18:00 of the reservation date.
        $daytime = $this->createReservation('2026-08-10');
        ReservationAmenity::create([
            'reservation_id' => $daytime->id,
            'amenity_id' => 'amenity-1',
            'pricing_type' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
        ]);

        // NightToDay covers night of the date + day of the NEXT day -> 18:00 next day.
        $nightToDay = $this->createReservation('2026-08-11');
        ReservationAmenity::create([
            'reservation_id' => $nightToDay->id,
            'amenity_id' => 'amenity-1',
            'pricing_type' => 'NightToDay',
            'price_at_booking' => 700,
            'quantity' => 1,
        ]);

        // Nighttime ends at 06:00 of the next day.
        $nighttime = $this->createReservation('2026-08-12');
        ReservationAmenity::create([
            'reservation_id' => $nighttime->id,
            'amenity_id' => 'amenity-1',
            'pricing_type' => 'Nighttime',
            'price_at_booking' => 700,
            'quantity' => 1,
        ]);

        $response = $this->get('/staff/reservations');
        $response->assertOk();

        $data = $response->viewData('reservationData');
        $this->assertSame('2026-08-10T18:00:00+08:00', $data[$daytime->id]['checkout_at']);
        $this->assertSame('2026-08-12T18:00:00+08:00', $data[$nightToDay->id]['checkout_at']);
        $this->assertSame('2026-08-13T06:00:00+08:00', $data[$nighttime->id]['checkout_at']);
    }

    public function test_reservation_availability_endpoint_disables_dates_where_the_amenity_is_booked(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-08-01');
        $this->staffSession();
        $this->createAmenity('amenity-1');

        // Another reservation already holds amenity-1 for Daytime on Aug 10.
        $other = $this->createReservation('2026-08-10');
        ReservationAmenity::create([
            'reservation_id' => $other->id,
            'amenity_id' => 'amenity-1',
            'pricing_type' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
        ]);

        // The reservation we are rescheduling, on Aug 15.
        $reservation = $this->createReservation('2026-08-15');
        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => 'amenity-1',
            'pricing_type' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
        ]);

        $response = $this->getJson("/staff/reservations/{$reservation->id}/availability?month=8&year=2026");

        $response->assertOk()
            ->assertJsonPath('slot.0', 'Daytime')
            ->assertJsonCount(31, 'availability');

        $availability = collect($response->json('availability'))->keyBy('date');

        // Aug 10 is taken by the other reservation -> unavailable.
        $this->assertFalse($availability['2026-08-10']['available']);
        // The reservation's own date is always selectable (itself is excluded).
        $this->assertTrue($availability['2026-08-15']['available']);
        // A free day is available.
        $this->assertTrue($availability['2026-08-12']['available']);
    }

    public function test_reservation_update_rejects_rescheduling_to_a_taken_date(): void
    {
        $this->staffSession();
        $this->createAmenity('amenity-1');

        // Another reservation holds amenity-1 for Daytime on Aug 10.
        $other = $this->createReservation('2026-08-10');
        ReservationAmenity::create([
            'reservation_id' => $other->id,
            'amenity_id' => 'amenity-1',
            'pricing_type' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
        ]);

        $reservation = $this->createReservation('2026-08-15');
        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => 'amenity-1',
            'pricing_type' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
        ]);

        // Rescheduling onto the taken date is rejected.
        $conflict = $this->postJson("/staff/reservations/{$reservation->id}/update", [
            'booker_name' => 'Online Booker',
            'email' => 'online@example.com',
            'phone' => '09170000000',
            'reservation_date' => '2026-08-10',
            'number_of_guests' => 2,
            'status' => 'Pending',
        ]);

        $conflict->assertStatus(409);
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'reservation_date' => '2026-08-15 00:00:00',
        ]);

        // Rescheduling onto a free date succeeds.
        $ok = $this->postJson("/staff/reservations/{$reservation->id}/update", [
            'booker_name' => 'Online Booker',
            'email' => 'online@example.com',
            'phone' => '09170000000',
            'reservation_date' => '2026-08-12',
            'number_of_guests' => 2,
            'status' => 'Confirmed',
        ]);

        $ok->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'reservation_date' => '2026-08-12 00:00:00',
            'status' => 'Confirmed',
        ]);
    }
}
