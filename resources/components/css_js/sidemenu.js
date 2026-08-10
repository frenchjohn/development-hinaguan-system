// ============================================================
// Sidebar navigation is always instant. (A previous "Processing
// still in progress" guard modal was removed — it appeared even
// when nothing real was running and confused users. In-flight
// operations show their own button-level loading states.)
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
        const themeText = document.querySelector('.dash-sidebar__theme-text');
        if (themeText) {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            // Show the current mode name
            themeText.textContent = currentTheme === 'light' ? 'Light Mode' : 'Dark Mode';
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
            // Mobile: toggle sidebar-open class
            dashLayout.classList.toggle('sidebar-open');
        } else {
            // Desktop: toggle sidebar-collapsed class
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

    // Show/hide the mobile backdrop that sits behind the open sidebar. The
    // Tailwind sidebar uses [data-sidebar-overlay] (toggled via .is-open),
    // the classic theme uses .dash-sidebar__overlay (CSS-driven) — handle both.
    function syncOverlay() {
        const overlay = document.querySelector('.dash-sidebar__overlay, [data-sidebar-overlay]');
        if (!overlay) return;
        const open = dashLayout.classList.contains('sidebar-open');
        overlay.classList.toggle('is-open', open);
        overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
    }

    // Sidebar toggle + overlay clicks are delegated at the document level so
    // they keep working after SPA page swaps re-render the header (and its
    // toggle button), and so no other script can fight this handler.
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-dash-sidebar-toggle]')) {
            toggleSidebar(e);
        } else if (e.target.closest('.dash-sidebar__overlay, [data-sidebar-overlay]')) {
            closeSidebar();
        }
    });

    // Close sidebar on escape key (mobile only)
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && window.innerWidth <= 992 && dashLayout.classList.contains('sidebar-open')) {
            closeSidebar();
        }
    });

    // Handle user dropdown toggle
    if (userToggle) {
        userToggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const dropdown = userToggle.nextElementSibling;
            if (dropdown) {
                const isOpen = dropdown.classList.contains('is-open');
                if (isOpen) {
                    dropdown.classList.remove('is-open');
                    userToggle.classList.remove('is-open');
                } else {
                    dropdown.classList.add('is-open');
                    userToggle.classList.add('is-open');
                    // Update theme text when dropdown opens
                    updateThemeText();
                }
            }
        });
    }

    // Close user dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (userToggle) {
            const dropdown = userToggle.nextElementSibling;
            const profileSection = userToggle.closest('.dash-sidebar__profile');
            if (profileSection && !profileSection.contains(e.target)) {
                dropdown.classList.remove('is-open');
                userToggle.classList.remove('is-open');
            }
        }
    });

    // Handle theme toggle
    if (themeToggle) {
        themeToggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeText();
        });
    }

    // Handle window resize
    window.addEventListener('resize', () => {
        // Reset classes when switching between mobile/desktop
        if (window.innerWidth > 992) {
            dashLayout.classList.remove('sidebar-open');
        } else {
            dashLayout.classList.remove('sidebar-collapsed');
        }
    });

    // Handle logout confirmation modal
    const logoutConfirmBtn = document.querySelector('[data-logout-confirm]');
    const logoutModal = document.getElementById('logoutModal');
    const logoutCancelBtn = document.querySelector('[data-logout-cancel]');
    const logoutConfirmSubmitBtn = document.querySelector('.logout-modal__btn--confirm');

    if (logoutConfirmBtn && logoutModal) {
        logoutConfirmBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            logoutModal.classList.add('is-open');
            logoutModal.setAttribute('aria-hidden', 'false');
        });
    }

    if (logoutCancelBtn && logoutModal) {
        logoutCancelBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            logoutModal.classList.remove('is-open');
            logoutModal.setAttribute('aria-hidden', 'true');
        });
    }

    // Handle logout confirm button with loading effect
    if (logoutConfirmSubmitBtn) {
        logoutConfirmSubmitBtn.addEventListener('click', (e) => {
            // Add loading state
            logoutConfirmSubmitBtn.classList.add('is-loading');
            // The form will submit naturally after this
        });
    }

    // Close modal when clicking backdrop
    if (logoutModal) {
        const backdrop = logoutModal.querySelector('.logout-modal__backdrop');
        if (backdrop) {
            backdrop.addEventListener('click', () => {
                logoutModal.classList.remove('is-open');
                logoutModal.setAttribute('aria-hidden', 'true');
            });
        }

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && logoutModal.classList.contains('is-open')) {
                logoutModal.classList.remove('is-open');
                logoutModal.setAttribute('aria-hidden', 'true');
            }
        });
    }

    // ============================================================
    // INSTANT NAVIGATION (SPA-style page swapping)
    // Replaces the old full-page reload: fetch the target page,
    // swap only the <main> content, load its CSS/JS, re-run init.
    // ============================================================

    // Map dashboard URL paths to their registered page init functions.
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
        '/admin/users': 'admin_usermanagement',
        '/admin/settings': 'admin_settings',
    };

    // Every page-script src already present in this document.
    const loadedScriptSrcs = new Set(
        Array.from(document.querySelectorAll('script[src]')).map(s => s.src)
    );

    // Mark the current page's inline <style> blocks and body-level data scripts so
    // they can be swapped out on later navigations.
    document.querySelectorAll('head > style').forEach(style => {
        style.setAttribute('data-page-style', '');
    });
    document.body.querySelectorAll('script:not([src])').forEach(script => {
        script.setAttribute('data-spa-data', '');
    });

    // Preloaded page HTML, keyed by pathname, so navigation is a pure DOM swap.
    const pageCache = new Map();

    // Inline <script> tags inserted via innerHTML never execute, so recreate them.
    function rehydrateScripts(container) {
        container.querySelectorAll('script:not([src])').forEach((oldScript) => {
            const fresh = document.createElement('script');
            fresh.textContent = oldScript.textContent;
            oldScript.replaceWith(fresh);
        });
    }

    // Bring over the new page's stylesheets, inline styles, and JS bundles.
    async function loadPageAssets(doc) {
        // Stylesheet links (each page ships its own CSS bundle)
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

        // Page JS bundles (only the ones not already loaded)
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

    // Refresh the page-data scripts at the end of <body>
    // (e.g. window.staffReservationData = @json(...)).
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
        // Staff sidebar links use .nav-link (Tailwind), admin uses .dash-sidebar__link.
        document.querySelectorAll('.dash-sidebar__link, #dashSidebar .nav-link').forEach(link => {
            const href = link.getAttribute('href');
            if (!href) return;
            let linkPath = href;
            try { linkPath = new URL(href, window.location.origin).pathname; } catch (e) { /* ignore */ }
            link.classList.toggle('is-active', linkPath === targetPath);
        });
    }

    // Gentle entrance for dashboard content on initial load and SPA swaps.
    // A single, quick fade-up of everything BELOW the header — the header bar
    // itself stays perfectly still so it never looks like it "moves" when
    // switching pages. Inline styles are cleared once the entrance finishes.
    let entranceTimer = null;
    function runContentEntrance() {
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const main = document.querySelector('main.dash-content');
        if (!main) return;

        // Fade every visible direct child except the header (the app bar stays
        // put). Fixed-position modals and hidden panels (offsetParent null) are
        // excluded so the entrance never touches their inline styles.
        const header = main.querySelector(':scope > .dash-header');
        const targets = Array.from(main.children).filter((el) => el !== header && el.offsetParent !== null);
        if (!targets.length) return;

        // Cancel any still-running entrance from a previous navigation so a
        // fast double-click can't leave a stale timer that snaps content visible.
        clearTimeout(entranceTimer);

        const apply = (style, value) => targets.forEach((el) => { el.style[style] = value; });
        apply('opacity', '0');
        apply('transform', 'translateY(10px)');
        apply('transition', 'opacity 0.25s ease-out, transform 0.25s ease-out');

        requestAnimationFrame(() => {
            apply('opacity', '1');
            apply('transform', 'none');
        });

        // Clearing the inline styles restores the stylesheet values, so
        // content can never stay hidden.
        entranceTimer = setTimeout(() => {
            targets.forEach((el) => {
                el.style.transition = '';
                el.style.opacity = '';
                el.style.transform = '';
            });
        }, 380);
    }

    let navToken = 0;

    async function navigateTo(url, push = true) {
        const targetPath = new URL(url, window.location.origin).pathname;
        const currentPath = new URL(window.location.href).pathname;
        if (targetPath === currentPath) return;

        // Highlight the clicked link IMMEDIATELY — before anything else — so the
        // sidebar responds to the click the instant it happens.
        updateActiveLink(url);

        const token = ++navToken;

        try {
            // Serve from the warm cache first (populated by preload below) so a
            // click is a pure DOM swap with zero network wait.
            let doc = pageCache.get(targetPath) || null;
            const servedFromCache = !!doc;
            let newMain = doc ? doc.querySelector('main.dash-content') : null;

            if (!newMain) {
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    __skipBusy: true,
                });
                if (!response.ok) throw new Error('Bad status ' + response.status);

                const html = await response.text();
                if (token !== navToken) return; // a newer navigation superseded this one

                doc = new DOMParser().parseFromString(html, 'text/html');
                newMain = doc.querySelector('main.dash-content');
                if (!newMain) {
                    // Not a dashboard page (e.g. session expired) — do a normal load.
                    window.location.href = url;
                    return;
                }
                // Remember it so a revisit is instant.
                pageCache.set(targetPath, doc);
            }

            const pageKey = SPA_PAGE_KEYS[targetPath] || null;
            const main = document.querySelector('main.dash-content');

            // Let the current page clean up (e.g. stop the QR camera) before swapping.
            window.dispatchEvent(new CustomEvent('spa:leaving'));

            // Load the new page's CSS/JS before swapping content. In the preloaded
            // path this is usually a no-op because the assets are already loaded.
            await loadPageAssets(doc);
            if (token !== navToken) return;

            // Swap content
            main.innerHTML = newMain.innerHTML;
            
            // Swap body classes (for CSS scoping like .s-das-page, which fixes modals)
            document.body.className = doc.body.className;

            // Update title
            const title = doc.querySelector('title');
            if (title) document.title = title.textContent;

            // Re-run inline scripts inside main (header clock, page init helpers)
            rehydrateScripts(main);

            // Refresh body-level data scripts
            syncBodyDataScripts(doc);

            // Swap body-level overlays (modals kept outside <main>)
            syncBodyOverlays(doc);

            // Run the page's init
            if (pageKey && window.AppPage && typeof window.AppPage[pageKey] === 'function') {
                window.AppPage[pageKey]();
            } else if (pageKey) {
                // Page's bundle failed to load — fall back to a normal navigation.
                window.location.href = url;
                return;
            }

            // History state
            if (push) history.pushState({ spa: true, url }, '', url);

            // Close mobile sidebar and scroll to top
            if (window.innerWidth <= 992) closeSidebar();
            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Gentle entrance for the swapped content
            runContentEntrance();

            // If this page was served from cache, refresh just that one page in
            // the background so the cached copy is current for the next visit.
            // (Refreshing ALL cached pages after every navigation floods the
            // single-threaded PHP dev server and makes real clicks queue behind
            // background fetches.)
            if (servedFromCache) {
                setTimeout(() => preloadPage(targetPath, true), 400);
            }
        } catch (err) {
            console.warn('[instant-nav] failed, falling back to full navigation', err);
            window.location.href = url;
        }
    }

    // Bring the new page's body-level overlays (e.g. #userModal on admin/users,
    // #amenityModal on admin/amenities, #staffOtpModal on staff/settings) into the
    // live document. The SPA only swaps <main>, but a few pages keep modals at the
    // <body> level — without this they silently disappear after SPA navigation and
    // their open buttons throw errors.
    function syncBodyOverlays(doc) {
        const keepSelector = '.dash-layout, .chatbot-widget';

        // Drop leftover page-level overlays left behind by the previous page.
        document.body.querySelectorAll('body > .modal, body > [id$="odal"]').forEach((el) => {
            if (el.matches(keepSelector)) return;
            el.remove();
        });

        // Bring in the new page's body-level overlays (cloned so the cached
        // document keeps its own copy for future revisits).
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

    // Intercept sidebar navigation links (dash-sidebar__link, profile dropdown
    // links and any [data-page-transition] links) so they navigate instantly.
    // Regular in-content links are left alone to keep their normal behavior.
    document.addEventListener('click', (e) => {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        const anchor = e.target.closest('a[href]');
        if (!anchor) return;
        if (anchor.target === '_blank' || anchor.hasAttribute('download')) return;

        const isNavLink = anchor.matches('.dash-sidebar__link, .dash-sidebar__profile-item, [data-page-transition]');
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

    // Back/forward buttons
    window.addEventListener('popstate', () => {
        navigateTo(window.location.href, false);
    });

    // ------------------------------------------------------------
    // Background preloading — this is what makes clicks feel instant.
    // The hovered page is fetched (and its JS/CSS bundles loaded) ahead of
    // time, so clicking a sidebar item is a pure DOM swap. Only the hovered
    // page is preloaded: bulk-preloading every dashboard page at once floods
    // the single-threaded PHP dev server and real clicks queue behind it.
    // ------------------------------------------------------------

    // Fetch + cache a page's HTML and load its JS/CSS bundles ahead of time.
    // Pass force=true to replace an existing cached copy (used after navigation
    // so revisits get fresh data).
    async function preloadPage(url, force = false) {
        try {
            const targetPath = new URL(url, window.location.origin).pathname;
            if (!force && pageCache.has(targetPath)) return true;

            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                __skipBusy: true,
            });
            if (!response.ok) return false;

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            if (!doc.querySelector('main.dash-content')) return false; // not a dashboard page (e.g. session expired)

            pageCache.set(targetPath, doc);

            // Load the page's JS bundle(s) and CSS in the background too, so the
            // swap + init runs with zero remaining work at click time.
            await loadPageAssets(doc);
            return true;
        } catch (err) {
            /* silent — preloading is best-effort */
            return false;
        }
    }

    // Only dashboard pages are preloadable (skip the 'Back to Website' link).
    function isDashboardHref(href) {
        return href && (href.includes('/staff/') || href.includes('/admin/'));
    }

    // Preload the hovered page immediately so the next click is already cached.
    document.addEventListener('mouseover', (e) => {
        const anchor = e.target.closest('.dash-sidebar__link[href], .dash-sidebar__profile-item[href]');
        if (!anchor || anchor.dataset.preloaded) return;
        anchor.dataset.preloaded = '1';
        const href = anchor.getAttribute('href');
        if (!isDashboardHref(href)) return;
        preloadPage(href);
    });

    // Initial content entrance (full page loads)
    runContentEntrance();
});
