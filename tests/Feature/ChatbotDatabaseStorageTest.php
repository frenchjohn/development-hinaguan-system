<?php

namespace Tests\Feature;

use App\Models\ChatbotMessage;
use App\Models\StaffAccount;
use App\Models\AdminAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotDatabaseStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake OpenRouter API
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $body = json_decode($request->body(), true);
            $userMsg = end($body['messages'])['content'] ?? '';

            if (str_contains($userMsg, 'near checkout')) {
                return Http::response([
                    'choices' => [
                        [
                            'message' => [
                                'content' => "<think>1. User is asking for due checkout today.\n2. Look up context.</think>\nHere's a thinking process: Analyzing user query...\n\nReservation #6 is due for checkout today at 12:00 PM."
                            ]
                        ]
                    ]
                ], 200);
            }

            return Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Hello! This is a test AI response.'
                        ]
                    ]
                ]
            ], 200);
        });

        putenv('OPENROUTER_API_KEY=test-fake-key');
        $_ENV['OPENROUTER_API_KEY'] = 'test-fake-key';
    }

    public function test_staff_chat_persists_messages_to_database()
    {
        $staff = StaffAccount::create([
            'name' => 'Staff Alice',
            'email' => 'alice@hinaguan.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'role' => 'staff',
            ]
        ])->postJson('/chatbot', [
            'message' => 'Who is due for checkout today?',
            'model' => 'meta-llama/llama-3-8b-instruct:free',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['reply']);

        // Assert user message and bot response were stored in DB
        $this->assertDatabaseHas('chatbot_messages', [
            'user_type' => 'staff',
            'user_id' => $staff->id,
            'role' => 'user',
            'content' => 'Who is due for checkout today?',
        ]);

        $this->assertDatabaseHas('chatbot_messages', [
            'user_type' => 'staff',
            'user_id' => $staff->id,
            'role' => 'assistant',
            'content' => 'Hello! This is a test AI response.',
        ]);
    }

    public function test_staff_history_is_isolated_between_different_staff_accounts()
    {
        $staff1 = StaffAccount::create([
            'name' => 'Staff One',
            'email' => 'one@hinaguan.com',
            'password' => bcrypt('password123'),
        ]);

        $staff2 = StaffAccount::create([
            'name' => 'Staff Two',
            'email' => 'two@hinaguan.com',
            'password' => bcrypt('password123'),
        ]);

        // Create message for staff 1
        ChatbotMessage::create([
            'user_type' => 'staff',
            'user_id' => $staff1->id,
            'role' => 'user',
            'content' => 'Staff One Secret Query',
        ]);

        // Create message for staff 2
        ChatbotMessage::create([
            'user_type' => 'staff',
            'user_id' => $staff2->id,
            'role' => 'user',
            'content' => 'Staff Two Secret Query',
        ]);

        // Request history as Staff 1
        $res1 = $this->withSession([
            'auth_user' => [
                'id' => $staff1->id,
                'name' => $staff1->name,
                'role' => 'staff',
            ]
        ])->getJson('/chatbot/history');

        $res1->assertStatus(200);
        $res1->assertJsonFragment(['content' => 'Staff One Secret Query']);
        $res1->assertJsonMissing(['content' => 'Staff Two Secret Query']);

        // Request history as Staff 2
        $res2 = $this->withSession([
            'auth_user' => [
                'id' => $staff2->id,
                'name' => $staff2->name,
                'role' => 'staff',
            ]
        ])->getJson('/chatbot/history');

        $res2->assertStatus(200);
        $res2->assertJsonFragment(['content' => 'Staff Two Secret Query']);
        $res2->assertJsonMissing(['content' => 'Staff One Secret Query']);
    }

    public function test_staff_clear_deletes_only_that_staff_conversation()
    {
        $staff1 = StaffAccount::create([
            'name' => 'Staff One',
            'email' => 'one@hinaguan.com',
            'password' => bcrypt('password123'),
        ]);

        $staff2 = StaffAccount::create([
            'name' => 'Staff Two',
            'email' => 'two@hinaguan.com',
            'password' => bcrypt('password123'),
        ]);

        ChatbotMessage::create([
            'user_type' => 'staff',
            'user_id' => $staff1->id,
            'role' => 'user',
            'content' => 'Message to be deleted',
        ]);

        ChatbotMessage::create([
            'user_type' => 'staff',
            'user_id' => $staff2->id,
            'role' => 'user',
            'content' => 'Message to remain safe',
        ]);

        // Staff 1 clears their conversation
        $response = $this->withSession([
            'auth_user' => [
                'id' => $staff1->id,
                'name' => $staff1->name,
                'role' => 'staff',
            ]
        ])->postJson('/chatbot/clear');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Assert Staff 1 messages are deleted, Staff 2 messages remain
        $this->assertDatabaseMissing('chatbot_messages', [
            'user_type' => 'staff',
            'user_id' => $staff1->id,
        ]);

        $this->assertDatabaseHas('chatbot_messages', [
            'user_type' => 'staff',
            'user_id' => $staff2->id,
            'content' => 'Message to remain safe',
        ]);
    }

    public function test_admin_chat_persists_and_clears_history()
    {
        $admin = AdminAccount::create([
            'name' => 'Admin Boss',
            'email' => 'boss@hinaguan.com',
            'password' => bcrypt('password123'),
        ]);

        // Send admin message
        $response = $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'role' => 'admin',
            ]
        ])->postJson('/admin-chatbot', [
            'message' => 'Show me sales and revenue breakdown',
            'model' => 'meta-llama/llama-3-8b-instruct:free',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('chatbot_messages', [
            'user_type' => 'admin',
            'user_id' => $admin->id,
            'role' => 'user',
            'content' => 'Show me sales and revenue breakdown',
        ]);

        // Fetch history
        $historyRes = $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'role' => 'admin',
            ]
        ])->getJson('/admin-chatbot/history');

        $historyRes->assertStatus(200);
        $historyRes->assertJsonFragment(['content' => 'Show me sales and revenue breakdown']);

        // Clear history
        $clearRes = $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'role' => 'admin',
            ]
        ])->postJson('/admin-chatbot/clear');

        $clearRes->assertStatus(200);
        $clearRes->assertJson(['success' => true]);

        $this->assertDatabaseMissing('chatbot_messages', [
            'user_type' => 'admin',
            'user_id' => $admin->id,
        ]);
    }

    public function test_guest_chat_does_not_write_to_database()
    {
        $initialCount = ChatbotMessage::count();

        $response = $this->postJson('/guest-chatbot', [
            'message' => 'What are your entrance rates and operating hours?',
            'model' => 'openrouter/free',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['reply']);

        // Database count should not have increased
        $this->assertEquals($initialCount, ChatbotMessage::count());
    }

    public function test_chatbot_strips_raw_thinking_process_and_tags()
    {
        $staff = StaffAccount::create([
            'name' => 'Staff Tester',
            'email' => 'tester@hinaguan.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'role' => 'staff',
            ]
        ])->postJson('/chatbot', [
            'message' => 'what reservation is near checkout?',
            'model' => 'openrouter/free',
        ]);

        $response->assertStatus(200);
        $reply = $response->json('reply');

        $this->assertStringNotContainsString('<think>', $reply);
        $this->assertStringNotContainsString('thinking process', strtolower($reply));
        $this->assertStringContainsString('Reservation #6 is due for checkout today at 12:00 PM.', $reply);
    }
}
