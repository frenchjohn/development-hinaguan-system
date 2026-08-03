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
    <style>
        .dash-main::before {
            background-image: url('{{ asset('storage/design_images/background_image1.png') }}');
        }
    </style>
</head>
<body class="antialiased">
    <div class="dash-layout">
        <x-staff_sidemenu active="occupancy-monitor" userName="{{ session('auth_user.name') ?? 'Staff User' }}" userRole="Staff" />

        <div class="dash-main">
            <div class="page-transition-overlay" id="pageTransitionOverlay">
                <div class="page-transition-skeleton">
                    <div class="page-transition-skeleton__header skeleton"></div>
                    <div class="page-transition-skeleton__stats">
                        <div class="page-transition-skeleton__stat skeleton"></div>
                        <div class="page-transition-skeleton__stat skeleton"></div>
                        <div class="page-transition-skeleton__stat skeleton"></div>
                    </div>
                    <div class="page-transition-skeleton__grid">
                        <div class="page-transition-skeleton__panel skeleton"></div>
                        <div class="page-transition-skeleton__panel skeleton"></div>
                    </div>
                </div>
            </div>

            <x-header
                title="Occupancy Monitor"
                subtitle="Real-time view of all amenities and their availability"
            />

            <main class="dash-content">
                <section class="occupancy-monitor">
                    <div class="occupancy-monitor__header">
                        <h3 class="occupancy-monitor__title">All Amenities</h3>
                        <p class="occupancy-monitor__subtitle">Click on an amenity to view details and current occupancy</p>
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
                                    <option value="daynight">DayNight Time</option>
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
                                    if (str_contains($timeSlot, 'daytime')) {
                                        $occupiedSlots[] = 'daytime';
                                    } elseif (str_contains($timeSlot, 'nighttime')) {
                                        $occupiedSlots[] = 'nighttime';
                                    } elseif (str_contains($timeSlot, 'daynight')) {
                                        // DayNight covers both daytime and nighttime
                                        $occupiedSlots[] = 'daytime';
                                        $occupiedSlots[] = 'nighttime';
                                    }
                                }
                                
                                // Determine reserved time slots
                                $reservedSlots = [];
                                foreach ($amenityOccupancy['reserved'] as $reserved) {
                                    $timeSlot = strtolower($reserved['time_slot']);
                                    if (str_contains($timeSlot, 'daytime')) {
                                        $reservedSlots[] = 'daytime';
                                    } elseif (str_contains($timeSlot, 'nighttime')) {
                                        $reservedSlots[] = 'nighttime';
                                    } elseif (str_contains($timeSlot, 'daynight')) {
                                        // DayNight covers both daytime and nighttime
                                        $reservedSlots[] = 'daytime';
                                        $reservedSlots[] = 'nighttime';
                                    }
                                }
                                
                                // Combine occupied and reserved slots
                                $unavailableSlots = array_unique(array_merge($occupiedSlots, $reservedSlots));
                                
                                // Determine available slots (all slots minus unavailable)
                                $allSlots = ['daytime', 'nighttime'];
                                $availableSlots = array_diff($allSlots, $unavailableSlots);
                            @endphp
                            <div class="occupancy-card" 
                                 data-amenity-id="{{ $amenity->id }}" 
                                 data-amenity-name="{{ strtolower($amenity->amenities_name) }}"
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
                                    <h4 class="occupancy-card__name">{{ $amenity->amenities_name }}</h4>
                                    <div class="occupancy-card__capacity">
                                        <span class="occupancy-card__capacity-label">Capacity:</span>
                                        <span class="occupancy-card__capacity-value">{{ $amenity->minimum_capacity }} - {{ $amenity->maximum_capacity }}</span>
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
                </section>
            </main>
        </div>
    </div>

    <x-staff_chatbot />
</body>
</html>
