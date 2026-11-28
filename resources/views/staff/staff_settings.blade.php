<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Settings — Hinaguan Nature Park</title>
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
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/staff_js/staff_settings.js',
        'resources/js/staff_chatbot.js',
    ])
    <style>
        body.staff-portal {
            background-color: #ebf3ec !important;
        }
        [data-theme="dark"] body.staff-portal {
            background-color: #0f1110 !important;
        }
        body.staff-portal .dash-layout,
        body.staff-portal .dash-main,
        body.staff-portal .dash-content {
            background: transparent !important;
            background-color: transparent !important;
            background-image: none !important;
        }
        body.staff-portal .dash-main {
            position: relative !important;
            min-height: 100vh;
            z-index: 0;
        }
        body.staff-portal .dash-main::before {
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
            body.staff-portal .dash-main::before {
                left: 0 !important;
            }
        }
        [data-theme="dark"] body.staff-portal .dash-main::before {
            background-color: #0f1110 !important;
            background-image: linear-gradient(rgba(15, 17, 16, 0.94), rgba(15, 17, 16, 0.97)), url('{{ asset('storage/design_images/staff-admin-background-image.jpeg') }}') !important;
            filter: none !important;
            -webkit-filter: none !important;
            opacity: 1 !important;
        }
        body.staff-portal .dash-content {
            position: relative !important;
            z-index: 1 !important;
        }
        body.staff-portal [class*="backdrop-blur"] {
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
    </style>
</head>
<body class="antialiased staff-portal">
    <div class="dash-layout">
        <x-staff_sidemenu active="settings" userName="{{ session('auth_user.name') ?? 'Staff User' }}" userRole="Staff" />
        <div class="dash-main">
            <x-header title="Settings" subtitle="Update your profile securely" />

            <main class="dash-content p-6">
                <div id="staffSettingsAlertContainer" class="space-y-2">
                    @if(session('success'))
                        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-950/50 dark:text-emerald-200">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 dark:border-red-500/30 dark:bg-red-950/50 dark:text-red-200">{{ session('error') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 dark:border-red-500/30 dark:bg-red-950/50 dark:text-red-200">
                            {{ $errors->first() }}
                        </div>
                    @endif
                </div>

                @php
                    $staffName = session('auth_user.name') ?? 'Staff User';
                    $staffEmail = session('auth_user.email') ?? '';
                    $staffInitials = collect(explode(' ', trim($staffName)))
                        ->filter()
                        ->take(2)
                        ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                        ->implode('') ?: 'ST';
                @endphp

                <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
                    {{-- Left: Staff Account Summary --}}
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs lg:col-span-4 dark:border-white/10 dark:bg-[#181b19]">
                        <div class="flex items-center gap-3.5">
                            <div data-staff-initials class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#155e3c] text-base font-bold text-white">
                                {{ $staffInitials }}
                            </div>
                            <div class="min-w-0">
                                <h2 data-staff-name-display class="m-0 truncate text-base font-bold text-slate-900 dark:text-white">{{ $staffName }}</h2>
                                <p data-staff-email-display class="m-0 truncate text-xs text-slate-500 dark:text-slate-400">{{ $staffEmail }}</p>
                            </div>
                        </div>

                        <div class="mt-5 space-y-2.5 border-t border-slate-100 pt-4 text-xs dark:border-white/10">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Account Role</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">Park Staff</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Verification</span>
                                <span class="font-semibold text-emerald-700 dark:text-emerald-400">Email OTP Enabled</span>
                            </div>
                        </div>

                        <p class="m-0 mt-5 rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs leading-relaxed text-slate-600 dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-400">
                            Any update to your name, email address, or password requires confirming a 6-digit code sent to your email.
                        </p>
                    </div>

                    {{-- Right: Account & Security Actions --}}
                    <div class="rounded-xl border border-slate-200 bg-white shadow-xs lg:col-span-8 dark:border-white/10 dark:bg-[#181b19]">
                        <div class="border-b border-slate-100 px-6 py-4 dark:border-white/10">
                            <h2 class="m-0 text-base font-bold text-slate-900 dark:text-white">Account &amp; Security</h2>
                            <p class="m-0 mt-0.5 text-xs text-slate-500 dark:text-slate-400">Manage your profile name, email address, and login password.</p>
                        </div>

                        <div class="divide-y divide-slate-100 dark:divide-white/10">
                            {{-- Full Name Row --}}
                            <div class="flex flex-col gap-3 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Full Name</div>
                                    <div data-staff-name-display class="mt-1 truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $staffName }}</div>
                                </div>
                                <button type="button" data-open-staff-modal="staffChangeNameModal"
                                    class="inline-flex shrink-0 cursor-pointer items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50 dark:border-white/15 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10">
                                    Change Name
                                </button>
                            </div>

                            {{-- Email Address Row --}}
                            <div class="flex flex-col gap-3 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Email Address</div>
                                    <div data-staff-email-display class="mt-1 truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $staffEmail }}</div>
                                </div>
                                <button type="button" data-open-staff-modal="staffChangeEmailModal"
                                    class="inline-flex shrink-0 cursor-pointer items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50 dark:border-white/15 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10">
                                    Change Email
                                </button>
                            </div>

                            {{-- Password Row --}}
                            <div class="flex flex-col gap-3 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Password</div>
                                    <div class="mt-1 text-sm font-semibold tracking-widest text-slate-900 dark:text-white">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</div>
                                    <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Minimum 8 characters. Verified via email OTP.</div>
                                </div>
                                <button type="button" data-open-staff-modal="staffChangePasswordModal"
                                    class="inline-flex shrink-0 cursor-pointer items-center justify-center rounded-lg bg-[#155e3c] px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-[#114b30]">
                                    Change Password
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    {{-- Modal: Change Name --}}
    <div class="modal fixed inset-0 z-[1200] hidden items-center justify-center p-4 is-open:flex" id="staffChangeNameModal" aria-hidden="true">
        <div class="absolute inset-0 bg-black/50 dark:bg-black/75" data-close-staff-modal="staffChangeNameModal"></div>
        <div class="relative z-[1] w-full max-w-md overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-white/10 dark:bg-[#181b19]">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-white/10">
                <div>
                    <h3 class="m-0 text-base font-bold text-slate-900 dark:text-white">Change Full Name</h3>
                    <p class="m-0 mt-0.5 text-xs text-slate-500 dark:text-slate-400">An OTP code will be sent to verify this change.</p>
                </div>
                <button type="button" data-close-staff-modal="staffChangeNameModal" aria-label="Close"
                    class="cursor-pointer rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-white/10 dark:hover:text-slate-200">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
                        <form method="POST" action="{{ route('staff.settings.update') }}" class="staff-settings-modal-form p-5" data-error-target="staffChangeNameError">
                @csrf
                <input type="hidden" name="email" value="{{ $staffEmail }}">
                <div class="space-y-1.5">
                    <label for="modal_staff_name" class="block text-xs font-semibold text-slate-700 dark:text-slate-200">Full Name <span class="text-red-500">*</span></label>
                    <input id="modal_staff_name" name="name" type="text" value="{{ $staffName }}" required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/15 dark:border-white/15 dark:bg-white/5 dark:text-white">
                </div>
                <p id="staffChangeNameError" class="m-0 mt-2 hidden text-xs font-medium text-red-600 dark:text-red-400"></p>
                <div class="mt-5 flex items-center justify-end gap-2.5">
                    <button type="button" data-close-staff-modal="staffChangeNameModal" class="cursor-pointer rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/15 dark:bg-transparent dark:text-slate-300 dark:hover:bg-white/5">Cancel</button>
                    <button type="submit" class="cursor-pointer rounded-lg bg-[#155e3c] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#114b30] disabled:cursor-not-allowed disabled:opacity-60">Send OTP Code</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Change Email --}}
    <div class="modal fixed inset-0 z-[1200] hidden items-center justify-center p-4 is-open:flex" id="staffChangeEmailModal" aria-hidden="true">
        <div class="absolute inset-0 bg-black/50 dark:bg-black/75" data-close-staff-modal="staffChangeEmailModal"></div>
        <div class="relative z-[1] w-full max-w-md overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-white/10 dark:bg-[#181b19]">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-white/10">
                <div>
                    <h3 class="m-0 text-base font-bold text-slate-900 dark:text-white">Change Email Address</h3>
                    <p class="m-0 mt-0.5 text-xs text-slate-500 dark:text-slate-400">An OTP code will be sent to verify the new email.</p>
                </div>
                <button type="button" data-close-staff-modal="staffChangeEmailModal" aria-label="Close"
                    class="cursor-pointer rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-white/10 dark:hover:text-slate-200">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('staff.settings.update') }}" class="staff-settings-modal-form p-5" data-error-target="staffChangeEmailError">
                @csrf
                <input type="hidden" name="name" value="{{ $staffName }}">
                <div class="space-y-1.5">
                    <label for="modal_staff_email" class="block text-xs font-semibold text-slate-700 dark:text-slate-200">New Email Address <span class="text-red-500">*</span></label>
                    <input id="modal_staff_email" name="email" type="email" value="{{ $staffEmail }}" required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/15 dark:border-white/15 dark:bg-white/5 dark:text-white">
                </div>
                <p id="staffChangeEmailError" class="m-0 mt-2 hidden text-xs font-medium text-red-600 dark:text-red-400"></p>
                <div class="mt-5 flex items-center justify-end gap-2.5">
                    <button type="button" data-close-staff-modal="staffChangeEmailModal" class="cursor-pointer rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/15 dark:bg-transparent dark:text-slate-300 dark:hover:bg-white/5">Cancel</button>
                    <button type="submit" class="cursor-pointer rounded-lg bg-[#155e3c] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#114b30] disabled:cursor-not-allowed disabled:opacity-60">Send OTP Code</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Change Password --}}
    <div class="modal fixed inset-0 z-[1200] hidden items-center justify-center p-4 is-open:flex" id="staffChangePasswordModal" aria-hidden="true">
        <div class="absolute inset-0 bg-black/50 dark:bg-black/75" data-close-staff-modal="staffChangePasswordModal"></div>
        <div class="relative z-[1] w-full max-w-md overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-white/10 dark:bg-[#181b19]">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-white/10">
                <div>
                    <h3 class="m-0 text-base font-bold text-slate-900 dark:text-white">Change Password</h3>
                    <p class="m-0 mt-0.5 text-xs text-slate-500 dark:text-slate-400">An OTP code will be sent to verify this change.</p>
                </div>
                <button type="button" data-close-staff-modal="staffChangePasswordModal" aria-label="Close"
                    class="cursor-pointer rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-white/10 dark:hover:text-slate-200">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('staff.settings.update') }}" id="staffChangePasswordForm" class="staff-settings-modal-form space-y-4 p-5" data-error-target="staffSettingsPasswordError">
                @csrf
                <input type="hidden" name="name" value="{{ $staffName }}">
                <input type="hidden" name="email" value="{{ $staffEmail }}">

                <div class="space-y-1.5">
                    <label for="password" class="block text-xs font-semibold text-slate-700 dark:text-slate-200">New Password <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input id="password" name="password" type="password" required minlength="8" placeholder="At least 8 characters"
                            class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-3.5 pr-10 text-sm text-slate-900 focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/15 dark:border-white/15 dark:bg-white/5 dark:text-white">
                        <button type="button" data-toggle-password="password" aria-label="Toggle password visibility"
                            class="absolute inset-y-0 right-0 flex cursor-pointer items-center px-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </button>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 dark:text-slate-200">Confirm New Password <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" placeholder="Re-enter new password"
                            class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-3.5 pr-10 text-sm text-slate-900 focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/15 dark:border-white/15 dark:bg-white/5 dark:text-white">
                        <button type="button" data-toggle-password="password_confirmation" aria-label="Toggle confirm password visibility"
                            class="absolute inset-y-0 right-0 flex cursor-pointer items-center px-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </button>
                    </div>
                </div>

                <p id="staffSettingsPasswordError" class="m-0 hidden text-xs font-medium text-red-600 dark:text-red-400"></p>

                <div class="mt-5 flex items-center justify-end gap-2.5 pt-1">
                    <button type="button" data-close-staff-modal="staffChangePasswordModal" class="cursor-pointer rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/15 dark:bg-transparent dark:text-slate-300 dark:hover:bg-white/5">Cancel</button>
                    <button type="submit" class="cursor-pointer rounded-lg bg-[#155e3c] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#114b30] disabled:cursor-not-allowed disabled:opacity-60">Send OTP Code</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fixed inset-0 z-[1200] hidden items-center justify-center p-4 is-open:flex" id="staffOtpModal" aria-hidden="true">
        <div class="absolute inset-0 bg-black/50 dark:bg-black/75" data-close-staff-otp></div>
        <div class="relative z-[1] w-full max-w-md overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-white/10 dark:bg-[#181b19]">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-white/10">
                <div>
                    <h3 class="m-0 text-base font-bold text-slate-900 dark:text-white">Verify Your Changes</h3>
                    <p id="staffOtpSubtitle" class="m-0 mt-0.5 text-xs text-slate-500 dark:text-slate-400">Enter the 6-digit code sent to your email.</p>
                </div>
                <button type="button" class="cursor-pointer rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-white/10 dark:hover:text-slate-200" data-close-staff-otp aria-label="Close">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('staff.settings.verify') }}" id="staffOtpForm" class="block p-5">
                @csrf
                <div class="space-y-1.5">
                    <label for="otp_code" class="block text-xs font-semibold text-slate-700 dark:text-slate-200">Verification Code <span class="text-red-500">*</span></label>
                    <input id="otp_code" name="code" type="tel" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required
                        class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-center font-mono text-lg font-bold tracking-[0.35em] text-slate-900 placeholder:text-slate-300 focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/15 dark:border-white/15 dark:bg-white/5 dark:text-white">
                </div>
                <p id="staffOtpError" class="m-0 mt-2 hidden text-xs font-medium text-red-600 dark:text-red-400"></p>
                <div class="mt-5 flex items-center justify-end gap-2.5 border-t border-slate-100 pt-4 dark:border-white/10">
                    <button type="button" data-close-staff-otp class="cursor-pointer rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/15 dark:bg-transparent dark:text-slate-300 dark:hover:bg-white/5">Cancel</button>
                    <button type="submit" class="cursor-pointer rounded-lg bg-[#155e3c] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#114b30] disabled:cursor-not-allowed disabled:opacity-60">Verify &amp; Save</button>
                </div>
            </form>
        </div>
    </div>
    @if(session('staff_profile_change'))
        <script>
            (function () {
                var modal = document.getElementById('staffOtpModal');
                if (modal) {
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                }
            })();
        </script>
    @endif

    <x-staff_chatbot />
</body>
</html>
