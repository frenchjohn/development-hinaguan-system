<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WeatherService
{
    public function getMultiDayForecast(int $days = 3): ?array
    {
        $key = config('services.weatherapi.key');
        $location = config('services.weatherapi.location');

        if (! $key || ! $location) {
            return null;
        }

        return Cache::remember(
            'header_weather_forecast_'.md5($location),
            now()->addMinutes(30),
            function () use ($key, $location, $days) {
                $response = Http::timeout(8)->get('https://api.weatherapi.com/v1/forecast.json', [
                    'key' => $key,
                    'q' => $location,
                    'days' => $days,
                    'aqi' => 'no',
                    'alerts' => 'no',
                ]);

                if (! $response->successful()) {
                    return null;
                }

                $data = $response->json();
                $forecastDays = $data['forecast']['forecastday'] ?? [];

                if ($forecastDays === []) {
                    return null;
                }

                $daysOut = [];

                foreach ($forecastDays as $forecastDay) {
                    $icon = $forecastDay['day']['condition']['icon'] ?? null;

                    if ($icon && ! str_starts_with($icon, 'http')) {
                        $icon = 'https:'.$icon;
                    }

                    $date = Carbon::parse($forecastDay['date'] ?? now()->toDateString());
                    $isToday = $date->isSameDay(now());

                    // WeatherAPI's forecast.json includes an hourly breakdown for
                    // each day (24 entries: 12 AM, 1 AM, ... 11 PM) with temp,
                    // condition, icon and rain chance per hour.
                    $hourly = [];
                    foreach (($forecastDay['hour'] ?? []) as $hourEntry) {
                        $hourTime = isset($hourEntry['time']) ? Carbon::parse($hourEntry['time']) : null;
                        if (! $hourTime) {
                            continue;
                        }

                        $hourIcon = $hourEntry['condition']['icon'] ?? null;

                        if ($hourIcon && ! str_starts_with($hourIcon, 'http')) {
                            $hourIcon = 'https:'.$hourIcon;
                        }

                        // NOTE: is_past assumes the app timezone matches the
                        // location's local time (both Asia/Manila for the park);
                        // adjust if they ever diverge.
                        $hourly[] = [
                            'hour' => (int) $hourTime->format('G'),
                            'time_label' => $hourTime->format('g A'),
                            'temp_c' => $hourEntry['temp_c'] ?? null,
                            'condition' => $hourEntry['condition']['text'] ?? null,
                            'icon' => $hourIcon,
                            'chance_of_rain' => $hourEntry['chance_of_rain'] ?? null,
                            'is_past' => $isToday && $hourTime->lt(now()),
                        ];
                    }

                    $daysOut[] = [
                        'date' => $forecastDay['date'] ?? $date->toDateString(),
                        'day_name' => $isToday ? 'Today' : $date->format('l'),
                        'condition' => $forecastDay['day']['condition']['text'] ?? null,
                        'icon' => $icon,
                        'max_temp_c' => $forecastDay['day']['maxtemp_c'] ?? null,
                        'min_temp_c' => $forecastDay['day']['mintemp_c'] ?? null,
                        'avg_temp_c' => $forecastDay['day']['avgtemp_c'] ?? null,
                        'chance_of_rain' => $forecastDay['day']['daily_chance_of_rain'] ?? null,
                        'is_today' => $isToday,
                        'hourly' => $hourly,
                    ];
                }

                // forecast.json also embeds the current conditions, so callers
                // can derive "now" from this same (cached) response without a
                // separate current.json request.
                $current = $data['current'] ?? [];
                $nowIcon = $current['condition']['icon'] ?? null;

                if ($nowIcon && ! str_starts_with($nowIcon, 'http')) {
                    $nowIcon = 'https:'.$nowIcon;
                }

                return [
                    'location' => $data['location']['name'] ?? $location,
                    'updated_at' => $current['last_updated'] ?? null,
                    'now' => [
                        'temp_c' => $current['temp_c'] ?? null,
                        'feelslike_c' => $current['feelslike_c'] ?? null,
                        'humidity' => $current['humidity'] ?? null,
                        'wind_kph' => $current['wind_kph'] ?? null,
                        'condition' => $current['condition']['text'] ?? null,
                        'icon' => $nowIcon,
                    ],
                    'days' => $daysOut,
                ];
            }
        );
    }

    public function getTodayWeather(): ?array
    {
        $key = config('services.weatherapi.key');
        $location = config('services.weatherapi.location');

        if (! $key || ! $location) {
            return null;
        }

        return Cache::remember(
            'homepage_weather_'.md5($location),
            now()->addMinutes(30),
            function () use ($key, $location) {
                $response = Http::timeout(8)->get('https://api.weatherapi.com/v1/current.json', [
                    'key' => $key,
                    'q' => $location,
                ]);

                if (! $response->successful()) {
                    return null;
                }

                $data = $response->json();
                $icon = $data['current']['condition']['icon'] ?? null;

                if ($icon && ! str_starts_with($icon, 'http')) {
                    $icon = 'https:'.$icon;
                }

                return [
                    'location' => $data['location']['name'] ?? $location,
                    'region' => $data['location']['region'] ?? null,
                    'temp_c' => $data['current']['temp_c'] ?? null,
                    'feelslike_c' => $data['current']['feelslike_c'] ?? null,
                    'humidity' => $data['current']['humidity'] ?? null,
                    'wind_kph' => $data['current']['wind_kph'] ?? null,
                    'condition' => $data['current']['condition']['text'] ?? null,
                    'icon' => $icon,
                ];
            }
        );
    }

    public function getForecastForDate(string $date): ?array
    {
        $key = config('services.weatherapi.key');
        $location = config('services.weatherapi.location');

        if (! $key || ! $location) {
            return null;
        }

        try {
            $targetDate = Carbon::parse($date)->startOfDay();
        } catch (\Throwable $exception) {
            return null;
        }

        $today = now()->startOfDay();
        $maxForecastDate = $today->copy()->addDays(3)->startOfDay();

        if ($targetDate->lt($today) || $targetDate->gt($maxForecastDate)) {
            return null;
        }

        return Cache::remember(
            'reservation_weather_'.md5($location.'_'.$targetDate->toDateString()),
            now()->addMinutes(30),
            function () use ($key, $location, $targetDate, $today) {
                if ($targetDate->equalTo($today)) {
                    $response = Http::timeout(8)->get('https://api.weatherapi.com/v1/current.json', [
                        'key' => $key,
                        'q' => $location,
                    ]);

                    if (! $response->successful()) {
                        return null;
                    }

                    $data = $response->json();
                    $icon = $data['current']['condition']['icon'] ?? null;

                    if ($icon && ! str_starts_with($icon, 'http')) {
                        $icon = 'https:'.$icon;
                    }

                    return [
                        'date' => $targetDate->toDateString(),
                        'condition' => $data['current']['condition']['text'] ?? null,
                        'icon' => $icon,
                        'temp_c' => $data['current']['temp_c'] ?? null,
                        'feelslike_c' => $data['current']['feelslike_c'] ?? null,
                        'humidity' => $data['current']['humidity'] ?? null,
                        'is_current' => true,
                    ];
                }

                $response = Http::timeout(8)->get('https://api.weatherapi.com/v1/forecast.json', [
                    'key' => $key,
                    'q' => $location,
                    'days' => 3,
                    'aqi' => 'no',
                    'alerts' => 'no',
                ]);

                if (! $response->successful()) {
                    return null;
                }

                $data = $response->json();
                $forecastDays = $data['forecast']['forecastday'] ?? [];

                foreach ($forecastDays as $forecastDay) {
                    if (($forecastDay['date'] ?? null) === $targetDate->toDateString()) {
                        $icon = $forecastDay['day']['condition']['icon'] ?? null;

                        if ($icon && ! str_starts_with($icon, 'http')) {
                            $icon = 'https:'.$icon;
                        }

                        return [
                            'date' => $forecastDay['date'] ?? $targetDate->toDateString(),
                            'condition' => $forecastDay['day']['condition']['text'] ?? null,
                            'icon' => $icon,
                            'max_temp_c' => $forecastDay['day']['maxtemp_c'] ?? null,
                            'min_temp_c' => $forecastDay['day']['mintemp_c'] ?? null,
                            'avg_temp_c' => $forecastDay['day']['avgtemp_c'] ?? null,
                            'chance_of_rain' => $forecastDay['day']['daily_chance_of_rain'] ?? null,
                            'is_current' => false,
                        ];
                    }
                }

                return null;
            }
        );
    }
}
