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
    @endphp

    <!-- Header -->
    <header class="bg-white/90 backdrop-blur-md border-b border-emerald-950/10 sticky top-0 z-40">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('storage/design_images/main_logo.jpeg') }}" alt="Hinaguan Nature Park" class="w-10 h-10 rounded-full object-cover shadow-sm border border-emerald-800/20">
                <div>
                    <h1 class="font-display font-bold text-base sm:text-lg text-emerald-950 leading-tight">Hinaguan Nature Park</h1>
                    <p class="text-[0.7rem] uppercase tracking-wider text-emerald-700 font-semibold">Reservation Portal</p>
                </div>
            </div>
            <div class="text-right">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                    <i class="bi bi-shield-check text-xs text-emerald-600"></i> Secure Rescheduling
                </span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-4xl w-full mx-auto px-4 sm:px-6 py-8">
        @if ($state === 'invalid')
            <!-- Invalid Token State -->
            <div class="bg-white rounded-3xl p-8 sm:p-12 shadow-xl border border-red-100 text-center max-w-lg mx-auto">
                <div class="w-16 h-16 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <h2 class="font-display text-2xl font-bold text-slate-800 mb-2">Invalid Rescheduling Link</h2>
                <p class="text-slate-600 text-sm mb-6 leading-relaxed">
                    The link you followed is not valid or may have been typed incorrectly. If you received this link via SMS, please check the message again or contact Hinaguan Nature Park.
                </p>
                @if (!empty($parkContact))
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700">
                        <i class="bi bi-telephone-fill text-emerald-700"></i> Contact Us: {{ $parkContact }}
                    </div>
                @endif
            </div>

        @elseif ($state === 'used')
            <!-- Already Used State -->
            <div class="bg-white rounded-3xl p-8 sm:p-12 shadow-xl border border-amber-100 text-center max-w-lg mx-auto">
                <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i class="bi bi-link-45deg"></i>
                </div>
                <h2 class="font-display text-2xl font-bold text-slate-800 mb-2">Link Already Used</h2>
                <p class="text-slate-600 text-sm mb-4 leading-relaxed">
                    This single-use rescheduling link has already been submitted and cannot be reused.
                </p>
                @if ($rescheduleRequest->requested_date)
                    <div class="bg-emerald-50 rounded-2xl p-5 border border-emerald-200 mb-6 text-left">
                        <div class="text-xs uppercase font-bold text-emerald-800 tracking-wider mb-3">Your Submitted Request</div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3 text-xs">
                            <div class="bg-white/80 p-3 rounded-xl border border-emerald-100">
                                <span class="text-[0.68rem] font-bold uppercase tracking-wider text-emerald-700 block">Check-In</span>
                                <div class="font-bold text-emerald-950 text-sm mt-0.5">
                                    {{ $reqCheckInAt?->format('F j, Y') }}
                                </div>
                                <div class="text-xs font-semibold text-emerald-700 mt-0.5 flex items-center gap-1">
                                    <i class="bi bi-clock"></i> {{ $reqCheckInAt?->format('g:i A') }}
                                </div>
                            </div>

                            <div class="bg-white/80 p-3 rounded-xl border border-emerald-100">
                                <span class="text-[0.68rem] font-bold uppercase tracking-wider text-emerald-700 block">Check-Out</span>
                                <div class="font-bold text-emerald-950 text-sm mt-0.5">
                                    {{ $reqCheckOutAt?->format('F j, Y') }}
                                </div>
                                <div class="text-xs font-semibold text-emerald-700 mt-0.5 flex items-center gap-1">
                                    <i class="bi bi-clock"></i> {{ $reqCheckOutAt?->format('g:i A') }}
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-emerald-200/60 flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-medium">Stay Duration: <strong class="text-slate-800">{{ $stayDays }} {{ \Illuminate\Support\Str::plural('Day', $stayDays) }} Continuous Stay</strong></span>
                            <span class="font-bold uppercase text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md">{{ $rescheduleRequest->status }}</span>
                        </div>
                    </div>
                @endif
                <p class="text-slate-500 text-xs mb-6">
                    Our staff will review your submitted dates and notify you via SMS. If you need further assistance, please contact us.
                </p>
                @if (!empty($parkContact))
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700">
                        <i class="bi bi-telephone-fill text-emerald-700"></i> Inquiries: {{ $parkContact }}
                    </div>
                @endif
            </div>

        @elseif ($state === 'expired')
            <!-- Expired State -->
            <div class="bg-white rounded-3xl p-8 sm:p-12 shadow-xl border border-rose-100 text-center max-w-lg mx-auto">
                <div class="w-16 h-16 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i class="bi bi-hourglass-bottom"></i>
                </div>
                <h2 class="font-display text-2xl font-bold text-slate-800 mb-2">Link Has Expired</h2>
                <p class="text-slate-600 text-sm mb-6 leading-relaxed">
                    For your security, rescheduling links expire after 24 hours. The 24-hour window for this link has passed.
                </p>
                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 mb-6 text-xs text-slate-600">
                    Expired on: <span class="font-bold text-slate-800">{{ $rescheduleRequest->expires_at->format('M d, Y · g:i A') }}</span>
                </div>
                <p class="text-slate-500 text-xs mb-6">
                    Please contact Hinaguan Nature Park staff to request a fresh rescheduling link.
                </p>
                @if (!empty($parkContact))
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700">
                        <i class="bi bi-telephone-fill text-emerald-700"></i> Call: {{ $parkContact }}
                    </div>
                @endif
            </div>

        @elseif ($state === 'submitted_success')
            <!-- Submission Success Confirmation -->
            <div class="bg-white rounded-3xl p-8 sm:p-12 shadow-xl border border-emerald-100 text-center max-w-lg mx-auto">
                <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-4 text-3xl">
                    <i class="bi bi-check2-circle"></i>
                </div>
                <h2 class="font-display text-2xl sm:text-3xl font-bold text-emerald-950 mb-2">Request Submitted!</h2>
                <p class="text-slate-600 text-sm mb-6 leading-relaxed">
                    Thank you, <strong class="text-slate-800">{{ $reservation->booker_name }}</strong>! We have received your requested new dates.
                </p>

                <div class="bg-emerald-50/70 border border-emerald-200 rounded-2xl p-5 mb-6 text-left">
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="text-emerald-800/80 font-medium block">Reservation ID</span>
                            <span class="font-bold text-emerald-950 font-mono text-sm">#{{ $reservation->id }}</span>
                        </div>
                        <div>
                            <span class="text-emerald-800/80 font-medium block">Stay Duration</span>
                            <span class="font-semibold text-slate-700">
                                {{ $stayDays }} {{ \Illuminate\Support\Str::plural('Day', $stayDays) }} Continuous Stay
                            </span>
                        </div>

                        <!-- Original Schedule (no session/slot labels) -->
                        <div class="col-span-2 pt-2 border-t border-emerald-200/60">
                            <span class="text-emerald-800/80 font-medium block">Original Schedule</span>
                            <div class="text-slate-600 text-xs mt-0.5 font-medium">
                                @if ($origCheckInAt && $origCheckOutAt)
                                    <span>{{ $origCheckInAt->format('M d, Y · g:i A') }} &ndash; {{ $origCheckOutAt->format('M d, Y · g:i A') }}</span>
                                @elseif ($origStart)
                                    <span>{{ \Carbon\Carbon::parse($origStart)->format('M d, Y') }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Requested New Schedule: Display Check-in Date & Time and Check-out Date & Time -->
                        <div class="col-span-2 pt-2 border-t border-emerald-200/60">
                            <span class="text-emerald-800/80 font-bold uppercase tracking-wider text-[0.7rem] block">Requested New Schedule</span>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                <div class="bg-white/80 p-3 rounded-xl border border-emerald-100">
                                    <span class="text-[0.68rem] font-bold uppercase tracking-wider text-emerald-700 block">Check-In</span>
                                    <div class="font-bold text-emerald-950 text-sm mt-0.5">
                                        {{ $reqCheckInAt?->format('F j, Y') }}
                                    </div>
                                    <div class="text-xs font-semibold text-emerald-700 mt-0.5 flex items-center gap-1">
                                        <i class="bi bi-clock"></i> {{ $reqCheckInAt?->format('g:i A') }}
                                    </div>
                                </div>

                                <div class="bg-white/80 p-3 rounded-xl border border-emerald-100">
                                    <span class="text-[0.68rem] font-bold uppercase tracking-wider text-emerald-700 block">Check-Out</span>
                                    <div class="font-bold text-emerald-950 text-sm mt-0.5">
                                        {{ $reqCheckOutAt?->format('F j, Y') }}
                                    </div>
                                    <div class="text-xs font-semibold text-emerald-700 mt-0.5 flex items-center gap-1">
                                        <i class="bi bi-clock"></i> {{ $reqCheckOutAt?->format('g:i A') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 text-xs text-slate-600 mb-6 text-left flex items-start gap-2.5">
                    <i class="bi bi-chat-dots-fill text-emerald-600 text-base shrink-0 mt-0.5"></i>
                    <div>
                        <strong>What happens next?</strong><br>
                        Our park staff will review your requested schedule. Once approved, you will receive an SMS confirmation on <strong class="text-slate-800">{{ $displayPhone }}</strong>.
                    </div>
                </div>

                <a href="{{ url('/') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-emerald-900 hover:bg-emerald-950 text-white font-semibold text-sm transition-all shadow-md">
                    <i class="bi bi-house-door"></i> Back to Homepage
                </a>
            </div>

        @else
            <!-- Active Rescheduling Form State -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left: Reservation Details & Notice -->
                <div class="lg:col-span-5 flex flex-col gap-6">
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-emerald-950/10">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                            <div>
                                <span class="text-[0.68rem] font-bold uppercase tracking-wider text-emerald-800 block">Reschedule Request</span>
                                <h2 class="font-display text-xl font-bold text-slate-900">{{ $reservation->booker_name }}</h2>
                            </div>
                            <span class="px-3 py-1 rounded-xl bg-emerald-50 text-emerald-900 font-mono font-bold text-xs border border-emerald-200">
                                #{{ $reservation->id }}
                            </span>
                        </div>

                        <!-- Current Scheduled Info -->
                        <div class="space-y-3 text-xs mb-5">
                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-slate-500 font-medium">Original Dates:</span>
                                <span class="font-bold text-slate-800 text-right">
                                    @if ($stayDays > 1)
                                        {{ \Carbon\Carbon::parse($origStart)->format('M d, Y') }} &ndash; {{ \Carbon\Carbon::parse($origEnd)->format('M d, Y') }}
                                        <span class="block text-[0.68rem] text-slate-500 font-normal">({{ $stayDays }} Days Stay)</span>
                                    @else
                                        {{ \Carbon\Carbon::parse($origStart)->format('F j, Y') }}
                                    @endif
                                </span>
                            </div>

                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-slate-500 font-medium">Stay Duration:</span>
                                <span class="font-bold text-emerald-900">
                                    {{ $stayDays }} {{ \Illuminate\Support\Str::plural('Day', $stayDays) }} Continuous Stay
                                </span>
                            </div>

                            <!-- Session: Fixed (same as original) -->
                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-slate-500 font-medium">Session:</span>
                                <span class="font-bold text-slate-800 text-xs">
                                    Fixed (same as original)
                                </span>
                            </div>

                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                                <span class="text-slate-500 font-medium">Guests Count:</span>
                                <span class="font-semibold text-slate-800">{{ $reservation->number_of_guests }} guests</span>
                            </div>
                        </div>

                        @if ($stayDays > 1)
                            <div class="p-3.5 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 text-xs text-emerald-900 mb-5 flex items-start gap-2.5">
                                <i class="bi bi-calendar-range-fill text-emerald-700 text-base shrink-0 mt-0.5"></i>
                                <div class="leading-relaxed">
                                    <strong>{{ $stayDays }}-Day Stay Adaptation:</strong><br>
                                    When you pick a check-in date, the selector automatically schedules the full <strong>{{ $stayDays }} continuous days</strong> through check-out. The session is fixed (same as original).
                                </div>
                            </div>
                        @endif

                        <!-- Amenities Availed -->
                        @if ($reservation->reservationAmenities->isNotEmpty())
                            <div class="mb-5">
                                <div class="text-[0.68rem] font-bold uppercase tracking-wider text-slate-400 mb-2">Reserved Amenities</div>
                                <div class="space-y-1.5">
                                    @foreach ($reservation->reservationAmenities as $ra)
                                        <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-emerald-50/50 border border-emerald-100/60 text-xs">
                                            <span class="font-semibold text-emerald-950">{{ $ra->amenity?->amenities_name ?? 'Amenity' }}</span>
                                            <span class="text-[0.7rem] text-emerald-700 font-medium">Qty: {{ $ra->quantity }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Expiration Notice -->
                        <div class="flex items-center gap-2 p-3 rounded-xl bg-slate-100 text-[0.72rem] text-slate-600">
                            <i class="bi bi-clock-history text-slate-500"></i>
                            <span>Link expires in <strong>{{ $rescheduleRequest->expires_at->diffForHumans(['parts' => 2]) }}</strong> (valid 24h)</span>
                        </div>
                    </div>
                </div>

                <!-- Right: Availability Calendar & Submission -->
                <div class="lg:col-span-7">
                    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-emerald-950/10">
                        <div class="mb-4">
                            <span class="text-[0.68rem] font-bold uppercase tracking-wider text-emerald-700 block">Step 1 of 1</span>
                            <h3 class="font-display text-2xl font-bold text-slate-900">
                                @if ($stayDays > 1)
                                    Choose Check-In Date ({{ $stayDays }}-Day Stay)
                                @else
                                    Choose Your Preferred Date
                                @endif
                            </h3>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                @if ($stayDays > 1)
                                    Select your preferred check-in date. The calendar will automatically reserve the entire <strong>{{ $stayDays }}-day window</strong>. The session is fixed (same as original).
                                @else
                                    Only dates with full availability for your reserved amenities are selectable. The session is fixed (same as original).
                                @endif
                            </p>
                        </div>

                        <!-- Calendar Header (Month / Year Navigation) -->
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                            <button type="button" id="prevMonthBtn" class="w-9 h-9 rounded-xl border border-slate-200 hover:border-emerald-600 bg-white hover:bg-emerald-50 text-slate-700 flex items-center justify-center transition-all cursor-pointer">
                                <i class="bi bi-chevron-left text-xs font-bold"></i>
                            </button>
                            <div class="text-center">
                                <span id="calendarMonthLabel" class="font-display font-bold text-base sm:text-lg text-slate-900 block">Loading...</span>
                            </div>
                            <button type="button" id="nextMonthBtn" class="w-9 h-9 rounded-xl border border-slate-200 hover:border-emerald-600 bg-white hover:bg-emerald-50 text-slate-700 flex items-center justify-center transition-all cursor-pointer">
                                <i class="bi bi-chevron-right text-xs font-bold"></i>
                            </button>
                        </div>

                        <!-- Weekday labels -->
                        <div class="grid grid-cols-7 gap-1.5 text-center mb-2">
                            <span class="text-[0.7rem] font-bold uppercase tracking-wider text-slate-400">Sun</span>
                            <span class="text-[0.7rem] font-bold uppercase tracking-wider text-slate-400">Mon</span>
                            <span class="text-[0.7rem] font-bold uppercase tracking-wider text-slate-400">Tue</span>
                            <span class="text-[0.7rem] font-bold uppercase tracking-wider text-slate-400">Wed</span>
                            <span class="text-[0.7rem] font-bold uppercase tracking-wider text-slate-400">Thu</span>
                            <span class="text-[0.7rem] font-bold uppercase tracking-wider text-slate-400">Fri</span>
                            <span class="text-[0.7rem] font-bold uppercase tracking-wider text-slate-400">Sat</span>
                        </div>

                        <!-- Calendar Days Grid -->
                        <div id="calendarGrid" class="grid grid-cols-7 gap-1.5 min-h-[260px] relative">
                            <!-- Injected dynamically by JS -->
                            <div class="col-span-7 flex items-center justify-center py-16 text-slate-400 text-xs">
                                <i class="bi bi-arrow-repeat animate-spin text-lg mr-2 text-emerald-600"></i> Loading availability calendar...
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="flex items-center justify-between text-[0.7rem] text-slate-500 mt-4 pt-3 border-t border-slate-100 flex-wrap gap-2">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded-md border border-slate-300 bg-white"></span> Available
                                </span>
                                @if ($stayDays > 1)
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="w-3 h-3 rounded-md bg-emerald-900"></span> Check-in / Out
                                    </span>
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="w-3 h-3 rounded-md bg-emerald-200 border border-emerald-300"></span> Stay Days
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="w-3 h-3 rounded-md bg-emerald-900"></span> Selected
                                    </span>
                                @endif
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="w-3 h-3 rounded-md bg-slate-200 line-through"></span> Unavailable
                                </span>
                            </div>
                        </div>

                        <!-- Selection Summary & Submit Form -->
                        <form id="rescheduleForm" action="{{ url('/reservation/reschedule/' . $rescheduleRequest->token) }}" method="POST" class="mt-6 pt-5 border-t border-slate-100">
                            @csrf
                            <input type="hidden" name="requested_date" id="requestedDateInput" value="">

                            <div class="bg-emerald-50/70 border border-emerald-200/80 rounded-2xl p-4 sm:p-5 mb-5">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-emerald-200/60 mb-3">
                                    <div>
                                        <span class="text-[0.68rem] font-bold uppercase tracking-wider text-emerald-800 block">
                                            @if ($stayDays > 1)
                                                Selected {{ $stayDays }}-Day Reschedule Schedule:
                                            @else
                                                Selected Preferred Date:
                                            @endif
                                        </span>
                                        <div id="selectedDateDisplay" class="font-bold text-slate-800 text-sm sm:text-base mt-0.5">
                                            Please tap an available check-in date on the calendar above
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        <span id="dateBadge" class="hidden px-3 py-1 rounded-full text-xs font-bold bg-emerald-800 text-white">
                                            @if ($stayDays > 1) {{ $stayDays }} Days Picked @else Date Picked @endif
                                        </span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-emerald-950/80">
                                    <div class="flex items-center gap-1.5">
                                        <i class="bi bi-clock-history text-emerald-700"></i>
                                        <span>Session: <strong class="text-emerald-950">Fixed (same as original)</strong></span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <i class="bi bi-shield-check text-emerald-700"></i>
                                        <span>Amenities: <strong class="text-emerald-950">100% Available for full stay</strong></span>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" id="submitRescheduleBtn" disabled class="w-full cursor-not-allowed opacity-50 py-3.5 px-6 rounded-2xl bg-emerald-800 hover:bg-emerald-900 text-white font-bold text-sm transition-all duration-200 shadow-md flex items-center justify-center gap-2">
                                <i class="bi bi-calendar-check text-base"></i>
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
                </div>
            </div>

            <!-- Confirmation Modal -->
            <div id="confirmSubmitModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-xs p-4">
                <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-slate-100 text-center">
                    <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center mx-auto mb-4 text-2xl">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <h4 class="font-display font-bold text-xl text-slate-900 mb-2">
                        @if ($stayDays > 1)
                            Confirm {{ $stayDays }}-Day Reschedule
                        @else
                            Confirm Date Submission
                        @endif
                    </h4>
                    <p class="text-xs text-slate-600 mb-4 leading-relaxed">
                        Are you sure you want to submit this requested schedule for your reservation?
                    </p>

                    <div class="bg-emerald-50/70 border border-emerald-200/80 rounded-2xl p-4 text-left text-xs mb-4 space-y-2">
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
                        <div class="flex justify-between pt-1 border-t border-emerald-200/60">
                            <span class="text-slate-500 font-medium">Session:</span>
                            <span class="font-bold text-emerald-900">Fixed (same as original)</span>
                        </div>
                    </div>

                    <p class="text-[0.72rem] text-slate-400 mb-6 italic">
                        Note: This single-use link will be submitted and cannot be reopened. Staff will be notified immediately.
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <button type="button" id="modalCancelBtn" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-semibold text-xs hover:bg-slate-50 transition-all cursor-pointer">
                            Choose Another
                        </button>
                        <button type="button" id="modalConfirmBtn" class="px-6 py-2.5 rounded-xl bg-emerald-800 hover:bg-emerald-900 text-white font-bold text-xs transition-all shadow-sm cursor-pointer">
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

                    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                    const calendarMonthLabel = document.getElementById('calendarMonthLabel');
                    const calendarGrid = document.getElementById('calendarGrid');
                    const prevBtn = document.getElementById('prevMonthBtn');
                    const nextBtn = document.getElementById('nextMonthBtn');
                    const requestedDateInput = document.getElementById('requestedDateInput');
                    const selectedDateDisplay = document.getElementById('selectedDateDisplay');
                    const dateBadge = document.getElementById('dateBadge');
                    const submitBtn = document.getElementById('submitRescheduleBtn');
                    const form = document.getElementById('rescheduleForm');
                    const confirmModal = document.getElementById('confirmSubmitModal');
                    const modalCheckInText = document.getElementById('modalCheckInText');
                    const modalCheckOutText = document.getElementById('modalCheckOutText');
                    const modalDateConfirmText = document.getElementById('modalDateConfirmText');
                    const modalCancelBtn = document.getElementById('modalCancelBtn');
                    const modalConfirmBtn = document.getElementById('modalConfirmBtn');

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
                            } else {
                                availabilityMap = {};
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

                            // availabilityMap[dateStr] checks if the full stay window is available for all reserved amenities
                            const isAvailable = availabilityMap[dateStr] === true && !isPastOrToday && !isOriginalDate;

                            if (isPastOrToday || isOriginalDate || !isAvailable) {
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
                                    selectDateRange(dateStr);
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

                    // Select Date / Range
                    function selectDateRange(startDateStr) {
                        selectedStartDateStr = startDateStr;
                        selectedEndDateStr = addDaysToDateStr(startDateStr, stayDays - 1);
                        requestedDateInput.value = startDateStr;

                        const startObj = parseDateString(selectedStartDateStr);
                        const endObj = parseDateString(selectedEndDateStr);

                        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
                        const startFormatted = startObj.toLocaleDateString('en-US', options);
                        const endFormatted = endObj.toLocaleDateString('en-US', options);

                        if (stayDays > 1) {
                            selectedDateDisplay.innerHTML = `
                                <div class="text-emerald-950 font-bold text-sm sm:text-base">
                                    ${startFormatted} &ndash; ${endFormatted}
                                </div>
                                <div class="text-xs font-semibold text-emerald-800 mt-0.5">
                                    ${stayDays} Days Continuous Stay &bull; Session is fixed (same as original)
                                </div>
                            `;
                            dateBadge.textContent = `${stayDays} Days Picked`;
                        } else {
                            selectedDateDisplay.innerHTML = `
                                <span class="text-emerald-950 font-bold">${startFormatted}</span>
                                <span class="text-xs font-semibold text-emerald-800 ml-2">&bull; Session is fixed (same as original)</span>
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
                    prevBtn.addEventListener('click', () => {
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

                    nextBtn.addEventListener('click', () => {
                        currentMonth++;
                        if (currentMonth > 11) {
                            currentMonth = 0;
                            currentYear++;
                        }
                        loadAvailability(currentYear, currentMonth);
                    });

                    // Intercept form submit to show confirmation modal
                    form.addEventListener('submit', (e) => {
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

                        confirmModal.classList.remove('hidden');
                        confirmModal.classList.add('flex');
                    });

                    modalCancelBtn.addEventListener('click', () => {
                        confirmModal.classList.add('hidden');
                        confirmModal.classList.remove('flex');
                    });

                    modalConfirmBtn.addEventListener('click', () => {
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
