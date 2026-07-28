<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Reports — Hinaguan Nature Park</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/css/homepage.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/sidemenu.css',
        'resources/css/staff_css/staff_reports.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/staff_js/staff_reports.js',
    ])
</head>
<body class="antialiased">
    <div class="dash-layout">
        <x-staff_sidemenu active="reports" />

        <div class="dash-main">
            <div class="page-transition-overlay" id="pageTransitionOverlay">
                <div class="page-transition-skeleton">
                    <div class="page-transition-skeleton__header skeleton"></div>
                    <div class="page-transition-skeleton__stats">
                        <div class="page-transition-skeleton__stat skeleton"></div>
                        <div class="page-transition-skeleton__stat skeleton"></div>
                        <div class="page-transition-skeleton__stat skeleton"></div>
                    </div>
                    <div class="page-transition-skeleton__grid">
                        <div class="page-transition-skeleton__panel skeleton"></div>
                        <div class="page-transition-skeleton__panel skeleton"></div>
                    </div>
                </div>
            </div>

            <x-header
                title="Staff Reports"
                subtitle="Customer, reservation, and amenity insights"
                userName="Staff User"
                userRole="Staff"
                :settingsUrl="route('staff.settings')"
            />

            <main class="dash-content">
                <section class="reports-head">
                    <div>
                        <p class="reports-head__eyebrow">Staff Reports</p>
                        <h2 class="reports-head__title">Track reservations, guest details, and amenities</h2>
                        <p class="reports-head__text">Filter by customer, amenity, status, and check-in range. Print only the rows shown in the filtered report.</p>
                    </div>
                    <div class="reports-head__actions">
                        <button type="button" class="btn btn--ghost reports-print-button" id="printReportsButton">Print PDF</button>
                    </div>
                </section>

                <section class="reports-filters" id="reportsFilters">
                    <div class="reports-filter-group">
                        <label for="customerFilter">Customer</label>
                        <select id="customerFilter">
                            <option value="all">All customers</option>
                            @foreach($customerOptions as $customerOption)
                                <option value="{{ $customerOption }}">{{ $customerOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="reports-filter-group">
                        <label for="amenityFilter">Amenity</label>
                        <select id="amenityFilter">
                            <option value="all">All amenities</option>
                            @foreach($amenityOptions as $amenityOption)
                                <option value="{{ $amenityOption }}">{{ $amenityOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="reports-filter-group">
                        <label for="statusFilter">Reservation Status</label>
                        <select id="statusFilter">
                            <option value="all">All statuses</option>
                            @foreach($statusOptions as $statusOption)
                                <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="reports-filter-group">
                        <label for="dateFrom">Check-in from</label>
                        <input id="dateFrom" type="date" value="{{ $firstCheckInDate }}">
                    </div>
                    <div class="reports-filter-group">
                        <label for="dateTo">Check-in to</label>
                        <input id="dateTo" type="date" value="{{ $lastCheckInDate }}">
                    </div>
                </section>

                <section class="reports-print-summary" id="reportsPrintSummary" aria-hidden="true">
                    <div class="reports-print-summary__row">
                        <strong>Customer:</strong>
                        <span id="printCustomerText">All customers</span>
                    </div>
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

                <div class="reports-metrics">
                    <article class="reports-metric-card">
                        <p class="reports-metric-card__label">Total Reservations</p>
                        <p class="reports-metric-card__value">{{ $totalReservations }}</p>
                    </article>
                    <article class="reports-metric-card">
                        <p class="reports-metric-card__label">Customers in report</p>
                        <p class="reports-metric-card__value">{{ $customerCount }}</p>
                    </article>
                    <article class="reports-metric-card">
                        <p class="reports-metric-card__label">Amenities used</p>
                        <p class="reports-metric-card__value">{{ $amenityCount }}</p>
                    </article>
                    <article class="reports-metric-card">
                        <p class="reports-metric-card__label">Total revenue</p>
                        <p class="reports-metric-card__value">₱{{ number_format($totalRevenue, 2) }}</p>
                    </article>
                </div>

                <section class="reports-panel reports-panel--wide">
                    <div class="reports-panel__head">
                        <h3 class="reports-panel__title">Reservation Report</h3>
                        <span class="reports-panel__meta">Showing all filtered reservations</span>
                    </div>
                    <div class="reports-table-wrap">
                        <table class="reports-table" id="reservationReportTable">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Check-in</th>
                                    <th>Amenities</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reportRows as $row)
                                    <tr data-customer="{{ $row['customer_name'] }}" data-amenity="{{ $row['amenities'] }}" data-status="{{ $row['status'] }}" data-checkin="{{ $row['check_in'] }}">
                                        <td>{{ $row['customer_name'] }}</td>
                                        <td>{{ $row['check_in'] ? \Illuminate\Support\Carbon::parse($row['check_in'])->format('M d, Y') : 'TBD' }}</td>
                                        <td>{{ $row['amenities'] }}</td>
                                        <td>{{ $row['status'] }}</td>
                                        <td>{{ $row['payment_status'] }}</td>
                                        <td>₱{{ number_format($row['total_amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="reports-table-empty">No reservation report data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
