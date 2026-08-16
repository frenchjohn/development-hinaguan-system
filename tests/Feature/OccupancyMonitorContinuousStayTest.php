<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OccupancyMonitorContinuousStayTest extends TestCase
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

    public function test_continuous_multiday_stay_occupies_both_sessions_on_middle_days_and_correct_session_on_end_day(): void
    {
        Carbon::setTestNow('2026-08-17 10:00:00');

        $amenity = Amenity::create([
            'id' => 'am-villa-1',
            'amenities_name' => 'Continuous Test Villa',
            'daytime_price' => 1000,
            'nighttime_price' => 1500,
            'minimum_capacity' => 1,
            'maximum_capacity' => 10,
            'status' => true,
        ]);

        // Reservation 1: Continuous Stay from Aug 16 Daytime to Aug 19 Daytime (Checked In)
        $res = Reservation::create([
            'booker_name' => 'John Doe',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.occ@example.com',
            'contact_no' => '09123456789',
            'phone' => '09123456789',
            'number_of_guests' => 1,
            'reservation_date' => '2026-08-16',
            'end_date' => '2026-08-19',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'status' => 'Checked In',
            'payment_status' => 'Paid',
            'total_amount' => 5000,
            'amount_paid' => 5000,
            'remaining_balance' => 0,
            'created_by' => 1,
        ]);

        ReservationAmenity::create([
            'reservation_id' => $res->id,
            'amenity_id' => $amenity->id,
            'pricing_type' => 'Continuous Stay (4D)',
            'start_date' => '2026-08-16',
            'end_date' => '2026-08-19',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'price_at_booking' => 5000,
            'status' => 'Active',
        ]);

        // On today (2026-08-17 - Middle Day):
        // Both Daytime and Nighttime must be occupied (unavailable_slots = daytime,nighttime)
        $response = $this->withSession(['auth_user' => ['role' => 'staff', 'id' => 1, 'name' => 'Staff']])
            ->get(route('staff.occupancy-monitor'));

        $response->assertStatus(200);
        $response->assertSee('Continuous Test Villa');
        $response->assertSee('data-unavailable-slots="daytime,nighttime"', false);
        $response->assertSee('data-available-slots=""', false);

        // Now test on End Day (2026-08-19):
        // Daytime should be occupied, Nighttime should be available!
        Carbon::setTestNow('2026-08-19 10:00:00');

        $responseEnd = $this->withSession(['auth_user' => ['role' => 'staff', 'id' => 1, 'name' => 'Staff']])
            ->get(route('staff.occupancy-monitor'));

        $responseEnd->assertStatus(200);
        $responseEnd->assertSee('data-unavailable-slots="daytime"', false);
        $responseEnd->assertSee('data-available-slots="nighttime"', false);

        Carbon::setTestNow(); // reset
    }
}
