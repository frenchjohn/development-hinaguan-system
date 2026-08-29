<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkEvent extends Model
{
    use HasFactory;

    protected $table = 'park_events';

    protected $fillable = [
        'title',
        'date',
        'day',
        'time',
        'event',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Boot model to automatically compute 'day' from 'date' if day is not provided.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($event) {
            if ($event->date && empty($event->day)) {
                $event->day = Carbon::parse($event->date)->format('l');
            }
        });
    }

    /**
     * Scope to retrieve active events.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to retrieve events happening in the current week (Monday to Sunday).
     */
    public function scopeThisWeek($query)
    {
        $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
        $endOfWeek = Carbon::now()->endOfWeek()->toDateString();

        return $query->whereBetween('date', [$startOfWeek, $endOfWeek]);
    }

    /**
     * Scope to retrieve upcoming events from today onward.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', Carbon::now()->toDateString());
    }
}
