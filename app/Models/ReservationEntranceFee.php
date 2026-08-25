<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationEntranceFee extends Model
{
    use HasFactory;

    protected $table = 'reservation_entrance_fees';

    protected $fillable = [
        'reservation_id',
        'pricing_type',
        'pool_option',
        'total_amount',
        'pool_fee',
        'pool_access_count',
        'adult_count',
        'child_count',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'pool_fee' => 'decimal:2',
        'pool_access_count' => 'integer',
        'adult_count' => 'integer',
        'child_count' => 'integer',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }
}
