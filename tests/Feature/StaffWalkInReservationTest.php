<?php

namespace Tests\Feature;

use App\Models\Amenity;
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
        $this->assertEquals(125, $entranceFee->pool_fee); // (50 day pool + 75 night pool) = 125
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
}
