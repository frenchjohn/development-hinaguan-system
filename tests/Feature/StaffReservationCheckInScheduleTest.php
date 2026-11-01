<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffReservationCheckInScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ParkSetting::create([
            'daytime_start' => '08:00:00',
            'daytime_end' => '17:00:00',
            'nighttime_start' => '18:00:00',
            'nighttime_end' => '08:00:00',
            'daytime_adult_entrance_fee' => 20,
            'nighttime_adult_entrance_fee' => 50,
        ]);
    }

    private function makeStaffSession(): void
    {
        $this->withSession(['auth_user' => ['id' => 1, 'name' => 'Staff User', 'role' => 'staff']]);
    }

    public function test_cannot_check_in_future_unscheduled_online_reservation(): void
    {
        $this->makeStaffSession();

        // Current time is Nov 1, 2026 16:00
        Carbon::setTestNow(Carbon::parse('2026-11-01 16:00:00', 'Asia/Manila'));

        // Reservation is scheduled for Nov 3, 2026 (Daytime 08:00 AM)
        $reservation = Reservation::create([
            'booker_name' => 'Future Booker',
            'email' => 'future@example.com',
            'phone' => '09123456789',
            'reservation_date' => '2026-11-03',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'status' => 'Pending',
            'reservation_type' => 'online',
            'number_of_guests' => 1,
            'total_amount' => 500,
            'amount_paid' => 250,
            'remaining_balance' => 250,
            'payment_status' => 'Partially Paid',
        ]);

        $response = $this->postJson("/staff/reservations/{$reservation->id}/check-in", [
            'guest_mode' => 'with_primary',
            'primary_guest' => [
                'first_name' => 'Future',
                'last_name' => 'Guest',
                'age' => 25,
                'gender' => 'Male',
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'This reservation is not scheduled yet. Check-in opens 1 hour before the scheduled time (Nov 3, 2026 · 7:00 AM).',
        ]);

        Carbon::setTestNow();
    }

    public function test_can_check_in_when_within_one_hour_before_schedule(): void
    {
        $this->makeStaffSession();

        // Current time is Nov 3, 2026 07:15 AM (45 mins before 8:00 AM)
        Carbon::setTestNow(Carbon::parse('2026-11-03 07:15:00', 'Asia/Manila'));

        $reservation = Reservation::create([
            'booker_name' => 'On Time Booker',
            'email' => 'ontime@example.com',
            'phone' => '09123456789',
            'reservation_date' => '2026-11-03',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'status' => 'Pending',
            'reservation_type' => 'online',
            'number_of_guests' => 1,
            'total_amount' => 500,
            'amount_paid' => 250,
            'remaining_balance' => 250,
            'payment_status' => 'Partially Paid',
        ]);

        $amenity = Amenity::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'amenities_name' => 'Payag Scheduled',
            'daytime_price' => 500,
            'nighttime_price' => 600,
            'minimum_capacity' => 1,
            'maximum_capacity' => 10,
            'status' => true,
        ]);

        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $amenity->id,
            'start_date' => '2026-11-03',
            'end_date' => '2026-11-03',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'pricing_type' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
        ]);

        $response = $this->postJson("/staff/reservations/{$reservation->id}/check-in", [
            'guest_mode' => 'with_primary',
            'primary_guest' => [
                'first_name' => 'OnTime',
                'last_name' => 'Guest',
                'age' => 25,
                'gender' => 'Male',
            ],
            'entrance_option' => 'all_free',
            'pool_option' => 'no_pool',
        ]);

        $response->assertOk();
        $this->assertEquals('Checked In', $reservation->fresh()->status);

        Carbon::setTestNow();
    }
}

