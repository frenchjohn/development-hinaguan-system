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
        'resources/css/staff_css/staff_settings.css',
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

            <main class="dash-content">
                <x-header title="Settings" subtitle="Update your profile securely" />

                @if(session('success'))
                    <div class="alert alert--success">{{ session('success') }}</div>
                @endif

                @if(session('error'))
                    <div class="alert alert--error">{{ session('error') }}</div>
                @endif

                    <form method="POST" action="{{ route('staff.settings.update') }}" class="settings-form">
                        @csrf
                        <div class="settings-form__grid">
                            <div class="settings-form__field">
                                <label for="name">Full Name</label>
                                <input id="name" name="name" type="text" value="{{ session('auth_user.name') ?? '' }}" required>
                            </div>
                            <div class="settings-form__field">
                                <label for="email">Email Address</label>
                                <input id="email" name="email" type="email" value="{{ session('auth_user.email') ?? '' }}" required>
                            </div>
                            <div class="settings-form__field">
                                <label for="password">New Password</label>
                                <input id="password" name="password" type="password" placeholder="Leave blank to keep current password">
                            </div>
                            <div class="settings-form__field">
                                <label for="password_confirmation">Confirm New Password</label>
                                <input id="password_confirmation" name="password_confirmation" type="password">
                            </div>
                        </div>
                        <button type="submit" class="btn btn--primary">Send verification code</button>
                    </form>

                    @if(session('staff_profile_change'))
                        <div class="modal" id="staffOtpModal" aria-hidden="true">
                            <div class="modal__backdrop" data-close-staff-otp></div>
                            <div class="modal__panel">
                                <div class="modal__header">
                                    <h3>Verify your changes</h3>
                                    <button type="button" class="modal__close" data-close-staff-otp>&times;</button>
                                </div>
                                <div class="modal__body">
                                    <p>A 6-digit code was sent to your email. Enter it below to confirm the update.</p>
                                    <form method="POST" action="{{ route('staff.settings.verify') }}" class="otp-form">
                                        @csrf
                                        <input id="otp_code" name="code" type="tel" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" placeholder="Enter 6-digit code" required>
                                        <button type="submit" class="btn btn--primary">Verify</button>
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
                </section>
            </main>
        </div>
    </div>

    <x-staff_chatbot />
</body>
</html>
