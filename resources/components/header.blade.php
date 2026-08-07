@props([
    'title' => 'Dashboard',
    'subtitle' => null,
])

@php
    $pendingCount = \App\Models\Reservation::where('status', 'Pending')->count();
    $checkedInCount = \App\Models\ReservationGuest::whereNull('checked_out_at')
        ->whereHas('reservation', function($q) {
            $q->where('status', 'Checked In');
        })
        ->count();
    $totalNotifs = ($pendingCount > 0 ? 1 : 0) + ($checkedInCount > 0 ? 1 : 0);

    $weatherService = app(\App\Services\WeatherService::class);
    // Single cached forecast call (forecast.json embeds current conditions).
    $weatherForecast = $weatherService->getMultiDayForecast(3);
    $weatherNow = $weatherForecast['now'] ?? null;
    $weatherUpdated = null;
    if (! empty($weatherForecast['updated_at'])) {
        try {
            $weatherUpdated = \Carbon\Carbon::parse($weatherForecast['updated_at'])->format('g:i A');
        } catch (\Throwable $e) {
            $weatherUpdated = null;
        }
    }
@endphp

<header class="dash-header">
    <div class="dash-header__left">
        <button type="button" class="dash-header__toggle" data-dash-sidebar-toggle aria-label="Toggle menu">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <div class="dash-header__titles">
            <h1 class="dash-header__title">{{ $title }}</h1>
            @if ($subtitle)
                <p class="dash-header__subtitle">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    <div class="dash-header__right">
        <!-- Live Park Status Pill -->
        <div class="dash-header__status-badge">
            <span class="dash-header__status-dot"></span>
            <span class="dash-header__status-text">Park Open</span>
        </div>

        <!-- Weather Forecast Widget -->
        @if ($weatherNow || $weatherForecast)
        <div class="dash-header__weather-wrap">
            <button type="button" class="dash-header__weather-btn" id="weatherBtn" aria-label="Weather forecast" aria-haspopup="true" aria-expanded="false" aria-controls="weatherDropdown">
                @if (!empty($weatherNow['icon']))
                    <img src="{{ $weatherNow['icon'] }}" alt="" class="dash-header__weather-icon">
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="dash-header__weather-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                @endif
                <span class="dash-header__weather-temp">{{ round($weatherNow['temp_c'] ?? 0) }}°</span>
                <span class="dash-header__weather-cond">{{ $weatherNow['condition'] ?? '—' }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="dash-header__weather-chevron" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div class="dash-header__weather-dropdown" id="weatherDropdown" aria-hidden="true">
                <div class="dash-header__weather-head">
                    <div>
                        <h3>Weather · {{ $weatherForecast['location'] ?? 'Park' }}</h3>
                        <p class="dash-header__weather-updated">3-day forecast{{ $weatherUpdated ? ' · updated ' . $weatherUpdated : '' }}</p>
                    </div>
                    <button type="button" class="dash-header__weather-close" data-weather-close aria-label="Close weather forecast">&times;</button>
                </div>

                @if ($weatherNow)
                    <div class="dash-header__weather-now">
                        @if (!empty($weatherNow['icon']))
                            <img src="{{ $weatherNow['icon'] }}" alt="" class="dash-header__weather-now-icon">
                        @endif
                        <div class="dash-header__weather-now-main">
                            <span class="dash-header__weather-now-temp">{{ round($weatherNow['temp_c'] ?? 0) }}°</span>
                            <span class="dash-header__weather-now-cond">{{ $weatherNow['condition'] ?? '—' }}</span>
                        </div>
                        <div class="dash-header__weather-now-metrics">
                            <div class="dash-header__weather-now-metric">
                                <span>Feels like</span>
                                <strong>{{ round($weatherNow['feelslike_c'] ?? $weatherNow['temp_c'] ?? 0) }}°</strong>
                            </div>
                            <div class="dash-header__weather-now-metric">
                                <span>Humidity</span>
                                <strong>{{ $weatherNow['humidity'] ?? 0 }}%</strong>
                            </div>
                            <div class="dash-header__weather-now-metric">
                                <span>Wind</span>
                                <strong>{{ round($weatherNow['wind_kph'] ?? 0) }} km/h</strong>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($weatherForecast && count($weatherForecast['days'] ?? []) > 0)
                    <div class="dash-header__weather-tabs" role="tablist" aria-label="Forecast days">
                        @foreach ($weatherForecast['days'] as $dayIndex => $day)
                            <button type="button" class="dash-header__weather-tab {{ $dayIndex === 0 ? 'is-active' : '' }}" data-weather-tab="{{ $dayIndex }}" role="tab" aria-selected="{{ $dayIndex === 0 ? 'true' : 'false' }}">
                                <span class="dash-header__weather-tab-day">{{ $day['day_name'] }}</span>
                                <span class="dash-header__weather-tab-temp">
                                    @if (!empty($day['icon']))
                                        <img src="{{ $day['icon'] }}" alt="">
                                    @endif
                                    {{ round($day['max_temp_c'] ?? 0) }}° / {{ round($day['min_temp_c'] ?? 0) }}°
                                </span>
                            </button>
                        @endforeach
                    </div>

                    @foreach ($weatherForecast['days'] as $dayIndex => $day)
                        <div class="dash-header__weather-hours {{ $dayIndex === 0 ? 'is-active' : '' }}" data-weather-hours="{{ $dayIndex }}">
                            <div class="dash-header__weather-hours-label">
                                <span>Hourly — {{ $day['day_name'] }}</span>
                                <span class="dash-header__weather-hours-rain">Rain {{ $day['chance_of_rain'] ?? 0 }}%</span>
                            </div>
                            <div class="dash-header__weather-hours-strip">
                                @forelse ($day['hourly'] as $hour)
                                    @php
                                        // One chip every 3 hours (12 AM, 3 AM, ... 9 PM);
                                        // skip hours that have already passed today.
                                        if ($hour['hour'] % 3 !== 0) continue;
                                        if ($day['is_today'] && ($hour['is_past'] ?? false)) continue;
                                    @endphp
                                    <div class="dash-header__weather-hour">
                                        <span class="dash-header__weather-hour-time">{{ $hour['time_label'] }}</span>
                                        @if (!empty($hour['icon']))
                                            <img src="{{ $hour['icon'] }}" alt="" class="dash-header__weather-hour-icon">
                                        @endif
                                        <span class="dash-header__weather-hour-temp">{{ round($hour['temp_c'] ?? 0) }}°</span>
                                        <span class="dash-header__weather-hour-rain">{{ $hour['chance_of_rain'] ?? 0 }}%</span>
                                    </div>
                                @empty
                                    <p class="dash-header__weather-hours-empty">No hourly data available.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
        @endif

        <!-- Notification Bell Dropdown -->
        <div class="dash-header__notif-wrapper">
            <button type="button" class="dash-header__notif-btn" id="notifBellBtn" aria-label="Notifications">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                @if($totalNotifs > 0)
                    <span class="dash-header__notif-badge">{{ $totalNotifs }}</span>
                @endif
            </button>
            <div class="dash-header__notif-dropdown" id="notifDropdown">
                <div class="dash-header__notif-header">
                    <h3>Notifications</h3>
                    <span class="dash-header__notif-count">{{ $totalNotifs }} new</span>
                </div>
                <div class="dash-header__notif-list">
                    @if($pendingCount > 0)
                        <a href="{{ route('staff.reservations') }}" class="dash-header__notif-item">
                            <div class="dash-header__notif-icon dash-header__notif-icon--warning">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="dash-header__notif-content">
                                <p class="dash-header__notif-title">Pending Online Reservations</p>
                                <p class="dash-header__notif-desc">{{ $pendingCount }} reservation(s) awaiting action</p>
                            </div>
                        </a>
                    @endif
                    @if($checkedInCount > 0)
                        <a href="{{ route('staff.checkins') }}" class="dash-header__notif-item">
                            <div class="dash-header__notif-icon dash-header__notif-icon--success">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <div class="dash-header__notif-content">
                                <p class="dash-header__notif-title">Guests On-Site</p>
                                <p class="dash-header__notif-desc">{{ $checkedInCount }} guest(s) currently active at the park</p>
                            </div>
                        </a>
                    @endif
                    @if($totalNotifs === 0)
                        <div class="dash-header__notif-empty">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p>No new notifications right now.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>
