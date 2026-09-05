<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
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

        // Fallback to local intelligent report generator if external API unavailable
        $fallbackReport = $this->generateLocalReport($query, $preset, $stats);

        return response()->json([
            'success' => true,
            'data' => $fallbackReport,
            'source' => 'local_engine',
        ]);
    }

    private function compileDatabaseStatistics(): array
    {
        $reservations = Reservation::with(['reservationAmenities.amenity'])->get();
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

        // Complete catalog of ALL park amenities (both booked and unbooked)
        $allParkAmenities = Amenity::all();
        $amenityMap = [];

        foreach ($allParkAmenities as $am) {
            $amenityMap[$am->id] = [
                'id' => $am->id,
                'name' => $am->amenities_name,
                'price_day' => (float) ($am->daytime_price ?? 0),
                'price_night' => (float) ($am->nighttime_price ?? 0),
                'is_active' => (bool) $am->status,
                'bookings_count' => 0,
                'total_revenue' => 0.0,
            ];
        }

        // Add reservation counts and revenues to amenities
        foreach ($reservations as $r) {
            foreach ($r->reservationAmenities as $ra) {
                $amId = $ra->amenity_id;
                if ($amId && isset($amenityMap[$amId])) {
                    $amenityMap[$amId]['bookings_count'] += (int) ($ra->quantity ?? 1);
                    $amenityMap[$amId]['total_revenue'] += (float) ($ra->price_at_booking ?? 0);
                } elseif ($ra->amenity) {
                    $name = $ra->amenity->amenities_name;
                    if (!isset($amenityMap[$name])) {
                        $amenityMap[$name] = [
                            'id' => $amId ?? $name,
                            'name' => $name,
                            'price_day' => (float) ($ra->amenity->daytime_price ?? 0),
                            'price_night' => (float) ($ra->amenity->nighttime_price ?? 0),
                            'is_active' => true,
                            'bookings_count' => 0,
                            'total_revenue' => 0.0,
                        ];
                    }
                    $amenityMap[$name]['bookings_count'] += (int) ($ra->quantity ?? 1);
                    $amenityMap[$name]['total_revenue'] += (float) ($ra->price_at_booking ?? 0);
                }
            }
        }

        $allAmenitiesList = array_values($amenityMap);
        usort($allAmenitiesList, fn($a, $b) => $b['bookings_count'] <=> $a['bookings_count'] ?: $b['total_revenue'] <=> $a['total_revenue']);

        // Day of week distribution
        $dayCounts = ['Sun' => 0, 'Mon' => 0, 'Tue' => 0, 'Wed' => 0, 'Thu' => 0, 'Fri' => 0, 'Sat' => 0];
        foreach ($reservations as $r) {
            if ($r->reservation_date) {
                $dayCounts[Carbon::parse($r->reservation_date)->format('D')]++;
            }
        }

        // Recent 30 days
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $recent30Reservations = $reservations->filter(fn($r) => $r->created_at && $r->created_at >= $thirtyDaysAgo);
        $recent30Revenue = (float) $recent30Reservations->sum('amount_paid');
        $recent30Count = $recent30Reservations->count();

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
            'day_of_week' => $dayCounts,
            'recent_30d_revenue' => $recent30Revenue,
            'recent_30d_bookings' => $recent30Count,
        ];
    }

    private function queryOpenRouter(string $apiKey, string $model, string $query, array $stats): ?array
    {
        // Minified compact JSON to conserve prompt tokens
        $contextJson = json_encode($stats);

        $systemPrompt = <<<PROMPT
You are Hinaguan Intelligence, an executive data analyst for Hinaguan Nature Park. Answer the admin's query using the provided database context. Return ONLY a strict JSON object (no markdown, no preamble).

IMPORTANT RULES:
- When the user asks to list/show amenities, you MUST list ALL amenities from "all_amenities" in the DATA, including unbooked ones with 0 bookings.
- Be concise and direct in executive_summary.
- "key_metrics": 1-4 metric objects if numbers/counters requested; else [].
- "insights": 1-3 focused bullet observations answering the query; else [].
- "table_data": structured table with headers and rows whenever the user asks for a list, breakdown, or table; else null.
- "recommendations": ONLY if recommendations/strategies were asked; else [].

Schema:
{
  "title": "Short title",
  "executive_summary": "1-2 concise sentences answering query",
  "key_metrics": [{"label":"Name","value":"₱0.00","subtext":"Short text","change_type":"positive|negative|neutral"}],
  "insights": [{"headline":"Short finding","description":"1 sentence explanation","badge":"High Impact|Trend|Opportunity|Alert"}],
  "table_data": {"title":"Table title","headers":["Col1","Col2"],"rows":[["A","B"]]} | null,
  "recommendations": [{"action":"Short action","detail":"1 sentence detail"}] | []
}
Use ₱ for currency.
PROMPT;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => request()->getHttpHost(),
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
            'max_tokens' => 800,
            'temperature' => 0.1,
        ]);

        if ($response->successful()) {
            $content = $response->json('choices.0.message.content');
            if ($content) {
                // Strip markdown code block delimiters if present
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
        $topAmenity = $topAmenities[0]['name'] ?? 'Cabanas & Cottages';
        $topAmenityCount = $topAmenities[0]['count'] ?? 0;

        // Peak day identification
        $busiestDay = 'Saturday';
        $highestCount = 0;
        $dayOfWeek = $s['day_of_week'] ?? $s['day_of_week_distribution'] ?? [];
        foreach ($dayOfWeek as $day => $count) {
            if ($count > $highestCount) {
                $highestCount = $count;
                $busiestDay = $day;
            }
        }

        $q = strtolower($query . ' ' . ($preset ?? ''));
        $wantsRecommendations = (bool) preg_match('/(recommend|suggest|tip|strategy|strategies|advice|growth|improve|optimization|optimize|how to|what should)/i', $q);
        $wantsTable = (bool) preg_match('/(table|breakdown|list|compare|comparison|amenit|channel|daily|monthly|ledger|schedule|detail|share)/i', $q);
        $wantsCounters = (bool) preg_match('/(count|number|how many|total|revenue|spend|stat|metric|kpi|rate|balance|financial|sum|amount|cost|how much)/i', $q) || (!$wantsTable && !$wantsRecommendations);

        $title = !empty($preset) ? ucwords(str_replace('_', ' ', $preset)) . ' Report' : 'Executive Analysis Report';

        // 1. Key Metrics (only if quantitative / counter requested)
        $keyMetrics = [];
        if ($wantsCounters) {
            if (str_contains($q, 'cancel')) {
                $keyMetrics = [
                    ['label' => 'Total Cancellations', 'value' => (string) ($s['cancelled_count'] ?? 0), 'subtext' => "Rate: " . ($s['cancellation_rate'] ?? 0) . "%", 'change_type' => 'negative'],
                    ['label' => 'Total Bookings', 'value' => (string) $totalReservations, 'subtext' => 'All recorded bookings', 'change_type' => 'neutral'],
                ];
            } elseif (str_contains($q, 'walkin') || str_contains($q, 'online')) {
                $keyMetrics = [
                    ['label' => 'Online Bookings', 'value' => (string) ($s['online_count'] ?? 0), 'subtext' => 'Website reservations', 'change_type' => 'positive'],
                    ['label' => 'Walk-in Bookings', 'value' => (string) ($s['walkin_count'] ?? 0), 'subtext' => 'On-site front desk', 'change_type' => 'neutral'],
                    ['label' => 'Total Revenue', 'value' => $totalRev, 'subtext' => 'Gross collected', 'change_type' => 'positive'],
                ];
            } else {
                $keyMetrics = [
                    ['label' => 'Total Collected Revenue', 'value' => $totalRev, 'subtext' => 'Gross collected sales', 'change_type' => 'positive'],
                    ['label' => 'Total Bookings', 'value' => (string) $totalReservations, 'subtext' => ($s['confirmed_count'] ?? 0) . " confirmed / " . ($s['checked_in_count'] ?? 0) . " checked-in", 'change_type' => 'neutral'],
                    ['label' => 'Average Spend / Booking', 'value' => $avgSpend, 'subtext' => "Avg {$avgGuests} guests per group", 'change_type' => 'positive'],
                    ['label' => 'Unpaid Balance', 'value' => $unpaid, 'subtext' => 'Pending check-in collection', 'change_type' => ($s['unpaid_balance'] ?? 0) > 0 ? 'negative' : 'neutral'],
                ];
            }
        }

        // 2. Table Data (only if table / breakdown requested)
        $tableData = null;
        if ($wantsTable) {
            if (str_contains($q, 'peak') || str_contains($q, 'day')) {
                $dayRows = [];
                foreach ($dayOfWeek as $day => $cnt) {
                    $dayRows[] = [$day, (string) $cnt, $totalReservations > 0 ? round(($cnt / $totalReservations) * 100, 1) . '%' : '0%'];
                }
                $tableData = [
                    'title' => 'Weekly Booking Distribution Breakdown',
                    'headers' => ['Day of Week', 'Total Bookings', 'Share of Volume'],
                    'rows' => $dayRows,
                ];
            } else {
                $allAmenities = $s['all_amenities'] ?? $s['top_amenities'] ?? [];
                $amenityRows = [];
                foreach ($allAmenities as $a) {
                    $amenityRevenue = (float)($a['total_revenue'] ?? $a['revenue'] ?? 0);
                    $bookingCount = (int)($a['bookings_count'] ?? $a['count'] ?? 0);
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
        }

        // 3. Insights
        $insights = [
            [
                'headline' => "Peak Concentration: {$busiestDay}",
                'description' => "Demand is highest on {$busiestDay} ({$highestCount} bookings).",
                'badge' => 'Trend',
            ],
            [
                'headline' => "Primary Revenue Anchor: {$topAmenity}",
                'description' => "{$topAmenity} leads reservations ({$topAmenityCount} bookings).",
                'badge' => 'High Impact',
            ],
        ];

        // 4. Recommendations (ONLY if explicitly requested)
        $recommendations = [];
        if ($wantsRecommendations) {
            $recommendations = [
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
            ];
        }

        return [
            'title' => $title,
            'executive_summary' => "Hinaguan Nature Park has recorded {$totalReservations} total bookings generating {$totalRev} in collected revenue with an average guest spend of {$avgSpend} per party. Highest booking activity is concentrated on {$busiestDay}s led by {$topAmenity}.",
            'key_metrics' => $keyMetrics,
            'insights' => $insights,
            'table_data' => $tableData,
            'recommendations' => $recommendations,
        ];
    }
}
