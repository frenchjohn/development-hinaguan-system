<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffReservationsMultiDayEditTest extends TestCase
{
    use RefreshDatabase;

    private function staffSession(): void
    {
        session(['auth_user' => ['id' => 1, 'name' => 'Staff Member', 'email' => 'staff@example.com', 'role' => 'staff']]);
    }

    private function createAmenity(string $id, int $dayPrice = 500, int $nightPrice = 700): Amenity
    {
        return Amenity::create([
            'id' => $id,
            'amenities_name' => 'Cottage ' . $id,
            'daytime_price' => (string) $dayPrice,
            'nighttime_price' => (string) $nightPrice,
            'daytime_aircon_price' => (string) ($dayPrice + 300),
            'nighttime_aircon_price' => (string) ($nightPrice + 300),
            'additional_per_head' => '100',
            'minimum_capacity' => '5',
            'maximum_capacity' => '15',
            'description' => 'Test cottage',
            'image' => null,
            'status' => true,
        ]);
    }

    public function test_can_update_reservation_to_multi_day_continuous_stay_and_recalculates_price_and_balance_increase(): void
    {
        $this->staffSession();
        $this->createAmenity('cottage-1', 500, 700);

        $reservation = Reservation::create([
            'booker_name' => 'Juan Dela Cruz',
            'phone' => '09171234567',
            'email' => 'juan@example.com',
            'reservation_date' => '2026-09-10 00:00:00',
            'end_date' => '2026-09-10 00:00:00',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 4,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 500,
            'amount_paid' => 250,
            'remaining_balance' => 250,
            'payment_status' => 'Partially Paid',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => 'cottage-1',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
            'remarks' => 'Single Day',
            'status' => 'Active',
        ]);

        // Reschedule to 3 days: Sept 10 (Daytime) to Sept 12 (Nighttime)
        // 3 Days Stay: 3 Day slots (3 * 500 = 1500) and 3 Night slots (3 * 700 = 2100) = 3,600 total
        $response = $this->postJson("/staff/reservations/{$reservation->id}/update", [
            'booker_name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'phone' => '09171234567',
            'reservation_date' => '2026-09-10',
            'end_date' => '2026-09-12',
            'start_slot' => 'Daytime',
            'end_slot' => 'Nighttime',
            'number_of_guests' => 4,
            'status' => 'Confirmed',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'reservation' => [
                    'id' => $reservation->id,
                    'total_days' => 3,
                    'start_slot' => 'Daytime',
                    'end_slot' => 'Nighttime',
                    'total_amount' => 3600,
                    'amount_paid' => 250,
                    'remaining_balance' => 3350,
                    'payment_status' => 'Partially Paid',
                    'status' => 'Confirmed',
                ],
            ]);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'total_amount' => 3600,
            'remaining_balance' => 3350,
            'payment_status' => 'Partially Paid',
            'total_days' => 3,
            'start_slot' => 'Daytime',
            'end_slot' => 'Nighttime',
            'status' => 'Confirmed',
        ]);

        $this->assertDatabaseHas('reservation_amenities', [
            'reservation_id' => $reservation->id,
            'amenity_id' => 'cottage-1',
            'day_slots_count' => 3,
            'night_slots_count' => 3,
            'price_at_booking' => 3600,
        ]);
    }

    public function test_can_decrease_multi_day_reservation_and_recalculates_balance_decrease(): void
    {
        $this->staffSession();
        $this->createAmenity('cottage-2', 500, 500);

        // 5 Days continuous stay: Sept 10 to Sept 14 (Daytime to Daytime)
        // 5 Days span: 5 Day slots (5*500=2500) + 4 Night slots (4*500=2000) = 4,500 total
        $reservation = Reservation::create([
            'booker_name' => 'Maria Santos',
            'phone' => '09181112222',
            'email' => 'maria@example.com',
            'reservation_date' => '2026-09-10 00:00:00',
            'end_date' => '2026-09-14 00:00:00',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 5,
            'number_of_guests' => 6,
            'reservation_type' => 'online',
            'status' => 'Confirmed',
            'total_amount' => 4500,
            'amount_paid' => 3000,
            'remaining_balance' => 1500,
            'payment_status' => 'Partially Paid',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => 'cottage-2',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-14',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 5,
            'night_slots_count' => 4,
            'pricing_type' => 'Continuous Stay (5D)',
            'price_at_booking' => 4500,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // Shorten stay to 2 days: Sept 11 to Sept 12 (Daytime to Daytime)
        // 2 Days span: 2 Day slots (2*500=1000) + 1 Night slot (1*500=500) = 1,500 total
        // Since amount_paid was 3,000, remaining_balance becomes 0 and payment_status becomes 'Paid'
        $response = $this->postJson("/staff/reservations/{$reservation->id}/update", [
            'booker_name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'phone' => '09181112222',
            'reservation_date' => '2026-09-11',
            'end_date' => '2026-09-12',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'number_of_guests' => 6,
            'status' => 'Confirmed',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'reservation' => [
                    'id' => $reservation->id,
                    'total_days' => 2,
                    'total_amount' => 1500,
                    'amount_paid' => 3000,
                    'remaining_balance' => 0,
                    'payment_status' => 'Paid',
                ],
            ]);

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'total_amount' => 1500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
            'total_days' => 2,
        ]);
    }

    public function test_rejects_rescheduling_when_continuous_range_overlaps_taken_dates(): void
    {
        $this->staffSession();
        $this->createAmenity('cottage-3', 500, 700);

        // Other reservation occupies Sept 12 Daytime
        $otherReservation = Reservation::create([
            'booker_name' => 'Other Booker',
            'phone' => '09199998888',
            'email' => 'other@example.com',
            'reservation_date' => '2026-09-12 00:00:00',
            'end_date' => '2026-09-12 00:00:00',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 2,
            'reservation_type' => 'online',
            'status' => 'Confirmed',
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $otherReservation->id,
            'amenity_id' => 'cottage-3',
            'start_date' => '2026-09-12',
            'end_date' => '2026-09-12',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        $reservation = Reservation::create([
            'booker_name' => 'Pending Booker',
            'phone' => '09170001122',
            'email' => 'pending@example.com',
            'reservation_date' => '2026-09-15 00:00:00',
            'end_date' => '2026-09-15 00:00:00',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 2,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 500,
            'amount_paid' => 250,
            'remaining_balance' => 250,
            'payment_status' => 'Partially Paid',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => 'cottage-3',
            'start_date' => '2026-09-15',
            'end_date' => '2026-09-15',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // Attempt to span across Sept 10 to Sept 14 (which crosses Sept 12)
        $response = $this->postJson("/staff/reservations/{$reservation->id}/update", [
            'booker_name' => 'Pending Booker',
            'email' => 'pending@example.com',
            'phone' => '09170001122',
            'reservation_date' => '2026-09-10',
            'end_date' => '2026-09-14',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'number_of_guests' => 2,
            'status' => 'Pending',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
            ]);

        // Ensure database dates remain unchanged
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'reservation_date' => '2026-09-15 00:00:00',
        ]);
    }

    public function test_availability_endpoint_returns_per_slot_status_and_pricing_for_month(): void
    {
        $this->staffSession();
        $this->createAmenity('cottage-4', 600, 800);

        $reservation = Reservation::create([
            'booker_name' => 'Test Booker',
            'phone' => '09170001133',
            'email' => 'test@example.com',
            'reservation_date' => '2026-10-05 00:00:00',
            'end_date' => '2026-10-06 00:00:00',
            'start_slot' => 'Daytime',
            'end_slot' => 'Nighttime',
            'total_days' => 2,
            'number_of_guests' => 3,
            'reservation_type' => 'online',
            'status' => 'Confirmed',
            'total_amount' => 2800,
            'amount_paid' => 1400,
            'remaining_balance' => 1400,
            'payment_status' => 'Partially Paid',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => 'cottage-4',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-06',
            'start_slot' => 'Daytime',
            'end_slot' => 'Nighttime',
            'day_slots_count' => 2,
            'night_slots_count' => 2,
            'pricing_type' => 'Continuous Stay (2D)',
            'price_at_booking' => 2800,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        $response = $this->getJson("/staff/reservations/{$reservation->id}/availability?month=10&year=2026");

        $response->assertOk()
            ->assertJsonStructure([
                'reservation_id',
                'month',
                'year',
                'current_start_date',
                'current_end_date',
                'current_start_slot',
                'current_end_slot',
                'total_days',
                'total_amount',
                'amount_paid',
                'remaining_balance',
                'payment_status',
                'amenities',
                'availability' => [
                    '*' => [
                        'date',
                        'is_past',
                        'daytime',
                        'nighttime',
                        'available',
                        'full_available',
                    ],
                ],
            ]);
    }
}
