window.AppPage = window.AppPage || {};
window.AppPage['staff_settings'] = function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const alertContainer = document.getElementById('staffSettingsAlertContainer');

    const showBanner = (message, isError = false) => {
        if (!alertContainer) return;
        alertContainer.innerHTML = `<div class="mb-4 rounded-xl border ${
            isError
                ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-500/30 dark:bg-red-950/50 dark:text-red-200'
                : 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-950/50 dark:text-emerald-200'
        } px-4 py-3 text-sm font-medium">${message}</div>`;
    };

    const setModalError = (elId, message) => {
        const el = elId ? document.getElementById(elId) : null;
        if (!el) return;
        if (message) {
            el.textContent = message;
            el.classList.remove('hidden');
        } else {
            el.textContent = '';
            el.classList.add('hidden');
        }
    };

    const setModalLoading = (modal, isLoading, loadingLabel, idleLabel) => {
        if (!modal) return;
        modal.dataset.submitting = isLoading ? 'true' : 'false';
        modal.querySelectorAll('[data-close-staff-modal], [data-close-staff-otp]').forEach((btn) => {
            if (btn.tagName === 'BUTTON') btn.disabled = isLoading;
        });
        const submitBtn = modal.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = isLoading;
            submitBtn.textContent = isLoading ? loadingLabel : idleLabel;
        }
    };

    const openModal = (modal) => {
        if (!modal) return;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        const firstInput = modal.querySelector('input:not([type="hidden"])');
        if (firstInput) setTimeout(() => firstInput.focus(), 50);
    };

    const closeModal = (modal, force = false) => {
        if (!modal || (!force && modal.dataset.submitting === 'true')) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        window.dispatchEvent(new CustomEvent('modal:closed'));
    };

    document.querySelectorAll('[data-open-staff-modal]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const modal = document.getElementById(btn.getAttribute('data-open-staff-modal'));
            const form = modal?.querySelector('form');
            if (form?.dataset.errorTarget) setModalError(form.dataset.errorTarget, '');
            openModal(modal);
        });
    });

    document.querySelectorAll('[data-close-staff-modal]').forEach((el) => {
        el.addEventListener('click', () => {
            closeModal(document.getElementById(el.getAttribute('data-close-staff-modal')));
        });
    });

    // Password visibility toggles
    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.getAttribute('data-toggle-password'));
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });

    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('password_confirmation');
    const passwordForm = document.getElementById('staffChangePasswordForm');

    password?.addEventListener('input', () => setModalError('staffSettingsPasswordError', ''));
    confirmPassword?.addEventListener('input', () => setModalError('staffSettingsPasswordError', ''));

    const otpModal = document.getElementById('staffOtpModal');
    const otpForm = document.getElementById('staffOtpForm');
    const otpCodeInput = document.getElementById('otp_code');
    const otpSubtitle = document.getElementById('staffOtpSubtitle');

    otpCodeInput?.addEventListener('input', () => setModalError('staffOtpError', ''));

    // Async modal form submit (Send OTP without refreshing page)
    document.querySelectorAll('.staff-settings-modal-form').forEach((form) => {
        const errorTarget = form.dataset.errorTarget;
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            setModalError(errorTarget, '');

            if (form === passwordForm) {
                if (!password?.value || password.value.length < 8) {
                    setModalError(errorTarget, 'Password must be at least 8 characters.');
                    password?.focus();
                    return;
                }
                if (password.value !== confirmPassword?.value) {
                    setModalError(errorTarget, 'Passwords do not match.');
                    confirmPassword?.focus();
                    return;
                }
            }

            const modal = form.closest('.modal');
            setModalLoading(modal, true, 'Sending...', 'Send OTP Code');

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form),
                });
                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    const firstErr = data.errors ? Object.values(data.errors).flat()[0] : null;
                    setModalError(errorTarget, firstErr || data.message || 'Unable to send verification code.');
                    return;
                }

                closeModal(modal, true);
                if (otpCodeInput) otpCodeInput.value = '';
                setModalError('staffOtpError', '');
                if (otpSubtitle && data.email) {
                    otpSubtitle.textContent = `Enter the 6-digit code sent to ${data.email}.`;
                }
                openModal(otpModal);
            } catch (_) {
                setModalError(errorTarget, 'Network error while sending verification code.');
            } finally {
                setModalLoading(modal, false, 'Sending...', 'Send OTP Code');
            }
        });
    });

    // Async OTP verify submit (Verify & Save without refreshing page)
    if (otpForm && otpModal) {
        otpForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            setModalError('staffOtpError', '');
            setModalLoading(otpModal, true, 'Verifying...', 'Verify & Save');

            try {
                const res = await fetch(otpForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(otpForm),
                });
                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    const firstErr = data.errors ? Object.values(data.errors).flat()[0] : null;
                    setModalError('staffOtpError', firstErr || data.message || 'The verification code is invalid.');
                    otpCodeInput?.focus();
                    return;
                }

                // Update visible profile text & hidden inputs in-place
                if (data.user) {
                    const { name, email } = data.user;
                    const initials = (name || 'ST').trim().split(/\s+/).slice(0, 2).map(p => p[0]?.toUpperCase() || '').join('') || 'ST';
                    document.querySelectorAll('[data-staff-name-display]').forEach(el => { el.textContent = name; });
                    document.querySelectorAll('[data-staff-email-display]').forEach(el => { el.textContent = email; });
                    document.querySelectorAll('[data-staff-initials]').forEach(el => { el.textContent = initials; });
                    document.querySelectorAll('.staff-settings-modal-form input[name="name"]').forEach(el => { el.value = name; });
                    document.querySelectorAll('.staff-settings-modal-form input[name="email"]').forEach(el => { el.value = email; });
                }

                if (password) password.value = '';
                if (confirmPassword) confirmPassword.value = '';
                if (otpCodeInput) otpCodeInput.value = '';
                closeModal(otpModal, true);
                showBanner(data.message || 'Your account details were updated successfully.', false);
            } catch (_) {
                setModalError('staffOtpError', 'Network error while verifying code.');
            } finally {
                setModalLoading(otpModal, false, 'Verifying...', 'Verify & Save');
            }
        });

        otpModal.querySelectorAll('[data-close-staff-otp]').forEach((el) => {
            el.addEventListener('click', () => closeModal(otpModal));
        });
        otpModal.addEventListener('click', (e) => {
            if (e.target === otpModal) closeModal(otpModal);
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.modal.is-open').forEach((modal) => closeModal(modal));
    });
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['staff_settings']());
