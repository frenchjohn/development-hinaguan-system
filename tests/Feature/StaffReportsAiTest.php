<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\StaffAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffReportsAiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_staff_ai_reports_endpoint(): void
    {
        $response = $this->postJson('/staff/api/reports/ai-analyze', [
            'query' => 'Analyze our total revenue',
        ]);

        $response->assertStatus(403)
            ->assertJson(['error' => 'Unauthorized access']);
    }

    public function test_authenticated_staff_can_view_reports_page_with_dual_tabs(): void
    {
        $staff = StaffAccount::create([
            'name' => 'Front Desk Staff',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => 'staff',
            ],
        ])->get('/staff/reports');

        $response->assertOk();
        $response->assertSee('Standard Reports');
        $response->assertSee('AI Report Studio');
        $response->assertSee('AI Staff Analytics Studio');
        $response->assertSee('Quick Analytical Audits');
        $response->assertSee('Revenue & Financials');
        $response->assertSee('Amenity Utilization');
    }

    public function test_authenticated_staff_can_generate_ai_report(): void
    {
        $staff = StaffAccount::create([
            'name' => 'Front Desk Staff',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
        ]);

        $amenity = Amenity::create([
            'id' => 'amenity-cabana-riverside',
            'amenities_name' => 'Riverside Cabana',
            'daytime_price' => 1200,
            'nighttime_price' => 1800,
            'minimum_capacity' => 2,
            'maximum_capacity' => 8,
            'status' => true,
        ]);

        $reservation = Reservation::create([
            'booker_name' => 'Maria Santos',
            'phone' => '09171234567',
            'email' => 'maria@example.com',
            'reservation_date' => now()->toDateString(),
            'total_days' => 1,
            'number_of_guests' => 4,
            'total_amount' => 1200.00,
            'amount_paid' => 1200.00,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
            'status' => 'Confirmed',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $amenity->id,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 1200.00,
            'quantity' => 1,
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => 'staff',
            ],
        ])->postJson('/staff/api/reports/ai-analyze', [
            'query' => 'Compare online vs walk-in revenue and list down all amenities',
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
    }

    public function test_staff_ai_reports_endpoint_lists_all_amenities_including_unbooked(): void
    {
        $staff = StaffAccount::create([
            'name' => 'Front Desk Staff',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
        ]);

        Amenity::create([
            'id' => 'amenity-cabana-a',
            'amenities_name' => 'Cabana A',
            'daytime_price' => 1000,
            'nighttime_price' => 1500,
            'minimum_capacity' => 2,
            'maximum_capacity' => 6,
            'status' => true,
        ]);

        Amenity::create([
            'id' => 'amenity-villa-b',
            'amenities_name' => 'Forest Villa B',
            'daytime_price' => 2000,
            'nighttime_price' => 2500,
            'minimum_capacity' => 4,
            'maximum_capacity' => 10,
            'status' => true,
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => 'staff',
            ],
        ])->postJson('/staff/api/reports/ai-analyze', [
            'query' => 'List down all amenities and show a table of their utilization',
        ]);

        $response->assertOk();
        $tableData = $response->json('data.table_data');
        $this->assertNotNull($tableData);
        $this->assertIsArray($tableData['rows']);
        $this->assertGreaterThanOrEqual(2, count($tableData['rows']));
    }
}
