<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reservations — Hinaguan Nature Park</title>
    <script>
        // Prevent flash of wrong theme by setting theme immediately
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link rel="icon" type="image/jpeg" href="{{ asset('storage/design_images/main_logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700|poppins:300,400,500,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite([
        'resources/css/app.css',
        'resources/css/homepage.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/staff_sidemenu.css',
        'resources/css/chatbot.css',
        'resources/css/staff_css/staff_shared.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/staff_js/staff_reservations.js',
        'resources/js/staff_chatbot.js',
    ])
    <script>
        window.staffAmenitiesData = @json($allAmenities ?? []);
        window.ACTIVE_OCCUPIED_AMENITY_IDS = @json($activeOccupiedAmenityIds ?? []);
    </script>
    <style>
        body.staff-portal {
            background-color: #ebf3ec !important;
        }
        [data-theme="dark"] body.staff-portal {
            background-color: #0f1110 !important;
        }
        body.staff-portal .dash-layout,
        body.staff-portal .dash-main,
        body.staff-portal .dash-content {
            background: transparent !important;
            background-color: transparent !important;
            background-image: none !important;
        }
        body.staff-portal .dash-main {
            position: relative !important;
            min-height: 100vh;
            z-index: 0;
        }
        body.staff-portal .dash-main::before {
            content: '' !important;
            display: block !important;
            position: fixed !important;
            top: 0 !important;
            left: var(--dash-sidebar-w, 10rem) !important;
            right: 0 !important;
            bottom: 0 !important;
            width: auto !important;
            height: 100vh !important;
            z-index: -1 !important;
            pointer-events: none !important;
            background-color: #ebf3ec !important;
            background-image: url('{{ asset('storage/design_images/staff-admin-background-image.jpeg') }}') !important;
            background-size: 100% 100% !important;
            background-position: center center !important;
            background-repeat: no-repeat !important;
            filter: none !important;
            -webkit-filter: none !important;
            opacity: 1 !important;
            transition: left 0.25s ease !important;
        }
        .dash-layout.sidebar-collapsed .dash-main::before {
            left: 0 !important;
        }
        @media (max-width: 992px) {
            body.staff-portal .dash-main::before {
                left: 0 !important;
            }
        }
        [data-theme="dark"] body.staff-portal .dash-main::before {
            background-color: #0f1110 !important;
            background-image: linear-gradient(rgba(15, 17, 16, 0.94), rgba(15, 17, 16, 0.97)), url('{{ asset('storage/design_images/staff-admin-background-image.jpeg') }}') !important;
            filter: none !important;
            -webkit-filter: none !important;
            opacity: 1 !important;
        }
        body.staff-portal .dash-content {
            position: relative !important;
            z-index: 1 !important;
        }
        body.staff-portal [class*="backdrop-blur"] {
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
    </style>
</head>
<body class="antialiased staff-portal">
    <div class="dash-layout">
        <x-staff_sidemenu active="reservations" userName="{{ session('auth_user.name') ?? 'Staff User' }}" userRole="Staff" />

        <div class="dash-main">
            <x-header
                title="Reservations"
                subtitle="Manage online reservations and walk-in check-ins"
            />

            <main class="dash-content p-6">
                @php
                    $parkSettings = \App\Models\ParkSetting::first();
                    $currentHour = now()->format('H:i');
                    $daytimeStart = $parkSettings?->daytime_start ?? '06:00';
                    $daytimeEnd = $parkSettings?->daytime_end ?? '17:00';
                    $nighttimeStart = $parkSettings?->nighttime_start ?? '17:00';
                    $nighttimeEnd = $parkSettings?->nighttime_end ?? '06:00';
                    $timePeriod = 'Daytime';
                    if ($nighttimeStart && $nighttimeEnd) {
                        if ($nighttimeStart <= $nighttimeEnd) {
                            if ($currentHour >= $nighttimeStart && $currentHour <= $nighttimeEnd) $timePeriod = 'Nighttime';
                        } else {
                            if ($currentHour >= $nighttimeStart || $currentHour <= $nighttimeEnd) $timePeriod = 'Nighttime';
                        }
                    }
                @endphp

                <script data-spa-data="">
                    window.staffAmenitiesData = @json($allAmenities ?? []);
                    window.staffReservationData = @json($reservationData ?? []);
                </script>
                <div class="resv-metrics mb-5 grid grid-cols-4 gap-3" style="display: grid !important; grid-template-columns: repeat(4, minmax(0, 1fr)) !important;" data-park-settings="{{ json_encode([
                    'daytime_start' => $daytimeStart,
                    'daytime_end' => $daytimeEnd,
                    'nighttime_start' => $nighttimeStart,
                    'nighttime_end' => $nighttimeEnd,
                    'daytime_adult_entrance_fee' => $parkSettings->daytime_adult_entrance_fee ?? 0,
                    'daytime_child_entrance_fee' => $parkSettings->daytime_child_entrance_fee ?? 0,
                    'nighttime_adult_entrance_fee' => $parkSettings->nighttime_adult_entrance_fee ?? 0,
                    'nighttime_child_entrance_fee' => $parkSettings->nighttime_child_entrance_fee ?? 0,
                    'day_pool_fee' => $parkSettings->day_pool_fee ?? 0,
                    'night_pool_fee' => $parkSettings->night_pool_fee ?? 0,
                ]) }}">
                    <!-- 1. DATE & TIME -->
                    <article class="flex items-center gap-3 rounded-2xl border border-white/80 bg-white/90 p-3.5 shadow-[0_4px_20px_rgba(20,50,30,0.03)] backdrop-blur-sm dark:border-white/10 dark:bg-[#181b19]/90 min-w-0">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#e8f5e9] text-[#2e7d32] dark:bg-[rgba(46,125,50,0.2)] dark:text-[#9ca3af]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </span>
                        <div class="flex flex-col gap-0.5 min-w-0">
                            <p class="m-0 text-[0.68rem] font-bold uppercase tracking-wider text-[#718076] dark:text-[#9baaa1] truncate">DATE &amp; TIME</p>
                            <p class="m-0 text-sm font-bold text-[#183d28] dark:text-[#e8f5e9] truncate" id="resvDate">{{ now()->format('F j, Y') }}</p>
                            <p class="m-0 text-xs font-semibold text-[#2e7d32] dark:text-[#4ade80] flex items-center gap-1.5 truncate">
                                <svg class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span id="resvTime">{{ now()->format('g:i A') }}</span>
                            </p>
                        </div>
                    </article>

                    <!-- 2. SESSION -->
                    <article class="flex items-center gap-3 rounded-2xl border border-white/80 bg-white/90 p-3.5 shadow-[0_4px_20px_rgba(20,50,30,0.03)] backdrop-blur-sm dark:border-white/10 dark:bg-[#181b19]/90 min-w-0">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#fff8e1] text-[#f57f17] dark:bg-[rgba(255,179,0,0.2)] dark:text-[#ffca28]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </span>
                        <div class="flex flex-col gap-1 min-w-0">
                            <p class="m-0 text-[0.68rem] font-bold uppercase tracking-wider text-[#718076] dark:text-[#9baaa1] truncate">SESSION</p>
                            <span class="inline-flex w-fit items-center rounded-md px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide {{ $timePeriod === 'Daytime' ? 'bg-[#fff3e0] text-[#e65100] dark:bg-[rgba(255,152,0,0.2)] dark:text-[#ffb74d]' : 'bg-[#ede7f6] text-[#6a1b9a] dark:bg-[rgba(103,58,183,0.2)] dark:text-[#ce93d8]' }}" id="resvSession">{{ $timePeriod === 'Nighttime' ? 'OVERNIGHT' : strtoupper($timePeriod) }}</span>
                            <p class="m-0 text-[0.65rem] text-[#718076] dark:text-[#9baaa1] truncate">
                                {{ $timePeriod === 'Daytime' ? ($daytimeStart ? \Carbon\Carbon::parse($daytimeStart)->format('g:i A') : '6:00 AM') . ' – ' . ($daytimeEnd ? \Carbon\Carbon::parse($daytimeEnd)->format('g:i A') : '5:00 PM') : ($nighttimeStart ? \Carbon\Carbon::parse($nighttimeStart)->format('g:i A') : '5:00 PM') . ' – ' . ($nighttimeEnd ? \Carbon\Carbon::parse($nighttimeEnd)->format('g:i A') : '6:00 AM') }}
                            </p>
                        </div>
                    </article>

                    <!-- 3. PENDING RESERVATIONS -->
                    <article class="flex items-center gap-3 rounded-2xl border border-white/80 bg-white/90 p-3.5 shadow-[0_4px_20px_rgba(20,50,30,0.03)] backdrop-blur-sm dark:border-white/10 dark:bg-[#181b19]/90 min-w-0">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#fff3e0] text-[#e65100] dark:bg-[rgba(255,152,0,0.2)] dark:text-[#ffb74d]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div class="flex flex-col gap-0.5 min-w-0">
                            <p class="m-0 text-2xl font-extrabold leading-none text-[#183d28] dark:text-[#e8f5e9]">{{ $pendingCount }}</p>
                            <p class="m-0 text-xs font-medium text-[#718076] dark:text-[#9baaa1] truncate">Pending Reservations</p>
                            <p class="m-0 text-[0.65rem] text-[#718076] dark:text-[#9baaa1] truncate">Awaiting confirmation</p>
                        </div>
                    </article>

                    <!-- 4. TODAY & PAST RESERVATIONS -->
                    <article class="flex items-center gap-3 rounded-2xl border border-white/80 bg-white/90 p-3.5 shadow-[0_4px_20px_rgba(20,50,30,0.03)] backdrop-blur-sm dark:border-white/10 dark:bg-[#181b19]/90 min-w-0">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ ($pastScheduleCount ?? 0) > 0 ? 'bg-[#fee2e2] text-[#dc2626] dark:bg-[rgba(220,38,38,0.2)] dark:text-[#f87171]' : 'bg-[#e8f5e9] text-[#2e7d32] dark:bg-[rgba(46,125,50,0.2)] dark:text-[#9ca3af]' }}">
                            <i class="bi bi-calendar-event text-lg"></i>
                        </span>
                        <div class="flex flex-col gap-0.5 min-w-0">
                            <p class="m-0 text-2xl font-extrabold leading-none text-[#183d28] dark:text-[#e8f5e9]">{{ $scheduledOrPastCount ?? 0 }}</p>
                            <p class="m-0 text-xs font-medium text-[#718076] dark:text-[#9baaa1] truncate">Today &amp; Past Reservations</p>
                            <p class="m-0 text-[0.65rem] text-[#718076] dark:text-[#9baaa1] truncate">
                                <span>{{ $todayScheduledCount ?? 0 }} today</span>
                                @if(($pastScheduleCount ?? 0) > 0)
                                    &middot; <span class="font-bold text-[#dc2626] dark:text-[#f87171]">{{ $pastScheduleCount }} past schedule</span>
                                @endif
                            </p>
                        </div>
                    </article>
                </div>

                @if (!empty($weatherAlerts) && count($weatherAlerts) > 0)
                @php
                    // Group alerts by date for cleaner display
                    $alertsByDate = [];
                    foreach ($weatherAlerts as $alert) {
                        $alertsByDate[$alert['date']][] = $alert;
                    }
                @endphp
                <div class="mb-4 rounded-2xl border border-amber-300/60 bg-gradient-to-br from-amber-50 to-orange-50 shadow-sm dark:border-amber-500/20 dark:from-amber-950/30 dark:to-orange-950/20 overflow-hidden" id="weatherAlertBanner">
                    {{-- Header --}}
                    <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-amber-200/60 dark:border-amber-500/15">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                                <i class="bi bi-cloud-rain-fill text-sm"></i>
                            </span>
                            <div>
                                <p class="m-0 text-sm font-bold text-amber-900 dark:text-amber-300">
                                    Weather Alert — {{ count($weatherAlerts) }} Reservation{{ count($weatherAlerts) > 1 ? 's' : '' }} May Face Rain
                                </p>
                                <p class="m-0 text-xs text-amber-700/80 dark:text-amber-400/70">Based on 3-day forecast · Consider notifying guests</p>
                            </div>
                        </div>
                        <button type="button" onclick="document.getElementById('weatherAlertBanner').remove()" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-amber-500 hover:bg-amber-100 dark:hover:bg-amber-500/20 transition-colors cursor-pointer" title="Dismiss">
                            <i class="bi bi-x text-base"></i>
                        </button>
                    </div>

                    {{-- Grouped by date --}}
                    <div class="divide-y divide-amber-200/50 dark:divide-amber-500/10">
                        @foreach ($alertsByDate as $date => $dateAlerts)
                        @php
                            $firstAlert = $dateAlerts[0];
                            $isToday = $date === now()->toDateString();
                            $isTomorrow = $date === now()->addDay()->toDateString();
                            $dateLabel = $isToday ? 'Today' : ($isTomorrow ? 'Tomorrow' : $firstAlert['date_label']);
                        @endphp
                        <div class="px-4 py-3">
                            {{-- Date row --}}
                            <div class="flex items-center gap-3 mb-2.5">
                                {{-- Weather icon --}}
                                @if($firstAlert['icon'])
                                    <img src="{{ $firstAlert['icon'] }}" alt="{{ $firstAlert['condition'] }}" class="h-9 w-9 shrink-0 drop-shadow-sm" loading="lazy">
                                @else
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-500/20">
                                        <i class="bi bi-cloud-drizzle-fill text-amber-500 text-lg"></i>
                                    </span>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-bold text-sm text-amber-900 dark:text-amber-300">{{ $dateLabel }}</span>
                                        <span class="text-xs text-amber-700/70 dark:text-amber-400/60">{{ $firstAlert['date_label'] }}</span>
                                        {{-- Rain chance pill --}}
                                        @php
                                            $rc = $firstAlert['rain_chance'];
                                            $pillColor = $rc >= 80 ? 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400' : ($rc >= 60 ? 'bg-orange-100 text-orange-700 dark:bg-orange-950/40 dark:text-orange-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400');
                                        @endphp
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $pillColor }}">
                                            <i class="bi bi-droplet-fill text-[9px]"></i>
                                            {{ $rc }}% rain
                                        </span>
                                        <span class="text-[11px] text-amber-700/70 dark:text-amber-400/60">{{ $firstAlert['condition'] }}</span>
                                        @if($firstAlert['max_temp_c'])
                                            <span class="text-[11px] text-amber-700/60 dark:text-amber-400/50">
                                                {{ round($firstAlert['max_temp_c']) }}°/{{ round($firstAlert['min_temp_c'] ?? $firstAlert['max_temp_c']) }}°C
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <span class="shrink-0 rounded-full bg-amber-100 dark:bg-amber-500/20 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:text-amber-400">
                                    {{ count($dateAlerts) }} reservation{{ count($dateAlerts) > 1 ? 's' : '' }}
                                </span>
                            </div>

                            {{-- Reservation chips --}}
                            <div class="flex flex-wrap gap-2 pl-12">
                                @foreach ($dateAlerts as $alert)
                                <div class="flex items-center gap-1.5 rounded-xl border border-amber-200/80 bg-white/70 dark:border-amber-500/15 dark:bg-white/5 px-2.5 py-1.5 text-xs shadow-xs">
                                    <span class="font-bold text-[#183d28] dark:text-[#e8f5e9]">#{{ $alert['reservation_id'] }}</span>
                                    <span class="text-[#718076] dark:text-[#9baaa1]">·</span>
                                    <span class="text-[#374151] dark:text-[#d1fae5] font-medium truncate max-w-[120px]">{{ $alert['booker_name'] }}</span>
                                    @if (!empty($alert['amenity_names']))
                                        <span class="text-[#718076] dark:text-[#9baaa1]">·</span>
                                        <span class="text-[#718076] dark:text-[#9baaa1] truncate max-w-[140px]">{{ implode(', ', $alert['amenity_names']) }}</span>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if (session('success'))
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-300 font-medium" id="pageFlashSuccess" data-page-flash="success">{{ session('success') }}</div>
                @endif


                <div class="resv-toolbar mb-3.5 flex flex-wrap items-center justify-between gap-3">
                    <div class="resv-toolbar__left flex flex-wrap items-center gap-2.5">
                        <button type="button" class="guest-filter-toggle inline-flex cursor-pointer items-center justify-between gap-2 rounded-xl border border-[#dfe5e0] bg-white px-4 py-2 text-sm font-semibold text-[#183d28] shadow-sm transition-all duration-150 hover:bg-gray-50 dark:border-white/15 dark:bg-[#181b19] dark:text-[#f3f4f6] dark:hover:bg-[#242a26]" id="reservationFilterToggle" aria-expanded="false" aria-controls="reservationFilterPanel">
                            <svg class="h-4 w-4 text-[#718076] dark:text-[#9ca3af]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/></svg>
                            <span>Filters</span>
                            <span class="guest-filter-toggle__icon text-xs text-[#718076] dark:text-[#9ca3af]">▾</span>
                        </button>
                        <div class="resv-search flex items-center gap-2 rounded-xl border border-[#dfe5e0] bg-white px-3.5 py-2 shadow-sm transition-all duration-150 focus-within:border-[#2d6a4f] dark:border-white/15 dark:bg-[#181b19]">
                            <svg class="h-4 w-4 shrink-0 text-[#718076] dark:text-[#9ca3af]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                            <input type="search" id="reservationSearchInput" placeholder="Search reservations..." class="w-[200px] sm:w-[260px] border-0 bg-transparent p-0 text-sm text-[#183d28] outline-none placeholder:text-[#718076] dark:text-[#f3f4f6]">
                        </div>
                    </div>
                    <div class="resv-toolbar__right flex flex-wrap items-center gap-2">
                        <button type="button" class="inline-flex cursor-pointer items-center gap-2 whitespace-nowrap rounded-xl border border-amber-500/30 bg-amber-500/10 px-3.5 py-2 text-sm font-semibold text-amber-900 transition-all duration-150 hover:bg-amber-500 hover:text-white active:scale-95 dark:border-amber-500/30 dark:bg-amber-500/20 dark:text-amber-300 dark:hover:bg-amber-600 dark:hover:text-white shadow-xs" id="reschedRequestsBtn" title="View Reschedule Requests">
                            <i class="bi bi-calendar2-range text-sm"></i>
                            <span>Resched Requests</span>
                            <span id="reschedRequestsBadge" class="hidden inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-amber-600 rounded-full">0</span>
                        </button>
                        <button type="button" id="scanQrBtn" class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-hp-green/30 bg-hp-green/10 text-hp-green transition-all duration-150 hover:bg-hp-green hover:text-white hover:border-hp-green active:scale-[0.98] dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-hp-green dark:hover:text-white shadow-xs" title="Scan reservation QR" aria-label="Scan reservation QR">
                            <i class="bi bi-qr-code-scan text-base"></i>
                        </button>
                        <button type="button" class="resv-tool-btn inline-flex cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-xl border border-[#dfe5e0] bg-white px-3.5 py-2 text-sm font-semibold text-[#183d28] shadow-sm transition-all duration-150 hover:bg-gray-50 active:scale-95 dark:border-white/15 dark:bg-[#181b19] dark:text-[#f3f4f6] dark:hover:bg-[#242a26]" id="refreshTableBtn">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Refresh
                        </button>
                    </div>
                </div>

                <div class="guest-toolbar guest-toolbar--collapsed resv-filter-panel mb-3.5 grid items-end gap-3 rounded-2xl border border-[#dfe5e0] bg-white p-4 shadow-sm dark:border-white/15 dark:bg-[#181b19]" id="reservationFilterPanel" hidden>
                    <label class="guest-toolbar__field grid gap-1.5 text-[0.82rem] font-semibold text-[#183d28] dark:text-[#f3f4f6]">
                        <span>Sort by</span>
                        <select id="reservationSortSelect" class="w-full rounded-xl border border-[#dfe5e0] bg-white px-3.5 py-2 text-sm text-[#183d28] focus:border-[#2d6a4f] focus:outline-none dark:border-white/15 dark:bg-[#141715] dark:text-[#f3f4f6]">
                            <option value="date-asc">Reservation date (soonest)</option>
                            <option value="date-desc">Reservation date (latest)</option>
                            <option value="name-asc">Booker (A-Z)</option>
                            <option value="name-desc">Booker (Z-A)</option>
                            <option value="amount-desc">Amount (High-Low)</option>
                        </select>
                    </label>
                    <label class="guest-toolbar__field grid gap-1.5 text-[0.82rem] font-semibold text-[#183d28] dark:text-[#f3f4f6]">
                        <span>Status</span>
                        <select id="reservationStatusFilter" class="w-full rounded-xl border border-[#dfe5e0] bg-white px-3.5 py-2 text-sm text-[#183d28] focus:border-[#2d6a4f] focus:outline-none dark:border-white/15 dark:bg-[#141715] dark:text-[#f3f4f6]">
                            <option value="all">All statuses</option>
                            <option value="today">Today's Reservations</option>
                            <option value="past">Past / Overdue Arrival</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="checked in">Checked In</option>
                            <option value="checked out">Checked Out</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="no show">No Show</option>
                        </select>
                    </label>
                    <label class="guest-toolbar__field grid gap-1.5 text-[0.82rem] font-semibold text-[#183d28] dark:text-[#f3f4f6]">
                        <span>Reservation date from</span>
                        <input type="date" id="reservationDateFrom" class="w-full rounded-xl border border-[#dfe5e0] bg-white px-3.5 py-2 text-sm text-[#183d28] focus:border-[#2d6a4f] focus:outline-none dark:border-white/15 dark:bg-[#141715] dark:text-[#f3f4f6]">
                    </label>
                    <label class="guest-toolbar__field grid gap-1.5 text-[0.82rem] font-semibold text-[#183d28] dark:text-[#f3f4f6]">
                        <span>Reservation date to</span>
                        <input type="date" id="reservationDateTo" class="w-full rounded-xl border border-[#dfe5e0] bg-white px-3.5 py-2 text-sm text-[#183d28] focus:border-[#2d6a4f] focus:outline-none dark:border-white/15 dark:bg-[#141715] dark:text-[#f3f4f6]">
                    </label>
                    <button type="button" class="guest-toolbar__clear cursor-pointer rounded-xl border border-[#dfe5e0] bg-[#f4f7f5] px-4 py-2 text-sm font-semibold text-[#183d28] transition-colors hover:bg-gray-200 dark:border-white/15 dark:bg-white/10 dark:text-[#f3f4f6]" id="reservationFiltersClear">Clear</button>
                </div>

                <div class="guest-toolbar__meta mb-2.5 text-xs font-medium text-[#718076] dark:text-[#9baaa1]">
                    <span id="reservationResultsCount">Showing {{ $reservations->count() }} of {{ $reservations->count() }} reservation{{ $reservations->count() === 1 ? '' : 's' }}</span>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-[#dfe5e0] bg-white shadow-[0_4px_20px_rgba(20,50,30,0.04)] dark:border-white/10 dark:bg-[#181b19] dark:shadow-[0_4px_20px_rgba(0,0,0,0.3)]" id="reservationTableWrap">
                    <table class="w-full min-w-[880px] table-fixed border-separate border-spacing-0 text-left">
                        <colgroup>
                            <col style="width: 8%; min-width: 70px;">
                            <col style="width: 28%; min-width: 220px;">
                            <col style="width: 22%; min-width: 175px;">
                            <col style="width: 15%; min-width: 125px;">
                            <col style="width: 13%; min-width: 110px;">
                            <col style="width: 14%; min-width: 115px;">
                        </colgroup>
                        <thead class="bg-[#f7faf8] dark:bg-[#1e2220]">
                            <tr class="border-b border-[#e8eee9] dark:border-white/10">
                                <th class="py-3.5 pl-5 pr-3 text-center align-middle text-[0.72rem] font-bold uppercase tracking-wider text-[#486553] dark:text-[#9ca3af] whitespace-nowrap">ID</th>
                                <th class="py-3.5 px-4 text-left align-middle text-[0.72rem] font-bold uppercase tracking-wider text-[#486553] dark:text-[#9ca3af]">BOOKER</th>
                                <th class="py-3.5 px-4 text-left align-middle text-[0.72rem] font-bold uppercase tracking-wider text-[#486553] dark:text-[#9ca3af]">RESERVATION DATE</th>
                                <th class="py-3.5 px-3 text-center align-middle text-[0.72rem] font-bold uppercase tracking-wider text-[#486553] dark:text-[#9ca3af]">SESSION</th>
                                <th class="py-3.5 px-3 text-center align-middle text-[0.72rem] font-bold uppercase tracking-wider text-[#486553] dark:text-[#9ca3af]">STATUS</th>
                                <th class="py-3.5 pl-4 pr-6 sm:pr-8 text-right align-middle text-[0.72rem] font-bold uppercase tracking-wider text-[#486553] dark:text-[#9ca3af]">AMOUNT</th>
                            </tr>
                        </thead>
                        <tbody id="reservationTableBody" class="divide-y divide-[#f0f4ef] bg-white dark:divide-white/5 dark:bg-[#181b19]">
                            @forelse ($reservations as $reservation)
                                @php
                                    $resDateStr = $reservation->reservation_date;
                                    $resDateObj = $resDateStr ? \Carbon\Carbon::parse($resDateStr)->startOfDay() : null;
                                    $todayObj = now()->startOfDay();
                                    $isToday = $resDateObj && $resDateObj->equalTo($todayObj);
                                    $statusLower = strtolower($reservation->status);
                                    $isPendingOrConfirmed = in_array($statusLower, ['pending', 'confirmed']);
                                    $isPastArrival = $resDateObj && $resDateObj->lessThan($todayObj) && $isPendingOrConfirmed;
                                    $daysOverdue = $isPastArrival ? $todayObj->diffInDays($resDateObj) : 0;

                                    $timeSlots = $reservationData[$reservation->id]['time_slots'] ?? [];
                                    $initials = collect(explode(' ', trim($reservation->booker_name ?? '?')))
                                        ->filter()
                                        ->take(1)
                                        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
                                        ->implode('') ?: '?';
                                    $resStartDate = $reservation->reservation_date ? \Carbon\Carbon::parse($reservation->reservation_date)->format('Y-m-d') : null;
                                    $resEndDate = $reservation->end_date ? \Carbon\Carbon::parse($reservation->end_date)->format('Y-m-d') : null;
                                    $totalDays = (int) ($reservation->total_days ?? ($resStartDate && $resEndDate ? (\Carbon\Carbon::parse($resStartDate)->diffInDays(\Carbon\Carbon::parse($resEndDate)) + 1) : 1));
                                    $isMultiDay = $resEndDate && $resStartDate && ($resEndDate !== $resStartDate) && ($totalDays > 1);
                                @endphp
                                <tr
                                    class="guest-row reservation-row {{ $isToday ? 'today-reservation' : '' }} {{ $isPastArrival ? 'past-reservation' : '' }} cursor-pointer select-none transition-colors duration-150 hover:bg-[#f7faf6] focus-visible:bg-[#f7faf6] focus-visible:outline-none dark:hover:bg-[#242a26] dark:focus-visible:bg-[#242a26]"
                                    data-reservation-id="{{ $reservation->id }}"
                                    data-booker-name="{{ e($reservation->booker_name) }}"
                                    data-email="{{ e($reservation->email) }}"
                                    data-phone="{{ e($reservation->phone) }}"
                                    data-reservation-date="{{ $reservation->reservation_date }}"
                                    data-status="{{ strtolower($reservation->status) }}"
                                    data-guests="{{ $reservation->number_of_guests }}"
                                    data-total-amount="{{ (float) $reservation->total_amount }}"
                                    data-is-past="{{ $isPastArrival ? '1' : '0' }}"
                                    data-search="{{ strtolower(trim($reservation->id . ' #' . $reservation->id . ' ' . ($reservation->booker_name ?? '') . ' ' . ($reservation->email ?? '') . ' ' . ($reservation->phone ?? '') . ' ' . ($reservation->status ?? '') . ($isPastArrival ? ' past overdue' : '') . ($isToday ? ' today' : '') . ' ' . ($reservation->start_slot ?? '') . ' ' . (($reservation->start_slot ?? '') === 'Nighttime' ? 'overnight' : ''))) }}"
                                    tabindex="0"
                                    role="button"
                                    aria-label="View reservation details for {{ e($reservation->booker_name) }} (#{{ $reservation->id }})"
                                >
                                    <td class="py-3.5 pl-5 pr-3 text-center align-middle border-t border-[#f0f4ef] dark:border-white/5 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-lg bg-[#e8f5e9] px-2 py-0.5 text-xs font-bold text-[#1b4332] font-mono dark:bg-[rgba(46,125,50,0.25)] dark:text-[#9ca3af]">#{{ $reservation->id }}</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-left align-middle border-t border-[#f0f4ef] dark:border-white/5">
                                        <div class="resv-booker flex items-center gap-3">
                                            <span class="resv-avatar flex h-9 w-9 shrink-0 select-none items-center justify-center rounded-full bg-[#183d28] text-[0.78rem] font-bold uppercase tracking-[0.03em] text-white dark:bg-[#2e7d55]">{{ $initials }}</span>
                                            <div class="resv-booker__info flex min-w-0 flex-col gap-0.5">
                                                <div class="guest-name font-bold text-sm text-[#183d28] dark:text-[#e8f5e9] flex items-center gap-1.5 flex-wrap">
                                                    <span>{{ $reservation->booker_name }}</span>
                                                    @if ($isToday)
                                                        <span class="today-reservation-badge inline-block rounded-md bg-[#ff9800] px-2 py-0.5 text-[0.65rem] font-bold tracking-wide text-white dark:bg-[#ffb74d]">TODAY</span>
                                                    @elseif ($isPastArrival)
                                                        <span class="past-reservation-badge inline-flex items-center gap-1 rounded-md bg-[#ef4444] px-2 py-0.5 text-[0.65rem] font-bold tracking-wide text-white shadow-sm dark:bg-[#dc2626]" title="Arrival date was {{ $resDateObj->format('M j, Y') }} ({{ $daysOverdue }} {{ $daysOverdue === 1 ? 'day' : 'days' }} overdue)">
                                                            <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                                                            PAST ARRIVAL ({{ $daysOverdue }}d ago)
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="guest-meta text-xs text-[#718076] dark:text-[#9baaa1] truncate">{{ $reservation->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-left align-middle border-t border-[#f0f4ef] dark:border-white/5">
                                        @if ($isMultiDay)
                                            <div>
                                                <span class="font-bold text-xs sm:text-sm {{ $isPastArrival ? 'text-[#dc2626] dark:text-[#f87171]' : 'text-[#183d28] dark:text-[#e8f5e9]' }}">{{ \Carbon\Carbon::parse($reservation->reservation_date)->format('M j, Y') }} – {{ \Carbon\Carbon::parse($reservation->end_date)->format('M j, Y') }}</span>
                                                <div class="text-[0.7rem] text-[#718076] dark:text-[#9baaa1]">({{ $totalDays }} Days Stay)</div>
                                                @if ($isPastArrival)
                                                    <div class="text-[0.68rem] font-semibold text-[#dc2626] dark:text-[#f87171] mt-0.5"><i class="bi bi-exclamation-triangle-fill me-1"></i>Overdue Arrival</div>
                                                @endif
                                            </div>
                                        @else
                                            <div>
                                                <span class="font-bold text-xs sm:text-sm {{ $isPastArrival ? 'text-[#dc2626] dark:text-[#f87171]' : 'text-[#183d28] dark:text-[#e8f5e9]' }}">{{ \Carbon\Carbon::parse($reservation->reservation_date)->format('M j, Y') }}</span>
                                                <div class="text-[0.7rem] text-[#718076] dark:text-[#9baaa1]">(1 Day Stay)</div>
                                                @if ($isPastArrival)
                                                    <div class="text-[0.68rem] font-semibold text-[#dc2626] dark:text-[#f87171] mt-0.5"><i class="bi bi-exclamation-triangle-fill me-1"></i>Overdue Arrival</div>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-3 text-center align-middle border-t border-[#f0f4ef] dark:border-white/5">
                                        @if ($isMultiDay)
                                            <span class="inline-flex items-center gap-1 rounded-lg border border-teal-200 bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700 dark:border-teal-800/40 dark:bg-teal-950/40 dark:text-teal-300 whitespace-nowrap">
                                                <i class="bi bi-calendar-range text-[0.7rem] text-teal-600 dark:text-teal-400"></i>
                                                Continuous Stay ({{ $totalDays }}D)
                                            </span>
                                        @elseif (!empty($timeSlots))
                                            <div class="time-slot-labels flex flex-wrap items-center justify-center gap-1.5">
                                                @foreach ($timeSlots as $slot)
                                                    @php
                                                        $isNight = in_array(strtolower($slot), ['nighttime', 'overnight']);
                                                    @endphp
                                                    @if ($isNight)
                                                        <span class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:border-indigo-800/40 dark:bg-indigo-950/40 dark:text-indigo-300 whitespace-nowrap">
                                                            <i class="bi bi-moon-stars-fill text-[0.7rem] text-indigo-500 dark:text-indigo-400"></i>
                                                            Overnight
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:border-amber-800/40 dark:bg-amber-950/40 dark:text-amber-300 whitespace-nowrap">
                                                            <i class="bi bi-sun-fill text-[0.7rem] text-amber-500 dark:text-amber-400"></i>
                                                            {{ str_ireplace('nighttime', 'Overnight', $slot) }}
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            @php
                                                $startSlot = $reservation->start_slot ?? 'Daytime';
                                                $isNight = in_array(strtolower($startSlot), ['nighttime', 'overnight']);
                                            @endphp
                                            @if ($isNight)
                                                <span class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:border-indigo-800/40 dark:bg-indigo-950/40 dark:text-indigo-300 whitespace-nowrap">
                                                    <i class="bi bi-moon-stars-fill text-[0.7rem] text-indigo-500 dark:text-indigo-400"></i>
                                                    Overnight
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:border-amber-800/40 dark:bg-amber-950/40 dark:text-amber-300 whitespace-nowrap">
                                                    <i class="bi bi-sun-fill text-[0.7rem] text-amber-500 dark:text-amber-400"></i>
                                                    {{ str_ireplace('nighttime', 'Overnight', $startSlot) }}
                                                </span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-3 text-center align-middle border-t border-[#f0f4ef] dark:border-white/5">
                                        @php
                                            $status = ucfirst(strtolower($reservation->status));
                                            $statusClass = match($status) {
                                                'Pending' => 'bg-[#fff3e0] text-[#e65100] border-[#ffe0b2] dark:bg-[rgba(230,81,0,0.2)] dark:text-[#ffb74d] dark:border-[#ff9800]/30',
                                                'Confirmed' => 'bg-[#e8f5e9] text-[#2e7d32] border-[#c8e6c9] dark:bg-[rgba(46,125,50,0.2)] dark:text-[#9ca3af] dark:border-[#9ca3af]/30',
                                                'Checked In', 'Checked in' => 'bg-[#e3f2fd] text-[#1565c0] border-[#bbdefb] dark:bg-[rgba(21,101,192,0.2)] dark:text-[#64b5f6] dark:border-[#64b5f6]/30',
                                                'Checked Out', 'Checked out' => 'bg-[#ede7f6] text-[#6a1b9a] border-[#d1c4e9] dark:bg-[rgba(106,27,154,0.2)] dark:text-[#ce93d8] dark:border-[#ce93d8]/30',
                                                'Cancelled' => 'bg-[#ffebee] text-[#c62828] border-[#ffcdd2] dark:bg-[rgba(198,40,40,0.2)] dark:text-[#ef5350] dark:border-[#ef5350]/30',
                                                'No Show', 'No show' => 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800/50 dark:text-slate-300 dark:border-slate-700',
                                                default => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-white/10 dark:text-gray-300'
                                            };
                                        @endphp
                                        <span class="reservation-status inline-flex items-center justify-center rounded-full border px-3 py-0.5 text-xs font-bold capitalize {{ $statusClass }}">{{ $reservation->status }}</span>
                                    </td>
                                    <td class="py-3.5 pl-4 pr-6 sm:pr-8 text-right align-middle border-t border-[#f0f4ef] dark:border-white/5 font-bold text-sm text-[#183d28] dark:text-[#e8f5e9]">₱{{ number_format($reservation->total_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="guest-empty px-4 py-8 text-center text-sm text-[#718076] dark:text-[#9baaa1]">No pending online reservations found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </main>
        </div>
    </div>

    <!-- Modals (Direct children of body) -->
    <!-- Modals (Direct children of body) -->
    <div class="guest-modal fixed inset-0 z-[1000] hidden items-center justify-center is-open:flex" id="reservationModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/60 dark:bg-black/80 backdrop-blur-xs" data-close-reservation-modal="true"></div>
        <div class="relative z-[1] w-full max-w-[1020px] max-h-[92vh] flex flex-col overflow-hidden rounded-3xl bg-hp-cream dark:bg-[rgba(26,30,28,0.98)] border border-glass-border shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="reservationModalTitle">
            <div class="guest-modal__header shrink-0 flex items-center justify-between gap-3 px-6 py-3 border-b border-[rgba(13,44,29,0.1)] dark:border-white/10 bg-white/40 dark:bg-white/[0.02] !mb-0" style="margin-bottom: 0 !important;">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-hp-green/15 text-hp-green">
                        <i class="bi bi-info-circle-fill text-lg"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 id="reservationModalTitle" class="guest-modal__title m-0 font-display text-xl font-bold text-hp-text dark:text-[#f3f4f6]">Reservation Details</h3>
                            <span id="reservationModalIdBadge" class="inline-flex items-center rounded-md bg-[#e8f5e9] px-2.5 py-0.5 text-xs font-bold text-[#1b4332] font-mono dark:bg-emerald-950/60 dark:text-emerald-300">#9</span>
                        </div>
                        <p class="m-0 text-xs text-hp-text-muted mt-0.5">Overview of customer booking, reserved amenities, and stay schedule.</p>
                    </div>
                </div>
                <div class="guest-modal__header-actions flex items-center gap-2.5">
                    <span id="reservationModalStatus" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold bg-[#e8f5e9] text-[#1b4332] border border-[#c8e6c9]/60 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40"></span>
                    <!-- Sleek Sticky 'X' Close Button -->
                    <button type="button" class="group cursor-pointer w-8 h-8 shrink-0 rounded-full border border-gray-300/80 bg-white/80 hover:bg-red-50 hover:border-red-300 text-gray-500 hover:text-red-600 dark:border-white/15 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-red-950/40 dark:hover:border-red-800/60 dark:hover:text-red-400 flex items-center justify-center transition-all duration-200 shadow-xs hover:scale-105 active:scale-95" data-close-reservation-modal="true" aria-label="Close reservation details" style="position: static !important;">
                        <i class="bi bi-x-lg text-xs font-bold transition-transform duration-200 group-hover:rotate-90"></i>
                    </button>
                </div>
            </div>
            <div id="reservationModalBody" class="flex-1 min-h-0 overflow-y-auto custom-scrollbar px-6 sm:px-8 py-5"></div>
            <!-- Sticky Action Footer with Left Status actions (Cancelled/No-Show) and Right Confirm/Close -->
            <div id="reservationModalFooter" class="shrink-0 sticky bottom-0 z-10 flex items-center justify-between gap-3 px-6 sm:px-8 py-3.5 border-t border-[rgba(13,44,29,0.1)] dark:border-white/10 bg-white/85 dark:bg-[#1a1e1c]/95 backdrop-blur-md flex-wrap sm:flex-nowrap"></div>
        </div>
    </div>

    <div class="guest-modal guest-modal--calendar fixed inset-0 z-[1250] hidden items-center justify-center is-open:flex" style="z-index: 1250 !important;" id="editCalendarModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/50 dark:bg-black/75" data-close-edit-calendar="true"></div>
        <div class="guest-modal__content guest-modal__content--range relative z-[1] w-full max-w-[620px] max-h-[92vh] flex flex-col rounded-2xl bg-glass p-5 shadow-glass dark:bg-[rgba(30,30,30,0.96)]" role="dialog" aria-modal="true" aria-labelledby="editCalendarModalTitle">
            <div class="guest-modal__header mb-2.5 flex items-center justify-between border-b border-[rgba(13,44,29,0.1)] pb-2.5 dark:border-white/10">
                <div class="min-w-0">
                    <h3 id="editCalendarModalTitle" class="guest-modal__title m-0 font-display text-base sm:text-lg text-hp-text">Reschedule Stay</h3>
                    <p class="m-0 text-xs text-hp-text-muted">Select continuous stay dates (up to 5 years ahead)</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="edit-calendar__modal-date whitespace-nowrap rounded-full border border-glass-border bg-[rgba(200,164,93,0.12)] px-2.5 py-0.5 text-xs font-semibold text-[#8a7a4d] dark:border-[rgba(200,164,93,0.35)] dark:bg-[rgba(200,164,93,0.14)] dark:text-[#c8a45d]" id="editCalModalCurrent"></span>
                    <button type="button" class="cursor-pointer w-7 h-7 rounded-full border border-gray-300/80 bg-white/80 hover:bg-red-50 hover:border-red-300 text-gray-500 hover:text-red-600 dark:border-white/15 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-red-950/40 dark:hover:border-red-800/60 dark:hover:text-red-400 flex items-center justify-center transition-all duration-150 shadow-xs" data-close-edit-calendar="true" aria-label="Close calendar">
                        <i class="bi bi-x-lg text-[0.65rem] font-bold"></i>
                    </button>
                </div>
            </div>

            <!-- Check-in & Check-out Clear Summary Bar -->
            <div class="mb-2.5 rounded-xl border border-glass-border bg-glass p-2.5 dark:border-white/10 dark:bg-white/5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <!-- Check-In Box -->
                    <div class="flex items-center gap-2.5 rounded-lg border border-emerald-500/25 bg-emerald-500/10 px-3 py-2 text-xs">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white font-black text-[0.7rem] shadow-xs">IN</div>
                        <div class="min-w-0">
                            <div class="text-[0.62rem] font-bold uppercase tracking-wider text-hp-text-muted">Check-In Session</div>
                            <div class="font-black text-emerald-800 dark:text-emerald-300 text-xs sm:text-[0.82rem] truncate" id="editTopCheckInText">
                                —
                            </div>
                        </div>
                    </div>

                    <!-- Check-Out Box (States the whole date & time) -->
                    <div class="flex items-center gap-2.5 rounded-lg border-2 border-emerald-500/40 bg-emerald-500/15 px-3 py-2 text-xs shadow-xs">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-hp-green text-white shadow-xs">
                            <i class="bi bi-box-arrow-right text-sm"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[0.62rem] font-black uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
                                Check-Out Date & Time
                            </div>
                            <div class="font-black text-hp-text dark:text-white text-xs sm:text-[0.85rem] tracking-tight truncate" id="editTopCheckoutFullText">
                                —
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Session Pickers -->
                <div class="mt-2 pt-2 border-t border-glass-border dark:border-white/10 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <span class="text-[0.65rem] font-bold text-hp-text-muted block mb-1">Check-In Session:</span>
                        <div class="grid grid-cols-2 gap-1.5" id="editStartSlotGroup">
                            <button type="button" class="session-pill-btn flex items-center justify-center gap-1 py-1 px-2 text-xs" data-slot-type="start" data-slot-val="Daytime">
                                <i class="bi bi-sun-fill text-[0.75rem] text-amber-500"></i>
                                <span>Daytime (8 AM)</span>
                            </button>
                            <button type="button" class="session-pill-btn flex items-center justify-center gap-1 py-1 px-2 text-xs" data-slot-type="start" data-slot-val="Nighttime">
                                <i class="bi bi-moon-stars-fill text-[0.75rem] text-indigo-400"></i>
                                <span>Overnight (5 PM)</span>
                            </button>
                        </div>
                    </div>
                    <div>
                        <span class="text-[0.65rem] font-bold text-hp-text-muted block mb-1">Check-Out Session:</span>
                        <div class="grid grid-cols-2 gap-1.5" id="editEndSlotGroup">
                            <button type="button" class="session-pill-btn flex items-center justify-center gap-1 py-1 px-2 text-xs" data-slot-type="end" data-slot-val="Daytime">
                                <i class="bi bi-sun-fill text-[0.75rem] text-amber-500"></i>
                                <span>Daytime (5 PM)</span>
                            </button>
                            <button type="button" class="session-pill-btn flex items-center justify-center gap-1 py-1 px-2 text-xs" data-slot-type="end" data-slot-val="Nighttime">
                                <i class="bi bi-moon-stars-fill text-[0.75rem] text-indigo-400"></i>
                                <span>Overnight (8 AM)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar Component -->
            <div class="edit-calendar edit-calendar--modal rounded-xl border border-glass-border bg-hp-cream p-3 transition-colors duration-300 dark:bg-white/5 dark:border-white/10">
                <div class="edit-calendar__head mb-1.5 flex items-center justify-between gap-2">
                    <button type="button" class="edit-calendar__nav inline-flex h-7 w-7 cursor-pointer items-center justify-center rounded-lg border border-glass-border bg-glass text-base leading-none text-hp-text transition-all duration-200 hover:border-hp-green hover:bg-hp-green/10 dark:border-white/12 dark:bg-white/6 dark:text-[#f3f4f6]" id="editCalPrev" aria-label="Previous month">&lsaquo;</button>
                    <div class="edit-calendar__title-wrap flex min-w-0 items-baseline gap-2">
                        <div class="edit-calendar__title text-sm font-bold capitalize text-hp-text dark:text-[#f3f4f6]" id="editCalTitle">&mdash;</div>
                        <select class="edit-calendar__year cursor-pointer rounded-md border border-glass-border bg-glass px-2 py-0.5 text-xs font-bold text-hp-text transition-all duration-200 hover:border-hp-green focus:border-hp-green focus:outline-none dark:border-white/12 dark:bg-white/6 dark:text-[#f3f4f6]" id="editCalYear" aria-label="Select year"></select>
                    </div>
                    <button type="button" class="edit-calendar__nav inline-flex h-7 w-7 cursor-pointer items-center justify-center rounded-lg border border-glass-border bg-glass text-base leading-none text-hp-text transition-all duration-200 hover:border-hp-green hover:bg-hp-green/10 dark:border-white/12 dark:bg-white/6 dark:text-[#f3f4f6]" id="editCalNext" aria-label="Next month">&rsaquo;</button>
                </div>

                <div class="edit-calendar__weekdays mt-1 grid grid-cols-7 gap-1">
                    <span class="text-center text-[0.68rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Su</span><span class="text-center text-[0.68rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Mo</span><span class="text-center text-[0.68rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Tu</span><span class="text-center text-[0.68rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">We</span><span class="text-center text-[0.68rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Th</span><span class="text-center text-[0.68rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Fr</span><span class="text-center text-[0.68rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Sa</span>
                </div>

                <div class="edit-calendar__grid relative mt-1 grid min-h-[170px] grid-cols-7 gap-1 transition-opacity duration-250" id="editCalGrid"></div>

                <div class="mt-2 flex flex-wrap items-center justify-between gap-2 border-t border-glass-border pt-1.5 text-[0.68rem] text-hp-text-muted dark:border-white/10">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-hp-green"></span> Selected Range</span>
                        <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-[rgba(13,44,29,0.2)] dark:bg-white/20"></span> Unavailable</span>
                    </div>
                    <span id="editCalStepHelp" class="font-semibold text-hp-green dark:text-emerald-400">Click date to set check-in</span>
                </div>
            </div>

            <!-- Unified Single Action Footer with Prominent Check-Out Date & Time -->
            <div class="mt-2.5 flex items-center justify-between gap-3 border-t border-glass-border pt-2 dark:border-white/10">
                <div class="min-w-0">
                    <div class="text-xs font-black text-hp-text dark:text-white truncate" id="editCalCheckoutDateText">
                        —
                    </div>
                    <div class="text-[0.68rem] text-hp-text-muted mt-0.5 truncate" id="editCalSummaryScheduleText">
                        Check-In: <span id="editCalCheckInText">—</span>
                    </div>
                    <div class="text-[0.68rem] font-bold text-emerald-700 dark:text-emerald-400 mt-0.5" id="editCalCostSummary">
                        ₱0.00
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <div id="editCalSummaryText" class="hidden"></div>
                    <div id="editTopCheckoutBadge" class="hidden"></div>
                    <div id="editCalDaysBadgeText" class="hidden"></div>
                    <button type="button" class="cursor-pointer rounded-xl border border-glass-border bg-glass px-3.5 py-1.5 text-xs font-semibold text-hp-text hover:bg-glass-hover" data-close-edit-calendar="true">Cancel</button>
                    <button type="button" class="cursor-pointer rounded-xl border-0 bg-hp-green px-4 py-1.5 text-xs font-bold text-white transition-colors duration-150 hover:bg-hp-green-dark shadow-xs whitespace-nowrap" id="editCalApplyBtn">Apply Schedule</button>
                </div>
            </div>
        </div>
    </div>

    <div class="guest-modal guest-modal--confirm fixed inset-0 z-[1400] hidden items-center justify-center is-open:flex" style="z-index: 1400 !important;" id="confirmModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/50 dark:bg-black/75 backdrop-blur-sm" data-close-confirm-modal="true"></div>
        <div class="guest-modal__content guest-modal__content--confirm relative z-[1] w-full max-w-[400px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-8 text-center shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle">
            <div class="guest-modal__confirm-icon mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full border border-rose-500/25 bg-rose-500/15 text-rose-500 dark:text-rose-400">
                <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <h3 id="confirmModalTitle" class="guest-modal__title guest-modal__title--confirm m-0 mb-3 font-display text-xl text-hp-text">Confirm Action</h3>
            <p id="confirmModalMessage" class="guest-modal__message mb-8 text-[0.95rem] leading-relaxed text-hp-text-muted">Are you sure you want to proceed?</p>
            <div class="guest-modal__actions flex justify-center gap-3">
                <button type="button" class="guest-form__secondary min-w-[100px] cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" id="confirmModalCancel">No</button>
                <button type="button" class="guest-form__button min-w-[100px] cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark" id="confirmModalConfirm">Yes</button>
            </div>
        </div>
    </div>

    <div class="guest-modal guest-modal--success fixed inset-0 z-[1500] hidden items-center justify-center is-open:flex" style="z-index: 1500 !important;" id="successModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/50 dark:bg-black/75" data-close-success-modal="true"></div>
        <div class="guest-modal__content guest-modal__content--success relative z-[1] w-full max-w-[400px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-8 text-center shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="successModalTitle">
            <div class="guest-modal__success-icon mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-[rgba(34,197,94,0.1)] text-[#22c55e] dark:bg-[rgba(34,197,94,0.2)]">
                <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 id="successModalTitle" class="guest-modal__title guest-modal__title--success m-0 mb-3 font-display text-xl text-[#22c55e]">Success</h3>
            <p id="successModalMessage" class="guest-modal__message mb-8 text-[0.95rem] leading-relaxed text-hp-text-muted">Operation completed successfully!</p>
            <div class="guest-modal__actions flex justify-center gap-3">
                <button type="button" class="guest-form__button min-w-[100px] cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark" id="successModalClose">OK</button>
            </div>
        </div>
    </div>

    <!-- RESCHEDULE REQUESTS LIST MODAL -->
    <div class="guest-modal fixed inset-0 z-[1300] hidden items-center justify-center is-open:flex" style="z-index: 1300 !important;" id="reschedRequestsModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/60 dark:bg-black/80 backdrop-blur-xs" data-close-resched-requests-modal="true"></div>
        <div class="guest-modal__content guest-modal__content--wide relative z-[1] w-full max-w-5xl max-h-[92vh] flex flex-col overflow-hidden rounded-3xl bg-hp-cream dark:bg-[rgba(26,30,28,0.98)] border border-glass-border shadow-2xl mx-3 sm:mx-6 my-auto" role="dialog" aria-modal="true" aria-labelledby="reschedRequestsModalTitle">
            <div class="shrink-0 flex items-center justify-between gap-3 px-6 py-4 border-b border-[rgba(13,44,29,0.1)] dark:border-white/10 bg-white/50 dark:bg-white/[0.02]">
                <div>
                    <div class="flex items-center gap-2">
                        <i class="bi bi-calendar2-range text-lg text-emerald-800 dark:text-emerald-400"></i>
                        <h3 id="reschedRequestsModalTitle" class="m-0 font-display text-xl font-bold text-hp-text dark:text-[#f3f4f6]">Reservation Reschedule Requests</h3>
                    </div>
                    <p class="m-0 text-xs text-hp-text-muted mt-0.5">Manage guest date change requests dispatched via single-use SMS links</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="refreshReschedRequestsBtn" class="cursor-pointer h-8 px-3 rounded-xl border border-gray-300/80 bg-white/80 hover:bg-gray-100 text-gray-700 dark:border-white/15 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-white/20 flex items-center gap-1.5 text-xs font-semibold shadow-xs transition-colors">
                        <i class="bi bi-arrow-clockwise text-xs"></i>
                        <span>Refresh</span>
                    </button>
                    <button type="button" class="group cursor-pointer w-8 h-8 rounded-full border border-gray-300/80 bg-white/80 hover:bg-red-50 hover:border-red-300 text-gray-500 hover:text-red-600 dark:border-white/15 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-red-950/40 dark:hover:border-red-800/60 dark:hover:text-red-400 flex items-center justify-center transition-all duration-200 shadow-xs hover:scale-105 active:scale-95" data-close-resched-requests-modal="true" aria-label="Close modal">
                        <i class="bi bi-x-lg text-xs font-bold transition-transform duration-200 group-hover:rotate-90"></i>
                    </button>
                </div>
            </div>

            <!-- Status Filter Tabs -->
            <div class="shrink-0 px-6 py-2.5 border-b border-[rgba(13,44,29,0.08)] dark:border-white/5 bg-white/30 dark:bg-white/[0.01] flex items-center gap-2 overflow-x-auto custom-scrollbar">
                <button type="button" class="resched-tab-btn is-active cursor-pointer px-3 py-1 rounded-xl text-xs font-bold transition-all bg-hp-green text-white shadow-xs shrink-0" data-resched-tab="all">
                    All (<span id="reschedCountAll">0</span>)
                </button>
                <button type="button" class="resched-tab-btn cursor-pointer px-3 py-1 rounded-xl text-xs font-semibold text-hp-text transition-all hover:bg-black/5 dark:hover:bg-white/10 shrink-0" data-resched-tab="submitted">
                    Awaiting Action (<span id="reschedCountSubmitted">0</span>)
                </button>
                <button type="button" class="resched-tab-btn cursor-pointer px-3 py-1 rounded-xl text-xs font-semibold text-hp-text transition-all hover:bg-black/5 dark:hover:bg-white/10 shrink-0" data-resched-tab="pending">
                    Link Sent / Pending (<span id="reschedCountPending">0</span>)
                </button>
                <button type="button" class="resched-tab-btn cursor-pointer px-3 py-1 rounded-xl text-xs font-semibold text-hp-text transition-all hover:bg-black/5 dark:hover:bg-white/10 shrink-0" data-resched-tab="approved">
                    Approved (<span id="reschedCountApproved">0</span>)
                </button>
                <button type="button" class="resched-tab-btn cursor-pointer px-3 py-1 rounded-xl text-xs font-semibold text-hp-text transition-all hover:bg-black/5 dark:hover:bg-white/10 shrink-0" data-resched-tab="declined">
                    Declined (<span id="reschedCountDeclined">0</span>)
                </button>
            </div>

            <!-- Table / Request list -->
            <div class="flex-1 min-h-[280px] overflow-y-auto custom-scrollbar p-5 sm:p-6">
                <div id="reschedRequestsList" class="space-y-3">
                    {{-- Skeleton loader (replaced by JS on load) --}}
                    @foreach(range(1,3) as $_)
                    <div class="p-4 rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-white/5 animate-pulse">
                        <div class="flex items-center gap-2.5 mb-3">
                            <div class="h-5 w-14 rounded-md bg-slate-200 dark:bg-white/10"></div>
                            <div class="h-4 w-36 rounded-md bg-slate-200 dark:bg-white/10"></div>
                            <div class="h-5 w-20 rounded-lg bg-slate-200 dark:bg-white/10 ml-auto"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div class="h-9 rounded-xl bg-slate-100 dark:bg-white/5"></div>
                            <div class="h-9 rounded-xl bg-slate-100 dark:bg-white/5"></div>
                        </div>
                        <div class="h-3 w-2/3 rounded bg-slate-100 dark:bg-white/5"></div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="shrink-0 flex items-center justify-between px-6 py-3 border-t border-[rgba(13,44,29,0.1)] dark:border-white/10 bg-white/50 dark:bg-white/[0.02]">
                <div class="text-xs text-hp-text-muted">
                    Showing <span id="reschedRequestsTotalShowing" class="font-bold text-hp-text">0</span> request(s)
                </div>
                <button type="button" class="cursor-pointer rounded-xl border border-glass-border bg-white/80 dark:bg-white/10 px-5 py-2 text-xs font-semibold text-hp-text hover:bg-white dark:hover:bg-white/15" data-close-resched-requests-modal="true">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- SEND RESCHEDULE REQUEST SMS MODAL (TRIGGERED FROM RESERVATION DETAIL) -->
    <div class="guest-modal fixed inset-0 z-[1350] hidden items-center justify-center is-open:flex" style="z-index: 1350 !important;" id="requestReschedModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/60 dark:bg-black/80 backdrop-blur-xs" data-close-request-resched-modal="true"></div>
        <div class="guest-modal__content relative z-[1] w-full max-w-[540px] max-h-[92vh] flex flex-col overflow-hidden rounded-3xl bg-white dark:bg-[#1a1e1c] border border-glass-border shadow-2xl mx-3 sm:mx-4 my-auto" role="dialog" aria-modal="true" aria-labelledby="requestReschedModalTitle">
            <div class="shrink-0 flex items-center justify-between gap-3 px-6 py-4 border-b border-[rgba(13,44,29,0.1)] dark:border-white/10 bg-hp-cream dark:bg-white/[0.02]">
                <div>
                    <h3 id="requestReschedModalTitle" class="m-0 font-display text-lg font-bold text-hp-text dark:text-[#f3f4f6]">Send Reschedule Link (SMS)</h3>
                    <p class="m-0 text-xs text-hp-text-muted mt-0.5">Send a temporary 24-hour single-use link for guest to choose a new date</p>
                </div>
                <button type="button" class="group cursor-pointer w-8 h-8 rounded-full border border-gray-300/80 bg-white/80 hover:bg-red-50 hover:border-red-300 text-gray-500 hover:text-red-600 dark:border-white/15 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-red-950/40 dark:hover:border-red-800/60 dark:hover:text-red-400 flex items-center justify-center transition-all duration-200 shadow-xs hover:scale-105 active:scale-95" data-close-request-resched-modal="true" aria-label="Close modal">
                    <i class="bi bi-x-lg text-xs font-bold transition-transform duration-200 group-hover:rotate-90"></i>
                </button>
            </div>

            <form id="sendReschedSmsForm" class="flex flex-col flex-1 min-h-0 overflow-y-auto custom-scrollbar p-6 space-y-4">
                <input type="hidden" id="sendReschedReservationId" value="">

                <!-- Booker & Reservation Card -->
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 flex items-center justify-between">
                    <div>
                        <span class="text-[0.68rem] font-bold uppercase tracking-wider text-slate-400 block">Guest / Booker</span>
                        <div class="font-bold text-slate-900 dark:text-white text-sm" id="sendReschedBookerName">—</div>
                    </div>
                    <span class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-300 font-mono font-bold text-xs" id="sendReschedIdBadge">#—</span>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10">
                        <span class="text-slate-400 block text-[0.68rem] font-medium uppercase">Current Date</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 text-xs" id="sendReschedCurrentDate">—</span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10">
                        <span class="text-slate-400 block text-[0.68rem] font-medium uppercase">Session</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 text-xs" id="sendReschedSession">—</span>
                    </div>
                </div>

                <!-- Recipient Mobile -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="sendReschedPhone" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            Recipient Mobile Number:
                        </label>
                        <span class="text-[0.68rem] text-slate-400 font-medium">Philippine Mobile (starts with 09)</span>
                    </div>
                    <div class="relative flex items-center">
                        <span class="absolute left-3 flex items-center gap-1 text-xs font-bold text-slate-500 dark:text-slate-400 select-none">
                            <i class="bi bi-phone"></i>
                        </span>
                        <input type="text" id="sendReschedPhone" maxlength="13" class="w-full pl-8 pr-3.5 py-2 text-xs font-mono font-medium rounded-xl border border-slate-200 bg-white dark:bg-white/5 dark:border-white/15 dark:text-white focus:border-emerald-600 focus:outline-none" placeholder="09XXXXXXXXX">
                    </div>
                    <p class="text-[0.68rem] text-slate-400 mt-1">
                        Must be an 11-digit Philippine mobile number starting with <strong class="text-emerald-700 dark:text-emerald-400">09</strong> (e.g. 09123456789).
                    </p>
                </div>

                <!-- Message Textarea -->
                <div>
                    <label for="sendReschedMessage" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Rescheduling Explanation / Message:
                    </label>
                    <textarea id="sendReschedMessage" rows="3" class="w-full p-3 text-xs rounded-xl border border-slate-200 bg-white dark:bg-white/5 dark:border-white/15 dark:text-white focus:border-emerald-600 focus:outline-none leading-relaxed" placeholder="Enter message for guest..."></textarea>
                    <p class="text-[0.68rem] text-slate-400 mt-1">
                        The secure link placeholder <code class="bg-slate-100 dark:bg-white/10 px-1 py-0.5 rounded text-emerald-700 dark:text-emerald-400">{link}</code> will be replaced automatically.
                    </p>
                </div>

                <!-- Prefix notification callout -->
                <div class="p-3 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/40 text-xs text-amber-900 dark:text-amber-300 flex items-start gap-2.5">
                    <i class="bi bi-info-circle-fill text-amber-600 text-sm shrink-0 mt-0.5"></i>
                    <div>
                        <strong>Automatic SMS Prefix:</strong><br>
                        The message will start with: <span class="font-mono font-bold text-slate-800 dark:text-white bg-amber-100 dark:bg-amber-900/50 px-1.5 py-0.5 rounded" id="sendReschedPrefixPreview">hi Booker of reservation_#,</span> followed by your message containing the 24-hour single-use link.
                    </div>
                </div>

                <div id="sendReschedErrorAlert" class="hidden p-3 rounded-xl bg-red-50 text-red-700 text-xs border border-red-200"></div>
            </form>

            <div class="shrink-0 flex items-center justify-end gap-3 px-6 py-3.5 border-t border-[rgba(13,44,29,0.1)] dark:border-white/10 bg-slate-50 dark:bg-white/[0.02]">
                <button type="button" class="cursor-pointer rounded-xl border border-glass-border bg-white/80 dark:bg-white/10 px-4 py-2 text-xs font-semibold text-hp-text hover:bg-white dark:hover:bg-white/15" data-close-request-resched-modal="true">
                    Cancel
                </button>
                <button type="button" id="submitSendReschedBtn" class="cursor-pointer rounded-xl border-0 bg-hp-green hover:bg-hp-green-dark px-5 py-2 text-xs font-bold text-white transition-all shadow-sm flex items-center gap-1.5 active:scale-[0.98]">
                    <i class="bi bi-send-fill text-xs"></i>
                    <span>Send Reschedule SMS</span>
                </button>
            </div>
        </div>
    </div>

    <!-- DECLINE RESCHEDULE REASON MODAL -->
    <div class="guest-modal fixed inset-0 z-[1400] hidden items-center justify-center is-open:flex" style="z-index: 1400 !important;" id="declineReschedModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/60 dark:bg-black/80 backdrop-blur-xs" data-close-decline-resched-modal="true"></div>
        <div class="guest-modal__content relative z-[1] w-full max-w-[420px] rounded-3xl bg-white dark:bg-[#1a1e1c] border border-glass-border shadow-2xl p-6 text-center mx-3 my-auto" role="dialog" aria-modal="true" aria-labelledby="declineReschedTitle">
            <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3 text-xl">
                <i class="bi bi-x-octagon"></i>
            </div>
            <h4 id="declineReschedTitle" class="font-display font-bold text-lg text-slate-900 dark:text-white mb-1">Decline Reschedule Request</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4 leading-relaxed">
                Are you sure you want to decline this reschedule request? An SMS notification will be sent to the guest.
            </p>
            <div class="text-left mb-4">
                <label for="declineReschedReason" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Optional Reason / Note:</label>
                <textarea id="declineReschedReason" rows="2" class="w-full p-2.5 text-xs rounded-xl border border-slate-200 bg-white dark:bg-white/5 dark:border-white/15 dark:text-white focus:border-rose-500 focus:outline-none" placeholder="e.g. Park fully booked on chosen date"></textarea>
            </div>
            <input type="hidden" id="declineReschedRequestId" value="">
            <div class="flex items-center justify-center gap-3">
                <button type="button" class="cursor-pointer px-4 py-2 rounded-xl border border-slate-200 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-white/10" data-close-decline-resched-modal="true">Cancel</button>
                <button type="button" id="confirmDeclineReschedBtn" class="cursor-pointer px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition-all shadow-sm">Yes, Decline</button>
            </div>
        </div>
    </div>

    <!-- CHOOSE AMENITIES MODAL (FOR CHECK-IN WITH OCCUPIED & RESERVED STATUS) -->
    <div class="guest-modal guest-modal--compact fixed inset-0 z-[1060] hidden items-center justify-center is-open:flex" style="z-index: 1060 !important;" id="checkInAmenityPickerModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/60 dark:bg-black/80 backdrop-blur-xs" data-close-checkin-amenity-picker="true"></div>
        <div class="guest-modal__content guest-modal__content--wide relative z-[1] w-full max-w-[780px] max-h-[min(90vh,820px)] flex flex-col overflow-hidden rounded-3xl bg-hp-cream dark:bg-[rgba(26,30,28,0.98)] border border-glass-border shadow-2xl p-5 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="checkInAmenityPickerTitle">
            <button type="button" class="guest-modal__close group absolute right-4 top-4 cursor-pointer w-8 h-8 rounded-full border border-gray-300/80 bg-white/80 hover:bg-red-50 hover:border-red-300 text-gray-500 hover:text-red-600 dark:border-white/15 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-red-950/40 dark:hover:border-red-800/60 dark:hover:text-red-400 flex items-center justify-center transition-all duration-200 shadow-xs hover:scale-105 active:scale-95 z-10" data-close-checkin-amenity-picker="true" aria-label="Close amenity picker">
                <i class="bi bi-x-lg text-xs font-bold transition-transform duration-200 group-hover:rotate-90"></i>
            </button>
            <div class="guest-modal__header mb-3 shrink-0 flex flex-wrap items-center justify-between gap-2 border-b border-[rgba(13,44,29,0.1)] pb-3 dark:border-white/10 pr-10">
                <div>
                    <h3 id="checkInAmenityPickerTitle" class="guest-modal__title m-0 font-display text-xl font-bold text-hp-text dark:text-[#f3f4f6]">Choose Available Amenities</h3>
                    <p class="m-0 text-xs text-hp-text-muted">Select amenities categorized by type with real-time occupancy status</p>
                </div>
                <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-700 dark:text-emerald-300" id="checkInAmenityPickerStayBadge">
                    Stay Schedule
                </div>
            </div>

            <!-- Filter Controls: Available, Occupied, Reserved, All + Category Dropdown -->
            <div class="mb-3.5 shrink-0 flex flex-wrap items-center justify-between gap-2.5 rounded-xl border border-glass-border bg-white/60 dark:bg-white/5 p-2 shadow-2xs">
                <!-- Status Filter Pills -->
                <div class="flex flex-wrap items-center gap-1.5" id="checkInAmenityStatusFilters" role="tablist">
                    <button type="button" class="checkin-amenity-filter-pill is-active cursor-pointer rounded-lg px-2.5 sm:px-3 py-1 text-xs font-bold transition-all bg-hp-green text-white shadow-xs" data-picker-status="available">
                        <span>Available</span>
                        <span class="ms-1 rounded-full bg-white/20 px-1.5 py-0.2 text-[0.65rem]" id="checkInCountAvailable">0</span>
                    </button>
                    <button type="button" class="checkin-amenity-filter-pill cursor-pointer rounded-lg px-2.5 sm:px-3 py-1 text-xs font-semibold text-hp-text transition-all bg-transparent hover:bg-black/5 dark:hover:bg-white/10" data-picker-status="occupied">
                        <span>Occupied</span>
                        <span class="ms-1 rounded-full bg-red-500/20 text-red-700 dark:text-red-300 px-1.5 py-0.2 text-[0.65rem]" id="checkInCountOccupied">0</span>
                    </button>
                    <button type="button" class="checkin-amenity-filter-pill cursor-pointer rounded-lg px-2.5 sm:px-3 py-1 text-xs font-semibold text-hp-text transition-all bg-transparent hover:bg-black/5 dark:hover:bg-white/10" data-picker-status="reserved">
                        <span>Reserved</span>
                        <span class="ms-1 rounded-full bg-amber-500/20 text-amber-700 dark:text-amber-300 px-1.5 py-0.2 text-[0.65rem]" id="checkInCountReserved">0</span>
                    </button>
                    <button type="button" class="checkin-amenity-filter-pill cursor-pointer rounded-lg px-2.5 sm:px-3 py-1 text-xs font-semibold text-hp-text transition-all bg-transparent hover:bg-black/5 dark:hover:bg-white/10" data-picker-status="all">
                        <span>All</span>
                        <span class="ms-1 rounded-full bg-black/10 px-1.5 py-0.2 text-[0.65rem] dark:bg-white/20" id="checkInCountAll">0</span>
                    </button>
                </div>

                <!-- Category Filter Dropdown -->
                <div class="flex items-center gap-2">
                    <label for="checkInAmenityCategorySelect" class="text-xs font-bold text-hp-text-muted shrink-0">Category:</label>
                    <select id="checkInAmenityCategorySelect" class="cursor-pointer rounded-lg border border-glass-border bg-white/90 px-2.5 py-1 text-xs font-semibold text-hp-text shadow-xs transition-colors focus:border-hp-green focus:outline-none dark:bg-[#2a2e2b] dark:text-[#f3f4f6]">
                        <option value="all">All Categories</option>
                    </select>
                </div>
            </div>

            <!-- Amenities List Container (Categorized & Scrollable) -->
            <div class="flex-1 min-h-0 overflow-y-auto custom-scrollbar pr-1 space-y-4" id="checkInAmenityPickerContainer">
                <div class="flex items-center justify-center py-10 text-sm text-hp-text-muted">
                    <svg class="mr-2 h-5 w-5 animate-spin text-hp-green" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    Checking amenity availability...
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="mt-3.5 shrink-0 flex items-center justify-between border-t border-[rgba(13,44,29,0.1)] pt-3 dark:border-white/10">
                <div class="text-xs text-hp-text-muted">
                    <span id="checkInAmenityPickerSummaryText">Showing available amenities</span>
                </div>
                <button type="button" class="cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2 text-xs font-bold text-white transition-colors duration-150 hover:bg-hp-green-dark shadow-xs" data-close-checkin-amenity-picker="true">Done</button>
            </div>
        </div>
    </div>

    <!-- CHECK IN RESERVATION MODAL (CLEAN SINGLE-SECTION SCROLLABLE FLOW) -->
    <div class="guest-modal guest-modal--add fixed inset-0 z-[1000] hidden items-center justify-center is-open:flex" id="checkInModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/60 dark:bg-black/80 backdrop-blur-xs" data-close-check-in-modal="true"></div>
        <div class="relative z-[1] w-full max-w-[1020px] max-h-[92vh] flex flex-col overflow-hidden rounded-3xl bg-hp-cream dark:bg-[rgba(26,30,28,0.98)] border border-glass-border shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="checkInModalTitle">
            
            <!-- Sticky Modal Header -->
            <div class="guest-modal__header shrink-0 flex items-center justify-between gap-3 px-6 py-3 border-b border-[rgba(13,44,29,0.1)] dark:border-white/10 bg-white/40 dark:bg-white/[0.02] !mb-0" style="margin-bottom: 0 !important;">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-hp-green/15 text-hp-green">
                        <i class="bi bi-box-arrow-in-right text-lg"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 id="checkInModalTitle" class="guest-modal__title m-0 font-display text-xl font-bold text-hp-text dark:text-[#f3f4f6]">Check In Reservation</h3>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-0.5 text-[0.68rem] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                                Online Reservation Check-In
                            </span>
                        </div>
                        <p class="m-0 text-xs text-hp-text-muted mt-0.5">Review booking information, guest details, and complete check-in in one simple view.</p>
                    </div>
                </div>
                <button type="button" class="group cursor-pointer w-8 h-8 shrink-0 rounded-full border border-gray-300/80 bg-white/80 hover:bg-red-50 hover:border-red-300 text-gray-500 hover:text-red-600 dark:border-white/15 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-red-950/40 dark:hover:border-red-800/60 dark:hover:text-red-400 flex items-center justify-center transition-all duration-200 shadow-xs hover:scale-105 active:scale-95" data-close-check-in-modal="true" aria-label="Close check-in form" style="position: static !important;">
                    <i class="bi bi-x-lg text-xs font-bold transition-transform duration-200 group-hover:rotate-90"></i>
                </button>
            </div>

            <!-- Main Form with Single Vertical Scroll Area -->
            <form id="checkInForm" class="guest-form !flex !flex-col !gap-0 !m-0 flex-1 min-h-0 overflow-hidden" style="display: flex !important; flex-direction: column !important; gap: 0 !important; margin: 0 !important;" action="#">
                <input type="hidden" name="check_in_guest_mode" value="with_primary">
                
                <!-- Primary Guest Hidden Form Mirror Inputs (Maintained for submission compatibility) -->
                <div id="checkInPrimaryGuestHiddenInputs" class="hidden" style="display: none !important;">
                    <input type="hidden" name="check_in_primary_guest[first_name]" id="checkInHiddenPrimaryFirstName">
                    <input type="hidden" name="check_in_primary_guest[middle_name]" id="checkInHiddenPrimaryMiddleName">
                    <input type="hidden" name="check_in_primary_guest[last_name]" id="checkInHiddenPrimaryLastName">
                    <input type="hidden" name="check_in_primary_guest[age]" id="checkInHiddenPrimaryAge">
                    <input type="hidden" name="check_in_primary_guest[gender]" id="checkInHiddenPrimaryGender">
                    <input type="hidden" name="check_in_primary_guest[is_foreigner]" id="checkInHiddenPrimaryIsForeigner" value="0">
                    <input type="hidden" name="check_in_primary_guest[phone]" id="checkInHiddenPrimaryPhone">
                    <input type="hidden" name="check_in_primary_guest[email]" id="checkInHiddenPrimaryEmail">
                    <input type="hidden" name="check_in_primary_guest[has_pool_access]" id="checkInHiddenPrimaryHasPool" value="0">
                </div>

                <!-- Hidden badges & elements kept for JS backwards compatibility -->
                <div class="hidden" style="display: none !important;" aria-hidden="true">
                    <span id="checkInSidebarAmenitiesBadge">0</span>
                    <span id="checkInSidebarCompanionsBadge">0</span>
                    <span id="checkInSidebarFeesBadge">₱0.00</span>
                    <span id="checkInMainGuestStatus"></span>
                    <span id="checkInStayCompactText"></span>
                    <span id="checkInEntranceCompactText"></span>
                    <span id="checkInPoolCompactText"></span>
                </div>

                <!-- Scrollable Body Content -->
                <div class="flex-1 min-h-0 overflow-y-auto px-6 pt-3 pb-5 space-y-3.5 custom-scrollbar" id="checkInScrollContent">

                    <!-- SECTION 1: STAY SCHEDULE & ADMISSION POLICIES (SLIM, COMPACT & ORGANIZED) -->
                    <div class="rounded-xl border border-glass-border bg-glass/40 dark:bg-white/[0.02] px-3.5 py-2.5 shadow-2xs">
                        <!-- Hidden inputs for reschedule tracking -->
                        <input type="hidden" name="check_in_reservation_date" id="checkInReservationDate">
                        <input type="hidden" name="check_in_end_date" id="checkInEndDate">
                        <input type="hidden" name="check_in_start_slot" id="checkInStartSlot" value="Daytime">
                        <input type="hidden" name="check_in_end_slot" id="checkInEndSlot" value="Daytime">

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 items-center">
                            <!-- Stay Schedule Info (5 cols) -->
                            <div class="lg:col-span-5 flex items-center justify-between gap-2 min-w-0">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-hp-green/15 text-hp-green text-xs">
                                        <i class="bi bi-calendar-check-fill"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-[0.65rem] font-bold uppercase tracking-wider text-hp-text-muted">Stay Schedule:</span>
                                            <span id="checkInScheduleSummaryText" class="font-bold text-xs text-hp-text dark:text-[#f3f4f6] truncate">
                                                Today — 1 Day
                                            </span>
                                            <span id="checkInStaySessionBadge" class="rounded-md bg-hp-green/10 border border-hp-green/20 px-1.5 py-0.2 text-[0.62rem] font-bold text-hp-green">
                                                Scheduled
                                            </span>
                                        </div>
                                        <p class="m-0 text-[0.68rem] text-hp-text-muted truncate mt-0.5" id="checkInScheduleDatesText">Check-in schedule</p>
                                    </div>
                                </div>
                                <button type="button" id="checkInRescheduleBtn" class="inline-flex items-center gap-1 rounded-lg border border-hp-green/30 bg-hp-green/10 px-2.5 py-1 text-[0.7rem] font-bold text-hp-green hover:bg-hp-green hover:text-white transition-colors cursor-pointer shrink-0 shadow-2xs" title="Reschedule stay dates or sessions">
                                    <i class="bi bi-calendar-event"></i>
                                    <span>Change</span>
                                </button>
                            </div>

                            <!-- Policies (7 cols: 2 side-by-side compact selects) -->
                            <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <!-- Entrance Fee Policy -->
                                <div class="flex flex-col gap-0.5">
                                    <label class="flex items-center gap-1 text-[0.65rem] font-bold uppercase tracking-wider text-amber-800 dark:text-amber-300" for="checkInEntranceOption">
                                        <i class="bi bi-ticket-perforated-fill text-amber-600 text-[0.65rem]"></i>
                                        <span>Entrance Policy</span>
                                    </label>
                                    <select name="entrance_option" id="checkInEntranceOption" class="w-full rounded-lg border border-glass-border bg-white dark:bg-[#232725] px-2 py-1 text-xs font-semibold text-hp-text transition-colors duration-200 focus:border-hp-green focus:outline-none dark:text-[#f3f4f6]">
                                        <option value="all_paid" selected>Standard Rate (Pay by Age)</option>
                                        <option value="all_free">Free Entrance (Promo • ₱0.00)</option>
                                    </select>
                                    <span class="hidden" id="checkInEntranceOptionHelp"></span>
                                </div>

                                <!-- Pool Access Policy -->
                                <div class="flex flex-col gap-0.5">
                                    <label class="flex items-center gap-1 text-[0.65rem] font-bold uppercase tracking-wider text-sky-800 dark:text-sky-300" for="checkInPoolOption">
                                        <i class="bi bi-water text-sky-600 text-[0.65rem]"></i>
                                        <span>Pool Policy</span>
                                    </label>
                                    <select name="pool_option" id="checkInPoolOption" class="w-full rounded-lg border border-glass-border bg-white dark:bg-[#232725] px-2 py-1 text-xs font-semibold text-hp-text transition-colors duration-200 focus:border-hp-green focus:outline-none dark:text-[#f3f4f6]">
                                        <option value="no_pool" selected>No Pool (Default • ₱0.00)</option>
                                        <option value="specific">Specific (Selected Guests)</option>
                                        <option value="all_paid">All Paid (Standard Rate)</option>
                                        <option value="all_free">All Free (Promo • ₱0.00)</option>
                                    </select>
                                    <input type="hidden" name="check_in_include_pool" id="checkInIncludePoolLegacy" value="0">
                                    <span class="hidden" id="checkInPoolOptionHelp"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- THIN SECTION DIVIDER -->
                    <div class="border-t border-gray-300/80 dark:border-white/15 my-2" role="separator"></div>

                    <!-- SECTION 2: RESERVED AMENITIES (WITH SWAP & EDIT CAPABILITY) -->
                    <div class="rounded-2xl border border-glass-border bg-glass/60 dark:bg-white/[0.02] p-4.5 space-y-3" id="checkInAmenitiesTab">
                        <div class="flex items-center justify-between gap-2 border-b border-glass-border pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-hp-green/15 text-hp-green text-sm">
                                    <i class="bi bi-house-door-fill"></i>
                                </div>
                                <div>
                                    <h4 class="m-0 text-sm font-bold text-hp-text dark:text-[#f3f4f6]">Reserved Amenities</h4>
                                    <p class="m-0 text-[0.68rem] text-hp-text-muted">Review reserved amenities or add additional units</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" id="openCheckInAddAmenityModalBtn" class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-hp-green/40 bg-hp-green/10 hover:bg-hp-green hover:text-white px-3 py-1.5 text-xs font-bold text-hp-green transition-all shadow-2xs" title="Add another room, cottage, or amenity">
                                    <i class="bi bi-plus-circle text-xs"></i>
                                    <span>+ Add Amenity</span>
                                </button>
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-hp-green bg-hp-green/10 border border-hp-green/20 rounded-full px-3 py-0.5" id="checkInAmenitiesCountBadge">0 Booked</span>
                            </div>
                        </div>
                        <div id="checkInAmenitiesContainer" class="selected-amenities-grid grid gap-2.5 max-h-[300px] overflow-y-auto pr-1"></div>
                    </div>

                    <!-- THIN SECTION DIVIDER -->
                    <div class="border-t border-gray-300/80 dark:border-white/15 my-2" role="separator"></div>

                    <!-- SECTION 3: MAIN GUEST (PRIMARY BOOKER) - INLINE FORM -->
                    <div class="rounded-2xl border border-glass-border bg-glass/60 dark:bg-white/[0.02] p-4.5 space-y-3" id="checkInGuestsTab">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-glass-border pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-600/15 text-emerald-600 dark:text-emerald-400 text-sm">
                                    <i class="bi bi-person-fill-check"></i>
                                </div>
                                <h4 class="m-0 text-sm font-bold text-hp-text dark:text-[#f3f4f6]">Main Guest (Primary Booker)</h4>
                            </div>
                            <span id="checkInMainGuestReadyBadge" class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-0.5 text-xs font-bold text-emerald-700 dark:text-emerald-400">
                                <i class="bi bi-check-circle-fill text-[0.7rem]"></i>
                                <span>Primary Guest</span>
                            </span>
                        </div>

                        <!-- Direct Inline Inputs for Main Guest -->
                        <div class="grid gap-3 pt-1">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="guest-form__field-group grid gap-1">
                                    <label class="guest-form__label text-xs font-bold text-hp-text" for="checkInMainFirstName">First name <span class="text-red-500">*</span></label>
                                    <input type="text" id="checkInMainFirstName" placeholder="Enter first name" class="w-full rounded-xl border border-glass-border bg-white dark:bg-[#232725] px-3.5 py-2 text-xs text-hp-text transition-colors duration-200 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:text-[#f3f4f6]">
                                </div>
                                <div class="guest-form__field-group grid gap-1">
                                    <label class="guest-form__label text-xs font-bold text-hp-text" for="checkInMainMiddleName">Middle name <span class="text-hp-text-muted font-normal text-[0.68rem]">(Optional)</span></label>
                                    <input type="text" id="checkInMainMiddleName" placeholder="Optional" class="w-full rounded-xl border border-glass-border bg-white dark:bg-[#232725] px-3.5 py-2 text-xs text-hp-text transition-colors duration-200 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:text-[#f3f4f6]">
                                </div>
                                <div class="guest-form__field-group grid gap-1">
                                    <label class="guest-form__label text-xs font-bold text-hp-text" for="checkInMainLastName">Last name <span class="text-red-500">*</span></label>
                                    <input type="text" id="checkInMainLastName" placeholder="Enter last name" class="w-full rounded-xl border border-glass-border bg-white dark:bg-[#232725] px-3.5 py-2 text-xs text-hp-text transition-colors duration-200 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:text-[#f3f4f6]">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="guest-form__field-group grid gap-1">
                                    <div class="flex items-center justify-between">
                                        <label class="guest-form__label text-xs font-bold text-hp-text" for="checkInMainAge">Age <span class="text-red-500">*</span></label>
                                        <span id="checkInMainAgeBadge" class="hidden rounded px-1.5 py-0.2 text-[0.65rem] font-bold text-emerald-700 bg-emerald-500/10 dark:text-emerald-300">Adult Rate</span>
                                    </div>
                                    <input type="number" id="checkInMainAge" min="0" placeholder="e.g. 25" class="w-full rounded-xl border border-glass-border bg-white dark:bg-[#232725] px-3.5 py-2 text-xs text-hp-text transition-colors duration-200 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:text-[#f3f4f6]">
                                </div>
                                <div class="guest-form__field-group grid gap-1">
                                    <label class="guest-form__label text-xs font-bold text-hp-text" for="checkInMainGender">Gender <span class="text-red-500">*</span></label>
                                    <select id="checkInMainGender" class="w-full rounded-xl border border-glass-border bg-white dark:bg-[#232725] px-3.5 py-2 text-xs text-hp-text transition-colors duration-200 focus:border-hp-green focus:outline-none dark:text-[#f3f4f6]">
                                        <option value="" disabled selected>Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="guest-form__field-group grid gap-1">
                                    <label class="guest-form__label text-xs font-bold text-hp-text" for="checkInMainIsForeigner">Nationality <span class="text-red-500">*</span></label>
                                    <select id="checkInMainIsForeigner" class="w-full rounded-xl border border-glass-border bg-white dark:bg-[#232725] px-3.5 py-2 text-xs text-hp-text transition-colors duration-200 focus:border-hp-green focus:outline-none dark:text-[#f3f4f6]">
                                        <option value="0" selected>Filipino</option>
                                        <option value="1">Foreigner</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="guest-form__field-group grid gap-1">
                                    <label class="guest-form__label text-xs font-bold text-hp-text" for="checkInMainPhone">Phone Number</label>
                                    <input type="text" id="checkInMainPhone" placeholder="09xxxxxxxxx" class="w-full rounded-xl border border-glass-border bg-white dark:bg-[#232725] px-3.5 py-2 text-xs text-hp-text transition-colors duration-200 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:text-[#f3f4f6]">
                                </div>
                                <div class="guest-form__field-group grid gap-1">
                                    <label class="guest-form__label text-xs font-bold text-hp-text" for="checkInMainEmail">Email Address</label>
                                    <input type="email" id="checkInMainEmail" placeholder="example@email.com" class="w-full rounded-xl border border-glass-border bg-white dark:bg-[#232725] px-3.5 py-2 text-xs text-hp-text transition-colors duration-200 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:text-[#f3f4f6]">
                                </div>
                            </div>

                            <div id="checkInMainPoolAccessRow" class="mt-1 flex items-center justify-between rounded-xl border border-sky-500/30 bg-sky-500/10 p-3">
                                <div class="flex items-center gap-2">
                                    <i class="bi bi-water text-base text-sky-600 dark:text-sky-400"></i>
                                    <div>
                                        <p class="m-0 text-xs font-bold text-sky-900 dark:text-sky-200">Main Guest Pool Access</p>
                                        <p class="m-0 text-[0.72rem] text-sky-700/80 dark:text-sky-300/80">Grant pool access pass for the primary guest under specific pool policy</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input type="checkbox" id="checkInPrimaryGuestHasPool" value="1" class="h-4 w-4 accent-hp-green cursor-pointer">
                                </label>
                            </div>

                            <!-- Inline Validation Error -->
                            <div id="checkInMainGuestModalError" class="hidden rounded-xl border border-red-300 bg-red-50 p-2.5 text-xs font-semibold text-red-600 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400">
                                Please fill up all required fields for the main guest (first name, last name, age, gender).
                            </div>
                        </div>
                    </div>

                    <!-- THIN SECTION DIVIDER -->
                    <div class="border-t border-gray-300/80 dark:border-white/15 my-2" role="separator"></div>

                    <!-- SECTION 4: COMPANIONS & ADDITIONAL GUESTS -->
                    <div class="rounded-2xl border border-glass-border bg-glass/60 dark:bg-white/[0.02] p-4.5 space-y-3" id="checkInCompanionSection">
                        <div class="guest-form__section-header flex flex-wrap items-center justify-between gap-2 border-b border-glass-border pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-hp-green/15 text-hp-green text-sm">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <div class="flex items-center gap-2">
                                    <h4 class="m-0 text-sm font-bold text-hp-text dark:text-[#f3f4f6]">Companions & Group Members</h4>
                                    <span id="checkInCompanionCountBadge" class="rounded-full bg-hp-green/10 border border-hp-green/20 px-2.5 py-0.5 text-xs font-bold text-hp-green">0 companions</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" id="toggleCheckInCompanionFilterBtn" class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-glass-border bg-glass px-3 py-1.5 text-xs font-semibold text-hp-text hover:bg-glass-hover transition-colors" title="Toggle Search & Filters">
                                    <i class="bi bi-funnel text-xs text-hp-green"></i>
                                    <span>Filter</span>
                                </button>
                                <button type="button" class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-emerald-600/30 bg-hp-green px-4 py-1.5 text-xs font-bold text-white shadow-sm transition-all duration-200 hover:bg-hp-green-dark hover:shadow active:scale-[0.98]" id="checkInAddCompanionBtn">
                                    <i class="bi bi-person-plus-fill text-xs"></i>
                                    <span>+ Add Companions</span>
                                </button>
                            </div>
                        </div>

                        <!-- Filter toolbar -->
                        <div id="checkInCompanionFilterToolbar" class="hidden flex-wrap items-center gap-2 rounded-xl border border-glass-border/70 bg-glass/70 p-2.5 transition-all animate-fade-in">
                            <div class="relative flex-1 min-w-[170px]">
                                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-hp-text-muted/70"></i>
                                <input type="text" id="checkInCompanionSearchInput" placeholder="Search companion name..." class="w-full rounded-xl border border-glass-border bg-white dark:bg-[#232725] py-1.5 pl-8 pr-3 text-xs text-hp-text placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:text-[#f3f4f6]">
                            </div>
                            <div class="w-auto min-w-[110px]">
                                <select id="checkInCompanionFilterGender" class="w-full rounded-xl border border-glass-border bg-white dark:bg-[#232725] px-2.5 py-1.5 text-xs font-medium text-hp-text focus:border-hp-green focus:outline-none dark:text-[#f3f4f6]">
                                    <option value="">All Genders</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="w-auto min-w-[125px]">
                                <select id="checkInCompanionFilterAgeGroup" class="w-full rounded-xl border border-glass-border bg-white dark:bg-[#232725] px-2.5 py-1.5 text-xs font-medium text-hp-text focus:border-hp-green focus:outline-none dark:text-[#f3f4f6]">
                                    <option value="">All Age Groups</option>
                                    <option value="0-12">Kids (0-12)</option>
                                    <option value="13-17">Teens (13-17)</option>
                                    <option value="18-59">Adults (18-59)</option>
                                    <option value="60+">Seniors (60+)</option>
                                </select>
                            </div>
                            <button type="button" id="checkInCompanionFilterResetBtn" class="hidden items-center gap-1 rounded-xl border border-glass-border bg-glass px-2.5 py-1.5 text-xs font-semibold text-hp-text-muted hover:text-red-500 hover:border-red-500/30 transition-colors cursor-pointer" title="Reset filters">
                                <i class="bi bi-x-circle"></i>
                                <span>Reset</span>
                            </button>
                        </div>

                        <div id="checkInCompanionList" class="guest-companion-list grid gap-2 max-h-[260px] overflow-y-auto overflow-x-hidden pr-1"></div>
                        <div id="checkInCompanionHiddenFields"></div>
                    </div>

                    <!-- THIN SECTION DIVIDER -->
                    <div class="border-t border-gray-300/80 dark:border-white/15 my-2" role="separator"></div>

                    <!-- SECTION 5: FEES & PAYMENT BREAKDOWN -->
                    <div class="rounded-2xl border border-glass-border bg-glass/60 dark:bg-white/[0.02] p-4.5 space-y-4" id="checkInFeesTab">
                        <div class="flex items-center justify-between border-b border-glass-border pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-hp-green/15 text-hp-green text-sm">
                                    <i class="bi bi-receipt"></i>
                                </div>
                                <h4 class="m-0 text-sm font-bold text-hp-text dark:text-[#f3f4f6]">Fees & Payment Summary</h4>
                            </div>
                            <span id="checkInEffectivePeriodBadge" class="inline-flex items-center rounded-full border border-glass-border bg-[rgba(255,152,0,0.15)] px-2.5 py-0.5 text-[0.68rem] font-bold uppercase tracking-[0.06em] text-[#e65100] dark:bg-[rgba(255,152,0,0.2)] dark:text-[#ffb74d]">—</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Entrance & Pool Breakdown -->
                            <div class="rounded-xl border border-glass-border bg-white/70 dark:bg-[#202422] p-4">
                                <div class="text-xs font-bold text-hp-text mb-2.5 flex items-center gap-1.5">
                                    <i class="bi bi-ticket-detailed text-hp-green"></i> Entrance & Pool Fees
                                </div>
                                <div class="flex flex-col gap-2 text-xs text-hp-text-muted">
                                    <div class="flex justify-between">
                                        <span>Adults Entrance:</span>
                                        <strong class="text-hp-text dark:text-gray-200" id="checkInAdultSummary">0 × ₱0.00</strong>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Children Entrance:</span>
                                        <strong class="text-hp-text dark:text-gray-200" id="checkInChildSummary">0 × ₱0.00</strong>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Pool Fee:</span>
                                        <strong class="text-hp-text dark:text-gray-200" id="checkInPoolSummary">₱0.00</strong>
                                    </div>
                                    <div class="flex justify-between border-t border-glass-border pt-2 font-bold">
                                        <span class="text-hp-text dark:text-gray-100">Entrance Subtotal:</span>
                                        <strong class="text-hp-green dark:text-emerald-400" id="checkInEntranceTotal">₱0.00</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Online Reservation & Extras Breakdown -->
                            <div class="rounded-xl border border-glass-border bg-white/70 dark:bg-[#202422] p-4 flex flex-col justify-between">
                                <div>
                                    <div class="text-xs font-bold text-hp-text mb-2.5 flex items-center gap-1.5">
                                        <i class="bi bi-shield-check text-hp-green"></i> Online Booking & Extras
                                    </div>
                                    <div class="flex flex-col gap-2 text-xs text-hp-text-muted">
                                        <div class="flex justify-between">
                                            <span>Booking Amount:</span>
                                            <strong class="text-hp-text dark:text-gray-200" id="checkInBookingTotalText">₱0.00</strong>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Online Paid Deposit:</span>
                                            <strong class="text-hp-green dark:text-emerald-400" id="checkInBookingPaidText">₱0.00</strong>
                                        </div>
                                        <div class="flex justify-between font-semibold">
                                            <span>Remaining Balance:</span>
                                            <strong class="text-[#e65100] dark:text-[#ffb74d]" id="checkInReservationBalance">₱0.00</strong>
                                        </div>
                                        <div id="checkInExtraHeadCard" class="flex justify-between border-t border-glass-border pt-2">
                                            <span>Extra Guest Fee:</span>
                                            <strong class="text-[#e65100] dark:text-[#ffb74d]" id="checkInExtraHeadTotal">₱0.00</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 rounded-lg bg-hp-green/5 p-2 text-[0.68rem] text-hp-text-muted">
                                    Online remaining balance and admission fees are consolidated into total to pay.
                                </div>
                            </div>
                        </div>

                        <!-- Highlighted Total Amount Callout Card -->
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-hp-green/30 bg-hp-green/10 p-4 dark:bg-hp-green/5">
                            <div>
                                <div class="text-[0.7rem] font-bold uppercase tracking-wider text-hp-text-muted">Total Amount to Pay at Check-In</div>
                                <div class="text-2xl sm:text-3xl font-black text-hp-green dark:text-emerald-400" id="checkInGrandTotal">₱0.00</div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-700 dark:text-emerald-300">
                                    <i class="bi bi-cash-stack"></i>
                                    <span>Consolidated Payment</span>
                                </span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Sticky Bottom Action Footer -->
                <div class="shrink-0 flex items-center justify-between gap-4 px-6 py-3.5 border-t border-[rgba(13,44,29,0.1)] dark:border-white/10 bg-hp-cream/95 dark:bg-[rgba(26,30,28,0.98)] backdrop-blur-sm z-20">
                    <div class="flex items-baseline gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-hp-text-muted">Total to pay:</span>
                        <strong class="text-lg sm:text-xl font-black text-hp-green dark:text-emerald-400 tracking-tight" id="checkInSidebarGrandTotalText">₱0.00</strong>
                    </div>
                    <div class="flex items-center gap-2.5">
                        <button type="button" class="cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-xs font-semibold text-hp-text hover:bg-glass-hover transition-colors" data-close-check-in-modal="true">
                            Cancel
                        </button>
                        <button type="submit" class="w-auto cursor-pointer rounded-xl border-0 bg-hp-green py-2.5 px-5 text-xs font-bold text-white transition-all duration-200 hover:bg-hp-green-dark shadow-md hover:shadow-lg active:scale-[0.98] flex items-center justify-center gap-2" id="checkInSubmitBtn">
                            <i class="bi bi-check2-circle text-sm"></i>
                            <span>Check In Reservation</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="guest-modal guest-modal--add fixed inset-0 z-[1000] hidden items-center justify-center is-open:flex" id="scanQrModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/50 dark:bg-black/75" data-close-scan-modal="true"></div>
        <div class="guest-modal__content guest-modal__content--wide relative z-[1] flex w-full max-w-[900px] max-h-[min(84vh,760px)] flex-row overflow-y-auto rounded-2xl bg-hp-cream p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="scanQrModalTitle">
            <button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-scan-modal="true" aria-label="Close QR scanner">&times;</button>
            <div class="flex flex-1 flex-col justify-center p-6">
                <h3 id="scanQrModalTitle" class="guest-modal__title m-0 mb-6 font-display text-xl text-hp-text">Scan Reservation QR</h3>
                <p class="scan-modal__hint mb-6 text-sm leading-relaxed text-hp-text">Allow camera access and hold the reservation QR code in front of the lens.</p>
                <label class="guest-form__field mb-4 grid gap-1.5">
                    <span class="mb-1 block text-sm font-semibold text-hp-text">Camera</span>
                    <select id="qrCameraSelect" class="w-full rounded-xl border border-hp-green-dark bg-white px-3.5 py-3 text-black"></select>
                </label>
                <div class="scan-modal__status mb-6 rounded-lg bg-[rgba(26,58,31,0.1)] px-3 py-2 text-sm font-semibold text-hp-green" id="qrScannerStatus">Ready to scan</div>
                <div class="guest-form__actions mt-auto flex flex-col gap-3">
                    <button type="button" class="guest-form__button cursor-pointer rounded-lg border-0 bg-hp-green-dark px-4 py-3 font-medium text-white" id="stopQrBtn">Stop Scanner</button>
                </div>
            </div>
            <div class="flex flex-1 items-center justify-center bg-black/5 p-6 dark:bg-black/20">
                <div id="qrScanner" class="scan-modal__scanner h-[300px] w-full max-w-[400px] overflow-hidden rounded-xl bg-black"></div>
            </div>
        </div>
    </div>

    <!-- UNIFIED TWO-COLUMN COMPANION MODAL -->
    <div class="guest-modal guest-modal--wide fixed inset-0 z-[1060] hidden items-center justify-center is-open:flex" style="z-index: 1060 !important;" id="checkInCompanionModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/60 dark:bg-black/80 backdrop-blur-sm" data-close-check-in-companion-modal="true"></div>
        <div class="guest-modal__content guest-modal__content--companion-unified relative z-[1] w-full max-h-[min(92vh,860px)] overflow-y-auto rounded-3xl bg-hp-cream p-6 shadow-2xl dark:bg-[rgba(26,30,28,0.98)] border border-glass-border !w-[min(1360px,95vw)] !max-w-[1360px]" role="dialog" aria-modal="true" aria-labelledby="checkInCompanionModalTitle">
            <button type="button" class="guest-modal__close absolute right-4 top-4 cursor-pointer border-0 bg-transparent text-2xl text-hp-text hover:opacity-75 transition-opacity" data-close-check-in-companion-modal="true" aria-label="Close modal">&times;</button>
            
            <div class="guest-modal__header mb-4 flex items-center justify-between border-b border-glass-border pb-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-hp-green/15 text-hp-green">
                        <i class="bi bi-people-fill text-xl"></i>
                    </div>
                    <div>
                        <h3 id="checkInCompanionModalTitle" class="guest-modal__title m-0 font-display text-lg font-bold text-hp-text dark:text-[#f3f4f6]">Add Companions</h3>
                        <p class="m-0 text-xs text-hp-text-muted">Add companions by group and review the list before applying to check-in</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- LEFT SIDE: COMPANION CREATOR FORMS (6 cols) -->
                <div class="lg:col-span-6 flex flex-col gap-3">

                    <!-- SINGLE COMPANION FORM -->
                    <form id="checkInCompanionForm" class="guest-form--tab-content hidden" data-checkin-companion-content="single" action="#" aria-hidden="true">
                        <div class="rounded-2xl border border-glass-border bg-glass/60 dark:bg-white/5 p-4 grid gap-3">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div class="guest-form__field-group grid gap-1">
                                    <label class="guest-form__label text-xs font-semibold text-hp-text">First Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="first_name" required placeholder="First name" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3 py-2 text-xs text-hp-text transition-colors focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                                </div>
                                <div class="guest-form__field-group grid gap-1">
                                    <label class="guest-form__label text-xs font-semibold text-hp-text">Middle Name</label>
                                    <input type="text" name="middle_name" placeholder="Middle name" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3 py-2 text-xs text-hp-text transition-colors focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                                </div>
                                <div class="guest-form__field-group grid gap-1">
                                    <label class="guest-form__label text-xs font-semibold text-hp-text">Last Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="last_name" required placeholder="Last name" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3 py-2 text-xs text-hp-text transition-colors focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div class="guest-form__field-group grid gap-1">
                                    <label class="guest-form__label text-xs font-semibold text-hp-text">Age <span class="text-red-500">*</span></label>
                                    <input type="number" name="age" min="0" max="130" required placeholder="Age" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3 py-2 text-xs text-hp-text transition-colors focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                                </div>
                                <div class="guest-form__field-group grid gap-1">
                                    <label class="guest-form__label text-xs font-semibold text-hp-text">Gender <span class="text-red-500">*</span></label>
                                    <select name="gender" required class="guest-form__select w-full rounded-xl border border-glass-border bg-glass px-3 py-2 text-xs font-semibold text-hp-text transition-colors focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                                        <option value="Male" selected>Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="guest-form__field-group grid gap-1">
                                    <label class="guest-form__label text-xs font-semibold text-hp-text">Nationality <span class="text-red-500">*</span></label>
                                    <select name="is_foreigner" class="guest-form__select w-full rounded-xl border border-glass-border bg-glass px-3 py-2 text-xs font-semibold text-hp-text transition-colors focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                                        <option value="0" selected>Filipino</option>
                                        <option value="1">Foreigner</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="guest-form__field-group grid gap-1">
                                    <label class="guest-form__label text-xs font-semibold text-hp-text">Phone</label>
                                    <input type="text" name="phone" placeholder="Optional phone" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3 py-2 text-xs text-hp-text transition-colors focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                                </div>
                                <div class="guest-form__field-group grid gap-1">
                                    <label class="guest-form__label text-xs font-semibold text-hp-text">Email</label>
                                    <input type="email" name="email" placeholder="Optional email" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3 py-2 text-xs text-hp-text transition-colors focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                                </div>
                            </div>
                            <div class="guest-form__field-group hidden gap-1" id="checkInCompanionAmenityWrap">
                                <label class="guest-form__label text-xs font-semibold text-hp-text">Assign to Amenity</label>
                                <select name="amenity_id" id="checkInCompanionAmenity" class="guest-form__select w-full rounded-xl border border-glass-border bg-glass px-3 py-2 text-xs font-semibold text-hp-text transition-colors focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                                    <option value="" selected>No amenity</option>
                                </select>
                            </div>
                            <div class="flex items-center justify-between rounded-xl border border-glass-border bg-glass p-2.5" id="checkInCompanionPoolWrap">
                                <div class="flex items-center gap-2">
                                    <i class="bi bi-water text-base text-sky-600 dark:text-sky-400"></i>
                                    <div>
                                        <p class="m-0 text-xs font-bold text-sky-900 dark:text-sky-200">Include Pool Pass</p>
                                        <p class="m-0 text-[0.68rem] text-hp-text-muted">Grant pool access under specific pool policy</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input type="checkbox" name="has_pool_access" id="checkInCompanionHasPool" value="1" class="h-4 w-4 accent-hp-green cursor-pointer">
                                </label>
                            </div>
                        </div>
                        <div class="guest-form__actions flex flex-wrap justify-end pt-1">
                            <button type="submit" class="guest-form__button inline-flex items-center gap-2 cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-xs font-bold text-white transition-all duration-200 hover:bg-hp-green-dark shadow-sm active:scale-[0.98]">
                                <i class="bi bi-person-plus-fill"></i>
                                <span>Add Single Companion</span>
                            </button>
                        </div>
                    </form>

                    <!-- BULK COMPANIONS FORM -->
                    <form id="checkInBulkCompanionForm" class="guest-form--tab-content guest-form--tab-content--active grid gap-3" data-checkin-companion-content="bulk" action="#">
                        <div class="rounded-2xl border border-glass-border bg-glass/60 dark:bg-white/5 p-4 grid gap-3">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="guest-form__field-group grid gap-1">
                                    <label class="guest-form__label text-xs font-semibold text-hp-text">Gender</label>
                                    <div class="flex overflow-hidden rounded-xl border border-glass-border bg-glass">
                                        <label class="flex-1 text-center cursor-pointer">
                                            <input type="radio" name="gender" value="Male" checked class="peer sr-only">
                                            <span class="block py-2 text-xs font-semibold transition-colors peer-checked:bg-hp-green peer-checked:text-white border-r border-glass-border">Male</span>
                                        </label>
                                        <label class="flex-1 text-center cursor-pointer">
                                            <input type="radio" name="gender" value="Female" class="peer sr-only">
                                            <span class="block py-2 text-xs font-semibold transition-colors peer-checked:bg-hp-green peer-checked:text-white">Female</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="guest-form__field-group grid gap-1">
                                    <label class="guest-form__label text-xs font-semibold text-hp-text">Nationality</label>
                                    <div class="flex overflow-hidden rounded-xl border border-glass-border bg-glass">
                                        <label class="flex-1 text-center cursor-pointer">
                                            <input type="radio" name="is_foreigner" value="0" checked class="peer sr-only">
                                            <span class="block py-2 text-xs font-semibold transition-colors peer-checked:bg-hp-green peer-checked:text-white border-r border-glass-border">Filipino</span>
                                        </label>
                                        <label class="flex-1 text-center cursor-pointer">
                                            <input type="radio" name="is_foreigner" value="1" class="peer sr-only">
                                            <span class="block py-2 text-xs font-semibold transition-colors peer-checked:bg-hp-green peer-checked:text-white">Foreigner</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="guest-form__field-group grid gap-1">
                                    <label class="guest-form__label text-xs font-semibold text-hp-text">Age Group</label>
                                    <div class="grid grid-cols-2 overflow-hidden rounded-xl border border-glass-border bg-glass text-center">
                                        <label class="cursor-pointer border-b border-r border-glass-border">
                                            <input type="radio" name="age_group" value="0-12" class="peer sr-only">
                                            <span class="block py-1.5 text-xs font-semibold transition-colors peer-checked:bg-hp-green peer-checked:text-white">Kids (0-12)</span>
                                        </label>
                                        <label class="cursor-pointer border-b border-glass-border">
                                            <input type="radio" name="age_group" value="13-17" class="peer sr-only">
                                            <span class="block py-1.5 text-xs font-semibold transition-colors peer-checked:bg-hp-green peer-checked:text-white">Teens (13-17)</span>
                                        </label>
                                        <label class="cursor-pointer border-r border-glass-border">
                                            <input type="radio" name="age_group" value="18-59" checked class="peer sr-only">
                                            <span class="block py-1.5 text-xs font-semibold transition-colors peer-checked:bg-hp-green peer-checked:text-white">Adults (18-59)</span>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="age_group" value="60+" class="peer sr-only">
                                            <span class="block py-1.5 text-xs font-semibold transition-colors peer-checked:bg-hp-green peer-checked:text-white">Seniors (60+)</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="guest-form__field-group grid gap-1">
                                    <label class="guest-form__label text-xs font-semibold text-hp-text">Group Quantity</label>
                                    <div class="flex items-center gap-1.5 rounded-xl border border-glass-border bg-glass p-1">
                                        <button type="button" id="checkInBulkQtyMinusBtn" class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg border-0 bg-black/5 dark:bg-white/10 text-base font-extrabold text-hp-text transition-colors hover:bg-black/10">−</button>
                                        <input type="number" name="quantity" id="checkInBulkCompanionQuantity" value="1" min="1" max="500" class="no-spinners m-0 w-full flex-1 border-0 bg-transparent text-center font-display text-lg font-bold text-hp-green-dark dark:text-hp-green focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                        <button type="button" id="checkInBulkQtyPlusBtn" class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg border-0 bg-black/5 dark:bg-white/10 text-base font-extrabold text-hp-text transition-colors hover:bg-black/10">+</button>
                                    </div>
                                </div>
                            </div>

                            <div class="guest-form__field-group hidden gap-1" id="checkInBulkCompanionAmenityWrap">
                                <label class="guest-form__label text-xs font-semibold text-hp-text">Assign to Amenity</label>
                                <select name="amenity_id" id="checkInBulkCompanionAmenity" class="guest-form__select w-full rounded-xl border border-glass-border bg-glass px-3 py-2 text-xs font-semibold text-hp-text transition-colors focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                                    <option value="" selected>No amenity</option>
                                </select>
                            </div>

                            <div class="guest-form__field-group grid gap-1 sm:col-span-2 rounded-xl border border-glass-border bg-glass p-2.5" id="checkInBulkCompanionPoolWrap">
                                <div class="flex items-center justify-between">
                                    <label class="guest-form__label text-xs font-semibold text-hp-text flex items-center gap-1.5" for="checkInBulkPoolQuantity">
                                        <i class="bi bi-water text-sky-600"></i> Pool Access Quantity
                                    </label>
                                    <span class="text-[0.68rem] text-hp-text-muted" id="checkInBulkPoolQtyHint">0 of 1</span>
                                </div>
                                <input type="number" name="pool_access_quantity" id="checkInBulkPoolQuantity" min="0" max="1" value="0" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3 py-1.5 text-xs text-hp-text transition-colors focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                            </div>
                        </div>
                        <div class="guest-form__actions flex flex-wrap justify-end pt-1">
                            <button type="submit" class="guest-form__button inline-flex items-center gap-2 cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-xs font-bold text-white transition-all duration-200 hover:bg-hp-green-dark shadow-sm active:scale-[0.98]">
                                <i class="bi bi-people-fill"></i>
                                <span>Add Companions</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- RIGHT SIDE: STAGED COMPANION PREVIEW (6 cols) -->
                <div class="lg:col-span-6 flex flex-col gap-3">
                    <div class="rounded-2xl border border-glass-border bg-hp-cream/70 dark:bg-white/5 p-4 shadow-xs flex flex-col" id="checkInModalCompanionPreviewSection">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2 border-b border-glass-border/40 pb-2.5">
                            <div class="flex items-center gap-2">
                                <i class="bi bi-person-lines-fill text-hp-green text-base"></i>
                                <h4 class="m-0 text-sm font-bold text-hp-text dark:text-[#f3f4f6]">Staged Companions</h4>
                            </div>
                            <div class="flex items-center gap-2">
                                <span id="checkInModalCompanionPreviewCountBadge" class="rounded-full bg-hp-green/15 px-2.5 py-0.5 text-xs font-bold text-hp-green">0 companions</span>
                                <button type="button" id="toggleCheckInModalCompanionFilterBtn" class="flex h-7 items-center gap-1.5 px-2.5 rounded-lg border border-glass-border bg-glass text-xs font-semibold text-hp-text hover:bg-glass-hover hover:border-glass-border-strong transition-colors cursor-pointer" title="Toggle Search & Filter">
                                    <i class="bi bi-funnel text-xs text-hp-green"></i>
                                    <span class="text-[0.72rem]">Filter</span>
                                </button>
                                <button type="button" id="checkInModalCompanionClearAllBtn" class="hidden text-xs font-semibold text-red-500 hover:text-red-700 transition-colors cursor-pointer">
                                    Clear
                                </button>
                            </div>
                        </div>

                        <!-- Modal Staged Search & Filter Toolbar (Default is hidden) -->
                        <div id="checkInModalCompanionFilterToolbar" class="mb-3 hidden flex-wrap items-center gap-1.5 rounded-xl border border-glass-border/70 bg-glass/70 p-2 transition-all animate-fade-in">
                            <!-- Search single companion by name -->
                            <div class="relative flex-1 min-w-[130px]">
                                <i class="bi bi-search absolute left-2.5 top-1/2 -translate-y-1/2 text-[0.65rem] text-hp-text-muted/70"></i>
                                <input type="text" id="checkInModalCompanionSearchInput" placeholder="Search single name..." class="w-full rounded-lg border border-glass-border bg-glass py-1 pl-7 pr-2 text-[0.72rem] text-hp-text placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                            </div>
                            
                            <!-- Filter bulk: Gender -->
                            <div class="w-auto min-w-[90px]">
                                <select id="checkInModalCompanionFilterGender" class="w-full rounded-lg border border-glass-border bg-glass px-2 py-1 text-[0.72rem] font-medium text-hp-text focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                                    <option value="">Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>

                            <!-- Filter bulk: Age Group -->
                            <div class="w-auto min-w-[100px]">
                                <select id="checkInModalCompanionFilterAgeGroup" class="w-full rounded-lg border border-glass-border bg-glass px-2 py-1 text-[0.72rem] font-medium text-hp-text focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                                    <option value="">Age Group</option>
                                    <option value="0-12">Kids (0-12)</option>
                                    <option value="13-17">Teens (13-17)</option>
                                    <option value="18-59">Adults (18-59)</option>
                                    <option value="60+">Seniors (60+)</option>
                                </select>
                            </div>

                            <button type="button" id="checkInModalCompanionFilterResetBtn" class="hidden items-center gap-1 rounded-lg border border-glass-border bg-glass px-2 py-1 text-[0.68rem] font-semibold text-hp-text-muted hover:text-red-500 hover:border-red-500/30 transition-colors cursor-pointer" title="Reset filters">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>

                        <!-- Scrollable Staged List Container -->
                        <div id="checkInModalCompanionPreviewList" class="grid gap-2 max-h-[440px] overflow-y-auto pr-1">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL FOOTER ACTIONS -->
            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-glass-border/40 pt-4">
                <div class="text-xs font-medium text-hp-text-muted">
                    <span id="checkInModalCompanionFooterSummary">0 companions added so far</span>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <button type="button" class="guest-form__secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-xs font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" data-close-check-in-companion-modal="true">Cancel</button>
                    <button type="button" id="checkInModalConfirmAllCompanionsBtn" class="inline-flex items-center gap-2 cursor-pointer rounded-xl border-0 bg-hp-green px-6 py-2.5 text-xs font-bold text-white transition-all duration-200 hover:bg-hp-green-dark shadow-md active:scale-[0.98]" data-close-check-in-companion-modal="true">
                        <i class="bi bi-check2-circle text-base"></i>
                        <span>Add All Companions Created</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Check-In Companion Group Edit Modal -->
    <div class="guest-modal hidden fixed inset-0 items-center justify-center is-open:flex" id="checkInCompanionGroupEditModal" aria-hidden="true" style="z-index: 1060;">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/60 dark:bg-black/80" data-close-checkin-group-edit-modal="true"></div>
        <div class="guest-modal__content relative z-[1] w-full max-w-[560px] !max-h-none !overflow-visible rounded-2xl bg-hp-cream p-5 sm:p-6 shadow-2xl dark:bg-[rgba(26,30,28,0.98)] border border-glass-border animate-fade-in" role="dialog" aria-modal="true" aria-labelledby="checkInGroupEditTitle">
            <button type="button" class="group absolute right-4 top-4 cursor-pointer w-8 h-8 rounded-full border border-gray-300/80 bg-white/80 hover:bg-red-50 hover:border-red-300 text-gray-500 hover:text-red-600 dark:border-white/15 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-red-950/40 dark:hover:border-red-800/60 dark:hover:text-red-400 flex items-center justify-center transition-all duration-200 shadow-xs z-10" data-close-checkin-group-edit-modal="true" aria-label="Close modal">
                <i class="bi bi-x-lg text-xs font-bold transition-transform duration-200 group-hover:rotate-90"></i>
            </button>

            <!-- Modal Header -->
            <div class="mb-3 flex items-center gap-3 border-b border-glass-border/60 pb-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-hp-green/15 text-hp-green">
                    <i class="bi bi-people-fill text-lg"></i>
                </div>
                <div>
                    <h3 id="checkInGroupEditTitle" class="m-0 font-display text-base font-bold text-hp-text dark:text-[#f3f4f6]">Edit Companion Group</h3>
                    <p class="m-0 text-xs text-hp-text-muted">Adjust group size and access privileges</p>
                </div>
            </div>

            <!-- Demographics Badge -->
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <span id="checkInGroupEditDemographicsBadge" class="inline-flex items-center gap-1.5 rounded-lg bg-hp-green/15 text-hp-green px-3 py-1 text-xs font-bold"></span>
                <span id="checkInGroupEditAmenityBadge" class="hidden inline-flex items-center gap-1.5 rounded-lg bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 px-2.5 py-1 text-xs font-bold"></span>
            </div>

            <form id="checkInGroupEditForm" class="grid gap-3" action="#">
                <input type="hidden" id="checkInGroupEditGroupIndex" value="-1">

                <!-- Row 1: Group Quantity (Inline Row) -->
                <div class="flex items-center justify-between gap-3 rounded-xl border border-glass-border bg-glass/60 dark:bg-white/5 px-3.5 py-2.5">
                    <div>
                        <label class="text-sm font-bold text-hp-text dark:text-[#f3f4f6] flex items-center gap-2 cursor-pointer m-0" for="checkInGroupEditQuantityInput">
                            <i class="bi bi-people text-hp-green text-base"></i> Group Quantity
                        </label>
                        <p class="m-0 text-xs text-hp-text-muted">Total number of guests in this group</p>
                    </div>
                    <div class="flex items-center gap-1 rounded-lg border border-glass-border bg-white/90 dark:bg-black/30 p-1 shadow-2xs">
                        <button type="button" id="checkInGroupEditQtyMinusBtn" class="flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-md border-0 bg-black/5 dark:bg-white/10 text-base font-extrabold text-hp-text hover:bg-black/10 active:scale-95 transition-all" title="Decrease quantity">−</button>
                        <input type="number" id="checkInGroupEditQuantityInput" min="1" max="500" value="1" class="no-spinners m-0 w-14 border-0 bg-transparent text-center font-display text-base font-bold text-hp-green-dark dark:text-hp-green focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" title="Type custom quantity">
                        <button type="button" id="checkInGroupEditQtyPlusBtn" class="flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-md border-0 bg-black/5 dark:bg-white/10 text-base font-extrabold text-hp-text hover:bg-black/10 active:scale-95 transition-all" title="Increase quantity">+</button>
                    </div>
                </div>

                <!-- Row 2: Pool Access Passes -->
                <div class="grid grid-cols-1 gap-3" id="checkInGroupEditAccessRow">
                    <div class="rounded-xl border border-sky-500/30 bg-sky-500/5 dark:bg-sky-500/10 p-3 flex flex-col justify-between gap-2 transition-all" id="checkInGroupEditPoolWrap">
                        <div class="flex items-center justify-between gap-1.5">
                            <label class="text-xs font-bold text-sky-900 dark:text-sky-200 flex items-center gap-1.5 cursor-pointer m-0 truncate select-none" for="checkInGroupEditPoolInput">
                                <i class="bi bi-water text-sky-600 text-sm"></i> Pool Passes
                            </label>
                            <span class="text-xs font-bold text-sky-800 dark:text-sky-300 shrink-0" id="checkInGroupEditPoolHint">0 of 1</span>
                        </div>
                        <div class="flex items-center gap-1 rounded-lg border border-sky-500/20 bg-white/95 dark:bg-black/30 p-1 shadow-2xs">
                            <button type="button" id="checkInGroupEditPoolMinusBtn" class="flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-md border-0 bg-black/5 dark:bg-white/10 text-sm font-extrabold text-sky-900 dark:text-sky-200 hover:bg-black/10 active:scale-95 transition-all" title="Decrease pool">−</button>
                            <input type="number" id="checkInGroupEditPoolInput" min="0" max="1" value="0" class="no-spinners m-0 w-full flex-1 border-0 bg-transparent text-center font-display text-sm font-bold text-sky-950 dark:text-sky-100 focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" title="Type custom pool pass count">
                            <button type="button" id="checkInGroupEditPoolPlusBtn" class="flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-md border-0 bg-black/5 dark:bg-white/10 text-sm font-extrabold text-sky-900 dark:text-sky-200 hover:bg-black/10 active:scale-95 transition-all">+</button>
                        </div>
                        <div class="flex items-center justify-end gap-1.5 pt-0.5">
                            <button type="button" id="checkInGroupEditPoolZeroBtn" class="cursor-pointer rounded-lg border border-sky-500/25 bg-white/80 dark:bg-white/10 px-2.5 py-1 text-xs font-bold text-sky-800 dark:text-sky-300 hover:bg-sky-500/20 active:scale-95 transition-all">None (0)</button>
                            <button type="button" id="checkInGroupEditPoolAllBtn" class="cursor-pointer rounded-lg border border-sky-500/25 bg-white/80 dark:bg-white/10 px-2.5 py-1 text-xs font-bold text-sky-800 dark:text-sky-300 hover:bg-sky-500/20 active:scale-95 transition-all">All Pool</button>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-1 flex items-center justify-end gap-2.5 border-t border-glass-border/60 pt-3">
                    <button type="button" class="cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2 text-xs sm:text-sm font-semibold text-hp-text transition-colors hover:bg-glass-hover" data-close-checkin-group-edit-modal="true">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-1.5 cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2 text-xs sm:text-sm font-bold text-white shadow-xs transition-all hover:bg-hp-green-dark active:scale-[0.98]">
                        <i class="bi bi-check-lg text-sm"></i>
                        <span>Apply Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Duplicate Companion Warning Modal -->
    <div class="guest-modal hidden z-[1065]" id="duplicateCompanionModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/60 dark:bg-black/80" data-close-duplicate-modal="true"></div>
        <div class="guest-modal__content relative z-[1] w-full max-w-[440px] rounded-2xl bg-glass p-6 shadow-2xl dark:bg-[rgba(30,30,30,0.98)] text-center animate-fade-in" role="dialog" aria-modal="true" aria-labelledby="duplicateCompanionTitle">
            <div class="mx-auto mb-3.5 flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500/15 text-amber-600 dark:text-amber-400">
                <i class="bi bi-exclamation-triangle-fill text-2xl"></i>
            </div>
            <h3 id="duplicateCompanionTitle" class="m-0 font-display text-lg font-bold text-hp-text dark:text-[#f3f4f6]">Companion Already Exists</h3>
            <p id="duplicateCompanionMessage" class="mt-2 mb-5 text-xs leading-relaxed text-hp-text-muted">
                A companion with identical information has already been created.
            </p>
            <div class="flex justify-center">
                <button type="button" class="cursor-pointer rounded-xl border-0 bg-hp-green px-6 py-2.5 text-xs font-bold text-white shadow-md transition-all hover:bg-hp-green-dark active:scale-[0.98]" data-close-duplicate-modal="true">
                    Got It
                </button>
            </div>
        </div>
    </div>

    <!-- Remove Companion Confirmation Modal -->
    <div class="guest-modal hidden z-[1070]" id="removeCompanionConfirmModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/60 dark:bg-black/80" data-close-remove-companion-modal="true"></div>
        <div class="guest-modal__content relative z-[1] w-full max-w-[420px] rounded-2xl bg-glass p-6 shadow-2xl dark:bg-[rgba(30,30,30,0.98)] text-center animate-fade-in" role="dialog" aria-modal="true" aria-labelledby="removeCompanionModalTitle">
            <div class="mx-auto mb-3.5 flex h-14 w-14 items-center justify-center rounded-2xl bg-red-500/15 text-red-500 dark:text-red-400 border border-red-500/20 shadow-xs">
                <i class="bi bi-trash3-fill text-2xl"></i>
            </div>
            <h3 id="removeCompanionModalTitle" class="m-0 font-display text-lg font-bold text-hp-text dark:text-[#f3f4f6]">Remove Companion?</h3>
            <p id="removeCompanionModalMessage" class="mt-2 mb-5 text-xs leading-relaxed text-hp-text-muted">
                Are you sure you want to remove this companion? This action cannot be undone.
            </p>
            <div class="flex items-center justify-center gap-2.5">
                <button type="button" class="cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2 text-xs font-semibold text-hp-text transition-all hover:bg-glass-hover active:scale-[0.98]" data-close-remove-companion-modal="true">
                    Cancel
                </button>
                <button type="button" id="confirmRemoveCompanionBtn" class="inline-flex items-center gap-1.5 cursor-pointer rounded-xl border-0 bg-red-600 px-5 py-2 text-xs font-bold text-white shadow-md transition-all hover:bg-red-700 active:scale-[0.98]">
                    <i class="bi bi-trash3 text-xs"></i>
                    <span>Yes, Remove</span>
                </button>
            </div>
        </div>
    </div>

    <div class="guest-modal guest-modal--compact fixed inset-0 z-[1000] hidden items-center justify-center is-open:flex" id="checkInConfirmationModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/50 dark:bg-black/75" data-close-check-in-confirmation="true"></div>
        <div class="guest-modal__content guest-modal__content--compact relative z-[1] w-full max-w-[500px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="checkInConfirmationTitle">
            <button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-check-in-confirmation="true" aria-label="Close confirmation">&times;</button>
            <h3 id="checkInConfirmationTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Check In Reservation</h3>
            <div id="checkInConfirmationBody" class="guest-modal__body mt-6 grid gap-5"></div>
            <div class="guest-form__actions mt-6 flex flex-wrap justify-end gap-3">
                <button type="button" class="guest-form__secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" data-close-check-in-confirmation="true">Cancel</button>
                <button type="button" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark" id="confirmCheckInBtn">Yes, Check In</button>
            </div>
        </div>
    </div>

    <div class="guest-modal guest-modal--compact fixed inset-0 z-[1000] hidden items-center justify-center is-open:flex" id="companionSummaryModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/50 dark:bg-black/75" data-close-companion-summary="true"></div>
        <div class="guest-modal__content guest-modal__content--compact relative z-[1] w-full max-w-[500px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="companionSummaryTitle">
            <button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-companion-summary="true" aria-label="Close summary">&times;</button>
            <h3 id="companionSummaryTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Companion Groups Summary</h3>
            <div id="companionSummaryBody" class="guest-modal__body mt-6 grid gap-5"></div>
            <div class="guest-form__actions mt-6 flex flex-wrap justify-end gap-3">
                <button type="button" class="guest-form__secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" data-close-companion-summary="true">Cancel</button>
                <button type="button" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark" id="proceedToCheckInBtn">Proceed to Check In</button>
            </div>
        </div>
    </div>

    <x-staff_chatbot />

    <script>
        window.staffReservationData = @json($reservationData ?? []);
        window.ALL_AMENITIES = @json($allAmenities ?? []);
    </script>
</body>
</html>
