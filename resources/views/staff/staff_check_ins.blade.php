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
		'resources/css/staff_css/staff_shared.css',
		'resources/css/homepage.css',
		'resources/components/css_js/header.css',
		'resources/components/css_js/staff_sidemenu.css',
		'resources/css/chatbot.css',
		'resources/components/css_js/header.js',
		'resources/components/css_js/sidemenu.js',
		'resources/js/staff_js/staff_check_ins.js',
		'resources/js/staff_js/staff_reservations.js',
		'resources/js/staff_chatbot.js',
	])
</head>
<body class="antialiased staff-portal">
	<div class="dash-layout">
		<x-staff_sidemenu active="checkins" userName="{{ session('auth_user.name') ?? 'Staff User' }}" userRole="Staff" />

		<div class="dash-main">

			<main class="dash-content p-6">
				<x-header
					title="Check Ins"
					subtitle="Active check-ins and walk-ins"
				/>
				@if (session('success'))
					<div class="mb-4 rounded-xl border border-glass-border bg-[rgba(26,58,31,0.15)] px-4 py-3 text-hp-green" id="pageFlashSuccess" data-page-flash="success">{{ session('success') }}</div>
				@endif

					@php
						// 1. Calculate Active Customers
						$activeCustomers = collect($customers ?? collect())->filter(function ($customer) {
							return $customer->reservationGuests->filter(function ($guest) {
								$reservation = $guest->reservation ?? null;
								if (! $reservation) return false;
								if ($guest->checked_out_at) return false;
								if (! $reservation->check_in) return false;
								return $reservation->status === 'Checked In';
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
								return $guest->reservation && !$guest->checked_out_at && $guest->reservation->check_in && $guest->reservation->status === 'Checked In';
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

						// 6. Real Comparisons vs Yesterday & vs Last Month
						$yesterdayDate = now()->subDay()->toDateString();
						$yesterdayActiveGuests = \App\Models\ReservationGuest::whereHas('reservation', function($q) use ($yesterdayDate) {
							$q->whereDate('check_in', $yesterdayDate)
							  ->orWhere(function($q2) use ($yesterdayDate) {
								  $q2->whereNull('check_in')->whereDate('reservation_date', $yesterdayDate)->where('status', '!=', 'Cancelled');
							  });
						})->count();

						$activeGuestCount = $activeCustomers->count();
						if ($yesterdayActiveGuests > 0) {
							$guestTrendPct = round((($activeGuestCount - $yesterdayActiveGuests) / $yesterdayActiveGuests) * 100);
						} elseif ($activeGuestCount > 0) {
							$guestTrendPct = 100;
						} else {
							$guestTrendPct = 0;
						}

						$yesterdayActiveRes = \App\Models\Reservation::whereDate('check_in', $yesterdayDate)
							->orWhere(function($q) use ($yesterdayDate) {
								$q->whereNull('check_in')->whereDate('reservation_date', $yesterdayDate)->where('status', '!=', 'Cancelled');
							})->count();

						if ($yesterdayActiveRes > 0) {
							$resTrendPct = round((($totalActiveRes - $yesterdayActiveRes) / $yesterdayActiveRes) * 100);
						} elseif ($totalActiveRes > 0) {
							$resTrendPct = 100;
						} else {
							$resTrendPct = 0;
						}

						$todayAmount = (float) $totalAmount;
						$yesterdayAmount = (float) \App\Models\Reservation::whereDate('created_at', $yesterdayDate)
							->orWhereDate('reservation_date', $yesterdayDate)
							->sum('amount_paid');

						if ($yesterdayAmount > 0) {
							$amountTrendPct = round((($todayAmount - $yesterdayAmount) / $yesterdayAmount) * 100);
						} elseif ($todayAmount > 0) {
							$amountTrendPct = 100;
						} else {
							$amountTrendPct = 0;
						}

						$thisMonthRev = (float) \App\Models\Reservation::whereYear('created_at', now()->year)
							->whereMonth('created_at', now()->month)
							->sum('amount_paid');

						$lastMonthDate = now()->subMonth();
						$lastMonthRev = (float) \App\Models\Reservation::whereYear('created_at', $lastMonthDate->year)
							->whereMonth('created_at', $lastMonthDate->month)
							->sum('amount_paid');

						if ($lastMonthRev > 0) {
							$monthTrendPct = round((($thisMonthRev - $lastMonthRev) / $lastMonthRev) * 100);
						} elseif ($thisMonthRev > 0) {
							$monthTrendPct = 100;
						} else {
							$monthTrendPct = 0;
						}
					@endphp

					<!-- MASTER TABS -->
					<div class="checkins-tabs-container mb-5">
						<div class="checkins-tabs" role="tablist">
							<button type="button" class="checkins-tab is-active" data-tab-target="guest" role="tab" aria-selected="true">
								<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
								<span>Guests</span>
							</button>
							<button type="button" class="checkins-tab" data-tab-target="reservation" role="tab" aria-selected="false">
								<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
								<span>Reservations</span>
							</button>
							<button type="button" class="checkins-tab" data-tab-target="dashboard" role="tab" aria-selected="false">
								<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
								<span>Analytics Dashboard</span>
							</button>
						</div>
					</div>



					<div id="dashboardSection" class="tab-content-section" style="display: none;">
						<div class="premium-dashboard grid grid-cols-1 gap-6 xl:grid-cols-2">
						<!-- Widget 1: Overview -->
						<div class="premium-widget rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
							<div class="premium-widget__header mb-4 flex items-center gap-3">
								<div class="widget-icon widget-icon--green flex h-9 w-9 items-center justify-center rounded-lg bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1a3324] dark:text-[#6ab88c]">
									<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
								</div>
								<h4 class="m-0 text-sm font-bold uppercase tracking-wide text-hp-text">OVERVIEW</h4>
							</div>
							<div class="premium-widget__grid cols-3 mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
								<div class="premium-stat">
									<span class="text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">ACTIVE GUESTS</span>
									<strong class="block font-display text-2xl font-bold text-hp-green-dark dark:text-[#c8e6c8]">{{ $activeCustomers->count() }}</strong>
									@if ($guestTrendPct > 0)
										<div class="stat-trend trend-up mt-1 text-xs font-semibold text-[#16a34a]">▲ {{ $guestTrendPct }}% vs yesterday</div>
									@elseif ($guestTrendPct < 0)
										<div class="stat-trend trend-down mt-1 text-xs font-semibold text-[#dc2626]">▼ {{ abs($guestTrendPct) }}% vs yesterday</div>
									@else
										<div class="stat-trend mt-1 text-xs font-semibold text-hp-text-muted">0% vs yesterday</div>
									@endif
								</div>
								<div class="premium-stat">
									<span class="text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">RESERVATIONS</span>
									<strong class="block font-display text-2xl font-bold text-hp-green-dark dark:text-[#c8e6c8]">{{ $totalActiveRes }}</strong>
									@if ($resTrendPct > 0)
										<div class="stat-trend trend-up mt-1 text-xs font-semibold text-[#16a34a]">▲ {{ $resTrendPct }}% vs yesterday</div>
									@elseif ($resTrendPct < 0)
										<div class="stat-trend trend-down mt-1 text-xs font-semibold text-[#dc2626]">▼ {{ abs($resTrendPct) }}% vs yesterday</div>
									@else
										<div class="stat-trend mt-1 text-xs font-semibold text-hp-text-muted">0% vs yesterday</div>
									@endif
								</div>
								<div class="premium-stat">
									<span class="text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">TOTAL AMOUNT</span>
									<strong class="text-gradient-green block bg-gradient-to-r from-[#16a34a] to-[#0e5c37] bg-clip-text font-display text-2xl font-bold text-transparent">₱{{ number_format($totalAmount, 0) }}</strong>
									@if ($amountTrendPct > 0)
										<div class="stat-trend trend-up mt-1 text-xs font-semibold text-[#16a34a]">▲ {{ $amountTrendPct }}% vs yesterday</div>
									@elseif ($amountTrendPct < 0)
										<div class="stat-trend trend-down mt-1 text-xs font-semibold text-[#dc2626]">▼ {{ abs($amountTrendPct) }}% vs yesterday</div>
									@else
										<div class="stat-trend mt-1 text-xs font-semibold text-hp-text-muted">0% vs yesterday</div>
									@endif
								</div>
							</div>
							<!-- Role breakdown -->
							<div class="premium-widget__grid cols-3 grid grid-cols-1 gap-4 border-t border-dashed border-black/10 pt-4 sm:grid-cols-3 dark:border-white/10">
								<div class="premium-stat">
									<span class="text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">MAIN GUESTS</span>
									<strong class="block text-xl text-hp-text-muted">{{ $activeMainGuests }}</strong>
								</div>
								<div class="premium-stat">
									<span class="text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">SINGLE COMP.</span>
									<strong class="block text-xl text-hp-text-muted">{{ $activeSingleCompanions }}</strong>
								</div>
								<div class="premium-stat">
									<span class="text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">BULK COMP.</span>
									<strong class="block text-xl text-hp-text-muted">{{ $activeBulkCompanions }}</strong>
								</div>
							</div>

							<div class="revenue-this-month mt-6 flex items-end justify-between rounded-lg bg-[rgba(34,197,94,0.05)] px-4 py-3">
								<div>
									<div class="mb-0.5 text-xs font-medium text-[#166534]">Revenue this month</div>
									<strong class="text-xl text-[#15803d]">₱{{ number_format($thisMonthRev, 2) }}</strong>
								</div>
								@if ($monthTrendPct > 0)
									<div class="stat-trend trend-up text-xs font-semibold text-[#16a34a]">▲ {{ $monthTrendPct }}% vs last month</div>
								@elseif ($monthTrendPct < 0)
									<div class="stat-trend trend-down text-xs font-semibold text-[#dc2626]">▼ {{ abs($monthTrendPct) }}% vs last month</div>
								@else
									<div class="stat-trend text-xs font-semibold text-hp-text-muted">0% vs last month</div>
								@endif
							</div>
						</div>

						<!-- Widget 2: Demographics -->
						<div class="premium-widget rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
							<div class="premium-widget__header mb-4 flex items-center gap-3">
								<div class="widget-icon widget-icon--blue flex h-9 w-9 items-center justify-center rounded-lg bg-[#e5f0f6] text-[#2a6a8f] dark:bg-[#182c38] dark:text-[#6ea9c9]">
									<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
								</div>
								<h4 class="m-0 text-sm font-bold uppercase tracking-wide text-hp-text">DEMOGRAPHICS</h4>
							</div>
							@php
								$totalGuests = $activeCustomers->count();
								$pctMale = $totalGuests > 0 ? round(($guestSummaryMale / $totalGuests) * 100) : 0;
								$pctFem = $totalGuests > 0 ? round(($guestSummaryFemale / $totalGuests) * 100) : 0;
								$pctFor = $totalGuests > 0 ? round(($guestSummaryForeign / $totalGuests) * 100) : 0;
								$pctFil = $totalGuests > 0 ? round(($guestSummaryFilipino / $totalGuests) * 100) : 0;

								if ($totalGuests === 0) {
									$conicGradient = "rgba(100, 116, 139, 0.15) 0deg 360deg";
								} else {
									$degMale = $pctMale * 3.6;
									$degFem = $degMale + ($pctFem * 3.6);
									$conicGradient = "#22c55e 0deg {$degMale}deg, #ec4899 {$degMale}deg {$degFem}deg, #e5e7eb {$degFem}deg 360deg";
								}
							@endphp
							<div class="premium-widget__grid cols-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
								<div class="premium-stat" data-tooltip="Filipino: {{ $demoMaleFil }}&#xa;Foreigner: {{ $demoMaleFor }}">
									<span class="text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">MALE</span>
									<strong class="block font-display text-2xl font-bold text-hp-green-dark dark:text-[#c8e6c8]">{{ $guestSummaryMale }}</strong>
									<div class="stat-trend trend-blue mt-1 text-xs font-semibold text-[#2a6a8f]">{{ $pctMale }}%</div>
								</div>
								<div class="premium-stat" data-tooltip="Filipino: {{ $demoFemFil }}&#xa;Foreigner: {{ $demoFemFor }}">
									<span class="text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">FEMALE</span>
									<strong class="block font-display text-2xl font-bold text-hp-green-dark dark:text-[#c8e6c8]">{{ $guestSummaryFemale }}</strong>
									<div class="stat-trend trend-blue mt-1 text-xs font-semibold text-[#2a6a8f]">{{ $pctFem }}%</div>
								</div>
								<div class="premium-stat" data-tooltip="Filipino: {{ $demoForFil ?? 0 }}&#xa;Foreigner: {{ $demoForFor ?? 0 }}">
									<span class="text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">FOREIGNER</span>
									<strong class="block font-display text-2xl font-bold text-hp-green-dark dark:text-[#c8e6c8]">{{ $guestSummaryForeign }}</strong>
									<div class="stat-trend trend-blue mt-1 text-xs font-semibold text-[#2a6a8f]">{{ $pctFor }}%</div>
								</div>
								<div class="premium-stat" data-tooltip="Filipino: {{ $demoFilFil ?? 0 }}&#xa;Foreigner: {{ $demoFilFor ?? 0 }}">
									<span class="text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">FILIPINO</span>
									<strong class="block font-display text-2xl font-bold text-hp-green-dark dark:text-[#c8e6c8]">{{ $guestSummaryFilipino }}</strong>
									<div class="stat-trend trend-blue mt-1 text-xs font-semibold text-[#2a6a8f]">{{ $pctFil }}%</div>
								</div>
							</div>

							<!-- Donut Chart Area -->
							<div class="demographics-chart-area mt-6 flex items-center justify-center gap-8 px-2 py-4">
								<div class="donut-chart-container relative h-[120px] w-[120px]">
									<div class="donut-chart h-full w-full rounded-full transition-all duration-500" style="background: conic-gradient({{ $conicGradient }});"></div>
									<div class="donut-chart-inner absolute left-5 right-5 top-5 bottom-5 flex flex-col items-center justify-center rounded-full bg-glass shadow-[inset_0_2px_4px_rgba(0,0,0,0.05)]">
										<strong class="text-xl leading-none text-hp-text">{{ $totalGuests }}</strong>
										<span class="text-[0.65rem] text-hp-text-muted">Total</span>
									</div>
								</div>
								<div class="donut-legend flex min-w-[120px] flex-col gap-2.5 text-[0.8rem] text-hp-text">
									<div class="legend-item flex justify-between"><span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-[#22c55e]"></span>Male</span> <span>{{ $guestSummaryMale }} ({{ $pctMale }}%)</span></div>
									<div class="legend-item flex justify-between"><span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-[#ec4899]"></span>Female</span> <span>{{ $guestSummaryFemale }} ({{ $pctFem }}%)</span></div>
									<div class="legend-item flex justify-between"><span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-[#f59e0b]"></span>Foreigner</span> <span>{{ $guestSummaryForeign }} ({{ $pctFor }}%)</span></div>
									<div class="legend-item flex justify-between"><span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-[#8b5cf6]"></span>Filipino</span> <span>{{ $guestSummaryFilipino }} ({{ $pctFil }}%)</span></div>
								</div>
							</div>
						</div>

						<!-- Widget 4: Age Groups -->
						<div class="premium-widget rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
							<div class="premium-widget__header mb-4 flex items-center gap-3">
								<div class="widget-icon widget-icon--blue flex h-9 w-9 items-center justify-center rounded-lg bg-[#e5f0f6] text-[#2a6a8f] dark:bg-[#182c38] dark:text-[#6ea9c9]">
									<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
								</div>
								<h4 class="m-0 text-sm font-bold uppercase tracking-wide text-hp-text">AGE GROUPS</h4>
							</div>
							@php
								$pctKids = $totalGuests > 0 ? round(($ageKids / $totalGuests) * 100) : 0;
								$pctTeen = $totalGuests > 0 ? round(($ageTeen / $totalGuests) * 100) : 0;
								$pctAdult = $totalGuests > 0 ? round(($ageAdult / $totalGuests) * 100) : 0;
								$pctSenior = $totalGuests > 0 ? round(($ageSenior / $totalGuests) * 100) : 0;
							@endphp
							<div class="premium-widget__grid cols-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
								<div class="premium-stat" data-tooltip="Filipino: {{ $ageFilKids }}&#xa;Foreigner: {{ $ageForKids }}">
									<span class="text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">KIDS</span>
									<strong class="block font-display text-2xl font-bold text-hp-green-dark dark:text-[#c8e6c8]">{{ $ageKids }}</strong>
									<div class="stat-trend mt-1 text-xs font-semibold text-hp-text-muted">{{ $pctKids }}%</div>
								</div>
								<div class="premium-stat" data-tooltip="Filipino: {{ $ageFilTeen }}&#xa;Foreigner: {{ $ageForTeen }}">
									<span class="text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">TEENS</span>
									<strong class="block font-display text-2xl font-bold text-hp-green-dark dark:text-[#c8e6c8]">{{ $ageTeen }}</strong>
									<div class="stat-trend mt-1 text-xs font-semibold text-hp-text-muted">{{ $pctTeen }}%</div>
								</div>
								<div class="premium-stat" data-tooltip="Filipino: {{ $ageFilAdult }}&#xa;Foreigner: {{ $ageForAdult }}">
									<span class="text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">ADULTS</span>
									<strong class="block font-display text-2xl font-bold text-hp-green-dark dark:text-[#c8e6c8]">{{ $ageAdult }}</strong>
									<div class="stat-trend mt-1 text-xs font-semibold text-hp-text-muted">{{ $pctAdult }}%</div>
								</div>
								<div class="premium-stat" data-tooltip="Filipino: {{ $ageFilSenior }}&#xa;Foreigner: {{ $ageForSenior }}">
									<span class="text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">SENIORS</span>
									<strong class="block font-display text-2xl font-bold text-hp-green-dark dark:text-[#c8e6c8]">{{ $ageSenior }}</strong>
									<div class="stat-trend mt-1 text-xs font-semibold text-hp-text-muted">{{ $pctSenior }}%</div>
								</div>
							</div>

							<!-- Progress Bars Area -->
							<div class="age-progress-area mt-6 flex flex-col gap-4">
								<div class="progress-row">
									<div class="mb-1 flex justify-between text-xs text-hp-text-muted">
										<span>Kids (0 - 12 yrs)</span>
										<span>{{ $pctKids }}%</span>
									</div>
									<div class="h-2 w-full overflow-hidden rounded bg-black/5 dark:bg-white/10">
										<div class="h-full rounded bg-[#22c55e]" style="width: {{ $pctKids }}%;"></div>
									</div>
								</div>
								<div class="progress-row">
									<div class="mb-1 flex justify-between text-xs text-hp-text-muted">
										<span>Teens (13 - 17 yrs)</span>
										<span>{{ $pctTeen }}%</span>
									</div>
									<div class="h-2 w-full overflow-hidden rounded bg-black/5 dark:bg-white/10">
										<div class="h-full rounded bg-[#3b82f6]" style="width: {{ $pctTeen }}%;"></div>
									</div>
								</div>
								<div class="progress-row">
									<div class="mb-1 flex justify-between text-xs text-hp-text-muted">
										<span>Adults (18 - 59 yrs)</span>
										<span>{{ $pctAdult }}%</span>
									</div>
									<div class="h-2 w-full overflow-hidden rounded bg-black/5 dark:bg-white/10">
										<div class="h-full rounded bg-[#a855f7]" style="width: {{ $pctAdult }}%;"></div>
									</div>
								</div>
								<div class="progress-row">
									<div class="mb-1 flex justify-between text-xs text-hp-text-muted">
										<span>Seniors (60+ yrs)</span>
										<span>{{ $pctSenior }}%</span>
									</div>
									<div class="h-2 w-full overflow-hidden rounded bg-black/5 dark:bg-white/10">
										<div class="h-full rounded bg-[#f59e0b]" style="width: {{ $pctSenior }}%;"></div>
									</div>
								</div>
							</div>
						</div>

						<!-- Widget 3: Checkout Alerts -->
						<div class="premium-widget premium-widget--alert flex flex-wrap gap-8 rounded-2xl border border-glass-border bg-glass p-6 shadow-glass xl:col-span-2">
							<div class="min-w-[300px] flex-1">
								<div class="premium-widget__header mb-4 flex items-center gap-3">
									<div class="widget-icon widget-icon--orange flex h-9 w-9 items-center justify-center rounded-lg bg-[#fef3c7] text-[#b45309] dark:bg-[#3a2f14] dark:text-[#e5c35c]">
										<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
									</div>
									<h4 class="m-0 text-sm font-bold uppercase tracking-wide text-hp-text">CHECKOUT ALERTS</h4>
								</div>
								<div class="premium-widget__grid cols-4 mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
									<div class="premium-stat stat-due bg-transparent p-0 shadow-none">
										<span class="text-xs font-bold uppercase tracking-[0.06em] text-hp-text-muted">TOTAL DUE</span>
										<strong class="block text-[1.8rem] font-extrabold text-[#e11d48] dark:text-[#fca5a5]">{{ $guestSummaryCheckoutDue }}</strong>
										<div class="mb-6 text-xs text-hp-text-muted">Guests</div>
										<strong class="block text-xl font-bold text-[#f59e0b] dark:text-[#fcd34d]">{{ $resSummaryCheckoutDue }}</strong>
										<div class="text-xs text-hp-text-muted">Reservations due</div>
									</div>
									<div class="premium-stat stat-due-sub bg-transparent p-0 shadow-none">
										<span class="text-xs font-bold uppercase tracking-[0.06em] text-hp-text-muted">MAIN DUE</span>
										<strong class="block text-[1.8rem] font-extrabold text-[#e11d48] dark:text-[#fca5a5]">{{ $dueMainGuests }}</strong>
										<div class="mb-6 text-xs text-hp-text-muted">Guests</div>
									</div>
									<div class="premium-stat stat-due-sub bg-transparent p-0 shadow-none">
										<span class="text-xs font-bold uppercase tracking-[0.06em] text-hp-text-muted">SINGLE COMP.</span>
										<strong class="block text-[1.8rem] font-extrabold text-[#e11d48] dark:text-[#fca5a5]">{{ $dueSingleCompanions }}</strong>
										<div class="mb-6 text-xs text-hp-text-muted">Guests</div>
									</div>
									<div class="premium-stat stat-due-sub bg-transparent p-0 shadow-none">
										<span class="text-xs font-bold uppercase tracking-[0.06em] text-hp-text-muted">BULK COMP.</span>
										<strong class="block text-[1.8rem] font-extrabold text-[#e11d48] dark:text-[#fca5a5]">{{ $dueBulkCompanions }}</strong>
										<div class="mb-6 text-xs text-hp-text-muted">Guests</div>
									</div>
								</div>
							</div>

							<div class="upcoming-checkouts-panel min-w-[300px] flex-1 border-l border-dashed border-black/10 pl-8 dark:border-white/10">
								<div class="mb-6 flex items-center justify-between">
									<h4 class="m-0 text-sm font-semibold text-hp-text">Upcoming Check-outs</h4>
									<a href="#" class="flex items-center gap-1 text-[0.8rem] font-medium text-[#f97316] no-underline">
										View all alerts <span class="text-[1.1rem]">&rarr;</span>
									</a>
								</div>

								<div class="upcoming-checkouts-list flex flex-col gap-3">
									@forelse (collect($activeReservations ?? [])->take(3) as $res)
						@php
							$primaryGuest = $res->reservationGuests->firstWhere('is_primary_guest', true)?->customer;
							$guestName = $primaryGuest ? trim(($primaryGuest->first_name ?? '') . ' ' . ($primaryGuest->last_name ?? '')) : 'Unknown';
							$amenityNames = $res->reservationAmenities->pluck('amenity.amenities_name')->filter()->unique()->join(', ');
							$entranceLabel = $res->entranceFee
								? 'Entrance' . ($res->entranceFee->pricing_type ? ' · ' . $res->entranceFee->pricing_type : '')
								: '';
						@endphp
										<div class="upcoming-item flex items-center justify-between rounded-lg bg-[rgba(34,197,94,0.05)] p-3">
											<div class="flex items-center gap-4">
												<div class="flex h-10 w-10 items-center justify-center rounded-full bg-[rgba(34,197,94,0.1)] text-[#22c55e]">
													<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
												</div>
												<div>
													<div class="text-sm font-semibold text-hp-text">{{ $amenityNames ?: ($entranceLabel ?: 'Entrance') }}</div>
													<div class="text-xs text-hp-text-muted">Reserved by {{ $guestName }}</div>
												</div>
											</div>
											<div class="time-left-pill flex items-center gap-1.5 rounded-full bg-[rgba(249,115,22,0.1)] px-3 py-1 text-xs font-semibold text-[#c2410c]">
												<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
												<span class="table-time-left" data-checkout-at="{{ $reservationData[$res->id]['checkout_at'] ?? '' }}"></span>
											</div>
										</div>
									@empty
										<div class="rounded-xl border border-dashed border-black/10 py-6 text-center text-xs text-hp-text-muted dark:border-white/10">
											No active check-outs scheduled
										</div>
									@endforelse
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
						<div class="checkins-main-column min-w-0">

							<section class="checkins-card overflow-hidden rounded-2xl border border-glass-border bg-glass shadow-glass">
							<div class="checkins-card-header flex items-center justify-between gap-4 border-b border-glass-border px-6 py-5">
								<div class="table-header-left flex items-center">
									<h2 class="checkins-title m-0 font-display text-xl font-bold text-hp-text">Guest Data View</h2>
								</div>
								<div class="checkins-actions flex items-center gap-2">
									<button type="button" class="ci-btn-primary inline-flex cursor-pointer items-center gap-2 rounded-xl border-0 bg-hp-green px-4 py-2.5 text-sm font-semibold text-white transition-all duration-200 hover:-translate-y-px hover:bg-hp-green-dark hover:shadow-[0_6px_16px_rgba(23,138,82,0.22)]" data-open-add-guest-modal="true">
										<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
											<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
										</svg>
										Add Guest
									</button>
									<button type="button" class="ci-btn-icon inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-glass-border bg-glass text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" id="scanQrBtn" aria-label="Scan QR Code">
										<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
											<path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
											<path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
										</svg>
									</button>
								</div>
							</div>
						<div class="guest-filter-shell flex flex-col gap-3 border-b border-glass-border/40 bg-black/[0.01] px-6 py-4 dark:bg-white/[0.01]">
							<button type="button" class="guest-filter-toggle inline-flex w-fit cursor-pointer items-center justify-between gap-2.5 rounded-full border border-glass-border bg-glass px-4 py-2 font-semibold text-hp-text transition-all duration-200 hover:bg-hp-gold hover:text-hp-green-dark dark:border-glass-border dark:hover:bg-[#2d5a32] dark:hover:border-[#4a8a52] dark:hover:text-[#c8e6c8]" id="guestFilterToggle" aria-expanded="false" aria-controls="guestFilterPanel">
							<span>Filters</span>
							<span class="guest-filter-toggle__icon text-[0.95rem]">▾</span>
						</button>
						<div class="guest-toolbar guest-toolbar--collapsed grid items-end gap-3 rounded-[14px] border border-glass-border bg-hp-cream p-4 transition-colors duration-300 md:grid-cols-2 xl:grid-cols-3 dark:bg-glass" id="guestFilterPanel" hidden>
							<label class="guest-toolbar__field guest-toolbar__field--search grid gap-1.5 text-[0.82rem] font-semibold text-hp-text xl:col-span-3">
								<span>Search</span>
								<input type="search" id="guestSearchInput" placeholder="Search by name, ID, gender" class="w-full rounded-[11px] border border-glass-border bg-glass px-3.5 py-2.5 text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
							</label>
							<label class="guest-toolbar__field grid gap-1.5 text-[0.82rem] font-semibold text-hp-text">
								<span>Sort by</span>
								<select id="guestSortSelect" class="w-full rounded-[11px] border border-glass-border bg-glass px-3.5 py-2.5 text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
									<option value="name-asc">Name (A-Z)</option>
									<option value="name-desc">Name (Z-A)</option>
									<option value="age-asc">Age (Low-High)</option>
									<option value="age-desc">Age (High-Low)</option>
									<option value="reservation-asc">Reservation Type</option>
								</select>
							</label>
							<label class="guest-toolbar__field grid gap-1.5 text-[0.82rem] font-semibold text-hp-text">
								<span>Show Guests</span>
								<select id="guestRoleSelect" class="w-full rounded-[11px] border border-glass-border bg-glass px-3.5 py-2.5 text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
									<option value="all">All Guests</option>
									<option value="primary">Main Guests Only</option>
									<option value="companion">Companions Only</option>
								</select>
							</label>
							<label class="guest-toolbar__field grid gap-1.5 text-[0.82rem] font-semibold text-hp-text">
								<span>Check-in from</span>
								<input type="date" id="guestCheckInFrom" class="w-full rounded-[11px] border border-glass-border bg-glass px-3.5 py-2.5 text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
							</label>
							<label class="guest-toolbar__field grid gap-1.5 text-[0.82rem] font-semibold text-hp-text">
								<span>Check-in to</span>
								<input type="date" id="guestCheckInTo" class="w-full rounded-[11px] border border-glass-border bg-glass px-3.5 py-2.5 text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
							</label>
							<label class="guest-toolbar__field grid gap-1.5 text-[0.82rem] font-semibold text-hp-text">
								<span>Reservation ID</span>
								<select id="guestReservationSelect" class="w-full rounded-[11px] border border-glass-border bg-glass px-3.5 py-2.5 text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
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
							<button type="button" class="guest-toolbar__clear cursor-pointer rounded-[11px] border-none bg-[rgba(13,44,29,0.1)] px-4 py-2.5 font-semibold text-hp-text transition-colors duration-200 hover:bg-hp-gold hover:text-hp-green-dark dark:bg-[#2d5a32] dark:text-[#c8e6c8]" id="guestFiltersClear">Clear</button>
						</div>
					</div>

					<div class="guest-toolbar__meta flex items-center justify-between border-b border-glass-border/30 bg-black/[0.01] px-6 py-2.5 text-xs font-semibold text-hp-text-muted dark:bg-white/[0.01]">
						<span id="guestResultsCount">Showing {{ $activeCustomers->count() }} active guests</span>
					</div>

					<div class="guest-table-wrap w-full overflow-x-auto" id="guestTableWrap">
						<table class="guest-table w-full border-collapse border-spacing-0 bg-transparent">
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
								@php
									// Order rows so each primary guest is immediately followed by
									// their companions (grouped by active reservation) instead of
									// being scattered alphabetically across the table.
									$customersById = collect($customers ?? collect())->keyBy('id');
									$guestOrderKeys = [];
									foreach ($customers ?? collect() as $orderCustomer) {
										$orderEntry = $orderCustomer->reservationGuests
											->filter(fn ($g) => $g->reservation && ! $g->checked_out_at && $g->reservation->check_in && $g->reservation->status === 'Checked In')
											->first(fn ($g) => $g->reservation && $g->reservation->reservation_type === 'walk_in')
											?? $orderCustomer->reservationGuests
												->filter(fn ($g) => $g->reservation && ! $g->checked_out_at && $g->reservation->check_in && $g->reservation->status === 'Checked In')
												->first();
										$orderRes = $orderEntry?->reservation;
										$resKey = $orderRes
											? ($orderRes->check_in?->timestamp ?? $orderRes->reservation_date?->timestamp ?? 0)
											: PHP_INT_MAX;
										$guestOrderKeys[$orderCustomer->id] = [
											$resKey,
											($orderEntry?->is_primary_guest ?? false) ? 0 : 1,
											strtolower(trim(($orderCustomer->last_name ?? '') . ' ' . ($orderCustomer->first_name ?? ''))),
										];
									}
									$orderedCustomerIds = collect($customers ?? collect())
										->sortBy(function ($c) use ($guestOrderKeys) {
											$k = $guestOrderKeys[$c->id] ?? [PHP_INT_MAX, 1, ''];
											// Fixed-width string key: reservation check-in, then primary
											// (0) before companions (1), then name. (Multi-closure sortBy
											// silently no-ops here, so use one string key.)
											return sprintf('%020d|%d|%s', $k[0], $k[1], $k[2]);
										})
										->pluck('id')
										->all();
								@endphp
								@foreach ($orderedCustomerIds as $orderedCustomerId)
									@php
										$customer = $customersById[$orderedCustomerId] ?? null;
									@endphp
									@if (! $customer)
										@continue
									@endif
									@php
										$hasActiveReservation = $customer->reservationGuests->filter(function ($guest) {
											$reservation = $guest->reservation ?? null;
											if (! $reservation) return false;
											if ($guest->checked_out_at) return false;
											if (! $reservation->check_in) return false;
											return $reservation->status === 'Checked In';
										})->isNotEmpty();
									@endphp

									@if (! $hasActiveReservation)
										@continue
									@endif

									@php
										$reservationEntry = $customer->reservationGuests->filter(function ($guest) {
											return $guest->reservation && !$guest->checked_out_at && $guest->reservation->check_in && $guest->reservation->status === 'Checked In';
										})->first(function ($guest) {
											return $guest->reservation && $guest->reservation->reservation_type === 'walk_in';
										}) ?? $customer->reservationGuests->filter(function ($guest) {
											return $guest->reservation && !$guest->checked_out_at && $guest->reservation->check_in && $guest->reservation->status === 'Checked In';
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
										}										// Group Bulk Companions — ONE row per group. Two bulk groups are
										// only merged when they share the reservation id, gender, age
										// group AND nationality (a "Kids" group is never merged into a
										// "Seniors" group).
										$totalBulk = 0;
										$activeBulk = 0;
										$bulkGender = '';
										$bulkAgeGroup = '';
										$bulkNationality = '';
										if ($isBulk) {
											static $processedBulkGroupKeys = [];
											$resId = $reservationEntry?->reservation?->id;
											$bulkReservation = $reservationEntry?->reservation;
											if (! $resId || ! $bulkReservation) {
												continue;
											}

											// Age group from the stored representative midpoint age
											// (0-12→6, 13-17→15, 18-59→30, 60+→65).
											$bulkAgeNum = (int) ($customer->age ?? 99);
											$bulkAgeGroup = $bulkAgeNum <= 12 ? '0-12' : ($bulkAgeNum <= 17 ? '13-17' : ($bulkAgeNum <= 59 ? '18-59' : '60+'));
											$bulkGender = $customer->gender ?? 'Unknown';
											$bulkNationality = (bool) ($customer->is_foreigner ?? false) ? 'Foreigner' : 'Filipino';
											$bulkGroupKey = $resId . '|' . $bulkGender . '|' . $bulkAgeGroup . '|' . $bulkNationality;

											if (in_array($bulkGroupKey, $processedBulkGroupKeys)) {
												continue;
											}
											$processedBulkGroupKeys[] = $bulkGroupKey;

											$groupBulk = $bulkReservation->reservationGuests->filter(function ($rg) use ($bulkGender, $bulkAgeGroup, $bulkNationality) {
												$c = $rg->customer;
												if (! $c) return false;
												$fn = strtolower(trim($c->first_name ?? ''));
												if (! (str_starts_with($fn, 'bulk') || str_contains($fn, 'companion'))) return false;
												$ageNum = (int) ($c->age ?? 99);
												$ageGroup = $ageNum <= 12 ? '0-12' : ($ageNum <= 17 ? '13-17' : ($ageNum <= 59 ? '18-59' : '60+'));
												$gender = $c->gender ?? 'Unknown';
												$nationality = (bool) ($c->is_foreigner ?? false) ? 'Foreigner' : 'Filipino';
												return $gender === $bulkGender && $ageGroup === $bulkAgeGroup && $nationality === $bulkNationality;
											});

											$totalBulk = $groupBulk->count();
											$activeBulk = $groupBulk->whereNull('checked_out_at')->count();
											if ($activeBulk === 0) continue;

											$customer->first_name = "Bulk Companions (#$resId)";
											$customer->last_name = "";
											$customer->middle_name = "$activeBulk/$totalBulk Checked In";
											$customer->age = $bulkAgeGroup;
											$customer->gender = $bulkGender;
											$customer->is_foreigner = $bulkNationality === 'Foreigner' ? true : false;
										}
									@endphp
									<tr
										class="guest-row {{ $highlightClass }} {{ $isPrimary ? 'guest-row--primary' : 'guest-row--companion' }} {{ $isBulk ? 'guest-row--bulk-group' : '' }} cursor-pointer select-none transition-colors duration-200 hover:bg-hp-cream focus-visible:bg-hp-cream focus-visible:outline-none dark:hover:bg-[#2d5a32] dark:focus-visible:bg-[#2d5a32]"
										@if (! $isPrimary) style="display: none;" @endif
										data-customer-id="{{ $customer->id }}"
										data-reservation-id="{{ $reservationEntry?->reservation?->id ?? '' }}"
										data-is-primary="{{ $isPrimary ? 'true' : 'false' }}"
										data-bulk-group="{{ $isBulk ? 'true' : 'false' }}"
										data-bulk-total="{{ $totalBulk }}"
										data-bulk-active="{{ $activeBulk }}"
										data-bulk-demo="{{ $isBulk ? ($bulkGender . ' · ' . $bulkAgeGroup . ' · ' . $bulkNationality) : '' }}"
										data-bulk-gender="{{ $isBulk ? $bulkGender : '' }}"
										data-bulk-age-group="{{ $isBulk ? $bulkAgeGroup : '' }}"
										data-bulk-nationality="{{ $isBulk ? $bulkNationality : '' }}"
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
											<div class="cell-person flex min-w-0 items-center gap-2.5">
												<span class="cell-person__avatar flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#178a52] to-[#0e5c37] text-[0.6rem] font-bold text-white shadow-sm {{ $isBulk ? 'cell-person__avatar--bulk' : ($isPrimary ? 'cell-person__avatar--main' : 'cell-person__avatar--companion') }}" title="{{ $isBulk ? 'Bulk Companion' : ($isPrimary ? 'Main Guest' : 'Single Companion') }}">
													@if($isBulk)
														<svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" /><path d="M5.082 14.254a8.287 8.287 0 00-1.308 5.135 9.687 9.687 0 01-1.764-.44l-.115-.04a.563.563 0 01-.373-.487l-.01-.121a3.75 3.75 0 016.576-3.036 7.525 7.525 0 00-3.006-1.011zM18.918 14.254a8.287 8.287 0 011.308 5.135 9.687 9.687 0 001.764-.44l.115-.04a.563.563 0 00.373-.487l.01-.121a3.75 3.75 0 00-6.576-3.036 7.525 7.525 0 013.006-1.011z" /></svg>
													@elseif($isPrimary)
														<svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" /><path d="M5.082 14.254a8.287 8.287 0 00-1.308 5.135 9.687 9.687 0 01-1.764-.44l-.115-.04a.563.563 0 01-.373-.487l-.01-.121a3.75 3.75 0 016.576-3.036 7.525 7.525 0 00-3.006-1.011zM18.918 14.254a8.287 8.287 0 011.308 5.135 9.687 9.687 0 001.764-.44l.115-.04a.563.563 0 00.373-.487l.01-.121a3.75 3.75 0 00-6.576-3.036 7.525 7.525 0 013.006-1.011z" /></svg>
													@else
														<svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" /></svg>
													@endif
												</span>
												@if($isPrimary && $companionCount > 0)
													<button type="button" class="btn-expand-row flex h-6 w-6 shrink-0 cursor-pointer items-center justify-center rounded-full border border-glass-border bg-glass text-hp-text-muted transition-all duration-200 hover:bg-hp-cream hover:text-hp-green dark:hover:bg-[#2d5a32] [&.expanded]:rotate-180 [&.expanded]:text-hp-green" data-expand-reservation="{{ $reservationEntry?->reservation?->id }}" aria-label="Toggle Companions">
														<svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
													</button>
												@endif
												<div class="cell-person__body min-w-0">
													<div class="guest-name flex items-center gap-1.5 text-[0.82rem] font-semibold leading-tight text-hp-text">
														<span>{{ $isBulk ? $customer->first_name : trim(($customer->first_name ?? '') . ' ' . ($customer->middle_name ?? '') . ' ' . ($customer->last_name ?? '')) }}</span>
														@if($isStray)
															<span class="rounded bg-[#f59e0b] px-1 py-0.5 align-middle text-[0.6rem] font-semibold text-white">Stray</span>
														@endif
														@if ($isPrimary && $companionCount > 0 && ! $isStray)
															<span class="guest-companion-count inline-flex items-center gap-0.5 rounded-full bg-hp-green/10 px-1.5 py-0.2 text-[0.62rem] font-bold text-hp-green dark:bg-hp-green/25 dark:text-[#6ab88c]">
																+{{ $companionCount }}
															</span>
														@endif
													</div>
													<div class="guest-meta mt-0.5 flex items-center gap-2 text-[0.72rem] leading-tight text-hp-text-muted">
														@if($isBulk)
															<span>{{ $customer->middle_name }}</span>
														@else
															<span>ID: {{ $customer->id }}</span>
														@endif
													</div>
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
											<div class="text-[0.82rem] font-semibold leading-tight text-hp-text">{{ $displayAge }}</div>
											<div class="text-[0.72rem] leading-tight text-hp-text-muted capitalize">{{ $customer->gender ?? 'N/A' }}</div>
										</td>
										<td>
											<span class="status-pill inline-flex items-center gap-1 whitespace-nowrap rounded-full border border-glass-border px-2 py-0.5 text-[0.65rem] font-bold tracking-[0.02em] shadow-sm {{ $customer->is_foreigner ? 'status-pill--confirmed bg-[#e7f3ec] text-[#0e5c37] dark:bg-[#1a3324] dark:text-[#6ab88c]' : 'status-pill--checked-out bg-[rgba(120,130,122,0.13)] text-hp-text-muted' }}">{{ $customer->is_foreigner ? 'Foreigner' : 'Filipino' }}</span>
										</td>
										<td>
											<span class="table-time-left inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[0.72rem] font-semibold text-hp-text-muted" data-checkout-at="{{ $checkoutAtStr }}" data-status="{{ $reservationEntry?->reservation?->status ?? '' }}"></span>
										</td>
										<td class="text-right text-[#9ca3af]">
											<svg class="inline-block h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z" /></svg>
										</td>
									</tr>
								@endforeach
									<tr id="guestEmptyRow" style="display: {{ ($activeCustomers ?? collect())->isEmpty() ? '' : 'none' }};">
										<td colspan="5" class="border-0">
											<div class="empty-state-wrapper flex flex-col items-center justify-center gap-1 py-12 text-center">
												<div class="empty-state-icon mb-2 flex h-14 w-14 items-center justify-center rounded-full bg-[#e7f3ec] text-[#1c5c3c]">
													<svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
														<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
													</svg>
												</div>
												<h4 class="m-0 text-base font-bold text-hp-text">No active guests</h4>
												<p class="m-0 text-sm text-hp-text-muted">There are no guests currently checked in to the park.</p>
											</div>
										</td>
									</tr>
							</tbody>
						</table>
					</div>
						</section>
					</div>

					<div class="checkins-sidebar">
						<!-- Sidebar Summary Cards (Individual Pastel Cards Container) -->
						<div class="sidebar-summary-cards mb-6 flex flex-col gap-3 rounded-2xl border border-glass-border bg-glass p-3 shadow-glass">
							<!-- Item 1: Active Guests -->
							<div class="top-stat-card flex items-center rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-3 transition-all hover:shadow-sm dark:border-emerald-500/25 dark:bg-emerald-950/30">
								<div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400">
									<svg class="h-5 w-5 stroke-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
								</div>
								<div class="mx-3.5 h-6 w-[1px] bg-emerald-500/20"></div>
								<strong class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 min-w-[28px]">{{ $activeCustomers->count() }}</strong>
								<span class="ml-3.5 text-sm font-semibold text-hp-text">Active Guests</span>
							</div>

							<!-- Item 2: Checked In Today -->
							<div class="top-stat-card flex items-center rounded-xl border border-sky-500/20 bg-sky-500/10 p-3 transition-all hover:shadow-sm dark:border-sky-500/25 dark:bg-sky-950/30">
								<div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-500/20 text-sky-600 dark:text-sky-400">
									<svg class="h-5 w-5 stroke-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
								</div>
								<div class="mx-3.5 h-6 w-[1px] bg-sky-500/20"></div>
								<strong class="text-2xl font-extrabold text-sky-600 dark:text-sky-400 min-w-[28px]">{{ $todaysCheckins }}</strong>
								<span class="ml-3.5 text-sm font-semibold text-hp-text">Checked In Today</span>
							</div>

							<!-- Item 3: Expected Check-outs -->
							<div class="top-stat-card flex items-center rounded-xl border border-amber-500/20 bg-amber-500/10 p-3 transition-all hover:shadow-sm dark:border-amber-500/25 dark:bg-amber-950/30">
								<div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400">
									<svg class="h-5 w-5 stroke-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
								</div>
								<div class="mx-3.5 h-6 w-[1px] bg-amber-500/20"></div>
								<strong class="text-2xl font-extrabold text-amber-600 dark:text-amber-400 min-w-[28px]">{{ $expectedCheckouts }}</strong>
								<span class="ml-3.5 text-sm font-semibold text-hp-text">Expected Check-outs</span>
							</div>

							<!-- Item 4: Walk-ins Today -->
							<div class="top-stat-card flex items-center rounded-xl border border-purple-500/20 bg-purple-500/10 p-3 transition-all hover:shadow-sm dark:border-purple-500/25 dark:bg-purple-950/30">
								<div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-purple-500/20 text-purple-600 dark:text-purple-400">
									<svg class="h-5 w-5 stroke-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
								</div>
								<div class="mx-3.5 h-6 w-[1px] bg-purple-500/20"></div>
								<strong class="text-2xl font-extrabold text-purple-600 dark:text-purple-400 min-w-[28px]">{{ $walkInsToday }}</strong>
								<span class="ml-3.5 text-sm font-semibold text-hp-text">Walk-Ins Today</span>
							</div>
						</div>

						<!-- Demographics Card -->
						<div class="checkins-card sidebar-data-card rounded-2xl border border-glass-border bg-glass p-5 shadow-glass mb-6">
							<h4 class="sidebar-data-title m-0 mb-4 font-display text-base font-bold text-hp-text">Guest Demographics</h4>
							<div class="sidebar-data-list flex flex-col gap-3">
								<div class="sidebar-data-item">
									<div class="data-item-top mb-1 flex items-center justify-between text-xs">
										<span class="data-label font-semibold text-hp-text-muted">Kids (0-12)</span>
										<span class="data-value font-bold text-hp-text">{{ $metrics['kids'] }}</span>
									</div>
									<div class="data-progress-bg h-1.5 overflow-hidden rounded-full bg-glass-hover dark:bg-white/10"><div class="data-progress-fill h-full rounded-full bg-hp-green-mid" style="width: {{ ($metrics['kids'] / $totalGuests) * 100 }}%"></div></div>
								</div>
								<div class="sidebar-data-item">
									<div class="data-item-top mb-1 flex items-center justify-between text-xs">
										<span class="data-label font-semibold text-hp-text-muted">Teens (13-17)</span>
										<span class="data-value font-bold text-hp-text">{{ $metrics['teens'] }}</span>
									</div>
									<div class="data-progress-bg h-1.5 overflow-hidden rounded-full bg-glass-hover dark:bg-white/10"><div class="data-progress-fill h-full rounded-full bg-hp-green-mid" style="width: {{ ($metrics['teens'] / $totalGuests) * 100 }}%"></div></div>
								</div>
								<div class="sidebar-data-item">
									<div class="data-item-top mb-1 flex items-center justify-between text-xs">
										<span class="data-label font-semibold text-hp-text-muted">Adults (18-59)</span>
										<span class="data-value font-bold text-hp-text">{{ $metrics['adults'] }}</span>
									</div>
									<div class="data-progress-bg h-1.5 overflow-hidden rounded-full bg-glass-hover dark:bg-white/10"><div class="data-progress-fill h-full rounded-full bg-hp-green-mid" style="width: {{ ($metrics['adults'] / $totalGuests) * 100 }}%"></div></div>
								</div>
								<div class="sidebar-data-item">
									<div class="data-item-top mb-1 flex items-center justify-between text-xs">
										<span class="data-label font-semibold text-hp-text-muted">Seniors (60+)</span>
										<span class="data-value font-bold text-hp-text">{{ $metrics['seniors'] }}</span>
									</div>
									<div class="data-progress-bg h-1.5 overflow-hidden rounded-full bg-glass-hover dark:bg-white/10"><div class="data-progress-fill h-full rounded-full bg-hp-green-mid" style="width: {{ ($metrics['seniors'] / $totalGuests) * 100 }}%"></div></div>
								</div>
							</div>
						</div>
					</div>
				</div> <!-- Closing #guestTableSection -->

					{{-- RESERVATION TABLE --}}
					<div id="reservationTableSection" class="tab-content-section" style="display: none;">
						<section class="checkins-card overflow-hidden rounded-2xl border border-glass-border bg-glass shadow-glass">
							<div class="checkins-card-header flex items-center justify-between gap-4 border-b border-glass-border px-6 py-5">
								<div class="table-header-left flex items-center">
									<h2 class="checkins-title m-0 font-display text-xl font-bold text-hp-text">Reservation Data View</h2>
								</div>
								<div class="checkins-actions flex items-center gap-2">
									<button type="button" class="ci-btn-primary inline-flex cursor-pointer items-center gap-2 rounded-xl border-0 bg-hp-green px-4 py-2.5 text-sm font-semibold text-white transition-all duration-200 hover:-translate-y-px hover:bg-hp-green-dark hover:shadow-[0_6px_16px_rgba(23,138,82,0.22)]" data-open-add-guest-modal="true">
										<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
											<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
										</svg>
										Add Guest
									</button>
									<button type="button" class="ci-btn-icon inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-glass-border bg-glass text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" aria-label="Scan QR Code">
										<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
											<path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
											<path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
										</svg>
									</button>
								</div>
							</div>

					<div class="guest-filter-shell flex flex-col gap-3 border-b border-glass-border/40 bg-black/[0.01] px-6 py-4 dark:bg-white/[0.01]">
						<button type="button" class="guest-filter-toggle inline-flex w-fit cursor-pointer items-center justify-between gap-2.5 rounded-full border border-glass-border bg-glass px-4 py-2 font-semibold text-hp-text transition-all duration-200 hover:bg-hp-gold hover:text-hp-green-dark dark:border-glass-border dark:hover:bg-[#2d5a32] dark:hover:border-[#4a8a52] dark:hover:text-[#c8e6c8]" id="resvFilterToggle" aria-expanded="false" aria-controls="resvFilterPanel">
							<span>Reservation Filters</span>
							<span class="guest-filter-toggle__icon text-[0.95rem]">▾</span>
						</button>
						<div class="guest-toolbar guest-toolbar--collapsed grid items-end gap-3 rounded-[14px] border border-glass-border bg-hp-cream p-4 transition-colors duration-300 md:grid-cols-2 xl:grid-cols-4 dark:bg-glass" id="resvFilterPanel" hidden>
							<label class="guest-toolbar__field guest-toolbar__field--search grid gap-1.5 text-[0.82rem] font-semibold text-hp-text xl:col-span-4">
								<span>Search</span>
								<input type="search" id="resvSearchInput" placeholder="Search by reservation ID, main guest, booker, amenity..." class="w-full rounded-[11px] border border-glass-border bg-glass px-3.5 py-2.5 text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
							</label>
							<label class="guest-toolbar__field grid gap-1.5 text-[0.82rem] font-semibold text-hp-text">
								<span>Reservation Type</span>
								<select id="resvTypeFilter" class="w-full rounded-[11px] border border-glass-border bg-glass px-3.5 py-2.5 text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
									<option value="all">All Types</option>
									<option value="walk_in">Walk-in</option>
									<option value="online">Online</option>
								</select>
							</label>
							<label class="guest-toolbar__field grid gap-1.5 text-[0.82rem] font-semibold text-hp-text">
								<span>Check-in from</span>
								<input type="date" id="resvDateFrom" class="w-full rounded-[11px] border border-glass-border bg-glass px-3.5 py-2.5 text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
							</label>
							<label class="guest-toolbar__field grid gap-1.5 text-[0.82rem] font-semibold text-hp-text">
								<span>Check-in to</span>
								<input type="date" id="resvDateTo" class="w-full rounded-[11px] border border-glass-border bg-glass px-3.5 py-2.5 text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
							</label>
							<button type="button" class="guest-toolbar__clear cursor-pointer rounded-[11px] border-none bg-[rgba(13,44,29,0.1)] px-4 py-2.5 font-semibold text-hp-text transition-colors duration-200 hover:bg-hp-gold hover:text-hp-green-dark dark:bg-[#2d5a32] dark:text-[#c8e6c8]" id="resvFiltersClear">Clear</button>
						</div>
					</div>

					<div class="guest-toolbar__meta flex items-center justify-between border-b border-glass-border/30 bg-black/[0.01] px-6 py-2.5 text-xs font-semibold text-hp-text-muted dark:bg-white/[0.01]">
						<span id="resvResultsCount">Showing {{ $activeReservations->count() }} reservation{{ $activeReservations->count() === 1 ? '' : 's' }}</span>
					</div>

					<div class="guest-table-wrap w-full overflow-x-auto" id="reservationTableWrap">
						<table class="guest-table w-full border-collapse border-spacing-0 bg-transparent">
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
										$rowAmenityNames = $reservation->reservationAmenities->pluck('amenity.amenities_name')->filter()->unique()->join(', ');
										$rowPrimaryName = $primaryGuest ? trim(($primaryGuest->first_name ?? '') . ' ' . ($primaryGuest->last_name ?? '')) : '';
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
										class="reservation-row {{ $highlightClass }} cursor-pointer select-none transition-colors duration-200 hover:bg-hp-cream focus-visible:bg-hp-cream focus-visible:outline-none dark:hover:bg-[#2d5a32] dark:focus-visible:bg-[#2d5a32]"
										data-reservation-id="{{ $reservation->id }}"
										data-reservation-type="{{ $reservation->reservation_type }}"
										data-check-in-date="{{ $reservation->check_in ? \Carbon\Carbon::parse($reservation->check_in)->format('Y-m-d') : '' }}"
										data-reservation-search="{{ strtolower(trim($reservation->id . ' ' . ($reservation->reservation_type === 'walk_in' ? 'walk-in' : 'online') . ' ' . $rowPrimaryName . ' ' . ($reservation->booker_name ?? '') . ' ' . $rowAmenityNames . ' ' . ($reservation->status ?? ''))) }}"
										tabindex="0"
										role="button"
										aria-label="View reservation {{ $reservation->id }}"
									>
										<td>
											<div class="flex items-center gap-2">
												<button type="button" class="btn-expand-row flex h-6 w-6 shrink-0 cursor-pointer items-center justify-center rounded-full border border-glass-border bg-glass text-hp-text-muted transition-all duration-200 hover:bg-hp-cream hover:text-hp-green dark:hover:bg-[#2d5a32] [&.expanded]:rotate-180 [&.expanded]:text-hp-green" data-expand-reservation="{{ $reservation->id }}" aria-label="Toggle Reservation Details">
													<svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
												</button>
												<div>
													<div class="guest-name text-[0.82rem] font-semibold leading-tight text-hp-text">#{{ $reservation->id }}</div>
													<div class="guest-meta mt-0.5 flex items-center gap-1 text-[0.72rem] leading-tight text-hp-text-muted">
														<span>{{ $reservation->reservation_type === 'walk_in' ? 'Walk-in' : 'Online' }}</span>
														@if ($isMixedTime)
															<span class="status-pill status-pill--pending inline-flex items-center gap-0.5 rounded-full border border-glass-border px-1.5 py-0.2 text-[0.62rem] font-bold bg-[#fef3c7] text-[#b45309] dark:bg-[#3a2f14] dark:text-[#e5c35c]">Mixed</span>
														@endif
													</div>
												</div>
											</div>
										</td>
										<td>
											@if ($primaryGuest)
												<div class="cell-person flex min-w-0 items-center gap-2.5">
													<span class="cell-person__avatar cell-person__avatar--star flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#178a52] to-[#0e5c37] text-[0.6rem] font-bold text-white shadow-sm" title="Main Guest">
														<svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" /></svg>
													</span>
													<div class="cell-person__body min-w-0">
														<div class="guest-name truncate text-[0.82rem] font-semibold leading-tight text-hp-text">{{ trim(($primaryGuest->first_name ?? '') . ' ' . ($primaryGuest->middle_name ?? '') . ' ' . ($primaryGuest->last_name ?? '')) }}</div>
													</div>
												</div>
											@else
												<div class="guest-name text-[0.82rem] font-semibold text-hp-text">—</div>
											@endif
										</td>
										<td>
											<div class="guest-name mono-cell whitespace-nowrap text-[0.8rem] font-semibold leading-tight text-hp-text">{{ $reservation->check_in ? \Carbon\Carbon::parse($reservation->check_in)->format('M d, Y') : '—' }}</div>
											<div class="guest-meta mono-cell mt-0.5 whitespace-nowrap text-[0.72rem] leading-tight text-hp-text-muted">{{ $reservation->check_in ? \Carbon\Carbon::parse($reservation->check_in)->format('h:i A') : '—' }}</div>
										</td>
										<td>
											@php
												$amenityNames = $reservation->reservationAmenities->pluck('amenity.amenities_name')->filter()->unique()->join(', ');
											@endphp
											<span class="guest-meta truncate text-[0.78rem] text-hp-text-muted">{{ $amenityNames ?: 'None' }}</span>
										</td>
										<td>
											<div class="guest-name text-[0.82rem] font-semibold leading-tight text-hp-text">{{ $totalResGuests }} Total</div>
											<div class="guest-meta mt-0.5 text-[0.72rem] leading-tight text-hp-text-muted">{{ $remainingResGuests }} Remaining</div>
										</td>
										<td>
											<span class="table-time-left inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[0.72rem] font-semibold text-hp-text-muted" data-checkout-at="{{ $reservationData[$reservation->id]['checkout_at'] ?? '' }}" data-status="{{ $reservation->status ?? '' }}"></span>
										</td>
										<td class="text-right text-[#9ca3af]">
											<svg class="inline-block h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z" /></svg>
										</td>
									</tr>
								@empty
									<tr>
										<td colspan="7" class="border-0">
											<div class="empty-state-wrapper flex flex-col items-center justify-center gap-1 py-12 text-center">
												<div class="empty-state-icon mb-2 flex h-14 w-14 items-center justify-center rounded-full bg-[#e7f3ec] text-[#1c5c3c]">
													<svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
														<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
													</svg>
												</div>
												<h4 class="m-0 text-base font-bold text-hp-text">No active reservations</h4>
												<p class="m-0 text-sm text-hp-text-muted">There are no checked-in reservations right now.</p>
											</div>
										</td>
									</tr>
								@endforelse
							</tbody>
						</table>
					</div>
						</section>
					</div>
				</div>
			</main>
		</div>
	</div>
	<!-- Modals (Direct children of body) -->
	<div class="guest-modal" id="guestModal" aria-hidden="true">
		<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-modal="true"></div>
		<div class="guest-modal__content relative z-[1] w-full max-w-[720px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="guestModalTitle">
			<button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-modal="true" aria-label="Close details">&times;</button>
			<div class="guest-modal__header mb-4 flex items-center gap-3">
				<h3 id="guestModalTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Guest Details</h3>
				<span id="guestModalRole" class="guest-modal__role-badge inline-flex items-center rounded-full px-3 py-1.5 text-[0.78rem] font-bold uppercase tracking-[0.04em]"></span>
			</div>
			<div id="guestModalBody" class="guest-modal__body grid gap-4"></div>
			<div class="guest-form__actions mt-6 flex justify-end gap-3" id="guestModalActions">
				<button type="button" class="guest-form__button--secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" data-close-modal="true">Close</button>
				<button type="button" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark" id="guestCheckOutBtn">Check Out</button>
			</div>
		</div>
	</div>

	<script>
		window.ALL_AMENITIES = @json($amenities ?? []);
		window.SERVER_CURRENT_SESSION = "{{ ($currentPeriod ?? '') === 'nighttime' ? 'Nighttime' : 'Daytime' }}";
		window.SERVER_TODAY = "{{ now()->toDateString() }}";
	</script>

	<div class="guest-modal guest-modal--add" id="addGuestModal" aria-hidden="true">
		<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-add-modal="true"></div>
		<div class="guest-modal__content guest-modal__content--wide relative z-[1] w-full max-w-[900px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="addGuestModalTitle">
			<button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-add-modal="true" aria-label="Close add guest form">&times;</button>
			<div class="guest-modal__header mb-4 flex items-center justify-between gap-3 border-b border-[rgba(13,44,29,0.1)] pb-4 dark:border-white/10">
				<div class="flex items-center gap-3">
					<h3 id="addGuestModalTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Add Walk-In Reservation</h3>
					<span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
						Walk-In Check-In
					</span>
				</div>
			</div>
						<form id="addGuestForm" class="guest-form grid gap-4" action="{{ route('staff.checkins.guests.store') }}" method="POST">
							@csrf
							<input type="hidden" name="guest_mode" value="with_primary">
							<input type="hidden" name="reservation_type" id="reservation_type" value="walk_in">
							<input type="hidden" name="check_in" id="check_in" value="{{ now()->toDateString() }}">
							
							<!-- Master Reservation Dates & Sessions -->
							<input type="hidden" name="start_date" id="walkInStartDate" value="{{ now()->toDateString() }}">
							<input type="hidden" name="end_date" id="walkInEndDate" value="{{ now()->toDateString() }}">
							<input type="hidden" name="start_slot" id="walkInStartSlot" value="{{ ($currentPeriod ?? '') === 'nighttime' ? 'Nighttime' : 'Daytime' }}">
							<input type="hidden" name="end_slot" id="walkInEndSlot" value="{{ ($currentPeriod ?? '') === 'nighttime' ? 'Nighttime' : 'Daytime' }}">
							<input type="hidden" name="total_days" id="walkInTotalDays" value="1">
							<input type="hidden" name="time_period" id="time_period" value="{{ $currentPeriod ?? 'daytime' }}">

							@if ($errors->any())
								<div class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400">
									<ul class="m-0 list-disc pl-5">
										@foreach ($errors->all() as $error)
											<li>{{ $error }}</li>
										@endforeach
									</ul>
								</div>
							@endif
							@if (session('success'))
								<div class="rounded-xl border border-hp-green/30 bg-hp-green/10 px-4 py-3 text-sm font-semibold text-hp-green-dark dark:border-hp-green/20 dark:text-[#4ade80]">
									{{ session('success') }}
								</div>
							@endif

							<div class="guest-form__grid grid grid-cols-1 gap-4 lg:grid-cols-2">
								<div class="guest-form__section guest-form__section--compact rounded-2xl border border-glass-border bg-hp-cream p-4 transition-colors duration-300 dark:bg-white/5">
									<div class="guest-form__section-header mb-2 flex items-center justify-between">
										<h4 class="guest-form__section-title m-0 text-base font-bold text-hp-text dark:text-[#c8e6c8]">Stay & Entrance Details</h4>
									</div>
									
									<!-- Stay Dates & Sessions Selector -->
									<div class="guest-form__field-group mb-3 grid gap-1.5">
										<label class="guest-form__label text-sm font-semibold text-hp-text">Stay Schedule & Duration</label>
										<button type="button" class="flex w-full cursor-pointer items-center justify-between rounded-xl border border-glass-border bg-glass p-3 text-left transition-all duration-200 hover:border-hp-green hover:bg-glass-hover" id="walkInOpenCalendarBtn">
											<div class="flex items-center gap-3">
												<div class="flex h-9 w-9 items-center justify-center rounded-lg bg-hp-green/10 text-hp-green dark:bg-hp-green/20">
													<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
														<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
													</svg>
												</div>
												<div>
													<div class="text-sm font-bold text-hp-text dark:text-[#c8e6c8]" id="walkInScheduleSummaryText">Today ({{ ($currentPeriod ?? '') === 'nighttime' ? 'Nighttime' : 'Daytime' }}) — 1 Day</div>
													<div class="text-xs text-hp-text-muted" id="walkInScheduleDatesText">{{ now()->format('M d, Y') }} ({{ ($currentPeriod ?? '') === 'nighttime' ? 'Nighttime' : 'Daytime' }})</div>
												</div>
											</div>
											<span class="rounded-lg border border-hp-green/30 bg-hp-green/10 px-2.5 py-1 text-xs font-bold text-hp-green">Change Checkout</span>
										</button>
									</div>

									<div class="guest-form__field-group mb-3 grid gap-1.5">
										<label class="guest-form__checkbox-wrapper flex cursor-pointer items-center gap-2 text-sm text-hp-text">
											<input type="checkbox" name="include_pool" id="include_pool" class="h-4 w-4 accent-hp-green">
											<span>Include Pool Access</span>
										</label>
									</div>

									<div class="guest-form__field-group mb-3 grid gap-1.5">
										<label class="guest-form__label text-sm font-semibold text-hp-text">Companions</label>
										<div class="guest-form__actions-inline flex flex-wrap gap-2">
											<button type="button" class="guest-form__action-btn inline-flex cursor-pointer items-center rounded-lg border border-glass-border bg-glass px-3 py-2 text-sm font-medium text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" id="addCompanionBtn">
												<svg class="mr-1.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
													<path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
												</svg>
												Add Companion
											</button>
										</div>
									</div>

									<div class="guest-form__field-group grid gap-1.5">
										<label class="guest-form__label text-sm font-semibold text-hp-text">Entrance Fees Summary</label>
										<div class="guest-form__fees-list flex flex-col gap-1.5 rounded-lg border border-glass-border bg-glass p-3 text-sm text-hp-text-muted">
											<div class="guest-form__fee-item flex justify-between">
												<span>Adult Entrance Fee:</span>
												<strong class="text-hp-text" id="adultEntranceFee">₱0.00</strong>
											</div>
											<div class="guest-form__fee-item flex justify-between">
												<span>Child Entrance Fee:</span>
												<strong class="text-hp-text" id="childEntranceFee">₱0.00</strong>
											</div>
											<div class="guest-form__fee-item flex justify-between">
												<span>Pool Fee:</span>
												<strong class="text-hp-text" id="poolFee">₱0.00</strong>
											</div>
											<div class="guest-form__fee-item guest-form__fee-item--total flex justify-between border-t border-glass-border pt-2">
												<span>Entrance Subtotal:</span>
												<strong class="text-hp-green" id="totalEntranceFee">₱0.00</strong>
											</div>
										</div>
									</div>
								</div>

								<div id="primaryGuestSection" class="guest-form__section guest-form__section--compact rounded-2xl border border-glass-border bg-hp-cream p-4 transition-colors duration-300 dark:bg-white/5">
									<div class="guest-form__section-header mb-2">
										<h4 class="guest-form__section-title m-0 text-base font-bold text-hp-text dark:text-[#c8e6c8]">Primary Guest</h4>
									</div>
									<div class="guest-form__field-group mb-3 grid gap-1.5">
										<label class="guest-form__label text-sm font-semibold text-hp-text" for="primary_first_name">First name</label>
										<input type="text" name="primary_guest[first_name]" id="primary_first_name" placeholder="Enter first name" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
									</div>
									<div class="guest-form__field-group mb-3 grid gap-1.5">
										<label class="guest-form__label text-sm font-semibold text-hp-text" for="primary_middle_name">Middle name</label>
										<input type="text" name="primary_guest[middle_name]" id="primary_middle_name" placeholder="Enter middle name" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
									</div>
									<div class="guest-form__field-group mb-3 grid gap-1.5">
										<label class="guest-form__label text-sm font-semibold text-hp-text" for="primary_last_name">Last name</label>
										<input type="text" name="primary_guest[last_name]" id="primary_last_name" placeholder="Enter last name" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
									</div>
									<div class="guest-form__row guest-form__row--two mb-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
										<div class="guest-form__field-group grid gap-1.5">
											<div class="flex items-center justify-between">
												<label class="guest-form__label text-sm font-semibold text-hp-text" for="primary_age">Age</label>
												<span id="primaryAgeBadge" class="rounded-md border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[0.7rem] font-bold text-emerald-700 dark:text-emerald-300">Adult Rate</span>
											</div>
											<input type="number" name="primary_guest[age]" id="primary_age" min="0" placeholder="Age in years" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
										</div>
										<div class="guest-form__field-group grid gap-1.5">
											<label class="guest-form__label text-sm font-semibold text-hp-text" for="primary_gender">Gender</label>
											<select name="primary_guest[gender]" id="primary_gender" class="guest-form__select w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
												<option value="">Select gender</option>
												<option value="Male">Male</option>
												<option value="Female">Female</option>
											</select>
										</div>
									</div>
									<div class="guest-form__field-group mb-3 grid gap-1.5">
										<label class="guest-form__label text-sm font-semibold text-hp-text" for="primaryGuestIsForeigner">Nationality</label>
										<select name="primary_guest[is_foreigner]" id="primaryGuestIsForeigner" class="guest-form__select w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
											<option value="0" selected>Filipino</option>
											<option value="1">Foreigner</option>
										</select>
									</div>
									<div class="guest-form__row guest-form__row--two grid grid-cols-1 gap-4 sm:grid-cols-2">
										<div class="guest-form__field-group grid gap-1.5">
											<label class="guest-form__label text-sm font-semibold text-hp-text" for="primary_phone">Phone</label>
											<input type="text" name="primary_guest[phone]" id="primary_phone" placeholder="Phone number" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
										</div>
										<div class="guest-form__field-group grid gap-1.5">
											<label class="guest-form__label text-sm font-semibold text-hp-text" for="primary_email">Email</label>
											<input type="email" name="primary_guest[email]" id="primary_email" placeholder="Email address" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
										</div>
									</div>
								</div>
							</div>

							<div class="guest-form__section rounded-2xl border border-glass-border bg-hp-cream p-4 transition-colors duration-300 dark:bg-white/5">
								<div class="guest-form__section-header mb-2 flex items-center justify-between">
									<h4 class="guest-form__section-title m-0 text-base font-bold text-hp-text dark:text-[#c8e6c8]">Companions</h4>
									<span id="walkInCompanionCountBadge" class="text-xs text-hp-text-muted">0 companions</span>
								</div>
								<div id="companionList" class="guest-companion-list grid gap-2"></div>
								<div id="companionHiddenFields"></div>
							</div>

							<!-- Amenities Section with Mixed Times / Schedule Customizer -->
							<div class="guest-form__section rounded-2xl border border-glass-border bg-hp-cream p-4 transition-colors duration-300 dark:bg-white/5" id="amenitySection">
								<div class="guest-form__section-header mb-3 flex items-center justify-between">
									<div>
										<h4 class="guest-form__section-title m-0 text-base font-bold text-hp-text dark:text-[#c8e6c8]">Amenities</h4>
										<p class="m-0 text-xs text-hp-text-muted">Add available amenities and customize individual stay schedules</p>
									</div>
									<button type="button" class="guest-form__action-btn inline-flex cursor-pointer items-center rounded-lg border border-glass-border bg-glass px-3 py-1.5 text-xs font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" id="chooseAmenitiesBtn">
										<svg class="mr-1.5 h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
											<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
										</svg>
										Choose Amenities
									</button>
								</div>
								<div id="selectedAmenitiesContainer" class="grid gap-2.5">
									<p class="m-0 py-3 text-center text-xs text-hp-text-muted" id="noAmenitiesNotice">No amenities selected yet. Click "Choose Amenities" to add.</p>
								</div>
								<div id="amenitiesHiddenInputs"></div>
								<div class="guest-form__summary mt-3 flex justify-between rounded-lg border border-glass-border bg-glass px-4 py-3 text-sm font-semibold text-hp-text">
									<span>Amenities Subtotal</span>
									<strong class="text-hp-green" id="walkInAmenitiesSubtotal">₱0.00</strong>
								</div>
							</div>

							<!-- Overall Total Banner -->
							<div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-hp-green/30 bg-hp-green/10 p-4 dark:bg-hp-green/5">
								<div>
									<div class="text-xs font-bold uppercase tracking-wider text-hp-text-muted">Total Amount (Entrance + Amenities)</div>
									<div class="text-2xl font-extrabold text-hp-green" id="reservationTotal">₱0.00</div>
								</div>
								<div class="guest-form__actions flex flex-wrap gap-2">
									<button type="button" class="guest-form__button--secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" data-close-add-modal="true">Cancel</button>
									<button type="submit" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-6 py-2.5 text-sm font-bold text-white transition-colors duration-200 hover:bg-hp-green-dark">Check In Walk-In</button>
								</div>
							</div>
							<input type="hidden" name="total_amount" id="totalAmountInput" value="0">
						</form>
					</div>
				</div>

				<!-- Walk-In Range Calendar Modal -->
				<div class="guest-modal guest-modal--calendar" id="walkInCalendarModal" aria-hidden="true">
					<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-walkin-calendar="true"></div>
					<div class="guest-modal__content guest-modal__content--range relative z-[1] w-full max-w-[540px] max-h-[min(90vh,820px)] overflow-y-auto rounded-2xl bg-glass p-5 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="walkInCalendarModalTitle">
						<button type="button" class="guest-modal__close absolute right-3.5 top-3.5 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-walkin-calendar="true" aria-label="Close calendar">&times;</button>
						<div class="guest-modal__header mb-3 flex flex-wrap items-center justify-between gap-3 border-b border-[rgba(13,44,29,0.1)] pb-3 dark:border-white/10">
							<div>
								<h3 id="walkInCalendarModalTitle" class="guest-modal__title m-0 font-display text-lg text-hp-text">Select Stay Duration</h3>
								<p class="m-0 text-xs text-hp-text-muted">Check-in is locked to <strong>Today</strong>. Select your Check-Out Date and Session.</p>
							</div>
							<span class="edit-calendar__modal-date whitespace-nowrap rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-[0.8rem] font-bold text-emerald-600 dark:text-emerald-400" id="walkInCalCurrentBadge">Walk-In</span>
						</div>

						<!-- Check-in info & Check-out Session Selector -->
						<div class="mb-3.5 grid grid-cols-1 gap-2.5 sm:grid-cols-2 rounded-xl border border-glass-border bg-glass p-3 dark:border-white/10 dark:bg-white/5">
							<div class="grid gap-1">
								<span class="text-[0.72rem] font-bold uppercase tracking-[0.04em] text-hp-text-muted">Check-In (Now / Fixed)</span>
								<div class="flex items-center gap-2 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 text-xs font-bold text-emerald-700 dark:text-emerald-300" id="walkInFixedStartBanner">
									<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
										<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
									</svg>
									<span>Today • <span id="walkInFixedStartSessionText">{{ ($currentPeriod ?? '') === 'nighttime' ? 'Nighttime' : 'Daytime' }}</span></span>
								</div>
							</div>
							<div class="grid gap-1">
								<span class="text-[0.72rem] font-bold uppercase tracking-[0.04em] text-hp-text-muted">Check-Out Session</span>
								<div class="flex gap-1.5" id="walkInEndSlotGroup">
									<button type="button" class="session-pill-btn flex-1 rounded-lg border border-glass-border bg-glass py-1.5 text-xs font-bold text-hp-text transition-all duration-150 data-[active=true]:border-hp-green data-[active=true]:bg-hp-green data-[active=true]:text-white" data-slot-type="end" data-slot-val="Daytime" data-active="true">Daytime</button>
									<button type="button" class="session-pill-btn flex-1 rounded-lg border border-glass-border bg-glass py-1.5 text-xs font-bold text-hp-text transition-all duration-150 data-[active=true]:border-hp-green data-[active=true]:bg-hp-green data-[active=true]:text-white" data-slot-type="end" data-slot-val="Nighttime">Nighttime</button>
								</div>
							</div>
						</div>

						<!-- Calendar Component -->
						<div class="edit-calendar edit-calendar--modal rounded-[0.85rem] border border-glass-border bg-hp-cream p-4 transition-colors duration-300 dark:bg-white/5 dark:border-white/10">
							<div class="edit-calendar__head mb-2 flex items-center justify-between gap-2">
								<button type="button" class="edit-calendar__nav inline-flex h-[2rem] w-[2rem] cursor-pointer items-center justify-center rounded-[0.55rem] border border-glass-border bg-glass text-lg leading-none text-hp-text transition-all duration-200 hover:border-hp-green hover:bg-hp-green/10 dark:border-white/12 dark:bg-white/6 dark:text-[#c8e6c8]" id="walkInCalPrev" aria-label="Previous month">&lsaquo;</button>
								<div class="edit-calendar__title-wrap flex min-w-0 items-baseline gap-2">
									<div class="edit-calendar__title text-[0.95rem] font-bold capitalize text-hp-text dark:text-[#c8e6c8]" id="walkInCalTitle">&mdash;</div>
									<select class="edit-calendar__year cursor-pointer rounded-[0.45rem] border border-glass-border bg-glass px-2.5 py-1 text-[0.85rem] font-bold text-hp-text transition-all duration-200 hover:border-hp-green focus:border-hp-green focus:outline-none dark:border-white/12 dark:bg-white/6 dark:text-[#c8e6c8]" id="walkInCalYear" aria-label="Select year"></select>
								</div>
								<button type="button" class="edit-calendar__nav inline-flex h-[2rem] w-[2rem] cursor-pointer items-center justify-center rounded-[0.55rem] border border-glass-border bg-glass text-lg leading-none text-hp-text transition-all duration-200 hover:border-hp-green hover:bg-hp-green/10 dark:border-white/12 dark:bg-white/6 dark:text-[#c8e6c8]" id="walkInCalNext" aria-label="Next month">&rsaquo;</button>
							</div>

							<div class="edit-calendar__weekdays mt-2 grid grid-cols-7 gap-1">
								<span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Su</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Mo</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Tu</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">We</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Th</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Fr</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Sa</span>
							</div>

							<div class="edit-calendar__grid relative mt-1 grid min-h-[220px] grid-cols-7 gap-1 transition-opacity duration-250" id="walkInCalGrid"></div>

							<div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-glass-border pt-2 text-[0.72rem] text-hp-text-muted dark:border-white/10">
								<div class="flex flex-wrap items-center gap-2">
									<span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-hp-green"></span> Selected Stay</span>
									<span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-[rgba(13,44,29,0.2)] dark:bg-white/20"></span> Available</span>
								</div>
								<span id="walkInCalStepHelp" class="font-semibold text-hp-green dark:text-[#81c784]">Click any date to set Check-Out</span>
							</div>
						</div>

						<!-- Modal Footer: Summary & Apply button -->
						<div class="edit-calendar__footer mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-glass-border pt-3 dark:border-white/10">
							<div class="min-w-0">
								<div class="text-xs font-bold text-hp-text dark:text-[#c8e6c8]" id="walkInCalSummaryText">Select checkout date</div>
								<div class="text-[0.72rem] text-hp-text-muted" id="walkInCalSpanText">1 Day Stay</div>
							</div>
							<div class="flex gap-2">
								<button type="button" class="guest-form__secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-3.5 py-2 text-xs font-semibold text-hp-text hover:bg-glass-hover" data-close-walkin-calendar="true">Cancel</button>
								<button type="button" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-4 py-2 text-xs font-bold text-white transition-colors duration-150 hover:bg-hp-green-dark" id="walkInCalApplyBtn">Apply Stay Schedule</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Walk-In Per-Amenity Schedule Customizer Modal -->
				<div class="guest-modal guest-modal--compact" id="walkInAmenityScheduleModal" aria-hidden="true">
					<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-walkin-amenity-schedule="true"></div>
					<div class="guest-modal__content guest-modal__content--compact relative z-[1] w-full max-w-[500px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="walkInAmenityScheduleTitle">
						<button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-walkin-amenity-schedule="true" aria-label="Close schedule form">&times;</button>
						<h3 id="walkInAmenityScheduleTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Customize Amenity Stay</h3>
						<input type="hidden" id="walkInAmenityScheduleAmenityId" value="">
						
						<!-- Allowed Range Notice -->
						<div id="walkInAmenityScheduleAllowedRangeHint" class="mt-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs text-emerald-800 dark:text-emerald-300">
							<strong>Allowed Stay Window:</strong> <span id="walkInAmenityScheduleRangeText">—</span>
						</div>

						<div class="mt-4 grid gap-4">
							<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
								<div class="grid gap-1.5">
									<label class="text-xs font-bold uppercase tracking-wider text-hp-text-muted" for="walkInAmenityStartDate">Check-In Date</label>
									<input type="date" id="walkInAmenityStartDate" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</div>
								<div class="grid gap-1.5">
									<label class="text-xs font-bold uppercase tracking-wider text-hp-text-muted" for="walkInAmenityStartSlot">Check-In Session</label>
									<select id="walkInAmenityStartSlot" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
										<option value="Daytime">Daytime</option>
										<option value="Nighttime">Nighttime</option>
									</select>
								</div>
							</div>

							<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
								<div class="grid gap-1.5">
									<label class="text-xs font-bold uppercase tracking-wider text-hp-text-muted" for="walkInAmenityEndDate">Check-Out Date</label>
									<input type="date" id="walkInAmenityEndDate" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</div>
								<div class="grid gap-1.5">
									<label class="text-xs font-bold uppercase tracking-wider text-hp-text-muted" for="walkInAmenityEndSlot">Check-Out Session</label>
									<select id="walkInAmenityEndSlot" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
										<option value="Daytime">Daytime</option>
										<option value="Nighttime">Nighttime</option>
									</select>
								</div>
							</div>

							<div id="walkInAmenityAirconWrap" class="flex items-center gap-2 rounded-xl border border-glass-border bg-glass p-3">
								<input type="checkbox" id="walkInAmenityAirconToggle" class="h-4 w-4 accent-hp-green">
								<label for="walkInAmenityAirconToggle" class="cursor-pointer text-sm font-semibold text-hp-text">
									Air-Conditioned Option <small id="walkInAmenityAirconDiff" class="text-xs text-hp-text-muted"></small>
								</label>
							</div>

							<div class="rounded-xl border border-glass-border bg-glass p-3.5 text-xs text-hp-text-muted">
								<div class="flex justify-between font-bold text-hp-text dark:text-[#c8e6c8]">
									<span>Duration:</span>
									<span id="walkInAmenityDurationText">1 Day (1D 0N)</span>
								</div>
								<div class="mt-1 flex justify-between">
									<span>Price Calculation:</span>
									<span id="walkInAmenityMathText">₱0.00</span>
								</div>
								<div class="mt-1 flex justify-between border-t border-glass-border/40 pt-1 font-bold text-hp-green">
									<span>Amenity Total:</span>
									<span id="walkInAmenityTotalPrice">₱0.00</span>
								</div>
							</div>
						</div>

						<div class="guest-form__actions mt-5 flex justify-end gap-2">
							<button type="button" class="guest-form__secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2 text-xs font-semibold text-hp-text hover:bg-glass-hover" data-close-walkin-amenity-schedule="true">Cancel</button>
							<button type="button" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2 text-xs font-bold text-white transition-colors duration-150 hover:bg-hp-green-dark" id="walkInAmenitySaveScheduleBtn">Save Amenity Stay</button>
						</div>
					</div>
				</div>

				<!-- Choose Amenities Modal -->
				<div class="guest-modal guest-modal--compact" id="amenityModal" aria-hidden="true">
					<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-amenity-modal="true"></div>
					<div class="guest-modal__content guest-modal__content--wide relative z-[1] w-full max-w-[680px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="amenityModalTitle">
						<button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-amenity-modal="true" aria-label="Close amenity selection">&times;</button>
						<div class="guest-modal__header mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-[rgba(13,44,29,0.1)] pb-3 dark:border-white/10">
							<div>
								<h3 id="amenityModalTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Choose Available Amenities</h3>
								<p class="m-0 text-xs text-hp-text-muted">Showing amenities available for the selected walk-in stay window</p>
							</div>
							<div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-700 dark:text-emerald-300" id="amenityModalStayBadge">
								Today Stay
							</div>
						</div>
						<div class="guest-form__amenities grid gap-3" id="amenitiesContainer">
							<div class="flex items-center justify-center py-8 text-sm text-hp-text-muted">
								<svg class="mr-2 h-5 w-5 animate-spin text-hp-green" fill="none" viewBox="0 0 24 24">
									<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
									<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
								</svg>
								Checking amenity availability...
							</div>
						</div>
						<div class="mt-5 flex justify-end">
							<button type="button" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2 text-xs font-bold text-white transition-colors duration-150 hover:bg-hp-green-dark" data-close-amenity-modal="true">Done</button>
						</div>
					</div>
				</div>

				<div class="guest-modal guest-modal--wide" id="companionModal" aria-hidden="true">
					<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-companion-modal="true"></div>
					<div class="guest-modal__content guest-modal__content--wide relative z-[1] w-full max-w-[900px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="companionModalTitle">
						<button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-companion-modal="true" aria-label="Close companion form">&times;</button>
						<div class="guest-modal__header mb-4 flex items-center gap-3">
							<h3 id="companionModalTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Add Companion</h3>
						</div>
						<div class="guest-form__tabs mb-4 flex gap-2 rounded-xl border border-glass-border bg-glass p-1.5">
							<button type="button" class="guest-form__tab guest-form__tab--active flex-1 cursor-pointer rounded-lg border-0 bg-hp-green px-4 py-2.5 text-sm font-bold text-white transition-all duration-200" data-companion-tab="single">Single Companion</button>
							<button type="button" class="guest-form__tab flex-1 cursor-pointer rounded-lg border-0 bg-transparent px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover" data-companion-tab="bulk">Bulk Companions</button>
						</div>

						<!-- Single Companion Form -->
						<form id="companionForm" class="guest-form guest-form--tab-content guest-form--tab-content--active grid gap-4" data-companion-content="single">
							<div class="guest-form__grid grid grid-cols-1 gap-4 sm:grid-cols-3">
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="companion_first_name">First name</label>
									<input type="text" name="first_name" id="companion_first_name" placeholder="Enter first name" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="companion_middle_name">Middle name</label>
									<input type="text" name="middle_name" id="companion_middle_name" placeholder="Enter middle name" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="companion_last_name">Last name</label>
									<input type="text" name="last_name" id="companion_last_name" placeholder="Enter last name" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<div class="flex items-center justify-between">
										<label class="guest-form__label text-sm font-semibold text-hp-text" for="companion_age">Age</label>
										<span id="companionAgeComputedBadge" class="rounded-md border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[0.7rem] font-bold text-emerald-700 dark:text-emerald-300">Adult Rate</span>
									</div>
									<input type="number" name="age" id="companion_age" min="0" placeholder="Age in years" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
									<input type="hidden" name="age_type" id="companion_age_type" value="adult">
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="companion_gender">Gender</label>
									<select name="gender" id="companion_gender" class="guest-form__select w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
										<option value="">Select gender</option>
										<option value="Male">Male</option>
										<option value="Female">Female</option>
									</select>
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="companionIsForeigner">Nationality</label>
									<select name="is_foreigner" id="companionIsForeigner" class="guest-form__select w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
										<option value="0" selected>Filipino</option>
										<option value="1">Foreigner</option>
									</select>
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="companion_phone">Phone</label>
									<input type="text" name="phone" id="companion_phone" placeholder="Phone number" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="companion_email">Email</label>
									<input type="email" name="email" id="companion_email" placeholder="Email address" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</div>
							</div>
							<div class="guest-form__actions flex flex-wrap justify-end gap-3">
								<button type="button" class="guest-form__button--secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" data-close-companion-modal="true">Cancel</button>
								<button type="submit" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark">Add Companion</button>
							</div>
						</form>

						<!-- Bulk Companion Form -->
						<form id="bulkCompanionForm" class="guest-form guest-form--tab-content gap-4" data-companion-content="bulk" style="display: none;">
							<div class="guest-form__grid grid grid-cols-1 gap-4 sm:grid-cols-2">
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="bulk_companion_gender">Gender</label>
									<select name="gender" id="bulk_companion_gender" class="guest-form__select w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
										<option value="">Select gender</option>
										<option value="Male">Male</option>
										<option value="Female">Female</option>
									</select>
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="bulk_companion_age_group">Age Group</label>
									<select name="age_group" id="bulk_companion_age_group" class="guest-form__select w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
										<option value="0-12">Kids (0-12)</option>
										<option value="13-17">Teens (13-17)</option>
										<option value="18-59">Adults (18-59)</option>
										<option value="60+">Seniors (60+)</option>
									</select>
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="bulk_companion_is_foreigner">Nationality</label>
									<select name="is_foreigner" id="bulk_companion_is_foreigner" class="guest-form__select w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
										<option value="0" selected>Filipino</option>
										<option value="1">Foreigner</option>
									</select>
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="bulk_companion_quantity">Quantity</label>
									<input type="number" name="quantity" id="bulk_companion_quantity" min="1" max="500" value="1" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</div>
							</div>
							<div class="guest-form__actions flex flex-wrap justify-end gap-3">
								<button type="button" class="guest-form__button--secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" data-close-companion-modal="true">Cancel</button>
								<button type="submit" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark">Add Bulk Companions</button>
							</div>
						</form>
					</div>
				</div>

				<!-- Payment & Review Confirmation Modal -->
				<div class="guest-modal" id="paymentConfirmModal" aria-hidden="true" style="z-index: 1050;">
					<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.65)]" data-close-payment-modal="true"></div>
					<div class="guest-modal__content relative z-[1] w-full max-w-[640px] max-h-[min(90vh,840px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-2xl dark:bg-[rgba(30,30,30,0.98)]" role="dialog" aria-modal="true" aria-labelledby="paymentConfirmTitle">
						<button type="button" class="guest-modal__close absolute right-4 top-4 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-payment-modal="true" aria-label="Close modal">&times;</button>
						
						<div class="guest-modal__header mb-4 flex items-center gap-3 border-b border-glass-border pb-3">
							<div class="flex h-11 w-11 items-center justify-center rounded-xl bg-hp-green/15 text-hp-green">
								<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
								</svg>
							</div>
							<div>
								<h3 id="paymentConfirmTitle" class="guest-modal__title m-0 font-display text-xl font-bold text-hp-text">Review & Payment Confirmation</h3>
								<p class="m-0 text-xs text-hp-text-muted">Review walk-in reservation details, stay schedule, and total cost before check-in</p>
							</div>
						</div>

						<div class="guest-modal__body grid gap-3.5">
							<!-- Expected Checkout Banner -->
							<div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3.5 text-xs text-emerald-900 dark:text-emerald-200">
								<div class="flex items-center gap-2 font-bold text-sm text-emerald-800 dark:text-emerald-300">
									<svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
										<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
									</svg>
									<span>Expected Check-Out</span>
								</div>
								<div class="mt-1 text-base font-extrabold text-hp-green-dark dark:text-emerald-300" id="payConfirmExpectedCheckoutText">
									—
								</div>
								<div class="mt-0.5 text-[0.75rem] text-hp-text-muted" id="payConfirmStayScheduleBreakdown">
									—
								</div>
							</div>

							<!-- Primary Guest & Party Details -->
							<div class="rounded-xl border border-glass-border bg-hp-cream/60 p-3.5 text-xs text-hp-text dark:bg-white/5 grid gap-2">
								<div class="flex justify-between items-center border-b border-glass-border/40 pb-2">
									<span class="text-hp-text-muted font-semibold">Primary Guest:</span>
									<strong id="payConfirmGuestName" class="font-bold text-hp-text text-sm">-</strong>
								</div>
								<div class="flex justify-between items-center">
									<span class="text-hp-text-muted font-semibold">Contact Info:</span>
									<span id="payConfirmContactInfo" class="font-medium">-</span>
								</div>
								<div class="flex justify-between items-center">
									<span class="text-hp-text-muted font-semibold">Guest Profile:</span>
									<span id="payConfirmDemographics" class="font-medium">-</span>
								</div>
								<div class="flex justify-between items-center border-t border-glass-border/40 pt-1.5">
									<span class="text-hp-text-muted font-semibold">Total Guests:</span>
									<span id="payConfirmPartyCount" class="font-bold text-hp-green">1 Guest</span>
								</div>
							</div>

							<!-- Entrance & Pool Fees Breakdown -->
							<div class="rounded-xl border border-glass-border bg-glass p-3.5 text-xs text-hp-text grid gap-2">
								<div class="font-bold text-hp-text-muted uppercase tracking-wider text-[0.7rem]">Entrance & Access Fees</div>
								<div class="flex justify-between items-center">
									<span class="text-hp-text-muted">Adult Entrance:</span>
									<span id="payConfirmAdultFee" class="font-medium">₱0.00</span>
								</div>
								<div class="flex justify-between items-center">
									<span class="text-hp-text-muted">Child Entrance:</span>
									<span id="payConfirmChildFee" class="font-medium">₱0.00</span>
								</div>
								<div class="flex justify-between items-center">
									<span class="text-hp-text-muted">Pool Access:</span>
									<span id="payConfirmPoolFee" class="font-medium">₱0.00</span>
								</div>
								<div class="flex justify-between items-center border-t border-glass-border/40 pt-1.5 font-semibold">
									<span>Entrance Subtotal:</span>
									<span id="payConfirmEntranceTotal" class="text-hp-green font-bold">₱0.00</span>
								</div>
							</div>

							<!-- Selected Amenities Breakdown -->
							<div class="rounded-xl border border-glass-border bg-glass p-3.5 text-xs text-hp-text grid gap-2">
								<div class="flex justify-between items-center">
									<span class="font-bold text-hp-text-muted uppercase tracking-wider text-[0.7rem]">Selected Amenities</span>
									<span id="payConfirmAmenitiesSubtotal" class="font-bold text-hp-green">₱0.00</span>
								</div>
								<div id="payConfirmAmenitiesList" class="grid gap-1.5 text-xs text-hp-text-muted">
									<p class="m-0 text-hp-text-muted italic">No amenities selected</p>
								</div>
							</div>

							<!-- Big Payment Box -->
							<div class="rounded-xl border border-hp-green/30 bg-hp-green/10 p-4 text-center">
								<span class="block text-xs font-bold uppercase tracking-wider text-hp-green">Total Amount to Pay</span>
								<strong id="payConfirmGrandTotal" class="block font-display text-3xl font-extrabold text-hp-green-dark dark:text-[#4ade80] mt-1">₱0.00</strong>
								<span class="mt-1.5 inline-block rounded-full bg-hp-green px-3.5 py-1 text-[0.72rem] font-bold uppercase tracking-wider text-white">Full Payment at Counter (Paid)</span>
							</div>
						</div>

						<div class="guest-form__actions mt-5 flex flex-wrap justify-end gap-3 border-t border-glass-border pt-4">
							<button type="button" class="guest-form__button--secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" id="cancelPaymentBtn" data-close-payment-modal="true">Back / Edit</button>
							<button type="button" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-6 py-2.5 text-sm font-bold text-white transition-colors duration-200 hover:bg-hp-green-dark shadow-lg shadow-hp-green/20" id="confirmPaymentBtn">Confirm Payment & Check In</button>
						</div>
					</div>
				</div>

				{{-- Check In Modal (used when scanning a reservation) --}}
				<div class="guest-modal guest-modal--add" id="checkInModal" aria-hidden="true">
					<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-check-in-modal="true"></div>
					<div class="guest-modal__content guest-modal__content--wide relative z-[1] w-full max-w-[900px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="checkInModalTitle">
						<button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-check-in-modal="true" aria-label="Close check-in form">&times;</button>
						<h3 id="checkInModalTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Check In Reservation</h3>
						<form id="checkInForm" class="guest-form mt-6 grid gap-4" action="#">
							<div class="guest-form__group grid gap-2">
								<label class="guest-form__label text-sm font-semibold text-hp-text">Guest mode</label>
								<div class="guest-form__chips flex flex-wrap gap-2">
									<label class="guest-form__chip flex cursor-pointer items-center gap-2 rounded-full border border-glass-border bg-glass px-4 py-2 text-sm font-semibold text-hp-text transition-all duration-200 has-[:checked]:border-hp-green has-[:checked]:bg-hp-green has-[:checked]:text-white">
										<input type="radio" name="check_in_guest_mode" value="with_primary" checked class="sr-only">
										<span>With primary guest</span>
									</label>
									<label class="guest-form__chip flex cursor-pointer items-center gap-2 rounded-full border border-glass-border bg-glass px-4 py-2 text-sm font-semibold text-hp-text transition-all duration-200 has-[:checked]:border-hp-green has-[:checked]:bg-hp-green has-[:checked]:text-white">
										<input type="radio" name="check_in_guest_mode" value="visitors_only" class="sr-only">
										<span>Visitors only</span>
									</label>
								</div>
							</div>

							<div id="checkInPrimaryGuestSection" class="guest-form__section grid gap-3 rounded-2xl border border-glass-border bg-hp-cream p-4 transition-colors duration-300 dark:bg-white/5">
								<div class="guest-form__section-header mb-1">
									<h4 class="guest-form__section-title m-0 text-base font-bold text-hp-text dark:text-[#c8e6c8]">Primary guest</h4>
								</div>
								<div class="guest-form__row guest-form__row--three grid grid-cols-1 gap-4 sm:grid-cols-3">
									<label class="guest-form__field grid gap-1.5">
										<span class="text-sm font-semibold text-hp-text">First name</span>
										<input type="text" name="check_in_primary_guest[first_name]" placeholder="First name" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
									</label>
									<label class="guest-form__field grid gap-1.5">
										<span class="text-sm font-semibold text-hp-text">Middle name</span>
										<input type="text" name="check_in_primary_guest[middle_name]" placeholder="Middle name" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
									</label>
									<label class="guest-form__field grid gap-1.5">
										<span class="text-sm font-semibold text-hp-text">Last name</span>
										<input type="text" name="check_in_primary_guest[last_name]" placeholder="Last name" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
									</label>
								</div>
								<div class="guest-form__row guest-form__row--three grid grid-cols-1 gap-4 sm:grid-cols-3">
									<label class="guest-form__field grid gap-1.5">
										<span class="text-sm font-semibold text-hp-text">Age</span>
										<input type="number" name="check_in_primary_guest[age]" min="0" placeholder="Age" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
									</label>
									<label class="guest-form__field grid gap-1.5">
										<span class="text-sm font-semibold text-hp-text">Gender</span>
										<select name="check_in_primary_guest[gender]" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
											<option value="">Select gender</option>
											<option value="Male">Male</option>
											<option value="Female">Female</option>
										</select>
									</label>
									<label class="guest-form__field grid gap-1.5">
										<span class="text-sm font-semibold text-hp-text">Nationality</span>
										<select name="check_in_primary_guest[is_foreigner]" id="checkInPrimaryIsForeigner" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
											<option value="0" selected>Filipino</option>
											<option value="1">Foreigner</option>
										</select>
									</label>
								</div>
								<div class="guest-form__row guest-form__row--two grid grid-cols-1 gap-4 sm:grid-cols-2">
									<label class="guest-form__field grid gap-1.5">
										<span class="text-sm font-semibold text-hp-text">Phone</span>
										<input type="text" name="check_in_primary_guest[phone]" placeholder="Phone number" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
									</label>
									<label class="guest-form__field grid gap-1.5">
										<span class="text-sm font-semibold text-hp-text">Email</span>
										<input type="email" name="check_in_primary_guest[email]" placeholder="Email address" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
									</label>
								</div>
							</div>
							<div class="guest-form__section grid gap-3 rounded-2xl border border-glass-border bg-hp-cream p-4 transition-colors duration-300 dark:bg-white/5">
								<div class="guest-form__section-header mb-1 flex items-center justify-between gap-2">
									<h4 class="guest-form__section-title m-0 text-base font-bold text-hp-text dark:text-[#c8e6c8]">Companions</h4>
									<div class="flex gap-2">
										<button type="button" class="guest-form__secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" id="checkInAddCompanionBtn">+ Add Single</button>
										<button type="button" class="guest-form__secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" id="checkInBulkCompanionBtn">+ Add Bulk</button>
									</div>
								</div>
								<div id="checkInCompanionList" class="guest-companion-list grid gap-2"></div>
								<div id="checkInCompanionHiddenFields"></div>
							</div>

							<div class="guest-form__actions flex flex-wrap justify-end gap-3">
								<button type="button" class="guest-form__secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" data-close-check-in-modal="true">Cancel</button>
								<button type="submit" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark">Check In</button>
							</div>
						</form>
					</div>
				</div>

				{{-- Check Out Confirmation Modal --}}
				<div class="guest-modal" id="checkOutConfirmModal" aria-hidden="true" style="z-index: 1100;">
					<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-check-out-confirm="true"></div>
					<div class="guest-modal__content guest-modal__content--compact relative z-[1] w-full max-w-[500px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="checkOutConfirmTitle">
						<button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-check-out-confirm="true" aria-label="Close confirmation">&times;</button>
						<h3 id="checkOutConfirmTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Confirm Check Out</h3>
						<p class="mb-6 text-[#666]">Are you sure you want to check out this reservation? This action cannot be undone.</p>
						<div class="guest-form__actions flex flex-wrap justify-end gap-3">
							<button type="button" class="guest-form__button--secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" data-close-check-out-confirm="true">Cancel</button>
							<button type="button" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark" id="confirmCheckOutBtn">Yes, Check Out</button>
						</div>
					</div>
				</div>

				{{-- Reservation Detail Modal --}}
				<div class="guest-modal" id="reservationModal" aria-hidden="true">
					<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-reservation-modal="true"></div>
					<div class="guest-modal__content relative z-[1] w-full max-w-[720px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="reservationModalTitle">
						<button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-reservation-modal="true" aria-label="Close details">&times;</button>
						<div class="guest-modal__header mb-4 flex items-center gap-3 border-b border-[rgba(13,44,29,0.1)] pb-4 dark:border-white/10">
							<h3 id="reservationModalTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Reservation Details</h3>
							<span id="reservationModalStatus" class="guest-modal__role-badge inline-flex items-center rounded-full px-3 py-1.5 text-[0.78rem] font-bold uppercase tracking-[0.04em]"></span>
							<button type="button" class="guest-form__button--secondary guest-form__button--small ml-auto hidden cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" id="reservationAddCompanionBtn">Add Companion</button>
							<button type="button" class="guest-form__button guest-form__button--small cursor-pointer rounded-xl border-0 bg-hp-green px-4 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark" id="reservationCheckOutBtn">Check Out</button>
						</div>
						<div id="reservationModalBody" class="guest-modal__body grid gap-4"></div>
						<div class="guest-form__actions mt-6 flex flex-wrap justify-end gap-3" id="reservationModalActions">
							<button type="button" class="guest-form__button--secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" data-close-reservation-modal="true">Close</button>
						</div>
					</div>
				</div>

				{{-- Add Companion to Active Reservation Modal --}}
				<div class="guest-modal guest-modal--wide" id="reservationAddCompanionModal" aria-hidden="true">
					<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-reservation-add-companion="true"></div>
					<div class="guest-modal__content guest-modal__content--wide relative z-[1] w-full max-w-[900px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="reservationAddCompanionTitle">
						<button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-reservation-add-companion="true" aria-label="Close companion form">&times;</button>
						<div class="guest-modal__header mb-4 flex items-center gap-3">
							<h3 id="reservationAddCompanionTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Add Companion</h3>
							<span id="reservationAddCompanionFor" class="guest-modal__role-badge inline-flex items-center rounded-full bg-hp-green/10 px-3 py-1.5 text-[0.78rem] font-bold uppercase tracking-[0.04em] text-hp-green"></span>
						</div>
						<div class="guest-form__tabs mb-4 flex gap-2 rounded-xl border border-glass-border bg-glass p-1.5">
							<button type="button" class="guest-form__tab guest-form__tab--active flex-1 cursor-pointer rounded-lg border-0 bg-hp-green px-4 py-2.5 text-sm font-bold text-white transition-all duration-200" data-res-add-tab="single">Single Companion</button>
							<button type="button" class="guest-form__tab flex-1 cursor-pointer rounded-lg border-0 bg-transparent px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover" data-res-add-tab="bulk">Bulk Companions</button>
						</div>

						<!-- Single Companion Form -->
						<form id="reservationAddSingleForm" class="guest-form guest-form--tab-content guest-form--tab-content--active grid gap-4" data-res-add-content="single">
							<div class="guest-form__grid grid grid-cols-1 gap-4 sm:grid-cols-3">
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="resadd_first_name">First name</label>
									<input type="text" name="first_name" id="resadd_first_name" placeholder="Enter first name" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="resadd_middle_name">Middle name</label>
									<input type="text" name="middle_name" id="resadd_middle_name" placeholder="Enter middle name" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="resadd_last_name">Last name</label>
									<input type="text" name="last_name" id="resadd_last_name" placeholder="Enter last name" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="resadd_age">Age</label>
									<input type="number" name="age" id="resadd_age" min="0" placeholder="Age" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="resadd_gender">Gender</label>
									<select name="gender" id="resadd_gender" class="guest-form__select w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
										<option value="">Select gender</option>
										<option value="Male">Male</option>
										<option value="Female">Female</option>
									</select>
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="resadd_is_foreigner">Nationality</label>
									<select name="is_foreigner" id="resadd_is_foreigner" class="guest-form__select w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
										<option value="0" selected>Filipino</option>
										<option value="1">Foreigner</option>
									</select>
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="resadd_phone">Phone</label>
									<input type="text" name="phone" id="resadd_phone" placeholder="Phone number" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="resadd_email">Email</label>
									<input type="email" name="email" id="resadd_email" placeholder="Email address" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</div>
							</div>
							<div class="guest-form__grid grid grid-cols-1 gap-4 sm:grid-cols-3">
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__checkbox-wrapper flex cursor-pointer items-center gap-2 text-sm text-hp-text">
										<input type="checkbox" name="pool_access" id="resadd_pool_access" class="h-4 w-4 accent-hp-green">
										<span>Include Pool Access</span>
									</label>
								</div>
							</div>
							<div class="guest-form__field-group grid gap-1.5">
								<label class="guest-form__label text-sm font-semibold text-hp-text">Fees (auto-computed)</label>
								<div class="guest-form__fees-list flex flex-col gap-1.5 rounded-lg border border-glass-border bg-glass p-3 text-sm text-hp-text-muted">
									<div class="guest-form__fee-item flex justify-between">
										<span>Adult Entrance (<span id="resaddAdultCount">0</span>):</span>
										<strong class="text-hp-text" id="resaddAdultFee">₱0.00</strong>
									</div>
									<div class="guest-form__fee-item flex justify-between">
										<span>Child Entrance (<span id="resaddChildCount">0</span>):</span>
										<strong class="text-hp-text" id="resaddChildFee">₱0.00</strong>
									</div>
									<div class="guest-form__fee-item flex justify-between">
										<span>Pool Fee (<span id="resaddPoolCount">0</span>):</span>
										<strong class="text-hp-text" id="resaddPoolFee">₱0.00</strong>
									</div>
									<div class="guest-form__fee-item guest-form__fee-item--total flex justify-between border-t border-glass-border pt-2">
										<span>Total:</span>
										<strong class="text-hp-green" id="resaddTotalFee">₱0.00</strong>
									</div>
								</div>
							</div>
							<div class="guest-form__actions flex flex-wrap justify-end gap-3">
								<button type="button" class="guest-form__button--secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" data-close-reservation-add-companion="true">Cancel</button>
								<button type="submit" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark">Add Companion</button>
							</div>
						</form>

						<!-- Bulk Companion Form -->
						<form id="reservationAddBulkForm" class="guest-form guest-form--tab-content gap-4" data-res-add-content="bulk" style="display: none;">
							<div class="guest-form__grid grid grid-cols-1 gap-4 sm:grid-cols-2">
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="resadd_bulk_gender">Gender</label>
									<select name="gender" id="resadd_bulk_gender" class="guest-form__select w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
										<option value="">Select gender</option>
										<option value="Male">Male</option>
										<option value="Female">Female</option>
									</select>
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="resadd_bulk_age_group">Age Group</label>
									<select name="age_group" id="resadd_bulk_age_group" class="guest-form__select w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
										<option value="0-12">Kids (0-12)</option>
										<option value="13-17">Teens (13-17)</option>
										<option value="18-59">Adults (18-59)</option>
										<option value="60+">Seniors (60+)</option>
									</select>
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="resadd_bulk_is_foreigner">Nationality</label>
									<select name="is_foreigner" id="resadd_bulk_is_foreigner" class="guest-form__select w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
										<option value="0" selected>Filipino</option>
										<option value="1">Foreigner</option>
									</select>
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__label text-sm font-semibold text-hp-text" for="resadd_bulk_quantity">Quantity</label>
									<input type="number" name="quantity" id="resadd_bulk_quantity" min="1" max="500" value="1" class="guest-form__input w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</div>
								<div class="guest-form__field-group grid gap-1.5">
									<label class="guest-form__checkbox-wrapper flex cursor-pointer items-center gap-2 text-sm text-hp-text">
										<input type="checkbox" name="pool_access" id="resadd_bulk_pool_access" class="h-4 w-4 accent-hp-green">
										<span id="resaddBulkPoolLabel">Include Pool Access (all 1)</span>
									</label>
								</div>
							</div>
							<div class="guest-form__field-group grid gap-1.5">
								<label class="guest-form__label text-sm font-semibold text-hp-text">Fees (auto-computed)</label>
								<div class="guest-form__fees-list flex flex-col gap-1.5 rounded-lg border border-glass-border bg-glass p-3 text-sm text-hp-text-muted">
									<div class="guest-form__fee-item flex justify-between">
										<span>Adult Entrance (<span id="resaddBulkAdultCount">0</span>):</span>
										<strong class="text-hp-text" id="resaddBulkAdultFee">₱0.00</strong>
									</div>
									<div class="guest-form__fee-item flex justify-between">
										<span>Child Entrance (<span id="resaddBulkChildCount">0</span>):</span>
										<strong class="text-hp-text" id="resaddBulkChildFee">₱0.00</strong>
									</div>
									<div class="guest-form__fee-item flex justify-between">
										<span>Pool Fee (<span id="resaddBulkPoolCount">0</span>):</span>
										<strong class="text-hp-text" id="resaddBulkPoolFee">₱0.00</strong>
									</div>
									<div class="guest-form__fee-item guest-form__fee-item--total flex justify-between border-t border-glass-border pt-2">
										<span>Total:</span>
										<strong class="text-hp-green" id="resaddBulkTotalFee">₱0.00</strong>
									</div>
								</div>
							</div>
							<div class="guest-form__actions flex flex-wrap justify-end gap-3">
								<button type="button" class="guest-form__button--secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" data-close-reservation-add-companion="true">Cancel</button>
								<button type="submit" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark">Add Bulk Companions</button>
							</div>
						</form>
					</div>
				</div>

				{{-- Scan QR Modal --}}
				<div class="guest-modal guest-modal--add" id="scanQrModal" aria-hidden="true">
					<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-scan-modal="true"></div>
					<div class="guest-modal__content guest-modal__content--wide relative z-[1] flex w-full max-w-[900px] max-h-[min(84vh,760px)] flex-row overflow-y-auto rounded-2xl bg-hp-cream p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="scanQrModalTitle">
						<button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-scan-modal="true" aria-label="Close QR scanner">&times;</button>
						<div class="flex flex-1 flex-col justify-center p-6">
							<h3 id="scanQrModalTitle" class="guest-modal__title m-0 mb-6 font-display text-xl text-hp-text">Scan Reservation QR</h3>
							<p class="scan-modal__hint mb-6 text-sm leading-relaxed text-hp-text">Allow camera access and hold the reservation QR code in front of the lens.</p>
							<label class="guest-form__field mb-4 grid gap-1.5">
								<span class="mb-1 block text-sm font-semibold text-hp-text">Camera</span>
								<select id="qrCameraSelect" class="w-full rounded-xl border border-hp-green-dark bg-white px-3.5 py-3 text-black"></select>
							</label>
							<div class="scan-modal__status mb-6 rounded-lg bg-[rgba(26,58,31,0.1)] px-3 py-2 text-sm font-semibold text-hp-green" id="qrScannerStatus">Ready to scan</div>
							<div class="guest-form__actions mt-auto flex flex-col gap-3">
								<button type="button" class="guest-form__button cursor-pointer rounded-lg border-0 bg-hp-green-dark px-4 py-3 font-medium text-white" id="stopQrBtn">Stop Scanner</button>
							</div>
						</div>
						<div class="flex flex-1 items-center justify-center bg-black/5 p-6 dark:bg-black/20">
							<div id="qrScanner" class="scan-modal__scanner h-[300px] w-full max-w-[400px] overflow-hidden rounded-xl bg-black"></div>
						</div>
					</div>
				</div>

				{{-- Companion modal used by check-in flow --}}
				<div class="guest-modal guest-modal--compact" id="checkInCompanionModal" aria-hidden="true">
					<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-check-in-companion-modal="true"></div>
					<div class="guest-modal__content guest-modal__content--compact relative z-[1] w-full max-w-[500px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="checkInCompanionModalTitle">
						<button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-check-in-companion-modal="true" aria-label="Close companion form">&times;</button>
						<h3 id="checkInCompanionModalTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Add Companion</h3>
						<form id="checkInCompanionForm" class="guest-form mt-6 grid gap-4" action="#">
							<div class="guest-form__row guest-form__row--three grid grid-cols-1 gap-4 sm:grid-cols-3">
								<label class="guest-form__field grid gap-1.5">
									<span class="text-sm font-semibold text-hp-text">First name</span>
									<input type="text" name="first_name" placeholder="First name" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</label>
								<label class="guest-form__field grid gap-1.5">
									<span class="text-sm font-semibold text-hp-text">Middle name</span>
									<input type="text" name="middle_name" placeholder="Middle name" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</label>
								<label class="guest-form__field grid gap-1.5">
									<span class="text-sm font-semibold text-hp-text">Last name</span>
									<input type="text" name="last_name" placeholder="Last name" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</label>
							</div>
							<div class="guest-form__row guest-form__row--three grid grid-cols-1 gap-4 sm:grid-cols-3">
								<label class="guest-form__field grid gap-1.5">
									<span class="text-sm font-semibold text-hp-text">Age</span>
									<input type="number" name="age" min="0" placeholder="Age" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</label>
								<label class="guest-form__field grid gap-1.5">
									<span class="text-sm font-semibold text-hp-text">Gender</span>
									<select name="gender" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
										<option value="">Select gender</option>
										<option value="Male">Male</option>
										<option value="Female">Female</option>
									</select>
								</label>
								<label class="guest-form__field grid gap-1.5">
									<span class="text-sm font-semibold text-hp-text">Nationality</span>
									<select name="is_foreigner" id="checkInCompanionIsForeigner" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
										<option value="0" selected>Filipino</option>
										<option value="1">Foreigner</option>
									</select>
								</label>
							</div>
							<div class="guest-form__row guest-form__row--two grid grid-cols-1 gap-4 sm:grid-cols-2">
								<label class="guest-form__field grid gap-1.5">
									<span class="text-sm font-semibold text-hp-text">Phone</span>
									<input type="text" name="phone" placeholder="Phone number" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</label>
								<label class="guest-form__field grid gap-1.5">
									<span class="text-sm font-semibold text-hp-text">Email</span>
									<input type="email" name="email" placeholder="Email address" class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
								</label>
							</div>
							<div class="guest-form__actions flex flex-wrap justify-end gap-3">
								<button type="button" class="guest-form__secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" data-close-check-in-companion-modal="true">Cancel</button>
								<button type="submit" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark">Add Companion</button>
							</div>
						</form>
					</div>
				</div>

				{{-- Check In Confirmation Modal --}}
				<div class="guest-modal guest-modal--compact" id="checkInConfirmationModal" aria-hidden="true">
					<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-check-in-confirmation="true"></div>
					<div class="guest-modal__content guest-modal__content--compact relative z-[1] w-full max-w-[500px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="checkInConfirmationTitle">
						<button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-check-in-confirmation="true" aria-label="Close confirmation">&times;</button>
						<h3 id="checkInConfirmationTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Check In Reservation</h3>
						<div id="checkInConfirmationBody" class="guest-modal__body mt-6 grid gap-4"></div>
						<div class="guest-form__actions mt-6 flex flex-wrap justify-end gap-3">
							<button type="button" class="guest-form__secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" data-close-check-in-confirmation="true">Cancel</button>
							<button type="button" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark" id="confirmCheckInBtn">Yes, Check In</button>
						</div>
					</div>
				</div>

				{{-- Companion Groups Summary Modal --}}
				<div class="guest-modal guest-modal--compact" id="companionSummaryModal" aria-hidden="true">
					<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-companion-summary="true"></div>
					<div class="guest-modal__content guest-modal__content--compact relative z-[1] w-full max-w-[500px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="companionSummaryTitle">
						<button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-companion-summary="true" aria-label="Close summary">&times;</button>
						<h3 id="companionSummaryTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Companion Groups Summary</h3>
						<div id="companionSummaryBody" class="guest-modal__body mt-6 grid gap-4"></div>
						<div class="guest-form__actions mt-6 flex flex-wrap justify-end gap-3">
							<button type="button" class="guest-form__secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" data-close-companion-summary="true">Cancel</button>
							<button type="button" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark" id="proceedToCheckInBtn">Proceed to Check In</button>
						</div>
					</div>
				</div>

				{{-- Bulk Companion Modal --}}
				<div class="guest-modal guest-modal--compact" id="bulkCompanionModal" aria-hidden="true">
					<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-bulk-companion-modal="true"></div>
					<div class="guest-modal__content guest-modal__content--compact relative z-[1] w-full max-w-[500px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="bulkCompanionModalTitle">
						<button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-bulk-companion-modal="true" aria-label="Close bulk companion form">&times;</button>

						<div class="guest-modal__header mb-4 flex flex-col items-center gap-1 border-b-0 pb-0 text-center">
							<div class="guest-modal__icon-wrap mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-hp-green-mid text-white shadow-[0_4px_12px_rgba(46,125,85,0.2)]">
								<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
									<path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z" />
								</svg>
							</div>
							<h3 id="bulkCompanionModalTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Add Companions in Bulk</h3>
							<p class="guest-modal__subtitle mt-1 text-sm text-hp-text-muted">Quickly generate multiple companions of the same demographic profile.</p>
						</div>

						<form id="bulkCompanionForm" class="guest-form mt-6 grid gap-4" action="#">
							<div class="bulk-panel mb-6 rounded-2xl border border-glass-border bg-glass-hover p-5">
								<div class="guest-form__row guest-form__row--two grid grid-cols-1 gap-4 sm:grid-cols-2">
									<div class="bulk-field flex flex-col gap-2">
										<span class="bulk-field__label text-[0.8rem] font-semibold uppercase tracking-[0.5px] text-hp-text-muted">Gender</span>
										<div class="bulk-segment flex overflow-hidden rounded-xl border border-glass-border bg-glass">
											<label class="bulk-segment__btn relative flex-1 cursor-pointer text-center">
												<input type="radio" name="gender" value="Male" checked class="absolute h-0 w-0 opacity-0">
												<span class="block border-r border-glass-border px-2 py-2.5 text-sm font-medium transition-all duration-200">Male</span>
											</label>
											<label class="bulk-segment__btn relative flex-1 cursor-pointer text-center">
												<input type="radio" name="gender" value="Female" class="absolute h-0 w-0 opacity-0">
												<span class="block px-2 py-2.5 text-sm font-medium transition-all duration-200">Female</span>
											</label>
										</div>
									</div>

									<div class="bulk-field flex flex-col gap-2">
										<span class="bulk-field__label text-[0.8rem] font-semibold uppercase tracking-[0.5px] text-hp-text-muted">Nationality</span>
										<div class="bulk-segment flex overflow-hidden rounded-xl border border-glass-border bg-glass">
											<label class="bulk-segment__btn relative flex-1 cursor-pointer text-center">
												<input type="radio" name="is_foreigner" value="0" checked class="absolute h-0 w-0 opacity-0">
												<span class="block border-r border-glass-border px-2 py-2.5 text-sm font-medium transition-all duration-200">Filipino</span>
											</label>
											<label class="bulk-segment__btn relative flex-1 cursor-pointer text-center">
												<input type="radio" name="is_foreigner" value="1" class="absolute h-0 w-0 opacity-0">
												<span class="block px-2 py-2.5 text-sm font-medium transition-all duration-200">Foreigner</span>
											</label>
										</div>
									</div>
								</div>

								<div class="guest-form__row mt-5 grid gap-4">
									<label class="guest-form__field grid gap-1.5">
										<span class="text-[0.8rem] font-semibold uppercase tracking-[0.5px] text-hp-text-muted">Age Group</span>
										<select name="age_group" id="bulkCompanionAgeGroup" class="w-full cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-3 text-sm text-hp-text">
											<option value="0-12">Kids (0-12 years)</option>
											<option value="13-17">Teens (13-17 years)</option>
											<option value="18-59">Adults (18-59 years)</option>
											<option value="60+">Seniors (60+ years)</option>
										</select>
									</label>
								</div>
							</div>

							<!-- Quantity Stepper -->
							<div class="bulk-quantity-panel text-center">
								<span class="bulk-field__label mb-3 block text-[0.85rem] font-semibold uppercase tracking-[0.5px] text-hp-text-muted">Number of Companions</span>
								<div class="bulk-stepper inline-flex items-center overflow-hidden rounded-2xl border border-glass-border-strong bg-glass shadow-glass">
									<button type="button" class="bulk-stepper__btn flex h-12 w-12 cursor-pointer items-center justify-center border-0 bg-transparent text-hp-text transition-colors duration-200 hover:bg-glass-hover" id="bulkBtnMinus" aria-label="Decrease quantity">
										<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h12.5a.75.75 0 010 1.5H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
									</button>
									<input type="number" name="quantity" id="bulkCompanionQuantity" class="bulk-stepper__input h-12 w-[60px] border-x border-glass-border bg-transparent text-center text-xl font-bold text-hp-text [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" min="1" max="50" value="1" required>
									<button type="button" class="bulk-stepper__btn flex h-12 w-12 cursor-pointer items-center justify-center border-0 bg-transparent text-hp-text transition-colors duration-200 hover:bg-glass-hover" id="bulkBtnPlus" aria-label="Increase quantity">
										<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v5.5h5.5a.75.75 0 010 1.5h-5.5v5.5a.75.75 0 01-1.5 0v-5.5h-5.5a.75.75 0 010-1.5h5.5v-5.5A.75.75 0 0110 3z" clip-rule="evenodd" /></svg>
									</button>
								</div>
							</div>

							<div class="guest-form__actions mt-8 flex gap-4">
								<button type="button" class="guest-form__secondary flex-1 cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong" data-close-bulk-companion-modal="true">Cancel</button>
								<button type="submit" class="guest-form__button flex flex-[2] cursor-pointer items-center justify-center gap-2 rounded-xl border-0 bg-hp-green-mid px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark" id="generateCompanionsBtn">
									<svg class="h-[18px] w-[18px]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v5.5h5.5a.75.75 0 010 1.5h-5.5v5.5a.75.75 0 01-1.5 0v-5.5h-5.5a.75.75 0 010-1.5h5.5v-5.5A.75.75 0 0110 3z" clip-rule="evenodd" /></svg>
									Generate Companions
								</button>
							</div>
						</form>
					</div>
				</div>

				{{-- Bulk Group Manage Modal --}}
				<div class="guest-modal guest-modal--compact" id="bulkGroupManageModal" aria-hidden="true">
					<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-bulk-manage-modal="true"></div>
					<div class="guest-modal__content guest-modal__content--compact relative z-[1] w-full max-w-[500px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-0 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="bulkGroupManageTitle">
						<button type="button" class="guest-modal__close absolute right-4 top-4 z-10 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-bulk-manage-modal="true" aria-label="Close form">&times;</button>

						<div class="guest-modal__header flex flex-col items-center gap-1 border-b-0 p-8 pb-4 text-center">
							<div class="guest-modal__icon-wrap mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-[#6e9f54] to-[#2e7d55] text-white shadow-[0_4px_15px_rgba(46,125,85,0.3)]">
								<svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
									<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
								</svg>
							</div>
							<h3 id="bulkGroupManageTitle" class="guest-modal__title m-0 text-xl font-bold text-hp-text">Manage Bulk Companions</h3>
							<div class="mt-2 flex items-center justify-center gap-2 text-sm text-hp-text-muted">
								<span>Reservation</span>
								<span id="bulkManageResId" class="rounded-xl border border-glass-border bg-glass-hover px-2.5 py-1 font-semibold text-hp-text">#</span>
							</div>
						</div>

						<div class="guest-modal__body p-8 pt-0 text-center">
							<div id="bulkManageDemographics" class="mb-8 inline-flex items-center gap-2 rounded-full border border-dashed border-[rgba(46,125,85,0.3)] bg-hp-cream px-5 py-3 font-medium text-hp-green-mid dark:bg-white/5">
								<!-- Rendered dynamically via JS -->
							</div>

							<div class="mb-6 flex items-center justify-center gap-8 rounded-2xl border border-glass-border bg-glass p-6 shadow-[0_2px_8px_rgba(0,0,0,0.02)] dark:bg-glass">
								<button type="button" id="bulkManageBtnDecrease" aria-label="Check out one companion" class="flex h-[52px] w-[52px] cursor-pointer items-center justify-center rounded-full border border-rose-500/30 bg-rose-500/15 text-rose-500 dark:text-rose-400 transition-all duration-200 hover:bg-rose-500/25">
									<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" /></svg>
								</button>

								<div class="flex flex-col items-center">
									<span id="bulkManageActiveCount" class="font-['Montserrat',sans-serif] text-[3.5rem] font-extrabold leading-none text-hp-text">0</span>
									<div class="mt-2 flex items-center gap-1 rounded-xl bg-glass-strong px-3 py-1 text-[0.8rem] text-hp-text-muted">
										<span>out of</span>
										<span id="bulkManageTotalCount" class="font-bold text-hp-text">0</span>
										<span>inside</span>
									</div>
								</div>
							</div>

							{{-- Check out several companions at once --}}
							<div class="mb-4 rounded-2xl border border-glass-border bg-glass p-4 dark:bg-glass">
								<p class="mb-3 text-center text-[0.72rem] font-semibold uppercase tracking-[0.5px] text-hp-text-muted">Check out multiple at once</p>
								<div class="flex items-center justify-center gap-2.5">
									<button type="button" id="bulkManageQtyMinus" aria-label="Decrease quantity" class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-glass-border bg-glass text-lg text-hp-text transition-colors duration-200 hover:bg-glass-hover">−</button>
									<input type="number" id="bulkManageQtyInput" value="1" min="1" max="50" class="bulk-stepper__input h-11 w-16 border-x-0 border border-glass-border bg-transparent text-center text-lg font-bold text-hp-text [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" aria-label="Number of companions to check out">
									<button type="button" id="bulkManageQtyPlus" aria-label="Increase quantity" class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-glass-border bg-glass text-lg text-hp-text transition-colors duration-200 hover:bg-glass-hover">+</button>
									<button type="button" id="bulkManageCheckOutBtn" class="ml-1 flex h-11 cursor-pointer items-center gap-2 rounded-xl border-0 bg-rose-600 px-5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-rose-700 shadow-[0_2px_8px_rgba(225,29,72,0.25)]">
										<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" /></svg>
										Check Out
									</button>
								</div>
							</div>

							<div class="flex items-center justify-center gap-2 rounded-xl border border-rose-500/20 bg-rose-500/10 p-3 text-left text-[0.8rem] leading-[1.4] text-rose-600 dark:text-rose-400">
								<svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
								<span>Click the <strong>minus button</strong> to check out one, or set a quantity and press <strong>Check Out</strong> to check out several at once.</span>
							</div>
						</div>
					</div>
				</div>

				{{-- Adjust / Extend Stay Schedule Modal --}}
				<div class="guest-modal guest-modal--compact" id="extendStayModal" aria-hidden="true">
					<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-extend-stay-modal="true"></div>
					<div class="guest-modal__content guest-modal__content--range relative z-[1] w-full max-w-[540px] max-h-[min(90vh,820px)] overflow-y-auto rounded-2xl bg-glass p-5 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="extendStayTitle">
						<button type="button" class="guest-modal__close absolute right-3.5 top-3.5 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-extend-stay-modal="true" aria-label="Close modal">&times;</button>
						<div class="guest-modal__header mb-3 flex flex-wrap items-center justify-between gap-3 border-b border-[rgba(13,44,29,0.1)] pb-3 dark:border-white/10">
							<div class="flex items-center gap-2.5">
								<div class="flex h-9 w-9 items-center justify-center rounded-xl bg-hp-green/10 text-hp-green dark:bg-hp-green/20">
									<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
										<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
									</svg>
								</div>
								<div>
									<h3 id="extendStayTitle" class="guest-modal__title m-0 font-display text-lg font-bold text-hp-text">Adjust Stay Schedule</h3>
									<span class="text-xs text-hp-text-muted">Reservation #<span id="extendStayResId"></span></span>
								</div>
							</div>
							<span class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-600 dark:text-emerald-400" id="extendStayCurrentBadge">Stay Window</span>
						</div>

						<form id="extendStayForm" class="grid gap-3.5">
							<input type="hidden" id="extendStayNewEndDate" value="">
							<input type="hidden" id="extendStayNewEndSlot" value="Daytime">

							<div class="rounded-xl border border-glass-border bg-hp-cream/60 p-3 dark:bg-white/5">
								<div class="text-[0.7rem] font-bold uppercase tracking-wider text-hp-text-muted mb-0.5">Current Stay Schedule</div>
								<div class="text-xs font-bold text-hp-text" id="extendStayCurrentSummary">—</div>
								<div class="mt-1 text-[0.72rem] text-hp-text-muted" id="extendStayBoundaryHelp">You can extend the stay forward or step back as long as it does not cross booked amenities.</div>
							</div>

							{{-- Check-Out Session Selector --}}
							<div class="grid gap-1">
								<span class="text-[0.72rem] font-bold uppercase tracking-[0.04em] text-hp-text-muted">Check-Out Session</span>
								<div class="flex gap-1.5" id="extendStayEndSlotGroup">
									<button type="button" class="session-pill-btn flex-1 rounded-lg border border-glass-border bg-glass py-1.5 text-xs font-bold text-hp-text transition-all duration-150 data-[active=true]:border-hp-green data-[active=true]:bg-hp-green data-[active=true]:text-white" data-slot-val="Daytime" data-active="true">Daytime (until 5:00 PM)</button>
									<button type="button" class="session-pill-btn flex-1 rounded-lg border border-glass-border bg-glass py-1.5 text-xs font-bold text-hp-text transition-all duration-150 data-[active=true]:border-hp-green data-[active=true]:bg-hp-green data-[active=true]:text-white" data-slot-val="Nighttime">Nighttime (until 6:00 AM)</button>
								</div>
							</div>

							{{-- Interactive 5-Year Calendar --}}
							<div class="edit-calendar edit-calendar--modal rounded-[0.85rem] border border-glass-border bg-hp-cream p-3.5 transition-colors duration-300 dark:bg-white/5 dark:border-white/10">
								<div class="edit-calendar__head mb-2 flex items-center justify-between gap-2">
									<button type="button" class="edit-calendar__nav inline-flex h-[1.9rem] w-[1.9rem] cursor-pointer items-center justify-center rounded-[0.5rem] border border-glass-border bg-glass text-lg leading-none text-hp-text transition-all duration-200 hover:border-hp-green hover:bg-hp-green/10 dark:border-white/12 dark:bg-white/6 dark:text-[#c8e6c8]" id="extendStayCalPrev" aria-label="Previous month">&lsaquo;</button>
									<div class="edit-calendar__title-wrap flex min-w-0 items-baseline gap-2">
										<div class="edit-calendar__title text-[0.9rem] font-bold capitalize text-hp-text dark:text-[#c8e6c8]" id="extendStayCalTitle">&mdash;</div>
										<select class="edit-calendar__year cursor-pointer rounded-[0.45rem] border border-glass-border bg-glass px-2 py-0.5 text-xs font-bold text-hp-text transition-all duration-200 hover:border-hp-green focus:border-hp-green focus:outline-none dark:border-white/12 dark:bg-white/6 dark:text-[#c8e6c8]" id="extendStayCalYear" aria-label="Select year"></select>
									</div>
									<button type="button" class="edit-calendar__nav inline-flex h-[1.9rem] w-[1.9rem] cursor-pointer items-center justify-center rounded-[0.5rem] border border-glass-border bg-glass text-lg leading-none text-hp-text transition-all duration-200 hover:border-hp-green hover:bg-hp-green/10 dark:border-white/12 dark:bg-white/6 dark:text-[#c8e6c8]" id="extendStayCalNext" aria-label="Next month">&rsaquo;</button>
								</div>

								<div class="edit-calendar__weekdays mt-1.5 grid grid-cols-7 gap-1">
									<span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Su</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Mo</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Tu</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">We</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Th</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Fr</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Sa</span>
								</div>

								<div class="edit-calendar__grid relative mt-1 grid min-h-[190px] grid-cols-7 gap-1" id="extendStayCalGrid"></div>

								<div class="mt-2.5 flex flex-wrap items-center justify-between gap-2 border-t border-glass-border pt-2 text-[0.7rem] text-hp-text-muted dark:border-white/10">
									<div class="flex flex-wrap items-center gap-2">
										<span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-hp-green"></span> Selected Stay</span>
										<span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-hp-green/20"></span> In Stay Range</span>
									</div>
									<span id="extendStayCalStepHelp" class="font-semibold text-hp-green dark:text-[#81c784]">Click date to set Check-Out</span>
								</div>
							</div>

							<div id="extendStayWarning" class="hidden rounded-xl border border-amber-500/30 bg-amber-500/10 p-2.5 text-xs text-amber-700 dark:text-amber-300"></div>

							<div class="mt-1 flex justify-end gap-3">
								<button type="button" class="cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2 text-sm font-semibold text-hp-text transition-all hover:bg-glass-hover" data-close-extend-stay-modal="true">Cancel</button>
								<button type="submit" class="cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2 text-sm font-semibold text-white transition-all hover:bg-hp-green-dark" id="submitExtendStayBtn">Save Stay Schedule</button>
							</div>
						</form>
					</div>
				</div>

				{{-- Extend Existing Amenity Modal --}}
				<div class="guest-modal guest-modal--compact" id="extendAmenityModal" aria-hidden="true">
					<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-extend-amenity-modal="true"></div>
					<div class="guest-modal__content guest-modal__content--range relative z-[1] w-full max-w-[560px] max-h-[min(90vh,820px)] overflow-y-auto rounded-2xl bg-glass p-5 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="extendAmenityTitle">
						<button type="button" class="guest-modal__close absolute right-3.5 top-3.5 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-extend-amenity-modal="true" aria-label="Close modal">&times;</button>
						<div class="guest-modal__header mb-3 flex flex-wrap items-center justify-between gap-3 border-b border-[rgba(13,44,29,0.1)] pb-3 dark:border-white/10">
							<div class="flex items-center gap-2.5">
								<div class="flex h-9 w-9 items-center justify-center rounded-xl bg-hp-green/10 text-hp-green dark:bg-hp-green/20">
									<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
										<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
									</svg>
								</div>
								<div>
									<h3 id="extendAmenityTitle" class="guest-modal__title m-0 font-display text-lg font-bold text-hp-text">Extend Amenity Duration</h3>
									<span class="text-xs text-hp-text-muted" id="extendAmenitySubtitle">Extend active amenity</span>
								</div>
							</div>
						</div>

						<form id="extendAmenityForm" class="grid gap-3.5">
							<input type="hidden" id="extendAmenityResId" value="">
							<input type="hidden" id="extendAmenityRaId" value="">
							<input type="hidden" id="extendAmenityNewEndDate" value="">
							<input type="hidden" id="extendAmenityNewEndSlot" value="Daytime">

							{{-- Non-reversibility Policy Notice --}}
							<div class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-800 dark:text-amber-300 flex items-start gap-2">
								<svg class="h-4 w-4 shrink-0 mt-0.5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
									<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
								</svg>
								<div>
									<strong>Notice:</strong> Once an amenity extension is confirmed and paid, its duration <strong>cannot be decreased or stepped back</strong>. It can only be extended further within the stay schedule.
								</div>
							</div>

							<div class="rounded-xl border border-glass-border bg-hp-cream/60 p-3 dark:bg-white/5">
								<div class="flex justify-between items-center mb-1">
									<span class="text-xs font-semibold uppercase tracking-wider text-hp-text-muted">Amenity</span>
									<strong class="text-sm font-bold text-hp-text" id="extendAmenityName">—</strong>
								</div>
								<div class="flex justify-between items-center mb-1">
									<span class="text-xs text-hp-text-muted">Current Duration</span>
									<span class="text-xs font-semibold text-hp-text" id="extendAmenityCurrentDuration">—</span>
								</div>
								<div class="flex justify-between items-center">
									<span class="text-xs text-hp-text-muted">Master Stay Window Limit</span>
									<span class="text-xs font-semibold text-hp-green" id="extendAmenityStayLimit">—</span>
								</div>
							</div>

							{{-- Check-Out Session Selector --}}
							<div class="grid gap-1">
								<span class="text-[0.72rem] font-bold uppercase tracking-[0.04em] text-hp-text-muted">New Check-Out Session</span>
								<div class="flex gap-1.5" id="extendAmenityEndSlotGroup">
									<button type="button" class="session-pill-btn flex-1 rounded-lg border border-glass-border bg-glass py-1.5 text-xs font-bold text-hp-text transition-all duration-150 data-[active=true]:border-hp-green data-[active=true]:bg-hp-green data-[active=true]:text-white" data-slot-val="Daytime" data-active="true">Daytime</button>
									<button type="button" class="session-pill-btn flex-1 rounded-lg border border-glass-border bg-glass py-1.5 text-xs font-bold text-hp-text transition-all duration-150 data-[active=true]:border-hp-green data-[active=true]:bg-hp-green data-[active=true]:text-white" data-slot-val="Nighttime">Nighttime</button>
								</div>
							</div>

							{{-- Interactive 5-Year Calendar for Amenity Extension --}}
							<div class="edit-calendar edit-calendar--modal rounded-[0.85rem] border border-glass-border bg-hp-cream p-3.5 transition-colors duration-300 dark:bg-white/5 dark:border-white/10">
								<div class="edit-calendar__head mb-2 flex items-center justify-between gap-2">
									<button type="button" class="edit-calendar__nav inline-flex h-[1.9rem] w-[1.9rem] cursor-pointer items-center justify-center rounded-[0.5rem] border border-glass-border bg-glass text-lg leading-none text-hp-text transition-all duration-200 hover:border-hp-green hover:bg-hp-green/10 dark:border-white/12 dark:bg-white/6 dark:text-[#c8e6c8]" id="extendAmenityCalPrev" aria-label="Previous month">&lsaquo;</button>
									<div class="edit-calendar__title-wrap flex min-w-0 items-baseline gap-2">
										<div class="edit-calendar__title text-[0.9rem] font-bold capitalize text-hp-text dark:text-[#c8e6c8]" id="extendAmenityCalTitle">&mdash;</div>
										<select class="edit-calendar__year cursor-pointer rounded-[0.45rem] border border-glass-border bg-glass px-2 py-0.5 text-xs font-bold text-hp-text transition-all duration-200 hover:border-hp-green focus:border-hp-green focus:outline-none dark:border-white/12 dark:bg-white/6 dark:text-[#c8e6c8]" id="extendAmenityCalYear" aria-label="Select year"></select>
									</div>
									<button type="button" class="edit-calendar__nav inline-flex h-[1.9rem] w-[1.9rem] cursor-pointer items-center justify-center rounded-[0.5rem] border border-glass-border bg-glass text-lg leading-none text-hp-text transition-all duration-200 hover:border-hp-green hover:bg-hp-green/10 dark:border-white/12 dark:bg-white/6 dark:text-[#c8e6c8]" id="extendAmenityCalNext" aria-label="Next month">&rsaquo;</button>
								</div>

								<div class="edit-calendar__weekdays mt-1.5 grid grid-cols-7 gap-1">
									<span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Su</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Mo</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Tu</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">We</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Th</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Fr</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Sa</span>
								</div>

								<div class="edit-calendar__grid relative mt-1 grid min-h-[190px] grid-cols-7 gap-1" id="extendAmenityCalGrid"></div>

								<div class="mt-2.5 flex flex-wrap items-center justify-between gap-2 border-t border-glass-border pt-2 text-[0.7rem] text-hp-text-muted dark:border-white/10">
									<div class="flex flex-wrap items-center gap-2">
										<span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-hp-green"></span> Extended Range</span>
										<span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-rose-500"></span> Booked (Conflict)</span>
										<span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-500/60"></span> Exceeds Stay</span>
									</div>
									<span id="extendAmenityCalStepHelp" class="font-semibold text-hp-green dark:text-[#81c784]">Click date to extend amenity</span>
								</div>
							</div>

							{{-- Price & Extra Duration Preview --}}
							<div class="rounded-xl border border-glass-border bg-glass p-3.5">
								<div class="flex justify-between text-xs mb-1.5">
									<span class="text-hp-text-muted">Added Continuous Sessions:</span>
									<strong class="text-hp-text" id="extendAmenityAddedSessionsText">0 sessions</strong>
								</div>
								<div class="flex justify-between text-sm font-bold border-t border-glass-border pt-1.5">
									<span class="text-hp-text">Additional Fee to Pay:</span>
									<span class="text-hp-green font-extrabold text-base" id="extendAmenityAddedCostText">₱0.00</span>
								</div>
							</div>

							<div id="extendAmenityWarning" class="hidden rounded-xl border border-amber-500/30 bg-amber-500/10 p-2.5 text-xs text-amber-700 dark:text-amber-300"></div>

							<div class="mt-1 flex justify-end gap-3">
								<button type="button" class="cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2 text-sm font-semibold text-hp-text transition-all hover:bg-glass-hover" data-close-extend-amenity-modal="true">Cancel</button>
								<button type="submit" class="cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2 text-sm font-semibold text-white transition-all hover:bg-hp-green-dark" id="submitExtendAmenityBtn">Proceed to Pay & Confirm</button>
							</div>
						</form>
					</div>
				</div>

				{{-- Add New Amenity Mid-Stay Modal --}}
				<div class="guest-modal guest-modal--wide" id="addAmenityMidStayModal" aria-hidden="true">
					<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-add-amenity-modal="true"></div>
					<div class="guest-modal__content guest-modal__content--wide relative z-[1] w-full max-w-[580px] max-h-[min(90vh,840px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="addAmenityMidStayTitle">
						<button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-add-amenity-modal="true" aria-label="Close add amenity modal">&times;</button>
						<div class="guest-modal__header mb-4 flex items-center justify-between gap-3 border-b border-[rgba(13,44,29,0.1)] pb-3 dark:border-white/10">
							<div class="flex items-center gap-2.5">
								<div class="flex h-9 w-9 items-center justify-center rounded-xl bg-hp-green/10 text-hp-green dark:bg-hp-green/20">
									<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
										<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
									</svg>
								</div>
								<div>
									<h3 id="addAmenityMidStayTitle" class="guest-modal__title m-0 font-display text-lg font-bold text-hp-text">Add Amenity Mid-Stay</h3>
									<span class="text-xs text-hp-text-muted">Add new amenity to Reservation #<span id="addAmenityResId"></span></span>
								</div>
							</div>
						</div>

						<form id="addAmenityMidStayForm" class="grid gap-3.5">
							<input type="hidden" id="addAmenityMidStayResId" value="">
							<input type="hidden" id="addAmenityNewEndDate" value="">
							<input type="hidden" id="addAmenityNewEndSlot" value="Daytime">

							<!-- 1. Select Amenity -->
							<div class="grid gap-1.5">
								<label class="text-xs font-bold uppercase tracking-wider text-hp-text-muted" for="midStayAmenitySelect">Select Amenity</label>
								<select id="midStayAmenitySelect" required class="w-full rounded-xl border border-glass-border bg-glass px-3.5 py-2.5 text-sm font-semibold text-hp-text transition-colors focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5">
									<option value="">-- Choose an amenity --</option>
								</select>
							</div>

							<!-- 2. Start Info (Fixed from Today) & Master Stay Limit -->
							<div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 rounded-xl border border-glass-border bg-glass p-3 dark:border-white/10 dark:bg-white/5">
								<div class="grid gap-1">
									<span class="text-[0.72rem] font-bold uppercase tracking-[0.04em] text-hp-text-muted">Start Session (Today)</span>
									<div class="flex items-center gap-2 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 text-xs font-bold text-emerald-700 dark:text-emerald-300">
										<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
											<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
										</svg>
										<span id="addAmenityStartFixedText">Today • Daytime</span>
									</div>
								</div>
								<div class="grid gap-1">
									<span class="text-[0.72rem] font-bold uppercase tracking-[0.04em] text-hp-text-muted">Stay Check-Out Limit</span>
									<div class="flex items-center gap-2 rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-xs font-bold text-amber-800 dark:text-amber-300">
										<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
											<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
										</svg>
										<span id="addAmenityStayLimit">—</span>
									</div>
								</div>
							</div>

							<!-- 3. Aircon Option (Only shown if amenity actually has aircon) -->
							<div id="midStayAirconWrapper" class="hidden">
								<label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-glass-border bg-glass p-3 text-xs text-hp-text hover:bg-glass-hover">
									<input type="checkbox" id="midStayIsAircon" class="h-4 w-4 accent-hp-green">
									<span class="font-bold">With Aircon Option</span>
								</label>
							</div>

							<!-- 4. End Session Selector -->
							<div class="grid gap-1">
								<span class="text-[0.72rem] font-bold uppercase tracking-[0.04em] text-hp-text-muted">Select Amenity Check-Out Session</span>
								<div class="flex gap-2" id="addAmenityEndSlotGroup">
									<button type="button" class="session-pill-btn flex-1 rounded-xl border border-glass-border bg-glass py-2 text-xs font-bold text-hp-text transition-all duration-150 data-[active=true]:border-hp-green data-[active=true]:bg-hp-green data-[active=true]:text-white cursor-pointer" data-slot-val="Daytime" data-active="true">Daytime</button>
									<button type="button" class="session-pill-btn flex-1 rounded-xl border border-glass-border bg-glass py-2 text-xs font-bold text-hp-text transition-all duration-150 data-[active=true]:border-hp-green data-[active=true]:bg-hp-green data-[active=true]:text-white cursor-pointer" data-slot-val="Nighttime">Nighttime</button>
								</div>
							</div>

							<!-- 5. 5-Year Calendar Component -->
							<div class="edit-calendar edit-calendar--modal rounded-[0.85rem] border border-glass-border bg-hp-cream p-4 transition-colors duration-300 dark:bg-white/5 dark:border-white/10">
								<div class="edit-calendar__head mb-2 flex items-center justify-between gap-2">
									<button type="button" class="edit-calendar__nav inline-flex h-[2rem] w-[2rem] cursor-pointer items-center justify-center rounded-[0.55rem] border border-glass-border bg-glass text-lg leading-none text-hp-text transition-all duration-200 hover:border-hp-green hover:bg-hp-green/10 dark:border-white/12 dark:bg-white/6 dark:text-[#c8e6c8]" id="addAmenityCalPrev" aria-label="Previous month">&lsaquo;</button>
									<div class="edit-calendar__title-wrap flex min-w-0 items-baseline gap-2">
										<div class="edit-calendar__title text-[0.95rem] font-bold capitalize text-hp-text dark:text-[#c8e6c8]" id="addAmenityCalTitle">&mdash;</div>
										<select class="edit-calendar__year cursor-pointer rounded-[0.45rem] border border-glass-border bg-glass px-2.5 py-1 text-[0.85rem] font-bold text-hp-text transition-all duration-200 hover:border-hp-green focus:border-hp-green focus:outline-none dark:border-white/12 dark:bg-white/6 dark:text-[#c8e6c8]" id="addAmenityCalYear" aria-label="Select year"></select>
									</div>
									<button type="button" class="edit-calendar__nav inline-flex h-[2rem] w-[2rem] cursor-pointer items-center justify-center rounded-[0.55rem] border border-glass-border bg-glass text-lg leading-none text-hp-text transition-all duration-200 hover:border-hp-green hover:bg-hp-green/10 dark:border-white/12 dark:bg-white/6 dark:text-[#c8e6c8]" id="addAmenityCalNext" aria-label="Next month">&rsaquo;</button>
								</div>

								<div class="edit-calendar__weekdays mt-2 grid grid-cols-7 gap-1">
									<span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Su</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Mo</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Tu</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">We</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Th</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Fr</span><span class="text-center text-[0.65rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Sa</span>
								</div>

								<div class="edit-calendar__grid relative mt-1 grid min-h-[200px] grid-cols-7 gap-1 transition-opacity duration-250" id="addAmenityCalGrid">
									<div class="col-span-7 flex flex-col items-center justify-center py-10 text-xs text-hp-text-muted font-medium">Please select an amenity above</div>
								</div>

								<div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-glass-border pt-2 text-[0.72rem] text-hp-text-muted dark:border-white/10">
									<div class="flex flex-wrap items-center gap-2">
										<span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-hp-green"></span> Selected Stay</span>
										<span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span> Booked</span>
										<span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span> Exceeds Stay</span>
									</div>
									<span id="addAmenityCalStepHelp" class="font-semibold text-hp-green dark:text-[#81c784]">Click date to set check-out</span>
								</div>
							</div>

							<!-- 6. Price Preview -->
							<div class="rounded-xl border border-glass-border bg-glass p-3.5">
								<div class="flex justify-between text-xs mb-1.5">
									<span class="text-hp-text-muted font-semibold">Continuous Sessions:</span>
									<strong class="text-hp-text" id="midStaySlotsText">0 sessions</strong>
								</div>
								<div class="flex justify-between text-sm font-bold border-t border-glass-border pt-1.5">
									<span class="text-hp-text">Total Amenity Fee:</span>
									<span class="text-hp-green font-extrabold text-base" id="midStayCostText">₱0.00</span>
								</div>
							</div>

							<div id="addAmenityWarning" class="hidden rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs font-semibold text-amber-700 dark:text-amber-300"></div>

							<div class="mt-1 flex justify-end gap-3">
								<button type="button" class="cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all hover:bg-glass-hover" data-close-add-amenity-modal="true">Cancel</button>
								<button type="submit" class="cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-all hover:bg-hp-green-dark" id="submitAddAmenityBtn">Proceed to Pay & Confirm</button>
							</div>
						</form>
					</div>
				</div>

				{{-- Final Extension / Addition Payment Confirmation Modal --}}
				<div class="guest-modal guest-modal--compact" id="extensionPaymentModal" aria-hidden="true">
					<div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-extension-payment-modal="true"></div>
					<div class="guest-modal__content guest-modal__content--compact relative z-[1] w-full max-w-[520px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="extensionPaymentTitle">
						<button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-extension-payment-modal="true" aria-label="Close payment modal">&times;</button>
						<div class="guest-modal__header mb-4 flex items-center gap-2.5 border-b border-[rgba(13,44,29,0.1)] pb-3 dark:border-white/10">
							<div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20">
								<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
									<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
								</svg>
							</div>
							<div>
								<h3 id="extensionPaymentTitle" class="guest-modal__title m-0 font-display text-lg font-bold text-hp-text">Review & Pay at Counter</h3>
								<span class="text-xs text-hp-text-muted">Confirm payment to apply booking</span>
							</div>
						</div>

						<div class="grid gap-4">
							<div class="rounded-xl border border-glass-border bg-hp-cream/60 p-4 dark:bg-white/5">
								<div class="text-xs font-semibold uppercase tracking-wider text-hp-text-muted mb-2">Itemized Addition Details</div>
								<div class="flex justify-between items-center text-sm font-bold text-hp-text mb-1">
									<span id="extPayItemName">—</span>
									<span class="text-hp-green font-extrabold" id="extPayItemCost">₱0.00</span>
								</div>
								<div class="text-xs text-hp-text-muted mb-2" id="extPayItemSchedule">—</div>
								<div class="border-t border-glass-border pt-2 text-xs flex justify-between text-hp-text-muted">
									<span>Payment Method:</span>
									<strong class="text-hp-text">Cash at Counter</strong>
								</div>
							</div>

							<div class="flex items-center justify-between rounded-xl bg-hp-green/10 border border-hp-green/20 p-4">
								<div>
									<span class="block text-xs uppercase font-bold text-hp-green-dark dark:text-emerald-300">Amount Due Now</span>
									<span class="text-xs text-hp-text-muted">Collect payment from guest</span>
								</div>
								<span class="text-2xl font-extrabold text-hp-green" id="extPayTotalAmount">₱0.00</span>
							</div>

							<div class="mt-2 flex justify-end gap-3">
								<button type="button" class="cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all hover:bg-glass-hover" data-close-extension-payment-modal="true">Back / Cancel</button>
								<button type="button" class="cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2.5 text-sm font-semibold text-white transition-all hover:bg-hp-green-dark" id="confirmExtensionPaymentBtn">Confirm & Pay at Counter</button>
							</div>
						</div>
					</div>
				</div>

	<x-staff_chatbot />

	<script>
		window.staffGuestData = @json($guestData ?? []);
		window.staffReservationData = @json($reservationData ?? []);
		window.ALL_AMENITIES = @json($amenities ?? []);
		window.SERVER_TODAY = "{{ now()->toDateString() }}";
		window.SERVER_CURRENT_SESSION = "{{ ($currentPeriod ?? '') === 'nighttime' ? 'Nighttime' : 'Daytime' }}";
		window.AVAILABLE_AMENITY_IDS = @json($availableAmenityIds ?? []);
		window.OCCUPIED_TODAY_AMENITY_IDS = @json($occupiedTodayAmenityIds ?? []);
	</script>
</body>
</html>
