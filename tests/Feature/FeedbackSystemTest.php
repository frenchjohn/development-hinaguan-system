<?php

namespace Tests\Feature;

use App\Models\AdminAccount;
use App\Models\Feedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FeedbackSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_anonymous_feedback(): void
    {
        $response = $this->postJson('/feedback', [
            'full_name' => '',
            'is_anonymous' => true,
            'description' => 'Beautiful park and very peaceful.',
            'stars' => 5,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('feedbacks', [
            'full_name' => Feedback::ANONYMOUS_NAME,
            'is_anonymous' => true,
            'stars' => 5,
            'is_shown' => true,
        ]);
    }

    public function test_guest_can_submit_named_feedback(): void
    {
        $response = $this->postJson('/feedback', [
            'full_name' => 'Maria Santos',
            'is_anonymous' => false,
            'description' => 'Great amenities and friendly staff.',
            'stars' => 4,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('feedbacks', [
            'full_name' => 'Maria Santos',
            'is_anonymous' => false,
            'stars' => 4,
        ]);
    }

    public function test_guest_feedback_with_inappropriate_language_is_rejected(): void
    {
        $response = $this->postJson('/feedback', [
            'full_name' => 'Maria Santos',
            'is_anonymous' => false,
            'description' => 'The staff was gago and rude.',
            'stars' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('moderation.blocked', true)
            ->assertJsonPath('moderation.terms.0', 'gago');

        $this->assertDatabaseCount('feedbacks', 0);
    }

    public function test_guest_feedback_detects_bisaya_variant_and_returns_the_complete_sentence(): void
    {
        $response = $this->postJson('/feedback', [
            'full_name' => 'Maria Santos',
            'is_anonymous' => false,
            'description' => 'The river view was beautiful. Pinisti kaayo ang staff today!',
            'stars' => 3,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('moderation.terms.0', 'pinisti')
            ->assertJsonPath('moderation.matches.0.sentence', 'Pinisti kaayo ang staff today!');

        $this->assertDatabaseCount('feedbacks', 0);
    }

    public function test_guest_feedback_blocks_bad_word_shortcuts_in_bisaya_tagalog_and_english(): void
    {
        $shortcuts = [
            ['text' => 'Bati kaayo diri psti gyud mo.', 'expected_term' => 'psti'],
            ['text' => 'Ang pste ninyo tanan.', 'expected_term' => 'pste'],
            ['text' => 'What the fck is this service.', 'expected_term' => 'fck'],
            ['text' => 'Fack this place.', 'expected_term' => 'fack'],
            ['text' => 'Pakyu sa tanan staff.', 'expected_term' => 'pakyu'],
            ['text' => 'Ywa kaayo ang experience.', 'expected_term' => 'ywa'],
            ['text' => 'Ka aty gyud sa mga cottage.', 'expected_term' => 'aty'],
            ['text' => 'Tngina niyo ang pangit.', 'expected_term' => 'tngina'],
        ];

        foreach ($shortcuts as $case) {
            $response = $this->postJson('/feedback', [
                'full_name' => 'Guest Tester',
                'is_anonymous' => false,
                'description' => $case['text'],
                'stars' => 1,
            ]);

            $response->assertStatus(422)
                ->assertJsonPath('moderation.blocked', true);

            $terms = $response->json('moderation.terms');
            $this->assertContains($case['expected_term'], $terms, "Failed asserting that '{$case['expected_term']}' was blocked for input: '{$case['text']}'");
        }

        $this->assertDatabaseCount('feedbacks', 0);
    }

    public function test_guest_feedback_blocks_obfuscated_and_repeated_bad_words(): void
    {
        // 1. Repeated letters (pssssti)
        $response1 = $this->postJson('/feedback', [
            'full_name' => 'Obfuscated Guest',
            'is_anonymous' => false,
            'description' => 'Nice river. But pssssti mo tanan! Terrible.',
            'stars' => 1,
        ]);

        $response1->assertStatus(422)
            ->assertJsonPath('moderation.blocked', true)
            ->assertJsonPath('moderation.terms.0', 'psti')
            ->assertJsonPath('moderation.matches.0.sentence', 'But pssssti mo tanan!');

        // 2. Asterisk masking (f*ck)
        $response2 = $this->postJson('/feedback', [
            'full_name' => 'Masked Guest',
            'is_anonymous' => false,
            'description' => 'The f*ck is wrong with this pool.',
            'stars' => 1,
        ]);

        $response2->assertStatus(422)
            ->assertJsonPath('moderation.blocked', true)
            ->assertJsonPath('moderation.terms.0', 'fck');

        // 3. Leetspeak (p!st!)
        $response3 = $this->postJson('/feedback', [
            'full_name' => 'Leet Guest',
            'is_anonymous' => false,
            'description' => 'Ayaw mo adto kay p!st! kaayo.',
            'stars' => 1,
        ]);

        $response3->assertStatus(422)
            ->assertJsonPath('moderation.blocked', true)
            ->assertJsonPath('moderation.terms.0', 'pisti');

        $this->assertDatabaseCount('feedbacks', 0);
    }

    public function test_guest_feedback_with_inappropriate_image_is_rejected_before_storage(): void
    {
        Storage::fake('public');
        config(['services.openrouter.key' => 'test-key']);

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => '{"blocked":true,"matches":[{"image":"explicit.jpg","reason":"sexual content"}]}',
                    ],
                ]],
            ], 200),
        ]);

        $response = $this->post('/feedback', [
            'full_name' => 'Image Guest',
            'is_anonymous' => '0',
            'description' => 'A lovely visit to the river.',
            'stars' => 5,
            'images' => [UploadedFile::fake()->create('explicit.jpg', 100, 'image/jpeg')],
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422)
            ->assertJsonPath('moderation.type', 'image')
            ->assertJsonPath('moderation.matches.0.reason', 'sexual content');

        $this->assertDatabaseCount('feedbacks', 0);
        Storage::disk('public')->assertMissing('feedback_images/explicit.jpg');
    }

    public function test_feedback_page_shows_only_visible_reviews(): void
    {
        Feedback::create([
            'full_name' => 'Visible Guest',
            'is_anonymous' => false,
            'description' => 'Shown review',
            'stars' => 5,
            'is_shown' => true,
        ]);

        Feedback::create([
            'full_name' => 'Hidden Guest',
            'is_anonymous' => false,
            'description' => 'Hidden review',
            'stars' => 5,
            'is_shown' => false,
        ]);

        $response = $this->get('/feedback');
        $response->assertOk();
        $response->assertSee('Visible Guest');
        $response->assertDontSee('Hidden Guest');
    }

    public function test_homepage_shows_top_rated_featured_reviews(): void
    {
        Feedback::create([
            'full_name' => 'Low Rating',
            'is_anonymous' => false,
            'description' => 'Okay visit',
            'stars' => 3,
            'is_shown' => true,
        ]);

        Feedback::create([
            'full_name' => 'Top Guest',
            'is_anonymous' => false,
            'description' => 'Amazing experience',
            'stars' => 5,
            'is_shown' => true,
        ]);

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('Top Guest');
        $response->assertSee('Amazing experience');
    }

    public function test_admin_can_toggle_feedback_visibility_and_delete(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Admin One',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        $feedback = Feedback::create([
            'full_name' => 'Guest To Hide',
            'is_anonymous' => false,
            'description' => 'Needs moderation',
            'stars' => 2,
            'is_shown' => true,
        ]);

        $this->withSession(['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'admin']])
            ->patchJson("/admin/feedback/{$feedback->id}/visibility", ['is_shown' => false])
            ->assertOk();

        $this->assertFalse($feedback->fresh()->is_shown);

        $this->withSession(['auth_user' => ['id' => $admin->id, 'name' => $admin->name, 'role' => 'admin']])
            ->deleteJson("/admin/feedback/{$feedback->id}")
            ->assertOk();

        $this->assertDatabaseMissing('feedbacks', ['id' => $feedback->id]);
    }

    public function test_guest_can_submit_feedback_with_images(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $file1 = \Illuminate\Http\UploadedFile::fake()->create('nature1.jpg', 100, 'image/jpeg');
        $file2 = \Illuminate\Http\UploadedFile::fake()->create('nature2.png', 100, 'image/png');

        $response = $this->post('/feedback', [
            'full_name' => 'Photographer Guest',
            'is_anonymous' => '0',
            'description' => 'Took great pictures of the river and pool!',
            'stars' => 5,
            'images' => [$file1, $file2],
        ], ['Accept' => 'application/json']);

        $response->assertOk();
        $this->assertDatabaseHas('feedbacks', [
            'full_name' => 'Photographer Guest',
            'stars' => 5,
        ]);

        $feedback = Feedback::where('full_name', 'Photographer Guest')->first();
        $this->assertNotNull($feedback);
        $this->assertCount(2, $feedback->images);
        $this->assertDatabaseHas('feedback_images', [
            'feedback_id' => $feedback->id,
        ]);
    }

    public function test_feedback_image_upload_rejects_more_than_5_images(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $files = [
            \Illuminate\Http\UploadedFile::fake()->create('img1.jpg', 50, 'image/jpeg'),
            \Illuminate\Http\UploadedFile::fake()->create('img2.jpg', 50, 'image/jpeg'),
            \Illuminate\Http\UploadedFile::fake()->create('img3.jpg', 50, 'image/jpeg'),
            \Illuminate\Http\UploadedFile::fake()->create('img4.jpg', 50, 'image/jpeg'),
            \Illuminate\Http\UploadedFile::fake()->create('img5.jpg', 50, 'image/jpeg'),
            \Illuminate\Http\UploadedFile::fake()->create('img6.jpg', 50, 'image/jpeg'),
        ];

        $response = $this->post('/feedback', [
            'full_name' => 'Overlimit Guest',
            'is_anonymous' => '0',
            'description' => 'Tried uploading 6 pictures.',
            'stars' => 4,
            'images' => $files,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422);
    }
}
