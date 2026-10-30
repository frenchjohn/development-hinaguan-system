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
            'booking_draft' => 'nullable|array',
        ]);

        $userMessage = trim($request->input('message'));
        $msgLower = strtolower($userMessage);
        $bookingDraft = $request->input('booking_draft', []);

        // Detect booking actions
        $bookingAction = null;
        if (preg_match('/(?:cancel\s+booking|reset\s+booking|stop\s+booking|exit\s+booking)/i', $msgLower)) {
            $bookingAction = 'cancel_booking';
            $bookingDraft = null;
        } elseif (preg_match('/(?:show\s+(?:me\s+)?(?:what\s+(?:amenit(?:y|ies)?\s+)?i\s+(?:just\s+)?avail|my\s+(?:pick|picks|selection|draft|booking)|the\s+record)|what\s+did\s+i\s+pick|unsa\s+akong\s+gipili|patingin\s+ng\s+pinili\s*ko|view\s+pick)/i', $msgLower)) {
            $bookingAction = 'show_picks';
        }

        // Only activate booking action on explicit command /book
        if (trim($msgLower) === '/book') {
            $bookingAction = 'confirm_booking_prompt';
        }

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

        $apiKey = config('services.openrouter.key') ?: env('OPENROUTER_API_KEY');
        $model = $request->input('model', 'openrouter/free');

        if (!$apiKey) {
            $fallback = $this->generateDirectFallbackResponse($userMessage, $bookingDraft);
            return response()->json([
                'reply' => !empty($fallback) ? $fallback : 'The chatbot is currently offline. Please call park hotline 0917 861 8383.',
                'booking_action' => $bookingAction,
                'booking_draft' => $bookingDraft,
            ]);
        }

        $guestContext = $this->getGuestContext($userMessage, $bookingDraft);

        $systemPrompt = "You are Bren (also known as Bren AI Assistant), the warm, friendly, intelligent, and helpful AI concierge for Hinaguan Nature Park in Jasaan, Misamis Oriental.\n\n"
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
            . "   - NEVER OUTPUT SAFETY EVALUATIONS OR MODERATION LABELS: Never output lines like 'User Safety:', 'Response Safety:', 'Safety: safe', or meta moderation checks. Answer the guest directly.\n"
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
            . "   - Pets allowed on leash; free parking on site.\n"
            . "9. AMENITY AVAILABILITY & DATES (CRITICAL):\n"
            . "   - When a guest asks about the availability of an amenity or what dates it is available (e.g., 'what date is payag 4 available?', 'is cottage 1 available?', 'when can I book payag 4?', 'available dates'):\n"
            . "   - Carefully review section '[4. LIVE AMENITY AVAILABILITY & UPCOMING RESERVED DATES (FROM DATABASE)]'.\n"
            . "   - If the amenity has upcoming reservations, explicitly state which dates and slots are reserved, and clarify that ALL OTHER upcoming dates are open and available!\n"
            . "   - If it has no upcoming reservations, confirm that it is 100% available for booking on any date.\n"
            . "   - Always encourage the guest to click 'Book via Chat' or use the 'Book Now' button to select their date and reserve it.\n"
            . "10. BOOK VIA CHAT INQUIRIES & SUGGESTIONS:\n"
            . "   - If the guest asks how to book via chat, or wants to start their booking, or asks if they can book here:\n"
            . "   - Warmly let them know they can book right in this chat by clicking the green 'Book via Chat' chip at the top of the chat (or typing /book). You can also answer their questions first so they can choose the best amenity and date before starting!\n\n"
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

        try {
            $requestPayload = [
                'messages' => $messagesPayload,
                'max_tokens' => 1200,
                'temperature' => 0.3,
            ];

            if ($model === 'openrouter/free' || empty($model)) {
                $requestPayload['models'] = [
                    'recursive/flint-v1.0:free',
                    'liquid/lfm-2.5-2.6b:free',
                    'openrouter/free',
                ];
            } else {
                $requestPayload['model'] = $model;
            }

            $response = Http::timeout(15)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => request()->getHttpHost(),
                'X-Title' => 'Hinaguan Nature Park Guest Portal',
            ])->post("https://openrouter.ai/api/v1/chat/completions", $requestPayload);

            if ($response->successful()) {
                $data = $response->json();
                $msgObj = $data['choices'][0]['message'] ?? [];
                $rawReply = trim($msgObj['content'] ?? '');

                if (empty($rawReply) && !empty($msgObj['reasoning'])) {
                    $rawReply = $this->cleanChatbotReply($msgObj['reasoning'], $userMessage);
                }

                $reply = $this->cleanChatbotReply($rawReply, $userMessage);

                return response()->json([
                    'reply' => $reply,
                    'booking_action' => $bookingAction,
                    'booking_draft' => $bookingDraft,
                ]);
            } else {
                Log::warning('Guest Chatbot OpenRouter Error (engaging intelligent fallback): ' . $response->body());

                // Optional fallback to Gemini if GEMINI_API_KEY is configured
                $geminiKey = env('GEMINI_API_KEY');
                if ($geminiKey) {
                    try {
                        $geminiRes = Http::timeout(5)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$geminiKey}", [
                            'system_instruction' => [
                                'parts' => [['text' => $systemPrompt]]
                            ],
                            'contents' => [
                                ['role' => 'user', 'parts' => [['text' => $userMessage]]]
                            ],
                            'generationConfig' => [
                                'temperature' => 0.3,
                                'maxOutputTokens' => 1000,
                            ]
                        ]);
                        if ($geminiRes->successful()) {
                            $gData = $geminiRes->json();
                            $geminiText = $gData['candidates'][0]['content']['parts'][0]['text'] ?? '';
                            if (!empty(trim($geminiText))) {
                                $reply = $this->cleanChatbotReply($geminiText, $userMessage);
                                return response()->json([
                                    'reply' => $reply,
                                    'booking_action' => $bookingAction,
                                    'booking_draft' => $bookingDraft,
                                ]);
                            }
                        }
                    } catch (\Exception $ge) {
                        Log::warning('Guest Chatbot Gemini fallback exception: ' . $ge->getMessage());
                    }
                }

                $fallback = $this->generateDirectFallbackResponse($userMessage, $bookingDraft);
                return response()->json([
                    'reply' => !empty($fallback) ? $fallback : 'The assistant is temporarily unavailable. Please call 0917 861 8383.',
                    'booking_action' => $bookingAction,
                    'booking_draft' => $bookingDraft,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Guest Chatbot Exception (engaging intelligent fallback): ' . $e->getMessage());

            // Optional fallback to Gemini if GEMINI_API_KEY is configured
            $geminiKey = env('GEMINI_API_KEY');
            if ($geminiKey) {
                try {
                    $geminiRes = Http::timeout(5)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$geminiKey}", [
                        'system_instruction' => [
                            'parts' => [['text' => $systemPrompt]]
                        ],
                        'contents' => [
                            ['role' => 'user', 'parts' => [['text' => $userMessage]]]
                        ],
                        'generationConfig' => [
                            'temperature' => 0.3,
                            'maxOutputTokens' => 1000,
                        ]
                    ]);
                    if ($geminiRes->successful()) {
                        $gData = $geminiRes->json();
                        $geminiText = $gData['candidates'][0]['content']['parts'][0]['text'] ?? '';
                        if (!empty(trim($geminiText))) {
                            $reply = $this->cleanChatbotReply($geminiText, $userMessage);
                            return response()->json([
                                'reply' => $reply,
                                'booking_action' => $bookingAction,
                                'booking_draft' => $bookingDraft,
                            ]);
                        }
                    }
                } catch (\Exception $ge) {
                    Log::warning('Guest Chatbot Gemini fallback exception: ' . $ge->getMessage());
                }
            }

            $fallback = $this->generateDirectFallbackResponse($userMessage, $bookingDraft);
            return response()->json([
                'reply' => !empty($fallback) ? $fallback : 'The guest concierge is temporarily unavailable. Please call 0917 861 8383.',
                'booking_action' => $bookingAction,
                'booking_draft' => $bookingDraft,
            ]);
        }
    }

    /**
     * Clean raw AI response to strip thinking processes, reasoning tags, numbered scratchpad steps, and draft labels.
     */
    private function cleanChatbotReply(string $reply, string $userMessage = ''): string
    {
        $confusedFallback = "I'm sorry, i couldnt understand and im confuse can you state it again";

        if (empty(trim($reply))) {
            return !empty($userMessage) ? $this->generateDirectFallbackResponse($userMessage) : $confusedFallback;
        }

        $text = trim($reply);

        // Check for safety evaluation/guardrail responses (e.g. "User Safety: safe\nResponse Safety: safe")
        if (preg_match('/(?:user\s+safety|response\s+safety)/i', $reply) || preg_match('/(?:user\s+safety|response\s+safety)/i', $text)) {
            $strippedSafety = trim(preg_replace('/^(?:user|response)\s*safety\s*:\s*[^\r\n]*/im', '', $text));
            if (empty($strippedSafety) || preg_match('/(?:user\s+safety|response\s+safety)/i', $strippedSafety)) {
                return $confusedFallback;
            }
            $text = $strippedSafety;
        }

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

        // 7. Strip leading bot/role prefixes like "Bren:", "HinaguanBot:", "StaffBot:", "AdminBot:", "Assistant:"
        $text = preg_replace('/^(?:Bren(?:\s+AI\s+Assistant)?|HinaguanBot|StaffBot|AdminBot|GuestBot|Bot|Assistant|AI):\s*/i', '', trim($text));

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

        $cleaned = trim($text);
        if (empty($cleaned)) {
            return !empty($userMessage) ? $this->generateDirectFallbackResponse($userMessage) : $confusedFallback;
        }
        if (preg_match('/(?:user\s+safety|response\s+safety)/i', $cleaned)) {
            return $confusedFallback;
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

        // Detect language
        $isBisaya = (bool) preg_match('/\b(?:pila|tagpila|asa|nindot|suba|puy-anan|unsa|init|uwan|tugnaw|daghan|gamay|kaayo|gani|diay|kinsa|kanus-a|kabuok|ka\s+tawo|unsaon|adto|anhi|swak|lahi|pagkaon|sugba|sapa)\b/i', $userMessage)
            || preg_match('/\b(?:ug|sa)\s+(?:mga|amenity|cottage|payag|pagkaon)\b/i', $userMessage);

        $isTagalog = (bool) preg_match('/\b(?:magkano|meron|saan|ano|anong|ba|po|opo|ulan|mainit|pwede|kami|tayo|sino|kailan|tao|bawat|paano|pumunta|sakyan|pagkain|maganda|sulit)\b/i', $userMessage);

        // If strong Bisaya markers are present, prefer Bisaya
        if (preg_match('/\b(?:ug|pagkaon|nindot|unsa|kabuok|ka\s+tawo|kanus-a|suba|sapa)\b/i', $userMessage)) {
            $isBisaya = true;
            $isTagalog = false;
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

        // 2. Short words / greetings / typos (e.g. "what te", "hi", "hello", "hey", "kumusta")
        if (preg_match('/^(?:hi+|hello+|hey+|good\s*(?:morning|afternoon|evening)|kumusta|kamusta|halo|bren|sup|yo|start)\b/i', $msgLower) || mb_strlen($msgLower) <= 2) {
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
                $pax = !empty($bookingDraft['guest']['guests_count']) ? (int) $bookingDraft['guest']['guests_count'] : 1;

                $dp = round($total * 0.5, 2);
                $bal = round($total - $dp, 2);

                return "Here is the record of what you have selected so far:\n"
                    . "{$amenitiesStr}\n"
                    . "• 📅 Target Schedule: {$scheduleStr}\n"
                    . "• 👤 Primary Guest: {$booker}\n"
                    . "• 📞 Mobile & Email: {$phone} | {$email}\n"
                    . "• 👥 Headcount: {$pax} pax\n"
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

        // 5. Amenity Availability & Date Inquiries (e.g. "what date is payag 4 available", "is cottage 1 available")
        $availabilityReply = $this->handleAmenityAvailabilityInquiry($userMessage, $isBisaya, $isTagalog);
        if ($availabilityReply !== null) {
            return $availabilityReply;
        }

        // 6. Direct Booking Mode command: /book
        if (trim($msgLower) === '/book') {
            if ($isBisaya) {
                return "Pwede kaayo! Pwede ta mag-book sa imong online reservation dinhi sa chat 🌿\n\nGusto ba nimo nga sugdan na nato ang imong booking karon?";
            } elseif ($isTagalog) {
                return "Pwedeng-pwede po! Pwede tayong mag-book ng inyong online reservation dito mismo sa chat 🌿\n\nGusto niyo po bang simulan na natin ang inyong pag-book ngayon?";
            } else {
                return "I can certainly book a reservation for you right here in our chat! 🌿\n\nWould you like to start your booking now?";
            }
        }

        // 7. Book via Chat Inquiry (e.g. "can I book via chat?", "how to book via chat", "pwede ba mag book sa chat")
        if (preg_match('/(?:book\s+via\s+chat|book\s+sa\s+chat|mag-?book\s+sa\s+chat|pwede\s+(?:ba\s+)?(?:mag-?)?book\s+dinhi|dito\s+ba\s+mag-?book)/i', $msgLower)) {
            if ($isBisaya) {
                return "Oo, pwede kaayo ka mag-book dinhi mismo sa chat! 🌿\n\nI-click lang ang berdeng **Book via Chat** button sa ibabaw (o i-type ang `/book`). Giya-an ka nako sa pagpili sa petsa, amenities, ug pagkompirmar sa imong reservation.";
            } elseif ($isTagalog) {
                return "Opo, pwedeng-pwede po kayong mag-book dito mismo sa ating chat! 🌿\n\nI-click lamang po ang berdeng **Book via Chat** button sa itaas (o i-type ang `/book`). Gagabayan ko po kayo sa pagpili ng petsa, amenities, at pag-confirm ng inyong reservation.";
            } else {
                return "Yes, you can certainly book your reservation right here in our chat! 🌿\n\nSimply click the green **Book via Chat** button right above (or type `/book`). I will guide you step-by-step through selecting your dates, choosing your amenities, and receiving your digital pass.";
            }
        }

        // 8. How to Book Online Inquiry (supports chip: "How do I book an amenity online?")
        $isBookingInquiry = (bool) preg_match('/(?:how\s+(?:do\s+i|to|can\s+i)\s+(?:book|reserve)|paano\s+(?:mag-?)?(?:book|reserve)|unsaon\s+pag-?(?:book|reserve)|steps?\s+to\s+(?:book|reserve)|online\s+booking|booking\s+process|procedure|downpayment|down\s*payment|no\s*refund|non-?refundable|refund\s*policy)/i', $msgLower);
        if ($isBookingInquiry) {
            if ($isBisaya) {
                return "Aron mag-book online sa Hinaguan Nature Park:\n"
                    . "1. I-click ang **Book Now** button sa website (o i-click ang **Book via Chat** sa ibabaw).\n"
                    . "2. Pagpili una ug target date o pilia ang imong gustong amenity (Cottage, Payag, A-House, o Function Hall).\n"
                    . "3. Pilia ang session slot (Daytime: {$dayStart} – {$dayEnd}, Nighttime: {$nightStart} – {$nightEnd}, o Continuous Stay).\n"
                    . "4. Isulod ang mga detalye sa primary guest ug gidaghanon sa mga kauban.\n"
                    . "5. Bayri ang **50% DOWNPAYMENT** online aron ma-secure ug makumpirma ang imong reservation.\n\n"
                    . "⚠️ **MAHALAGANG PAHINUMDOM**: Ang 50% downpayment kay **STRICTLY NON-REFUNDABLE** (walay refund), apan pwede mohangyo ug reschedule sa petsa kung naay advance notice.";
            } elseif ($isTagalog) {
                return "Para mag-book online sa Hinaguan Nature Park:\n"
                    . "1. I-click ang **Book Now** button sa website (o i-click ang **Book via Chat** sa itaas).\n"
                    . "2. Pumili ng target date o piliin ang inyong gustong amenity (Cottage, Payag, A-House, o Function Hall).\n"
                    . "3. Piliin ang session slot (Daytime: {$dayStart} – {$dayEnd}, Nighttime: {$nightStart} – {$nightEnd}, o Continuous Stay).\n"
                    . "4. Ilagay ang detalye ng primary guest at bilang ng mga kasama.\n"
                    . "5. Magbayad ng **50% DOWNPAYMENT** online upang ma-secure at makumpirma ang inyong reservation.\n\n"
                    . "⚠️ **MAHALAGANG PAALALA**: Ang 50% downpayment po ay **STRICTLY NON-REFUNDABLE** (walang refund), ngunit maaari kayong mag-request ng rescheduling ng petsa.";
            } else {
                return "To book a reservation online at Hinaguan Nature Park:\n"
                    . "1. Click the **Book Now** button on our website (or click **Book via Chat** right above!).\n"
                    . "2. Select your visit date and preferred amenity (Cottage, Payag, A-House, or Function Hall).\n"
                    . "3. Choose your session slot (Daytime: {$dayStart} – {$dayEnd}, Nighttime: {$nightStart} – {$nightEnd}, or multi-day Continuous Stay).\n"
                    . "4. Fill in primary guest contact details and companion headcount.\n"
                    . "5. Pay the required **50% DOWNPAYMENT** online to lock in and confirm your reservation.\n\n"
                    . "⚠️ **MANDATORY POLICY**: Please note that the 50% downpayment is **STRICTLY NON-REFUNDABLE** (no refund policy), though date rescheduling can be requested with advance notice.";
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
        $isSuggestion = (bool) preg_match('/(?:suggest|recommend|rekomenda|bagay|swak|best\s+amenit|maganda(?:ng)?|nindot|what\s+(?:should|can)\s+i\s+(?:book|choose|pick|avail|get)|anong|ano\s+po|unsa|which\s+(?:amenit|cottage|payag|hut|house)|pili\s+ng\s+amenit|pilig\s+amenit)/i', $msgLower);

        $isCouple = (bool) preg_match('/\b(?:couple|mag-?asawa|magkasintahan|dalawa|2\s*(?:pax|people|persons|tao)?|for\s+2)\b/i', $userMessage);

        // Group size recommendation (e.g. "for 10 pax", "group of 10 people", or "couple")
        if ($isCouple || preg_match('/(\d+)\s*(?:people|persons|pax|guests|heads|kabuok|ka\s+tao|tao)/i', $userMessage, $m) ||
            preg_match('/for\s+(\d+)/i', $userMessage, $m)) {
            $pax = $isCouple ? 2 : (int) ($m[1] ?? 2);

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
        $isRulesInquiry = (bool) preg_match('/(?:rule|rules|bawal|polic|corkage|outside\s+food|pagkaon|pagkain|food|drink|inom|alcohol|beer|liquor|pet|pets|dog|iro|aso|cat|grill|grilling|ihaw|sugba|barbecue|bbq|park(?:ing)?|smoking|sigarilyo|vape|swimwear|attire)/i', $msgLower);
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
        $currentTimeStr = $now->format('F j, Y - g:i A');

        $context .= "Current Date/Time: {$currentTimeStr}\n";

        $draftAmenities = !empty($bookingDraft['amenities']) && is_array($bookingDraft['amenities'])
            ? $bookingDraft['amenities']
            : (!empty($bookingDraft['amenity']) ? [$bookingDraft['amenity']] : []);

        if (!empty($draftAmenities)) {
            $amNames = array_map(fn ($a) => ($a['name'] ?? 'Amenity') . " (₱" . number_format((float) ($a['price'] ?? 0), 2) . ")", $draftAmenities);
            $total = array_sum(array_map(fn ($a) => (float) ($a['price'] ?? 0), $draftAmenities));
            $dp = round($total * 0.5, 2);
            $bal = round($total - $dp, 2);
            $startDate = $bookingDraft['startDate'] ?? ($bookingDraft['date'] ?? $todayStr);
            $endDate = $bookingDraft['endDate'] ?? $startDate;
            $startSlot = $bookingDraft['startSlot'] ?? ($bookingDraft['slot'] ?? 'Daytime');
            $endSlot = $bookingDraft['endSlot'] ?? $startSlot;
            $booker = !empty($bookingDraft['guest']['name']) ? $bookingDraft['guest']['name'] : 'Pending name';
            $phone = !empty($bookingDraft['guest']['phone']) ? $bookingDraft['guest']['phone'] : 'Pending phone';
            $pax = !empty($bookingDraft['guest']['guests_count']) ? (int) $bookingDraft['guest']['guests_count'] : 1;

            $context .= "\n[ACTIVE USER BOOKING DRAFT IN PROGRESS (STORED IN LOCALSTORAGE)]:\n"
                . "- Picked Amenities (" . count($draftAmenities) . " items): " . implode(', ', $amNames) . "\n"
                . "- Schedule: Check-in {$startDate} [{$startSlot}] to Check-out {$endDate} [{$endSlot}]\n"
                . "- Primary Booker: {$booker} (Phone: {$phone})\n"
                . "- Number of Guests: {$pax}\n"
                . "- Total: ₱" . number_format($total, 2) . " | 50% Downpayment Required: ₱" . number_format($dp, 2) . " | Balance Due: ₱" . number_format($bal, 2) . "\n"
                . "- Note: If the user asks 'show me what amenity i just avail before it book', confirm these exact details to them!\n";
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
                    if ($isBisaya) {
                        return "Oo, available kaayo ang {$targetAmenity->amenities_name} ({$priceStr}) sa {$formattedDate}! 🌿\n\nGusto ba nimo kini i-book karon? I-click lang ang **Book via Chat** o gamita ang **Book Now** button sa website.";
                    } elseif ($isTagalog) {
                        return "Opo, available na available po ang {$targetAmenity->amenities_name} ({$priceStr}) sa {$formattedDate}! 🌿\n\nGusto niyo po bang i-book ito ngayon? I-click lamang po ang **Book via Chat** o ang **Book Now** button sa website.";
                    } else {
                        return "Yes! {$targetAmenity->amenities_name} ({$priceStr}) is completely available on {$formattedDate}! 🌿\n\nWould you like to book it now? Click **Book via Chat** or use the **Book Now** button on our website to confirm.";
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
