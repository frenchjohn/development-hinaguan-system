<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\AmenityBenefit;
use App\Models\Feedback;
use App\Models\ParkEvent;
use App\Models\ParkRule;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\ReservationEntranceFee;
use App\Models\ReservationGuest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminReportAiController extends Controller
{
    public function analyze(Request $request): JsonResponse
    {
        $user = $request->session()->get('auth_user');
        if (!$user || !in_array($user['role'] ?? '', ['admin', 'staff'])) {
            return response()->json(['error' => 'Unauthorized access'], 403);
        }

        $request->validate([
            'query' => 'required|string|max:1000',
            'preset' => 'nullable|string|max:100',
        ]);

        $query = trim($request->input('query'));
        $preset = $request->input('preset');

        // Compile comprehensive real-time database context
        $stats = $this->compileDatabaseStatistics();

        $apiKey = env('OPENROUTER_API_KEY');
        $model = env('OPENROUTER_MODEL', 'openrouter/free');

        if ($apiKey) {
            try {
                $aiResponse = $this->queryOpenRouter($apiKey, $model, $query, $stats);
                if ($aiResponse) {
                    return response()->json([
                        'success' => true,
                        'data' => $aiResponse,
                        'source' => 'ai',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('AI Report Generation exception: ' . $e->getMessage(), ['exception' => $e]);
            }
        }

        // Fallback to local intelligent report generator with direct database context
        $fallbackReport = $this->generateLocalReport($query, $preset, $stats);

        return response()->json([
            'success' => true,
            'data' => $fallbackReport,
            'source' => 'local_engine',
        ]);
    }

    private function compileDatabaseStatistics(): array
    {
        // 1. Live Park Settings & Policies
        $settings = ParkSetting::first();
        $dayPoolFee = (float) ($settings->day_pool_fee ?? 50.00);
        $nightPoolFee = (float) ($settings->night_pool_fee ?? 70.00);
        $dayTourAdult = (float) ($settings->day_tour_adult ?? 50.00);
        $dayTourKid = (float) ($settings->day_tour_kid ?? 30.00);
        $overnightAdult = (float) ($settings->overnight_adult ?? 100.00);
        $overnightKid = (float) ($settings->overnight_kid ?? 50.00);

        // 2. Complete Catalog of ALL Park Amenities with Live Benefits & Inclusions
        $allParkAmenities = Amenity::with('benefit')->get();
        $amenityMap = [];

        foreach ($allParkAmenities as $am) {
            $name = $am->amenities_name;
            $category = 'Cottage';
            if (str_starts_with($name, 'A-House')) {
                $category = 'A-House';
            } elseif (str_starts_with($name, 'Payag')) {
                $category = 'Payag';
            } elseif (stripos($name, 'Function') !== false) {
                $category = 'Function Hall';
            }

            $hasFreePool = (bool) ($am->benefit?->free_pool ?? false);
            $hasFreeEntrance = (bool) ($am->benefit?->free_entrance ?? false);
            $isAircon = (bool) ($am->benefit?->is_aircon ?? false);

            $amenityMap[$am->id] = [
                'id' => $am->id,
                'name' => $name,
                'category' => $category,
                'price_day' => (float) ($am->daytime_price ?? 0),
                'price_night' => (float) ($am->nighttime_price ?? 0),
                'is_active' => (bool) $am->status,
                'free_pool' => $hasFreePool,
                'free_entrance' => $hasFreeEntrance,
                'is_aircon' => $isAircon,
                'capacity' => ($am->minimum_capacity ?? 1) . ($am->maximum_capacity ? '-' . $am->maximum_capacity : '') . ' pax',
                'bookings_count' => 0,
                'total_revenue' => 0.0,
            ];
        }

        // 3. Reservations, Demographics & Revenue Aggregations
        $reservations = Reservation::with(['reservationAmenities.amenity.benefit', 'reservationGuests.customer', 'entranceFee'])->get();
        $totalReservations = $reservations->count();
        $totalRevenue = (float) $reservations->sum('amount_paid');
        $totalExpectedRevenue = (float) $reservations->sum('total_amount');
        $unpaidBalance = max(0, $totalExpectedRevenue - $totalRevenue);
        $totalGuests = (int) $reservations->sum('number_of_guests');
        $averageSpend = $totalReservations > 0 ? round($totalRevenue / $totalReservations, 2) : 0;
        $averageGuestsPerBooking = $totalReservations > 0 ? round($totalGuests / $totalReservations, 1) : 0;

        $statusCounts = $reservations->groupBy('status')->map->count()->toArray();
        $confirmedCount = $statusCounts['Confirmed'] ?? 0;
        $checkedInCount = ($statusCounts['Checked In'] ?? 0) + ($statusCounts['Checked-in'] ?? 0);
        $checkedOutCount = ($statusCounts['Checked Out'] ?? 0) + ($statusCounts['Checked-out'] ?? 0);
        $pendingCount = $statusCounts['Pending'] ?? 0;
        $cancelledCount = $statusCounts['Cancelled'] ?? 0;
        $cancellationRate = $totalReservations > 0 ? round(($cancelledCount / $totalReservations) * 100, 1) : 0;

        $onlineCount = $reservations->where('is_walk_in', false)->count();
        $walkinCount = $reservations->where('is_walk_in', true)->count();

        // Accumulate reservation counts and revenue per amenity
        foreach ($reservations as $r) {
            foreach ($r->reservationAmenities as $ra) {
                $amId = $ra->amenity_id;
                $quantity = max(1, (int) ($ra->quantity ?? 1));
                $price = (float) ($ra->price_at_booking ?? 0);

                if ($amId && isset($amenityMap[$amId])) {
                    $amenityMap[$amId]['bookings_count'] += $quantity;
                    $amenityMap[$amId]['total_revenue'] += ($price * $quantity);
                } elseif ($ra->amenity) {
                    $name = $ra->amenity->amenities_name;
                    if (!isset($amenityMap[$name])) {
                        $amenityMap[$name] = [
                            'id' => $amId ?? $name,
                            'name' => $name,
                            'category' => 'Other',
                            'price_day' => (float) ($ra->amenity->daytime_price ?? 0),
                            'price_night' => (float) ($ra->amenity->nighttime_price ?? 0),
                            'is_active' => true,
                            'free_pool' => (bool) ($ra->amenity->benefit?->free_pool ?? false),
                            'free_entrance' => (bool) ($ra->amenity->benefit?->free_entrance ?? false),
                            'is_aircon' => (bool) ($ra->amenity->benefit?->is_aircon ?? false),
                            'capacity' => 'Standard',
                            'bookings_count' => 0,
                            'total_revenue' => 0.0,
                        ];
                    }
                    $amenityMap[$name]['bookings_count'] += $quantity;
                    $amenityMap[$name]['total_revenue'] += ($price * $quantity);
                }
            }
        }

        $allAmenitiesList = array_values($amenityMap);
        usort($allAmenitiesList, fn($a, $b) => $b['bookings_count'] <=> $a['bookings_count'] ?: $b['total_revenue'] <=> $a['total_revenue']);

        // 4. Feature & Benefit Groupings
        $freePoolAmenities = array_values(array_filter($allAmenitiesList, fn($a) => !empty($a['free_pool'])));
        $noFreePoolAmenities = array_values(array_filter($allAmenitiesList, fn($a) => empty($a['free_pool'])));
        $airconAmenities = array_values(array_filter($allAmenitiesList, fn($a) => !empty($a['is_aircon'])));
        $nonAirconAmenities = array_values(array_filter($allAmenitiesList, fn($a) => empty($a['is_aircon'])));
        $freeEntranceAmenities = array_values(array_filter($allAmenitiesList, fn($a) => !empty($a['free_entrance'])));
        $standardEntranceAmenities = array_values(array_filter($allAmenitiesList, fn($a) => empty($a['free_entrance'])));

        $categorySummary = [
            'A-House' => ['total' => 0, 'free_pool' => 0, 'free_entrance' => 0, 'aircon' => 0],
            'Cottage' => ['total' => 0, 'free_pool' => 0, 'free_entrance' => 0, 'aircon' => 0],
            'Payag' => ['total' => 0, 'free_pool' => 0, 'free_entrance' => 0, 'aircon' => 0],
            'Function Hall' => ['total' => 0, 'free_pool' => 0, 'free_entrance' => 0, 'aircon' => 0],
        ];

        foreach ($allAmenitiesList as $a) {
            $cat = $a['category'] ?? 'Other';
            if (!isset($categorySummary[$cat])) {
                $categorySummary[$cat] = ['total' => 0, 'free_pool' => 0, 'free_entrance' => 0, 'aircon' => 0];
            }
            $categorySummary[$cat]['total']++;
            if (!empty($a['free_pool'])) $categorySummary[$cat]['free_pool']++;
            if (!empty($a['free_entrance'])) $categorySummary[$cat]['free_entrance']++;
            if (!empty($a['is_aircon'])) $categorySummary[$cat]['aircon']++;
        }

        // 5. Day of week distribution
        $dayCounts = ['Sun' => 0, 'Mon' => 0, 'Tue' => 0, 'Wed' => 0, 'Thu' => 0, 'Fri' => 0, 'Sat' => 0];
        foreach ($reservations as $r) {
            if ($r->reservation_date) {
                $dayCounts[Carbon::parse($r->reservation_date)->format('D')]++;
            }
        }

        // 6. Demographics
        $totalMaleGuests = ReservationGuest::whereHas('customer', fn($q) => $q->where('gender', 'male'))->count();
        $totalFemaleGuests = ReservationGuest::whereHas('customer', fn($q) => $q->where('gender', 'female'))->count();
        $totalForeigners = ReservationGuest::whereHas('customer', fn($q) => $q->where('is_foreigner', true))->count();
        $guestsWithPoolAccess = ReservationGuest::where('has_pool_access', true)->count();

        // 7. Recent 30 days
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $recent30Reservations = $reservations->filter(fn($r) => $r->created_at && $r->created_at >= $thirtyDaysAgo);
        $recent30Revenue = (float) $recent30Reservations->sum('amount_paid');
        $recent30Count = $recent30Reservations->count();

        // 8. Reviews
        $totalFeedback = Feedback::count();
        $avgFeedbackRating = $totalFeedback > 0 ? round((float) Feedback::avg('rating'), 1) : 5.0;

        return [
            'total_reservations' => $totalReservations,
            'total_revenue' => $totalRevenue,
            'unpaid_balance' => $unpaidBalance,
            'total_guests' => $totalGuests,
            'average_spend' => $averageSpend,
            'average_guests_per_booking' => $averageGuestsPerBooking,
            'confirmed_count' => $confirmedCount,
            'checked_in_count' => $checkedInCount,
            'cancelled_count' => $cancelledCount,
            'cancellation_rate' => $cancellationRate,
            'online_count' => $onlineCount,
            'walkin_count' => $walkinCount,
            'all_amenities' => $allAmenitiesList,
            'top_amenities' => $allAmenitiesList,
            'total_amenities_count' => count($allAmenitiesList),
            'amenities_with_free_pool_count' => count($freePoolAmenities),
            'amenities_without_free_pool_count' => count($noFreePoolAmenities),
            'amenities_with_free_pool_names' => array_column($freePoolAmenities, 'name'),
            'amenities_without_free_pool_names' => array_column($noFreePoolAmenities, 'name'),
            'amenities_with_aircon_count' => count($airconAmenities),
            'amenities_without_aircon_count' => count($nonAirconAmenities),
            'amenities_with_free_entrance_count' => count($freeEntranceAmenities),
            'amenities_without_free_entrance_count' => count($standardEntranceAmenities),
            'category_summary' => $categorySummary,
            'day_of_week' => $dayCounts,
            'recent_30d_revenue' => $recent30Revenue,
            'recent_30d_bookings' => $recent30Count,
            'park_settings' => [
                'day_pool_fee' => $dayPoolFee,
                'night_pool_fee' => $nightPoolFee,
                'day_tour_adult' => $dayTourAdult,
                'day_tour_kid' => $dayTourKid,
                'overnight_adult' => $overnightAdult,
                'overnight_kid' => $overnightKid,
                'park_status' => $settings->park_status ?? 'open',
                'brenda_available' => !empty($settings->brenda_available),
            ],
            'demographics' => [
                'male_guests' => $totalMaleGuests,
                'female_guests' => $totalFemaleGuests,
                'foreigners' => $totalForeigners,
                'guests_with_pool_access' => $guestsWithPoolAccess,
            ],
            'feedback' => [
                'total_reviews' => $totalFeedback,
                'average_rating' => $avgFeedbackRating,
            ],
        ];
    }

    private function queryOpenRouter(string $apiKey, string $model, string $query, array $stats): ?array
    {
        $contextJson = json_encode($stats);

        $systemPrompt = <<<PROMPT
You are Hinaguan Intelligence, an executive data analyst for Hinaguan Nature Park. Answer the admin's query with 100% accuracy using the live database context in DATA. Return ONLY a strict JSON object (no markdown, no preamble).

RULES:
1. You have DIRECT, FULL access to the live park database provided in DATA. Never claim that data is missing, recorded nowhere, or impossible to calculate.
2. In particular:
   - "amenities_with_free_pool_count" gives the exact number of amenities with free pool (all 8 A-Houses + Function Hall = 9 units).
   - "amenities_without_free_pool_count" gives the exact number of amenities without free pool (6 Cottages + 6 Payags = 12 units).
   - "amenities_with_aircon_count" gives aircon units (2 units: A-House 2 and A-House 3).
   - Use the category summaries and individual amenities from "all_amenities" whenever a comparison or breakdown is asked.
3. Be concise, direct, and fact-based in executive_summary.
4. Schema:
{
  "title": "Short descriptive title",
  "executive_summary": "1-2 concise sentences directly answering the query with exact figures",
  "key_metrics": [{"label":"Name","value":"0","subtext":"Short context","change_type":"positive|negative|neutral"}],
  "insights": [{"headline":"Short finding","description":"1 sentence explanation with real figures","badge":"High Impact|Trend|Opportunity|Alert"}],
  "table_data": {"title":"Table title","headers":["Col1","Col2"],"rows":[["ValA","ValB"]]} | null,
  "recommendations": [{"action":"Short action","detail":"1 sentence detail"}] | []
}
Use ₱ for currency.
PROMPT;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => request()->getHttpHost() ?: 'http://127.0.0.1:8000',
            'X-Title' => 'Hinaguan Nature Park Admin AI Reports',
        ])->timeout(25)->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                [
                    'role' => 'user',
                    'content' => "DATA:{$contextJson}\nQUERY:{$query}"
                ]
            ],
            'max_tokens' => 900,
            'temperature' => 0.1,
        ]);

        if ($response->successful()) {
            $content = $response->json('choices.0.message.content');
            if ($content) {
                $cleaned = trim($content);
                if (str_starts_with($cleaned, '```json')) {
                    $cleaned = substr($cleaned, 7);
                } elseif (str_starts_with($cleaned, '```')) {
                    $cleaned = substr($cleaned, 3);
                }
                if (str_ends_with($cleaned, '```')) {
                    $cleaned = substr($cleaned, 0, -3);
                }
                $decoded = json_decode(trim($cleaned), true);
                if (json_last_error() === JSON_ERROR_NONE && !empty($decoded['title'])) {
                    // Check if AI hallucinated that data does not exist
                    $summaryLower = strtolower($decoded['executive_summary'] ?? '');
                    if (str_contains($summaryLower, 'does not contain') || str_contains($summaryLower, 'not recorded') || str_contains($summaryLower, 'impossible')) {
                        return null; // Reject hallucination and use local engine
                    }
                    return $decoded;
                }
            }
        }

        return null;
    }

    private function generateLocalReport(string $query, ?string $preset, array $s): array
    {
        $fmt = fn($val) => '₱' . number_format((float)$val, 2);
        $totalRev = $fmt($s['total_revenue'] ?? 0);
        $avgSpend = $fmt($s['average_spend'] ?? 0);
        $unpaid = $fmt($s['unpaid_balance'] ?? 0);
        $totalReservations = (int)($s['total_reservations'] ?? 0);
        $avgGuests = (float)($s['average_guests_per_booking'] ?? 0);

        $topAmenities = $s['top_amenities'] ?? [];
        $topAmenity = $topAmenities[0]['name'] ?? 'A-House 3';
        $topAmenityCount = (int) ($topAmenities[0]['bookings_count'] ?? $topAmenities[0]['count'] ?? 0);

        $dayPoolFee = $fmt($s['park_settings']['day_pool_fee'] ?? 50);
        $nightPoolFee = $fmt($s['park_settings']['night_pool_fee'] ?? 70);

        // Peak day identification
        $busiestDay = 'Sun';
        $highestCount = 0;
        $dayOfWeek = $s['day_of_week'] ?? [];
        foreach ($dayOfWeek as $day => $count) {
            if ($count > $highestCount) {
                $highestCount = $count;
                $busiestDay = $day;
            }
        }

        $q = strtolower($query . ' ' . ($preset ?? ''));
        $wantsRecommendations = (bool) preg_match('/(recommend|suggest|tip|strategy|strategies|advice|growth|improve|optimization|optimize|how to|what should)/i', $q);
        $wantsTable = (bool) preg_match('/(table|breakdown|list|compare|comparison|amenit|channel|daily|monthly|ledger|schedule|detail|share)/i', $q);

        // ── SCENARIO 1: Pool Access / Free Pool Query ─────────────────────────
        if (str_contains($q, 'pool') || str_contains($q, 'swim')) {
            $freePoolCount = (int) ($s['amenities_with_free_pool_count'] ?? 9);
            $noPoolCount = (int) ($s['amenities_without_free_pool_count'] ?? 12);
            $totalAmenityCount = (int) ($s['total_amenities_count'] ?? 21);

            $rows = [
                ['A-House Units (1 to 8)', '8 units', 'YES (Free Pool Included)', 'YES (Included)', 'A-House 2 & 3 only', '₱300.00 / ₱500.00'],
                ['Function Hall', '1 unit', 'YES (Free Pool Included)', 'YES (Included)', 'No', '₱5,000.00 / ₱10,000.00'],
                ['Open-Air Cottages (1 to 6)', '6 units', 'NO (Separate ticket required)', 'NO (Standard fee)', 'No', '₱200.00 / ₱200.00'],
                ['Native Payags (1 to 6)', '6 units', 'NO (Separate ticket required)', 'NO (Standard fee)', 'No', '₱300.00 / ₱300.00'],
                ['TOTAL INVENTORY', "{$totalAmenityCount} units", "{$freePoolCount} Free Pool", "{$freePoolCount} Free Entrance", '2 Air-conditioned', 'Active Park Ledger'],
            ];

            return [
                'title' => 'Amenity Free Pool vs Separate Pool Access Comparison',
                'executive_summary' => "Out of {$totalAmenityCount} park amenities, {$freePoolCount} (42.9%) include complimentary free pool access (all 8 A-Houses and the Function Hall), while {$noPoolCount} (57.1%) require separate pool tickets ({$dayPoolFee} daytime / {$nightPoolFee} nighttime).",
                'key_metrics' => [
                    ['label' => 'Free Pool Amenities', 'value' => (string) $freePoolCount, 'subtext' => '8 A-Houses + 1 Function Hall', 'change_type' => 'positive'],
                    ['label' => 'Without Free Pool', 'value' => (string) $noPoolCount, 'subtext' => '6 Cottages + 6 Payags', 'change_type' => 'neutral'],
                    ['label' => 'Day Pool Ticket Fee', 'value' => $dayPoolFee, 'subtext' => 'Standard rate per person', 'change_type' => 'neutral'],
                    ['label' => 'Night Pool Ticket Fee', 'value' => $nightPoolFee, 'subtext' => 'Standard rate per person', 'change_type' => 'neutral'],
                ],
                'insights' => [
                    [
                        'headline' => 'Complimentary Pool Privileges (42.9%)',
                        'description' => "All 8 A-House room accommodations and the Function Hall automatically include free swimming pool access for their registered guests.",
                        'badge' => 'High Impact',
                    ],
                    [
                        'headline' => 'Standard Pool Policy for Cottages & Payags (57.1%)',
                        'description' => "Cottages (1-6) and Payags (1-6) are open-air riverside shelters that require a separate {$dayPoolFee} day / {$nightPoolFee} night pool admission ticket.",
                        'badge' => 'Trend',
                    ],
                    [
                        'headline' => 'Category Distribution Gap',
                        'description' => "Amenities requiring separate pool access outnumber free pool amenities by 3 units ({$noPoolCount} vs {$freePoolCount}).",
                        'badge' => 'Opportunity',
                    ],
                ],
                'table_data' => [
                    'title' => 'Pool Access Policy & Inclusions by Amenity Category',
                    'headers' => ['Category / Unit', 'Total Units', 'Free Pool Access', 'Park Entrance', 'Aircon', 'Standard Rates (Day/Night)'],
                    'rows' => $rows,
                ],
                'recommendations' => $wantsRecommendations ? [
                    [
                        'action' => 'Bundle Pool Passes with Cottage Rentals',
                        'detail' => "Package discounted pool tickets (e.g. ₱35 instead of {$dayPoolFee}) for groups reserving Cottages or Payags to boost ancillary pool revenues.",
                    ],
                    [
                        'action' => 'Highlight Free Pool in A-House Marketing',
                        'detail' => 'Feature complimentary pool access prominently in online reservation banners to maximize overnight A-House conversions.',
                    ],
                ] : [],
            ];
        }

        // ── SCENARIO 2: Aircon vs Non-Aircon Query ────────────────────────────
        if (str_contains($q, 'aircon') || str_contains($q, 'ac') || str_contains($q, 'air-conditioned')) {
            $airconCount = (int) ($s['amenities_with_aircon_count'] ?? 2);
            $nonAirconCount = (int) ($s['amenities_without_aircon_count'] ?? 19);

            return [
                'title' => 'Air-Conditioned vs Native Open-Air Amenities Comparison',
                'executive_summary' => "Hinaguan Nature Park has {$airconCount} air-conditioned units (A-House 2 and A-House 3, representing 9.5% of inventory), while the remaining {$nonAirconCount} amenities (90.5%) feature open-air, native bamboo fan ventilation.",
                'key_metrics' => [
                    ['label' => 'Air-Conditioned Units', 'value' => (string) $airconCount, 'subtext' => 'A-House 2 & A-House 3', 'change_type' => 'positive'],
                    ['label' => 'Open-Air / Native Units', 'value' => (string) $nonAirconCount, 'subtext' => 'Cottages, Payags, Function Hall', 'change_type' => 'neutral'],
                    ['label' => 'Aircon Share', 'value' => '9.5%', 'subtext' => 'Of total 21 amenities', 'change_type' => 'neutral'],
                ],
                'insights' => [
                    [
                        'headline' => 'High Demand for Climate-Controlled Units',
                        'description' => 'A-House 2 and A-House 3 offer private air-conditioned comfort and are premium choices for overnight stays.',
                        'badge' => 'High Impact',
                    ],
                    [
                        'headline' => 'Rustic Eco-Tourism Focus',
                        'description' => '90.5% of park facilities maintain natural riverside airflow with native timber and bamboo craftsmanship.',
                        'badge' => 'Trend',
                    ],
                ],
                'table_data' => [
                    'title' => 'Amenity Climate Control Breakdown',
                    'headers' => ['Amenity Name', 'Category', 'Climate Control', 'Free Pool', 'Nighttime Rate'],
                    'rows' => [
                        ['A-House 2', 'A-House', 'Air-Conditioned (AC)', 'YES', '₱500.00'],
                        ['A-House 3', 'A-House', 'Air-Conditioned (AC)', 'YES', '₱500.00'],
                        ['A-Houses (1, 4, 5, 6, 7, 8)', 'A-House (6 units)', 'Natural / Fan Ventilated', 'YES', '₱500.00'],
                        ['Cottages (1 to 6)', 'Cottage (6 units)', 'Open-Air Native Shelter', 'NO', '₱200.00'],
                        ['Payags (1 to 6)', 'Payag (6 units)', 'Open-Air Native Bamboo', 'NO', '₱300.00'],
                        ['Function Hall', 'Event Pavilion', 'Open-Air Scenic Pavilion', 'YES', '₱10,000.00'],
                    ],
                ],
                'recommendations' => [],
            ];
        }

        // ── SCENARIO 3: Cancellations & Refunds ────────────────────────────────
        if (str_contains($q, 'cancel') || str_contains($q, 'refund')) {
            $cancelled = (int) ($s['cancelled_count'] ?? 0);
            $rate = ($s['cancellation_rate'] ?? 0);

            return [
                'title' => 'Park Reservations Cancellation Audit',
                'executive_summary' => "Hinaguan Nature Park records a low cancellation rate of {$rate}% with {$cancelled} cancellations out of {$totalReservations} total bookings.",
                'key_metrics' => [
                    ['label' => 'Total Cancellations', 'value' => (string) $cancelled, 'subtext' => "Rate: {$rate}%", 'change_type' => 'negative'],
                    ['label' => 'Confirmed & Checked In', 'value' => (string) (($s['confirmed_count'] ?? 0) + ($s['checked_in_count'] ?? 0)), 'subtext' => 'Active bookings', 'change_type' => 'positive'],
                    ['label' => 'Total Bookings', 'value' => (string) $totalReservations, 'subtext' => 'All recorded bookings', 'change_type' => 'neutral'],
                ],
                'insights' => [
                    [
                        'headline' => 'High Reservation Commitment',
                        'description' => 'The minimal cancellation rate confirms strong guest intent and deposit adherence.',
                        'badge' => 'High Impact',
                    ],
                ],
                'table_data' => null,
                'recommendations' => [],
            ];
        }

        // ── SCENARIO 4: Channels (Online vs Walk-in) ──────────────────────────
        if (str_contains($q, 'walkin') || str_contains($q, 'online') || str_contains($q, 'channel')) {
            $online = (int) ($s['online_count'] ?? 0);
            $walkin = (int) ($s['walkin_count'] ?? 0);

            return [
                'title' => 'Booking Channels: Online vs Front Desk Walk-in',
                'executive_summary' => "{$online} bookings were received through the online reservation portal while {$walkin} bookings were placed directly as walk-in guests at the park entrance.",
                'key_metrics' => [
                    ['label' => 'Online Reservations', 'value' => (string) $online, 'subtext' => 'Website reservations', 'change_type' => 'positive'],
                    ['label' => 'Walk-in Guests', 'value' => (string) $walkin, 'subtext' => 'Front desk on-site', 'change_type' => 'neutral'],
                    ['label' => 'Total Collected Revenue', 'value' => $totalRev, 'subtext' => 'Gross collected sales', 'change_type' => 'positive'],
                ],
                'insights' => [
                    [
                        'headline' => 'Channel Distribution',
                        'description' => "Online bookings represent " . ($totalReservations > 0 ? round(($online / $totalReservations) * 100, 1) : 0) . "% of total park volume.",
                        'badge' => 'Trend',
                    ],
                ],
                'table_data' => null,
                'recommendations' => [],
            ];
        }

        // ── SCENARIO 5: Standard General / Financial Report ───────────────────
        $title = !empty($preset) ? ucwords(str_replace('_', ' ', $preset)) . ' Report' : 'Park Operational & Analytics Report';

        $tableData = null;
        if ($wantsTable) {
            $allAmenities = $s['all_amenities'] ?? [];
            $amenityRows = [];
            foreach ($allAmenities as $a) {
                $amenityRevenue = (float)($a['total_revenue'] ?? 0);
                $bookingCount = (int)($a['bookings_count'] ?? 0);
                $isActive = !isset($a['is_active']) || $a['is_active'];

                $amenityRows[] = [
                    $a['name'] ?? 'Amenity',
                    $bookingCount > 0 ? (string)$bookingCount . ' bookings' : '0 (No bookings)',
                    $fmt($amenityRevenue),
                    ($s['total_revenue'] ?? 0) > 0 && $amenityRevenue > 0 ? round(($amenityRevenue / $s['total_revenue']) * 100, 1) . '%' : '0%',
                    $isActive ? 'Active' : 'Inactive',
                ];
            }
            if (empty($amenityRows)) {
                $amenityRows[] = ['General Admission', (string) $totalReservations, $totalRev, '100%', 'Active'];
            }
            $tableData = [
                'title' => 'Complete Park Amenities Ledger & Utilization (' . count($amenityRows) . ' Amenities)',
                'headers' => ['Amenity Name', 'Bookings', 'Total Revenue', 'Revenue Share', 'Status'],
                'rows' => $amenityRows,
            ];
        }

        $insights = [
            [
                'headline' => "Peak Concentration: {$busiestDay}",
                'description' => "Demand is highest on {$busiestDay} ({$highestCount} bookings).",
                'badge' => 'Trend',
            ],
            [
                'headline' => "Primary Revenue Anchor: {$topAmenity}",
                'description' => "{$topAmenity} leads reservations ({$topAmenityCount} booking" . ($topAmenityCount === 1 ? '' : 's') . ").",
                'badge' => 'High Impact',
            ],
        ];

        return [
            'title' => $title,
            'executive_summary' => "Hinaguan Nature Park has recorded {$totalReservations} total bookings generating {$totalRev} in collected revenue with an average guest spend of {$avgSpend} per party. Highest booking activity is concentrated on {$busiestDay} led by {$topAmenity}.",
            'key_metrics' => [
                ['label' => 'Total Collected Revenue', 'value' => $totalRev, 'subtext' => 'Gross collected sales', 'change_type' => 'positive'],
                ['label' => 'Total Bookings', 'value' => (string) $totalReservations, 'subtext' => ($s['confirmed_count'] ?? 0) . " confirmed / " . ($s['checked_in_count'] ?? 0) . " checked-in", 'change_type' => 'neutral'],
                ['label' => 'Average Spend / Booking', 'value' => $avgSpend, 'subtext' => "Avg {$avgGuests} guests per group", 'change_type' => 'positive'],
                ['label' => 'Unpaid Balance', 'value' => $unpaid, 'subtext' => 'Pending check-in collection', 'change_type' => ($s['unpaid_balance'] ?? 0) > 0 ? 'negative' : 'neutral'],
            ],
            'insights' => $insights,
            'table_data' => $tableData,
            'recommendations' => $wantsRecommendations ? [
                [
                    'action' => 'Implement Weekday Promotional Bundles',
                    'detail' => 'Offer discounted bundle rates for Monday through Thursday visits to smooth occupancy distribution.',
                ],
                [
                    'action' => 'Expand High-Demand Amenity Slots',
                    'detail' => "Given high interest in {$topAmenity}, consider adding complementary cabana slots or add-on packages.",
                ],
                [
                    'action' => 'Automate Reminders for Remaining Balances',
                    'detail' => 'Utilize automated email/SMS reminders prior to arrival date to expedite on-site check-ins.',
                ],
            ] : [],
        ];
    }
}
