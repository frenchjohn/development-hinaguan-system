<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Records — Hinaguan Nature Park</title>
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

            <main class="dash-content p-6">
                <x-header
                    title="Records"
                    subtitle="View checked-out guests and completed reservations"
                />

                <!-- SUMMARY STRIP (metric cards matching screenshot with colored left accent and side icon) -->
                <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Card 1: Guest Records -->
                    <article class="flex items-center gap-4 p-5 rounded-2xl bg-white dark:bg-[#181b19] border border-[#dbe3de] dark:border-[#282c29] border-l-[4px] border-l-emerald-600 dark:border-l-emerald-500 shadow-[0_2px_8px_rgba(0,0,0,0.04)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#eaf5ee] text-[#178a52] dark:bg-[#1e2220] dark:text-[#8fd0ab]">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="m-0 text-2xl font-bold leading-tight text-[#0d2c1d] dark:text-[#f5f5f0]" id="counterGuestRecords">{{ $guestRecordsCount }}</p>
                            <p class="m-0 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">Guest Records</p>
                            <p class="m-0 text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Total checked-out guests</p>
                        </div>
                    </article>

                    <!-- Card 2: Completed Reservations -->
                    <article class="flex items-center gap-4 p-5 rounded-2xl bg-white dark:bg-[#181b19] border border-[#dbe3de] dark:border-[#282c29] border-l-[4px] border-l-blue-500 dark:border-l-blue-400 shadow-[0_2px_8px_rgba(0,0,0,0.04)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#e8f0fe] text-[#2563eb] dark:bg-[#1b2a45] dark:text-[#7da7f0]">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="m-0 text-2xl font-bold leading-tight text-[#0d2c1d] dark:text-[#f5f5f0]" id="counterCompletedReservations">{{ $completedReservationsCount }}</p>
                            <p class="m-0 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">Completed Reservations</p>
                            <p class="m-0 text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Reservations completed</p>
                        </div>
                    </article>

                    <!-- Card 3: Revenue Collected -->
                    <article class="flex items-center gap-4 p-5 rounded-2xl bg-white dark:bg-[#181b19] border border-[#dbe3de] dark:border-[#282c29] border-l-[4px] border-l-amber-500 dark:border-l-amber-400 shadow-[0_2px_8px_rgba(0,0,0,0.04)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#fef3c7] text-[#d97706] dark:bg-[#3a2f14] dark:text-[#e5c35c]">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="m-0 text-2xl font-bold leading-tight text-[#0d2c1d] dark:text-[#f5f5f0]" id="counterRevenueCollected">₱{{ number_format($completedRevenue) }}</p>
                            <p class="m-0 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">Revenue Collected</p>
                            <p class="m-0 text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">From completed stays</p>
                        </div>
                    </article>

                    <!-- Card 4: Unique Visitors -->
                    <article class="flex items-center gap-4 p-5 rounded-2xl bg-white dark:bg-[#181b19] border border-[#dbe3de] dark:border-[#282c29] border-l-[4px] border-l-purple-500 dark:border-l-purple-400 shadow-[0_2px_8px_rgba(0,0,0,0.04)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#f1eafd] text-[#7c3aed] dark:bg-[#2b2142] dark:text-[#b79df0]">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.999-3.198a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="m-0 text-2xl font-bold leading-tight text-[#0d2c1d] dark:text-[#f5f5f0]" id="counterUniqueVisitors">{{ $uniqueGuestsCount }}</p>
                            <p class="m-0 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">Unique Visitors</p>
                            <p class="m-0 text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Different guests visited</p>
                        </div>
                    </article>
                </div>

                <!-- TAB BUTTONS -->
                <div class="mb-4 flex items-center gap-3">
                    <button type="button" class="records-tab-btn records-tab-btn--active cursor-pointer rounded-full border border-transparent bg-[#178a52] px-6 py-2 text-xs font-bold text-white shadow-sm transition-all hover:bg-[#126e41] focus:outline-none" data-tab="guests" id="guestsTabBtn">
                        Guests
                    </button>
                    <button type="button" class="records-tab-btn cursor-pointer rounded-full border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-6 py-2 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] shadow-sm transition-all hover:bg-[#f4f7f5] dark:hover:bg-[#141715] focus:outline-none" data-tab="reservations" id="reservationsTabBtn">
                        Reservations
                    </button>
                </div>

                {{-- CHECKED-OUT GUEST RECORDS TABLE --}}
                <div class="min-w-0">
                    <section data-tab-content="guests">
                        <div class="guest-panel my-4 rounded-2xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] p-6 shadow-[0_4px_16px_rgba(0,0,0,0.03)] dark:shadow-[0_4px_20px_rgba(0,0,0,0.4)]">
                            <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#0d2c1d] text-white shadow-sm">
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="m-0 text-base font-bold text-[#0d2c1d] dark:text-[#f5f5f0] leading-tight">Checked-Out Guest Records</h3>
                                        <p class="m-0 text-xs text-[#5a6b5c] dark:text-[#a8b8a8]">Guests who have completed their visit and checked out</p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="relative">
                                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#889b8a]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                                        <input type="search" id="guestSearchInput" placeholder="Search guest, ID, or res" class="w-60 rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-[#f8faf9] dark:bg-[#141715] py-2 pl-9 pr-3 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] placeholder:text-[#889b8a] focus:bg-white dark:focus:bg-[#181b19] focus:border-[#178a52] focus:ring-1 focus:ring-[#178a52] focus:outline-none transition-all shadow-sm">
                                    </div>

                                    <!-- Show Companions Checkbox (Only on guest side, default: unchecked) -->
                                    <label class="inline-flex items-center gap-2 cursor-pointer select-none px-3.5 py-2 rounded-xl bg-[#f4f7f5] dark:bg-[#141715] border border-[#dbe3de] dark:border-[#282c29] text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0] shadow-sm hover:bg-[#e8efe9] dark:hover:bg-[#242a26] transition-colors">
                                        <input type="checkbox" id="showCompanionsCheckbox" class="w-4 h-4 text-[#178a52] rounded border-[#dbe3de] dark:border-[#282c29] focus:ring-[#178a52] cursor-pointer">
                                        <span>Show Companions</span>
                                    </label>

                                    <!-- Filter button -->
                                    <button type="button" class="guest-filter-toggle inline-flex items-center justify-center gap-1.5 rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3.5 py-2 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] transition-all hover:bg-[#f4f7f5] dark:hover:bg-[#141715] shadow-sm cursor-pointer" id="guestFilterToggle" aria-expanded="false" aria-controls="guestFilterPanel">
                                        <svg class="h-3.5 w-3.5 text-[#5a6b5c] dark:text-[#a8b8a8]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/></svg>
                                        <span>Filters</span>
                                        <span class="guest-filter-toggle__icon text-[0.7rem] text-[#889b8a]">▾</span>
                                    </button>
                                </div>
                            </div>

                            @if (session('success'))
                                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-950/40 dark:border-emerald-900 px-4 py-3 text-xs font-semibold text-emerald-800 dark:text-emerald-300">{{ session('success') }}</div>
                            @endif

                            <div class="guest-filter-shell mb-4 grid gap-3">
                                <div class="guest-toolbar grid grid-cols-1 sm:grid-cols-3 md:grid-cols-4 items-end gap-3 rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-[#f8faf9] dark:bg-[#141715] p-4 transition-all" id="guestFilterPanel" hidden>
                                    <label class="grid gap-1.5 text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">
                                        <span>Sort by</span>
                                        <select id="guestSortSelect" class="w-full rounded-lg border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-2 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-sm cursor-pointer">
                                            <option value="checkout-desc">Checkout (Newest)</option>
                                            <option value="checkout-asc">Checkout (Oldest)</option>
                                            <option value="name-asc">Name (A-Z)</option>
                                            <option value="name-desc">Name (Z-A)</option>
                                            <option value="customer-id-asc">Customer ID (Low-High)</option>
                                            <option value="customer-id-desc">Customer ID (High-Low)</option>
                                            <option value="reservation-asc">Reservation (Low-High)</option>
                                            <option value="reservation-desc">Reservation (High-Low)</option>
                                            <option value="age-asc">Age (Low-High)</option>
                                            <option value="age-desc">Age (High-Low)</option>
                                            <option value="gender-asc">Gender (A-Z)</option>
                                            <option value="gender-desc">Gender (Z-A)</option>
                                            <option value="nationality-asc">Nationality (A-Z)</option>
                                            <option value="nationality-desc">Nationality (Z-A)</option>
                                            <option value="status-asc">Status (A-Z)</option>
                                            <option value="status-desc">Status (Z-A)</option>
                                        </select>
                                    </label>
                                    <label class="grid gap-1.5 text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">
                                        <span>Checked out from</span>
                                        <input type="date" id="guestCheckOutFrom" class="w-full rounded-lg border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-2 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-sm">
                                    </label>
                                    <label class="grid gap-1.5 text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">
                                        <span>Checked out to</span>
                                        <input type="date" id="guestCheckOutTo" class="w-full rounded-lg border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-2 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-sm">
                                    </label>
                                    <button type="button" class="cursor-pointer rounded-lg bg-[#e8efe9] dark:bg-[#1e2220] px-4 py-2 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] transition-colors hover:bg-[#178a52] hover:text-white border-0" id="guestFiltersClear">Clear</button>
                                </div>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-[#e5e9e6] dark:border-[#282c29]" id="guestTableWrap">
                                <table class="guest-table w-full min-w-[860px] text-left border-collapse">
                                    <thead class="bg-[#f4f7f5] dark:bg-[#141715] border-b border-[#e5e9e6] dark:border-[#282c29]">
                                        <tr>
                                            <th class="py-3.5 px-4 text-[0.72rem] font-bold text-[#2f6f45] dark:text-[#8fd0ab] uppercase tracking-wider sortable cursor-pointer select-none" data-sort="name">GUEST / GROUP</th>
                                            <th class="py-3.5 px-4 text-[0.72rem] font-bold text-[#2f6f45] dark:text-[#8fd0ab] uppercase tracking-wider sortable cursor-pointer select-none" data-sort="customer-id">CUSTOMER ID</th>
                                            <th class="py-3.5 px-4 text-[0.72rem] font-bold text-[#2f6f45] dark:text-[#8fd0ab] uppercase tracking-wider sortable cursor-pointer select-none" data-sort="reservation">RESERVATION</th>
                                            <th class="py-3.5 px-4 text-[0.72rem] font-bold text-[#2f6f45] dark:text-[#8fd0ab] uppercase tracking-wider sortable cursor-pointer select-none" data-sort="age">AGE</th>
                                            <th class="py-3.5 px-4 text-[0.72rem] font-bold text-[#2f6f45] dark:text-[#8fd0ab] uppercase tracking-wider sortable cursor-pointer select-none" data-sort="gender">GENDER</th>
                                            <th class="py-3.5 px-4 text-[0.72rem] font-bold text-[#2f6f45] dark:text-[#8fd0ab] uppercase tracking-wider sortable cursor-pointer select-none" data-sort="nationality">NATIONALITY</th>
                                            <th class="py-3.5 px-4 text-[0.72rem] font-bold text-[#2f6f45] dark:text-[#8fd0ab] uppercase tracking-wider sortable cursor-pointer select-none" data-sort="status">STATUS</th>
                                            <th class="py-3.5 px-4 text-[0.72rem] font-bold text-[#2f6f45] dark:text-[#8fd0ab] uppercase tracking-wider sortable cursor-pointer select-none" data-sort="checked-out">CHECKED OUT</th>
                                            <th class="py-3.5 px-4 text-right"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="guestTableBody" class="divide-y divide-[#e5e9e6] dark:divide-[#282c29] bg-white dark:bg-[#181b19]">
                                        @forelse ($guestRows as $row)
                                            @if (($row['type'] ?? '') === 'bulk')
                                                @php
                                                    $group = $row['group'];
                                                    $bulkStatus = 'Checked Out';
                                                    $bulkResAmount = (float) ($reservationAmounts[$group['reservation_id']] ?? 0);
                                                @endphp
                                                <tr
                                                    class="guest-row guest-row--bulk-group cursor-pointer select-none transition-colors hover:bg-[#f8faf9] dark:hover:bg-[#141715]"
                                                    data-bulk-group="true"
                                                    data-is-companion="true"
                                                    data-is-primary="false"
                                                    data-guest-count="{{ $group['count'] }}"
                                                    data-bulk-key="{{ $group['key'] }}"
                                                    data-gender="{{ $group['gender'] }}"
                                                    data-nationality="{{ $group['nationality'] }}"
                                                    data-status="{{ $bulkStatus }}"
                                                    data-reservation-id="{{ $group['reservation_id'] }}"
                                                    data-reservation-amount="{{ $bulkResAmount }}"
                                                    data-age-value="999999"
                                                    data-checked-out="{{ $group['checked_out_at'] ?? '' }}"
                                                    data-search="{{ strtolower(trim(($group['name'] ?? '') . ' ' . $group['reservation_id'] . ' ' . $group['gender'] . ' ' . $group['age_group'] . ' ' . $group['nationality'] . ' checked out ' . $group['count'] . ' bulk companion group')) }}"
                                                    tabindex="0"
                                                    role="button"
                                                >
                                                    <td class="py-3 px-4">
                                                        <div class="flex items-center gap-3 min-w-0">
                                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#178a52] text-white shadow-sm" title="Bulk Companion Group">
                                                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" /><path d="M5.082 14.254a8.287 8.287 0 00-1.308 5.135 9.687 9.687 0 01-1.764-.44l-.115-.04a.563.563 0 01-.373-.487l-.01-.121a3.75 3.75 0 016.576-3.036 7.525 7.525 0 00-3.006-1.011zM18.918 14.254a8.287 8.287 0 011.308 5.135 9.687 9.687 0 001.764-.44l.115-.04a.563.563 0 00.373-.487l.01-.121a3.75 3.75 0 00-6.576-3.036 7.525 7.525 0 013.006-1.011z" /></svg>
                                                            </span>
                                                            <div class="min-w-0">
                                                                <div class="text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] flex items-center gap-1.5">
                                                                    <span>{{ $group['name'] }}</span>
                                                                    <span class="px-2 py-0.5 text-[0.68rem] font-bold rounded-full bg-[#e5e9e6] text-[#5a6b5c] dark:bg-[#282c29] dark:text-[#a8b8a8]">{{ $group['count'] }}x</span>
                                                                </div>
                                                                <div class="text-[0.75rem] text-[#889b8a] mt-0.5">Bulk companion group</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="py-3 px-4 text-xs text-[#889b8a]">—</td>
                                                    <td class="py-3 px-4">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#eaf5ee] text-[#178a52] dark:bg-[#1e2220] dark:text-[#8fd0ab] border border-[#c2e2ce] dark:border-[#1e4e33]">Reservation #{{ $group['reservation_id'] }}</span>
                                                    </td>
                                                    <td class="py-3 px-4 text-xs text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $group['age_group'] }}</td>
                                                    <td class="py-3 px-4 text-xs text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $group['gender'] }}</td>
                                                    <td class="py-3 px-4 text-xs font-medium text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $group['nationality'] }}</td>
                                                    <td class="py-3 px-4">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#eaf5ee] text-[#178a52] dark:bg-[#1e2220] dark:text-[#8fd0ab]">Checked Out</span>
                                                    </td>
                                                    <td class="py-3 px-4">
                                                        @if ($group['checked_out_at'])
                                                            <div class="text-xs font-medium text-[#0d2c1d] dark:text-[#f5f5f0]">{{ \Carbon\Carbon::parse($group['checked_out_at'])->format('M d, Y') }}</div>
                                                            <div class="text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">{{ \Carbon\Carbon::parse($group['checked_out_at'])->format('h:i A') }}</div>
                                                        @else
                                                            <span class="text-xs text-[#889b8a]">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-3 px-4 text-right text-[#9ca3af]">
                                                        <svg class="h-5 w-5 ml-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
                                                    </td>
                                                </tr>
                                            @else
                                                @php
                                                    $guestEntry = $row['entry'];
                                                    $customer = $guestEntry->customer;
                                                    $isPrimaryGuest = (bool) ($guestEntry->is_primary_guest ?? false);
                                                    $guestStatus = 'Checked Out';
                                                    $guestTypeLabel = ($guestEntry->reservation?->reservation_type ?? '') === 'walk_in' ? 'Walk-in Guest' : ($isPrimaryGuest ? 'Online Guest' : 'Companion');
                                                    $guestResAmount = (float) ($reservationAmounts[$guestEntry->reservation_id ?? 0] ?? 0);
                                                @endphp
                                                <tr
                                                    class="guest-row cursor-pointer select-none transition-colors hover:bg-[#f8faf9] dark:hover:bg-[#141715]"
                                                    data-customer-id="{{ $customer->id }}"
                                                    data-is-companion="{{ $isPrimaryGuest ? 'false' : 'true' }}"
                                                    data-is-primary="{{ $isPrimaryGuest ? 'true' : 'false' }}"
                                                    data-guest-count="1"
                                                    data-age="{{ $customer->age ?? 'N/A' }}"
                                                    data-gender="{{ $customer->gender ?? 'N/A' }}"
                                                    data-nationality="{{ $customer->is_foreigner ? 'Foreigner' : 'Filipino' }}"
                                                    data-status="{{ $guestStatus }}"
                                                    data-reservation-id="{{ $guestEntry->reservation_id ?? '' }}"
                                                    data-reservation-amount="{{ $guestResAmount }}"
                                                    data-is-foreigner="{{ $customer->is_foreigner ? 'Foreigner' : 'Filipino' }}"
                                                    data-checked-out="{{ $guestEntry->checked_out_at ?? '' }}"
                                                    data-age-value="{{ is_numeric($customer->age) ? (int) $customer->age : 999999 }}"
                                                    data-search="{{ strtolower(trim(($customer->first_name ?? '') . ' ' . ($customer->middle_name ?? '') . ' ' . ($customer->last_name ?? '') . ' ' . $customer->id . ' ' . ($customer->gender ?? '') . ' ' . ($customer->is_foreigner ? 'Foreigner' : 'Filipino') . ' ' . $guestStatus . ' ' . $guestTypeLabel . ' ' . ($guestEntry->reservation_id ?? ''))) }}"
                                                    tabindex="0"
                                                    role="button"
                                                >
                                                    <td class="py-3 px-4">
                                                        <div class="flex items-center gap-3 min-w-0">
                                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $isPrimaryGuest ? 'bg-[#c8a45d] text-white' : 'bg-[#2f6f45] text-white' }} shadow-sm" title="{{ $isPrimaryGuest ? 'Main Guest' : 'Companion' }}">
                                                                @if ($isPrimaryGuest)
                                                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" /></svg>
                                                                @else
                                                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" /></svg>
                                                                @endif
                                                            </span>
                                                            <div class="min-w-0">
                                                                <div class="text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">{{ collect([$customer->first_name, $customer->middle_name, $customer->last_name])->filter()->join(' ') }}</div>
                                                                <div class="text-[0.75rem] text-[#889b8a] mt-0.5">{{ $guestTypeLabel }}</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="py-3 px-4 text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $customer->id }}</td>
                                                    <td class="py-3 px-4">
                                                        @if ($guestEntry->reservation_id)
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#eaf5ee] text-[#178a52] dark:bg-[#1e2220] dark:text-[#8fd0ab] border border-[#c2e2ce] dark:border-[#1e4e33]">Reservation #{{ $guestEntry->reservation_id }}</span>
                                                        @else
                                                            <span class="text-xs text-[#889b8a]">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-3 px-4 text-xs text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $customer->age ?? 'N/A' }}</td>
                                                    <td class="py-3 px-4 text-xs text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $customer->gender ?? 'N/A' }}</td>
                                                    <td class="py-3 px-4 text-xs font-medium text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $customer->is_foreigner ? 'Foreigner' : 'Filipino' }}</td>
                                                    <td class="py-3 px-4">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#eaf5ee] text-[#178a52] dark:bg-[#1e2220] dark:text-[#8fd0ab]">Checked Out</span>
                                                    </td>
                                                    <td class="py-3 px-4">
                                                        @if ($guestEntry->checked_out_at)
                                                            <div class="text-xs font-medium text-[#0d2c1d] dark:text-[#f5f5f0]">{{ \Carbon\Carbon::parse($guestEntry->checked_out_at)->format('M d, Y') }}</div>
                                                            <div class="text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">{{ \Carbon\Carbon::parse($guestEntry->checked_out_at)->format('h:i A') }}</div>
                                                        @else
                                                            <span class="text-xs text-[#889b8a]">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-3 px-4 text-right text-[#9ca3af]">
                                                        <svg class="h-5 w-5 ml-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
                                                    </td>
                                                </tr>
                                            @endif
                                        @empty
                                            <tr>
                                                <td colspan="9" class="px-4 py-8 text-center text-xs text-[#889b8a]">No checked-out guest records found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- GUEST TABLE PAGINATION --}}
                            <div class="records-pagination mt-4 border-t border-[#e5e9e6] dark:border-[#282c29] pt-4">
                                <div class="flex flex-wrap items-center justify-between gap-x-5 gap-y-3 text-xs">
                                    <div class="flex items-center gap-2 text-[#5a6b5c] dark:text-[#a8b8a8]">
                                        <span>Showing</span>
                                        <select id="guestPerPage" class="cursor-pointer rounded-lg border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-2 py-1 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-sm">
                                            <option value="5">5</option>
                                            <option value="10" selected>10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                        </select>
                                        <span>per page</span>
                                    </div>
                                    <span id="guestResultsCount" class="font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">Showing 0 of 0 records</span>
                                    <div class="flex items-center gap-1.5">
                                        <button type="button" id="guestPrevPage" class="cursor-pointer rounded-lg border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-1.5 font-semibold text-[#0d2c1d] dark:text-[#f5f5f0] transition-colors hover:bg-[#f4f7f5] dark:hover:bg-[#141715] disabled:cursor-not-allowed disabled:opacity-40 shadow-sm">‹ Prev</button>
                                        <div id="guestPageNumbers" class="flex items-center gap-1"></div>
                                        <button type="button" id="guestNextPage" class="cursor-pointer rounded-lg border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-1.5 font-semibold text-[#0d2c1d] dark:text-[#f5f5f0] transition-colors hover:bg-[#f4f7f5] dark:hover:bg-[#141715] disabled:cursor-not-allowed disabled:opacity-40 shadow-sm">Next ›</button>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center gap-2 text-xs text-[#5a6b5c] dark:text-[#a8b8a8]">
                                    <span>Go to page</span>
                                    <input type="number" id="guestPageInput" min="1" value="1" class="w-16 rounded-lg border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-2 py-1 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-sm">
                                    <button type="button" id="guestGoPage" class="cursor-pointer rounded-lg bg-[#178a52] px-3 py-1 font-semibold text-white transition-colors hover:bg-[#126e41] border-0 shadow-sm">Go</button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- RESERVATIONS TAB SECTION -->
                    <section class="guest-panel my-4 rounded-2xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] p-6 shadow-[0_4px_16px_rgba(0,0,0,0.03)] dark:shadow-[0_4px_20px_rgba(0,0,0,0.4)]" data-tab-content="reservations" hidden>
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#0d2c1d] text-white shadow-sm">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="m-0 text-base font-bold text-[#0d2c1d] dark:text-[#f5f5f0] leading-tight">Completed Reservations</h3>
                                    <p class="m-0 text-xs text-[#5a6b5c] dark:text-[#a8b8a8]">Records of reservations that have been checked out</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="relative">
                                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#889b8a]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                                    <input type="search" id="reservationSearchInput" placeholder="Search booker, email, or ID" class="w-60 rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-[#f8faf9] dark:bg-[#141715] py-2 pl-9 pr-3 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] placeholder:text-[#889b8a] focus:bg-white dark:focus:bg-[#181b19] focus:border-[#178a52] focus:ring-1 focus:ring-[#178a52] focus:outline-none transition-all shadow-sm">
                                </div>
                                <button type="button" class="guest-filter-toggle inline-flex items-center justify-center gap-1.5 rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3.5 py-2 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] transition-all hover:bg-[#f4f7f5] dark:hover:bg-[#141715] shadow-sm cursor-pointer" id="reservationFilterToggle" aria-expanded="false" aria-controls="reservationFilterPanel">
                                    <svg class="h-3.5 w-3.5 text-[#5a6b5c] dark:text-[#a8b8a8]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/></svg>
                                    <span>Filters</span>
                                    <span class="guest-filter-toggle__icon text-[0.7rem] text-[#889b8a]">▾</span>
                                </button>
                            </div>
                        </div>

                        <div class="guest-filter-shell mb-4 grid gap-3">
                            <div class="guest-toolbar grid grid-cols-1 sm:grid-cols-3 md:grid-cols-4 items-end gap-3 rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-[#f8faf9] dark:bg-[#141715] p-4 transition-all" id="reservationFilterPanel" hidden>
                                <label class="grid gap-1.5 text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">
                                    <span>Sort by</span>
                                    <select id="reservationSortSelect" class="w-full rounded-lg border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-2 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-sm cursor-pointer">
                                        <option value="date-desc">Checkout (Newest)</option>
                                        <option value="date-asc">Checkout (Oldest)</option>
                                        <option value="name-asc">Booker Name (A-Z)</option>
                                        <option value="name-desc">Booker Name (Z-A)</option>
                                        <option value="amount-desc">Amount (High to Low)</option>
                                    </select>
                                </label>
                                <label class="grid gap-1.5 text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">
                                    <span>Checked out from</span>
                                    <input type="date" id="reservationCheckOutFrom" class="w-full rounded-lg border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-2 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-sm">
                                </label>
                                <label class="grid gap-1.5 text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">
                                    <span>Checked out to</span>
                                    <input type="date" id="reservationCheckOutTo" class="w-full rounded-lg border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-2 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-sm">
                                </label>
                                <button type="button" class="cursor-pointer rounded-lg bg-[#e8efe9] dark:bg-[#1e2220] px-4 py-2 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] transition-colors hover:bg-[#178a52] hover:text-white border-0" id="reservationFiltersClear">Clear</button>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-[#e5e9e6] dark:border-[#282c29]" id="reservationTableWrap">
                            <table class="guest-table w-full min-w-[860px] text-left border-collapse">
                                <thead class="bg-[#f4f7f5] dark:bg-[#141715] border-b border-[#e5e9e6] dark:border-[#282c29]">
                                    <tr>
                                        <th class="py-3.5 px-4 text-[0.72rem] font-bold text-[#2f6f45] dark:text-[#8fd0ab] uppercase tracking-wider">BOOKER NAME</th>
                                        <th class="py-3.5 px-4 text-[0.72rem] font-bold text-[#2f6f45] dark:text-[#8fd0ab] uppercase tracking-wider">EMAIL</th>
                                        <th class="py-3.5 px-4 text-[0.72rem] font-bold text-[#2f6f45] dark:text-[#8fd0ab] uppercase tracking-wider">GUESTS</th>
                                        <th class="py-3.5 px-4 text-[0.72rem] font-bold text-[#2f6f45] dark:text-[#8fd0ab] uppercase tracking-wider">STATUS</th>
                                        <th class="py-3.5 px-4 text-[0.72rem] font-bold text-[#2f6f45] dark:text-[#8fd0ab] uppercase tracking-wider">CHECK-IN</th>
                                        <th class="py-3.5 px-4 text-[0.72rem] font-bold text-[#2f6f45] dark:text-[#8fd0ab] uppercase tracking-wider">CHECK-OUT</th>
                                        <th class="py-3.5 px-4 text-[0.72rem] font-bold text-[#2f6f45] dark:text-[#8fd0ab] uppercase tracking-wider text-right">AMOUNT PAID</th>
                                        <th class="py-3.5 px-4 text-right"></th>
                                    </tr>
                                </thead>
                                <tbody id="reservationTableBody" class="divide-y divide-[#e5e9e6] dark:divide-[#282c29] bg-white dark:bg-[#181b19]">
                                    @forelse ($checkedOutReservations as $reservation)
                                        @php
                                            $allGuestsCheckedOut = $reservation->reservationGuests->isNotEmpty() && $reservation->reservationGuests->every(fn ($g) => $g->checked_out_at !== null);
                                            $displayStatus = ($reservation->status === 'Checked Out' || $reservation->check_out || $allGuestsCheckedOut) ? 'Checked Out' : $reservation->status;
                                            $lastGuestCheckout = $reservation->reservationGuests->pluck('checked_out_at')->filter()->max();
                                            $displayCheckout = $reservation->check_out ?: $lastGuestCheckout;
                                            $bookerInitials = collect(explode(' ', trim($reservation->booker_name ?? '')))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';
                                        @endphp
                                        <tr
                                            class="reservation-row cursor-pointer select-none transition-colors hover:bg-[#f8faf9] dark:hover:bg-[#141715]"
                                            data-reservation-id="{{ $reservation->id }}"
                                            data-guest-count="{{ $reservation->number_of_guests ?? 1 }}"
                                            data-booker-name="{{ strtolower($reservation->booker_name ?? '') }}"
                                            data-email="{{ strtolower($reservation->email ?? '') }}"
                                            data-check-out="{{ $displayCheckout ?? '' }}"
                                            data-amount="{{ (float) ($reservation->amount_paid ?? 0) }}"
                                            data-search="{{ strtolower(trim(($reservation->booker_name ?? '') . ' ' . ($reservation->email ?? '') . ' ' . $reservation->id)) }}"
                                            tabindex="0"
                                            role="button"
                                        >
                                            <td class="py-3 px-4">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    @if($reservation->reservationGuests->count() > 0)
                                                        <button type="button" class="btn-expand-row flex h-6 w-6 shrink-0 cursor-pointer items-center justify-center rounded-full border border-[#dbe3de] dark:border-[#282c29] bg-[#f4f7f5] dark:bg-[#141715] text-[#5a6b5c] transition-all hover:bg-[#178a52] hover:text-white [&.expanded]:rotate-180 [&.expanded]:text-[#178a52]" data-expand-reservation="{{ $reservation->id }}" aria-label="Toggle Companions">
                                                            <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                                        </button>
                                                    @endif
                                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#178a52] to-[#0e5c37] text-xs font-bold text-white shadow-sm">{{ $bookerInitials }}</span>
                                                    <div class="min-w-0">
                                                        <div class="text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $reservation->booker_name }}</div>
                                                        <div class="text-[0.75rem] text-[#889b8a] mt-0.5">ID: {{ $reservation->id }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4 text-xs text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $reservation->email }}</td>
                                            <td class="py-3 px-4 text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $reservation->number_of_guests }}</td>
                                            <td class="py-3 px-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#eaf5ee] text-[#178a52] dark:bg-[#1e2220] dark:text-[#8fd0ab]">{{ $displayStatus }}</span>
                                            </td>
                                            <td class="py-3 px-4 text-xs text-[#5a6b5c] dark:text-[#a8b8a8]">{{ $reservation->check_in ? \Carbon\Carbon::parse($reservation->check_in)->format('M d, Y h:i A') : 'N/A' }}</td>
                                            <td class="py-3 px-4 text-xs text-[#5a6b5c] dark:text-[#a8b8a8]">{{ $displayCheckout ? \Carbon\Carbon::parse($displayCheckout)->format('M d, Y h:i A') : 'N/A' }}</td>
                                            <td class="py-3 px-4 text-xs text-right font-bold text-[#0d2c1d] dark:text-[#f5f5f0] tabular-nums">₱{{ number_format($reservation->amount_paid, 2) }}</td>
                                            <td class="py-3 px-4 text-right text-[#9ca3af]">
                                                <svg class="h-5 w-5 ml-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
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
                                                            'count' => 0,
                                                            'members' => [],
                                                        ];
                                                    }
                                                    $bulkGroupsForRes[$key]['count']++;
                                                    $bulkGroupsForRes[$key]['members'][] = [
                                                        'customer_id' => $customer->id,
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
                                                    class="companion-row companion-of-{{ $reservation->id }} cursor-pointer select-none bg-[#f8faf9] dark:bg-[#0e2418] hover:bg-[#f0f4f2] dark:hover:bg-[#1e2220] transition-colors"
                                                    data-bulk-group="true"
                                                    data-bulk-key="{{ $compRow['key'] }}"
                                                    data-bulk-name="{{ $compRow['name'] }}"
                                                    data-bulk-count="{{ $compRow['count'] }}"
                                                    data-bulk-age="{{ $compRow['age_group'] }}"
                                                    data-bulk-gender="{{ $compRow['gender'] }}"
                                                    data-bulk-nationality="{{ $compRow['nationality'] }}"
                                                    data-reservation-id="{{ $reservation->id }}"
                                                    data-bulk-members='@json($compRow['members'])'
                                                    tabindex="0"
                                                    role="button"
                                                    aria-label="View {{ $compRow['name'] }} group details"
                                                    style="display: none;"
                                                >
                                                    <td colspan="2" class="py-2.5 px-4 pl-12">
                                                        <div class="cell-person cell-person--companion flex items-center gap-3 min-w-0">
                                                            <span class="cell-person__avatar cell-person__avatar--bulk flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#178a52] text-white shadow-sm" title="Bulk Companion Group">
                                                                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" /><path d="M5.082 14.254a8.287 8.287 0 00-1.308 5.135 9.687 9.687 0 01-1.764-.44l-.115-.04a.563.563 0 01-.373-.487l-.01-.121a3.75 3.75 0 016.576-3.036 7.525 7.525 0 00-3.006-1.011zM18.918 14.254a8.287 8.287 0 011.308 5.135 9.687 9.687 0 001.764-.44l.115-.04a.563.563 0 00.373-.487l.01-.121a3.75 3.75 0 00-6.576-3.036 7.525 7.525 0 013.006-1.011z" /></svg>
                                                            </span>
                                                            <div class="cell-person__body min-w-0">
                                                                <div class="guest-name text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] flex items-center gap-1.5">
                                                                    <span>{{ $compRow['name'] }}</span>
                                                                    <span class="px-2 py-0.5 text-[0.65rem] font-bold rounded-full bg-[#e5e9e6] text-[#5a6b5c] dark:bg-[#282c29] dark:text-[#a8b8a8]">{{ $compRow['count'] }}x</span>
                                                                </div>
                                                                <div class="guest-meta text-[0.7rem] text-[#889b8a]">Bulk companion group</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="py-2.5 px-4 text-xs text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $compRow['age_group'] }}</td>
                                                    <td class="py-2.5 px-4"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $compRow['is_foreigner'] ? 'bg-[#eaf5ee] text-[#178a52]' : 'bg-gray-100 text-gray-700' }}">{{ $compRow['nationality'] }}</span></td>
                                                    <td colspan="4"></td>
                                                </tr>
                                            @else
                                                @php
                                                    $guest = $compRow['guest'];
                                                @endphp
                                                <tr
                                                    class="companion-row companion-of-{{ $reservation->id }} cursor-pointer select-none bg-[#f8faf9] dark:bg-[#0e2418] hover:bg-[#f0f4f2] dark:hover:bg-[#1e2220] transition-colors"
                                                    data-customer-id="{{ $guest->customer->id }}"
                                                    tabindex="0"
                                                    role="button"
                                                    aria-label="View details for {{ collect([$guest->customer->first_name, $guest->customer->middle_name, $guest->customer->last_name])->filter()->join(' ') }}"
                                                    style="display: none;"
                                                >
                                                    <td colspan="2" class="py-2.5 px-4 pl-12">
                                                        <div class="cell-person cell-person--companion flex items-center gap-3 min-w-0">
                                                            <span class="cell-person__avatar cell-person__avatar--companion flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#2f6f45] text-white shadow-sm" title="Companion">
                                                                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" /></svg>
                                                            </span>
                                                            <div class="cell-person__body min-w-0">
                                                                <div class="guest-name text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">{{ collect([$guest->customer->first_name, $guest->customer->middle_name, $guest->customer->last_name])->filter()->join(' ') }}</div>
                                                                <div class="guest-meta text-[0.7rem] text-[#889b8a]">ID: {{ $guest->customer->id }}</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="py-2.5 px-4 text-xs text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $guest->customer->age ?? 'N/A' }}</td>
                                                    <td class="py-2.5 px-4"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $guest->customer->is_foreigner ? 'bg-[#eaf5ee] text-[#178a52]' : 'bg-gray-100 text-gray-700' }}">{{ $guest->customer->is_foreigner ? 'Foreigner' : 'Filipino' }}</span></td>
                                                    <td colspan="4"></td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-4 py-8 text-center text-xs text-[#889b8a]">No checked-out reservations found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- RESERVATION TABLE PAGINATION --}}
                        <div class="records-pagination mt-4 border-t border-[#e5e9e6] dark:border-[#282c29] pt-4">
                            <div class="flex flex-wrap items-center justify-between gap-x-5 gap-y-3 text-xs">
                                <div class="flex items-center gap-2 text-[#5a6b5c] dark:text-[#a8b8a8]">
                                    <span>Showing</span>
                                    <select id="reservationPerPage" class="cursor-pointer rounded-lg border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-2 py-1 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-sm">
                                        <option value="5">5</option>
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                    <span>per page</span>
                                </div>
                                <span id="reservationResultsCount" class="font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">Showing 0 of 0 reservations</span>
                                <div class="flex items-center gap-1.5">
                                    <button type="button" id="reservationPrevPage" class="cursor-pointer rounded-lg border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-1.5 font-semibold text-[#0d2c1d] dark:text-[#f5f5f0] transition-colors hover:bg-[#f4f7f5] dark:hover:bg-[#141715] disabled:cursor-not-allowed disabled:opacity-40 shadow-sm">‹ Prev</button>
                                    <div id="reservationPageNumbers" class="flex items-center gap-1"></div>
                                    <button type="button" id="reservationNextPage" class="cursor-pointer rounded-lg border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-1.5 font-semibold text-[#0d2c1d] dark:text-[#f5f5f0] transition-colors hover:bg-[#f4f7f5] dark:hover:bg-[#141715] disabled:cursor-not-allowed disabled:opacity-40 shadow-sm">Next ›</button>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center gap-2 text-xs text-[#5a6b5c] dark:text-[#a8b8a8]">
                                <span>Go to page</span>
                                <input type="number" id="reservationPageInput" min="1" value="1" class="w-16 rounded-lg border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-2 py-1 text-xs text-[#0d2c1d] dark:text-[#f5f5f0] focus:border-[#178a52] focus:outline-none shadow-sm">
                                <button type="button" id="reservationGoPage" class="cursor-pointer rounded-lg bg-[#178a52] px-3 py-1 font-semibold text-white transition-colors hover:bg-[#126e41] border-0 shadow-sm">Go</button>
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
                            <h3 id="reservationModalTitle" class="guest-modal__title m-0 text-lg font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">Reservation Details</h3>
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
