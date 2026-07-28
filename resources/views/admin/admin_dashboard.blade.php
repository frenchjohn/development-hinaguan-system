<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard — Hinaguan Nature Park</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|playfair-display:600,700" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/sidemenu.css',
        'resources/css/admin_css/admin_dashboard.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/admin_js/admin_dashboard.js',
    ])
</head>
<body class="antialiased">
    <div class="dash-layout">
        <x-admin_sidemenu active="dashboard" />

        <div class="dash-main">
            <!-- Page transition overlay with skeleton loading -->
            <div class="page-transition-overlay" id="pageTransitionOverlay">
                <div class="page-transition-skeleton">
                    <div class="skeleton skeleton--title page-transition-skeleton__header"></div>
                    <div class="page-transition-skeleton__stats">
                        <div class="skeleton skeleton--card page-transition-skeleton__stat"></div>
                        <div class="skeleton skeleton--card page-transition-skeleton__stat"></div>
                        <div class="skeleton skeleton--card page-transition-skeleton__stat"></div>
                    </div>
                    <div class="page-transition-skeleton__grid">
                        <div class="skeleton skeleton--panel page-transition-skeleton__panel"></div>
                        <div class="skeleton skeleton--panel page-transition-skeleton__panel"></div>
                    </div>
                </div>
            </div>
            <x-header
                title="Admin Dashboard"
                subtitle="Overview of park operations and reservations"
                userName="Admin User"
                userRole="Administrator"
                :settingsUrl="route('admin.settings')"
            />

            <main class="dash-content">
                <section class="dash-welcome">
                    <h2 class="dash-welcome__title">Good day, Admin!</h2>
                    <p class="dash-welcome__text">
                        Here is a snapshot of Hinaguan Nature Park today. Manage reservations, amenities, and visitor activity from this panel.
                    </p>
                </section>

                <div class="dash-stats">
                    <article class="dash-stat-card">
                        <p class="dash-stat-card__label">Total Reservations</p>
                        <p class="dash-stat-card__value">{{ $totalReservations }}</p>
                    </article>
                    <article class="dash-stat-card">
                        <p class="dash-stat-card__label">Total Guests</p>
                        <p class="dash-stat-card__value">{{ $totalGuests }}</p>
                    </article>
                    <article class="dash-stat-card">
                        <p class="dash-stat-card__label">Revenue (Month)</p>
                        <p class="dash-stat-card__value">₱{{ number_format($currentMonthRevenue, 2) }}</p>
                    </article>
                    <article class="dash-stat-card">
                        <p class="dash-stat-card__label">Pending Reservations</p>
                        <p class="dash-stat-card__value">{{ $pendingReservations }}</p>
                    </article>
                    <article class="dash-stat-card">
                        <p class="dash-stat-card__label">Checked-in Guests</p>
                        <p class="dash-stat-card__value">{{ $checkedInGuests }}</p>
                    </article>
                    <article class="dash-stat-card reports-metric-card--alert">
                        <p class="dash-stat-card__label">Cancelled Reservations</p>
                        <p class="dash-stat-card__value">{{ $cancelledReservations }}</p>
                    </article>
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
                                        <tr>
                                            <td>{{ $reservation->booker_name }}</td>
                                            <td>{{ $reservation->reservation_date ? \Illuminate\Support\Carbon::parse($reservation->reservation_date)->format('M d, Y') : 'TBD' }}</td>
                                            <td>{{ $reservation->reservationAmenities->pluck('amenity.amenities_name')->filter()->join(', ') ?: 'None' }}</td>
                                            <td>
                                                <span class="dash-badge dash-badge--{{ strtolower($reservation->status) }}">{{ $reservation->status }}</span>
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
                                <span>Total guests checked in</span>
                                <strong>{{ $checkedInGuests }}</strong>
                            </div>
                            <div class="dash-summary-item">
                                <span>Unique customers</span>
                                <strong>{{ $uniqueCustomerCount }}</strong>
                            </div>
                            <div class="dash-summary-item">
                                <span>Top booked amenity</span>
                                <strong>{{ $topAmenity['name'] ?? 'N/A' }} ({{ $topAmenity['count'] ?? 0 }})</strong>
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
                                <a href="#">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    View Staff List
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
