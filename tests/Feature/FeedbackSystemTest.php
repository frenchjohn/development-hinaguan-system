<?php

namespace Tests\Feature;

use App\Models\AdminAccount;
use App\Models\Feedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
