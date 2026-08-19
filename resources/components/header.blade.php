@props([
    'title' => 'Dashboard',
    'subtitle' => null,
])

@php
    $weatherService = app(\App\Services\WeatherService::class);
    // Single cached forecast call (forecast.json embeds current conditions).
    $weatherForecast = $weatherService->getMultiDayForecast(3);
    $weatherNow = $weatherForecast['now'] ?? null;
    $weatherUpdated = ! empty($weatherForecast['updated_at']) ? \Carbon\Carbon::parse($weatherForecast['updated_at'])->format('g:i A') : null;

    $authUser = session('auth_user');
    $userRole = $authUser['role'] ?? 'guest';
    $userId = (int) ($authUser['id'] ?? 0);
    $userLastSeenId = \App\Models\UserActivityRead::getLastSeenId($userRole, $userId);

    // Limit dropdown display to 20 notifications
    $initialActivities = \App\Models\ActivityLog::query()->orderByDesc('id')->take(20)->get();
    $latestActivityId = \App\Models\ActivityLog::max('id') ?? 0;

    $initialUnreadCount = \App\Models\ActivityLog::where('id', '>', $userLastSeenId)->count();

    $headerParkSettings = \App\Models\ParkSetting::first();
    $headerParkStatus = $headerParkSettings->park_status ?? 'open';
    $headerCloseDesc = $headerParkSettings->close_description ?? null;
@endphp

<header class="dash-header sticky top-0 z-[100] flex items-center justify-between gap-4 px-6 h-[3.75rem] min-h-[3.75rem] bg-white dark:bg-[#0d2116] border-b border-[#e5e9e6] dark:border-[#1a3d2a] shadow-[0_2px_12px_rgba(0,0,0,0.06)] dark:shadow-[0_4px_20px_rgba(0,0,0,0.4)] transition-all duration-300 ease-in-out -mt-[1.5rem] -mx-[1.5rem] mb-[1.5rem]" data-dash-header>
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
        <!-- Live Park Status Pill with Interactive Hover Tooltip -->
        <div class="col-start-1 w-full relative group">
            @if ($headerParkStatus === 'closed')
                <div class="dash-header__status-badge dash-header__status-badge--closed cursor-help" data-park-status-badge data-status="closed" tabindex="0">
                    <span class="dash-header__status-dot"></span>
                    <span class="font-semibold">Park Closed</span>
                </div>
                <div class="dash-header__status-tooltip pointer-events-none opacity-0 invisible group-hover:opacity-100 group-hover:visible group-focus-within:opacity-100 group-focus-within:visible transition-all duration-200" role="tooltip">
                    <div class="text-[0.7rem] font-bold text-red-700 dark:text-red-400 mb-0.5 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Park Currently Closed</span>
                    </div>
                    <div class="text-[0.72rem] text-[#3d4a3e] dark:text-[#d1ddd4] leading-relaxed" data-park-status-tooltip>
                        {{ $headerCloseDesc ?: 'The park is temporarily closed for maintenance or weather conditions.' }}
                    </div>
                </div>
            @else
                <div class="dash-header__status-badge cursor-help" data-park-status-badge data-status="open" tabindex="0">
                    <span class="dash-header__status-dot"></span>
                    <span class="font-semibold">Park Open</span>
                </div>
                <div class="dash-header__status-tooltip pointer-events-none opacity-0 invisible group-hover:opacity-100 group-hover:visible group-focus-within:opacity-100 group-focus-within:visible transition-all duration-200" role="tooltip">
                    <div class="text-[0.7rem] font-bold text-emerald-700 dark:text-emerald-400 mb-0.5 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Park Is Open</span>
                    </div>
                    <div class="text-[0.72rem] text-[#3d4a3e] dark:text-[#d1ddd4] leading-relaxed" data-park-status-tooltip>
                        The park is open and operating normally for day and night guests.
                    </div>
                </div>
            @endif
        </div>

        @if ($weatherNow)
        <!-- Live Weather Forecast Widget -->
        <div class="col-start-2 relative">
            <button type="button" class="w-full flex items-center justify-between gap-[0.35rem] py-[0.3rem] px-[0.6rem] border border-[#dbe3de] dark:border-[#1a3d2a] rounded-full bg-[#f4f7f5] dark:bg-[#12281c] text-[#0d2c1d] dark:text-white cursor-pointer transition-all duration-200 ease-in-out hover:bg-[#e8efe9] dark:hover:bg-[#183525] hover:border-[#c5d4cb] dark:hover:border-[#27573d] [&.is-active]:bg-[#e8efe9] dark:[&.is-active]:bg-[#183525] [&.is-active]:border-[#c5d4cb] dark:[&.is-active]:border-[#27573d]" id="weatherBtn" aria-label="Toggle weather forecast" aria-haspopup="true" aria-expanded="false" aria-controls="weatherDropdown">
                <div class="flex items-center gap-[0.35rem] min-w-0">
                    <img src="{{ $weatherNow['icon'] }}" alt="{{ $weatherNow['condition'] }}" class="w-[1.1rem] h-[1.1rem] object-contain shrink-0" onerror="this.style.display='none';">
                    <span class="text-[0.75rem] font-bold text-[#0d2c1d] dark:text-[#f5f5f0] truncate">{{ round($weatherNow['temp_c']) }}°C</span>
                </div>
                <div class="flex items-center gap-[0.3rem] text-[#2a6a8f] dark:text-[#6ea9c9] shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="w-[0.75rem] h-[0.75rem] shrink-0">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                    </svg>
                    <span class="text-[0.75rem] font-semibold">{{ $weatherNow['chance_of_rain'] ?? 0 }}%</span>
                </div>
                <div class="flex items-center gap-[0.25rem] text-[#5a6b5c] dark:text-[#a8b8a8] border-l border-[#dbe3de] dark:border-[#1a3d2a] pl-[0.35rem] shrink-0">
                    <span class="text-[0.75rem] font-medium" id="weatherClockDay">{{ now()->format('D') }}</span>
                    <span class="text-[0.75rem] font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]" id="weatherClockTime">{{ now()->format('g:i A') }}</span>
                </div>
            </button>

            <div class="absolute top-[calc(100%+0.5rem)] right-0 w-[22.5rem] max-w-[calc(100vw-2rem)] bg-white dark:bg-[#0d2116] border border-[#dbe3de] dark:border-[#1a3d2a] rounded-[1rem] shadow-[0_12px_40px_rgba(0,0,0,0.12)] dark:shadow-[0_12px_40px_rgba(0,0,0,0.6)] opacity-0 invisible -translate-y-2 transition-all duration-200 ease-in-out z-[120] overflow-hidden [&.is-open]:opacity-100 [&.is-open]:visible [&.is-open]:translate-y-0" id="weatherDropdown" aria-hidden="true">
                <div class="flex items-center justify-between py-3 px-4 border-b border-[#e5e9e6] dark:border-[#1a3d2a] bg-[#f8faf9] dark:bg-[#12281c]">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">Park Weather · Jasaan, Misamis Oriental</span>
                    </div>
                    @if ($weatherUpdated)
                        <span class="text-[0.68rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Updated {{ $weatherUpdated }}</span>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-3 p-4 bg-gradient-to-br from-[#f0f7f2] to-[#e4efe7] dark:from-[#112a1c] dark:to-[#091710] border-b border-[#e5e9e6] dark:border-[#1a3d2a]">
                    <div class="flex items-center gap-3">
                        <img src="{{ $weatherNow['icon'] }}" alt="{{ $weatherNow['condition'] }}" class="w-12 h-12 object-contain filter drop-shadow-md">
                        <div>
                            <div class="text-2xl font-black text-[#0d2c1d] dark:text-[#f5f5f0] leading-none">{{ round($weatherNow['temp_c']) }}°<span class="text-sm font-normal text-[#5a6b5c] dark:text-[#a8b8a8]">C</span></div>
                            <div class="text-xs font-medium text-[#2f6f45] dark:text-[#8fd0ab] mt-1">{{ $weatherNow['condition'] }}</div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-right">
                        <div class="bg-white/60 dark:bg-black/20 rounded-lg p-2 flex flex-col justify-center">
                            <span class="text-[0.65rem] text-[#5a6b5c] dark:text-[#a8b8a8] block">Rain Chance</span>
                            <span class="text-xs font-bold text-[#2a6a8f] dark:text-[#6ea9c9]">{{ $weatherNow['chance_of_rain'] ?? 0 }}%</span>
                        </div>
                        <div class="bg-white/60 dark:bg-black/20 rounded-lg p-2 flex flex-col justify-center">
                            <span class="text-[0.65rem] text-[#5a6b5c] dark:text-[#a8b8a8] block">Humidity</span>
                            <span class="text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">{{ $weatherNow['humidity'] ?? 0 }}%</span>
                        </div>
                    </div>
                </div>

                @if (! empty($weatherForecast['days']))
                    <div class="flex border-b border-[#e5e9e6] dark:border-[#1a3d2a] bg-[#f8faf9] dark:bg-[#12281c]">
                        @foreach ($weatherForecast['days'] as $i => $day)
                            <button type="button" class="flex-1 py-2.5 px-2 text-center border-0 bg-transparent cursor-pointer transition-colors border-b-2 border-transparent hover:bg-black/5 dark:hover:bg-white/5 [&.is-active]:border-[var(--hp-green)] [&.is-active]:font-bold [&.is-active]:text-[var(--hp-green)] dark:[&.is-active]:text-[var(--hp-gold)] dark:[&.is-active]:border-[var(--hp-gold)] {{ $i === 0 ? 'is-active' : '' }}" data-weather-tab="{{ $i }}" aria-selected="{{ $i === 0 ? 'true' : 'false' }}">
                                <span class="block text-[0.7rem] uppercase tracking-wider text-[#5a6b5c] dark:text-[#a8b8a8]">{{ $day['day_label'] ?? $day['day_name'] ?? 'Day' }}</span>
                                <span class="text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">{{ round($day['max_temp_c'] ?? 0) }}° / {{ round($day['min_temp_c'] ?? 0) }}°</span>
                            </button>
                        @endforeach
                    </div>

                    @foreach ($weatherForecast['days'] as $i => $day)
                        <div class="p-3 {{ $i !== 0 ? 'hidden' : '' }}" data-weather-hours="{{ $i }}">
                            <div class="flex items-center justify-between mb-2 px-1">
                                <span class="text-[0.7rem] font-semibold text-[#5a6b5c] dark:text-[#a8b8a8]">{{ $day['condition'] ?? 'Clear' }}</span>
                                <span class="text-[0.7rem] font-medium text-[#2a6a8f] dark:text-[#6ea9c9]">🌧 {{ $day['chance_of_rain'] ?? 0 }}% rain</span>
                            </div>
                            <div class="flex gap-2 overflow-x-auto pb-1 pt-0.5 no-scrollbar">
                                @forelse (($day['hours'] ?? $day['hourly'] ?? []) as $hour)
                                    <div class="flex flex-col items-center gap-1 min-w-[3.25rem] py-1.5 px-1 rounded-lg bg-[#f4f7f5] dark:bg-[#12281c] border border-[#e5e9e6] dark:border-[#1a3d2a] shrink-0">
                                        <span class="text-[0.65rem] font-medium text-[#5a6b5c] dark:text-[#a8b8a8]">{{ $hour['time'] ?? $hour['time_label'] ?? '' }}</span>
                                        @if (! empty($hour['icon']))
                                            <img src="{{ $hour['icon'] }}" alt="{{ $hour['condition'] ?? 'Weather' }}" class="w-5 h-5 object-contain" onerror="this.style.display='none';">
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
                        <div class="notif-item flex items-start gap-3 p-3.5 transition-colors hover:bg-[#f4f7f5] dark:hover:bg-[#12281c] cursor-pointer" data-activity-id="{{ $act->id }}" data-activity-title="{{ $act->title }}" data-activity-desc="{{ $act->description }}" data-activity-type="{{ $act->activity_type }}" data-actor-name="{{ $act->actor_name }}" data-actor-role="{{ $act->actor_role }}" data-reservation-id="{{ $act->reservation_id }}" data-activity-time="{{ $act->created_at ? $act->created_at->format('l, F j, Y \a\t g:i A') : '' }}" data-activity-relative="{{ $act->created_at ? $act->created_at->diffForHumans() : 'Recently' }}">
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

                <!-- See all previous notifications footer button -->
                <div class="py-2.5 px-3 bg-[#f8faf9] dark:bg-[#12281c] border-t border-[#e5e9e6] dark:border-[#1a3d2a]">
                    <button type="button" id="openAllNotifsModalBtn" class="w-full flex items-center justify-center gap-1.5 py-1.5 px-3 text-[0.75rem] font-bold text-[#178a52] dark:text-[#8fd0ab] hover:bg-[#eaf5ee] dark:hover:bg-[#183525] rounded-lg transition-colors border border-[#c2e2ce]/60 dark:border-[#1e4e33] bg-white dark:bg-[#0d2116] cursor-pointer shadow-sm">
                        <span>See all previous notifications</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" class="w-3.5 h-3.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- ============================================================ -->
<!-- NOTIFICATION DETAIL MODAL                                    -->
<!-- ============================================================ -->
<div id="notifDetailModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-all duration-200" aria-hidden="true">
    <div class="relative w-full max-w-lg bg-white dark:bg-[#0d2116] border border-[#dbe3de] dark:border-[#1a3d2a] rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] overflow-hidden transform scale-95 opacity-0 transition-all duration-200 ease-out" id="notifDetailModalCard">
        <!-- Modal Header -->
        <div class="flex items-start justify-between p-5 border-b border-[#e5e9e6] dark:border-[#1a3d2a] bg-[#f8faf9] dark:bg-[#12281c]">
            <div class="flex items-center gap-3.5 min-w-0">
                <div id="notifDetailIcon" class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="min-w-0">
                    <span id="notifDetailTypeBadge" class="inline-block text-[0.68rem] font-extrabold uppercase px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 tracking-wider mb-1">Activity</span>
                    <h3 id="notifDetailTitle" class="m-0 text-base font-bold text-[#0d2c1d] dark:text-[#f5f5f0] leading-tight truncate">Notification Details</h3>
                </div>
            </div>
            <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center text-[#5a6b5c] hover:text-[#0d2c1d] dark:text-[#a8b8a8] dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/10 transition-colors border-0 bg-transparent cursor-pointer" data-close-notif-detail aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-5 space-y-4 max-h-[65vh] overflow-y-auto">
            <!-- Metadata Grid -->
            <div class="grid grid-cols-2 gap-3 p-3.5 rounded-xl bg-[#f4f7f5] dark:bg-[#091710] border border-[#e5e9e6] dark:border-[#183525] text-xs">
                <div>
                    <span class="block text-[0.7rem] font-semibold text-[#6e7c73] dark:text-[#7f9486] uppercase tracking-wider mb-0.5">Date & Time</span>
                    <span id="notifDetailTime" class="font-medium text-[#0d2c1d] dark:text-[#f5f5f0]">-</span>
                </div>
                <div>
                    <span class="block text-[0.7rem] font-semibold text-[#6e7c73] dark:text-[#7f9486] uppercase tracking-wider mb-0.5">Logged By</span>
                    <span id="notifDetailActor" class="font-medium text-[#0d2c1d] dark:text-[#f5f5f0]">-</span>
                </div>
                <div id="notifDetailResWrap" class="col-span-2 hidden">
                    <span class="block text-[0.7rem] font-semibold text-[#6e7c73] dark:text-[#7f9486] uppercase tracking-wider mb-0.5">Reservation Reference</span>
                    <span id="notifDetailRes" class="inline-flex items-center gap-1 font-bold text-[#178a52] dark:text-[#8fd0ab]">-</span>
                </div>
            </div>

            <!-- Description -->
            <div>
                <h4 class="m-0 text-xs font-bold text-[#6e7c73] dark:text-[#7f9486] uppercase tracking-wider mb-1.5">Activity Description</h4>
                <div id="notifDetailDesc" class="p-4 rounded-xl bg-white dark:bg-[#0d2116] border border-[#dbe3de] dark:border-[#1a3d2a] text-sm text-[#0d2c1d] dark:text-[#f5f5f0] leading-relaxed whitespace-pre-wrap">
                    -
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-end gap-2.5 p-4 border-t border-[#e5e9e6] dark:border-[#1a3d2a] bg-[#f8faf9] dark:bg-[#12281c]">
            <button type="button" class="py-2 px-4 text-xs font-semibold text-[#5a6b5c] dark:text-[#a8b8a8] hover:bg-black/5 dark:hover:bg-white/10 rounded-lg transition-colors border-0 bg-transparent cursor-pointer" data-close-notif-detail>Close</button>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ALL PREVIOUS NOTIFICATIONS MODAL (Search & Date Filters)      -->
<!-- ============================================================ -->
<div id="allNotifsModal" class="fixed inset-0 z-[190] hidden items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-all duration-200" aria-hidden="true">
    <div class="relative w-full max-w-3xl bg-white dark:bg-[#0d2116] border border-[#dbe3de] dark:border-[#1a3d2a] rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] overflow-hidden flex flex-col max-h-[88vh] transform scale-95 opacity-0 transition-all duration-200 ease-out" id="allNotifsModalCard">
        <!-- Modal Header -->
        <div class="flex items-center justify-between py-4 px-6 border-b border-[#e5e9e6] dark:border-[#1a3d2a] bg-[#f8faf9] dark:bg-[#12281c] shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <div>
                    <h3 class="m-0 text-base font-bold text-[#0d2c1d] dark:text-[#f5f5f0] leading-tight">All Activity Notifications</h3>
                    <p class="m-0 text-xs text-[#5a6b5c] dark:text-[#a8b8a8]">Search, filter, and review complete activity log history</p>
                </div>
            </div>
            <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center text-[#5a6b5c] hover:text-[#0d2c1d] dark:text-[#a8b8a8] dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/10 transition-colors border-0 bg-transparent cursor-pointer" data-close-all-notifs aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Filter Controls Bar -->
        <div class="p-4 bg-[#f4f7f5] dark:bg-[#0e2418] border-b border-[#e5e9e6] dark:border-[#1a3d2a] shrink-0 space-y-3">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <!-- Search Bar -->
                <div class="relative flex-1">
                    <svg class="w-4 h-4 text-[#6e7c73] dark:text-[#7f9486] absolute left-3 top-1/2 -translate-y-1/2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" id="allNotifsSearchInput" placeholder="Search title, details, staff name, reservation #..." class="w-full pl-9 pr-8 py-2 text-xs rounded-xl bg-white dark:bg-[#0d2116] border border-[#dbe3de] dark:border-[#1a3d2a] text-[#0d2c1d] dark:text-[#f5f5f0] placeholder-[#889b8a] dark:placeholder-[#6e7c73] focus:outline-none focus:ring-2 focus:ring-[#178a52] transition-all shadow-sm">
                    <button type="button" id="allNotifsSearchClear" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-[#889b8a] hover:text-[#0d2c1d] dark:hover:text-white border-0 bg-transparent cursor-pointer p-1">✕</button>
                </div>

                <!-- Activity Type Filter -->
                <select id="allNotifsTypeSelect" class="py-2 px-3 text-xs rounded-xl bg-white dark:bg-[#0d2116] border border-[#dbe3de] dark:border-[#1a3d2a] text-[#0d2c1d] dark:text-[#f5f5f0] focus:outline-none focus:ring-2 focus:ring-[#178a52] cursor-pointer shadow-sm">
                    <option value="all">All Activity Types</option>
                    <option value="check_in">Check-Ins</option>
                    <option value="check_out">Check-Outs</option>
                    <option value="amenities">Amenities & Extensions</option>
                    <option value="staff">Staff Actions</option>
                    <option value="rules">Rules & System</option>
                </select>

                <!-- Date Range Preset Filter -->
                <select id="allNotifsDatePreset" class="py-2 px-3 text-xs rounded-xl bg-white dark:bg-[#0d2116] border border-[#dbe3de] dark:border-[#1a3d2a] text-[#0d2c1d] dark:text-[#f5f5f0] focus:outline-none focus:ring-2 focus:ring-[#178a52] cursor-pointer shadow-sm">
                    <option value="all">All Time</option>
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="week">Last 7 Days</option>
                    <option value="month">Last 30 Days</option>
                    <option value="custom">Custom Date Range</option>
                </select>
            </div>

            <!-- Custom Date Inputs (shown only when 'custom' selected) -->
            <div id="allNotifsCustomDateWrap" class="hidden flex items-center gap-2 pt-1">
                <span class="text-[0.72rem] font-semibold text-[#5a6b5c] dark:text-[#a8b8a8]">From:</span>
                <input type="date" id="allNotifsStartDate" class="py-1.5 px-2.5 text-xs rounded-lg bg-white dark:bg-[#0d2116] border border-[#dbe3de] dark:border-[#1a3d2a] text-[#0d2c1d] dark:text-[#f5f5f0] shadow-sm">
                <span class="text-[0.72rem] font-semibold text-[#5a6b5c] dark:text-[#a8b8a8]">To:</span>
                <input type="date" id="allNotifsEndDate" class="py-1.5 px-2.5 text-xs rounded-lg bg-white dark:bg-[#0d2116] border border-[#dbe3de] dark:border-[#1a3d2a] text-[#0d2c1d] dark:text-[#f5f5f0] shadow-sm">
                <button type="button" id="allNotifsApplyCustomDate" class="py-1.5 px-3 text-xs font-semibold rounded-lg bg-[#178a52] text-white hover:bg-[#126e41] border-0 cursor-pointer transition-colors shadow-sm">Apply</button>
            </div>
        </div>

        <!-- Scrollable Notifications List -->
        <div class="flex-1 overflow-y-auto p-4 space-y-2.5 min-h-[320px] max-h-[50vh]" id="allNotifsListContainer">
            <!-- Populated dynamically via JS -->
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-between py-3 px-6 border-t border-[#e5e9e6] dark:border-[#1a3d2a] bg-[#f8faf9] dark:bg-[#12281c] shrink-0 text-xs">
            <span id="allNotifsCountLabel" class="text-[#5a6b5c] dark:text-[#a8b8a8] font-medium">Loading notifications…</span>
            <button type="button" class="py-2 px-4 text-xs font-semibold text-[#0d2c1d] dark:text-white bg-white dark:bg-[#0d2116] hover:bg-[#e8efe9] dark:hover:bg-[#183525] border border-[#dbe3de] dark:border-[#1a3d2a] rounded-lg transition-colors cursor-pointer" data-close-all-notifs>Close</button>
        </div>
    </div>
</div>

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
