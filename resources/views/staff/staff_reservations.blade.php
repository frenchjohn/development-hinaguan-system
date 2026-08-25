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

                <x-header
                    title="Reservations"
                    subtitle="Manage online reservations and walk-in check-ins"
                />

                <script data-spa-data="">
                    window.staffAmenitiesData = @json($allAmenities ?? []);
                    window.staffReservationData = @json($reservationData ?? []);
                </script>

                <div class="resv-metrics mb-5 grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-3" data-park-settings="{{ json_encode([
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
                    <!-- 1. DATE -->
                    <article class="flex items-center gap-3.5 rounded-2xl border border-white/80 bg-white/90 p-4 shadow-[0_4px_20px_rgba(20,50,30,0.03)] backdrop-blur-sm dark:border-white/10 dark:bg-[#181b19]/90">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#e8f5e9] text-[#2e7d32] dark:bg-[rgba(46,125,50,0.2)] dark:text-[#9ca3af]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </span>
                        <div class="flex flex-col gap-0.5 min-w-0">
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-wider text-[#718076] dark:text-[#9baaa1]">DATE</p>
                            <p class="m-0 text-sm sm:text-base font-bold text-[#183d28] dark:text-[#e8f5e9] truncate" id="resvDate">{{ now()->format('F j, Y') }}</p>
                        </div>
                    </article>

                    <!-- 2. TIME -->
                    <article class="flex items-center gap-3.5 rounded-2xl border border-white/80 bg-white/90 p-4 shadow-[0_4px_20px_rgba(20,50,30,0.03)] backdrop-blur-sm dark:border-white/10 dark:bg-[#181b19]/90">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#e8f5e9] text-[#2e7d32] dark:bg-[rgba(46,125,50,0.2)] dark:text-[#9ca3af]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div class="flex flex-col gap-0.5 min-w-0">
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-wider text-[#718076] dark:text-[#9baaa1]">TIME</p>
                            <p class="m-0 text-sm sm:text-base font-bold text-[#183d28] dark:text-[#e8f5e9] truncate" id="resvTime">{{ now()->format('g:i A') }}</p>
                        </div>
                    </article>

                    <!-- 3. SESSION -->
                    <article class="flex items-center gap-3.5 rounded-2xl border border-white/80 bg-white/90 p-4 shadow-[0_4px_20px_rgba(20,50,30,0.03)] backdrop-blur-sm dark:border-white/10 dark:bg-[#181b19]/90">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#fff8e1] text-[#f57f17] dark:bg-[rgba(255,179,0,0.2)] dark:text-[#ffca28]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </span>
                        <div class="flex flex-col gap-1 min-w-0">
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-wider text-[#718076] dark:text-[#9baaa1]">SESSION</p>
                            <span class="inline-flex w-fit items-center rounded-md px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide {{ $timePeriod === 'Daytime' ? 'bg-[#fff3e0] text-[#e65100] dark:bg-[rgba(255,152,0,0.2)] dark:text-[#ffb74d]' : 'bg-[#ede7f6] text-[#6a1b9a] dark:bg-[rgba(103,58,183,0.2)] dark:text-[#ce93d8]' }}" id="resvSession">{{ strtoupper($timePeriod) }}</span>
                        </div>
                    </article>

                    <!-- 4. PENDING RESERVATIONS -->
                    <article class="flex items-center gap-3.5 rounded-2xl border border-white/80 bg-white/90 p-4 shadow-[0_4px_20px_rgba(20,50,30,0.03)] backdrop-blur-sm dark:border-white/10 dark:bg-[#181b19]/90">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#fff3e0] text-[#e65100] dark:bg-[rgba(255,152,0,0.2)] dark:text-[#ffb74d]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div class="flex flex-col gap-0.5 min-w-0">
                            <p class="m-0 text-2xl font-extrabold leading-none text-[#183d28] dark:text-[#e8f5e9]">{{ $pendingCount }}</p>
                            <p class="m-0 text-xs font-medium text-[#718076] dark:text-[#9baaa1]">Pending Reservations</p>
                        </div>
                    </article>

                    <!-- 5. TODAY'S CHECK-INS -->
                    <article class="flex items-center gap-3.5 rounded-2xl border border-white/80 bg-white/90 p-4 shadow-[0_4px_20px_rgba(20,50,30,0.03)] backdrop-blur-sm dark:border-white/10 dark:bg-[#181b19]/90">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#e8f5e9] text-[#2e7d32] dark:bg-[rgba(46,125,50,0.2)] dark:text-[#9ca3af]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div class="flex flex-col gap-0.5 min-w-0">
                            <p class="m-0 text-2xl font-extrabold leading-none text-[#183d28] dark:text-[#e8f5e9]">{{ $todayCheckIns }}</p>
                            <p class="m-0 text-xs font-medium text-[#718076] dark:text-[#9baaa1]">Today's Check-ins</p>
                        </div>
                    </article>

                    <!-- 6. EXPECTED GUESTS -->
                    <article class="flex items-center gap-3.5 rounded-2xl border border-white/80 bg-white/90 p-4 shadow-[0_4px_20px_rgba(20,50,30,0.03)] backdrop-blur-sm dark:border-white/10 dark:bg-[#181b19]/90">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#e3f2fd] text-[#1976d2] dark:bg-[rgba(25,118,210,0.2)] dark:text-[#64b5f6]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </span>
                        <div class="flex flex-col gap-0.5 min-w-0">
                            <p class="m-0 text-2xl font-extrabold leading-none text-[#183d28] dark:text-[#e8f5e9]">{{ $expectedGuests }}</p>
                            <p class="m-0 text-xs font-medium text-[#718076] dark:text-[#9baaa1]">Expected Guests</p>
                        </div>
                    </article>
                </div>

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
                        <button type="button" class="resv-tool-btn inline-flex cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-xl border border-[#dfe5e0] bg-white px-3.5 py-2 text-sm font-semibold text-[#183d28] shadow-sm transition-all duration-150 hover:bg-gray-50 active:scale-95 dark:border-white/15 dark:bg-[#181b19] dark:text-[#f3f4f6] dark:hover:bg-[#242a26]" id="refreshTableBtn">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Refresh
                        </button>
                        <button type="button" class="resv-tool-btn inline-flex cursor-pointer items-center justify-center rounded-xl border border-[#dfe5e0] bg-white p-2 text-[#183d28] shadow-sm transition-all duration-150 hover:bg-gray-50 active:scale-95 dark:border-white/15 dark:bg-[#181b19] dark:text-[#f3f4f6] dark:hover:bg-[#242a26]" id="scanQrBtn" title="Scan reservation QR">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </button>
                        <button type="button" class="resv-tool-btn inline-flex cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-xl border border-[#dfe5e0] bg-white px-3.5 py-2 text-sm font-semibold text-[#183d28] shadow-sm transition-all duration-150 hover:bg-gray-50 active:scale-95 dark:border-white/15 dark:bg-[#181b19] dark:text-[#f3f4f6] dark:hover:bg-[#242a26]" id="exportCsvBtn">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                            Export
                        </button>
                        <button type="button" class="resv-tool-btn resv-tool-btn--primary inline-flex cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-xl bg-[#2d6a4f] hover:bg-[#1b4332] px-4 py-2 text-sm font-semibold text-white shadow-md transition-all duration-150 active:scale-95" id="addWalkInBtn">
                            <span class="text-base font-bold leading-none">+</span>
                            Add Walk-in
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
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
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

                <div class="guest-table-wrap overflow-x-auto rounded-2xl border border-[#dfe5e0] bg-white shadow-sm dark:border-white/15 dark:bg-[#181b19]" id="reservationTableWrap">
                    <table class="guest-table w-full min-w-[760px] border-collapse border-spacing-0 text-left">
                        <thead class="bg-[#f7faf8] dark:bg-[#1e2220]">
                            <tr class="border-b border-[#e8eee9] dark:border-white/10">
                                <th class="py-3.5 px-3 w-20 text-[0.7rem] font-bold uppercase tracking-wider text-[#486553] dark:text-[#9ca3af] whitespace-nowrap">ID</th>
                                <th class="py-3.5 px-4 text-[0.7rem] font-bold uppercase tracking-wider text-[#486553] dark:text-[#9ca3af]">BOOKER</th>
                                <th class="py-3.5 px-4 text-[0.7rem] font-bold uppercase tracking-wider text-[#486553] dark:text-[#9ca3af]">RESERVATION DATE</th>
                                <th class="py-3.5 px-4 text-[0.7rem] font-bold uppercase tracking-wider text-[#486553] dark:text-[#9ca3af]">SESSION</th>
                                <th class="py-3.5 px-4 text-[0.7rem] font-bold uppercase tracking-wider text-[#486553] dark:text-[#9ca3af] text-center">GUESTS</th>
                                <th class="py-3.5 px-4 text-[0.7rem] font-bold uppercase tracking-wider text-[#486553] dark:text-[#9ca3af] text-center">STATUS</th>
                                <th class="py-3.5 px-4 text-[0.7rem] font-bold uppercase tracking-wider text-[#486553] dark:text-[#9ca3af] text-right">AMOUNT</th>
                                <th class="py-3.5 px-4 text-[0.7rem] font-bold uppercase tracking-wider text-[#486553] dark:text-[#9ca3af] text-center">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="reservationTableBody" class="divide-y divide-[#f0f4ef] bg-white dark:divide-white/5 dark:bg-[#181b19]">
                            @forelse ($reservations as $reservation)
                                @php
                                    $isToday = $reservation->reservation_date && \Carbon\Carbon::parse($reservation->reservation_date)->isToday();
                                    $timeSlots = $reservationData[$reservation->id]['time_slots'] ?? [];
                                    $initials = collect(explode(' ', trim($reservation->booker_name ?? '?')))
                                        ->filter()
                                        ->take(1)
                                        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
                                        ->implode('') ?: '?';
                                    $totalDays = $reservation->total_days ?? (\Carbon\Carbon::parse($reservation->reservation_date)->diffInDays(\Carbon\Carbon::parse($reservation->end_date ?? $reservation->reservation_date)) + 1);
                                @endphp
                                <tr
                                    class="guest-row reservation-row {{ $isToday ? 'today-reservation' : '' }} cursor-pointer select-none transition-colors duration-150 hover:bg-[#f7faf6] focus-visible:bg-[#f7faf6] focus-visible:outline-none dark:hover:bg-[#242a26] dark:focus-visible:bg-[#242a26]"
                                    data-reservation-id="{{ $reservation->id }}"
                                    data-booker-name="{{ e($reservation->booker_name) }}"
                                    data-email="{{ e($reservation->email) }}"
                                    data-phone="{{ e($reservation->phone) }}"
                                    data-reservation-date="{{ $reservation->reservation_date }}"
                                    data-status="{{ strtolower($reservation->status) }}"
                                    data-guests="{{ $reservation->number_of_guests }}"
                                    data-total-amount="{{ (float) $reservation->total_amount }}"
                                    data-search="{{ strtolower(trim($reservation->id . ' #' . $reservation->id . ' ' . ($reservation->booker_name ?? '') . ' ' . ($reservation->email ?? '') . ' ' . ($reservation->phone ?? '') . ' ' . ($reservation->status ?? ''))) }}"
                                    tabindex="0"
                                    role="button"
                                    aria-label="View reservation details for {{ e($reservation->booker_name) }} (#{{ $reservation->id }})"
                                >
                                    <td class="py-3.5 px-3 w-20 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-lg bg-[#e8f5e9] px-2 py-0.5 text-xs font-bold text-[#1b4332] font-mono dark:bg-[rgba(46,125,50,0.25)] dark:text-[#9ca3af]">#{{ $reservation->id }}</span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="resv-booker flex items-center gap-3">
                                            <span class="resv-avatar flex h-9 w-9 shrink-0 select-none items-center justify-center rounded-full bg-[#183d28] text-[0.78rem] font-bold uppercase tracking-[0.03em] text-white dark:bg-[#2e7d55]">{{ $initials }}</span>
                                            <div class="resv-booker__info flex min-w-0 flex-col gap-0.5">
                                                <div class="guest-name font-bold text-sm text-[#183d28] dark:text-[#e8f5e9]">
                                                    {{ $reservation->booker_name }}
                                                    @if ($isToday)
                                                        <span class="today-reservation-badge ml-1.5 inline-block rounded-md bg-[#ff9800] px-2 py-0.5 text-[0.65rem] font-bold tracking-wide text-white dark:bg-[#ffb74d]">TODAY</span>
                                                    @endif
                                                </div>
                                                <div class="guest-meta text-xs text-[#718076] dark:text-[#9baaa1] truncate">{{ $reservation->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if ($reservation->end_date && $reservation->end_date !== $reservation->reservation_date)
                                            <div>
                                                <span class="font-bold text-xs sm:text-sm text-[#183d28] dark:text-[#e8f5e9]">{{ \Carbon\Carbon::parse($reservation->reservation_date)->format('M j, Y') }} – {{ \Carbon\Carbon::parse($reservation->end_date)->format('M j, Y') }}</span>
                                                <div class="text-[0.7rem] text-[#718076] dark:text-[#9baaa1]">({{ $totalDays }} {{ $totalDays > 1 ? 'Days Stay' : 'Day Stay' }})</div>
                                            </div>
                                        @else
                                            <div>
                                                <span class="font-bold text-xs sm:text-sm text-[#183d28] dark:text-[#e8f5e9]">{{ \Carbon\Carbon::parse($reservation->reservation_date)->format('M j, Y') }}</span>
                                                <div class="text-[0.7rem] text-[#718076] dark:text-[#9baaa1]">(1 Day Stay)</div>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if ($totalDays > 1)
                                            <span class="inline-flex items-center rounded-lg border border-[#c8e6c9] bg-[#e8f5e9] px-2.5 py-1 text-xs font-semibold text-[#2e7d32] dark:border-[#2e7d50] dark:bg-[rgba(46,125,50,0.2)] dark:text-[#9ca3af]">Continuous Stay ({{ $totalDays }}D)</span>
                                        @elseif (!empty($timeSlots))
                                            <div class="time-slot-labels flex flex-wrap gap-1.5">
                                                @foreach ($timeSlots as $slot)
                                                    <span class="inline-flex items-center rounded-lg border border-[#c8e6c9] bg-[#e8f5e9] px-2.5 py-1 text-xs font-semibold text-[#2e7d32] dark:border-[#2e7d50] dark:bg-[rgba(46,125,50,0.2)] dark:text-[#9ca3af]">{{ $slot }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="inline-flex items-center rounded-lg border border-[#c8e6c9] bg-[#e8f5e9] px-2.5 py-1 text-xs font-semibold text-[#2e7d32] dark:border-[#2e7d50] dark:bg-[rgba(46,125,50,0.2)] dark:text-[#9ca3af]">{{ $reservation->start_slot ?? 'Daytime' }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-semibold text-sm text-[#183d28] dark:text-[#e8f5e9]">{{ $reservation->number_of_guests }}</td>
                                    <td class="py-3.5 px-4 text-center">
                                        @php
                                            $status = ucfirst(strtolower($reservation->status));
                                            $statusClass = match($status) {
                                                'Pending' => 'bg-[#fff3e0] text-[#e65100] border-[#ffe0b2] dark:bg-[rgba(230,81,0,0.2)] dark:text-[#ffb74d] dark:border-[#ff9800]/30',
                                                'Confirmed' => 'bg-[#e8f5e9] text-[#2e7d32] border-[#c8e6c9] dark:bg-[rgba(46,125,50,0.2)] dark:text-[#9ca3af] dark:border-[#9ca3af]/30',
                                                'Checked In', 'Checked in' => 'bg-[#e3f2fd] text-[#1565c0] border-[#bbdefb] dark:bg-[rgba(21,101,192,0.2)] dark:text-[#64b5f6] dark:border-[#64b5f6]/30',
                                                'Checked Out', 'Checked out' => 'bg-[#ede7f6] text-[#6a1b9a] border-[#d1c4e9] dark:bg-[rgba(106,27,154,0.2)] dark:text-[#ce93d8] dark:border-[#ce93d8]/30',
                                                'Cancelled' => 'bg-[#ffebee] text-[#c62828] border-[#ffcdd2] dark:bg-[rgba(198,40,40,0.2)] dark:text-[#ef5350] dark:border-[#ef5350]/30',
                                                default => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-white/10 dark:text-gray-300'
                                            };
                                        @endphp
                                        <span class="reservation-status inline-flex items-center justify-center rounded-full border px-3 py-0.5 text-xs font-bold capitalize {{ $statusClass }}">{{ $reservation->status }}</span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-bold text-sm text-[#183d28] dark:text-[#e8f5e9]">₱{{ number_format($reservation->total_amount, 2) }}</td>
                                    <td class="py-3.5 px-4 text-center">
                                        <button type="button" class="resv-row-action inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-[#dfe5e0] bg-white text-gray-500 shadow-sm transition-all duration-150 hover:border-[#2d6a4f] hover:bg-[#2d6a4f] hover:text-white dark:border-white/15 dark:bg-white/5 dark:text-[#9ca3af] dark:hover:bg-[#2e7d55]" aria-label="View reservation details">
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="guest-empty px-4 py-8 text-center text-sm text-[#718076] dark:text-[#9baaa1]">No pending online reservations found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </main>
        </div>
    </div>

    <!-- Modals (Direct children of body) -->
    <div class="guest-modal fixed inset-0 z-[1000] hidden items-center justify-center is-open:flex" id="reservationModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/50 dark:bg-black/75" data-close-reservation-modal="true"></div>
        <div class="guest-modal__content relative z-[1] w-full max-w-[720px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="reservationModalTitle">
            <button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-reservation-modal="true" aria-label="Close reservation details">&times;</button>
            <div class="guest-modal__header mb-6 flex items-center justify-between gap-4 border-b border-[rgba(13,44,29,0.1)] pb-4 dark:border-white/10">
                <h3 id="reservationModalTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Reservation Details</h3>
                <div class="guest-modal__header-actions flex items-center gap-3">
                    <span id="reservationModalStatus" class="guest-modal__role-badge inline-flex items-center rounded-full px-3 py-1.5 text-[0.78rem] font-bold uppercase tracking-[0.04em]"></span>
                    <button type="button" class="guest-modal__edit-btn inline-flex cursor-pointer items-center gap-2 rounded-[0.65rem] border border-glass-border bg-glass px-4 py-2 text-sm font-semibold text-hp-text transition-all duration-200 hover:border-hp-green hover:bg-[rgba(26,58,31,0.1)] dark:border-white/15 dark:bg-white/5" id="editReservationBtn" data-edit-reservation="true">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                        </svg>
                        Edit
                    </button>
                </div>
            </div>
            <div id="reservationModalBody" class="guest-modal__body grid gap-5"></div>
            <div id="reservationModalEditForm" class="guest-modal__edit-form border-t border-[rgba(13,44,29,0.1)] pt-6 dark:border-white/10" hidden>
                <form id="editReservationForm" class="guest-form grid gap-4">
                    <input type="hidden" name="reservation_id" id="editReservationId">
                    <div class="guest-form__row guest-form__row--two grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label class="guest-form__field grid gap-1.5">
                            <span class="text-sm font-semibold text-hp-text">Booker Name</span>
                            <input type="text" name="booker_name" id="editBookerName" required class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                        </label>
                        <label class="guest-form__field grid gap-1.5">
                            <span class="text-sm font-semibold text-hp-text">Email</span>
                            <input type="email" name="email" id="editEmail" required class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                        </label>
                    </div>
                    <div class="guest-form__row grid gap-4">
                        <label class="guest-form__field grid gap-1.5">
                            <span class="text-sm font-semibold text-hp-text">Phone</span>
                            <input type="text" name="phone" id="editPhone" required class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                        </label>
                    </div>
                    <!-- Stay Schedule & Continuous Multi-Day Selector -->
                    <div class="guest-form__field edit-schedule-card grid gap-2 rounded-xl border border-glass-border bg-glass p-3.5 dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-center justify-between">
                            <span class="text-[0.78rem] font-bold uppercase tracking-[0.04em] text-hp-text-muted dark:text-[#9ca3af]">Stay Schedule</span>
                            <span id="editStayDurationBadge" class="inline-flex items-center rounded-full bg-hp-green/10 px-2.5 py-0.5 text-[0.75rem] font-bold text-hp-green dark:bg-hp-green/20 dark:text-[#9ca3af]">1 Day Stay</span>
                        </div>
                        
                        <!-- Hidden inputs for full continuous schedule submission -->
                        <input type="hidden" name="reservation_date" id="editReservationDate">
                        <input type="hidden" name="end_date" id="editEndDate">
                        <input type="hidden" name="start_slot" id="editStartSlot" value="Daytime">
                        <input type="hidden" name="end_slot" id="editEndSlot" value="Daytime">

                        <button type="button" class="edit-calendar__trigger flex w-full cursor-pointer items-center justify-between gap-2.5 rounded-[0.7rem] border border-glass-border bg-glass px-3.5 py-3 text-left text-sm font-semibold text-hp-text transition-all duration-200 hover:border-hp-green hover:shadow-glass focus-visible:border-hp-green focus-visible:shadow-glass focus-visible:outline-none dark:border-white/12 dark:bg-white/5 dark:text-[#f3f4f6] dark:hover:border-[#9ca3af]" id="editCalTrigger" aria-haspopup="dialog">
                            <div class="flex min-w-0 items-center gap-2.5 overflow-hidden">
                                <svg class="edit-calendar__trigger-icon h-5 w-5 shrink-0 text-[#8a7a4d] dark:text-[#c8a45d]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <div class="min-w-0">
                                    <div class="edit-calendar__trigger-value truncate text-sm font-bold text-hp-text dark:text-[#f3f4f6]" id="editCalTriggerValue">&mdash;</div>
                                    <div class="text-[0.75rem] text-hp-text-muted dark:text-[#f3f4f6]" id="editCalTriggerSessions">Daytime to Daytime</div>
                                </div>
                            </div>
                            <span class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-hp-green/30 bg-hp-green/10 px-2.5 py-1 text-xs font-bold text-hp-green hover:bg-hp-green hover:text-white transition-colors dark:border-[#9ca3af]/40 dark:bg-[#9ca3af]/15 dark:text-[#9ca3af]">
                                Change
                                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </button>

                        <!-- Dynamic Price Impact & Balance Preview -->
                        <div id="editPriceImpactCard" class="edit-price-impact rounded-lg border border-glass-border bg-[rgba(26,58,31,0.05)] p-3 text-xs dark:bg-white/5 dark:border-white/10" hidden>
                            <div class="flex items-center justify-between font-semibold">
                                <span class="text-hp-text-muted">Total Cost:</span>
                                <span id="editPreviewTotal" class="text-sm font-bold text-hp-text dark:text-[#f3f4f6]">₱0.00</span>
                            </div>
                            <div class="mt-1 flex items-center justify-between text-hp-text-muted">
                                <span>Amount Paid:</span>
                                <span id="editPreviewPaid" class="font-medium text-hp-text dark:text-[#f3f4f6]">₱0.00</span>
                            </div>
                            <div class="mt-1.5 flex items-center justify-between border-t border-glass-border pt-1.5 dark:border-white/10">
                                <span class="font-bold text-hp-text">New Balance:</span>
                                <span id="editPreviewBalance" class="font-extrabold text-sm text-[#e65100] dark:text-[#ffb74d]">₱0.00</span>
                            </div>
                            <div id="editPriceDiffBadge" class="mt-2 text-center text-[0.72rem] font-bold text-hp-text-muted"></div>
                        </div>
                    </div>

                    <!-- Booked Amenities Editor (Swap & Date Adjustment) -->
                    <div id="editAmenitiesSection" class="guest-form__field edit-amenities-card grid gap-3 rounded-xl border border-glass-border bg-glass p-3.5 dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-center justify-between border-b border-glass-border pb-2 dark:border-white/10">
                            <span class="text-[0.78rem] font-bold uppercase tracking-[0.04em] text-hp-text-muted dark:text-[#9ca3af]">Booked Amenities</span>
                            <span class="text-[0.75rem] text-hp-text-muted font-medium">Swap amenity or edit dates within stay schedule</span>
                        </div>
                        <div id="editAmenitiesList" class="grid gap-3"></div>
                    </div>
                    <div class="guest-form__row guest-form__row--two grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label class="guest-form__field grid gap-1.5">
                            <span class="text-sm font-semibold text-hp-text">Number of Guests</span>
                            <input type="number" name="number_of_guests" id="editGuests" min="1" required class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                        </label>
                        <label class="guest-form__field grid gap-1.5">
                            <span class="text-sm font-semibold text-hp-text">Status</span>
                            <select name="status" id="editStatus" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                                <option value="Pending">Pending</option>
                                <option value="Confirmed">Confirmed</option>
                                <option value="Checked In">Checked In</option>
                                <option value="Checked Out">Checked Out</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </label>
                    </div>
                    <div class="guest-form__actions flex flex-wrap justify-end gap-3">
                        <button type="button" class="guest-form__secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" id="cancelEditBtn">Cancel</button>
                        <button type="submit" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="guest-modal guest-modal--calendar fixed inset-0 z-[1000] hidden items-center justify-center is-open:flex" id="editCalendarModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/50 dark:bg-black/75" data-close-edit-calendar="true"></div>
        <div class="guest-modal__content guest-modal__content--range relative z-[1] w-full max-w-[540px] max-h-[min(90vh,820px)] overflow-y-auto rounded-2xl bg-glass p-5 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="editCalendarModalTitle">
            <button type="button" class="guest-modal__close absolute right-3.5 top-3.5 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-edit-calendar="true" aria-label="Close calendar">&times;</button>
            <div class="guest-modal__header mb-3 flex flex-wrap items-center justify-between gap-3 border-b border-[rgba(13,44,29,0.1)] pb-3 dark:border-white/10">
                <div>
                    <h3 id="editCalendarModalTitle" class="guest-modal__title m-0 font-display text-lg text-hp-text">Reschedule Stay</h3>
                    <p class="m-0 text-xs text-hp-text-muted">Select continuous stay dates (up to 5 years ahead)</p>
                </div>
                <span class="edit-calendar__modal-date whitespace-nowrap rounded-full border border-glass-border bg-[rgba(200,164,93,0.12)] px-3 py-1 text-[0.8rem] font-semibold text-[#8a7a4d] dark:border-[rgba(200,164,93,0.35)] dark:bg-[rgba(200,164,93,0.14)] dark:text-[#c8a45d]" id="editCalModalCurrent"></span>
            </div>

            <!-- Sessions Bar: Start Slot & End Slot -->
            <div class="edit-calendar__sessions-panel mb-3.5 grid grid-cols-1 gap-2.5 sm:grid-cols-2 rounded-xl border border-glass-border bg-glass p-3 dark:border-white/10 dark:bg-white/5">
                <div class="grid gap-1">
                    <span class="text-[0.72rem] font-bold uppercase tracking-[0.04em] text-hp-text-muted">Check-In Session</span>
                    <div class="flex gap-1.5" id="editStartSlotGroup">
                        <button type="button" class="session-pill-btn flex-1 rounded-lg border border-glass-border bg-glass py-1.5 text-xs font-bold text-hp-text transition-all duration-150 data-[active=true]:border-hp-green data-[active=true]:bg-hp-green data-[active=true]:text-white" data-slot-type="start" data-slot-val="Daytime">Daytime</button>
                        <button type="button" class="session-pill-btn flex-1 rounded-lg border border-glass-border bg-glass py-1.5 text-xs font-bold text-hp-text transition-all duration-150 data-[active=true]:border-hp-green data-[active=true]:bg-hp-green data-[active=true]:text-white" data-slot-type="start" data-slot-val="Nighttime">Nighttime</button>
                    </div>
                </div>
                <div class="grid gap-1">
                    <span class="text-[0.72rem] font-bold uppercase tracking-[0.04em] text-hp-text-muted">Check-Out Session</span>
                    <div class="flex gap-1.5" id="editEndSlotGroup">
                        <button type="button" class="session-pill-btn flex-1 rounded-lg border border-glass-border bg-glass py-1.5 text-xs font-bold text-hp-text transition-all duration-150 data-[active=true]:border-hp-green data-[active=true]:bg-hp-green data-[active=true]:text-white" data-slot-type="end" data-slot-val="Daytime">Daytime</button>
                        <button type="button" class="session-pill-btn flex-1 rounded-lg border border-glass-border bg-glass py-1.5 text-xs font-bold text-hp-text transition-all duration-150 data-[active=true]:border-hp-green data-[active=true]:bg-hp-green data-[active=true]:text-white" data-slot-type="end" data-slot-val="Nighttime">Nighttime</button>
                    </div>
                </div>
            </div>

            <!-- Calendar Component -->
            <div class="edit-calendar edit-calendar--modal rounded-[0.85rem] border border-glass-border bg-hp-cream p-4 transition-colors duration-300 dark:bg-white/5 dark:border-white/10">
                <div class="edit-calendar__head mb-2 flex items-center justify-between gap-2">
                    <button type="button" class="edit-calendar__nav inline-flex h-[2rem] w-[2rem] cursor-pointer items-center justify-center rounded-[0.55rem] border border-glass-border bg-glass text-lg leading-none text-hp-text transition-all duration-200 hover:border-hp-green hover:bg-hp-green/10 dark:border-white/12 dark:bg-white/6 dark:text-[#f3f4f6]" id="editCalPrev" aria-label="Previous month">&lsaquo;</button>
                    <div class="edit-calendar__title-wrap flex min-w-0 items-baseline gap-2">
                        <div class="edit-calendar__title text-[0.95rem] font-bold capitalize text-hp-text dark:text-[#f3f4f6]" id="editCalTitle">&mdash;</div>
                        <select class="edit-calendar__year cursor-pointer rounded-[0.45rem] border border-glass-border bg-glass px-2.5 py-1 text-[0.85rem] font-bold text-hp-text transition-all duration-200 hover:border-hp-green focus:border-hp-green focus:outline-none dark:border-white/12 dark:bg-white/6 dark:text-[#f3f4f6]" id="editCalYear" aria-label="Select year"></select>
                    </div>
                    <button type="button" class="edit-calendar__nav inline-flex h-[2rem] w-[2rem] cursor-pointer items-center justify-center rounded-[0.55rem] border border-glass-border bg-glass text-lg leading-none text-hp-text transition-all duration-200 hover:border-hp-green hover:bg-hp-green/10 dark:border-white/12 dark:bg-white/6 dark:text-[#f3f4f6]" id="editCalNext" aria-label="Next month">&rsaquo;</button>
                </div>

                <div class="edit-calendar__weekdays mt-2 grid grid-cols-7 gap-1">
                    <span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Su</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Mo</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Tu</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">We</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Th</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Fr</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Sa</span>
                </div>

                <div class="edit-calendar__grid relative mt-1 grid min-h-[220px] grid-cols-7 gap-1 transition-opacity duration-250" id="editCalGrid"></div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-glass-border pt-2 text-[0.72rem] text-hp-text-muted dark:border-white/10">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-hp-green"></span> Selected Range</span>
                        <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-[rgba(13,44,29,0.2)] dark:bg-white/20"></span> Unavailable</span>
                    </div>
                    <span id="editCalStepHelp" class="font-semibold text-hp-green dark:text-[#9ca3af]">Click date to set check-in</span>
                </div>
            </div>

            <!-- Modal Footer: Summary & Apply button -->
            <div class="edit-calendar__footer mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-glass-border pt-3 dark:border-white/10">
                <div class="min-w-0">
                    <div class="text-xs font-bold text-hp-text dark:text-[#f3f4f6]" id="editCalSummaryText">Select date range</div>
                    <div class="text-[0.72rem] text-hp-text-muted" id="editCalCostSummary">₱0.00</div>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="guest-form__secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-3.5 py-2 text-xs font-semibold text-hp-text hover:bg-glass-hover" data-close-edit-calendar="true">Cancel</button>
                    <button type="button" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-4 py-2 text-xs font-bold text-white transition-colors duration-150 hover:bg-hp-green-dark" id="editCalApplyBtn">Apply Schedule</button>
                </div>
            </div>
        </div>
    </div>

    <div class="guest-modal guest-modal--confirm fixed inset-0 z-[1100] hidden items-center justify-center is-open:flex" id="confirmModal" aria-hidden="true">
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

    <div class="guest-modal guest-modal--success fixed inset-0 z-[1200] hidden items-center justify-center is-open:flex" id="successModal" aria-hidden="true">
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

    <div class="guest-modal guest-modal--add fixed inset-0 z-[1000] hidden items-center justify-center is-open:flex" id="checkInModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/50 dark:bg-black/75" data-close-check-in-modal="true"></div>
        <div class="guest-modal__content guest-modal__content--wide relative z-[1] w-full max-w-[900px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="checkInModalTitle">
            <button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-check-in-modal="true" aria-label="Close check-in form">&times;</button>
            <h3 id="checkInModalTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Check In Reservation</h3>
            <form id="checkInForm" class="guest-form mt-6 grid gap-4" action="#">
                <div class="guest-form__group grid gap-2">
                    <label class="guest-form__label text-sm font-semibold text-hp-text">Guest mode</label>
                    <div class="guest-form__chips flex flex-wrap gap-2">
                        <label class="guest-form__chip flex cursor-pointer items-center gap-2 rounded-full border border-glass-border bg-glass px-4 py-2 text-sm font-semibold text-hp-text transition-all duration-200 has-[:checked]:border-hp-green has-[:checked]:bg-hp-green has-[:checked]:text-white">
                            <input type="radio" name="check_in_guest_mode" value="with_primary" checked class="sr-only">
                            <span>With primary guest</span>
                        </label>
                    </div>
                </div>

                <div id="checkInPrimaryGuestSection" class="guest-form__section grid gap-3 rounded-2xl border border-glass-border bg-hp-cream p-4 transition-colors duration-300 dark:bg-white/5">
                    <div class="guest-form__section-header mb-1 flex items-center justify-between">
                        <h4 class="guest-form__section-title m-0 text-base font-bold text-hp-text dark:text-[#f3f4f6]">Primary guest</h4>
                    </div>
                    <div class="guest-form__row guest-form__row--three grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <label class="guest-form__field grid gap-1.5">
                            <span class="text-sm font-semibold text-hp-text">First name</span>
                            <input type="text" name="check_in_primary_guest[first_name]" placeholder="First name" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                        </label>
                        <label class="guest-form__field grid gap-1.5">
                            <span class="text-sm font-semibold text-hp-text">Middle name</span>
                            <input type="text" name="check_in_primary_guest[middle_name]" placeholder="Middle name" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                        </label>
                        <label class="guest-form__field grid gap-1.5">
                            <span class="text-sm font-semibold text-hp-text">Last name</span>
                            <input type="text" name="check_in_primary_guest[last_name]" placeholder="Last name" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                        </label>
                    </div>
                    <div class="guest-form__row guest-form__row--three grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <label class="guest-form__field grid gap-1.5">
                            <span class="text-sm font-semibold text-hp-text">Age</span>
                            <input type="number" name="check_in_primary_guest[age]" min="0" placeholder="Age" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                        </label>
                        <label class="guest-form__field grid gap-1.5">
                            <span class="text-sm font-semibold text-hp-text">Gender</span>
                            <select name="check_in_primary_guest[gender]" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                                <option value="">Select gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </label>
                        <label class="guest-form__field grid gap-1.5">
                            <span class="text-sm font-semibold text-hp-text">Nationality</span>
                            <select name="check_in_primary_guest[is_foreigner]" id="checkInPrimaryIsForeigner" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                                <option value="0" selected>Filipino</option>
                                <option value="1">Foreigner</option>
                            </select>
                        </label>
                    </div>
                    <div class="guest-form__row guest-form__row--two grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label class="guest-form__field grid gap-1.5">
                            <span class="text-sm font-semibold text-hp-text">Phone</span>
                            <input type="text" name="check_in_primary_guest[phone]" placeholder="Phone number" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                        </label>
                        <label class="guest-form__field grid gap-1.5">
                            <span class="text-sm font-semibold text-hp-text">Email</span>
                            <input type="email" name="check_in_primary_guest[email]" placeholder="Email address" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                        </label>
                    </div>
                </div>

                <div class="guest-form__section grid gap-3 rounded-2xl border border-glass-border bg-hp-cream p-4 transition-colors duration-300 dark:bg-white/5">
                    <div class="guest-form__section-header mb-1 flex items-center justify-between gap-2">
                        <h4 class="guest-form__section-title m-0 text-base font-bold text-hp-text dark:text-[#f3f4f6]">Companions</h4>
                        <div class="flex gap-2">
                            <button type="button" class="guest-form__secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" id="checkInAddCompanionBtn">+ Add Single</button>
                            <button type="button" class="guest-form__secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" id="checkInBulkCompanionBtn">+ Add Bulk</button>
                        </div>
                    </div>
                    <div id="checkInCompanionList" class="guest-companion-list grid gap-2"></div>
                    <div id="checkInCompanionHiddenFields"></div>
                </div>

                <div class="guest-form__section grid gap-3 rounded-2xl border border-glass-border bg-hp-cream p-4 transition-colors duration-300 dark:bg-white/5">
                    <div class="guest-form__section-header mb-1 flex items-center justify-between gap-2">
                        <h4 class="guest-form__section-title m-0 text-base font-bold text-hp-text dark:text-[#f3f4f6]">Entrance Fee</h4>
                        <span id="checkInEffectivePeriodBadge" class="inline-flex items-center rounded-full border border-glass-border bg-[rgba(255,152,0,0.15)] px-2.5 py-1 text-[0.72rem] font-bold uppercase tracking-[0.06em] text-[#e65100] dark:bg-[rgba(255,152,0,0.2)] dark:text-[#ffb74d]">—</span>
                    </div>
                    <label class="guest-form__field inline-flex w-fit cursor-pointer items-center gap-2.5">
                        <input type="checkbox" name="check_in_include_pool" id="checkInIncludePool" class="h-4 w-4 cursor-pointer accent-hp-green">
                        <span class="text-sm font-semibold text-hp-text">Include Pool Access</span>
                    </label>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <div class="rounded-xl border border-glass-border bg-glass px-3.5 py-2.5">
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Adults</p>
                            <p class="m-0 text-[0.95rem] font-bold text-hp-text dark:text-[#f3f4f6]" id="checkInAdultSummary">0 × ₱0.00</p>
                        </div>
                        <div class="rounded-xl border border-glass-border bg-glass px-3.5 py-2.5">
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Children</p>
                            <p class="m-0 text-[0.95rem] font-bold text-hp-text dark:text-[#f3f4f6]" id="checkInChildSummary">0 × ₱0.00</p>
                        </div>
                        <div class="rounded-xl border border-glass-border bg-glass px-3.5 py-2.5">
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Pool</p>
                            <p class="m-0 text-[0.95rem] font-bold text-hp-text dark:text-[#f3f4f6]" id="checkInPoolSummary">₱0.00</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-hp-green/30 bg-[rgba(26,58,31,0.08)] px-3.5 py-2.5 dark:bg-[rgba(129,199,132,0.08)]">
                        <div>
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Entrance subtotal</p>
                            <p class="m-0 text-[1.05rem] font-extrabold text-hp-green" id="checkInEntranceTotal">₱0.00</p>
                        </div>
                        <div>
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Reservation balance</p>
                            <p class="m-0 text-[1.05rem] font-extrabold text-[#e65100]" id="checkInReservationBalance">₱0.00</p>
                        </div>
                        <div>
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Total to pay</p>
                            <p class="m-0 text-[1.05rem] font-extrabold text-hp-text dark:text-[#f3f4f6]" id="checkInGrandTotal">₱0.00</p>
                        </div>
                    </div>
                </div>

                <div class="guest-form__actions flex flex-wrap justify-end gap-3">
                    <button type="button" class="guest-form__secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" data-close-check-in-modal="true">Cancel</button>
                    <button type="submit" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark">Check In</button>
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

    <div class="guest-modal guest-modal--compact fixed inset-0 z-[1000] hidden items-center justify-center is-open:flex" id="checkInCompanionModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/50 dark:bg-black/75" data-close-check-in-companion-modal="true"></div>
        <div class="guest-modal__content guest-modal__content--compact relative z-[1] w-full max-w-[500px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="checkInCompanionModalTitle">
            <button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-check-in-companion-modal="true" aria-label="Close companion form">&times;</button>
            <h3 id="checkInCompanionModalTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Add Companion</h3>
            <form id="checkInCompanionForm" class="guest-form mt-6 grid gap-4" action="#">
                <div class="guest-form__row guest-form__row--three grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <label class="guest-form__field grid gap-1.5">
                        <span class="text-sm font-semibold text-hp-text">First name</span>
                        <input type="text" name="first_name" placeholder="First name" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                    </label>
                    <label class="guest-form__field grid gap-1.5">
                        <span class="text-sm font-semibold text-hp-text">Middle name</span>
                        <input type="text" name="middle_name" placeholder="Middle name" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                    </label>
                    <label class="guest-form__field grid gap-1.5">
                        <span class="text-sm font-semibold text-hp-text">Last name</span>
                        <input type="text" name="last_name" placeholder="Last name" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                    </label>
                </div>
                <div class="guest-form__row guest-form__row--three grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <label class="guest-form__field grid gap-1.5">
                        <span class="text-sm font-semibold text-hp-text">Age</span>
                        <input type="number" name="age" min="0" placeholder="Age" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                    </label>
                    <label class="guest-form__field grid gap-1.5">
                        <span class="text-sm font-semibold text-hp-text">Gender</span>
                        <select name="gender" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                            <option value="">Select gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </label>
                    <label class="guest-form__field grid gap-1.5">
                        <span class="text-sm font-semibold text-hp-text">Nationality</span>
                        <select name="is_foreigner" id="checkInCompanionIsForeigner" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                            <option value="0" selected>Filipino</option>
                            <option value="1">Foreigner</option>
                        </select>
                    </label>
                </div>
                <div class="guest-form__row guest-form__row--two grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label class="guest-form__field grid gap-1.5">
                        <span class="text-sm font-semibold text-hp-text">Phone</span>
                        <input type="text" name="phone" placeholder="Phone number" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                    </label>
                    <label class="guest-form__field grid gap-1.5">
                        <span class="text-sm font-semibold text-hp-text">Email</span>
                        <input type="email" name="email" placeholder="Email address" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                    </label>
                </div>
                <div class="guest-form__actions flex flex-wrap justify-end gap-3">
                    <button type="button" class="guest-form__secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" data-close-check-in-companion-modal="true">Cancel</button>
                    <button type="submit" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark">Add Companion</button>
                </div>
            </form>
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

    <div class="guest-modal guest-modal--compact fixed inset-0 z-[1000] hidden items-center justify-center is-open:flex" id="bulkCompanionModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/50 dark:bg-black/75" data-close-bulk-companion-modal="true"></div>
        <div class="guest-modal__content guest-modal__content--compact relative z-[1] w-full max-w-[500px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="bulkCompanionModalTitle">
            <button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-bulk-companion-modal="true" aria-label="Close bulk companion form">&times;</button>

            <div class="guest-modal__header mb-4 flex flex-col items-center gap-1 border-b-0 pb-0 text-center">
                <div class="guest-modal__icon-wrap mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-hp-green-mid text-white shadow-[0_4px_12px_rgba(46,125,85,0.2)]">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z" />
                    </svg>
                </div>
                <h3 id="bulkCompanionModalTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Add Companions in Bulk</h3>
                <p class="guest-modal__subtitle mt-1 text-sm text-hp-text-muted">Quickly generate multiple companions of the same profile.</p>
            </div>

            <form id="bulkCompanionForm" class="guest-form mt-6 grid gap-4" action="#">
                <div class="bulk-panel mb-6 rounded-2xl border border-glass-border bg-glass-hover p-5">
                    <div class="guest-form__row guest-form__row--two grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="bulk-field flex flex-col gap-2">
                            <span class="bulk-field__label text-[0.8rem] font-semibold uppercase tracking-[0.5px] text-hp-text-muted">Gender</span>
                            <div class="bulk-segment flex overflow-hidden rounded-xl border border-glass-border bg-glass">
                                <label class="bulk-segment__btn relative flex-1 cursor-pointer text-center">
                                    <input type="radio" name="gender" value="Male" checked class="absolute h-0 w-0 opacity-0">
                                    <span class="block border-r border-glass-border px-2 py-2.5 text-sm font-medium transition-all duration-200">Male</span>
                                </label>
                                <label class="bulk-segment__btn relative flex-1 cursor-pointer text-center">
                                    <input type="radio" name="gender" value="Female" class="absolute h-0 w-0 opacity-0">
                                    <span class="block px-2 py-2.5 text-sm font-medium transition-all duration-200">Female</span>
                                </label>
                            </div>
                        </div>

                        <div class="bulk-field flex flex-col gap-2">
                            <span class="bulk-field__label text-[0.8rem] font-semibold uppercase tracking-[0.5px] text-hp-text-muted">Nationality</span>
                            <div class="bulk-segment flex overflow-hidden rounded-xl border border-glass-border bg-glass">
                                <label class="bulk-segment__btn relative flex-1 cursor-pointer text-center">
                                    <input type="radio" name="is_foreigner" value="0" checked class="absolute h-0 w-0 opacity-0">
                                    <span class="block border-r border-glass-border px-2 py-2.5 text-sm font-medium transition-all duration-200">Filipino</span>
                                </label>
                                <label class="bulk-segment__btn relative flex-1 cursor-pointer text-center">
                                    <input type="radio" name="is_foreigner" value="1" class="absolute h-0 w-0 opacity-0">
                                    <span class="block px-2 py-2.5 text-sm font-medium transition-all duration-200">Foreigner</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="guest-form__row guest-form__row--two mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="bulk-field flex flex-col gap-2">
                            <span class="bulk-field__label text-[0.8rem] font-semibold uppercase tracking-[0.5px] text-hp-text-muted">Age Group</span>
                            <div class="bulk-segment flex flex-wrap overflow-hidden rounded-xl border border-glass-border bg-glass">
                                <label class="bulk-segment__btn relative flex-[1_1_50%] cursor-pointer text-center">
                                    <input type="radio" name="age_group" value="0-12" class="absolute h-0 w-0 opacity-0">
                                    <span class="block border-b border-r border-glass-border px-2 py-2.5 text-[0.8rem] font-medium transition-all duration-200">Kids</span>
                                </label>
                                <label class="bulk-segment__btn relative flex-[1_1_50%] cursor-pointer text-center">
                                    <input type="radio" name="age_group" value="13-17" class="absolute h-0 w-0 opacity-0">
                                    <span class="block border-b border-glass-border px-2 py-2.5 text-[0.8rem] font-medium transition-all duration-200">Teens</span>
                                </label>
                                <label class="bulk-segment__btn relative flex-[1_1_50%] cursor-pointer text-center">
                                    <input type="radio" name="age_group" value="18-59" checked class="absolute h-0 w-0 opacity-0">
                                    <span class="block border-r border-glass-border px-2 py-2.5 text-[0.8rem] font-medium transition-all duration-200">Adults</span>
                                </label>
                                <label class="bulk-segment__btn relative flex-[1_1_50%] cursor-pointer text-center">
                                    <input type="radio" name="age_group" value="60+" class="absolute h-0 w-0 opacity-0">
                                    <span class="block px-2 py-2.5 text-[0.8rem] font-medium transition-all duration-200">Seniors</span>
                                </label>
                            </div>
                        </div>

                        <div class="bulk-field flex flex-col gap-2">
                            <span class="bulk-field__label text-[0.8rem] font-semibold uppercase tracking-[0.5px] text-hp-text-muted">Quantity</span>
                            <div class="bulk-qty-wrap flex h-full items-center rounded-xl border border-glass-border bg-glass p-2">
                                <button type="button" id="bulkCompanionBtnMinus" class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-xl border-0 bg-black/5 text-xl text-hp-text transition-colors duration-200 hover:bg-black/10">−</button>
                                <input type="number" name="quantity" id="bulkCompanionQuantity" value="1" min="1" max="50" class="m-0 w-full flex-1 border-0 bg-transparent text-center font-display text-2xl font-semibold text-hp-green-dark [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                <button type="button" id="bulkCompanionBtnPlus" class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-xl border-0 bg-black/5 text-xl text-hp-text transition-colors duration-200 hover:bg-black/10">+</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="guest-form__actions flex flex-wrap justify-end gap-3">
                    <button type="button" class="guest-form__secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" data-close-bulk-companion-modal="true">Cancel</button>
                    <button type="submit" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark" id="generateCompanionsBtn">Generate</button>
                </div>
            </form>
        </div>
    </div>

    <x-staff_chatbot />

    <script>
        window.staffReservationData = @json($reservationData ?? []);
    </script>
</body>
</html>
