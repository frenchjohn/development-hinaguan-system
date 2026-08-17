<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
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

        // Security / Privacy Guardrail: Strictly block confidential sales, customer data, staff accounts
        $sensitiveTerms = [
            'revenue', 'financial', 'sales', 'profit', 'total earned', 'total income',
            'staff account', 'staff password', 'admin password', 'admin account', 'staff list', 'employee list',
            'customer list', 'guest list', 'customer phone', 'customer email', 'who booked', 'who is staying',
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

        $systemPrompt = "You are HinaguanBot, the official guest concierge for Hinaguan Nature Park in Jasaan, Misamis Oriental.\n"
            . "CRITICAL INSTRUCTIONS:\n"
            . "- BE DIRECT, CONCISE, AND ON-POINT. Answer in 2 to 4 sentences or compact bullet points.\n"
            . "- DO NOT use filler phrases (e.g. 'I hope this helps!', 'Feel free to ask more!', 'Hello! Welcome!'). Save token usage by delivering only the exact answer immediately.\n"
            . "- Understand English, Tagalog, Bisaya, and Taglish naturally.\n"
            . "- CAPABILITIES:\n"
            . "  1. AMENITY RECOMMENDATIONS: Recommend based on party size & budget from the catalogue.\n"
            . "  2. COST ESTIMATOR: Calculate price breakdowns immediately if guest mentions group size/amenity (e.g., Adults × Entrance + Kids × Entrance + Pool + Cottage).\n"
            . "  3. AVAILABILITY & CHECKOUT: Report live available vs occupied amenities with expected checkout times.\n"
            . "  4. PARK POLICIES: Outside food allowed (NO corkage fee for common meals/drinks), free grilling stations, proper swimwear required for pool, free parking, pets allowed on leash.\n"
            . "  5. BOOKING STEPS: Online (click 'Book Now' > pick date/time > select amenity > pay via GCash/Bank > get QR code) or Walk-in (register & pay at entrance counter).\n"
            . "  6. SESSIONS: Daytime (6:00 AM - 6:00 PM), Nighttime (6:00 PM - 6:00 AM).\n"
            . "  7. FEES: Daytime Entrance: Adult ₱70, Child ₱50 | Nighttime Entrance: Adult ₱100, Child ₱70 | Pool Access: Day ₱100, Night ₱150.\n"
            . "  8. LOCATION: Upper Hinaguan, Jasaan, Misamis Oriental (Hotline: 0917 861 8383).\n\n"
            . "=== LIVE PARK & AMENITIES CONTEXT ===\n"
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
                'max_tokens' => 350,
                'temperature' => 0.4,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['choices'][0]['message']['content'] ?? 'I could not process your request at this time.';
                $reply = str_replace(['HinaguanBot:', 'Bot:', 'GuestBot:'], '', $reply);
                $reply = trim($reply);

                return response()->json(['reply' => $reply]);
            } else {
                Log::error('Guest Chatbot OpenRouter Error: ' . $response->body());
                return response()->json([
                    'reply' => 'The assistant is temporarily unavailable. Please call 0917 861 8383.'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Guest Chatbot Exception: ' . $e->getMessage());
            return response()->json([
                'reply' => 'The guest concierge is temporarily unavailable. Please call 0917 861 8383.'
            ], 500);
        }
    }

    private function getGuestContext(string $message): string
    {
        $context = '';
        $now = now();
        $todayStr = $now->toDateString();
        $currentTimeStr = $now->format('F j, Y - g:i A');

        $context .= "Current Date/Time: {$currentTimeStr}\n";

        // 1. ALL ACTIVE AMENITIES WITH CAPACITIES & RATES
        $amenities = Amenity::where('status', true)->get();
        $context .= "\n[AMENITIES & RATES]:\n";
        foreach ($amenities as $am) {
            $aircon = $am->daytime_aircon_price ? " | Aircon Day ₱{$am->daytime_aircon_price}, Night ₱{$am->nighttime_aircon_price}" : "";
            $context .= "- {$am->amenities_name} (Cap: {$am->minimum_capacity}-{$am->maximum_capacity} pax): Day ₱{$am->daytime_price}, Night ₱{$am->nighttime_price}{$aircon}\n";
        }

        // 2. LIVE OCCUPANCY & EXPECTED CHECKOUT PREDICTIONS
        $activeBooked = ReservationAmenity::with(['reservation', 'amenity'])
            ->whereIn('status', ['Checked In', 'Confirmed', 'active'])
            ->whereHas('reservation', fn ($q) => $q->whereIn('status', ['Checked In', 'Confirmed', 'active']))
            ->get();

        $context .= "\n[LIVE AVAILABILITY]:\n";
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

        // 3. RECOMMENDATIONS CHEATSHEET
        $context .= "\n[GROUP RECOMMENDATIONS]:\n"
            . "- 1-6 pax: Native Kubo, Open Shed, Umbrella Tables, or Gazebo.\n"
            . "- 7-15 pax: Pool Cottages, Deluxe Cottages, Lakeside Pavilions.\n"
            . "- 16-30+ pax: Grand Function Hall, Family Villa, VIP Multi-Cottage.\n";

        // 4. OFFICIAL PARK RULES & GUIDELINES FROM DATABASE
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
