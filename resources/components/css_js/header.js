document.addEventListener('DOMContentLoaded', () => {
    // Sidebar toggle/overlay/resize are owned by sidemenu.js (delegated,
    // so they survive SPA page swaps that re-render the header). Keeping a
    // duplicate binding here would fight that handler (one sets the state,
    // the other toggles it) and make the hamburger a no-op.
    const layout = document.querySelector('.dash-layout');

    const closeSidebar = () => layout?.classList.remove('sidebar-open');

    window.addEventListener('resize', () => {
        if (window.innerWidth > 992) {
            closeSidebar();
        }
    });

    // ------------------------------------------------------------
    // Weather forecast widget. Uses document-level event delegation so
    // it keeps working after SPA page swaps re-render the header.
    // ------------------------------------------------------------
    const weatherBtn = () => document.getElementById('weatherBtn');
    const weatherDropdown = () => document.getElementById('weatherDropdown');

    const setWeatherOpen = (open) => {
        const btn = weatherBtn();
        const panel = weatherDropdown();
        if (!btn || !panel) return;
        btn.classList.toggle('is-active', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel.classList.toggle('is-open', open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
    };

    document.addEventListener('click', (e) => {
        const toggle = e.target.closest('#weatherBtn');
        if (toggle) {
            e.preventDefault();
            const panel = weatherDropdown();
            setWeatherOpen(panel ? !panel.classList.contains('is-open') : false);
            return;
        }

        const tab = e.target.closest('[data-weather-tab]');
        if (tab) {
            const index = tab.dataset.weatherTab;
            document.querySelectorAll('[data-weather-tab]').forEach((t) => {
                const active = t === tab;
                t.classList.toggle('is-active', active);
                t.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            document.querySelectorAll('[data-weather-hours]').forEach((panel) => {
                panel.classList.toggle('is-active', panel.dataset.weatherHours === index);
            });
            return;
        }

        if (e.target.closest('[data-weather-close]')) {
            setWeatherOpen(false);
            return;
        }

        // Clicking anywhere else closes the dropdown.
        const panel = weatherDropdown();
        if (panel && panel.classList.contains('is-open') && !e.target.closest('.dash-header__weather-wrap')) {
            setWeatherOpen(false);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && weatherDropdown()?.classList.contains('is-open')) {
            setWeatherOpen(false);
        }
    });

    // ------------------------------------------------------------
    // Live day + time on the weather pill. One shared interval so SPA
    // page swaps (which re-render the header) never stack timers — the
    // interval simply re-finds the fresh elements each tick.
    // ------------------------------------------------------------
    const updateWeatherClock = () => {
        const dayEl = document.getElementById('weatherClockDay');
        const timeEl = document.getElementById('weatherClockTime');
        if (!dayEl && !timeEl) return;
        const now = new Date();
        const day = now.toLocaleDateString('en-US', { weekday: 'long' });
        const time = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        if (dayEl && dayEl.textContent !== day) dayEl.textContent = day;
        if (timeEl && timeEl.textContent !== time) timeEl.textContent = time;
    };

    if (!window.__weatherClockInterval) {
        window.__weatherClockInterval = setInterval(updateWeatherClock, 1000);
    }
    updateWeatherClock();
});
