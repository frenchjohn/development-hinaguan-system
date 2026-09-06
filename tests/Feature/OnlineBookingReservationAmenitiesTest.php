<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\ReservationGuest;
use App\Models\User;
use App\Services\PayMongoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class OnlineBookingReservationAmenitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_online_booking_creates_reservation_amenities_and_primary_guest()
    {
        $amenity = Amenity::create([
            'id' => 'payag-1',
            'amenities_name' => 'Payag Deluxe',
            'daytime_price' => 1000,
            'nighttime_price' => 1500,
            'minimum_capacity' => 1,
            'maximum_capacity' => 10,
            'additional_per_head' => 50,
            'status' => true,
        ]);

        $mockPayMongo = Mockery::mock(PayMongoService::class);
        $mockPayMongo->shouldReceive('createPaymentIntent')->andReturn([
            'id' => 'pi_test_12345',
            'client_key' => 'pi_test_12345_client_key',
        ]);
        $mockPayMongo->shouldReceive('createPaymentMethod')->andReturn([
            'id' => 'pm_test_12345',
        ]);
        $mockPayMongo->shouldReceive('attachPaymentMethod')->andReturn([
            'id' => 'pi_test_12345',
            'status' => 'succeeded',
            'next_action' => null,
        ]);
        $this->app->instance(PayMongoService::class, $mockPayMongo);

        // 1. Step 1: Create Intent
        $createIntentResponse = $this->postJson('/reservation/create-intent', [
            'booker_name' => 'John Doe',
            'phone' => '09123456789',
            'email' => 'john@example.com',
            'number_of_guests' => 6,
            'reservation_date' => '2026-08-20',
            'end_date' => '2026-08-25',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 6,
            'amenities' => [
                [
                    'amenity_id' => 'payag-1',
                    'start_date' => '2026-08-20',
                    'end_date' => '2026-08-25',
                    'start_slot' => 'Daytime',
                    'end_slot' => 'Daytime',
                    'pricing_type' => 'Daytime',
                    'price_at_booking' => 12000,
                    'day_slots_count' => 6,
                    'night_slots_count' => 5,
                ],
            ],
        ]);

        $createIntentResponse->assertStatus(200);
        $createIntentResponse->assertJson([
            'success' => true,
            'payment_intent_id' => 'pi_test_12345',
        ]);

        // 2. Step 2: Process Payment
        $processResponse = $this->postJson('/reservation/process-payment', [
            'payment_intent_id' => 'pi_test_12345',
            'client_key' => 'pi_test_12345_client_key',
            'payment_method_type' => 'gcash',
        ]);

        $processResponse->assertStatus(200);
        $processResponse->assertJson([
            'success' => true,
            'status' => 'succeeded',
        ]);

        // 3. Verify Reservation, Guest, and Amenities in DB
        $reservation = Reservation::where('email', 'john@example.com')->first();
        $this->assertNotNull($reservation);
        $this->assertEquals('John Doe', $reservation->booker_name);
        $this->assertEquals(6, $reservation->number_of_guests);
        $this->assertEquals(6, $reservation->total_days);

        // Verify Primary Guest was created
        $this->assertDatabaseHas('customers', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);
        $this->assertDatabaseHas('reservation_guests', [
            'reservation_id' => $reservation->id,
            'is_primary_guest' => true,
        ]);

        // Verify ReservationAmenity was created
        $this->assertDatabaseHas('reservation_amenities', [
            'reservation_id' => $reservation->id,
            'amenity_id' => 'payag-1',
            'price_at_booking' => 12000,
            'day_slots_count' => 6,
            'night_slots_count' => 5,
            'pricing_type' => 'Continuous Stay (6D)',
        ]);

        // 4. Verify Staff Reservations Page and Refresh Endpoint include amenities and guest data
        $staffResponse = $this->withSession(['auth_user' => [
            'id' => 1,
            'name' => 'Staff',
            'email' => 'staff@example.com',
            'role' => 'staff',
        ]])->getJson('/staff/reservations/refresh');

        $staffResponse->assertStatus(200);
        $resData = $staffResponse->json("reservations.{$reservation->id}");
        $this->assertNotNull($resData);
        $this->assertCount(1, $resData['reservation_amenities']);
        $this->assertEquals('Payag Deluxe', $resData['reservation_amenities'][0]['amenity']['amenities_name']);
        $this->assertEquals('Continuous Stay (6D)', $resData['reservation_amenities'][0]['pricing_type']);
        $this->assertCount(1, $resData['reservation_guests']);
        $this->assertEquals('John', $resData['reservation_guests'][0]['customer']['first_name']);
    }

    public function test_create_intent_validates_booker_info()
    {
        // 1. Invalid booker name with numbers
        $res = $this->postJson('/reservation/create-intent', [
            'booker_name' => 'John123',
            'phone' => '09123456789',
            'email' => 'john@example.com',
            'number_of_guests' => 2,
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['booker_name']);

        // 2. Invalid booker name with symbols
        $res = $this->postJson('/reservation/create-intent', [
            'booker_name' => 'John @Doe!',
            'phone' => '09123456789',
            'email' => 'john@example.com',
            'number_of_guests' => 2,
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['booker_name']);

        // 3. Invalid phone number (letters / invalid length)
        $res = $this->postJson('/reservation/create-intent', [
            'booker_name' => 'John Doe',
            'phone' => '12345',
            'email' => 'john@example.com',
            'number_of_guests' => 2,
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['phone']);

        // 4. Invalid email format
        $res = $this->postJson('/reservation/create-intent', [
            'booker_name' => 'John Doe',
            'phone' => '09123456789',
            'email' => 'not-an-email',
            'number_of_guests' => 2,
        ]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['email']);
    }
}
