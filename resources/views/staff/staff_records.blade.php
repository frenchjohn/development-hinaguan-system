<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Records — Hinaguan Nature Park</title>
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
        'resources/css/staff_css/staff_records.css',
        'resources/css/chatbot.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/staff_js/staff_records.js',
        'resources/js/staff_chatbot.js',
    ])
</head>
<body class="antialiased staff-portal s-rec-page">
    <div class="dash-layout">
        <x-staff_sidemenu active="records" userName="{{ session('auth_user.name') ?? 'Staff User' }}" userRole="Staff" />

        <div class="dash-main">

            <main class="dash-content">
                <x-header
                    title="Records"
                    subtitle="View checked-out guests and completed reservations"
                />

                <!-- SUMMARY STRIP -->
                <div class="records-summary">
                    <article class="records-summary-card">
                        <span class="records-summary-card__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </span>
                        <div>
                            <p class="records-summary-card__value">{{ $guestRecordsCount }}</p>
                            <p class="records-summary-card__label">Guest Records</p>
                        </div>
                    </article>
                    <article class="records-summary-card">
                        <span class="records-summary-card__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </span>
                        <div>
                            <p class="records-summary-card__value">{{ $completedReservationsCount }}</p>
                            <p class="records-summary-card__label">Completed Reservations</p>
                        </div>
                    </article>
                    <article class="records-summary-card">
                        <span class="records-summary-card__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div>
                            <p class="records-summary-card__value">₱{{ number_format($completedRevenue) }}</p>
                            <p class="records-summary-card__label">Revenue Collected</p>
                        </div>
                    </article>
                    <article class="records-summary-card">
                        <span class="records-summary-card__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </span>
                        <div>
                            <p class="records-summary-card__value">{{ $uniqueGuestsCount }}</p>
                            <p class="records-summary-card__label">Unique Visitors</p>
                        </div>
                    </article>
                </div>

                <!-- TAB BUTTONS -->
                <div class="records-tabs">
                    <button type="button" class="records-tab-btn records-tab-btn--active" data-tab="guests" id="guestsTabBtn">
                        Guests
                    </button>
                    <button type="button" class="records-tab-btn" data-tab="reservations" id="reservationsTabBtn">
                        Reservations
                    </button>
                </div>

                <!-- GUESTS TABLE SECTION -->
                <section class="dash-panel guest-panel" data-tab-content="guests">
                    <div class="dash-panel__head guest-panel__head">
                        <div>
                            <h3 class="dash-panel__title">Checked-Out Guest Records</h3>
                            <p class="dash-panel__subtitle">Guests who have completed their visit and checked out</p>
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="guest-alert">{{ session('success') }}</div>
                    @endif

                    <div class="guest-filter-shell">
                        <button type="button" class="guest-filter-toggle" id="guestFilterToggle" aria-expanded="false" aria-controls="guestFilterPanel">
                            <span>Filters</span>
                            <span class="guest-filter-toggle__icon">▾</span>
                        </button>
                        <div class="guest-toolbar guest-toolbar--collapsed" id="guestFilterPanel" hidden>
                            <label class="guest-toolbar__field guest-toolbar__field--search">
                                <span>Search</span>
                                <input type="search" id="guestSearchInput" placeholder="Search by name, ID, gender">
                            </label>
                            <label class="guest-toolbar__field">
                                <span>Sort by</span>
                                <select id="guestSortSelect">
                                    <option value="name-asc">Name (A-Z)</option>
                                    <option value="name-desc">Name (Z-A)</option>
                                    <option value="age-asc">Age (Low-High)</option>
                                    <option value="age-desc">Age (High-Low)</option>
                                    <option value="checkout-desc">Checkout (Newest)</option>
                                </select>
                            </label>
                            <label class="guest-toolbar__field">
                                <span>Checked out from</span>
                                <input type="date" id="guestCheckOutFrom">
                            </label>
                            <label class="guest-toolbar__field">
                                <span>Checked out to</span>
                                <input type="date" id="guestCheckOutTo">
                            </label>
                            <button type="button" class="guest-toolbar__clear" id="guestFiltersClear">Clear</button>
                        </div>
                    </div>

                    <div class="guest-toolbar__meta">
                        <span id="guestResultsCount">Showing {{ $checkedOutGuests->count() }} records</span>
                    </div>

                    <div class="guest-table-wrap" id="guestTableWrap">
                        <table class="guest-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>Status</th>
                                    <th>Checked Out</th>
                                </tr>
                            </thead>
                            <tbody id="guestTableBody">
                                @forelse ($checkedOutGuests as $guestEntry)
                                    @php
                                        $customer = $guestEntry->customer;
                                        $guestInitials = collect(explode(' ', trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';
                                    @endphp
                                    <tr
                                        class="guest-row"
                                        data-customer-id="{{ $customer->id }}"
                                        data-age="{{ $customer->age ?? 'N/A' }}"
                                        data-gender="{{ $customer->gender ?? 'N/A' }}"
                                        data-is-foreigner="{{ $customer->is_foreigner ? 'Foreigner' : 'Filipino' }}"
                                        data-checked-out="{{ $guestEntry->checked_out_at ?? '' }}"
                                        data-age-value="{{ is_numeric($customer->age) ? (int) $customer->age : 999999 }}"
                                        data-search="{{ strtolower(trim(($customer->first_name ?? '') . ' ' . ($customer->middle_name ?? '') . ' ' . ($customer->last_name ?? '') . ' ' . $customer->id . ' ' . ($customer->gender ?? '') . ' ' . ($customer->is_foreigner ? 'Foreigner' : 'Filipino'))) }}"
                                        tabindex="0"
                                        role="button"
                                        aria-label="View details for {{ trim(($customer->first_name ?? '') . ' ' . ($customer->middle_name ?? '') . ' ' . ($customer->last_name ?? '')) }}"
                                    >
                                        <td>
                                            <div class="cell-person">
                                                <span class="cell-person__avatar">{{ $guestInitials }}</span>
                                                <div class="cell-person__body">
                                                    <div class="guest-name">{{ trim(($customer->first_name ?? '') . ' ' . ($customer->middle_name ?? '') . ' ' . ($customer->last_name ?? '')) }}</div>
                                                    <div class="guest-meta">Customer ID: {{ $customer->id }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $customer->age ?? 'N/A' }}</td>
                                        <td>{{ $customer->gender ?? 'N/A' }}</td>
                                        <td>
                                            <span class="status-pill {{ $customer->is_foreigner ? 'status-pill--confirmed' : 'status-pill--checked-out' }}">{{ $customer->is_foreigner ? 'Foreigner' : 'Filipino' }}</span>
                                        </td>
                                        <td class="mono-cell">{{ $guestEntry->checked_out_at ? \Carbon\Carbon::parse($guestEntry->checked_out_at)->format('M d, Y h:i A') : 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="guest-empty">No checked-out guest records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- RESERVATIONS TABLE SECTION -->
                <section class="dash-panel guest-panel" data-tab-content="reservations" hidden style="margin-top: 2rem;">
                    <div class="dash-panel__head guest-panel__head">
                        <div>
                            <h3 class="dash-panel__title">Completed Reservations</h3>
                            <p class="dash-panel__subtitle">Records of reservations that have been checked out</p>
                        </div>
                    </div>

                    <div class="guest-filter-shell">
                        <button type="button" class="guest-filter-toggle" id="reservationFilterToggle" aria-expanded="false" aria-controls="reservationFilterPanel">
                            <span>Filters</span>
                            <span class="guest-filter-toggle__icon">▾</span>
                        </button>
                        <div class="guest-toolbar guest-toolbar--collapsed" id="reservationFilterPanel" hidden>
                            <label class="guest-toolbar__field guest-toolbar__field--search">
                                <span>Search</span>
                                <input type="search" id="reservationSearchInput" placeholder="Search by booker name, email, ID">
                            </label>
                            <label class="guest-toolbar__field">
                                <span>Sort by</span>
                                <select id="reservationSortSelect">
                                    <option value="date-desc">Checkout (Newest)</option>
                                    <option value="date-asc">Checkout (Oldest)</option>
                                    <option value="name-asc">Booker Name (A-Z)</option>
                                    <option value="name-desc">Booker Name (Z-A)</option>
                                    <option value="amount-desc">Amount (High to Low)</option>
                                </select>
                            </label>
                            <label class="guest-toolbar__field">
                                <span>Checked out from</span>
                                <input type="date" id="reservationCheckOutFrom">
                            </label>
                            <label class="guest-toolbar__field">
                                <span>Checked out to</span>
                                <input type="date" id="reservationCheckOutTo">
                            </label>
                            <button type="button" class="guest-toolbar__clear" id="reservationFiltersClear">Clear</button>
                        </div>
                    </div>

                    <div class="guest-toolbar__meta">
                        <span id="reservationResultsCount">Showing {{ $checkedOutReservations->count() }} reservations</span>
                    </div>

                    <div class="guest-table-wrap" id="reservationTableWrap">
                        <table class="guest-table">
                            <thead>
                                <tr>
                                    <th>Booker Name</th>
                                    <th>Email</th>
                                    <th>Guests</th>
                                    <th>Status</th>
                                    <th>Check-In</th>
                                    <th>Check-Out</th>
                                    <th>Amount Paid</th>
                                </tr>
                            </thead>
                            <tbody id="reservationTableBody">
                                @forelse ($checkedOutReservations as $reservation)
                                    <tr
                                        class="reservation-row"
                                        data-reservation-id="{{ $reservation->id }}"
                                        data-booker-name="{{ strtolower($reservation->booker_name ?? '') }}"
                                        data-email="{{ strtolower($reservation->email ?? '') }}"
                                        data-check-out="{{ $reservation->check_out ?? '' }}"
                                        data-amount="{{ (float) ($reservation->amount_paid ?? 0) }}"
                                        data-search="{{ strtolower(trim(($reservation->booker_name ?? '') . ' ' . ($reservation->email ?? '') . ' ' . $reservation->id)) }}"
                                        tabindex="0"
                                        role="button"
                                        aria-label="View details for {{ $reservation->booker_name }}"
                                    >
                                        <td>
                                            @php
                                                $bookerInitials = collect(explode(' ', trim($reservation->booker_name ?? '')))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';
                                            @endphp
                                            <div class="cell-person">
                                                <span class="cell-person__avatar">{{ $bookerInitials }}</span>
                                                <div class="cell-person__body">
                                                    <div class="guest-name">{{ $reservation->booker_name }}</div>
                                                    <div class="guest-meta">ID: {{ $reservation->id }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $reservation->email }}</td>
                                        <td>{{ $reservation->number_of_guests }}</td>
                                        <td>
                                            <span class="status-pill status-pill--{{ strtolower(str_replace(' ', '-', $reservation->status)) }}">{{ $reservation->status }}</span>
                                        </td>
                                        <td class="mono-cell">{{ $reservation->check_in ? \Carbon\Carbon::parse($reservation->check_in)->format('M d, Y h:i A') : 'N/A' }}</td>
                                        <td class="mono-cell">{{ $reservation->check_out ? \Carbon\Carbon::parse($reservation->check_out)->format('M d, Y h:i A') : 'N/A' }}</td>
                                        <td class="num-cell">₱{{ number_format($reservation->amount_paid, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="guest-empty">No checked-out reservations found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="guest-modal" id="guestModal" aria-hidden="true">
                    <div class="guest-modal__backdrop" data-close-modal="true"></div>
                    <div class="guest-modal__content" role="dialog" aria-modal="true" aria-labelledby="guestModalTitle">
                        <button type="button" class="guest-modal__close" data-close-modal="true" aria-label="Close details">&times;</button>
                        <div class="guest-modal__header">
                            <h3 id="guestModalTitle" class="guest-modal__title">Guest Details</h3>
                        </div>
                        <div id="guestModalBody" class="guest-modal__body"></div>
                    </div>
                </div>

                <div class="guest-modal" id="reservationModal" aria-hidden="true">
                    <div class="guest-modal__backdrop" data-close-reservation-modal="true"></div>
                    <div class="guest-modal__content" role="dialog" aria-modal="true" aria-labelledby="reservationModalTitle">
                        <button type="button" class="guest-modal__close" data-close-reservation-modal="true" aria-label="Close details">&times;</button>
                        <div class="guest-modal__header">
                            <h3 id="reservationModalTitle" class="guest-modal__title">Reservation Details</h3>
                        </div>
                        <div id="reservationModalBody" class="guest-modal__body"></div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <x-staff_chatbot />

    <script>
        window.staffGuestData = @json($guestData ?? []);
        window.staffReservationData = @json($reservationData ?? []);
    </script>
</body>
</html>
