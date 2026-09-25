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

class GuestChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array',
            'history.*.role' => 'required_with:history|string|in:user,assistant',
            'history.*.content' => 'required_with:history|string',
            'model' => 'nullable|string',
        ]);

        $userMessage = trim($request->input('message'));
        $msgLower = strtolower($userMessage);

        // Security / Privacy Guardrail: Strictly block confidential sales, company financials, staff accounts
        $sensitiveTerms = [
            'revenue', 'financial', 'sales', 'profit', 'total earned', 'total income',
            'staff account', 'staff password', 'admin password', 'admin account', 'staff list', 'employee list',
            'developer', 'coding', 'programming', 'politics', 'religion'
        ];

        foreach ($sensitiveTerms as $term) {
            if (str_contains($msgLower, $term)) {
                return response()->json([
                    'reply' => "I am the Hinaguan Guest Assistant. For privacy and security reasons, I can only assist with park amenities, rates, availability, and booking procedures. For special inquiries, please contact our park front desk directly."
                ]);
            }
        }

        $apiKey = env('OPENROUTER_API_KEY');
        $model = $request->input('model', 'openrouter/free');

        if (!$apiKey) {
            return response()->json([
                'reply' => 'The chatbot is currently offline. Please call park hotline 0917 861 8383.'
            ], 500);
        }

        $guestContext = $this->getGuestContext($userMessage);

        $systemPrompt = "You are HinaguanBot, the warm, friendly, intelligent, and helpful AI concierge for Hinaguan Nature Park in Jasaan, Misamis Oriental.\n\n"
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
            . "2. GO STRAIGHT TO THE POINT:\n"
            . "   - Answer directly in 1 to 3 friendly, helpful, and concise human sentences (or clear bullet points if step-by-step directions or booking instructions are requested).\n"
            . "   - NEVER output internal reasoning, outlines, numbered analysis, scratchpads, or draft prefixes.\n"
            . "3. MATCH THE USER'S LANGUAGE & FORGIVE TYPOS/GRAMMAR:\n"
            . "   - Seamlessly understand misspelled words, typographical errors, phonetic spelling, and broken grammar (e.g. 'cotag', 'pyag', 'boking', 'chek in', 'magkano po ahouse', 'pila bayranan', 'resrvation', 'unsaon pag adto', 'paano pumunta'). Deduce intent directly without correcting the user.\n"
            . "   - If the user asks in Bisaya / Cebuano (e.g., 'tagpila', 'pila', 'asa dapit', 'naay pool', 'nindot', 'suba', 'init', 'uwan', 'pila bayad', 'unsaon pag book', 'unsaon pag adto'), reply warmly and naturally in Bisaya!\n"
            . "   - If the user asks in Tagalog / Filipino / Taglish (e.g., 'magkano', 'meron ba', 'saan', 'ano po rates', 'pwede ba', 'paano mag book', 'paano pumunta'), reply warmly and naturally in Tagalog!\n"
            . "   - If the user asks in English, reply in English!\n"
            . "4. HOW TO BOOK A RESERVATION ONLINE (CRITICAL PROCEDURE):\n"
            . "   - Explain the simple steps to reserve online:\n"
            . "     1. Click the 'Book Now' button on the website.\n"
            . "     2. First, either pick your target date or pick your preferred amenity (Cottage, Payag, A-House, Function Hall).\n"
            . "     3. Choose your session slot (Daytime: 8:00 AM – 5:00 PM, Nighttime: 6:00 PM – 8:00 AM next day, or multi-day Continuous Stay).\n"
            . "     4. Fill in primary guest details and companion headcount (with pool access options).\n"
            . "     5. Pay the required 50% DOWNPAYMENT online to secure and confirm the reservation.\n"
            . "   - MANDATORY POLICY REMINDER: Explicitly mention that the 50% downpayment is STRICTLY NON-REFUNDABLE (no refund policy), though date rescheduling can be requested.\n"
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
            . "   - A-HOUSES (₱300 day / ₱500 night): Includes FREE entrance and FREE swimming pool access for 2 guests.\n"
            . "   - FUNCTION HALL: Includes FREE entrance and FREE swimming pool access for the booked group (15 to 50+ pax).\n"
            . "7. TODAY'S WEATHER-AWARE RECOMMENDATIONS:\n"
            . "   - Check [TODAY'S WEATHER LOG (FROM DATABASE)].\n"
            . "   - If rainy, overcast, or high chance of rain: Recommend weather-sheltered spots (like cozy A-Houses, Function Hall, or covered cottages/payags) and mention staying dry.\n"
            . "   - If sunny, warm, or clear: Recommend riverside payags (Payag 2 or 6), breezy mountain payag (Payag 3), shaded garden payag (Payag 5), or taking a refreshing swim in the pool!\n"
            . "8. GENERAL PARK POLICIES:\n"
            . "   - Outside food allowed with NO corkage fee; free grilling stations available.\n"
            . "   - Pets allowed on leash; free parking on site.\n\n"
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

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => request()->getHttpHost(),
                'X-Title' => 'Hinaguan Nature Park Guest Portal',
            ])->post("https://openrouter.ai/api/v1/chat/completions", [
                'model' => $model,
                'messages' => $messagesPayload,
                'max_tokens' => 1000,
                'temperature' => 0.3,
                'include_reasoning' => false,
                'reasoning' => [
                    'effort' => 'none',
                    'exclude' => true,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $rawReply = $data['choices'][0]['message']['content'] ?? '';
                $reply = $this->cleanChatbotReply($rawReply, $userMessage);

                return response()->json(['reply' => $reply]);
            } else {
                Log::error('Guest Chatbot OpenRouter Error: ' . $response->body());
                $fallback = $this->generateDirectFallbackResponse($userMessage);
                return response()->json([
                    'reply' => !empty($fallback) ? $fallback : 'The assistant is temporarily unavailable. Please call 0917 861 8383.'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Guest Chatbot Exception: ' . $e->getMessage());
            $fallback = $this->generateDirectFallbackResponse($userMessage);
            return response()->json([
                'reply' => !empty($fallback) ? $fallback : 'The guest concierge is temporarily unavailable. Please call 0917 861 8383.'
            ]);
        }
    }

    /**
     * Clean raw AI response to strip thinking processes, reasoning tags, numbered scratchpad steps, and draft labels.
     */
    private function cleanChatbotReply(string $reply, string $userMessage = ''): string
    {
        if (empty(trim($reply))) {
            return !empty($userMessage) ? $this->generateDirectFallbackResponse($userMessage) : '';
        }

        $text = trim($reply);

        // 1. Strip XML-like thinking/reasoning tags (<think>...</think>, <thought>...</thought>, etc.)
        $text = preg_replace('/<(?:think|thought|reasoning|scratchpad|analysis|internal)>.*?<\/(?:think|thought|reasoning|scratchpad|analysis|internal)>/is', '', $text);
        $text = preg_replace('/<(?:think|thought|reasoning|scratchpad|analysis|internal)>.*$/is', '', $text);

        // 2. If the model outputs a draft or final answer section at the end (e.g., "Draft:\n"...", "Final Response:", "Response:"), extract only that answer!
        if (preg_match('/(?:^|\n)\s*(?:Draft|Final\s+Response|Final\s+Answer|Actual\s+Response|Clean\s+Response|Response|Output|Assistant\s+Reply):\s*(.+)$/is', $text, $matches)) {
            $extracted = trim($matches[1]);
            if (!empty($extracted)) {
                $text = $extracted;
            }
        }

        // 3. If there is a "3. Formulate Response:" or "3. Response:" step with the final answer
        if (preg_match('/(?:^|\n)\s*\d+\.\s*(?:Formulate|Draft|Response|Output|Answer|Final\s+Step).*?:\s*\n*(.+)$/is', $text, $matches)) {
            $extracted = trim($matches[1]);
            if (!empty($extracted)) {
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
            if (preg_match('/^(?:[-*•]\s*(?:User\s+(?:said|is\s+asking|wants)|Context:|Internal\s+note|Chain\s+of\s+thought|Thinking\s+process|Scratchpad))/i', $trimmedP)) {
                continue;
            }
            $filtered[] = $p;
        }

        if (!empty($filtered)) {
            $text = implode("\n\n", $filtered);
        } else {
            // ALL paragraphs were analytical scratchpad steps! Check if there is an embedded recommendation
            if (preg_match('/(?:Therefore|In summary|Overall|Recommendation|I recommend|We recommend)\s*[:,\-]?\s*(.+)$/is', $text, $match)) {
                $text = trim($match[1]);
            } else {
                return !empty($userMessage) ? $this->generateDirectFallbackResponse($userMessage) : 'Welcome to Hinaguan Nature Park! How can I assist you with your booking today?';
            }
        }

        // 6. Strip any leftover "Draft:", "Response:", "Answer:" labels at start
        $text = preg_replace('/^(?:Draft|Final\s+Response|Final\s+Answer|Response|Output|Answer|Reply):\s*/i', '', trim($text));

        // 7. Strip leading bot/role prefixes like "HinaguanBot:", "StaffBot:", "AdminBot:", "Assistant:"
        $text = preg_replace('/^(?:HinaguanBot|StaffBot|AdminBot|GuestBot|Bot|Assistant|AI):\s*/i', '', trim($text));

        // 8. If the text still starts with numbered analysis like "1. Analyze User Input:", reject and generate direct answer
        if (preg_match('/^\s*\d+\.\s*(?:Analyze|Analysis|Check|Determine|Plan)/i', $text)) {
            return !empty($userMessage) ? $this->generateDirectFallbackResponse($userMessage) : 'Welcome to Hinaguan Nature Park! How can I assist you with your booking today?';
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

        return trim($text);
    }

    /**
     * Context-aware direct fallback response in case a free LLM emits only analytical scratchpad tokens.
     */
    private function generateDirectFallbackResponse(string $userMessage): string
    {
        $msgLower = strtolower($userMessage);

        // Detect language
        $isBisaya = (bool) preg_match('/\b(?:pila|tagpila|asa|nindot|suba|puy-anan|unsa|init|uwan|tugnaw|daghan|gamay|man|kaayo|gani|diay|kinsa|kanus-a|kabuok|ka\s+tao|unsaon|adto|anhi|sakay)\b/i', $userMessage);
        $isTagalog = (bool) preg_match('/\b(?:magkano|meron|saan|ano|ba|po|ulan|mainit|pwede|kami|tayo|sino|kailan|tao|bawat|paano|pumunta|sakyan)\b/i', $userMessage);

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

        // 2. How to Book Online Inquiry (with 50% non-refundable downpayment rule)
        $isBookingInquiry = (bool) preg_match('/(?:how\s+to\s+book|paano\s+(?:mag-?)?book|unsaon\s+pag-?book|unsaon\s+pag-?reserve|paano\s+mag-?reserve|steps?\s+to\s+book|online\s+booking|mag-?book|pag-?book|downpayment|down\s*payment|no\s*refund|non-?refundable|refund)/i', $msgLower);
        if ($isBookingInquiry) {
            if ($isBisaya) {
                return "Aron mag-book online sa Hinaguan Nature Park:\n"
                    . "1. I-click ang 'Book Now' button sa among website.\n"
                    . "2. Pagpili una ug petsa (target date) o pilia ang imong gustong amenity (Cottage, Payag, A-House, o Function Hall).\n"
                    . "3. Pilia ang session slot (Daytime: 8:00 AM – 5:00 PM, Nighttime: 6:00 PM – 8:00 AM, o Continuous Stay).\n"
                    . "4. Isulod ang mga detalye sa primary guest ug gidaghanon sa mga kauban.\n"
                    . "5. Bayri ang 50% DOWNPAYMENT online aron ma-secure ug makumpirma ang imong reservation.\n\n"
                    . "PAHINUMDOM: Ang 50% downpayment kay STRICTLY NON-REFUNDABLE (walay refund), apan pwede mohangyo ug reschedule sa petsa kung naay pahibalo.";
            } elseif ($isTagalog) {
                return "Para mag-book online sa Hinaguan Nature Park:\n"
                    . "1. I-click ang 'Book Now' button sa aming website.\n"
                    . "2. Pumili muna ng inyong target date o piliin ang inyong gustong amenity (Cottage, Payag, A-House, o Function Hall).\n"
                    . "3. Piliin ang session slot (Daytime: 8:00 AM – 5:00 PM, Nighttime: 6:00 PM – 8:00 AM kinabukasan, o Continuous Stay).\n"
                    . "4. Ilagay ang detalye ng primary guest at bilang ng mga kasama.\n"
                    . "5. Magbayad ng 50% DOWNPAYMENT online upang ma-secure at makumpirma ang inyong reservation.\n\n"
                    . "MAHALAGANG PAALALA: Ang 50% downpayment po ay STRICTLY NON-REFUNDABLE (walang refund), ngunit maaari kayong mag-request ng rescheduling ng petsa.";
            } else {
                return "To book a reservation online at Hinaguan Nature Park:\n"
                    . "1. Click the 'Book Now' button on our website.\n"
                    . "2. First, either select your preferred visit date or pick your desired amenity (Cottage, Payag, A-House, or Function Hall).\n"
                    . "3. Choose your session slot (Daytime: 8:00 AM – 5:00 PM, Nighttime: 6:00 PM – 8:00 AM next morning, or multi-day Continuous Stay).\n"
                    . "4. Fill in your primary guest details and companion headcount.\n"
                    . "5. Pay the required 50% DOWNPAYMENT online to lock in and confirm your reservation.\n\n"
                    . "IMPORTANT POLICY: Please note that the 50% downpayment is STRICTLY NON-REFUNDABLE (no refund policy), though date rescheduling can be requested.";
            }
        }

        // 3. How to Go / Directions Inquiry (Solana, Jasaan, Spring View Resort landmark, inner road, Google Maps)
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

        // 4. Park Activities Inquiry (from park_activities table)
        $isActivitiesInquiry = (bool) preg_match('/(?:activit|unsa(?:ng)?\s*(?:mabuhat|buhaton)|ano(?:ng)?\s*(?:pwedeng\s*)?gawin|things\s+to\s+do|river\s*swim|pictorial|picture|swimming)/i', $msgLower);
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

        // 5. Reservation Status Lookup (if user provided a reservation number)
        if (preg_match('/(?:res(?:ervation)?|booking|boking)\s*(?:#|no\.?|num(?:ber)?)?\s*(\d+)/i', $msgLower, $m)) {
            $resId = (int) $m[1];
            $res = Reservation::with(['reservationAmenities.amenity'])->find($resId);
            if ($res) {
                $ams = $res->reservationAmenities->map(fn ($ra) => $ra->amenity?->amenities_name ?? 'Amenity')->implode(', ');
                $stay = "{$res->reservation_date} [{$res->start_slot}]";
                return "Reservation #{$res->id} for {$res->booker_name} is currently [{$res->status}]. Stay date: {$stay}. Booked amenities: " . ($ams ?: 'None') . ". Remaining balance: ₱" . number_format($res->remaining_balance, 2) . " ({$res->payment_status}).";
            }
        }

        // Fetch live weather from database (daily_weather_shift_logs)
        $latestWeather = DailyWeatherShiftLog::orderBy('log_date', 'desc')->first();
        $weatherCond = $latestWeather ? $latestWeather->weather_condition : 'Sunny';
        $weatherTemp = $latestWeather ? $latestWeather->temperature_celsius : '30';
        $weatherRain = $latestWeather ? (int) $latestWeather->precipitation_probability : 10;
        $isRainy = stripos($weatherCond, 'rain') !== false || $weatherRain >= 50;

        // 6. Weather inquiry
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

        // 7. Location / Vibe inquiry (Riverside, Mountain breeze, Garden shade, Scenic view)
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

        // 8. Group recommendation (pax / number of people)
        if (preg_match('/(\d+)\s*(?:people|persons|pax|guests|heads|kabuok|ka\s+tao|tao)/i', $userMessage, $m) ||
            preg_match('/for\s+(\d+)/i', $userMessage, $m)) {
            $pax = (int) $m[1];

            if ($pax >= 1 && $pax <= 2) {
                if ($isBisaya) {
                    return "Para sa 1 hangtod 2 ka tawo, ang among A-Houses (₱300 daytime / ₱500 nighttime) ang bagay kaayo kay apil na ang libreng entrance ug pool access!";
                } elseif ($isTagalog) {
                    return "Para po sa 1 hanggang 2 tao, swak ang aming A-Houses (₱300 daytime / ₱500 nighttime) dahil may kasama na itong libreng entrance at pool access!";
                } else {
                    return "For 1 to 2 guests, our cozy A-Houses (₱300 daytime / ₱500 nighttime) are ideal and include free entrance and pool access, or you can relax in an open-air Cottage (₱200) or Payag (₱300).";
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

        // 9. Specific amenity inquiry (Cottage / Payag / A-House / Function Hall)
        if (str_contains($msgLower, 'cottage') || str_contains($msgLower, 'kubo') || str_contains($msgLower, 'shed')) {
            if ($isBisaya) {
                return "Ang among mga Cottage (Cottage 1 to 6) kay ₱200 para sa daytime o nighttime, naay lamesa ug lingkoranan para sa pamilya hangtod 10 ka tawo (lahi ang entrance nga ₱20 ug pool nga ₱50).";
            } else {
                return "Our open-air Cottages (Cottage 1 to 6) are ₱200 for daytime or nighttime use, featuring dining tables and chairs for up to 10 guests. Regular entrance (₱20/adult) and pool access (₱50/person) apply separately.";
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

        // 10. Entrance fees / swimming pool inquiry
        if (str_contains($msgLower, 'entrance') || str_contains($msgLower, 'fee') || str_contains($msgLower, 'rate') || str_contains($msgLower, 'price') || str_contains($msgLower, 'pool')) {
            $settings = ParkSetting::first();
            $dayAdult = $settings ? number_format((float)($settings->daytime_adult_entrance_fee ?? 20)) : '20';
            $dayPool = $settings ? number_format((float)($settings->day_pool_fee ?? 50)) : '50';
            if ($isBisaya) {
                return "Ang daytime entrance fee kay ₱{$dayAdult} sa hamtong (libre ang 12 anyos paubos), ug ang pool access kay ₱{$dayPool} matag tawo.";
            } elseif ($isTagalog) {
                return "Ang daytime entrance fee po ay ₱{$dayAdult} bawat adult (libre ang 12 pababa), at ₱{$dayPool} naman po bawat tao para sa swimming pool.";
            } else {
                return "Our daytime entrance fee is ₱{$dayAdult} per adult (free for children 12 and below), with daytime pool access at ₱{$dayPool} per person. Feel free to visit our Rates page for full details!";
            }
        }

        // 11. Operating hours / schedule inquiry
        if (str_contains($msgLower, 'hour') || str_contains($msgLower, 'time') || str_contains($msgLower, 'open') || str_contains($msgLower, 'schedule') || str_contains($msgLower, 'oras')) {
            $settings = ParkSetting::first();
            $openTime = $settings?->opening_time ? Carbon::parse($settings->opening_time)->format('g:i A') : '8:00 AM';
            $closeTime = $settings?->closing_time ? Carbon::parse($settings->closing_time)->format('g:i A') : '5:00 PM';
            if ($isBisaya) {
                return "Abli ang Hinaguan Nature Park gikan {$openTime} hangtod {$closeTime} adlaw-adlaw. Naa mi daytime ug overnight stays!";
            } elseif ($isTagalog) {
                return "Bukas po ang Hinaguan Nature Park mula {$openTime} hanggang {$closeTime} araw-araw. May daytime at overnight cottage stays po kami!";
            } else {
                return "Hinaguan Nature Park is open from {$openTime} to {$closeTime} daily. We offer both daytime and overnight cottage stays!";
            }
        }

        if ($isBisaya) {
            return "Maayong adlaw! Welcome sa Hinaguan Nature Park sa Jasaan, Misamis Oriental. Naa mi mga payag, cottages, swimming pool, ug A-house stays. Unsa may akong matabang kanimo karon?";
        } elseif ($isTagalog) {
            return "Magandang araw! Maligayang pagdating sa Hinaguan Nature Park sa Jasaan, Misamis Oriental. Mayroon kaming mga payag, cottage, swimming pool, at nature stays. Paano ko po kayo matutulungan ngayon?";
        } else {
            return "Hello! Welcome to Hinaguan Nature Park in Jasaan, Misamis Oriental. We offer cottages, pools, and nature stays. How can I assist you with your booking or park visit today?";
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

        // 5. Amenities & Rates (cottage, payag, a-house, function hall, pool, rates)
        if (preg_match('/(?:amenit|cottage|cotag|kotats|payag|pyag|a-?house|ahouse|function\s*hall|kubo|pool|swim|rate|price|tagpila|magkano|pila|entrance|bayad|presyo)/i', $msg)) {
            $topics['amenities'] = true;
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

        return $topics;
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
    private function getGuestContext(string $message): string
    {
        $context = '';
        $now = now();
        $todayStr = $now->toDateString();
        $currentTimeStr = $now->format('F j, Y - g:i A');

        $context .= "Current Date/Time: {$currentTimeStr}\n";

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

        // Table 5: reservation_amenities (Live occupancy check)
        $activeBooked = ReservationAmenity::with(['reservation', 'amenity'])
            ->whereIn('status', ['Checked In', 'Confirmed', 'active'])
            ->whereHas('reservation', fn ($q) => $q->whereIn('status', ['Checked In', 'Confirmed', 'active']))
            ->get();

        $context .= "\n[4. LIVE AVAILABILITY & OCCUPANCY (FROM DATABASE reservation_amenities)]:\n";
        if ($activeBooked->isNotEmpty()) {
            foreach ($activeBooked as $item) {
                $amName = $item->amenity?->amenities_name ?? 'Amenity';
                $res = $item->reservation;
                $slot = $item->end_slot ?: ($res->end_slot ?? $res->start_slot ?? 'Daytime');
                $endDate = $item->end_date ?: ($res->end_date ?? $res->reservation_date ?? $todayStr);
                $expectedCheckout = strcasecmp((string)$slot, 'Nighttime') === 0 ? "6:00 AM after {$endDate}" : "6:00 PM on {$endDate}";
                $context .= "- {$amName}: Occupied until {$endDate} [{$slot}] (Available after {$expectedCheckout})\n";
            }
        } else {
            $context .= "All amenities are currently available for booking today!\n";
        }

        // Group size guide
        $context .= "\n[GROUP SIZE RECOMMENDATION RULES (CRITICAL)]:\n"
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
}
