<?php

namespace App\Services;

use App\Models\DailyWeatherShiftLog;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VisitorPredictionService
{
    protected WeatherForecastService $weatherService;

    // Fallback baseline guest counts if historical database is brand new
    protected array $defaultBaselines = [
        'Daytime' => [
            0 => 95, // Sunday
            1 => 18, // Monday
            2 => 22, // Tuesday
            3 => 25, // Wednesday
            4 => 28, // Thursday
            5 => 50, // Friday
            6 => 90, // Saturday
        ],
        'Nighttime' => [
            0 => 40, // Sunday
            1 => 8,  // Monday
            2 => 10, // Tuesday
            3 => 12, // Wednesday
            4 => 15, // Thursday
            5 => 30, // Friday
            6 => 45, // Saturday
        ],
    ];

    // Default weather multipliers
    protected array $weatherMultipliers = [
        'Sunny' => 1.00,
        'Fair' => 0.98,
        'Partly Cloudy' => 0.92,
        'Cloudy' => 0.82,
        'Foggy' => 0.75,
        'Rainy' => 0.40,
        'Heavy Rain' => 0.18,
        'Thunderstorm' => 0.08,
    ];

    public function __construct(?WeatherForecastService $weatherService = null)
    {
        $this->weatherService = $weatherService ?? new WeatherForecastService();
    }

    /**
     * Philippine standard holidays list (Month-Day or recurring)
     */
    protected array $fixedHolidays = [
        '01-01' => 'New Year\'s Day',
        '04-09' => 'Araw ng Kagitingan',
        '05-01' => 'Labor Day',
        '06-12' => 'Independence Day',
        '08-21' => 'Ninoy Aquino Day',
        '08-31' => 'National Heroes Day',
        '11-01' => 'All Saints\' Day',
        '11-02' => 'All Souls\' Day',
        '11-30' => 'Bonifacio Day',
        '12-08' => 'Feast of the Immaculate Conception',
        '12-24' => 'Christmas Eve',
        '12-25' => 'Christmas Day',
        '12-30' => 'Rizal Day',
        '12-31' => 'New Year\'s Eve',
    ];

    /**
     * Generate full prediction report for a given date (or today).
     */
    public function predictForDate(?string $dateStr = null): array
    {
        $date = $dateStr ? Carbon::parse($dateStr) : Carbon::today();
        $forecast = $this->weatherService->getShiftForecastForDate($date->toDateString());

        $daytimeWeather = $forecast['daytime']['condition'] ?? 'Sunny';
        $nighttimeWeather = $forecast['nighttime']['condition'] ?? 'Partly Cloudy';

        // Check for holidays and park events
        $holidayInfo = $this->detectHolidayOrEvent($date);

        $daytime = $this->predictShift($date, 'Daytime', $daytimeWeather, $forecast['daytime'] ?? [], $holidayInfo);
        $nighttime = $this->predictShift($date, 'Nighttime', $nighttimeWeather, $forecast['nighttime'] ?? [], $holidayInfo);

        // Next 7 days summary outlook
        $weekForecast = $this->weatherService->getForecast(7);
        $outlookDays = [];
        foreach ($weekForecast['days'] ?? [] as $dayInfo) {
            $dayDate = Carbon::parse($dayInfo['date']);
            $dayHoliday = $this->detectHolidayOrEvent($dayDate);
            $dayShiftPred = $this->predictShift($dayDate, 'Daytime', $dayInfo['daytime']['condition'], $dayInfo['daytime'], $dayHoliday);
            $nightShiftPred = $this->predictShift($dayDate, 'Nighttime', $dayInfo['nighttime']['condition'], $dayInfo['nighttime'], $dayHoliday);

            $outlookDays[] = [
                'date' => $dayInfo['date'],
                'day_name' => $dayInfo['day_name'],
                'full_day_name' => $dayInfo['full_day_name'],
                'is_today' => $dayInfo['is_today'],
                'is_holiday' => $dayHoliday['is_holiday'],
                'holiday_name' => $dayHoliday['name'],
                'daytime_weather' => $dayInfo['daytime']['condition'],
                'daytime_icon' => $dayInfo['daytime']['icon'],
                'daytime_temp' => $dayInfo['daytime']['temp'],
                'daytime_predicted' => $dayShiftPred['predicted_guests'],
                'nighttime_weather' => $dayInfo['nighttime']['condition'],
                'nighttime_icon' => $dayInfo['nighttime']['icon'],
                'nighttime_temp' => $dayInfo['nighttime']['temp'],
                'nighttime_predicted' => $nightShiftPred['predicted_guests'],
                'total_predicted' => $dayShiftPred['predicted_guests'] + $nightShiftPred['predicted_guests'],
            ];
        }

        return [
            'date' => $date->toDateString(),
            'formatted_date' => $date->format('F d, Y'),
            'day_name' => $date->format('l'),
            'is_weekend' => $date->isWeekend(),
            'is_holiday' => $holidayInfo['is_holiday'],
            'holiday_name' => $holidayInfo['name'],
            'has_special_event' => $holidayInfo['is_event'],
            'event_name' => $holidayInfo['event_title'],
            'daytime' => $daytime,
            'nighttime' => $nighttime,
            'total_day_predicted' => $daytime['predicted_guests'] + $nighttime['predicted_guests'],
            'outlook' => $outlookDays,
        ];
    }

    /**
     * Detect if date is a Philippine holiday or scheduled park event.
     */
    public function detectHolidayOrEvent(Carbon $date): array
    {
        $md = $date->format('m-d');
        $holidayName = $this->fixedHolidays[$md] ?? null;

        $event = null;
        try {
            $event = \App\Models\ParkEvent::query()
                ->where('is_active', true)
                ->whereDate('date', $date->toDateString())
                ->first();
        } catch (\Throwable $e) {
            // ignore if table doesn't exist
        }

        return [
            'is_holiday' => $holidayName !== null || $event !== null,
            'is_event' => $event !== null,
            'name' => $holidayName ?? ($event ? $event->title : null),
            'event_title' => $event?->title,
        ];
    }

    /**
     * Predict traffic for a single shift (Daytime or Nighttime).
     */
    public function predictShift(Carbon $date, string $shift, string $weatherCondition, array $weatherMeta = [], ?array $holidayInfo = null): array
    {
        $dayOfWeek = $date->dayOfWeek; // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
        $holidayInfo = $holidayInfo ?? $this->detectHolidayOrEvent($date);

        // 1. Calculate Historical Baseline for this Shift & Day of Week
        $historicalStats = $this->getHistoricalStats($dayOfWeek, $shift);
        $sampleCount = $historicalStats['sample_count'];
        $hasSufficientData = ($sampleCount >= 3);

        // Baseline sunny average
        if ($historicalStats['sunny_avg'] > 0 && $hasSufficientData) {
            $baselineSunnyAvg = $historicalStats['sunny_avg'];
        } else {
            // If it's a holiday, use high weekend/Sunday baseline
            $effectiveDayIndex = $holidayInfo['is_holiday'] ? 0 : $dayOfWeek;
            $baselineSunnyAvg = $this->defaultBaselines[$shift][$effectiveDayIndex] ?? 20;
        }

        // If it's a holiday on a weekday and we have weekday history, boost it to weekend level
        if ($holidayInfo['is_holiday'] && !$date->isWeekend() && $hasSufficientData) {
            $baselineSunnyAvg = max($baselineSunnyAvg, ($shift === 'Daytime' ? 85 : 35));
        }

        // 2. Weather Impact Multiplier
        $multiplier = $this->getWeatherMultiplier($weatherCondition, $dayOfWeek, $shift);
        $predictedGuests = max(1, (int) round($baselineSunnyAvg * $multiplier));
        $penaltyPercent = (int) round(($multiplier - 1.0) * 100);

        // Range estimate (low / high)
        $rangeMin = max(1, (int) floor($predictedGuests * 0.8));
        $rangeMax = (int) ceil($predictedGuests * 1.25);

        // 3. Arrival Time & Peak Check-in Predictions
        $arrivalTimes = $this->estimateArrivalTimes($shift, $dayOfWeek, $holidayInfo['is_holiday']);

        // 4. Crowd Level Category
        $crowdLevel = $this->getCrowdLevel($predictedGuests, $shift);

        // 5. Data confidence description
        $confidenceStatus = match (true) {
            $sampleCount >= 6 => [
                'label' => 'High Accuracy',
                'badge' => 'Reliable Past Data',
                'description' => "Based on {$sampleCount} past {$date->format('l')} records.",
                'sufficient' => true,
            ],
            $sampleCount >= 3 => [
                'label' => 'Moderate Accuracy',
                'badge' => 'Sufficient Data',
                'description' => "Based on {$sampleCount} past {$date->format('l')} records.",
                'sufficient' => true,
            ],
            $sampleCount > 0 => [
                'label' => 'Learning Phase',
                'badge' => 'Limited Data (' . $sampleCount . ' sample)',
                'description' => "Only {$sampleCount} past {$date->format('l')} logged so far. Using initial baseline while accumulating logs.",
                'sufficient' => false,
            ],
            default => [
                'label' => 'Baseline Estimate',
                'badge' => 'No Past ' . $date->format('l') . ' Data Yet',
                'description' => "Not enough past {$date->format('l')} logs in database yet. Using park standard baseline until attendance logs accumulate.",
                'sufficient' => false,
            ],
        };

        // 6. Explanatory Insight
        $explanation = $this->buildExplanation(
            $date->format('l'),
            $shift,
            $weatherCondition,
            $predictedGuests,
            (int) round($baselineSunnyAvg),
            $penaltyPercent,
            $holidayInfo,
            $confidenceStatus
        );

        return [
            'shift' => $shift,
            'day_name' => $date->format('l'),
            'weather' => $weatherCondition,
            'weather_icon' => $weatherMeta['icon'] ?? '',
            'temperature' => $weatherMeta['temp'] ?? 28.0,
            'precipitation_probability' => $weatherMeta['precip_prob'] ?? 10,
            'is_rainy' => $weatherMeta['is_rainy'] ?? in_array($weatherCondition, ['Rainy', 'Heavy Rain', 'Thunderstorm']),
            'predicted_guests' => $predictedGuests,
            'range_min' => $rangeMin,
            'range_max' => $rangeMax,
            'baseline_sunny_avg' => (int) round($baselineSunnyAvg),
            'weather_multiplier' => $multiplier,
            'weather_impact_percent' => $penaltyPercent,
            'earliest_arrival' => $arrivalTimes['earliest'],
            'peak_arrival_window' => $arrivalTimes['peak_window'],
            'crowd_level' => $crowdLevel['label'],
            'crowd_badge_class' => $crowdLevel['badge_class'],
            'crowd_color' => $crowdLevel['color'],
            'is_holiday' => $holidayInfo['is_holiday'],
            'holiday_name' => $holidayInfo['name'],
            'confidence' => $confidenceStatus['label'],
            'confidence_badge' => $confidenceStatus['badge'],
            'confidence_description' => $confidenceStatus['description'],
            'has_sufficient_data' => $confidenceStatus['sufficient'],
            'historical_samples' => $sampleCount,
            'explanation' => $explanation,
        ];
    }

    /**
     * Compute historical sunny baseline from daily_weather_shift_logs and past reservations.
     */
    protected function getHistoricalStats(int $dayOfWeek, string $shift): array
    {
        try {
            $driver = DB::connection()->getDriverName();
            $query = DailyWeatherShiftLog::query()->where('shift', $shift);

            if ($driver === 'sqlite') {
                $query->whereRaw("CAST(strftime('%w', log_date) AS INTEGER) = ?", [$dayOfWeek]);
            } else {
                $query->whereRaw('DAYOFWEEK(log_date) = ?', [$dayOfWeek + 1]); // MySQL DAYOFWEEK is 1=Sun, 7=Sat
            }

            $logs = $query->get();

            if ($logs->isNotEmpty()) {
                $sunnyLogs = $logs->filter(fn($l) => in_array($l->weather_condition, ['Sunny', 'Fair', 'Partly Cloudy']));
                $sunnyAvg = $sunnyLogs->isNotEmpty() ? $sunnyLogs->avg('actual_guests') : $logs->avg('actual_guests');

                return [
                    'sunny_avg' => $sunnyAvg ? round($sunnyAvg) : 0,
                    'sample_count' => $logs->count(),
                ];
            }
        } catch (\Throwable $e) {
            // fallback
        }

        return [
            'sunny_avg' => 0,
            'sample_count' => 0,
        ];
    }

    /**
     * Get weather multiplier based on empirical data or historical correlation.
     */
    protected function getWeatherMultiplier(string $condition, int $dayOfWeek, string $shift): float
    {
        // Check if we have empirical historical log differences
        try {
            $conditionAvg = DailyWeatherShiftLog::query()
                ->where('shift', $shift)
                ->where('weather_condition', $condition)
                ->avg('actual_guests');

            $sunnyAvg = DailyWeatherShiftLog::query()
                ->where('shift', $shift)
                ->whereIn('weather_condition', ['Sunny', 'Fair', 'Partly Cloudy'])
                ->avg('actual_guests');

            if ($conditionAvg !== null && $sunnyAvg !== null && $sunnyAvg > 0) {
                $empiricalFactor = $conditionAvg / $sunnyAvg;
                // Bound multiplier between 0.05 and 1.5
                return max(0.05, min(1.5, round($empiricalFactor, 2)));
            }
        } catch (\Throwable $e) {
            // fallback
        }

        return $this->weatherMultipliers[$condition] ?? 0.85;
    }

    /**
     * Estimate customer arrival starting hour and peak check-in window.
     */
    protected function estimateArrivalTimes(string $shift, int $dayOfWeek, bool $isHoliday = false): array
    {
        // In Daytime, arrivals typically begin at 8:30 AM / 9:00 AM, peak around 10:30 AM - 1:30 PM (lunch & pool)
        // In Nighttime, arrivals typically begin at 6:00 PM / 6:15 PM, peak around 6:45 PM - 8:30 PM (dinner & night swim)
        if ($shift === 'Daytime') {
            $isPeakDay = ($dayOfWeek === 0 || $dayOfWeek === 6 || $isHoliday);
            return [
                'earliest' => $isPeakDay ? '08:30 AM' : '09:15 AM',
                'peak_window' => $isPeakDay ? '10:00 AM – 2:00 PM' : '11:00 AM – 1:30 PM',
            ];
        }

        // Nighttime
        $isPeakDay = ($dayOfWeek === 0 || $dayOfWeek === 5 || $dayOfWeek === 6 || $isHoliday);
        return [
            'earliest' => $isPeakDay ? '05:45 PM' : '06:15 PM',
            'peak_window' => $isPeakDay ? '06:30 PM – 9:00 PM' : '06:45 PM – 8:30 PM',
        ];
    }

    /**
     * Categorize predicted visitor counts into friendly crowd levels.
     */
    protected function getCrowdLevel(int $guests, string $shift): array
    {
        if ($shift === 'Daytime') {
            if ($guests >= 60) {
                return ['label' => 'Heavy Crowd', 'badge_class' => 'badge-danger', 'color' => '#dc2626'];
            } elseif ($guests >= 35) {
                return ['label' => 'Moderate Crowd', 'badge_class' => 'badge-warning', 'color' => '#d97706'];
            } elseif ($guests >= 15) {
                return ['label' => 'Steady', 'badge_class' => 'badge-info', 'color' => '#2563eb'];
            }
            return ['label' => 'Light', 'badge_class' => 'badge-success', 'color' => '#16a34a'];
        }

        // Nighttime thresholds
        if ($guests >= 30) {
            return ['label' => 'Heavy Night Crowd', 'badge_class' => 'badge-danger', 'color' => '#dc2626'];
        } elseif ($guests >= 15) {
            return ['label' => 'Moderate Night Crowd', 'badge_class' => 'badge-warning', 'color' => '#d97706'];
        }
        return ['label' => 'Light Night Crowd', 'badge_class' => 'badge-success', 'color' => '#16a34a'];
    }

    /**
     * Generate plain english explanation for staff and admin.
     */
    protected function buildExplanation(
        string $dayName,
        string $shift,
        string $weather,
        int $predicted,
        int $baseline,
        int $penaltyPercent,
        array $holidayInfo,
        array $confidenceStatus
    ): string {
        $prefix = '';
        if ($holidayInfo['is_holiday']) {
            $prefix = "Holiday ({$holidayInfo['name']}): ";
        }

        if (!$confidenceStatus['sufficient']) {
            return "{$prefix}{$confidenceStatus['description']} Estimated ~{$predicted} {$shift} visitors under {$weather} conditions.";
        }

        if ($penaltyPercent < 0) {
            $absPenalty = abs($penaltyPercent);
            return "{$prefix}Based on past {$dayName} records (sunny average ~{$baseline} guests), {$weather} forecast reduces projected {$shift} turnout by {$absPenalty}% to ~{$predicted} visitors.";
        } elseif ($penaltyPercent > 0) {
            return "{$prefix}Based on past {$dayName} records (sunny average ~{$baseline} guests), favorable {$weather} conditions increase projected {$shift} turnout by {$penaltyPercent}% to ~{$predicted} visitors.";
        }

        return "{$prefix}Projected {$shift} attendance is on track with typical {$dayName} numbers (~{$predicted} visitors) under {$weather} conditions.";
    }

    /**
     * Record actual attendance and weather for a completed date/shift.
     * Automatically called at the end of the day or when sync runs.
     */
    public function recordShiftLog(string $date, string $shift, ?string $weatherCondition = null, ?float $temperature = null): DailyWeatherShiftLog
    {
        $carbonDate = Carbon::parse($date);

        // Fetch weather if not provided
        if (!$weatherCondition) {
            $weather = $this->weatherService->getShiftForecastForDate($carbonDate->toDateString());
            $weatherCondition = $shift === 'Daytime' ? ($weather['daytime']['condition'] ?? 'Sunny') : ($weather['nighttime']['condition'] ?? 'Partly Cloudy');
            $temperature = $shift === 'Daytime' ? ($weather['daytime']['temperature'] ?? 28.0) : ($weather['nighttime']['temperature'] ?? 26.0);
        }

        // Query all reservations that checked in on this date for this shift
        $reservations = Reservation::query()
            ->whereDate('reservation_date', $carbonDate->toDateString())
            ->whereIn('status', ['Checked In', 'Checked Out'])
            ->whereNotNull('check_in')
            ->get();

        // Filter by shift
        $shiftReservations = $reservations->filter(function ($r) use ($shift) {
            $slot = $r->start_slot ?: ($r->entranceFee?->pricing_type ?: 'Daytime');
            if ($shift === 'Daytime') {
                return !str_contains(strtolower($slot), 'night');
            } else {
                return str_contains(strtolower($slot), 'night');
            }
        });

        $actualGuests = (int) $shiftReservations->sum('number_of_guests');
        $actualReservations = $shiftReservations->count();

        // Determine earliest check-in time
        $checkInTimes = $shiftReservations->map(fn($r) => $r->check_in ? Carbon::parse($r->check_in) : null)->filter();
        $earliestCheckIn = $checkInTimes->isNotEmpty() ? $checkInTimes->min()->format('H:i:s') : ($shift === 'Daytime' ? '09:00:00' : '18:15:00');
        $peakArrival = $shift === 'Daytime' ? '11:00:00' : '19:00:00';

        $existing = DailyWeatherShiftLog::query()
            ->whereDate('log_date', $carbonDate->toDateString())
            ->where('shift', $shift)
            ->first();

        $data = [
            'log_date' => $carbonDate->toDateString(),
            'shift' => $shift,
            'weather_condition' => $weatherCondition ?: 'Sunny',
            'temperature_celsius' => $temperature ?: 28.0,
            'precipitation_probability' => $weather['daytime']['precip_prob'] ?? 10,
            'is_weekend' => $carbonDate->isWeekend(),
            'is_holiday' => $this->detectHolidayOrEvent($carbonDate)['is_holiday'],
            'actual_guests' => $actualGuests,
            'actual_reservations' => $actualReservations,
            'earliest_arrival_time' => $earliestCheckIn,
            'peak_arrival_time' => $peakArrival,
        ];

        if ($existing) {
            $existing->update($data);
            return $existing;
        }

        return DailyWeatherShiftLog::create($data);
    }

    /**
     * Sync yesterday's and past completed shifts so database is always up to date.
     */
    public function syncRecentShiftLogs(int $daysBack = 7): void
    {
        for ($i = 1; $i <= $daysBack; $i++) {
            $pastDate = Carbon::today()->subDays($i)->toDateString();
            $this->recordShiftLog($pastDate, 'Daytime');
            $this->recordShiftLog($pastDate, 'Nighttime');
        }
    }
}

