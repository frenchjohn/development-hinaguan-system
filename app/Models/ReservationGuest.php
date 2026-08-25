<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationGuest extends Model
{
    use HasFactory;

    protected $table = 'reservation_guests';

    protected $fillable = [
        'reservation_id',
        'customer_id',
        'is_primary_guest',
        'has_pool_access',
        'checked_out_at',
    ];

    protected $casts = [
        'is_primary_guest' => 'boolean',
        'has_pool_access' => 'boolean',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
