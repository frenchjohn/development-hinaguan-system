<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Amenity;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPendingReservationAmenityChangeTest extends TestCase
{
    use RefreshDatabase;

    private function staffSession(): void
    {
        session(['auth_user' => ['id' => 1, 'name' => 'Officer Juana', 'email' => 'juana@example.com', 'role' => 'staff']]);
    }

    private function createAmenity(string $id, string $name, int $dayPrice, int $nightPrice, bool $status = true): Amenity
    {
        return Amenity::create([
            'id' => $id,
            'amenities_name' => $name,
            'daytime_price' => (string) $dayPrice,
            'nighttime_price' => (string) $nightPrice,
            'additional_per_head' => '100',
            'minimum_capacity' => '2',
            'maximum_capacity' => '10',
            'description' => 'Test amenity description',
            'image' => null,
            'status' => $status,
        ]);
    }

    public function test_available_replacements_filters_by_same_category_and_identical_prices(): void
    {
        $this->staffSession();

        // Category: Cottage (price: 200 daytime, 200 overnight)
        $cottage1 = $this->createAmenity('cottage-1', 'Cottage 1', 200, 200);
        $cottage2 = $this->createAmenity('cottage-2', 'Cottage 2', 200, 200);
        $cottage3 = $this->createAmenity('cottage-3', 'Cottage 3', 200, 200);
        // Same category (Cottage), but DIFFERENT price -> must NOT appear
        $cottageVip = $this->createAmenity('cottage-vip', 'Cottage VIP 4', 500, 700);

        // Different category (Payag) with same price (200, 200) -> must NOT appear
        $payag1 = $this->createAmenity('payag-1', 'Payag 1', 200, 200);

        // Reservation with Cottage 1 booked
        $reservation = Reservation::create([
            'booker_name' => 'Juan Luna',
            'phone' => '09171234567',
            'email' => 'juan@example.com',
            'reservation_date' => '2026-11-10 00:00:00',
            'end_date' => '2026-11-10 00:00:00',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 4,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 200,
            'amount_paid' => 100,
            'remaining_balance' => 100,
            'payment_status' => 'Partially Paid',
        ]);

        $ra = ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $cottage1->id,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 200,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // Another reservation occupying Cottage 3 on the same date (2026-11-10 Daytime)
        $otherRes = Reservation::create([
            'booker_name' => 'Other Booker',
            'phone' => '09179998888',
            'email' => 'other@example.com',
            'reservation_date' => '2026-11-10 00:00:00',
            'end_date' => '2026-11-10 00:00:00',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 2,
            'reservation_type' => 'online',
            'status' => 'Confirmed',
            'total_amount' => 200,
            'amount_paid' => 200,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $otherRes->id,
            'amenity_id' => $cottage3->id,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 200,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        $response = $this->getJson("/staff/reservations/{$reservation->id}/amenities/{$ra->id}/available-replacements");

        $response->assertOk()
            ->assertJson(['success' => true]);

        $replacements = $response->json('replacements');
        $replacementIds = array_column($replacements, 'id');

        // Cottage 2 is same category, same price (200/200), and available -> MUST be present
        $this->assertContains('cottage-2', $replacementIds);

        // Cottage 3 is taken by another reservation -> MUST NOT be present
        $this->assertNotContains('cottage-3', $replacementIds);

        // Cottage VIP has different price -> MUST NOT be present
        $this->assertNotContains('cottage-vip', $replacementIds);

        // Payag 1 is different category -> MUST NOT be present
        $this->assertNotContains('payag-1', $replacementIds);

        // Cottage 1 is the currently selected amenity -> MUST NOT be present in replacement choices
        $this->assertNotContains('cottage-1', $replacementIds);
    }

    public function test_staff_can_change_amenity_on_pending_reservation_and_creates_activity_log_and_notification(): void
    {
        $this->staffSession();

        $c1 = $this->createAmenity('cottage-1', 'Cottage 1', 200, 200);
        $c2 = $this->createAmenity('cottage-2', 'Cottage 2', 200, 200);

        $reservation = Reservation::create([
            'booker_name' => 'Leonor Rivera',
            'phone' => '09180001111',
            'email' => 'leonor@example.com',
            'reservation_date' => '2026-12-01 00:00:00',
            'end_date' => '2026-12-01 00:00:00',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 3,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 200,
            'amount_paid' => 100,
            'remaining_balance' => 100,
            'payment_status' => 'Partially Paid',
        ]);

        $ra = ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $c1->id,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-01',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 200,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // Change amenity from Cottage 1 to Cottage 2
        $response = $this->postJson("/staff/reservations/{$reservation->id}/amenities/{$ra->id}/change", [
            'new_amenity_id' => $c2->id,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        // Database assertion: reservation_amenities record updated
        $this->assertDatabaseHas('reservation_amenities', [
            'id' => $ra->id,
            'amenity_id' => $c2->id,
        ]);

        // Verify Activity Log was recorded
        $log = ActivityLog::where('reservation_id', $reservation->id)
            ->where('activity_type', 'amenity_changed')
            ->first();

        $this->assertNotNull($log, 'An activity log must be created when staff changes an amenity.');
        $this->assertEquals('Amenity Changed', $log->title);
        $this->assertEquals('Officer Juana', $log->actor_name);
        $this->assertStringContainsString('Cottage 1', $log->description);
        $this->assertStringContainsString('Cottage 2', $log->description);
        $this->assertStringContainsString('Leonor Rivera', $log->description);

        // Verify the activity log shows in the notifications endpoint
        $notifResponse = $this->getJson('/api/activity-notifications');
        $notifResponse->assertOk();
        $this->assertGreaterThanOrEqual(1, $notifResponse->json('unread_count'));
        $activities = $notifResponse->json('activities');
        $this->assertNotEmpty($activities);
        $firstAct = $activities[0];
        $this->assertEquals('Amenity Changed', $firstAct['title']);
        $this->assertStringContainsString('Cottage 2', $firstAct['description']);
    }

    public function test_cannot_change_amenity_if_reservation_is_not_pending(): void
    {
        $this->staffSession();

        $c1 = $this->createAmenity('cottage-1', 'Cottage 1', 200, 200);
        $c2 = $this->createAmenity('cottage-2', 'Cottage 2', 200, 200);

        $reservation = Reservation::create([
            'booker_name' => 'Confirmed Guest',
            'phone' => '09180002222',
            'email' => 'confirmed@example.com',
            'reservation_date' => '2026-12-05 00:00:00',
            'end_date' => '2026-12-05 00:00:00',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 2,
            'reservation_type' => 'online',
            'status' => 'Confirmed', // Not Pending!
            'total_amount' => 200,
            'amount_paid' => 200,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $ra = ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $c1->id,
            'start_date' => '2026-12-05',
            'end_date' => '2026-12-05',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 200,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        $response = $this->postJson("/staff/reservations/{$reservation->id}/amenities/{$ra->id}/change", [
            'new_amenity_id' => $c2->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Amenities can only be changed on pending reservations.',
            ]);
    }

    public function test_cannot_change_amenity_to_different_price_even_within_same_category(): void
    {
        $this->staffSession();

        // A-House 1 (Standard: 300 day, 500 night)
        $ahouse1 = $this->createAmenity('ahouse-1', 'A-House 1', 300, 500);
        // A-House 2 (Aircon: 700 day, 1000 night)
        $ahouseAircon = $this->createAmenity('ahouse-2', 'A-House 2', 700, 1000);

        $reservation = Reservation::create([
            'booker_name' => 'Budget Traveler',
            'phone' => '09180003333',
            'email' => 'budget@example.com',
            'reservation_date' => '2026-12-10 00:00:00',
            'end_date' => '2026-12-10 00:00:00',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 2,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 300,
            'amount_paid' => 150,
            'remaining_balance' => 150,
            'payment_status' => 'Partially Paid',
        ]);

        $ra = ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $ahouse1->id,
            'start_date' => '2026-12-10',
            'end_date' => '2026-12-10',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 300,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        $response = $this->postJson("/staff/reservations/{$reservation->id}/amenities/{$ra->id}/change", [
            'new_amenity_id' => $ahouseAircon->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Replacement amenity must have the same daytime and overnight price.',
            ]);
    }

    public function test_cannot_change_amenity_to_already_booked_unit_for_the_same_dates(): void
    {
        $this->staffSession();

        $c1 = $this->createAmenity('cottage-1', 'Cottage 1', 200, 200);
        $c2 = $this->createAmenity('cottage-2', 'Cottage 2', 200, 200);

        // Res 1 occupying Cottage 2 on 2026-12-20
        $occupantRes = Reservation::create([
            'booker_name' => 'Existing Occupant',
            'phone' => '09180004444',
            'email' => 'occupant@example.com',
            'reservation_date' => '2026-12-20 00:00:00',
            'end_date' => '2026-12-20 00:00:00',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 2,
            'reservation_type' => 'online',
            'status' => 'Confirmed',
            'total_amount' => 200,
            'amount_paid' => 200,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $occupantRes->id,
            'amenity_id' => $c2->id,
            'start_date' => '2026-12-20',
            'end_date' => '2026-12-20',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 200,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // Res 2 (Pending) with Cottage 1 on 2026-12-20
        $pendingRes = Reservation::create([
            'booker_name' => 'Pending Booker',
            'phone' => '09180005555',
            'email' => 'pending@example.com',
            'reservation_date' => '2026-12-20 00:00:00',
            'end_date' => '2026-12-20 00:00:00',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 2,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 200,
            'amount_paid' => 100,
            'remaining_balance' => 100,
            'payment_status' => 'Partially Paid',
        ]);

        $ra = ReservationAmenity::create([
            'reservation_id' => $pendingRes->id,
            'amenity_id' => $c1->id,
            'start_date' => '2026-12-20',
            'end_date' => '2026-12-20',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 200,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // Try swapping to Cottage 2 -> should be blocked with 409
        $response = $this->postJson("/staff/reservations/{$pendingRes->id}/amenities/{$ra->id}/change", [
            'new_amenity_id' => $c2->id,
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'The selected amenity is not available on the scheduled reservation dates.',
            ]);
    }
}
