<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|playfair-display:600,700" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/admin_sidemenu.css',
        'resources/css/admin_css/admin_dashboard.css',
        'resources/css/staff_css/staff_theme.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/admin_js/admin_dashboard.js',
    ])
</head>
<body class="antialiased admin-portal">
    <div class="dash-layout">
        <x-admin_sidemenu active="dashboard" userName="{{ session('auth_user.name') ?? 'Admin User' }}" userRole="Admin" />

        <div class="dash-main">
            <!-- Page transition overlay with skeleton loading -->
            <main class="dash-content">
                <x-header
                    title="Admin Dashboard"
                    subtitle="Overview of park operations and reservations"
                />
                @php
                    $hour = (int) now()->format('G');
                    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
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
                    $revenueMax = max(1, max($weekRevenue));
                @endphp

                <section class="dash-greeting">
                    <div class="dash-greeting__brand">
                        <img src="{{ asset('storage/design_images/main_logo.jpeg') }}" alt="Hinaguan Nature Park" class="dash-greeting__logo">
                        <div>
                            <p class="dash-greeting__eyebrow">Hinaguan Nature Park · Admin Portal</p>
                            <h2 class="dash-greeting__title">{{ $greeting }}, {{ session('auth_user.name') ?? 'Admin' }}</h2>
                            <p class="dash-greeting__text">Here is a snapshot of Hinaguan Nature Park today — reservations, visitors, and revenue at a glance.</p>
                        </div>
                    </div>
                    <div class="dash-greeting__meta">
                        <span class="dash-greeting__live"><span class="dash-greeting__live-dot"></span> Live</span>
                        <p class="dash-greeting__date">{{ \Carbon\Carbon::now()->format('l, F j, Y') }}</p>
                    </div>
                </section>

                <div class="dash-stats">
                    <article class="dash-stat-card">
                        <div class="dash-stat-card__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="dash-stat-card__body">
                            <p class="dash-stat-card__label">Total Reservations</p>
                            <p class="dash-stat-card__value">{{ $totalReservations }}</p>
                            <p class="dash-stat-card__hint">All reservations in the system</p>
                        </div>
                    </article>
                    <article class="dash-stat-card">
                        <div class="dash-stat-card__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div class="dash-stat-card__body">
                            <p class="dash-stat-card__label">Total Guests</p>
                            <p class="dash-stat-card__value">{{ $totalGuests }}</p>
                            <p class="dash-stat-card__hint">Total visitors booked</p>
                        </div>
                    </article>
                    <article class="dash-stat-card dash-stat-card--accent">
                        <div class="dash-stat-card__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                        </div>
                        <div class="dash-stat-card__body">
                            <p class="dash-stat-card__label">Today's Visitors</p>
                            <p class="dash-stat-card__value">{{ $todayVisitors }}</p>
                            <p class="dash-stat-card__hint">Guests expected to arrive today</p>
                        </div>
                    </article>
                    <article class="dash-stat-card dash-stat-card--accent">
                        <div class="dash-stat-card__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="dash-stat-card__body">
                            <p class="dash-stat-card__label">Revenue (Month)</p>
                            <p class="dash-stat-card__value">₱{{ number_format($currentMonthRevenue, 2) }}</p>
                            <p class="dash-stat-card__hint">Collected this month</p>
                        </div>
                    </article>
                    <article class="dash-stat-card">
                        <div class="dash-stat-card__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="dash-stat-card__body">
                            <p class="dash-stat-card__label">Pending Reservations</p>
                            <p class="dash-stat-card__value">{{ $pendingReservations }}</p>
                            <p class="dash-stat-card__hint">Awaiting confirmation</p>
                        </div>
                    </article>
                    <article class="dash-stat-card">
                        <div class="dash-stat-card__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="dash-stat-card__body">
                            <p class="dash-stat-card__label">Checked-in Guests</p>
                            <p class="dash-stat-card__value">{{ $checkedInGuests }}</p>
                            <p class="dash-stat-card__hint">Guests on site right now</p>
                        </div>
                    </article>
                    <article class="dash-stat-card reports-metric-card--alert">
                        <div class="dash-stat-card__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="dash-stat-card__body">
                            <p class="dash-stat-card__label">Cancelled Reservations</p>
                            <p class="dash-stat-card__value">{{ $cancelledReservations }}</p>
                            <p class="dash-stat-card__hint">Cancelled reservations</p>
                        </div>
                    </article>
                </div>

                <div class="dash-charts">
                    <section class="dash-panel dash-chart">
                        <div class="dash-panel__head">
                            <h3 class="dash-panel__title">Revenue — Last 7 Days</h3>
                            <div class="dash-chart__legend">
                                <span class="dash-legend__item"><i class="dash-legend__swatch dash-legend__swatch--revenue"></i>Revenue (₱)</span>
                            </div>
                        </div>
                        <div class="dash-barchart">
                            @for ($i = 0; $i < 7; $i++)
                                <div class="dash-barchart__col">
                                    <div class="dash-barchart__bars">
                                        <div class="dash-barchart__bar dash-barchart__bar--revenue"
                                             style="--val: {{ round($weekRevenue[$i] / $revenueMax * 100) }}%"
                                             title="₱{{ number_format($weekRevenue[$i]) }} collected"></div>
                                    </div>
                                    <span class="dash-barchart__value">₱{{ number_format($weekRevenue[$i]) }}</span>
                                    <span class="dash-barchart__label">{{ $weekDays[$i] }}</span>
                                </div>
                            @endfor
                        </div>
                    </section>

                    <section class="dash-panel dash-chart">
                        <div class="dash-panel__head">
                            <h3 class="dash-panel__title">Reservation Status</h3>
                        </div>
                        @if ($donutTotal > 0)
                            <div class="dash-donut-wrap">
                                <div class="dash-donut" style="{{ $donutStyle }}">
                                    <div class="dash-donut__hole">
                                        <span class="dash-donut__total">{{ $donutTotal }}</span>
                                        <small>total</small>
                                    </div>
                                </div>
                                <ul class="dash-donut__legend">
                                    @foreach ($statusBreakdown as $status => $count)
                                        <li>
                                            <i style="background: {{ $donutColors[$status] ?? '#c8a45d' }}"></i>
                                            {{ $status }}
                                            <strong>{{ $count }}</strong>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <p class="dash-chart__empty">No reservations recorded yet.</p>
                        @endif
                    </section>
                </div>

                <div class="dash-grid-2">
                    <section class="dash-panel">
                        <div class="dash-panel__head">
                            <h3 class="dash-panel__title">Recent Reservations</h3>
                            <a href="{{ route('admin.reports') }}" class="dash-panel__link">View reports</a>
                        </div>
                        <div class="dash-table-wrap">
                            <table class="dash-table">
                                <thead>
                                    <tr>
                                        <th>Guest</th>
                                        <th>Date</th>
                                        <th>Amenity</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentReservations as $reservation)
                                        @php $initials = strtoupper(implode('', array_map(fn ($w) => $w[0] ?? '', array_slice(preg_split('/\s+/', trim($reservation->booker_name ?? '?')), 0, 2)))); @endphp
                                        <tr>
                                            <td>
                                                <span class="cell-person">
                                                    <span class="cell-person__avatar">{{ $initials ?: '?' }}</span>
                                                    <span class="cell-person__name">{{ $reservation->booker_name }}</span>
                                                </span>
                                            </td>
                                            <td class="mono-cell">{{ $reservation->reservation_date ? \Illuminate\Support\Carbon::parse($reservation->reservation_date)->format('M d, Y') : 'TBD' }}</td>
                                            <td>{{ $reservation->reservationAmenities->pluck('amenity.amenities_name')->filter()->join(', ') ?: 'None' }}</td>
                                            <td>
                                                <span class="status-pill status-pill--{{ strtolower(str_replace(' ', '-', $reservation->status)) }}">{{ $reservation->status }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="dash-table-empty">No recent reservations yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="dash-panel">
                        <div class="dash-panel__head">
                            <h3 class="dash-panel__title">Key metrics</h3>
                        </div>
                        <div class="dash-panel__body dash-panel__body--metric-list">
                            <div class="dash-summary-item">
                                <span class="dash-summary-item__label">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Total guests checked in
                                </span>
                                <strong>{{ $checkedInGuests }}</strong>
                            </div>
                            <div class="dash-summary-item">
                                <span class="dash-summary-item__label">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                    Unique customers
                                </span>
                                <strong>{{ $uniqueCustomerCount }}</strong>
                            </div>
                            <div class="dash-summary-item">
                                <span class="dash-summary-item__label">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    Top booked amenity
                                </span>
                                <strong>{{ $topAmenity['name'] ?? 'N/A' }} <small>({{ $topAmenity['count'] ?? 0 }})</small></strong>
                            </div>
                        </div>
                        <div class="dash-panel__head dash-panel__head--secondary">
                            <h3 class="dash-panel__title">Quick Actions</h3>
                        </div>
                        <ul class="dash-quick-actions">
                            <li>
                                <a href="{{ route('reservation') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    New Reservation
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.amenities') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    Manage Amenities
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.users') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Manage Staff Users
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.settings') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    System Settings
                                </a>
                            </li>
                        </ul>
                    </section>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
