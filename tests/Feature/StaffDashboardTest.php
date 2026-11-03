<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ReservationGuest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_dashboard_displays_real_dashboard_metrics(): void
    {
        session(['auth_user' => ['id' => 1, 'name' => 'Staff', 'email' => 'staff@example.com', 'role' => 'staff']]);

        $customer = Customer::create([
            'first_name' => 'Active',
            'middle_name' => null,
            'last_name' => 'Guest',
            'age' => 21,
            'gender' => 'Male',
            'nationality' => 'Filipino',
            'is_foreigner' => false,
            'phone' => '09170000000',
            'email' => 'active@example.com',
        ]);

        $reservation = Reservation::create([
            'booker_name' => 'Active Guest',
            'phone' => '09170000000',
            'email' => 'active@example.com',
            'check_in' => now()->toDateString(),
            'number_of_guests' => 1,
            'reservation_type' => 'online',
            'status' => 'Checked In',
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        ReservationGuest::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'is_primary_guest' => true,
            'checked_out_at' => null,
        ]);

        Reservation::create([
            'booker_name' => 'Pending Guest',
            'phone' => '09170000001',
            'email' => 'pending@example.com',
            'check_in' => now()->addDay()->toDateString(),
            'number_of_guests' => 2,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 800,
            'amount_paid' => 400,
            'remaining_balance' => 400,
            'payment_status' => 'Partially Paid',
        ]);

        $response = $this->get('/staff/dashboard');

        $response->assertOk();
        $response->assertViewHas('todayCheckIns', 1);
        $response->assertViewHas('pendingReservationsCount', 1);
        $response->assertViewHas('guestsOnSiteCount', 1);
        $response->assertSee('activeOverviewDonutCanvas');
        $response->assertSee('activeOverviewBarCanvas');
        $response->assertSee('window.__sdActiveOverviewData');
        $response->assertViewHas('weekCheckInCounts');
        $response->assertViewHas('weekGuestsAdmitted');
        $response->assertViewHas('totalWeekGuests');
        $response->assertSee('sdTabTraffic');
        $response->assertSee('sdTabCollections');
        $response->assertSee('window.__sdOpsChartData');
        $response->assertSee(route('staff.checkins'));
        $response->assertSee(route('staff.checkins', ['tab' => 'dashboard']));
        $response->assertSee(route('staff.reports'));
        $response->assertSee(route('staff.occupancy-monitor'));
        $response->assertSee(route('staff.reservations'));
    }

    public function test_staff_checkins_page_opens_dashboard_tab_when_requested(): void
    {
        session(['auth_user' => ['id' => 1, 'name' => 'Staff', 'email' => 'staff@example.com', 'role' => 'staff']]);

        $response = $this->get('/staff/check-ins?tab=dashboard');

        $response->assertOk();
        $response->assertSee('id="dashboardSection"', false);
        $response->assertSee('id="widgetOverview"', false);
        $response->assertSee('id="widgetDemographics"', false);
        $response->assertSee('id="widgetAgeGroups"', false);
        $response->assertSee('id="widgetPool"', false);
        $response->assertSee('id="widgetCheckoutAlerts"', false);
    }
}
