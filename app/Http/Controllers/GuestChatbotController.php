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

        $systemPrompt = "You are HinaguanBot, the warm, friendly, and helpful guest concierge for Hinaguan Nature Park in Jasaan, Misamis Oriental.\n\n"
            . "CRITICAL OUTPUT RULES (STRICTLY ENFORCED):\n"
            . "- OUTPUT ONLY THE DIRECT CONVERSATIONAL RESPONSE. Do NOT include reasoning steps, planning, internal monologue, notes, or analytical scratchpads.\n"
            . "- NEVER output numbered analysis steps (e.g. '1. Analyze User Input:', '2. Check Knowledge Base:', '3. Formulate Response:').\n"
            . "- NEVER prefix your response with 'Draft:', 'Response:', 'Answer:', or 'HinaguanBot:'. Start directly with your welcoming message to the guest.\n"
            . "- Speak warmly and naturally in clear, friendly human sentences (1 to 3 helpful sentences) like a welcoming resort front-desk host.\n"
            . "- Understand English, Tagalog, Bisaya, and Taglish naturally.\n\n"
            . "PARK INFO & POLICIES:\n"
            . "1. SESSIONS: Daytime (6:00 AM - 6:00 PM), Nighttime (6:00 PM - 6:00 AM).\n"
            . "2. ENTRANCE: Daytime (Adult ₱70, Child ₱50) | Nighttime (Adult ₱100, Child ₱70).\n"
            . "3. POOL ACCESS: Day ₱100, Night ₱150 (proper swimwear required).\n"
            . "4. PARK POLICIES: Outside food allowed (NO corkage fee for common meals/drinks), free grilling stations, pets allowed on leash, free parking.\n"
            . "5. BOOKING STEPS: Online (click 'Book Now' > pick date/time > select amenity > pay via GCash/Bank > get QR code) or Walk-in (register & pay at entrance counter).\n"
            . "6. LOCATION & HOTLINE: Upper Hinaguan, Jasaan, Misamis Oriental (Hotline: 0917 861 8383).\n\n"
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
                'max_tokens' => 600,
                'temperature' => 0.2,
                'include_reasoning' => false,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $rawReply = $data['choices'][0]['message']['content'] ?? 'I could not process your request at this time.';
                $reply = $this->cleanChatbotReply($rawReply);

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

    private function getGuestContext(string $message): string
    {
        $context = '';
        $now = now();
        $todayStr = $now->toDateString();
        $currentTimeStr = $now->format('F j, Y - g:i A');

        $context .= "Current Date/Time: {$currentTimeStr}\n";

        $settings = ParkSetting::first();
        $statusStr = ($settings?->park_status ?? 'open') === 'closed' 
            ? "CLOSED (Reason: " . ($settings?->close_description ?: 'Temporarily closed for maintenance') . ")" 
            : "OPEN (Operating normally for all visitors)";
        $context .= "Live Park Operational Status: {$statusStr}\n";

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
