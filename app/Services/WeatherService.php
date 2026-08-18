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

        $cacheKey = 'header_weather_forecast_v4_'.md5($location);
        $lastGoodKey = 'header_weather_last_good_'.md5($location);

        $cached = Cache::get($cacheKey);
        if ($cached && is_array($cached) && ! empty($cached['now'])) {
            return $cached;
        }

        try {
            $response = Http::timeout(5.0)->retry(2, 150)->get('https://api.weatherapi.com/v1/forecast.json', [
                'key' => $key,
                'q' => $location,
                'days' => $days,
                'aqi' => 'no',
                'alerts' => 'no',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $forecastDays = $data['forecast']['forecastday'] ?? [];

                if ($forecastDays !== []) {

                    $current = $data['current'] ?? [];
                    $currentConditionText = (string) ($current['condition']['text'] ?? '');
                    $isRainingCurrent = (bool) preg_match('/rain|drizzle|shower|thunder|storm|sleet|precip/i', $currentConditionText)
                        || ((float) ($current['precip_mm'] ?? 0) > 0);

                    $currentHourInt = (int) now()->format('G');
                    $currentHourRainChance = null;

                    $daysOut = [];

                    foreach ($forecastDays as $forecastDayIndex => $forecastDay) {
                        $icon = $forecastDay['day']['condition']['icon'] ?? null;

                        if ($icon && ! str_starts_with($icon, 'http')) {
                            $icon = 'https:'.$icon;
                        }

                        $date = Carbon::parse($forecastDay['date'] ?? now()->toDateString());
                        $isToday = $date->isSameDay(now());

                        // WeatherAPI's forecast.json includes an hourly breakdown for each day
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

                            $hourCondition = (string) ($hourEntry['condition']['text'] ?? '');
                            $hourInt = (int) $hourTime->format('G');

                            $hourChance = isset($hourEntry['chance_of_rain']) ? (int) $hourEntry['chance_of_rain'] : null;
                            
                            // If condition indicates rain or has precipitation, ensure chance of rain is realistic
                            if (preg_match('/rain|drizzle|shower|thunder|storm|sleet/i', $hourCondition) || (float) ($hourEntry['precip_mm'] ?? 0) > 0) {
                                $hourChance = max($hourChance ?? 0, 70);
                            } else {
                                $hourChance = $hourChance ?? 0;
                            }

                            if ($isToday && $hourInt === $currentHourInt) {
                                $currentHourRainChance = $hourChance;
                            }

                            $hourly[] = [
                                'time' => $hourTime->format('g A'),
                                'hour' => $hourInt,
                                'time_label' => $hourTime->format('g A'),
                                'temp_c' => $hourEntry['temp_c'] ?? null,
                                'condition' => $hourCondition,
                                'icon' => $hourIcon,
                                'chance_of_rain' => $hourChance,
                                'is_past' => $isToday && $hourInt < $currentHourInt,
                            ];
                        }

                        // For Today: filter out past hours so we show only current & upcoming hours
                        if ($isToday) {
                            $filteredHourly = array_values(array_filter($hourly, function ($h) use ($currentHourInt) {
                                return $h['hour'] >= $currentHourInt;
                            }));

                            if (count($filteredHourly) > 0) {
                                $filteredHourly[0]['time'] = 'Now';
                                $filteredHourly[0]['time_label'] = 'Now';
                            }
                            $displayHourly = $filteredHourly;
                        } else {
                            $displayHourly = $hourly;
                        }

                        $dailyChance = isset($forecastDay['day']['daily_chance_of_rain']) ? (int) $forecastDay['day']['daily_chance_of_rain'] : null;
                        if (preg_match('/rain|drizzle|shower|thunder|storm/i', (string) ($forecastDay['day']['condition']['text'] ?? '')) || (float) ($forecastDay['day']['totalprecip_mm'] ?? 0) > 0) {
                            $dailyChance = max($dailyChance ?? 0, 65);
                        }

                        $daysOut[] = [
                            'date' => $forecastDay['date'] ?? $date->toDateString(),
                            'day_name' => $isToday ? 'Today' : $date->format('l'),
                            'day_label' => $isToday ? 'Today' : $date->format('D'),
                            'condition' => $forecastDay['day']['condition']['text'] ?? null,
                            'icon' => $icon,
                            'max_temp_c' => $forecastDay['day']['maxtemp_c'] ?? null,
                            'min_temp_c' => $forecastDay['day']['mintemp_c'] ?? null,
                            'avg_temp_c' => $forecastDay['day']['avgtemp_c'] ?? null,
                            'chance_of_rain' => $dailyChance ?? 0,
                            'is_today' => $isToday,
                            'hourly' => $displayHourly,
                            'hours' => $displayHourly,
                        ];
                    }

                    $nowIcon = $current['condition']['icon'] ?? null;
                    if ($nowIcon && ! str_starts_with($nowIcon, 'http')) {
                        $nowIcon = 'https:'.$nowIcon;
                    }

                    // Compute true current rain chance
                    $effectiveNowRainChance = $currentHourRainChance ?? $daysOut[0]['chance_of_rain'] ?? 0;
                    if ($isRainingCurrent) {
                        $effectiveNowRainChance = max($effectiveNowRainChance, 80);
                    }

                    // Synchronize today's 'Now' hour card and summary with the current rain chance
                    if (! empty($daysOut) && ! empty($daysOut[0]['is_today'])) {
                        $daysOut[0]['chance_of_rain'] = $effectiveNowRainChance;
                        if (! empty($daysOut[0]['hourly']) && ($daysOut[0]['hourly'][0]['time'] ?? '') === 'Now') {
                            $daysOut[0]['hourly'][0]['chance_of_rain'] = $effectiveNowRainChance;
                            $daysOut[0]['hours'][0]['chance_of_rain'] = $effectiveNowRainChance;
                        }
                    }

                    $result = [
                        'location' => $data['location']['name'] ?? $location,
                        'updated_at' => $current['last_updated'] ?? null,
                        'now' => [
                            'temp_c' => $current['temp_c'] ?? null,
                            'feelslike_c' => $current['feelslike_c'] ?? null,
                            'humidity' => $current['humidity'] ?? null,
                            'wind_kph' => $current['wind_kph'] ?? null,
                            'condition' => $current['condition']['text'] ?? null,
                            'icon' => $nowIcon,
                            'chance_of_rain' => $effectiveNowRainChance,
                        ],
                        'days' => $daysOut,
                    ];

                    Cache::put($cacheKey, $result, now()->addMinutes(20));
                    Cache::put($lastGoodKey, $result, now()->addHours(24));

                    return $result;
                }
            }
        } catch (\Throwable $e) {
            // Log or silent fallback
        }

        // Return last known good forecast if live API request fails
        $lastGood = Cache::get($lastGoodKey);
        if ($lastGood && is_array($lastGood) && ! empty($lastGood['now'])) {
            return $lastGood;
        }

        return null;
    }

    public function getTodayWeather(): ?array
    {
        $key = config('services.weatherapi.key');
        $location = config('services.weatherapi.location');

        if (! $key || ! $location) {
            return null;
        }

        return Cache::remember(
            'homepage_weather_v3_'.md5($location),
            now()->addMinutes(15),
            function () use ($key, $location) {
                $multiDay = $this->getMultiDayForecast(1);
                if (! $multiDay) {
                    return null;
                }

                $now = now();
                $currentHour = (int) $now->format('G');
                $hourly = $multiDay['days'][0]['hourly'] ?? [];
                $next3Hours = [];

                foreach ($hourly as $h) {
                    if ($h['hour'] > $currentHour && count($next3Hours) < 3) {
                        $next3Hours[] = $h;
                    }
                }

                // If late in the evening, fallback to remaining hours
                if (count($next3Hours) < 3 && ! empty($hourly)) {
                    foreach ($hourly as $h) {
                        if (count($next3Hours) < 3 && ! in_array($h, $next3Hours, true)) {
                            $next3Hours[] = $h;
                        }
                    }
                }

                return [
                    'location' => $multiDay['location'] ?? $location,
                    'region' => null,
                    'temp_c' => $multiDay['now']['temp_c'] ?? null,
                    'feelslike_c' => $multiDay['now']['feelslike_c'] ?? null,
                    'humidity' => $multiDay['now']['humidity'] ?? null,
                    'wind_kph' => $multiDay['now']['wind_kph'] ?? null,
                    'condition' => $multiDay['now']['condition'] ?? null,
                    'icon' => $multiDay['now']['icon'] ?? null,
                    'next_3_hours' => $next3Hours,
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
                try {
                    if ($targetDate->equalTo($today)) {
                        $response = Http::timeout(2.5)->get('https://api.weatherapi.com/v1/current.json', [
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

                    $response = Http::timeout(2.5)->get('https://api.weatherapi.com/v1/forecast.json', [
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
                } catch (\Throwable $e) {
                    return null;
                }
            }
        );
    }
}
