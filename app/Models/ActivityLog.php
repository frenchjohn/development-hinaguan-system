<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'staff_id',
        'reservation_id',
        'action',
        'activity_type',
        'payment_amount',
        'title',
        'description',
        'actor_name',
        'actor_role',
        'metadata',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffAccount::class, 'staff_id');
    }

    /**
     * Helper to log an activity record.
     * Supports both $action and legacy $activityType, plus payment amount.
     */
    public static function log(
        ?string $activityType = null,
        string $title = '',
        string $description = '',
        ?int $reservationId = null,
        ?string $actorName = null,
        ?string $actorRole = null,
        ?int $staffId = null,
        float|int|string $paymentAmount = 0.00,
        array $metadata = [],
        ?string $action = null
    ): self {
        $authUser = session('auth_user') ?? [];
        
        $resolvedActorName = $actorName ?: ($authUser['name'] ?? 'Staff User');
        $resolvedActorRole = $actorRole ?: ($authUser['role'] ?? 'staff');

        // Resolve staff_id if actor is staff
        $resolvedStaffId = $staffId;
        if ($resolvedStaffId === null && $resolvedActorRole === 'staff' && isset($authUser['id']) && is_numeric($authUser['id'])) {
            $resolvedStaffId = (int) $authUser['id'];
        }

        // Action and activity_type synchronization
        $finalAction = $action ?: ($activityType ?: 'activity');
        $finalActivityType = $activityType ?: $finalAction;
        $finalPaymentAmount = (float) $paymentAmount;

        return self::create([
            'staff_id' => $resolvedStaffId,
            'reservation_id' => $reservationId,
            'action' => $finalAction,
            'activity_type' => $finalActivityType,
            'payment_amount' => $finalPaymentAmount,
            'title' => $title,
            'description' => $description,
            'actor_name' => $resolvedActorName,
            'actor_role' => $resolvedActorRole,
            'metadata' => $metadata,
        ]);
    }
}
