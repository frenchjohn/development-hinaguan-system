<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Hinaguan Nature Park Visit Receipt</title>
    <style>
        @page {
            margin: 20px;
            size: a4 portrait;
        }
        body {
            margin: 0;
            padding: 24px;
            background-color: #ffffff;
            font-family: 'DejaVu Sans', 'Courier New', Courier, monospace;
            color: #333333;
            font-size: 13px;
        }
        .receipt-card {
            max-width: 500px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #dddddd;
            padding: 32px;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #333333;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .receipt-header h1 {
            margin: 0 0 8px;
            font-size: 22px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #111111;
        }
        .receipt-header p.sub {
            margin: 0;
            font-size: 14px;
            color: #666666;
        }
        .receipt-header p.date {
            margin: 8px 0 0;
            font-size: 12px;
            color: #999999;
        }
        .customer-info {
            margin-bottom: 20px;
        }
        .customer-label {
            margin: 0 0 4px;
            font-size: 12px;
            text-transform: uppercase;
            color: #666666;
        }
        .customer-name {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
            color: #000000;
        }
        .visit-details {
            background: #f9f9f9;
            border: 1px solid #eeeeee;
            padding: 16px;
            margin-bottom: 20px;
        }
        .section-title {
            margin: 0 0 12px;
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 1px solid #dddddd;
            padding-bottom: 8px;
            font-weight: bold;
            color: #333333;
        }
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }
        .detail-table td {
            padding: 4px 0;
            font-size: 13px;
            vertical-align: top;
        }
        .detail-table td.label-td {
            text-align: left;
            color: #333333;
        }
        .detail-table td.value-td {
            text-align: right;
            color: #111111;
        }
        .amenities-section,
        .receipt-section {
            margin-bottom: 20px;
        }
        .total-box {
            border-top: 2px solid #333333;
            border-bottom: 2px solid #333333;
            padding: 16px 0;
            margin-bottom: 20px;
        }
        .total-table {
            width: 100%;
            border-collapse: collapse;
        }
        .total-table td.total-label {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: left;
            color: #000000;
        }
        .total-table td.total-amount {
            font-size: 22px;
            font-weight: bold;
            text-align: right;
            color: #000000;
        }
        .receipt-footer {
            text-align: center;
            border-top: 1px dashed #dddddd;
            padding-top: 20px;
        }
        .receipt-footer p.msg {
            margin: 0 0 8px;
            font-size: 12px;
            color: #666666;
        }
        .receipt-footer p.proof {
            margin: 0;
            font-size: 11px;
            color: #999999;
        }
    </style>
</head>
<body>
    <div class="receipt-card">
        <!-- Header -->
        <div class="receipt-header">
            <h1>Hinaguan Nature Park</h1>
            <p class="sub">Official Receipt</p>
            <p class="date">{{ now()->format('F j, Y') }}</p>
        </div>

        <!-- Customer Info -->
        <div class="customer-info">
            <p class="customer-label">Receipt For:</p>
            <p class="customer-name">{{ $customer->first_name }} {{ $customer->last_name }}</p>
        </div>

        <!-- Visit Details -->
        <div class="visit-details">
            <div class="section-title">Visit Details</div>
            <table class="detail-table" cellpadding="0" cellspacing="0">
                @if($reservation)
                <tr>
                    <td class="label-td">Reservation ID:</td>
                    <td class="value-td">{{ $reservation->id }}</td>
                </tr>
                <tr>
                    <td class="label-td">Reservation Date:</td>
                    <td class="value-td">{{ $reservation->reservation_date ? $reservation->reservation_date->format('F j, Y') : 'N/A' }}</td>
                </tr>
                @endif

                @if($mainGuestName)
                <tr>
                    <td class="label-td">Main Guest:</td>
                    <td class="value-td">{{ $mainGuestName }}</td>
                </tr>
                @endif

                <tr>
                    <td class="label-td">Guest Count:</td>
                    <td class="value-td">{{ $guestCount }}</td>
                </tr>

                <tr>
                    <td class="label-td">Type:</td>
                    <td class="value-td">
                        @if($reservation)
                            {{ $reservation->reservation_type === 'online' ? 'Online Reservation' : 'Walk In' }}
                        @else
                            Walk In
                        @endif
                    </td>
                </tr>

                <tr>
                    <td class="label-td">Check In:</td>
                    <td class="value-td">{{ $checkInDateTime }}</td>
                </tr>

                <tr>
                    <td class="label-td">Check Out:</td>
                    <td class="value-td">{{ $checkOutDateTime }}</td>
                </tr>
            </table>
        </div>

        <!-- Entrance & Admission -->
        @if(!empty($entranceBreakdown) && ($entranceBreakdown['total_amount'] ?? 0) > 0)
        <div class="receipt-section">
            <div class="section-title">Entrance & Admission:</div>
            <table class="detail-table" cellpadding="0" cellspacing="0">
                @if(($entranceBreakdown['base_entrance'] ?? 0) > 0)
                <tr>
                    <td class="label-td">
                        Park Admission
                        @php
                            $counts = [];
                            if (!empty($entranceBreakdown['adult_count'])) {
                                $counts[] = $entranceBreakdown['adult_count'] . ' ' . ($entranceBreakdown['adult_count'] > 1 ? 'Adults' : 'Adult');
                            }
                            if (!empty($entranceBreakdown['child_count'])) {
                                $counts[] = $entranceBreakdown['child_count'] . ' ' . ($entranceBreakdown['child_count'] > 1 ? 'Children' : 'Child');
                            }
                        @endphp
                        @if(count($counts) > 0)
                            ({{ implode(', ', $counts) }})
                        @endif
                        :
                    </td>
                    <td class="value-td">&#8369;{{ number_format($entranceBreakdown['base_entrance'], 2) }}</td>
                </tr>
                @endif

                @if(($entranceBreakdown['pool_fee'] ?? 0) > 0)
                <tr>
                    <td class="label-td">
                        Pool Access Pass
                        @if(!empty($entranceBreakdown['pool_access_count']))
                            ({{ $entranceBreakdown['pool_access_count'] }} {{ $entranceBreakdown['pool_access_count'] > 1 ? 'guests' : 'guest' }})
                        @endif
                        :
                    </td>
                    <td class="value-td">&#8369;{{ number_format($entranceBreakdown['pool_fee'], 2) }}</td>
                </tr>
                @endif

                @if(($entranceBreakdown['base_entrance'] ?? 0) <= 0 && ($entranceBreakdown['pool_fee'] ?? 0) <= 0)
                <tr>
                    <td class="label-td">Park Entrance Fee:</td>
                    <td class="value-td">&#8369;{{ number_format($entranceBreakdown['total_amount'], 2) }}</td>
                </tr>
                @endif
            </table>
        </div>
        @endif

        <!-- Amenities -->
        <div class="amenities-section">
            <div class="section-title">Amenities Used:</div>
            <table class="detail-table" cellpadding="0" cellspacing="0">
                @php
                    $amenitiesDisplay = !empty($amenitiesList) ? $amenitiesList : $amenities;
                @endphp

                @if(!empty($amenitiesDisplay) && count($amenitiesDisplay) > 0)
                    @foreach($amenitiesDisplay as $amenity)
                    <tr>
                        <td class="label-td">
                            {{ $amenity['name'] ?? 'Amenity' }}
                            @if(isset($amenity['quantity']) && (int)$amenity['quantity'] > 1) (x{{ $amenity['quantity'] }})@endif
                            :
                        </td>
                        <td class="value-td">&#8369;{{ number_format($amenity['price'] ?? 0, 2) }}</td>
                    </tr>
                    @endforeach
                @elseif($reservation && $reservation->reservationAmenities && $reservation->reservationAmenities->count() > 0)
                    @foreach($reservation->reservationAmenities as $reservationAmenity)
                    <tr>
                        <td class="label-td">
                            {{ $reservationAmenity->amenity?->amenities_name ?? 'Amenity' }}
                            @if($reservationAmenity->quantity > 1) (x{{ $reservationAmenity->quantity }})@endif
                            :
                        </td>
                        <td class="value-td">&#8369;{{ number_format($reservationAmenity->price_at_booking * $reservationAmenity->quantity, 2) }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="label-td" style="color:#999;">None:</td>
                        <td class="value-td">&#8369;0.00</td>
                    </tr>
                @endif
            </table>
        </div>

        <!-- Additional Fees / Charges (Damages, Extra Head, etc.) -->
        @if(!empty($additionalChargesList) && count($additionalChargesList) > 0)
        <div class="receipt-section">
            <div class="section-title">Additional Fees & Charges:</div>
            <table class="detail-table" cellpadding="0" cellspacing="0">
                @foreach($additionalChargesList as $charge)
                <tr>
                    <td class="label-td">{{ $charge['label'] ?? 'Additional Fee' }}:</td>
                    <td class="value-td">&#8369;{{ number_format($charge['amount'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </table>
        </div>
        @endif

        <!-- Total -->
        <div class="total-box">
            <table class="total-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="total-label">Total Cost:</td>
                    <td class="total-amount">&#8369;{{ number_format($totalCost, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            <p class="msg">Thank you for visiting Hinaguan Nature Park!</p>
            <p class="proof">This receipt serves as proof of your visit.</p>
        </div>
    </div>
</body>
</html>
