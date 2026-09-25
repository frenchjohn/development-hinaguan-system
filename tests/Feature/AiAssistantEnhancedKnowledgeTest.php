<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminChatbotController;
use App\Http\Controllers\GuestChatbotController;
use App\Http\Controllers\StaffChatbotController;
use App\Models\ActivityLog;
use App\Models\AdminAccount;
use App\Models\Amenity;
use App\Models\Announcement;
use App\Models\Customer;
use App\Models\DailyWeatherShiftLog;
use App\Models\Feedback;
use App\Models\ParkActivity;
use App\Models\ParkEvent;
use App\Models\ParkRule;
use App\Models\ParkSetting;
use App\Models\RescheduleRequest;
use App\Models\Reservation;
use App\Models\ReservationCharge;
use App\Models\ReservationGuest;
use App\Models\StaffAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class AiAssistantEnhancedKnowledgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ParkSetting::create([
            'park_status' => 'open',
            'daytime_start' => '08:00:00',
            'daytime_end' => '17:00:00',
            'nighttime_start' => '18:00:00',
            'nighttime_end' => '08:00:00',
            'opening_time' => '08:00:00',
            'closing_time' => '17:00:00',
            'daytime_adult_entrance_fee' => 20,
            'daytime_child_entrance_fee' => 0,
            'nighttime_adult_entrance_fee' => 50,
            'nighttime_child_entrance_fee' => 0,
            'day_pool_fee' => 50,
            'night_pool_fee' => 100,
            'brenda_available' => true,
        ]);
    }

    public function test_staff_ai_topic_detection_understands_typos_and_bisaya()
    {
        $controller = new StaffChatbotController();
        $refMethod = new ReflectionMethod($controller, 'detectStaffTopics');
        $refMethod->setAccessible(true);

        // Bisaya inquiry about reschedules with typos: "naa bay nagpa-resched o balhin date?"
        $topics1 = $refMethod->invoke($controller, 'naa bay nagpa-resched o balhin date?');
        $this->assertTrue($topics1['reschedules']);

        // Typos and Tagalog inquiry about balance and damages: "magkano pa ang utang at penalty ni Juan?"
        $topics2 = $refMethod->invoke($controller, 'magkano pa ang utang at penalty ni Juan?');
        $this->assertTrue($topics2['charges']);

        // Bisaya inquiry about headcount / checked-in: "pila kabuok nisulod sa park karon?"
        $topics3 = $refMethod->invoke($controller, 'pila kabuok nisulod sa park karon?');
        $this->assertTrue($topics3['checkins']);

        // Typos on amenities: "tagpila ang ahouse ug pyag kung gabii?"
        $topics4 = $refMethod->invoke($controller, 'tagpila ang ahouse ug pyag kung gabii?');
        $this->assertTrue($topics4['amenities']);

        // Demographics with Bisaya: "pila kabuok bata ug tigulang?"
        $topics5 = $refMethod->invoke($controller, 'pila kabuok bata ug tigulang?');
        $this->assertTrue($topics5['demographics']);
    }

    public function test_staff_ai_context_retrieves_connected_data_for_reschedules_charges_announcements()
    {
        $controller = new StaffChatbotController();
        $refContext = new ReflectionMethod($controller, 'getStaffContext');
        $refContext->setAccessible(true);

        $res = Reservation::create([
            'booker_name' => 'Eduardo Santos',
            'phone' => '09171112222',
            'email' => 'eduardo@test.com',
            'reservation_date' => now()->toDateString(),
            'start_slot' => 'Daytime',
            'number_of_guests' => 4,
            'status' => 'Confirmed',
            'reservation_type' => 'online',
            'total_amount' => 1200,
            'amount_paid' => 600,
            'remaining_balance' => 600,
            'payment_status' => 'Partially Paid',
        ]);

        RescheduleRequest::create([
            'reservation_id' => $res->id,
            'token' => 'test-token',
            'original_date' => now()->toDateString(),
            'requested_date' => now()->addDays(3)->toDateString(),
            'status' => 'pending',
            'reason' => 'Emergency family trip',
            'expires_at' => now()->addDays(2),
        ]);

        ReservationCharge::create([
            'reservation_id' => $res->id,
            'description' => 'Extra foam mattress',
            'charge_type' => 'others',
            'amount' => 150.00,
            'status' => 'unpaid',
        ]);

        Announcement::create([
            'title' => 'Typhoon Weather Advisory',
            'message' => 'Please take shelter in covered payags due to rain.',
            'category' => 'Weather Alert',
            'target_type' => 'all',
            'recipient_count' => 14,
            'delivery_status' => 'sent',
        ]);

        // Query about reschedules -> connects to reschedule_requests table
        $contextResched = $refContext->invoke($controller, 'Are there any pending resched requests?');
        $this->assertStringContainsString('RESCHEDULE REQUESTS (reschedule_requests)', $contextResched);
        $this->assertStringContainsString('Eduardo Santos', $contextResched);
        $this->assertStringContainsString('Emergency family trip', $contextResched);

        // Query about charges -> connects to reservation_charges table
        $contextCharges = $refContext->invoke($controller, 'Pila ang extra charge ug balance?');
        $this->assertStringContainsString('CHARGES & BALANCES (reservation_charges)', $contextCharges);
        $this->assertStringContainsString('Extra foam mattress', $contextCharges);

        // Query about SMS / announcements -> connects to sms_notifications table
        $contextSms = $refContext->invoke($controller, 'Naa bay text blast or announcements?');
        $this->assertStringContainsString('SMS ANNOUNCEMENTS (sms_notifications)', $contextSms);
        $this->assertStringContainsString('Typhoon Weather Advisory', $contextSms);
    }

    public function test_admin_ai_has_access_to_staff_accounts_audit_logs_and_financials()
    {
        $controller = new AdminChatbotController();
        $refContext = new ReflectionMethod($controller, 'getAdminContext');
        $refContext->setAccessible(true);

        StaffAccount::create([
            'name' => 'Roberto Bautista',
            'email' => 'roberto@parkhinaguan.com',
            'password' => bcrypt('password123'),
            'ban_status' => false,
        ]);

        AdminAccount::create([
            'name' => 'Super Administrator',
            'email' => 'admin@parkhinaguan.com',
            'password' => bcrypt('password123'),
        ]);

        $res99 = Reservation::create([
            'id' => 99,
            'booker_name' => 'Juan Dela Cruz',
            'phone' => '09189998888',
            'email' => 'juan@test.com',
            'reservation_date' => now()->toDateString(),
            'start_slot' => 'Daytime',
            'number_of_guests' => 2,
            'status' => 'Checked In',
            'reservation_type' => 'online',
            'total_amount' => 800,
            'amount_paid' => 800,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        ActivityLog::create([
            'action' => 'check_in',
            'activity_type' => 'check_in',
            'title' => 'Guest Checked In',
            'description' => 'Checked in Reservation #99 for Juan Dela Cruz',
            'actor_name' => 'Roberto Bautista',
            'actor_role' => 'staff',
            'reservation_id' => $res99->id,
        ]);

        // Admin asks for staff roster
        $contextStaff = $refContext->invoke($controller, 'Sino-sino ang mga staff sa roster?');
        $this->assertStringContainsString('STAFF & ADMIN ROSTER', $contextStaff);
        $this->assertStringContainsString('Roberto Bautista', $contextStaff);
        $this->assertStringNotContainsString('password123', $contextStaff); // Passwords must never be revealed

        // Admin asks for audit logs
        $contextAudit = $refContext->invoke($controller, 'Kinsa nag check in bag-o lang? Show audit history.');
        $this->assertStringContainsString('RECENT ACTIVITY AUDIT TRAIL', $contextAudit);
        $this->assertStringContainsString('Roberto Bautista', $contextAudit);
    }

    public function test_guest_ai_topic_detection_and_fallback_for_booking_and_directions()
    {
        $controller = new GuestChatbotController();
        $refDetect = new ReflectionMethod($controller, 'detectGuestTopics');
        $refDetect->setAccessible(true);
        $refFallback = new ReflectionMethod($controller, 'generateDirectFallbackResponse');
        $refFallback->setAccessible(true);

        // 1. Bisaya inquiry on how to book: "unsaon pag-book ug cottage online?"
        $topicsBooking = $refDetect->invoke($controller, 'unsaon pag-book ug cottage online?');
        $this->assertTrue($topicsBooking['booking_guide']);
        $this->assertTrue($topicsBooking['amenities']);

        // Check fallback for booking: must mention Book Now, 50% downpayment, and non-refundable / walay refund
        $fallbackBookingBisaya = $refFallback->invoke($controller, 'unsaon pag-book ug cottage online?');
        $this->assertStringContainsString('Book Now', $fallbackBookingBisaya);
        $this->assertStringContainsString('50% DOWNPAYMENT', $fallbackBookingBisaya);
        $this->assertStringContainsString('NON-REFUNDABLE', $fallbackBookingBisaya);

        // 2. Tagalog inquiry on directions: "paano pumunta sa park galing CDO?"
        $topicsDirections = $refDetect->invoke($controller, 'paano pumunta sa park galing CDO?');
        $this->assertTrue($topicsDirections['directions']);

        // Check fallback for directions: must mention Solana, Jasaan, Spring View Resort, inner road, and Google Maps
        $fallbackDirections = $refFallback->invoke($controller, 'paano pumunta sa park galing CDO?');
        $this->assertStringContainsString('Solana', $fallbackDirections);
        $this->assertStringContainsString('Jasaan', $fallbackDirections);
        $this->assertStringContainsString('Spring View Resort', $fallbackDirections);
        $this->assertStringContainsString('inner road', $fallbackDirections);
        $this->assertStringContainsString('Google Maps', $fallbackDirections);

        // 3. Bisaya inquiry on directions with Spring View landmark: "drop sa spring view resort jasaan unsaon pagsulod?"
        $topicsSpringView = $refDetect->invoke($controller, 'drop sa spring view resort jasaan unsaon pagsulod?');
        $this->assertTrue($topicsSpringView['directions']);
        $fallbackSpringView = $refFallback->invoke($controller, 'drop sa spring view resort jasaan unsaon pagsulod?');
        $this->assertStringContainsString('Spring View Resort', $fallbackSpringView);
        $this->assertStringContainsString('sulod nga agianan', $fallbackSpringView);

        // 4. Park activities detection
        $topicsAct = $refDetect->invoke($controller, 'unsa pwede activities buhaton sa hinaguan?');
        $this->assertTrue($topicsAct['activities']);
    }

    public function test_guest_ai_context_retrieves_12_tables_and_reservation_details()
    {
        $controller = new GuestChatbotController();
        $refContext = new ReflectionMethod($controller, 'getGuestContext');
        $refContext->setAccessible(true);

        // Create sample data across permitted tables
        $amenity = Amenity::create([
            'id' => 'payag_2',
            'amenities_name' => 'Payag 2 Riverside',
            'description' => 'Bamboo hut right along the running river',
            'daytime_price' => 300,
            'nighttime_price' => 300,
            'minimum_capacity' => 1,
            'maximum_capacity' => 8,
            'status' => true,
        ]);

        $customer = Customer::create([
            'first_name' => 'Maria',
            'last_name' => 'Clara',
            'phone' => '09170001122',
            'email' => 'maria@test.com',
            'age' => 25,
            'gender' => 'Female',
        ]);

        $res = Reservation::create([
            'id' => 77,
            'booker_name' => 'Maria Clara',
            'phone' => '09170001122',
            'email' => 'maria@test.com',
            'reservation_date' => now()->addDays(2)->toDateString(),
            'start_slot' => 'Daytime',
            'number_of_guests' => 3,
            'status' => 'Confirmed',
            'reservation_type' => 'online',
            'total_amount' => 1000,
            'amount_paid' => 500,
            'remaining_balance' => 500,
            'payment_status' => 'Partially Paid',
        ]);

        ReservationGuest::create([
            'reservation_id' => $res->id,
            'customer_id' => $customer->id,
            'is_primary_guest' => true,
            'has_pool_access' => true,
        ]);

        ParkActivity::create([
            'activity' => 'River Swimming',
            'description' => 'Refreshing dip in the clean natural river stream',
        ]);

        Feedback::create([
            'full_name' => 'Satisfied Guest',
            'stars' => 5,
            'description' => 'Very relaxing riverside scenery and cold water!',
            'is_shown' => true,
            'is_anonymous' => false,
        ]);

        // 1. Inquiring about reservation #77 retrieves connected reservation, customer & companion data
        $contextRes = $refContext->invoke($controller, 'Can I check status for booking #77 under Maria Clara?');
        $this->assertStringContainsString('GUEST RESERVATION STATUS (FROM DATABASE reservations, customers, reservation_guests)', $contextRes);
        $this->assertStringContainsString('Reservation #77 for Maria Clara', $contextRes);
        $this->assertStringContainsString('Confirmed', $contextRes);
        $this->assertStringContainsString('Maria Clara', $contextRes);

        // 2. Inquiring about activities retrieves park_activities
        $contextAct = $refContext->invoke($controller, 'What activities can guests do at Hinaguan?');
        $this->assertStringContainsString('OFFICIAL PARK ACTIVITIES (FROM DATABASE park_activities)', $contextAct);
        $this->assertStringContainsString('River Swimming', $contextAct);

        // 3. Inquiring about booking instructions and directions
        $contextGuide = $refContext->invoke($controller, 'How to book online and how to go to the park?');
        $this->assertStringContainsString('HOW TO BOOK A RESERVATION ONLINE', $contextGuide);
        $this->assertStringContainsString('50% DOWNPAYMENT', $contextGuide);
        $this->assertStringContainsString('NON-REFUNDABLE', $contextGuide);
        $this->assertStringContainsString('HOW TO GO / DIRECTIONS TO HINAGUAN NATURE PARK', $contextGuide);
        $this->assertStringContainsString('Spring View Resort', $contextGuide);
        $this->assertStringContainsString('Google Maps', $contextGuide);
    }
}
