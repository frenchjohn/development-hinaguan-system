<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Hinaguan Nature Park — A riverside sanctuary in Jasaan, Misamis Oriental. Discover pristine trails, crystal-clear waters, and unforgettable outdoor experiences.">

    <title>Hinaguan Nature Park</title>

    <link rel="icon" type="image/jpeg" href="{{ asset('storage/design_images/main_logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/css/homepage.css', 'resources/css/chatbot.css', 'resources/js/homepage.js', 'resources/js/guest_chatbot.js'])
</head>
<body class="antialiased">

    {{-- Fixed site header (topbar + nav stay together on scroll) --}}
    {{-- Fixed site header (topbar + nav stay together on scroll) --}}
    <div class="hp-site-header" id="hpSiteHeader">
        <div class="hp-topbar {{ ($parkSettings->park_status ?? 'open') === 'closed' ? 'hp-topbar--closed' : '' }}">
            <div class="hp-topbar__inner">
                @if (($parkSettings->park_status ?? 'open') === 'closed')
                    <p class="hp-topbar__text flex items-center justify-center gap-2 flex-wrap">
                        <span class="hp-topbar-badge hp-topbar-badge--closed relative group cursor-help inline-flex items-center gap-1.5 py-0.5 px-2.5 rounded-full bg-red-600 text-white font-bold text-xs shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                            Park Closed
                            <span class="hp-topbar-tooltip pointer-events-none opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200" role="tooltip">
                                <strong>Closure Reason:</strong><br>{{ $parkSettings->close_description ?: 'The park is temporarily closed for maintenance or weather conditions.' }}
                            </span>
                        </span>
                        <span>{{ !empty($parkSettings->close_description) ? $parkSettings->close_description : 'The park is temporarily closed for maintenance.' }}</span>
                        <span class="opacity-80">&nbsp;|&nbsp; Inquiries: {{ $parkSettings->contact_number ?? '0917 861 8383' }}</span>
                    </p>
                @else
                    <p class="hp-topbar__text">
                        <span class="hp-topbar-badge hp-topbar-badge--open relative group cursor-help inline-flex items-center gap-1.5 py-0.5 px-2 rounded-full bg-[#1b5e3a] text-white font-bold text-xs mr-1 shadow-sm">
                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                            Now Open!
                            <span class="hp-topbar-tooltip pointer-events-none opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200" role="tooltip">
                                The park is currently open to guests & visitors.
                            </span>
                        </span>
                        Daytime: Adult &#8369;{{ $parkSettings->daytime_adult_entrance_fee ?? 70 }} &middot; Child &#8369;{{ $parkSettings->daytime_child_entrance_fee ?? 50 }} &nbsp;|&nbsp;
                        Overnight: Adult &#8369;{{ $parkSettings->nighttime_adult_entrance_fee ?? 100 }} &nbsp;|&nbsp;
                        <a href="{{ route('reservation') }}">Reserve Now</a>
                        &nbsp;&middot;&nbsp; Call: {{ $parkSettings->contact_number ?? '0917 861 8383' }}
                    </p>
                @endif
            </div>
        </div>

        <header class="hp-header" id="hpHeader">
        <div class="hp-header__inner">
            <a href="#home" class="hp-logo" data-nav-link>
                <span class="hp-logo__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-1.5 2.5-4 5-4 8a4 4 0 108 0c0-3-2.5-5.5-4-8z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8M10 18h4"/>
                    </svg>
                </span>
                <span class="hp-logo__text">
                    <span class="hp-logo__name">Hinaguan Nature Park</span>
                    <span class="hp-logo__location">Jasaan, Misamis Oriental</span>
                </span>
            </a>

            <nav class="hp-nav">
                <ul class="hp-nav__links">
                    <li><a href="#about" data-nav-link>About</a></li>
                    <li><a href="#amenities" data-nav-link>Amenities</a></li>
                    <li><a href="#activities" data-nav-link>Activities</a></li>
                    <li><a href="#events" data-nav-link>Events</a></li>
                    <li><a href="#rates" data-nav-link>Rates</a></li>
                    <li><a href="#gallery" data-nav-link>Gallery</a></li>
                    <li><a href="#reviews" data-nav-link>Reviews</a></li>
                    <li><a href="#directions" data-nav-link>Directions</a></li>
                </ul>


                <!-- Live Status Dot with Hover Tooltip -->
                <div class="hp-nav-status relative group shrink-0">
                    @if (($parkSettings->park_status ?? 'open') === 'closed')
                        <button type="button" class="hp-status-pill hp-status-pill--closed cursor-pointer" id="hpStatusClosedBtn" tabindex="0" aria-label="Park is Closed Today — Hover or click for details" title="Park is Closed Today">
                            <span class="hp-status-pill__radar"></span>
                            <span class="hp-status-pill__dot"></span>
                        </button>
                        <div class="hp-status-tooltip pointer-events-none opacity-0 invisible group-hover:opacity-100 group-hover:visible group-focus-within:opacity-100 group-focus-within:visible transition-all duration-200" role="tooltip">
                            <p class="font-bold text-red-400 text-xs mb-1 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                Notice: Park is Closed Today
                            </p>
                            <p class="text-xs text-white/90 leading-relaxed mb-1.5">{{ $parkSettings->close_description ?: 'The park is temporarily closed for maintenance or weather conditions.' }}</p>
                            <span class="inline-block text-[10px] text-red-300/90 font-medium underline">Click dot to view notice</span>
                        </div>
                    @else
                        <div class="hp-status-pill hp-status-pill--open cursor-help" tabindex="0" aria-label="Park is Open — Hover for details" title="Park is Open">
                            <span class="hp-status-pill__radar"></span>
                            <span class="hp-status-pill__dot"></span>
                        </div>
                        <div class="hp-status-tooltip pointer-events-none opacity-0 invisible group-hover:opacity-100 group-hover:visible group-focus-within:opacity-100 group-focus-within:visible transition-all duration-200" role="tooltip">
                            <p class="font-bold text-emerald-400 text-xs mb-1 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                Park Is Open
                            </p>
                            <p class="text-xs text-white/90 leading-relaxed">Open daily for day tour and overnight stays.</p>
                        </div>
                    @endif
                </div>

                <a href="{{ route('reservation') }}" class="hp-btn hp-btn--book">Book Now</a>
            </nav>

            <button class="hp-menu-toggle" aria-label="Open menu" aria-expanded="false">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>
        </div>
        </header>
    </div>

    {{-- Mobile nav --}}
    <nav class="hp-mobile-nav" id="hpMobileNav" aria-hidden="true">
        <button type="button" class="hp-mobile-nav__close" id="hpMobileNavClose" aria-label="Close navigation menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <div class="hp-mobile-nav__status-wrap">
            @if (($parkSettings->park_status ?? 'open') === 'closed')
                <div class="hp-mobile-nav__status hp-mobile-nav__status--closed">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500 shrink-0"></span>
                    <span>Park Closed: {{ $parkSettings->close_description ?: 'Temporarily closed' }}</span>
                </div>
            @else
                <div class="hp-mobile-nav__status hp-mobile-nav__status--open">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
                    <span>Park Open Today</span>
                </div>
            @endif
        </div>

        <div class="hp-mobile-nav__links">
            <a href="#about" data-nav-link>About</a>
            <a href="#amenities" data-nav-link>Amenities</a>
            <a href="#activities" data-nav-link>Activities</a>
            <a href="#events" data-nav-link>Events</a>
            <a href="#rates" data-nav-link>Rates</a>
            <a href="#gallery" data-nav-link>Gallery</a>
            <a href="#reviews" data-nav-link>Reviews</a>
            <a href="#directions" data-nav-link>Directions</a>
        </div>

        <div class="hp-mobile-nav__action">
            <a href="{{ route('reservation') }}" class="hp-btn hp-btn--book">Book Now</a>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="hp-hero" id="home" data-section>
        <div class="hp-hero__bg" style="background-image: url('{{ asset('images/background.jpeg') }}')" aria-hidden="true"></div>
        <div class="hp-hero__overlay" aria-hidden="true"></div>

        @if ($weather || !empty($parkSettings->brenda_available))
            <div class="hp-hero__side-widgets" id="hpHeroSideWidgets" data-animate="fade-up">
                {{-- Mobile Widgets Toggle Bar (Visible only on mobile <= 768px) --}}
                <div class="hp-mobile-widgets-bar">
                    <button type="button" class="hp-mobile-widgets-toggle" id="hpMobileWidgetsToggle" aria-expanded="false" aria-controls="hpMobileWidgetsCollapse">
                        <span class="hp-mobile-widgets-toggle__left">
                            <span class="hp-mobile-widgets-toggle__beacon">
                                <span class="hp-mobile-widgets-toggle__ping"></span>
                            </span>
                            <span class="hp-mobile-widgets-toggle__title">
                                @if ($weather && !empty($parkSettings->brenda_available))
                                    Weather &amp; Brenda In Park
                                @elseif ($weather)
                                    Live Weather Updates
                                @else
                                    Celebrity Host Notice
                                @endif
                            </span>
                        </span>
                        <span class="hp-mobile-widgets-toggle__right">
                            @if ($weather)
                                <span class="hp-mobile-widgets-toggle__temp">{{ round($weather['temp_c']) }}°C</span>
                            @endif
                            <span class="hp-mobile-widgets-toggle__chevron" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </span>
                        </span>
                    </button>
                </div>

                {{-- Side Widgets Body: Collapsible on Mobile, Standard Flex on Desktop --}}
                <div class="hp-hero__side-widgets-body" id="hpMobileWidgetsCollapse">
                    {{-- Mobile-only Close Header inside the expanded card --}}
                    <div class="hp-mobile-widgets-close-bar">
                        <span class="hp-mobile-widgets-close-title">
                            <i class="bi bi-broadcast"></i> Live Updates
                        </span>
                        <button type="button" class="hp-mobile-widgets-close-btn" id="hpMobileWidgetsClose" aria-label="Close weather and notifications">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span>Close</span>
                        </button>
                    </div>

                    @if ($weather)
                        <aside class="hp-weather" aria-label="Today's weather">
                            <div class="hp-weather__shimmer" aria-hidden="true"></div>

                            {{-- Header / Status Bar --}}
                            <div class="hp-weather__header">
                                <div class="hp-weather__status-pill">
                                    <span class="hp-weather__beacon">
                                        <span class="hp-weather__ping"></span>
                                    </span>
                                    <span>Live Weather</span>
                                </div>
                                <div class="hp-weather__location-tag">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span>{{ $weather['location'] }}</span>
                                </div>
                            </div>

                            {{-- Hero Section: Temp & Condition + Weather Visual Orb --}}
                            <div class="hp-weather__hero">
                                <div class="hp-weather__hero-main">
                                    <div class="hp-weather__temp-wrap">
                                        <span class="hp-weather__temp-num">{{ round($weather['temp_c']) }}</span>
                                        <span class="hp-weather__temp-unit">°C</span>
                                    </div>
                                    <div class="hp-weather__condition-badge">
                                        {{ $weather['condition'] }}
                                    </div>
                                </div>
                                <div class="hp-weather__hero-orb">
                                    @if ($weather['icon'])
                                        <img src="{{ $weather['icon'] }}" alt="{{ $weather['condition'] }}" class="hp-weather__icon" width="48" height="48">
                                    @endif
                                </div>
                            </div>

                            {{-- Metrics Strip: 3 Micro Stat Badges --}}
                            <div class="hp-weather__metrics-strip">
                                <div class="hp-weather__metric-chip" title="Feels like">
                                    <i class="bi bi-thermometer-half"></i>
                                    <span>Feels <strong>{{ round($weather['feelslike_c']) }}°</strong></span>
                                </div>
                                <div class="hp-weather__metric-chip" title="Humidity">
                                    <i class="bi bi-droplet-half"></i>
                                    <span><strong>{{ $weather['humidity'] }}%</strong></span>
                                </div>
                                @if (!empty($weather['wind_kph']))
                                    <div class="hp-weather__metric-chip" title="Wind speed">
                                        <i class="bi bi-wind"></i>
                                        <span><strong>{{ round($weather['wind_kph']) }}</strong> km/h</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Hourly Timeline Outlook --}}
                            @if (!empty($weather['next_3_hours']))
                                <div class="hp-weather__timeline">
                                    <div class="hp-weather__timeline-header">
                                        <span class="hp-weather__timeline-title">
                                            <i class="bi bi-clock-history"></i> Next Hours
                                        </span>
                                        <span class="hp-weather__timeline-hint">Forecast</span>
                                    </div>
                                    <div class="hp-weather__timeline-grid">
                                        @foreach ($weather['next_3_hours'] as $hour)
                                            <div class="hp-weather__timeline-item">
                                                <span class="hp-weather__timeline-time">{{ $hour['time_label'] }}</span>
                                                <div class="hp-weather__timeline-icon-box">
                                                    @if (!empty($hour['icon']))
                                                        <img src="{{ $hour['icon'] }}" alt="{{ $hour['condition'] }}" class="hp-weather__timeline-icon" width="26" height="26">
                                                    @endif
                                                </div>
                                                <span class="hp-weather__timeline-temp">{{ round($hour['temp_c']) }}°</span>
                                                <span class="hp-weather__timeline-rain" title="Rain probability">
                                                    <i class="bi bi-cloud-rain-fill"></i> {{ $hour['chance_of_rain'] ?? 0 }}%
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </aside>
                    @endif

                    @if (!empty($parkSettings->brenda_available))
                        {{-- Celebrity Host Spotlight Widget (Directly Under Weather) --}}
                        <div class="hp-brenda-widget group" tabindex="0" aria-label="Celebrity host Brenda Mage is in the park today">
                            <div class="hp-brenda-widget__shimmer" aria-hidden="true"></div>
                            <div class="hp-brenda-widget__header">
                                <span class="hp-brenda-widget__badge">
                                    <span class="hp-brenda-widget__dot">
                                        <span class="hp-brenda-widget__ping"></span>
                                    </span>
                                    <span>Celebrity In Park</span>
                                </span>
                                <span class="hp-brenda-widget__sparkle" title="Celebrity presence">
                                    <i class="bi bi-stars"></i>
                                </span>
                            </div>

                            <div class="hp-brenda-widget__body">
                                <div class="hp-brenda-widget__avatar" aria-hidden="true">
                                    <i class="bi bi-patch-check-fill"></i>
                                </div>
                                <div class="hp-brenda-widget__info">
                                    <h3 class="hp-brenda-widget__name">Brenda is in the park!</h3>
                                    <p class="hp-brenda-widget__subtitle">Celebrity owner Brenda Mage is on-site today</p>
                                </div>
                            </div>

                            <div class="hp-brenda-widget__footer">
                                <div class="hp-brenda-widget__loc">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span>Hinaguan Nature Park</span>
                                </div>
                                <span class="hp-brenda-widget__pill">
                                    <i class="bi bi-camera-fill"></i>
                                    <span>Meet &amp; Greet</span>
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="hp-hero__content">
            <div class="hp-hero__text" data-animate="fade-up">
                <span class="hp-hero__eyebrow">Riverside Sanctuary &middot; Jasaan, Misamis Oriental</span>
                <h1 class="hp-hero__title">Hinaguan Nature Park</h1>
                <p class="hp-hero__subtitle">
                    Where the river sings and the forest breathes — an enchanting riverside escape
                    owned by celebrity Brenda Mage.
                </p>

                <div class="hp-hero__status-row flex flex-wrap items-center gap-3 mb-6">
                    <a href="{{ route('amenities') }}" class="hp-live-status group cursor-pointer transition-all duration-300 hover:scale-105 hover:shadow-lg no-underline" aria-label="View amenities and real-time park occupancy">
                        <span class="hp-live-status__dot"></span>
                        <div class="hp-live-status__content">
                            <p class="hp-live-status__label flex items-center gap-1 group-hover:text-hp-gold">
                                Currently in the park
                                <svg class="h-3.5 w-3.5 opacity-70 group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/></svg>
                            </p>
                            <p class="hp-live-status__count">
                                <span id="activeGuestCount" data-count="{{ $activeGuestCount ?? 0 }}">{{ $activeGuestCount ?? 0 }}</span>
                                <span class="hp-live-status__suffix">guests</span>
                            </p>
                        </div>
                    </a>

                    @if ($nearEvent)
                        <a href="#events" class="hp-hero-event-badge group cursor-pointer transition-all duration-300 hover:scale-105 hover:shadow-lg no-underline" data-nav-link aria-label="Near Event: {{ $nearEvent->title }}">
                            <span class="hp-hero-event-badge__dot"></span>
                            <div class="hp-hero-event-badge__content">
                                <p class="hp-hero-event-badge__label flex items-center gap-1 group-hover:text-hp-gold">
                                    <i class="bi bi-calendar-event"></i>
                                    <span>Near Event &middot; {{ $nearEvent->day }} &middot; {{ \Carbon\Carbon::parse($nearEvent->date)->format('M d') }}@if ($nearEvent->time) &middot; {{ $nearEvent->time }}@endif</span>
                                    <svg class="h-3.5 w-3.5 opacity-70 group-hover:translate-x-0.5 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/></svg>
                                </p>
                                <p class="hp-hero-event-badge__title">
                                    {{ $nearEvent->title }}
                                </p>
                            </div>
                        </a>
                    @endif
                </div>


                <div class="hp-hero__actions">
                    <a href="{{ route('reservation') }}" class="hp-btn hp-btn--hero">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Reserve Now
                    </a>
                    <a href="#about" class="hp-btn hp-btn--outline" data-nav-link>Explore the Park</a>
                </div>
            </div>
        </div>

        <div class="hp-hero__scroll" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </section>

    {{-- About --}}
    <section class="hp-section hp-section--cream" id="about" data-section>
        <div class="hp-container">
            <div class="hp-about-grid">
                <div class="hp-about__visual" data-animate="fade-right">
                    <div class="hp-about__image-main">
                        <img src="{{ asset('images/picnic_and_bonding.jpg') }}" alt="Guests enjoying Hinaguan Nature Park" loading="lazy">
                    </div>
                    <div class="hp-about__image-secondary">
                        <img src="{{ asset('images/River_Trecking.jpg') }}" alt="River trekking at Hinaguan Nature Park" loading="lazy">
                    </div>
                    <div class="hp-about__badge">&#8369;{{ $parkSettings->daytime_adult_entrance_fee ?? 20 }} Entrance Fee</div>
                </div>

                <div class="hp-about__text" data-animate="fade-up" data-delay="150">
                    <span class="hp-section__label">About the Park</span>
                    <h2 class="hp-section__title">A True Escape Into Nature's Embrace</h2>
                    <p>
                        Nestled along the banks of a pristine river in Jasaan, Misamis Oriental, Hinaguan Nature Park
                        offers a serene retreat where lush greenery, crystal-clear waters, and the gentle sounds of
                        nature create the perfect backdrop for relaxation and adventure.
                    </p>
                    <p>
                        Owned and hosted by beloved celebrity Brenda Mage, this riverside sanctuary welcomes families,
                        friends, and nature lovers to unwind, explore, and create lasting memories in one of Mindanao's
                        most enchanting destinations.
                    </p>
                    <p>
                        From natural river streams and fresh swimming spots to cozy cottages and open-air dining, every corner
                        of Hinaguan is designed to bring you closer to the beauty of the outdoors.
                    </p>

                    <div class="hp-about__host">
                        <div class="hp-about__host-avatar">
                            <img src="{{ asset('images/photography.jpg') }}" alt="Brenda Mage at Hinaguan Nature Park" loading="lazy">
                        </div>
                        <div>
                            <p class="hp-about__host-name">Brenda Mage</p>
                            <p class="hp-about__host-role">Park Owner &amp; Host</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Amenities --}}
    <section class="hp-section hp-section--dark" id="amenities" data-section>
        <div class="hp-container">
            <div class="hp-section__header" data-animate="fade-up">
                <span class="hp-section__label">What We Offer</span>
                <h2 class="hp-section__title">Park Amenities &amp; Highlights</h2>
                <p class="hp-section__desc">
                    Everything you need for a comfortable and memorable visit, surrounded by nature's finest offerings.
                </p>
            </div>

            <div class="hp-amenities-grid">
                <div class="hp-amenity" data-animate="fade-up" data-delay="0">
                    <div class="hp-amenity__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                    </div>
                    <h3 class="hp-amenity__title">Cottages &amp; Huts</h3>
                    <p class="hp-amenity__desc">Rustic cottages and open huts perfect for day visits or overnight stays with family and friends.</p>
                </div>
                <div class="hp-amenity" data-animate="fade-up" data-delay="80">
                    <div class="hp-amenity__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <h3 class="hp-amenity__title">Natural Pool</h3>
                    <p class="hp-amenity__desc">Refresh in our clean, spring-fed swimming pool surrounded by towering trees and tropical foliage.</p>
                </div>
                <div class="hp-amenity" data-animate="fade-up" data-delay="160">
                    <div class="hp-amenity__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="hp-amenity__title">Scenic Views</h3>
                    <p class="hp-amenity__desc">Panoramic river views, lush natural landscapes, and photo-worthy spots at every turn of the park.</p>
                </div>
                <div class="hp-amenity" data-animate="fade-up" data-delay="240">
                    <div class="hp-amenity__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9c1.5 0 2.5-.75 4.25-.75s2.75.75 4.25.75 2.75-.75 4.25-.75 2.75.75 4.25.75M3.75 14c1.5 0 2.5-.75 4.25-.75s2.75.75 4.25.75 2.75-.75 4.25-.75 2.75.75 4.25.75M3.75 19c1.5 0 2.5-.75 4.25-.75s2.75.75 4.25.75 2.75-.75 4.25-.75 2.75.75 4.25.75"/></svg>
                    </div>
                    <h3 class="hp-amenity__title">Natural River</h3>
                    <p class="hp-amenity__desc">Relax by the crystal-clear river waters — enjoy gentle currents, shallow wading spots, and a peaceful riverside breeze.</p>
                </div>
                <div class="hp-amenity" data-animate="fade-up" data-delay="320">
                    <div class="hp-amenity__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="hp-amenity__title">Affordable Rates</h3>
                    <p class="hp-amenity__desc">Enjoy a full day of nature, fun, and relaxation without breaking the bank — great value for everyone.</p>
                </div>
                <div class="hp-amenity" data-animate="fade-up" data-delay="400">
                    <div class="hp-amenity__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <h3 class="hp-amenity__title">Food &amp; Refreshments</h3>
                    <p class="hp-amenity__desc">On-site food stalls and refreshment areas so you can stay energized throughout your visit.</p>
                </div>
            </div>

            <div class="hp-section__cta" data-animate="fade-up">
                <a href="{{ route('amenities') }}" class="hp-btn hp-btn--outline-dark">View All Amenities</a>
            </div>
        </div>
    </section>

    {{-- Activities --}}
    <section class="hp-section hp-section--cream" id="activities" data-section>
        <div class="hp-container">
            <div class="hp-section__header" data-animate="fade-up">
                <span class="hp-section__label">Things to Do</span>
                <h2 class="hp-section__title">Activities &amp; Experiences</h2>
                <p class="hp-section__desc">
                    From peaceful riverside walks to fun-filled group activities, there's something for every visitor.
                </p>
            </div>

            <div class="hp-activities-grid">
                <article class="hp-activity-card" data-animate="fade-up" data-delay="0">
                    <div class="hp-activity-card__image">
                        <img src="{{ asset('images/River_Trecking.jpg') }}" alt="River trekking at Hinaguan Nature Park" loading="lazy">
                    </div>
                    <div class="hp-activity-card__body">
                        <h3>River Trekking</h3>
                        <p>Follow scenic trails along the riverbank and discover hidden spots, rock formations, and lush vegetation.</p>
                    </div>
                </article>
                <article class="hp-activity-card" data-animate="fade-up" data-delay="100">
                    <div class="hp-activity-card__image">
                        <img src="{{ asset('images/swimming_and_wading.jpg') }}" alt="Swimming and wading at Hinaguan Nature Park" loading="lazy">
                    </div>
                    <div class="hp-activity-card__body">
                        <h3>Swimming &amp; Wading</h3>
                        <p>Cool off in the natural pool or wade in the shallow river areas — perfect for kids and adults alike.</p>
                    </div>
                </article>
                <article class="hp-activity-card" data-animate="fade-up" data-delay="200">
                    <div class="hp-activity-card__image">
                        <img src="{{ asset('images/picnic_and_bonding.jpg') }}" alt="Picnic and bonding at Hinaguan Nature Park" loading="lazy">
                    </div>
                    <div class="hp-activity-card__body">
                        <h3>Picnic &amp; Bonding</h3>
                        <p>Spread out at open picnic areas, enjoy meals with loved ones, and soak in the peaceful riverside atmosphere.</p>
                    </div>
                </article>
                <article class="hp-activity-card" data-animate="fade-up" data-delay="300">
                    <div class="hp-activity-card__image">
                        <img src="{{ asset('images/photography.jpg') }}" alt="Photography at Hinaguan Nature Park" loading="lazy">
                    </div>
                    <div class="hp-activity-card__body">
                        <h3>Photography</h3>
                        <p>Capture stunning shots along the riverside, scenic landscapes, and rustic cottages — a content creator's paradise.</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- Park Events (All Events) --}}
    <section class="hp-section hp-section--dark" id="events" data-section>
        <div class="hp-container">
            <div class="hp-section__header" data-animate="fade-up">
                <span class="hp-section__label">What's Happening</span>
                <h2 class="hp-section__title">Park Events &amp; Experiences</h2>
                <p class="hp-section__desc">
                    Discover exciting gatherings, seasonal celebrations, and outdoor activities scheduled at Hinaguan Nature Park.
                </p>
            </div>

            @if ($nearEvent)
                {{-- Near Event Spotlight Card --}}
                <div class="hp-near-event-spotlight" data-animate="fade-up" data-delay="50">
                    <div class="hp-near-event-spotlight__card">
                        <div class="hp-near-event-spotlight__badge">
                            <span class="hp-pulse-dot"></span>
                            <span>Featured Near Event &middot; Happening Soon</span>
                        </div>
                        <div class="hp-near-event-spotlight__grid">
                            <div class="hp-near-event-spotlight__date-box">
                                <span class="hp-near-event-spotlight__month">{{ \Carbon\Carbon::parse($nearEvent->date)->format('M') }}</span>
                                <span class="hp-near-event-spotlight__day">{{ \Carbon\Carbon::parse($nearEvent->date)->format('d') }}</span>
                                <span class="hp-near-event-spotlight__weekday">{{ $nearEvent->day ?: \Carbon\Carbon::parse($nearEvent->date)->format('l') }}</span>
                            </div>
                            <div class="hp-near-event-spotlight__content">
                                <div class="hp-near-event-spotlight__meta">
                                    <i class="bi bi-clock"></i>
                                    <span>{{ $nearEvent->time ?: 'All Day Event' }}</span>
                                    <span class="opacity-40">&middot;</span>
                                    <i class="bi bi-geo-alt"></i>
                                    <span>Hinaguan Nature Park</span>
                                </div>
                                <h3 class="hp-near-event-spotlight__title">{{ $nearEvent->title }}</h3>
                                <p class="hp-near-event-spotlight__desc">{{ $nearEvent->event }}</p>
                            </div>
                            <div class="hp-near-event-spotlight__action">
                                <a href="{{ route('reservation') }}" class="hp-btn hp-btn--book">
                                    Book Reservation
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Events Schedule Banner --}}
            <div class="hp-events-week-banner" data-animate="fade-up" data-delay="80">
                <div class="hp-events-week-banner__info">
                    <div class="hp-events-week-banner__tag">
                        <span class="hp-pulse-dot"></span>
                        <span>Park Schedule</span>
                    </div>
                    <p class="hp-events-week-banner__dates">
                        Hinaguan Nature Park &middot; Jasaan, Misamis Oriental
                    </p>
                </div>
                <div class="hp-events-week-banner__summary">
                    @if ($allEvents->isNotEmpty())
                        <span class="text-sm font-semibold text-[#1b5e3a]">
                            🎉 <strong>{{ $allEvents->count() }}</strong> event{{ $allEvents->count() > 1 ? 's' : '' }} scheduled
                        </span>
                    @else
                        <span class="text-sm text-gray-400">Open daily for scenic walks, picnics, and overnight stays.</span>
                    @endif
                </div>
            </div>

            {{-- Events Carousel (2 Items Visible, Horizontal Scroll) --}}
            <div class="hp-events-panel is-active" id="panelAllEvents">
                @if ($allEvents->isNotEmpty())
                    <div class="hp-events-carousel-wrapper">
                        <div class="hp-events-carousel-track" id="hpEventsTrack">
                            @foreach ($allEvents as $index => $event)
                                @php
                                    $eventCarbon = \Carbon\Carbon::parse($event->date);
                                    $isToday = $eventCarbon->isToday();
                                @endphp
                                <article class="hp-event-card {{ $isToday ? 'hp-event-card--today' : '' }}" data-animate="fade-up" data-delay="{{ min(($index + 1) * 80, 400) }}">
                                    <div class="hp-event-card__date-box">
                                        <span class="hp-event-card__weekday">{{ $event->day ?: $eventCarbon->format('l') }}</span>
                                        <span class="hp-event-card__day">{{ $eventCarbon->format('d') }}</span>
                                        <span class="hp-event-card__month">{{ $eventCarbon->format('M') }}</span>
                                    </div>

                                    <div class="hp-event-card__content">
                                        <div class="hp-event-card__meta-top">
                                            @if ($isToday)
                                                <span class="hp-event-badge hp-event-badge--today">
                                                    <span class="hp-pulse-dot"></span> Happening Today
                                                </span>
                                            @else
                                                <span class="hp-event-badge hp-event-badge--week">Scheduled Event</span>
                                            @endif

                                            @if ($event->time)
                                                <span class="hp-event-time">
                                                    <svg class="hp-event-time__icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="14" height="14">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <span>{{ $event->time }}</span>
                                                </span>
                                            @endif
                                        </div>

                                        <h3 class="hp-event-card__title">{{ $event->title }}</h3>

                                        <p class="hp-event-card__desc">
                                            {{ $event->event }}
                                        </p>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        {{-- Carousel Controls (Arrows + Dots) --}}
                        @if ($allEvents->count() > 2)
                            <div class="hp-events-carousel-nav" data-animate="fade-up">
                                <button type="button" class="hp-events-nav-btn hp-events-nav-btn--prev" id="hpEventsPrev" aria-label="Previous events">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </button>

                                <div class="hp-events-carousel-dots" id="hpEventsDots">
                                    @foreach ($allEvents as $idx => $ev)
                                        <button type="button" class="hp-events-dot {{ $idx === 0 ? 'is-active' : '' }}" data-index="{{ $idx }}" aria-label="Go to event {{ $idx + 1 }}"></button>
                                    @endforeach
                                </div>

                                <button type="button" class="hp-events-nav-btn hp-events-nav-btn--next" id="hpEventsNext" aria-label="Next events">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="hp-events-empty" data-animate="fade-up">
                        <div class="hp-events-empty__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                            </svg>
                        </div>
                        <h3>No Special Events Scheduled</h3>
                        <p>Hinaguan Nature Park is open daily for regular entrance, swimming, picnics, and overnight stays.</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Rates --}}
    <section class="hp-section hp-section--cream" id="rates" data-section>
        <div class="hp-container">
            <div class="hp-section__header" data-animate="fade-up">
                <span class="hp-section__label">Pricing</span>
                <h2 class="hp-section__title">Affordable Rates for Everyone</h2>
                <p class="hp-section__desc">
                    Transparent pricing with no hidden fees. Choose the visit type that suits your adventure.
                </p>
            </div>

            <div class="hp-rates-grid">
                <div class="hp-rate-card" data-animate="fade-up" data-delay="0">
                    <div class="hp-rate-card__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <h3 class="hp-rate-card__title">Daytime Visit</h3>
                    <p class="hp-rate-card__meta">Entrance Fee &middot; Full park access during the day</p>
                    <div class="hp-rate-card__price-box">
                        <span class="hp-rate-card__badge">Adult</span>
                        <p class="hp-rate-card__price">&#8369;{{ $parkSettings->daytime_adult_entrance_fee ?? 70 }} <span>per person</span></p>
                    </div>
                    <div class="hp-rate-card__price-box hp-rate-card__price-box--child">
                        <span class="hp-rate-card__badge">Child</span>
                        <p class="hp-rate-card__price">&#8369;{{ $parkSettings->daytime_child_entrance_fee ?? 50 }} <span>per person</span></p>
                    </div>
                </div>

                <div class="hp-rate-card hp-rate-card--featured" data-animate="fade-up" data-delay="150">
                    <span class="hp-rate-card__tag">Most Popular</span>
                    <div class="hp-rate-card__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </div>
                    <h3 class="hp-rate-card__title">Overnight Stay</h3>
                    <p class="hp-rate-card__meta">Entrance Fee &middot; Check-in 6:00 PM &middot; Check-out 8:00 AM</p>
                    <div class="hp-rate-card__price-box">
                        <span class="hp-rate-card__badge">Adult</span>
                        <p class="hp-rate-card__price">&#8369;{{ $parkSettings->nighttime_adult_entrance_fee ?? 100 }} <span>per person</span></p>
                    </div>
                    <div class="hp-rate-card__price-box hp-rate-card__price-box--child">
                        <span class="hp-rate-card__badge">Child</span>
                        <p class="hp-rate-card__price">&#8369;{{ $parkSettings->nighttime_child_entrance_fee ?? 70 }} <span>per person</span></p>
                    </div>
                </div>
            </div>

            <div class="hp-rates-note" data-animate="fade-up">
                <p>Entrance fee of &#8369;{{ $parkSettings->daytime_adult_entrance_fee ?? 20 }} applies to all visitors. Cottage and amenity rentals are priced separately.</p>
                <a href="{{ route('reservation') }}" class="hp-btn hp-btn--hero">Book Your Visit</a>
            </div>
        </div>
    </section>

    {{-- Gallery --}}
    <section class="hp-section hp-section--dark" id="gallery" data-section>
        <div class="hp-container">
            <div class="hp-section__header" data-animate="fade-up">
                <span class="hp-section__label">Gallery</span>
                <h2 class="hp-section__title">Moments at Hinaguan</h2>
                <p class="hp-section__desc">A glimpse of the beauty, fun, and serenity waiting for you at the park.</p>
            </div>

            <div class="hp-gallery-grid">
                @foreach (range(1, 8) as $index)
                    <div class="hp-gallery-item{{ $index === 1 || $index === 6 ? ' hp-gallery-item--wide' : '' }}" data-animate="zoom-in" data-delay="{{ ($index - 1) * 80 }}">
                        <img src="{{ asset('images/image_' . $index . '.jpg') }}" alt="Hinaguan Nature Park photo {{ $index }}" loading="lazy">
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Guest Reviews --}}
    <section class="hp-section hp-section--cream" id="reviews" data-section>
        <div class="hp-container">
            <div class="hp-section__header" data-animate="fade-up">
                <span class="hp-section__label">Guest Reviews</span>
                <h2 class="hp-section__title">What Visitors Say</h2>
                <p class="hp-section__desc">Real experiences from guests who explored Hinaguan Nature Park.</p>
            </div>

            @if ($featuredFeedbacks->isNotEmpty())
                @php
                    $rawCount = $featuredFeedbacks->count();
                    $repeatMultiplier = $rawCount >= 8 ? 1 : (int) ceil(8 / max(1, $rawCount));
                @endphp
                <div class="hp-reviews-running" data-animate="fade-up" data-delay="100">
                    <div class="hp-reviews-running__track">
                        @foreach (range(1, 2) as $trackGroup)
                            <div class="hp-reviews-running__group" {{ $loop->last ? 'aria-hidden=true' : '' }}>
                                @for ($rep = 0; $rep < $repeatMultiplier; $rep++)
                                    @foreach ($featuredFeedbacks as $feedback)
                                        <article class="hp-review-card">
                                            <div class="hp-review-card__header">
                                                <span class="hp-review-card__avatar" aria-hidden="true">{{ $feedback->initials }}</span>
                                                <div class="hp-review-card__user">
                                                    <h3 class="hp-review-card__name">{{ $feedback->full_name }}</h3>
                                                    <time class="hp-review-card__date" datetime="{{ $feedback->created_at->toDateString() }}">{{ $feedback->created_at->format('M j, Y') }}</time>
                                                </div>
                                            </div>
                                            <div class="hp-review-card__stars" aria-label="{{ $feedback->stars }} out of 5 stars">
                                                @for ($s = 1; $s <= 5; $s++)
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="{{ $s <= $feedback->stars ? 'is-filled' : '' }}"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                                @endfor
                                            </div>
                                            <p class="hp-review-card__text">&ldquo;{{ $feedback->description }}&rdquo;</p>
                                        </article>
                                    @endforeach
                                @endfor
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="hp-reviews-running__actions" data-animate="fade-up" data-delay="150">
                    <a href="{{ route('feedback') }}" class="hp-btn--reviews-outline">See Other Reviews</a>
                </div>
            @else
                <div class="hp-reviews-empty" data-animate="fade-up">
                    <p>No guest reviews yet. Be the first to share your Hinaguan experience.</p>
                    <a href="{{ route('feedback') }}" class="hp-btn hp-btn--hero">Write a Review</a>
                </div>
            @endif
        </div>
    </section>

    {{-- Directions --}}
    <section class="hp-section hp-section--dark" id="directions" data-section>
        <div class="hp-container">
            <div class="hp-section__header" data-animate="fade-up">
                <span class="hp-section__label">Find Us</span>
                <h2 class="hp-section__title">Directions &amp; Contact</h2>
                <p class="hp-section__desc">Plan your trip to Hinaguan Nature Park in Jasaan, Misamis Oriental.</p>
            </div>

            <div class="hp-directions-grid">
                <div class="hp-directions__info" data-animate="fade-right">
                    <div class="hp-contact-item">
                        <div class="hp-contact-item__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <p class="hp-contact-item__label">Location</p>
                            <p class="hp-contact-item__value">Hinaguan, Jasaan<br>Misamis Oriental, Philippines</p>
                        </div>
                    </div>
                    <div class="hp-contact-item">
                        <div class="hp-contact-item__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </div>
                        <div>
                            <p class="hp-contact-item__label">Phone</p>
                            <p class="hp-contact-item__value"><a href="tel:+63{{ preg_replace('/[^0-9]/', '', $parkSettings->contact_number ?? '0917 861 8383') }}">{{ $parkSettings->contact_number ?? '0917 861 8383' }}</a></p>
                        </div>
                    </div>
                    <div class="hp-contact-item">
                        <div class="hp-contact-item__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <p class="hp-contact-item__label">Email</p>
                            <p class="hp-contact-item__value"><a href="mailto:{{ $parkSettings->email ?? 'info@hinaguannaturepark.com' }}">{{ $parkSettings->email ?? 'info@hinaguannaturepark.com' }}</a></p>
                        </div>
                    </div>
                    <div class="hp-contact-item">
                        <div class="hp-contact-item__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="hp-contact-item__label">Park Hours</p>
                            <p class="hp-contact-item__value">Daily &middot; {{ $parkSettings?->opening_time ? date('g:i A', strtotime($parkSettings->opening_time)) : '6:00 AM' }} – {{ $parkSettings?->closing_time ? date('g:i A', strtotime($parkSettings->closing_time)) : '6:00 PM' }}<br>Overnight check-in from {{ $parkSettings?->nighttime_start ? date('g:i A', strtotime($parkSettings->nighttime_start)) : '6:00 PM' }}</p>
                        </div>
                    </div>

                    <div class="hp-directions__steps">
                        <h3>How to Get Here</h3>
                        <ol>
                            <li>From Cagayan de Oro City, take the bus or van bound for Jasaan.</li>
                            <li>Ask the driver to drop you off at Hinaguan, Jasaan.</li>
                            <li>Follow local signage to Hinaguan Nature Park — approximately 5 minutes from the highway.</li>
                        </ol>
                    </div>
                </div>

                <div class="hp-directions__map" data-animate="fade-left">
                    <iframe
                        title="Hinaguan Nature Park location"
                        src="https://maps.google.com/maps?q=Jasaan%20Misamis%20Oriental%20Philippines&output=embed"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen
                    ></iframe>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="hp-footer">
        <div class="hp-container hp-footer__inner">
            <div class="hp-footer__brand">
                <span class="hp-logo__name">Hinaguan Nature Park</span>
                <p>Jasaan, Misamis Oriental</p>
            </div>
            <p class="hp-footer__copy">&copy; {{ date('Y') }} Hinaguan Nature Park. All rights reserved.</p>
        </div>
    </footer>

    {{-- Floating action buttons --}}
    <div class="hp-fab-group hp-fab-group--left">
        <a href="https://m.me/hinaguannaturepark" class="hp-fab hp-fab--messenger" target="_blank" rel="noopener noreferrer" aria-label="Message us on Messenger">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.145 2 11.243c0 2.906 1.446 5.502 3.709 7.17V22l3.405-1.871c.907.252 1.871.389 2.886.389 5.523 0 10-4.145 10-9.243S17.523 2 12 2zm1.017 12.443-2.558-2.726-5.002 2.726 5.511-5.847 2.624 2.726 4.933-2.726-5.508 5.847z"/></svg>
        </a>
        <a href="tel:+639178618383" class="hp-fab hp-fab--phone" aria-label="Call us">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
        </a>
    </div>

    @if (($parkSettings->park_status ?? 'open') === 'closed')
        {{-- Bootstrap Modal: Notice Park is Closed Today --}}
        <div class="modal fade hp-park-closed-modal" id="parkClosedModal" tabindex="-1" aria-labelledby="parkClosedModalLabel" aria-hidden="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content hp-simple-modal">
                    {{-- Header with Badge & Close button --}}
                    <div class="hp-simple-modal__header">
                        <span class="hp-simple-modal__badge">
                            <span class="hp-simple-modal__dot"></span>
                            Notice
                        </span>
                        <button type="button" class="hp-simple-modal__close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    {{-- Simple Icon --}}
                    <div class="hp-simple-modal__icon">
                        <i class="bi bi-exclamation-circle-fill"></i>
                    </div>

                    {{-- Title & Message --}}
                    <h3 class="hp-simple-modal__title" id="parkClosedModalLabel">
                        Notice: The Park is Closed Today
                    </h3>
                    <p class="hp-simple-modal__desc">
                        {{ $parkSettings->close_description ?: 'Hinaguan Nature Park is temporarily closed today for maintenance or weather safety. We apologize for any inconvenience caused.' }}
                    </p>

                    {{-- Single Clean Action Button --}}
                    <button type="button" class="hp-simple-modal__btn" data-bs-dismiss="modal">
                        Understood
                    </button>
                </div>
            </div>
        </div>
    @endif

    <x-guest_chatbot />

</body>
</html>
