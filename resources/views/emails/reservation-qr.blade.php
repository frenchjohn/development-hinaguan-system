<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Confirmation &amp; Entry Pass - Hinaguan Nature Park</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td, p, h1, h2, h3, a, span { font-family: Arial, sans-serif !important; }
    </style>
    <![endif]-->
    <style>
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; margin: 0 !important; border-radius: 0 !important; }
            .mobile-padding { padding: 20px 16px !important; }
            .mobile-header { padding: 24px 18px !important; }
            .qr-code-img { width: 200px !important; max-width: 200px !important; height: auto !important; }
            .col-half { display: block !important; width: 100% !important; padding-right: 0 !important; padding-left: 0 !important; margin-bottom: 12px !important; }
            .btn-download { display: block !important; width: 100% !important; text-align: center !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f2; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing:antialiased; color:#1e293b; line-height:1.6;">

    <!-- Wrapper Table -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f1f5f2; padding:28px 12px;">
        <tr>
            <td align="center">
                
                <!-- Main Email Card -->
                <table class="email-container" width="620" cellpadding="0" cellspacing="0" border="0" style="width:620px; max-width:620px; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 6px 24px rgba(15,23,42,0.06); border:1px solid #e2e8f0;">
                    
                    <!-- Top Brand Header Banner -->
                    <tr>
                        <td class="mobile-header" style="background:#134629; padding:28px 36px; text-align:center; color:#ffffff;">
                            
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center">
                                        <div style="display:inline-block; padding:4px 12px; background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.22); border-radius:4px; font-size:11px; font-weight:700; letter-spacing:1.8px; text-transform:uppercase; color:#bbf7d0; margin-bottom:10px;">
                                            HINAGUAN NATURE PARK
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <h1 style="margin:4px 0 6px; font-size:24px; font-weight:700; line-height:1.3; color:#ffffff; letter-spacing:-0.3px;">
                                Booking Confirmation
                            </h1>
                            <p style="margin:0; font-size:13px; color:#dcfce7; line-height:1.5;">
                                Your online reservation has been successfully confirmed.
                            </p>

                            <!-- Reservation Tag -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:16px;">
                                <tr>
                                    <td align="center">
                                        <div style="display:inline-block; background:#ffffff; color:#134629; font-size:13px; font-weight:700; padding:6px 16px; border-radius:8px;">
                                            Reservation ID: <span style="color:#15803d; letter-spacing:0.5px;">#{{ $reservation->id }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td class="mobile-padding" style="padding:30px 36px 20px;">
                            
                            <!-- Greeting -->
                            <p style="margin:0 0 12px; font-size:15px; font-weight:600; color:#0f172a;">
                                Mabuhay, {{ $bookerName }}!
                            </p>
                            <p style="margin:0 0 22px; font-size:13.5px; color:#475569; line-height:1.6;">
                                Thank you for booking with Hinaguan Nature Park. Your official reservation details and gate check-in pass are presented below. An official printable PDF pass is also attached to this email.
                            </p>

                            <!-- DOWNLOAD PDF ACTION BANNER -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:18px 20px; text-align:center;">
                                        <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#15803d; margin-bottom:6px;">
                                            Official Printable Document
                                        </div>
                                        <div style="font-size:13px; color:#334155; margin-bottom:14px;">
                                            You can download your official confirmation pass as a PDF document for printing or offline presentation:
                                        </div>
                                        <div>
                                            <a href="{{ $downloadPdfUrl }}" class="btn-download" style="display:inline-block; background:#15803d; color:#ffffff; text-decoration:none; font-size:13.5px; font-weight:700; padding:11px 26px; border-radius:8px; letter-spacing:0.3px;">
                                                Download PDF Entry Pass
                                            </a>
                                        </div>
                                        <div style="margin-top:10px; font-size:11.5px; color:#64748b;">
                                            (A copy is also attached directly to this email as a PDF file)
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- GATE QR PASS CARD -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:12px; margin-bottom:24px;">
                                <tr>
                                    <td style="background:#f1f5f9; padding:10px 18px; border-bottom:1px solid #cbd5e1;">
                                        <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#334155;">
                                            Official Check-In QR Pass
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:22px 20px; text-align:center;">
                                        
                                        <div style="font-size:12.5px; color:#475569; margin-bottom:14px;">
                                            Scan this code at the park entrance gate or reception desk
                                        </div>

                                        <!-- QR Code Box -->
                                        <table align="center" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto; background:#ffffff; padding:12px; border-radius:10px; border:1px solid #cbd5e1;">
                                            <tr>
                                                <td align="center">
                                                    <img class="qr-code-img" src="{{ $qrImageUrl }}" alt="Reservation QR Code" width="220" height="220" style="display:block; width:220px; height:220px; border-radius:6px; background:#ffffff;" />
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Pass Subtext -->
                                        <div style="margin-top:12px; font-size:12px; font-weight:600; color:#334155;">
                                            Pass Reference: <code style="font-family:monospace; background:#e2e8f0; color:#0f172a; padding:2px 6px; border-radius:4px; font-size:11.5px;">{{ $qrPayload }}</code>
                                        </div>
                                        <div style="margin-top:6px; font-size:11.5px; color:#64748b;">
                                            You may present this on your mobile screen or bring a printed copy of the attached PDF.
                                        </div>

                                    </td>
                                </tr>
                            </table>

                            <!-- SCHEDULE & ARRIVAL CARD -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:22px;">
                                <tr>
                                    <td style="background:#f8fafc; padding:10px 18px; border-bottom:1px solid #e2e8f0;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#334155;">
                                                    Schedule &amp; Arrival Information
                                                </td>
                                                <td align="right" style="font-size:12px; font-weight:600; color:#15803d;">
                                                    {{ $reservation->number_of_guests ?? 1 }} Guest{{ ($reservation->number_of_guests ?? 1) > 1 ? 's' : '' }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 20px;">
                                        
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <!-- Date Row -->
                                            <tr>
                                                <td style="padding-bottom:12px;">
                                                    <div style="font-size:10.5px; text-transform:uppercase; font-weight:700; color:#64748b; letter-spacing:0.5px;">Reservation Date</div>
                                                    <div style="font-size:14px; font-weight:700; color:#0f172a; margin-top:2px;">
                                                        {{ $dateDisplay }}
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Time Slot Row -->
                                            <tr>
                                                <td style="padding-bottom:12px;">
                                                    <div style="font-size:10.5px; text-transform:uppercase; font-weight:700; color:#64748b; letter-spacing:0.5px;">Booked Time Slot</div>
                                                    <div style="font-size:13.5px; font-weight:600; color:#0f172a; margin-top:2px;">
                                                        {{ $slotLabel }}
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Arrival Window -->
                                            <tr>
                                                <td style="padding-bottom:12px;">
                                                    <div style="font-size:10.5px; text-transform:uppercase; font-weight:700; color:#9a3412; letter-spacing:0.5px;">Target Arrival &amp; Check-In Window</div>
                                                    <div style="font-size:14px; font-weight:700; color:#9a3412; margin-top:2px;">
                                                        Arrive at {{ $arriveTargetTime }}
                                                    </div>
                                                    <div style="font-size:12.5px; color:#334155; margin-top:4px; line-height:1.5;">
                                                        {{ $arrivalRecommendation }}
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Departure Time -->
                                            <tr>
                                                <td>
                                                    <div style="font-size:10.5px; text-transform:uppercase; font-weight:700; color:#64748b; letter-spacing:0.5px;">Check-Out / Duration</div>
                                                    <div style="font-size:13.5px; font-weight:600; color:#0f172a; margin-top:2px;">
                                                        {{ $departureTime }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>

                                    </td>
                                </tr>
                            </table>

                            <!-- GUEST CONTACT DETAILS -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:22px;">
                                <tr>
                                    <td style="background:#f8fafc; padding:10px 18px; border-bottom:1px solid #e2e8f0; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#334155;">
                                        Guest Contact Information
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="50%" class="col-half" valign="top" style="padding-bottom:10px; padding-right:10px;">
                                                    <div style="font-size:10.5px; text-transform:uppercase; font-weight:700; color:#64748b;">Booker Name</div>
                                                    <div style="font-size:13.5px; font-weight:700; color:#0f172a; margin-top:2px;">{{ $bookerName }}</div>
                                                </td>
                                                <td width="50%" class="col-half" valign="top" style="padding-bottom:10px; padding-left:10px;">
                                                    <div style="font-size:10.5px; text-transform:uppercase; font-weight:700; color:#64748b;">Contact Phone</div>
                                                    <div style="font-size:13.5px; font-weight:700; color:#0f172a; margin-top:2px;">{{ $phone }}</div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="50%" class="col-half" valign="top" style="padding-right:10px;">
                                                    <div style="font-size:10.5px; text-transform:uppercase; font-weight:700; color:#64748b;">Email Address</div>
                                                    <div style="font-size:13.5px; font-weight:600; color:#0f172a; margin-top:2px; word-break:break-all;">{{ $email }}</div>
                                                </td>
                                                <td width="50%" class="col-half" valign="top" style="padding-left:10px;">
                                                    <div style="font-size:10.5px; text-transform:uppercase; font-weight:700; color:#64748b;">Total Guests</div>
                                                    <div style="font-size:13.5px; font-weight:600; color:#0f172a; margin-top:2px;">{{ $reservation->number_of_guests ?? 1 }} Person(s)</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- AVAILED AMENITIES TABLE -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:22px;">
                                <tr>
                                    <td style="background:#f8fafc; padding:10px 18px; border-bottom:1px solid #e2e8f0; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#334155;">
                                        Reserved Amenities &amp; Inclusions
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 20px;">
                                        
                                        @if(!empty($amenities) && count($amenities) > 0)
                                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                @foreach($amenities as $index => $item)
                                                    <tr style="{{ $loop->last && !$entranceFee ? '' : 'border-bottom:1px solid #f1f5f9;' }}">
                                                        <td style="padding:8px 0; vertical-align:top;">
                                                            <div style="font-size:13.5px; font-weight:700; color:#0f172a;">
                                                                {{ $item['name'] }}
                                                                @if($item['quantity'] > 1)
                                                                    <span style="font-size:11.5px; font-weight:600; color:#15803d; background:#dcfce7; padding:2px 6px; border-radius:4px;">x{{ $item['quantity'] }}</span>
                                                                @endif
                                                            </div>
                                                            <div style="font-size:11.5px; color:#64748b; margin-top:2px;">
                                                                Package / Slot: <span style="color:#334155; font-weight:600;">{{ $item['pricing_type'] }}</span>
                                                            </div>
                                                        </td>
                                                        <td align="right" style="padding:8px 0; vertical-align:top; white-space:nowrap;">
                                                            <div style="font-size:13.5px; font-weight:700; color:#0f172a; font-family:monospace;">
                                                                PHP {{ number_format($item['subtotal'], 2) }}
                                                            </div>
                                                            @if($item['quantity'] > 1)
                                                                <div style="font-size:10.5px; color:#64748b;">
                                                                    (PHP {{ number_format($item['price'], 2) }} each)
                                                                </div>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach

                                                <!-- Entrance Fee Add-on if recorded -->
                                                @if($entranceFee)
                                                    <tr style="border-top:1px solid #f1f5f9;">
                                                        <td style="padding:8px 0; vertical-align:top;">
                                                            <div style="font-size:13.5px; font-weight:700; color:#0f172a;">
                                                                Entrance Fee ({{ $entranceFee->adult_count ?? 0 }} Adults, {{ $entranceFee->child_count ?? 0 }} Children)
                                                            </div>
                                                            @if($entranceFee->pool_fee > 0)
                                                                <div style="font-size:11.5px; color:#64748b; margin-top:2px;">
                                                                    Includes pool access pass
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td align="right" style="padding:8px 0; vertical-align:top; white-space:nowrap;">
                                                            <div style="font-size:13.5px; font-weight:700; color:#0f172a; font-family:monospace;">
                                                                PHP {{ number_format($entranceFee->total_amount, 2) }}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endif
                                            </table>
                                        @else
                                            <p style="margin:0; font-size:12.5px; color:#64748b; font-style:italic;">
                                                Standard park access reservation. Specific amenity details will be confirmed upon entry.
                                            </p>
                                        @endif

                                    </td>
                                </tr>
                            </table>

                            <!-- BILLING & PAYMENT SUMMARY CARD -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:24px;">
                                <tr>
                                    <td style="background:#f1f5f9; padding:10px 18px; border-bottom:1px solid #e2e8f0;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#334155;">
                                                    Billing &amp; Payment Breakdown
                                                </td>
                                                <td align="right">
                                                    @if($remainingBalance <= 0 && $totalAmount > 0)
                                                        <span style="font-size:11px; font-weight:700; text-transform:uppercase; background:#dcfce7; color:#15803d; padding:3px 8px; border-radius:4px; border:1px solid #bbf7d0;">
                                                            Fully Paid
                                                        </span>
                                                    @elseif($amountPaid > 0)
                                                        <span style="font-size:11px; font-weight:700; text-transform:uppercase; background:#fef3c7; color:#b45309; padding:3px 8px; border-radius:4px; border:1px solid #fde68a;">
                                                            Downpayment Confirmed
                                                        </span>
                                                    @else
                                                        <span style="font-size:11px; font-weight:700; text-transform:uppercase; background:#e0f2fe; color:#0369a1; padding:3px 8px; border-radius:4px; border:1px solid #bae6fd;">
                                                            {{ $paymentStatus }}
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="padding-bottom:6px; font-size:12.5px; color:#64748b;">Total Booking Amount:</td>
                                                <td align="right" style="padding-bottom:6px; font-size:13.5px; font-weight:700; color:#0f172a; font-family:monospace;">
                                                    PHP {{ number_format($totalAmount, 2) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-bottom:6px; font-size:12.5px; color:#64748b;">
                                                    Amount Paid ({{ $paymentMethod }}):
                                                </td>
                                                <td align="right" style="padding-bottom:6px; font-size:13.5px; font-weight:700; color:#15803d; font-family:monospace;">
                                                    PHP {{ number_format($amountPaid, 2) }}
                                                </td>
                                            </tr>
                                            <tr style="border-top:1px dashed #cbd5e1;">
                                                <td style="padding-top:10px; font-size:13.5px; font-weight:700; color:#0f172a;">
                                                    Remaining Balance Due at Arrival:
                                                </td>
                                                <td align="right" style="padding-top:10px; font-size:15px; font-weight:700; color:{{ $remainingBalance > 0 ? '#b45309' : '#15803d' }}; font-family:monospace;">
                                                    PHP {{ number_format($remainingBalance, 2) }}
                                                </td>
                                            </tr>
                                        </table>

                                        @if($remainingBalance > 0)
                                            <div style="margin-top:12px; padding:8px 12px; background:#fffbeb; border:1px solid #fef08a; border-radius:6px; font-size:11.5px; color:#92400e; line-height:1.45;">
                                                <strong>Note:</strong> Outstanding balance of <strong>PHP {{ number_format($remainingBalance, 2) }}</strong> can be settled at the park reception desk upon arrival via Cash or GCash.
                                            </div>
                                        @endif

                                        <div style="margin-top:10px; padding:9px 12px; background:#fff1f2; border:1px solid #fecdd3; border-left:3px solid #e11d48; border-radius:6px; font-size:11.5px; color:#9f1239; line-height:1.45;">
                                            <strong>Strictly No Refund:</strong> All deposits, downpayments, and reservation fees are final and strictly non-refundable under normal booking conditions.
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- CHECK-IN GUIDELINES -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#fffaf5; border:1px solid #fed7aa; border-radius:12px; margin-bottom:20px;">
                                <tr>
                                    <td style="background:#fef3c7; padding:10px 18px; border-bottom:1px solid #fcd34d;">
                                        <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#92400e;">
                                            Important Check-In Guidelines &amp; Policies
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="20" valign="top" style="padding-bottom:10px; font-size:12px; font-weight:bold; color:#9a3412;">1.</td>
                                                <td style="padding-bottom:10px; font-size:12.5px; color:#451a03; line-height:1.5;">
                                                    <strong>Present Your Entry QR Pass:</strong> Have your QR code ready on your phone or bring the attached PDF printout. It is required for automatic gate check-in.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="20" valign="top" style="padding-bottom:10px; font-size:12px; font-weight:bold; color:#9a3412;">2.</td>
                                                <td style="padding-bottom:10px; font-size:12.5px; color:#451a03; line-height:1.5;">
                                                    <strong>Valid ID Verification:</strong> If the QR code is inaccessible, you must present a valid Government or Student ID matching <strong>{{ $bookerName }}</strong>.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="20" valign="top" style="padding-bottom:10px; font-size:12px; font-weight:bold; color:#9a3412;">3.</td>
                                                <td style="padding-bottom:10px; font-size:12.5px; color:#451a03; line-height:1.5;">
                                                    <strong>Reservation Reference:</strong> Keep note of your <strong>Reservation ID #{{ $reservation->id }}</strong> for any front-desk inquiries.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="20" valign="top" style="font-size:12px; font-weight:bold; color:#9a3412;">4.</td>
                                                <td style="font-size:12.5px; color:#451a03; line-height:1.5;">
                                                    <strong>Strictly No Refund Policy:</strong> All downpayments, fees, and booking payments are strictly non-refundable under normal booking conditions.
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- PARK RULES & GUIDELINES -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff; border:1px solid #d1fae5; border-radius:12px; margin-bottom:22px; overflow:hidden;">
                                <tr>
                                    <td style="background:#f0fdf4; padding:11px 18px; border-bottom:1px solid #bbf7d0;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#166534;">
                                                    Park Rules &amp; Guidelines
                                                </td>
                                                <td align="right" style="font-size:11px; font-weight:600; color:#15803d;">
                                                    Enjoy Responsibly
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <p style="margin:0 0 14px; font-size:12.5px; color:#475569; line-height:1.5;">
                                            To ensure a safe, peaceful, and enjoyable experience for all guests and nature, please take note of our park policies:
                                        </p>
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            @if(isset($parkRules) && count($parkRules) > 0)
                                                @foreach($parkRules as $index => $rule)
                                                    <tr>
                                                        <td width="22" valign="top" style="padding-bottom:11px; font-size:12px; font-weight:700; color:#15803d; line-height:1.4;">
                                                            {{ $index + 1 }}.
                                                        </td>
                                                        <td style="padding-bottom:11px; font-size:12.5px; color:#1e293b; line-height:1.55;">
                                                            <strong style="color:#0f172a;">{{ $rule->rule_name }}:</strong>
                                                            <span style="color:#475569;">{{ $rule->rule_descriptions }}</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td width="22" valign="top" style="padding-bottom:11px; font-size:12px; font-weight:700; color:#15803d; line-height:1.4;">1.</td>
                                                    <td style="padding-bottom:11px; font-size:12.5px; color:#1e293b; line-height:1.55;">
                                                        <strong style="color:#0f172a;">Proper Swimming Pool Attire:</strong>
                                                        <span style="color:#475569;">Proper swimwear (rash guards, swim trunks, bathing suits) is required when entering swimming pools. Cotton t-shirts, denim pants, and undergarments are strictly prohibited in the water.</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="22" valign="top" style="padding-bottom:11px; font-size:12px; font-weight:700; color:#15803d; line-height:1.4;">2.</td>
                                                    <td style="padding-bottom:11px; font-size:12.5px; color:#1e293b; line-height:1.55;">
                                                        <strong style="color:#0f172a;">Outside Food &amp; Corkage Policy:</strong>
                                                        <span style="color:#475569;">Guests may bring outside food and non-alcoholic drinks with zero corkage fee. Free outdoor grilling stations are available (please bring your own charcoal and utensils).</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="22" valign="top" style="padding-bottom:11px; font-size:12px; font-weight:700; color:#15803d; line-height:1.4;">3.</td>
                                                    <td style="padding-bottom:11px; font-size:12.5px; color:#1e293b; line-height:1.55;">
                                                        <strong style="color:#0f172a;">Quiet Hours &amp; Respect:</strong>
                                                        <span style="color:#475569;">Quiet hours are strictly observed from 10:00 PM to 6:00 AM for the comfort of overnight guests and nature. High-volume sound systems must be turned down.</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="22" valign="top" style="padding-bottom:11px; font-size:12px; font-weight:700; color:#15803d; line-height:1.4;">4.</td>
                                                    <td style="padding-bottom:11px; font-size:12.5px; color:#1e293b; line-height:1.55;">
                                                        <strong style="color:#0f172a;">Clean As You Go (CLAYGO):</strong>
                                                        <span style="color:#475569;">Help maintain the pristine beauty of our park by disposing of all garbage into labeled waste segregation bins.</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="22" valign="top" style="padding-bottom:11px; font-size:12px; font-weight:700; color:#15803d; line-height:1.4;">5.</td>
                                                    <td style="padding-bottom:11px; font-size:12.5px; color:#1e293b; line-height:1.55;">
                                                        <strong style="color:#0f172a;">Pet Policy:</strong>
                                                        <span style="color:#475569;">Pets are welcome inside park grounds but must be kept on a leash at all times. Pet owners are responsible for waste cleanup.</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="22" valign="top" style="font-size:12px; font-weight:700; color:#15803d; line-height:1.4;">6.</td>
                                                    <td style="font-size:12.5px; color:#1e293b; line-height:1.55;">
                                                        <strong style="color:#0f172a;">Designated Smoking Areas:</strong>
                                                        <span style="color:#475569;">Smoking and vaping are only permitted in designated outdoor smoking zones away from cottages and children's swimming areas.</span>
                                                    </td>
                                                </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer & Contact -->
                    <tr>
                        <td style="background:#0f2918; padding:24px 36px; text-align:center; color:#cbd5e1; border-top:1px solid #1e3a2b;">
                            
                            <div style="font-size:13px; font-weight:700; color:#ffffff; letter-spacing:0.8px; margin-bottom:4px; text-transform:uppercase;">
                                Hinaguan Nature Park
                            </div>
                            <div style="font-size:11.5px; color:#94a3b8; margin-bottom:10px;">
                                Jasaan, Misamis Oriental, Philippines
                            </div>

                            <div style="font-size:11.5px; color:#cbd5e1; margin-bottom:12px;">
                                Contact: {{ $parkPhone }} &nbsp;|&nbsp; Email: {{ $parkEmail }}
                            </div>

                            <div style="font-size:10.5px; color:#64748b; line-height:1.5; border-top:1px solid rgba(255,255,255,0.08); padding-top:12px;">
                                This is an automated booking confirmation email. An official PDF copy of your entry pass is attached.<br>
                                If you need assistance with your booking, please contact our front desk at {{ $parkPhone }}.
                            </div>

                        </td>
                    </tr>

                </table>
                <!-- End Main Card -->

            </td>
        </tr>
    </table>

</body>
</html>
