<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $table = 'announcements';

    protected $fillable = [
        'title',
        'message',
        'category',
        'target_type',
        'recipient_count',
        'target_reservation_ids',
        'recipient_phones',
        'delivery_status',
        'delivery_details',
        'created_by',
    ];

    protected $casts = [
        'target_reservation_ids' => 'array',
        'recipient_phones' => 'array',
        'delivery_details' => 'array',
        'recipient_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
