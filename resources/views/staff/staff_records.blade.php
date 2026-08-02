<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Records — Hinaguan Nature Park</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/css/homepage.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/sidemenu.css',
        'resources/css/staff_css/staff_dashboard.css',
        'resources/css/staff_css/staff_records.css',
        'resources/css/chatbot.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/staff_js/staff_records.js',
        'resources/js/staff_chatbot.js',
    ])
</head>
<body class="antialiased">
    <div class="dash-layout">
        <x-staff_sidemenu active="records" />

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
                title="Records"
                subtitle="View checked-out guests and completed reservations"
                userName="Staff User"
                userRole="Staff"
                :settingsUrl="route('staff.settings')"
            />

            <main class="dash-content">
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
                            <h3 class="dash-panel__title">Checked-Out Guests</h3>
                            <p class="dash-panel__subtitle">Records of guests who have checked out</p>
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
                                            <div class="guest-name">{{ trim(($customer->first_name ?? '') . ' ' . ($customer->middle_name ?? '') . ' ' . ($customer->last_name ?? '')) }}</div>
                                            <div class="guest-meta">Customer ID: {{ $customer->id }}</div>
                                        </td>
                                        <td>{{ $customer->age ?? 'N/A' }}</td>
                                        <td>{{ $customer->gender ?? 'N/A' }}</td>
                                        <td>{{ $customer->is_foreigner ? 'Foreigner' : 'Filipino' }}</td>
                                        <td>{{ $guestEntry->checked_out_at ? \Carbon\Carbon::parse($guestEntry->checked_out_at)->format('M d, Y h:i A') : 'N/A' }}</td>
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
                                            <div class="guest-name">{{ $reservation->booker_name }}</div>
                                            <div class="guest-meta">ID: {{ $reservation->id }}</div>
                                        </td>
                                        <td>{{ $reservation->email }}</td>
                                        <td>{{ $reservation->number_of_guests }}</td>
                                        <td>{{ $reservation->check_in ? \Carbon\Carbon::parse($reservation->check_in)->format('M d, Y h:i A') : 'N/A' }}</td>
                                        <td>{{ $reservation->check_out ? \Carbon\Carbon::parse($reservation->check_out)->format('M d, Y h:i A') : 'N/A' }}</td>
                                        <td>₱{{ number_format($reservation->amount_paid, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="guest-empty">No checked-out reservations found.</td>
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
