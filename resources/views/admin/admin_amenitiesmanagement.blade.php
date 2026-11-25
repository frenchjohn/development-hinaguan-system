<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Amenities Management — Hinaguan Nature Park</title>
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
        'resources/css/admin_css/admin_shared.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/admin_js/admin_amenitiesmanagement.js',
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
    </style>
</head>
<body class="antialiased admin-portal">
    <div class="dash-layout">
        <x-admin_sidemenu active="amenities" userName="{{ session('auth_user.name') ?? 'Admin User' }}" userRole="Admin" />

        <div class="dash-main">
            <x-header
                title="Amenities Management"
                subtitle="Create, edit, and maintain park amenities"
            />
            <!-- Page transition overlay with skeleton loading -->
            <main class="dash-content p-6">
                <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--border-strong)] hover:shadow-[var(--shadow-md)]">
                        <span class="grid h-[2.6rem] w-[2.6rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-[var(--green-soft)] text-[var(--green)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-[1.25rem] w-[1.25rem]"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </span>
                        <div>
                            <p class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]">{{ $totalAmenities }}</p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Total Amenities</p>
                        </div>
                    </article>
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--border-strong)] hover:shadow-[var(--shadow-md)]">
                        <span class="grid h-[2.6rem] w-[2.6rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-[var(--green-soft)] text-[var(--green)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-[1.25rem] w-[1.25rem]"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div>
                            <p class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]">{{ $enabledAmenities }}</p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Enabled</p>
                        </div>
                    </article>
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--border-strong)] hover:shadow-[var(--shadow-md)]">
                        <span class="grid h-[2.6rem] w-[2.6rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-[var(--danger-soft)] text-[var(--danger)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-[1.25rem] w-[1.25rem]"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </span>
                        <div>
                            <p class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]">{{ $disabledAmenities }}</p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Disabled</p>
                        </div>
                    </article>
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--border-strong)] hover:shadow-[var(--shadow-md)]">
                        <span class="grid h-[2.6rem] w-[2.6rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-[var(--warn-soft)] text-[var(--warn)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-[1.25rem] w-[1.25rem]"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
                        </span>
                        <div>
                            <p class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]">{{ $onSaleAmenities }}</p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">On Sale</p>
                        </div>
                    </article>
                </div>

                @if(session('success'))
                    <div class="alert alert--success">{{ session('success') }}</div>
                @endif

                <section class="rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-sm)]">
                    {{-- Section header --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[var(--border)] pb-4 mb-4">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-500/15 text-emerald-700 dark:text-emerald-300">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                                </svg>
                            </span>
                            <div>
                                <h2 class="m-0 font-display text-base font-bold text-[var(--ink)]">Amenities Directory</h2>
                                <p class="m-0 text-xs text-[var(--ink-muted)]">Search, sort, and manage park amenities and pricing</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                onclick="if(typeof window.refreshActivePage==='function'){window.refreshActivePage(true);}"
                                title="Refresh amenities list"
                                class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl border border-[var(--border)] bg-gray-50 dark:bg-neutral-900/40 text-[var(--ink-muted)] hover:border-emerald-500 hover:text-emerald-600 dark:hover:text-emerald-400 transition-all cursor-pointer"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                <span>Refresh</span>
                            </button>
                            <button type="button" class="btn btn--primary" data-open-amenity-modal>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                <span>New Amenity</span>
                            </button>
                        </div>
                    </div>

                    {{-- Filters row --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                        <div>
                            <label for="amenitySearch" class="block mb-1 text-[11px] font-semibold uppercase tracking-wide text-[var(--ink-muted)]">Search amenities</label>
                            <input id="amenitySearch" type="search" placeholder="Search by name..." autocomplete="off" class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2 text-xs text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]">
                        </div>
                        <div>
                            <label for="amenitySortColumn" class="block mb-1 text-[11px] font-semibold uppercase tracking-wide text-[var(--ink-muted)]">Sort column</label>
                            <select id="amenitySortColumn" class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2 text-xs text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]">
                                <option value="">None</option>
                                <option value="daytimePrice">Day price</option>
                                <option value="nighttimePrice">Night price</option>
                                <option value="additionalPerHead">Additional per head</option>
                                <option value="minimumCapacity">Minimum capacity</option>
                                <option value="maximumCapacity">Maximum capacity</option>
                            </select>
                        </div>
                        <div>
                            <label for="amenitySortOrder" class="block mb-1 text-[11px] font-semibold uppercase tracking-wide text-[var(--ink-muted)]">Sort order</label>
                            <select id="amenitySortOrder" class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2 text-xs text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]">
                                <option value="none">None</option>
                                <option value="asc">Low to high</option>
                                <option value="desc">High to low</option>
                            </select>
                        </div>
                        <div>
                            <label for="amenityStatus" class="block mb-1 text-[11px] font-semibold uppercase tracking-wide text-[var(--ink-muted)]">Status</label>
                            <select id="amenityStatus" class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2 text-xs text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]">
                                <option value="all">All statuses</option>
                                <option value="enabled">Enabled only</option>
                                <option value="disabled">Disabled only</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto overflow-y-auto max-h-[442px] rounded-xl border border-[var(--border)]" style="max-height: 442px;">
                        <table class="w-full text-left text-xs border-separate border-spacing-0">
                        <thead class="sticky top-0 z-10 shadow-xs">
                            <tr class="font-semibold text-[var(--ink-muted)]">
                                <th class="sticky top-0 z-10 py-3 px-4 whitespace-nowrap bg-gray-50 dark:bg-neutral-900 border-b border-[var(--border)]">Amenity</th>
                                <th class="sticky top-0 z-10 py-3 px-4 text-right whitespace-nowrap bg-gray-50 dark:bg-neutral-900 border-b border-[var(--border)]">Day Price</th>
                                <th class="sticky top-0 z-10 py-3 px-4 text-right whitespace-nowrap bg-gray-50 dark:bg-neutral-900 border-b border-[var(--border)]">Night Price</th>
                                <th class="sticky top-0 z-10 py-3 px-4 whitespace-nowrap bg-gray-50 dark:bg-neutral-900 border-b border-[var(--border)]">Benefits</th>
                                <th class="sticky top-0 z-10 py-3 px-4 text-center whitespace-nowrap bg-gray-50 dark:bg-neutral-900 border-b border-[var(--border)]">Capacity</th>
                                <th class="sticky top-0 z-10 py-3 px-4 text-center whitespace-nowrap bg-gray-50 dark:bg-neutral-900 border-b border-[var(--border)]">Sale</th>
                                <th class="sticky top-0 z-10 py-3 px-4 whitespace-nowrap bg-gray-50 dark:bg-neutral-900 border-b border-[var(--border)]">Status</th>
                                <th class="sticky top-0 z-10 py-3 px-4 text-right whitespace-nowrap bg-gray-50 dark:bg-neutral-900 border-b border-[var(--border)]">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="amenitiesTableBody">
                            @forelse($amenities as $amenity)
                                <tr
                                    data-amenity-id="{{ $amenity->id }}"
                                    data-amenities-name="{{ e($amenity->amenities_name) }}"
                                    data-daytime-price="{{ $amenity->daytime_price }}"
                                    data-nighttime-price="{{ $amenity->nighttime_price }}"
                                    data-is-aircon="{{ $amenity->benefits?->is_aircon ? '1' : '0' }}"
                                    data-free-entrance="{{ $amenity->benefits?->free_entrance ? '1' : '0' }}"
                                    data-free-pool="{{ $amenity->benefits?->free_pool ? '1' : '0' }}"
                                    data-additional-per-head="{{ $amenity->additional_per_head }}"
                                    data-minimum-capacity="{{ $amenity->minimum_capacity }}"
                                    data-maximum-capacity="{{ $amenity->maximum_capacity }}"
                                    data-description="{{ e($amenity->description) }}"
                                    data-image-url="{{ $amenity->image ? e(asset('storage/' . $amenity->image)) : '' }}"
                                    data-image-path="{{ $amenity->image ?? '' }}"
                                    data-status="{{ $amenity->status ? 'enabled' : 'disabled' }}"
                                    data-sale-percentage="{{ $amenity->sale_percentage ?? 0 }}"
                                    data-original-daytime-price="{{ $amenity->original_daytime_price ?? $amenity->daytime_price }}"
                                    data-original-nighttime-price="{{ $amenity->original_nighttime_price ?? $amenity->nighttime_price }}"
                                    class="amenity-row cursor-pointer transition-colors hover:bg-black/5 dark:hover:bg-white/5"
                                >
                                    <td class="py-3 px-4 whitespace-nowrap border-b border-[var(--border)]">
                                        <div class="flex items-center gap-2.5">
                                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-emerald-500/15 text-[11px] font-bold text-emerald-700 dark:text-emerald-300">
                                                {{ strtoupper(mb_substr($amenity->amenities_name, 0, 1)) }}
                                            </span>
                                            <span class="font-semibold text-[var(--ink)]">{{ $amenity->amenities_name }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap border-b border-[var(--border)] tabular-nums">
                                        @if($amenity->sale_percentage && $amenity->sale_percentage > 0)
                                            <p class="m-0 font-bold text-emerald-700 dark:text-emerald-400">₱{{ number_format($amenity->daytime_price, 2) }}</p>
                                            <p class="m-0 text-[10px] text-[var(--ink-muted)] line-through">₱{{ number_format($amenity->original_daytime_price ?? $amenity->daytime_price, 2) }}</p>
                                        @else
                                            <span class="font-semibold text-[var(--ink)]">₱{{ number_format($amenity->daytime_price, 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap border-b border-[var(--border)] tabular-nums">
                                        @if($amenity->sale_percentage && $amenity->sale_percentage > 0)
                                            <p class="m-0 font-bold text-emerald-700 dark:text-emerald-400">₱{{ number_format($amenity->nighttime_price, 2) }}</p>
                                            <p class="m-0 text-[10px] text-[var(--ink-muted)] line-through">₱{{ number_format($amenity->original_nighttime_price ?? $amenity->nighttime_price, 2) }}</p>
                                        @else
                                            <span class="font-semibold text-[var(--ink)]">₱{{ number_format($amenity->nighttime_price, 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 border-b border-[var(--border)]">
                                        <div class="flex flex-wrap gap-1">
                                            @if($amenity->benefits?->is_aircon)
                                                <span class="inline-flex items-center gap-1 rounded-md bg-cyan-500/15 px-2 py-0.5 text-[10px] font-bold text-cyan-700 dark:text-cyan-300">Aircon</span>
                                            @endif
                                            @if($amenity->benefits?->free_entrance)
                                                <span class="inline-flex items-center gap-1 rounded-md bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:text-emerald-300">Free Entrance</span>
                                            @endif
                                            @if($amenity->benefits?->free_pool)
                                                <span class="inline-flex items-center gap-1 rounded-md bg-blue-500/15 px-2 py-0.5 text-[10px] font-bold text-blue-700 dark:text-blue-300">Free Pool</span>
                                            @endif
                                            @if(!$amenity->benefits?->is_aircon && !$amenity->benefits?->free_entrance && !$amenity->benefits?->free_pool)
                                                <span class="text-[var(--ink-muted)]">—</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap border-b border-[var(--border)] tabular-nums">
                                        @if($amenity->minimum_capacity !== null || $amenity->maximum_capacity !== null)
                                            <span class="inline-flex items-center rounded-lg bg-gray-100 dark:bg-neutral-800 px-2 py-0.5 text-[11px] font-semibold text-[var(--ink)]">
                                                {{ $amenity->minimum_capacity ?? '—' }}–{{ $amenity->maximum_capacity ?? '—' }} pax
                                            </span>
                                        @else
                                            <span class="text-[var(--ink-muted)]">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap border-b border-[var(--border)]">
                                        @if($amenity->sale_percentage && $amenity->sale_percentage > 0)
                                            <span class="inline-flex items-center rounded-md bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 px-2 py-0.5 text-[10px] font-bold">
                                                {{ $amenity->sale_percentage }}% OFF
                                            </span>
                                        @else
                                            <span class="text-[var(--ink-muted)]">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap border-b border-[var(--border)]">
                                        @if($amenity->status)
                                            <span class="inline-flex items-center rounded-md bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 px-2 py-0.5 text-[10px] font-bold">Enabled</span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 px-2 py-0.5 text-[10px] font-bold">Disabled</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap border-b border-[var(--border)]">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button type="button" data-action="edit" title="Edit amenity" class="inline-flex h-7 w-7 cursor-pointer items-center justify-center rounded-lg border border-[var(--border)] bg-transparent text-[var(--ink-muted)] transition-colors hover:border-emerald-500 hover:text-emerald-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button type="button" data-action="delete" title="Delete amenity" class="inline-flex h-7 w-7 cursor-pointer items-center justify-center rounded-lg border border-[var(--border)] bg-transparent text-[var(--ink-muted)] transition-colors hover:border-red-500 hover:text-red-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-10 px-4 text-center text-xs text-[var(--ink-muted)]">No amenities found yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            </main>
        </div>
    </div>

    <div class="modal !items-center !justify-center !p-4" id="amenityModal" aria-hidden="true">
        <div class="modal__backdrop" data-close-amenity-modal></div>
        <div class="relative z-10 my-auto flex w-full max-w-[660px] max-h-[90vh] flex-col overflow-hidden rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] shadow-xl">
            <div class="flex shrink-0 items-center justify-between border-b border-[var(--border)] px-6 py-4">
                <h3 id="amenityModalTitle" class="m-0 font-display text-lg font-bold text-[var(--ink)]">Add New Amenity</h3>
                <button type="button" data-close-amenity-modal aria-label="Close" class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border-0 bg-transparent text-[var(--ink-muted)] transition-colors hover:bg-[var(--surface-2)] hover:text-[var(--ink)]">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.amenities.store') }}" id="amenityForm" enctype="multipart/form-data" data-store-url="{{ route('admin.amenities.store') }}" data-update-base-url="{{ url('admin/amenities') }}" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <input type="hidden" name="amenity_id" id="amenityId">
                <input type="hidden" name="existing_image" id="existingImage">

                <div class="flex-1 space-y-4 overflow-y-auto px-6 py-5">
                    {{-- Name, Status, Sale --}}
                    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-12">
                        <div class="sm:col-span-6">
                            <label for="amenities_name" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Amenity Name <span class="text-red-500">*</span></label>
                            <input id="amenities_name" name="amenities_name" type="text" placeholder="e.g. A-House 1" required class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]">
                        </div>
                        <div class="sm:col-span-3">
                            <label for="status" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Status</label>
                            <select id="status" name="status" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]">
                                <option value="enabled">Enabled</option>
                                <option value="disabled">Disabled</option>
                            </select>
                        </div>
                        <div class="sm:col-span-3">
                            <label for="sale_percentage" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Sale (%)</label>
                            <input id="sale_percentage" name="sale_percentage" type="number" step="0.01" min="0" max="100" inputmode="decimal" placeholder="0" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]">
                        </div>
                    </div>

                    {{-- Pricing --}}
                    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-3">
                        <div>
                            <label for="daytime_price" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Daytime Price (₱) <span class="text-red-500">*</span></label>
                            <input id="daytime_price" name="daytime_price" type="number" step="0.01" min="0" inputmode="decimal" placeholder="0.00" required class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]">
                        </div>
                        <div>
                            <label for="nighttime_price" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Nighttime Price (₱) <span class="text-red-500">*</span></label>
                            <input id="nighttime_price" name="nighttime_price" type="number" step="0.01" min="0" inputmode="decimal" placeholder="0.00" required class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]">
                        </div>
                        <div>
                            <label for="additional_per_head" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Extra Per Head (₱)</label>
                            <input id="additional_per_head" name="additional_per_head" type="number" step="0.01" min="0" inputmode="decimal" placeholder="0.00" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]">
                        </div>
                    </div>

                    {{-- Capacity --}}
                    <div class="grid grid-cols-2 gap-3.5">
                        <div>
                            <label for="minimum_capacity" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Minimum Capacity (pax) <span class="text-red-500">*</span></label>
                            <input id="minimum_capacity" name="minimum_capacity" type="number" step="1" min="1" inputmode="numeric" placeholder="e.g. 1" required class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]">
                        </div>
                        <div>
                            <label for="maximum_capacity" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Maximum Capacity (pax) <span class="text-red-500">*</span></label>
                            <input id="maximum_capacity" name="maximum_capacity" type="number" step="1" min="1" inputmode="numeric" placeholder="e.g. 4" required class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]">
                        </div>
                    </div>

                    {{-- Included Benefits --}}
                    <div>
                        <span class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Included Benefits</span>
                        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-xs font-medium text-[var(--ink)] transition-colors hover:border-[var(--border-strong)]">
                                <input type="checkbox" id="is_aircon" name="is_aircon" value="1" class="h-4 w-4 accent-[#1b5e3a] rounded cursor-pointer">
                                <span>Has Aircon</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-xs font-medium text-[var(--ink)] transition-colors hover:border-[var(--border-strong)]">
                                <input type="checkbox" id="free_entrance" name="free_entrance" value="1" class="h-4 w-4 accent-[#1b5e3a] rounded cursor-pointer">
                                <span>Free Entrance</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-xs font-medium text-[var(--ink)] transition-colors hover:border-[var(--border-strong)]">
                                <input type="checkbox" id="free_pool" name="free_pool" value="1" class="h-4 w-4 accent-[#1b5e3a] rounded cursor-pointer">
                                <span>Free Pool Access</span>
                            </label>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label for="description" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Description</label>
                        <textarea id="description" name="description" rows="2" placeholder="Optional amenity notes or inclusions..." class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)] resize-y"></textarea>
                    </div>

                    {{-- Amenity Image --}}
                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label class="m-0 text-xs font-semibold text-[var(--ink)]">Amenity Image</label>
                            <span class="text-[11px] text-[var(--ink-muted)]">PNG, JPG, WEBP up to 4MB</span>
                        </div>

                        <div id="imagePreview" class="image-preview mb-2.5" style="display:none;">
                            <div class="relative overflow-hidden rounded-xl border border-[var(--border)] bg-black/5">
                                <img id="imagePreviewImg" src="" alt="Amenity preview" class="block max-h-[180px] w-full object-cover">
                                <div class="absolute bottom-2.5 left-2.5 rounded-md bg-black/70 px-2 py-0.5 text-[11px] font-medium text-white">
                                    <span id="imagePreviewBadge">Image Preview</span>
                                </div>
                                <button type="button" id="removeImageBtn" class="absolute right-2.5 top-2.5 flex h-7 w-7 cursor-pointer items-center justify-center rounded-lg border-0 bg-black/65 text-white transition-colors hover:bg-red-600" title="Remove selected image">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="dropzone" id="imageDropZone">
                            <span class="dropzone__text">Drag &amp; drop an image here, or click to browse</span>
                            <span class="dropzone__filename" id="imageFileName">No file chosen</span>
                        </div>
                        <input id="image" name="image" type="file" accept="image/*" hidden>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2.5 border-t border-[var(--border)] bg-[var(--surface-2)] px-6 py-3.5">
                    <button type="button" class="btn btn--ghost" data-close-amenity-modal>Cancel</button>
                    <button type="button" class="btn btn--ghost" id="amenityEditButton" style="display:none;">Edit</button>
                    <button type="submit" class="btn btn--primary" id="amenitySubmitButton">Create Amenity</button>
                    <button type="button" class="btn btn--danger" id="amenityDeleteButton" style="display:none;" data-delete-amenity>Delete</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Admin AI Intelligence Chatbot --}}
    <x-admin_chatbot />
</body>
</html>
