<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Reports — Hinaguan Nature Park</title>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite([
        'resources/css/app.css',
        'resources/css/homepage.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/staff_sidemenu.css',
        'resources/css/staff_css/staff_reports.css',
        'resources/css/chatbot.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/staff_js/staff_reports.js',
        'resources/js/staff_chatbot.js',
    ])
</head>
<body class="antialiased staff-portal s-rep-page">
    <div class="dash-layout">
        <x-staff_sidemenu active="reports" userName="{{ session('auth_user.name') ?? 'Staff User' }}" userRole="Staff" />

        <div class="dash-main">

            <main class="dash-content">
                <x-header
                    title="Staff Reports"
                    subtitle="Customer, reservation, and amenity insights"
                />

                <section class="reports-filter-card is-open" id="reportsFilters">
                    <div class="reports-filter-card__head" id="filterToggleBtn">
                        <div class="reports-filter-card__title-group">
                            <div class="reports-filter-card__icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                            </div>
                            <div>
                                <h3 class="reports-filter-card__title">Filter Report</h3>
                                <p class="reports-filter-card__hint">Narrow reservations by customer, amenity, status or check-in range</p>
                            </div>
                        </div>
                        <div class="reports-filter-card__actions">
                            <button type="button" class="btn btn--outline" id="resetFiltersBtn">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Reset
                            </button>
                            <button type="button" class="btn btn--primary" id="applyFiltersBtn">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                                Apply Filters
                            </button>
                            <svg class="reports-filter-card__chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                    <div class="reports-filter-card__body">
                        <div class="reports-filters__grid">
                            <label class="reports-filter-group">
                                <span class="reports-filter-group__label">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                    Customer
                                </span>
                                <select id="customerFilter">
                                    <option value="all">All customers</option>
                                    @foreach($customerOptions as $customerOption)
                                        <option value="{{ $customerOption }}">{{ $customerOption }}</option>
                                    @endforeach
                                </select>
                            </label>
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
                            <label class="reports-filter-group reports-filter-group--range">
                                <span class="reports-filter-group__label">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                                    Check-in Range
                                </span>
                                <div class="reports-filter-group__range">
                                    <input id="dateFrom" type="date" value="{{ $firstCheckInDate }}" aria-label="Check-in from">
                                    <span class="reports-filter-group__range-sep">→</span>
                                    <input id="dateTo" type="date" value="{{ $lastCheckInDate }}" aria-label="Check-in to">
                                </div>
                            </label>
                        </div>
                        <div class="reports-filters__presets">
                            <span class="reports-filters__presets-label">Quick Range:</span>
                            <button type="button" class="preset-chip" data-preset="today">Today</button>
                            <button type="button" class="preset-chip" data-preset="7d">Last 7 days</button>
                            <button type="button" class="preset-chip" data-preset="30d">Last 30 days</button>
                            <button type="button" class="preset-chip" data-preset="month">This month</button>
                            <button type="button" class="preset-chip is-active" data-preset="all">All time</button>
                        </div>
                    </div>
                </section>

                <div class="reports-kpi-row">
                    <article class="reports-kpi-card">
                        <div class="reports-kpi-card__icon reports-kpi-card__icon--green">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="reports-kpi-card__content">
                            <h4 class="reports-kpi-card__value" id="kpiReservations">{{ $totalReservations }}</h4>
                            <p class="reports-kpi-card__label">Total Reservations</p>
                            <span class="reports-kpi-card__meta">• From all selected filters</span>
                        </div>
                    </article>

                    <article class="reports-kpi-card">
                        <div class="reports-kpi-card__icon reports-kpi-card__icon--light-green">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <div class="reports-kpi-card__content">
                            <h4 class="reports-kpi-card__value">{{ $totalGuests }}</h4>
                            <p class="reports-kpi-card__label">Total Guests</p>
                            <span class="reports-kpi-card__meta">• From all selected filters</span>
                        </div>
                    </article>

                    <article class="reports-kpi-card">
                        <div class="reports-kpi-card__icon reports-kpi-card__icon--blue">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="reports-kpi-card__content">
                            <h4 class="reports-kpi-card__value" id="kpiRevenue">₱{{ number_format($totalRevenue, 2) }}</h4>
                            <p class="reports-kpi-card__label">Total Revenue</p>
                            <span class="reports-kpi-card__meta">• From all selected filters</span>
                        </div>
                    </article>

                    <article class="reports-kpi-card">
                        <div class="reports-kpi-card__icon reports-kpi-card__icon--purple">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <div class="reports-kpi-card__content">
                            <h4 class="reports-kpi-card__value">₱{{ number_format($averageSpend, 2) }}</h4>
                            <p class="reports-kpi-card__label">Avg per Reservation</p>
                            <span class="reports-kpi-card__meta">• From all selected filters</span>
                        </div>
                    </article>
                </div>

                <div class="reports-charts-row">
                    <!-- Revenue Area Chart -->
                    <section class="reports-panel reports-panel--revenue">
                        <div class="reports-panel__head">
                            <div class="reports-panel__title-group">
                                <div class="reports-panel__icon reports-panel__icon--green">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                </div>
                                <h3 class="reports-panel__title">Revenue — Last 6 Months</h3>
                            </div>
                            <div class="reports-panel__dropdown">
                                <select>
                                    <option>Total amount per month</option>
                                </select>
                            </div>
                        </div>
                        <div class="reports-panel__chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </section>

                    <!-- Status Donut Chart -->
                    <section class="reports-panel reports-panel--status">
                        <div class="reports-panel__head">
                            <div class="reports-panel__title-group">
                                <div class="reports-panel__icon reports-panel__icon--light-green">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </div>
                                <h3 class="reports-panel__title">Reservation Status</h3>
                            </div>
                        </div>
                        <div class="reports-panel__donut-container">
                            <div class="reports-donut-chart">
                                <canvas id="statusDonutChart"></canvas>
                                <div class="reports-donut-chart__center">
                                    <span class="reports-donut-chart__total" id="donutTotalCount">0</span>
                                    <span class="reports-donut-chart__label">Total</span>
                                </div>
                            </div>
                            <div class="reports-donut-legend" id="donutLegendContainer">
                                <!-- Populated by JS -->
                            </div>
                        </div>
                        <div class="reports-panel__footer">
                            <a href="#" class="reports-panel__link">View full breakdown →</a>
                        </div>
                    </section>
                </div>

                <div class="reports-charts-row reports-charts-row--bottom">
                    <!-- Top Amenities -->
                    <section class="reports-panel reports-panel--amenities">
                        <div class="reports-panel__head">
                            <div class="reports-panel__title-group">
                                <div class="reports-panel__icon reports-panel__icon--green">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                </div>
                                <div>
                                    <h3 class="reports-panel__title">Top Amenities</h3>
                                    <p class="reports-panel__subtitle">Most reserved amenities</p>
                                </div>
                            </div>
                        </div>
                        <div class="reports-table-headers">
                            <span>AMENITY</span>
                            <span>RESERVATIONS</span>
                        </div>
                        <div class="reports-bars-container" id="topAmenitiesContainer">
                            <!-- Populated by JS -->
                        </div>
                    </section>

                    <!-- Peak Days -->
                    <section class="reports-panel reports-panel--peak">
                        <div class="reports-panel__head">
                            <div class="reports-panel__title-group">
                                <div class="reports-panel__icon reports-panel__icon--green">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <h3 class="reports-panel__title">Peak Days</h3>
                                    <p class="reports-panel__subtitle">Busiest days based on reservations</p>
                                </div>
                            </div>
                            <div class="reports-panel__dropdown">
                                <select>
                                    <option>By Reservations</option>
                                    <option>By Guests</option>
                                </select>
                            </div>
                        </div>
                        <div class="reports-bars-container reports-bars-container--flush" id="peakDaysContainer">
                            <!-- Populated by JS -->
                        </div>
                    </section>
                </div>

                <script>
                    window.reportData = {
                        monthlyLabels: @json($monthlyLabels),
                        monthlyRevenue: @json($monthlyRevenue),
                        statusCounts: @json($reportStatusCounts),
                        rawRows: @json($reportRows)
                    };
                </script>
            </main>
        </div>
    </div>

    <x-staff_chatbot />
</body>
</html>
