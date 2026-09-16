document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.querySelector('[data-password-toggle]');
    const password = document.querySelector('#password');

    if (toggle && password) {
        toggle.addEventListener('click', function () {
            const isPassword = password.type === 'password';
            password.type = isPassword ? 'text' : 'password';
            toggle.textContent = isPassword ? 'Hide' : 'Show';
        });
    }

    // Login submit button loading state & double-click prevention
    const loginForm = document.querySelector('.login-form');
    const submitBtn = document.querySelector('.login-form__submit');
    const submitText = submitBtn ? submitBtn.querySelector('.login-form__submit-text') : null;

    if (loginForm && submitBtn) {
        let isSubmitting = false;

        loginForm.addEventListener('submit', function (e) {
            // If HTML5 form validation fails, abort without locking the button
            if (typeof loginForm.checkValidity === 'function' && !loginForm.checkValidity()) {
                return;
            }

            // Prevent multiple submissions if already in progress
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }

            isSubmitting = true;
            submitBtn.classList.add('is-submitting');
            submitBtn.setAttribute('aria-busy', 'true');
            if (submitText) {
                submitText.textContent = 'Logging in...';
            }

            // Delay disabling the DOM element slightly so the browser finishes initiating native form submission
            setTimeout(function () {
                submitBtn.disabled = true;
            }, 10);
        });

        // Reset state if page is restored from back-forward cache (bfcache)
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                isSubmitting = false;
                submitBtn.disabled = false;
                submitBtn.classList.remove('is-submitting');
                submitBtn.removeAttribute('aria-busy');
                if (submitText) {
                    submitText.textContent = 'Log in';
                }
            }
        });
    }
});
