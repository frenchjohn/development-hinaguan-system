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
        'resources/css/staff_css/staff_shared.css',
        'resources/css/homepage.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/staff_sidemenu.css',
        'resources/css/chatbot.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/staff_js/staff_settings.js',
        'resources/js/staff_chatbot.js',
    ])
</head>
<body class="antialiased staff-portal">
    <div class="dash-layout">
        <x-staff_sidemenu active="settings" userName="{{ session('auth_user.name') ?? 'Staff User' }}" userRole="Staff" />
        <div class="dash-main">

            <main class="dash-content p-6">
                <x-header title="Settings" subtitle="Update your profile securely" />

                @if(session('success'))
                    <div class="mb-4 rounded-2xl border border-glass-border bg-[rgba(26,58,31,0.15)] px-4 py-3 text-sm font-semibold text-hp-green shadow-glass">{{ session('success') }}</div>
                @endif

                @if(session('error'))
                    <div class="mb-4 rounded-2xl border border-glass-border bg-[rgba(220,38,38,0.1)] px-4 py-3 text-sm font-semibold text-[#dc2626] shadow-glass">{{ session('error') }}</div>
                @endif

                <div class="settings-panel rounded-2xl border border-glass-border bg-glass p-6 shadow-glass sm:p-8">
                    <form method="POST" action="{{ route('staff.settings.update') }}" class="settings-form">
                        @csrf
                        <div class="settings-form__grid mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="settings-form__field grid gap-1.5">
                                <label for="name" class="text-sm font-semibold text-hp-text">Full Name</label>
                                <input id="name" name="name" type="text" value="{{ session('auth_user.name') ?? '' }}" required
                                    class="w-full rounded-xl border border-glass-border bg-glass px-4 py-3 text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none focus:ring-2 focus:ring-hp-green/25 dark:border-white/10 dark:bg-white/5">
                            </div>
                            <div class="settings-form__field grid gap-1.5">
                                <label for="email" class="text-sm font-semibold text-hp-text">Email Address</label>
                                <input id="email" name="email" type="email" value="{{ session('auth_user.email') ?? '' }}" required
                                    class="w-full rounded-xl border border-glass-border bg-glass px-4 py-3 text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none focus:ring-2 focus:ring-hp-green/25 dark:border-white/10 dark:bg-white/5">
                            </div>
                            <div class="settings-form__field grid gap-1.5">
                                <label for="password" class="text-sm font-semibold text-hp-text">New Password</label>
                                <input id="password" name="password" type="password" placeholder="Leave blank to keep current password"
                                    class="w-full rounded-xl border border-glass-border bg-glass px-4 py-3 text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none focus:ring-2 focus:ring-hp-green/25 dark:border-white/10 dark:bg-white/5">
                            </div>
                            <div class="settings-form__field grid gap-1.5">
                                <label for="password_confirmation" class="text-sm font-semibold text-hp-text">Confirm New Password</label>
                                <input id="password_confirmation" name="password_confirmation" type="password"
                                    class="w-full rounded-xl border border-glass-border bg-glass px-4 py-3 text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none focus:ring-2 focus:ring-hp-green/25 dark:border-white/10 dark:bg-white/5">
                            </div>
                        </div>
                        <button type="submit" class="mt-4 cursor-pointer rounded-xl bg-hp-green px-4 py-3 font-semibold text-white transition-colors duration-200 hover:bg-hp-gold hover:text-hp-green-dark">Send verification code</button>
                    </form>
                </div>

                @if(session('staff_profile_change'))
                    <div class="modal fixed inset-0 z-[1000] hidden items-center justify-center is-open:flex" id="staffOtpModal" aria-hidden="true">
                        <div class="absolute inset-0 bg-[rgba(13,44,29,0.45)]" data-close-staff-otp></div>
                        <div class="modal__panel relative z-[1] w-full max-w-[520px] rounded-2xl bg-glass p-4 shadow-glass dark:bg-[rgba(30,30,30,0.95)]">
                            <div class="modal__header mb-3 flex items-center justify-between">
                                <h3 class="m-0 text-lg font-bold text-hp-text">Verify your changes</h3>
                                <button type="button" class="modal__close cursor-pointer border-0 bg-transparent text-xl text-hp-text" data-close-staff-otp>&times;</button>
                            </div>
                            <div class="modal__body">
                                <p class="my-1 mb-4 text-hp-text-muted">A 6-digit code was sent to your email. Enter it below to confirm the update.</p>
                                <form method="POST" action="{{ route('staff.settings.verify') }}" class="otp-form mt-3 flex flex-wrap gap-3">
                                    @csrf
                                    <input id="otp_code" name="code" type="tel" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" placeholder="Enter 6-digit code" required
                                        class="min-w-[220px] flex-1 rounded-xl border border-glass-border bg-glass px-4 py-3 text-hp-text transition-colors duration-300 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none focus:ring-2 focus:ring-hp-green/25 dark:border-white/10 dark:bg-white/5">
                                    <button type="submit" class="cursor-pointer rounded-xl bg-hp-green px-4 py-3 font-semibold text-white transition-colors duration-200 hover:bg-hp-gold hover:text-hp-green-dark">Verify</button>
                                </form>
                            </div>
                        </div>
                    </div>
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
            </main>
        </div>
    </div>

    <x-staff_chatbot />
</body>
</html>
