<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Customer;
use App\Models\DailyWeatherShiftLog;
use App\Models\Feedback;
use App\Models\ParkActivity;
use App\Models\ParkEvent;
use App\Models\ParkRule;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\ReservationGuest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\OpenAiKeyService;
use App\Services\OpenRouterKeyService;

class GuestChatbotController extends Controller
{
    /**
     * Provide a fresh, clean initial booking draft structure.
     */
    private function getEmptyDraft(): array
    {
        return [
            'active' => false,
            'step' => 'date_slot',
            'tentativeDate' => null,
            'tentativeEndDate' => null,
            'tentativeSlot' => 'Daytime',
            'tentativeEndSlot' => 'Daytime',
            'tentativeStayType' => null,
            'startDate' => null,
            'endDate' => null,
            'startSlot' => 'Daytime',
            'endSlot' => 'Daytime',
            'slot' => 'Daytime',
            'dayCount' => 1,
            'nightCount' => 0,
            'daysSpan' => 1,
            'tentativeAmenity' => null,
            'tentativeAmenities' => null,
            'tentativeCategory' => null,
            'amenities' => [],
            'amenity' => null,
            'guest' => [
                'name' => '',
                'phone' => '',
                'email' => '',
                'guests_count' => null,
            ],
            'payment' => [
                'payment_intent_id' => null,
                'total_amount' => 0,
                'deposit_amount' => 0,
                'remaining_balance' => 0,
                'method' => null,
                'status' => 'pending',
                'reservation_id' => null,
            ],
        ];
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array',
            'history.*.role' => 'required_with:history|string|in:user,assistant',
            'history.*.content' => 'required_with:history|string',
            'model' => 'nullable|string',
            'booking_draft' => 'nullable|array',
        ]);

        $userMessage = trim($request->input('message'));
        $msgLower = strtolower($userMessage);
        $bookingDraft = $request->input('booking_draft', []);

        // Detect booking actions & conversational draft commands
        $bookingAction = null;

        // 1. Full Reset / Cancel / Stop Booking / Clean Draft
        if (preg_match('/\b(?:cancel\s*(?:my\s*)?booking|reset\s+booking|stop\s+(?:the\s+)?book(?:ing)?|exit\s+booking|ayaw\s*na\s*lang|clean\s+(?:the\s+|my\s+)?draft|clean\s+booking|clear\s+(?:the\s+|my\s+)?draft|clear\s+booking|reset\s+draft|start\s+over|delete\s+draft|remove\s+draft|erase\s+draft)\b/i', $msgLower)) {
            $bookingDraft = $this->getEmptyDraft();
            return response()->json([
                'reply' => "No problem! I've cleared your booking draft and cancelled booking mode. How else may I assist you with Hinaguan Nature Park today?",
                'booking_draft' => $bookingDraft,
                'booking_action' => 'booking_cancelled',
            ]);
        } elseif (preg_match('/\b(?:clean\s+(?:selected\s+|the\s+)?date|clear\s+(?:selected\s+|the\s+|my\s+)?date|reset\s+(?:selected\s+|the\s+)?date|remove\s+(?:selected\s+|the\s+)?date|delete\s+(?:the\s+)?date|cancel\s+(?:the\s+)?date|tanggalin\s+ang\s+petsa|tangtanga\s+ang\s+petsa)\b/i', $msgLower)) {
            $bookingDraft['startDate'] = null;
            $bookingDraft['endDate'] = null;
            $bookingDraft['startSlot'] = 'Daytime';
            $bookingDraft['endSlot'] = 'Daytime';
            $bookingDraft['slot'] = 'Daytime';
            $bookingDraft['tentativeDate'] = null;
            $bookingDraft['tentativeEndDate'] = null;
            $bookingDraft['tentativeSlot'] = null;
            $bookingDraft['tentativeEndSlot'] = null;
            $bookingDraft['tentativeStayType'] = null;
            $bookingDraft['step'] = 'date_slot';
            return response()->json([
                'reply' => "I've cleared your selected reservation date. What date would you like to visit instead?",
                'booking_draft' => $bookingDraft,
                'booking_action' => 'update_draft',
            ]);
        } elseif (preg_match('/\b(?:clean\s+(?:selected\s+|the\s+|my\s+)?amenit(?:y|ies)?|clear\s+(?:selected\s+|the\s+|my\s+)?amenit(?:y|ies)?|reset\s+(?:selected\s+|the\s+)?amenit(?:y|ies)?|remove\s+(?:selected\s+|the\s+)?amenit(?:y|ies)?|clean\s+picked\s+amenit(?:y|ies)?|clear\s+picks?|clean\s+picks?|remove\s+function\s*hall|clear\s+function\s*hall|tanggalin\s+ang\s+amenity|tangtanga\s+ang\s+amenity)\b/i', $msgLower)) {
            $bookingDraft['amenities'] = [];
            $bookingDraft['amenity'] = null;
            $bookingDraft['tentativeAmenities'] = null;
            $bookingDraft['tentativeAmenity'] = null;
            $bookingDraft['tentativeCategory'] = null;
            if (!empty($bookingDraft['startDate'])) {
                $bookingDraft['step'] = 'amenity';
            }
            return response()->json([
                'reply' => "I've cleared your selected amenities. Which amenity would you like to reserve instead?",
                'booking_draft' => $bookingDraft,
                'booking_action' => 'update_draft',
            ]);
        } elseif (preg_match('/(?:show\s+(?:me\s+)?(?:what\s+(?:amenit(?:y|ies)?\s+)?i\s+(?:just\s+)?avail|my\s+(?:pick|picks|selection|draft|booking)|the\s+record)|what\s+did\s+i\s+pick|unsa\s+akong\s+gipili|patingin\s+ng\s+pinili\s*ko|view\s+pick)/i', $msgLower)) {
            $bookingAction = 'show_picks';
        }

        // Only activate booking action on explicit command /book
        if (trim($msgLower) === '/book') {
            $bookingAction = 'confirm_booking_prompt';
        }

        $history = $request->input('history', []);
        if (!is_array($history)) {
            $history = [];
        }

        // Secretly enrich booking draft from the user's conversational message and recent history
        $bookingDraft = $this->enrichBookingDraftFromMessage($userMessage, $bookingDraft, $history);

        // Security / Privacy Guardrail: Strictly block confidential sales, company financials, staff accounts
        $sensitiveTerms = [
            'revenue', 'financial', 'sales', 'profit', 'total earned', 'total income',
            'staff account', 'staff password', 'admin password', 'admin account', 'staff list', 'employee list',
            'developer', 'coding', 'programming', 'politics', 'religion'
        ];

        foreach ($sensitiveTerms as $term) {
            if (str_contains($msgLower, $term)) {
                return response()->json([
                    'reply' => "I am the Hinaguan Guest Assistant. For privacy and security reasons, I can only assist with park amenities, rates, availability, and booking procedures. For special inquiries, please contact our park front desk directly.",
                    'booking_action' => $bookingAction,
                    'booking_draft' => $bookingDraft,
                ]);
            }
        }

        $requestedModel = $request->input('model', 'gpt-6-luna');
        if (empty($requestedModel)) {
            $requestedModel = 'gpt-6-luna';
        }

        $openAiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');
        $openRouterKey = config('services.openrouter.key') ?: env('OPENROUTER_API_KEY');

        if (!$openAiKey && !$openRouterKey) {
            $openAiStatus = OpenAiKeyService::getStatus();
            $keyStatus = OpenRouterKeyService::getStatus();
            $fallback = $this->generateDirectFallbackResponse($userMessage, $bookingDraft);
            if (!empty($fallback)) {
                $this->proactivelyExtractProposedBookingDetails($fallback, $bookingDraft);
            }
            return response()->json([
                'reply' => !empty($fallback) ? $fallback : 'The chatbot is currently offline. Please call park hotline 0917 861 8383.',
                'active_model' => 'local/fallback',
                'booking_action' => $bookingAction,
                'booking_draft' => $bookingDraft,
                'openai_status' => $openAiStatus,
                'openrouter_status' => $keyStatus,
            ]);
        }

        $now = now();
        $todayReadable = $now->format('F j, Y');
        $currentYear = $now->year;
        $nextYear = $now->year + 1;
        $guestContext = $this->getGuestContext($userMessage, $bookingDraft);

        $systemPrompt = "CRITICAL DIRECTIVE: You are Bren (also known as Bren AI Assistant), the warm, friendly, hospitable, and attentive AI concierge for Hinaguan Nature Park in Jasaan, Misamis Oriental, speaking directly to a human guest.\n"
            . "- DO NOT output chain-of-thought, internal analysis, reasoning steps, planning, outlines, or scratchpads.\n"
            . "- NEVER write '1. Analyze User Input:', '2. Check Booking Procedure Rules:', 'Let\'s structure the response:', 'User says:', or numbered thinking steps.\n"
            . "- NEVER output internal monologue or draft notes. Speak DIRECTLY and warmly to the guest from your very first word!\n\n"
            . "CRITICAL OPERATIONAL RULES (STRICTLY ENFORCED):\n"
            . "1. STRICT DATABASE-ONLY KNOWLEDGE & SCOPE (12 PERMITTED TABLES ONLY):\n"
            . "   - You ONLY search and retrieve information from the LIVE DATABASE CONTEXT below across these 12 tables:\n"
            . "     1) amenities: ID, amenities_name, description, daytime_price, nighttime_price, minimum_capacity, maximum_capacity, status.\n"
            . "     2) amenities_benefits: 1-to-1 with amenities; free_entrance, free_pool, is_aircon.\n"
            . "     3) customers: ID, first_name, middle_name, last_name, age, gender, phone, email.\n"
            . "     4) daily_weather_shift_logs: ID, log_date, shift, weather_condition, temperature_celsius, precipitation_probability, notes.\n"
            . "     5) feedbacks: ID, stars, description, full_name, is_anonymous, is_shown.\n"
            . "     6) park_activities: ID, activity, description, image.\n"
            . "     7) park_events: ID, title, event, date, day, time, is_active.\n"
            . "     8) park_rules: ID, rule_name, rule_descriptions.\n"
            . "     9) park_settings: ID, park_status, opening_time, closing_time, daytime_start, daytime_end, nighttime_start, nighttime_end, fees, contact.\n"
            . "     10) reservations: ID, booker_name, phone, email, reservation_type, reservation_date, start_slot, end_date, end_slot, status, total_amount, amount_paid, remaining_balance, payment_status, number_of_guests.\n"
            . "     11) reservation_amenities: ID, reservation_id, amenity_id, start_date, start_slot, end_date, end_slot, price, status.\n"
            . "     12) reservation_guests: ID, reservation_id, customer_id, is_primary_guest, has_pool_access, checked_out_at.\n"
            . "   - STRICT SECURITY GUARDRAIL: You have NO access to employee/staff accounts, admin accounts, sessions, migrations, or server logs. Never discuss confidential sales or internal credentials.\n"
            . "   - STRICT RELEVANCE: You ONLY answer topics directly related to Hinaguan Nature Park. If a user asks about anything unrelated (e.g. general knowledge, math, homework, coding, politics, other businesses), politely decline in 1 short sentence and redirect them to ask about Hinaguan Nature Park.\n"
            . "2. GO STRAIGHT TO THE POINT & TALK LIKE A REAL HUMAN CONCIERGE ('TALKINGGG!'):\n"
            . "   - Speak warmly, conversationally, and engagingly, as if you are welcoming a guest directly at our front desk.\n"
            . "   - Answer directly in 1 to 3 friendly, helpful sentences.\n"
            . "   - STRICT GUARDRAIL — ZERO INTERNAL MONOLOGUE OR REASONING LEAKAGE:\n"
            . "     * NEVER output internal reasoning, outlines, numbered analysis, scratchpads, or draft prefixes.\n"
            . "     * NEVER output lines or words like 'Analyze User Input', 'Check Booking Procedure', 'Let\'s structure', 'User says', 'need handle', 'we must', 'we need', 'we should', 'Section 4 says', 'The draft says', 'Could mean', 'DO NOT output', 'We can say', or 'operational rules'.\n"
            . "     * ONLY speak the final direct, warm words intended for the guest!\n"
            . "   - NEVER OUTPUT SAFETY EVALUATIONS OR MODERATION LABELS: Never output lines like 'User Safety:', 'Response Safety:', 'Safety: safe', or meta moderation checks. Answer the guest directly.\n"
            . "3. MATCH THE USER'S LANGUAGE & FORGIVE TYPOS/GRAMMAR:\n"
            . "   - Seamlessly understand misspelled words, typographical errors, phonetic spelling, and broken grammar (e.g. 'cotag', 'pyag', 'boking', 'chek in', 'magkano po ahouse', 'pila bayranan', 'resrvation', 'unsaon pag adto', 'paano pumunta'). Deduce intent directly without correcting the user.\n"
            . "   - If the user asks in Bisaya / Cebuano (e.g., 'tagpila', 'pila', 'asa dapit', 'naay pool', 'nindot', 'suba', 'init', 'uwan', 'pila bayad', 'unsaon pag book', 'unsaon pag adto'), reply warmly and naturally in Bisaya!\n"
            . "   - If the user asks in Tagalog / Filipino / Taglish (e.g., 'magkano', 'meron ba', 'saan', 'ano po rates', 'pwede ba', 'paano mag book', 'paano pumunta'), reply warmly and naturally in Tagalog!\n"
            . "   - If the user asks in English, reply in English!\n"
            . "4. PROCEDURAL BOOKING ASSISTANCE (NO ROBOTIC FAQ TUTORIALS):\n"
            . "   - NEVER output a dry, numbered FAQ list instructing users how to click buttons on the website (e.g., NEVER say '1. Click Book Now... 2. Select your visit date... 3. Choose your session slot...').\n"
            . "   - When a guest asks how to book or expresses interest in reserving, TALK DIRECTLY to them! Explain warmly that they can easily reserve right here in this chat (or on our website), and immediately invite them to start by asking for their target date.\n"
            . "   - MANDATORY POLICY REMINDER: Remind them that securing a reservation requires a 50% downpayment which is STRICTLY NON-REFUNDABLE (rescheduling allowed with advance notice).\n"
            . "5. HOW TO GO / DIRECTIONS TO THE PARK (LANDMARKS & NAVIGATION):\n"
            . "   - Location: Hinaguan Nature Park is located in Barangay Solana, Jasaan, Misamis Oriental.\n"
            . "   - Landmark: 'Spring View Resort' (a popular spring-water swimming pool resort in Solana, Jasaan, right after Solana Bridge).\n"
            . "   - Commute / Driving Directions:\n"
            . "     * From Cagayan de Oro City or nearby towns, take any bus, van, or jeep bound for Jasaan or Balingasag (e.g. from Agora Bus Terminal).\n"
            . "     * Tell the driver or conductor to drop you off in Solana, Jasaan, right by the turn-off near Spring View Resort (after Solana Bridge).\n"
            . "     * From the highway near Spring View Resort, enter and follow the inner road heading inland toward the river/hills.\n"
            . "     * You can take a local 'habal-habal' (motorcycle taxi) directly to Hinaguan Nature Park, or drive through the inner road following Google Maps or Waze (searchable as 'Hinaguan Nature Park' or 'Spring View Resort').\n"
            . "6. AMENITY INCLUSIONS (NEVER HALLUCINATE):\n"
            . "   - COTTAGES (₱200) and PAYAGS (₱300): DO NOT include free entrance or free pool access. Regular entrance (₱20 daytime adult) and pool access (₱50 per person) are separate fees. NEVER claim cottages or payags include free entrance or pool!\n"
            . "   - A-HOUSES (₱300 day / ₱500 night; air-conditioned A-House 2 & 3 are ₱700 day / ₱1,000 night): Includes FREE entrance and FREE swimming pool access for 2 guests.\n"
            . "   - FUNCTION HALL: Includes FREE entrance and FREE swimming pool access for the booked group (15 to 50+ pax).\n"
            . "7. TODAY'S WEATHER-AWARE RECOMMENDATIONS:\n"
            . "   - Check [TODAY'S WEATHER LOG (FROM DATABASE)].\n"
            . "   - If rainy, overcast, or high chance of rain: Recommend weather-sheltered spots (like cozy A-Houses, Function Hall, or covered cottages/payags) and mention staying dry.\n"
            . "   - If sunny, warm, or clear: Recommend riverside payags (Payag 2 or 6), breezy mountain payag (Payag 3), shaded garden payag (Payag 5), or taking a refreshing swim in the pool!\n"
            . "8. GENERAL PARK POLICIES:\n"
            . "   - Outside food allowed with NO corkage fee; free grilling stations available.\n"
            . "   - Pets allowed on leash; free parking on site.\n"
            . "9. AMENITY AVAILABILITY & DATES (CRITICAL):\n"
            . "   - When a guest asks about the availability of an amenity or what dates it is available (e.g., 'what date is payag 4 available?', 'is cottage 1 available?', 'when can I book payag 4?', 'available dates'):\n"
            . "   - Carefully review section '[4. LIVE AMENITY AVAILABILITY & UPCOMING RESERVED DATES (FROM DATABASE)]'.\n"
            . "   - If the amenity has upcoming reservations, explicitly state which dates and slots are reserved, and clarify that ALL OTHER upcoming dates are open and available!\n"
            . "   - If it has no upcoming reservations, confirm that it is 100% available for booking on any date.\n"
            . "   - Always warmly offer to book it for them right in the chat.\n"
            . "10. CONVERSATIONAL BOOKING PROCEDURE (MAKE SURE BEFORE RECORDING DATES OR AMENITIES):\n"
            . "   When a guest is booking or inquiring about reserving, guide them carefully through these steps, ALWAYS MAKING SURE they are confident before recording dates or amenities:\n"
            . "   * STEP 1 — DATE & STAY TYPE (VALIDATE AGAINST TODAY'S DATE: {$todayReadable}):\n"
            . "     - CRITICAL DATE GUARDRAIL (STRICTLY NO PAST DATES):\n"
            . "       Today's date is {$todayReadable}. Hinaguan Nature Park strictly DOES NOT accept bookings for past dates!\n"
            . "       * PREVIOUSLY RECORDED DATE IN THE PAST (CRITICAL):\n"
            . "         If the guest returns or continues a conversation where their previously selected/recorded date has ALREADY PASSED (e.g. they chose November 23 earlier, but today is November 24 onwards):\n"
            . "         STRICTLY DEMAND THAT CONTINUING WITH THIS DATE IS IMPOSSIBLE! Under NO circumstances may you confirm, continue, or check availability for that expired date. State clearly, warmly, and firmly that their recorded date has already passed and reservations for past dates are impossible. Ask what upcoming date (from today {$todayReadable} onwards) they would like to visit!\n"
            . "       * If the guest proposes or mentions a date whose month/day has already passed this year (e.g. 'sept 25' or 'august 10' when today is November {$currentYear}):\n"
            . "         NEVER accept it for {$currentYear} or say it sounds like a wonderful date!\n"
            . "         Instead, warmly clarify if they meant NEXT YEAR ({$nextYear}):\n"
            . "         'September 25th for this year ({$currentYear}) has already passed! Did you mean September 25, {$nextYear} (next year), or were you thinking of another upcoming date? Also, will that be for Daytime (8:00 AM – 5:00 PM) or Nighttime (6:00 PM – 8:00 AM)?'\n"
            . "       * If the guest confirms next year (e.g., 'yes', 'next year', 'yes next year', 'yes {$nextYear}'):\n"
            . "         Accept the date for {$nextYear} (next year) and proceed with booking!\n"
            . "       * If they explicitly provide an older past year (e.g., 2024 or 2025) or say 'yesterday', politely inform them: 'That date has already passed. We only accept reservations for today onwards! What upcoming date would you like to visit?'\n"
            . "     - When a guest mentions, asks about, or proposes an upcoming date (e.g., 'what about nov 20', 'is tomorrow good?', 'december 3'):\n"
            . "       DO NOT assume the date is finalized or locked in immediately!\n"
            . "       Warmly acknowledge that it is a great date, and MAKE SURE:\n"
            . "       '[Date] sounds like a wonderful date to visit Hinaguan Nature Park! 🌿 Are you sure you would like to pick [Date] for your visit? Also, would you prefer a **Day Tour**, **Night Tour**, or **24-Hour Stay**? ☀️🌙 I\'ll help you find the perfect amenity for your visit! 😊'\n"
            . "       DO NOT include '[VIEW_AMENITIES_BUTTON]'!\n"
            . "     - When the guest confirms their stay / tour type (e.g. 'Night', 'Day', 'Night Tour', 'Day Tour', 'Nighttime', 'Daytime'):\n"
            . "       * CRITICAL TOPIC FOCUS & AMENITY CONTINUITY RULE:\n"
            . "         If an amenity was ALREADY discussed, proposed, or selected in the conversation (e.g., 'airconditioned ahouse', 'aircon a-house', 'a-house 2', 'payag 2', 'cottage'):\n"
            . "         STRICTLY DO NOT DUMP THE RATES TABLE OF OTHER AMENITIES (Cottages, Payags, Function Hall)!\n"
            . "         STRICTLY DO NOT ASK 'Which one catches your eye?' or ask what amenity they want again!\n"
            . "         STAY 100% FOCUSED ON THE AMENITY BEING DISCUSSED! Acknowledge their date and tour type FOR THAT AMENITY:\n"
            . "         - For Air-conditioned A-Houses (which are A-House 2 and A-House 3 at ₱1,000 night / ₱700 day with free entrance & pool access for 2):\n"
            . "           'Wonderful! 🌙💚 A Night Tour tonight, [Date], for the **Air-conditioned A-House** (₱1,000, includes free entrance & pool access for 2) sounds lovely! Our air-conditioned units are **A-House 2** and **A-House 3**. Shall I lock in **A-House 2** for you, or do you have a preference between A-House 2 and A-House 3? Also, to prepare your digital reservation pass, may I have your **Full Name**, please? 😊'\n"
            . "         - If the specific amenity unit is already locked in (e.g. A-House 2, Payag 2):\n"
            . "           Warmly confirm it and immediately ask for their **Full Name**!\n"
            . "       * ONLY IF NO AMENITY HAS BEEN DISCUSSED OR CHOSEN YET:\n"
            . "         Acknowledge warmly and present the standard rates table:\n"
            . "         'Wonderful! 🌙💚 A **Night Tour on [Date]** sounds like a lovely way to relax at Hinaguan Nature Park! 🌿✨\n\nHere are our amenities and their night rates:\n\n| Amenity | Night Rate |\n| :--- | :--- |\n| Regular A-House (6 rooms) | ₱500 |\n| Aircon A-House (2 rooms) | ₱1,000 |\n| Cottage (6 units) | ₱200 |\n| Payag (6 units) | ₱300 |\n| Function Hall (1 unit) | ₱10,000 |\n\nPlease note that these are our standard rates. Actual availability still needs to be verified for [Date].'\n"
            . "         (If Day Tour, use Day Rate: Regular A-House ₱300, Aircon A-House ₱700, Cottage ₱200, Payag ₱300, Function Hall ₱5,000)\n"
            . "         Ask which amenity catches their eye or if they want you to check live availability!\n"
            . "   * STEP 2 — AMENITY INQUIRY & AVAILABILITY (MAKE SURE BEFORE RECORDING, SUPPORT MULTIPLE AMENITIES):\n"
            . "     - The guest may mention ONE or MULTIPLE amenities in a single message (e.g., 'uhm i want ahouse 2 and payag 3', 'cottage 1 and cottage 2', 'i think its better to pick ahouse 1 right?', 'what about ahouse 1?'):\n"
            . "       1. DO NOT immediately record amenities if the guest is merely asking, wondering, or stating tentative interest!\n"
            . "       2. Check section '[4. LIVE AMENITY AVAILABILITY & UPCOMING RESERVED DATES (FROM DATABASE)]' for ALL mentioned amenities.\n"
            . "       3. Inform them directly if each specific amenity is available or reserved on that date, its rate, and key inclusions.\n"
            . "       4. When MULTIPLE amenities are mentioned (e.g., 'uhm i want ahouse 2 and payag 3'), state the live availability of BOTH/ALL, individual rates and features, and calculate the combined total and 50% required downpayment!\n"
            . "       5. Ask if they are sure they want to reserve both/all of them, or if they would like to see all available amenities: e.g. 'Both A-House 2 (₱700.00) and Payag 3 (₱300.00) are available for November 25, 2026 (Daytime)! Combined total is ₱1,000.00 (50% downpayment: ₱500.00). Are you sure you would like to reserve both A-House 2 and Payag 3, or would you like me to show you all the available amenities for that date?'\n"
            . "       6. CRITICAL BUTTON RULE: DO NOT include the '[VIEW_AMENITIES_BUTTON]' token until the guest explicitly AGREES or asks to see all available amenities (e.g., 'yes show me', 'sure', 'show all amenities', 'pakita', 'yes please', 'view available')! Only output '[VIEW_AMENITIES_BUTTON]' when they agree to view them!\n"
            . "     - If the guest agrees or asks to view all available amenities (e.g., 'yes show me', 'sure', 'pakita', 'yes please', 'view available', 'show all amenities'):\n"
            . "       Respond: 'Here are all the available amenities for [Date] [Slot]! Tap the button below to browse photos, capacity, and rates: [VIEW_AMENITIES_BUTTON]'\n"
            . "     - Only when the guest explicitly confirms they are sure about the amenity/amenities (e.g., 'yes', 'sure', 'reserve both', 'yes I\'ll take them', 'let\'s go with A-House 2 and Payag 3', 'confirm'):\n"
            . "       Lock in ALL selected amenities and proceed to Step 3!\n"
            . "   * STEP 3 — PRIMARY BOOKER DETAILS (STRICTLY CONVERSATIONAL — ASK ONE BY ONE!):\n"
            . "     - DO NOT ask for everything all at once! Collect the guest\'s details ONE BY ONE in natural conversation:\n"
            . "       1) Sub-step 1 (Full Name):\n"
            . "          When the amenity choice is confirmed, acknowledge the amenity warmly and ask ONLY for their **Full Name**:\n"
            . "          e.g., 'Awesome! A-House 2 (₱1,000.00) is locked in for your visit on December 3, 2026 – Night Tour! 🌿💚 To prepare your digital reservation pass, may I have your **Full Name**, please? 😊'\n"
            . "       2) Sub-step 2 (Mobile Number):\n"
            . "          Once the guest provides their name (e.g. 'Jesrail Aulestia'), warmly acknowledge:\n"
            . "          'Thank you, Jesrail Aulestia! 🌿💚 I\\'ve added your name to your booking details for December 3, 2026 – Night Tour. 🌙🏡 Now, may I have your **mobile contact number**, please? 📱😊 We\\'ll use it to send important updates about your reservation.'\n"
            . "       3) Sub-step 3 (Email Address):\n"
            . "          Once the mobile number is provided (e.g. '09954102458'), warmly acknowledge:\n"
            . "          'Thank you! 🌿💚 I\\'ve added **09954102458** as your contact number. We\\'re almost done with your booking details! 😊 May I have your **email address**, please? 📧 We\\'ll use it to send your booking confirmation and receipt once your reservation is confirmed.'\n"
            . "       4) Sub-step 4 (Number of Guests / Pax):\n"
            . "          Once the email is provided, warmly acknowledge:\n"
            . "          'Thank you! 🌿💚 I\\'ve added **jesrail@example.com** to your booking details. We\\'re almost done with your booking details! 😊 How many **guests** in total will be visiting with you? 👥 (Maximum capacity for your selected amenities: 2 pax)'\n"
            . "       - NOTE: If the guest happens to provide multiple details in a single message, acknowledge them warmly and ask only for the next missing piece!\n"
            . "   * STEP 4 — SUMMARY & 50% DOWNPAYMENT (ONLY WHEN ALL 4 DETAILS ARE PROVIDED):\n"
            . "     - Once Full Name, Mobile Number, Email Address, and Number of Guests are all collected, present a clear, friendly summary:\n"
            . "       'Thank you so much, Jesrail Aulestia! 🌿💚 Here is the summary of your reservation:\n\n• 🛖 Amenity: **A-House 2**\n• 📅 Schedule: **December 3, 2026 – Night Tour**\n• 👤 Primary Guest: **Jesrail Aulestia** (09954102458, jesrail@example.com)\n• 👥 Number of Guests: **2 pax**\n• 💵 Total Amount: **₱1,000.00**\n• 💳 Required 50% Downpayment: **₱500.00** (Strictly Non-Refundable)\n• ⏳ Remaining Balance: **₱500.00** (payable upon check-in)\n\nWhenever you\'re ready, tap below to settle the 50% downpayment to finalize your reservation! 😊\n[PROCEED_PAYMENT_BUTTON]'\n"
            . "11. HANDLING ADDING AMENITIES, SWITCHING/SWAPPING AMENITIES, OR REMOVING AMENITIES AT ANY STAGE:\n"
            . "   - Guests are completely free to change their mind, switch/swap amenities, add more amenities, or remove amenities at ANY point during the booking conversation!\n"
            . "   - SWAPPING / CHANGING ONE AMENITY INTO ANOTHER (e.g. 'change payag 6 into payag 4', 'replace payag 6 with payag 4'):\n"
            . "     * Check Section 4 or the Active Draft section for the live availability of the replacement amenity on their schedule.\n"
            . "     * Warmly inform the guest that the replacement amenity is available on their date and session slot!\n"
            . "     * Confirm that the old amenity is replaced with the new one, and list all their current picked amenities and the updated total amount!\n"
            . "     * STRICTLY DO NOT say 'your selection already includes [New Amenity]' or 'would leave it selected twice'! The change is already applied cleanly.\n"
            . "   - CRITICAL PERSISTENCE RULE: NEVER argue with the guest, NEVER say 'the active draft still shows [Old Amenity]', and NEVER say 'I haven't confirmed [Amenity] with you so I won't add them'. If the guest says they confirmed an amenity or asks to remove/switch/keep amenities (e.g. 'no Function Hall', 'keep only Payag 2 and Payag 6', 'switch to Payag 2 and 6'), IMMEDIATELY honor and reflect their choice warmly without question or dispute!\n"
            . "   - When an amenity is added, switched, or removed, KEEP all other collected details (dates, session slot, full name, mobile number, email, headcount) intact, update the total and 50% downpayment, and proceed to the next step!\n"
            . "   - If the guest already provided their name, DO NOT ask for their name again; continue to their phone number, email, or summary!\n"
            . "   - If the guest says 'I want to change something before proceeding':\n"
            . "     Ask warmly what they would like to adjust (their date, session slot, or amenity choices).\n"
            . "   - If the guest confirms adding (e.g. 'add it', 'yes add cottage 2', 'add cottage 2'):\n"
            . "     Acknowledge that it's added, state the updated total and 50% downpayment, and ask if they are ready to proceed to the next step or payment!\n"
            . "   - If the guest confirms switching or removing (e.g. 'switch to cottage 2', 'change to cottage 2', 'no function hall'):\n"
            . "     Acknowledge the change warmly, state the updated total and 50% downpayment, and proceed seamlessly!\n\n"
            . "12. CRITICAL FUNCTION HALL & GROUP SIZE GUARDRAILS:\n"
            . "   - FUNCTION HALL IS STRICTLY FOR LARGE GATHERINGS (15 to 50+ guests at ₱5,000 day / ₱10,000 night)!\n"
            . "   - NEVER automatically select, assign, or assume Function Hall is in the booking unless the guest EXPLICITLY requested it by name or has 15+ guests!\n"
            . "   - If the guest is a couple, mentions a girlfriend/boyfriend/partner/wife/husband, or has 1 to 4 guests, NEVER say or assume Function Hall is selected! Instead, recommend cozy A-Houses (Regular ₱300 day / ₱500 night, Aircon ₱700 day / ₱1,000 night) or bamboo Payags/Cottages.\n"
            . "   - Before recording any amenity into a booking, make sure the guest has explicitly agreed to or chosen it.\n\n"
            . "=== LIVE PARK & DATABASE CONTEXT ===\n"
            . $guestContext;

        $messagesPayload = [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ]
        ];

        $history = $request->input('history', []);
        if (is_array($history) && !empty($history)) {
            $recentHistory = array_slice($history, -4);
            foreach ($recentHistory as $turn) {
                if (!empty($turn['content']) && in_array($turn['role'], ['user', 'assistant'])) {
                    // Safeguard against context poisoning: never feed safety checks or confused loops back to the model
                    if (preg_match('/(?:user\s+safety|response\s+safety)/i', $turn['content']) || str_contains($turn['content'], "im confuse can you state it again")) {
                        continue;
                    }
                    $messagesPayload[] = [
                        'role' => $turn['role'],
                        'content' => $turn['content']
                    ];
                }
            }
        }

        $messagesPayload[] = [
            'role' => 'user',
            'content' => $userMessage
        ];

        $openAiModel = config('services.openai.model') ?: env('OPENAI_MODEL', 'gpt-6-luna');
        $openAiBaseUrl = rtrim(config('services.openai.base_url') ?: env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/');

        $rawReply = '';
        $activeModel = null;
        $fallbackReason = null;

        $isManuallySelectedOpenAi = $request->filled('model') && ($requestedModel === 'gpt-6-luna');
        $shouldTryOpenAi = ($requestedModel === 'gpt-6-luna' || str_contains($requestedModel, 'gpt-6') || str_contains($requestedModel, 'openai') || $requestedModel === 'auto');

        // If the user manually selected OpenAI GPT-6 Luna, but it has 0 credits:
        // Strictly prevent silent fallback so the user is immediately informed!
        if ($isManuallySelectedOpenAi && !OpenAiKeyService::isAvailable()) {
            $keyStatus = OpenAiKeyService::getStatus();
            return response()->json([
                'reply' => "⚠️ **OpenAI GPT-6 Luna** cannot be used: You have 0 credits remaining on your OpenAI account (" . ($keyStatus['message'] ?? 'Credit balance exhausted') . ").\n\nPlease add credits at [OpenAI Billing](https://platform.openai.com/billing) or switch the AI model to **OpenRouter Free (Backup)**.",
                'active_model' => 'none',
                'fallback_reason' => 'OpenAI GPT-6 Luna credits are 0. Auto-fallback disabled for manual model selection.',
                'booking_action' => null,
                'booking_draft' => $bookingDraft,
                'openai_status' => $keyStatus,
                'openrouter_status' => OpenRouterKeyService::getStatus(),
            ]);
        }

        // 1. Primary Model: OpenAI GPT-6 Luna
        if ($shouldTryOpenAi && !empty($openAiKey) && OpenAiKeyService::isAvailable()) {
            try {
                $openAiPayload = [
                    'model' => $openAiModel,
                    'messages' => $messagesPayload,
                    'max_completion_tokens' => 1200,
                ];
                if (!str_contains($openAiModel, 'gpt-6') && !str_contains($openAiModel, 'o1') && !str_contains($openAiModel, 'o3')) {
                    $openAiPayload['temperature'] = 0.3;
                }

                $openAiResponse = Http::timeout(25)->withHeaders([
                    'Authorization' => 'Bearer ' . $openAiKey,
                    'Content-Type' => 'application/json',
                ])->post("{$openAiBaseUrl}/chat/completions", $openAiPayload);

                if ($openAiResponse->successful()) {
                    OpenAiKeyService::markActive();
                    $data = $openAiResponse->json();
                    $msgObj = $data['choices'][0]['message'] ?? [];
                    $candidate = trim($msgObj['content'] ?? '');
                    if (!empty($candidate)) {
                        $rawReply = $candidate;
                        $activeModel = 'gpt-6-luna';
                    }
                } else {
                    $openAiStatus = OpenAiKeyService::markFromCompletionResponse($openAiResponse->status(), $openAiResponse->body());
                    Log::warning("Guest Chatbot OpenAI Error (HTTP {$openAiResponse->status()}): " . $openAiResponse->body());
                    if ($isManuallySelectedOpenAi) {
                        return response()->json([
                            'reply' => "⚠️ **OpenAI GPT-6 Luna** cannot be used: You have 0 credits remaining on your OpenAI account (" . ($openAiStatus['message'] ?? 'Credit balance exhausted') . ").\n\nPlease add credits at [OpenAI Billing](https://platform.openai.com/billing) or switch the AI model to **OpenRouter Free (Backup)**.",
                            'active_model' => 'none',
                            'fallback_reason' => 'OpenAI GPT-6 Luna credits are 0.',
                            'booking_action' => null,
                            'booking_draft' => $bookingDraft,
                            'openai_status' => $openAiStatus,
                            'openrouter_status' => OpenRouterKeyService::getStatus(),
                        ]);
                    }
                    $fallbackReason = 'OpenAI GPT-6 Luna credits are 0 or unavailable; automatically switched to OpenRouter Free.';
                }
            } catch (\Exception $e) {
                Log::warning('Guest Chatbot OpenAI Exception: ' . $e->getMessage());
                if ($isManuallySelectedOpenAi) {
                    return response()->json([
                        'reply' => "⚠️ **OpenAI GPT-6 Luna** connection failed: " . $e->getMessage() . ".\n\nPlease switch to **OpenRouter Free (Backup)** in the AI Model selector.",
                        'active_model' => 'none',
                        'fallback_reason' => 'OpenAI connection error.',
                        'booking_action' => null,
                        'booking_draft' => $bookingDraft,
                        'openai_status' => OpenAiKeyService::getStatus(),
                        'openrouter_status' => OpenRouterKeyService::getStatus(),
                    ]);
                }
                $fallbackReason = 'OpenAI connection error; automatically switched to OpenRouter Free.';
            }
        } elseif ($shouldTryOpenAi && !$isManuallySelectedOpenAi) {
            $fallbackReason = 'OpenAI GPT-6 Luna key is missing, expired, or credit is 0; automatically using OpenRouter Free backup.';
        }

        // 2. Backup Model: OpenRouter Free
        if (empty($rawReply) && !empty($openRouterKey)) {
            try {
                $requestPayload = [
                    'messages' => $messagesPayload,
                    'max_tokens' => 1200,
                    'temperature' => 0.3,
                    'models' => [
                        'recursive/flint-v1.0:free',
                        'liquid/lfm-2.5-2.6b:free',
                        'openrouter/free',
                    ],
                ];

                $response = Http::timeout(15)->withHeaders([
                    'Authorization' => 'Bearer ' . $openRouterKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => request()->getHttpHost(),
                    'X-Title' => 'Hinaguan Nature Park Guest Portal',
                ])->post("https://openrouter.ai/api/v1/chat/completions", $requestPayload);

                if ($response->successful()) {
                    OpenRouterKeyService::markActive();
                    $data = $response->json();
                    $msgObj = $data['choices'][0]['message'] ?? [];
                    $candidate = trim($msgObj['content'] ?? '');

                    if (!empty($candidate)) {
                        $rawReply = $candidate;
                        $activeModel = 'openrouter/free';
                    }
                } else {
                    $keyStatus = OpenRouterKeyService::markFromCompletionResponse($response->status(), $response->body());
                    Log::warning('Guest Chatbot OpenRouter Error (engaging intelligent fallback): ' . $response->body());
                }
            } catch (\Exception $e) {
                Log::warning('Guest Chatbot OpenRouter Exception: ' . $e->getMessage());
            }
        }

        // 3. Deliver Output or Local Intelligent Concierge Engine
        if (!empty($rawReply)) {
            $reply = $this->cleanChatbotReply($rawReply, $userMessage, $bookingDraft);
            $this->proactivelyExtractProposedBookingDetails($reply, $bookingDraft);
            return response()->json([
                'reply' => $reply,
                'active_model' => $activeModel,
                'fallback_reason' => $fallbackReason,
                'booking_action' => $bookingAction,
                'booking_draft' => $bookingDraft,
                'openai_status' => OpenAiKeyService::getStatus(),
                'openrouter_status' => OpenRouterKeyService::getStatus(),
            ]);
        }

        // Local Intelligent Fallback
        $keyStatus = OpenRouterKeyService::getStatus();
        $fallback = $this->generateDirectFallbackResponse($userMessage, $bookingDraft);
        if (!empty($fallback)) {
            $this->proactivelyExtractProposedBookingDetails($fallback, $bookingDraft);
        }
        return response()->json([
            'reply' => !empty($fallback) ? $fallback : 'The assistant is temporarily unavailable. Please call 0917 861 8383.',
            'active_model' => 'local/fallback',
            'fallback_reason' => $fallbackReason ?: 'External cloud LLMs unavailable; local intelligence engine active.',
            'booking_action' => $bookingAction,
            'booking_draft' => $bookingDraft,
            'openai_status' => OpenAiKeyService::getStatus(),
            'openrouter_status' => $keyStatus,
        ]);
    }

    /**
     * Clean raw AI response to strip thinking processes, reasoning tags, numbered scratchpad steps, and draft labels.
     */
    private function cleanChatbotReply(string $reply, string $userMessage = '', ?array $bookingDraft = null): string
    {
        $confusedFallback = "I'm sorry, i couldnt understand and im confuse can you state it again";

        if (empty(trim($reply))) {
            return !empty($userMessage) ? $this->generateDirectFallbackResponse($userMessage, $bookingDraft) : $confusedFallback;
        }

        $text = trim($reply);

        // Check for safety evaluation/guardrail responses (e.g. "User Safety: safe\nResponse Safety: safe")
        if (preg_match('/(?:user\s+safety|response\s+safety)/i', $reply) || preg_match('/(?:user\s+safety|response\s+safety)/i', $text)) {
            $strippedSafety = trim(preg_replace('/^(?:user|response)\s*safety\s*:\s*[^\r\n]*/im', '', $text));
            if (empty($strippedSafety) || preg_match('/(?:user\s+safety|response\s+safety)/i', $strippedSafety)) {
                return !empty($userMessage) ? $this->generateDirectFallbackResponse($userMessage, $bookingDraft) : $confusedFallback;
            }
            $text = $strippedSafety;
        }

        // 1. Strip XML-like thinking/reasoning tags (<think>...</think>, <thought>...</thought>, etc.)
        $text = preg_replace('/<(?:think|thought|reasoning|scratchpad|analysis|internal)>.*?<\/(?:think|thought|reasoning|scratchpad|analysis|internal)>/is', '', $text);
        $text = preg_replace('/<(?:think|thought|reasoning|scratchpad|analysis|internal)>.*$/is', '', $text);

        // 1.5 Strict guardrail against internal monologue / thinking leakage
        $metaPattern = '/\b(?:Analyze\s+User\s+Input|Check\s+(?:Booking\s+)?Procedure|Booking\s+Procedure\s+Rules|Let\'?s\s+structure(?:\s+the\s+response)?|User\s+(?:says|said|responded|is\s+asking|jumped|asks)|Context\s+from\s+earlier|According\s+to\s+the\s+rules|the\s+rule(?:s)?\s+say|Let\'?s\s+review|Looking\s+back\s*:|I\s+need\s+to\s+follow|I\s+should\s+(?:gently\s+)?(?:remind|ask|verify|check)|This\s+seems\s+like|wasn\'?t\s+explicitly\s+confirmed|Acknowledge\s+.*selection|Check\s+availability\s*:|Mention\s+rate\s+and\s+inclusions|Then\s+proceed\s+to\s+booking|But\s+wait,?\s+the\s+rules|Step\s+\d+\s*:\s*[A-Za-z]+|need\s+handle|we\s+must|we\s+need|we\s+should|need\s+check\s+live\s+availability|Section\s+\d+\s+says|(?:The\s+)?draft\s+(?:currently\s+)?(?:has|had|said)|Could\s+mean\s+(?:add|switch)|operational\s+rules\s*:|When\s+guest\s+asks\s+about\s+availability|DO\s+NOT\s+output|Actually\s+assistant\s+said|That\s+may\s+have\s+been\s+premature|We\s+can\s+say\s+(?:yes|no)|Inquired\s+Amenity\s*:|Inquired\s+Additional|Current\s+Stage\s*:|Tentative\s*\/\s*Proposed\s+Date|Review\s+section\s+\d+|internal\s+(?:thought|reasoning|analysis)|scratchpad)\b/i';

        // 1.8 If the model outputs a draft or final answer section at the end (e.g., "Draft:\n"...", "Final Response:", etc.), extract only that answer first!
        if (preg_match('/(?:^|\n)\s*(?:Draft|Final\s+Response|Final\s+Answer|Actual\s+Response|Clean\s+Response):\s*["\']?(.+?)["\']?\s*$/is', $text, $matches)) {
            $extracted = trim($matches[1]);
            if (!empty($extracted) && !preg_match($metaPattern, $extracted)) {
                $text = $extracted;
            }
        }

        $isMonologue = (bool) preg_match($metaPattern, $text)
            || (bool) preg_match('/^\s*\d+\.\s*(?:Analyze|Analysis|Check|Review|Examine|Evaluate|Determine|Assess|Plan|Understand|Formulate|Consider|Think|Task|Step|Scenario|Intent|Input)/i', $text);

        if ($isMonologue) {
            // Check if there is an explicit conversational quote or final response inside the monologue
            if (preg_match('/(?:(?:Final\s+Response|Clean\s+Response|Assistant\s+Reply|Response|Draft)[:\s]*)\s*["\']?([^"\r\n]+(?:\r?\n[^"\r\n]+)*)["\']?/i', $text, $subMatch)) {
                $candidate = trim($subMatch[1]);
                if (!preg_match($metaPattern, $candidate) && !preg_match('/^\s*\d+\.\s*(?:Analyze|Analysis|Check|Review)/i', $candidate) && strlen($candidate) > 20) {
                    $text = $candidate;
                } else {
                    return !empty($userMessage) ? $this->generateDirectFallbackResponse($userMessage, $bookingDraft) : "Welcome to Hinaguan Nature Park! How can I assist you with your booking today?";
                }
            } else {
                return !empty($userMessage) ? $this->generateDirectFallbackResponse($userMessage, $bookingDraft) : "Welcome to Hinaguan Nature Park! How can I assist you with your booking today?";
            }
        }

        // 2. If the model outputs a draft or final answer section at the end (e.g., "Draft:\n"...", "Final Response:", "Response:"), extract only that answer!
        if (preg_match('/(?:^|\n)\s*(?:Draft|Final\s+Response|Final\s+Answer|Actual\s+Response|Clean\s+Response|Response|Output|Assistant\s+Reply):\s*(.+)$/is', $text, $matches)) {
            $extracted = trim($matches[1]);
            if (!empty($extracted) && !preg_match($metaPattern, $extracted)) {
                $text = $extracted;
            }
        }

        // 3. If there is a "3. Formulate Response:" or "3. Response:" step with the final answer
        if (preg_match('/(?:^|\n)\s*\d+\.\s*(?:Formulate|Draft|Response|Output|Answer|Final\s+Step).*?:\s*\n*(.+)$/is', $text, $matches)) {
            $extracted = trim($matches[1]);
            if (!empty($extracted) && !preg_match($metaPattern, $extracted)) {
                $text = $extracted;
            }
        }

        // 4. Strip block-level thinking/process headers (e.g. "Here's a thinking process:", "Thinking Process:", "Thought Process:", "Analysis:")
        $text = preg_replace('/^(?:Here\'?s\s+(?:a\s+)?(?:thinking|reasoning)\s+process|Thinking\s+Process|Thought\s+Process|Reasoning\s+Process|Chain\s+of\s+Thought|Internal\s+Analysis|Analysis):\s*/im', '', $text);

        // 5. Filter out paragraphs that are numbered chain-of-thought analysis steps or meta reasoning
        $paragraphs = preg_split('/\r?\n\s*\r?\n/', $text);
        $filtered = [];
        foreach ($paragraphs as $p) {
            $trimmedP = trim($p);
            // If paragraph starts with a chain-of-thought / scratchpad step header, discard it
            if (preg_match('/^\d+\.\s*(?:Analyze|Analysis|Check|Retrieve|Search|Formulate|Draft|Understand|Examine|Review|Identify|Determine|Plan|Context|Task|Step|Consider|Thought|Think|Scenario|User|Intent|Input|Knowledge)/i', $trimmedP)) {
                continue;
            }
            // If paragraph is solely meta reasoning bullets like "- User said...", "- Context: ...", discard it
            if (preg_match('/^(?:[-*•]\s*(?:User\s+(?:said|is\s+asking|wants|says|responded)|Context:|Internal\s+note|Chain\s+of\s+thought|Thinking\s+process|Scratchpad|Looking\s+back|Step\s+\d|Acknowledge|Check|Mention|Ask|Then\s+proceed))/i', $trimmedP)) {
                continue;
            }
            if (preg_match($metaPattern, $trimmedP)) {
                continue;
            }
            $filtered[] = $p;
        }

        if (!empty($filtered)) {
            $text = implode("\n\n", $filtered);
        } else {
            return !empty($userMessage) ? $this->generateDirectFallbackResponse($userMessage, $bookingDraft) : 'Welcome to Hinaguan Nature Park! How can I assist you with your booking today?';
        }

        // 6. Strip any leftover "Draft:", "Response:", "Answer:" labels at start
        $text = preg_replace('/^(?:Draft|Final\s+Response|Final\s+Answer|Response|Output|Answer|Reply):\s*/i', '', trim($text));

        // 7. Strip leading bot/role prefixes like "Bren:", "HinaguanBot:", "StaffBot:", "AdminBot:", "Assistant:"
        $text = preg_replace('/^(?:Bren(?:\s+AI\s+Assistant)?|HinaguanBot|StaffBot|AdminBot|GuestBot|Bot|Assistant|AI):\s*/i', '', trim($text));

        // 8. If the text still starts with numbered analysis or matches metaPattern, reject and generate direct answer
        if (preg_match('/^\s*\d+\.\s*(?:Analyze|Analysis|Check|Determine|Plan|Review)/i', $text) || preg_match($metaPattern, $text) || preg_match('/(?:Let\'?s\s+structure|But\s+wait)/i', $text)) {
            return !empty($userMessage) ? $this->generateDirectFallbackResponse($userMessage, $bookingDraft) : 'Welcome to Hinaguan Nature Park! How can I assist you with your booking today?';
        }

        // 9. Strip surrounding quotation marks if the draft was wrapped in quotes
        $text = trim($text);
        if ((str_starts_with($text, '"') && str_ends_with($text, '"')) || (str_starts_with($text, "'") && str_ends_with($text, "'"))) {
            if (strlen($text) >= 2) {
                $text = trim(substr($text, 1, -1));
            }
        }
        if (str_starts_with($text, '"') && substr_count($text, '"') === 1) {
            $text = ltrim($text, '"');
        }

        // 10. Inclusion safeguard: prevent hallucinated free entrance or free pool for Cottages and Payags
        if (preg_match('/\b(?:cottage|payag)\b/i', $text) && !preg_match('/\b(?:function hall|a-house)\b/i', $text)) {
            $text = preg_replace('/(?:,\s*(?:and\s*)?|and\s+)?includes\s+free\s+entrance\s*(?:and|&)\s*(?:free\s*)?pool(?:\s+access)?\.?/i', '. (Please note that regular entrance and pool access are separate fees.)', $text);
            $text = preg_replace('/with\s+free\s+entrance\s*(?:and|&)\s*(?:free\s*)?pool(?:\s+access)?/i', 'with regular entrance and pool fees applying separately', $text);
            $text = preg_replace('/(?:includes|has|with)\s+free\s+(?:entrance|pool)(?:\s+access)?/i', 'regular entrance and pool access apply separately', $text);
        }

        $cleaned = trim($text);
        if (empty($cleaned)) {
            return !empty($userMessage) ? $this->generateDirectFallbackResponse($userMessage, $bookingDraft) : $confusedFallback;
        }
        if (preg_match('/(?:user\s+safety|response\s+safety)/i', $cleaned) || preg_match($metaPattern, $cleaned) || preg_match('/(?:Let\'?s\s+structure|But\s+wait)/i', $cleaned)) {
            return !empty($userMessage) ? $this->generateDirectFallbackResponse($userMessage, $bookingDraft) : $confusedFallback;
        }

        return $cleaned;
    }

    /**
     * Context-aware direct fallback response in case external LLM is offline, invalid key, or times out.
     * Comprehensive database-driven intelligence for Hinaguan Nature Park.
     */
    private function generateDirectFallbackResponse(string $userMessage, ?array $bookingDraft = null): string
    {
        $msgLower = mb_strtolower(trim($userMessage));
        $now = now();
        $todayStr = $now->toDateString();

        // 0. Guard against previously recorded dates that have expired
        if (!empty($bookingDraft['pastDateClarification']['recordedDateExpired'])) {
            $pastDate = $bookingDraft['pastDateClarification']['pastDate'] ?? 'that date';
            try {
                $pastDateReadable = Carbon::parse($pastDate)->format('F j, Y');
            } catch (\Exception $e) {
                $pastDateReadable = $pastDate;
            }
            if (preg_match('/\b(?:pila|tagpila|asa|nindot|unsa|diay|kinsa|kanus-a|kabuok|ka\s+tawo|unsaon|adto|anhi|swak|lahi|pagkaon|sugba|sapa)\b/i', $userMessage)) {
                return "Ang imong naunang napili nga petsa ({$pastDateReadable}) milabay na diay! Dili na kita makapadayon sa booking alang sa nilabay nga petsa kay gidili kini. Unsang umaabot nga petsa (sugod karon {$todayStr} o unahan) ang imong gustong i-reserba sa Hinaguan Nature Park?";
            } elseif (preg_match('/\b(?:magkano|meron|saan|ano|anong|ba|po|opo|pwede|kami|tayo|sino|kailan|tao|paano|pumunta)\b/i', $userMessage)) {
                return "Ang inyong naunang napiling petsa ({$pastDateReadable}) ay lumipas na po! Hindi na po natin maipagpapatuloy ang booking para sa nakaraang petsa dahil hindi po ito posible. Anong darating na petsa po (mula ngayong {$todayStr} pataas) ang nais ninyong i-book sa Hinaguan Nature Park?";
            } else {
                return "Your previously selected date ({$pastDateReadable}) has already passed! It is strictly not possible to reserve or continue a booking for a past date. What upcoming date (from today onwards) would you like to visit Hinaguan Nature Park instead?";
            }
        }

        // Detect language
        $isBisaya = (bool) preg_match('/\b(?:pila|tagpila|asa|nindot|suba|puy-anan|unsa|init|uwan|tugnaw|daghan|gamay|kaayo|gani|diay|kinsa|kanus-a|kabuok|ka\s+tawo|unsaon|adto|anhi|swak|lahi|pagkaon|sugba|sapa)\b/i', $userMessage)
            || preg_match('/\b(?:ug|sa)\s+(?:mga|amenity|cottage|payag|pagkaon)\b/i', $userMessage);

        $isTagalog = (bool) preg_match('/\b(?:magkano|meron|saan|ano|anong|ba|po|opo|ulan|mainit|pwede|kami|tayo|sino|kailan|tao|bawat|paano|pumunta|sakyan|pagkain|maganda|sulit)\b/i', $userMessage);

        // If strong Bisaya markers are present, prefer Bisaya
        if (preg_match('/\b(?:ug|pagkaon|nindot|unsa|kabuok|ka\s+tawo|kanus-a|suba|sapa)\b/i', $userMessage)) {
            $isBisaya = true;
            $isTagalog = false;
        }

        // If neutral message (like phone number, email, or number) and draft has a remembered language:
        if (!$isBisaya && !$isTagalog && !empty($bookingDraft['lang'])) {
            if ($bookingDraft['lang'] === 'bisaya') {
                $isBisaya = true;
            } elseif ($bookingDraft['lang'] === 'tagalog') {
                $isTagalog = true;
            }
        }

        // Fetch live park settings and weather from database
        $settings = ParkSetting::first();
        $openTime = $settings?->opening_time ? Carbon::parse($settings->opening_time)->format('g:i A') : '8:00 AM';
        $closeTime = $settings?->closing_time ? Carbon::parse($settings->closing_time)->format('g:i A') : '5:00 PM';
        $dayStart = $settings?->daytime_start ? Carbon::parse($settings->daytime_start)->format('g:i A') : '8:00 AM';
        $dayEnd = $settings?->daytime_end ? Carbon::parse($settings->daytime_end)->format('g:i A') : '5:00 PM';
        $nightStart = $settings?->nighttime_start ? Carbon::parse($settings->nighttime_start)->format('g:i A') : '6:00 PM';
        $nightEnd = $settings?->nighttime_end ? Carbon::parse($settings->nighttime_end)->format('g:i A') : '8:00 AM';

        $dayAdult = $settings ? number_format((float)($settings->daytime_adult_entrance_fee ?? 20), 2) : '20.00';
        $nightAdult = $settings ? number_format((float)($settings->nighttime_adult_entrance_fee ?? 50), 2) : '50.00';
        $dayPool = $settings ? number_format((float)($settings->day_pool_fee ?? 50), 2) : '50.00';
        $nightPool = $settings ? number_format((float)($settings->night_pool_fee ?? 50), 2) : '50.00';
        $brendaIsHere = (bool) ($settings?->brenda_available ?? true);

        $latestWeather = DailyWeatherShiftLog::orderBy('log_date', 'desc')->first();
        $weatherCond = $latestWeather ? $latestWeather->weather_condition : 'Sunny';
        $weatherTemp = $latestWeather ? $latestWeather->temperature_celsius : '30';
        $weatherRain = $latestWeather ? (int) $latestWeather->precipitation_probability : 10;
        $isRainy = stripos($weatherCond, 'rain') !== false || $weatherRain >= 50;

        // 1. Guardrail for completely unrelated topics
        $unrelated = (bool) preg_match('/\b(?:python|javascript|php|code|coding|equation|solve|calculate|math|president|election|crypto|bitcoin|homework)\b/i', $userMessage);
        if ($unrelated) {
            if ($isBisaya) {
                return "Pasayloa ko, igo ra ko makatabang sa mga pangutana bahin sa Hinaguan Nature Park. Unsa may imong gustong mahibaloan bahin sa among amenities, rates, o booking?";
            } elseif ($isTagalog) {
                return "Paumanhin po, tanging mga katanungan lamang tungkol sa Hinaguan Nature Park ang aking masasagot. May maitutulong po ba ako sa inyong pagbisita, rates, o booking?";
            } else {
                return "I can only assist with inquiries regarding Hinaguan Nature Park. How may I help you with our amenities, rates, weather, or bookings?";
            }
        }

        // Check if user is asking a general question/FAQ (e.g. food/corkage, rules, swimming pool, entrance fees, commute/directions, weather, etc.)
        // This ensures the bot answers the user's question even if a booking is active instead of repeating booking prompts!
        $isGeneralFaq = (bool) preg_match('/(?:rule|rules|bawal|polic|corkage|outside\s+food|pagkaon|pagkain|food|baon|ilimnon|inumin|pet|pets|iro|ido|aso|kanding|iring|pusa|animal|grill|grilling|sugba|ihaw|ihawan|alcohol|hard\s+liquor|beer|alak|parking|parkanan|entrance|entrance\s+fee|rates?|how\s+much|pila|magkano|pool|swimming|ligo|kaligo|brenda|brenda\s+mage|weather|panahon|ulan|init|direction|direksyon|commute|paano\s+pumunta|unsaon\s+pag-?adto|asa\s+dapit)\b/i', $msgLower)
            && !preg_match('/^(?:09\d{9}|\+?639\d{9})$/', preg_replace('/\s+/', '', $userMessage))
            && !preg_match('/\b[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}\b/', $userMessage);

        // 2. Short words / greetings / typos (e.g. "what te", "hi", "hello", "hey", "kumusta")
        $hasActiveBookingContext = !empty($bookingDraft['active'])
            || !empty($bookingDraft['amenities'])
            || !empty($bookingDraft['amenity'])
            || !empty($bookingDraft['startDate'])
            || !empty($bookingDraft['tentativeDate'])
            || !empty($bookingDraft['step']);

        if (!$hasActiveBookingContext && (preg_match('/^(?:hi+|hello+|hey+|good\s*(?:morning|afternoon|evening)|kumusta|kamusta|halo|bren|sup|yo|start)\b/i', $msgLower) || (mb_strlen($msgLower) <= 2 && !is_numeric($msgLower)))) {
            if ($isBisaya) {
                return "Maayong adlaw! Ako si Bren, ang imong AI assistant dinhi sa Hinaguan Nature Park 🌿\n\nMakatabang ko kanimo sa:\n• 🛖 Amenities ug Rates (Payags, Cottages, A-Houses, Function Hall)\n• 📅 Live Availability ug Rekomendasyon\n• 🎟️ Entrance Fees ug Operating Hours\n• 📝 Giya sa Online Booking\n• 📍 Direksyon ug Commute Guide\n\nUnsay akong matabang kanimo karon?";
            } elseif ($isTagalog) {
                return "Magandang araw! Ako si Bren, ang inyong AI assistant dito sa Hinaguan Nature Park 🌿\n\nMatutulungan ko po kayo sa:\n• 🛖 Amenities at Rates (Payags, Cottages, A-Houses, Function Hall)\n• 📅 Live Availability at Rekomendasyon\n• 🎟️ Entrance Fees at Operating Hours\n• 📝 Paano mag-book online\n• 📍 Direksyon at Commute Guide\n\nAno po ang maitutulong ko sa inyo ngayon?";
            } else {
                return "Hello and welcome to **Hinaguan Nature Park** in Jasaan, Misamis Oriental! 🌿 I am **Bren**, your AI assistant.\n\nI can help you with:\n• 🛖 **Amenities & Rates** (Payags, Cottages, A-Houses, Function Hall)\n• 📅 **Live Availability & Recommendations**\n• 🎟️ **Entrance Fees & Operating Hours**\n• 📝 **Step-by-step Online Booking**\n• 📍 **Directions & Commute Guide**\n\nHow may I help you today?";
            }
        }

        // Specific partial typo check like "what te", "what ti", "what the", "what is this"
        if (preg_match('/^what\s+(?:te|ti|the|r|time)\b/i', $msgLower) || preg_match('/^(?:what|unsa|ano)\s*$/i', $msgLower)) {
            if ($isBisaya) {
                return "Kung nangutana ka bahin sa **oras sa park**, abli kami gikan {$openTime} hangtod {$closeTime} (Daytime: {$dayStart} – {$dayEnd}, Nighttime: {$nightStart} – {$nightEnd})!\n\nKung bahin sa **rates o amenities**, naa mi mga Cottages (₱200), Payags (₱300), A-Houses (₱300/₱500), ug Function Hall (₱5,000). Unsay imong gustong masayran?";
            } elseif ($isTagalog) {
                return "Kung tungkol po sa **oras ng park**, bukas kami mula {$openTime} hanggang {$closeTime} (Daytime: {$dayStart} – {$dayEnd}, Nighttime: {$nightStart} – {$nightEnd})!\n\nKung tungkol naman sa **rates o amenities**, mayroon kaming Cottages (₱200), Payags (₱300), A-Houses (₱300/₱500), at Function Hall (₱5,000). Ano po ang nais ninyong alamin?";
            } else {
                return "If you are inquiring about **park hours**, our daytime session runs from {$dayStart} to {$dayEnd} and nighttime from {$nightStart} to {$nightEnd}!\n\nIf you are asking about **amenities & rates**, we offer Cottages (₱200), Payags (₱300), A-Houses (₱300/₱500), and our Grand Function Hall (₱5,000). How may I assist you?";
            }
        }

        // 3. Request amenity picker or catalog button
        if (preg_match('/(?:(?:give|show|open|reopen|display|bring\s+up|launch|view|get)\s+(?:me\s+)?(?:the\s+)?(?:amenit(?:y|ies)?(?:\s+picker|\s+selector|\s+catalog|\s+list|\s+modal)?|cottages?|payags?|options)|amenit(?:y|ies)\s+picker|pick\s+(?:an\s+)?amenity|choose\s+(?:an\s+)?amenity|select\s+(?:an\s+)?amenity|pakita\s+ang\s+amenit|patingin\s+ng\s+amenit)/i', $msgLower)) {
            $startDate = $bookingDraft['startDate'] ?? ($bookingDraft['date'] ?? null);
            $slot = $bookingDraft['startSlot'] ?? ($bookingDraft['slot'] ?? 'Daytime');
            if ($startDate) {
                return "Sure! Click the **Choose Amenities** button right above your message box (or in the top bar) to view all available cottages, payags, and A-houses for {$startDate} [{$slot}] 🌿";
            }
        }

        // 4. Show what I availed / current picks
        if (preg_match('/(?:show\s+(?:me\s+)?(?:what\s+(?:amenit(?:y|ies)?\s+)?i\s+(?:just\s+)?avail|my\s+(?:pick|picks|selection|draft|booking)|the\s+record)|what\s+did\s+i\s+pick|unsa\s+akong\s+gipili|patingin\s+ng\s+pinili\s*ko|view\s+pick)/i', $msgLower)) {
            $amenities = !empty($bookingDraft['amenities']) && is_array($bookingDraft['amenities'])
                ? $bookingDraft['amenities']
                : (!empty($bookingDraft['amenity']) ? [$bookingDraft['amenity']] : []);

            if (!empty($amenities)) {
                $amLines = [];
                $total = 0;
                foreach ($amenities as $am) {
                    $name = $am['name'] ?? 'Amenity';
                    $price = (float) ($am['price'] ?? 0);
                    $total += $price;
                    $amLines[] = "• 🏷️ {$name} (₱" . number_format($price, 2) . ")";
                }
                $amenitiesStr = implode("\n", $amLines);

                $startDate = $bookingDraft['startDate'] ?? ($bookingDraft['date'] ?? now()->toDateString());
                $endDate = $bookingDraft['endDate'] ?? $startDate;
                $startSlot = $bookingDraft['startSlot'] ?? ($bookingDraft['slot'] ?? 'Daytime');
                $endSlot = $bookingDraft['endSlot'] ?? $startSlot;

                $scheduleStr = ($startDate === $endDate && $startSlot === $endSlot)
                    ? "{$startDate} [{$startSlot}]"
                    : "Check-in: {$startDate} [{$startSlot}] &rarr; Check-out: {$endDate} [{$endSlot}]";

                $booker = !empty($bookingDraft['guest']['name']) ? $bookingDraft['guest']['name'] : 'Not yet provided';
                $phone = !empty($bookingDraft['guest']['phone']) ? $bookingDraft['guest']['phone'] : 'Not yet provided';
                $email = !empty($bookingDraft['guest']['email']) ? $bookingDraft['guest']['email'] : 'Not yet provided';
                $pax = !empty($bookingDraft['guest']['guests_count']) ? ((string) $bookingDraft['guest']['guests_count'] . ' pax') : 'Not yet provided';

                $dp = round($total * 0.5, 2);
                $bal = round($total - $dp, 2);

                return "Here is the record of what you have selected so far:\n"
                    . "{$amenitiesStr}\n"
                    . "• 📅 Target Schedule: {$scheduleStr}\n"
                    . "• 👤 Primary Guest: {$booker}\n"
                    . "• 📞 Mobile & Email: {$phone} | {$email}\n"
                    . "• 👥 Headcount: {$pax}\n"
                    . "• 💵 Total Reservation: ₱" . number_format($total, 2) . "\n"
                    . "• 💳 Required 50% Downpayment: ₱" . number_format($dp, 2) . "\n"
                    . "• ⏳ Remaining Balance: ₱" . number_format($bal, 2) . " (payable at park entrance)\n\n"
                    . "POLICY NOTE: The 50% downpayment is strictly non-refundable upon payment. Would you like to proceed with payment or modify your selection?";
            } else {
                if (!empty($bookingDraft['active']) && !empty($bookingDraft['startDate'])) {
                    $sd = $bookingDraft['startDate'];
                    $sl = $bookingDraft['startSlot'] ?? 'Daytime';
                    return "You haven't selected any amenities yet! Click the **Choose Amenities** button right above your message box to browse our cottages, payags, and A-houses for {$sd} [{$sl}].";
                }
                return "You haven't selected any amenities yet! To start your booking right now, let me know your preferred check-in and check-out dates and session slots, and I'll display our available cottages, payags, and A-houses.";
            }
        }

        // 4.6 Handling Adding, Switching, or Modifying Amenities when booking draft has items
        $hasExistingDraftAmenities = !empty($bookingDraft['amenities']) && is_array($bookingDraft['amenities']) && count($bookingDraft['amenities']) > 0;
        $isChangeBookingRequest = (bool) preg_match('/(?:change\s+something|want\s+to\s+change|change\s+my|modify|adjust|paliton|usbon|baguhin)\b/i', $msgLower);

        if ($isChangeBookingRequest && !preg_match('/\b(?:cottage|payag|a-?house|function\s*hall|date|daytime|nighttime)\b/i', $msgLower)) {
            if ($isBisaya) {
                return "Walay problema! Unsay gusto nimong usbon sa imong booking sa dili pa mopadayon sa payment? Pwede natong usbon ang petsa, session (Daytime o Nighttime), magdugang o mag-ilis ug amenity, o i-update ang imong guest details. Isulti lang kanako!";
            } elseif ($isTagalog) {
                return "Walang problema po! Ano po ang nais ninyong baguhin sa inyong reservation bago magpatuloy sa payment? Maaari nating baguhin ang petsa, session slot (Daytime o Nighttime), magdagdag o magpalit ng amenity, o i-update ang inyong guest info. Sabihin lamang po sa akin!";
            } else {
                return "No problem at all! What would you like to change before proceeding to payment? We can update your visit date, session slot (Daytime or Nighttime), add or switch amenities, or update your contact details. Just let me know!";
            }
        }

        // Check if user is asking to add, switch, or inquiring about an amenity while draft amenities exist
        $inquiredAmenitiesForModification = $this->extractAllAmenitiesFromMessage($userMessage);
        if (empty($inquiredAmenitiesForModification) && !empty($bookingDraft['tentativeAmenities'])) {
            $tList = is_array($bookingDraft['tentativeAmenities']) ? $bookingDraft['tentativeAmenities'] : explode(',', $bookingDraft['tentativeAmenities']);
            foreach ($tList as $tName) {
                $m = Amenity::with('benefits')->where('status', true)->where('amenities_name', 'LIKE', '%' . trim($tName) . '%')->first();
                if ($m) $inquiredAmenitiesForModification[] = $m;
            }
        } elseif (empty($inquiredAmenitiesForModification) && !empty($bookingDraft['tentativeAmenity'])) {
            $m = Amenity::with('benefits')->where('status', true)->where('amenities_name', 'LIKE', '%' . trim($bookingDraft['tentativeAmenity']) . '%')->first();
            if ($m) $inquiredAmenitiesForModification[] = $m;
        }

        if ($hasExistingDraftAmenities && !empty($inquiredAmenitiesForModification)) {
            $existingNames = array_map(fn($a) => $a['name'] ?? 'Amenity', $bookingDraft['amenities']);
            $existingNamesStr = implode(', ', $existingNames);
            $inquiredNames = array_map(fn($m) => $m->amenities_name, $inquiredAmenitiesForModification);
            $inquiredNamesStr = implode(' and ', $inquiredNames);
            $inquiredNamesStrBis = implode(' ug ', $inquiredNames);
            $inquiredNamesStrTag = implode(' at ', $inquiredNames);

            $isExplicitAdd = (bool) preg_match('/\b(?:add\s+(?:it|this|more|another)?|yes\s+add|dugang|dagdag|plus|also)\b/i', $msgLower)
                && !preg_match('/\b(?:can\s+u|pwede\s+ba|pwede\s+kaha|is\s+it|\?)\b/i', $msgLower);
            $isExplicitSwitch = (bool) preg_match('/\b(?:switch\s+(?:to\s+it|to\s+|it)?|change\s+to|palit|replace|instead)\b/i', $msgLower)
                && !preg_match('/\b(?:can\s+u|pwede\s+ba|pwede\s+kaha|is\s+it|\?)\b/i', $msgLower);
            $isAddOrSwitchInquiry = (bool) preg_match('/\b(?:can\s+u\s+add|what\s+about|how\s+about|pwede\s+ba|another|additional|\?)\b/i', $msgLower);

            // If user did NOT say add/switch/modify (e.g. they just booked/confirmed their amenities with "book ahouse 2 and payag 3"),
            // then skip Section 4 so the conversation smoothly proceeds to Section 5.4 (asking for Full Name).
            if ($isExplicitAdd || $isExplicitSwitch || $isAddOrSwitchInquiry) {
                $sDate = $bookingDraft['startDate'] ?? now()->toDateString();
                $sSlot = $bookingDraft['startSlot'] ?? ($bookingDraft['slot'] ?? 'Daytime');
                $isNight = $sSlot === 'Nighttime';

                $newAddedPrice = 0;
                foreach ($inquiredAmenitiesForModification as $m) {
                    $newAddedPrice += $isNight ? (float) ($m->nighttime_price ?? 0) : (float) ($m->daytime_price ?? 0);
                }

                $combinedTotal = array_sum(array_map(fn($a) => (float) ($a['price'] ?? 0), $bookingDraft['amenities']));
                $combinedDp = round($combinedTotal * 0.5, 2);

                $priorAmenities = array_filter($bookingDraft['amenities'], function($a) use ($inquiredNames) {
                    return !in_array($a['name'] ?? '', $inquiredNames);
                });
                $priorNames = array_map(fn($a) => $a['name'] ?? 'Amenity', $priorAmenities);
                $priorNamesStr = !empty($priorNames) ? implode(', ', $priorNames) : 'your current reservation';

                if ($isExplicitAdd) {
                    if ($isBisaya) {
                        return "Nindot kaayo! Gidugang na nako ang **{$inquiredNamesStrBis}** sa imong reservation uban sa **{$priorNamesStr}** para sa {$sDate} ({$sSlot})! 🌿\n\nAng updated total kay **₱" . number_format($combinedTotal, 2) . "** (50% downpayment: **₱" . number_format($combinedDp, 2) . "**).\n\nAndam na ba ka mopadayon sa payment?";
                    } elseif ($isTagalog) {
                        return "Ayos po! Naidagdag ko na ang **{$inquiredNamesStrTag}** sa inyong reservation kasama ang **{$priorNamesStr}** para sa {$sDate} ({$sSlot})! 🌿\n\nAng inyong updated total ay **₱" . number_format($combinedTotal, 2) . "** (50% downpayment: **₱" . number_format($combinedDp, 2) . "**).\n\nHanda na po ba kayong magpatuloy sa payment?";
                    } else {
                        return "Awesome! I've added **{$inquiredNamesStr}** to your reservation alongside **{$priorNamesStr}** for {$sDate} ({$sSlot})! 🌿\n\nYour updated total is **₱" . number_format($combinedTotal, 2) . "** (50% downpayment: **₱" . number_format($combinedDp, 2) . "**).\n\nAre you ready to proceed to payment, or would you like to review anything else?";
                    }
                } elseif ($isExplicitSwitch) {
                    $switchTotal = $newAddedPrice;
                    $switchDp = round($switchTotal * 0.5, 2);
                    if ($isBisaya) {
                        return "Nasabtan! Gi-ilis na nako ang imong amenity ngadto sa **{$inquiredNamesStrBis}** (₱" . number_format($switchTotal, 2) . ") para sa {$sDate} ({$sSlot}).\n\nTotal: **₱" . number_format($switchTotal, 2) . "** (50% downpayment: **₱" . number_format($switchDp, 2) . "**).\n\nAndam na ba ka mopadayon sa payment?";
                    } elseif ($isTagalog) {
                        return "Nakuha ko po! Napalitan na ang inyong amenity patungong **{$inquiredNamesStrTag}** (₱" . number_format($switchTotal, 2) . ") para sa {$sDate} ({$sSlot}).\n\nTotal: **₱" . number_format($switchTotal, 2) . "** (50% downpayment: **₱" . number_format($switchDp, 2) . "**).\n\nHanda na po ba kayong magpatuloy sa payment?";
                    } else {
                        return "Got it! I've switched your reserved amenity to **{$inquiredNamesStr}** (₱" . number_format($switchTotal, 2) . ") for {$sDate} ({$sSlot}).\n\nTotal is **₱" . number_format($switchTotal, 2) . "** (50% required downpayment: **₱" . number_format($switchDp, 2) . "**).\n\nAre you ready to proceed to payment?";
                    }
                } else {
                    // Inquiring: "can u add more cottage, like cottage 2", "what about cottage 2", etc.
                    $candidateTotal = $combinedTotal + $newAddedPrice;
                    if ($isBisaya) {
                        return "Oo! Available kaayo ang **{$inquiredNamesStrBis}** (₱" . number_format($newAddedPrice, 2) . ") para sa imong schedule nga **{$sDate} ({$sSlot})**! 🌿\n\nGusto ba nimo nga **idugang** kini sa imong reservation uban sa {$priorNamesStr} (bag-ong total: ₱" . number_format($candidateTotal, 2) . "), o mas gusto nimo nga **i-switch** ngadto sa {$inquiredNamesStrBis}?";
                    } elseif ($isTagalog) {
                        return "Opo! Available po ang **{$inquiredNamesStrTag}** (₱" . number_format($newAddedPrice, 2) . ") para sa inyong schedule sa **{$sDate} ({$sSlot})**! 🌿\n\nNais niyo po bang **idagdag** ito sa inyong booking kasama ang {$priorNamesStr} (bagong total: ₱" . number_format($candidateTotal, 2) . "), o mas gusto niyo pong **i-switch** patungong {$inquiredNamesStrTag}?";
                    } else {
                        return "Yes! **{$inquiredNamesStr}** (₱" . number_format($newAddedPrice, 2) . ") is available for your schedule on **{$sDate} ({$sSlot})**! 🌿\n\nWould you like me to **add** it to your reservation alongside {$priorNamesStr} (for a total of ₱" . number_format($candidateTotal, 2) . "), or would you prefer to **switch** instead?";
                    }
                }
            }
        }

        // 5. Amenity Availability & Date Inquiries (e.g. "what date is payag 4 available", "is cottage 1 available")
        $availabilityReply = $this->handleAmenityAvailabilityInquiry($userMessage, $isBisaya, $isTagalog);
        if ($availabilityReply !== null) {
            return $availabilityReply;
        }

        // 5.1 Agreement to view amenities or direct user request to show all amenities
        $isAgreeToViewAmenities = (bool) preg_match('/(?:(?:yes|sure|oo|sige|opo|yes\s+please)\s+(?:show(?:\s+me)?|view|pakita)|show\s+(?:me\s+)?all\s+amenit|view\s+(?:all\s+)?available\s+amenit|pakita\s+(?:ang\s+)?tanan|show\s+available\s+amenit|patingin\s+ng\s+amenit)/i', $msgLower)
            || ((!empty($bookingDraft['startDate']) || !empty($bookingDraft['tentativeDate'])) && preg_match('/^(?:yes\s*(?:show\s*me|please)?|sure|pakita|sige|show\s*me)$/i', trim($msgLower)) && empty($bookingDraft['amenities']));

        if ($isAgreeToViewAmenities) {
            $sd = $bookingDraft['startDate'] ?? ($bookingDraft['tentativeDate'] ?? now()->toDateString());
            $sl = $bookingDraft['startSlot'] ?? ($bookingDraft['tentativeSlot'] ?? 'Daytime');
            if ($isBisaya) {
                return "Mao kini ang tanang available nga amenities para sa **{$sd} ({$sl})**! I-tap ang button sa ubos aron makapili: [VIEW_AMENITIES_BUTTON]";
            } elseif ($isTagalog) {
                return "Narito po ang lahat ng available amenities para sa **{$sd} ({$sl})**! I-tap lamang po ang button sa ibaba upang makapili: [VIEW_AMENITIES_BUTTON]";
            } else {
                return "Here are all the available amenities for **{$sd} ({$sl})**! Tap the button below to browse photos, capacity, and rates: [VIEW_AMENITIES_BUTTON]";
            }
        }

        // 5.15 Past date clarification (Reject past dates, clarify if guest means next year)
        if (!empty($bookingDraft['pastDateClarification'])) {
            $clar = $bookingDraft['pastDateClarification'];
            if (!empty($clar['expiredPast'])) {
                if ($isBisaya) {
                    return "Nalabyan na kana nga petsa. Modawat lamang kami ug reservations sugod karong adlawa paabante! Unsa nga umaabot nga petsa ang gusto nimo bisitahon sa Hinaguan Nature Park?";
                } elseif ($isTagalog) {
                    return "Nakalipas na po ang petsang iyan. Tumatanggap lamang po kami ng reservations mula ngayong araw pasulong! Anong darating na petsa po ang nais ninyong bisitahin sa Hinaguan Nature Park?";
                } else {
                    return "That date has already passed. We only accept reservations from today onwards! What upcoming date would you like to visit Hinaguan Nature Park?";
                }
            }
            $monthDay = $clar['monthDay'] ?? 'That date';
            $curY = $clar['currentYear'] ?? now()->year;
            $nextY = $clar['nextYear'] ?? (now()->year + 1);
            if ($isBisaya) {
                return "Ang **{$monthDay}** para karong tuiga ({$curY}) nalabyan na! Buot ba nimong ipasabot ang **{$monthDay}, {$nextY} (sunod tuig)**, o naghuna-huna ka ug laing umaabot nga petsa? Ug kini ba para sa **Daytime** ({$dayStart} – {$dayEnd}) o **Nighttime** ({$nightStart} – {$nightEnd})?";
            } elseif ($isTagalog) {
                return "Ang **{$monthDay}** po para sa taong ito ({$curY}) ay nakalipas na! Ang ibig sabihin po ba ninyo ay **{$monthDay}, {$nextY} (sa susunod na taon)**, o may iba pa po kayong tinitignang darating na petsa? At ito po ba ay para sa **Daytime** ({$dayStart} – {$dayEnd}) o **Nighttime** ({$nightStart} – {$nightEnd})?";
            } else {
                return "{$monthDay} for this year ({$curY}) has already passed! Did you mean **{$monthDay}, {$nextY} (next year)**, or were you thinking of another upcoming date? Also, will that be for **Daytime** ({$dayStart} – {$dayEnd}) or **Nighttime** ({$nightStart} – {$nightEnd})?";
            }
        }

        // 5.2 Tentative date inquiry (Make sure before recording!)
        if (!empty($bookingDraft['tentativeDate']) && empty($bookingDraft['startDate'])) {
            $tDate = $bookingDraft['tentativeDate'];
            $tCarbon = Carbon::parse($tDate);
            $tFmt = $tCarbon->format('F j, Y');

            // Safety guard: if tentative date is in the past
            if ($tCarbon->toDateString() < now()->toDateString()) {
                $monthDay = $tCarbon->format('F j');
                $curY = now()->year;
                $nextY = now()->year + 1;
                if ($isBisaya) {
                    return "Ang **{$monthDay}** para karong tuiga ({$curY}) nalabyan na! Buot ba nimong ipasabot ang **{$monthDay}, {$nextY} (sunod tuig)**, o naghuna-huna ka ug laing umaabot nga petsa? Ug kini ba para sa **Daytime** ({$dayStart} – {$dayEnd}) o **Nighttime** ({$nightStart} – {$nightEnd})?";
                } elseif ($isTagalog) {
                    return "Ang **{$monthDay}** po para sa taong ito ({$curY}) ay nakalipas na! Ang ibig sabihin po ba ninyo ay **{$monthDay}, {$nextY} (sa susunod na taon)**, o may iba pa po kayong tinitignang darating na petsa? At ito po ba ay para sa **Daytime** ({$dayStart} – {$dayEnd}) o **Nighttime** ({$nightStart} – {$nightEnd})?";
                } else {
                    return "{$monthDay} for this year ({$curY}) has already passed! Did you mean **{$monthDay}, {$nextY} (next year)**, or were you thinking of another upcoming date? Also, will that be for **Daytime** ({$dayStart} – {$dayEnd}) or **Nighttime** ({$nightStart} – {$nightEnd})?";
                }
            }

            $hasKnownAmenity = !empty($bookingDraft['amenities']) || !empty($bookingDraft['amenity']) || !empty($bookingDraft['tentativeAmenity']) || !empty($bookingDraft['tentativeAmenities']) || !empty($bookingDraft['tentativeCategory']);
            if ($hasKnownAmenity) {
                $amStr = !empty($bookingDraft['amenities'])
                    ? implode(' and ', array_map(fn($a) => $a['name'] ?? 'Amenity', $bookingDraft['amenities']))
                    : (!empty($bookingDraft['tentativeAmenities'])
                        ? implode(' and ', (array) $bookingDraft['tentativeAmenities'])
                        : ($bookingDraft['tentativeAmenity'] ?? ($bookingDraft['tentativeCategory'] ?? 'your selected amenity')));
                if ($isBisaya) {
                    return "Nindot kaayo nga petsa ang **{$tFmt}** para sa imong **{$amStr}** sa Hinaguan Nature Park! 🌿 Sigurado ba ka nga gusto nimo pilion ang {$tFmt} para sa imong pagbisita? Ug gusto ba nimo ug **Day Tour**, **Night Tour**, o **24-Hour Stay**? ☀️🌙";
                } elseif ($isTagalog) {
                    return "Napakagandang petsa po ng **{$tFmt}** para sa inyong **{$amStr}** sa Hinaguan Nature Park! 🌿 Sigurado po ba kayong nais ninyong piliin ang {$tFmt} para sa inyong pagbisita? At nais niyo po ba ng **Day Tour**, **Night Tour**, o **24-Hour Stay**? ☀️🌙";
                } else {
                    return "{$tFmt} sounds like a wonderful date for your **{$amStr}** at Hinaguan Nature Park! 🌿 Are you sure you would like to pick {$tFmt} for your visit? Also, would you prefer a **Day Tour**, **Night Tour**, or **24-Hour Stay**? ☀️🌙";
                }
            }

            if ($isBisaya) {
                return "Nindot kaayo nga petsa ang **{$tFmt}** para mobisita sa Hinaguan Nature Park! 🌿 Sigurado ba ka nga gusto nimo pilion ang {$tFmt} para sa imong pagbisita? Ug gusto ba nimo ug **Day Tour**, **Night Tour**, o **24-Hour Stay**? ☀️🌙\n\nTabangan tika sa pagpangita sa pinakanindot nga amenity para sa imong pagbisita! 😊";
            } elseif ($isTagalog) {
                return "Napakagandang petsa po ng **{$tFmt}** para bumisita sa Hinaguan Nature Park! 🌿 Sigurado po ba kayong nais ninyong piliin ang {$tFmt} para sa inyong pagbisita? At nais niyo po ba ng **Day Tour**, **Night Tour**, o **24-Hour Stay**? ☀️🌙\n\nTutulungan ko po kayong mahanap ang tamang amenity para sa inyong pagbisita! 😊";
            } else {
                return "{$tFmt} sounds like a wonderful date to visit Hinaguan Nature Park! 🌿 Are you sure you would like to pick {$tFmt} for your visit? Also, would you prefer a **Day Tour**, **Night Tour**, or **24-Hour Stay**? ☀️🌙\n\nI'll help you find the perfect amenity for your visit! 😊";
            }
        }

        // 5.24 Date confirmed acknowledgment when an amenity was already discussed or tentative
        if (!empty($bookingDraft['startDate']) && empty($bookingDraft['amenities']) && (!empty($bookingDraft['tentativeAmenity']) || !empty($bookingDraft['tentativeAmenities']) || !empty($bookingDraft['tentativeCategory']))) {
            $sdFmt = Carbon::parse($bookingDraft['startDate'])->format('F j, Y');
            $sl = $bookingDraft['startSlot'] ?? ($bookingDraft['slot'] ?? 'Daytime');
            $isNight = $sl === 'Nighttime';
            $tourName = $isNight ? 'Night Tour' : 'Day Tour';
            $tourIcon = $isNight ? '🌙' : '☀️';

            $targetAmenityStr = !empty($bookingDraft['tentativeAmenities'])
                ? implode(' and ', (array) $bookingDraft['tentativeAmenities'])
                : ($bookingDraft['tentativeAmenity'] ?? $bookingDraft['tentativeCategory']);

            $isAircon = stripos($targetAmenityStr, 'aircon') !== false || stripos($targetAmenityStr, 'air-condition') !== false;
            $priceStr = $isAircon ? ($isNight ? '₱1,000.00' : '₱700.00') : ($isNight ? '₱500.00' : '₱300.00');

            if ($isAircon) {
                if ($isBisaya) {
                    return "Nindot kaayo! {$tourIcon}💚 Ang **{$tourName} sa {$sdFmt}** para sa **Air-conditioned A-House** ({$priceStr}, apil ang libreng entrance ug pool access para sa 2) perpekto kaayo! 🌿✨\n\nAng among air-conditioned units kay **A-House 2** ug **A-House 3**. Pwede nako i-lock in ang **A-House 2** para nimo (o isulti lang kung mas gusto nimo ang A-House 3). Aron maandam ang imong digital reservation pass, pwede ba nako makuha ang imong **Tibuok Ngalan (Full Name)**? 😊";
                } elseif ($isTagalog) {
                    return "Napakaganda po! {$tourIcon}💚 Ang **{$tourName} ngayong {$sdFmt}** para sa **Air-conditioned A-House** ({$priceStr}, may libreng entrance at pool access para sa 2) ay napakagandang desisyon! 🌿✨\n\nAng aming air-conditioned units po ay **A-House 2** at **A-House 3**. Maaari ko po bang i-lock in ang **A-House 2** para sa inyo (o sabihin lamang kung nais ninyo ang A-House 3)? Upang maihanda po ang inyong pass, maaari ko po bang makuha ang inyong **Buong Pangalan (Full Name)**? 😊";
                } else {
                    return "Wonderful! {$tourIcon}💚 A **{$tourName} on {$sdFmt}** for the **Air-conditioned A-House** ({$priceStr}, includes free entrance and pool access for 2) sounds lovely! 🌿✨\n\nOur air-conditioned units are **A-House 2** and **A-House 3**. Shall I lock in **A-House 2** for you, or do you have a preference between A-House 2 and A-House 3? Also, to prepare your digital reservation pass, may I have your **Full Name**, please? 😊";
                }
            } else {
                if ($isBisaya) {
                    return "Nindot kaayo! {$tourIcon}💚 Ang **{$tourName} sa {$sdFmt}** para sa **{$targetAmenityStr}** perpekto kaayo! 🌿✨ Aron maandam ang imong digital pass, pwede ba nako makuha ang imong **Tibuok Ngalan (Full Name)**? 😊";
                } elseif ($isTagalog) {
                    return "Napakaganda po! {$tourIcon}💚 Ang **{$tourName} ngayong {$sdFmt}** para sa **{$targetAmenityStr}** ay perpekto! 🌿✨ Upang maihanda ang inyong pass, maaari ko po bang makuha ang inyong **Buong Pangalan (Full Name)**? 😊";
                } else {
                    return "Wonderful! {$tourIcon}💚 A **{$tourName} on {$sdFmt}** for **{$targetAmenityStr}** sounds lovely! 🌿✨ To prepare your digital reservation pass, may I have your **Full Name**, please? 😊";
                }
            }
        }

        // 5.25 Date confirmed acknowledgment (Schedule is locked in, show standard rates table as in Image 1!)
        if (!empty($bookingDraft['startDate']) && empty($bookingDraft['amenities']) && empty($bookingDraft['tentativeAmenity']) && empty($bookingDraft['tentativeAmenities']) && empty($bookingDraft['tentativeCategory'])) {
            $sdFmt = Carbon::parse($bookingDraft['startDate'])->format('F j, Y');
            $sl = $bookingDraft['startSlot'] ?? ($bookingDraft['slot'] ?? 'Daytime');
            $isNight = $sl === 'Nighttime';
            $tourName = $isNight ? 'Night Tour' : 'Day Tour';
            $tourIcon = $isNight ? '🌙' : '☀️';
            $colTitle = $isNight ? 'Night Rate' : 'Day Rate';

            $ahouseRegPrice = $isNight ? '₱500' : '₱300';
            $ahouseAirconPrice = $isNight ? '₱1,000' : '₱700';
            $cottagePrice = '₱200';
            $payagPrice = '₱300';
            $funcHallPrice = $isNight ? '₱10,000' : '₱5,000';

            $table = "| Amenity | {$colTitle} |\n"
                . "| :--- | :--- |\n"
                . "| Regular A-House (6 rooms) | {$ahouseRegPrice} |\n"
                . "| Aircon A-House (2 rooms) | {$ahouseAirconPrice} |\n"
                . "| Cottage (6 units) | {$cottagePrice} |\n"
                . "| Payag (6 units) | {$payagPrice} |\n"
                . "| Function Hall (1 unit) | {$funcHallPrice} |";

            if ($isBisaya) {
                return "Nindot kaayo! {$tourIcon}💚 Ang **{$tourName} sa {$sdFmt}** maayo kaayong paagi sa pag-relax sa Hinaguan Nature Park! 🌿✨\n\n"
                    . "Ania ang among amenities ug ang ilang mga rates:\n\n"
                    . $table . "\n\n"
                    . "Palihug timan-i nga standard rates kini. Ang actual availability kinahanglan pa nga susihon para sa {$sdFmt}.\n\n"
                    . "Unsa nga amenity ang nakapainteres nimo, o gusto ba nimo nga susihon nako ang live availability para kanimo? 🌿";
            } elseif ($isTagalog) {
                return "Napakaganda po! {$tourIcon}💚 Ang **{$tourName} sa {$sdFmt}** ay isang napakagandang paraan para mag-relax sa Hinaguan Nature Park! 🌿✨\n\n"
                    . "Narito po ang aming mga amenities at ang kanilang rates:\n\n"
                    . $table . "\n\n"
                    . "Pakitandaan po na ito ay ang aming standard rates. Ang actual availability ay kailangan pang beripikahin para sa {$sdFmt}.\n\n"
                    . "Aling amenity po ang nakapukaw sa inyong interes, o nais niyo po bang suriin ko ang live availability para sa inyo? 🌿";
            } else {
                return "Wonderful! {$tourIcon}💚 A **{$tourName} on {$sdFmt}** sounds like a lovely way to relax at Hinaguan Nature Park! 🌿✨\n\n"
                    . "Here are our amenities and their " . strtolower($colTitle) . "s:\n\n"
                    . $table . "\n\n"
                    . "Please note that these are our standard rates. Actual availability still needs to be verified for {$sdFmt}.\n\n"
                    . "Which one catches your eye, or would you like me to check live availability for you? 🌿";
            }
        }

        // 5.3 Amenity inquiry / selection (Make sure before recording, support multiple amenities!)
        // Only trigger if the guest's CURRENT message specifically mentions or inquires about amenities!
        $inquiredAmenities = $this->extractAllAmenitiesFromMessage($userMessage);

        if (!empty($inquiredAmenities) && empty($bookingDraft['amenities'])) {
            $sDate = $bookingDraft['startDate'] ?? ($bookingDraft['tentativeDate'] ?? null);
            $sDateFmt = $sDate ? Carbon::parse($sDate)->format('F j, Y') : null;
            $sSlot = $bookingDraft['startSlot'] ?? ($bookingDraft['slot'] ?? null);
            $hasExplicitSlot = !empty($sSlot) && in_array($sSlot, ['Daytime', 'Nighttime']);
            $isNight = $sSlot === 'Nighttime';

            if (count($inquiredAmenities) > 1) {
                // Multi-amenity inquiry response!
                $names = [];
                $bulletsEn = [];
                $bulletsBis = [];
                $bulletsTag = [];
                $totDay = 0;
                $totNight = 0;
                $totSelected = 0;

                foreach ($inquiredAmenities as $am) {
                    $amName = $am->amenities_name;
                    $names[] = $amName;
                    $dayP = (float) ($am->daytime_price ?? 0);
                    $nightP = (float) ($am->nighttime_price ?? 0);
                    $curP = $isNight ? $nightP : $dayP;
                    $totDay += $dayP;
                    $totNight += $nightP;
                    $totSelected += $curP;

                    $isAircon = (bool) ($am->benefits?->is_aircon ?? false);
                    $hasPool = (bool) ($am->benefits?->free_pool ?? false);

                    $descEn = $isAircon ? "Air-conditioned" : (str_contains(strtolower($amName), 'payag') ? "Open-air native hut" : "Cozy shelter");
                    if ($hasPool) {
                        $descEn .= ", includes free entrance & pool access for 2";
                    }
                    $curPFmt = "₱" . number_format($curP, 2);
                    $bulletsEn[] = "• **{$amName}** ({$curPFmt}): {$descEn}";

                    $descBis = $isAircon ? "Naay aircon" : (str_contains(strtolower($amName), 'payag') ? "Bukas nga payag" : "Komportable nga pahuwayan");
                    if ($hasPool) {
                        $descBis .= ", libreng entrance ug pool access para sa 2";
                    }
                    $bulletsBis[] = "• **{$amName}** ({$curPFmt}): {$descBis}";

                    $descTag = $isAircon ? "May aircon" : (str_contains(strtolower($amName), 'payag') ? "Bukas na payag" : "Komportableng pahingahan");
                    if ($hasPool) {
                        $descTag .= ", may libreng entrance at pool access para sa 2";
                    }
                    $bulletsTag[] = "• **{$amName}** ({$curPFmt}): {$descTag}";
                }

                $namesStr = implode(' and ', $names);
                $namesStrBis = implode(' ug ', $names);
                $namesStrTag = implode(' at ', $names);
                $totFmt = "₱" . number_format($totSelected, 2);
                $dp = round($totSelected * 0.5, 2);
                $dpFmt = "₱" . number_format($dp, 2);
                $bulletsEnStr = implode("\n", $bulletsEn);
                $bulletsBisStr = implode("\n", $bulletsBis);
                $bulletsTagStr = implode("\n", $bulletsTag);

                if ($sDateFmt && $hasExplicitSlot) {
                    if ($isBisaya) {
                        return "Available ang **{$namesStrBis}** para sa **{$sDateFmt} ({$sSlot})**! 🌿\n\n{$bulletsBisStr}\n\nKinatibuk-ang Total: **{$totFmt}** (50% downpayment: **{$dpFmt}**).\n\nSigurado ba ka nga gusto nimo i-reserve ang **{$namesStrBis}**, o gusto nimo nga ipakita nako ang tanang available nga amenities nianang petsaha?";
                    } elseif ($isTagalog) {
                        return "Available po ang **{$namesStrTag}** para sa **{$sDateFmt} ({$sSlot})**! 🌿\n\n{$bulletsTagStr}\n\nKabuuang Total: **{$totFmt}** (50% downpayment: **{$dpFmt}**).\n\nSigurado po ba kayong nais ninyong i-reserve ang **{$namesStrTag}**, o nais niyo po bang ipakita ko ang lahat ng available amenities sa petsang iyan?";
                    } else {
                        return "Both **{$namesStr}** are available for **{$sDateFmt} ({$sSlot})**! 🌿\n\n{$bulletsEnStr}\n\nCombined Total: **{$totFmt}** (50% required downpayment: **{$dpFmt}**).\n\nAre you sure you would like to reserve both **{$namesStr}**, or would you like me to show you all the available amenities for that date?";
                    }
                } elseif ($sDateFmt && !$hasExplicitSlot) {
                    $totDayFmt = "₱" . number_format($totDay, 2);
                    $totNightFmt = "₱" . number_format($totNight, 2);
                    if ($isBisaya) {
                        return "Available ang **{$namesStrBis}** para sa **{$sDateFmt}**! 🌿\n\n• Daytime Total: **{$totDayFmt}**\n• Nighttime Total: **{$totNightFmt}**\n\nGusto ba nimo i-reserve ang duha para sa **Daytime** o **Nighttime**, o gusto nimo nga ipakita nako ang tanang available nga amenities nianang petsaha?";
                    } elseif ($isTagalog) {
                        return "Available po ang **{$namesStrTag}** para sa **{$sDateFmt}**! 🌿\n\n• Daytime Total: **{$totDayFmt}**\n• Nighttime Total: **{$totNightFmt}**\n\nNais niyo po bang i-reserve ang pareho para sa **Daytime** o **Nighttime**, o nais niyo po bang ipakita ko ang lahat ng available amenities sa petsang iyan?";
                    } else {
                        return "Both **{$namesStr}** are available for **{$sDateFmt}**! 🌿\n\n• Daytime Total: **{$totDayFmt}**\n• Nighttime Total: **{$totNightFmt}**\n\nWould you like to reserve both for **Daytime** or **Nighttime**, or would you like me to show you all available amenities for that date?";
                    }
                } else {
                    if ($isBisaya) {
                        return "Available ang **{$namesStrBis}**! Unsa nga petsa ang imong plano sa pagbisita aron masusi nako ang availability?";
                    } elseif ($isTagalog) {
                        return "Available po ang **{$namesStrTag}**! Anong petsa po ang inyong balak bumisita upang masuri ko ang availability?";
                    } else {
                        return "Both **{$namesStr}** are available! What date are you planning to visit so I can check availability for you?";
                    }
                }
            } else {
                $amModel = $inquiredAmenities[0];
                $targetAmenityName = $amModel->amenities_name;
                $isAircon = (bool) ($amModel->benefits?->is_aircon ?? false);
                $hasFreePool = (bool) ($amModel->benefits?->free_pool ?? false);
                $dayPrice = (float) ($amModel->daytime_price ?? 0);
                $nightPrice = (float) ($amModel->nighttime_price ?? 0);
                $dayPriceFmt = "₱" . number_format($dayPrice, 2);
                $nightPriceFmt = "₱" . number_format($nightPrice, 2);

                $airconDescEn = $isAircon ? "air-conditioned, " : "";
                $airconDescBis = $isAircon ? "naay aircon, " : "";
                $airconDescTag = $isAircon ? "may aircon po ito, " : "";

                $inclDescEn = $hasFreePool ? "includes free entrance & swimming pool access for 2 guests" : "regular entrance (₱{$dayAdult}) and pool access (₱{$dayPool}) apply separately";
                $inclDescBis = $hasFreePool ? "libre na ang entrance ug pool access para sa 2 ka tawo" : "lahi ang entrance (₱{$dayAdult}) ug pool access (₱{$dayPool})";
                $inclDescTag = $hasFreePool ? "kasama na ang libreng entrance at pool access para sa 2 bisita" : "hiwalay po ang regular entrance (₱{$dayAdult}) at pool access (₱{$dayPool})";

                if ($sDateFmt && !$hasExplicitSlot) {
                    if ($isBisaya) {
                        return "Available ang **{$targetAmenityName}** para sa **{$sDateFmt}**! {$airconDescBis}{$inclDescBis} ({$dayPriceFmt} sa Daytime [{$dayStart} – {$dayEnd}] o {$nightPriceFmt} sa Nighttime [{$nightStart} – {$nightEnd}]).\n\nGusto ba nimo i-reserve ang {$targetAmenityName} para sa **Daytime** o **Nighttime**, o gusto nimo nga ipakita nako ang tanang available nga amenities nianang petsaha?";
                    } elseif ($isTagalog) {
                        return "Available po ang **{$targetAmenityName}** para sa **{$sDateFmt}**! {$airconDescTag}{$inclDescTag} ({$dayPriceFmt} sa Daytime [{$dayStart} – {$dayEnd}] o {$nightPriceFmt} sa Nighttime [{$nightStart} – {$nightEnd}]).\n\nNais niyo po bang i-reserve ang {$targetAmenityName} para sa **Daytime** o **Nighttime**, o nais niyo po bang ipakita ko ang lahat ng available amenities sa petsang iyan?";
                    } else {
                        return "{$targetAmenityName} is available for **{$sDateFmt}**! It is {$airconDescEn}{$inclDescEn} ({$dayPriceFmt} for Daytime [{$dayStart} – {$dayEnd}] or {$nightPriceFmt} for Nighttime [{$nightStart} – {$nightEnd}]).\n\nWould you like to reserve {$targetAmenityName} for **Daytime** or **Nighttime**, or would you like me to show you all available amenities for that date?";
                    }
                } elseif ($sDateFmt && $hasExplicitSlot) {
                    $chosenPriceFmt = $sSlot === 'Nighttime' ? $nightPriceFmt : $dayPriceFmt;
                    if ($isBisaya) {
                        return "Available ang **{$targetAmenityName}** ({$chosenPriceFmt}) para sa **{$sDateFmt} ({$sSlot})**! {$airconDescBis}{$inclDescBis}.\n\nSigurado ba ka nga gusto nimo i-reserve ang {$targetAmenityName}, o gusto ba nimo nga ipakita nako ang tanang available nga amenities nianang petsaha?";
                    } elseif ($isTagalog) {
                        return "Available po ang **{$targetAmenityName}** ({$chosenPriceFmt}) para sa **{$sDateFmt} ({$sSlot})**! {$airconDescTag}{$inclDescTag}.\n\nSigurado po ba kayong nais ninyong i-reserve ang {$targetAmenityName}, o nais niyo po bang ipakita ko ang lahat ng available amenities sa petsang iyan?";
                    } else {
                        return "{$targetAmenityName} ({$chosenPriceFmt}) is available for **{$sDateFmt} ({$sSlot})**! It is {$airconDescEn}{$inclDescEn}.\n\nAre you sure you would like to reserve {$targetAmenityName}, or would you like me to show you all the available amenities for that date?";
                    }
                } else {
                    if ($isBisaya) {
                        return "Ang **{$targetAmenityName}** kay {$airconDescBis}{$inclDescBis} ({$dayPriceFmt} Daytime / {$nightPriceFmt} Nighttime). Unsa nga petsa ang imong plano sa pagbisita aron masusi nako ang availability?";
                    } elseif ($isTagalog) {
                        return "Ang **{$targetAmenityName}** po ay {$airconDescTag}{$inclDescTag} ({$dayPriceFmt} Daytime / {$nightPriceFmt} Nighttime). Anong petsa po ang inyong balak bumisita upang masuri ko ang availability?";
                    } else {
                        return "{$targetAmenityName} is {$airconDescEn}{$inclDescEn} ({$dayPriceFmt} Daytime / {$nightPriceFmt} Nighttime). What date are you planning to visit so I can check availability for you?";
                    }
                }
            }
        }

        // 5.4 Primary Booker Details - Step 3 Sub-step 1: Full Name
        if (!empty($bookingDraft['amenities']) && empty($bookingDraft['guest']['name']) && empty($isGeneralFaq)) {
            $amNames = array_map(fn($a) => $a['name'] ?? 'Amenity', $bookingDraft['amenities']);
            $amNamesStr = implode(', ', $amNames);
            $sdFmt = !empty($bookingDraft['startDate']) ? Carbon::parse($bookingDraft['startDate'])->format('F j, Y') : null;
            $sl = $bookingDraft['startSlot'] ?? ($bookingDraft['slot'] ?? 'Daytime');
            $isNight = $sl === 'Nighttime';
            $tourName = $isNight ? 'Night Tour' : 'Day Tour';
            $tot = array_sum(array_map(fn($a) => (float) ($a['price'] ?? 0), $bookingDraft['amenities']));
            $totFmt = "₱" . number_format($tot, 2);
            $schedPartEn = $sdFmt ? " on **{$sdFmt} – {$tourName}**" : "";
            $schedPartBis = $sdFmt ? " para sa imong pagbisita sa **{$sdFmt} – {$tourName}**" : "";
            $schedPartTag = $sdFmt ? " para sa inyong pagbisita sa **{$sdFmt} – {$tourName}**" : "";

            $hasPhone = !empty($bookingDraft['guest']['phone']);
            $phoneStr = $hasPhone ? $bookingDraft['guest']['phone'] : '';

            if ($hasPhone) {
                if ($isBisaya) {
                    return "Salamat! 🌿💚 Gidugang na nako ang imong contact number (**{$phoneStr}**). Naka-lock in na ang **{$amNamesStr}** ({$totFmt}){$schedPartBis}!\n\nAron maandam nato ang imong digital reservation pass, mahimo ba nako makuha ang imong **Tibuok Ngalan (Full Name)**? 😊";
                } elseif ($isTagalog) {
                    return "Salamat po! 🌿💚 Naidagdag ko na po ang inyong contact number (**{$phoneStr}**). Naka-lock in na ang **{$amNamesStr}** ({$totFmt}){$schedPartTag}!\n\nUpang maihanda po natin ang inyong digital reservation pass, maaari po bang mahingi ang inyong **Buong Pangalan (Full Name)**? 😊";
                } else {
                    return "Thank you! 🌿💚 I've noted your contact number (**{$phoneStr}**). **{$amNamesStr}** ({$totFmt}) is locked in{$schedPartEn}!\n\nTo prepare your digital reservation pass, may I have your **Full Name**, please? 😊";
                }
            } else {
                if ($isBisaya) {
                    return "Nindot kaayo! Naka-lock in na ang **{$amNamesStr}** ({$totFmt}){$schedPartBis}! 🌿💚\n\nAron maandam nato ang imong digital reservation pass, mahimo ba nako makuha ang imong **Tibuok Ngalan (Full Name)**? 😊";
                } elseif ($isTagalog) {
                    return "Ayos po! Naka-lock in na ang **{$amNamesStr}** ({$totFmt}){$schedPartTag}! 🌿💚\n\nUpang maihanda po natin ang inyong digital reservation pass, maaari po bang mahingi ang inyong **Buong Pangalan (Full Name)**? 😊";
                } else {
                    return "Awesome! **{$amNamesStr}** ({$totFmt}) is locked in{$schedPartEn}! 🌿💚\n\nTo prepare your digital reservation pass, may I have your **Full Name**, please? 😊";
                }
            }
        }

        // 5.42 Primary Booker Details - Step 3 Sub-step 2: Mobile Number
        if (!empty($bookingDraft['amenities']) && !empty($bookingDraft['guest']['name']) && empty($bookingDraft['guest']['phone']) && empty($isGeneralFaq)) {
            $name = $bookingDraft['guest']['name'];
            $sdFmt = !empty($bookingDraft['startDate']) ? Carbon::parse($bookingDraft['startDate'])->format('F j, Y') : null;
            $sl = $bookingDraft['startSlot'] ?? ($bookingDraft['slot'] ?? 'Daytime');
            $isNight = $sl === 'Nighttime';
            $tourName = $isNight ? 'Night Tour' : 'Day Tour';
            $tourEmoji = $isNight ? '🌙🏡' : '☀️🏡';
            $schedPartEn = $sdFmt ? " for **{$sdFmt} – {$tourName}**. {$tourEmoji}" : ".";
            $schedPartBis = $sdFmt ? " para sa **{$sdFmt} – {$tourName}**. {$tourEmoji}" : ".";
            $schedPartTag = $sdFmt ? " para sa **{$sdFmt} – {$tourName}**. {$tourEmoji}" : ".";

            if ($isBisaya) {
                return "Salamat, {$name}! 🌿💚\n\nGidugang na nako ang imong ngalan sa imong booking details{$schedPartBis}\n\nKaron, mahimo ba nako makuha ang imong **mobile contact number**, palihug? 📱😊 Gamiton kini aron magpadala ug importanteng updates bahin sa imong reservation.";
            } elseif ($isTagalog) {
                return "Salamat po, {$name}! 🌿💚\n\nNaidagdag ko na ang inyong pangalan sa inyong booking details{$schedPartTag}\n\nNgayon po, maaari ko po bang mahingi ang inyong **mobile contact number**, please? 📱😊 Gagamitin po natin ito upang magpadala ng mahahalagang updates tungkol sa inyong reservation.";
            } else {
                return "Thank you, {$name}! 🌿💚\n\nI've added your name to your booking details{$schedPartEn}\n\nNow, may I have your **mobile contact number**, please? 📱😊 We'll use it to send important updates about your reservation.";
            }
        }

        // 5.44 Primary Booker Details - Step 3 Sub-step 3: Email Address
        if (!empty($bookingDraft['amenities']) && !empty($bookingDraft['guest']['name']) && !empty($bookingDraft['guest']['phone']) && empty($bookingDraft['guest']['email']) && empty($isGeneralFaq)) {
            $phone = $bookingDraft['guest']['phone'];

            if ($isBisaya) {
                return "Salamat! 🌿💚 Gidugang nako ang **{$phone}** isip imong contact number.\n\nHapit na ta mahuman sa imong booking details! 😊\n\nMahimo ba nako makuha ang imong **email address**, palihug? 📧 Gamiton kini aron ipadala ang imong booking confirmation ug resibo inigka-kumpirma sa imong reservation.";
            } elseif ($isTagalog) {
                return "Maraming salamat po! 🌿💚 Naidagdag ko na ang **{$phone}** bilang inyong contact number.\n\nHalos tapos na po tayo sa inyong booking details! 😊\n\nMaaari ko po bang mahingi ang inyong **email address**, please? 📧 Gagamitin po natin ito upang ipadala ang inyong booking confirmation at resibo kapag kumpirmado na ang inyong reservation.";
            } else {
                return "Thank you! 🌿💚 I've added **{$phone}** as your contact number.\n\nWe're almost done with your booking details! 😊\n\nMay I have your **email address**, please? 📧 We'll use it to send your booking confirmation and receipt once your reservation is confirmed.";
            }
        }

        // 5.46 Primary Booker Details - Step 3 Sub-step 4: Number of Guests (Pax)
        if (!empty($bookingDraft['amenities']) && !empty($bookingDraft['guest']['name']) && !empty($bookingDraft['guest']['phone']) && !empty($bookingDraft['guest']['email']) && empty($bookingDraft['guest']['guests_count']) && empty($isGeneralFaq)) {
            $email = $bookingDraft['guest']['email'];
            $maxCap = array_sum(array_map(fn($a) => (int) ($a['maximum_capacity'] ?? 10), $bookingDraft['amenities'])) ?: 10;

            if ($isBisaya) {
                return "Salamat! 🌿💚 Gidugang nako ang **{$email}** sa imong booking details.\n\nHapit na ta mahuman sa imong booking details! 😊\n\nPila ka **bisita (guests)** sa kinatibuk-an ang mokuyog kanimo niini nga pagbisita? 👥 (Maximum capacity para sa imong napiling amenities: {$maxCap} pax)";
            } elseif ($isTagalog) {
                return "Maraming salamat po! 🌿💚 Naidagdag ko na ang **{$email}** sa inyong booking details.\n\nHalos tapos na po tayo sa inyong booking details! 😊\n\nIlang **bisita (guests)** po sa kabuuan ang kasama ninyong bibisita para sa stay na ito? 👥 (Maximum capacity para sa inyong napiling amenities: {$maxCap} pax)";
            } else {
                return "Thank you! 🌿💚 I've added **{$email}** to your booking details.\n\nWe're almost done with your booking details! 😊\n\nHow many **guests** in total will be visiting with you? 👥 (Maximum capacity for your selected amenities: {$maxCap} pax)";
            }
        }

        // 5.5 Step 4: Reservation Summary & 50% Downpayment Button (STRICTLY requires all 4 details: name, phone, email, guests_count)
        if (!empty($bookingDraft['guest']['name']) && !empty($bookingDraft['guest']['phone']) && !empty($bookingDraft['guest']['email']) && !empty($bookingDraft['guest']['guests_count']) && !empty($bookingDraft['amenities'])) {
            $bookerName = $bookingDraft['guest']['name'];
            $phone = $bookingDraft['guest']['phone'] ?? '';
            $email = $bookingDraft['guest']['email'] ?? '';
            $pax = !empty($bookingDraft['guest']['guests_count']) ? (int) $bookingDraft['guest']['guests_count'] : 1;
            $amNames = array_map(fn($a) => $a['name'] ?? 'Amenity', $bookingDraft['amenities']);
            $amNamesStr = implode(', ', $amNames);

            // If visit date was not chosen yet, ask for date to finalize schedule!
            if (empty($bookingDraft['startDate'])) {
                if ($isBisaya) {
                    return "Daghang salamat, **{$bookerName}**! 🌿💚 Narekord na nako ang imong detalye ({$pax} ka bisita para sa **{$amNamesStr}**).\n\nAron makompleto ang summary ug mopadayon sa 50% downpayment, unsang petsa ug session (Daytime o Nighttime) ang imong plano sa pagbisita?";
                } elseif ($isTagalog) {
                    return "Maraming salamat po, **{$bookerName}**! 🌿💚 Naitala ko na po ang inyong detalye ({$pax} bisita para sa **{$amNamesStr}**).\n\nUpang makumpleto ang summary at makapagpatuloy sa 50% downpayment, anong petsa at session (Daytime o Nighttime) po ang inyong balak bumisita?";
                } else {
                    return "Thank you so much, **{$bookerName}**! 🌿💚 I have recorded your booking details ({$pax} guests for **{$amNamesStr}**).\n\nTo complete your reservation summary and proceed to the 50% downpayment, what target date and session (Daytime o Nighttime) would you like to visit Hinaguan Nature Park?";
                }
            }

            $sdFmt = Carbon::parse($bookingDraft['startDate'])->format('F j, Y');
            $sl = $bookingDraft['startSlot'] ?? ($bookingDraft['slot'] ?? 'Daytime');
            $isNight = $sl === 'Nighttime';
            $tourName = $isNight ? 'Night Tour' : 'Day Tour';
            $tot = array_sum(array_map(fn($a) => (float) ($a['price'] ?? 0), $bookingDraft['amenities']));
            $totFmt = "₱" . number_format($tot, 2);
            $dp = round($tot * 0.5, 2);
            $dpFmt = "₱" . number_format($dp, 2);
            $bal = round($tot - $dp, 2);
            $balFmt = "₱" . number_format($bal, 2);

            if ($isBisaya) {
                return "Daghang salamat, **{$bookerName}**! 🌿💚 Ania ang katingbanan sa imong reservation:\n\n"
                    . "• 🛖 Amenity: **{$amNamesStr}**\n"
                    . "• 📅 Schedule: **{$sdFmt} – {$tourName}**\n"
                    . "• 👤 Primary Guest: **{$bookerName}** (" . ($phone ?: 'No phone') . ", " . ($email ?: 'No email') . ")\n"
                    . "• 👥 Gidaghanon sa Bisita: **{$pax} pax**\n"
                    . "• 💵 Total Amount: **{$totFmt}**\n"
                    . "• 💳 Gikinahanglan nga 50% Downpayment: **{$dpFmt}** (Strictly Non-Refundable)\n"
                    . "• ⏳ Balanse sa Check-in: **{$balFmt}** (bayran sa entrance)\n\n"
                    . "Kung andam na ka, i-tap ang button sa ubos aron mabayran ang 50% downpayment aron ma-finalize ang imong booking! 😊\n[PROCEED_PAYMENT_BUTTON]";
            } elseif ($isTagalog) {
                return "Maraming salamat po, **{$bookerName}**! 🌿💚 Narito po ang buod ng inyong reservation:\n\n"
                    . "• 🛖 Amenity: **{$amNamesStr}**\n"
                    . "• 📅 Schedule: **{$sdFmt} – {$tourName}**\n"
                    . "• 👤 Primary Guest: **{$bookerName}** (" . ($phone ?: 'No phone') . ", " . ($email ?: 'No email') . ")\n"
                    . "• 👥 Bilang ng Bisita: **{$pax} pax**\n"
                    . "• 💵 Kabuuang Halaga: **{$totFmt}**\n"
                    . "• 💳 Kinakailangang 50% Downpayment: **{$dpFmt}** (Strictly Non-Refundable)\n"
                    . "• ⏳ Natitirang Balanse sa Check-in: **{$balFmt}** (babayaran pagdating sa park)\n\n"
                    . "Kapag handa na po kayo, i-tap lamang ang button sa ibaba upang bayaran ang 50% downpayment para ma-finalize ang inyong booking! 😊\n[PROCEED_PAYMENT_BUTTON]";
            } else {
                return "Thank you so much, **{$bookerName}**! 🌿💚 Here is the summary of your reservation:\n\n"
                    . "• 🛖 Amenity: **{$amNamesStr}**\n"
                    . "• 📅 Schedule: **{$sdFmt} – {$tourName}**\n"
                    . "• 👤 Primary Guest: **{$bookerName}** (" . ($phone ?: 'No phone') . ", " . ($email ?: 'No email') . ")\n"
                    . "• 👥 Number of Guests: **{$pax} pax**\n"
                    . "• 💵 Total Amount: **{$totFmt}**\n"
                    . "• 💳 Required 50% Downpayment: **{$dpFmt}** (Strictly Non-Refundable)\n"
                    . "• ⏳ Remaining Balance: **{$balFmt}** (payable upon check-in)\n\n"
                    . "Whenever you're ready, tap below to settle the 50% downpayment to finalize your reservation! 😊\n[PROCEED_PAYMENT_BUTTON]";
            }
        }

        // 6. Direct Booking Mode command: /book or booking intent
        if (trim($msgLower) === '/book' || preg_match('/^(?:book\s+via\s+chat|i\s+want\s+to\s+book|gusto\s+(?:ko|kami)\s+mag-?book|pwede\s+mag-?book|mag-?book\s+ko|magpa-?reserve\s+ko)$/i', $msgLower)) {
            $startDate = $bookingDraft['startDate'] ?? ($bookingDraft['date'] ?? null);
            if ($startDate) {
                $slot = $bookingDraft['startSlot'] ?? ($bookingDraft['slot'] ?? 'Daytime');
                if ($isBisaya) {
                    return "Naka-set na ang imong schedule para sa **{$startDate} ({$slot})**! 📅\n\nUnsang amenity ang imong gustong i-reserve? Naa mi mga cozy **A-Houses**, lumad nga **Payags**, **Cottages**, o **Function Hall**.\n\nGusto ba nimo nga ipakita nako ang tanang available nga amenities nianang petsaha?";
                } elseif ($isTagalog) {
                    return "Naka-set na po ang inyong schedule para sa **{$startDate} ({$slot})**! 📅\n\nAnong amenity po ang nais ninyong i-reserve? Mayroon kaming cozy **A-Houses**, katutubong **Payags**, **Cottages**, o **Function Hall**.\n\nGusto niyo po bang ipakita ko ang available amenities sa petsang iyan?";
                } else {
                    return "Your schedule is currently set to **{$startDate} ({$slot})**! 📅\n\nWhich amenity would you like to reserve? We have cozy **A-Houses**, native bamboo **Payags**, open-air **Cottages**, and our **Grand Function Hall**.\n\nWould you like me to show you the available amenities on that date?";
                }
            }

            if ($isBisaya) {
                return "Malipayon kaayo ko nga motabang sa imong pag-book dinhi sa chat! 🌿\n\nKanus-a ang imong plano nga petsa sa pagbisita? Ug kini ba para sa **single-day visit** (Daytime: {$dayStart} – {$dayEnd} o Nighttime: {$nightStart} – {$nightEnd}) o **multi-day stay**?";
            } elseif ($isTagalog) {
                return "Ikinagagalak ko pong tulungan kayo sa inyong pag-book dito sa chat! 🌿\n\nKailan po ang inyong planong petsa ng pagbisita? At ito po ba ay para sa **single-day visit** (Daytime: {$dayStart} – {$dayEnd} o Nighttime: {$nightStart} – {$nightEnd}) o **multi-day stay**?";
            } else {
                return "I'd love to help you book your stay at Hinaguan Nature Park! 🌿\n\nWhat date are you planning to visit? And is it for a **single-day visit** (Daytime: {$dayStart} – {$dayEnd} / Nighttime: {$nightStart} – {$nightEnd}) or a **multi-day stay**?";
            }
        }

        // 7. Book via Chat Inquiry (e.g. "can I book via chat?", "how to book via chat", "pwede ba mag book sa chat")
        if (preg_match('/(?:book\s+via\s+chat|book\s+sa\s+chat|mag-?book\s+sa\s+chat|pwede\s+(?:ba\s+)?(?:mag-?)?book\s+dinhi|dito\s+ba\s+mag-?book)/i', $msgLower)) {
            if ($isBisaya) {
                return "Oo, pwede kaayo ta mag-book dinhi mismo sa chat! 🌿\n\nKanus-a ang imong plano nga petsa sa pagbisita? Ug kini ba para sa **single-day visit** (Daytime: {$dayStart} – {$dayEnd} o Nighttime: {$nightStart} – {$nightEnd}) o **multi-day stay**?";
            } elseif ($isTagalog) {
                return "Opo, pwedeng-pwede po tayong mag-book dito mismo sa chat! 🌿\n\nKailan po ang inyong planong petsa ng pagbisita? At ito po ba ay para sa **single-day visit** (Daytime: {$dayStart} – {$dayEnd} o Nighttime: {$nightStart} – {$nightEnd}) o **multi-day stay**?";
            } else {
                return "Yes, you can certainly book your reservation right here in our chat! 🌿\n\nWhat date are you planning to visit? And is it for a **single-day visit** (Daytime: {$dayStart} – {$dayEnd} / Nighttime: {$nightStart} – {$nightEnd}) or a **multi-day stay**?";
            }
        }

        // 8. How to Book Online Inquiry (supports chip: "How do I book an amenity online?")
        $isBookingInquiry = (bool) preg_match('/(?:how\s+(?:do\s+i|to|can\s+i)\s+(?:book|reserve)|paano\s+(?:mag-?)?(?:book|reserve)|unsaon\s+pag-?(?:book|reserve)|steps?\s+to\s+(?:book|reserve)|online\s+booking|booking\s+process|procedure|downpayment|down\s*payment|no\s*refund|non-?refundable|refund\s*policy)/i', $msgLower);
        if ($isBookingInquiry) {
            $startDate = $bookingDraft['startDate'] ?? ($bookingDraft['date'] ?? null);
            $slot = $bookingDraft['startSlot'] ?? ($bookingDraft['slot'] ?? 'Daytime');

            if ($startDate) {
                if ($isBisaya) {
                    return "Sayon ra kaayo mag-book dinhi sa chat! 🌿 Naa na tay target schedule nga **{$startDate} ({$slot})**.\n\nGusto ba nimo nga ipakita nako ang tanang available nga amenities nianang petsaha?";
                } elseif ($isTagalog) {
                    return "Napakadali lang pong mag-book dito sa chat! 🌿 Naka-set na po ang ating target schedule sa **{$startDate} ({$slot})**.\n\nGusto niyo po bang ipakita ko ang lahat ng available amenities sa petsang iyan?";
                } else {
                    return "Booking is super easy right here in chat! 🌿 We already have your target schedule set for **{$startDate} ({$slot})**.\n\nWould you like me to show you all the available amenities for that date?";
                }
            }

            if ($isBisaya) {
                return "Sayon ra kaayo mag-book! Pwede kaayo ta mag-book dinhi mismo sa chat (o gamit ang 'Book Now' sa website) 🌿\n\nKini lang ang atong gikinahanglan:\n1️⃣ Imong target nga petsa sa pagbisita ug session (Daytime: {$dayStart} – {$dayEnd} o Nighttime: {$nightStart} – {$nightEnd})\n2️⃣ Imong pilion nga amenity (Payag ₱300, Cottage ₱200, o A-House ₱300/₱500)\n3️⃣ Contact details ug ang gikinahanglan nga **50% DOWNPAYMENT** (**STRICTLY NON-REFUNDABLE**) aron ma-confirm ang slot.\n\nKanus-a ang imong plano nga petsa sa pagbisita? Ug daytime ba o nighttime?";
            } elseif ($isTagalog) {
                return "Napakadali lang pong mag-book! Pwedeng-pwede po tayong mag-book dito mismo sa chat (o gamit ang 'Book Now' sa website) 🌿\n\nIto lang po ang ating kailangan:\n1️⃣ Inyong target na petsa ng pagbisita at session (Daytime: {$dayStart} – {$dayEnd} o Nighttime: {$nightStart} – {$nightEnd})\n2️⃣ Inyong nais na amenity (Payag ₱300, Cottage ₱200, o A-House ₱300/₱500)\n3️⃣ Contact details at ang kinakailangang **50% DOWNPAYMENT** (**STRICTLY NON-REFUNDABLE**) upang ma-confirm ang reservation.\n\nKailan po ang inyong planong petsa ng pagbisita? At daytime po ba o nighttime?";
            } else {
                return "Booking is super easy! You can book right here with me conversationally (or on our website) 🌿\n\nAll we need is:\n1️⃣ Your target visit date & session (Daytime: {$dayStart} – {$dayEnd} or Nighttime: {$nightStart} – {$nightEnd})\n2️⃣ Your preferred amenity (Payags ₱300, Cottages ₱200, or A-Houses ₱300/₱500)\n3️⃣ Your contact info and the required **50% DOWNPAYMENT** (**STRICTLY NON-REFUNDABLE**) to secure your reservation.\n\nWhat date were you planning to visit? And are you thinking of a daytime or nighttime stay?";
            }
        }

        // 9. Available Amenities & Complete Rates Catalog (supports chip: "What amenities are available today and what are their rates?")
        $isAmenityCatalog = (bool) preg_match('/(?:what\s+amenit|available\s+amenit|list\s+of\s+amenit|show\s+(?:all\s+)?amenit|rates?\s+of\s+amenit|all\s+amenit|catalog|mga\s+amenit|unsay\s+amenit|unsa\s+inyong\s+amenit|anong\s+amenit|ano\s+ang\s+mga\s+amenit|options?\s+(?:sa|ng|for)\s+amenit)/i', $msgLower)
            || str_contains($msgLower, 'what amenities are available today and what are their rates');

        if ($isAmenityCatalog) {
            $weatherNote = $isRainy ? " *(Weather note: today has rain showers, so our covered A-Houses & Function Hall are top picks!)*" : " *(Weather note: today is pleasant and sunny, perfect for riverside payags & swimming!)*";

            if ($isBisaya) {
                return "Mao kini ang among mga available nga amenities ug rates sa Hinaguan Nature Park:{$weatherNote}\n\n"
                    . "• 🛖 **Native Payags (Payag 1 to 6)** — ₱300 (Daytime o Nighttime)\n"
                    . "  - Kapasidad: 1 hangtod 8 ka tawo. Gihimo gikan sa presko nga kawayan; duol sa sapa ang Payag 2 ug 6!\n\n"
                    . "• 🏡 **Cottages (Cottage 1 to 6)** — ₱200 (Daytime o Nighttime)\n"
                    . "  - Kapasidad: hangtod 10 ka tawo. May open-air dining tables ug lingkoranan. Walay corkage fee sa pagkaon!\n\n"
                    . "• 🏕️ **A-Houses (A-House 1 to 8)** — ₱300 Daytime / ₱500 Nighttime\n"
                    . "  - Kapasidad: 1 hangtod 2 ka tawo. **APIL NA ANG LIBRENG ENTRANCE UG POOL ACCESS para sa 2 ka bisita!** (Naay aircon ang A-House 2 ug 3).\n\n"
                    . "• 🏛️ **Grand Function Hall** — ₱5,000 Daytime / ₱10,000 Nighttime\n"
                    . "  - Kapasidad: 15 hangtod 50+ ka tawo. **APIL NA ANG LIBRENG ENTRANCE UG POOL ACCESS para sa tibuok grupo!**\n\n"
                    . "📌 *Pahinumdom: Para sa Cottages ug Payags, lahi ang regular entrance fee (₱{$dayAdult}) ug pool access (₱{$dayPool}).*\n\n"
                    . "I-click ang **Book via Chat** button sa ibabaw aron masusi ang mga bakanteng petsa ug makapa-book dayon!";
            } elseif ($isTagalog) {
                return "Narito po ang aming mga available na amenities at rates sa Hinaguan Nature Park:{$weatherNote}\n\n"
                    . "• 🛖 **Native Payags (Payag 1 to 6)** — ₱300 (Daytime o Nighttime)\n"
                    . "  - Kapasidad: 1 hanggang 8 tao. Gawa sa preskong kawayan; malapit sa ilog ang Payag 2 at 6!\n\n"
                    . "• 🏡 **Cottages (Cottage 1 to 6)** — ₱200 (Daytime o Nighttime)\n"
                    . "  - Kapasidad: hanggang 10 tao. May dining tables at upuan. Walang corkage fee sa pagkain!\n\n"
                    . "• 🏕️ **A-Houses (A-House 1 to 8)** — ₱300 Daytime / ₱500 Nighttime\n"
                    . "  - Kapasidad: 1 hanggang 2 tao. **KASAMA NA ANG LIBRENG ENTRANCE AT SWIMMING POOL ACCESS para sa 2 guests!** (Aircon ang A-House 2 at 3).\n\n"
                    . "• 🏛️ **Grand Function Hall** — ₱5,000 Daytime / ₱10,000 Nighttime\n"
                    . "  - Kapasidad: 15 hanggang 50+ tao. **KASAMA NA ANG LIBRENG ENTRANCE AT POOL ACCESS para sa buong grupo!**\n\n"
                    . "📌 *Paalala: Sa Cottages at Payags, hiwalay po ang regular entrance (₱{$dayAdult}) at pool fee (₱{$dayPool}).*\n\n"
                    . "I-click lamang po ang **Book via Chat** button sa itaas upang tingnan ang bakanteng petsa at mag-reserve!";
            } else {
                return "Here are the available amenities and rates at Hinaguan Nature Park:{$weatherNote}\n\n"
                    . "• 🛖 **Native Bamboo Payags (Payag 1 to 6)** — ₱300 (Daytime or Nighttime)\n"
                    . "  - Capacity: Up to 8 guests. Authentic bamboo huts with natural breeze; Payag 2 & 6 sit right by the river!\n\n"
                    . "• 🏡 **Open-Air Cottages (Cottage 1 to 6)** — ₱200 (Daytime or Nighttime)\n"
                    . "  - Capacity: Up to 10 guests. Shaded wooden dining tables and benches. Outside food allowed with NO corkage!\n\n"
                    . "• 🏕️ **Cozy A-Houses (A-House 1 to 8)** — ₱300 Daytime / ₱500 Nighttime\n"
                    . "  - Capacity: 1 to 2 guests. **INCLUDES FREE ENTRANCE & FREE SWIMMING POOL ACCESS for 2 guests!** (A-House 2 & 3 feature air-conditioning).\n\n"
                    . "• 🏛️ **Grand Function Hall** — ₱5,000 Daytime / ₱10,000 Nighttime\n"
                    . "  - Capacity: 15 to 50+ guests. **INCLUDES FREE GROUP ENTRANCE & SWIMMING POOL ACCESS!** Perfect for birthdays and family reunions.\n\n"
                    . "📌 *Policy note: Regular entrance (₱{$dayAdult} daytime adult) and pool access (₱{$dayPool}/person) apply separately for Cottages and Payags.*\n\n"
                    . "Click the green **Book via Chat** button above to check live dates and reserve your spot!";
            }
        }

        // 10. Amenity Suggestions & Recommendations (e.g. "suggest me an amenity pls", "recommend an amenity")
        $hasActiveBookingContext = !empty($bookingDraft['active'])
            || !empty($bookingDraft['amenities'])
            || !empty($bookingDraft['startDate'])
            || !empty($bookingDraft['guest']['name'])
            || !empty($bookingDraft['guest']['email']);

        $isSuggestion = (bool) preg_match('/(?:suggest|recommend|rekomenda|bagay|swak|best\s+amenit|maganda(?:ng)?\s+(?:amenit|cottage|payag|ahouse|a-house)|nindot\s+(?:nga\s+)?(?:amenit|cottage|payag|ahouse|a-house)|what\s+(?:should|can)\s+i\s+(?:book|choose|pick|avail|get)|anong\s+(?:magandang|masarap|bagay|swak)|unsa\s+(?:ang\s+)?(?:nindot|bagay|maayo|swak)|which\s+(?:amenit|cottage|payag|hut|house)|pili\s+ng\s+amenit|pilig\s+amenit)/i', $msgLower)
            && !preg_match('/(?:how\s+to\s+go|how\s+to\s+get|directions?|paano\s+pumunta|unsaon\s+pag-?adto|unsaon\s+pagsulod|asa\s+dapit|commute|spring\s*view|solana|jasaan)\b/i', $msgLower);

        // Group size recommendation: ONLY trigger if explicitly asking for suggestion/recommendation AND NOT in the middle of active booking!
        $isExplicitGroupSuggestion = $isSuggestion || (bool) preg_match('/(?:recommend|suggest|what\s+is\s+best\s+for|bagay\s+sa|swak\s+sa|swak\s+para\s+sa|which\s+amenity\s+for)\s+.*(?:\d+|couple)/i', $msgLower);

        $isCouple = (bool) preg_match('/\b(?:couple|mag-?asawa|magkasintahan|girlfriend|boyfriend|gf|bf|wife|husband|partner|asawa|uyab|pares)\b/i', $userMessage);

        if ((!$hasActiveBookingContext || $isCouple) && $isExplicitGroupSuggestion) {

            // Group size recommendation (e.g. "for 10 pax", "group of 10 people", or "couple")
            if ($isCouple || preg_match('/(\d+)\s*(?:people|persons|pax|guests|heads|kabuok|ka\s+tao|tao)/i', $userMessage, $m) ||
                preg_match('/for\s+(\d+)/i', $userMessage, $m)) {
                $pax = $isCouple ? 2 : (int) ($m[1] ?? 2);

            if ($pax >= 1 && $pax <= 2) {
                if ($isBisaya) {
                    return "Para sa inyong duha sa imong uyab/partner, ang among mga A-Houses (Regular sa ₱300 daytime / ₱500 nighttime, o Air-conditioned sa ₱700 daytime / ₱1,000 nighttime) ang labing bagay kay cozy ug apil na ang libreng entrance ug swimming pool access para sa duha! Pwede sab mo mag-relax sa katutubong Payag (₱300) o open-air Cottage (₱200).";
                } elseif ($isTagalog) {
                    return "Para po sa inyong dalawa ng girlfriend/partner mo, bagay na bagay ang aming A-Houses (Regular sa ₱300 daytime / ₱500 nighttime, o Air-conditioned sa ₱700 daytime / ₱1,000 nighttime) dahil napaka-cozy at may kasama nang libreng entrance at swimming pool access para sa inyong dalawa! Pwede rin kayo mag-relax sa katutubong Payag (₱300) o open-air Cottage (₱200).";
                } else {
                    return "For you and your girlfriend, our cozy A-Houses are ideal! We offer Regular A-Houses (₱300 daytime / ₱500 nighttime) and Air-conditioned A-Houses (₱700 daytime / ₱1,000 nighttime)—both include free entrance and swimming pool access for 2 guests! You can also unwind in an open-air Cottage (₱200) or bamboo Payag (₱300).";
                }
            } elseif ($pax <= 10) {
                $weatherTip = $isRainy ? " (Both are covered, perfect for today's weather!)" : " (Cottage 1-6 features open-air dining tables, while Payags offer authentic bamboo relaxation!)";
                if ($isBisaya) {
                    return "Para sa grupo nga {$pax} ka tawo, girekomenda namo ang among mga Cottage (Cottage 1-6 sa ₱200) o native Payags (Payag 1-6 sa ₱300).{$weatherTip} Pwede magdala ug pagkaon nga walay corkage!";
                } elseif ($isTagalog) {
                    return "Para po sa {$pax} tao, pinaka-swak ang aming mga Cottage (Cottage 1-6 sa ₱200) o katutubong Payag (Payag 1-6 sa ₱300). Walang corkage fee sa pagkain!";
                } else {
                    return "For a group of {$pax} guests, our open-air Cottages (Cottage 1 to 6 at ₱200) or native Payags (Payag 1 to 6 at ₱300) are the perfect choice!{$weatherTip} No corkage fee for outside food.";
                }
            } else {
                if ($isBisaya) {
                    return "Para sa dakong grupo nga {$pax} ka tawo, ang among Function Hall (₱5,000 day / ₱10,000 night, apil na free entrance ug pool access) o pag-book ug daghang cottages ang pinakamaayo!";
                } else {
                    return "For a large group of {$pax} guests, our Grand Function Hall (₱5,000 day / ₱10,000 night for 15-50+ pax, includes free entrance and pool access) or booking multiple adjacent cottages would be ideal!";
                }
            }
        }
    }

        // General amenity suggestion without specific pax (e.g. "suggest me an amenity pls")
        if ($isSuggestion) {
            if ($isBisaya) {
                return "Malipayon ko nga morekomenda sa labing angay nga amenity para kanimo sa Hinaguan Nature Park! 🌿\n\n"
                    . "• 🏕️ **Para sa Couples o Overnight (1-2 ka tawo)**: Ang among **A-Houses** (₱300 daytime / ₱500 nighttime) ang pinakanindot kay **apil na ang libreng entrance ug libreng pool access** para sa inyong duha!\n\n"
                    . "• 🛖 **Para sa Pamilya ug Grupo (3-10 ka tawo)**:\n"
                    . "  - **Native Payags (₱300)**: Kung ganahan mo ug presko nga hangin ug tingog sa sapa, girekomenda namo ang **Payag 2 o 6** (riverside) o **Payag 3** (mountain breeze).\n"
                    . "  - **Cottages (₱200)**: May mga lamesa ug lingkoranan, maayo kaayo para sa picnic ug salo-salo (walay corkage fee sa pagkaon!).\n\n"
                    . "• 🏛️ **Para sa Dakong Grupo o Okasyon (15-50+ ka tawo)**: Ang among **Function Hall** (₱5,000 daytime) nga may libreng pool ug entrance access para sa tibuok grupo!\n\n"
                    . "Pila kabuok ang inyong grupo, ug kanus-a ang inyong plano nga pagbisita? Pwede sab nimo i-click ang **Book via Chat** sa ibabaw aron makasugod!";
            } elseif ($isTagalog) {
                return "Ikinagagalak ko pong irekomenda ang pinaka-swak na amenity para sa inyong pagbisita sa Hinaguan Nature Park! 🌿\n\n"
                    . "• 🏕️ **Para sa Couples o Overnight (1-2 tao)**: Pinakamaganda ang aming **A-Houses** (₱300 daytime / ₱500 nighttime) dahil **kasama na ang libreng entrance at libreng swimming pool access** para sa inyong 2!\n\n"
                    . "• 🛖 **Para sa Pamilya at Barkada (3-10 tao)**:\n"
                    . "  - **Native Payags (₱300)**: Kung nais niyo ng preskong simoy ng hangin at tunog ng tubig, piliin ang **Payag 2 o 6** (tabi ng ilog) o **Payag 3** (mountain breeze).\n"
                    . "  - **Cottages (₱200)**: May mga dining table at upuan, perpekto para sa salo-salo (walang corkage fee sa pagkain!).\n\n"
                    . "• 🏛️ **Para sa Malaking Grupo o Reunion (15-50+ tao)**: Ang aming **Function Hall** (₱5,000 daytime) na may libreng pool access at entrance para sa buong grupo!\n\n"
                    . "Ilan po kayong bibisita at anong petsa ang inyong plano? Maaari rin po ninyong i-click ang **Book via Chat** sa itaas upang mag-reserve!";
            } else {
                return "I'd be delighted to recommend the best amenity for your visit to Hinaguan Nature Park! 🌿\n\n"
                    . "• 🏕️ **For Couples or Overnight Stays (1-2 guests)**: Our cozy **A-Houses** (₱300 daytime / ₱500 nighttime) are ideal and include **FREE park entrance & FREE swimming pool access** for 2 guests! (A-House 2 & 3 have aircon).\n\n"
                    . "• 🛖 **For Families & Groups (3-10 guests)**:\n"
                    . "  - **Native Payags (₱300)**: For authentic bamboo relaxation with soothing river sounds, we recommend **Payag 2 or 6** (riverside) or **Payag 3** (mountain breeze).\n"
                    . "  - **Open-Air Cottages (₱200)**: Great for daytime dining and picnics, with NO corkage fee on outside food!\n\n"
                    . "• 🏛️ **For Large Groups & Celebrations (15-50+ guests)**: Our **Grand Function Hall** (₱5,000 daytime / ₱10,000 nighttime) includes FREE entrance & pool access for your entire group!\n\n"
                    . "How many guests are in your group, and when do you plan to visit? You can also click the green **Book via Chat** button above to browse available dates!";
            }
        }

        // 11. Celebrity Owner Brenda Mage Presence & Availability
        $isBrendaInquiry = (bool) preg_match('/(?:brenda|brenda\s*mage|celebrity|tag[\s-]?iya|may[\s-]?ari|owner)/i', $msgLower);
        if ($isBrendaInquiry) {
            if ($brendaIsHere) {
                if ($isBisaya) {
                    return "🌟 **Oo! Anaa si Brenda Mage sa park karong adlawa!** Ang celebrity owner nga si Brenda Mage anaa sa Hinaguan Nature Park karon. Naay kahigayonan ang mga bisita nga makakita ug makapa-picture uban kaniya samtang naglingaw-lingaw sa resort!";
                } elseif ($isTagalog) {
                    return "🌟 **Opo! Nasa park po si Brenda Mage ngayon!** Ang aming celebrity owner na si Brenda Mage ay kasalukuyang nasa Hinaguan Nature Park. May pagkakataon po ang mga bisita na makita at magpa-picture kasama siya habang nagrerelax sa park!";
                } else {
                    return "🌟 **Yes! Celebrity owner Brenda Mage is in the park today!** Brenda Mage is currently on-site at Hinaguan Nature Park. Guests have the wonderful opportunity to meet Brenda and take photos while enjoying our resort and scenery!";
                }
            } else {
                if ($isBisaya) {
                    return "Wala sa park si Brenda Mage karong adlawa, apan abli kaayo ang Hinaguan Nature Park aron mohatag kaninyo ug nindot ug makalingaw nga kasinatian sa kinaiyahan ug swimming pool!";
                } elseif ($isTagalog) {
                    return "Wala po sa park si Brenda Mage ngayon, ngunit bukas na bukas po ang Hinaguan Nature Park upang magbigay sa inyo ng magandang karanasan sa kalikasan, swimming pools, at payag stays!";
                } else {
                    return "Celebrity owner Brenda Mage is not on-site at the park today, but Hinaguan Nature Park is fully open and ready to welcome you with our relaxing pools, riverside payags, and nature stays!";
                }
            }
        }

        // 12. Park Rules, Outside Food, Corkage, Pets, Grilling, Alcohol, Parking
        $isRulesInquiry = (bool) preg_match('/(?:rule|rules|bawal|polic|corkage|outside\s+food|pagkaon|pagkain|food|drink|inom|alcohol|beer|liquor|pet|pets|dog|iro|aso|cat|grill|grilling|ihaw|sugba|barbecue|bbq|parking|parkanan|smoking|sigarilyo|vape|swimwear|attire)/i', $msgLower)
            && !preg_match('/(?:how\s+to\s+go|how\s+to\s+get|directions?|paano\s+pumunta|unsaon\s+pag-?adto|unsaon\s+pag\s+adto|asa\s+dapit|commute|sakyan|sakay|byahe|biyahe)\b/i', $msgLower);
        if ($isRulesInquiry) {
            if (preg_match('/(?:corkage|outside\s+food|pagkaon|pagkain|food|dala\s+(?:ug\s+)?pagkaon|baon|grill|ihaw|sugba)/i', $msgLower)) {
                if ($isBisaya) {
                    return "🍱 **Pagkaon ug Grilling Policy**:\n"
                        . "• **PWEDE KAAYO MAGDALA UG PAGKAON UG ILIMNON NGA WALAY CORKAGE FEE!**\n"
                        . "• Naa mi **libre nga grilling / sugbahanan stations** para sa tanang bisita (pagdala lang mo ug kaugalingong uling).\n"
                        . "• Tugot ang alcoholic drinks sa kasarangan nga gidaghanon (hangyo lang namo ang pagmintinar sa kalinaw sa park).";
                } elseif ($isTagalog) {
                    return "🍱 **Pagkain at Grilling Policy**:\n"
                        . "• **PWEDENG-PWEDE PONG MAGDALA NG SARILING PAGKAIN AT INUMIN NANG WALANG CORKAGE FEE!**\n"
                        . "• Mayroon kaming **libreng grilling stations** para sa lahat ng bisita (magdala lamang po ng sariling uling).\n"
                        . "• Pinapayagan ang alak nang katamtaman lamang upang mapanatili ang kapayapaan at katahimikan sa park.";
                } else {
                    return "🍱 **Outside Food & Grilling Policy**:\n"
                        . "• **Outside food and drinks are 100% WELCOME with NO CORKAGE FEE!**\n"
                        . "• We offer **free grilling stations** for all guests (please bring your own charcoal).\n"
                        . "• Alcoholic beverages are permitted in moderation, keeping the park peaceful and family-friendly.";
                }
            }

            if (preg_match('/(?:pet|pets|dog|iro|aso|cat)/i', $msgLower)) {
                if ($isBisaya) {
                    return "🐾 **Pet Policy**: Pet-friendly kaayo ang Hinaguan Nature Park! Welcome ang inyong mga binuhi basta nakahigot (on leash) ug gipanghinloan sa tag-iya ang ilang hugaw.";
                } elseif ($isTagalog) {
                    return "🐾 **Pet Policy**: Pet-friendly po ang Hinaguan Nature Park! Malugod pong tinatanggap ang inyong mga alaga basta't nakatali (on leash) at panatilihing malinis ang paligid.";
                } else {
                    return "🐾 **Pet Policy**: Hinaguan Nature Park is pet-friendly! Pets are warmly welcome on park grounds as long as they are kept on a leash and owners clean up after them.";
                }
            }

            $dbRules = ParkRule::all();
            $dbRulesText = '';
            if ($dbRules->isNotEmpty()) {
                $dbRulesText = "\n" . $dbRules->map(fn($r) => "• **{$r->rule_name}**: {$r->rule_descriptions}")->implode("\n");
            }

            if ($isBisaya) {
                return "Mao kini ang mga sumbanan ug patakaran sa Hinaguan Nature Park:\n"
                    . "• 🍱 **Walay Corkage Fee**: Pwede magdala ug kaugalingong pagkaon ug ilimnon. Naay libreng grilling stations.\n"
                    . "• 🐾 **Pet-Friendly**: Tugot ang mga binuhing mananap basta nakahigot ug limpiyohan ang hugaw.\n"
                    . "• 🏊 **Pool Attire**: Pagsul-ob ug saktong swimwear (bawal ang denim o cotton shirts sa pool).\n"
                    . "• 🚗 **Free Parking**: Naay luag ug libreng parkinganan sa sakyanan ug motor.\n"
                    . "• 🚭 **Smoking & Alcohol**: Sa designated areas lang ang panigarilyo; kasarangan lang ang pag-inom aron magpabiling hapsay."
                    . $dbRulesText;
            } elseif ($isTagalog) {
                return "Narito po ang mga alituntunin at patakaran sa Hinaguan Nature Park:\n"
                    . "• 🍱 **Walang Corkage Fee**: Malugod na pinapayagan ang sariling pagkain at inumin. May libreng ihawan.\n"
                    . "• 🐾 **Pet-Friendly**: Pwedeng magdala ng alaga basta't nakatali at nililinis ang dumi.\n"
                    . "• 🏊 **Pool Attire**: Wastong kasuotang panlangoy (bawal ang maong o bulak na t-shirt sa pool).\n"
                    . "• 🚗 **Free Parking**: May maluwag at libreng parking space para sa kotse at motor.\n"
                    . "• 🚭 **Smoking & Alak**: Sa tamang designated areas lamang ang paninigarilyo; bawal ang labis na kalasingan."
                    . $dbRulesText;
            } else {
                return "Here are the essential park rules and policies at Hinaguan Nature Park:\n"
                    . "• 🍱 **No Corkage Fee**: You are welcome to bring outside food and drinks! Free grilling stations available.\n"
                    . "• 🐾 **Pet-Friendly**: Pets are allowed on leash; please clean up after your pets.\n"
                    . "• 🏊 **Pool Attire**: Proper swimming attire required (no cotton shirts or denim in the pools).\n"
                    . "• 🚗 **Free Parking**: Spacious complimentary parking for cars, vans, and motorcycles.\n"
                    . "• 🚭 **Smoking & Alcohol**: Smoking/vaping in designated areas only; alcoholic drinks permitted in moderation."
                    . $dbRulesText;
            }
        }

        // 13. Entrance Fees & Operating Sessions / Hours (supports chip: "What are the entrance fees and operating sessions?")
        $isFeesOrHoursInquiry = (bool) preg_match('/(?:entrance|admission|ticket|bayad\s+sa\s+entrance|bayranan|gate\s+fee|pool\s+fee|rates?|fees?|prices?|presyo|oras|hours?|schedule|opening|closing|operating\s*sessions?|what\s+time|anong\s+oras|unsang\s+orasa|open|close)/i', $msgLower);
        if ($isFeesOrHoursInquiry) {
            if ($isBisaya) {
                return "Mao kini ang among Operating Sessions ug Entrance / Pool Fees sa Hinaguan Nature Park:\n\n"
                    . "• ⏰ **Oras sa Pag-abli (Operating Sessions)**:\n"
                    . "  - **Daytime Session**: {$dayStart} hangtod {$dayEnd} (Gate abli: {$openTime} – {$closeTime})\n"
                    . "  - **Nighttime Session (Overnight Stays)**: {$nightStart} hangtod {$nightEnd} sunod adlaw\n\n"
                    . "• 🎟️ **Entrance Fees**:\n"
                    . "  - **Daytime Entrance**: ₱{$dayAdult} matag hamtong (adult) | **LIBRE ang 12 anyos paubos!**\n"
                    . "  - **Nighttime Entrance**: ₱{$nightAdult} matag hamtong (adult) | **LIBRE ang 12 anyos paubos!**\n\n"
                    . "• 🏊 **Swimming Pool Access**:\n"
                    . "  - Daytime Pool: ₱{$dayPool} matag tawo\n"
                    . "  - Nighttime Pool: ₱{$nightPool} matag tawo\n"
                    . "  *(Libre ang entrance ug pool kung mag-book sa A-House o Function Hall!)*\n\n"
                    . "Gusto ba nimo magsusi ug cottage o mag-book karon? I-click lang ang **Book via Chat** sa ibabaw!";
            } elseif ($isTagalog) {
                return "Narito po ang aming Operating Sessions at Entrance / Pool Fees sa Hinaguan Nature Park:\n\n"
                    . "• ⏰ **Oras at Sessions (Operating Sessions)**:\n"
                    . "  - **Daytime Session**: {$dayStart} hanggang {$dayEnd} (Gate: {$openTime} – {$closeTime})\n"
                    . "  - **Nighttime Session (Overnight Stays)**: {$nightStart} hanggang {$nightEnd} kinabukasan\n\n"
                    . "• 🎟️ **Entrance Fees**:\n"
                    . "  - **Daytime Entrance**: ₱{$dayAdult} bawat adult | **LIBRE po ang mga batang 12 taong gulang pababa!**\n"
                    . "  - **Nighttime Entrance**: ₱{$nightAdult} bawat adult | **LIBRE po ang mga batang 12 taong gulang pababa!**\n\n"
                    . "• 🏊 **Swimming Pool Access**:\n"
                    . "  - Daytime Pool: ₱{$dayPool} bawat tao\n"
                    . "  - Nighttime Pool: ₱{$nightPool} bawat tao\n"
                    . "  *(Kasama na po ang libreng entrance at pool kapag naka-book sa A-House o Function Hall!)*\n\n"
                    . "Nais niyo po bang magpa-reserve? I-click lamang ang **Book via Chat** button sa itaas!";
            } else {
                return "Here are the Operating Sessions and Entrance / Pool Fees at Hinaguan Nature Park:\n\n"
                    . "• ⏰ **Park Operating Sessions**:\n"
                    . "  - **Daytime Session**: {$dayStart} – {$dayEnd} (Main Gate open: {$openTime} to {$closeTime})\n"
                    . "  - **Nighttime Session (Overnight Stays)**: {$nightStart} – {$nightEnd} next morning\n\n"
                    . "• 🎟️ **Park Entrance Fees**:\n"
                    . "  - **Daytime Entrance**: ₱{$dayAdult} per adult | **FREE for children 12 years old and below!**\n"
                    . "  - **Nighttime Entrance**: ₱{$nightAdult} per adult | **FREE for children 12 years old and below!**\n\n"
                    . "• 🏊 **Swimming Pool Access**:\n"
                    . "  - Daytime Pool: ₱{$dayPool} per person\n"
                    . "  - Nighttime Pool: ₱{$nightPool} per person\n"
                    . "  *(Note: Swimming pool and park entrance are included FREE with A-House and Function Hall bookings!)*\n\n"
                    . "Would you like to check available cottages or reserve a slot? Click **Book via Chat** above!";
            }
        }

        // 14. How to Go / Directions Inquiry
        $isDirectionsInquiry = (bool) preg_match('/(?:how\s+to\s+go|how\s+to\s+get|directions?|paano\s+pumunta|unsaon\s+pag-?adto|unsaon\s+pag-?anhi|location|address|asa\s+dapit|saan\s+banda|saan\s+ang|spring\s*view|jasaan|solana|commute|byahe|inner\s*road|inner\s*way|google\s*map|waze)/i', $msgLower);
        if ($isDirectionsInquiry) {
            if ($isBisaya) {
                return "Unsaon pag-adto sa Hinaguan Nature Park:\n"
                    . "• Lokasyon: Barangay Solana, Jasaan, Misamis Oriental.\n"
                    . "• Landmark sa Highway: Manaog sa Jasaan, dapit sa sikat nga swimming pool resort nga gitawag ug 'Spring View Resort' (human gyud sa taytayan sa Solana).\n"
                    . "• Agianan: Gikan sa highway duol sa Spring View Resort, sudla ang sulod nga agianan (inner road) paingon sa suba ug bukid.\n"
                    . "• Sakyanan: Pwede mosakay ug habal-habal (motorcycle taxi) diretso sa park, o magdala ug kaugalingong sakyanan.\n"
                    . "• Navigation: Pwede kaayo nimo i-check ug sundon sa Google Maps o Waze pinaagi sa pag-search sa 'Hinaguan Nature Park' o 'Spring View Resort'!";
            } elseif ($isTagalog) {
                return "Paano pumunta sa Hinaguan Nature Park:\n"
                    . "• Lokasyon: Barangay Solana, Jasaan, Misamis Oriental.\n"
                    . "• Landmark sa Highway: Bumaba sa Jasaan, sa may tapat ng pampublikong swimming pool resort na tinatawag na 'Spring View Resort' (pagkalampas ng Solana Bridge).\n"
                    . "• Daan: Mula sa highway sa tapat ng Spring View Resort, pumasok sa inner road (panloob na daan) patungo sa ilog.\n"
                    . "• Sasakyan: Maaari kayong sumakay ng habal-habal (motorcycle taxi) papasok sa park o mag-drive ng sariling sasakyan.\n"
                    . "• Navigation: Maaari niyo rin itong i-check at i-navigate gamit ang Google Maps o Waze (i-search lamang ang 'Hinaguan Nature Park' o 'Spring View Resort')!";
            } else {
                return "How to get to Hinaguan Nature Park:\n"
                    . "• Location: Barangay Solana, Jasaan, Misamis Oriental.\n"
                    . "• Landmark & Drop-off: Tell your driver to drop you off in Solana, Jasaan, right by the well-known swimming pool resort called 'Spring View Resort' (just past Solana Bridge).\n"
                    . "• Route: From the highway at Spring View Resort, enter and take the inner road heading inland toward the river.\n"
                    . "• Local Transport: Take a local 'habal-habal' (motorcycle taxi) directly to the park gate, or drive your private vehicle.\n"
                    . "• Google Maps / Waze: You can easily search and navigate on Google Maps or Waze by typing 'Hinaguan Nature Park' or 'Spring View Resort'!";
            }
        }

        // 15. Park Activities Inquiry (from park_activities table)
        $isActivitiesInquiry = (bool) preg_match('/(?:activit|unsa(?:ng)?\s*(?:mabuhat|buhaton)|ano(?:ng)?\s*(?:pwedeng\s*)?gawin|things\s+to\s+do|river\s*swim|pictorial|picture|swimming|nature)/i', $msgLower);
        if ($isActivitiesInquiry) {
            $acts = ParkActivity::all();
            if ($acts->isNotEmpty()) {
                $actList = $acts->map(fn ($a) => "• {$a->activity}: {$a->description}")->implode("\n");
                if ($isBisaya) {
                    return "Mao kini ang mga nindot nga kalihokan (activities) sa Hinaguan Nature Park:\n{$actList}\n\nPwede sab mo mag-relax sa among riverside payags ug mag-swimming sa pool!";
                } elseif ($isTagalog) {
                    return "Narito po ang mga puwedeng gawin (activities) sa Hinaguan Nature Park:\n{$actList}\n\nPuwede rin po kayong mag-relax sa aming riverside payag at mag-swimming sa pool!";
                } else {
                    return "Here are the wonderful activities you can enjoy at Hinaguan Nature Park:\n{$actList}\n\nYou can also relax by the scenic riverbanks or take a swim in our pool!";
                }
            }
        }

        // 16. Reservation Status Lookup (if user provided a reservation number)
        if (preg_match('/(?:res(?:ervation)?|booking|boking)\s*(?:#|no\.?|num(?:ber)?)?\s*(\d+)/i', $msgLower, $m)) {
            $resId = (int) $m[1];
            $res = Reservation::with(['reservationAmenities.amenity'])->find($resId);
            if ($res) {
                $ams = $res->reservationAmenities->map(fn ($ra) => $ra->amenity?->amenities_name ?? 'Amenity')->implode(', ');
                $stay = "{$res->reservation_date} [{$res->start_slot}]";
                return "Reservation #{$res->id} for {$res->booker_name} is currently [{$res->status}]. Stay date: {$stay}. Booked amenities: " . ($ams ?: 'None') . ". Remaining balance: ₱" . number_format($res->remaining_balance, 2) . " ({$res->payment_status}).";
            }
        }

        // 17. Weather inquiry
        if (str_contains($msgLower, 'weather') || str_contains($msgLower, 'panahon') || str_contains($msgLower, 'klima') || str_contains($msgLower, 'ulan') || str_contains($msgLower, 'uwan')) {
            if ($isBisaya) {
                $advice = $isRainy ? "Tungod kay naay uwan, maayo mag-book sa among covered A-Houses o Function Hall para komportable mo." : "Nindot kaayo ang panahon para mag-langoy sa pool o mag-relax sa riverside Payag 2 o 6!";
                return "Ang panahon karon sa Hinaguan kay {$weatherCond} ({$weatherTemp}°C, {$weatherRain}% tsansa sa uwan). {$advice}";
            } elseif ($isTagalog) {
                $advice = $isRainy ? "Dahil may tsansa ng ulan, mainam mag-book sa aming covered A-Houses o Function Hall." : "Napakaganda po ng panahon ngayon para mag-swimming sa pool o mag-relax sa aming riverside Payag!";
                return "Ang lagay ng panahon ngayon sa Hinaguan ay {$weatherCond} ({$weatherTemp}°C, {$weatherRain}% tsansa ng ulan). {$advice}";
            } else {
                $advice = $isRainy ? "Since there is a chance of rain, we recommend our covered A-Houses or Function Hall to stay dry and comfortable." : "It is a wonderful day to enjoy our swimming pool and riverside Payag 2 or 6!";
                return "Today's weather at Hinaguan Nature Park is {$weatherCond} ({$weatherTemp}°C with {$weatherRain}% chance of rain). {$advice}";
            }
        }

        // 18. Location / Vibe inquiry (Riverside, Mountain breeze, Garden shade, Scenic view)
        if (str_contains($msgLower, 'river') || str_contains($msgLower, 'suba') || str_contains($msgLower, 'ilog')) {
            if ($isBisaya) {
                return "Kung gusto kag duol sa suba nga presko ug relaxing, girekomenda namo ang Payag 2 ug Payag 6 (₱300)! Naa kini dapit sa sapa nga gilibutan sa kalasangan.";
            } elseif ($isTagalog) {
                return "Kung nais niyo po ng malapit sa ilog at presko, highly recommended ang Payag 2 at Payag 6 (₱300)! Tamang-tama sa tabi ng tubig at sariwang hangin.";
            } else {
                return "For a refreshing riverside vibe by the running water, we highly recommend Payag 2 or Payag 6 (₱300)! Both are authentic native huts right by the river.";
            }
        }

        if (str_contains($msgLower, 'mountain') || str_contains($msgLower, 'breeze') || str_contains($msgLower, 'hangin') || str_contains($msgLower, 'bukid')) {
            if ($isBisaya) {
                return "Para sa refreshing mountain breeze ug hilom nga lugar, ang Payag 3 (₱300) ang pinakanindot para sa gamay nga grupo o pamilya!";
            } else {
                return "For a refreshing mountain breeze in a peaceful setting, Payag 3 (₱300) is the perfect choice for relaxing and quiet picnics!";
            }
        }

        if (str_contains($msgLower, 'garden') || str_contains($msgLower, 'shade') || str_contains($msgLower, 'landong') || str_contains($msgLower, 'kahoy')) {
            if ($isBisaya) {
                return "Ang Payag 5 (₱300) nahimutang sa ilawom sa mga landong nga kahoy sa tanaman, bugnaw ug presko kaayo puy-an!";
            } else {
                return "Payag 5 (₱300) is nestled in a serene garden haven under lush shade trees, keeping it cool and comfortable all day!";
            }
        }

        // 19. Specific amenity inquiries (Cottage / Payag / A-House / Function Hall)
        if (str_contains($msgLower, 'cottage') || str_contains($msgLower, 'kubo') || str_contains($msgLower, 'shed')) {
            if ($isBisaya) {
                return "Ang among mga Cottage (Cottage 1 to 6) kay ₱200 para sa daytime o nighttime, naay lamesa ug lingkoranan para sa pamilya hangtod 10 ka tawo (lahi ang entrance nga ₱{$dayAdult} ug pool nga ₱{$dayPool}).";
            } else {
                return "Our open-air Cottages (Cottage 1 to 6) are ₱200 for daytime or nighttime use, featuring dining tables and chairs for up to 10 guests. Regular entrance (₱{$dayAdult}/adult) and pool access (₱{$dayPool}/person) apply separately.";
            }
        }

        if (str_contains($msgLower, 'payag') || str_contains($msgLower, 'hut') || str_contains($msgLower, 'bamboo')) {
            if ($isBisaya) {
                return "Ang among mga native Payag (Payag 1 to 6) kay ₱300, ginama sa kawayan nga presko kaayo para sa 1 hangtod 8 ka tawo. Duol sa suba ang Payag 2 ug 6!";
            } else {
                return "Our native bamboo Payags (Payag 1 to 6) are ₱300 for daytime or nighttime use, offering shade and breeze for up to 8 guests. Payag 2 and 6 are riverside!";
            }
        }

        if (str_contains($msgLower, 'a-house') || str_contains($msgLower, 'ahouse') || str_contains($msgLower, 'cabin') || str_contains($msgLower, 'overnight') || str_contains($msgLower, 'room')) {
            if ($isBisaya) {
                return "Ang among A-Houses (A-House 1 to 8) kay ₱300 sa adlaw ug ₱500 sa gabii (1 to 2 ka tawo), naay aircon sa A-House 2 ug 3, ug libre na ang entrance ug pool access!";
            } else {
                return "Our A-Houses (A-House 1 to 8) are ₱300 daytime and ₱500 nighttime (1 to 2 guests). A-House 2 & 3 are air-conditioned, and all A-Houses include free entrance and swimming pool access!";
            }
        }

        if (str_contains($msgLower, 'function hall') || str_contains($msgLower, 'event') || str_contains($msgLower, 'hall') || str_contains($msgLower, 'wedding') || str_contains($msgLower, 'party')) {
            return "Our Function Hall is ₱5,000 for daytime and ₱10,000 for nighttime (accommodating 15 to 50+ guests). It includes free entrance and free pool access for your group!";
        }

        // 20. Intelligent default response
        if ($isBisaya) {
            return "Maayong adlaw! Welcome sa Hinaguan Nature Park sa Jasaan, Misamis Oriental 🌿\n\nMakatabang ko nimo sa pag-inquire o pag-book. Mahimo kang mangutana bahin sa:\n• 🛖 **Amenities & Rates** (Cottages ₱200, Payags ₱300, A-Houses ₱300/₱500)\n• 🎟️ **Entrance Fees & Oras** (₱{$dayAdult} daytime entrance / 8 AM – 5 PM)\n• 📍 **Direksyon sa Park** (Barangay Solana duol sa Spring View Resort)\n• 📝 **Pag-book Online** (o i-click ang **Book via Chat** sa ibabaw)\n\nUnsay imong gustong mahibaloan?";
        } elseif ($isTagalog) {
            return "Magandang araw! Maligayang pagdating sa Hinaguan Nature Park sa Jasaan, Misamis Oriental 🌿\n\nNandito po ako upang tumulong sa inyong pagbisita o pag-book. Maaari kayong magtanong tungkol sa:\n• 🛖 **Amenities & Rates** (Cottages ₱200, Payags ₱300, A-Houses ₱300/₱500)\n• 🎟️ **Entrance Fees & Oras** (₱{$dayAdult} daytime entrance / 8 AM – 5 PM)\n• 📍 **Direksyon papunta** (Barangay Solana katapat ng Spring View Resort)\n• 📝 **Paano mag-book** (o i-click ang **Book via Chat** sa itaas)\n\nAno po ang nais ninyong alamin?";
        } else {
            return "Welcome to **Hinaguan Nature Park** in Jasaan, Misamis Oriental! 🌿\n\nI am **Bren**, your AI Assistant. I can assist you with:\n• 🛖 **Amenities & Rates** (Cottages ₱200, Payags ₱300, A-Houses ₱300/₱500, Function Hall)\n• 🎟️ **Entrance Fees & Hours** (₱{$dayAdult} daytime entrance / 8:00 AM – 5:00 PM)\n• 📍 **Directions & Commute** (Barangay Solana near Spring View Resort)\n• 📝 **Booking Online or via Chat** (Click **Book via Chat** above!)\n\nWhat would you like to know or reserve today?";
        }
    }

    /**
     * Intelligent topic detector for Guest AI assistant across all 12 permitted tables.
     * Supports misspellings, colloquial expressions, Bisaya, Tagalog, and English.
     */
    private function detectGuestTopics(string $message): array
    {
        $msg = ' ' . strtolower($message) . ' ';
        $topics = [
            'is_specific' => false,
            'specific_entity' => false,
            'specific_id' => null,
            'search_terms' => [],
            'booking_guide' => false,
            'directions' => false,
            'amenities' => false,
            'weather' => false,
            'activities' => false,
            'events' => false,
            'rules' => false,
            'feedbacks' => false,
            'reservations' => false,
        ];

        // 1. Specific Reservation / Booking ID (e.g., "res #12", "booking 5", "reservation 104")
        if (preg_match('/(?:res(?:ervation)?|booking|boking|ref(?:erence)?)\s*(?:#|no\.?|num(?:ber)?)?\s*(\d+)/i', $msg, $m)) {
            $topics['specific_id'] = (int) $m[1];
            $topics['specific_entity'] = true;
            $topics['reservations'] = true;
            $topics['is_specific'] = true;
        } elseif (preg_match('/#(\d+)\b/', $msg, $m)) {
            $topics['specific_id'] = (int) $m[1];
            $topics['specific_entity'] = true;
            $topics['reservations'] = true;
            $topics['is_specific'] = true;
        }

        // 2. Search for booker name, email, or phone number
        if (preg_match('/(?:under|name\s*is|para\s*kang|kay|booker|guest)\s+([A-Za-z]{2,}(?:\s+[A-Za-z]{2,})*)/i', $message, $m)) {
            $candidate = trim($m[1]);
            if (!in_array(strtolower($candidate), ['hinaguan', 'nature park', 'spring view', 'jasaan', 'solana', 'cottage', 'payag', 'ahouse', 'pool'])) {
                $topics['search_terms'][] = $candidate;
                $topics['specific_entity'] = true;
                $topics['reservations'] = true;
                $topics['is_specific'] = true;
            }
        }
        if (preg_match('/(09\d{9}|\+639\d{9})/', $message, $m)) {
            $topics['search_terms'][] = $m[1];
            $topics['specific_entity'] = true;
            $topics['reservations'] = true;
            $topics['is_specific'] = true;
        }

        // 3. Online Booking Guide Intent (with typo tolerance & multilingual)
        if (preg_match('/(?:how\s+to\s+book|paano\s+(?:mag-?)?book|unsaon\s+pag-?book|unsaon\s+pag-?reserve|paano\s+mag-?reserve|steps?\s+to\s+book|online\s+booking|book\s+now|downpayment|down\s*payment|no\s*refund|non-?refundable|refund\s*policy|dp\b|unsaon\s+pagpa-?reserve)/i', $msg)) {
            $topics['booking_guide'] = true;
            $topics['is_specific'] = true;
        }

        // 4. Directions / How to Go Intent (Spring View, Solana, Jasaan, inner road, commute, Google Maps)
        if (preg_match('/(?:how\s+to\s+go|how\s+to\s+get|directions?|paano\s+pumunta|unsaon\s+pag-?adto|unsaon\s+pag-?anhi|paano\s+makarating|location|address|asa\s+dapit|saan\s+banda|saan\s+ang|spring\s*view|jasaan|solana|commute|byahe|sakay|inner\s*road|inner\s*way|google\s*map|waze|landmark)/i', $msg)) {
            $topics['directions'] = true;
            $topics['is_specific'] = true;
        }

        // 5. Amenities, Rates & Availability (cottage, payag, a-house, function hall, pool, rates, dates)
        if (preg_match('/(?:amenit|cottage|cotag|kotats|payag|pyag|a-?house|ahouse|function\s*hall|kubo|pool|swim|rate|price|tagpila|magkano|pila|entrance|bayad|presyo)/i', $msg)) {
            $topics['amenities'] = true;
            $topics['is_specific'] = true;
        }
        if (preg_match('/(?:availab|when|date|petsa|kanus-?a|kailan|bakante|vacant|free|open|occup|schedule)/i', $msg)) {
            $topics['availability'] = true;
            $topics['is_specific'] = true;
        }

        // 6. Weather & Climate
        if (preg_match('/(?:weather|panahon|klima|ulan|uwan|rain|init|sunny|temp|forecast|tugnaw|bagyo)/i', $msg)) {
            $topics['weather'] = true;
            $topics['is_specific'] = true;
        }

        // 7. Park Activities (park_activities)
        if (preg_match('/(?:activit|unsa(?:ng)?\s*(?:mabuhat|buhaton)|ano(?:ng)?\s*(?:pwedeng\s*)?gawin|things\s+to\s+do|river\s*swim|pictorial|picture|photo|relax|hike|adventure)/i', $msg)) {
            $topics['activities'] = true;
            $topics['is_specific'] = true;
        }

        // 8. Park Events (park_events)
        if (preg_match('/(?:event|happening|kalingawan|selebrasyon|occasion|fiesta|party|gathering|schedule)/i', $msg)) {
            $topics['events'] = true;
            $topics['is_specific'] = true;
        }

        // 9. Park Rules & Policies (park_rules)
        if (preg_match('/(?:rule|policy|pet|corkage|food|dala\s*pagkaon|pwede\s*ba|bawal|allowed|prohibit|oras|open|close|operating\s*hour)/i', $msg)) {
            $topics['rules'] = true;
            $topics['is_specific'] = true;
        }

        // 10. Reviews & Feedbacks (feedbacks)
        if (preg_match('/(?:feedback|review|rating|star|comment|kasinatian|opinyon|testimoni)/i', $msg)) {
            $topics['feedbacks'] = true;
            $topics['is_specific'] = true;
        }

        // 11. General Reservations / Booking inquiry
        if (preg_match('/(?:reserv|booking|boking|status|check-?in|check\s*my)/i', $msg)) {
            $topics['reservations'] = true;
        }

        // 12. Conversational Booking Intent & Picks Inquiry
        if (preg_match('/(?:i\s+want\s+to\s+book|gusto\s+(?:ko|kami)\s+mag-?book|pwede\s+mag-?book|book\s+(?:a\s+)?(?:cottage|payag|a-?house|room|cabin|function\s*hall|reservation)|magpa-?reserve\s+ko|book\s+via\s+chat|\/book\b)/i', $msg)) {
            $topics['booking_mode'] = true;
            $topics['is_specific'] = true;
        }

        if (preg_match('/(?:show\s+(?:me\s+)?(?:what\s+(?:amenit(?:y|ies)?\s+)?i\s+(?:just\s+)?avail|my\s+(?:pick|picks|selection|draft|booking)|the\s+record)|what\s+did\s+i\s+pick|unsa\s+akong\s+gipili|patingin\s+ng\s+pinili\s*ko|view\s+pick)/i', $msg)) {
            $topics['show_picks'] = true;
            $topics['is_specific'] = true;
        }

        return $topics;
    }

    /**
     * Proactively extract tentative/proposed booking items (amenities, dates, guests)
     * from the assistant's reply so that the frontend's localStorage draft is already primed.
     */
    private function proactivelyExtractProposedBookingDetails(string $reply, ?array &$draft): void
    {
        if (!is_array($draft)) {
            $draft = [];
        }

        // 1. Proactively extract proposed amenities or amenity replacements
        $replyActions = $this->extractAmenityActionsFromMessage($reply);
        if (!empty($replyActions['removed']) && !empty($draft['amenities'])) {
            $removedIds = array_map(fn($m) => (string) $m->id, $replyActions['removed']);
            $draft['amenities'] = array_values(array_filter(
                $draft['amenities'],
                fn($am) => !in_array((string)($am['id'] ?? ''), $removedIds)
            ));
            if (empty($draft['amenities'])) {
                $draft['amenity'] = null;
            } else {
                $draft['amenity'] = $draft['amenities'][0];
            }
        }

        if (!empty($replyActions['wanted'])) {
            $isNight = ($draft['startSlot'] ?? ($draft['tentativeSlot'] ?? 'Daytime')) === 'Nighttime';
            $proposedItems = [];
            foreach ($replyActions['wanted'] as $am) {
                // Function Hall should NEVER be proactively extracted as a proposed/tentative amenity unless explicitly requested or pax >= 15
                if (stripos($am->amenities_name, 'Function Hall') !== false) {
                    continue;
                }
                $price = $isNight ? (float) ($am->nighttime_price ?? 0) : (float) ($am->daytime_price ?? 0);
                $proposedItems[] = [
                    'id' => (string) $am->id,
                    'name' => $am->amenities_name,
                    'price' => $price,
                    'daytime_price' => (float) ($am->daytime_price ?? 0),
                    'nighttime_price' => (float) ($am->nighttime_price ?? 0),
                    'minimum_capacity' => (int) ($am->minimum_capacity ?? 1),
                    'maximum_capacity' => (int) ($am->maximum_capacity ?? 10),
                ];
            }

            $isCatalogOrRates = (count($replyActions['wanted']) >= 3)
                || (bool) preg_match('/(?:rate|price|table|here are our|rates:)/i', $reply);

            if (!$isCatalogOrRates && ($replyActions['is_keep_only'] || $replyActions['is_switch'] || !empty($replyActions['removed']))) {
                if (!empty($proposedItems)) {
                    $draft['amenities'] = $proposedItems;
                    $draft['amenity'] = $proposedItems[0];
                    $draft['tentativeAmenities'] = null;
                    $draft['tentativeAmenity'] = null;
                    $draft['active'] = true;
                }
            } elseif (!$isCatalogOrRates && empty($draft['amenities']) && !empty($proposedItems)) {
                $draft['tentativeAmenities'] = array_map(fn($a) => $a['name'], $proposedItems);
                $draft['tentativeAmenity'] = implode(', ', $draft['tentativeAmenities']);
                $draft['active'] = true;
            }
        }

        // 2. Proactively extract proposed dates if none locked in yet
        if (empty($draft['startDate'])) {
            $now = now();
            $todayStr = $now->toDateString();
            $startDate = null;
            $endDate = null;

            if (preg_match('/arriving\s+today/i', $reply) || preg_match('/\btoday,\s*[A-Za-z]+\s+\d+/i', $reply)) {
                $startDate = $todayStr;
            } elseif (preg_match('/arriving\s+tomorrow/i', $reply)) {
                $startDate = $now->copy()->addDay()->toDateString();
            }

            if (preg_match('/checking\s+out\s+tomorrow/i', $reply)) {
                $endDate = $now->copy()->addDay()->toDateString();
            }

            $monthPattern = 'jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:t|tember)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?';
            if (preg_match_all('/\b(' . $monthPattern . ')\s+(\d{1,2})(?:st|nd|rd|th)?(?:,?\s*(\d{4}))?\b/i', $reply, $allDateMatches, PREG_SET_ORDER)) {
                $parsedDates = [];
                foreach ($allDateMatches as $dm) {
                    try {
                        $yr = !empty($dm[3]) ? (int)$dm[3] : $now->year;
                        $p = Carbon::parse("{$dm[1]} {$dm[2]} {$yr}");
                        if ($p->isValid()) {
                            $parsedDates[] = $p->toDateString();
                        }
                    } catch (\Exception $e) {}
                }
                if (!empty($parsedDates)) {
                    $startDate = $startDate ?: $parsedDates[0];
                    if (count($parsedDates) > 1) {
                        $endDate = $parsedDates[1];
                    }
                }
            }

            if ($startDate && $startDate >= $todayStr) {
                $draft['tentativeDate'] = $startDate;
                $draft['tentativeEndDate'] = $endDate ?: $startDate;
                if (preg_match('/24-?hour|overnight/i', $reply)) {
                    $draft['tentativeSlot'] = 'Daytime';
                    $draft['tentativeEndSlot'] = 'Daytime';
                    $draft['tentativeStayType'] = '24_hours';
                }
                $draft['active'] = true;
            }
        }

        // 3. Proactively extract proposed guest headcount if none locked in yet
        if (empty($draft['guest']['guests_count'])) {
            if (preg_match('/(?:for\s+(?:your\s+)?|for\s+)(\d{1,3})\s*(?:guests?|pax|people)/i', $reply, $gMatch)) {
                $count = (int) $gMatch[1];
                if ($count >= 1 && $count <= 200) {
                    $draft['tentativeGuests'] = $count;
                    $draft['active'] = true;
                }
            }
        }
    }

    /**
     * Inspect recent assistant messages in history to restore proposed amenities, dates, and headcounts
     * when the user affirms or instructs to set up the booking.
     */
    private function enrichFromConversationHistory(array $history, array &$draft, string $userMessage): void
    {
        if (empty($history)) {
            return;
        }

        $now = now();
        $todayStr = $now->toDateString();

        // Collect recent assistant messages in reverse chronological order
        $assistantMessages = [];
        foreach (array_reverse($history) as $turn) {
            $role = $turn['role'] ?? '';
            $content = $turn['content'] ?? ($turn['text'] ?? '');
            if ($role === 'assistant' && !empty($content)) {
                $assistantMessages[] = $content;
                if (count($assistantMessages) >= 4) {
                    break;
                }
            }
        }

        foreach ($assistantMessages as $asstText) {
            // A. Amenities: sync proposed, switched, or removed amenities from assistant confirmation
            $replyActions = $this->extractAmenityActionsFromMessage($asstText);
            if (!empty($replyActions['removed']) && !empty($draft['amenities'])) {
                $removedIds = array_map(fn($m) => (string) $m->id, $replyActions['removed']);
                $draft['amenities'] = array_values(array_filter(
                    $draft['amenities'],
                    fn($am) => !in_array((string)($am['id'] ?? ''), $removedIds)
                ));
            }

            if ($replyActions['is_keep_only'] || $replyActions['is_switch'] || (!empty($replyActions['removed']) && !empty($replyActions['wanted']))) {
                if (!empty($replyActions['wanted'])) {
                    $isNight = ($draft['startSlot'] ?? ($draft['tentativeSlot'] ?? 'Daytime')) === 'Nighttime';
                    $amenityItems = [];
                    foreach ($replyActions['wanted'] as $am) {
                        $price = $isNight ? (float) ($am->nighttime_price ?? 0) : (float) ($am->daytime_price ?? 0);
                        $amenityItems[] = [
                            'id' => (string) $am->id,
                            'name' => $am->amenities_name,
                            'price' => $price,
                            'daytime_price' => (float) ($am->daytime_price ?? 0),
                            'nighttime_price' => (float) ($am->nighttime_price ?? 0),
                            'minimum_capacity' => (int) ($am->minimum_capacity ?? 1),
                            'maximum_capacity' => (int) ($am->maximum_capacity ?? 10),
                        ];
                    }
                    if (!empty($amenityItems)) {
                        $draft['active'] = true;
                        $draft['amenities'] = $amenityItems;
                        $draft['amenity'] = $amenityItems[0];
                        $draft['tentativeAmenity'] = null;
                        $draft['tentativeAmenities'] = null;
                    }
                }
            }

            // B. Dates: if startDate not yet locked in draft
            if (empty($draft['startDate'])) {
                $foundStartDate = null;
                $foundEndDate = null;
                $is24Hours = (bool) preg_match('/24-?hour|overnight/i', $asstText);

                if (preg_match('/arriving\s+today/i', $asstText) || preg_match('/\btoday,\s*[A-Za-z]+\s+\d+/i', $asstText)) {
                    $foundStartDate = $todayStr;
                } elseif (preg_match('/arriving\s+tomorrow/i', $asstText)) {
                    $foundStartDate = $now->copy()->addDay()->toDateString();
                }

                if (preg_match('/checking\s+out\s+tomorrow/i', $asstText)) {
                    $foundEndDate = $now->copy()->addDay()->toDateString();
                }

                $monthPattern = 'jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:t|tember)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?';
                if (preg_match_all('/\b(' . $monthPattern . ')\s+(\d{1,2})(?:st|nd|rd|th)?(?:,?\s*(\d{4}))?\b/i', $asstText, $dMatches, PREG_SET_ORDER)) {
                    $parsedDates = [];
                    foreach ($dMatches as $dm) {
                        try {
                            $yr = !empty($dm[3]) ? (int)$dm[3] : $now->year;
                            $p = Carbon::parse("{$dm[1]} {$dm[2]} {$yr}");
                            if ($p->isValid()) {
                                $parsedDates[] = $p->toDateString();
                            }
                        } catch (\Exception $e) {}
                    }
                    if (!empty($parsedDates)) {
                        $foundStartDate = $foundStartDate ?: $parsedDates[0];
                        if (count($parsedDates) > 1) {
                            $foundEndDate = $parsedDates[1];
                        }
                    }
                }

                if ($foundStartDate && $foundStartDate >= $todayStr) {
                    $draft['active'] = true;
                    $draft['startDate'] = $foundStartDate;
                    $draft['endDate'] = $foundEndDate ?: $foundStartDate;
                    if ($is24Hours) {
                        $draft['startSlot'] = 'Daytime';
                        $draft['endSlot'] = 'Daytime';
                        $draft['stayType'] = '24_hours';
                    } else {
                        if (preg_match('/\b(?:night|nighttime|overnight)\b/i', $asstText)) {
                            $draft['startSlot'] = 'Nighttime';
                            $draft['endSlot'] = 'Nighttime';
                        } else {
                            $draft['startSlot'] = 'Daytime';
                            $draft['endSlot'] = 'Daytime';
                        }
                    }
                    $draft['slot'] = $draft['startSlot'];
                    $draft['tentativeDate'] = null;
                    $draft['tentativeSlot'] = null;
                    $draft['tentativeEndDate'] = null;
                    $draft['tentativeEndSlot'] = null;
                    $draft['tentativeStayType'] = null;
                }
            }

            // C. Guests count: if guests_count not yet locked in draft
            if (empty($draft['guest']['guests_count'])) {
                if (preg_match('/(?:for\s+(?:your\s+)?|for\s+)(\d{1,3})\s*(?:guests?|pax|people)/i', $asstText, $gx)) {
                    $gCount = (int) $gx[1];
                    if ($gCount >= 1 && $gCount <= 200) {
                        $draft['guest']['guests_count'] = $gCount;
                        $draft['tentativeGuests'] = null;
                        $draft['active'] = true;
                    }
                }
            }

            // If we've successfully recovered amenities and dates, stop scanning earlier messages
            if (!empty($draft['amenities']) && !empty($draft['startDate'])) {
                break;
            }
        }
    }

    /**
     * Helper to resolve a single amenity from a string snippet (e.g. "payag6", "cottage 1", "function hall").
     */
    private function findSingleAmenityFromSnippet(string $snippet): ?Amenity
    {
        if (preg_match('/\b(Payags?|A-?Houses?|Cottages?)\s*(\d+)\b/i', $snippet, $m)) {
            $category = strtolower($m[1]);
            $num = $m[2];
            $categoryName = match (true) {
                str_contains($category, 'house') => 'A-House',
                str_contains($category, 'payag') => 'Payag',
                str_contains($category, 'cottage') => 'Cottage',
                default => ucfirst($category),
            };
            $standardName = "{$categoryName} {$num}";
            return Amenity::with('benefits')->where('status', true)
                ->where('amenities_name', 'LIKE', "%{$standardName}%")
                ->first();
        }
        if (preg_match('/Function\s*Hall/i', $snippet)) {
            return Amenity::with('benefits')->where('status', true)
                ->where('amenities_name', 'LIKE', '%Function Hall%')
                ->first();
        }
        if (preg_match('/\b(?:air-?conditioned|aircon)\b/i', $snippet) && (preg_match('/\b(?:house|room|unit)\b/i', $snippet) || !preg_match('/\b(?:cottage|payag)\b/i', $snippet))) {
            return Amenity::with('benefits')->where('status', true)
                ->whereHas('benefits', fn($b) => $b->where('is_aircon', true))
                ->orderBy('amenities_name', 'asc')
                ->first() ?: Amenity::where('amenities_name', 'A-House 2')->first();
        }
        if (preg_match('/\b(?:regular|standard|non-?aircon)\s*(?:a-?house)?\b/i', $snippet)) {
            return Amenity::where('amenities_name', 'A-House 1')->first();
        }
        if (preg_match('/\bpayag\b/i', $snippet)) {
            return Amenity::where('amenities_name', 'Payag 1')->first();
        }
        if (preg_match('/\bcottage\b/i', $snippet)) {
            return Amenity::where('amenities_name', 'Cottage 1')->first();
        }
        return null;
    }

    /**
     * Check if a specific amenity is available on a specific date and session slot.
     */
    private function checkAmenityAvailability(int|string $amenityId, string $date, string $slot = 'Daytime'): bool
    {
        $todayStr = now()->toDateString();
        $reservations = ReservationAmenity::with('reservation')
            ->where('amenity_id', $amenityId)
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'Completed');
            })
            ->whereHas('reservation', function ($rq) {
                $rq->whereNotIn('status', ['Cancelled', 'Checked Out', 'cancelled', 'checked out', 'checked_out', 'checked-out']);
            })
            ->get();

        foreach ($reservations as $ra) {
            $res = $ra->reservation;
            $s = $ra->start_date ? $ra->start_date->toDateString() : ($res?->reservation_date ? $res->reservation_date->toDateString() : $todayStr);
            $e = $ra->end_date ? $ra->end_date->toDateString() : ($res?->end_date ? $res->end_date->toDateString() : $s);
            if ($s && $e && $date >= $s && $date <= $e) {
                $sSlot = $ra->start_slot ?: ($res?->start_slot ?? 'Daytime');
                $eSlot = $ra->end_slot ?: ($res?->end_slot ?? $sSlot);
                if ($s === $e) {
                    if ($sSlot === $slot || $eSlot === $slot || ($sSlot === 'Daytime' && $eSlot === 'Nighttime')) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Parse amenity actions from a message (wanted amenities, removed/negated amenities, action flags).
     *
     * @return array{
     *   wanted: Amenity[],
     *   removed: Amenity[],
     *   is_removal_only: bool,
     *   is_switch: bool,
     *   is_swap: bool,
     *   swap_from: ?Amenity,
     *   swap_to: ?Amenity,
     *   is_add: bool,
     *   is_keep_only: bool
     * }
     */
    private function extractAmenityActionsFromMessage(string $message): array
    {
        $allFound = [];
        $foundIds = [];

        // 1. Full category + number mentions e.g. "A-House 2", "Payag 3", "Cottage 1", "ahouse2", "cottages 1"
        if (preg_match_all('/\b(Payags?|A-?Houses?|Cottages?)\s*(\d+)\b/i', $message, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $m) {
                $matchedText = $m[0][0];
                $offset = $m[0][1];
                $category = strtolower($m[1][0]);
                $num = $m[2][0];
                $categoryName = match (true) {
                    str_contains($category, 'house') => 'A-House',
                    str_contains($category, 'payag') => 'Payag',
                    str_contains($category, 'cottage') => 'Cottage',
                    default => ucfirst($category),
                };
                $standardName = "{$categoryName} {$num}";
                $model = Amenity::with('benefits')->where('status', true)
                    ->where('amenities_name', 'LIKE', "%{$standardName}%")
                    ->first();
                if ($model && !in_array($model->id, $foundIds)) {
                    $foundIds[] = $model->id;
                    $allFound[] = [
                        'model' => $model,
                        'name' => $model->amenities_name,
                        'offset' => $offset,
                        'text' => $matchedText,
                    ];
                }
            }
        }

        // 1b. Shorthand multi-amenity lists like "cottage 1 and 2", "cottages 1, 2", "payag 3, 4"
        if (preg_match_all('/\b(Payags?|A-?Houses?|Cottages?)\s*(\d+)(?:\s*(?:and|&|,|\+)\s*(\d+))+\b/i', $message, $multiMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($multiMatches as $mm) {
                $category = strtolower($mm[1][0]);
                $categoryName = match (true) {
                    str_contains($category, 'house') => 'A-House',
                    str_contains($category, 'payag') => 'Payag',
                    str_contains($category, 'cottage') => 'Cottage',
                    default => ucfirst($category),
                };
                $offset = $mm[0][1];
                preg_match_all('/\d+/', $mm[0][0], $numMatches);
                foreach ($numMatches[0] as $num) {
                    $standardName = "{$categoryName} {$num}";
                    $model = Amenity::with('benefits')->where('status', true)
                        ->where('amenities_name', 'LIKE', "%{$standardName}%")
                        ->first();
                    if ($model && !in_array($model->id, $foundIds)) {
                        $foundIds[] = $model->id;
                        $allFound[] = [
                            'model' => $model,
                            'name' => $model->amenities_name,
                            'offset' => $offset,
                            'text' => $standardName,
                        ];
                    }
                }
            }
        }

        // 2. Match Function Hall
        if (preg_match_all('/Function\s*Hall/i', $message, $fhMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($fhMatches as $fhm) {
                $model = Amenity::with('benefits')->where('status', true)
                    ->where('amenities_name', 'LIKE', '%Function Hall%')
                    ->first();
                if ($model && !in_array($model->id, $foundIds)) {
                    $foundIds[] = $model->id;
                    $allFound[] = [
                        'model' => $model,
                        'name' => $model->amenities_name,
                        'offset' => $fhm[0][1],
                        'text' => $fhm[0][0],
                    ];
                }
            }
        }

        // 3. Natural quantity phrases like "two cottages", "2 cottages", "two payags", "2 a-houses"
        if (empty($allFound)) {
            $wordQty = ['two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'six' => 6];
            if (preg_match('/\b(two|three|four|five|six|\d+)\s+(cottages?|payags?|a-?houses?)\b/i', $message, $qMatch, PREG_OFFSET_CAPTURE)) {
                $qtyStr = strtolower($qMatch[1][0]);
                $count = isset($wordQty[$qtyStr]) ? $wordQty[$qtyStr] : (int) $qtyStr;
                $catStr = strtolower($qMatch[2][0]);
                $prefix = match (true) {
                    str_contains($catStr, 'cottage') => 'Cottage',
                    str_contains($catStr, 'payag') => 'Payag',
                    str_contains($catStr, 'house') => 'A-House',
                    default => '',
                };
                if (!empty($prefix) && $count >= 1 && $count <= 6) {
                    $models = Amenity::with('benefits')->where('status', true)
                        ->where('amenities_name', 'LIKE', "{$prefix}%")
                        ->orderBy('amenities_name', 'asc')
                        ->take($count)
                        ->get();
                    foreach ($models as $m) {
                        if (!in_array($m->id, $foundIds)) {
                            $foundIds[] = $m->id;
                            $allFound[] = [
                                'model' => $m,
                                'name' => $m->amenities_name,
                                'offset' => $qMatch[0][1],
                                'text' => $qMatch[0][0],
                            ];
                        }
                    }
                }
            }
        }

        // 4. Air-conditioned A-House mentions without unit number
        // e.g. "airconditioned ahouse", "aircon a-house", "the airconditioned ahouse", "air-conditioned ahouse"
        if (empty($allFound)) {
            if (preg_match('/\b(?:air-?conditioned|aircon)\s*(?:a-?houses?|rooms?|units?)?\b/i', $message, $acMatch, PREG_OFFSET_CAPTURE)
                || preg_match('/\b(?:a-?houses?)\s+(?:nga\s+)?(?:naay\s+)?(?:air-?conditioned|aircon)\b/i', $message, $acMatch, PREG_OFFSET_CAPTURE)) {
                $acModel = Amenity::with('benefits')->where('status', true)
                    ->whereHas('benefits', fn($b) => $b->where('is_aircon', true))
                    ->orderBy('amenities_name', 'asc')
                    ->first();
                if (!$acModel) {
                    $acModel = Amenity::with('benefits')->where('status', true)->where('amenities_name', 'A-House 2')->first();
                }
                if ($acModel && !in_array($acModel->id, $foundIds)) {
                    $foundIds[] = $acModel->id;
                    $allFound[] = [
                        'model' => $acModel,
                        'name' => $acModel->amenities_name,
                        'offset' => $acMatch[0][1],
                        'text' => $acMatch[0][0],
                        'category_label' => 'Air-conditioned A-House',
                    ];
                }
            }
        }

        // 5. Regular A-House mentions without unit number (e.g. "regular a-house", "regular ahouse", "non-aircon ahouse")
        if (empty($allFound)) {
            if (preg_match('/\b(?:regular|standard|non-?aircon)\s+(?:a-?houses?|rooms?|units?)\b/i', $message, $regMatch, PREG_OFFSET_CAPTURE)) {
                $regModel = Amenity::with('benefits')->where('status', true)->where('amenities_name', 'A-House 1')->first();
                if ($regModel && !in_array($regModel->id, $foundIds)) {
                    $foundIds[] = $regModel->id;
                    $allFound[] = [
                        'model' => $regModel,
                        'name' => $regModel->amenities_name,
                        'offset' => $regMatch[0][1],
                        'text' => $regMatch[0][0],
                        'category_label' => 'Regular A-House',
                    ];
                }
            }
        }

        // 6. Generic category selection when user is picking/booking: "the ahouse", "the cottage", "the payag"
        if (empty($allFound)) {
            if (preg_match('/\b(?:book|reserve|take|choose|pilion|kuhaon|okay\s+with|prefer|pick)\s+(?:the\s+|a\s+|an\s+)?(cottage|payag|a-?house)\b/i', $message, $genMatch, PREG_OFFSET_CAPTURE)) {
                $genCat = strtolower($genMatch[1][0]);
                $genTarget = match (true) {
                    str_contains($genCat, 'house') => 'A-House 1',
                    str_contains($genCat, 'payag') => 'Payag 1',
                    str_contains($genCat, 'cottage') => 'Cottage 1',
                    default => '',
                };
                if (!empty($genTarget)) {
                    $genModel = Amenity::with('benefits')->where('status', true)->where('amenities_name', $genTarget)->first();
                    if ($genModel && !in_array($genModel->id, $foundIds)) {
                        $foundIds[] = $genModel->id;
                        $allFound[] = [
                            'model' => $genModel,
                            'name' => $genModel->amenities_name,
                            'offset' => $genMatch[0][1],
                            'text' => $genMatch[0][0],
                        ];
                    }
                }
            }
        }

        $wanted = [];
        $removed = [];
        $isSwap = false;
        $swapFrom = null;
        $swapTo = null;

        // Check explicit swap pattern: "change [the] A into/to B", "replace A with B", "switch A to B", "swap A with B", "palita ang A ug B"
        if (preg_match('/\b(?:change|replace|switch|swap|palita|palitan)\s+(?:the\s+|ang\s+)?(.+?)\s+(?:into|with|to|ug|ng)\s+(.+?)(?:pls|please|\.|\!|\?|$)/i', $message, $sm)) {
            $fromAmenity = $this->findSingleAmenityFromSnippet($sm[1]);
            $toAmenity = $this->findSingleAmenityFromSnippet($sm[2]);
            if ($fromAmenity && $toAmenity) {
                $isSwap = true;
                $swapFrom = $fromAmenity;
                $swapTo = $toAmenity;
                $removed = [$fromAmenity];
                $wanted = [$toAmenity];
            }
        }

        if (!$isSwap) {
            // Check each found amenity for negation context (within 35 characters prior to mention)
            foreach ($allFound as $item) {
                $offset = $item['offset'];
                $precedingSnippet = substr($message, max(0, $offset - 35), min($offset, 35));
                $isNegated = (bool) preg_match('/\b(?:no|not|remove|drop|cancel|delete|exclude|without|don\'?t\s+want|instead\s+of|ayaw|dili|wag|tangtanga|tanggalin)\b[^\.\,\;\!\?]*$/i', $precedingSnippet);
                if ($isNegated) {
                    $removed[] = $item['model'];
                } else {
                    $wanted[] = $item['model'];
                }
            }
        }

        $msgLower = mb_strtolower($message);
        $isKeepOnly = (bool) preg_match('/\b(?:keep\s+only|only\s+keep|i\'?ll\s+keep\s+only|just\s+keep|keep)\b/i', $msgLower);
        $isSwitch = (bool) preg_match('/\b(?:switch(?:\s+to)?|change\s+to|can\s+u\s+change\s+(?:it\s+)?to|pwede\s+(?:i-?)?change(?:\s+to)?|pwede\s+(?:i-?)?palit|replace(?:\s+with)?|instead\s+of)\b/i', $msgLower);
        $isAdd = (bool) preg_match('/\b(?:add|dugang|dagdag|plus|also\s+add|can\s+u\s+add|pwede\s+add)\b/i', $msgLower);
        $isRemovalOnly = !empty($removed) && empty($wanted);

        return [
            'wanted' => $wanted,
            'removed' => $removed,
            'is_removal_only' => $isRemovalOnly,
            'is_switch' => $isSwitch || $isSwap,
            'is_swap' => $isSwap,
            'swap_from' => $swapFrom,
            'swap_to' => $swapTo,
            'is_add' => $isAdd,
            'is_keep_only' => $isKeepOnly,
        ];
    }

    /**
     * Extract all wanted amenities mentioned in a message (excluding negated/removed amenities).
     */
    private function extractAllAmenitiesFromMessage(string $message): array
    {
        return $this->extractAmenityActionsFromMessage($message)['wanted'];
    }

    /**
     * Secretly enrich and persist the active booking draft from the user's conversational message.
     */
    private function enrichBookingDraftFromMessage(string $userMessage, ?array $draft, array $history = []): array
    {
        $draft = is_array($draft) ? $draft : [];
        $msgLower = mb_strtolower(trim($userMessage));
        $now = now();
        $todayStr = $now->toDateString();

        // 0. Detect expired past recorded dates in incoming draft
        $expiredRecordedDate = null;
        if (!empty($draft['startDate']) && $draft['startDate'] < $todayStr) {
            $expiredRecordedDate = $draft['startDate'];
        } elseif (!empty($draft['date']) && $draft['date'] < $todayStr) {
            $expiredRecordedDate = $draft['date'];
        } elseif (!empty($draft['tentativeDate']) && $draft['tentativeDate'] < $todayStr) {
            $expiredRecordedDate = $draft['tentativeDate'];
        } elseif (!empty($draft['recordedPastDate']) && $draft['recordedPastDate'] < $todayStr) {
            $expiredRecordedDate = $draft['recordedPastDate'];
        }

        if ($expiredRecordedDate) {
            $draft['active'] = true;
            $draft['startDate'] = null;
            $draft['endDate'] = null;
            $draft['date'] = null;
            $draft['tentativeDate'] = null;
            $draft['amenities'] = [];
            $draft['amenity'] = null;
            $draft['tentativeAmenities'] = null;
            $draft['tentativeAmenity'] = null;
            $draft['step'] = 'date_slot';
            $draft['pastDateClarification'] = [
                'expiredPast' => true,
                'pastDate' => $expiredRecordedDate,
                'recordedDateExpired' => true,
            ];
            unset($draft['recordedPastDate']);
        }

        // 1. Detect booking intent to activate draft
        if (preg_match('/(?:book\s+via\s+chat|\/book|i\s+want\s+to\s+book|gusto\s+(?:ko|kami)\s+mag-?book|pwede\s+mag-?book|magpa-?reserve\s+ko|mag-?book\s+ko)/i', $msgLower)) {
            $draft['active'] = true;
            if (empty($draft['step'])) {
                $draft['step'] = 'date_slot';
            }
        }

        // 2. Secretly extract date and session slot if present
        $dateFound = null;
        $slotFound = null;

        // 2.1 Handle response to past date clarification if pending
        $isAffirmingNextYear = false;
        if (!empty($draft['pastDateClarification']) && empty($draft['pastDateClarification']['expiredPast'])) {
            $clar = $draft['pastDateClarification'];
            $nextYearVal = (string) ($clar['nextYear'] ?? ($now->year + 1));
            $isAffirmingNextYear = (bool) preg_match('/\b(?:next\s+year|sunod\s+tuig|susunod\s+na\s+taon|' . $nextYearVal . '|yes|sure|oo|opo|sige|proceed|correct|yep|yeah|pwede|let\'?s\s+go|confirm(?:ed)?)\b/i', $msgLower)
                && !preg_match('/\b(?:no|not|dili|hindi|change|different|other)\b/i', $msgLower);

            if ($isAffirmingNextYear) {
                $dateFound = $clar['nextYearDate'];
                unset($draft['pastDateClarification']);
            }
        }

        $isPastDate = false;
        $pastDateDetails = null;
        $monthPattern = 'jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:t|tember)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?';

        if (empty($dateFound)) {
            if (preg_match('/\b(?:tomorrow|ugma|bukas)\b/i', $msgLower)) {
                $dateFound = $now->copy()->addDay()->toDateString();
            } elseif (preg_match('/\b(?:today|tonight|karon|ngayong\s+araw)\b/i', $msgLower)) {
                $dateFound = $now->toDateString();
            } elseif (preg_match('/\byesterday\b/i', $msgLower)) {
                $isPastDate = true;
                $pastDateDetails = [
                    'expiredPast' => true,
                    'pastDate' => $now->copy()->subDay()->toDateString(),
                ];
            } elseif (preg_match('/\b(' . $monthPattern . ')\s+(\d{1,2})(?:st|nd|rd|th)?(?:,?\s*(\d{4}))?\b/i', $userMessage, $dMatches)
                   || preg_match('/\b(\d{1,2})(?:st|nd|rd|th)?\s+(?:of\s+)?(' . $monthPattern . ')(?:,?\s*(\d{4}))?\b/i', $userMessage, $dMatchesDayFirst)) {
                try {
                    if (!empty($dMatches)) {
                        $monthStr = $dMatches[1];
                        $dayStr = $dMatches[2];
                        $explicitYear = !empty($dMatches[3]) ? (int) $dMatches[3] : null;
                    } else {
                        $monthStr = $dMatchesDayFirst[2];
                        $dayStr = $dMatchesDayFirst[1];
                        $explicitYear = !empty($dMatchesDayFirst[3]) ? (int) $dMatchesDayFirst[3] : null;
                    }
                    $yearStr = $explicitYear ?: $now->year;
                    $parsed = Carbon::parse("{$monthStr} {$dayStr} {$yearStr}");
                    if ($parsed->isValid()) {
                        $candidateDate = $parsed->toDateString();
                        if ($candidateDate < $now->toDateString()) {
                            $isPastDate = true;
                            if ($explicitYear !== null && $explicitYear < $now->year) {
                                $pastDateDetails = [
                                    'expiredPast' => true,
                                    'pastDate' => $candidateDate,
                                ];
                            } else {
                                $nextYearParsed = $parsed->copy()->addYear();
                                $pastDateDetails = [
                                    'expiredPast' => false,
                                    'pastDate' => $candidateDate,
                                    'nextYearDate' => $nextYearParsed->toDateString(),
                                    'monthDay' => $parsed->format('F j'),
                                    'currentYear' => $now->year,
                                    'nextYear' => $now->year + 1,
                                ];
                            }
                        } else {
                            $dateFound = $candidateDate;
                            unset($draft['pastDateClarification']);
                        }
                    }
                } catch (\Exception $e) {}
            } elseif (preg_match('/\b(\d{4})-(\d{1,2})-(\d{1,2})\b/', $userMessage, $dMatches)) {
                $candidateDate = sprintf('%04d-%02d-%02d', $dMatches[1], $dMatches[2], $dMatches[3]);
                if ($candidateDate < $now->toDateString()) {
                    $isPastDate = true;
                    $pastDateDetails = [
                        'expiredPast' => true,
                        'pastDate' => $candidateDate,
                    ];
                } else {
                    $dateFound = $candidateDate;
                    unset($draft['pastDateClarification']);
                }
            }
        }

        if ($isPastDate && $pastDateDetails) {
            $draft['active'] = true;
            $draft['pastDateClarification'] = $pastDateDetails;
            $draft['tentativeDate'] = null;
            $dateFound = null;
        }

        if (preg_match('/\b(?:night|nighttime|gabii|gabi|overnight)\b/i', $msgLower)) {
            $slotFound = 'Nighttime';
        } elseif (preg_match('/\b(?:day|daytime|adlaw|araw|morning)\b/i', $msgLower)) {
            $slotFound = 'Daytime';
        }

        // 3. Pre-extract amenities from current user message to assist conversational cross-confirmation
        $matchedAmenities = $this->extractAllAmenitiesFromMessage($userMessage);
        $hasAmenityMention = !empty($matchedAmenities);

        // Check date confirmation vs tentative inquiry
        $hasAffirmation = (bool) preg_match('/\b(?:yes+|yep|yeah|yea|yup|yeap|sure|surely|ok(?:ay)?|k|okie|alright|all\s+right|good|that\'?s\s+good|sounds?\s+good|looks?\s+good|all\s+good|great|nice|perfect|sige|cge|ge|g|oo|opo|proceed|reserve(?:\s+both|\s+it)?|book(?:\s+both|\s+it)?|both|yes\s+both|confirm(?:ed)?|let\'?s\s+go|go\s+with|go|go\s+ahead|i\'?ll\s+take|we\'?ll\s+take|kunin\s+ko|kuhaon|pilion|lock\s+in|locked\s*in|correct|right|exactly|pwede|can\s+u\s+set\s+it\s+up(?:\s+now)?|can\s+you\s+set\s+it\s+up(?:\s+now)?|set\s+it\s+up(?:\s+now)?|set\s+this\s+up|please\s+do|let\'?s\s+do\s+(?:that|it)|make\s+the\s+reservation)\b/i', $msgLower);
        $hasRejection = (bool) preg_match('/\b(?:no|not|dili|hindi|change|different|other)\b/i', $msgLower);

        // If tentativeDate was set, and user affirms, selects an amenity, specifies slot, or specifies date
        $isConfirmingTentativeDate = false;
        if (!empty($draft['tentativeDate']) && empty($draft['startDate']) && !$hasRejection) {
            if ($hasAffirmation || $hasAmenityMention || ($slotFound && empty($dateFound)) || ($dateFound && $dateFound === $draft['tentativeDate'])) {
                $isConfirmingTentativeDate = true;
            }
        }

        if ($isConfirmingTentativeDate) {
            $draft['active'] = true;
            $draft['startDate'] = $draft['tentativeDate'];
            $draft['endDate'] = $draft['tentativeEndDate'] ?? $draft['startDate'];
            $draft['startSlot'] = $slotFound ?: ($draft['tentativeSlot'] ?? 'Daytime');
            $draft['endSlot'] = $draft['tentativeEndSlot'] ?? $draft['startSlot'];
            $draft['slot'] = $draft['startSlot'];
            if (!empty($draft['tentativeStayType'])) {
                $draft['stayType'] = $draft['tentativeStayType'];
            }
            $draft['tentativeDate'] = null;
            $draft['tentativeSlot'] = null;
            $draft['tentativeEndDate'] = null;
            $draft['tentativeEndSlot'] = null;
            $draft['tentativeStayType'] = null;
            if (empty($draft['amenities']) && empty($draft['amenity'])) {
                $draft['step'] = 'amenity';
            }
        } elseif ($dateFound) {
            // Check if user is being tentative / asking ("what about", "how about", "?", etc.)
            $isExplicitInquiry = (bool) preg_match('/(?:what\s+about|how\s+about|what\s+if|pwede\s+ba|pwede\s+kaha|is\s+it\s+good|availab|\?)/i', $msgLower);
            $isExplicitConfirm = (!$isExplicitInquiry && ($hasAffirmation || !empty($draft['amenities']) || !empty($draft['amenity']) || ($draft['step'] ?? '') === 'date_slot'))
                || ($isAffirmingNextYear && ($slotFound || !empty($draft['tentativeSlot']) || $hasAffirmation))
                || ((bool) preg_match('/(?:confirm(?:ed)?|lock\s+in|definitely|let\'?s\s+go\s+with|i\s+choose|book|reserve|pa-?book|gusto\s+(?:ko|kami)\s+mag-?book)\b/i', $msgLower) && !$isExplicitInquiry)
                || ($hasAmenityMention && !$isExplicitInquiry);

            if ($isExplicitConfirm) {
                $draft['active'] = true;
                $draft['startDate'] = $dateFound;
                $draft['endDate'] = $dateFound;
                $draft['startSlot'] = $slotFound ?: ($draft['tentativeSlot'] ?? ($draft['startSlot'] ?? 'Daytime'));
                $draft['endSlot'] = $draft['startSlot'];
                $draft['slot'] = $draft['startSlot'];
                $draft['tentativeDate'] = null;
                $draft['tentativeSlot'] = null;

                // If slot is Nighttime, ensure amenity prices reflect nighttime rate
                $isNight = $draft['startSlot'] === 'Nighttime';
                if (!empty($draft['amenities'])) {
                    foreach ($draft['amenities'] as &$amItem) {
                        if ($isNight && !empty($amItem['nighttime_price'])) {
                            $amItem['price'] = (float) $amItem['nighttime_price'];
                        } elseif (!$isNight && !empty($amItem['daytime_price'])) {
                            $amItem['price'] = (float) $amItem['daytime_price'];
                        }
                    }
                    unset($amItem);
                    $draft['amenity'] = $draft['amenities'][0];
                }

                if (empty($draft['amenities']) && empty($draft['amenity'])) {
                    $draft['step'] = 'amenity';
                } else {
                    $draft['step'] = 'guest_info';
                }
            } else {
                // Tentative proposal / inquiry: DO NOT record into startDate yet!
                $draft['active'] = true;
                $draft['tentativeDate'] = $dateFound;
                $draft['tentativeSlot'] = $slotFound ?: ($draft['tentativeSlot'] ?? 'Daytime');
                if (empty($draft['startDate'])) {
                    $draft['step'] = 'date_slot';
                }
            }
        } elseif ($slotFound && !empty($draft['startDate'])) {
            $draft['startSlot'] = $slotFound;
            $draft['endSlot'] = $slotFound;
            $draft['slot'] = $slotFound;
        } elseif ($slotFound && !empty($draft['tentativeDate'])) {
            $draft['tentativeSlot'] = $slotFound;
        }

        // 3. Amenity handling (Make sure before recording, support multiple amenities, removals, additions, switches!)
        $userAmenityActions = $this->extractAmenityActionsFromMessage($userMessage);

        // 3.0 If user explicitly requested a 1-to-1 swap (e.g. "change payag 6 into payag 4", "replace payag 6 with payag 4")
        if ($userAmenityActions['is_swap'] && $userAmenityActions['swap_from'] && $userAmenityActions['swap_to']) {
            $fromId = (string) $userAmenityActions['swap_from']->id;
            $toModel = $userAmenityActions['swap_to'];
            $isNight = ($draft['startSlot'] ?? ($draft['tentativeSlot'] ?? 'Daytime')) === 'Nighttime';
            $price = $isNight ? (float) ($toModel->nighttime_price ?? 0) : (float) ($toModel->daytime_price ?? 0);
            $newItem = [
                'id' => (string) $toModel->id,
                'name' => $toModel->amenities_name,
                'price' => $price,
                'daytime_price' => (float) ($toModel->daytime_price ?? 0),
                'nighttime_price' => (float) ($toModel->nighttime_price ?? 0),
                'minimum_capacity' => (int) ($toModel->minimum_capacity ?? 1),
                'maximum_capacity' => (int) ($toModel->maximum_capacity ?? 10),
            ];

            $draft['active'] = true;
            if (!empty($draft['amenities'])) {
                $swapped = false;
                foreach ($draft['amenities'] as $idx => $existing) {
                    if ((string)($existing['id'] ?? '') === $fromId || stripos($existing['name'] ?? '', $userAmenityActions['swap_from']->amenities_name) !== false) {
                        $draft['amenities'][$idx] = $newItem;
                        $swapped = true;
                        break;
                    }
                }
                if (!$swapped) {
                    $draft['amenities'][] = $newItem;
                }
            } else {
                $draft['amenities'] = [$newItem];
            }
            $draft['amenities'] = array_values($draft['amenities']);
            $draft['amenity'] = $draft['amenities'][0];
            $draft['tentativeAmenities'] = null;
            $draft['tentativeAmenity'] = null;
        } else {
            // 3.1 If user explicitly requested removing / dropping / cancelling amenities
            if (!empty($userAmenityActions['removed']) && !empty($draft['amenities'])) {
                $removedIds = array_map(fn($m) => (string) $m->id, $userAmenityActions['removed']);
                $draft['amenities'] = array_values(array_filter(
                    $draft['amenities'],
                    fn($am) => !in_array((string)($am['id'] ?? ''), $removedIds)
                ));
                if (!empty($draft['tentativeAmenities'])) {
                    $removedNames = array_map(fn($m) => strtolower($m->amenities_name), $userAmenityActions['removed']);
                    $draft['tentativeAmenities'] = array_values(array_filter(
                        $draft['tentativeAmenities'],
                        fn($tn) => !in_array(strtolower($tn), $removedNames)
                    ));
                    $draft['tentativeAmenity'] = implode(', ', $draft['tentativeAmenities']);
                }
                if (empty($draft['amenities'])) {
                    $draft['amenity'] = null;
                    $draft['step'] = 'amenity';
                } else {
                    $draft['amenity'] = $draft['amenities'][0];
                }
            }

            $newAmenityItems = [];
            $isNight = ($draft['startSlot'] ?? ($draft['tentativeSlot'] ?? 'Daytime')) === 'Nighttime';
            foreach ($userAmenityActions['wanted'] as $am) {
                $price = $isNight ? (float) ($am->nighttime_price ?? 0) : (float) ($am->daytime_price ?? 0);
                $newAmenityItems[] = [
                    'id' => (string) $am->id,
                    'name' => $am->amenities_name,
                    'price' => $price,
                    'daytime_price' => (float) ($am->daytime_price ?? 0),
                    'nighttime_price' => (float) ($am->nighttime_price ?? 0),
                    'minimum_capacity' => (int) ($am->minimum_capacity ?? 1),
                    'maximum_capacity' => (int) ($am->maximum_capacity ?? 10),
                ];
            }

            if (!empty($draft['pastDateClarification']['recordedDateExpired'])) {
                $draft['amenities'] = [];
                $draft['amenity'] = null;
                $draft['tentativeAmenities'] = null;
                $draft['tentativeAmenity'] = null;
                $newAmenityItems = [];
            }

            if (!empty($newAmenityItems)) {
                $isExplicitAdd = $userAmenityActions['is_add'];
                $isExplicitSwitch = $userAmenityActions['is_switch'] || $userAmenityActions['is_keep_only'] || (!empty($userAmenityActions['removed']) && !empty($userAmenityActions['wanted']));
                $isExplicitInquiry = (bool) preg_match('/\b(?:think|better|right\??|what\s+about|how\s+about|is\s+it\s+good|availab|pwede\s+ba|pwede\s+kaha|magkano|how\s+much|pila|unsa|ano|\?|siguro|tingin\s+mo|nindot\s+ba|maybe)\b/i', $msgLower)
                    && !$isExplicitAdd && !$isExplicitSwitch;
                $isExplicitConfirm = (bool) preg_match('/\b(?:yes|sure|reserve|book|i\'?ll\s+take|i\s+will\s+take|let\'?s\s+go\s+with|confirm(?:ed)?|kunin\s+ko|i-?reserve|kuhaon|pilion|lock\s+in|okay\s+with|ok\s+with|fine\s+with|prefer|good\s+with|i\'?m\s+good|im\s+good|i\'?m\s+ok|im\s+ok|i\'?m\s+okay|im\s+okay)\b/i', $msgLower);
                $isAnsweringAmenityPrompt = ($draft['step'] ?? '') === 'amenity' || !empty($draft['startDate']) || !empty($draft['tentativeDate']);

                $isTentativeAmenity = $isExplicitInquiry || (!$isExplicitConfirm && !$isAnsweringAmenityPrompt && !$isExplicitAdd && !$isExplicitSwitch);

                if ($isTentativeAmenity && !$isExplicitAdd && !$isExplicitSwitch) {
                    $draft['tentativeAmenities'] = array_map(fn($item) => $item['name'], $newAmenityItems);
                    $draft['tentativeAmenity'] = implode(', ', $draft['tentativeAmenities']);
                    $draft['active'] = true;
                } elseif (!empty($draft['amenities']) && $isExplicitAdd) {
                    $draft['active'] = true;
                    foreach ($newAmenityItems as $newItem) {
                        $alreadyInList = false;
                        foreach ($draft['amenities'] as $existing) {
                            if ((string)($existing['id'] ?? '') === (string)$newItem['id']) {
                                $alreadyInList = true;
                                break;
                            }
                        }
                        if (!$alreadyInList) {
                            $draft['amenities'][] = $newItem;
                        }
                    }
                    $draft['amenity'] = $draft['amenities'][0];
                    $draft['tentativeAmenity'] = null;
                    $draft['tentativeAmenities'] = null;
                } elseif (!empty($draft['amenities']) && $isExplicitSwitch) {
                    $draft['active'] = true;
                    $draft['amenities'] = $newAmenityItems;
                    $draft['amenity'] = $newAmenityItems[0];
                    $draft['tentativeAmenity'] = null;
                    $draft['tentativeAmenities'] = null;
                } else {
                    $draft['active'] = true;
                    $draft['amenities'] = $newAmenityItems;
                    $draft['amenity'] = $newAmenityItems[0];
                    $draft['tentativeAmenity'] = null;
                    $draft['tentativeAmenities'] = null;
                    if (empty($draft['guest']['name']) || empty($draft['guest']['phone']) || empty($draft['guest']['email']) || empty($draft['guest']['guests_count'])) {
                        $draft['step'] = 'guest_info';
                    }
                }
            } elseif (!empty($draft['tentativeAmenities']) || !empty($draft['tentativeAmenity']) || !empty($draft['tentativeCategory'])) {
                $isAgreeToView = (bool) preg_match('/(?:show|view|pakita|patingin)/i', $msgLower);
                $isAddConfirm = (bool) preg_match('/\b(?:add(?:\s+it)?|yes\s+add|dugang|dagdag|both|together)\b/i', $msgLower) && !$isAgreeToView;
                $isSwitchConfirm = (bool) preg_match('/\b(?:switch(?:\s+to\s+it)?|change(?:\s+to\s+it)?|palit|replace|instead)\b/i', $msgLower) && !$isAgreeToView;
                $isSlotSelection = (bool) $slotFound && empty($dateFound);
                $isDateSelection = !empty($dateFound);
                $hasPhoneInMessage = (bool) preg_match('/(?:(?:\+?63\s*|0)?(9\d{2}[\s\-]?\d{3}[\s\-]?\d{4}|9\d{9}))/i', $userMessage);
                $hasEmailInMessage = (bool) preg_match('/\b([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})\b/', $userMessage);
                $hasAffirmation = (bool) preg_match('/\b(?:yes+|yes\s+yes|yes+\s+yes+|yep|yeah|yea|yup|yeap|sure|surely|ok(?:ay)?|k|okie|alright|all\s+right|good|that\'?s\s+good|sounds?\s+good|looks?\s+good|all\s+good|great|nice|perfect|sige|sige\s+sige|cge|cge\s+cge|ge|g|oo|oo\s+oo|opo|proceed|reserve(?:\s+both|\s+it)?|book(?:\s+both|\s+it)?|both|yes\s+both|confirm(?:ed)?|let\'?s\s+go|go\s+with|go|go\s+ahead|i\'?ll\s+take|we\'?ll\s+take|kunin\s+ko|kuhaon|pilion|lock\s+in|locked\s*in|correct|right|exactly|pwede|can\s+u\s+set\s+it\s+up(?:\s+now)?|can\s+you\s+set\s+it\s+up(?:\s+now)?|set\s+it\s+up(?:\s+now)?|set\s+this\s+up|please\s+do|let\'?s\s+do\s+(?:that|it)|make\s+the\s+reservation)\b/i', $msgLower);
                $isAskingSuggestion = (bool) preg_match('/(?:suggest|recommend|rekomenda|bagay|swak|what\s+(?:should|can)\s+i\s+(?:book|choose|pick)|unsa\s+(?:ang\s+)?(?:nindot|bagay)|which\s+(?:amenit|cottage|payag|hut|house))/i', $msgLower);

                $isGeneralConfirm = ($hasAffirmation || $isSlotSelection || $isDateSelection || $hasPhoneInMessage || $hasEmailInMessage) && !$isAgreeToView && !$isAskingSuggestion;

                if ($isAddConfirm || $isSwitchConfirm || $isGeneralConfirm) {
                    if ($slotFound) {
                        $draft['startSlot'] = $slotFound;
                        $draft['endSlot'] = $slotFound;
                        $draft['slot'] = $slotFound;
                    }
                    $tentativeList = !empty($draft['tentativeAmenities']) && is_array($draft['tentativeAmenities'])
                        ? $draft['tentativeAmenities']
                        : array_map('trim', explode(',', $draft['tentativeAmenity'] ?: ($draft['tentativeCategory'] ?? '')));

                    $resolvedItems = [];
                    $isNight = ($draft['startSlot'] ?? 'Daytime') === 'Nighttime';
                    foreach ($tentativeList as $tName) {
                        if (empty($tName)) continue;
                        $amenityModel = Amenity::where('status', true)
                            ->where('amenities_name', 'LIKE', "%{$tName}%")
                            ->first();
                        if (!$amenityModel) {
                            $amenityModel = $this->findSingleAmenityFromSnippet($tName);
                        }
                        if ($amenityModel) {
                            $price = $isNight ? (float) ($amenityModel->nighttime_price ?? 0) : (float) ($amenityModel->daytime_price ?? 0);
                            $resolvedItems[] = [
                                'id' => (string) $amenityModel->id,
                                'name' => $amenityModel->amenities_name,
                                'price' => $price,
                                'daytime_price' => (float) ($amenityModel->daytime_price ?? 0),
                                'nighttime_price' => (float) ($amenityModel->nighttime_price ?? 0),
                                'minimum_capacity' => (int) ($amenityModel->minimum_capacity ?? 1),
                                'maximum_capacity' => (int) ($amenityModel->maximum_capacity ?? 10),
                            ];
                        }
                    }

                    if (!empty($resolvedItems)) {
                        $draft['active'] = true;
                        if (!empty($draft['amenities']) && $isAddConfirm) {
                            foreach ($resolvedItems as $newItem) {
                                $alreadyInList = false;
                                foreach ($draft['amenities'] as $existing) {
                                    if ((string)($existing['id'] ?? '') === (string)$newItem['id']) {
                                        $alreadyInList = true;
                                        break;
                                    }
                                }
                                if (!$alreadyInList) {
                                    $draft['amenities'][] = $newItem;
                                }
                            }
                        } else {
                            $draft['amenities'] = $resolvedItems;
                        }
                        $draft['amenity'] = $draft['amenities'][0];
                        $draft['tentativeAmenity'] = null;
                        $draft['tentativeAmenities'] = null;
                        if (empty($draft['startDate'])) {
                            $draft['step'] = 'date_slot';
                        } elseif (empty($draft['guest']['name']) || empty($draft['guest']['phone']) || empty($draft['guest']['email']) || empty($draft['guest']['guests_count'])) {
                            $draft['step'] = 'guest_info';
                        }
                    }
                }
            }
        }

        // Conversational Memory: Scan and restore proposed amenities, dates, and headcounts from recent assistant proposals in history!
        $this->enrichFromConversationHistory($history, $draft, $userMessage);

        // Also check if tentative guests was set from proactive extraction
        if ($hasAffirmation && !empty($draft['tentativeGuests']) && empty($draft['guest']['guests_count'])) {
            $draft['guest']['guests_count'] = (int) $draft['tentativeGuests'];
            $draft['tentativeGuests'] = null;
        }

        // 4. Secretly extract guest details (phone, email, name, guests_count)
        // 4.1 Phone (Supports "09930457138 thats all", "+63 993 045 7138", etc.)
        if (preg_match('/(?:(?:\+?63\s*|0)?(9\d{2}[\s\-]?\d{3}[\s\-]?\d{4}|9\d{9}))/i', $userMessage, $pMatch)) {
            $cleanDigits = preg_replace('/\D/', '', $pMatch[1]);
            if (strlen($cleanDigits) === 10 && str_starts_with($cleanDigits, '9')) {
                $draft['guest']['phone'] = '0' . $cleanDigits;
            }
        }

        // 4.2 Email
        $hadEmailBefore = !empty($draft['guest']['email']);
        $emailExtractedInThisTurn = false;
        if (preg_match('/\b([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})\b/', $userMessage, $eMatch)) {
            $draft['guest']['email'] = strtolower(trim($eMatch[1]));
            $emailExtractedInThisTurn = true;
        }

        // 4.3 Headcount / Pax
        $hadGuestsCountBefore = !empty($draft['guest']['guests_count']);
        $wordNumbers = [
            'usa' => 1, 'isa' => 1, 'solo' => 1,
            'duha' => 2, 'dalawa' => 2,
            'tulo' => 3, 'tatlo' => 3,
            'upat' => 4, 'apat' => 4,
            'lima' => 5,
            'unom' => 6, 'anim' => 6,
            'pito' => 7,
            'walo' => 8,
            'siyam' => 9,
            'napulo' => 10, 'sampu' => 10,
        ];

        $msgWithoutPhone = preg_replace('/(?:\+?63|0)?9\d{2}[\s\-\.]?\d{3}[\s\-\.]?\d{4}|(?:\+?63|0)?9\d{9}/', ' ', $userMessage);
        $msgWithoutPhone = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', ' ', $msgWithoutPhone);
        if (preg_match('/\b(?:it\'?s|its|we\s*are|kami|mi|kaming|just|only|around)?\s*(\d{1,3})\s*(?:guests?|pax|people|persons|katao|ka\s*tao|ka\s*tawo|kabuok)?\b/i', $msgWithoutPhone, $paxMatch)) {
            $paxVal = (int) $paxMatch[1];
            $hasExplicitPaxWord = (bool) preg_match('/\b(?:guests?|pax|people|persons|katao|ka\s*tao|ka\s*tawo|kabuok|it\'?s|its|we\s*are|kami|mi)\b/i', $userMessage);
            // Accept standalone or prefixed number if email was already known, or if an explicit pax/headcount word was provided
            if ($paxVal >= 1 && $paxVal <= 200 && ($hasExplicitPaxWord || ($hadEmailBefore && !$emailExtractedInThisTurn) || !empty($draft['guest']['email']))) {
                $draft['guest']['guests_count'] = $paxVal;
                $draft['step'] = 'payment';
            }
        } elseif (preg_match('/\b(?:just\s+me|solo|ako\s*lang|only\s+me|just\s+1|1\s+person|1\s+pax)\b/i', $msgLower)) {
            $draft['guest']['guests_count'] = 1;
            $draft['step'] = 'payment';
        } elseif (preg_match('/\b(isa|usa|duha|dalawa|tulo|tatlo|upat|apat|lima|unom|anim|pito|walo|siyam|napulo|sampu)\b/i', $msgLower, $wMatch)) {
            $wordKey = strtolower($wMatch[1]);
            $hasExplicitPaxWord = (bool) preg_match('/\b(?:kami|mi|tawo|tao|pax|guests?|katao)\b/i', $msgLower);
            if (isset($wordNumbers[$wordKey]) && ($hasExplicitPaxWord || ($hadEmailBefore && !$emailExtractedInThisTurn) || !empty($draft['guest']['email']))) {
                $draft['guest']['guests_count'] = $wordNumbers[$wordKey];
                $draft['step'] = 'payment';
            }
        }

        // Guard: If email was not provided yet and guests_count was not established previously or from conversation history,
        // prevent accidental number extraction from premature user messages
        if (!$hadGuestsCountBefore && empty($draft['guest']['email']) && !empty($draft['guest']['guests_count']) && empty($hasExplicitPaxWord)) {
            $draft['guest']['guests_count'] = null;
        }

        // 4.4 Name
        $msgWithoutPhoneOrEmail = preg_replace('/(?:\+?63|0)?9\d{2}[\s\-\.]?\d{3}[\s\-\.]?\d{4}|(?:\+?63|0)?9\d{9}/', ' ', $userMessage);
        $msgWithoutPhoneOrEmail = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', ' ', $msgWithoutPhoneOrEmail);
        $trimmedMsg = trim($msgWithoutPhoneOrEmail);

        if (preg_match('/(?:my\s+name\s+is|name\s*(?:is|:)?|ako\s*(?:si)?|i\s*am|ngalan\s*nako\s*kay|pangalan\s*ko\s*ay|it\'?s|its)\s+([A-Za-z\s\.\'-]{2,40})/i', $trimmedMsg, $nMatch)) {
            $candName = ucwords(strtolower(trim($nMatch[1])));
            if (strlen($candName) >= 3 && !preg_match('/\b(?:book|reservation|amenity|cottage|payag|ahouse|house|guest|guests)\b/i', $candName)) {
                $draft['guest']['name'] = $candName;
            }
        } elseif (empty($draft['guest']['name']) && !empty($draft['amenities'])) {
            // If amenities are selected and name is not yet provided:
            // Strip leading "its " or "it's " from candidate string if present
            $cleanedCandidate = preg_replace('/^(?:it\'?s|its)\s+/i', '', $trimmedMsg);
            $cleanWords = preg_split('/\s+/', preg_replace('/[^\p{L}\s\.\'-]/u', '', $cleanedCandidate));
            $cleanWords = array_values(array_filter($cleanWords, fn($w) => strlen(trim($w)) > 0));
            $nonNameWords = ['hi', 'hello', 'hey', 'book', 'yes', 'no', 'sure', 'ok', 'okay', 'payag', 'cottage', 'ahouse', 'a-house', 'house', 'function', 'hall', 'daytime', 'nighttime', 'morning', 'night', 'today', 'tomorrow', 'ugma', 'bukas', 'puhon', 'karon', 'view', 'show', 'cancel', 'proceed', 'reserve', 'reservation', 'pax', 'guest', 'guests', 'person', 'persons', 'people', 'thats', 'that', 'all', 'good', 'lang', 'po', 'ko', 'only', 'na', 'done', 'we', 'are', 'said', 'continue', 'my', 'booking'];
            $containsNonName = (bool) preg_match('/\b(?:yes+|yep|yeah|yea|yup|yeap|sure|surely|ok(?:ay)?|k|okie|alright|all\s+right|good|that\'?s\s+good|sounds?\s+good|looks?\s+good|all\s+good|great|nice|perfect|sige|cge|ge|g|oo|opo|proceed|reserve|book|both|thats\s+all)\b/i', $cleanedCandidate);
            if (!$containsNonName) {
                foreach ($cleanWords as $w) {
                    $wLower = strtolower($w);
                    if (in_array($wLower, $nonNameWords) || in_array(str_replace('-', '', $wLower), $nonNameWords) || str_starts_with($wLower, 'yes')) {
                        $containsNonName = true;
                        break;
                    }
                }
            }
            if (!$hasAmenityMention && !$containsNonName && count($cleanWords) >= 1 && count($cleanWords) <= 4 && strlen($cleanedCandidate) >= 3 && strlen($cleanedCandidate) <= 40) {
                $draft['guest']['name'] = ucwords(strtolower(implode(' ', $cleanWords)));
            }
        }

        // 4.5 Step determination
        if (!empty($draft['amenities'])) {
            if (empty($draft['startDate'])) {
                $draft['step'] = 'date_slot';
                unset($draft['guest_field']);
            } elseif (empty($draft['guest']['name'])) {
                $draft['step'] = 'guest_info';
                $draft['guest_field'] = 'name';
            } elseif (empty($draft['guest']['phone'])) {
                $draft['step'] = 'guest_info';
                $draft['guest_field'] = 'phone';
            } elseif (empty($draft['guest']['email'])) {
                $draft['step'] = 'guest_info';
                $draft['guest_field'] = 'email';
            } elseif (empty($draft['guest']['guests_count'])) {
                $draft['step'] = 'guest_info';
                $draft['guest_field'] = 'guests_count';
            } else {
                $draft['step'] = 'payment';
                unset($draft['guest_field']);
            }
        }

        // 4.6 Function Hall Sanity Guardrail: Function Hall is strictly for 15+ pax.
        // If the user is a couple, girlfriend/boyfriend, or specifies <= 4 pax, purge Function Hall!
        $isCoupleOrSmallGroup = (bool) preg_match('/\b(?:couple|mag-?asawa|magkasintahan|girlfriend|boyfriend|gf|bf|wife|husband|partner|asawa|uyab|pares)\b/i', $userMessage)
            || (preg_match('/(\d+)\s*(?:people|persons|pax|guests|heads|kabuok|ka\s+tao|tao)/i', $userMessage, $paxM) && (int)$paxM[1] <= 4)
            || (isset($draft['guest']['guests_count']) && (int)$draft['guest']['guests_count'] <= 4);

        if ($isCoupleOrSmallGroup) {
            if (!empty($draft['amenities'])) {
                $draft['amenities'] = array_values(array_filter($draft['amenities'], function($a) {
                    return stripos($a['name'] ?? '', 'Function Hall') === false;
                }));
                $draft['amenity'] = $draft['amenities'][0] ?? null;
            }
            if (!empty($draft['tentativeAmenities'])) {
                $draft['tentativeAmenities'] = array_values(array_filter($draft['tentativeAmenities'], function($name) {
                    return stripos($name, 'Function Hall') === false;
                }));
                $draft['tentativeAmenity'] = !empty($draft['tentativeAmenities']) ? implode(', ', $draft['tentativeAmenities']) : null;
            }
        }

        return $draft;
    }

    /**
     * Assemble live guest context from the 12 permitted database tables:
     * 1. park_settings
     * 2. daily_weather_shift_logs
     * 3. amenities
     * 4. amenities_benefits
     * 5. reservation_amenities
     * 6. park_activities
     * 7. park_rules
     * 8. park_events
     * 9. feedbacks
     * 10. reservations
     * 11. customers
     * 12. reservation_guests
     */
    private function getGuestContext(string $message, ?array $bookingDraft = null): string
    {
        $context = '';
        $now = now();
        $todayStr = $now->toDateString();
        $todayReadable = $now->format('F j, Y');
        $currentTimeStr = $now->format('F j, Y - g:i A');

        $context .= "Current Date/Time: {$currentTimeStr}\n";

        $isCoupleOrSmallGroup = (bool) preg_match('/\b(?:couple|mag-?asawa|magkasintahan|girlfriend|boyfriend|gf|bf|wife|husband|partner|asawa|uyab|pares)\b/i', $message)
            || (preg_match('/(\d+)\s*(?:people|persons|pax|guests|heads|kabuok|ka\s+tao|tao)/i', $message, $paxM) && (int)$paxM[1] <= 4)
            || (!empty($bookingDraft['guest']['guests_count']) && (int)$bookingDraft['guest']['guests_count'] <= 4);

        if ($isCoupleOrSmallGroup) {
            if (!empty($bookingDraft['amenities']) && is_array($bookingDraft['amenities'])) {
                $bookingDraft['amenities'] = array_values(array_filter($bookingDraft['amenities'], function($a) {
                    return stripos($a['name'] ?? '', 'Function Hall') === false;
                }));
                $bookingDraft['amenity'] = $bookingDraft['amenities'][0] ?? null;
            }
            if (!empty($bookingDraft['tentativeAmenities']) && is_array($bookingDraft['tentativeAmenities'])) {
                $bookingDraft['tentativeAmenities'] = array_values(array_filter($bookingDraft['tentativeAmenities'], function($name) {
                    return stripos($name, 'Function Hall') === false;
                }));
                $bookingDraft['tentativeAmenity'] = !empty($bookingDraft['tentativeAmenities']) ? implode(', ', $bookingDraft['tentativeAmenities']) : null;
            }
        }

        $draftAmenities = !empty($bookingDraft['amenities']) && is_array($bookingDraft['amenities'])
            ? $bookingDraft['amenities']
            : (!empty($bookingDraft['amenity']) ? [$bookingDraft['amenity']] : []);

        $startDateVal = $bookingDraft['startDate'] ?? ($bookingDraft['date'] ?? null);
        $hasValidFutureSchedule = !empty($startDateVal) && $startDateVal >= $todayStr;

        if (!empty($draftAmenities)) {
            $amNames = array_map(fn ($a) => ($a['name'] ?? 'Amenity') . " (₱" . number_format((float) ($a['price'] ?? 0), 2) . ")", $draftAmenities);
            $total = array_sum(array_map(fn ($a) => (float) ($a['price'] ?? 0), $draftAmenities));
            $dp = round($total * 0.5, 2);
            $bal = round($total - $dp, 2);
            $startDate = $hasValidFutureSchedule ? $startDateVal : 'Pending (Date not yet confirmed)';
            $endDate = $bookingDraft['endDate'] ?? $startDate;
            $startSlot = $bookingDraft['startSlot'] ?? ($bookingDraft['slot'] ?? 'Daytime');
            $endSlot = $bookingDraft['endSlot'] ?? $startSlot;
            $booker = !empty($bookingDraft['guest']['name']) ? $bookingDraft['guest']['name'] : 'Pending name';
            $phone = !empty($bookingDraft['guest']['phone']) ? $bookingDraft['guest']['phone'] : 'Pending phone';
            $hasName = !empty($bookingDraft['guest']['name']);
            $hasPhone = !empty($bookingDraft['guest']['phone']);
            $hasEmail = !empty($bookingDraft['guest']['email']);
            $hasPax = !empty($bookingDraft['guest']['guests_count']);
            $pax = $hasPax ? (int) $bookingDraft['guest']['guests_count'] : null;
            $tentativeAm = $bookingDraft['tentativeAmenity'] ?? null;

            $msgActions = $this->extractAmenityActionsFromMessage($message);
            $stageContext = '';
            if ($msgActions['is_swap'] && $msgActions['swap_from'] && $msgActions['swap_to']) {
                $swapFrom = $msgActions['swap_from'];
                $swapTo = $msgActions['swap_to'];
                $isToAvail = $hasValidFutureSchedule ? $this->checkAmenityAvailability($swapTo->id, $startDateVal, $startSlot) : true;
                $toPrice = $startSlot === 'Nighttime' ? (float)($swapTo->nighttime_price ?? 0) : (float)($swapTo->daytime_price ?? 0);
                $nextPrompt = !$hasName ? "ask for their Full Name" : (!$hasPhone ? "ask for their Philippine Mobile Number" : (!$hasEmail ? "ask for their Email Address" : (!$hasPax ? "ask for the Number of Guests / Pax" : "present the complete neat reservation summary with [PROCEED_PAYMENT_BUTTON] on its own line")));

                $stageContext = "- AMENITY SWAP REQUESTED BY GUEST IN THIS MESSAGE:\n"
                    . "  * The guest explicitly requested to change/swap {$swapFrom->amenities_name} into {$swapTo->amenities_name}!\n"
                    . "  * Chosen Schedule: {$startDate} [{$startSlot}]\n"
                    . "  * Live Availability of {$swapTo->amenities_name} on {$startDate} [{$startSlot}]: " . ($isToAvail ? "100% AVAILABLE! (Rate: ₱" . number_format($toPrice, 2) . ")" : "RESERVED / UNAVAILABLE on this slot!") . "\n"
                    . "  * Current Picked Amenities in Draft: " . implode(', ', $amNames) . " (Total: ₱" . number_format($total, 2) . ", 50% downpayment: ₱" . number_format($dp, 2) . ")\n"
                    . "  * MANDATORY ACTION FOR BREN:\n"
                    . "    1. Confirm warmly that {$swapTo->amenities_name} is available on {$startDate} ({$startSlot}) and has replaced {$swapFrom->amenities_name}!\n"
                    . "    2. Confirm their current picked amenities: " . implode(' and ', $amNames) . " (Total: ₱" . number_format($total, 2) . ", 50% downpayment: ₱" . number_format($dp, 2) . ").\n"
                    . "    3. STRICTLY DO NOT say 'your selection already includes {$swapTo->amenities_name}' or 'would leave it selected twice'! The swap is already applied cleanly.\n"
                    . "    4. Immediately continue to the next missing detail: {$nextPrompt}!\n";
            } elseif ($msgActions['is_add'] && !empty($msgActions['wanted'])) {
                $addedNames = array_map(fn($m) => $m->amenities_name, $msgActions['wanted']);
                $availNotes = [];
                foreach ($msgActions['wanted'] as $wAm) {
                    $wAvail = $hasValidFutureSchedule ? $this->checkAmenityAvailability($wAm->id, $startDateVal, $startSlot) : true;
                    $wPrice = $startSlot === 'Nighttime' ? (float)($wAm->nighttime_price ?? 0) : (float)($wAm->daytime_price ?? 0);
                    $availNotes[] = "{$wAm->amenities_name}: " . ($wAvail ? "100% AVAILABLE (Rate: ₱" . number_format($wPrice, 2) . ")" : "RESERVED on this slot");
                }
                $nextPrompt = !$hasName ? "ask for their Full Name" : (!$hasPhone ? "ask for their Philippine Mobile Number" : (!$hasEmail ? "ask for their Email Address" : (!$hasPax ? "ask for the Number of Guests / Pax" : "present the complete neat reservation summary with [PROCEED_PAYMENT_BUTTON] on its own line")));

                $stageContext = "- AMENITY ADDED BY GUEST IN THIS MESSAGE:\n"
                    . "  * Added: " . implode(', ', $addedNames) . "\n"
                    . "  * Chosen Schedule: {$startDate} [{$startSlot}]\n"
                    . "  * Live Availability on {$startDate} [{$startSlot}]: " . implode('; ', $availNotes) . "\n"
                    . "  * Current Picked Amenities in Draft: " . implode(', ', $amNames) . " (Total: ₱" . number_format($total, 2) . ", 50% downpayment: ₱" . number_format($dp, 2) . ")\n"
                    . "  * MANDATORY ACTION FOR BREN: Warmly confirm the addition and its live availability on {$startDate} ({$startSlot}), state the updated total and 50% downpayment, and {$nextPrompt}!\n";
            } elseif ($tentativeAm) {
                $tentativeList = is_array($bookingDraft['tentativeAmenities'] ?? null)
                    ? $bookingDraft['tentativeAmenities']
                    : array_map('trim', explode(',', $tentativeAm));
                $tAvailNotes = [];
                foreach ($tentativeList as $tName) {
                    $m = Amenity::where('status', true)->where('amenities_name', 'LIKE', "%{$tName}%")->first();
                    if ($m) {
                        $mAvail = $hasValidFutureSchedule ? $this->checkAmenityAvailability($m->id, $startDateVal, $startSlot) : true;
                        $mPrice = $startSlot === 'Nighttime' ? (float)($m->nighttime_price ?? 0) : (float)($m->daytime_price ?? 0);
                        $tAvailNotes[] = "{$m->amenities_name}: " . ($mAvail ? "100% AVAILABLE (Rate: ₱" . number_format($mPrice, 2) . ")" : "RESERVED on this slot");
                    }
                }
                $stageContext = "- Inquired Additional / Replacement Amenity: {$tentativeAm}\n"
                    . "  * Schedule: {$startDate} [{$startSlot}]\n"
                    . "  * Live Availability on {$startDate} [{$startSlot}]: " . implode('; ', $tAvailNotes) . "\n"
                    . "  * ACTION: Tell the guest if {$tentativeAm} is available on their date {$startDate} [{$startSlot}], state its rate, and ask if they want to ADD it alongside their existing amenity (" . implode(', ', $amNames) . ") or SWITCH to it instead. Speak directly to the guest without meta-reasoning!\n";
            } elseif (!$hasValidFutureSchedule) {
                $stageContext = "- Current Stage: Amenities are selected (" . implode(', ', $amNames) . "), but Visit Date is not yet chosen.\n"
                    . "  * ACTION: Warmly acknowledge the selected amenities (" . implode(', ', $amNames) . ") and ask what date they would like to visit Hinaguan Nature Park (Daytime or Nighttime) so you can lock in their schedule and verify live availability!\n"
                    . "  * STRICTLY DO NOT ask for Full Name, phone, or email yet, and DO NOT output [PROCEED_PAYMENT_BUTTON] until a valid future date is chosen!\n";
            } elseif (!$hasName) {
                $stageContext = "- Current Stage: Step 3 — Primary Booker Details (Sub-step 1: Full Name).\n"
                    . "  * Schedule: {$startDate} [{$startSlot}]" . ($startDate !== $endDate ? " to {$endDate} [{$endSlot}]" : "") . "\n"
                    . "  * Amenities: " . implode(', ', $amNames) . "\n"
                    . ($hasPax ? "  * Number of Guests: {$pax} pax (Already recorded)\n" : "")
                    . "  * ACTION: Acknowledge the locked amenities and schedule warmly and ask ONLY for their Full Name to prepare their digital reservation pass. (e.g. 'May I have your Full Name?'). DO NOT ask for phone, email, or guest count yet, and DO NOT output [PROCEED_PAYMENT_BUTTON]!\n";
            } elseif (!$hasPhone) {
                $stageContext = "- Current Stage: Step 3 — Primary Booker Details (Sub-step 2: Mobile Number).\n"
                    . "  * Guest Name Provided: {$booker}\n"
                    . "  * ACTION: Greet {$booker} warmly and ask ONLY for their Philippine Mobile Number (e.g. 0917 123 4567) for gate entry check-in and SMS confirmation. DO NOT ask for email or guest count yet!\n";
            } elseif (!$hasEmail) {
                $stageContext = "- Current Stage: Step 3 — Primary Booker Details (Sub-step 3: Email Address).\n"
                    . "  * Guest Name: {$booker} | Phone: {$phone}\n"
                    . "  * ACTION: Thank {$booker} and ask ONLY for their Email Address to send the digital reservation pass and QR code. DO NOT ask for guest count yet!\n";
            } elseif (!$hasPax) {
                $stageContext = "- Current Stage: Step 3 — Primary Booker Details (Sub-step 4: Number of Guests / Pax).\n"
                    . "  * Guest Name: {$booker} | Phone: {$phone} | Email: " . ($bookingDraft['guest']['email'] ?? '') . "\n"
                    . "  * ACTION: Ask how many guests in total will be visiting/staying (mention max capacity if applicable). STRICTLY DO NOT output [PROCEED_PAYMENT_BUTTON] and DO NOT ask for downpayment yet!\n";
            } else {
                $stageContext = "- Current Stage: Step 4 — Reservation Summary & 50% Downpayment.\n"
                    . "  * All details collected! Name: {$booker}, Phone: {$phone}, Email: " . ($bookingDraft['guest']['email'] ?? '') . ", Guests: {$pax}\n"
                    . "  * ACTION: Present the complete neat reservation summary (Schedule, Amenity, Booker details, Total ₱, 50% non-refundable downpayment required ₱, remaining balance at check-in ₱) and conclude with the token [PROCEED_PAYMENT_BUTTON] on its own line!\n"
                    . "  * Note: If the guest wants to change or add something before paying, warmly help them adjust!\n";
            }

            $context .= "\n[ACTIVE USER BOOKING DRAFT IN PROGRESS (STORED IN LOCALSTORAGE)]:\n"
                . "- Picked Amenities (" . count($draftAmenities) . " items): " . implode(', ', $amNames) . "\n"
                . "- Schedule: " . ($hasValidFutureSchedule ? "Check-in {$startDate} [{$startSlot}] to Check-out {$endDate} [{$endSlot}]" : "Date pending / unconfirmed") . "\n"
                . "- Primary Booker: {$booker} (Phone: {$phone})\n"
                . "- Number of Guests: " . ($hasPax ? "{$pax} pax" : "Pending (Awaiting guest headcount)") . "\n"
                . "- Total: ₱" . number_format($total, 2) . " | 50% Downpayment Required: ₱" . number_format($dp, 2) . " | Balance Due: ₱" . number_format($bal, 2) . "\n"
                . $stageContext
                . "- Note: If the user asks 'show me what amenity i just avail before it book', confirm these exact details to them!\n";
        } elseif (!empty($bookingDraft['startDate']) && $bookingDraft['startDate'] >= $todayStr) {
            $startDate = $bookingDraft['startDate'];
            $endDate = $bookingDraft['endDate'] ?? $startDate;
            $startSlot = $bookingDraft['startSlot'] ?? ($bookingDraft['slot'] ?? 'Daytime');
            $endSlot = $bookingDraft['endSlot'] ?? $startSlot;
            $sched = ($startDate === $endDate && $startSlot === $endSlot) ? "{$startDate} [{$startSlot}]" : "{$startDate} [{$startSlot}] to {$endDate} [{$endSlot}]";
            $tentativeAm = $bookingDraft['tentativeAmenity'] ?? null;
            $tentativeAms = $bookingDraft['tentativeAmenities'] ?? [];
            $tentativeAmsList = !empty($tentativeAms) && is_array($tentativeAms) ? implode(', ', $tentativeAms) : $tentativeAm;
            $tentativeCategory = $bookingDraft['tentativeCategory'] ?? null;
            $topicAmenity = $tentativeAmsList ?: $tentativeCategory;
            $context .= "\n[ACTIVE USER BOOKING DRAFT IN PROGRESS (STORED IN LOCALSTORAGE)]:\n"
                . "- Confirmed Schedule: {$sched}\n";
            if ($topicAmenity) {
                $context .= "- Inquired / Proposed Amenity (ACTIVE TOPIC OF DISCUSSION): {$topicAmenity}\n"
                    . "  * CRITICAL MANDATORY INSTRUCTION FOR BREN: You and the guest are ALREADY talking about {$topicAmenity}!\n"
                    . "  * STRICTLY DO NOT dump the table of other amenities (Cottages, Payags, Function Hall) and DO NOT ask 'Which one catches your eye?' or ask what amenity they want again!\n"
                    . "  * Confirm {$topicAmenity} for their schedule {$sched}! If this is the air-conditioned A-House (units: A-House 2 and A-House 3, ₱1,000 night / ₱700 day), clarify preference between A-House 2 and A-House 3, and ask for their Full Name to proceed with their reservation pass!\n";
            } else {
                $context .= "- Current Stage: Schedule is confirmed. Ask what amenity they have in mind (cozy A-Houses, bamboo Payags, open-air Cottages, Function Hall). DO NOT output [VIEW_AMENITIES_BUTTON] yet!\n";
            }
        } elseif (!empty($bookingDraft['pastDateClarification'])) {
            $clar = $bookingDraft['pastDateClarification'];
            if (!empty($clar['recordedDateExpired'])) {
                $pDate = $clar['pastDate'] ?? 'the previously selected date';
                try {
                    $pDateFmt = Carbon::parse($pDate)->format('F j, Y');
                } catch (\Exception $e) {
                    $pDateFmt = $pDate;
                }
                $context .= "\n[CRITICAL ALERT — PREVIOUSLY RECORDED BOOKING DATE HAS PASSED (STRICTLY FORBIDDEN)]:\n"
                    . "- The guest's previously recorded visit date was {$pDateFmt} ({$pDate}), but today's actual date is {$todayReadable}!\n"
                    . "- THIS RECORDED DATE HAS ALREADY PASSED. RESERVING OR CONTINUING A BOOKING FOR A PAST DATE IS STRICTLY IMPOSSIBLE.\n"
                    . "- MANDATORY INSTRUCTION: You MUST NOT confirm, continue, or check availability for {$pDateFmt}. Under NO circumstances may you say {$pDateFmt} is available or continue the reservation for it!\n"
                    . "- State clearly, warmly, and firmly that their previously chosen date ({$pDateFmt}) has already passed and reservations for past dates are strictly not possible.\n"
                    . "- DEMAND / ASK them what upcoming date (from today {$todayReadable} onwards) they would like to visit Hinaguan Nature Park!\n"
                    . "- STRICTLY DO NOT output [VIEW_AMENITIES_BUTTON] and DO NOT output [PROCEED_PAYMENT_BUTTON]!\n";
            } elseif (!empty($clar['expiredPast'])) {
                $context .= "\n[PAST DATE INQUIRY REJECTED]:\n"
                    . "- The guest mentioned a past date (" . ($clar['pastDate'] ?? 'past date') . ") which has ALREADY PASSED!\n"
                    . "- ACTION: Hinaguan Nature Park strictly does NOT accept past bookings. Inform the guest warmly that the date has passed and ask them what upcoming date from today onwards they would like to visit.\n";
            } else {
                $monthDay = $clar['monthDay'] ?? 'That date';
                $curY = $clar['currentYear'] ?? $now->year;
                $nextY = $clar['nextYear'] ?? ($now->year + 1);
                $context .= "\n[PAST DATE INQUIRY - CLARIFICATION REQUIRED (CRITICAL)]:\n"
                    . "- The guest mentioned {$monthDay}, but for the current year ({$curY}) this date has ALREADY PASSED!\n"
                    . "- ACTION: DO NOT accept the date for {$curY} and DO NOT say it sounds like a wonderful date for {$curY}!\n"
                    . "- WARM CLARIFICATION: Politely clarify if the guest meant NEXT YEAR ({$nextY}): '{$monthDay} for this year ({$curY}) has already passed! Did you mean {$monthDay}, {$nextY} (next year), or were you thinking of another upcoming date? Also, will that be for Daytime (8:00 AM – 5:00 PM) or Nighttime (6:00 PM – 8:00 AM)?'\n"
                    . "- DO NOT output [VIEW_AMENITIES_BUTTON]!\n";
            }
        } elseif (!empty($bookingDraft['tentativeDate'])) {
            $tDate = $bookingDraft['tentativeDate'];
            $tSlot = $bookingDraft['tentativeSlot'] ?? 'Daytime';
            $context .= "\n[ACTIVE USER BOOKING DRAFT IN PROGRESS (STORED IN LOCALSTORAGE)]:\n"
                . "- Tentative / Proposed Date: {$tDate} [{$tSlot}] (NOT CONFIRMED YET! The guest proposed or asked about this date. Make sure if they are sure about this date and whether it's Daytime or Nighttime before locking it in! DO NOT output [VIEW_AMENITIES_BUTTON]!)\n";
        }

        // Table 1: park_settings
        $settings = ParkSetting::first();
        if ($settings) {
            $isOpen = ($settings->park_status ?? 'open') === 'open';
            $statusStr = $isOpen 
                ? "OPEN (Operating normally for all visitors)" 
                : "CLOSED (Reason: " . ($settings->close_description ?: 'Temporarily closed for maintenance') . ")";

            $dayStart = $settings->daytime_start ? Carbon::parse($settings->daytime_start)->format('g:i A') : '8:00 AM';
            $dayEnd = $settings->daytime_end ? Carbon::parse($settings->daytime_end)->format('g:i A') : '5:00 PM';
            $nightStart = $settings->nighttime_start ? Carbon::parse($settings->nighttime_start)->format('g:i A') : '6:00 PM';
            $nightEnd = $settings->nighttime_end ? Carbon::parse($settings->nighttime_end)->format('g:i A') : '8:00 AM';

            $openTime = $settings->opening_time ? Carbon::parse($settings->opening_time)->format('g:i A') : '8:00 AM';
            $closeTime = $settings->closing_time ? Carbon::parse($settings->closing_time)->format('g:i A') : '5:00 PM';

            $dayAdult = number_format((float) ($settings->daytime_adult_entrance_fee ?? 0), 2);
            $dayChild = (float) ($settings->daytime_child_entrance_fee ?? 0);
            $dayChildStr = $dayChild > 0 ? "₱" . number_format($dayChild, 2) : "₱0.00 (FREE for children)";

            $nightAdult = number_format((float) ($settings->nighttime_adult_entrance_fee ?? 0), 2);
            $nightChild = (float) ($settings->nighttime_child_entrance_fee ?? 0);
            $nightChildStr = $nightChild > 0 ? "₱" . number_format($nightChild, 2) : "₱0.00 (FREE for children)";

            $dayPool = number_format((float) ($settings->day_pool_fee ?? 0), 2);
            $nightPool = number_format((float) ($settings->night_pool_fee ?? 0), 2);

            $brendaStatus = $settings->brenda_available ? "YES (Brenda Mage is available / at the park)" : "NO (Brenda Mage is not available today)";

            $context .= "\n[1. OFFICIAL PARK SETTINGS, GATE HOURS & RATES (FROM DATABASE park_settings)]:\n"
                . "- Park Status: {$statusStr}\n"
                . "- Park Gate Hours: {$openTime} to {$closeTime}\n"
                . "- Daytime Session Hours: {$dayStart} - {$dayEnd}\n"
                . "  * Daytime Adult Entrance Fee: ₱{$dayAdult}\n"
                . "  * Daytime Child (12 & below) Entrance Fee: {$dayChildStr}\n"
                . "- Nighttime Session Hours: {$nightStart} - {$nightEnd}\n"
                . "  * Nighttime Adult Entrance Fee: ₱{$nightAdult}\n"
                . "  * Nighttime Child (12 & below) Entrance Fee: {$nightChildStr}\n"
                . "- Swimming Pool Access Fees:\n"
                . "  * Day Swim Pool: ₱{$dayPool} per person\n"
                . "  * Night Swim Pool: ₱{$nightPool} per person\n"
                . "- Brenda Mage Availability: {$brendaStatus}\n"
                . "- Park Hotline & Inquiries: " . ($settings->contact_number ?: '0985-323-9532') . "\n"
                . "- Park Email: " . ($settings->email ?: 'parkhinaguan@gmail.com') . "\n";
        }

        // Table 2: daily_weather_shift_logs
        $currentShift = ($now->hour >= 6 && $now->hour < 18) ? 'Daytime' : 'Nighttime';
        $weatherLog = DailyWeatherShiftLog::where('log_date', $todayStr)
            ->where('shift', $currentShift)
            ->first()
            ?? DailyWeatherShiftLog::where('log_date', $todayStr)->first()
            ?? DailyWeatherShiftLog::orderBy('log_date', 'desc')->first();

        if ($weatherLog) {
            $wCond = $weatherLog->weather_condition;
            $wTemp = $weatherLog->temperature_celsius ? $weatherLog->temperature_celsius . '°C' : '30°C';
            $wPrecip = (int) $weatherLog->precipitation_probability;
            $wShift = $weatherLog->shift ?: $currentShift;
            $wNotes = $weatherLog->notes ? " (Notes: {$weatherLog->notes})" : "";

            $weatherAdvice = ($wPrecip >= 40 || stripos($wCond, 'rain') !== false)
                ? "Rainy/Wet Weather Advice: Suggest weather-sheltered spots like cozy A-Houses, Function Hall, or covered dining cottages to keep guests dry."
                : "Sunny/Fair Weather Advice: Ideal for swimming pool access and open-air riverside/mountain payags (Payag 2, 3, 5, 6) or cottages!";

            $context .= "\n[2. TODAY'S LIVE WEATHER CONDITIONS (FROM DATABASE daily_weather_shift_logs)]:\n"
                . "- Condition: {$wCond} ({$wShift} shift) | Temp: {$wTemp} | Rain Probability: {$wPrecip}%{$wNotes}\n"
                . "- Weather Guidance: {$weatherAdvice}\n";
        }

        // Tables 3 & 4: amenities & amenities_benefits
        $amenities = Amenity::with('benefits')->where('status', true)->get();
        $context .= "\n[3. OFFICIAL PARK AMENITIES WITH LOCATIONS, VIBES & DESCRIPTIONS (FROM DATABASE amenities & amenities_benefits)]:\n";
        foreach ($amenities as $am) {
            $benefit = $am->benefits;
            $freeEntrance = ($benefit && $benefit->free_entrance) ? 'YES (Free entrance included with this booking)' : 'NO (Regular entrance fees apply)';
            $freePool = ($benefit && $benefit->free_pool) ? 'YES (Free pool access included)' : 'NO (Separate pool fee required)';
            $aircon = ($benefit && $benefit->is_aircon) ? 'YES (Air-conditioned)' : 'NO (Open-air / Non-aircon)';
            $addHead = number_format((float) $am->additional_per_head, 2);

            $name = $am->amenities_name;
            $minCap = !empty($am->minimum_capacity) ? (int)$am->minimum_capacity : 1;
            if (!empty($am->maximum_capacity)) {
                $maxCap = (int)$am->maximum_capacity;
            } elseif (stripos($name, 'function hall') !== false || stripos($name, 'hall') !== false) {
                $minCap = 15;
                $maxCap = 50;
            } elseif (stripos($name, 'cottage') !== false) {
                $maxCap = 10;
            } elseif (stripos($name, 'payag') !== false) {
                $maxCap = 8;
            } elseif (stripos($name, 'a-house') !== false) {
                $maxCap = 2;
            } else {
                $maxCap = $minCap;
            }
            $capLabel = ($minCap === $maxCap) ? "{$minCap} persons" : "{$minCap} to {$maxCap} persons";

            $context .= "- {$am->amenities_name} (Capacity: {$capLabel}):\n"
                . "  * Description, Vibe & Location: \"{$am->description}\"\n"
                . "  * Rates: Daytime: ₱" . number_format((float) $am->daytime_price, 2) . " | Nighttime: ₱" . number_format((float) $am->nighttime_price, 2) . " | Extra Head: ₱{$addHead}\n"
                . "  * Inclusions: Free Entrance: {$freeEntrance} | Free Pool: {$freePool} | Air-conditioned: {$aircon}\n";
        }

        // Table 5: reservation_amenities & reservations (Live occupancy & upcoming availability)
        $upcomingReservations = ReservationAmenity::with(['reservation', 'amenity'])
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhere('status', '!=', 'Completed');
            })
            ->whereHas('reservation', function ($rq) {
                $rq->whereNotIn('status', ['Cancelled', 'Checked Out', 'cancelled', 'checked out', 'checked_out', 'checked-out']);
            })
            ->where(function ($q) use ($todayStr) {
                $q->where('start_date', '>=', $todayStr)
                  ->orWhere('end_date', '>=', $todayStr)
                  ->orWhereHas('reservation', function ($rq) use ($todayStr) {
                      $rq->where('reservation_date', '>=', $todayStr)
                         ->orWhere('end_date', '>=', $todayStr)
                         ->orWhereIn('status', ['Checked In', 'checked in', 'Active', 'active']);
                  });
            })
            ->get();

        $context .= "\n[4. LIVE AMENITY AVAILABILITY & UPCOMING RESERVED DATES (FROM DATABASE reservation_amenities)]:\n";
        $bookedByAmenity = [];
        foreach ($upcomingReservations as $ra) {
            $amName = $ra->amenity?->amenities_name;
            if (!$amName) continue;
            $res = $ra->reservation;
            $sDate = $ra->start_date ? $ra->start_date->toDateString() : ($res?->reservation_date ? $res->reservation_date->toDateString() : $todayStr);
            $eDate = $ra->end_date ? $ra->end_date->toDateString() : ($res?->end_date ? $res->end_date->toDateString() : $sDate);
            $sSlot = $ra->start_slot ?: ($res?->start_slot ?? 'Daytime');
            $eSlot = $ra->end_slot ?: ($res?->end_slot ?? $sSlot);
            $resStatus = $res ? $res->status : ($ra->status ?: 'Reserved');

            $dateRange = ($sDate === $eDate && $sSlot === $eSlot)
                ? "{$sDate} [{$sSlot}]"
                : "{$sDate} [{$sSlot}] to {$eDate} [{$eSlot}]";

            $bookedByAmenity[$amName][] = "{$dateRange} ({$resStatus})";
        }

        if (!empty($bookedByAmenity)) {
            foreach ($bookedByAmenity as $amName => $ranges) {
                $context .= "- {$amName}: RESERVED on " . implode(', ', $ranges) . ". (Available on all other upcoming dates!)\n";
            }
            $allAmenityNames = Amenity::where('status', true)->pluck('amenities_name')->toArray();
            $unreserved = array_diff($allAmenityNames, array_keys($bookedByAmenity));
            if (!empty($unreserved)) {
                $context .= "- All other amenities currently have NO upcoming reservations and are 100% available: " . implode(', ', $unreserved) . "\n";
            }
        } else {
            $context .= "All amenities (Cottages 1-6, Payags 1-6, A-Houses 1-8, Function Hall) currently have NO upcoming reservations and are 100% available for booking!\n";
        }

        // Group size guide
        $context .= "\n[GROUP SIZE RECOMMENDATION RULES (CRITICAL)]:\n"
            . "- IMPORTANT: ONLY use these recommendations when the user is explicitly asking for suggestions or what amenity fits their group (e.g. 'what is best for 10 pax?'). NEVER use these recommendations if the user is answering a booking prompt (such as saying 'its 2', '2 guests', or providing their headcount during an active booking in progress)! Answering headcount during a booking means updating the reservation draft, NOT recommending amenities.\n"
            . "- 1 to 2 persons: Recommend A-Houses (A-House 1 to 8, ₱300 day / ₱500 night, private cabin for solo or couples).\n"
            . "- 3 to 6 persons (Small groups / Families): Recommend Cottages (Cottage 1 to 6 at ₱200, open-air with dining table and chairs) OR Payags (Payag 1 to 6 at ₱300, native bamboo huts). NEVER recommend Function Hall for small groups of 3 to 6 people!\n"
            . "- 7 to 10 persons: Recommend Cottages (₱200) or Payags (₱300).\n"
            . "- 15 to 50+ persons (Large events, reunions, corporate gatherings): Recommend Function Hall (₱5,000 day / ₱10,000 night, includes free entrance & pool access).\n";

        // Intelligent connected topic detection
        $topics = $this->detectGuestTopics($message);

        // Table 6: park_activities
        if (!empty($topics['activities']) || empty($topics['is_specific'])) {
            $activities = ParkActivity::all();
            if ($activities->isNotEmpty()) {
                $context .= "\n[5. OFFICIAL PARK ACTIVITIES (FROM DATABASE park_activities)]:\n";
                foreach ($activities as $act) {
                    $context .= "- {$act->activity}: {$act->description}\n";
                }
            }
        }

        // Table 7: park_rules
        if (!empty($topics['rules']) || empty($topics['is_specific'])) {
            $rules = ParkRule::all();
            if ($rules->isNotEmpty()) {
                $context .= "\n[6. OFFICIAL PARK RULES & POLICIES (FROM DATABASE park_rules)]:\n";
                foreach ($rules as $r) {
                    $context .= "- {$r->rule_name}: {$r->rule_descriptions}\n";
                }
            }
        }

        // Table 8: park_events
        if (!empty($topics['events']) || empty($topics['is_specific'])) {
            $events = ParkEvent::where('is_active', true)->orderBy('date')->get();
            if ($events->isNotEmpty()) {
                $context .= "\n[7. ACTIVE PARK EVENTS & HAPPENINGS (FROM DATABASE park_events)]:\n";
                foreach ($events as $ev) {
                    $dateStr = $ev->date ? Carbon::parse($ev->date)->format('M d, Y') : 'Date TBA';
                    $dayStr = $ev->day ? " ({$ev->day})" : "";
                    $timeStr = $ev->time ? " at {$ev->time}" : "";
                    $context .= "- {$ev->title}: {$dateStr}{$dayStr}{$timeStr} - {$ev->event}\n";
                }
            }
        }

        // Table 9: feedbacks
        if (!empty($topics['feedbacks']) || empty($topics['is_specific'])) {
            $feedbackCount = Feedback::count();
            $feedbacks = Feedback::where('is_shown', true)->latest()->take(3)->get();
            if ($feedbackCount > 0 || $feedbacks->isNotEmpty()) {
                $avgStars = number_format((float) (Feedback::avg('stars') ?: 5.0), 1);
                $context .= "\n[8. GUEST REVIEWS & FEEDBACK (FROM DATABASE feedbacks)]:\n"
                    . "- Overall Rating: {$avgStars} / 5.0 stars ({$feedbackCount} verified reviews).\n";
                foreach ($feedbacks as $fb) {
                    $name = $fb->is_anonymous ? 'A guest' : ($fb->full_name ?: 'A guest');
                    $context .= "- {$name} ({$fb->stars} stars): \"{$fb->description}\"\n";
                }
            }
        }

        // Tables 10, 11 & 12: reservations, customers, reservation_guests
        if (!empty($topics['specific_id']) || !empty($topics['search_terms']) || (!empty($topics['reservations']) && !empty($topics['is_specific']))) {
            $matchedReservations = collect();

            if (!empty($topics['specific_id'])) {
                $foundById = Reservation::with(['reservationAmenities.amenity', 'reservationGuests.customer'])
                    ->find($topics['specific_id']);
                if ($foundById) {
                    $matchedReservations->push($foundById);
                }
            }

            if (!empty($topics['search_terms'])) {
                foreach ($topics['search_terms'] as $term) {
                    $found = Reservation::with(['reservationAmenities.amenity', 'reservationGuests.customer'])
                        ->where(function ($q) use ($term) {
                            $q->where('booker_name', 'like', "%{$term}%")
                              ->orWhere('phone', 'like', "%{$term}%")
                              ->orWhere('email', 'like', "%{$term}%");
                        })
                        ->take(3)
                        ->get();
                    $matchedReservations = $matchedReservations->merge($found);
                }
            }

            $matchedReservations = $matchedReservations->unique('id');

            if ($matchedReservations->isNotEmpty()) {
                $context .= "\n[9. GUEST RESERVATION STATUS (FROM DATABASE reservations, customers, reservation_guests)]:\n";
                foreach ($matchedReservations as $r) {
                    $ams = $r->reservationAmenities->map(fn ($ra) => ($ra->amenity?->amenities_name ?? 'Amenity') . " [{$ra->status}]")->implode(', ');
                    $guestList = $r->reservationGuests->map(fn ($g) => $g->customer ? "{$g->customer->first_name} {$g->customer->last_name}" : 'Guest')->implode(', ');
                    $dpStatus = $r->amount_paid >= ($r->total_amount * 0.5) ? 'Paid (50% deposit received)' : 'Pending deposit';

                    $context .= "- Reservation #{$r->id} for {$r->booker_name}:\n"
                        . "  * Status: {$r->status} ({$r->reservation_type})\n"
                        . "  * Dates & Slot: {$r->reservation_date} [{$r->start_slot}] to " . ($r->end_date ?: $r->reservation_date) . " [" . ($r->end_slot ?: $r->start_slot) . "]\n"
                        . "  * Number of Guests: {$r->number_of_guests}\n"
                        . "  * Amenities: " . ($ams ?: 'None recorded') . "\n"
                        . "  * Registered Companions: " . ($guestList ?: 'None recorded') . "\n"
                        . "  * Financials: Total: ₱" . number_format($r->total_amount, 2) . ", Paid: ₱" . number_format($r->amount_paid, 2) . ", Balance Due: ₱" . number_format($r->remaining_balance, 2) . " [{$r->payment_status}, Deposit: {$dpStatus}]\n";
                }
            }
        }

        // Connected Guides: Online Booking and How to Go
        if (!empty($topics['booking_guide']) || empty($topics['is_specific'])) {
            $context .= "\n[10. HOW TO BOOK A RESERVATION ONLINE (PROCEDURE & 50% NON-REFUNDABLE POLICY)]:\n"
                . "1. Click the 'Book Now' button on the website navigation bar.\n"
                . "2. Step 1: Either pick your visit date on the interactive calendar, or pick your desired amenity (Cottage 1-6, Payag 1-6, A-House 1-8, or Function Hall).\n"
                . "3. Step 2: Choose your session slot: Daytime (8:00 AM – 5:00 PM), Nighttime (6:00 PM – 8:00 AM next morning), or multi-day Continuous stay.\n"
                . "4. Step 3: Enter the primary guest information (Full name, phone, email) and companion headcount with optional swimming pool access.\n"
                . "5. Step 4: Pay the required 50% DOWNPAYMENT online to lock in and confirm your reservation.\n"
                . "CRITICAL POLICY REMINDER: The 50% downpayment is STRICTLY NON-REFUNDABLE (no refund policy), but rescheduling of dates can be requested if given prior notice.\n";
        }

        if (!empty($topics['directions']) || empty($topics['is_specific'])) {
            $context .= "\n[11. HOW TO GO / DIRECTIONS TO HINAGUAN NATURE PARK (LANDMARK & NAVIGATION)]:\n"
                . "- Location: Zone 2, Barangay Solana, Jasaan, Misamis Oriental.\n"
                . "- Landmark on the Highway: Drop off at 'Spring View Resort' (a popular swimming pool resort along the highway in Solana, Jasaan, right past the Solana Bridge).\n"
                . "- Inner Road Route: From the national highway right at the Spring View Resort turn-off, enter and take the inner road heading inland toward the river and mountains.\n"
                . "- Commute: From Cagayan de Oro City (Agora Bus Terminal) or neighboring towns, board any bus (Rural Transit), van, or jeep heading to Jasaan or Balingasag. Tell the driver or conductor to drop you off in Solana, Jasaan at the Spring View Resort corner.\n"
                . "- Inner Road Transport: At the Spring View junction, take a local 'habal-habal' (motorcycle taxi) directly to Hinaguan Nature Park, or drive through the inner road with your private vehicle.\n"
                . "- Google Maps & Waze: Searchable and navigable directly on Google Maps or Waze by typing 'Hinaguan Nature Park' or 'Spring View Resort'.\n";
        }

        return $context;
    }

    /**
     * Handle amenity availability & available dates inquiries in direct fallback mode.
     */
    private function handleAmenityAvailabilityInquiry(string $userMessage, bool $isBisaya = false, bool $isTagalog = false): ?string
    {
        $msgLower = strtolower($userMessage);

        $hasDatePattern = (bool) preg_match('/(?:today|tomorrow|karon|ngayon|ugma|bukas|tonight|weekend|sabado|linggo|domingo|monday|tuesday|wednesday|thursday|friday|saturday|sunday|\b(?:jan|feb|mar|apr|may|jun|jul|aug|sep|sept|oct|nov|dec)[a-z]*\s+\d{1,2}|\d{4}-\d{1,2}-\d{1,2}|\d{1,2}\/\d{1,2}(?:\/\d{2,4})?)/i', $msgLower);

        $isAvailabilityInquiry = (bool) (
            preg_match('/(?:availab|when|date|petsa|kanus-?a|kailan|bakante|vacant|free|open|occup|schedule|what\s*day|unsa\s*nga\s*adlaw|anong\s*araw)/i', $msgLower)
            || preg_match('/(?:can\s+(?:i|we)|pwede\s+(?:ba)?|possible\s+to|may\s+pagka-?taon|posible)\s+(?:to\s+)?(?:book|reserve|get|take|stay|avail)/i', $msgLower)
            || ($hasDatePattern && preg_match('/(?:book|reserve|stay|visit|rent)/i', $msgLower))
        );
        $mentionsAmenity = (bool) preg_match('/(?:payag|cottage|a-?house|ahouse|function\s*hall|cabin|room|kubo)/i', $msgLower);

        if (!$isAvailabilityInquiry || !$mentionsAmenity) {
            return null;
        }

        $allAmenities = Amenity::where('status', true)->get();
        if ($allAmenities->isEmpty()) {
            return null;
        }

        $todayStr = now()->toDateString();

        // 1. Check for specific amenity mention with number or exact name
        // e.g. "Payag 4", "Cottage 2", "A-House 1", "Function Hall"
        $targetAmenity = null;

        // Try exact match against amenity names
        foreach ($allAmenities as $am) {
            $amLower = strtolower($am->amenities_name);
            $amNorm = str_replace(['-', ' '], '', $amLower);
            $msgNorm = str_replace(['-', ' '], '', $msgLower);

            if (str_contains($msgNorm, $amNorm)) {
                $targetAmenity = $am;
                break;
            }
        }

        // If specific amenity found
        if ($targetAmenity) {
            $reservations = ReservationAmenity::with('reservation')
                ->where('amenity_id', $targetAmenity->id)
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'Completed');
                })
                ->whereHas('reservation', function ($rq) {
                    $rq->whereNotIn('status', ['Cancelled', 'Checked Out', 'cancelled', 'checked out', 'checked_out', 'checked-out']);
                })
                ->where(function ($q) use ($todayStr) {
                    $q->where('start_date', '>=', $todayStr)
                      ->orWhere('end_date', '>=', $todayStr)
                      ->orWhereHas('reservation', function ($rq) use ($todayStr) {
                          $rq->where('reservation_date', '>=', $todayStr)->orWhere('end_date', '>=', $todayStr);
                      });
                })
                ->orderBy('start_date', 'asc')
                ->get();

            // Check if user asked about a specific date (e.g. "today", "tomorrow", "nov 25", or specific date)
            $parsedDate = null;
            if (preg_match('/\b(?:today|karon|ngayon)\b/i', $msgLower)) {
                $parsedDate = now()->toDateString();
            } elseif (preg_match('/\b(?:tomorrow|ugma|bukas)\b/i', $msgLower)) {
                $parsedDate = now()->addDay()->toDateString();
            } elseif (preg_match('/\b(?:tonight|karong\s+gabii|ngayong\s+gabi)\b/i', $msgLower)) {
                $parsedDate = now()->toDateString();
            } elseif (preg_match('/\b(?:this|next)?\s*(?:monday|tuesday|wednesday|thursday|friday|saturday|sunday|sabado|linggo|domingo)\b/i', $msgLower, $dm)) {
                try {
                    $dayMap = ['sabado' => 'saturday', 'linggo' => 'sunday', 'domingo' => 'sunday'];
                    $rawDay = strtolower(trim($dm[0]));
                    foreach ($dayMap as $fil => $eng) {
                        $rawDay = str_replace($fil, $eng, $rawDay);
                    }
                    $parsedDate = Carbon::parse($rawDay)->toDateString();
                } catch (\Exception $e) {}
            } elseif (preg_match('/(?:on\s+|sa\s+)?(\d{4}-\d{1,2}-\d{1,2})/i', $msgLower, $dm)) {
                try {
                    $parsedDate = Carbon::parse($dm[1])->toDateString();
                } catch (\Exception $e) {}
            } elseif (preg_match('/(?:on\s+|sa\s+)?((?:january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|jun|jul|aug|sep|sept|oct|nov|dec)[a-z]*\s+\d{1,2}(?:st|nd|rd|th)?(?:\s*,\s*\d{4})?)/i', $msgLower, $dm)) {
                try {
                    $parsedDate = Carbon::parse($dm[1])->toDateString();
                } catch (\Exception $e) {}
            } elseif (preg_match('/(?:on\s+|sa\s+)?(\d{1,2}(?:st|nd|rd|th)?\s+(?:of\s+)?(?:january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|jun|jul|aug|sep|sept|oct|nov|dec)[a-z]*(?:\s*,\s*\d{4})?)/i', $msgLower, $dm)) {
                try {
                    $clean = preg_replace('/(\d+)(?:st|nd|rd|th)\s+(?:of\s+)?/i', '$1 ', $dm[1]);
                    $parsedDate = Carbon::parse($clean)->toDateString();
                } catch (\Exception $e) {}
            } elseif (preg_match('/(?:on\s+|sa\s+)?(\d{1,2}\/\d{1,2}(?:\/\d{2,4})?)/i', $msgLower, $dm)) {
                try {
                    $parsedDate = Carbon::parse($dm[1])->toDateString();
                } catch (\Exception $e) {}
            }

            $priceStr = "₱" . number_format((float) $targetAmenity->daytime_price, 2);

            // If a specific target date was queried by user
            if ($parsedDate) {
                $isReservedOnDate = $reservations->contains(function ($ra) use ($parsedDate, $todayStr) {
                    $res = $ra->reservation;
                    $s = $ra->start_date ? $ra->start_date->toDateString() : ($res?->reservation_date ? $res->reservation_date->toDateString() : $todayStr);
                    $e = $ra->end_date ? $ra->end_date->toDateString() : ($res?->end_date ? $res->end_date->toDateString() : $s);
                    return $parsedDate >= $s && $parsedDate <= $e;
                });

                $formattedDate = Carbon::parse($parsedDate)->format('F j, Y');

                if ($isReservedOnDate) {
                    if ($isBisaya) {
                        return "Ang {$targetAmenity->amenities_name} kay nareserba na sa maong petsa ({$formattedDate}).\n\nApan abli ug bakante kini sa ubang mga petsa, o pwede ka mopili sa laing parehas nga amenity! I-click ang **Book via Chat** o **Book Now** aron makita ang tanang bakante.";
                    } elseif ($isTagalog) {
                        return "Ang {$targetAmenity->amenities_name} po ay may nakareserba na sa petsang iyon ({$formattedDate}).\n\nNgunit available po ito sa ibang darating na mga petsa, o maaari kayong pumili ng ibang bakanteng amenity! I-click lamang po ang **Book via Chat** o **Book Now** para makita ang mga bukas.";
                    } else {
                        return "{$targetAmenity->amenities_name} is already reserved on {$formattedDate}.\n\nHowever, it is open and available on other dates, or you can choose an alternative open amenity! Feel free to click **Book via Chat** or the **Book Now** button to check all open dates.";
                    }
                } else {
                    $inclNote = stripos($targetAmenity->amenities_name, 'a-house') !== false
                        ? " (includes free entrance & swimming pool access for 2 guests)"
                        : " (regular entrance and pool access apply separately)";

                    if ($isBisaya) {
                        return "Oo, available kaayo ang {$targetAmenity->amenities_name} ({$priceStr}) sa {$formattedDate}! 🌿{$inclNote}\n\nSigurado ba ka nga gusto nimo i-reserve ang {$targetAmenity->amenities_name}, o gusto ba nimo nga ipakita nako ang tanang available nga amenities nianang petsaha?";
                    } elseif ($isTagalog) {
                        return "Opo, available na available po ang {$targetAmenity->amenities_name} ({$priceStr}) sa {$formattedDate}! 🌿{$inclNote}\n\nSigurado po ba kayong nais ninyong i-reserve ang {$targetAmenity->amenities_name}, o nais niyo po bang ipakita ko ang lahat ng available amenities sa petsang iyan?";
                    } else {
                        return "Yes! {$targetAmenity->amenities_name} ({$priceStr}) is available on {$formattedDate}! 🌿{$inclNote}\n\nAre you sure you would like to reserve {$targetAmenity->amenities_name}, or would you like me to show you all the available amenities for that date?";
                    }
                }
            }

            // If no specific date was asked (e.g. "what date is the payag 4 available?")
            if ($reservations->isNotEmpty()) {
                $reservedRanges = [];
                foreach ($reservations as $ra) {
                    $res = $ra->reservation;
                    $s = $ra->start_date ? $ra->start_date->toDateString() : ($res?->reservation_date ? $res->reservation_date->toDateString() : $todayStr);
                    $e = $ra->end_date ? $ra->end_date->toDateString() : ($res?->end_date ? $res->end_date->toDateString() : $s);
                    $slot = $ra->start_slot ?: ($res?->start_slot ?? 'Daytime');

                    $sFmt = Carbon::parse($s)->format('M j, Y');
                    $eFmt = Carbon::parse($e)->format('M j, Y');
                    $rangeStr = ($s === $e) ? "{$sFmt} [{$slot}]" : "{$sFmt} to {$eFmt} [{$slot}]";
                    $reservedRanges[] = "• {$rangeStr}";
                }

                $bookedList = implode("\n", $reservedRanges);

                if ($isBisaya) {
                    return "Ang {$targetAmenity->amenities_name} ({$priceStr}) kay abli ug available sa kadaghanang petsa!\n\nNareserba lang kini sa mosunod nga petsa:\n{$bookedList}\n\nAng tanang ubang petsa (apil ang karon ug ubang adlaw) kay libre ug bakante pa kaayo! Pwede nimo i-click ang **Book via Chat** o ang **Book Now** button sa website aron makapili ka sa imong target date.";
                } elseif ($isTagalog) {
                    return "Ang {$targetAmenity->amenities_name} ({$priceStr}) po ay available at bukas sa halos lahat ng darating na petsa!\n\nNareserba lamang po ito sa mga sumusunod na petsa:\n{$bookedList}\n\nLahat po ng iba pang petsa (kasama ang ngayon, bukas, at iba pang araw) ay bakante at puwedeng-puwede ninyong i-book! I-click lamang po ang **Book via Chat** o ang **Book Now** button sa website upang piliin ang inyong target date.";
                } else {
                    return "{$targetAmenity->amenities_name} ({$priceStr}) is open and available for booking on almost all dates!\n\nIt is currently reserved only on the following date(s):\n{$bookedList}\n\nAll other upcoming dates (including today, tomorrow, and surrounding days) are wide open! You can click **Book via Chat** or use the **Book Now** button on our website to pick your date and secure your reservation.";
                }
            } else {
                if ($isBisaya) {
                    return "Nindot nga balita! Ang {$targetAmenity->amenities_name} ({$priceStr}) kay 100% available ug walay nakareserba sa tanang umaabot nga petsa.\n\nMahimo kang mopili sa bisan unsang petsa pinaagi sa pag-click sa **Book via Chat** o sa **Book Now** button!";
                } elseif ($isTagalog) {
                    return "Magandang balita po! Ang {$targetAmenity->amenities_name} ({$priceStr}) ay 100% available at walang nakareserba sa darating na mga petsa.\n\nMaaari kayong pumili ng kahit anong petsa sa pamamagitan ng pag-click sa **Book via Chat** o sa **Book Now** button!";
                } else {
                    return "Good news! {$targetAmenity->amenities_name} ({$priceStr}) currently has NO upcoming reservations and is 100% available on all upcoming dates!\n\nYou can click **Book via Chat** or use the **Book Now** button on our website to pick your preferred date and reserve it.";
                }
            }
        }

        // 2. If general category inquiry (e.g. "what dates are payags available?", "are cottages available?")
        $category = null;
        if (preg_match('/payag|hut|kubo/i', $msgLower)) {
            $category = 'Payag';
        } elseif (preg_match('/cottage|shed/i', $msgLower)) {
            $category = 'Cottage';
        } elseif (preg_match('/a-?house|ahouse|cabin|room/i', $msgLower)) {
            $category = 'A-House';
        } elseif (preg_match('/function\s*hall|hall|event/i', $msgLower)) {
            $category = 'Function Hall';
        }

        if ($category) {
            $catAmenities = $allAmenities->filter(fn ($a) => stripos($a->amenities_name, $category) !== false);

            if ($catAmenities->isNotEmpty()) {
                $statusLines = [];
                foreach ($catAmenities as $am) {
                    $hasRes = ReservationAmenity::with('reservation')
                        ->where('amenity_id', $am->id)
                        ->where(function ($q) {
                            $q->whereNull('status')->orWhere('status', '!=', 'Completed');
                        })
                        ->whereHas('reservation', function ($rq) {
                            $rq->whereNotIn('status', ['Cancelled', 'Checked Out', 'cancelled', 'checked out', 'checked_out', 'checked-out']);
                        })
                        ->where(function ($q) use ($todayStr) {
                            $q->where('start_date', '>=', $todayStr)
                              ->orWhere('end_date', '>=', $todayStr)
                              ->orWhereHas('reservation', function ($rq) use ($todayStr) {
                                  $rq->where('reservation_date', '>=', $todayStr)->orWhere('end_date', '>=', $todayStr);
                              });
                        })
                        ->first();

                    if ($hasRes) {
                        $res = $hasRes->reservation;
                        $s = $hasRes->start_date ? $hasRes->start_date->toDateString() : ($res?->reservation_date ? $res->reservation_date->toDateString() : $todayStr);
                        $e = $hasRes->end_date ? $hasRes->end_date->toDateString() : ($res?->end_date ? $res->end_date->toDateString() : $s);
                        $sFmt = Carbon::parse($s)->format('M j');
                        $eFmt = Carbon::parse($e)->format('M j');
                        $dateTxt = ($s === $e) ? $sFmt : "{$sFmt} - {$eFmt}";
                        $statusLines[] = "• **{$am->amenities_name}**: Reserved on {$dateTxt} (available on other dates)";
                    } else {
                        $statusLines[] = "• **{$am->amenities_name}**: Available on all upcoming dates";
                    }
                }

                $listTxt = implode("\n", $statusLines);

                if ($isBisaya) {
                    return "Mao kini ang availability sa among mga {$category}s:\n{$listTxt}\n\nPwede ka mag-book sa imong gustong petsa pinaagi sa pag-click sa **Book via Chat** o **Book Now** button!";
                } elseif ($isTagalog) {
                    return "Narito po ang availability ng aming mga {$category}:\n{$listTxt}\n\nMaaari po kayong mag-book sa inyong napiling petsa sa pamamagitan ng pag-click sa **Book via Chat** o **Book Now** button!";
                } else {
                    return "Here is the current availability for our {$category}s:\n{$listTxt}\n\nYou can reserve your preferred dates by clicking **Book via Chat** or using the **Book Now** button on our website!";
                }
            }
        }

        return null;
    }
}
