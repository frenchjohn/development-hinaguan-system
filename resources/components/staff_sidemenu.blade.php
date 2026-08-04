@props(['active' => 'dashboard', 'userName' => 'Staff', 'userRole' => 'Staff'])

@php
    $links = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'url' => route('staff.dashboard'), 'icon' => 'grid'],
        ['key' => 'reservations', 'label' => 'Reservations', 'url' => route('staff.reservations'), 'icon' => 'calendar'],
        ['key' => 'occupancy-monitor', 'label' => 'Occupancy', 'url' => route('staff.occupancy-monitor'), 'icon' => 'monitor'],
        ['key' => 'reports', 'label' => 'Reports', 'url' => route('staff.reports'), 'icon' => 'chart'],
        ['key' => 'checkins', 'label' => 'Check-ins', 'url' => route('staff.checkins'), 'icon' => 'check'],
        ['key' => 'records', 'label' => 'Records', 'url' => route('staff.records'), 'icon' => 'archive'],
    ];
@endphp

<aside class="dash-sidebar" id="dashSidebar">
    <!-- Logo Section -->
    <div class="dash-sidebar__brand">
        <div class="dash-sidebar__logo-wrapper">
            <img src="{{ asset('storage/design_images/main_logo.jpeg') }}" onerror="console.error('Logo failed to load'); this.style.display='none';" alt="Hinaguan Nature Park Logo" class="dash-sidebar__logo-img">
        </div>
    </div>

    <!-- Separator Line -->
    <div class="dash-sidebar__separator"></div>

    <!-- Navigation -->
    <nav class="dash-sidebar__nav" aria-label="Staff navigation">
        <ul class="dash-sidebar__list">
            @foreach ($links as $link)
                <li>
                    <a
                        href="{{ $link['url'] }}"
                        class="dash-sidebar__link {{ $active === $link['key'] ? 'is-active' : '' }}"
                        data-page-transition
                    >
                        <span class="dash-sidebar__link-icon-wrapper">
                            @include('components.partials.sidemenu-icon', ['icon' => $link['icon']])
                        </span>
                        <span class="dash-sidebar__link-text">{{ $link['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <!-- Separator Line -->
    <div class="dash-sidebar__separator"></div>

    <!-- Profile Section -->
    <div class="dash-sidebar__profile">
        <button type="button" class="dash-sidebar__profile-btn" data-dash-user-toggle aria-label="User menu">
            <span class="dash-sidebar__profile-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </span>
            <span class="dash-sidebar__profile-info">
                <span class="dash-sidebar__profile-name">{{ $userName }}</span>
                <span class="dash-sidebar__profile-role">{{ $userRole }}</span>
            </span>
        </button>
        <div class="dash-sidebar__profile-dropdown">
            <button type="button" class="dash-sidebar__profile-item dash-sidebar__theme-toggle-btn" data-theme-toggle aria-label="Toggle dark mode">
                <svg class="dash-sidebar__theme-icon dash-sidebar__theme-icon--light" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <svg class="dash-sidebar__theme-icon dash-sidebar__theme-icon--dark" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <span class="dash-sidebar__theme-text">Light Mode</span>
            </button>
            <a href="{{ route('staff.settings') }}" class="dash-sidebar__profile-item">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" class="dash-sidebar__dropdown-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Settings</span>
            </a>
            <a href="{{ route('home') }}" class="dash-sidebar__profile-item">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" class="dash-sidebar__dropdown-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Back to Website</span>
            </a>
            <button type="button" class="dash-sidebar__profile-logout" data-logout-confirm>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" class="dash-sidebar__dropdown-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Sign out</span>
            </button>
        </div>
    </div>
</aside>

<div class="dash-sidebar__overlay" aria-hidden="true"></div>

<!-- Logout Confirmation Modal -->
<div class="logout-modal" id="logoutModal" aria-hidden="true">
    <div class="logout-modal__backdrop"></div>
    <div class="logout-modal__content">
        <div class="logout-modal__header">
            <h3 class="logout-modal__title">Confirm Logout</h3>
        </div>
        <div class="logout-modal__body">
            <p class="logout-modal__message">Are you sure you want to sign out of your account?</p>
        </div>
        <div class="logout-modal__footer">
            <button type="button" class="logout-modal__btn logout-modal__btn--cancel" data-logout-cancel>Cancel</button>
            <form method="POST" action="{{ route('logout') }}" class="logout-modal__form">
                @csrf
                <button type="submit" class="logout-modal__btn logout-modal__btn--confirm"><span>Sign out</span></button>
            </form>
        </div>
    </div>
</div>
