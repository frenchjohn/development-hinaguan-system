<?php

namespace Tests\Feature;

use App\Models\AdminAccount;
use App\Models\Announcement;
use App\Models\Customer;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationGuest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminAnnouncementPageTest extends TestCase
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
            'park_status' => 'open',
        ]);
    }

    public function test_admin_sidemenu_order_is_correct(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Admin User',
            'email' => 'admin@hinaguan.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
            ],
        ])->get('/admin/dashboard');

        $response->assertOk();
        $content = $response->getContent();

        // Check order: Dashboard, Amenities, Users, Announcement, Reports, Feedback
        $posDashboard = strpos($content, route('admin.dashboard'));
        $posAmenities = strpos($content, route('admin.amenities'));
        $posUsers = strpos($content, route('admin.users'));
        $posAnnouncement = strpos($content, route('admin.announcements'));
        $posReports = strpos($content, route('admin.reports'));
        $posFeedback = strpos($content, route('admin.feedback'));

        $this->assertNotFalse($posDashboard, 'Dashboard link not found');
        $this->assertNotFalse($posAmenities, 'Amenities link not found');
        $this->assertNotFalse($posUsers, 'Users link not found');
        $this->assertNotFalse($posAnnouncement, 'Announcement link not found');
        $this->assertNotFalse($posReports, 'Reports link not found');
        $this->assertNotFalse($posFeedback, 'Feedback link not found');

        $this->assertTrue($posDashboard < $posAmenities, 'Dashboard must be before Amenities');
        $this->assertTrue($posAmenities < $posUsers, 'Amenities must be before Users');
        $this->assertTrue($posUsers < $posAnnouncement, 'Users must be before Announcement');
        $this->assertTrue($posAnnouncement < $posReports, 'Announcement must be before Reports');
        $this->assertTrue($posReports < $posFeedback, 'Reports must be before Feedback');
    }

    public function test_announcements_page_renders_reservations_with_companions_and_filters(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Admin User',
            'email' => 'admin@hinaguan.com',
            'password' => Hash::make('password123'),
        ]);

        // Create Active Reservation with 1 primary and 2 companions
        $activeRes = Reservation::create([
            'booker_name' => 'John Doe',
            'phone' => '09123456789',
            'email' => 'john@example.com',
            'reservation_date' => now(),
            'check_in' => now(),
            'check_out' => null,
            'status' => 'Checked In',
            'number_of_guests' => 3,
            'reservation_type' => 'online',
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $primaryCust = Customer::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'age' => 32,
            'gender' => 'Male',
            'phone' => '09123456789',
            'email' => 'john@example.com',
        ]);

        ReservationGuest::create([
            'reservation_id' => $activeRes->id,
            'customer_id' => $primaryCust->id,
            'is_primary_guest' => true,
            'has_pool_access' => true,
        ]);

        $comp1 = Customer::create(['first_name' => 'Jane', 'last_name' => 'Doe', 'age' => 25, 'gender' => 'Female']);
        ReservationGuest::create([
            'reservation_id' => $activeRes->id,
            'customer_id' => $comp1->id,
            'is_primary_guest' => false,
            'has_pool_access' => true,
        ]);

        $comp2 = Customer::create(['first_name' => 'Bobby', 'last_name' => 'Doe', 'age' => 10, 'gender' => 'Male']);
        ReservationGuest::create([
            'reservation_id' => $activeRes->id,
            'customer_id' => $comp2->id,
            'is_primary_guest' => false,
            'has_pool_access' => false,
        ]);

        // Create Checked Out Reservation
        $checkedOutRes = Reservation::create([
            'booker_name' => 'Sarah Connor',
            'phone' => '09987654321',
            'email' => 'sarah@example.com',
            'reservation_date' => now()->subDays(2),
            'check_in' => now()->subDays(2),
            'check_out' => now()->subDays(1),
            'status' => 'Checked Out',
            'number_of_guests' => 1,
            'reservation_type' => 'walk_in',
            'total_amount' => 200,
            'amount_paid' => 200,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
            ],
        ])->get('/admin/announcements');

        $response->assertOk();
        $content = $response->getContent();

        // Check page title and headings
        $this->assertStringContainsString('Guest SMS Announcements', $content);
        $this->assertStringContainsString('Active In-Park', $content);
        $this->assertStringContainsString('All Reservations', $content);

        // Check active guest display
        $this->assertStringContainsString('John Doe', $content);
        $this->assertStringContainsString('09123456789', $content);
        $this->assertStringContainsString('2 Companions', $content);

        // Check checked-out reservation display
        $this->assertStringContainsString('Sarah Connor', $content);
        $this->assertStringContainsString('09987654321', $content);
    }

    public function test_send_sms_announcement_broadcast(): void
    {
        Http::fake([
            'https://dashboard.philsms.com/*' => Http::response(['status' => 'success', 'message' => 'SMS queued successfully'], 200),
        ]);

        $admin = AdminAccount::create([
            'name' => 'Admin User',
            'email' => 'admin@hinaguan.com',
            'password' => Hash::make('password123'),
        ]);

        $res = Reservation::create([
            'booker_name' => 'Alice Walker',
            'phone' => '09171234567',
            'email' => 'alice@example.com',
            'status' => 'Checked In',
            'number_of_guests' => 2,
            'reservation_type' => 'online',
            'total_amount' => 300,
            'amount_paid' => 300,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $cust = Customer::create([
            'first_name' => 'Alice',
            'last_name' => 'Walker',
            'age' => 28,
            'gender' => 'Female',
            'phone' => '09171234567',
            'email' => 'alice@example.com',
        ]);

        ReservationGuest::create([
            'reservation_id' => $res->id,
            'customer_id' => $cust->id,
            'is_primary_guest' => true,
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
            ],
        ])->postJson('/admin/announcements/send-sms', [
            'reservation_ids' => [$res->id],
            'message' => 'Hinaguan Nature Park Advisory: Pool open until 10 PM tonight.',
            'title' => 'Pool Hours Notice',
            'category' => 'sms_broadcast',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'sent_count' => 1,
        ]);

        $this->assertDatabaseHas('announcements', [
            'title' => 'Pool Hours Notice',
            'recipient_count' => 1,
            'created_by' => 'Admin User',
        ]);
    }
}
