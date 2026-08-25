<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Customer;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\ReservationEntranceFee;
use App\Models\ReservationGuest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffWalkInReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ParkSetting::create([
            'daytime_adult_entrance_fee' => 100,
            'daytime_child_entrance_fee' => 50,
            'nighttime_adult_entrance_fee' => 150,
            'nighttime_child_entrance_fee' => 75,
            'day_pool_fee' => 50,
            'night_pool_fee' => 75,
            'daytime_start' => '06:00',
            'daytime_end' => '18:00',
            'nighttime_start' => '18:00',
            'nighttime_end' => '06:00',
        ]);
    }

    public function test_staff_can_create_walk_in_reservation_with_multi_day_and_amenities(): void
    {
        $amenity = Amenity::create([
            'id' => 'AMENITY-COTTAGE-001',
            'amenities_name' => 'Cottage Deluxe',
            'description' => 'Spacious cottage',
            'daytime_price' => 500,
            'nighttime_price' => 600,
            'daytime_aircon_price' => 700,
            'nighttime_aircon_price' => 800,
            'minimum_capacity' => 2,
            'maximum_capacity' => 10,
            'status' => true,
        ]);

        $response = $this->withSession([
            'auth_user' => ['id' => 1, 'role' => 'staff', 'name' => 'Staff Member'],
        ])->post(route('staff.checkins.guests.store'), [
            'guest_mode' => 'with_primary',
            'reservation_type' => 'walk_in',
            'start_date' => '2026-08-25',
            'end_date' => '2026-08-27',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Daytime',
            'total_days' => 3,
            'include_pool' => 'on',
            'primary_guest' => [
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'age' => 30,
                'gender' => 'Male',
                'is_foreigner' => 0,
                'phone' => '09123456789',
                'email' => 'juan@example.com',
            ],
            'companions' => [
                [
                    'first_name' => 'Maria',
                    'last_name' => 'Dela Cruz',
                    'age' => 8,
                    'gender' => 'Female',
                    'is_foreigner' => 0,
                ],
            ],
            'selected_amenities' => [
                [
                    'amenity_id' => (string) $amenity->id,
                    'start_date' => '2026-08-25',
                    'end_date' => '2026-08-26',
                    'start_slot' => 'Nighttime',
                    'end_slot' => 'Daytime',
                    'is_aircon' => 1,
                    'pricing_type' => 'Continuous Stay (2D) Aircon',
                ],
            ],
            'total_amount' => 2000,
        ]);

        $response->assertRedirect(route('staff.checkins'));
        $response->assertSessionHas('success');

        $reservation = Reservation::where('email', 'juan@example.com')->first();
        $this->assertNotNull($reservation);
        $this->assertEquals('walk_in', $reservation->reservation_type);
        $this->assertEquals('Checked In', $reservation->status);
        $this->assertEquals('Paid', $reservation->payment_status);
        $this->assertEquals('2026-08-25', $reservation->reservation_date ? \Illuminate\Support\Carbon::parse($reservation->reservation_date)->toDateString() : null);
        $this->assertEquals('2026-08-27', $reservation->end_date ? \Illuminate\Support\Carbon::parse($reservation->end_date)->toDateString() : null);
        $this->assertEquals('Nighttime', $reservation->start_slot);
        $this->assertEquals('Daytime', $reservation->end_slot);
        $this->assertEquals(3, $reservation->total_days);
        $this->assertEquals(2, $reservation->number_of_guests);

        // Check amenity record
        $resAmenity = ReservationAmenity::where('reservation_id', $reservation->id)->first();
        $this->assertNotNull($resAmenity);
        $this->assertEquals($amenity->id, $resAmenity->amenity_id);
        $this->assertEquals('2026-08-25', $resAmenity->start_date ? \Illuminate\Support\Carbon::parse($resAmenity->start_date)->toDateString() : null);
        $this->assertEquals('2026-08-26', $resAmenity->end_date ? \Illuminate\Support\Carbon::parse($resAmenity->end_date)->toDateString() : null);
        $this->assertEquals('Nighttime', $resAmenity->start_slot);
        $this->assertEquals('Daytime', $resAmenity->end_slot);
        $this->assertEquals(1, $resAmenity->day_slots_count);
        $this->assertEquals(1, $resAmenity->night_slots_count);
        $this->assertEquals(1500, $resAmenity->price_at_booking); // 800 (night AC) + 700 (day AC)
        $this->assertEquals('Active', $resAmenity->status);

        // Check entrance fees
        $entranceFee = ReservationEntranceFee::where('reservation_id', $reservation->id)->first();
        $this->assertNotNull($entranceFee);
        $this->assertEquals(1, $entranceFee->adult_count);
        $this->assertEquals(1, $entranceFee->child_count);
        $this->assertEquals(250, $entranceFee->pool_fee); // 2 guests * (50 day pool + 75 night pool) = 250
    }

    public function test_cannot_book_amenity_outside_master_reservation_dates(): void
    {
        $amenity = Amenity::create([
            'id' => 'AMENITY-GAZEBO-001',
            'amenities_name' => 'Gazebo',
            'description' => 'Cozy gazebo',
            'daytime_price' => 300,
            'nighttime_price' => 400,
            'minimum_capacity' => 1,
            'maximum_capacity' => 6,
            'status' => true,
        ]);

        $response = $this->withSession([
            'auth_user' => ['id' => 1, 'role' => 'staff', 'name' => 'Staff Member'],
        ])->from(route('staff.checkins'))->post(route('staff.checkins.guests.store'), [
            'guest_mode' => 'with_primary',
            'reservation_type' => 'walk_in',
            'start_date' => '2026-08-25',
            'end_date' => '2026-08-26',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 2,
            'primary_guest' => [
                'first_name' => 'Pedro',
                'last_name' => 'Penduko',
            ],
            'selected_amenities' => [
                [
                    'amenity_id' => (string) $amenity->id,
                    'start_date' => '2026-08-25',
                    'end_date' => '2026-08-27', // Exceeds master end_date (Aug 26)
                    'start_slot' => 'Daytime',
                    'end_slot' => 'Daytime',
                ],
            ],
            'total_amount' => 1000,
        ]);

        $response->assertRedirect(route('staff.checkins'));
        $response->assertSessionHasErrors(['selected_amenities']);
        $this->assertDatabaseMissing('reservations', ['booker_name' => 'Pedro Penduko']);
    }

    public function test_cannot_book_amenity_slot_that_is_already_taken(): void
    {
        $amenity = Amenity::create([
            'id' => 'AMENITY-PAVILION-001',
            'amenities_name' => 'Pavilion A',
            'description' => 'Main pavilion',
            'daytime_price' => 1000,
            'nighttime_price' => 1200,
            'minimum_capacity' => 5,
            'maximum_capacity' => 30,
            'status' => true,
        ]);

        // Create an existing active reservation on Aug 25 Daytime
        $existingRes = Reservation::create([
            'booker_name' => 'First Guest',
            'phone' => '09111111111',
            'email' => 'first@example.com',
            'number_of_guests' => 1,
            'reservation_date' => '2026-08-25',
            'end_date' => '2026-08-25',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'status' => 'Checked In',
            'reservation_type' => 'walk_in',
            'payment_status' => 'Paid',
            'total_amount' => 1000,
            'amount_paid' => 1000,
            'remaining_balance' => 0,
        ]);

        ReservationAmenity::create([
            'reservation_id' => $existingRes->id,
            'amenity_id' => $amenity->id,
            'start_date' => '2026-08-25',
            'end_date' => '2026-08-25',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 1000,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // Second walk-in attempts to book the same amenity on Aug 25 Daytime
        $response = $this->withSession([
            'auth_user' => ['id' => 1, 'role' => 'staff', 'name' => 'Staff Member'],
        ])->from(route('staff.checkins'))->post(route('staff.checkins.guests.store'), [
            'guest_mode' => 'with_primary',
            'reservation_type' => 'walk_in',
            'start_date' => '2026-08-25',
            'end_date' => '2026-08-25',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'primary_guest' => [
                'first_name' => 'Second',
                'last_name' => 'Guest',
            ],
            'selected_amenities' => [
                [
                    'amenity_id' => (string) $amenity->id,
                    'start_date' => '2026-08-25',
                    'end_date' => '2026-08-25',
                    'start_slot' => 'Daytime',
                    'end_slot' => 'Daytime',
                ],
            ],
            'total_amount' => 1000,
        ]);

        $response->assertRedirect(route('staff.checkins'));
        $response->assertSessionHasErrors(['selected_amenities']);
        $this->assertDatabaseMissing('reservations', ['booker_name' => 'Second Guest']);
    }

    public function test_amenity_availability_api_returns_free_and_occupied_amenities(): void
    {
        $cottage = Amenity::create([
            'id' => 'AMENITY-COTTAGE-TEST',
            'amenities_name' => 'Cottage Test',
            'daytime_price' => 500,
            'nighttime_price' => 600,
            'minimum_capacity' => 1,
            'maximum_capacity' => 10,
            'status' => true,
        ]);

        $gazebo = Amenity::create([
            'id' => 'AMENITY-GAZEBO-TEST',
            'amenities_name' => 'Gazebo Test',
            'daytime_price' => 300,
            'nighttime_price' => 400,
            'minimum_capacity' => 1,
            'maximum_capacity' => 6,
            'status' => true,
        ]);

        // Book Cottage on 2026-08-25 Daytime
        $res = Reservation::create([
            'booker_name' => 'Occ Guest',
            'phone' => '09122222222',
            'email' => 'occ@example.com',
            'number_of_guests' => 1,
            'reservation_date' => '2026-08-25',
            'end_date' => '2026-08-25',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'status' => 'Checked In',
            'reservation_type' => 'walk_in',
            'payment_status' => 'Paid',
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
        ]);

        ReservationAmenity::create([
            'reservation_id' => $res->id,
            'amenity_id' => $cottage->id,
            'start_date' => '2026-08-25',
            'end_date' => '2026-08-25',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // Query API for 2026-08-25 Daytime
        $apiResponse = $this->getJson(route('api.amenities.availability', [
            'start_date' => '2026-08-25',
            'end_date' => '2026-08-25',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
        ]));

        $apiResponse->assertOk();
        $data = $apiResponse->json();
        $this->assertContains('AMENITY-COTTAGE-TEST', $data['occupied_ids']);
        $this->assertNotContains('AMENITY-GAZEBO-TEST', $data['occupied_ids']);

        $cottageItem = collect($data['amenities'])->firstWhere('id', 'AMENITY-COTTAGE-TEST');
        $this->assertFalse($cottageItem['is_available']);

        $gazeboItem = collect($data['amenities'])->firstWhere('id', 'AMENITY-GAZEBO-TEST');
        $this->assertTrue($gazeboItem['is_available']);
    }

    public function test_walk_in_with_specific_pool_access(): void
    {
        $response = $this->withSession([
            'auth_user' => ['id' => 1, 'role' => 'staff', 'name' => 'Staff Member'],
        ])->post(route('staff.checkins.guests.store'), [
            'guest_mode' => 'with_primary',
            'reservation_type' => 'walk_in',
            'start_date' => '2026-08-25',
            'end_date' => '2026-08-25',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'pool_option' => 'specific',
            'primary_guest' => [
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'age' => 35,
                'gender' => 'Male',
                'has_pool_access' => 1, // Primary has pool
            ],
            'companions' => [
                [
                    'first_name' => 'Luigi',
                    'last_name' => 'Rossi',
                    'age' => 30,
                    'gender' => 'Male',
                    'has_pool_access' => 0, // No pool
                ],
                [
                    'first_name' => 'Peach',
                    'last_name' => 'Toadstool',
                    'age' => 28,
                    'gender' => 'Female',
                    'has_pool_access' => 1, // Has pool
                ],
            ],
        ]);

        $response->assertRedirect(route('staff.checkins'));

        $res = Reservation::where('booker_name', 'Mario Rossi')->first();
        $this->assertNotNull($res);
        $this->assertEquals(3, $res->number_of_guests);

        $entranceFee = ReservationEntranceFee::where('reservation_id', $res->id)->first();
        $this->assertNotNull($entranceFee);
        $this->assertEquals('specific', $entranceFee->pool_option);
        $this->assertEquals(2, $entranceFee->pool_access_count);
        $this->assertEquals(100, $entranceFee->pool_fee); // 2 guests * 50 day_pool_fee

        $guests = ReservationGuest::where('reservation_id', $res->id)->with('customer')->get();
        $this->assertCount(3, $guests);

        $marioGuest = $guests->first(fn($g) => $g->customer->first_name === 'Mario');
        $this->assertTrue((bool) $marioGuest->has_pool_access);

        $luigiGuest = $guests->first(fn($g) => $g->customer->first_name === 'Luigi');
        $this->assertFalse((bool) $luigiGuest->has_pool_access);

        $peachGuest = $guests->first(fn($g) => $g->customer->first_name === 'Peach');
        $this->assertTrue((bool) $peachGuest->has_pool_access);
    }

    public function test_walk_in_with_free_promo_pool_access(): void
    {
        $response = $this->withSession([
            'auth_user' => ['id' => 1, 'role' => 'staff', 'name' => 'Staff Member'],
        ])->post(route('staff.checkins.guests.store'), [
            'guest_mode' => 'with_primary',
            'reservation_type' => 'walk_in',
            'start_date' => '2026-08-25',
            'end_date' => '2026-08-25',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'pool_option' => 'all_free',
            'primary_guest' => [
                'first_name' => 'Promo',
                'last_name' => 'Guest',
                'age' => 25,
            ],
            'companions' => [
                [
                    'first_name' => 'Promo',
                    'last_name' => 'Companion',
                    'age' => 22,
                ],
            ],
        ]);

        $response->assertRedirect(route('staff.checkins'));

        $res = Reservation::where('booker_name', 'Promo Guest')->first();
        $this->assertNotNull($res);

        $entranceFee = ReservationEntranceFee::where('reservation_id', $res->id)->first();
        $this->assertEquals('all_free', $entranceFee->pool_option);
        $this->assertEquals(0, $entranceFee->pool_fee);
        $this->assertEquals(2, $entranceFee->pool_access_count);

        $guests = ReservationGuest::where('reservation_id', $res->id)->get();
        foreach ($guests as $guest) {
            $this->assertTrue((bool) $guest->has_pool_access);
        }
    }

    public function test_walk_in_with_no_pool_access(): void
    {
        $response = $this->withSession([
            'auth_user' => ['id' => 1, 'role' => 'staff', 'name' => 'Staff Member'],
        ])->post(route('staff.checkins.guests.store'), [
            'guest_mode' => 'with_primary',
            'reservation_type' => 'walk_in',
            'start_date' => '2026-08-25',
            'end_date' => '2026-08-25',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'pool_option' => 'no_pool',
            'primary_guest' => [
                'first_name' => 'Standard',
                'last_name' => 'Visitor',
                'age' => 40,
            ],
        ]);

        $response->assertRedirect(route('staff.checkins'));

        $res = Reservation::where('booker_name', 'Standard Visitor')->first();
        $this->assertNotNull($res);

        $entranceFee = ReservationEntranceFee::where('reservation_id', $res->id)->first();
        $this->assertEquals('no_pool', $entranceFee->pool_option);
        $this->assertEquals(0, $entranceFee->pool_fee);
        $this->assertEquals(0, $entranceFee->pool_access_count);

        $primaryGuest = ReservationGuest::where('reservation_id', $res->id)->first();
        $this->assertFalse((bool) $primaryGuest->has_pool_access);
    }

    public function test_online_reservation_check_in_with_specific_pool_access(): void
    {
        $res = Reservation::create([
            'booker_name' => 'Online Booker',
            'phone' => '09123456789',
            'email' => 'booker@example.com',
            'reservation_date' => '2026-08-25',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 3,
            'status' => 'Pending',
            'total_amount' => 500,
            'amount_paid' => 250,
            'remaining_balance' => 250,
            'payment_status' => 'Partially Paid',
        ]);

        $amenity = Amenity::create([
            'id' => 'AMENITY-TEST-ONLINE-1',
            'amenities_name' => 'Cottage Online',
            'daytime_price' => 500,
            'nighttime_price' => 600,
            'daytime_aircon_price' => 700,
            'nighttime_aircon_price' => 800,
            'minimum_capacity' => 2,
            'maximum_capacity' => 10,
            'status' => true,
        ]);

        ReservationAmenity::create([
            'reservation_id' => $res->id,
            'amenity_id' => $amenity->id,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
            'start_date' => '2026-08-25',
            'end_date' => '2026-08-25',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'status' => 'Active',
        ]);

        $response = $this->withSession([
            'auth_user' => ['id' => 1, 'role' => 'staff', 'name' => 'Staff Member'],
        ])->post("/staff/reservations/{$res->id}/check-in", [
            'guest_mode' => 'with_primary',
            'pool_option' => 'specific',
            'primary_guest' => [
                'first_name' => 'Online',
                'last_name' => 'Booker',
                'age' => 30,
                'has_pool_access' => '1',
            ],
            'companions' => [
                [
                    'first_name' => 'Companion',
                    'last_name' => 'One',
                    'age' => 25,
                    'has_pool_access' => '1',
                ],
                [
                    'first_name' => 'Companion',
                    'last_name' => 'Two',
                    'age' => 10, // Child
                    'has_pool_access' => '0',
                ],
            ],
        ]);

        $response->assertOk();

        $res->refresh();
        $this->assertEquals('Checked In', $res->status);
        $this->assertEquals('Paid', $res->payment_status);

        $entranceFee = ReservationEntranceFee::where('reservation_id', $res->id)->first();
        $this->assertNotNull($entranceFee);
        $this->assertEquals('specific', $entranceFee->pool_option);
        $this->assertEquals(2, $entranceFee->pool_access_count);
        // 2 adults (2 * 100) + 1 child (50) = 250 entrance. 2 pool passes (2 * 50) = 100. Total = 350.
        $this->assertEquals(100, $entranceFee->pool_fee);
        $this->assertEquals(350, $entranceFee->total_amount);

        $guests = ReservationGuest::where('reservation_id', $res->id)->get();
        $this->assertCount(3, $guests);
        $poolCount = $guests->where('has_pool_access', true)->count();
        $this->assertEquals(2, $poolCount);
    }

    public function test_online_reservation_check_in_with_promo_free_pool(): void
    {
        $res = Reservation::create([
            'booker_name' => 'Promo Online',
            'phone' => '09123456789',
            'email' => 'promo@example.com',
            'reservation_date' => '2026-08-25',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 2,
            'status' => 'Pending',
            'total_amount' => 300,
            'amount_paid' => 300,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $response = $this->withSession([
            'auth_user' => ['id' => 1, 'role' => 'staff', 'name' => 'Staff Member'],
        ])->post("/staff/reservations/{$res->id}/check-in", [
            'guest_mode' => 'with_primary',
            'pool_option' => 'all_free',
            'primary_guest' => [
                'first_name' => 'Promo',
                'last_name' => 'Online',
                'age' => 28,
            ],
            'companions' => [
                [
                    'first_name' => 'Promo',
                    'last_name' => 'Companion',
                    'age' => 27,
                ],
            ],
        ]);

        $response->assertOk();

        $entranceFee = ReservationEntranceFee::where('reservation_id', $res->id)->first();
        $this->assertEquals('all_free', $entranceFee->pool_option);
        $this->assertEquals(0, $entranceFee->pool_fee);
        $this->assertEquals(2, $entranceFee->pool_access_count);

        $guests = ReservationGuest::where('reservation_id', $res->id)->get();
        foreach ($guests as $guest) {
            $this->assertTrue((bool) $guest->has_pool_access);
        }
    }

    public function test_online_reservation_check_in_with_boolean_false_companions(): void
    {
        $res = Reservation::create([
            'booker_name' => 'John Doe',
            'phone' => '09930457138',
            'email' => 'john@example.com',
            'reservation_date' => '2026-08-25',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 4,
            'status' => 'Pending',
            'total_amount' => 4200,
            'amount_paid' => 0,
            'remaining_balance' => 4200,
            'payment_status' => 'Unpaid',
        ]);

        $response = $this->withSession([
            'auth_user' => ['id' => 1, 'role' => 'staff', 'name' => 'Staff Member'],
        ])->post("/staff/reservations/{$res->id}/check-in", [
            'guest_mode' => 'with_primary',
            'pool_option' => 'specific',
            'primary_guest' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'age' => 23,
                'gender' => 'Male',
                'is_foreigner' => false,
                'phone' => '09930457138',
                'email' => 'john@example.com',
                'has_pool_access' => true,
            ],
            'companions' => [
                [
                    'first_name' => 'Companion',
                    'last_name' => 'A',
                    'age' => '20',
                    'gender' => 'Female',
                    'is_foreigner' => false,
                    'has_pool_access' => true,
                ],
                [
                    'first_name' => 'Companion',
                    'last_name' => 'B',
                    'age' => '22',
                    'gender' => 'Male',
                    'is_foreigner' => false,
                    'has_pool_access' => false,
                ],
                [
                    'first_name' => 'Companion',
                    'last_name' => 'C',
                    'age' => '25',
                    'gender' => 'Female',
                    'is_foreigner' => false,
                    'has_pool_access' => false,
                ],
            ],
        ]);

        $response->assertOk();

        $res->refresh();
        $this->assertEquals('Checked In', $res->status);
    }

    public function test_bulk_companions_separate_pool_and_non_pool_checkout(): void
    {
        $res = Reservation::create([
            'booker_name' => 'Bulk Tester',
            'phone' => '09930457138',
            'email' => 'bulktester@example.com',
            'number_of_guests' => 5,
            'reservation_date' => '2026-08-25',
            'end_date' => '2026-08-25',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'status' => 'Checked In',
            'reservation_type' => 'walk_in',
            'payment_status' => 'Paid',
            'total_amount' => 1500,
            'amount_paid' => 1500,
            'remaining_balance' => 0,
        ]);

        $primaryCustomer = Customer::create([
            'first_name' => 'Bulk',
            'last_name' => 'Leader',
            'age' => 30,
            'gender' => 'Male',
            'is_foreigner' => false,
            'phone' => '09930457138',
            'email' => 'bulktester@example.com',
        ]);

        ReservationGuest::create([
            'reservation_id' => $res->id,
            'customer_id' => $primaryCustomer->id,
            'is_primary_guest' => true,
            'has_pool_access' => true,
        ]);

        // Create 2 pool bulk companions and 2 no-pool bulk companions
        for ($i = 1; $i <= 4; $i++) {
            $c = Customer::create([
                'first_name' => 'Companion',
                'last_name' => (string) $i,
                'age' => 30,
                'gender' => 'Male',
                'is_foreigner' => false,
            ]);
            ReservationGuest::create([
                'reservation_id' => $res->id,
                'customer_id' => $c->id,
                'is_primary_guest' => false,
                'has_pool_access' => $i <= 2, // 1,2 have pool; 3,4 do not
            ]);
        }

        // Check out 1 pool companion
        $poolCheckoutResp = $this->withSession([
            'auth_user' => ['id' => 1, 'role' => 'staff', 'name' => 'Staff Member'],
        ])->postJson("/staff/reservations/{$res->id}/bulk-companions/check-out", [
            'count' => 1,
            'pool_access_type' => 'with_pool',
            'gender' => 'Male',
            'age_group' => '18-59',
            'is_foreigner' => false,
        ]);

        $poolCheckoutResp->assertOk();
        $this->assertEquals(1, $poolCheckoutResp->json('checked_out'));

        // Verify that only 1 companion with pool access was checked out
        $activePool = $res->reservationGuests()->where('is_primary_guest', false)->where('has_pool_access', true)->whereNull('checked_out_at')->count();
        $this->assertEquals(1, $activePool);

        $activeNoPool = $res->reservationGuests()->where('is_primary_guest', false)->where('has_pool_access', false)->whereNull('checked_out_at')->count();
        $this->assertEquals(2, $activeNoPool);

        // Check out 2 no-pool companions
        $noPoolCheckoutResp = $this->withSession([
            'auth_user' => ['id' => 1, 'role' => 'staff', 'name' => 'Staff Member'],
        ])->postJson("/staff/reservations/{$res->id}/bulk-companions/check-out", [
            'count' => 2,
            'pool_access_type' => 'without_pool',
            'gender' => 'Male',
            'age_group' => '18-59',
            'is_foreigner' => false,
        ]);

        $noPoolCheckoutResp->assertOk();
        $this->assertEquals(2, $noPoolCheckoutResp->json('checked_out'));

        $activeNoPoolAfter = $res->reservationGuests()->where('is_primary_guest', false)->where('has_pool_access', false)->whereNull('checked_out_at')->count();
        $this->assertEquals(0, $activeNoPoolAfter);
    }
}


