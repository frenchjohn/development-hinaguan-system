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
        'resources/css/admin_css/admin_shared.css',
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
            <main class="dash-content p-6">
                <x-header
                    title="Park Reports"
                    subtitle="Reservation, revenue, and amenity analytics"
                />
                <section class="mb-4">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="mb-2 inline-flex rounded-full bg-[rgba(200,164,93,0.12)] px-3 py-[0.35rem] text-[0.75rem] font-bold uppercase tracking-[0.14em] text-[var(--hp-gold-dark)]">Reports</p>
                            <h2 class="m-0 font-display text-[1.5rem] font-bold leading-[1.1] text-[var(--hp-text)]">Park performance overview</h2>
                            <p class="m-0 mt-2 max-w-[45rem] text-[0.9rem] leading-[1.5] text-[var(--hp-text-muted)]">View reservation analytics, payment insights, amenity popularity, and filtered report output.</p>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-4">
                        <button type="button" class="btn btn--ghost reports-print-button rounded-full border border-[rgba(13,44,29,0.1)] bg-white px-[1.35rem] py-[0.85rem] text-[var(--hp-text)] transition-all duration-200 hover:bg-[var(--hp-gold)] hover:text-[var(--hp-green-dark)] dark:border-white/10 dark:bg-white/5 dark:hover:bg-[rgba(200,164,93,0.2)]" id="printReportsButton">Print PDF</button>
                    </div>
                </section>

                <section class="reports-filters mb-4 rounded-[0.75rem] border border-[rgba(13,44,29,0.1)] bg-white p-[1.15rem_1.25rem_1.05rem] transition-colors duration-300 dark:border-white/10 dark:bg-white/5" id="reportsFilters">
                    <div class="mb-4 flex flex-wrap items-start justify-between gap-4 border-b border-[var(--border)] pb-[0.9rem]">
                        <div>
                            <h3 class="m-0 mb-[0.2rem] font-display text-[0.98rem] font-semibold text-[var(--ink)]">Filter Report</h3>
                            <p class="m-0 text-[0.78rem] text-[var(--ink-muted)]">Narrow the ledger by amenity, reservation status, or check-in range</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-[0.6rem]">
                            <span class="rounded-full border border-[var(--border)] bg-[var(--surface-2)] px-[0.7rem] py-[0.28rem] text-[0.72rem] font-semibold text-[var(--ink-muted)]" id="activeFilterText">Showing all reservations</span>
                            <button type="button" class="inline-flex items-center gap-[0.35rem] rounded-[var(--radius-sm)] border border-[var(--border-strong)] bg-[var(--surface-2)] px-[0.8rem] py-[0.35rem] text-[0.76rem] font-semibold text-[var(--ink)] transition-all duration-150 hover:border-[rgba(207,75,71,0.35)] hover:bg-[var(--danger-soft)] hover:text-[var(--danger)] active:scale-[0.97]" id="resetFiltersBtn">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-[0.8rem] w-[0.8rem]"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                Reset
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-[0.9rem] md:grid-cols-2 xl:grid-cols-4">
                        <label class="reports-filter-group flex min-w-0 flex-col gap-[0.35rem]">
                            <span class="flex items-center gap-[0.4rem] text-[0.68rem] font-bold uppercase tracking-[0.05em] text-[var(--ink-muted)]">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="h-[0.85rem] w-[0.85rem] text-[var(--green)]"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 01-1.125-1.125v-3.75zM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-8.25zM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-2.25z"/></svg>
                                Amenity
                            </span>
                            <select id="amenityFilter" class="w-full rounded-lg border border-[rgba(13,44,29,0.1)] bg-white px-[0.8rem] py-[0.6rem] text-[0.85rem] text-[var(--hp-text)] transition-colors duration-300 dark:border-white/10 dark:bg-white/5">
                                <option value="all">All amenities</option>
                                @foreach($amenityOptions as $amenityOption)
                                    <option value="{{ $amenityOption }}">{{ $amenityOption }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="reports-filter-group flex min-w-0 flex-col gap-[0.35rem]">
                            <span class="flex items-center gap-[0.4rem] text-[0.68rem] font-bold uppercase tracking-[0.05em] text-[var(--ink-muted)]">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="h-[0.85rem] w-[0.85rem] text-[var(--green)]"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Reservation Status
                            </span>
                            <select id="statusFilter" class="w-full rounded-lg border border-[rgba(13,44,29,0.1)] bg-white px-[0.8rem] py-[0.6rem] text-[0.85rem] text-[var(--hp-text)] transition-colors duration-300 dark:border-white/10 dark:bg-white/5">
                                <option value="all">All statuses</option>
                                @foreach($statusOptions as $statusOption)
                                    <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="reports-filter-group flex min-w-0 flex-col gap-[0.35rem]">
                            <span class="flex items-center gap-[0.4rem] text-[0.68rem] font-bold uppercase tracking-[0.05em] text-[var(--ink-muted)]">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="h-[0.85rem] w-[0.85rem] text-[var(--green)]"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                                Check-in from
                            </span>
                            <input id="dateFrom" type="date" value="{{ $firstCheckInDate }}" class="w-full rounded-lg border border-[rgba(13,44,29,0.1)] bg-white px-[0.8rem] py-[0.6rem] text-[0.85rem] text-[var(--hp-text)] transition-colors duration-300 dark:border-white/10 dark:bg-white/5">
                        </label>
                        <label class="reports-filter-group flex min-w-0 flex-col gap-[0.35rem]">
                            <span class="flex items-center gap-[0.4rem] text-[0.68rem] font-bold uppercase tracking-[0.05em] text-[var(--ink-muted)]">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="h-[0.85rem] w-[0.85rem] text-[var(--green)]"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                                Check-in to
                            </span>
                            <input id="dateTo" type="date" value="{{ $lastCheckInDate }}" class="w-full rounded-lg border border-[rgba(13,44,29,0.1)] bg-white px-[0.8rem] py-[0.6rem] text-[0.85rem] text-[var(--hp-text)] transition-colors duration-300 dark:border-white/10 dark:bg-white/5">
                        </label>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-[var(--border)] pt-[0.9rem]">
                        <span class="text-[0.72rem] font-bold uppercase tracking-[0.04em] text-[var(--ink-faint)]">Quick range:</span>
                        <button type="button" class="preset-chip rounded-full border border-[var(--border)] bg-[var(--surface-2)] px-3 py-[0.3rem] text-[0.72rem] font-semibold text-[var(--ink-muted)] transition-all duration-150 hover:-translate-y-px hover:border-[rgba(23,138,82,0.5)] hover:text-[var(--green-deep)]" data-preset="today">Today</button>
                        <button type="button" class="preset-chip rounded-full border border-[var(--border)] bg-[var(--surface-2)] px-3 py-[0.3rem] text-[0.72rem] font-semibold text-[var(--ink-muted)] transition-all duration-150 hover:-translate-y-px hover:border-[rgba(23,138,82,0.5)] hover:text-[var(--green-deep)]" data-preset="7d">Last 7 days</button>
                        <button type="button" class="preset-chip rounded-full border border-[var(--border)] bg-[var(--surface-2)] px-3 py-[0.3rem] text-[0.72rem] font-semibold text-[var(--ink-muted)] transition-all duration-150 hover:-translate-y-px hover:border-[rgba(23,138,82,0.5)] hover:text-[var(--green-deep)]" data-preset="30d">Last 30 days</button>
                        <button type="button" class="preset-chip rounded-full border border-[var(--border)] bg-[var(--surface-2)] px-3 py-[0.3rem] text-[0.72rem] font-semibold text-[var(--ink-muted)] transition-all duration-150 hover:-translate-y-px hover:border-[rgba(23,138,82,0.5)] hover:text-[var(--green-deep)]" data-preset="month">This month</button>
                        <button type="button" class="preset-chip rounded-full border border-[var(--border)] bg-[var(--surface-2)] px-3 py-[0.3rem] text-[0.72rem] font-semibold text-[var(--ink-muted)] transition-all duration-150 hover:-translate-y-px hover:border-[rgba(23,138,82,0.5)] hover:text-[var(--green-deep)]" data-preset="all">All time</button>
                    </div>
                </section>

                <section class="mb-4 flex gap-2">
                    <button class="reports-tab rounded-full bg-[var(--hp-cream)] px-5 py-[0.6rem] text-[0.85rem] font-semibold text-[var(--hp-text-muted)] transition-all duration-200 hover:bg-[rgba(13,44,29,0.08)] hover:text-[var(--hp-text)] dark:bg-white/8 dark:hover:bg-white/12 reports-tab--active" data-tab="overview">Overview</button>
                    <button class="reports-tab rounded-full bg-[var(--hp-cream)] px-5 py-[0.6rem] text-[0.85rem] font-semibold text-[var(--hp-text-muted)] transition-all duration-200 hover:bg-[rgba(13,44,29,0.08)] hover:text-[var(--hp-text)] dark:bg-white/8 dark:hover:bg-white/12" data-tab="amenities">Amenities</button>
                    <button class="reports-tab rounded-full bg-[var(--hp-cream)] px-5 py-[0.6rem] text-[0.85rem] font-semibold text-[var(--hp-text-muted)] transition-all duration-200 hover:bg-[rgba(13,44,29,0.08)] hover:text-[var(--hp-text)] dark:bg-white/8 dark:hover:bg-white/12" data-tab="breakdown">Breakdown</button>
                    <button class="reports-tab rounded-full bg-[var(--hp-cream)] px-5 py-[0.6rem] text-[0.85rem] font-semibold text-[var(--hp-text-muted)] transition-all duration-200 hover:bg-[rgba(13,44,29,0.08)] hover:text-[var(--hp-text)] dark:bg-white/8 dark:hover:bg-white/12" data-tab="ledger">Ledger</button>
                    <button class="reports-tab rounded-full bg-[var(--hp-cream)] px-5 py-[0.6rem] text-[0.85rem] font-semibold text-[var(--hp-text-muted)] transition-all duration-200 hover:bg-[rgba(13,44,29,0.08)] hover:text-[var(--hp-text)] dark:bg-white/8 dark:hover:bg-white/12" data-tab="revenue">Revenue</button>
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
                    <div class="mb-5 grid grid-cols-[repeat(auto-fit,minmax(12rem,1fr))] gap-3">
                        <article class="rounded-xl border border-[rgba(13,44,29,0.1)] bg-white px-5 py-4 transition-colors duration-300 dark:border-white/10 dark:bg-white/5">
                            <p class="reports-metric-card__label m-0 mb-2 text-[0.75rem] font-bold text-[var(--hp-text-muted)]">Total Reservations</p>
                            <p class="reports-metric-card__value m-0 text-[1.4rem] font-bold text-[var(--hp-text)]">{{ $totalReservations }}</p>
                        </article>
                        <article class="rounded-xl border border-[rgba(13,44,29,0.1)] bg-white px-5 py-4 transition-colors duration-300 dark:border-white/10 dark:bg-white/5">
                            <p class="reports-metric-card__label m-0 mb-2 text-[0.75rem] font-bold text-[var(--hp-text-muted)]">Total guests</p>
                            <p class="reports-metric-card__value m-0 text-[1.4rem] font-bold text-[var(--hp-text)]">{{ $totalGuests }}</p>
                        </article>
                        <article class="rounded-xl border border-[rgba(13,44,29,0.1)] bg-white px-5 py-4 transition-colors duration-300 dark:border-white/10 dark:bg-white/5">
                            <p class="reports-metric-card__label m-0 mb-2 text-[0.75rem] font-bold text-[var(--hp-text-muted)]">Unique customers</p>
                            <p class="reports-metric-card__value m-0 text-[1.4rem] font-bold text-[var(--hp-text)]">{{ $customerCount }}</p>
                        </article>
                        <article class="rounded-xl border border-[rgba(13,44,29,0.1)] bg-white px-5 py-4 transition-colors duration-300 dark:border-white/10 dark:bg-white/5">
                            <p class="reports-metric-card__label m-0 mb-2 text-[0.75rem] font-bold text-[var(--hp-text-muted)]">Revenue collected</p>
                            <p class="reports-metric-card__value m-0 text-[1.4rem] font-bold text-[var(--hp-text)]">₱{{ number_format($revenue, 2) }}</p>
                        </article>
                        <article class="rounded-xl border border-[rgba(13,44,29,0.1)] bg-white px-5 py-4 transition-colors duration-300 dark:border-white/10 dark:bg-white/5">
                            <p class="reports-metric-card__label m-0 mb-2 text-[0.75rem] font-bold text-[var(--hp-text-muted)]">Checked-in guests</p>
                            <p class="reports-metric-card__value m-0 text-[1.4rem] font-bold text-[var(--hp-text)]">{{ $checkedInGuests }}</p>
                        </article>
                        <article class="rounded-xl border border-[rgba(254,226,226,0.7)] bg-[rgba(254,226,226,0.7)] px-5 py-4 transition-colors duration-300">
                            <p class="reports-metric-card__label m-0 mb-2 text-[0.75rem] font-bold text-[var(--hp-text-muted)]">Cancelled reservations</p>
                            <p class="reports-metric-card__value m-0 text-[1.4rem] font-bold text-[var(--hp-text)]">{{ $cancelledReservations }}</p>
                        </article>
                        <article class="rounded-xl border border-[rgba(13,44,29,0.1)] bg-white px-5 py-4 transition-colors duration-300 dark:border-white/10 dark:bg-white/5">
                            <p class="reports-metric-card__label m-0 mb-2 text-[0.75rem] font-bold text-[var(--hp-text-muted)]">Top amenity</p>
                            <p class="reports-metric-card__value m-0 text-[1.4rem] font-bold text-[var(--hp-text)]">{{ $mostBookedAmenity }}</p>
                            <p class="reports-metric-card__meta m-0 mt-1 text-[0.8rem] text-[var(--hp-text-muted)]">{{ $mostBookedAmenityCount }} bookings</p>
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
                                <p class="reports-empty text-center py-10 text-[var(--ink-faint)]">No amenity reservations have been recorded yet.</p>
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
                    <div class="mb-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
                        <section class="reports-panel reports-panel--summary">
                            <div class="reports-panel__head">
                                <h3 class="reports-panel__title">Reservation Breakdown</h3>
                            </div>
                            <div class="reports-panel__body grid gap-2">
                                @foreach($reservationTypeBreakdown as $item)
                                    <div class="flex items-center justify-between rounded-lg border border-[rgba(13,44,29,0.1)] bg-[var(--hp-cream)] px-3 py-[0.6rem] text-[0.85rem]">
                                        <span class="text-[var(--hp-text-muted)]">{{ $item['type'] }}</span>
                                        <strong class="text-[var(--hp-text)]">{{ $item['count'] }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                        <section class="reports-panel reports-panel--summary">
                            <div class="reports-panel__head">
                                <h3 class="reports-panel__title">Payment Status</h3>
                            </div>
                            <div class="reports-panel__body grid gap-2">
                                @foreach($paymentStatusBreakdown as $item)
                                    <div class="flex items-center justify-between rounded-lg border border-[rgba(13,44,29,0.1)] bg-[var(--hp-cream)] px-3 py-[0.6rem] text-[0.85rem]">
                                        <span class="text-[var(--hp-text-muted)]">{{ $item['status'] }}</span>
                                        <strong class="text-[var(--hp-text)]">{{ $item['count'] }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                        <section class="reports-panel reports-panel--summary">
                            <div class="reports-panel__head">
                                <h3 class="reports-panel__title">Booking peaks</h3>
                            </div>
                            <div class="reports-panel__body grid gap-2">
                                <div class="flex items-center justify-between rounded-lg border border-[rgba(13,44,29,0.1)] bg-[var(--hp-cream)] px-3 py-[0.6rem] text-[0.85rem]">
                                    <span class="text-[var(--hp-text-muted)]">Peak booking day</span>
                                    <strong class="text-right text-[var(--hp-text)]">
                                        @if($peakBookedDay)
                                            {{ \Illuminate\Support\Carbon::parse($peakBookedDay)->format('M d, Y') }}
                                            ({{ $peakBookedDayCount }} bookings)
                                        @else
                                            No data
                                        @endif
                                    </strong>
                                </div>
                                <div class="flex items-center justify-between rounded-lg border border-[rgba(13,44,29,0.1)] bg-[var(--hp-cream)] px-3 py-[0.6rem] text-[0.85rem]">
                                    <span class="text-[var(--hp-text-muted)]">Peak booking month</span>
                                    <strong class="text-right text-[var(--hp-text)]">
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
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                <div class="rounded-lg border border-[rgba(13,44,29,0.1)] bg-[var(--hp-cream)] px-4 py-[0.875rem]">
                                    <p class="m-0 mb-[0.4rem] text-[0.8rem] text-[var(--hp-text-muted)]">Total revenue collected</p>
                                    <strong class="block text-[1.1rem] text-[var(--hp-text)]">₱{{ number_format($revenue, 2) }}</strong>
                                </div>
                                <div class="rounded-lg border border-[rgba(13,44,29,0.1)] bg-[var(--hp-cream)] px-4 py-[0.875rem]">
                                    <p class="m-0 mb-[0.4rem] text-[0.8rem] text-[var(--hp-text-muted)]">Pending reservations</p>
                                    <strong class="block text-[1.1rem] text-[var(--hp-text)]">{{ $pendingReservations }}</strong>
                                </div>
                                <div class="rounded-lg border border-[rgba(13,44,29,0.1)] bg-[var(--hp-cream)] px-4 py-[0.875rem]">
                                    <p class="m-0 mb-[0.4rem] text-[0.8rem] text-[var(--hp-text-muted)]">Cancelled reservations</p>
                                    <strong class="block text-[1.1rem] text-[var(--hp-text)]">{{ $cancelledReservations }}</strong>
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
