document.addEventListener('DOMContentLoaded', () => {
    const siteHeader = document.getElementById('hpSiteHeader');
    const header = document.getElementById('hpHeader');
    const menuToggle = document.querySelector('.hp-menu-toggle');
    const mobileNav = document.querySelector('.hp-mobile-nav');
    const mobileLinks = mobileNav?.querySelectorAll('a');
    const guestCountEl = document.getElementById('activeGuestCount');
    const scrollToTopBtn = document.getElementById('scrollToTop');
    const navLinks = document.querySelectorAll('[data-nav-link]');
    const sections = document.querySelectorAll('[data-section]');
    const animatedElements = document.querySelectorAll('[data-animate]');

    const getScrollOffset = () => (siteHeader?.offsetHeight ?? 0) + 8;

    const syncHeaderOffset = () => {
        if (!siteHeader) return;
        document.documentElement.style.setProperty('--hp-header-offset', `${siteHeader.offsetHeight}px`);
    };

    syncHeaderOffset();
    window.addEventListener('resize', syncHeaderOffset, { passive: true });

    // Sticky header background on scroll
    const onScroll = () => {
        const scrolled = window.scrollY > 40;
        header?.classList.toggle('is-scrolled', scrolled);
        scrollToTopBtn?.classList.toggle('is-visible', window.scrollY > 500);
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    // Mobile menu
    const mobileNavClose = document.getElementById('hpMobileNavClose');

    const closeMobileNav = () => {
        mobileNav?.classList.remove('is-open');
        menuToggle?.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    };

    menuToggle?.addEventListener('click', () => {
        const isOpen = mobileNav?.classList.toggle('is-open');
        menuToggle.setAttribute('aria-expanded', String(isOpen));
        document.body.style.overflow = isOpen ? 'hidden' : '';
    });

    mobileNavClose?.addEventListener('click', closeMobileNav);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && mobileNav?.classList.contains('is-open')) {
            closeMobileNav();
        }
    });

    mobileLinks?.forEach((link) => {
        link.addEventListener('click', closeMobileNav);
    });

    // Mobile weather & Brenda notification toggle (close & open)
    const mobileWidgetsToggle = document.getElementById('hpMobileWidgetsToggle');
    const mobileWidgetsCollapse = document.getElementById('hpMobileWidgetsCollapse');
    const mobileWidgetsClose = document.getElementById('hpMobileWidgetsClose');

    const toggleMobileWidgets = (expand) => {
        if (!mobileWidgetsCollapse || !mobileWidgetsToggle) return;
        const shouldExpand = expand !== undefined ? expand : !mobileWidgetsCollapse.classList.contains('is-open');
        mobileWidgetsCollapse.classList.toggle('is-open', shouldExpand);
        mobileWidgetsToggle.classList.toggle('is-active', shouldExpand);
        mobileWidgetsToggle.setAttribute('aria-expanded', String(shouldExpand));
    };

    mobileWidgetsToggle?.addEventListener('click', () => {
        toggleMobileWidgets();
    });

    mobileWidgetsClose?.addEventListener('click', () => {
        toggleMobileWidgets(false);
    });

    // Smooth scroll for anchor links
    navLinks.forEach((anchor) => {
        anchor.addEventListener('click', (e) => {
            const targetId = anchor.getAttribute('href');
            if (!targetId || !targetId.startsWith('#')) return;

            const target = document.querySelector(targetId);
            if (!target) return;

            e.preventDefault();
            const top = target.getBoundingClientRect().top + window.scrollY - getScrollOffset();

            window.scrollTo({ top, behavior: 'smooth' });
            closeMobileNav();
        });
    });

    // Scroll spy — active nav link
    const setActiveNav = (sectionId) => {
        navLinks.forEach((link) => {
            const href = link.getAttribute('href');
            const isActive = href === `#${sectionId}` || (sectionId === 'home' && href === '#home');
            link.classList.toggle('is-active', isActive);
        });
    };

    const sectionObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    setActiveNav(entry.target.id);
                }
            });
        },
        {
            rootMargin: `-${getScrollOffset()}px 0px -55% 0px`,
            threshold: 0,
        }
    );

    sections.forEach((section) => sectionObserver.observe(section));

    // Entrance animations on scroll
    const animateObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;

                const el = entry.target;
                const delay = parseInt(el.dataset.delay ?? '0', 10);

                window.setTimeout(() => {
                    el.classList.add('is-visible');
                }, delay);

                animateObserver.unobserve(el);
            });
        },
        {
            rootMargin: '0px 0px -8% 0px',
            threshold: 0.1,
        }
    );

    animatedElements.forEach((el) => animateObserver.observe(el));

    // Animate hero elements immediately on load
    const heroElements = document.querySelectorAll('.hp-hero [data-animate]');
    heroElements.forEach((el, index) => {
        window.setTimeout(() => {
            el.classList.add('is-visible');
        }, 200 + index * 150);
    });

    // Scroll to top button
    scrollToTopBtn?.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Active guest count polling
    const updateActiveGuestCount = async () => {
        if (!guestCountEl) return;

        try {
            const response = await fetch('/api/active-guests-count', {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) return;

            const data = await response.json();
            guestCountEl.textContent = Number(data.count ?? 0);
        } catch (error) {
            console.warn('Unable to refresh active guest count.', error);
        }
    };

    updateActiveGuestCount();
    window.setInterval(updateActiveGuestCount, 30000);

    // ── Events Horizontal Carousel Controller ──
    const eventsTrack = document.getElementById('hpEventsTrack');
    const eventsPrevBtn = document.getElementById('hpEventsPrev');
    const eventsNextBtn = document.getElementById('hpEventsNext');
    const eventsDotsContainer = document.getElementById('hpEventsDots');

    if (eventsTrack) {
        const getCardWidth = () => {
            const firstCard = eventsTrack.querySelector('.hp-event-card');
            if (!firstCard) return eventsTrack.clientWidth;
            const gap = parseFloat(getComputedStyle(eventsTrack).gap) || 24;
            return firstCard.offsetWidth + gap;
        };

        const updateEventsNav = () => {
            const maxScroll = eventsTrack.scrollWidth - eventsTrack.clientWidth;
            const currentScroll = eventsTrack.scrollLeft;

            if (eventsPrevBtn) {
                const isStart = currentScroll <= 6;
                eventsPrevBtn.disabled = isStart;
                eventsPrevBtn.classList.toggle('is-disabled', isStart);
            }
            if (eventsNextBtn) {
                const isEnd = currentScroll >= maxScroll - 6;
                eventsNextBtn.disabled = isEnd;
                eventsNextBtn.classList.toggle('is-disabled', isEnd);
            }

            // Sync active dot
            if (eventsDotsContainer) {
                const cardWidth = getCardWidth();
                const activeIndex = Math.min(
                    Math.round(currentScroll / cardWidth),
                    eventsDotsContainer.children.length - 1
                );
                const dots = eventsDotsContainer.querySelectorAll('.hp-events-dot');
                dots.forEach((dot, idx) => {
                    dot.classList.toggle('is-active', idx === activeIndex);
                });
            }
        };

        eventsPrevBtn?.addEventListener('click', () => {
            const cardWidth = getCardWidth();
            eventsTrack.scrollBy({ left: -cardWidth, behavior: 'smooth' });
        });

        eventsNextBtn?.addEventListener('click', () => {
            const cardWidth = getCardWidth();
            eventsTrack.scrollBy({ left: cardWidth, behavior: 'smooth' });
        });

        if (eventsDotsContainer) {
            const dots = eventsDotsContainer.querySelectorAll('.hp-events-dot');
            dots.forEach((dot) => {
                dot.addEventListener('click', () => {
                    const idx = parseInt(dot.dataset.index ?? '0', 10);
                    const cardWidth = getCardWidth();
                    eventsTrack.scrollTo({ left: idx * cardWidth, behavior: 'smooth' });
                });
            });
        }

        eventsTrack.addEventListener('scroll', updateEventsNav, { passive: true });
        window.addEventListener('resize', updateEventsNav, { passive: true });
        updateEventsNav();
    }

    // ── Park Closed Notice Modal Controller ──
    const parkClosedModal = document.getElementById('parkClosedModal');
    const closedStatusBtn = document.getElementById('hpStatusClosedBtn');

    if (parkClosedModal) {
        let bsModalInstance = null;
        const hasBootstrapJs = typeof window.bootstrap !== 'undefined' && typeof window.bootstrap.Modal !== 'undefined';

        if (hasBootstrapJs) {
            try {
                bsModalInstance = new window.bootstrap.Modal(parkClosedModal, {
                    backdrop: 'static',
                    keyboard: true
                });
            } catch (e) {
                console.warn('Could not initialize bootstrap.Modal instance, using native fallback', e);
            }
        }

        const showNoticeModal = () => {
            if (bsModalInstance) {
                bsModalInstance.show();
                return;
            }

            // Fallback native modal display
            let backdrop = document.querySelector('.hp-modal-backdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.className = 'hp-modal-backdrop';
                document.body.appendChild(backdrop);
            }

            document.body.classList.add('hp-modal-open');
            parkClosedModal.style.display = 'block';
            parkClosedModal.setAttribute('aria-hidden', 'false');

            // Force reflow for smooth CSS transitions
            void parkClosedModal.offsetHeight;

            backdrop.classList.add('show');
            parkClosedModal.classList.add('show');
        };

        const hideNoticeModal = () => {
            if (bsModalInstance) {
                bsModalInstance.hide();
            } else {
                parkClosedModal.classList.remove('show');
                const backdrop = document.querySelector('.hp-modal-backdrop');
                if (backdrop) {
                    backdrop.classList.remove('show');
                    window.setTimeout(() => backdrop.remove(), 300);
                }
                window.setTimeout(() => {
                    parkClosedModal.style.display = 'none';
                    parkClosedModal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('hp-modal-open');
                }, 300);
            }

            try {
                sessionStorage.setItem('hp_park_closed_modal_dismissed', '1');
            } catch (e) {}
        };

        // Open automatically on initial page visit if not already dismissed in this session
        try {
            if (!sessionStorage.getItem('hp_park_closed_modal_dismissed')) {
                window.setTimeout(showNoticeModal, 500);
            }
        } catch (e) {
            window.setTimeout(showNoticeModal, 500);
        }

        // Clicking the red status dot in the header opens the notice modal
        closedStatusBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            showNoticeModal();
        });

        // Dismiss handlers
        parkClosedModal.querySelectorAll('[data-bs-dismiss="modal"]').forEach((btn) => {
            btn.addEventListener('click', hideNoticeModal);
        });

        parkClosedModal.addEventListener('click', (e) => {
            if (e.target === parkClosedModal) {
                hideNoticeModal();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && parkClosedModal.classList.contains('show')) {
                hideNoticeModal();
            }
        });
    }
});
