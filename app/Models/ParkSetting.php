<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkSetting extends Model
{
    protected $fillable = [
        'contact_number',
        'email',
        'park_status',
        'close_description',
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

    /**
     * Check if park is currently set to Open.
     */
    public function isOpen(): bool
    {
        return ($this->park_status ?? 'open') === 'open';
    }

    /**
     * Check if park is currently set to Closed.
     */
    public function isClosed(): bool
    {
        return ($this->park_status ?? 'open') === 'closed';
    }
}
