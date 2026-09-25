<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AdminAccount;
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
use App\Models\StaffAccount;
use App\Models\UserActivityRead;
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

        $systemPrompt = "You are HinaguanBot, the intelligent, highly capable, and professional Executive Intelligence Assistant for administrators at Hinaguan Nature Park in Jasaan, Misamis Oriental.\n\n"
            . "CRITICAL OUTPUT RULES (STRICTLY ENFORCED):\n"
            . "- OUTPUT ONLY YOUR DIRECT CONVERSATIONAL BRIEFING to the administrator. Never output reasoning steps, thinking processes, chain-of-thought, scratchpads, or draft prefixes.\n"
            . "- NEVER prefix your response with 'Draft:', 'Response:', 'Answer:', or 'HinaguanBot:'. Start directly with your briefing.\n"
            . "- Maintain an articulate, executive, and warm tone (1 to 3 concise flowing sentences for standard questions, or structured bulleted reports when detailed breakdowns are requested).\n"
            . "- STRICT DATABASE ACCURACY (ZERO HALLUCINATION): Always quote revenue figures, staff records, guest counts, rates, and audit logs EXACTLY as provided in the LIVE SYSTEM & DATABASE CONTEXT below. Never guess or fabricate information.\n\n"
            . "DATABASE COMPREHENSION & PARK LOGIC:\n"
            . "You have comprehensive visibility across all park database tables (activity_logs, admin_accounts, amenities, amenities_benefits, chatbot_messages, customers, daily_weather_shift_logs, feedbacks, park_activities, park_events, park_rules, park_settings, reschedule_requests, reservations, reservation_amenities, reservation_charges, reservation_entrance_fees, reservation_guests, sms_notifications, staff_accounts, user_activity_reads):\n"
            . "1. EXECUTIVE FINANCIALS: Real-time calculation of gross revenue, total collected revenue, unpaid balances, online vs walk-in splits, and daily/weekly/monthly trends.\n"
            . "2. AUDIT TRAIL (activity_logs): Detailed records of who (staff/admin) performed check-ins, checkouts, walk-ins, cancellations, extensions, or account modifications with exact timestamps.\n"
            . "3. STAFF ROSTER (staff_accounts & admin_accounts): Active vs. banned staff, employee directory, and admin management accounts (passwords and hashes are strictly safeguarded and never exposed).\n"
            . "4. RESERVATIONS & OCCUPANCY: Status lifecycles ('pending', 'Confirmed', 'Checked In', 'Checked Out', 'Cancelled'), day/night slots, multi-day continuous stays, guest lists, and booked amenities.\n"
            . "5. RESCHEDULE REQUESTS (reschedule_requests): Date change requests from guests with original date, requested date, status (pending/approved/declined), and reasons.\n"
            . "6. EXTRA CHARGES & PENALTIES (reservation_charges): Additional fees added for extra mattresses, damages, corkage, late checkouts, or extra hours.\n"
            . "7. ANNOUNCEMENTS & SMS (sms_notifications): SMS broadcasts sent to visitors via PhilSMS with recipient counts, categories, and delivery statuses.\n"
            . "8. DEMOGRAPHICS MINING: Real-time demographic distribution (Kids 0-12, Teens 13-17, Adults 18-59, Seniors 60+, Gender, Foreigners vs Locals).\n"
            . "9. AMENITY INCLUSIONS: Cottages & Payags do NOT include free entrance or free pool access; A-Houses include FREE entrance and pool access for 2; Function Hall includes free group entrance & pool.\n"
            . "10. PARK SETTINGS, RULES & EVENTS: Operating hours, pool/gate fees, Brenda Mage availability, and scheduled events.\n\n"
            . "LINGUISTIC FLUENCY (TYPOS, GRAMMAR & MULTILINGUAL):\n"
            . "- Effortlessly interpret misspelled words, typographical errors, and phonetic spelling (e.g., 'resched', 'boking', 'cotag', 'pyag', 'chek in', 'chekout', 'balans', 'kita', 'demografic').\n"
            . "- Forgive grammatical errors, colloquial expressions, and sentence fragments seamlessly.\n"
            . "- Understand Bisaya/Cebuano, Tagalog, Taglish, and English naturally.\n"
            . "- MATCH THE USER'S LANGUAGE:\n"
            . "  * If the administrator asks in Bisaya / Cebuano (e.g., 'pila tanan gross sales', 'kinsa ang staff nga naka-ban', 'naa bay nagpa-resched', 'pila kabuok nag check out'), reply naturally and professionally in Bisaya!\n"
            . "  * If the administrator asks in Tagalog / Taglish (e.g., 'magkano kabuuang revenue', 'sino ang mga staff', 'may mga pending ba na resched', 'paki-check audit logs'), reply naturally and professionally in Tagalog/Taglish!\n"
            . "  * If the administrator asks in English, reply in professional executive English!\n\n"
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

        // BASELINE: Official Park Settings & Live Rates from park_settings table
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

            $context .= "\n[OFFICIAL PARK SETTINGS & LIVE RATES (park_settings)]:\n"
                . "- Park Operational Status: {$statusStr}\n"
                . "- Gate Hours: {$openTime} to {$closeTime}\n"
                . "- Daytime Session Hours: {$dayStart} - {$dayEnd} | Adult: ₱{$dayAdult}, Child: {$dayChildStr}\n"
                . "- Nighttime Session Hours: {$nightStart} - {$nightEnd} | Adult: ₱{$nightAdult}, Child: {$nightChildStr}\n"
                . "- Pool Access Rates: Day: ₱{$dayPool}/person, Night: ₱{$nightPool}/person\n"
                . "- Brenda Mage Presence: {$brendaStatus}\n"
                . "- Official Contact: " . ($settings->contact_number ?: '0985-323-9532') . " | Email: " . ($settings->email ?: 'parkhinaguan@gmail.com') . "\n";
        }

        // EXECUTIVE OPERATIONAL & FINANCIAL KPI SNAPSHOT (Always present)
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
        $totalGross = (float) Reservation::sum('total_amount');
        $totalCollected = (float) Reservation::sum('amount_paid');
        $totalUnpaidBalance = (float) Reservation::sum('remaining_balance');
        $todayRevenue = (float) Reservation::whereDate('reservation_date', $todayStr)
            ->orWhereDate('created_at', $todayStr)
            ->sum('amount_paid');

        $context .= "\n[EXECUTIVE KPI SNAPSHOT]:\n"
            . "- Active Checked-in Reservations: {$checkedInCount} ({$totalCheckedInGuests} guests currently on site)\n"
            . "- Due for Departure Today: {$dueCheckoutsCount} reservations\n"
            . "- Pending Online Bookings: {$pendingCount} awaiting confirmation\n"
            . "- Financial Status: Collected Today: ₱" . number_format($todayRevenue, 2) . " | Total All-Time Collected: ₱" . number_format($totalCollected, 2) . " (Gross: ₱" . number_format($totalGross, 2) . ", Outstanding Balance: ₱" . number_format($totalUnpaidBalance, 2) . ")\n";

        // INTELLIGENT TOPIC DETECTION FOR ADMIN
        $topics = $this->detectAdminTopics($message);

        // 1. SPECIFIC ENTITY SEARCH (Reservation, Customer, or Staff/Admin account by ID or Name)
        if (!empty($topics['specific_entity']) || !empty($topics['search_terms'])) {
            $matchedReservations = collect();

            if (!empty($topics['specific_id'])) {
                $foundRes = Reservation::with(['reservationAmenities.amenity', 'reservationGuests.customer', 'entranceFee', 'reservationCharges', 'rescheduleRequests'])
                    ->find($topics['specific_id']);
                if ($foundRes) {
                    $matchedReservations->push($foundRes);
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
                    $logs = ActivityLog::where('reservation_id', $r->id)->orderBy('created_at')->get();

                    $context .= "- Reservation #{$r->id}: Booker {$r->booker_name} (Phone: {$r->phone}, Email: {$r->email})\n"
                        . "  * Status: {$r->status} ({$r->reservation_type}) | Headcount: {$r->number_of_guests}\n"
                        . "  * Stay Schedule: {$r->reservation_date} [{$r->start_slot}] to " . ($r->end_date ?: $r->reservation_date) . " [" . ($r->end_slot ?: $r->start_slot) . "]\n"
                        . "  * Financials: Total: ₱" . number_format($r->total_amount, 2) . ", Paid: ₱" . number_format($r->amount_paid, 2) . ", Balance Due: ₱" . number_format($r->remaining_balance, 2) . " [{$r->payment_status}, {$r->payment_method}]\n"
                        . "  * Booked Amenities: " . ($ams ?: 'None') . "\n"
                        . "  * Registered Guests: " . ($guests ?: 'None recorded') . "\n";
                    if ($charges) {
                        $context .= "  * Extra Charges: {$charges}\n";
                    }
                    if ($rescheds) {
                        $context .= "  * Reschedule Requests: {$rescheds}\n";
                    }
                    if ($logs->isNotEmpty()) {
                        $context .= "  * Audit History: " . $logs->map(fn ($l) => "[" . ($l->created_at ? $l->created_at->format('M d g:i A') : 'N/A') . "] {$l->description} by {$l->actor_name}")->implode(' | ') . "\n";
                    }
                }
            }
        }

        // 2. STAFF ROSTER & ADMIN ACCOUNTS (staff_accounts & admin_accounts)
        if (!empty($topics['staff_roster'])) {
            $staffAccounts = StaffAccount::all();
            $adminAccounts = AdminAccount::all();

            $activeStaff = $staffAccounts->where('ban_status', false);
            $bannedStaff = $staffAccounts->where('ban_status', true);

            $context .= "\n[CONNECTED DATABASE RECORDS - STAFF & ADMIN ROSTER (staff_accounts, admin_accounts)]:\n"
                . "- Staff Summary: {$staffAccounts->count()} accounts (Active: {$activeStaff->count()}, Banned: {$bannedStaff->count()})\n";
            foreach ($staffAccounts as $sa) {
                $status = $sa->ban_status ? 'BANNED' : 'ACTIVE';
                $created = $sa->created_at ? $sa->created_at->format('M d, Y') : 'N/A';
                $context .= "  * Staff #{$sa->id}: {$sa->name} ({$sa->email}) - Status: {$status} (Joined: {$created})\n";
            }
            $context .= "- Admin Accounts ({$adminAccounts->count()} total):\n";
            foreach ($adminAccounts as $aa) {
                $recovery = $aa->recovery_email ? " (Recovery: {$aa->recovery_email})" : "";
                $context .= "  * Admin #{$aa->id}: {$aa->name} ({$aa->email}){$recovery}\n";
            }
        }

        // 3. EXECUTIVE FINANCIALS & SALES TRENDS (reservations)
        if (!empty($topics['sales'])) {
            $allRes = Reservation::all();
            $todayRes = Reservation::whereDate('reservation_date', $todayStr)->orWhereDate('created_at', $todayStr)->get();
            $thisWeekRes = Reservation::whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])->get();
            $thisMonthRes = Reservation::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->get();

            $onlineCollected = $allRes->where('reservation_type', 'online')->sum('amount_paid');
            $walkinCollected = $allRes->where('reservation_type', 'walk_in')->sum('amount_paid');

            $context .= "\n[CONNECTED DATABASE RECORDS - EXECUTIVE FINANCIALS (reservations)]:\n"
                . "- Total Gross Revenue: ₱" . number_format($allRes->sum('total_amount'), 2) . "\n"
                . "- Total Collected Collections: ₱" . number_format($allRes->sum('amount_paid'), 2) . "\n"
                . "- Total Uncollected Balance: ₱" . number_format($allRes->sum('remaining_balance'), 2) . "\n"
                . "- Sales Breakdown: Today: ₱" . number_format($todayRes->sum('amount_paid'), 2) . " | This Week: ₱" . number_format($thisWeekRes->sum('amount_paid'), 2) . " | This Month: ₱" . number_format($thisMonthRes->sum('amount_paid'), 2) . "\n"
                . "- Channel Breakdown: Online Portal: ₱" . number_format($onlineCollected, 2) . " | Front Desk Walk-In: ₱" . number_format($walkinCollected, 2) . "\n";
        }

        // 4. ACTIVITY LOGS AUDIT TRAIL (activity_logs)
        if (!empty($topics['activity_logs']) || empty($topics['is_specific'])) {
            $recentLogs = ActivityLog::orderByDesc('created_at')->take(20)->get();
            $context .= "\n[CONNECTED DATABASE RECORDS - RECENT ACTIVITY AUDIT TRAIL (activity_logs)]:\n";
            if ($recentLogs->isNotEmpty()) {
                $latestCheckin = $recentLogs->firstWhere('activity_type', 'check_in') ?: $recentLogs->firstWhere('activity_type', 'walkin_created');
                $latestCheckout = $recentLogs->firstWhere('activity_type', 'check_out');
                $latestExtension = $recentLogs->first(fn ($l) => in_array($l->activity_type, ['stay_extended', 'amenity_extended']));

                if ($latestCheckin) {
                    $context .= "★ Latest Check-in: Res #{$latestCheckin->reservation_id} on " . ($latestCheckin->created_at?->format('M d, g:i A') ?? 'N/A') . " by {$latestCheckin->actor_name}\n";
                }
                if ($latestCheckout) {
                    $context .= "★ Latest Check-out: Res #{$latestCheckout->reservation_id} on " . ($latestCheckout->created_at?->format('M d, g:i A') ?? 'N/A') . " by {$latestCheckout->actor_name}\n";
                }
                if ($latestExtension) {
                    $context .= "★ Latest Stay Extension: Res #{$latestExtension->reservation_id} on " . ($latestExtension->created_at?->format('M d, g:i A') ?? 'N/A') . " by {$latestExtension->actor_name}\n";
                }

                $context .= "Recent Log Feed:\n";
                foreach ($recentLogs->take(12) as $l) {
                    $t = $l->created_at ? $l->created_at->format('M d g:i A') : 'N/A';
                    $res = $l->reservation_id ? " [Res #{$l->reservation_id}]" : "";
                    $context .= "- [{$t}]{$res} {$l->description} | By: {$l->actor_name} ({$l->actor_role})\n";
                }
            } else {
                $context .= "No activity logs recorded yet.\n";
            }
        }

        // 5. RESCHEDULE REQUESTS (reschedule_requests)
        if (!empty($topics['reschedules'])) {
            $reschedRequests = RescheduleRequest::with(['reservation', 'approver'])->orderByDesc('id')->take(10)->get();
            $context .= "\n[CONNECTED DATABASE RECORDS - RESCHEDULE REQUESTS (reschedule_requests)]:\n";
            if ($reschedRequests->isNotEmpty()) {
                foreach ($reschedRequests as $rq) {
                    $booker = $rq->reservation?->booker_name ?? 'Guest';
                    $approver = $rq->approver?->name ? " (Handled by: {$rq->approver->name})" : "";
                    $context .= "- Request #{$rq->id} for Res #{$rq->reservation_id} ({$booker}): {$rq->original_date} -> {$rq->requested_date} | Status: {$rq->status}{$approver} | Reason: " . ($rq->reason ?: 'None') . "\n";
                }
            } else {
                $context .= "No reschedule requests in database.\n";
            }
        }

        // 6. EXTRA CHARGES & PENALTIES (reservation_charges)
        if (!empty($topics['charges'])) {
            $charges = ReservationCharge::with(['reservation', 'amenity'])->orderByDesc('id')->take(10)->get();
            $context .= "\n[CONNECTED DATABASE RECORDS - EXTRA CHARGES (reservation_charges)]:\n";
            if ($charges->isNotEmpty()) {
                foreach ($charges as $c) {
                    $booker = $c->reservation?->booker_name ?? 'Res #' . $c->reservation_id;
                    $am = $c->amenity?->amenities_name ? " [{$c->amenity->amenities_name}]" : "";
                    $context .= "- Charge #{$c->id}: ₱" . number_format($c->amount, 2) . " ({$c->charge_type}) for {$booker}{$am} - {$c->description} (Status: {$c->status})\n";
                }
            } else {
                $context .= "No additional charges recorded.\n";
            }
        }

        // 7. ANNOUNCEMENTS & SMS (sms_notifications)
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

        // 8. DEMOGRAPHICS MINING (customers & reservation_guests)
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

            $context .= "\n[CONNECTED DATABASE RECORDS - DEMOGRAPHICS DATA MINING (customers & reservation_guests)]:\n"
                . "- Total Guests Profiled: {$allGuests->count()}\n"
                . "- Age Brackets: Kids (0-12): {$kids}, Teens (13-17): {$teens}, Adults (18-59): {$adults}, Seniors (60+): {$seniors}\n"
                . "- Gender: Female: {$females}, Male: {$males} | Origin: Locals: {$locals}, Foreign Visitors: {$foreigners}\n";
        }

        // 9. CHECK-INS & ACTIVE OCCUPANCY
        if (!empty($topics['checkins']) || empty($topics['is_specific'])) {
            $checkedIn = Reservation::with(['reservationAmenities.amenity'])
                ->where('status', 'Checked In')
                ->orderByDesc('check_in')
                ->get();

            $context .= "\n[CONNECTED DATABASE RECORDS - ACTIVE CHECKED-IN GUESTS ({$checkedIn->count()} reservations)]:\n";
            if ($checkedIn->isNotEmpty()) {
                foreach ($checkedIn as $cir) {
                    $amNames = $cir->reservationAmenities->map(fn ($ra) => $ra->amenity?->amenities_name ?? 'Amenity')->implode(', ');
                    $checkInTime = $cir->check_in ? Carbon::parse($cir->check_in)->format('M d g:i A') : 'N/A';
                    $end = ($cir->end_date ?: $cir->reservation_date) . " [" . ($cir->end_slot ?: $cir->start_slot) . "]";
                    $bal = $cir->remaining_balance > 0 ? " | ⚠️ Balance: ₱" . number_format($cir->remaining_balance, 2) : " | Paid";
                    $context .= "- Res #{$cir->id}: {$cir->booker_name} | Checked In: {$checkInTime} | Depart: {$end} | Headcount: {$cir->number_of_guests} | Paid: ₱" . number_format($cir->amount_paid, 2) . "{$bal} | Booked: " . ($amNames ?: 'None') . "\n";
                }
            } else {
                $context .= "No reservations currently checked in.\n";
            }
        }

        // 10. CHECKOUTS & DEPARTURES
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
                    $bal = $dep->remaining_balance > 0 ? " [⚠️ Balance to collect: ₱" . number_format($dep->remaining_balance, 2) . "]" : " [Paid]";
                    $context .= "- Res #{$dep->id}: {$dep->booker_name} (Slot: {$slot} -> Checkout: {$expectedTime}){$bal}\n";
                }
            } else {
                $context .= "No reservations due for departure today.\n";
            }
        }

        // 11. AMENITIES, CAPACITIES & RATES (amenities & amenities_benefits)
        if (!empty($topics['amenities']) || empty($topics['is_specific'])) {
            $amenities = Amenity::with('benefits')->get();
            $context .= "\n[CONNECTED DATABASE RECORDS - AMENITIES & RATES (amenities)]:\n";
            foreach ($amenities as $am) {
                $status = $am->status ? 'ACTIVE' : 'INACTIVE';
                $b = $am->benefits;
                $freeEnt = ($b && $b->free_entrance) ? 'YES (Free entrance included)' : 'NO (Regular entrance fees apply)';
                $freePool = ($b && $b->free_pool) ? 'YES (Free pool included)' : 'NO (Pool fees apply)';
                $aircon = ($b && $b->is_aircon) ? 'YES (Air-conditioned)' : 'NO (Open-air / Non-aircon)';
                $addHead = number_format((float) $am->additional_per_head, 2);

                $minCap = !empty($am->minimum_capacity) ? (int) $am->minimum_capacity : 1;
                $maxCap = !empty($am->maximum_capacity) ? (int) $am->maximum_capacity : $minCap;
                $cap = ($minCap === $maxCap) ? "{$minCap} pax" : "{$minCap} to {$maxCap} pax";

                $context .= "- {$am->amenities_name} [{$status}] (Capacity: {$cap}): Day: ₱" . number_format((float) $am->daytime_price, 2) . " | Night: ₱" . number_format((float) $am->nighttime_price, 2) . " | Extra Head: ₱{$addHead} | Free Ent: {$freeEnt} | Free Pool: {$freePool} | Aircon: {$aircon}\n";
            }
        }

        // 12. PARK RULES (park_rules)
        if (!empty($topics['rules'])) {
            $rules = ParkRule::all();
            $context .= "\n[CONNECTED DATABASE RECORDS - OFFICIAL PARK RULES (park_rules)]:\n";
            foreach ($rules as $r) {
                $context .= "- {$r->rule_name}: {$r->rule_descriptions}\n";
            }
        }

        // 13. PARK EVENTS (park_events)
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

        // 14. PARK ACTIVITIES (park_activities)
        if (!empty($topics['activities'])) {
            $activities = ParkActivity::all();
            $context .= "\n[CONNECTED DATABASE RECORDS - PARK ACTIVITIES (park_activities)]:\n";
            if ($activities->isNotEmpty()) {
                foreach ($activities as $act) {
                    $context .= "- {$act->activity}: {$act->description}\n";
                }
            } else {
                $context .= "Recreational activities include natural river swimming, pool swimming, photo shoots, and family dining.\n";
            }
        }

        // 15. REVIEWS & FEEDBACK (feedbacks)
        if (!empty($topics['feedback'])) {
            $feedbacks = Feedback::latest()->take(6)->get();
            $avgStars = Feedback::avg('stars');
            $context .= "\n[CONNECTED DATABASE RECORDS - REVIEWS & RATINGS (feedbacks)]:\n"
                . "- Average Rating: " . number_format((float) $avgStars, 1) . " / 5.0 stars (Total: " . Feedback::count() . " reviews)\n";
            foreach ($feedbacks as $fb) {
                $name = $fb->is_anonymous ? 'Anonymous Guest' : $fb->full_name;
                $context .= "- [{$fb->stars} Stars] {$name}: \"{$fb->description}\" (Replied: " . ($fb->replied ? 'Yes' : 'No') . ")\n";
            }
        }

        // 16. WEATHER SHIFT LOGS (daily_weather_shift_logs & WeatherService)
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

        return $context;
    }

    /**
     * Detect specific topics, IDs, and search terms from the admin query.
     * Robustly tolerates misspellings, colloquialisms, Taglish, Tagalog, and Bisaya.
     */
    private function detectAdminTopics(string $message): array
    {
        $msgLower = mb_strtolower(trim($message));
        $topics = [
            'is_specific' => false,
            'specific_id' => null,
            'search_terms' => [],
            'staff_roster' => false,
            'sales' => false,
            'activity_logs' => false,
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
        ];

        // 1. Extract possible ID
        if (preg_match('/(?:#|\bres(?:ervation)?\s*#?|\bid\s*#?)\s*(\d+)/i', $msgLower, $matches)) {
            $topics['specific_id'] = (int) $matches[1];
            $topics['specific_entity'] = true;
            $topics['is_specific'] = true;
        } elseif (preg_match('/\b\d{1,6}\b/', $msgLower, $matches)) {
            $topics['specific_id'] = (int) $matches[0];
            $topics['specific_entity'] = true;
            $topics['is_specific'] = true;
        }

        // 2. Extract potential names or search words
        $stopWords = ['the', 'and', 'for', 'are', 'what', 'who', 'how', 'pila', 'kinsa', 'unsa', 'naa', 'karon', 'kaha', 'ba', 'nga', 'ang', 'sa', 'og', 'ug', 'sino', 'ano', 'bakit', 'meron', 'wala', 'mga', 'bang', 'may', 'po', 'ngayon', 'today', 'check', 'show', 'give', 'list', 'park', 'hinaguan'];
        $words = preg_split('/[\s,\.\?!;:]+/', $msgLower);
        foreach ($words as $w) {
            $w = trim($w);
            if (mb_strlen($w) >= 3 && !in_array($w, $stopWords, true) && !is_numeric($w)) {
                if (Reservation::where('booker_name', 'like', "%{$w}%")->exists() || Customer::where('first_name', 'like', "%{$w}%")->orWhere('last_name', 'like', "%{$w}%")->exists() || StaffAccount::where('name', 'like', "%{$w}%")->exists()) {
                    $topics['search_terms'][] = $w;
                    $topics['specific_entity'] = true;
                    $topics['is_specific'] = true;
                }
            }
        }

        // 3. Staff Roster & Admin Accounts
        if (preg_match('/staff|employee|roster|bantay|trabahador|banned|active\s*staff|admin\s*account/i', $msgLower)) {
            $topics['staff_roster'] = true;
            $topics['is_specific'] = true;
        }

        // 4. Sales & Executive Financials
        if (preg_match('/sale|revenue|financial|income|halin|kita|gross|koleksyon|collection|total\s*bayad|cashier|walk[\s-]?in\s*sales|online\s*sales/i', $msgLower)) {
            $topics['sales'] = true;
            $topics['is_specific'] = true;
        }

        // 5. Activity Logs & Audit Trail
        if (preg_match('/audit|log|history|kinsa\s*nag|sino\s*nag|who\s*did|who\s*checked|who\s*approved|recent\s*action|gi[\s-]?update|gi[\s-]?usab|record/i', $msgLower)) {
            $topics['activity_logs'] = true;
            $topics['is_specific'] = true;
        }

        // 6. Reschedules / Date changes
        if (preg_match('/resched|reched|rebook|balhin|ilis|lipat|palit|move\s*date|postpone|change\s*date|bag[\s-]?o.*petsa/i', $msgLower)) {
            $topics['reschedules'] = true;
            $topics['is_specific'] = true;
        }

        // 7. Charges, Damages, Balances, Unpaid
        if (preg_match('/charge|penalty|penalties|damage|damages|mattress|corkage|korkage|guba|bayranan|utang|unpaid|kuwang|kulang|balance|balanse|remaining|bayad/i', $msgLower)) {
            $topics['charges'] = true;
            $topics['is_specific'] = true;
        }

        // 8. Check-ins, Headcount, Occupancy
        if (preg_match('/check[\s-]?in|sulod|loob|pumasok|nisulod|nisud|active\s*guest|headcount|occupancy|present|on[\s-]?site/i', $msgLower)) {
            $topics['checkins'] = true;
            $topics['is_specific'] = true;
        }

        // 9. Checkouts, Departures
        if (preg_match('/check[\s-]?out|chekot|chckout|departure|gawas|nigawas|lumabas|alis|uwian|due|countdown/i', $msgLower)) {
            $topics['checkouts'] = true;
            $topics['is_specific'] = true;
        }

        // 10. Demographics & Age / Gender Breakdown
        if (preg_match('/demograph|demograf|bata|kid|child|teen|adult|senior|tigulang|gender|lalaki|lalake|babae|babaye|male|female|foreigner|dayo|local|lokal|edad|age/i', $msgLower)) {
            $topics['demographics'] = true;
            $topics['is_specific'] = true;
        }

        // 11. Amenities, Capacities & Rates
        if (preg_match('/amenit|cottag|cotag|payag|pyag|a[\s-]?house|ahouse|function\s*hall|hall|pool|swim|ligo|langoy|aircon|capacity|kapasidad|inclusions|libre|free|bakante|availab|presyo|rate|pila|tagpila|magkano|abang|rent/i', $msgLower)) {
            $topics['amenities'] = true;
            $topics['is_specific'] = true;
        }

        // 12. Rules & Guidelines
        if (preg_match('/rule|policy|policies|patakaran|balaod|bawal|pwede|allowed|prohibit|pet|iro|aso|smok|panigarilyo|inom|liquor|alcohol|curfew|attire|swimwear|grill/i', $msgLower)) {
            $topics['rules'] = true;
            $topics['is_specific'] = true;
        }

        // 13. Events & Celebrations
        if (preg_match('/event|happen|okasyon|pista|fiesta|celebrat|concert|party|kalendaryo|holiday/i', $msgLower)) {
            $topics['events'] = true;
            $topics['is_specific'] = true;
        }

        // 14. Recreational Activities
        if (preg_match('/activit|lingaw|buhaton|kayak|sakayan|hike|hiking|trail|trek|nature\s*walk|photo|pictorial|picture|attraction/i', $msgLower)) {
            $topics['activities'] = true;
            $topics['is_specific'] = true;
        }

        // 15. Reviews & Feedbacks
        if (preg_match('/feedback|review|rating|star|reklamo|complaint|komento|comment|ingon|satisfaction/i', $msgLower)) {
            $topics['feedback'] = true;
            $topics['is_specific'] = true;
        }

        // 16. Weather & Forecast
        if (preg_match('/weather|panahon|uwan|ulan|rain|init|sunny|init\s*kaayo|bagyo|storm|temp|temperature|forecast|shift\s*log/i', $msgLower)) {
            $topics['weather'] = true;
            $topics['is_specific'] = true;
        }

        // 17. Announcements & SMS
        if (preg_match('/announc|sms|text|blast|broadcast|pahibalo|anunsyo|notif/i', $msgLower)) {
            $topics['announcements'] = true;
            $topics['is_specific'] = true;
        }

        return $topics;
    }
}
