<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Confirmation & Entry Pass - Hinaguan Nature Park</title>
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
            .qr-code-img { width: 220px !important; max-width: 220px !important; }
            .col-half { display: block !important; width: 100% !important; padding-right: 0 !important; padding-left: 0 !important; margin-bottom: 12px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#eef2eb; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing:antialiased; color:#233027; line-height:1.6;">

    <!-- Wrapper Table -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef2eb; padding:24px 12px;">
        <tr>
            <td align="center">
                
                <!-- Main Email Card -->
                <table class="email-container" width="620" cellpadding="0" cellspacing="0" border="0" style="width:620px; max-width:620px; background:#ffffff; border-radius:24px; overflow:hidden; box-shadow:0 12px 35px rgba(20,60,35,0.08); border:1px solid #dbe5d8;">
                    
                    <!-- ── HEADER BANNER ── -->
                    <tr>
                        <td class="mobile-header" style="background:linear-gradient(145deg, #134629 0%, #1c5e37 60%, #297c4a 100%); padding:32px 36px; text-align:center; color:#ffffff;">
                            
                            <!-- Brand & Badge -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center">
                                        <div style="display:inline-block; padding:5px 14px; background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.25); border-radius:20px; font-size:11px; font-weight:700; letter-spacing:2.5px; text-transform:uppercase; color:#d4edd9; margin-bottom:12px;">
                                            🌲 HINAGUAN NATURE PARK
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <h1 style="margin:6px 0 8px; font-size:26px; font-weight:800; line-height:1.25; color:#ffffff; letter-spacing:-0.5px;">
                                Booking Confirmed!
                            </h1>
                            <p style="margin:0; font-size:14px; color:#e0fae6; line-height:1.5;">
                                Your online reservation has been confirmed. Please present your entry QR pass upon arrival.
                            </p>

                            <!-- Reservation Tag -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:18px;">
                                <tr>
                                    <td align="center">
                                        <div style="display:inline-block; background:#ffffff; color:#134629; font-size:13px; font-weight:700; padding:8px 18px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.12);">
                                            Reservation ID: <span style="font-size:15px; color:#15803d; letter-spacing:0.5px;">#{{ $reservation->id }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- ── BODY CONTENT ── -->
                    <tr>
                        <td class="mobile-padding" style="padding:32px 36px 24px;">
                            
                            <!-- Greeting -->
                            <p style="margin:0 0 16px; font-size:16px; font-weight:600; color:#1a3824;">
                                Mabuhay, {{ $bookerName }}!
                            </p>
                            <p style="margin:0 0 24px; font-size:14px; color:#4a5c4f; line-height:1.65;">
                                Thank you for choosing Hinaguan Nature Park. We have successfully secured your reservation. Below is your official entry QR pass along with complete details of your booked stay and amenities.
                            </p>

                            <!-- ── QR PASS CARD (PRIMARY HERO) ── -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f8f4; border:2px dashed #9bc4a5; border-radius:20px; margin-bottom:28px; overflow:hidden;">
                                <tr>
                                    <td style="padding:24px 20px; text-align:center;">
                                        
                                        <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1.5px; color:#2e6a42; margin-bottom:6px;">
                                            🎟️ Official Check-in QR Pass
                                        </div>
                                        <div style="font-size:13px; color:#5c7562; margin-bottom:16px;">
                                            Scan this at the entrance gate or reception desk
                                        </div>

                                        <!-- QR Code Box -->
                                        <table align="center" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto; background:#ffffff; padding:14px; border-radius:16px; box-shadow:0 6px 18px rgba(25,75,40,0.12); border:1px solid #d3e4d7;">
                                            <tr>
                                                <td align="center">
                                                    <img class="qr-code-img" src="{{ $qrImageUrl }}" alt="Reservation QR Code" width="240" height="240" style="display:block; width:240px; height:240px; border-radius:10px; background:#ffffff;" />
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Pass Subtext -->
                                        <div style="margin-top:14px; font-size:13px; font-weight:600; color:#1a3d28;">
                                            Reference: <code style="font-family:monospace; background:#e4efe6; color:#165b32; padding:3px 8px; border-radius:6px; font-size:12px;">{{ $qrPayload }}</code>
                                        </div>
                                        <div style="margin-top:6px; font-size:12px; color:#6b8572;">
                                            💡 You can save this email or take a screenshot on your mobile phone.
                                        </div>

                                    </td>
                                </tr>
                            </table>

                            <!-- ── WHEN TO ARRIVE & SCHEDULE CARD ── -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff; border:1px solid #c9decb; border-radius:18px; margin-bottom:24px; overflow:hidden; box-shadow:0 3px 12px rgba(20,50,30,0.04);">
                                <tr>
                                    <td style="background:#e8f4eb; padding:12px 18px; border-bottom:1px solid #c9decb;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="font-size:14px; font-weight:700; color:#144629;">
                                                    📅 Schedule & Arrival Details
                                                </td>
                                                <td align="right" style="font-size:12px; font-weight:600; color:#1b6338;">
                                                    {{ $reservation->number_of_guests ?? 1 }} Guest{{ ($reservation->number_of_guests ?? 1) > 1 ? 's' : '' }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:18px 20px;">
                                        
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <!-- Date Row -->
                                            <tr>
                                                <td width="36" valign="top" style="padding-bottom:12px; font-size:18px; line-height:1;">
                                                    📆
                                                </td>
                                                <td style="padding-bottom:12px;">
                                                    <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:#6d8474; letter-spacing:0.5px;">Reservation Date</div>
                                                    <div style="font-size:15px; font-weight:700; color:#173d25; margin-top:2px;">
                                                        {{ $dateDisplay }}
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Time Slot Row -->
                                            <tr>
                                                <td width="36" valign="top" style="padding-bottom:12px; font-size:18px; line-height:1;">
                                                    ⏰
                                                </td>
                                                <td style="padding-bottom:12px;">
                                                    <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:#6d8474; letter-spacing:0.5px;">Booked Time Slot</div>
                                                    <div style="font-size:14px; font-weight:700; color:#173d25; margin-top:2px;">
                                                        {{ $slotLabel }}
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Arrival Window (Highlight) -->
                                            <tr>
                                                <td width="36" valign="top" style="padding-bottom:14px; font-size:18px; line-height:1;">
                                                    🚪
                                                </td>
                                                <td style="padding-bottom:14px;">
                                                    <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:#b45309; letter-spacing:0.5px;">When to Arrive / Check-in Window</div>
                                                    <div style="font-size:15px; font-weight:800; color:#92400e; margin-top:2px;">
                                                        Arrive at {{ $arriveTargetTime }}
                                                    </div>
                                                    <div style="font-size:13px; color:#3d5242; margin-top:4px; line-height:1.5;">
                                                        {{ $arrivalRecommendation }}
                                                    </div>
                                                    <div style="margin-top:6px; display:inline-block; background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:6px 10px; font-size:12px; color:#92400e; font-weight:600;">
                                                        ✨ We can still check you in even if you're late, but it's better to be early!
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Departure Time -->
                                            <tr>
                                                <td width="36" valign="top" style="font-size:18px; line-height:1;">
                                                    🕒
                                                </td>
                                                <td>
                                                    <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:#6d8474; letter-spacing:0.5px;">Check-Out / Duration</div>
                                                    <div style="font-size:14px; font-weight:600; color:#173d25; margin-top:2px;">
                                                        {{ $departureTime }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>

                                    </td>
                                </tr>
                            </table>

                            <!-- ── GUEST & CONTACT DETAILS ── -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff; border:1px solid #e0e8dd; border-radius:18px; margin-bottom:24px; overflow:hidden;">
                                <tr>
                                    <td style="background:#f8faf8; padding:12px 18px; border-bottom:1px solid #e0e8dd; font-size:14px; font-weight:700; color:#144629;">
                                        👤 Guest Contact Information
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="50%" class="col-half" valign="top" style="padding-bottom:10px; padding-right:10px;">
                                                    <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:#788c7e;">Booker Name</div>
                                                    <div style="font-size:14px; font-weight:700; color:#193a26; margin-top:2px;">{{ $bookerName }}</div>
                                                </td>
                                                <td width="50%" class="col-half" valign="top" style="padding-bottom:10px; padding-left:10px;">
                                                    <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:#788c7e;">Contact Phone</div>
                                                    <div style="font-size:14px; font-weight:700; color:#193a26; margin-top:2px;">{{ $phone }}</div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="50%" class="col-half" valign="top" style="padding-right:10px;">
                                                    <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:#788c7e;">Email Address</div>
                                                    <div style="font-size:14px; font-weight:600; color:#193a26; margin-top:2px; word-break:break-all;">{{ $email }}</div>
                                                </td>
                                                <td width="50%" class="col-half" valign="top" style="padding-left:10px;">
                                                    <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:#788c7e;">Total Guests</div>
                                                    <div style="font-size:14px; font-weight:600; color:#193a26; margin-top:2px;">{{ $reservation->number_of_guests ?? 1 }} Person(s)</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- ── AVAILED AMENITIES TABLE ── -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff; border:1px solid #e0e8dd; border-radius:18px; margin-bottom:24px; overflow:hidden;">
                                <tr>
                                    <td style="background:#f8faf8; padding:12px 18px; border-bottom:1px solid #e0e8dd; font-size:14px; font-weight:700; color:#144629;">
                                        🏕️ Availed Amenities & Inclusions
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 20px;">
                                        
                                        @if(!empty($amenities) && count($amenities) > 0)
                                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                @foreach($amenities as $index => $item)
                                                    <tr style="{{ $loop->last ? '' : 'border-bottom:1px solid #edf2ec;' }}">
                                                        <td style="padding:10px 0; vertical-align:top;">
                                                            <div style="font-size:14px; font-weight:700; color:#173d25;">
                                                                {{ $item['name'] }}
                                                                @if($item['quantity'] > 1)
                                                                    <span style="font-size:12px; font-weight:600; color:#2d6a4f; background:#e8f4eb; padding:2px 6px; border-radius:6px;">x{{ $item['quantity'] }}</span>
                                                                @endif
                                                            </div>
                                                            <div style="font-size:12px; color:#6b8271; margin-top:2px;">
                                                                Package / Slot: <span style="color:#2f533b; font-weight:600;">{{ $item['pricing_type'] }}</span>
                                                            </div>
                                                        </td>
                                                        <td align="right" style="padding:10px 0; vertical-align:top; white-space:nowrap;">
                                                            <div style="font-size:14px; font-weight:700; color:#173d25;">
                                                                ₱{{ number_format($item['subtotal'], 2) }}
                                                            </div>
                                                            @if($item['quantity'] > 1)
                                                                <div style="font-size:11px; color:#889e8e;">
                                                                    (₱{{ number_format($item['price'], 2) }} each)
                                                                </div>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach

                                                <!-- Entrance Fee Add-on if recorded -->
                                                @if($entranceFee)
                                                    <tr style="border-top:1px solid #edf2ec;">
                                                        <td style="padding:10px 0; vertical-align:top;">
                                                            <div style="font-size:14px; font-weight:700; color:#173d25;">
                                                                Entrance Fee ({{ $entranceFee->adult_count ?? 0 }} Adults, {{ $entranceFee->child_count ?? 0 }} Kids)
                                                            </div>
                                                            @if($entranceFee->pool_fee > 0)
                                                                <div style="font-size:12px; color:#6b8271; margin-top:2px;">
                                                                    Includes pool access pass
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td align="right" style="padding:10px 0; vertical-align:top; white-space:nowrap;">
                                                            <div style="font-size:14px; font-weight:700; color:#173d25;">
                                                                ₱{{ number_format($entranceFee->total_amount, 2) }}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endif
                                            </table>
                                        @else
                                            <p style="margin:0; font-size:13px; color:#6b8271; font-style:italic;">
                                                Standard park access reservation. Specific amenity details will be confirmed upon entry.
                                            </p>
                                        @endif

                                    </td>
                                </tr>
                            </table>

                            <!-- ── PAYMENT & BILLING SUMMARY CARD ── -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f9fbf9; border:1px solid #d8e5d6; border-radius:18px; margin-bottom:28px; overflow:hidden;">
                                <tr>
                                    <td style="background:#edf5ee; padding:12px 18px; border-bottom:1px solid #d8e5d6;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="font-size:14px; font-weight:700; color:#144629;">
                                                    💳 Billing & Payment Breakdown
                                                </td>
                                                <td align="right">
                                                    @if($remainingBalance <= 0 && $totalAmount > 0)
                                                        <span style="font-size:11px; font-weight:700; text-transform:uppercase; background:#dcfce7; color:#15803d; padding:4px 10px; border-radius:12px; border:1px solid #bbf7d0;">
                                                            ✓ Fully Paid
                                                        </span>
                                                    @elseif($amountPaid > 0)
                                                        <span style="font-size:11px; font-weight:700; text-transform:uppercase; background:#fef3c7; color:#b45309; padding:4px 10px; border-radius:12px; border:1px solid #fde68a;">
                                                            Downpayment Confirmed
                                                        </span>
                                                    @else
                                                        <span style="font-size:11px; font-weight:700; text-transform:uppercase; background:#e0f2fe; color:#0369a1; padding:4px 10px; border-radius:12px; border:1px solid #bae6fd;">
                                                            {{ $paymentStatus }}
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="padding-bottom:8px; font-size:13px; color:#5c7363;">Total Booking Amount:</td>
                                                <td align="right" style="padding-bottom:8px; font-size:14px; font-weight:700; color:#1c4028;">
                                                    ₱{{ number_format($totalAmount, 2) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-bottom:8px; font-size:13px; color:#5c7363;">
                                                    Amount Paid ({{ $paymentMethod }}):
                                                </td>
                                                <td align="right" style="padding-bottom:8px; font-size:14px; font-weight:700; color:#15803d;">
                                                    ₱{{ number_format($amountPaid, 2) }}
                                                </td>
                                            </tr>
                                            <tr style="border-top:1px dashed #cbdcd0;">
                                                <td style="padding-top:12px; font-size:14px; font-weight:800; color:#144629;">
                                                    Remaining Balance Due at Arrival:
                                                </td>
                                                <td align="right" style="padding-top:12px; font-size:17px; font-weight:800; color:{{ $remainingBalance > 0 ? '#b45309' : '#15803d' }};">
                                                    ₱{{ number_format($remainingBalance, 2) }}
                                                </td>
                                            </tr>
                                        </table>

                                        @if($remainingBalance > 0)
                                            <div style="margin-top:14px; padding:10px 14px; background:#fffbeb; border:1px solid #fef08a; border-radius:10px; font-size:12px; color:#92400e; line-height:1.5;">
                                                📌 <strong>Note:</strong> Your remaining balance of <strong>₱{{ number_format($remainingBalance, 2) }}</strong> can be settled at the park front desk upon check-in via Cash or GCash.
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <!-- ── VISITOR REMINDERS & IMPORTANT NOTICE ── -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#fffdfa; border:2px solid #ecd8af; border-radius:18px; margin-bottom:24px; overflow:hidden; box-shadow:0 4px 14px rgba(180,83,9,0.06);">
                                <tr>
                                    <td style="background:#fef3c7; padding:12px 18px; border-bottom:1px solid #fcd34d;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="font-size:13px; font-weight:800; text-transform:uppercase; letter-spacing:1px; color:#92400e;">
                                                    ⚠️ Important Check-in Notice &amp; Requirements
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="26" valign="top" style="padding-bottom:12px; font-size:16px;">📲</td>
                                                <td style="padding-bottom:12px; font-size:13px; color:#453723; line-height:1.55;">
                                                    <strong style="color:#78350f;">Bring Your Entry QR Code (Required):</strong>
                                                    Please have this QR code ready on your phone (or a printed copy) upon arrival. It is <strong>required for check-in</strong> to automatically identify and verify that you are the rightful owner of this reservation.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="26" valign="top" style="padding-bottom:12px; font-size:16px;">🪪</td>
                                                <td style="padding-bottom:12px; font-size:13px; color:#453723; line-height:1.55;">
                                                    <strong style="color:#78350f;">Valid ID Verification (If QR Code Is Missing):</strong>
                                                    If you fail to bring or present your QR code, you must present a <strong>valid Government or Student ID matching the name entered on this reservation (<span style="color:#92400e; font-weight:700;">{{ $bookerName }}</span>)</strong> to confirm your identity.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="26" valign="top" style="padding-bottom:12px; font-size:16px;">🔢</td>
                                                <td style="padding-bottom:12px; font-size:13px; color:#453723; line-height:1.55;">
                                                    <strong style="color:#78350f;">Know Your Reservation ID:</strong>
                                                    Please take note of your <strong>Reservation ID #{{ $reservation->id }}</strong> in case our front-desk staff needs to look up or verify your booking manually.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="26" valign="top" style="font-size:16px;">🌿</td>
                                                <td style="font-size:13px; color:#453723; line-height:1.55;">
                                                    <strong style="color:#78350f;">Park Guidelines:</strong>
                                                    Please help us keep the park clean (Leave No Trace). Proper swimwear is required when using the swimming facilities.
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- ── FOOTER & CONTACT ── -->
                    <tr>
                        <td style="background:#163b25; padding:28px 36px; text-align:center; color:#d4edd9; border-top:1px solid #235237;">
                            
                            <div style="font-size:15px; font-weight:800; color:#ffffff; letter-spacing:1px; margin-bottom:6px;">
                                HINAGUAN NATURE PARK
                            </div>
                            <div style="font-size:12px; color:#a3cbb0; margin-bottom:14px;">
                                Jasaan, Misamis Oriental, Philippines • Open Daily
                            </div>

                            <!-- Contact info badges -->
                            <table align="center" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 16px;">
                                <tr>
                                    <td align="center" style="font-size:12px; color:#d4edd9;">
                                        📞 <strong>Phone:</strong> {{ $parkPhone }} &nbsp;|&nbsp; ✉️ <strong>Email:</strong> {{ $parkEmail }}
                                    </td>
                                </tr>
                            </table>

                            <div style="font-size:11px; color:#7ca88b; line-height:1.6; border-top:1px solid rgba(255,255,255,0.1); padding-top:14px;">
                                This is an automated booking confirmation email. Please do not reply directly to this email.<br>
                                Need help or modifications? Contact our front desk directly at <strong>{{ $parkPhone }}</strong>.
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
