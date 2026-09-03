<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Services\FeedbackAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFeedbackAiTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_sentiment_service_detects_positive_feedback(): void
    {
        $feedback = Feedback::create([
            'full_name' => 'Happy Tourist',
            'is_anonymous' => false,
            'description' => 'Grabe ka nindot ang river and relaxing kaayo! Very helpful and friendly staff.',
            'stars' => 5,
            'is_shown' => true,
        ]);

        $service = app(FeedbackAiService::class);
        $analysis = $service->analyzeSentiment($feedback);

        $this->assertEquals('positive', $analysis['sentiment']);
        $this->assertEquals('Positive', $analysis['label']);
        $this->assertEquals('🟢', $analysis['emoji']);
        $this->assertNotEmpty($analysis['summary']);
        $this->assertNotEmpty($analysis['explanation']);
        $this->assertNotEmpty($analysis['points']);
        $this->assertEquals('positive', $analysis['points'][0]['type']);
        $this->assertNotEmpty($analysis['points'][0]['how']);
    }

    public function test_ai_sentiment_service_detects_negative_feedback(): void
    {
        $feedback = Feedback::create([
            'full_name' => 'Unhappy Guest',
            'is_anonymous' => false,
            'description' => 'Hugaw kaayo ang banyo tapos dugay kaayo ang service. Disappointing experience.',
            'stars' => 1,
            'is_shown' => true,
        ]);

        $service = app(FeedbackAiService::class);
        $analysis = $service->analyzeSentiment($feedback);

        $this->assertEquals('negative', $analysis['sentiment']);
        $this->assertEquals('Negative', $analysis['label']);
        $this->assertEquals('🔴', $analysis['emoji']);
    }

    public function test_ai_sentiment_service_detects_profanity_and_flags_it(): void
    {
        $feedback = Feedback::create([
            'full_name' => 'ulol',
            'is_anonymous' => false,
            'description' => 'adsaddsadssdadsdadssadsd',
            'stars' => 4,
            'is_shown' => true,
        ]);

        $service = app(FeedbackAiService::class);
        $analysis = $service->analyzeSentiment($feedback);

        $this->assertEquals('negative', $analysis['sentiment']);
        $this->assertStringContainsString('Inappropriate', $analysis['label']);
        $this->assertEquals('🔴', $analysis['emoji']);
    }

    public function test_ai_sentiment_service_detects_gibberish(): void
    {
        $feedback = Feedback::create([
            'full_name' => 'Guest Tester',
            'is_anonymous' => false,
            'description' => 'asdfghjklasdfghjkl',
            'stars' => 5,
            'is_shown' => true,
        ]);

        $service = app(FeedbackAiService::class);
        $analysis = $service->analyzeSentiment($feedback);

        $this->assertEquals('neutral', $analysis['sentiment']);
        $this->assertStringContainsString('Gibberish', $analysis['label']);
        $this->assertEquals('🟡', $analysis['emoji']);
    }

    public function test_ai_sentiment_ignores_star_rating_and_focuses_purely_on_text(): void
    {
        // Positive text with 2 stars
        $feedback = Feedback::create([
            'full_name' => 'John maka',
            'is_anonymous' => false,
            'description' => 'Good kayo diri, maka relax kog tarong',
            'stars' => 2,
            'is_shown' => true,
        ]);

        $service = app(FeedbackAiService::class);
        $analysis = $service->analyzeSentiment($feedback);

        $this->assertEquals('positive', $analysis['sentiment']);
        $this->assertEquals('Positive', $analysis['label']);
        $this->assertEquals('🟢', $analysis['emoji']);
        $this->assertEquals('Satisfied & complimentary', $analysis['tone']);
    }

    public function test_ai_executive_insights_calculates_distribution(): void
    {
        Feedback::create([
            'full_name' => 'Praise 1',
            'is_anonymous' => false,
            'description' => 'Amazing river views and clean cottages!',
            'stars' => 5,
            'is_shown' => true,
        ]);

        Feedback::create([
            'full_name' => 'Praise 2',
            'is_anonymous' => false,
            'description' => 'Lingaw kaayo ang pamilya. Recommended!',
            'stars' => 5,
            'is_shown' => true,
        ]);

        Feedback::create([
            'full_name' => 'Complaint 1',
            'is_anonymous' => false,
            'description' => 'Baho ang kasilyas samok.',
            'stars' => 1,
            'is_shown' => true,
        ]);

        $service = app(FeedbackAiService::class);
        $insights = $service->generateExecutiveInsights(forceFresh: true);

        $this->assertEquals(3, $insights['total_reviews']);
        $this->assertGreaterThan(50, $insights['positive_percent']);
        $this->assertGreaterThan(0, $insights['negative_percent']);
        $this->assertNotEmpty($insights['recommendation']);
    }

    public function test_admin_can_refresh_ai_insights_endpoint(): void
    {
        Feedback::create([
            'full_name' => 'Sample Guest',
            'is_anonymous' => false,
            'description' => 'Very relaxing environment.',
            'stars' => 5,
            'is_shown' => true,
        ]);

        $adminUser = [
            'id' => 1,
            'name' => 'Admin User',
            'username' => 'admin',
            'role' => 'admin',
        ];

        $response = $this->withSession(['auth_user' => $adminUser])
            ->postJson('/admin/feedback/ai-insights/refresh');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'insights' => [
                'total_reviews',
                'positive_count',
                'positive_percent',
                'neutral_count',
                'neutral_percent',
                'negative_count',
                'negative_percent',
                'average_rating',
                'top_praises',
                'top_issues',
                'recommendation',
            ],
        ]);
    }
}
