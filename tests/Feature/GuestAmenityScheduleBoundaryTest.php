<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\ParkSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestAmenityScheduleBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private Amenity $amenity;

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

        $this->amenity = Amenity::create([
            'id' => 'AMENITY-TEST-001',
            'amenities_name' => 'Payag Luxury',
            'description' => 'Luxury payag',
            'daytime_price' => 250,
            'nighttime_price' => 300,
            'minimum_capacity' => 1,
            'maximum_capacity' => 10,
            'status' => true,
            'has_aircon' => false,
        ]);
    }

    public function test_amenity_schedule_succeeds_when_strictly_within_reservation_boundaries()
    {
        $mockPayMongo = \Mockery::mock(\App\Services\PayMongoService::class);
        $mockPayMongo->shouldReceive('createPaymentIntent')
            ->once()
            ->andReturn([
                'id' => 'pi_test_123',
                'client_key' => 'client_key_123',
            ]);
        $this->app->instance(\App\Services\PayMongoService::class, $mockPayMongo);

        $payload = [
            'booker_name' => 'John Guest',
            'email' => 'john@example.com',
            'phone' => '09123456789',
            'number_of_guests' => 2,
            'reservation_date' => '2026-08-25',
            'end_date' => '2026-08-30',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Daytime',
            'total_days' => 6,
            'amenities' => [
                [
                    'amenity_id' => $this->amenity->id,
                    'start_date' => '2026-08-26',
                    'end_date' => '2026-08-29',
                    'start_slot' => 'Daytime',
                    'end_slot' => 'Nighttime',
                    'pricing_type' => 'Daytime',
                    'price_at_booking' => 1000,
                ]
            ]
        ];

        $response = $this->postJson('/reservation/create-intent', $payload);
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'payment_intent_id' => 'pi_test_123',
        ]);
    }

    public function test_amenity_schedule_fails_when_start_date_before_reservation()
    {
        $payload = [
            'booker_name' => 'John Guest',
            'email' => 'john@example.com',
            'phone' => '09123456789',
            'number_of_guests' => 2,
            'reservation_date' => '2026-08-25',
            'end_date' => '2026-08-30',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Daytime',
            'total_days' => 6,
            'amenities' => [
                [
                    'amenity_id' => $this->amenity->id,
                    'start_date' => '2026-08-24', // BEFORE reservation start!
                    'end_date' => '2026-08-29',
                    'start_slot' => 'Nighttime',
                    'end_slot' => 'Daytime',
                    'pricing_type' => 'Nighttime',
                    'price_at_booking' => 1000,
                ]
            ]
        ];

        $response = $this->postJson('/reservation/create-intent', $payload);
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('must fall within the overall reservation dates', $response->json('message'));
    }

    public function test_amenity_schedule_fails_when_end_date_after_reservation()
    {
        $payload = [
            'booker_name' => 'John Guest',
            'email' => 'john@example.com',
            'phone' => '09123456789',
            'number_of_guests' => 2,
            'reservation_date' => '2026-08-25',
            'end_date' => '2026-08-30',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Daytime',
            'total_days' => 6,
            'amenities' => [
                [
                    'amenity_id' => $this->amenity->id,
                    'start_date' => '2026-08-26',
                    'end_date' => '2026-08-31', // AFTER reservation end!
                    'start_slot' => 'Daytime',
                    'end_slot' => 'Daytime',
                    'pricing_type' => 'Daytime',
                    'price_at_booking' => 1000,
                ]
            ]
        ];

        $response = $this->postJson('/reservation/create-intent', $payload);
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('must fall within the overall reservation dates', $response->json('message'));
    }

    public function test_amenity_schedule_fails_when_start_slot_is_daytime_on_nighttime_start_day()
    {
        $payload = [
            'booker_name' => 'John Guest',
            'email' => 'john@example.com',
            'phone' => '09123456789',
            'number_of_guests' => 2,
            'reservation_date' => '2026-08-25',
            'end_date' => '2026-08-30',
            'start_slot' => 'Nighttime', // Reservation starts Nighttime on Aug 25
            'end_slot' => 'Daytime',
            'total_days' => 6,
            'amenities' => [
                [
                    'amenity_id' => $this->amenity->id,
                    'start_date' => '2026-08-25',
                    'end_date' => '2026-08-29',
                    'start_slot' => 'Daytime', // Cannot start Daytime on Aug 25!
                    'end_slot' => 'Daytime',
                    'pricing_type' => 'Daytime',
                    'price_at_booking' => 1000,
                ]
            ]
        ];

        $response = $this->postJson('/reservation/create-intent', $payload);
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('cannot be Daytime because the reservation starts at Nighttime', $response->json('message'));
    }

    public function test_amenity_schedule_fails_when_end_slot_is_nighttime_on_daytime_end_day()
    {
        $payload = [
            'booker_name' => 'John Guest',
            'email' => 'john@example.com',
            'phone' => '09123456789',
            'number_of_guests' => 2,
            'reservation_date' => '2026-08-25',
            'end_date' => '2026-08-30',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Daytime', // Reservation ends Daytime on Aug 30
            'total_days' => 6,
            'amenities' => [
                [
                    'amenity_id' => $this->amenity->id,
                    'start_date' => '2026-08-26',
                    'end_date' => '2026-08-30',
                    'start_slot' => 'Daytime',
                    'end_slot' => 'Nighttime', // Cannot end Nighttime on Aug 30!
                    'pricing_type' => 'Daytime',
                    'price_at_booking' => 1000,
                ]
            ]
        ];

        $response = $this->postJson('/reservation/create-intent', $payload);
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('cannot be Nighttime because the reservation ends at Daytime', $response->json('message'));
    }
}
