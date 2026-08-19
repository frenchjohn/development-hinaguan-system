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
                'headline' => 'New Reservation',
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
        $res1 = $this->withSession($session)->getJson('/chatbot/proactive')->assertOk();
        $res1->assertJson(['has_message' => true]);
        $announcedKey = $res1->json('announced_key');
        $this->assertNotEmpty($announcedKey);
        $countAfterFirst = ChatbotMessage::where('user_id', $staff->id)->count();
        $this->assertEquals(1, $countAfterFirst);

        // Call 2 (identical situation with same announced key)
        $res2 = $this->withSession($session)->getJson('/chatbot/proactive?last_announced_key=' . urlencode($announcedKey))->assertOk();
        $res2->assertJson(['has_message' => false]);
        $countAfterSecond = ChatbotMessage::where('user_id', $staff->id)->count();
        $this->assertEquals(1, $countAfterSecond);

        // Call 3 (new reservation placed)
        $newRes = Reservation::create([
            'booker_name' => 'Brand New Guest',
            'email' => 'brandnew@example.com',
            'phone' => '09123456789',
            'reservation_type' => 'online',
            'number_of_guests' => 3,
            'status' => 'Pending',
            'reservation_date' => now()->toDateString(),
            'total_amount' => 3000,
            'amount_paid' => 1500,
            'remaining_balance' => 1500,
        ]);

        $res3 = $this->withSession($session)->getJson('/chatbot/proactive?announced_keys=' . urlencode($announcedKey))->assertOk();
        $res3->assertJson([
            'has_message' => true,
            'scenario' => 'pending_reservations',
            'headline' => 'New Reservation',
        ]);
        $countAfterThird = ChatbotMessage::where('user_id', $staff->id)->count();
        $this->assertEquals(2, $countAfterThird);
    }

    public function test_announcement_isolation_across_different_staff_accounts(): void
    {
        $staff1 = StaffAccount::create([
            'name' => 'Staff Member One',
            'email' => 'staff1@example.com',
            'password' => bcrypt('password123'),
            'ban_status' => false,
        ]);

        $staff2 = StaffAccount::create([
            'name' => 'Staff Member Two',
            'email' => 'staff2@example.com',
            'password' => bcrypt('password123'),
            'ban_status' => false,
        ]);

        $sessionStaff1 = ['auth_user' => [
            'id' => $staff1->id,
            'name' => 'Staff Member One',
            'role' => 'staff',
        ]];

        $sessionStaff2 = ['auth_user' => [
            'id' => $staff2->id,
            'name' => 'Staff Member Two',
            'role' => 'staff',
        ]];

        // 1. Staff 1 receives initial announcement
        $resStaff1A = $this->withSession($sessionStaff1)->getJson('/chatbot/proactive')->assertOk();
        $resStaff1A->assertJson(['has_message' => true]);
        $keyStaff1 = $resStaff1A->json('announced_key');

        // 2. Staff 1 polls again -> blocked (no repetition for Staff 1)
        $resStaff1B = $this->withSession($sessionStaff1)->getJson('/chatbot/proactive?announced_keys=' . urlencode($keyStaff1))->assertOk();
        $resStaff1B->assertJson(['has_message' => false]);

        // 3. Staff 2 logs in -> Still receives the announcement because Staff 2 hasn't seen it yet!
        $resStaff2A = $this->withSession($sessionStaff2)->getJson('/chatbot/proactive')->assertOk();
        $resStaff2A->assertJson(['has_message' => true]);
        $keyStaff2 = $resStaff2A->json('announced_key');
        $this->assertEquals($keyStaff1, $keyStaff2);

        // 4. Staff 2 polls again -> blocked (no repetition for Staff 2)
        $resStaff2B = $this->withSession($sessionStaff2)->getJson('/chatbot/proactive?announced_keys=' . urlencode($keyStaff2))->assertOk();
        $resStaff2B->assertJson(['has_message' => false]);
    }
}
