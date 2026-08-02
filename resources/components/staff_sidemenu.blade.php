@props(['active' => 'dashboard'])

@php
    $links = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'url' => route('staff.dashboard'), 'icon' => 'grid'],
        ['key' => 'reservations', 'label' => 'Reservations', 'url' => route('staff.reservations'), 'icon' => 'calendar'],
        ['key' => 'occupancy-monitor', 'label' => 'Occupancy Monitor', 'url' => route('staff.occupancy-monitor'), 'icon' => 'monitor'],
        ['key' => 'reports', 'label' => 'Reports', 'url' => route('staff.reports'), 'icon' => 'chart'],
        ['key' => 'checkins', 'label' => 'Check-ins', 'url' => route('staff.checkins'), 'icon' => 'check'],
        ['key' => 'records', 'label' => 'Records', 'url' => route('staff.records'), 'icon' => 'archive'],
    ];
@endphp

<aside class="dash-sidebar" id="dashSidebar">
    <!-- Animated background -->
    <div class="dash-sidebar__bg">
        <div class="dash-sidebar__bg-image"></div>
        <div class="dash-sidebar__bg-overlay"></div>
    </div>

    <div class="dash-sidebar__content">
        <div class="dash-sidebar__brand">
            <div class="dash-sidebar__logo-wrapper">
                <img src="{{ asset('storage/design_images/main_logo.jpeg') }}" onerror="console.error('Logo failed to load'); this.style.display='none';" alt="Hinaguan Nature Park Logo" class="dash-sidebar__logo-img">
            </div>
            <div class="dash-sidebar__brand-text">
                <span class="dash-sidebar__brand-name">Hinaguan Nature Park</span>
                <span class="dash-sidebar__brand-tag">Staff Panel</span>
            </div>
        </div>

        <nav class="dash-sidebar__nav" aria-label="Staff navigation">
            <p class="dash-sidebar__label">Menu</p>
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
                                <span class="dash-sidebar__link-glow"></span>
                            </span>
                            <span class="dash-sidebar__link-text">{{ $link['label'] }}</span>
                            <span class="dash-sidebar__link-indicator"></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="dash-sidebar__footer">
            <div class="dash-sidebar__footer-content">
                <p>&copy; {{ date('Y') }} Hinaguan Nature Park</p>
                <p class="dash-sidebar__footer-version">v1.0.0</p>
            </div>
        </div>
    </div>
</aside>

<div class="dash-sidebar__overlay" aria-hidden="true"></div>
