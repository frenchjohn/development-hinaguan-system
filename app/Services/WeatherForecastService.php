<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherForecastService
{
    // Default coordinates for Hinaguan Nature Park area (Central Visayas / Negros Oriental / Guihulngan area)
    protected float $latitude;
    protected float $longitude;

    public function __construct(?float $latitude = null, ?float $longitude = null)
    {
        $this->latitude = $latitude ?? (float) config('services.weather.latitude', 10.12);
        $this->longitude = $longitude ?? (float) config('services.weather.longitude', 123.27);
    }

    /**
     * Get weather forecast for today and the next 6 days.
     */
    public function getForecast(int $days = 7): array
    {
        $cacheKey = "weather_forecast_{$this->latitude}_{$this->longitude}_{$days}";

        return Cache::remember($cacheKey, 3600, function () use ($days) {
            try {
                $response = Http::timeout(4)->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude' => $this->latitude,
                    'longitude' => $this->longitude,
                    'hourly' => 'temperature_2m,precipitation_probability,weathercode',
                    'daily' => 'weathercode,temperature_2m_max,temperature_2m_min,precipitation_probability_max',
                    'timezone' => 'Asia/Manila',
                    'forecast_days' => $days,
                ]);

                if ($response->successful()) {
                    return $this->parseApiResponse($response->json());
                }
            } catch (\Throwable $e) {
                Log::warning('Weather forecast fetch error: ' . $e->getMessage());
            }

            return $this->getFallbackForecast($days);
        });
    }

    /**
     * Get the forecast for a specific date (Daytime & Nighttime).
     */
    public function getShiftForecastForDate(string $dateStr): array
    {
        $all = $this->getForecast(7);
        $found = collect($all['days'] ?? [])->firstWhere('date', $dateStr);

        if ($found) {
            return $found;
        }

        return $this->generateDefaultDayData(Carbon::parse($dateStr));
    }

    /**
     * Parse raw Open-Meteo response into structured Daytime & Nighttime format.
     */
    protected function parseApiResponse(array $data): array
    {
        $daily = $data['daily'] ?? [];
        $hourly = $data['hourly'] ?? [];
        $dates = $daily['time'] ?? [];

        $days = [];

        foreach ($dates as $idx => $date) {
            $parsedDate = Carbon::parse($date);
            $dayIndices = [];
            $nightIndices = [];

            // Hourly times are e.g. "2026-08-30T00:00"
            if (!empty($hourly['time'])) {
                foreach ($hourly['time'] as $hIdx => $timeStr) {
                    if (str_starts_with($timeStr, $date)) {
                        $hour = (int) substr($timeStr, 11, 2);
                        if ($hour >= 8 && $hour < 18) {
                            $dayIndices[] = $hIdx;
                        } elseif ($hour >= 18 && $hour <= 23) {
                            $nightIndices[] = $hIdx;
                        }
                    }
                }
            }

            // Daytime metrics
            $dayWeatherCode = !empty($dayIndices) ? $this->getMostFrequent(array_map(fn($i) => $hourly['weathercode'][$i] ?? 0, $dayIndices)) : ($daily['weathercode'][$idx] ?? 0);
            $dayTempAvg = !empty($dayIndices) ? round(collect($dayIndices)->avg(fn($i) => $hourly['temperature_2m'][$i] ?? 30), 1) : round(($daily['temperature_2m_max'][$idx] ?? 31), 1);
            $dayPrecipProb = !empty($dayIndices) ? (int) round(collect($dayIndices)->max(fn($i) => $hourly['precipitation_probability'][$i] ?? 0)) : (int) ($daily['precipitation_probability_max'][$idx] ?? 10);

            // Nighttime metrics
            $nightWeatherCode = !empty($nightIndices) ? $this->getMostFrequent(array_map(fn($i) => $hourly['weathercode'][$i] ?? 0, $nightIndices)) : ($daily['weathercode'][$idx] ?? 0);
            $nightTempAvg = !empty($nightIndices) ? round(collect($nightIndices)->avg(fn($i) => $hourly['temperature_2m'][$i] ?? 26), 1) : round(($daily['temperature_2m_min'][$idx] ?? 25), 1);
            $nightPrecipProb = !empty($nightIndices) ? (int) round(collect($nightIndices)->max(fn($i) => $hourly['precipitation_probability'][$i] ?? 0)) : 10;

            $dayCondition = $this->wmoCodeToCondition($dayWeatherCode);
            $nightCondition = $this->wmoCodeToCondition($nightWeatherCode);

            $days[] = [
                'date' => $date,
                'day_name' => $parsedDate->format('D'),
                'full_day_name' => $parsedDate->format('l'),
                'is_today' => $parsedDate->isToday(),
                'daytime' => [
                    'condition' => $dayCondition['name'],
                    'icon' => $dayCondition['icon'],
                    'temp' => $dayTempAvg,
                    'precip_prob' => $dayPrecipProb,
                    'is_rainy' => $dayCondition['is_rainy'],
                ],
                'nighttime' => [
                    'condition' => $nightCondition['name'],
                    'icon' => $nightCondition['icon'],
                    'temp' => $nightTempAvg,
                    'precip_prob' => $nightPrecipProb,
                    'is_rainy' => $nightCondition['is_rainy'],
                ],
            ];
        }

        return [
            'location' => 'Hinaguan Nature Park',
            'days' => $days,
            'source' => 'live',
        ];
    }

    /**
     * Map WMO weather codes to human friendly condition, icon, and rain status.
     */
    public function wmoCodeToCondition(int $code): array
    {
        return match (true) {
            $code === 0 => [
                'name' => 'Sunny',
                'icon' => '☀️',
                'is_rainy' => false,
            ],
            $code >= 1 && $code <= 2 => [
                'name' => 'Partly Cloudy',
                'icon' => '⛅',
                'is_rainy' => false,
            ],
            $code === 3 => [
                'name' => 'Cloudy',
                'icon' => '☁️',
                'is_rainy' => false,
            ],
            $code === 45 || $code === 48 => [
                'name' => 'Foggy',
                'icon' => '🌫️',
                'is_rainy' => false,
            ],
            ($code >= 51 && $code <= 55) || ($code >= 61 && $code <= 63) || ($code >= 80 && $code <= 81) => [
                'name' => 'Rainy',
                'icon' => '🌧️',
                'is_rainy' => true,
            ],
            $code >= 65 && $code <= 67 || $code === 82 => [
                'name' => 'Heavy Rain',
                'icon' => '⛈️',
                'is_rainy' => true,
            ],
            $code >= 95 && $code <= 99 => [
                'name' => 'Thunderstorm',
                'icon' => '🌩️',
                'is_rainy' => true,
            ],
            default => [
                'name' => 'Fair',
                'icon' => '🌤️',
                'is_rainy' => false,
            ],
        };
    }

    /**
     * Fallback forecast if network is unreachable.
     */
    protected function getFallbackForecast(int $days): array
    {
        $result = [];
        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::today()->addDays($i);
            $result[] = $this->generateDefaultDayData($date);
        }

        return [
            'location' => 'Hinaguan Nature Park',
            'days' => $result,
            'source' => 'fallback',
        ];
    }

    protected function generateDefaultDayData(Carbon $date): array
    {
        $isWeekend = $date->isWeekend();

        return [
            'date' => $date->toDateString(),
            'day_name' => $date->format('D'),
            'full_day_name' => $date->format('l'),
            'is_today' => $date->isToday(),
            'daytime' => [
                'condition' => 'Sunny',
                'icon' => '☀️',
                'temp' => 31.0,
                'precip_prob' => 15,
                'is_rainy' => false,
            ],
            'nighttime' => [
                'condition' => 'Partly Cloudy',
                'icon' => '🌙',
                'temp' => 25.5,
                'precip_prob' => 20,
                'is_rainy' => false,
            ],
        ];
    }

    protected function getMostFrequent(array $array): int
    {
        if (empty($array)) {
            return 0;
        }
        $values = array_count_values($array);
        arsort($values);
        return (int) array_key_first($values);
    }
}
