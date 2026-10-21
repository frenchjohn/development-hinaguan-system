<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FeedbackToken extends Model
{
    use HasFactory;

    protected $table = 'feedback_tokens';

    protected $fillable = [
        'token',
        'customer_id',
        'reservation_id',
        'feedback_id',
        'expires_at',
        'submitted_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function feedback()
    {
        return $this->belongsTo(Feedback::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null || $this->feedback_id !== null;
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isSubmitted();
    }

    /**
     * Create or retrieve a single-use 24-hour feedback token for checkout.
     */
    public static function createForCheckout(Customer $customer, ?Reservation $reservation = null, int $hoursValid = 24): self
    {
        // Reuse an existing valid unused token if one is still active
        $existing = static::where('customer_id', $customer->id)
            ->when($reservation, fn ($q) => $q->where('reservation_id', $reservation->id))
            ->whereNull('submitted_at')
            ->whereNull('feedback_id')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        return static::create([
            'token' => Str::random(48),
            'customer_id' => $customer->id,
            'reservation_id' => $reservation?->id,
            'expires_at' => now()->addHours($hoursValid),
        ]);
    }
}
