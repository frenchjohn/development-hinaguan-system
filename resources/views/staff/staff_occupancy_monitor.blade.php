<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Occupancy Monitor — Hinaguan Nature Park</title>
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
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/staff_js/staff_occupancy_monitor.js',
        'resources/js/staff_chatbot.js',
    ])
    <style>
        body.staff-portal {
            background-color: #ebf3ec !important;
        }
        [data-theme="dark"] body.staff-portal {
            background-color: #0f1110 !important;
        }
        body.staff-portal .dash-layout,
        body.staff-portal .dash-main,
        body.staff-portal .dash-content {
            background: transparent !important;
            background-color: transparent !important;
            background-image: none !important;
        }
        body.staff-portal .dash-main {
            position: relative !important;
            min-height: 100vh;
            z-index: 0;
        }
        body.staff-portal .dash-main::before {
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
            background-image: linear-gradient(rgba(255, 255, 255, 0.4), rgba(255, 255, 255, 0.3)), url('{{ asset('storage/design_images/staff-admin-background-image.jpeg') }}') !important;
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
            body.staff-portal .dash-main::before {
                left: 0 !important;
            }
        }
        [data-theme="dark"] body.staff-portal .dash-main::before {
            background-color: #0f1110 !important;
            background-image: linear-gradient(rgba(15, 17, 16, 0.94), rgba(15, 17, 16, 0.97)), url('{{ asset('storage/design_images/staff-admin-background-image.jpeg') }}') !important;
            filter: none !important;
            -webkit-filter: none !important;
            opacity: 1 !important;
        }
        body.staff-portal .dash-content {
            position: relative !important;
            z-index: 1 !important;
        }
        body.staff-portal [class*="backdrop-blur"] {
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
    </style>
</head>
<body class="antialiased staff-portal">
    <div class="dash-layout">
        <x-staff_sidemenu active="occupancy-monitor" userName="{{ session('auth_user.name') ?? 'Staff User' }}" userRole="Staff" />

        <div class="dash-main">
            <x-header
                title="Occupancy Monitor"
                subtitle="Real-time view of all amenities and their availability"
            />

            <main class="dash-content p-6">

                {{-- Live status strip (KPIs) --}}
                <div class="mb-4 grid grid-cols-2 gap-3.5 md:grid-cols-3 lg:grid-cols-5" id="occupancyKpiStrip">
                    <article class="flex min-w-0 items-center gap-3 rounded-2xl border border-glass-border bg-glass p-4 shadow-glass transition-transform duration-300 hover:-translate-y-0.5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[11px] bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1e2220] dark:text-[#6ab88c]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="occupancy-stat__value m-0 font-display text-[1.45rem] font-bold leading-[1.1] text-hp-text tabular-nums" data-count="{{ $totalAmenities }}">{{ $totalAmenities }}</p>
                            <p class="mt-0.5 text-[0.68rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Amenities</p>
                        </div>
                    </article>
                    <article class="flex min-w-0 items-center gap-3 rounded-2xl border border-glass-border bg-glass p-4 shadow-glass transition-transform duration-300 hover:-translate-y-0.5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[11px] bg-[#fde8e8] text-[#b91c1c] dark:bg-[#3a1f1c] dark:text-[#f3a0a0]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="occupancy-stat__value m-0 font-display text-[1.45rem] font-bold leading-[1.1] text-hp-text tabular-nums" data-count="{{ $occupiedCount }}">{{ $occupiedCount }}</p>
                            <p class="mt-0.5 text-[0.68rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Occupied Now</p>
                            <p class="mt-0.5 truncate text-[0.7rem] text-hp-text-muted/70">{{ $occupiedReservations }} active reservation{{ $occupiedReservations === 1 ? '' : 's' }}</p>
                        </div>
                    </article>
                    <article class="flex min-w-0 items-center gap-3 rounded-2xl border border-glass-border bg-glass p-4 shadow-glass transition-transform duration-300 hover:-translate-y-0.5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[11px] bg-[#fef3c7] text-[#b45309] dark:bg-[#3a2f14] dark:text-[#e5c35c]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="occupancy-stat__value m-0 font-display text-[1.45rem] font-bold leading-[1.1] text-hp-text tabular-nums" data-count="{{ $reservedCount }}">{{ $reservedCount }}</p>
                            <p class="mt-0.5 text-[0.68rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Reserved Today</p>
                        </div>
                    </article>
                    <article class="flex min-w-0 items-center gap-3 rounded-2xl border border-glass-border bg-glass p-4 shadow-glass transition-transform duration-300 hover:-translate-y-0.5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[11px] bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1e2220] dark:text-[#6ab88c]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="occupancy-stat__value m-0 font-display text-[1.45rem] font-bold leading-[1.1] text-hp-text tabular-nums" data-count="{{ $availableCount }}">{{ $availableCount }}</p>
                            <p class="mt-0.5 text-[0.68rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Available Now</p>
                        </div>
                    </article>
                    <article class="flex min-w-0 items-center gap-3 rounded-2xl border border-glass-border bg-gradient-to-br from-[#e7f3ec]/60 to-transparent p-4 shadow-glass transition-transform duration-300 hover:-translate-y-0.5 dark:from-[#1e2220]/40">
                        <div class="occupancy-rate-ring relative grid h-[3.1rem] w-[3.1rem] shrink-0 place-items-center rounded-full shadow-[inset_0_1px_2px_rgba(23,42,32,0.08)]" style="background: conic-gradient(var(--hp-green) calc(var(--pct) * 1%), var(--glass-border) 0); --pct: {{ $occupancyRate }}">
                            <span class="grid h-[2.15rem] w-[2.15rem] place-items-center rounded-full bg-glass text-[0.68rem] font-bold text-hp-green shadow-[inset_0_1px_2px_rgba(23,42,32,0.06)]">{{ $occupancyRate }}%</span>
                        </div>
                        <div class="min-w-0">
                            <p class="mt-0.5 text-[0.68rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Occupancy Rate</p>
                            <p class="mt-0.5 truncate text-[0.7rem] text-hp-text-muted/70">{{ $inUseCount }} of {{ $totalAmenities }} in use</p>
                        </div>
                    </article>
                    <article class="flex min-w-0 items-center gap-3 rounded-2xl border border-glass-border bg-glass p-4 shadow-glass transition-transform duration-300 hover:-translate-y-0.5 col-span-2 sm:col-span-1">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[11px] bg-[#f1eafd] text-[#7c3aed] dark:bg-[#2b2142] dark:text-[#b79df0]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="occupancy-stat__value m-0 font-display text-[1.45rem] font-bold leading-[1.1] text-hp-text tabular-nums" data-count="{{ $visitorCount }}">{{ $visitorCount }}</p>
                            <p class="mt-0.5 text-[0.68rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Visitors</p>
                            <p class="mt-0.5 truncate text-[0.7rem] text-hp-text-muted/70">Guests inside with no amenity</p>
                        </div>
                    </article>
                </div>

                {{-- Date Range & Active Status Indicator Badge --}}
                <div class="mb-4 flex flex-wrap items-center gap-4 rounded-xl border border-glass-border bg-glass px-4 py-2 text-[0.74rem] text-hp-text-muted shadow-glass" id="occupancyDateRangeBadge">
                    @if ($startDate === $today && $endDate === $today && ($session === 'all' || empty($session)))
                        <span class="inline-flex items-center gap-1.5 font-bold text-hp-green"><i class="h-2 w-2 animate-pulse rounded-full bg-[#22c55e]"></i> Live (Today)</span>
                    @elseif ($startDate === $endDate)
                        <span class="inline-flex items-center gap-1.5 font-bold text-[#0284c7] dark:text-[#38bdf8]"><i class="h-2 w-2 rounded-full bg-[#0284c7]"></i> Date: {{ \Illuminate\Support\Carbon::parse($startDate)->format('M d, Y') }}{{ ($session && $session !== 'all') ? ' (' . ($session === 'daytime' ? 'Daytime' : 'Overnight') . ')' : '' }}</span>
                    @else
                        <span class="inline-flex items-center gap-1.5 font-bold text-[#0284c7] dark:text-[#38bdf8]"><i class="h-2 w-2 rounded-full bg-[#0284c7]"></i> Range: {{ \Illuminate\Support\Carbon::parse($startDate)->format('M d, Y') }} – {{ \Illuminate\Support\Carbon::parse($endDate)->format('M d, Y') }}{{ ($session && $session !== 'all') ? ' (' . ($session === 'daytime' ? 'Daytime' : 'Overnight') . ')' : '' }}</span>
                    @endif
                    <span class="inline-flex items-center gap-1.5 font-semibold"><i class="h-[0.55rem] w-[0.55rem] rounded-full bg-[#dc2626]"></i>Occupied</span>
                    <span class="inline-flex items-center gap-1.5 font-semibold"><i class="h-[0.55rem] w-[0.55rem] rounded-full bg-[#c8a45d]"></i>Reserved</span>
                    <span class="inline-flex items-center gap-1.5 font-semibold"><i class="h-[0.55rem] w-[0.55rem] rounded-full bg-hp-green"></i>Available</span>
                </div>

                {{-- Clean Modern Filter Toolbar --}}
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-glass-border bg-glass p-3 sm:p-3.5 shadow-glass backdrop-blur-md">
                    {{-- Left: Filter Controls --}}
                    <div class="flex flex-wrap items-center gap-2.5">
                        {{-- Date & Session Filter Modal Trigger --}}
                        <button
                            type="button"
                            id="openDateFilterModalBtn"
                            class="h-[38px] inline-flex cursor-pointer items-center gap-2.5 rounded-xl border border-glass-border bg-white/80 dark:bg-[#161a17]/80 hover:bg-glass-hover hover:border-hp-green/40 px-3.5 py-1.5 text-xs font-semibold text-hp-text transition-all shadow-xs"
                            title="Filter by Date & Session"
                        >
                            <i class="bi bi-calendar-range text-emerald-600 dark:text-emerald-400 text-sm"></i>
                            <span id="dateFilterBtnLabel" class="max-w-[240px] truncate">
                                @php
                                    $labelParts = [];
                                    if ($startDate && $endDate) {
                                        if ($startDate === $endDate) {
                                            if ($startDate === $today) {
                                                $labelParts[] = 'Today';
                                            } else {
                                                $labelParts[] = \Illuminate\Support\Carbon::parse($startDate)->format('M d, Y');
                                            }
                                        } else {
                                            $labelParts[] = \Illuminate\Support\Carbon::parse($startDate)->format('m/d') . ' – ' . \Illuminate\Support\Carbon::parse($endDate)->format('m/d');
                                        }
                                    } elseif ($startDate) {
                                        $labelParts[] = \Illuminate\Support\Carbon::parse($startDate)->format('M d, Y');
                                    }

                                    $currSession = $session ?? 'all';
                                    if ($currSession === 'daytime') {
                                        $labelParts[] = 'Daytime';
                                    } elseif ($currSession === 'nighttime' || $currSession === 'overnight') {
                                        $labelParts[] = 'Overnight';
                                    }
                                    $isFiltered = ($startDate !== $today || $endDate !== $today || ($session && $session !== 'all'));
                                @endphp
                                {{ count($labelParts) > 0 ? implode(' • ', $labelParts) : 'Filter by Date & Session' }}
                            </span>
                            <span id="dateFilterActiveDot" class="{{ $isFiltered ? '' : 'hidden' }} h-2 w-2 rounded-full bg-emerald-500 shadow-xs"></span>
                            <i class="bi bi-chevron-down text-[10px] text-hp-text-muted"></i>
                        </button>

                        {{-- Availability Dropdown --}}
                        <div class="relative flex items-center">
                            <i class="bi bi-funnel-fill absolute left-3 text-xs text-hp-text-muted pointer-events-none"></i>
                            <select id="availabilityFilter" class="h-[38px] rounded-xl border border-glass-border bg-white/80 dark:bg-[#161a17]/80 hover:border-hp-green/40 pl-8 pr-7 py-1.5 text-xs font-semibold text-hp-text outline-none focus:border-hp-green cursor-pointer appearance-none shadow-xs transition-colors">
                                <option value="all">All Statuses</option>
                                <option value="available">Available</option>
                                <option value="occupied">Occupied</option>
                                <option value="reserved">Reserved</option>
                                <option value="unavailable">Unavailable (Occupied/Reserved)</option>
                            </select>
                            <i class="bi bi-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] text-hp-text-muted pointer-events-none"></i>
                        </div>

                        {{-- Reset Filter Button --}}
                        <button
                            type="button"
                            id="clearFiltersBtn"
                            class="{{ $isFiltered ? '' : 'hidden' }} h-[38px] inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-glass-border bg-white/80 dark:bg-[#161a17]/80 hover:bg-glass-hover px-3 py-1.5 text-xs font-semibold text-hp-text-muted hover:text-hp-text transition-all shadow-xs"
                            title="Reset to Today & All Sessions"
                        >
                            <i class="bi bi-arrow-counterclockwise text-xs"></i>
                            <span>Reset to Today</span>
                        </button>
                    </div>

                    {{-- Right: Search Input --}}
                    <div class="relative w-full sm:w-64 max-w-full">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-hp-text-muted pointer-events-none"></i>
                        <input
                            type="text"
                            id="searchAmenities"
                            placeholder="Search amenities..."
                            class="h-[38px] w-full rounded-xl border border-glass-border bg-white/80 dark:bg-[#161a17]/80 pl-8 pr-8 text-xs font-medium text-hp-text outline-none focus:border-hp-green placeholder:text-hp-text-muted/60 transition-colors shadow-xs"
                        />
                        <button type="button" id="clearSearchBtn" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer" title="Clear search">
                            <i class="bi bi-x-circle-fill text-xs"></i>
                        </button>
                    </div>
                </div>

                {{-- Dynamic Occupancy Data Container (Categories & Amenity Cards) --}}
                <div id="occupancyDataContainer">

                @php
                    $getCategoryName = function($name) {
                        $trimmed = trim((string) $name);
                        $base = trim(preg_replace('/\s*[-_#]?\s*(?:\d+|[A-Z]\b|[IVXLCDM]+)$/i', '', $trimmed));
                        if (empty($base)) {
                            $base = $trimmed;
                        }
                        if (preg_match('/s$/i', $base)) {
                            return $base;
                        }
                        if (preg_match('/(sh|ch|x|z)$/i', $base)) {
                            return $base . 'es';
                        }
                        if (preg_match('/[^aeiou]y$/i', $base)) {
                            return substr($base, 0, -1) . 'ies';
                        }
                        return $base . 's';
                    };

                    $categories = $amenities->groupBy(function($amenity) use ($getCategoryName) {
                        return $getCategoryName($amenity->amenities_name);
                    });
                @endphp

                @if($categories->count() > 1)
                    <div class="occupancy-category-nav mb-6 flex flex-wrap items-center gap-2" id="occupancyCategoryNav">
                        <button type="button" class="occupancy-cat-pill is-active inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-hp-green bg-hp-green px-3.5 py-1.5 text-xs font-bold text-white shadow-sm transition-all duration-200" data-category-filter="all">
                            <span>All Amenities</span>
                            <span class="rounded-full bg-white/20 px-1.5 py-0.5 text-[10px]">{{ $amenities->count() }}</span>
                        </button>
                        @foreach($categories as $categoryName => $groupAmenities)
                            <button type="button" class="occupancy-cat-pill inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-glass-border bg-glass px-3.5 py-1.5 text-xs font-semibold text-hp-text transition-all duration-200 hover:border-hp-green hover:bg-glass-hover hover:text-hp-green" data-category-filter="{{ $categoryName }}">
                                <span>{{ $categoryName }}</span>
                                <span class="rounded-full bg-glass-hover px-1.5 py-0.5 text-[10px] text-hp-text-muted">{{ $groupAmenities->count() }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                <div id="occupancyGroupsContainer" class="flex flex-col gap-8">
                    @forelse ($categories as $categoryName => $categoryAmenities)
                        <section class="occupancy-category-group" data-category="{{ $categoryName }}">
                            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-glass-border pb-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-hp-green/10 text-hp-green dark:bg-hp-green/20">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                        </svg>
                                    </span>
                                    <h3 class="m-0 font-display text-lg font-bold text-hp-text">{{ $categoryName }}</h3>
                                    <span class="rounded-full bg-hp-green/15 px-2.5 py-0.5 text-xs font-semibold text-hp-green">
                                        {{ $categoryAmenities->count() }} {{ \Illuminate\Support\Str::plural('unit', $categoryAmenities->count()) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-xs font-medium text-hp-text-muted">
                                    @php
                                        $catOccupied = $categoryAmenities->filter(fn($a) => !empty(($occupancyData[$a->id] ?? [])['occupied']))->count();
                                        $catAvailable = $categoryAmenities->count() - $catOccupied;
                                    @endphp
                                    <span class="font-semibold text-hp-green">{{ $catAvailable }} available</span>
                                    @if($catOccupied > 0)
                                        <span>&middot;</span>
                                        <span class="font-semibold text-[#dc2626]">{{ $catOccupied }} occupied</span>
                                    @endif
                                </div>
                            </div>

                            <div class="occupancy-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                                @foreach ($categoryAmenities as $amenity)
                                    @php
                                        $amenityOccupancy = $occupancyData[$amenity->id] ?? ['occupied' => [], 'reserved' => []];

                                        // Determine occupied time slots
                                        $occupiedSlots = [];
                                        foreach ($amenityOccupancy['occupied'] as $occupied) {
                                            if (!empty($occupied['today_slots'])) {
                                                foreach ($occupied['today_slots'] as $s) {
                                                    $occupiedSlots[] = strtolower($s);
                                                }
                                            } else {
                                                $timeSlot = strtolower($occupied['time_slot']);
                                                if (str_contains($timeSlot, 'daytonight')) {
                                                    $occupiedSlots[] = 'daytime';
                                                    $occupiedSlots[] = 'nighttime';
                                                } elseif (str_contains($timeSlot, 'nighttoday')) {
                                                    $occupiedSlots[] = 'nighttime';
                                                } elseif (str_contains($timeSlot, 'daytime')) {
                                                    $occupiedSlots[] = 'daytime';
                                                } elseif (str_contains($timeSlot, 'nighttime')) {
                                                    $occupiedSlots[] = 'nighttime';
                                                }
                                            }
                                        }

                                        // Determine reserved time slots
                                        $reservedSlots = [];
                                        foreach ($amenityOccupancy['reserved'] as $reserved) {
                                            if (!empty($reserved['today_slots'])) {
                                                foreach ($reserved['today_slots'] as $s) {
                                                    $reservedSlots[] = strtolower($s);
                                                }
                                            } else {
                                                $timeSlot = strtolower($reserved['time_slot']);
                                                if (str_contains($timeSlot, 'daytonight')) {
                                                    $reservedSlots[] = 'daytime';
                                                    $reservedSlots[] = 'nighttime';
                                                } elseif (str_contains($timeSlot, 'nighttoday')) {
                                                    $reservedSlots[] = 'nighttime';
                                                } elseif (str_contains($timeSlot, 'daytime')) {
                                                    $reservedSlots[] = 'daytime';
                                                } elseif (str_contains($timeSlot, 'nighttime')) {
                                                    $reservedSlots[] = 'nighttime';
                                                }
                                            }
                                        }

                                        // Combine occupied and reserved slots
                                        $occupiedSlots = array_values(array_unique($occupiedSlots));
                                        $reservedSlots = array_values(array_unique($reservedSlots));
                                        $unavailableSlots = array_values(array_unique(array_merge($occupiedSlots, $reservedSlots)));

                                        // Determine available slots (all slots minus unavailable)
                                        $allSlots = ['daytime', 'nighttime'];
                                        $availableSlots = array_values(array_diff($allSlots, $unavailableSlots));

                                        // Card status badge
                                        $isOccupied = ! empty($amenityOccupancy['occupied']);
                                        $isReserved = ! empty($amenityOccupancy['reserved']);
                                        if ($isOccupied) {
                                            $cardStatus = 'occupied';
                                            $cardStatusLabel = 'Occupied';
                                        } elseif ($isReserved) {
                                            $cardStatus = 'reserved';
                                            $cardStatusLabel = 'Reserved';
                                        } else {
                                            $cardStatus = 'available';
                                            $cardStatusLabel = 'Available';
                                        }
                                        $hasDay = in_array('daytime', $availableSlots);
                                        $hasNight = in_array('nighttime', $availableSlots);
                                    @endphp
                                    <div class="occupancy-card group relative cursor-pointer overflow-hidden rounded-2xl bg-glass shadow-glass transition-all duration-300 hover:-translate-y-1 hover:shadow-glass"
                                         data-amenity-id="{{ $amenity->id }}"
                                         data-amenity-name="{{ strtolower($amenity->amenities_name) }}"
                                         data-display-name="{{ e($amenity->amenities_name) }}"
                                         data-daytime-price="₱{{ number_format($amenity->daytime_price, 2) }}"
                                         data-nighttime-price="₱{{ number_format($amenity->nighttime_price, 2) }}"
                                         data-daytime-aircon-price="{{ $amenity->daytime_aircon_price ? '₱'.number_format($amenity->daytime_aircon_price, 2) : 'N/A' }}"
                                         data-nighttime-aircon-price="{{ $amenity->nighttime_aircon_price ? '₱'.number_format($amenity->nighttime_aircon_price, 2) : 'N/A' }}"
                                         data-additional-per-head="{{ $amenity->additional_per_head ? '₱'.number_format($amenity->additional_per_head, 2) : 'N/A' }}"
                                         data-min-cap="{{ $amenity->minimum_capacity ?? 'N/A' }}"
                                         data-max-cap="{{ $amenity->maximum_capacity ?? 'N/A' }}"
                                         data-description="{{ e($amenity->description ?? 'No description available for this amenity.') }}"
                                         data-image-src="{{ $amenity->image ? asset('storage/' . $amenity->image) : '' }}"
                                         data-occupied-json="{{ json_encode($amenityOccupancy['occupied']) }}"
                                         data-reserved-json="{{ json_encode($amenityOccupancy['reserved']) }}"
                                         data-available-slots="{{ implode(',', $availableSlots) }}"
                                         data-unavailable-slots="{{ implode(',', $unavailableSlots) }}">
                                        <div class="relative aspect-[4/3] w-full overflow-hidden bg-hp-cream">
                                            @if ($amenity->image)
                                                <img src="{{ asset('storage/' . $amenity->image) }}" alt="{{ $amenity->amenities_name }}" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                            @else
                                                <div class="flex h-full w-full items-center justify-center text-hp-text-muted">
                                                    <svg class="h-12 w-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                                    </svg>
                                                </div>
                                            @endif
                                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[rgba(13,44,29,0.7)] to-transparent dark:from-black/70"></div>
                                            <span class="occupancy-card__badge absolute left-3 top-3 z-[5] inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[0.66rem] font-bold uppercase tracking-[0.05em] text-white shadow-glass backdrop-blur-sm occupancy-card__badge--{{ $cardStatus }}">
                                                <i class="h-1 w-1 rounded-full bg-white/90"></i>{{ $cardStatusLabel }}
                                            </span>
                                            @php
                                                $allReservations = [];
                                                if (!empty($amenityOccupancy['occupied'])) {
                                                    foreach ($amenityOccupancy['occupied'] as $occupied) {
                                                        $allReservations[] = ['id' => $occupied['reservation_id'], 'type' => 'occupied'];
                                                    }
                                                }
                                                if (!empty($amenityOccupancy['reserved'])) {
                                                    foreach ($amenityOccupancy['reserved'] as $reserved) {
                                                        $allReservations[] = ['id' => $reserved['reservation_id'], 'type' => 'reserved'];
                                                    }
                                                }
                                                $displayReservations = array_slice($allReservations, 0, 4);
                                                $overflowCount = count($allReservations) - 4;
                                            @endphp

                                            {{-- Occupied circles at top right --}}
                                            @if (!empty($amenityOccupancy['occupied']))
                                                @foreach ($amenityOccupancy['occupied'] as $index => $occupied)
                                                    @if ($index < 2)
                                                        <div class="absolute top-3 {{ $index === 0 ? 'right-3' : 'right-14' }} z-10 flex h-10 w-10 items-center justify-center rounded-full border-2 border-white bg-[#dc2626] text-[0.75rem] font-bold text-white shadow-lg">
                                                            #{{ $occupied['reservation_id'] }}
                                                        </div>
                                                    @endif
                                                @endforeach
                                            @endif

                                            {{-- Reserved circles at bottom --}}
                                            @if (!empty($amenityOccupancy['reserved']))
                                                @foreach ($amenityOccupancy['reserved'] as $index => $reserved)
                                                    @if ($index < 2)
                                                        <div class="absolute bottom-3 {{ $index === 0 ? 'right-3' : 'right-14' }} z-10 flex h-10 w-10 items-center justify-center rounded-full border-2 border-white bg-[#c8a45d] text-[0.75rem] font-bold text-white shadow-lg">
                                                            #{{ $reserved['reservation_id'] }}
                                                        </div>
                                                    @endif
                                                @endforeach
                                            @endif

                                            {{-- Overflow indicator if more than 4 total --}}
                                            @if ($overflowCount > 0)
                                                <div class="absolute bottom-3 right-14 z-10 flex h-10 w-10 items-center justify-center rounded-full border-2 border-white bg-[#6b7280] text-[0.7rem] font-bold text-white shadow-lg">
                                                    +{{ $overflowCount }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="occupancy-card__content relative flex flex-col gap-3 bg-glass p-4">
                                            <div class="flex items-start justify-between gap-2.5">
                                                <h4 class="occupancy-card__name m-0 font-display text-lg font-semibold leading-[1.3] text-hp-text dark:text-[#f3f4f6]">{{ $amenity->amenities_name }}</h4>
                                                <span class="shrink-0 whitespace-nowrap text-[0.8rem] font-bold text-hp-green">₱{{ number_format($amenity->daytime_price, 2) }}<small class="text-[0.62rem] font-semibold text-hp-text-muted/70">/day</small></span>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <span class="slot-chip slot-chip--daytime inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[0.68rem] font-bold tracking-[0.02em] shadow-[inset_0_1px_0_rgba(255,255,255,0.35)] {{ $hasDay ? 'is-free border-[rgba(23,138,82,0.28)] bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1e2220] dark:text-[#6ab88c]' : 'is-taken border-[rgba(207,75,71,0.25)] bg-[#fde8e8] text-[#b91c1c] dark:bg-[#3a1f1c] dark:text-[#f3a0a0]' }}">
                                                    <i class="slot-chip__dot h-[0.42rem] w-[0.42rem] rounded-full {{ $hasDay ? 'bg-hp-green' : 'bg-[#dc2626]' }}"></i>Daytime
                                                </span>
                                                <span class="slot-chip slot-chip--nighttime inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[0.68rem] font-bold tracking-[0.02em] shadow-[inset_0_1px_0_rgba(255,255,255,0.35)] {{ $hasNight ? 'is-free border-[rgba(23,138,82,0.28)] bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1e2220] dark:text-[#6ab88c]' : 'is-taken border-[rgba(207,75,71,0.25)] bg-[#fde8e8] text-[#b91c1c] dark:bg-[#3a1f1c] dark:text-[#f3a0a0]' }}">
                                                    <i class="slot-chip__dot h-[0.42rem] w-[0.42rem] rounded-full {{ $hasNight ? 'bg-hp-green' : 'bg-[#dc2626]' }}"></i>Overnight
                                                </span>
                                            </div>
                                            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-glass-border pt-2.5 text-[0.72rem] text-hp-text-muted">
                                                <span class="inline-flex items-center gap-1.5 font-semibold">
                                                    <svg class="h-3.5 w-3.5 text-hp-text-muted/70" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                                    {{ $amenity->minimum_capacity }}–{{ $amenity->maximum_capacity }} pax
                                                </span>
                                                <span class="font-semibold text-hp-text-muted/70">Overnight ₱{{ number_format($amenity->nighttime_price, 2) }}</span>
                                            </div>
                                            <div class="flex items-center justify-between gap-2 text-[0.72rem] font-bold text-hp-green opacity-60 transition-opacity duration-200 group-hover:opacity-100">
                                                <span>View details</span>
                                                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/></svg>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <div class="occupancy-empty col-span-full flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-glass-border-strong bg-glass px-4 py-16 text-center text-hp-text-muted shadow-glass">
                            <svg class="h-16 w-16 opacity-50" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                            </svg>
                            <p class="m-0 text-base">No amenities found</p>
                        </div>
                    @endforelse
                </div>
                </div> {{-- End #occupancyDataContainer --}}
            </main>
        </div>
    </div>

    <!-- Amenity Detail Modal -->
    <div class="modal fixed inset-0 z-[1000] flex items-center justify-center p-4 opacity-0 transition-all duration-250 invisible is-open:visible is-open:opacity-100" id="amenityDetailModal" aria-hidden="true">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="closeAmenityDetailModal"></div>
        <div class="amenity-detail-panel relative z-[1] flex max-h-[90vh] w-full max-w-[600px] flex-col overflow-hidden rounded-2xl bg-glass shadow-glass dark:bg-glass">
            <div class="flex items-center justify-between border-b border-[#e5e7eb] px-6 py-5 dark:border-glass-border">
                <h3 class="m-0 font-display text-xl text-hp-green-dark dark:text-[#f3f4f6]" id="modalAmenityTitle">Amenity Details</h3>
                <button type="button" class="modal__close cursor-pointer border-0 bg-transparent text-2xl leading-none text-hp-text-muted" id="closeAmenityDetailModalBtn">&times;</button>
            </div>
            <div class="amenity-detail-body flex flex-col gap-5 overflow-y-auto p-6">
                <div class="amenity-detail-img-wrap h-[200px] w-full overflow-hidden rounded-xl bg-glass-hover dark:bg-[#0d2812]">
                    <img id="modalAmenityImg" src="" alt="Amenity Image" class="h-full w-full object-cover" style="display:none;">
                    <div id="modalAmenityImgPlaceholder" class="amenity-detail-placeholder flex h-full w-full items-center justify-center text-hp-text-muted" style="display:none;">
                        <span>No Image Available</span>
                    </div>
                </div>
                <div class="amenity-detail-info flex flex-col gap-5">
                    <p class="amenity-detail-desc m-0 text-[0.95rem] leading-relaxed text-hp-text-muted" id="modalAmenityDesc"></p>
                    <div class="amenity-detail-grid grid grid-cols-2 gap-4 rounded-xl bg-glass-hover p-4 dark:bg-[#0d2812]">
                        <div class="detail-item flex flex-col gap-1">
                            <span class="detail-label text-xs font-semibold uppercase tracking-[0.05em] text-hp-text-muted">Daytime Price</span>
                            <span class="detail-val text-base font-bold text-hp-green-dark dark:text-[#9ca3af]" id="modalDaytimePrice"></span>
                        </div>
                        <div class="detail-item flex flex-col gap-1">
                            <span class="detail-label text-xs font-semibold uppercase tracking-[0.05em] text-hp-text-muted">Overnight Price</span>
                            <span class="detail-val text-base font-bold text-hp-green-dark dark:text-[#9ca3af]" id="modalNighttimePrice"></span>
                        </div>
                        <div class="detail-item flex flex-col gap-1">
                            <span class="detail-label text-xs font-semibold uppercase tracking-[0.05em] text-hp-text-muted">Day Aircon</span>
                            <span class="detail-val text-base font-bold text-hp-green-dark dark:text-[#9ca3af]" id="modalDayAircon"></span>
                        </div>
                        <div class="detail-item flex flex-col gap-1">
                            <span class="detail-label text-xs font-semibold uppercase tracking-[0.05em] text-hp-text-muted">Overnight Aircon</span>
                            <span class="detail-val text-base font-bold text-hp-green-dark dark:text-[#9ca3af]" id="modalNightAircon"></span>
                        </div>
                        <div class="detail-item flex flex-col gap-1">
                            <span class="detail-label text-xs font-semibold uppercase tracking-[0.05em] text-hp-text-muted">Additional / Head</span>
                            <span class="detail-val text-base font-bold text-hp-green-dark dark:text-[#9ca3af]" id="modalAddHead"></span>
                        </div>
                        <div class="detail-item flex flex-col gap-1">
                            <span class="detail-label text-xs font-semibold uppercase tracking-[0.05em] text-hp-text-muted">Capacity</span>
                            <span class="detail-val text-base font-bold text-hp-green-dark dark:text-[#9ca3af]" id="modalCapacity"></span>
                        </div>
                    </div>
                    <div class="amenity-detail-status-section">
                        <h4 class="m-0 mb-3 text-sm font-bold text-hp-text dark:text-[#f3f4f6]">Current Status & Active Reservations</h4>
                        <div id="modalStatusList" class="status-list flex flex-col gap-2"></div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end border-t border-[#e5e7eb] px-6 py-4 dark:border-glass-border">
                <button type="button" class="btn btn--secondary cursor-pointer rounded-lg border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:-translate-y-px hover:border-glass-border-strong hover:bg-glass-hover" id="closeAmenityDetailModalFooter">Close</button>
            </div>
        </div>
    </div>

    {{-- DATE & SESSION FILTER MODAL --}}
    <div
        id="dateFilterModal"
        class="hidden fixed inset-0 z-[2000] items-center justify-center p-4 bg-black/60 backdrop-blur-xs transition-opacity print:hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="dateFilterModalTitle"
    >
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-[#141715] border border-gray-200 dark:border-neutral-800 shadow-2xl overflow-hidden flex flex-col">
            
            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-neutral-800 bg-gray-50/50 dark:bg-neutral-900/40">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300">
                        <i class="bi bi-calendar-range text-sm"></i>
                    </span>
                    <div>
                        <h3 id="dateFilterModalTitle" class="m-0 text-sm font-bold text-gray-900 dark:text-neutral-100">Filter by Date &amp; Session</h3>
                        <p class="m-0 text-[11px] text-gray-500 dark:text-neutral-400">Configure date range and operating session</p>
                    </div>
                </div>
                <button
                    type="button"
                    id="closeDateFilterModalBtn"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-black/5 dark:hover:bg-white/5 cursor-pointer transition-colors"
                    aria-label="Close"
                >
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-5 space-y-4 text-xs">
                
                {{-- Date Section (Start & End Dates) --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block font-bold text-gray-900 dark:text-neutral-100">Select Date</label>
                        <span class="text-[10px] text-gray-500 dark:text-neutral-400">Leave End empty for single date</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <span class="block text-[11px] text-gray-500 dark:text-neutral-400 mb-1 font-semibold">Start Date</span>
                            <div class="relative">
                                <i class="bi bi-calendar-event absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none"></i>
                                <input
                                    type="date"
                                    id="modalStartDateInput"
                                    value="{{ $startDate }}"
                                    class="h-9 w-full appearance-none rounded-xl border border-gray-200 dark:border-neutral-800 bg-gray-50 dark:bg-neutral-900/60 pl-8 pr-2.5 text-xs text-gray-900 dark:text-neutral-100 outline-none focus:border-emerald-500 cursor-pointer transition-colors font-medium"
                                >
                            </div>
                        </div>
                        <div>
                            <span class="block text-[11px] text-gray-500 dark:text-neutral-400 mb-1 font-semibold">End Date <span class="text-[10px] font-normal text-gray-400">(optional)</span></span>
                            <div class="relative">
                                <i class="bi bi-calendar-check absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none"></i>
                                <input
                                    type="date"
                                    id="modalEndDateInput"
                                    value="{{ $endDate !== $startDate ? $endDate : '' }}"
                                    class="h-9 w-full appearance-none rounded-xl border border-gray-200 dark:border-neutral-800 bg-gray-50 dark:bg-neutral-900/60 pl-8 pr-2.5 text-xs text-gray-900 dark:text-neutral-100 outline-none focus:border-emerald-500 cursor-pointer transition-colors font-medium"
                                >
                            </div>
                        </div>
                    </div>

                    {{-- Quick Presets --}}
                    <div class="flex flex-wrap items-center gap-1.5 pt-1">
                        <span class="text-[10px] font-semibold text-gray-500 dark:text-neutral-400 mr-0.5">Quick:</span>
                        <button type="button" id="presetTodayBtn" data-preset="today" class="px-2 py-0.5 rounded-md border border-gray-200 dark:border-neutral-800 bg-gray-50 dark:bg-neutral-900/40 text-[11px] font-semibold text-gray-600 dark:text-neutral-400 hover:border-emerald-500 hover:text-emerald-600 dark:hover:text-emerald-400 cursor-pointer transition-all">
                            Today
                        </button>
                        <button type="button" id="presetTomorrowBtn" data-preset="tomorrow" class="px-2 py-0.5 rounded-md border border-gray-200 dark:border-neutral-800 bg-gray-50 dark:bg-neutral-900/40 text-[11px] font-semibold text-gray-600 dark:text-neutral-400 hover:border-emerald-500 hover:text-emerald-600 dark:hover:text-emerald-400 cursor-pointer transition-all">
                            Tomorrow
                        </button>
                        <button type="button" id="presetThisWeekendBtn" data-preset="weekend" class="px-2 py-0.5 rounded-md border border-gray-200 dark:border-neutral-800 bg-gray-50 dark:bg-neutral-900/40 text-[11px] font-semibold text-gray-600 dark:text-neutral-400 hover:border-emerald-500 hover:text-emerald-600 dark:hover:text-emerald-400 cursor-pointer transition-all">
                            This Weekend
                        </button>
                        <button type="button" id="presetThisWeekBtn" data-preset="week" class="px-2 py-0.5 rounded-md border border-gray-200 dark:border-neutral-800 bg-gray-50 dark:bg-neutral-900/40 text-[11px] font-semibold text-gray-600 dark:text-neutral-400 hover:border-emerald-500 hover:text-emerald-600 dark:hover:text-emerald-400 cursor-pointer transition-all">
                            This Week
                        </button>
                        <button type="button" id="presetThisMonthBtn" data-preset="month" class="px-2 py-0.5 rounded-md border border-gray-200 dark:border-neutral-800 bg-gray-50 dark:bg-neutral-900/40 text-[11px] font-semibold text-gray-600 dark:text-neutral-400 hover:border-emerald-500 hover:text-emerald-600 dark:hover:text-emerald-400 cursor-pointer transition-all">
                            This Month
                        </button>
                    </div>
                </div>

                {{-- Subtle Divider --}}
                <div class="border-t border-gray-200 dark:border-neutral-800 pt-3">
                    {{-- Operating Session --}}
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <label for="modalSessionSelect" class="block font-semibold text-gray-900 dark:text-neutral-100 text-[11px]">Operating Session</label>
                            <p class="m-0 text-[10px] text-gray-500 dark:text-neutral-400">Filter amenity occupancy by session</p>
                        </div>
                        <div class="relative min-w-[190px]">
                            <select
                                id="modalSessionSelect"
                                class="h-8 w-full appearance-none rounded-lg border border-gray-200 dark:border-neutral-800 bg-gray-50 dark:bg-neutral-900/60 pl-2.5 pr-7 text-xs text-gray-900 dark:text-neutral-100 outline-none focus:border-emerald-500 cursor-pointer transition-colors"
                            >
                                <option value="all" {{ ($session === 'all' || empty($session)) ? 'selected' : '' }}>All Sessions (24 hrs)</option>
                                <option value="daytime" {{ ($session === 'daytime') ? 'selected' : '' }}>Daytime (8:00 AM – 5:00 PM)</option>
                                <option value="nighttime" {{ ($session === 'nighttime') ? 'selected' : '' }}>Overnight (6:00 PM – 8:00 AM)</option>
                            </select>
                            <i class="bi bi-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 pointer-events-none"></i>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between gap-3 px-5 py-3.5 border-t border-gray-200 dark:border-neutral-800 bg-gray-50/50 dark:bg-neutral-900/40">
                <button
                    type="button"
                    id="modalResetFilterBtn"
                    class="h-9 px-3.5 rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-800 text-xs font-semibold text-gray-600 dark:text-neutral-400 hover:border-rose-400 hover:text-rose-600 dark:hover:text-rose-400 cursor-pointer transition-all"
                >
                    Reset
                </button>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        id="modalCancelFilterBtn"
                        class="h-9 px-4 rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-800 text-xs font-semibold text-gray-900 dark:text-neutral-100 hover:bg-black/5 dark:hover:bg-white/5 cursor-pointer transition-all"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        id="modalApplyFilterBtn"
                        class="h-9 px-5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs cursor-pointer transition-all"
                    >
                        Apply Filter
                    </button>
                </div>
            </div>

        </div>
    </div>

    <x-staff_chatbot />
</body>
</html>
