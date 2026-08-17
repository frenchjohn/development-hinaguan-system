<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\ParkSetting;
use App\Services\PayMongoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TodayDaytimePastAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ParkSetting::create([
            'daytime_start' => '06:00',
            'daytime_end' => '18:00',
            'nighttime_start' => '18:00',
            'nighttime_end' => '06:00',
            'daytime_adult_entrance_fee' => 70,
            'daytime_child_entrance_fee' => 50,
            'nighttime_adult_entrance_fee' => 100,
            'nighttime_child_entrance_fee' => 80,
        ]);
    }

    public function test_calendar_marks_today_daytime_unavailable_when_it_is_nighttime(): void
    {
        // Travel to today at 19:30 (Nighttime)
        Carbon::setTestNow(Carbon::parse('2026-08-20 19:30:00'));

        $amenity = Amenity::create([
            'id' => 'cottage-alpha',
            'amenities_name' => 'Cottage Alpha',
            'daytime_price' => 500,
            'nighttime_price' => 700,
            'minimum_capacity' => 5,
            'maximum_capacity' => 15,
            'status' => true,
        ]);

        $response = $this->getJson('/reservation/availability/calendar?amenity_id=cottage-alpha&slot=Daytime');
        $response->assertOk();

        $availability = collect($response->json('availability'));
        $todayEntry = $availability->firstWhere('date', '2026-08-20');
        $tomorrowEntry = $availability->firstWhere('date', '2026-08-21');

        $this->assertNotNull($todayEntry);
        // Today daytime and daytonight must be false because daytime has passed
        $this->assertFalse($todayEntry['daytime']);
        $this->assertFalse($todayEntry['daytonight']);
        // Today nighttime must still be available
        $this->assertTrue($todayEntry['nighttime']);

        // Tomorrow daytime must be available
        $this->assertNotNull($tomorrowEntry);
        $this->assertTrue($tomorrowEntry['daytime']);
        $this->assertTrue($tomorrowEntry['nighttime']);
        $this->assertTrue($tomorrowEntry['daytonight']);
    }

    public function test_availability_endpoint_blocks_today_daytime_when_it_is_nighttime(): void
    {
        // Travel to today at 20:00 (Nighttime)
        Carbon::setTestNow(Carbon::parse('2026-08-20 20:00:00'));

        $amenity = Amenity::create([
            'id' => 'cottage-beta',
            'amenities_name' => 'Cottage Beta',
            'daytime_price' => 500,
            'nighttime_price' => 700,
            'minimum_capacity' => 5,
            'maximum_capacity' => 15,
            'status' => true,
        ]);

        // Requesting today Daytime
        $responseDay = $this->getJson('/reservation/availability?start_date=2026-08-20&end_date=2026-08-20&start_slot=Daytime&end_slot=Daytime');
        $responseDay->assertOk();
        $this->assertContains('cottage-beta', $responseDay->json('occupied_amenity_ids'));

        // Requesting today Nighttime
        $responseNight = $this->getJson('/reservation/availability?start_date=2026-08-20&end_date=2026-08-20&start_slot=Nighttime&end_slot=Nighttime');
        $responseNight->assertOk();
        $this->assertNotContains('cottage-beta', $responseNight->json('occupied_amenity_ids'));
    }

    public function test_create_intent_rejects_today_daytime_when_it_is_nighttime(): void
    {
        // Travel to today at 21:00 (Nighttime)
        Carbon::setTestNow(Carbon::parse('2026-08-20 21:00:00'));

        $amenity = Amenity::create([
            'id' => 'cottage-gamma',
            'amenities_name' => 'Cottage Gamma',
            'daytime_price' => 500,
            'nighttime_price' => 700,
            'minimum_capacity' => 5,
            'maximum_capacity' => 15,
            'status' => true,
        ]);

        $mockPayMongo = Mockery::mock(PayMongoService::class);
        $mockPayMongo->shouldReceive('createPaymentIntent')->andReturn([
            'id' => 'pi_test_today_night',
            'client_key' => 'pi_test_client_key',
        ]);
        $this->app->instance(PayMongoService::class, $mockPayMongo);

        // Attempting today Daytime booking
        $responseFail = $this->postJson('/reservation/create-intent', [
            'booker_name' => 'Late Booker',
            'phone' => '09123456789',
            'email' => 'late@example.com',
            'number_of_guests' => 5,
            'reservation_date' => '2026-08-20',
            'end_date' => '2026-08-20',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'amenities' => [
                [
                    'amenity_id' => 'cottage-gamma',
                    'start_date' => '2026-08-20',
                    'end_date' => '2026-08-20',
                    'start_slot' => 'Daytime',
                    'end_slot' => 'Daytime',
                    'pricing_type' => 'Daytime',
                    'price_at_booking' => 500,
                ],
            ],
        ]);

        $responseFail->assertStatus(409);
        $this->assertFalse($responseFail->json('success'));

        // Booking today Nighttime succeeds
        $responsePass = $this->postJson('/reservation/create-intent', [
            'booker_name' => 'Night Booker',
            'phone' => '09123456789',
            'email' => 'night@example.com',
            'number_of_guests' => 5,
            'reservation_date' => '2026-08-20',
            'end_date' => '2026-08-20',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Nighttime',
            'total_days' => 1,
            'amenities' => [
                [
                    'amenity_id' => 'cottage-gamma',
                    'start_date' => '2026-08-20',
                    'end_date' => '2026-08-20',
                    'start_slot' => 'Nighttime',
                    'end_slot' => 'Nighttime',
                    'pricing_type' => 'Nighttime',
                    'price_at_booking' => 700,
                ],
            ],
        ]);

        $responsePass->assertStatus(200);
        $this->assertTrue($responsePass->json('success'));
    }
}
