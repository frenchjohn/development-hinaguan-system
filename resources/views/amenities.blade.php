<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Amenities — Hinaguan Nature Park</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/amenities.css', 'resources/css/chatbot.css', 'resources/js/amenities.js', 'resources/js/guest_chatbot.js'])
</head>
<body class="antialiased am-page" style="--am-page-bg: url('{{ asset('images/background.jpeg') }}')">

    <div class="am-site-header" id="amSiteHeader">
        <div class="am-topbar">
            <div class="am-topbar__inner">
                <p class="am-topbar__text"><strong>Now Open!</strong> Daytime: Adult &#8369;70 &middot; Child &#8369;50 &nbsp;|&nbsp; Overnight: Adult &#8369;100 &nbsp;|&nbsp; <a href="{{ route('reservation') }}">Reserve Now</a> &nbsp;&middot;&nbsp; Call: 0917 861 8383</p>
            </div>
        </div>
        <header class="am-header">
            <div class="am-header__inner">
                <a href="{{ route('home') }}" class="am-logo">
                    <span class="am-logo__icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-1.5 2.5-4 5-4 8a4 4 0 108 0c0-3-2.5-5.5-4-8z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8M10 18h4"/></svg></span>
                    <span class="am-logo__text"><span class="am-logo__name">Hinaguan Nature Park</span><span class="am-logo__location">Jasaan, Misamis Oriental</span></span>
                </a>
                <nav class="am-nav">
                    <ul class="am-nav__links">
                        <li><a href="{{ route('home') }}#about">About</a></li>
                        <li><a href="{{ route('home') }}#amenities">Amenities</a></li>
                        <li><a href="{{ route('home') }}#activities">Activities</a></li>
                        <li><a href="{{ route('home') }}#rates">Rates</a></li>
                        <li><a href="{{ route('home') }}#gallery">Gallery</a></li>
                        <li><a href="{{ route('home') }}#directions">Directions</a></li>
                    </ul>
                    <a href="{{ route('reservation') }}" class="am-btn am-btn--book">Book Now</a>
                </nav>
            </div>
        </header>
    </div>

    <main class="am-main">
        <div class="am-container">
            <div class="am-hero" data-animate="fade-up">
                <span class="am-label">Amenities</span>
                <h1 class="am-title">Explore Our Park Amenities</h1>
                <p class="am-desc">Browse cottages, pavilions, and facilities available at Hinaguan Nature Park.</p>
            </div>

            <div class="am-grid">
                @foreach($amenities as $index => $amenity)
                    <article class="am-card" data-animate="fade-up" data-delay="{{ min($index * 60, 360) }}">
                        <button type="button" class="am-card__button" data-open-info-modal
                            data-name="{{ $amenity->amenities_name }}"
                            data-description="{{ $amenity->description ?? 'No description available.' }}"
                            data-capacity="{{ $amenity->minimum_capacity }}–{{ $amenity->maximum_capacity }} guests"
                            data-day-price="{{ $amenity->daytime_price ? '₱' . number_format($amenity->daytime_price, 2) : 'N/A' }}"
                            data-night-price="{{ $amenity->nighttime_price ? '₱' . number_format($amenity->nighttime_price, 2) : 'N/A' }}"
                            data-image="{{ $amenity->image ? asset('storage/' . $amenity->image) : '' }}">
                            @if($amenity->image)
                                <div class="am-card__image" style="background-image:url('{{ asset('storage/' . $amenity->image) }}')"></div>
                            @else
                                <div class="am-card__image am-card__image--empty"></div>
                            @endif
                            <div class="am-card__overlay"><span>{{ $amenity->amenities_name }}</span></div>
                        </button>
                    </article>
                @endforeach
            </div>
        </div>
    </main>

    <div class="am-modal" id="infoModal" aria-hidden="true">
        <div class="am-modal__backdrop" data-close-info-modal></div>
        <div class="am-modal__panel">
            <div class="am-modal__header">
                <div>
                    <p class="am-modal__eyebrow">Amenity details</p>
                    <h3 id="infoModalTitle">Amenity</h3>
                </div>
                <button type="button" class="am-modal__close" data-close-info-modal>&times;</button>
            </div>
            <div class="am-modal__body">
                <div class="am-modal__image" id="infoModalImage" hidden></div>
                <div class="am-modal__meta">
                    <div><span>Capacity</span><strong id="infoModalCapacity"></strong></div>
                    <div><span>Daytime</span><strong id="infoModalDayPrice"></strong></div>
                    <div><span>Nighttime</span><strong id="infoModalNightPrice"></strong></div>
                </div>
                <p class="am-modal__text" id="infoModalDescription"></p>
                <a href="{{ route('reservation') }}" class="am-btn am-btn--book am-btn--full">Book this amenity</a>
            </div>
        </div>
    </div>

    <footer class="am-footer"><p>&copy; {{ date('Y') }} <strong>Hinaguan Nature Park</strong>. All rights reserved.</p></footer>

    <x-guest_chatbot />
</body>
</html>
