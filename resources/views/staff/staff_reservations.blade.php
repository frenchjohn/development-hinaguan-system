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
                                    $totalDays = $reservation->total_days ?? (\Carbon\Carbon::parse($reservation->reservation_date)->diffInDays(\Carbon\Carbon::parse($reservation->end_date ?? $reservation->reservation_date)) + 1);
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
                                    data-search="{{ strtolower(trim($reservation->id . ' #' . $reservation->id . ' ' . ($reservation->booker_name ?? '') . ' ' . ($reservation->email ?? '') . ' ' . ($reservation->phone ?? '') . ' ' . ($reservation->status ?? '') . ($isPastArrival ? ' past overdue' : '') . ($isToday ? ' today' : ''))) }}"
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
                                    <td class="py-3.5 px-4">
                                        @if ($reservation->end_date && $reservation->end_date !== $reservation->reservation_date)
                                            <div>
                                                <span class="font-bold text-xs sm:text-sm {{ $isPastArrival ? 'text-[#dc2626] dark:text-[#f87171]' : 'text-[#183d28] dark:text-[#e8f5e9]' }}">{{ \Carbon\Carbon::parse($reservation->reservation_date)->format('M j, Y') }} – {{ \Carbon\Carbon::parse($reservation->end_date)->format('M j, Y') }}</span>
                                                <div class="text-[0.7rem] text-[#718076] dark:text-[#9baaa1]">({{ $totalDays }} {{ $totalDays > 1 ? 'Days Stay' : 'Day Stay' }})</div>
                                                @if ($isPastArrival)
                                                    <div class="text-[0.68rem] font-semibold text-[#dc2626] dark:text-[#f87171] mt-0.5">⚠️ Overdue Arrival</div>
                                                @endif
                                            </div>
                                        @else
                                            <div>
                                                <span class="font-bold text-xs sm:text-sm {{ $isPastArrival ? 'text-[#dc2626] dark:text-[#f87171]' : 'text-[#183d28] dark:text-[#e8f5e9]' }}">{{ \Carbon\Carbon::parse($reservation->reservation_date)->format('M j, Y') }}</span>
                                                <div class="text-[0.7rem] text-[#718076] dark:text-[#9baaa1]">(1 Day Stay)</div>
                                                @if ($isPastArrival)
                                                    <div class="text-[0.68rem] font-semibold text-[#dc2626] dark:text-[#f87171] mt-0.5">⚠️ Overdue Arrival</div>
                                                @endif
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
                                                'No Show', 'No show' => 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800/50 dark:text-slate-300 dark:border-slate-700',
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
                                <option value="No Show">No Show</option>
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

                    <div id="checkInPrimaryGuestPoolWrap" class="mt-2 flex items-center justify-between rounded-xl border border-sky-500/30 bg-sky-500/10 p-3">
                        <div class="flex items-center gap-2">
                            <span class="text-base">🏊</span>
                            <div>
                                <p class="m-0 text-xs font-bold text-sky-900 dark:text-sky-200">Primary Guest Pool Access</p>
                                <p class="m-0 text-[0.72rem] text-sky-700/80 dark:text-sky-300/80">Include pool pass for the main guest under specific pool policy</p>
                            </div>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" name="check_in_primary_guest[has_pool_access]" id="checkInPrimaryGuestHasPool" value="1" class="h-4 w-4 accent-hp-green cursor-pointer">
                        </label>
                    </div>
                </div>

                <div class="guest-form__section rounded-2xl border border-glass-border bg-hp-cream p-4 transition-colors duration-300 dark:bg-white/5">
                    <div class="guest-form__section-header mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h4 class="guest-form__section-title m-0 text-base font-bold text-hp-text dark:text-[#f3f4f6]">Companions</h4>
                            <p class="m-0 text-xs text-hp-text-muted" id="checkInCompanionCountBadge">0 companions added</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="toggleCheckInCompanionFilterBtn" class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-glass-border bg-glass px-3 py-2 text-xs font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong active:scale-[0.98]" title="Toggle companion search & filters">
                                <i class="bi bi-funnel text-xs text-hp-green"></i>
                                <span>Filter & Search</span>
                            </button>
                            <button type="button" class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-emerald-600/30 bg-hp-green px-4 py-2 text-xs font-bold text-white shadow-md transition-all duration-200 hover:bg-hp-green-dark hover:shadow-lg active:scale-[0.98]" id="checkInAddCompanionBtn">
                                <svg class="h-4 w-4 shrink-0 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                                <span>+ Add Companions</span>
                            </button>
                        </div>
                    </div>

                    <!-- Companions Filter & Search Bar (Default is hidden) -->
                    <div id="checkInCompanionFilterToolbar" class="mb-3 hidden flex-wrap items-center gap-2 rounded-xl border border-glass-border/70 bg-glass/70 p-2.5 transition-all animate-fade-in">
                        <!-- Search single companion by name -->
                        <div class="relative flex-1 min-w-[170px]">
                            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-hp-text-muted/70"></i>
                            <input type="text" id="checkInCompanionSearchInput" placeholder="Search single companion..." class="w-full rounded-xl border border-glass-border bg-glass py-1.5 pl-8 pr-3 text-xs text-hp-text placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                        </div>
                        
                        <!-- Filter bulk: Gender -->
                        <div class="w-auto min-w-[110px]">
                            <select id="checkInCompanionFilterGender" class="w-full rounded-xl border border-glass-border bg-glass px-2.5 py-1.5 text-xs font-medium text-hp-text focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                                <option value="">All Genders</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>

                        <!-- Filter bulk: Age Group -->
                        <div class="w-auto min-w-[125px]">
                            <select id="checkInCompanionFilterAgeGroup" class="w-full rounded-xl border border-glass-border bg-glass px-2.5 py-1.5 text-xs font-medium text-hp-text focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
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

                    <div id="checkInCompanionList" class="guest-companion-list grid gap-2 max-h-[290px] overflow-y-auto overflow-x-hidden pr-1"></div>
                    <div id="checkInCompanionHiddenFields"></div>
                </div>

                <div class="guest-form__section grid gap-3 rounded-2xl border border-glass-border bg-hp-cream p-4 transition-colors duration-300 dark:bg-white/5">
                    <div class="guest-form__section-header mb-1 flex items-center justify-between gap-2">
                        <h4 class="guest-form__section-title m-0 text-base font-bold text-hp-text dark:text-[#f3f4f6]">Entrance Fee</h4>
                        <span id="checkInEffectivePeriodBadge" class="inline-flex items-center rounded-full border border-glass-border bg-[rgba(255,152,0,0.15)] px-2.5 py-1 text-[0.72rem] font-bold uppercase tracking-[0.06em] text-[#e65100] dark:bg-[rgba(255,152,0,0.2)] dark:text-[#ffb74d]">—</span>
                    </div>

                    <div class="guest-form__field-group mb-1 grid gap-1.5">
                        <label class="guest-form__label text-sm font-semibold text-hp-text" for="checkInPoolOption">Pool Access Policy</label>
                        <select name="pool_option" id="checkInPoolOption" class="guest-form__select w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm font-semibold text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                            <option value="no_pool" selected>No Pool Access (Default • ₱0.00)</option>
                            <option value="specific">Specific Pool Access (Select Guests / Groups)</option>
                            <option value="all_paid">All Pool Access (Standard Rate)</option>
                            <option value="all_free">All Pool Access Free (Promo • ₱0.00)</option>
                        </select>
                        <input type="hidden" name="check_in_include_pool" id="checkInIncludePoolLegacy" value="0">
                        <p class="m-0 text-[0.72rem] text-hp-text-muted" id="checkInPoolOptionHelp">No pool fee will be charged for any guest in this reservation.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
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
                        <div class="rounded-xl border border-glass-border bg-glass px-3.5 py-2.5" id="checkInExtraHeadCard">
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Extra Head Fee</p>
                            <p class="m-0 text-[0.95rem] font-bold text-[#e65100] dark:text-[#ffb74d]" id="checkInExtraHeadSummary">₱0.00</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-hp-green/30 bg-[rgba(26,58,31,0.08)] px-3.5 py-2.5 dark:bg-[rgba(129,199,132,0.08)]">
                        <div>
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Entrance subtotal</p>
                            <p class="m-0 text-[1.05rem] font-extrabold text-hp-green" id="checkInEntranceTotal">₱0.00</p>
                        </div>
                        <div>
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Extra guest fee</p>
                            <p class="m-0 text-[1.05rem] font-extrabold text-[#e65100]" id="checkInExtraHeadTotal">₱0.00</p>
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

    <!-- UNIFIED TWO-COLUMN COMPANION MODAL -->
    <div class="guest-modal guest-modal--wide fixed inset-0 z-[1000] hidden items-center justify-center is-open:flex" id="checkInCompanionModal" aria-hidden="true">
        <div class="guest-modal__backdrop absolute inset-0 bg-black/60 dark:bg-black/80 backdrop-blur-sm" data-close-check-in-companion-modal="true"></div>
        <div class="guest-modal__content guest-modal__content--companion-unified relative z-[1] w-full max-h-[min(92vh,860px)] overflow-y-auto rounded-3xl bg-hp-cream p-6 shadow-2xl dark:bg-[rgba(26,30,28,0.98)] border border-glass-border" style="width: min(1360px, 95vw) !important; max-width: 1360px !important;" role="dialog" aria-modal="true" aria-labelledby="checkInCompanionModalTitle">
            <button type="button" class="guest-modal__close absolute right-4 top-4 cursor-pointer border-0 bg-transparent text-2xl text-hp-text hover:opacity-75 transition-opacity" data-close-check-in-companion-modal="true" aria-label="Close modal">&times;</button>
            
            <div class="guest-modal__header mb-4 flex items-center justify-between border-b border-glass-border pb-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-hp-green/15 text-hp-green">
                        <i class="bi bi-people-fill text-xl"></i>
                    </div>
                    <div>
                        <h3 id="checkInCompanionModalTitle" class="guest-modal__title m-0 font-display text-lg font-bold text-hp-text dark:text-[#f3f4f6]">Add Companions</h3>
                        <p class="m-0 text-xs text-hp-text-muted">Create single or bulk companions and review the list before applying to check-in</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- LEFT SIDE: COMPANION CREATOR FORMS (6 cols) -->
                <div class="lg:col-span-6 flex flex-col gap-3">
                    <!-- Tab Switching: Single vs Bulk -->
                    <div class="flex rounded-xl bg-black/5 dark:bg-white/5 p-1 border border-glass-border/40">
                        <button type="button" class="guest-form__tab guest-form__tab--active flex-1 cursor-pointer rounded-lg py-2 text-xs font-bold transition-all bg-hp-green text-white shadow-xs text-center" data-checkin-companion-tab="single">
                            <i class="bi bi-person me-1"></i> Single Companion
                        </button>
                        <button type="button" class="guest-form__tab flex-1 cursor-pointer rounded-lg py-2 text-xs font-semibold text-hp-text transition-all bg-transparent text-center hover:text-hp-green" data-checkin-companion-tab="bulk">
                            <i class="bi bi-people me-1"></i> Bulk Companions
                        </button>
                    </div>

                    <!-- SINGLE COMPANION FORM -->
                    <form id="checkInCompanionForm" class="guest-form--tab-content guest-form--tab-content--active grid gap-3" data-checkin-companion-content="single" action="#">
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
                            <div class="guest-form__field-group grid gap-1" id="checkInCompanionAmenityWrap" style="display: none;">
                                <label class="guest-form__label text-xs font-semibold text-hp-text">Assign to Amenity</label>
                                <select name="amenity_id" id="checkInCompanionAmenity" class="guest-form__select w-full rounded-xl border border-glass-border bg-glass px-3 py-2 text-xs font-semibold text-hp-text transition-colors focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]"></select>
                            </div>
                            <div class="flex items-center justify-between rounded-xl border border-glass-border bg-glass p-2.5" id="checkInCompanionPoolWrap">
                                <div class="flex items-center gap-2">
                                    <span class="text-base">🏊</span>
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
                    <form id="checkInBulkCompanionForm" class="guest-form--tab-content hidden grid gap-3" data-checkin-companion-content="bulk" action="#">
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

                            <div class="guest-form__field-group grid gap-1" id="checkInBulkCompanionAmenityWrap" style="display: none;">
                                <label class="guest-form__label text-xs font-semibold text-hp-text">Assign to Amenity</label>
                                <select name="amenity_id" id="checkInBulkCompanionAmenity" class="guest-form__select w-full rounded-xl border border-glass-border bg-glass px-3 py-2 text-xs font-semibold text-hp-text transition-colors focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]"></select>
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
                                <span>Add Bulk Companions</span>
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

    <!-- Duplicate Companion Warning Modal -->
    <div class="guest-modal hidden" id="duplicateCompanionModal" aria-hidden="true" style="z-index: 1065;">
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
    <div class="guest-modal hidden" id="removeCompanionConfirmModal" aria-hidden="true" style="z-index: 1070;">
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
    </div>           </div>
            </form>
        </div>
    </div>

    <x-staff_chatbot />

    <script>
        window.staffReservationData = @json($reservationData ?? []);
        window.ALL_AMENITIES = @json($allAmenities ?? []);
    </script>
</body>
</html>
