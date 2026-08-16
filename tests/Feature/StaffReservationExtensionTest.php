<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffReservationExtensionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ParkSetting::create([
            'park_name' => 'Hinaguan Nature Park',
            'daytime_start' => '08:00',
            'daytime_end' => '17:00',
            'nighttime_start' => '17:00',
            'nighttime_end' => '06:00',
            'daytime_adult_entrance_fee' => 70,
            'daytime_child_entrance_fee' => 30,
            'nighttime_adult_entrance_fee' => 100,
            'nighttime_child_entrance_fee' => 50,
            'day_pool_fee' => 50,
            'night_pool_fee' => 75,
        ]);
    }

    private function actingAsStaff(): array
    {
        $staffUser = [
            'id' => 1,
            'name' => 'Test Staff',
            'email' => 'staff@hinaguan.test',
            'role' => 'staff',
        ];

        return ['auth_user' => $staffUser];
    }

    private function createAmenity(string $id, string $name, float $day = 300, float $night = 500): Amenity
    {
        return Amenity::create([
            'id' => $id,
            'amenities_name' => $name,
            'description' => 'Test Amenity',
            'daytime_price' => $day,
            'nighttime_price' => $night,
            'daytime_aircon_price' => $day + 200,
            'nighttime_aircon_price' => $night + 200,
            'minimum_capacity' => 1,
            'maximum_capacity' => 10,
            'status' => true,
        ]);
    }

    public function test_staff_can_extend_master_stay_schedule()
    {
        $res = Reservation::create([
            'booker_name' => 'John Doe',
            'email' => 'john@test.com',
            'phone' => '09123456789',
            'number_of_guests' => 1,
            'reservation_date' => '2026-09-01',
            'end_date' => '2026-09-01',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'check_in' => '2026-09-01 08:00:00',
            'status' => 'Checked In',
            'reservation_type' => 'walk_in',
            'payment_status' => 'Paid',
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
        ]);

        $response = $this->withSession($this->actingAsStaff())
            ->postJson(route('staff.reservations.extend-stay', $res->id), [
                'new_end_date' => '2026-09-02',
                'new_end_slot' => 'Daytime',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'end_date' => '2026-09-02',
                'end_slot' => 'Daytime',
                'total_days' => 2,
            ]);

        $this->assertNotNull($response->json('checkout_at'));

        $res->refresh();
        $this->assertEquals('2026-09-02', $res->end_date->format('Y-m-d'));
        $this->assertEquals('Daytime', $res->end_slot);
        $this->assertEquals(2, $res->total_days);
    }

    public function test_extend_stay_updates_expected_checkout_when_amenity_ends_earlier()
    {
        $amenity = $this->createAmenity('AMN-EARLY-01', 'Cottage Early', 300, 500);

        $res = Reservation::create([
            'booker_name' => 'Ashlyn Famador',
            'email' => 'ashlyn@test.com',
            'phone' => '09123456789',
            'number_of_guests' => 7,
            'reservation_date' => '2026-08-16',
            'end_date' => '2026-08-21',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Nighttime',
            'total_days' => 6,
            'check_in' => '2026-08-16 17:00:00',
            'status' => 'Checked In',
            'reservation_type' => 'walk_in',
            'payment_status' => 'Paid',
            'total_amount' => 5000,
            'amount_paid' => 5000,
            'remaining_balance' => 0,
        ]);

        ReservationAmenity::create([
            'reservation_id' => $res->id,
            'amenity_id' => $amenity->id,
            'start_date' => '2026-08-16',
            'end_date' => '2026-08-21',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Nighttime',
            'day_slots_count' => 5,
            'night_slots_count' => 6,
            'pricing_type' => 'Continuous Stay (6D)',
            'price_at_booking' => 5000,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // Extend master stay to Aug 25 Nighttime
        $response = $this->withSession($this->actingAsStaff())
            ->postJson(route('staff.reservations.extend-stay', $res->id), [
                'new_end_date' => '2026-08-25',
                'new_end_slot' => 'Nighttime',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'end_date' => '2026-08-25',
                'end_slot' => 'Nighttime',
                'total_days' => 10,
            ]);

        // Expected checkout should now be 2026-08-26 06:00:00 (since Nighttime ends at 06:00 AM next day)
        $checkoutAt = $response->json('checkout_at');
        $this->assertStringStartsWith('2026-08-26', $checkoutAt);
    }

    public function test_staff_can_extend_amenity_duration_within_stay_boundary()
    {
        $amenity = $this->createAmenity('AMN-ALPHA-01', 'Cottage Alpha', 300, 500);

        $res = Reservation::create([
            'booker_name' => 'Jane Smith',
            'email' => 'jane@test.com',
            'phone' => '09123456789',
            'number_of_guests' => 2,
            'reservation_date' => '2026-09-01',
            'end_date' => '2026-09-02',
            'start_slot' => 'Daytime',
            'end_slot' => 'Nighttime',
            'total_days' => 2,
            'check_in' => '2026-09-01 08:00:00',
            'status' => 'Checked In',
            'reservation_type' => 'walk_in',
            'payment_status' => 'Paid',
            'total_amount' => 300,
            'amount_paid' => 300,
            'remaining_balance' => 0,
        ]);

        $ra = ReservationAmenity::create([
            'reservation_id' => $res->id,
            'amenity_id' => $amenity->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-01',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 300,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // Extend amenity to 2026-09-01 Nighttime
        $response = $this->withSession($this->actingAsStaff())
            ->postJson(route('staff.reservations.amenities.extend', [$res->id, $ra->id]), [
                'new_end_date' => '2026-09-01',
                'new_end_slot' => 'Nighttime',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'added_cost' => 500,
                'new_total' => 800,
            ]);

        $ra->refresh();
        $this->assertEquals('2026-09-01', $ra->end_date->format('Y-m-d'));
        $this->assertEquals('Nighttime', $ra->end_slot);
        $this->assertEquals(800, $ra->price_at_booking);

        $res->refresh();
        $this->assertEquals(800, $res->total_amount);
        $this->assertEquals(800, $res->amount_paid);
    }

    public function test_amenity_extension_fails_if_exceeds_master_stay_window()
    {
        $amenity = $this->createAmenity('AMN-BETA-02', 'Cottage Beta', 300, 500);

        $res = Reservation::create([
            'booker_name' => 'Jane Smith',
            'email' => 'jane@test.com',
            'phone' => '09123456789',
            'number_of_guests' => 1,
            'reservation_date' => '2026-09-01',
            'end_date' => '2026-09-01',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'check_in' => '2026-09-01 08:00:00',
            'status' => 'Checked In',
            'reservation_type' => 'walk_in',
            'payment_status' => 'Paid',
            'total_amount' => 300,
            'amount_paid' => 300,
            'remaining_balance' => 0,
        ]);

        $ra = ReservationAmenity::create([
            'reservation_id' => $res->id,
            'amenity_id' => $amenity->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-01',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 300,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // Attempt extending to next day when master stay ends on 2026-09-01 Daytime
        $response = $this->withSession($this->actingAsStaff())
            ->postJson(route('staff.reservations.amenities.extend', [$res->id, $ra->id]), [
                'new_end_date' => '2026-09-02',
                'new_end_slot' => 'Daytime',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_staff_can_add_new_amenity_mid_stay()
    {
        $amenity = $this->createAmenity('AMN-GAZ-01', 'Gazebo 1', 400, 600);

        $res = Reservation::create([
            'booker_name' => 'Mike Ross',
            'email' => 'mike@test.com',
            'phone' => '09123456789',
            'number_of_guests' => 1,
            'reservation_date' => '2026-09-01',
            'end_date' => '2026-09-02',
            'start_slot' => 'Daytime',
            'end_slot' => 'Nighttime',
            'total_days' => 2,
            'check_in' => '2026-09-01 08:00:00',
            'status' => 'Checked In',
            'reservation_type' => 'walk_in',
            'payment_status' => 'Paid',
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
        ]);

        $response = $this->withSession($this->actingAsStaff())
            ->postJson(route('staff.reservations.amenities.add', $res->id), [
                'amenity_id' => $amenity->id,
                'start_date' => '2026-09-01',
                'start_slot' => 'Daytime',
                'end_date' => '2026-09-01',
                'end_slot' => 'Nighttime',
                'quantity' => 1,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'added_cost' => 1000, // 400 (Day) + 600 (Night)
                'new_total' => 1500,
            ]);

        $this->assertDatabaseHas('reservation_amenities', [
            'reservation_id' => $res->id,
            'amenity_id' => $amenity->id,
            'price_at_booking' => 1000,
            'status' => 'Active',
        ]);
    }

    public function test_staff_can_step_back_stay_schedule_down_to_amenity_checkout_boundary()
    {
        $amenity = $this->createAmenity('AMN-STEP-01', 'Cottage Step', 300, 500);

        // Stay is Aug 16 to Aug 30 (15 days), but amenity is only booked until Aug 25 Nighttime
        $res = Reservation::create([
            'booker_name' => 'Rachel Zane',
            'email' => 'rachel@test.com',
            'phone' => '09123456789',
            'number_of_guests' => 2,
            'reservation_date' => '2026-08-16',
            'end_date' => '2026-08-30',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Nighttime',
            'total_days' => 15,
            'check_in' => '2026-08-16 17:00:00',
            'status' => 'Checked In',
            'reservation_type' => 'walk_in',
            'payment_status' => 'Paid',
            'total_amount' => 5000,
            'amount_paid' => 5000,
            'remaining_balance' => 0,
        ]);

        ReservationAmenity::create([
            'reservation_id' => $res->id,
            'amenity_id' => $amenity->id,
            'start_date' => '2026-08-16',
            'end_date' => '2026-08-25',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Nighttime',
            'day_slots_count' => 9,
            'night_slots_count' => 10,
            'pricing_type' => 'Continuous Stay (10D)',
            'price_at_booking' => 5000,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // Staff steps back stay from Aug 30 to Aug 25 Nighttime (which equals amenity boundary) -> SHOULD SUCCEED
        $response = $this->withSession($this->actingAsStaff())
            ->postJson(route('staff.reservations.extend-stay', $res->id), [
                'new_end_date' => '2026-08-25',
                'new_end_slot' => 'Nighttime',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'end_date' => '2026-08-25',
                'end_slot' => 'Nighttime',
                'total_days' => 10,
            ]);

        $res->refresh();
        $this->assertEquals('2026-08-25', $res->end_date->format('Y-m-d'));
        $this->assertEquals('Nighttime', $res->end_slot);
        $this->assertEquals(10, $res->total_days);
    }

    public function test_staff_cannot_step_back_stay_schedule_past_booked_amenity_checkout()
    {
        $amenity = $this->createAmenity('AMN-BOUND-01', 'Cottage Boundary', 300, 500);

        // Stay is Aug 16 to Aug 30, amenity is booked until Aug 25 Nighttime
        $res = Reservation::create([
            'booker_name' => 'Harvey Specter',
            'email' => 'harvey@test.com',
            'phone' => '09123456789',
            'number_of_guests' => 2,
            'reservation_date' => '2026-08-16',
            'end_date' => '2026-08-30',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Nighttime',
            'total_days' => 15,
            'check_in' => '2026-08-16 17:00:00',
            'status' => 'Checked In',
            'reservation_type' => 'walk_in',
            'payment_status' => 'Paid',
            'total_amount' => 5000,
            'amount_paid' => 5000,
            'remaining_balance' => 0,
        ]);

        ReservationAmenity::create([
            'reservation_id' => $res->id,
            'amenity_id' => $amenity->id,
            'start_date' => '2026-08-16',
            'end_date' => '2026-08-25',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Nighttime',
            'day_slots_count' => 9,
            'night_slots_count' => 10,
            'pricing_type' => 'Continuous Stay (10D)',
            'price_at_booking' => 5000,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // Attempt stepping back before Aug 25 Nighttime (e.g. to Aug 24 Nighttime) -> SHOULD FAIL 422
        $response = $this->withSession($this->actingAsStaff())
            ->postJson(route('staff.reservations.extend-stay', $res->id), [
                'new_end_date' => '2026-08-24',
                'new_end_slot' => 'Nighttime',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message']);
        
        $this->assertStringContainsString('Cannot step back stay schedule before', $response->json('message'));
    }

    public function test_availability_endpoint_supports_amenity_id_filtering()
    {
        $amenity = $this->createAmenity('AMN-CAL-01', 'Calendar Cottage', 300, 500);

        $res = Reservation::create([
            'booker_name' => 'Donna Paulsen',
            'email' => 'donna@test.com',
            'phone' => '09123456789',
            'number_of_guests' => 1,
            'reservation_date' => '2026-08-16',
            'end_date' => '2026-08-20',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 5,
            'check_in' => '2026-08-16 08:00:00',
            'status' => 'Checked In',
            'reservation_type' => 'walk_in',
            'payment_status' => 'Paid',
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
        ]);

        $response = $this->withSession($this->actingAsStaff())
            ->getJson("/staff/reservations/{$res->id}/availability?amenity_id={$amenity->id}&month=8&year=2026");

        $response->assertOk()
            ->assertJsonStructure([
                'availability' => [
                    '*' => ['date', 'is_past', 'daytime', 'nighttime', 'available', 'full_available']
                ]
            ]);
    }

    public function test_availability_blocks_pending_reservations_and_prevents_overlapping_amenity_extension()
    {
        $amenity = $this->createAmenity('AMN-PEND-01', 'Function Hall Test', 1000, 1500);

        // Guest A has checked in from Aug 16 to Sept 10, with Function Hall Aug 16 to Aug 25
        $resA = Reservation::create([
            'booker_name' => 'Guest A',
            'email' => 'guesta@test.com',
            'phone' => '09123456789',
            'number_of_guests' => 2,
            'reservation_date' => '2026-08-16',
            'end_date' => '2026-09-10',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Nighttime',
            'total_days' => 26,
            'check_in' => '2026-08-16 17:00:00',
            'status' => 'Checked In',
            'reservation_type' => 'walk_in',
            'payment_status' => 'Paid',
            'total_amount' => 5000,
            'amount_paid' => 5000,
            'remaining_balance' => 0,
        ]);

        $raA = ReservationAmenity::create([
            'reservation_id' => $resA->id,
            'amenity_id' => $amenity->id,
            'start_date' => '2026-08-16',
            'end_date' => '2026-08-25',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Nighttime',
            'pricing_type' => 'Continuous Stay (10D)',
            'price_at_booking' => 5000,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // Guest B has a PENDING reservation on Sept 3 (Daytime) for the same Function Hall
        $resB = Reservation::create([
            'booker_name' => 'Guest B',
            'email' => 'guestb@test.com',
            'phone' => '09987654321',
            'number_of_guests' => 20,
            'reservation_date' => '2026-09-03',
            'end_date' => '2026-09-03',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'status' => 'Pending',
            'reservation_type' => 'online',
            'payment_status' => 'Partially Paid',
            'total_amount' => 1000,
            'amount_paid' => 500,
            'remaining_balance' => 500,
        ]);

        ReservationAmenity::create([
            'reservation_id' => $resB->id,
            'amenity_id' => $amenity->id,
            'start_date' => '2026-09-03',
            'end_date' => '2026-09-03',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'pricing_type' => 'Daytime',
            'price_at_booking' => 1000,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // 1. Availability query for Sept 2026 must report Sept 3 Daytime as UNAVAILABLE (false)
        $availResponse = $this->withSession($this->actingAsStaff())
            ->getJson("/staff/reservations/{$resA->id}/availability?amenity_id={$amenity->id}&month=9&year=2026");

        $availResponse->assertOk();
        $sept3Data = collect($availResponse->json('availability'))->firstWhere('date', '2026-09-03');
        $this->assertNotNull($sept3Data);
        $this->assertFalse($sept3Data['daytime'], 'Sept 3 Daytime should be false because Guest B has a pending reservation');

        // 2. Extending Guest A beyond Sept 3 (e.g. to Sept 4) must fail because Sept 3 Daytime is taken
        $extendResponse = $this->withSession($this->actingAsStaff())
            ->postJson(route('staff.reservations.amenities.extend', [$resA->id, $raA->id]), [
                'new_end_date' => '2026-09-04',
                'new_end_slot' => 'Daytime',
            ]);

        $extendResponse->assertStatus(422);
    }
}
