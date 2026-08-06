window.AppPage = window.AppPage || {};
window.AppPage['staff_dashboard'] = function () {
    // Animate the vertical bars and horizontal fills with a small stagger.
    const animated = Array.from(document.querySelectorAll('.dash-barchart__bar, .dash-hbar__fill'));

    // Trigger on the next frame so the browser has painted the 0-height state first.
    requestAnimationFrame(() => {
        animated.forEach((el, i) => {
            el.style.transitionDelay = `${(i % 14) * 55}ms`;
            el.classList.add('is-animated');
        });
    });
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['staff_dashboard']());