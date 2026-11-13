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
        'refunded_amount',
        'remaining_balance',
        'payment_status',
        'payment_intent_id',
        'payment_method',
        'allow_sms',
        'allow_email',
    ];

    protected $casts = [
        'reservation_date' => 'datetime',
        'end_date' => 'datetime',
        'total_days' => 'integer',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'allow_sms' => 'boolean',
        'allow_email' => 'boolean',
    ];

    protected $appends = [
        'formatted_id',
    ];

    /**
     * Format any reservation ID with minimum 3 digits zero-padded (e.g. 1 -> 001, 11 -> 011, 145 -> 145, 1444 -> 1444).
     */
    public static function formatId($id): string
    {
        if ($id === null || $id === '') {
            return '';
        }
        $raw = (string) $id;
        $digits = preg_replace('/\D/', '', $raw);
        if ($digits === '') {
            return $raw;
        }
        return strlen($digits) < 3 ? str_pad($digits, 3, '0', STR_PAD_LEFT) : $digits;
    }

    /**
     * Accessor for formatted_id.
     */
    public function getFormattedIdAttribute(): string
    {
        return self::formatId($this->id);
    }

    /**
     * Determine if the guest has opted in to receive SMS messages.
     */
    public function canReceiveSms(): bool
    {
        return (bool) ($this->allow_sms ?? true);
    }

    /**
     * Determine if the guest has opted in to receive Email notifications.
     */
    public function canReceiveEmail(): bool
    {
        return (bool) ($this->allow_email ?? true);
    }

    public function reservationAmenities()
    {
        return $this->hasMany(ReservationAmenity::class, 'reservation_id');
    }

    public function entranceFee()
    {
        return $this->hasOne(ReservationEntranceFee::class, 'reservation_id');
    }

    public function reservationCharges()
    {
        return $this->hasMany(ReservationCharge::class, 'reservation_id');
    }

    public function rescheduleRequests()
    {
        return $this->hasMany(RescheduleRequest::class, 'reservation_id')->orderByDesc('id');
    }
}
