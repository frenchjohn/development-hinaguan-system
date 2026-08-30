<?php

namespace Database\Seeders;

use App\Models\DailyWeatherShiftLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DailyWeatherShiftLogSeeder extends Seeder
{
    public function run(): void
    {
        $startDate = Carbon::today()->subDays(60);

        for ($i = 0; $i < 60; $i++) {
            $date = $startDate->copy()->addDays($i);
            $isWeekend = $date->isWeekend();
            $dayOfWeek = $date->dayOfWeek;

            // Deterministic pseudo-random weather pattern based on day
            $weatherRoll = ($i * 7 + 3) % 100;
            if ($weatherRoll < 60) {
                $dayWeather = 'Sunny';
                $dayRainProb = 10;
                $dayTemp = 31.5;
            } elseif ($weatherRoll < 80) {
                $dayWeather = 'Partly Cloudy';
                $dayRainProb = 25;
                $dayTemp = 29.8;
            } elseif ($weatherRoll < 95) {
                $dayWeather = 'Rainy';
                $dayRainProb = 75;
                $dayTemp = 26.5;
            } else {
                $dayWeather = 'Heavy Rain';
                $dayRainProb = 90;
                $dayTemp = 24.5;
            }

            $nightWeatherRoll = ($i * 11 + 5) % 100;
            if ($nightWeatherRoll < 65) {
                $nightWeather = 'Sunny';
                $nightRainProb = 10;
                $nightTemp = 26.0;
            } elseif ($nightWeatherRoll < 85) {
                $nightWeather = 'Partly Cloudy';
                $nightRainProb = 20;
                $nightTemp = 25.2;
            } else {
                $nightWeather = 'Rainy';
                $nightRainProb = 80;
                $nightTemp = 23.5;
            }

            // Calculate realistic visitor counts based on conditions
            $dayGuests = match ($dayWeather) {
                'Sunny' => $isWeekend ? rand(75, 115) : rand(20, 38),
                'Partly Cloudy' => $isWeekend ? rand(65, 95) : rand(18, 32),
                'Rainy' => $isWeekend ? rand(20, 42) : rand(4, 14),
                'Heavy Rain' => $isWeekend ? rand(8, 20) : rand(1, 6),
                default => 15,
            };

            $nightGuests = match ($nightWeather) {
                'Sunny' => $isWeekend ? rand(35, 55) : rand(10, 22),
                'Partly Cloudy' => $isWeekend ? rand(30, 45) : rand(8, 18),
                'Rainy' => $isWeekend ? rand(10, 20) : rand(2, 7),
                default => 8,
            };

            // Daytime log
            DailyWeatherShiftLog::updateOrCreate(
                [
                    'log_date' => $date->toDateString(),
                    'shift' => 'Daytime',
                ],
                [
                    'weather_condition' => $dayWeather,
                    'temperature_celsius' => $dayTemp,
                    'precipitation_probability' => $dayRainProb,
                    'actual_guests' => $dayGuests,
                    'actual_reservations' => max(1, (int) round($dayGuests / 4)),
                    'earliest_arrival_time' => $isWeekend ? '08:30:00' : '09:15:00',
                    'peak_arrival_time' => $isWeekend ? '10:45:00' : '11:30:00',
                    'latest_arrival_time' => '16:30:00',
                    'is_weekend' => $isWeekend,
                    'is_holiday' => false,
                    'notes' => "Historical log for {$date->format('Y-m-d')} Daytime",
                ]
            );

            // Nighttime log
            DailyWeatherShiftLog::updateOrCreate(
                [
                    'log_date' => $date->toDateString(),
                    'shift' => 'Nighttime',
                ],
                [
                    'weather_condition' => $nightWeather,
                    'temperature_celsius' => $nightTemp,
                    'precipitation_probability' => $nightRainProb,
                    'actual_guests' => $nightGuests,
                    'actual_reservations' => max(1, (int) round($nightGuests / 4)),
                    'earliest_arrival_time' => $isWeekend ? '17:45:00' : '18:15:00',
                    'peak_arrival_time' => $isWeekend ? '19:15:00' : '19:45:00',
                    'latest_arrival_time' => '22:00:00',
                    'is_weekend' => $isWeekend,
                    'is_holiday' => false,
                    'notes' => "Historical log for {$date->format('Y-m-d')} Nighttime",
                ]
            );
        }
    }
}
