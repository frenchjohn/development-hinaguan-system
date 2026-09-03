<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FeedbackImage extends Model
{
    protected $table = 'feedback_images';

    protected $fillable = [
        'feedback_id',
        'image_path',
    ];

    protected $appends = [
        'image_url',
    ];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class, 'feedback_id');
    }

    public function getImageUrlAttribute(): string
    {
        if (! $this->image_path) {
            return '';
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        return asset('storage/' . ltrim($this->image_path, '/'));
    }

    protected static function booted(): void
    {
        static::deleting(function (FeedbackImage $feedbackImage) {
            if ($feedbackImage->image_path && Storage::disk('public')->exists($feedbackImage->image_path)) {
                Storage::disk('public')->delete($feedbackImage->image_path);
            }
        });
    }
}
