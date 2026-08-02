document.addEventListener('DOMContentLoaded', function () {
    // Drill-down navigation functionality
    const settingsMenu = document.getElementById('settingsMenu');
    const menuCards = document.querySelectorAll('.admin-settings__menu-card');
    const contentSections = document.querySelectorAll('.admin-settings__content');
    const backButtons = document.querySelectorAll('.admin-settings__back-btn');

    // Handle menu card clicks
    menuCards.forEach(card => {
        card.addEventListener('click', () => {
            const targetId = card.getAttribute('data-target');
            
            // Hide menu
            settingsMenu.classList.add('admin-settings__menu--hidden');
            
            // Show target content
            contentSections.forEach(section => {
                section.classList.add('admin-settings__content--hidden');
            });
            const targetContent = document.getElementById(targetId);
            if (targetContent) {
                targetContent.classList.remove('admin-settings__content--hidden');
            }
        });
    });

    // Handle back button clicks
    backButtons.forEach(backBtn => {
        backBtn.addEventListener('click', () => {
            // Hide all content sections
            contentSections.forEach(section => {
                section.classList.add('admin-settings__content--hidden');
            });
            
            // Show menu
            settingsMenu.classList.remove('admin-settings__menu--hidden');
        });
    });

    // Park Settings Edit functionality
    const editParkSettingsBtn = document.getElementById('editParkSettingsBtn');
    const cancelParkSettingsBtn = document.getElementById('cancelParkSettingsBtn');
    const parkSettingsForm = document.getElementById('parkSettingsForm');
    const parkSettingsFormActions = document.getElementById('parkSettingsFormActions');
    const parkSettingsInputs = parkSettingsForm ? parkSettingsForm.querySelectorAll('input') : [];
    const parkSettingsSuccessModal = document.getElementById('parkSettingsSuccessModal');
    const closeParkSettingsSuccessModal = document.getElementById('closeParkSettingsSuccessModal');

    // Store original values for cancel functionality
    let originalParkSettingsValues = {};

    editParkSettingsBtn?.addEventListener('click', () => {
        // Store original values
        parkSettingsInputs.forEach(input => {
            originalParkSettingsValues[input.id] = input.value;
            input.disabled = false;
        });

        // Show form actions, hide edit button
        parkSettingsFormActions.classList.remove('admin-settings__form-actions--hidden');
        editParkSettingsBtn.classList.add('admin-settings__btn--hidden');
    });

    cancelParkSettingsBtn?.addEventListener('click', () => {
        // Restore original values
        parkSettingsInputs.forEach(input => {
            input.value = originalParkSettingsValues[input.id] || '';
            input.disabled = true;
        });

        // Hide form actions, show edit button
        parkSettingsFormActions.classList.add('admin-settings__form-actions--hidden');
        editParkSettingsBtn.classList.remove('admin-settings__btn--hidden');
    });

    // Handle park settings form submission
    parkSettingsForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(parkSettingsForm);
        
        try {
            const response = await fetch(parkSettingsForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });
            
            if (response.ok) {
                // Show success modal
                parkSettingsSuccessModal.style.display = 'flex';
                
                // Disable fields and hide form actions
                parkSettingsInputs.forEach(input => {
                    input.disabled = true;
                });
                parkSettingsFormActions.classList.add('admin-settings__form-actions--hidden');
                editParkSettingsBtn.classList.remove('admin-settings__btn--hidden');
            } else {
                // Handle error
                const errorData = await response.json();
                console.error('Error updating park settings:', errorData);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });

    // Close success modal
    closeParkSettingsSuccessModal?.addEventListener('click', () => {
        parkSettingsSuccessModal.style.display = 'none';
    });

    // Close modal when clicking outside
    parkSettingsSuccessModal?.addEventListener('click', (e) => {
        if (e.target === parkSettingsSuccessModal) {
            parkSettingsSuccessModal.style.display = 'none';
        }
    });

    // Page transition with skeleton loading
    const pageTransitionOverlay = document.getElementById('pageTransitionOverlay');
    
    // Handle outgoing page transition (when clicking navigation links)
    const transitionLinks = document.querySelectorAll('[data-page-transition]');
    transitionLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetUrl = this.href;
            
            // Immediately update active state on sidemenu
            const allLinks = document.querySelectorAll('.dash-sidebar__link');
            allLinks.forEach(l => l.classList.remove('is-active'));
            this.classList.add('is-active');
            
            // Show skeleton overlay immediately
            if (pageTransitionOverlay) {
                pageTransitionOverlay.classList.add('is-active');
                
                // Navigate after a short delay to allow the overlay to appear
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 100);
            } else {
                // Fallback if overlay doesn't exist
                window.location.href = targetUrl;
            }
        });
    });

    const changePasswordForm = document.getElementById('changePasswordForm');
    const changeEmailForm = document.getElementById('changeEmailForm');
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const toggleEmailBtn = document.getElementById('toggleEmailBtn');
    const cancelPasswordBtn = document.getElementById('cancelPasswordBtn');
    const cancelEmailBtn = document.getElementById('cancelEmailBtn');
    const otpPasswordModal = document.getElementById('otpPasswordModal');
    const otpEmailModal = document.getElementById('otpEmailModal');
    const verifyPasswordOtpBtn = document.getElementById('verifyPasswordOtpBtn');
    const verifyEmailOtpBtn = document.getElementById('verifyEmailOtpBtn');
    const cancelPasswordOtpBtn = document.getElementById('cancelPasswordOtpBtn');
    const cancelEmailOtpBtn = document.getElementById('cancelEmailOtpBtn');
    const otpPasswordCodeInput = document.getElementById('otpPasswordCode');
    const otpEmailCodeInput = document.getElementById('otpEmailCode');

    // Get submit buttons
    const passwordSubmitBtn = changePasswordForm ? changePasswordForm.querySelector('button[type="submit"]') : null;
    const emailSubmitBtn = changeEmailForm ? changeEmailForm.querySelector('button[type="submit"]') : null;

    let pendingPasswordData = null;
    let pendingEmailData = null;
    let isPasswordLoading = false;
    let isEmailLoading = false;

    // ===== PASSWORD SECTION =====

    // Toggle password form visibility
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', function () {
            changePasswordForm.classList.toggle('admin-settings__form--hidden');
            togglePasswordBtn.classList.toggle('active');
        });
    }

    // Cancel password form
    if (cancelPasswordBtn) {
        cancelPasswordBtn.addEventListener('click', function () {
            changePasswordForm.classList.add('admin-settings__form--hidden');
            togglePasswordBtn.classList.remove('active');
            changePasswordForm.reset();
            clearErrors(['currentPasswordError', 'newPasswordError', 'confirmPasswordError']);
        });
    }

    // Handle Change Password Form
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            // Clear previous errors
            clearErrors(['currentPasswordError', 'newPasswordError', 'confirmPasswordError']);

            // Validation
            if (!currentPassword || !newPassword || !confirmPassword) {
                showError('currentPasswordError', 'All fields are required');
                return;
            }

            if (newPassword.length < 8) {
                showError('newPasswordError', 'Password must be at least 8 characters');
                return;
            }

            if (newPassword !== confirmPassword) {
                showError('confirmPasswordError', 'Passwords do not match');
                return;
            }

            // Send OTP
            try {
                isPasswordLoading = true;
                passwordSubmitBtn.disabled = true;
                passwordSubmitBtn.classList.add('loading');
                const originalText = passwordSubmitBtn.textContent;
                passwordSubmitBtn.textContent = 'Sending...';

                const response = await fetch('/admin/send-password-otp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    },
                    body: JSON.stringify({
                        current_password: currentPassword,
                        new_password: newPassword,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    if (data.errors) {
                        Object.keys(data.errors).forEach((key) => {
                            showError(key + 'Error', data.errors[key][0]);
                        });
                    } else {
                        showError('currentPasswordError', data.message || 'Failed to send OTP');
                    }
                    isPasswordLoading = false;
                    passwordSubmitBtn.disabled = false;
                    passwordSubmitBtn.classList.remove('loading');
                    passwordSubmitBtn.textContent = originalText;
                    return;
                }

                // Store password data for OTP verification
                pendingPasswordData = {
                    current_password: currentPassword,
                    new_password: newPassword,
                };

                // Show OTP Modal
                otpPasswordModal.style.display = 'flex';
                otpPasswordCodeInput.focus();
                isPasswordLoading = false;
                passwordSubmitBtn.disabled = false;
                passwordSubmitBtn.classList.remove('loading');
                passwordSubmitBtn.textContent = originalText;
            } catch (error) {
                console.error('Error:', error);
                showError('currentPasswordError', 'An error occurred. Please try again.');
                isPasswordLoading = false;
                passwordSubmitBtn.disabled = false;
                passwordSubmitBtn.classList.remove('loading');
                passwordSubmitBtn.textContent = originalText;
            }
        });
    }

    // Handle Password OTP Verification
    if (verifyPasswordOtpBtn) {
        verifyPasswordOtpBtn.addEventListener('click', async function () {
            const otpCode = otpPasswordCodeInput.value.trim();

            // Clear previous errors
            document.getElementById('otpPasswordError').textContent = '';

            if (!otpCode || otpCode.length !== 6 || isNaN(otpCode)) {
                showError('otpPasswordError', 'Please enter a valid 6-digit OTP code');
                return;
            }

            try {
                verifyPasswordOtpBtn.disabled = true;
                verifyPasswordOtpBtn.classList.add('loading');

                const response = await fetch('/admin/verify-password-otp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    },
                    body: JSON.stringify({
                        otp_code: otpCode,
                        new_password: pendingPasswordData.new_password,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    showError('otpPasswordError', data.message || 'OTP verification failed');
                    verifyPasswordOtpBtn.disabled = false;
                    verifyPasswordOtpBtn.classList.remove('loading');
                    return;
                }

                // Success
                otpPasswordModal.style.display = 'none';
                changePasswordForm.reset();
                changePasswordForm.classList.add('admin-settings__form--hidden');
                togglePasswordBtn.classList.remove('active');
                pendingPasswordData = null;
                otpPasswordCodeInput.value = '';
                verifyPasswordOtpBtn.disabled = false;
                verifyPasswordOtpBtn.classList.remove('loading');
                showSuccessMessage('Password changed successfully!');
            } catch (error) {
                console.error('Error:', error);
                showError('otpPasswordError', 'An error occurred. Please try again.');
                verifyPasswordOtpBtn.disabled = false;
                verifyPasswordOtpBtn.classList.remove('loading');
            }
        });
    }

    // Handle Cancel Password OTP
    if (cancelPasswordOtpBtn) {
        cancelPasswordOtpBtn.addEventListener('click', function () {
            otpPasswordModal.style.display = 'none';
            otpPasswordCodeInput.value = '';
            document.getElementById('otpPasswordError').textContent = '';
            pendingPasswordData = null;
        });
    }

    // ===== EMAIL SECTION =====

    // Toggle email form visibility
    if (toggleEmailBtn) {
        toggleEmailBtn.addEventListener('click', function () {
            changeEmailForm.classList.toggle('admin-settings__form--hidden');
            toggleEmailBtn.classList.toggle('active');
        });
    }

    // Cancel email form
    if (cancelEmailBtn) {
        cancelEmailBtn.addEventListener('click', function () {
            changeEmailForm.classList.add('admin-settings__form--hidden');
            toggleEmailBtn.classList.remove('active');
            changeEmailForm.reset();
            document.getElementById('newEmailError').textContent = '';
        });
    }

    // Handle Change Email Form
    if (changeEmailForm) {
        changeEmailForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const newEmail = document.getElementById('newEmail').value;

            // Clear previous errors
            document.getElementById('newEmailError').textContent = '';

            // Validation
            if (!newEmail) {
                showError('newEmailError', 'Email is required');
                return;
            }

            if (!isValidEmail(newEmail)) {
                showError('newEmailError', 'Please enter a valid email address');
                return;
            }

            // Send OTP
            try {
                isEmailLoading = true;
                emailSubmitBtn.disabled = true;
                emailSubmitBtn.classList.add('loading');
                const originalText = emailSubmitBtn.textContent;
                emailSubmitBtn.textContent = 'Sending...';

                const response = await fetch('/admin/send-email-otp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    },
                    body: JSON.stringify({
                        new_email: newEmail,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    showError('newEmailError', data.message || 'Failed to send OTP');
                    isEmailLoading = false;
                    emailSubmitBtn.disabled = false;
                    emailSubmitBtn.classList.remove('loading');
                    emailSubmitBtn.textContent = originalText;
                    return;
                }

                // Store email data for OTP verification
                pendingEmailData = {
                    new_email: newEmail,
                };

                // Show OTP Modal
                otpEmailModal.style.display = 'flex';
                otpEmailCodeInput.focus();
                isEmailLoading = false;
                emailSubmitBtn.disabled = false;
                emailSubmitBtn.classList.remove('loading');
                emailSubmitBtn.textContent = originalText;
            } catch (error) {
                console.error('Error:', error);
                showError('newEmailError', 'An error occurred. Please try again.');
                isEmailLoading = false;
                emailSubmitBtn.disabled = false;
                emailSubmitBtn.classList.remove('loading');
                emailSubmitBtn.textContent = originalText;
            }
        });
    }

    // Handle Email OTP Verification
    if (verifyEmailOtpBtn) {
        verifyEmailOtpBtn.addEventListener('click', async function () {
            const otpCode = otpEmailCodeInput.value.trim();

            // Clear previous errors
            document.getElementById('otpEmailError').textContent = '';

            if (!otpCode || otpCode.length !== 6 || isNaN(otpCode)) {
                showError('otpEmailError', 'Please enter a valid 6-digit OTP code');
                return;
            }

            try {
                verifyEmailOtpBtn.disabled = true;
                verifyEmailOtpBtn.classList.add('loading');

                const response = await fetch('/admin/verify-email-otp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    },
                    body: JSON.stringify({
                        otp_code: otpCode,
                        new_email: pendingEmailData.new_email,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    showError('otpEmailError', data.message || 'OTP verification failed');
                    verifyEmailOtpBtn.disabled = false;
                    verifyEmailOtpBtn.classList.remove('loading');
                    return;
                }

                // Success
                otpEmailModal.style.display = 'none';
                document.getElementById('currentEmailDisplay').textContent = pendingEmailData.new_email;
                changeEmailForm.reset();
                changeEmailForm.classList.add('admin-settings__form--hidden');
                toggleEmailBtn.classList.remove('active');
                pendingEmailData = null;
                otpEmailCodeInput.value = '';
                verifyEmailOtpBtn.disabled = false;
                verifyEmailOtpBtn.classList.remove('loading');
                showSuccessMessage('Email changed successfully!');
            } catch (error) {
                console.error('Error:', error);
                showError('otpEmailError', 'An error occurred. Please try again.');
                verifyEmailOtpBtn.disabled = false;
                verifyEmailOtpBtn.classList.remove('loading');
            }
        });
    }

    // Handle Cancel Email OTP
    if (cancelEmailOtpBtn) {
        cancelEmailOtpBtn.addEventListener('click', function () {
            otpEmailModal.style.display = 'none';
            otpEmailCodeInput.value = '';
            document.getElementById('otpEmailError').textContent = '';
            pendingEmailData = null;
        });
    }

    // ===== HELPER FUNCTIONS =====

    function showError(elementId, message) {
        const errorElement = document.getElementById(elementId);
        if (errorElement) {
            errorElement.textContent = message;
        }
    }

    function clearErrors(errorIds) {
        errorIds.forEach((id) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = '';
            }
        });
    }

    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    function showSuccessMessage(message) {
        // Create a temporary success message
        const successDiv = document.createElement('div');
        successDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #10b981;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            z-index: 2000;
            animation: slideIn 0.3s ease;
        `;
        successDiv.textContent = message;
        document.body.appendChild(successDiv);

        setTimeout(() => {
            successDiv.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                successDiv.remove();
            }, 300);
        }, 3000);
    }

    // Allow OTP input only for numbers
    if (otpPasswordCodeInput) {
        otpPasswordCodeInput.addEventListener('keypress', function (e) {
            if (!/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });
    }

    if (otpEmailCodeInput) {
        otpEmailCodeInput.addEventListener('keypress', function (e) {
            if (!/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });
    }
});
