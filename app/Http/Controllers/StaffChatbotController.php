<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Amenity;
use App\Models\Announcement;
use App\Models\ChatbotMessage;
use App\Models\Customer;
use App\Models\DailyWeatherShiftLog;
use App\Models\Feedback;
use App\Models\ParkActivity;
use App\Models\ParkEvent;
use App\Models\ParkRule;
use App\Models\ParkSetting;
use App\Models\RescheduleRequest;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\ReservationCharge;
use App\Models\ReservationEntranceFee;
use App\Models\ReservationGuest;
use App\Models\UserActivityRead;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StaffChatbotController extends Controller
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
        $authUser = session('auth_user') ?? [];
        $userId = !empty($authUser['id']) ? (int) $authUser['id'] : null;

        // Persist user's message to database if authenticated
        $userMsgRecord = null;
        if ($userId) {
            $userMsgRecord = ChatbotMessage::create([
                'user_type' => 'staff',
                'user_id' => $userId,
                'role' => 'user',
                'content' => $userMessage,
                'model' => $request->input('model', 'openrouter/free'),
            ]);
        }

        // Security Guardrail: Staff chatbot CANNOT access staff account passwords or admin credentials
        $restrictedForStaff = [
            'staff password', 'admin password', 'all staff password',
            'admin credentials', 'database credentials', 'api key', 'secret key',
            'create admin', 'ban admin', 'delete admin',
            'staff account', 'admin account'
        ];

        foreach ($restrictedForStaff as $term) {
            if (str_contains($msgLower, $term)) {
                $guardrailReply = "I am the Staff Assistant. I cannot display or modify staff account credentials, passwords, or admin security accounts. Please consult the Park Administrator for system security management.";
                if ($userId) {
                    ChatbotMessage::create([
                        'user_type' => 'staff',
                        'user_id' => $userId,
                        'role' => 'assistant',
                        'content' => $guardrailReply,
                        'model' => $request->input('model', 'openrouter/free'),
                    ]);
                }
                return response()->json(['reply' => $guardrailReply]);
            }
        }

        // Check for completely off-topic questions
        $forbiddenTopics = ['write python code', 'solve math equation', 'celebrity gossip', 'astrology horoscope', 'cryptocurrency trading'];
        foreach ($forbiddenTopics as $topic) {
            if (stripos($userMessage, $topic) !== false) {
                $offTopicReply = "I can only assist with Hinaguan Nature Park staff operations, guest reservations, check-ins, checkouts, and demographic data mining.";
                if ($userId) {
                    ChatbotMessage::create([
                        'user_type' => 'staff',
                        'user_id' => $userId,
                        'role' => 'assistant',
                        'content' => $offTopicReply,
                        'model' => $request->input('model', 'openrouter/free'),
                    ]);
                }
                return response()->json(['reply' => $offTopicReply]);
            }
        }

        $apiKey = env('OPENROUTER_API_KEY');
        $model = $request->input('model', 'openrouter/free');

        if (!$apiKey) {
            $offlineReply = 'The staff chatbot service is currently offline. Please check system configuration.';
            if ($userId) {
                ChatbotMessage::create([
                    'user_type' => 'staff',
                    'user_id' => $userId,
                    'role' => 'assistant',
                    'content' => $offlineReply,
                    'model' => $model,
                ]);
            }
            return response()->json(['reply' => $offlineReply], 500);
        }

        $staffContext = $this->getStaffContext($userMessage);

        $systemPrompt = "You are HinaguanBot, the intelligent and highly accurate AI Operations Assistant for park staff at Hinaguan Nature Park in Jasaan, Misamis Oriental.\n\n"
            . "CRITICAL OUTPUT RULES (STRICTLY ENFORCED):\n"
            . "- OUTPUT ONLY YOUR DIRECT CONVERSATIONAL RESPONSE. Never include internal reasoning, thinking steps, chain-of-thought, outlines, scratchpads, or draft prefixes.\n"
            . "- NEVER prefix your response with 'Draft:', 'Response:', 'Answer:', or 'HinaguanBot:'. Start directly with your message to the staff member.\n"
            . "- Keep your answer concise, natural, and friendly (1 to 3 clear sentences for quick questions, or neatly structured bullet points if a list or full breakdown is asked).\n"
            . "- STRICT DATABASE ACCURACY (ZERO HALLUCINATION): Every name, reservation ID, rate, headcount, balance, and schedule must be derived STRICTLY from the LIVE DATABASE CONTEXT below. Never guess or invent numbers.\n\n"
            . "DATABASE COMPREHENSION & PARK LOGIC:\n"
            . "You are deeply integrated into the park's operational database tables (reservations, reservation_amenities, reservation_charges, reservation_entrance_fees, reservation_guests, customers, amenities, amenities_benefits, park_settings, park_rules, park_events, park_activities, feedbacks, daily_weather_shift_logs, reschedule_requests, sms_notifications, activity_logs, chatbot_messages, user_activity_reads).\n"
            . "1. RESERVATIONS & LIFECYCLES: 'pending' (new online booking waiting approval), 'Confirmed' (downpayment received, confirmed), 'Checked In' (currently in park), 'Checked Out' (departed), 'Cancelled'.\n"
            . "2. FINANCIAL CALCULATION: Total = Entrance Fees + Amenities + Extra Charges. Remaining Balance = Total - Amount Paid. Always alert staff if a departing or checked-in guest has an unpaid balance.\n"
            . "3. SESSIONS & SLOTS: Daytime (8:00 AM - 5:00 PM), Nighttime (6:00 PM - 8:00 AM next day). Continuous Stay spans across multiple days/slots.\n"
            . "4. AMENITY INCLUSIONS: Cottages (₱200) & Payags (₱300) DO NOT include free entrance or free pool access (standard entrance & pool fees apply). A-Houses include FREE entrance and FREE pool access for 2 guests. Function Hall includes FREE entrance & pool access for the booked group.\n"
            . "5. RESCHEDULE REQUESTS (reschedule_requests): Rebooking requests submitted by guests with original date, requested date, reason, and status (pending/approved/declined).\n"
            . "6. EXTRA CHARGES & PENALTIES (reservation_charges): Additional fees for extra mattresses, damages, corkage, late checkout, or extra hours.\n"
            . "7. ANNOUNCEMENTS & SMS (sms_notifications): SMS broadcasts sent to park visitors via PhilSMS for alerts, reminders, or promos.\n"
            . "8. DEMOGRAPHICS MINING: Guest counts for Kids (0-12), Teens (13-17), Adults (18-59), Seniors (60+), Gender (Female vs Male), and Locals vs Foreigners.\n"
            . "9. PARK RULES & SETTINGS: Operating hours, pool/entrance rates, Brenda Mage presence, outside food allowed with NO corkage, free grilling, and pets on leash.\n\n"
            . "LINGUISTIC FLUENCY (TYPOS, GRAMMAR & MULTILINGUAL):\n"
            . "- Effortlessly understand misspelled words, typographical errors, and phonetic spelling (e.g., 'resched', 'boking', 'cotag', 'pyag', 'chek in', 'chekout', 'balans', 'pila kuwang', 'demografic'). Deduce the user's intent without correcting them.\n"
            . "- Forgive grammatical errors, shorthand, and broken sentences seamlessly.\n"
            . "- Understand Bisaya/Cebuano, Tagalog, Taglish, and English naturally.\n"
            . "- MATCH THE USER'S LANGUAGE:\n"
            . "  * If the staff member asks in Bisaya / Cebuano (e.g., 'pila tanan kita karon', 'kinsa ang naka-check in', 'naa bay nagpa-resched', 'pila kuwang ni...', 'tagpila ang cottage'), reply warmly and naturally in Bisaya!\n"
            . "  * If the staff member asks in Tagalog / Taglish (e.g., 'magkano sales ngayon', 'sino naka check in', 'may resched ba', 'paki-check balance'), reply warmly and naturally in Tagalog/Taglish!\n"
            . "  * If the staff member asks in English, reply in English!\n\n"
            . "=== LIVE STAFF OPERATIONS & DATABASE CONTEXT ===\n"
            . $staffContext;

        $messagesPayload = [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ]
        ];

        // Retrieve past conversation history from database (or fallback to request payload)
        if ($userId && $userMsgRecord) {
            $pastDbMessages = ChatbotMessage::forUser('staff', $userId)
                ->where('id', '<', $userMsgRecord->id)
                ->latest('id')
                ->take(6)
                ->get()
                ->reverse();

            foreach ($pastDbMessages as $msg) {
                $messagesPayload[] = [
                    'role' => $msg->role,
                    'content' => $msg->content,
                ];
            }
        } else {
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
                'X-Title' => 'Hinaguan Nature Park Staff Portal',
            ])->post("https://openrouter.ai/api/v1/chat/completions", [
                'model' => $model,
                'messages' => $messagesPayload,
                'max_tokens' => 1000,
                'temperature' => 0.2,
                'include_reasoning' => false,
                'reasoning' => [
                    'effort' => 'none',
                    'exclude' => true,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $rawReply = $data['choices'][0]['message']['content'] ?? 'I could not process your request at this time.';
                $reply = $this->cleanChatbotReply($rawReply);

                if ($userId) {
                    ChatbotMessage::create([
                        'user_type' => 'staff',
                        'user_id' => $userId,
                        'role' => 'assistant',
                        'content' => $reply,
                        'model' => $model,
                    ]);
                }

                return response()->json(['reply' => $reply]);
            } else {
                Log::error('Staff Chatbot OpenRouter Error: ' . $response->body());
                $errReply = 'The staff assistant service encountered an error. Please try again shortly.';
                if ($userId) {
                    ChatbotMessage::create([
                        'user_type' => 'staff',
                        'user_id' => $userId,
                        'role' => 'assistant',
                        'content' => $errReply,
                        'model' => $model,
                    ]);
                }
                return response()->json(['reply' => $errReply], 500);
            }
        } catch (\Exception $e) {
            Log::error('Staff Chatbot Exception: ' . $e->getMessage());
            $excReply = 'The staff assistant is temporarily unavailable. Error: ' . $e->getMessage();
            if ($userId) {
                ChatbotMessage::create([
                    'user_type' => 'staff',
                    'user_id' => $userId,
                    'role' => 'assistant',
                    'content' => $excReply,
                    'model' => $model,
                ]);
            }
            return response()->json(['reply' => $excReply], 500);
        }
    }

    /**
     * Get saved chat history for the logged-in staff member.
     */
    public function history(Request $request)
    {
        $authUser = session('auth_user');
        if (!$authUser || empty($authUser['id'])) {
            return response()->json(['messages' => []]);
        }

        $messages = ChatbotMessage::forUser('staff', (int) $authUser['id'])
            ->orderBy('id', 'asc')
            ->get(['id', 'role', 'content', 'created_at'])
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'role' => $msg->role,
                    'content' => $msg->content,
                    'isBot' => $msg->role === 'assistant',
                    'created_at' => $msg->created_at?->toIso8601String(),
                ];
            });

        return response()->json(['messages' => $messages]);
    }

    /**
     * Clear all saved chat history for the logged-in staff member.
     */
    public function clear(Request $request)
    {
        $authUser = session('auth_user');
        if (!$authUser || empty($authUser['id'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        ChatbotMessage::forUser('staff', (int) $authUser['id'])->delete();

        return response()->json(['success' => true, 'message' => 'Conversation history cleared.']);
    }

    /**
     * Generate real-time responsive / proactive AI greeting with contextual insight & follow-up question.
     */
    public function proactiveMessage(Request $request)
    {
        $authUser = session('auth_user') ?? [];
        if (empty($authUser) || empty($authUser['id'])) {
            return response()->json(['has_message' => false], 401);
        }

        $userId = (int) $authUser['id'];
        $rawName = !empty($authUser['name']) ? trim($authUser['name']) : 'Staff';
        $firstName = explode(' ', $rawName)[0];
        $now = now();
        $todayStr = $now->toDateString();
        $hour = (int) $now->format('G');
        $timeOfDay = ($hour < 12) ? 'morning' : (($hour < 17) ? 'afternoon' : 'evening');

        $settings = ParkSetting::first();
        $isParkClosed = ($settings?->park_status ?? 'open') === 'closed';
        $closeDesc = $settings?->close_description ?: 'scheduled maintenance';

        $sessionKeysName = "staff_announced_keys_{$userId}";
        $announcedKeys = (array) session($sessionKeysName, []);
        $clientKeys = array_filter(explode(',', (string) $request->query('announced_keys', '')));
        foreach ($clientKeys as $ck) {
            $announcedKeys[$ck] = true;
        }

        $resSessionKey = "staff_last_announced_res_id_{$userId}";
        $clientLastAnnounced = (int) $request->query('last_announced_res_id', 0);
        $sessionLastAnnounced = (int) session($resSessionKey, 0);
        $lastAnnouncedResId = max($clientLastAnnounced, $sessionLastAnnounced);

        // 1. Pending Reservations needing staff action (matching reservations awaiting confirmation)
        $pendingQuery = Reservation::query()
            ->whereIn('status', ['Pending', 'pending'])
            ->where(function ($query) {
                $query->whereNull('check_in')
                    ->orWhere('check_in', '');
            });

        $pendingCount = (clone $pendingQuery)->count();
        $latestPending = (clone $pendingQuery)->latest('id')->first();

        // Check if there is an unannounced brand new reservation
        $isBrandNew = false;
        if ($latestPending) {
            if ($lastAnnouncedResId === 0) {
                // On initial load, announce if created recently (last 15 mins) or if forced/new activity triggered
                $isRecent = $latestPending->created_at && $latestPending->created_at->diffInMinutes(now()) <= 15;
                if ($isRecent || $request->boolean('force')) {
                    $isBrandNew = true;
                } else {
                    $lastAnnouncedResId = $latestPending->id;
                    session([$resSessionKey => $latestPending->id]);
                }
            } elseif ($latestPending->id > $lastAnnouncedResId) {
                $isBrandNew = true;
            }
        }

        // 2. Checkouts Due Today
        $dueCheckoutsCount = Reservation::where('status', 'Checked In')
            ->where(function ($q) use ($todayStr) {
                $q->whereDate('end_date', $todayStr)
                  ->orWhere(function ($q2) use ($todayStr) {
                      $q2->whereNull('end_date')->whereDate('reservation_date', $todayStr);
                  });
            })
            ->count();

        // 3. Checked-In Active Guests
        $activeGuestsCount = ReservationGuest::whereNull('checked_out_at')
            ->whereHas('reservation', fn($q) => $q->where('status', 'Checked In'))
            ->count();

        // 4. Revenue Comparisons
        $todayRevenue = (float) Reservation::whereDate('created_at', $todayStr)
            ->orWhereDate('reservation_date', $todayStr)
            ->sum('amount_paid');

        $yesterdayStr = $now->copy()->subDay()->toDateString();
        $yesterdayRevenue = (float) Reservation::whereDate('created_at', $yesterdayStr)
            ->orWhereDate('reservation_date', $yesterdayStr)
            ->sum('amount_paid');

        // 5. Live Weather
        $weatherCondition = 'Clear skies';
        $tempC = 29;
        $rainChance = 10;
        $isRaining = false;

        try {
            $weatherData = app(WeatherService::class)->getMultiDayForecast(1);
            if (!empty($weatherData['now'])) {
                $tempC = $weatherData['now']['temp_c'] ?? $tempC;
                $weatherCondition = $weatherData['now']['condition'] ?? $weatherCondition;
                $rainChance = $weatherData['now']['chance_of_rain'] ?? $rainChance;
                $isRaining = !empty($weatherData['now']['is_raining']) || $rainChance >= 60;
            }
        } catch (\Throwable $e) {
            // gracefully fallback
        }

        // Intelligently select the most timely insight scenario
        $scenario = 'default';
        $currentKey = "briefing_{$todayStr}_{$timeOfDay}";
        $headline = 'Shift Briefing';
        $message = "Good {$timeOfDay}, {$firstName}! All park operations are running smoothly today.";
        $followUp = "Would you like me to walk you through today's expected schedule or guest demographics?";
        $quickActionPrompt = "Give me an overview of today's schedule and expected guests";
        $actionBtnLabel = "View Overview";

        if ($isParkClosed) {
            $scenario = 'park_closed';
            $currentKey = "closed_{$closeDesc}";
            $headline = 'Park Closed Notice';
            $message = "Hey {$firstName}, just a reminder that the park is currently set to Closed (\"{$closeDesc}\").";
            $followUp = "Would you like to review operational details or check guest inquiries?";
            $quickActionPrompt = "Tell me the park closure status and guest guidelines";
            $actionBtnLabel = "Check Status";
        } elseif ($isBrandNew && $latestPending) {
            $scenario = 'pending_reservations';
            $currentKey = "pending_res_{$latestPending->id}";
            $headline = 'New Reservation';
            $message = "Hey {$firstName}, a new reservation is booked right now, go and check it!";
            $followUp = "Would you like me to summarize the booking details for {$latestPending->booker_name}?";
            $quickActionPrompt = "Summarize new reservation #{$latestPending->id} for {$latestPending->booker_name}";
            $actionBtnLabel = "Check Reservation";
            $lastAnnouncedResId = $latestPending->id;
            session([$resSessionKey => $latestPending->id]);
        } elseif ($dueCheckoutsCount > 0) {
            $scenario = 'due_checkouts';
            $currentKey = "due_checkouts_{$dueCheckoutsCount}_{$todayStr}";
            $headline = 'Due Checkouts';
            $plural = $dueCheckoutsCount > 1 ? "{$dueCheckoutsCount} reservations" : "1 reservation";
            $message = "Hey {$firstName}, we have {$plural} scheduled for checkout today.";
            $followUp = "Would you like me to pull up their departure time slots and check for any outstanding balances?";
            $quickActionPrompt = "Who is due for checkout today and do they have remaining balances?";
            $actionBtnLabel = "View Checkouts";
        } elseif ($todayRevenue > 0 && $todayRevenue >= $yesterdayRevenue) {
            $scenario = 'revenue_growth';
            $currentKey = "revenue_{$todayRevenue}_{$todayStr}";
            $headline = 'Revenue Milestone';
            $revFormatted = number_format($todayRevenue, 2);
            $message = "Wow {$firstName}, our revenue increased today, reaching ₱{$revFormatted}!";
            $followUp = "Would you like me to compare our current revenue and past collections?";
            $quickActionPrompt = "Compare current revenue with past collections and show top amenities";
            $actionBtnLabel = "Compare Revenue";
        } elseif ($activeGuestsCount >= 10) {
            $scenario = 'high_occupancy';
            $currentKey = "occupancy_" . floor($activeGuestsCount / 5) . "_{$todayStr}";
            $headline = 'Park Occupancy';
            $message = "Hey {$firstName}, the park is lively right now with {$activeGuestsCount} active guests inside!";
            $followUp = "Would you like me to check which cottages and amenities are still available for walk-ins?";
            $quickActionPrompt = "Check amenity availability and occupied slots for walk-ins";
            $actionBtnLabel = "Check Availability";
        } elseif ($isRaining || $rainChance >= 40) {
            $scenario = 'weather_rain';
            $currentKey = "weather_rain_{$weatherCondition}_" . round($rainChance / 20) . "_" . round($tempC / 2);
            $headline = 'Weather Alert';
            $message = "Heads up {$firstName}, there's a {$rainChance}% chance of rain in Jasaan ({$weatherCondition}, {$tempC}°C).";
            $followUp = "Would you like me to check the 3-day weather forecast for upcoming outdoor bookings?";
            $quickActionPrompt = "Check 3-day weather forecast and rain outlook";
            $actionBtnLabel = "Check Weather";
        } elseif (preg_match('/clear|sunny/i', $weatherCondition) || $tempC >= 27) {
            $scenario = 'weather_sunny';
            $currentKey = "weather_sunny_{$weatherCondition}_" . round($tempC / 2);
            $headline = 'Weather & Arrivals';
            $message = "Woah {$firstName}, we got nice weather right now in Jasaan ({$tempC}°C, {$weatherCondition})!";
            $followUp = "Would you like an overview of expected guest arrivals and remaining day slots?";
            $quickActionPrompt = "Show expected arrivals and day slot availability for today";
            $actionBtnLabel = "View Arrivals";
        }

        // If this exact announcement was already made and not forced, do not re-announce
        $alreadyAnnounced = !empty($announcedKeys[$currentKey]);
        if ($alreadyAnnounced && !$request->boolean('force')) {
            return response()->json([
                'has_message' => false,
                'scenario' => $scenario,
                'announced_key' => $currentKey,
                'announced_keys' => array_keys($announcedKeys),
                'announced_res_id' => $lastAnnouncedResId,
                'timestamp' => $now->toDateTimeString(),
            ]);
        }

        // Update announced keys in session
        $announcedKeys[$currentKey] = true;
        session([$sessionKeysName => $announcedKeys]);

        $fullSpeech = "{$message}\n\n{$followUp}";

        // Persist message to database if not already saved recently
        $lastMessage = ChatbotMessage::forUser('staff', $userId)->orderByDesc('id')->first();
        if (!$lastMessage || $lastMessage->content !== $fullSpeech) {
            ChatbotMessage::create([
                'user_type' => 'staff',
                'user_id' => $userId,
                'role' => 'assistant',
                'content' => $fullSpeech,
                'model' => 'openrouter/free',
            ]);
        }

        return response()->json([
            'has_message' => true,
            'scenario' => $scenario,
            'headline' => $headline,
            'message' => $message,
            'follow_up' => $followUp,
            'full_speech' => $fullSpeech,
            'quick_action_prompt' => $quickActionPrompt,
            'action_button_text' => $actionBtnLabel,
            'announced_key' => $currentKey,
            'announced_keys' => array_keys($announcedKeys),
            'announced_res_id' => $lastAnnouncedResId,
            'timestamp' => $now->toDateTimeString(),
        ]);
    }

    /**
     * Clean raw AI response to strip thinking processes, reasoning tags, numbered scratchpad steps, and draft labels.
     */
    private function cleanChatbotReply(string $reply): string
    {
        if (empty(trim($reply))) {
            return '';
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

        // 5. Filter out paragraphs that are numbered chain-of-thought analysis steps
        $paragraphs = preg_split('/\r?\n\s*\r?\n/', $text);
        if (count($paragraphs) > 1) {
            $filtered = [];
            foreach ($paragraphs as $p) {
                $trimmedP = trim($p);
                // If paragraph starts with a chain-of-thought / scratchpad step header, discard it
                if (preg_match('/^\d+\.\s*(?:Analyze|Analysis|Check|Retrieve|Search|Formulate|Draft|Understand|Examine|Review|Identify|Determine|Plan|Context|Task|Step|Consider|Thought|Think|Scenario|User|Intent|Input|Knowledge)/i', $trimmedP)) {
                    continue;
                }
                // If paragraph is solely meta reasoning bullets like "- User said...", "- Context: ...", discard it
                if (preg_match('/^(?:[-*•]\s+(?:User\s+said|Context:|Previous\s+turns:|The\s+|Now\s+|I\s+should|Mention\s+the|Direct\s+answer|No\s+thinking|Natural,))/i', $trimmedP)) {
                    continue;
                }
                $filtered[] = $p;
            }
            if (!empty($filtered)) {
                $text = implode("\n\n", $filtered);
            }
        }

        // 6. Strip any leftover "Draft:", "Response:", "Answer:" labels at start
        $text = preg_replace('/^(?:Draft|Final\s+Response|Final\s+Answer|Response|Output|Answer|Reply):\s*/i', '', trim($text));

        // 7. Strip leading bot/role prefixes like "HinaguanBot:", "StaffBot:", "AdminBot:", "Assistant:"
        $text = preg_replace('/^(?:HinaguanBot|StaffBot|AdminBot|GuestBot|Bot|Assistant|AI):\s*/i', '', trim($text));

        // 8. Strip surrounding quotation marks if the draft was wrapped in quotes (e.g., `"Right now, ..."` or `'Right now, ...'`)
        $text = trim($text);
        if ((str_starts_with($text, '"') && str_ends_with($text, '"')) || (str_starts_with($text, "'") && str_ends_with($text, "'"))) {
            if (strlen($text) >= 2) {
                $text = trim(substr($text, 1, -1));
            }
        }
        // Also strip a leading quote if the draft was cut off with an unclosed leading quote (e.g. `"Right now, ...`)
        if (str_starts_with($text, '"') && substr_count($text, '"') === 1) {
            $text = ltrim($text, '"');
        }

        return trim($text);
    }

    private function getStaffContext(string $message): string
    {
        $context = '';
        $now = now();
        $todayStr = $now->toDateString();
        $currentTimeStr = $now->format('F j, Y - g:i A');

        $context .= "Current Date/Time: {$currentTimeStr}\n";

        // BASELINE: Official Park Settings & Live Rates from park_settings table
        $settings = ParkSetting::first();
        if ($settings) {
            $isOpen = ($settings->park_status ?? 'open') === 'open';
            $statusStr = $isOpen 
                ? "OPEN (Operating normally for day and night visitors)" 
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

            $context .= "\n[OFFICIAL PARK SETTINGS & LIVE RATES (park_settings)]:\n"
                . "- Park Operational Status: {$statusStr}\n"
                . "- General Park Hours: {$openTime} to {$closeTime}\n"
                . "- Daytime Session Hours: {$dayStart} - {$dayEnd} | Adult: ₱{$dayAdult}, Child (12 & below): {$dayChildStr}\n"
                . "- Nighttime Session Hours: {$nightStart} - {$nightEnd} | Adult: ₱{$nightAdult}, Child (12 & below): {$nightChildStr}\n"
                . "- Swimming Pool Access Fees: Day: ₱{$dayPool}/person, Night: ₱{$nightPool}/person\n"
                . "- Brenda Mage Presence: {$brendaStatus}\n"
                . "- Contact: " . ($settings->contact_number ?: '0985-323-9532') . " | Email: " . ($settings->email ?: 'parkhinaguan@gmail.com') . "\n";
        }

        // QUICK OPERATIONAL SNAPSHOT (Always present for baseline awareness)
        $checkedInCount = Reservation::where('status', 'Checked In')->count();
        $totalCheckedInGuests = (int) Reservation::where('status', 'Checked In')->sum('number_of_guests');
        $dueCheckoutsCount = Reservation::where('status', 'Checked In')
            ->where(function ($q) use ($todayStr) {
                $q->whereDate('end_date', $todayStr)
                  ->orWhere(function ($q2) use ($todayStr) {
                      $q2->whereNull('end_date')->whereDate('reservation_date', $todayStr);
                  });
            })->count();
        $pendingCount = Reservation::where('status', 'pending')->count();
        $todayRevenue = (float) Reservation::whereDate('reservation_date', $todayStr)
            ->orWhereDate('created_at', $todayStr)
            ->sum('amount_paid');

        $context .= "\n[TODAY'S OPERATIONAL SUMMARY]:\n"
            . "- Active Checked-in: {$checkedInCount} reservations ({$totalCheckedInGuests} guests currently on site)\n"
            . "- Due for Checkout Today: {$dueCheckoutsCount} reservations\n"
            . "- Pending Online Bookings Awaiting Action: {$pendingCount}\n"
            . "- Today's Collected Revenue: ₱" . number_format($todayRevenue, 2) . "\n";

        // INTELLIGENT CONNECTED TOPIC DETECTION (Retrieves data only if it connects to the user's inquiry)
        $topics = $this->detectStaffTopics($message);

        // 1. SPECIFIC ENTITY SEARCH (Reservation by ID or Customer / Booker by Name or Phone)
        if (!empty($topics['specific_entity']) || !empty($topics['search_terms'])) {
            $matchedReservations = collect();

            if (!empty($topics['specific_id'])) {
                $foundById = Reservation::with(['reservationAmenities.amenity', 'reservationGuests.customer', 'entranceFee', 'reservationCharges', 'rescheduleRequests'])
                    ->find($topics['specific_id']);
                if ($foundById) {
                    $matchedReservations->push($foundById);
                }
            }

            if (!empty($topics['search_terms'])) {
                foreach ($topics['search_terms'] as $term) {
                    $found = Reservation::with(['reservationAmenities.amenity', 'reservationGuests.customer', 'entranceFee', 'reservationCharges', 'rescheduleRequests'])
                        ->where(function ($q) use ($term) {
                            $q->where('booker_name', 'like', "%{$term}%")
                              ->orWhere('phone', 'like', "%{$term}%")
                              ->orWhere('email', 'like', "%{$term}%");
                        })
                        ->take(5)
                        ->get();
                    $matchedReservations = $matchedReservations->merge($found);
                }
            }

            $matchedReservations = $matchedReservations->unique('id');

            if ($matchedReservations->isNotEmpty()) {
                $context .= "\n[CONNECTED DATABASE RECORDS - MATCHED RESERVATIONS]:\n";
                foreach ($matchedReservations as $r) {
                    $ams = $r->reservationAmenities->map(fn ($ra) => ($ra->amenity?->amenities_name ?? 'Amenity') . " [{$ra->status}]")->implode(', ');
                    $guests = $r->reservationGuests->map(fn ($g) => $g->customer ? "{$g->customer->first_name} {$g->customer->last_name} (Age " . ($g->customer->age ?? 'N/A') . ", {$g->customer->gender})" : 'Guest')->implode(', ');
                    $charges = $r->reservationCharges->map(fn ($rc) => "{$rc->description}: ₱" . number_format($rc->amount, 2))->implode('; ');
                    $rescheds = $r->rescheduleRequests->map(fn ($rq) => "Date: {$rq->requested_date} [Status: {$rq->status}]")->implode('; ');

                    $context .= "- Reservation #{$r->id}: Booker {$r->booker_name} (Phone: {$r->phone}, Email: {$r->email})\n"
                        . "  * Status: {$r->status} ({$r->reservation_type}) | Guests: {$r->number_of_guests}\n"
                        . "  * Stay Schedule: {$r->reservation_date} [{$r->start_slot}] to " . ($r->end_date ?: $r->reservation_date) . " [" . ($r->end_slot ?: $r->start_slot) . "]\n"
                        . "  * Financials: Total: ₱" . number_format($r->total_amount, 2) . ", Paid: ₱" . number_format($r->amount_paid, 2) . ", Balance Due: ₱" . number_format($r->remaining_balance, 2) . " [{$r->payment_status}]\n"
                        . "  * Booked Amenities: " . ($ams ?: 'None') . "\n"
                        . "  * Registered Guests: " . ($guests ?: 'None recorded') . "\n";
                    if ($charges) {
                        $context .= "  * Extra Charges: {$charges}\n";
                    }
                    if ($rescheds) {
                        $context .= "  * Reschedule Requests: {$rescheds}\n";
                    }
                }
            }
        }

        // 2. RESCHEDULE REQUESTS TABLE (reschedule_requests)
        if (!empty($topics['reschedules'])) {
            $reschedRequests = RescheduleRequest::with('reservation')->orderByDesc('id')->take(10)->get();
            $context .= "\n[CONNECTED DATABASE RECORDS - RESCHEDULE REQUESTS (reschedule_requests)]:\n";
            if ($reschedRequests->isNotEmpty()) {
                foreach ($reschedRequests as $rq) {
                    $booker = $rq->reservation?->booker_name ?? 'Guest';
                    $context .= "- Request #{$rq->id} for Res #{$rq->reservation_id} ({$booker}): Original: {$rq->original_date} -> Requested: {$rq->requested_date} | Status: {$rq->status} | Reason: " . ($rq->reason ?: 'No reason stated') . "\n";
                }
            } else {
                $context .= "No reschedule requests found in database.\n";
            }
        }

        // 3. CHARGES & UNPAID BALANCES (reservation_charges & reservations)
        if (!empty($topics['charges'])) {
            $charges = ReservationCharge::with(['reservation', 'amenity'])->orderByDesc('id')->take(10)->get();
            $unpaidReservations = Reservation::where('remaining_balance', '>', 0)
                ->whereIn('status', ['Confirmed', 'Checked In'])
                ->orderByDesc('remaining_balance')
                ->take(10)
                ->get();

            $context .= "\n[CONNECTED DATABASE RECORDS - CHARGES & BALANCES (reservation_charges)]:\n";
            if ($charges->isNotEmpty()) {
                $context .= "Recent Charges Added:\n";
                foreach ($charges as $c) {
                    $booker = $c->reservation?->booker_name ?? 'Res #' . $c->reservation_id;
                    $am = $c->amenity?->amenities_name ? " (for {$c->amenity->amenities_name})" : "";
                    $context .= "- Charge #{$c->id}: ₱" . number_format($c->amount, 2) . " [{$c->charge_type}] for {$booker}{$am} - {$c->description} (Status: {$c->status})\n";
                }
            }
            if ($unpaidReservations->isNotEmpty()) {
                $context .= "Reservations with Remaining Balance to Collect:\n";
                foreach ($unpaidReservations as $ur) {
                    $context .= "- Res #{$ur->id}: {$ur->booker_name} | Balance Due: ₱" . number_format($ur->remaining_balance, 2) . " | Status: {$ur->status} | Phone: {$ur->phone}\n";
                }
            }
        }

        // 4. CHECK-INS & ON-SITE OCCUPANCY (reservations, reservation_amenities)
        if (!empty($topics['checkins']) || empty($topics['is_specific'])) {
            $checkedIn = Reservation::with(['reservationAmenities.amenity', 'reservationGuests.customer'])
                ->where('status', 'Checked In')
                ->orderByDesc('check_in')
                ->get();

            $context .= "\n[CONNECTED DATABASE RECORDS - CURRENTLY CHECKED IN ON SITE ({$checkedIn->count()} reservations)]:\n";
            if ($checkedIn->isNotEmpty()) {
                foreach ($checkedIn as $res) {
                    $ams = $res->reservationAmenities->map(fn ($ra) => ($ra->amenity?->amenities_name ?? 'Amenity'))->implode(', ');
                    $balNotice = $res->remaining_balance > 0 ? " | ⚠️ BALANCE DUE: ₱" . number_format($res->remaining_balance, 2) : " | Paid";
                    $context .= "- Res #{$res->id}: {$res->booker_name} (Phone: {$res->phone}) | Guests: {$res->number_of_guests} | Depart: " . ($res->end_date ?: $res->reservation_date) . " [{$res->start_slot}]{$balNotice} | Amenities: " . ($ams ?: 'None') . "\n";
                }
            } else {
                $context .= "No guests currently checked in.\n";
            }
        }

        // 5. CHECKOUTS & DEPARTURES (reservations)
        if (!empty($topics['checkouts'])) {
            $todayDepartures = Reservation::with(['reservationAmenities.amenity'])
                ->where('status', 'Checked In')
                ->where(function ($q) use ($todayStr) {
                    $q->whereDate('end_date', $todayStr)
                      ->orWhere(function ($q2) use ($todayStr) {
                          $q2->whereNull('end_date')->whereDate('reservation_date', $todayStr);
                      });
                })
                ->get();

            $context .= "\n[CONNECTED DATABASE RECORDS - TODAY'S DUE CHECKOUTS ({$todayDepartures->count()} reservations)]:\n";
            if ($todayDepartures->isNotEmpty()) {
                foreach ($todayDepartures as $dep) {
                    $slot = $dep->end_slot ?: $dep->start_slot;
                    $expectedTime = strcasecmp((string)$slot, 'Nighttime') === 0 ? '6:00 AM (Next Morning)' : '5:00 PM - 6:00 PM (Today)';
                    $bal = $dep->remaining_balance > 0 ? " [⚠️ Collect balance: ₱" . number_format($dep->remaining_balance, 2) . "]" : " [Fully Paid]";
                    $context .= "- Res #{$dep->id}: {$dep->booker_name} (Slot: {$slot} -> Checkout: {$expectedTime}){$bal}\n";
                }
            } else {
                $context .= "No guests due for departure today.\n";
            }
        }

        // 6. GUEST DEMOGRAPHICS MINING (customers & reservation_guests)
        if (!empty($topics['demographics'])) {
            $allGuests = ReservationGuest::with('customer')->get();
            $kids = 0; $teens = 0; $adults = 0; $seniors = 0; $females = 0; $males = 0; $locals = 0; $foreigners = 0;

            foreach ($allGuests as $rg) {
                $c = $rg->customer;
                if (!$c) continue;

                if ($c->age !== null) {
                    if ($c->age <= 12) $kids++;
                    elseif ($c->age <= 17) $teens++;
                    elseif ($c->age <= 59) $adults++;
                    else $seniors++;
                }
                if (strtolower((string) $c->gender) === 'female') $females++;
                else $males++;

                if ($c->is_foreigner) $foreigners++;
                else $locals++;
            }

            $context .= "\n[CONNECTED DATABASE RECORDS - DEMOGRAPHICS MINING (customers & reservation_guests)]:\n"
                . "- Total Guests Recorded: {$allGuests->count()}\n"
                . "- Age Groups: Kids (0-12): {$kids}, Teens (13-17): {$teens}, Adults (18-59): {$adults}, Seniors (60+): {$seniors}\n"
                . "- Gender Breakdown: Female: {$females}, Male: {$males}\n"
                . "- Origin: Local Visitors: {$locals}, Foreign Visitors: {$foreigners}\n";
        }

        // 7. AMENITIES, RATES & INCLUSIONS (amenities & amenities_benefits)
        if (!empty($topics['amenities']) || empty($topics['is_specific'])) {
            $amenities = Amenity::with('benefits')->where('status', true)->get();
            $context .= "\n[CONNECTED DATABASE RECORDS - AMENITIES & INCLUSIONS (amenities)]:\n";
            foreach ($amenities as $am) {
                $b = $am->benefits;
                $freeEnt = ($b && $b->free_entrance) ? 'YES (Free entrance included)' : 'NO (Regular entrance fees apply)';
                $freePool = ($b && $b->free_pool) ? 'YES (Free pool included)' : 'NO (Pool fees apply)';
                $aircon = ($b && $b->is_aircon) ? 'YES (Air-conditioned)' : 'NO (Open-air / Non-aircon)';
                $addHead = number_format((float) $am->additional_per_head, 2);

                $minCap = !empty($am->minimum_capacity) ? (int) $am->minimum_capacity : 1;
                $maxCap = !empty($am->maximum_capacity) ? (int) $am->maximum_capacity : $minCap;
                $cap = ($minCap === $maxCap) ? "{$minCap} pax" : "{$minCap} to {$maxCap} pax";

                $context .= "- {$am->amenities_name} (Capacity: {$cap}):\n"
                    . "  * Day Rate: ₱" . number_format((float) $am->daytime_price, 2) . " | Night Rate: ₱" . number_format((float) $am->nighttime_price, 2) . " | Extra Head: ₱{$addHead}\n"
                    . "  * Inclusions: Free Entrance: {$freeEnt} | Free Pool: {$freePool} | Aircon: {$aircon}\n";
            }
        }

        // 8. PARK RULES & REGULATIONS (park_rules)
        if (!empty($topics['rules'])) {
            $rules = ParkRule::all();
            $context .= "\n[CONNECTED DATABASE RECORDS - OFFICIAL PARK RULES (park_rules)]:\n";
            foreach ($rules as $r) {
                $context .= "- {$r->rule_name}: {$r->rule_descriptions}\n";
            }
        }

        // 9. PARK EVENTS & HAPPENINGS (park_events)
        if (!empty($topics['events'])) {
            $events = ParkEvent::where('is_active', true)->orderBy('date')->get();
            $context .= "\n[CONNECTED DATABASE RECORDS - PARK EVENTS (park_events)]:\n";
            if ($events->isNotEmpty()) {
                foreach ($events as $ev) {
                    $d = $ev->date ? Carbon::parse($ev->date)->format('M d, Y') : 'Date TBA';
                    $t = $ev->time ? " at {$ev->time}" : "";
                    $context .= "- {$ev->title}: {$d}{$t} - {$ev->event}\n";
                }
            } else {
                $context .= "No active park events scheduled.\n";
            }
        }

        // 10. RECREATIONAL PARK ACTIVITIES (park_activities)
        if (!empty($topics['activities'])) {
            $activities = ParkActivity::all();
            $context .= "\n[CONNECTED DATABASE RECORDS - PARK ACTIVITIES (park_activities)]:\n";
            if ($activities->isNotEmpty()) {
                foreach ($activities as $act) {
                    $context .= "- {$act->activity}: {$act->description}\n";
                }
            } else {
                $context .= "Activities include swimming in the river/pools, sightseeing, picnics, and native relaxation.\n";
            }
        }

        // 11. GUEST REVIEWS & FEEDBACK (feedbacks & feedback_images)
        if (!empty($topics['feedback'])) {
            $feedbacks = Feedback::latest()->take(6)->get();
            $avgStars = Feedback::avg('stars');
            $context .= "\n[CONNECTED DATABASE RECORDS - REVIEWS & FEEDBACK (feedbacks)]:\n"
                . "- Average Rating: " . number_format((float) $avgStars, 1) . " / 5.0 stars (Total: " . Feedback::count() . " reviews)\n";
            foreach ($feedbacks as $fb) {
                $name = $fb->is_anonymous ? 'Anonymous Guest' : $fb->full_name;
                $context .= "- [{$fb->stars} Stars] {$name}: \"{$fb->description}\" (Replied: " . ($fb->replied ? 'Yes' : 'No') . ")\n";
            }
        }

        // 12. WEATHER & SHIFT LOGS (daily_weather_shift_logs & WeatherService)
        if (!empty($topics['weather'])) {
            $weatherLogs = DailyWeatherShiftLog::orderByDesc('log_date')->take(4)->get();
            $context .= "\n[CONNECTED DATABASE RECORDS - WEATHER SHIFT LOGS (daily_weather_shift_logs)]:\n";
            if ($weatherLogs->isNotEmpty()) {
                foreach ($weatherLogs as $wl) {
                    $context .= "- {$wl->log_date} ({$wl->shift} shift): {$wl->condition}, {$wl->temperature_c}°C, {$wl->rain_chance_percentage}% rain chance. Notes: " . ($wl->notes ?: 'None') . "\n";
                }
            }
            try {
                $weatherNow = app(WeatherService::class)->getMultiDayForecast(1);
                if (!empty($weatherNow['now'])) {
                    $context .= "Current Live Forecast for Jasaan: {$weatherNow['now']['condition']}, {$weatherNow['now']['temp_c']}°C, {$weatherNow['now']['chance_of_rain']}% rain chance.\n";
                }
            } catch (\Throwable $e) {}
        }

        // 13. ANNOUNCEMENTS & SMS (sms_notifications)
        if (!empty($topics['announcements'])) {
            $announcements = Announcement::orderByDesc('id')->take(6)->get();
            $context .= "\n[CONNECTED DATABASE RECORDS - SMS ANNOUNCEMENTS (sms_notifications)]:\n";
            if ($announcements->isNotEmpty()) {
                foreach ($announcements as $an) {
                    $context .= "- [{$an->category}] {$an->title}: \"{$an->message}\" | Recipients: {$an->recipient_count} | Status: {$an->delivery_status} | Sent: " . ($an->created_at ? $an->created_at->format('M d, Y g:i A') : 'N/A') . "\n";
                }
            } else {
                $context .= "No SMS announcements found in database.\n";
            }
        }

        // 14. SALES & CASHIER TOTALS (reservations)
        if (!empty($topics['sales'])) {
            $allRes = Reservation::all();
            $todayRes = Reservation::whereDate('reservation_date', $todayStr)->orWhereDate('created_at', $todayStr)->get();
            $thisWeekRes = Reservation::whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])->get();

            $context .= "\n[CONNECTED DATABASE RECORDS - SALES & CASHIER COLLECTIONS (reservations)]:\n"
                . "- Today's Collected Sales: ₱" . number_format($todayRes->sum('amount_paid'), 2) . "\n"
                . "- This Week's Collected Sales: ₱" . number_format($thisWeekRes->sum('amount_paid'), 2) . "\n"
                . "- All-Time Total Collected: ₱" . number_format($allRes->sum('amount_paid'), 2) . "\n"
                . "- Total Pending Uncollected Balance: ₱" . number_format($allRes->sum('remaining_balance'), 2) . "\n";
        }

        // 15. ACTIVITY AUDIT TRAIL (activity_logs)
        if (!empty($topics['activity_logs'])) {
            $logs = ActivityLog::orderByDesc('id')->take(10)->get();
            $context .= "\n[CONNECTED DATABASE RECORDS - RECENT ACTIVITY AUDIT TRAIL (activity_logs)]:\n";
            if ($logs->isNotEmpty()) {
                foreach ($logs as $l) {
                    $t = $l->created_at ? $l->created_at->format('M d g:i A') : 'N/A';
                    $resId = $l->reservation_id ? " [Res #{$l->reservation_id}]" : "";
                    $context .= "- [{$t}]{$resId} {$l->description} (by {$l->actor_name})\n";
                }
            } else {
                $context .= "No activity logs recorded yet.\n";
            }
        }

        return $context;
    }

    /**
     * Detect specific topics, IDs, and search terms from the staff query.
     * Robustly tolerates misspellings, colloquialisms, Taglish, Tagalog, and Bisaya.
     */
    private function detectStaffTopics(string $message): array
    {
        $msgLower = mb_strtolower(trim($message));
        $topics = [
            'is_specific' => false,
            'specific_id' => null,
            'search_terms' => [],
            'reschedules' => false,
            'charges' => false,
            'checkins' => false,
            'checkouts' => false,
            'demographics' => false,
            'amenities' => false,
            'rules' => false,
            'events' => false,
            'activities' => false,
            'feedback' => false,
            'weather' => false,
            'announcements' => false,
            'sales' => false,
            'activity_logs' => false,
        ];

        // 1. Extract possible Reservation ID
        if (preg_match('/(?:#|\bres(?:ervation)?\s*#?|\bid\s*#?)\s*(\d+)/i', $msgLower, $matches)) {
            $topics['specific_id'] = (int) $matches[1];
            $topics['specific_entity'] = true;
            $topics['is_specific'] = true;
        } elseif (preg_match('/\b\d{1,6}\b/', $msgLower, $matches)) {
            $topics['specific_id'] = (int) $matches[0];
            $topics['specific_entity'] = true;
            $topics['is_specific'] = true;
        }

        // 2. Extract potential names or search words (words of 3+ letters excluding common keywords)
        $stopWords = ['the', 'and', 'for', 'are', 'what', 'who', 'how', 'pila', 'kinsa', 'unsa', 'naa', 'karon', 'kaha', 'ba', 'nga', 'ang', 'sa', 'og', 'ug', 'sino', 'ano', 'bakit', 'meron', 'wala', 'mga', 'bang', 'may', 'po', 'ngayon', 'today', 'check', 'show', 'give', 'list', 'park', 'hinaguan'];
        $words = preg_split('/[\s,\.\?!;:]+/', $msgLower);
        foreach ($words as $w) {
            $w = trim($w);
            if (mb_strlen($w) >= 3 && !in_array($w, $stopWords, true) && !is_numeric($w)) {
                // If it might be a customer name, check against Customer or Reservation booker_name
                if (Reservation::where('booker_name', 'like', "%{$w}%")->exists() || Customer::where('first_name', 'like', "%{$w}%")->orWhere('last_name', 'like', "%{$w}%")->exists()) {
                    $topics['search_terms'][] = $w;
                    $topics['specific_entity'] = true;
                    $topics['is_specific'] = true;
                }
            }
        }

        // 3. Reschedules / Date changes
        if (preg_match('/resched|reched|rebook|balhin|ilis|lipat|palit|move\s*date|postpone|change\s*date|bag[\s-]?o.*petsa/i', $msgLower)) {
            $topics['reschedules'] = true;
            $topics['is_specific'] = true;
        }

        // 4. Charges, Damages, Balances, Unpaid
        if (preg_match('/charge|penalty|penalties|damage|damages|mattress|corkage|korkage|guba|bayranan|utang|unpaid|kuwang|kulang|balance|balanse|remaining|bayad/i', $msgLower)) {
            $topics['charges'] = true;
            $topics['is_specific'] = true;
        }

        // 5. Check-ins, Headcount, Occupancy
        if (preg_match('/check[\s-]?in|sulod|loob|pumasok|nisulod|nisud|active\s*guest|headcount|occupancy|present|on[\s-]?site/i', $msgLower)) {
            $topics['checkins'] = true;
            $topics['is_specific'] = true;
        }

        // 6. Checkouts, Departures
        if (preg_match('/check[\s-]?out|chekot|chckout|departure|gawas|nigawas|lumabas|alis|uwian|due|countdown/i', $msgLower)) {
            $topics['checkouts'] = true;
            $topics['is_specific'] = true;
        }

        // 7. Demographics & Age / Gender Breakdown
        if (preg_match('/demograph|demograf|bata|kid|child|teen|adult|senior|tigulang|gender|lalaki|lalake|babae|babaye|male|female|foreigner|dayo|local|lokal|edad|age/i', $msgLower)) {
            $topics['demographics'] = true;
            $topics['is_specific'] = true;
        }

        // 8. Amenities, Capacities & Rates
        if (preg_match('/amenit|cottag|cotag|payag|pyag|a[\s-]?house|ahouse|function\s*hall|hall|pool|swim|ligo|langoy|aircon|capacity|kapasidad|inclusions|libre|free|bakante|availab|presyo|rate|pila|tagpila|magkano|abang|rent/i', $msgLower)) {
            $topics['amenities'] = true;
            $topics['is_specific'] = true;
        }

        // 9. Rules & Guidelines
        if (preg_match('/rule|policy|policies|patakaran|balaod|bawal|pwede|allowed|prohibit|pet|iro|aso|smok|panigarilyo|inom|liquor|alcohol|curfew|attire|swimwear|grill/i', $msgLower)) {
            $topics['rules'] = true;
            $topics['is_specific'] = true;
        }

        // 10. Events & Celebrations
        if (preg_match('/event|happen|okasyon|pista|fiesta|celebrat|concert|party|kalendaryo|holiday/i', $msgLower)) {
            $topics['events'] = true;
            $topics['is_specific'] = true;
        }

        // 11. Recreational Activities
        if (preg_match('/activit|lingaw|buhaton|kayak|sakayan|hike|hiking|trail|trek|nature\s*walk|photo|pictorial|picture|attraction/i', $msgLower)) {
            $topics['activities'] = true;
            $topics['is_specific'] = true;
        }

        // 12. Reviews & Feedbacks
        if (preg_match('/feedback|review|rating|star|reklamo|complaint|komento|comment|ingon|satisfaction/i', $msgLower)) {
            $topics['feedback'] = true;
            $topics['is_specific'] = true;
        }

        // 13. Weather & Forecast
        if (preg_match('/weather|panahon|uwan|ulan|rain|init|sunny|init\s*kaayo|bagyo|storm|temp|temperature|forecast|shift\s*log/i', $msgLower)) {
            $topics['weather'] = true;
            $topics['is_specific'] = true;
        }

        // 14. Announcements & SMS
        if (preg_match('/announc|sms|text|blast|broadcast|pahibalo|anunsyo|notif/i', $msgLower)) {
            $topics['announcements'] = true;
            $topics['is_specific'] = true;
        }

        // 15. Sales & Cashier Collections
        if (preg_match('/sale|revenue|financial|income|halin|kita|gross|koleksyon|collection|total\s*bayad|cashier|walk[\s-]?in\s*sales|online\s*sales/i', $msgLower)) {
            $topics['sales'] = true;
            $topics['is_specific'] = true;
        }

        // 16. Activity Logs & Audit Trail
        if (preg_match('/audit|log|history|kinsa\s*nag|sino\s*nag|who\s*did|who\s*checked|who\s*approved|recent\s*action|gi[\s-]?update|gi[\s-]?usab|record/i', $msgLower)) {
            $topics['activity_logs'] = true;
            $topics['is_specific'] = true;
        }

        return $topics;
    }
}

