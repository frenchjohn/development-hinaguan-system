<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Reports — Hinaguan Nature Park</title>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        body.staff-portal [class*="backdrop-blur"] {
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
        .report-tab-btn {
            color: var(--hp-text-muted, #64748b);
            background: transparent;
        }
        .report-tab-btn:hover {
            color: var(--hp-text, #111827);
            background: rgba(255, 255, 255, 0.45);
        }
        [data-theme="dark"] .report-tab-btn:hover {
            background: rgba(255, 255, 255, 0.08);
        }
        .report-tab-btn.is-active {
            color: #ffffff !important;
            background: #1c5c3c !important;
            box-shadow: 0 4px 14px rgba(28, 92, 60, 0.3);
        }
        [data-theme="dark"] .report-tab-btn.is-active {
            background: #1c5c3c !important;
            color: #ffffff !important;
        }
        .ai-glass-hero {
            background: linear-gradient(135deg, rgba(28, 92, 60, 0.08) 0%, rgba(59, 130, 246, 0.08) 100%), var(--glass-bg, rgba(255, 255, 255, 0.7));
        }
        [data-theme="dark"] .ai-glass-hero {
            background: linear-gradient(135deg, rgba(28, 92, 60, 0.15) 0%, rgba(59, 130, 246, 0.15) 100%), var(--glass-bg, rgba(20, 23, 21, 0.8));
        }
        .ai-preset-card {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .ai-preset-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(28, 92, 60, 0.15);
            border-color: rgba(28, 92, 60, 0.4);
        }
        @keyframes pulse-glow {
            0%, 100% { opacity: 0.6; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.03); }
        }
        .animate-pulse-glow {
            animation: pulse-glow 3s infinite ease-in-out;
        }
    </style>
</head>
<body class="antialiased staff-portal">
    <div class="dash-layout">
        <x-staff_sidemenu active="reports" userName="{{ session('auth_user.name') ?? 'Staff User' }}" userRole="Staff" />

        <div class="dash-main">
            <x-header
                title="Staff Reports"
                subtitle="Customer, reservation, and amenity insights"
            />

            <main class="dash-content p-6">

                {{-- Segmented Mode Switcher (Standard vs AI) --}}
                <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                    <div class="inline-flex rounded-2xl border border-glass-border bg-glass p-1.5 shadow-sm">
                        <button type="button" id="tabStandardReports" class="report-tab-btn is-active inline-flex cursor-pointer items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold transition-all duration-200">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Standard Reports
                        </button>
                        <button type="button" id="tabAiReports" class="report-tab-btn inline-flex cursor-pointer items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold transition-all duration-200">
                            <svg class="h-4 w-4 text-emerald-500 animate-pulse" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            AI Report Studio
                            <span class="rounded-full bg-emerald-500/20 px-2 py-0.5 text-[0.65rem] font-bold tracking-wider text-emerald-700 dark:text-emerald-300 uppercase">AI Intelligence</span>
                        </button>
                    </div>

                    <div class="text-xs text-hp-text-muted">
                        Switch between operational ledger reports and AI-powered custom analysis
                    </div>
                </div>

                {{-- SECTION 1: Standard Reports --}}
                <div id="standardReportsSection" class="flex flex-col">
                    <section class="group is-open mb-6 overflow-hidden rounded-2xl border border-glass-border bg-glass p-6 shadow-glass transition-all duration-300 is-open:border-t is-open:pt-6" id="reportsFilters">
                        <div class="flex cursor-pointer items-center justify-between" id="filterToggleBtn">
                            <div class="flex items-center gap-4">
                                <div class="flex h-11 w-11 items-center justify-center rounded-[10px] bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1e2220] dark:text-[#6ab88c]">
                                    <svg class="h-[22px] w-[22px]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                                </div>
                                <div>
                                    <h3 class="m-0 text-lg font-semibold text-hp-text">Filter Report</h3>
                                    <p class="m-0 text-sm text-hp-text-muted">Narrow reservations by customer, amenity, status or check-in range</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="button" class="inline-flex cursor-pointer items-center gap-2 rounded-[10px] border border-glass-border px-4 py-2 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover" id="resetFiltersBtn">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Reset
                                </button>
                                <button type="button" class="inline-flex cursor-pointer items-center gap-2 rounded-[10px] bg-hp-green-mid px-4 py-2 text-sm font-semibold text-white transition-all duration-200 hover:bg-hp-green-dark" id="applyFiltersBtn">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                                    Apply Filters
                                </button>
                                <svg class="ml-2 h-5 w-5 text-hp-text-muted transition-transform duration-300 group-[.is-open]:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                        <div class="invisible max-h-0 opacity-0 transition-all duration-300 group-[.is-open]:visible group-[.is-open]:mt-6 group-[.is-open]:max-h-[500px] group-[.is-open]:border-t group-[.is-open]:border-glass-border group-[.is-open]:pt-6 group-[.is-open]:opacity-100">
                            <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                                <label class="flex flex-col gap-2">
                                    <span class="flex items-center gap-2 text-sm font-semibold text-hp-text">
                                        <svg class="h-4 w-4 text-hp-green-mid" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                        Customer
                                    </span>
                                    <select id="customerFilter" class="w-full rounded-[10px] border border-glass-border bg-transparent px-3 py-2.5 font-ui text-sm text-hp-text transition-colors duration-200 focus:border-hp-green-mid focus:outline-none">
                                        <option value="all">All customers</option>
                                        @foreach($customerOptions as $customerOption)
                                            <option value="{{ $customerOption }}">{{ $customerOption }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="flex flex-col gap-2">
                                    <span class="flex items-center gap-2 text-sm font-semibold text-hp-text">
                                        <svg class="h-4 w-4 text-hp-green-mid" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 01-1.125-1.125v-3.75zM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-8.25zM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-2.25z"/></svg>
                                        Amenity
                                    </span>
                                    <select id="amenityFilter" class="w-full rounded-[10px] border border-glass-border bg-transparent px-3 py-2.5 font-ui text-sm text-hp-text transition-colors duration-200 focus:border-hp-green-mid focus:outline-none">
                                        <option value="all">All amenities</option>
                                        @foreach($amenityOptions as $amenityOption)
                                            <option value="{{ $amenityOption }}">{{ $amenityOption }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="flex flex-col gap-2">
                                    <span class="flex items-center gap-2 text-sm font-semibold text-hp-text">
                                        <svg class="h-4 w-4 text-hp-green-mid" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Reservation Status
                                    </span>
                                    <select id="statusFilter" class="w-full rounded-[10px] border border-glass-border bg-transparent px-3 py-2.5 font-ui text-sm text-hp-text transition-colors duration-200 focus:border-hp-green-mid focus:outline-none">
                                        <option value="all">All statuses</option>
                                        @foreach($statusOptions as $statusOption)
                                            <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="flex flex-col gap-2">
                                    <span class="flex items-center gap-2 text-sm font-semibold text-hp-text">
                                        <svg class="h-4 w-4 text-hp-green-mid" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                                        Check-in Range
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <input id="dateFrom" type="date" value="{{ $firstCheckInDate }}" aria-label="Check-in from" class="w-full rounded-[10px] border border-glass-border bg-transparent px-3 py-2.5 font-ui text-sm text-hp-text transition-colors duration-200 focus:border-hp-green-mid focus:outline-none">
                                        <span class="text-hp-text-muted">→</span>
                                        <input id="dateTo" type="date" value="{{ $lastCheckInDate }}" aria-label="Check-in to" class="w-full rounded-[10px] border border-glass-border bg-transparent px-3 py-2.5 font-ui text-sm text-hp-text transition-colors duration-200 focus:border-hp-green-mid focus:outline-none">
                                    </div>
                                </label>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="text-sm font-semibold text-hp-text-muted">Quick Range:</span>
                                <button type="button" class="preset-chip cursor-pointer rounded-full border border-glass-border px-3.5 py-1.5 text-[0.8rem] font-medium text-hp-text transition-all duration-200 hover:border-transparent hover:bg-[#e7f3ec] hover:text-[#1c5c3c] dark:hover:bg-[#1e2220] dark:hover:text-[#6ab88c] is-active:border-hp-green-mid is-active:bg-hp-green-mid is-active:text-white" data-preset="today">Today</button>
                                <button type="button" class="preset-chip cursor-pointer rounded-full border border-glass-border px-3.5 py-1.5 text-[0.8rem] font-medium text-hp-text transition-all duration-200 hover:border-transparent hover:bg-[#e7f3ec] hover:text-[#1c5c3c] dark:hover:bg-[#1e2220] dark:hover:text-[#6ab88c] is-active:border-hp-green-mid is-active:bg-hp-green-mid is-active:text-white" data-preset="7d">Last 7 days</button>
                                <button type="button" class="preset-chip cursor-pointer rounded-full border border-glass-border px-3.5 py-1.5 text-[0.8rem] font-medium text-hp-text transition-all duration-200 hover:border-transparent hover:bg-[#e7f3ec] hover:text-[#1c5c3c] dark:hover:bg-[#1e2220] dark:hover:text-[#6ab88c] is-active:border-hp-green-mid is-active:bg-hp-green-mid is-active:text-white" data-preset="30d">Last 30 days</button>
                                <button type="button" class="preset-chip cursor-pointer rounded-full border border-glass-border px-3.5 py-1.5 text-[0.8rem] font-medium text-hp-text transition-all duration-200 hover:border-transparent hover:bg-[#e7f3ec] hover:text-[#1c5c3c] dark:hover:bg-[#1e2220] dark:hover:text-[#6ab88c] is-active:border-hp-green-mid is-active:bg-hp-green-mid is-active:text-white" data-preset="month">This month</button>
                                <button type="button" class="preset-chip is-active cursor-pointer rounded-full border border-glass-border px-3.5 py-1.5 text-[0.8rem] font-medium text-hp-text transition-all duration-200 hover:border-transparent hover:bg-[#e7f3ec] hover:text-[#1c5c3c] dark:hover:bg-[#1e2220] dark:hover:text-[#6ab88c] is-active:border-hp-green-mid is-active:bg-hp-green-mid is-active:text-white" data-preset="all">All time</button>
                            </div>
                        </div>
                    </section>

                    <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                        <article class="flex items-start gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1e2220] dark:text-[#6ab88c]">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="flex flex-col">
                                <h4 class="m-0 mb-0.5 font-display text-2xl font-bold text-hp-text" id="kpiReservations">{{ $totalReservations }}</h4>
                                <p class="m-0 mb-1 text-sm font-semibold text-hp-text-muted">Total Reservations</p>
                                <span class="text-xs text-hp-text-muted opacity-70">• From all selected filters</span>
                            </div>
                        </article>

                        <article class="flex items-start gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#eaf5e1] text-[#4b8022] dark:bg-[#213316] dark:text-[#96c76e]">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <div class="flex flex-col">
                                <h4 class="m-0 mb-0.5 font-display text-2xl font-bold text-hp-text">{{ $totalGuests }}</h4>
                                <p class="m-0 mb-1 text-sm font-semibold text-hp-text-muted">Total Guests</p>
                                <span class="text-xs text-hp-text-muted opacity-70">• From all selected filters</span>
                            </div>
                        </article>

                        <article class="flex items-start gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#e5f0f6] text-[#2a6a8f] dark:bg-[#182c38] dark:text-[#6ea9c9]">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="flex flex-col">
                                <h4 class="m-0 mb-0.5 font-display text-2xl font-bold text-hp-text" id="kpiRevenue">₱{{ number_format($totalRevenue, 2) }}</h4>
                                <p class="m-0 mb-1 text-sm font-semibold text-hp-text-muted">Total Revenue</p>
                                <span class="text-xs text-hp-text-muted opacity-70">• From all selected filters</span>
                            </div>
                        </article>

                        <article class="flex items-start gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#f0e9f4] text-[#6d4b8e] dark:bg-[#2b1f33] dark:text-[#a889c4]">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            </div>
                            <div class="flex flex-col">
                                <h4 class="m-0 mb-0.5 font-display text-2xl font-bold text-hp-text">₱{{ number_format($averageSpend, 2) }}</h4>
                                <p class="m-0 mb-1 text-sm font-semibold text-hp-text-muted">Avg per Reservation</p>
                                <span class="text-xs text-hp-text-muted opacity-70">• From all selected filters</span>
                            </div>
                        </article>
                    </div>

                    <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-[2fr_1fr]">
                        <!-- Revenue Area Chart -->
                        <section class="flex flex-col rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
                            <div class="mb-6 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1e2220] dark:text-[#6ab88c]">
                                        <svg class="h-[18px] w-[18px]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                    </div>
                                    <h3 class="m-0 text-lg font-semibold text-hp-text">Revenue — Last 6 Months</h3>
                                </div>
                                <div>
                                    <select class="rounded-[10px] border border-glass-border bg-transparent px-3 py-1.5 text-[0.8rem] text-hp-text outline-none">
                                        <option>Total amount per month</option>
                                    </select>
                                </div>
                            </div>
                            <div class="relative min-h-[280px] w-full flex-1">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </section>

                        <!-- Status Donut Chart -->
                        <section class="flex flex-col rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
                            <div class="mb-6 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#eaf5e1] text-[#4b8022] dark:bg-[#213316] dark:text-[#96c76e]">
                                        <svg class="h-[18px] w-[18px]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </div>
                                    <h3 class="m-0 text-lg font-semibold text-hp-text">Reservation Status</h3>
                                </div>
                            </div>
                            <div class="flex flex-1 flex-col items-center gap-6">
                                <div class="relative h-[200px] w-[200px]">
                                    <canvas id="statusDonutChart"></canvas>
                                    <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-center">
                                        <span class="block text-[1.8rem] font-bold leading-none text-hp-text" id="donutTotalCount">0</span>
                                        <span class="text-xs uppercase tracking-[0.5px] text-hp-text-muted">Total</span>
                                    </div>
                                </div>
                                <div class="flex w-full flex-col gap-2.5" id="donutLegendContainer">
                                    <!-- Populated by JS -->
                                </div>
                            </div>
                            <div class="mt-6 border-t border-glass-border pt-4 text-center">
                                <a href="#" class="text-sm font-semibold text-hp-green-mid no-underline transition-colors duration-200 hover:text-hp-green-dark">View full breakdown →</a>
                            </div>
                        </section>
                    </div>

                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                        <!-- Top Amenities -->
                        <section class="flex flex-col rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
                            <div class="mb-6 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1e2220] dark:text-[#6ab88c]">
                                        <svg class="h-[18px] w-[18px]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    </div>
                                    <div>
                                        <h3 class="m-0 text-lg font-semibold text-hp-text">Top Amenities</h3>
                                        <p class="m-0 text-[0.8rem] text-hp-text-muted">Most reserved amenities</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4 flex justify-between border-b border-glass-border pb-2 text-xs font-semibold uppercase tracking-[0.5px] text-hp-text-muted">
                                <span>AMENITY</span>
                                <span>RESERVATIONS</span>
                            </div>
                            <div class="flex flex-col gap-4" id="topAmenitiesContainer">
                                <!-- Populated by JS -->
                            </div>
                        </section>

                        <!-- Peak Days -->
                        <section class="flex flex-col rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
                            <div class="mb-6 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1e2220] dark:text-[#6ab88c]">
                                        <svg class="h-[18px] w-[18px]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div>
                                        <h3 class="m-0 text-lg font-semibold text-hp-text">Peak Days</h3>
                                        <p class="m-0 text-[0.8rem] text-hp-text-muted">Busiest days based on reservations</p>
                                    </div>
                                </div>
                                <div>
                                    <select class="rounded-[10px] border border-glass-border bg-transparent px-3 py-1.5 text-[0.8rem] text-hp-text outline-none">
                                        <option>By Reservations</option>
                                        <option>By Guests</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex flex-col gap-4" id="peakDaysContainer">
                                <!-- Populated by JS -->
                            </div>
                        </section>
                    </div>

                    <!-- Recent Reservations Table -->
                    <section class="mt-6 flex flex-col rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
                        <div class="mb-6 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#e5f0f6] text-[#2a6a8f] dark:bg-[#182c38] dark:text-[#6ea9c9]">
                                    <svg class="h-[18px] w-[18px]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                </div>
                                <div>
                                    <h3 class="m-0 text-lg font-semibold text-hp-text">Recent Reservations</h3>
                                    <p class="m-0 text-[0.8rem] text-hp-text-muted">Detailed view of reservations with guests</p>
                                </div>
                            </div>
                        </div>

                        <div class="guest-table-wrap overflow-x-auto" id="reportsReservationTableWrap">
                            <table class="guest-table w-full border-collapse text-left">
                                <thead>
                                    <tr>
                                        <th>Reservation</th>
                                        <th>Main Guest</th>
                                        <th>Check-in & Date</th>
                                        <th>Amenities</th>
                                        <th>Guests</th>
                                        <th>Status & Payment</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="reportsReservationTableBody">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                {{-- SECTION 2: AI Report Studio --}}
                <div id="aiReportsSection" class="hidden flex flex-col">
                    {{-- AI Studio Hero Banner --}}
                    <div class="ai-glass-hero mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 rounded-3xl border border-glass-border p-6 shadow-glass">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="m-0 text-xl font-display font-bold text-hp-text">AI Staff Analytics Studio</h2>
                                    <span class="rounded-full bg-emerald-500/20 px-2.5 py-0.5 text-[0.7rem] font-bold text-emerald-700 dark:text-emerald-300">Live AI</span>
                                </div>
                                <p class="m-0 mt-1 text-xs text-hp-text-muted leading-relaxed">
                                    Ask for operational summaries, guest traffic breakdowns, amenity demand, or custom reports.
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2 rounded-2xl border border-glass-border bg-glass px-4 py-2 text-xs font-semibold text-hp-text">
                            <span class="inline-block h-2 w-2 rounded-full bg-emerald-500 animate-ping"></span>
                            Real-time DB Connection
                        </div>
                    </div>

                    {{-- AI Presets Grid --}}
                    <div class="mb-6">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="m-0 text-sm font-bold uppercase tracking-wider text-hp-text-muted">Quick Analytical Audits</h3>
                            <span class="text-xs text-hp-text-muted">Click any preset to generate instantly</span>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                            <button type="button" class="ai-preset-card group flex flex-col items-start rounded-2xl border border-glass-border bg-glass p-4 text-left shadow-sm cursor-pointer transition-all hover:border-emerald-500/40 hover:bg-glass-hover" data-ai-prompt="Analyze our total revenue performance, collected sales, outstanding balances, and average spend per reservation.">
                                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <h4 class="m-0 mb-1 text-sm font-bold text-hp-text">Revenue &amp; Financials</h4>
                                <p class="m-0 text-xs text-hp-text-muted leading-tight">Sales, unpaid dues & spending patterns</p>
                            </button>

                            <button type="button" class="ai-preset-card group flex flex-col items-start rounded-2xl border border-glass-border bg-glass p-4 text-left shadow-sm cursor-pointer transition-all hover:border-emerald-500/40 hover:bg-glass-hover" data-ai-prompt="What are our peak booking days, highest traffic months, and weekly visitor distribution trends?">
                                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                </div>
                                <h4 class="m-0 mb-1 text-sm font-bold text-hp-text">Peak Days & Forecast</h4>
                                <p class="m-0 text-xs text-hp-text-muted leading-tight">Weekend surges & seasonal demand</p>
                            </button>

                            <button type="button" class="ai-preset-card group flex flex-col items-start rounded-2xl border border-glass-border bg-glass p-4 text-left shadow-sm cursor-pointer transition-all hover:border-emerald-500/40 hover:bg-glass-hover" data-ai-prompt="List down all amenities and show a breakdown table of their bookings, revenue, and utilization.">
                                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                </div>
                                <h4 class="m-0 mb-1 text-sm font-bold text-hp-text">Amenity Utilization</h4>
                                <p class="m-0 text-xs text-hp-text-muted leading-tight">Catalog breakdown & demand</p>
                            </button>

                            <button type="button" class="ai-preset-card group flex flex-col items-start rounded-2xl border border-glass-border bg-glass p-4 text-left shadow-sm cursor-pointer transition-all hover:border-emerald-500/40 hover:bg-glass-hover" data-ai-prompt="Break down guest volumes, average party sizes, and online booking vs on-site walk-in distribution.">
                                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </div>
                                <h4 class="m-0 mb-1 text-sm font-bold text-hp-text">Guests & Channels</h4>
                                <p class="m-0 text-xs text-hp-text-muted leading-tight">Party sizes, online vs walk-in splits</p>
                            </button>

                            <button type="button" class="ai-preset-card group flex flex-col items-start rounded-2xl border border-glass-border bg-glass p-4 text-left shadow-sm cursor-pointer transition-all hover:border-emerald-500/40 hover:bg-glass-hover" data-ai-prompt="Provide strategic recommendations to improve front desk check-in efficiency and boost weekday visitor volume.">
                                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400 group-hover:scale-110 transition-transform">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                </div>
                                <h4 class="m-0 mb-1 text-sm font-bold text-hp-text">Operations Advice</h4>
                                <p class="m-0 text-xs text-hp-text-muted leading-tight">Actionable tips for staff operations</p>
                            </button>
                        </div>
                    </div>

                    {{-- AI Custom Natural-Language Query Box --}}
                    <section class="mb-6 rounded-3xl border border-glass-border bg-glass p-6 shadow-glass">
                        <form id="aiReportForm" class="flex flex-col gap-4">
                            <div class="flex items-center justify-between">
                                <label for="aiQueryInput" class="text-sm font-bold text-hp-text flex items-center gap-2">
                                    <svg class="h-4 w-4 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                    Custom Report Query
                                </label>
                                <span class="text-xs text-hp-text-muted">Type any question or scenario you want analyzed</span>
                            </div>

                            <div class="relative">
                                <textarea id="aiQueryInput" rows="3" class="w-full rounded-2xl border border-glass-border bg-glass-hover/60 p-4 text-sm text-hp-text placeholder-hp-text-muted/60 outline-none transition-all focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 resize-none" placeholder="e.g. 'Compare revenue between online and walk-in bookings', 'List down all amenities and their total bookings', 'What is our check-in and cancellation rate?'"></textarea>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex flex-wrap items-center gap-2 text-xs text-hp-text-muted">
                                    <span class="font-semibold">Quick Suggestions:</span>
                                    <button type="button" class="ai-suggest-btn rounded-full border border-glass-border bg-glass px-2.5 py-1 transition-all hover:bg-emerald-500/10 hover:text-emerald-600 hover:border-emerald-500/30 cursor-pointer" data-fill="Compare online booking revenue vs on-site walk-in revenue and show key metrics.">Online vs Walk-in</button>
                                    <button type="button" class="ai-suggest-btn rounded-full border border-glass-border bg-glass px-2.5 py-1 transition-all hover:bg-emerald-500/10 hover:text-emerald-600 hover:border-emerald-500/30 cursor-pointer" data-fill="List down all amenities and show a breakdown table.">All Amenities List</button>
                                    <button type="button" class="ai-suggest-btn rounded-full border border-glass-border bg-glass px-2.5 py-1 transition-all hover:bg-emerald-500/10 hover:text-emerald-600 hover:border-emerald-500/30 cursor-pointer" data-fill="Give me an operational summary of our cancellations and pending balances.">Cancellations & Balances</button>
                                </div>

                                <div class="flex items-center gap-3">
                                    <button type="button" id="aiClearBtn" class="inline-flex cursor-pointer items-center gap-1.5 rounded-xl border border-glass-border px-4 py-2.5 text-xs font-semibold text-hp-text-muted transition-all hover:bg-glass-hover">
                                        Clear
                                    </button>
                                    <button type="submit" id="aiSubmitBtn" class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-600/20 transition-all hover:bg-emerald-700 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <svg id="aiSubmitIcon" class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        <span id="aiSubmitText">Generate AI Analysis</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </section>

                    {{-- AI Report Output Container --}}
                    <div id="aiReportOutputContainer">
                        {{-- Initial Placeholder State --}}
                        <div id="aiEmptyState" class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-glass-border bg-glass/40 py-16 px-6 text-center shadow-sm">
                            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 animate-pulse-glow">
                                <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <h3 class="m-0 mb-2 font-display text-xl font-bold text-hp-text">Ready to Generate Your Custom AI Report</h3>
                            <p class="m-0 max-w-md text-sm text-hp-text-muted">
                                Select one of the quick analytical audit presets above or type a specific question about park revenue, amenities, or guest bookings.
                            </p>
                        </div>

                        {{-- Loading State --}}
                        <div id="aiLoadingState" class="hidden flex flex-col items-center justify-center rounded-3xl border border-glass-border bg-glass py-20 px-6 text-center shadow-glass">
                            <div class="relative mb-6">
                                <div class="h-16 w-16 rounded-full border-4 border-emerald-500/20 border-t-emerald-600 animate-spin"></div>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <svg class="h-6 w-6 text-emerald-600 animate-pulse" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                            </div>
                            <h3 class="m-0 mb-2 font-display text-lg font-bold text-hp-text" id="aiLoadingText">Analyzing Real-time Park Database...</h3>
                            <p class="m-0 text-xs text-hp-text-muted">Mining reservations, computing KPIs, and structuring findings</p>
                        </div>

                        {{-- Generated Report Container (Populated dynamically via JS) --}}
                        <div id="aiReportResults" class="hidden flex flex-col gap-6"></div>
                    </div>
                </div>

                <script>
                    window.reportData = {
                        monthlyLabels: @json($monthlyLabels),
                        monthlyRevenue: @json($monthlyRevenue),
                        statusCounts: @json($reportStatusCounts),
                        rawRows: @json($reportRows)
                    };
                </script>
            </main>
        </div>
    </div>

    <x-staff_chatbot />
</body>
</html>
