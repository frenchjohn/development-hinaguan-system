<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation Pass #{{ $reservation->id }} - Hinaguan Nature Park</title>
    <style>
        @page {
            margin: 24px 28px;
            size: a4 portrait;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #000000;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #000000;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .brand-title {
            font-size: 20px;
            font-weight: bold;
            color: #000000;
            letter-spacing: 0.5px;
            margin: 0 0 2px 0;
            text-transform: uppercase;
        }
        .brand-subtitle {
            font-size: 10px;
            color: #333333;
            margin: 0;
        }
        .badge-status {
            display: inline-block;
            background: #ffffff;
            border: 1.5px solid #000000;
            color: #000000;
            font-size: 10px;
            font-weight: bold;
            padding: 4px 10px;
            border-radius: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .pass-meta {
            text-align: right;
        }
        .res-number {
            font-size: 16px;
            font-weight: bold;
            color: #000000;
            margin: 0 0 3px 0;
        }
        
        /* QR & Hero Section */
        .hero-table {
            width: 100%;
            margin-bottom: 14px;
            border: 1px solid #000000;
            background: #ffffff;
        }
        .hero-qr-td {
            width: 160px;
            text-align: center;
            vertical-align: middle;
            padding: 12px;
            background: #ffffff;
            border-right: 1px solid #000000;
        }
        .hero-info-td {
            padding: 12px 16px;
            vertical-align: top;
            background: #ffffff;
        }
        .section-header {
            font-size: 11px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #000000;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }
        
        /* Two column layout tables */
        .two-col-table {
            width: 100%;
            margin-bottom: 12px;
        }
        .col-left {
            width: 50%;
            vertical-align: top;
            padding-right: 8px;
        }
        .col-right {
            width: 50%;
            vertical-align: top;
            padding-left: 8px;
        }
        .box {
            border: 1px solid #000000;
            background: #ffffff;
            padding: 10px 12px;
        }
        .info-row {
            margin-bottom: 6px;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .label {
            font-size: 9px;
            font-weight: bold;
            color: #444444;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .value {
            font-size: 11px;
            font-weight: bold;
            color: #000000;
        }
        
        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .items-table th {
            background: #f0f0f0;
            color: #000000;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 8px;
            border: 1px solid #000000;
            text-align: left;
        }
        .items-table td {
            font-size: 10px;
            padding: 6px 8px;
            border: 1px solid #666666;
            color: #000000;
            background: #ffffff;
        }
        .items-table td.amount-col {
            text-align: right;
            font-weight: bold;
            font-family: 'Courier New', Courier, monospace;
        }
        
        /* Payment Summary */
        .summary-box {
            width: 100%;
            margin-bottom: 12px;
            border: 1.5px solid #000000;
            background: #ffffff;
            padding: 10px 14px;
        }
        .summary-table {
            width: 100%;
        }
        .summary-table td {
            padding: 3px 0;
            font-size: 10px;
            color: #000000;
        }
        .summary-table td.sum-label {
            color: #222222;
        }
        .summary-table td.sum-val {
            text-align: right;
            font-weight: bold;
            font-family: 'Courier New', Courier, monospace;
            color: #000000;
        }
        .total-row td {
            border-top: 1.5px solid #000000;
            padding-top: 6px;
            font-size: 12px;
            font-weight: bold;
            color: #000000;
        }
        
        /* Instructions / Notice */
        .notice-box {
            border: 1px solid #000000;
            background: #ffffff;
            padding: 8px 12px;
            margin-bottom: 12px;
        }
        .notice-title {
            font-size: 9px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            margin-bottom: 4px;
            border-bottom: 1px dashed #666666;
            padding-bottom: 3px;
        }
        .notice-list {
            margin: 0;
            padding-left: 14px;
            font-size: 9px;
            color: #000000;
            line-height: 1.45;
        }
        
        /* Footer */
        .footer {
            border-top: 1px solid #000000;
            padding-top: 8px;
            font-size: 8.5px;
            color: #444444;
            text-align: center;
            line-height: 1.4;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="vertical-align: top;">
                <div class="brand-title">Hinaguan Nature Park</div>
                <div class="brand-subtitle">Jasaan, Misamis Oriental, Philippines | Contact: {{ $parkPhone }}</div>
                <div class="brand-subtitle">Official Online Reservation Pass &amp; Confirmation Voucher</div>
            </td>
            <td class="pass-meta" style="vertical-align: top;">
                <div class="res-number">Pass #{{ $reservation->id }}</div>
                <div class="badge-status">
                    @if($remainingBalance <= 0 && $totalAmount > 0)
                        Fully Paid
                    @elseif($amountPaid > 0)
                        Downpayment Confirmed
                    @else
                        {{ $paymentStatus }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Hero / Quick Check-In Scanner Section -->
    <table class="hero-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="hero-qr-td">
                @if(!empty($qrSvgDataUri))
                    <img src="{{ $qrSvgDataUri }}" width="135" height="135" alt="Gate Check-In QR Pass" style="display: block; margin: 0 auto;" />
                @else
                    <img src="{{ $qrImageUrl }}" width="135" height="135" alt="Gate Check-In QR Pass" style="display: block; margin: 0 auto;" />
                @endif
                <div style="font-size: 8px; color: #444444; margin-top: 4px; font-family: monospace;">{{ $qrPayload }}</div>
            </td>
            <td class="hero-info-td">
                <div class="section-header">Gate Check-In Pass</div>
                <div style="font-size: 13px; font-weight: bold; color: #000000; margin-bottom: 4px;">
                    Mabuhay, {{ $bookerName }}!
                </div>
                <div style="font-size: 10px; color: #222222; margin-bottom: 8px;">
                    Please present this official QR pass upon arrival at the entrance gate or reception desk.
                </div>
                <table style="width: 100%; font-size: 10px;">
                    <tr>
                        <td style="width: 100px; color: #444444; font-weight: bold;">Reservation Date:</td>
                        <td style="font-weight: bold; color: #000000;">{{ $dateDisplay }}</td>
                    </tr>
                    <tr>
                        <td style="color: #444444; font-weight: bold;">Time Slot:</td>
                        <td style="font-weight: bold; color: #000000;">{{ $slotLabel }}</td>
                    </tr>
                    <tr>
                        <td style="color: #444444; font-weight: bold;">Arrival Target:</td>
                        <td style="font-weight: bold; color: #000000;">Arrive at {{ $arriveTargetTime }}</td>
                    </tr>
                    <tr>
                        <td style="color: #444444; font-weight: bold;">Check-Out:</td>
                        <td style="color: #000000;">{{ $departureTime }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Two-Column Details: Guest Info & Schedule -->
    <table class="two-col-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="col-left">
                <div class="box">
                    <div class="section-header">Guest Information</div>
                    <div class="info-row">
                        <div class="label">Booker Name</div>
                        <div class="value">{{ $bookerName }}</div>
                    </div>
                    <div class="info-row">
                        <div class="label">Contact Phone</div>
                        <div class="value">{{ $phone }}</div>
                    </div>
                    <div class="info-row">
                        <div class="label">Email Address</div>
                        <div class="value">{{ $email }}</div>
                    </div>
                    <div class="info-row">
                        <div class="label">Total Party Size</div>
                        <div class="value">{{ $reservation->number_of_guests ?? 1 }} Guest(s)</div>
                    </div>
                </div>
            </td>
            <td class="col-right">
                <div class="box">
                    <div class="section-header">Check-In Schedule</div>
                    <div class="info-row">
                        <div class="label">Booked Date</div>
                        <div class="value">{{ $dateDisplay }}</div>
                    </div>
                    <div class="info-row">
                        <div class="label">Arrival Instructions</div>
                        <div style="font-size: 10px; color: #000000; line-height: 1.35;">{{ $arrivalRecommendation }}</div>
                    </div>
                    <div class="info-row" style="margin-top: 4px;">
                        <div class="label">Operating Window</div>
                        <div style="font-size: 10px; color: #000000;">{{ $arrivalTimeWindow }}</div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Availed Amenities Table -->
    <div style="margin-bottom: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; color: #000000; letter-spacing: 0.5px;">
        Reserved Amenities &amp; Inclusions
    </div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 45%;">Item / Facility Description</th>
                <th style="width: 25%;">Package / Slot</th>
                <th style="width: 10%; text-align: center;">Qty</th>
                <th style="width: 10%; text-align: right;">Unit Price</th>
                <th style="width: 10%; text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($amenities) && count($amenities) > 0)
                @foreach($amenities as $item)
                    <tr>
                        <td style="font-weight: bold;">{{ $item['name'] }}</td>
                        <td>{{ $item['pricing_type'] }}</td>
                        <td style="text-align: center;">{{ $item['quantity'] }}</td>
                        <td class="amount-col">PHP {{ number_format($item['price'], 2) }}</td>
                        <td class="amount-col">PHP {{ number_format($item['subtotal'], 2) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="5" style="color: #444444; font-style: italic;">
                        Standard park access. Specific amenities to be arranged upon arrival.
                    </td>
                </tr>
            @endif

            @if($entranceFee)
                <tr>
                    <td style="font-weight: bold;">
                        Entrance Fee ({{ $entranceFee->adult_count ?? 0 }} Adults, {{ $entranceFee->child_count ?? 0 }} Children)
                        @if(($entranceFee->pool_fee ?? 0) > 0)
                            <div style="font-size: 9px; color: #444444; font-weight: normal;">Includes pool access pass</div>
                        @endif
                    </td>
                    <td>Entrance Package</td>
                    <td style="text-align: center;">1</td>
                    <td class="amount-col">PHP {{ number_format($entranceFee->total_amount, 2) }}</td>
                    <td class="amount-col">PHP {{ number_format($entranceFee->total_amount, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Payment & Balance Breakdown -->
    <div class="summary-box">
        <table class="summary-table">
            <tr>
                <td class="sum-label" style="width: 70%;">Total Reservation Amount:</td>
                <td class="sum-val">PHP {{ number_format($totalAmount, 2) }}</td>
            </tr>
            <tr>
                <td class="sum-label">Amount Paid ({{ $paymentMethod }}):</td>
                <td class="sum-val">PHP {{ number_format($amountPaid, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Remaining Balance Due at Arrival:</td>
                <td class="sum-val">
                    PHP {{ number_format($remainingBalance, 2) }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Check-In Guidelines Notice -->
    <div class="notice-box">
        <div class="notice-title">Important Check-In Guidelines</div>
        <ol class="notice-list">
            <li><strong>Entry QR Pass:</strong> Present this pass (printed or displayed on your mobile device) at the gate scanner.</li>
            <li><strong>ID Verification:</strong> If the QR pass is not readily accessible, please present a valid Government or Student ID matching <strong>{{ $bookerName }}</strong>.</li>
            <li><strong>Remaining Balance:</strong> Any outstanding balance of PHP {{ number_format($remainingBalance, 2) }} can be settled at the front desk upon check-in via Cash or GCash.</li>
            <li><strong>Park Rules:</strong> Help keep our park pristine (Leave No Trace). Proper swimming attire is strictly enforced in pool areas.</li>
            <li><strong>Strictly No Refund:</strong> All booking deposits and reservation payments are strictly final and non-refundable.</li>
        </ol>
    </div>

    <!-- Footer -->
    <div class="footer">
        Hinaguan Nature Park &bull; Jasaan, Misamis Oriental, Philippines<br>
        Inquiries &amp; Assistance: {{ $parkPhone }} | {{ $parkEmail }}<br>
        This document serves as an official electronic confirmation voucher. Generated automatically on {{ date('F d, Y g:i A') }}.
    </div>

</body>
</html>
