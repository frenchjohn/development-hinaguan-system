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

                <button type="button" class="rp-slot-btn" data-slot="DayToNight" id="slotDayToNight">Day to Night</button>

                <button type="button" class="rp-slot-btn" data-slot="NightToDay" id="slotNightToDay">Night to Day</button>

            </div>

            <p class="rp-slotbar__hint">Daytime: 6:00 AM – 6:00 PM &middot; Nighttime: 6:00 PM – 6:00 AM &middot; Day to Night covers both &middot; Night to Day runs tonight into tomorrow daytime</p>

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

                            <div class="rp-modal__meta">

                                <div class="rp-modal__meta-item"><span>Date</span><strong id="modalDate"></strong></div>

                                <div class="rp-modal__meta-item"><span>Type</span><strong id="modalSlot"></strong></div>

                                <div class="rp-modal__meta-item"><span>Capacity</span><strong id="modalCapacity"></strong></div>

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

                            <button type="submit" class="rp-booking-form__button">Reserve prototype</button>

                            <p class="rp-booking-form__message" id="bookingNotice"></p>

                        </form>

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

                    <div class="rp-dp-slot" role="group" aria-label="Booking type">

                        <span class="rp-dp-slot__label">Booking type</span>

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

                                Day to Night

                            </button>

                            <button type="button" class="rp-slot-btn" data-slot="NightToDay" id="modalSlotNightToDay">

                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>

                                Night to Day

                            </button>

                        </div>

                    </div>

                    <div class="rp-calendar" id="datePickerDays"></div>

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

                    <p class="rp-modal__hint rp-modal__hint--top">Select a date to continue booking this amenity.</p>

                    <div class="rp-modal__slot-toggle" role="tablist" aria-label="Booking slot">

                        <button type="button" class="rp-slot-btn is-active" data-slot-toggle="Daytime">Daytime</button>

                        <button type="button" class="rp-slot-btn" data-slot-toggle="Nighttime">Nighttime</button>

                        <button type="button" class="rp-slot-btn" data-slot-toggle="DayToNight">Day to Night</button>

                        <button type="button" class="rp-slot-btn" data-slot-toggle="NightToDay">Night to Day</button>

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

                    <p class="rp-modal__hint">Available dates are highlighted. Unavailable dates are dimmed.</p>

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

            <div class="rp-modal__backdrop" data-close-success-modal></div>

            <div class="rp-modal__panel rp-modal__panel--success">

                <div class="rp-modal__success-icon">

                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">

                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />

                    </svg>

                </div>

                <div class="rp-modal__success-content">

                    <h2>Reservation Confirmed!</h2>

                    <div class="rp-modal__success-details">

                        <p class="rp-modal__success-notice">

                            <strong>Important:</strong> A QR code has been sent to your email address. Please bring this QR code on your reservation day and scan it at the check-in counter.

                        </p>

                        <p class="rp-modal__success-sub">Your booking is confirmed and partially paid. The remaining balance can be settled upon check-in.</p>

                    </div>

                    <div class="rp-modal__success-actions">

                        <button type="button" id="successConfirmBtn" class="rp-booking-form__button rp-booking-form__button--primary">Got it!</button>

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
