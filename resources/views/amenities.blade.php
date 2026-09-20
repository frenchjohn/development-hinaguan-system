<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Amenities &amp; Real-Time Availability — Hinaguan Nature Park</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('storage/design_images/main_logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite([
        'resources/css/app.css',
        'resources/css/amenities.css',
        'resources/css/chatbot.css',
        'resources/js/amenities.js',
        'resources/js/guest_chatbot.js'
    ])
</head>
<body class="antialiased min-h-screen bg-white text-gray-800 font-['Montserrat',sans-serif] selection:bg-[#a3e635] selection:text-[#06190f] relative overflow-x-hidden">

    {{-- Fixed Site Header (Matching Homepage Header Design) --}}
    <header class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 bg-[#061a10] border-b border-emerald-500/20 shadow-md" id="amSiteHeader">
        @if (($parkSettings->park_status ?? 'open') === 'closed')
            {{-- Park Closed Notice Bar --}}
            <div class="bg-red-900/90 text-red-100 text-xs py-1 px-4 text-center border-b border-red-700/50 flex items-center justify-center gap-2">
                <span class="inline-flex items-center gap-1 bg-red-600 text-white font-bold text-[10px] px-2 py-0.5 rounded-full uppercase tracking-wider">
                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> Park Closed
                </span>
                <span>{{ $parkSettings->close_description ?: 'The park is temporarily closed today for maintenance or weather safety.' }}</span>
                <span class="opacity-75">| Inquiries: {{ $parkSettings->contact_number ?? '0917 861 8383' }}</span>
            </div>
        @endif

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 h-20 flex items-center justify-between">
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-3 group no-underline">
                <div class="w-11 h-11 rounded-full overflow-hidden border-2 border-emerald-400/50 shadow-[0_0_12px_rgba(74,222,128,0.25)] shrink-0 group-hover:border-emerald-300 transition-colors">
                    <img src="{{ asset('storage/design_images/main_logo.jpeg') }}" alt="Hinaguan Nature Park Logo" class="w-full h-full object-cover">
                </div>
                <div class="flex flex-col">
                    <span class="font-serif text-lg sm:text-xl font-bold text-white tracking-wide leading-tight group-hover:text-emerald-200 transition-colors">Hinaguan Nature Park</span>
                    <span class="text-[9px] sm:text-[10px] font-bold tracking-[0.2em] text-emerald-400 uppercase">JASAAN, MISAMIS ORIENTAL</span>
                </div>
            </a>

            {{-- Action Buttons: Only Back and Book button --}}
            <div class="flex items-center gap-3 sm:gap-4">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 border border-emerald-400/30 hover:border-emerald-400/60 bg-white/10 hover:bg-white/15 text-white font-semibold text-xs tracking-wider uppercase px-4 sm:px-5 py-2.5 rounded-full transition-all no-underline shadow-sm">
                    <i class="bi bi-arrow-left text-sm"></i>
                    <span>Back</span>
                </a>
                <a href="{{ route('reservation') }}" class="inline-flex items-center justify-center bg-[#a3e635] hover:bg-[#bef264] text-[#082214] font-extrabold text-xs tracking-wider uppercase px-5 sm:px-6 py-2.5 rounded-full shadow-[0_2px_15px_rgba(163,230,53,0.35)] transition-all transform hover:scale-105 active:scale-95 no-underline">
                    BOOK NOW
                </a>
            </div>
        </div>
    </header>

    <main class="relative z-10 pb-16 bg-white" style="padding-top: calc(var(--am-header-offset, 5rem) + 1.5rem);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Page Header --}}
            <div class="mb-8 text-center max-w-2xl mx-auto pt-2">
                <span class="text-xs font-bold tracking-[0.22em] text-[#1b5e3a] uppercase block mb-1.5">
                    PARK FACILITIES
                </span>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-serif font-bold text-[#0f2d1e] tracking-tight mb-2.5">
                    Amenities &amp; Availability
                </h1>
                <p class="text-gray-600 text-sm sm:text-base leading-relaxed">
                    Explore our cottages, function spaces, and scenic shelters. Check real-time availability and plan your ideal visit.
                </p>
            </div>

            <!-- Filter Controls Toolbar -->
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-gray-50/90 px-4 py-3 shadow-sm backdrop-blur-sm">
                <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                    <div class="relative flex-1 sm:w-56">
                        <input type="text" id="searchAmenities" placeholder="Search amenities..." class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 pl-9 text-xs text-gray-800 placeholder-gray-400 focus:border-[#1b5e3a] focus:ring-1 focus:ring-[#1b5e3a] focus:outline-none transition-all shadow-sm" />
                        <svg class="absolute left-3 top-2.5 h-3.5 w-3.5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    </div>
                    <select id="categoryFilter" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs text-gray-700 focus:border-[#1b5e3a] focus:outline-none transition-all shadow-sm">
                        <option value="all">All Categories</option>
                        <option value="a-houses">A-Houses</option>
                        <option value="cottages">Cottages</option>
                        <option value="function-hall">Function Hall</option>
                        <option value="payags">Payags</option>
                    </select>
                    <select id="timeSlotFilter" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs text-gray-700 focus:border-[#1b5e3a] focus:outline-none transition-all shadow-sm">
                        <option value="all">All Time Slots</option>
                        <option value="daytime">Daytime Available</option>
                        <option value="nighttime">Nighttime Available</option>
                    </select>
                    <select id="availabilityFilter" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs text-gray-700 focus:border-[#1b5e3a] focus:outline-none transition-all shadow-sm">
                        <option value="all">All Availability</option>
                        <option value="available">Available Now</option>
                        <option value="occupied">Occupied</option>
                        <option value="reserved">Reserved</option>
                    </select>
                </div>
                <div class="flex items-center gap-2.5">
                    <button type="button" id="clearFiltersBtn" class="cursor-pointer rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold uppercase tracking-wider text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 shadow-sm">Reset</button>
                    <a href="{{ route('reservation') }}" class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-xs font-bold uppercase tracking-wider no-underline transition cursor-pointer bg-[#1b5e3a] text-white hover:bg-[#14472b] shadow-sm">Reserve Facility</a>
                </div>
            </div>

            @php
                $categoryConfig = [
                    'a-houses' => [
                        'title' => 'A-Houses',
                        'subtitle' => 'Cozy modern A-frame cottages and overnight shelters.',
                        'icon' => 'bi-triangle',
                        'items' => collect(),
                    ],
                    'cottages' => [
                        'title' => 'Cottages',
                        'subtitle' => 'Spacious open and sheltered cottages for families and gatherings.',
                        'icon' => 'bi-houses',
                        'items' => collect(),
                    ],
                    'function-hall' => [
                        'title' => 'Function Hall',
                        'subtitle' => 'Grand covered space perfect for celebrations, reunions, and events.',
                        'icon' => 'bi-building',
                        'items' => collect(),
                    ],
                    'payags' => [
                        'title' => 'Payags',
                        'subtitle' => 'Traditional open-air nipa cottages for relaxation and picnics.',
                        'icon' => 'bi-house-door',
                        'items' => collect(),
                    ],
                    'other' => [
                        'title' => 'Other Facilities',
                        'subtitle' => 'Additional park amenities and spaces.',
                        'icon' => 'bi-tree',
                        'items' => collect(),
                    ],
                ];

                foreach ($amenities as $amenity) {
                    $lowerName = strtolower($amenity->amenities_name);
                    if (str_contains($lowerName, 'payag')) {
                        $categoryConfig['payags']['items']->push($amenity);
                    } elseif (str_contains($lowerName, 'a-house') || str_contains($lowerName, 'ahouse') || str_contains($lowerName, 'a house')) {
                        $categoryConfig['a-houses']['items']->push($amenity);
                    } elseif (str_contains($lowerName, 'cottage')) {
                        $categoryConfig['cottages']['items']->push($amenity);
                    } elseif (str_contains($lowerName, 'hall') || str_contains($lowerName, 'function')) {
                        $categoryConfig['function-hall']['items']->push($amenity);
                    } else {
                        $categoryConfig['other']['items']->push($amenity);
                    }
                }

                // Natural sort in each category so Payag 1..6, A-House 1..8, Cottage 1..6 are strictly ordered
                foreach ($categoryConfig as $catKey => &$catInfo) {
                    $catInfo['items'] = $catInfo['items']->sort(function ($a, $b) {
                        return strnatcasecmp($a->amenities_name, $b->amenities_name);
                    })->values();
                }
                unset($catInfo);
            @endphp

            <!-- Categorized Amenities Sections -->
            <div id="amenitiesContainer" class="space-y-12">
                @foreach ($categoryConfig as $catKey => $catData)
                    @if ($catData['items']->isNotEmpty())
                        <section class="amenity-category-section" data-category="{{ $catKey }}">
                            <!-- Category Header -->
                            <div class="flex items-center justify-between mb-5 border-b border-gray-200 pb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#1b5e3a] flex items-center justify-center text-lg border border-emerald-200/60 shadow-sm shrink-0">
                                        <i class="bi {{ $catData['icon'] }}"></i>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2.5">
                                            <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f2d1e]">{{ $catData['title'] }}</h2>
                                            <span class="rounded-full bg-emerald-100 text-[#1b5e3a] text-[0.68rem] font-bold px-2.5 py-0.5">{{ $catData['items']->count() }} units</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $catData['subtitle'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Amenities Grid -->
                            <div class="occupancy-grid grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                @foreach ($catData['items'] as $amenity)
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
                                            $statusBadgeBg = 'bg-red-600 text-white';
                                        } elseif ($isReserved) {
                                            $cardStatus = 'reserved';
                                            $cardStatusLabel = 'Reserved';
                                            $statusBadgeBg = 'bg-amber-500 text-white';
                                        } else {
                                            $cardStatus = 'available';
                                            $cardStatusLabel = 'Available';
                                            $statusBadgeBg = 'bg-emerald-600 text-white';
                                        }
                                        $hasDay = in_array('daytime', $availableSlots);
                                        $hasNight = in_array('nighttime', $availableSlots);
                                    @endphp

                                    <div class="occupancy-card group relative cursor-pointer overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-emerald-500/40 flex flex-col justify-between"
                                         data-amenity-id="{{ $amenity->id }}"
                                         data-amenity-name="{{ strtolower($amenity->amenities_name) }}"
                                         data-display-name="{{ e($amenity->amenities_name) }}"
                                         data-category="{{ $catKey }}"
                                         data-category-name="{{ $catData['title'] }}"
                                         data-status-badge="{{ $cardStatus }}"
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
                                        <div class="relative aspect-[4/3] w-full overflow-hidden bg-gray-100">
                                            @if ($amenity->image)
                                                <img src="{{ asset('storage/' . $amenity->image) }}" alt="{{ $amenity->amenities_name }}" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />
                                                <div class="flex h-full w-full items-center justify-center text-gray-400 bg-gray-100" style="display:none;">
                                                    <i class="bi bi-image text-3xl text-gray-300"></i>
                                                </div>
                                            @else
                                                <div class="flex h-full w-full items-center justify-center text-gray-400 bg-gray-100">
                                                    <i class="bi bi-image text-3xl text-gray-300"></i>
                                                </div>
                                            @endif

                                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>

                                            <!-- Status Badge -->
                                            <span class="absolute left-2.5 top-2.5 z-[5] inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[0.62rem] font-bold uppercase tracking-wider shadow-md backdrop-blur-md {{ $statusBadgeBg }}">
                                                <span class="h-1.5 w-1.5 rounded-full bg-white animate-pulse"></span>{{ $cardStatusLabel }}
                                            </span>

                                            {{-- Occupied circles at top right (Red) --}}
                                            @if (!empty($amenityOccupancy['occupied']))
                                                @foreach ($amenityOccupancy['occupied'] as $index => $occupied)
                                                    @if ($index < 2)
                                                        <div class="absolute top-2.5 {{ $index === 0 ? 'right-2.5' : 'right-12' }} z-10 flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-red-600 text-[0.7rem] font-bold text-white shadow-lg transition-transform duration-200 hover:scale-110"
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
                                                        <div class="absolute bottom-2.5 {{ $index === 0 ? 'right-2.5' : 'right-12' }} z-10 flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-amber-500 text-[0.7rem] font-bold text-white shadow-lg transition-transform duration-200 hover:scale-110"
                                                             title="Reserved: #{{ $reserved['reservation_id'] }} ({{ $reserved['time_slot_label'] ?? $reserved['time_slot'] }}){{ !empty($reserved['is_shared_group']) ? ' [Shared Group]' : '' }}">
                                                            #{{ $reserved['reservation_id'] }}
                                                        </div>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </div>

                                        <!-- Card Content -->
                                        <div class="relative flex flex-col gap-2.5 p-4 flex-grow justify-between">
                                            <div>
                                                <div class="mb-1">
                                                    <span class="text-[0.62rem] font-bold tracking-wider uppercase text-emerald-700 block">{{ $catData['title'] }}</span>
                                                    <div class="flex items-start justify-between gap-2 mt-0.5">
                                                        <h4 class="m-0 font-serif text-base font-bold leading-tight text-[#0f2d1e] line-clamp-1">{{ $amenity->amenities_name }}</h4>
                                                        <span class="shrink-0 text-xs font-bold text-[#1b5e3a]">₱{{ number_format($amenity->daytime_price, 2) }}<small class="text-[0.65rem] font-medium text-gray-500">/day</small></span>
                                                    </div>
                                                </div>

                                                <!-- Availability Slot Chips & Benefits -->
                                                <div class="flex flex-wrap gap-1.5 mb-2.5">
                                                    <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[0.62rem] font-bold uppercase tracking-wider {{ $hasDay ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-700' }}">
                                                        <i class="h-1.5 w-1.5 rounded-full {{ $hasDay ? 'bg-emerald-600' : 'bg-red-500' }}"></i>Daytime
                                                    </span>
                                                    <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[0.62rem] font-bold uppercase tracking-wider {{ $hasNight ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-700' }}">
                                                        <i class="h-1.5 w-1.5 rounded-full {{ $hasNight ? 'bg-emerald-600' : 'bg-red-500' }}"></i>Nighttime
                                                    </span>
                                                    @if($amenity->benefits?->is_aircon)
                                                        <span class="inline-flex items-center gap-1 rounded-full border border-cyan-200 bg-cyan-50 px-2 py-0.5 text-[0.62rem] font-bold text-cyan-800">
                                                            <i class="bi bi-snow"></i> Aircon
                                                        </span>
                                                    @endif
                                                    @if($amenity->benefits?->free_pool)
                                                        <span class="inline-flex items-center gap-1 rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-[0.62rem] font-bold text-blue-800">
                                                            <i class="bi bi-water"></i> Free Pool
                                                        </span>
                                                    @endif
                                                    @if($amenity->benefits?->free_entrance)
                                                        <span class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[0.62rem] font-bold text-emerald-800">
                                                            <i class="bi bi-ticket-perforated-fill"></i> Free Entrance
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div>
                                                <div class="flex items-center justify-between border-t border-gray-100 pt-2.5 text-xs text-gray-500">
                                                    <span class="inline-flex items-center gap-1 font-medium">
                                                        <i class="bi bi-people text-[#1b5e3a]"></i>
                                                        {{ $amenity->minimum_capacity }}–{{ $amenity->maximum_capacity }} pax
                                                    </span>
                                                    <span class="font-semibold text-gray-700">Night ₱{{ number_format($amenity->nighttime_price, 2) }}</span>
                                                </div>

                                                <div class="flex items-center justify-between text-xs font-bold text-[#1b5e3a] transition-colors group-hover:text-[#0f2d1e] pt-2">
                                                    <span>View details</span>
                                                    <i class="bi bi-arrow-right transition-transform group-hover:translate-x-1"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                @endforeach
            </div>

            <!-- No Results State -->
            <div id="noResultsState" class="hidden flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-gray-300 bg-gray-50 py-16 text-center text-gray-500 my-8">
                <i class="bi bi-inbox text-3xl text-gray-400"></i>
                <p class="text-sm font-semibold text-gray-700">No matching amenities found</p>
                <p class="text-xs text-gray-400">Try adjusting your filters or search keywords.</p>
            </div>
        </div>
    </main>

    <!-- Amenity Detail Modal (White Theme: Left Image, Right Info, No Book Button) -->
    <div class="am-modal fixed inset-0 z-[120] flex items-center justify-center p-3 sm:p-5 opacity-0 transition-all duration-200 invisible is-open:visible is-open:opacity-100" id="infoModal" aria-hidden="true">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" id="closeAmenityDetailModal"></div>
        <div class="relative z-10 flex max-h-[92vh] w-full max-w-4xl flex-col md:flex-row overflow-hidden rounded-3xl border border-gray-200 bg-white text-gray-800 shadow-2xl">
            
            <!-- Left Side: Large Image -->
            <div class="relative w-full md:w-1/2 min-h-[240px] sm:min-h-[280px] md:min-h-[460px] bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                <img id="infoModalImage" src="" alt="Amenity Image" class="h-full w-full object-cover object-center absolute inset-0" />
                <div id="infoModalImgPlaceholder" class="flex flex-col items-center justify-center gap-2 text-gray-400 p-6 text-center" style="display:none;">
                    <i class="bi bi-image text-4xl text-gray-300"></i>
                    <span class="text-xs font-semibold">No Image Available</span>
                </div>
                <!-- Status Badge Overlay on top of image -->
                <div id="infoModalStatusBadge" class="absolute top-4 left-4 z-10"></div>
            </div>

            <!-- Right Side: Info & Details -->
            <div class="flex flex-col justify-between w-full md:w-1/2 overflow-hidden bg-white">
                <!-- Modal Header -->
                <div class="flex items-start justify-between border-b border-gray-100 px-6 pt-5 pb-3">
                    <div>
                        <span class="text-[0.68rem] font-bold uppercase tracking-widest text-[#1b5e3a] block mb-1" id="infoModalCategory">PARK FACILITY</span>
                        <h3 class="m-0 font-serif text-xl sm:text-2xl font-bold text-[#0f2d1e] leading-tight" id="infoModalTitle">Amenity Details</h3>
                    </div>
                    <button type="button" class="cursor-pointer w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-900 flex items-center justify-center transition-colors text-xl font-bold shrink-0 ml-3" id="closeAmenityDetailModalBtn" aria-label="Close modal">&times;</button>
                </div>

                <!-- Modal Body Scrollable Content -->
                <div class="flex flex-col gap-4 overflow-y-auto px-6 py-4 flex-1 custom-scrollbar max-h-[calc(92vh-140px)] md:max-h-[500px]">
                    <p class="m-0 text-xs sm:text-sm leading-relaxed text-gray-600" id="infoModalDescription"></p>

                    <!-- Included Benefits Badges -->
                    <div id="infoModalBenefitsWrap" class="flex flex-wrap items-center gap-1.5"></div>

                    <!-- Pricing & Details Grid -->
                    <div class="grid grid-cols-2 gap-2.5 rounded-2xl border border-gray-200 bg-gray-50/80 p-3.5">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-gray-500">Daytime Price</span>
                            <span class="text-base font-bold text-[#1b5e3a]" id="infoModalDayPrice"></span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-gray-500">Nighttime Price</span>
                            <span class="text-base font-bold text-[#1b5e3a]" id="infoModalNightPrice"></span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-gray-500">Add'l / Head</span>
                            <span class="text-xs font-semibold text-gray-800" id="infoModalAddHead"></span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[0.62rem] font-bold uppercase tracking-wider text-gray-500">Capacity</span>
                            <span class="text-xs font-semibold text-gray-800" id="infoModalCapacity"></span>
                        </div>
                    </div>

                    <!-- Active Status Section -->
                    <div>
                        <h4 class="mb-2 text-xs font-bold text-gray-700 uppercase tracking-wider">Today's Schedule &amp; Bookings</h4>
                        <div id="modalStatusList" class="flex flex-col gap-2"></div>
                    </div>
                </div>

                <!-- Modal Footer: Only Back/Close button, NO book facility button -->
                <div class="flex items-center justify-end border-t border-gray-100 px-6 py-3.5 bg-gray-50/70">
                    <button type="button" class="cursor-pointer inline-flex items-center gap-1.5 rounded-full border border-gray-300 bg-white hover:bg-gray-100 px-5 py-2 text-xs font-bold uppercase tracking-wider text-gray-700 transition shadow-sm" id="closeAmenityDetailModalFooter">
                        <i class="bi bi-x-lg text-xs"></i>
                        <span>Close</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <footer class="relative z-10 bg-[#06190f] text-white/70 py-10 px-5 text-center text-xs border-t border-emerald-500/20">
        <p class="m-0">&copy; {{ date('Y') }} <strong class="text-emerald-300">Hinaguan Nature Park</strong>. All rights reserved.</p>
    </footer>

    <x-guest_chatbot />
</body>
</html>
