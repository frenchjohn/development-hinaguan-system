<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
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

        // Security Guardrail: Staff chatbot CANNOT access staff account passwords or admin credentials
        $restrictedForStaff = [
            'staff password', 'admin password', 'all staff password',
            'admin credentials', 'database credentials', 'api key', 'secret key',
            'create admin', 'ban admin', 'delete admin'
        ];

        foreach ($restrictedForStaff as $term) {
            if (str_contains($msgLower, $term)) {
                return response()->json([
                    'reply' => "I am the Staff Assistant. I cannot display or modify staff account credentials, passwords, or admin security accounts. Please consult the Park Administrator for system security management."
                ]);
            }
        }

        // Check for completely off-topic questions
        $forbiddenTopics = ['write python code', 'solve math equation', 'celebrity gossip', 'astrology horoscope', 'cryptocurrency trading'];
        foreach ($forbiddenTopics as $topic) {
            if (stripos($userMessage, $topic) !== false) {
                return response()->json([
                    'reply' => "I can only assist with Hinaguan Nature Park staff operations, guest reservations, check-ins, checkouts, and demographic data mining."
                ]);
            }
        }

        $apiKey = env('OPENROUTER_API_KEY');
        $model = $request->input('model', 'openrouter/free');

        if (!$apiKey) {
            return response()->json([
                'reply' => 'The staff chatbot service is currently offline. Please check system configuration.'
            ], 500);
        }

        $staffContext = $this->getStaffContext($userMessage);

        $systemPrompt = "You are HinaguanBot Staff Assistant, the operations & data mining copilot for staff at Hinaguan Nature Park.\n"
            . "CRITICAL INSTRUCTIONS:\n"
            . "- BE DIRECT, CONCISE, AND ON-POINT. Answer in 2 to 4 sentences or compact bullet points.\n"
            . "- DO NOT use filler conversational text (e.g. 'I would be happy to help!', 'Let me know if you need anything else!'). Deliver the exact facts immediately to minimize token cost.\n"
            . "- CAPABILITIES:\n"
            . "  1. OPERATIONS: Identify checked-in guests, departures/due checkouts today, countdowns, and upcoming arrivals.\n"
            . "  2. CASHIER & BALANCES: Report collected sales and highlight any checked-in reservations with unpaid remaining balances.\n"
            . "  3. WALK-IN PRICE CALCULATOR: Instantly calculate total walk-in cost for staff (Adults × Entrance + Kids × Entrance + Pool + Amenity).\n"
            . "  4. DEMOGRAPHICS MINING: Report guest counts for Kids (0-12), Teens (13-17), Adults (18-59), Seniors (60+), Gender (Female vs Male), and Nationality.\n"
            . "  5. PARK RATES: Daytime Entrance: Adult ₱70, Child ₱50 | Nighttime Entrance: Adult ₱100, Child ₱70 | Pool: Day ₱100, Night ₱150.\n"
            . "  6. PROCEDURES: Explain stay extensions, mid-stay amenity additions, and checkout steps in the staff portal.\n\n"
            . "=== LIVE STAFF OPERATIONS & ANALYTICS CONTEXT ===\n"
            . $staffContext;

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
                'X-Title' => 'Hinaguan Nature Park Staff Portal',
            ])->post("https://openrouter.ai/api/v1/chat/completions", [
                'model' => $model,
                'messages' => $messagesPayload,
                'max_tokens' => 350,
                'temperature' => 0.3,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['choices'][0]['message']['content'] ?? 'I could not process your request at this time.';
                $reply = str_replace(['HinaguanBot:', 'Bot:', 'StaffBot:'], '', $reply);
                $reply = trim($reply);

                return response()->json(['reply' => $reply]);
            } else {
                Log::error('Staff Chatbot OpenRouter Error: ' . $response->body());
                return response()->json([
                    'reply' => 'The staff assistant service encountered an error. Please try again shortly.'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Staff Chatbot Exception: ' . $e->getMessage());
            return response()->json([
                'reply' => 'The staff assistant is temporarily unavailable. Error: ' . $e->getMessage()
            ], 500);
        }
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
