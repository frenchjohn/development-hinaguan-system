// ============================================================
// Sidebar navigation is always instant.
// ============================================================
window.AppBusy = {
    get isBusy() { return false; },
    get count() { return 0; },
    begin() { },
    end() { },
    reset() { },
};

// Sidemenu toggle functionality and instant navigation
window.addEventListener('DOMContentLoaded', function () {
    const dashLayout = document.querySelector('.dash-layout');
    const userToggle = document.querySelector('[data-dash-user-toggle]');
    const themeToggle = document.querySelector('[data-theme-toggle]');

    // Initialize theme from localStorage
    const storedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', storedTheme);

    // Update theme text based on current theme
    function updateThemeText() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        document.querySelectorAll('.dash-sidebar__theme-text, [data-theme-text]').forEach(el => {
            el.textContent = currentTheme === 'light' ? 'Light Mode' : 'Dark Mode';
        });
    }

    // Helper to close user profile dropdown
    function closeUserDropdown() {
        document.querySelectorAll('[data-dash-user-dropdown], [data-dash-user-toggle] + div').forEach(dropdown => {
            dropdown.classList.remove('is-open');
        });
        document.querySelectorAll('[data-dash-user-toggle]').forEach(toggle => {
            toggle.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        });
    }

    // Helper to toggle user profile dropdown
    function toggleUserDropdown(toggleBtn) {
        const dropdown = toggleBtn.nextElementSibling;
        if (!dropdown) return;
        const isOpen = dropdown.classList.contains('is-open');
        if (isOpen) {
            dropdown.classList.remove('is-open');
            toggleBtn.classList.remove('is-open');
            toggleBtn.setAttribute('aria-expanded', 'false');
        } else {
            dropdown.classList.add('is-open');
            toggleBtn.classList.add('is-open');
            toggleBtn.setAttribute('aria-expanded', 'true');
            updateThemeText();
        }
    }

    // Call initially after DOM is ready
    setTimeout(updateThemeText, 0);

    if (!dashLayout) {
        console.error('Sidemenu: dash-layout element not found');
        return;
    }

    function toggleSidebar(e) {
        if (e) e.preventDefault();

        const isMobile = window.innerWidth <= 992;

        if (isMobile) {
            dashLayout.classList.toggle('sidebar-open');
        } else {
            dashLayout.classList.toggle('sidebar-collapsed');
        }
        syncOverlay();
    }

    function closeSidebar() {
        const isMobile = window.innerWidth <= 992;

        if (isMobile) {
            dashLayout.classList.remove('sidebar-open');
        } else {
            dashLayout.classList.remove('sidebar-collapsed');
        }
        syncOverlay();
    }

    // Show/hide the mobile backdrop that sits behind the open sidebar.
    function syncOverlay() {
        const overlay = document.querySelector('.dash-sidebar__overlay, [data-sidebar-overlay]');
        if (!overlay) return;
        const open = dashLayout.classList.contains('sidebar-open');
        overlay.classList.toggle('is-open', open);
        overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
    }

    // Global click listener for sidebar toggles, user profile dropdown, theme toggle, and logout modal
    document.addEventListener('click', (e) => {
        // Sidebar toggle
        if (e.target.closest('[data-dash-sidebar-toggle]')) {
            toggleSidebar(e);
            return;
        }

        // Sidebar overlay (mobile)
        if (e.target.closest('.dash-sidebar__overlay, [data-sidebar-overlay]')) {
            closeSidebar();
            return;
        }

        // Profile toggle button
        const userToggleBtn = e.target.closest('[data-dash-user-toggle]');
        if (userToggleBtn) {
            e.preventDefault();
            e.stopPropagation();
            toggleUserDropdown(userToggleBtn);
            return;
        }

        // Theme toggle button
        const themeBtn = e.target.closest('[data-theme-toggle]');
        if (themeBtn) {
            e.preventDefault();
            e.stopPropagation();
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeText();
            return;
        }

        // Logout confirm trigger (opens confirmation modal, closes profile dropdown)
        if (e.target.closest('[data-logout-confirm]')) {
            e.preventDefault();
            e.stopPropagation();
            closeUserDropdown();
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
            }
            return;
        }

        // Logout cancel / backdrop trigger
        if (e.target.closest('[data-logout-cancel]')) {
            e.preventDefault();
            e.stopPropagation();
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            }
            return;
        }

        // Logout confirm submit button loading state
        const confirmSubmitBtn = e.target.closest('.logout-modal__btn--confirm');
        if (confirmSubmitBtn) {
            confirmSubmitBtn.classList.add('is-loading');
        }

        // Auto-close user profile dropdown when clicking outside
        const openDropdown = document.querySelector('[data-dash-user-dropdown].is-open, [data-dash-user-toggle] + div.is-open');
        if (openDropdown) {
            const toggle = document.querySelector('[data-dash-user-toggle]');
            const isClickInsideToggle = toggle && toggle.contains(e.target);
            const isClickInsideDropdown = openDropdown.contains(e.target);
            if (!isClickInsideToggle && !isClickInsideDropdown) {
                closeUserDropdown();
            }
        }
    });

    // Close on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (window.innerWidth <= 992 && dashLayout.classList.contains('sidebar-open')) {
                closeSidebar();
            }
            const modal = document.getElementById('logoutModal');
            if (modal && modal.classList.contains('is-open')) {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            }
            closeUserDropdown();
        }
    });

    // Handle window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth > 992) {
            dashLayout.classList.remove('sidebar-open');
        } else {
            dashLayout.classList.remove('sidebar-collapsed');
        }
    });

    // ============================================================
    // INSTANT NAVIGATION (SPA-style page swapping with 0ms DOM swap)
    // ============================================================

    const SPA_PAGE_KEYS = {
        '/staff/dashboard': 'staff_dashboard',
        '/staff/reservations': 'staff_reservations',
        '/staff/occupancy-monitor': 'staff_occupancy_monitor',
        '/staff/reports': 'staff_reports',
        '/staff/check-ins': 'staff_check_ins',
        '/staff/records': 'staff_records',
        '/staff/settings': 'staff_settings',
        '/admin/dashboard': 'admin_dashboard',
        '/admin/amenities': 'admin_amenitiesmanagement',
        '/admin/reports': 'admin_reports',
        '/admin/feedback': 'admin_feedback',
        '/admin/users': 'admin_usermanagement',
        '/admin/settings': 'admin_settings',
    };

    // Every page-script src already present in this document.
    const loadedScriptSrcs = new Set(
        Array.from(document.querySelectorAll('script[src]')).map(s => s.src)
    );

    // Mark current inline styles and body scripts
    document.querySelectorAll('head > style').forEach(style => {
        style.setAttribute('data-page-style', '');
    });
    document.body.querySelectorAll('script:not([src])').forEach(script => {
        script.setAttribute('data-spa-data', '');
    });

    // In-memory DOM cache for instant 0ms transitions
    const pageCache = new Map();
    const inFlightFetches = new Map();

    // Cache the initial document immediately
    const initialDoc = document.cloneNode(true);
    pageCache.set(window.location.pathname, initialDoc);

    // Inline <script> tags inserted via innerHTML never execute, so recreate them.
    function rehydrateScripts(container) {
        container.querySelectorAll('script:not([src])').forEach((oldScript) => {
            if (oldScript.closest('header, .dash-header, [data-dash-header]')) return;
            const fresh = document.createElement('script');
            fresh.textContent = oldScript.textContent;
            oldScript.replaceWith(fresh);
        });
    }

    // Bring over the new page's stylesheets, inline styles, and JS bundles.
    async function loadPageAssets(doc) {
        // Stylesheet links
        doc.querySelectorAll('head link[rel="stylesheet"]').forEach(link => {
            const href = link.getAttribute('href');
            if (!href || document.querySelector('head link[rel="stylesheet"][href="' + href + '"]')) return;
            const fresh = document.createElement('link');
            fresh.rel = 'stylesheet';
            fresh.href = href;
            document.head.appendChild(fresh);
        });

        // Page-specific inline styles
        document.querySelectorAll('head > style[data-page-style]').forEach(s => s.remove());
        doc.querySelectorAll('head > style').forEach(style => {
            const fresh = document.createElement('style');
            fresh.setAttribute('data-page-style', '');
            fresh.textContent = style.textContent;
            document.head.appendChild(fresh);
        });

        // Page JS bundles
        const missing = Array.from(doc.querySelectorAll('head script[type="module"][src]'))
            .map(s => s.getAttribute('src'))
            .filter(src => src && !loadedScriptSrcs.has(new URL(src, window.location.origin).href));

        for (const src of new Set(missing)) {
            try {
                const abs = new URL(src, window.location.origin).href;
                await import(/* @vite-ignore */ abs);
                loadedScriptSrcs.add(abs);
            } catch (err) {
                console.warn('[instant-nav] could not load script', src, err);
            }
        }
    }

    // Refresh page data scripts
    function syncBodyDataScripts(doc) {
        document.body.querySelectorAll('script[data-spa-data]').forEach(s => s.remove());
        doc.querySelectorAll('body > script:not([src])').forEach(script => {
            const fresh = document.createElement('script');
            fresh.setAttribute('data-spa-data', '');
            fresh.textContent = script.textContent;
            document.body.appendChild(fresh);
        });
    }

    function updateActiveLink(url) {
        const targetPath = new URL(url, window.location.origin).pathname;
        document.querySelectorAll('.dash-sidebar__link, #dashSidebar .nav-link').forEach(link => {
            const href = link.getAttribute('href');
            if (!href) return;
            let linkPath = href;
            try { linkPath = new URL(href, window.location.origin).pathname; } catch (e) { /* ignore */ }
            link.classList.toggle('is-active', linkPath === targetPath);
        });
    }

    let entranceTimer = null;
    function runContentEntrance() {
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const main = document.querySelector('main.dash-content');
        if (!main) return;

        const header = main.querySelector(':scope > header, :scope > .dash-header, [data-dash-header]');
        const targets = Array.from(main.children).filter((el) => el !== header && el.offsetParent !== null);
        if (!targets.length) return;

        clearTimeout(entranceTimer);

        const apply = (style, value) => targets.forEach((el) => { el.style[style] = value; });
        apply('opacity', '0');
        apply('transform', 'translateY(6px)');
        apply('transition', 'opacity 0.15s ease-out, transform 0.15s ease-out');

        requestAnimationFrame(() => {
            apply('opacity', '1');
            apply('transform', 'none');
        });

        entranceTimer = setTimeout(() => {
            targets.forEach((el) => {
                el.style.transition = '';
                el.style.opacity = '';
                el.style.transform = '';
            });
        }, 200);
    }

    let navToken = 0;

    async function navigateTo(url, push = true) {
        const targetPath = new URL(url, window.location.origin).pathname;
        const currentPath = new URL(window.location.href).pathname;
        if (targetPath === currentPath) return;

        // Highlight clicked link immediately for instant feedback
        updateActiveLink(url);

        const token = ++navToken;

        try {
            let doc = pageCache.get(targetPath) || null;
            const servedFromCache = !!doc;

            if (!doc) {
                showNavBar();
                // Preload / wait on existing in-flight fetch
                await preloadPage(url);
                doc = pageCache.get(targetPath) || null;
            }

            let newMain = doc ? doc.querySelector('main.dash-content') : null;

            if (!newMain) {
                window.location.href = url;
                return;
            }

            if (token !== navToken) return;

            const pageKey = SPA_PAGE_KEYS[targetPath] || null;
            const main = document.querySelector('main.dash-content');
            if (!main) {
                window.location.href = url;
                return;
            }

            // Cleanup event for camera or charts
            window.dispatchEvent(new CustomEvent('spa:leaving'));

            // Load assets
            await loadPageAssets(doc);
            if (token !== navToken) return;

            // Persistent Header DOM swap: keep header intact, update only page title & body content
            const currentHeader = document.querySelector('[data-dash-header]');
            const newHeader = doc ? doc.querySelector('[data-dash-header]') : null;

            if (currentHeader && newHeader) {
                const newH1 = newHeader.querySelector('h1');
                const newP = newHeader.querySelector('p');
                const currentH1 = currentHeader.querySelector('h1');
                const currentP = currentHeader.querySelector('p');

                if (currentH1 && newH1) {
                    currentH1.textContent = newH1.textContent;
                }
                if (currentP) {
                    if (newP && newP.textContent.trim()) {
                        currentP.textContent = newP.textContent;
                        currentP.style.display = '';
                    } else {
                        currentP.textContent = '';
                        currentP.style.display = 'none';
                    }
                }
            }
            main.innerHTML = newMain.innerHTML;
            finishNavBar();

            // Swap body classes
            document.body.className = doc.body.className;

            // Update title
            const title = doc.querySelector('title');
            if (title) document.title = title.textContent;

            // Re-run inline scripts inside main
            rehydrateScripts(main);

            // Refresh body data scripts
            syncBodyDataScripts(doc);

            // Swap overlays/modals
            syncBodyOverlays(doc);

            // Run page init immediately
            if (pageKey && window.AppPage && typeof window.AppPage[pageKey] === 'function') {
                try {
                    window.AppPage[pageKey]();
                } catch (e) {
                    console.debug('[instant-nav] page init error', e);
                }
            } else if (pageKey) {
                window.location.href = url;
                return;
            }

            // Sync notification UI with current account's read state
            window.dispatchEvent(new CustomEvent('spa:navigated'));
            if (typeof window.syncNotificationUI === 'function') {
                window.syncNotificationUI();
            }

            // History state
            if (push) history.pushState({ spa: true, url }, '', url);

            // Close mobile sidebar, close user dropdown, and scroll to top
            if (window.innerWidth <= 992) closeSidebar();
            closeUserDropdown();
            window.scrollTo({ top: 0, behavior: 'instant' });

            // Micro entrance
            runContentEntrance();

            // Refresh in background quietly
            if (servedFromCache) {
                setTimeout(() => preloadPage(targetPath, true), 500);
            }
        } catch (err) {
            console.warn('[instant-nav] fallback to hard navigation', err);
            window.location.href = url;
        }
    }

    function syncBodyOverlays(doc) {
        const keepSelector = '.dash-layout, .chatbot-widget, #notifDetailModal, #allNotifsModal, #weatherDropdown';

        document.body.querySelectorAll('body > .modal, body > [id$="odal"]').forEach((el) => {
            if (el.matches(keepSelector)) return;
            el.remove();
        });

        Array.from(doc.body.children).forEach((el) => {
            if (el.matches('script, style, link, meta')) return;
            if (el.matches(keepSelector)) return;
            if (el.matches('.modal') || /[Mm]odal/.test(el.id || '')) {
                const existing = document.getElementById(el.id);
                if (existing) existing.remove();
                document.body.appendChild(el.cloneNode(true));
            }
        });
    }

    // Intercept sidebar navigation clicks
    document.addEventListener('click', (e) => {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        const anchor = e.target.closest('a[href]');
        if (!anchor) return;
        if (anchor.target === '_blank' || anchor.hasAttribute('download')) return;

        const isNavLink = anchor.matches('.dash-sidebar__link, .dash-sidebar__profile-item, [data-page-transition], #dashSidebar a');
        if (!isNavLink) return;

        const href = anchor.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) return;

        let url;
        try { url = new URL(href, window.location.origin); } catch (err) { return; }
        if (url.origin !== window.location.origin) return;

        const path = url.pathname;
        if (!path.startsWith('/staff/') && !path.startsWith('/admin/')) return;

        e.preventDefault();
        navigateTo(url.href, true);
    });

    // Back/forward browser buttons
    window.addEventListener('popstate', () => {
        navigateTo(window.location.href, false);
    });

    // Fetch + cache a page's HTML in memory with in-flight deduplication
    async function preloadPage(url, force = false) {
        try {
            const targetPath = new URL(url, window.location.origin).pathname;
            if (!force && pageCache.has(targetPath)) return true;

            if (inFlightFetches.has(targetPath)) {
                return await inFlightFetches.get(targetPath);
            }

            const fetchPromise = (async () => {
                try {
                    const response = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        __skipBusy: true,
                    });
                    if (!response.ok) return false;

                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    if (!doc.querySelector('main.dash-content')) return false;

                    pageCache.set(targetPath, doc);
                    await loadPageAssets(doc);
                    return true;
                } catch (err) {
                    return false;
                } finally {
                    inFlightFetches.delete(targetPath);
                }
            })();

            inFlightFetches.set(targetPath, fetchPromise);
            return await fetchPromise;
        } catch (err) {
            return false;
        }
    }

    function isDashboardHref(href) {
        return href && (href.includes('/staff/') || href.includes('/admin/'));
    }

    // Immediate preload on hover/touch
    const handlePreloadTrigger = (e) => {
        if (!e || !e.target || typeof e.target.closest !== 'function') return;
        const anchor = e.target.closest('.dash-sidebar__link[href], .dash-sidebar__profile-item[href], [data-page-transition][href], #dashSidebar a[href]');
        if (!anchor || anchor.dataset.preloaded) return;
        anchor.dataset.preloaded = '1';
        const href = anchor.getAttribute('href');
        if (!isDashboardHref(href)) return;
        preloadPage(href);
    };

    document.addEventListener('pointerenter', handlePreloadTrigger, true);
    document.addEventListener('touchstart', handlePreloadTrigger, { passive: true });

    // ------------------------------------------------------------
    // Preparing Pages Indicator & Fast Background Preloader
    // ------------------------------------------------------------
    function getPrepareToast() {
        let toast = document.getElementById('spaPrepareToast');
        if (toast) return toast;
        toast = document.createElement('div');
        toast.id = 'spaPrepareToast';
        toast.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;align-items:center;gap:8px;background:rgba(13,44,29,0.92);backdrop-filter:blur(10px);color:#f5f5f0;font:600 12px/1.4 "Poppins",system-ui,sans-serif;padding:8px 16px;border-radius:999px;box-shadow:0 4px 20px rgba(0,0,0,0.25);border:1px solid rgba(255,255,255,0.15);opacity:0;transition:opacity .3s cubic-bezier(0.4,0,0.2,1),transform .3s cubic-bezier(0.4,0,0.2,1);transform:translateY(10px);pointer-events:none;';

        toast.innerHTML = `
            <span id="spaPrepareIcon" style="display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;">
                <span style="width:10px;height:10px;border-radius:50%;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;animation:spaSpin 0.7s linear infinite;"></span>
            </span>
            <span id="spaPrepareLabel">Preparing pages…</span>
        `;
        document.body.appendChild(toast);

        if (!document.getElementById('spaSpinKeyframes')) {
            const style = document.createElement('style');
            style.id = 'spaSpinKeyframes';
            style.textContent = '@keyframes spaSpin { to { transform: rotate(360deg); } }';
            document.head.appendChild(style);
        }
        return toast;
    }

    function showPrepareProgress(done, total) {
        const toast = getPrepareToast();
        const label = document.getElementById('spaPrepareLabel');
        const icon = document.getElementById('spaPrepareIcon');
        if (label) label.textContent = `Preparing pages… ${done}/${total}`;
        if (icon) icon.innerHTML = '<span style="width:10px;height:10px;border-radius:50%;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;animation:spaSpin 0.7s linear infinite;"></span>';
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    }

    function showPrepareDone() {
        const toast = getPrepareToast();
        const label = document.getElementById('spaPrepareLabel');
        const icon = document.getElementById('spaPrepareIcon');
        if (label) label.textContent = 'Pages ready ✓';
        if (icon) icon.innerHTML = '<svg style="width:14px;height:14px;color:#4ade80;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
        }, 1500);
    }

    // Fast parallel batch preloader
    async function prefetchSidebarPages() {
        const currentPath = window.location.pathname;
        const links = Array.from(document.querySelectorAll('#dashSidebar a[href], [data-page-transition][href]'))
            .map(a => a.getAttribute('href'))
            .filter(href => isDashboardHref(href))
            .map(href => new URL(href, window.location.origin).pathname)
            .filter((path, idx, arr) => path !== currentPath && arr.indexOf(path) === idx);

        if (!links.length) return;

        const total = links.length;
        let done = links.filter(p => pageCache.has(p)).length;

        if (done >= total) return;

        showPrepareProgress(done, total);

        // Preload in parallel batches of 2 for maximum speed without overloading
        const batchSize = 2;
        for (let i = 0; i < links.length; i += batchSize) {
            const batch = links.slice(i, i + batchSize).filter(p => !pageCache.has(p));
            if (batch.length > 0) {
                await Promise.all(batch.map(path => preloadPage(path)));
            }
            done = links.filter(p => pageCache.has(p)).length;
            showPrepareProgress(done, total);
        }

        showPrepareDone();
    }

    setTimeout(prefetchSidebarPages, 120);

    // Slim progress bar for un-cached clicks
    function navBar() {
        let bar = document.getElementById('spaNavBar');
        if (bar) return bar;
        bar = document.createElement('div');
        bar.id = 'spaNavBar';
        bar.style.cssText = 'position:fixed;top:0;left:0;height:3px;width:0;opacity:0;background:linear-gradient(90deg,#6E9F54,#4ade80);box-shadow:0 0 8px rgba(110,159,84,0.6);z-index:9999;transition:width .15s ease,opacity .2s ease;pointer-events:none;';
        document.body.appendChild(bar);
        return bar;
    }

    function showNavBar() {
        const bar = navBar();
        bar.style.opacity = '1';
        bar.style.width = '0%';
        requestAnimationFrame(() => { bar.style.width = '70%'; });
    }

    function finishNavBar() {
        const bar = document.getElementById('spaNavBar');
        if (!bar) return;
        bar.style.width = '100%';
        setTimeout(() => {
            bar.style.opacity = '0';
            bar.style.width = '0%';
        }, 150);
    }

    // Initial content entrance
    runContentEntrance();
});
