<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyWeatherShiftLog extends Model
{
    use HasFactory;

    protected $table = 'daily_weather_shift_logs';

    protected $fillable = [
        'log_date',
        'shift',
        'weather_condition',
        'temperature_celsius',
        'precipitation_probability',
        'actual_guests',
        'actual_reservations',
        'earliest_arrival_time',
        'peak_arrival_time',
        'latest_arrival_time',
        'is_weekend',
        'is_holiday',
        'notes',
    ];

    protected $casts = [
        'log_date' => 'date',
        'temperature_celsius' => 'decimal:2',
        'precipitation_probability' => 'integer',
        'actual_guests' => 'integer',
        'actual_reservations' => 'integer',
        'is_weekend' => 'boolean',
        'is_holiday' => 'boolean',
    ];
}
