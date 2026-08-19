<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Amenity;
use App\Models\ChatbotMessage;
use App\Models\Customer;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\ReservationGuest;
use App\Models\StaffAccount;
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
            . "CONVERSATION STYLE & TONE GUIDELINES:\n"
            . "- Speak naturally in clear, professional, human-like sentences (like an intelligent operations manager briefing an executive).\n"
            . "- Answer the specific query directly in 1 to 3 natural, conversational sentences. Blend names, numbers, and dates smoothly into the explanation.\n"
            . "- Avoid stiff robotic outlines or dumping unrelated sections unless the administrator specifically asks for a full report or breakdown.\n"
            . "- Understand English, Tagalog, Bisaya, and Taglish naturally.\n"
            . "- DO NOT include any thinking process, reasoning steps, internal analysis, notes, or prefixes (e.g. NEVER output \"Here's a thinking process:\"). Deliver ONLY the natural human response.\n\n"
            . "CORE KNOWLEDGE & CAPABILITIES:\n"
            . "1. AUDIT & RECENT ACTIVITIES: Pinpoint who performed check-ins, checkouts, extensions, or account actions with exact reservation IDs, staff names, and timestamps.\n"
            . "2. EXECUTIVE FINANCIALS: Provide gross revenue, collected sales, outstanding uncollected balances, today/weekly/monthly revenue, and online vs walk-in breakdowns.\n"
            . "3. STAFF ROSTER: Report total staff, active/banned status, and staff member details.\n"
            . "4. DEMOGRAPHICS MINING: Report Kids (0-12), Teens (13-17), Adults (18-59), Seniors (60+), Gender (Female/Male), and Nationality counts.\n"
            . "5. RESERVATIONS & ON-SITE OCCUPANCY: Report checked-in reservations, departures, and look up any specific reservation by ID or name.\n\n"
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
                'max_tokens' => 350,
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
            $excReply = 'The admin assistant is temporarily unavailable. Error: ' . $e->getMessage();
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
     * Clean raw AI response to strip thinking processes, reasoning tags, and bot prefixes.
     */
    private function cleanChatbotReply(string $reply): string
    {
        // 1. Strip <think>...</think> tags if model outputs raw reasoning tokens
        $reply = preg_replace('/<think>.*?<\/think>/is', '', $reply);

        // 2. Strip "Here's a thinking process: ... \n\n" or "Thinking Process: ... \n\n"
        $reply = preg_replace('/(?:^|\n)\s*(?:Here\'?s\s+(?:a\s+)?thinking\s+process|Thinking\s+Process|Thought\s+Process):.*?(?:\r?\n\r?\n|$)/is', '', $reply);

        // 3. Strip leading bot prefix
        $reply = preg_replace('/^(?:HinaguanBot|StaffBot|AdminBot|GuestBot|Bot|Assistant):\s*/i', '', trim($reply));

        return trim($reply);
    }

    private function getAdminContext(string $message): string
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
