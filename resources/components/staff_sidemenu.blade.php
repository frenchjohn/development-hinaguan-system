@props(['active' => 'dashboard'])

@php
    $links = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'url' => route('staff.dashboard'), 'icon' => 'grid'],
        ['key' => 'reservations', 'label' => 'Reservations', 'url' => route('staff.reservations'), 'icon' => 'calendar'],
        ['key' => 'occupancy-monitor', 'label' => 'Occupancy Monitor', 'url' => route('staff.occupancy-monitor'), 'icon' => 'monitor'],
        ['key' => 'reports', 'label' => 'Reports', 'url' => route('staff.reports'), 'icon' => 'chart'],
        ['key' => 'checkins', 'label' => 'Check-ins', 'url' => route('staff.checkins'), 'icon' => 'check'],
        ['key' => 'records', 'label' => 'Records', 'url' => route('staff.records'), 'icon' => 'archive'],
        ['key' => 'settings', 'label' => 'Settings', 'url' => route('staff.settings'), 'icon' => 'cog'],
    ];
@endphp

<aside class="dash-sidebar" id="dashSidebar">
    <div class="dash-sidebar__brand">
        <div class="dash-sidebar__logo">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-1.5 2.5-4 5-4 8a4 4 0 108 0c0-3-2.5-5.5-4-8z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8M10 18h4"/>
            </svg>
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
                        @include('components.partials.sidemenu-icon', ['icon' => $link['icon']])
                        {{ $link['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="dash-sidebar__footer">
        &copy; {{ date('Y') }} Hinaguan Nature Park
    </div>
</aside>

<div class="dash-sidebar__overlay" aria-hidden="true"></div>
