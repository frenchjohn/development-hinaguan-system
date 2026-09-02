<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Records & Archives — Hinaguan Nature Park</title>
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
        'resources/js/staff_js/staff_records.js',
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
    </style>
</head>
<body class="antialiased staff-portal">
    <div class="dash-layout">
        <x-staff_sidemenu active="records" userName="{{ session('auth_user.name') ?? 'Staff User' }}" userRole="Staff" />

        <div class="dash-main">
            <x-header
                title="Records & Archives"
                subtitle="Historical records of checked-out guests, completed stays, no shows, and cancelled reservations"
            />

            <main class="dash-content p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">

                <!-- SUMMARY STRIP METRIC CARDS -->
                <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <!-- Card 1: Guest Records -->
                    <article class="flex items-center gap-3.5 p-4 rounded-2xl bg-white/95 dark:bg-[#181b19]/95 border border-[#dbe3de] dark:border-[#282c29] border-l-[4px] border-l-emerald-600 dark:border-l-emerald-500 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="m-0 text-xl font-extrabold leading-tight text-[#0d2c1d] dark:text-[#f5f5f0]" id="counterGuestRecords">{{ $guestRecordsCount }}</p>
                            <p class="m-0 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">Guest Records</p>
                            <p class="m-0 text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8] truncate">Checked-out visitors</p>
                        </div>
                    </article>

                    <!-- Card 2: Checked Out Stays -->
                    <article class="flex items-center gap-3.5 p-4 rounded-2xl bg-white/95 dark:bg-[#181b19]/95 border border-[#dbe3de] dark:border-[#282c29] border-l-[4px] border-l-blue-600 dark:border-l-blue-400 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="m-0 text-xl font-extrabold leading-tight text-[#0d2c1d] dark:text-[#f5f5f0]" id="counterCheckedOut">{{ $checkedOutCount ?? $completedReservationsCount }}</p>
                            <p class="m-0 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">Checked Out</p>
                            <p class="m-0 text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8] truncate">Completed visits</p>
                        </div>
                    </article>

                    <!-- Card 3: No Show Records -->
                    <article class="flex items-center gap-3.5 p-4 rounded-2xl bg-white/95 dark:bg-[#181b19]/95 border border-[#dbe3de] dark:border-[#282c29] border-l-[4px] border-l-slate-500 dark:border-l-slate-400 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="m-0 text-xl font-extrabold leading-tight text-[#0d2c1d] dark:text-[#f5f5f0]" id="counterNoShow">{{ $noShowCount ?? 0 }}</p>
                            <p class="m-0 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">No Show</p>
                            <p class="m-0 text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8] truncate">Unattended bookings</p>
                        </div>
                    </article>

                    <!-- Card 4: Cancelled Records -->
                    <article class="flex items-center gap-3.5 p-4 rounded-2xl bg-white/95 dark:bg-[#181b19]/95 border border-[#dbe3de] dark:border-[#282c29] border-l-[4px] border-l-rose-500 dark:border-l-rose-400 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="m-0 text-xl font-extrabold leading-tight text-[#0d2c1d] dark:text-[#f5f5f0]" id="counterCancelled">{{ $cancelledCount ?? 0 }}</p>
                            <p class="m-0 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">Cancelled</p>
                            <p class="m-0 text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8] truncate">Voided bookings</p>
                        </div>
                    </article>

                    <!-- Card 5: Total Revenue Logged -->
                    <article class="flex items-center gap-3.5 p-4 rounded-2xl bg-white/95 dark:bg-[#181b19]/95 border border-[#dbe3de] dark:border-[#282c29] border-l-[4px] border-l-amber-500 dark:border-l-amber-400 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md sm:col-span-2 lg:col-span-1">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="m-0 text-xl font-extrabold leading-tight text-[#0d2c1d] dark:text-[#f5f5f0]" id="counterRevenueCollected">₱{{ number_format($completedRevenue, 0) }}</p>
                            <p class="m-0 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">Total Revenue</p>
                            <p class="m-0 text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8] truncate">Ingested funds</p>
                        </div>
                    </article>
                </div>

                <!-- TAB BUTTONS -->
                <div class="mb-5 flex flex-wrap items-center gap-3">
                    <button type="button" class="records-tab-btn records-tab-btn--active inline-flex items-center gap-2 cursor-pointer rounded-xl border border-transparent bg-[#178a52] px-5 py-2.5 text-xs font-bold text-white shadow-sm transition-all hover:bg-[#126e41] focus:outline-none" data-tab="guests" id="guestsTabBtn">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                        <span>Guest Records</span>
                        <span class="rounded-full bg-white/20 px-2 py-0.5 text-[0.68rem] font-bold text-white">{{ $guestRecordsCount }}</span>
                    </button>
                    <button type="button" class="records-tab-btn inline-flex items-center gap-2 cursor-pointer rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-5 py-2.5 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] shadow-sm transition-all hover:bg-[#f4f7f5] dark:hover:bg-[#141715] focus:outline-none" data-tab="reservations" id="reservationsTabBtn">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <span>Reservation Records</span>
                        <span class="rounded-full bg-gray-100 dark:bg-white/10 px-2 py-0.5 text-[0.68rem] font-bold text-[#5a6b5c] dark:text-[#a8b8a8]">{{ $completedReservationsCount }}</span>
                    </button>
                </div>

                {{-- CHECKED-OUT GUEST RECORDS TABLE --}}
                <div class="min-w-0">
                    <section data-tab-content="guests">
                        <div class="guest-panel my-4 rounded-2xl border border-[#dbe3de] dark:border-[#282c29] bg-white/95 dark:bg-[#181b19]/95 shadow-sm overflow-hidden transition-all">
                            {{-- Panel Header & Toolbar --}}
                            <div class="p-5 sm:p-6 border-b border-[#e8eee9] dark:border-[#282c29]">
                                <div class="flex flex-wrap items-center justify-between gap-4">
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-600 to-emerald-800 text-white shadow-sm ring-4 ring-emerald-500/10">
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <h3 class="m-0 text-base font-bold text-[#0d2c1d] dark:text-[#f5f5f0] leading-tight">Checked-Out Guest Records</h3>
                                            <p class="m-0 text-xs text-[#5a6b5c] dark:text-[#a8b8a8]">Visitors who have successfully checked out of the park</p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-3">
                                        <div class="relative">
                                            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#889b8a]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                                            <input type="search" id="guestSearchInput" placeholder="Search guest name, ID, or res..." class="w-60 sm:w-68 rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-[#f8faf9] dark:bg-[#141715] py-2.5 pl-9 pr-3 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] placeholder:text-[#889b8a] focus:bg-white dark:focus:bg-[#181b19] focus:border-[#178a52] focus:ring-2 focus:ring-[#178a52]/20 focus:outline-none transition-all shadow-xs">
                                        </div>

                                        <!-- Show Companions Checkbox -->
                                        <label class="inline-flex items-center gap-2 cursor-pointer select-none px-3.5 py-2.5 rounded-xl bg-[#f4f7f5] dark:bg-[#141715] border border-[#dbe3de] dark:border-[#282c29] text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0] shadow-xs hover:bg-[#e8efe9] dark:hover:bg-[#242a26] transition-colors">
                                            <input type="checkbox" id="showCompanionsCheckbox" class="w-4 h-4 text-[#178a52] rounded border-[#dbe3de] dark:border-[#282c29] focus:ring-[#178a52] cursor-pointer">
                                            <span>Show Companions</span>
                                        </label>

                                        <!-- Filter button -->
                                        <button type="button" class="guest-filter-toggle inline-flex items-center justify-center gap-1.5 rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-4 py-2.5 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] transition-all hover:bg-[#f4f7f5] dark:hover:bg-[#141715] shadow-xs cursor-pointer" id="guestFilterToggle" aria-expanded="false" aria-controls="guestFilterPanel">
                                            <svg class="h-3.5 w-3.5 text-[#5a6b5c] dark:text-[#a8b8a8]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/></svg>
                                            <span>Filters</span>
                                            <span class="guest-filter-toggle__icon text-[0.7rem] text-[#889b8a]">▾</span>
                                        </button>
                                    </div>
                                </div>

                                @if (session('success'))
                                    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-950/40 dark:border-emerald-900 px-4 py-3 text-xs font-semibold text-emerald-800 dark:text-emerald-300">{{ session('success') }}</div>
                                @endif

                                <div class="guest-filter-shell mt-4 grid gap-3" id="guestFilterPanel" hidden>
                                    <div class="guest-toolbar grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 items-end gap-3 rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-[#f8faf9] dark:bg-[#141715] p-4 transition-all shadow-inner">
                                        <label class="grid gap-1.5 text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">
                                            <span>Status</span>
                                            <select id="guestStatusFilter" class="w-full rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-2 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-xs cursor-pointer">
                                                <option value="all">All Statuses</option>
                                                <option value="checked out">Checked Out</option>
                                                <option value="no show">No Show</option>
                                                <option value="cancelled">Cancelled</option>
                                            </select>
                                        </label>
                                        <label class="grid gap-1.5 text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">
                                            <span>Sort by</span>
                                            <select id="guestSortSelect" class="w-full rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-2 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-xs cursor-pointer">
                                                <option value="checkout-desc">Date (Newest)</option>
                                                <option value="checkout-asc">Date (Oldest)</option>
                                                <option value="name-asc">Guest Name (A-Z)</option>
                                                <option value="name-desc">Guest Name (Z-A)</option>
                                                <option value="customer-id-asc">Customer ID (Low-High)</option>
                                                <option value="customer-id-desc">Customer ID (High-Low)</option>
                                                <option value="reservation-asc">Reservation (Low-High)</option>
                                                <option value="reservation-desc">Reservation (High-Low)</option>
                                                <option value="age-asc">Age (Low-High)</option>
                                                <option value="age-desc">Age (High-Low)</option>
                                            </select>
                                        </label>
                                        <label class="grid gap-1.5 text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">
                                            <span>Date from</span>
                                            <input type="date" id="guestCheckOutFrom" class="w-full rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-2 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-xs">
                                        </label>
                                        <label class="grid gap-1.5 text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">
                                            <span>Date to</span>
                                            <input type="date" id="guestCheckOutTo" class="w-full rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-2 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-xs">
                                        </label>
                                        <button type="button" class="cursor-pointer rounded-xl bg-[#e8efe9] dark:bg-[#1e2220] px-4 py-2.5 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] transition-colors hover:bg-[#178a52] hover:text-white border-0 shadow-xs" id="guestFiltersClear">Clear</button>
                                    </div>
                                </div>
                            </div>

                            {{-- Table Wrap --}}
                            <div class="overflow-x-auto w-full" id="guestTableWrap">
                                <table class="guest-table w-full min-w-[960px] text-left border-collapse">
                                    <thead class="bg-[#f4f8f5] dark:bg-[#151c17] border-b border-[#e2e8e4] dark:border-[#282c29]">
                                        <tr>
                                            <th class="py-3.5 px-5 text-[0.7rem] font-bold text-[#1f5c38] dark:text-[#8fd0ab] uppercase tracking-wider sortable cursor-pointer select-none" data-sort="name">GUEST / GROUP</th>
                                            <th class="py-3.5 px-4 text-[0.7rem] font-bold text-[#1f5c38] dark:text-[#8fd0ab] uppercase tracking-wider sortable cursor-pointer select-none" data-sort="customer-id">CUSTOMER ID</th>
                                            <th class="py-3.5 px-4 text-[0.7rem] font-bold text-[#1f5c38] dark:text-[#8fd0ab] uppercase tracking-wider sortable cursor-pointer select-none" data-sort="reservation">RESERVATION</th>
                                            <th class="py-3.5 px-4 text-[0.7rem] font-bold text-[#1f5c38] dark:text-[#8fd0ab] uppercase tracking-wider sortable cursor-pointer select-none" data-sort="age">AGE</th>
                                            <th class="py-3.5 px-4 text-[0.7rem] font-bold text-[#1f5c38] dark:text-[#8fd0ab] uppercase tracking-wider sortable cursor-pointer select-none" data-sort="gender">GENDER</th>
                                            <th class="py-3.5 px-4 text-[0.7rem] font-bold text-[#1f5c38] dark:text-[#8fd0ab] uppercase tracking-wider sortable cursor-pointer select-none" data-sort="nationality">NATIONALITY</th>
                                            <th class="py-3.5 px-4 text-[0.7rem] font-bold text-[#1f5c38] dark:text-[#8fd0ab] uppercase tracking-wider sortable cursor-pointer select-none" data-sort="status">STATUS</th>
                                            <th class="py-3.5 px-5 text-[0.7rem] font-bold text-[#1f5c38] dark:text-[#8fd0ab] uppercase tracking-wider sortable cursor-pointer select-none" data-sort="checked-out">DATE / CHECKOUT</th>
                                            <th class="py-3.5 px-4 text-right w-12"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="guestTableBody" class="divide-y divide-[#edf2ee] dark:divide-[#242a26] bg-white dark:bg-[#181b19]">
                                        @forelse ($guestRows as $row)
                                            @if (($row['type'] ?? '') === 'bulk')
                                                @php
                                                    $group = $row['group'];
                                                    $bulkStatus = $group['status'] ?? 'Checked Out';
                                                    $bulkStatusTheme = ($bulkStatus === 'Cancelled')
                                                        ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/40'
                                                        : (($bulkStatus === 'No Show')
                                                            ? 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800/50 dark:text-slate-300 dark:border-slate-700'
                                                            : 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40');
                                                    $bulkResAmount = (float) ($reservationAmounts[$group['reservation_id']] ?? 0);
                                                    $bulkHasPool = (bool) ($group['has_pool_access'] ?? false);
                                                @endphp
                                                <tr
                                                    class="guest-row guest-row--bulk-group group cursor-pointer select-none transition-all hover:bg-[#f2f7f4] dark:hover:bg-[#1e2621]"
                                                    data-bulk-group="true"
                                                    data-is-companion="true"
                                                    data-is-primary="false"
                                                    data-guest-count="{{ $group['count'] }}"
                                                    data-bulk-key="{{ $group['key'] }}"
                                                    data-gender="{{ $group['gender'] }}"
                                                    data-nationality="{{ $group['nationality'] }}"
                                                    data-status="{{ strtolower($bulkStatus) }}"
                                                    data-has-pool="{{ $bulkHasPool ? 'true' : 'false' }}"
                                                    data-reservation-id="{{ $group['reservation_id'] }}"
                                                    data-reservation-amount="{{ $bulkResAmount }}"
                                                    data-age-value="999999"
                                                    data-checked-out="{{ $group['checked_out_at'] ?? '' }}"
                                                    data-search="{{ strtolower(trim(($group['name'] ?? '') . ' ' . $group['reservation_id'] . ' ' . $group['gender'] . ' ' . $group['age_group'] . ' ' . $group['nationality'] . ' ' . $bulkStatus . ' ' . $group['count'] . ' bulk companion group #' . $group['reservation_id'] . ($bulkHasPool ? ' pool swimming swimmer' : ''))) }}"
                                                    tabindex="0"
                                                    role="button"
                                                >
                                                    <td class="py-3.5 px-5">
                                                        <div class="flex items-center gap-3.5 min-w-0">
                                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-teal-600 to-emerald-700 text-white shadow-xs" title="Bulk Companion Group">
                                                                <svg class="h-4.5 w-4.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" /><path d="M5.082 14.254a8.287 8.287 0 00-1.308 5.135 9.687 9.687 0 01-1.764-.44l-.115-.04a.563.563 0 01-.373-.487l-.01-.121a3.75 3.75 0 016.576-3.036 7.525 7.525 0 00-3.006-1.011zM18.918 14.254a8.287 8.287 0 011.308 5.135 9.687 9.687 0 001.764-.44l.115-.04a.563.563 0 00.373-.487l.01-.121a3.75 3.75 0 00-6.576-3.036 7.525 7.525 0 013.006-1.011z" /></svg>
                                                            </span>
                                                            <div class="min-w-0">
                                                                <div class="text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] flex items-center gap-1.5 flex-wrap">
                                                                    <span>{{ $group['name'] }}</span>
                                                                    <span class="px-2 py-0.5 text-[0.68rem] font-bold rounded-full bg-[#e8eee9] text-[#2c5f3e] dark:bg-[#282c29] dark:text-[#a8b8a8] border border-[#dbe3de] dark:border-transparent">{{ $group['count'] }}x</span>
                                                                    @if ($bulkHasPool)
                                                                        <span class="inline-flex items-center gap-1 rounded-md bg-[#e0f2fe] dark:bg-[#082f49] px-2 py-0.5 text-[0.65rem] font-bold text-[#0284c7] dark:text-[#38bdf8] border border-[#bae6fd] dark:border-[#0369a1]/40 shadow-2xs" title="{{ $group['pool_access_count'] ?? $group['count'] }} Guests Availing Pool">
                                                                            <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 16.5c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 20.25c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0" /></svg>
                                                                            Pool Access ({{ $group['pool_access_count'] ?? $group['count'] }})
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                                <div class="text-[0.72rem] text-[#718774] dark:text-[#889b8a] mt-0.5">Bulk companion group</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="py-3.5 px-4 text-xs text-[#889b8a] font-mono">—</td>
                                                    <td class="py-3.5 px-4">
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold tracking-wide bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50 font-mono shadow-2xs">#{{ $group['reservation_id'] }}</span>
                                                    </td>
                                                    <td class="py-3.5 px-4 text-xs font-medium text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $group['age_group'] }}</td>
                                                    <td class="py-3.5 px-4 text-xs font-medium text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $group['gender'] }}</td>
                                                    <td class="py-3.5 px-4 text-xs">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[0.72rem] font-medium bg-[#f0f4f1] dark:bg-[#202722] text-[#0d2c1d] dark:text-[#f5f5f0] border border-[#dbe3de] dark:border-[#282c29]">{{ $group['nationality'] }}</span>
                                                    </td>
                                                    <td class="py-3.5 px-4">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $bulkStatusTheme }}">{{ $bulkStatus }}</span>
                                                    </td>
                                                    <td class="py-3.5 px-5">
                                                        @if ($group['checked_out_at'])
                                                            <div class="text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">{{ \Carbon\Carbon::parse($group['checked_out_at'])->format('M d, Y') }}</div>
                                                            <div class="text-[0.7rem] text-[#718774] dark:text-[#889b8a]">{{ \Carbon\Carbon::parse($group['checked_out_at'])->format('h:i A') }}</div>
                                                        @else
                                                            <span class="text-xs text-[#889b8a]">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-3.5 px-4 text-right">
                                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg text-[#889b8a] transition-all group-hover:bg-[#178a52]/10 group-hover:text-[#178a52] dark:group-hover:text-[#8fd0ab] ml-auto" title="View group details">
                                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @else
                                                @php
                                                    $guestEntry = $row['entry'];
                                                    $customer = $guestEntry->customer;
                                                    $isPrimaryGuest = (bool) ($guestEntry->is_primary_guest ?? false);
                                                    $resStatus = trim((string) ($guestEntry->reservation?->status ?? 'Checked Out'));
                                                    
                                                    if (in_array($resStatus, ['No Show', 'no show', 'No show', 'no_show'])) {
                                                        $guestStatus = 'No Show';
                                                        $guestStatusTheme = 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800/50 dark:text-slate-300 dark:border-slate-700';
                                                    } elseif (in_array($resStatus, ['Cancelled', 'cancelled', 'Cancel'])) {
                                                        $guestStatus = 'Cancelled';
                                                        $guestStatusTheme = 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/40';
                                                    } else {
                                                        $guestStatus = 'Checked Out';
                                                        $guestStatusTheme = 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40';
                                                    }

                                                    $guestTypeLabel = ($guestEntry->reservation?->reservation_type ?? '') === 'walk_in' ? 'Walk-in Guest' : ($isPrimaryGuest ? 'Online Primary' : 'Companion');
                                                    $guestResAmount = (float) ($reservationAmounts[$guestEntry->reservation_id ?? 0] ?? 0);
                                                    $effectiveGuestDate = $guestEntry->checked_out_at ?: ($guestEntry->reservation?->check_out ?: ($guestEntry->reservation?->reservation_date ?: $guestEntry->created_at));
                                                    $hasPoolAccess = (bool) ($guestEntry->has_pool_access ?? false);
                                                @endphp
                                                <tr
                                                    class="guest-row group cursor-pointer select-none transition-all hover:bg-[#f2f7f4] dark:hover:bg-[#1e2621]"
                                                    data-customer-id="{{ $customer->id }}"
                                                    data-is-companion="{{ $isPrimaryGuest ? 'false' : 'true' }}"
                                                    data-is-primary="{{ $isPrimaryGuest ? 'true' : 'false' }}"
                                                    data-guest-count="1"
                                                    data-age="{{ $customer->age ?? 'N/A' }}"
                                                    data-gender="{{ $customer->gender ?? 'N/A' }}"
                                                    data-nationality="{{ $customer->is_foreigner ? 'Foreigner' : 'Filipino' }}"
                                                    data-status="{{ strtolower($guestStatus) }}"
                                                    data-has-pool="{{ $hasPoolAccess ? 'true' : 'false' }}"
                                                    data-reservation-id="{{ $guestEntry->reservation_id ?? '' }}"
                                                    data-reservation-amount="{{ $guestResAmount }}"
                                                    data-is-foreigner="{{ $customer->is_foreigner ? 'Foreigner' : 'Filipino' }}"
                                                    data-checked-out="{{ $effectiveGuestDate ?? '' }}"
                                                    data-age-value="{{ is_numeric($customer->age) ? (int) $customer->age : 999999 }}"
                                                    data-search="{{ strtolower(trim(($customer->first_name ?? '') . ' ' . ($customer->middle_name ?? '') . ' ' . ($customer->last_name ?? '') . ' ' . $customer->id . ' ' . ($customer->gender ?? '') . ' ' . ($customer->is_foreigner ? 'Foreigner' : 'Filipino') . ' ' . $guestStatus . ' ' . $guestTypeLabel . ' #' . ($guestEntry->reservation_id ?? '') . ($hasPoolAccess ? ' pool swimming swimmer' : ''))) }}"
                                                    tabindex="0"
                                                    role="button"
                                                >
                                                    <td class="py-3.5 px-5">
                                                        <div class="flex items-center gap-3.5 min-w-0">
                                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $isPrimaryGuest ? 'bg-gradient-to-br from-amber-500 to-amber-700 text-white ring-2 ring-amber-400/20' : 'bg-gradient-to-br from-emerald-600 to-emerald-800 text-white ring-2 ring-emerald-500/20' }} shadow-xs" title="{{ $isPrimaryGuest ? 'Main Guest' : 'Companion' }}">
                                                                @if ($isPrimaryGuest)
                                                                    <svg class="h-4.5 w-4.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" /></svg>
                                                                @else
                                                                    <svg class="h-4.5 w-4.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" /></svg>
                                                                @endif
                                                            </span>
                                                            <div class="min-w-0">
                                                                <div class="text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] flex items-center gap-1.5 flex-wrap">
                                                                    <span>{{ collect([$customer->first_name, $customer->middle_name, $customer->last_name])->filter()->join(' ') }}</span>
                                                                    @if ($hasPoolAccess)
                                                                        <span class="inline-flex items-center gap-1 rounded-md bg-[#e0f2fe] dark:bg-[#082f49] px-2 py-0.5 text-[0.65rem] font-bold text-[#0284c7] dark:text-[#38bdf8] border border-[#bae6fd] dark:border-[#0369a1]/40 shadow-2xs" title="Availing Pool Access">
                                                                            <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 16.5c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 20.25c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0" /></svg>
                                                                            Pool Access
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                                <div class="text-[0.72rem] text-[#718774] dark:text-[#889b8a] mt-0.5 flex items-center gap-1">
                                                                    <span class="inline-block w-1.5 h-1.5 rounded-full {{ $isPrimaryGuest ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                                                                    <span>{{ $guestTypeLabel }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="py-3.5 px-4">
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold text-[#2a4533] dark:text-[#d0e0d5] bg-[#f0f4f1] dark:bg-[#202722] border border-[#dbe3de] dark:border-[#282c29] font-mono shadow-2xs">#{{ $customer->id }}</span>
                                                    </td>
                                                    <td class="py-3.5 px-4">
                                                        @if ($guestEntry->reservation_id)
                                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold tracking-wide bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50 font-mono shadow-2xs">#{{ $guestEntry->reservation_id }}</span>
                                                        @else
                                                            <span class="text-xs text-[#889b8a] font-mono">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-3.5 px-4 text-xs font-medium text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $customer->age ?? 'N/A' }}</td>
                                                    <td class="py-3.5 px-4 text-xs font-medium text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $customer->gender ?? 'N/A' }}</td>
                                                    <td class="py-3.5 px-4 text-xs">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[0.72rem] font-medium {{ $customer->is_foreigner ? 'bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800/40' : 'bg-[#f0f4f1] text-[#0d2c1d] border border-[#dbe3de] dark:bg-[#202722] dark:text-[#f5f5f0] dark:border-[#282c29]' }}">{{ $customer->is_foreigner ? 'Foreigner' : 'Filipino' }}</span>
                                                    </td>
                                                    <td class="py-3.5 px-4">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $guestStatusTheme }}">{{ $guestStatus }}</span>
                                                    </td>
                                                    <td class="py-3.5 px-5">
                                                        @if ($effectiveGuestDate)
                                                            <div class="text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">{{ \Carbon\Carbon::parse($effectiveGuestDate)->format('M d, Y') }}</div>
                                                            <div class="text-[0.7rem] text-[#718774] dark:text-[#889b8a]">{{ \Carbon\Carbon::parse($effectiveGuestDate)->format('h:i A') }}</div>
                                                        @else
                                                            <span class="text-xs text-[#889b8a]">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-3.5 px-4 text-right">
                                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg text-[#889b8a] transition-all group-hover:bg-[#178a52]/10 group-hover:text-[#178a52] dark:group-hover:text-[#8fd0ab] ml-auto" title="View details">
                                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                        @empty
                                            <tr>
                                                <td colspan="9" class="px-5 py-12 text-center text-xs text-[#889b8a]">
                                                    <div class="flex flex-col items-center justify-center gap-2">
                                                        <svg class="h-8 w-8 text-[#889b8a]/60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                                        <span class="font-medium">No guest archive records found.</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- GUEST TABLE PAGINATION (Embedded in Card Footer) --}}
                            <div class="records-pagination bg-[#f8faf9] dark:bg-[#141715] border-t border-[#e2e8e4] dark:border-[#282c29] px-5 py-4">
                                <div class="flex flex-wrap items-center justify-between gap-x-5 gap-y-3 text-xs">
                                    <div class="flex items-center gap-2 text-[#4a5c4d] dark:text-[#a8b8a8]">
                                        <span class="font-medium">Showing</span>
                                        <select id="guestPerPage" class="cursor-pointer rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-1.5 text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-xs">
                                            <option value="5">5</option>
                                            <option value="10" selected>10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                        </select>
                                        <span class="font-medium">per page</span>
                                    </div>
                                    <span id="guestResultsCount" class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">Showing 0 of 0 records</span>
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center gap-1.5">
                                            <button type="button" id="guestPrevPage" class="cursor-pointer rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3.5 py-1.5 font-bold text-[#0d2c1d] dark:text-[#f5f5f0] transition-all hover:bg-[#f4f7f5] dark:hover:bg-[#202722] disabled:cursor-not-allowed disabled:opacity-40 shadow-xs">‹ Prev</button>
                                            <div id="guestPageNumbers" class="flex items-center gap-1"></div>
                                            <button type="button" id="guestNextPage" class="cursor-pointer rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3.5 py-1.5 font-bold text-[#0d2c1d] dark:text-[#f5f5f0] transition-all hover:bg-[#f4f7f5] dark:hover:bg-[#202722] disabled:cursor-not-allowed disabled:opacity-40 shadow-xs">Next ›</button>
                                        </div>
                                        <div class="hidden sm:flex items-center gap-1.5 pl-3 border-l border-[#dbe3de] dark:border-[#282c29] text-xs text-[#5a6b5c] dark:text-[#a8b8a8]">
                                            <span>Go to</span>
                                            <input type="number" id="guestPageInput" min="1" value="1" class="w-14 rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-2 py-1 text-center text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-xs">
                                            <button type="button" id="guestGoPage" class="cursor-pointer rounded-xl bg-[#178a52] px-3 py-1 font-bold text-white transition-colors hover:bg-[#126e41] border-0 shadow-xs">Go</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- RESERVATIONS TAB SECTION -->
                    <section data-tab-content="reservations" hidden>
                        <div class="guest-panel my-4 rounded-2xl border border-[#dbe3de] dark:border-[#282c29] bg-white/95 dark:bg-[#181b19]/95 shadow-sm overflow-hidden transition-all">
                            <div class="p-5 sm:p-6 border-b border-[#e8eee9] dark:border-[#282c29]">
                                <div class="flex flex-wrap items-center justify-between gap-4">
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#0d2c1d] to-[#1c4d35] text-white shadow-sm ring-4 ring-emerald-900/10">
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <h3 class="m-0 text-base font-bold text-[#0d2c1d] dark:text-[#f5f5f0] leading-tight">Reservation Archive Records</h3>
                                            <p class="m-0 text-xs text-[#5a6b5c] dark:text-[#a8b8a8]">Complete archive of checked-out, no show, and cancelled bookings</p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-3">
                                        <div class="relative">
                                            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#889b8a]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                                            <input type="search" id="reservationSearchInput" placeholder="Search booker, email, phone, ID..." class="w-60 sm:w-68 rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-[#f8faf9] dark:bg-[#141715] py-2.5 pl-9 pr-3 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] placeholder:text-[#889b8a] focus:bg-white dark:focus:bg-[#181b19] focus:border-[#178a52] focus:ring-2 focus:ring-[#178a52]/20 focus:outline-none transition-all shadow-xs">
                                        </div>

                                        <button type="button" class="guest-filter-toggle inline-flex items-center justify-center gap-1.5 rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-4 py-2.5 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] transition-all hover:bg-[#f4f7f5] dark:hover:bg-[#141715] shadow-xs cursor-pointer" id="reservationFilterToggle" aria-expanded="false" aria-controls="reservationFilterPanel">
                                            <svg class="h-3.5 w-3.5 text-[#5a6b5c] dark:text-[#a8b8a8]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/></svg>
                                            <span>Filters</span>
                                            <span class="guest-filter-toggle__icon text-[0.7rem] text-[#889b8a]">▾</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="guest-filter-shell mt-4 grid gap-3" id="reservationFilterPanel" hidden>
                                    <div class="guest-toolbar grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 items-end gap-3 rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-[#f8faf9] dark:bg-[#141715] p-4 transition-all shadow-inner">
                                        <label class="grid gap-1.5 text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">
                                            <span>Status</span>
                                            <select id="reservationStatusFilter" class="w-full rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-2 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-xs cursor-pointer">
                                                <option value="all">All Statuses</option>
                                                <option value="checked out">Checked Out</option>
                                                <option value="no show">No Show</option>
                                                <option value="cancelled">Cancelled</option>
                                            </select>
                                        </label>
                                        <label class="grid gap-1.5 text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">
                                            <span>Sort by</span>
                                            <select id="reservationSortSelect" class="w-full rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-2 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-xs cursor-pointer">
                                                <option value="date-desc">Date (Newest first)</option>
                                                <option value="date-asc">Date (Oldest first)</option>
                                                <option value="res-id-desc">Reservation ID (High-Low)</option>
                                                <option value="res-id-asc">Reservation ID (Low-High)</option>
                                                <option value="name-asc">Booker Name (A-Z)</option>
                                                <option value="name-desc">Booker Name (Z-A)</option>
                                                <option value="amount-desc">Amount Paid (High-Low)</option>
                                            </select>
                                        </label>
                                        <label class="grid gap-1.5 text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">
                                            <span>Date from</span>
                                            <input type="date" id="reservationCheckOutFrom" class="w-full rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-2 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-xs">
                                        </label>
                                        <label class="grid gap-1.5 text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">
                                            <span>Date to</span>
                                            <input type="date" id="reservationCheckOutTo" class="w-full rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-2 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-xs">
                                        </label>
                                        <button type="button" class="cursor-pointer rounded-xl bg-[#e8efe9] dark:bg-[#1e2220] px-4 py-2.5 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] transition-colors hover:bg-[#178a52] hover:text-white border-0 shadow-xs" id="reservationFiltersClear">Clear</button>
                                    </div>
                                </div>
                            </div>

                            <div class="overflow-x-auto w-full" id="reservationTableWrap">
                                <table class="guest-table w-full min-w-[980px] text-left border-collapse">
                                    <thead class="bg-[#f4f8f5] dark:bg-[#151c17] border-b border-[#e2e8e4] dark:border-[#282c29]">
                                        <tr>
                                            <th class="py-3.5 px-5 text-[0.7rem] font-bold text-[#1f5c38] dark:text-[#8fd0ab] uppercase tracking-wider">RESERVATION ID</th>
                                            <th class="py-3.5 px-4 text-[0.7rem] font-bold text-[#1f5c38] dark:text-[#8fd0ab] uppercase tracking-wider">MAIN BOOKER</th>
                                            <th class="py-3.5 px-4 text-[0.7rem] font-bold text-[#1f5c38] dark:text-[#8fd0ab] uppercase tracking-wider text-center">GUESTS</th>
                                            <th class="py-3.5 px-4 text-[0.7rem] font-bold text-[#1f5c38] dark:text-[#8fd0ab] uppercase tracking-wider text-center">STATUS</th>
                                            <th class="py-3.5 px-4 text-[0.7rem] font-bold text-[#1f5c38] dark:text-[#8fd0ab] uppercase tracking-wider">SCHEDULE / CHECK-IN</th>
                                            <th class="py-3.5 px-4 text-[0.7rem] font-bold text-[#1f5c38] dark:text-[#8fd0ab] uppercase tracking-wider">CHECK-OUT / ACTIVITY</th>
                                            <th class="py-3.5 px-5 text-[0.7rem] font-bold text-[#1f5c38] dark:text-[#8fd0ab] uppercase tracking-wider text-right">PAID / TOTAL</th>
                                            <th class="py-3.5 px-4 text-right w-12"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="reservationTableBody" class="divide-y divide-[#edf2ee] dark:divide-[#242a26] bg-white dark:bg-[#181b19]">
                                        @forelse ($checkedOutReservations as $reservation)
                                            @php
                                                $normalizedStatus = trim((string) $reservation->status);
                                                $allGuestsCheckedOut = $reservation->reservationGuests->isNotEmpty() && $reservation->reservationGuests->every(fn ($g) => $g->checked_out_at !== null);
                                                
                                                if (in_array($normalizedStatus, ['No Show', 'no show', 'No show', 'no_show'])) {
                                                    $displayStatus = 'No Show';
                                                    $statusTheme = 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800/50 dark:text-slate-300 dark:border-slate-700';
                                                } elseif (in_array($normalizedStatus, ['Cancelled', 'cancelled', 'Cancel'])) {
                                                    $displayStatus = 'Cancelled';
                                                    $statusTheme = 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/40';
                                                } else {
                                                    $displayStatus = 'Checked Out';
                                                    $statusTheme = 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40';
                                                }

                                                $lastGuestCheckout = $reservation->reservationGuests->pluck('checked_out_at')->filter()->max();
                                                $displayCheckout = $reservation->check_out ?: $lastGuestCheckout;
                                                $effectiveDate = $displayCheckout ?: ($reservation->reservation_date ?: $reservation->created_at);
                                            @endphp
                                            <tr
                                                class="reservation-row group cursor-pointer select-none transition-all hover:bg-[#f2f7f4] dark:hover:bg-[#1e2621]"
                                                data-reservation-id="{{ $reservation->id }}"
                                                data-guest-count="{{ $reservation->number_of_guests ?? 1 }}"
                                                data-booker-name="{{ strtolower($reservation->booker_name ?? '') }}"
                                                data-email="{{ strtolower($reservation->email ?? '') }}"
                                                data-status="{{ strtolower($displayStatus) }}"
                                                data-check-out="{{ $effectiveDate ?? '' }}"
                                                data-amount="{{ (float) ($reservation->amount_paid ?? 0) }}"
                                                data-search="{{ strtolower(trim(($reservation->booker_name ?? '') . ' ' . ($reservation->email ?? '') . ' ' . ($reservation->phone ?? '') . ' ' . $reservation->id . ' res #' . $reservation->id . ' ' . $displayStatus)) }}"
                                                tabindex="0"
                                                role="button"
                                            >
                                                <td class="py-3.5 px-5">
                                                    <div class="flex items-center gap-2.5 min-w-0">
                                                        @if($reservation->reservationGuests->count() > 0)
                                                            <button type="button" class="btn-expand-row flex h-6 w-6 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-[#dbe3de] dark:border-[#282c29] bg-[#f4f7f5] dark:bg-[#141715] text-[#5a6b5c] transition-all hover:bg-[#178a52] hover:text-white [&.expanded]:rotate-180 [&.expanded]:text-[#178a52]" data-expand-reservation="{{ $reservation->id }}" aria-label="Toggle Companions">
                                                                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                                            </button>
                                                        @endif
                                                        <div class="min-w-0">
                                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold tracking-wide bg-[#178a52]/10 text-[#178a52] dark:bg-[#8fd0ab]/15 dark:text-[#8fd0ab] border border-[#178a52]/20 dark:border-[#8fd0ab]/30 shadow-2xs font-mono">
                                                                <svg class="w-3.5 h-3.5 text-[#178a52] dark:text-[#8fd0ab]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path fill-rule="evenodd" d="M5.5 3A2.5 2.5 0 0 0 3 5.5v2.879a2.5 2.5 0 0 0 .732 1.767l6.5 6.5a2.5 2.5 0 0 0 3.536 0l2.878-2.878a2.5 2.5 0 0 0 0-3.536l-6.5-6.5A2.5 2.5 0 0 0 8.38 3H5.5ZM6 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                                                                </svg>
                                                                #{{ $reservation->id }}
                                                            </span>
                                                            @php
                                                                $resPoolFee = (float) ($reservation->entranceFee?->pool_fee ?? 0);
                                                                $resPoolCount = (int) ($reservation->entranceFee?->pool_access_count ?? $reservation->reservationGuests->filter(fn($g) => (bool)$g->has_pool_access)->count());
                                                                $resHasPool = $resPoolFee > 0 || $resPoolCount > 0 || ($reservation->entranceFee?->pool_option && $reservation->entranceFee->pool_option !== 'no_pool');
                                                            @endphp
                                                            <div class="flex items-center gap-1.5 flex-wrap mt-0.5">
                                                                <span class="text-[0.7rem] text-[#718774] dark:text-[#889b8a] font-medium capitalize">{{ $reservation->reservation_type ?? 'online' }} Booking</span>
                                                                @if ($resHasPool)
                                                                    <span class="inline-flex items-center gap-1 rounded-md bg-[#e0f2fe] dark:bg-[#082f49] px-1.5 py-0.5 text-[0.65rem] font-bold text-[#0284c7] dark:text-[#38bdf8] border border-[#bae6fd] dark:border-[#0369a1]/40 shadow-2xs" title="Pool Included in Booking">
                                                                        <svg class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 16.5c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 20.25c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0" /></svg>
                                                                        Pool {{ $resPoolCount > 0 ? "({$resPoolCount})" : '' }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3.5 px-4 text-xs">
                                                    <div class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $reservation->booker_name }}</div>
                                                    <div class="text-[0.72rem] text-[#718774] dark:text-[#889b8a] mt-0.5 truncate max-w-[200px]">{{ $reservation->email ?: 'No email' }} · {{ $reservation->phone ?: 'No phone' }}</div>
                                                </td>
                                                <td class="py-3.5 px-4 text-xs font-bold text-center text-[#0d2c1d] dark:text-[#f5f5f0]">
                                                    <span class="inline-flex items-center justify-center min-w-[26px] px-2 py-0.5 rounded-full bg-[#f0f4f1] dark:bg-[#202722] border border-[#dbe3de] dark:border-[#282c29]">{{ $reservation->number_of_guests }}</span>
                                                </td>
                                                <td class="py-3.5 px-4 text-center">
                                                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $statusTheme }} shadow-2xs">{{ $displayStatus }}</span>
                                                </td>
                                                <td class="py-3.5 px-4 text-xs text-[#5a6b5c] dark:text-[#a8b8a8]">
                                                    @if($reservation->check_in)
                                                        <div class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">{{ \Carbon\Carbon::parse($reservation->check_in)->format('M d, Y') }}</div>
                                                        <div class="text-[0.7rem] text-[#718774] dark:text-[#889b8a]">{{ \Carbon\Carbon::parse($reservation->check_in)->format('h:i A') }}</div>
                                                    @elseif($reservation->reservation_date)
                                                        <div class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">{{ \Carbon\Carbon::parse($reservation->reservation_date)->format('M d, Y') }}</div>
                                                        <div class="text-[0.7rem] text-[#718774] dark:text-[#889b8a]">{{ $reservation->start_slot ?? 'Daytime' }}</div>
                                                    @else
                                                        <span class="text-[#889b8a]">N/A</span>
                                                    @endif
                                                </td>
                                                <td class="py-3.5 px-4 text-xs text-[#5a6b5c] dark:text-[#a8b8a8]">
                                                    @if($displayCheckout)
                                                        <div class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">{{ \Carbon\Carbon::parse($displayCheckout)->format('M d, Y') }}</div>
                                                        <div class="text-[0.7rem] text-[#718774] dark:text-[#889b8a]">{{ \Carbon\Carbon::parse($displayCheckout)->format('h:i A') }}</div>
                                                    @elseif($reservation->end_date)
                                                        <div class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">{{ \Carbon\Carbon::parse($reservation->end_date)->format('M d, Y') }}</div>
                                                        <div class="text-[0.7rem] text-[#718774] dark:text-[#889b8a]">{{ $reservation->end_slot ?? 'Daytime' }}</div>
                                                    @else
                                                        <span class="text-[#889b8a]">N/A</span>
                                                    @endif
                                                </td>
                                                <td class="py-3.5 px-5 text-xs text-right font-bold text-[#0d2c1d] dark:text-[#f5f5f0] tabular-nums">
                                                    <div class="text-emerald-700 dark:text-emerald-400 font-extrabold text-sm">₱{{ number_format($reservation->amount_paid, 2) }}</div>
                                                    <div class="text-[0.68rem] text-[#889b8a] font-normal">of ₱{{ number_format($reservation->total_amount, 2) }}</div>
                                                </td>
                                                <td class="py-3.5 px-4 text-right">
                                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg text-[#889b8a] transition-all group-hover:bg-[#178a52]/10 group-hover:text-[#178a52] dark:group-hover:text-[#8fd0ab] ml-auto" title="View booking details">
                                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                                    </div>
                                                </td>
                                            </tr>

                                            {{-- COMPANION ROWS --}}
                                            @php
                                                $isBulkCompanionName = function (?string $name): bool {
                                                    $name = strtolower(trim((string) $name));
                                                    return str_starts_with($name, 'bulk') || str_contains($name, 'companion');
                                                };

                                                $bulkAgeGroupLabel = function ($age): string {
                                                    if ($age === null || $age === '') return 'Unknown';
                                                    if (!is_numeric($age)) return (string) $age;
                                                    $age = (int) $age;
                                                    if ($age <= 12) return '0-12';
                                                    if ($age <= 17) return '13-17';
                                                    if ($age <= 59) return '18-59';
                                                    return '60+';
                                                };

                                                $companionGuests = $reservation->reservationGuests->filter(fn ($g) => ! $g->is_primary_guest && $g->customer);
                                                $groupedCompanionRows = [];
                                                $bulkGroupsForRes = [];

                                                foreach ($companionGuests as $guest) {
                                                    $customer = $guest->customer;
                                                    if ($isBulkCompanionName($customer->first_name)) {
                                                        $ageGroup = $bulkAgeGroupLabel($customer->age);
                                                        $gender = $customer->gender ?? 'N/A';
                                                        $genderLower = strtolower($gender);
                                                        $nationality = $customer->is_foreigner ? 'Foreigner' : 'Filipino';
                                                        $key = "{$reservation->id}|{$ageGroup}|{$genderLower}|{$nationality}";

                                                        if (! isset($bulkGroupsForRes[$key])) {
                                                            $bulkGroupsForRes[$key] = [
                                                                'type' => 'bulk',
                                                                'key' => $key,
                                                                'name' => 'Bulk Companions',
                                                                'age_group' => $ageGroup,
                                                                'gender' => $gender,
                                                                'nationality' => $nationality,
                                                                'is_foreigner' => (bool) $customer->is_foreigner,
                                                                'has_pool_access' => false,
                                                                'pool_access_count' => 0,
                                                                'count' => 0,
                                                                'members' => [],
                                                            ];
                                                        }
                                                        $bulkGroupsForRes[$key]['count']++;
                                                        if ($guest->has_pool_access) {
                                                            $bulkGroupsForRes[$key]['has_pool_access'] = true;
                                                            $bulkGroupsForRes[$key]['pool_access_count']++;
                                                        }
                                                        $bulkGroupsForRes[$key]['members'][] = [
                                                            'customer_id' => $customer->id,
                                                            'has_pool_access' => (bool) $guest->has_pool_access,
                                                            'check_in' => $guest->reservation?->check_in ? \Carbon\Carbon::parse($guest->reservation->check_in)->toDateTimeString() : null,
                                                            'checked_out_at' => $guest->checked_out_at ? \Carbon\Carbon::parse($guest->checked_out_at)->toDateTimeString() : null,
                                                        ];
                                                    } else {
                                                        $groupedCompanionRows[] = [
                                                            'type' => 'regular',
                                                            'guest' => $guest,
                                                        ];
                                                    }
                                                }

                                                foreach ($bulkGroupsForRes as $bg) {
                                                    $groupedCompanionRows[] = $bg;
                                                }
                                            @endphp

                                            @foreach ($groupedCompanionRows as $compRow)
                                                @if ($compRow['type'] === 'bulk')
                                                    <tr
                                                        class="companion-row companion-of-{{ $reservation->id }} group cursor-pointer select-none bg-[#f6faf7] dark:bg-[#111f16] hover:bg-[#ebf5ed] dark:hover:bg-[#162a1e] transition-colors border-l-4 border-l-emerald-600"
                                                        data-bulk-group="true"
                                                        data-bulk-key="{{ $compRow['key'] }}"
                                                        data-bulk-name="{{ $compRow['name'] }}"
                                                        data-bulk-count="{{ $compRow['count'] }}"
                                                        data-bulk-age="{{ $compRow['age_group'] }}"
                                                        data-bulk-gender="{{ $compRow['gender'] }}"
                                                        data-bulk-nationality="{{ $compRow['nationality'] }}"
                                                        data-has-pool="{{ ($compRow['has_pool_access'] ?? false) ? 'true' : 'false' }}"
                                                        data-reservation-id="{{ $reservation->id }}"
                                                        data-bulk-members='@json($compRow['members'])'
                                                        tabindex="0"
                                                        role="button"
                                                        aria-label="View {{ $compRow['name'] }} group details"
                                                        style="display: none;"
                                                    >
                                                        <td colspan="2" class="py-2.5 px-5 pl-12">
                                                            <div class="cell-person cell-person--companion flex items-center gap-3 min-w-0">
                                                                <span class="cell-person__avatar cell-person__avatar--bulk flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-2xs" title="Bulk Companion Group">
                                                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" /><path d="M5.082 14.254a8.287 8.287 0 00-1.308 5.135 9.687 9.687 0 01-1.764-.44l-.115-.04a.563.563 0 01-.373-.487l-.01-.121a3.75 3.75 0 016.576-3.036 7.525 7.525 0 00-3.006-1.011zM18.918 14.254a8.287 8.287 0 011.308 5.135 9.687 9.687 0 001.764-.44l.115-.04a.563.563 0 00.373-.487l.01-.121a3.75 3.75 0 00-6.576-3.036 7.525 7.525 0 013.006-1.011z" /></svg>
                                                                </span>
                                                                <div class="cell-person__body min-w-0">
                                                                    <div class="guest-name text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] flex items-center gap-1.5 flex-wrap">
                                                                        <span>{{ $compRow['name'] }}</span>
                                                                        <span class="px-2 py-0.5 text-[0.65rem] font-bold rounded-full bg-[#e0eae2] text-[#2c5f3e] dark:bg-[#1a3324] dark:text-[#8fd0ab]">{{ $compRow['count'] }}x</span>
                                                                        @if ($compRow['has_pool_access'] ?? false)
                                                                            <span class="inline-flex items-center gap-1 rounded-md bg-[#e0f2fe] dark:bg-[#082f49] px-1.5 py-0.5 text-[0.65rem] font-bold text-[#0284c7] dark:text-[#38bdf8] border border-[#bae6fd] dark:border-[#0369a1]/40 shadow-2xs" title="{{ $compRow['pool_access_count'] ?? $compRow['count'] }} with Pool">
                                                                                <svg class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 16.5c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 20.25c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0" /></svg>
                                                                                Pool ({{ $compRow['pool_access_count'] ?? $compRow['count'] }})
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                    <div class="guest-meta text-[0.7rem] text-[#718774] dark:text-[#889b8a]">Bulk companion group</div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="py-2.5 px-4 text-xs font-medium text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $compRow['age_group'] }}</td>
                                                        <td class="py-2.5 px-4"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $compRow['is_foreigner'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-[#e8eee9] text-[#2c5f3e] dark:bg-[#202722] dark:text-gray-300' }}">{{ $compRow['nationality'] }}</span></td>
                                                        <td colspan="4"></td>
                                                    </tr>
                                                @else
                                                    @php
                                                        $guest = $compRow['guest'];
                                                        $compHasPool = (bool) ($guest->has_pool_access ?? false);
                                                    @endphp
                                                    <tr
                                                        class="companion-row companion-of-{{ $reservation->id }} group cursor-pointer select-none bg-[#f6faf7] dark:bg-[#111f16] hover:bg-[#ebf5ed] dark:hover:bg-[#162a1e] transition-colors border-l-4 border-l-emerald-600"
                                                        data-customer-id="{{ $guest->customer->id }}"
                                                        data-has-pool="{{ $compHasPool ? 'true' : 'false' }}"
                                                        tabindex="0"
                                                        role="button"
                                                        aria-label="View details for {{ collect([$guest->customer->first_name, $guest->customer->middle_name, $guest->customer->last_name])->filter()->join(' ') }}"
                                                        style="display: none;"
                                                    >
                                                        <td colspan="2" class="py-2.5 px-5 pl-12">
                                                            <div class="cell-person cell-person--companion flex items-center gap-3 min-w-0">
                                                                <span class="cell-person__avatar cell-person__avatar--companion flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-700 text-white shadow-2xs" title="Companion">
                                                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" /></svg>
                                                                </span>
                                                                <div class="cell-person__body min-w-0">
                                                                    <div class="guest-name text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] flex items-center gap-1.5 flex-wrap">
                                                                        <span>{{ collect([$guest->customer->first_name, $guest->customer->middle_name, $guest->customer->last_name])->filter()->join(' ') }}</span>
                                                                        @if ($compHasPool)
                                                                            <span class="inline-flex items-center gap-1 rounded-md bg-[#e0f2fe] dark:bg-[#082f49] px-1.5 py-0.5 text-[0.65rem] font-bold text-[#0284c7] dark:text-[#38bdf8] border border-[#bae6fd] dark:border-[#0369a1]/40 shadow-2xs" title="Availing Pool Access">
                                                                                <svg class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 16.5c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 20.25c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0" /></svg>
                                                                                Pool Access
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                    <div class="guest-meta text-[0.7rem] text-[#718774] dark:text-[#889b8a] font-mono">ID: #{{ $guest->customer->id }} · Companion</div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="py-2.5 px-4 text-xs font-medium text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $guest->customer->age ?? 'N/A' }}</td>
                                                        <td class="py-2.5 px-4"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $guest->customer->is_foreigner ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-[#e8eee9] text-[#2c5f3e] dark:bg-[#202722] dark:text-gray-300' }}">{{ $guest->customer->is_foreigner ? 'Foreigner' : 'Filipino' }}</span></td>
                                                        <td colspan="4"></td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        @empty
                                            <tr>
                                                <td colspan="8" class="px-5 py-12 text-center text-xs text-[#889b8a]">
                                                    <div class="flex flex-col items-center justify-center gap-2">
                                                        <svg class="h-8 w-8 text-[#889b8a]/60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                                        <span class="font-medium">No reservation archive records found.</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- RESERVATION TABLE PAGINATION (Embedded in Card Footer) --}}
                            <div class="records-pagination bg-[#f8faf9] dark:bg-[#141715] border-t border-[#e2e8e4] dark:border-[#282c29] px-5 py-4">
                                <div class="flex flex-wrap items-center justify-between gap-x-5 gap-y-3 text-xs">
                                    <div class="flex items-center gap-2 text-[#4a5c4d] dark:text-[#a8b8a8]">
                                        <span class="font-medium">Showing</span>
                                        <select id="reservationPerPage" class="cursor-pointer rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-1.5 text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-xs">
                                            <option value="5">5</option>
                                            <option value="10" selected>10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                        </select>
                                        <span class="font-medium">per page</span>
                                    </div>
                                    <span id="reservationResultsCount" class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">Showing 0 of 0 reservations</span>
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center gap-1.5">
                                            <button type="button" id="reservationPrevPage" class="cursor-pointer rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3.5 py-1.5 font-bold text-[#0d2c1d] dark:text-[#f5f5f0] transition-all hover:bg-[#f4f7f5] dark:hover:bg-[#202722] disabled:cursor-not-allowed disabled:opacity-40 shadow-xs">‹ Prev</button>
                                            <div id="reservationPageNumbers" class="flex items-center gap-1"></div>
                                            <button type="button" id="reservationNextPage" class="cursor-pointer rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3.5 py-1.5 font-bold text-[#0d2c1d] dark:text-[#f5f5f0] transition-all hover:bg-[#f4f7f5] dark:hover:bg-[#202722] disabled:cursor-not-allowed disabled:opacity-40 shadow-xs">Next ›</button>
                                        </div>
                                        <div class="hidden sm:flex items-center gap-1.5 pl-3 border-l border-[#dbe3de] dark:border-[#282c29] text-xs text-[#5a6b5c] dark:text-[#a8b8a8]">
                                            <span>Go to</span>
                                            <input type="number" id="reservationPageInput" min="1" value="1" class="w-14 rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-2 py-1 text-center text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-xs">
                                            <button type="button" id="reservationGoPage" class="cursor-pointer rounded-xl bg-[#178a52] px-3 py-1 font-bold text-white transition-colors hover:bg-[#126e41] border-0 shadow-xs">Go</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- MODALS --}}
                <div class="guest-modal fixed inset-0 z-[1000] hidden items-center justify-center is-open:flex" id="guestModal" aria-hidden="true">
                    <div class="guest-modal__backdrop absolute inset-0 bg-black/60 backdrop-blur-sm" data-close-modal="true"></div>
                    <div class="guest-modal__content relative z-[1] w-full max-w-[720px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-white dark:bg-[#181b19] border border-[#dbe3de] dark:border-[#282c29] p-6 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="guestModalTitle">
                        <button type="button" class="guest-modal__close absolute right-4 top-4 cursor-pointer border-0 bg-transparent text-2xl text-[#5a6b5c] hover:text-[#0d2c1d] dark:text-[#a8b8a8] dark:hover:text-white" data-close-modal="true" aria-label="Close details">&times;</button>
                        <div class="guest-modal__header mb-4 flex items-center gap-3">
                            <h3 id="guestModalTitle" class="guest-modal__title m-0 text-lg font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">Guest Details</h3>
                        </div>
                        <div id="guestModalBody" class="guest-modal__body grid gap-4 text-xs"></div>
                    </div>
                </div>

                <div class="guest-modal fixed inset-0 z-[1000] hidden items-center justify-center is-open:flex" id="reservationModal" aria-hidden="true">
                    <div class="guest-modal__backdrop absolute inset-0 bg-black/60 backdrop-blur-sm" data-close-reservation-modal="true"></div>
                    <div class="guest-modal__content relative z-[1] w-full max-w-[720px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-white dark:bg-[#181b19] border border-[#dbe3de] dark:border-[#282c29] p-6 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="reservationModalTitle">
                        <button type="button" class="guest-modal__close absolute right-4 top-4 cursor-pointer border-0 bg-transparent text-2xl text-[#5a6b5c] hover:text-[#0d2c1d] dark:text-[#a8b8a8] dark:hover:text-white" data-close-reservation-modal="true" aria-label="Close details">&times;</button>
                        <div class="guest-modal__header mb-4 flex items-center gap-3">
                            <h3 id="reservationModalTitle" class="guest-modal__title m-0 text-lg font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">Reservation Archive Details</h3>
                        </div>
                        <div id="reservationModalBody" class="guest-modal__body grid gap-4 text-xs"></div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <x-staff_chatbot />

    <script>
        window.staffGuestData = @json($guestData ?? []);
        window.staffBulkGroupData = @json($bulkGroupData ?? []);
        window.staffReservationData = @json($reservationData ?? []);
        window.staffReservationAmounts = @json($reservationAmounts ?? []);
    </script>
</body>
</html>
