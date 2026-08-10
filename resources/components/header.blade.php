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

<header class="sticky top-0 z-[100] flex items-center justify-between gap-4 px-6 h-[3.75rem] min-h-[3.75rem] bg-white/42 dark:bg-[#08120c]/42 border-b border-white/55 dark:border-white/12 border-t border-t-white/25 shadow-[0_8px_32px_0_rgba(31,38,135,0.08)] dark:shadow-[0_8px_32px_0_rgba(0,0,0,0.4)] backdrop-blur-[6px] saturate-150 transition-all duration-300 ease-in-out -mt-[1.5rem] -mx-[1.5rem] mb-[1.5rem]">
    <div class="flex items-center gap-4 min-w-0">
        <button type="button" class="inline-flex items-center justify-center w-9 h-9 border border-white/50 dark:border-white/10 rounded-[0.6rem] bg-white/40 dark:bg-black/35 backdrop-blur-[6px] text-[#0d2c1d] dark:text-white cursor-pointer transition-all duration-200 ease-in-out shrink-0 hover:bg-white/50 dark:hover:bg-black/45 hover:border-white/80 dark:hover:border-white/25 hover:text-[#178a52] dark:hover:text-[#3cac77]" data-dash-sidebar-toggle aria-label="Toggle menu">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <div class="flex flex-col gap-0.5 min-w-0">
            <h1 class="m-0 font-['Poppins',sans-serif] text-[1.15rem] font-bold text-[#0d2c1d] dark:text-[#f5f5f0] leading-[1.2] whitespace-nowrap overflow-hidden text-ellipsis">{{ $title }}</h1>
            @if ($subtitle)
                <p class="m-0 text-[0.78rem] text-[#5a6b5c] dark:text-[#a8b8a8] whitespace-nowrap overflow-hidden text-ellipsis">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-[6.75rem_13rem_2.25rem] items-center gap-[1.15rem] shrink-0">
        <!-- Live Park Status Pill -->
        <div class="col-start-1 w-full inline-flex items-center justify-center gap-[0.45rem] py-[0.3rem] px-[0.75rem] rounded-full bg-[#4c9a5f]/12 dark:bg-[#4c9a5f]/20 border border-[#4c9a5f]/30 dark:border-[#4c9a5f]/40 text-[#2f6f45] dark:text-[#8fd0ab] text-[0.75rem] font-semibold">
            <span class="w-[0.45rem] h-[0.45rem] rounded-full bg-[#2f9e63] shadow-[0_0_6px_rgba(47,158,99,0.4)] animate-[statusPulse_2s_infinite_ease-in-out]"></span>
            <span class="font-semibold">Park Open</span>
        </div>

        <!-- Weather Forecast Widget -->
        @if ($weatherNow || $weatherForecast)
        <div class="col-start-2 relative">
            <button type="button" class="inline-flex flex-col items-stretch justify-center gap-[0.12rem] w-full py-[0.3rem] px-[0.8rem] border border-white/50 dark:border-white/10 rounded-[0.85rem] bg-white/40 dark:bg-black/35 backdrop-blur-[6px] text-[#0d2c1d] dark:text-white font-['Montserrat',sans-serif] text-[0.78rem] font-semibold cursor-pointer transition-all duration-200 ease-in-out hover:bg-white/50 dark:hover:bg-black/45 hover:border-white/80 dark:hover:border-white/25 hover:text-[#178a52] dark:hover:text-[#c8a45d] [&.is-active]:bg-white/50 dark:[&.is-active]:bg-black/45 [&.is-active]:border-white/80 dark:[&.is-active]:border-white/25 [&.is-active]:text-[#178a52] dark:[&.is-active]:text-[#c8a45d] group/weather" id="weatherBtn" aria-label="Weather forecast" aria-haspopup="true" aria-expanded="false" aria-controls="weatherDropdown">
                <span class="inline-flex items-center justify-center gap-[0.45rem] whitespace-nowrap">
                    @if (!empty($weatherNow['icon']))
                        <img src="{{ $weatherNow['icon'] }}" alt="" class="w-[1.35rem] h-[1.35rem] object-contain shrink-0">
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-[1.35rem] h-[1.35rem] shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    @endif
                    <span class="text-[0.82rem] font-bold">{{ round($weatherNow['temp_c'] ?? 0) }}°</span>
                    <span class="text-[0.72rem] text-[#5a6b5c] dark:text-[#a8b8a8] max-w-[10.5rem] overflow-hidden text-ellipsis whitespace-nowrap">{{ $weatherNow['condition'] ?? '—' }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-[0.9rem] h-[0.9rem] text-[#5a6b5c] dark:text-[#a8b8a8] transition-transform duration-200 ease-in-out shrink-0 group-[.is-active]/weather:rotate-180" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </span>
                <span class="text-[0.66rem] font-semibold leading-none tracking-[0.01em] text-[#5a6b5c] dark:text-[#a8b8a8] whitespace-nowrap overflow-hidden text-ellipsis text-center">
                    <span id="weatherClockDay">{{ now()->format('l') }}</span>
                    <span class="opacity-60 mx-[0.15rem]" aria-hidden="true">·</span>
                    <span id="weatherClockTime">{{ now()->format('g:i A') }}</span>
                </span>
            </button>

            <div class="absolute top-[calc(100%+0.5rem)] right-0 w-[22rem] max-w-[calc(100vw-2rem)] bg-white/65 dark:bg-black/55 backdrop-blur-[6px] saturate-125 border border-white/80 dark:border-white/25 rounded-[0.9rem] shadow-[0_8px_32px_0_rgba(31,38,135,0.07)] dark:shadow-[0_8px_32px_0_rgba(0,0,0,0.37)] opacity-0 invisible -translate-y-2 transition-all duration-200 ease-in-out z-[130] overflow-hidden [&.is-open]:opacity-100 [&.is-open]:visible [&.is-open]:translate-y-0" id="weatherDropdown" aria-hidden="true">
                <div class="flex items-center justify-between gap-3 py-[0.85rem] px-4 border-b border-white/50 dark:border-white/10 bg-white/40 dark:bg-transparent">
                    <div>
                        <h3 class="m-0 mb-[0.15rem] text-[0.9rem] font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">Weather · {{ $weatherForecast['location'] ?? 'Park' }}</h3>
                        <p class="m-0 text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">3-day forecast{{ $weatherUpdated ? ' · updated ' . $weatherUpdated : '' }}</p>
                    </div>
                    <button type="button" class="inline-flex items-center justify-center w-7 h-7 border-none rounded-lg bg-transparent text-[#5a6b5c] dark:text-[#a8b8a8] text-[1.15rem] leading-none cursor-pointer shrink-0 transition-all duration-150 ease-in-out hover:bg-[#0d2c1d]/5 hover:text-[#0d2c1d] dark:hover:text-[#f5f5f0]" data-weather-close aria-label="Close weather forecast">&times;</button>
                </div>

                @if ($weatherNow)
                    <div class="flex items-center gap-[0.9rem] p-4 border-b border-white/55 dark:border-white/12">
                        @if (!empty($weatherNow['icon']))
                            <img src="{{ $weatherNow['icon'] }}" alt="" class="w-12 h-12 object-contain shrink-0">
                        @endif
                        <div class="flex flex-col min-w-0">
                            <span class="font-['Poppins',sans-serif] text-[1.6rem] font-bold leading-[1.1] text-[#0d2c1d] dark:text-[#f5f5f0]">{{ round($weatherNow['temp_c'] ?? 0) }}°</span>
                            <span class="text-[0.78rem] text-[#5a6b5c] dark:text-[#a8b8a8] leading-[1.35]">{{ $weatherNow['condition'] ?? '—' }}</span>
                        </div>
                        <div class="grid gap-[0.4rem] ml-auto shrink-0">
                            <div class="flex items-baseline gap-2">
                                <span class="text-[0.68rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Feels like</span>
                                <strong class="text-[0.76rem] font-bold text-[#0d2c1d] dark:text-[#f5f5f0] min-w-[3.6rem] text-right">{{ round($weatherNow['feelslike_c'] ?? $weatherNow['temp_c'] ?? 0) }}°</strong>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-[0.68rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Humidity</span>
                                <strong class="text-[0.76rem] font-bold text-[#0d2c1d] dark:text-[#f5f5f0] min-w-[3.6rem] text-right">{{ $weatherNow['humidity'] ?? 0 }}%</strong>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-[0.68rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Wind</span>
                                <strong class="text-[0.76rem] font-bold text-[#0d2c1d] dark:text-[#f5f5f0] min-w-[3.6rem] text-right">{{ round($weatherNow['wind_kph'] ?? 0) }} km/h</strong>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($weatherForecast && count($weatherForecast['days'] ?? []) > 0)
                    <div class="flex gap-[0.4rem] pt-3 px-4 pb-2 overflow-x-auto" role="tablist" aria-label="Forecast days">
                        @foreach ($weatherForecast['days'] as $dayIndex => $day)
                            <button type="button" class="flex-1 min-w-0 flex flex-col gap-[0.2rem] py-[0.45rem] px-[0.6rem] border border-white/55 dark:border-white/12 rounded-[0.6rem] bg-transparent text-[#5a6b5c] dark:text-[#a8b8a8] font-['Montserrat',sans-serif] cursor-pointer transition-all duration-150 ease-in-out hover:border-[#c8a45d] hover:text-[#0d2c1d] dark:hover:text-[#f5f5f0] [&.is-active]:bg-[#c8a45d]/15 [&.is-active]:border-[#c8a45d] [&.is-active]:text-[#1a3d2a] dark:[&.is-active]:text-[#c8a45d] {{ $dayIndex === 0 ? 'is-active' : '' }}" data-weather-tab="{{ $dayIndex }}" role="tab" aria-selected="{{ $dayIndex === 0 ? 'true' : 'false' }}">
                                <span class="text-[0.72rem] font-bold">{{ $day['day_name'] }}</span>
                                <span class="inline-flex items-center gap-[0.3rem] text-[0.68rem] font-semibold">
                                    @if (!empty($day['icon']))
                                        <img src="{{ $day['icon'] }}" alt="" class="w-4 h-4">
                                    @endif
                                    {{ round($day['max_temp_c'] ?? 0) }}° / {{ round($day['min_temp_c'] ?? 0) }}°
                                </span>
                            </button>
                        @endforeach
                    </div>

                    @foreach ($weatherForecast['days'] as $dayIndex => $day)
                        <div class="hidden [&.is-active]:block p-4 {{ $dayIndex === 0 ? 'is-active' : '' }}" data-weather-hours="{{ $dayIndex }}">
                            <div class="flex items-center justify-between mb-3 text-[0.7rem] font-semibold text-[#5a6b5c] dark:text-[#a8b8a8]">
                                <span>Hourly — {{ $day['day_name'] }}</span>
                                <span class="text-[#2a6a8f] dark:text-[#6ea9c9]">Rain {{ $day['chance_of_rain'] ?? 0 }}%</span>
                            </div>
                            <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-thin scrollbar-thumb-white/30 scrollbar-track-transparent">
                                @forelse ($day['hourly'] as $hour)
                                    @php
                                        if ($hour['hour'] % 3 !== 0) continue;
                                        if ($day['is_today'] && ($hour['is_past'] ?? false)) continue;
                                    @endphp
                                    <div class="flex flex-col items-center gap-1 min-w-[3.5rem] p-2 rounded-lg bg-white/30 dark:bg-black/30 border border-white/40 dark:border-white/10 shrink-0">
                                        <span class="text-[0.65rem] font-semibold text-[#5a6b5c] dark:text-[#a8b8a8]">{{ $hour['time_label'] }}</span>
                                        @if (!empty($hour['icon']))
                                            <img src="{{ $hour['icon'] }}" alt="" class="w-6 h-6 object-contain">
                                        @endif
                                        <span class="text-[0.75rem] font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">{{ round($hour['temp_c'] ?? 0) }}°</span>
                                        <span class="text-[0.6rem] font-semibold text-[#2a6a8f] dark:text-[#6ea9c9]">{{ $hour['chance_of_rain'] ?? 0 }}%</span>
                                    </div>
                                @empty
                                    <p class="m-0 text-[0.75rem] italic text-[#889b8a] dark:text-[#6e7c73] p-2 text-center w-full">No hourly data available.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
        @endif

        <!-- Notification Bell Dropdown -->
        <div class="col-start-3 relative">
            <button type="button" class="relative inline-flex items-center justify-center w-9 h-9 border border-white/50 dark:border-white/10 rounded-full bg-white/40 dark:bg-black/35 backdrop-blur-[6px] text-[#0d2c1d] dark:text-white cursor-pointer transition-all duration-200 ease-in-out hover:bg-white/50 dark:hover:bg-black/45 hover:border-white/80 dark:hover:border-white/25 hover:text-[#178a52] dark:hover:text-[#3cac77] [&.is-active]:bg-white/50 dark:[&.is-active]:bg-black/45 [&.is-active]:border-white/80 dark:[&.is-active]:border-white/25 [&.is-active]:text-[#178a52] dark:[&.is-active]:text-[#3cac77]" id="notifBellBtn" aria-label="Notifications">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-[1.2rem] h-[1.2rem]">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                @if($totalNotifs > 0)
                    <span class="absolute -top-[2px] -right-[2px] min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-[#ef4444] text-white text-[0.65rem] font-bold flex items-center justify-center border-2 border-[#fdfcf8] dark:border-[#0b2418] animate-[badgeBounce_0.3s_ease]">{{ $totalNotifs }}</span>
                @endif
            </button>
            <div class="absolute top-[calc(100%+0.5rem)] right-0 w-[20rem] bg-white/65 dark:bg-black/55 backdrop-blur-[6px] saturate-125 border border-white/80 dark:border-white/25 rounded-[0.9rem] shadow-[0_8px_32px_0_rgba(31,38,135,0.07)] dark:shadow-[0_8px_32px_0_rgba(0,0,0,0.37)] opacity-0 invisible -translate-y-2 transition-all duration-200 ease-in-out z-[120] overflow-hidden [&.is-open]:opacity-100 [&.is-open]:visible [&.is-open]:translate-y-0" id="notifDropdown">
                <div class="flex items-center justify-between py-[0.85rem] px-4 border-b border-white/50 dark:border-white/10 bg-white/40 dark:bg-transparent">
                    <h3 class="m-0 text-[0.88rem] font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">Notifications</h3>
                    <span class="text-[0.72rem] font-semibold py-[0.2rem] px-[0.5rem] rounded-full bg-[#c8a45d]/15 text-[#a8843f] dark:text-[#d9b058]">{{ $totalNotifs }} new</span>
                </div>
                <div class="max-h-[18rem] overflow-y-auto">
                    @if($pendingCount > 0)
                        <a href="{{ route('staff.reservations') }}" class="flex items-start gap-3 py-[0.85rem] px-4 no-underline border-b border-[#0d2c1d]/5 dark:border-white/10 last:border-b-0 transition-colors duration-150 ease-in-out hover:bg-[#0d2c1d]/5 dark:hover:bg-white/5">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 bg-[#eab308]/15 text-[#d97706]">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-[1.1rem] h-[1.1rem]"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="m-0 mb-[0.15rem] text-[0.82rem] font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">Pending Online Reservations</p>
                                <p class="m-0 text-[0.75rem] text-[#5a6b5c] dark:text-[#a8b8a8] leading-[1.35]">{{ $pendingCount }} reservation(s) awaiting action</p>
                            </div>
                        </a>
                    @endif
                    @if($checkedInCount > 0)
                        <a href="{{ route('staff.checkins') }}" class="flex items-start gap-3 py-[0.85rem] px-4 no-underline border-b border-[#0d2c1d]/5 dark:border-white/10 last:border-b-0 transition-colors duration-150 ease-in-out hover:bg-[#0d2c1d]/5 dark:hover:bg-white/5">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 bg-[#22c55e]/15 text-[#16a34a]">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-[1.1rem] h-[1.1rem]"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="m-0 mb-[0.15rem] text-[0.82rem] font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">Guests On-Site</p>
                                <p class="m-0 text-[0.75rem] text-[#5a6b5c] dark:text-[#a8b8a8] leading-[1.35]">{{ $checkedInCount }} guest(s) currently active at the park</p>
                            </div>
                        </a>
                    @endif
                    @if($totalNotifs === 0)
                        <div class="py-8 px-4 text-center text-[#5a6b5c] dark:text-[#a8b8a8]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" class="w-8 h-8 mb-2 opacity-50 mx-auto"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="m-0 text-[0.8rem]">No new notifications right now.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>
<style>
@keyframes statusPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.3); opacity: 0.7; }
}
@keyframes badgeBounce {
    0% { transform: scale(0); }
    80% { transform: scale(1.2); }
    100% { transform: scale(1); }
}
</style>
