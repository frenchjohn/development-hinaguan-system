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

    mobileLinks?.forEach((link) => {
        link.addEventListener('click', closeMobileNav);
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
});
