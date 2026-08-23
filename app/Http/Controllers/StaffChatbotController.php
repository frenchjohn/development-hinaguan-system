<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\ChatbotMessage;
use App\Models\Customer;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\ReservationGuest;
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
            'create admin', 'ban admin', 'delete admin'
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

        $systemPrompt = "You are HinaguanBot, a friendly and helpful AI assistant for the staff at Hinaguan Nature Park.\n\n"
            . "CRITICAL OUTPUT RULES (STRICTLY ENFORCED):\n"
            . "- OUTPUT ONLY THE DIRECT CONVERSATIONAL RESPONSE. Do NOT include reasoning steps, planning, internal monologue, notes, or analytical scratchpads.\n"
            . "- NEVER output numbered analysis steps (e.g. '1. Analyze User Input:', '2. Check Knowledge Base:', '3. Formulate Response:').\n"
            . "- NEVER prefix your response with 'Draft:', 'Response:', 'Answer:', or 'HinaguanBot:'. Start directly with your message to the staff member.\n"
            . "- Keep your response natural, warm, and concise (1 to 3 clear, flowing sentences) unless the staff member specifically asks for a full list or report.\n"
            . "- Understand English, Tagalog, Bisaya, and Taglish naturally.\n\n"
            . "CORE KNOWLEDGE & CAPABILITIES:\n"
            . "1. OPERATIONS: Checked-in guests, departures/due checkouts today, countdowns, upcoming arrivals, stay extensions, and checkout procedures.\n"
            . "2. CASHIER & BALANCES: Total sales collected, and reservations with unpaid remaining balances.\n"
            . "3. WALK-IN PRICE CALCULATOR: Calculate total walk-in cost for staff (Adults × Entrance + Kids × Entrance + Pool + Amenity).\n"
            . "4. DEMOGRAPHICS MINING: Guest counts for Kids (0-12), Teens (13-17), Adults (18-59), Seniors (60+), Gender (Female vs Male), and Nationality.\n"
            . "5. PARK RATES: Daytime Entrance: Adult ₱70, Child ₱50 | Nighttime Entrance: Adult ₱100, Child ₱70 | Pool: Day ₱100, Night ₱150.\n\n"
            . "=== LIVE STAFF OPERATIONS & ANALYTICS CONTEXT ===\n"
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
                'max_tokens' => 600,
                'temperature' => 0.2,
                'include_reasoning' => false,
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

        $settings = ParkSetting::first();
        $statusStr = ($settings?->park_status ?? 'open') === 'closed' 
            ? "CLOSED (Reason: " . ($settings?->close_description ?: 'Temporarily closed for maintenance') . ")" 
            : "OPEN (Operating normally for all day and night visitors)";
        $context .= "Live Park Operational Status: {$statusStr}\n";

        // 1. CURRENTLY CHECKED-IN ACTIVE GUESTS & HEADCOUNT
        $checkedIn = Reservation::with(['reservationAmenities.amenity', 'reservationGuests.customer'])
            ->where('status', 'Checked In')
            ->orderByDesc('check_in')
            ->get();

        $totalCheckedInGuests = $checkedIn->sum('number_of_guests');
        $context .= "\n[CURRENTLY CHECKED-IN ON SITE ({$checkedIn->count()} reservations, {$totalCheckedInGuests} total guests)]:\n";
        if ($checkedIn->isNotEmpty()) {
            foreach ($checkedIn as $res) {
                $ams = $res->reservationAmenities->map(fn ($ra) => ($ra->amenity?->amenities_name ?? 'Amenity') . " [{$ra->status}]")->implode(', ');
                $endDate = $res->end_date ?: $res->reservation_date;
                $endSlot = $res->end_slot ?: $res->start_slot;
                $balanceNotice = $res->remaining_balance > 0 ? " | ⚠️ UNPAID BALANCE: ₱" . number_format($res->remaining_balance, 2) : " | Paid in Full";
                $context .= "- Reservation #{$res->id}: {$res->booker_name} (Phone: {$res->phone}) | Depart: {$endDate} [{$endSlot}] | Guests: {$res->number_of_guests} | Paid: ₱" . number_format($res->amount_paid, 2) . "{$balanceNotice} | Booked: " . ($ams ?: 'None') . "\n";
            }
        } else {
            $context .= "No guests currently checked in on site.\n";
        }

        // 2. TODAY'S DUE CHECKOUTS
        $todayDepartures = Reservation::with(['reservationAmenities.amenity'])
            ->where('status', 'Checked In')
            ->where(function ($q) use ($todayStr) {
                $q->whereDate('end_date', $todayStr)
                  ->orWhere(function ($q2) use ($todayStr) {
                      $q2->whereNull('end_date')->whereDate('reservation_date', $todayStr);
                  });
            })
            ->get();

        $context .= "\n[DUE FOR CHECKOUT TODAY ({$todayDepartures->count()} reservations)]:\n";
        if ($todayDepartures->isNotEmpty()) {
            foreach ($todayDepartures as $dep) {
                $slot = $dep->end_slot ?: $dep->start_slot;
                $expectedTime = strcasecmp((string)$slot, 'Nighttime') === 0 ? '6:00 AM (Next Morning)' : '6:00 PM (Today)';
                $bal = $dep->remaining_balance > 0 ? " [⚠️ Balance to collect: ₱" . number_format($dep->remaining_balance, 2) . "]" : " [Paid]";
                $context .= "- Reservation #{$dep->id}: {$dep->booker_name} (Slot: {$slot} -> Expected: {$expectedTime}){$bal}\n";
            }
        } else {
            $context .= "No reservations due for checkout today.\n";
        }

        // 3. RECENT 15 RESERVATIONS LEDGER
        $recentBookings = Reservation::with(['reservationAmenities.amenity'])
            ->orderByDesc('id')
            ->take(15)
            ->get();

        $context .= "\n[RECENT RESERVATIONS (LATEST 15)]:\n";
        foreach ($recentBookings as $rb) {
            $amList = $rb->reservationAmenities->pluck('amenity.amenities_name')->filter()->implode(', ');
            $context .= "- Reservation #{$rb->id}: {$rb->booker_name} | Status: {$rb->status} ({$rb->reservation_type}) | Date: {$rb->reservation_date} [{$rb->start_slot}] | Paid: ₱" . number_format($rb->amount_paid, 2) . ", Balance: ₱" . number_format($rb->remaining_balance, 2) . " | Amenities: " . ($amList ?: 'None') . "\n";
        }

        // 4. SALES & CASHIER TOTALS
        $allRes = Reservation::all();
        $todayRes = Reservation::whereDate('reservation_date', $todayStr)->orWhereDate('created_at', $todayStr)->get();
        $thisWeekRes = Reservation::whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])->get();

        $context .= "\n[SALES & CASHIER COLLECTIONS]:\n"
            . "- Today's Collected Sales: ₱" . number_format($todayRes->sum('amount_paid'), 2) . "\n"
            . "- This Week's Collected Sales: ₱" . number_format($thisWeekRes->sum('amount_paid'), 2) . "\n"
            . "- All-Time Total Collected: ₱" . number_format($allRes->sum('amount_paid'), 2) . "\n"
            . "- Total Pending Uncollected Balance: ₱" . number_format($allRes->sum('remaining_balance'), 2) . "\n";

        // 5. GUEST DEMOGRAPHICS DATA MINING
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

        $context .= "\n[DEMOGRAPHICS MINING]:\n"
            . "- Total Guests: {$allGuests->count()} | Kids (0-12): {$kids}, Teens (13-17): {$teens}, Adults (18-59): {$adults}, Seniors (60+): {$seniors}\n"
            . "- Gender: Female: {$females}, Male: {$males} | Locals: {$locals}, Foreigners: {$foreigners}\n";

        // 6. AMENITIES RATES
        $amenities = Amenity::where('status', true)->get();
        $context .= "\n[AMENITIES & RATES]:\n";
        foreach ($amenities as $am) {
            $context .= "- {$am->amenities_name} (Cap {$am->minimum_capacity}-{$am->maximum_capacity}): Day ₱{$am->daytime_price}, Night ₱{$am->nighttime_price}\n";
        }

        // 7. SPECIFIC DEEP SEARCH
        if (preg_match('/(\d+)/', $message, $numMatches)) {
            $lookupId = (int) $numMatches[1];
            $specificRes = Reservation::with(['reservationAmenities.amenity', 'reservationGuests.customer'])->find($lookupId);
            if ($specificRes) {
                $guestList = $specificRes->reservationGuests->map(fn ($g) => $g->customer ? "{$g->customer->first_name} {$g->customer->last_name} (Age " . ($g->customer->age ?? 'N/A') . ", {$g->customer->gender})" : 'Guest')->implode(', ');
                $context .= "\n[DETAILS FOR RESERVATION #{$lookupId}]:\n"
                    . "- Booker: {$specificRes->booker_name} (Phone: {$specificRes->phone}) | Status: {$specificRes->status}\n"
                    . "- Schedule: {$specificRes->reservation_date} [{$specificRes->start_slot}] to " . ($specificRes->end_date ?: $specificRes->reservation_date) . " [" . ($specificRes->end_slot ?: $specificRes->start_slot) . "]\n"
                    . "- Paid: ₱" . number_format($specificRes->amount_paid, 2) . ", Balance Due: ₱" . number_format($specificRes->remaining_balance, 2) . "\n"
                    . "- Guests: " . ($guestList ?: 'None') . "\n";
            }
        }

        // 8. OFFICIAL PARK RULES & GUIDELINES FROM DATABASE
        $rules = \App\Models\ParkRule::all();
        if ($rules->isNotEmpty()) {
            $context .= "\n[OFFICIAL PARK RULES & GUIDELINES]:\n";
            foreach ($rules as $r) {
                $context .= "- {$r->rule_name}: {$r->rule_descriptions}\n";
            }
        }

        return $context;
    }
}
