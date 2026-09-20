<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Shift & Activity Report — Hinaguan Nature Park</title>
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite([
        'resources/css/app.css',
        'resources/css/homepage.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/staff_sidemenu.css',
        'resources/css/chatbot.css',
        'resources/css/staff_css/staff_shared.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/staff_js/staff_reports.js',
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
        /* Media print formatting: only print the official slip */

        /* STRICT PRINT FORMATTING: Only print the Official Handover Slip */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            body * {
                visibility: hidden !important;
            }
            #printableHandoverSlip,
            #printableHandoverSlip * {
                visibility: visible !important;
            }
            #printableHandoverSlip {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                display: block !important;
                background: #ffffff !important;
                color: #000000 !important;
                padding: 24px !important;
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body class="antialiased staff-portal">
    <div class="dash-layout">
        <x-staff_sidemenu active="reports" userName="{{ session('auth_user.name') ?? 'Staff User' }}" userRole="Staff" />

        <div class="dash-main">
            <x-header
                title="Staff Shift & Activity Report"
                subtitle="Personal shift performance, cash collections, and guest transaction ledger"
            />

            <main class="dash-content p-4 sm:p-6 space-y-6">

                {{-- UNIFIED EXECUTIVE SHIFT TOOLBAR & FILTER CONSOLE --}}
                <div class="rounded-2xl border border-glass-border bg-glass p-5 shadow-glass backdrop-blur-md">
                    {{-- Row 1: Staff Identity & Primary Shift Actions --}}
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5 pb-5 border-b border-glass-border">
                        {{-- Staff Identity Left Column --}}
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#1c5c3c] text-white shadow-md">
                                <i class="bi bi-person-badge text-2xl"></i>
                            </div>
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h1 class="m-0 text-xl md:text-2xl font-display font-bold text-hp-text tracking-tight">{{ $staffName }}</h1>
                                    <span class="inline-flex items-center gap-1 rounded-md bg-[#1c5c3c]/15 px-2.5 py-0.5 text-xs font-semibold text-[#1c5c3c] dark:text-[#6ab88c]">
                                        <i class="bi bi-shield-check"></i> Staff ID #{{ $staffId }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 rounded-md {{ $currentSession === 'Daytime' ? 'bg-amber-500/15 text-amber-700 dark:text-amber-300' : 'bg-indigo-500/15 text-indigo-700 dark:text-indigo-300' }} px-2.5 py-0.5 text-xs font-semibold">
                                        @if($currentSession === 'Daytime')
                                            <i class="bi bi-sun-fill text-amber-500"></i> Daytime Session (08:00 AM – 05:00 PM)
                                        @else
                                            <i class="bi bi-moon-stars-fill text-indigo-400"></i> Overnight Session (05:00 PM – 08:00 AM)
                                        @endif
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 text-xs text-hp-text-muted">
                                    <span class="inline-flex items-center gap-1">
                                        <i class="bi bi-calendar3"></i> {{ now()->format('l, F j, Y') }}
                                    </span>
                                    <span>•</span>
                                    <span id="filterViewingText">Viewing: <strong class="text-hp-text">{{ ucwords(str_replace('_', ' ', $preset)) }}</strong> ({{ $filterFrom ? \Carbon\Carbon::parse($filterFrom)->format('M d, Y') : 'Start' }} → {{ $filterTo ? \Carbon\Carbon::parse($filterTo)->format('M d, Y') : 'End' }})</span>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons Right Column --}}
                        <div class="flex flex-wrap items-center gap-3">
                            {{-- Shift Activity Ledger Modal Trigger --}}
                            <button type="button" id="openLedgerBtn" class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-glass-border bg-white/70 dark:bg-black/20 hover:bg-glass-hover px-4 py-2.5 text-sm font-semibold text-hp-text shadow-sm transition-all duration-200">
                                <i class="bi bi-journal-text text-base text-[#1c5c3c] dark:text-[#6ab88c]"></i>
                                <span>Shift Activity Ledger</span>
                                <span id="ledgerCountBadge" class="rounded-full bg-[#1c5c3c]/15 text-[#1c5c3c] dark:text-[#6ab88c] px-2.5 py-0.5 text-xs font-bold">{{ $ledgerRows->count() }}</span>
                            </button>

                            {{-- Handover Slip Modal Trigger --}}
                            <button type="button" id="openHandoverModalBtn" class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-[#1c5c3c] hover:bg-[#14402b] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200">
                                <i class="bi bi-printer-fill text-base"></i>
                                <span>Print Handover Slip</span>
                            </button>
                        </div>
                    </div>

                    {{-- Row 2: Streamlined Filter Bar --}}
                    <form method="GET" action="{{ route('staff.reports') }}" id="reportFilterForm" class="pt-4 space-y-3">
                        <input type="hidden" name="preset" id="presetInput" value="{{ $preset }}">

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            {{-- Quick Period Pills --}}
                            <div id="periodPillsContainer" class="flex flex-wrap items-center gap-1.5">
                                <span class="text-xs font-bold uppercase tracking-wider text-hp-text-muted mr-1.5 flex items-center gap-1">
                                    <i class="bi bi-calendar-check"></i> Period:
                                </span>
                                @php
                                    $periods = [
                                        'today' => 'Today',
                                        'yesterday' => 'Yesterday',
                                        'this_week' => 'This Week',
                                        'this_month' => 'This Month',
                                        'all' => 'All Time'
                                    ];
                                @endphp
                                @foreach($periods as $key => $label)
                                    <a href="{{ route('staff.reports', ['preset' => $key, 'session' => $sessionFilter, 'action' => $actionFilter]) }}"
                                       data-preset="{{ $key }}"
                                       class="period-pill rounded-lg px-3 py-1.5 text-xs font-semibold transition-all duration-150 {{ $preset === $key ? 'bg-[#1c5c3c] text-white shadow-sm' : 'bg-black/5 dark:bg-white/5 text-hp-text hover:bg-black/10 dark:hover:bg-white/10' }}">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>

                            {{-- Dropdown Filters & Custom Date Toggle --}}
                            <div class="flex flex-wrap items-center gap-2.5">
                                {{-- Shift Dropdown --}}
                                <div class="relative flex items-center">
                                    <i class="bi bi-clock-history absolute left-3 text-xs text-hp-text-muted pointer-events-none"></i>
                                    <select name="session" id="sessionSelect" class="rounded-xl border border-glass-border bg-white/70 dark:bg-[#161a17]/80 pl-8 pr-7 py-1.5 text-xs font-medium text-hp-text outline-none focus:border-[#1c5c3c] cursor-pointer appearance-none shadow-sm">
                                        <option value="all" {{ $sessionFilter === 'all' ? 'selected' : '' }}>All Shifts</option>
                                        <option value="daytime" {{ $sessionFilter === 'daytime' ? 'selected' : '' }}>Daytime Shift (8AM - 5PM)</option>
                                        <option value="nighttime" {{ $sessionFilter === 'nighttime' ? 'selected' : '' }}>Overnight Shift (5PM - 8AM)</option>
                                    </select>
                                    <i class="bi bi-chevron-down absolute right-2.5 text-[0.6rem] text-hp-text-muted pointer-events-none"></i>
                                </div>

                                {{-- Action Dropdown --}}
                                <div class="relative flex items-center">
                                    <i class="bi bi-funnel-fill absolute left-3 text-xs text-hp-text-muted pointer-events-none"></i>
                                    <select name="action" id="actionSelect" class="rounded-xl border border-glass-border bg-white/70 dark:bg-[#161a17]/80 pl-8 pr-7 py-1.5 text-xs font-medium text-hp-text outline-none focus:border-[#1c5c3c] cursor-pointer appearance-none shadow-sm">
                                        <option value="all" {{ $actionFilter === 'all' ? 'selected' : '' }}>All Logged Actions</option>
                                        <option value="checked_in" {{ $actionFilter === 'checked_in' ? 'selected' : '' }}>Check-Ins Only</option>
                                        <option value="checked_out" {{ $actionFilter === 'checked_out' ? 'selected' : '' }}>Check-Outs Only</option>
                                        <option value="additional_charge_paid" {{ $actionFilter === 'additional_charge_paid' ? 'selected' : '' }}>Damage / Incident Fees</option>
                                        <option value="added_amenity" {{ $actionFilter === 'added_amenity' ? 'selected' : '' }}>Amenities Added</option>
                                        <option value="companion_added" {{ $actionFilter === 'companion_added' ? 'selected' : '' }}>Companions Added</option>
                                        <option value="reservation_extended" {{ $actionFilter === 'reservation_extended' ? 'selected' : '' }}>Extensions</option>
                                        <option value="cancelled" {{ $actionFilter === 'cancelled' ? 'selected' : '' }}>Cancellations</option>
                                        <option value="no_show" {{ $actionFilter === 'no_show' ? 'selected' : '' }}>No-Shows</option>
                                    </select>
                                    <i class="bi bi-chevron-down absolute right-2.5 text-[0.6rem] text-hp-text-muted pointer-events-none"></i>
                                </div>

                                {{-- Custom Date Range Button --}}
                                <button type="button" id="toggleDateRangeBtn" class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-glass-border bg-white/70 dark:bg-[#161a17]/80 px-3 py-1.5 text-xs font-semibold text-hp-text hover:bg-glass-hover transition-colors shadow-sm">
                                    <i class="bi bi-calendar-range text-hp-text-muted"></i>
                                    <span>Date Range</span>
                                    <i id="dateRangeChevron" class="bi bi-chevron-down text-[0.6rem] text-hp-text-muted transition-transform duration-200"></i>
                                </button>

                                {{-- Filter Submit Button --}}
                                <button type="submit" class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-[#1c5c3c] hover:bg-[#14402b] px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm transition-all">
                                    <i class="bi bi-funnel"></i>
                                    <span>Filter</span>
                                </button>

                                <span id="resetFilterContainer">
                                    @if($sessionFilter !== 'all' || $actionFilter !== 'all' || $preset !== 'today')
                                        <a href="{{ route('staff.reports', ['preset' => 'today']) }}" data-reset-filter="true" class="inline-flex cursor-pointer items-center gap-1 rounded-xl border border-glass-border bg-white/70 dark:bg-[#161a17]/80 px-2.5 py-1.5 text-xs font-medium text-hp-text-muted hover:text-hp-text hover:bg-glass-hover transition-colors" title="Reset Filters">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </a>
                                    @endif
                                </span>
                            </div>
                        </div>

                        {{-- Collapsible Custom Date Range Picker (Default: Closed) --}}
                        <div id="customDateRangeRow" class="hidden flex-wrap items-center gap-3 pt-3 border-t border-glass-border/60 text-xs">
                            <span class="font-semibold text-hp-text-muted flex items-center gap-1">
                                <i class="bi bi-arrow-right-short"></i> Custom Range:
                            </span>
                            <div class="flex items-center gap-2">
                                <label for="dateFromInput" class="text-hp-text-muted">From:</label>
                                <input type="date" name="date_from" id="dateFromInput" value="{{ $filterFrom }}" class="rounded-xl border border-glass-border bg-white dark:bg-[#161a17] px-3 py-1.5 text-xs text-hp-text outline-none focus:border-[#1c5c3c]">
                            </div>
                            <div class="flex items-center gap-2">
                                <label for="dateToInput" class="text-hp-text-muted">To:</label>
                                <input type="date" name="date_to" id="dateToInput" value="{{ $filterTo }}" class="rounded-xl border border-glass-border bg-white dark:bg-[#161a17] px-3 py-1.5 text-xs text-hp-text outline-none focus:border-[#1c5c3c]">
                            </div>
                            <button type="submit" class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-[#1c5c3c] px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-[#14402b]">
                                <i class="bi bi-check-lg"></i> Apply Range
                            </button>
                            <a href="{{ route('staff.reports', ['preset' => 'today']) }}" data-reset-filter="true" class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-glass-border px-3 py-1.5 text-xs font-medium text-hp-text-muted hover:text-hp-text hover:bg-glass-hover">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>

                {{-- DYNAMIC DATA WRAPPER (KPIs & Shift Reconciliation) --}}
                <div id="reportsDataContainer" class="space-y-6 transition-opacity duration-150">

                    {{-- KPI METRICS CARDS (6 METRICS) --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                    {{-- 1. Total Collections --}}
                    <article class="rounded-2xl border border-glass-border bg-glass p-4 shadow-glass flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold uppercase tracking-wider text-hp-text-muted">Total Collections</span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-700 dark:text-emerald-400">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div>
                            <div class="text-2xl font-display font-bold text-emerald-600 dark:text-emerald-400 leading-tight">
                                ₱{{ number_format($totalCollections, 2) }}
                            </div>
                            <p class="text-[0.7rem] text-hp-text-muted mt-1 font-medium">Personally collected on this shift</p>
                        </div>
                    </article>

                    {{-- 2. Check-Ins --}}
                    <article class="rounded-2xl border border-glass-border bg-glass p-4 shadow-glass flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold uppercase tracking-wider text-hp-text-muted">Check-Ins</span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-green-500/15 text-green-700 dark:text-green-400">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                            </div>
                        </div>
                        <div>
                            <div class="text-2xl font-display font-bold text-hp-text leading-tight">
                                {{ $checkInsCount }}
                            </div>
                            <p class="text-[0.7rem] text-hp-text-muted mt-1 font-medium">₱{{ number_format($checkInCollections, 2) }} entrance/room fees</p>
                        </div>
                    </article>

                    {{-- 3. Check-Outs --}}
                    <article class="rounded-2xl border border-glass-border bg-glass p-4 shadow-glass flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold uppercase tracking-wider text-hp-text-muted">Check-Outs</span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-500/15 text-sky-700 dark:text-sky-400">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </div>
                        </div>
                        <div>
                            <div class="text-2xl font-display font-bold text-hp-text leading-tight">
                                {{ $checkOutsCount }}
                            </div>
                            <p class="text-[0.7rem] text-hp-text-muted mt-1 font-medium">Completed departures</p>
                        </div>
                    </article>

                    {{-- 4. Guests Handled --}}
                    <article class="rounded-2xl border border-glass-border bg-glass p-4 shadow-glass flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold uppercase tracking-wider text-hp-text-muted">Guests Handled</span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-500/15 text-blue-700 dark:text-blue-400">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                        </div>
                        <div>
                            <div class="text-2xl font-display font-bold text-hp-text leading-tight">
                                {{ $totalGuestsHandled }}
                            </div>
                            <p class="text-[0.7rem] text-hp-text-muted mt-1 font-medium">Across {{ $totalReservationsHandled }} reservations</p>
                        </div>
                    </article>

                    {{-- 5. Damage Charges --}}
                    <article class="rounded-2xl border border-glass-border bg-glass p-4 shadow-glass flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold uppercase tracking-wider text-hp-text-muted">Damage Fees</span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/15 text-amber-700 dark:text-amber-400">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                        </div>
                        <div>
                            <div class="text-2xl font-display font-bold text-amber-700 dark:text-amber-400 leading-tight">
                                ₱{{ number_format($damageChargesCollected, 2) }}
                            </div>
                            <p class="text-[0.7rem] text-hp-text-muted mt-1 font-medium">{{ $damageChargesCount }} incident collections</p>
                        </div>
                    </article>

                    {{-- 6. Added Amenities & Extras --}}
                    <article class="rounded-2xl border border-glass-border bg-glass p-4 shadow-glass flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold uppercase tracking-wider text-hp-text-muted">Amenities & Extras</span>
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-500/15 text-purple-700 dark:text-purple-400">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                            </div>
                        </div>
                        <div>
                            <div class="text-2xl font-display font-bold text-purple-700 dark:text-purple-400 leading-tight">
                                ₱{{ number_format($amenitiesCollected + $companionsCollected + $extensionsCollected, 2) }}
                            </div>
                            <p class="text-[0.7rem] text-hp-text-muted mt-1 font-medium">{{ $amenitiesAddedCount }} amenities, {{ $companionsCount }} companions</p>
                        </div>
                    </article>
                </div>

                {{-- SHIFT RECONCILIATION & CASH DRAWER BREAKDOWN --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Cash Handover Breakdown Card --}}
                    <div class="lg:col-span-1 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between border-b border-glass-border pb-3 mb-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#1c5c3c]/15 text-[#1c5c3c] dark:text-[#6ab88c]">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    </div>
                                    <h2 class="m-0 text-base font-display font-bold text-hp-text">Shift Cash Breakdown</h2>
                                </div>
                                <span class="rounded-md bg-emerald-500/10 px-2 py-0.5 text-[0.68rem] font-bold text-emerald-700 dark:text-emerald-300">Reconciled</span>
                            </div>

                            <div class="space-y-3 text-sm">
                                <div class="flex items-center justify-between py-1 border-b border-glass-border/60">
                                    <span class="text-hp-text-muted flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-green-500"></span>
                                        Entrance & Check-In Collections
                                    </span>
                                    <span class="font-semibold text-hp-text">₱{{ number_format($checkInCollections, 2) }}</span>
                                </div>

                                <div class="flex items-center justify-between py-1 border-b border-glass-border/60">
                                    <span class="text-hp-text-muted flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                        Damage & Incident Payments
                                    </span>
                                    <span class="font-semibold text-hp-text">₱{{ number_format($damageChargesCollected, 2) }}</span>
                                </div>

                                <div class="flex items-center justify-between py-1 border-b border-glass-border/60">
                                    <span class="text-hp-text-muted flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-purple-500"></span>
                                        Extra Amenity Payments
                                    </span>
                                    <span class="font-semibold text-hp-text">₱{{ number_format($amenitiesCollected, 2) }}</span>
                                </div>

                                <div class="flex items-center justify-between py-1 border-b border-glass-border/60">
                                    <span class="text-hp-text-muted flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-teal-500"></span>
                                        Companions / Extra Guests
                                    </span>
                                    <span class="font-semibold text-hp-text">₱{{ number_format($companionsCollected, 2) }}</span>
                                </div>

                                <div class="flex items-center justify-between py-1 border-b border-glass-border/60">
                                    <span class="text-hp-text-muted flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                                        Reservation Extensions
                                    </span>
                                    <span class="font-semibold text-hp-text">₱{{ number_format($extensionsCollected, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 pt-3 border-t-2 border-[#1c5c3c]/30">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-xs uppercase font-bold text-hp-text-muted">Total Handover Cash</span>
                                    <p class="text-[0.68rem] text-hp-text-muted">Net cash verified for drawer turnover</p>
                                </div>
                                <div class="text-xl font-display font-bold text-[#1c5c3c] dark:text-[#6ab88c]">
                                    ₱{{ number_format($totalCollections, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Shift Activity Distribution Card --}}
                    <div class="lg:col-span-2 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between border-b border-glass-border pb-3 mb-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-500/15 text-blue-700 dark:text-blue-400">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                    </div>
                                    <h2 class="m-0 text-base font-display font-bold text-hp-text">Shift Operations Summary</h2>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" class="open-ledger-trigger text-xs font-semibold text-[#1c5c3c] dark:text-[#6ab88c] hover:underline flex items-center gap-1 cursor-pointer">
                                        <span>Open Full Ledger ({{ $ledgerRows->count() }})</span>
                                        <span>→</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                                <div class="rounded-xl border border-glass-border p-3 text-center bg-white/40 dark:bg-black/20">
                                    <span class="block text-xl font-display font-bold text-green-600">{{ $checkInsCount }}</span>
                                    <span class="text-xs text-hp-text-muted">Check-Ins</span>
                                </div>
                                <div class="rounded-xl border border-glass-border p-3 text-center bg-white/40 dark:bg-black/20">
                                    <span class="block text-xl font-display font-bold text-sky-600">{{ $checkOutsCount }}</span>
                                    <span class="text-xs text-hp-text-muted">Check-Outs</span>
                                </div>
                                <div class="rounded-xl border border-glass-border p-3 text-center bg-white/40 dark:bg-black/20">
                                    <span class="block text-xl font-display font-bold text-amber-600">{{ $damageChargesCount }}</span>
                                    <span class="text-xs text-hp-text-muted">Damages/Extra</span>
                                </div>
                                <div class="rounded-xl border border-glass-border p-3 text-center bg-white/40 dark:bg-black/20">
                                    <span class="block text-xl font-display font-bold text-purple-600">{{ $amenitiesAddedCount + $companionsCount }}</span>
                                    <span class="text-xs text-hp-text-muted">Amenities/Extras</span>
                                </div>
                            </div>

                            {{-- Operational notes --}}
                            <div class="rounded-xl border border-glass-border/70 bg-surface-2/60 p-3.5 text-xs text-hp-text space-y-1.5">
                                <div class="flex items-center gap-2 text-hp-text font-semibold">
                                    <svg class="h-4 w-4 text-[#1c5c3c]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    Shift Handover Verification
                                </div>
                                <p class="text-hp-text-muted leading-relaxed">
                                    Ensure that all cash collections (<strong>₱{{ number_format($totalCollections, 2) }}</strong>) match your physical drawer turnover. Click "Print Handover Slip" to inspect the official handover slip and print physical turnover signatures.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center justify-between text-xs text-hp-text-muted gap-2">
                            <span>Logged In: <strong>{{ $staffName }}</strong> ({{ $staffEmail }})</span>
                            <button type="button" class="open-ledger-trigger inline-flex items-center gap-1.5 rounded-lg border border-glass-border px-3 py-1.5 text-xs font-semibold text-hp-text hover:bg-glass-hover cursor-pointer transition-colors">
                                <svg class="h-3.5 w-3.5 text-[#1c5c3c]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                View Detailed Ledger Table
                            </button>
                        </div>
                    </div>
                </div>

                {{-- End reportsDataContainer --}}
                </div>

            </main>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 1. SHIFT ACTIVITY & PAYMENT LEDGER MODAL (Fixed Overlay) --}}
    {{-- ============================================================ --}}
    <div id="ledgerModal" class="fixed inset-0 z-[1000] hidden items-center justify-center p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="ledgerModalTitle">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" data-close-ledger-modal="true"></div>

        {{-- Dialog Box --}}
        <div class="relative flex flex-col w-full max-w-5xl max-h-[90vh] bg-white dark:bg-[#161a17] border border-glass-border rounded-2xl shadow-2xl overflow-hidden z-10 animate-in fade-in zoom-in-95 duration-150">
            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50/75 dark:bg-[#121513]">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-700 dark:text-emerald-400">
                        <i class="bi bi-journal-text text-xl"></i>
                    </div>
                    <div>
                        <h2 id="ledgerModalTitle" class="m-0 text-base sm:text-lg font-display font-bold text-hp-text">Shift Activity & Payment Ledger</h2>
                        <p id="ledgerModalSubtitle" class="m-0 text-xs text-hp-text-muted">Transactions handled by {{ $staffName }} • {{ ucfirst($sessionFilter) }} Session ({{ ucwords(str_replace('_', ' ', $preset)) }})</p>
                    </div>
                </div>
                <button type="button" id="closeLedgerModalBtn" class="rounded-xl p-2 text-hp-text-muted hover:bg-black/5 dark:hover:bg-white/10 hover:text-hp-text transition-colors cursor-pointer">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            {{-- Search & Controls --}}
            <div class="px-6 pt-4 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-800/80 bg-white dark:bg-[#161a17]">
                <div class="relative w-full sm:w-80">
                    <i class="bi bi-search absolute left-3 top-2.5 text-xs text-hp-text-muted"></i>
                    <input type="text" id="ledgerSearchInput" placeholder="Search guest, action, or ID..." class="w-full rounded-xl border border-glass-border bg-gray-50 dark:bg-[#121513] pl-8 pr-3 py-2 text-xs text-hp-text outline-none focus:border-[#1c5c3c] focus:ring-1 focus:ring-[#1c5c3c]">
                </div>
                <span id="ledgerCountDisplay" class="text-xs font-semibold text-hp-text-muted">Showing {{ $ledgerRows->count() }} transaction(s)</span>
            </div>

            {{-- Modal Body: Scrollable Table --}}
            <div class="overflow-y-auto flex-1 p-0">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-gray-50 dark:bg-[#121513] text-gray-500 dark:text-gray-400 uppercase text-[0.68rem] font-bold sticky top-0 z-10 border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th class="px-5 py-3">Time & Date</th>
                            <th class="px-3 py-3">Shift</th>
                            <th class="px-3 py-3">Action</th>
                            <th class="px-4 py-3">Reservation & Guest</th>
                            <th class="px-4 py-3">Transaction Details</th>
                            <th class="px-5 py-3 text-right">Collected Amount</th>
                        </tr>
                    </thead>
                    <tbody id="ledgerTableBody" class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($ledgerRows as $row)
                            <tr class="ledger-row hover:bg-black/[0.02] dark:hover:bg-white/[0.02] transition-colors"
                                data-search="{{ strtolower($row['guest_name'] . ' ' . $row['action'] . ' ' . $row['title'] . ' ' . $row['description'] . ' res#' . $row['reservation_id']) }}">
                                {{-- Timestamp --}}
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <div class="font-bold text-hp-text">{{ $row['time_raw'] }}</div>
                                    <div class="text-[0.68rem] text-hp-text-muted">{{ $row['date_raw'] }}</div>
                                </td>

                                {{-- Shift pill --}}
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[0.68rem] font-semibold {{ $row['session_tag'] === 'Daytime' ? 'bg-amber-500/10 text-amber-700 dark:text-amber-300' : 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300' }}">
                                        @if($row['session_tag'] === 'Daytime')
                                            <i class="bi bi-sun-fill text-[0.65rem] text-amber-500"></i>
                                        @else
                                            <i class="bi bi-moon-stars-fill text-[0.65rem] text-indigo-400"></i>
                                        @endif
                                        {{ $row['session_tag'] }}
                                    </span>
                                </td>

                                {{-- Action Badge --}}
                                <td class="px-3 py-3 whitespace-nowrap">
                                    @php
                                        $actionColor = match($row['action']) {
                                            'checked_in' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/20',
                                            'checked_out' => 'bg-blue-500/10 text-blue-700 dark:text-blue-300 border-blue-500/20',
                                            'additional_charge_paid' => 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/20',
                                            'added_amenity' => 'bg-teal-500/10 text-teal-700 dark:text-teal-300 border-teal-500/20',
                                            'companion_added' => 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-indigo-500/20',
                                            'reservation_extended' => 'bg-violet-500/10 text-violet-700 dark:text-violet-300 border-violet-500/20',
                                            'cancelled', 'no_show' => 'bg-rose-500/10 text-rose-700 dark:text-rose-300 border-rose-500/20',
                                            default => 'bg-gray-500/10 text-gray-700 dark:text-gray-300 border-gray-500/20',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[0.68rem] font-bold uppercase tracking-wider border {{ $actionColor }}">
                                        {{ str_replace('_', ' ', $row['action']) }}
                                    </span>
                                </td>

                                {{-- Reservation & Guest --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($row['reservation_id'])
                                        <div class="font-semibold text-hp-text flex items-center gap-1.5">
                                            <span>{{ $row['guest_name'] }}</span>
                                            <span class="text-[0.68rem] font-bold text-[#1c5c3c] dark:text-[#6ab88c]">#{{ $row['reservation_id'] }}</span>
                                        </div>
                                        <div class="text-[0.68rem] text-hp-text-muted">{{ $row['guests_count'] }} guest(s)</div>
                                    @else
                                        <span class="text-hp-text-muted">General Log</span>
                                    @endif
                                </td>

                                {{-- Details --}}
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-hp-text">{{ $row['title'] }}</div>
                                    <div class="text-[0.68rem] text-hp-text-muted leading-tight line-clamp-2">{{ $row['description'] }}</div>
                                </td>

                                {{-- Amount --}}
                                <td class="px-5 py-3 whitespace-nowrap text-right font-display font-bold">
                                    @if($row['payment_amount'] > 0)
                                        <span class="text-emerald-600 dark:text-emerald-400 text-sm font-bold">+ ₱{{ $row['formatted_amount'] }}</span>
                                    @else
                                        <span class="text-hp-text-muted text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr id="ledgerEmptyRow">
                                <td colspan="6" class="py-12 text-center text-hp-text-muted text-sm">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i class="bi bi-clipboard2-x text-3xl text-hp-text-muted/60"></i>
                                        <span>No activity or payment logs found for the selected period and session.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Modal Footer --}}
            <div class="flex items-center justify-between px-6 py-3.5 border-t border-gray-200 dark:border-gray-800 bg-gray-50/75 dark:bg-[#121513]">
                <div id="ledgerFooterTotal" class="text-xs text-hp-text-muted">
                    Total Collections: <strong class="text-emerald-600 dark:text-emerald-400 font-display text-sm font-bold">₱{{ number_format($totalCollections, 2) }}</strong>
                </div>
                <button type="button" id="closeLedgerModalBtnFooter" class="rounded-xl border border-gray-300 dark:border-gray-700 px-4 py-2 text-xs font-semibold text-hp-text hover:bg-black/5 dark:hover:bg-white/10 transition-colors cursor-pointer">
                    Close Ledger
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 2. HANDOVER SLIP PREVIEW & PRINT MODAL (Fixed Overlay) --}}
    {{-- ============================================================ --}}
    <div id="handoverModal" class="fixed inset-0 z-[1000] hidden items-center justify-center p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="handoverModalTitle">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" data-close-handover-modal="true"></div>

        {{-- Dialog Box --}}
        <div class="relative flex flex-col w-full max-w-3xl max-h-[90vh] bg-white dark:bg-[#161a17] border border-glass-border rounded-2xl shadow-2xl overflow-hidden z-10 animate-in fade-in zoom-in-95 duration-150">
            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50/75 dark:bg-[#121513]">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#1c5c3c]/15 text-[#1c5c3c] dark:text-[#6ab88c]">
                        <i class="bi bi-printer-fill text-xl"></i>
                    </div>
                    <div>
                        <h2 id="handoverModalTitle" class="m-0 text-base sm:text-lg font-display font-bold text-hp-text">Shift Handover Slip Preview</h2>
                        <p class="m-0 text-xs text-hp-text-muted">Review the official reconciliation slip below before printing</p>
                    </div>
                </div>
                <button type="button" id="closeHandoverModalBtn" class="rounded-xl p-2 text-hp-text-muted hover:bg-black/5 dark:hover:bg-white/10 hover:text-hp-text transition-colors cursor-pointer">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            {{-- Preview Content (The actual slip) --}}
            <div class="overflow-y-auto flex-1 p-5 sm:p-6 bg-gray-100/70 dark:bg-[#121513]">
                <div class="p-6 bg-white text-black rounded-xl border border-gray-300 shadow-sm text-sm font-sans" id="handoverSlipPreviewContent">
                    <div class="text-center border-b-2 border-black pb-4 mb-4">
                        <h1 class="text-xl font-bold tracking-wide uppercase">Hinaguan Nature Park</h1>
                        <h2 class="text-sm font-semibold text-gray-700">Official Staff Shift Handover & Reconciliation Slip</h2>
                        <p class="text-[0.7rem] text-gray-500 mt-1">Generated: {{ now()->format('F d, Y • h:i A') }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 border border-gray-300 p-3 rounded-lg mb-4 text-xs">
                        <div>
                            <p><strong>Duty Staff Name:</strong> {{ $staffName }}</p>
                            <p><strong>Staff Account ID:</strong> #{{ $staffId }}</p>
                            <p><strong>Shift Session:</strong> {{ $sessionFilter === 'nighttime' ? 'Overnight' : ucfirst($sessionFilter) }} Session</p>
                        </div>
                        <div>
                            <p><strong>Report Period:</strong> {{ ucwords(str_replace('_', ' ', $preset)) }} ({{ $filterFrom ? \Carbon\Carbon::parse($filterFrom)->format('M d, Y') : 'Start' }} → {{ $filterTo ? \Carbon\Carbon::parse($filterTo)->format('M d, Y') : 'End' }})</p>
                            <p><strong>Total Guests Handled:</strong> {{ $totalGuestsHandled }}</p>
                            <p><strong>Total Handled Reservations:</strong> {{ $totalReservationsHandled }}</p>
                        </div>
                    </div>

                    <h3 class="text-xs font-bold uppercase mb-2 border-b border-gray-300 pb-1">Shift Collections & Operations Summary</h3>
                    <table class="w-full text-left text-xs border border-gray-300 mb-4">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 border border-gray-300">Operational Category</th>
                                <th class="p-2 border border-gray-300 text-center">Count</th>
                                <th class="p-2 border border-gray-300 text-right">Collected Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="p-2 border border-gray-300">Guest Check-Ins Handled</td>
                                <td class="p-2 border border-gray-300 text-center font-bold">{{ $checkInsCount }}</td>
                                <td class="p-2 border border-gray-300 text-right">₱{{ number_format($checkInCollections, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="p-2 border border-gray-300">Guest Check-Outs Handled</td>
                                <td class="p-2 border border-gray-300 text-center font-bold">{{ $checkOutsCount }}</td>
                                <td class="p-2 border border-gray-300 text-right">₱0.00</td>
                            </tr>
                            <tr>
                                <td class="p-2 border border-gray-300">Damage & Incident Fees</td>
                                <td class="p-2 border border-gray-300 text-center font-bold">{{ $damageChargesCount }}</td>
                                <td class="p-2 border border-gray-300 text-right">₱{{ number_format($damageChargesCollected, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="p-2 border border-gray-300">Extra Amenities Added</td>
                                <td class="p-2 border border-gray-300 text-center font-bold">{{ $amenitiesAddedCount }}</td>
                                <td class="p-2 border border-gray-300 text-right">₱{{ number_format($amenitiesCollected, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="p-2 border border-gray-300">Companions / Extra Guests Added</td>
                                <td class="p-2 border border-gray-300 text-center font-bold">{{ $companionsCount }}</td>
                                <td class="p-2 border border-gray-300 text-right">₱{{ number_format($companionsCollected, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="p-2 border border-gray-300">Reservation Extensions Processed</td>
                                <td class="p-2 border border-gray-300 text-center font-bold">{{ $extensionsCount }}</td>
                                <td class="p-2 border border-gray-300 text-right">₱{{ number_format($extensionsCollected, 2) }}</td>
                            </tr>
                            <tr class="bg-gray-100 font-bold">
                                <td class="p-2 border border-gray-300 text-sm" colspan="2">TOTAL NET CASH DRAWER TURNOVER</td>
                                <td class="p-2 border border-gray-300 text-right text-sm">₱{{ number_format($totalCollections, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mt-8 pt-4 border-t border-gray-400 grid grid-cols-3 gap-6 text-center text-[0.7rem]">
                        <div>
                            <div class="border-b border-black mb-1 h-10"></div>
                            <p class="font-bold uppercase">{{ $staffName }}</p>
                            <p class="text-gray-500">Outgoing Staff</p>
                        </div>
                        <div>
                            <div class="border-b border-black mb-1 h-10"></div>
                            <p class="font-bold uppercase">_________________________</p>
                            <p class="text-gray-500">Incoming Staff</p>
                        </div>
                        <div>
                            <div class="border-b border-black mb-1 h-10"></div>
                            <p class="font-bold uppercase">_________________________</p>
                            <p class="text-gray-500">Duty Supervisor</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="flex items-center justify-end gap-3 px-6 py-3.5 border-t border-gray-200 dark:border-gray-800 bg-gray-50/75 dark:bg-[#121513]">
                <button type="button" id="closeHandoverModalBtnFooter" class="rounded-xl border border-gray-300 dark:border-gray-700 px-4 py-2 text-xs font-semibold text-hp-text hover:bg-black/5 dark:hover:bg-white/10 transition-colors cursor-pointer">
                    Close
                </button>
                <button type="button" id="printHandoverSlipBtn" class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-[#1c5c3c] hover:bg-[#14402b] px-5 py-2.5 text-xs font-semibold text-white shadow-sm transition-all">
                    <i class="bi bi-printer-fill"></i>
                    <span>Print Official Slip Now</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 3. HIDDEN PRINTABLE CONTAINER FOR BROWSER PRINT ENGINE --}}
    {{-- ============================================================ --}}
    <div id="printableHandoverSlip" style="display: none;" class="print:!block p-8 bg-white text-black font-sans">
        <div class="text-center border-b-2 border-black pb-4 mb-6">
            <h1 class="text-2xl font-bold tracking-wide uppercase">Hinaguan Nature Park</h1>
            <h2 class="text-base font-semibold text-gray-700">Official Staff Shift Handover & Reconciliation Slip</h2>
            <p class="text-xs text-gray-500 mt-1">Generated: {{ now()->format('F d, Y • h:i A') }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4 border border-gray-300 p-4 rounded-lg mb-6 text-sm">
            <div>
                <p><strong>Duty Staff Name:</strong> {{ $staffName }}</p>
                <p><strong>Staff Account ID:</strong> #{{ $staffId }}</p>
                <p><strong>Shift Session:</strong> {{ ucfirst($sessionFilter) }} Session</p>
            </div>
            <div>
                <p><strong>Report Period:</strong> {{ ucwords(str_replace('_', ' ', $preset)) }} ({{ $filterFrom ? \Carbon\Carbon::parse($filterFrom)->format('M d, Y') : 'Start' }} → {{ $filterTo ? \Carbon\Carbon::parse($filterTo)->format('M d, Y') : 'End' }})</p>
                <p><strong>Total Guests Handled:</strong> {{ $totalGuestsHandled }}</p>
                <p><strong>Total Handled Reservations:</strong> {{ $totalReservationsHandled }}</p>
            </div>
        </div>

        <h3 class="text-base font-bold uppercase mb-2 border-b border-gray-300 pb-1">Operational Activity Summary</h3>
        <table class="w-full text-left text-sm border border-gray-300 mb-6">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 border border-gray-300">Operational Category</th>
                    <th class="p-2 border border-gray-300 text-center">Count</th>
                    <th class="p-2 border border-gray-300 text-right">Collected Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="p-2 border border-gray-300">Guest Check-Ins Processed</td>
                    <td class="p-2 border border-gray-300 text-center font-bold">{{ $checkInsCount }}</td>
                    <td class="p-2 border border-gray-300 text-right">₱{{ number_format($checkInCollections, 2) }}</td>
                </tr>
                <tr>
                    <td class="p-2 border border-gray-300">Guest Check-Outs Processed</td>
                    <td class="p-2 border border-gray-300 text-center font-bold">{{ $checkOutsCount }}</td>
                    <td class="p-2 border border-gray-300 text-right">₱0.00</td>
                </tr>
                <tr>
                    <td class="p-2 border border-gray-300">Damage / Incident Fees Collected</td>
                    <td class="p-2 border border-gray-300 text-center font-bold">{{ $damageChargesCount }}</td>
                    <td class="p-2 border border-gray-300 text-right">₱{{ number_format($damageChargesCollected, 2) }}</td>
                </tr>
                <tr>
                    <td class="p-2 border border-gray-300">Extra Amenities Added</td>
                    <td class="p-2 border border-gray-300 text-center font-bold">{{ $amenitiesAddedCount }}</td>
                    <td class="p-2 border border-gray-300 text-right">₱{{ number_format($amenitiesCollected, 2) }}</td>
                </tr>
                <tr>
                    <td class="p-2 border border-gray-300">Extra Companions / Walk-In Guests</td>
                    <td class="p-2 border border-gray-300 text-center font-bold">{{ $companionsCount }}</td>
                    <td class="p-2 border border-gray-300 text-right">₱{{ number_format($companionsCollected, 2) }}</td>
                </tr>
                <tr>
                    <td class="p-2 border border-gray-300">Reservation Extensions Processed</td>
                    <td class="p-2 border border-gray-300 text-center font-bold">{{ $extensionsCount }}</td>
                    <td class="p-2 border border-gray-300 text-right">₱{{ number_format($extensionsCollected, 2) }}</td>
                </tr>
                <tr class="bg-gray-100 font-bold">
                    <td class="p-2 border border-gray-300 text-base" colspan="2">TOTAL NET CASH DRAWER TURNOVER</td>
                    <td class="p-2 border border-gray-300 text-right text-base">₱{{ number_format($totalCollections, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="mt-12 pt-8 border-t border-gray-400 grid grid-cols-3 gap-8 text-center text-xs">
            <div>
                <div class="border-b border-black mb-1 h-12"></div>
                <p class="font-bold uppercase">{{ $staffName }}</p>
                <p class="text-gray-500">Outgoing Staff Signature</p>
            </div>
            <div>
                <div class="border-b border-black mb-1 h-12"></div>
                <p class="font-bold uppercase">_________________________</p>
                <p class="text-gray-500">Incoming Staff Signature</p>
            </div>
            <div>
                <div class="border-b border-black mb-1 h-12"></div>
                <p class="font-bold uppercase">_________________________</p>
                <p class="text-gray-500">Duty Supervisor Signature</p>
            </div>
        </div>
    </div>

    <x-staff_chatbot />
</body>
</html>

