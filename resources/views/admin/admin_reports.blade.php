<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Reports — Hinaguan Nature Park</title>
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
        'resources/css/admin_css/admin_reports.css',
        'resources/css/staff_css/staff_theme.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/admin_js/admin_reports.js',
    ])
</head>
<body class="antialiased admin-portal">
    <div class="dash-layout">
        <x-admin_sidemenu active="reports" userName="{{ session('auth_user.name') ?? 'Admin User' }}" userRole="Admin" />

        <div class="dash-main">
            <!-- Page transition overlay with skeleton loading -->
            <main class="dash-content">
                <x-header
                    title="Park Reports"
                    subtitle="Reservation, revenue, and amenity analytics"
                />
                <section class="reports-head">
                    <div>
                        <p class="reports-head__eyebrow">Reports</p>
                        <h2 class="reports-head__title">Park performance overview</h2>
                        <p class="reports-head__text">View reservation analytics, payment insights, amenity popularity, and filtered report output.</p>
                    </div>
                    <div class="reports-head__actions">
                        <button type="button" class="btn btn--ghost reports-print-button" id="printReportsButton">Print PDF</button>
                    </div>
                </section>

                <section class="reports-filters" id="reportsFilters">
                    <div class="reports-filters__head">
                        <div>
                            <h3 class="reports-filters__title">Filter Report</h3>
                            <p class="reports-filters__hint">Narrow the ledger by amenity, reservation status, or check-in range</p>
                        </div>
                        <div class="reports-filters__head-actions">
                            <span class="reports-filters__active" id="activeFilterText">Showing all reservations</span>
                            <button type="button" class="reports-filters__reset" id="resetFiltersBtn">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                Reset
                            </button>
                        </div>
                    </div>
                    <div class="reports-filters__grid">
                        <label class="reports-filter-group">
                            <span class="reports-filter-group__label">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 01-1.125-1.125v-3.75zM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-8.25zM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-2.25z"/></svg>
                                Amenity
                            </span>
                            <select id="amenityFilter">
                                <option value="all">All amenities</option>
                                @foreach($amenityOptions as $amenityOption)
                                    <option value="{{ $amenityOption }}">{{ $amenityOption }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="reports-filter-group">
                            <span class="reports-filter-group__label">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Reservation Status
                            </span>
                            <select id="statusFilter">
                                <option value="all">All statuses</option>
                                @foreach($statusOptions as $statusOption)
                                    <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="reports-filter-group">
                            <span class="reports-filter-group__label">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                                Check-in from
                            </span>
                            <input id="dateFrom" type="date" value="{{ $firstCheckInDate }}">
                        </label>
                        <label class="reports-filter-group">
                            <span class="reports-filter-group__label">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                                Check-in to
                            </span>
                            <input id="dateTo" type="date" value="{{ $lastCheckInDate }}">
                        </label>
                    </div>
                    <div class="reports-filters__presets">
                        <span class="reports-filters__presets-label">Quick range:</span>
                        <button type="button" class="preset-chip" data-preset="today">Today</button>
                        <button type="button" class="preset-chip" data-preset="7d">Last 7 days</button>
                        <button type="button" class="preset-chip" data-preset="30d">Last 30 days</button>
                        <button type="button" class="preset-chip" data-preset="month">This month</button>
                        <button type="button" class="preset-chip" data-preset="all">All time</button>
                    </div>
                </section>

                <section class="reports-tabs">
                    <button class="reports-tab reports-tab--active" data-tab="overview">Overview</button>
                    <button class="reports-tab" data-tab="amenities">Amenities</button>
                    <button class="reports-tab" data-tab="breakdown">Breakdown</button>
                    <button class="reports-tab" data-tab="ledger">Ledger</button>
                    <button class="reports-tab" data-tab="revenue">Revenue</button>
                </section>

                <section class="reports-print-summary" id="reportsPrintSummary" aria-hidden="true">
                    <div class="reports-print-summary__row">
                        <strong>Amenity:</strong>
                        <span id="printAmenityText">All amenities</span>
                    </div>
                    <div class="reports-print-summary__row">
                        <strong>Status:</strong>
                        <span id="printStatusText">All statuses</span>
                    </div>
                    <div class="reports-print-summary__row">
                        <strong>Check-in range:</strong>
                        <span id="printDateRangeText">{{ $firstCheckInDate }} - {{ $lastCheckInDate }}</span>
                    </div>
                </section>

                <div class="reports-tab-content reports-tab-content--active" id="tab-overview">
                    <div class="reports-metrics">
                        <article class="reports-metric-card">
                            <p class="reports-metric-card__label">Total Reservations</p>
                            <p class="reports-metric-card__value">{{ $totalReservations }}</p>
                        </article>
                        <article class="reports-metric-card">
                            <p class="reports-metric-card__label">Total guests</p>
                            <p class="reports-metric-card__value">{{ $totalGuests }}</p>
                        </article>
                        <article class="reports-metric-card">
                            <p class="reports-metric-card__label">Unique customers</p>
                            <p class="reports-metric-card__value">{{ $customerCount }}</p>
                        </article>
                        <article class="reports-metric-card">
                            <p class="reports-metric-card__label">Revenue collected</p>
                            <p class="reports-metric-card__value">₱{{ number_format($revenue, 2) }}</p>
                        </article>
                        <article class="reports-metric-card">
                            <p class="reports-metric-card__label">Checked-in guests</p>
                            <p class="reports-metric-card__value">{{ $checkedInGuests }}</p>
                        </article>
                        <article class="reports-metric-card reports-metric-card--alert">
                            <p class="reports-metric-card__label">Cancelled reservations</p>
                            <p class="reports-metric-card__value">{{ $cancelledReservations }}</p>
                        </article>
                        <article class="reports-metric-card">
                            <p class="reports-metric-card__label">Top amenity</p>
                            <p class="reports-metric-card__value">{{ $mostBookedAmenity }}</p>
                            <p class="reports-metric-card__label reports-metric-card__meta">{{ $mostBookedAmenityCount }} bookings</p>
                        </article>
                    </div>
                </div>

                <div class="reports-tab-content" id="tab-amenities">
                    <section class="reports-panel">
                        <div class="reports-panel__head">
                            <h3 class="reports-panel__title">Top 5 Most Reserved Amenities</h3>
                        </div>
                        <div class="reports-panel__body">
                            @if($amenityBreakdown->isEmpty())
                                <p class="reports-empty">No amenity reservations have been recorded yet.</p>
                            @else
                                <div class="reports-table-wrap">
                                    <table class="reports-table reports-table--compact">
                                        <thead>
                                            <tr>
                                                <th>Amenity</th>
                                                <th>Bookings</th>
                                                <th>Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($amenityBreakdown->take(5) as $item)
                                                <tr>
                                                    <td>{{ $item['name'] }}</td>
                                                    <td>{{ $item['count'] }}</td>
                                                    <td>₱{{ number_format($item['revenue'], 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </section>
                </div>

                <div class="reports-tab-content" id="tab-breakdown">
                    <div class="reports-panel-group">
                        <section class="reports-panel reports-panel--summary">
                            <div class="reports-panel__head">
                                <h3 class="reports-panel__title">Reservation Breakdown</h3>
                            </div>
                            <div class="reports-panel__body reports-stats-list">
                                @foreach($reservationTypeBreakdown as $item)
                                    <div class="reports-stats-item">
                                        <span>{{ $item['type'] }}</span>
                                        <strong>{{ $item['count'] }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                        <section class="reports-panel reports-panel--summary">
                            <div class="reports-panel__head">
                                <h3 class="reports-panel__title">Payment Status</h3>
                            </div>
                            <div class="reports-panel__body reports-stats-list">
                                @foreach($paymentStatusBreakdown as $item)
                                    <div class="reports-stats-item">
                                        <span>{{ $item['status'] }}</span>
                                        <strong>{{ $item['count'] }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                        <section class="reports-panel reports-panel--summary">
                            <div class="reports-panel__head">
                                <h3 class="reports-panel__title">Booking peaks</h3>
                            </div>
                            <div class="reports-panel__body reports-stats-list">
                                <div class="reports-stats-item">
                                    <span>Peak booking day</span>
                                    <strong>
                                        @if($peakBookedDay)
                                            {{ \Illuminate\Support\Carbon::parse($peakBookedDay)->format('M d, Y') }}
                                            ({{ $peakBookedDayCount }} bookings)
                                        @else
                                            No data
                                        @endif
                                    </strong>
                                </div>
                                <div class="reports-stats-item">
                                    <span>Peak booking month</span>
                                    <strong>
                                        @if($peakBookedMonth)
                                            {{ $peakBookedMonth }}
                                            ({{ $peakBookedMonthCount }} bookings)
                                        @else
                                            No data
                                        @endif
                                    </strong>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="reports-tab-content" id="tab-ledger">
                    <section class="reports-panel reports-panel--wide">
                        <div class="reports-panel__head">
                            <h3 class="reports-panel__title">Reservation Ledger</h3>
                            <span class="reports-panel__meta">Filtered result set</span>
                        </div>
                        <div class="reports-table-wrap">
                            <table class="reports-table" id="reservationsTable">
                                <thead>
                                    <tr>
                                        <th>Booker</th>
                                        <th>Check-in</th>
                                        <th>Guests</th>
                                        <th>Amenities</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reservations as $reservation)
                                        @php $initials = strtoupper(implode('', array_map(fn ($w) => $w[0] ?? '', array_slice(preg_split('/\s+/', trim($reservation->booker_name ?? '?')), 0, 2)))); @endphp
                                        <tr data-amenity="{{ $reservation->reservationAmenities->pluck('amenity.amenities_name')->filter()->join(', ') }}" data-status="{{ $reservation->status }}" data-checkin="{{ $reservation->reservation_date }}">
                                            <td>
                                                <span class="cell-person">
                                                    <span class="cell-person__avatar">{{ $initials ?: '?' }}</span>
                                                    <span class="cell-person__name">{{ $reservation->booker_name }}</span>
                                                </span>
                                            </td>
                                            <td class="mono-cell">{{ $reservation->reservation_date ? \Illuminate\Support\Carbon::parse($reservation->reservation_date)->format('M d, Y') : 'TBD' }}</td>
                                            <td>{{ $reservation->number_of_guests }}</td>
                                            <td>{{ $reservation->reservationAmenities->pluck('amenity.amenities_name')->filter()->join(', ') ?: 'None' }}</td>
                                            <td>
                                                <span class="status-pill status-pill--{{ strtolower(str_replace(' ', '-', $reservation->status)) }}">{{ $reservation->status }}</span>
                                            </td>
                                            <td>
                                                <span class="status-pill status-pill--{{ strtolower(str_replace(' ', '-', $reservation->payment_status)) }}">{{ $reservation->payment_status }}</span>
                                            </td>
                                            <td class="num-cell">₱{{ number_format($reservation->total_amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="reports-table-empty">No reservations available.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <div class="reports-tab-content" id="tab-revenue">
                    <section class="reports-panel">
                        <div class="reports-panel__head">
                            <h3 class="reports-panel__title">Revenue Summary</h3>
                        </div>
                        <div class="reports-panel__body">
                            <div class="reports-summary-grid">
                                <div>
                                    <p>Total revenue collected</p>
                                    <strong>₱{{ number_format($revenue, 2) }}</strong>
                                </div>
                                <div>
                                    <p>Pending reservations</p>
                                    <strong>{{ $pendingReservations }}</strong>
                                </div>
                                <div>
                                    <p>Cancelled reservations</p>
                                    <strong>{{ $cancelledReservations }}</strong>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
