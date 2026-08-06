@props([
    'title' => 'Dashboard',
    'subtitle' => null,
    'showWelcome' => false,
])

@php
    $parkSettings = \App\Models\ParkSetting::first();
    $currentTime = now();
    $currentHour = $currentTime->format('H:i');
    $timePeriod = 'Daytime';

    $daytimeStart = $parkSettings?->daytime_start ?? '06:00';
    $daytimeEnd = $parkSettings?->daytime_end ?? '17:00';
    $nighttimeStart = $parkSettings?->nighttime_start ?? '17:00';
    $nighttimeEnd = $parkSettings?->nighttime_end ?? '06:00';

    // Check if current time is in nighttime range
    if ($nighttimeStart && $nighttimeEnd) {
        if ($nighttimeStart <= $nighttimeEnd) {
            // Same day range (e.g., 18:00 - 22:00)
            if ($currentHour >= $nighttimeStart && $currentHour <= $nighttimeEnd) {
                $timePeriod = 'Nighttime';
            }
        } else {
            // Overnight range (e.g., 22:00 - 06:00)
            if ($currentHour >= $nighttimeStart || $currentHour <= $nighttimeEnd) {
                $timePeriod = 'Nighttime';
            }
        }
    }

    // Get weather forecast
    $weatherService = new \App\Services\WeatherService();
    $weatherForecast = $weatherService->getMultiDayForecast(3);
@endphp

<header class="dash-header" style="background: url('{{ asset('storage/design_images/background_image2.png') }}') center/cover no-repeat;" data-park-settings="{{ json_encode([
    'daytime_start' => $daytimeStart,
    'daytime_end' => $daytimeEnd,
    'nighttime_start' => $nighttimeStart,
    'nighttime_end' => $nighttimeEnd,
]) }}">
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
            <div class="dash-header__date-time">
                <div class="dash-header__date-time-item">
                    <span class="dash-header__date-label">Date</span>
                    <span class="dash-header__date" id="headerDate">{{ $currentTime->format('F j, Y') }}</span>
                </div>
                <div class="dash-header__date-time-item">
                    <span class="dash-header__time-label">Time</span>
                    <span class="dash-header__time" id="headerTime">{{ $currentTime->format('g:i A') }}</span>
                </div>
                <div class="dash-header__date-time-item dash-header__date-time-item--period">
                    <span class="dash-header__period-indicator {{ $timePeriod === 'Nighttime' ? 'is-nighttime' : 'is-daytime' }}" id="periodIndicator">
                        @if($timePeriod === 'Nighttime')
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        @endif
                    </span>
                    <div class="dash-header__time-period-wrapper">
                        <span class="dash-header__time-period {{ $timePeriod === 'Nighttime' ? 'is-nighttime' : 'is-daytime' }}" id="timePeriod">{{ $timePeriod }}</span>
                        <span class="dash-header__time-period-countdown {{ $timePeriod === 'Nighttime' ? 'is-daytime' : 'is-nighttime' }}" id="timePeriodCountdown"></span>
                    </div>
                </div>
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
            @if($weatherForecast && count($weatherForecast) > 0)
                <div class="dash-header__weather">
                    @foreach($weatherForecast as $day)
                        <div class="dash-header__weather-item {{ $day['is_today'] ? 'is-today' : '' }}">
                            <span class="dash-header__weather-day">{{ $day['day_name'] }}</span>
                            @if($day['icon'])
                                <img src="{{ $day['icon'] }}" alt="{{ $day['condition'] }}" class="dash-header__weather-icon">
                            @endif
                            <span class="dash-header__weather-temp">
                                {{ round($day['max_temp_c']) }}°
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</header>

<script>
(function() {
    const header = document.querySelector('.dash-header');
    if (!header) return;

    const parkSettings = JSON.parse(header.dataset.parkSettings || '{}');
    const dateElement = document.getElementById('headerDate');
    const timeElement = document.getElementById('headerTime');
    const periodIndicator = document.getElementById('periodIndicator');
    const timePeriodElement = document.getElementById('timePeriod');
    const timePeriodCountdown = document.getElementById('timePeriodCountdown');

    function getCurrentTimePeriod(currentTime) {
        const currentHour = currentTime.toTimeString().slice(0, 5);
        const nighttimeStart = parkSettings.nighttime_start || '17:00';
        const nighttimeEnd = parkSettings.nighttime_end || '06:00';

        if (nighttimeStart && nighttimeEnd) {
            if (nighttimeStart <= nighttimeEnd) {
                if (currentHour >= nighttimeStart && currentHour <= nighttimeEnd) {
                    return 'Nighttime';
                }
            } else {
                if (currentHour >= nighttimeStart || currentHour <= nighttimeEnd) {
                    return 'Nighttime';
                }
            }
        }
        return 'Daytime';
    }

    function getTimeUntilNextPeriod(currentTime) {
        const currentHour = currentTime.toTimeString().slice(0, 5);
        const nighttimeStart = parkSettings.nighttime_start || '17:00';
        const nighttimeEnd = parkSettings.nighttime_end || '06:00';
        const daytimeStart = parkSettings.daytime_start || '06:00';
        const daytimeEnd = parkSettings.daytime_end || '17:00';

        const currentPeriod = getCurrentTimePeriod(currentTime);
        let nextPeriodTime;

        if (currentPeriod === 'Daytime') {
            nextPeriodTime = nighttimeStart;
        } else {
            nextPeriodTime = daytimeStart;
        }

        const [nextHours, nextMinutes] = nextPeriodTime.split(':').map(Number);
        const [currentHours, currentMinutes] = currentHour.split(':').map(Number);

        let nextDate = new Date(currentTime);
        nextDate.setHours(nextHours, nextMinutes, 0, 0);

        if (nextDate <= currentTime) {
            nextDate.setDate(nextDate.getDate() + 1);
        }

        const diff = nextDate - currentTime;
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

        return `${hours}h ${minutes}m until ${currentPeriod === 'Daytime' ? 'Nighttime' : 'Daytime'}`;
    }

    function updateDateTime() {
        const now = new Date();
        
        // Update date
        const dateOptions = { month: 'long', day: 'numeric', year: 'numeric' };
        dateElement.textContent = now.toLocaleDateString('en-US', dateOptions);

        // Update time
        const timeOptions = { hour: 'numeric', minute: '2-digit', hour12: true };
        timeElement.textContent = now.toLocaleTimeString('en-US', timeOptions);

        // Update period
        const currentPeriod = getCurrentTimePeriod(now);
        const isNighttime = currentPeriod === 'Nighttime';

        // Update icon
        if (isNighttime) {
            periodIndicator.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>';
            periodIndicator.classList.remove('is-daytime');
            periodIndicator.classList.add('is-nighttime');
        } else {
            periodIndicator.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>';
            periodIndicator.classList.remove('is-nighttime');
            periodIndicator.classList.add('is-daytime');
        }

        // Update text
        timePeriodElement.textContent = currentPeriod;
        timePeriodElement.classList.remove('is-daytime', 'is-nighttime');
        timePeriodElement.classList.add(isNighttime ? 'is-nighttime' : 'is-daytime');

        // Update countdown
        const timeUntil = getTimeUntilNextPeriod(now);
        timePeriodCountdown.textContent = timeUntil;
        timePeriodCountdown.classList.remove('is-daytime', 'is-nighttime');
        timePeriodCountdown.classList.add(currentPeriod === 'Daytime' ? 'is-nighttime' : 'is-daytime');
    }

    // Update every second (clear any previous timer so SPA navigation doesn't stack intervals)
    if (window.__headerClockTimer) clearInterval(window.__headerClockTimer);
    window.__headerClockTimer = setInterval(updateDateTime, 1000);
    updateDateTime();
})();
</script>
