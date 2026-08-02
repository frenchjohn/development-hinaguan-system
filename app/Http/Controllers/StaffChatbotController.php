<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class StaffChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $userMessage = $request->input('message');
        
        // Check if message is about the resort
        $forbiddenTopics = ['coding', 'politics', 'games', 'school', 'religion', 'celebrity', 'programming', 'developer'];
        $isForbidden = false;
        
        foreach ($forbiddenTopics as $topic) {
            if (stripos($userMessage, $topic) !== false) {
                $isForbidden = true;
                break;
            }
        }
        
        if ($isForbidden) {
            return response()->json([
                'reply' => "I'm sorry, I can only assist with questions about Hinaguan Nature Park."
            ]);
        }

        $apiKey = env('OPENROUTER_API_KEY');
        $model = $request->input('model', 'meta-llama/llama-3-8b-instruct:free'); // Specific model with less safety filtering
        
        if (!$apiKey) {
            return response()->json([
                'reply' => 'I apologize, but the chatbot service is currently unavailable. Please contact park staff for assistance.'
            ], 500);
        }

        // Fetch dynamic data based on user query
        $dynamicContext = $this->getDynamicContext($userMessage);

        $systemPrompt = "You are HinaguanBot, a helpful AI assistant for Hinaguan Nature Park staff. Keep responses short and direct (under 50 words). Answer only what was asked. No filler, no suggestions to check elsewhere, no follow-up questions unless necessary.\n\nAmenities: Cottage A, Cottage B, Function Hall, Overnight Rooms, Swimming Pool\nHours: 8:00 AM - 8:00 PM\nDownpayment: 10%\nCheck-in: 2 PM\nCheck-out: 12 PM\n\n{$dynamicContext}";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => request()->getHttpHost(),
                'X-Title' => 'Hinaguan Nature Park',
            ])->post("https://openrouter.ai/api/v1/chat/completions", [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $userMessage
                    ]
                ],
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['choices'][0]['message']['content'] ?? 'I apologize, but I could not process your request. Please contact park staff.';
                
                // Clean up the response
                $reply = str_replace(['HinaguanBot:', 'Bot:'], '', $reply);
                $reply = trim($reply);
                
                return response()->json([
                    'reply' => $reply
                ]);
            } else {
                \Log::error('OpenRouter API Error: ' . $response->body());
            
                // Return actual error details for debugging
                return response()->json([
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'reply' => 'I apologize, but I encountered an error. Please contact park staff for assistance.'
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Chatbot Exception: ' . $e->getMessage());
            return response()->json([
                'reply' => 'I apologize, but the service is temporarily unavailable. Please contact park staff for assistance.'
            ], 500);
        }
    }

    private function getDynamicContext($message)
    {
        $context = '';
        $messageLower = strtolower($message);
        
        // Parse date from message
        $dateRange = $this->parseDate($message);
        
        // Check-in related queries
        if (str_contains($messageLower, 'check in') || str_contains($messageLower, 'check-in') || str_contains($messageLower, 'checked in')) {
            if ($dateRange) {
                $checkedInCount = DB::table('reservations')
                    ->where('status', 'Checked In')
                    ->whereBetween('check_in', [$dateRange['start'], $dateRange['end']])
                    ->count();
                $context .= "\n\nCheck-ins ({$dateRange['description']}): {$checkedInCount} guests.";
            } else {
                $checkedInCount = DB::table('reservations')->where('status', 'Checked In')->count();
                $context .= "\n\nCurrent Check-ins: {$checkedInCount} guests currently checked in.";
            }
        }

        // Check-out related queries
        if (str_contains($messageLower, 'check out') || str_contains($messageLower, 'check-out') || str_contains($messageLower, 'checked out')) {
            if ($dateRange) {
                $checkedOutCount = DB::table('reservations')
                    ->where('status', 'Checked Out')
                    ->whereBetween('check_out', [$dateRange['start'], $dateRange['end']])
                    ->count();
                $context .= "\n\nCheck-outs ({$dateRange['description']}): {$checkedOutCount} guests.";
            } else {
                $checkedOutCount = DB::table('reservations')->where('status', 'Checked Out')->count();
                $context .= "\n\nCheck-outs Today: {$checkedOutCount} guests checked out.";
            }
        }

        // Reservation count queries
        if (str_contains($messageLower, 'reservation') || str_contains($messageLower, 'booking')) {
            if ($dateRange) {
                $totalReservations = DB::table('reservations')
                    ->whereBetween('reservation_date', [$dateRange['start'], $dateRange['end']])
                    ->count();
                $pendingReservations = DB::table('reservations')
                    ->whereBetween('reservation_date', [$dateRange['start'], $dateRange['end']])
                    ->where('status', 'Pending')
                    ->count();
                $confirmedReservations = DB::table('reservations')
                    ->whereBetween('reservation_date', [$dateRange['start'], $dateRange['end']])
                    ->where('status', 'Confirmed')
                    ->count();
                $cancelledReservations = DB::table('reservations')
                    ->whereBetween('reservation_date', [$dateRange['start'], $dateRange['end']])
                    ->where('status', 'Cancelled')
                    ->count();
                $context .= "\n\nReservations ({$dateRange['description']}): Total {$totalReservations}, {$pendingReservations} pending, {$confirmedReservations} confirmed, {$cancelledReservations} cancelled.";
            } else {
                $totalReservations = DB::table('reservations')->count();
                $pendingReservations = DB::table('reservations')->where('status', 'Pending')->count();
                $confirmedReservations = DB::table('reservations')->where('status', 'Confirmed')->count();
                $cancelledReservations = DB::table('reservations')->where('status', 'Cancelled')->count();
                $context .= "\n\nReservations: Total {$totalReservations}, {$pendingReservations} pending, {$confirmedReservations} confirmed, {$cancelledReservations} cancelled.";
            }
        }

        // Today's reservations
        if (str_contains($messageLower, 'today') || str_contains($messageLower, 'today\'s')) {
            $today = now()->toDateString();
            $todayReservations = DB::table('reservations')->whereDate('reservation_date', $today)->count();
            $todayCheckIns = DB::table('reservations')->whereDate('check_in', $today)->count();
            $context .= "\n\nToday's Activity: {$todayReservations} reservations, {$todayCheckIns} check-ins.";
        }

        // Guest count queries
        if (str_contains($messageLower, 'guest') || str_contains($messageLower, 'people')) {
            if ($dateRange) {
                $totalGuests = DB::table('reservations')
                    ->whereBetween('reservation_date', [$dateRange['start'], $dateRange['end']])
                    ->sum('number_of_guests');
                $context .= "\n\nGuests ({$dateRange['description']}): {$totalGuests} guests.";
            } else {
                $totalGuests = DB::table('reservations')->sum('number_of_guests');
                $avgGuests = DB::table('reservations')->avg('number_of_guests');
                $context .= "\n\nGuest Statistics: Total {$totalGuests} guests, Average " . round($avgGuests, 1) . " guests per reservation.";
            }
        }

        // Occupancy queries
        if (str_contains($messageLower, 'occupancy') || str_contains($messageLower, 'busy') || str_contains($messageLower, 'available')) {
            $checkedInCount = DB::table('reservations')->where('status', 'Checked In')->count();
            $totalReservations = DB::table('reservations')->where('status', 'Confirmed')->count();
            $context .= "\n\nCurrent Occupancy: {$checkedInCount} checked in, {$totalReservations} confirmed reservations.";
        }

        // Revenue/Financial queries
        if (str_contains($messageLower, 'revenue') || str_contains($messageLower, 'income') || str_contains($messageLower, 'money') || str_contains($messageLower, 'earn') || str_contains($messageLower, 'sales')) {
            if ($dateRange) {
                $totalRevenue = DB::table('reservations')
                    ->whereBetween('reservation_date', [$dateRange['start'], $dateRange['end']])
                    ->sum('total_amount');
                $totalPaid = DB::table('reservations')
                    ->whereBetween('reservation_date', [$dateRange['start'], $dateRange['end']])
                    ->sum('amount_paid');
                $totalBalance = DB::table('reservations')
                    ->whereBetween('reservation_date', [$dateRange['start'], $dateRange['end']])
                    ->sum('remaining_balance');
                $context .= "\n\nFinancials ({$dateRange['description']}): Revenue ₱" . number_format($totalRevenue, 2) . ", Collected ₱" . number_format($totalPaid, 2) . ", Pending Balance ₱" . number_format($totalBalance, 2) . ".";
            } else {
                $totalRevenue = DB::table('reservations')->sum('total_amount');
                $totalPaid = DB::table('reservations')->sum('amount_paid');
                $totalBalance = DB::table('reservations')->sum('remaining_balance');
                $context .= "\n\nFinancials: Total Revenue ₱" . number_format($totalRevenue, 2) . ", Collected ₱" . number_format($totalPaid, 2) . ", Pending Balance ₱" . number_format($totalBalance, 2) . ".";
            }
        }

        // Payment status queries
        if (str_contains($messageLower, 'payment') || str_contains($messageLower, 'paid') || str_contains($messageLower, 'unpaid') || str_contains($messageLower, 'balance')) {
            $fullyPaid = DB::table('reservations')->where('payment_status', 'Paid')->count();
            $partiallyPaid = DB::table('reservations')->where('payment_status', 'Partially Paid')->count();
            $totalBalance = DB::table('reservations')->sum('remaining_balance');
            $context .= "\n\nPayment Status: {$fullyPaid} fully paid, {$partiallyPaid} partially paid. Total pending balance: ₱" . number_format($totalBalance, 2) . ".";
        }

        // Walk-in vs Online queries
        if (str_contains($messageLower, 'walk-in') || str_contains($messageLower, 'walk in') || str_contains($messageLower, 'online')) {
            $walkIn = DB::table('reservations')->where('reservation_type', 'walk_in')->count();
            $online = DB::table('reservations')->where('reservation_type', 'online')->count();
            $context .= "\n\nReservation Types: {$walkIn} walk-ins, {$online} online bookings.";
        }

        // Status breakdown queries
        if (str_contains($messageLower, 'status') || str_contains($messageLower, 'pending') || str_contains($messageLower, 'confirmed')) {
            $pending = DB::table('reservations')->where('status', 'Pending')->count();
            $confirmed = DB::table('reservations')->where('status', 'Confirmed')->count();
            $checkedIn = DB::table('reservations')->where('status', 'Checked In')->count();
            $checkedOut = DB::table('reservations')->where('status', 'Checked Out')->count();
            $cancelled = DB::table('reservations')->where('status', 'Cancelled')->count();
            $context .= "\n\nStatus Breakdown: {$pending} Pending, {$confirmed} Confirmed, {$checkedIn} Checked In, {$checkedOut} Checked Out, {$cancelled} Cancelled.";
        }

        // Average spending queries
        if (str_contains($messageLower, 'average') || str_contains($messageLower, 'avg') || str_contains($messageLower, 'spending')) {
            $avgTotal = DB::table('reservations')->avg('total_amount');
            $avgPaid = DB::table('reservations')->avg('amount_paid');
            $context .= "\n\nAverage Spending: ₱" . number_format($avgTotal, 2) . " per reservation, Average paid: ₱" . number_format($avgPaid, 2) . ".";
        }

        // Cancellation rate queries
        if (str_contains($messageLower, 'cancel') || str_contains($messageLower, 'cancellation')) {
            $total = DB::table('reservations')->count();
            $cancelled = DB::table('reservations')->where('status', 'Cancelled')->count();
            $rate = $total > 0 ? round(($cancelled / $total) * 100, 1) : 0;
            $context .= "\n\nCancellation Rate: {$cancelled} cancelled out of {$total} total ({$rate}%).";
        }

        // Peak time queries (this week)
        if (str_contains($messageLower, 'week') || str_contains($messageLower, 'peak') || str_contains($messageLower, 'busy day')) {
            $weekStart = now()->startOfWeek();
            $weekEnd = now()->endOfWeek();
            $weekReservations = DB::table('reservations')->whereBetween('reservation_date', [$weekStart, $weekEnd])->count();
            $weekRevenue = DB::table('reservations')->whereBetween('reservation_date', [$weekStart, $weekEnd])->sum('total_amount');
            $context .= "\n\nThis Week: {$weekReservations} reservations, Revenue ₱" . number_format($weekRevenue, 2) . ".";
        }

        // Downpayment queries
        if (str_contains($messageLower, 'downpayment') || str_contains($messageLower, 'deposit')) {
            $totalRevenue = DB::table('reservations')->sum('total_amount');
            $totalPaid = DB::table('reservations')->sum('amount_paid');
            $downpaymentRate = $totalRevenue > 0 ? round(($totalPaid / $totalRevenue) * 100, 1) : 0;
            $context .= "\n\nDownpayment Info: 10% required. Current collection rate: {$downpaymentRate}% of total revenue collected.";
        }

        return $context;
    }

    private function parseDate($message)
    {
        $messageLower = strtolower($message);
        $now = now();
        
        // X days ago
        if (preg_match('/(\d+)\s*days?\s*ago/i', $message, $matches)) {
            $days = (int)$matches[1];
            $date = $now->subDays($days);
            return [
                'start' => $date->startOfDay(),
                'end' => $date->endOfDay(),
                'description' => "{$days} days ago (" . $date->toDateString() . ")"
            ];
        }
        
        // Yesterday
        if (str_contains($messageLower, 'yesterday')) {
            $date = $now->subDay();
            return [
                'start' => $date->startOfDay(),
                'end' => $date->endOfDay(),
                'description' => "yesterday (" . $date->toDateString() . ")"
            ];
        }
        
        // Tomorrow
        if (str_contains($messageLower, 'tomorrow')) {
            $date = $now->addDay();
            return [
                'start' => $date->startOfDay(),
                'end' => $date->endOfDay(),
                'description' => "tomorrow (" . $date->toDateString() . ")"
            ];
        }
        
        // Last week
        if (str_contains($messageLower, 'last week')) {
            $start = $now->subWeek()->startOfWeek();
            $end = $now->subWeek()->endOfWeek();
            return [
                'start' => $start,
                'end' => $end,
                'description' => "last week"
            ];
        }
        
        // This week
        if (str_contains($messageLower, 'this week')) {
            $start = $now->startOfWeek();
            $end = $now->endOfWeek();
            return [
                'start' => $start,
                'end' => $end,
                'description' => "this week"
            ];
        }
        
        // Last month
        if (str_contains($messageLower, 'last month')) {
            $start = $now->subMonth()->startOfMonth();
            $end = $now->subMonth()->endOfMonth();
            return [
                'start' => $start,
                'end' => $end,
                'description' => "last month"
            ];
        }
        
        // This month
        if (str_contains($messageLower, 'this month')) {
            $start = $now->startOfMonth();
            $end = $now->endOfMonth();
            return [
                'start' => $start,
                'end' => $end,
                'description' => "this month"
            ];
        }
        
        // Specific date formats (July 15, August 1, 2026-07-20)
        if (preg_match('/(\d{4})-(\d{1,2})-(\d{1,2})/', $message, $matches)) {
            $date = \Carbon\Carbon::create($matches[1], $matches[2], $matches[3]);
            return [
                'start' => $date->startOfDay(),
                'end' => $date->endOfDay(),
                'description' => $date->toDateString()
            ];
        }
        
        // Month name + day (July 15, August 1)
        if (preg_match('/(january|february|march|april|may|june|july|august|september|october|november|december)\s+(\d{1,2})/i', $message, $matches)) {
            $monthName = $matches[1];
            $day = $matches[2];
            $year = $now->year;
            $date = \Carbon\Carbon::createFromFormat('F j', ucfirst($monthName) . ' ' . $day)->year($year);
            return [
                'start' => $date->startOfDay(),
                'end' => $date->endOfDay(),
                'description' => $date->toDateString()
            ];
        }
        
        return null;
    }
}
