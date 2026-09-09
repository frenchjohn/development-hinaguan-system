<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'amenity_id',
        'description',
        'charge_type',
        'amount',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function amenity()
    {
        return $this->belongsTo(Amenity::class);
    }
}
