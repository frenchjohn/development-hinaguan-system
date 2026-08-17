<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddedAmenityWalkInAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private Amenity $gazebo;
    private Amenity $poolsideCottage;

    protected function setUp(): void
    {
        parent::setUp();

        ParkSetting::create([
            'opening_time' => '08:00',
            'closing_time' => '17:00',
            'daytime_start' => '08:00',
            'daytime_end' => '17:00',
            'nighttime_start' => '17:00',
            'nighttime_end' => '22:00',
        ]);

        $this->gazebo = Amenity::create([
            'id' => 'AMENITY-GAZEBO-001',
            'amenities_name' => 'Gazebo 1',
            'description' => 'Garden gazebo',
            'daytime_price' => 500,
            'nighttime_price' => 700,
            'minimum_capacity' => 1,
            'maximum_capacity' => 10,
            'status' => true,
        ]);

        $this->poolsideCottage = Amenity::create([
            'id' => 'AMENITY-POOL-001',
            'amenities_name' => 'Poolside Cottage 1',
            'description' => 'Near swimming pool',
            'daytime_price' => 800,
            'nighttime_price' => 1000,
            'minimum_capacity' => 1,
            'maximum_capacity' => 15,
            'status' => true,
        ]);
    }

    public function test_added_amenity_on_existing_checked_in_reservation_is_immediately_unavailable_for_walkin()
    {
        $today = now()->toDateString();

        // 1. Existing Checked In reservation on site (originally booked only Poolside Cottage)
        $reservation = Reservation::create([
            'booker_name' => 'Alice CheckedIn',
            'phone' => '09123456789',
            'email' => 'alice@example.com',
            'reservation_date' => $today,
            'end_date' => $today,
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 4,
            'status' => 'Checked In',
            'total_amount' => 800,
            'amount_paid' => 800,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
            'reservation_type' => 'walkin',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $this->poolsideCottage->id,
            'start_date' => $today,
            'end_date' => $today,
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'pricing_type' => 'Daytime',
            'price_at_booking' => 800,
            'status' => 'Active',
        ]);

        // Before adding Gazebo, Gazebo should be AVAILABLE for today Daytime
        $availBefore = $this->getJson("/api/amenities/availability?start_date={$today}&end_date={$today}&start_slot=Daytime&end_slot=Daytime");
        $availBefore->assertStatus(200);
        $amenitiesBefore = collect($availBefore->json('amenities'))->keyBy('id');
        $this->assertTrue($amenitiesBefore[$this->gazebo->id]['is_available'], 'Gazebo should initially be available');

        // 2. Staff adds Gazebo to Alice\'s checked-in reservation
        $addResponse = $this->withSession(['auth_user' => ['id' => 1, 'name' => 'Staff Member', 'role' => 'staff']])
            ->postJson("/staff/reservations/{$reservation->id}/amenities/add", [
                'amenity_id' => $this->gazebo->id,
                'start_date' => $today,
                'end_date' => $today,
                'start_slot' => 'Daytime',
                'end_slot' => 'Daytime',
                'quantity' => 1,
            ]);

        $addResponse->assertStatus(200);
        $addResponse->assertJson(['success' => true]);

        // 3. Now check walk-in availability endpoint: Gazebo MUST BE UNAVAILABLE
        $availAfter = $this->getJson("/api/amenities/availability?start_date={$today}&end_date={$today}&start_slot=Daytime&end_slot=Daytime");
        $availAfter->assertStatus(200);
        $amenitiesAfter = collect($availAfter->json('amenities'))->keyBy('id');
        $this->assertFalse($amenitiesAfter[$this->gazebo->id]['is_available'], 'Gazebo must now be unavailable for walk-in picker');
        $this->assertContains($this->gazebo->id, $availAfter->json('occupied_ids'));

        // 4. Attempting to add Gazebo via walk-in submission should fail conflict check
        $walkInResponse = $this->withSession(['auth_user' => ['id' => 1, 'name' => 'Staff Member', 'role' => 'staff']])
            ->post(route('staff.checkins.guests.store'), [
                'guest_mode' => 'with_primary',
                'reservation_type' => 'walk_in',
                'start_date' => $today,
                'end_date' => $today,
                'start_slot' => 'Daytime',
                'end_slot' => 'Daytime',
                'total_days' => 1,
                'primary_guest' => [
                    'first_name' => 'Bob',
                    'last_name' => 'WalkIn',
                    'age' => 30,
                    'gender' => 'Male',
                    'is_foreigner' => 0,
                    'phone' => '09123456789',
                    'email' => 'bob@example.com',
                ],
                'selected_amenities' => [
                    [
                        'amenity_id' => (string) $this->gazebo->id,
                        'start_date' => $today,
                        'end_date' => $today,
                        'start_slot' => 'Daytime',
                        'end_slot' => 'Daytime',
                        'pricing_type' => 'Daytime',
                    ],
                ],
            ]);

        $walkInResponse->assertStatus(302);
        $walkInResponse->assertSessionHasErrors(['selected_amenities']);
    }

    public function test_pending_and_confirmed_reservations_with_unpaid_status_block_amenities_for_covered_session()
    {
        $targetDate = '2026-09-10';

        // Create Pending reservation with payment_status = 'Unpaid'
        $pendingRes = Reservation::create([
            'booker_name' => 'Charlie Pending',
            'phone' => '09123456789',
            'email' => 'charlie@example.com',
            'reservation_date' => $targetDate,
            'end_date' => $targetDate,
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 5,
            'status' => 'Pending',
            'total_amount' => 500,
            'amount_paid' => 0,
            'remaining_balance' => 500,
            'payment_status' => 'Unpaid',
            'reservation_type' => 'online',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $pendingRes->id,
            'amenity_id' => $this->gazebo->id,
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'pricing_type' => 'Daytime',
            'price_at_booking' => 500,
            'status' => 'Active',
        ]);

        // Gazebo should be unavailable on 2026-09-10 Daytime
        $resAvail = $this->getJson("/api/amenities/availability?start_date={$targetDate}&end_date={$targetDate}&start_slot=Daytime&end_slot=Daytime");
        $resAvail->assertStatus(200);
        $amenities = collect($resAvail->json('amenities'))->keyBy('id');
        $this->assertFalse($amenities[$this->gazebo->id]['is_available']);
        $this->assertContains($this->gazebo->id, $resAvail->json('occupied_ids'));
    }
}
