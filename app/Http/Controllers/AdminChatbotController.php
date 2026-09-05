<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Amenity;
use App\Models\ChatbotMessage;
use App\Models\Customer;
use App\Models\Feedback;
use App\Models\ParkEvent;
use App\Models\ParkRule;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\ReservationGuest;
use App\Models\StaffAccount;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminChatbotController extends Controller
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
        $authUser = session('auth_user') ?? [];
        $userId = !empty($authUser['id']) ? (int) $authUser['id'] : null;

        // Persist user's message to database if authenticated
        $userMsgRecord = null;
        if ($userId) {
            $userMsgRecord = ChatbotMessage::create([
                'user_type' => 'admin',
                'user_id' => $userId,
                'role' => 'user',
                'content' => $userMessage,
                'model' => $request->input('model', 'openrouter/free'),
            ]);
        }

        // Check for general completely off-topic questions
        $forbiddenTopics = ['write python code', 'solve math equation', 'celebrity gossip', 'astrology horoscope', 'cryptocurrency trading'];
        foreach ($forbiddenTopics as $topic) {
            if (stripos($userMessage, $topic) !== false) {
                $offTopicReply = "I am the Hinaguan Nature Park Admin Intelligence Assistant. I specialize in park operations, reservations, revenue analytics, staff records, and activity audit logs.";
                if ($userId) {
                    ChatbotMessage::create([
                        'user_type' => 'admin',
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
            $offlineReply = 'The admin intelligence service is currently offline. Please check system configuration.';
            if ($userId) {
                ChatbotMessage::create([
                    'user_type' => 'admin',
                    'user_id' => $userId,
                    'role' => 'assistant',
                    'content' => $offlineReply,
                    'model' => $model,
                ]);
            }
            return response()->json(['reply' => $offlineReply], 500);
        }

        $adminContext = $this->getAdminContext($userMessage);

        $systemPrompt = "You are HinaguanBot, a friendly and professional executive intelligence assistant for administrators at Hinaguan Nature Park.\n\n"
            . "CRITICAL OUTPUT RULES (STRICTLY ENFORCED):\n"
            . "- OUTPUT ONLY THE DIRECT CONVERSATIONAL RESPONSE. Do NOT include reasoning steps, planning, internal monologue, notes, or analytical scratchpads.\n"
            . "- NEVER output numbered analysis steps (e.g. '1. Analyze User Input:', '2. Check Knowledge Base:', '3. Formulate Response:').\n"
            . "- NEVER prefix your response with 'Draft:', 'Response:', 'Answer:', or 'HinaguanBot:'. Start directly with your briefing to the administrator.\n"
            . "- Speak naturally in clear, professional, executive-level sentences (1 to 3 concise sentences) blending names, numbers, and dates smoothly.\n"
            . "- Understand English, Tagalog, Bisaya, and Taglish naturally.\n"
            . "- STRICT DATABASE ACCURACY (ZERO HALLUCINATION): Always quote fees, rates, operating hours, amenities, rules, and events EXACTLY as listed in the LIVE SYSTEM & DATABASE CONTEXT below. NEVER guess or invent prices.\n\n"
            . "CORE KNOWLEDGE & CAPABILITIES:\n"
            . "1. AUDIT & RECENT ACTIVITIES: Pinpoint who performed check-ins, checkouts, extensions, or account actions with exact reservation IDs, staff names, and timestamps.\n"
            . "2. EXECUTIVE FINANCIALS: Provide gross revenue, collected sales, outstanding uncollected balances, today/weekly/monthly revenue, and online vs walk-in breakdowns.\n"
            . "3. STAFF ROSTER: Report total staff, active/banned status, and staff member details.\n"
            . "4. DEMOGRAPHICS MINING: Report Kids (0-12), Teens (13-17), Adults (18-59), Seniors (60+), Gender (Female/Male), and Nationality counts.\n"
            . "5. RESERVATIONS & ON-SITE OCCUPANCY: Report checked-in reservations, departures, and look up any specific reservation by ID or name.\n"
            . "6. LIVE PARK SETTINGS, RULES & RATES: Current entrance fees, pool rates, operating hours, active park rules, and park events directly from the database.\n\n"
            . "=== LIVE SYSTEM & DATABASE CONTEXT ===\n"
            . $adminContext;

        $messagesPayload = [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ]
        ];

        // Retrieve past conversation history from database (or fallback to request payload)
        if ($userId && $userMsgRecord) {
            $pastDbMessages = ChatbotMessage::forUser('admin', $userId)
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
                'X-Title' => 'Hinaguan Nature Park Admin Portal',
            ])->post("https://openrouter.ai/api/v1/chat/completions", [
                'model' => $model,
                'messages' => $messagesPayload,
                'max_tokens' => 600,
                'temperature' => 0.2,
                'include_reasoning' => false,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $rawReply = $data['choices'][0]['message']['content'] ?? 'I could not process your admin query at this moment.';
                $reply = $this->cleanChatbotReply($rawReply);

                if ($userId) {
                    ChatbotMessage::create([
                        'user_type' => 'admin',
                        'user_id' => $userId,
                        'role' => 'assistant',
                        'content' => $reply,
                        'model' => $model,
                    ]);
                }

                return response()->json(['reply' => $reply]);
            } else {
                Log::error('Admin Chatbot OpenRouter Error: ' . $response->body());
                $errReply = 'The admin intelligence service encountered an error. Please try again shortly.';
                if ($userId) {
                    ChatbotMessage::create([
                        'user_type' => 'admin',
                        'user_id' => $userId,
                        'role' => 'assistant',
                        'content' => $errReply,
                        'model' => $model,
                    ]);
                }
                return response()->json(['reply' => $errReply], 500);
            }
        } catch (\Exception $e) {
            Log::error('Admin Chatbot Exception: ' . $e->getMessage());
            $excReply = 'The admin intelligence assistant is temporarily unavailable. Error: ' . $e->getMessage();
            if ($userId) {
                ChatbotMessage::create([
                    'user_type' => 'admin',
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
     * Get saved chat history for the logged-in admin.
     */
    public function history(Request $request)
    {
        $authUser = session('auth_user');
        if (!$authUser || empty($authUser['id'])) {
            return response()->json(['messages' => []]);
        }

        $messages = ChatbotMessage::forUser('admin', (int) $authUser['id'])
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
     * Clear all saved chat history for the logged-in admin.
     */
    public function clear(Request $request)
    {
        $authUser = session('auth_user');
        if (!$authUser || empty($authUser['id'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        ChatbotMessage::forUser('admin', (int) $authUser['id'])->delete();

        return response()->json(['success' => true, 'message' => 'Conversation history cleared.']);
    }

    /**
     * Generate real-time responsive / proactive AI greeting for Administrator.
     */
    public function proactiveMessage(Request $request)
    {
        $authUser = session('auth_user') ?? [];
        if (empty($authUser) || empty($authUser['id'])) {
            return response()->json(['has_message' => false], 401);
        }

        $userId = (int) $authUser['id'];
        $rawName = !empty($authUser['name']) ? trim($authUser['name']) : 'Admin';
        $firstName = explode(' ', $rawName)[0];

        $now = now();
        $todayStr = $now->toDateString();
        $hour = (int) $now->format('G');
        $timeOfDay = ($hour < 12) ? 'morning' : (($hour < 17) ? 'afternoon' : 'evening');

        $settings = ParkSetting::first();
        $isParkClosed = ($settings?->park_status ?? 'open') === 'closed';
        $closeDesc = $settings?->close_description ?: 'scheduled maintenance';

        $sessionKeysName = "admin_announced_keys_{$userId}";
        $announcedKeys = (array) session($sessionKeysName, []);
        $clientKeys = array_filter(explode(',', (string) $request->query('announced_keys', '')));
        foreach ($clientKeys as $ck) {
            $announcedKeys[$ck] = true;
        }

        // 1. Total Collected Revenue Today vs Yesterday
        $todayRevenue = (float) Reservation::whereDate('created_at', $todayStr)
            ->orWhereDate('reservation_date', $todayStr)
            ->sum('amount_paid');

        $yesterdayStr = $now->copy()->subDay()->toDateString();
        $yesterdayRevenue = (float) Reservation::whereDate('created_at', $yesterdayStr)
            ->orWhereDate('reservation_date', $yesterdayStr)
            ->sum('amount_paid');

        // 2. Recent Staff Activities in Audit Log (Last 3 hours)
        $recentStaffActivitiesCount = ActivityLog::where('created_at', '>=', $now->copy()->subHours(3))->count();

        // 3. Live Weather
        $weatherCondition = 'Clear skies';
        $tempC = 29;
        $rainChance = 10;

        try {
            $weatherData = app(WeatherService::class)->getMultiDayForecast(1);
            if (!empty($weatherData['now'])) {
                $tempC = $weatherData['now']['temp_c'] ?? $tempC;
                $weatherCondition = $weatherData['now']['condition'] ?? $weatherCondition;
                $rainChance = $weatherData['now']['chance_of_rain'] ?? $rainChance;
            }
        } catch (\Throwable $e) {}

        // Select the most relevant scenario for Admin
        $scenario = 'default';
        $currentKey = "admin_briefing_{$todayStr}_{$timeOfDay}";
        $headline = 'Admin Briefing';
        $message = "Good {$timeOfDay}, {$firstName}! All management systems and staff audit logs are running smoothly.";
        $followUp = "Would you like an intelligence report on recent revenue, demographics, or staff activity?";
        $quickActionPrompt = "Give me an admin intelligence briefing on revenue and operations";
        $actionBtnLabel = "Admin Briefing";

        if ($isParkClosed) {
            $scenario = 'park_closed';
            $currentKey = "admin_closed_{$closeDesc}";
            $headline = 'Park Closed Notice';
            $message = "Hey {$firstName}, the park is currently set to Closed (\"{$closeDesc}\").";
            $followUp = "Would you like me to review the park operational settings or pending guest inquiries?";
            $quickActionPrompt = "Show current park settings and operational status";
            $actionBtnLabel = "Review Settings";
        } elseif ($todayRevenue > 0 && $todayRevenue >= $yesterdayRevenue) {
            $scenario = 'revenue_growth';
            $currentKey = "admin_revenue_{$todayRevenue}_{$todayStr}";
            $headline = 'Revenue Milestone';
            $revFormatted = number_format($todayRevenue, 2);
            $message = "Wow {$firstName}, our revenue increased today, reaching ₱{$revFormatted}!";
            $followUp = "Would you like me to compare our current revenue and past collections?";
            $quickActionPrompt = "Compare today's revenue with previous periods and show top earning amenities";
            $actionBtnLabel = "Compare Revenue";
        } elseif ($recentStaffActivitiesCount > 0) {
            $scenario = 'recent_activities';
            $currentKey = "admin_activities_{$recentStaffActivitiesCount}";
            $headline = 'Recent Staff Activities';
            $message = "Hey {$firstName}, {$recentStaffActivitiesCount} staff activities have recently been logged in the audit trail.";
            $followUp = "Would you like me to summarize the latest staff check-ins and stay extensions?";
            $quickActionPrompt = "Summarize recent staff activity audit logs";
            $actionBtnLabel = "Audit Summary";
        } elseif (preg_match('/clear|sunny/i', $weatherCondition) || $tempC >= 27) {
            $scenario = 'weather_sunny';
            $currentKey = "admin_weather_{$weatherCondition}_" . round($tempC / 2);
            $headline = 'Weather Intelligence';
            $message = "Woah {$firstName}, we got nice weather right now in Jasaan ({$tempC}°C, {$weatherCondition})!";
            $followUp = "Would you like a quick breakdown of today's resort operations and expected revenue?";
            $quickActionPrompt = "Give me today's resort operations and revenue overview";
            $actionBtnLabel = "Resort Overview";
        }

        // If this exact announcement was already made and not forced, do not re-announce
        $alreadyAnnounced = !empty($announcedKeys[$currentKey]);
        if ($alreadyAnnounced && !$request->boolean('force')) {
            return response()->json([
                'has_message' => false,
                'scenario' => $scenario,
                'announced_key' => $currentKey,
                'announced_keys' => array_keys($announcedKeys),
                'timestamp' => $now->toDateTimeString(),
            ]);
        }

        // Update announced keys in session
        $announcedKeys[$currentKey] = true;
        session([$sessionKeysName => $announcedKeys]);

        $fullSpeech = "{$message}\n\n{$followUp}";

        // Persist message to database if not duplicate
        $lastMessage = ChatbotMessage::forUser('admin', $userId)->orderByDesc('id')->first();
        if (!$lastMessage || $lastMessage->content !== $fullSpeech) {
            ChatbotMessage::create([
                'user_type' => 'admin',
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

    private function getAdminContext(string $message): string
    {
        $context = '';
        $now = now();
        $todayStr = $now->toDateString();
        $currentTimeStr = $now->format('F j, Y - g:i A');

        $context .= "Current Date/Time: {$currentTimeStr}\n";

        $settings = ParkSetting::first();
        if ($settings) {
            $isOpen = ($settings->park_status ?? 'open') === 'open';
            $statusStr = $isOpen 
                ? "OPEN (Operating normally for all day and night visitors)" 
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

            $context .= "\n[OFFICIAL PARK SETTINGS & LIVE RATES (SOURCE OF TRUTH FROM DATABASE)]:\n"
                . "- Park Operational Status: {$statusStr}\n"
                . "- General Park Gate Hours: {$openTime} to {$closeTime}\n"
                . "- Daytime Session Hours: {$dayStart} - {$dayEnd}\n"
                . "  * Adult Entrance Fee: ₱{$dayAdult}\n"
                . "  * Child (12 & below) Entrance Fee: {$dayChildStr}\n"
                . "- Nighttime Session Hours: {$nightStart} - {$nightEnd}\n"
                . "  * Adult Entrance Fee: ₱{$nightAdult}\n"
                . "  * Child (12 & below) Entrance Fee: {$nightChildStr}\n"
                . "- Swimming Pool Access Fees:\n"
                . "  * Day Swim Pool: ₱{$dayPool} per person\n"
                . "  * Night Swim Pool: ₱{$nightPool} per person\n"
                . "- Brenda Mage Availability: {$brendaStatus}\n"
                . "- Official Contact Number: " . ($settings->contact_number ?: '0985-323-9532') . "\n"
                . "- Official Email: " . ($settings->email ?: 'parkhinaguan@gmail.com') . "\n";
        }

        // 1. RECENT ACTIVITY AUDIT TRAIL (LATEST 30)
        $recentLogs = ActivityLog::orderByDesc('created_at')->take(30)->get();
        $context .= "\n[AUDIT & RECENT ACTIVITIES]:\n";
        if ($recentLogs->isNotEmpty()) {
            $latestCheckin = $recentLogs->firstWhere('activity_type', 'check_in') ?: $recentLogs->firstWhere('activity_type', 'walkin_created');
            $latestCheckout = $recentLogs->firstWhere('activity_type', 'check_out');
            $latestExtension = $recentLogs->first(fn ($l) => in_array($l->activity_type, ['stay_extended', 'amenity_extended']));

            if ($latestCheckin) {
                $time = $latestCheckin->created_at ? $latestCheckin->created_at->format('M d, Y g:i A') : 'N/A';
                $context .= "★ MOST RECENT CHECK-IN: Reservation #{$latestCheckin->reservation_id} on {$time} by Staff '{$latestCheckin->actor_name}' ({$latestCheckin->description})\n";
            }
            if ($latestCheckout) {
                $time = $latestCheckout->created_at ? $latestCheckout->created_at->format('M d, Y g:i A') : 'N/A';
                $context .= "★ MOST RECENT CHECK-OUT: Reservation #{$latestCheckout->reservation_id} on {$time} by Staff '{$latestCheckout->actor_name}' ({$latestCheckout->description})\n";
            }
            if ($latestExtension) {
                $time = $latestExtension->created_at ? $latestExtension->created_at->format('M d, Y g:i A') : 'N/A';
                $context .= "★ MOST RECENT EXTENSION: Reservation #{$latestExtension->reservation_id} on {$time} by Staff '{$latestExtension->actor_name}' ({$latestExtension->description})\n";
            }

            $context .= "--- Recent Log Feed ---\n";
            foreach ($recentLogs->take(15) as $l) {
                $t = $l->created_at ? $l->created_at->format('M d g:i A') : 'N/A';
                $res = $l->reservation_id ? " [Res #{$l->reservation_id}]" : "";
                $context .= "- [{$t}]{$res} {$l->description} | By: {$l->actor_name} ({$l->actor_role})\n";
            }
        } else {
            $context .= "No activity logs recorded yet.\n";
        }

        // 2. CURRENTLY CHECKED-IN RESERVATIONS
        $checkedIn = Reservation::with(['reservationAmenities.amenity'])
            ->where('status', 'Checked In')
            ->orderByDesc('check_in')
            ->get();

        $context .= "\n[CURRENTLY CHECKED-IN ON SITE ({$checkedIn->count()} active reservations)]:\n";
        if ($checkedIn->isNotEmpty()) {
            foreach ($checkedIn as $cir) {
                $amNames = $cir->reservationAmenities->map(fn ($ra) => $ra->amenity?->amenities_name ?? 'Amenity')->implode(', ');
                $checkInTime = $cir->check_in ? Carbon::parse($cir->check_in)->format('M d g:i A') : 'N/A';
                $end = ($cir->end_date ?: $cir->reservation_date) . " [" . ($cir->end_slot ?: $cir->start_slot) . "]";
                $bal = $cir->remaining_balance > 0 ? " | ⚠️ Balance: ₱" . number_format($cir->remaining_balance, 2) : " | Paid";
                $context .= "- Reservation #{$cir->id}: {$cir->booker_name} | Checked In: {$checkInTime} | Depart: {$end} | Guests: {$cir->number_of_guests} | Paid: ₱" . number_format($cir->amount_paid, 2) . "{$bal} | Booked: " . ($amNames ?: 'None') . "\n";
            }
        } else {
            $context .= "No reservations currently checked in.\n";
        }

        // 3. RECENT 15 RESERVATIONS LEDGER
        $recentRes = Reservation::with(['reservationAmenities.amenity'])
            ->orderByDesc('id')
            ->take(15)
            ->get();

        $context .= "\n[RESERVATIONS LEDGER (LATEST 15)]:\n";
        foreach ($recentRes as $r) {
            $amList = $r->reservationAmenities->pluck('amenity.amenities_name')->filter()->implode(', ');
            $context .= "- Reservation #{$r->id}: {$r->booker_name} | Status: {$r->status} ({$r->reservation_type}) | Date: {$r->reservation_date} [{$r->start_slot}] | Paid: ₱" . number_format($r->amount_paid, 2) . ", Balance: ₱" . number_format($r->remaining_balance, 2) . " | Amenities: " . ($amList ?: 'None') . "\n";
        }

        // 4. STAFF ROSTER
        $staffAccounts = StaffAccount::all();
        $activeStaff = $staffAccounts->where('ban_status', false);
        $bannedStaff = $staffAccounts->where('ban_status', true);

        $context .= "\n[STAFF DIRECTORY]:\n"
            . "- Total Staff Accounts: {$staffAccounts->count()} (Active: {$activeStaff->count()}, Banned: {$bannedStaff->count()})\n";
        foreach ($staffAccounts as $sa) {
            $status = $sa->ban_status ? 'Banned' : 'Active';
            $context .= "  * Staff #{$sa->id}: {$sa->name} ({$sa->email}) - Status: {$status}\n";
        }

        // 5. EXECUTIVE FINANCIAL METRICS
        $allReservations = Reservation::all();
        $totalGross = $allReservations->sum('total_amount');
        $totalCollected = $allReservations->sum('amount_paid');
        $totalPendingBalance = $allReservations->sum('remaining_balance');

        $todayRes = Reservation::whereDate('reservation_date', $todayStr)->orWhereDate('created_at', $todayStr)->get();
        $thisWeekRes = Reservation::whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])->get();
        $thisMonthRes = Reservation::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->get();

        $onlineCollected = $allReservations->where('reservation_type', 'online')->sum('amount_paid');
        $walkinCollected = $allReservations->where('reservation_type', 'walk_in')->sum('amount_paid');

        $context .= "\n[EXECUTIVE FINANCIALS]:\n"
            . "- Total All-Time Collected: ₱" . number_format($totalCollected, 2) . " (Gross Expected: ₱" . number_format($totalGross, 2) . ")\n"
            . "- Total Pending Uncollected Balance: ₱" . number_format($totalPendingBalance, 2) . "\n"
            . "- Today's Sales: ₱" . number_format($todayRes->sum('amount_paid'), 2) . " | This Week: ₱" . number_format($thisWeekRes->sum('amount_paid'), 2) . " | This Month: ₱" . number_format($thisMonthRes->sum('amount_paid'), 2) . "\n"
            . "- Online Sales: ₱" . number_format($onlineCollected, 2) . " | Walk-In Sales: ₱" . number_format($walkinCollected, 2) . "\n";

        // 6. DEMOGRAPHICS MINING
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

        $context .= "\n[DEMOGRAPHICS DATA MINING]:\n"
            . "- Total Guests: {$allGuests->count()} | Kids (0-12): {$kids}, Teens (13-17): {$teens}, Adults (18-59): {$adults}, Seniors (60+): {$seniors}\n"
            . "- Gender: Females: {$females}, Males: {$males} | Locals: {$locals}, Foreigners: {$foreigners}\n";

        // 7. SPECIFIC DEEP SEARCH
        if (preg_match('/(\d+)/', $message, $numMatches)) {
            $entityId = (int) $numMatches[1];
            $specificRes = Reservation::with(['reservationAmenities.amenity', 'reservationGuests.customer'])->find($entityId);
            if ($specificRes) {
                $specGuests = $specificRes->reservationGuests->map(fn ($g) => $g->customer ? "{$g->customer->first_name} {$g->customer->last_name}" : 'Guest')->implode(', ');
                $specLogs = ActivityLog::where('reservation_id', $entityId)->orderBy('created_at')->get();
                $context .= "\n[AUDIT DETAILS FOR RESERVATION #{$entityId}]:\n"
                    . "- Booker: {$specificRes->booker_name} (Phone: {$specificRes->phone}) | Status: {$specificRes->status}\n"
                    . "- Schedule: {$specificRes->reservation_date} [{$specificRes->start_slot}] to " . ($specificRes->end_date ?: $specificRes->reservation_date) . " [" . ($specificRes->end_slot ?: $specificRes->start_slot) . "]\n"
                    . "- Financials: Total ₱" . number_format($specificRes->total_amount, 2) . ", Paid ₱" . number_format($specificRes->amount_paid, 2) . ", Balance ₱" . number_format($specificRes->remaining_balance, 2) . "\n"
                    . "- Guests: " . ($specGuests ?: 'None') . "\n";
                if ($specLogs->isNotEmpty()) {
                    $context .= "- History:\n";
                    foreach ($specLogs as $sl) {
                        $context .= "  * [" . ($sl->created_at ? $sl->created_at->format('M d g:i A') : 'N/A') . "] {$sl->title}: {$sl->description} (by {$sl->actor_name})\n";
                    }
                }
            }
        }

        // 8. OFFICIAL AMENITIES, CAPACITIES & RATES (FROM DATABASE)
        $amenities = Amenity::with('benefits')->get();
        if ($amenities->isNotEmpty()) {
            $context .= "\n[OFFICIAL AMENITIES, CAPACITIES & INCLUSIONS (FROM DATABASE)]:\n";
            foreach ($amenities as $am) {
                $status = $am->status ? 'ACTIVE' : 'INACTIVE';
                $benefit = $am->benefits;
                $freeEntrance = ($benefit && $benefit->free_entrance) ? 'YES (Free entrance included)' : 'NO (Regular entrance fees apply)';
                $freePool = ($benefit && $benefit->free_pool) ? 'YES (Free pool access included)' : 'NO (Separate pool fee required)';
                $aircon = ($benefit && $benefit->is_aircon) ? 'YES (Air-conditioned)' : 'NO (Open-air / Non-aircon)';
                $addHead = number_format((float) $am->additional_per_head, 2);

                $context .= "- {$am->amenities_name} [{$status}] (Capacity: {$am->minimum_capacity}-{$am->maximum_capacity} pax):\n"
                    . "  * Rates: Daytime: ₱" . number_format((float) $am->daytime_price, 2) . " | Nighttime: ₱" . number_format((float) $am->nighttime_price, 2) . " | Extra Head: ₱{$addHead}\n"
                    . "  * Inclusions: Free Entrance: {$freeEntrance} | Free Pool: {$freePool} | Air-conditioned: {$aircon}\n";
            }
        }

        // 9. OFFICIAL PARK RULES & GUIDELINES FROM DATABASE
        $rules = ParkRule::all();
        if ($rules->isNotEmpty()) {
            $context .= "\n[OFFICIAL PARK RULES & GUIDELINES (FROM DATABASE)]:\n";
            foreach ($rules as $r) {
                $context .= "- {$r->rule_name}: {$r->rule_descriptions}\n";
            }
        }

        // 10. ACTIVE PARK EVENTS & HAPPENINGS (FROM DATABASE)
        $events = ParkEvent::where('is_active', true)->orderBy('date')->get();
        if ($events->isNotEmpty()) {
            $context .= "\n[ACTIVE PARK EVENTS & HAPPENINGS (FROM DATABASE)]:\n";
            foreach ($events as $ev) {
                $dateStr = $ev->date ? Carbon::parse($ev->date)->format('M d, Y') : 'Date TBA';
                $dayStr = $ev->day ? " ({$ev->day})" : "";
                $timeStr = $ev->time ? " at {$ev->time}" : "";
                $context .= "- {$ev->title}: {$dateStr}{$dayStr}{$timeStr} - {$ev->event}\n";
            }
        }

        // 11. GUEST REVIEWS & RATINGS OVERVIEW
        $feedbackCount = Feedback::count();
        if ($feedbackCount > 0) {
            $avgStars = number_format((float) Feedback::avg('stars'), 1);
            $context .= "\n[GUEST REVIEWS & RATINGS (FROM DATABASE)]:\n"
                . "- Total Reviews: {$feedbackCount} | Average Rating: {$avgStars} / 5.0 stars\n";
        }

        return $context;
    }
}
