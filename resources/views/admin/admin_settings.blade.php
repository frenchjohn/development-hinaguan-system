<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Settings — Hinaguan Nature Park</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|playfair-display:600,700" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/sidemenu.css',
        'resources/css/admin_css/admin_dashboard.css',
        'resources/css/admin_css/admin_settings.css',
        'resources/components/css_js/header.js',
        'resources/js/admin_js/admin_settings.js',
    ])
</head>
<body class="antialiased">
    <div class="dash-layout">
        <x-admin_sidemenu active="settings" />
        <div class="dash-main">
            <!-- Page transition overlay with skeleton loading -->
            <div class="page-transition-overlay" id="pageTransitionOverlay">
                <div class="page-transition-skeleton">
                    <div class="skeleton skeleton--title page-transition-skeleton__header"></div>
                    <div class="page-transition-skeleton__stats">
                        <div class="skeleton skeleton--card page-transition-skeleton__stat"></div>
                        <div class="skeleton skeleton--card page-transition-skeleton__stat"></div>
                        <div class="skeleton skeleton--card page-transition-skeleton__stat"></div>
                    </div>
                    <div class="page-transition-skeleton__grid">
                        <div class="skeleton skeleton--panel page-transition-skeleton__panel"></div>
                        <div class="skeleton skeleton--panel page-transition-skeleton__panel"></div>
                    </div>
                </div>
            </div>
            <x-header title="Settings" subtitle="Admin configuration (prototype)" userName="Admin User" userRole="Administrator" :settingsUrl="route('admin.settings')" />
            <main class="dash-content">
                <div class="admin-settings">
                    <!-- Change Password Section -->
                    <section class="dash-panel admin-settings__card">
                        <div class="admin-settings__card-header">
                            <h2 class="admin-settings__card-title">Password</h2>
                            <button type="button" class="admin-settings__toggle-btn" id="togglePasswordBtn">
                                <span>Change Password</span>
                                <svg class="admin-settings__toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                            </button>
                        </div>
                        <form id="changePasswordForm" class="admin-settings__form admin-settings__form--hidden">
                            @csrf
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
                            <div class="admin-settings__form-actions">
                                <button type="submit" class="admin-settings__btn admin-settings__btn--primary">Send OTP Code</button>
                                <button type="button" class="admin-settings__btn admin-settings__btn--secondary" id="cancelPasswordBtn">Cancel</button>
                            </div>
                        </form>

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
                    <section class="dash-panel admin-settings__card">
                        <div class="admin-settings__card-header">
                            <h2 class="admin-settings__card-title">Email</h2>
                            <button type="button" class="admin-settings__toggle-btn" id="toggleEmailBtn">
                                <span>Change Email</span>
                                <svg class="admin-settings__toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="admin-settings__card-content">
                            <div class="admin-settings__email-display">
                                <p class="admin-settings__email-label">Current Email:</p>
                                <p class="admin-settings__email-value" id="currentEmailDisplay">{{ session('auth_user')['email'] ?? 'Not set' }}</p>
                            </div>
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
            </main>
        </div>
    </div>
</body>
</html>
