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
        'resources/css/homepage.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/staff_sidemenu.css',
        'resources/css/staff_css/staff_dashboard.css',
        'resources/css/chatbot.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/staff_js/staff_dashboard.js',
        'resources/js/staff_chatbot.js',
    ])
</head>
<body class="antialiased staff-portal page-staff-dashboard s-das-page">
    <div class="dash-layout">
        <x-staff_sidemenu active="dashboard" userName="{{ session('auth_user.name') ?? 'Staff User' }}" userRole="Staff" />

        <div class="dash-main">

            <main class="dash-content">
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
                <section class="sd-greeting-banner">
                    <div class="sd-greeting-banner__left">
                        <h2 class="sd-greeting-banner__title">{{ $greeting }}, {{ session('auth_user.name') ?? 'Staff User' }}!</h2>
                        <p class="sd-greeting-banner__subtitle">Welcome to the Hinaguan Nature Park Portal</p>
                    </div>
                    <div class="sd-greeting-banner__right">
                        <div class="sd-greeting-banner__datetime">
                            <span class="sd-greeting-banner__date">{{ \Carbon\Carbon::now()->format('l, F j, Y') }}</span>
                            <span class="sd-greeting-banner__time" id="sdLiveClock">{{ \Carbon\Carbon::now()->format('g:i A') }}</span>
                        </div>
                        @if ($weatherNow)
                        <div class="sd-greeting-banner__weather">
                            @if (!empty($weatherNow['icon']))
                                <img src="{{ $weatherNow['icon'] }}" alt="" class="sd-greeting-banner__weather-icon">
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="sd-greeting-banner__weather-icon-svg"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            @endif
                            <div class="sd-greeting-banner__weather-info">
                                <span class="sd-greeting-banner__weather-temp">{{ round($weatherNow['temp_c'] ?? 0) }}°C</span>
                                <span class="sd-greeting-banner__weather-cond">{{ $weatherNow['condition'] ?? '—' }}</span>
                            </div>
                        </div>
                        @endif
                        <div class="sd-greeting-banner__park-status">
                            <span class="sd-greeting-banner__park-badge">PARK<br>OPEN</span>
                        </div>
                    </div>
                </section>

                {{-- ===== CHECKOUT ALERTS BANNER ===== --}}
                @if (($dashboardGuestsDue ?? 0) > 0 || ($dashboardResDue ?? 0) > 0)
                <div class="sd-alert-banner">
                    <div class="sd-alert-banner__icon sd-pulse">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div class="sd-alert-banner__content">
                        <strong>ATTENTION REQUIRED</strong>
                        <span>There are <strong>{{ $dashboardResDue }} reservations</strong> ({{ $dashboardGuestsDue }} guests) currently overdue for checkout.</span>
                    </div>
                    <a href="{{ route('staff.checkins') }}" class="sd-alert-banner__btn">Resolve Check-outs &rarr;</a>
                </div>
                @endif

                {{-- ===== MAIN DASHBOARD GRID: Stats | Chart | Donut ===== --}}
                <div class="sd-dashboard-grid">
                    {{-- LEFT: Stat Cards --}}
                    <div class="sd-stats-col">
                        <article class="sd-stat-card">
                            <div class="sd-stat-card__icon sd-stat-card__icon--checkins">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="sd-stat-card__body">
                                <p class="sd-stat-card__label">Today's Check-ins</p>
                                <p class="sd-stat-card__value">{{ $todayCheckIns }}</p>
                                <p class="sd-stat-card__hint">Reservations marked as checked in today</p>
                            </div>
                        </article>
                        <article class="sd-stat-card">
                            <div class="sd-stat-card__icon sd-stat-card__icon--pending">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="sd-stat-card__body">
                                <p class="sd-stat-card__label">Pending Reservations</p>
                                <p class="sd-stat-card__value">{{ $pendingReservationsCount }}</p>
                            </div>
                        </article>
                        <article class="sd-stat-card">
                            <div class="sd-stat-card__icon sd-stat-card__icon--guests">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div class="sd-stat-card__body">
                                <p class="sd-stat-card__label">Guests On-Site</p>
                                <p class="sd-stat-card__value">{{ $guestsOnSiteCount }}</p>
                            </div>
                        </article>
                        <article class="sd-stat-card">
                            <div class="sd-stat-card__icon sd-stat-card__icon--revenue">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="sd-stat-card__body">
                                <p class="sd-stat-card__label">Today's Revenue</p>
                                <p class="sd-stat-card__value">₱{{ number_format($todayRevenue) }}</p>
                            </div>
                        </article>
                    </div>

                    {{-- CENTER: Area Chart --}}
                    <section class="sd-chart-panel">
                        <div class="sd-chart-panel__head">
                            <h3 class="sd-chart-panel__title">Bookings & Revenue – Last 7 Days</h3>
                            <div class="sd-chart-panel__legend">
                                <span class="sd-legend-item">
                                    <i class="sd-legend-swatch sd-legend-swatch--bookings"></i>Bookings
                                </span>
                                <span class="sd-legend-item">
                                    <i class="sd-legend-swatch sd-legend-swatch--revenue"></i>Revenue
                                </span>
                            </div>
                        </div>
                        <div class="sd-area-chart" id="sdAreaChart">
                            {{-- Chart rendered by JS using the data below --}}
                            <canvas id="sdAreaChartCanvas"></canvas>
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
                    <section class="sd-donut-panel">
                        <div class="sd-donut-panel__head">
                            <h3 class="sd-donut-panel__title">Reservation Status</h3>
                        </div>
                        <div class="sd-donut-panel__body">
                            @if ($donutTotal > 0)
                                <div class="sd-donut-ring" style="{{ $donutStyle }}">
                                    <div class="sd-donut-ring__hole">
                                        <span class="sd-donut-ring__total">{{ $donutTotal }}</span>
                                        <small>Total</small>
                                    </div>
                                </div>
                                <ul class="sd-donut-legend">
                                    @foreach ($statusBreakdown as $status => $count)
                                        <li>
                                            <i style="background: {{ $donutColors[$status] ?? '#c8a45d' }}"></i>
                                            {{ $status }}
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="sd-chart-empty">No reservations recorded yet.</p>
                            @endif
                        </div>
                    </section>
                </div>

                {{-- ===== BOTTOM ROW: Amenities | Arrivals | Activity ===== --}}
                <div class="sd-bottom-grid">
                    <section class="dash-panel dash-chart">
                        <div class="dash-panel__head">
                            <h3 class="dash-panel__title">Most Booked Amenities</h3>
                        </div>
                        @if ($topAmenities->isNotEmpty())
                            <div class="dash-hbars">
                                @foreach ($topAmenities as $amenity)
                                    <div class="dash-hbar">
                                        <div class="dash-hbar__row">
                                            <span>{{ $amenity['name'] }}</span>
                                            <strong>{{ $amenity['total'] }}</strong>
                                        </div>
                                        <div class="dash-hbar__track">
                                            <div class="dash-hbar__fill" style="--val: {{ round($amenity['total'] / $topAmenityMax * 100) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="dash-chart__empty">No amenity bookings yet.</p>
                        @endif
                    </section>

                    <section class="dash-panel">
                        <div class="dash-panel__head">
                            <h3 class="dash-panel__title">Today's Expected Arrivals</h3>
                        </div>
                        <ul class="dash-arrivals">
                            @forelse ($todayArrivals as $arrival)
                                <li class="dash-arrival">
                                    <span class="dash-arrival__dot" aria-hidden="true"></span>
                                    <div class="dash-arrival__body">
                                        <p class="dash-arrival__name">{{ $arrival->booker_name }}</p>
                                        <p class="dash-arrival__meta">{{ $arrival->number_of_guests }} guest(s) ·
                                            {{ \Carbon\Carbon::parse($arrival->reservation_date)->format('g:i A') }}</p>
                                    </div>
                                    <span class="dash-arrival__badge dash-arrival__badge--{{ strtolower(str_replace(' ', '-', $arrival->status)) }}">{{ $arrival->status }}</span>
                                </li>
                            @empty
                                <li class="dash-arrival">
                                    <p class="dash-arrival__empty">No arrivals expected today.</p>
                                </li>
                            @endforelse
                        </ul>
                    </section>

                    <section class="dash-panel">
                        <div class="dash-panel__head">
                            <h3 class="dash-panel__title">Recent Activity</h3>
                        </div>
                        <ul class="dash-activity-list">
                            @forelse ($activityItems as $activity)
                                <li class="dash-activity">
                                    <span class="dash-activity__dot" aria-hidden="true"></span>
                                    <div>
                                        <p class="dash-activity__text">{{ $activity['text'] }}</p>
                                        <p class="dash-activity__time">{{ $activity['time'] }}</p>
                                    </div>
                                </li>
                            @empty
                                <li class="dash-activity">
                                    <span class="dash-activity__dot" aria-hidden="true"></span>
                                    <div>
                                        <p class="dash-activity__text">No recent reservation activity yet.</p>
                                        <p class="dash-activity__time">Check back soon</p>
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
