<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\AmenityBenefit;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\StaffAccount;
use App\Models\AdminAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotConversationalRobustnessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ParkSetting::create([
            'opening_time' => '08:00:00',
            'closing_time' => '17:00:00',
            'daytime_start' => '08:00:00',
            'daytime_end' => '17:00:00',
            'nighttime_start' => '18:00:00',
            'nighttime_end' => '08:00:00',
            'daytime_adult_entrance_fee' => 20.00,
            'nighttime_adult_entrance_fee' => 50.00,
            'day_pool_fee' => 50.00,
            'night_pool_fee' => 50.00,
            'brenda_available' => true,
        ]);

        Amenity::create([
            'id' => '1',
            'amenities_name' => 'Payag 2',
            'category' => 'Payags',
            'daytime_price' => 300.00,
            'nighttime_price' => 300.00,
            'minimum_capacity' => 1,
            'maximum_capacity' => 10,
            'status' => true,
        ]);

        Amenity::create([
            'id' => '2',
            'amenities_name' => 'Payag 4',
            'category' => 'Payags',
            'daytime_price' => 300.00,
            'nighttime_price' => 300.00,
            'minimum_capacity' => 1,
            'maximum_capacity' => 10,
            'status' => true,
        ]);

        Amenity::create([
            'id' => '3',
            'amenities_name' => 'Payag 6',
            'category' => 'Payags',
            'daytime_price' => 300.00,
            'nighttime_price' => 300.00,
            'minimum_capacity' => 1,
            'maximum_capacity' => 10,
            'status' => true,
        ]);

        Amenity::create([
            'id' => '4',
            'amenities_name' => 'Function Hall',
            'category' => 'Function Hall',
            'daytime_price' => 5000.00,
            'nighttime_price' => 10000.00,
            'minimum_capacity' => 15,
            'maximum_capacity' => 100,
            'status' => true,
        ]);

        Amenity::create([
            'id' => '5',
            'amenities_name' => 'A-House 2',
            'category' => 'A-Houses',
            'daytime_price' => 700.00,
            'nighttime_price' => 1000.00,
            'minimum_capacity' => 1,
            'maximum_capacity' => 2,
            'status' => true,
        ]);

        AmenityBenefit::create([
            'amenity_id' => '5',
            'is_aircon' => true,
            'free_entrance' => true,
            'free_pool' => true,
        ]);

        Amenity::create([
            'id' => '6',
            'amenities_name' => 'A-House 3',
            'category' => 'A-Houses',
            'daytime_price' => 700.00,
            'nighttime_price' => 1000.00,
            'minimum_capacity' => 1,
            'maximum_capacity' => 2,
            'status' => true,
        ]);

        AmenityBenefit::create([
            'amenity_id' => '6',
            'is_aircon' => true,
            'free_entrance' => true,
            'free_pool' => true,
        ]);

        // Fake LLMs to simulate fallback or consistent local intelligence
        Http::fake([
            'https://openrouter.ai/*' => Http::response(['error' => 'Simulated offline'], 500),
            'https://api.openai.com/*' => Http::response(['error' => 'Simulated offline'], 500),
        ]);
    }

    /**
     * Test Scenario 1: Multi-turn booking in English with "its 2" headcount resolution
     * (Verifies that "its 2" is recognized as headcount rather than mistaken for an amenity suggestion request).
     */
    public function test_multi_turn_booking_english_with_short_headcount_reply(): void
    {
        $tomorrow = now()->addDay()->toDateString();

        // Turn 1: Guest initiates booking for tomorrow daytime and chooses Payag 2
        $resp1 = $this->postJson('/guest-chatbot', [
            'message' => "I want to book Payag 2 tomorrow Daytime",
            'history' => [],
            'booking_draft' => [],
        ]);

        $resp1->assertOk();
        $draft1 = $resp1->json('booking_draft');
        $this->assertNotEmpty($draft1['amenities']);
        $this->assertEquals('Payag 2', $draft1['amenities'][0]['name']);
        $this->assertEquals($tomorrow, $draft1['startDate']);
        $this->assertEquals('Daytime', $draft1['startSlot']);

        // Turn 2: Guest provides name
        $resp2 = $this->postJson('/guest-chatbot', [
            'message' => "its danny pangilinan",
            'history' => [
                ['role' => 'user', 'content' => "I want to book Payag 2 tomorrow Daytime"],
                ['role' => 'assistant', 'content' => $resp1->json('reply')],
            ],
            'booking_draft' => $draft1,
        ]);

        $resp2->assertOk();
        $draft2 = $resp2->json('booking_draft');
        $this->assertEquals('Danny Pangilinan', $draft2['guest']['name']);

        // Turn 3: Guest provides mobile number
        $resp3 = $this->postJson('/guest-chatbot', [
            'message' => "09930457138",
            'history' => [
                ['role' => 'user', 'content' => "its danny pangilinan"],
                ['role' => 'assistant', 'content' => $resp2->json('reply')],
            ],
            'booking_draft' => $draft2,
        ]);

        $resp3->assertOk();
        $draft3 = $resp3->json('booking_draft');
        $this->assertEquals('09930457138', $draft3['guest']['phone']);

        // Turn 4: Guest provides email
        $resp4 = $this->postJson('/guest-chatbot', [
            'message' => "danny@gmail.com",
            'history' => [
                ['role' => 'user', 'content' => "09930457138"],
                ['role' => 'assistant', 'content' => $resp3->json('reply')],
            ],
            'booking_draft' => $draft3,
        ]);

        $resp4->assertOk();
        $draft4 = $resp4->json('booking_draft');
        $this->assertEquals('danny@gmail.com', $draft4['guest']['email']);

        // Turn 5: Assistant asks for guests count; user replies with short phrase "its 2"
        $resp5 = $this->postJson('/guest-chatbot', [
            'message' => "its 2",
            'history' => [
                ['role' => 'user', 'content' => "danny@gmail.com"],
                ['role' => 'assistant', 'content' => "Thank you! How many guests in total will be visiting with you? 👥"],
            ],
            'booking_draft' => $draft4,
        ]);

        $resp5->assertOk();
        $draft5 = $resp5->json('booking_draft');
        // Crucial Assertion: Headcount must be 2, NOT null, and NOT misinterpreted as suggestion request!
        $this->assertEquals(2, $draft5['guest']['guests_count']);
        $this->assertEquals('payment', $draft5['step']);
        $this->assertStringContainsString('PROCEED_PAYMENT_BUTTON', $resp5->json('reply'));
        $this->assertStringContainsString('Danny Pangilinan', $resp5->json('reply'));
        $this->assertStringContainsString('₱300.00', $resp5->json('reply'));
    }

    /**
     * Test Scenario 2: Amenity swap and removal during active booking
     * (Verifies that "change payag 6 into payag 4" replaces payag 6 without duplicate or argument,
     * and "no function hall" drops function hall cleanly).
     */
    public function test_amenity_swap_and_negation_without_hallucinated_arguments(): void
    {
        $tomorrow = now()->addDay()->toDateString();

        // Guest starts with Payag 2 and Payag 6
        $initialDraft = [
            'active' => true,
            'startDate' => $tomorrow,
            'endDate' => $tomorrow,
            'startSlot' => 'Daytime',
            'endSlot' => 'Daytime',
            'amenities' => [
                ['id' => '1', 'name' => 'Payag 2', 'price' => 300.0],
                ['id' => '3', 'name' => 'Payag 6', 'price' => 300.0],
            ],
            'guest' => [
                'name' => 'Danny Pangilinan',
                'phone' => '09930457138',
            ],
        ];

        // Guest requests swap: "oh wait i want to change the payag6 into payag 4 pls"
        $resp = $this->postJson('/guest-chatbot', [
            'message' => "oh wait i want to change the payag6 into payag 4 pls",
            'history' => [
                ['role' => 'assistant', 'content' => "I've got Payag 2 and Payag 6 for your 14 guests. May I have your email?"],
            ],
            'booking_draft' => $initialDraft,
        ]);

        $resp->assertOk();
        $updatedDraft = $resp->json('booking_draft');
        $amenityNames = array_map(fn($a) => $a['name'], $updatedDraft['amenities']);

        // Assert Payag 6 is gone and Payag 4 is present
        $this->assertNotContains('Payag 6', $amenityNames);
        $this->assertContains('Payag 4', $amenityNames);
        $this->assertContains('Payag 2', $amenityNames);
        $this->assertCount(2, $updatedDraft['amenities']);

        // Response must NOT contain contradictory phrases
        $reply = $resp->json('reply');
        $this->assertStringNotContainsString('would leave Payag 4 selected twice', $reply);
        $this->assertStringNotContainsString('the active draft still shows', $reply);
    }

    /**
     * Test Scenario 3: Topic change / interruption mid-booking
     * (User asks about corkage / outside food in Bisaya mid-booking, then resumes seamlessly).
     */
    public function test_topic_change_interruption_mid_booking(): void
    {
        $tomorrow = now()->addDay()->toDateString();

        $activeDraft = [
            'active' => true,
            'startDate' => $tomorrow,
            'endDate' => $tomorrow,
            'startSlot' => 'Daytime',
            'endSlot' => 'Daytime',
            'amenities' => [
                ['id' => '1', 'name' => 'Payag 2', 'price' => 300.0],
            ],
            'guest' => [
                'name' => 'Danny Pangilinan',
            ],
        ];

        // User asks about food/corkage in Bisaya in the middle of booking
        $resp = $this->postJson('/guest-chatbot', [
            'message' => "pwede ba magdala ug pagkaon gikan sa gawas? naay bayad?",
            'history' => [
                ['role' => 'assistant', 'content' => "Thank you, Danny! May I have your mobile number?"],
            ],
            'booking_draft' => $activeDraft,
        ]);

        $resp->assertOk();
        $reply = $resp->json('reply');
        // Answers corkage question accurately
        $this->assertTrue(
            str_contains(strtolower($reply), 'corkage') ||
            str_contains(strtolower($reply), 'pagkaon') ||
            str_contains(strtolower($reply), 'libre') ||
            str_contains(strtolower($reply), 'outside food')
        );

        // Booking draft remains active and unchanged!
        $returnedDraft = $resp->json('booking_draft');
        $this->assertTrue($returnedDraft['active']);
        $this->assertEquals('Danny Pangilinan', $returnedDraft['guest']['name']);
        $this->assertEquals('Payag 2', $returnedDraft['amenities'][0]['name']);
    }

    /**
     * Test Scenario 4: Colloquial Bisaya Booking Conversation
     */
    public function test_bisaya_colloquial_booking_flow(): void
    {
        $tomorrow = now()->addDay()->toDateString();

        $resp = $this->postJson('/guest-chatbot', [
            'message' => "pa-book mi ugma puhon sa payag 2 duha mi kabuok",
            'history' => [],
            'booking_draft' => [],
        ]);

        $resp->assertOk();
        $draft = $resp->json('booking_draft');
        $this->assertEquals($tomorrow, $draft['startDate']);
        $this->assertEquals('Payag 2', $draft['amenities'][0]['name']);
        $this->assertEquals(2, $draft['guest']['guests_count']);
    }

    /**
     * Test Scenario 5: Rejection of past dates
     * (Verifies that picking yesterday or an expired past date is rejected politely).
     */
    public function test_past_date_rejection(): void
    {
        $yesterday = now()->subDay()->toDateString();

        $resp = $this->postJson('/guest-chatbot', [
            'message' => "I want to book yesterday for Payag 2",
            'history' => [],
            'booking_draft' => [],
        ]);

        $resp->assertOk();
        $draft = $resp->json('booking_draft');
        // Draft must NOT lock in yesterday as a valid startDate!
        $this->assertNull($draft['startDate'] ?? null);
        $this->assertTrue(!empty($draft['pastDateClarification']));
    }

    /**
     * Test Scenario 6: Cancellation of active booking
     */
    public function test_booking_cancellation(): void
    {
        $activeDraft = [
            'active' => true,
            'startDate' => now()->addDay()->toDateString(),
            'amenities' => [['id' => '1', 'name' => 'Payag 2', 'price' => 300.0]],
            'guest' => ['name' => 'John'],
        ];

        $resp = $this->postJson('/guest-chatbot', [
            'message' => "cancel my booking",
            'history' => [],
            'booking_draft' => $activeDraft,
        ]);

        $resp->assertOk();
        $draft = $resp->json('booking_draft');
        $this->assertFalse($draft['active'] ?? false);
        $this->assertEquals('booking_cancelled', $resp->json('booking_action'));
    }

    /**
     * Test Scenario 7: Staff Assistant multilingual query handling
     */
    public function test_staff_chatbot_bisaya_overdue_checkouts(): void
    {
        $staff = StaffAccount::create([
            'name' => 'Staff Brenda User',
            'email' => 'staffbrenda@example.com',
            'password' => bcrypt('password123'),
            'ban_status' => false,
        ]);

        $this->withSession([
            'auth_user' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => 'staff',
            ]
        ]);

        Reservation::create([
            'booker_name' => 'Overdue Guest Maria',
            'email' => 'maria@example.com',
            'phone' => '09123456789',
            'reservation_date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->subDays(1)->toDateString(),
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 2,
            'number_of_guests' => 4,
            'reservation_type' => 'walk-in',
            'status' => 'Checked In',
            'total_amount' => 1000,
            'amount_paid' => 1000,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        // Staff asks in Bisaya
        $resp = $this->postJson('/chatbot', [
            'message' => "kinsa ang overdue checkout karon?",
        ]);

        $resp->assertOk();
        $reply = $resp->json('reply');
        $this->assertStringContainsString('Overdue Guest Maria', $reply);
        $this->assertStringContainsString('OVERDUE', $reply);
    }

    /**
     * Test Scenario 8: Admin Assistant financial overview
     */
    public function test_admin_chatbot_financial_overview(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Admin Boss',
            'email' => 'adminboss@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
            ]
        ]);

        Reservation::create([
            'booker_name' => 'Paid Guest',
            'email' => 'paid@example.com',
            'phone' => '09123456789',
            'reservation_date' => now()->toDateString(),
            'number_of_guests' => 4,
            'total_amount' => 5000,
            'amount_paid' => 2500,
            'remaining_balance' => 2500,
            'payment_status' => 'Partially Paid',
            'reservation_type' => 'online',
            'status' => 'Confirmed',
        ]);

        // Admin asks for sales / revenue
        $resp = $this->postJson('/admin-chatbot', [
            'message' => "pila tanan gross sales ug revenue",
        ]);

        $resp->assertOk();
        $reply = $resp->json('reply');
        $this->assertStringContainsString('Financial', $reply);
        $this->assertStringContainsString('2,500.00', $reply);
    }

    /**
     * Test Scenario 9: Air-conditioned A-House continuity & strict topic focus
     * (Verifies that agreeing to an aircon A-House and confirming the date/slot
     * maintains focus on A-House 2/3 and does NOT dump the generic rates catalog or ask what amenity they want).
     */
    public function test_airconditioned_ahouse_continuity_and_topic_focus(): void
    {
        // Turn 1: Guest selects air-conditioned A-House
        $resp1 = $this->postJson('/guest-chatbot', [
            'message' => "uhm im okay with the airconditioned ahouse",
            'history' => [
                ['role' => 'user', 'content' => "Happy monthsary! What do you recommend for an overnight stay?"],
                ['role' => 'assistant', 'content' => "For a cozy overnight together, I'd suggest an A-House: the regular ones are ₱500/night, while the air-conditioned A-Houses are ₱1,000/night—both include free entrance and pool access for two."],
            ],
            'booking_draft' => [
                'active' => true,
            ],
        ]);

        $resp1->assertOk();
        $reply1 = $resp1->json('reply');
        $draft1 = $resp1->json('booking_draft');

        // Verify draft captured the aircon amenity or tentative selection
        $hasAirconRecorded = !empty($draft1['tentativeCategory']) && stripos($draft1['tentativeCategory'], 'air') !== false
            || (!empty($draft1['amenities']) && stripos($draft1['amenities'][0]['name'] ?? '', 'A-House') !== false)
            || (!empty($draft1['tentativeAmenity']) && stripos($draft1['tentativeAmenity'], 'air') !== false);
        $this->assertTrue($hasAirconRecorded, 'Draft must record air-conditioned A-House tentatively or in amenities');

        // Turn 2: Guest says "yes yes tonight"
        $resp2 = $this->postJson('/guest-chatbot', [
            'message' => "yes yes tonight",
            'history' => [
                ['role' => 'user', 'content' => "uhm im okay with the airconditioned ahouse"],
                ['role' => 'assistant', 'content' => $reply1],
            ],
            'booking_draft' => $draft1,
        ]);

        $resp2->assertOk();
        $reply2 = $resp2->json('reply');
        $draft2 = $resp2->json('booking_draft');

        // Must stay focused on air-conditioned A-House (A-House 2 or 3)
        $this->assertTrue(
            str_contains(strtolower($reply2), 'air-conditioned') ||
            str_contains(strtolower($reply2), 'a-house 2') ||
            str_contains(strtolower($reply2), 'a-house 3') ||
            str_contains(strtolower($reply2), 'a-house'),
            'Reply must maintain topic focus on the air-conditioned A-House'
        );

        // Must NOT dump the generic rates table of all other amenities
        $this->assertStringNotContainsString('Which one catches your eye', $reply2);
        $this->assertStringNotContainsString('Function Hall', $reply2);

        // Must ask for Full Name to continue booking
        $this->assertTrue(
            str_contains(strtolower($reply2), 'full name') ||
            str_contains(strtolower($reply2), 'tibuok ngalan') ||
            str_contains(strtolower($reply2), 'buong pangalan') ||
            str_contains(strtolower($reply2), 'pangalan'),
            'Bot must advance to asking for Full Name'
        );
    }

    /**
     * Test that Bren never auto-selects Function Hall when presenting rates tables or listings.
     */
    public function test_guest_cannot_auto_select_function_hall_from_rates_table(): void
    {
        $resp = $this->postJson('/guest-chatbot', [
            'message' => 'what are your night tour rates?',
            'history' => [],
            'booking_draft' => [
                'active' => false,
                'amenities' => [],
            ],
        ]);

        $resp->assertOk();
        $draft = $resp->json('booking_draft');

        // Verify Function Hall was NOT auto-selected into amenities
        $amenityNames = array_map(fn($a) => $a['name'] ?? '', $draft['amenities'] ?? []);
        $this->assertNotContains('Function Hall', $amenityNames);
        $this->assertEmpty($draft['amenities'] ?? []);

        // Tentative amenities must also not contain Function Hall
        $tentative = $draft['tentativeAmenities'] ?? [];
        $this->assertNotContains('Function Hall', $tentative);
    }

    /**
     * Test suggestion for girlfriend/couple recommends A-House and never claims Function Hall is selected.
     */
    public function test_couple_girlfriend_suggestion_recommends_ahouse_never_function_hall(): void
    {
        $todayStr = now()->toDateString();

        $resp = $this->postJson('/guest-chatbot', [
            'message' => 'can u suggest for my us of my girlfriend',
            'history' => [
                ['role' => 'user', 'content' => 'i want to visit tonight'],
                ['role' => 'assistant', 'content' => "Here are our night rates:\nRegular A-House P500\nFunction Hall P10,000"],
            ],
            'booking_draft' => [
                'active' => true,
                'startDate' => $todayStr,
                'startSlot' => 'Nighttime',
                'amenities' => [],
            ],
        ]);

        $resp->assertOk();
        $reply = $resp->json('reply');
        $draft = $resp->json('booking_draft');

        // Must recommend A-House (Regular or Aircon)
        $this->assertTrue(
            str_contains(strtolower($reply), 'a-house') || str_contains(strtolower($reply), 'ahouse'),
            'Should suggest cozy A-House for couple/girlfriend'
        );

        // Must NEVER claim that Function Hall is currently selected
        $this->assertStringNotContainsString('currently has the Function Hall selected', $reply);
        $this->assertStringNotContainsString('switch it to an A-House', $reply);

        // Draft must NOT have Function Hall selected
        $amenityNames = array_map(fn($a) => $a['name'] ?? '', $draft['amenities'] ?? []);
        $this->assertNotContains('Function Hall', $amenityNames);
    }

    /**
     * Test conversational commands to stop booking, clean draft, clear selected date, and clear amenities.
     */
    public function test_conversational_clean_draft_and_clear_amenities_and_date(): void
    {
        $todayStr = now()->toDateString();
        $initialDraft = [
            'active' => true,
            'startDate' => $todayStr,
            'endDate' => $todayStr,
            'startSlot' => 'Daytime',
            'amenities' => [
                ['id' => '1', 'name' => 'Payag 2', 'price' => 300],
            ],
            'guest' => [
                'name' => 'John Doe',
                'phone' => '09123456789',
            ],
        ];

        // 1. Clean selected date
        $respDate = $this->postJson('/guest-chatbot', [
            'message' => 'clean selected date',
            'booking_draft' => $initialDraft,
        ]);
        $respDate->assertOk();
        $this->assertEquals('update_draft', $respDate->json('booking_action'));
        $draftDate = $respDate->json('booking_draft');
        $this->assertNull($draftDate['startDate']);
        $this->assertCount(1, $draftDate['amenities']);

        // 2. Clean selected amenities
        $respAmenity = $this->postJson('/guest-chatbot', [
            'message' => 'clean selected amenities',
            'booking_draft' => $initialDraft,
        ]);
        $respAmenity->assertOk();
        $this->assertEquals('update_draft', $respAmenity->json('booking_action'));
        $draftAmenity = $respAmenity->json('booking_draft');
        $this->assertEmpty($draftAmenity['amenities']);
        $this->assertEquals($todayStr, $draftAmenity['startDate']);

        // 3. Clean the draft / stop the book
        $respStop = $this->postJson('/guest-chatbot', [
            'message' => 'stop the book and clean the draft',
            'booking_draft' => $initialDraft,
        ]);
        $respStop->assertOk();
        $this->assertEquals('booking_cancelled', $respStop->json('booking_action'));
        $draftStop = $respStop->json('booking_draft');
        $this->assertFalse($draftStop['active']);
        $this->assertNull($draftStop['startDate']);
        $this->assertEmpty($draftStop['amenities']);
    }
}
