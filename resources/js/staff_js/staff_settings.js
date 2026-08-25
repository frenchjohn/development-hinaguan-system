window.AppPage = window.AppPage || {};
window.AppPage['staff_settings'] = function () {


    const form = document.querySelector('.settings-form');
    if (!form) return;

    form.addEventListener('submit', (event) => {
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('password_confirmation');

        if (password && confirmPassword && password.value && password.value !== confirmPassword.value) {
            event.preventDefault();
            alert('Passwords do not match.');
        }
    });
    // OTP modal handling: open if present and wire close buttons
    const otpModal = document.getElementById('staffOtpModal');
    if (otpModal) {
        const closeEls = otpModal.querySelectorAll('[data-close-staff-otp]');
        closeEls.forEach(el => el.addEventListener('click', () => {
            otpModal.classList.remove('is-open');
            otpModal.setAttribute('aria-hidden', 'true');
        }));
        otpModal.addEventListener('click', (e) => {
            if (e.target === otpModal) {
                otpModal.classList.remove('is-open');
                otpModal.setAttribute('aria-hidden', 'true');
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['staff_settings']());