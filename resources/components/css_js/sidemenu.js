// Sidemenu toggle functionality and instant navigation
window.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.querySelector('[data-dash-sidebar-toggle]');
    const sidebarOverlay = document.querySelector('.dash-sidebar__overlay');
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

    if (!sidebarToggle) {
        console.error('Sidemenu: toggle button not found');
        return;
    }

    // Create loading overlay
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'page-loading-overlay';
    loadingOverlay.innerHTML = '<div class="page-loading-spinner"></div>';
    document.body.appendChild(loadingOverlay);

    // Get sidebar links early for use in navigation handler
    const sidebarLinks = document.querySelectorAll('.dash-sidebar__link');

    // Clean up any loading states from previous navigation
    function cleanupLoadingStates() {
        sidebarLinks.forEach(link => {
            link.classList.remove('is-loading');
        });
        hideLoading();
    }

    // Run cleanup on page load
    cleanupLoadingStates();

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
    }

    function closeSidebar() {
        const isMobile = window.innerWidth <= 992;
        
        if (isMobile) {
            dashLayout.classList.remove('sidebar-open');
        } else {
            dashLayout.classList.remove('sidebar-collapsed');
        }
    }

    function showLoading() {
        loadingOverlay.classList.add('is-active');
    }

    function hideLoading() {
        loadingOverlay.classList.remove('is-active');
    }

    // Instant navigation with loading animation
    function handleNavigationClick(e) {
        const link = e.currentTarget;
        const url = link.getAttribute('href');
        
        // Only handle internal staff navigation links
        if (!url || url.startsWith('http') || url.startsWith('//') || url.startsWith('#')) {
            return;
        }

        e.preventDefault();
        
        // Clean up any previous loading states
        cleanupLoadingStates();
        
        // Instantly update active state - remove from all links, add to clicked
        sidebarLinks.forEach(l => l.classList.remove('is-active'));
        link.classList.add('is-active');
        
        // Show loading state on the clicked link
        link.classList.add('is-loading');
        showLoading();
        
        // Close sidebar on mobile
        if (window.innerWidth <= 992) {
            closeSidebar();
        }

        // Navigate to the new page
        window.location.href = url;
    }

    sidebarToggle.addEventListener('click', toggleSidebar);

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }

    // Close sidebar on escape key (mobile only)
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && window.innerWidth <= 992 && dashLayout.classList.contains('sidebar-open')) {
            closeSidebar();
        }
    });

    // Handle navigation link clicks with loading animation
    sidebarLinks.forEach(link => {
        link.addEventListener('click', handleNavigationClick);
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
            if (!profileSection.contains(e.target)) {
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
});
