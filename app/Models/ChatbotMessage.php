<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotMessage extends Model
{
    protected $table = 'chatbot_messages';

    protected $fillable = [
        'user_type',
        'user_id',
        'role',
        'content',
        'model',
    ];

    /**
     * Scope query to a specific user and role type.
     */
    public function scopeForUser($query, string $userType, int $userId)
    {
        return $query->where('user_type', $userType)->where('user_id', $userId);
    }
}
