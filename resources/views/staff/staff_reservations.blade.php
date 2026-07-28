<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reservations — Hinaguan Nature Park</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/css/homepage.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/sidemenu.css',
        'resources/css/staff_css/staff_dashboard.css',
        'resources/css/staff_css/staff_reservations.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/staff_js/staff_reservations.js',
    ])
</head>
<body class="antialiased">
    <div class="dash-layout">
        <x-staff_sidemenu active="reservations" />

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
                title="Reservations"
                subtitle="Pending online reservations waiting for check-in"
                userName="Staff User"
                userRole="Staff"
                :settingsUrl="route('staff.settings')"
            />

            <main class="dash-content">
                <section class="dash-panel guest-panel">
                    <div class="dash-panel__head guest-panel__head">
                        <div>
                            <h3 class="dash-panel__title">Pending Online Reservations</h3>
                            <p class="dash-panel__subtitle">Reservations that have not been checked in yet</p>
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="guest-alert">{{ session('success') }}</div>
                    @endif

                    <div class="guest-filter-shell">
                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                            <button type="button" class="guest-filter-toggle" id="reservationFilterToggle" aria-expanded="false" aria-controls="reservationFilterPanel">
                                <span>Filters</span>
                                <span class="guest-filter-toggle__icon">▾</span>
                            </button>
                            <button type="button" class="guest-filter-toggle guest-filter-toggle--secondary" id="refreshTableBtn" style="background-color: var(--hp-green-dark); color: white;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Refresh
                            </button>
                            <button type="button" class="guest-filter-toggle guest-filter-toggle--secondary" id="scanQrBtn" style="background-color: var(--hp-green-dark); color: white;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                        <div class="guest-toolbar guest-toolbar--collapsed" id="reservationFilterPanel" hidden>
                            <label class="guest-toolbar__field guest-toolbar__field--search">
                                <span>Search</span>
                                <input type="search" id="reservationSearchInput" placeholder="Search by booker, email, phone, or status">
                            </label>
                            <label class="guest-toolbar__field">
                                <span>Sort by</span>
                                <select id="reservationSortSelect">
                                    <option value="date-asc">Reservation date (soonest)</option>
                                    <option value="date-desc">Reservation date (latest)</option>
                                    <option value="name-asc">Booker (A-Z)</option>
                                    <option value="name-desc">Booker (Z-A)</option>
                                    <option value="amount-desc">Amount (High-Low)</option>
                                </select>
                            </label>
                            <label class="guest-toolbar__field">
                                <span>Status</span>
                                <select id="reservationStatusFilter">
                                    <option value="all">All statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                </select>
                            </label>
                            <label class="guest-toolbar__field">
                                <span>Reservation date from</span>
                                <input type="date" id="reservationDateFrom">
                            </label>
                            <label class="guest-toolbar__field">
                                <span>Reservation date to</span>
                                <input type="date" id="reservationDateTo">
                            </label>
                            <button type="button" class="guest-toolbar__clear" id="reservationFiltersClear">Clear</button>
                        </div>
                    </div>

                    <div class="guest-toolbar__meta">
                        <span id="reservationResultsCount">Showing {{ $reservations->count() }} reservation{{ $reservations->count() === 1 ? '' : 's' }}</span>
                    </div>

                    <div class="guest-table-wrap" id="reservationTableWrap">
                        <table class="guest-table">
                            <thead>
                                <tr>
                                    <th>Booker</th>
                                    <th>Reservation date</th>
                                    <th>Time Slots</th>
                                    <th>Guests</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody id="reservationTableBody">
                                @forelse ($reservations as $reservation)
                                    @php
                                        $isToday = $reservation->reservation_date && \Carbon\Carbon::parse($reservation->reservation_date)->isToday();
                                        $timeSlots = $reservationData[$reservation->id]['time_slots'] ?? [];
                                    @endphp
                                    <tr
                                        class="guest-row reservation-row {{ $isToday ? 'today-reservation' : '' }}"
                                        data-reservation-id="{{ $reservation->id }}"
                                        data-booker-name="{{ e($reservation->booker_name) }}"
                                        data-email="{{ e($reservation->email) }}"
                                        data-phone="{{ e($reservation->phone) }}"
                                        data-reservation-date="{{ $reservation->reservation_date }}"
                                        data-status="{{ strtolower($reservation->status) }}"
                                        data-guests="{{ $reservation->number_of_guests }}"
                                        data-total-amount="{{ (float) $reservation->total_amount }}"
                                        data-search="{{ strtolower(trim(($reservation->booker_name ?? '') . ' ' . ($reservation->email ?? '') . ' ' . ($reservation->phone ?? '') . ' ' . ($reservation->status ?? ''))) }}"
                                        tabindex="0"
                                        role="button"
                                        aria-label="View reservation details for {{ e($reservation->booker_name) }}"
                                    >
                                        <td>
                                            <div class="guest-name">
                                                {{ $reservation->booker_name }}
                                                @if ($isToday)
                                                    <span class="today-reservation-badge">TODAY</span>
                                                @endif
                                            </div>
                                            <div class="guest-meta">{{ $reservation->email }}</div>
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($reservation->reservation_date)->format('F j, Y') }}</td>
                                        <td>
                                            @if (!empty($timeSlots))
                                                <div class="time-slot-labels">
                                                    @foreach ($timeSlots as $slot)
                                                        <span class="time-slot-label time-slot-label--{{ strtolower(str_replace(' ', '', $slot)) }}">{{ $slot }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ $reservation->number_of_guests }}</td>
                                        <td>
                                            <span class="reservation-status reservation-status--{{ strtolower($reservation->status) }}">{{ $reservation->status }}</span>
                                        </td>
                                        <td>₱{{ number_format($reservation->total_amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="guest-empty">No pending online reservations found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="guest-modal" id="reservationModal" aria-hidden="true">
                    <div class="guest-modal__backdrop" data-close-reservation-modal="true"></div>
                    <div class="guest-modal__content" role="dialog" aria-modal="true" aria-labelledby="reservationModalTitle">
                        <button type="button" class="guest-modal__close" data-close-reservation-modal="true" aria-label="Close reservation details">&times;</button>
                        <div class="guest-modal__header">
                            <h3 id="reservationModalTitle" class="guest-modal__title">Reservation Details</h3>
                            <div class="guest-modal__header-actions">
                                <span id="reservationModalStatus" class="guest-modal__role-badge"></span>
                                <button type="button" class="guest-modal__edit-btn" id="editReservationBtn" data-edit-reservation="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                    </svg>
                                    Edit
                                </button>
                            </div>
                        </div>
                        <div id="reservationModalBody" class="guest-modal__body"></div>
                        <div id="reservationModalEditForm" class="guest-modal__edit-form" hidden>
                            <form id="editReservationForm" class="guest-form">
                                <input type="hidden" name="reservation_id" id="editReservationId">
                                <div class="guest-form__row guest-form__row--two">
                                    <label class="guest-form__field">
                                        <span>Booker Name</span>
                                        <input type="text" name="booker_name" id="editBookerName" required>
                                    </label>
                                    <label class="guest-form__field">
                                        <span>Email</span>
                                        <input type="email" name="email" id="editEmail" required>
                                    </label>
                                </div>
                                <div class="guest-form__row guest-form__row--two">
                                    <label class="guest-form__field">
                                        <span>Phone</span>
                                        <input type="text" name="phone" id="editPhone" required>
                                    </label>
                                    <label class="guest-form__field">
                                        <span>Reservation Date</span>
                                        <input type="date" name="reservation_date" id="editReservationDate" required>
                                    </label>
                                </div>
                                <div class="guest-form__row guest-form__row--two">
                                    <label class="guest-form__field">
                                        <span>Number of Guests</span>
                                        <input type="number" name="number_of_guests" id="editGuests" min="1" required>
                                    </label>
                                    <label class="guest-form__field">
                                        <span>Status</span>
                                        <select name="status" id="editStatus">
                                            <option value="Pending">Pending</option>
                                            <option value="Confirmed">Confirmed</option>
                                            <option value="Checked In">Checked In</option>
                                            <option value="Checked Out">Checked Out</option>
                                            <option value="Cancelled">Cancelled</option>
                                        </select>
                                    </label>
                                </div>
                                <div class="guest-form__actions">
                                    <button type="button" class="guest-form__secondary" id="cancelEditBtn">Cancel</button>
                                    <button type="submit" class="guest-form__button">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="guest-modal guest-modal--confirm" id="confirmModal" aria-hidden="true">
                    <div class="guest-modal__backdrop" data-close-confirm-modal="true"></div>
                    <div class="guest-modal__content guest-modal__content--confirm" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle">
                        <div class="guest-modal__confirm-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <h3 id="confirmModalTitle" class="guest-modal__title guest-modal__title--confirm">Confirm Action</h3>
                        <p id="confirmModalMessage" class="guest-modal__message">Are you sure you want to proceed?</p>
                        <div class="guest-modal__actions">
                            <button type="button" class="guest-form__secondary" id="confirmModalCancel">No</button>
                            <button type="button" class="guest-form__button" id="confirmModalConfirm">Yes</button>
                        </div>
                    </div>
                </div>

                <div class="guest-modal guest-modal--success" id="successModal" aria-hidden="true">
                    <div class="guest-modal__backdrop" data-close-success-modal="true"></div>
                    <div class="guest-modal__content guest-modal__content--success" role="dialog" aria-modal="true" aria-labelledby="successModalTitle">
                        <div class="guest-modal__success-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 id="successModalTitle" class="guest-modal__title guest-modal__title--success">Success</h3>
                        <p id="successModalMessage" class="guest-modal__message">Operation completed successfully!</p>
                        <div class="guest-modal__actions">
                            <button type="button" class="guest-form__button" id="successModalClose">OK</button>
                        </div>
                    </div>
                </div>

                <div class="guest-modal guest-modal--add" id="checkInModal" aria-hidden="true">
                    <div class="guest-modal__backdrop" data-close-check-in-modal="true"></div>
                    <div class="guest-modal__content guest-modal__content--wide" role="dialog" aria-modal="true" aria-labelledby="checkInModalTitle">
                        <button type="button" class="guest-modal__close" data-close-check-in-modal="true" aria-label="Close check-in form">&times;</button>
                        <h3 id="checkInModalTitle" class="guest-modal__title">Check In Reservation</h3>
                        <form id="checkInForm" class="guest-form" action="#">
                            <div class="guest-form__group">
                                <label class="guest-form__label">Guest mode</label>
                                <div class="guest-form__chips">
                                    <label class="guest-form__chip">
                                        <input type="radio" name="check_in_guest_mode" value="with_primary" checked>
                                        <span>With primary guest</span>
                                    </label>
                                    <label class="guest-form__chip">
                                        <input type="radio" name="check_in_guest_mode" value="visitors_only">
                                        <span>Visitors only</span>
                                    </label>
                                </div>
                            </div>

                            <div id="checkInPrimaryGuestSection" class="guest-form__section">
                                <div class="guest-form__section-header">
                                    <h4 class="guest-form__section-title">Primary guest</h4>
                                </div>
                                <div class="guest-form__row guest-form__row--three">
                                    <label class="guest-form__field">
                                        <span>First name</span>
                                        <input type="text" name="check_in_primary_guest[first_name]" placeholder="First name">
                                    </label>
                                    <label class="guest-form__field">
                                        <span>Middle name</span>
                                        <input type="text" name="check_in_primary_guest[middle_name]" placeholder="Middle name">
                                    </label>
                                    <label class="guest-form__field">
                                        <span>Last name</span>
                                        <input type="text" name="check_in_primary_guest[last_name]" placeholder="Last name">
                                    </label>
                                </div>
                                <div class="guest-form__row guest-form__row--three">
                                    <label class="guest-form__field">
                                        <span>Age</span>
                                        <input type="number" name="check_in_primary_guest[age]" min="0" placeholder="Age">
                                    </label>
                                    <label class="guest-form__field">
                                        <span>Gender</span>
                                        <select name="check_in_primary_guest[gender]">
                                            <option value="">Select gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </label>
                                    <label class="guest-form__field">
                                        <span>Nationality</span>
                                        <select name="check_in_primary_guest[is_foreigner]" id="checkInPrimaryIsForeigner">
                                            <option value="0" selected>Filipino</option>
                                            <option value="1">Foreigner</option>
                                        </select>
                                    </label>
                                </div>
                                <div class="guest-form__row guest-form__row--two">
                                    <label class="guest-form__field">
                                        <span>Phone</span>
                                        <input type="text" name="check_in_primary_guest[phone]" placeholder="Phone number">
                                    </label>
                                    <label class="guest-form__field">
                                        <span>Email</span>
                                        <input type="email" name="check_in_primary_guest[email]" placeholder="Email address">
                                    </label>
                                </div>
                            </div>

                            <div class="guest-form__section">
                                <div class="guest-form__section-header">
                                    <h4 class="guest-form__section-title">Companions</h4>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button type="button" class="guest-form__secondary" id="checkInAddCompanionBtn">+ Add Single</button>
                                        <button type="button" class="guest-form__secondary" id="checkInBulkCompanionBtn">+ Add Bulk</button>
                                    </div>
                                </div>
                                <div id="checkInCompanionList" class="guest-companion-list"></div>
                                <div id="checkInCompanionHiddenFields"></div>
                            </div>

                            <div class="guest-form__actions">
                                <button type="button" class="guest-form__secondary" data-close-check-in-modal="true">Cancel</button>
                                <button type="submit" class="guest-form__button">Check In</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="guest-modal guest-modal--add" id="scanQrModal" aria-hidden="true">
                    <div class="guest-modal__backdrop" data-close-scan-modal="true"></div>
                    <div class="guest-modal__content guest-modal__content--wide" role="dialog" aria-modal="true" aria-labelledby="scanQrModalTitle" style="display: flex; flex-direction: row; background: var(--hp-cream);">
                        <button type="button" class="guest-modal__close" data-close-scan-modal="true" aria-label="Close QR scanner">&times;</button>
                        <div style="flex: 1; padding: 1.5rem; display: flex; flex-direction: column; justify-content: center;">
                            <h3 id="scanQrModalTitle" class="guest-modal__title" style="color: var(--hp-text); margin-bottom: 1.5rem;">Scan Reservation QR</h3>
                            <p class="scan-modal__hint" style="color: var(--hp-text); margin-bottom: 1.5rem; line-height: 1.6;">Allow camera access and hold the reservation QR code in front of the lens.</p>
                            <label class="guest-form__field" style="margin-bottom: 1rem;">
                                <span style="color: var(--hp-text); font-weight: 600; display: block; margin-bottom: 0.5rem;">Camera</span>
                                <select id="qrCameraSelect" style="width:100%; padding:0.75rem 0.85rem; border:1px solid var(--hp-green-dark); border-radius:0.75rem; background:#fff; color: #000;"></select>
                            </label>
                            <div class="scan-modal__status" id="qrScannerStatus" style="color: var(--hp-text); margin-bottom: 1.5rem; font-weight: 500;">Ready to scan</div>
                            <div class="guest-form__actions" style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: auto;">
                                <button type="button" class="guest-form__button" id="stopQrBtn" style="background-color: var(--hp-green-dark); color: white; border: none; padding: 0.75rem 1rem; border-radius: 0.5rem; cursor: pointer; font-weight: 500;">Stop Scanner</button>
                            </div>
                        </div>
                        <div style="flex: 1; padding: 1.5rem; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.05);">
                            <div id="qrScanner" class="scan-modal__scanner" style="width: 100%; max-width: 400px; height: 300px; background: #000; border-radius: 0.75rem; overflow: hidden;"></div>
                        </div>
                    </div>
                </div>

                <div class="guest-modal guest-modal--compact" id="checkInCompanionModal" aria-hidden="true">
                    <div class="guest-modal__backdrop" data-close-check-in-companion-modal="true"></div>
                    <div class="guest-modal__content guest-modal__content--compact" role="dialog" aria-modal="true" aria-labelledby="checkInCompanionModalTitle">
                        <button type="button" class="guest-modal__close" data-close-check-in-companion-modal="true" aria-label="Close companion form">&times;</button>
                        <h3 id="checkInCompanionModalTitle" class="guest-modal__title">Add Companion</h3>
                        <form id="checkInCompanionForm" class="guest-form" action="#">
                            <div class="guest-form__row guest-form__row--three">
                                <label class="guest-form__field">
                                    <span>First name</span>
                                    <input type="text" name="first_name" placeholder="First name">
                                </label>
                                <label class="guest-form__field">
                                    <span>Middle name</span>
                                    <input type="text" name="middle_name" placeholder="Middle name">
                                </label>
                                <label class="guest-form__field">
                                    <span>Last name</span>
                                    <input type="text" name="last_name" placeholder="Last name">
                                </label>
                            </div>
                            <div class="guest-form__row guest-form__row--three">
                                <label class="guest-form__field">
                                    <span>Age</span>
                                    <input type="number" name="age" min="0" placeholder="Age">
                                </label>
                                <label class="guest-form__field">
                                    <span>Gender</span>
                                    <select name="gender">
                                        <option value="">Select gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </label>
                                <label class="guest-form__field">
                                    <span>Nationality</span>
                                    <select name="is_foreigner" id="checkInCompanionIsForeigner">
                                        <option value="0" selected>Filipino</option>
                                        <option value="1">Foreigner</option>
                                    </select>
                                </label>
                            </div>
                            <div class="guest-form__row guest-form__row--two">
                                <label class="guest-form__field">
                                    <span>Phone</span>
                                    <input type="text" name="phone" placeholder="Phone number">
                                </label>
                                <label class="guest-form__field">
                                    <span>Email</span>
                                    <input type="email" name="email" placeholder="Email address">
                                </label>
                            </div>
                            <div class="guest-form__actions">
                                <button type="button" class="guest-form__secondary" data-close-check-in-companion-modal="true">Cancel</button>
                                <button type="submit" class="guest-form__button">Add Companion</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="guest-modal guest-modal--compact" id="checkInConfirmationModal" aria-hidden="true">
                    <div class="guest-modal__backdrop" data-close-check-in-confirmation="true"></div>
                    <div class="guest-modal__content guest-modal__content--compact" role="dialog" aria-modal="true" aria-labelledby="checkInConfirmationTitle">
                        <button type="button" class="guest-modal__close" data-close-check-in-confirmation="true" aria-label="Close confirmation">&times;</button>
                        <h3 id="checkInConfirmationTitle" class="guest-modal__title">Check In Reservation</h3>
                        <div id="checkInConfirmationBody" class="guest-modal__body"></div>
                        <div class="guest-form__actions">
                            <button type="button" class="guest-form__secondary" data-close-check-in-confirmation="true">Cancel</button>
                            <button type="button" class="guest-form__button" id="confirmCheckInBtn">Yes, Check In</button>
                        </div>
                    </div>
                </div>

                <div class="guest-modal guest-modal--compact" id="companionSummaryModal" aria-hidden="true">
                    <div class="guest-modal__backdrop" data-close-companion-summary="true"></div>
                    <div class="guest-modal__content guest-modal__content--compact" role="dialog" aria-modal="true" aria-labelledby="companionSummaryTitle">
                        <button type="button" class="guest-modal__close" data-close-companion-summary="true" aria-label="Close summary">&times;</button>
                        <h3 id="companionSummaryTitle" class="guest-modal__title">Companion Groups Summary</h3>
                        <div id="companionSummaryBody" class="guest-modal__body"></div>
                        <div class="guest-form__actions">
                            <button type="button" class="guest-form__secondary" data-close-companion-summary="true">Cancel</button>
                            <button type="button" class="guest-form__button" id="proceedToCheckInBtn">Proceed to Check In</button>
                        </div>
                    </div>
                </div>

                <div class="guest-modal guest-modal--compact" id="bulkCompanionModal" aria-hidden="true">
                    <div class="guest-modal__backdrop" data-close-bulk-companion-modal="true"></div>
                    <div class="guest-modal__content guest-modal__content--compact" role="dialog" aria-modal="true" aria-labelledby="bulkCompanionModalTitle">
                        <button type="button" class="guest-modal__close" data-close-bulk-companion-modal="true" aria-label="Close bulk companion form">&times;</button>
                        <h3 id="bulkCompanionModalTitle" class="guest-modal__title">Add Companions in Bulk</h3>
                        <form id="bulkCompanionForm" class="guest-form" action="#">
                            <div class="guest-form__row guest-form__row--two">
                                <label class="guest-form__field">
                                    <span>Gender</span>
                                    <select name="gender" id="bulkCompanionGender">
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </label>
                                <label class="guest-form__field">
                                    <span>Nationality</span>
                                    <select name="is_foreigner" id="bulkCompanionIsForeigner">
                                        <option value="0" selected>Filipino</option>
                                        <option value="1">Foreigner</option>
                                    </select>
                                </label>
                            </div>
                            <div class="guest-form__row guest-form__row--two">
                                <label class="guest-form__field">
                                    <span>Age Group</span>
                                    <select name="age_group" id="bulkCompanionAgeGroup">
                                        <option value="0-12">Kids (0-12)</option>
                                        <option value="13-17">Teens (13-17)</option>
                                        <option value="18-59">Adults (18-59)</option>
                                        <option value="60+">Seniors (60+)</option>
                                    </select>
                                </label>
                                <label class="guest-form__field">
                                    <span>Quantity</span>
                                    <input type="number" name="quantity" id="bulkCompanionQuantity" min="1" max="50" value="1" required>
                                </label>
                            </div>
                            <div class="guest-form__actions">
                                <button type="button" class="guest-form__secondary" data-close-bulk-companion-modal="true">Cancel</button>
                                <button type="submit" class="guest-form__button" id="generateCompanionsBtn">Generate Companions</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
        window.staffReservationData = @json($reservationData ?? []);
    </script>
</body>
</html>
