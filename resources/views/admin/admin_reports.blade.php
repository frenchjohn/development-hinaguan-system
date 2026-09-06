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
    <script src="{{ asset('vendor/xlsx/xlsx.full.min.js') }}"></script>
    <script>
        if (typeof XLSX === 'undefined') {
            document.write('<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"><\/script>');
        }
    </script>
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

        /* Hide print elements strictly on screen view */
        @media screen {
            .print-only-header,
            .print-summary-box,
            .print-ledger-title {
                display: none !important;
            }
        }

        .report-tab-btn {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .report-tab-btn:not(.is-active):hover {
            background-color: rgba(255, 255, 255, 0.5) !important;
            color: var(--hp-text, #111827) !important;
        }
        [data-theme="dark"] .report-tab-btn:not(.is-active):hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: #ffffff !important;
        }
        .report-tab-btn.is-active {
            color: #ffffff !important;
            background: #1c5c3c !important;
            box-shadow: 0 4px 14px rgba(28, 92, 60, 0.3) !important;
        }
        [data-theme="dark"] .report-tab-btn.is-active {
            background: #1c5c3c !important;
            color: #ffffff !important;
        }

        .matrix-sub-tab {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .matrix-sub-tab:not(.is-active):hover {
            background-color: rgba(255, 255, 255, 0.5) !important;
            color: var(--hp-text, #111827) !important;
        }
        [data-theme="dark"] .matrix-sub-tab:not(.is-active):hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: #ffffff !important;
        }
        .matrix-sub-tab.is-active {
            background: #1c5c3c !important;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(28, 92, 60, 0.25) !important;
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

        .print-logo {
            max-width: 52px !important;
            max-height: 52px !important;
            width: 52px !important;
            height: 52px !important;
            object-fit: cover !important;
        }

        /* Matrix Cell Styling Utilities */
        .matrix-cell-val {
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 26px;
            height: 24px;
            border-radius: 6px;
        }

        /* ===== PRINT SPECIFIC STYLES ===== */
        @media print {
            aside, header, nav, .dash-sidebar, .sidebar-wrapper, .header-container, .dash-header-wrap, #reportsFilters, #exportCsvBtn, #printReportsButton, #resetFiltersBtn, .preset-chip, .matrix-tab-item, .matrix-controls-panel, .web-only-section, .web-only-charts, .dash-header { display: none !important; }

            @page { size: A4 portrait; margin: 15mm; }
            html, body { background: #ffffff !important; color: #000000 !important; font-family: Arial, sans-serif !important; font-size: 10pt !important; }
            
            .print-only-header { display: block !important; border-bottom: 2px solid #000 !important; margin-bottom: 20px !important; }
            .print-meta-grid { display: grid !important; grid-template-columns: repeat(2, 1fr) !important; gap: 6px 20px !important; font-size: 9pt !important; color: #000000 !important; background: #ffffff !important; padding: 8px 0 !important; border-top: 1px solid #e5e7eb !important; }

            /* 4. Official Clean Metric Summary Boxes */
            .print-summary-box { width: 100% !important; margin-bottom: 20px !important; border: 1px solid #000000 !important; background: #ffffff !important; }
            .print-summary-row { display: flex !important; width: 100% !important; }
            .print-summary-cell { flex: 1 !important; padding: 10px 12px !important; text-align: center !important; border-right: 1px solid #000000 !important; }
            .print-summary-cell:last-child { border-right: none !important; }
            .print-summary-val { display: block !important; font-size: 13pt !important; font-weight: 700 !important; color: #000000 !important; margin-bottom: 2px !important; }
            .print-summary-lbl { display: block !important; font-size: 8pt !important; text-transform: uppercase !important; font-weight: 600 !important; color: #4b5563 !important; }

            /* 5. Clean Official Ledger Table */
            .print-ledger-title { display: block !important; font-size: 11pt !important; font-weight: 700 !important; text-transform: uppercase !important; margin: 16px 0 8px 0 !important; color: #000000 !important; letter-spacing: 0.3px !important; }
            .print-table-wrapper { overflow: visible !important; border: 1px solid #000000 !important; background: transparent !important; box-shadow: none !important; border-radius: 0 !important; }
            table { width: 100% !important; border-collapse: collapse !important; font-size: 8.5pt !important; }
            thead th { background: #f3f4f6 !important; color: #000000 !important; font-weight: 700 !important; text-transform: uppercase !important; border: 1px solid #000000 !important; padding: 6px 8px !important; }
            tbody td { border: 1px solid #e5e7eb !important; padding: 6px 8px !important; color: #000000 !important; }
            tbody tr:nth-child(even) { background-color: #fafafa !important; }
            .status-pill { border: none !important; background: transparent !important; padding: 0 !important; font-weight: 600 !important; color: #000000 !important; }
        }
    </style>
</head>
<body class="admin-portal font-sans antialiased text-hp-text">
    <div class="dash-layout">
        <x-admin_sidemenu active="reports" userName="{{ session('auth_user.name') ?? 'Admin User' }}" userRole="Admin" />

        <div class="dash-main">
            <x-header
                title="Admin Reports & Analytics"
                subtitle="Comprehensive analytics, revenue performance, and park operational ledger"
            />

            <main class="dash-content p-6">

                <!-- SECTION NAVIGATION TABS -->
                <div class="web-only-section mb-6 flex flex-wrap items-center justify-between gap-4">
                    <div class="inline-flex rounded-2xl border border-glass-border bg-glass p-1.5 shadow-glass backdrop-blur-md">
                        <button type="button" id="tabMatrixReports" class="report-tab-btn is-active flex items-center gap-2.5 rounded-xl px-5 py-2.5 text-sm font-semibold transition-all duration-200 cursor-pointer bg-[#1c5c3c] text-white shadow-md shadow-[#1c5c3c]/20">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span>Daily Occupancy Matrix</span>
                        </button>
                        <button type="button" id="tabStandardReports" class="report-tab-btn flex items-center gap-2.5 rounded-xl px-5 py-2.5 text-sm font-semibold transition-all duration-200 cursor-pointer text-hp-text-muted hover:text-hp-text hover:bg-glass-hover">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <span>Standard Reports</span>
                        </button>
                        <button type="button" id="tabAiReports" class="report-tab-btn flex items-center gap-2.5 rounded-xl px-5 py-2.5 text-sm font-semibold transition-all duration-200 cursor-pointer text-hp-text-muted hover:text-hp-text hover:bg-glass-hover">
                            <svg class="h-4 w-4 text-emerald-500 animate-pulse" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span>AI Report Studio</span>
                            <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[0.65rem] font-bold text-emerald-600 dark:text-emerald-400">AI Innovation</span>
                        </button>
                    </div>

                    <div class="hidden sm:flex items-center gap-2 text-xs text-hp-text-muted">
                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-500 animate-ping"></span>
                        <span>Live Database Analytics Sync</span>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- SECTION 1: DAILY AMENITY & ROOM OCCUPANCY MATRIX         -->
                <!-- ========================================================= -->
                <div id="matrixReportsSection" class="transition-opacity duration-300">
                    <section class="mb-8 overflow-hidden rounded-3xl border border-glass-border bg-glass p-6 md:p-8 shadow-glass transition-all duration-300" id="amenityMatrixSection">
                        {{-- Top Header matching user's official Excel format --}}
                        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-5 border-b border-glass-border pb-6 mb-6">
                            <div class="flex items-start gap-4">
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1e2220] dark:text-[#6ab88c] shadow-sm">
                                    <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span class="rounded-md bg-hp-green-mid/15 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider text-hp-green-mid" id="matrixActiveMonthLabel">
                                            SEPTEMBER 2026
                                        </span>
                                    </div>
                                    <h2 class="m-0 font-display text-2xl font-bold text-hp-text">
                                        Daily Amenity & Room Occupancy Matrix
                                    </h2>
                                    <p class="m-0 text-xs text-hp-text-muted mt-0.5" id="matrixSubtitlePeriod">
                                        Sep 1, 2026 to Sep 30, 2026 (30 days)
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                                <button type="button" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white px-4 py-2.5 text-xs font-semibold shadow-sm transition-all active:scale-95" id="exportMatrixExcelBtn" title="Download formatted Excel workbook matching official template with full column widths">
                                    <svg class="h-4 w-4 text-emerald-200" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm1.5 12.5-1.5 2.5 1.5 2.5h-1.6l-.9-1.7-.9 1.7H9l1.5-2.5L9 14.5h1.6l.9 1.7.9-1.7h1.6zM13 9V3.5L18.5 9H13z"/>
                                    </svg>
                                    <span>Download Excel (.xlsx)</span>
                                </button>
                                <button type="button" class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl bg-hp-green-mid px-4 py-2.5 text-xs font-semibold text-white shadow-md shadow-hp-green-mid/20 transition-all hover:bg-[#15462e] active:scale-95" id="printMatrixPdfBtn">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    <span>Download / Print PDF</span>
                                </button>
                            </div>
                        </div>                        {{-- SUB-VIEW SWITCHER INSIDE DAILY OCCUPANCY SECTION --}}
                        <div class="mb-5 flex flex-wrap items-center justify-between gap-4 border-b border-glass-border pb-4">
                            <div class="inline-flex rounded-2xl border border-glass-border bg-glass p-1.5 shadow-xs">
                                <button type="button" id="subTabRoomsMatrix" class="matrix-sub-tab is-active flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all duration-200 cursor-pointer bg-[#1c5c3c] text-white shadow-xs">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <span>Amenity & Room Occupancy Matrix</span>
                                </button>
                                <button type="button" id="subTabGuestsMatrix" class="matrix-sub-tab flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold text-hp-text-muted hover:text-hp-text hover:bg-glass-hover transition-all duration-200 cursor-pointer">
                                    <svg class="h-4 w-4 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <span>Number of Guests Matrix (Demographics)</span>
                                </button>
                            </div>
                            
                            <div class="text-xs text-hp-text-muted">
                                <span class="font-medium">Active Sheet:</span>
                                <span id="matrixCurrentViewLabel" class="font-bold text-hp-text ml-1">Monthly Room Occupancy</span>
                            </div>
                        </div>

                        {{-- Control & Filter Toolbar for Matrix --}}
                        <div class="matrix-controls-panel mb-6 flex flex-col gap-5 rounded-2xl border border-glass-border bg-glass p-5 shadow-sm">
                            {{-- Row 1: Quick Presets & Custom Date Range --}}
                            <div class="flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-hp-text mr-1">
                                        <svg class="h-3.5 w-3.5 text-hp-green-mid" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Time Presets:
                                    </span>
                                    <button type="button" class="matrix-tab-item cursor-pointer inline-flex items-center justify-center px-3.5 py-1.5 rounded-full text-xs font-semibold border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#1a201c] text-gray-700 dark:text-gray-200 shadow-xs hover:border-hp-green-mid hover:text-hp-green-mid transition-all" data-matrix-preset="today">Today</button>
                                    <button type="button" class="matrix-tab-item cursor-pointer inline-flex items-center justify-center px-3.5 py-1.5 rounded-full text-xs font-semibold border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#1a201c] text-gray-700 dark:text-gray-200 shadow-xs hover:border-hp-green-mid hover:text-hp-green-mid transition-all" data-matrix-preset="7d">Last 7 Days</button>
                                    <button type="button" class="matrix-tab-item is-active cursor-pointer inline-flex items-center justify-center px-3.5 py-1.5 rounded-full text-xs font-semibold border border-[#1c5c3c] bg-[#1c5c3c] text-white shadow-xs transition-all" data-matrix-preset="1m">This Month (1 Month)</button>
                                    <button type="button" class="matrix-tab-item cursor-pointer inline-flex items-center justify-center px-3.5 py-1.5 rounded-full text-xs font-semibold border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#1a201c] text-gray-700 dark:text-gray-200 shadow-xs hover:border-hp-green-mid hover:text-hp-green-mid transition-all" data-matrix-preset="last_month">Last Month</button>
                                    <button type="button" class="matrix-tab-item cursor-pointer inline-flex items-center justify-center px-3.5 py-1.5 rounded-full text-xs font-semibold border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#1a201c] text-gray-700 dark:text-gray-200 shadow-xs hover:border-hp-green-mid hover:text-hp-green-mid transition-all" data-matrix-preset="3m">Last 3 Months</button>
                                    <button type="button" class="matrix-tab-item cursor-pointer inline-flex items-center justify-center px-3.5 py-1.5 rounded-full text-xs font-semibold border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#1a201c] text-gray-700 dark:text-gray-200 shadow-xs hover:border-hp-green-mid hover:text-hp-green-mid transition-all" data-matrix-preset="1y">1 Year (This Year)</button>
                                    <button type="button" class="matrix-tab-item cursor-pointer inline-flex items-center justify-center px-3.5 py-1.5 rounded-full text-xs font-semibold border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#1a201c] text-gray-700 dark:text-gray-200 shadow-xs hover:border-hp-green-mid hover:text-hp-green-mid transition-all" data-matrix-preset="all">All Time</button>
                                </div>

                                <div class="flex items-center gap-2.5 shrink-0 bg-white dark:bg-[#1a201c] p-1.5 rounded-xl border border-gray-300/80 dark:border-gray-700/80 shadow-xs">
                                    <span class="text-xs font-bold text-hp-text-muted px-1">Date Range:</span>
                                    <input id="matrixDateFrom" type="date" class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-black/20 px-2.5 py-1 text-xs text-hp-text outline-none focus:border-hp-green-mid" />
                                    <span class="text-xs text-hp-text-muted font-bold">→</span>
                                    <input id="matrixDateTo" type="date" class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-black/20 px-2.5 py-1 text-xs text-hp-text outline-none focus:border-hp-green-mid" />
                                    <button type="button" id="matrixApplyDateBtn" class="cursor-pointer rounded-lg bg-hp-green-mid px-3 py-1 text-xs font-bold text-white shadow-xs hover:bg-[#15462e] transition-colors">
                                        Apply
                                    </button>
                                </div>
                            </div>

                            {{-- Row 2: Clean Columns Bar with Modal Launcher (Only visible in Rooms Matrix view) --}}
                            <div id="matrixRoomsControlsRow" class="border-t border-glass-border/60 pt-3 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="button" id="openMatrixColumnsModalBtn" class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-hp-green-mid/40 bg-hp-green-mid/10 hover:bg-hp-green-mid/20 dark:bg-hp-green-mid/15 dark:hover:bg-hp-green-mid/25 px-3.5 py-2 text-xs font-bold text-hp-green-mid transition-all active:scale-95 shadow-xs">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                        </svg>
                                        <span>Select Columns / Amenities</span>
                                        <span id="matrixActiveColumnsBadge" class="rounded-full bg-hp-green-mid text-white px-2 py-0.5 text-[0.65rem] font-extrabold tracking-wide shadow-xs">
                                            {{ $allAmenities->count() }} of {{ $allAmenities->count() }} Active
                                        </span>
                                    </button>
                                    <span id="matrixColumnsSummaryText" class="text-xs text-hp-text-muted font-medium">
                                        Showing all {{ $allAmenities->count() }} amenities
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button type="button" id="matrixQuickResetAllBtn" class="text-xs font-semibold text-hp-green-mid hover:underline cursor-pointer flex items-center gap-1.5 transition-colors">
                                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        <span>Reset to All Amenities</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- MODAL: CUSTOMIZE COLUMNS / AMENITIES --}}
                        <div id="matrixColumnsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm opacity-0 pointer-events-none transition-all duration-200">
                            <div class="matrix-modal-card relative w-full max-w-2xl rounded-3xl border border-glass-border bg-white dark:bg-[#161a17] text-hp-text shadow-2xl overflow-hidden flex flex-col max-h-[85vh] transform scale-95 transition-transform duration-200">
                                {{-- Modal Header --}}
                                <div class="flex items-center justify-between border-b border-glass-border px-6 py-5 bg-gray-50/50 dark:bg-white/[0.02]">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1e2220] dark:text-[#6ab88c] shadow-xs">
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-base font-bold text-hp-text">Select Columns / Amenities</h3>
                                            <p class="text-xs text-hp-text-muted">Choose which rooms or cottages appear in the matrix ledger</p>
                                        </div>
                                    </div>
                                    <button type="button" id="closeMatrixColumnsModalBtn" class="h-8 w-8 rounded-full border border-glass-border flex items-center justify-center text-hp-text-muted hover:text-hp-text hover:bg-glass-hover transition-colors cursor-pointer">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>

                                {{-- Modal Search & Master Bar --}}
                                <div class="px-6 py-3.5 border-b border-glass-border bg-white dark:bg-[#161a17] flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                                    <div class="relative flex-1">
                                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-hp-text-muted" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                        <input type="text" id="matrixAmenitySearchInput" placeholder="Search amenities (e.g. A-House 1, Bamboo...)" class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-black/20 text-hp-text outline-none focus:border-hp-green-mid" />
                                    </div>
                                    <div class="flex items-center gap-3 shrink-0">
                                        <button type="button" id="matrixSelectAllAmenitiesBtn" class="text-xs font-semibold text-hp-green-mid hover:underline cursor-pointer">Select All</button>
                                        <span class="text-gray-300 dark:text-gray-700">|</span>
                                        <button type="button" id="matrixClearAllAmenitiesBtn" class="text-xs font-semibold text-hp-text-muted hover:text-rose-500 cursor-pointer">Clear All</button>
                                    </div>
                                </div>

                                {{-- Modal Body: Categorized Amenity Checklist --}}
                                <div class="px-6 py-5 overflow-y-auto max-h-[50vh] space-y-5 bg-gray-50/30 dark:bg-black/10" id="matrixModalAmenityList">
                                    {{-- Master Select All Row --}}
                                    <label class="flex items-center justify-between p-3.5 rounded-2xl border border-hp-green-mid/30 bg-hp-green-mid/5 hover:bg-hp-green-mid/10 transition-colors cursor-pointer">
                                        <div class="flex items-center gap-3">
                                            <input type="checkbox" id="matrixToggleAllCheckbox" class="h-4 w-4 rounded border-gray-300 text-hp-green-mid focus:ring-hp-green-mid cursor-pointer" checked>
                                            <span class="text-xs font-bold text-hp-text">All Amenities (Default)</span>
                                        </div>
                                        <span class="text-xs text-hp-green-mid font-semibold">{{ $allAmenities->count() }} Total Units</span>
                                    </label>

                                    @if(isset($amenityCategories['a_houses']) && $amenityCategories['a_houses']['count'] > 0)
                                    <div class="category-group-block rounded-2xl border border-glass-border bg-glass/60 p-4">
                                        <div class="flex items-center justify-between mb-3 pb-2 border-b border-glass-border/60">
                                            <label class="flex items-center gap-2.5 cursor-pointer">
                                                <input type="checkbox" id="matrixToggleCategoryAHouses" class="matrix-category-cb h-4 w-4 rounded border-gray-300 text-hp-green-mid focus:ring-hp-green-mid cursor-pointer" data-category="a_houses" checked>
                                                <span class="text-xs font-bold uppercase tracking-wider text-hp-text">A-Houses</span>
                                            </label>
                                            <span class="rounded-full bg-hp-green-mid/15 px-2.5 py-0.5 text-[0.65rem] font-bold text-hp-green-mid">
                                                {{ $amenityCategories['a_houses']['count'] }} Units
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                            @foreach($allAmenities as $amenity)
                                                @php
                                                    $catKey = (stripos($amenity->amenities_name, 'A-House') !== false || stripos($amenity->amenities_name, 'A House') !== false) ? 'a_houses' : ((stripos($amenity->amenities_name, 'Cottage') !== false) ? 'cottages' : 'rooms_others');
                                                @endphp
                                                @if($catKey === 'a_houses')
                                                <label class="amenity-item-card flex items-center justify-between p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-[#1a201c] hover:border-hp-green-mid transition-all cursor-pointer shadow-2xs" data-amenity-name="{{ strtolower($amenity->amenities_name) }}">
                                                    <div class="flex items-center gap-2.5">
                                                        <input type="checkbox" 
                                                            class="matrix-amenity-checkbox h-4 w-4 rounded border-gray-300 text-hp-green-mid focus:ring-hp-green-mid cursor-pointer" 
                                                            value="{{ $amenity->id }}" 
                                                            data-id="{{ $amenity->id }}" 
                                                            data-name="{{ $amenity->amenities_name }}" 
                                                            data-category="a_houses"
                                                            checked>
                                                        <span class="text-xs font-semibold text-hp-text">{{ $amenity->amenities_name }}</span>
                                                    </div>
                                                    <span class="text-[0.65rem] font-medium text-hp-text-muted">A-House</span>
                                                </label>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    @if(isset($amenityCategories['cottages']) && $amenityCategories['cottages']['count'] > 0)
                                    <div class="category-group-block rounded-2xl border border-glass-border bg-glass/60 p-4">
                                        <div class="flex items-center justify-between mb-3 pb-2 border-b border-glass-border/60">
                                            <label class="flex items-center gap-2.5 cursor-pointer">
                                                <input type="checkbox" id="matrixToggleCategoryCottages" class="matrix-category-cb h-4 w-4 rounded border-gray-300 text-hp-green-mid focus:ring-hp-green-mid cursor-pointer" data-category="cottages" checked>
                                                <span class="text-xs font-bold uppercase tracking-wider text-hp-text">Cottages</span>
                                            </label>
                                            <span class="rounded-full bg-blue-500/15 px-2.5 py-0.5 text-[0.65rem] font-bold text-blue-700 dark:text-blue-400">
                                                {{ $amenityCategories['cottages']['count'] }} Units
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                            @foreach($allAmenities as $amenity)
                                                @php
                                                    $catKey = (stripos($amenity->amenities_name, 'A-House') !== false || stripos($amenity->amenities_name, 'A House') !== false) ? 'a_houses' : ((stripos($amenity->amenities_name, 'Cottage') !== false) ? 'cottages' : 'rooms_others');
                                                @endphp
                                                @if($catKey === 'cottages')
                                                <label class="amenity-item-card flex items-center justify-between p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-[#1a201c] hover:border-hp-green-mid transition-all cursor-pointer shadow-2xs" data-amenity-name="{{ strtolower($amenity->amenities_name) }}">
                                                    <div class="flex items-center gap-2.5">
                                                        <input type="checkbox" 
                                                            class="matrix-amenity-checkbox h-4 w-4 rounded border-gray-300 text-hp-green-mid focus:ring-hp-green-mid cursor-pointer" 
                                                            value="{{ $amenity->id }}" 
                                                            data-id="{{ $amenity->id }}" 
                                                            data-name="{{ $amenity->amenities_name }}" 
                                                            data-category="cottages"
                                                            checked>
                                                        <span class="text-xs font-semibold text-hp-text">{{ $amenity->amenities_name }}</span>
                                                    </div>
                                                    <span class="text-[0.65rem] font-medium text-hp-text-muted">Cottage</span>
                                                </label>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif

                                    @if(isset($amenityCategories['rooms_others']) && $amenityCategories['rooms_others']['count'] > 0)
                                    <div class="category-group-block rounded-2xl border border-glass-border bg-glass/60 p-4">
                                        <div class="flex items-center justify-between mb-3 pb-2 border-b border-glass-border/60">
                                            <label class="flex items-center gap-2.5 cursor-pointer">
                                                <input type="checkbox" id="matrixToggleCategoryRoomsOthers" class="matrix-category-cb h-4 w-4 rounded border-gray-300 text-hp-green-mid focus:ring-hp-green-mid cursor-pointer" data-category="rooms_others" checked>
                                                <span class="text-xs font-bold uppercase tracking-wider text-hp-text">Rooms & Others</span>
                                            </label>
                                            <span class="rounded-full bg-purple-500/15 px-2.5 py-0.5 text-[0.65rem] font-bold text-purple-700 dark:text-purple-400">
                                                {{ $amenityCategories['rooms_others']['count'] }} Units
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                            @foreach($allAmenities as $amenity)
                                                @php
                                                    $catKey = (stripos($amenity->amenities_name, 'A-House') !== false || stripos($amenity->amenities_name, 'A House') !== false) ? 'a_houses' : ((stripos($amenity->amenities_name, 'Cottage') !== false) ? 'cottages' : 'rooms_others');
                                                @endphp
                                                @if($catKey === 'rooms_others')
                                                <label class="amenity-item-card flex items-center justify-between p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-[#1a201c] hover:border-hp-green-mid transition-all cursor-pointer shadow-2xs" data-amenity-name="{{ strtolower($amenity->amenities_name) }}">
                                                    <div class="flex items-center gap-2.5">
                                                        <input type="checkbox" 
                                                            class="matrix-amenity-checkbox h-4 w-4 rounded border-gray-300 text-hp-green-mid focus:ring-hp-green-mid cursor-pointer" 
                                                            value="{{ $amenity->id }}" 
                                                            data-id="{{ $amenity->id }}" 
                                                            data-name="{{ $amenity->amenities_name }}" 
                                                            data-category="rooms_others"
                                                            checked>
                                                        <span class="text-xs font-semibold text-hp-text">{{ $amenity->amenities_name }}</span>
                                                    </div>
                                                    <span class="text-[0.65rem] font-medium text-hp-text-muted">Room / Other</span>
                                                </label>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                {{-- Modal Footer --}}
                                <div class="border-t border-glass-border px-6 py-4 bg-gray-50/50 dark:bg-white/[0.02] flex items-center justify-between gap-3">
                                    <div class="text-xs text-hp-text font-semibold">
                                        <span id="matrixModalSelectedCount">{{ $allAmenities->count() }}</span> of {{ $allAmenities->count() }} Amenities Selected
                                    </div>
                                    <div class="flex items-center gap-2.5">
                                        <button type="button" id="cancelMatrixColumnsModalBtn" class="px-4 py-2 rounded-xl border border-glass-border bg-white dark:bg-[#1a201c] text-xs font-semibold text-hp-text hover:bg-glass-hover transition-all cursor-pointer">
                                            Cancel
                                        </button>
                                        <button type="button" id="applyMatrixColumnsModalBtn" class="px-5 py-2 rounded-xl bg-hp-green-mid text-xs font-bold text-white shadow-md shadow-hp-green-mid/20 hover:bg-[#15462e] active:scale-95 transition-all cursor-pointer">
                                            Apply Columns
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- VIEW 1: Excel-Style Daily Amenity & Room Occupancy Matrix Grid Table --}}
                        <div id="matrixRoomsContainer" class="block">
                            <div class="relative max-h-[620px] overflow-x-auto overflow-y-auto rounded-2xl border border-glass-border bg-glass-hover/20 shadow-inner">
                                <table class="w-full border-separate border-spacing-0 text-center text-xs" id="amenityDailyMatrixTable">
                                    <thead>
                                        <!-- Super Header Row (Row 1) -->
                                        <tr id="matrixSuperHeaderRow" class="bg-[#1c5c3c] text-white font-bold tracking-wider uppercase text-[0.7rem]">
                                            <th rowspan="2" class="sticky top-0 left-0 z-40 w-[65px] min-w-[65px] max-w-[65px] py-2 px-3 border border-white/20 bg-[#1c5c3c] text-white font-bold tracking-wider uppercase text-[0.7rem] align-middle box-border">DATE</th>
                                            <th rowspan="2" class="sticky top-0 left-[65px] z-40 w-[105px] min-w-[105px] max-w-[105px] py-2 px-3 border border-white/20 bg-[#1c5c3c] text-white font-bold tracking-wider uppercase text-[0.7rem] align-middle shadow-[3px_0_6px_-2px_rgba(0,0,0,0.25)] box-border">DAY</th>
                                            <th id="matrixSuperHeaderAmenity" colspan="{{ $allAmenities->count() }}" class="sticky top-0 z-20 h-8 max-h-8 leading-8 py-0 px-3 border border-white/20 bg-[#15462e] tracking-widest text-[0.75rem] font-bold text-white uppercase align-middle box-border">
                                                ROOM / AMENITY IDENTIFICATION
                                            </th>
                                            <th rowspan="2" class="sticky top-0 z-20 py-2 px-3 border border-white/20 min-w-[100px] bg-[#1e4a33] text-white font-bold tracking-wider uppercase text-[0.7rem] align-middle leading-tight box-border">NUMBER OF GUEST CHECK IN</th>
                                            <th rowspan="2" class="sticky top-0 z-20 py-2 px-3 border border-white/20 min-w-[100px] bg-[#1e4a33] text-white font-bold tracking-wider uppercase text-[0.7rem] align-middle leading-tight box-border">NUMBER OF GUESTS STAYED OVERNIGHT</th>
                                            <th rowspan="2" class="sticky top-0 z-20 py-2 px-3 border border-white/20 min-w-[95px] bg-[#1e4a33] text-white font-bold tracking-wider uppercase text-[0.7rem] align-middle leading-tight box-border">NUMBER OF ROOMS OCCUPIED</th>
                                        </tr>
                                        <!-- Sub Header Row (Row 2: Dynamic Amenity Columns) -->
                                        <tr id="matrixAmenitySubHeaderRow" class="bg-[#246b47] text-white font-semibold text-[0.68rem] tracking-wide">
                                            @foreach($allAmenities as $amenity)
                                                <th class="sticky top-8 z-10 py-1.5 px-2.5 border border-white/20 bg-[#246b47] text-white text-[0.68rem] font-bold tracking-wide align-middle min-w-[85px] box-border" data-col-amenity-id="{{ $amenity->id }}">
                                                    {{ strtoupper($amenity->amenities_name) }}
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody id="matrixTableBody" class="divide-y divide-glass-border bg-glass">
                                        <!-- Populated dynamically by JS -->
                                    </tbody>
                                    <tfoot id="matrixTableFoot" class="font-bold bg-[#eaf3ed] dark:bg-[#1a231d] text-hp-text border-t-2 border-[#1c5c3c]">
                                        <!-- Populated dynamically by JS -->
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        {{-- VIEW 2: Excel-Style Number of Guests Breakdown (Male / Female / Foreigner / Total) Table --}}
                        <div id="matrixGuestsContainer" class="hidden">
                            <div class="relative max-h-[620px] overflow-x-auto overflow-y-auto rounded-2xl border border-glass-border bg-glass-hover/20 shadow-inner">
                                <table class="w-full border-separate border-spacing-0 text-center text-xs" id="amenityGuestsMatrixTable">
                                    <thead>
                                        <tr class="bg-[#1c5c3c] text-white font-bold tracking-wider uppercase text-[0.72rem]">
                                            <th class="sticky top-0 left-0 z-40 w-[80px] min-w-[80px] max-w-[80px] py-2.5 px-3 border border-white/20 bg-[#1c5c3c] text-white font-bold tracking-wider uppercase align-middle box-border">DATE</th>
                                            <th class="sticky top-0 left-[80px] z-40 w-[110px] min-w-[110px] max-w-[110px] py-2.5 px-3 border border-white/20 bg-[#1c5c3c] text-white font-bold tracking-wider uppercase align-middle shadow-[3px_0_6px_-2px_rgba(0,0,0,0.25)] box-border">DAY</th>
                                            <th class="sticky top-0 z-20 py-2.5 px-3 border border-white/20 min-w-[120px] bg-[#1e4a33] text-white font-bold tracking-wider uppercase align-middle box-border">MALE</th>
                                            <th class="sticky top-0 z-20 py-2.5 px-3 border border-white/20 min-w-[120px] bg-[#1e4a33] text-white font-bold tracking-wider uppercase align-middle box-border">FEMALE</th>
                                            <th class="sticky top-0 z-20 py-2.5 px-3 border border-white/20 min-w-[120px] bg-[#1e4a33] text-white font-bold tracking-wider uppercase align-middle box-border">FOREIGNER</th>
                                            <th class="sticky top-0 z-20 py-2.5 px-3 border border-white/20 min-w-[130px] bg-[#15462e] text-white font-bold tracking-wider uppercase align-middle box-border">TOTAL</th>
                                        </tr>
                                    </thead>
                                    <tbody id="matrixGuestsTableBody" class="divide-y divide-glass-border bg-glass">
                                        <!-- Populated dynamically by JS -->
                                    </tbody>
                                    <tfoot id="matrixGuestsTableFoot" class="font-bold bg-[#eaf3ed] dark:bg-[#1a231d] text-hp-text border-t-2 border-[#1c5c3c]">
                                        <!-- Populated dynamically by JS -->
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        {{-- Dynamic Month Quick Tabs at bottom (like Excel Tabs in user photo) --}}
                        <div class="mt-4 flex flex-wrap items-center gap-2 pt-2 border-t border-glass-border text-xs text-hp-text-muted">
                            <span class="font-bold uppercase tracking-wider text-[0.7rem] mr-1">Sheet Quick Tabs:</span>
                            <div id="matrixSheetMonthTabs" class="flex flex-wrap items-center gap-2">
                                <!-- Populated dynamically by JS -->
                            </div>
                        </div>
                    </section>
                </div>

                <!-- ========================================== -->
                <!-- SECTION 2: STANDARD OPERATIONAL REPORTS   -->
                <!-- ========================================== -->
                <div id="standardReportsSection" class="hidden transition-opacity duration-300">
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
                                <div class="flex h-11 w-11 items-center justify-center rounded-[10px] bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1e2220] dark:text-[#6ab88c]">
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
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1e2220] dark:text-[#6ab88c]">
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
                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1e2220] dark:text-[#6ab88c]">
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
                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1e2220] dark:text-[#6ab88c]">
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
                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1e2220] dark:text-[#6ab88c]">
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
                                            $effectiveCheckIn = $r->check_in ?? $r->reservation_date;
                                            $checkInStr = $effectiveCheckIn ? \Illuminate\Support\Carbon::parse($effectiveCheckIn)->timezone(config('app.timezone', 'Asia/Manila'))->format('Y-m-d') : '';
                                        @endphp
                                        <tr class="border-b border-glass-border/50 hover:bg-glass-hover"
                                            data-amenity="{{ strtolower($amenitiesStr) }}"
                                            data-status="{{ strtolower($r->status) }}"
                                            data-checkin="{{ $checkInStr }}">
                                            <td class="py-3 px-3 font-medium text-hp-text">{{ $r->booker_name }}</td>
                                            <td class="py-3 px-3 text-xs text-hp-text-muted">{{ $amenitiesStr }}</td>
                                            <td class="mono-cell py-3 px-3 text-xs text-hp-text-muted">{{ $effectiveCheckIn ? \Illuminate\Support\Carbon::parse($effectiveCheckIn)->timezone(config('app.timezone', 'Asia/Manila'))->format('M d, Y') : 'N/A' }}</td>
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
                </div>

                <!-- ========================================== -->
                <!-- SECTION 2: AI REPORT STUDIO & ANALYST     -->
                <!-- ========================================== -->
                <div id="aiReportsSection" class="hidden transition-opacity duration-300">
                    {{-- Hero Studio Banner --}}
                    <section class="ai-glass-hero relative mb-6 overflow-hidden rounded-3xl border border-emerald-500/20 p-6 md:p-8 shadow-glass">
                        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                            <div class="max-w-2xl">
                                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span>AI Executive Report Studio</span>
                                </div>
                                <h2 class="m-0 mb-2 font-display text-2xl md:text-3xl font-bold text-hp-text">
                                    Ask What Data You Need, AI Delivers
                                </h2>
                                <p class="m-0 text-sm leading-relaxed text-hp-text-muted">
                                    Query park operations, revenue, visitor trends, and amenity performance in plain English. Get instant calculated metrics, structured breakdown tables, and strategic recommendations tailored for executive decision-making.
                                </p>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <div class="rounded-2xl border border-glass-border bg-glass p-4 text-center shadow-sm backdrop-blur-md">
                                    <span class="block text-2xl font-bold text-emerald-600 dark:text-emerald-400">Live</span>
                                    <span class="text-[0.7rem] uppercase tracking-wider text-hp-text-muted">Data Connected</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- AI Quick Analytical Presets --}}
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
                                <h4 class="m-0 mb-1 text-sm font-bold text-hp-text">Revenue & Financials</h4>
                                <p class="m-0 text-xs text-hp-text-muted leading-tight">Sales, unpaid dues & spending patterns</p>
                            </button>

                            <button type="button" class="ai-preset-card group flex flex-col items-start rounded-2xl border border-glass-border bg-glass p-4 text-left shadow-sm cursor-pointer transition-all hover:border-emerald-500/40 hover:bg-glass-hover" data-ai-prompt="What are our peak booking days, highest traffic months, and weekly visitor distribution trends?">
                                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                </div>
                                <h4 class="m-0 mb-1 text-sm font-bold text-hp-text">Peak Days & Forecast</h4>
                                <p class="m-0 text-xs text-hp-text-muted leading-tight">Weekend surges & seasonal demand</p>
                            </button>

                            <button type="button" class="ai-preset-card group flex flex-col items-start rounded-2xl border border-glass-border bg-glass p-4 text-left shadow-sm cursor-pointer transition-all hover:border-emerald-500/40 hover:bg-glass-hover" data-ai-prompt="Which amenities generate the most revenue vs the most bookings, and which are underutilized?">
                                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                </div>
                                <h4 class="m-0 mb-1 text-sm font-bold text-hp-text">Amenity Utilization</h4>
                                <p class="m-0 text-xs text-hp-text-muted leading-tight">Profitable vs underused attractions</p>
                            </button>

                            <button type="button" class="ai-preset-card group flex flex-col items-start rounded-2xl border border-glass-border bg-glass p-4 text-left shadow-sm cursor-pointer transition-all hover:border-emerald-500/40 hover:bg-glass-hover" data-ai-prompt="Break down guest volumes, average party sizes, and online booking vs on-site walk-in distribution.">
                                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </div>
                                <h4 class="m-0 mb-1 text-sm font-bold text-hp-text">Guests & Channels</h4>
                                <p class="m-0 text-xs text-hp-text-muted leading-tight">Party sizes, online vs walk-in splits</p>
                            </button>

                            <button type="button" class="ai-preset-card group flex flex-col items-start rounded-2xl border border-glass-border bg-glass p-4 text-left shadow-sm cursor-pointer transition-all hover:border-emerald-500/40 hover:bg-glass-hover" data-ai-prompt="Provide strategic business recommendations to maximize park revenue and boost weekday booking rates.">
                                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400 group-hover:scale-110 transition-transform">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                </div>
                                <h4 class="m-0 mb-1 text-sm font-bold text-hp-text">Growth Strategies</h4>
                                <p class="m-0 text-xs text-hp-text-muted leading-tight">Actionable ideas to boost revenue</p>
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
                                <textarea id="aiQueryInput" rows="3" class="w-full rounded-2xl border border-glass-border bg-glass-hover/60 p-4 text-sm text-hp-text placeholder-hp-text-muted/60 outline-none transition-all focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 resize-none" placeholder="e.g. 'Compare revenue between online and walk-in bookings for the last 30 days', 'What is our most profitable amenity and what should we price it at?', 'Summarize our cancellations and suggest ways to reduce them'"></textarea>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex flex-wrap items-center gap-2 text-xs text-hp-text-muted">
                                    <span class="font-semibold">Quick Suggestions:</span>
                                    <button type="button" class="ai-suggest-btn rounded-full border border-glass-border bg-glass px-2.5 py-1 transition-all hover:bg-emerald-500/10 hover:text-emerald-600 hover:border-emerald-500/30 cursor-pointer" data-fill="Compare online booking revenue vs on-site walk-in revenue and show key metrics.">Online vs Walk-in</button>
                                    <button type="button" class="ai-suggest-btn rounded-full border border-glass-border bg-glass px-2.5 py-1 transition-all hover:bg-emerald-500/10 hover:text-emerald-600 hover:border-emerald-500/30 cursor-pointer" data-fill="Analyze our cancellation rates, pending bookings, and potential revenue loss.">Cancellation Audit</button>
                                    <button type="button" class="ai-suggest-btn rounded-full border border-glass-border bg-glass px-2.5 py-1 transition-all hover:bg-emerald-500/10 hover:text-emerald-600 hover:border-emerald-500/30 cursor-pointer" data-fill="Give me a comprehensive executive summary of this month's park performance and visitor trends.">Monthly Executive Summary</button>
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
                                Select one of the quick analytical audit presets above or type a specific question about revenue, amenities, or guest bookings.
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
                            <p class="m-0 text-xs text-hp-text-muted">Mining reservations, computing financial KPIs, and structuring findings</p>
                        </div>

                        {{-- Generated Report Container (Populated dynamically via JS) --}}
                        <div id="aiReportResults" class="hidden flex flex-col gap-6"></div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        window.reportData = {!! json_encode($reportData ?? []) !!};
    </script>
    {{-- Admin AI Intelligence Chatbot --}}
    <x-admin_chatbot />
</body>
</html>
