<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Dashboard ΓÇö Hinaguan Nature Park</title>
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
                <section class="mb-4 flex flex-wrap items-center justify-between gap-6 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass">
                    <div class="min-w-[200px] flex-1">
                        <h2 class="m-0 mb-1 font-display text-[clamp(1.1rem,2vw,1.5rem)] font-bold leading-[1.25] text-hp-green-dark dark:text-[#f3f4f6]">{{ $greeting }}, {{ session('auth_user.name') ?? 'Staff User' }}!</h2>
                        <p class="m-0 text-sm font-medium text-hp-text-muted">Welcome to the Hinaguan Nature Park Portal</p>
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
                                <span class="font-display text-[1.3rem] font-bold leading-[1.1] text-hp-green-dark dark:text-[#f3f4f6]">{{ round($weatherNow['temp_c'] ?? 0) }}┬░C</span>
                                <span class="text-[0.7rem] font-semibold text-hp-text-muted">{{ $weatherNow['condition'] ?? 'ΓÇö' }}</span>
                            </div>
                        </div>
                        @endif
                        <div class="shrink-0">
                            <span class="flex h-[60px] w-[60px] items-center justify-center rounded-xl bg-hp-green text-center text-[0.7rem] font-extrabold uppercase leading-[1.3] tracking-[0.06em] text-white shadow-[0_4px_12px_rgba(28,92,60,0.35)] max-[768px]:h-[50px] max-[768px]:w-[50px] max-[768px]:text-[0.6rem] dark:bg-[#178a52] dark:shadow-[0_4px_12px_rgba(23,138,82,0.4)]">PARK<br>OPEN</span>
                        </div>
                    </div>
                </section>

                {{-- ===== LIVE ANALYTICS: 2-column first card + 4 cards (5 total) ===== --}}
                <h3 class="mb-3 font-display text-base font-bold text-hp-text dark:text-[#f3f4f6]">Live Analytics</h3>
                @php $laPoolNoPct = 100 - $laPoolAccessPct; @endphp
                <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">

                    {{-- Card 1: Active Overview (Guests & Reservations with Walk-in vs Online) --}}
                    <div class="lg:col-span-2 flex flex-col gap-2.5 rounded-2xl border border-glass-border bg-glass p-3.5 shadow-glass transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_6px_20px_rgba(13,44,29,0.12)] dark:border-white/10 dark:bg-[#181b19]/80">
                        <div class="flex items-center justify-between">
                            <span class="text-[0.65rem] font-bold uppercase tracking-[0.08em] text-hp-text-muted">Active Overview</span>
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-[rgba(23,138,82,0.12)] text-hp-green">
                                <svg width="13" height="13" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1 rounded-lg bg-[rgba(23,138,82,0.06)] px-2.5 py-2 dark:bg-[rgba(23,138,82,0.12)]">
                                <div class="text-[0.6rem] font-semibold uppercase text-hp-text-muted">Guests</div>
                                <div class="mt-0.5 font-display text-xl font-extrabold leading-none text-[#1c5c3c] dark:text-[#f3f4f6]">{{ $laTotalLive }}</div>
                                <div class="text-[0.6rem] font-bold text-hp-green">On-site</div>
                            </div>
                            <div class="flex-1 rounded-lg bg-[rgba(23,138,82,0.06)] px-2.5 py-2 dark:bg-[rgba(23,138,82,0.12)]">
                                <div class="text-[0.6rem] font-semibold uppercase text-hp-text-muted">Reservations</div>
                                <div class="mt-0.5 font-display text-xl font-extrabold leading-none text-[#1c5c3c] dark:text-[#f3f4f6]">{{ $activeCheckedInCount }}</div>
                                <div class="text-[0.6rem] font-bold text-hp-green">Active</div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1 rounded-lg bg-[rgba(245,158,11,0.07)] px-2.5 py-2 dark:bg-[rgba(245,158,11,0.10)]">
                                <div class="text-[0.6rem] font-semibold uppercase text-[#f59e0b]">Walk-in</div>
                                <div class="mt-0.5 text-lg font-extrabold text-[#1c5c3c] dark:text-[#f3f4f6]">{{ $activeWalkInGuests }} / {{ $activeWalkInReservations }}</div>
                                <div class="text-[0.6rem] font-bold text-[#f59e0b]">Guests / Res</div>
                            </div>
                            <div class="flex-1 rounded-lg bg-[rgba(59,130,246,0.07)] px-2.5 py-2 dark:bg-[rgba(59,130,246,0.10)]">
                                <div class="text-[0.6rem] font-semibold uppercase text-[#3b82f6]">Online</div>
                                <div class="mt-0.5 text-lg font-extrabold text-[#1c5c3c] dark:text-[#f3f4f6]">{{ $activeOnlineGuests }} / {{ $activeOnlineReservations }}</div>
                                <div class="text-[0.6rem] font-bold text-[#3b82f6]">Guests / Res</div>
                            </div>
                        </div>
                        <div class="mt-auto flex items-center gap-1 text-[0.6rem] font-semibold text-hp-green">
                            <span class="inline-block h-1 w-1 animate-pulse rounded-full bg-hp-green"></span>
                            Live count
                        </div>
                    </div>

                    {{-- Card 2: Demographics --}}
                    <div class="flex flex-col gap-2.5 rounded-2xl border border-glass-border bg-glass p-3.5 shadow-glass transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_6px_20px_rgba(13,44,29,0.12)] dark:border-white/10 dark:bg-[#181b19]/80">
                        <div class="flex items-center justify-between">
                            <span class="text-[0.65rem] font-bold uppercase tracking-[0.08em] text-hp-text-muted">Demographics</span>
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-[rgba(42,106,143,0.12)] text-[#2a6a8f]">
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
                    </div>

                    {{-- Card 3: Age Groups --}}
                    <div class="flex flex-col gap-2 rounded-2xl border border-glass-border bg-glass p-3.5 shadow-glass transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_6px_20px_rgba(13,44,29,0.12)] dark:border-white/10 dark:bg-[#181b19]/80">
                        <div class="flex items-center justify-between">
                            <span class="text-[0.65rem] font-bold uppercase tracking-[0.08em] text-hp-text-muted">Age Groups</span>
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-[rgba(168,85,247,0.12)] text-[#7c3aed]">
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
                    </div>

                    {{-- Card 4: Pool Activity --}}
                    <div class="flex flex-col gap-2.5 rounded-2xl border border-glass-border bg-glass p-3.5 shadow-glass transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_6px_20px_rgba(13,44,29,0.12)] dark:border-white/10 dark:bg-[#181b19]/80">
                        <div class="flex items-center justify-between">
                            <span class="text-[0.65rem] font-bold uppercase tracking-[0.08em] text-hp-text-muted">Pool Activity</span>
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-[rgba(2,132,199,0.12)] text-[#0284c7]">
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
                    </div>

                    {{-- Card 5: Checkout Alerts --}}
                    <div class="flex flex-col gap-2.5 rounded-2xl border p-3.5 shadow-glass transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_6px_20px_rgba(13,44,29,0.12)] {{ $dashboardGuestsDue > 0 ? 'border-[rgba(225,29,72,0.35)] bg-[rgba(225,29,72,0.03)]' : 'border-glass-border bg-glass dark:border-white/10 dark:bg-[#181b19]/80' }}">
                        <div class="flex items-center justify-between">
                            <span class="text-[0.65rem] font-bold uppercase tracking-[0.08em] {{ $dashboardGuestsDue > 0 ? 'text-[#e11d48]' : 'text-hp-text-muted' }}">Checkout Alerts</span>
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg {{ $dashboardGuestsDue > 0 ? 'bg-[rgba(225,29,72,0.12)] text-[#e11d48]' : 'bg-[rgba(245,158,11,0.12)] text-[#b45309]' }}">
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
                    </div>

                </div>{{-- /.analytics grid --}}

                {{-- ===== VISITOR PREDICTION - always visible below analytics ===== --}}
                @if (isset($predictionReport))
                    <div class="mb-4">
                        <x-visitor_prediction_card :predictionReport="$predictionReport" :isStaff="true" />
                    </div>
                @endif

                {{-- ===== MAIN GRID: Chart | Donut ===== --}}
                <div class="mb-4 grid grid-cols-1 items-stretch gap-4 lg:grid-cols-[minmax(0,1fr)_260px]">
                    {{-- LEFT: Area Chart --}}
                    <section class="sd-chart-panel flex flex-col overflow-hidden rounded-2xl border border-glass-border bg-glass shadow-glass">
                        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-glass-border px-5 py-4">
                            <h3 class="m-0 font-display text-base font-bold text-hp-text dark:text-[#f3f4f6]">Bookings &amp; Revenue &ndash; Last 7 Days</h3>
                            <div class="flex gap-4">
                                <span class="sd-legend-item inline-flex items-center gap-1.5 text-[0.72rem] font-semibold text-hp-text-muted">
                                    <i class="sd-legend-swatch sd-legend-swatch--bookings inline-block h-[0.7rem] w-[0.7rem] rounded-[0.2rem] bg-hp-green"></i>Bookings
                                </span>
                                <span class="sd-legend-item inline-flex items-center gap-1.5 text-[0.72rem] font-semibold text-hp-text-muted">
                                    <i class="sd-legend-swatch sd-legend-swatch--revenue inline-block h-[0.7rem] w-[0.7rem] rounded-[0.2rem] bg-[#c8a45d]"></i>Revenue
                                </span>
                            </div>
                        </div>
                        <div class="sd-area-chart relative min-h-[240px] flex-1 p-4" id="sdAreaChart">
                            <canvas id="sdAreaChartCanvas" class="h-full w-full"></canvas>
                        </div>
                        <script>
                            window.__sdChartData = {
                                labels: @json($weekDays),
                                bookings: @json($weekReservationCounts),
                            };
                            window.__sdChartData_revenueRaw = @json($weekRevenue);
                        </script>
                    </section>

                    {{-- RIGHT: Donut --}}
                    <section class="sd-donut-panel flex flex-col overflow-hidden rounded-2xl border border-glass-border bg-glass shadow-glass">
                        <div class="border-b border-glass-border px-5 py-4">
                            <h3 class="m-0 font-display text-base font-bold text-hp-text dark:text-[#f3f4f6]">Reservation Status</h3>
                        </div>
                        <div class="sd-donut-panel__body flex flex-1 flex-col items-center justify-center gap-4 p-5">
                            @if ($donutTotal > 0)
                                <div class="sd-donut-ring flex h-[150px] w-[150px] shrink-0 items-center justify-center rounded-full shadow-[inset_0_0_0_1px_rgba(13,44,29,0.06)] max-[768px]:h-[120px] max-[768px]:w-[120px]" style="{{ $donutStyle }}">
                                    <div class="sd-donut-ring__hole flex h-[90px] w-[90px] flex-col items-center justify-center rounded-full bg-glass max-[768px]:h-[72px] max-[768px]:w-[72px]">
                                        <span class="sd-donut-ring__total font-display text-[1.75rem] font-bold leading-none text-hp-green-dark dark:text-[#f3f4f6]">{{ $donutTotal }}</span>
                                        <small class="mt-0.5 text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-hp-text-muted">Total</small>
                                    </div>
                                </div>
                                <ul class="sd-donut-legend m-0 flex w-full list-none flex-col gap-1.5 p-0">
                                    @foreach ($statusBreakdown as $status => $count)
                                        <li class="flex items-center gap-2 text-[0.75rem] font-medium text-hp-text">
                                             <i class="inline-block h-[0.6rem] w-[0.6rem] shrink-0 rounded-full" style="background: {{ $donutColors[$status] ?? '#c8a45d' }}"></i>
                                            {{ $status }}
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
                            <h3 class="dash-panel__title m-0 font-display text-base font-bold text-hp-text dark:text-[#f3f4f6]">Most Booked Amenities</h3>
                        </div>
                        @if ($topAmenities->isNotEmpty())
                            <div class="dash-hbars grid gap-4 p-5">
                                @foreach ($topAmenities as $amenity)
                                    <div class="dash-hbar">
                                        <div class="dash-hbar__row mb-1.5 flex items-baseline justify-between text-[0.8125rem] text-hp-text">
                                            <span>{{ $amenity['name'] }}</span>
                                            <strong class="text-hp-green-dark dark:text-[#f3f4f6]">{{ $amenity['total'] }}</strong>
                                        </div>
                                        <div class="dash-hbar__track h-2 overflow-hidden rounded-full bg-glass-hover dark:bg-[#0d2812]">
                                            <div class="dash-hbar__fill h-full w-0 rounded-full bg-gradient-to-r from-hp-green-mid to-hp-green-dark transition-[width] duration-[600ms] ease-[cubic-bezier(0.22,0.8,0.32,1)] [&.is-animated]:w-[var(--val)]" style="--val: {{ round($amenity['total'] / $topAmenityMax * 100) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="dash-chart__empty m-0 px-5 py-4 text-sm text-hp-text-muted">No amenity bookings yet.</p>
                        @endif
                    </section>

                    <section class="dash-panel overflow-visible rounded-2xl border border-glass-border bg-glass shadow-glass">
                        <div class="dash-panel__head border-b border-glass-border px-5 py-4">
                            <h3 class="dash-panel__title m-0 font-display text-base font-bold text-hp-text dark:text-[#f3f4f6]">Today's Expected Arrivals</h3>
                        </div>
                        <ul class="dash-arrivals m-0 list-none p-4">
                            @forelse ($todayArrivals as $arrival)
                                <li class="dash-arrival flex items-center gap-3.5 border-b border-glass-border py-3.5 last:border-none">
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