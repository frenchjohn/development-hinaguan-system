<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RescheduleRequest extends Model
{
    use HasFactory;

    protected $table = 'reschedule_requests';

    protected $fillable = [
        'reservation_id',
        'token',
        'original_date',
        'requested_date',
        'status',
        'expires_at',
        'used_at',
        'approved_by',
        'approved_at',
        'reason',
        'decline_reason',
    ];

    protected $casts = [
        'original_date' => 'date:Y-m-d',
        'requested_date' => 'date:Y-m-d',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(StaffAccount::class, 'approved_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isDeclined(): bool
    {
        return $this->status === 'declined';
    }
}
