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

    private function createAmenity(string $id): Amenity
    {
        return Amenity::create([
            'id' => $id,
            'amenities_name' => 'Picnic Area ' . $id,
            'daytime_price' => '500',
            'nighttime_price' => '700',
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
        $response->assertSee('ID');
        $response->assertViewHas('reservations', function ($reservations) use ($response) {
            $first = $reservations->first();
            $response->assertSee('#' . $first->id);
            return $reservations->count() === 1 && $first->booker_name === 'Online Booker';
        });
    }

    public function test_reservation_data_computes_checkout_from_master_schedule_only(): void
    {
        $this->staffSession();
        $this->createAmenity('amenity-1');

        // Checkout reference is ONLY the reservation's own master stay schedule.
        // Amenity pricing types must NOT influence it. Reservations created here
        // have no explicit slots, so they default to a single Daytime day (18:00).

        $daytime = $this->createReservation('2026-08-10');
        ReservationAmenity::create([
            'reservation_id' => $daytime->id,
            'amenity_id' => 'amenity-1',
            'pricing_type' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
        ]);

        // Even though the amenity row claims NightToDay, the master schedule wins.
        $nightToDay = $this->createReservation('2026-08-11');
        ReservationAmenity::create([
            'reservation_id' => $nightToDay->id,
            'amenity_id' => 'amenity-1',
            'pricing_type' => 'NightToDay',
            'price_at_booking' => 700,
            'quantity' => 1,
        ]);

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
        $this->assertSame('2026-08-11T18:00:00+08:00', $data[$nightToDay->id]['checkout_at']);
        $this->assertSame('2026-08-12T18:00:00+08:00', $data[$nighttime->id]['checkout_at']);
    }

    public function test_reservation_data_uses_explicit_master_end_date_for_checkout(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-08-01');
        $this->staffSession();
        $this->createAmenity('amenity-1');

        // Multi-day stay: master end_date drives the checkout (+4 days, Daytime 18:00),
        // even when an amenity row inside ends earlier or claims another slot.
        $multiDay = $this->createReservation('2026-08-10');
        $multiDay->update([
            'end_date' => '2026-08-14',
            'end_slot' => 'Daytime',
            'total_days' => 5,
        ]);
        ReservationAmenity::create([
            'reservation_id' => $multiDay->id,
            'amenity_id' => 'amenity-1',
            'pricing_type' => 'Nighttime',
            'price_at_booking' => 700,
            'quantity' => 1,
        ]);

        $response = $this->get('/staff/reservations');
        $response->assertOk();

        $data = $response->viewData('reservationData');
        $this->assertSame('2026-08-14T18:00:00+08:00', $data[$multiDay->id]['checkout_at']);
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

    public function test_online_reservation_check_in_modal_renders_single_section_layout(): void
    {
        $this->staffSession();
        $this->createAmenity('amenity-1');
        $reservation = $this->createReservation('2026-08-10');

        $response = $this->get('/staff/reservations');

        $response->assertOk();
        // Assert modal container exists
        $response->assertSee('id="checkInModal"', false);
        $response->assertSee('id="checkInScrollContent"', false);
        // Assert Stay Schedule & Admission Policies are present
        $response->assertSee('Stay Schedule', false);
        $response->assertSee('id="checkInRescheduleBtn"', false);
        $response->assertSee('id="checkInEntranceOption"', false);
        $response->assertSee('id="checkInPoolOption"', false);
        // Assert Reserved Amenities section
        $response->assertSee('id="checkInAmenitiesContainer"', false);
        $response->assertDontSee('id="toggleCheckInAmenityEditBtn"', false);
        $response->assertDontSee('id="checkInAmenitiesEditContainer"', false);
        $response->assertSee('id="openCheckInAddAmenityModalBtn"', false);
        // Assert amenity picker modal and filters
        $response->assertSee('id="checkInAmenityPickerModal"', false);
        $response->assertSee('id="checkInCountAvailable"', false);
        $response->assertSee('id="checkInCountOccupied"', false);
        $response->assertSee('id="checkInCountReserved"', false);
        $response->assertSee('id="checkInCountAll"', false);
        $response->assertSee('id="checkInAmenityCategorySelect"', false);
        $response->assertSee('id="checkInAmenityPickerContainer"', false);
        // Assert inline Main Guest form inputs
        $response->assertSee('id="checkInMainFirstName"', false);
        $response->assertSee('id="checkInMainLastName"', false);
        $response->assertSee('id="checkInMainAge"', false);
        $response->assertSee('id="checkInMainGender"', false);
        $response->assertSee('id="checkInMainIsForeigner"', false);
        // Assert Fees and Totals
        $response->assertSee('id="checkInGrandTotal"', false);
        $response->assertSee('id="checkInSubmitBtn"', false);
        // Assert multi-tab sidebar navigation has been removed
        $response->assertDontSee('walkin-modal-sidebar');
    }

    public function test_amenity_availability_api_distinguishes_occupied_and_reserved_amenities(): void
    {
        $this->staffSession();
        $amenity1 = $this->createAmenity('amenity-avail');
        $amenity2 = $this->createAmenity('amenity-occ');
        $amenity3 = $this->createAmenity('amenity-res');

        // Reservation 1: Checked In (Occupied)
        $resOccupied = $this->createReservation('2026-09-20');
        $resOccupied->update(['status' => 'Checked In']);
        \App\Models\ReservationAmenity::create([
            'reservation_id' => $resOccupied->id,
            'amenity_id' => $amenity2->id,
            'start_date' => '2026-09-20',
            'end_date' => '2026-09-20',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'pricing_type' => 'Daytime',
            'quantity' => 1,
            'price_at_booking' => 500,
        ]);

        // Reservation 2: Confirmed (Reserved)
        $resReserved = $this->createReservation('2026-09-20');
        $resReserved->update(['status' => 'Confirmed']);
        \App\Models\ReservationAmenity::create([
            'reservation_id' => $resReserved->id,
            'amenity_id' => $amenity3->id,
            'start_date' => '2026-09-20',
            'end_date' => '2026-09-20',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'pricing_type' => 'Daytime',
            'quantity' => 1,
            'price_at_booking' => 500,
        ]);

        $response = $this->getJson('/api/amenities/availability?start_date=2026-09-20&end_date=2026-09-20&start_slot=Daytime&end_slot=Daytime');
        $response->assertOk();
        $data = $response->json();

        $this->assertContains((string) $amenity2->id, array_map('strval', $data['occupied_ids']));
        $this->assertContains((string) $amenity3->id, array_map('strval', $data['reserved_ids']));

        // With exclude_reservation_id for resReserved, amenity3 should be available
        $excludeResponse = $this->getJson('/api/amenities/availability?start_date=2026-09-20&end_date=2026-09-20&start_slot=Daytime&end_slot=Daytime&exclude_reservation_id=' . $resReserved->id);
        $excludeResponse->assertOk();
        $excludeData = $excludeResponse->json();
        $this->assertNotContains((string) $amenity3->id, array_map('strval', $excludeData['reserved_ids']));
    }

    public function test_staff_can_add_and_remove_amenities_on_reservation_update(): void
    {
        $this->staffSession();
        $amenity1 = $this->createAmenity('amenity-init');
        $amenity2 = $this->createAmenity('amenity-add');

        $reservation = $this->createReservation('2026-10-01');
        $ra1 = \App\Models\ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $amenity1->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-01',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'pricing_type' => 'Daytime',
            'quantity' => 1,
            'price_at_booking' => 500,
        ]);

        // Submit update adding amenity2 and removing amenity1
        $response = $this->postJson("/staff/reservations/{$reservation->id}/update", [
            'booker_name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '09123456789',
            'reservation_date' => '2026-10-01',
            'end_date' => '2026-10-01',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'number_of_guests' => 2,
            'status' => 'Confirmed',
            'amenities' => [
                [
                    'id' => null, // newly added amenity
                    'amenity_id' => $amenity2->id,
                    'start_date' => '2026-10-01',
                    'end_date' => '2026-10-01',
                    'start_slot' => 'Daytime',
                    'end_slot' => 'Daytime',
                ],
            ],
        ]);

        $response->assertOk();

        // Verify amenity1 was removed and amenity2 was added
        $this->assertDatabaseMissing('reservation_amenities', [
            'id' => $ra1->id,
        ]);
        $this->assertDatabaseHas('reservation_amenities', [
            'reservation_id' => $reservation->id,
            'amenity_id' => $amenity2->id,
        ]);
    }

    public function test_amenity_availed_by_active_checked_in_reservation_is_occupied_and_only_available_when_no_active_reservation(): void
    {
        $this->staffSession();
        $amenity = $this->createAmenity('amenity-active-checkin');

        // Create active checked-in reservation with this amenity
        $activeRes = $this->createReservation('2026-09-15');
        $activeRes->update([
            'status' => 'Checked In',
            'check_in' => '2026-09-13 14:00:00',
            'check_out' => null,
        ]);

        \App\Models\ReservationAmenity::create([
            'reservation_id' => $activeRes->id,
            'amenity_id' => $amenity->id,
            'start_date' => '2026-09-15',
            'end_date' => '2026-09-15',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'pricing_type' => 'Daytime',
            'quantity' => 1,
            'price_at_booking' => 500,
        ]);

        // Query availability for a different date (e.g. 2026-09-19)
        $response = $this->getJson('/api/amenities/availability?start_date=2026-09-19&end_date=2026-09-19&start_slot=Daytime&end_slot=Daytime');
        $response->assertOk();
        $data = $response->json();

        // The amenity must be occupied and NOT available because active reservation has availed it
        $this->assertContains((string) $amenity->id, array_map('strval', $data['occupied_ids']));
        $amenityData = collect($data['amenities'])->firstWhere('id', (string) $amenity->id);
        $this->assertNotNull($amenityData);
        $this->assertEquals('occupied', $amenityData['status']);
        $this->assertTrue($amenityData['is_occupied']);
        $this->assertFalse($amenityData['is_available']);

        // Now checkout the active reservation
        $activeRes->update([
            'status' => 'Checked Out',
            'check_out' => now(),
        ]);

        // Now for 2026-09-19, it should be available
        $afterCheckout = $this->getJson('/api/amenities/availability?start_date=2026-09-19&end_date=2026-09-19&start_slot=Daytime&end_slot=Daytime');
        $afterCheckout->assertOk();
        $afterData = $afterCheckout->json();
        $this->assertNotContains((string) $amenity->id, array_map('strval', $afterData['occupied_ids']));
        $afterAmenityData = collect($afterData['amenities'])->firstWhere('id', (string) $amenity->id);
        $this->assertEquals('available', $afterAmenityData['status']);
        $this->assertFalse($afterAmenityData['is_occupied']);
        $this->assertTrue($afterAmenityData['is_available']);
    }

    public function test_active_checked_in_reservation_blocks_calendar_availability_for_held_amenity(): void
    {
        $this->staffSession();
        $amenity = $this->createAmenity('cottage-cal-1');

        $today = now()->toDateString();
        $month = now()->month;
        $year = now()->year;

        // Active checked in reservation currently occupying cottage-cal-1
        $activeRes = $this->createReservation($today);
        $activeRes->update([
            'status' => 'Checked In',
            'check_in' => now(),
            'check_out' => null,
        ]);
        ReservationAmenity::create([
            'reservation_id' => $activeRes->id,
            'amenity_id' => $amenity->id,
            'pricing_type' => 'Daytime',
            'quantity' => 1,
            'price_at_booking' => 500,
            'start_date' => $today,
            'end_date' => $today,
        ]);

        // Pending reservation that also holds cottage-cal-1 opens its reschedule calendar
        $pendingRes = $this->createReservation(now()->addDays(5)->toDateString());
        ReservationAmenity::create([
            'reservation_id' => $pendingRes->id,
            'amenity_id' => $amenity->id,
            'pricing_type' => 'Daytime',
            'quantity' => 1,
            'price_at_booking' => 500,
        ]);

        $response = $this->getJson("/staff/reservations/{$pendingRes->id}/availability?month={$month}&year={$year}");
        $response->assertOk();

        $availability = collect($response->json('availability'))->keyBy('date');

        // Today must be marked unavailable because the amenity is occupied by active checked-in reservation
        $this->assertFalse($availability[$today]['daytime']);
        $this->assertFalse($availability[$today]['available']);
    }

    public function test_reschedule_syncs_all_amenity_dates_to_new_stay_master_schedule(): void
    {
        $this->staffSession();
        $amenity = $this->createAmenity('room-sync-1');

        $res = $this->createReservation('2026-10-01');
        $res->update([
            'end_date' => '2026-10-01',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
        ]);
        $ra = ReservationAmenity::create([
            'reservation_id' => $res->id,
            'amenity_id' => $amenity->id,
            'pricing_type' => 'Daytime',
            'quantity' => 1,
            'price_at_booking' => 500,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-01',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
        ]);

        // Reschedule master reservation to 2026-10-05 -> 2026-10-07 Nighttime
        $updateResponse = $this->postJson("/staff/reservations/{$res->id}/update", [
            'booker_name' => 'Online Booker',
            'email' => 'online@example.com',
            'phone' => '09170000000',
            'reservation_date' => '2026-10-05',
            'end_date' => '2026-10-07',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Nighttime',
            'number_of_guests' => 2,
            'status' => 'Pending',
        ]);

        $updateResponse->assertOk();

        $ra->refresh();
        $this->assertEquals('2026-10-05', $ra->start_date instanceof \Illuminate\Support\Carbon ? $ra->start_date->toDateString() : (string) $ra->start_date);
        $this->assertEquals('2026-10-07', $ra->end_date instanceof \Illuminate\Support\Carbon ? $ra->end_date->toDateString() : (string) $ra->end_date);
        $this->assertEquals('Nighttime', $ra->start_slot);
        $this->assertEquals('Nighttime', $ra->end_slot);
    }

    public function test_reservation_detail_modal_reschedule_flow(): void
    {
        $this->staffSession();
        $this->createAmenity('cottage-detail-1');
        $res = $this->createReservation('2026-11-10');
        $ra = ReservationAmenity::create([
            'reservation_id' => $res->id,
            'amenity_id' => 'cottage-detail-1',
            'pricing_type' => 'Daytime',
            'quantity' => 1,
            'price_at_booking' => 500,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
        ]);

        $pageResponse = $this->get('/staff/reservations');
        $pageResponse->assertOk();
        $pageResponse->assertSee('id="reservationModal"', false);
        $pageResponse->assertSee('id="editCalendarModal"', false);

        // Reschedule via detail modal apply flow
        $updateResponse = $this->postJson("/staff/reservations/{$res->id}/update", [
            'booker_name' => 'Online Booker',
            'email' => 'online@example.com',
            'phone' => '09170000000',
            'reservation_date' => '2026-11-15',
            'end_date' => '2026-11-16',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'number_of_guests' => 2,
            'status' => 'Pending',
            'amenities' => [
                [
                    'id' => $ra->id,
                    'amenity_id' => 'cottage-detail-1',
                    'start_date' => '2026-11-15',
                    'end_date' => '2026-11-16',
                    'start_slot' => 'Daytime',
                    'end_slot' => 'Daytime',
                    'quantity' => 1,
                ]
            ],
        ]);

        $updateResponse->assertOk();
        $updateResponse->assertJson(['success' => true]);

        $res->refresh();
        $this->assertEquals('2026-11-15', $res->reservation_date instanceof \Illuminate\Support\Carbon ? $res->reservation_date->toDateString() : substr((string)$res->reservation_date, 0, 10));
        $this->assertEquals('2026-11-16', $res->end_date instanceof \Illuminate\Support\Carbon ? $res->end_date->toDateString() : substr((string)$res->end_date, 0, 10));

        $ra->refresh();
        $this->assertEquals('2026-11-15', $ra->start_date instanceof \Illuminate\Support\Carbon ? $ra->start_date->toDateString() : substr((string)$ra->start_date, 0, 10));
        $this->assertEquals('2026-11-16', $ra->end_date instanceof \Illuminate\Support\Carbon ? $ra->end_date->toDateString() : substr((string)$ra->end_date, 0, 10));
    }

    public function test_staff_can_update_reservation_status_to_cancelled_no_show_and_reopen(): void
    {
        $this->staffSession();
        $this->createAmenity('cottage-status-1');
        $res = $this->createReservation('2026-12-01');

        // 1. Mark as Cancelled
        $cancelResponse = $this->postJson("/staff/reservations/{$res->id}/status", [
            'status' => 'Cancelled',
        ]);
        $cancelResponse->assertOk();
        $cancelResponse->assertJson([
            'success' => true,
            'reservation' => [
                'id' => $res->id,
                'status' => 'Cancelled',
            ],
        ]);
        $res->refresh();
        $this->assertEquals('Cancelled', $res->status);

        // 2. Mark as No Show
        $noShowResponse = $this->postJson("/staff/reservations/{$res->id}/status", [
            'status' => 'No Show',
        ]);
        $noShowResponse->assertOk();
        $noShowResponse->assertJson([
            'success' => true,
            'reservation' => [
                'id' => $res->id,
                'status' => 'No Show',
            ],
        ]);
        $res->refresh();
        $this->assertEquals('No Show', $res->status);

        // 3. Reopen to Pending
        $reopenResponse = $this->postJson("/staff/reservations/{$res->id}/status", [
            'status' => 'Pending',
        ]);
        $reopenResponse->assertOk();
        $reopenResponse->assertJson([
            'success' => true,
            'reservation' => [
                'id' => $res->id,
                'status' => 'Pending',
            ],
        ]);
        $res->refresh();
        $this->assertEquals('Pending', $res->status);
    }

    public function test_scheduled_or_past_counter_and_today_expected_guests_metrics(): void
    {
        $this->staffSession();

        // 1. Past overdue reservation (yesterday, 3 guests)
        Reservation::create([
            'booker_name' => 'Past Booker',
            'phone' => '09170000001',
            'email' => 'past@example.com',
            'reservation_date' => now()->subDay()->toDateString(),
            'check_in' => null,
            'number_of_guests' => 3,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 1000,
            'amount_paid' => 500,
            'remaining_balance' => 500,
            'payment_status' => 'Partially Paid',
        ]);

        // 2. Today scheduled reservation (today, 5 guests)
        Reservation::create([
            'booker_name' => 'Today Booker',
            'phone' => '09170000002',
            'email' => 'today@example.com',
            'reservation_date' => now()->toDateString(),
            'check_in' => null,
            'number_of_guests' => 5,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 1500,
            'amount_paid' => 1500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        // 3. Future reservation (tomorrow, 4 guests)
        Reservation::create([
            'booker_name' => 'Future Booker',
            'phone' => '09170000003',
            'email' => 'future@example.com',
            'reservation_date' => now()->addDay()->toDateString(),
            'check_in' => null,
            'number_of_guests' => 4,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 1200,
            'amount_paid' => 600,
            'remaining_balance' => 600,
            'payment_status' => 'Partially Paid',
        ]);

        $response = $this->get('/staff/reservations');

        $response->assertOk();
        $response->assertViewHas('pendingCount', 3);
        $response->assertViewHas('todayScheduledCount', 1);
        $response->assertViewHas('pastScheduleCount', 1);
        $response->assertViewHas('scheduledOrPastCount', 2);
        $response->assertViewHas('expectedGuests', 5);

        // Verify HTML elements
        $response->assertSee('Scheduled / Past Schedule');
        $response->assertSee('Expected Guests');
        $response->assertSee("Today's scheduled visitors", false);
    }
}

