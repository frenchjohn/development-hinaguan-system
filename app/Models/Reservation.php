<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $table = 'reservations';

    public function reservationGuests()
    {
        return $this->hasMany(ReservationGuest::class, 'reservation_id');
    }

    protected $fillable = [
        'id',
        'booker_name',
        'phone',
        'email',
        'reservation_date',
        'end_date',
        'start_slot',
        'end_slot',
        'total_days',
        'check_in',
        'check_out',
        'number_of_guests',
        'reservation_type',
        'status',
        'total_amount',
        'amount_paid',
        'remaining_balance',
        'payment_status',
        'payment_intent_id',
        'payment_method',
    ];

    protected $casts = [
        'reservation_date' => 'datetime',
        'end_date' => 'datetime',
        'total_days' => 'integer',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
    ];

    public function reservationAmenities()
    {
        return $this->hasMany(ReservationAmenity::class, 'reservation_id');
    }

    public function entranceFee()
    {
        return $this->hasOne(ReservationEntranceFee::class, 'reservation_id');
    }
}
