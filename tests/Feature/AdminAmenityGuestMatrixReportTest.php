<?php

namespace Tests\Feature;

use App\Models\AdminAccount;
use App\Models\Amenity;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAmenityGuestMatrixReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ParkSetting::create([
            'park_name' => 'Hinaguan Nature Park',
            'daytime_start' => '08:00',
            'daytime_end' => '17:00',
            'nighttime_start' => '17:00',
            'nighttime_end' => '06:00',
            'daytime_adult_entrance_fee' => 70,
            'daytime_child_entrance_fee' => 30,
            'nighttime_adult_entrance_fee' => 100,
            'nighttime_child_entrance_fee' => 50,
            'day_pool_fee' => 50,
            'night_pool_fee' => 75,
        ]);
    }

    public function test_admin_reports_page_renders_matrix_section_and_amenity_options(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Park Admin',
            'email' => 'admin@hinaguan.com',
            'password' => Hash::make('password123'),
        ]);

        $ahouse1 = Amenity::create([
            'id' => 'ah-1',
            'amenities_name' => 'A-House 1',
            'daytime_price' => 300,
            'nighttime_price' => 500,
            'minimum_capacity' => 1,
            'status' => true,
        ]);

        $cottage1 = Amenity::create([
            'id' => 'cot-1',
            'amenities_name' => 'Cottage 1',
            'daytime_price' => 200,
            'nighttime_price' => 200,
            'minimum_capacity' => 1,
            'status' => true,
        ]);

        // Single day overnight reservation on Sep 9 (2 guests)
        $res1 = Reservation::create([
            'booker_name' => 'Single Day Guest',
            'phone' => '09123456789',
            'email' => 'guest1@example.com',
            'number_of_guests' => 2,
            'reservation_date' => '2026-09-09',
            'end_date' => '2026-09-10',
            'total_days' => 1,
            'reservation_type' => 'overnight',
            'status' => 'Checked In',
            'payment_status' => 'Paid',
            'total_amount' => 1000,
            'amount_paid' => 1000,
            'remaining_balance' => 0,
        ]);

        ReservationAmenity::create([
            'reservation_id' => $res1->id,
            'amenity_id' => $ahouse1->id,
            'pricing_type' => 'Overnight',
            'start_date' => '2026-09-09',
            'end_date' => '2026-09-10',
            'price_at_booking' => 1000,
            'quantity' => 1,
        ]);

        // Multi-day stay on Sep 8 to Sep 10 (2 guests)
        $res2 = Reservation::create([
            'booker_name' => 'Multi Day Guest',
            'phone' => '09123456789',
            'email' => 'guest2@example.com',
            'number_of_guests' => 2,
            'reservation_date' => '2026-09-08',
            'end_date' => '2026-09-10',
            'total_days' => 3,
            'reservation_type' => 'continuous_multiday',
            'status' => 'Checked In',
            'payment_status' => 'Paid',
            'total_amount' => 3000,
            'amount_paid' => 3000,
            'remaining_balance' => 0,
        ]);

        ReservationAmenity::create([
            'reservation_id' => $res2->id,
            'amenity_id' => $cottage1->id,
            'pricing_type' => 'Continuous Stay (3D)',
            'start_date' => '2026-09-08',
            'end_date' => '2026-09-10',
            'price_at_booking' => 3000,
            'quantity' => 1,
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
            ],
        ])->get(route('admin.reports'));

        $response->assertOk();
        $response->assertSee('Daily Amenity & Room Occupancy Matrix', false);
        $response->assertSee('Download Excel (.xlsx)');
        $response->assertSee('Download / Print PDF');
        $response->assertSee('A-House 1');
        $response->assertSee('Cottage 1');
        $response->assertSee('All Amenities (Default)');
        $response->assertSee('NUMBER OF GUEST CHECK IN');
        $response->assertSee('NUMBER OF GUESTS STAYED OVERNIGHT');
        $response->assertSee('NUMBER OF ROOMS OCCUPIED');

        // Check reportData json passed to view
        $reportData = $response->viewData('reportData');
        $this->assertNotEmpty($reportData['reservations']);
        $this->assertEquals('Checked In', $reportData['reservations'][0]['status']);
    }

    public function test_matrix_report_data_includes_reservation_statuses(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Park Admin 2',
            'email' => 'admin2@hinaguan.com',
            'password' => Hash::make('password123'),
        ]);

        $cottage = Amenity::create([
            'id' => 'cot-test',
            'amenities_name' => 'Cottage Test',
            'daytime_price' => 200,
            'nighttime_price' => 200,
            'minimum_capacity' => 1,
            'status' => true,
        ]);

        // Pending reservation
        Reservation::create([
            'booker_name' => 'Pending Guest',
            'email' => 'pending@example.com',
            'phone' => '09123456789',
            'number_of_guests' => 5,
            'reservation_date' => '2026-09-15',
            'status' => 'Pending',
            'payment_status' => 'Unpaid',
            'total_amount' => 1000,
            'amount_paid' => 0,
            'remaining_balance' => 1000,
        ]);

        // Confirmed reservation
        Reservation::create([
            'booker_name' => 'Confirmed Guest',
            'email' => 'confirmed@example.com',
            'phone' => '09123456789',
            'number_of_guests' => 3,
            'reservation_date' => '2026-09-16',
            'status' => 'Confirmed',
            'payment_status' => 'Paid',
            'total_amount' => 1000,
            'amount_paid' => 1000,
            'remaining_balance' => 0,
        ]);

        // Checked In reservation
        Reservation::create([
            'booker_name' => 'Checked In Guest',
            'email' => 'checkedin@example.com',
            'phone' => '09123456789',
            'number_of_guests' => 4,
            'reservation_date' => '2026-09-17',
            'status' => 'Checked In',
            'payment_status' => 'Paid',
            'total_amount' => 1000,
            'amount_paid' => 1000,
            'remaining_balance' => 0,
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
            ],
        ])->get(route('admin.reports'));

        $response->assertOk();
        $reportData = $response->viewData('reportData');
        $statuses = collect($reportData['reservations'])->pluck('status')->all();

        $this->assertContains('Pending', $statuses);
        $this->assertContains('Confirmed', $statuses);
        $this->assertContains('Checked In', $statuses);
    }
}
