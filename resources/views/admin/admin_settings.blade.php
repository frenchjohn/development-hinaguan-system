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
            background-image: url('{{ asset('storage/design_images/staff-admin-background-image.jpeg') }}') !important;
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
            <!-- Page transition overlay with skeleton loading -->
            <main class="dash-content p-6">
                <x-header title="Settings" subtitle="Manage park configuration, rules, and security" />
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
                        <button type="button" class="admin-settings__back-btn mb-6 inline-flex items-center gap-2 rounded-xl border border-[rgba(13,44,29,0.1)] bg-white px-6 py-3 font-medium text-[var(--hp-text)] transition-all duration-300 hover:-translate-x-1 hover:border-[var(--hp-green)] hover:bg-[var(--hp-green)] hover:text-white dark:border-white/10 dark:bg-white/5 dark:hover:border-[var(--hp-gold)] dark:hover:bg-[var(--hp-gold)]" id="backToMenu">
                            <svg class="admin-settings__back-icon h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Settings
                        </button>
                        <section class="dash-panel p-8">
                            <div class="mb-4 flex flex-wrap items-center justify-between gap-4 border-b-2 border-[rgba(13,44,29,0.1)] pb-4">
                                <div>
                                    <h2 class="admin-settings__card-title m-0 text-[1.25rem] font-semibold text-[var(--hp-text)]">Park Configuration</h2>
                                    <p class="admin-settings__card-text m-0 mb-4 mt-1 text-[0.875rem] text-[var(--hp-text-muted)]">Manage park information, operating hours, and fees.</p>
                                </div>
                                <button type="button" class="admin-settings__btn admin-settings__btn--primary" id="editParkSettingsBtn">
                                    <svg class="admin-settings__btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Edit
                                </button>
                            </div>
                            <form method="POST" action="{{ route('admin.settings.park.update') }}" class="admin-settings__form" id="parkSettingsForm">
                                @csrf
                                <div class="admin-settings__form-grid">
                                    <!-- Live Park Status & Closure Reason Card -->
                                    <div class="admin-settings__group admin-settings__group--full p-4 rounded-2xl bg-[#f4f7f5] dark:bg-[#091710] border border-[#dbe3de] dark:border-[#242a26]">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                            <div>
                                                <label class="admin-settings__label text-sm font-bold text-[#0d2c1d] dark:text-[#f5f5f0] mb-0.5">Live Park Operational Status</label>
                                                <p class="m-0 text-xs text-[#5a6b5c] dark:text-[#a8b8a8]">Set whether the park is currently open for visitors or temporarily closed.</p>
                                            </div>
                                            <div class="inline-flex items-center gap-4 bg-white dark:bg-[#181b19] py-1.5 px-3.5 rounded-xl border border-[#dbe3de] dark:border-[#282c29]">
                                                <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold">
                                                    <input type="radio" name="park_status" value="open" id="park_status_open" class="w-4 h-4 text-emerald-600 focus:ring-emerald-500" {{ ($parkSettings->park_status ?? 'open') === 'open' ? 'checked' : '' }} disabled>
                                                    <span class="inline-flex items-center gap-1.5 py-0.5 px-2.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                                        Park Open
                                                    </span>
                                                </label>
                                                <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold">
                                                    <input type="radio" name="park_status" value="closed" id="park_status_closed" class="w-4 h-4 text-red-600 focus:ring-red-500" {{ ($parkSettings->park_status ?? 'open') === 'closed' ? 'checked' : '' }} disabled>
                                                    <span class="inline-flex items-center gap-1.5 py-0.5 px-2.5 rounded-full bg-red-100 dark:bg-red-950/60 text-red-800 dark:text-red-300 border border-red-300 dark:border-red-800">
                                                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                                        Park Closed
                                                    </span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Close Description (Reason for Closure) -->
                                        <div id="closeDescriptionWrapper" class="{{ ($parkSettings->park_status ?? 'open') === 'closed' ? '' : 'hidden' }} mt-3 pt-3 border-t border-[#dbe3de] dark:border-[#242a26]">
                                            <label for="close_description" class="admin-settings__label text-xs font-semibold text-red-700 dark:text-red-400">
                                                Closure Reason / Description <span class="text-xs font-normal text-[#5a6b5c] dark:text-[#a8b8a8]">(Shows on hover in headers & guest website)</span>
                                            </label>
                                            <textarea id="close_description" name="close_description" rows="2" class="admin-settings__input w-full mt-1" placeholder="e.g., Temporarily closed for scheduled river maintenance and pool water treatment. We will reopen tomorrow." disabled>{{ $parkSettings->close_description ?? '' }}</textarea>
                                            <p class="m-0 mt-1 text-[0.75rem] text-[#5a6b5c] dark:text-[#a8b8a8]">When set to Open, this description will automatically be cleared.</p>
                                        </div>
                                    </div>

                                    <div class="admin-settings__group">
                                        <label for="contact_number" class="admin-settings__label">Contact Number</label>
                                        <input type="tel" id="contact_number" name="contact_number" class="admin-settings__input" value="{{ $parkSettings->contact_number ?? '' }}" disabled>
                                    </div>
                                    <div class="admin-settings__group">
                                        <label for="email" class="admin-settings__label">Park Email</label>
                                        <input type="email" id="email" name="email" class="admin-settings__input" value="{{ $parkSettings->email ?? '' }}" disabled>
                                    </div>
                                    <div class="admin-settings__group">
                                        <label for="daytime_start" class="admin-settings__label">Daytime Start</label>
                                        <input type="time" id="daytime_start" name="daytime_start" class="admin-settings__input" value="{{ $parkSettings->daytime_start ?? '08:01' }}" disabled>
                                    </div>
                                    <div class="admin-settings__group">
                                        <label for="daytime_end" class="admin-settings__label">Daytime End</label>
                                        <input type="time" id="daytime_end" name="daytime_end" class="admin-settings__input" value="{{ $parkSettings->daytime_end ?? '18:00' }}" disabled>
                                    </div>
                                    <div class="admin-settings__group">
                                        <label for="nighttime_start" class="admin-settings__label">Nighttime Start</label>
                                        <input type="time" id="nighttime_start" name="nighttime_start" class="admin-settings__input" value="{{ $parkSettings->nighttime_start ?? '18:01' }}" disabled>
                                    </div>
                                    <div class="admin-settings__group">
                                        <label for="nighttime_end" class="admin-settings__label">Nighttime End</label>
                                        <input type="time" id="nighttime_end" name="nighttime_end" class="admin-settings__input" value="{{ $parkSettings->nighttime_end ?? '08:00' }}" disabled>
                                    </div>
                                    <div class="admin-settings__group">
                                        <label for="daytime_adult_entrance_fee" class="admin-settings__label">Daytime Adult Entrance Fee (₱)</label>
                                        <input type="number" id="daytime_adult_entrance_fee" name="daytime_adult_entrance_fee" step="0.01" class="admin-settings__input" value="{{ $parkSettings->daytime_adult_entrance_fee ?? 0 }}" disabled>
                                    </div>
                                    <div class="admin-settings__group">
                                        <label for="daytime_child_entrance_fee" class="admin-settings__label">Daytime Child Entrance Fee (₱)</label>
                                        <input type="number" id="daytime_child_entrance_fee" name="daytime_child_entrance_fee" step="0.01" class="admin-settings__input" value="{{ $parkSettings->daytime_child_entrance_fee ?? 0 }}" disabled>
                                    </div>
                                    <div class="admin-settings__group">
                                        <label for="nighttime_adult_entrance_fee" class="admin-settings__label">Nighttime Adult Entrance Fee (₱)</label>
                                        <input type="number" id="nighttime_adult_entrance_fee" name="nighttime_adult_entrance_fee" step="0.01" class="admin-settings__input" value="{{ $parkSettings->nighttime_adult_entrance_fee ?? 0 }}" disabled>
                                    </div>
                                    <div class="admin-settings__group">
                                        <label for="nighttime_child_entrance_fee" class="admin-settings__label">Nighttime Child Entrance Fee (₱)</label>
                                        <input type="number" id="nighttime_child_entrance_fee" name="nighttime_child_entrance_fee" step="0.01" class="admin-settings__input" value="{{ $parkSettings->nighttime_child_entrance_fee ?? 0 }}" disabled>
                                    </div>
                                    <div class="admin-settings__group">
                                        <label for="day_pool_fee" class="admin-settings__label">Day Pool Fee (₱)</label>
                                        <input type="number" id="day_pool_fee" name="day_pool_fee" step="0.01" class="admin-settings__input" value="{{ $parkSettings->day_pool_fee ?? 0 }}" disabled>
                                    </div>
                                    <div class="admin-settings__group">
                                        <label for="night_pool_fee" class="admin-settings__label">Night Pool Fee (₱)</label>
                                        <input type="number" id="night_pool_fee" name="night_pool_fee" step="0.01" class="admin-settings__input" value="{{ $parkSettings->night_pool_fee ?? 0 }}" disabled>
                                    </div>
                                    <div class="admin-settings__group admin-settings__group--full">
                                        <label for="facebook_link" class="admin-settings__label">Facebook Link</label>
                                        <input type="url" id="facebook_link" name="facebook_link" class="admin-settings__input" value="{{ $parkSettings->facebook_link ?? '' }}" disabled>
                                    </div>
                                </div>
                                <div class="admin-settings__form-actions admin-settings__form-actions--hidden" id="parkSettingsFormActions">
                                    <button type="submit" class="admin-settings__btn admin-settings__btn--primary">Save Changes</button>
                                    <button type="button" class="admin-settings__btn admin-settings__btn--secondary" id="cancelParkSettingsBtn">Cancel</button>
                                </div>
                            </form>
                        </section>
                    </div>

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

                    <!-- Park Rules Content -->
                    <div class="admin-settings__content admin-settings__content--hidden" id="park-rules">
                        <button type="button" class="admin-settings__back-btn mb-6 inline-flex items-center gap-2 rounded-xl border border-[rgba(13,44,29,0.1)] bg-white px-6 py-3 font-medium text-[var(--hp-text)] transition-all duration-300 hover:-translate-x-1 hover:border-[var(--hp-green)] hover:bg-[var(--hp-green)] hover:text-white dark:border-white/10 dark:bg-white/5 dark:hover:border-[var(--hp-gold)] dark:hover:bg-[var(--hp-gold)]" id="backToMenuFromRules">
                            <svg class="admin-settings__back-icon h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Settings
                        </button>

                        <section class="dash-panel p-6 sm:p-8">
                            <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b-2 border-[rgba(13,44,29,0.1)] pb-4 dark:border-white/10">
                                <div>
                                    <h2 class="admin-settings__card-title m-0 text-[1.25rem] font-semibold text-[var(--hp-text)]">Park Rules &amp; Guidelines</h2>
                                    <p class="admin-settings__card-text m-0 mt-1 text-[0.875rem] text-[var(--hp-text-muted)]">Click on any rule to view its description, edit, or delete.</p>
                                </div>
                                <button type="button" class="admin-settings__btn admin-settings__btn--primary" id="addRuleBtn">
                                    <svg class="admin-settings__btn-icon h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Add Rule
                                </button>
                            </div>

                            <!-- Compact Rules List -->
                            <div class="park-rules-list grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" id="parkRulesGrid">
                                @forelse($parkRules as $rule)
                                    <div class="park-rule-item group relative flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[rgba(13,44,29,0.1)] bg-white px-4 py-3.5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--hp-green)] hover:shadow-md dark:border-white/10 dark:bg-white/5 dark:hover:border-[var(--hp-gold)]"
                                         id="parkRuleCard_{{ $rule->id }}"
                                         data-rule-id="{{ $rule->id }}"
                                         data-rule-name="{{ $rule->rule_name }}"
                                         data-rule-desc="{{ $rule->rule_descriptions }}"
                                         data-rule-updated="{{ $rule->updated_at ? $rule->updated_at->diffForHumans() : 'Recently' }}">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[rgba(26,58,31,0.08)] text-xs font-bold text-[var(--hp-green)] transition-colors group-hover:bg-[var(--hp-green)] group-hover:text-white dark:bg-[rgba(200,164,93,0.15)] dark:text-[var(--hp-gold)] dark:group-hover:bg-[var(--hp-gold)] dark:group-hover:text-black">
                                                #{{ $rule->id }}
                                            </span>
                                            <span class="truncate text-sm font-semibold text-[var(--hp-text)] rule-name-display">
                                                {{ $rule->rule_name }}
                                            </span>
                                        </div>
                                        <div class="flex items-center text-[var(--hp-text-muted)] group-hover:text-[var(--hp-green)] dark:group-hover:text-[var(--hp-gold)] transition-colors">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-span-full py-10 text-center text-sm text-[var(--hp-text-muted)]" id="emptyParkRulesState">
                                        No park rules defined yet. Click "Add Rule" above to create one.
                                    </div>
                                @endforelse
                            </div>
                        </section>
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
                    <div id="parkRuleModal" class="admin-settings__modal" style="display: none;">
                        <div class="admin-settings__modal-content max-w-lg">
                            <h3 class="admin-settings__modal-title text-left" id="parkRuleModalTitle">Add Park Rule</h3>
                            <p class="admin-settings__modal-text text-left" id="parkRuleModalSubtitle">Create a new operational rule or guideline for Hinaguan Nature Park.</p>
                            
                            <form id="parkRuleForm" class="mt-4 flex flex-col gap-4 text-left">
                                @csrf
                                <input type="hidden" id="ruleIdInput" name="rule_id" value="">
                                
                                <div class="admin-settings__group admin-settings__group--full">
                                    <label for="ruleNameInput" class="admin-settings__label font-semibold">Rule Name / Title</label>
                                    <input type="text" id="ruleNameInput" name="rule_name" class="admin-settings__input" placeholder="e.g. Proper Swimming Attire" required>
                                    <span class="admin-settings__error" id="ruleNameError"></span>
                                </div>

                                <div class="admin-settings__group admin-settings__group--full">
                                    <label for="ruleDescInput" class="admin-settings__label font-semibold">Rule Description / Guidelines</label>
                                    <textarea id="ruleDescInput" name="rule_descriptions" rows="4" class="admin-settings__input h-auto resize-y" placeholder="Describe the policy, requirements, or restrictions..." required></textarea>
                                    <span class="admin-settings__error" id="ruleDescError"></span>
                                </div>

                                <div class="admin-settings__form-actions mt-2 flex justify-end gap-3">
                                    <button type="submit" class="admin-settings__btn admin-settings__btn--primary" id="saveRuleSubmitBtn">Save Rule</button>
                                    <button type="button" class="admin-settings__btn admin-settings__btn--secondary" id="cancelRuleModalBtn">Cancel</button>
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

                    <!-- Event Settings Content -->
                    <div class="admin-settings__content admin-settings__content--hidden" id="event-settings">
                        <button type="button" class="admin-settings__back-btn mb-6 inline-flex items-center gap-2 rounded-xl border border-[rgba(13,44,29,0.1)] bg-white px-6 py-3 font-medium text-[var(--hp-text)] transition-all duration-300 hover:-translate-x-1 hover:border-[var(--hp-green)] hover:bg-[var(--hp-green)] hover:text-white dark:border-white/10 dark:bg-white/5 dark:hover:border-[var(--hp-gold)] dark:hover:bg-[var(--hp-gold)]" id="backToMenuFromEvents">
                            <svg class="admin-settings__back-icon h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Settings
                        </button>

                        <section class="dash-panel p-6 sm:p-8">
                            <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b-2 border-[rgba(13,44,29,0.1)] pb-4 dark:border-white/10">
                                <div>
                                    <h2 class="admin-settings__card-title m-0 text-[1.25rem] font-semibold text-[var(--hp-text)]">Park Events &amp; Experiences</h2>
                                    <p class="admin-settings__card-text m-0 mt-1 text-[0.875rem] text-[var(--hp-text-muted)]">Click on any event to view details, edit, or remove.</p>
                                </div>
                                <button type="button" class="admin-settings__btn admin-settings__btn--primary" id="addEventBtn">
                                    <svg class="admin-settings__btn-icon h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Add Event
                                </button>
                            </div>

                            <!-- Events List Grid -->
                            <div class="park-events-list grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" id="parkEventsGrid">
                                @forelse($parkEvents as $event)
                                    <div class="park-event-item group relative flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[rgba(13,44,29,0.1)] bg-white px-4 py-3.5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--hp-green)] hover:shadow-md dark:border-white/10 dark:bg-white/5 dark:hover:border-[var(--hp-gold)]"
                                         id="parkEventCard_{{ $event->id }}"
                                         data-event-id="{{ $event->id }}"
                                         data-event-title="{{ $event->title }}"
                                         data-event-date="{{ $event->date ? $event->date->format('Y-m-d') : '' }}"
                                         data-event-day="{{ $event->day }}"
                                         data-event-time="{{ $event->time }}"
                                         data-event-desc="{{ $event->event }}"
                                         data-event-updated="{{ $event->updated_at ? $event->updated_at->diffForHumans() : 'Recently' }}">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="flex flex-col items-center justify-center shrink-0 w-12 py-1 rounded-lg bg-[rgba(26,58,31,0.08)] text-[var(--hp-green)] transition-colors group-hover:bg-[var(--hp-green)] group-hover:text-white dark:bg-[rgba(200,164,93,0.15)] dark:text-[var(--hp-gold)] dark:group-hover:bg-[var(--hp-gold)] dark:group-hover:text-black">
                                                <span class="text-[0.65rem] font-bold uppercase tracking-wider">{{ $event->date ? $event->date->format('M') : 'DATE' }}</span>
                                                <span class="text-base font-extrabold leading-none">{{ $event->date ? $event->date->format('d') : '--' }}</span>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="truncate block text-sm font-semibold text-[var(--hp-text)] event-title-display">
                                                    {{ $event->title }}
                                                </span>
                                                <span class="text-xs text-[var(--hp-text-muted)] block truncate">
                                                    {{ $event->day }}{{ $event->time ? ' · ' . $event->time : '' }} &middot; {{ Str::limit($event->event, 35) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center text-[var(--hp-text-muted)] group-hover:text-[var(--hp-green)] dark:group-hover:text-[var(--hp-gold)] transition-colors">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-span-full py-10 text-center text-sm text-[var(--hp-text-muted)]" id="emptyParkEventsState">
                                        No park events created yet. Click "Add Event" above to create one.
                                    </div>
                                @endforelse
                            </div>
                        </section>
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
                    <div id="parkEventModal" class="admin-settings__modal" style="display: none;">
                        <div class="admin-settings__modal-content max-w-lg text-left">
                            <div class="flex items-start justify-between gap-4 border-b border-[rgba(13,44,29,0.1)] pb-3 dark:border-white/10">
                                <div>
                                    <h3 class="admin-settings__modal-title text-left m-0 text-xl font-bold" id="parkEventModalTitle">Add Park Event</h3>
                                    <p class="admin-settings__modal-text text-left m-0 mt-1 text-xs text-[var(--hp-text-muted)]" id="parkEventModalSubtitle">Schedule a new event for Hinaguan Nature Park.</p>
                                </div>
                                <button type="button" id="closeAddEventModalXBtn" class="text-[var(--hp-text-muted)] hover:text-[var(--hp-text)] transition p-1">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            
                            <form id="parkEventForm" class="mt-4 flex flex-col gap-4 text-left">
                                @csrf
                                <input type="hidden" id="eventIdInput" name="event_id" value="">

                                <div class="admin-settings__group admin-settings__group--full">
                                    <label for="eventTitleInput" class="admin-settings__label font-semibold">Event Title</label>
                                    <input type="text" id="eventTitleInput" name="title" class="admin-settings__input w-full" placeholder="e.g. Riverside Acoustic Sunset Sessions" required maxlength="255">
                                    <span class="admin-settings__error" id="eventTitleError"></span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="admin-settings__group">
                                        <label for="eventDateInput" class="admin-settings__label font-semibold">Date</label>
                                        <input type="date" id="eventDateInput" name="date" class="admin-settings__input w-full" required>
                                        <span class="admin-settings__error" id="eventDateError"></span>
                                    </div>
                                    <div class="admin-settings__group">
                                        <label for="eventDayInput" class="admin-settings__label font-semibold">What Day</label>
                                        <input type="text" id="eventDayInput" name="day" class="admin-settings__input w-full" placeholder="Auto from date">
                                        <span class="admin-settings__error" id="eventDayError"></span>
                                    </div>
                                    <div class="admin-settings__group">
                                        <label for="eventTimeInput" class="admin-settings__label font-semibold">Time</label>
                                        <input type="text" id="eventTimeInput" name="time" class="admin-settings__input w-full" placeholder="e.g. 4:00 PM - 8:00 PM" maxlength="100">
                                        <span class="admin-settings__error" id="eventTimeError"></span>
                                    </div>
                                </div>

                                <div class="admin-settings__group admin-settings__group--full">
                                    <label for="eventDescInput" class="admin-settings__label font-semibold">Event Description (What event)</label>
                                    <textarea id="eventDescInput" name="event" rows="4" class="admin-settings__input w-full h-auto resize-y" placeholder="Describe what event or activity is taking place..." required maxlength="2000"></textarea>
                                    <span class="admin-settings__error" id="eventDescError"></span>
                                </div>

                                <div class="admin-settings__modal-actions mt-2 flex justify-end gap-3 border-t border-[rgba(13,44,29,0.1)] pt-4 dark:border-white/10">
                                    <button type="button" id="cancelEventModalBtn" class="admin-settings__btn admin-settings__btn--secondary">Cancel</button>
                                    <button type="submit" id="saveEventSubmitBtn" class="admin-settings__btn admin-settings__btn--primary">Save Event</button>
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

                    <!-- Security Content -->
                    <div class="admin-settings__content admin-settings__content--hidden" id="security">
                        <button type="button" class="admin-settings__back-btn mb-6 inline-flex items-center gap-2 rounded-xl border border-[rgba(13,44,29,0.1)] bg-white px-6 py-3 font-medium text-[var(--hp-text)] transition-all duration-300 hover:-translate-x-1 hover:border-[var(--hp-green)] hover:bg-[var(--hp-green)] hover:text-white dark:border-white/10 dark:bg-white/5 dark:hover:border-[var(--hp-gold)] dark:hover:bg-[var(--hp-gold)]" id="backToMenuFromSecurity">
                            <svg class="admin-settings__back-icon h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Settings
                        </button>
                        
                        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                            <!-- Change Password Section -->
                            <section class="dash-panel p-8">
                                <div class="mb-6 flex items-center gap-4">
                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[rgba(26,58,31,0.1)] text-[var(--hp-green)] dark:bg-[rgba(200,164,93,0.15)] dark:text-[var(--hp-gold)]">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-7 w-7">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="admin-settings__card-title m-0 text-[1.25rem] font-semibold text-[var(--hp-text)]">Password</h2>
                                        <p class="admin-settings__card-text m-0 text-[0.875rem] text-[var(--hp-text-muted)]">Update your account password</p>
                                    </div>
                                </div>
                                <form id="changePasswordForm" class="admin-settings__form admin-settings__form--hidden">
                                    @csrf
                                    <div class="admin-settings__form-grid">
                                        <div class="admin-settings__group">
                                            <label for="currentPassword" class="admin-settings__label">Current Password</label>
                                            <input type="password" id="currentPassword" name="current_password" class="admin-settings__input" required>
                                            <span class="admin-settings__error" id="currentPasswordError"></span>
                                        </div>
                                        <div class="admin-settings__group">
                                            <label for="newPassword" class="admin-settings__label">New Password</label>
                                            <input type="password" id="newPassword" name="new_password" class="admin-settings__input" required>
                                            <span class="admin-settings__error" id="newPasswordError"></span>
                                        </div>
                                        <div class="admin-settings__group">
                                            <label for="confirmPassword" class="admin-settings__label">Confirm Password</label>
                                            <input type="password" id="confirmPassword" name="confirm_password" class="admin-settings__input" required>
                                            <span class="admin-settings__error" id="confirmPasswordError"></span>
                                        </div>
                                    </div>
                                    <div class="admin-settings__form-actions">
                                        <button type="submit" class="admin-settings__btn admin-settings__btn--primary">Send OTP Code</button>
                                        <button type="button" class="admin-settings__btn admin-settings__btn--secondary" id="cancelPasswordBtn">Cancel</button>
                                    </div>
                                </form>
                                <button type="button" class="admin-settings__btn admin-settings__btn--outline" id="togglePasswordBtn">
                                    Change Password
                                </button>

                                <!-- OTP Verification Modal for Password -->
                                <div id="otpPasswordModal" class="admin-settings__modal" style="display: none;">
                                    <div class="admin-settings__modal-content">
                                        <h3 class="admin-settings__modal-title">Verify OTP Code</h3>
                                        <p class="admin-settings__modal-text">An OTP code has been sent to your email address.</p>
                                        <input type="text" id="otpPasswordCode" class="admin-settings__input" placeholder="Enter 6-digit OTP code" maxlength="6">
                                        <span class="admin-settings__error" id="otpPasswordError"></span>
                                        <div class="admin-settings__modal-actions">
                                            <button type="button" id="verifyPasswordOtpBtn" class="admin-settings__btn admin-settings__btn--primary">Verify & Change Password</button>
                                            <button type="button" id="cancelPasswordOtpBtn" class="admin-settings__btn admin-settings__btn--secondary">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Change Email Section -->
                            <section class="dash-panel p-8">
                                <div class="mb-6 flex items-center gap-4">
                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[rgba(26,58,31,0.1)] text-[var(--hp-green)] dark:bg-[rgba(200,164,93,0.15)] dark:text-[var(--hp-gold)]">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-7 w-7">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="admin-settings__card-title m-0 text-[1.25rem] font-semibold text-[var(--hp-text)]">Email</h2>
                                        <p class="admin-settings__card-text m-0 text-[0.875rem] text-[var(--hp-text-muted)]">Update your email address</p>
                                    </div>
                                </div>
                                <div class="admin-settings__email-display">
                                    <p class="admin-settings__email-label">Current Email:</p>
                                    <p class="admin-settings__email-value" id="currentEmailDisplay">{{ session('auth_user')['email'] ?? 'Not set' }}</p>
                                </div>
                                <form id="changeEmailForm" class="admin-settings__form admin-settings__form--hidden">
                                    @csrf
                                    <div class="admin-settings__group">
                                        <label for="newEmail" class="admin-settings__label">New Email Address</label>
                                        <input type="email" id="newEmail" name="new_email" class="admin-settings__input" required>
                                        <span class="admin-settings__error" id="newEmailError"></span>
                                    </div>
                                    <div class="admin-settings__info">You will receive an OTP code on your current email to verify the change.</div>
                                    <div class="admin-settings__form-actions">
                                        <button type="submit" class="admin-settings__btn admin-settings__btn--primary">Send OTP Code</button>
                                        <button type="button" class="admin-settings__btn admin-settings__btn--secondary" id="cancelEmailBtn">Cancel</button>
                                    </div>
                                </form>
                                <button type="button" class="admin-settings__btn admin-settings__btn--outline" id="toggleEmailBtn">
                                    Change Email
                                </button>

                                <!-- OTP Verification Modal for Email -->
                                <div id="otpEmailModal" class="admin-settings__modal" style="display: none;">
                                    <div class="admin-settings__modal-content">
                                        <h3 class="admin-settings__modal-title">Verify Email Change</h3>
                                        <p class="admin-settings__modal-text">An OTP code has been sent to your current email address.</p>
                                        <input type="text" id="otpEmailCode" class="admin-settings__input" placeholder="Enter 6-digit OTP code" maxlength="6">
                                        <span class="admin-settings__error" id="otpEmailError"></span>
                                        <div class="admin-settings__modal-actions">
                                            <button type="button" id="verifyEmailOtpBtn" class="admin-settings__btn admin-settings__btn--primary">Verify & Update Email</button>
                                            <button type="button" id="cancelEmailOtpBtn" class="admin-settings__btn admin-settings__btn--secondary">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    {{-- Admin AI Intelligence Chatbot --}}
    <x-admin_chatbot />
</body>
</html>
                </div>
            </main>
        </div>
    </div>

    {{-- Admin AI Intelligence Chatbot --}}
    <x-admin_chatbot />
</body>
</html>
