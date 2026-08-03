
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>Check Ins — Hinaguan Nature Park</title>
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
		'resources/css/staff_css/staff_check_ins.css',
		'resources/css/chatbot.css',
		'resources/components/css_js/header.js',
		'resources/components/css_js/sidemenu.js',
		'resources/js/staff_js/staff_check_ins.js',
		'resources/js/staff_js/staff_reservations.js',
		'resources/js/staff_chatbot.js',
	])
	<style>
		.dash-main::before {
			background-image: url('{{ asset('storage/design_images/background_image1.png') }}');
		}
	</style>
</head>
<body class="antialiased">
	<div class="dash-layout">
		<x-staff_sidemenu active="checkins" userName="{{ session('auth_user.name') ?? 'Staff User' }}" userRole="Staff" />

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
				title="Check Ins"
				subtitle="Active check-ins and walk-ins"
			/>

			<main class="dash-content">
				<section class="dash-panel guest-panel">
					<div class="dash-panel__head guest-panel__head">
						<div>
							<h3 class="dash-panel__title">Current Check-ins</h3>
							<p class="dash-panel__subtitle">Guests currently checked in or not yet checked out</p>
						</div>
						<div style="display: flex; gap: 0.5rem;">
							<button type="button" class="guest-panel__button" id="tabGuestBtn" style="background-color: var(--hp-green-dark); color: white; box-shadow: 0 4px 12px rgba(13, 44, 29, 0.3); transform: translateY(-2px);">Guest</button>
							<button type="button" class="guest-panel__button" id="tabReservationBtn" style="background-color: var(--hp-cream); color: var(--hp-text);">Reservation</button>
							<a href="#" class="guest-panel__button" data-open-add-guest-modal="true" style="background-color: var(--hp-gold); color: white;">Add Guest</a>
							<button type="button" class="guest-panel__button" id="scanQrBtn" style="background-color: var(--hp-green-dark); color: white;">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem;">
									<path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
									<path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
								</svg>
							</button>
						</div>
					</div>

					@if (session('success'))
						<div class="guest-alert">{{ session('success') }}</div>
					@endif

					{{-- GUEST TABLE --}}
					<div id="guestTableSection">
						@php
							$activeCustomers = collect($customers ?? collect())->filter(function ($customer) {
								return $customer->reservationGuests->filter(function ($guest) {
									$reservation = $guest->reservation ?? null;
									if (! $reservation) {
										return false;
									}
									// Skip if guest is individually checked out
									if ($guest->checked_out_at) {
										return false;
									}
									// Only show guests who have been checked in
									if (! $reservation->check_in) {
										return false;
									}
									$status = strtolower(str_replace(' ', '_', (string) ($reservation->status ?? '')));
									return $status !== 'checked_out' && $status !== 'checkedout' && $status !== 'checked-out';
								})->isNotEmpty();
							});

							// Calculate guest summary counts
							$guestSummaryFemale = $activeCustomers->filter(function ($customer) {
								return strtolower((string) ($customer->gender ?? '')) === 'female';
							})->count();

							$guestSummaryMale = $activeCustomers->filter(function ($customer) {
								return strtolower((string) ($customer->gender ?? '')) === 'male';
							})->count();

							$guestSummaryForeign = $activeCustomers->filter(function ($customer) {
								return (bool) ($customer->is_foreigner ?? false);
							})->count();

							$guestSummaryFilipino = $activeCustomers->filter(function ($customer) {
								return ! (bool) ($customer->is_foreigner ?? false);
							})->count();
						@endphp

						<div class="guest-summary">
							<div class="guest-summary-card">
								<span>Total active guests</span>
								<strong id="guestSummaryTotal">{{ $activeCustomers->count() }}</strong>
							</div>
							<div class="guest-summary-card">
								<span>Female</span>
								<strong id="guestSummaryFemale">{{ $guestSummaryFemale }}</strong>
							</div>
						<div class="guest-summary-card">
							<span>Male</span>
							<strong id="guestSummaryMale">{{ $guestSummaryMale }}</strong>
						</div>
						<div class="guest-summary-card">
							<span>Foreign</span>
							<strong id="guestSummaryForeign">{{ $guestSummaryForeign }}</strong>
						</div>
						<div class="guest-summary-card">
							<span>Filipino</span>
							<strong id="guestSummaryFilipino">{{ $guestSummaryFilipino }}</strong>
						</div>
					</div>

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
									<option value="reservation-asc">Reservation Type</option>
								</select>
							</label>
							<label class="guest-toolbar__field">
								<span>Check-in from</span>
								<input type="date" id="guestCheckInFrom">
							</label>
							<label class="guest-toolbar__field">
								<span>Check-in to</span>
								<input type="date" id="guestCheckInTo">
							</label>
							<label class="guest-toolbar__field">
								<span>Reservation ID</span>
								<select id="guestReservationSelect">
									<option value="">All Reservations</option>
									@php
										$filteredReservations = collect($reservations ?? collect())->filter(function ($reservation) {
											$status = strtolower(str_replace(' ', '_', (string) ($reservation->status ?? '')));
											return $status !== 'checked_out' && $status !== 'checkedout' && $status !== 'checked-out' && $reservation->check_in;
										});
									@endphp
									@forelse ($filteredReservations as $reservation)
										@php
											$primaryGuest = $reservation->reservationGuests->first(function ($guest) {
												return $guest->is_primary_guest && $guest->customer;
											});
											$primaryGuestName = $primaryGuest?->customer ? 
												trim(($primaryGuest->customer->first_name ?? '') . ' ' . ($primaryGuest->customer->last_name ?? '')) : 
												$reservation->booker_name ?? 'Unknown';
										@endphp
										<option value="{{ $reservation->id }}">#{{ $reservation->id }} - {{ $primaryGuestName }}</option>
									@empty
										{{-- No active reservations --}}
									@endforelse
								</select>
							</label>
							<button type="button" class="guest-toolbar__clear" id="guestFiltersClear">Clear</button>
						</div>
					</div>

					<div class="guest-toolbar__meta">
						<span id="guestResultsCount">Showing {{ $activeCustomers->count() }} active guests</span>
					</div>

					<div class="guest-table-wrap" id="guestTableWrap">
						<table class="guest-table">
							<thead>
								<tr>
									<th>Name</th>
									<th>Age</th>
									<th>Gender</th>
									<th>Status</th>
									<th>Reservation Type</th>
								</tr>
							</thead>
							<tbody id="guestTableBody">
								@forelse ($customers ?? collect() as $customer)
									@php
										$hasActiveReservation = $customer->reservationGuests->filter(function ($guest) {
											$reservation = $guest->reservation ?? null;
											if (! $reservation) return false;
											// Skip if guest is individually checked out
											if ($guest->checked_out_at) return false;
											// Skip if reservation is checked out
											$status = strtolower(str_replace(' ', '_', (string) ($reservation->status ?? '')));
											return $status !== 'checked_out' && $status !== 'checkedout' && $status !== 'checked-out';
										})->isNotEmpty();
									@endphp

									@if (! $hasActiveReservation)
										@continue
									@endif

									@php
										$reservationEntry = $customer->reservationGuests->filter(function ($guest) {
											return $guest->reservation && !$guest->checked_out_at;
										})->first(function ($guest) {
											return $guest->reservation && $guest->reservation->reservation_type === 'walk_in';
										}) ?? $customer->reservationGuests->filter(function ($guest) {
											return $guest->reservation && !$guest->checked_out_at;
										})->first();
										$reservationType = $reservationEntry?->reservation?->reservation_type;
										$reservationTypeLabel = $reservationType === 'walk_in' ? 'walk-in' : ($reservationType ?? 'N/A');
									@endphp
									<tr
										class="guest-row"
										data-customer-id="{{ $customer->id }}"
										data-reservation-id="{{ $reservationEntry?->reservation?->id ?? '' }}"
										data-age="{{ $customer->age ?? 'N/A' }}"
										data-gender="{{ strtolower((string) ($customer->gender ?? 'N/A')) }}"
											data-check-in="{{ $reservationEntry?->reservation?->check_in ?? '' }}"
											data-check-out="{{ $reservationEntry?->reservation?->check_out ?? '' }}"
											data-checked-out-at="{{ $reservationEntry?->checked_out_at ?? '' }}"
											data-status="{{ $reservationEntry?->reservation?->status ?? 'N/A' }}"
											data-age-value="{{ is_numeric($customer->age) ? (int) $customer->age : 999999 }}"
											data-is-foreign="{{ (bool) ($customer->is_foreigner ?? false) ? 'true' : 'false' }}"
											data-search="{{ strtolower(trim(($customer->first_name ?? '') . ' ' . ($customer->middle_name ?? '') . ' ' . ($customer->last_name ?? '') . ' ' . $customer->id . ' ' . ($customer->gender ?? '') . ' ' . ($customer->is_foreigner ? 'Foreigner' : 'Filipino') . ' ' . $reservationTypeLabel)) }}"
										data-is-foreigner="{{ $customer->is_foreigner ? 'Foreigner' : 'Filipino' }}"
										data-reservation-type="{{ $reservationTypeLabel }}"
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
										<td>{{ $reservationTypeLabel }}</td>
									</tr>
								@empty
									<tr>
										<td colspan="5" class="guest-empty">No active check-ins found.</td>
									</tr>
								@endforelse
							</tbody>
						</table>
					</div>
					</div>
				</section>

				{{-- RESERVATION TABLE --}}
				<section class="dash-panel guest-panel" id="reservationTableSection">
					<div class="dash-panel__head guest-panel__head">
						<div>
							<h3 class="dash-panel__title">Active Reservations</h3>
							<p class="dash-panel__subtitle">Reservations checked in but not yet checked out</p>
						</div>
					</div>

					<div class="guest-summary">
						<div class="guest-summary-card">
							<span>Total active reservations</span>
							<strong id="reservationSummaryTotal">{{ count($activeReservations ?? []) }}</strong>
						</div>
						<div class="guest-summary-card">
							<span>Total amount</span>
							<strong id="reservationSummaryTotalAmount">₱{{ number_format(collect($activeReservations ?? [])->sum('total_amount'), 2) }}</strong>
						</div>
						<div class="guest-summary-card">
							<span>Total guests</span>
							<strong id="reservationSummaryTotalGuests">{{ collect($activeReservations ?? [])->sum('number_of_guests') }}</strong>
						</div>
					</div>

					<div class="guest-table-wrap" id="reservationTableWrap">
						<table class="guest-table">
							<thead>
								<tr>
									<th>Reservation</th>
									<th>Check-in</th>
									<th>Main Guest</th>
									<th>Guests Count</th>
									<th>Amenities</th>
								</tr>
							</thead>
							<tbody id="reservationTableBody">
								@forelse ($activeReservations ?? collect() as $reservation)
									@php
										$primaryGuest = $reservation->reservationGuests->firstWhere('is_primary_guest', true)?->customer;
									@endphp
									<tr
										class="reservation-row"
										data-reservation-id="{{ $reservation->id }}"
										tabindex="0"
										role="button"
										aria-label="View reservation {{ $reservation->id }}"
									>
										<td>
											<div class="guest-name">Reservation #{{ $reservation->id }}</div>
											<div class="guest-meta">{{ $reservation->reservation_type === 'walk_in' ? 'Walk-in' : 'Online' }}</div>
										</td>
										<td>{{ $reservation->check_in }}</td>
										<td>
											@if ($primaryGuest)
												<div class="guest-name">{{ trim(($primaryGuest->first_name ?? '') . ' ' . ($primaryGuest->middle_name ?? '') . ' ' . ($primaryGuest->last_name ?? '')) }}</div>
											@else
												<div class="guest-name">—</div>
											@endif
										</td>
										<td>{{ $reservation->number_of_guests }}</td>
										<td>
											@php
												$amenityNames = $reservation->reservationAmenities->pluck('amenity.amenities_name')->filter()->unique()->join(', ');
											@endphp
											{{ $amenityNames ?: '—' }}
										</td>
									</tr>
								@empty
									<tr>
										<td colspan="5" class="guest-empty">No active reservations found.</td>
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
							<span id="guestModalRole" class="guest-modal__role-badge"></span>
						</div>
						<div id="guestModalBody" class="guest-modal__body"></div>
						<div class="guest-form__actions" id="guestModalActions">
							<button type="button" class="guest-form__button--secondary" data-close-modal="true">Close</button>
							<button type="button" class="guest-form__button" id="guestCheckOutBtn">Check Out</button>
						</div>
					</div>
				</div>

				<div class="guest-modal guest-modal--add" id="addGuestModal" aria-hidden="true">
					<div class="guest-modal__backdrop" data-close-add-modal="true"></div>
					<div class="guest-modal__content guest-modal__content--wide" role="dialog" aria-modal="true" aria-labelledby="addGuestModalTitle">
						<button type="button" class="guest-modal__close" data-close-add-modal="true" aria-label="Close add guest form">&times;</button>
						<h3 id="addGuestModalTitle" class="guest-modal__title">Add Guest Reservation</h3>
						<form id="addGuestForm" class="guest-form" action="{{ route('staff.checkins.guests.store') }}" method="POST">
							@csrf
							<div class="guest-form__group">
								<label class="guest-form__label">Guest mode</label>
								<div class="guest-form__chips">
									<label class="guest-form__chip">
										<input type="radio" name="guest_mode" value="with_primary" checked>
										<span>With primary guest</span>
									</label>
									<label class="guest-form__chip">
										<input type="radio" name="guest_mode" value="visitors_only">
										<span>Visitors only</span>
									</label>
								</div>
							</div>

							<div class="guest-form__row">
								<label class="guest-form__field">
									<span>Reservation type</span>
									<select name="reservation_type" required>
										<option value="online">Online</option>
										<option value="walk_in">Walk-in</option>
									</select>
								</label>
							</div>

							<div class="guest-form__row">
								<label class="guest-form__field">
									<span>Check-in date</span>
									<input type="date" name="check_in" value="{{ now()->toDateString() }}" required>
								</label>
							</div>
							<div id="primaryGuestSection" class="guest-form__section">
								<div class="guest-form__section-header">
									<h4 class="guest-form__section-title">Primary guest</h4>
								</div>
								<div class="guest-form__row guest-form__row--three">
									<label class="guest-form__field">
										<span>First name</span>
										<input type="text" name="primary_guest[first_name]" placeholder="First name">
									</label>
									<label class="guest-form__field">
										<span>Middle name</span>
										<input type="text" name="primary_guest[middle_name]" placeholder="Middle name">
									</label>
									<label class="guest-form__field">
										<span>Last name</span>
										<input type="text" name="primary_guest[last_name]" placeholder="Last name">
									</label>
								</div>
								<div class="guest-form__row guest-form__row--three">
									<label class="guest-form__field">
										<span>Age</span>
										<input type="number" name="primary_guest[age]" min="0" placeholder="Age">
									</label>
									<label class="guest-form__field">
										<span>Gender</span>
										<select name="primary_guest[gender]">
											<option value="">Select gender</option>
											<option value="Male">Male</option>
											<option value="Female">Female</option>
										</select>
									</label>
									<label class="guest-form__field">
										<span>Nationality</span>
											<select name="primary_guest[is_foreigner]" id="primaryGuestIsForeigner">
												<option value="0" selected>Filipino</option>
												<option value="1">Foreigner</option>
											</select>
										</label>
								</div>
								<div class="guest-form__row guest-form__row--two">
									<label class="guest-form__field">
										<span>Phone</span>
										<input type="text" name="primary_guest[phone]" placeholder="Phone number">
									</label>
									<label class="guest-form__field">
										<span>Email</span>
										<input type="email" name="primary_guest[email]" placeholder="Email address">
									</label>
								</div>
							</div>

							<div class="guest-form__section">
								<div class="guest-form__section-header">
									<h4 class="guest-form__section-title">Companions</h4>
									<button type="button" class="guest-form__secondary" id="addCompanionBtn">+ Add Companion</button>
								</div>
								<div id="companionList" class="guest-companion-list"></div>
								<div id="companionHiddenFields"></div>
							</div>

							<div class="guest-form__section">
								<div class="guest-form__section-header">
									<h4 class="guest-form__section-title">Amenities</h4>
									<button type="button" class="guest-form__secondary" id="chooseAmenitiesBtn">Choose Amenities</button>
								</div>
								<div id="selectedAmenitiesContainer"></div>
								<div class="guest-form__summary">
									<span>Total</span>
									<strong id="reservationTotal">₱0.00</strong>
								</div>
								<input type="hidden" name="total_amount" id="totalAmountInput" value="0">
							</div>

							<div class="guest-form__actions">
								<button type="button" class="guest-form__secondary" data-close-add-modal="true">Cancel</button>
								<button type="submit" class="guest-form__button">Create Reservation</button>
							</div>
						</form>
					</div>
				</div>

				<div class="guest-modal guest-modal--compact" id="amenityModal" aria-hidden="true">
					<div class="guest-modal__backdrop" data-close-amenity-modal="true"></div>
					<div class="guest-modal__content guest-modal__content--compact" role="dialog" aria-modal="true" aria-labelledby="amenityModalTitle">
						<button type="button" class="guest-modal__close" data-close-amenity-modal="true" aria-label="Close amenity selection">&times;</button>
						<h3 id="amenityModalTitle" class="guest-modal__title">Choose Amenities</h3>
						<div class="guest-form__amenities" id="amenitiesContainer">
							@forelse ($amenities ?? collect() as $amenity)
								<label class="guest-amenity-option">
									<input type="checkbox" class="amenity-checkbox" value="{{ $amenity->id }}" data-amenity-id="{{ $amenity->id }}" data-amenity-name="{{ $amenity->amenities_name }}">
									<span class="guest-amenity-option__body">
										<strong>{{ $amenity->amenities_name }}</strong>
										<small>Choose a pricing option</small>
									</span>
									<select class="guest-amenity-option__select" disabled>
										@if ($amenity->daytime_price !== null)
											<option value="Daytime" data-price="{{ $amenity->daytime_price }}">Daytime — ₱{{ number_format($amenity->daytime_price, 2) }}</option>
										@endif
										@if ($amenity->nighttime_price !== null)
											<option value="Nighttime" data-price="{{ $amenity->nighttime_price }}">Nighttime — ₱{{ number_format($amenity->nighttime_price, 2) }}</option>
										@endif
										@if ($amenity->daytime_aircon_price !== null)
											<option value="Daytime Aircon" data-price="{{ $amenity->daytime_aircon_price }}">Daytime Aircon — ₱{{ number_format($amenity->daytime_aircon_price, 2) }}</option>
										@endif
										@if ($amenity->nighttime_aircon_price !== null)
											<option value="Nighttime Aircon" data-price="{{ $amenity->nighttime_aircon_price }}">Nighttime Aircon — ₱{{ number_format($amenity->nighttime_aircon_price, 2) }}</option>
										@endif
									</select>
								</label>
							@empty
								<p class="guest-empty">No active amenities are available yet.</p>
							@endforelse
						</div>
					</div>
				</div>

				<div class="guest-modal guest-modal--compact" id="companionModal" aria-hidden="true">
					<div class="guest-modal__backdrop" data-close-companion-modal="true"></div>
					<div class="guest-modal__content guest-modal__content--compact" role="dialog" aria-modal="true" aria-labelledby="companionModalTitle">
						<button type="button" class="guest-modal__close" data-close-companion-modal="true" aria-label="Close companion form">&times;</button>
						<h3 id="companionModalTitle" class="guest-modal__title">Add Companion</h3>
						<form id="companionForm" class="guest-form" action="#">
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
									<select name="is_foreigner" id="companionIsForeigner">
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
								<button type="button" class="guest-form__secondary" data-close-companion-modal="true">Cancel</button>
								<button type="submit" class="guest-form__button">Add Companion</button>
							</div>
						</form>
					</div>
				</div>

				{{-- Check In Modal (used when scanning a reservation) --}}
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
									<button type="button" class="guest-form__secondary" id="checkInAddCompanionBtn">+ Add Companion</button>
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

				{{-- Check Out Confirmation Modal --}}
				<div class="guest-modal" id="checkOutConfirmModal" aria-hidden="true">
					<div class="guest-modal__backdrop" data-close-check-out-confirm="true"></div>
					<div class="guest-modal__content guest-modal__content--compact" role="dialog" aria-modal="true" aria-labelledby="checkOutConfirmTitle">
						<button type="button" class="guest-modal__close" data-close-check-out-confirm="true" aria-label="Close confirmation">&times;</button>
						<h3 id="checkOutConfirmTitle" class="guest-modal__title">Confirm Check Out</h3>
						<p style="margin-bottom: 1.5rem; color: #666;">Are you sure you want to check out this reservation? This action cannot be undone.</p>
						<div class="guest-form__actions">
							<button type="button" class="guest-form__button--secondary" data-close-check-out-confirm="true">Cancel</button>
							<button type="button" class="guest-form__button" id="confirmCheckOutBtn">Yes, Check Out</button>
						</div>
					</div>
				</div>

				{{-- Reservation Detail Modal --}}
				<div class="guest-modal" id="reservationModal" aria-hidden="true">
					<div class="guest-modal__backdrop" data-close-reservation-modal="true"></div>
					<div class="guest-modal__content" role="dialog" aria-modal="true" aria-labelledby="reservationModalTitle">
						<button type="button" class="guest-modal__close" data-close-reservation-modal="true" aria-label="Close details">&times;</button>
						<div class="guest-modal__header">
							<h3 id="reservationModalTitle" class="guest-modal__title">Reservation Details</h3>
							<span id="reservationModalStatus" class="guest-modal__role-badge"></span>
							<button type="button" class="guest-form__button guest-form__button--small" id="reservationCheckOutBtn" style="margin-left: auto;">Check Out</button>
						</div>
						<div id="reservationModalBody" class="guest-modal__body"></div>
						<div class="guest-form__actions" id="reservationModalActions">
							<button type="button" class="guest-form__button--secondary" data-close-reservation-modal="true">Close</button>
						</div>
					</div>
				</div>

				{{-- Scan QR Modal --}}
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

				{{-- Companion modal used by check-in flow --}}
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

				</main>
			</div>
		</div>
	</div>

	<x-staff_chatbot />

	<script>
		window.staffGuestData = @json($guestData ?? []);
		window.staffReservationData = @json($reservationData ?? []);
	</script>
</body>
</html>
