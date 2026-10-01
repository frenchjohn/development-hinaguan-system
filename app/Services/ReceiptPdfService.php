<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ParkSetting;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

class ReceiptPdfService
{
    /**
     * Build structured view data for checkout receipt PDF and email.
     */
    public function buildData(
        Customer $customer,
        ?Reservation $reservation = null,
        array $amenities = [],
        string $checkInDateTime = '',
        string $checkOutDateTime = '',
        float $totalCost = 0
    ): array {
        if ($reservation && $reservation->exists) {
            $reservation->loadMissing([
                'reservationGuests.customer',
                'reservationAmenities.amenity',
                'entranceFee',
                'reservationCharges',
            ]);
        }

        // Park contact details
        $parkSetting = null;
        try {
            $parkSetting = ParkSetting::first();
        } catch (\Throwable $e) {
            // DB fallback
        }

        $parkPhone = $parkSetting?->contact_number ?: '0985-323-9532';
        $parkEmail = $parkSetting?->email ?: 'parkhinaguan@gmail.com';
        $parkAddress = 'Jasaan, Misamis Oriental, Philippines';

        // Resolve customer and main guest names
        $customerName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        if (empty($customerName)) {
            $customerName = 'Valued Guest';
        }

        $mainGuestName = null;
        if ($reservation) {
            $primaryGuest = $reservation->reservationGuests->firstWhere('is_primary_guest', true);
            if ($primaryGuest && $primaryGuest->customer) {
                $mainGuestName = trim(($primaryGuest->customer->first_name ?? '') . ' ' . ($primaryGuest->customer->last_name ?? ''));
            } elseif (!empty($reservation->booker_name)) {
                $mainGuestName = $reservation->booker_name;
            }
        }
        if (empty($mainGuestName)) {
            $mainGuestName = $customerName;
        }

        // Timestamps
        $checkInFormatted = $checkInDateTime;
        if (empty($checkInFormatted) && $reservation) {
            $checkInFormatted = $reservation->check_in
                ? $reservation->check_in->format('F j, Y g:i A')
                : ($reservation->reservation_date ? $reservation->reservation_date->format('F j, Y') : 'N/A');
        }
        if (empty($checkInFormatted)) {
            $checkInFormatted = now()->format('F j, Y g:i A');
        }

        $checkOutFormatted = $checkOutDateTime;
        if (empty($checkOutFormatted) && $reservation && $reservation->check_out) {
            $checkOutFormatted = $reservation->check_out->format('F j, Y g:i A');
        }
        if (empty($checkOutFormatted)) {
            $checkOutFormatted = now()->format('F j, Y g:i A');
        }

        $reservationDate = $reservation?->reservation_date
            ? $reservation->reservation_date->format('F j, Y')
            : ($reservation?->check_in ? $reservation->check_in->format('F j, Y') : now()->format('F j, Y'));

        $guestCount = $reservation
            ? ($reservation->reservationGuests->count() ?: ($reservation->number_of_guests ?: 1))
            : 1;

        // 1. Entrance & Admission Breakdown
        $entranceBreakdown = null;
        if ($reservation && $reservation->entranceFee && (float) ($reservation->entranceFee->total_amount ?? 0) > 0) {
            $ef = $reservation->entranceFee;
            $adultCount = (int) ($ef->adult_count ?? 0);
            $childCount = (int) ($ef->child_count ?? 0);
            $poolFee = (float) ($ef->pool_fee ?? 0);
            $poolCount = (int) ($ef->pool_access_count ?? 0);
            $totalEntrance = (float) ($ef->total_amount ?? 0);
            $baseEntrance = max(0, $totalEntrance - $poolFee);

            $entranceBreakdown = [
                'has_entrance' => true,
                'adult_count' => $adultCount,
                'child_count' => $childCount,
                'senior_count' => (int) ($ef->senior_pwd_count ?? 0),
                'base_entrance' => $baseEntrance,
                'pool_fee' => $poolFee,
                'pool_access_count' => $poolCount,
                'total_amount' => $totalEntrance,
                'pricing_type' => $ef->pricing_type ?? 'Daytime',
            ];
        }

        // 2. Availed Amenities Breakdown
        $amenitiesList = [];
        if ($reservation && $reservation->reservationAmenities && $reservation->reservationAmenities->isNotEmpty()) {
            foreach ($reservation->reservationAmenities as $resAmenity) {
                $qty = max(1, (int) $resAmenity->quantity);
                $unitPrice = (float) $resAmenity->price_at_booking;
                $amenityCost = $unitPrice * $qty;
                $amenityName = $resAmenity->amenity?->amenities_name ?? 'Amenity';

                $amenitiesList[] = [
                    'name' => $amenityName,
                    'quantity' => $qty,
                    'price' => $amenityCost,
                ];
            }
        } elseif (!empty($amenities)) {
            foreach ($amenities as $am) {
                $amenitiesList[] = [
                    'name' => $am['name'] ?? 'Amenity',
                    'quantity' => (int) ($am['quantity'] ?? 1),
                    'price' => (float) ($am['price'] ?? 0),
                ];
            }
        }

        // 3. Additional Charges / Penalties Breakdown (Damages, Extra Head, Cleaning, Lost, etc.)
        $additionalChargesList = [];
        if ($reservation && $reservation->reservationCharges && $reservation->reservationCharges->isNotEmpty()) {
            foreach ($reservation->reservationCharges as $charge) {
                $chargeAmount = (float) $charge->amount;
                $type = strtolower((string) $charge->charge_type);
                $typePrefix = match($type) {
                    'damage' => 'Damage Fee',
                    'cleaning' => 'Cleaning Fee',
                    'lost' => 'Lost Item Fee',
                    'extra_head', 'extra_guest' => 'Additional Head Fee',
                    default => 'Additional Charge',
                };
                $label = !empty($charge->description) ? "{$typePrefix} ({$charge->description})" : $typePrefix;

                $additionalChargesList[] = [
                    'type' => $type,
                    'label' => $label,
                    'description' => $charge->description,
                    'amount' => $chargeAmount,
                ];
            }
        }

        // Check if reservation has excess guests requiring an additional head fee (if not already recorded in charges)
        if ($reservation && $reservation->reservationAmenities && $reservation->reservationAmenities->isNotEmpty()) {
            $hasExtraHeadInCharges = collect($additionalChargesList)->contains(fn ($c) => in_array($c['type'], ['extra_head', 'extra_guest']) || str_contains(strtolower($c['label']), 'extra head') || str_contains(strtolower($c['label']), 'additional head'));

            if (!$hasExtraHeadInCharges) {
                $totalGuestCount = ($reservation->reservationGuests && $reservation->reservationGuests->count() > 0)
                    ? $reservation->reservationGuests->count()
                    : (int) ($reservation->number_of_guests ?: 1);
                $calculatedExtraHead = 0;
                $excessGuestsCount = 0;

                foreach ($reservation->reservationAmenities as $ra) {
                    $am = $ra->amenity;
                    if ($am && $am->maximum_capacity !== null && (int)$am->maximum_capacity > 0 && (float)($am->additional_per_head ?? 0) > 0) {
                        if ($totalGuestCount > (int) $am->maximum_capacity) {
                            $excess = $totalGuestCount - (int) $am->maximum_capacity;
                            $calculatedExtraHead += $excess * (float) $am->additional_per_head;
                            $excessGuestsCount += $excess;
                        }
                    }
                }

                $amenitiesSubtotal = array_sum(array_column($amenitiesList, 'price'));
                $entranceSubtotal = $entranceBreakdown ? $entranceBreakdown['total_amount'] : 0;
                $chargesSubtotal = array_sum(array_column($additionalChargesList, 'amount'));
                $baseKnownSum = $amenitiesSubtotal + $entranceSubtotal + $chargesSubtotal;

                if ($calculatedExtraHead > 0) {
                    if ($entranceBreakdown && $entranceBreakdown['base_entrance'] >= $calculatedExtraHead) {
                        $entranceBreakdown['base_entrance'] = round($entranceBreakdown['base_entrance'] - $calculatedExtraHead, 2);
                        $entranceBreakdown['total_amount'] = round($entranceBreakdown['total_amount'] - $calculatedExtraHead, 2);
                        $additionalChargesList[] = [
                            'type' => 'extra_head',
                            'label' => "Additional Head Fee ({$excessGuestsCount} excess guest" . ($excessGuestsCount > 1 ? 's' : '') . ")",
                            'description' => 'Exceeding capacity limit',
                            'amount' => $calculatedExtraHead,
                        ];
                    } elseif ($totalCost > $baseKnownSum || ($reservation->total_amount ?? 0) > $baseKnownSum) {
                        $additionalChargesList[] = [
                            'type' => 'extra_head',
                            'label' => "Additional Head Fee ({$excessGuestsCount} excess guest" . ($excessGuestsCount > 1 ? 's' : '') . ")",
                            'description' => 'Exceeding capacity limit',
                            'amount' => $calculatedExtraHead,
                        ];
                    }
                }
            }
        }

        // Consolidated items array for tables
        $items = [];
        if ($entranceBreakdown && $entranceBreakdown['base_entrance'] > 0) {
            $items[] = [
                'name' => 'Park Admission (' . ($entranceBreakdown['adult_count'] > 0 ? "{$entranceBreakdown['adult_count']} Adults" : '') . ($entranceBreakdown['child_count'] > 0 ? ", {$entranceBreakdown['child_count']} Children" : '') . ')',
                'category' => 'Admission',
                'quantity' => 1,
                'unit_price' => $entranceBreakdown['base_entrance'],
                'subtotal' => $entranceBreakdown['base_entrance'],
            ];
        }
        if ($entranceBreakdown && $entranceBreakdown['pool_fee'] > 0) {
            $items[] = [
                'name' => 'Pool Access Pass' . ($entranceBreakdown['pool_access_count'] > 0 ? " ({$entranceBreakdown['pool_access_count']} guests)" : ''),
                'category' => 'Pool Pass',
                'quantity' => 1,
                'unit_price' => $entranceBreakdown['pool_fee'],
                'subtotal' => $entranceBreakdown['pool_fee'],
            ];
        }
        foreach ($amenitiesList as $am) {
            $items[] = [
                'name' => $am['name'],
                'category' => 'Amenity Rental',
                'quantity' => $am['quantity'],
                'unit_price' => $am['quantity'] > 0 ? ($am['price'] / $am['quantity']) : $am['price'],
                'subtotal' => $am['price'],
            ];
        }
        foreach ($additionalChargesList as $ch) {
            $items[] = [
                'name' => $ch['label'],
                'category' => 'Additional Fee',
                'quantity' => 1,
                'unit_price' => $ch['amount'],
                'subtotal' => $ch['amount'],
            ];
        }

        // Compute totals
        $itemsSum = array_sum(array_column($items, 'subtotal'));
        $finalTotal = $totalCost > 0 ? $totalCost : $itemsSum;
        if ($reservation && (float) ($reservation->total_amount ?? 0) > $finalTotal) {
            $finalTotal = (float) $reservation->total_amount;
        }
        if ($finalTotal <= 0 && $reservation && (float) ($reservation->amount_paid ?? 0) > 0) {
            $finalTotal = (float) $reservation->amount_paid;
        }

        $receiptNumber = $reservation
            ? "REC-RES-{$reservation->id}-" . now()->format('Ymd')
            : "REC-CUST-{$customer->id}-" . now()->format('Ymd');

        $paymentMethod = $reservation?->payment_method ?: 'Cash / Front Desk';
        $reservationType = $reservation?->reservation_type === 'online' ? 'Online Reservation' : 'Walk-in Guest';

        $downloadPdfUrl = $reservation
            ? route('reservation.download-receipt', ['id' => $reservation->id])
            : null;

        return [
            'customer' => $customer,
            'customerName' => $customerName,
            'mainGuestName' => $mainGuestName,
            'reservation' => $reservation,
            'reservationDate' => $reservationDate,
            'reservationType' => $reservationType,
            'guestCount' => $guestCount,
            'checkInDateTime' => $checkInFormatted,
            'checkOutDateTime' => $checkOutFormatted,
            'items' => $items,
            'entranceBreakdown' => $entranceBreakdown,
            'amenitiesList' => $amenitiesList,
            'additionalChargesList' => $additionalChargesList,
            'amenities' => $amenities,
            'totalCost' => $finalTotal,
            'amountPaid' => $finalTotal,
            'paymentMethod' => $paymentMethod,
            'receiptNumber' => $receiptNumber,
            'issuedAt' => now()->format('F j, Y g:i A'),
            'parkPhone' => $parkPhone,
            'parkEmail' => $parkEmail,
            'parkAddress' => $parkAddress,
            'downloadPdfUrl' => $downloadPdfUrl,
        ];
    }

    /**
     * Generate DomPDF instance for the checkout receipt.
     */
    public function generatePdf(
        Customer $customer,
        ?Reservation $reservation = null,
        array $amenities = [],
        string $checkInDateTime = '',
        string $checkOutDateTime = '',
        float $totalCost = 0
    ): \Barryvdh\DomPDF\PDF {
        $data = $this->buildData($customer, $reservation, $amenities, $checkInDateTime, $checkOutDateTime, $totalCost);
        return Pdf::loadView('pdf.checkout-receipt', $data)->setPaper('a4', 'portrait');
    }

    /**
     * Get raw binary string of generated receipt PDF.
     */
    public function getPdfOutput(
        Customer $customer,
        ?Reservation $reservation = null,
        array $amenities = [],
        string $checkInDateTime = '',
        string $checkOutDateTime = '',
        float $totalCost = 0
    ): string {
        return $this->generatePdf($customer, $reservation, $amenities, $checkInDateTime, $checkOutDateTime, $totalCost)->output();
    }
}
