<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'activity_type',
        'title',
        'description',
        'reservation_id',
        'staff_id',
        'actor_name',
        'actor_role',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    /**
     * Helper to log an activity record.
     */
    public static function log(
        string $activityType,
        string $title,
        string $description,
        ?int $reservationId = null,
        ?string $actorName = null,
        ?string $actorRole = null,
        ?string $staffId = null,
        array $metadata = []
    ): self {
        $authUser = session('auth_user') ?? [];
        
        $resolvedActorName = $actorName ?: ($authUser['name'] ?? 'Staff User');
        $resolvedActorRole = $actorRole ?: ($authUser['role'] ?? 'staff');
        $resolvedStaffId = $staffId ?: (isset($authUser['id']) ? (string) $authUser['id'] : null);

        return self::create([
            'activity_type' => $activityType,
            'title' => $title,
            'description' => $description,
            'reservation_id' => $reservationId,
            'staff_id' => $resolvedStaffId,
            'actor_name' => $resolvedActorName,
            'actor_role' => $resolvedActorRole,
            'metadata' => $metadata,
        ]);
    }
}
