<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Reports — Hinaguan Nature Park</title>
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
        'resources/css/admin_css/admin_shared.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/admin_js/admin_reports.js',
        'resources/js/admin_chatbot.js',
    ])
    <style>
        body.admin-portal {
            background-color: #ebf3ec !important;
        }
        [data-theme="dark"] body.admin-portal {
            background-color: #06120a !important;
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
            background-color: #06120a !important;
            background-image: linear-gradient(rgba(6, 18, 10, 0.88), rgba(6, 18, 10, 0.92)), url('{{ asset('storage/design_images/staff-admin-background-image.jpeg') }}') !important;
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

        /* Hide print elements strictly on screen view */
        @media screen {
            .print-only-header,
            .print-summary-box,
            .print-ledger-title {
                display: none !important;
            }
        }

        .print-logo {
            max-width: 52px !important;
            max-height: 52px !important;
            width: 52px !important;
            height: 52px !important;
            object-fit: cover !important;
        }

        /* ===== PRINT SPECIFIC STYLES ===== */
        @media print {
            /* 1. Hide system UI chrome, sidebars, web headers, controls, and canvas charts */
            aside,
            header,
            nav,
            .dash-sidebar,
            .sidebar-wrapper,
            .header-container,
            .dash-header-wrap,
            #reportsFilters,
            #exportCsvBtn,
            #printReportsButton,
            #resetFiltersBtn,
            .preset-chip,
            .web-only-section,
            .web-only-charts,
            .dash-header {
                display: none !important;
            }

            /* 2. Reset Page Layout & Backgrounds for Pure White Paper */
            @page {
                size: A4 portrait;
                margin: 15mm 15mm 15mm 15mm;
            }

            html, body {
                background: #ffffff !important;
                color: #000000 !important;
                font-family: Arial, Helvetica, sans-serif !important;
                font-size: 10pt !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .dash-layout, .dash-main, .dash-content {
                display: block !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
            }

            /* 3. Official Clean Print Header (No Colors) */
            .print-only-header {
                display: block !important;
                margin-bottom: 20px !important;
                border-bottom: 2px solid #000000 !important;
                padding-bottom: 12px !important;
            }

            .print-header-top {
                display: flex !important;
                align-items: center !important;
                gap: 16px !important;
                margin-bottom: 12px !important;
            }

            .print-title {
                font-size: 16pt !important;
                font-weight: 700 !important;
                margin: 0 !important;
                letter-spacing: 0.5px !important;
                color: #000000 !important;
                text-transform: uppercase !important;
            }

            .print-subtitle {
                font-size: 9.5pt !important;
                color: #333333 !important;
                margin: 2px 0 0 0 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
            }

            .print-meta-grid {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 6px 20px !important;
                font-size: 9pt !important;
                color: #000000 !important;
                background: #ffffff !important;
                padding: 8px 12px !important;
                border: 1px solid #000000 !important;
            }

            /* 4. Clean Monochrome Summary Grid Table */
            .print-summary-box {
                display: table !important;
                width: 100% !important;
                margin-bottom: 20px !important;
                border-collapse: collapse !important;
            }

            .print-summary-row {
                display: table-row !important;
            }

            .print-summary-cell {
                display: table-cell !important;
                padding: 8px 10px !important;
                border: 1px solid #000000 !important;
                text-align: center !important;
                width: 25% !important;
                background: #ffffff !important;
            }

            .print-summary-val {
                font-size: 13pt !important;
                font-weight: bold !important;
                display: block !important;
                color: #000000 !important;
            }

            .print-summary-lbl {
                font-size: 8pt !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
                color: #222222 !important;
            }

            /* 5. Clean Monochrome Ledger Table */
            .print-ledger-section {
                border: none !important;
                box-shadow: none !important;
                background: transparent !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .print-ledger-title {
                font-size: 11pt !important;
                font-weight: bold !important;
                text-transform: uppercase !important;
                margin-bottom: 8px !important;
                color: #000000 !important;
            }

            .dash-table-wrap {
                overflow: visible !important;
            }

            table.dash-table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin-top: 6px !important;
                font-size: 9pt !important;
            }

            table.dash-table th {
                background-color: #f0f0f0 !important;
                color: #000000 !important;
                border: 1px solid #000000 !important;
                padding: 6px 8px !important;
                font-weight: bold !important;
                text-transform: uppercase !important;
                font-size: 8pt !important;
                text-align: left !important;
            }

            table.dash-table td {
                border: 1px solid #000000 !important;
                padding: 6px 8px !important;
                color: #000000 !important;
                background: transparent !important;
            }

            table.dash-table tr {
                page-break-inside: avoid !important;
            }

            .status-pill {
                background: transparent !important;
                color: #000000 !important;
                border: none !important;
                padding: 0 !important;
                font-weight: bold !important;
                font-size: 8.5pt !important;
                text-transform: capitalize !important;
            }

            .mono-cell {
                font-family: inherit !important;
            }
        }
    </style>
</head>
<body class="antialiased admin-portal">
    <div class="dash-layout">
        <x-admin_sidemenu active="reports" userName="{{ session('auth_user.name') ?? 'Admin User' }}" userRole="Admin" />

        <div class="dash-main">
            <main class="dash-content p-6">
                <!-- PRINT ONLY OFFICIAL REPORT HEADER -->
                <div class="print-only-header hidden print:block">
                    <div class="print-header-top">
                        <img src="{{ asset('storage/design_images/main_logo.jpeg') }}" alt="Hinaguan Nature Park Logo" class="print-logo">
                        <div>
                            <h1 class="print-title">Hinaguan Nature Park</h1>
                            <p class="print-subtitle">Official Reservation & Revenue Operational Report</p>
                        </div>
                    </div>
                    <div class="print-meta-grid">
                        <div><strong>Date Generated:</strong> {{ now()->format('F d, Y - h:i A') }}</div>
                        <div><strong>Filter Amenity:</strong> <span id="printAmenityLabel">All Amenities</span></div>
                        <div><strong>Filter Status:</strong> <span id="printStatusLabel">All Statuses</span></div>
                        <div><strong>Date Range:</strong> <span id="printDateRangeLabel">All Time</span></div>
                    </div>
                </div>

                <div class="web-only-section">
                    <x-header
                        title="Admin Reports & Analytics"
                        subtitle="Comprehensive analytics, revenue performance, and park operational ledger"
                    />
                </div>

                @php
                    $averageSpend = $totalReservations > 0 ? $revenue / $totalReservations : 0;
                @endphp

                <!-- PRINT ONLY SUMMARY METRICS TABLE -->
                <div class="print-summary-box hidden print:table">
                    <div class="print-summary-row">
                        <div class="print-summary-cell">
                            <span class="print-summary-val" id="printKpiRes">{{ $totalReservations }}</span>
                            <span class="print-summary-lbl">Total Reservations</span>
                        </div>
                        <div class="print-summary-cell">
                            <span class="print-summary-val" id="printKpiGuests">{{ $totalGuests }}</span>
                            <span class="print-summary-lbl">Total Guests</span>
                        </div>
                        <div class="print-summary-cell">
                            <span class="print-summary-val" id="printKpiRev">₱{{ number_format($revenue, 2) }}</span>
                            <span class="print-summary-lbl">Total Revenue</span>
                        </div>
                        <div class="print-summary-cell">
                            <span class="print-summary-val">₱{{ number_format($averageSpend, 2) }}</span>
                            <span class="print-summary-lbl">Avg / Reservation</span>
                        </div>
                    </div>
                </div>

                {{-- ===== FILTER TOOLBAR ===== --}}
                <section class="group is-open mb-6 overflow-hidden rounded-2xl border border-glass-border bg-glass p-6 shadow-glass transition-all duration-300" id="reportsFilters">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-glass-border pb-4" id="filterToggleBtn">
                        <div class="flex items-center gap-4">
                            <div class="flex h-11 w-11 items-center justify-center rounded-[10px] bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1a3324] dark:text-[#6ab88c]">
                                <svg class="h-[22px] w-[22px]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                            </div>
                            <div>
                                <h3 class="m-0 text-lg font-semibold text-hp-text">Filter Operational Ledger</h3>
                                <p class="m-0 text-sm text-hp-text-muted">Narrow reservations by amenity, status, payment status, or date range</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button" class="inline-flex cursor-pointer items-center gap-2 rounded-[10px] border border-glass-border px-3.5 py-2 text-xs font-semibold text-hp-text transition-all hover:bg-glass-hover" id="exportCsvBtn">
                                <svg class="h-4 w-4 text-hp-green-mid" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Export CSV
                            </button>
                            <button type="button" class="inline-flex cursor-pointer items-center gap-2 rounded-[10px] border border-glass-border px-3.5 py-2 text-xs font-semibold text-hp-text transition-all hover:bg-glass-hover" id="printReportsButton">
                                <svg class="h-4 w-4 text-hp-green-mid" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Print PDF
                            </button>
                            <button type="button" class="inline-flex cursor-pointer items-center gap-2 rounded-[10px] border border-glass-border px-3.5 py-2 text-xs font-semibold text-hp-text transition-all hover:bg-glass-hover" id="resetFiltersBtn">
                                <svg class="h-4 w-4 text-hp-text-muted" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Reset
                            </button>
                        </div>
                    </div>

                    <div class="pt-4">
                        <div class="mb-5 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
                            <label class="flex flex-col gap-2">
                                <span class="text-xs font-semibold text-hp-text-muted">Amenity</span>
                                <select id="amenityFilter" class="w-full rounded-xl border border-glass-border bg-transparent px-3 py-2 text-sm text-hp-text outline-none focus:border-hp-green-mid">
                                    <option value="all">All amenities</option>
                                    @foreach($amenityOptions as $amenityOption)
                                        <option value="{{ $amenityOption }}">{{ $amenityOption }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="text-xs font-semibold text-hp-text-muted">Reservation Status</span>
                                <select id="statusFilter" class="w-full rounded-xl border border-glass-border bg-transparent px-3 py-2 text-sm text-hp-text outline-none focus:border-hp-green-mid">
                                    <option value="all">All statuses</option>
                                    @foreach($statusOptions as $statusOption)
                                        <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="text-xs font-semibold text-hp-text-muted">Check-in Range</span>
                                <div class="flex items-center gap-2">
                                    <input id="dateFrom" type="date" value="" class="w-full rounded-xl border border-glass-border bg-transparent px-3 py-2 text-sm text-hp-text outline-none focus:border-hp-green-mid">
                                    <span class="text-hp-text-muted">→</span>
                                    <input id="dateTo" type="date" value="" class="w-full rounded-xl border border-glass-border bg-transparent px-3 py-2 text-sm text-hp-text outline-none focus:border-hp-green-mid">
                                </div>
                            </label>
                            <label class="flex flex-col gap-2">
                                <span class="text-xs font-semibold text-hp-text-muted">Active Filter Output</span>
                                <div class="flex h-[38px] items-center rounded-xl border border-glass-border bg-glass-hover px-3 text-xs font-semibold text-hp-text-muted" id="activeFilterText">
                                    Showing all reservations
                                </div>
                            </label>
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="text-xs font-semibold text-hp-text-muted">Presets:</span>
                            <button type="button" class="preset-chip cursor-pointer rounded-full border border-glass-border px-3.5 py-1 text-xs font-medium text-hp-text transition-all hover:bg-hp-green-soft is-active:bg-hp-green-mid is-active:text-white" data-preset="today">Today</button>
                            <button type="button" class="preset-chip cursor-pointer rounded-full border border-glass-border px-3.5 py-1 text-xs font-medium text-hp-text transition-all hover:bg-hp-green-soft is-active:bg-hp-green-mid is-active:text-white" data-preset="7d">Last 7 days</button>
                            <button type="button" class="preset-chip cursor-pointer rounded-full border border-glass-border px-3.5 py-1 text-xs font-medium text-hp-text transition-all hover:bg-hp-green-soft is-active:bg-hp-green-mid is-active:text-white" data-preset="30d">Last 30 days</button>
                            <button type="button" class="preset-chip cursor-pointer rounded-full border border-glass-border px-3.5 py-1 text-xs font-medium text-hp-text transition-all hover:bg-hp-green-soft is-active:bg-hp-green-mid is-active:text-white" data-preset="month">This month</button>
                            <button type="button" class="preset-chip is-active cursor-pointer rounded-full border border-glass-border px-3.5 py-1 text-xs font-medium text-hp-text transition-all hover:bg-hp-green-soft is-active:bg-hp-green-mid is-active:text-white" data-preset="all">All time</button>
                        </div>
                    </div>
                </section>

                {{-- ===== KPI STAT CARDS (WEB ONLY) ===== --}}
                <div class="web-only-section mb-6 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                    <article class="flex items-start gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1a3324] dark:text-[#6ab88c]">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="flex flex-col">
                            <h4 class="m-0 mb-0.5 font-display text-2xl font-bold text-hp-text" id="kpiReservations">{{ $totalReservations }}</h4>
                            <p class="m-0 mb-1 text-sm font-semibold text-hp-text-muted">Total Reservations</p>
                            <span class="text-xs text-hp-text-muted opacity-70">• Active in ledger</span>
                        </div>
                    </article>

                    <article class="flex items-start gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#eaf5e1] text-[#4b8022] dark:bg-[#213316] dark:text-[#96c76e]">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <div class="flex flex-col">
                            <h4 class="m-0 mb-0.5 font-display text-2xl font-bold text-hp-text">{{ $totalGuests }}</h4>
                            <p class="m-0 mb-1 text-sm font-semibold text-hp-text-muted">Total Guests</p>
                            <span class="text-xs text-hp-text-muted opacity-70">• Booked visitor volume</span>
                        </div>
                    </article>

                    <article class="flex items-start gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#e5f0f6] text-[#2a6a8f] dark:bg-[#182c38] dark:text-[#6ea9c9]">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="flex flex-col">
                            <h4 class="m-0 mb-0.5 font-display text-2xl font-bold text-hp-text" id="kpiRevenue">₱{{ number_format($revenue, 2) }}</h4>
                            <p class="m-0 mb-1 text-sm font-semibold text-hp-text-muted">Total Revenue</p>
                            <span class="text-xs text-hp-text-muted opacity-70">• Filtered gross revenue</span>
                        </div>
                    </article>

                    <article class="flex items-start gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-glass">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#f0e9f4] text-[#6d4b8e] dark:bg-[#2b1f33] dark:text-[#a889c4]">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <div class="flex flex-col">
                            <h4 class="m-0 mb-0.5 font-display text-2xl font-bold text-hp-text">₱{{ number_format($averageSpend, 2) }}</h4>
                            <p class="m-0 mb-1 text-sm font-semibold text-hp-text-muted">Avg / Reservation</p>
                            <span class="text-xs text-hp-text-muted opacity-70">• Average booking amount</span>
                        </div>
                    </article>
                </div>

                {{-- ===== CHARTS GRID (WEB ONLY) ===== --}}
                <div class="web-only-charts web-only-section mb-6 grid grid-cols-1 gap-6 xl:grid-cols-[2fr_1fr]">
                    {{-- Revenue Trend Area Chart --}}
                    <section class="flex flex-col rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
                        <div class="mb-6 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1a3324] dark:text-[#6ab88c]">
                                    <svg class="h-[18px] w-[18px]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                </div>
                                <h3 class="m-0 text-lg font-semibold text-hp-text">Revenue Performance Trend</h3>
                            </div>
                        </div>
                        <div class="relative min-h-[280px] w-full flex-1">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </section>

                    {{-- Status Donut Chart --}}
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
                                    <span class="block text-[1.8rem] font-bold leading-none text-hp-text" id="donutTotalCount">{{ $totalReservations }}</span>
                                    <span class="text-xs uppercase tracking-[0.5px] text-hp-text-muted">Total</span>
                                </div>
                            </div>
                            <div class="flex w-full flex-col gap-2.5" id="donutLegendContainer">
                                <!-- Populated dynamically by JS -->
                            </div>
                        </div>
                    </section>
                </div>

                <div class="web-only-charts web-only-section mb-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
                    {{-- Top Amenities Breakdown --}}
                    <section class="flex flex-col rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
                        <div class="mb-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1a3324] dark:text-[#6ab88c]">
                                    <svg class="h-[18px] w-[18px]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                </div>
                                <div>
                                    <h3 class="m-0 text-lg font-semibold text-hp-text">Popular Amenities</h3>
                                    <p class="m-0 text-xs text-hp-text-muted">Most reserved amenities</p>
                                </div>
                            </div>
                        </div>
                        <div class="mb-4 flex justify-between border-b border-glass-border pb-2 text-xs font-semibold uppercase tracking-[0.5px] text-hp-text-muted">
                            <span>Amenity Name</span>
                            <span>Bookings</span>
                        </div>
                        <div class="flex flex-col gap-4" id="topAmenitiesContainer">
                            <!-- Populated dynamically by JS -->
                        </div>
                    </section>

                    {{-- Peak Days Breakdown --}}
                    <section class="flex flex-col rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
                        <div class="mb-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1a3324] dark:text-[#6ab88c]">
                                    <svg class="h-[18px] w-[18px]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <h3 class="m-0 text-lg font-semibold text-hp-text">Peak Visitor Days</h3>
                                    <p class="m-0 text-xs text-hp-text-muted">Distribution by day of week</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-1 items-end justify-between gap-2 pt-6 pb-2" id="peakDaysContainer">
                            <!-- Populated dynamically by JS -->
                        </div>
                    </section>
                </div>

                {{-- ===== LEDGER TABLE ===== --}}
                <section class="print-ledger-section rounded-2xl border border-glass-border bg-glass p-6 shadow-glass">
                    <div class="web-only-section mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="m-0 text-lg font-semibold text-hp-text">Detailed Reservation Ledger</h3>
                            <p class="m-0 text-xs text-hp-text-muted">All active records in the selected view</p>
                        </div>
                    </div>
                    <div class="print-ledger-title hidden">Reservation Operational Records</div>
                    <div class="dash-table-wrap overflow-x-auto">
                        <table class="dash-table w-full text-left text-sm" id="reservationsTable">
                            <thead>
                                <tr class="border-b border-glass-border text-xs uppercase tracking-wider text-hp-text-muted">
                                    <th class="py-3 px-3">Booker</th>
                                    <th class="py-3 px-3">Amenity</th>
                                    <th class="py-3 px-3">Check-in Date</th>
                                    <th class="py-3 px-3">Guests</th>
                                    <th class="py-3 px-3">Amount</th>
                                    <th class="py-3 px-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reservations as $r)
                                    @php
                                        $amenitiesStr = $r->reservationAmenities->pluck('amenity.amenities_name')->filter()->join(', ') ?: 'None';
                                        $checkInStr = $r->reservation_date ? \Illuminate\Support\Carbon::parse($r->reservation_date)->format('Y-m-d') : '';
                                    @endphp
                                    <tr class="border-b border-glass-border/50 hover:bg-glass-hover"
                                        data-amenity="{{ strtolower($amenitiesStr) }}"
                                        data-status="{{ strtolower($r->status) }}"
                                        data-checkin="{{ $checkInStr }}">
                                        <td class="py-3 px-3 font-medium text-hp-text">{{ $r->booker_name }}</td>
                                        <td class="py-3 px-3 text-xs text-hp-text-muted">{{ $amenitiesStr }}</td>
                                        <td class="mono-cell py-3 px-3 text-xs text-hp-text-muted">{{ $r->reservation_date ? \Illuminate\Support\Carbon::parse($r->reservation_date)->format('M d, Y') : 'N/A' }}</td>
                                        <td class="py-3 px-3 text-xs text-hp-text">{{ $r->number_of_guests }}</td>
                                        <td class="py-3 px-3 font-semibold text-hp-text">₱{{ number_format($r->amount_paid, 2) }}</td>
                                        <td class="py-3 px-3">
                                            <span class="status-pill status-pill--{{ strtolower(str_replace(' ', '-', $r->status)) }} rounded-full px-2.5 py-1 text-[0.7rem] font-bold">{{ $r->status }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-6 text-center text-sm text-hp-text-muted">No reservations found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script>
        window.reportData = {
            rawRows: [
                @foreach($reservations as $r)
                {
                    id: {{ $r->id }},
                    customer_name: @json($r->booker_name),
                    amenities: @json($r->reservationAmenities->pluck('amenity.amenities_name')->filter()->join(', ')),
                    status: @json($r->status),
                    payment_status: @json($r->payment_status ?? 'Paid'),
                    check_in: @json($r->reservation_date ? \Illuminate\Support\Carbon::parse($r->reservation_date)->format('Y-m-d') : null),
                    amount: {{ (float)$r->amount_paid }},
                    guests: {{ (int)$r->number_of_guests }}
                }@if(!$loop->last),@endif
                @endforeach
            ]
        };
    </script>
    {{-- Admin AI Intelligence Chatbot --}}
    <x-admin_chatbot />
</body>
</html>
