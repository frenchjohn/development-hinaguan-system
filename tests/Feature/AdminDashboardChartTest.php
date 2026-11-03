<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_renders_executive_chart_with_revenue_and_bookings(): void
    {
        // Create sample reservation
        $res = Reservation::create([
            'booker_name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'phone' => '09123456789',
            'reservation_date' => now()->toDateString(),
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'number_of_guests' => 4,
            'total_amount' => 1200.00,
            'amount_paid' => 600.00,
            'remaining_balance' => 600.00,
            'status' => 'Checked In',
            'reservation_type' => 'online',
            'check_in' => now()->toDateString(),
        ]);

        // Create sample activity log
        ActivityLog::create([
            'reservation_id' => $res->id,
            'activity_type' => 'online_reservation_created',
            'title' => 'Online Reservation Deposit',
            'description' => 'Online downpayment of ₱600.00 received',
            'payment_amount' => 600.00,
            'actor_name' => 'Maria Santos',
            'actor_role' => 'guest',
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => 1,
                'name' => 'Park Admin',
                'role' => 'admin',
            ],
        ])->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Financial Revenue Trends');
        $response->assertSee('id="sdTabRevenue"', false);
        $response->assertSee('id="sdTabBookings"', false);
        $response->assertSee('id="sdAdminStat1Val"', false);
        $response->assertSee('id="sdAdminChartTooltip"', false);
        $response->assertSee('window.__sdAdminOpsChartData', false);
    }
}
