<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\ChatbotMessage;
use App\Models\Customer;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\ReservationGuest;
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
            . "CONVERSATION STYLE & TONE GUIDELINES:\n"
            . "- Speak naturally in friendly, human-like sentences (like a helpful coworker chatting with the staff member).\n"
            . "- Answer the specific question directly in 1 to 3 clear, flowing sentences. Avoid robotic outlines, stiff headers, or unnecessary data dumps unless the user specifically asks for a full list or table.\n"
            . "- When answering about reservations or checkouts, summarize the answer warmly and clearly in natural conversational English (e.g., 'Right now, there are no checkouts due today. The nearest upcoming checkouts are Reservation #6 under Ashlyn Famador and #4 under Tryyy, both scheduled to check out on August 31.').\n"
            . "- Understand English, Tagalog, Bisaya, and Taglish naturally.\n"
            . "- DO NOT include any thinking process, reasoning steps, internal analysis, notes, or prefixes (e.g. NEVER output \"Here's a thinking process:\"). Deliver ONLY the natural human response.\n\n"
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
                'max_tokens' => 350,
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

    private function getStaffContext(string $message): string
    {
        $context = '';
        $now = now();
        $todayStr = $now->toDateString();
        $currentTimeStr = $now->format('F j, Y - g:i A');

        $context .= "Current Date/Time: {$currentTimeStr}\n";

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
