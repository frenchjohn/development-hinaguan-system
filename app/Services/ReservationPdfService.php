<?php

namespace App\Services;

use App\Models\ParkSetting;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class ReservationPdfService
{
    /**
     * Build unified view data for reservation email and PDF.
     * All output is free of emojis and multi-byte currency symbols (PHP is used instead of ₱).
     */
    public function buildData(Reservation $reservation): array
    {
        if ($reservation->exists) {
            $reservation->loadMissing([
                'reservationGuests.customer',
                'reservationAmenities.amenity',
                'entranceFee',
            ]);
        }

        $qrPayload = 'reservation_id=' . $reservation->id;
        $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrPayload);

        // Generate vector SVG data URI for crisp, offline-ready PDF rendering
        $qrSvgDataUri = null;
        try {
            $qr = new QrCode($qrPayload);
            $writer = new SvgWriter();
            $result = $writer->write($qr);
            $qrSvgDataUri = $result->getDataUri();
        } catch (\Throwable $e) {
            $qrSvgDataUri = $qrImageUrl;
        }

        // Park settings
        $parkSetting = null;
        try {
            $parkSetting = ParkSetting::first();
        } catch (\Throwable $e) {
            // DB fallback
        }

        // Park rules
        $parkRules = collect();
        try {
            $parkRules = \App\Models\ParkRule::orderBy('id')->get();
        } catch (\Throwable $e) {
            // DB fallback
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
        $bookerName = $reservation->booker_name;
        $phone = $reservation->phone;
        $email = $reservation->email;
        $customerId = null;

        if ($reservation->relationLoaded('reservationGuests') && $reservation->reservationGuests->isNotEmpty()) {
            $primaryGuest = $reservation->reservationGuests->firstWhere('is_primary_guest', true)
                ?: $reservation->reservationGuests->first();
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
        $startDateRaw = $reservation->reservation_date ?: $reservation->check_in;
        $startDateFormatted = $startDateRaw ? Carbon::parse($startDateRaw)->format('F d, Y (l)') : 'Upcoming Date';
        $startDateShort = $startDateRaw ? Carbon::parse($startDateRaw)->format('M d, Y') : 'N/A';

        $endDateRaw = $reservation->end_date ?: $reservation->check_out;
        $endDateFormatted = $endDateRaw ? Carbon::parse($endDateRaw)->format('F d, Y (l)') : $startDateFormatted;
        $endDateShort = $endDateRaw ? Carbon::parse($endDateRaw)->format('M d, Y') : $startDateShort;

        $isMultiDay = $endDateRaw && $startDateRaw && (Carbon::parse($startDateRaw)->toDateString() !== Carbon::parse($endDateRaw)->toDateString());
        $dateDisplay = $isMultiDay ? "{$startDateShort} to {$endDateShort}" : $startDateFormatted;

        // Slot & Arrival Window Resolution
        $startSlot = (string) ($reservation->start_slot ?: 'Daytime');
        $endSlot = (string) ($reservation->end_slot ?: $startSlot);
        $slotLower = strtolower($startSlot);

        if (str_contains($slotLower, 'nighttoday')) {
            $slotLabel = "Night to Day Stay ({$nighttimeStart} - {$daytimeEnd} next day)";
            $arriveTargetTime = "{$startDateShort} at {$nighttimeStart}";
            $arrivalTimeWindow = "Check-in opens at {$nighttimeStart} on {$startDateShort}";
            $arrivalRecommendation = "Please arrive on {$startDateShort} at {$nighttimeStart} (recommended between {$nighttimeStart} - 8:00 PM). Front-desk staff can still assist with late check-in if delayed.";
            $departureTime = "Check-out by {$daytimeEnd} on {$endDateShort}";
        } elseif (str_contains($slotLower, 'daytonight')) {
            $slotLabel = "Day to Night Stay ({$daytimeStart} - {$nighttimeEnd} next day)";
            $arriveTargetTime = "{$startDateShort} at {$daytimeStart}";
            $arrivalTimeWindow = "Check-in opens at {$daytimeStart} on {$startDateShort}";
            $arrivalRecommendation = "Please arrive on {$startDateShort} at {$daytimeStart}. Arriving early allows you to enjoy daytime and evening park amenities to the fullest.";
            $departureTime = "Check-out by {$nighttimeEnd} on {$endDateShort}";
        } elseif (str_contains($slotLower, 'night') && !str_contains($slotLower, 'day')) {
            $slotLabel = "Nighttime Stay ({$nighttimeStart} - {$nighttimeEnd})";
            $arriveTargetTime = "{$startDateShort} at {$nighttimeStart}";
            $arrivalTimeWindow = "Check-in begins at {$nighttimeStart} on {$startDateShort}";
            $arrivalRecommendation = "Please arrive on {$startDateShort} at {$nighttimeStart} (recommended between {$nighttimeStart} - 8:00 PM). Front-desk staff can still check you in if you arrive late.";
            $departureTime = "Departure by {$nighttimeEnd} the next morning ({$endDateShort})";
        } elseif ($isMultiDay) {
            $daysCount = $reservation->total_days ?: (Carbon::parse($startDateRaw)->diffInDays(Carbon::parse($endDateRaw)) + 1);
            $slotLabel = "Continuous Stay ({$daysCount} Days / {$startSlot} to {$endSlot})";
            $startIsNight = str_contains($slotLower, 'night');
            $startSlotTime = $startIsNight ? $nighttimeStart : $daytimeStart;
            $endIsNight = str_contains(strtolower($endSlot), 'night');
            $endSlotTime = $endIsNight ? $nighttimeEnd : $daytimeEnd;

            $arriveTargetTime = "{$startDateShort} at {$startSlotTime}";
            $arrivalTimeWindow = "Check-in opens at {$startSlotTime} on {$startDateShort}";
            $arrivalRecommendation = "Please arrive on {$startDateShort} at {$startSlotTime}. Check-in remains open for late arrivals throughout your reserved date.";
            $departureTime = "Check-out by {$endSlotTime} on {$endDateShort}";
        } else {
            $slotLabel = "Daytime Stay ({$daytimeStart} - {$daytimeEnd})";
            $arriveTargetTime = "{$startDateShort} at {$daytimeStart}";
            $arrivalTimeWindow = "Gate opens at {$daytimeStart} | Park closes at {$daytimeEnd}";
            $arrivalRecommendation = "Please arrive on {$startDateShort} at {$daytimeStart} (recommended between {$daytimeStart} - 10:00 AM). Check-in is available throughout the operating hours.";
            $departureTime = "Daytime access ends at {$daytimeEnd}";
        }

        // Amenities collection
        $amenities = [];
        if ($reservation->relationLoaded('reservationAmenities')) {
            foreach ($reservation->reservationAmenities as $ra) {
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
        $entranceFee = $reservation->relationLoaded('entranceFee') ? $reservation->entranceFee : null;

        // Amounts
        $totalAmount = (float) ($reservation->total_amount ?: 0);
        $amountPaid = (float) ($reservation->amount_paid ?: 0);
        $remainingBalance = (float) ($reservation->remaining_balance ?? max(0, $totalAmount - $amountPaid));

        $paymentStatus = ucfirst((string) ($reservation->payment_status ?: ($remainingBalance <= 0 && $totalAmount > 0 ? 'Paid' : 'Partially Paid')));
        $paymentMethod = $reservation->payment_method ?: 'Online Payment';

        // Direct PDF download URL
        $downloadPdfUrl = route('reservation.download-pass', ['id' => $reservation->id]);

        return [
            'reservation' => $reservation,
            'qrPayload' => $qrPayload,
            'qrImageUrl' => $qrImageUrl,
            'qrSvgDataUri' => $qrSvgDataUri,
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
            'downloadPdfUrl' => $downloadPdfUrl,
            'parkRules' => $parkRules,
        ];
    }

    /**
     * Generate DomPDF instance for the reservation pass.
     */
    public function generatePdf(Reservation $reservation): \Barryvdh\DomPDF\PDF
    {
        $data = $this->buildData($reservation);
        return Pdf::loadView('pdf.reservation-pass', $data)->setPaper('a4', 'portrait');
    }

    /**
     * Get raw binary string of generated PDF.
     */
    public function getPdfOutput(Reservation $reservation): string
    {
        return $this->generatePdf($reservation)->output();
    }
}
