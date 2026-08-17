@props([
    'title' => 'Dashboard',
    'subtitle' => null,
])

@php
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

    $authUser = session('auth_user');
    $userRole = $authUser['role'] ?? 'guest';
    $userId = (int) ($authUser['id'] ?? 0);
    $userLastSeenId = \App\Models\UserActivityRead::getLastSeenId($userRole, $userId);

    $initialActivities = \App\Models\ActivityLog::query()->orderByDesc('id')->take(20)->get();
    $latestActivityId = \App\Models\ActivityLog::max('id') ?? 0;

    $initialUnreadCount = \App\Models\ActivityLog::where('id', '>', $userLastSeenId)->count();
@endphp

<header class="sticky top-0 z-[100] flex items-center justify-between gap-4 px-6 h-[3.75rem] min-h-[3.75rem] bg-white dark:bg-[#0d2116] border-b border-[#e5e9e6] dark:border-[#1a3d2a] shadow-[0_2px_12px_rgba(0,0,0,0.06)] dark:shadow-[0_4px_20px_rgba(0,0,0,0.4)] transition-all duration-300 ease-in-out -mt-[1.5rem] -mx-[1.5rem] mb-[1.5rem]">
    <div class="flex items-center gap-4 min-w-0">
        <button type="button" class="inline-flex items-center justify-center w-9 h-9 border border-[#dbe3de] dark:border-[#1a3d2a] rounded-[0.6rem] bg-[#f4f7f5] dark:bg-[#12281c] text-[#0d2c1d] dark:text-white cursor-pointer transition-all duration-200 ease-in-out shrink-0 hover:bg-[#e8efe9] dark:hover:bg-[#183525] hover:border-[#c5d4cb] dark:hover:border-[#27573d] hover:text-[#178a52] dark:hover:text-[#3cac77]" data-dash-sidebar-toggle aria-label="Toggle menu">
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
        <div class="col-start-1 w-full inline-flex items-center justify-center gap-[0.45rem] py-[0.3rem] px-[0.75rem] rounded-full bg-[#eaf5ee] dark:bg-[#133020] border border-[#c2e2ce] dark:border-[#1e4e33] text-[#2f6f45] dark:text-[#8fd0ab] text-[0.75rem] font-semibold">
            <span class="w-[0.45rem] h-[0.45rem] rounded-full bg-[#2f9e63] shadow-[0_0_6px_rgba(47,158,99,0.4)] animate-[statusPulse_2s_infinite_ease-in-out]"></span>
            <span class="font-semibold">Park Open</span>
        </div>

        <!-- Weather Forecast Widget -->
        @if ($weatherNow || $weatherForecast)
        <div class="col-start-2 relative">
            <button type="button" class="inline-flex flex-col items-stretch justify-center gap-[0.12rem] w-full py-[0.3rem] px-[0.8rem] border border-[#dbe3de] dark:border-[#1a3d2a] rounded-[0.85rem] bg-[#f4f7f5] dark:bg-[#12281c] text-[#0d2c1d] dark:text-white font-['Montserrat',sans-serif] text-[0.78rem] font-semibold cursor-pointer transition-all duration-200 ease-in-out hover:bg-[#e8efe9] dark:hover:bg-[#183525] hover:border-[#c5d4cb] dark:hover:border-[#27573d] hover:text-[#178a52] dark:hover:text-[#c8a45d] [&.is-active]:bg-[#e8efe9] dark:[&.is-active]:bg-[#183525] [&.is-active]:border-[#c5d4cb] dark:[&.is-active]:border-[#27573d] [&.is-active]:text-[#178a52] dark:[&.is-active]:text-[#c8a45d] group/weather" id="weatherBtn" aria-label="Weather forecast" aria-haspopup="true" aria-expanded="false" aria-controls="weatherDropdown">
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

            <div class="absolute top-[calc(100%+0.5rem)] right-0 w-[22rem] max-w-[calc(100vw-2rem)] bg-white dark:bg-[#0d2116] border border-[#dbe3de] dark:border-[#1a3d2a] rounded-[0.9rem] shadow-[0_12px_40px_rgba(0,0,0,0.12)] dark:shadow-[0_12px_40px_rgba(0,0,0,0.6)] opacity-0 invisible -translate-y-2 transition-all duration-200 ease-in-out z-[130] overflow-hidden [&.is-open]:opacity-100 [&.is-open]:visible [&.is-open]:translate-y-0" id="weatherDropdown" aria-hidden="true">
                <div class="flex items-center justify-between gap-3 py-[0.85rem] px-4 border-b border-[#e5e9e6] dark:border-[#1a3d2a] bg-[#f8faf9] dark:bg-[#12281c]">
                    <div>
                        <h3 class="m-0 mb-[0.15rem] text-[0.9rem] font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">Weather · {{ $weatherForecast['location'] ?? 'Park' }}</h3>
                        <p class="m-0 text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">3-day forecast{{ $weatherUpdated ? ' · updated ' . $weatherUpdated : '' }}</p>
                    </div>
                    <button type="button" class="inline-flex items-center justify-center w-7 h-7 border-none rounded-lg bg-transparent text-[#5a6b5c] dark:text-[#a8b8a8] text-[1.15rem] leading-none cursor-pointer shrink-0 transition-all duration-150 ease-in-out hover:bg-[#0d2c1d]/5 hover:text-[#0d2c1d] dark:hover:text-[#f5f5f0]" data-weather-close aria-label="Close weather forecast">&times;</button>
                </div>

                @if ($weatherNow)
                    <div class="flex items-center gap-[0.9rem] p-4 border-b border-[#e5e9e6] dark:border-[#1a3d2a]">
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
                            <button type="button" class="flex-1 min-w-0 flex flex-col gap-[0.2rem] py-[0.45rem] px-[0.6rem] border border-[#dbe3de] dark:border-[#1a3d2a] rounded-[0.6rem] bg-transparent text-[#5a6b5c] dark:text-[#a8b8a8] font-['Montserrat',sans-serif] cursor-pointer transition-all duration-150 ease-in-out hover:border-[#c8a45d] hover:text-[#0d2c1d] dark:hover:text-[#f5f5f0] [&.is-active]:bg-[#c8a45d]/15 [&.is-active]:border-[#c8a45d] [&.is-active]:text-[#1a3d2a] dark:[&.is-active]:text-[#c8a45d] {{ $dayIndex === 0 ? 'is-active' : '' }}" data-weather-tab="{{ $dayIndex }}" role="tab" aria-selected="{{ $dayIndex === 0 ? 'true' : 'false' }}">
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
                            <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-white/20 scrollbar-track-transparent">
                                @forelse ($day['hourly'] as $hour)
                                    @php
                                        if ($hour['hour'] % 3 !== 0) continue;
                                        if ($day['is_today'] && ($hour['is_past'] ?? false)) continue;
                                    @endphp
                                    <div class="flex flex-col items-center gap-1 min-w-[3.5rem] p-2 rounded-lg bg-[#f4f7f5] dark:bg-[#12281c] border border-[#dbe3de] dark:border-[#1a3d2a] shrink-0">
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

        <!-- Notification Bell Dropdown (Live Activity Logs) -->
        <div class="col-start-3 relative" id="headerNotifContainer">
            <button type="button" class="relative inline-flex items-center justify-center w-9 h-9 border border-[#dbe3de] dark:border-[#1a3d2a] rounded-full bg-[#f4f7f5] dark:bg-[#12281c] text-[#0d2c1d] dark:text-white cursor-pointer transition-all duration-200 ease-in-out hover:bg-[#e8efe9] dark:hover:bg-[#183525] hover:border-[#c5d4cb] dark:hover:border-[#27573d] hover:text-[#178a52] dark:hover:text-[#3cac77] [&.is-active]:bg-[#e8efe9] dark:[&.is-active]:bg-[#183525] [&.is-active]:border-[#c5d4cb] dark:[&.is-active]:border-[#27573d] [&.is-active]:text-[#178a52] dark:[&.is-active]:text-[#3cac77]" id="notifBellBtn" aria-label="Activity Notifications" aria-haspopup="true" aria-expanded="false" aria-controls="notifDropdown">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-[1.2rem] h-[1.2rem]">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span id="notifBadge" class="{{ $initialUnreadCount > 0 ? '' : 'hidden' }} absolute -top-[3px] -right-[3px] min-w-[1.15rem] h-[1.15rem] px-1 rounded-full bg-red-600 text-white text-[0.65rem] font-extrabold flex items-center justify-center border-2 border-white dark:border-[#0d2116] shadow-sm animate-[badgeBounce_0.3s_ease]" style="{{ $initialUnreadCount > 0 ? 'display: flex;' : 'display: none;' }}">{{ $initialUnreadCount > 99 ? '99+' : $initialUnreadCount }}</span>
            </button>

            <div class="absolute top-[calc(100%+0.5rem)] right-0 w-[24rem] max-w-[calc(100vw-2rem)] bg-white dark:bg-[#0d2116] border border-[#dbe3de] dark:border-[#1a3d2a] rounded-[1rem] shadow-[0_12px_40px_rgba(0,0,0,0.12)] dark:shadow-[0_12px_40px_rgba(0,0,0,0.6)] opacity-0 invisible -translate-y-2 transition-all duration-200 ease-in-out z-[130] overflow-hidden [&.is-open]:opacity-100 [&.is-open]:visible [&.is-open]:translate-y-0" id="notifDropdown" aria-hidden="true" data-latest-id="{{ $latestActivityId }}" data-user-type="{{ $userRole }}" data-user-id="{{ $userId }}" data-last-seen-id="{{ $userLastSeenId }}">
                <div class="flex items-center justify-between py-3 px-4 border-b border-[#e5e9e6] dark:border-[#1a3d2a] bg-[#f8faf9] dark:bg-[#12281c]">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.6)] animate-pulse"></span>
                        <h3 class="m-0 text-sm font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">Activity Logs</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="notifUnreadPill" class="{{ $initialUnreadCount > 0 ? '' : 'hidden' }} text-[0.68rem] font-bold py-0.5 px-2 rounded-full bg-red-500/15 text-red-600 dark:text-red-400">{{ $initialUnreadCount }} new</span>
                        <button type="button" id="markAllNotifsReadBtn" class="text-[0.72rem] font-semibold text-[var(--hp-green)] hover:underline dark:text-[var(--hp-gold)] bg-transparent border-0 cursor-pointer p-0">Mark read</button>
                    </div>
                </div>

                <div class="max-h-[22rem] overflow-y-auto divide-y divide-[#e5e9e6] dark:divide-[#1a3d2a]" id="notifList">
                    @forelse($initialActivities as $act)
                        @php
                            $type = $act->activity_type;
                            $iconBg = 'bg-[#4c9a5f]/15 dark:bg-[#4c9a5f]/20 text-[#2f6f45] dark:text-[#8fd0ab]';
                            if (in_array($type, ['check_in', 'checked_in'])) {
                                $iconBg = 'bg-emerald-500/15 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400';
                            } elseif (in_array($type, ['check_out', 'amenity_checked_out'])) {
                                $iconBg = 'bg-blue-500/15 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400';
                            } elseif (in_array($type, ['stay_extended', 'amenity_extended'])) {
                                $iconBg = 'bg-amber-500/15 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400';
                            } elseif ($type === 'amenity_added') {
                                $iconBg = 'bg-teal-500/15 dark:bg-teal-500/20 text-teal-600 dark:text-teal-400';
                            } elseif (str_starts_with($type, 'rule_')) {
                                $iconBg = 'bg-purple-500/15 dark:bg-purple-500/20 text-purple-600 dark:text-purple-400';
                            } elseif (str_starts_with($type, 'staff_')) {
                                $iconBg = 'bg-cyan-500/15 dark:bg-cyan-500/20 text-cyan-600 dark:text-cyan-400';
                            } elseif (str_contains($type, 'cancel')) {
                                $iconBg = 'bg-rose-500/15 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400';
                            }

                            $isNew = ($act->id > $userLastSeenId);
                        @endphp
                        <div class="notif-item flex items-start gap-3 p-3.5 transition-colors hover:bg-[#f4f7f5] dark:hover:bg-[#12281c] cursor-pointer" data-activity-id="{{ $act->id }}">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 {{ $iconBg }}">
                                @if(in_array($type, ['check_in', 'checked_in']))
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M18 11l2 2 4-4"/></svg>
                                @elseif(in_array($type, ['check_out', 'amenity_checked_out']))
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                @elseif(in_array($type, ['stay_extended', 'amenity_extended']))
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @elseif(str_starts_with($type, 'rule_'))
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-1 mb-0.5">
                                    <span class="text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] truncate">{{ $act->title }}</span>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <span class="notif-new-badge {{ $isNew ? '' : 'hidden' }} text-[0.6rem] font-extrabold uppercase px-1.5 py-0.2 rounded-md bg-red-600 text-white tracking-wide shadow-sm">NEW</span>
                                        <span class="text-[0.68rem] text-[#5a6b5c] dark:text-[#a8b8a8]">{{ $act->created_at ? $act->created_at->diffForHumans(null, true) : 'now' }}</span>
                                    </div>
                                </div>
                                <p class="m-0 text-[0.74rem] leading-[1.35] text-[#5a6b5c] dark:text-[#a8b8a8] line-clamp-2">{{ $act->description }}</p>
                                @if($act->actor_name)
                                    <div class="mt-1 flex items-center gap-1.5 text-[0.66rem] text-[var(--hp-green)] dark:text-[var(--hp-gold)] font-medium">
                                        <span>By: {{ $act->actor_name }} ({{ ucfirst($act->actor_role ?? 'Staff') }})</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-10 px-4 text-center text-[#5a6b5c] dark:text-[#a8b8a8]" id="notifEmptyState">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" class="w-8 h-8 mb-2 opacity-40 mx-auto"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="m-0 text-[0.8rem]">No activity logs yet.</p>
                        </div>
                    @endforelse
                </div>

                <div class="py-2.5 px-4 bg-[#f8faf9] dark:bg-[#12281c] border-t border-[#e5e9e6] dark:border-[#1a3d2a] text-center">
                    <span class="text-[0.72rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Live activity feed updates in real-time</span>
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
