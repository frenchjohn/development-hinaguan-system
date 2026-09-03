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
                    <li><a href="#rates" data-nav-link>Rates</a></li>
                    <li><a href="#gallery" data-nav-link>Gallery</a></li>
                    <li><a href="#reviews" data-nav-link>Reviews</a></li>
                    <li><a href="#directions" data-nav-link>Directions</a></li>
                </ul>

                <!-- Live Status Pill with Hover Tooltip -->
                <div class="hp-nav-status relative group shrink-0">
                    @if (($parkSettings->park_status ?? 'open') === 'closed')
                        <div class="hp-status-pill hp-status-pill--closed cursor-help" tabindex="0">
                            <span class="hp-status-pill__dot"></span>
                            <span>Park Closed</span>
                        </div>
                        <div class="hp-status-tooltip pointer-events-none opacity-0 invisible group-hover:opacity-100 group-hover:visible group-focus-within:opacity-100 group-focus-within:visible transition-all duration-200" role="tooltip">
                            <p class="font-bold text-red-400 text-xs mb-1">Park Currently Closed</p>
                            <p class="text-xs text-white/90 leading-relaxed">{{ $parkSettings->close_description ?: 'The park is temporarily closed for maintenance or weather conditions.' }}</p>
                        </div>
                    @else
                        <div class="hp-status-pill hp-status-pill--open cursor-help" tabindex="0">
                            <span class="hp-status-pill__dot"></span>
                            <span>Park Open</span>
                        </div>
                        <div class="hp-status-tooltip pointer-events-none opacity-0 invisible group-hover:opacity-100 group-hover:visible group-focus-within:opacity-100 group-focus-within:visible transition-all duration-200" role="tooltip">
                            <p class="font-bold text-emerald-400 text-xs mb-1">Park Is Open</p>
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
    <nav class="hp-mobile-nav" aria-hidden="true">
        <div class="px-4 py-2 mb-2">
            @if (($parkSettings->park_status ?? 'open') === 'closed')
                <div class="inline-flex items-center gap-2 py-1.5 px-3 rounded-full bg-red-500/20 text-red-300 border border-red-500/30 text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    <span>Park Closed: {{ $parkSettings->close_description ?: 'Temporarily closed' }}</span>
                </div>
            @else
                <div class="inline-flex items-center gap-2 py-1.5 px-3 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Park Open Today</span>
                </div>
            @endif
        </div>
        <a href="#about" data-nav-link>About</a>
        <a href="#amenities" data-nav-link>Amenities</a>
        <a href="#activities" data-nav-link>Activities</a>
        <a href="#rates" data-nav-link>Rates</a>
        <a href="#gallery" data-nav-link>Gallery</a>
        <a href="#reviews" data-nav-link>Reviews</a>
        <a href="#directions" data-nav-link>Directions</a>
        <a href="{{ route('reservation') }}" class="hp-btn hp-btn--book">Book Now</a>
    </nav>

    {{-- Hero --}}
    <section class="hp-hero" id="home" data-section>
        <div class="hp-hero__bg" style="background-image: url('{{ asset('images/background.jpeg') }}')" aria-hidden="true"></div>
        <div class="hp-hero__overlay" aria-hidden="true"></div>

        @if ($weather || $nearEvent)
            <div class="hp-hero__side-widgets" data-animate="fade-left">
                @if ($weather)
                    <aside class="hp-weather" aria-label="Today's weather">
                        <p class="hp-weather__label">Today's Weather</p>
                        <div class="hp-weather__main">
                            @if ($weather['icon'])
                                <img src="{{ $weather['icon'] }}" alt="{{ $weather['condition'] }}" class="hp-weather__icon" width="44" height="44">
                            @endif
                            <div class="hp-weather__info">
                                <p class="hp-weather__temp">{{ round($weather['temp_c']) }}°C</p>
                                <p class="hp-weather__condition">{{ $weather['condition'] }}</p>
                            </div>
                        </div>
                        <p class="hp-weather__location">{{ $weather['location'] }}{{ !empty($weather['region']) ? ', '.$weather['region'] : '' }}</p>
                        <p class="hp-weather__meta">Feels like {{ round($weather['feelslike_c']) }}°C &middot; {{ $weather['humidity'] }}% humidity</p>

                        @if (!empty($weather['next_3_hours']))
                            <div class="hp-weather__hourly mt-2 pt-2 border-t border-white/20">
                                <p class="text-[0.62rem] font-bold uppercase tracking-wider text-hp-gold mb-1.5">Next 3 Hours</p>
                                <div class="grid grid-cols-3 gap-1.5 text-center">
                                    @foreach ($weather['next_3_hours'] as $hour)
                                        <div class="rounded-lg bg-white/10 p-1.5 backdrop-blur-sm flex flex-col items-center">
                                            <span class="text-[0.65rem] font-semibold text-white/90">{{ $hour['time_label'] }}</span>
                                            @if (!empty($hour['icon']))
                                                <img src="{{ $hour['icon'] }}" alt="{{ $hour['condition'] }}" class="h-6 w-6 my-0.5">
                                            @endif
                                            <span class="text-[0.72rem] font-bold text-white">{{ round($hour['temp_c']) }}°C</span>
                                            <span class="text-[0.58rem] text-[#6ab88c]">{{ $hour['chance_of_rain'] ?? 0 }}% rain</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </aside>
                @endif

                @if ($nearEvent)
                    <div class="hp-near-event-widget group" tabindex="0" aria-label="Near Event: {{ $nearEvent->title }}">
                        <div class="hp-near-event-widget__header">
                            <span class="hp-near-event-widget__label">
                                <span class="hp-near-event-widget__dot"></span>
                                Near Event
                            </span>
                            <span class="hp-near-event-widget__date">
                                {{ $nearEvent->day }} &middot; {{ \Carbon\Carbon::parse($nearEvent->date)->format('M d') }}
                                @if ($nearEvent->time)
                                    &middot; {{ $nearEvent->time }}
                                @endif
                            </span>
                        </div>

                        <h3 class="hp-near-event-widget__title">
                            {{ $nearEvent->title }}
                        </h3>

                        <div class="hp-near-event-widget__hint">
                            <span>Hover for details</span>
                            <svg class="h-3 w-3 text-[var(--hp-gold)] transition-transform group-hover:translate-y-0.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </div>

                        {{-- Smooth Expandable Description on Hover --}}
                        <div class="hp-near-event-widget__expand">
                            <p class="hp-near-event-widget__desc">
                                {{ $nearEvent->event }}
                            </p>
                            <a href="#events" class="hp-near-event-widget__link">
                                <span>View all events</span>
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                @endif
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

    <button class="hp-fab hp-fab--top" id="scrollToTop" aria-label="Scroll to top">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
    </button>

    <x-guest_chatbot />

</body>
</html>
