<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\StaffAccount;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EarlyCheckInAmenityOccupancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ParkSetting::create([
            'park_name' => 'Hinaguan Nature Park',
            'daytime_start' => '06:00',
            'daytime_end' => '18:00',
            'nighttime_start' => '18:00',
            'nighttime_end' => '06:00',
            'daytime_adult_entrance_fee' => 70,
            'daytime_child_entrance_fee' => 30,
            'nighttime_adult_entrance_fee' => 100,
            'nighttime_child_entrance_fee' => 50,
            'day_pool_fee' => 50,
            'night_pool_fee' => 75,
        ]);
    }

    public function test_early_checked_in_online_reservation_marks_amenity_occupied_before_scheduled_date(): void
    {
        Carbon::setTestNow('2026-08-21 10:00:00');

        $amenity = Amenity::create([
            'id' => (string) Str::uuid(),
            'amenities_name' => 'Payag Early Check-In',
            'daytime_price' => 500,
            'nighttime_price' => 700,
            'minimum_capacity' => 1,
            'maximum_capacity' => 10,
            'status' => true,
        ]);

        $scheduledStart = '2026-08-25';
        $scheduledEnd = '2026-08-25';

        $reservation = Reservation::create([
            'booker_name' => 'Early Guest',
            'email' => 'early@test.com',
            'phone' => '09170000099',
            'reservation_date' => $scheduledStart,
            'end_date' => $scheduledEnd,
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'check_in' => '2026-08-21 10:00:00',
            'status' => 'Checked In',
            'reservation_type' => 'online',
            'number_of_guests' => 1,
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $amenity->id,
            'pricing_type' => 'Daytime',
            'start_date' => $scheduledStart,
            'end_date' => $scheduledEnd,
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        $response = $this->withSession(['auth_user' => ['role' => 'staff', 'id' => 1, 'name' => 'Staff']])
            ->get(route('staff.occupancy-monitor'));

        $response->assertStatus(200);
        $response->assertSee('Payag Early Check-In');
        $response->assertSee('data-unavailable-slots="daytime,nighttime"', false);

        $availability = $this->getJson('/api/amenities/availability?start_date=2026-08-21&end_date=2026-08-21&start_slot=Daytime&end_slot=Daytime');
        $availability->assertOk();
        $this->assertContains((string) $amenity->id, array_map('strval', $availability->json('occupied_ids')));

        Carbon::setTestNow();
    }

    public function test_staff_check_ins_page_excludes_early_checked_in_amenity_from_walk_in_availability(): void
    {
        Carbon::setTestNow('2026-08-21 10:00:00');

        $staff = StaffAccount::create([
            'name' => 'Staff Early',
            'email' => 'staff-early@test.com',
            'password' => bcrypt('password'),
            'ban_status' => false,
        ]);

        $amenity = Amenity::create([
            'id' => (string) Str::uuid(),
            'amenities_name' => 'Reserved Payag',
            'daytime_price' => 500,
            'nighttime_price' => 700,
            'minimum_capacity' => 1,
            'maximum_capacity' => 10,
            'status' => true,
        ]);

        $scheduledStart = '2026-08-25';

        $reservation = Reservation::create([
            'booker_name' => 'Future Guest',
            'email' => 'future@test.com',
            'phone' => '09170000088',
            'reservation_date' => $scheduledStart,
            'end_date' => $scheduledStart,
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'check_in' => '2026-08-21 10:00:00',
            'status' => 'Checked In',
            'reservation_type' => 'online',
            'number_of_guests' => 1,
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $amenity->id,
            'pricing_type' => 'Daytime',
            'start_date' => $scheduledStart,
            'end_date' => $scheduledStart,
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        $response = $this->withSession(['auth_user' => ['id' => $staff->id, 'name' => $staff->name, 'role' => 'staff']])
            ->get('/staff/check-ins');

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringContainsString(
            'window.OCCUPIED_TODAY_AMENITY_IDS =',
            $content,
            'Check-ins page should expose occupied amenity ids to the walk-in picker.'
        );
        $this->assertStringContainsString(
            '"' . $amenity->id . '"',
            $content,
            'Early checked-in amenity should be marked occupied today even before scheduled date.'
        );

        Carbon::setTestNow();
    }
}
