<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\ParkEvent;
use App\Models\ParkSetting;
use App\Models\ReservationGuest;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(WeatherService $weather): View
    {
        $activeGuestCount = ReservationGuest::query()
            ->whereNull('checked_out_at')
            ->whereHas('reservation', function ($query) {
                $query->where('status', 'Checked In');
            })
            ->count();

        $parkSettings = ParkSetting::first();

        $featuredFeedbacks = Feedback::visible()
            ->topRated()
            ->limit(10)
            ->get();

        $now = Carbon::now();
        $oneWeekAhead = $now->copy()->addDays(7)->toDateString();

        // Nearest upcoming event in range 1 week (from today to 7 days ahead)
        $nearEvent = ParkEvent::active()
            ->whereBetween('date', [$now->toDateString(), $oneWeekAhead])
            ->orderBy('date', 'asc')
            ->first();

        // All active events for the events section
        $allEvents = ParkEvent::active()
            ->orderBy('date', 'asc')
            ->get();

        return view('homepage', [
            'weather' => $weather->getTodayWeather(),
            'activeGuestCount' => $activeGuestCount,
            'parkSettings' => $parkSettings,
            'featuredFeedbacks' => $featuredFeedbacks,
            'nearEvent' => $nearEvent,
            'allEvents' => $allEvents,
        ]);
    }
}
