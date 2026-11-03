<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Dashboard — Hinaguan Nature Park</title>
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
        'resources/js/staff_js/staff_dashboard.js',
        'resources/js/staff_chatbot.js',
    ])
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
        <x-staff_sidemenu active="dashboard" userName="{{ session('auth_user.name') ?? 'Staff User' }}" userRole="Staff" />

        <div class="dash-main">
            <x-header
                title="Staff Dashboard"
                subtitle="Daily tasks and guest activity at the park"
                :showWelcome="true"
            />

            <main class="dash-content p-6">

                @php
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
                    $barMax = max(1, max($weekReservationCounts));
                    $revenueMax = max(1, max($weekRevenue));
                @endphp

                @php
                    $hour = (int) now()->format('G');
                    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');

                    $weatherService = app(\App\Services\WeatherService::class);
                    $weatherForecast = $weatherService->getMultiDayForecast(3);
                    $weatherNow = $weatherForecast['now'] ?? null;
                @endphp

                {{-- ===== GREETING BANNER ===== --}}
                <section class="mb-4 flex flex-wrap items-center justify-between gap-6 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass max-[768px]:flex-col max-[768px]:items-stretch max-[768px]:gap-3">
                    <div class="min-w-[200px] flex-1">
                        <h2 class="m-0 mb-1 font-display text-[clamp(1.1rem,2vw,1.5rem)] font-bold leading-[1.25] text-hp-green-dark dark:text-[#f3f4f6]">{{ $greeting }}, {{ session('auth_user.name') ?? 'Staff User' }}!</h2>
                        <p class="m-0 text-sm font-medium text-hp-text-muted">Welcome to the Hinaguan Nature Park Portal</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-6 max-[768px]:w-full max-[768px]:justify-between max-[768px]:gap-3">
                        <div class="flex min-w-0 flex-col items-end gap-0.5 max-[992px]:items-start">
                            <span class="whitespace-nowrap text-[0.8rem] font-medium text-hp-text-muted">{{ \Carbon\Carbon::now()->format('l, F j, Y') }}</span>
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
                                <span class="font-display text-[1.3rem] font-bold leading-[1.1] text-hp-green-dark dark:text-[#f3f4f6]">{{ round($weatherNow['temp_c'] ?? 0) }}&#176;C</span>
                                <span class="text-[0.7rem] font-semibold text-hp-text-muted">{{ $weatherNow['condition'] ?? '—' }}</span>
                            </div>
                        </div>
                        @endif
                        <div class="shrink-0">
                            <span class="flex h-[60px] w-[60px] items-center justify-center rounded-xl bg-hp-green text-center text-[0.7rem] font-extrabold uppercase leading-[1.3] tracking-[0.06em] text-white shadow-[0_4px_12px_rgba(28,92,60,0.35)] max-[768px]:h-[50px] max-[768px]:w-[50px] max-[768px]:text-[0.6rem] dark:bg-[#178a52] dark:shadow-[0_4px_12px_rgba(23,138,82,0.4)]">PARK<br>OPEN</span>
                        </div>
                    </div>
                </section>

                {{-- ===== LIVE ANALYTICS: 2-column first card + 4 cards (5 total) ===== --}}
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="m-0 font-display text-base font-bold text-hp-text dark:text-[#f3f4f6]">Live Analytics</h3>
                    <a href="{{ route('staff.checkins', ['tab' => 'dashboard']) }}#dashboardSection" class="inline-flex items-center gap-1 text-xs font-semibold text-hp-green hover:underline dark:text-emerald-400" title="Go to Analytics Dashboard in Check-Ins">
                        <span>Check-Ins Analytics</span>
                        <i class="bi bi-arrow-right text-[0.7rem]"></i>
                    </a>
                </div>
                @php
                    $checkinsDashboardUrl = route('staff.checkins', ['tab' => 'dashboard']);
                    $laPoolNoPct = 100 - $laPoolAccessPct;
                    $laWalkInGuestPct = $laTotalLive > 0 ? round(($activeWalkInGuests / $laTotalLive) * 100) : 0;
                    $laOnlineGuestPct = $laTotalLive > 0 ? max(0, 100 - $laWalkInGuestPct) : 0;
                    $laWalkInResPct = $activeCheckedInCount > 0 ? round(($activeWalkInReservations / $activeCheckedInCount) * 100) : 0;
                    $laOnlineResPct = $activeCheckedInCount > 0 ? max(0, 100 - $laWalkInResPct) : 0;
                @endphp
                <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">

                    {{-- Card 1: Active Overview Graph (Guests & Reservations with Walk-in vs Online) --}}
                    <div class="lg:col-span-2 flex flex-col justify-between rounded-2xl border border-glass-border bg-glass p-3.5 shadow-glass transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_6px_20px_rgba(13,44,29,0.12)] dark:border-white/10 dark:bg-[#181b19]/80">
                        {{-- Card Header --}}
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-glass-border/60 pb-2 dark:border-white/5">
                            <a href="{{ $checkinsDashboardUrl }}#dashboardSection" class="group/title flex items-center gap-2 text-inherit no-underline hover:opacity-85 transition-opacity" title="Open Analytics Dashboard in Check-Ins">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-[rgba(23,138,82,0.12)] text-hp-green group-hover/title:bg-hp-green group-hover/title:text-white transition-colors">
                                    <svg width="13" height="13" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[0.7rem] font-bold uppercase tracking-[0.08em] text-hp-text group-hover/title:text-hp-green dark:text-[#f3f4f6] dark:group-hover/title:text-emerald-400 transition-colors">Active Overview</span>
                                    <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-hp-green" title="Live"></span>
                                    <i class="bi bi-arrow-up-right text-[0.65rem] text-hp-text-muted opacity-0 group-hover/title:opacity-100 transition-opacity"></i>
                                </div>
                            </a>

                            {{-- Channel Legends & View Switcher --}}
                            <div class="flex items-center gap-2.5">
                                <div class="flex items-center gap-2 text-[0.62rem] font-semibold text-hp-text-muted">
                                    <span class="inline-flex items-center gap-1">
                                        <span class="h-2 w-2 rounded-full bg-[#f59e0b]"></span> Walk-in
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <span class="h-2 w-2 rounded-full bg-[#3b82f6]"></span> Online
                                    </span>
                                </div>
                                <div class="inline-flex rounded-lg bg-black/5 p-0.5 dark:bg-white/5" role="tablist" id="sdActiveOverviewTabs">
                                    <button type="button" id="sdTabSplit" class="sd-ao-tab-btn inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[0.65rem] font-bold transition-all bg-white text-hp-text shadow-sm dark:bg-[#222723] dark:text-[#f3f4f6]" title="Ratio View">
                                        <i class="bi bi-pie-chart-fill"></i> Ratio
                                    </button>
                                    <button type="button" id="sdTabColumns" class="sd-ao-tab-btn inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[0.65rem] font-semibold text-hp-text-muted transition-all hover:text-hp-text dark:hover:text-[#f3f4f6]" title="Bar Chart View">
                                        <i class="bi bi-bar-chart-fill"></i> Bars
                                    </button>
                                </div>
                                <a href="{{ $checkinsDashboardUrl }}#dashboardSection" class="inline-flex items-center gap-1 text-[0.65rem] font-semibold text-hp-green hover:underline dark:text-emerald-400" title="Open Analytics Dashboard">
                                    <span>Analytics Dashboard</span>
                                    <i class="bi bi-arrow-right text-[0.6rem]"></i>
                                </a>
                            </div>
                        </div>

                        {{-- VIEW 1: Split View (Donut Gauge + Channel Bars) --}}
                        <div id="sdActiveOverviewSplitView" class="grid grid-cols-1 items-center gap-3 py-2 sm:grid-cols-[115px_minmax(0,1fr)]">
                            {{-- Donut Gauge with Center Total --}}
                            <div class="relative flex flex-col items-center justify-center">
                                <div class="relative h-[105px] w-[105px]">
                                    <canvas id="activeOverviewDonutCanvas" class="h-full w-full"></canvas>
                                    <a href="{{ $checkinsDashboardUrl }}#dashboardSection" class="pointer-events-auto absolute inset-0 flex flex-col items-center justify-center text-center group/center hover:scale-105 transition-transform" title="Go to Analytics Dashboard in Check-Ins">
                                        <span class="font-display text-xl font-extrabold leading-none text-[#1c5c3c] group-hover/center:text-hp-green dark:text-[#f3f4f6] dark:group-hover/center:text-emerald-400 transition-colors">{{ $laTotalLive }}</span>
                                        <span class="mt-0.5 text-[0.55rem] font-bold uppercase tracking-wider text-hp-text-muted">On-Site</span>
                                    </a>
                                </div>
                            </div>

                            {{-- Comparative Channel Proportion Bars --}}
                            <div class="flex flex-col justify-center gap-2.5">
                                {{-- Guests Bar --}}
                                <div>
                                    <div class="mb-1 flex items-center justify-between text-[0.65rem]">
                                        <span class="font-bold text-hp-text dark:text-[#f3f4f6]">Guests</span>
                                        <span class="font-extrabold text-hp-green dark:text-[#4c9a5f]">{{ $laTotalLive }}</span>
                                    </div>
                                    <div class="flex h-2.5 w-full overflow-hidden rounded-full bg-black/5 p-0.5 dark:bg-white/10">
                                        @if($laTotalLive > 0)
                                             @if($activeWalkInGuests > 0)
                                                <div class="h-full rounded-l-full bg-gradient-to-r from-[#f59e0b] to-[#fbbf24] transition-all duration-500" style="width: {{ $laWalkInGuestPct }}%" title="Walk-in: {{ $activeWalkInGuests }} ({{ $laWalkInGuestPct }}%)"></div>
                                            @endif
                                            @if($activeOnlineGuests > 0)
                                                <div class="h-full {{ $activeWalkInGuests > 0 ? '' : 'rounded-l-full' }} rounded-r-full bg-gradient-to-r from-[#3b82f6] to-[#60a5fa] transition-all duration-500" style="width: {{ $laOnlineGuestPct }}%" title="Online: {{ $activeOnlineGuests }} ({{ $laOnlineGuestPct }}%)"></div>
                                            @endif
                                        @else
                                            <div class="h-full w-full rounded-full bg-slate-200 dark:bg-white/10"></div>
                                        @endif
                                    </div>
                                    <div class="mt-0.5 flex items-center justify-between text-[0.6rem] font-medium">
                                        <span class="text-[#f59e0b]">{{ $activeWalkInGuests }} Walk-in ({{ $laWalkInGuestPct }}%)</span>
                                        <span class="text-[#3b82f6]">{{ $activeOnlineGuests }} Online ({{ $laOnlineGuestPct }}%)</span>
                                    </div>
                                </div>

                                {{-- Reservations Bar --}}
                                <div>
                                    <div class="mb-1 flex items-center justify-between text-[0.65rem]">
                                        <span class="font-bold text-hp-text dark:text-[#f3f4f6]">Reservations</span>
                                        <span class="font-extrabold text-[#1c5c3c] dark:text-[#f3f4f6]">{{ $activeCheckedInCount }}</span>
                                    </div>
                                    <div class="flex h-2.5 w-full overflow-hidden rounded-full bg-black/5 p-0.5 dark:bg-white/10">
                                        @if($activeCheckedInCount > 0)
                                            @if($activeWalkInReservations > 0)
                                                <div class="h-full rounded-l-full bg-gradient-to-r from-[#f59e0b] to-[#fbbf24] transition-all duration-500" style="width: {{ $laWalkInResPct }}%" title="Walk-in: {{ $activeWalkInReservations }} ({{ $laWalkInResPct }}%)"></div>
                                            @endif
                                            @if($activeOnlineReservations > 0)
                                                <div class="h-full {{ $activeWalkInReservations > 0 ? '' : 'rounded-l-full' }} rounded-r-full bg-gradient-to-r from-[#3b82f6] to-[#60a5fa] transition-all duration-500" style="width: {{ $laOnlineResPct }}%" title="Online: {{ $activeOnlineReservations }} ({{ $laOnlineResPct }}%)"></div>
                                            @endif
                                        @else
                                            <div class="h-full w-full rounded-full bg-slate-200 dark:bg-white/10"></div>
                                        @endif
                                    </div>
                                    <div class="mt-0.5 flex items-center justify-between text-[0.6rem] font-medium">
                                        <span class="text-[#f59e0b]">{{ $activeWalkInReservations }} Walk-in ({{ $laWalkInResPct }}%)</span>
                                        <span class="text-[#3b82f6]">{{ $activeOnlineReservations }} Online ({{ $laOnlineResPct }}%)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- VIEW 2: Columns View (Canvas Grouped Bar Chart) --}}
                        <div id="sdActiveOverviewColumnsView" class="hidden py-1">
                            <div class="relative h-[125px] w-full">
                                <canvas id="activeOverviewBarCanvas" class="h-full w-full"></canvas>
                            </div>
                        </div>
                    </div>
                    <script>
                        window.__sdActiveOverviewData = {
                            totalGuests: {{ (int) $laTotalLive }},
                            walkInGuests: {{ (int) $activeWalkInGuests }},
                            onlineGuests: {{ (int) $activeOnlineGuests }},
                            totalReservations: {{ (int) $activeCheckedInCount }},
                            walkInReservations: {{ (int) $activeWalkInReservations }},
                            onlineReservations: {{ (int) $activeOnlineReservations }},
                        };
                    </script>

                    {{-- Card 2: Demographics --}}
                    <a href="{{ $checkinsDashboardUrl }}#widgetDemographics" class="group flex flex-col gap-2.5 rounded-2xl border border-glass-border bg-glass p-3.5 shadow-glass transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_6px_20px_rgba(13,44,29,0.12)] hover:border-hp-green/30 dark:border-white/10 dark:bg-[#181b19]/80 cursor-pointer block text-inherit no-underline" title="View Demographics in Check-Ins Analytics Dashboard">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-1.5 text-[0.65rem] font-bold uppercase tracking-[0.08em] text-hp-text-muted group-hover:text-hp-green dark:group-hover:text-emerald-400 transition-colors">
                                <span>Demographics</span>
                                <i class="bi bi-arrow-up-right text-[0.6rem] opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </span>
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-[rgba(42,106,143,0.12)] text-[#2a6a8f] group-hover:bg-[#2a6a8f] group-hover:text-white transition-colors">
                                <svg width="13" height="13" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1 rounded-lg bg-[rgba(42,106,143,0.06)] px-2.5 py-2 dark:bg-[rgba(42,106,143,0.12)]">
                                <div class="text-[0.6rem] font-semibold uppercase text-hp-text-muted">Male</div>
                                <div class="mt-0.5 font-display text-xl font-extrabold leading-none text-[#1c5c3c] dark:text-[#f3f4f6]">{{ $laDemoMale }}</div>
                                <div class="text-[0.6rem] font-bold text-[#2a6a8f]">{{ $laPctMale }}%</div>
                            </div>
                            <div class="flex-1 rounded-lg bg-[rgba(236,72,153,0.06)] px-2.5 py-2 dark:bg-[rgba(236,72,153,0.10)]">
                                <div class="text-[0.6rem] font-semibold uppercase text-hp-text-muted">Female</div>
                                <div class="mt-0.5 font-display text-xl font-extrabold leading-none text-[#1c5c3c] dark:text-[#f3f4f6]">{{ $laDemoFemale }}</div>
                                <div class="text-[0.6rem] font-bold text-[#ec4899]">{{ $laPctFemale }}%</div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1 rounded-lg bg-[rgba(139,92,246,0.07)] px-2.5 py-2 dark:bg-[rgba(139,92,246,0.12)]">
                                <div class="text-[0.6rem] font-semibold uppercase text-[#8b5cf6]">Filipino</div>
                                <div class="mt-0.5 text-lg font-extrabold text-[#1c5c3c] dark:text-[#f3f4f6]">{{ $laDemoFilipino }}</div>
                                <div class="text-[0.6rem] font-bold text-[#8b5cf6]">{{ $laPctFilipino }}%</div>
                            </div>
                            <div class="flex-1 rounded-lg bg-[rgba(245,158,11,0.07)] px-2.5 py-2 dark:bg-[rgba(245,158,11,0.10)]">
                                <div class="text-[0.6rem] font-semibold uppercase text-[#f59e0b]">Foreign</div>
                                <div class="mt-0.5 text-lg font-extrabold text-[#1c5c3c] dark:text-[#f3f4f6]">{{ $laDemoForeign }}</div>
                                <div class="text-[0.6rem] font-bold text-[#f59e0b]">{{ $laPctForeign }}%</div>
                            </div>
                        </div>
                    </a>

                    {{-- Card 3: Age Groups --}}
                    <a href="{{ $checkinsDashboardUrl }}#widgetAgeGroups" class="group flex flex-col gap-2 rounded-2xl border border-glass-border bg-glass p-3.5 shadow-glass transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_6px_20px_rgba(13,44,29,0.12)] hover:border-hp-green/30 dark:border-white/10 dark:bg-[#181b19]/80 cursor-pointer block text-inherit no-underline" title="View Age Groups in Check-Ins Analytics Dashboard">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-1.5 text-[0.65rem] font-bold uppercase tracking-[0.08em] text-hp-text-muted group-hover:text-hp-green dark:group-hover:text-emerald-400 transition-colors">
                                <span>Age Groups</span>
                                <i class="bi bi-arrow-up-right text-[0.6rem] opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </span>
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-[rgba(168,85,247,0.12)] text-[#7c3aed] group-hover:bg-[#7c3aed] group-hover:text-white transition-colors">
                                <svg width="13" height="13" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-[0.65rem] font-semibold text-hp-text-muted">Kids (&lt;=12)</span>
                                    <span class="text-[0.7rem] font-extrabold text-[#22c55e]">{{ $laAgeKids }} <span class="text-[0.6rem] font-semibold text-hp-text-muted">{{ $laPctKids }}%</span></span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-black/5 dark:bg-white/10"><div class="h-full rounded-full bg-[#22c55e]" style="width:{{ $laPctKids }}%;"></div></div>
                            </div>
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-[0.65rem] font-semibold text-hp-text-muted">Teens (13-17)</span>
                                    <span class="text-[0.7rem] font-extrabold text-[#3b82f6]">{{ $laAgeTeen }} <span class="text-[0.6rem] font-semibold text-hp-text-muted">{{ $laPctTeen }}%</span></span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-black/5 dark:bg-white/10"><div class="h-full rounded-full bg-[#3b82f6]" style="width:{{ $laPctTeen }}%;"></div></div>
                            </div>
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-[0.65rem] font-semibold text-hp-text-muted">Adults (18-59)</span>
                                    <span class="text-[0.7rem] font-extrabold text-[#a855f7]">{{ $laAgeAdult }} <span class="text-[0.6rem] font-semibold text-hp-text-muted">{{ $laPctAdult }}%</span></span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-black/5 dark:bg-white/10"><div class="h-full rounded-full bg-[#a855f7]" style="width:{{ $laPctAdult }}%;"></div></div>
                            </div>
                            <div>
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-[0.65rem] font-semibold text-hp-text-muted">Seniors (60+)</span>
                                    <span class="text-[0.7rem] font-extrabold text-[#f59e0b]">{{ $laAgeSenior }} <span class="text-[0.6rem] font-semibold text-hp-text-muted">{{ $laPctSenior }}%</span></span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-black/5 dark:bg-white/10"><div class="h-full rounded-full bg-[#f59e0b]" style="width:{{ $laPctSenior }}%;"></div></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 text-[0.6rem] font-semibold text-hp-text-muted">
                            <span class="inline-block h-1 w-1 animate-pulse rounded-full bg-hp-green"></span>
                            {{ $laTotalLive }} on-site now
                        </div>
                    </a>

                    {{-- Card 4: Pool Activity --}}
                    <a href="{{ $checkinsDashboardUrl }}#widgetPool" class="group flex flex-col gap-2.5 rounded-2xl border border-glass-border bg-glass p-3.5 shadow-glass transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_6px_20px_rgba(13,44,29,0.12)] hover:border-[#0284c7]/30 dark:border-white/10 dark:bg-[#181b19]/80 cursor-pointer block text-inherit no-underline" title="View Pool Activity in Check-Ins Analytics Dashboard">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-1.5 text-[0.65rem] font-bold uppercase tracking-[0.08em] text-hp-text-muted group-hover:text-[#0284c7] transition-colors">
                                <span>Pool Activity</span>
                                <i class="bi bi-arrow-up-right text-[0.6rem] opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </span>
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-[rgba(2,132,199,0.12)] text-[#0284c7] group-hover:bg-[#0284c7] group-hover:text-white transition-colors">
                                <svg width="13" height="13" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 16.5c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0"/></svg>
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-[#0284c7]"></span>
                            <span class="text-[0.7rem] font-bold text-[#0284c7]">{{ $laPoolWith }} Active Swimmer{{ $laPoolWith !== 1 ? 's' : '' }}</span>
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1 rounded-lg bg-[rgba(2,132,199,0.07)] px-2.5 py-2 dark:bg-[rgba(2,132,199,0.12)]">
                                <div class="text-[0.6rem] font-semibold uppercase text-hp-text-muted">With Pass</div>
                                <div class="mt-0.5 font-display text-xl font-extrabold leading-none text-[#0284c7]">{{ $laPoolWith }}</div>
                                <div class="text-[0.6rem] font-bold text-[#0284c7]">{{ $laPoolAccessPct }}%</div>
                            </div>
                            <div class="flex-1 rounded-lg bg-black/[0.03] px-2.5 py-2 dark:bg-white/5">
                                <div class="text-[0.6rem] font-semibold uppercase text-hp-text-muted">No Pass</div>
                                <div class="mt-0.5 font-display text-xl font-extrabold leading-none text-hp-text-muted">{{ $laPoolWithout }}</div>
                                <div class="text-[0.6rem] font-bold text-hp-text-muted">{{ $laPoolNoPct }}%</div>
                            </div>
                        </div>
                        <div class="flex h-2 overflow-hidden rounded-full">
                            <div class="rounded-l-full bg-gradient-to-r from-[#0284c7] to-[#38bdf8]" style="width:{{ $laPoolAccessPct }}%;"></div>
                            <div class="rounded-r-full bg-slate-200 dark:bg-slate-700" style="width:{{ $laPoolNoPct }}%;"></div>
                        </div>
                    </a>

                    {{-- Card 5: Checkout Alerts --}}
                    <a href="{{ $checkinsDashboardUrl }}#widgetCheckoutAlerts" class="group flex flex-col gap-2.5 rounded-2xl border p-3.5 shadow-glass transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_6px_20px_rgba(13,44,29,0.12)] hover:border-hp-green/30 cursor-pointer block text-inherit no-underline {{ $dashboardGuestsDue > 0 ? 'border-[rgba(225,29,72,0.35)] bg-[rgba(225,29,72,0.03)]' : 'border-glass-border bg-glass dark:border-white/10 dark:bg-[#181b19]/80' }}" title="View Checkout Alerts in Check-Ins Analytics Dashboard">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-1.5 text-[0.65rem] font-bold uppercase tracking-[0.08em] {{ $dashboardGuestsDue > 0 ? 'text-[#e11d48]' : 'text-hp-text-muted' }} group-hover:text-hp-green dark:group-hover:text-emerald-400 transition-colors">
                                <span>Checkout Alerts</span>
                                <i class="bi bi-arrow-up-right text-[0.6rem] opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </span>
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg {{ $dashboardGuestsDue > 0 ? 'bg-[rgba(225,29,72,0.12)] text-[#e11d48]' : 'bg-[rgba(245,158,11,0.12)] text-[#b45309]' }} group-hover:scale-105 transition-transform">
                                <svg width="13" height="13" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1 rounded-lg px-2.5 py-2 {{ $dashboardGuestsDue > 0 ? 'bg-[rgba(225,29,72,0.07)]' : 'bg-black/[0.03] dark:bg-white/5' }}">
                                <div class="text-[0.6rem] font-semibold uppercase text-hp-text-muted">Overdue</div>
                                <div class="mt-0.5 flex items-center gap-1.5 font-display text-xl font-extrabold leading-none {{ $dashboardGuestsDue > 0 ? 'text-[#e11d48]' : 'text-[#1c5c3c] dark:text-[#f3f4f6]' }}">
                                    {{ $dashboardGuestsDue }}
                                    @if($dashboardGuestsDue > 0)<span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-[#e11d48]"></span>@endif
                                </div>
                                <div class="text-[0.6rem] font-bold {{ $dashboardGuestsDue > 0 ? 'text-[#e11d48]' : 'text-hp-text-muted' }}">{{ $dashboardResDue }} res. due</div>
                            </div>
                            <div class="flex-1 rounded-lg bg-[rgba(245,158,11,0.07)] px-2.5 py-2 dark:bg-[rgba(245,158,11,0.10)]">
                                <div class="text-[0.6rem] font-semibold uppercase text-hp-text-muted">Upcoming &lt;=1h</div>
                                <div class="mt-0.5 font-display text-xl font-extrabold leading-none {{ $dashboardNearCheckout > 0 ? 'text-[#f59e0b]' : 'text-hp-text-muted' }}">{{ $dashboardNearCheckout }}</div>
                                <div class="text-[0.6rem] font-bold text-[#f59e0b]">Near checkout</div>
                            </div>
                        </div>
                        @if($dashboardGuestsDue > 0 || $dashboardNearCheckout > 0)
                            <div class="flex flex-wrap gap-1">
                                @if($dashboardGuestsDue > 0)<span class="inline-flex items-center rounded-full bg-[rgba(225,29,72,0.12)] px-2 py-0.5 text-[0.6rem] font-bold text-[#e11d48]">! {{ $dashboardGuestsDue }} overdue</span>@endif
                                @if($dashboardNearCheckout > 0)<span class="inline-flex items-center rounded-full bg-[rgba(245,158,11,0.12)] px-2 py-0.5 text-[0.6rem] font-bold text-[#b45309]">~ {{ $dashboardNearCheckout }} soon</span>@endif
                            </div>
                        @else
                            <div class="text-[0.65rem] font-semibold text-hp-text-muted">All clear</div>
                        @endif
                    </a>

                </div>{{-- /.analytics grid --}}

                {{-- ===== VISITOR PREDICTION - always visible below analytics ===== --}}
                @if (isset($predictionReport))
                    <div class="mb-4">
                        <x-visitor_prediction_card :predictionReport="$predictionReport" :isStaff="true" />
                    </div>
                @endif

                {{-- ===== OPERATIONAL TRENDS ===== --}}
                <h3 class="mb-3 font-display text-base font-bold text-hp-text dark:text-[#f3f4f6]">Operational Trends</h3>

                {{-- ===== MAIN GRID: Chart | Donut ===== --}}
                <div class="mb-4 grid grid-cols-1 items-stretch gap-4 lg:grid-cols-[minmax(0,1fr)_260px]">
                    {{-- LEFT: Operations Chart (Guest Traffic & Check-Ins / Collections) --}}
                    <section class="sd-chart-panel flex flex-col overflow-hidden rounded-2xl border border-glass-border bg-glass shadow-glass transition-all duration-200 dark:border-white/10 dark:bg-[#181b19]/80">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-glass-border/70 px-5 py-3.5 dark:border-white/5">
                            <a href="{{ route('staff.reports') }}" class="group/hdr flex items-center gap-2.5 text-inherit no-underline hover:opacity-85 transition-opacity" title="View Full Analytics & Reports">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[rgba(23,138,82,0.12)] text-hp-green group-hover/hdr:bg-hp-green group-hover/hdr:text-white transition-colors dark:bg-emerald-950/60 dark:text-emerald-400">
                                    <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                </span>
                                <div>
                                    <h3 class="m-0 flex items-center gap-1.5 font-display text-sm sm:text-base font-bold text-hp-text group-hover/hdr:text-hp-green dark:text-[#f3f4f6] dark:group-hover/hdr:text-emerald-400 transition-colors" id="sdChartTitle">
                                        <span>Daily Guest Traffic &amp; Check-Ins</span>
                                        <i class="bi bi-arrow-up-right text-xs opacity-0 group-hover/hdr:opacity-100 transition-opacity"></i>
                                    </h3>
                                    <p class="m-0 text-[0.65rem] text-hp-text-muted" id="sdChartSubtitle">
                                        7-day operational volume of admitted guests and front-desk check-ins
                                    </p>
                                </div>
                            </a>

                            <div class="flex flex-wrap items-center gap-3">
                                {{-- Dynamic Legend --}}
                                <div id="sdChartLegend" class="flex items-center gap-3">
                                    <span class="sd-legend-item inline-flex items-center gap-1.5 text-[0.7rem] font-semibold text-hp-text-muted">
                                        <span class="h-2.5 w-2.5 rounded-full bg-[#16a34a] inline-block"></span> Guests Admitted
                                    </span>
                                    <span class="sd-legend-item inline-flex items-center gap-1.5 text-[0.7rem] font-semibold text-hp-text-muted">
                                        <span class="h-2.5 w-2.5 rounded-sm bg-[#0d9488] inline-block"></span> Check-Ins
                                    </span>
                                </div>

                                {{-- Mode Switcher Tabs --}}
                                <div class="inline-flex rounded-lg bg-black/5 p-0.5 dark:bg-white/5" role="tablist">
                                    <button type="button" id="sdTabTraffic" class="sd-mode-tab-btn inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-[0.68rem] font-bold transition-all bg-white text-hp-text shadow-sm dark:bg-[#222723] dark:text-[#f3f4f6]" title="Guest Traffic & Check-Ins">
                                        <i class="bi bi-people-fill text-xs"></i> Traffic
                                    </button>
                                    <button type="button" id="sdTabCollections" class="sd-mode-tab-btn inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-[0.68rem] font-semibold text-hp-text-muted transition-all hover:text-hp-text dark:hover:text-[#f3f4f6]" title="Front-Desk Collections">
                                        <i class="bi bi-cash-stack text-xs"></i> Collections
                                    </button>
                                </div>

                                {{-- Direct Link to Reports --}}
                                <a href="{{ route('staff.reports') }}" class="inline-flex items-center gap-1 rounded-md border border-glass-border bg-black/[0.03] px-2.5 py-1 text-[0.68rem] font-bold text-hp-text-muted hover:border-hp-green hover:bg-hp-green/10 hover:text-hp-green dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6] dark:hover:border-emerald-400 dark:hover:text-emerald-400 transition-all" title="Open Reports & Analytics">
                                    <span>Reports</span>
                                    <i class="bi bi-arrow-right text-[0.65rem]"></i>
                                </a>
                            </div>
                        </div>

                        {{-- Summary Metric Strip --}}
                        <div class="grid grid-cols-3 border-b border-glass-border/60 bg-black/[0.015] px-5 py-2 text-xs dark:border-white/5 dark:bg-white/[0.02]">
                            <div class="border-r border-glass-border/60 pr-3 dark:border-white/5">
                                <span class="block text-[0.62rem] font-semibold uppercase tracking-wider text-hp-text-muted" id="sdStat1Label">7-Day Guests</span>
                                <span class="font-display text-sm font-bold text-hp-text dark:text-[#f3f4f6]" id="sdStat1Val">{{ $totalWeekGuests }} <span class="text-[0.65rem] font-normal text-hp-text-muted">guests</span></span>
                            </div>
                            <div class="border-r border-glass-border/60 px-3 dark:border-white/5">
                                <span class="block text-[0.62rem] font-semibold uppercase tracking-wider text-hp-text-muted" id="sdStat2Label">7-Day Check-Ins</span>
                                <span class="font-display text-sm font-bold text-hp-text dark:text-[#f3f4f6]" id="sdStat2Val">{{ $totalWeekCheckIns }} <span class="text-[0.65rem] font-normal text-hp-text-muted">check-ins</span></span>
                            </div>
                            <div class="pl-3">
                                <span class="block text-[0.62rem] font-semibold uppercase tracking-wider text-hp-text-muted" id="sdStat3Label">Peak Activity</span>
                                <span class="font-display text-sm font-bold text-hp-green dark:text-emerald-400" id="sdStat3Val">{{ $peakDayLabel }}</span>
                            </div>
                        </div>

                        {{-- Interactive Canvas Container with Tooltip --}}
                        <div class="sd-area-chart relative min-h-[240px] flex-1 p-4" id="sdAreaChart">
                            <canvas id="sdAreaChartCanvas" class="h-full w-full cursor-crosshair"></canvas>
                            <div id="sdChartTooltip" class="pointer-events-none absolute z-20 hidden -translate-x-1/2 -translate-y-full rounded-xl border border-white/10 bg-[#0d2c1d]/95 px-3 py-2 text-white shadow-xl backdrop-blur-md transition-opacity duration-150">
                                {{-- Dynamically populated on hover in JS --}}
                            </div>
                        </div>

                        <script>
                            window.__sdOpsChartData = {
                                labels: @json($weekDays),
                                dates: @json($weekDates),
                                fullDates: @json($weekFullDates),
                                guests: @json($weekGuestsAdmitted),
                                checkIns: @json($weekCheckInCounts),
                                walkIns: @json($weekWalkInCheckIns),
                                online: @json($weekOnlineCheckIns),
                                deskCollections: @json($weekDeskCollections),
                                totalCollections: @json($weekTotalCollections),
                                totalGuests: {{ (int) $totalWeekGuests }},
                                totalCheckIns: {{ (int) $totalWeekCheckIns }},
                                totalCollectionsSum: {{ (float) $totalWeekDeskCollections }},
                                peakDay: @json($peakDayLabel),
                            };
                            // Backward compatibility
                            window.__sdChartData = {
                                labels: @json($weekDays),
                                bookings: @json($weekReservationCounts),
                            };
                            window.__sdChartData_revenueRaw = @json($weekRevenue);
                        </script>
                    </section>

                    {{-- RIGHT: Donut --}}
                    <section class="sd-donut-panel flex flex-col overflow-hidden rounded-2xl border border-glass-border bg-glass shadow-glass">
                        <div class="flex items-center justify-between border-b border-glass-border px-5 py-4">
                            <a href="{{ route('staff.reservations') }}" class="group flex items-center gap-1.5 font-display text-base font-bold text-hp-text text-inherit no-underline hover:text-hp-green dark:text-[#f3f4f6] dark:hover:text-emerald-400 transition-colors" title="Manage Reservations">
                                <span>Reservation Status</span>
                                <i class="bi bi-arrow-up-right text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </a>
                            <a href="{{ route('staff.reservations') }}" class="inline-flex items-center gap-1 text-[0.72rem] font-semibold text-hp-green hover:underline dark:text-emerald-400" title="Go to Reservations">
                                <span>Reservations</span>
                                <i class="bi bi-arrow-right text-[0.65rem]"></i>
                            </a>
                        </div>
                        <div class="sd-donut-panel__body flex flex-1 flex-col items-center justify-center gap-4 p-5">
                            @if ($donutTotal > 0)
                                <a href="{{ route('staff.reservations') }}" class="sd-donut-ring group/ring flex h-[150px] w-[150px] shrink-0 items-center justify-center rounded-full shadow-[inset_0_0_0_1px_rgba(13,44,29,0.06)] hover:scale-105 transition-transform max-[768px]:h-[120px] max-[768px]:w-[120px] cursor-pointer" style="{{ $donutStyle }}" title="View Reservations">
                                    <div class="sd-donut-ring__hole flex h-[90px] w-[90px] flex-col items-center justify-center rounded-full bg-glass group-hover/ring:bg-white/90 dark:group-hover/ring-[#222723] transition-colors max-[768px]:h-[72px] max-[768px]:w-[72px]">
                                        <span class="sd-donut-ring__total font-display text-[1.75rem] font-bold leading-none text-hp-green-dark group-hover/ring:text-hp-green dark:text-[#f3f4f6] dark:group-hover/ring:text-emerald-400 transition-colors">{{ $donutTotal }}</span>
                                        <small class="mt-0.5 text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-hp-text-muted">Total</small>
                                    </div>
                                </a>
                                <ul class="sd-donut-legend m-0 flex w-full list-none flex-col gap-1.5 p-0">
                                    @foreach ($statusBreakdown as $status => $count)
                                        <li class="flex items-center justify-between text-[0.75rem] font-medium text-hp-text">
                                            <span class="flex items-center gap-2">
                                                <i class="inline-block h-[0.6rem] w-[0.6rem] shrink-0 rounded-full" style="background: {{ $donutColors[$status] ?? '#c8a45d' }}"></i>
                                                {{ $status }}
                                            </span>
                                            <a href="{{ route('staff.reservations') }}" class="font-bold text-hp-text hover:text-hp-green dark:text-[#f3f4f6] dark:hover:text-emerald-400 transition-colors" title="Filter by {{ $status }}">
                                                {{ $count }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="sd-chart-empty m-0 p-4 text-center text-sm text-hp-text-muted">No reservations recorded yet.</p>
                            @endif
                        </div>
                    </section>
                </div>

                {{-- ===== BOTTOM ROW: Amenities | Arrivals ===== --}}
                <div class="sd-bottom-grid grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <section class="dash-panel dash-chart overflow-visible rounded-2xl border border-glass-border bg-glass shadow-glass">
                        <div class="dash-panel__head flex flex-wrap items-center justify-between gap-4 border-b border-glass-border px-5 py-4">
                            <a href="{{ route('staff.occupancy-monitor') }}" class="group flex items-center gap-1.5 font-display text-base font-bold text-hp-text text-inherit no-underline hover:text-hp-green dark:text-[#f3f4f6] dark:hover:text-emerald-400 transition-colors" title="Go to Occupancy Monitor">
                                <span class="dash-panel__title m-0">Most Booked Amenities</span>
                                <i class="bi bi-arrow-up-right text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </a>
                            <a href="{{ route('staff.occupancy-monitor') }}" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-[0.72rem] font-semibold text-hp-green hover:bg-hp-green/10 dark:text-emerald-400 dark:hover:bg-emerald-400/10 transition-colors" title="View Amenity Occupancy">
                                <span>Occupancy Monitor</span>
                                <i class="bi bi-arrow-right text-[0.65rem]"></i>
                            </a>
                        </div>
                        @if ($topAmenities->isNotEmpty())
                            <div class="dash-hbars grid gap-4 p-5">
                                @foreach ($topAmenities as $amenity)
                                    <a href="{{ route('staff.occupancy-monitor') }}" class="dash-hbar group/item block no-underline cursor-pointer" title="View {{ $amenity['name'] }} in Occupancy Monitor">
                                        <div class="dash-hbar__row mb-1.5 flex items-baseline justify-between text-[0.8125rem] text-hp-text group-hover/item:text-hp-green dark:group-hover/item:text-emerald-400 transition-colors">
                                            <span>{{ $amenity['name'] }}</span>
                                            <strong class="text-hp-green-dark group-hover/item:text-hp-green dark:text-[#f3f4f6] dark:group-hover/item:text-emerald-400 transition-colors">{{ $amenity['total'] }}</strong>
                                        </div>
                                        <div class="dash-hbar__track h-2 overflow-hidden rounded-full bg-glass-hover dark:bg-[#0d2812]">
                                            <div class="dash-hbar__fill h-full w-0 rounded-full bg-gradient-to-r from-hp-green-mid to-hp-green-dark transition-[width] duration-[600ms] ease-[cubic-bezier(0.22,0.8,0.32,1)] [&.is-animated]:w-[var(--val)]" style="--val: {{ round($amenity['total'] / $topAmenityMax * 100) }}%"></div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="dash-chart__empty m-0 px-5 py-4 text-sm text-hp-text-muted">No amenity bookings yet.</p>
                        @endif
                    </section>

                    <section class="dash-panel overflow-visible rounded-2xl border border-glass-border bg-glass shadow-glass">
                        <div class="dash-panel__head flex flex-wrap items-center justify-between gap-4 border-b border-glass-border px-5 py-4">
                            <a href="{{ route('staff.reservations') }}" class="group flex items-center gap-1.5 font-display text-base font-bold text-hp-text text-inherit no-underline hover:text-hp-green dark:text-[#f3f4f6] dark:hover:text-emerald-400 transition-colors" title="Manage Reservations">
                                <span class="dash-panel__title m-0">Today's Expected Arrivals</span>
                                <i class="bi bi-arrow-up-right text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </a>
                            <a href="{{ route('staff.reservations') }}" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-[0.72rem] font-semibold text-hp-green hover:bg-hp-green/10 dark:text-emerald-400 dark:hover:bg-emerald-400/10 transition-colors" title="Go to Reservations">
                                <span>Reservations</span>
                                <i class="bi bi-arrow-right text-[0.65rem]"></i>
                            </a>
                        </div>
                        <ul class="dash-arrivals m-0 list-none p-4">
                            @forelse ($todayArrivals as $arrival)
                                <li class="dash-arrival flex items-center gap-3.5 border-b border-glass-border py-3.5 last:border-none hover:bg-black/[0.02] dark:hover:bg-white/[0.02] transition-colors rounded-lg px-2 cursor-pointer" onclick="window.location.href='{{ route('staff.reservations') }}'" title="Click to view in Reservations">
                                    <span class="dash-arrival__dot h-[0.55rem] w-[0.55rem] shrink-0 rounded-full bg-hp-gold shadow-glass" aria-hidden="true"></span>
                                    <div class="dash-arrival__body min-w-0 flex-1">
                                        <p class="dash-arrival__name m-0 text-sm font-semibold text-hp-text">{{ $arrival->booker_name }}</p>
                                        <p class="dash-arrival__meta m-0 mt-0.5 text-xs text-hp-text-muted">{{ $arrival->number_of_guests }} guest(s) &middot;
                                            {{ \Carbon\Carbon::parse($arrival->reservation_date)->format('g:i A') }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-[0.6875rem] font-bold uppercase tracking-[0.05em] {{ $arrival->status === 'Pending' ? 'bg-[rgba(200,164,93,0.16)] text-[#8a6d2f]' : 'bg-[rgba(76,154,95,0.16)] text-[#2f6f45]' }}">{{ $arrival->status }}</span>
                                </li>
                            @empty
                                <li class="dash-arrival">
                                    <p class="dash-arrival__empty m-0 text-sm text-hp-text-muted">No arrivals expected today.</p>
                                </li>
                            @endforelse
                        </ul>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <x-staff_chatbot />
</body>
</html>