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
        int|string|null $staffId = null,
        float|int|string $paymentAmount = 0.00,
        array $metadata = [],
        ?string $action = null
    ): self {
        $authUser = session('auth_user') ?? [];
        
        $resolvedActorName = $actorName ?: ($authUser['name'] ?? 'Staff User');
        $resolvedActorRole = $actorRole ?: ($authUser['role'] ?? 'staff');

        // Resolve staff_id if actor is staff
        $resolvedStaffId = (isset($staffId) && is_numeric($staffId)) ? (int) $staffId : null;
        if ($resolvedStaffId === null && $resolvedActorRole === 'staff' && isset($authUser['id']) && is_numeric($authUser['id'])) {
            $resolvedStaffId = (int) $authUser['id'];
        }

        // Verify foreign key integrity to avoid 1452 Integrity Constraint Violations
        if ($resolvedStaffId !== null) {
            try {
                if (!\App\Models\StaffAccount::where('id', $resolvedStaffId)->exists()) {
                    $resolvedStaffId = null;
                }
            } catch (\Throwable $e) {
                $resolvedStaffId = null;
            }
        }

        if ($reservationId !== null) {
            try {
                if (!\App\Models\Reservation::where('id', $reservationId)->exists()) {
                    $reservationId = null;
                }
            } catch (\Throwable $e) {
                $reservationId = null;
            }
        }

        // Action and activity_type synchronization
        $finalAction = $action ?: ($activityType ?: 'activity');
        $finalActivityType = $activityType ?: $finalAction;
        $finalPaymentAmount = (float) $paymentAmount;

        try {
            return self::create([
                'staff_id' => $resolvedStaffId,
                'reservation_id' => $reservationId,
                'action' => substr($finalAction, 0, 64),
                'activity_type' => substr($finalActivityType, 0, 64),
                'payment_amount' => $finalPaymentAmount,
                'title' => substr($title, 0, 128),
                'description' => $description,
                'actor_name' => substr($resolvedActorName, 0, 128),
                'actor_role' => substr($resolvedActorRole, 0, 32),
                'metadata' => $metadata,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('ActivityLog::log could not be created: ' . $e->getMessage(), [
                'action' => $finalAction,
                'reservation_id' => $reservationId,
                'staff_id' => $resolvedStaffId,
            ]);
            return new self();
        }
    }
}
