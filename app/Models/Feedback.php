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

    public function images(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FeedbackImage::class, 'feedback_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->full_name;
    }

    public function getAiAnalysisAttribute(): array
    {
        return app(\App\Services\FeedbackAiService::class)->analyzeSentiment($this);
    }

    public function getAiSentimentAttribute(): string
    {
        return $this->ai_analysis['sentiment'] ?? 'neutral';
    }

    public function getAiSentimentLabelAttribute(): string
    {
        return $this->ai_analysis['label'] ?? 'Neutral';
    }

    public function getAiSentimentEmojiAttribute(): string
    {
        return $this->ai_analysis['emoji'] ?? '🟡';
    }

    public function getAiSummaryAttribute(): string
    {
        return $this->ai_analysis['summary'] ?? $this->description;
    }

    public function getAiToneAttribute(): string
    {
        return $this->ai_analysis['tone'] ?? 'General';
    }

    public function getAiExplanationAttribute(): string
    {
        return $this->ai_analysis['explanation'] ?? '';
    }

    public function getAiPointsAttribute(): array
    {
        return $this->ai_analysis['points'] ?? [];
    }

    protected static function booted(): void
    {
        static::deleting(function (Feedback $feedback) {
            foreach ($feedback->images as $image) {
                $image->delete();
            }
        });
    }
}
