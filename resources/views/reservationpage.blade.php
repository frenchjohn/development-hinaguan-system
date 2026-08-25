<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Reserve cottages, huts, and amenities at Hinaguan Nature Park in Jasaan, Misamis Oriental. Pick a date, choose your amenity, and confirm your booking in minutes.">

    <title>Book a Visit — Hinaguan Nature Park</title>

    <link rel="icon" type="image/jpeg" href="{{ asset('storage/design_images/main_logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700" rel="stylesheet">

    @php
        $settings = $parkSettings ?? \App\Models\ParkSetting::first();
        $daytimeStart = $settings ? strtotime((string) ($settings->daytime_start ?? '06:00')) : strtotime('06:00');
        $daytimeEnd = $settings ? strtotime((string) ($settings->daytime_end ?? '18:00')) : strtotime('18:00');
        $nowSeconds = strtotime(now()->format('H:i'));
        $isNighttimeNow = !($nowSeconds >= $daytimeStart && $nowSeconds < $daytimeEnd);
        $todayDate = now()->toDateString();
    @endphp
    <script>
        window.PARK_TODAY_DATE = @json($todayDate);
        window.PARK_IS_NIGHTTIME_NOW = @json($isNighttimeNow);
        window.PARK_DAYTIME_START = @json($settings->daytime_start ?? '06:00');
        window.PARK_DAYTIME_END = @json($settings->daytime_end ?? '18:00');
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

                <div data-animate="fade-up">

                    <a href="{{ route('home') }}" class="rp-back-button">

                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">

                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>

                        </svg>

                        Back to Homepage

                    </a>

                    <span class="rp-label">Reservations</span>

                    <h1 class="rp-title">Book Your Escape to <em>Hinaguan</em></h1>

                    <p class="rp-desc">Pick a date, choose your amenity, and we will take care of the rest. Daytime, overnight, or both — your riverside getaway starts here.</p>

                </div>



                <div class="rp-hero__stats" data-animate="fade-up" data-delay="150">

                    <div class="rp-hero__stat">

                        <strong>{{ $amenities->count() }}+</strong>

                        <span>Amenities to choose from</span>

                    </div>

                    <div class="rp-hero__stat">

                        <strong>Daily</strong>

                        <span>Open every single day</span>

                    </div>

                    <div class="rp-hero__stat">

                        <strong>&#8369;{{ $parkSettings->daytime_adult_entrance_fee ?? 70 }}</strong>

                        <span>Day entry from</span>

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



        {{-- ── How booking works (two paths) ── --}}

        <section class="rp-steps" id="bookingSteps" data-animate="fade-up" data-delay="100" aria-label="How booking works">

            <div class="rp-steps__head">

                <span class="rp-label">Two ways to book</span>

                <h2 class="rp-steps__title">Start wherever feels right</h2>

                <p class="rp-steps__desc">Planning around a date, or already in love with a specific spot? Both routes lead to the same perfect day out.</p>

            </div>

            <div class="rp-steps__paths">

                <article class="rp-step rp-step--date">

                    <span class="rp-step__badge">Path 1 &middot; Date first</span>

                    <div class="rp-step__icon">

                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">

                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>

                        </svg>

                    </div>

                    <h3>Know when you're going?</h3>

                    <p>Pick your date first, then browse exactly which amenities are free that day.</p>

                    <ol class="rp-step__list">

                        <li><span>1</span> Choose your visit date</li>

                        <li><span>2</span> Pick from what's available that day</li>

                    </ol>

                </article>

                <article class="rp-step rp-step--amenity">

                    <span class="rp-step__badge">Path 2 &middot; Amenity first</span>

                    <div class="rp-step__icon">

                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">

                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>

                        </svg>

                    </div>

                    <h3>Got a favorite spot?</h3>

                    <p>Tap any amenity to open its availability calendar, then book it on a date that suits you.</p>

                    <ol class="rp-step__list">

                        <li><span>1</span> Pick your favorite amenity</li>

                        <li><span>2</span> Choose an open date from its calendar</li>

                    </ol>

                </article>

            </div>

            <article class="rp-step rp-step--finish">

                <div class="rp-step__icon">

                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">

                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>

                    </svg>

                </div>

                <div class="rp-step__finish-copy">

                    <h3>Confirm &amp; get your QR</h3>

                    <p>Both paths end the same way — enter your details, pay a small deposit, and your QR code arrives by email for check-in.</p>

                </div>

                <span class="rp-step__finish-num" aria-hidden="true">&#10003;</span>

            </article>

        </section>



        {{-- ── Date selection CTA ── --}}

        <section class="rp-date-cta" data-animate="fade-up" data-delay="100" id="dateCtaSection">

            <div class="rp-date-cta__content">

                <span class="rp-date-cta__icon">

                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">

                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />

                    </svg>

                </span>

                <h2>Ready to reserve?</h2>

                <p>Start by picking the date you want to visit — it only takes a few seconds.</p>

                <button type="button" id="pickDateBtn" class="rp-date-cta__button">

                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">

                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />

                    </svg>

                    Pick a Date

                </button>

                <p class="rp-date-cta__hint">Daytime &amp; overnight bookings are limited each day — early booking is recommended.</p>

            </div>

        </section>



        {{-- ── Date controls (revealed after a date is chosen) ── --}}

        <section class="rp-filterbar" data-animate="fade-up" data-delay="100" id="dateControlsSection" hidden>

            <div class="rp-filterbar__controls">

                <div class="rp-date-card">

                    <span class="rp-date-card__label">Reservation date</span>

                    <div class="rp-date-card__picker">

                        <input id="reservation_date" name="reservation_date" type="hidden" value="" data-min-date="{{ now()->toDateString() }}">

                        <button type="button" id="reservationDateTrigger" class="rp-date-card__day">Select date</button>

                        <span class="rp-date-card__weekday" id="reservationDay"></span>

                    </div>

                    <div class="rp-date-card__weather" id="reservationWeatherPreview" hidden>

                        <div class="rp-weather-preview__wrap">

                            <img src="" alt="" class="rp-weather-preview__icon" id="weatherIcon" hidden>

                            <div class="rp-weather-preview__content">

                                <strong id="weatherCondition"></strong>

                                <span id="weatherTemp"></span>

                            </div>

                        </div>

                        <p class="rp-weather-preview__empty" id="weatherEmpty"></p>

                        <div class="rp-weather-preview__skeleton" id="weatherSkeleton">

                            <div class="rp-weather-preview__skeleton-icon"></div>

                            <div class="rp-weather-preview__skeleton-content">

                                <div class="rp-weather-preview__skeleton-text"></div>

                                <div class="rp-weather-preview__skeleton-text rp-weather-preview__skeleton-text--small"></div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>



        {{-- ── Booking type ── --}}

        <section class="rp-slotbar" aria-label="Booking type" data-animate="fade-up" data-delay="150" id="slotControlsSection" hidden>

            <span class="rp-slotbar__label">Booking type</span>

            <div class="rp-slotbar__buttons">

                <button type="button" class="rp-slot-btn is-active" data-slot="Daytime" id="slotDaytime">Daytime</button>

                <button type="button" class="rp-slot-btn" data-slot="Nighttime" id="slotNighttime">Nighttime</button>

                <button type="button" class="rp-slot-btn" data-slot="DayToNight" id="slotDayToNight">Whole Day (24 hrs)</button>

            </div>

            <p class="rp-slotbar__hint">Daytime: 6:00 AM – 6:00 PM &middot; Nighttime: 6:00 PM – 6:00 AM &middot; Whole Day: 6:00 AM – 6:00 AM next day (Day & Night combined)</p>

        </section>



        {{-- ── Filters & multi-select ── --}}

        <section class="rp-subfilters" data-animate="fade-up" data-delay="200">

            <div class="rp-subfilters__control">

                <label for="filterType">Filter by</label>

                <div class="rp-select-wrap">

                    <select id="filterType">

                        <option value="all">All amenities</option>

                        <option value="capacity">Capacity range</option>

                        <option value="price">Price range</option>

                    </select>

                </div>

            </div>

            <div class="rp-subfilters__control">

                <label for="filterMin">Minimum</label>

                <input id="filterMin" type="number" min="0" placeholder="Min" disabled>

            </div>

            <div class="rp-subfilters__control">

                <label for="filterMax">Maximum</label>

                <input id="filterMax" type="number" min="0" placeholder="Max" disabled>

            </div>

            <label class="rp-multi-toggle" for="multiSelectionToggle">

                <input id="multiSelectionToggle" type="checkbox">

                <span class="rp-multi-toggle__track" aria-hidden="true"><span class="rp-multi-toggle__thumb"></span></span>

                <span class="rp-multi-toggle__label">Multiple selection</span>

            </label>

        </section>



        {{-- ── Amenity grid ── --}}

        <section class="rp-grid" id="reservationGridShell" data-animate="fade-up" data-delay="250">

            {{-- Section heading --}}

            <div class="rp-grid__head">

                <div>

                    <span class="rp-label">Available amenities</span>

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

                <div class="rp-grid__list" id="amenityGrid">

                    @foreach($amenities as $index => $amenity)

                        @php

                            $minPrice = collect([$amenity->daytime_price, $amenity->nighttime_price])->filter()->min();

                            $maxPrice = collect([$amenity->daytime_price, $amenity->nighttime_price])->filter()->max();

                            $hasSale = $amenity->sale_percentage && $amenity->sale_percentage > 0;

                        @endphp

                        <article class="rp-card"

                            data-animate="fade-up"

                            data-delay="{{ min($index * 60, 360) }}"

                            data-amenity-id="{{ $amenity->id }}"

                            data-name="{{ $amenity->amenities_name }}"

                            data-min-capacity="{{ $amenity->minimum_capacity }}"

                            data-max-capacity="{{ $amenity->maximum_capacity }}"

                            data-min-price="{{ $minPrice }}"

                            data-max-price="{{ $maxPrice }}"

                            data-daytime-price="{{ $amenity->daytime_price }}"

                            data-nighttime-price="{{ $amenity->nighttime_price }}"

                            data-daytime-aircon-price="{{ $amenity->daytime_aircon_price ?? '' }}"

                            data-nighttime-aircon-price="{{ $amenity->nighttime_aircon_price ?? '' }}"

                            data-has-aircon="{{ (!empty($amenity->daytime_aircon_price) || !empty($amenity->nighttime_aircon_price)) ? '1' : '0' }}"

                            data-additional="{{ $amenity->additional_per_head ?? '0' }}"

                            data-description="{{ $amenity->description ?? '' }}"

                            data-sale-percentage="{{ $amenity->sale_percentage ?? 0 }}"

                            data-original-daytime-price="{{ $amenity->original_daytime_price ?? $amenity->daytime_price }}"

                            data-original-nighttime-price="{{ $amenity->original_nighttime_price ?? $amenity->nighttime_price }}"

                            data-original-daytime-aircon-price="{{ $amenity->original_daytime_aircon_price ?? $amenity->daytime_aircon_price }}"

                            data-original-nighttime-aircon-price="{{ $amenity->original_nighttime_aircon_price ?? $amenity->nighttime_aircon_price }}">

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

                                </div>

                                @if($hasSale)

                                    <div class="rp-card__sale-badge">{{ $amenity->sale_percentage }}% OFF</div>

                                @endif

                                <div class="rp-card__overlay">

                                    <span>{{ $amenity->amenities_name }}</span>

                                </div>

                                <span class="rp-card__cta">View &amp; Book</span>

                            </button>

                        </article>

                    @endforeach

                </div>

                <div class="rp-empty" id="emptyState" style="display:none;">

                    <p>No amenities are available for the selected date and booking type.</p>

                </div>

            @endif

        </section>



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

                            <div id="airconChoice" class="rp-modal__aircon"></div>

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

            <div class="rp-modal__panel rp-modal__panel--calendar">

                <div class="rp-modal__header">

                    <div>

                        <p class="rp-modal__eyebrow">When do you want to visit?</p>

                        <h2>Select reservation date</h2>

                    </div>

                    <button type="button" class="rp-modal__close" data-close-date-picker>&times;</button>

                </div>

                <div class="rp-modal__content rp-modal__content--stacked">

                    {{-- Stay Mode Switch: Single Day vs Multi-Day --}}
                    <div class="rp-mode-switch-wrap">
                        <span class="rp-dp-slot__label">Booking Type</span>
                        <div class="rp-mode-switch" role="group" aria-label="Stay Type">
                            <button type="button" class="rp-mode-btn is-active" id="dpModeSingle" data-mode="single">Single Day</button>
                            <button type="button" class="rp-mode-btn" id="dpModeRange" data-mode="range">Multi-Day Stay (Date Range)</button>
                        </div>
                    </div>

                    <div class="rp-dp-toolbar">

                        <div class="rp-dp-toolbar__field">

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

                        <div class="rp-dp-toolbar__field">

                            <label class="rp-dp-toolbar__label" for="datePickerYear">Year</label>

                            <div class="rp-select-wrap">

                                <select id="datePickerYear" class="rp-calendar-controls__select"></select>

                            </div>

                        </div>

                    </div>

                    {{-- Single Day Slots View --}}
                    <div class="rp-dp-slot" id="dpSingleSlotWrap" role="group" aria-label="Single Day slot">

                        <span class="rp-dp-slot__label">Time Slot</span>

                        <div class="rp-dp-slot__buttons">

                            <button type="button" class="rp-slot-btn is-active" data-slot="Daytime" id="modalSlotDaytime">

                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>

                                Daytime

                            </button>

                            <button type="button" class="rp-slot-btn" data-slot="Nighttime" id="modalSlotNighttime">

                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>

                                Nighttime

                            </button>

                            <button type="button" class="rp-slot-btn" data-slot="DayToNight" id="modalSlotDayToNight">

                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8a4 4 0 100 8 4 4 0 000-8z"/><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>

                                Whole Day (24 hrs)

                            </button>

                        </div>

                    </div>

                    {{-- Multi-Day Range Slots View --}}
                    <div class="rp-range-slots-box" id="dpRangeSlotWrap" style="display: none;">
                        <div class="rp-range-slot-group">
                            <span class="rp-range-slot-label">Start Time Slot (Day 1)</span>
                            <div class="rp-range-slot-toggle">
                                <button type="button" class="rp-subslot-btn is-active" data-range-start-slot="Daytime">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    Daytime (Morning)
                                </button>
                                <button type="button" class="rp-subslot-btn" data-range-start-slot="Nighttime">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                                    Nighttime (Evening)
                                </button>
                            </div>
                        </div>
                        <div class="rp-range-slot-group">
                            <span class="rp-range-slot-label">End Time Slot (Last Day)</span>
                            <div class="rp-range-slot-toggle">
                                <button type="button" class="rp-subslot-btn is-active" data-range-end-slot="Daytime">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    Daytime (Evening)
                                </button>
                                <button type="button" class="rp-subslot-btn" data-range-end-slot="Nighttime">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                                    Nighttime (Overnight)
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Range Selection Summary Bar --}}
                    <div class="rp-range-summary-bar" id="dpRangeSummaryBar" style="display: none;">
                        <div class="rp-range-card">
                            <span class="rp-range-card__label">Check-in</span>
                            <strong id="dpRangeStartLabel">Select start date</strong>
                        </div>
                        <div class="rp-range-arrow">&rarr;</div>
                        <div class="rp-range-card">
                            <span class="rp-range-card__label">Check-out</span>
                            <strong id="dpRangeEndLabel">Select end date</strong>
                        </div>
                        <div class="rp-range-badge" id="dpRangeDaysBadge">1 Day</div>
                    </div>

                    <div class="rp-calendar" id="datePickerDays"></div>

                    <div class="rp-dp-actions" id="dpRangeActions" style="display: none;">
                        <button type="button" class="rp-booking-form__button" id="dpApplyRangeBtn">Apply Selected Dates &rarr;</button>
                    </div>

                    <p class="rp-dp-hint"><span class="rp-dp-hint__dot" aria-hidden="true"></span> Highlighted dates are open &mdash; dimmed dates are past or fully booked.</p>

                </div>

            </div>

        </div>



        {{-- ── Availability calendar modal ── --}}

        <div class="rp-modal" id="availabilityModal" aria-hidden="true">

            <div class="rp-modal__backdrop" data-close-availability-modal></div>

            <div class="rp-modal__panel rp-modal__panel--calendar rp-modal__panel--scroll">

                <div class="rp-modal__header">

                    <div>

                        <p class="rp-modal__eyebrow">Availability calendar</p>

                        <h2 id="availabilityModalTitle">Amenity name</h2>

                    </div>

                    <button type="button" class="rp-modal__close" data-close-availability-modal>&times;</button>

                </div>

                <div class="rp-modal__content rp-modal__content--stacked">

                    <div class="rp-mode-switch-wrap">
                        <div class="rp-mode-switch" role="group" aria-label="Availability stay mode">
                            <button type="button" class="rp-mode-btn is-active" id="avModeSingle" data-av-mode="single">Single Day</button>
                            <button type="button" class="rp-mode-btn" id="avModeRange" data-av-mode="range">Multi-Day Stay</button>
                        </div>
                    </div>

                    {{-- Single Day Slots --}}
                    <div class="rp-modal__slot-toggle" id="avSingleSlotToggle" role="tablist" aria-label="Booking slot">

                        <button type="button" class="rp-slot-btn is-active" data-slot-toggle="Daytime">Daytime</button>

                        <button type="button" class="rp-slot-btn" data-slot-toggle="Nighttime">Nighttime</button>

                        <button type="button" class="rp-slot-btn" data-slot-toggle="DayToNight">Whole Day (24 hrs)</button>

                    </div>

                    {{-- Multi-Day Range Slots --}}
                    <div class="rp-range-slots-box" id="avRangeSlotWrap" style="display: none;">
                        <div class="rp-range-slot-group">
                            <span class="rp-range-slot-label">Start Time Slot (Day 1)</span>
                            <div class="rp-range-slot-toggle">
                                <button type="button" class="rp-subslot-btn is-active" data-av-start-slot="Daytime">Daytime (Morning)</button>
                                <button type="button" class="rp-subslot-btn" data-av-start-slot="Nighttime">Nighttime (Evening)</button>
                            </div>
                        </div>
                        <div class="rp-range-slot-group">
                            <span class="rp-range-slot-label">End Time Slot (Last Day)</span>
                            <div class="rp-range-slot-toggle">
                                <button type="button" class="rp-subslot-btn is-active" data-av-end-slot="Daytime">Daytime (Evening)</button>
                                <button type="button" class="rp-subslot-btn" data-av-end-slot="Nighttime">Nighttime (Overnight)</button>
                            </div>
                        </div>
                    </div>

                    {{-- Availability Range Summary Bar --}}
                    <div class="rp-range-summary-bar" id="avRangeSummaryBar" style="display: none;">
                        <div class="rp-range-card">
                            <span class="rp-range-card__label">Check-in</span>
                            <strong id="avRangeStartLabel">Pick start date</strong>
                        </div>
                        <div class="rp-range-arrow">&rarr;</div>
                        <div class="rp-range-card">
                            <span class="rp-range-card__label">Check-out</span>
                            <strong id="avRangeEndLabel">Pick end date</strong>
                        </div>
                        <div class="rp-range-badge" id="avRangeDaysBadge">1 Day</div>
                    </div>

                    <div class="rp-calendar__controls">

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

                        <select id="calendarYear" class="rp-calendar__select"></select>

                    </div>

                    <div class="rp-calendar-wrap">

                        <div class="rp-calendar" id="availabilityCalendar" role="grid" aria-label="Available dates"></div>

                    </div>

                    <div class="rp-dp-actions" id="avRangeActions" style="display: none;">
                        <button type="button" class="rp-booking-form__button" id="avApplyRangeBtn">Book Selected Date Range &rarr;</button>
                    </div>

                    <p class="rp-modal__hint">Available dates are highlighted. Unavailable dates are dimmed.</p>

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

                                <strong>📩 All Booking Details &amp; QR Pass Sent to Your Email!</strong>
                                We have emailed your <strong>official entry QR code</strong>, <strong>scheduled arrival time &amp; date</strong>, <strong>availed amenities</strong>, and <strong>complete billing breakdown</strong> to your email address. Everything you need for your visit has been sent — please check your inbox (or spam folder)!

                            </div>

                            <div class="rp-modal__success-notice" style="background: rgba(234, 179, 8, 0.15); border-color: rgba(234, 179, 8, 0.5); text-align: left;">

                                <strong style="color: #fde047; margin-bottom: 0.35rem;">⚠️ IMPORTANT CHECK-IN NOTICE:</strong>
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
