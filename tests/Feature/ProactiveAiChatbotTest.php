<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ChatbotMessage;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\StaffAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProactiveAiChatbotTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/chatbot/proactive');
        $response->assertStatus(401);

        $adminResponse = $this->getJson('/admin-chatbot/proactive');
        $adminResponse->assertStatus(401);
    }

    public function test_staff_proactive_greeting_detects_pending_reservations_and_persists_to_db(): void
    {
        $staff = StaffAccount::create([
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'password' => bcrypt('password123'),
            'ban_status' => false,
        ]);

        Reservation::create([
            'booker_name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '09123456789',
            'reservation_type' => 'online',
            'number_of_guests' => 4,
            'status' => 'Pending',
            'reservation_date' => now()->toDateString(),
            'total_amount' => 2000,
            'amount_paid' => 1000,
            'remaining_balance' => 1000,
        ]);

        $response = $this->withSession(['auth_user' => [
            'id' => $staff->id,
            'name' => 'Maria Santos',
            'role' => 'staff',
        ]])->getJson('/chatbot/proactive');

        $response->assertOk()
            ->assertJson([
                'has_message' => true,
                'scenario' => 'pending_reservations',
                'headline' => 'New Reservations',
            ]);

        $json = $response->json();
        $this->assertStringContainsString('Maria', $json['message']);
        $this->assertStringContainsString('reservation', $json['message']);
        $this->assertNotEmpty($json['follow_up']);
        $this->assertNotEmpty($json['quick_action_prompt']);
        $this->assertNotEmpty($json['action_button_text']);

        // Assert persisted in database for Maria
        $this->assertDatabaseHas('chatbot_messages', [
            'user_type' => 'staff',
            'user_id' => $staff->id,
            'role' => 'assistant',
        ]);
    }

    public function test_staff_proactive_greeting_detects_revenue_increase(): void
    {
        $staff = StaffAccount::create([
            'name' => 'Carlos Reyes',
            'email' => 'carlos@example.com',
            'password' => bcrypt('password123'),
            'ban_status' => false,
        ]);

        // Create paid reservation today with future checkout so it doesn't trigger due checkouts
        Reservation::create([
            'booker_name' => 'Happy Guest',
            'email' => 'happy@example.com',
            'phone' => '09123456789',
            'reservation_type' => 'walk_in',
            'number_of_guests' => 5,
            'status' => 'Checked In',
            'reservation_date' => now()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'total_amount' => 5500,
            'amount_paid' => 5500,
            'remaining_balance' => 0,
        ]);

        $response = $this->withSession(['auth_user' => [
            'id' => $staff->id,
            'name' => 'Carlos Reyes',
            'role' => 'staff',
        ]])->getJson('/chatbot/proactive');

        $response->assertOk()
            ->assertJson([
                'has_message' => true,
                'scenario' => 'revenue_growth',
                'headline' => 'Revenue Milestone',
            ]);

        $json = $response->json();
        $this->assertStringContainsString('revenue increased', strtolower($json['message']));
        $this->assertStringContainsString('compare', strtolower($json['follow_up']));
    }

    public function test_staff_proactive_greeting_detects_due_checkouts(): void
    {
        $staff = StaffAccount::create([
            'name' => 'David Lee',
            'email' => 'david@example.com',
            'password' => bcrypt('password123'),
            'ban_status' => false,
        ]);

        Reservation::create([
            'booker_name' => 'Departing Guest',
            'email' => 'depart@example.com',
            'phone' => '09123456789',
            'reservation_type' => 'walk_in',
            'number_of_guests' => 2,
            'status' => 'Checked In',
            'reservation_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'total_amount' => 1500,
            'amount_paid' => 1500,
            'remaining_balance' => 0,
        ]);

        $response = $this->withSession(['auth_user' => [
            'id' => $staff->id,
            'name' => 'David Lee',
            'role' => 'staff',
        ]])->getJson('/chatbot/proactive');

        $response->assertOk()
            ->assertJson([
                'has_message' => true,
                'scenario' => 'due_checkouts',
                'headline' => 'Due Checkouts',
            ]);

        $json = $response->json();
        $this->assertStringContainsString('checkout today', strtolower($json['message']));
        $this->assertStringContainsString('departure', strtolower($json['follow_up']));
    }

    public function test_admin_proactive_greeting_detects_recent_staff_activities(): void
    {
        $admin = StaffAccount::create([
            'name' => 'Admin Boss',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'ban_status' => false,
        ]);

        // Log recent staff activity
        ActivityLog::create([
            'activity_type' => 'stay_extended',
            'title' => 'Stay Extended',
            'user_type' => 'staff',
            'user_id' => 999,
            'user_name' => 'Staff Alex',
            'description' => 'Extended stay for Reservation #10',
            'created_at' => now(),
        ]);

        $response = $this->withSession(['auth_user' => [
            'id' => $admin->id,
            'name' => 'Admin Boss',
            'role' => 'admin',
        ]])->getJson('/admin-chatbot/proactive');

        $response->assertOk()
            ->assertJson([
                'has_message' => true,
                'scenario' => 'recent_activities',
                'headline' => 'Recent Staff Activities',
            ]);

        $json = $response->json();
        $this->assertStringContainsString('audit', strtolower($json['message']));
        $this->assertStringContainsString('staff', strtolower($json['follow_up']));

        // Assert message saved for admin
        $this->assertDatabaseHas('chatbot_messages', [
            'user_type' => 'admin',
            'user_id' => $admin->id,
            'role' => 'assistant',
        ]);
    }

    public function test_proactive_greeting_does_not_duplicate_same_message_in_db(): void
    {
        $staff = StaffAccount::create([
            'name' => 'Elena Cruz',
            'email' => 'elena@example.com',
            'password' => bcrypt('password123'),
            'ban_status' => false,
        ]);

        $session = ['auth_user' => [
            'id' => $staff->id,
            'name' => 'Elena Cruz',
            'role' => 'staff',
        ]];

        // Call 1
        $this->withSession($session)->getJson('/chatbot/proactive')->assertOk();
        $countAfterFirst = ChatbotMessage::where('user_id', $staff->id)->count();
        $this->assertEquals(1, $countAfterFirst);

        // Call 2 (identical situation)
        $this->withSession($session)->getJson('/chatbot/proactive')->assertOk();
        $countAfterSecond = ChatbotMessage::where('user_id', $staff->id)->count();
        $this->assertEquals(1, $countAfterSecond);
    }
}
