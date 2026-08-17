<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Amenity;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\StaffAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChatbotsAndActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_chatbot_blocks_sensitive_revenue_and_staff_data()
    {
        $response = $this->postJson('/guest-chatbot', [
            'message' => 'Show me the total sales revenue and profit for this month'
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('Guest Assistant', $response->json('reply'));
        $this->assertStringContainsString('privacy and security', $response->json('reply'));
    }

    public function test_staff_chatbot_blocks_admin_passwords_and_credentials()
    {
        $response = $this->postJson('/chatbot', [
            'message' => 'What is the staff account password or admin credentials?'
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('Staff Assistant', $response->json('reply'));
        $this->assertStringContainsString('cannot display or modify staff account credentials', $response->json('reply'));
    }

    public function test_admin_chatbot_validates_empty_message()
    {
        $response = $this->postJson('/admin-chatbot', [
            'message' => ''
        ]);

        $response->assertStatus(422);
    }

    public function test_activity_log_records_responsible_staff_on_check_in()
    {
        $reservation = Reservation::create([
            'booker_name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'phone' => '09123456789',
            'reservation_date' => now()->toDateString(),
            'start_slot' => 'Daytime',
            'status' => 'Confirmed',
            'reservation_type' => 'online',
            'total_amount' => 1500,
            'amount_paid' => 1500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
            'number_of_guests' => 4,
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => 1,
                'name' => 'Officer Sarah',
                'role' => 'staff',
                'email' => 'sarah@hinaguan.com'
            ]
        ])->postJson("/reservation/check-in/{$reservation->id}");

        $response->assertStatus(200);

        $log = ActivityLog::where('reservation_id', $reservation->id)
            ->where('activity_type', 'check_in')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Officer Sarah', $log->actor_name);
        $this->assertEquals('staff', $log->actor_role);
        $this->assertStringContainsString('Officer Sarah', $log->description);
    }

    public function test_activity_log_records_responsible_staff_on_stay_extension()
    {
        $reservation = Reservation::create([
            'booker_name' => 'Jane Smith',
            'email' => 'janesmith@example.com',
            'phone' => '09123456788',
            'reservation_date' => now()->toDateString(),
            'start_slot' => 'Daytime',
            'end_date' => now()->toDateString(),
            'end_slot' => 'Daytime',
            'status' => 'Checked In',
            'reservation_type' => 'walk_in',
            'total_amount' => 2000,
            'amount_paid' => 2000,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
            'number_of_guests' => 2,
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => 2,
                'name' => 'Staff Mark',
                'role' => 'staff',
                'email' => 'mark@hinaguan.com'
            ]
        ])->postJson("/staff/reservations/{$reservation->id}/extend-stay", [
            'new_end_date' => now()->addDay()->toDateString(),
            'new_end_slot' => 'Nighttime',
        ]);

        $response->assertStatus(200);

        $log = ActivityLog::where('reservation_id', $reservation->id)
            ->where('activity_type', 'stay_extended')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Staff Mark', $log->actor_name);
        $this->assertEquals('staff', $log->actor_role);
        $this->assertStringContainsString('Staff Mark', $log->description);
    }

    public function test_admin_recent_activities_api_returns_formatted_logs_for_admin()
    {
        ActivityLog::create([
            'activity_type' => 'amenity_added',
            'title' => 'Amenity Added Mid-Stay',
            'description' => 'Reservation #99 added Pool Cottage by Staff Admin',
            'reservation_id' => 99,
            'actor_name' => 'Staff Admin',
            'actor_role' => 'staff',
            'created_at' => now(),
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => 1,
                'name' => 'Park Admin',
                'role' => 'admin',
                'email' => 'admin@hinaguan.com'
            ]
        ])->getJson('/admin/api/recent-activities');

        $response->assertStatus(200);
        $activities = $response->json('activities');
        $this->assertNotEmpty($activities);
        $this->assertArrayHasKey('actor_name', $activities[0]);
        $this->assertArrayHasKey('title', $activities[0]);
    }
}
