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
                $result = [];

                foreach ($forecastDays as $forecastDay) {
                    $icon = $forecastDay['day']['condition']['icon'] ?? null;

                    if ($icon && ! str_starts_with($icon, 'http')) {
                        $icon = 'https:'.$icon;
                    }

                    $date = Carbon::parse($forecastDay['date'] ?? now()->toDateString());
                    $isToday = $date->isSameDay(now());

                    $result[] = [
                        'date' => $forecastDay['date'] ?? $date->toDateString(),
                        'day_name' => $isToday ? 'Today' : $date->format('l'),
                        'condition' => $forecastDay['day']['condition']['text'] ?? null,
                        'icon' => $icon,
                        'max_temp_c' => $forecastDay['day']['maxtemp_c'] ?? null,
                        'min_temp_c' => $forecastDay['day']['mintemp_c'] ?? null,
                        'avg_temp_c' => $forecastDay['day']['avgtemp_c'] ?? null,
                        'chance_of_rain' => $forecastDay['day']['daily_chance_of_rain'] ?? null,
                        'is_today' => $isToday,
                    ];
                }

                return $result;
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
