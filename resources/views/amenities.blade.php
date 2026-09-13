<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Amenities & Real-Time Availability — Hinaguan Nature Park</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('storage/design_images/main_logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700|poppins:300,400,500,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite([
        'resources/css/app.css',
        'resources/css/amenities.css',
        'resources/css/chatbot.css',
        'resources/js/amenities.js',
        'resources/js/guest_chatbot.js'
    ])
</head>
<body class="antialiased min-h-screen bg-[#0b2418] text-white font-['Montserrat',system-ui,sans-serif] relative bg-cover bg-center bg-fixed before:fixed before:inset-0 before:bg-gradient-to-b before:from-[#0b2418]/90 before:via-[#0b2418]/80 before:to-[#0b2418]/95 before:pointer-events-none before:z-0" style="background-image: url('{{ asset('images/background.jpeg') }}')">

    <div class="fixed top-0 left-0 right-0 z-50" id="amSiteHeader">
        <div class="bg-[#c8a45d] py-1 {{ ($parkSettings->park_status ?? 'open') === 'closed' ? '!bg-red-100 border-b border-red-300' : '' }}">
            <div class="max-w-7xl mx-auto px-5 text-center">
                @if (($parkSettings->park_status ?? 'open') === 'closed')
                    <p class="m-0 text-xs font-medium text-red-800">
                        <span class="inline-flex items-center gap-1.5 py-0.5 px-2.5 rounded-full bg-red-600 text-white font-bold text-xs shadow-sm mr-1">
                            <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                            Park Closed
                        </span>
                        <span>{{ !empty($parkSettings->close_description) ? $parkSettings->close_description : 'The park is temporarily closed for maintenance.' }}</span>
                        &nbsp;|&nbsp; Call: {{ $parkSettings->contact_number ?? '0917 861 8383' }}
                    </p>
                @else
                    <p class="m-0 text-xs font-medium text-[#001a11]">
                        <strong>Now Open!</strong>
                        Daytime: Adult &#8369;{{ $parkSettings->daytime_adult_entrance_fee ?? 70 }} &middot; Child &#8369;{{ $parkSettings->daytime_child_entrance_fee ?? 50 }} &nbsp;|&nbsp;
                        Overnight: Adult &#8369;{{ $parkSettings->nighttime_adult_entrance_fee ?? 100 }} &nbsp;|&nbsp;
                        <a href="{{ route('reservation') }}" class="font-bold text-[#001a11] underline hover:text-black">Reserve Now</a>
                        &nbsp;&middot;&nbsp; Call: {{ $parkSettings->contact_number ?? '0917 861 8383' }}
                    </p>
                @endif
            </div>
        </div>
        <header class="bg-[#0b2418]/95 backdrop-blur-md py-2 shadow-[0_4px_20px_rgba(0,0,0,0.25)] border-b border-[rgba(200,164,93,0.2)]">
            <div class="max-w-7xl mx-auto px-5 flex items-center justify-between gap-5">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 no-underline group">
                    <span class="w-8 h-8 bg-[#c8a45d] rounded-full flex items-center justify-center text-[#0b2418] shrink-0 shadow-sm transition-transform group-hover:scale-105">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-1.5 2.5-4 5-4 8a4 4 0 108 0c0-3-2.5-5.5-4-8z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8M10 18h4"/></svg>
                    </span>
                    <span class="flex flex-col">
                        <span class="font-['Playfair_Display',Georgia,serif] font-semibold text-base text-[#c8a45d] leading-tight">Hinaguan Nature Park</span>
                        <span class="text-[0.58rem] tracking-wider uppercase text-white/65">Jasaan, Misamis Oriental</span>
                    </span>
                </a>
                <nav class="flex items-center gap-4">
                    <ul class="hidden min-[900px]:flex items-center gap-4 list-none m-0 p-0">
                        <li><a href="{{ route('home') }}#about" class="text-xs font-semibold uppercase tracking-wider text-white/85 transition hover:text-[#c8a45d] no-underline">About</a></li>
                        <li><a href="{{ route('amenities') }}" class="text-xs font-semibold uppercase tracking-wider text-[#c8a45d] no-underline">Amenities</a></li>
                        <li><a href="{{ route('home') }}#activities" class="text-xs font-semibold uppercase tracking-wider text-white/85 transition hover:text-[#c8a45d] no-underline">Activities</a></li>
                        <li><a href="{{ route('home') }}#rates" class="text-xs font-semibold uppercase tracking-wider text-white/85 transition hover:text-[#c8a45d] no-underline">Rates</a></li>
                        <li><a href="{{ route('home') }}#gallery" class="text-xs font-semibold uppercase tracking-wider text-white/85 transition hover:text-[#c8a45d] no-underline">Gallery</a></li>
                        <li><a href="{{ route('home') }}#directions" class="text-xs font-semibold uppercase tracking-wider text-white/85 transition hover:text-[#c8a45d] no-underline">Directions</a></li>
                    </ul>
                    <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider no-underline transition cursor-pointer bg-white/10 text-white hover:bg-white/20 border border-[rgba(200,164,93,0.4)]">
                        <svg class="h-3.5 w-3.5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                        Back to Home
                    </a>
                    <a href="{{ route('reservation') }}" class="inline-flex items-center justify-center rounded px-4 py-1.5 text-xs font-bold uppercase tracking-wider no-underline transition cursor-pointer bg-[#c8a45d] text-[#0b2418] hover:bg-[#d4b06a] shadow-md">Book Now</a>
                </nav>
            </div>
        </header>
    </div>

    <main class="relative z-10 pb-8" style="padding-top: calc(var(--am-header-offset, 4.8rem) + 0.75rem);">
        <div class="max-w-7xl mx-auto px-4">

            <!-- Filter Controls Toolbar -->
            <div class="mb-3.5 flex flex-wrap items-center justify-between gap-2.5 rounded-xl border border-[rgba(200,164,93,0.3)] bg-[#0b2418]/90 px-3 py-2 backdrop-blur-md shadow-md">
                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-1 rounded-lg border border-[rgba(200,164,93,0.35)] bg-white/10 px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-white transition hover:bg-white/20">
                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                        Back to Home
                    </a>
                    <div class="relative flex-1 md:w-48">
                        <input type="text" id="searchAmenities" placeholder="Search amenities..." class="w-full rounded-lg border border-[rgba(200,164,93,0.25)] bg-[#061810] px-3 py-1.5 pl-8 text-xs text-white placeholder-white/50 focus:border-[#c8a45d] focus:outline-none" />
                        <svg class="absolute left-2.5 top-2 h-3.5 w-3.5 text-white/50" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    </div>
                    <select id="timeSlotFilter" class="rounded-lg border border-[rgba(200,164,93,0.25)] bg-[#061810] px-2.5 py-1.5 text-xs text-white focus:border-[#c8a45d] focus:outline-none">
                        <option value="all">All Time Slots</option>
                        <option value="daytime">Daytime Available</option>
                        <option value="nighttime">Nighttime Available</option>
                    </select>
                    <select id="availabilityFilter" class="rounded-lg border border-[rgba(200,164,93,0.25)] bg-[#061810] px-2.5 py-1.5 text-xs text-white focus:border-[#c8a45d] focus:outline-none">
                        <option value="all">All Availability</option>
                        <option value="available">Available Now</option>
                        <option value="occupied">Occupied</option>
                        <option value="reserved">Reserved</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="clearFiltersBtn" class="cursor-pointer rounded-lg border border-[rgba(200,164,93,0.25)] bg-white/5 px-2.5 py-1.5 text-[0.65rem] font-bold uppercase tracking-wider text-white transition hover:bg-white/15">Reset</button>
                    <a href="{{ route('reservation') }}" class="inline-flex items-center justify-center rounded px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider no-underline transition cursor-pointer bg-[#c8a45d] text-[#0b2418] hover:bg-[#d4b06a] shadow-md">Reserve Facility</a>
                </div>
            </div>


            <!-- Amenities Grid (4 per row on desktop) -->
            <div class="occupancy-grid grid grid-cols-1 gap-3.5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                @forelse ($amenities as $amenity)
                    @php
                        $amenityOccupancy = $occupancyData[$amenity->id] ?? ['occupied' => [], 'reserved' => []];
                        $occupiedSlots = [];
                        foreach ($amenityOccupancy['occupied'] as $occupied) {
                            if (!empty($occupied['today_slots'])) {
                                foreach ($occupied['today_slots'] as $s) {
                                    $occupiedSlots[] = strtolower($s);
                                }
                            } else {
                                $timeSlot = strtolower($occupied['time_slot']);
                                if (str_contains($timeSlot, 'daytonight')) {
                                    $occupiedSlots[] = 'daytime';
                                    $occupiedSlots[] = 'nighttime';
                                } elseif (str_contains($timeSlot, 'nighttoday')) {
                                    $occupiedSlots[] = 'nighttime';
                                } elseif (str_contains($timeSlot, 'daytime')) {
                                    $occupiedSlots[] = 'daytime';
                                } elseif (str_contains($timeSlot, 'nighttime')) {
                                    $occupiedSlots[] = 'nighttime';
                                }
                            }
                        }

                        $reservedSlots = [];
                        foreach ($amenityOccupancy['reserved'] as $reserved) {
                            if (!empty($reserved['today_slots'])) {
                                foreach ($reserved['today_slots'] as $s) {
                                    $reservedSlots[] = strtolower($s);
                                }
                            } else {
                                $timeSlot = strtolower($reserved['time_slot']);
                                if (str_contains($timeSlot, 'daytonight')) {
                                    $reservedSlots[] = 'daytime';
                                    $reservedSlots[] = 'nighttime';
                                } elseif (str_contains($timeSlot, 'nighttoday')) {
                                    $reservedSlots[] = 'nighttime';
                                } elseif (str_contains($timeSlot, 'daytime')) {
                                    $reservedSlots[] = 'daytime';
                                } elseif (str_contains($timeSlot, 'nighttime')) {
                                    $reservedSlots[] = 'nighttime';
                                }
                            }
                        }

                        $occupiedSlots = array_values(array_unique($occupiedSlots));
                        $reservedSlots = array_values(array_unique($reservedSlots));
                        $unavailableSlots = array_values(array_unique(array_merge($occupiedSlots, $reservedSlots)));
                        $allSlots = ['daytime', 'nighttime'];
                        $availableSlots = array_values(array_diff($allSlots, $unavailableSlots));

                        $isOccupied = ! empty($amenityOccupancy['occupied']);
                        $isReserved = ! empty($amenityOccupancy['reserved']);

                        if ($isOccupied) {
                            $cardStatus = 'occupied';
                            $cardStatusLabel = 'Occupied';
                            $statusBadgeBg = 'bg-red-700/90';
                        } elseif ($isReserved) {
                            $cardStatus = 'reserved';
                            $cardStatusLabel = 'Reserved';
                            $statusBadgeBg = 'bg-amber-600/90';
                        } else {
                            $cardStatus = 'available';
                            $cardStatusLabel = 'Available';
                            $statusBadgeBg = 'bg-emerald-700/90';
                        }
                        $hasDay = in_array('daytime', $availableSlots);
                        $hasNight = in_array('nighttime', $availableSlots);
                    @endphp

                    <div class="occupancy-card group relative cursor-pointer overflow-hidden rounded-xl border border-[rgba(200,164,93,0.25)] bg-[#0b2418]/90 shadow-xl backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-[#c8a45d]"
                         data-amenity-id="{{ $amenity->id }}"
                         data-amenity-name="{{ strtolower($amenity->amenities_name) }}"
                         data-display-name="{{ e($amenity->amenities_name) }}"
                         data-daytime-price="₱{{ number_format($amenity->daytime_price, 2) }}"
                         data-nighttime-price="₱{{ number_format($amenity->nighttime_price, 2) }}"
                         data-is-aircon="{{ $amenity->benefits?->is_aircon ? '1' : '0' }}"
                         data-free-entrance="{{ $amenity->benefits?->free_entrance ? '1' : '0' }}"
                         data-free-pool="{{ $amenity->benefits?->free_pool ? '1' : '0' }}"
                         data-additional-per-head="{{ $amenity->additional_per_head ? '₱'.number_format($amenity->additional_per_head, 2) : 'N/A' }}"
                         data-min-cap="{{ $amenity->minimum_capacity ?? 'N/A' }}"
                         data-max-cap="{{ $amenity->maximum_capacity ?? 'N/A' }}"
                         data-description="{{ e($amenity->description ?? 'No description available for this amenity.') }}"
                         data-image-src="{{ $amenity->image ? asset('storage/' . $amenity->image) : '' }}"
                         data-occupied-json="{{ json_encode($amenityOccupancy['occupied']) }}"
                         data-reserved-json="{{ json_encode($amenityOccupancy['reserved']) }}"
                         data-available-slots="{{ implode(',', $availableSlots) }}"
                         data-unavailable-slots="{{ implode(',', $unavailableSlots) }}">

                        <!-- Card Image / Header -->
                        <div class="relative aspect-[4/3] w-full overflow-hidden bg-[#061810]">
                            @if ($amenity->image)
                                <img src="{{ asset('storage/' . $amenity->image) }}" alt="{{ $amenity->amenities_name }}" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                            @else
                                <div class="flex h-full w-full items-center justify-center text-white/40">
                                    <svg class="h-10 w-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                    </svg>
                                </div>
                            @endif

                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#061810]/80 to-transparent"></div>

                            <!-- Status Badge -->
                            <span class="absolute left-2.5 top-2.5 z-[5] inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[0.62rem] font-bold uppercase tracking-wider text-white shadow-md backdrop-blur-md {{ $statusBadgeBg }}">
                                <i class="h-1 w-1 rounded-full bg-white animate-pulse"></i>{{ $cardStatusLabel }}
                            </span>

                            @php
                                $allReservations = [];
                                if (!empty($amenityOccupancy['occupied'])) {
                                    foreach ($amenityOccupancy['occupied'] as $occupied) {
                                        $allReservations[] = [
                                            'id' => $occupied['reservation_id'],
                                            'type' => 'occupied',
                                            'label' => ($occupied['time_slot_label'] ?? $occupied['time_slot']),
                                            'guest_count' => ($occupied['guest_count'] ?? 0),
                                            'is_shared' => !empty($occupied['is_shared_group']),
                                            'total_amenities' => ($occupied['total_amenities_count'] ?? 0)
                                        ];
                                    }
                                }
                                if (!empty($amenityOccupancy['reserved'])) {
                                    foreach ($amenityOccupancy['reserved'] as $reserved) {
                                        $allReservations[] = [
                                            'id' => $reserved['reservation_id'],
                                            'type' => 'reserved',
                                            'label' => ($reserved['time_slot_label'] ?? $reserved['time_slot']),
                                            'is_shared' => !empty($reserved['is_shared_group']),
                                            'total_amenities' => ($reserved['total_amenities_count'] ?? 0)
                                        ];
                                    }
                                }
                                $overflowCount = count($allReservations) - 4;
                            @endphp

                            {{-- Occupied circles at top right (Red) --}}
                            @if (!empty($amenityOccupancy['occupied']))
                                @foreach ($amenityOccupancy['occupied'] as $index => $occupied)
                                    @if ($index < 2)
                                        <div class="absolute top-2.5 {{ $index === 0 ? 'right-2.5' : 'right-12' }} z-10 flex h-9 w-9 items-center justify-center rounded-full border-2 border-white bg-[#dc2626] text-[0.72rem] font-bold text-white shadow-lg transition-transform duration-200 hover:scale-110"
                                             title="Occupied: #{{ $occupied['reservation_id'] }} ({{ $occupied['time_slot_label'] ?? $occupied['time_slot'] }}) - {{ $occupied['guest_count'] ?? 0 }} inside{{ !empty($occupied['is_shared_group']) ? ' [Shared Group]' : '' }}">
                                            #{{ $occupied['reservation_id'] }}
                                        </div>
                                    @endif
                                @endforeach
                            @endif

                            {{-- Reserved circles at bottom right (Amber/Gold) --}}
                            @if (!empty($amenityOccupancy['reserved']))
                                @foreach ($amenityOccupancy['reserved'] as $index => $reserved)
                                    @if ($index < 2)
                                        <div class="absolute bottom-2.5 {{ $index === 0 ? 'right-2.5' : 'right-12' }} z-10 flex h-9 w-9 items-center justify-center rounded-full border-2 border-white bg-[#c8a45d] text-[0.72rem] font-bold text-white shadow-lg transition-transform duration-200 hover:scale-110"
                                             title="Reserved: #{{ $reserved['reservation_id'] }} ({{ $reserved['time_slot_label'] ?? $reserved['time_slot'] }}){{ !empty($reserved['is_shared_group']) ? ' [Shared Group]' : '' }}">
                                            #{{ $reserved['reservation_id'] }}
                                        </div>
                                    @endif
                                @endforeach
                            @endif

                            {{-- Overflow indicator if more than 4 total --}}
                            @if ($overflowCount > 0)
                                <div class="absolute bottom-2.5 right-12 z-10 flex h-9 w-9 items-center justify-center rounded-full border-2 border-white bg-gray-600 text-[0.68rem] font-bold text-white shadow-lg"
                                     title="+{{ $overflowCount }} more reservations">
                                    +{{ $overflowCount }}
                                </div>
                            @endif
                        </div>

                        <!-- Card Content -->
                        <div class="relative flex flex-col gap-2.5 p-3.5">
                            <div class="flex items-start justify-between gap-2">
                                <h4 class="m-0 font-display text-base font-bold leading-tight text-white line-clamp-1">{{ $amenity->amenities_name }}</h4>
                                <span class="shrink-0 text-xs font-bold text-[#c8a45d]">₱{{ number_format($amenity->daytime_price, 2) }}<small class="text-[0.6rem] font-medium text-white/60">/day</small></span>
                            </div>

                            <!-- Availability Slot Chips & Benefits -->
                            <div class="flex flex-wrap gap-1.5">
                                <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[0.62rem] font-bold uppercase tracking-wider {{ $hasDay ? 'border-emerald-500/40 bg-emerald-950/70 text-emerald-300' : 'border-red-500/40 bg-red-950/70 text-red-300' }}">
                                    <i class="h-1 w-1 rounded-full {{ $hasDay ? 'bg-emerald-400' : 'bg-red-400' }}"></i>Daytime
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[0.62rem] font-bold uppercase tracking-wider {{ $hasNight ? 'border-emerald-500/40 bg-emerald-950/70 text-emerald-300' : 'border-red-500/40 bg-red-950/70 text-red-300' }}">
                                    <i class="h-1 w-1 rounded-full {{ $hasNight ? 'bg-emerald-400' : 'bg-red-400' }}"></i>Nighttime
                                </span>
                                @if($amenity->benefits?->is_aircon)
                                    <span class="inline-flex items-center gap-1 rounded-full border border-cyan-500/40 bg-cyan-950/70 px-2 py-0.5 text-[0.62rem] font-bold text-cyan-300">
                                        <i class="bi bi-snow"></i> Aircon
                                    </span>
                                @endif
                                @if($amenity->benefits?->free_pool)
                                    <span class="inline-flex items-center gap-1 rounded-full border border-blue-500/40 bg-blue-950/70 px-2 py-0.5 text-[0.62rem] font-bold text-blue-300">
                                        <i class="bi bi-water"></i> Free Pool
                                    </span>
                                @endif
                                @if($amenity->benefits?->free_entrance)
                                    <span class="inline-flex items-center gap-1 rounded-full border border-emerald-500/40 bg-emerald-950/70 px-2 py-0.5 text-[0.62rem] font-bold text-emerald-300">
                                        <i class="bi bi-ticket-perforated-fill"></i> Free Entrance
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center justify-between border-t border-[rgba(200,164,93,0.15)] pt-2 text-[0.7rem] text-white/70">
                                <span class="inline-flex items-center gap-1 font-medium">
                                    <svg class="h-3.5 w-3.5 text-[#c8a45d]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                    {{ $amenity->minimum_capacity }}–{{ $amenity->maximum_capacity }} pax
                                </span>
                                <span class="font-semibold text-white/80">Night ₱{{ number_format($amenity->nighttime_price, 2) }}</span>
                            </div>

                            <div class="flex items-center justify-between text-[0.68rem] font-bold text-[#c8a45d] transition-colors group-hover:text-white">
                                <span>View details</span>
                                <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/></svg>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-[rgba(200,164,93,0.3)] bg-[#0b2418]/80 py-12 text-center text-white/70">
                        <p class="text-sm font-semibold">No amenities found</p>
                    </div>
                @endforelse
            </div>
        </div>
    </main>

    <!-- Amenity Detail Modal -->
    <div class="am-modal fixed inset-0 z-[120] flex items-center justify-center p-4 opacity-0 transition-all duration-250 invisible is-open:visible is-open:opacity-100" id="infoModal" aria-hidden="true">
        <div class="fixed inset-0 bg-black/75 backdrop-blur-md" id="closeAmenityDetailModal"></div>
        <div class="relative z-10 flex max-h-[88vh] w-full max-w-[580px] flex-col overflow-hidden rounded-xl border border-[rgba(200,164,93,0.4)] bg-[#0b2418] text-white shadow-2xl">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-[rgba(200,164,93,0.2)] px-5 py-3.5 bg-[#061810]">
                <div>
                    <span class="text-[0.62rem] font-bold uppercase tracking-widest text-[#c8a45d]">Facility Details</span>
                    <h3 class="m-0 font-display text-lg font-bold text-white" id="infoModalTitle">Amenity Details</h3>
                </div>
                <button type="button" class="cursor-pointer text-xl font-bold text-white/60 hover:text-white" id="closeAmenityDetailModalBtn">&times;</button>
            </div>

            <!-- Modal Body -->
            <div class="flex flex-col gap-4 overflow-y-auto p-5">
                <div class="h-[180px] w-full overflow-hidden rounded-lg bg-[#061810] border border-[rgba(200,164,93,0.2)]">
                    <img id="infoModalImage" src="" alt="Amenity Image" class="h-full w-full object-cover" style="display:none;">
                    <div id="infoModalImgPlaceholder" class="flex h-full w-full items-center justify-center text-white/40" style="display:none;">
                        <span>No Image Available</span>
                    </div>
                </div>

                <p class="m-0 text-xs leading-relaxed text-white/85" id="infoModalDescription"></p>

                <!-- Included Benefits Badges -->
                <div id="infoModalBenefitsWrap" class="flex flex-wrap items-center gap-1.5"></div>

                <!-- Pricing & Details Grid -->
                <div class="grid grid-cols-2 gap-3 rounded-lg border border-[rgba(200,164,93,0.2)] bg-[#061810] p-3.5">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[0.6rem] font-bold uppercase tracking-wider text-white/50">Daytime Price</span>
                        <span class="text-sm font-bold text-[#c8a45d]" id="infoModalDayPrice"></span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[0.6rem] font-bold uppercase tracking-wider text-white/50">Nighttime Price</span>
                        <span class="text-sm font-bold text-[#c8a45d]" id="infoModalNightPrice"></span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[0.6rem] font-bold uppercase tracking-wider text-white/50">Add'l / Head</span>
                        <span class="text-xs font-semibold text-white/90" id="infoModalAddHead"></span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[0.6rem] font-bold uppercase tracking-wider text-white/50">Capacity</span>
                        <span class="text-xs font-semibold text-white/90" id="infoModalCapacity"></span>
                    </div>
                </div>

                <!-- Active Status Section -->
                <div>
                    <h4 class="mb-2.5 text-xs font-bold text-white">Current Status & Active Bookings Today</h4>
                    <div id="modalStatusList" class="flex flex-col gap-2"></div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-between border-t border-[rgba(200,164,93,0.2)] px-5 py-3 bg-[#061810]">
                <button type="button" class="cursor-pointer inline-flex items-center gap-1.5 rounded-lg border border-[rgba(200,164,93,0.35)] bg-white/10 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-white transition hover:bg-white/20" id="closeAmenityDetailModalFooter">
                    <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                    Back
                </button>
            </div>

        </div>
    </div>

    <footer class="relative z-10 bg-[#001a11]/95 text-white/55 p-5 text-center text-xs border-t border-[rgba(200,164,93,0.2)]">
        <p class="m-0">&copy; {{ date('Y') }} <strong class="text-[#c8a45d]">Hinaguan Nature Park</strong>. All rights reserved.</p>
    </footer>

    <x-guest_chatbot />
</body>
</html>
