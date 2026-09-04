<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Customer;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\ReservationEntranceFee;
use App\Models\ReservationGuest;
use App\Models\StaffAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmenityCapacityAdditionalFeeTest extends TestCase
{
    use RefreshDatabase;

    protected $staffUser;
    protected $ahouseAmenity;
    protected $cottageAmenity;

    protected function setUp(): void
    {
        parent::setUp();

        ParkSetting::create([
            'daytime_start' => '08:00:00',
            'daytime_end' => '17:00:00',
            'nighttime_start' => '18:00:00',
            'nighttime_end' => '08:00:00',
            'daytime_adult_entrance_fee' => 100,
            'daytime_child_entrance_fee' => 0,
            'nighttime_adult_entrance_fee' => 120,
            'nighttime_child_entrance_fee' => 0,
            'day_pool_fee' => 50,
            'night_pool_fee' => 70,
        ]);

        $this->staffUser = StaffAccount::create([
            'name' => 'Staff Member',
            'username' => 'staff_test',
            'email' => 'staff@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->ahouseAmenity = Amenity::create([
            'id' => 'AMENITY-AHOUSE-001',
            'amenities_name' => 'A-House 1',
            'minimum_capacity' => 1,
            'maximum_capacity' => 2,
            'additional_per_head' => 100,
            'daytime_price' => 300,
            'nighttime_price' => 500,
            'status' => true,
        ]);

        $this->cottageAmenity = Amenity::create([
            'id' => 'AMENITY-COTTAGE-001',
            'amenities_name' => 'Cottage 1',
            'minimum_capacity' => 1,
            'maximum_capacity' => null,
            'additional_per_head' => null,
            'daytime_price' => 200,
            'nighttime_price' => 200,
            'status' => true,
        ]);
    }

    private function authStaff(): self
    {
        return $this->withSession([
            'auth_user' => [
                'id' => $this->staffUser->id,
                'name' => $this->staffUser->name,
                'role' => 'staff',
            ]
        ]);
    }

    public function test_online_reservation_check_in_with_exceeded_amenity_capacity_charges_additional_fee()
    {
        $booker = Customer::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'Male',
            'email' => 'john@example.com',
            'phone' => '09123456789',
        ]);

        $reservation = Reservation::create([
            'booker_name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '09123456789',
            'reservation_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 4,
            'status' => 'Approved',
            'payment_status' => 'Paid',
            'total_amount' => 300,
            'amount_paid' => 300,
            'remaining_balance' => 0,
        ]);

        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $this->ahouseAmenity->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'price' => 300,
            'pricing_type' => 'Daytime',
            'is_aircon' => false,
            'status' => 'Active',
        ]);

        // Check in with 4 guests (Primary + 3 companions). Capacity is 2 -> 2 excess guests @ 100 = 200 extra fee.
        // Entrance fee: 4 adults @ 100 = 400. Extra head fee = 200. Total added = 600.
        $response = $this->authStaff()
            ->post("/staff/reservations/{$reservation->id}/check-in", [
                'guest_mode' => 'with_primary',
                'check_in' => now()->toDateTimeString(),
                'primary_guest' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'age' => '30',
                    'gender' => 'Male',
                    'is_foreigner' => '0',
                    'phone' => '09123456789',
                    'email' => 'john@example.com',
                    'has_pool_access' => '0',
                ],
                'companions' => [
                    [
                        'first_name' => 'Jane',
                        'last_name' => 'Doe',
                        'age' => '28',
                        'gender' => 'Female',
                        'is_foreigner' => '0',
                        'has_pool_access' => '0',
                        'amenity_id' => (string) $this->ahouseAmenity->id,
                    ],
                    [
                        'first_name' => 'Bob',
                        'last_name' => 'Doe',
                        'age' => '25',
                        'gender' => 'Male',
                        'is_foreigner' => '0',
                        'has_pool_access' => '0',
                        'amenity_id' => (string) $this->ahouseAmenity->id,
                    ],
                    [
                        'first_name' => 'Alice',
                        'last_name' => 'Doe',
                        'age' => '22',
                        'gender' => 'Female',
                        'is_foreigner' => '0',
                        'has_pool_access' => '0',
                        'amenity_id' => (string) $this->ahouseAmenity->id,
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $reservation->refresh();

        $this->assertEquals('Checked In', $reservation->status);
        // Base 300 + 400 entrance + 200 extra head = 900
        $this->assertEquals(900, (float) $reservation->total_amount);
        $this->assertEquals(900, (float) $reservation->amount_paid);

        $ref = ReservationEntranceFee::where('reservation_id', $reservation->id)->first();
        $this->assertNotNull($ref);
        // 4 adults @ 100 = 400 + 200 extra head = 600
        $this->assertEquals(600, (float) $ref->total_amount);
    }

    public function test_walk_in_check_in_with_exceeded_amenity_capacity_charges_additional_fee()
    {
        // 3 guests (Primary + 2 companions) in A-House 1 (max 2, add 100/extra head).
        // Excess = 1 guest -> 100 extra head fee.
        // Entrance: 3 adults @ 100 = 300.
        // Amenity: 300.
        // Extra head: 100.
        // Total = 700.
        $response = $this->authStaff()
            ->post('/staff/check-ins/guests', [
                'guest_mode' => 'with_primary',
                'check_in' => now()->toDateString(),
                'start_date' => now()->toDateString(),
                'end_date' => now()->toDateString(),
                'start_slot' => 'Daytime',
                'end_slot' => 'Daytime',
                'primary_guest' => [
                    'first_name' => 'Mark',
                    'last_name' => 'Smith',
                    'age' => '35',
                    'gender' => 'Male',
                    'is_foreigner' => '0',
                    'phone' => '09987654321',
                    'email' => 'mark@example.com',
                ],
                'total_amount' => 700,
                'selected_amenities' => [
                    [
                        'amenity_id' => $this->ahouseAmenity->id,
                        'start_date' => now()->toDateString(),
                        'end_date' => now()->toDateString(),
                        'start_slot' => 'Daytime',
                        'end_slot' => 'Daytime',
                        'is_aircon' => '0',
                    ]
                ],
                'companions' => [
                    [
                        'first_name' => 'Sarah',
                        'last_name' => 'Smith',
                        'age' => '30',
                        'gender' => 'Female',
                        'is_foreigner' => '0',
                        'amenity_id' => (string) $this->ahouseAmenity->id,
                    ],
                    [
                        'first_name' => 'Jake',
                        'last_name' => 'Smith',
                        'age' => '20',
                        'gender' => 'Male',
                        'is_foreigner' => '0',
                        'amenity_id' => (string) $this->ahouseAmenity->id,
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $reservation = Reservation::latest('id')->first();
        $this->assertNotNull($reservation);

        $this->assertEquals('Checked In', $reservation->status);
        $this->assertEquals(700, (float) $reservation->total_amount);
        $this->assertEquals(700, (float) $reservation->amount_paid);

        $ref = ReservationEntranceFee::where('reservation_id', $reservation->id)->first();
        $this->assertNotNull($ref);
        // 3 adults @ 100 = 300 + 100 extra head = 400
        $this->assertEquals(400, (float) $ref->total_amount);
    }

    public function test_walk_in_check_in_with_cottage_no_limit_has_zero_extra_fee()
    {
        // 10 guests in Cottage 1 (no limit).
        // Entrance: 10 adults @ 100 = 1000.
        // Amenity: 200.
        // Extra head: 0.
        // Total = 1200.
        $companions = [];
        for ($i = 1; $i <= 9; $i++) {
            $companions[] = [
                'first_name' => "Companion {$i}",
                'last_name' => 'Test',
                'age' => '25',
                'gender' => 'Male',
                'is_foreigner' => '0',
                'amenity_id' => (string) $this->cottageAmenity->id,
            ];
        }

        $response = $this->authStaff()
            ->post('/staff/check-ins/guests', [
                'guest_mode' => 'with_primary',
                'check_in' => now()->toDateString(),
                'start_date' => now()->toDateString(),
                'end_date' => now()->toDateString(),
                'start_slot' => 'Daytime',
                'end_slot' => 'Daytime',
                'primary_guest' => [
                    'first_name' => 'Leader',
                    'last_name' => 'Test',
                    'age' => '30',
                    'gender' => 'Male',
                    'is_foreigner' => '0',
                ],
                'total_amount' => 1200,
                'selected_amenities' => [
                    [
                        'amenity_id' => $this->cottageAmenity->id,
                        'start_date' => now()->toDateString(),
                        'end_date' => now()->toDateString(),
                        'start_slot' => 'Daytime',
                        'end_slot' => 'Daytime',
                        'is_aircon' => '0',
                    ]
                ],
                'companions' => $companions,
            ]);

        $response->assertSessionHasNoErrors();
        $reservation = Reservation::latest('id')->first();
        $this->assertNotNull($reservation);

        $this->assertEquals(1200, (float) $reservation->total_amount);
        $this->assertEquals(1200, (float) $reservation->amount_paid);

        $ref = ReservationEntranceFee::where('reservation_id', $reservation->id)->first();
        $this->assertNotNull($ref);
        $this->assertEquals(1000, (float) $ref->total_amount);
    }

    public function test_add_companion_to_checked_in_reservation_charges_extra_head_fee_when_exceeding_capacity()
    {
        $booker = Customer::create([
            'first_name' => 'Original',
            'last_name' => 'Guest',
            'gender' => 'Male',
            'email' => 'orig@example.com',
            'phone' => '09111111111',
        ]);

        $reservation = Reservation::create([
            'booker_name' => 'Original Guest',
            'email' => 'orig@example.com',
            'phone' => '09111111111',
            'reservation_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 2,
            'status' => 'Checked-In',
            'payment_status' => 'Paid',
            'total_amount' => 500, // 300 amenity + 200 entrance (2 guests)
            'amount_paid' => 500,
            'remaining_balance' => 0,
        ]);

        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $this->ahouseAmenity->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'price' => 300,
            'pricing_type' => 'Daytime',
            'is_aircon' => false,
            'status' => 'Active',
        ]);

        ReservationGuest::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $booker->id,
            'is_primary_guest' => true,
        ]);

        $c2 = Customer::create([
            'first_name' => 'Second',
            'last_name' => 'Guest',
            'gender' => 'Female',
        ]);
        ReservationGuest::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $c2->id,
            'is_primary_guest' => false,
        ]);

        ReservationEntranceFee::create([
            'reservation_id' => $reservation->id,
            'pricing_type' => 'Daytime',
            'adult_count' => 2,
            'child_count' => 0,
            'total_amount' => 200,
        ]);

        // Already 2 guests in A-House 1 (max capacity is 2).
        // Adding 1 new adult companion to A-House 1:
        // Entrance: 100.
        // Extra head fee: 100.
        // Total additional = 200.
        $response = $this->authStaff()
            ->postJson("/staff/reservations/{$reservation->id}/add-companion", [
                'companions' => [
                    [
                        'first_name' => 'Third',
                        'last_name' => 'Guest',
                        'age' => 25,
                        'gender' => 'Male',
                        'is_foreigner' => false,
                        'pool_access' => false,
                        'is_free_entrance' => false,
                        'amenity_id' => (string) $this->ahouseAmenity->id,
                    ]
                ]
            ]);

        $response->assertOk();
        $reservation->refresh();

        // Original 500 + 100 entrance + 100 extra head = 700
        $this->assertEquals(700, (float) $reservation->total_amount);
        $this->assertEquals(700, (float) $reservation->amount_paid);

        $ref = ReservationEntranceFee::where('reservation_id', $reservation->id)->first();
        $this->assertNotNull($ref);
        // Original 200 + 100 adult entrance + 100 extra head = 400
        $this->assertEquals(400, (float) $ref->total_amount);
    }

    public function test_checked_in_amenity_shows_as_occupied_in_occupancy_monitor_and_unavailable_in_api()
    {
        \Illuminate\Support\Carbon::setTestNow('2026-08-31 10:00:00');
        $today = now()->toDateString();

        // 1. Create a walk-in check-in with A-House 1
        $response = $this->authStaff()
            ->post('/staff/check-ins/guests', [
                'guest_mode' => 'with_primary',
                'reservation_type' => 'walk_in',
                'start_date' => $today,
                'end_date' => $today,
                'start_slot' => 'Daytime',
                'end_slot' => 'Daytime',
                'total_days' => 1,
                'time_period' => 'daytime',
                'entrance_option' => 'all_paid',
                'pool_option' => 'no_pool',
                'primary_guest' => [
                    'first_name' => 'Alice',
                    'last_name' => 'Walker',
                    'age' => 28,
                    'gender' => 'Female',
                    'is_foreigner' => false,
                ],
                'selected_amenities' => [
                    [
                        'amenity_id' => (string) $this->ahouseAmenity->id,
                        'start_date' => $today,
                        'end_date' => $today,
                        'start_slot' => 'Daytime',
                        'end_slot' => 'Daytime',
                        'pricing_type' => 'Daytime',
                        'price_at_booking' => 300,
                        'is_aircon' => false,
                        'quantity' => 1,
                    ]
                ],
            ]);

        $response->assertRedirect('/staff/check-ins');

        // 2. Check Occupancy Monitor
        $occResponse = $this->authStaff()->get('/staff/occupancy-monitor');
        $occResponse->assertOk();
        $occData = $occResponse->viewData('occupancyData');
        
        $this->assertNotEmpty($occData[$this->ahouseAmenity->id]['occupied']);
        $this->assertEquals(1, $occResponse->viewData('occupiedCount'));

        // Cottage 1 should still be available
        $this->assertEmpty($occData[$this->cottageAmenity->id]['occupied']);
        $this->assertEmpty($occData[$this->cottageAmenity->id]['reserved']);

        // 3. Check Availability API
        $apiResponse = $this->getJson("/api/amenities/availability?start_date={$today}&end_date={$today}&start_slot=Daytime&end_slot=Daytime");
        $apiResponse->assertOk();
        $amenitiesList = $apiResponse->json('amenities');

        $ahouseApi = collect($amenitiesList)->firstWhere('id', $this->ahouseAmenity->id);
        $this->assertNotNull($ahouseApi);
        $this->assertFalse($ahouseApi['is_available']);

        $cottageApi = collect($amenitiesList)->firstWhere('id', $this->cottageAmenity->id);
        $this->assertNotNull($cottageApi);
        $this->assertTrue($cottageApi['is_available']);

        // 4. Check Guest Reservation Page Calendar Availability
        $calMonth = (int) now()->format('n') - 1;
        $calYear = (int) now()->format('Y');
        $calResponse = $this->getJson("/reservation/availability/calendar?amenity_id={$this->ahouseAmenity->id}&slot=Daytime&month={$calMonth}&year={$calYear}");
        $calResponse->assertOk();
        $calData = $calResponse->json('availability');
        
        $todayCalEntry = collect($calData)->firstWhere('date', $today);
        $this->assertNotNull($todayCalEntry);
        $this->assertFalse($todayCalEntry['daytime'], 'A-House 1 should not be available for daytime today because it is checked in for daytime');
        $this->assertTrue($todayCalEntry['nighttime'], 'A-House 1 SHOULD be available for nighttime today because it is only checked in for daytime');
        $this->assertFalse($todayCalEntry['daytonight'], 'A-House 1 should not be available for whole day today because daytime is booked');

        // Check availability API for Nighttime
        $nightApiResponse = $this->getJson("/api/amenities/availability?start_date={$today}&end_date={$today}&start_slot=Nighttime&end_slot=Nighttime");
        $nightApiResponse->assertOk();
        $nightAmenitiesList = $nightApiResponse->json('amenities');
        $ahouseNightApi = collect($nightAmenitiesList)->firstWhere('id', $this->ahouseAmenity->id);
        $this->assertNotNull($ahouseNightApi);
        $this->assertTrue($ahouseNightApi['is_available'], 'A-House 1 should be available in API for Nighttime');

        // Cottage 1 calendar check
        $cottageCalResponse = $this->getJson("/reservation/availability/calendar?amenity_id={$this->cottageAmenity->id}&slot=Daytime&month={$calMonth}&year={$calYear}");
        $cottageCalResponse->assertOk();
        $cottageCalData = $cottageCalResponse->json('availability');
        $cottageTodayEntry = collect($cottageCalData)->firstWhere('date', $today);
        $this->assertNotNull($cottageTodayEntry);
        $this->assertTrue($cottageTodayEntry['daytime'], 'Cottage 1 should be available for daytime today');
    }

    public function test_add_amenity_mid_stay_with_custom_future_schedule()
    {
        \Illuminate\Support\Carbon::setTestNow('2026-08-31 10:00:00');

        // Create checked-in reservation spanning Aug 31 to Sep 4
        $reservation = \App\Models\Reservation::create([
            'reservation_code' => 'RES-TEST-MIDSTAY',
            'booker_name' => 'John MidStay',
            'email' => 'johnmidstay@example.com',
            'phone' => '09123456789',
            'number_of_guests' => 2,
            'reservation_type' => 'walk_in',
            'reservation_date' => '2026-08-31',
            'end_date' => '2026-09-04',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 5,
            'total_amount' => 1000,
            'amount_paid' => 1000,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
            'status' => 'Checked-In',
        ]);

        \App\Models\ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $this->ahouseAmenity->id,
            'start_date' => '2026-08-31',
            'end_date' => '2026-09-04',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'price' => 1000,
            'pricing_type' => 'Continuous Stay (5D)',
            'status' => 'Active',
            'quantity' => 1,
        ]);

        // Add Cottage 1 mid-stay starting Sep 3 Nighttime to Sep 4 Daytime
        $response = $this->authStaff()->postJson("/staff/reservations/{$reservation->id}/amenities/add", [
            'amenity_id' => (string) $this->cottageAmenity->id,
            'start_date' => '2026-09-03',
            'start_slot' => 'Nighttime',
            'end_date' => '2026-09-04',
            'end_slot' => 'Daytime',
            'is_aircon' => false,
            'quantity' => 1,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertNotNull($response->json('starts_at'));
        $this->assertNotNull($response->json('checkout_at'));

        // Check availability of Cottage 1 on Aug 31 (today) - MUST be available because amenity starts on Sep 3!
        $aug31Api = $this->getJson('/api/amenities/availability?start_date=2026-08-31&end_date=2026-08-31&start_slot=Daytime&end_slot=Daytime');
        $aug31Api->assertOk();
        $cottageAug31 = collect($aug31Api->json('amenities'))->firstWhere('id', $this->cottageAmenity->id);
        $this->assertNotNull($cottageAug31);
        $this->assertTrue($cottageAug31['is_available'], 'Cottage 1 should be available on Aug 31 Daytime even though parent reservation is checked in today');

        // Check availability of Cottage 1 on Sep 1 (should be FREE)
        $sep1Api = $this->getJson('/api/amenities/availability?start_date=2026-09-01&end_date=2026-09-01&start_slot=Daytime&end_slot=Daytime');
        $sep1Api->assertOk();
        $cottageSep1 = collect($sep1Api->json('amenities'))->firstWhere('id', $this->cottageAmenity->id);
        $this->assertNotNull($cottageSep1);
        $this->assertTrue($cottageSep1['is_available'], 'Cottage 1 should be available on Sep 1 Daytime');

        // Check availability of Cottage 1 on Sep 3 Daytime (should be FREE)
        $sep3DayApi = $this->getJson('/api/amenities/availability?start_date=2026-09-03&end_date=2026-09-03&start_slot=Daytime&end_slot=Daytime');
        $sep3DayApi->assertOk();
        $cottageSep3Day = collect($sep3DayApi->json('amenities'))->firstWhere('id', $this->cottageAmenity->id);
        $this->assertNotNull($cottageSep3Day);
        $this->assertTrue($cottageSep3Day['is_available'], 'Cottage 1 should be available on Sep 3 Daytime');

        // Check availability of Cottage 1 on Sep 3 Nighttime (should be OCCUPIED / UNAVAILABLE)
        $sep3NightApi = $this->getJson('/api/amenities/availability?start_date=2026-09-03&end_date=2026-09-03&start_slot=Nighttime&end_slot=Nighttime');
        $sep3NightApi->assertOk();
        $cottageSep3Night = collect($sep3NightApi->json('amenities'))->firstWhere('id', $this->cottageAmenity->id);
        $this->assertNotNull($cottageSep3Night);
        $this->assertFalse($cottageSep3Night['is_available'], 'Cottage 1 should NOT be available on Sep 3 Nighttime');

        // Check that /staff/check-ins loads cleanly with status 200 without any closure variable issues
        $checkInsPage = $this->authStaff()->get('/staff/check-ins');
        $checkInsPage->assertOk();

        // Check that /staff/occupancy-monitor does NOT show Cottage 1 as occupied today!
        $occPage = $this->authStaff()->get('/staff/occupancy-monitor');
        $occPage->assertOk();
        $occData = $occPage->viewData('occupancyData');
        $this->assertEmpty($occData[$this->cottageAmenity->id]['occupied'], 'Cottage 1 should NOT be occupied today on occupancy monitor because it starts on Sep 3');
    }
}
