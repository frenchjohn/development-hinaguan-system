<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Settings — Hinaguan Nature Park</title>
    <script>
        // Prevent flash of wrong theme by setting theme immediately
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link rel="icon" type="image/jpeg" href="{{ asset('storage/design_images/main_logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700|poppins:300,400,500,600,700" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/css/homepage.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/staff_sidemenu.css',
        'resources/css/chatbot.css',
        'resources/css/staff_css/staff_shared.css',
        'resources/css/admin_css/admin_shared.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/admin_js/admin_settings.js',
        'resources/js/admin_chatbot.js',
    ])
    <style>
        body.admin-portal {
            background-color: #ebf3ec !important;
        }
        [data-theme="dark"] body.admin-portal {
            background-color: #0f1110 !important;
        }
        body.admin-portal .dash-layout,
        body.admin-portal .dash-main,
        body.admin-portal .dash-content {
            background: transparent !important;
            background-color: transparent !important;
            background-image: none !important;
        }
        body.admin-portal .dash-main {
            position: relative !important;
            min-height: 100vh;
            z-index: 0;
        }
        body.admin-portal .dash-main::before {
            content: '' !important;
            display: block !important;
            position: fixed !important;
            top: 0 !important;
            left: var(--dash-sidebar-w, 10rem) !important;
            right: 0 !important;
            bottom: 0 !important;
            width: auto !important;
            height: 100vh !important;
            z-index: -1 !important;
            pointer-events: none !important;
            background-color: #ebf3ec !important;
            background-image: linear-gradient(rgba(255, 255, 255, 0.4), rgba(255, 255, 255, 0.3)), url('{{ asset('storage/design_images/staff-admin-background-image.jpeg') }}') !important;
            background-size: 100% 100% !important;
            background-position: center center !important;
            background-repeat: no-repeat !important;
            filter: none !important;
            -webkit-filter: none !important;
            opacity: 1 !important;
            transition: left 0.25s ease !important;
        }
        .dash-layout.sidebar-collapsed .dash-main::before {
            left: 0 !important;
        }
        @media (max-width: 992px) {
            body.admin-portal .dash-main::before {
                left: 0 !important;
            }
        }
        [data-theme="dark"] body.admin-portal .dash-main::before {
            background-color: #0f1110 !important;
            background-image: linear-gradient(rgba(15, 17, 16, 0.94), rgba(15, 17, 16, 0.97)), url('{{ asset('storage/design_images/staff-admin-background-image.jpeg') }}') !important;
            filter: none !important;
            -webkit-filter: none !important;
            opacity: 1 !important;
        }
        body.admin-portal .dash-content {
            position: relative !important;
            z-index: 1 !important;
        }
        body.admin-portal [class*="backdrop-blur"] {
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
    </style>
</head>
<body class="antialiased admin-portal">
    <div class="dash-layout">
        <x-admin_sidemenu active="settings" userName="{{ session('auth_user.name') ?? 'Admin User' }}" userRole="Admin" />
        <div class="dash-main">
            <x-header title="Settings" subtitle="Manage park configuration, rules, and security" />
            <!-- Page transition overlay with skeleton loading -->
            <main class="dash-content p-6">
                <div class="admin-settings">
                    <!-- Horizontal Card Menu -->
                    <div class="admin-settings__menu" id="settingsMenu">
                        <div class="admin-settings__menu-card group relative flex cursor-pointer flex-col items-center gap-4 overflow-hidden rounded-2xl border border-[rgba(13,44,29,0.1)] bg-white p-8 text-center transition-all duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] before:absolute before:left-0 before:right-0 before:top-0 before:h-1 before:bg-[var(--hp-green)] before:content-[''] before:scale-x-0 before:transition-transform before:duration-300 hover:-translate-y-2 hover:border-[var(--hp-green)] hover:shadow-[0_20px_40px_rgba(13,44,29,0.15)] hover:before:scale-x-100 dark:border-white/10 dark:bg-white/5 dark:before:bg-[var(--hp-gold)] dark:hover:border-[var(--hp-gold)] dark:hover:shadow-[0_20px_40px_rgba(0,0,0,0.3)]" data-target="park-settings">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[rgba(26,58,31,0.1)] text-[var(--hp-green)] transition-all duration-300 group-hover:scale-110 group-hover:bg-[var(--hp-green)] group-hover:text-white dark:bg-[rgba(200,164,93,0.15)] dark:text-[var(--hp-gold)] dark:group-hover:bg-[var(--hp-gold)]">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-8 w-8">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <h3 class="admin-settings__menu-card__title m-0 text-[1.25rem] font-semibold text-[var(--hp-text)]">Park Settings</h3>
                            <p class="admin-settings__menu-card__text m-0 text-[0.875rem] leading-[1.5] text-[var(--hp-text-muted)]">Manage park configuration, hours, and entrance/pool fees</p>
                        </div>
                        <div class="admin-settings__menu-card group relative flex cursor-pointer flex-col items-center gap-4 overflow-hidden rounded-2xl border border-[rgba(13,44,29,0.1)] bg-white p-8 text-center transition-all duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] before:absolute before:left-0 before:right-0 before:top-0 before:h-1 before:bg-[var(--hp-green)] before:content-[''] before:scale-x-0 before:transition-transform before:duration-300 hover:-translate-y-2 hover:border-[var(--hp-green)] hover:shadow-[0_20px_40px_rgba(13,44,29,0.15)] hover:before:scale-x-100 dark:border-white/10 dark:bg-white/5 dark:before:bg-[var(--hp-gold)] dark:hover:border-[var(--hp-gold)] dark:hover:shadow-[0_20px_40px_rgba(0,0,0,0.3)]" data-target="park-rules">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[rgba(26,58,31,0.1)] text-[var(--hp-green)] transition-all duration-300 group-hover:scale-110 group-hover:bg-[var(--hp-green)] group-hover:text-white dark:bg-[rgba(200,164,93,0.15)] dark:text-[var(--hp-gold)] dark:group-hover:bg-[var(--hp-gold)]">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-8 w-8">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="admin-settings__menu-card__title m-0 text-[1.25rem] font-semibold text-[var(--hp-text)]">Park Rules</h3>
                            <p class="admin-settings__menu-card__text m-0 text-[0.875rem] leading-[1.5] text-[var(--hp-text-muted)]">Manage park guidelines, swimming attire, corkage, and regulations</p>
                        </div>
                        <div class="admin-settings__menu-card group relative flex cursor-pointer flex-col items-center gap-4 overflow-hidden rounded-2xl border border-[rgba(13,44,29,0.1)] bg-white p-8 text-center transition-all duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] before:absolute before:left-0 before:right-0 before:top-0 before:h-1 before:bg-[var(--hp-green)] before:content-[''] before:scale-x-0 before:transition-transform before:duration-300 hover:-translate-y-2 hover:border-[var(--hp-green)] hover:shadow-[0_20px_40px_rgba(13,44,29,0.15)] hover:before:scale-x-100 dark:border-white/10 dark:bg-white/5 dark:before:bg-[var(--hp-gold)] dark:hover:border-[var(--hp-gold)] dark:hover:shadow-[0_20px_40px_rgba(0,0,0,0.3)]" data-target="event-settings">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[rgba(26,58,31,0.1)] text-[var(--hp-green)] transition-all duration-300 group-hover:scale-110 group-hover:bg-[var(--hp-green)] group-hover:text-white dark:bg-[rgba(200,164,93,0.15)] dark:text-[var(--hp-gold)] dark:group-hover:bg-[var(--hp-gold)]">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-8 w-8">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <h3 class="admin-settings__menu-card__title m-0 text-[1.25rem] font-semibold text-[var(--hp-text)]">Event Settings</h3>
                            <p class="admin-settings__menu-card__text m-0 text-[0.875rem] leading-[1.5] text-[var(--hp-text-muted)]">Manage park events, schedule dates, what day, and descriptions</p>
                        </div>
                        <div class="admin-settings__menu-card group relative flex cursor-pointer flex-col items-center gap-4 overflow-hidden rounded-2xl border border-[rgba(13,44,29,0.1)] bg-white p-8 text-center transition-all duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] before:absolute before:left-0 before:right-0 before:top-0 before:h-1 before:bg-[var(--hp-green)] before:content-[''] before:scale-x-0 before:transition-transform before:duration-300 hover:-translate-y-2 hover:border-[var(--hp-green)] hover:shadow-[0_20px_40px_rgba(13,44,29,0.15)] hover:before:scale-x-100 dark:border-white/10 dark:bg-white/5 dark:before:bg-[var(--hp-gold)] dark:hover:border-[var(--hp-gold)] dark:hover:shadow-[0_20px_40px_rgba(0,0,0,0.3)]" data-target="park-activities">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[rgba(26,58,31,0.1)] text-[var(--hp-green)] transition-all duration-300 group-hover:scale-110 group-hover:bg-[var(--hp-green)] group-hover:text-white dark:bg-[rgba(200,164,93,0.15)] dark:text-[var(--hp-gold)] dark:group-hover:bg-[var(--hp-gold)]">
                                <i class="bi bi-compass text-3xl"></i>
                            </div>
                            <h3 class="admin-settings__menu-card__title m-0 text-[1.25rem] font-semibold text-[var(--hp-text)]">Park Activities</h3>
                            <p class="admin-settings__menu-card__text m-0 text-[0.875rem] leading-[1.5] text-[var(--hp-text-muted)]">Add, edit, and update activities shown on the homepage</p>
                        </div>
                        <div class="admin-settings__menu-card group relative flex cursor-pointer flex-col items-center gap-4 overflow-hidden rounded-2xl border border-[rgba(13,44,29,0.1)] bg-white p-8 text-center transition-all duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] before:absolute before:left-0 before:right-0 before:top-0 before:h-1 before:bg-[var(--hp-green)] before:content-[''] before:scale-x-0 before:transition-transform before:duration-300 hover:-translate-y-2 hover:border-[var(--hp-green)] hover:shadow-[0_20px_40px_rgba(13,44,29,0.15)] hover:before:scale-x-100 dark:border-white/10 dark:bg-white/5 dark:before:bg-[var(--hp-gold)] dark:hover:border-[var(--hp-gold)] dark:hover:shadow-[0_20px_40px_rgba(0,0,0,0.3)]" data-target="security">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-[rgba(26,58,31,0.1)] text-[var(--hp-green)] transition-all duration-300 group-hover:scale-110 group-hover:bg-[var(--hp-green)] group-hover:text-white dark:bg-[rgba(200,164,93,0.15)] dark:text-[var(--hp-gold)] dark:group-hover:bg-[var(--hp-gold)]">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-8 w-8">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <h3 class="admin-settings__menu-card__title m-0 text-[1.25rem] font-semibold text-[var(--hp-text)]">Security</h3>
                            <p class="admin-settings__menu-card__text m-0 text-[0.875rem] leading-[1.5] text-[var(--hp-text-muted)]">Update password and email settings</p>
                        </div>
                    </div>

                    @if(session('success'))
                        <div class="alert alert--success">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert--error">{{ session('error') }}</div>
                    @endif

                    <!-- Park Settings Content -->
                    <div class="admin-settings__content admin-settings__content--hidden" id="park-settings">
                        <button type="button" class="admin-settings__back-btn mb-4 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-3.5 py-2 text-xs font-semibold text-[var(--ink)] shadow-xs transition-colors hover:border-[var(--border-strong)] hover:bg-[var(--surface-2)]" id="backToMenu">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back to Settings
                        </button>
                        <section class="rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-5 sm:p-6 shadow-[var(--shadow-sm)]">
                            <div class="mb-5 flex flex-wrap items-center justify-between gap-4 border-b border-[var(--border)] pb-4">
                                <div>
                                    <h2 class="m-0 font-display text-lg font-bold text-[var(--ink)]">Park Configuration</h2>
                                    <p class="m-0 mt-0.5 text-xs text-[var(--ink-muted)]">Manage operational status, contact info, operating hours, and entrance fees.</p>
                                </div>
                                <button type="button" class="btn btn--primary inline-flex items-center gap-1.5" id="editParkSettingsBtn">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Edit
                                </button>
                            </div>
                            <form method="POST" action="{{ route('admin.settings.park.update') }}" id="parkSettingsForm" class="space-y-6">
                                @csrf

                                {{-- Operational Status --}}
                                <div class="space-y-3">
                                    <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-2)]/60 p-4">
                                        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                                            <div>
                                                <p class="m-0 text-sm font-semibold text-[var(--ink)]">Live Park Operational Status</p>
                                                <p class="m-0 mt-0.5 text-xs text-[var(--ink-muted)]">Set whether the park is currently open for visitors or temporarily closed.</p>
                                            </div>
                                            <div class="inline-flex items-center gap-3 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-3 py-1.5">
                                                <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                                    <input type="radio" name="park_status" value="open" id="park_status_open" class="h-4 w-4 accent-[#1b5e3a]" {{ ($parkSettings->park_status ?? 'open') === 'open' ? 'checked' : '' }} disabled>
                                                    <span>Park Open</span>
                                                </label>
                                                <span class="h-4 w-px bg-[var(--border)]"></span>
                                                <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs font-semibold text-rose-700 dark:text-rose-300">
                                                    <input type="radio" name="park_status" value="closed" id="park_status_closed" class="h-4 w-4 accent-rose-600" {{ ($parkSettings->park_status ?? 'open') === 'closed' ? 'checked' : '' }} disabled>
                                                    <span>Park Closed</span>
                                                </label>
                                            </div>
                                        </div>

                                        <div id="closeDescriptionWrapper" class="{{ ($parkSettings->park_status ?? 'open') === 'closed' ? '' : 'hidden' }} mt-3 border-t border-[var(--border)] pt-3">
                                            <label for="close_description" class="mb-1 block text-xs font-semibold text-rose-700 dark:text-rose-400">
                                                Closure Reason <span class="font-normal text-[var(--ink-muted)]">(Shown on hover in headers &amp; guest site)</span>
                                            </label>
                                            <textarea id="close_description" name="close_description" rows="2" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] disabled:opacity-60" placeholder="e.g., Temporarily closed for scheduled maintenance." disabled>{{ $parkSettings->close_description ?? '' }}</textarea>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-2)]/60 p-4">
                                        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                                            <div>
                                                <p class="m-0 text-sm font-semibold text-[var(--ink)]">Brenda's Park Availability</p>
                                                <p class="m-0 mt-0.5 text-xs text-[var(--ink-muted)]">Show whether Brenda Mage is currently in the park on the homepage.</p>
                                            </div>
                                            <div class="inline-flex items-center gap-3 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-3 py-1.5">
                                                <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                                    <input type="radio" name="brenda_available" value="1" id="brenda_available_yes" class="h-4 w-4 accent-[#1b5e3a]" {{ ($parkSettings->brenda_available ?? true) ? 'checked' : '' }} disabled>
                                                    <span>In the Park</span>
                                                </label>
                                                <span class="h-4 w-px bg-[var(--border)]"></span>
                                                <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs font-semibold text-amber-700 dark:text-amber-300">
                                                    <input type="radio" name="brenda_available" value="0" id="brenda_available_no" class="h-4 w-4 accent-amber-600" {{ !($parkSettings->brenda_available ?? true) ? 'checked' : '' }} disabled>
                                                    <span>Not in the Park</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Contact & Social --}}
                                <div class="border-t border-[var(--border)] pt-5">
                                    <h3 class="m-0 mb-3 text-xs font-bold uppercase tracking-wider text-[var(--ink-muted)]">Contact &amp; Links</h3>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <div>
                                            <label for="contact_number" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Contact Number</label>
                                            <input type="tel" id="contact_number" name="contact_number" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)] disabled:opacity-65" value="{{ $parkSettings->contact_number ?? '' }}" disabled>
                                        </div>
                                        <div>
                                            <label for="email" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Park Email</label>
                                            <input type="email" id="email" name="email" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)] disabled:opacity-65" value="{{ $parkSettings->email ?? '' }}" disabled>
                                        </div>
                                        <div>
                                            <label for="facebook_link" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Facebook Link</label>
                                            <input type="url" id="facebook_link" name="facebook_link" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)] disabled:opacity-65" value="{{ $parkSettings->facebook_link ?? '' }}" disabled>
                                        </div>
                                    </div>
                                </div>

                                {{-- Operating Hours --}}
                                <div class="border-t border-[var(--border)] pt-5">
                                    <h3 class="m-0 mb-3 text-xs font-bold uppercase tracking-wider text-[var(--ink-muted)]">Operating Hours</h3>
                                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                                        <div>
                                            <label for="daytime_start" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Daytime Start</label>
                                            <input type="time" id="daytime_start" name="daytime_start" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)] disabled:opacity-65" value="{{ $parkSettings->daytime_start ?? '08:01' }}" disabled>
                                        </div>
                                        <div>
                                            <label for="daytime_end" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Daytime End</label>
                                            <input type="time" id="daytime_end" name="daytime_end" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)] disabled:opacity-65" value="{{ $parkSettings->daytime_end ?? '18:00' }}" disabled>
                                        </div>
                                        <div>
                                            <label for="nighttime_start" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Nighttime Start</label>
                                            <input type="time" id="nighttime_start" name="nighttime_start" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)] disabled:opacity-65" value="{{ $parkSettings->nighttime_start ?? '18:01' }}" disabled>
                                        </div>
                                        <div>
                                            <label for="nighttime_end" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Nighttime End</label>
                                            <input type="time" id="nighttime_end" name="nighttime_end" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)] disabled:opacity-65" value="{{ $parkSettings->nighttime_end ?? '08:00' }}" disabled>
                                        </div>
                                    </div>
                                </div>

                                {{-- Entrance & Pool Fees --}}
                                <div class="border-t border-[var(--border)] pt-5">
                                    <h3 class="m-0 mb-3 text-xs font-bold uppercase tracking-wider text-[var(--ink-muted)]">Entrance &amp; Pool Fees</h3>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                        <div>
                                            <label for="daytime_adult_entrance_fee" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Daytime Adult Entrance (₱)</label>
                                            <input type="number" id="daytime_adult_entrance_fee" name="daytime_adult_entrance_fee" step="0.01" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)] disabled:opacity-65" value="{{ $parkSettings->daytime_adult_entrance_fee ?? 0 }}" disabled>
                                        </div>
                                        <div>
                                            <label for="daytime_child_entrance_fee" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Daytime Child Entrance (₱)</label>
                                            <input type="number" id="daytime_child_entrance_fee" name="daytime_child_entrance_fee" step="0.01" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)] disabled:opacity-65" value="{{ $parkSettings->daytime_child_entrance_fee ?? 0 }}" disabled>
                                        </div>
                                        <div>
                                            <label for="day_pool_fee" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Day Pool Fee (₱)</label>
                                            <input type="number" id="day_pool_fee" name="day_pool_fee" step="0.01" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)] disabled:opacity-65" value="{{ $parkSettings->day_pool_fee ?? 0 }}" disabled>
                                        </div>
                                        <div>
                                            <label for="nighttime_adult_entrance_fee" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Nighttime Adult Entrance (₱)</label>
                                            <input type="number" id="nighttime_adult_entrance_fee" name="nighttime_adult_entrance_fee" step="0.01" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)] disabled:opacity-65" value="{{ $parkSettings->nighttime_adult_entrance_fee ?? 0 }}" disabled>
                                        </div>
                                        <div>
                                            <label for="nighttime_child_entrance_fee" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Nighttime Child Entrance (₱)</label>
                                            <input type="number" id="nighttime_child_entrance_fee" name="nighttime_child_entrance_fee" step="0.01" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)] disabled:opacity-65" value="{{ $parkSettings->nighttime_child_entrance_fee ?? 0 }}" disabled>
                                        </div>
                                        <div>
                                            <label for="night_pool_fee" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Night Pool Fee (₱)</label>
                                            <input type="number" id="night_pool_fee" name="night_pool_fee" step="0.01" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)] disabled:opacity-65" value="{{ $parkSettings->night_pool_fee ?? 0 }}" disabled>
                                        </div>
                                    </div>
                                </div>

                                <div class="admin-settings__form-actions admin-settings__form-actions--hidden flex items-center justify-end gap-2.5 border-t border-[var(--border)] pt-4" id="parkSettingsFormActions">
                                    <button type="button" class="btn btn--ghost" id="cancelParkSettingsBtn">Cancel</button>
                                    <button type="submit" class="btn btn--primary">Save Changes</button>
                                </div>
                            </form>
                        </section>
                    </div>

                    <!-- Park Rules Content -->
                    <div class="admin-settings__content admin-settings__content--hidden" id="park-rules">
                        <button type="button" class="admin-settings__back-btn mb-4 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-3.5 py-2 text-xs font-semibold text-[var(--ink)] shadow-xs transition-colors hover:border-[var(--border-strong)] hover:bg-[var(--surface-2)]" id="backToMenuFromRules">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back to Settings
                        </button>

                        <section class="rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-5 sm:p-6 shadow-[var(--shadow-sm)]">
                            <div class="mb-5 flex flex-wrap items-center justify-between gap-4 border-b border-[var(--border)] pb-4">
                                <div>
                                    <h2 class="m-0 font-display text-lg font-bold text-[var(--ink)]">Park Rules &amp; Guidelines</h2>
                                    <p class="m-0 mt-0.5 text-xs text-[var(--ink-muted)]">Click any rule to view, edit, or remove it.</p>
                                </div>
                                <button type="button" class="btn btn--primary inline-flex items-center gap-1.5" id="addRuleBtn">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    Add Rule
                                </button>
                            </div>

                            <div class="park-rules-list grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" id="parkRulesGrid">
                                @forelse($parkRules as $rule)
                                    <div class="park-rule-item group relative flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--border)] bg-[var(--surface-2)]/50 px-4 py-3 transition-colors hover:border-emerald-500/50 hover:bg-[var(--surface)]"
                                         id="parkRuleCard_{{ $rule->id }}"
                                         data-rule-id="{{ $rule->id }}"
                                         data-rule-name="{{ $rule->rule_name }}"
                                         data-rule-desc="{{ $rule->rule_descriptions }}"
                                         data-rule-updated="{{ $rule->updated_at ? $rule->updated_at->diffForHumans() : 'Recently' }}">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/12 text-xs font-bold text-emerald-700 dark:text-emerald-300">
                                                #{{ $rule->id }}
                                            </span>
                                            <span class="truncate text-sm font-semibold text-[var(--ink)] rule-name-display">
                                                {{ $rule->rule_name }}
                                            </span>
                                        </div>
                                        <svg class="h-4 w-4 shrink-0 text-[var(--ink-muted)] transition-colors group-hover:text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </div>
                                @empty
                                    <div class="col-span-full py-10 text-center text-xs text-[var(--ink-muted)]" id="emptyParkRulesState">
                                        No park rules defined yet. Click "Add Rule" above to create one.
                                    </div>
                                @endforelse
                            </div>
                        </section>
                    </div>

                    <!-- Park Activities Content -->
                    <div class="admin-settings__content admin-settings__content--hidden" id="park-activities">
                        <button type="button" class="admin-settings__back-btn mb-4 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-3.5 py-2 text-xs font-semibold text-[var(--ink)] shadow-xs transition-colors hover:border-[var(--border-strong)] hover:bg-[var(--surface-2)]" id="backToMenuFromActivities">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back to Settings
                        </button>
                        <section class="rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-5 sm:p-6 shadow-[var(--shadow-sm)]">
                            <div class="mb-5 flex flex-wrap items-center justify-between gap-4 border-b border-[var(--border)] pb-4">
                                <div>
                                    <h2 class="m-0 font-display text-lg font-bold text-[var(--ink)]">Park Activities</h2>
                                    <p class="m-0 mt-0.5 text-xs text-[var(--ink-muted)]">Manage the activities and experiences displayed on the homepage.</p>
                                </div>
                                <button type="button" class="btn btn--primary inline-flex items-center gap-1.5" id="addActivityBtn">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    Add New Activity
                                </button>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2" id="parkActivitiesGrid">
                                @forelse($parkActivities as $activity)
                                    <article class="rounded-xl border border-[var(--border)] bg-[var(--surface-2)]/40 p-4 transition-colors hover:border-[var(--border-strong)]">
                                        <div class="flex gap-4">
                                            @if($activity->image)
                                                <img class="h-20 w-24 shrink-0 rounded-lg border border-[var(--border)] object-cover" src="{{ str_starts_with($activity->image, 'images/') ? asset($activity->image) : asset('storage/' . $activity->image) }}" alt="{{ $activity->activity }}">
                                            @else
                                                <div class="flex h-20 w-24 shrink-0 items-center justify-center rounded-lg border border-[var(--border)] bg-[var(--surface)] text-[var(--ink-muted)]">
                                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                                                </div>
                                            @endif
                                            <div class="min-w-0 flex-1">
                                                <h3 class="m-0 truncate text-sm font-bold text-[var(--ink)]">{{ $activity->activity }}</h3>
                                                <p class="m-0 mt-1 text-xs leading-relaxed text-[var(--ink-muted)]">{{ \Illuminate\Support\Str::limit($activity->description, 110) }}</p>
                                                <button type="button" class="btn btn--ghost mt-2.5 px-3 py-1.5 text-xs" data-edit-activity="{{ $activity->id }}">Edit Activity</button>
                                            </div>
                                        </div>
                                        <form method="POST" action="{{ route('admin.settings.activities.update', $activity) }}" enctype="multipart/form-data" class="mt-4 hidden border-t border-[var(--border)] pt-3" data-activity-form="{{ $activity->id }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="grid gap-3">
                                                <input class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface)] px-3 py-2 text-sm text-[var(--ink)] outline-none focus:border-[var(--green)]" name="activity" value="{{ $activity->activity }}" required>
                                                <textarea class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface)] px-3 py-2 text-sm text-[var(--ink)] outline-none focus:border-[var(--green)]" name="description" rows="3" required>{{ $activity->description }}</textarea>
                                                <input class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface)] px-3 py-2 text-xs text-[var(--ink)]" type="file" name="image" accept="image/*">
                                                <div class="flex justify-end gap-2">
                                                    <button type="button" class="btn btn--ghost px-3 py-1.5 text-xs" data-cancel-activity="{{ $activity->id }}">Cancel</button>
                                                    <button class="btn btn--primary px-3 py-1.5 text-xs">Save Changes</button>
                                                </div>
                                            </div>
                                        </form>
                                    </article>
                                @empty
                                    <p class="col-span-full py-10 text-center text-xs text-[var(--ink-muted)]">No activities yet. Add the first activity above.</p>
                                @endforelse
                            </div>
                        </section>
                    </div>

                    <!-- Event Settings Content -->
                    <div class="admin-settings__content admin-settings__content--hidden" id="event-settings">
                        <button type="button" class="admin-settings__back-btn mb-4 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-3.5 py-2 text-xs font-semibold text-[var(--ink)] shadow-xs transition-colors hover:border-[var(--border-strong)] hover:bg-[var(--surface-2)]" id="backToMenuFromEvents">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back to Settings
                        </button>

                        <section class="rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-5 sm:p-6 shadow-[var(--shadow-sm)]">
                            <div class="mb-5 flex flex-wrap items-center justify-between gap-4 border-b border-[var(--border)] pb-4">
                                <div>
                                    <h2 class="m-0 font-display text-lg font-bold text-[var(--ink)]">Park Events &amp; Experiences</h2>
                                    <p class="m-0 mt-0.5 text-xs text-[var(--ink-muted)]">Click any event to view details, edit, or remove it.</p>
                                </div>
                                <button type="button" class="btn btn--primary inline-flex items-center gap-1.5" id="addEventBtn">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    Add Event
                                </button>
                            </div>

                            <div class="park-events-list grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" id="parkEventsGrid">
                                @forelse($parkEvents as $event)
                                    <div class="park-event-item group relative flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[var(--border)] bg-[var(--surface-2)]/50 px-4 py-3 transition-colors hover:border-emerald-500/50 hover:bg-[var(--surface)]"
                                         id="parkEventCard_{{ $event->id }}"
                                         data-event-id="{{ $event->id }}"
                                         data-event-title="{{ $event->title }}"
                                         data-event-date="{{ $event->date ? $event->date->format('Y-m-d') : '' }}"
                                         data-event-day="{{ $event->day }}"
                                         data-event-time="{{ $event->time }}"
                                         data-event-desc="{{ $event->event }}"
                                         data-event-updated="{{ $event->updated_at ? $event->updated_at->diffForHumans() : 'Recently' }}">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <div class="flex w-11 shrink-0 flex-col items-center justify-center rounded-lg bg-emerald-500/12 py-1 text-emerald-700 dark:text-emerald-300">
                                                <span class="text-[10px] font-bold uppercase tracking-wider">{{ $event->date ? $event->date->format('M') : 'DATE' }}</span>
                                                <span class="text-sm font-extrabold leading-tight">{{ $event->date ? $event->date->format('d') : '--' }}</span>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="block truncate text-sm font-semibold text-[var(--ink)] event-title-display">
                                                    {{ $event->title }}
                                                </span>
                                                <span class="block truncate text-xs text-[var(--ink-muted)]">
                                                    {{ $event->day }}{{ $event->time ? ' · ' . $event->time : '' }} &middot; {{ Str::limit($event->event, 35) }}
                                                </span>
                                            </div>
                                        </div>
                                        <svg class="h-4 w-4 shrink-0 text-[var(--ink-muted)] transition-colors group-hover:text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </div>
                                @empty
                                    <div class="col-span-full py-10 text-center text-xs text-[var(--ink-muted)]" id="emptyParkEventsState">
                                        No park events created yet. Click "Add Event" above to create one.
                                    </div>
                                @endforelse
                            </div>
                        </section>
                    </div>

                    <!-- Security Content -->
                    <div class="admin-settings__content admin-settings__content--hidden" id="security">
                        <button type="button" class="admin-settings__back-btn mb-4 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-3.5 py-2 text-xs font-semibold text-[var(--ink)] shadow-xs transition-colors hover:border-[var(--border-strong)] hover:bg-[var(--surface-2)]" id="backToMenuFromSecurity">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back to Settings
                        </button>

                        <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                            <!-- Change Password Card -->
                            <section class="flex flex-col justify-between rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-5 sm:p-6 shadow-[var(--shadow-sm)]">
                                <div>
                                    <div class="mb-4 flex items-center gap-3.5 border-b border-[var(--border)] pb-4">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/12 text-emerald-700 dark:text-emerald-300">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        </div>
                                        <div>
                                            <h2 class="m-0 font-display text-base font-bold text-[var(--ink)]">Password</h2>
                                            <p class="m-0 text-xs text-[var(--ink-muted)]">Update your account password with OTP verification</p>
                                        </div>
                                    </div>
                                    <p class="m-0 mb-4 text-xs leading-relaxed text-[var(--ink-muted)]">
                                        Keep your administrator account secure by updating your password periodically. An OTP code will be sent to your email before changes apply.
                                    </p>
                                </div>
                                <div>
                                    <button type="button" class="btn btn--primary" id="togglePasswordBtn">
                                        Change Password
                                    </button>
                                </div>
                            </section>

                            <!-- Change Email Card -->
                            <section class="flex flex-col justify-between rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-5 sm:p-6 shadow-[var(--shadow-sm)]">
                                <div>
                                    <div class="mb-4 flex items-center gap-3.5 border-b border-[var(--border)] pb-4">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/12 text-emerald-700 dark:text-emerald-300">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        </div>
                                        <div>
                                            <h2 class="m-0 font-display text-base font-bold text-[var(--ink)]">Email</h2>
                                            <p class="m-0 text-xs text-[var(--ink-muted)]">Update your administrator email address</p>
                                        </div>
                                    </div>
                                    <div class="mb-4 rounded-lg border border-[var(--border)] bg-[var(--surface-2)]/60 px-3.5 py-2.5">
                                        <p class="m-0 text-[11px] font-semibold uppercase tracking-wide text-[var(--ink-muted)]">Current Email</p>
                                        <p class="m-0 mt-0.5 text-sm font-semibold text-[var(--ink)]" id="currentEmailDisplay">{{ session('auth_user')['email'] ?? 'Not set' }}</p>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn--primary" id="toggleEmailBtn">
                                        Change Email
                                    </button>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- ==================== ADMIN SETTINGS MODALS (Placed outside dash-layout to prevent header overlay) ==================== -->

    <!-- Park Settings Success Modal -->
    <div id="parkSettingsSuccessModal" class="admin-settings__modal" style="display: none;">
        <div class="admin-settings__modal-content admin-settings__modal-content--success">
            <div class="admin-settings__modal-icon admin-settings__modal-icon--success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="admin-settings__modal-title">Success!</h3>
            <p class="admin-settings__modal-text">Park settings have been successfully updated.</p>
            <button type="button" id="closeParkSettingsSuccessModal" class="admin-settings__btn admin-settings__btn--primary">OK</button>
        </div>
    </div>

    <!-- View Park Rule Modal -->
    <div id="viewParkRuleModal" class="admin-settings__modal" style="display: none;">
        <div class="admin-settings__modal-content max-w-lg text-left">
            <div class="flex items-start justify-between gap-4 border-b border-[rgba(13,44,29,0.1)] pb-4 dark:border-white/10">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[rgba(26,58,31,0.1)] text-xs font-bold text-[var(--hp-green)] dark:bg-[rgba(200,164,93,0.15)] dark:text-[var(--hp-gold)]" id="viewRuleIdBadge">
                        #1
                    </span>
                    <div>
                        <h3 class="m-0 text-lg font-bold text-[var(--hp-text)]" id="viewRuleModalTitle">Rule Name</h3>
                        <p class="m-0 text-xs text-[var(--hp-text-muted)]" id="viewRuleModalUpdated">Updated recently</p>
                    </div>
                </div>
                <button type="button" id="closeViewRuleModalXBtn" class="text-[var(--hp-text-muted)] hover:text-[var(--hp-text)] transition">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="mt-4">
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--hp-text-muted)] mb-1.5">Rule Description &amp; Policy</label>
                <div class="rounded-xl border border-[rgba(13,44,29,0.08)] bg-[rgba(13,44,29,0.02)] p-4 text-sm leading-relaxed text-[var(--hp-text)] dark:border-white/10 dark:bg-white/5 whitespace-pre-line max-h-60 overflow-y-auto" id="viewRuleModalDesc">
                    Rule description goes here...
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-[rgba(13,44,29,0.1)] pt-4 dark:border-white/10">
                <button type="button" id="viewModalDeleteBtn" class="inline-flex items-center gap-1.5 rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-600 hover:text-white dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-600 dark:hover:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete Rule
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" id="viewModalEditBtn" class="admin-settings__btn admin-settings__btn--primary text-sm px-4 py-2">
                        <svg class="h-4 w-4 mr-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit Rule
                    </button>
                    <button type="button" id="closeViewRuleModalBtn" class="admin-settings__btn admin-settings__btn--secondary text-sm px-4 py-2">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add / Edit Park Rule Modal -->
    <div id="parkRuleModal" class="admin-settings__modal !items-center !justify-center !p-4" style="display: none;">
        <div class="relative z-10 w-full max-w-[500px] overflow-hidden rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] shadow-xl text-left">
            <div class="flex items-center justify-between border-b border-[var(--border)] px-6 py-4">
                <div>
                    <h3 class="m-0 font-display text-lg font-bold text-[var(--ink)]" id="parkRuleModalTitle">Add Park Rule</h3>
                    <p class="m-0 mt-0.5 text-xs text-[var(--ink-muted)]" id="parkRuleModalSubtitle">Create a new operational rule or guideline for Hinaguan Nature Park.</p>
                </div>
                <button type="button" onclick="document.getElementById('cancelRuleModalBtn')?.click()" aria-label="Close" class="flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg border-0 bg-transparent text-[var(--ink-muted)] transition-colors hover:bg-[var(--surface-2)] hover:text-[var(--ink)]">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="parkRuleForm" class="text-left">
                @csrf
                <input type="hidden" id="ruleIdInput" name="rule_id" value="">

                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label for="ruleNameInput" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Rule Name / Title <span class="text-red-500">*</span></label>
                        <input type="text" id="ruleNameInput" name="rule_name" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]" placeholder="e.g. Proper Swimming Attire" required>
                        <span class="admin-settings__error mt-1 block text-xs text-red-600" id="ruleNameError"></span>
                    </div>

                    <div>
                        <label for="ruleDescInput" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Rule Description / Guidelines <span class="text-red-500">*</span></label>
                        <textarea id="ruleDescInput" name="rule_descriptions" rows="4" class="w-full resize-y rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]" placeholder="Describe the policy, requirements, or restrictions..." required></textarea>
                        <span class="admin-settings__error mt-1 block text-xs text-red-600" id="ruleDescError"></span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 border-t border-[var(--border)] bg-[var(--surface-2)] px-6 py-3.5">
                    <button type="button" class="btn btn--ghost" id="cancelRuleModalBtn">Cancel</button>
                    <button type="submit" class="btn btn--primary" id="saveRuleSubmitBtn">Save Rule</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Rule Confirmation Modal -->
    <div id="deleteParkRuleModal" class="admin-settings__modal" style="display: none;">
        <div class="admin-settings__modal-content max-w-md text-center">
            <div class="admin-settings__modal-icon mb-4 mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </div>
            <h3 class="admin-settings__modal-title text-xl font-bold text-[var(--hp-text)]">Delete Park Rule?</h3>
            <p class="admin-settings__modal-text mt-2 text-sm text-[var(--hp-text-muted)]" id="deleteRuleConfirmText">Are you sure you want to delete this rule? This action cannot be undone.</p>
            
            <input type="hidden" id="deleteRuleIdInput" value="">

            <div class="admin-settings__modal-actions mt-6 flex justify-center gap-3">
                <button type="button" id="confirmDeleteRuleBtn" class="admin-settings__btn bg-red-600 hover:bg-red-700 text-white font-medium px-6 py-2.5 rounded-xl transition">Delete Rule</button>
                <button type="button" id="cancelDeleteRuleBtn" class="admin-settings__btn admin-settings__btn--secondary">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Add Activity Modal -->
    <div id="addActivityModal" class="fixed inset-0 z-[2000] hidden items-center justify-center overflow-y-auto bg-black/60 p-4" role="dialog" aria-modal="true" aria-labelledby="addActivityModalTitle">
        <div class="relative z-[2001] w-full max-w-3xl rounded-2xl bg-white p-6 text-left shadow-2xl dark:border dark:border-white/10 dark:bg-[#181b19] sm:p-8">
            <div class="flex items-start justify-between gap-4 border-b border-[rgba(13,44,29,0.1)] pb-4 dark:border-white/10">
                <div>
                    <h3 id="addActivityModalTitle" class="m-0 text-xl font-bold text-[var(--hp-text)]">Add New Activity</h3>
                    <p class="m-0 mt-1 text-sm text-[var(--hp-text-muted)]">Add an experience visitors can discover on the homepage.</p>
                </div>
                <button type="button" class="text-xl text-[var(--hp-text-muted)] transition hover:text-[var(--hp-text)]" id="closeAddActivityXBtn" aria-label="Close add activity dialog"><i class="bi bi-x-lg"></i></button>
            </div>
            <form method="POST" action="{{ route('admin.settings.activities.store') }}" enctype="multipart/form-data" class="mt-5 grid gap-6 text-left lg:grid-cols-2">
                @csrf
                <div class="grid content-start gap-4">
                    <label class="grid gap-1.5 text-sm font-semibold text-[var(--hp-text)]">Activity name<input class="admin-settings__input" name="activity" placeholder="Activity name" required></label>
                    <label class="grid gap-1.5 text-sm font-semibold text-[var(--hp-text)]">Description<textarea class="admin-settings__input h-auto" name="description" rows="7" placeholder="Full activity description" required></textarea></label>
                    <div class="flex justify-end gap-2 pt-1 lg:justify-start"><button type="button" class="admin-settings__btn admin-settings__btn--secondary" id="cancelActivityBtn">Cancel</button><button class="admin-settings__btn admin-settings__btn--primary">Add Activity</button></div>
                </div>
                <div class="grid content-start gap-3 rounded-2xl border border-dashed border-[rgba(13,44,29,0.2)] bg-[rgba(13,44,29,0.025)] p-4 dark:border-white/15 dark:bg-white/5">
                    <div>
                        <label class="text-sm font-semibold text-[var(--hp-text)]" for="activityImageInput">Activity image</label>
                        <p class="m-0 mt-1 text-xs text-[var(--hp-text-muted)]">JPG, PNG, WEBP, or AVIF up to 5 MB.</p>
                    </div>
                    <label class="flex min-h-48 cursor-pointer items-center justify-center overflow-hidden rounded-xl border border-[rgba(13,44,29,0.1)] bg-white text-sm text-[var(--hp-text-muted)] transition hover:border-[var(--hp-green)] dark:border-white/10 dark:bg-[#111512]" for="activityImageInput">
                        <span id="activityImagePlaceholder" class="flex flex-col items-center gap-2"><i class="bi bi-cloud-arrow-up text-3xl text-[var(--hp-green)]"></i><span>Choose an image</span></span>
                        <img id="activityImagePreview" class="hidden h-48 max-h-48 w-full object-cover" alt="Selected activity preview">
                    </label>
                    <input class="sr-only" id="activityImageInput" type="file" name="image" accept="image/jpeg,image/png,image/webp,image/avif">
                </div>
            </form>
        </div>
    </div>

    <!-- View Park Event Modal -->
    <div id="viewParkEventModal" class="admin-settings__modal" style="display: none;">
        <div class="admin-settings__modal-content max-w-lg text-left">
            <div class="flex items-start justify-between gap-4 border-b border-[rgba(13,44,29,0.1)] pb-4 dark:border-white/10">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[rgba(26,58,31,0.1)] text-xs font-bold text-[var(--hp-green)] dark:bg-[rgba(200,164,93,0.15)] dark:text-[var(--hp-gold)]" id="viewEventIdBadge">
                        #1
                    </span>
                    <div>
                        <h3 class="m-0 text-lg font-bold text-[var(--hp-text)]" id="viewEventModalTitle">Event Title</h3>
                        <p class="m-0 text-xs text-[var(--hp-text-muted)]" id="viewEventModalMeta">Date &middot; Day</p>
                    </div>
                </div>
                <button type="button" id="closeViewEventModalXBtn" class="text-[var(--hp-text-muted)] hover:text-[var(--hp-text)] transition p-1">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="mt-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                    <div class="rounded-xl border border-[rgba(13,44,29,0.08)] bg-[rgba(13,44,29,0.02)] p-3 dark:border-white/10 dark:bg-white/5">
                        <span class="block text-[0.7rem] uppercase tracking-wider text-[var(--hp-text-muted)] font-bold">Event Date</span>
                        <span class="text-sm font-semibold text-[var(--hp-text)]" id="viewEventModalDateDisplay">--</span>
                    </div>
                    <div class="rounded-xl border border-[rgba(13,44,29,0.08)] bg-[rgba(13,44,29,0.02)] p-3 dark:border-white/10 dark:bg-white/5">
                        <span class="block text-[0.7rem] uppercase tracking-wider text-[var(--hp-text-muted)] font-bold">What Day</span>
                        <span class="text-sm font-semibold text-[var(--hp-text)]" id="viewEventModalDayDisplay">--</span>
                    </div>
                    <div class="rounded-xl border border-[rgba(13,44,29,0.08)] bg-[rgba(13,44,29,0.02)] p-3 dark:border-white/10 dark:bg-white/5">
                        <span class="block text-[0.7rem] uppercase tracking-wider text-[var(--hp-text-muted)] font-bold">Time</span>
                        <span class="text-sm font-semibold text-[var(--hp-text)]" id="viewEventModalTimeDisplay">--</span>
                    </div>
                </div>

                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--hp-text-muted)] mb-1.5">Event Description</label>
                <div class="rounded-xl border border-[rgba(13,44,29,0.08)] bg-[rgba(13,44,29,0.02)] p-4 text-sm leading-relaxed text-[var(--hp-text)] dark:border-white/10 dark:bg-white/5 whitespace-pre-line max-h-60 overflow-y-auto" id="viewEventModalDesc">
                    Event description goes here...
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-[rgba(13,44,29,0.1)] pt-4 dark:border-white/10">
                <button type="button" id="viewEventModalDeleteBtn" class="inline-flex items-center gap-1.5 rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-600 hover:text-white dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-600 dark:hover:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete Event
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" id="viewEventModalEditBtn" class="admin-settings__btn admin-settings__btn--primary text-sm px-4 py-2">
                        <svg class="h-4 w-4 mr-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit Event
                    </button>
                    <button type="button" id="closeViewEventModalBtn" class="admin-settings__btn admin-settings__btn--secondary text-sm px-4 py-2">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add / Edit Park Event Modal -->
    <div id="parkEventModal" class="admin-settings__modal !items-center !justify-center !p-4" style="display: none;">
        <div class="relative z-10 w-full max-w-[560px] overflow-hidden rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] shadow-xl text-left">
            <div class="flex items-center justify-between border-b border-[var(--border)] px-6 py-4">
                <div>
                    <h3 class="m-0 font-display text-lg font-bold text-[var(--ink)]" id="parkEventModalTitle">Add Park Event</h3>
                    <p class="m-0 mt-0.5 text-xs text-[var(--ink-muted)]" id="parkEventModalSubtitle">Schedule a new event for Hinaguan Nature Park.</p>
                </div>
                <button type="button" id="closeAddEventModalXBtn" aria-label="Close" class="flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg border-0 bg-transparent text-[var(--ink-muted)] transition-colors hover:bg-[var(--surface-2)] hover:text-[var(--ink)]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="parkEventForm" class="text-left">
                @csrf
                <input type="hidden" id="eventIdInput" name="event_id" value="">

                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label for="eventTitleInput" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Event Title <span class="text-red-500">*</span></label>
                        <input type="text" id="eventTitleInput" name="title" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]" placeholder="e.g. Riverside Acoustic Sunset Sessions" required maxlength="255">
                        <span class="admin-settings__error mt-1 block text-xs text-red-600" id="eventTitleError"></span>
                    </div>

                    <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-3">
                        <div>
                            <label for="eventDateInput" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Date <span class="text-red-500">*</span></label>
                            <input type="date" id="eventDateInput" name="date" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]" required>
                            <span class="admin-settings__error mt-1 block text-xs text-red-600" id="eventDateError"></span>
                        </div>
                        <div>
                            <label for="eventDayInput" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">What Day</label>
                            <input type="text" id="eventDayInput" name="day" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]" placeholder="Auto from date">
                            <span class="admin-settings__error mt-1 block text-xs text-red-600" id="eventDayError"></span>
                        </div>
                        <div>
                            <label for="eventTimeInput" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Time</label>
                            <input type="text" id="eventTimeInput" name="time" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]" placeholder="e.g. 4:00 PM - 8:00 PM" maxlength="100">
                            <span class="admin-settings__error mt-1 block text-xs text-red-600" id="eventTimeError"></span>
                        </div>
                    </div>

                    <div>
                        <label for="eventDescInput" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Event Description <span class="text-red-500">*</span></label>
                        <textarea id="eventDescInput" name="event" rows="3" class="w-full resize-y rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]" placeholder="Describe what event or activity is taking place..." required maxlength="2000"></textarea>
                        <span class="admin-settings__error mt-1 block text-xs text-red-600" id="eventDescError"></span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 border-t border-[var(--border)] bg-[var(--surface-2)] px-6 py-3.5">
                    <button type="button" id="cancelEventModalBtn" class="btn btn--ghost">Cancel</button>
                    <button type="submit" id="saveEventSubmitBtn" class="btn btn--primary">Save Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Event Confirmation Modal -->
    <div id="deleteParkEventModal" class="admin-settings__modal" style="display: none;">
        <div class="admin-settings__modal-content max-w-md text-center">
            <div class="admin-settings__modal-icon mb-4 mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </div>
            <h3 class="admin-settings__modal-title text-xl font-bold text-[var(--hp-text)]">Delete Park Event?</h3>
            <p class="admin-settings__modal-text mt-2 text-sm text-[var(--hp-text-muted)]" id="deleteEventConfirmText">Are you sure you want to delete this event? This action cannot be undone.</p>
            
            <input type="hidden" id="deleteEventIdInput" value="">

            <div class="admin-settings__modal-actions mt-6 flex justify-center gap-3">
                <button type="button" id="confirmDeleteEventBtn" class="admin-settings__btn bg-red-600 hover:bg-red-700 text-white font-medium px-6 py-2.5 rounded-xl transition">Delete Event</button>
                <button type="button" id="cancelDeleteEventBtn" class="admin-settings__btn admin-settings__btn--secondary">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" aria-hidden="true" class="admin-settings__modal !items-center !justify-center !p-4" style="display: none;">
        <div class="relative z-10 w-full max-w-[460px] overflow-hidden rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] shadow-xl text-left">
            <div class="flex items-center justify-between border-b border-[var(--border)] px-6 py-4">
                <div>
                    <h3 class="m-0 font-display text-lg font-bold text-[var(--ink)]">Change Password</h3>
                    <p class="m-0 mt-0.5 text-xs text-[var(--ink-muted)]">An OTP code will be sent to verify this change.</p>
                </div>
                <button type="button" id="closePasswordModalXBtn" aria-label="Close" class="flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg border-0 bg-transparent text-[var(--ink-muted)] transition-colors hover:bg-[var(--surface-2)] hover:text-[var(--ink)]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form id="changePasswordForm" class="text-left">
                @csrf
                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label for="currentPassword" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Current Password <span class="text-red-500">*</span></label>
                        <input type="password" id="currentPassword" name="current_password" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]" required>
                        <span class="admin-settings__error mt-1 block text-xs text-red-600" id="currentPasswordError"></span>
                    </div>
                    <div>
                        <label for="newPassword" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">New Password <span class="text-red-500">*</span></label>
                        <input type="password" id="newPassword" name="new_password" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]" required>
                        <span class="admin-settings__error mt-1 block text-xs text-red-600" id="newPasswordError"></span>
                    </div>
                    <div>
                        <label for="confirmPassword" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Confirm Password <span class="text-red-500">*</span></label>
                        <input type="password" id="confirmPassword" name="confirm_password" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]" required>
                        <span class="admin-settings__error mt-1 block text-xs text-red-600" id="confirmPasswordError"></span>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2.5 border-t border-[var(--border)] bg-[var(--surface-2)] px-6 py-3.5">
                    <button type="button" id="cancelPasswordBtn" class="!m-0 inline-flex h-9 cursor-pointer items-center justify-center rounded-lg border border-[var(--border-strong)] bg-[var(--surface)] px-4 text-xs font-semibold text-[var(--ink)] transition-colors hover:bg-[var(--surface-2)]">Cancel</button>
                    <button type="submit" class="!m-0 inline-flex h-9 cursor-pointer items-center justify-center rounded-lg border-0 bg-[#1b5e3a] px-4 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-[#14472c]">Send OTP Code</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Email Modal -->
    <div id="changeEmailModal" aria-hidden="true" class="admin-settings__modal !items-center !justify-center !p-4" style="display: none;">
        <div class="relative z-10 w-full max-w-[460px] overflow-hidden rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] shadow-xl text-left">
            <div class="flex items-center justify-between border-b border-[var(--border)] px-6 py-4">
                <div>
                    <h3 class="m-0 font-display text-lg font-bold text-[var(--ink)]">Change Email Address</h3>
                    <p class="m-0 mt-0.5 text-xs text-[var(--ink-muted)]">You will receive an OTP code on your current email.</p>
                </div>
                <button type="button" id="closeEmailModalXBtn" aria-label="Close" class="flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg border-0 bg-transparent text-[var(--ink-muted)] transition-colors hover:bg-[var(--surface-2)] hover:text-[var(--ink)]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form id="changeEmailForm" class="text-left">
                @csrf
                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label for="newEmail" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">New Email Address <span class="text-red-500">*</span></label>
                        <input type="email" id="newEmail" name="new_email" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]" required>
                        <span class="admin-settings__error mt-1 block text-xs text-red-600" id="newEmailError"></span>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2.5 border-t border-[var(--border)] bg-[var(--surface-2)] px-6 py-3.5">
                    <button type="button" id="cancelEmailBtn" class="!m-0 inline-flex h-9 cursor-pointer items-center justify-center rounded-lg border border-[var(--border-strong)] bg-[var(--surface)] px-4 text-xs font-semibold text-[var(--ink)] transition-colors hover:bg-[var(--surface-2)]">Cancel</button>
                    <button type="submit" class="!m-0 inline-flex h-9 cursor-pointer items-center justify-center rounded-lg border-0 bg-[#1b5e3a] px-4 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-[#14472c]">Send OTP Code</button>
                </div>
            </form>
        </div>
    </div>

    <!-- OTP Verification Modal for Password -->
    <div id="otpPasswordModal" class="admin-settings__modal !items-center !justify-center !p-4" style="display: none;">
        <div class="relative z-10 w-full max-w-[420px] overflow-hidden rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] shadow-xl text-left">
            <div class="border-b border-[var(--border)] px-6 py-4">
                <h3 class="m-0 font-display text-lg font-bold text-[var(--ink)]">Verify OTP Code</h3>
                <p class="m-0 mt-0.5 text-xs text-[var(--ink-muted)]">An OTP code has been sent to your email address.</p>
            </div>
            <div class="px-6 py-5">
                <input type="text" id="otpPasswordCode" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2.5 text-center text-base font-bold tracking-[0.35em] text-[var(--ink)] outline-none focus:border-[var(--green)] focus:bg-[var(--surface)]" placeholder="000000" maxlength="6">
                <span class="admin-settings__error mt-1.5 block text-xs text-red-600" id="otpPasswordError"></span>
            </div>
            <div class="flex items-center justify-end gap-2.5 border-t border-[var(--border)] bg-[var(--surface-2)] px-6 py-3.5">
                <button type="button" id="cancelPasswordOtpBtn" class="!m-0 inline-flex h-9 cursor-pointer items-center justify-center rounded-lg border border-[var(--border-strong)] bg-[var(--surface)] px-4 text-xs font-semibold text-[var(--ink)] transition-colors hover:bg-[var(--surface-2)]">Cancel</button>
                <button type="button" id="verifyPasswordOtpBtn" class="!m-0 inline-flex h-9 cursor-pointer items-center justify-center rounded-lg border-0 bg-[#1b5e3a] px-4 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-[#14472c]">Verify &amp; Change Password</button>
            </div>
        </div>
    </div>

    <!-- OTP Verification Modal for Email -->
    <div id="otpEmailModal" class="admin-settings__modal !items-center !justify-center !p-4" style="display: none;">
        <div class="relative z-10 w-full max-w-[420px] overflow-hidden rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] shadow-xl text-left">
            <div class="border-b border-[var(--border)] px-6 py-4">
                <h3 class="m-0 font-display text-lg font-bold text-[var(--ink)]">Verify Email Change</h3>
                <p class="m-0 mt-0.5 text-xs text-[var(--ink-muted)]">An OTP code has been sent to your current email address.</p>
            </div>
            <div class="px-6 py-5">
                <input type="text" id="otpEmailCode" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2.5 text-center text-base font-bold tracking-[0.35em] text-[var(--ink)] outline-none focus:border-[var(--green)] focus:bg-[var(--surface)]" placeholder="000000" maxlength="6">
                <span class="admin-settings__error mt-1.5 block text-xs text-red-600" id="otpEmailError"></span>
            </div>
            <div class="flex items-center justify-end gap-2.5 border-t border-[var(--border)] bg-[var(--surface-2)] px-6 py-3.5">
                <button type="button" id="cancelEmailOtpBtn" class="!m-0 inline-flex h-9 cursor-pointer items-center justify-center rounded-lg border border-[var(--border-strong)] bg-[var(--surface)] px-4 text-xs font-semibold text-[var(--ink)] transition-colors hover:bg-[var(--surface-2)]">Cancel</button>
                <button type="button" id="verifyEmailOtpBtn" class="!m-0 inline-flex h-9 cursor-pointer items-center justify-center rounded-lg border-0 bg-[#1b5e3a] px-4 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-[#14472c]">Verify &amp; Update Email</button>
            </div>
        </div>
    </div>

    {{-- Admin AI Intelligence Chatbot --}}
    <x-admin_chatbot />
</body>
</html>
