<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Occupancy Monitor — Hinaguan Nature Park</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/css/homepage.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/sidemenu.css',
        'resources/css/staff_css/staff_occupancy_monitor.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/staff_js/staff_occupancy_monitor.js',
    ])
</head>
<body class="antialiased">
    <div class="dash-layout">
        <x-staff_sidemenu active="occupancy-monitor" />

        <div class="dash-main">
            <x-header
                title="Occupancy Monitor"
                subtitle="Real-time view of all amenities and their availability"
                userName="Staff User"
                userRole="Staff"
                :settingsUrl="route('staff.settings')"
            />

            <main class="dash-content">
                <section class="occupancy-monitor">
                    <div class="occupancy-monitor__header">
                        <h3 class="occupancy-monitor__title">All Amenities</h3>
                        <p class="occupancy-monitor__subtitle">Click on an amenity to view details and current occupancy</p>
                    </div>

                    <div class="occupancy-grid">
                        @forelse ($amenities as $amenity)
                            <div class="occupancy-card" data-amenity-id="{{ $amenity->id }}">
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
                                    @php
                                        $amenityOccupancy = $occupancyData[$amenity->id] ?? ['occupied' => [], 'reserved' => []];
                                    @endphp
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
</body>
</html>
