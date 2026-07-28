document.addEventListener('DOMContentLoaded', () => {
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
});
