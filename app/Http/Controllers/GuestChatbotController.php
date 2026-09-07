<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\DailyWeatherShiftLog;
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

        $systemPrompt = "You are HinaguanBot, the warm, friendly, and helpful AI concierge for Hinaguan Nature Park in Jasaan, Misamis Oriental.\n\n"
            . "CRITICAL OPERATIONAL RULES (STRICTLY ENFORCED):\n"
            . "1. STRICT DATABASE-ONLY KNOWLEDGE & SCOPE:\n"
            . "   - You ONLY search and retrieve information from the LIVE DATABASE CONTEXT below.\n"
            . "   - You ONLY answer topics directly related to Hinaguan Nature Park (amenities, weather, rates, schedules, rules, reviews, bookings).\n"
            . "   - If a user asks about anything unrelated to the park (e.g. general knowledge, math, homework, coding, politics, other businesses), politely decline in 1 short sentence and redirect them to ask about Hinaguan Nature Park.\n"
            . "2. GO STRAIGHT TO THE POINT:\n"
            . "   - Answer directly in 1 to 3 friendly, helpful, and concise human sentences.\n"
            . "   - NEVER output internal reasoning, outlines, numbered analysis, scratchpads, or draft prefixes.\n"
            . "3. MATCH THE USER'S LANGUAGE:\n"
            . "   - If the user asks in Bisaya / Cebuano (e.g., 'tagpila', 'pila', 'asa dapit', 'naay pool', 'nindot', 'suba', 'init', 'uwan'), reply warmly and naturally in Bisaya!\n"
            . "   - If the user asks in Tagalog / Filipino / Taglish (e.g., 'magkano', 'meron ba', 'saan', 'ano po rates', 'pwede ba'), reply warmly and naturally in Tagalog!\n"
            . "   - If the user asks in English, reply in English!\n"
            . "4. AMENITY VIBE, LOCATION & DESCRIPTION AWARENESS:\n"
            . "   - Read and use the EXACT 'Description, Vibe & Location' from each amenity in the database. Even for the same amenity type, locations and atmospheres differ:\n"
            . "     * Payag 2 & Payag 6: Riverside native payags right by the water, surrounded by lush greenery.\n"
            . "     * Payag 3: Enjoying refreshing mountain breezes, perfect for quiet small-group picnics.\n"
            . "     * Payag 4: Open-air bamboo payag with scenic nature views.\n"
            . "     * Payag 5: Nestled under shade trees in a serene garden setting.\n"
            . "     * Payag 1: Traditional bamboo payag with natural ventilation.\n"
            . "     * Cottages 1 to 6: Open-air dining cottages with table and chairs for family dining and unwinding.\n"
            . "     * A-Houses 1 to 8: Private rooms/cabins for overnight or restful stays (A-House 2 & 3 are air-conditioned; all A-Houses include free entrance & free pool access for 2).\n"
            . "     * Function Hall: Spacious event pavilion for celebrations and big gatherings (15 to 50+ pax, includes free entrance & pool access).\n"
            . "5. TODAY'S WEATHER-AWARE RECOMMENDATIONS:\n"
            . "   - Check [TODAY'S WEATHER LOG (FROM DATABASE)].\n"
            . "   - If rainy, overcast, or high chance of rain: Recommend weather-sheltered spots (like cozy A-Houses, Function Hall, or covered cottages/payags) and mention staying dry.\n"
            . "   - If sunny, warm, or clear: Recommend riverside payags (Payag 2 or 6), breezy mountain payag (Payag 3), shaded garden payag (Payag 5), or taking a refreshing swim in the pool!\n"
            . "6. STRICT INCLUSION RULES (NEVER VIOLATE):\n"
            . "   - COTTAGES (₱200) and PAYAGS (₱300): DO NOT include free entrance or free pool access. Regular entrance (₱20 daytime adult) and pool access (₱50 per person) are separate fees. NEVER claim cottages or payags include free entrance or pool!\n"
            . "   - A-HOUSES (₱300 day / ₱500 night): Includes FREE entrance and FREE swimming pool access for 2 guests.\n"
            . "   - FUNCTION HALL: Includes FREE entrance and FREE swimming pool access for the booked group.\n"
            . "7. GENERAL PARK POLICIES:\n"
            . "   - Outside food allowed with NO corkage fee; free grilling stations available.\n"
            . "   - Pets allowed on leash; free parking on site.\n"
            . "   - Booking steps: Online ('Book Now' on website) or Walk-in at entrance counter.\n\n"
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

        // Detect language
        $isBisaya = (bool) preg_match('/\b(?:pila|tagpila|asa|nindot|suba|puy-anan|unsa|init|uwan|tugnaw|daghan|gamay|man|kaayo|gani|diay|kinsa|kanus-a|kabuok|ka\s+tao)\b/i', $userMessage);
        $isTagalog = (bool) preg_match('/\b(?:magkano|meron|saan|ano|ba|po|ulan|mainit|pwede|kami|tayo|sino|kailan|tao|bawat)\b/i', $userMessage);

        // 1. Guardrail for completely unrelated topics
        $unrelated = (bool) preg_match('/\b(?:python|javascript|php|code|coding|equation|solve|calculate|math|president|election|crypto|bitcoin|homework)\b/i', $userMessage);
        if ($unrelated) {
            if ($isBisaya) {
                return "Pasayloa ko, igo ra ko makatabang sa mga pangutana bahin sa Hinaguan Nature Park. Unsa may imong gustong mahibaloan bahin sa among amenities, rates, o booking?";
            } elseif ($isTagalog) {
                return "Paumanhin po, tanging mga katanungan lamang tungkol sa Hinaguan Nature Park ang aking masasagot. May maitutulong po ba ako sa inyong pagbisita, rates, o booking?";
            } else {
                return "I can only assist with inquiries regarding Hinaguan Nature Park. How may I help you with our amenities, rates, weather, or bookings?";
            }
        }

        // Fetch live weather from database (daily_weather_shift_logs)
        $latestWeather = DailyWeatherShiftLog::orderBy('log_date', 'desc')->first();
        $weatherCond = $latestWeather ? $latestWeather->weather_condition : 'Sunny';
        $weatherTemp = $latestWeather ? $latestWeather->temperature_celsius : '30';
        $weatherRain = $latestWeather ? (int) $latestWeather->precipitation_probability : 10;
        $isRainy = stripos($weatherCond, 'rain') !== false || $weatherRain >= 50;

        // 2. Weather inquiry
        if (str_contains($msgLower, 'weather') || str_contains($msgLower, 'panahon') || str_contains($msgLower, 'klima') || str_contains($msgLower, 'ulan') || str_contains($msgLower, 'uwan')) {
            if ($isBisaya) {
                $advice = $isRainy ? "Tungod kay naay uwan, maayo mag-book sa among covered A-Houses o Function Hall para komportable mo." : "Nindot kaayo ang panahon para mag-langoy sa pool o mag-relax sa riverside Payag 2 o 6!";
                return "Ang panahon karon sa Hinaguan kay {$weatherCond} ({$weatherTemp}°C, {$weatherRain}% tsansa sa uwan). {$advice}";
            } elseif ($isTagalog) {
                $advice = $isRainy ? "Dahil may tsansa ng ulan, mainam mag-book sa aming covered A-Houses o Function Hall." : "Napakaganda po ng panahon ngayon para mag-swimming sa pool o mag-relax sa aming riverside Payag!";
                return "Ang lagay ng panahon ngayon sa Hinaguan ay {$weatherCond} ({$weatherTemp}°C, {$weatherRain}% tsansa ng ulan). {$advice}";
            } else {
                $advice = $isRainy ? "Since there is a chance of rain, we recommend our covered A-Houses or Function Hall to stay dry and comfortable." : "It is a wonderful day to enjoy our swimming pool and riverside Payag 2 or 6!";
                return "Today's weather at Hinaguan Nature Park is {$weatherCond} ({$weatherTemp}°C with {$weatherRain}% chance of rain). {$advice}";
            }
        }

        // 3. Location / Vibe inquiry (Riverside, Mountain breeze, Garden shade, Scenic view)
        if (str_contains($msgLower, 'river') || str_contains($msgLower, 'suba') || str_contains($msgLower, 'ilog')) {
            if ($isBisaya) {
                return "Kung gusto kag duol sa suba nga presko ug relaxing, girekomenda namo ang Payag 2 ug Payag 6 (₱300)! Naa kini dapit sa sapa nga gilibutan sa kalasangan.";
            } elseif ($isTagalog) {
                return "Kung nais niyo po ng malapit sa ilog at presko, highly recommended ang Payag 2 at Payag 6 (₱300)! Tamang-tama sa tabi ng tubig at sariwang hangin.";
            } else {
                return "For a refreshing riverside vibe by the running water, we highly recommend Payag 2 or Payag 6 (₱300)! Both are authentic native huts right by the river.";
            }
        }

        if (str_contains($msgLower, 'mountain') || str_contains($msgLower, 'breeze') || str_contains($msgLower, 'hangin') || str_contains($msgLower, 'bukid')) {
            if ($isBisaya) {
                return "Para sa refreshing mountain breeze ug hilom nga lugar, ang Payag 3 (₱300) ang pinakanindot para sa gamay nga grupo o pamilya!";
            } else {
                return "For a refreshing mountain breeze in a peaceful setting, Payag 3 (₱300) is the perfect choice for relaxing and quiet picnics!";
            }
        }

        if (str_contains($msgLower, 'garden') || str_contains($msgLower, 'shade') || str_contains($msgLower, 'landong') || str_contains($msgLower, 'kahoy')) {
            if ($isBisaya) {
                return "Ang Payag 5 (₱300) nahimutang sa ilawom sa mga landong nga kahoy sa tanaman, bugnaw ug presko kaayo puy-an!";
            } else {
                return "Payag 5 (₱300) is nestled in a serene garden haven under lush shade trees, keeping it cool and comfortable all day!";
            }
        }

        // 4. Group recommendation (pax / number of people)
        if (preg_match('/(\d+)\s*(?:people|persons|pax|guests|heads|kabuok|ka\s+tao|tao)/i', $userMessage, $m) ||
            preg_match('/for\s+(\d+)/i', $userMessage, $m)) {
            $pax = (int) $m[1];

            if ($pax >= 1 && $pax <= 2) {
                if ($isBisaya) {
                    return "Para sa 1 hangtod 2 ka tawo, ang among A-Houses (₱300 daytime / ₱500 nighttime) ang bagay kaayo kay apil na ang libreng entrance ug pool access!";
                } elseif ($isTagalog) {
                    return "Para po sa 1 hanggang 2 tao, swak ang aming A-Houses (₱300 daytime / ₱500 nighttime) dahil may kasama na itong libreng entrance at pool access!";
                } else {
                    return "For 1 to 2 guests, our cozy A-Houses (₱300 daytime / ₱500 nighttime) are ideal and include free entrance and pool access, or you can relax in an open-air Cottage (₱200) or Payag (₱300).";
                }
            } elseif ($pax <= 10) {
                $weatherTip = $isRainy ? " (Both are covered, perfect for today's weather!)" : " (Cottage 1-6 features open-air dining tables, while Payags offer authentic bamboo relaxation!)";
                if ($isBisaya) {
                    return "Para sa grupo nga {$pax} ka tawo, girekomenda namo ang among mga Cottage (Cottage 1-6 sa ₱200) o native Payags (Payag 1-6 sa ₱300).{$weatherTip} Pwede magdala ug pagkaon nga walay corkage!";
                } elseif ($isTagalog) {
                    return "Para po sa {$pax} tao, pinaka-swak ang aming mga Cottage (Cottage 1-6 sa ₱200) o katutubong Payag (Payag 1-6 sa ₱300). Walang corkage fee sa pagkain!";
                } else {
                    return "For a group of {$pax} guests, our open-air Cottages (Cottage 1 to 6 at ₱200) or native Payags (Payag 1 to 6 at ₱300) are the perfect choice!{$weatherTip} No corkage fee for outside food.";
                }
            } else {
                if ($isBisaya) {
                    return "Para sa dakong grupo nga {$pax} ka tawo, ang among Function Hall (₱5,000 day / ₱10,000 night, apil na free entrance ug pool access) o pag-book ug daghang cottages ang pinakamaayo!";
                } else {
                    return "For a large group of {$pax} guests, our Grand Function Hall (₱5,000 day / ₱10,000 night for 15-50+ pax, includes free entrance and pool access) or booking multiple adjacent cottages would be ideal!";
                }
            }
        }

        // 5. Specific amenity inquiry (Cottage / Payag / A-House / Function Hall)
        if (str_contains($msgLower, 'cottage') || str_contains($msgLower, 'kubo') || str_contains($msgLower, 'shed')) {
            if ($isBisaya) {
                return "Ang among mga Cottage (Cottage 1 to 6) kay ₱200 para sa daytime o nighttime, naay lamesa ug lingkoranan para sa pamilya hangtod 10 ka tawo (lahi ang entrance nga ₱20 ug pool nga ₱50).";
            } else {
                return "Our open-air Cottages (Cottage 1 to 6) are ₱200 for daytime or nighttime use, featuring dining tables and chairs for up to 10 guests. Regular entrance (₱20/adult) and pool access (₱50/person) apply separately.";
            }
        }

        if (str_contains($msgLower, 'payag') || str_contains($msgLower, 'hut') || str_contains($msgLower, 'bamboo')) {
            if ($isBisaya) {
                return "Ang among mga native Payag (Payag 1 to 6) kay ₱300, ginama sa kawayan nga presko kaayo para sa 1 hangtod 8 ka tawo. Duol sa suba ang Payag 2 ug 6!";
            } else {
                return "Our native bamboo Payags (Payag 1 to 6) are ₱300 for daytime or nighttime use, offering shade and breeze for up to 8 guests. Payag 2 and 6 are riverside!";
            }
        }

        if (str_contains($msgLower, 'a-house') || str_contains($msgLower, 'ahouse') || str_contains($msgLower, 'cabin') || str_contains($msgLower, 'overnight') || str_contains($msgLower, 'room')) {
            if ($isBisaya) {
                return "Ang among A-Houses (A-House 1 to 8) kay ₱300 sa adlaw ug ₱500 sa gabii (1 to 2 ka tawo), naay aircon sa A-House 2 ug 3, ug libre na ang entrance ug pool access!";
            } else {
                return "Our A-Houses (A-House 1 to 8) are ₱300 daytime and ₱500 nighttime (1 to 2 guests). A-House 2 & 3 are air-conditioned, and all A-Houses include free entrance and swimming pool access!";
            }
        }

        if (str_contains($msgLower, 'function hall') || str_contains($msgLower, 'event') || str_contains($msgLower, 'hall') || str_contains($msgLower, 'wedding') || str_contains($msgLower, 'party')) {
            return "Our Function Hall is ₱5,000 for daytime and ₱10,000 for nighttime (accommodating 15 to 50+ guests). It includes free entrance and free pool access for your group!";
        }

        // 6. Entrance fees / swimming pool inquiry
        if (str_contains($msgLower, 'entrance') || str_contains($msgLower, 'fee') || str_contains($msgLower, 'rate') || str_contains($msgLower, 'price') || str_contains($msgLower, 'pool')) {
            $settings = ParkSetting::first();
            $dayAdult = $settings ? number_format((float)($settings->daytime_adult_entrance_fee ?? 20)) : '20';
            $dayPool = $settings ? number_format((float)($settings->day_pool_fee ?? 50)) : '50';
            if ($isBisaya) {
                return "Ang daytime entrance fee kay ₱{$dayAdult} sa hamtong (libre ang 12 anyos paubos), ug ang pool access kay ₱{$dayPool} matag tawo.";
            } elseif ($isTagalog) {
                return "Ang daytime entrance fee po ay ₱{$dayAdult} bawat adult (libre ang 12 pababa), at ₱{$dayPool} naman po bawat tao para sa swimming pool.";
            } else {
                return "Our daytime entrance fee is ₱{$dayAdult} per adult (free for children 12 and below), with daytime pool access at ₱{$dayPool} per person. Feel free to visit our Rates page for full details!";
            }
        }

        // 7. Operating hours / schedule inquiry
        if (str_contains($msgLower, 'hour') || str_contains($msgLower, 'time') || str_contains($msgLower, 'open') || str_contains($msgLower, 'schedule') || str_contains($msgLower, 'oras')) {
            $settings = ParkSetting::first();
            $openTime = $settings?->opening_time ? Carbon::parse($settings->opening_time)->format('g:i A') : '8:00 AM';
            $closeTime = $settings?->closing_time ? Carbon::parse($settings->closing_time)->format('g:i A') : '5:00 PM';
            if ($isBisaya) {
                return "Abli ang Hinaguan Nature Park gikan {$openTime} hangtod {$closeTime} adlaw-adlaw. Naa mi daytime ug overnight stays!";
            } elseif ($isTagalog) {
                return "Bukas po ang Hinaguan Nature Park mula {$openTime} hanggang {$closeTime} araw-araw. May daytime at overnight cottage stays po kami!";
            } else {
                return "Hinaguan Nature Park is open from {$openTime} to {$closeTime} daily. We offer both daytime and overnight cottage stays!";
            }
        }

        if ($isBisaya) {
            return "Maayong adlaw! Welcome sa Hinaguan Nature Park sa Jasaan, Misamis Oriental. Naa mi mga payag, cottages, swimming pool, ug A-house stays. Unsa may akong matabang kanimo karon?";
        } elseif ($isTagalog) {
            return "Magandang araw! Maligayang pagdating sa Hinaguan Nature Park sa Jasaan, Misamis Oriental. Mayroon kaming mga payag, cottage, swimming pool, at nature stays. Paano ko po kayo matutulungan ngayon?";
        } else {
            return "Hello! Welcome to Hinaguan Nature Park in Jasaan, Misamis Oriental. We offer cottages, pools, and nature stays. How can I assist you with your booking or park visit today?";
        }
    }

    private function getGuestContext(string $message): string
    {
        $context = '';
        $now = now();
        $todayStr = $now->toDateString();
        $currentTimeStr = $now->format('F j, Y - g:i A');

        $context .= "Current Date/Time: {$currentTimeStr}\n";

        // Table 1: park_settings
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

            $context .= "\n[1. OFFICIAL PARK SETTINGS, GATE HOURS & RATES (FROM DATABASE park_settings)]:\n"
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

        // Table 2: daily_weather_shift_logs
        $currentShift = ($now->hour >= 6 && $now->hour < 18) ? 'Daytime' : 'Nighttime';
        $weatherLog = DailyWeatherShiftLog::where('log_date', $todayStr)
            ->where('shift', $currentShift)
            ->first()
            ?? DailyWeatherShiftLog::where('log_date', $todayStr)->first()
            ?? DailyWeatherShiftLog::orderBy('log_date', 'desc')->first();

        if ($weatherLog) {
            $wCond = $weatherLog->weather_condition;
            $wTemp = $weatherLog->temperature_celsius ? $weatherLog->temperature_celsius . '°C' : '30°C';
            $wPrecip = (int) $weatherLog->precipitation_probability;
            $wShift = $weatherLog->shift ?: $currentShift;
            $wNotes = $weatherLog->notes ? " (Notes: {$weatherLog->notes})" : "";

            $weatherAdvice = ($wPrecip >= 40 || stripos($wCond, 'rain') !== false)
                ? "Rainy/Wet Weather Advice: Suggest weather-sheltered spots like cozy A-Houses, Function Hall, or covered dining cottages to keep guests dry."
                : "Sunny/Fair Weather Advice: Ideal for swimming pool access and open-air riverside/mountain payags (Payag 2, 3, 5, 6) or cottages!";

            $context .= "\n[2. TODAY'S LIVE WEATHER CONDITIONS (FROM DATABASE daily_weather_shift_logs)]:\n"
                . "- Condition: {$wCond} ({$wShift} shift) | Temp: {$wTemp} | Rain Probability: {$wPrecip}%{$wNotes}\n"
                . "- Weather Guidance: {$weatherAdvice}\n";
        }

        // Tables 3 & 4: amenities & amenities_benefits
        $amenities = Amenity::with('benefits')->where('status', true)->get();
        $context .= "\n[3. OFFICIAL PARK AMENITIES WITH LOCATIONS, VIBES & DESCRIPTIONS (FROM DATABASE amenities & amenities_benefits)]:\n";
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
                . "  * Description, Vibe & Location: \"{$am->description}\"\n"
                . "  * Rates: Daytime: ₱" . number_format((float) $am->daytime_price, 2) . " | Nighttime: ₱" . number_format((float) $am->nighttime_price, 2) . " | Extra Head: ₱{$addHead}\n"
                . "  * Inclusions: Free Entrance: {$freeEntrance} | Free Pool: {$freePool} | Air-conditioned: {$aircon}\n";
        }

        // Live occupancy check
        $activeBooked = ReservationAmenity::with(['reservation', 'amenity'])
            ->whereIn('status', ['Checked In', 'Confirmed', 'active'])
            ->whereHas('reservation', fn ($q) => $q->whereIn('status', ['Checked In', 'Confirmed', 'active']))
            ->get();

        $context .= "\n[4. LIVE AVAILABILITY & OCCUPANCY (FROM DATABASE reservation_amenities)]:\n";
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

        // Group size guide
        $context .= "\n[GROUP SIZE RECOMMENDATION RULES (CRITICAL)]:\n"
            . "- 1 to 2 persons: Recommend A-Houses (A-House 1 to 8, ₱300 day / ₱500 night, private cabin for solo or couples).\n"
            . "- 3 to 6 persons (Small groups / Families): Recommend Cottages (Cottage 1 to 6 at ₱200, open-air with dining table and chairs) OR Payags (Payag 1 to 6 at ₱300, native bamboo huts). NEVER recommend Function Hall for small groups of 3 to 6 people!\n"
            . "- 7 to 10 persons: Recommend Cottages (₱200) or Payags (₱300).\n"
            . "- 15 to 50+ persons (Large events, reunions, corporate gatherings): Recommend Function Hall (₱5,000 day / ₱10,000 night, includes free entrance & pool access).\n";

        // Table 5: park_rules
        $rules = ParkRule::all();
        if ($rules->isNotEmpty()) {
            $context .= "\n[5. OFFICIAL PARK RULES & POLICIES (FROM DATABASE park_rules)]:\n";
            foreach ($rules as $r) {
                $context .= "- {$r->rule_name}: {$r->rule_descriptions}\n";
            }
        }

        // Table 6: park_events
        $events = ParkEvent::where('is_active', true)->orderBy('date')->get();
        if ($events->isNotEmpty()) {
            $context .= "\n[6. ACTIVE PARK EVENTS & HAPPENINGS (FROM DATABASE park_events)]:\n";
            foreach ($events as $ev) {
                $dateStr = $ev->date ? Carbon::parse($ev->date)->format('M d, Y') : 'Date TBA';
                $dayStr = $ev->day ? " ({$ev->day})" : "";
                $timeStr = $ev->time ? " at {$ev->time}" : "";
                $context .= "- {$ev->title}: {$dateStr}{$dayStr}{$timeStr} - {$ev->event}\n";
            }
        }

        // Table 7: feedbacks
        $feedbackCount = Feedback::count();
        $feedbacks = Feedback::where('is_shown', true)->latest()->take(3)->get();
        if ($feedbackCount > 0 || $feedbacks->isNotEmpty()) {
            $avgStars = number_format((float) (Feedback::avg('stars') ?: 5.0), 1);
            $context .= "\n[7. GUEST REVIEWS & FEEDBACK (FROM DATABASE feedbacks)]:\n"
                . "- Overall Rating: {$avgStars} / 5.0 stars ({$feedbackCount} verified reviews).\n";
            foreach ($feedbacks as $fb) {
                $name = $fb->is_anonymous ? 'A guest' : ($fb->full_name ?: 'A guest');
                $context .= "- {$name} ({$fb->stars} stars): \"{$fb->description}\"\n";
            }
        }

        return $context;
    }
}
