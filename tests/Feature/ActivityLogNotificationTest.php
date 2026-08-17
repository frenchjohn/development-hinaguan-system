<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AdminAccount;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ReservationGuest;
use App\Models\StaffAccount;
use App\Models\UserActivityRead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActivityLogNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected AdminAccount $admin;
    protected StaffAccount $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminAccount::create([
            'email' => 'admin_notif_test@example.com',
            'name' => 'Admin Notif Tester',
            'password' => Hash::make('secret1234'),
        ]);

        $this->staff = StaffAccount::create([
            'email' => 'staff_notif_test@example.com',
            'name' => 'Staff Notif Tester',
            'password' => Hash::make('secret1234'),
        ]);
    }

    public function test_each_admin_and_staff_account_has_independent_unread_tracking(): void
    {
        // 1. Create a new activity log
        $log = ActivityLog::create([
            'activity_type' => 'check_in',
            'title' => 'Guest Checked In',
            'description' => 'John Doe checked in with 2 guests by Staff Tester',
            'actor_name' => 'Staff Tester',
            'actor_role' => 'staff',
        ]);

        // 2. Admin fetches notifications -> unread_count is 1 and log is_new is true
        $adminResponse = $this->withSession([
            'auth_user' => [
                'id' => $this->admin->id,
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'admin',
            ],
        ])->getJson('/api/activity-notifications');

        $adminResponse->assertStatus(200);
        $this->assertEquals(1, $adminResponse->json('unread_count'));
        $this->assertTrue($adminResponse->json('activities.0.is_new'));

        // 3. Admin marks all as read
        $markReadResponse = $this->withSession([
            'auth_user' => [
                'id' => $this->admin->id,
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'admin',
            ],
        ])->postJson('/api/activity-notifications/mark-read', [
            'last_seen_id' => $log->id,
        ]);

        $markReadResponse->assertStatus(200);
        $this->assertEquals(0, $markReadResponse->json('unread_count'));

        // 4. Admin re-checks -> 0 unread, is_new is false
        $adminRecheck = $this->withSession([
            'auth_user' => [
                'id' => $this->admin->id,
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'admin',
            ],
        ])->getJson('/api/activity-notifications');

        $this->assertEquals(0, $adminRecheck->json('unread_count'));
        $this->assertFalse($adminRecheck->json('activities.0.is_new'));

        // 5. Staff logs in -> FOR STAFF IT IS STILL UNREAD & NEW!
        $staffResponse = $this->withSession([
            'auth_user' => [
                'id' => $this->staff->id,
                'name' => $this->staff->name,
                'email' => $this->staff->email,
                'role' => 'staff',
            ],
        ])->getJson('/api/activity-notifications');

        $staffResponse->assertStatus(200);
        $this->assertEquals(1, $staffResponse->json('unread_count'), 'Staff should still have 1 unread notification even if Admin already read it');
        $this->assertTrue($staffResponse->json('activities.0.is_new'), 'Log should still be marked as NEW for Staff');

        // 6. Staff marks as read -> Now Staff also has 0 unread
        $this->withSession([
            'auth_user' => [
                'id' => $this->staff->id,
                'name' => $this->staff->name,
                'email' => $this->staff->email,
                'role' => 'staff',
            ],
        ])->postJson('/api/activity-notifications/mark-read', [
            'last_seen_id' => $log->id,
        ])->assertStatus(200);

        $staffRecheck = $this->withSession([
            'auth_user' => [
                'id' => $this->staff->id,
                'name' => $this->staff->name,
                'email' => $this->staff->email,
                'role' => 'staff',
            ],
        ])->getJson('/api/activity-notifications');

        $this->assertEquals(0, $staffRecheck->json('unread_count'));
        $this->assertFalse($staffRecheck->json('activities.0.is_new'));
    }

    public function test_guest_checkout_creates_activity_log_and_triggers_notification(): void
    {
        $customer = Customer::create([
            'first_name' => 'Alice',
            'last_name' => 'Wonderland',
            'gender' => 'Female',
            'age' => 25,
            'email' => 'alice@example.com',
            'phone' => '09123456789',
        ]);

        $reservation = Reservation::create([
            'booker_name' => 'Alice Wonderland',
            'phone' => '09123456789',
            'email' => 'alice@example.com',
            'reservation_date' => now()->toDateString(),
            'check_in' => now(),
            'number_of_guests' => 1,
            'status' => 'Checked In',
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $guest = ReservationGuest::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'is_primary_guest' => true,
            'checked_out_at' => null,
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $this->staff->id,
                'name' => 'Staff Officer John',
                'email' => $this->staff->email,
                'role' => 'staff',
            ],
        ])->postJson("/staff/reservation-guests/{$guest->id}/check-out");

        $response->assertStatus(200);
        $this->assertNotNull($guest->fresh()->checked_out_at);

        // Verify Activity Log was created!
        $log = ActivityLog::where('reservation_id', $reservation->id)
            ->where('activity_type', 'check_out')
            ->first();

        $this->assertNotNull($log, 'An activity log must be generated when checking out a guest');
        $this->assertStringContainsString('Alice Wonderland', $log->description);
        $this->assertStringContainsString('Staff Officer John', $log->description);
        $this->assertEquals('Staff Officer John', $log->actor_name);
    }

    public function test_reservation_full_checkout_creates_activity_log(): void
    {
        $reservation = Reservation::create([
            'booker_name' => 'Bob Builder',
            'phone' => '09998887777',
            'email' => 'bob@example.com',
            'reservation_date' => now()->toDateString(),
            'check_in' => now(),
            'number_of_guests' => 2,
            'status' => 'Checked In',
            'total_amount' => 1000,
            'amount_paid' => 1000,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $this->staff->id,
                'name' => 'Staff Officer John',
                'email' => $this->staff->email,
                'role' => 'staff',
            ],
        ])->postJson("/staff/reservations/{$reservation->id}/check-out");

        $response->assertStatus(200);

        $log = ActivityLog::where('reservation_id', $reservation->id)
            ->where('activity_type', 'check_out')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('Bob Builder', $log->description);
        $this->assertEquals('Staff Officer John', $log->actor_name);
    }

    public function test_check_only_heartbeat_returns_fast_status(): void
    {
        $log = ActivityLog::create([
            'activity_type' => 'amenity_added',
            'title' => 'Amenity Added',
            'description' => 'Pool cottage added by Staff Tester',
            'actor_name' => 'Staff Tester',
            'actor_role' => 'staff',
        ]);

        // When client is up to date
        $response = $this->withSession([
            'auth_user' => [
                'id' => $this->admin->id,
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'admin',
            ],
        ])->getJson('/api/activity-notifications?check_only=1&latest_id=' . $log->id . '&last_seen_id=' . $log->id);

        $response->assertStatus(200);
        $response->assertJson([
            'has_new' => false,
            'latest_id' => $log->id,
            'unread_count' => 0,
        ]);

        // When a new log arrives
        $newLog = ActivityLog::create([
            'activity_type' => 'rule_created',
            'title' => 'Park Rule Created',
            'description' => 'New Rule',
            'actor_name' => 'Admin Tester',
            'actor_role' => 'admin',
        ]);

        $responseNew = $this->withSession([
            'auth_user' => [
                'id' => $this->admin->id,
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'admin',
            ],
        ])->getJson('/api/activity-notifications?check_only=1&latest_id=' . $log->id . '&last_seen_id=' . $log->id);

        $responseNew->assertStatus(200);
        $responseNew->assertJson([
            'has_new' => true,
            'latest_id' => $newLog->id,
            'unread_count' => 1,
        ]);
    }

    public function test_since_id_returns_only_new_activities(): void
    {
        $log1 = ActivityLog::create([
            'activity_type' => 'check_in',
            'title' => 'Check In 1',
            'description' => 'Description 1',
            'actor_name' => 'Staff 1',
            'actor_role' => 'staff',
        ]);

        $log2 = ActivityLog::create([
            'activity_type' => 'check_in',
            'title' => 'Check In 2',
            'description' => 'Description 2',
            'actor_name' => 'Staff 2',
            'actor_role' => 'staff',
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $this->staff->id,
                'name' => $this->staff->name,
                'email' => $this->staff->email,
                'role' => 'staff',
            ],
        ])->getJson('/api/activity-notifications?since_id=' . $log1->id);

        $response->assertStatus(200);
        $activities = $response->json('activities');
        $this->assertCount(1, $activities);
        $this->assertEquals($log2->id, $activities[0]['id']);
        $this->assertEquals('Check In 2', $activities[0]['title']);
    }

    public function test_unauthenticated_users_cannot_access_activity_notifications(): void
    {
        $response = $this->getJson('/api/activity-notifications');
        $response->assertStatus(401);
    }

    public function test_header_renders_activity_log_notification_elements(): void
    {
        ActivityLog::create([
            'activity_type' => 'check_out',
            'title' => 'Guest Checked Out',
            'description' => 'Guest Jane checked out by Staff Notif Tester',
            'actor_name' => 'Staff Notif Tester',
            'actor_role' => 'staff',
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $this->admin->id,
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'admin',
            ],
        ])->get(route('admin.settings'));

        $response->assertStatus(200);
        $response->assertSee('id="notifBellBtn"', false);
        $response->assertSee('id="notifDropdown"', false);
        $response->assertSee('Activity Logs');
        $response->assertSee('Guest Checked Out');
        $response->assertSee('Staff Notif Tester');
    }
}
