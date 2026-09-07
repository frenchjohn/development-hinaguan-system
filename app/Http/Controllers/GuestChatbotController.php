<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Feedback;
use App\Models\ParkEvent;
use App\Models\ParkRule;
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

        $systemPrompt = "You are HinaguanBot, the warm, friendly, and helpful resort front-desk host for Hinaguan Nature Park in Jasaan, Misamis Oriental.\n\n"
            . "CRITICAL DIRECTIVE (STRICTLY ENFORCED):\n"
            . "- Answer the guest directly in 1 to 3 warm, helpful, and natural human sentences.\n"
            . "- GO STRAIGHT TO THE ANSWER. Do NOT output any reasoning, thinking process, outlines, numbered analysis, or draft prefixes.\n"
            . "- Your very first word must be the welcoming conversational message to the guest.\n"
            . "- Speak naturally in English, Tagalog, or Bisaya.\n"
            . "CRITICAL INCLUSION RULES (STRICTLY ENFORCED - NEVER VIOLATE):\n"
            . "- COTTAGES (Cottage 1 to 6) and PAYAGS (Payag 1 to 6): DO NOT HAVE FREE ENTRANCE OR FREE POOL ACCESS. Regular entrance fees (₱20 daytime adult) and pool access fees (₱50 per person) are separate charges. NEVER tell a guest that cottages or payags include free entrance or free pool!\n"
            . "- A-HOUSES (A-House 1 to 8): Includes FREE entrance and FREE swimming pool access for 2 guests.\n"
            . "- FUNCTION HALL: Includes FREE entrance and FREE swimming pool access for the booked event group.\n\n"
            . "PARK GENERAL POLICIES & BOOKING:\n"
            . "1. OUTSIDE FOOD & CORKAGE: Outside food is allowed with NO corkage fee for common meals and drinks; free grilling stations available.\n"
            . "2. PETS & PARKING: Pets are allowed on leash; free parking available on site.\n"
            . "3. BOOKING STEPS: Online (click 'Book Now' > pick date/time > select amenity > pay via GCash/Bank > receive QR code) or Walk-in (register & pay at the entrance counter).\n\n"
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
                'max_tokens' => 1000,
                'temperature' => 0.3,
                'include_reasoning' => false,
                'reasoning' => [
                    'effort' => 'none',
                    'exclude' => true,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $rawReply = $data['choices'][0]['message']['content'] ?? '';
                $reply = $this->cleanChatbotReply($rawReply, $userMessage);

                return response()->json(['reply' => $reply]);
            } else {
                Log::error('Guest Chatbot OpenRouter Error: ' . $response->body());
                $fallback = $this->generateDirectFallbackResponse($userMessage);
                return response()->json([
                    'reply' => !empty($fallback) ? $fallback : 'The assistant is temporarily unavailable. Please call 0917 861 8383.'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Guest Chatbot Exception: ' . $e->getMessage());
            $fallback = $this->generateDirectFallbackResponse($userMessage);
            return response()->json([
                'reply' => !empty($fallback) ? $fallback : 'The guest concierge is temporarily unavailable. Please call 0917 861 8383.'
            ]);
        }
    }

    /**
     * Clean raw AI response to strip thinking processes, reasoning tags, numbered scratchpad steps, and draft labels.
     */
    private function cleanChatbotReply(string $reply, string $userMessage = ''): string
    {
        if (empty(trim($reply))) {
            return !empty($userMessage) ? $this->generateDirectFallbackResponse($userMessage) : '';
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

        // 7. Strip leading bot/role prefixes like "HinaguanBot:", "StaffBot:", "AdminBot:", "Assistant:"
        $text = preg_replace('/^(?:HinaguanBot|StaffBot|AdminBot|GuestBot|Bot|Assistant|AI):\s*/i', '', trim($text));

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

        return trim($text);
    }

    /**
     * Context-aware direct fallback response in case a free LLM emits only analytical scratchpad tokens.
     */
    private function generateDirectFallbackResponse(string $userMessage): string
    {
        $msgLower = strtolower($userMessage);

        // 1. Group recommendation (pax / number of people)
        if (preg_match('/(\d+)\s*(?:people|persons|pax|guests|heads|kabuok|ka\s+tao|tao)/i', $userMessage, $m) ||
            preg_match('/for\s+(\d+)/i', $userMessage, $m)) {
            $pax = (int) $m[1];

            if ($pax >= 1 && $pax <= 2) {
                return "For 1 to 2 guests, our cozy A-Houses (A-House 1 to 8 at ₱300 daytime / ₱500 nighttime) include free entrance and pool access, or you can enjoy an open-air Cottage (₱200) or Payag (₱300).";
            } elseif ($pax <= 10) {
                return "For a group of {$pax} guests, our open-air Cottages (Cottage 1 to 6 at ₱200) or native Payags (Payag 1 to 6 at ₱300) are the perfect choice! Both have dining tables and seating for 4 to 10 people (entrance and pool fees apply separately).";
            } else {
                return "For a large gathering of {$pax} guests, our Grand Function Hall (₱5,000 daytime / ₱10,000 nighttime for 15 to 50+ pax, includes free entrance and pool access) or booking multiple adjacent cottages would be ideal!";
            }
        }

        // 2. Specific amenity inquiry (Cottage / Payag / A-House / Function Hall)
        if (str_contains($msgLower, 'cottage') || str_contains($msgLower, 'kubo') || str_contains($msgLower, 'shed')) {
            return "Our open-air Cottages (Cottage 1 to 6) are ₱200 for daytime or nighttime use, featuring a dining table and chairs for up to 10 guests. Regular entrance (₱20/adult) and pool access (₱50/person) are separate fees.";
        }

        if (str_contains($msgLower, 'payag') || str_contains($msgLower, 'hut') || str_contains($msgLower, 'bamboo')) {
            return "Our native bamboo Payags (Payag 1 to 6) are ₱300 for daytime or nighttime use, offering shade and seating for up to 8 guests. Regular entrance (₱20/adult) and pool access (₱50/person) are separate fees.";
        }

        if (str_contains($msgLower, 'a-house') || str_contains($msgLower, 'ahouse') || str_contains($msgLower, 'cabin') || str_contains($msgLower, 'overnight') || str_contains($msgLower, 'room')) {
            return "Our A-Houses (A-House 1 to 8) are ₱300 for daytime and ₱500 for nighttime (1 to 2 guests). Selected units feature air-conditioning and include free entrance and swimming pool access!";
        }

        if (str_contains($msgLower, 'function hall') || str_contains($msgLower, 'event') || str_contains($msgLower, 'hall') || str_contains($msgLower, 'wedding') || str_contains($msgLower, 'party')) {
            return "Our Function Hall is ₱5,000 for daytime and ₱10,000 for nighttime (accommodating 15 to 50+ guests). It includes free entrance and free pool access for your group!";
        }

        // 3. Entrance fees / swimming pool inquiry
        if (str_contains($msgLower, 'entrance') || str_contains($msgLower, 'fee') || str_contains($msgLower, 'rate') || str_contains($msgLower, 'price') || str_contains($msgLower, 'pool')) {
            $settings = ParkSetting::first();
            $dayAdult = $settings ? number_format((float)($settings->daytime_adult_entrance_fee ?? 20)) : '20';
            $dayPool = $settings ? number_format((float)($settings->day_pool_fee ?? 50)) : '50';
            return "Our daytime entrance fee is ₱{$dayAdult} per adult (free for children 12 and below), with daytime pool access at ₱{$dayPool} per person. Feel free to visit our Rates page for full details!";
        }

        // 4. Operating hours / schedule inquiry
        if (str_contains($msgLower, 'hour') || str_contains($msgLower, 'time') || str_contains($msgLower, 'open') || str_contains($msgLower, 'schedule')) {
            $settings = ParkSetting::first();
            $openTime = $settings?->opening_time ? Carbon::parse($settings->opening_time)->format('g:i A') : '8:00 AM';
            $closeTime = $settings?->closing_time ? Carbon::parse($settings->closing_time)->format('g:i A') : '5:00 PM';
            return "Hinaguan Nature Park is open from {$openTime} to {$closeTime} daily. We offer both daytime and overnight cottage stays!";
        }

        return "Hello! Welcome to Hinaguan Nature Park in Jasaan, Misamis Oriental. We offer cottages, pools, and nature stays. How can I assist you with your booking or park visit today?";
    }

    private function getGuestContext(string $message): string
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

            $context .= "\n[OFFICIAL PARK SETTINGS & LIVE RATES (SOURCE OF TRUTH FROM DATABASE)]:\n"
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

        // 1. ALL ACTIVE AMENITIES WITH CAPACITIES, RATES & INCLUSIONS
        $amenities = Amenity::with('benefits')->where('status', true)->get();
        $context .= "\n[OFFICIAL AMENITIES, CAPACITIES & INCLUSIONS (FROM DATABASE)]:\n";
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
                . "  * Rates: Daytime: ₱" . number_format((float) $am->daytime_price, 2) . " | Nighttime: ₱" . number_format((float) $am->nighttime_price, 2) . " | Extra Head: ₱{$addHead}\n"
                . "  * Inclusions: Free Entrance: {$freeEntrance} | Free Pool: {$freePool} | Air-conditioned: {$aircon}\n";
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

        // 3. GROUP RECOMMENDATION GUIDE
        $context .= "\n[GROUP SIZE RECOMMENDATION RULES (CRITICAL)]:\n"
            . "- 1 to 2 persons: Recommend A-Houses (A-House 1 to 8, ₱300 day / ₱500 night, private cabin for solo or couples).\n"
            . "- 3 to 6 persons (Small groups / Families): Recommend Cottages (Cottage 1 to 6 at ₱200, open-air with dining table and chairs) OR Payags (Payag 1 to 6 at ₱300, native bamboo huts). NEVER recommend Function Hall for small groups of 3 to 6 people!\n"
            . "- 7 to 10 persons: Recommend Cottages (₱200) or Payags (₱300).\n"
            . "- 15 to 50+ persons (Large events, reunions, corporate gatherings): Recommend Function Hall (₱5,000 day / ₱10,000 night, includes free entrance & pool access).\n";

        // 4. OFFICIAL PARK RULES & GUIDELINES FROM DATABASE
        $rules = ParkRule::all();
        if ($rules->isNotEmpty()) {
            $context .= "\n[OFFICIAL PARK RULES & GUIDELINES (FROM DATABASE)]:\n";
            foreach ($rules as $r) {
                $context .= "- {$r->rule_name}: {$r->rule_descriptions}\n";
            }
        }

        // 5. ACTIVE PARK EVENTS FROM DATABASE
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

        // 6. GUEST RATINGS & REVIEWS OVERVIEW
        $feedbackCount = Feedback::count();
        if ($feedbackCount > 0) {
            $avgStars = number_format((float) Feedback::avg('stars'), 1);
            $context .= "\n[GUEST RATINGS & REVIEWS (FROM DATABASE)]:\n"
                . "- Rated {$avgStars} / 5.0 stars across {$feedbackCount} guest reviews.\n";
        }

        return $context;
    }
}
