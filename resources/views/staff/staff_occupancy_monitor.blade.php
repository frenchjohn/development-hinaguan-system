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
        'resources/css/homepage.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/staff_sidemenu.css',
        'resources/css/staff_css/staff_occupancy_monitor.css',
        'resources/css/chatbot.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/staff_js/staff_occupancy_monitor.js',
        'resources/js/staff_chatbot.js',
    ])
</head>
<body class="antialiased staff-portal s-occ-page">
    <div class="dash-layout">
        <x-staff_sidemenu active="occupancy-monitor" userName="{{ session('auth_user.name') ?? 'Staff User' }}" userRole="Staff" />

        <div class="dash-main">

            <main class="dash-content">
                <x-header
                    title="Occupancy Monitor"
                    subtitle="Real-time view of all amenities and their availability"
                />

                {{-- Live status strip --}}
                <div class="occupancy-stats">
                    <article class="occupancy-stat">
                        <span class="occupancy-stat__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
                        </span>
                        <div class="occupancy-stat__body">
                            <p class="occupancy-stat__value" data-count="{{ $totalAmenities }}">{{ $totalAmenities }}</p>
                            <p class="occupancy-stat__label">Amenities</p>
                        </div>
                    </article>
                    <article class="occupancy-stat occupancy-stat--occupied">
                        <span class="occupancy-stat__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                        </span>
                        <div class="occupancy-stat__body">
                            <p class="occupancy-stat__value" data-count="{{ $occupiedCount }}">{{ $occupiedCount }}</p>
                            <p class="occupancy-stat__label">Occupied Now</p>
                            <p class="occupancy-stat__hint">{{ $occupiedReservations }} active reservation{{ $occupiedReservations === 1 ? '' : 's' }}</p>
                        </div>
                    </article>
                    <article class="occupancy-stat occupancy-stat--reserved">
                        <span class="occupancy-stat__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div class="occupancy-stat__body">
                            <p class="occupancy-stat__value" data-count="{{ $reservedCount }}">{{ $reservedCount }}</p>
                            <p class="occupancy-stat__label">Reserved Today</p>
                        </div>
                    </article>
                    <article class="occupancy-stat occupancy-stat--available">
                        <span class="occupancy-stat__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div class="occupancy-stat__body">
                            <p class="occupancy-stat__value" data-count="{{ $availableCount }}">{{ $availableCount }}</p>
                            <p class="occupancy-stat__label">Available Now</p>
                        </div>
                    </article>
                    <article class="occupancy-stat occupancy-stat--rate">
                        <div class="occupancy-rate-ring" style="--pct: {{ $occupancyRate }}">
                            <span>{{ $occupancyRate }}%</span>
                        </div>
                        <div class="occupancy-stat__body">
                            <p class="occupancy-stat__label">Occupancy Rate</p>
                            <p class="occupancy-stat__hint">{{ $inUseCount }} of {{ $totalAmenities }} in use</p>
                        </div>
                    </article>
                </div>

                <div class="occupancy-legend">
                    <span class="occupancy-legend__live"><i class="occupancy-legend__pulse"></i> Live</span>
                    <span class="occupancy-legend__item"><i class="occupancy-legend__dot occupancy-legend__dot--occupied"></i>Occupied</span>
                    <span class="occupancy-legend__item"><i class="occupancy-legend__dot occupancy-legend__dot--reserved"></i>Reserved today</span>
                    <span class="occupancy-legend__item"><i class="occupancy-legend__dot occupancy-legend__dot--available"></i>Available</span>
                    <span class="occupancy-legend__hint">Click any amenity card for full details</span>
                </div>

                <div class="occupancy-monitor__filter-toggle">
                        <button type="button" id="filterToggleBtn" class="filter-toggle-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                            </svg>
                            Filters
                        </button>
                    </div>

                    <div class="occupancy-monitor__filter-panel" id="filterPanel">
                        <div class="filter-panel__row">
                            <div class="filter-panel__field">
                                <label for="searchAmenities">Search Amenities</label>
                                <input type="text" id="searchAmenities" placeholder="Search by name..." />
                            </div>
                            <div class="filter-panel__field">
                                <label for="timeSlotFilter">Time Slot</label>
                                <select id="timeSlotFilter">
                                    <option value="all">All Time Slots</option>
                                    <option value="daytime">Daytime</option>
                                    <option value="nighttime">Nighttime</option>
                                    <option value="daytonight">Day to Night</option>
                                    <option value="nighttoday">Night to Day</option>
                                </select>
                            </div>
                            <div class="filter-panel__field">
                                <label for="availabilityFilter">Availability</label>
                                <select id="availabilityFilter">
                                    <option value="all">All</option>
                                    <option value="available">Available</option>
                                    <option value="unavailable">Unavailable</option>
                                </select>
                            </div>
                        </div>
                        <div class="filter-panel__actions">
                            <button type="button" id="clearFiltersBtn" class="filter-panel__secondary">Clear Filters</button>
                        </div>
                    </div>

                    <div class="occupancy-grid">
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
                            <div class="occupancy-card" 
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
                                <div class="occupancy-card__image">
                                    @if ($amenity->image)
                                        <img src="{{ asset('storage/' . $amenity->image) }}" alt="{{ $amenity->amenities_name }}" loading="lazy">
                                    @else
                                        <div class="occupancy-card__placeholder">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="occupancy-card__overlay"></div>
                                    <span class="occupancy-card__badge occupancy-card__badge--{{ $cardStatus }}">
                                        <i class="occupancy-card__badge-dot"></i>{{ $cardStatusLabel }}
                                    </span>
                                    @if (!empty($amenityOccupancy['occupied']))
                                        <div class="occupancy-card__status-overlay occupancy-card__status-overlay--occupied">
                                            @foreach ($amenityOccupancy['occupied'] as $occupied)
                                                <div class="occupancy-card__status-item">
                                                    <span class="occupancy-card__status-label">Occupied by:</span>
                                                    <span class="occupancy-card__status-id">#{{ $occupied['reservation_id'] }}</span>
                                                    <span class="occupancy-card__status-slot">{{ $occupied['time_slot'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if (!empty($amenityOccupancy['reserved']))
                                        <div class="occupancy-card__status-overlay occupancy-card__status-overlay--reserved">
                                            @foreach ($amenityOccupancy['reserved'] as $reserved)
                                                <div class="occupancy-card__status-item">
                                                    <span class="occupancy-card__status-label">Reserved today by:</span>
                                                    <span class="occupancy-card__status-id">#{{ $reserved['reservation_id'] }}</span>
                                                    <span class="occupancy-card__status-slot">{{ $reserved['time_slot'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="occupancy-card__content">
                                    <div class="occupancy-card__head">
                                        <h4 class="occupancy-card__name">{{ $amenity->amenities_name }}</h4>
                                        <span class="occupancy-card__rate">₱{{ number_format($amenity->daytime_price, 2) }}<small>/day</small></span>
                                    </div>
                                    <div class="occupancy-card__slots">
                                        <span class="slot-chip slot-chip--daytime {{ $hasDay ? 'is-free' : 'is-taken' }}">
                                            <i class="slot-chip__dot"></i>Daytime
                                        </span>
                                        <span class="slot-chip slot-chip--nighttime {{ $hasNight ? 'is-free' : 'is-taken' }}">
                                            <i class="slot-chip__dot"></i>Nighttime
                                        </span>
                                    </div>
                                    <div class="occupancy-card__meta">
                                        <span class="occupancy-card__capacity">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                            {{ $amenity->minimum_capacity }}–{{ $amenity->maximum_capacity }} pax
                                        </span>
                                        <span class="occupancy-card__night-rate">Night ₱{{ number_format($amenity->nighttime_price, 2) }}</span>
                                    </div>
                                    <div class="occupancy-card__footer">
                                        <span>View details</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/></svg>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="occupancy-empty">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                                </svg>
                                <p>No amenities found</p>
                            </div>
                        @endforelse
                    </div>
            </main>
        </div>
    </div>

    <!-- Amenity Detail Modal -->
    <div class="modal" id="amenityDetailModal" aria-hidden="true">
        <div class="modal__backdrop" id="closeAmenityDetailModal"></div>
        <div class="modal__panel amenity-detail-panel">
            <div class="modal__header">
                <h3 id="modalAmenityTitle">Amenity Details</h3>
                <button type="button" class="modal__close" id="closeAmenityDetailModalBtn">&times;</button>
            </div>
            <div class="modal__body amenity-detail-body">
                <div class="amenity-detail-img-wrap">
                    <img id="modalAmenityImg" src="" alt="Amenity Image" style="display:none;">
                    <div id="modalAmenityImgPlaceholder" class="amenity-detail-placeholder" style="display:none;">
                        <span>No Image Available</span>
                    </div>
                </div>
                <div class="amenity-detail-info">
                    <p class="amenity-detail-desc" id="modalAmenityDesc"></p>
                    <div class="amenity-detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Daytime Price</span>
                            <span class="detail-val" id="modalDaytimePrice"></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Nighttime Price</span>
                            <span class="detail-val" id="modalNighttimePrice"></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Day Aircon</span>
                            <span class="detail-val" id="modalDayAircon"></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Night Aircon</span>
                            <span class="detail-val" id="modalNightAircon"></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Additional / Head</span>
                            <span class="detail-val" id="modalAddHead"></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Capacity</span>
                            <span class="detail-val" id="modalCapacity"></span>
                        </div>
                    </div>
                    <div class="amenity-detail-status-section">
                        <h4>Current Status & Active Reservations</h4>
                        <div id="modalStatusList" class="status-list"></div>
                    </div>
                </div>
            </div>
            <div class="modal__footer">
                <button type="button" class="btn btn--secondary" id="closeAmenityDetailModalFooter">Close</button>
            </div>
        </div>
    </div>

    <x-staff_chatbot />
</body>
</html>
