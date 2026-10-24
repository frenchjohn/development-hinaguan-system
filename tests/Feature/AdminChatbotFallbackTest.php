<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AdminAccount;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ReservationGuest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminChatbotFallbackTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdminSession(): AdminAccount
    {
        $admin = AdminAccount::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
            ]
        ]);

        return $admin;
    }

    public function test_admin_ai_gracefully_falls_back_on_openrouter_401_error()
    {
        $this->makeAdminSession();

        // Simulate OpenRouter 401 "User not found"
        Http::fake([
            'https://openrouter.ai/*' => Http::response([
                'error' => [
                    'message' => 'User not found.',
                    'code' => 401,
                ]
            ], 401),
        ]);

        // 1. General query / "what happen"
        $response = $this->postJson('/admin-chatbot', [
            'message' => 'what happen',
        ]);

        $response->assertStatus(200);
        $this->assertStringNotContainsString('encountered an error', $response->json('reply'));

        // 2. Recent Activities
        ActivityLog::create([
            'action' => 'checked_in',
            'activity_type' => 'check_in',
            'title' => 'Guest Check-In Completed',
            'description' => 'Staff Bren checked in Reservation #105',
            'actor_name' => 'Staff Bren',
            'actor_role' => 'staff',
        ]);

        $responseActivities = $this->postJson('/admin-chatbot', [
            'message' => 'Recent Activities',
        ]);
        $responseActivities->assertStatus(200);
        $this->assertStringContainsString('Recent Park Activities', $responseActivities->json('reply'));
        $this->assertStringContainsString('Guest Check-In Completed', $responseActivities->json('reply'));

        // 3. Sales & Revenue
        $responseRevenue = $this->postJson('/admin-chatbot', [
            'message' => 'Sales & Revenue',
        ]);
        $responseRevenue->assertStatus(200);
        $this->assertStringContainsString('Financial & Revenue Overview', $responseRevenue->json('reply'));

        // 4. Demographics
        $responseDemo = $this->postJson('/admin-chatbot', [
            'message' => 'Kids & Seniors Demographics',
        ]);
        $responseDemo->assertStatus(200);
        $this->assertStringContainsString('Demographics', $responseDemo->json('reply'));
    }
}
