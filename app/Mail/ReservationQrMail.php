<?php

namespace App\Mail;

use App\Models\ParkSetting;
use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ReservationQrMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation)
    {
        if ($this->reservation->exists) {
            $this->reservation->loadMissing([
                'reservationGuests.customer',
                'reservationAmenities.amenity',
                'entranceFee',
            ]);
        }
    }

    public function envelope(): Envelope
    {
        $fromAddress = config('mail.from.address') ?: config('mail.mailers.smtp.username') ?: 'parkhinaguan@gmail.com';
        $fromName = config('mail.from.name') ?: 'Hinaguan Nature Park';
        $resId = $this->reservation->id ? '#' . $this->reservation->id : '';

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: "Your Booking Confirmation & Entry QR Pass {$resId} - Hinaguan Nature Park",
        );
    }

    public function content(): Content
    {
        $qrPayload = 'reservation_id=' . $this->reservation->id;
        $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrPayload);

        // Fetch park settings for hours and contact info
        $parkSetting = null;
        try {
            $parkSetting = ParkSetting::first();
        } catch (\Throwable $e) {
            // Fallback if db table not ready
        }

        $formatTime = function (?string $raw, string $default): string {
            if (!$raw) return $default;
            try {
                $c = Carbon::parse($raw);
                if ($c->minute === 1) {
                    $c = $c->minute(0)->second(0);
                }
                return $c->format('g:i A');
            } catch (\Throwable $e) {
                return $default;
            }
        };

        $daytimeStart = $formatTime($parkSetting?->opening_time ?: $parkSetting?->daytime_start, '8:00 AM');
        $daytimeEnd = $formatTime($parkSetting?->daytime_end ?: $parkSetting?->closing_time, '6:00 PM');
        $nighttimeStart = $formatTime($parkSetting?->nighttime_start, '6:00 PM');
        $nighttimeEnd = $formatTime($parkSetting?->nighttime_end, '8:00 AM');

        // Guest details resolution
        $bookerName = $this->reservation->booker_name;
        $phone = $this->reservation->phone;
        $email = $this->reservation->email;
        $customerId = null;

        if ($this->reservation->relationLoaded('reservationGuests') && $this->reservation->reservationGuests->isNotEmpty()) {
            $primaryGuest = $this->reservation->reservationGuests->firstWhere('is_primary_guest', true)
                ?: $this->reservation->reservationGuests->first();
            if ($primaryGuest) {
                $customerId = $primaryGuest->customer_id;
                if ($primaryGuest->customer) {
                    if (empty($bookerName)) {
                        $bookerName = trim(($primaryGuest->customer->first_name ?? '') . ' ' . ($primaryGuest->customer->last_name ?? ''));
                    }
                    if (empty($phone)) {
                        $phone = $primaryGuest->customer->phone;
                    }
                    if (empty($email)) {
                        $email = $primaryGuest->customer->email;
                    }
                }
            }
        }

        $bookerName = $bookerName ?: 'Valued Guest';
        $phone = $phone ?: 'Not provided';
        $email = $email ?: 'Not provided';

        // Date resolution
        $startDateRaw = $this->reservation->reservation_date ?: $this->reservation->check_in;
        $startDateFormatted = $startDateRaw ? Carbon::parse($startDateRaw)->format('F d, Y (l)') : 'Upcoming Date';
        $startDateShort = $startDateRaw ? Carbon::parse($startDateRaw)->format('M d, Y') : 'N/A';

        $endDateRaw = $this->reservation->end_date ?: $this->reservation->check_out;
        $endDateFormatted = $endDateRaw ? Carbon::parse($endDateRaw)->format('F d, Y (l)') : $startDateFormatted;
        $endDateShort = $endDateRaw ? Carbon::parse($endDateRaw)->format('M d, Y') : $startDateShort;

        $isMultiDay = $endDateRaw && $startDateRaw && (Carbon::parse($startDateRaw)->toDateString() !== Carbon::parse($endDateRaw)->toDateString());
        $dateDisplay = $isMultiDay ? "{$startDateShort} to {$endDateShort}" : $startDateFormatted;

        // Slot & Arrival Window Resolution
        $startSlot = (string) ($this->reservation->start_slot ?: 'Daytime');
        $endSlot = (string) ($this->reservation->end_slot ?: $startSlot);
        $slotLower = strtolower($startSlot);

        if (str_contains($slotLower, 'nighttoday')) {
            $slotLabel = "Night to Day Stay ({$nighttimeStart} - {$daytimeEnd} next day)";
            $arriveTargetTime = "{$startDateShort} at {$nighttimeStart}";
            $arrivalTimeWindow = "Check-in opens at {$nighttimeStart} on {$startDateShort}";
            $arrivalRecommendation = "Please arrive on {$startDateShort} at {$nighttimeStart} (recommended between {$nighttimeStart} – 8:00 PM). We can still check you in even if you arrive late in the evening, but it's best to be early to enjoy your stay!";
            $departureTime = "Check-out by {$daytimeEnd} on {$endDateShort}";
        } elseif (str_contains($slotLower, 'daytonight')) {
            $slotLabel = "Day to Night Stay ({$daytimeStart} - {$nighttimeEnd} next day)";
            $arriveTargetTime = "{$startDateShort} at {$daytimeStart}";
            $arrivalTimeWindow = "Check-in opens at {$daytimeStart} on {$startDateShort}";
            $arrivalRecommendation = "Please arrive on {$startDateShort} at {$daytimeStart}. We can still check you in even if you arrive late during the day, but arriving early allows you to enjoy both day and night amenities to the fullest!";
            $departureTime = "Check-out by {$nighttimeEnd} on {$endDateShort}";
        } elseif (str_contains($slotLower, 'night') && !str_contains($slotLower, 'day')) {
            $slotLabel = "Nighttime Stay ({$nighttimeStart} - {$nighttimeEnd})";
            $arriveTargetTime = "{$startDateShort} at {$nighttimeStart}";
            $arrivalTimeWindow = "Check-in begins at {$nighttimeStart} on {$startDateShort}";
            $arrivalRecommendation = "Please arrive on {$startDateShort} at {$nighttimeStart} (recommended between {$nighttimeStart} – 8:00 PM). We can still check you in even if you arrive late in the evening, but arriving early gives you more time to settle in!";
            $departureTime = "Departure by {$nighttimeEnd} the next morning ({$endDateShort})";
        } elseif ($isMultiDay) {
            $daysCount = $this->reservation->total_days ?: (Carbon::parse($startDateRaw)->diffInDays(Carbon::parse($endDateRaw)) + 1);
            $slotLabel = "Continuous Stay ({$daysCount} Days / {$startSlot} to {$endSlot})";
            $startIsNight = str_contains($slotLower, 'night');
            $startSlotTime = $startIsNight ? $nighttimeStart : $daytimeStart;
            $endIsNight = str_contains(strtolower($endSlot), 'night');
            $endSlotTime = $endIsNight ? $nighttimeEnd : $daytimeEnd;

            $arriveTargetTime = "{$startDateShort} at {$startSlotTime}";
            $arrivalTimeWindow = "Check-in opens at {$startSlotTime} on {$startDateShort}";
            $arrivalRecommendation = "Please arrive on {$startDateShort} at {$startSlotTime}. We can still check you in even if you arrive late on your start date, but it's best to be early so you don't miss out on any of your booked stay!";
            $departureTime = "Check-out by {$endSlotTime} on {$endDateShort}";
        } else {
            $slotLabel = "Daytime Stay ({$daytimeStart} - {$daytimeEnd})";
            $arriveTargetTime = "{$startDateShort} at {$daytimeStart}";
            $arrivalTimeWindow = "Gate opens at {$daytimeStart} • Park closes at {$daytimeEnd}";
            $arrivalRecommendation = "Please arrive on {$startDateShort} at {$daytimeStart} (recommended between {$daytimeStart} – 10:00 AM). We can still check you in even if you're late during the day, but it's better to be early to enjoy the whole day!";
            $departureTime = "Daytime access ends at {$daytimeEnd}";
        }

        // Amenities collection
        $amenities = [];
        if ($this->reservation->relationLoaded('reservationAmenities')) {
            foreach ($this->reservation->reservationAmenities as $ra) {
                $name = $ra->amenity?->amenities_name ?: ($ra->remarks ?: 'Reserved Amenity');
                $pricingType = $ra->pricing_type ?: ($ra->start_slot ?: 'Standard');
                $qty = (int) ($ra->quantity ?: 1);
                $price = (float) ($ra->price_at_booking ?: 0);
                $subtotal = $price * $qty;

                $amenities[] = [
                    'name' => $name,
                    'pricing_type' => $pricingType,
                    'quantity' => $qty,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ];
            }
        }

        // Entrance fee
        $entranceFee = $this->reservation->relationLoaded('entranceFee') ? $this->reservation->entranceFee : null;

        // Amounts
        $totalAmount = (float) ($this->reservation->total_amount ?: 0);
        $amountPaid = (float) ($this->reservation->amount_paid ?: 0);
        $remainingBalance = (float) ($this->reservation->remaining_balance ?? max(0, $totalAmount - $amountPaid));

        $paymentStatus = ucfirst((string) ($this->reservation->payment_status ?: ($remainingBalance <= 0 && $totalAmount > 0 ? 'Paid' : 'Partially Paid')));
        $paymentMethod = $this->reservation->payment_method ?: 'Online Payment';

        return new Content(
            view: 'emails.reservation-qr',
            with: [
                'reservation' => $this->reservation,
                'qrPayload' => $qrPayload,
                'qrImageUrl' => $qrImageUrl,
                'bookerName' => $bookerName,
                'phone' => $phone,
                'email' => $email,
                'customerId' => $customerId,
                'startDateFormatted' => $startDateFormatted,
                'endDateFormatted' => $endDateFormatted,
                'dateDisplay' => $dateDisplay,
                'isMultiDay' => $isMultiDay,
                'slotLabel' => $slotLabel,
                'arriveTargetTime' => $arriveTargetTime,
                'arrivalTimeWindow' => $arrivalTimeWindow,
                'arrivalRecommendation' => $arrivalRecommendation,
                'departureTime' => $departureTime,
                'amenities' => $amenities,
                'entranceFee' => $entranceFee,
                'totalAmount' => $totalAmount,
                'amountPaid' => $amountPaid,
                'remainingBalance' => $remainingBalance,
                'paymentStatus' => $paymentStatus,
                'paymentMethod' => $paymentMethod,
                'parkPhone' => $parkSetting?->contact_number ?: '0985-323-9532',
                'parkEmail' => $parkSetting?->email ?: 'parkhinaguan@gmail.com',
                'parkFacebook' => $parkSetting?->facebook_link,
            ],
        );
    }
}

