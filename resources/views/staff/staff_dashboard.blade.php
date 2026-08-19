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
    @vite([
        'resources/css/app.css',
        'resources/css/staff_css/staff_shared.css',
        'resources/css/homepage.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/staff_sidemenu.css',
        'resources/css/chatbot.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/staff_js/staff_dashboard.js',
        'resources/js/staff_chatbot.js',
    ])
</head>
<body class="antialiased staff-portal">
    <div class="dash-layout">
        <x-staff_sidemenu active="dashboard" userName="{{ session('auth_user.name') ?? 'Staff User' }}" userRole="Staff" />

        <div class="dash-main">

            <main class="dash-content p-6">
                <x-header
                    title="Staff Dashboard"
                    subtitle="Daily tasks and guest activity at the park"
                    :showWelcome="true"
                />

                @php
                    $donutColors = [
                        'Pending' => '#c8a45d',
                        'Confirmed' => '#4c9a5f',
                        'Checked In' => '#2f6f45',
                        'Checked Out' => '#94a3b8',
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
                        <h2 class="m-0 mb-1 font-display text-[clamp(1.1rem,2vw,1.5rem)] font-bold leading-[1.25] text-hp-green-dark dark:text-[#c8e6c8]">{{ $greeting }}, {{ session('auth_user.name') ?? 'Staff User' }}!</h2>
                        <p class="m-0 text-sm font-medium text-hp-text-muted">Welcome to the Hinaguan Nature Park Portal</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-6">
                        <div class="flex flex-col items-end gap-0.5 max-[992px]:items-start">
                            <span class="text-[0.8rem] font-medium text-hp-text-muted">{{ \Carbon\Carbon::now()->format('l, F j, Y') }}</span>
                            <span class="font-display text-2xl font-bold leading-none text-hp-green-dark dark:text-[#c8e6c8]" id="sdLiveClock">{{ \Carbon\Carbon::now()->format('g:i A') }}</span>
                        </div>
                        @if ($weatherNow)
                        <div class="flex items-center gap-2">
                            @if (!empty($weatherNow['icon']))
                                <img src="{{ $weatherNow['icon'] }}" alt="" class="h-10 w-10 object-contain">
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-9 w-9 text-[#c8a45d]"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            @endif
                            <div class="flex flex-col items-center">
                                <span class="font-display text-[1.3rem] font-bold leading-[1.1] text-hp-green-dark dark:text-[#c8e6c8]">{{ round($weatherNow['temp_c'] ?? 0) }}°C</span>
                                <span class="text-[0.7rem] font-semibold text-hp-text-muted">{{ $weatherNow['condition'] ?? '—' }}</span>
                            </div>
                        </div>
                        @endif
                        <div class="shrink-0">
                            <span class="flex h-[60px] w-[60px] items-center justify-center rounded-xl bg-hp-green text-center text-[0.7rem] font-extrabold uppercase leading-[1.3] tracking-[0.06em] text-white shadow-[0_4px_12px_rgba(28,92,60,0.35)] max-[768px]:h-[50px] max-[768px]:w-[50px] max-[768px]:text-[0.6rem] dark:bg-[#178a52] dark:shadow-[0_4px_12px_rgba(23,138,82,0.4)]">PARK<br>OPEN</span>
                        </div>
                    </div>
                </section>

                {{-- ===== CHECKOUT ALERTS BANNER ===== --}}
                @if (($dashboardGuestsDue ?? 0) > 0 || ($dashboardResDue ?? 0) > 0)
                <div class="mb-6 flex items-center gap-5 rounded-2xl border border-rose-500/25 bg-rose-500/10 p-5 shadow-sm dark:border-rose-500/30 dark:bg-rose-950/25">
                    <div class="sd-pulse flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-rose-600 text-white shadow-sm">
                        <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div class="flex flex-1 flex-col gap-1">
                        <strong class="text-base font-extrabold uppercase tracking-[0.05em] text-rose-700 dark:text-rose-300">ATTENTION REQUIRED</strong>
                        <span class="text-sm text-hp-text">There are <strong>{{ $dashboardResDue }} reservations</strong> ({{ $dashboardGuestsDue }} guests) currently overdue for checkout.</span>
                    </div>
                    <a href="{{ route('staff.checkins') }}" class="whitespace-nowrap rounded-xl bg-rose-600 px-6 py-3 text-sm font-bold text-white no-underline shadow-[0_4px_12px_rgba(225,29,72,0.25)] transition-all duration-200 hover:-translate-y-0.5 hover:bg-rose-700 hover:text-white hover:shadow-[0_6px_15px_rgba(225,29,72,0.35)]">Resolve Check-outs &rarr;</a>
                </div>
                @endif

                {{-- ===== MAIN DASHBOARD GRID: Stats | Chart | Donut ===== --}}
                <div class="mb-4 grid grid-cols-1 items-stretch gap-4 lg:grid-cols-2 xl:grid-cols-[240px_minmax(0,1fr)_260px]">
                    {{-- LEFT: Stat Cards --}}
                    <div class="sd-stats-col grid grid-cols-1 gap-3 sm:grid-cols-2 lg:col-span-2 xl:col-span-1 xl:grid-cols-1">
                        <article class="flex flex-1 items-center gap-3.5 rounded-2xl border border-glass-border bg-glass p-3.5 shadow-glass transition-all duration-200 hover:-translate-y-0.5 hover:bg-glass-hover hover:shadow-[0_4px_16px_rgba(16,44,31,0.12)]">
                            <div class="sd-stat-card__icon flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#e6f3ec] p-2 text-[#1c5c3c] dark:bg-[rgba(28,92,60,0.25)] dark:text-[#81c784]">
                                <svg class="h-full w-full" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="m-0 text-[0.72rem] font-semibold leading-[1.3] text-hp-text-muted">Today's Check-ins</p>
                                <p class="m-0 mt-0.5 font-display text-2xl font-bold leading-[1.1] text-hp-green-dark dark:text-[#c8e6c8]">{{ $todayCheckIns }}</p>
                                <p class="m-0 mt-0.5 text-[0.65rem] leading-[1.4] text-hp-text-muted">Reservations marked as checked in today</p>
                            </div>
                        </article>
                        <article class="flex flex-1 items-center gap-3.5 rounded-2xl border border-glass-border bg-glass p-3.5 shadow-glass transition-all duration-200 hover:-translate-y-0.5 hover:bg-glass-hover hover:shadow-[0_4px_16px_rgba(16,44,31,0.12)]">
                            <div class="sd-stat-card__icon flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#e6f3ec] p-2 text-[#1c5c3c] dark:bg-[rgba(28,92,60,0.25)] dark:text-[#81c784]">
                                <svg class="h-full w-full" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="m-0 text-[0.72rem] font-semibold leading-[1.3] text-hp-text-muted">Pending Reservations</p>
                                <p class="m-0 mt-0.5 font-display text-2xl font-bold leading-[1.1] text-hp-green-dark dark:text-[#c8e6c8]">{{ $pendingReservationsCount }}</p>
                            </div>
                        </article>
                        <article class="flex flex-1 items-center gap-3.5 rounded-2xl border border-glass-border bg-glass p-3.5 shadow-glass transition-all duration-200 hover:-translate-y-0.5 hover:bg-glass-hover hover:shadow-[0_4px_16px_rgba(16,44,31,0.12)]">
                            <div class="sd-stat-card__icon flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#e6f3ec] p-2 text-[#1c5c3c] dark:bg-[rgba(28,92,60,0.25)] dark:text-[#81c784]">
                                <svg class="h-full w-full" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="m-0 text-[0.72rem] font-semibold leading-[1.3] text-hp-text-muted">Guests On-Site</p>
                                <p class="m-0 mt-0.5 font-display text-2xl font-bold leading-[1.1] text-hp-green-dark dark:text-[#c8e6c8]">{{ $guestsOnSiteCount }}</p>
                            </div>
                        </article>
                        <article class="flex flex-1 items-center gap-3.5 rounded-2xl border border-glass-border bg-glass p-3.5 shadow-glass transition-all duration-200 hover:-translate-y-0.5 hover:bg-glass-hover hover:shadow-[0_4px_16px_rgba(16,44,31,0.12)]">
                            <div class="sd-stat-card__icon flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#e6f3ec] p-2 text-[#1c5c3c] dark:bg-[rgba(28,92,60,0.25)] dark:text-[#81c784]">
                                <svg class="h-full w-full" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="m-0 text-[0.72rem] font-semibold leading-[1.3] text-hp-text-muted">Today's Revenue</p>
                                <p class="m-0 mt-0.5 font-display text-2xl font-bold leading-[1.1] text-hp-green-dark dark:text-[#c8e6c8]">₱{{ number_format($todayRevenue) }}</p>
                            </div>
                        </article>
                    </div>

                    {{-- CENTER: Area Chart --}}
                    <section class="sd-chart-panel flex flex-col overflow-hidden rounded-2xl border border-glass-border bg-glass shadow-glass">
                        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-glass-border px-5 py-4">
                            <h3 class="m-0 font-display text-base font-bold text-hp-text dark:text-[#c8e6c8]">Bookings & Revenue – Last 7 Days</h3>
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
                            {{-- Chart rendered by JS using the data below --}}
                            <canvas id="sdAreaChartCanvas" class="h-full w-full"></canvas>
                        </div>
                        {{-- Pass data to JS --}}
                        <script>
                            window.__sdChartData = {
                                labels: @json($weekDays),
                                bookings: @json($weekReservationCounts),
                            };
                            window.__sdChartData_revenueRaw = @json($weekRevenue);
                        </script>
                    </section>

                    {{-- RIGHT: Donut Chart --}}
                    <section class="sd-donut-panel flex flex-col overflow-hidden rounded-2xl border border-glass-border bg-glass shadow-glass">
                        <div class="border-b border-glass-border px-5 py-4">
                            <h3 class="m-0 font-display text-base font-bold text-hp-text dark:text-[#c8e6c8]">Reservation Status</h3>
                        </div>
                        <div class="sd-donut-panel__body flex flex-1 flex-col items-center justify-center gap-4 p-5">
                            @if ($donutTotal > 0)
                                <div class="sd-donut-ring flex h-[150px] w-[150px] shrink-0 items-center justify-center rounded-full shadow-[inset_0_0_0_1px_rgba(13,44,29,0.06)] max-[768px]:h-[120px] max-[768px]:w-[120px]" style="{{ $donutStyle }}">
                                    <div class="sd-donut-ring__hole flex h-[90px] w-[90px] flex-col items-center justify-center rounded-full bg-glass max-[768px]:h-[72px] max-[768px]:w-[72px]">
                                        <span class="sd-donut-ring__total font-display text-[1.75rem] font-bold leading-none text-hp-green-dark dark:text-[#c8e6c8]">{{ $donutTotal }}</span>
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

                {{-- ===== BOTTOM ROW: Amenities | Arrivals | Activity ===== --}}
                <div class="sd-bottom-grid grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <section class="dash-panel dash-chart overflow-visible rounded-2xl border border-glass-border bg-glass shadow-glass">
                        <div class="dash-panel__head flex flex-wrap items-center justify-between gap-4 border-b border-glass-border px-5 py-4">
                            <h3 class="dash-panel__title m-0 font-display text-base font-bold text-hp-text dark:text-[#c8e6c8]">Most Booked Amenities</h3>
                        </div>
                        @if ($topAmenities->isNotEmpty())
                            <div class="dash-hbars grid gap-4 p-5">
                                @foreach ($topAmenities as $amenity)
                                    <div class="dash-hbar">
                                        <div class="dash-hbar__row mb-1.5 flex items-baseline justify-between text-[0.8125rem] text-hp-text">
                                            <span>{{ $amenity['name'] }}</span>
                                            <strong class="text-hp-green-dark dark:text-[#c8e6c8]">{{ $amenity['total'] }}</strong>
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
                            <h3 class="dash-panel__title m-0 font-display text-base font-bold text-hp-text dark:text-[#c8e6c8]">Today's Expected Arrivals</h3>
                        </div>
                        <ul class="dash-arrivals m-0 list-none p-4">
                            @forelse ($todayArrivals as $arrival)
                                <li class="dash-arrival flex items-center gap-3.5 border-b border-glass-border py-3.5 last:border-none">
                                    <span class="dash-arrival__dot h-[0.55rem] w-[0.55rem] shrink-0 rounded-full bg-hp-gold shadow-glass" aria-hidden="true"></span>
                                    <div class="dash-arrival__body min-w-0 flex-1">
                                        <p class="dash-arrival__name m-0 text-sm font-semibold text-hp-text">{{ $arrival->booker_name }}</p>
                                        <p class="dash-arrival__meta m-0 mt-0.5 text-xs text-hp-text-muted">{{ $arrival->number_of_guests }} guest(s) ·
                                            {{ \Carbon\Carbon::parse($arrival->reservation_date)->format('g:i A') }}</p>
                                    </div>
                                    <span class="dash-arrival__badge dash-arrival__badge--{{ strtolower(str_replace(' ', '-', $arrival->status)) }} shrink-0 rounded-full px-2.5 py-1 text-[0.6875rem] font-bold uppercase tracking-[0.05em] {{ $arrival->status === 'Pending' ? 'bg-[rgba(200,164,93,0.16)] text-[#8a6d2f]' : 'bg-[rgba(76,154,95,0.16)] text-[#2f6f45]' }}">{{ $arrival->status }}</span>
                                </li>
                            @empty
                                <li class="dash-arrival">
                                    <p class="dash-arrival__empty m-0 text-sm text-hp-text-muted">No arrivals expected today.</p>
                                </li>
                            @endforelse
                        </ul>
                    </section>

                    <section class="dash-panel overflow-visible rounded-2xl border border-glass-border bg-glass shadow-glass">
                        <div class="dash-panel__head border-b border-glass-border px-5 py-4">
                            <h3 class="dash-panel__title m-0 font-display text-base font-bold text-hp-text dark:text-[#c8e6c8]">Recent Activity</h3>
                        </div>
                        <ul class="dash-activity-list m-0 list-none p-4">
                            @forelse ($activityItems as $activity)
                                <li class="dash-activity flex gap-4 border-b border-glass-border py-4 last:border-none">
                                    <span class="dash-activity__dot mt-1.5 h-[0.625rem] w-[0.625rem] shrink-0 rounded-full bg-hp-gold shadow-glass" aria-hidden="true"></span>
                                    <div>
                                        <p class="dash-activity__text m-0 text-sm leading-[1.6] text-hp-text">{{ $activity['text'] }}</p>
                                        <p class="dash-activity__time m-0 mt-1.5 text-xs text-hp-text-muted">{{ $activity['time'] }}</p>
                                    </div>
                                </li>
                            @empty
                                <li class="dash-activity">
                                    <span class="dash-activity__dot mt-1.5 h-[0.625rem] w-[0.625rem] shrink-0 rounded-full bg-hp-gold shadow-glass" aria-hidden="true"></span>
                                    <div>
                                        <p class="dash-activity__text m-0 text-sm leading-[1.6] text-hp-text">No recent reservation activity yet.</p>
                                        <p class="dash-activity__time m-0 mt-1.5 text-xs text-hp-text-muted">Check back soon</p>
                                    </div>
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
