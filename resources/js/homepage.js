document.addEventListener('DOMContentLoaded', () => {
    const siteHeader = document.getElementById('hpSiteHeader');
    const header = document.getElementById('hpHeader');
    const guestCountEl = document.getElementById('activeGuestCount');
    const scrollToTopBtn = document.getElementById('scrollToTop');
    const navLinks = document.querySelectorAll('#hpDesktopNav [data-nav-link], #hpMobileNav [data-nav-link]');
    const sections = document.querySelectorAll('[data-section]');
    const animatedElements = document.querySelectorAll('[data-animate]');

    const getScrollOffset = () => (siteHeader?.offsetHeight ?? 0) + 8;

    const syncHeaderOffset = () => {
        if (!siteHeader) return;
        document.documentElement.style.setProperty('--hp-header-offset', `${siteHeader.offsetHeight}px`);
    };

    syncHeaderOffset();
    window.addEventListener('resize', syncHeaderOffset, { passive: true });

    // ── Visual Zoom & Wide Screen Scale Sync ──
    // Ensures elements maintain their physical size, proportions and screen placement on zoom-out (Ctrl -)
    const syncResponsiveZoom = () => {
        const width = window.innerWidth;
        const baselineWidth = 1440;

        if (width > 1520) {
            // Screen width expanded due to browser zoom out or ultra-high resolution
            const scale = Math.min(2.5, width / baselineWidth);
            document.documentElement.style.fontSize = `${(16 * scale).toFixed(2)}px`;
        } else {
            document.documentElement.style.fontSize = '';
        }
    };

    syncResponsiveZoom();
    window.addEventListener('resize', syncResponsiveZoom, { passive: true });



    // Mobile menu
    const menuToggle = document.getElementById('hpMobileMenuToggle') || document.querySelector('.hp-menu-toggle');
    const mobileNav = document.getElementById('hpMobileNav') || document.querySelector('.hp-mobile-nav');
    const mobileNavClose = document.getElementById('hpMobileNavClose');
    const mobileNavBackdrop = document.getElementById('hpMobileNavBackdrop');
    const mobileLinks = mobileNav?.querySelectorAll('a');

    const openMobileNav = () => {
        if (!mobileNav) return;
        mobileNav.classList.add('is-open');
        mobileNav.classList.remove('translate-x-full');
        mobileNav.classList.add('translate-x-0');
        mobileNav.setAttribute('aria-hidden', 'false');
        menuToggle?.setAttribute('aria-expanded', 'true');
        if (mobileNavBackdrop) {
            mobileNavBackdrop.classList.remove('hidden');
            requestAnimationFrame(() => {
                mobileNavBackdrop.classList.remove('opacity-0');
            });
        }
        document.body.style.overflow = 'hidden';
    };

    const closeMobileNav = () => {
        if (!mobileNav) return;
        mobileNav.classList.remove('is-open');
        mobileNav.classList.remove('translate-x-0');
        mobileNav.classList.add('translate-x-full');
        mobileNav.setAttribute('aria-hidden', 'true');
        menuToggle?.setAttribute('aria-expanded', 'false');
        if (mobileNavBackdrop) {
            mobileNavBackdrop.classList.add('opacity-0');
            setTimeout(() => {
                if (!mobileNav.classList.contains('is-open')) {
                    mobileNavBackdrop.classList.add('hidden');
                }
            }, 300);
        }
        document.body.style.overflow = '';
    };

    menuToggle?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (mobileNav?.classList.contains('is-open')) {
            closeMobileNav();
        } else {
            openMobileNav();
        }
    });

    mobileNavClose?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        closeMobileNav();
    });

    mobileNavBackdrop?.addEventListener('click', closeMobileNav);

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

    // Section IDs corresponding to the nav links in exact document order
    const navSectionIds = ['about', 'activities', 'amenities', 'events', 'rates', 'reviews', 'gallery', 'directions'];

    const getNavSections = () => {
        return navSectionIds
            .map((id) => document.getElementById(id))
            .filter(Boolean)
            .sort((a, b) => a.offsetTop - b.offsetTop);
    };

    // Scroll spy — active nav link (clean, fixed directly to each link)
    const setActiveNav = (sectionId) => {
        navLinks.forEach((link) => {
            const href = link.getAttribute('href');
            const isActive = Boolean(sectionId) && href === `#${sectionId}`;
            link.classList.toggle('is-active', isActive);
        });
    };

    // Smooth scroll for anchor links
    navLinks.forEach((anchor) => {
        anchor.addEventListener('click', (e) => {
            const targetId = anchor.getAttribute('href');
            if (!targetId || !targetId.startsWith('#')) return;

            const target = document.querySelector(targetId);
            if (!target) return;

            e.preventDefault();
            const top = target.getBoundingClientRect().top + window.scrollY - getScrollOffset();

            setActiveNav(targetId.replace('#', ''));
            window.scrollTo({ top, behavior: 'smooth' });
            closeMobileNav();
        });
    });

    // Home logo click scrolls to top and clears active nav
    const homeAnchor = document.querySelector('a[href="#home"]');
    homeAnchor?.addEventListener('click', (e) => {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setActiveNav('');
        closeMobileNav();
    });

    const updateActiveNav = () => {
        const scrollPos = window.scrollY;
        const triggerOffset = getScrollOffset() + 60;
        const navSections = getNavSections();

        if (navSections.length === 0) return;

        // If above the first section (Hero), clear active nav
        if (scrollPos < (navSections[0].offsetTop - triggerOffset)) {
            setActiveNav('');
            return;
        }

        // If scrolled to the bottom of the page, highlight the last section
        if ((window.innerHeight + scrollPos) >= (document.documentElement.scrollHeight - 60)) {
            const lastSection = navSections[navSections.length - 1];
            if (lastSection) {
                setActiveNav(lastSection.id);
                return;
            }
        }

        // Find the section currently in view by scanning from bottom up
        let activeId = '';
        for (let i = navSections.length - 1; i >= 0; i--) {
            const section = navSections[i];
            const top = section.offsetTop - triggerOffset;
            if (scrollPos >= top) {
                activeId = section.id;
                break;
            }
        }

        setActiveNav(activeId);
    };

    // Sticky header background & active nav on scroll
    let isScrollTicking = false;
    const handleScroll = () => {
        const scrolled = window.scrollY > 40;
        siteHeader?.classList.toggle('is-scrolled', scrolled);
        header?.classList.toggle('is-scrolled', scrolled);
        scrollToTopBtn?.classList.toggle('is-visible', window.scrollY > 500);

        updateActiveNav();
    };

    window.addEventListener('scroll', () => {
        if (!isScrollTicking) {
            window.requestAnimationFrame(() => {
                handleScroll();
                isScrollTicking = false;
            });
            isScrollTicking = true;
        }
    }, { passive: true });

    handleScroll();

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

    // Activities carousel: native horizontal scrolling with side arrow indicators, touch swipe, mouse drag and wheel
    const activitiesTrack = document.getElementById('hpActivitiesTrack');
    const sidePrevBtn = document.getElementById('hpActivitiesSidePrev');
    const sideNextBtn = document.getElementById('hpActivitiesSideNext');
    const activityCards = activitiesTrack?.querySelectorAll('[data-activity-card]') ?? [];

    const activityModal = document.getElementById('hpActivityModal');
    const activityModalImage = document.getElementById('hpActivityModalImage');
    const activityModalTitle = document.getElementById('hpActivityModalTitle');
    const activityModalDescription = document.getElementById('hpActivityModalDescription');
    let activityModalTrigger = null;

    const closeActivityModal = () => {
        if (!activityModal) return;
        activityModal.classList.remove('is-open');
        activityModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('hp-modal-open');
        activityModalTrigger?.focus();
    };

    const openActivityModal = (card) => {
        activityModalTrigger = card;
        if (activityModalTitle) activityModalTitle.textContent = card.dataset.activityTitle ?? '';
        if (activityModalDescription) activityModalDescription.textContent = card.dataset.activityDescription ?? '';
        if (activityModalImage) {
            activityModalImage.src = card.dataset.activityImage ?? '';
            activityModalImage.alt = card.dataset.activityTitle ?? '';
            activityModalImage.hidden = !card.dataset.activityImage;
        }
        activityModal?.classList.add('is-open');
        activityModal?.setAttribute('aria-hidden', 'false');
        document.body.classList.add('hp-modal-open');
    };

    if (activitiesTrack && activityCards.length) {
        const getScrollStep = () => {
            const firstCard = activityCards[0];
            if (!firstCard) return activitiesTrack.clientWidth;
            const cardWidth = firstCard.offsetWidth;
            const trackStyle = window.getComputedStyle(activitiesTrack);
            const gap = parseFloat(trackStyle.gap || trackStyle.columnGap || '24') || 24;
            
            if (window.innerWidth < 700) {
                return cardWidth + gap;
            } else if (window.innerWidth < 1024) {
                return (cardWidth + gap) * 2;
            } else {
                return (cardWidth + gap) * 3;
            }
        };

        const updateActivityControls = () => {
            const maxScroll = Math.max(0, activitiesTrack.scrollWidth - activitiesTrack.clientWidth);
            const currentScroll = activitiesTrack.scrollLeft;

            if (sidePrevBtn) {
                const canScrollLeft = currentScroll > 10;
                sidePrevBtn.classList.toggle('opacity-0', !canScrollLeft);
                sidePrevBtn.classList.toggle('pointer-events-none', !canScrollLeft);
                sidePrevBtn.disabled = !canScrollLeft;
            }
            if (sideNextBtn) {
                const canScrollRight = currentScroll < maxScroll - 10;
                sideNextBtn.classList.toggle('opacity-0', !canScrollRight);
                sideNextBtn.classList.toggle('pointer-events-none', !canScrollRight);
                sideNextBtn.disabled = !canScrollRight;
            }
        };

        sidePrevBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            activitiesTrack.scrollBy({ left: -getScrollStep(), behavior: 'smooth' });
        });
        sideNextBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            activitiesTrack.scrollBy({ left: getScrollStep(), behavior: 'smooth' });
        });

        // ── Touch swipe handling (prevents modal popup when swiping on phone) ──
        let touchStartX = 0;
        let touchStartY = 0;
        let isTouching = false;
        let hasSwiped = false;

        activitiesTrack.addEventListener('touchstart', (e) => {
            if (!e.touches.length) return;
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
            isTouching = true;
            hasSwiped = false;
        }, { passive: true });

        activitiesTrack.addEventListener('touchmove', (e) => {
            if (!isTouching || !e.touches.length) return;
            const diffX = Math.abs(e.touches[0].clientX - touchStartX);
            const diffY = Math.abs(e.touches[0].clientY - touchStartY);
            if (diffX > 8) {
                hasSwiped = true;
            }
        }, { passive: true });

        const onTouchEnd = () => {
            isTouching = false;
            setTimeout(() => {
                hasSwiped = false;
            }, 150);
        };
        activitiesTrack.addEventListener('touchend', onTouchEnd, { passive: true });
        activitiesTrack.addEventListener('touchcancel', onTouchEnd, { passive: true });

        // ── Mouse drag-to-scroll on desktop ──
        let isMouseDown = false;
        let mouseStartX = 0;
        let scrollStartLeft = 0;
        let hasMouseDragged = false;

        activitiesTrack.addEventListener('mousedown', (e) => {
            if (e.button !== 0) return;
            isMouseDown = true;
            hasMouseDragged = false;
            mouseStartX = e.pageX - activitiesTrack.offsetLeft;
            scrollStartLeft = activitiesTrack.scrollLeft;
            activitiesTrack.classList.add('is-dragging');
        });

        window.addEventListener('mousemove', (e) => {
            if (!isMouseDown) return;
            const x = e.pageX - activitiesTrack.offsetLeft;
            const walk = (x - mouseStartX);
            if (Math.abs(walk) > 6) {
                hasMouseDragged = true;
            }
            activitiesTrack.scrollLeft = scrollStartLeft - walk;
        });

        window.addEventListener('mouseup', () => {
            if (isMouseDown) {
                isMouseDown = false;
                activitiesTrack.classList.remove('is-dragging');
                setTimeout(() => {
                    hasMouseDragged = false;
                }, 120);
            }
        });

        // ── Mouse wheel horizontal scroll ──
        activitiesTrack.addEventListener('wheel', (e) => {
            if (Math.abs(e.deltaY) > Math.abs(e.deltaX) && Math.abs(e.deltaY) > 5) {
                const maxScroll = activitiesTrack.scrollWidth - activitiesTrack.clientWidth;
                const canScrollLeft = activitiesTrack.scrollLeft > 0 && e.deltaY < 0;
                const canScrollRight = activitiesTrack.scrollLeft < maxScroll - 1 && e.deltaY > 0;
                
                if (canScrollLeft || canScrollRight) {
                    e.preventDefault();
                    activitiesTrack.scrollBy({
                        left: e.deltaY * 1.3,
                        behavior: 'auto'
                    });
                }
            }
        }, { passive: false });

        activitiesTrack.addEventListener('scroll', updateActivityControls, { passive: true });
        window.addEventListener('resize', updateActivityControls, { passive: true });
        updateActivityControls();

        // ── Attach card click with drag/swipe guard ──
        activityCards.forEach((card) => {
            card.addEventListener('click', (e) => {
                if (hasSwiped || hasMouseDragged) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }
                openActivityModal(card);
            });
            card.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openActivityModal(card);
                }
            });
        });
    }

    activityModal?.querySelectorAll('[data-activity-modal-close]').forEach((element) => {
        element.addEventListener('click', closeActivityModal);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activityModal?.classList.contains('is-open')) closeActivityModal();
    });

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

    // ── Gallery & Lightbox Modals Controller ──
    const allPhotosModal = document.getElementById('hpAllPhotosModal');
    const closeAllPhotosBtn = document.getElementById('hpCloseAllPhotosBtn');
    const allPhotosBackdrop = document.getElementById('hpAllPhotosBackdrop');

    const imageLightboxModal = document.getElementById('hpImageLightboxModal');
    const closeLightboxBtn = document.getElementById('hpCloseLightboxBtn');
    const lightboxBackdrop = document.getElementById('hpLightboxBackdrop');
    const lightboxPrevBtn = document.getElementById('hpLightboxPrevBtn');
    const lightboxNextBtn = document.getElementById('hpLightboxNextBtn');
    const lightboxImage = document.getElementById('hpLightboxImage');
    const lightboxCounter = document.getElementById('hpLightboxCounter');

    let galleryData = { featured: [], all: [] };
    try {
        const raw = document.getElementById('hpGalleryData')?.textContent;
        if (raw) {
            galleryData = JSON.parse(raw);
        }
    } catch (e) {
        console.warn('Failed to parse gallery data:', e);
    }

    let currentLightboxList = [];
    let currentLightboxIndex = 0;

    // Open All Photos Modal
    const openAllPhotosModal = () => {
        if (!allPhotosModal) return;
        allPhotosModal.style.display = 'flex';
        void allPhotosModal.offsetHeight; // trigger reflow
        allPhotosModal.classList.remove('opacity-0', 'pointer-events-none', 'hidden');
        allPhotosModal.classList.add('opacity-100', 'pointer-events-auto', 'is-open');
        allPhotosModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('hp-modal-open');
        document.body.style.overflow = 'hidden';
    };

    const closeAllPhotosModal = () => {
        if (!allPhotosModal) return;
        allPhotosModal.classList.remove('opacity-100', 'pointer-events-auto', 'is-open');
        allPhotosModal.classList.add('opacity-0', 'pointer-events-none');
        allPhotosModal.setAttribute('aria-hidden', 'true');
        setTimeout(() => {
            if (!allPhotosModal.classList.contains('is-open')) {
                allPhotosModal.style.display = 'none';
            }
        }, 280);
        if (!imageLightboxModal?.classList.contains('is-open')) {
            document.body.classList.remove('hp-modal-open');
            document.body.style.overflow = '';
        }
    };

    // Global hooks for inline onclick calls
    window.hpOpenAllGallery = openAllPhotosModal;
    window.hpCloseAllPhotos = closeAllPhotosModal;

    // Wire all "See More Photos" / "View More Photos" buttons
    document.querySelectorAll('#hpOpenAllGalleryBtn, #hpOpenAllGalleryGridBtn, [data-open-gallery-all]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openAllPhotosModal();
        });
        btn.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openAllPhotosModal();
            }
        });
    });

    closeAllPhotosBtn?.addEventListener('click', closeAllPhotosModal);
    allPhotosBackdrop?.addEventListener('click', closeAllPhotosModal);

    // Lightbox Controls (No names displayed)
    const updateLightbox = () => {
        if (!currentLightboxList.length || !lightboxImage) return;
        const item = currentLightboxList[currentLightboxIndex];
        if (!item) return;

        lightboxImage.style.opacity = '0';
        lightboxImage.style.transform = 'scale(0.96)';

        setTimeout(() => {
            lightboxImage.src = item.url;
            lightboxImage.alt = 'Hinaguan Nature Park Full Image';
            if (lightboxCounter) {
                lightboxCounter.textContent = `${currentLightboxIndex + 1} / ${currentLightboxList.length}`;
            }

            if (lightboxPrevBtn) lightboxPrevBtn.disabled = currentLightboxIndex <= 0;
            if (lightboxNextBtn) lightboxNextBtn.disabled = currentLightboxIndex >= currentLightboxList.length - 1;

            lightboxImage.onload = () => {
                lightboxImage.style.opacity = '1';
                lightboxImage.style.transform = 'scale(1)';
            };
            lightboxImage.style.opacity = '1';
            lightboxImage.style.transform = 'scale(1)';
        }, 80);
    };

    const openLightbox = (list, index) => {
        if (!imageLightboxModal || !list || !list.length) return;
        currentLightboxList = list;
        currentLightboxIndex = Math.max(0, Math.min(index, list.length - 1));
        
        imageLightboxModal.style.display = 'flex';
        void imageLightboxModal.offsetHeight;
        imageLightboxModal.classList.remove('opacity-0', 'pointer-events-none', 'hidden');
        imageLightboxModal.classList.add('opacity-100', 'pointer-events-auto', 'is-open');
        imageLightboxModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('hp-modal-open');
        document.body.style.overflow = 'hidden';

        updateLightbox();
    };

    const closeLightbox = () => {
        if (!imageLightboxModal) return;
        imageLightboxModal.classList.remove('opacity-100', 'pointer-events-auto', 'is-open');
        imageLightboxModal.classList.add('opacity-0', 'pointer-events-none');
        imageLightboxModal.setAttribute('aria-hidden', 'true');
        setTimeout(() => {
            if (!imageLightboxModal.classList.contains('is-open')) {
                imageLightboxModal.style.display = 'none';
            }
        }, 280);

        if (!allPhotosModal?.classList.contains('is-open')) {
            document.body.classList.remove('hp-modal-open');
            document.body.style.overflow = '';
        }
    };

    window.hpOpenLightbox = openLightbox;
    window.hpCloseLightbox = closeLightbox;

    closeLightboxBtn?.addEventListener('click', closeLightbox);
    lightboxBackdrop?.addEventListener('click', closeLightbox);

    lightboxPrevBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        if (currentLightboxIndex > 0) {
            currentLightboxIndex--;
            updateLightbox();
        }
    });

    lightboxNextBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        if (currentLightboxIndex < currentLightboxList.length - 1) {
            currentLightboxIndex++;
            updateLightbox();
        }
    });

    // Touch swipe support on Lightbox for phones
    let lbTouchStartX = 0;
    let lbTouchStartY = 0;
    imageLightboxModal?.addEventListener('touchstart', (e) => {
        if (!e.touches.length) return;
        lbTouchStartX = e.touches[0].clientX;
        lbTouchStartY = e.touches[0].clientY;
    }, { passive: true });

    imageLightboxModal?.addEventListener('touchend', (e) => {
        if (!e.changedTouches.length) return;
        const diffX = e.changedTouches[0].clientX - lbTouchStartX;
        const diffY = Math.abs(e.changedTouches[0].clientY - lbTouchStartY);
        if (Math.abs(diffX) > 40 && diffY < 60) {
            if (diffX < 0 && currentLightboxIndex < currentLightboxList.length - 1) {
                currentLightboxIndex++;
                updateLightbox();
            } else if (diffX > 0 && currentLightboxIndex > 0) {
                currentLightboxIndex--;
                updateLightbox();
            }
        }
    }, { passive: true });

    // Keyboard navigation (Escape, Left, Right)
    document.addEventListener('keydown', (e) => {
        if (imageLightboxModal?.classList.contains('is-open')) {
            if (e.key === 'Escape') {
                closeLightbox();
            } else if (e.key === 'ArrowLeft' && currentLightboxIndex > 0) {
                currentLightboxIndex--;
                updateLightbox();
            } else if (e.key === 'ArrowRight' && currentLightboxIndex < currentLightboxList.length - 1) {
                currentLightboxIndex++;
                updateLightbox();
            }
        } else if (allPhotosModal?.classList.contains('is-open')) {
            if (e.key === 'Escape') {
                closeAllPhotosModal();
            }
        }
    });

    // Attach click handlers to Featured Gallery items on homepage (maps to all for seamless full browsing)
    document.querySelectorAll('.hp-gallery-item').forEach((item) => {
        item.addEventListener('click', () => {
            const index = parseInt(item.dataset.galleryIndex || '0', 10);
            const src = item.dataset.gallerySrc;
            let targetList = galleryData.all && galleryData.all.length ? galleryData.all : galleryData.featured;
            let targetIndex = index;
            if (galleryData.all && galleryData.all.length && src) {
                const found = galleryData.all.findIndex((img) => img.url === src || img.filename === src);
                if (found !== -1) {
                    targetIndex = found;
                }
            }
            openLightbox(targetList, targetIndex);
        });
        item.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                item.click();
            }
        });
    });

    // Attach click handlers to All Photos Modal items
    document.querySelectorAll('.hp-all-photos-item').forEach((item) => {
        item.addEventListener('click', () => {
            const index = parseInt(item.dataset.galleryIndex || '0', 10);
            openLightbox(galleryData.all, index);
        });
        item.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                item.click();
            }
        });
    });
});
