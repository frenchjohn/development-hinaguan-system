<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|playfair-display:600,700" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/admin_sidemenu.css',
        'resources/css/admin_css/admin_dashboard.css',
        'resources/css/admin_css/admin_settings.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/admin_js/admin_settings.js',
    ])
</head>
<body class="antialiased">
    <div class="dash-layout">
        <x-admin_sidemenu active="dashboard" userName="{{ session('auth_user.name') ?? 'Admin User' }}" userRole="Admin" />
        <div class="dash-main">
            <!-- Page transition overlay with skeleton loading -->
            <main class="dash-content">
                <x-header title="Settings" subtitle="Manage park settings and security" />
                <div class="admin-settings">
                    <!-- Horizontal Card Menu -->
                    <div class="admin-settings__menu" id="settingsMenu">
                        <div class="admin-settings__menu-card" data-target="park-settings">
                            <div class="admin-settings__menu-card__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <h3 class="admin-settings__menu-card__title">Park Settings</h3>
                            <p class="admin-settings__menu-card__text">Manage park configuration, hours, and fees</p>
                        </div>
                        <div class="admin-settings__menu-card" data-target="security">
                            <div class="admin-settings__menu-card__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <h3 class="admin-settings__menu-card__title">Security</h3>
                            <p class="admin-settings__menu-card__text">Update password and email settings</p>
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
                        <button type="button" class="admin-settings__back-btn" id="backToMenu">
                            <svg class="admin-settings__back-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Settings
                        </button>
                        <section class="dash-panel admin-settings__card">
                            <div class="admin-settings__card-header">
                                <div>
                                    <h2 class="admin-settings__card-title">Park Configuration</h2>
                                    <p class="admin-settings__card-text">Manage park information, operating hours, and fees.</p>
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

                    <!-- Security Content -->
                    <div class="admin-settings__content admin-settings__content--hidden" id="security">
                        <button type="button" class="admin-settings__back-btn" id="backToMenuFromSecurity">
                            <svg class="admin-settings__back-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Settings
                        </button>
                        
                        <div class="admin-settings__security-grid">
                            <!-- Change Password Section -->
                            <section class="dash-panel admin-settings__card admin-settings__security-card">
                                <div class="admin-settings__security-card__header">
                                    <div class="admin-settings__security-card__icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="admin-settings__card-title">Password</h2>
                                        <p class="admin-settings__card-text">Update your account password</p>
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
                            <section class="dash-panel admin-settings__card admin-settings__security-card">
                                <div class="admin-settings__security-card__header">
                                    <div class="admin-settings__security-card__icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="admin-settings__card-title">Email</h2>
                                        <p class="admin-settings__card-text">Update your email address</p>
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
</body>
</html>
