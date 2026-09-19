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

        /* Preset filter chips */
        .report-chip {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .report-chip.is-active {
            background-color: #1c5c3c !important;
            color: #ffffff !important;
            border-color: #1c5c3c !important;
            box-shadow: 0 2px 8px rgba(28, 92, 60, 0.25);
        }

        /* Action Badges */
        .badge-action {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 600;
            line-height: 1;
            text-transform: capitalize;
            letter-spacing: 0.02em;
        }
        .badge-checked_in {
            background: rgba(22, 163, 74, 0.14);
            color: #15803d;
            border: 1px solid rgba(22, 163, 74, 0.28);
        }
        [data-theme="dark"] .badge-checked_in {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
            border-color: rgba(34, 197, 94, 0.35);
        }
        .badge-checked_out, .badge-check_out {
            background: rgba(2, 132, 199, 0.14);
            color: #0369a1;
            border: 1px solid rgba(2, 132, 199, 0.28);
        }
        [data-theme="dark"] .badge-checked_out, [data-theme="dark"] .badge-check_out {
            background: rgba(56, 189, 248, 0.2);
            color: #7dd3fc;
            border-color: rgba(56, 189, 248, 0.35);
        }
        .badge-additional_charge_paid {
            background: rgba(217, 119, 6, 0.14);
            color: #b45309;
            border: 1px solid rgba(217, 119, 6, 0.28);
        }
        [data-theme="dark"] .badge-additional_charge_paid {
            background: rgba(245, 158, 11, 0.2);
            color: #fcd34d;
            border-color: rgba(245, 158, 11, 0.35);
        }
        .badge-added_amenity {
            background: rgba(147, 51, 234, 0.14);
            color: #7e22ce;
            border: 1px solid rgba(147, 51, 234, 0.28);
        }
        [data-theme="dark"] .badge-added_amenity {
            background: rgba(168, 85, 247, 0.2);
            color: #d8b4fe;
            border-color: rgba(168, 85, 247, 0.35);
        }
        .badge-companion_added {
            background: rgba(13, 148, 136, 0.14);
            color: #0f766e;
            border: 1px solid rgba(13, 148, 136, 0.28);
        }
        [data-theme="dark"] .badge-companion_added {
            background: rgba(20, 184, 166, 0.2);
            color: #5eead4;
            border-color: rgba(20, 184, 166, 0.35);
        }
        .badge-reservation_extended {
            background: rgba(79, 70, 229, 0.14);
            color: #4338ca;
            border: 1px solid rgba(79, 70, 229, 0.28);
        }
        [data-theme="dark"] .badge-reservation_extended {
            background: rgba(99, 102, 241, 0.2);
            color: #a5b4fc;
            border-color: rgba(99, 102, 241, 0.35);
        }
        .badge-cancelled, .badge-no_show {
            background: rgba(220, 38, 38, 0.14);
            color: #b91c1c;
            border: 1px solid rgba(220, 38, 38, 0.28);
        }
        [data-theme="dark"] .badge-cancelled, [data-theme="dark"] .badge-no_show {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border-color: rgba(239, 68, 68, 0.35);
        }

        /* ------------------------------------------------------------
           SOLID MODAL STYLING (High Z-Index & Zero Transparency Bleed)
           ------------------------------------------------------------ */
        .staff-modal-overlay {
            position: fixed !important;
            inset: 0 !important;
            z-index: 999999 !important;
            background-color: rgba(10, 16, 12, 0.82) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 1.25rem !important;
        }
        .staff-modal-overlay.hidden {
            display: none !important;
        }
        .staff-modal-dialog {
            position: relative !important;
            background-color: #ffffff !important;
            color: #1c2b22 !important;
            border: 1px solid #d8ded9 !important;
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.45) !important;
            border-radius: 1.5rem !important;
            width: 100% !important;
            max-height: 90vh !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
        }
        [data-theme="dark"] .staff-modal-dialog {
            background-color: #161a17 !important;
            color: #e2e8e4 !important;
            border-color: #2d382f !important;
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.85) !important;
        }
        .staff-modal-header {
            padding: 1.25rem 1.5rem !important;
            border-bottom: 1px solid #e5eae6 !important;
            background-color: #f8faf8 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
        }
        [data-theme="dark"] .staff-modal-header {
            border-color: #273029 !important;
            background-color: #1b211d !important;
        }
        .staff-modal-body {
            padding: 1.25rem 1.5rem !important;
            overflow-y: auto !important;
            flex: 1 1 auto !important;
            background-color: #ffffff !important;
        }
        [data-theme="dark"] .staff-modal-body {
            background-color: #161a17 !important;
        }
        .staff-modal-footer {
            padding: 1rem 1.5rem !important;
            border-top: 1px solid #e5eae6 !important;
            background-color: #f8faf8 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
        }
        [data-theme="dark"] .staff-modal-footer {
            border-color: #273029 !important;
            background-color: #1b211d !important;
        }
        .staff-modal-table-wrap {
            border: 1px solid #e2e7e3 !important;
            border-radius: 0.875rem !important;
            overflow: auto !important;
            background-color: #ffffff !important;
        }
        [data-theme="dark"] .staff-modal-table-wrap {
            border-color: #2b352e !important;
            background-color: #141715 !important;
        }
        .staff-modal-table {
            width: 100% !important;
            border-collapse: collapse !important;
            text-align: left !important;
        }
        .staff-modal-table thead th {
            position: sticky !important;
            top: 0 !important;
            z-index: 10 !important;
            background-color: #eef3ef !important;
            color: #3b4e42 !important;
            padding: 0.85rem 1rem !important;
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            border-bottom: 1px solid #dce2dd !important;
        }
        [data-theme="dark"] .staff-modal-table thead th {
            background-color: #222923 !important;
            color: #a3b2a8 !important;
            border-color: #333d36 !important;
        }
        .staff-modal-table tbody td {
            padding: 0.85rem 1rem !important;
            background-color: #ffffff !important;
            color: #1c2b22 !important;
            border-bottom: 1px solid #edf0ed !important;
        }
        [data-theme="dark"] .staff-modal-table tbody td {
            background-color: #161a17 !important;
            color: #e2e8e4 !important;
            border-color: #242c26 !important;
        }
        .staff-modal-table tbody tr:hover td {
            background-color: #f2f7f3 !important;
        }
        [data-theme="dark"] .staff-modal-table tbody tr:hover td {
            background-color: #212822 !important;
        }
        .staff-modal-search {
            background-color: #ffffff !important;
            border: 1px solid #ced5cf !important;
            color: #1c2b22 !important;
            border-radius: 0.75rem !important;
            padding: 0.55rem 0.75rem 0.55rem 2.25rem !important;
            font-size: 0.82rem !important;
            outline: none !important;
            transition: all 0.2s ease !important;
        }
        .staff-modal-search:focus {
            border-color: #1c5c3c !important;
            box-shadow: 0 0 0 3px rgba(28, 92, 60, 0.15) !important;
        }
        [data-theme="dark"] .staff-modal-search {
            background-color: #1e2420 !important;
            border-color: #38453c !important;
            color: #f1f5f2 !important;
        }

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

                {{-- STAFF SHIFT IDENTITY & ACTIONS BANNER --}}
                <div class="rounded-3xl border border-glass-border bg-glass p-6 shadow-glass relative overflow-hidden">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[#1c5c3c] text-white shadow-md">
                                <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <h1 class="m-0 text-xl md:text-2xl font-display font-bold text-hp-text">{{ $staffName }}</h1>
                                    <span class="rounded-full bg-[#1c5c3c]/15 px-3 py-0.5 text-xs font-bold text-[#1c5c3c] dark:text-[#6ab88c]">
                                        Staff ID #{{ $staffId }}
                                    </span>
                                    <span class="rounded-full {{ $currentSession === 'Daytime' ? 'bg-amber-500/15 text-amber-700 dark:text-amber-400' : 'bg-indigo-500/15 text-indigo-700 dark:text-indigo-400' }} px-3 py-0.5 text-xs font-bold flex items-center gap-1">
                                        @if($currentSession === 'Daytime')
                                            <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                            Daytime Session (08:00 AM – 05:00 PM)
                                        @else
                                            <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                                            Nighttime Session (05:00 PM – 08:00 AM)
                                        @endif
                                    </span>
                                </div>
                                <p class="m-0 mt-1.5 text-xs sm:text-sm text-hp-text-muted flex flex-wrap items-center gap-2">
                                    <span>Today: <strong>{{ now()->format('l, F j, Y') }}</strong></span>
                                    <span>•</span>
                                    <span>Filtering: <strong>{{ ucwords(str_replace('_', ' ', $preset)) }}</strong> ({{ $filterFrom ? \Carbon\Carbon::parse($filterFrom)->format('M d, Y') : 'Start' }} → {{ $filterTo ? \Carbon\Carbon::parse($filterTo)->format('M d, Y') : 'End' }})</span>
                                    <span>•</span>
                                    <span>Shift Filter: <strong>{{ ucfirst($sessionFilter) }}</strong></span>
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            {{-- BUTTON 1: Open Activity Ledger Modal --}}
                            <button type="button" id="openLedgerBtn" class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-glass-border px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover shadow-sm">
                                <svg class="h-4 w-4 text-[#1c5c3c] dark:text-[#6ab88c]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                </svg>
                                <span>Shift Activity Ledger</span>
                                <span class="rounded-full bg-[#1c5c3c]/15 text-[#1c5c3c] dark:text-[#6ab88c] px-2 py-0.5 text-xs font-bold">{{ $ledgerRows->count() }}</span>
                            </button>

                            {{-- BUTTON 2: Preview & Print Handover Slip Modal --}}
                            <button type="button" id="openHandoverModalBtn" class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-[#1c5c3c] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-[#14402b] hover:shadow">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                <span>Print Handover Slip</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- COLLAPSIBLE FILTER PANEL (Default is closed) --}}
                <div class="rounded-2xl border border-glass-border bg-glass shadow-glass overflow-hidden transition-all duration-200" id="filterAccordion">
                    {{-- Accordion Toggle Header --}}
                    <button type="button" id="filterToggleBtn" class="w-full flex items-center justify-between p-4 sm:p-5 text-left cursor-pointer hover:bg-glass-hover/50 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#1c5c3c]/15 text-[#1c5c3c] dark:text-[#6ab88c]">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="m-0 text-sm sm:text-base font-display font-bold text-hp-text">Filter Shift Report</h3>
                                <p class="m-0 text-xs text-hp-text-muted">Customize date range, shift session (Daytime/Nighttime), or action types</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                Active: {{ ucwords(str_replace('_', ' ', $preset)) }} • {{ ucfirst($sessionFilter) }}
                            </span>
                            <svg id="filterChevron" class="h-5 w-5 text-hp-text-muted transition-transform duration-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </button>

                    {{-- Collapsible Filter Content (hidden by default) --}}
                    <div id="filterContent" class="hidden border-t border-glass-border p-5 space-y-4">
                        <form method="GET" action="{{ route('staff.reports') }}" id="reportFilterForm" class="space-y-4">
                            {{-- Quick Presets --}}
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-glass-border pb-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-bold uppercase tracking-wider text-hp-text-muted mr-1">Period:</span>
                                    <a href="{{ route('staff.reports', ['preset' => 'today', 'session' => $sessionFilter]) }}" class="report-chip rounded-full border border-glass-border px-3.5 py-1.5 text-xs font-semibold text-hp-text {{ $preset === 'today' ? 'is-active' : 'hover:bg-glass-hover' }}">Today</a>
                                    <a href="{{ route('staff.reports', ['preset' => 'yesterday', 'session' => $sessionFilter]) }}" class="report-chip rounded-full border border-glass-border px-3.5 py-1.5 text-xs font-semibold text-hp-text {{ $preset === 'yesterday' ? 'is-active' : 'hover:bg-glass-hover' }}">Yesterday</a>
                                    <a href="{{ route('staff.reports', ['preset' => 'this_week', 'session' => $sessionFilter]) }}" class="report-chip rounded-full border border-glass-border px-3.5 py-1.5 text-xs font-semibold text-hp-text {{ $preset === 'this_week' ? 'is-active' : 'hover:bg-glass-hover' }}">This Week</a>
                                    <a href="{{ route('staff.reports', ['preset' => 'this_month', 'session' => $sessionFilter]) }}" class="report-chip rounded-full border border-glass-border px-3.5 py-1.5 text-xs font-semibold text-hp-text {{ $preset === 'this_month' ? 'is-active' : 'hover:bg-glass-hover' }}">This Month</a>
                                    <a href="{{ route('staff.reports', ['preset' => 'all', 'session' => $sessionFilter]) }}" class="report-chip rounded-full border border-glass-border px-3.5 py-1.5 text-xs font-semibold text-hp-text {{ $preset === 'all' ? 'is-active' : 'hover:bg-glass-hover' }}">All Time</a>
                                </div>

                                <input type="hidden" name="preset" id="presetInput" value="{{ $preset }}">
                            </div>

                            {{-- Granular Controls --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                                <div>
                                    <label for="dateFromInput" class="block text-xs font-semibold text-hp-text-muted mb-1.5">Date From</label>
                                    <input type="date" name="date_from" id="dateFromInput" value="{{ $filterFrom }}" class="w-full rounded-xl border border-glass-border bg-transparent px-3 py-2 text-sm text-hp-text outline-none focus:border-[#1c5c3c]">
                                </div>

                                <div>
                                    <label for="dateToInput" class="block text-xs font-semibold text-hp-text-muted mb-1.5">Date To</label>
                                    <input type="date" name="date_to" id="dateToInput" value="{{ $filterTo }}" class="w-full rounded-xl border border-glass-border bg-transparent px-3 py-2 text-sm text-hp-text outline-none focus:border-[#1c5c3c]">
                                </div>

                                <div>
                                    <label for="sessionSelect" class="block text-xs font-semibold text-hp-text-muted mb-1.5">Shift / Session</label>
                                    <select name="session" id="sessionSelect" class="w-full rounded-xl border border-glass-border bg-transparent px-3 py-2 text-sm text-hp-text outline-none focus:border-[#1c5c3c]">
                                        <option value="all" {{ $sessionFilter === 'all' ? 'selected' : '' }}>All Shift Sessions</option>
                                        <option value="daytime" {{ $sessionFilter === 'daytime' ? 'selected' : '' }}>Daytime Shift (08:00 AM - 05:00 PM)</option>
                                        <option value="nighttime" {{ $sessionFilter === 'nighttime' ? 'selected' : '' }}>Nighttime Shift (05:00 PM - 08:00 AM)</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="actionSelect" class="block text-xs font-semibold text-hp-text-muted mb-1.5">Action Filter</label>
                                    <select name="action" id="actionSelect" class="w-full rounded-xl border border-glass-border bg-transparent px-3 py-2 text-sm text-hp-text outline-none focus:border-[#1c5c3c]">
                                        <option value="all" {{ $actionFilter === 'all' ? 'selected' : '' }}>All Logged Actions</option>
                                        <option value="checked_in" {{ $actionFilter === 'checked_in' ? 'selected' : '' }}>Check-Ins Only</option>
                                        <option value="checked_out" {{ $actionFilter === 'checked_out' ? 'selected' : '' }}>Check-Outs Only</option>
                                        <option value="additional_charge_paid" {{ $actionFilter === 'additional_charge_paid' ? 'selected' : '' }}>Damage / Incident Charges</option>
                                        <option value="added_amenity" {{ $actionFilter === 'added_amenity' ? 'selected' : '' }}>Amenities Added</option>
                                        <option value="companion_added" {{ $actionFilter === 'companion_added' ? 'selected' : '' }}>Companions Added</option>
                                        <option value="reservation_extended" {{ $actionFilter === 'reservation_extended' ? 'selected' : '' }}>Extensions</option>
                                        <option value="cancelled" {{ $actionFilter === 'cancelled' ? 'selected' : '' }}>Cancellations</option>
                                        <option value="no_show" {{ $actionFilter === 'no_show' ? 'selected' : '' }}>No-Shows</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-2">
                                <a href="{{ route('staff.reports', ['preset' => 'today']) }}" class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-glass-border px-4 py-2 text-xs font-semibold text-hp-text hover:bg-glass-hover">
                                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Reset to Today
                                </a>
                                <button type="submit" class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl bg-[#1c5c3c] px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[#14402b]">
                                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                                    Apply Filters
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

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

            </main>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 1. SHIFT ACTIVITY & PAYMENT LEDGER MODAL (Root Level, Z-Index 999999) --}}
    {{-- ============================================================ --}}
    <div id="ledgerModal" class="staff-modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="ledgerModalTitle">
        <div class="staff-modal-dialog max-w-5xl">
            {{-- Modal Header --}}
            <div class="staff-modal-header">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-700 dark:text-emerald-400">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                    </div>
                    <div>
                        <h2 id="ledgerModalTitle" class="m-0 text-base sm:text-lg font-display font-bold text-hp-text">Shift Activity & Payment Ledger</h2>
                        <p class="m-0 text-xs text-hp-text-muted">Transactions handled by {{ $staffName }} • {{ ucfirst($sessionFilter) }} Session ({{ ucwords(str_replace('_', ' ', $preset)) }})</p>
                    </div>
                </div>
                <button type="button" id="closeLedgerModalBtn" class="rounded-xl p-2 text-hp-text-muted hover:bg-black/5 dark:hover:bg-white/10 hover:text-hp-text transition-colors cursor-pointer">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Search & Controls --}}
            <div class="px-6 pt-4 pb-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-[#161a17]">
                <div class="relative w-full sm:w-80">
                    <input type="text" id="ledgerSearchInput" placeholder="Search guest, action, or ID..." class="staff-modal-search w-full">
                    <svg class="absolute left-3 top-3 h-3.5 w-3.5 text-hp-text-muted" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <span id="ledgerCountDisplay" class="text-xs font-semibold text-hp-text-muted">Showing {{ $ledgerRows->count() }} transaction(s)</span>
            </div>

            {{-- Modal Body: Scrollable Table --}}
            <div class="staff-modal-body">
                <div class="staff-modal-table-wrap">
                    <table class="staff-modal-table">
                        <thead>
                            <tr>
                                <th>Time & Date</th>
                                <th>Shift</th>
                                <th>Action</th>
                                <th>Reservation & Guest</th>
                                <th>Transaction Details</th>
                                <th style="text-align: right;">Collected Amount</th>
                            </tr>
                        </thead>
                        <tbody id="ledgerTableBody">
                            @forelse($ledgerRows as $row)
                                <tr class="ledger-row"
                                    data-search="{{ strtolower($row['guest_name'] . ' ' . $row['action'] . ' ' . $row['title'] . ' ' . $row['description'] . ' res#' . $row['reservation_id']) }}">
                                    {{-- Timestamp --}}
                                    <td class="whitespace-nowrap">
                                        <div class="font-bold text-hp-text">{{ $row['time_raw'] }}</div>
                                        <div class="text-[0.68rem] text-hp-text-muted">{{ $row['date_raw'] }}</div>
                                    </td>

                                    {{-- Shift pill --}}
                                    <td class="whitespace-nowrap">
                                        <span class="rounded-md px-2 py-0.5 text-[0.68rem] font-semibold {{ $row['session_tag'] === 'Daytime' ? 'bg-amber-500/10 text-amber-700 dark:text-amber-300' : 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300' }}">
                                            {{ $row['session_tag'] }}
                                        </span>
                                    </td>

                                    {{-- Action Badge --}}
                                    <td class="whitespace-nowrap">
                                        <span class="badge-action badge-{{ $row['action'] }}">
                                            {{ str_replace('_', ' ', $row['action']) }}
                                        </span>
                                    </td>

                                    {{-- Reservation & Guest --}}
                                    <td class="whitespace-nowrap">
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
                                    <td>
                                        <div class="font-semibold text-hp-text">{{ $row['title'] }}</div>
                                        <div class="text-[0.68rem] text-hp-text-muted leading-tight line-clamp-2">{{ $row['description'] }}</div>
                                    </td>

                                    {{-- Amount --}}
                                    <td style="text-align: right;" class="whitespace-nowrap font-display font-bold">
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
                                            <svg class="h-8 w-8 text-hp-text-muted/60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span>No activity or payment logs found for the selected period and session.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="staff-modal-footer">
                <div class="text-xs text-hp-text-muted">
                    Total Collections: <strong class="text-emerald-600 dark:text-emerald-400 font-display text-sm font-bold">₱{{ number_format($totalCollections, 2) }}</strong>
                </div>
                <button type="button" id="closeLedgerModalBtnFooter" class="rounded-xl border border-gray-300 dark:border-gray-700 px-4 py-2 text-xs font-semibold text-hp-text hover:bg-black/5 dark:hover:bg-white/10 transition-colors cursor-pointer">
                    Close Ledger
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 2. HANDOVER SLIP PREVIEW & PRINT MODAL (Root Level, Z-Index 999999) --}}
    {{-- ============================================================ --}}
    <div id="handoverModal" class="staff-modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="handoverModalTitle">
        <div class="staff-modal-dialog max-w-3xl">
            {{-- Modal Header --}}
            <div class="staff-modal-header">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#1c5c3c]/15 text-[#1c5c3c] dark:text-[#6ab88c]">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </div>
                    <div>
                        <h2 id="handoverModalTitle" class="m-0 text-base sm:text-lg font-display font-bold text-hp-text">Shift Handover Slip Preview</h2>
                        <p class="m-0 text-xs text-hp-text-muted">Review the official reconciliation slip below before printing</p>
                    </div>
                </div>
                <button type="button" id="closeHandoverModalBtn" class="rounded-xl p-2 text-hp-text-muted hover:bg-black/5 dark:hover:bg-white/10 hover:text-hp-text transition-colors cursor-pointer">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Preview Content (The actual slip) --}}
            <div class="staff-modal-body bg-gray-50 dark:bg-[#121513]">
                <div class="p-6 bg-white text-black rounded-2xl border border-gray-300 shadow-sm text-sm font-sans" id="handoverSlipPreviewContent">
                    <div class="text-center border-b-2 border-black pb-4 mb-4">
                        <h1 class="text-xl font-bold tracking-wide uppercase">Hinaguan Nature Park</h1>
                        <h2 class="text-sm font-semibold text-gray-700">Official Staff Shift Handover & Reconciliation Slip</h2>
                        <p class="text-[0.7rem] text-gray-500 mt-1">Generated: {{ now()->format('F d, Y • h:i A') }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 border border-gray-300 p-3 rounded-lg mb-4 text-xs">
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
            <div class="staff-modal-footer">
                <button type="button" id="closeHandoverModalBtnFooter" class="rounded-xl border border-gray-300 dark:border-gray-700 px-4 py-2 text-xs font-semibold text-hp-text hover:bg-black/5 dark:hover:bg-white/10 transition-colors cursor-pointer">
                    Close
                </button>
                <button type="button" id="printHandoverSlipBtn" class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-[#1c5c3c] px-5 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-[#14402b] transition-all">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Print Official Slip Now</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- 3. HIDDEN PRINTABLE CONTAINER FOR BROWSER PRINT ENGINE --}}
    {{-- ============================================================ --}}
    <div id="printableHandoverSlip" class="hidden print:block p-8 bg-white text-black font-sans">
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

