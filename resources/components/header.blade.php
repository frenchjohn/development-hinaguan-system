@props([
    'title' => 'Dashboard',
    'subtitle' => null,
    'showWelcome' => false,
])

<header class="dash-header" style="background: url('{{ asset('storage/design_images/background_image2.png') }}') center/cover no-repeat;">
    <div class="dash-header__overlay"></div>
    <div class="dash-header__content">
        <div class="dash-header__left">
            <button type="button" class="dash-header__toggle" data-dash-sidebar-toggle aria-label="Toggle menu">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>
            <div class="dash-header__titles">
                <h1 class="dash-header__title">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="dash-header__subtitle">{{ $subtitle }}</p>
                @endif
            </div>
            @if ($showWelcome)
                <div class="dash-header__divider"></div>
                <div class="dash-header__welcome">
                    <span class="dash-header__welcome-icon">🍃</span>
                    <span>Good morning, Staff!</span>
                </div>
            @endif
        </div>

        <div class="dash-header__right">
            <a href="{{ route('home') }}" class="dash-header__home">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Public Site</span>
            </a>
        </div>
    </div>
</header>
