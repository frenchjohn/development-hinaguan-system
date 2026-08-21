<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\ParkSetting;
use App\Models\ReservationGuest;
use App\Services\WeatherService;
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

        return view('homepage', [
            'weather' => $weather->getTodayWeather(),
            'activeGuestCount' => $activeGuestCount,
            'parkSettings' => $parkSettings,
            'featuredFeedbacks' => $featuredFeedbacks,
        ]);
    }
}
