<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Reserve cottages, huts, and amenities at Hinaguan Nature Park in Jasaan, Misamis Oriental. Pick a date, choose your amenity, and confirm your booking in minutes.">

    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <title>Book a Visit — Hinaguan Nature Park</title>

    <link rel="icon" type="image/jpeg" href="{{ asset('storage/design_images/main_logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                for (let registration of registrations) {
                    registration.unregister();
                }
            });
            if ('caches' in window) {
                caches.keys().then(function(names) {
                    for (let name of names) {
                        caches.delete(name);
                    }
                });
            }
        }
    </script>

    @php
        $settings = $parkSettings ?? \App\Models\ParkSetting::first();
        $dayStartRaw = $settings->daytime_start ?? $settings->opening_time ?? '08:00';
        $dayEndRaw = $settings->daytime_end ?? $settings->closing_time ?? '17:00';
        $nightStartRaw = $settings->nighttime_start ?? $settings->daytime_end ?? '17:00';
        $nightEndRaw = $settings->nighttime_end ?? $settings->daytime_start ?? '08:00';

        $daytimeStart = strtotime((string) $dayStartRaw);
        $daytimeEnd = strtotime((string) $dayEndRaw);
        $nighttimeStart = strtotime((string) $nightStartRaw);
        $nighttimeEnd = strtotime((string) $nightEndRaw);

        $nowSeconds = strtotime(now()->format('H:i'));
        $isNighttimeNow = !($nowSeconds >= $daytimeStart && $nowSeconds < $daytimeEnd);
        $todayDate = now()->toDateString();

        $daytimeStartFormatted = date('g:i A', $daytimeStart);
        $daytimeEndFormatted = date('g:i A', $daytimeEnd);
        $nighttimeStartFormatted = date('g:i A', $nighttimeStart);
        $nighttimeEndFormatted = date('g:i A', $nighttimeEnd);
    @endphp
    <script>
        window.PARK_TODAY_DATE = @json($todayDate);
        window.PARK_IS_NIGHTTIME_NOW = @json($isNighttimeNow);
        window.PARK_DAYTIME_START = @json($dayStartRaw);
        window.PARK_DAYTIME_END = @json($dayEndRaw);
        window.PARK_NIGHTTIME_START = @json($nightStartRaw);
        window.PARK_NIGHTTIME_END = @json($nightEndRaw);
    </script>

    @vite(['resources/css/app.css', 'resources/css/reservationpage.css', 'resources/css/chatbot.css', 'resources/js/reservationpage.js', 'resources/js/guest_chatbot.js'])

</head>

<body class="antialiased rp-page" style="--rp-page-bg: url('{{ asset('images/background.jpeg') }}')">

    {{-- Site header --}}
    <div class="rp-site-header" id="rpSiteHeader">

        <div class="rp-topbar">

            <div class="rp-topbar__inner">

                <p class="rp-topbar__text">

                    <strong>Now Open!</strong>

                    Daytime: Adult &#8369;{{ $parkSettings->daytime_adult_entrance_fee ?? 70 }} &middot; Child &#8369;{{ $parkSettings->daytime_child_entrance_fee ?? 50 }} &nbsp;|&nbsp;

                    Overnight: Adult &#8369;{{ $parkSettings->nighttime_adult_entrance_fee ?? 100 }} &nbsp;|&nbsp;

                    <a href="{{ route('reservation') }}">Reserve Now</a>

                    &nbsp;&middot;&nbsp; Call: {{ $parkSettings->contact_number ?? '0917 861 8383' }}

                </p>

            </div>

        </div>



        <header class="rp-header is-scrolled" id="rpHeader">

            <div class="rp-header__inner">

                <a href="{{ route('home') }}" class="rp-logo">

                    <span class="rp-logo__icon">

                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">

                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-1.5 2.5-4 5-4 8a4 4 0 108 0c0-3-2.5-5.5-4-8z"/>

                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8M10 18h4"/>

                        </svg>

                    </span>

                    <span class="rp-logo__text">

                        <span class="rp-logo__name">Hinaguan Nature Park</span>

                        <span class="rp-logo__location">Jasaan, Misamis Oriental</span>

                    </span>

                </a>



                <nav class="rp-nav">

                    <ul class="rp-nav__links">

                        <li><a href="{{ route('home') }}#about">About</a></li>

                        <li><a href="{{ route('home') }}#amenities">Amenities</a></li>

                        <li><a href="{{ route('home') }}#activities">Activities</a></li>

                        <li><a href="{{ route('home') }}#rates">Rates</a></li>

                        <li><a href="{{ route('home') }}#gallery">Gallery</a></li>

                        <li><a href="{{ route('home') }}#directions">Directions</a></li>

                    </ul>

                    <a href="{{ route('reservation') }}" class="rp-btn rp-btn--book is-active">Book Now</a>

                </nav>



                <button class="rp-menu-toggle" aria-label="Open menu" aria-expanded="false">

                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">

                        <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>

                    </svg>

                </button>

            </div>

        </header>

    </div>



    <nav class="rp-mobile-nav" aria-hidden="true">

        <a href="{{ route('home') }}#about">About</a>

        <a href="{{ route('home') }}#amenities">Amenities</a>

        <a href="{{ route('home') }}#activities">Activities</a>

        <a href="{{ route('home') }}#rates">Rates</a>

        <a href="{{ route('home') }}#gallery">Gallery</a>

        <a href="{{ route('home') }}#directions">Directions</a>

        <a href="{{ route('reservation') }}" class="rp-btn rp-btn--book">Book Now</a>

    </nav>



    <main class="rp-main">

        {{-- ── Hero ── --}}

        <section class="rp-hero">

            <div class="rp-hero__content">

                <div class="rp-hero__intro" data-animate="fade-up">

                    <a href="{{ route('home') }}" class="rp-back-button">

                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">

                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>

                        </svg>

                        Back to Homepage

                    </a>

                    <span class="rp-label">Book Your Visit</span>

                    <h1 class="rp-title">Reserve Your Stay at <em>Hinaguan</em></h1>

                    <p class="rp-desc">Experience serenity by the riverside with nature-crafted cottages and cozy A-houses. Select your date below, review our simple guidelines, and secure your spot today.</p>

                </div>



                <div class="rp-policies" data-animate="fade-up" data-delay="150">

                    <div class="rp-policy-card">
                        <div class="rp-policy-card__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="m9 12 2 2 4-4"/>
                            </svg>
                        </div>
                        <div class="rp-policy-card__text">
                            <strong>Instant Reservation</strong>
                            <span>Automatically reserved immediately upon booking — no waiting for confirmation.</span>
                        </div>
                    </div>

                    <div class="rp-policy-card">
                        <div class="rp-policy-card__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="20" height="14" x="2" y="5" rx="2"/>
                                <line x1="2" x2="22" y1="10" y2="10"/>
                            </svg>
                        </div>
                        <div class="rp-policy-card__text">
                            <strong>50% Downpayment</strong>
                            <span>A 50% initial downpayment is required to lock in and secure your reserved slot.</span>
                        </div>
                    </div>

                    <div class="rp-policy-card">
                        <div class="rp-policy-card__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="4.93" x2="19.07" y1="4.93" y2="19.07"/>
                            </svg>
                        </div>
                        <div class="rp-policy-card__text">
                            <strong>Strictly No Refund</strong>
                            <span>All payments, reservation fees, and deposits are final and non-refundable.</span>
                        </div>
                    </div>

                    <div class="rp-policy-card">
                        <div class="rp-policy-card__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </div>
                        <div class="rp-policy-card__text">
                            <strong>Active Contact Info</strong>
                            <span>Please provide active contact details so our staff can reach you with stay updates.</span>
                        </div>
                    </div>

                </div>

            </div>

        </section>



        {{-- ── Scroll-to-booking hint (floats until the booking area is in view) ── --}}

        <button type="button" class="rp-scroll-hint" id="scrollHint" aria-label="Scroll down to the booking section">

            <span class="rp-scroll-hint__label">Start booking below</span>

            <span class="rp-scroll-hint__arrow" aria-hidden="true">

                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">

                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>

                </svg>

            </span>

        </button>



        {{-- ── Main Booking Content ── --}}
        <div class="rp-booking-content" id="mainBookingContent">

            {{-- ── Date selection CTA (compact banner) ── --}}

            <section class="rp-date-cta" data-animate="fade-up" data-delay="100" id="dateCtaSection">

            <div class="rp-date-cta__inner">

                <div class="rp-date-cta__left">

                    <span class="rp-date-cta__icon" aria-hidden="true">

                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">

                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />

                        </svg>

                    </span>

                    <div class="rp-date-cta__text">

                        <h2>Ready to reserve?</h2>

                        <p>Pick your visit date first to browse available spots, or tap any amenity below.</p>

                    </div>

                </div>

                <div class="rp-date-cta__action">

                    <button type="button" id="pickDateBtn" class="rp-date-cta__button">

                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">

                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />

                        </svg>

                        <span>Pick a Date</span>

                    </button>

                </div>

            </div>

        </section>



        {{-- ── Unified Reservation Date, Weather & Stay Panel ── --}}

        <section class="rp-booking-panel" data-animate="fade-up" data-delay="100" id="dateControlsSection" hidden>

            <div class="rp-bp-card">

                {{-- Top Row: Date Selector --}}
                <div class="rp-bp-header">

                    <div class="rp-bp-header__icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>

                    <div class="rp-bp-header__main">

                        <span class="rp-bp-label">Select reservation date</span>

                        <input id="reservation_date" name="reservation_date" type="hidden" value="" data-min-date="{{ now()->toDateString() }}">

                        <button type="button" id="reservationDateTrigger" class="rp-bp-date-trigger">

                            <span class="rp-bp-date-trigger__content">
                                <svg class="rp-bp-date-trigger__cal-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span id="reservationDateText">Select date</span>
                            </span>

                            <svg class="rp-bp-date-trigger__chevron" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>

                        </button>

                    </div>

                </div>

                {{-- Bottom Row: Weather & Stay (2 Columns) --}}
                <div class="rp-bp-body">

                    {{-- Weather Column --}}
                    <div class="rp-bp-col rp-bp-col--weather">

                        <span class="rp-bp-col__title">Weather forecast</span>

                        <div class="rp-bp-subcard rp-bp-subcard--weather" id="reservationWeatherPreview">

                            <div class="rp-weather-preview__wrap">

                                <img src="" alt="" class="rp-weather-preview__icon" id="weatherIcon" hidden>

                                <div class="rp-weather-preview__content">

                                    <strong id="weatherCondition">Checking forecast...</strong>

                                    <span id="weatherTemp"></span>

                                </div>

                            </div>

                            <p class="rp-weather-preview__empty" id="weatherEmpty" hidden>Weather forecast is available for up to 3 days ahead.</p>

                            <div class="rp-weather-preview__skeleton" id="weatherSkeleton">

                                <div class="rp-weather-preview__skeleton-icon"></div>

                                <div class="rp-weather-preview__skeleton-content">

                                    <div class="rp-weather-preview__skeleton-text"></div>

                                    <div class="rp-weather-preview__skeleton-text rp-weather-preview__skeleton-text--small"></div>

                                </div>

                            </div>

                        </div>

                    </div>

                    {{-- Your Stay Column --}}
                    <div class="rp-bp-col rp-bp-col--stay">

                        <span class="rp-bp-col__title">Your stay</span>

                        <div class="rp-bp-subcard rp-bp-subcard--stay" id="mainDateTimePreview">

                            {{-- Check-in Sub-column --}}
                            <div class="rp-stay-item rp-stay-item--checkin">

                                <div class="rp-stay-icon rp-stay-icon--in">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                                        <polyline points="10 17 15 12 10 7"/>
                                        <line x1="15" y1="12" x2="3" y2="12"/>
                                    </svg>
                                </div>

                                <div class="rp-stay-details">

                                    <span class="rp-stay-label">Check-in</span>

                                    <span class="rp-stay-date" id="mainCheckInDate">—</span>

                                    <strong class="rp-stay-time" id="mainCheckInTime">—</strong>

                                    <small class="rp-stay-session" id="mainCheckInSession"></small>

                                </div>

                            </div>

                            <div class="rp-stay-divider" aria-hidden="true"></div>

                            {{-- Check-out Sub-column --}}
                            <div class="rp-stay-item rp-stay-item--checkout">

                                <div class="rp-stay-icon rp-stay-icon--out">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="5" y1="12" x2="19" y2="12"/>
                                        <polyline points="12 5 19 12 12 19"/>
                                    </svg>
                                </div>

                                <div class="rp-stay-details">

                                    <span class="rp-stay-label">Check-out</span>

                                    <span class="rp-stay-date" id="mainCheckOutDate">—</span>

                                    <strong class="rp-stay-time" id="mainCheckOutTime">—</strong>

                                    <small class="rp-stay-session" id="mainCheckOutSession"></small>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                {{-- Bottom Centered Anchor Indicator --}}
                <div class="rp-bp-anchor-btn" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <polyline points="19 12 12 19 5 12"/>
                    </svg>
                </div>

            </div>

        </section>



        {{-- ── Amenity grid ── --}}

        <section class="rp-grid" id="reservationGridShell" data-animate="fade-up" data-delay="200">

            {{-- Section heading --}}

            <div class="rp-grid__head">

                <div>

                    <h2 class="rp-grid__title">Choose your spot</h2>

                </div>

                <p class="rp-grid__sub">Tap an amenity to see its calendar and rates, or start with a date above.</p>

            </div>



            {{-- Skeleton loading state --}}

            <div class="rp-grid__skeleton" id="gridSkeleton">

                @for($i = 1; $i <= 6; $i++)

                    <div class="rp-card rp-card--skeleton">

                        <div class="rp-card__skeleton-image"></div>

                        <div class="rp-card__skeleton-content">

                            <div class="rp-card__skeleton-title"></div>

                            <div class="rp-card__skeleton-meta"></div>

                            <div class="rp-card__skeleton-price"></div>

                        </div>

                    </div>

                @endfor

            </div>



            <div class="rp-grid__loading" id="availabilityLoading" hidden>

                <div class="rp-grid__loading-spinner" aria-hidden="true"></div>

                <p>Loading amenities for this date and slot…</p>

            </div>



            @if($amenities->isEmpty())

                <div class="rp-empty">

                    <p>No amenities are available right now. Please check back later.</p>

                </div>

            @else

                @php
                    $getCategoryName = function($name) {
                        $trimmed = trim((string) $name);
                        $base = trim(preg_replace('/\s*[-_#]?\s*(?:\d+|[A-Z]\b|[IVXLCDM]+)$/i', '', $trimmed));
                        if (empty($base)) {
                            $base = $trimmed;
                        }
                        if (preg_match('/s$/i', $base)) {
                            return $base;
                        }
                        if (preg_match('/(sh|ch|x|z)$/i', $base)) {
                            return $base . 'es';
                        }
                        if (preg_match('/[^aeiou]y$/i', $base)) {
                            return substr($base, 0, -1) . 'ies';
                        }
                        return $base . 's';
                    };

                    $categories = $amenities->groupBy(function($amenity) use ($getCategoryName) {
                        return $getCategoryName($amenity->amenities_name);
                    });
                @endphp

                {{-- Category Filter Quick Pills (if more than 1 category) --}}
                @if($categories->count() > 1)
                    <div class="rp-category-nav" id="categoryNav">
                        <button type="button" class="rp-category-pill is-active" data-category-filter="all">
                            <span>All Amenities</span>
                            <small>({{ $amenities->count() }})</small>
                        </button>
                        @foreach($categories as $categoryName => $groupAmenities)
                            <button type="button" class="rp-category-pill" data-category-filter="{{ $categoryName }}">
                                <span>{{ $categoryName }}</span>
                                <small>({{ $groupAmenities->count() }})</small>
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="rp-grid__categories" id="amenityGrid">
                    @php $cardIndex = 0; @endphp
                    @foreach($categories as $categoryName => $categoryAmenities)
                        <div class="rp-category-group" data-category="{{ $categoryName }}">
                            <div class="rp-category-header">
                                <div class="rp-category-title-wrap">
                                    <span class="rp-category-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                        </svg>
                                    </span>
                                    <h3 class="rp-category-title">{{ $categoryName }}:</h3>
                                    <span class="rp-category-badge">{{ $categoryAmenities->count() }} {{ \Illuminate\Support\Str::plural('unit', $categoryAmenities->count()) }}</span>
                                </div>
                                <div class="rp-category-line"></div>
                            </div>

                            <div class="rp-grid__list">
                                @foreach($categoryAmenities as $amenity)
                                    @php
                                        $minPrice = collect([$amenity->daytime_price, $amenity->nighttime_price])->filter()->min();
                                        $maxPrice = collect([$amenity->daytime_price, $amenity->nighttime_price])->filter()->max();
                                        $hasSale = $amenity->sale_percentage && $amenity->sale_percentage > 0;
                                        $cardIndex++;
                                    @endphp

                                    <article class="rp-card"
                                        data-animate="fade-up"
                                        data-delay="{{ min($cardIndex * 50, 360) }}"
                                        data-amenity-id="{{ $amenity->id }}"
                                        data-name="{{ $amenity->amenities_name }}"
                                        data-category="{{ $categoryName }}"
                                        data-min-capacity="{{ $amenity->minimum_capacity }}"
                                        data-max-capacity="{{ $amenity->maximum_capacity }}"
                                        data-min-price="{{ $minPrice }}"
                                        data-max-price="{{ $maxPrice }}"
                                        data-daytime-price="{{ $amenity->daytime_price }}"
                                        data-nighttime-price="{{ $amenity->nighttime_price }}"
                                        data-is-aircon="{{ $amenity->benefits?->is_aircon ? '1' : '0' }}"
                                        data-free-entrance="{{ $amenity->benefits?->free_entrance ? '1' : '0' }}"
                                        data-free-pool="{{ $amenity->benefits?->free_pool ? '1' : '0' }}"
                                        data-additional="{{ $amenity->additional_per_head ?? '0' }}"
                                        data-description="{{ $amenity->description ?? '' }}"
                                        data-sale-percentage="{{ $amenity->sale_percentage ?? 0 }}"
                                        data-original-daytime-price="{{ $amenity->original_daytime_price ?? $amenity->daytime_price }}"
                                        data-original-nighttime-price="{{ $amenity->original_nighttime_price ?? $amenity->nighttime_price }}">

                                        {{-- Individual Amenity Selection Button / Checkbox --}}
                                        <button type="button" class="rp-card__select-btn" data-card-select aria-pressed="false" aria-label="Select {{ $amenity->amenities_name }} for multi-booking" title="Select this amenity">
                                            <span class="rp-card__select-box">
                                                <svg class="rp-card__select-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                            <span class="rp-card__select-text">Select</span>
                                        </button>

                                        <button type="button" class="rp-card__button" data-open-modal>
                                            @if($amenity->image)
                                                <div class="rp-card__image" style="background-image:url('{{ asset('storage/' . $amenity->image) }}')"></div>
                                            @else
                                                <div class="rp-card__image rp-card__image--empty"></div>
                                            @endif

                                            <div class="rp-card__chips">
                                                <span class="rp-card__chip rp-card__chip--capacity">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                    {{ $amenity->minimum_capacity }}–{{ $amenity->maximum_capacity }} pax
                                                </span>

                                                @if($minPrice)
                                                    <span class="rp-card__chip rp-card__chip--price">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        from &#8369;{{ number_format($minPrice) }}
                                                    </span>
                                                @endif

                                                @if($amenity->benefits?->is_aircon)
                                                    <span class="rp-card__chip rp-card__chip--aircon bg-cyan-500/20 text-cyan-200 border border-cyan-400/30">
                                                        <i class="bi bi-snow"></i> Aircon
                                                    </span>
                                                @endif
                                                @if($amenity->benefits?->free_pool)
                                                    <span class="rp-card__chip rp-card__chip--pool bg-blue-500/20 text-blue-200 border border-blue-400/30">
                                                        <i class="bi bi-water"></i> Free Pool
                                                    </span>
                                                @endif
                                                @if($amenity->benefits?->free_entrance)
                                                    <span class="rp-card__chip rp-card__chip--entrance bg-emerald-500/20 text-emerald-200 border border-emerald-400/30">
                                                        <i class="bi bi-ticket-perforated-fill"></i> Free Entrance
                                                    </span>
                                                @endif

                                                @if($hasSale)
                                                    <span class="rp-card__sale-badge">{{ $amenity->sale_percentage }}% OFF</span>
                                                @endif
                                            </div>

                                            <div class="rp-card__overlay">
                                                <span>{{ $amenity->amenities_name }}</span>
                                            </div>

                                            <span class="rp-card__cta">View &amp; Book</span>
                                        </button>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="rp-empty" id="emptyState" style="display:none;">
                    <p>No amenities are available for the selected date and booking type.</p>
                </div>

            @endif

        </section>

        </div> {{-- ── End #mainBookingContent ── --}}



        {{-- ── Reassurance strip ── --}}

        <section class="rp-perks" data-animate="fade-up">

            <div class="rp-perk">

                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>

                <div><strong>Pay a small deposit</strong><span>50% down to lock in your booking</span></div>

            </div>

            <div class="rp-perk">

                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>

                <div><strong>QR check-in</strong><span>Your QR is sent straight to your email</span></div>

            </div>

            <div class="rp-perk">

                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>

                <div><strong>No hidden fees</strong><span>Pay the balance at check-in</span></div>

            </div>

        </section>



        {{-- ── Help & FAQ ── --}}

        <section class="rp-help" data-animate="fade-up" id="help">

            <div class="rp-help__intro">

                <span class="rp-label">Need a hand?</span>

                <h2 class="rp-help__title">Questions about your visit</h2>

                <p>Everything you need to know before booking. Still unsure? Give us a ring — we are happy to help.</p>

                <a href="tel:+63{{ preg_replace('/[^0-9]/', '', $parkSettings->contact_number ?? '09178618383') }}" class="rp-help__call">

                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>

                    Call {{ $parkSettings->contact_number ?? '0917 861 8383' }}

                </a>

            </div>

            <div class="rp-help__faq">

                <details class="rp-faq" open>

                    <summary>Do I need to pay the full amount when booking?</summary>

                    <p>No — a 50% deposit secures your reservation. You can settle the remaining balance when you check in at the park.</p>

                </details>

                <details class="rp-faq">

                    <summary>How will I receive my booking confirmation?</summary>

                    <p>Once your reservation is confirmed, a QR code is emailed to you. Present it at the check-in counter on the day of your visit.</p>

                </details>

                <details class="rp-faq">

                    <summary>What are the park hours?</summary>

                    <p>Daytime visits run from {{ $parkSettings?->opening_time ? date('g:i A', strtotime($parkSettings->opening_time)) : '6:00 AM' }} to {{ $parkSettings?->closing_time ? date('g:i A', strtotime($parkSettings->closing_time)) : '6:00 PM' }}. Overnight guests check in from {{ $parkSettings?->nighttime_start ? date('g:i A', strtotime($parkSettings->nighttime_start)) : '6:00 PM' }}.</p>

                </details>

                <details class="rp-faq">

                    <summary>Can I book more than one amenity at a time?</summary>

                    <p>Yes! Turn on "Multiple selection" above the amenity list to reserve several cottages or areas for your group in a single booking.</p>

                </details>

            </div>

        </section>



        {{-- ── Floating selection bar ── --}}

        <div class="rp-floating-actions" id="selectionFloatingBar" hidden>

            <div class="rp-floating-actions__copy">

                <strong id="selectionCountLabel">0 amenities selected</strong>

                <span>Tap to review your picks</span>

            </div>

            <button type="button" id="selectionCheckoutBtn">Review selection</button>

        </div>



        {{-- ── Selection sheet ── --}}

        <div class="rp-selection-sheet" id="selectionSheet" aria-hidden="true">

            <div class="rp-selection-sheet__backdrop" data-close-selection></div>

            <div class="rp-selection-sheet__panel">

                <div class="rp-selection-sheet__header">

                    <div>

                        <p class="rp-modal__eyebrow">Selection summary</p>

                        <h3>Your chosen amenities</h3>

                    </div>

                    <button type="button" class="rp-modal__close" data-close-selection>&times;</button>

                </div>

                <div class="rp-selection-sheet__total" id="selectionTotalBox">

                    <div class="rp-selection-sheet__math" id="selectionMathText">No items selected</div>

                    <div class="rp-selection-sheet__total-price" id="selectionTotalPrice">&#8369;0.00</div>

                </div>

                <ul class="rp-selection-sheet__list" id="selectionSummaryList"></ul>

                <button type="button" id="selectionContinueBtn" class="rp-booking-form__button">Continue booking</button>

            </div>

        </div>



        {{-- ── Amenity detail / booking modal ── --}}

        {{-- ── Amenity detail / booking modal ── --}}

        <div class="rp-modal" id="amenityModal" aria-hidden="true">

            <div class="rp-modal__backdrop" data-close-modal></div>

            <div class="rp-modal__panel rp-modal__panel--scroll">

                <div class="rp-modal__header">

                    <div>

                        <p class="rp-modal__eyebrow">Amenity details</p>

                        <h2 id="modalName">Amenity name</h2>

                    </div>

                    <button type="button" class="rp-modal__close" data-close-modal>&times;</button>

                </div>

                <div class="rp-modal__content">

                    <div class="rp-modal__left">

                        <div class="rp-modal__summary">

                            <div class="rp-modal__meta" id="modalMetaBlock">

                                <div class="rp-modal__meta-item"><span>Stay Duration</span><strong id="modalDate"></strong></div>

                                <div class="rp-modal__meta-item"><span>Time Slots</span><strong id="modalSlot"></strong></div>

                                <div class="rp-modal__meta-item"><span>Capacity</span><strong id="modalCapacity"></strong></div>

                            </div>

                            {{-- Multi-Amenity Selected Items Container --}}
                            <div id="modalMultiAmenityContainer" class="rp-modal__multi-container" style="display: none;">
                                <div class="rp-modal__section-header">
                                    <h4 class="rp-modal__section-title">Selected Amenities & Schedules</h4>
                                    <span class="rp-modal__section-hint">Click &ldquo;Edit Dates&rdquo; to customize stay</span>
                                </div>
                                <div id="modalMultiAmenityList" class="rp-selected-amenities-list"></div>
                            </div>

                            <div class="rp-modal__pricebox">

                                <span id="modalPriceLabel">Price</span>

                                <strong id="modalPriceValue">&#8369;0.00</strong>

                                <p id="modalPriceHint"></p>

                                <div id="modalSaleInfo" class="rp-modal__sale-info" style="display: none;">

                                    <span class="rp-modal__original-price" id="modalOriginalPrice">&#8369;0.00</span>

                                    <span class="rp-modal__sale-percentage" id="modalSalePercentage">0% OFF</span>

                                </div>

                            </div>

                            <div id="modalBenefits" class="rp-modal__benefits flex flex-wrap gap-1.5 my-2"></div>

                            <div id="airconChoice" class="rp-modal__aircon" style="display: none;"></div>

                            <p class="rp-modal__text" id="modalDescription"></p>

                        </div>

                    </div>

                    <div class="rp-modal__right">

                        <form class="rp-booking-form is-hidden" id="bookingForm">

                            <h3>Guest reservation</h3>

                            <input type="hidden" name="check_in" id="bookingCheckIn">

                            <input type="hidden" name="check_out" id="bookingCheckOut">

                            <input type="hidden" name="reservation_date" id="bookingReservationDate">

                            <input type="hidden" name="end_date" id="bookingEndDate">

                            <input type="hidden" name="start_slot" id="bookingStartSlot">

                            <input type="hidden" name="end_slot" id="bookingEndSlot">

                            <input type="hidden" name="total_days" id="bookingTotalDays">

                            <label>

                                Booker name

                                <input type="text" name="booker_name" placeholder="Enter booker name" required>

                            </label>

                            <label>

                                Phone

                                <input type="tel" name="phone" placeholder="Enter phone number" required>

                            </label>

                            <label>

                                Email

                                <input type="email" name="email" placeholder="Enter email address" required>

                            </label>

                            <label>

                                Number of guests

                                <input type="number" name="number_of_guests" min="1" required>

                            </label>

                            <button type="submit" class="rp-booking-form__button">Proceed to Payment &rarr;</button>

                            <p class="rp-booking-form__message" id="bookingNotice"></p>

                        </form>

                    </div>

                </div>

            </div>

        </div>

        {{-- ── PayMongo Payment Modal ── --}}
        <div class="rp-modal" id="paymongoPaymentModal" aria-hidden="true">

            <div class="rp-modal__backdrop" data-close-payment-modal></div>

            <div class="rp-modal__panel rp-modal__panel--payment rp-modal__panel--scroll">

                <div class="rp-modal__header">

                    <div>

                        <p class="rp-modal__eyebrow" id="pmStepEyebrow">Step 2 of 3 &middot; Select Payment Method</p>

                        <h2 id="pmStepTitle">Choose Payment Option</h2>

                    </div>

                    <button type="button" class="rp-modal__close" data-close-payment-modal>&times;</button>

                </div>

                <div class="rp-modal__content rp-modal__content--stacked">

                    {{-- Summary Box --}}
                    <div class="rp-pay-summary">

                        <div class="rp-pay-summary__item">

                            <span>Total Booking</span>

                            <strong id="pmSummaryTotal">&#8369;0.00</strong>

                        </div>

                        <div class="rp-pay-summary__item rp-pay-summary__item--highlight">

                            <span>50% Deposit Due Now</span>

                            <strong id="pmSummaryDeposit">&#8369;0.00</strong>

                        </div>

                        <div class="rp-pay-summary__item">

                            <span>Pay at Check-in</span>

                            <strong id="pmSummaryBalance">&#8369;0.00</strong>

                        </div>

                    </div>

                    {{-- STEP 2 VIEW: Method Selector --}}
                    <div id="pmStepSelect">

                        <p class="rp-modal__hint">Select your preferred payment gateway method below:</p>

                        <div class="rp-pay-tabs" role="tablist">

                            <button type="button" class="rp-pay-tab is-active" data-pm-tab="gcash">

                                <span class="rp-pay-tab__badge">GCash</span>

                                <span>GCash E-Wallet</span>

                            </button>

                            <button type="button" class="rp-pay-tab" data-pm-tab="paymaya">

                                <span class="rp-pay-tab__badge rp-pay-tab__badge--maya">Maya</span>

                                <span>PayMaya / Maya</span>

                            </button>

                            <button type="button" class="rp-pay-tab" data-pm-tab="card">

                                <span class="rp-pay-tab__badge rp-pay-tab__badge--card">Card</span>

                                <span>Credit / Debit Card</span>

                            </button>

                            <button type="button" class="rp-pay-tab" data-pm-tab="qrph">

                                <span class="rp-pay-tab__badge rp-pay-tab__badge--qr">QR</span>

                                <span>QR Ph Scan</span>

                            </button>

                        </div>

                        <button type="button" class="rp-booking-form__button rp-booking-form__button--primary" id="pmProceedToStep3Btn" style="margin-top: 1.25rem;">
                            Proceed to Payment &rarr;
                        </button>

                    </div>

                    {{-- STEP 3 VIEW: Active Payment Authorization & Countdown --}}
                    <div id="pmStepProcess" hidden>

                        <div class="rp-step3-topbar">

                            <button type="button" class="rp-pay-back-btn" id="pmBackToStep2Btn">
                                &larr; Change Payment Method
                            </button>

                            <div class="rp-pay-timer" id="pmTimerBox">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Session expires in</span>
                                <strong id="pmCountdown">10:00</strong>
                            </div>

                        </div>

                        {{-- Tab Panels --}}
                        <div class="rp-pay-panels">

                            {{-- GCash Panel --}}
                            <div class="rp-pay-panel is-active" id="pmPanelGcash" data-pm-panel="gcash">

                                <div class="rp-pay-info">

                                    <p>Authorize your deposit payment securely via <strong>GCash</strong> in the portal below.</p>

                                </div>

                            </div>

                            {{-- PayMaya Panel --}}
                            <div class="rp-pay-panel" id="pmPanelPaymaya" data-pm-panel="paymaya" style="display:none;">

                                <div class="rp-pay-info">

                                    <p>Authorize your deposit payment securely via <strong>PayMaya / Maya</strong> in the portal below.</p>

                                </div>

                            </div>

                            {{-- Card Panel --}}
                            <div class="rp-pay-panel" id="pmPanelCard" data-pm-panel="card" style="display:none;">

                                <form id="pmCardForm" class="rp-card-form">

                                    <div class="rp-card-form__field">

                                        <label for="pmCardNumber">Card Number</label>

                                        <input type="text" id="pmCardNumber" placeholder="4532 0000 0000 0000" maxlength="19" required>

                                    </div>

                                    <div class="rp-card-form__row">

                                        <div class="rp-card-form__field">

                                            <label for="pmCardExpiry">Expiry Date</label>

                                            <input type="text" id="pmCardExpiry" placeholder="MM / YY" maxlength="5" required>

                                        </div>

                                        <div class="rp-card-form__field">

                                            <label for="pmCardCvc">CVC / CVV</label>

                                            <input type="text" id="pmCardCvc" placeholder="123" maxlength="4" required>

                                        </div>

                                    </div>

                                    <button type="submit" class="rp-booking-form__button" id="pmPayCardBtn">Authorize Card Payment &rarr;</button>

                                </form>

                            </div>

                            {{-- QR Ph Panel --}}
                            <div class="rp-pay-panel" id="pmPanelQrph" data-pm-panel="qrph" style="display:none;">

                                <div class="rp-pay-info">

                                    <p>Scan this QR Ph code using your e-wallet or mobile banking app (GCash, Maya, BDO, BPI, etc.).</p>

                                </div>

                                <div class="rp-qr-box" id="pmQrContainer">

                                    <img src="" alt="QR Ph Code" id="pmQrImage" class="rp-qr-image">

                                    <p class="rp-qr-hint">Scan QR code using any supported mobile app to complete payment.</p>

                                </div>

                            </div>

                        </div>

                        {{-- Embedded Sandbox Iframe Container --}}
                        <div class="rp-iframe-box" id="pmIframeContainer" hidden>

                            <div class="rp-iframe-header">

                                <span class="rp-iframe-dot"></span>

                                <span>PayMongo Payment Authorization (In-Website)</span>

                            </div>

                            <iframe id="pmAuthIframe" src="about:blank" title="PayMongo Authorization"></iframe>

                        </div>

                        {{-- Live Status Indicator --}}
                        <div class="rp-pay-status" id="pmStatusBox" hidden>

                            <div class="rp-pay-status__spinner"></div>

                            <div class="rp-pay-status__text" id="pmStatusText">Waiting for payment confirmation…</div>

                        </div>

                        <p class="rp-booking-form__message" id="pmNotice"></p>

                    </div>

                </div>

            </div>

        </div>



        {{-- ── Cancel confirmation modal ── --}}

        <div class="rp-modal" id="cancelConfirmModal" aria-hidden="true">

            <div class="rp-modal__backdrop" data-close-cancel-confirm></div>

            <div class="rp-modal__panel">

                <div class="rp-modal__header">

                    <h2>Cancel reservation?</h2>

                    <button type="button" class="rp-modal__close" data-close-cancel-confirm>&times;</button>

                </div>

                <div class="rp-modal__content">

                    <p>Are you sure you want to cancel? This will refresh the page.</p>

                    <div class="rp-modal__actions">

                        <button type="button" class="rp-modal__btn rp-modal__btn--secondary" data-close-cancel-confirm>No</button>

                        <button type="button" class="rp-modal__btn rp-modal__btn--primary" id="confirmCancelBtn">Yes, cancel</button>

                    </div>

                </div>

            </div>

        </div>



        {{-- ── Date picker modal ── --}}

        <div class="rp-modal" id="datePickerModal" aria-hidden="true">

            <div class="rp-modal__backdrop" data-close-date-picker></div>

            <div class="rp-modal__panel rp-modal__panel--calendar-split rp-modal__panel--scroll">

                <div class="rp-modal__header" style="margin-bottom: 0.85rem;">

                    <div>

                        <p class="rp-modal__eyebrow">When do you want to visit?</p>

                        <h2 style="font-size: 1.35rem; margin: 0;">Select reservation date</h2>

                    </div>

                    <button type="button" class="rp-modal__close" data-close-date-picker>&times;</button>

                </div>

                <div class="rp-modal__content rp-dp-split-layout">

                    {{-- Left Column: Calendar Picker --}}
                    <div class="rp-dp-left-col">
                        <div class="rp-dp-toolbar" style="margin-bottom: 0.5rem;">
                            <div class="rp-dp-toolbar__field rp-dp-toolbar__field--month">
                                <label class="rp-dp-toolbar__label" for="datePickerMonth">Month</label>
                                <div class="rp-select-wrap">
                                    <select id="datePickerMonth" class="rp-calendar-controls__select">
                                        <option value="0">January</option>
                                        <option value="1">February</option>
                                        <option value="2">March</option>
                                        <option value="3">April</option>
                                        <option value="4">May</option>
                                        <option value="5">June</option>
                                        <option value="6">July</option>
                                        <option value="7">August</option>
                                        <option value="8">September</option>
                                        <option value="9">October</option>
                                        <option value="10">November</option>
                                        <option value="11">December</option>
                                    </select>
                                </div>
                            </div>
                            <div class="rp-dp-toolbar__field rp-dp-toolbar__field--year">
                                <label class="rp-dp-toolbar__label" for="datePickerYear">Year</label>
                                <div class="rp-select-wrap">
                                    <select id="datePickerYear" class="rp-calendar-controls__select"></select>
                                </div>
                            </div>
                        </div>

                        <div class="rp-calendar" id="datePickerDays"></div>

                        <p class="rp-dp-hint" style="margin: 0.4rem 0 0; font-size: 0.72rem;"><span class="rp-dp-hint__dot" aria-hidden="true"></span> Click a start date then an end date for multi-day stays &mdash; or click the same date for 1 day.</p>
                    </div>

                    {{-- Right Column: Check-in & Check-out Session Picker & Final Confirmation --}}
                    <div class="rp-dp-right-col">
                        <h3 class="rp-dp-sidebar-title">Reservation Schedule</h3>

                        {{-- Check-in Time Card --}}
                        <div class="rp-dp-time-card">
                            <div class="rp-dp-time-card__head">
                                <span class="rp-dp-time-card__label">Check-in</span>
                                <span class="rp-dp-time-card__date" id="dpCheckInDate">Select date</span>
                            </div>
                            <div class="rp-dp-session-pick" role="group" aria-label="Check-in session">
                                <button type="button" class="rp-session-btn is-active" data-dp-start-slot="Daytime" id="dpStartSlotDaytime">
                                    <span><i class="bi bi-sun-fill text-amber-400 me-1"></i> Daytime</span>
                                    <small class="rp-session-time" id="dpCheckInDaytimeTimeLabel">{{ $daytimeStartFormatted }}</small>
                                </button>
                                <button type="button" class="rp-session-btn" data-dp-start-slot="Nighttime" id="dpStartSlotNighttime">
                                    <span><i class="bi bi-moon-stars-fill text-indigo-300 me-1"></i> Overnight</span>
                                    <small class="rp-session-time" id="dpCheckInOvernightTimeLabel">{{ $nighttimeStartFormatted }}</small>
                                </button>
                            </div>
                            <div class="rp-dp-time-card__preview" id="dpCheckInPreviewText">Select date on calendar</div>
                        </div>

                        {{-- Check-out Time Card --}}
                        <div class="rp-dp-time-card">
                            <div class="rp-dp-time-card__head">
                                <span class="rp-dp-time-card__label">Check-out</span>
                                <span class="rp-dp-time-card__date" id="dpCheckOutDate">Select date</span>
                            </div>
                            <div class="rp-dp-session-pick" role="group" aria-label="Check-out session">
                                <button type="button" class="rp-session-btn is-active" data-dp-end-slot="Daytime" id="dpEndSlotDaytime">
                                    <span><i class="bi bi-sun-fill text-amber-400 me-1"></i> Daytime</span>
                                    <small class="rp-session-time" id="dpCheckOutDaytimeTimeLabel">{{ $daytimeEndFormatted }}</small>
                                </button>
                                <button type="button" class="rp-session-btn" data-dp-end-slot="Nighttime" id="dpEndSlotNighttime">
                                    <span><i class="bi bi-moon-stars-fill text-indigo-300 me-1"></i> Overnight</span>
                                    <small class="rp-session-time" id="dpCheckOutOvernightTimeLabel">{{ $nighttimeEndFormatted }} next day</small>
                                </button>
                            </div>
                            <div class="rp-dp-time-card__preview" id="dpCheckOutPreviewText">Select date on calendar</div>
                        </div>

                        {{-- Stay Summary Badge --}}
                        <div class="rp-dp-summary-badge" id="dpStaySummaryBadge">1 Day · Daytime</div>

                        {{-- Confirm Date Action Button --}}
                        <button type="button" class="rp-booking-form__button rp-dp-confirm-btn" id="dpConfirmDateBtn">
                            Confirm Date &rarr;
                        </button>
                    </div>

                </div>

            </div>

        </div>



        {{-- ── Availability calendar modal ── --}}

        <div class="rp-modal" id="availabilityModal" aria-hidden="true">

            <div class="rp-modal__backdrop" data-close-availability-modal></div>

            <div class="rp-modal__panel rp-modal__panel--calendar-split rp-modal__panel--scroll">

                <div class="rp-modal__header" style="margin-bottom: 0.85rem;">

                    <div>

                        <p class="rp-modal__eyebrow">Availability calendar</p>

                        <h2 id="availabilityModalTitle" style="font-size: 1.35rem; margin: 0;">Amenity name</h2>

                    </div>

                    <button type="button" class="rp-modal__close" data-close-availability-modal>&times;</button>

                </div>

                <div class="rp-modal__content rp-dp-split-layout">

                    {{-- Left Column: Calendar Picker --}}
                    <div class="rp-dp-left-col">
                        <div class="rp-dp-toolbar" style="margin-bottom: 0.5rem;">
                            <div class="rp-dp-toolbar__field rp-dp-toolbar__field--month">
                                <label class="rp-dp-toolbar__label" for="calendarMonth">Month</label>
                                <div class="rp-select-wrap">
                                    <select id="calendarMonth" class="rp-calendar__select">
                                        <option value="0">January</option>
                                        <option value="1">February</option>
                                        <option value="2">March</option>
                                        <option value="3">April</option>
                                        <option value="4">May</option>
                                        <option value="5">June</option>
                                        <option value="6">July</option>
                                        <option value="7">August</option>
                                        <option value="8">September</option>
                                        <option value="9">October</option>
                                        <option value="10">November</option>
                                        <option value="11">December</option>
                                    </select>
                                </div>
                            </div>
                            <div class="rp-dp-toolbar__field rp-dp-toolbar__field--year">
                                <label class="rp-dp-toolbar__label" for="calendarYear">Year</label>
                                <div class="rp-select-wrap">
                                    <select id="calendarYear" class="rp-calendar__select"></select>
                                </div>
                            </div>
                        </div>

                        <div class="rp-calendar-wrap">
                            <div class="rp-calendar" id="availabilityCalendar" role="grid" aria-label="Available dates"></div>
                        </div>

                        <p class="rp-modal__hint" style="margin: 0.4rem 0 0; font-size: 0.72rem;">Available dates are highlighted. Click start date then end date for multi-day stays.</p>
                    </div>

                    {{-- Right Column: Check-in & Check-out Session Picker & Final Confirmation --}}
                    <div class="rp-dp-right-col">
                        <h3 class="rp-dp-sidebar-title">Reservation Schedule</h3>

                        {{-- Check-in Time Card --}}
                        <div class="rp-dp-time-card">
                            <div class="rp-dp-time-card__head">
                                <span class="rp-dp-time-card__label">Check-in</span>
                                <span class="rp-dp-time-card__date" id="avCheckInDate">Pick a date</span>
                            </div>
                            <div class="rp-dp-session-pick" role="group" aria-label="Check-in session">
                                <button type="button" class="rp-session-btn is-active" data-av-start-slot="Daytime" id="avStartSlotDaytime">
                                    <span><i class="bi bi-sun-fill text-amber-400 me-1"></i> Daytime</span>
                                    <small class="rp-session-time" id="avCheckInDaytimeTimeLabel">{{ $daytimeStartFormatted }}</small>
                                </button>
                                <button type="button" class="rp-session-btn" data-av-start-slot="Nighttime" id="avStartSlotNighttime">
                                    <span><i class="bi bi-moon-stars-fill text-indigo-300 me-1"></i> Overnight</span>
                                    <small class="rp-session-time" id="avCheckInOvernightTimeLabel">{{ $nighttimeStartFormatted }}</small>
                                </button>
                            </div>
                            <div class="rp-dp-time-card__preview" id="avCheckInPreviewText">Pick a date on calendar</div>
                        </div>

                        {{-- Check-out Time Card --}}
                        <div class="rp-dp-time-card">
                            <div class="rp-dp-time-card__head">
                                <span class="rp-dp-time-card__label">Check-out</span>
                                <span class="rp-dp-time-card__date" id="avCheckOutDate">Pick a date</span>
                            </div>
                            <div class="rp-dp-session-pick" role="group" aria-label="Check-out session">
                                <button type="button" class="rp-session-btn is-active" data-av-end-slot="Daytime" id="avEndSlotDaytime">
                                    <span><i class="bi bi-sun-fill text-amber-400 me-1"></i> Daytime</span>
                                    <small class="rp-session-time" id="avCheckOutDaytimeTimeLabel">{{ $daytimeEndFormatted }}</small>
                                </button>
                                <button type="button" class="rp-session-btn" data-av-end-slot="Nighttime" id="avEndSlotNighttime">
                                    <span><i class="bi bi-moon-stars-fill text-indigo-300 me-1"></i> Overnight</span>
                                    <small class="rp-session-time" id="avCheckOutOvernightTimeLabel">{{ $nighttimeEndFormatted }} next day</small>
                                </button>
                            </div>
                            <div class="rp-dp-time-card__preview" id="avCheckOutPreviewText">Pick a date on calendar</div>
                        </div>

                        {{-- Stay Summary Badge --}}
                        <div class="rp-dp-summary-badge" id="avStaySummaryBadge">1 Day · Daytime</div>

                        {{-- Confirm Date Action Button --}}
                        <button type="button" class="rp-booking-form__button rp-dp-confirm-btn" id="avConfirmDateBtn">
                            Confirm Date &rarr;
                        </button>
                    </div>

                </div>

            </div>

        </div>



        {{-- ── Edit Individual Amenity Schedule Modal ── --}}
        <div class="rp-modal" id="editAmenityScheduleModal" aria-hidden="true" style="z-index: 130;">
            <div class="rp-modal__backdrop" data-close-edit-schedule-modal></div>
            <div class="rp-modal__panel rp-modal__panel--compact rp-modal__panel--scroll">
                <div class="rp-modal__header">
                    <div>
                        <p class="rp-modal__eyebrow">Customize Amenity Stay</p>
                        <h2 id="editScheduleAmenityName">Amenity Name</h2>
                    </div>
                    <button type="button" class="rp-modal__close" data-close-edit-schedule-modal>&times;</button>
                </div>
                <div class="rp-modal__content rp-modal__content--stacked">
                    <input type="hidden" id="editScheduleAmenityId" value="">
                    
                    <div class="rp-schedule-edit-fields">
                        <div id="editScheduleAllowedRangeHint" class="rp-schedule-range-hint" style="display: none;">
                            <span>Reservation window:</span>
                            <strong id="editScheduleRangeText"></strong>
                        </div>

                        <div class="rp-schedule-field-group">
                            <label class="rp-schedule-label">Check-in Date &amp; Time Slot</label>
                            <div class="rp-schedule-input-row">
                                <input type="date" id="editScheduleStartDate" class="rp-schedule-date-input">
                                <select id="editScheduleStartSlot" class="rp-schedule-slot-select">
                                    <option value="Daytime">Daytime (Morning)</option>
                                    <option value="Nighttime">Nighttime (Evening)</option>
                                </select>
                            </div>
                        </div>

                        <div class="rp-schedule-field-group">
                            <label class="rp-schedule-label">Check-out Date &amp; Time Slot</label>
                            <div class="rp-schedule-input-row">
                                <input type="date" id="editScheduleEndDate" class="rp-schedule-date-input">
                                <select id="editScheduleEndSlot" class="rp-schedule-slot-select">
                                    <option value="Daytime">Daytime (Evening)</option>
                                    <option value="Nighttime">Nighttime (Overnight)</option>
                                </select>
                            </div>
                        </div>

                        <div id="editScheduleAirconWrap" class="rp-schedule-aircon-box" style="display: none;">
                            <label class="rp-schedule-aircon-label">
                                <input type="checkbox" id="editScheduleAirconToggle" class="rp-schedule-checkbox">
                                <span class="rp-schedule-aircon-text">
                                    <strong>Air-conditioning Package</strong>
                                    <small id="editScheduleAirconDiff">Include AC</small>
                                </span>
                            </label>
                        </div>

                        <div class="rp-schedule-summary-box">
                            <div class="rp-schedule-summary-row">
                                <span class="rp-schedule-summary-label">Stay Duration:</span>
                                <strong id="editScheduleDurationText">1 Day (1D 0N)</strong>
                            </div>
                            <div class="rp-schedule-summary-row">
                                <span class="rp-schedule-summary-label">Rate calculation:</span>
                                <span id="editScheduleMathText" class="rp-schedule-math">1 Daytime &times; &#8369;250</span>
                            </div>
                            <div class="rp-schedule-summary-total">
                                <span>Total for this amenity:</span>
                                <strong id="editScheduleTotalPrice">&#8369;250.00</strong>
                            </div>
                        </div>

                        <div class="rp-schedule-modal-actions">
                            <button type="button" class="rp-modal__btn rp-modal__btn--secondary" data-close-edit-schedule-modal>Cancel</button>
                            <button type="button" class="rp-modal__btn rp-modal__btn--primary" id="saveScheduleBtn">Save Schedule</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Multi aircon modal ── --}}
        <div class="rp-modal" id="multiAirconModal" aria-hidden="true">

            <div class="rp-modal__backdrop" data-close-multi-aircon-modal></div>

            <div class="rp-modal__panel rp-modal__panel--compact rp-modal__panel--scroll">

                <div class="rp-modal__header">

                    <div>

                        <p class="rp-modal__eyebrow">Multiple reservation</p>

                        <h2 id="multiAirconName">Amenity name</h2>

                    </div>

                    <button type="button" class="rp-modal__close" data-close-multi-aircon-modal>&times;</button>

                </div>

                <div class="rp-modal__content rp-modal__content--stacked">

                    <div class="rp-modal__summary">

                        <div class="rp-modal__meta">

                            <div class="rp-modal__meta-item"><span>Date</span><strong id="multiAirconDate"></strong></div>

                            <div class="rp-modal__meta-item"><span>Type</span><strong id="multiAirconSlot"></strong></div>

                            <div class="rp-modal__meta-item"><span>Capacity</span><strong id="multiAirconCapacity"></strong></div>

                        </div>

                        <div class="rp-modal__pricebox">

                            <span>Package price</span>

                            <strong id="multiAirconPriceValue">&#8369;0.00</strong>

                            <p id="multiAirconPriceHint">Choose whether this amenity will include aircon.</p>

                        </div>

                        <div id="multiAirconChoice" class="rp-modal__aircon"></div>

                        <p class="rp-modal__text" id="multiAirconDescription"></p>

                        <button type="button" id="multiAirconConfirmBtn" class="rp-booking-form__button">Confirm selection</button>

                    </div>

                </div>

            </div>

        </div>



        {{-- ── Success modal ── --}}

        <div class="rp-modal rp-modal--success" id="reservationSuccessModal" aria-hidden="true">

            <div class="rp-modal__backdrop"></div>

            <div class="rp-modal__panel rp-modal__panel--success">

                <div class="rp-modal__success-icon">

                    <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">

                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />

                    </svg>

                </div>

                <div class="rp-modal__success-content">

                    <h2>Reservation Confirmed &amp; Deposit Paid!</h2>

                    <div class="rp-modal__success-scroll-wrap" id="successModalScrollBody">

                        <div class="rp-modal__success-details">

                            <div class="rp-modal__success-notice">

                                <strong><i class="bi bi-envelope-check-fill me-1"></i> All Booking Details &amp; QR Pass Sent to Your Email!</strong>
                                We have emailed your <strong>official entry QR code</strong>, <strong>scheduled arrival time &amp; date</strong>, <strong>availed amenities</strong>, and <strong>complete billing breakdown</strong> to your email address. Everything you need for your visit has been sent — please check your inbox (or spam folder)!

                            </div>

                            <div class="rp-modal__success-notice" style="background: rgba(234, 179, 8, 0.15); border-color: rgba(234, 179, 8, 0.5); text-align: left;">

                                <strong style="color: #fde047; margin-bottom: 0.35rem;"><i class="bi bi-exclamation-triangle-fill me-1"></i> IMPORTANT CHECK-IN NOTICE:</strong>
                                Please <strong>bring your Entry QR Code</strong> (on your phone or printed). It is <strong>required upon arrival</strong> to automatically verify your identity and confirm that you are the rightful owner of this reservation for express check-in.

                            </div>

                            <p class="rp-modal__success-sub">Your deposit has been successfully processed. Any remaining balance can be settled at the park counter upon check-in.</p>

                        </div>

                    </div>

                    <div class="rp-modal__scroll-hint" id="successModalScrollHint" role="button" tabindex="0" title="Scroll down to unlock">

                        <span id="successModalScrollHintText">Scroll down to review all notices</span>
                        <svg class="rp-scroll-arrow-down" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">

                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />

                        </svg>

                    </div>

                    <div class="rp-modal__success-actions">

                        <button type="button" id="successConfirmBtn" class="rp-booking-form__button rp-booking-form__button--primary" disabled>
                            <span id="successConfirmBtnText">Scroll down to unlock (Got it!)</span>
                        </button>

                    </div>

                </div>

            </div>

        </div>



        {{-- ── Error modal ── --}}

        <div class="rp-modal rp-modal--error" id="reservationErrorModal" aria-hidden="true">

            <div class="rp-modal__backdrop" data-close-error-modal></div>

            <div class="rp-modal__panel rp-modal__panel--error">

                <div class="rp-modal__error-icon">

                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">

                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />

                    </svg>

                </div>

                <div class="rp-modal__error-content">

                    <h2>Booking Conflict</h2>

                    <div class="rp-modal__error-details">

                        <p class="rp-modal__error-message">This amenity is already booked for the selected time slot. Please choose a different time or amenity.</p>

                    </div>

                    <div class="rp-modal__error-actions">

                        <button type="button" id="errorConfirmBtn" class="rp-booking-form__button rp-booking-form__button--primary">Got it!</button>

                    </div>

                </div>

            </div>

        </div>

        {{-- ── Payment Failed / Expired modal ── --}}

        <div class="rp-modal rp-modal--error" id="paymentFailedModal" aria-hidden="true">

            <div class="rp-modal__backdrop" data-close-failed-modal></div>

            <div class="rp-modal__panel rp-modal__panel--error">

                <div class="rp-modal__error-icon">

                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">

                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />

                    </svg>

                </div>

                <div class="rp-modal__error-content">

                    <h2 id="paymentFailedTitle">Payment Session Expired</h2>

                    <div class="rp-modal__error-details">

                        <p class="rp-modal__error-message" id="paymentFailedMessage">The 10-minute payment window has passed. No reservation was created and no charge was made.</p>

                    </div>

                    <div class="rp-modal__error-actions">

                        <button type="button" id="paymentFailedRetryBtn" class="rp-booking-form__button rp-booking-form__button--primary">Try Booking Again</button>

                    </div>

                </div>

            </div>

        </div>

    </main>



    <footer class="rp-footer">

        <div class="rp-footer__inner">

            <div class="rp-footer__brand">

                <span class="rp-logo__name">Hinaguan Nature Park</span>

                <p>Jasaan, Misamis Oriental, Philippines</p>

            </div>

            <div class="rp-footer__links">

                <a href="{{ route('home') }}#about">About</a>

                <a href="{{ route('home') }}#amenities">Amenities</a>

                <a href="{{ route('home') }}#rates">Rates</a>

                <a href="{{ route('home') }}#directions">Directions</a>

                <a href="{{ route('reservation') }}">Reserve Now</a>

            </div>

            <p class="rp-footer__copy">&copy; {{ date('Y') }} <strong>Hinaguan Nature Park</strong>. All rights reserved.</p>

        </div>

    </footer>

    <x-guest_chatbot />

</body>

</html>
