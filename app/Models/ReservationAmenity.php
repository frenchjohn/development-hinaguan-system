<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationAmenity extends Model
{
    use HasFactory;

    protected $table = 'reservation_amenities';

    protected $fillable = [
        'reservation_id',
        'amenity_id',
        'pricing_type',
        'price_at_booking',
        'quantity',
        'remarks',
        'status',
    ];

    public function amenity()
    {
        return $this->belongsTo(Amenity::class, 'amenity_id');
    }
}
