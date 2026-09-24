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
        '/admin/users': 'admin_usermanagement',
        '/admin/announcements': 'admin_announcement',
        '/admin/reports': 'admin_reports',
        '/admin/feedback': 'admin_feedback',
        '/admin/payment': 'admin_payment',
        '/admin/settings': 'admin_settings',
    };

    function getSpaPageKey(path) {
        if (!path) return null;
        for (const [route, key] of Object.entries(SPA_PAGE_KEYS)) {
            if (path === route || path.endsWith(route)) {
                return key;
            }
        }
        return null;
    }

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

    // ============================================================
    // SMART VERSIONED CACHE: Instant 0ms switches when data hasn't changed
    // ============================================================
    let currentDataVersion = 1;
    const pageCache = new Map();
    const inFlightFetches = new Map();

    function bumpDataVersion() {
        currentDataVersion++;
    }
    window.bumpDataVersion = bumpDataVersion;

    function setCachedPage(key, doc, version = currentDataVersion) {
        if (!key || !doc) return;
        pageCache.set(key, {
            doc: doc.cloneNode(true),
            version: version,
            savedAt: Date.now()
        });
    }

    function getCachedPage(key) {
        const entry = pageCache.get(key);
        if (!entry) return null;
        // If data in the app has mutated since this was cached, it needs an update!
        if (entry.version !== currentDataVersion) return null;
        return entry.doc;
    }

    function clearAppCache() {
        pageCache.clear();
        currentDataVersion++;
    }
    window.clearAppCache = clearAppCache;

    // Cache the initial document immediately at version 1
    const initialKey = window.location.pathname + (window.location.search || '');
    setCachedPage(initialKey, document.cloneNode(true), currentDataVersion);

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

    let currentNavToken = 0;
    let currentNavAbortController = null;
    let targetNavHref = window.location.href;

    async function navigateTo(url, push = true) {
        const targetUrl = new URL(url, window.location.origin);
        const targetHref = targetUrl.href;

        // If not currently navigating and already on this exact page, do nothing
        if (currentNavAbortController === null && targetHref === window.location.href) {
            return;
        }

        // Immediately abort any in-flight navigation request so previous pages NEVER overwrite the new page
        if (currentNavAbortController) {
            currentNavAbortController.abort();
            currentNavAbortController = null;
        }

        targetNavHref = targetHref;
        const token = ++currentNavToken;
        const abortController = new AbortController();
        currentNavAbortController = abortController;

        // Highlight clicked link immediately for instant feedback
        updateActiveLink(targetHref);

        // ONLY trigger the white disabled veil if the page actually takes a moment to load (>100ms)
        let veilTimer = null;
        let veilActive = false;

        const triggerVeil = (delay = 100) => {
            clearTimeout(veilTimer);
            veilTimer = setTimeout(() => {
                if (token === currentNavToken) {
                    veilActive = true;
                    showPageLoadingVeil();
                    showNavBar();
                }
            }, delay);
        };

        const dismissVeil = () => {
            clearTimeout(veilTimer);
            if (veilActive && token === currentNavToken) {
                veilActive = false;
                hidePageLoadingVeil();
                finishNavBar();
            }
        };

        const cacheKey = targetUrl.pathname + (targetUrl.search || '');
        let doc = getCachedPage(cacheKey);

        if (!doc) {
            // ONLY pages that need an update or haven't been cached will trigger the veil if slow
            triggerVeil(100);

            let fetchPromise = inFlightFetches.get(cacheKey);
            if (fetchPromise) {
                try {
                    await fetchPromise;
                    if (token !== currentNavToken) return;
                    doc = getCachedPage(cacheKey);
                } catch (e) {
                    /* ignore */
                }
            }

            if (!doc) {
                try {
                    const response = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        cache: 'no-cache',
                        signal: abortController.signal,
                        __skipBusy: true,
                    });

                    if (token !== currentNavToken) return;

                    if (!response.ok) {
                        dismissVeil();
                        window.location.href = url;
                        return;
                    }

                    const html = await response.text();
                    if (token !== currentNavToken) return;

                    doc = new DOMParser().parseFromString(html, 'text/html');

                    // Cache with currentDataVersion so navigating back is 100% instant!
                    setCachedPage(cacheKey, doc, currentDataVersion);
                } catch (fetchErr) {
                    // Silently ignore aborted requests caused by fast page switching
                    if (fetchErr.name === 'AbortError' || token !== currentNavToken) {
                        return;
                    }
                    console.warn('[instant-nav] fetch error', fetchErr);
                    dismissVeil();
                    window.location.href = url;
                    return;
                }
            }
        }

        if (token !== currentNavToken) return;

        try {
            const newMain = doc ? doc.querySelector('main.dash-content') : null;

            if (!newMain) {
                dismissVeil();
                window.location.href = url;
                return;
            }

            if (token !== currentNavToken) {
                dismissVeil();
                return;
            }

            const pageKey = getSpaPageKey(targetUrl.pathname);
            const main = document.querySelector('main.dash-content');
            if (!main) {
                dismissVeil();
                window.location.href = url;
                return;
            }

            // Cleanup event for camera or charts on previous page
            window.dispatchEvent(new CustomEvent('spa:leaving'));

            // Load any new page assets (stylesheets, JS bundles)
            await loadPageAssets(doc);
            if (token !== currentNavToken) {
                dismissVeil();
                return;
            }

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

            // Strictly check token right before altering the visible DOM
            if (token !== currentNavToken) {
                dismissVeil();
                return;
            }

            // Swap main content directly with fresh data
            main.className = newMain.className;
            main.innerHTML = newMain.innerHTML;

            // Dismiss veil as soon as fresh DOM is swapped in
            dismissVeil();

            // Swap body classes
            document.body.className = doc.body.className;

            // Update title
            const title = doc.querySelector('title');
            if (title) document.title = title.textContent;

            // Re-run inline scripts inside main
            rehydrateScripts(main);

            // Refresh body data scripts (window.staffReservationData, etc.)
            syncBodyDataScripts(doc);

            // Swap overlays/modals
            syncBodyOverlays(doc);

            // History state: push new page to history
            if (push) history.pushState({ spa: true, url }, '', url);
            targetNavHref = window.location.href;
            currentNavAbortController = null;

            // Run page init immediately with fresh data
            if (pageKey && window.AppPage && typeof window.AppPage[pageKey] === 'function') {
                try {
                    window.AppPage[pageKey]();
                } catch (e) {
                    console.debug('[instant-nav] page init error', e);
                }
            } else if (pageKey) {
                dismissVeil();
                window.location.href = url;
                return;
            }

            // Sync notification UI with current account's read state
            window.dispatchEvent(new CustomEvent('spa:navigated'));
            if (typeof window.syncNotificationUI === 'function') {
                window.syncNotificationUI();
            }

            // Close mobile sidebar, close user dropdown, and scroll to top
            if (window.innerWidth <= 992) closeSidebar();
            closeUserDropdown();
            window.scrollTo({ top: 0, behavior: 'instant' });

            // Micro entrance
            runContentEntrance();
        } catch (err) {
            if (token !== currentNavToken) return;
            if (err.name === 'AbortError') return;
            console.warn('[instant-nav] fallback to hard navigation', err);
            dismissVeil();
            window.location.href = url;
        } finally {
            if (token === currentNavToken) {
                currentNavAbortController = null;
            }
        }
    }

    function syncBodyOverlays(doc) {
        const keepSelector = '.dash-layout, .chatbot-widget, #notifDetailModal, #allNotifsModal, #weatherDropdown';

        document.body.querySelectorAll('body > .modal, body > [id$="odal"], #printableHandoverSlip').forEach((el) => {
            if (el.matches(keepSelector)) return;
            el.remove();
        });

        Array.from(doc.body.children).forEach((el) => {
            if (el.matches('script, style, link, meta')) return;
            if (el.matches(keepSelector)) return;
            if (el.matches('.modal') || /[Mm]odal/.test(el.id || '') || el.id === 'printableHandoverSlip') {
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
        if (!path.includes('/staff/') && !path.includes('/admin/')) return;

        e.preventDefault();
        navigateTo(url.href, true);
    });

    // Back/forward browser buttons
    window.addEventListener('popstate', () => {
        navigateTo(window.location.href, false);
    });

    // Fetch + warm up a page's assets and cache in memory with in-flight deduplication
    async function preloadPage(url) {
        try {
            const targetUrl = new URL(url, window.location.origin);
            const cacheKey = targetUrl.pathname + (targetUrl.search || '');
            if (getCachedPage(cacheKey)) return true;

            if (inFlightFetches.has(cacheKey)) {
                return await inFlightFetches.get(cacheKey);
            }

            const fetchPromise = (async () => {
                try {
                    const response = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        cache: 'no-cache',
                        __skipBusy: true,
                    });
                    if (!response.ok) return false;

                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    if (!doc.querySelector('main.dash-content')) return false;

                    // Cache page at currentDataVersion for instant 0ms switching
                    setCachedPage(cacheKey, doc, currentDataVersion);

                    // Load assets into browser cache so CSS and Vite JS modules are ready
                    await loadPageAssets(doc);
                    return true;
                } catch (err) {
                    return false;
                } finally {
                    inFlightFetches.delete(cacheKey);
                }
            })();

            inFlightFetches.set(cacheKey, fetchPromise);
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
        let done = links.filter(p => getCachedPage(p)).length;

        if (done >= total) return;

        showPrepareProgress(done, total);

        // Preload in parallel batches of 2 for maximum speed without overloading
        const batchSize = 2;
        for (let i = 0; i < links.length; i += batchSize) {
            const batch = links.slice(i, i + batchSize).filter(p => !getCachedPage(p));
            if (batch.length > 0) {
                await Promise.all(batch.map(path => preloadPage(path)));
            }
            done = links.filter(p => getCachedPage(p)).length;
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

    // ------------------------------------------------------------
    // Page Loading Veil (Frosted "disabled" overlay over current view)
    // ------------------------------------------------------------
    function ensureVeilStyles() {
        if (document.getElementById('spaVeilStyles')) return;
        const style = document.createElement('style');
        style.id = 'spaVeilStyles';
        style.textContent = `
            .spa-page-veil {
                position: fixed;
                z-index: 75;
                background-color: rgba(255, 255, 255, 0.55);
                backdrop-filter: blur(2px);
                -webkit-backdrop-filter: blur(2px);
                cursor: wait;
                pointer-events: all;
                opacity: 0;
                transition: opacity 0.15s ease-out;
            }
            [data-theme="dark"] .spa-page-veil {
                background-color: rgba(15, 17, 16, 0.55);
            }
            .spa-page-veil.is-visible {
                opacity: 1;
            }
            .dash-content.is-page-loading {
                filter: grayscale(25%);
                opacity: 0.60;
                pointer-events: none !important;
                user-select: none !important;
                transition: filter 0.15s ease, opacity 0.15s ease;
            }
        `;
        document.head.appendChild(style);
    }

    function showPageLoadingVeil() {
        ensureVeilStyles();

        // Mark current page content as disabled look
        const mainContent = document.querySelector('main.dash-content');
        if (mainContent) {
            mainContent.classList.add('is-page-loading');
        }

        let veil = document.getElementById('spaPageVeil');
        if (!veil) {
            veil = document.createElement('div');
            veil.id = 'spaPageVeil';
            veil.className = 'spa-page-veil';
            document.body.appendChild(veil);
        }

        // Align veil precisely with the main content area (excluding the sidebar)
        const mainContainer = document.querySelector('.dash-main') || mainContent;
        if (mainContainer) {
            const rect = mainContainer.getBoundingClientRect();
            veil.style.left = `${Math.max(0, rect.left)}px`;
            veil.style.top = '0px';
            veil.style.width = `${rect.width}px`;
            veil.style.height = '100vh';
        } else {
            veil.style.left = '0px';
            veil.style.top = '0px';
            veil.style.width = '100vw';
            veil.style.height = '100vh';
        }

        requestAnimationFrame(() => {
            veil.classList.add('is-visible');
        });
    }

    function hidePageLoadingVeil() {
        const veil = document.getElementById('spaPageVeil');
        if (veil) {
            veil.classList.remove('is-visible');
            setTimeout(() => {
                if (!veil.classList.contains('is-visible') && veil.parentNode) {
                    veil.remove();
                }
            }, 180);
        }

        const mainContent = document.querySelector('main.dash-content');
        if (mainContent) {
            mainContent.classList.remove('is-page-loading');
        }
    }

    // ============================================================
    // REAL-TIME AUTO-UPDATE ENGINE FOR ACTIVE PAGES
    // ============================================================
    let isRefreshingActivePage = false;
    let pendingRefreshAfterModal = false;
    let activeRefreshTimer = null;

    function isAnyModalOpen() {
        return !!document.querySelector(
            '.modal.is-open, .guest-modal.is-open, [id$="Modal"].is-open, [id$="modal"].is-open, dialog[open]'
        );
    }

    function isUserTyping() {
        const active = document.activeElement;
        if (!active) return false;
        return (
            ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName) ||
            active.isContentEditable
        );
    }

    function scheduleActivePageRefresh(delay = 150, force = true) {
        clearTimeout(activeRefreshTimer);
        activeRefreshTimer = setTimeout(() => {
            refreshActivePage(force);
        }, delay);
    }
    window.scheduleActivePageRefresh = scheduleActivePageRefresh;

    async function refreshActivePage(force = false) {
        const currentPath = window.location.pathname;
        const pageKey = getSpaPageKey(currentPath);
        if (!pageKey) return; // Not an SPA page

        if (document.visibilityState !== 'visible') {
            pendingRefreshAfterModal = true;
            return;
        }

        // Safety: Do not interrupt active modals
        if (isAnyModalOpen()) {
            pendingRefreshAfterModal = true;
            return;
        }

        // Safety: Do not interrupt active typing
        if (isUserTyping() && !force) {
            pendingRefreshAfterModal = true;
            return;
        }

        if (isRefreshingActivePage) return;
        isRefreshingActivePage = true;

        try {
            // Specialized handling for staff_reports to preserve active filters and modals
            if (pageKey === 'staff_reports' && window.__staffReportsController?.fetchAndSwapReports) {
                await window.__staffReportsController.fetchAndSwapReports(window.location.href, false);
                return;
            }

            const response = await fetch(window.location.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-cache',
                __skipBusy: true,
            });

            if (!response.ok) return;

            // Ensure user hasn't navigated away during the fetch
            if (window.location.pathname !== currentPath) return;

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const newMain = doc.querySelector('main.dash-content');
            const currentMain = document.querySelector('main.dash-content');

            if (!newMain || !currentMain) return;

            // Compare relevant content and body script data
            const oldDataScripts = Array.from(document.body.querySelectorAll('script[data-spa-data]')).map(s => s.textContent).join('\n');
            const newDataScripts = Array.from(doc.querySelectorAll('body > script:not([src])')).map(s => s.textContent).join('\n');
            const dataChanged = oldDataScripts !== newDataScripts;

            // Cache the fresh document for the current page at currentDataVersion
            setCachedPage(currentPath + (window.location.search || ''), doc, currentDataVersion);

            // Compare relevant content: skip DOM mutation only if identical and data not changed and not forced
            if (!force && !dataChanged && currentMain.innerHTML.trim() === newMain.innerHTML.trim()) {
                return;
            }

            // Double check modal wasn't opened while fetch was running
            if (isAnyModalOpen()) {
                pendingRefreshAfterModal = true;
                return;
            }

            // Preserve scroll position
            const scrollX = window.scrollX;
            const scrollY = window.scrollY;

            // Preserve search values
            const searchInputs = Array.from(document.querySelectorAll('input[type="search"], input[id*="Search"], input[id*="search"]'));
            const savedSearches = searchInputs.map(input => ({ id: input.id, value: input.value }));

            // Dispatch cleanup event so previous page timers/tickers reset cleanly
            window.dispatchEvent(new CustomEvent('spa:leaving'));

            // Load any new assets if present
            await loadPageAssets(doc);

            // Sync Persistent Header
            const currentHeader = document.querySelector('[data-dash-header]');
            const newHeader = doc.querySelector('[data-dash-header]');
            if (currentHeader && newHeader) {
                const newH1 = newHeader.querySelector('h1');
                const newP = newHeader.querySelector('p');
                const currentH1 = currentHeader.querySelector('h1');
                const currentP = currentHeader.querySelector('p');
                if (currentH1 && newH1) currentH1.textContent = newH1.textContent;
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

            // Seamlessly swap main content
            currentMain.className = newMain.className;
            currentMain.innerHTML = newMain.innerHTML;

            // Re-run inline scripts inside main
            rehydrateScripts(currentMain);

            // Refresh body data scripts (window.staffReservationData, etc.)
            syncBodyDataScripts(doc);

            // Sync body overlays/modals
            syncBodyOverlays(doc);

            // Re-run page initializer
            if (window.AppPage && typeof window.AppPage[pageKey] === 'function') {
                try {
                    window.AppPage[pageKey]();
                } catch (e) {
                    console.debug('[live-refresh] page init error', e);
                }
            }

            // Restore search input values and re-filter
            savedSearches.forEach(({ id, value }) => {
                if (id && value) {
                    const el = document.getElementById(id);
                    if (el) {
                        el.value = value;
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
            });

            // Restore scroll position
            window.scrollTo({ left: scrollX, top: scrollY, behavior: 'instant' });

            // Notify UI
            window.dispatchEvent(new CustomEvent('spa:page-refreshed', { detail: { path: currentPath } }));
        } catch (err) {
            console.debug('[live-refresh] refresh error', err);
        } finally {
            isRefreshingActivePage = false;
        }
    }
    window.refreshActivePage = refreshActivePage;

    // Listen for incoming activity notifications from header heartbeat
    window.addEventListener('activity:new', () => {
        bumpDataVersion();
        scheduleActivePageRefresh(200, true);
    });

    // Listen for explicit data mutations dispatched by page scripts
    window.addEventListener('app:data-mutated', () => {
        bumpDataVersion();
        scheduleActivePageRefresh(250, true);
    });

    // Refresh when tab gains focus
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            scheduleActivePageRefresh(100, false);
        }
    });

    // Check for deferred refresh when modals close or input blurs
    function checkPendingRefresh() {
        if (!pendingRefreshAfterModal) return;
        if (!isAnyModalOpen() && !isUserTyping()) {
            pendingRefreshAfterModal = false;
            scheduleActivePageRefresh(100);
        }
    }

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-close-reservation-modal], [data-close-check-in-modal], [data-close-scan-modal], [data-close-companion-summary], [data-close-bulk-companion-modal], [data-close-resched-requests-modal], [data-close-date-filter-modal], [data-logout-cancel], .guest-modal__backdrop, .modal-backdrop')) {
            setTimeout(checkPendingRefresh, 200);
        }
    });

    document.addEventListener('focusout', () => {
        setTimeout(checkPendingRefresh, 300);
    });

    // Background idle heartbeat refresh (every 12 seconds)
    setInterval(() => {
        if (document.visibilityState === 'visible' && !isAnyModalOpen() && !isUserTyping()) {
            scheduleActivePageRefresh(0);
        }
    }, 12000);

    // Transparent fetch interceptor: Any successful mutation (POST/PUT/PATCH/DELETE) refreshes active page
    if (!window.__spaFetchIntercepted) {
        window.__spaFetchIntercepted = true;
        const rawFetch = window.fetch;
        window.fetch = async function (...args) {
            const res = await rawFetch.apply(this, args);
            try {
                const init = args[1] || {};
                const method = (init.method || 'GET').toUpperCase();
                if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
                    const rawUrl = typeof args[0] === 'string' ? args[0] : (args[0]?.url || '');
                    if (res.ok && (rawUrl.includes('/staff/') || rawUrl.includes('/admin/') || rawUrl.includes('/api/'))) {
                        bumpDataVersion();
                        scheduleActivePageRefresh(300, true);
                    }
                }
            } catch (e) {
                // Ignore interceptor error
            }
            return res;
        };
    }

    // Initial content entrance
    runContentEntrance();
});
