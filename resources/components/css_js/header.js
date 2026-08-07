document.addEventListener('DOMContentLoaded', () => {
    const layout = document.querySelector('.dash-layout');
    const sidebarToggle = document.querySelector('[data-dash-sidebar-toggle]');
    const overlay = document.querySelector('.dash-sidebar__overlay');

    const closeSidebar = () => layout?.classList.remove('sidebar-open');
    const openSidebar = () => layout?.classList.add('sidebar-open');

    sidebarToggle?.addEventListener('click', () => {
        if (layout?.classList.contains('sidebar-open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    overlay?.addEventListener('click', closeSidebar);

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
});
