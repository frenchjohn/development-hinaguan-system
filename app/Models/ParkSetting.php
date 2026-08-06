<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkSetting extends Model
{
    protected $fillable = [
        'contact_number',
        'email',
        'opening_time',
        'closing_time',
        'daytime_start',
        'daytime_end',
        'nighttime_start',
        'nighttime_end',
        'daytime_adult_entrance_fee',
        'daytime_child_entrance_fee',
        'nighttime_adult_entrance_fee',
        'nighttime_child_entrance_fee',
        'day_pool_fee',
        'night_pool_fee',
        'facebook_link',
    ];

    protected $table = 'park_settings';
}
