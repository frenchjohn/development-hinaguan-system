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
        'resources/css/staff_css/staff_shared.css',
        'resources/css/homepage.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/staff_sidemenu.css',
        'resources/css/chatbot.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/staff_js/staff_records.js',
        'resources/js/staff_chatbot.js',
    ])
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

                <!-- SUMMARY STRIP -->
                <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="flex items-center gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-[0_4px_12px_rgba(0,0,0,0.03)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_8px_16px_rgba(0,0,0,0.05)]">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[rgba(46,125,85,0.1)] text-hp-green-mid">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </span>
                        <div>
                            <p class="m-0 mb-0.5 text-2xl font-bold leading-[1.2] text-hp-text">{{ $guestRecordsCount }}</p>
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.05em] text-hp-text-muted">Guest Records</p>
                        </div>
                    </article>
                    <article class="flex items-center gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-[0_4px_12px_rgba(0,0,0,0.03)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_8px_16px_rgba(0,0,0,0.05)]">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[rgba(46,125,85,0.1)] text-hp-green-mid">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </span>
                        <div>
                            <p class="m-0 mb-0.5 text-2xl font-bold leading-[1.2] text-hp-text">{{ $completedReservationsCount }}</p>
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.05em] text-hp-text-muted">Completed Reservations</p>
                        </div>
                    </article>
                    <article class="flex items-center gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-[0_4px_12px_rgba(0,0,0,0.03)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_8px_16px_rgba(0,0,0,0.05)]">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[rgba(46,125,85,0.1)] text-hp-green-mid">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div>
                            <p class="m-0 mb-0.5 text-2xl font-bold leading-[1.2] text-hp-text">₱{{ number_format($completedRevenue) }}</p>
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.05em] text-hp-text-muted">Revenue Collected</p>
                        </div>
                    </article>
                    <article class="flex items-center gap-4 rounded-2xl border border-glass-border bg-glass p-5 shadow-[0_4px_12px_rgba(0,0,0,0.03)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_8px_16px_rgba(0,0,0,0.05)]">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[rgba(46,125,85,0.1)] text-hp-green-mid">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </span>
                        <div>
                            <p class="m-0 mb-0.5 text-2xl font-bold leading-[1.2] text-hp-text">{{ $uniqueGuestsCount }}</p>
                            <p class="m-0 text-[0.7rem] font-bold uppercase tracking-[0.05em] text-hp-text-muted">Unique Visitors</p>
                        </div>
                    </article>
                </div>

                <!-- TAB BUTTONS -->
                <div class="mb-4 flex items-center gap-3">
                    <button type="button" class="records-tab-btn records-tab-btn--active cursor-pointer rounded-full border border-glass-border bg-glass px-6 py-2.5 text-sm font-semibold text-hp-text shadow-[0_2px_4px_rgba(0,0,0,0.02)] transition-all duration-200 hover:border-hp-green-mid hover:text-hp-green-mid hover:outline-none records-tab-btn--active:border-hp-green-dark records-tab-btn--active:bg-hp-green-dark records-tab-btn--active:text-white records-tab-btn--active:shadow-[0_4px_10px_rgba(20,64,43,0.2)] dark:bg-glass dark:hover:bg-[#2d5a32] dark:hover:border-[#4a8a52] dark:hover:text-[#c8e6c8] dark:records-tab-btn--active:bg-hp-green-mid dark:records-tab-btn--active:border-hp-green-mid" data-tab="guests" id="guestsTabBtn">
                        Guests
                    </button>
                    <button type="button" class="records-tab-btn cursor-pointer rounded-full border border-glass-border bg-glass px-6 py-2.5 text-sm font-semibold text-hp-text shadow-[0_2px_4px_rgba(0,0,0,0.02)] transition-all duration-200 hover:border-hp-green-mid hover:text-hp-green-mid hover:outline-none records-tab-btn--active:border-hp-green-dark records-tab-btn--active:bg-hp-green-dark records-tab-btn--active:text-white records-tab-btn--active:shadow-[0_4px_10px_rgba(20,64,43,0.2)] dark:bg-glass dark:hover:bg-[#2d5a32] dark:hover:border-[#4a8a52] dark:hover:text-[#c8e6c8] dark:records-tab-btn--active:bg-hp-green-mid dark:records-tab-btn--active:border-hp-green-mid" data-tab="reservations" id="reservationsTabBtn">
                        Reservations
                    </button>
                </div>

                <!-- GUESTS TABLE SECTION -->
                <section class="guest-panel rounded-2xl border border-glass-border bg-glass p-6 shadow-[0_2px_10px_rgba(0,0,0,0.02)]" data-tab-content="guests">
                    <div class="mb-6 flex items-center gap-4 border-b border-glass-border pb-6">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-hp-green-dark text-white">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                        <div>
                            <h3 class="m-0 text-lg text-hp-text">Checked-Out Guest Records</h3>
                            <p class="m-0 text-sm text-hp-text-muted">Guests who have completed their visit and checked out</p>
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 rounded-xl border border-glass-border bg-[rgba(26,58,31,0.15)] px-4 py-3 text-hp-green">{{ session('success') }}</div>
                    @endif

                    <div class="guest-filter-shell mb-3 grid gap-3">
                        <button type="button" class="guest-filter-toggle inline-flex w-fit cursor-pointer items-center justify-between gap-2.5 rounded-full border border-glass-border bg-glass px-4 py-2.5 font-semibold text-hp-text transition-all duration-200 hover:bg-hp-gold hover:text-hp-green-dark dark:border-glass-border dark:hover:bg-[#2d5a32] dark:hover:border-[#4a8a52] dark:hover:text-[#c8e6c8]" id="guestFilterToggle" aria-expanded="false" aria-controls="guestFilterPanel">
                            <span>Filters</span>
                            <span class="guest-filter-toggle__icon text-[0.95rem]">▾</span>
                        </button>
                        <div class="guest-toolbar guest-toolbar--collapsed grid items-end gap-3 rounded-[14px] border border-glass-border bg-hp-cream p-4 transition-colors duration-300 dark:bg-glass" id="guestFilterPanel" hidden>
                            <label class="guest-toolbar__field guest-toolbar__field--search grid gap-1.5 text-[0.82rem] font-semibold text-hp-text md:col-span-2">
                                <span>Search</span>
                                <input type="search" id="guestSearchInput" placeholder="Search by name, ID, gender" class="w-full rounded-[11px] border border-glass-border bg-glass px-3.5 py-2.5 text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
                            </label>
                            <label class="guest-toolbar__field grid gap-1.5 text-[0.82rem] font-semibold text-hp-text">
                                <span>Sort by</span>
                                <select id="guestSortSelect" class="w-full rounded-[11px] border border-glass-border bg-glass px-3.5 py-2.5 text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
                                    <option value="name-asc">Name (A-Z)</option>
                                    <option value="name-desc">Name (Z-A)</option>
                                    <option value="age-asc">Age (Low-High)</option>
                                    <option value="age-desc">Age (High-Low)</option>
                                    <option value="checkout-desc">Checkout (Newest)</option>
                                </select>
                            </label>
                            <label class="guest-toolbar__field grid gap-1.5 text-[0.82rem] font-semibold text-hp-text">
                                <span>Checked out from</span>
                                <input type="date" id="guestCheckOutFrom" class="w-full rounded-[11px] border border-glass-border bg-glass px-3.5 py-2.5 text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
                            </label>
                            <label class="guest-toolbar__field grid gap-1.5 text-[0.82rem] font-semibold text-hp-text">
                                <span>Checked out to</span>
                                <input type="date" id="guestCheckOutTo" class="w-full rounded-[11px] border border-glass-border bg-glass px-3.5 py-2.5 text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
                            </label>
                            <button type="button" class="guest-toolbar__clear cursor-pointer rounded-[11px] border-none bg-[rgba(13,44,29,0.1)] px-4 py-2.5 font-semibold text-hp-text transition-colors duration-200 hover:bg-hp-gold hover:text-hp-green-dark dark:bg-[#2d5a32] dark:text-[#c8e6c8]" id="guestFiltersClear">Clear</button>
                        </div>
                    </div>

                    <div class="guest-toolbar__meta mb-3.5 text-sm text-hp-text-muted">
                        <span id="guestResultsCount">Showing {{ $checkedOutGuests->count() }} records</span>
                    </div>

                    <div class="guest-table-wrap max-h-[440px] overflow-auto" id="guestTableWrap">
                        <table class="guest-table w-full min-w-[760px] border-collapse border-spacing-0 bg-transparent">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>Status</th>
                                    <th>Checked Out</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="guestTableBody">
                                @forelse ($checkedOutGuests as $guestEntry)
                                    @php
                                        $customer = $guestEntry->customer;
                                        $guestInitials = collect(explode(' ', trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';
                                    @endphp
                                    <tr
                                        class="guest-row cursor-pointer select-none transition-colors duration-200 hover:bg-hp-cream focus-visible:bg-hp-cream focus-visible:outline-none dark:hover:bg-[#2d5a32] dark:focus-visible:bg-[#2d5a32]"
                                        data-customer-id="{{ $customer->id }}"
                                        data-age="{{ $customer->age ?? 'N/A' }}"
                                        data-gender="{{ $customer->gender ?? 'N/A' }}"
                                        data-is-foreigner="{{ $customer->is_foreigner ? 'Foreigner' : 'Filipino' }}"
                                        data-checked-out="{{ $guestEntry->checked_out_at ?? '' }}"
                                        data-age-value="{{ is_numeric($customer->age) ? (int) $customer->age : 999999 }}"
                                        data-search="{{ strtolower(trim(($customer->first_name ?? '') . ' ' . ($customer->middle_name ?? '') . ' ' . ($customer->last_name ?? '') . ' ' . $customer->id . ' ' . ($customer->gender ?? '') . ' ' . ($customer->is_foreigner ? 'Foreigner' : 'Filipino'))) }}"
                                        tabindex="0"
                                        role="button"
                                        aria-label="View details for {{ trim(($customer->first_name ?? '') . ' ' . ($customer->middle_name ?? '') . ' ' . ($customer->last_name ?? '')) }}"
                                    >
                                        <td>
                                            <div class="cell-person flex min-w-0 items-center gap-3">
                                                <span class="cell-person__avatar flex h-[2.1rem] w-[2.1rem] shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#178a52] to-[#0e5c37] text-[0.66rem] font-bold tracking-[0.03em] text-white shadow-[inset_0_2px_3px_rgba(255,255,255,0.3),inset_0_-2px_4px_rgba(0,0,0,0.22),0_2px_6px_rgba(23,42,32,0.14)]">{{ $guestInitials }}</span>
                                                <div class="cell-person__body min-w-0">
                                                    <div class="guest-name font-semibold text-hp-text">{{ trim(($customer->first_name ?? '') . ' ' . ($customer->middle_name ?? '') . ' ' . ($customer->last_name ?? '')) }}</div>
                                                    <div class="guest-meta mt-0.5 text-[0.84rem] text-hp-text-muted">Customer ID: {{ $customer->id }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $customer->age ?? 'N/A' }}</td>
                                        <td>{{ $customer->gender ?? 'N/A' }}</td>
                                        <td>
                                            <span class="status-pill inline-flex items-center gap-1 whitespace-nowrap rounded-full border border-glass-border px-2.5 py-1 text-[0.7rem] font-bold tracking-[0.02em] shadow-[inset_0_1px_0_rgba(255,255,255,0.4)] dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.08)] {{ $customer->is_foreigner ? 'status-pill--confirmed bg-[#e7f3ec] text-[#0e5c37] dark:bg-[#1a3324] dark:text-[#6ab88c]' : 'status-pill--checked-out bg-[rgba(120,130,122,0.13)] text-hp-text-muted' }}">{{ $customer->is_foreigner ? 'Foreigner' : 'Filipino' }}</span>
                                        </td>
                                        <td class="mono-cell whitespace-nowrap text-[0.8rem] text-hp-text-muted">{{ $guestEntry->checked_out_at ? \Carbon\Carbon::parse($guestEntry->checked_out_at)->format('M d, Y h:i A') : 'N/A' }}</td>
                                        <td class="text-right text-[#9ca3af]">
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="guest-empty px-4 py-8 text-center text-hp-text-muted">No checked-out guest records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- RESERVATIONS TABLE SECTION -->
                <section class="guest-panel mt-8 rounded-2xl border border-glass-border bg-glass p-6 shadow-[0_2px_10px_rgba(0,0,0,0.02)]" data-tab-content="reservations" hidden>
                    <div class="mb-6 flex items-center gap-4 border-b border-glass-border pb-6">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-hp-green-dark text-white">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                        </div>
                        <div>
                            <h3 class="m-0 text-lg text-hp-text">Completed Reservations</h3>
                            <p class="m-0 text-sm text-hp-text-muted">Records of reservations that have been checked out</p>
                        </div>
                    </div>

                    <div class="guest-filter-shell mb-3 grid gap-3">
                        <button type="button" class="guest-filter-toggle inline-flex w-fit cursor-pointer items-center justify-between gap-2.5 rounded-full border border-glass-border bg-glass px-4 py-2.5 font-semibold text-hp-text transition-all duration-200 hover:bg-hp-gold hover:text-hp-green-dark dark:border-glass-border dark:hover:bg-[#2d5a32] dark:hover:border-[#4a8a52] dark:hover:text-[#c8e6c8]" id="reservationFilterToggle" aria-expanded="false" aria-controls="reservationFilterPanel">
                            <span>Filters</span>
                            <span class="guest-filter-toggle__icon text-[0.95rem]">▾</span>
                        </button>
                        <div class="guest-toolbar guest-toolbar--collapsed grid items-end gap-3 rounded-[14px] border border-glass-border bg-hp-cream p-4 transition-colors duration-300 dark:bg-glass" id="reservationFilterPanel" hidden>
                            <label class="guest-toolbar__field guest-toolbar__field--search grid gap-1.5 text-[0.82rem] font-semibold text-hp-text md:col-span-2">
                                <span>Search</span>
                                <input type="search" id="reservationSearchInput" placeholder="Search by booker name, email, ID" class="w-full rounded-[11px] border border-glass-border bg-glass px-3.5 py-2.5 text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
                            </label>
                            <label class="guest-toolbar__field grid gap-1.5 text-[0.82rem] font-semibold text-hp-text">
                                <span>Sort by</span>
                                <select id="reservationSortSelect" class="w-full rounded-[11px] border border-glass-border bg-glass px-3.5 py-2.5 text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
                                    <option value="date-desc">Checkout (Newest)</option>
                                    <option value="date-asc">Checkout (Oldest)</option>
                                    <option value="name-asc">Booker Name (A-Z)</option>
                                    <option value="name-desc">Booker Name (Z-A)</option>
                                    <option value="amount-desc">Amount (High to Low)</option>
                                </select>
                            </label>
                            <label class="guest-toolbar__field grid gap-1.5 text-[0.82rem] font-semibold text-hp-text">
                                <span>Checked out from</span>
                                <input type="date" id="reservationCheckOutFrom" class="w-full rounded-[11px] border border-glass-border bg-glass px-3.5 py-2.5 text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
                            </label>
                            <label class="guest-toolbar__field grid gap-1.5 text-[0.82rem] font-semibold text-hp-text">
                                <span>Checked out to</span>
                                <input type="date" id="reservationCheckOutTo" class="w-full rounded-[11px] border border-glass-border bg-glass px-3.5 py-2.5 text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
                            </label>
                            <button type="button" class="guest-toolbar__clear cursor-pointer rounded-[11px] border-none bg-[rgba(13,44,29,0.1)] px-4 py-2.5 font-semibold text-hp-text transition-colors duration-200 hover:bg-hp-gold hover:text-hp-green-dark dark:bg-[#2d5a32] dark:text-[#c8e6c8]" id="reservationFiltersClear">Clear</button>
                        </div>
                    </div>

                    <div class="guest-toolbar__meta mb-3.5 text-sm text-hp-text-muted">
                        <span id="reservationResultsCount">Showing {{ $checkedOutReservations->count() }} reservations</span>
                    </div>

                    <div class="guest-table-wrap max-h-[440px] overflow-auto" id="reservationTableWrap">
                        <table class="guest-table w-full min-w-[760px] border-collapse border-spacing-0 bg-transparent">
                            <thead>
                                <tr>
                                    <th>Booker Name</th>
                                    <th>Email</th>
                                    <th>Guests</th>
                                    <th>Status</th>
                                    <th>Check-In</th>
                                    <th>Check-Out</th>
                                    <th>Amount Paid</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="reservationTableBody">
                                @forelse ($checkedOutReservations as $reservation)
                                    <tr
                                        class="reservation-row cursor-pointer select-none transition-colors duration-200 hover:bg-hp-cream focus-visible:bg-hp-cream focus-visible:outline-none dark:hover:bg-[#2d5a32] dark:focus-visible:bg-[#2d5a32]"
                                        data-reservation-id="{{ $reservation->id }}"
                                        data-booker-name="{{ strtolower($reservation->booker_name ?? '') }}"
                                        data-email="{{ strtolower($reservation->email ?? '') }}"
                                        data-check-out="{{ $reservation->check_out ?? '' }}"
                                        data-amount="{{ (float) ($reservation->amount_paid ?? 0) }}"
                                        data-search="{{ strtolower(trim(($reservation->booker_name ?? '') . ' ' . ($reservation->email ?? '') . ' ' . $reservation->id)) }}"
                                        tabindex="0"
                                        role="button"
                                        aria-label="View details for {{ $reservation->booker_name }}"
                                    >
                                        <td>
                                            @php
                                                $bookerInitials = collect(explode(' ', trim($reservation->booker_name ?? '')))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';
                                            @endphp
                                            <div class="cell-person flex min-w-0 items-center gap-3">
                                                @if($reservation->reservationGuests->count() > 0)
                                                    <button type="button" class="btn-expand-row flex h-7 w-7 shrink-0 cursor-pointer items-center justify-center rounded-full border border-glass-border bg-glass text-hp-text-muted transition-all duration-200 hover:bg-hp-cream hover:text-hp-green dark:hover:bg-[#2d5a32] [&.expanded]:rotate-180 [&.expanded]:text-hp-green" data-expand-reservation="{{ $reservation->id }}" aria-label="Toggle Companions">
                                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                                    </button>
                                                @endif
                                                <span class="cell-person__avatar flex h-[2.1rem] w-[2.1rem] shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#178a52] to-[#0e5c37] text-[0.66rem] font-bold tracking-[0.03em] text-white shadow-[inset_0_2px_3px_rgba(255,255,255,0.3),inset_0_-2px_4px_rgba(0,0,0,0.22),0_2px_6px_rgba(23,42,32,0.14)]">{{ $bookerInitials }}</span>
                                                <div class="cell-person__body min-w-0">
                                                    <div class="guest-name font-semibold text-hp-text">{{ $reservation->booker_name }}</div>
                                                    <div class="guest-meta mt-0.5 text-[0.84rem] text-hp-text-muted">ID: {{ $reservation->id }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $reservation->email }}</td>
                                        <td>{{ $reservation->number_of_guests }}</td>
                                        <td>
                                            <span class="status-pill status-pill--{{ strtolower(str_replace(' ', '-', $reservation->status)) }} inline-flex items-center gap-1 whitespace-nowrap rounded-full border border-glass-border px-2.5 py-1 text-[0.7rem] font-bold tracking-[0.02em] shadow-[inset_0_1px_0_rgba(255,255,255,0.4)] dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.08)]">{{ $reservation->status }}</span>
                                        </td>
                                        <td class="mono-cell whitespace-nowrap text-[0.8rem] text-hp-text-muted">{{ $reservation->check_in ? \Carbon\Carbon::parse($reservation->check_in)->format('M d, Y h:i A') : 'N/A' }}</td>
                                        <td class="mono-cell whitespace-nowrap text-[0.8rem] text-hp-text-muted">{{ $reservation->check_out ? \Carbon\Carbon::parse($reservation->check_out)->format('M d, Y h:i A') : 'N/A' }}</td>
                                        <td class="num-cell whitespace-nowrap text-right font-semibold tabular-nums">₱{{ number_format($reservation->amount_paid, 2) }}</td>
                                        <td class="text-right text-[#9ca3af]">
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
                                        </td>
                                    </tr>

                                    {{-- COMPANION ROWS --}}
                                    @foreach ($reservation->reservationGuests as $guest)
                                        @if(!$guest->is_primary_guest && $guest->customer)
                                            <tr class="companion-row companion-of-{{ $reservation->id }} hover:bg-hp-cream dark:hover:bg-[#2d5a32]" style="display: none;">
                                                <td colspan="2">
                                                    <div class="cell-person cell-person--companion flex min-w-0 items-center gap-3">
                                                        <span class="cell-person__avatar flex h-[2.1rem] w-[2.1rem] shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#178a52] to-[#0e5c37] text-[0.66rem] font-bold tracking-[0.03em] text-white shadow-[inset_0_2px_3px_rgba(255,255,255,0.3),inset_0_-2px_4px_rgba(0,0,0,0.22),0_2px_6px_rgba(23,42,32,0.14)]" title="Companion">
                                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" /></svg>
                                                        </span>
                                                        <div class="cell-person__body min-w-0">
                                                            <div class="guest-name font-semibold text-hp-text">{{ trim(($guest->customer->first_name ?? '') . ' ' . ($guest->customer->middle_name ?? '') . ' ' . ($guest->customer->last_name ?? '')) }}</div>
                                                            <div class="guest-meta mt-0.5 text-[0.84rem] text-hp-text-muted">ID: {{ $guest->customer->id }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $guest->customer->age ?? 'N/A' }}</td>
                                                <td><span class="status-pill inline-flex items-center gap-1 whitespace-nowrap rounded-full border border-glass-border px-2.5 py-1 text-[0.7rem] font-bold tracking-[0.02em] shadow-[inset_0_1px_0_rgba(255,255,255,0.4)] dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.08)] {{ $guest->customer->is_foreigner ? 'bg-[#e7f3ec] text-[#0e5c37] dark:bg-[#1a3324] dark:text-[#6ab88c]' : 'bg-[rgba(120,130,122,0.13)] text-hp-text-muted' }}">{{ $guest->customer->is_foreigner ? 'Foreigner' : 'Filipino' }}</span></td>
                                                <td colspan="4"></td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="8" class="guest-empty px-4 py-8 text-center text-hp-text-muted">No checked-out reservations found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="guest-modal fixed inset-0 z-[1000] hidden items-center justify-center is-open:flex" id="guestModal" aria-hidden="true">
                    <div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-modal="true"></div>
                    <div class="guest-modal__content relative z-[1] w-full max-w-[720px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass" role="dialog" aria-modal="true" aria-labelledby="guestModalTitle">
                        <button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-modal="true" aria-label="Close details">&times;</button>
                        <div class="guest-modal__header mb-4 flex items-center gap-3">
                            <h3 id="guestModalTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Guest Details</h3>
                        </div>
                        <div id="guestModalBody" class="guest-modal__body grid gap-4"></div>
                    </div>
                </div>

                <div class="guest-modal fixed inset-0 z-[1000] hidden items-center justify-center is-open:flex" id="reservationModal" aria-hidden="true">
                    <div class="guest-modal__backdrop absolute inset-0 bg-[rgba(13,44,29,0.55)]" data-close-reservation-modal="true"></div>
                    <div class="guest-modal__content relative z-[1] w-full max-w-[720px] max-h-[min(84vh,760px)] overflow-y-auto rounded-2xl bg-glass p-6 shadow-glass" role="dialog" aria-modal="true" aria-labelledby="reservationModalTitle">
                        <button type="button" class="guest-modal__close absolute right-3 top-3 cursor-pointer border-0 bg-transparent text-2xl text-hp-text" data-close-reservation-modal="true" aria-label="Close details">&times;</button>
                        <div class="guest-modal__header mb-4 flex items-center gap-3">
                            <h3 id="reservationModalTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Reservation Details</h3>
                        </div>
                        <div id="reservationModalBody" class="guest-modal__body grid gap-4"></div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <x-staff_chatbot />

    <script>
        window.staffGuestData = @json($guestData ?? []);
        window.staffReservationData = @json($reservationData ?? []);
    </script>
</body>
</html>
