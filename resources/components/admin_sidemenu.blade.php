@props(['active' => 'dashboard', 'userName' => 'Admin', 'userRole' => 'Admin'])

@php
    $links = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'url' => route('admin.dashboard'), 'icon' => 'grid'],
        ['key' => 'amenities', 'label' => 'Amenities', 'url' => route('admin.amenities'), 'icon' => 'map'],
        ['key' => 'reports', 'label' => 'Reports', 'url' => route('admin.reports'), 'icon' => 'chart'],
        ['key' => 'users', 'label' => 'Users', 'url' => route('admin.users'), 'icon' => 'users'],
    ];
@endphp

<aside class="fixed top-0 left-0 z-50 flex flex-col w-[10rem] h-screen bg-gradient-to-b from-[#244A2D] to-[#183722] dark:from-[#1a3323] dark:to-[#0d1f13] border-r border-white/12 dark:border-[#14281c] text-white shadow-[4px_0_20px_rgba(0,0,0,0.12)] overflow-visible transition-transform duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] -translate-x-full lg:translate-x-0 group/sidebar" id="dashSidebar">
    <!-- Logo Section -->
    <div class="relative z-10 flex justify-center px-4 pt-5 pb-2.5">
        <div class="w-[70px] h-[70px] rounded-full bg-white/10 border border-white/20 dark:border-[#1b3525] flex items-center justify-center shadow-[0_4px_15px_rgba(0,0,0,0.15)] overflow-hidden">
            <img src="{{ asset('storage/design_images/main_logo.jpeg') }}" onerror="console.error('Logo failed to load'); this.style.display='none';" alt="Hinaguan Nature Park Logo" class="w-full h-full object-cover">
        </div>
    </div>

    <!-- Separator Line -->
    <div class="relative z-10 h-px mx-5 mb-4 bg-gradient-to-r from-transparent via-white/20 dark:via-white/10 to-transparent"></div>

    <!-- Navigation -->
    <nav class="relative z-10 flex-1 flex flex-col px-4 py-2 min-h-0 overflow-y-auto" aria-label="Admin navigation">
        <ul class="list-none m-0 p-0 flex flex-col gap-1 h-full justify-between">
            @foreach ($links as $link)
                <li>
                    <a
                        href="{{ $link['url'] }}"
                        class="nav-link relative flex flex-col items-center justify-center py-3 px-2.5 rounded-[14px] text-white min-h-0 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] cursor-pointer overflow-hidden group/link hover:bg-white/10 dark:hover:bg-white/5 hover:scale-105 {{ $active === $link['key'] ? 'is-active' : '' }}"
                        data-page-transition
                    >
                        <span class="nav-icon w-8 h-8 flex items-center justify-center mb-1.5 shrink-0 rounded-[10px] bg-white/5 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] opacity-80 group-hover/link:opacity-100 group-hover/link:scale-110 group-hover/link:bg-white/15">
                            <span class="[&>svg]:w-6 [&>svg]:h-6 [&>svg]:fill-none [&>svg]:transition-all [&>svg]:duration-300 [&>svg]:ease-[cubic-bezier(0.34,1.56,0.64,1)] [&>svg]:stroke-white/80 [&>svg]:stroke-[1.5]">
                                @include('components.partials.sidemenu-icon', ['icon' => $link['icon']])
                            </span>
                        </span>
                        <span class="nav-label font-['Poppins','Inter',sans-serif] text-[11px] text-center leading-[1.3] shrink-0 tracking-[0.3px] transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] opacity-70 text-white/75 font-medium group-hover/link:text-white group-hover/link:font-semibold">{{ $link['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <!-- Separator Line -->
    <div class="relative z-10 h-px mx-5 mb-3 bg-gradient-to-r from-transparent via-white/20 dark:via-white/10 to-transparent"></div>

    <!-- Profile Section -->
    <div class="relative z-[5] py-3.5 px-2.5 shrink-0">
        <button type="button" class="w-full flex items-center gap-2.5 py-3 px-3.5 rounded-[10px] bg-white/8 border border-white/12 dark:border-[#14281c] cursor-pointer transition-all duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] relative z-[5] group/profile hover:bg-white/12 dark:hover:bg-white/5 hover:border-white/20 dark:hover:border-[#1b3525] hover:-translate-y-px" data-dash-user-toggle aria-label="User menu">
            <span class="w-9 h-9 rounded-lg bg-[#6E9F54] text-white flex items-center justify-center shrink-0 transition-all duration-300 group-hover/profile:bg-[#7BAE61]">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-[18px] h-[18px] text-white">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </span>
            <span class="flex-1 text-left min-w-0">
                <span class="block text-[13px] font-semibold text-white leading-[1.2] overflow-hidden text-ellipsis whitespace-nowrap">{{ $userName }}</span>
                <span class="block text-[10px] text-white/70 capitalize mt-0.5">{{ $userRole }}</span>
            </span>
            <span class="w-1.5 h-1.5 border-r-2 border-b-2 border-white/40 transform rotate-45 transition-all duration-300 mr-1 group-hover/profile:border-white/80 group-hover/profile:rotate-45 group-hover/profile:translate-x-0.5 group-hover/profile:translate-y-0.5"></span>
        </button>
        <div class="fixed left-[168px] bottom-4 -translate-x-2 translate-y-2 min-w-[160px] p-1.5 bg-white dark:bg-[#1a3d2a] rounded-[10px] shadow-[0_6px_20px_rgba(0,0,0,0.18)] dark:shadow-[0_6px_20px_rgba(0,0,0,0.35)] opacity-0 invisible transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] z-[1000] pointer-events-none border border-black/5 dark:border-[#1b3525] [&.is-open]:opacity-100 [&.is-open]:visible [&.is-open]:translate-x-0 [&.is-open]:translate-y-0 [&.is-open]:pointer-events-auto">
            <button type="button" class="flex items-center gap-2.5 w-full p-3 text-[#244A2D] dark:text-[#a9c98b] no-underline rounded-lg text-[13px] font-medium text-left transition-all duration-200 border-none bg-transparent cursor-pointer relative hover:bg-[#244A2D]/10 hover:translate-x-0.5 dark:hover:bg-[#a9c98b]/15 group/theme" data-theme-toggle aria-label="Toggle dark mode">
                <svg class="w-[18px] h-[18px] shrink-0 absolute left-3 transition-all duration-200 stroke-[#244A2D] dark:stroke-[#a9c98b] opacity-100 rotate-0 dark:opacity-0 dark:rotate-90" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <svg class="w-[18px] h-[18px] shrink-0 absolute left-3 transition-all duration-200 stroke-[#244A2D] dark:stroke-[#a9c98b] opacity-0 -rotate-90 dark:opacity-100 dark:rotate-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <span class="ml-7 dash-sidebar__theme-text">Dark Mode</span>
            </button>
            <a href="{{ route('admin.settings') }}" class="flex items-center gap-2 w-full py-2.5 px-3 text-[#244A2D] dark:text-[#a9c98b] no-underline rounded-md text-[12px] font-medium text-left transition-all duration-200 border-none bg-transparent cursor-pointer hover:bg-[#244A2D]/8 hover:translate-x-0.5 dark:hover:bg-[#a9c98b]/12">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" class="w-4 h-4 shrink-0 stroke-[#244A2D] dark:stroke-[#a9c98b]">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Settings</span>
            </a>
            <a href="{{ route('home') }}" class="flex items-center gap-2 w-full py-2.5 px-3 text-[#244A2D] dark:text-[#a9c98b] no-underline rounded-md text-[12px] font-medium text-left transition-all duration-200 border-none bg-transparent cursor-pointer hover:bg-[#244A2D]/8 hover:translate-x-0.5 dark:hover:bg-[#a9c98b]/12">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" class="w-4 h-4 shrink-0 stroke-[#244A2D] dark:stroke-[#a9c98b]">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Back to Website</span>
            </a>
            <button type="button" class="flex items-center gap-2 w-full py-2.5 px-3 text-[#dc2626] dark:text-[#f87171] no-underline rounded-md text-[12px] font-semibold text-left transition-all duration-200 border-none bg-transparent cursor-pointer hover:bg-[#dc2626]/8 hover:translate-x-0.5 dark:hover:bg-[#f87171]/12" data-logout-confirm>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" class="w-4 h-4 shrink-0 stroke-[#dc2626] dark:stroke-[#f87171]">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Sign out</span>
            </button>
        </div>
    </div>
</aside>

<div class="hidden fixed inset-0 z-[49] bg-[#05100a]/50 backdrop-blur-sm opacity-0 invisible transition-all duration-250 ease-in [&.is-open]:block [&.is-open]:opacity-100 [&.is-open]:visible" aria-hidden="true" data-sidebar-overlay></div>

<!-- Logout Confirmation Modal -->
<div class="hidden fixed inset-0 z-[2000] items-center justify-center p-5 [&.is-open]:flex" id="logoutModal" aria-hidden="true">
    <div class="absolute inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm" data-logout-cancel></div>
    <div class="relative bg-white/65 dark:bg-[#1a3d2a] backdrop-blur-md border border-white/50 dark:border-[#35483d] rounded-2xl shadow-[0_20px_60px_rgba(0,0,0,0.3)] dark:shadow-[0_20px_60px_rgba(0,0,0,0.5)] max-w-[400px] w-full overflow-hidden animate-[modalSlideIn_0.3s_cubic-bezier(0.34,1.56,0.64,1)]">
        <div class="py-5 px-6 pb-4 border-b border-black/10 dark:border-white/10">
            <h3 class="m-0 text-[18px] font-bold text-[#1a1a1a] dark:text-white">Confirm Logout</h3>
        </div>
        <div class="py-5 px-6">
            <p class="m-0 text-[14px] text-[#666] dark:text-white/80 leading-[1.5]">Are you sure you want to sign out of your account?</p>
        </div>
        <div class="flex gap-3 pt-4 px-6 pb-5 justify-end">
            <button type="button" class="py-2.5 px-5 rounded-lg text-[14px] font-semibold cursor-pointer transition-all duration-200 border-none bg-[#f3f4f6] dark:bg-white/10 text-[#374151] dark:text-white hover:bg-[#e5e7eb] dark:hover:bg-white/15" data-logout-cancel>Cancel</button>
            <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                @csrf
                <button type="submit" class="py-2.5 px-5 rounded-lg text-[14px] font-semibold cursor-pointer transition-all duration-200 border-none bg-[#6E9F54] text-white relative overflow-hidden hover:bg-[#244A2D] hover:-translate-y-[1px] hover:shadow-[0_4px_12px_rgba(110,159,84,0.3)] [&.is-loading]:pointer-events-none [&.is-loading]:opacity-80">
                    <span>Sign out</span>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
@keyframes modalSlideIn {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
/* Layout classes for sidebar integration.
   Tailwind v4 moves the sidebar via the native `translate` property
   (-translate-x-full / lg:translate-x-0), so the open/collapsed states
   must control that property — a transform override alone can't cancel it. */
.sidebar-collapsed #dashSidebar { translate: -100%; }
.sidebar-open #dashSidebar { translate: 0; }
@media (min-width: 1024px) {
    .sidebar-collapsed #dashSidebar { translate: -100%; }
}

/* Active sidebar link — applied server-side on full loads and toggled by
   sidemenu.js (updateActiveLink) after SPA navigation. ID-scoped so it always
   beats the Tailwind utilities on the same element.
   New premium design: green glass pill + gold left notch + glowing icon chip. */
#dashSidebar .nav-link.is-active {
    background: linear-gradient(135deg, rgba(110, 159, 84, 0.38) 0%, rgba(110, 159, 84, 0.14) 100%);
    box-shadow:
        inset 0 0 0 1px rgba(255, 255, 255, 0.16),
        0 6px 20px rgba(0, 0, 0, 0.22),
        0 0 26px rgba(110, 159, 84, 0.22);
    scale: 1.03;
}
html[data-theme="dark"] #dashSidebar .nav-link.is-active {
    background: linear-gradient(135deg, rgba(110, 159, 84, 0.34) 0%, rgba(110, 159, 84, 0.12) 100%);
    box-shadow:
        inset 0 0 0 1px rgba(255, 255, 255, 0.10),
        0 6px 20px rgba(0, 0, 0, 0.45),
        0 0 26px rgba(110, 159, 84, 0.16);
}

/* Gold accent notch that grows in on the left edge */
#dashSidebar .nav-link.is-active::before {
    content: "";
    position: absolute;
    left: 5px;
    top: 50%;
    translate: 0 -50%;
    width: 4px;
    height: 0;
    border-radius: 999px;
    background: linear-gradient(180deg, #eed08b 0%, #c8a45d 100%);
    box-shadow: 0 0 10px rgba(200, 164, 93, 0.85), 0 0 22px rgba(200, 164, 93, 0.35);
    animation: dashActiveNotch 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
@keyframes dashActiveNotch {
    from { height: 0; opacity: 0; }
    to { height: 52%; opacity: 1; }
}

/* Icon sits in a frosted chip with a soft glow */
#dashSidebar .nav-link.is-active .nav-icon {
    opacity: 1;
    background: rgba(255, 255, 255, 0.20);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.14), 0 2px 10px rgba(0, 0, 0, 0.18);
    scale: 1.08;
}
#dashSidebar .nav-link.is-active svg {
    stroke: #fff;
    stroke-width: 2;
    filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.3));
}
#dashSidebar .nav-link.is-active .nav-label {
    color: #fff;
    font-weight: 600;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.25);
}
</style>
