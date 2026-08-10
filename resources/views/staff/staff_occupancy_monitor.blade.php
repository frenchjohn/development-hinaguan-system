<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Occupancy Monitor — Hinaguan Nature Park</title>
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
        'resources/js/staff_js/staff_occupancy_monitor.js',
        'resources/js/staff_chatbot.js',
    ])
</head>
<body class="antialiased staff-portal">
    <div class="dash-layout">
        <x-staff_sidemenu active="occupancy-monitor" userName="{{ session('auth_user.name') ?? 'Staff User' }}" userRole="Staff" />

        <div class="dash-main">

            <main class="dash-content p-6">
                <x-header
                    title="Occupancy Monitor"
                    subtitle="Real-time view of all amenities and their availability"
                />

                {{-- Live status strip --}}
                <div class="mb-4 grid grid-cols-2 gap-3.5 md:grid-cols-3">
                    <article class="flex min-w-0 items-center gap-3 rounded-2xl border border-glass-border bg-glass p-4 shadow-glass transition-transform duration-300 hover:-translate-y-0.5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[11px] bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1a3324] dark:text-[#6ab88c]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="occupancy-stat__value m-0 font-display text-[1.45rem] font-bold leading-[1.1] text-hp-text tabular-nums" data-count="{{ $totalAmenities }}">{{ $totalAmenities }}</p>
                            <p class="mt-0.5 text-[0.68rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Amenities</p>
                        </div>
                    </article>
                    <article class="flex min-w-0 items-center gap-3 rounded-2xl border border-glass-border bg-glass p-4 shadow-glass transition-transform duration-300 hover:-translate-y-0.5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[11px] bg-[#fde8e8] text-[#b91c1c] dark:bg-[#3a1f1c] dark:text-[#f3a0a0]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="occupancy-stat__value m-0 font-display text-[1.45rem] font-bold leading-[1.1] text-hp-text tabular-nums" data-count="{{ $occupiedCount }}">{{ $occupiedCount }}</p>
                            <p class="mt-0.5 text-[0.68rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Occupied Now</p>
                            <p class="mt-0.5 truncate text-[0.7rem] text-hp-text-muted/70">{{ $occupiedReservations }} active reservation{{ $occupiedReservations === 1 ? '' : 's' }}</p>
                        </div>
                    </article>
                    <article class="flex min-w-0 items-center gap-3 rounded-2xl border border-glass-border bg-glass p-4 shadow-glass transition-transform duration-300 hover:-translate-y-0.5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[11px] bg-[#fef3c7] text-[#b45309] dark:bg-[#3a2f14] dark:text-[#e5c35c]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="occupancy-stat__value m-0 font-display text-[1.45rem] font-bold leading-[1.1] text-hp-text tabular-nums" data-count="{{ $reservedCount }}">{{ $reservedCount }}</p>
                            <p class="mt-0.5 text-[0.68rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Reserved Today</p>
                        </div>
                    </article>
                    <article class="flex min-w-0 items-center gap-3 rounded-2xl border border-glass-border bg-glass p-4 shadow-glass transition-transform duration-300 hover:-translate-y-0.5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[11px] bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1a3324] dark:text-[#6ab88c]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="occupancy-stat__value m-0 font-display text-[1.45rem] font-bold leading-[1.1] text-hp-text tabular-nums" data-count="{{ $availableCount }}">{{ $availableCount }}</p>
                            <p class="mt-0.5 text-[0.68rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Available Now</p>
                        </div>
                    </article>
                    <article class="flex min-w-0 items-center gap-3 rounded-2xl border border-glass-border bg-gradient-to-br from-[#e7f3ec]/60 to-transparent p-4 shadow-glass transition-transform duration-300 hover:-translate-y-0.5 dark:from-[#1a3324]/40">
                        <div class="occupancy-rate-ring relative grid h-[3.1rem] w-[3.1rem] shrink-0 place-items-center rounded-full shadow-[inset_0_1px_2px_rgba(23,42,32,0.08)]" style="background: conic-gradient(var(--hp-green) calc(var(--pct) * 1%), var(--glass-border) 0); --pct: {{ $occupancyRate }}">
                            <span class="grid h-[2.15rem] w-[2.15rem] place-items-center rounded-full bg-glass text-[0.68rem] font-bold text-hp-green shadow-[inset_0_1px_2px_rgba(23,42,32,0.06)]">{{ $occupancyRate }}%</span>
                        </div>
                        <div class="min-w-0">
                            <p class="mt-0.5 text-[0.68rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Occupancy Rate</p>
                            <p class="mt-0.5 truncate text-[0.7rem] text-hp-text-muted/70">{{ $inUseCount }} of {{ $totalAmenities }} in use</p>
                        </div>
                    </article>
                    <article class="flex min-w-0 items-center gap-3 rounded-2xl border border-glass-border bg-glass p-4 shadow-glass transition-transform duration-300 hover:-translate-y-0.5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[11px] bg-[#f1eafd] text-[#7c3aed] dark:bg-[#2b2142] dark:text-[#b79df0]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="occupancy-stat__value m-0 font-display text-[1.45rem] font-bold leading-[1.1] text-hp-text tabular-nums" data-count="{{ $visitorCount }}">{{ $visitorCount }}</p>
                            <p class="mt-0.5 text-[0.68rem] font-bold uppercase tracking-[0.06em] text-hp-text-muted">Visitors</p>
                            <p class="mt-0.5 truncate text-[0.7rem] text-hp-text-muted/70">Guests inside with no amenity</p>
                        </div>
                    </article>
                </div>

                <div class="mb-4 flex flex-wrap items-center gap-4 rounded-xl border border-glass-border bg-glass px-4 py-2 text-[0.74rem] text-hp-text-muted shadow-glass">
                    <span class="inline-flex items-center gap-1.5 font-bold text-hp-green"><i class="h-2 w-2 animate-pulse rounded-full bg-[#22c55e]"></i> Live</span>
                    <span class="inline-flex items-center gap-1.5 font-semibold"><i class="h-[0.55rem] w-[0.55rem] rounded-full bg-[#dc2626]"></i>Occupied</span>
                    <span class="inline-flex items-center gap-1.5 font-semibold"><i class="h-[0.55rem] w-[0.55rem] rounded-full bg-[#c8a45d]"></i>Reserved today</span>
                    <span class="inline-flex items-center gap-1.5 font-semibold"><i class="h-[0.55rem] w-[0.55rem] rounded-full bg-hp-green"></i>Available</span>
                    <span class="ml-auto italic text-hp-text-muted/70">Click any amenity card for full details</span>
                </div>

                <div class="mb-3">
                    <button type="button" id="filterToggleBtn" class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-glass-border bg-glass px-4 py-2.5 text-sm font-medium text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                        </svg>
                        Filters
                    </button>
                </div>

                <div class="occupancy-monitor__filter-panel mb-6 hidden rounded-xl border border-glass-border bg-glass p-5 shadow-glass is-open:block" id="filterPanel">
                    <div class="mb-4 grid grid-cols-[repeat(auto-fit,minmax(180px,1fr))] gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label for="searchAmenities" class="text-[0.8rem] font-semibold text-hp-text">Search Amenities</label>
                            <input type="text" id="searchAmenities" placeholder="Search by name..." class="rounded-md border border-glass-border bg-glass px-3 py-2.5 text-sm text-hp-text transition-colors duration-200 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none dark:bg-[#0d2812]" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="timeSlotFilter" class="text-[0.8rem] font-semibold text-hp-text">Time Slot</label>
                            <select id="timeSlotFilter" class="rounded-md border border-glass-border bg-glass px-3 py-2.5 text-sm text-hp-text transition-colors duration-200 focus:border-hp-green focus:outline-none dark:bg-[#0d2812]">
                                <option value="all">All Time Slots</option>
                                <option value="daytime">Daytime</option>
                                <option value="nighttime">Nighttime</option>
                                <option value="daytonight">Day to Night</option>
                                <option value="nighttoday">Night to Day</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="availabilityFilter" class="text-[0.8rem] font-semibold text-hp-text">Availability</label>
                            <select id="availabilityFilter" class="rounded-md border border-glass-border bg-glass px-3 py-2.5 text-sm text-hp-text transition-colors duration-200 focus:border-hp-green focus:outline-none dark:bg-[#0d2812]">
                                <option value="all">All</option>
                                <option value="available">Available</option>
                                <option value="unavailable">Unavailable</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" id="clearFiltersBtn" class="cursor-pointer rounded-md border border-glass-border bg-transparent px-4 py-2 text-sm font-medium text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong">Clear Filters</button>
                    </div>
                </div>

                <div class="occupancy-grid grid grid-cols-[repeat(auto-fill,minmax(250px,1fr))] gap-6 max-[480px]:grid-cols-1">
                    @forelse ($amenities as $amenity)
                        @php
                            $amenityOccupancy = $occupancyData[$amenity->id] ?? ['occupied' => [], 'reserved' => []];

                            // Determine occupied time slots
                            $occupiedSlots = [];
                            foreach ($amenityOccupancy['occupied'] as $occupied) {
                                $timeSlot = strtolower($occupied['time_slot']);
                                if (str_contains($timeSlot, 'daytonight')) {
                                    // Day to Night covers both daytime and nighttime
                                    $occupiedSlots[] = 'daytime';
                                    $occupiedSlots[] = 'nighttime';
                                } elseif (str_contains($timeSlot, 'nighttoday')) {
                                    // Night to Day occupies tonight (its daytime is tomorrow)
                                    $occupiedSlots[] = 'nighttime';
                                } elseif (str_contains($timeSlot, 'daytime')) {
                                    $occupiedSlots[] = 'daytime';
                                } elseif (str_contains($timeSlot, 'nighttime')) {
                                    $occupiedSlots[] = 'nighttime';
                                }
                            }

                            // Determine reserved time slots
                            $reservedSlots = [];
                            foreach ($amenityOccupancy['reserved'] as $reserved) {
                                $timeSlot = strtolower($reserved['time_slot']);
                                if (str_contains($timeSlot, 'daytonight')) {
                                    // Day to Night covers both daytime and nighttime
                                    $reservedSlots[] = 'daytime';
                                    $reservedSlots[] = 'nighttime';
                                } elseif (str_contains($timeSlot, 'nighttoday')) {
                                    // Night to Day occupies tonight (its daytime is tomorrow)
                                    $reservedSlots[] = 'nighttime';
                                } elseif (str_contains($timeSlot, 'daytime')) {
                                    $reservedSlots[] = 'daytime';
                                } elseif (str_contains($timeSlot, 'nighttime')) {
                                    $reservedSlots[] = 'nighttime';
                                }
                            }

                            // Combine occupied and reserved slots
                            $unavailableSlots = array_unique(array_merge($occupiedSlots, $reservedSlots));

                            // Determine available slots (all slots minus unavailable)
                            $allSlots = ['daytime', 'nighttime'];
                            $availableSlots = array_diff($allSlots, $unavailableSlots);

                            // Card status badge
                            $isOccupied = ! empty($amenityOccupancy['occupied']);
                            $isReserved = ! empty($amenityOccupancy['reserved']);
                            if ($isOccupied) {
                                $cardStatus = 'occupied';
                                $cardStatusLabel = 'Occupied';
                            } elseif ($isReserved) {
                                $cardStatus = 'reserved';
                                $cardStatusLabel = 'Reserved';
                            } else {
                                $cardStatus = 'available';
                                $cardStatusLabel = 'Available';
                            }
                            $hasDay = in_array('daytime', $availableSlots);
                            $hasNight = in_array('nighttime', $availableSlots);
                        @endphp
                        <div class="occupancy-card group relative cursor-pointer overflow-hidden rounded-2xl bg-glass shadow-glass transition-all duration-300 hover:-translate-y-1 hover:shadow-glass"
                             data-amenity-id="{{ $amenity->id }}"
                             data-amenity-name="{{ strtolower($amenity->amenities_name) }}"
                             data-display-name="{{ e($amenity->amenities_name) }}"
                             data-daytime-price="₱{{ number_format($amenity->daytime_price, 2) }}"
                             data-nighttime-price="₱{{ number_format($amenity->nighttime_price, 2) }}"
                             data-daytime-aircon-price="{{ $amenity->daytime_aircon_price ? '₱'.number_format($amenity->daytime_aircon_price, 2) : 'N/A' }}"
                             data-nighttime-aircon-price="{{ $amenity->nighttime_aircon_price ? '₱'.number_format($amenity->nighttime_aircon_price, 2) : 'N/A' }}"
                             data-additional-per-head="{{ $amenity->additional_per_head ? '₱'.number_format($amenity->additional_per_head, 2) : 'N/A' }}"
                             data-min-cap="{{ $amenity->minimum_capacity ?? 'N/A' }}"
                             data-max-cap="{{ $amenity->maximum_capacity ?? 'N/A' }}"
                             data-description="{{ e($amenity->description ?? 'No description available for this amenity.') }}"
                             data-image-src="{{ $amenity->image ? asset('storage/' . $amenity->image) : '' }}"
                             data-occupied-json="{{ json_encode($amenityOccupancy['occupied']) }}"
                             data-reserved-json="{{ json_encode($amenityOccupancy['reserved']) }}"
                             data-available-slots="{{ implode(',', $availableSlots) }}"
                             data-unavailable-slots="{{ implode(',', $unavailableSlots) }}">
                            <div class="relative aspect-[4/3] w-full overflow-hidden bg-hp-cream">
                                @if ($amenity->image)
                                    <img src="{{ asset('storage/' . $amenity->image) }}" alt="{{ $amenity->amenities_name }}" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-hp-text-muted">
                                        <svg class="h-12 w-12" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                        </svg>
                                    </div>
                                @endif
                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[rgba(13,44,29,0.7)] to-transparent dark:from-black/70"></div>
                                <span class="occupancy-card__badge absolute left-3 top-3 z-[5] inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[0.66rem] font-bold uppercase tracking-[0.05em] text-white shadow-glass backdrop-blur-sm occupancy-card__badge--{{ $cardStatus }}">
                                    <i class="h-1 w-1 rounded-full bg-white/90"></i>{{ $cardStatusLabel }}
                                </span>
                                @if (!empty($amenityOccupancy['occupied']))
                                    <div class="occupancy-card__status-overlay absolute bottom-0 left-0 right-0 z-10 border-t-2 border-[#dc2626] bg-[rgba(220,38,38,0.9)] px-3 py-2 text-[0.68rem] text-white backdrop-blur-sm dark:border-[#f87171] dark:bg-[rgba(220,38,38,0.85)]">
                                        @foreach ($amenityOccupancy['occupied'] as $occupied)
                                            <div class="occupancy-card__status-item mb-1 flex flex-wrap items-center gap-1.5 last:mb-0">
                                                <span class="font-bold text-white [text-shadow:0_1px_2px_rgba(0,0,0,0.5)]">Occupied by:</span>
                                                <span class="font-bold text-white [text-shadow:0_1px_2px_rgba(0,0,0,0.5)]">#{{ $occupied['reservation_id'] }}</span>
                                                <span class="rounded-full border border-glass-border bg-white/25 px-2 py-0.5 text-[0.7rem] font-bold capitalize text-white [text-shadow:0_1px_2px_rgba(0,0,0,0.3)]">{{ $occupied['time_slot'] }}</span>
                                                <span class="rounded-full border border-glass-border bg-white/25 px-2 py-0.5 text-[0.7rem] font-bold text-white [text-shadow:0_1px_2px_rgba(0,0,0,0.3)]">{{ $occupied['guest_count'] ?? 0 }} guest{{ ($occupied['guest_count'] ?? 0) == 1 ? '' : 's' }} inside</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @if (!empty($amenityOccupancy['reserved']))
                                    <div class="occupancy-card__status-overlay absolute left-0 right-0 top-0 z-10 border-b-2 border-[#8a7a4d] bg-[rgba(200,164,93,0.9)] px-3 py-2 text-[0.68rem] text-white backdrop-blur-sm dark:border-[#fbbf24] dark:bg-[rgba(200,164,93,0.85)]">
                                        @foreach ($amenityOccupancy['reserved'] as $reserved)
                                            <div class="occupancy-card__status-item mb-1 flex flex-wrap items-center gap-1.5 last:mb-0">
                                                <span class="font-bold text-white [text-shadow:0_1px_2px_rgba(0,0,0,0.5)]">Reserved today by:</span>
                                                <span class="font-bold text-white [text-shadow:0_1px_2px_rgba(0,0,0,0.5)]">#{{ $reserved['reservation_id'] }}</span>
                                                <span class="rounded-full border border-glass-border bg-white/25 px-2 py-0.5 text-[0.7rem] font-bold capitalize text-white [text-shadow:0_1px_2px_rgba(0,0,0,0.3)]">{{ $reserved['time_slot'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="occupancy-card__content relative flex flex-col gap-3 bg-glass p-4">
                                <div class="flex items-start justify-between gap-2.5">
                                    <h4 class="occupancy-card__name m-0 font-display text-lg font-semibold leading-[1.3] text-hp-text dark:text-[#c8e6c8]">{{ $amenity->amenities_name }}</h4>
                                    <span class="shrink-0 whitespace-nowrap text-[0.8rem] font-bold text-hp-green">₱{{ number_format($amenity->daytime_price, 2) }}<small class="text-[0.62rem] font-semibold text-hp-text-muted/70">/day</small></span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="slot-chip slot-chip--daytime inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[0.68rem] font-bold tracking-[0.02em] shadow-[inset_0_1px_0_rgba(255,255,255,0.35)] {{ $hasDay ? 'is-free border-[rgba(23,138,82,0.28)] bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1a3324] dark:text-[#6ab88c]' : 'is-taken border-[rgba(207,75,71,0.25)] bg-[#fde8e8] text-[#b91c1c] dark:bg-[#3a1f1c] dark:text-[#f3a0a0]' }}">
                                        <i class="slot-chip__dot h-[0.42rem] w-[0.42rem] rounded-full {{ $hasDay ? 'bg-hp-green' : 'bg-[#dc2626]' }}"></i>Daytime
                                    </span>
                                    <span class="slot-chip slot-chip--nighttime inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[0.68rem] font-bold tracking-[0.02em] shadow-[inset_0_1px_0_rgba(255,255,255,0.35)] {{ $hasNight ? 'is-free border-[rgba(23,138,82,0.28)] bg-[#e7f3ec] text-[#1c5c3c] dark:bg-[#1a3324] dark:text-[#6ab88c]' : 'is-taken border-[rgba(207,75,71,0.25)] bg-[#fde8e8] text-[#b91c1c] dark:bg-[#3a1f1c] dark:text-[#f3a0a0]' }}">
                                        <i class="slot-chip__dot h-[0.42rem] w-[0.42rem] rounded-full {{ $hasNight ? 'bg-hp-green' : 'bg-[#dc2626]' }}"></i>Nighttime
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-glass-border pt-2.5 text-[0.72rem] text-hp-text-muted">
                                    <span class="inline-flex items-center gap-1.5 font-semibold">
                                        <svg class="h-3.5 w-3.5 text-hp-text-muted/70" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                        {{ $amenity->minimum_capacity }}–{{ $amenity->maximum_capacity }} pax
                                    </span>
                                    <span class="font-semibold text-hp-text-muted/70">Night ₱{{ number_format($amenity->nighttime_price, 2) }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-2 text-[0.72rem] font-bold text-hp-green opacity-60 transition-opacity duration-200 group-hover:opacity-100">
                                    <span>View details</span>
                                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/></svg>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="occupancy-empty col-span-full flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-glass-border-strong bg-glass px-4 py-16 text-center text-hp-text-muted shadow-glass">
                            <svg class="h-16 w-16 opacity-50" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                            </svg>
                            <p class="m-0 text-base">No amenities found</p>
                        </div>
                    @endforelse
                </div>
            </main>
        </div>
    </div>

    <!-- Amenity Detail Modal -->
    <div class="modal fixed inset-0 z-[1000] flex items-center justify-center p-4 opacity-0 transition-all duration-250 invisible is-open:visible is-open:opacity-100" id="amenityDetailModal" aria-hidden="true">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="closeAmenityDetailModal"></div>
        <div class="amenity-detail-panel relative z-[1] flex max-h-[90vh] w-full max-w-[600px] flex-col overflow-hidden rounded-2xl bg-glass shadow-glass dark:bg-glass">
            <div class="flex items-center justify-between border-b border-[#e5e7eb] px-6 py-5 dark:border-glass-border">
                <h3 class="m-0 font-display text-xl text-hp-green-dark dark:text-[#c8e6c8]" id="modalAmenityTitle">Amenity Details</h3>
                <button type="button" class="modal__close cursor-pointer border-0 bg-transparent text-2xl leading-none text-hp-text-muted" id="closeAmenityDetailModalBtn">&times;</button>
            </div>
            <div class="amenity-detail-body flex flex-col gap-5 overflow-y-auto p-6">
                <div class="amenity-detail-img-wrap h-[200px] w-full overflow-hidden rounded-xl bg-glass-hover dark:bg-[#0d2812]">
                    <img id="modalAmenityImg" src="" alt="Amenity Image" class="h-full w-full object-cover" style="display:none;">
                    <div id="modalAmenityImgPlaceholder" class="amenity-detail-placeholder flex h-full w-full items-center justify-center text-hp-text-muted" style="display:none;">
                        <span>No Image Available</span>
                    </div>
                </div>
                <div class="amenity-detail-info flex flex-col gap-5">
                    <p class="amenity-detail-desc m-0 text-[0.95rem] leading-relaxed text-hp-text-muted" id="modalAmenityDesc"></p>
                    <div class="amenity-detail-grid grid grid-cols-2 gap-4 rounded-xl bg-glass-hover p-4 dark:bg-[#0d2812]">
                        <div class="detail-item flex flex-col gap-1">
                            <span class="detail-label text-xs font-semibold uppercase tracking-[0.05em] text-hp-text-muted">Daytime Price</span>
                            <span class="detail-val text-base font-bold text-hp-green-dark dark:text-[#81c784]" id="modalDaytimePrice"></span>
                        </div>
                        <div class="detail-item flex flex-col gap-1">
                            <span class="detail-label text-xs font-semibold uppercase tracking-[0.05em] text-hp-text-muted">Nighttime Price</span>
                            <span class="detail-val text-base font-bold text-hp-green-dark dark:text-[#81c784]" id="modalNighttimePrice"></span>
                        </div>
                        <div class="detail-item flex flex-col gap-1">
                            <span class="detail-label text-xs font-semibold uppercase tracking-[0.05em] text-hp-text-muted">Day Aircon</span>
                            <span class="detail-val text-base font-bold text-hp-green-dark dark:text-[#81c784]" id="modalDayAircon"></span>
                        </div>
                        <div class="detail-item flex flex-col gap-1">
                            <span class="detail-label text-xs font-semibold uppercase tracking-[0.05em] text-hp-text-muted">Night Aircon</span>
                            <span class="detail-val text-base font-bold text-hp-green-dark dark:text-[#81c784]" id="modalNightAircon"></span>
                        </div>
                        <div class="detail-item flex flex-col gap-1">
                            <span class="detail-label text-xs font-semibold uppercase tracking-[0.05em] text-hp-text-muted">Additional / Head</span>
                            <span class="detail-val text-base font-bold text-hp-green-dark dark:text-[#81c784]" id="modalAddHead"></span>
                        </div>
                        <div class="detail-item flex flex-col gap-1">
                            <span class="detail-label text-xs font-semibold uppercase tracking-[0.05em] text-hp-text-muted">Capacity</span>
                            <span class="detail-val text-base font-bold text-hp-green-dark dark:text-[#81c784]" id="modalCapacity"></span>
                        </div>
                    </div>
                    <div class="amenity-detail-status-section">
                        <h4 class="m-0 mb-3 text-sm font-bold text-hp-text dark:text-[#c8e6c8]">Current Status & Active Reservations</h4>
                        <div id="modalStatusList" class="status-list flex flex-col gap-2"></div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end border-t border-[#e5e7eb] px-6 py-4 dark:border-glass-border">
                <button type="button" class="btn btn--secondary cursor-pointer rounded-lg border border-glass-border bg-glass px-4 py-2.5 text-sm font-semibold text-hp-text transition-all duration-200 hover:-translate-y-px hover:border-glass-border-strong hover:bg-glass-hover" id="closeAmenityDetailModalFooter">Close</button>
            </div>
        </div>
    </div>

    <x-staff_chatbot />
</body>
</html>
