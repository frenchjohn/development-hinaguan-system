<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedbacks';

    protected $fillable = [
        'full_name',
        'is_anonymous',
        'description',
        'stars',
        'is_shown',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'is_shown' => 'boolean',
        'stars' => 'integer',
    ];

    public const ANONYMOUS_NAME = 'Anonymous Guest';

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_shown', true);
    }

    public function scopeTopRated(Builder $query): Builder
    {
        return $query->orderByDesc('stars')->orderByDesc('created_at');
    }

    public function getInitialsAttribute(): string
    {
        $name = trim($this->full_name);

        if (strcasecmp($name, self::ANONYMOUS_NAME) === 0) {
            return 'AG';
        }

        $parts = preg_split('/\s+/', $name) ?: [];

        if (count($parts) === 1) {
            return strtoupper(substr($parts[0], 0, min(2, strlen($parts[0]))));
        }

        $first = substr($parts[0], 0, 1);
        $last = substr($parts[count($parts) - 1], 0, 1);

        return strtoupper($first.$last);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->full_name;
    }
}
