<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserActivityRead extends Model
{
    protected $table = 'user_activity_reads';

    protected $fillable = [
        'user_type',
        'user_id',
        'last_seen_activity_id',
    ];

    /**
     * Get the last seen activity ID for a given user type & user id.
     */
    public static function getLastSeenId(string $userType, int $userId): int
    {
        return (int) (static::where('user_type', $userType)
            ->where('user_id', $userId)
            ->value('last_seen_activity_id') ?? 0);
    }

    /**
     * Update the last seen activity ID for a given user type & user id.
     */
    public static function setLastSeenId(string $userType, int $userId, int $lastSeenId): static
    {
        return static::updateOrCreate(
            ['user_type' => $userType, 'user_id' => $userId],
            ['last_seen_activity_id' => $lastSeenId]
        );
    }
}
