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

                <!-- SUMMARY STRIP (metric cards with colored icon circles + bottom accent bar) -->
                <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="relative overflow-hidden rounded-2xl border border-glass-border bg-glass p-5 shadow-[0_4px_12px_rgba(0,0,0,0.03)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_8px_16px_rgba(0,0,0,0.05)]">
                        <span class="absolute inset-x-0 bottom-0 h-1 bg-hp-green-dark"></span>
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#e7f3ec] text-hp-green-dark dark:bg-[#1a3324] dark:text-[#6ab88c]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                        </span>
                        <p class="m-0 mt-3 text-2xl font-bold leading-[1.2] text-hp-text">{{ $guestRecordsCount }}</p>
                        <p class="m-0 text-[0.78rem] font-bold text-hp-text">Guest Records</p>
                        <p class="m-0 mt-0.5 text-[0.7rem] text-hp-text-muted">Total checked-out guests</p>
                    </article>
                    <article class="relative overflow-hidden rounded-2xl border border-glass-border bg-glass p-5 shadow-[0_4px_12px_rgba(0,0,0,0.03)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_8px_16px_rgba(0,0,0,0.05)]">
                        <span class="absolute inset-x-0 bottom-0 h-1 bg-[#3b82f6]"></span>
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#e8f0fe] text-[#2563eb] dark:bg-[#1b2a45] dark:text-[#7da7f0]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </span>
                        <p class="m-0 mt-3 text-2xl font-bold leading-[1.2] text-hp-text">{{ $completedReservationsCount }}</p>
                        <p class="m-0 text-[0.78rem] font-bold text-hp-text">Completed Reservations</p>
                        <p class="m-0 mt-0.5 text-[0.7rem] text-hp-text-muted">Reservations completed</p>
                    </article>
                    <article class="relative overflow-hidden rounded-2xl border border-glass-border bg-glass p-5 shadow-[0_4px_12px_rgba(0,0,0,0.03)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_8px_16px_rgba(0,0,0,0.05)]">
                        <span class="absolute inset-x-0 bottom-0 h-1 bg-[#f59e0b]"></span>
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#fef3c7] text-[#b45309] dark:bg-[#3a2f14] dark:text-[#e5c35c]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <p class="m-0 mt-3 text-2xl font-bold leading-[1.2] text-hp-text">₱{{ number_format($completedRevenue) }}</p>
                        <p class="m-0 text-[0.78rem] font-bold text-hp-text">Revenue Collected</p>
                        <p class="m-0 mt-0.5 text-[0.7rem] text-hp-text-muted">From completed stays</p>
                    </article>
                    <article class="relative overflow-hidden rounded-2xl border border-glass-border bg-glass p-5 shadow-[0_4px_12px_rgba(0,0,0,0.03)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_8px_16px_rgba(0,0,0,0.05)]">
                        <span class="absolute inset-x-0 bottom-0 h-1 bg-[#8b5cf6]"></span>
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#f1eafd] text-[#7c3aed] dark:bg-[#2b2142] dark:text-[#b79df0]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </span>
                        <p class="m-0 mt-3 text-2xl font-bold leading-[1.2] text-hp-text">{{ $uniqueGuestsCount }}</p>
                        <p class="m-0 text-[0.78rem] font-bold text-hp-text">Unique Visitors</p>
                        <p class="m-0 mt-0.5 text-[0.7rem] text-hp-text-muted">Different guests visited</p>
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


                        {{-- CHECKED-OUT GUEST RECORDS TABLE --}}
                        <div class="min-w-0">
                            <section data-tab-content="guests">
                                <div class="guest-panel my-6 rounded-2xl border border-glass-border bg-glass p-6 sm:p-8 shadow-[0_4px_16px_rgba(0,0,0,0.04)]">
                            <div class="mb-4 flex flex-wrap items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-hp-green-dark text-white">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="m-0 text-base font-bold text-hp-text">Checked-Out Guest Records</h3>
                                    <p class="m-0 text-[0.8rem] text-hp-text-muted">Guests who have completed their visit and checked out</p>
                                </div>
                                <div class="ml-auto flex flex-wrap items-center gap-2">
                                    <div class="relative">
                                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-hp-text-muted/60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                                        <input type="search" id="guestSearchInput" placeholder="Search guest, ID, or reservation..." class="w-56 rounded-full border border-glass-border bg-glass py-2 pl-9 pr-3 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
                                    </div>
                                    <button type="button" class="guest-filter-toggle inline-flex cursor-pointer items-center justify-between gap-2 rounded-full border border-glass-border bg-glass px-4 py-2 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-hp-gold hover:text-hp-green-dark dark:border-glass-border dark:hover:bg-[#2d5a32] dark:hover:border-[#4a8a52] dark:hover:text-[#c8e6c8]" id="guestFilterToggle" aria-expanded="false" aria-controls="guestFilterPanel">
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/></svg>
                                        Filters
                                        <span class="guest-filter-toggle__icon text-[0.85rem]">▾</span>
                                    </button>
                                </div>
                            </div>

                            @if (session('success'))
                                <div class="mb-4 rounded-xl border border-glass-border bg-[rgba(26,58,31,0.15)] px-4 py-3 text-hp-green">{{ session('success') }}</div>
                            @endif

                            <div class="guest-filter-shell mb-3 grid gap-3">
                                <div class="guest-toolbar guest-toolbar--collapsed grid items-end gap-3 rounded-[14px] border border-glass-border bg-hp-cream p-4 transition-colors duration-300 dark:bg-glass" id="guestFilterPanel" hidden>
                                    <label class="guest-toolbar__field grid gap-1.5 text-[0.82rem] font-semibold text-hp-text">
                                        <span>Sort by</span>
                                        <select id="guestSortSelect" class="w-full rounded-[11px] border border-glass-border bg-glass px-3.5 py-2.5 text-hp-text transition-colors duration-300 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
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
                                            <option value="checkout-asc">Checkout (Oldest)</option>
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

                            <div class="guest-table-wrap overflow-auto my-4 p-4" id="guestTableWrap">
                                <table class="guest-table w-full min-w-[860px] border-collapse border-spacing-0 bg-transparent">
                                    <thead>
                                        <tr>
                                            <th class="sortable" data-sort="name">Guest / Group</th>
                                            <th class="sortable" data-sort="customer-id">Customer ID</th>
                                            <th class="sortable" data-sort="reservation">Reservation</th>
                                            <th class="sortable" data-sort="age">Age</th>
                                            <th class="sortable" data-sort="gender">Gender</th>
                                            <th class="sortable" data-sort="nationality">Nationality</th>
                                            <th class="sortable" data-sort="status">Status</th>
                                            <th class="sortable" data-sort="checked-out">Checked Out</th>
                                            <th class="text-right"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="guestTableBody">
                                        @php
                                            $recordsStatusClasses = [
                                                'pending' => 'bg-[#fef3c7] text-[#b45309] dark:bg-[#3a2f14] dark:text-[#e5c35c]',
                                                'confirmed' => 'bg-[#e8f0fe] text-[#2563eb] dark:bg-[#1b2a45] dark:text-[#7da7f0]',
                                                'checked-in' => 'bg-[#e7f3ec] text-[#0e5c37] dark:bg-[#1a3324] dark:text-[#6ab88c]',
                                                'checked-out' => 'bg-[rgba(120,130,122,0.13)] text-hp-text-muted',
                                                'cancelled' => 'bg-[#fde8e8] text-[#b91c1c] dark:bg-[#3a1f1c] dark:text-[#f3a0a0]',
                                            ];
                                        @endphp
                                        @forelse ($guestRows as $row)
                                            @if (($row['type'] ?? '') === 'bulk')
                                                @php
                                                    $group = $row['group'];
                                                    $bulkStatus = $group['status'] ?? 'Checked Out';
                                                    $bulkStatusClass = $recordsStatusClasses[strtolower(str_replace(' ', '-', $bulkStatus))] ?? 'bg-[rgba(120,130,122,0.13)] text-hp-text-muted';
                                                @endphp
                                                <tr
                                                    class="guest-row guest-row--bulk-group cursor-pointer select-none transition-colors duration-200 hover:bg-hp-cream focus-visible:bg-hp-cream focus-visible:outline-none dark:hover:bg-[#2d5a32] dark:focus-visible:bg-[#2d5a32]"
                                                    data-bulk-group="true"
                                                    data-bulk-key="{{ $group['key'] }}"
                                                    data-gender="{{ $group['gender'] }}"
                                                    data-nationality="{{ $group['nationality'] }}"
                                                    data-status="{{ $bulkStatus }}"
                                                    data-reservation-id="{{ $group['reservation_id'] }}"
                                                    data-age-value="999999"
                                                    data-checked-out="{{ $group['checked_out_at'] ?? '' }}"
                                                    data-search="{{ strtolower(trim(($group['name'] ?? '') . ' ' . $group['reservation_id'] . ' ' . $group['gender'] . ' ' . $group['age_group'] . ' ' . $group['nationality'] . ' ' . $bulkStatus . ' ' . $group['count'] . ' bulk companion group')) }}"
                                                    tabindex="0"
                                                    role="button"
                                                    aria-label="View bulk companion group details"
                                                >
                                                    <td>
                                                        <div class="cell-person flex min-w-0 items-center gap-3">
                                                            <span class="cell-person__avatar cell-person__avatar--bulk flex h-[2.1rem] w-[2.1rem] shrink-0 items-center justify-center rounded-full text-white shadow-[inset_0_2px_3px_rgba(255,255,255,0.3),inset_0_-2px_4px_rgba(0,0,0,0.22),0_2px_6px_rgba(23,42,32,0.14)]" title="Bulk Companion Group">
                                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" /><path d="M5.082 14.254a8.287 8.287 0 00-1.308 5.135 9.687 9.687 0 01-1.764-.44l-.115-.04a.563.563 0 01-.373-.487l-.01-.121a3.75 3.75 0 016.576-3.036 7.525 7.525 0 00-3.006-1.011zM18.918 14.254a8.287 8.287 0 011.308 5.135 9.687 9.687 0 001.764-.44l.115-.04a.563.563 0 00.373-.487l.01-.121a3.75 3.75 0 00-6.576-3.036 7.525 7.525 0 013.006-1.011z" /></svg>
                                                            </span>
                                                            <div class="cell-person__body min-w-0">
                                                                <div class="guest-name font-semibold text-hp-text">
                                                                    {{ $group['name'] }}
                                                                    <span class="guest-companion-count ml-1.5 inline-flex items-center gap-1 rounded-full bg-hp-green/10 px-2 py-0.5 align-middle text-[0.65rem] font-bold text-hp-green dark:bg-hp-green/25 dark:text-[#6ab88c]">{{ $group['count'] }}x</span>
                                                                </div>
                                                                <div class="guest-meta mt-0.5 text-[0.84rem] text-hp-text-muted">Bulk companion group</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-hp-text-muted">—</td>
                                                    <td>
                                                        <span class="inline-flex items-center whitespace-nowrap rounded-full bg-[rgba(120,130,122,0.13)] px-2 py-0.5 text-[0.65rem] font-bold text-hp-text-muted">Reservation #{{ $group['reservation_id'] }}</span>
                                                    </td>
                                                    <td>{{ $group['age_group'] }}</td>
                                                    <td>{{ $group['gender'] }}</td>
                                                    <td class="whitespace-nowrap text-[0.84rem] font-medium text-hp-text">{{ $group['nationality'] }}</td>
                                                    <td>
                                                        <span class="status-pill inline-flex items-center gap-1 whitespace-nowrap rounded-full border border-glass-border px-2.5 py-1 text-[0.7rem] font-bold tracking-[0.02em] shadow-[inset_0_1px_0_rgba(255,255,255,0.4)] dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.08)] {{ $bulkStatusClass }}">{{ $bulkStatus }}</span>
                                                    </td>
                                                    <td class="mono-cell whitespace-nowrap text-[0.8rem] text-hp-text-muted">
                                                        @if ($group['checked_out_at'])
                                                            <div>{{ \Carbon\Carbon::parse($group['checked_out_at'])->format('M d, Y') }}</div>
                                                            <div>{{ \Carbon\Carbon::parse($group['checked_out_at'])->format('h:i A') }}</div>
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                    <td class="text-right text-[#9ca3af]">
                                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
                                                    </td>
                                                </tr>
                                            @else
                                                @php
                                                    $guestEntry = $row['entry'];
                                                    $customer = $guestEntry->customer;
                                                    $guestInitials = collect(explode(' ', trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';
                                                    $isPrimaryGuest = (bool) ($guestEntry->is_primary_guest ?? false);
                                                    $resStatus = $guestEntry->reservation?->status ?? 'Checked Out';
                                                    $guestStatusClass = $recordsStatusClasses[strtolower(str_replace(' ', '-', $resStatus))] ?? 'bg-[rgba(120,130,122,0.13)] text-hp-text-muted';
                                                    $guestTypeLabel = ($guestEntry->reservation?->reservation_type ?? '') === 'walk_in' ? 'Walk-in Guest' : 'Online Guest';
                                                @endphp
                                                <tr
                                                    class="guest-row cursor-pointer select-none transition-colors duration-200 hover:bg-hp-cream focus-visible:bg-hp-cream focus-visible:outline-none dark:hover:bg-[#2d5a32] dark:focus-visible:bg-[#2d5a32]"
                                                    data-customer-id="{{ $customer->id }}"
                                                    data-age="{{ $customer->age ?? 'N/A' }}"
                                                    data-gender="{{ $customer->gender ?? 'N/A' }}"
                                                    data-nationality="{{ $customer->is_foreigner ? 'Foreigner' : 'Filipino' }}"
                                                    data-status="{{ $resStatus }}"
                                                    data-reservation-id="{{ $guestEntry->reservation_id ?? '' }}"
                                                    data-is-foreigner="{{ $customer->is_foreigner ? 'Foreigner' : 'Filipino' }}"
                                                    data-checked-out="{{ $guestEntry->checked_out_at ?? '' }}"
                                                    data-age-value="{{ is_numeric($customer->age) ? (int) $customer->age : 999999 }}"
                                                    data-search="{{ strtolower(trim(($customer->first_name ?? '') . ' ' . ($customer->middle_name ?? '') . ' ' . ($customer->last_name ?? '') . ' ' . $customer->id . ' ' . ($customer->gender ?? '') . ' ' . ($customer->is_foreigner ? 'Foreigner' : 'Filipino') . ' ' . $resStatus . ' ' . $guestTypeLabel . ' ' . ($guestEntry->reservation_id ?? ''))) }}"
                                                    tabindex="0"
                                                    role="button"
                                                    aria-label="View details for {{ trim(($customer->first_name ?? '') . ' ' . ($customer->middle_name ?? '') . ' ' . ($customer->last_name ?? '')) }}"
                                                >
                                                    <td>
                                                        <div class="cell-person flex min-w-0 items-center gap-3">
                                                            <span class="cell-person__avatar {{ $isPrimaryGuest ? 'cell-person__avatar--main' : 'cell-person__avatar--companion' }} flex h-[2.1rem] w-[2.1rem] shrink-0 items-center justify-center rounded-full text-white shadow-[inset_0_2px_3px_rgba(255,255,255,0.3),inset_0_-2px_4px_rgba(0,0,0,0.22),0_2px_6px_rgba(23,42,32,0.14)]" title="{{ $isPrimaryGuest ? 'Main Guest' : 'Single Companion' }}">
                                                                @if ($isPrimaryGuest)
                                                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" /></svg>
                                                                @else
                                                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" /></svg>
                                                                @endif
                                                            </span>
                                                            <div class="cell-person__body min-w-0">
                                                                <div class="guest-name font-semibold text-hp-text">{{ collect([$customer->first_name, $customer->middle_name, $customer->last_name])->filter()->join(' ') }}</div>
                                                                <div class="guest-meta mt-0.5 text-[0.84rem] text-hp-text-muted">{{ $guestTypeLabel }}</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="tabular-nums text-hp-text">{{ $customer->id }}</td>
                                                    <td>
                                                        @if ($guestEntry->reservation_id)
                                                            <span class="inline-flex items-center whitespace-nowrap rounded-full bg-[rgba(120,130,122,0.13)] px-2 py-0.5 text-[0.65rem] font-bold text-hp-text-muted">Reservation #{{ $guestEntry->reservation_id }}</span>
                                                        @else
                                                            <span class="text-hp-text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $customer->age ?? 'N/A' }}</td>
                                                    <td>{{ $customer->gender ?? 'N/A' }}</td>
                                                    <td class="whitespace-nowrap text-[0.84rem] font-medium text-hp-text">{{ $customer->is_foreigner ? 'Foreigner' : 'Filipino' }}</td>
                                                    <td>
                                                        <span class="status-pill inline-flex items-center gap-1 whitespace-nowrap rounded-full border border-glass-border px-2.5 py-1 text-[0.7rem] font-bold tracking-[0.02em] shadow-[inset_0_1px_0_rgba(255,255,255,0.4)] dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.08)] {{ $guestStatusClass }}">{{ $resStatus }}</span>
                                                    </td>
                                                    <td class="mono-cell whitespace-nowrap text-[0.8rem] text-hp-text-muted">
                                                        @if ($guestEntry->checked_out_at)
                                                            <div>{{ \Carbon\Carbon::parse($guestEntry->checked_out_at)->format('M d, Y') }}</div>
                                                            <div>{{ \Carbon\Carbon::parse($guestEntry->checked_out_at)->format('h:i A') }}</div>
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                    <td class="text-right text-[#9ca3af]">
                                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
                                                    </td>
                                                </tr>
                                            @endif
                                        @empty
                                            <tr>
                                                <td colspan="9" class="guest-empty px-4 py-8 text-center text-hp-text-muted">No checked-out guest records found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- GUEST TABLE PAGINATION --}}
                            <div class="records-pagination mt-4 border-t border-glass-border pt-4">
                                <div class="flex flex-wrap items-center justify-between gap-x-5 gap-y-3">
                                    <div class="flex items-center gap-2 text-sm text-hp-text-muted">
                                        <span>Showing</span>
                                    <select id="guestPerPage" class="cursor-pointer rounded-lg border border-glass-border bg-glass px-2 py-1.5 text-sm text-hp-text focus:border-hp-green focus:outline-none dark:bg-[#0d2812] dark:text-[#c8e6c8]">
                                        <option value="5">5</option>
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                        <span>per page</span>
                                    </div>
                                    <span id="guestResultsCount" class="text-sm font-semibold text-hp-text">Showing 0 of 0 records</span>
                                    <div class="flex items-center gap-1.5">
                                        <button type="button" id="guestPrevPage" class="cursor-pointer rounded-lg border border-glass-border bg-glass px-3 py-1.5 text-sm font-semibold text-hp-text transition-colors duration-200 hover:bg-glass-hover disabled:cursor-not-allowed disabled:opacity-40">‹ Prev</button>
                                        <div id="guestPageNumbers" class="flex items-center gap-1"></div>
                                        <button type="button" id="guestNextPage" class="cursor-pointer rounded-lg border border-glass-border bg-glass px-3 py-1.5 text-sm font-semibold text-hp-text transition-colors duration-200 hover:bg-glass-hover disabled:cursor-not-allowed disabled:opacity-40">Next ›</button>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center gap-2 text-sm text-hp-text-muted">
                                    <span>Go to page</span>
                                    <input type="number" id="guestPageInput" min="1" value="1" class="w-16 rounded-lg border border-glass-border bg-glass px-2 py-1.5 text-sm text-hp-text focus:border-hp-green focus:outline-none dark:bg-[#0d2812] dark:text-[#c8e6c8]">
                                    <button type="button" id="guestGoPage" class="cursor-pointer rounded-lg bg-hp-green px-3 py-1.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark">Go</button>
                                </div>
                            </div>
                            </div>
                        </section>

                        <!-- RESERVATIONS TAB SECTION -->
                        <section class="guest-panel my-6 rounded-2xl border border-glass-border bg-glass p-6 sm:p-8 shadow-[0_4px_16px_rgba(0,0,0,0.04)]" data-tab-content="reservations" hidden>
                    <div class="mb-4 flex flex-wrap items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-hp-green-dark text-white">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="m-0 text-base font-bold text-hp-text">Completed Reservations</h3>
                            <p class="m-0 text-[0.8rem] text-hp-text-muted">Records of reservations that have been checked out</p>
                        </div>
                        <div class="ml-auto flex flex-wrap items-center gap-2">
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-hp-text-muted/60" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                                <input type="search" id="reservationSearchInput" placeholder="Search booker, email, or ID..." class="w-56 rounded-full border border-glass-border bg-glass py-2 pl-9 pr-3 text-sm text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:border-glass-border dark:bg-[#0d2812] dark:text-[#c8e6c8]">
                            </div>
                            <button type="button" class="guest-filter-toggle inline-flex cursor-pointer items-center justify-between gap-2 rounded-full border border-glass-border bg-glass px-4 py-2 text-sm font-semibold text-hp-text transition-all duration-200 hover:bg-hp-gold hover:text-hp-green-dark dark:border-glass-border dark:hover:bg-[#2d5a32] dark:hover:border-[#4a8a52] dark:hover:text-[#c8e6c8]" id="reservationFilterToggle" aria-expanded="false" aria-controls="reservationFilterPanel">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z"/></svg>
                                Filters
                                <span class="guest-filter-toggle__icon text-[0.85rem]">▾</span>
                            </button>
                        </div>
                    </div>

                    <div class="guest-filter-shell mb-3 grid gap-3">
                        <div class="guest-toolbar guest-toolbar--collapsed grid items-end gap-3 rounded-[14px] border border-glass-border bg-hp-cream p-4 transition-colors duration-300 dark:bg-glass" id="reservationFilterPanel" hidden>
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

                    <div class="guest-table-wrap overflow-auto my-4 p-4" id="reservationTableWrap">
                        <table class="guest-table w-full min-w-[860px] border-collapse border-spacing-0 bg-transparent">
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
                                            @php
                                                $allGuestsCheckedOut = $reservation->reservationGuests->isNotEmpty() && $reservation->reservationGuests->every(fn ($g) => $g->checked_out_at !== null);
                                                $displayStatus = ($reservation->status === 'Checked Out' || $reservation->check_out || $allGuestsCheckedOut) ? 'Checked Out' : $reservation->status;
                                                $lastGuestCheckout = $reservation->reservationGuests->pluck('checked_out_at')->filter()->max();
                                                $displayCheckout = $reservation->check_out ?: $lastGuestCheckout;
                                            @endphp
                                            <span class="status-pill status-pill--{{ strtolower(str_replace(' ', '-', $displayStatus)) }} inline-flex items-center gap-1 whitespace-nowrap rounded-full border border-glass-border px-2.5 py-1 text-[0.7rem] font-bold tracking-[0.02em] shadow-[inset_0_1px_0_rgba(255,255,255,0.4)] dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.08)]">{{ $displayStatus }}</span>
                                        </td>
                                        <td class="mono-cell whitespace-nowrap text-[0.8rem] text-hp-text-muted">{{ $reservation->check_in ? \Carbon\Carbon::parse($reservation->check_in)->format('M d, Y h:i A') : 'N/A' }}</td>
                                        <td class="mono-cell whitespace-nowrap text-[0.8rem] text-hp-text-muted">{{ $displayCheckout ? \Carbon\Carbon::parse($displayCheckout)->format('M d, Y h:i A') : 'N/A' }}</td>
                                        <td class="num-cell whitespace-nowrap text-right font-semibold tabular-nums">₱{{ number_format($reservation->amount_paid, 2) }}</td>
                                        <td class="text-right text-[#9ca3af]">
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
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
                                                $nationality = $customer->is_foreigner ? 'Foreigner' : 'Filipino';
                                                $key = $gender . '|' . $ageGroup . '|' . $nationality;

                                                if (! isset($bulkGroupsForRes[$key])) {
                                                    $bulkGroupsForRes[$key] = [
                                                        'type' => 'bulk',
                                                        'name' => 'Bulk Companions',
                                                        'age_group' => $ageGroup,
                                                        'gender' => $gender,
                                                        'nationality' => $nationality,
                                                        'is_foreigner' => (bool) $customer->is_foreigner,
                                                        'count' => 0,
                                                    ];
                                                }
                                                $bulkGroupsForRes[$key]['count']++;
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
                                            <tr class="companion-row companion-of-{{ $reservation->id }} hover:bg-hp-cream dark:hover:bg-[#2d5a32]" style="display: none;">
                                                <td colspan="2">
                                                    <div class="cell-person cell-person--companion flex min-w-0 items-center gap-3">
                                                        <span class="cell-person__avatar cell-person__avatar--bulk flex h-[2.1rem] w-[2.1rem] shrink-0 items-center justify-center rounded-full text-white shadow-[inset_0_2px_3px_rgba(255,255,255,0.3),inset_0_-2px_4px_rgba(0,0,0,0.22),0_2px_6px_rgba(23,42,32,0.14)]" title="Bulk Companion Group">
                                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" /><path d="M5.082 14.254a8.287 8.287 0 00-1.308 5.135 9.687 9.687 0 01-1.764-.44l-.115-.04a.563.563 0 01-.373-.487l-.01-.121a3.75 3.75 0 016.576-3.036 7.525 7.525 0 00-3.006-1.011zM18.918 14.254a8.287 8.287 0 011.308 5.135 9.687 9.687 0 001.764-.44l.115-.04a.563.563 0 00.373-.487l.01-.121a3.75 3.75 0 00-6.576-3.036 7.525 7.525 0 013.006-1.011z" /></svg>
                                                        </span>
                                                        <div class="cell-person__body min-w-0">
                                                            <div class="guest-name font-semibold text-hp-text">
                                                                {{ $compRow['name'] }}
                                                                <span class="guest-companion-count ml-1.5 inline-flex items-center gap-1 rounded-full bg-hp-green/10 px-2 py-0.5 align-middle text-[0.65rem] font-bold text-hp-green dark:bg-hp-green/25 dark:text-[#6ab88c]">{{ $compRow['count'] }}x</span>
                                                            </div>
                                                            <div class="guest-meta mt-0.5 text-[0.84rem] text-hp-text-muted">Bulk companion group</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $compRow['age_group'] }}</td>
                                                <td><span class="status-pill inline-flex items-center gap-1 whitespace-nowrap rounded-full border border-glass-border px-2.5 py-1 text-[0.7rem] font-bold tracking-[0.02em] shadow-[inset_0_1px_0_rgba(255,255,255,0.4)] dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.08)] {{ $compRow['is_foreigner'] ? 'bg-[#e7f3ec] text-[#0e5c37] dark:bg-[#1a3324] dark:text-[#6ab88c]' : 'bg-[rgba(120,130,122,0.13)] text-hp-text-muted' }}">{{ $compRow['nationality'] }}</span></td>
                                                <td colspan="4"></td>
                                            </tr>
                                        @else
                                            @php
                                                $guest = $compRow['guest'];
                                            @endphp
                                            <tr class="companion-row companion-of-{{ $reservation->id }} hover:bg-hp-cream dark:hover:bg-[#2d5a32]" style="display: none;">
                                                <td colspan="2">
                                                    <div class="cell-person cell-person--companion flex min-w-0 items-center gap-3">
                                                        <span class="cell-person__avatar cell-person__avatar--companion flex h-[2.1rem] w-[2.1rem] shrink-0 items-center justify-center rounded-full text-white shadow-[inset_0_2px_3px_rgba(255,255,255,0.3),inset_0_-2px_4px_rgba(0,0,0,0.22),0_2px_6px_rgba(23,42,32,0.14)]" title="Companion">
                                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" /></svg>
                                                        </span>
                                                        <div class="cell-person__body min-w-0">
                                                            <div class="guest-name font-semibold text-hp-text">{{ collect([$guest->customer->first_name, $guest->customer->middle_name, $guest->customer->last_name])->filter()->join(' ') }}</div>
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

                    {{-- RESERVATION TABLE PAGINATION --}}
                    <div class="records-pagination mt-4 border-t border-glass-border pt-4">
                        <div class="flex flex-wrap items-center justify-between gap-x-5 gap-y-3">
                            <div class="flex items-center gap-2 text-sm text-hp-text-muted">
                                <span>Showing</span>
                            <select id="reservationPerPage" class="cursor-pointer rounded-lg border border-glass-border bg-glass px-2 py-1.5 text-sm text-hp-text focus:border-hp-green focus:outline-none dark:bg-[#0d2812] dark:text-[#c8e6c8]">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                                <span>per page</span>
                            </div>
                            <span id="reservationResultsCount" class="text-sm font-semibold text-hp-text">Showing 0 of 0 reservations</span>
                            <div class="flex items-center gap-1.5">
                                <button type="button" id="reservationPrevPage" class="cursor-pointer rounded-lg border border-glass-border bg-glass px-3 py-1.5 text-sm font-semibold text-hp-text transition-colors duration-200 hover:bg-glass-hover disabled:cursor-not-allowed disabled:opacity-40">‹ Prev</button>
                                <div id="reservationPageNumbers" class="flex items-center gap-1"></div>
                                <button type="button" id="reservationNextPage" class="cursor-pointer rounded-lg border border-glass-border bg-glass px-3 py-1.5 text-sm font-semibold text-hp-text transition-colors duration-200 hover:bg-glass-hover disabled:cursor-not-allowed disabled:opacity-40">Next ›</button>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center gap-2 text-sm text-hp-text-muted">
                            <span>Go to page</span>
                            <input type="number" id="reservationPageInput" min="1" value="1" class="w-16 rounded-lg border border-glass-border bg-glass px-2 py-1.5 text-sm text-hp-text focus:border-hp-green focus:outline-none dark:bg-[#0d2812] dark:text-[#c8e6c8]">
                            <button type="button" id="reservationGoPage" class="cursor-pointer rounded-lg bg-hp-green px-3 py-1.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark">Go</button>
                        </div>
                    </div>
                </section>
                        </div>

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
        window.staffBulkGroupData = @json($bulkGroupData ?? []);
        window.staffReservationData = @json($reservationData ?? []);
    </script>
</body>
</html>
