<?php

namespace Tests\Feature;

use App\Models\AdminAccount;
use App\Models\Amenity;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminReportsAiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_ai_reports_endpoint(): void
    {
        $response = $this->postJson('/admin/api/reports/ai-analyze', [
            'query' => 'Analyze our total revenue',
        ]);

        $response->assertStatus(403)
            ->assertJson(['error' => 'Unauthorized access']);
    }

    public function test_admin_reports_page_renders_with_dual_tabs(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
            ],
        ])->get('/admin/reports');

        $response->assertOk();
        $response->assertSee('Standard Reports');
        $response->assertSee('AI Report Studio');
        $response->assertSee('Ask What Data You Need, AI Delivers');
        $response->assertSee('Quick Analytical Audits');
        $response->assertSee('Revenue & Financials', false);
        $response->assertSee('Peak Days & Forecast', false);
        $response->assertSee('Amenity Utilization', false);
    }

    public function test_authenticated_admin_can_generate_ai_report(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $amenity = Amenity::create([
            'id' => 'amenity-cabana-deluxe',
            'amenities_name' => 'Riverside Cabana Deluxe',
            'daytime_price' => 1500,
            'nighttime_price' => 2000,
            'minimum_capacity' => 4,
            'maximum_capacity' => 10,
            'status' => true,
        ]);

        $reservation = Reservation::create([
            'booker_name' => 'Juan Dela Cruz',
            'phone' => '09181234567',
            'email' => 'juan@example.com',
            'reservation_date' => now()->toDateString(),
            'total_days' => 1,
            'number_of_guests' => 6,
            'total_amount' => 1500.00,
            'amount_paid' => 1500.00,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
            'status' => 'Confirmed',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $amenity->id,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 1500.00,
            'quantity' => 1,
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
            ],
        ])->postJson('/admin/api/reports/ai-analyze', [
            'query' => 'Analyze our financial revenue and amenity bookings',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'title',
                    'executive_summary',
                    'key_metrics',
                    'insights',
                    'table_data',
                    'recommendations',
                ],
                'source',
            ]);

        $responseData = $response->json('data');
        $this->assertNotEmpty($responseData['title']);
        $this->assertNotEmpty($responseData['executive_summary']);
        $this->assertIsArray($responseData['key_metrics']);
        $this->assertIsArray($responseData['insights']);
        $this->assertIsArray($responseData['recommendations']);
        // Query did not ask for recommendations, so recommendations should be empty
        $this->assertEmpty($responseData['recommendations']);
    }

    public function test_recommendations_are_included_when_explicitly_requested(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
            ],
        ])->postJson('/admin/api/reports/ai-analyze', [
            'query' => 'Provide strategic recommendations and tips to improve our weekday revenue',
        ]);

        $response->assertOk();
        $responseData = $response->json('data');
        $this->assertNotEmpty($responseData['recommendations']);
    }

    public function test_ai_reports_endpoint_lists_all_amenities_including_unbooked(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        Amenity::create([
            'id' => 'amenity-cabana-1',
            'amenities_name' => 'Cabana One',
            'daytime_price' => 1000,
            'nighttime_price' => 1500,
            'minimum_capacity' => 2,
            'maximum_capacity' => 6,
            'status' => true,
        ]);

        Amenity::create([
            'id' => 'amenity-unbooked-cottage',
            'amenities_name' => 'Unbooked Forest Cottage',
            'daytime_price' => 800,
            'nighttime_price' => 1200,
            'minimum_capacity' => 2,
            'maximum_capacity' => 4,
            'status' => true,
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
            ],
        ])->postJson('/admin/api/reports/ai-analyze', [
            'query' => 'List down all amenities and show a table of their utilization',
        ]);

        $response->assertOk();
        $tableData = $response->json('data.table_data');
        $this->assertNotNull($tableData);
        $this->assertIsArray($tableData['rows']);
        $this->assertGreaterThanOrEqual(2, count($tableData['rows']));
    }
}
