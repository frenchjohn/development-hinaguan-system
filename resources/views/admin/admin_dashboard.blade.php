<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard — Hinaguan Nature Park</title>
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
        'resources/css/admin_css/admin_shared.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/admin_js/admin_dashboard.js',
        'resources/js/admin_chatbot.js',
    ])
    <style>
        body.admin-portal {
            background-color: #ebf3ec !important;
        }
        [data-theme="dark"] body.admin-portal {
            background-color: #0f1110 !important;
        }
        body.admin-portal .dash-layout,
        body.admin-portal .dash-main,
        body.admin-portal .dash-content {
            background: transparent !important;
            background-color: transparent !important;
            background-image: none !important;
        }
        body.admin-portal .dash-main {
            position: relative !important;
            min-height: 100vh;
            z-index: 0;
        }
        body.admin-portal .dash-main::before {
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
            background-image: linear-gradient(rgba(255, 255, 255, 0.4), rgba(255, 255, 255, 0.3)), url('{{ asset('storage/design_images/staff-admin-background-image.jpeg') }}') !important;
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
            body.admin-portal .dash-main::before {
                left: 0 !important;
            }
        }
        [data-theme="dark"] body.admin-portal .dash-main::before {
            background-color: #0f1110 !important;
            background-image: linear-gradient(rgba(15, 17, 16, 0.94), rgba(15, 17, 16, 0.97)), url('{{ asset('storage/design_images/staff-admin-background-image.jpeg') }}') !important;
            filter: none !important;
            -webkit-filter: none !important;
            opacity: 1 !important;
        }
        body.admin-portal .dash-content {
            position: relative !important;
            z-index: 1 !important;
        }
        body.admin-portal [class*="backdrop-blur"] {
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
    </style>
</head>
<body class="antialiased admin-portal">
    <div class="dash-layout">
        <x-admin_sidemenu active="dashboard" userName="{{ session('auth_user.name') ?? 'Admin User' }}" userRole="Admin" />

        <div class="dash-main">
            <x-header
                title="Admin Dashboard"
                subtitle="Overview of park operations, reservations, revenue, and analytics"
                :showWelcome="true"
            />

            <main class="dash-content p-6">

                @php
                    $hour = (int) now()->format('G');
                    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');

                    $weatherService = app(\App\Services\WeatherService::class);
                    $weatherForecast = $weatherService->getMultiDayForecast(3);
                    $weatherNow = $weatherForecast['now'] ?? null;

                    $donutColors = [
                        'Pending' => '#c8a45d',
                        'Confirmed' => '#4c9a5f',
                        'Checked In' => '#2f6f45',
                        'Checked Out' => '#9ca3af',
                        'Cancelled' => '#d64550',
                    ];
                    $donutTotal = array_sum($statusBreakdown);
                    $donutStart = 0;
                    $donutStops = [];
                    foreach ($statusBreakdown as $status => $count) {
                        $pct = $donutTotal > 0 ? ($count / $donutTotal) * 360 : 0;
                        $color = $donutColors[$status] ?? '#c8a45d';
                        $donutStops[] = "{$color} {$donutStart}deg " . ($donutStart + $pct) . 'deg';
                        $donutStart += $pct;
                    }
                    $donutStyle = $donutTotal > 0 ? 'background: conic-gradient(' . implode(', ', $donutStops) . ');' : '';
                    $revenueMax = max(1, max($weekRevenue));
                @endphp

                {{-- ===== GREETING BANNER ===== --}}
                <section class="mb-5 flex flex-wrap items-center justify-between gap-6 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass">
                    <div class="min-w-[200px] flex-1">
                        <h2 class="m-0 mb-1 font-display text-[clamp(1.1rem,2vw,1.5rem)] font-bold leading-[1.25] text-hp-green-dark dark:text-[#f3f4f6]">{{ $greeting }}, {{ session('auth_user.name') ?? 'Admin User' }}!</h2>
                        <p class="m-0 text-sm font-medium text-hp-text-muted">Welcome to Hinaguan Nature Park · Admin Portal Overview</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-6">
                        <div class="flex flex-col items-end gap-0.5 max-[992px]:items-start">
                            <span class="text-[0.8rem] font-medium text-hp-text-muted">{{ \Carbon\Carbon::now()->format('l, F j, Y') }}</span>
                            <span class="font-display text-2xl font-bold leading-none text-hp-green-dark dark:text-[#f3f4f6]" id="sdLiveClock">{{ \Carbon\Carbon::now()->format('g:i A') }}</span>
                        </div>
                        @if ($weatherNow)
                        <div class="flex items-center gap-2">
                            @if (!empty($weatherNow['icon']))
                                <img src="{{ $weatherNow['icon'] }}" alt="" class="h-10 w-10 object-contain">
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-9 w-9 text-[#c8a45d]"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            @endif
                            <div class="flex flex-col items-center">
                                <span class="font-display text-[1.3rem] font-bold leading-[1.1] text-hp-green-dark dark:text-[#f3f4f6]">{{ round($weatherNow['temp_c'] ?? 0) }}°C</span>
                                <span class="text-[0.7rem] font-semibold text-hp-text-muted">{{ $weatherNow['condition'] ?? '—' }}</span>
                            </div>
                        </div>
                        @endif
                        <div class="shrink-0">
                            <span class="flex h-[60px] w-[60px] items-center justify-center rounded-xl bg-hp-green text-center text-[0.7rem] font-extrabold uppercase leading-[1.3] tracking-[0.06em] text-white shadow-[0_4px_12px_rgba(28,92,60,0.35)] max-[768px]:h-[50px] max-[768px]:w-[50px] max-[768px]:text-[0.6rem] dark:bg-[#178a52] dark:shadow-[0_4px_12px_rgba(23,138,82,0.4)]">PARK<br>OPEN</span>
                        </div>
                    </div>
                </section>

                {{-- ===== VISITOR PREDICTION & WEATHER INTELLIGENCE (TOP) ===== --}}
                @if (isset($predictionReport))
                    <x-visitor_prediction_card :predictionReport="$predictionReport" />
                @endif

                {{-- Section Title --}}
                <div class="mb-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-2 w-2 rounded-full bg-hp-green animate-pulse"></span>
                        <h3 class="m-0 text-xs font-bold uppercase tracking-wider text-hp-text-muted">Park Overview &amp; Key Performance Metrics</h3>
                    </div>
                </div>

                {{-- ===== STAT CARDS ===== --}}
                <div class="mb-6 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                    <a href="{{ route('admin.reports') }}" class="group flex items-center gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass backdrop-blur-[6px] transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-hp-green/30 cursor-pointer block no-underline text-inherit" title="View Reservation Reports">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#e7f3ec] text-[#1c5c3c] group-hover:bg-[#1c5c3c] group-hover:text-white transition-colors dark:bg-[#1e2220] dark:text-[#6ab88c]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted group-hover:text-hp-green dark:group-hover:text-emerald-400 transition-colors flex items-center justify-between">
                                <span>Total Reservations</span>
                                <i class="bi bi-arrow-up-right text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </p>
                            <p class="m-0 font-display text-2xl font-bold leading-tight text-hp-text">{{ $totalReservations }}</p>
                            <p class="m-0 text-xs text-hp-text-muted">All time bookings</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.reports') }}" class="group flex items-center gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass backdrop-blur-[6px] transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-hp-green/30 cursor-pointer block no-underline text-inherit" title="View Guest Demographics & Reports">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#eaf5e1] text-[#4b8022] group-hover:bg-[#4b8022] group-hover:text-white transition-colors dark:bg-[#213316] dark:text-[#96c76e]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted group-hover:text-hp-green dark:group-hover:text-emerald-400 transition-colors flex items-center justify-between">
                                <span>Total Guests</span>
                                <i class="bi bi-arrow-up-right text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </p>
                            <p class="m-0 font-display text-2xl font-bold leading-tight text-hp-text">{{ $totalGuests }}</p>
                            <p class="m-0 text-xs text-hp-text-muted">Total visitors booked</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.payment') }}" class="group flex items-center gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass backdrop-blur-[6px] transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-hp-green/30 cursor-pointer block no-underline text-inherit" title="View Payment Transactions">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#e5f0f6] text-[#2a6a8f] group-hover:bg-[#2a6a8f] group-hover:text-white transition-colors dark:bg-[#182c38] dark:text-[#6ea9c9]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted group-hover:text-hp-green dark:group-hover:text-emerald-400 transition-colors flex items-center justify-between">
                                <span>Monthly Revenue</span>
                                <i class="bi bi-arrow-up-right text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </p>
                            <p class="m-0 font-display text-2xl font-bold leading-tight text-hp-text">₱{{ number_format($currentMonthRevenue, 2) }}</p>
                            <p class="m-0 text-xs text-hp-text-muted">Collected this month</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.reports') }}" class="group flex items-center gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass backdrop-blur-[6px] transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-hp-green/30 cursor-pointer block no-underline text-inherit" title="View Expected Visitors in Reports">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#f0e9f4] text-[#6d4b8e] group-hover:bg-[#6d4b8e] group-hover:text-white transition-colors dark:bg-[#2b1f33] dark:text-[#a889c4]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted group-hover:text-hp-green dark:group-hover:text-emerald-400 transition-colors flex items-center justify-between">
                                <span>Today's Visitors</span>
                                <i class="bi bi-arrow-up-right text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </p>
                            <p class="m-0 font-display text-2xl font-bold leading-tight text-hp-text">{{ $todayVisitors }}</p>
                            <p class="m-0 text-xs text-hp-text-muted">Expected park arrivals</p>
                        </div>
                    </a>
                </div>

                {{-- ===== CHARTS SECTION ===== --}}
                <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-[2fr_1fr]">
                    {{-- Executive Financial & Booking Performance Analytics Chart --}}
                    <section class="flex flex-col rounded-2xl border border-glass-border bg-glass shadow-glass overflow-hidden">
                        {{-- Chart Header --}}
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-glass-border/60 px-5 py-3.5">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="m-0 text-base font-bold text-hp-text" id="sdAdminChartTitle">Financial Revenue Trends</h3>
                                    <span class="rounded bg-hp-green/10 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wider text-hp-green dark:text-emerald-400">7-Day Analysis</span>
                                </div>
                                <p class="m-0 text-xs text-hp-text-muted" id="sdAdminChartSubtitle">Daily collections breakdown across front-desk and online channels (PHP)</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2.5">
                                {{-- Dynamic Legend --}}
                                <div class="hidden sm:flex items-center gap-3 text-xs" id="sdAdminChartLegend">
                                    <span class="inline-flex items-center gap-1.5 text-[0.7rem] font-semibold text-hp-text-muted">
                                        <span class="h-2.5 w-2.5 rounded-full bg-[#1c5c3c] dark:bg-emerald-400 inline-block"></span> Total Revenue
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 text-[0.7rem] font-semibold text-hp-text-muted">
                                        <span class="h-2 w-2 rounded-sm bg-[#c8a45d] inline-block"></span> Front-Desk
                                    </span>
                                </div>

                                {{-- Mode Switcher Tabs --}}
                                <div class="inline-flex rounded-lg bg-black/5 p-0.5 dark:bg-white/5" role="tablist">
                                    <button type="button" id="sdTabRevenue" class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-[0.68rem] font-bold transition-all bg-white text-hp-text shadow-sm dark:bg-[#222723] dark:text-[#f3f4f6]" title="Revenue & Collections (PHP)">
                                        <i class="bi bi-cash-stack text-xs text-hp-green"></i> Revenue (₱)
                                    </button>
                                    <button type="button" id="sdTabBookings" class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-[0.68rem] font-semibold text-hp-text-muted transition-all hover:text-hp-text dark:hover:text-[#f3f4f6]" title="Bookings & Guest Attendance">
                                        <i class="bi bi-calendar-check text-xs"></i> Bookings &amp; Guests
                                    </button>
                                </div>

                                {{-- Direct Link to Payment / Reports --}}
                                <a href="{{ route('admin.payment') }}" id="sdAdminChartLink" class="inline-flex items-center gap-1 rounded-md border border-glass-border bg-black/[0.03] px-2.5 py-1 text-[0.68rem] font-bold text-hp-text-muted hover:border-hp-green hover:bg-hp-green/10 hover:text-hp-green dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6] dark:hover:border-emerald-400 dark:hover:text-emerald-400 transition-all" title="View Detailed Payments">
                                    <span id="sdAdminChartLinkLabel">Payments</span>
                                    <i class="bi bi-arrow-right text-[0.65rem]"></i>
                                </a>
                            </div>
                        </div>

                        {{-- Summary Metric Strip --}}
                        <div class="grid grid-cols-3 border-b border-glass-border/60 bg-black/[0.015] px-5 py-2.5 text-xs dark:border-white/5 dark:bg-white/[0.02]">
                            <div class="border-r border-glass-border/60 pr-3 dark:border-white/5">
                                <span class="block text-[0.62rem] font-semibold uppercase tracking-wider text-hp-text-muted" id="sdAdminStat1Label">7-Day Revenue</span>
                                <span class="font-display text-sm font-bold text-hp-text dark:text-[#f3f4f6]" id="sdAdminStat1Val">₱{{ number_format($total7DayRevenue, 2) }}</span>
                            </div>
                            <div class="border-r border-glass-border/60 px-3 dark:border-white/5">
                                <span class="block text-[0.62rem] font-semibold uppercase tracking-wider text-hp-text-muted" id="sdAdminStat2Label">Daily Average</span>
                                <span class="font-display text-sm font-bold text-hp-text dark:text-[#f3f4f6]" id="sdAdminStat2Val">₱{{ number_format($avgDailyRevenue, 2) }} <span class="text-[0.65rem] font-normal text-hp-text-muted">/ day</span></span>
                            </div>
                            <div class="pl-3">
                                <span class="block text-[0.62rem] font-semibold uppercase tracking-wider text-hp-text-muted" id="sdAdminStat3Label">Peak Day</span>
                                <span class="font-display text-sm font-bold text-hp-green dark:text-emerald-400" id="sdAdminStat3Val">{{ $peakRevDay }}</span>
                            </div>
                        </div>

                        {{-- Interactive Canvas Container with Tooltip --}}
                        <div class="relative h-[280px] w-full p-3.5" id="sdAdminChartWrap">
                            <canvas id="sdAreaChartCanvas" class="block h-full w-full cursor-crosshair"></canvas>
                            <div id="sdAdminChartTooltip" class="pointer-events-none absolute z-30 hidden -translate-x-1/2 rounded-xl border border-white/10 bg-[#0d2c1d]/95 px-3.5 py-2.5 text-white shadow-2xl backdrop-blur-md transition-opacity duration-150">
                                {{-- Dynamically populated on hover in JS --}}
                            </div>
                        </div>
                    </section>

                    {{-- Reservation Status Donut Chart --}}
                    <section class="flex flex-col rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
                        <div class="mb-4 flex items-center justify-between border-b border-glass-border/60 pb-3">
                            <a href="{{ route('admin.reports') }}" class="group flex items-center gap-1.5 hover:opacity-85 transition-opacity text-inherit no-underline" title="View Reservation Reports">
                                <h3 class="m-0 text-lg font-semibold text-hp-text group-hover:text-hp-green dark:group-hover:text-emerald-400 transition-colors flex items-center gap-1.5">
                                    <span>Reservation Status</span>
                                    <i class="bi bi-arrow-up-right text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                </h3>
                            </a>
                            <a href="{{ route('admin.reports') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-hp-green hover:underline dark:text-emerald-400" title="Go to Reports">
                                <span>Reports</span>
                                <i class="bi bi-arrow-right text-[0.65rem]"></i>
                            </a>
                        </div>
                        @if ($donutTotal > 0)
                            <div class="dash-donut-wrap my-auto">
                                <a href="{{ route('admin.reports') }}" class="dash-donut hover:scale-105 transition-transform cursor-pointer" style="{{ $donutStyle }}" title="View Reservation Reports">
                                    <div class="dash-donut__hole">
                                        <span class="dash-donut__total font-display text-2xl font-bold leading-none text-hp-text">{{ $donutTotal }}</span>
                                        <small class="text-xs uppercase text-hp-text-muted">Bookings</small>
                                    </div>
                                </a>
                                <ul class="dash-donut__legend">
                                    @foreach ($statusBreakdown as $status => $count)
                                        <li>
                                            <a href="{{ route('admin.reports') }}" class="flex items-center justify-between gap-3 text-inherit no-underline group/item hover:text-hp-green transition-colors" title="View {{ $status }} reservations in reports">
                                                <span class="flex items-center gap-1.5">
                                                    <i style="background: {{ $donutColors[$status] ?? '#c8a45d' }}"></i>
                                                    <span class="text-xs font-medium text-hp-text group-hover/item:text-hp-green dark:group-hover/item:text-emerald-400">{{ $status }}</span>
                                                </span>
                                                <strong class="text-xs font-bold text-hp-text group-hover/item:text-hp-green dark:group-hover/item:text-emerald-400">{{ $count }}</strong>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <p class="dash-chart__empty py-8 text-center text-sm text-hp-text-muted">No reservations recorded yet.</p>
                        @endif
                    </section>
                </div>

                {{-- ===== BOTTOM GRID ===== --}}
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    {{-- Recent Activity Table --}}
                    <section class="dash-panel flex flex-col rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
                        <div class="mb-4 flex items-center justify-between">
                            <a href="{{ route('admin.reports') }}" class="group/title text-inherit no-underline" title="View all reservations in Reports">
                                <div>
                                    <h3 class="m-0 text-lg font-semibold text-hp-text group-hover/title:text-hp-green dark:group-hover/title:text-emerald-400 transition-colors flex items-center gap-1.5">
                                        <span>Recent Activity</span>
                                        <i class="bi bi-arrow-up-right text-xs opacity-0 group-hover/title:opacity-100 transition-opacity"></i>
                                    </h3>
                                    <p class="m-0 text-xs text-hp-text-muted">Latest reservations created</p>
                                </div>
                            </a>
                            <a href="{{ route('admin.reports') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-hp-green hover:underline dark:text-emerald-400" title="View all reservations in Reports">
                                <span>View All</span>
                                <i class="bi bi-arrow-right text-[0.65rem]"></i>
                            </a>
                        </div>
                        <div class="dash-table-wrap overflow-x-auto">
                            <table class="dash-table w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-glass-border text-xs uppercase tracking-wider text-hp-text-muted">
                                        <th class="py-3 px-3">Guest</th>
                                        <th class="py-3 px-3">Date</th>
                                        <th class="py-3 px-3">Amenity</th>
                                        <th class="py-3 px-3">Status</th>
                                        <th class="py-3 px-2 w-6"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentReservations as $reservation)
                                        @php $initials = strtoupper(implode('', array_map(fn ($w) => $w[0] ?? '', array_slice(preg_split('/\s+/', trim($reservation->booker_name ?? '?')), 0, 2)))); @endphp
                                        <tr class="border-b border-glass-border/50 hover:bg-glass-hover/80 cursor-pointer transition-colors group/row" onclick="window.location.href='{{ route('admin.reports') }}'" title="View reservation in Reports">
                                            <td class="py-3 px-3">
                                                <span class="cell-person flex items-center gap-2">
                                                    <span class="cell-person__avatar flex h-7 w-7 items-center justify-center rounded-full bg-[#1c5c3c] text-xs font-bold text-white">{{ $initials ?: '?' }}</span>
                                                    <span class="cell-person__name font-medium text-hp-text group-hover/row:text-hp-green dark:group-hover/row:text-emerald-400 transition-colors">{{ $reservation->booker_name }}</span>
                                                </span>
                                            </td>
                                            <td class="mono-cell py-3 px-3 text-xs text-hp-text-muted">{{ $reservation->reservation_date ? \Illuminate\Support\Carbon::parse($reservation->reservation_date)->format('M d, Y') : 'TBD' }}</td>
                                            <td class="py-3 px-3 text-xs text-hp-text-muted">{{ $reservation->reservationAmenities->pluck('amenity.amenities_name')->filter()->join(', ') ?: 'None' }}</td>
                                            <td class="py-3 px-3">
                                                <span class="status-pill status-pill--{{ strtolower(str_replace(' ', '-', $reservation->status)) }} rounded-full px-2.5 py-1 text-[0.7rem] font-bold">{{ $reservation->status }}</span>
                                            </td>
                                            <td class="py-3 px-2 text-right">
                                                <i class="bi bi-chevron-right text-xs text-hp-text-muted opacity-0 group-hover/row:opacity-100 group-hover/row:translate-x-0.5 transition-all"></i>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-6 text-center text-sm text-hp-text-muted">No recent reservations yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>

                    {{-- Recent Operational Activity & Staff Audit Log --}}
                    <section class="dash-panel flex flex-col rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
                        <div class="mb-4 flex items-center justify-between">
                            <a href="{{ route('admin.users') }}" class="group/audit text-inherit no-underline" title="View Staff Management & Audit Logs">
                                <div>
                                    <h3 class="m-0 text-lg font-semibold text-hp-text group-hover/audit:text-hp-green dark:group-hover/audit:text-emerald-400 transition-colors flex items-center gap-2">
                                        <span>Recent Operational Activities</span>
                                        <span class="inline-flex items-center rounded-full bg-amber-500/15 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-300">Staff Audit</span>
                                        <i class="bi bi-arrow-up-right text-xs opacity-0 group-hover/audit:opacity-100 transition-opacity"></i>
                                    </h3>
                                    <p class="m-0 text-xs text-hp-text-muted">Live audit log of check-ins, checkouts, stay/amenity extensions, and mid-stay additions</p>
                                </div>
                            </a>
                            <a href="{{ route('admin.users') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-hp-green hover:underline dark:text-emerald-400 shrink-0 ml-2" title="Manage Staff Accounts">
                                <span>Manage Staff</span>
                                <i class="bi bi-arrow-right text-[0.65rem]"></i>
                            </a>
                        </div>

                        <div class="space-y-3 max-h-[380px] overflow-y-auto pr-1">
                            @forelse($recentActivities as $act)
                                @php
                                    $badgeColor = match($act->activity_type) {
                                        'check_in' => 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 border-emerald-500/30',
                                        'check_out', 'amenity_checked_out' => 'bg-slate-500/20 text-slate-600 dark:text-slate-300 border-slate-500/30',
                                        'stay_extended', 'amenity_extended' => 'bg-blue-500/20 text-blue-600 dark:text-blue-300 border-blue-500/30',
                                        'amenity_added' => 'bg-purple-500/20 text-purple-600 dark:text-purple-300 border-purple-500/30',
                                        'walkin_created' => 'bg-amber-500/20 text-amber-600 dark:text-amber-300 border-amber-500/30',
                                        'staff_created', 'staff_updated', 'staff_banned', 'staff_unbanned' => 'bg-rose-500/20 text-rose-600 dark:text-rose-300 border-rose-500/30',
                                        default => 'bg-hp-green-light/30 text-hp-green-dark dark:text-hp-green-light border-hp-green-mid/30',
                                    };
                                @endphp
                                <a href="{{ route('admin.users') }}" class="block text-inherit no-underline group/act" title="View details in Staff Management">
                                    <div class="flex items-start justify-between gap-3 rounded-xl border border-glass-border bg-glass-hover p-3.5 transition-all group-hover/act:border-hp-green-mid/60 group-hover/act:shadow-sm cursor-pointer">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wider {{ $badgeColor }}">
                                                    {{ $act->title }}
                                                </span>
                                                @if($act->actor_name)
                                                    <span class="text-xs font-semibold text-hp-text flex items-center gap-1">
                                                        <span class="text-hp-text-muted">by</span>
                                                        <span class="text-hp-green-dark dark:text-emerald-400 underline decoration-dotted">{{ $act->actor_name }}</span>
                                                        <span class="text-[0.65rem] px-1.5 py-0.2 rounded bg-glass border border-glass-border text-hp-text-muted uppercase">({{ $act->actor_role }})</span>
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-hp-text leading-relaxed m-0">{{ $act->description }}</p>
                                        </div>
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <span class="text-[0.7rem] font-medium text-hp-text-muted whitespace-nowrap">
                                                {{ $act->created_at ? $act->created_at->diffForHumans() : 'Recently' }}
                                            </span>
                                            <i class="bi bi-chevron-right text-xs text-hp-text-muted opacity-0 group-hover/act:opacity-100 group-hover/act:translate-x-0.5 transition-all"></i>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="py-6 text-center text-sm text-hp-text-muted">
                                    No operational activities logged yet.
                                </div>
                            @endforelse
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>

    {{-- Admin AI Intelligence Chatbot --}}
    <x-admin_chatbot />

    <script>
        window.__sdAdminOpsChartData = {
            labels: @json($weekDays),
            dates: @json($weekDates),
            fullDates: @json($weekFullDates),
            revenue: @json($weekRevenue),
            deskRevenue: @json($weekDeskRevenue),
            onlineRevenue: @json($weekOnlineRevenue),
            bookings: @json($weekBookings),
            guests: @json($weekGuests),
            totalRevenue: {{ (float) $total7DayRevenue }},
            avgDailyRevenue: {{ (float) $avgDailyRevenue }},
            peakRevDay: @json($peakRevDay),
            totalBookings: {{ (int) $total7DayBookings }},
            totalGuests: {{ (int) $total7DayGuests }},
            peakBookDay: @json($peakBookDay),
            routes: {
                payment: @json(route('admin.payment')),
                reports: @json(route('admin.reports'))
            }
        };
        // Backward compatibility
        window.__sdChartData = {
            labels: @json($weekDays),
            bookings: @json($weekRevenue),
            revenue: @json($weekRevenue)
        };
    </script>
</body>
</html>
