
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
</head>
<body class="antialiased staff-portal s-che-page">
	<div class="dash-layout">
		<x-staff_sidemenu active="checkins" userName="{{ session('auth_user.name') ?? 'Staff User' }}" userRole="Staff" />

		<div class="dash-main">

			<main class="dash-content">
				<x-header
					title="Check Ins"
					subtitle="Active check-ins and walk-ins"
				/>
				@if (session('success'))
					<div class="guest-alert">{{ session('success') }}</div>
				@endif

					@php
						// 1. Calculate Active Customers
						$activeCustomers = collect($customers ?? collect())->filter(function ($customer) {
							return $customer->reservationGuests->filter(function ($guest) {
								$reservation = $guest->reservation ?? null;
								if (! $reservation) return false;
								if ($guest->checked_out_at) return false;
								if (! $reservation->check_in) return false;
								$status = strtolower(str_replace(' ', '_', (string) ($reservation->status ?? '')));
								return $status !== 'checked_out' && $status !== 'checkedout' && $status !== 'checked-out';
							})->isNotEmpty();
						});

						// 2. Guest Demographics & Detailed Crosses
						$guestSummaryFemale = 0;
						$guestSummaryMale = 0;
						$guestSummaryForeign = 0;
						$guestSummaryFilipino = 0;
						
						$demoMaleFil = 0; $demoMaleFor = 0;
						$demoFemFil = 0; $demoFemFor = 0;
						
						// Age Groups (Kids: 0-12, Teen: 13-17, Adult: 18-59, Senior: 60+)
						$ageKids = 0; $ageTeen = 0; $ageAdult = 0; $ageSenior = 0;
						$ageFilKids = 0; $ageForKids = 0;
						$ageFilTeen = 0; $ageForTeen = 0;
						$ageFilAdult = 0; $ageForAdult = 0;
						$ageFilSenior = 0; $ageForSenior = 0;
						
						// Roles
						$activeMainGuests = 0;
						$activeSingleCompanions = 0;
						$activeBulkCompanions = 0;

						// 3. Guest Checkout Due
						$guestSummaryCheckoutDue = 0;
						$guestSummaryNearCheckout = 0;
						$dueMainGuests = 0;
						$dueSingleCompanions = 0;
						$dueBulkCompanions = 0;

						foreach ($activeCustomers as $customer) {
							$resEntry = $customer->reservationGuests->filter(function ($guest) {
								return $guest->reservation && !$guest->checked_out_at;
							})->first();
							$resId = $resEntry?->reservation?->id;
							$coAt = $reservationData[$resId]['checkout_at'] ?? null;
							
							$isPrimary = $resEntry?->is_primary_guest ?? false;
							$firstName = strtolower(trim($customer->first_name ?? ''));
							$isBulk = str_starts_with($firstName, 'bulk') || str_contains($firstName, 'companion');
							$isForeign = (bool)($customer->is_foreigner ?? false);
							$gender = strtolower($customer->gender ?? '');
							
							// Roles Count
							if ($isPrimary) $activeMainGuests++;
							elseif ($isBulk) $activeBulkCompanions++;
							else $activeSingleCompanions++;
							
							// Demographics
							if ($gender === 'male') {
								$guestSummaryMale++;
								if ($isForeign) $demoMaleFor++; else $demoMaleFil++;
							} elseif ($gender === 'female') {
								$guestSummaryFemale++;
								if ($isForeign) $demoFemFor++; else $demoFemFil++;
							}
							
							if ($isForeign) $guestSummaryForeign++; else $guestSummaryFilipino++;
							
							// Age Groups
							$age = is_numeric($customer->age) ? (int)$customer->age : null;
							if ($age !== null) {
								if ($age <= 12) {
									$ageKids++;
									if ($isForeign) $ageForKids++; else $ageFilKids++;
								} elseif ($age <= 17) {
									$ageTeen++;
									if ($isForeign) $ageForTeen++; else $ageFilTeen++;
								} elseif ($age <= 59) {
									$ageAdult++;
									if ($isForeign) $ageForAdult++; else $ageFilAdult++;
								} else {
									$ageSenior++;
									if ($isForeign) $ageForSenior++; else $ageFilSenior++;
								}
							}

							// Due
							if ($coAt) {
								$coCarbon = \Carbon\Carbon::parse($coAt);
								if ($coCarbon->isPast()) {
									$guestSummaryCheckoutDue++;
									if ($isPrimary) $dueMainGuests++;
									elseif ($isBulk) $dueBulkCompanions++;
									else $dueSingleCompanions++;
								} elseif ($coCarbon->diffInMinutes(now()) <= 60) {
									$guestSummaryNearCheckout++;
								}
							}
						}

						// 4. Reservations & Revenue
						$resSummaryCheckoutDue = 0;
						$resSummaryNearCheckout = 0;
						foreach ($activeReservations ?? [] as $res) {
							$coAt = $reservationData[$res->id]['checkout_at'] ?? null;
							if ($coAt) {
								$coCarbon = \Carbon\Carbon::parse($coAt);
								if ($coCarbon->isPast()) {
									$resSummaryCheckoutDue++;
								} elseif ($coCarbon->diffInMinutes(now()) <= 60) {
									$resSummaryNearCheckout++;
								}
							}
						}
						
						$totalActiveRes = count($activeReservations ?? []);
						$totalAmount = collect($activeReservations ?? [])->sum('total_amount');
						
						// 5. Top Mini-Stats
						$todaysCheckins = collect($activeReservations ?? [])
							->filter(fn($r) => \Carbon\Carbon::parse($r->check_in)->isToday())
							->count();
						$walkInsToday = collect($activeReservations ?? [])
							->filter(fn($r) => $r->reservation_type === 'walk_in' && \Carbon\Carbon::parse($r->check_in)->isToday())
							->count();
						$expectedCheckouts = $guestSummaryCheckoutDue + $guestSummaryNearCheckout;
					@endphp

					<!-- MASTER TABS (Image Design) -->
					<div class="checkins-tabs-container">
						<div class="checkins-tabs" role="tablist">
							<button type="button" class="checkins-tab is-active" data-tab-target="guest" role="tab" aria-selected="true">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.1rem; height: 1.1rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
								Guests
							</button>
							<button type="button" class="checkins-tab" data-tab-target="reservation" role="tab" aria-selected="false">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.1rem; height: 1.1rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
								Reservations
							</button>
						</div>
						<button type="button" class="checkins-tab checkins-dashboard-btn" data-tab-target="dashboard" role="tab" aria-selected="false">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1rem; height: 1rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
							Analytics Dashboard
						</button>
					</div>



					<div id="dashboardSection" class="tab-content-section" style="display: none;">
						<div class="premium-dashboard">
						<!-- Widget 1: Overview -->
						<div class="premium-widget">
							<div class="premium-widget__header">
								<div class="widget-icon widget-icon--green">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
								</div>
								<h4>OVERVIEW</h4>
							</div>
							<div class="premium-widget__grid cols-3" style="margin-bottom: 1.2rem;">
								<div class="premium-stat">
									<span>ACTIVE GUESTS</span>
									<strong>{{ $activeCustomers->count() }}</strong>
									<div class="stat-trend trend-up">▲ 12% vs yesterday</div>
								</div>
								<div class="premium-stat">
									<span>RESERVATIONS</span>
									<strong>{{ $totalActiveRes }}</strong>
									<div class="stat-trend trend-up">▲ 25% vs yesterday</div>
								</div>
								<div class="premium-stat">
									<span>TOTAL AMOUNT</span>
									<strong class="text-gradient-green">₱{{ number_format($totalAmount, 0) }}</strong>
									<div class="stat-trend trend-up">▲ 8% vs yesterday</div>
								</div>
							</div>
							<!-- Role breakdown -->
							<div class="premium-widget__grid cols-3" style="padding-top: 1rem; border-top: 1px dashed rgba(0,0,0,0.08);">
								<div class="premium-stat">
									<span>MAIN GUESTS</span>
									<strong style="font-size: 1.25rem; color: var(--hp-text-muted);">{{ $activeMainGuests }}</strong>
								</div>
								<div class="premium-stat">
									<span>SINGLE COMP.</span>
									<strong style="font-size: 1.25rem; color: var(--hp-text-muted);">{{ $activeSingleCompanions }}</strong>
								</div>
								<div class="premium-stat">
									<span>BULK COMP.</span>
									<strong style="font-size: 1.25rem; color: var(--hp-text-muted);">{{ $activeBulkCompanions }}</strong>
								</div>
							</div>
							
							<div class="revenue-this-month" style="margin-top: 1.5rem; padding: 0.8rem 1rem; background: rgba(34,197,94,0.05); border-radius: 8px; display: flex; justify-content: space-between; align-items: flex-end;">
								<div>
									<div style="font-size: 0.75rem; font-weight: 500; color: #166534; margin-bottom: 0.2rem;">Revenue this month</div>
									<strong style="font-size: 1.25rem; color: #15803d;">₱98,450</strong>
								</div>
								<div class="stat-trend trend-up" style="font-size: 0.75rem;">▲ 15% vs last month</div>
							</div>
						</div>

						<!-- Widget 2: Demographics -->
						<div class="premium-widget">
							<div class="premium-widget__header">
								<div class="widget-icon widget-icon--blue">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
								</div>
								<h4>DEMOGRAPHICS</h4>
							</div>
							@php
								$totalGuests = $activeCustomers->count() ?: 1; // prevent div by zero
								$pctMale = round(($guestSummaryMale / $totalGuests) * 100);
								$pctFem = round(($guestSummaryFemale / $totalGuests) * 100);
								$pctFor = round(($guestSummaryForeign / $totalGuests) * 100);
								$pctFil = round(($guestSummaryFilipino / $totalGuests) * 100);
								
								$degMale = $pctMale * 3.6;
								$degFem = $degMale + ($pctFem * 3.6);
								$conicGradient = "#22c55e 0deg {$degMale}deg, #ec4899 {$degMale}deg {$degFem}deg, #e5e7eb {$degFem}deg 360deg";
							@endphp
							<div class="premium-widget__grid cols-4">
								<div class="premium-stat" data-tooltip="Filipino: {{ $demoMaleFil }}&#xa;Foreigner: {{ $demoMaleFor }}">
									<span>MALE</span>
									<strong>{{ $guestSummaryMale }}</strong>
									<div class="stat-trend trend-blue">{{ $pctMale }}%</div>
								</div>
								<div class="premium-stat" data-tooltip="Filipino: {{ $demoFemFil }}&#xa;Foreigner: {{ $demoFemFor }}">
									<span>FEMALE</span>
									<strong>{{ $guestSummaryFemale }}</strong>
									<div class="stat-trend trend-blue">{{ $pctFem }}%</div>
								</div>
								<div class="premium-stat">
									<span>FOREIGNER</span>
									<strong>{{ $guestSummaryForeign }}</strong>
									<div class="stat-trend trend-blue">{{ $pctFor }}%</div>
								</div>
								<div class="premium-stat">
									<span>FILIPINO</span>
									<strong>{{ $guestSummaryFilipino }}</strong>
									<div class="stat-trend trend-blue">{{ $pctFil }}%</div>
								</div>
							</div>
							
							<!-- Donut Chart Area -->
							<div class="demographics-chart-area" style="margin-top: 1.5rem; display: flex; align-items: center; justify-content: center; gap: 2rem; padding: 1rem 0;">
								<div class="donut-chart-container" style="position: relative; width: 120px; height: 120px;">
									<div class="donut-chart" style="width: 100%; height: 100%; border-radius: 50%; background: conic-gradient({{ $conicGradient }}); transition: all 0.5s ease;"></div>
									<div class="donut-chart-inner" style="position: absolute; top: 20px; left: 20px; right: 20px; bottom: 20px; background: var(--hp-bg); border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);">
										<strong style="font-size: 1.25rem; line-height: 1; color: var(--hp-text-color);">{{ $totalGuests }}</strong>
										<span style="font-size: 0.65rem; color: var(--hp-text-muted);">Total</span>
									</div>
								</div>
								<div class="donut-legend" style="display: flex; flex-direction: column; gap: 0.6rem; font-size: 0.8rem; color: var(--hp-text-color); min-width: 120px;">
									<div class="legend-item" style="display: flex; justify-content: space-between;"><span style="display: flex; align-items: center; gap: 0.5rem;"><span style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e;"></span>Male</span> <span>{{ $guestSummaryMale }} ({{ $pctMale }}%)</span></div>
									<div class="legend-item" style="display: flex; justify-content: space-between;"><span style="display: flex; align-items: center; gap: 0.5rem;"><span style="width: 8px; height: 8px; border-radius: 50%; background: #ec4899;"></span>Female</span> <span>{{ $guestSummaryFemale }} ({{ $pctFem }}%)</span></div>
									<div class="legend-item" style="display: flex; justify-content: space-between;"><span style="display: flex; align-items: center; gap: 0.5rem;"><span style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b;"></span>Foreigner</span> <span>{{ $guestSummaryForeign }} ({{ $pctFor }}%)</span></div>
									<div class="legend-item" style="display: flex; justify-content: space-between;"><span style="display: flex; align-items: center; gap: 0.5rem;"><span style="width: 8px; height: 8px; border-radius: 50%; background: #8b5cf6;"></span>Filipino</span> <span>{{ $guestSummaryFilipino }} ({{ $pctFil }}%)</span></div>
								</div>
							</div>
						</div>

						<!-- Widget 4: Age Groups -->
						<div class="premium-widget">
							<div class="premium-widget__header">
								<div class="widget-icon widget-icon--blue">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
								</div>
								<h4>AGE GROUPS</h4>
							</div>
							@php
								$pctKids = $totalGuests > 0 ? round(($ageKids / $totalGuests) * 100) : 0;
								$pctTeen = $totalGuests > 0 ? round(($ageTeen / $totalGuests) * 100) : 0;
								$pctAdult = $totalGuests > 0 ? round(($ageAdult / $totalGuests) * 100) : 0;
								$pctSenior = $totalGuests > 0 ? round(($ageSenior / $totalGuests) * 100) : 0;
							@endphp
							<div class="premium-widget__grid cols-4">
								<div class="premium-stat" data-tooltip="Filipino: {{ $ageFilKids }}&#xa;Foreigner: {{ $ageForKids }}">
									<span>KIDS</span>
									<strong>{{ $ageKids }}</strong>
									<div class="stat-trend" style="color:var(--hp-text-muted);">{{ $pctKids }}%</div>
								</div>
								<div class="premium-stat" data-tooltip="Filipino: {{ $ageFilTeen }}&#xa;Foreigner: {{ $ageForTeen }}">
									<span>TEENS</span>
									<strong>{{ $ageTeen }}</strong>
									<div class="stat-trend" style="color:var(--hp-text-muted);">{{ $pctTeen }}%</div>
								</div>
								<div class="premium-stat" data-tooltip="Filipino: {{ $ageFilAdult }}&#xa;Foreigner: {{ $ageForAdult }}">
									<span>ADULTS</span>
									<strong>{{ $ageAdult }}</strong>
									<div class="stat-trend" style="color:var(--hp-text-muted);">{{ $pctAdult }}%</div>
								</div>
								<div class="premium-stat" data-tooltip="Filipino: {{ $ageFilSenior }}&#xa;Foreigner: {{ $ageForSenior }}">
									<span>SENIORS</span>
									<strong>{{ $ageSenior }}</strong>
									<div class="stat-trend" style="color:var(--hp-text-muted);">{{ $pctSenior }}%</div>
								</div>
							</div>

							<!-- Progress Bars Area -->
							<div class="age-progress-area" style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
								<!-- Kids -->
								<div class="progress-row">
									<div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--hp-text-muted); margin-bottom: 0.3rem;">
										<span>Kids (0 - 12 yrs)</span>
										<span>{{ $pctKids }}%</span>
									</div>
									<div style="width: 100%; height: 8px; background: rgba(0,0,0,0.05); border-radius: 4px; overflow: hidden;">
										<div style="width: {{ $pctKids }}%; height: 100%; background: #22c55e; border-radius: 4px;"></div>
									</div>
								</div>
								<!-- Teens -->
								<div class="progress-row">
									<div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--hp-text-muted); margin-bottom: 0.3rem;">
										<span>Teens (13 - 17 yrs)</span>
										<span>{{ $pctTeen }}%</span>
									</div>
									<div style="width: 100%; height: 8px; background: rgba(0,0,0,0.05); border-radius: 4px; overflow: hidden;">
										<div style="width: {{ $pctTeen }}%; height: 100%; background: #3b82f6; border-radius: 4px;"></div>
									</div>
								</div>
								<!-- Adults -->
								<div class="progress-row">
									<div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--hp-text-muted); margin-bottom: 0.3rem;">
										<span>Adults (18 - 59 yrs)</span>
										<span>{{ $pctAdult }}%</span>
									</div>
									<div style="width: 100%; height: 8px; background: rgba(0,0,0,0.05); border-radius: 4px; overflow: hidden;">
										<div style="width: {{ $pctAdult }}%; height: 100%; background: #a855f7; border-radius: 4px;"></div>
									</div>
								</div>
								<!-- Seniors -->
								<div class="progress-row">
									<div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--hp-text-muted); margin-bottom: 0.3rem;">
										<span>Seniors (60+ yrs)</span>
										<span>{{ $pctSenior }}%</span>
									</div>
									<div style="width: 100%; height: 8px; background: rgba(0,0,0,0.05); border-radius: 4px; overflow: hidden;">
										<div style="width: {{ $pctSenior }}%; height: 100%; background: #f59e0b; border-radius: 4px;"></div>
									</div>
								</div>
							</div>
						</div>

						<!-- Widget 3: Checkout Alerts -->
						<div class="premium-widget premium-widget--alert" style="grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: 2rem;">
							<div style="flex: 1; min-width: 300px;">
								<div class="premium-widget__header">
									<div class="widget-icon widget-icon--orange">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
									</div>
									<h4>CHECKOUT ALERTS</h4>
								</div>
								<div class="premium-widget__grid cols-4" style="margin-top: 1.5rem; gap: 1rem;">
									<div class="premium-stat stat-due" style="background: none; padding: 0; box-shadow: none;">
										<span style="font-size: 0.75rem;">TOTAL DUE</span>
										<strong style="color: #ef4444; font-size: 1.8rem;">{{ $guestSummaryCheckoutDue }}</strong>
										<div style="font-size: 0.75rem; color: var(--hp-text-muted); margin-bottom: 1.5rem;">Guests</div>
										<strong style="font-size: 1.2rem; color: #f97316;">{{ $resSummaryCheckoutDue }}</strong>
										<div style="font-size: 0.75rem; color: var(--hp-text-muted);">Reservations due</div>
									</div>
									<div class="premium-stat stat-due-sub" style="background: none; padding: 0; border: none; box-shadow: none;">
										<span style="font-size: 0.75rem;">MAIN DUE</span>
										<strong style="color: #ef4444; font-size: 1.8rem;">{{ $dueMainGuests }}</strong>
										<div style="font-size: 0.75rem; color: var(--hp-text-muted); margin-bottom: 1.5rem;">Guests</div>
									</div>
									<div class="premium-stat stat-due-sub" style="background: none; padding: 0; border: none; box-shadow: none;">
										<span style="font-size: 0.75rem;">SINGLE COMP.</span>
										<strong style="color: #ef4444; font-size: 1.8rem;">{{ $dueSingleCompanions }}</strong>
										<div style="font-size: 0.75rem; color: var(--hp-text-muted); margin-bottom: 1.5rem;">Guests</div>
									</div>
									<div class="premium-stat stat-due-sub" style="background: none; padding: 0; border: none; box-shadow: none;">
										<span style="font-size: 0.75rem;">BULK COMP.</span>
										<strong style="color: #ef4444; font-size: 1.8rem;">{{ $dueBulkCompanions }}</strong>
										<div style="font-size: 0.75rem; color: var(--hp-text-muted); margin-bottom: 1.5rem;">Guests</div>
									</div>
								</div>
							</div>
							
							<div class="upcoming-checkouts-panel" style="flex: 1; min-width: 300px; padding-left: 2rem; border-left: 1px dashed rgba(0,0,0,0.08);">
								<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
									<h4 style="font-size: 0.85rem; font-weight: 600; color: var(--hp-text-color);">Upcoming Check-outs</h4>
									<a href="#" style="font-size: 0.8rem; color: #f97316; font-weight: 500; display: flex; align-items: center; gap: 0.3rem;">
										View all alerts <span style="font-size:1.1rem;">&rarr;</span>
									</a>
								</div>
								
								<div class="upcoming-checkouts-list" style="display: flex; flex-direction: column; gap: 0.8rem;">
									@foreach (collect($activeReservations ?? [])->take(3) as $res)
										@php
											$primaryGuest = $res->reservationGuests->firstWhere('is_primary_guest', true)?->customer;
											$guestName = $primaryGuest ? trim(($primaryGuest->first_name ?? '') . ' ' . ($primaryGuest->last_name ?? '')) : 'Unknown';
											$amenityNames = $res->reservationAmenities->pluck('amenity.amenities_name')->filter()->unique()->join(', ');
										@endphp
										<div class="upcoming-item" style="display: flex; justify-content: space-between; align-items: center; padding: 0.8rem; background: rgba(34,197,94,0.05); border-radius: 8px;">
											<div style="display: flex; align-items: center; gap: 1rem;">
												<div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(34,197,94,0.1); display: flex; align-items: center; justify-content: center; color: #22c55e;">
													<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 1.2rem; height: 1.2rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
												</div>
												<div>
													<div style="font-weight: 600; font-size: 0.9rem; color: var(--hp-text-color);">{{ $amenityNames ?: 'Entrance' }}</div>
													<div style="font-size: 0.75rem; color: var(--hp-text-muted);">Reserved by {{ $guestName }}</div>
												</div>
											</div>
											<div class="time-left-pill" style="padding: 0.3rem 0.8rem; border-radius: 999px; background: rgba(249,115,22,0.1); color: #c2410c; font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
												<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
												<span class="table-time-left" data-checkout-at="{{ $reservationData[$res->id]['checkout_at'] ?? '' }}"></span>
											</div>
										</div>
									@endforeach
								</div>
							</div>
						</div>
					</div>
					</div>

					{{-- GUEST TABLE --}}
					<div id="guestTableSection" class="tab-content-section checkins-layout-grid">
						@php
							$metrics = [
								'kids' => 0, 'teens' => 0, 'adults' => 0, 'seniors' => 0,
								'main' => 0, 'companions' => 0,
								'filipino' => 0, 'foreigner' => 0,
							];
							
							foreach ($activeCustomers ?? [] as $customer) {
								$age = $customer->age;
								if (is_numeric($age)) {
									if ($age <= 12) $metrics['kids']++;
									elseif ($age <= 17) $metrics['teens']++;
									elseif ($age <= 59) $metrics['adults']++;
									else $metrics['seniors']++;
								}
								
								$hasPrimary = $customer->reservationGuests->where('is_primary_guest', true)->filter(function($g) {
									return $g->reservation && !$g->checked_out_at && strtolower(str_replace(' ', '_', $g->reservation->status ?? '')) !== 'checked_out';
								})->isNotEmpty();
								
								if ($hasPrimary) {
									$metrics['main']++;
								} else {
									$metrics['companions']++;
								}
								
								if ($customer->is_foreigner) {
									$metrics['foreigner']++;
								} else {
									$metrics['filipino']++;
								}
							}
							$totalGuests = max(1, count($activeCustomers ?? []));
						@endphp
						<div class="checkins-main-column">
							<section class="checkins-card">
							<div class="checkins-card-header">
								<div class="table-header-left">
									<h2 class="checkins-title" style="margin-right: 1.5rem;">Guest Data View</h2>
								</div>
								<div class="checkins-actions">
									<button type="button" class="ci-btn-primary" data-open-add-guest-modal="true">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
											<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
										</svg>
										Add Guest
									</button>
									<button type="button" class="ci-btn-icon" id="scanQrBtn" aria-label="Scan QR Code">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
											<path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
											<path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
										</svg>
									</button>
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
								<span>Show Guests</span>
								<select id="guestRoleSelect">
									<option value="all">All Guests</option>
									<option value="primary">Main Guests Only</option>
									<option value="companion">Companions Only</option>
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
									<th>Guest</th>
									<th>Age / Gender</th>
									<th>Nationality</th>
									<th>Status / Time Left</th>
									<th></th>
								</tr>
							</thead>
							<tbody id="guestTableBody">
								@foreach ($customers ?? collect() as $customer)
									@php
										$hasActiveReservation = $customer->reservationGuests->filter(function ($guest) {
											$reservation = $guest->reservation ?? null;
											if (! $reservation) return false;
											if ($guest->checked_out_at) return false;
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
										$typePillClass = $reservationType === 'walk_in' ? 'status-pill--walk-in' : ($reservationType ? 'status-pill--online' : 'status-pill--checked-out');
										
										$isPrimary = $reservationEntry?->is_primary_guest ?? false;
										$isStray = false;
										
										if (!$isPrimary && $reservationEntry?->reservation) {
											$primaryGuest = $reservationEntry->reservation->reservationGuests->firstWhere('is_primary_guest', true);
											if (!$primaryGuest || $primaryGuest->checked_out_at !== null) {
												$isPrimary = true;
												$isStray = true;
											}
										}
										
										$firstName = strtolower(trim($customer->first_name ?? ''));
										$isBulk = str_starts_with($firstName, 'bulk') || str_contains($firstName, 'companion');
										
										$checkoutAtStr = $reservationData[$reservationEntry?->reservation?->id]['checkout_at'] ?? null;
										$checkoutDue = false;
										$checkoutNear = false;
										if ($checkoutAtStr) {
											$coCarbon = \Carbon\Carbon::parse($checkoutAtStr);
											if ($coCarbon->isPast()) {
												$checkoutDue = true;
											} elseif ($coCarbon->diffInMinutes(now()) <= 60) {
												$checkoutNear = true;
											}
										}
										$highlightClass = $checkoutDue ? 'row-checkout-due' : ($checkoutNear ? 'row-checkout-near' : '');
										
										// Hierarchy variables
										$companionCount = 0;
										if ($isPrimary && $reservationEntry?->reservation && !$isStray) {
											$companionCount = $reservationEntry->reservation->reservationGuests->where('is_primary_guest', false)->filter(function($g) { return !$g->checked_out_at; })->count();
										}
										
										// Group Bulk Companions
										$totalBulk = 0;
										$activeBulk = 0;
										if ($isBulk) {
											static $processedBulkResIds = [];
											$resId = $reservationEntry?->reservation?->id;
											if (!$resId || in_array($resId, $processedBulkResIds)) {
												continue;
											}
											$processedBulkResIds[] = $resId;
											
											$allBulkForRes = $reservationEntry->reservation->reservationGuests->filter(function($rg) {
												$fn = strtolower(trim($rg->customer?->first_name ?? ''));
												return str_starts_with($fn, 'bulk') || str_contains($fn, 'companion');
											});
											$totalBulk = $allBulkForRes->count();
											$activeBulk = $allBulkForRes->whereNull('checked_out_at')->count();
											if ($activeBulk === 0) continue;
											
											$customer->first_name = "Bulk Companions (#$resId)";
											$customer->last_name = "";
											$customer->middle_name = "$activeBulk/$totalBulk Checked In";
											$customer->age = "-";
											$customer->gender = "-";
											$customer->is_foreigner = null;
										}
									@endphp
									<tr
										class="guest-row {{ $highlightClass }} {{ $isPrimary ? 'guest-row--primary' : 'guest-row--companion' }} {{ $isBulk ? 'guest-row--bulk-group' : '' }}"
										data-customer-id="{{ $customer->id }}"
										data-reservation-id="{{ $reservationEntry?->reservation?->id ?? '' }}"
										data-is-primary="{{ $isPrimary ? 'true' : 'false' }}"
										data-bulk-group="{{ $isBulk ? 'true' : 'false' }}"
										data-bulk-total="{{ $totalBulk }}"
										data-bulk-active="{{ $activeBulk }}"
										data-age="{{ $customer->age ?? 'N/A' }}"
										data-gender="{{ strtolower((string) ($customer->gender ?? 'N/A')) }}"
											data-check-in="{{ $reservationEntry?->reservation?->check_in ?? '' }}"
											data-check-out="{{ $reservationEntry?->reservation?->check_out ?? '' }}"
											data-checked-out-at="{{ $reservationEntry?->checked_out_at ?? '' }}"
											data-status="{{ $reservationEntry?->reservation?->status ?? 'N/A' }}"
											data-age-value="{{ is_numeric($customer->age) ? (int) $customer->age : 999999 }}"
											data-is-foreign="{{ (bool) ($customer->is_foreigner ?? false) ? 'true' : 'false' }}"
											data-search="{{ strtolower(trim(($customer->first_name ?? '') . ' ' . ($customer->middle_name ?? '') . ' ' . ($customer->last_name ?? '') . ' ' . $customer->id . ' ' . ($customer->gender ?? '') . ' ' . ($customer->is_foreigner === null ? '-' : ($customer->is_foreigner ? 'Foreigner' : 'Filipino')) . ' ' . $reservationTypeLabel)) }}"
										data-is-foreigner="{{ $customer->is_foreigner === null ? '-' : ($customer->is_foreigner ? 'Foreigner' : 'Filipino') }}"
										data-reservation-type="{{ $reservationTypeLabel }}"
										tabindex="0"
										role="button"
										aria-label="View details for {{ trim(($customer->first_name ?? '') . ' ' . ($customer->middle_name ?? '') . ' ' . ($customer->last_name ?? '')) }}"
									>
										<td>
											<div class="cell-person">
												<span class="cell-person__avatar {{ $isPrimary ? 'cell-person__avatar--star' : '' }}" title="{{ $isPrimary ? 'Main Guest' : ($isBulk ? 'Bulk Companion' : 'Single Companion') }}">
													@if($isPrimary)
														<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" /></svg>
													@elseif($isBulk)
														<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" /><path d="M5.082 14.254a8.287 8.287 0 00-1.308 5.135 9.687 9.687 0 01-1.764-.44l-.115-.04a.563.563 0 01-.373-.487l-.01-.121a3.75 3.75 0 016.576-3.036 7.525 7.525 0 00-3.006-1.011zM18.918 14.254a8.287 8.287 0 011.308 5.135 9.687 9.687 0 001.764-.44l.115-.04a.563.563 0 00.373-.487l.01-.121a3.75 3.75 0 00-6.576-3.036 7.525 7.525 0 013.006-1.011z" /></svg>
													@else
														<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" /></svg>
													@endif
												</span>
												@if($isPrimary && $companionCount > 0)
													<button type="button" class="btn-expand-row" data-expand-reservation="{{ $reservationEntry?->reservation?->id }}" aria-label="Toggle Companions">
														<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1rem; height: 1rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
													</button>
												@endif
												<div class="cell-person__body">
													<div class="guest-name">
														@if($isBulk)
															{{ $customer->first_name }}
															<small style='color:var(--hp-text-muted); display:block; font-weight:normal; margin-top:2px;'>{{ $customer->middle_name }}</small>
														@else
															{{ trim(($customer->first_name ?? '') . ' ' . ($customer->middle_name ?? '') . ' ' . ($customer->last_name ?? '')) }}
														@endif
														@if($isStray)
															<span style="font-size: 0.65rem; padding: 0.15rem 0.35rem; background-color: var(--warn, #f59e0b); color: #fff; border-radius: 4px; margin-left: 0.35rem; font-weight: 600; vertical-align: middle;">Stray</span>
														@endif
													</div>
													<div class="guest-meta">ID: {{ $isBulk ? '-' : $customer->id }}</div>
												</div>
											</div>
										</td>
										<td>
											@php
												$displayAge = $customer->age ?? 'N/A';
												if ($isBulk && is_numeric($displayAge)) {
													if ($displayAge <= 12) $displayAge = 'Kids';
													elseif ($displayAge <= 17) $displayAge = 'Teens';
													elseif ($displayAge <= 59) $displayAge = 'Adults';
													else $displayAge = 'Seniors';
												}
											@endphp
											<div class="guest-name">{{ $displayAge }}</div>
											<div class="guest-meta">{{ $customer->gender ?? 'N/A' }}</div>
										</td>
										<td>
											<span class="status-pill {{ $customer->is_foreigner ? 'status-pill--confirmed' : 'status-pill--checked-out' }}">{{ $customer->is_foreigner ? 'Foreigner' : 'Filipino' }}</span>
										</td>
										<td>
											<span class="table-time-left" data-checkout-at="{{ $checkoutAtStr }}" data-status="{{ $reservationEntry?->reservation?->status ?? '' }}"></span>
										</td>
										<td style="text-align: right; color: #9ca3af;">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.2rem; height: 1.2rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z" /></svg>
										</td>
									</tr>
								@endforeach
									<tr id="guestEmptyRow" style="display: {{ ($activeCustomers ?? collect())->isEmpty() ? '' : 'none' }};">
										<td colspan="5" style="border: none;">
											<div class="empty-state-wrapper">
												<div class="empty-state-icon">
													<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
														<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
													</svg>
												</div>
												<h4 class="empty-state-title">No active guests</h4>
												<p class="empty-state-desc">There are no guests currently checked in to the park.</p>
											</div>
										</td>
									</tr>
							</tbody>
						</table>
					</div>
						</section>
					</div>
					
					<div class="checkins-sidebar">
						<!-- Demographics Card -->
						<div class="checkins-card sidebar-data-card">
							<h4 class="sidebar-data-title">Guest Demographics</h4>
							<div class="sidebar-data-list">
								<div class="sidebar-data-item">
									<div class="data-item-top">
										<span class="data-label">Kids (0-12)</span>
										<span class="data-value">{{ $metrics['kids'] }}</span>
									</div>
									<div class="data-progress-bg"><div class="data-progress-fill" style="width: {{ ($metrics['kids'] / $totalGuests) * 100 }}%"></div></div>
								</div>
								<div class="sidebar-data-item">
									<div class="data-item-top">
										<span class="data-label">Teens (13-17)</span>
										<span class="data-value">{{ $metrics['teens'] }}</span>
									</div>
									<div class="data-progress-bg"><div class="data-progress-fill" style="width: {{ ($metrics['teens'] / $totalGuests) * 100 }}%"></div></div>
								</div>
								<div class="sidebar-data-item">
									<div class="data-item-top">
										<span class="data-label">Adults (18-59)</span>
										<span class="data-value">{{ $metrics['adults'] }}</span>
									</div>
									<div class="data-progress-bg"><div class="data-progress-fill" style="width: {{ ($metrics['adults'] / $totalGuests) * 100 }}%"></div></div>
								</div>
								<div class="sidebar-data-item">
									<div class="data-item-top">
										<span class="data-label">Seniors (60+)</span>
										<span class="data-value">{{ $metrics['seniors'] }}</span>
									</div>
									<div class="data-progress-bg"><div class="data-progress-fill" style="width: {{ ($metrics['seniors'] / $totalGuests) * 100 }}%"></div></div>
								</div>
							</div>
						</div>

						<!-- Sidebar Summary Cards (Unified Container) -->
						<div class="sidebar-summary-container" style="background: var(--glass-bg); border-radius: var(--radius-lg); padding: 1.5rem; border: 1px solid var(--glass-border); box-shadow: var(--glass-shadow); display: flex; flex-direction: column; margin-bottom: 1.5rem;">
							<div class="sidebar-summary-grid" style="display: flex; flex-direction: column; gap: 1rem;">
								<!-- Item 1: Active Guests -->
								<div class="top-stat-card card-theme-green" style="background: var(--glass-bg-hover); padding: 1rem; border-radius: var(--radius-md); box-shadow: none; min-height: auto; border: 1px solid var(--glass-border-strong);">
									<div class="top-stat-card__icon" style="background: transparent; color: #16a34a; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; margin-right: 0.5rem;">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
									</div>
									<div class="top-stat-card__body">
										<span style="font-size: 0.8rem; font-weight: 600; color: var(--hp-text-muted);">Active Guests</span>
										<strong style="font-size: 1.5rem; line-height: 1.2;">{{ $activeCustomers->count() }}</strong>
										<div class="stat-trend trend-green" style="font-size: 0.75rem;">Currently inside</div>
									</div>
								</div>
								
								<!-- Item 2: Checked In Today -->
								<div class="top-stat-card card-theme-blue" style="background: var(--glass-bg-hover); padding: 1rem; border-radius: var(--radius-md); box-shadow: none; min-height: auto; border: 1px solid var(--glass-border-strong);">
									<div class="top-stat-card__icon" style="background: transparent; color: #2563eb; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; margin-right: 0.5rem;">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
									</div>
									<div class="top-stat-card__body">
										<span style="font-size: 0.8rem; font-weight: 600; color: var(--hp-text-muted);">Checked In Today</span>
										<strong style="font-size: 1.5rem; line-height: 1.2;">{{ $todaysCheckins }}</strong>
										<div class="stat-trend trend-green" style="font-size: 0.75rem;">Total check-ins</div>
									</div>
								</div>
								
								<!-- Item 3: Expected Check-outs -->
								<div class="top-stat-card card-theme-orange" style="background: var(--glass-bg-hover); padding: 1rem; border-radius: var(--radius-md); box-shadow: none; min-height: auto; border: 1px solid var(--glass-border-strong);">
									<div class="top-stat-card__icon" style="background: transparent; color: #ea580c; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; margin-right: 0.5rem;">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
									</div>
									<div class="top-stat-card__body">
										<span style="font-size: 0.8rem; font-weight: 600; color: var(--hp-text-muted);">Expected Check-outs</span>
										<strong style="font-size: 1.5rem; line-height: 1.2;">{{ $expectedCheckouts }}</strong>
										<div class="stat-trend" style="font-size: 0.75rem;">Guests near/past time</div>
									</div>
								</div>
								
								<!-- Item 4: Walk-ins Today -->
								<div class="top-stat-card card-theme-purple" style="background: var(--glass-bg-hover); padding: 1rem; border-radius: var(--radius-md); box-shadow: none; min-height: auto; border: 1px solid var(--glass-border-strong);">
									<div class="top-stat-card__icon" style="background: transparent; color: #9333ea; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; margin-right: 0.5rem;">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
									</div>
									<div class="top-stat-card__body">
										<span style="font-size: 0.8rem; font-weight: 600; color: var(--hp-text-muted);">Walk-Ins Today</span>
										<strong style="font-size: 1.5rem; line-height: 1.2;">{{ $walkInsToday }}</strong>
										<div class="stat-trend" style="font-size: 0.75rem;">Walk-in guests</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					</div>
				</div>
				</div> <!-- Closing #guestTableSection -->

					{{-- RESERVATION TABLE --}}
					<div id="reservationTableSection" class="tab-content-section" style="display: none;">
						<section class="checkins-card">
							<div class="checkins-card-header">
								<div class="table-header-left">
									<h2 class="checkins-title" style="margin-right: 1.5rem;">Reservation Data View</h2>
								</div>
								<div class="checkins-actions">
									<button type="button" class="ci-btn-primary" data-open-add-guest-modal="true">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
											<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
										</svg>
										Add Guest
									</button>
									<button type="button" class="ci-btn-icon" aria-label="Scan QR Code">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
											<path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
											<path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
										</svg>
									</button>
								</div>
							</div>

					<div class="guest-table-wrap" id="reservationTableWrap" style="margin-top: 1rem;">
						<table class="guest-table">
							<thead>
								<tr>
									<th>Reservation</th>
									<th>Main Guest</th>
									<th>Check-in & Time</th>
									<th>Amenities</th>
									<th>Guests</th>
									<th>Status / Time Left</th>
									<th></th>
								</tr>
							</thead>
							<tbody id="checkInsReservationTableBody">
								@forelse ($activeReservations ?? collect() as $reservation)
									@php
										$primaryGuest = $reservation->reservationGuests->firstWhere('is_primary_guest', true)?->customer;
										$guestInitials = $primaryGuest
											? collect(explode(' ', trim(($primaryGuest->first_name ?? '') . ' ' . ($primaryGuest->last_name ?? ''))))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?'
											: '?';
										// Mixed Time badge: multiple amenities spanning different time periods
										$resvTimePeriods = $reservation->reservationAmenities
											->map(fn ($ra) => str_replace([' Aircon', 'Aircon'], '', (string) $ra->pricing_type))
											->filter()
											->unique();
										$isMixedTime = $reservation->reservationAmenities->count() > 1 && $resvTimePeriods->count() > 1;
										
										$checkoutAtStr = $reservationData[$reservation->id]['checkout_at'] ?? null;
										$checkoutDue = false;
										$checkoutNear = false;
										if ($checkoutAtStr) {
											$coCarbon = \Carbon\Carbon::parse($checkoutAtStr);
											if ($coCarbon->isPast()) {
												$checkoutDue = true;
											} elseif ($coCarbon->diffInMinutes(now()) <= 60) {
												$checkoutNear = true;
											}
										}
										$highlightClass = $checkoutDue ? 'row-checkout-due' : ($checkoutNear ? 'row-checkout-near' : '');
										
										$totalResGuests = $reservation->reservationGuests->count();
										$remainingResGuests = $reservation->reservationGuests->whereNull('checked_out_at')->count();
									@endphp
									<tr
										class="reservation-row {{ $highlightClass }}"
										data-reservation-id="{{ $reservation->id }}"
										tabindex="0"
										role="button"
										aria-label="View reservation {{ $reservation->id }}"
									>
										<td>
											<div style="display: flex; align-items: center;">
												<button type="button" class="btn-expand-row" data-expand-reservation="{{ $reservation->id }}" aria-label="Toggle Reservation Details">
													<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1rem; height: 1rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
												</button>
												<div>
													<div class="guest-name">#{{ $reservation->id }}</div>
													<div class="guest-meta">
														{{ $reservation->reservation_type === 'walk_in' ? 'Walk-in' : 'Online' }}
														@if ($isMixedTime)
															<span class="status-pill status-pill--pending" style="margin-left:4px;">Mixed Time</span>
														@endif
													</div>
												</div>
											</div>
										</td>
										<td>
											@if ($primaryGuest)
												<div class="cell-person">
													<span class="cell-person__avatar cell-person__avatar--star" title="Main Guest">
														<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" /></svg>
													</span>
													<div class="cell-person__body">
														<div class="guest-name">{{ trim(($primaryGuest->first_name ?? '') . ' ' . ($primaryGuest->middle_name ?? '') . ' ' . ($primaryGuest->last_name ?? '')) }}</div>
													</div>
												</div>
											@else
												<div class="guest-name">—</div>
											@endif
										</td>
										<td>
											<div class="guest-name mono-cell">{{ $reservation->check_in ? \Carbon\Carbon::parse($reservation->check_in)->format('M d, Y') : '—' }}</div>
											<div class="guest-meta mono-cell">{{ $reservation->check_in ? \Carbon\Carbon::parse($reservation->check_in)->format('h:i A') : '—' }}</div>
										</td>
										<td>
											@php
												$amenityNames = $reservation->reservationAmenities->pluck('amenity.amenities_name')->filter()->unique()->join(', ');
											@endphp
											<span class="guest-meta">{{ $amenityNames ?: 'None' }}</span>
										</td>
										<td>
											<div class="guest-name">{{ $totalResGuests }} Total</div>
											<div class="guest-meta">{{ $remainingResGuests }} Remaining</div>
										</td>
										<td>
											<span class="table-time-left" data-checkout-at="{{ $reservationData[$reservation->id]['checkout_at'] ?? '' }}" data-status="{{ $reservation->status ?? '' }}"></span>
										</td>
										<td style="text-align: right; color: #9ca3af;">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.2rem; height: 1.2rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z" /></svg>
										</td>
									</tr>
								@empty
									<tr>
										<td colspan="7" style="border: none;">
											<div class="empty-state-wrapper">
												<div class="empty-state-icon">
													<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
														<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
													</svg>
												</div>
												<h4 class="empty-state-title">No active reservations</h4>
												<p class="empty-state-desc">There are no checked-in reservations right now.</p>
											</div>
										</td>
									</tr>
								@endforelse
							</tbody>
						</table>
					</div>
						</section>
					</div>
				</main>
			</div>
		</div>
	</div>
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
						<div class="guest-modal__header">
							<h3 id="addGuestModalTitle" class="guest-modal__title">Add Guest Reservation</h3>
						</div>
						<form id="addGuestForm" class="guest-form" action="{{ route('staff.checkins.guests.store') }}" method="POST">
							@csrf
							
							<div class="guest-form__grid">
								<div class="guest-form__section guest-form__section--compact">
									<div class="guest-form__section-header">
										<h4 class="guest-form__section-title">Reservation Details</h4>
									</div>
									<div class="guest-form__field-group">
										<label class="guest-form__label" for="reservation_type">Reservation type</label>
										<select name="reservation_type" id="reservation_type" class="guest-form__select">
											<option value="walk_in" selected>Walk-in</option>
											<option value="online">Online</option>
										</select>
									</div>
									<input type="hidden" name="check_in" id="check_in">
									<div class="guest-form__field-group">
										<label class="guest-form__label" for="time_period">Time Period</label>
										<select name="time_period" id="time_period" class="guest-form__select" disabled>
											<option value="daytime">Daytime</option>
											<option value="nighttime">Nighttime</option>
											<option value="daytonight">Day to Night</option>
											<option value="nighttoday">Night to Day</option>
										</select>
									</div>
									<div class="guest-form__field-group">
										<label class="guest-form__checkbox-wrapper">
											<input type="checkbox" name="include_pool" id="include_pool">
											<span>Include Pool Access</span>
										</label>
									</div>
									<div class="guest-form__field-group">
										<label class="guest-form__label">Additional Options</label>
										<div class="guest-form__actions-inline">
											<button type="button" class="guest-form__action-btn" id="addCompanionBtn">
												<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem; margin-right: 0.4rem;">
													<path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
												</svg>
												Add Companion
											</button>
											<button type="button" class="guest-form__action-btn" id="chooseAmenitiesBtn">
												<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem; margin-right: 0.4rem;">
													<path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
												</svg>
												Choose Amenities
											</button>
										</div>
									</div>
									<div class="guest-form__field-group">
										<label class="guest-form__label">Entrance Fees</label>
										<div class="guest-form__fees-list">
											<div class="guest-form__fee-item">
												<span>Adult Entrance Fee:</span>
												<strong id="adultEntranceFee">₱0.00</strong>
											</div>
											<div class="guest-form__fee-item">
												<span>Child Entrance Fee:</span>
												<strong id="childEntranceFee">₱0.00</strong>
											</div>
											<div class="guest-form__fee-item">
												<span>Pool Fee:</span>
												<strong id="poolFee">₱0.00</strong>
											</div>
											<div class="guest-form__fee-item guest-form__fee-item--total">
												<span>Total (All Guests):</span>
												<strong id="totalEntranceFee">₱0.00</strong>
											</div>
										</div>
									</div>
								</div>

								<div id="primaryGuestSection" class="guest-form__section guest-form__section--compact">
									<div class="guest-form__section-header">
										<h4 class="guest-form__section-title">Primary Guest</h4>
									</div>
									<div class="guest-form__field-group">
										<label class="guest-form__label" for="primary_first_name">First name</label>
										<input type="text" name="primary_guest[first_name]" id="primary_first_name" placeholder="Enter first name" class="guest-form__input">
									</div>
									<div class="guest-form__field-group">
										<label class="guest-form__label" for="primary_middle_name">Middle name</label>
										<input type="text" name="primary_guest[middle_name]" id="primary_middle_name" placeholder="Enter middle name" class="guest-form__input">
									</div>
									<div class="guest-form__field-group">
										<label class="guest-form__label" for="primary_last_name">Last name</label>
										<input type="text" name="primary_guest[last_name]" id="primary_last_name" placeholder="Enter last name" class="guest-form__input">
									</div>
									<div class="guest-form__row guest-form__row--two">
										<div class="guest-form__field-group">
											<label class="guest-form__label" for="primary_age">Age</label>
											<input type="number" name="primary_guest[age]" id="primary_age" min="0" placeholder="Age" class="guest-form__input">
										</div>
										<div class="guest-form__field-group">
											<label class="guest-form__label" for="primary_gender">Gender</label>
											<select name="primary_guest[gender]" id="primary_gender" class="guest-form__select">
												<option value="">Select gender</option>
												<option value="Male">Male</option>
												<option value="Female">Female</option>
											</select>
										</div>
									</div>
									<div class="guest-form__field-group">
										<label class="guest-form__label" for="primary_is_foreigner">Nationality</label>
										<select name="primary_guest[is_foreigner]" id="primaryGuestIsForeigner" class="guest-form__select">
											<option value="0" selected>Filipino</option>
											<option value="1">Foreigner</option>
										</select>
									</div>
									<div class="guest-form__row guest-form__row--two">
										<div class="guest-form__field-group">
											<label class="guest-form__label" for="primary_phone">Phone</label>
											<input type="text" name="primary_guest[phone]" id="primary_phone" placeholder="Phone number" class="guest-form__input">
										</div>
										<div class="guest-form__field-group">
											<label class="guest-form__label" for="primary_email">Email</label>
											<input type="email" name="primary_guest[email]" id="primary_email" placeholder="Email address" class="guest-form__input">
										</div>
									</div>
								</div>
							</div>

							<div class="guest-form__section">
								<div class="guest-form__section-header">
									<h4 class="guest-form__section-title">Companions</h4>
								</div>
								<div id="companionList" class="guest-companion-list"></div>
								<div id="companionHiddenFields"></div>
							</div>

							<div class="guest-form__section" id="amenitySection">
								<div class="guest-form__section-header">
									<h4 class="guest-form__section-title">Amenities</h4>
								</div>
								<div id="selectedAmenitiesContainer"></div>
								<div class="guest-form__summary">
									<span>Total</span>
									<strong id="reservationTotal">₱0.00</strong>
								</div>
								<input type="hidden" name="total_amount" id="totalAmountInput" value="0">
							</div>

							<div class="guest-form__actions">
								<button type="button" class="guest-form__button--secondary" data-close-add-modal="true">Cancel</button>
								<button type="submit" class="guest-form__button">Check In</button>
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
									</span>										<select class="guest-amenity-option__select" disabled>
											@if ($amenity->daytime_price !== null)
												<option value="Daytime" data-price="{{ $amenity->daytime_price }}">Daytime — ₱{{ number_format($amenity->daytime_price, 2) }}</option>
											@endif
											@if ($amenity->nighttime_price !== null)
												<option value="Nighttime" data-price="{{ $amenity->nighttime_price }}">Nighttime — ₱{{ number_format($amenity->nighttime_price, 2) }}</option>
											@endif
											@if ($amenity->daytime_price !== null && $amenity->nighttime_price !== null)
												<option value="DayToNight" data-price="{{ $amenity->daytime_price + $amenity->nighttime_price }}">Day to Night — ₱{{ number_format($amenity->daytime_price + $amenity->nighttime_price, 2) }}</option>
												<option value="NightToDay" data-price="{{ $amenity->daytime_price + $amenity->nighttime_price }}">Night to Day — ₱{{ number_format($amenity->daytime_price + $amenity->nighttime_price, 2) }}</option>
											@endif
											@if ($amenity->daytime_aircon_price !== null)
												<option value="Daytime Aircon" data-price="{{ $amenity->daytime_aircon_price }}">Daytime Aircon — ₱{{ number_format($amenity->daytime_aircon_price, 2) }}</option>
											@endif
											@if ($amenity->nighttime_aircon_price !== null)
												<option value="Nighttime Aircon" data-price="{{ $amenity->nighttime_aircon_price }}">Nighttime Aircon — ₱{{ number_format($amenity->nighttime_aircon_price, 2) }}</option>
											@endif
											@if ($amenity->daytime_aircon_price !== null && $amenity->nighttime_aircon_price !== null)
												<option value="DayToNight Aircon" data-price="{{ $amenity->daytime_aircon_price + $amenity->nighttime_aircon_price }}">Day to Night Aircon — ₱{{ number_format($amenity->daytime_aircon_price + $amenity->nighttime_aircon_price, 2) }}</option>
												<option value="NightToDay Aircon" data-price="{{ $amenity->daytime_aircon_price + $amenity->nighttime_aircon_price }}">Night to Day Aircon — ₱{{ number_format($amenity->daytime_aircon_price + $amenity->nighttime_aircon_price, 2) }}</option>
											@endif
										</select>
								</label>
							@empty
								<p class="guest-empty">No active amenities are available yet.</p>
							@endforelse
						</div>
					</div>
				</div>

				<div class="guest-modal guest-modal--wide" id="companionModal" aria-hidden="true">
					<div class="guest-modal__backdrop" data-close-companion-modal="true"></div>
					<div class="guest-modal__content guest-modal__content--wide" role="dialog" aria-modal="true" aria-labelledby="companionModalTitle">
						<button type="button" class="guest-modal__close" data-close-companion-modal="true" aria-label="Close companion form">&times;</button>
						<div class="guest-modal__header">
							<h3 id="companionModalTitle" class="guest-modal__title">Add Companion</h3>
						</div>
						<div class="guest-form__tabs">
							<button type="button" class="guest-form__tab guest-form__tab--active" data-companion-tab="single">Single</button>
							<button type="button" class="guest-form__tab" data-companion-tab="bulk">Bulk</button>
						</div>
						
						<!-- Single Companion Form -->
						<form id="companionForm" class="guest-form guest-form--tab-content guest-form--tab-content--active" data-companion-content="single">
							<div class="guest-form__grid">
								<div class="guest-form__field-group">
									<label class="guest-form__label" for="companion_first_name">First name</label>
									<input type="text" name="first_name" id="companion_first_name" placeholder="Enter first name" class="guest-form__input">
								</div>
								<div class="guest-form__field-group">
									<label class="guest-form__label" for="companion_middle_name">Middle name</label>
									<input type="text" name="middle_name" id="companion_middle_name" placeholder="Enter middle name" class="guest-form__input">
								</div>
								<div class="guest-form__field-group">
									<label class="guest-form__label" for="companion_last_name">Last name</label>
									<input type="text" name="last_name" id="companion_last_name" placeholder="Enter last name" class="guest-form__input">
								</div>
								<div class="guest-form__field-group">
									<label class="guest-form__label" for="companion_age">Age</label>
									<input type="number" name="age" id="companion_age" min="0" placeholder="Age" class="guest-form__input">
								</div>
								<div class="guest-form__field-group">
									<label class="guest-form__label" for="companion_age_type">Age Type</label>
									<select name="age_type" id="companion_age_type" class="guest-form__select">
										<option value="adult">Adult</option>
										<option value="child">Child</option>
									</select>
								</div>
								<div class="guest-form__field-group">
									<label class="guest-form__label" for="companion_gender">Gender</label>
									<select name="gender" id="companion_gender" class="guest-form__select">
										<option value="">Select gender</option>
										<option value="Male">Male</option>
										<option value="Female">Female</option>
									</select>
								</div>
								<div class="guest-form__field-group">
									<label class="guest-form__label" for="companion_is_foreigner">Nationality</label>
									<select name="is_foreigner" id="companionIsForeigner" class="guest-form__select">
										<option value="0" selected>Filipino</option>
										<option value="1">Foreigner</option>
									</select>
								</div>
								<div class="guest-form__field-group">
									<label class="guest-form__label" for="companion_phone">Phone</label>
									<input type="text" name="phone" id="companion_phone" placeholder="Phone number" class="guest-form__input">
								</div>
								<div class="guest-form__field-group">
									<label class="guest-form__label" for="companion_email">Email</label>
									<input type="email" name="email" id="companion_email" placeholder="Email address" class="guest-form__input">
								</div>
							</div>
							<div class="guest-form__actions">
								<button type="button" class="guest-form__button--secondary" data-close-companion-modal="true">Cancel</button>
								<button type="submit" class="guest-form__button">Add Companion</button>
							</div>
						</form>

						<!-- Bulk Companion Form -->
						<form id="bulkCompanionForm" class="guest-form guest-form--tab-content" data-companion-content="bulk">
							<div class="guest-form__grid">
								<div class="guest-form__field-group">
									<label class="guest-form__label" for="bulk_companion_gender">Gender</label>
									<select name="gender" id="bulk_companion_gender" class="guest-form__select">
										<option value="">Select gender</option>
										<option value="Male">Male</option>
										<option value="Female">Female</option>
									</select>
								</div>
								<div class="guest-form__field-group">
									<label class="guest-form__label" for="bulk_companion_age_group">Age Group</label>
									<select name="age_group" id="bulk_companion_age_group" class="guest-form__select">
										<option value="0-12">Kids (0-12)</option>
										<option value="13-17">Teens (13-17)</option>
										<option value="18-59">Adults (18-59)</option>
										<option value="60+">Seniors (60+)</option>
									</select>
								</div>
								<div class="guest-form__field-group">
									<label class="guest-form__label" for="bulk_companion_is_foreigner">Nationality</label>
									<select name="is_foreigner" id="bulk_companion_is_foreigner" class="guest-form__select">
										<option value="0" selected>Filipino</option>
										<option value="1">Foreigner</option>
									</select>
								</div>
								<div class="guest-form__field-group">
									<label class="guest-form__label" for="bulk_companion_quantity">Quantity</label>
									<input type="number" name="quantity" id="bulk_companion_quantity" min="1" max="500" value="1" class="guest-form__input">
								</div>
							</div>
							<div class="guest-form__actions">
								<button type="button" class="guest-form__button--secondary" data-close-companion-modal="true">Cancel</button>
								<button type="submit" class="guest-form__button">Add Bulk Companions</button>
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
							</div>								<div class="guest-form__section">
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

				{{-- Check In Confirmation Modal --}}
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

				{{-- Companion Groups Summary Modal --}}
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

				{{-- Bulk Companion Modal --}}
				<div class="guest-modal guest-modal--compact" id="bulkCompanionModal" aria-hidden="true">
					<div class="guest-modal__backdrop" data-close-bulk-companion-modal="true"></div>
					<div class="guest-modal__content guest-modal__content--compact" role="dialog" aria-modal="true" aria-labelledby="bulkCompanionModalTitle">
						<button type="button" class="guest-modal__close" data-close-bulk-companion-modal="true" aria-label="Close bulk companion form">&times;</button>
						
						<div class="guest-modal__header" style="text-align: center; border-bottom: none; padding-bottom: 0;">
							<div class="guest-modal__icon-wrap" style="margin: 0 auto 1rem; width: 48px; height: 48px; border-radius: 50%; background: var(--hp-green-mid); color: white; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(46, 125, 85, 0.2);">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 24px; height: 24px;">
									<path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z" />
								</svg>
							</div>
							<h3 id="bulkCompanionModalTitle" class="guest-modal__title">Add Companions in Bulk</h3>
							<p class="guest-modal__subtitle" style="color: var(--hp-text-muted); font-size: 0.9rem; margin-top: 0.25rem;">Quickly generate multiple companions of the same demographic profile.</p>
						</div>

						<form id="bulkCompanionForm" class="guest-form" action="#" style="margin-top: 1.5rem;">
							<div class="bulk-panel" style="background: var(--glass-bg-hover); padding: 1.25rem; border-radius: var(--radius-lg); border: 1px solid var(--glass-border); margin-bottom: 1.5rem;">
								<div class="guest-form__row guest-form__row--two">
									<!-- Gender -->
									<div class="bulk-field" style="display: flex; flex-direction: column; gap: 0.5rem;">
										<span class="bulk-field__label" style="font-size: 0.8rem; font-weight: 600; color: var(--hp-text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Gender</span>
										<div class="bulk-segment" style="display: flex; background: var(--glass-bg); border-radius: var(--radius-md); border: 1px solid var(--glass-border); overflow: hidden;">
											<label class="bulk-segment__btn" style="flex: 1; text-align: center; cursor: pointer; position: relative;">
												<input type="radio" name="gender" value="Male" checked style="position: absolute; opacity: 0; width: 0; height: 0;">
												<span style="display: block; padding: 0.6rem; font-size: 0.85rem; font-weight: 500; transition: all 0.2s; border-right: 1px solid var(--glass-border);">Male</span>
											</label>
											<label class="bulk-segment__btn" style="flex: 1; text-align: center; cursor: pointer; position: relative;">
												<input type="radio" name="gender" value="Female" style="position: absolute; opacity: 0; width: 0; height: 0;">
												<span style="display: block; padding: 0.6rem; font-size: 0.85rem; font-weight: 500; transition: all 0.2s;">Female</span>
											</label>
										</div>
									</div>
									
									<!-- Nationality -->
									<div class="bulk-field" style="display: flex; flex-direction: column; gap: 0.5rem;">
										<span class="bulk-field__label" style="font-size: 0.8rem; font-weight: 600; color: var(--hp-text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Nationality</span>
										<div class="bulk-segment" style="display: flex; background: var(--glass-bg); border-radius: var(--radius-md); border: 1px solid var(--glass-border); overflow: hidden;">
											<label class="bulk-segment__btn" style="flex: 1; text-align: center; cursor: pointer; position: relative;">
												<input type="radio" name="is_foreigner" value="0" checked style="position: absolute; opacity: 0; width: 0; height: 0;">
												<span style="display: block; padding: 0.6rem; font-size: 0.85rem; font-weight: 500; transition: all 0.2s; border-right: 1px solid var(--glass-border);">Filipino</span>
											</label>
											<label class="bulk-segment__btn" style="flex: 1; text-align: center; cursor: pointer; position: relative;">
												<input type="radio" name="is_foreigner" value="1" style="position: absolute; opacity: 0; width: 0; height: 0;">
												<span style="display: block; padding: 0.6rem; font-size: 0.85rem; font-weight: 500; transition: all 0.2s;">Foreigner</span>
											</label>
										</div>
									</div>
								</div>

								<!-- Age Group -->
								<div class="guest-form__row" style="margin-top: 1.25rem;">
									<label class="guest-form__field">
										<span style="font-size: 0.8rem; font-weight: 600; color: var(--hp-text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Age Group</span>
										<select name="age_group" id="bulkCompanionAgeGroup" style="width: 100%; padding: 0.75rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--glass-border); background: var(--glass-bg); color: var(--hp-text); font-family: var(--hp-font-ui); font-size: 0.95rem; cursor: pointer;">
											<option value="0-12">Kids (0-12 years)</option>
											<option value="13-17">Teens (13-17 years)</option>
											<option value="18-59">Adults (18-59 years)</option>
											<option value="60+">Seniors (60+ years)</option>
										</select>
									</label>
								</div>
							</div>

							<!-- Quantity Stepper -->
							<div class="bulk-quantity-panel" style="text-align: center;">
								<span class="bulk-field__label" style="display: block; margin-bottom: 0.75rem; font-size: 0.85rem; font-weight: 600; color: var(--hp-text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Number of Companions</span>
								<div class="bulk-stepper" style="display: inline-flex; align-items: center; background: var(--glass-bg); border: 1px solid var(--glass-border-strong); border-radius: var(--radius-xl); overflow: hidden; box-shadow: var(--glass-shadow);">
									<button type="button" class="bulk-stepper__btn" id="bulkBtnMinus" aria-label="Decrease quantity" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; background: transparent; border: none; cursor: pointer; color: var(--hp-text); transition: background 0.2s;">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 20px; height: 20px;"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h12.5a.75.75 0 010 1.5H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
									</button>
									<input type="number" name="quantity" id="bulkCompanionQuantity" class="bulk-stepper__input" min="1" max="50" value="1" required style="width: 60px; height: 48px; text-align: center; border: none; border-left: 1px solid var(--glass-border); border-right: 1px solid var(--glass-border); background: transparent; font-size: 1.25rem; font-weight: 700; color: var(--hp-text); -moz-appearance: textfield;">
									<button type="button" class="bulk-stepper__btn" id="bulkBtnPlus" aria-label="Increase quantity" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; background: transparent; border: none; cursor: pointer; color: var(--hp-text); transition: background 0.2s;">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 20px; height: 20px;"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v5.5h5.5a.75.75 0 010 1.5h-5.5v5.5a.75.75 0 01-1.5 0v-5.5h-5.5a.75.75 0 010-1.5h5.5v-5.5A.75.75 0 0110 3z" clip-rule="evenodd" /></svg>
									</button>
								</div>
							</div>

							<div class="guest-form__actions" style="margin-top: 2rem; display: flex; gap: 1rem;">
								<button type="button" class="guest-form__secondary" data-close-bulk-companion-modal="true" style="flex: 1; margin: 0;">Cancel</button>
								<button type="submit" class="guest-form__button" id="generateCompanionsBtn" style="flex: 2; margin: 0; display: flex; align-items: center; justify-content: center; gap: 0.5rem; background: var(--hp-green-mid); color: white;">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 18px; height: 18px;"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v5.5h5.5a.75.75 0 010 1.5h-5.5v5.5a.75.75 0 01-1.5 0v-5.5h-5.5a.75.75 0 010-1.5h5.5v-5.5A.75.75 0 0110 3z" clip-rule="evenodd" /></svg>
									Generate Companions
								</button>
							</div>
						</form>
					</div>
				</div>

				{{-- Bulk Group Manage Modal --}}
				<div class="guest-modal guest-modal--compact" id="bulkGroupManageModal" aria-hidden="true">
					<div class="guest-modal__backdrop" data-close-bulk-manage-modal="true"></div>
					<div class="guest-modal__content guest-modal__content--compact" role="dialog" aria-modal="true" aria-labelledby="bulkGroupManageTitle" style="padding: 0;">
						<button type="button" class="guest-modal__close" data-close-bulk-manage-modal="true" aria-label="Close form" style="position: absolute; right: 1rem; top: 1rem; z-index: 10;">&times;</button>
						
						<div class="guest-modal__header" style="text-align: center; border-bottom: none; padding: 2rem 2rem 1rem;">
							<div class="guest-modal__icon-wrap" style="margin: 0 auto 1rem; width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, var(--hp-green-light), var(--hp-green-mid)); color: white; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(46, 125, 85, 0.3);">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 28px; height: 28px;">
									<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
								</svg>
							</div>
							<h3 id="bulkGroupManageTitle" class="guest-modal__title" style="font-size: 1.25rem; font-weight: 700;">Manage Bulk Companions</h3>
							<div style="font-size: 0.85rem; color: var(--hp-text-muted); margin-top: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
								<span>Reservation</span>
								<span id="bulkManageResId" style="background: var(--glass-bg-hover); padding: 0.2rem 0.6rem; border-radius: 12px; border: 1px solid var(--glass-border); color: var(--hp-text-color); font-weight: 600;">#</span>
							</div>
						</div>

						<div class="guest-modal__body" style="padding: 0 2rem 2rem; text-align: center;">
							<div id="bulkManageDemographics" style="display: inline-flex; align-items: center; gap: 0.5rem; background: var(--hp-cream); padding: 0.75rem 1.25rem; border-radius: 20px; font-weight: 500; color: var(--hp-green-mid); border: 1px dashed rgba(46, 125, 85, 0.3); margin-bottom: 2rem;">
								<!-- Rendered dynamically via JS -->
							</div>
							
							<div style="background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: center; gap: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
								
								<button type="button" id="bulkManageBtnDecrease" aria-label="Check out one companion" style="width: 52px; height: 52px; border-radius: 50%; border: none; background: rgba(239, 68, 68, 0.1); color: var(--hp-red); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.2) inset;">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="width: 24px; height: 24px;"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" /></svg>
								</button>
								
								<div style="display: flex; flex-direction: column; align-items: center;">
									<span id="bulkManageActiveCount" style="font-size: 3.5rem; font-weight: 800; line-height: 1; color: var(--hp-text-color); font-family: 'Montserrat', sans-serif;">0</span>
									<div style="display: flex; align-items: center; gap: 0.25rem; font-size: 0.8rem; color: var(--hp-text-muted); margin-top: 0.5rem; background: var(--glass-bg-strong); padding: 0.25rem 0.75rem; border-radius: 12px;">
										<span>out of</span>
										<span id="bulkManageTotalCount" style="font-weight: 700; color: var(--hp-text-color);">0</span>
										<span>inside</span>
									</div>
								</div>
							</div>
							
							<div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; background: rgba(239, 68, 68, 0.05); color: var(--hp-red); padding: 0.75rem; border-radius: var(--radius-md); font-size: 0.8rem; text-align: left; line-height: 1.4;">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px; flex-shrink: 0;"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
								<span>Click the <strong>minus button</strong> to check out one companion from this group.</span>
							</div>
						</div>
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
