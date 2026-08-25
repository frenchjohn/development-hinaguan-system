<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard — Hinaguan Nature Park</title>
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
        'resources/css/chatbot.css',
        'resources/css/staff_css/staff_shared.css',
        'resources/css/admin_css/admin_shared.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/admin_js/admin_dashboard.js',
        'resources/js/admin_chatbot.js',
    ])
    <style>
        body.admin-portal {
            background-color: #ebf3ec !important;
        }
        [data-theme="dark"] body.admin-portal {
            background-color: #0f1110 !important;
        }
        body.admin-portal .dash-layout,
        body.admin-portal .dash-main,
        body.admin-portal .dash-content {
            background: transparent !important;
            background-color: transparent !important;
            background-image: none !important;
        }
        body.admin-portal .dash-main {
            position: relative !important;
            min-height: 100vh;
            z-index: 0;
        }
        body.admin-portal .dash-main::before {
            content: '' !important;
            display: block !important;
            position: fixed !important;
            top: 0 !important;
            left: var(--dash-sidebar-w, 10rem) !important;
            right: 0 !important;
            bottom: 0 !important;
            width: auto !important;
            height: 100vh !important;
            z-index: -1 !important;
            pointer-events: none !important;
            background-color: #ebf3ec !important;
            background-image: url('{{ asset('storage/design_images/staff-admin-background-image.jpeg') }}') !important;
            background-size: 100% 100% !important;
            background-position: center center !important;
            background-repeat: no-repeat !important;
            filter: none !important;
            -webkit-filter: none !important;
            opacity: 1 !important;
            transition: left 0.25s ease !important;
        }
        .dash-layout.sidebar-collapsed .dash-main::before {
            left: 0 !important;
        }
        @media (max-width: 992px) {
            body.admin-portal .dash-main::before {
                left: 0 !important;
            }
        }
        [data-theme="dark"] body.admin-portal .dash-main::before {
            background-color: #0f1110 !important;
            background-image: linear-gradient(rgba(15, 17, 16, 0.94), rgba(15, 17, 16, 0.97)), url('{{ asset('storage/design_images/staff-admin-background-image.jpeg') }}') !important;
            filter: none !important;
            -webkit-filter: none !important;
            opacity: 1 !important;
        }
        body.admin-portal .dash-content {
            position: relative !important;
            z-index: 1 !important;
        }
        body.admin-portal [class*="backdrop-blur"] {
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
    </style>
</head>
<body class="antialiased admin-portal">
    <div class="dash-layout">
        <x-admin_sidemenu active="dashboard" userName="{{ session('auth_user.name') ?? 'Admin User' }}" userRole="Admin" />

        <div class="dash-main">
            <main class="dash-content p-6">
                <x-header
                    title="Admin Dashboard"
                    subtitle="Overview of park operations, reservations, revenue, and analytics"
                    :showWelcome="true"
                />

                @php
                    $hour = (int) now()->format('G');
                    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');

                    $weatherService = app(\App\Services\WeatherService::class);
                    $weatherForecast = $weatherService->getMultiDayForecast(3);
                    $weatherNow = $weatherForecast['now'] ?? null;

                    $donutColors = [
                        'Pending' => '#c8a45d',
                        'Confirmed' => '#4c9a5f',
                        'Checked In' => '#2f6f45',
                        'Checked Out' => '#9ca3af',
                        'Cancelled' => '#d64550',
                    ];
                    $donutTotal = array_sum($statusBreakdown);
                    $donutStart = 0;
                    $donutStops = [];
                    foreach ($statusBreakdown as $status => $count) {
                        $pct = $donutTotal > 0 ? ($count / $donutTotal) * 360 : 0;
                        $color = $donutColors[$status] ?? '#c8a45d';
                        $donutStops[] = "{$color} {$donutStart}deg " . ($donutStart + $pct) . 'deg';
                        $donutStart += $pct;
                    }
                    $donutStyle = $donutTotal > 0 ? 'background: conic-gradient(' . implode(', ', $donutStops) . ');' : '';
                    $revenueMax = max(1, max($weekRevenue));
                @endphp

                {{-- ===== GREETING BANNER ===== --}}
                <section class="mb-6 flex flex-wrap items-center justify-between gap-6 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass">
                    <div class="min-w-[200px] flex-1">
                        <h2 class="m-0 mb-1 font-display text-[clamp(1.1rem,2vw,1.5rem)] font-bold leading-[1.25] text-hp-green-dark dark:text-[#f3f4f6]">{{ $greeting }}, {{ session('auth_user.name') ?? 'Admin User' }}!</h2>
                        <p class="m-0 text-sm font-medium text-hp-text-muted">Welcome to Hinaguan Nature Park · Admin Portal Overview</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-6">
                        <div class="flex flex-col items-end gap-0.5 max-[992px]:items-start">
                            <span class="text-[0.8rem] font-medium text-hp-text-muted">{{ \Carbon\Carbon::now()->format('l, F j, Y') }}</span>
                            <span class="font-display text-2xl font-bold leading-none text-hp-green-dark dark:text-[#f3f4f6]" id="sdLiveClock">{{ \Carbon\Carbon::now()->format('g:i A') }}</span>
                        </div>
                        @if ($weatherNow)
                        <div class="flex items-center gap-2">
                            @if (!empty($weatherNow['icon']))
                                <img src="{{ $weatherNow['icon'] }}" alt="" class="h-10 w-10 object-contain">
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-9 w-9 text-[#c8a45d]"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            @endif
                            <div class="flex flex-col items-center">
                                <span class="font-display text-[1.3rem] font-bold leading-[1.1] text-hp-green-dark dark:text-[#f3f4f6]">{{ round($weatherNow['temp_c'] ?? 0) }}°C</span>
                                <span class="text-[0.7rem] font-semibold text-hp-text-muted">{{ $weatherNow['condition'] ?? '—' }}</span>
                            </div>
                        </div>
                        @endif
                        <div class="shrink-0">
                            <span class="flex h-[60px] w-[60px] items-center justify-center rounded-xl bg-hp-green text-center text-[0.7rem] font-extrabold uppercase leading-[1.3] tracking-[0.06em] text-white shadow-[0_4px_12px_rgba(28,92,60,0.35)] max-[768px]:h-[50px] max-[768px]:w-[50px] max-[768px]:text-[0.6rem] dark:bg-[#178a52] dark:shadow-[0_4px_12px_rgba(23,138,82,0.4)]">PARK<br>OPEN</span>
                        </div>
                    </div>
                </section>

                {{-- ===== STAT CARDS ===== --}}
                <div class="mb-6 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                    <article class="flex items-center gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass backdrop-blur-[6px] transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1e2220] dark:text-[#6ab88c]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Total Reservations</p>
                            <p class="m-0 font-display text-2xl font-bold leading-tight text-hp-text">{{ $totalReservations }}</p>
                            <p class="m-0 text-xs text-hp-text-muted">All time bookings</p>
                        </div>
                    </article>

                    <article class="flex items-center gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass backdrop-blur-[6px] transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#eaf5e1] text-[#4b8022] dark:bg-[#213316] dark:text-[#96c76e]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Total Guests</p>
                            <p class="m-0 font-display text-2xl font-bold leading-tight text-hp-text">{{ $totalGuests }}</p>
                            <p class="m-0 text-xs text-hp-text-muted">Total visitors booked</p>
                        </div>
                    </article>

                    <article class="flex items-center gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass backdrop-blur-[6px] transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#e5f0f6] text-[#2a6a8f] dark:bg-[#182c38] dark:text-[#6ea9c9]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Monthly Revenue</p>
                            <p class="m-0 font-display text-2xl font-bold leading-tight text-hp-text">₱{{ number_format($currentMonthRevenue, 2) }}</p>
                            <p class="m-0 text-xs text-hp-text-muted">Collected this month</p>
                        </div>
                    </article>

                    <article class="flex items-center gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass backdrop-blur-[6px] transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#f0e9f4] text-[#6d4b8e] dark:bg-[#2b1f33] dark:text-[#a889c4]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                        </div>
                        <div>
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Today's Visitors</p>
                            <p class="m-0 font-display text-2xl font-bold leading-tight text-hp-text">{{ $todayVisitors }}</p>
                            <p class="m-0 text-xs text-hp-text-muted">Expected park arrivals</p>
                        </div>
                    </article>
                </div>

                {{-- ===== CHARTS SECTION ===== --}}
                <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-[2fr_1fr]">
                    {{-- Revenue Trend Smooth Curve Canvas Chart --}}
                    <section class="flex flex-col rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h3 class="m-0 text-lg font-semibold text-hp-text">Revenue & Booking Trends</h3>
                                <p class="m-0 text-xs text-hp-text-muted">Daily performance for the last 7 days</p>
                            </div>
                            <div class="flex items-center gap-4 text-xs font-semibold text-hp-text-muted">
                                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#1c5c3c]"></span> Revenue Trend</span>
                            </div>
                        </div>
                        <div class="relative min-h-[260px] w-full flex-1">
                            <canvas id="sdAreaChartCanvas"></canvas>
                        </div>
                    </section>

                    {{-- Reservation Status Donut Chart --}}
                    <section class="flex flex-col rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="m-0 text-lg font-semibold text-hp-text">Reservation Status</h3>
                            <span class="text-xs font-semibold text-hp-text-muted">{{ $donutTotal }} Total</span>
                        </div>
                        @if ($donutTotal > 0)
                            <div class="dash-donut-wrap my-auto">
                                <div class="dash-donut" style="{{ $donutStyle }}">
                                    <div class="dash-donut__hole">
                                        <span class="dash-donut__total font-display text-2xl font-bold leading-none text-hp-text">{{ $donutTotal }}</span>
                                        <small class="text-xs uppercase text-hp-text-muted">Bookings</small>
                                    </div>
                                </div>
                                <ul class="dash-donut__legend">
                                    @foreach ($statusBreakdown as $status => $count)
                                        <li>
                                            <i style="background: {{ $donutColors[$status] ?? '#c8a45d' }}"></i>
                                            <span class="text-xs font-medium text-hp-text">{{ $status }}</span>
                                            <strong class="text-xs font-bold text-hp-text">{{ $count }}</strong>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <p class="dash-chart__empty py-8 text-center text-sm text-hp-text-muted">No reservations recorded yet.</p>
                        @endif
                    </section>
                </div>

                {{-- ===== BOTTOM GRID ===== --}}
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    {{-- Recent Activity Table --}}
                    <section class="dash-panel flex flex-col rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h3 class="m-0 text-lg font-semibold text-hp-text">Recent Activity</h3>
                                <p class="m-0 text-xs text-hp-text-muted">Latest reservations created</p>
                            </div>
                            <a href="{{ route('admin.reports') }}" class="text-xs font-semibold text-hp-green-mid no-underline hover:underline">View All →</a>
                        </div>
                        <div class="dash-table-wrap overflow-x-auto">
                            <table class="dash-table w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-glass-border text-xs uppercase tracking-wider text-hp-text-muted">
                                        <th class="py-3 px-3">Guest</th>
                                        <th class="py-3 px-3">Date</th>
                                        <th class="py-3 px-3">Amenity</th>
                                        <th class="py-3 px-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentReservations as $reservation)
                                        @php $initials = strtoupper(implode('', array_map(fn ($w) => $w[0] ?? '', array_slice(preg_split('/\s+/', trim($reservation->booker_name ?? '?')), 0, 2)))); @endphp
                                        <tr class="border-b border-glass-border/50 hover:bg-glass-hover">
                                            <td class="py-3 px-3">
                                                <span class="cell-person flex items-center gap-2">
                                                    <span class="cell-person__avatar flex h-7 w-7 items-center justify-center rounded-full bg-[#1c5c3c] text-xs font-bold text-white">{{ $initials ?: '?' }}</span>
                                                    <span class="cell-person__name font-medium text-hp-text">{{ $reservation->booker_name }}</span>
                                                </span>
                                            </td>
                                            <td class="mono-cell py-3 px-3 text-xs text-hp-text-muted">{{ $reservation->reservation_date ? \Illuminate\Support\Carbon::parse($reservation->reservation_date)->format('M d, Y') : 'TBD' }}</td>
                                            <td class="py-3 px-3 text-xs text-hp-text-muted">{{ $reservation->reservationAmenities->pluck('amenity.amenities_name')->filter()->join(', ') ?: 'None' }}</td>
                                            <td class="py-3 px-3">
                                                <span class="status-pill status-pill--{{ strtolower(str_replace(' ', '-', $reservation->status)) }} rounded-full px-2.5 py-1 text-[0.7rem] font-bold">{{ $reservation->status }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-6 text-center text-sm text-hp-text-muted">No recent reservations yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>

                    {{-- Recent Operational Activity & Staff Audit Log --}}
                    <section class="dash-panel flex flex-col rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <h3 class="m-0 text-lg font-semibold text-hp-text flex items-center gap-2">
                                    <span>Recent Operational Activities</span>
                                    <span class="inline-flex items-center rounded-full bg-amber-500/15 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-300">Staff Audit</span>
                                </h3>
                                <p class="m-0 text-xs text-hp-text-muted">Live audit log of check-ins, checkouts, stay/amenity extensions, and mid-stay additions</p>
                            </div>
                        </div>

                        <div class="space-y-3 max-h-[380px] overflow-y-auto pr-1">
                            @forelse($recentActivities as $act)
                                @php
                                    $badgeColor = match($act->activity_type) {
                                        'check_in' => 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 border-emerald-500/30',
                                        'check_out', 'amenity_checked_out' => 'bg-slate-500/20 text-slate-600 dark:text-slate-300 border-slate-500/30',
                                        'stay_extended', 'amenity_extended' => 'bg-blue-500/20 text-blue-600 dark:text-blue-300 border-blue-500/30',
                                        'amenity_added' => 'bg-purple-500/20 text-purple-600 dark:text-purple-300 border-purple-500/30',
                                        'walkin_created' => 'bg-amber-500/20 text-amber-600 dark:text-amber-300 border-amber-500/30',
                                        'staff_created', 'staff_updated', 'staff_banned', 'staff_unbanned' => 'bg-rose-500/20 text-rose-600 dark:text-rose-300 border-rose-500/30',
                                        default => 'bg-hp-green-light/30 text-hp-green-dark dark:text-hp-green-light border-hp-green-mid/30',
                                    };
                                @endphp
                                <div class="flex items-start justify-between gap-3 rounded-xl border border-glass-border bg-glass-hover p-3.5 transition-all hover:border-hp-green-mid/40">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wider {{ $badgeColor }}">
                                                {{ $act->title }}
                                            </span>
                                            @if($act->actor_name)
                                                <span class="text-xs font-semibold text-hp-text flex items-center gap-1">
                                                    <span class="text-hp-text-muted">by</span>
                                                    <span class="text-hp-green-dark dark:text-emerald-400 underline decoration-dotted">{{ $act->actor_name }}</span>
                                                    <span class="text-[0.65rem] px-1.5 py-0.2 rounded bg-glass border border-glass-border text-hp-text-muted uppercase">({{ $act->actor_role }})</span>
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-hp-text leading-relaxed m-0">{{ $act->description }}</p>
                                    </div>
                                    <span class="shrink-0 text-[0.7rem] font-medium text-hp-text-muted whitespace-nowrap">
                                        {{ $act->created_at ? $act->created_at->diffForHumans() : 'Recently' }}
                                    </span>
                                </div>
                            @empty
                                <div class="py-6 text-center text-sm text-hp-text-muted">
                                    No operational activities logged yet.
                                </div>
                            @endforelse
                        </div>
                    </section>

                    {{-- Operations Summary & Quick Links --}}
                    <section class="dash-panel flex flex-col rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
                        <div class="mb-4">
                            <h3 class="m-0 text-lg font-semibold text-hp-text">Operational Key Metrics</h3>
                            <p class="m-0 text-xs text-hp-text-muted">System metrics and management shortcuts</p>
                        </div>
                        <div class="mb-6 grid gap-3">
                            <div class="flex items-center justify-between rounded-xl border border-glass-border bg-glass-hover px-4 py-3">
                                <span class="text-sm font-medium text-hp-text">Checked-in Guests Currently On-Site</span>
                                <strong class="text-base font-bold text-hp-green-dark dark:text-[#f3f4f6]">{{ $checkedInGuests }}</strong>
                            </div>
                            <div class="flex items-center justify-between rounded-xl border border-glass-border bg-glass-hover px-4 py-3">
                                <span class="text-sm font-medium text-hp-text">Pending Confirmations</span>
                                <strong class="text-base font-bold text-[#c8a45d]">{{ $pendingReservations }}</strong>
                            </div>
                            <div class="flex items-center justify-between rounded-xl border border-glass-border bg-glass-hover px-4 py-3">
                                <span class="text-sm font-medium text-hp-text">Top Booked Amenity</span>
                                <strong class="text-sm font-bold text-hp-text">{{ $topAmenity['name'] ?? 'N/A' }} ({{ $topAmenity['count'] ?? 0 }})</strong>
                            </div>
                        </div>

                        <h4 class="mb-3 text-sm font-bold uppercase tracking-wider text-hp-text-muted">Quick Actions</h4>
                        <div class="grid grid-cols-2 gap-3">
                            <a href="{{ route('admin.amenities') }}" class="flex items-center gap-2.5 rounded-xl border border-glass-border p-3 text-xs font-semibold text-hp-text no-underline transition-all hover:bg-glass-hover hover:border-hp-green-mid">
                                <svg class="h-4 w-4 text-hp-green-mid" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                Amenities
                            </a>
                            <a href="{{ route('admin.users') }}" class="flex items-center gap-2.5 rounded-xl border border-glass-border p-3 text-xs font-semibold text-hp-text no-underline transition-all hover:bg-glass-hover hover:border-hp-green-mid">
                                <svg class="h-4 w-4 text-hp-green-mid" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Manage Users
                            </a>
                            <a href="{{ route('admin.reports') }}" class="flex items-center gap-2.5 rounded-xl border border-glass-border p-3 text-xs font-semibold text-hp-text no-underline transition-all hover:bg-glass-hover hover:border-hp-green-mid">
                                <svg class="h-4 w-4 text-hp-green-mid" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                Park Reports
                            </a>
                            <a href="{{ route('admin.settings') }}" class="flex items-center gap-2.5 rounded-xl border border-glass-border p-3 text-xs font-semibold text-hp-text no-underline transition-all hover:bg-glass-hover hover:border-hp-green-mid">
                                <svg class="h-4 w-4 text-hp-green-mid" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Settings
                            </a>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>

    {{-- Admin AI Intelligence Chatbot --}}
    <x-admin_chatbot />

    <script>
        window.__sdChartData = {
            labels: @json($weekDays),
            bookings: @json($weekRevenue),
            revenue: @json($weekRevenue)
        };
    </script>
</body>
</html>
