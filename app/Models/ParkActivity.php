<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkActivity extends Model
{
    use HasFactory;

    protected $table = 'park_activities';

    protected $fillable = [
        'activity',
        'description',
        'image',
    ];
}
