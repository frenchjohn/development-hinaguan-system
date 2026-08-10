<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|playfair-display:600,700" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/components/css_js/header.css',
        'resources/css/admin_css/admin_shared.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/admin_js/admin_dashboard.js',
    ])
</head>
<body class="antialiased admin-portal">
    <div class="dash-layout">
        <x-admin_sidemenu active="dashboard" userName="{{ session('auth_user.name') ?? 'Admin User' }}" userRole="Admin" />

        <div class="dash-main">
            <!-- Page transition overlay with skeleton loading -->
            <main class="dash-content p-6">
                <x-header
                    title="Admin Dashboard"
                    subtitle="Overview of park operations and reservations"
                />
                @php
                    $hour = (int) now()->format('G');
                    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
                    $donutColors = [
                        'Pending' => '#c8a45d',
                        'Confirmed' => '#4c9a5f',
                        'Checked In' => '#2f6f45',
                        'Checked Out' => '#94a3b8',
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

                {{-- ===== GREETING ===== --}}
                <section class="flex flex-wrap items-center justify-between gap-5 px-[0.15rem] pb-[1.1rem] pt-[0.35rem]">
                    <div class="flex items-center gap-[0.9rem]">
                        <img src="{{ asset('storage/design_images/main_logo.jpeg') }}" alt="Hinaguan Nature Park" class="h-[46px] w-[46px] shrink-0 rounded-[14px] border border-glass-border-strong bg-glass object-cover shadow-glass">
                        <div>
                            <p class="m-0 mb-[0.1rem] text-[0.66rem] font-bold uppercase tracking-[0.14em] text-[var(--green)]">Hinaguan Nature Park · Admin Portal</p>
                            <h2 class="m-0 font-display text-[clamp(1.15rem,1.9vw,1.5rem)] font-semibold leading-[1.2] text-[var(--ink)]">{{ $greeting }}, {{ session('auth_user.name') ?? 'Admin' }}</h2>
                            <p class="m-0 mt-[0.15rem] max-w-[46rem] text-[0.85rem] leading-[1.5] text-[var(--ink-muted)]">Here is a snapshot of Hinaguan Nature Park today — reservations, visitors, and revenue at a glance.</p>
                        </div>
                    </div>
                    <div class="shrink-0 text-right">
                        <span class="inline-flex items-center gap-[0.45rem] rounded-full border border-glass-border bg-[var(--green-soft)] px-[0.8rem] py-[0.35rem] text-[0.72rem] font-bold text-[var(--green-deep)]">
                            <span class="h-[7px] w-[7px] rounded-full bg-[var(--success)]" style="animation:spPulseSoft 2.2s ease-out infinite"></span> Live
                        </span>
                        <p class="m-0 mt-1 text-[0.82rem] font-medium text-[var(--ink-muted)]">{{ \Carbon\Carbon::now()->format('l, F j, Y') }}</p>
                    </div>
                </section>

                {{-- ===== STAT CARDS ===== --}}
                <div class="mb-8 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <article class="rounded-2xl border border-glass-border bg-glass p-6 shadow-glass backdrop-blur-[6px] transition-all duration-300 hover:-translate-y-1 hover:bg-glass-hover">
                        <div class="mb-4 h-9 w-9 text-[var(--hp-gold)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-full w-full"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <p class="m-0 mb-2 text-[0.6875rem] font-bold uppercase tracking-[0.06em] text-[var(--hp-text-muted)]">Total Reservations</p>
                        <p class="m-0 mb-2 font-display text-[2rem] font-bold leading-none text-[var(--hp-green-dark)]">{{ $totalReservations }}</p>
                        <p class="m-0 text-[0.8125rem] leading-[1.5] text-[var(--hp-text-muted)]">All reservations in the system</p>
                    </article>
                    <article class="rounded-2xl border border-glass-border bg-glass p-6 shadow-glass backdrop-blur-[6px] transition-all duration-300 hover:-translate-y-1 hover:bg-glass-hover">
                        <div class="mb-4 h-9 w-9 text-[var(--hp-gold)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-full w-full"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <p class="m-0 mb-2 text-[0.6875rem] font-bold uppercase tracking-[0.06em] text-[var(--hp-text-muted)]">Total Guests</p>
                        <p class="m-0 mb-2 font-display text-[2rem] font-bold leading-none text-[var(--hp-green-dark)]">{{ $totalGuests }}</p>
                        <p class="m-0 text-[0.8125rem] leading-[1.5] text-[var(--hp-text-muted)]">Total visitors booked</p>
                    </article>
                    <article class="rounded-2xl border border-glass-border bg-glass p-6 shadow-glass backdrop-blur-[6px] transition-all duration-300 hover:-translate-y-1 hover:bg-glass-hover">
                        <div class="mb-4 h-9 w-9 text-[var(--hp-gold)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-full w-full"><path stroke-linecap="round" stroke-linejoin="round" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                        </div>
                        <p class="m-0 mb-2 text-[0.6875rem] font-bold uppercase tracking-[0.06em] text-[var(--hp-text-muted)]">Today's Visitors</p>
                        <p class="m-0 mb-2 font-display text-[2rem] font-bold leading-none text-[var(--hp-green-dark)]">{{ $todayVisitors }}</p>
                        <p class="m-0 text-[0.8125rem] leading-[1.5] text-[var(--hp-text-muted)]">Guests expected to arrive today</p>
                    </article>
                    <article class="rounded-2xl border border-glass-border bg-glass p-6 shadow-glass backdrop-blur-[6px] transition-all duration-300 hover:-translate-y-1 hover:bg-glass-hover">
                        <div class="mb-4 h-9 w-9 text-[var(--hp-gold)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-full w-full"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="m-0 mb-2 text-[0.6875rem] font-bold uppercase tracking-[0.06em] text-[var(--hp-text-muted)]">Revenue (Month)</p>
                        <p class="m-0 mb-2 font-display text-[2rem] font-bold leading-none text-[var(--hp-green-dark)]">₱{{ number_format($currentMonthRevenue, 2) }}</p>
                        <p class="m-0 text-[0.8125rem] leading-[1.5] text-[var(--hp-text-muted)]">Collected this month</p>
                    </article>
                    <article class="rounded-2xl border border-glass-border bg-glass p-6 shadow-glass backdrop-blur-[6px] transition-all duration-300 hover:-translate-y-1 hover:bg-glass-hover">
                        <div class="mb-4 h-9 w-9 text-[var(--hp-gold)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-full w-full"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="m-0 mb-2 text-[0.6875rem] font-bold uppercase tracking-[0.06em] text-[var(--hp-text-muted)]">Pending Reservations</p>
                        <p class="m-0 mb-2 font-display text-[2rem] font-bold leading-none text-[var(--hp-green-dark)]">{{ $pendingReservations }}</p>
                        <p class="m-0 text-[0.8125rem] leading-[1.5] text-[var(--hp-text-muted)]">Awaiting confirmation</p>
                    </article>
                    <article class="rounded-2xl border border-glass-border bg-glass p-6 shadow-glass backdrop-blur-[6px] transition-all duration-300 hover:-translate-y-1 hover:bg-glass-hover">
                        <div class="mb-4 h-9 w-9 text-[var(--hp-gold)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-full w-full"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="m-0 mb-2 text-[0.6875rem] font-bold uppercase tracking-[0.06em] text-[var(--hp-text-muted)]">Checked-in Guests</p>
                        <p class="m-0 mb-2 font-display text-[2rem] font-bold leading-none text-[var(--hp-green-dark)]">{{ $checkedInGuests }}</p>
                        <p class="m-0 text-[0.8125rem] leading-[1.5] text-[var(--hp-text-muted)]">Guests on site right now</p>
                    </article>
                    <article class="rounded-2xl border border-glass-border bg-glass p-6 shadow-glass backdrop-blur-[6px] transition-all duration-300 hover:-translate-y-1 hover:bg-glass-hover">
                        <div class="mb-4 h-9 w-9 text-[var(--danger)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-full w-full"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="m-0 mb-2 text-[0.6875rem] font-bold uppercase tracking-[0.06em] text-[var(--hp-text-muted)]">Cancelled Reservations</p>
                        <p class="m-0 mb-2 font-display text-[2rem] font-bold leading-none text-[var(--hp-green-dark)]">{{ $cancelledReservations }}</p>
                        <p class="m-0 text-[0.8125rem] leading-[1.5] text-[var(--hp-text-muted)]">Cancelled reservations</p>
                    </article>
                </div>

                {{-- ===== CHARTS ===== --}}
                <div class="mb-6 grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <section class="dash-panel">
                        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-glass-border px-5 py-4">
                            <h3 class="m-0 font-display text-[0.98rem] font-semibold text-[var(--ink)]">Revenue — Last 7 Days</h3>
                            <div class="flex items-center gap-1.5 text-[0.78rem] text-[var(--ink-muted)]">
                                <span class="inline-flex items-center gap-1.5"><i class="inline-block h-2.5 w-2.5 rounded-full bg-[#7fa08e]"></i>Revenue (₱)</span>
                            </div>
                        </div>
                        <div class="dash-barchart">
                            @for ($i = 0; $i < 7; $i++)
                                <div class="dash-barchart__col">
                                    <div class="dash-barchart__bars">
                                        <div class="dash-barchart__bar dash-barchart__bar--revenue"
                                             style="--val: {{ round($weekRevenue[$i] / $revenueMax * 100) }}%"
                                             title="₱{{ number_format($weekRevenue[$i]) }} collected"></div>
                                    </div>
                                    <span class="dash-barchart__value">₱{{ number_format($weekRevenue[$i]) }}</span>
                                    <span class="dash-barchart__label">{{ $weekDays[$i] }}</span>
                                </div>
                            @endfor
                        </div>
                    </section>

                    <section class="dash-panel">
                        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-glass-border px-5 py-4">
                            <h3 class="m-0 font-display text-[0.98rem] font-semibold text-[var(--ink)]">Reservation Status</h3>
                        </div>
                        @if ($donutTotal > 0)
                            <div class="dash-donut-wrap">
                                <div class="dash-donut" style="{{ $donutStyle }}">
                                    <div class="dash-donut__hole">
                                        <span class="dash-donut__total font-display text-[1.4rem] font-bold leading-none">{{ $donutTotal }}</span>
                                        <small>total</small>
                                    </div>
                                </div>
                                <ul class="dash-donut__legend">
                                    @foreach ($statusBreakdown as $status => $count)
                                        <li>
                                            <i style="background: {{ $donutColors[$status] ?? '#c8a45d' }}"></i>
                                            {{ $status }}
                                            <strong>{{ $count }}</strong>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <p class="dash-chart__empty">No reservations recorded yet.</p>
                        @endif
                    </section>
                </div>

                {{-- ===== BOTTOM GRID ===== --}}
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <section class="dash-panel">
                        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-glass-border px-5 py-4">
                            <h3 class="m-0 font-display text-[0.98rem] font-semibold text-[var(--ink)]">Recent Reservations</h3>
                            <a href="{{ route('admin.reports') }}" class="dash-panel__link">View reports</a>
                        </div>
                        <div class="dash-table-wrap">
                            <table class="dash-table">
                                <thead>
                                    <tr>
                                        <th>Guest</th>
                                        <th>Date</th>
                                        <th>Amenity</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentReservations as $reservation)
                                        @php $initials = strtoupper(implode('', array_map(fn ($w) => $w[0] ?? '', array_slice(preg_split('/\s+/', trim($reservation->booker_name ?? '?')), 0, 2)))); @endphp
                                        <tr>
                                            <td>
                                                <span class="cell-person">
                                                    <span class="cell-person__avatar">{{ $initials ?: '?' }}</span>
                                                    <span class="cell-person__name">{{ $reservation->booker_name }}</span>
                                                </span>
                                            </td>
                                            <td class="mono-cell">{{ $reservation->reservation_date ? \Illuminate\Support\Carbon::parse($reservation->reservation_date)->format('M d, Y') : 'TBD' }}</td>
                                            <td>{{ $reservation->reservationAmenities->pluck('amenity.amenities_name')->filter()->join(', ') ?: 'None' }}</td>
                                            <td>
                                                <span class="status-pill status-pill--{{ strtolower(str_replace(' ', '-', $reservation->status)) }}">{{ $reservation->status }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="dash-table-empty">No recent reservations yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="dash-panel">
                        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-glass-border px-5 py-4">
                            <h3 class="m-0 font-display text-[0.98rem] font-semibold text-[var(--ink)]">Key metrics</h3>
                        </div>
                        <div class="grid gap-3 p-4">
                            <div class="flex items-center justify-between gap-4 rounded-[11px] border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3.5 transition-all duration-200 hover:-translate-y-px hover:border-[var(--border-strong)]">
                                <span class="inline-flex items-center gap-2 text-[0.85rem] font-medium text-[var(--ink-muted)]">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-[1.05rem] w-[1.05rem] shrink-0 text-[var(--green)]"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Total guests checked in
                                </span>
                                <strong class="whitespace-nowrap text-[0.95rem] text-[var(--ink)]">{{ $checkedInGuests }}</strong>
                            </div>
                            <div class="flex items-center justify-between gap-4 rounded-[11px] border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3.5 transition-all duration-200 hover:-translate-y-px hover:border-[var(--border-strong)]">
                                <span class="inline-flex items-center gap-2 text-[0.85rem] font-medium text-[var(--ink-muted)]">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-[1.05rem] w-[1.05rem] shrink-0 text-[var(--green)]"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                    Unique customers
                                </span>
                                <strong class="whitespace-nowrap text-[0.95rem] text-[var(--ink)]">{{ $uniqueCustomerCount }}</strong>
                            </div>
                            <div class="flex items-center justify-between gap-4 rounded-[11px] border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3.5 transition-all duration-200 hover:-translate-y-px hover:border-[var(--border-strong)]">
                                <span class="inline-flex items-center gap-2 text-[0.85rem] font-medium text-[var(--ink-muted)]">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-[1.05rem] w-[1.05rem] shrink-0 text-[var(--green)]"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    Top booked amenity
                                </span>
                                <strong class="whitespace-nowrap text-[0.95rem] text-[var(--ink)]">{{ $topAmenity['name'] ?? 'N/A' }} <small class="font-medium text-[0.8rem] text-[var(--ink-faint)]">({{ $topAmenity['count'] ?? 0 }})</small></strong>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-glass-border px-5 py-4">
                            <h3 class="m-0 font-display text-[0.98rem] font-semibold text-[var(--ink)]">Quick Actions</h3>
                        </div>
                        <ul class="dash-quick-actions">
                            <li>
                                <a href="{{ route('reservation') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    New Reservation
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.amenities') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    Manage Amenities
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.users') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Manage Staff Users
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.settings') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    System Settings
                                </a>
                            </li>
                        </ul>
                    </section>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
