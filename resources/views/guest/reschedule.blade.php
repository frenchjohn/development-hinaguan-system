<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reschedule Your Reservation — Hinaguan Nature Park</title>

    <link rel="icon" type="image/jpeg" href="{{ asset('storage/design_images/main_logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:500,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css'])

    <style>
        :root {
            --hp-green: #183d28;
            --hp-green-light: #2d6a4f;
            --hp-cream: #fbfbf9;
            --hp-accent: #c8a45d;
        }
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f4f7f4;
            color: #1e293b;
            min-height: 100vh;
        }
        .font-display {
            font-family: 'Playfair Display', Georgia, serif;
        }
        .cal-day-cell {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.15s ease;
            position: relative;
            user-select: none;
        }
        .cal-day-cell.available {
            cursor: pointer;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            color: #183d28;
        }
        .cal-day-cell.available:hover {
            border-color: #2d6a4f;
            background-color: #ecfdf5;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.06);
        }
        .cal-day-cell.disabled {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed !important;
            opacity: 0.55;
            text-decoration: line-through;
            pointer-events: none;
        }
        .cal-day-cell.today-marker::after {
            content: '';
            position: absolute;
            bottom: 4px;
            width: 4px;
            height: 4px;
            border-radius: 9999px;
            background-color: #c8a45d;
        }

        .cal-day-cell.swap-required {
            border-color: #fde68a !important;
            background-color: #fffbeb !important;
            color: #92400e !important;
            font-weight: 700;
        }
        .cal-day-cell.swap-required:hover {
            border-color: #f59e0b !important;
            background-color: #fef3c7 !important;
        }
        .cal-day-cell.swap-required::after {
            content: '';
            position: absolute;
            top: 4px;
            right: 4px;
            width: 5px;
            height: 5px;
            border-radius: 9999px;
            background-color: #d97706;
        }

        /* Continuous stay range selection styles */
        .cal-day-cell.range-single {
            background-color: #183d28 !important;
            border-color: #183d28 !important;
            color: #ffffff !important;
            border-radius: 0.75rem !important;
            box-shadow: 0 4px 12px rgba(24, 61, 40, 0.35);
        }
        .cal-day-cell.range-start {
            background-color: #183d28 !important;
            border-color: #183d28 !important;
            color: #ffffff !important;
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
            box-shadow: 0 4px 12px rgba(24, 61, 40, 0.35);
        }
        .cal-day-cell.range-mid {
            background-color: #d1fae5 !important;
            border-color: #a7f3d0 !important;
            color: #064e3b !important;
            border-radius: 0 !important;
            font-weight: 700;
        }
        .cal-day-cell.range-end {
            background-color: #183d28 !important;
            border-color: #183d28 !important;
            color: #ffffff !important;
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
            box-shadow: 0 4px 12px rgba(24, 61, 40, 0.35);
        }

        /* Hover range preview */
        .cal-day-cell.range-hover-single {
            background-color: #ecfdf5 !important;
            border-color: #059669 !important;
            color: #064e3b !important;
        }
        .cal-day-cell.range-hover-start {
            background-color: #ecfdf5 !important;
            border-color: #059669 !important;
            color: #064e3b !important;
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
        }
        .cal-day-cell.range-hover-mid {
            background-color: #f0fdf4 !important;
            border-color: #a7f3d0 !important;
            color: #064e3b !important;
            border-radius: 0 !important;
        }
        .cal-day-cell.range-hover-end {
            background-color: #ecfdf5 !important;
            border-color: #059669 !important;
            color: #064e3b !important;
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
        }

        /* Mobile optimizations */
        @media (max-width: 639px) {
            .cal-day-cell {
                border-radius: 0.5rem;
                font-size: 0.75rem;
            }
            .cal-day-cell.today-marker::after {
                bottom: 2px;
                width: 3px;
                height: 3px;
            }
            .cal-day-cell.swap-required::after {
                top: 2px;
                right: 2px;
                width: 4px;
                height: 4px;
            }
            .cal-day-cell.range-single,
            .cal-day-cell.range-start,
            .cal-day-cell.range-end {
                border-radius: 0.5rem !important;
                box-shadow: 0 2px 6px rgba(24, 61, 40, 0.25);
            }
            .cal-day-cell.range-start {
                border-top-right-radius: 0 !important;
                border-bottom-right-radius: 0 !important;
            }
            .cal-day-cell.range-end {
                border-top-left-radius: 0 !important;
                border-bottom-left-radius: 0 !important;
            }
            .cal-day-cell.range-hover-start {
                border-top-right-radius: 0 !important;
                border-bottom-right-radius: 0 !important;
            }
            .cal-day-cell.range-hover-end {
                border-top-left-radius: 0 !important;
                border-bottom-left-radius: 0 !important;
            }
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">
    @php
        $parkSettings = \App\Models\ParkSetting::first();
        $daytimeStart = $parkSettings?->daytime_start ?? '08:00';
        $daytimeEnd = $parkSettings?->daytime_end ?? '18:00';
        $nighttimeStart = $parkSettings?->nighttime_start ?? '18:00';
        $nighttimeEnd = $parkSettings?->nighttime_end ?? '06:00';

        $stayDays = (int) ($reservation?->total_days ?? 1);
        if ($stayDays < 1) $stayDays = 1;

        $startSlot = $reservation?->start_slot ?? 'Daytime';
        $endSlot = $reservation?->end_slot ?? $startSlot;
        $cleanStartSlot = str_contains($startSlot, 'Night') ? 'Nighttime' : 'Daytime';
        $cleanEndSlot = str_contains($endSlot, 'Night') ? 'Nighttime' : 'Daytime';

        // Check-in & Check-out time objects
        $checkInTimeObj = \Carbon\Carbon::parse($cleanStartSlot === 'Nighttime' ? $nighttimeStart : $daytimeStart);
        $checkOutTimeObj = \Carbon\Carbon::parse($cleanEndSlot === 'Nighttime' ? $nighttimeEnd : $daytimeEnd);

        $checkInTimeStr = $checkInTimeObj->format('g:i A');
        $checkOutTimeStr = $checkOutTimeObj->format('g:i A');

        $origStart = $rescheduleRequest?->original_date;
        $origEnd = ($origStart && $stayDays > 1) ? \Carbon\Carbon::parse($origStart)->addDays($stayDays - 1)->toDateString() : $origStart;

        $reqStart = $rescheduleRequest?->requested_date;
        $reqEnd = ($reqStart && $stayDays > 1) ? \Carbon\Carbon::parse($reqStart)->addDays($stayDays - 1)->toDateString() : $reqStart;

        // Calculate full checkin and checkout datetimes
        $reqCheckInAt = null;
        $reqCheckOutAt = null;
        if ($reqStart) {
            $reqCheckInAt = \Carbon\Carbon::parse($reqStart)->setTime($checkInTimeObj->hour, $checkInTimeObj->minute);
        }
        if ($reqEnd) {
            if ($cleanEndSlot === 'Nighttime') {
                $reqCheckOutAt = \Carbon\Carbon::parse($reqEnd)->addDay()->setTime($checkOutTimeObj->hour, $checkOutTimeObj->minute);
            } else {
                $reqCheckOutAt = \Carbon\Carbon::parse($reqEnd)->setTime($checkOutTimeObj->hour, $checkOutTimeObj->minute);
            }
        }

        $origCheckInAt = null;
        $origCheckOutAt = null;
        if ($origStart) {
            $origCheckInAt = \Carbon\Carbon::parse($origStart)->setTime($checkInTimeObj->hour, $checkInTimeObj->minute);
        }
        if ($origEnd) {
            if ($cleanEndSlot === 'Nighttime') {
                $origCheckOutAt = \Carbon\Carbon::parse($origEnd)->addDay()->setTime($checkOutTimeObj->hour, $checkOutTimeObj->minute);
            } else {
                $origCheckOutAt = \Carbon\Carbon::parse($origEnd)->setTime($checkOutTimeObj->hour, $checkOutTimeObj->minute);
            }
        }

        $displayPhone = \App\Services\PhilSmsService::formatDisplayPhone($reservation?->phone);

        // 50% Downpayment Refund Calculations
        $origPaid = (float) ($reservation?->amount_paid ?? 0);
        if ($origPaid <= 0 && (float) ($reservation?->total_amount ?? 0) > 0) {
            $origPaid = round((float) $reservation->total_amount * 0.50, 2);
        }
        $refundAmount = round($origPaid * 0.50, 2);
        $retainedAmount = max(0, round($origPaid - $refundAmount, 2));
    @endphp

    <!-- Header -->
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <img src="{{ asset('storage/design_images/main_logo.jpeg') }}" alt="Hinaguan Nature Park" class="h-9 w-9 shrink-0 rounded-lg border border-slate-200 object-cover">
                <div class="min-w-0">
                    <h1 class="m-0 truncate font-display text-sm font-bold leading-tight text-[#123322] sm:text-base">Hinaguan Nature Park</h1>
                    <p class="m-0 truncate text-[11px] font-medium text-slate-500">Reservation Reschedule &amp; Refund Portal</p>
                </div>
            </div>
            <span class="hidden shrink-0 text-xs font-medium text-slate-500 sm:inline">Single-use 24h link</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-4xl w-full mx-auto px-3 sm:px-6 py-4 sm:py-8">
        @if ($errors->any())
            <div class="mb-4 sm:mb-6 p-3.5 sm:p-4 rounded-xl sm:rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs">
                <div class="font-bold mb-1 flex items-center gap-1.5">
                    <i class="bi bi-exclamation-circle-fill text-red-600"></i> Please check the required fields:
                </div>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($state === 'invalid')
            <!-- Invalid Token State -->
            <div class="mx-auto max-w-xl rounded-xl border border-slate-200 bg-white p-6 shadow-xs sm:p-8">
                <span class="text-[11px] font-bold uppercase tracking-wider text-red-700">Invalid Link</span>
                <h2 class="m-0 mt-1 font-display text-2xl font-bold text-slate-900">Invalid Rescheduling Link</h2>
                <p class="mt-2 mb-0 text-sm leading-relaxed text-slate-600">
                    The link you followed is not valid or may have been typed incorrectly. If you received this link via SMS, please check the message again or contact Hinaguan Nature Park.
                </p>
                @if (!empty($parkContact))
                    <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-semibold text-slate-700">
                        Contact Park Support: <strong class="text-slate-900">{{ $parkContact }}</strong>
                    </div>
                @endif
                <div class="mt-6">
                    <a href="{{ url('/') }}" class="inline-flex items-center rounded-lg bg-[#123322] px-5 py-2.5 text-xs font-semibold text-white no-underline transition-colors hover:bg-[#1a4730] sm:text-sm">
                        Back to Homepage
                    </a>
                </div>
            </div>

        @elseif ($state === 'used')
            <!-- Already Used State -->
            @if ($rescheduleRequest && $rescheduleRequest->isRefund())
                <div class="mx-auto max-w-xl rounded-xl border border-slate-200 bg-white p-6 shadow-xs sm:p-8">
                    <div class="border-b border-slate-100 pb-4">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800">Refund Request Recorded</span>
                        <h2 class="m-0 mt-1 font-display text-2xl font-bold text-slate-900">Refund Request Submitted</h2>
                        <p class="mt-1.5 mb-0 text-sm leading-relaxed text-slate-600">
                            Thank you, <strong class="text-slate-900">{{ $reservation?->booker_name ?: ($rescheduleRequest->ewallet_account_name ?: 'Guest') }}</strong>. Your 50% downpayment refund request has been logged for review.
                        </p>
                    </div>

                    <dl class="m-0 mt-5 divide-y divide-slate-200 rounded-lg border border-slate-200 bg-slate-50/70 px-4 text-xs sm:text-sm">
                        <div class="flex items-center justify-between py-3">
                            <dt class="text-slate-500">Reservation ID</dt>
                            <dd class="m-0 font-mono font-bold text-slate-900">#{{ $reservation?->formatted_id ?? sprintf('%03d', $rescheduleRequest->reservation_id) }}</dd>
                        </div>
                        <div class="flex items-center justify-between py-3">
                            <dt class="text-slate-500">Total Downpayment Paid</dt>
                            <dd class="m-0 font-semibold tabular-nums text-slate-900">₱{{ number_format($origPaid, 2) }}</dd>
                        </div>
                        <div class="flex items-center justify-between py-3">
                            <dt class="font-bold text-slate-900">50% Refund to Receive</dt>
                            <dd class="m-0 text-base font-bold tabular-nums text-emerald-800">₱{{ number_format($rescheduleRequest->refund_amount ?? $refundAmount, 2) }}</dd>
                        </div>
                        <div class="flex items-center justify-between py-3">
                            <dt class="text-slate-500">Receiving E-Wallet</dt>
                            <dd class="m-0 font-semibold text-slate-900">{{ $rescheduleRequest->ewallet_provider ?? 'GCash' }} &middot; {{ $rescheduleRequest->ewallet_account_number }}</dd>
                        </div>
                        <div class="flex items-center justify-between py-3">
                            <dt class="text-slate-500">Account Name</dt>
                            <dd class="m-0 font-semibold text-slate-900">{{ $rescheduleRequest->ewallet_account_name ?: ($reservation?->booker_name ?: 'Guest') }}</dd>
                        </div>
                    </dl>

                    @if ($rescheduleRequest->status === 'approved')
                        <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50/70 p-4 text-xs leading-relaxed text-emerald-900">
                            <strong class="block text-emerald-950">Refund Completed</strong>
                            Disbursed via <strong>{{ $rescheduleRequest->ewallet_provider ?? 'GCash' }}</strong> on {{ $rescheduleRequest->refunded_at?->format('M d, Y · g:i A') ?? 'recently' }}.
                            @if ($rescheduleRequest->refund_reference_number)
                                Reference #: <strong class="font-mono">{{ $rescheduleRequest->refund_reference_number }}</strong>
                            @endif
                        </div>
                    @elseif ($rescheduleRequest->status === 'declined')
                        <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-4 text-xs leading-relaxed text-red-800">
                            <strong class="block">Refund Request Declined</strong>
                            {{ $rescheduleRequest->decline_reason ?? 'Please contact Hinaguan Nature Park staff for inquiries.' }}
                        </div>
                    @else
                        <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4 text-xs leading-relaxed text-slate-600">
                            <strong class="block text-slate-900">Under Review</strong>
                            Please allow up to 24 hours for management review. Once approved, the refund will be sent to your e-wallet and confirmed via SMS. For questions, call <strong class="text-slate-900">{{ $parkContact ?: '09938373838' }}</strong>.
                        </div>
                    @endif

                    <div class="mt-6">
                        <a href="{{ url('/') }}" class="inline-flex items-center rounded-lg bg-[#123322] px-5 py-2.5 text-xs font-semibold text-white no-underline transition-colors hover:bg-[#1a4730] sm:text-sm">
                            Back to Homepage
                        </a>
                    </div>
                </div>
            @else
                <div class="mx-auto max-w-xl rounded-xl border border-slate-200 bg-white p-6 shadow-xs sm:p-8">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Single-Use Link</span>
                    <h2 class="m-0 mt-1 font-display text-2xl font-bold text-slate-900">Link Already Used</h2>
                    <p class="mt-2 mb-0 text-sm leading-relaxed text-slate-600">
                        This single-use rescheduling link has already been submitted and cannot be reused.
                    </p>

                    @if ($rescheduleRequest->requested_date)
                        <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50/70 p-4 text-xs">
                            <div class="mb-3 flex items-center justify-between border-b border-slate-200 pb-2">
                                <span class="font-bold uppercase tracking-wider text-slate-700">Submitted Request</span>
                                <span class="rounded border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-bold uppercase text-slate-800">{{ $rescheduleRequest->status }}</span>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="rounded-lg border border-slate-200 bg-white p-3">
                                    <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Check-In</span>
                                    <div class="mt-0.5 font-bold text-slate-900">{{ $reqCheckInAt?->format('F j, Y') }}</div>
                                    <div class="mt-0.5 text-slate-500">{{ $reqCheckInAt?->format('g:i A') }}</div>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-white p-3">
                                    <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Check-Out</span>
                                    <div class="mt-0.5 font-bold text-slate-900">{{ $reqCheckOutAt?->format('F j, Y') }}</div>
                                    <div class="mt-0.5 text-slate-500">{{ $reqCheckOutAt?->format('g:i A') }}</div>
                                </div>
                            </div>
                            <div class="mt-3 text-slate-600">
                                Stay Duration: <strong class="text-slate-900">{{ $stayDays }} {{ \Illuminate\Support\Str::plural('Day', $stayDays) }} Stay</strong>
                            </div>
                        </div>
                    @endif

                    <p class="mt-4 mb-0 text-xs leading-relaxed text-slate-500">
                        Our staff will review your requested dates and notify you via SMS.
                        @if (!empty($parkContact))
                            Inquiries: <strong class="text-slate-800">{{ $parkContact }}</strong>
                        @endif
                    </p>
                    <div class="mt-6">
                        <a href="{{ url('/') }}" class="inline-flex items-center rounded-lg bg-[#123322] px-5 py-2.5 text-xs font-semibold text-white no-underline transition-colors hover:bg-[#1a4730] sm:text-sm">
                            Back to Homepage
                        </a>
                    </div>
                </div>
            @endif

        @elseif ($state === 'expired')
            <!-- Expired State -->
            <div class="mx-auto max-w-xl rounded-xl border border-slate-200 bg-white p-6 shadow-xs sm:p-8">
                <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Invitation Expired</span>
                <h2 class="m-0 mt-1 font-display text-2xl font-bold text-slate-900">Link Has Expired</h2>
                <p class="mt-2 mb-0 text-sm leading-relaxed text-slate-600">
                    For security, rescheduling and refund links expire after 24 hours. Please contact Hinaguan Nature Park staff to request a new link.
                </p>
                <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs text-slate-600">
                    Expired on: <strong class="text-slate-900">{{ $rescheduleRequest->expires_at->format('M d, Y · g:i A') }}</strong>
                    @if (!empty($parkContact))
                        &middot; Contact: <strong class="text-slate-900">{{ $parkContact }}</strong>
                    @endif
                </div>
                <div class="mt-6">
                    <a href="{{ url('/') }}" class="inline-flex items-center rounded-lg bg-[#123322] px-5 py-2.5 text-xs font-semibold text-white no-underline transition-colors hover:bg-[#1a4730] sm:text-sm">
                        Back to Homepage
                    </a>
                </div>
            </div>

        @elseif ($state === 'submitted_refund_success')
            <!-- Refund Submission Success Confirmation -->
            <div class="mx-auto max-w-xl rounded-xl border border-slate-200 bg-white p-6 shadow-xs sm:p-8">
                <div class="border-b border-slate-100 pb-4">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800">Refund Request Recorded</span>
                    <h2 class="m-0 mt-1 font-display text-2xl font-bold text-slate-900">Refund Request Submitted</h2>
                    <p class="mt-1.5 mb-0 text-sm leading-relaxed text-slate-600">
                        Thank you, <strong class="text-slate-900">{{ $reservation?->booker_name ?: ($rescheduleRequest->ewallet_account_name ?: 'Guest') }}</strong>. Your request for a 50% downpayment refund has been submitted to Hinaguan Nature Park staff.
                    </p>
                </div>

                <dl class="m-0 mt-5 divide-y divide-slate-200 rounded-lg border border-slate-200 bg-slate-50/70 px-4 text-xs sm:text-sm">
                    <div class="flex items-center justify-between py-3">
                        <dt class="text-slate-500">Reservation ID</dt>
                        <dd class="m-0 font-mono font-bold text-slate-900">#{{ $reservation?->formatted_id ?? sprintf('%03d', $rescheduleRequest->reservation_id) }}</dd>
                    </div>
                    <div class="flex items-center justify-between py-3">
                        <dt class="text-slate-500">Total Downpayment Paid</dt>
                        <dd class="m-0 font-semibold tabular-nums text-slate-900">₱{{ number_format($origPaid, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between py-3">
                        <dt class="font-bold text-slate-900">50% Refund to Receive</dt>
                        <dd class="m-0 text-base font-bold tabular-nums text-emerald-800">₱{{ number_format($rescheduleRequest->refund_amount ?? $refundAmount, 2) }}</dd>
                    </div>
                    <div class="flex items-center justify-between py-3">
                        <dt class="text-slate-500">Receiving E-Wallet</dt>
                        <dd class="m-0 font-semibold text-slate-900">{{ $rescheduleRequest->ewallet_provider ?? 'GCash' }} &middot; {{ $rescheduleRequest->ewallet_account_number }}</dd>
                    </div>
                    <div class="flex items-center justify-between py-3">
                        <dt class="text-slate-500">Account Name</dt>
                        <dd class="m-0 font-semibold text-slate-900">{{ $rescheduleRequest->ewallet_account_name ?: ($reservation?->booker_name ?: 'Guest') }}</dd>
                    </div>
                </dl>

                <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4 text-xs leading-relaxed text-slate-600">
                    <strong class="block text-slate-900">Refund Request Under Review</strong>
                    Please allow up to 24 hours for management review. Once approved, your refund will be sent to your e-wallet and confirmed via SMS. For questions, call <strong class="text-slate-900">{{ $parkContact ?: '09938373838' }}</strong>.
                </div>

                <div class="mt-6">
                    <a href="{{ url('/') }}" class="inline-flex items-center rounded-lg bg-[#123322] px-5 py-2.5 text-xs font-semibold text-white no-underline transition-colors hover:bg-[#1a4730] sm:text-sm">
                        Back to Homepage
                    </a>
                </div>
            </div>

        @elseif ($state === 'submitted_success')
            <!-- Submission Success Confirmation -->
            <div class="mx-auto max-w-xl rounded-xl border border-slate-200 bg-white p-6 shadow-xs sm:p-8">
                <div class="border-b border-slate-100 pb-4">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800">Reschedule Request Recorded</span>
                    <h2 class="m-0 mt-1 font-display text-2xl font-bold text-slate-900">Request Submitted</h2>
                    <p class="mt-1.5 mb-0 text-sm leading-relaxed text-slate-600">
                        Thank you, <strong class="text-slate-900">{{ $reservation->booker_name }}</strong>. We have received your requested new dates.
                    </p>
                </div>

                <div class="mt-5 space-y-4 rounded-lg border border-slate-200 bg-slate-50/70 p-4 text-xs">
                    <div class="grid grid-cols-2 gap-3 border-b border-slate-200 pb-3">
                        <div>
                            <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Reservation ID</span>
                            <span class="font-mono text-sm font-bold text-slate-900">#{{ $reservation->formatted_id }}</span>
                        </div>
                        <div>
                            <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Stay Duration</span>
                            <span class="font-semibold text-slate-900">{{ $stayDays }} {{ \Illuminate\Support\Str::plural('Day', $stayDays) }} Stay</span>
                        </div>
                    </div>

                    <div class="border-b border-slate-200 pb-3">
                        <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Original Schedule</span>
                        <div class="mt-0.5 font-medium text-slate-700">
                            @if ($origCheckInAt && $origCheckOutAt)
                                <span>{{ $origCheckInAt->format('M d, Y · g:i A') }} &ndash; {{ $origCheckOutAt->format('M d, Y · g:i A') }}</span>
                            @elseif ($origStart)
                                <span>{{ \Carbon\Carbon::parse($origStart)->format('M d, Y') }}</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <span class="mb-2 block text-[11px] font-bold uppercase tracking-wider text-emerald-800">Requested New Schedule</span>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="rounded-lg border border-slate-200 bg-white p-3">
                                <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Check-In</span>
                                <div class="mt-0.5 font-bold text-slate-900">{{ $reqCheckInAt?->format('F j, Y') }}</div>
                                <div class="mt-0.5 text-slate-500">{{ $reqCheckInAt?->format('g:i A') }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-3">
                                <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Check-Out</span>
                                <div class="mt-0.5 font-bold text-slate-900">{{ $reqCheckOutAt?->format('F j, Y') }}</div>
                                <div class="mt-0.5 text-slate-500">{{ $reqCheckOutAt?->format('g:i A') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4 text-xs leading-relaxed text-slate-600">
                    <strong class="block text-slate-900">What happens next?</strong>
                    Our park staff will review your requested schedule. Once approved, you will receive an SMS confirmation on <strong class="text-slate-900">{{ $displayPhone }}</strong>.
                </div>

                <div class="mt-6">
                    <a href="{{ url('/') }}" class="inline-flex items-center rounded-lg bg-[#123322] px-5 py-2.5 text-xs font-semibold text-white no-underline transition-colors hover:bg-[#1a4730] sm:text-sm">
                        Back to Homepage
                    </a>
                </div>
            </div>

        @else
            @php
                $initialMode = $errors->any() ? (old('action_type') === 'refund' ? 'refund' : 'reschedule') : null;
            @endphp

            <!-- ======================================================== -->
            <!-- STEP 1: INITIAL OPTION SELECTION SCREEN (BUTTONS FIRST)  -->
            <!-- ======================================================== -->
            <div id="optionSelectionScreen" class="{{ $initialMode ? 'hidden' : '' }} mx-auto max-w-3xl space-y-6 transition-all duration-200">
                <!-- Summary Card of Current Booking -->
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
                    <div class="flex flex-col justify-between gap-2 px-5 py-4 sm:flex-row sm:items-center">
                        <div>
                            <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Current Reservation</span>
                            <h2 class="m-0 mt-0.5 font-display text-lg font-bold text-slate-900">{{ $reservation->booker_name }}</h2>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <span class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 font-mono font-bold text-slate-800">#{{ $reservation->formatted_id }}</span>
                            <span class="font-mono text-slate-500">{{ $displayPhone }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 divide-y divide-slate-200 border-t border-slate-200 bg-slate-50/70 text-xs sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                        <div class="px-5 py-3.5">
                            <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Scheduled Date</span>
                            <span class="mt-1 block text-sm font-bold text-slate-900">
                                @if ($stayDays > 1)
                                    {{ \Carbon\Carbon::parse($origStart)->format('M d, Y') }} &ndash; {{ \Carbon\Carbon::parse($origEnd)->format('M d, Y') }}
                                @else
                                    {{ \Carbon\Carbon::parse($origStart)->format('M d, Y') }}
                                @endif
                            </span>
                            <span class="text-[11px] text-slate-500">{{ $stayDays }} {{ \Illuminate\Support\Str::plural('Day', $stayDays) }} Continuous Stay</span>
                        </div>
                        <div class="px-5 py-3.5">
                            <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Booking</span>
                            <span class="mt-1 block text-sm font-bold tabular-nums text-slate-900">₱{{ number_format($reservation->total_amount, 2) }}</span>
                            <span class="text-[11px] text-slate-500">{{ $reservation->number_of_guests }} registered guests</span>
                        </div>
                        <div class="px-5 py-3.5">
                            <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Downpayment Paid</span>
                            <span class="mt-1 block text-sm font-bold tabular-nums text-emerald-800">₱{{ number_format($origPaid, 2) }}</span>
                            <span class="text-[11px] text-slate-500">50% refund value: <strong class="text-slate-700">₱{{ number_format($refundAmount, 2) }}</strong></span>
                        </div>
                    </div>
                </div>

                <!-- Step 1 Heading -->
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800">Step 1 of 2</span>
                    <h2 class="m-0 mt-1 font-display text-xl font-bold text-slate-900 sm:text-2xl">How would you like to proceed?</h2>
                    <p class="m-0 mt-1 text-xs text-slate-600 sm:text-sm">
                        Choose whether to move your reservation to new dates or cancel for a 50% downpayment refund.
                    </p>
                </div>

                <!-- Two Option Cards -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <!-- Option 1: Reschedule Dates -->
                    <div id="cardSelectResched" class="flex cursor-pointer flex-col justify-between rounded-xl border border-slate-200 bg-white p-5 shadow-xs transition-colors hover:border-emerald-700 sm:p-6">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800">Option 1</span>
                                <span class="text-[11px] font-semibold text-emerald-700">100% deposit kept</span>
                            </div>
                            <h3 class="m-0 mt-1.5 font-display text-lg font-bold text-slate-900">Reschedule Dates</h3>
                            <p class="m-0 mt-1.5 text-xs leading-relaxed text-slate-600">
                                Pick new available dates from our calendar and keep your full deposit value with no penalty.
                            </p>

                            <ul class="mt-4 mb-5 space-y-1.5 border-t border-slate-100 pt-3.5 text-xs text-slate-700">
                                <li>&bull; Keep full deposit (<strong>₱{{ number_format($origPaid, 2) }}</strong>)</li>
                                <li>&bull; Live availability calendar selection</li>
                                <li>&bull; Same-category unit swap if occupied</li>
                            </ul>
                        </div>

                        <button type="button" id="btnSelectResched" class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg bg-[#123322] px-4 py-2.5 text-xs font-semibold text-white transition-colors hover:bg-[#1a4730] sm:text-sm">
                            <span>Choose New Dates</span>
                            <span aria-hidden="true">&rarr;</span>
                        </button>
                    </div>

                    <!-- Option 2: 50% Refund -->
                    <div id="cardSelectRefund" class="flex cursor-pointer flex-col justify-between rounded-xl border border-slate-200 bg-white p-5 shadow-xs transition-colors hover:border-amber-600 sm:p-6">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Option 2</span>
                                <span class="text-[11px] font-semibold text-slate-500">GCash / Maya</span>
                            </div>
                            <h3 class="m-0 mt-1.5 font-display text-lg font-bold text-slate-900">Cancel &amp; 50% Refund</h3>
                            <p class="m-0 mt-1.5 text-xs leading-relaxed text-slate-600">
                                Cancel your reservation and request 50% of your downpayment sent to your GCash or Maya account.
                            </p>

                            <ul class="mt-4 mb-5 space-y-1.5 border-t border-slate-100 pt-3.5 text-xs text-slate-700">
                                <li>&bull; Receive <strong>₱{{ number_format($refundAmount, 2) }}</strong> (50% downpayment)</li>
                                <li>&bull; Sent directly to your e-wallet</li>
                                <li>&bull; SMS confirmation with reference #</li>
                            </ul>
                        </div>

                        <button type="button" id="btnSelectRefund" class="inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-800 transition-colors hover:bg-slate-50 sm:text-sm">
                            <span>Request 50% Refund</span>
                            <span aria-hidden="true">&rarr;</span>
                        </button>
                    </div>
                </div>

                <!-- Footer notice -->
                <p class="m-0 text-center text-xs text-slate-500">
                    This link expires in <strong>{{ $rescheduleRequest->expires_at->diffForHumans(['parts' => 2]) }}</strong> (valid for 24 hours).
                </p>
            </div>

            <!-- ======================================================== -->
            <!-- STEP 2: PROCESS SCREEN (ENTERED ONLY AFTER CLICKING AN OPTION) -->
            <!-- ======================================================== -->
            <div id="processScreen" class="{{ $initialMode ? '' : 'hidden' }} transition-all duration-300">
                <!-- Navigation & Option Switcher Top Bar -->
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-xs">
                    <div class="flex items-center gap-3">
                        <button type="button" id="btnBackToOptions" class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50">
                            <span aria-hidden="true">&larr;</span>
                            <span>Back to Options</span>
                        </button>
                        <span id="processStepTitle" class="hidden text-xs font-medium text-slate-500 sm:inline">
                            Step 2: <strong class="font-bold text-slate-900">Choose New Dates (Option 1)</strong>
                        </span>
                    </div>

                    <!-- Option Switcher -->
                    <div class="flex items-center gap-2">
                        <span class="hidden text-[11px] font-semibold uppercase tracking-wider text-slate-400 md:inline">Switch:</span>
                        <button type="button" id="tabOptionResched" class="guest-action-tab inline-flex shrink-0 cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-lg border-2 border-emerald-700 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-900 transition-colors">
                            <span>Reschedule</span>
                        </button>
                        <button type="button" id="tabOptionRefund" class="guest-action-tab inline-flex shrink-0 cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition-colors hover:border-amber-500">
                            <span>50% Refund</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
                
                <!-- Left: Reservation Details & Notice -->
                <div class="flex flex-col gap-5 lg:col-span-5">
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                        <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3.5">
                            <div>
                                <span class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500">Reservation Details</span>
                                <h2 class="m-0 mt-0.5 font-display text-lg font-bold text-slate-900">{{ $reservation->booker_name }}</h2>
                            </div>
                            <span class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 font-mono text-xs font-bold text-slate-800">
                                #{{ $reservation->formatted_id }}
                            </span>
                        </div>

                        <!-- Current Scheduled Info -->
                        <dl class="m-0 mb-4 divide-y divide-slate-100 text-xs">
                            <div class="flex items-center justify-between gap-2 py-2.5">
                                <dt class="text-slate-500">Original Dates</dt>
                                <dd class="m-0 text-right font-semibold text-slate-900">
                                    @if ($stayDays > 1)
                                        {{ \Carbon\Carbon::parse($origStart)->format('M d, Y') }} &ndash; {{ \Carbon\Carbon::parse($origEnd)->format('M d, Y') }}
                                        <span class="block text-[11px] font-normal text-slate-500">({{ $stayDays }} Days Stay)</span>
                                    @else
                                        {{ \Carbon\Carbon::parse($origStart)->format('F j, Y') }}
                                    @endif
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-2 py-2.5">
                                <dt class="text-slate-500">Stay Duration</dt>
                                <dd class="m-0 font-semibold text-slate-900">{{ $stayDays }} {{ \Illuminate\Support\Str::plural('Day', $stayDays) }} Continuous Stay</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2 py-2.5">
                                <dt class="text-slate-500">Session</dt>
                                <dd class="m-0 font-semibold text-slate-900">Fixed (same as original)</dd>
                            </div>
                            <div class="flex items-center justify-between gap-2 py-2.5">
                                <dt class="text-slate-500">Guests</dt>
                                <dd class="m-0 font-semibold text-slate-900">{{ $reservation->number_of_guests }} guests</dd>
                            </div>
                        </dl>

                        <!-- Financial Summary Breakdown -->
                        <div class="mb-4 space-y-1.5 rounded-lg border border-slate-200 bg-slate-50/70 p-3.5 text-xs">
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Total Booking</span>
                                <span class="font-semibold tabular-nums text-slate-900">₱{{ number_format($reservation->total_amount, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Downpayment Paid</span>
                                <span class="font-semibold tabular-nums text-slate-900">₱{{ number_format($origPaid, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-200 pt-2">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-700">50% Refund Value</span>
                                <span class="font-bold tabular-nums text-emerald-800">₱{{ number_format($refundAmount, 2) }}</span>
                            </div>
                        </div>

                        @if ($stayDays > 1)
                            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50/60 p-3 text-xs leading-relaxed text-emerald-900">
                                <strong>{{ $stayDays }}-Day Stay Adaptation:</strong>
                                Picking a check-in date automatically schedules all <strong>{{ $stayDays }} continuous days</strong> through check-out.
                            </div>
                        @endif

                        <!-- Amenities Availed -->
                        @if ($reservation->reservationAmenities->isNotEmpty())
                            <div class="mb-4">
                                <div class="mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Reserved Amenities</div>
                                <div class="divide-y divide-slate-100 rounded-lg border border-slate-200 text-xs">
                                    @foreach ($reservation->reservationAmenities as $ra)
                                        <div class="flex items-center justify-between px-3 py-2">
                                            <span class="font-semibold text-slate-800">{{ $ra->amenity?->amenities_name ?? 'Amenity' }}</span>
                                            <span class="text-slate-500">Qty: {{ $ra->quantity }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Expiration Notice -->
                        <p class="m-0 text-[11px] text-slate-500">
                            Link expires in <strong>{{ $rescheduleRequest->expires_at->diffForHumans(['parts' => 2]) }}</strong> (valid 24h).
                        </p>
                    </div>
                </div>

                <!-- Right: Switchable Panels (Option 1 Calendar VS Option 2 50% Refund) -->
                <div class="lg:col-span-7">
                    <!-- Option 1: Calendar Container -->
                    <div id="calendarContainer" class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs sm:p-6">
                        <div class="mb-4 border-b border-slate-100 pb-3.5">
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-emerald-800">Option 1 of 2</span>
                            <h3 class="m-0 mt-0.5 font-display text-lg font-bold text-slate-900 sm:text-xl">
                                @if ($stayDays > 1)
                                    Choose Check-In Date ({{ $stayDays }}-Day Stay)
                                @else
                                    Choose Your Preferred Date
                                @endif
                            </h3>
                            <p class="m-0 mt-1 text-xs leading-relaxed text-slate-500">
                                @if ($stayDays > 1)
                                    Select your preferred check-in date. The calendar will automatically reserve the entire <strong>{{ $stayDays }}-day window</strong>.
                                @else
                                    Only dates with full availability for your reserved amenities are selectable.
                                @endif
                            </p>
                        </div>

                        <!-- Calendar Header (Month / Year Navigation) -->
                        <div class="mb-3 flex items-center justify-between border-b border-slate-100 pb-3">
                            <button type="button" id="prevMonthBtn" class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition-colors hover:bg-slate-50">
                                <i class="bi bi-chevron-left text-xs font-bold"></i>
                            </button>
                            <span id="calendarMonthLabel" class="font-display text-sm font-bold text-slate-900 sm:text-base">Loading...</span>
                            <button type="button" id="nextMonthBtn" class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 transition-colors hover:bg-slate-50">
                                <i class="bi bi-chevron-right text-xs font-bold"></i>
                            </button>
                        </div>

                        <!-- Weekday labels -->
                        <div class="mb-1.5 grid grid-cols-7 gap-1 text-center">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Sun</span>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Mon</span>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Tue</span>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Wed</span>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Thu</span>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Fri</span>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Sat</span>
                        </div>

                        <!-- Calendar Days Grid -->
                        <div id="calendarGrid" class="relative grid min-h-[240px] grid-cols-7 gap-1">
                            <div class="col-span-7 flex items-center justify-center py-14 text-xs text-slate-400">
                                Loading availability calendar...
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-3 w-3 rounded border border-slate-300 bg-white"></span> Available
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="flex h-3 w-3 items-center justify-center rounded border border-amber-300 bg-amber-50">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                </span> Unit Swap Available
                            </span>
                            @if ($stayDays > 1)
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="h-3 w-3 rounded bg-emerald-900"></span> Check-in / Out
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="h-3 w-3 rounded border border-emerald-300 bg-emerald-200"></span> Stay Days
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="h-3 w-3 rounded bg-emerald-900"></span> Selected
                                </span>
                            @endif
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-3 w-3 rounded bg-slate-200 line-through"></span> Unavailable
                            </span>
                        </div>

                        <!-- Selection Summary & Submit Form -->
                        <form id="rescheduleForm" action="{{ url('/reservation/reschedule/' . $rescheduleRequest->token) }}" method="POST" class="mt-5 border-t border-slate-100 pt-5">
                            @csrf
                            <input type="hidden" name="requested_date" id="requestedDateInput" value="">
                            <input type="hidden" name="replacement_amenity_id" id="replacementAmenityIdInput" value="">

                            <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                                <div class="flex flex-col justify-between gap-2 border-b border-slate-200 pb-3 sm:flex-row sm:items-center">
                                    <div>
                                        <span class="block text-[11px] font-bold uppercase tracking-wider text-emerald-800">
                                            @if ($stayDays > 1)
                                                Selected {{ $stayDays }}-Day Schedule
                                            @else
                                                Selected Preferred Date
                                            @endif
                                        </span>
                                        <div id="selectedDateDisplay" class="mt-0.5 text-sm font-bold text-slate-900">
                                            Please tap an available check-in date on the calendar above
                                        </div>
                                    </div>
                                    <span id="dateBadge" class="hidden shrink-0 rounded-md bg-emerald-800 px-2.5 py-1 text-xs font-bold text-white">
                                        @if ($stayDays > 1) {{ $stayDays }} Days Picked @else Date Picked @endif
                                    </span>
                                </div>

                                <div class="mt-2.5 grid grid-cols-1 gap-1.5 text-xs text-slate-600 sm:grid-cols-2">
                                    <span>Session: <strong class="text-slate-900">Fixed (same as original)</strong></span>
                                    <span>Amenities: <strong class="text-slate-900">100% Available for full stay</strong></span>
                                </div>
                            </div>

                            <button type="submit" id="submitRescheduleBtn" disabled class="flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-lg bg-[#123322] px-5 py-2.5 text-xs font-semibold text-white opacity-50 transition-colors hover:bg-[#1a4730] sm:text-sm">
                                <span>
                                    @if ($stayDays > 1)
                                        Submit {{ $stayDays }}-Day Reschedule Request
                                    @else
                                        Submit Preferred Date
                                    @endif
                                </span>
                            </button>
                        </form>
                    </div>

                    <!-- Option 2: 50% Refund Request Container -->
                    <div id="refundContainer" class="hidden rounded-xl border border-slate-200 bg-white p-5 shadow-xs sm:p-6">
                        <div class="mb-4 border-b border-slate-100 pb-3.5">
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-amber-700">Option 2 of 2</span>
                            <h3 class="m-0 mt-0.5 font-display text-lg font-bold text-slate-900 sm:text-xl">
                                Request 50% Downpayment Refund
                            </h3>
                            <p class="m-0 mt-1 text-xs leading-relaxed text-slate-500">
                                Complete this form to cancel your reservation and receive 50% of your downpayment via GCash or Maya.
                            </p>
                        </div>

                        <!-- Financial Breakdown Card -->
                        <div class="mb-5 divide-y divide-slate-200 rounded-lg border border-slate-200 bg-slate-50/70 px-4 text-xs">
                            <div class="flex items-center justify-between py-2.5">
                                <span class="text-slate-600">Total Reservation Cost</span>
                                <span class="font-semibold tabular-nums text-slate-900">₱{{ number_format($reservation->total_amount, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <span class="text-slate-600">Downpayment Paid</span>
                                <span class="font-semibold tabular-nums text-slate-900">₱{{ number_format($origPaid, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <span class="block font-bold text-slate-900">Eligible 50% Refund</span>
                                    <span class="text-[11px] text-slate-500">50% of downpayment</span>
                                </div>
                                <span class="text-lg font-bold tabular-nums text-emerald-800">
                                    ₱{{ number_format($refundAmount, 2) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between py-2 text-[11px] text-slate-500">
                                <span>Cancellation Policy Fee (50% Retained)</span>
                                <span class="font-semibold tabular-nums text-slate-700">₱{{ number_format($retainedAmount, 2) }}</span>
                            </div>
                        </div>

                        <!-- Refund Form -->
                        <form id="refundForm" action="{{ url('/reservation/reschedule/' . $rescheduleRequest->token . '/refund') }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="action_type" value="refund">

                            <!-- E-Wallet Provider Selection -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-slate-700">E-Wallet Provider</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border-2 border-emerald-700 bg-emerald-50/50 px-3.5 py-2.5 transition-colors" id="walletLabelGcash">
                                        <input type="radio" name="ewallet_provider" value="GCash" checked class="text-emerald-700 focus:ring-emerald-600">
                                        <span class="text-xs font-bold text-slate-900">GCash</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 transition-colors hover:bg-slate-50" id="walletLabelMaya">
                                        <input type="radio" name="ewallet_provider" value="Maya" class="text-emerald-700 focus:ring-emerald-600">
                                        <span class="text-xs font-bold text-slate-900">Maya</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Mobile / Account Number -->
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <label for="refundAccountNo" class="block text-xs font-semibold text-slate-700">
                                        E-Wallet Mobile Number <span class="text-red-500">*</span>
                                    </label>
                                    <span class="text-[11px] text-slate-400">Starts with 09</span>
                                </div>
                                <input type="text" name="ewallet_account_number" id="refundAccountNo" maxlength="13" required value="{{ $reservation->phone }}"
                                    class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3.5 font-mono text-sm font-semibold text-slate-900 focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/15"
                                    placeholder="09XXXXXXXXX">
                            </div>

                            <!-- Account Holder Name -->
                            <div>
                                <label for="refundAccountName" class="mb-1 block text-xs font-semibold text-slate-700">
                                    Account Holder Full Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="ewallet_account_name" id="refundAccountName" required value="{{ $reservation->booker_name }}"
                                    class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm font-medium text-slate-900 focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/15"
                                    placeholder="e.g. Juan Dela Cruz">
                            </div>

                            <!-- Reason / Feedback (optional) -->
                            <div>
                                <label for="refundReason" class="mb-1 block text-xs font-semibold text-slate-700">
                                    Reason for Cancellation <span class="font-normal text-slate-400">(Optional)</span>
                                </label>
                                <textarea name="refund_reason" id="refundReason" rows="2"
                                    class="w-full rounded-lg border border-slate-300 bg-white p-3 text-xs text-slate-900 focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/15"
                                    placeholder="e.g. Schedule conflict, unable to reschedule new dates"></textarea>
                            </div>

                            <!-- Agreement checkbox -->
                            <label class="flex cursor-pointer items-start gap-2.5 text-xs leading-relaxed text-slate-600">
                                <input type="checkbox" id="refundConfirmCheck" required class="mt-0.5 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                                <span>
                                    I understand that submitting this request will <strong>cancel Reservation #{{ $reservation->formatted_id }}</strong> and that <strong>₱{{ number_format($refundAmount, 2) }}</strong> (50% of my downpayment) will be transferred to my e-wallet account by park staff.
                                </span>
                            </label>

                            <button type="submit" id="submitRefundBtn" class="flex w-full cursor-pointer items-center justify-center rounded-lg bg-amber-700 px-5 py-2.5 text-xs font-semibold text-white transition-colors hover:bg-amber-800 sm:text-sm">
                                Submit 50% Refund Request (₱{{ number_format($refundAmount, 2) }})
                            </button>
                        </form>
                    </div><!-- /#refundContainer -->
                </div><!-- /.lg:col-span-7 -->
            </div><!-- /.grid -->
        </div><!-- /#processScreen -->

            <!-- Amenity Swap Notice & Selection Modal -->
            <div id="amenitySwapModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-xs p-3 sm:p-4">
                <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-7 max-w-lg w-full shadow-2xl border border-slate-100 text-left">
                    <!-- Header -->
                    <div class="flex items-start gap-2.5 sm:gap-3.5 pb-3 sm:pb-4 border-b border-slate-100">
                        <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg sm:text-xl shrink-0">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <h4 class="font-display font-bold text-sm sm:text-lg text-slate-900 leading-tight">Amenity Unavailable on This Date</h4>
                                <span class="inline-flex items-center px-1.5 sm:px-2 py-0.5 rounded-full text-[9px] sm:text-[10px] font-bold bg-amber-100 text-amber-800 shrink-0">Swap Available</span>
                            </div>
                            <p class="text-[11px] sm:text-xs text-slate-500 mt-1 leading-relaxed">
                                Your reserved unit, <strong id="swapCurrentAmenityName" class="text-slate-800"></strong>, is already booked for this schedule.
                            </p>
                        </div>
                        <button type="button" id="closeSwapModalBtn" class="w-7 h-7 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 text-base sm:text-lg transition-all cursor-pointer shrink-0">
                            &times;
                        </button>
                    </div>

                    <!-- Explanation Notice (better phrased) -->
                    <div class="mt-3 sm:mt-4 p-2.5 sm:p-3.5 rounded-xl sm:rounded-2xl bg-amber-50/80 border border-amber-200/70 text-amber-900 text-[11px] sm:text-xs leading-relaxed space-y-1">
                        <p class="font-bold flex items-center gap-1.5 text-amber-950">
                            <i class="bi bi-info-circle-fill text-amber-600"></i>
                            You can still reschedule to this date!
                        </p>
                        <p class="text-[10px] sm:text-[11px] text-amber-800/95 leading-normal">
                            Hinaguan Nature Park allows you to switch to another available unit in the same category (<strong id="swapCategoryName"></strong>) with the <strong>exact same rate</strong>. No extra charges or fee changes will apply.
                        </p>
                    </div>

                    <!-- Options Container -->
                    <div class="mt-3 sm:mt-4">
                        <div class="flex items-center justify-between mb-1.5 sm:mb-2">
                            <span class="text-[0.65rem] sm:text-[0.7rem] font-bold uppercase tracking-wider text-slate-500">Available Replacement Units</span>
                            <span id="swapOptionsCount" class="text-[0.65rem] sm:text-[0.7rem] font-semibold text-emerald-700"></span>
                        </div>
                        <div id="swapOptionsContainer" class="space-y-1.5 sm:space-y-2 max-h-56 overflow-y-auto pr-1">
                            <!-- Dynamically injected replacement cards -->
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 sm:mt-6 pt-3 sm:pt-4 border-t border-slate-100 flex items-center justify-end gap-2 sm:gap-2.5">
                        <button type="button" id="cancelSwapBtn" class="px-3 sm:px-4 py-2 sm:py-2.5 rounded-lg sm:rounded-xl border border-slate-200 text-slate-700 font-semibold text-[11px] sm:text-xs hover:bg-slate-50 transition-all cursor-pointer">
                            Pick Another Date
                        </button>
                        <button type="button" id="confirmSwapBtn" disabled class="px-3.5 sm:px-5 py-2 sm:py-2.5 rounded-lg sm:rounded-xl bg-emerald-800 hover:bg-emerald-900 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-[11px] sm:text-xs transition-all shadow-sm flex items-center gap-1.5 cursor-pointer">
                            <i class="bi bi-check2"></i>
                            <span>Continue with Selected Unit</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Confirmation Modal -->
            <div id="confirmSubmitModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-xs p-3 sm:p-4">
                <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-8 max-w-md w-full shadow-2xl border border-slate-100 text-center">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center mx-auto mb-3 sm:mb-4 text-xl sm:text-2xl">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <h4 class="font-display font-bold text-lg sm:text-xl text-slate-900 mb-1.5 sm:mb-2">
                        @if ($stayDays > 1)
                            Confirm {{ $stayDays }}-Day Reschedule
                        @else
                            Confirm Date Submission
                        @endif
                    </h4>
                    <p class="text-[11px] sm:text-xs text-slate-600 mb-3 sm:mb-4 leading-relaxed">
                        Are you sure you want to submit this requested schedule for your reservation?
                    </p>

                    <div class="bg-emerald-50/70 border border-emerald-200/80 rounded-xl sm:rounded-2xl p-3 sm:p-4 text-left text-xs mb-3 sm:mb-4 space-y-1.5 sm:space-y-2">
                        @if ($stayDays > 1)
                            <div class="flex justify-between">
                                <span class="text-slate-500 font-medium">Check-In Date:</span>
                                <strong id="modalCheckInText" class="text-emerald-950 font-bold"></strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500 font-medium">Check-Out Date:</span>
                                <strong id="modalCheckOutText" class="text-emerald-950 font-bold"></strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500 font-medium">Duration:</span>
                                <strong class="text-emerald-900 font-bold">{{ $stayDays }} Days Continuous Stay</strong>
                            </div>
                        @else
                            <div class="flex justify-between">
                                <span class="text-slate-500 font-medium">Date:</span>
                                <strong id="modalDateConfirmText" class="text-emerald-950 font-bold"></strong>
                            </div>
                        @endif
                        <div id="modalSwapRow" class="hidden justify-between pt-1 border-t border-emerald-200/60">
                            <span class="text-slate-500 font-medium">Assigned Unit:</span>
                            <span class="font-bold text-amber-900"><span id="modalSwapAmenityText"></span> <span class="text-[10px] text-amber-700 font-normal">(Swapped · Same Rate)</span></span>
                        </div>
                        <div class="flex justify-between pt-1 border-t border-emerald-200/60">
                            <span class="text-slate-500 font-medium">Session:</span>
                            <span class="font-bold text-emerald-900">Fixed (same as original)</span>
                        </div>
                    </div>

                    <p class="text-[10px] sm:text-[0.72rem] text-slate-400 mb-4 sm:mb-6 italic">
                        Note: This single-use link will be submitted and cannot be reopened. Staff will be notified immediately.
                    </p>
                    <div class="flex items-center justify-center gap-2 sm:gap-3">
                        <button type="button" id="modalCancelBtn" class="px-3.5 sm:px-5 py-2 sm:py-2.5 rounded-lg sm:rounded-xl border border-slate-200 text-slate-700 font-semibold text-[11px] sm:text-xs hover:bg-slate-50 transition-all cursor-pointer">
                            Choose Another
                        </button>
                        <button type="button" id="modalConfirmBtn" class="px-4 sm:px-6 py-2 sm:py-2.5 rounded-lg sm:rounded-xl bg-emerald-800 hover:bg-emerald-900 text-white font-bold text-[11px] sm:text-xs transition-all shadow-sm cursor-pointer">
                            Yes, Submit Reschedule
                        </button>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const token = @json($rescheduleRequest->token);
                    const availabilityApiUrl = @json(url('/reservation/reschedule/' . $rescheduleRequest->token . '/availability'));
                    const stayDays = {{ $stayDays }};
                    const origStartDateStr = @json($origStart);
                    const origEndDateStr = @json($origEnd);
                    const checkInTimeStr = @json($checkInTimeStr);
                    const checkOutTimeStr = @json($checkOutTimeStr);

                    let currentYear = new Date().getFullYear();
                    let currentMonth = new Date().getMonth(); // 0-indexed
                    let selectedStartDateStr = null;
                    let selectedEndDateStr = null;
                    let availabilityMap = {}; // 'YYYY-MM-DD': boolean
                    let dateDetailsMap = {}; // 'YYYY-MM-DD': details object
                    let selectedReplacementAmenity = null;

                    let pendingSwapDateStr = null;
                    let pendingDayDetail = null;
                    let pendingSelectedReplacementId = null;

                    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                    const calendarMonthLabel = document.getElementById('calendarMonthLabel');
                    const calendarGrid = document.getElementById('calendarGrid');
                    const prevBtn = document.getElementById('prevMonthBtn');
                    const nextBtn = document.getElementById('nextMonthBtn');
                    const requestedDateInput = document.getElementById('requestedDateInput');
                    const replacementAmenityIdInput = document.getElementById('replacementAmenityIdInput');
                    const selectedDateDisplay = document.getElementById('selectedDateDisplay');
                    const dateBadge = document.getElementById('dateBadge');
                    const submitBtn = document.getElementById('submitRescheduleBtn');
                    const form = document.getElementById('rescheduleForm');
                    const confirmModal = document.getElementById('confirmSubmitModal');
                    const modalCheckInText = document.getElementById('modalCheckInText');
                    const modalCheckOutText = document.getElementById('modalCheckOutText');
                    const modalDateConfirmText = document.getElementById('modalDateConfirmText');
                    const modalSwapRow = document.getElementById('modalSwapRow');
                    const modalSwapAmenityText = document.getElementById('modalSwapAmenityText');
                    const modalCancelBtn = document.getElementById('modalCancelBtn');
                    const modalConfirmBtn = document.getElementById('modalConfirmBtn');

                    // Swap Modal elements
                    const amenitySwapModal = document.getElementById('amenitySwapModal');
                    const swapCurrentAmenityName = document.getElementById('swapCurrentAmenityName');
                    const swapCategoryName = document.getElementById('swapCategoryName');
                    const swapOptionsCount = document.getElementById('swapOptionsCount');
                    const swapOptionsContainer = document.getElementById('swapOptionsContainer');
                    const closeSwapModalBtn = document.getElementById('closeSwapModalBtn');
                    const cancelSwapBtn = document.getElementById('cancelSwapBtn');
                    const confirmSwapBtn = document.getElementById('confirmSwapBtn');

                    // Step 1 Selection Screen vs Step 2 Process Screen
                    const optionSelectionScreen = document.getElementById('optionSelectionScreen');
                    const processScreen = document.getElementById('processScreen');
                    const btnSelectResched = document.getElementById('btnSelectResched');
                    const btnSelectRefund = document.getElementById('btnSelectRefund');
                    const cardSelectResched = document.getElementById('cardSelectResched');
                    const cardSelectRefund = document.getElementById('cardSelectRefund');
                    const btnBackToOptions = document.getElementById('btnBackToOptions');
                    const processStepTitle = document.getElementById('processStepTitle');

                    // Option Tabs & Containers inside Process Screen
                    const tabOptionResched = document.getElementById('tabOptionResched');
                    const tabOptionRefund = document.getElementById('tabOptionRefund');
                    const calendarContainer = document.getElementById('calendarContainer');
                    const refundContainer = document.getElementById('refundContainer');

                    const switchToReschedTab = () => {
                        if (tabOptionResched && tabOptionRefund) {
                            tabOptionResched.className = 'guest-action-tab text-left px-2.5 sm:px-3.5 py-1.5 sm:py-2 rounded-lg sm:rounded-xl border-2 border-emerald-700 bg-emerald-50 text-emerald-900 font-bold text-[11px] sm:text-xs transition-all cursor-pointer shadow-xs inline-flex items-center gap-1.5';
                            tabOptionRefund.className = 'guest-action-tab text-left px-2.5 sm:px-3.5 py-1.5 sm:py-2 rounded-lg sm:rounded-xl border border-slate-200 bg-white hover:border-amber-400 text-slate-700 font-semibold text-[11px] sm:text-xs transition-all cursor-pointer inline-flex items-center gap-1.5';
                        }
                        
                        if (calendarContainer) calendarContainer.classList.remove('hidden');
                        if (refundContainer) refundContainer.classList.add('hidden');
                        if (processStepTitle) {
                            processStepTitle.innerHTML = 'Step 2: <strong class="text-emerald-950 font-bold">Choose New Dates (Option 1)</strong>';
                        }
                    };

                    const switchToRefundTab = () => {
                        if (tabOptionResched && tabOptionRefund) {
                            tabOptionRefund.className = 'guest-action-tab text-left px-2.5 sm:px-3.5 py-1.5 sm:py-2 rounded-lg sm:rounded-xl border-2 border-amber-600 bg-amber-50 text-amber-900 font-bold text-[11px] sm:text-xs transition-all cursor-pointer shadow-xs inline-flex items-center gap-1.5';
                            tabOptionResched.className = 'guest-action-tab text-left px-2.5 sm:px-3.5 py-1.5 sm:py-2 rounded-lg sm:rounded-xl border border-slate-200 bg-white hover:border-emerald-500 text-slate-700 font-semibold text-[11px] sm:text-xs transition-all cursor-pointer inline-flex items-center gap-1.5';
                        }

                        if (calendarContainer) calendarContainer.classList.add('hidden');
                        if (refundContainer) refundContainer.classList.remove('hidden');
                        if (processStepTitle) {
                            processStepTitle.innerHTML = 'Step 2: <strong class="text-amber-950 font-bold">Enter GCash Details for 50% Refund (Option 2)</strong>';
                        }
                    };

                    const enterProcess = (option) => {
                        if (optionSelectionScreen) optionSelectionScreen.classList.add('hidden');
                        if (processScreen) processScreen.classList.remove('hidden');

                        if (option === 'refund') {
                            switchToRefundTab();
                        } else {
                            switchToReschedTab();
                        }
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    };

                    const backToOptions = () => {
                        if (processScreen) processScreen.classList.add('hidden');
                        if (optionSelectionScreen) optionSelectionScreen.classList.remove('hidden');
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    };

                    btnSelectResched?.addEventListener('click', (e) => {
                        e.stopPropagation();
                        enterProcess('reschedule');
                    });
                    cardSelectResched?.addEventListener('click', () => {
                        enterProcess('reschedule');
                    });

                    btnSelectRefund?.addEventListener('click', (e) => {
                        e.stopPropagation();
                        enterProcess('refund');
                    });
                    cardSelectRefund?.addEventListener('click', () => {
                        enterProcess('refund');
                    });

                    btnBackToOptions?.addEventListener('click', backToOptions);
                    tabOptionResched?.addEventListener('click', switchToReschedTab);
                    tabOptionRefund?.addEventListener('click', switchToRefundTab);

                    // If page loaded with validation errors or old input, directly enter that process
                    const initialMode = @json($initialMode ?? null);
                    if (initialMode === 'refund') {
                        enterProcess('refund');
                    } else if (initialMode === 'reschedule') {
                        enterProcess('reschedule');
                    }

                    // E-wallet radio toggle styling
                    const walletRadios = document.querySelectorAll('input[name="ewallet_provider"]');
                    const walletLabelGcash = document.getElementById('walletLabelGcash');
                    const walletLabelMaya = document.getElementById('walletLabelMaya');

                    const updateWalletStyles = () => {
                        const selectedVal = document.querySelector('input[name="ewallet_provider"]:checked')?.value || 'GCash';
                        if (selectedVal === 'GCash') {
                            walletLabelGcash?.classList.add('border-emerald-700', 'bg-emerald-50/50');
                            walletLabelGcash?.classList.remove('border-slate-200', 'bg-white');
                            walletLabelMaya?.classList.remove('border-emerald-700', 'bg-emerald-50/50');
                            walletLabelMaya?.classList.add('border-slate-200', 'bg-white');
                        } else {
                            walletLabelMaya?.classList.add('border-emerald-700', 'bg-emerald-50/50');
                            walletLabelMaya?.classList.remove('border-slate-200', 'bg-white');
                            walletLabelGcash?.classList.remove('border-emerald-700', 'bg-emerald-50/50');
                            walletLabelGcash?.classList.add('border-slate-200', 'bg-white');
                        }
                    };

                    walletRadios.forEach(radio => {
                        radio.addEventListener('change', updateWalletStyles);
                    });

                    // Refund form submission
                    const refundForm = document.getElementById('refundForm');
                    const submitRefundBtn = document.getElementById('submitRefundBtn');
                    refundForm?.addEventListener('submit', (e) => {
                        const confirmCheck = document.getElementById('refundConfirmCheck');
                        if (confirmCheck && !confirmCheck.checked) {
                            e.preventDefault();
                            alert('Please confirm that you understand this will cancel your reservation and disburse 50% refund.');
                            return;
                        }

                        if (submitRefundBtn) {
                            submitRefundBtn.disabled = true;
                            submitRefundBtn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin mr-1"></i> Submitting Refund Request...';
                        }
                    });

                    const escapeHtml = (val) => String(val ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');

                    // Date arithmetic helpers (local date, avoids UTC offset shifts)
                    function parseDateString(str) {
                        const [y, m, d] = str.split('-').map(Number);
                        return new Date(y, m - 1, d);
                    }

                    function formatDateString(dateObj) {
                        const y = dateObj.getFullYear();
                        const m = String(dateObj.getMonth() + 1).padStart(2, '0');
                        const d = String(dateObj.getDate()).padStart(2, '0');
                        return `${y}-${m}-${d}`;
                    }

                    function addDaysToDateStr(dateStr, days) {
                        const dt = parseDateString(dateStr);
                        dt.setDate(dt.getDate() + days);
                        return formatDateString(dt);
                    }

                    // Minimum selectable check-in date: tomorrow
                    const tomorrow = new Date();
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    tomorrow.setHours(0, 0, 0, 0);

                    // Fetch availability from backend for the specified month/year
                    async function loadAvailability(year, month) {
                        calendarMonthLabel.textContent = `${monthNames[month]} ${year}`;
                        calendarGrid.innerHTML = `
                            <div class="col-span-7 flex items-center justify-center py-16 text-slate-400 text-xs">
                                <i class="bi bi-arrow-repeat animate-spin text-lg mr-2 text-emerald-600"></i> Loading availability...
                            </div>
                        `;

                        try {
                            const res = await fetch(`${availabilityApiUrl}?year=${year}&month=${month}`);
                            const data = await res.json();
                            if (data && data.availability) {
                                availabilityMap = data.availability;
                                dateDetailsMap = data.date_details || {};
                            } else {
                                availabilityMap = {};
                                dateDetailsMap = {};
                            }
                            renderCalendar(year, month);
                        } catch (err) {
                            console.error('Failed to load availability:', err);
                            renderCalendar(year, month);
                        }
                    }

                    function renderCalendar(year, month) {
                        calendarGrid.innerHTML = '';

                        const firstDayIndex = new Date(year, month, 1).getDay();
                        const daysInMonth = new Date(year, month + 1, 0).getDate();

                        // Empty padding cells before first day of month
                        for (let i = 0; i < firstDayIndex; i++) {
                            const emptyCell = document.createElement('div');
                            emptyCell.className = 'cal-day-cell opacity-0 pointer-events-none';
                            calendarGrid.appendChild(emptyCell);
                        }

                        const todayStr = formatDateString(new Date());

                        for (let day = 1; day <= daysInMonth; day++) {
                            const dayDate = new Date(year, month, day);
                            const dateStr = formatDateString(dayDate);

                            const cell = document.createElement('div');
                            cell.className = 'cal-day-cell';
                            cell.textContent = day;
                            cell.dataset.date = dateStr;

                            if (dateStr === todayStr) {
                                cell.classList.add('today-marker');
                            }

                            // Past dates or today are disabled
                            const isPastOrToday = dayDate < tomorrow;

                            // Original scheduled dates are disabled so they cannot be picked again
                            const isOriginalDate = origStartDateStr && (
                                dateStr === origStartDateStr ||
                                (origEndDateStr && dateStr >= origStartDateStr && dateStr <= origEndDateStr)
                            );

                            const dayDetail = dateDetailsMap[dateStr] || null;
                            const isDirectAvailable = availabilityMap[dateStr] === true && (!dayDetail || !dayDetail.requires_swap) && !isPastOrToday && !isOriginalDate;
                            const isSwapAvailable = availabilityMap[dateStr] === true && (dayDetail && dayDetail.requires_swap) && !isPastOrToday && !isOriginalDate;

                            if (isPastOrToday || isOriginalDate || (!isDirectAvailable && !isSwapAvailable)) {
                                cell.classList.add('disabled');
                                if (isOriginalDate) {
                                    cell.title = 'Current/original scheduled date (cannot be re-selected)';
                                } else if (isPastOrToday) {
                                    cell.title = 'Past date';
                                } else {
                                    cell.title = stayDays > 1 
                                        ? `One or more amenities are unavailable for a ${stayDays}-day stay starting this day` 
                                        : 'Unavailable / fully booked for your amenities';
                                }
                            } else if (isSwapAvailable) {
                                cell.classList.add('available', 'swap-required');
                                cell.title = `Your amenity is booked on this date, but matching ${dayDetail?.current_amenity?.category || 'category'} units are available at the same price!`;

                                cell.addEventListener('mouseenter', () => {
                                    if (stayDays > 1) {
                                        previewRangeHover(dateStr);
                                    } else {
                                        cell.classList.add('range-hover-single');
                                    }
                                });

                                cell.addEventListener('mouseleave', () => {
                                    clearRangeHover();
                                });

                                cell.addEventListener('click', () => {
                                    openAmenitySwapModal(dateStr, dayDetail);
                                });
                            } else {
                                cell.classList.add('available');
                                cell.title = stayDays > 1 
                                    ? `Available: Start ${stayDays}-day stay on this date` 
                                    : 'Available';

                                // Hover preview for stay range
                                cell.addEventListener('mouseenter', () => {
                                    if (stayDays > 1) {
                                        previewRangeHover(dateStr);
                                    } else {
                                        cell.classList.add('range-hover-single');
                                    }
                                });

                                cell.addEventListener('mouseleave', () => {
                                    clearRangeHover();
                                });

                                cell.addEventListener('click', () => {
                                    selectedReplacementAmenity = null;
                                    if (replacementAmenityIdInput) replacementAmenityIdInput.value = '';
                                    selectDateRange(dateStr, null, null);
                                });
                            }

                            calendarGrid.appendChild(cell);
                        }

                        // Re-apply range selection styling if dates match the currently viewed month
                        applySelectedRangeStyles();
                    }

                    // Hover Preview
                    function previewRangeHover(startDateStr) {
                        clearRangeHover();
                        const endDateStr = addDaysToDateStr(startDateStr, stayDays - 1);

                        document.querySelectorAll('.cal-day-cell.available').forEach(c => {
                            const d = c.dataset.date;
                            if (!d) return;

                            if (d === startDateStr && startDateStr === endDateStr) {
                                c.classList.add('range-hover-single');
                            } else if (d === startDateStr) {
                                c.classList.add('range-hover-start');
                            } else if (d === endDateStr) {
                                c.classList.add('range-hover-end');
                            } else if (d > startDateStr && d < endDateStr) {
                                c.classList.add('range-hover-mid');
                            }
                        });
                    }

                    function clearRangeHover() {
                        document.querySelectorAll('.cal-day-cell').forEach(c => {
                            c.classList.remove('range-hover-single', 'range-hover-start', 'range-hover-mid', 'range-hover-end');
                        });
                    }

                    // Swap Modal Controller
                    function openAmenitySwapModal(dateStr, dayDetail) {
                        if (!amenitySwapModal || !dayDetail) return;
                        pendingSwapDateStr = dateStr;
                        pendingDayDetail = dayDetail;
                        pendingSelectedReplacementId = null;

                        const curr = dayDetail.current_amenity || {};
                        const reps = dayDetail.replacements || [];

                        if (swapCurrentAmenityName) swapCurrentAmenityName.textContent = curr.name || 'Your Reserved Unit';
                        if (swapCategoryName) swapCategoryName.textContent = curr.category || 'amenity';
                        if (swapOptionsCount) swapOptionsCount.textContent = `${reps.length} unit${reps.length === 1 ? '' : 's'} available`;

                        if (confirmSwapBtn) {
                            confirmSwapBtn.disabled = true;
                        }

                        if (swapOptionsContainer) {
                            swapOptionsContainer.innerHTML = reps.map(rep => {
                                const dayP = Number(rep.daytime_price || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
                                const nightP = Number(rep.nighttime_price || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
                                return `
                                    <label class="group flex items-center justify-between p-3.5 rounded-2xl border border-slate-200/90 bg-white hover:border-emerald-600 hover:bg-emerald-50/50 cursor-pointer transition-all shadow-2xs">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <input type="radio" name="reschedSwapUnit" value="${escapeHtml(rep.id)}" class="w-4 h-4 text-emerald-800 accent-emerald-800 shrink-0 cursor-pointer">
                                            <div class="min-w-0">
                                                <div class="font-bold text-slate-900 text-xs truncate group-hover:text-emerald-900">${escapeHtml(rep.name)}</div>
                                                <div class="text-[11px] text-slate-500 mt-0.5">
                                                    Daytime: ₱${dayP} &bull; Overnight: ₱${nightP}
                                                    <span class="text-emerald-700 font-semibold">(Price Matches)</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="shrink-0 ml-2">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-200">
                                                <i class="bi bi-check-circle-fill text-[0.65rem]"></i>
                                                Available
                                            </span>
                                        </div>
                                    </label>
                                `;
                            }).join('');

                            swapOptionsContainer.querySelectorAll('input[name="reschedSwapUnit"]').forEach(radio => {
                                radio.addEventListener('change', () => {
                                    pendingSelectedReplacementId = radio.value;
                                    if (confirmSwapBtn) confirmSwapBtn.disabled = false;
                                });
                            });
                        }

                        amenitySwapModal.classList.remove('hidden');
                        amenitySwapModal.classList.add('flex');
                    }

                    const closeSwapModal = () => {
                        if (amenitySwapModal) {
                            amenitySwapModal.classList.add('hidden');
                            amenitySwapModal.classList.remove('flex');
                        }
                        pendingSwapDateStr = null;
                        pendingDayDetail = null;
                        pendingSelectedReplacementId = null;
                    };

                    closeSwapModalBtn?.addEventListener('click', closeSwapModal);
                    cancelSwapBtn?.addEventListener('click', closeSwapModal);

                    confirmSwapBtn?.addEventListener('click', () => {
                        if (!pendingSwapDateStr || !pendingDayDetail || !pendingSelectedReplacementId) return;
                        const chosenRep = (pendingDayDetail.replacements || []).find(r => String(r.id) === String(pendingSelectedReplacementId));
                        if (!chosenRep) return;

                        selectedReplacementAmenity = chosenRep;
                        if (replacementAmenityIdInput) replacementAmenityIdInput.value = chosenRep.id;

                        const dateToSelect = pendingSwapDateStr;
                        const currAmenity = pendingDayDetail.current_amenity;
                        closeSwapModal();

                        selectDateRange(dateToSelect, chosenRep, currAmenity);
                    });

                    // Select Date / Range
                    function selectDateRange(startDateStr, replacementObj, originalAmenityObj) {
                        selectedStartDateStr = startDateStr;
                        selectedEndDateStr = addDaysToDateStr(startDateStr, stayDays - 1);
                        requestedDateInput.value = startDateStr;

                        const startObj = parseDateString(selectedStartDateStr);
                        const endObj = parseDateString(selectedEndDateStr);

                        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
                        const startFormatted = startObj.toLocaleDateString('en-US', options);
                        const endFormatted = endObj.toLocaleDateString('en-US', options);

                        let swapHtml = '';
                        if (replacementObj) {
                            const dayPrice = Number(replacementObj.daytime_price || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
                            swapHtml = `
                                <div class="mt-3 p-3 rounded-2xl bg-amber-50/90 border border-amber-200/90 text-amber-950 text-xs flex items-start gap-2.5">
                                    <i class="bi bi-arrow-left-right text-amber-700 text-base shrink-0 mt-0.5"></i>
                                    <div class="leading-relaxed">
                                        <div><strong>Unit Swap on This Date:</strong> Switching to <strong>${escapeHtml(replacementObj.name)}</strong></div>
                                        <div class="text-[11px] text-amber-800 mt-0.5">
                                            Originally: ${escapeHtml(originalAmenityObj?.name || 'Current unit')} &bull; Same category (${escapeHtml(replacementObj.category)}) &bull; Identical price (₱${dayPrice} / day) &bull; No extra charge
                                        </div>
                                    </div>
                                </div>
                            `;
                        }

                        if (stayDays > 1) {
                            selectedDateDisplay.innerHTML = `
                                <div class="text-emerald-950 font-bold text-sm sm:text-base">
                                    ${startFormatted} &ndash; ${endFormatted}
                                </div>
                                <div class="text-xs font-semibold text-emerald-800 mt-0.5">
                                    ${stayDays} Days Continuous Stay &bull; Session is fixed (same as original)
                                </div>
                                ${swapHtml}
                            `;
                            dateBadge.textContent = `${stayDays} Days Picked`;
                        } else {
                            selectedDateDisplay.innerHTML = `
                                <div>
                                    <span class="text-emerald-950 font-bold">${startFormatted}</span>
                                    <span class="text-xs font-semibold text-emerald-800 ml-2">&bull; Session is fixed (same as original)</span>
                                </div>
                                ${swapHtml}
                            `;
                            dateBadge.textContent = 'Date Picked';
                        }
                        dateBadge.classList.remove('hidden');

                        submitBtn.disabled = false;
                        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                        submitBtn.classList.add('cursor-pointer');

                        applySelectedRangeStyles();
                    }

                    function applySelectedRangeStyles() {
                        if (!selectedStartDateStr) return;

                        document.querySelectorAll('.cal-day-cell').forEach(c => {
                            c.classList.remove('selected', 'range-single', 'range-start', 'range-mid', 'range-end');
                            const d = c.dataset.date;
                            if (!d) return;

                            if (stayDays === 1) {
                                if (d === selectedStartDateStr) {
                                    c.classList.add('range-single');
                                }
                            } else {
                                if (d === selectedStartDateStr) {
                                    c.classList.add('range-start');
                                } else if (d === selectedEndDateStr) {
                                    c.classList.add('range-end');
                                } else if (d > selectedStartDateStr && d < selectedEndDateStr) {
                                    c.classList.add('range-mid');
                                }
                            }
                        });
                    }

                    // Navigation buttons
                    prevBtn?.addEventListener('click', () => {
                        const now = new Date();
                        if (currentYear === now.getFullYear() && currentMonth <= now.getMonth()) {
                            return;
                        }
                        currentMonth--;
                        if (currentMonth < 0) {
                            currentMonth = 11;
                            currentYear--;
                        }
                        loadAvailability(currentYear, currentMonth);
                    });

                    nextBtn?.addEventListener('click', () => {
                        currentMonth++;
                        if (currentMonth > 11) {
                            currentMonth = 0;
                            currentYear++;
                        }
                        loadAvailability(currentYear, currentMonth);
                    });

                    // Intercept form submit to show confirmation modal
                    form?.addEventListener('submit', (e) => {
                        e.preventDefault();
                        if (!requestedDateInput.value) return;

                        const startObj = parseDateString(selectedStartDateStr);
                        const endObj = parseDateString(selectedEndDateStr);
                        const longOpts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };

                        if (stayDays > 1) {
                            if (modalCheckInText) modalCheckInText.textContent = `${startObj.toLocaleDateString('en-US', longOpts)}`;
                            if (modalCheckOutText) modalCheckOutText.textContent = `${endObj.toLocaleDateString('en-US', longOpts)}`;
                        } else {
                            if (modalDateConfirmText) modalDateConfirmText.textContent = `${startObj.toLocaleDateString('en-US', longOpts)}`;
                        }

                        if (modalSwapRow && modalSwapAmenityText) {
                            if (selectedReplacementAmenity) {
                                modalSwapAmenityText.textContent = selectedReplacementAmenity.name;
                                modalSwapRow.classList.remove('hidden');
                                modalSwapRow.classList.add('flex');
                            } else {
                                modalSwapRow.classList.add('hidden');
                                modalSwapRow.classList.remove('flex');
                            }
                        }

                        confirmModal?.classList.remove('hidden');
                        confirmModal?.classList.add('flex');
                    });

                    modalCancelBtn?.addEventListener('click', () => {
                        confirmModal?.classList.add('hidden');
                        confirmModal?.classList.remove('flex');
                    });

                    modalConfirmBtn?.addEventListener('click', () => {
                        modalConfirmBtn.disabled = true;
                        modalConfirmBtn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin mr-1"></i> Submitting...';
                        form.submit();
                    });

                    // Initial load
                    loadAvailability(currentYear, currentMonth);
                });
            </script>
        @endif
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500 mt-auto">
        <p>&copy; {{ date('Y') }} Hinaguan Nature Park. All rights reserved.</p>
    </footer>
</body>
</html>
