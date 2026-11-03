<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ParkSetting;
use App\Models\StaffAccount;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPaymentPdfTest extends TestCase
{
    use RefreshDatabase;

    protected StaffAccount $staff;

    protected function setUp(): void
    {
        parent::setUp();

        ParkSetting::create([
            'daytime_start'   => '08:00:00',
            'daytime_end'     => '17:00:00',
            'nighttime_start' => '18:00:00',
            'nighttime_end'   => '08:00:00',
        ]);

        $this->staff = StaffAccount::create([
            'name'     => 'Staff Jane',
            'email'    => 'staff_jane@example.com',
            'password' => Hash::make('secret1234'),
        ]);
    }

    public function test_guest_cannot_access_payment_pdf(): void
    {
        $response = $this->get('/admin/payment/pdf');
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_access_payment_page_with_print_pdf_button(): void
    {
        $response = $this->withSession([
            'auth_user' => [
                'name' => 'Admin User',
                'role' => 'admin',
            ],
        ])->get('/admin/payment');

        $response->assertStatus(200);
        $response->assertSee('id="printPdfBtn"', false);
        $response->assertSee('Print as PDF');
    }

    public function test_admin_can_generate_unfiltered_payment_pdf(): void
    {
        ActivityLog::create([
            'activity_type'  => 'check_in',
            'title'          => 'Check-In Payment Test',
            'description'    => 'Walk-in paid cash',
            'payment_amount' => 500.00,
            'actor_name'     => $this->staff->name,
            'actor_role'     => 'staff',
            'staff_id'       => $this->staff->id,
            'created_at'     => Carbon::parse('2026-11-01 10:00:00'),
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'name' => 'Admin User',
                'role' => 'admin',
            ],
        ])->get('/admin/payment/pdf');

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_admin_can_filter_pdf_by_date_and_session(): void
    {
        // Daytime record on Nov 01
        ActivityLog::create([
            'activity_type'  => 'walkin_created',
            'title'          => 'Daytime Walkin',
            'description'    => 'Walk-in booking for daytime session',
            'payment_amount' => 700.00,
            'actor_name'     => $this->staff->name,
            'actor_role'     => 'staff',
            'staff_id'       => $this->staff->id,
            'created_at'     => Carbon::parse('2026-11-01 11:00:00'),
        ]);

        // Overnight record on Nov 02
        ActivityLog::create([
            'activity_type'  => 'check_in',
            'title'          => 'Overnight CheckIn',
            'description'    => 'Overnight stay check-in',
            'payment_amount' => 1200.00,
            'actor_name'     => $this->staff->name,
            'actor_role'     => 'staff',
            'staff_id'       => $this->staff->id,
            'created_at'     => Carbon::parse('2026-11-02 20:00:00'),
        ]);

        // Test filtering by daytime on Nov 01
        $responseDay = $this->withSession([
            'auth_user' => [
                'name' => 'Admin User',
                'role' => 'admin',
            ],
        ])->get('/admin/payment/pdf?start_date=2026-11-01&end_date=2026-11-01&session=daytime');

        $responseDay->assertStatus(200);
        $this->assertEquals('application/pdf', $responseDay->headers->get('content-type'));

        // Test filtering by overnight
        $responseNight = $this->withSession([
            'auth_user' => [
                'name' => 'Admin User',
                'role' => 'admin',
            ],
        ])->get('/admin/payment/pdf?session=overnight');

        $responseNight->assertStatus(200);
        $this->assertEquals('application/pdf', $responseNight->headers->get('content-type'));
    }
}
