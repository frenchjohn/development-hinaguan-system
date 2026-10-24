<?php

namespace Tests\Feature;

use App\Models\ChatbotMessage;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ReservationGuest;
use App\Models\StaffAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StaffChatbotFallbackTest extends TestCase
{
    use RefreshDatabase;

    private function makeStaffSession(): StaffAccount
    {
        $staff = StaffAccount::create([
            'name' => 'Staff Bren User',
            'email' => 'staffbren@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->withSession([
            'auth_user' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => 'staff',
            ]
        ]);

        return $staff;
    }

    public function test_staff_ai_gracefully_falls_back_on_openrouter_401_error()
    {
        $this->makeStaffSession();

        // Simulate OpenRouter 401 "User not found"
        Http::fake([
            'https://openrouter.ai/*' => Http::response([
                'error' => [
                    'message' => 'User not found.',
                    'code' => 401,
                ]
            ], 401),
        ]);

        // 1. Greeting
        $response = $this->postJson('/chatbot', [
            'message' => 'hii',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('Bren', $response->json('reply'));
        $this->assertStringNotContainsString('encountered an error', $response->json('reply'));

        // 2. Due checkouts today
        $responseCheckouts = $this->postJson('/chatbot', [
            'message' => 'Due Checkouts Today',
        ]);
        $responseCheckouts->assertStatus(200);
        $this->assertStringContainsString('checkout', strtolower($responseCheckouts->json('reply')));
        $this->assertStringNotContainsString('encountered an error', $responseCheckouts->json('reply'));

        // 3. Demographics
        $responseDemo = $this->postJson('/chatbot', [
            'message' => 'Kids & Seniors Demographics',
        ]);
        $responseDemo->assertStatus(200);
        $this->assertStringContainsString('Demographics', $responseDemo->json('reply'));
        $this->assertStringNotContainsString('encountered an error', $responseDemo->json('reply'));
    }

    public function test_staff_ai_responds_to_specific_reservation_inquiry_on_fallback()
    {
        $this->makeStaffSession();

        $res = Reservation::create([
            'booker_name' => 'Park Hina',
            'email' => 'parkhina@example.com',
            'phone' => '09930457138',
            'reservation_date' => '2026-10-24',
            'end_date' => '2026-10-24',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 4,
            'reservation_type' => 'online',
            'status' => 'Checked In',
            'total_amount' => 1500,
            'amount_paid' => 1000,
            'remaining_balance' => 500,
            'payment_status' => 'Partially Paid',
        ]);

        Http::fake([
            'https://openrouter.ai/*' => Http::response(['error' => 'Simulated failure'], 500),
        ]);

        $response = $this->postJson('/chatbot', [
            'message' => "check reservation #{$res->id}",
        ]);

        $response->assertStatus(200);
        $reply = $response->json('reply');
        $this->assertStringContainsString((string) $res->id, $reply);
        $this->assertStringContainsString('Park Hina', $reply);
        $this->assertStringContainsString('Checked In', $reply);
    }

    public function test_staff_ai_includes_overdue_checkouts_when_staff_forgot_to_check_out()
    {
        $this->makeStaffSession();

        // Create an overdue reservation: scheduled checkout on Oct 20, 2026, but still Checked In
        $overdueRes = Reservation::create([
            'booker_name' => 'Overdue Guest John',
            'email' => 'overduejohn@example.com',
            'phone' => '09123456789',
            'reservation_date' => '2026-10-18',
            'end_date' => '2026-10-20',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 2,
            'number_of_guests' => 3,
            'reservation_type' => 'walk-in',
            'status' => 'Checked In',
            'check_in' => '2026-10-18 10:00:00',
            'check_out' => null,
            'total_amount' => 1200,
            'amount_paid' => 1200,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        Http::fake([
            'https://openrouter.ai/*' => Http::response(['error' => 'Simulated failure'], 500),
        ]);

        $response = $this->postJson('/chatbot', [
            'message' => 'Who is due for checkout today?',
        ]);

        $response->assertStatus(200);
        $reply = $response->json('reply');
        $this->assertStringContainsString('Overdue Guest John', $reply);
        $this->assertStringContainsString((string) $overdueRes->id, $reply);
        $this->assertStringContainsString('OVERDUE', $reply);
        $this->assertStringContainsString('Oct 20, 2026', $reply);
    }
}
