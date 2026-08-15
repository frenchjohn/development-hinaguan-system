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
        'start_date',
        'end_date',
        'start_slot',
        'end_slot',
        'day_slots_count',
        'night_slots_count',
        'pricing_type',
        'price_at_booking',
        'quantity',
        'remarks',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'day_slots_count' => 'integer',
        'night_slots_count' => 'integer',
        'price_at_booking' => 'decimal:2',
    ];

    public function amenity()
    {
        return $this->belongsTo(Amenity::class, 'amenity_id');
    }
}
