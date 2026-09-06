import { Html5Qrcode } from 'html5-qrcode';
import { showToast, queueToast, showPendingToast, convertFlashToToast } from './toast.js';

let activeStaffCheckInsHandlers = null;

if (!window.__staffCheckInsGlobalClickBound) {
    window.__staffCheckInsGlobalClickBound = true;
    document.addEventListener('click', (e) => {
        const companionCheckoutBtn = e.target.closest('.resv-companion-checkout-btn');
        if (companionCheckoutBtn) {
            e.preventDefault();
            e.stopPropagation();
            activeStaffCheckInsHandlers?.openCompanionCheckoutModal?.(companionCheckoutBtn.dataset.reservationId);
            return;
        }

        const extendStayBtn = e.target.closest('.resv-extend-stay-btn');
        if (extendStayBtn) {
            e.preventDefault();
            e.stopPropagation();
            activeStaffCheckInsHandlers?.openExtendStayModal?.(extendStayBtn.dataset.reservationId);
            return;
        }

        const addAmenityBtn = e.target.closest('.resv-add-amenity-btn');
        if (addAmenityBtn) {
            e.preventDefault();
            e.stopPropagation();
            activeStaffCheckInsHandlers?.openAddAmenityMidStayModal?.(addAmenityBtn.dataset.reservationId);
            return;
        }

        const extendAmenityBtn = e.target.closest('.resv-amenity-extend-btn');
        if (extendAmenityBtn) {
            e.preventDefault();
            e.stopPropagation();
            activeStaffCheckInsHandlers?.openExtendAmenityModal?.(extendAmenityBtn.dataset.reservationId, extendAmenityBtn.dataset.reservationAmenityId);
            return;
        }

        const amenityCheckoutBtn = e.target.closest('.resv-amenity-checkout-btn');
        if (amenityCheckoutBtn) {
            e.preventDefault();
            e.stopPropagation();
            activeStaffCheckInsHandlers?.handleAmenityCheckout?.(amenityCheckoutBtn);
            return;
        }

        if (e.target.classList.contains('guest-companion-pill__delete')) {
            e.target.closest('.guest-companion-pill')?.remove();
            return;
        }
    });

    window.addEventListener('beforeunload', () => {
        activeStaffCheckInsHandlers?.stopQrScanner?.();
    });
    window.addEventListener('spa:leaving', () => {
        activeStaffCheckInsHandlers?.stopQrScanner?.();
    });
}

window.AppPage = window.AppPage || {};
window.AppPage['staff_check_ins'] = function () {


    const tabGuestBtn = document.getElementById('tabGuestBtn');
    const tabReservationBtn = document.getElementById('tabReservationBtn');
    const guestTableSection = document.getElementById('guestTableSection');
    const reservationTableSection = document.getElementById('reservationTableSection');
    const reservationTableBody = document.getElementById('checkInsReservationTableBody');
    const reservationModal = document.getElementById('reservationModal');
    const reservationModalBody = document.getElementById('reservationModalBody');
    const reservationCheckOutBtn = document.getElementById('reservationCheckOutBtn');
    const reservationAddCompanionBtn = document.getElementById('reservationAddCompanionBtn');
    const reservationCompanionCheckoutHeaderBtn = document.getElementById('reservationCompanionCheckoutHeaderBtn');
    const reservationCloseButtons = document.querySelectorAll('[data-close-reservation-modal="true"]');
    const checkOutConfirmModal = document.getElementById('checkOutConfirmModal');
    const confirmCheckOutBtn = document.getElementById('confirmCheckOutBtn');
    const checkOutConfirmCloseButtons = document.querySelectorAll('[data-close-check-out-confirm="true"]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const reservationData = window.staffReservationData || {};
    const guestData = window.staffGuestData || {};

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');

    // Bulk companions store a representative midpoint age (0-12→6, 13-17→15,
    // 18-59→30, 60+→65). Display the actual age group instead of the midpoint.
    const ageGroupLabel = (age) => {
        const n = parseInt(age, 10);
        if (isNaN(n)) return age || 'N/A';
        if (n <= 12) return '0-12';
        if (n <= 17) return '13-17';
        if (n <= 59) return '18-59';
        return '60+';
    };

    let currentReservationId = null;
    let companionCount = 0;

    // Reservation id captured when the user clicks "Check Out" on the details
    // modal. Confirming must still work even if the details modal was closed in
    // the meantime (closing it clears currentReservationId).
    let pendingCheckOutReservationId = null;

    // Initialize: show dashboard table by default
    const dashboardSection = document.getElementById('dashboardSection');

    if (dashboardSection && guestTableSection && reservationTableSection) {
        // HTML already has the correct initial display states:
        // guest = visible, dashboard = hidden, reservation = hidden
        // Just ensure the active tab class is set correctly
    }

    // Tab switching
    const switchTab = (target) => {
        if (dashboardSection) dashboardSection.style.display = target === 'dashboard' ? '' : 'none';
        if (guestTableSection) guestTableSection.style.display = target === 'guest' ? '' : 'none';
        if (reservationTableSection) reservationTableSection.style.display = target === 'reservation' ? '' : 'none';

        document.querySelectorAll('.checkins-tab').forEach(btn => btn.classList.remove('is-active'));
        document.querySelectorAll(`.checkins-tab[data-tab-target="${target}"]`).forEach(btn => btn.classList.add('is-active'));
    };

    document.querySelectorAll('.checkins-tab[data-tab-target]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const target = e.currentTarget.getAttribute('data-tab-target');
            switchTab(target);
        });
    });

    // ── Checkout countdowns ──────────────────────────────────────────────
    const CHECKOUT_NEAR_MS = 60 * 60 * 1000;       // 1 hour before checkout
    const CHECKOUT_WARN_MS = 10 * 60 * 1000;       // 10 minutes before checkout

    const formatTimeLeft = (ms) => {
        const totalSeconds = Math.max(0, Math.floor(ms / 1000));
        const days = Math.floor(totalSeconds / 86400);
        const hours = Math.floor((totalSeconds % 86400) / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        if (days > 0) return `${days}d ${hours}h ${minutes}m`;
        if (hours > 0) return `${hours}h ${minutes}m`;
        if (minutes > 0) return `${minutes}m ${seconds}s`;
        return `${seconds}s`;
    };

    // Returns { visible, tone, left, text } for a countdown timestamp (start or checkout).
    const getCountdownState = (startsAtIso, checkoutAtIso) => {
        const now = Date.now();

        // 1. If the amenity has not reached its scheduled start session yet:
        if (startsAtIso) {
            const startTarget = new Date(startsAtIso).getTime();
            if (!Number.isNaN(startTarget) && now < startTarget) {
                const remainingToStart = startTarget - now;
                return {
                    visible: true,
                    tone: 'before_start',
                    left: remainingToStart,
                    text: `${formatTimeLeft(remainingToStart)} left before this amenity starts`,
                };
            }
        }

        // 2. Once the amenity has reached its start session (or for reservation checkout):
        if (!checkoutAtIso) return { visible: false, tone: '', left: 0, text: '' };
        const checkoutTarget = new Date(checkoutAtIso).getTime();
        if (Number.isNaN(checkoutTarget)) return { visible: false, tone: '', left: 0, text: '' };

        const remaining = checkoutTarget - now;
        if (remaining <= 0) return { visible: true, tone: 'due', left: 0, text: 'Time to Checked Out' };
        const tone = remaining <= CHECKOUT_WARN_MS ? 'warn' : (remaining <= CHECKOUT_NEAR_MS ? 'near' : 'far');
        return {
            visible: true,
            tone,
            left: remaining,
            text: `${formatTimeLeft(remaining)} left before check out`,
        };
    };

    // Alias for reservation checkout state
    const getCheckoutState = (iso) => getCountdownState(null, iso);

    // Always-visible countdowns: reservation timer + per-amenity timers in the modal.
    const renderCountdownEl = (el) => {
        if (!el) return;
        const startsAt = el.dataset.startsAt;
        const checkoutAt = el.dataset.checkoutAt;
        const state = getCountdownState(startsAt, checkoutAt);
        if (!state.visible) {
            el.textContent = '';
            el.style.display = 'none';
            el.removeAttribute('data-checkout-state');
            return;
        }
        el.style.display = '';
        el.textContent = state.text;
        el.setAttribute('data-checkout-state', state.tone);
    };

    const refreshCheckoutCountdowns = () => {
        // Modal reservation timer + per-amenity timers
        document.querySelectorAll('.resv-checkout-countdown, .resv-amenity-countdown').forEach(renderCountdownEl);
        // Table Pills: Merged Time Left and Status column
        document.querySelectorAll('.table-time-left').forEach((el) => {
            const state = getCheckoutState(el.dataset.checkoutAt);
            const statusStr = el.dataset.status || '';
            const tr = el.closest('tr');

            const isCheckedOut = statusStr === 'checked_out' || statusStr === 'checkedout' || statusStr === 'checked-out';

            if (isCheckedOut) {
                el.innerHTML = `<span style="display:inline-flex; align-items:center; padding:0.25rem 0.6rem; border-radius:999px; font-size:0.7rem; font-weight:700; background: rgba(107, 114, 128, 0.1); color: #6b7280;">Checked Out</span>`;
                if (tr) tr.classList.remove('row-checkout-due', 'row-checkout-near');
                return;
            }

            if (!state.visible) {
                el.innerHTML = `<span style="display:inline-flex; align-items:center; padding:0.25rem 0.6rem; border-radius:999px; font-size:0.7rem; font-weight:700; background: rgba(22, 163, 74, 0.1); color: #16a34a;">Checked In</span>`;
                if (tr) tr.classList.remove('row-checkout-due', 'row-checkout-near');
                return;
            }

            const clockIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 0.9rem; height: 0.9rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;

            if (state.tone === 'due') {
                el.innerHTML = `<span style="display:inline-flex; align-items:center; gap:0.2rem; padding:0.25rem 0.6rem; border-radius:999px; font-size:0.7rem; font-weight:700; background: rgba(239, 68, 68, 0.1); color: #ef4444;">Time to checkout</span>`;
            } else {
                let colorClass = '#16a34a'; // green
                let bgClass = 'rgba(22, 163, 74, 0.1)';
                if (state.tone === 'warn' || state.tone === 'near') {
                    colorClass = '#ea580c'; // orange
                    bgClass = 'rgba(234, 88, 12, 0.1)';
                }
                el.innerHTML = `<span style="display:inline-flex; align-items:center; gap:0.2rem; padding:0.25rem 0.6rem; border-radius:999px; font-size:0.7rem; font-weight:700; background: ${bgClass}; color: ${colorClass};">${clockIcon} ${formatTimeLeft(state.left)} left</span>`;
            }

            if (tr) {
                if (state.tone === 'due') {
                    tr.classList.add('row-checkout-due');
                    tr.classList.remove('row-checkout-near');
                } else if (state.tone === 'warn' || state.tone === 'near') {
                    tr.classList.add('row-checkout-near');
                    tr.classList.remove('row-checkout-due');
                } else {
                    tr.classList.remove('row-checkout-due', 'row-checkout-near');
                }
            }
        });

        // Dynamic update of amenity due red dots on reservation table rows
        document.querySelectorAll('tr.reservation-row').forEach(row => {
            const rawTimes = row.dataset.amenityCheckoutTimes;
            if (!rawTimes) return;
            const times = rawTimes.split(',').map(t => t.trim()).filter(Boolean);
            const now = Date.now();
            const isAnyAmenityDue = times.some(t => {
                const target = new Date(t).getTime();
                return !Number.isNaN(target) && target <= now;
            });

            // Check if the entire reservation stay itself is already due for checkout
            const resCheckoutEl = row.querySelector('.table-time-left');
            const resCheckoutAt = resCheckoutEl ? resCheckoutEl.dataset.checkoutAt : '';
            const resTarget = resCheckoutAt ? new Date(resCheckoutAt).getTime() : NaN;
            const isReservationItselfDue = !Number.isNaN(resTarget) && resTarget <= now;

            // Only show specific amenity due alert if the reservation stay itself is NOT already due
            const shouldShowAmenityDue = isAnyAmenityDue && !isReservationItselfDue;

            const existingDots = row.querySelectorAll('.amenity-due-dot, .amenity-due-status-pill');
            if (shouldShowAmenityDue) {
                if (existingDots.length === 0) {
                    const idWrap = row.querySelector('td:first-child .guest-name');
                    if (idWrap && !idWrap.querySelector('.amenity-due-dot')) {
                        const dot = document.createElement('span');
                        dot.className = 'amenity-due-dot inline-block h-2 w-2 shrink-0 rounded-full bg-red-500 shadow-[0_0_6px_rgba(239,68,68,0.7)] animate-pulse';
                        dot.title = 'A specific amenity in this reservation is due for checkout';
                        idWrap.appendChild(dot);
                    }
                    const amWrap = row.querySelector('td:nth-child(4) .flex');
                    if (amWrap && !amWrap.querySelector('.amenity-due-dot')) {
                        const dot = document.createElement('span');
                        dot.className = 'amenity-due-dot inline-block h-2.5 w-2.5 shrink-0 rounded-full bg-red-500 shadow-[0_0_6px_rgba(239,68,68,0.7)] animate-pulse';
                        dot.title = 'A specific amenity in this reservation is due for checkout';
                        amWrap.appendChild(dot);
                    }
                    const statusWrap = row.querySelector('td:nth-child(6) .flex');
                    if (statusWrap && !statusWrap.querySelector('.amenity-due-status-pill')) {
                        const pill = document.createElement('span');
                        pill.className = 'amenity-due-status-pill inline-flex items-center gap-1 rounded-full bg-red-500/15 border border-red-500/30 px-2 py-0.5 text-[0.65rem] font-bold text-red-600 dark:text-red-400 shadow-2xs';
                        pill.title = 'One or more specific amenities are time to checkout';
                        pill.innerHTML = '<span class="h-1.5 w-1.5 rounded-full bg-red-500 animate-pulse"></span> Amenity Due';
                        statusWrap.appendChild(pill);
                    }
                }
            } else {
                existingDots.forEach(d => d.remove());
            }
        });

        // Dynamic update of amenity tab button red dot in open modal
        const modalAmenityTabBtn = document.querySelector('#reservationModal .resv-tab-btn[data-target-pane="resvPaneAmenities"]');
        if (modalAmenityTabBtn) {
            const hasDueAmenityInModal = Boolean(document.querySelector('#resvPaneAmenities .resv-amenity-countdown[data-checkout-state="due"]'));
            const existingTabDot = modalAmenityTabBtn.querySelector('.amenity-tab-due-dot');
            if (hasDueAmenityInModal) {
                if (!existingTabDot) {
                    const dot = document.createElement('span');
                    dot.className = 'amenity-tab-due-dot inline-block h-2 w-2 shrink-0 rounded-full bg-red-500 shadow-[0_0_6px_rgba(239,68,68,0.7)] animate-pulse';
                    dot.title = 'Amenity time to checkout';
                    modalAmenityTabBtn.appendChild(dot);
                }
            } else if (existingTabDot) {
                existingTabDot.remove();
            }
        }

        // Dynamic update of top Master "Reservations" tab red dot
        const masterResvTab = document.querySelector('.checkins-tab[data-tab-target="reservation"]');
        if (masterResvTab) {
            const hasAnyDueInTable = Boolean(
                document.querySelector('tr.reservation-row .amenity-due-dot') ||
                document.querySelector('tr.reservation-row.row-checkout-due') ||
                document.querySelector('tr.reservation-row .amenity-due-status-pill')
            );
            const existingMasterDot = masterResvTab.querySelector('.master-resv-due-dot');
            if (hasAnyDueInTable) {
                if (!existingMasterDot) {
                    const dot = document.createElement('span');
                    dot.className = 'master-resv-due-dot inline-block h-2 w-2 rounded-full bg-red-500 shadow-[0_0_6px_rgba(239,68,68,0.7)] animate-pulse';
                    dot.title = 'Reservations have checkouts due';
                    masterResvTab.appendChild(dot);
                }
            } else if (existingMasterDot) {
                existingMasterDot.remove();
            }
        }
    };

    // Tick every 30s; tighten to 5s once any countdown enters the warning window.
    // Stored on window (with a guard) so SPA re-inits don't stack duplicate timers.
    let checkoutCadence = 30000;
    if (window.__staffCheckInsCheckoutTicker) clearInterval(window.__staffCheckInsCheckoutTicker);
    if (window.__staffCheckInsCheckoutAdaptTicker) clearInterval(window.__staffCheckInsCheckoutAdaptTicker);
    const startCheckoutTicker = (ms) => {
        if (window.__staffCheckInsCheckoutTicker) clearInterval(window.__staffCheckInsCheckoutTicker);
        window.__staffCheckInsCheckoutTicker = setInterval(refreshCheckoutCountdowns, ms);
        checkoutCadence = ms;
    };
    startCheckoutTicker(30000);
    window.__staffCheckInsCheckoutAdaptTicker = setInterval(() => {
        const hot = document.querySelector('.resv-checkout-countdown[data-checkout-state="warn"], .resv-checkout-countdown[data-checkout-state="due"], .resv-amenity-countdown[data-checkout-state="warn"], .resv-amenity-countdown[data-checkout-state="due"], .table-checkout-countdown[data-checkout-state="warn"], .table-checkout-countdown[data-checkout-state="due"]');
        const desired = hot ? 5000 : 30000;
        if (desired !== checkoutCadence) {
            startCheckoutTicker(desired);
        }
    }, 5000);

    refreshCheckoutCountdowns();

    const formatDate = (dateStr) => {
        if (!dateStr) return 'N/A';
        const clean = String(dateStr).trim();
        if (/^\d{4}-\d{2}-\d{2}$/.test(clean)) {
            const [y, m, d] = clean.split('-').map(Number);
            return new Date(y, m - 1, d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }
        const dt = new Date(clean);
        return isNaN(dt.getTime()) ? clean.replace(/T.*$/, '').replace(/Z$/, '') : dt.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    };

    const formatExpectedCheckout = (res) => {
        if (!res) return { date: 'N/A', session: 'Daytime', time: '', fullText: 'N/A' };

        let session = res.end_slot || res.start_slot;
        if (!session && res.reservation_amenities && res.reservation_amenities.length > 0) {
            const lastAmenity = res.reservation_amenities[res.reservation_amenities.length - 1];
            session = lastAmenity.end_slot || (lastAmenity.pricing_type && lastAmenity.pricing_type.toLowerCase().includes('night') ? 'Nighttime' : 'Daytime');
        }
        session = session ? (session.toLowerCase().includes('night') ? 'Nighttime' : 'Daytime') : 'Daytime';

        let rawDate = res.end_date || res.reservation_date;
        let formattedDate = 'N/A';
        let formattedTime = session === 'Nighttime' ? '8:00 AM (Next Morning)' : '5:00 PM (Daytime)';

        if (res.checkout_at) {
            const dt = new Date(res.checkout_at);
            if (!isNaN(dt.getTime())) {
                formattedDate = dt.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                formattedTime = dt.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            }
        }

        if (formattedDate === 'N/A' && rawDate) {
            const [y, m, d] = rawDate.split('-').map(Number);
            const dt = new Date(y, m - 1, d);
            if (session === 'Nighttime') {
                dt.setDate(dt.getDate() + 1);
            }
            formattedDate = dt.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        return {
            date: formattedDate,
            session: session,
            time: formattedTime,
            fullText: `${formattedDate} · ${session}${formattedTime ? ` (${formattedTime})` : ''}`
        };
    };

    // Reservation modal functions
    const openReservationModal = (reservationId) => {
        currentReservationId = reservationId;
        const reservation = (window.staffReservationData && window.staffReservationData[reservationId]) || reservationData[reservationId];

        if (!reservation) return;

        // Build modal content
        const guestsList = reservation.reservation_guests || [];
        const primaryGuest = guestsList.find(g => g.is_primary_guest);
        const companions = guestsList.filter(g => !g.is_primary_guest);
        const activeCompanions = companions.filter(c => !c.checked_out_at);

        const totalResGuests = guestsList.length || parseInt(reservation.number_of_guests || 0, 10);
        const activeResGuests = guestsList.filter(g => !g.checked_out_at).length;
        const totalPoolGuests = guestsList.filter(g => Boolean(g.has_pool_access)).length;
        const activePoolGuests = guestsList.filter(g => Boolean(g.has_pool_access) && !g.checked_out_at).length;

        // Does this reservation cover multiple amenity time periods?
        // (Daytime vs Daytime Aircon = same time; strip Aircon before comparing.)
        const validAmenities = (reservation.reservation_amenities || []).filter(a => a.price > 0 || a.price_at_booking > 0 || a.amenity_name || a.amenity);
        const uniquePricingTypes = [...new Set(validAmenities.map(a => String(a.pricing_type || 'N/A').replace(/\s*Aircon/gi, '').trim()))];
        const differentTime = validAmenities.length > 1 && uniquePricingTypes.length > 1;

        const expectedCheckout = formatExpectedCheckout(reservation);
        const startSlot = reservation.start_slot || 'Daytime';
        const endSlot = reservation.end_slot || startSlot;
        const isMultiDay = reservation.end_date && reservation.end_date !== reservation.reservation_date;

        const statusKey = String(reservation.status || '').toLowerCase().replace(/\s+/g, '_');
        const isCheckedIn = statusKey === 'checked_in' || statusKey === 'active' || statusKey.includes('checked');
        const showPerAmenityCheckout = isCheckedIn && differentTime;
        const hasActiveGuests = guestsList.some(g => !g.checked_out_at);
        const reservationTypeLabel = escapeHtml(reservation.reservation_type || (reservation.is_walk_in ? 'Walk-in' : 'Online Booking'));

        const primaryGuestId = primaryGuest?.customer?.id || primaryGuest?.customer_id || primaryGuest?.id || 'N/A';

        const resCheckoutState = getCheckoutState(reservation.checkout_at);
        const isReservationStayDue = isCheckedIn && resCheckoutState.visible && resCheckoutState.tone === 'due';

        const hasAmenityDue = validAmenities.some(a => {
            if ((a.status || 'Active') === 'Completed') return false;
            if (!a.checkout_at && !a.starts_at) return false;
            const st = getCountdownState(a.starts_at, a.checkout_at);
            return st.visible && st.tone === 'due';
        });

        let html = `
            ${isReservationStayDue ? `
                <!-- Reservation Stay Time Ended Reminder Banner -->
                <div class="resv-stay-due-banner mb-4 flex items-center justify-between gap-3.5 rounded-2xl border-2 border-red-500/40 bg-gradient-to-r from-red-500/15 via-red-500/10 to-rose-500/15 p-4 text-red-900 dark:text-red-200 shadow-sm">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-600 text-white shadow-sm ring-4 ring-red-500/20">
                            <svg class="h-6 w-6 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm sm:text-base font-black tracking-tight flex items-center gap-2 flex-wrap">
                                <span>Reservation Stay Time Has Ended</span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-red-600 px-2.5 py-0.5 text-[0.68rem] font-bold text-white uppercase tracking-wider shadow-2xs">
                                    <span class="h-1.5 w-1.5 rounded-full bg-white animate-ping"></span> Time to Check Out
                                </span>
                            </div>
                            <div class="text-xs text-red-700/90 dark:text-red-300/90 mt-0.5 font-medium">
                                The stay period for this reservation (${expectedCheckout.fullText}) has expired. Please check out the reservation and remaining guests.
                            </div>
                        </div>
                    </div>
                </div>
            ` : ''}
            <!-- Main Reservation Info Banner (Highlighting Reservation ID + Main Guest ID) -->
            <div class="rounded-2xl border border-glass-border bg-hp-cream/70 dark:bg-white/5 p-5 mb-4 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-800 text-white font-black text-xl shadow-md tracking-tight">
                            #${reservation.id}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <span class="font-display font-black text-xl text-hp-text tracking-tight">Reservation #${reservation.id}</span>
                                <span class="inline-flex items-center rounded-lg bg-hp-green/15 text-hp-green border border-hp-green/30 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wide">
                                    ${reservationTypeLabel}
                                </span>
                            </div>
                            <div class="text-sm text-hp-text-muted mt-1.5 flex items-center gap-2 flex-wrap">
                                <span>Main Guest: <strong class="text-hp-text font-bold">${escapeHtml(reservation.booker_name || (primaryGuest?.customer ? `${primaryGuest.customer.first_name} ${primaryGuest.customer.last_name}` : 'N/A'))}</strong> <span class="font-mono text-emerald-800 dark:text-emerald-300 font-bold bg-emerald-500/15 border border-emerald-500/30 px-2 py-0.5 rounded text-xs">ID: #${primaryGuestId}</span></span>
                                <span>&bull;</span>
                                <span><i class="bi bi-calendar3 me-1"></i>${formatDate(reservation.reservation_date)} <span class="font-bold">(${startSlot})</span>${isMultiDay ? ` → ${formatDate(reservation.end_date)} <span class="font-bold">(${endSlot})</span>` : ''}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Live Attendance & Payment Status Badges -->
                    <div class="flex items-center gap-2.5 flex-wrap shrink-0">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15 border border-emerald-500/30 px-4 py-1.5 text-xs font-bold text-emerald-800 dark:text-emerald-300 shadow-2xs">
                            <i class="bi bi-people-fill"></i> ${activeResGuests} / ${totalResGuests} Guests Inside
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-full ${reservation.payment_status?.toLowerCase() === 'paid' ? 'bg-emerald-500/15 border border-emerald-500/30 text-emerald-800 dark:text-emerald-300' : 'bg-amber-500/15 border border-amber-500/30 text-amber-800 dark:text-amber-300'} px-4 py-1.5 text-xs font-bold uppercase shadow-2xs">
                            ${(reservation.payment_status || 'PENDING').toUpperCase()}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Section Tabs Grid (4 Clean Tabs - NEVER scrolls horizontally, 100% visible) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 w-full my-4 shrink-0" style="display: grid !important; grid-template-columns: repeat(4, 1fr) !important; gap: 0.65rem !important;">
                <button type="button" class="resv-tab-btn active w-full inline-flex items-center justify-center gap-2 cursor-pointer rounded-xl bg-hp-green text-white shadow-sm py-3 px-3 text-xs sm:text-sm font-bold transition-all border border-transparent" data-target-pane="resvPaneOverview" style="min-height: 44px;">
                    <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                    <span class="truncate">Overview</span>
                </button>
                <button type="button" class="resv-tab-btn w-full inline-flex items-center justify-center gap-2 cursor-pointer rounded-xl border border-glass-border/60 bg-white/70 dark:bg-white/10 hover:bg-glass-hover text-hp-text-muted hover:text-hp-text py-3 px-3 text-xs sm:text-sm font-bold transition-all" data-target-pane="resvPaneGuests" style="min-height: 44px;">
                    <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                    <span class="truncate">Guests (${totalResGuests})</span>
                </button>
                <button type="button" class="resv-tab-btn w-full inline-flex items-center justify-center gap-2 cursor-pointer rounded-xl border border-glass-border/60 bg-white/70 dark:bg-white/10 hover:bg-glass-hover text-hp-text-muted hover:text-hp-text py-3 px-3 text-xs sm:text-sm font-bold transition-all" data-target-pane="resvPaneAmenities" style="min-height: 44px;">
                    <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                    <span class="truncate">Amenities (${validAmenities.length})</span>
                    ${hasAmenityDue ? '<span class="amenity-tab-due-dot inline-block h-2 w-2 shrink-0 rounded-full bg-red-500 shadow-[0_0_6px_rgba(239,68,68,0.7)] animate-pulse" title="Amenity is time to checkout"></span>' : ''}
                </button>
                <button type="button" class="resv-tab-btn w-full inline-flex items-center justify-center gap-2 cursor-pointer rounded-xl border border-glass-border/60 bg-white/70 dark:bg-white/10 hover:bg-glass-hover text-hp-text-muted hover:text-hp-text py-3 px-3 text-xs sm:text-sm font-bold transition-all" data-target-pane="resvPaneBilling" style="min-height: 44px;">
                    <svg class="h-4.5 w-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                    <span class="truncate">Billing</span>
                </button>
            </div>

            <!-- Tab Panes -->
            <div class="resv-tab-panes flex-1 min-h-0">
                <!-- ════════════════════════════════════════════════════════ -->
                <!-- PANE 1: OVERVIEW & STAY                                 -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div id="resvPaneOverview" class="resv-tab-pane space-y-4">
                    <!-- Key Information 4-Card Grid (Spacious & Clean) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div class="rounded-2xl border border-glass-border bg-glass p-4 flex flex-col justify-between shadow-2xs">
                            <span class="text-[0.72rem] font-bold text-hp-text-muted uppercase tracking-wider mb-1">Booker</span>
                            <div class="font-bold text-base text-hp-text truncate" title="${escapeHtml(reservation.booker_name || '')}">${escapeHtml(reservation.booker_name || 'N/A')}</div>
                            <span class="text-xs text-hp-text-muted mt-1">Lead Customer</span>
                        </div>
                        <div class="rounded-2xl border border-glass-border bg-glass p-4 flex flex-col justify-between shadow-2xs">
                            <span class="text-[0.72rem] font-bold text-hp-text-muted uppercase tracking-wider mb-1">Contact Info</span>
                            <div class="font-bold text-sm text-hp-text truncate">${escapeHtml(reservation.phone || 'No phone')}</div>
                            <span class="text-xs text-hp-text-muted truncate mt-1" title="${escapeHtml(reservation.email || '')}">${escapeHtml(reservation.email || 'No email')}</span>
                        </div>
                        <div class="rounded-2xl border border-glass-border bg-glass p-4 flex flex-col justify-between shadow-2xs">
                            <span class="text-[0.72rem] font-bold text-hp-text-muted uppercase tracking-wider mb-1">Reservation Stay</span>
                            <div class="font-bold text-sm text-hp-text">
                                ${isMultiDay
                ? `<span>${formatDate(reservation.reservation_date)} <span class="text-xs font-semibold text-hp-text-muted">(${startSlot})</span><br><span class="text-xs text-hp-text-muted font-normal">to</span> ${formatDate(reservation.end_date)} <span class="text-xs font-semibold text-hp-text-muted">(${endSlot})</span></span>`
                : `<span>${formatDate(reservation.reservation_date)} <span class="text-xs font-semibold text-hp-text-muted">(${startSlot})</span></span>`}
                            </div>
                            <div class="mt-2 flex items-center gap-1.5 flex-wrap">
                                ${isMultiDay ? `<span class="inline-flex rounded-md bg-hp-green/10 border border-hp-green/30 px-2 py-0.5 text-[0.68rem] font-bold text-hp-green">${reservation.total_days || 2} Days</span>` : ''}
                                ${(String(reservation.status || '').toLowerCase().includes('checked') || String(reservation.status || '').toLowerCase().includes('active')) ? `<button type="button" class="resv-extend-stay-btn inline-flex items-center gap-1 rounded-lg border border-hp-green/40 bg-hp-green/15 px-2.5 py-1 text-xs font-bold text-hp-green hover:bg-hp-green hover:text-white transition-all cursor-pointer shadow-2xs" data-reservation-id="${reservation.id}">Adjust / Extend</button>` : ''}
                            </div>
                        </div>
                        <div class="rounded-2xl border border-glass-border bg-glass p-4 flex flex-col justify-between shadow-2xs">
                            <span class="text-[0.72rem] font-bold text-hp-text-muted uppercase tracking-wider mb-1">Total Guests</span>
                            <div class="font-black text-2xl text-hp-text">${totalResGuests} <span class="text-sm font-semibold text-hp-text-muted">registered</span></div>
                            <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold mt-1"><i class="bi bi-people-fill me-1"></i>${activeResGuests} active inside</span>
                        </div>
                    </div>

                    <!-- Attendance Breakdown Stats Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
                        <div class="rounded-2xl border border-emerald-600/20 bg-emerald-600/5 dark:bg-emerald-950/20 p-4 shadow-2xs">
                            <span class="text-[0.72rem] font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">Total Guests</span>
                            <div class="text-2xl font-black text-emerald-800 dark:text-emerald-200 mt-1">${totalResGuests}</div>
                            <div class="text-xs text-hp-text-muted mt-1">Full reservation party</div>
                        </div>
                        <div class="rounded-2xl border border-emerald-600/20 bg-emerald-600/5 dark:bg-emerald-950/20 p-4 shadow-2xs">
                            <span class="text-[0.72rem] font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">Currently Inside</span>
                            <div class="text-2xl font-black text-emerald-800 dark:text-emerald-200 mt-1">${activeResGuests} <span class="text-sm font-medium text-hp-text-muted">/ ${totalResGuests}</span></div>
                            <div class="text-xs text-hp-text-muted mt-1">Active on premises</div>
                        </div>
                        <div class="rounded-2xl border border-sky-500/25 bg-sky-500/5 dark:bg-sky-950/20 p-4 shadow-2xs">
                            <span class="text-[0.72rem] font-bold text-sky-700 dark:text-sky-300 uppercase tracking-wider">Total Pool Passes</span>
                            <div class="text-2xl font-black text-sky-700 dark:text-sky-300 mt-1">${totalPoolGuests} <span class="text-sm font-medium text-hp-text-muted">/ ${totalResGuests}</span></div>
                            <div class="text-xs text-hp-text-muted mt-1">With pool pass inclusion</div>
                        </div>
                        <div class="rounded-2xl border border-sky-500/25 bg-sky-500/5 dark:bg-sky-950/20 p-4 shadow-2xs">
                            <span class="text-[0.72rem] font-bold text-sky-700 dark:text-sky-300 uppercase tracking-wider">Current Pool Guests</span>
                            <div class="text-2xl font-black text-sky-700 dark:text-sky-300 mt-1">${activePoolGuests} <span class="text-sm font-medium text-hp-text-muted">/ ${totalPoolGuests}</span></div>
                            <div class="text-xs text-hp-text-muted mt-1">Active pool users inside</div>
                        </div>
                    </div>

                    <!-- Expected Checkout Card -->
                    <div class="rounded-2xl border ${isReservationStayDue ? 'border-red-500/50 bg-red-500/10' : 'border-glass-border bg-hp-cream/60 dark:bg-white/5'} p-4 sm:p-5 flex justify-between items-center flex-wrap gap-3 shadow-2xs">
                        <div>
                            <span class="text-[0.72rem] font-bold ${isReservationStayDue ? 'text-red-700 dark:text-red-300' : 'text-hp-text-muted'} uppercase tracking-wider block">Expected Check-Out</span>
                            <div class="text-base sm:text-lg font-extrabold text-hp-text flex items-center gap-2.5 flex-wrap mt-1">
                                <span>${expectedCheckout.date}</span>
                                <span class="${isReservationStayDue ? 'bg-red-600' : 'bg-hp-green'} text-white text-xs font-bold px-3 py-1 rounded-full shadow-2xs">${expectedCheckout.session}</span>
                                ${expectedCheckout.time ? `<span class="text-sm font-semibold text-hp-text-muted">at ${expectedCheckout.time}</span>` : ''}
                            </div>
                        </div>
                        ${(String(reservation.status || '').toLowerCase().includes('checked') && reservation.checkout_at) ? `<div class="resv-checkout-countdown text-sm font-bold" data-checkout-at="${reservation.checkout_at}" data-checkout-state=""></div>` : ''}
                    </div>
                </div>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- PANE 2: GUESTS & COMPANIONS                             -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div id="resvPaneGuests" class="resv-tab-pane space-y-3 hidden">
                    <div class="flex items-center justify-between gap-2 border-b border-glass-border/40 pb-2 flex-wrap">
                        <span class="text-xs font-bold text-hp-text uppercase tracking-wider">Registered Guests & Companions</span>
                        <div class="flex items-center gap-2">
                            ${(String(reservation.status || '').toLowerCase().includes('checked') || String(reservation.status || '').toLowerCase().includes('active')) ? `
                                <button type="button" class="resv-companion-checkout-btn inline-flex items-center gap-1 rounded-xl bg-hp-green/10 border border-hp-green/40 px-3 py-1.5 text-xs font-bold text-hp-green hover:bg-hp-green hover:text-white transition-all cursor-pointer shadow-2xs" data-reservation-id="${reservation.id}">
                                    Group Checkout
                                </button>
                            ` : ''}
                        </div>
                    </div>

                    <div class="ci-guest-grid">
                        ${primaryGuest && primaryGuest.customer ? `
                            <div class="ci-guest-card">
                                <div class="ci-guest-icon ${primaryGuest.has_pool_access ? 'guest-avatar-glow--pool' : ''}">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                </div>
                                <div class="ci-guest-info">
                                    <div class="ci-guest-role flex items-center gap-1.5">
                                        <span>PRIMARY GUEST</span>
                                        <span class="inline-flex items-center rounded-md bg-emerald-500/20 text-emerald-800 dark:text-emerald-200 border border-emerald-500/40 px-1.5 py-0.2 text-[0.62rem] font-bold">Main Booker</span>
                                        ${primaryGuest.has_pool_access ? '<span style="font-size: 0.62rem; background: rgba(14,165,233,0.15); border: 1px solid rgba(14,165,233,0.3); color: #0369a1; padding: 1px 6px; border-radius: 8px; font-weight: 700;"><i class="bi bi-water me-1"></i>Pool Pass</span>' : '<span style="font-size: 0.62rem; background: rgba(100,116,139,0.15); border: 1px solid rgba(100,116,139,0.3); color: #475569; padding: 1px 6px; border-radius: 8px; font-weight: 700;">Standard</span>'}
                                    </div>
                                    <div class="ci-guest-name font-bold text-hp-text">${primaryGuest.customer.first_name} ${primaryGuest.customer.middle_name || ''} ${primaryGuest.customer.last_name}</div>
                                    <div class="ci-guest-meta">${primaryGuest.customer.age || 'N/A'} yrs &bull; ${primaryGuest.customer.gender || 'N/A'} &bull; ${primaryGuest.customer.is_foreigner ? 'Foreigner' : 'Filipino'}</div>
                                </div>
                            </div>
                        ` : '<div class="ci-guest-card"><div class="ci-guest-info"><div class="ci-guest-name">No main guest assigned</div></div></div>'}

                        ${(() => {
                const individualCompanions = activeCompanions.filter(c =>
                    c.customer &&
                    c.customer.first_name &&
                    !c.customer.first_name.toLowerCase().includes('companion') &&
                    !c.customer.first_name.toLowerCase().includes('reservation')
                );

                const bulkCompanions = activeCompanions.filter(c =>
                    !c.customer ||
                    !c.customer.first_name ||
                    c.customer.first_name.toLowerCase().includes('companion') ||
                    c.customer.first_name.toLowerCase().includes('reservation')
                );

                const bulkGroups = {};
                bulkCompanions.forEach(c => {
                    if (!c.customer) return;
                    const gender = c.customer.gender || 'Unknown';
                    const status = c.customer.is_foreigner ? 'Foreigner' : 'Filipino';
                    const age = c.customer.age || 'Unknown';
                    const key = `${gender}/${status}/${age}`;
                    if (!bulkGroups[key]) {
                        bulkGroups[key] = { gender, status, age, count: 0, poolCount: 0 };
                    }
                    bulkGroups[key].count++;
                    if (c.has_pool_access) bulkGroups[key].poolCount++;
                });

                const totalCompanions = bulkCompanions.length + individualCompanions.length;
                if (totalCompanions === 0) {
                    return `<div class="rounded-xl border border-glass-border bg-glass p-4 text-xs text-hp-text-muted italic text-center">No companion guests registered.</div>`;
                }

                let summaryLines = '';
                if (individualCompanions.length > 0) {
                    const singlePoolCount = individualCompanions.filter(c => Boolean(c.has_pool_access)).length;
                    summaryLines += `<div class="ci-guest-meta" style="color: #333;">Single / Named companions: <strong>${individualCompanions.length}</strong> (${singlePoolCount} with pool)</div>`;
                }
                if (bulkCompanions.length > 0) {
                    const bulkPoolCount = bulkCompanions.filter(c => Boolean(c.has_pool_access)).length;
                    const groupSummary = Object.values(bulkGroups)
                        .map(g => `${g.gender} · ${ageGroupLabel(g.age)} ×${g.count} (${g.poolCount} pool)`)
                        .join(' · ');
                    summaryLines += `<div class="ci-guest-meta" style="color: #333;">Bulk companion groups: <strong>${bulkCompanions.length}</strong> (${bulkPoolCount} with pool)${groupSummary ? ` <span style="color: #888; font-size: 0.78rem;">— ${groupSummary}</span>` : ''}</div>`;
                }

                return `
                                <div class="ci-guest-card" style="align-items: flex-start;">
                                    <div class="ci-guest-icon" style="margin-top: 0.2rem;">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                                    </div>
                                    <div class="ci-guest-info" style="width: 100%;">
                                        <div class="flex items-center justify-between gap-2 flex-wrap mb-1">
                                            <div class="ci-guest-role" style="margin-bottom: 0;">COMPANIONS (${totalCompanions})</div>
                                            ${activeCompanions.length > 0 ? `<button type="button" class="resv-companion-checkout-btn inline-flex items-center gap-1 rounded-lg bg-hp-green/10 border border-hp-green/40 px-2.5 py-1 text-xs font-bold text-hp-green hover:bg-hp-green hover:text-white transition-all cursor-pointer shadow-2xs" data-reservation-id="${reservation.id}">Group Checkout</button>` : ''}
                                        </div>
                                        ${summaryLines}
                                    </div>
                                </div>
                            `;
            })()}
                    </div>
                </div>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- PANE 3: RESERVED AMENITIES                              -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div id="resvPaneAmenities" class="resv-tab-pane space-y-3 hidden">
                    <div class="flex items-center justify-between gap-2 border-b border-glass-border/40 pb-2 flex-wrap">
                        <span class="text-xs font-bold text-hp-text uppercase tracking-wider flex items-center gap-1.5">
                            Reserved Amenities (${validAmenities.length})
                            ${hasAmenityDue ? '<span class="inline-block h-2 w-2 shrink-0 rounded-full bg-red-500 shadow-[0_0_6px_rgba(239,68,68,0.7)] animate-pulse" title="Amenity is time to checkout"></span>' : ''}
                        </span>
                        <div class="flex items-center gap-2">
                            ${(String(reservation.status || '').toLowerCase().includes('checked') || String(reservation.status || '').toLowerCase().includes('active')) ? `
                                <button type="button" class="resv-add-amenity-btn inline-flex items-center gap-1 rounded-xl bg-hp-green px-3 py-1.5 text-xs font-bold text-white hover:bg-hp-green-dark transition-colors cursor-pointer" data-reservation-id="${reservation.id}">
                                    + Add Amenity
                                </button>
                            ` : ''}
                        </div>
                    </div>

                    ${validAmenities.length > 0 ? `
                        <div class="resv-amenity-list">
                            ${validAmenities.map(a => {
                const amenityStatus = a.status || 'Active';
                const isCompleted = amenityStatus === 'Completed';
                const aStart = a.start_date ? formatDate(a.start_date) : '';
                const aEnd = a.end_date ? formatDate(a.end_date) : '';
                const aStartSlot = a.start_slot || startSlot;
                const aEndSlot = a.end_slot || endSlot;
                const aHasRange = a.start_date && a.end_date && a.start_date !== a.end_date;
                const aSched = aHasRange
                    ? ` (${aStart} [${aStartSlot}] to ${aEnd} [${aEndSlot}])`
                    : (aStartSlot ? ` (${aStartSlot})` : '');
                return `
                                    <div class="resv-amenity-item ${isCompleted ? 'resv-amenity-item--completed' : ''}">
                                        <div class="resv-amenity-item__info">
                                            <div class="resv-amenity-item__name">${a.amenity ? a.amenity.amenities_name : (a.amenity_name || a.amenity_id || 'Unknown amenity')}</div>
                                            <div class="resv-amenity-item__meta">${a.pricing_type || 'N/A'}${aSched} · ₱${parseFloat(a.price || a.price_at_booking || 0).toFixed(2)} x ${a.quantity || 1}</div>
                                            ${!isCompleted && (a.checkout_at || a.starts_at) ? `<div class="resv-amenity-countdown" data-starts-at="${a.starts_at || ''}" data-checkout-at="${a.checkout_at || ''}" data-checkout-state=""></div>` : ''}
                                        </div>
                                        <div class="resv-amenity-item__actions flex items-center gap-1.5">
                                            ${isCompleted
                        ? '<span class="resv-amenity-status resv-amenity-status--completed">Completed</span>'
                        : (
                            (isCheckedIn ? `<button type="button" class="resv-amenity-extend-btn rounded-lg border border-hp-green/40 bg-hp-green/10 px-2.5 py-1 text-xs font-bold text-hp-green hover:bg-hp-green hover:text-white transition-colors cursor-pointer" data-reservation-id="${reservation.id}" data-reservation-amenity-id="${a.id}">Extend</button>` : '') +
                            (isCheckedIn
                                ? `<button type="button" class="resv-amenity-checkout-btn" data-reservation-amenity-id="${a.id || ''}" data-reservation-id="${reservation.id}">Check Out</button>`
                                : '<span class="resv-amenity-status resv-amenity-status--active">Active</span>')
                        )
                    }
                                        </div>
                                    </div>
                                `;
            }).join('')}
                        </div>
                    ` : `
                        <div class="rounded-xl border border-glass-border bg-glass p-6 text-center text-xs text-hp-text-muted italic">
                            No amenities booked on this stay. Click "+ Add Amenity" to rent one mid-stay.
                        </div>
                    `}
                </div>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- PANE 4: BILLING & FEES                                  -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div id="resvPaneBilling" class="resv-tab-pane space-y-3 hidden">
                    <div class="ci-design-box" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div style="display:flex; gap: 2rem; flex-wrap: wrap;">
                            <div class="ci-col">
                                <span class="ci-label" style="text-transform: none;">Total Due:</span>
                                <div class="ci-value-lg">₱${parseFloat(reservation.total_amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                            </div>
                            <div class="ci-col ci-border-left">
                                <span class="ci-label" style="text-transform: none;">Paid to Date:</span>
                                <div class="ci-value-lg">₱${parseFloat(reservation.amount_paid || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                            </div>
                            <div class="ci-col ci-border-left">
                                <span class="ci-label" style="text-transform: none;">Balance Due:</span>
                                <div class="ci-value-lg">₱${parseFloat(reservation.remaining_balance || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                            </div>
                        </div>
                        <div>
                            <span class="ci-pill ${reservation.payment_status?.toLowerCase() === 'paid' ? 'ci-pill-green' : 'ci-pill-orange'}">
                                ${(reservation.payment_status || 'PENDING').toUpperCase()}
                            </span>
                        </div>
                    </div>

                    ${reservation.entrance_fee ? `
                        <div class="ci-design-box" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; padding: 0.85rem 1rem;">
                            <div style="display:flex; gap: 1.5rem; flex-wrap: wrap;">
                                <div class="ci-col">
                                    <span class="ci-label" style="text-transform: none;">Entrance Fee:</span>
                                    <div class="ci-value" style="font-weight: 600;">₱${(parseFloat(reservation.entrance_fee.total_amount || 0) - parseFloat(reservation.entrance_fee.pool_fee || 0)).toFixed(2)} <span style="font-weight: 400; font-size: 0.78rem;">(${reservation.entrance_fee.adult_count || 0} adult${(reservation.entrance_fee.adult_count || 0) === 1 ? '' : 's'} · ${reservation.entrance_fee.child_count || 0} child${(reservation.entrance_fee.child_count || 0) === 1 ? '' : 'ren'})</span></div>
                                </div>
                                <div class="ci-col ci-border-left">
                                    <span class="ci-label" style="text-transform: none;">Pool Fee:</span>
                                    <div class="ci-value" style="font-weight: 600;">₱${parseFloat(reservation.entrance_fee.pool_fee || 0).toFixed(2)}</div>
                                </div>
                                <div class="ci-col ci-border-left">
                                    <span class="ci-label" style="text-transform: none;">Entrance + Pool:</span>
                                    <div class="ci-value" style="font-weight: 700;">₱${parseFloat(reservation.entrance_fee.total_amount || 0).toFixed(2)}</div>
                                </div>
                            </div>
                        </div>
                    ` : ''}

                    ${(() => {
                const periods = [];
                if (validAmenities.length > 0) {
                    validAmenities.forEach(a => {
                        const t = String(a.pricing_type || 'N/A');
                        if (!periods.includes(t)) periods.push(t);
                    });
                } else if (reservation.entrance_fee && reservation.entrance_fee.pricing_type) {
                    periods.push(reservation.entrance_fee.pricing_type);
                }
                if (!periods.length) return '';
                return `
                        <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; background: rgba(194,146,29,0.10); border: 1px solid rgba(194,146,29,0.35); border-radius: 0.6rem; padding: 0.55rem 1rem;">
                            <span style="font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em; color: #a1760f;">Time Period:</span>
                            ${periods.map(t => `<span style="background: var(--hp-gold); color: #fff; font-weight: 700; font-size: 0.78rem; padding: 3px 10px; border-radius: 999px;">${t}</span>`).join('')}
                            ${differentTime ? '<span style="font-size: 0.75rem; font-weight: 600; color: #a1760f;">Mixed time periods</span>' : ''}
                        </div>
                        `;
            })()}
                </div>
            </div>
        `;

        // Update modal status badge
        const statusBadge = document.getElementById('reservationModalStatus');
        if (statusBadge) {
            statusBadge.textContent = reservation.status || 'Active';
        }

        // Only checked-in reservations can accept new companions mid-stay.
        if (reservationAddCompanionBtn) {
            reservationAddCompanionBtn.classList.toggle('hidden', !isCheckedIn);
        }
        if (reservationCompanionCheckoutHeaderBtn) {
            const hasActiveGuests = guestsList.some(g => !g.checked_out_at);
            reservationCompanionCheckoutHeaderBtn.classList.toggle('hidden', !isCheckedIn || !hasActiveGuests);
            reservationCompanionCheckoutHeaderBtn.setAttribute('data-reservation-id', reservation.id);
        }

        reservationModalBody.innerHTML = html;

        // Render and Bind Sticky Action Buttons in Modal Footer (Always visible wherever user scrolls)
        const stickyButtonsContainer = document.getElementById('reservationModalStickyButtons');
        if (stickyButtonsContainer) {
            if (isCheckedIn) {
                stickyButtonsContainer.innerHTML = `
                    <button type="button" class="resv-quick-add-companion-btn inline-flex items-center gap-1.5 cursor-pointer rounded-xl border-2 border-emerald-600 bg-emerald-600/10 hover:bg-emerald-600 hover:text-white px-3.5 py-1.5 text-xs font-extrabold text-emerald-900 dark:text-emerald-200 transition-all shadow-xs" data-reservation-id="${reservation.id}">
                        <svg class="w-3.5 h-3.5 text-emerald-700 dark:text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        <span>Add Companion</span>
                    </button>
                    ${hasActiveGuests ? `
                        <button type="button" class="resv-quick-group-checkout-btn inline-flex items-center gap-1.5 cursor-pointer rounded-xl bg-gradient-to-r from-teal-700 to-emerald-700 hover:from-teal-800 hover:to-emerald-800 px-3.5 py-1.5 text-xs font-extrabold text-white transition-all shadow-md hover:shadow-lg" data-reservation-id="${reservation.id}">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                            <span>Group Checkout</span>
                        </button>
                    ` : ''}
                    <button type="button" class="resv-quick-checkout-all-btn inline-flex items-center gap-1.5 cursor-pointer rounded-xl border-0 ${isReservationStayDue ? 'bg-red-600 hover:bg-red-700 ring-2 ring-red-500/30' : 'bg-hp-green hover:bg-hp-green-dark'} px-4 py-1.5 text-xs font-extrabold text-white transition-all shadow-md hover:shadow-lg" data-reservation-id="${reservation.id}">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                        <span>${isReservationStayDue ? 'Check Out All (Due)' : 'Check Out All'}</span>
                    </button>
                `;

                // Bind Sticky Action Buttons
                const quickAddCompanionBtn = stickyButtonsContainer.querySelector('.resv-quick-add-companion-btn');
                const quickGroupCheckoutBtn = stickyButtonsContainer.querySelector('.resv-quick-group-checkout-btn');
                const quickCheckoutAllBtn = stickyButtonsContainer.querySelector('.resv-quick-checkout-all-btn');

                quickAddCompanionBtn?.addEventListener('click', () => {
                    openResAddCompanionModal();
                });

                quickGroupCheckoutBtn?.addEventListener('click', () => {
                    openCompanionCheckoutModal(reservation.id);
                });

                quickCheckoutAllBtn?.addEventListener('click', () => {
                    pendingCheckOutReservationId = reservation.id;
                    openCheckOutConfirmModal();
                });
            } else {
                stickyButtonsContainer.innerHTML = '';
            }
        }

        // Bind Tab Switching Listeners inside modal
        const tabBtns = reservationModalBody.querySelectorAll('.resv-tab-btn');
        const tabPanes = reservationModalBody.querySelectorAll('.resv-tab-pane');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.dataset.targetPane;
                tabBtns.forEach(b => {
                    const isCurrent = (b === btn);
                    b.classList.toggle('active', isCurrent);
                    b.classList.toggle('bg-hp-green', isCurrent);
                    b.classList.toggle('text-white', isCurrent);
                    b.classList.toggle('shadow-sm', isCurrent);
                    b.classList.toggle('border-transparent', isCurrent);
                    b.classList.toggle('bg-white/70', !isCurrent);
                    b.classList.toggle('text-hp-text-muted', !isCurrent);
                    b.classList.toggle('border-glass-border/60', !isCurrent);
                });
                tabPanes.forEach(pane => {
                    pane.classList.toggle('hidden', pane.id !== targetId);
                });
            });
        });

        refreshCheckoutCountdowns();
        reservationModal.classList.add('is-open');
        reservationModal.setAttribute('aria-hidden', 'false');
    };

    const closeReservationModal = () => {
        currentReservationId = null;
        reservationModal.classList.remove('is-open');
        reservationModal.setAttribute('aria-hidden', 'true');
    };

    const openCheckOutConfirmModal = () => {
        if (checkOutConfirmModal) {
            checkOutConfirmModal.classList.add('is-open');
            checkOutConfirmModal.setAttribute('aria-hidden', 'false');
        }
    };

    const closeCheckOutConfirmModal = () => {
        if (checkOutConfirmModal) {
            checkOutConfirmModal.classList.remove('is-open');
            checkOutConfirmModal.setAttribute('aria-hidden', 'true');
        }
    };

    // Reservation row click handlers (both delegated and direct)
    if (reservationTableBody) {
        reservationTableBody.addEventListener('click', (e) => {
            if (e.target.closest('.btn-expand-row') || e.target.closest('button')) return;
            const row = e.target.closest('.reservation-row');
            if (row && row.dataset.reservationId) {
                openReservationModal(row.dataset.reservationId);
            }
        });
    }

    const reservationRows = reservationTableBody?.querySelectorAll('.reservation-row') ?? [];
    reservationRows.forEach(row => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('.btn-expand-row') || e.target.closest('button')) return;
            const reservationId = row.dataset.reservationId;
            if (reservationId) openReservationModal(reservationId);
        });
    });

    // Modal close handlers
    reservationCloseButtons.forEach(button => {
        button.addEventListener('click', closeReservationModal);
    });

    checkOutConfirmCloseButtons.forEach(button => {
        button.addEventListener('click', closeCheckOutConfirmModal);
    });

    // Companion checkout button in reservation modal header
    reservationCompanionCheckoutHeaderBtn?.addEventListener('click', () => {
        if (!currentReservationId) return;
        openCompanionCheckoutModal(currentReservationId);
    });

    // Reservation checkout - open confirmation modal
    reservationCheckOutBtn?.addEventListener('click', () => {
        if (!currentReservationId) return;
        pendingCheckOutReservationId = currentReservationId;
        openCheckOutConfirmModal();
    });

    // Confirm checkout - actually perform the action
    confirmCheckOutBtn?.addEventListener('click', async () => {
        if (!pendingCheckOutReservationId) return;

        const submitButton = confirmCheckOutBtn;
        submitButton.disabled = true;
        submitButton.textContent = 'Checking out...';

        try {
            const response = await fetch(`/staff/reservations/${pendingCheckOutReservationId}/check-out`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({}),
            });

            const data = await response.json();

            if (response.ok && data.success) {
                const resId = pendingCheckOutReservationId;
                closeCheckOutConfirmModal();
                closeReservationModal();

                if (window.staffReservationData && window.staffReservationData[resId]) {
                    window.staffReservationData[resId].status = 'Checked Out';
                }

                // Remove or update the reservation table row with a smooth exit
                const resvRow = document.querySelector(`tr.reservation-row[data-reservation-id="${resId}"]`);
                if (resvRow) {
                    resvRow.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                    resvRow.style.opacity = '0';
                    resvRow.style.transform = 'scaleY(0)';
                    setTimeout(() => {
                        resvRow.remove();
                        const remainingRows = document.querySelectorAll('tr.reservation-row').length;
                        const countEl = document.getElementById('resvResultsCount');
                        if (countEl) countEl.textContent = `Showing ${remainingRows} reservation${remainingRows === 1 ? '' : 's'}`;
                        refreshCheckoutCountdowns();
                    }, 250);
                }

                // Remove associated guest rows in the active guest table
                document.querySelectorAll(`tr.guest-row[data-reservation-id="${resId}"]`).forEach(gRow => {
                    gRow.remove();
                });
                const remainingGuestRows = document.querySelectorAll('tr.guest-row').length;
                const guestCountEl = document.getElementById('guestResultsCount');
                if (guestCountEl) guestCountEl.textContent = `Showing ${remainingGuestRows} active guest${remainingGuestRows === 1 ? '' : 's'}`;

                refreshCheckoutCountdowns();
                showToast(`Reservation #${resId} checked out successfully.`);
            } else {
                throw new Error(data.message || 'Failed to check out reservation');
            }
        } catch (error) {
            console.error('Check out error:', error);
            alert('Error checking out reservation: ' + error.message);
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Yes, Check Out';
        }
    });

    // Per-amenity check out handler
    const handleAmenityCheckout = async (btn) => {
        if (!btn) return;
        const reservationAmenityId = btn.dataset.reservationAmenityId;
        const reservationId = btn.dataset.reservationId;
        if (!reservationAmenityId || !reservationId) return;

        if (!confirm('Check out this amenity? The reservation stays active until all amenities are checked out.')) return;

        btn.disabled = true;
        btn.textContent = 'Checking out...';

        try {
            const response = await fetch(`/staff/reservations/${reservationId}/amenities/${reservationAmenityId}/check-out`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(payload.message || 'Unable to check out this amenity.');
            }

            // Mark completed in local data and re-render the modal
            const res = (window.staffReservationData && window.staffReservationData[reservationId]) || reservationData[reservationId];
            const amenity = res?.reservation_amenities?.find(a => String(a.id) === String(reservationAmenityId));
            if (amenity) amenity.status = 'Completed';

            // Synchronize background table row data attributes and live state in place
            const tableRow = document.querySelector(`tr.reservation-row[data-reservation-id="${reservationId}"]`);
            if (tableRow && res) {
                const activeAmenityCheckoutTimes = (res.reservation_amenities || [])
                    .filter(a => (a.status || 'Active') !== 'Completed' && a.checkout_at)
                    .map(a => a.checkout_at);
                tableRow.setAttribute('data-amenity-checkout-times', activeAmenityCheckoutTimes.join(','));
            }

            openReservationModal(reservationId);
            refreshCheckoutCountdowns();
            showToast('Amenity checked out successfully.');
        } catch (error) {
            window.alert(error.message || 'Unable to check out this amenity.');
            btn.disabled = false;
            btn.textContent = 'Check Out';
        }
    };

    // ── Continuous timeline slot builder (JS) ──────────────────────────────
    // Formats a Date object as a LOCAL YYYY-MM-DD key (toISOString() would shift
    // the date backwards for timezones ahead of UTC, e.g. PHT).
    const toDateKey = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

    const buildContinuousTimeline = (startDate, endDate, startSlot = 'Daytime', endSlot = 'Daytime') => {
        if (!startDate) return [];
        endDate = endDate || startDate;
        const cleanStartSlot = String(startSlot).includes('Night') ? 'Nighttime' : 'Daytime';
        const cleanEndSlot = String(endSlot).includes('Night') ? 'Nighttime' : 'Daytime';

        const s = new Date(startDate + 'T00:00:00');
        const e = new Date(endDate + 'T00:00:00');
        if (s > e) return [];

        const daysDiff = Math.round((e - s) / (1000 * 60 * 60 * 24));
        const pairs = [];

        if (daysDiff === 0) {
            if (cleanStartSlot === 'Daytime' && cleanEndSlot === 'Daytime') {
                pairs.push([startDate, 'Daytime']);
            } else if (cleanStartSlot === 'Nighttime' && cleanEndSlot === 'Nighttime') {
                pairs.push([startDate, 'Nighttime']);
            } else if (cleanStartSlot === 'Daytime' && cleanEndSlot === 'Nighttime') {
                pairs.push([startDate, 'Daytime']);
                pairs.push([startDate, 'Nighttime']);
            } else {
                const nextDay = new Date(s);
                nextDay.setDate(nextDay.getDate() + 1);
                const nextDayStr = toDateKey(nextDay);
                pairs.push([startDate, 'Nighttime']);
                pairs.push([nextDayStr, 'Daytime']);
            }
            return pairs;
        }

        for (let i = 0; i <= daysDiff; i++) {
            const curr = new Date(s);
            curr.setDate(curr.getDate() + i);
            const currStr = toDateKey(curr);

            if (i === 0) {
                if (cleanStartSlot === 'Daytime') {
                    pairs.push([currStr, 'Daytime']);
                    pairs.push([currStr, 'Nighttime']);
                } else {
                    pairs.push([currStr, 'Nighttime']);
                }
            } else if (i === daysDiff) {
                if (cleanEndSlot === 'Daytime') {
                    pairs.push([currStr, 'Daytime']);
                } else {
                    pairs.push([currStr, 'Daytime']);
                    pairs.push([currStr, 'Nighttime']);
                }
            } else {
                pairs.push([currStr, 'Daytime']);
                pairs.push([currStr, 'Nighttime']);
            }
        }
        return pairs;
    };

    // ── Adjust / Extend Stay Modal Handlers (Interactive 5-Year Calendar) ───
    const extendStayModal = document.getElementById('extendStayModal');
    const extendStayForm = document.getElementById('extendStayForm');
    const extendStayResId = document.getElementById('extendStayResId');
    const extendStayCurrentSummary = document.getElementById('extendStayCurrentSummary');
    const extendStayBoundaryHelp = document.getElementById('extendStayBoundaryHelp');
    const extendStayNewEndDate = document.getElementById('extendStayNewEndDate');
    const extendStayNewEndSlot = document.getElementById('extendStayNewEndSlot');
    const extendStayCalPrev = document.getElementById('extendStayCalPrev');
    const extendStayCalNext = document.getElementById('extendStayCalNext');
    const extendStayCalTitle = document.getElementById('extendStayCalTitle');
    const extendStayCalYear = document.getElementById('extendStayCalYear');
    const extendStayCalGrid = document.getElementById('extendStayCalGrid');
    const extendStayCalStepHelp = document.getElementById('extendStayCalStepHelp');
    const extendStayWarning = document.getElementById('extendStayWarning');
    const submitExtendStayBtn = document.getElementById('submitExtendStayBtn');
    const extendStayCloseButtons = document.querySelectorAll('[data-close-extend-stay-modal="true"]');

    const extendStayCalState = {
        resId: null,
        viewYear: new Date().getFullYear(),
        viewMonth: new Date().getMonth(),
        selectedEndDate: null,
        selectedEndSlot: 'Daytime',
        minPermissibleDate: null,
        minPermissibleSlot: 'Daytime',
        latestAmenityDate: null,
        latestAmenitySlot: 'Daytime',
        latestAmenityName: null,
    };

    const syncExtendStaySessionPills = () => {
        document.querySelectorAll('#extendStayEndSlotGroup [data-slot-val]').forEach(btn => {
            const val = btn.dataset.slotVal;
            btn.dataset.active = (val === extendStayCalState.selectedEndSlot) ? 'true' : 'false';
        });
    };

    document.querySelectorAll('#extendStayEndSlotGroup [data-slot-val]').forEach(btn => {
        btn.addEventListener('click', () => {
            const val = btn.dataset.slotVal;
            const res = reservationData[extendStayCalState.resId];
            if (!res) return;

            // Cannot choose Daytime if on minimum permissible date and amenity requires Nighttime
            if (extendStayCalState.selectedEndDate === extendStayCalState.minPermissibleDate && extendStayCalState.minPermissibleSlot === 'Nighttime' && val === 'Daytime') {
                if (extendStayWarning) {
                    extendStayWarning.textContent = `Check-out session on ${formatDate(extendStayCalState.minPermissibleDate)} cannot be earlier than ${extendStayCalState.minPermissibleSlot} due to active ${extendStayCalState.latestAmenityName || 'amenity'}.`;
                    extendStayWarning.classList.remove('hidden');
                }
                return;
            }

            if (extendStayWarning) extendStayWarning.classList.add('hidden');
            extendStayCalState.selectedEndSlot = val;
            if (extendStayNewEndSlot) extendStayNewEndSlot.value = val;
            syncExtendStaySessionPills();
            renderExtendStayCalendarMonth();
        });
    });

    const renderExtendStayCalendarMonth = () => {
        if (!extendStayCalGrid) return;
        const res = reservationData[extendStayCalState.resId];
        if (!res) return;

        const year = extendStayCalState.viewYear;
        const month = extendStayCalState.viewMonth;

        if (extendStayCalTitle) extendStayCalTitle.textContent = `${monthNames[month]} ${year}`;
        if (extendStayCalYear) extendStayCalYear.value = year;

        extendStayCalGrid.innerHTML = '';

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Empty padding cells
        for (let i = 0; i < firstDay; i++) {
            const empty = document.createElement('div');
            empty.className = 'edit-calendar__day edit-calendar__day--empty opacity-0 pointer-events-none';
            extendStayCalGrid.appendChild(empty);
        }

        const minAllowedDate = extendStayCalState.minPermissibleDate || res.reservation_date || todayStr;
        const startDate = res.reservation_date || todayStr;

        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'edit-calendar__day flex flex-col items-center justify-center rounded-lg p-1.5 text-xs font-semibold transition-all duration-150 relative cursor-pointer border border-transparent';
            btn.textContent = d;
            btn.dataset.date = dateStr;

            const isBeforeMin = dateStr < minAllowedDate;
            const isSelected = dateStr === extendStayCalState.selectedEndDate;
            const inRange = dateStr >= startDate && dateStr <= extendStayCalState.selectedEndDate;

            if (isBeforeMin) {
                btn.classList.add('is-disabled', 'opacity-30', 'cursor-not-allowed');
                btn.disabled = true;
                if (extendStayCalState.latestAmenityDate) {
                    btn.title = `Cannot step back before ${formatDate(extendStayCalState.latestAmenityDate)} due to active amenity`;
                }
            } else {
                btn.classList.add('is-available', 'hover:border-hp-green', 'hover:bg-hp-green/10');
            }

            if (inRange && !isBeforeMin) {
                btn.classList.add('is-selected', 'bg-hp-green', 'text-white', 'font-bold');
                btn.classList.remove('hover:bg-hp-green/10');
            }

            if (dateStr === todayStr) {
                const dot = document.createElement('span');
                dot.className = 'absolute bottom-1 h-1 w-1 rounded-full bg-emerald-400';
                btn.appendChild(dot);
            }

            btn.addEventListener('click', () => {
                if (btn.disabled) return;
                extendStayCalState.selectedEndDate = dateStr;
                if (extendStayNewEndDate) extendStayNewEndDate.value = dateStr;

                if (dateStr === extendStayCalState.minPermissibleDate && extendStayCalState.minPermissibleSlot === 'Nighttime') {
                    extendStayCalState.selectedEndSlot = 'Nighttime';
                    if (extendStayNewEndSlot) extendStayNewEndSlot.value = 'Nighttime';
                }

                if (extendStayWarning) extendStayWarning.classList.add('hidden');
                syncExtendStaySessionPills();
                renderExtendStayCalendarMonth();
            });

            extendStayCalGrid.appendChild(btn);
        }

        if (extendStayCalStepHelp) {
            extendStayCalStepHelp.textContent = `Selected: ${formatDate(extendStayCalState.selectedEndDate)} (${extendStayCalState.selectedEndSlot})`;
        }
    };

    const openExtendStayModal = (reservationId) => {
        const res = (window.staffReservationData && window.staffReservationData[reservationId]) || reservationData[reservationId];
        if (!res) return;

        extendStayCalState.resId = reservationId;
        const resIdEl = document.getElementById('extendStayResId');
        if (resIdEl) resIdEl.textContent = res.id;

        const isMultiDay = res.end_date && res.end_date !== res.reservation_date;
        const startSlot = res.start_slot || 'Daytime';
        const endSlot = res.end_slot || startSlot;
        const summaryEl = document.getElementById('extendStayCurrentSummary');
        if (summaryEl) {
            summaryEl.textContent = isMultiDay
                ? `${formatDate(res.reservation_date)} (${startSlot}) – ${formatDate(res.end_date)} (${endSlot}) · ${res.total_days || 2} Day(s)`
                : `${formatDate(res.reservation_date)} (${startSlot}) · 1 Day`;
        }

        // Calculate minimum allowable check-out date & slot from booked amenities
        let latestAmenityDate = null;
        let latestAmenitySlot = 'Daytime';
        let latestAmenityName = null;

        if (res.reservation_amenities && res.reservation_amenities.length > 0) {
            res.reservation_amenities.forEach(ra => {
                if (ra.status === 'Completed') return;
                const amEnd = ra.end_date || ra.start_date || res.reservation_date;
                const amEndSlot = ra.end_slot || 'Daytime';

                if (!latestAmenityDate || amEnd > latestAmenityDate || (amEnd === latestAmenityDate && amEndSlot === 'Nighttime')) {
                    latestAmenityDate = amEnd;
                    latestAmenitySlot = amEndSlot;
                    latestAmenityName = ra.amenity ? ra.amenity.amenities_name : (ra.amenity_name || 'Amenity');
                }
            });
        }

        extendStayCalState.latestAmenityDate = latestAmenityDate;
        extendStayCalState.latestAmenitySlot = latestAmenitySlot;
        extendStayCalState.latestAmenityName = latestAmenityName;

        const resStart = res.reservation_date || todayStr;
        const minPermissible = latestAmenityDate ? (latestAmenityDate > resStart ? latestAmenityDate : resStart) : resStart;
        extendStayCalState.minPermissibleDate = minPermissible;
        extendStayCalState.minPermissibleSlot = (latestAmenityDate && latestAmenityDate === minPermissible) ? latestAmenitySlot : startSlot;

        extendStayCalState.selectedEndDate = res.end_date || res.reservation_date || todayStr;
        extendStayCalState.selectedEndSlot = res.end_slot || startSlot;

        const newEndDateInput = document.getElementById('extendStayNewEndDate');
        const newEndSlotInput = document.getElementById('extendStayNewEndSlot');
        if (newEndDateInput) newEndDateInput.value = extendStayCalState.selectedEndDate;
        if (newEndSlotInput) newEndSlotInput.value = extendStayCalState.selectedEndSlot;

        const helpEl = document.getElementById('extendStayBoundaryHelp');
        if (helpEl) {
            if (latestAmenityDate) {
                helpEl.textContent = `Stay can step back down to ${formatDate(latestAmenityDate)} (${latestAmenitySlot}) due to active ${latestAmenityName || 'amenity'} booking.`;
            } else {
                helpEl.textContent = `Stay can step back down to check-in date (${formatDate(resStart)} [${startSlot}]).`;
            }
        }

        // 5-Year population
        const currentYear = new Date().getFullYear();
        const yearSelect = document.getElementById('extendStayCalYear');
        if (yearSelect) {
            yearSelect.innerHTML = '';
            for (let y = currentYear; y <= currentYear + 5; y++) {
                const opt = document.createElement('option');
                opt.value = y;
                opt.textContent = y;
                yearSelect.appendChild(opt);
            }
        }

        const initialDateObj = new Date(extendStayCalState.selectedEndDate);
        extendStayCalState.viewYear = !isNaN(initialDateObj.getFullYear()) ? initialDateObj.getFullYear() : currentYear;
        extendStayCalState.viewMonth = !isNaN(initialDateObj.getMonth()) ? initialDateObj.getMonth() : new Date().getMonth();

        const warningEl = document.getElementById('extendStayWarning');
        if (warningEl) warningEl.classList.add('hidden');
        syncExtendStaySessionPills();
        renderExtendStayCalendarMonth();

        const modal = document.getElementById('extendStayModal');
        if (modal) {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        }
    };

    const closeExtendStayModal = () => {
        if (extendStayModal) {
            extendStayModal.classList.remove('is-open');
            extendStayModal.setAttribute('aria-hidden', 'true');
        }
    };

    extendStayCloseButtons.forEach(btn => btn.addEventListener('click', closeExtendStayModal));

    extendStayCalPrev?.addEventListener('click', () => {
        extendStayCalState.viewMonth--;
        if (extendStayCalState.viewMonth < 0) {
            extendStayCalState.viewMonth = 11;
            extendStayCalState.viewYear--;
        }
        renderExtendStayCalendarMonth();
    });

    extendStayCalNext?.addEventListener('click', () => {
        extendStayCalState.viewMonth++;
        if (extendStayCalState.viewMonth > 11) {
            extendStayCalState.viewMonth = 0;
            extendStayCalState.viewYear++;
        }
        renderExtendStayCalendarMonth();
    });

    extendStayCalYear?.addEventListener('change', (e) => {
        extendStayCalState.viewYear = parseInt(e.target.value, 10);
        renderExtendStayCalendarMonth();
    });

    extendStayForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const resId = extendStayCalState.resId;
        if (!resId) return;

        const newEndDate = extendStayCalState.selectedEndDate;
        const newEndSlot = extendStayCalState.selectedEndSlot;
        if (!newEndDate || !newEndSlot) return;

        if (submitExtendStayBtn) {
            submitExtendStayBtn.disabled = true;
            submitExtendStayBtn.textContent = 'Saving...';
        }

        try {
            const response = await fetch(`/staff/reservations/${resId}/extend-stay`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    new_end_date: newEndDate,
                    new_end_slot: newEndSlot,
                }),
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Failed to adjust stay schedule.');
            }

            if (reservationData[resId]) {
                reservationData[resId].end_date = data.end_date;
                reservationData[resId].end_slot = data.end_slot;
                reservationData[resId].total_days = data.total_days;
                if (data.checkout_at) {
                    reservationData[resId].checkout_at = data.checkout_at;
                }
            }

            if (data.checkout_at) {
                document.querySelectorAll(`[data-checkout-at][data-reservation-id="${resId}"]`).forEach(el => {
                    el.setAttribute('data-checkout-at', data.checkout_at);
                });
            }

            closeExtendStayModal();
            openReservationModal(resId);
            refreshCheckoutCountdowns();
            showToast(data.message || 'Stay schedule updated successfully.');
        } catch (error) {
            if (extendStayWarning) {
                extendStayWarning.textContent = error.message || 'Failed to adjust stay schedule.';
                extendStayWarning.classList.remove('hidden');
            } else {
                alert(error.message || 'Failed to adjust stay schedule.');
            }
        } finally {
            if (submitExtendStayBtn) {
                submitExtendStayBtn.disabled = false;
                submitExtendStayBtn.textContent = 'Save Stay Schedule';
            }
        }
    });

    // ── Extend Amenity Modal Handlers (5-Year Calendar with Booked Slots Disabled) ───
    const extendAmenityModal = document.getElementById('extendAmenityModal');
    const extendAmenityForm = document.getElementById('extendAmenityForm');
    const extendAmenityResId = document.getElementById('extendAmenityResId');
    const extendAmenityRaId = document.getElementById('extendAmenityRaId');
    const extendAmenityName = document.getElementById('extendAmenityName');
    const extendAmenityCurrentDuration = document.getElementById('extendAmenityCurrentDuration');
    const extendAmenityStayLimit = document.getElementById('extendAmenityStayLimit');
    const extendAmenityNewEndDate = document.getElementById('extendAmenityNewEndDate');
    const extendAmenityNewEndSlot = document.getElementById('extendAmenityNewEndSlot');
    const extendAmenityCalPrev = document.getElementById('extendAmenityCalPrev');
    const extendAmenityCalNext = document.getElementById('extendAmenityCalNext');
    const extendAmenityCalTitle = document.getElementById('extendAmenityCalTitle');
    const extendAmenityCalYear = document.getElementById('extendAmenityCalYear');
    const extendAmenityCalGrid = document.getElementById('extendAmenityCalGrid');
    const extendAmenityCalStepHelp = document.getElementById('extendAmenityCalStepHelp');
    const extendAmenityAddedSessionsText = document.getElementById('extendAmenityAddedSessionsText');
    const extendAmenityAddedCostText = document.getElementById('extendAmenityAddedCostText');
    const extendAmenityWarning = document.getElementById('extendAmenityWarning');
    const submitExtendAmenityBtn = document.getElementById('submitExtendAmenityBtn');
    const extendAmenityCloseButtons = document.querySelectorAll('[data-close-extend-amenity-modal="true"]');

    let currentPendingExtension = null;

    const extendAmenityCalState = {
        resId: null,
        raId: null,
        amenityId: null,
        viewYear: new Date().getFullYear(),
        viewMonth: new Date().getMonth(),
        selectedEndDate: null,
        selectedEndSlot: 'Daytime',
        amStartDate: null,
        amStartSlot: null,
        amCurrentEndDate: null,
        amCurrentEndSlot: null,
        masterEndDate: null,
        masterEndSlot: null,
        availabilityCache: {},
        cachedMonths: {},
    };

    const syncExtendAmenitySessionPills = () => {
        document.querySelectorAll('#extendAmenityEndSlotGroup [data-slot-val]').forEach(btn => {
            const val = btn.dataset.slotVal;
            btn.dataset.active = (val === extendAmenityCalState.selectedEndSlot) ? 'true' : 'false';
        });
    };

    document.querySelectorAll('#extendAmenityEndSlotGroup [data-slot-val]').forEach(btn => {
        btn.addEventListener('click', () => {
            const val = btn.dataset.slotVal;
            extendAmenityCalState.selectedEndSlot = val;
            if (extendAmenityNewEndSlot) extendAmenityNewEndSlot.value = val;
            syncExtendAmenitySessionPills();
            recalcExtendAmenityPrice();
            renderExtendAmenityCalendarMonth();
        });
    });

    const recalcExtendAmenityPrice = () => {
        const resId = extendAmenityCalState.resId;
        const raId = extendAmenityCalState.raId;
        const res = reservationData[resId];
        if (!res) return;

        const ra = res.reservation_amenities?.find(a => String(a.id) === String(raId));
        if (!ra) return;

        const amenityObj = window.ALL_AMENITIES?.find(a => String(a.id) === String(ra.amenity_id || ra.amenity?.id));

        const masterStartDate = res.reservation_date || window.SERVER_TODAY;
        const masterEndDate = res.end_date || masterStartDate;
        const masterStartSlot = res.start_slot || 'Daytime';
        const masterEndSlot = res.end_slot || 'Daytime';

        const amStartDate = ra.start_date || masterStartDate;
        const amStartSlot = ra.start_slot || masterStartSlot;
        const amCurrentEndDate = ra.end_date || amStartDate;
        const amCurrentEndSlot = ra.end_slot || amStartSlot;

        const newEndDate = extendAmenityCalState.selectedEndDate || amCurrentEndDate;
        const newEndSlot = extendAmenityCalState.selectedEndSlot || 'Daytime';

        const masterTimeline = buildContinuousTimeline(masterStartDate, masterEndDate, masterStartSlot, masterEndSlot);
        const masterKeyMap = {};
        masterTimeline.forEach(([d, s]) => { masterKeyMap[`${d}_${s}`] = true; });

        const oldTimeline = buildContinuousTimeline(amStartDate, amCurrentEndDate, amStartSlot, amCurrentEndSlot);
        const newTimeline = buildContinuousTimeline(amStartDate, newEndDate, amStartSlot, newEndSlot);

        if (extendAmenityWarning) {
            extendAmenityWarning.classList.add('hidden');
            extendAmenityWarning.textContent = '';
        }

        if (newTimeline.length <= oldTimeline.length) {
            if (extendAmenityWarning) {
                extendAmenityWarning.textContent = 'Please choose an end date and session later than current duration.';
                extendAmenityWarning.classList.remove('hidden');
            }
            if (extendAmenityAddedSessionsText) extendAmenityAddedSessionsText.textContent = '0 sessions';
            if (extendAmenityAddedCostText) extendAmenityAddedCostText.textContent = '₱0.00';
            if (submitExtendAmenityBtn) submitExtendAmenityBtn.disabled = true;
            return;
        }

        let exceeds = false;
        for (const [d, s] of newTimeline) {
            if (!masterKeyMap[`${d}_${s}`]) {
                exceeds = true;
                break;
            }
        }

        if (exceeds) {
            if (extendAmenityWarning) {
                extendAmenityWarning.textContent = `Extended duration exceeds the reservation's overall check-out schedule (${formatDate(masterEndDate)} [${masterEndSlot}]). Extend the overall stay first.`;
                extendAmenityWarning.classList.remove('hidden');
            }
            if (submitExtendAmenityBtn) submitExtendAmenityBtn.disabled = true;
            return;
        }

        if (submitExtendAmenityBtn) submitExtendAmenityBtn.disabled = false;

        const addedSlots = newTimeline.slice(oldTimeline.length);
        let extraDay = 0;
        let extraNight = 0;
        addedSlots.forEach(([d, s]) => {
            if (s === 'Daytime') extraDay++;
            else extraNight++;
        });

        const hasAircon = String(ra.pricing_type || '').includes('Aircon');
        const dayPrice = hasAircon && amenityObj?.daytime_aircon_price ? parseFloat(amenityObj.daytime_aircon_price) : parseFloat(amenityObj?.daytime_price || ra.price_at_booking || 0);
        const nightPrice = hasAircon && amenityObj?.nighttime_aircon_price ? parseFloat(amenityObj.nighttime_aircon_price) : parseFloat(amenityObj?.nighttime_price || ra.price_at_booking || 0);
        const qty = parseInt(ra.quantity || 1, 10);

        const addedCost = ((extraDay * dayPrice) + (extraNight * nightPrice)) * qty;

        if (extendAmenityAddedSessionsText) {
            extendAmenityAddedSessionsText.textContent = `${addedSlots.length} session${addedSlots.length === 1 ? '' : 's'} (${extraDay} Day, ${extraNight} Night)`;
        }
        if (extendAmenityAddedCostText) {
            extendAmenityAddedCostText.textContent = `₱${addedCost.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }
    };

    const showCalendarLoading = (gridEl) => {
        if (!gridEl) return;
        gridEl.innerHTML = `
            <div class="col-span-7 flex flex-col items-center justify-center py-10 gap-3">
                <div class="relative flex items-center justify-center">
                    <div class="w-8 h-8 rounded-full border-2 border-hp-green/20 border-t-hp-green animate-spin"></div>
                    <div class="absolute w-3 h-3 rounded-full bg-hp-green/30 animate-ping"></div>
                </div>
                <span class="text-xs font-semibold text-hp-text-muted tracking-wide animate-pulse">Checking availability...</span>
            </div>
        `;
    };

    const checkAmenityContinuousConflict = (targetDate, targetSlot) => {
        const amCurrentEnd = extendAmenityCalState.amCurrentEndDate;
        const amCurrentSlot = extendAmenityCalState.amCurrentEndSlot;

        const fullTimeline = buildContinuousTimeline(extendAmenityCalState.amStartDate, targetDate, extendAmenityCalState.amStartSlot, targetSlot);
        const oldTimeline = buildContinuousTimeline(extendAmenityCalState.amStartDate, amCurrentEnd, extendAmenityCalState.amStartSlot, amCurrentSlot);

        if (fullTimeline.length <= oldTimeline.length) {
            return { hasConflict: false, conflictDate: null, conflictSlot: null, isDirectConflict: false };
        }

        const addedSlots = fullTimeline.slice(oldTimeline.length);
        for (const [d, s] of addedSlots) {
            const dayAvail = extendAmenityCalState.availabilityCache[d];
            if (dayAvail) {
                const slotKey = s === 'Daytime' ? 'daytime' : 'nighttime';
                if (dayAvail[slotKey] === false) {
                    return {
                        hasConflict: true,
                        conflictDate: d,
                        conflictSlot: s,
                        isDirectConflict: (d === targetDate),
                    };
                }
            }
        }

        return { hasConflict: false, conflictDate: null, conflictSlot: null, isDirectConflict: false };
    };

    const fetchAmenityAvailability = async (resId, amenityId, year, month) => {
        const cacheKey = `${amenityId}_${year}_${month}`;
        if (extendAmenityCalState.cachedMonths[cacheKey]) {
            return extendAmenityCalState.availabilityCache;
        }

        try {
            const resp = await fetch(`/staff/reservations/${resId}/availability?amenity_id=${encodeURIComponent(amenityId)}&month=${month + 1}&year=${year}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await resp.json().catch(() => ({}));
            const list = data.availability || [];
            list.forEach(item => {
                extendAmenityCalState.availabilityCache[item.date] = item;
            });
            extendAmenityCalState.cachedMonths[cacheKey] = true;
            return extendAmenityCalState.availabilityCache;
        } catch (e) {
            return extendAmenityCalState.availabilityCache || {};
        }
    };

    const renderExtendAmenityCalendarMonth = async () => {
        if (!extendAmenityCalGrid) return;
        const res = reservationData[extendAmenityCalState.resId];
        if (!res) return;

        const year = extendAmenityCalState.viewYear;
        const month = extendAmenityCalState.viewMonth;

        if (extendAmenityCalTitle) extendAmenityCalTitle.textContent = `${monthNames[month]} ${year}`;
        if (extendAmenityCalYear) extendAmenityCalYear.value = year;

        const cacheKey = `${extendAmenityCalState.amenityId}_${year}_${month}`;
        if (!extendAmenityCalState.cachedMonths[cacheKey]) {
            showCalendarLoading(extendAmenityCalGrid);
            await fetchAmenityAvailability(extendAmenityCalState.resId, extendAmenityCalState.amenityId, year, month);
        }

        // Avoid race conditions if user rapidly switched month
        if (extendAmenityCalState.viewYear !== year || extendAmenityCalState.viewMonth !== month) {
            return;
        }

        extendAmenityCalGrid.innerHTML = '';

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        for (let i = 0; i < firstDay; i++) {
            const empty = document.createElement('div');
            empty.className = 'edit-calendar__day edit-calendar__day--empty opacity-0 pointer-events-none';
            extendAmenityCalGrid.appendChild(empty);
        }

        const amCurrentEnd = extendAmenityCalState.amCurrentEndDate;
        const masterEnd = extendAmenityCalState.masterEndDate;

        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'edit-calendar__day flex flex-col items-center justify-center rounded-lg p-1.5 text-xs font-semibold transition-all duration-150 relative cursor-pointer border border-transparent';
            btn.textContent = d;
            btn.dataset.date = dateStr;

            const isPast = dateStr < todayStr;
            const isBeforeCurrentEnd = dateStr < amCurrentEnd;
            const isExceedMaster = dateStr > masterEnd;
            const inCurrentStay = dateStr >= extendAmenityCalState.amStartDate && dateStr <= amCurrentEnd;
            const inExtendedRange = dateStr > amCurrentEnd && dateStr <= extendAmenityCalState.selectedEndDate;

            const dayAvail = extendAmenityCalState.availabilityCache[dateStr];
            const hasDirectSlotTaken = dayAvail && (dayAvail.daytime === false || dayAvail.nighttime === false);
            const conflictCheck = checkAmenityContinuousConflict(dateStr, extendAmenityCalState.selectedEndSlot);

            if (isPast || isBeforeCurrentEnd) {
                btn.classList.add('is-disabled', 'opacity-30', 'cursor-not-allowed');
                btn.disabled = true;
                if (isBeforeCurrentEnd) btn.title = 'Amenity duration cannot step back.';
            } else if (isExceedMaster) {
                btn.classList.add('is-disabled', 'opacity-40', 'border-amber-500/30', 'bg-amber-500/10', 'text-amber-700', 'dark:text-amber-300', 'cursor-not-allowed');
                btn.disabled = true;
                btn.title = `Exceeds master stay check-out (${formatDate(masterEnd)}). Extend overall stay first.`;
            } else if (conflictCheck.hasConflict || hasDirectSlotTaken) {
                if (conflictCheck.isDirectConflict || hasDirectSlotTaken) {
                    btn.classList.add('is-booked', 'opacity-50', 'border-rose-500', 'bg-rose-500/20', 'text-rose-700', 'dark:text-rose-300', 'cursor-not-allowed', 'font-bold');
                    btn.disabled = true;
                    btn.title = `Booked on ${formatDate(dateStr)} by another guest.`;
                } else {
                    btn.classList.add('is-blocked', 'opacity-30', 'border-rose-300/40', 'bg-rose-500/5', 'text-rose-400', 'cursor-not-allowed');
                    btn.disabled = true;
                    btn.title = `Blocked because ${formatDate(conflictCheck.conflictDate)} (${conflictCheck.conflictSlot}) is booked. No skip days allowed.`;
                }
            } else {
                btn.classList.add('is-available', 'hover:border-hp-green', 'hover:bg-hp-green/10');
            }

            if (inCurrentStay && !isPast) {
                btn.classList.add('border-emerald-500/50', 'bg-emerald-500/20', 'text-emerald-800', 'dark:text-emerald-200');
            }

            if (inExtendedRange && !isPast && !isExceedMaster && !conflictCheck.hasConflict && !hasDirectSlotTaken) {
                btn.classList.add('is-selected', 'bg-hp-green', 'text-white', 'font-bold');
                btn.classList.remove('hover:bg-hp-green/10');
            }

            btn.addEventListener('click', () => {
                if (btn.disabled) return;
                const check = checkAmenityContinuousConflict(dateStr, extendAmenityCalState.selectedEndSlot);
                if (check.hasConflict) {
                    if (extendAmenityWarning) {
                        extendAmenityWarning.textContent = `Cannot extend to this date because ${formatDate(check.conflictDate)} (${check.conflictSlot}) is booked by another guest. Continuous stay required.`;
                        extendAmenityWarning.classList.remove('hidden');
                    }
                    return;
                }

                extendAmenityCalState.selectedEndDate = dateStr;
                if (extendAmenityNewEndDate) extendAmenityNewEndDate.value = dateStr;

                if (extendAmenityWarning) extendAmenityWarning.classList.add('hidden');
                syncExtendAmenitySessionPills();
                recalcExtendAmenityPrice();
                renderExtendAmenityCalendarMonth();
            });

            extendAmenityCalGrid.appendChild(btn);
        }

        if (extendAmenityCalStepHelp) {
            extendAmenityCalStepHelp.textContent = `Extend up to: ${formatDate(extendAmenityCalState.selectedEndDate || amCurrentEnd)} (${extendAmenityCalState.selectedEndSlot})`;
        }
    };

    const openExtendAmenityModal = async (reservationId, raId) => {
        const res = (window.staffReservationData && window.staffReservationData[reservationId]) || reservationData[reservationId];
        if (!res) return;
        const ra = res.reservation_amenities?.find(a => String(a.id) === String(raId));
        if (!ra) return;

        extendAmenityCalState.resId = reservationId;
        extendAmenityCalState.raId = raId;
        extendAmenityCalState.amenityId = ra.amenity_id || ra.amenity?.id;
        extendAmenityCalState.availabilityCache = {};
        extendAmenityCalState.cachedMonths = {};

        const resIdInput = document.getElementById('extendAmenityResId');
        const raIdInput = document.getElementById('extendAmenityRaId');
        const nameEl = document.getElementById('extendAmenityName');
        const currDurEl = document.getElementById('extendAmenityCurrentDuration');
        const limitEl = document.getElementById('extendAmenityStayLimit');
        const newEndDateInput = document.getElementById('extendAmenityNewEndDate');
        const newEndSlotInput = document.getElementById('extendAmenityNewEndSlot');
        const yearSelect = document.getElementById('extendAmenityCalYear');
        const modal = document.getElementById('extendAmenityModal');

        if (resIdInput) resIdInput.value = reservationId;
        if (raIdInput) raIdInput.value = raId;

        const amenityName = ra.amenity ? ra.amenity.amenities_name : (ra.amenity_name || 'Amenity');
        if (nameEl) nameEl.textContent = amenityName;

        const amStartDate = ra.start_date || res.reservation_date || todayStr;
        const amStartSlot = ra.start_slot || res.start_slot || 'Daytime';
        const amEndDate = ra.end_date || amStartDate;
        const amEndSlot = ra.end_slot || amStartSlot;

        extendAmenityCalState.amStartDate = amStartDate;
        extendAmenityCalState.amStartSlot = amStartSlot;
        extendAmenityCalState.amCurrentEndDate = amEndDate;
        extendAmenityCalState.amCurrentEndSlot = amEndSlot;

        if (currDurEl) {
            currDurEl.textContent = `${formatDate(amStartDate)} (${amStartSlot}) to ${formatDate(amEndDate)} (${amEndSlot})`;
        }

        const masterEndDate = res.end_date || res.reservation_date || todayStr;
        const masterEndSlot = res.end_slot || res.start_slot || 'Daytime';
        extendAmenityCalState.masterEndDate = masterEndDate;
        extendAmenityCalState.masterEndSlot = masterEndSlot;

        if (limitEl) {
            limitEl.textContent = `${formatDate(masterEndDate)} (${masterEndSlot})`;
        }

        extendAmenityCalState.selectedEndDate = amEndDate;
        extendAmenityCalState.selectedEndSlot = amEndSlot === 'Nighttime' ? 'Nighttime' : 'Daytime';

        if (newEndDateInput) newEndDateInput.value = extendAmenityCalState.selectedEndDate;
        if (newEndSlotInput) newEndSlotInput.value = extendAmenityCalState.selectedEndSlot;

        // 5-Year population
        const currentYear = new Date().getFullYear();
        if (yearSelect) {
            yearSelect.innerHTML = '';
            for (let y = currentYear; y <= currentYear + 5; y++) {
                const opt = document.createElement('option');
                opt.value = y;
                opt.textContent = y;
                yearSelect.appendChild(opt);
            }
        }

        const initialDateObj = new Date(amEndDate);
        extendAmenityCalState.viewYear = !isNaN(initialDateObj.getFullYear()) ? initialDateObj.getFullYear() : currentYear;
        extendAmenityCalState.viewMonth = !isNaN(initialDateObj.getMonth()) ? initialDateObj.getMonth() : new Date().getMonth();

        if (modal) {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        }

        syncExtendAmenitySessionPills();
        recalcExtendAmenityPrice();

        // Render current month (fast & smooth)
        renderExtendAmenityCalendarMonth();

        // Background prefetch next month for instantaneous page flips
        const nextMonthDate = new Date(extendAmenityCalState.viewYear, extendAmenityCalState.viewMonth + 1, 1);
        fetchAmenityAvailability(reservationId, extendAmenityCalState.amenityId, nextMonthDate.getFullYear(), nextMonthDate.getMonth());
    };

    const closeExtendAmenityModal = () => {
        if (extendAmenityModal) {
            extendAmenityModal.classList.remove('is-open');
            extendAmenityModal.setAttribute('aria-hidden', 'true');
        }
    };

    extendAmenityCloseButtons.forEach(btn => btn.addEventListener('click', closeExtendAmenityModal));

    extendAmenityCalPrev?.addEventListener('click', () => {
        extendAmenityCalState.viewMonth--;
        if (extendAmenityCalState.viewMonth < 0) {
            extendAmenityCalState.viewMonth = 11;
            extendAmenityCalState.viewYear--;
        }
        renderExtendAmenityCalendarMonth();
    });

    extendAmenityCalNext?.addEventListener('click', () => {
        extendAmenityCalState.viewMonth++;
        if (extendAmenityCalState.viewMonth > 11) {
            extendAmenityCalState.viewMonth = 0;
            extendAmenityCalState.viewYear++;
        }
        renderExtendAmenityCalendarMonth();
    });

    extendAmenityCalYear?.addEventListener('change', (e) => {
        extendAmenityCalState.viewYear = parseInt(e.target.value, 10);
        renderExtendAmenityCalendarMonth();
    });

    extendAmenityForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const resId = extendAmenityCalState.resId;
        const raId = extendAmenityCalState.raId;
        const res = reservationData[resId];
        const ra = res?.reservation_amenities?.find(a => String(a.id) === String(raId));
        if (!res || !ra) return;

        const amenityName = ra.amenity ? ra.amenity.amenities_name : (ra.amenity_name || 'Amenity');
        const newEndDate = extendAmenityCalState.selectedEndDate;
        const newEndSlot = extendAmenityCalState.selectedEndSlot;
        const addedCostStr = extendAmenityAddedCostText?.textContent?.replace('₱', '').replace(/,/g, '') || '0';
        const addedCost = parseFloat(addedCostStr) || 0;

        currentPendingExtension = {
            type: 'extend_amenity',
            reservationId: resId,
            raId: raId,
            newEndDate: newEndDate,
            newEndSlot: newEndSlot,
            cost: addedCost,
            title: `Extend ${amenityName}`,
            scheduleText: `Extended up to ${formatDate(newEndDate)} (${newEndSlot})`,
        };

        closeExtendAmenityModal();
        openExtensionPaymentModal();
    });

    // ── Add New Amenity Mid-Stay Modal Handlers ─────────────────────────────
    const addAmenityMidStayModal = document.getElementById('addAmenityMidStayModal');
    const addAmenityMidStayForm = document.getElementById('addAmenityMidStayForm');
    const addAmenityMidStayResId = document.getElementById('addAmenityMidStayResId');
    const addAmenityResId = document.getElementById('addAmenityResId');
    const midStayAmenitySelect = document.getElementById('midStayAmenitySelect');
    const addAmenityStartFixedText = document.getElementById('addAmenityStartFixedText');
    const addAmenityStayLimit = document.getElementById('addAmenityStayLimit');
    const midStayAirconWrapper = document.getElementById('midStayAirconWrapper');
    const midStayIsAircon = document.getElementById('midStayIsAircon');
    const addAmenityNewEndDate = document.getElementById('addAmenityNewEndDate');
    const addAmenityNewEndSlot = document.getElementById('addAmenityNewEndSlot');
    const addAmenityCalPrev = document.getElementById('addAmenityCalPrev');
    const addAmenityCalNext = document.getElementById('addAmenityCalNext');
    const addAmenityCalTitle = document.getElementById('addAmenityCalTitle');
    const addAmenityCalYear = document.getElementById('addAmenityCalYear');
    const addAmenityCalGrid = document.getElementById('addAmenityCalGrid');
    const addAmenityCalStepHelp = document.getElementById('addAmenityCalStepHelp');
    const midStaySlotsText = document.getElementById('midStaySlotsText');
    const midStayCostText = document.getElementById('midStayCostText');
    const addAmenityWarning = document.getElementById('addAmenityWarning');
    const submitAddAmenityBtn = document.getElementById('submitAddAmenityBtn');
    const addAmenityCloseButtons = document.querySelectorAll('[data-close-add-amenity-modal="true"]');

    // Earliest bookable (date, slot) for an amenity added mid-stay.
    // Checked-in mid-stay additions start at the current active session on site today
    const resolveMidStayStart = () => {
        const today = window.SERVER_TODAY || todayStr;
        const session = window.SERVER_CURRENT_SESSION || 'Daytime';
        return { date: today, slot: session };
    };

    const slotOrderIndex = (dateStr, slot) => {
        const [y, m, d] = String(dateStr).split('-').map(Number);
        return ((y * 372) + (m * 31) + d) * 2 + (String(slot).includes('Night') ? 1 : 0);
    };

    let addAmenityCalState = {
        resId: null,
        amenityId: null,
        viewYear: new Date().getFullYear(),
        viewMonth: new Date().getMonth(),
        startDate: null,
        startSlot: 'Daytime',
        selectedEndDate: null,
        selectedEndSlot: 'Daytime',
        masterStartDate: null,
        masterStartSlot: 'Daytime',
        masterEndDate: null,
        masterEndSlot: 'Daytime',
        selectionStep: 'start',
        availabilityCache: {},
        cachedMonths: {},
    };

    const syncAddAmenitySessionPills = () => {
        document.querySelectorAll('#addAmenityStartSlotGroup [data-slot-val]').forEach(btn => {
            const val = btn.dataset.slotVal;
            btn.dataset.active = (val === addAmenityCalState.startSlot) ? 'true' : 'false';
        });
        document.querySelectorAll('#addAmenityEndSlotGroup [data-slot-val]').forEach(btn => {
            const val = btn.dataset.slotVal;
            btn.dataset.active = (val === addAmenityCalState.selectedEndSlot) ? 'true' : 'false';
        });

        const startBadge = document.getElementById('addAmenityStartDateBadge');
        const endBadge = document.getElementById('addAmenityEndDateBadge');
        const stayLimitEl = document.getElementById('addAmenityStayLimit');
        const newStartDateInput = document.getElementById('addAmenityNewStartDate');
        const newStartSlotInput = document.getElementById('addAmenityNewStartSlot');
        const newEndDateInput = document.getElementById('addAmenityNewEndDate');
        const newEndSlotInput = document.getElementById('addAmenityNewEndSlot');

        if (startBadge) {
            startBadge.textContent = addAmenityCalState.startDate ? `${formatDate(addAmenityCalState.startDate)} (${addAmenityCalState.startSlot})` : 'Select Start';
        }
        if (endBadge) {
            endBadge.textContent = addAmenityCalState.selectedEndDate ? `${formatDate(addAmenityCalState.selectedEndDate)} (${addAmenityCalState.selectedEndSlot})` : 'Select End';
        }
        if (stayLimitEl) {
            const mStart = addAmenityCalState.masterStartDate ? `${formatDate(addAmenityCalState.masterStartDate)} (${addAmenityCalState.masterStartSlot})` : '—';
            const mEnd = addAmenityCalState.masterEndDate ? `${formatDate(addAmenityCalState.masterEndDate)} (${addAmenityCalState.masterEndSlot})` : '—';
            stayLimitEl.textContent = `${mStart} — ${mEnd}`;
        }
        if (newStartDateInput) newStartDateInput.value = addAmenityCalState.startDate || '';
        if (newStartSlotInput) newStartSlotInput.value = addAmenityCalState.startSlot || 'Daytime';
        if (newEndDateInput) newEndDateInput.value = addAmenityCalState.selectedEndDate || '';
        if (newEndSlotInput) newEndSlotInput.value = addAmenityCalState.selectedEndSlot || 'Daytime';
    };

    document.querySelectorAll('#addAmenityStartSlotGroup [data-slot-val]').forEach(btn => {
        btn.addEventListener('click', () => {
            const val = btn.dataset.slotVal;
            addAmenityCalState.startSlot = val;

            const startIdx = slotOrderIndex(addAmenityCalState.startDate, addAmenityCalState.startSlot);
            const endIdx = slotOrderIndex(addAmenityCalState.selectedEndDate, addAmenityCalState.selectedEndSlot);

            if (startIdx > endIdx) {
                addAmenityCalState.selectedEndDate = addAmenityCalState.startDate;
                addAmenityCalState.selectedEndSlot = val;
            }

            syncAddAmenitySessionPills();
            recalcAddAmenityPrice();
            renderAddAmenityCalendarMonth();
        });
    });

    document.querySelectorAll('#addAmenityEndSlotGroup [data-slot-val]').forEach(btn => {
        btn.addEventListener('click', () => {
            const val = btn.dataset.slotVal;
            addAmenityCalState.selectedEndSlot = val;

            const startIdx = slotOrderIndex(addAmenityCalState.startDate, addAmenityCalState.startSlot);
            const endIdx = slotOrderIndex(addAmenityCalState.selectedEndDate, val);

            if (endIdx < startIdx) {
                addAmenityCalState.selectedEndDate = addAmenityCalState.startDate;
                addAmenityCalState.selectedEndSlot = addAmenityCalState.startSlot;
            }

            syncAddAmenitySessionPills();
            recalcAddAmenityPrice();
            renderAddAmenityCalendarMonth();
        });
    });

    const checkAddAmenityContinuousConflict = (sDate, sSlot, eDate, eSlot) => {
        if (!addAmenityCalState.amenityId || !sDate || !eDate) {
            return { hasConflict: false, conflictDate: null, conflictSlot: null, isDirectConflict: false };
        }

        const fullTimeline = buildContinuousTimeline(sDate, eDate, sSlot, eSlot);

        for (const [d, s] of fullTimeline) {
            const dayAvail = addAmenityCalState.availabilityCache[d];
            if (dayAvail) {
                const slotKey = s === 'Daytime' ? 'daytime' : 'nighttime';
                if (dayAvail[slotKey] === false) {
                    return {
                        hasConflict: true,
                        conflictDate: d,
                        conflictSlot: s,
                        isDirectConflict: (d === eDate || d === sDate),
                    };
                }
            }
        }

        return { hasConflict: false, conflictDate: null, conflictSlot: null, isDirectConflict: false };
    };

    const fetchAddAmenityAvailability = async (resId, amenityId, year, month) => {
        if (!amenityId) return {};
        const cacheKey = `${amenityId}_${year}_${month}`;
        if (addAmenityCalState.cachedMonths[cacheKey]) {
            return addAmenityCalState.availabilityCache;
        }

        try {
            const resp = await fetch(`/staff/reservations/${resId}/availability?amenity_id=${encodeURIComponent(amenityId)}&month=${month + 1}&year=${year}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await resp.json().catch(() => ({}));
            const list = data.availability || [];
            list.forEach(item => {
                addAmenityCalState.availabilityCache[item.date] = item;
            });
            addAmenityCalState.cachedMonths[cacheKey] = true;
            return addAmenityCalState.availabilityCache;
        } catch (e) {
            return addAmenityCalState.availabilityCache || {};
        }
    };

    const renderAddAmenityCalendarMonth = async () => {
        if (!addAmenityCalGrid) return;
        const res = reservationData[addAmenityCalState.resId];
        if (!res) return;

        const year = addAmenityCalState.viewYear;
        const month = addAmenityCalState.viewMonth;

        if (addAmenityCalTitle) addAmenityCalTitle.textContent = `${monthNames[month]} ${year}`;
        if (addAmenityCalYear) addAmenityCalYear.value = year;

        if (!addAmenityCalState.amenityId) {
            addAmenityCalGrid.innerHTML = `
                <div class="col-span-7 flex flex-col items-center justify-center py-10 text-xs text-hp-text-muted font-medium">Please select an amenity above</div>
            `;
            return;
        }

        const cacheKey = `${addAmenityCalState.amenityId}_${year}_${month}`;
        if (!addAmenityCalState.cachedMonths[cacheKey]) {
            showCalendarLoading(addAmenityCalGrid);
            await fetchAddAmenityAvailability(addAmenityCalState.resId, addAmenityCalState.amenityId, year, month);
        }

        if (addAmenityCalState.viewYear !== year || addAmenityCalState.viewMonth !== month) {
            return;
        }

        addAmenityCalGrid.innerHTML = '';

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        for (let i = 0; i < firstDay; i++) {
            const empty = document.createElement('div');
            empty.className = 'edit-calendar__day edit-calendar__day--empty opacity-0 pointer-events-none';
            addAmenityCalGrid.appendChild(empty);
        }

        const startDate = addAmenityCalState.startDate;
        const endDate = addAmenityCalState.selectedEndDate;
        const masterStart = addAmenityCalState.masterStartDate;
        const masterEnd = addAmenityCalState.masterEndDate;

        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'edit-calendar__day flex flex-col items-center justify-center rounded-lg p-1.5 text-xs font-semibold transition-all duration-150 relative cursor-pointer border border-transparent';
            btn.textContent = d;
            btn.dataset.date = dateStr;

            const isPast = dateStr < todayStr;
            const isBeforeMaster = dateStr < masterStart;
            const isExceedMaster = dateStr > masterEnd;
            const isStart = dateStr === startDate;
            const isEnd = dateStr === endDate;
            const inSelectedRange = dateStr >= startDate && dateStr <= endDate;

            const dayAvail = addAmenityCalState.availabilityCache[dateStr];
            const hasDirectSlotTaken = dayAvail && (dayAvail.daytime === false && dayAvail.nighttime === false);

            if (isPast) {
                btn.classList.add('is-disabled', 'opacity-30', 'cursor-not-allowed');
                btn.disabled = true;
                btn.title = 'Date has already passed.';
            } else if (isBeforeMaster || isExceedMaster) {
                btn.classList.add('is-disabled', 'opacity-40', 'border-amber-500/30', 'bg-amber-500/10', 'text-amber-700', 'dark:text-amber-300', 'cursor-not-allowed');
                btn.disabled = true;
                btn.title = `Outside reservation stay window (${formatDate(masterStart)} — ${formatDate(masterEnd)}). Extend overall stay first.`;
            } else if (hasDirectSlotTaken) {
                btn.classList.add('is-booked', 'opacity-50', 'border-rose-500', 'bg-rose-500/20', 'text-rose-700', 'dark:text-rose-300', 'cursor-not-allowed', 'font-bold');
                btn.disabled = true;
                btn.title = `Fully booked on ${formatDate(dateStr)} by another guest.`;
            } else {
                btn.classList.add('is-available', 'hover:border-hp-green', 'hover:bg-hp-green/10');
            }

            if (inSelectedRange && !isPast && !isBeforeMaster && !isExceedMaster) {
                if (isStart || isEnd) {
                    btn.classList.add('is-selected', 'bg-hp-green', 'text-white', 'font-bold');
                    btn.classList.remove('hover:bg-hp-green/10');
                } else {
                    btn.classList.add('is-in-range', 'bg-hp-green/20', 'text-hp-green', 'font-bold');
                }
            }

            btn.addEventListener('click', () => {
                if (btn.disabled) return;

                if (addAmenityCalState.selectionStep === 'start') {
                    addAmenityCalState.startDate = dateStr;
                    if (dateStr > addAmenityCalState.selectedEndDate) {
                        addAmenityCalState.selectedEndDate = dateStr;
                    }
                    addAmenityCalState.selectionStep = 'end';
                } else {
                    if (dateStr < addAmenityCalState.startDate) {
                        addAmenityCalState.startDate = dateStr;
                        addAmenityCalState.selectionStep = 'end';
                    } else {
                        addAmenityCalState.selectedEndDate = dateStr;
                        addAmenityCalState.selectionStep = 'start';
                    }
                }

                syncAddAmenitySessionPills();
                recalcAddAmenityPrice();
                renderAddAmenityCalendarMonth();
            });

            addAmenityCalGrid.appendChild(btn);
        }

        if (addAmenityCalStepHelp) {
            const sLabel = addAmenityCalState.startDate ? `${formatDate(addAmenityCalState.startDate)} (${addAmenityCalState.startSlot})` : '—';
            const eLabel = addAmenityCalState.selectedEndDate ? `${formatDate(addAmenityCalState.selectedEndDate)} (${addAmenityCalState.selectedEndSlot})` : '—';
            addAmenityCalStepHelp.textContent = `${sLabel} → ${eLabel}`;
        }
    };

    const recalcAddAmenityPrice = () => {
        const resId = addAmenityCalState.resId;
        const res = reservationData[resId];
        if (!res) return;

        const amId = addAmenityCalState.amenityId;
        const amenityObj = window.ALL_AMENITIES?.find(a => String(a.id) === String(amId));

        const startDate = addAmenityCalState.startDate || window.SERVER_TODAY;
        const startSlot = addAmenityCalState.startSlot || 'Daytime';
        const endDate = addAmenityCalState.selectedEndDate || startDate;
        const endSlot = addAmenityCalState.selectedEndSlot || 'Daytime';

        const masterStartDate = addAmenityCalState.masterStartDate || res.reservation_date || window.SERVER_TODAY;
        const masterEndDate = addAmenityCalState.masterEndDate || res.end_date || masterStartDate;
        const masterStartSlot = addAmenityCalState.masterStartSlot || res.start_slot || 'Daytime';
        const masterEndSlot = addAmenityCalState.masterEndSlot || res.end_slot || 'Daytime';

        const masterTimeline = buildContinuousTimeline(masterStartDate, masterEndDate, masterStartSlot, masterEndSlot);
        const masterKeyMap = {};
        masterTimeline.forEach(([d, s]) => { masterKeyMap[`${d}_${s}`] = true; });

        if (slotOrderIndex(endDate, endSlot) < slotOrderIndex(startDate, startSlot)) {
            if (addAmenityWarning) {
                addAmenityWarning.textContent = 'Check-out date/session must be on or after check-in date/session.';
                addAmenityWarning.classList.remove('hidden');
            }
            if (midStaySlotsText) midStaySlotsText.textContent = '0 sessions';
            if (midStayCostText) midStayCostText.textContent = '₱0.00';
            if (submitAddAmenityBtn) submitAddAmenityBtn.disabled = true;
            return;
        }

        const itemTimeline = buildContinuousTimeline(startDate, endDate, startSlot, endSlot);

        if (addAmenityWarning) {
            addAmenityWarning.classList.add('hidden');
            addAmenityWarning.textContent = '';
        }

        if (!amenityObj) {
            if (midStaySlotsText) midStaySlotsText.textContent = '0 sessions';
            if (midStayCostText) midStayCostText.textContent = '₱0.00';
            if (submitAddAmenityBtn) submitAddAmenityBtn.disabled = true;
            return;
        }

        if (itemTimeline.length === 0) {
            if (addAmenityWarning) {
                addAmenityWarning.textContent = 'Check-out date/session must be on or after check-in date/session.';
                addAmenityWarning.classList.remove('hidden');
            }
            if (submitAddAmenityBtn) submitAddAmenityBtn.disabled = true;
            return;
        }

        let exceeds = false;
        for (const [d, s] of itemTimeline) {
            if (!masterKeyMap[`${d}_${s}`]) {
                exceeds = true;
                break;
            }
        }

        if (exceeds) {
            if (addAmenityWarning) {
                addAmenityWarning.textContent = `The chosen schedule (${formatDate(startDate)} [${startSlot}] to ${formatDate(endDate)} [${endSlot}]) exceeds the reservation's overall stay schedule (${formatDate(masterStartDate)} [${masterStartSlot}] to ${formatDate(masterEndDate)} [${masterEndSlot}]). Please extend the overall stay first.`;
                addAmenityWarning.classList.remove('hidden');
            }
            if (midStaySlotsText) midStaySlotsText.textContent = '0 sessions';
            if (midStayCostText) midStayCostText.textContent = '₱0.00';
            if (submitAddAmenityBtn) submitAddAmenityBtn.disabled = true;
            return;
        }

        const conflict = checkAddAmenityContinuousConflict(startDate, startSlot, endDate, endSlot);
        if (conflict.hasConflict) {
            if (addAmenityWarning) {
                addAmenityWarning.textContent = `Amenity is booked on ${formatDate(conflict.conflictDate)} (${conflict.conflictSlot}) by another guest. Please choose a different schedule or amenity.`;
                addAmenityWarning.classList.remove('hidden');
            }
            if (midStaySlotsText) midStaySlotsText.textContent = '0 sessions';
            if (midStayCostText) midStayCostText.textContent = '₱0.00';
            if (submitAddAmenityBtn) submitAddAmenityBtn.disabled = true;
            return;
        }

        let daySlots = 0;
        let nightSlots = 0;
        itemTimeline.forEach(([d, s]) => {
            if (s === 'Daytime') daySlots++;
            else nightSlots++;
        });
        const totalSlots = daySlots + nightSlots;

        const isAircon = midStayIsAircon?.checked || false;
        const dayPrice = isAircon && amenityObj.daytime_aircon_price ? parseFloat(amenityObj.daytime_aircon_price) : parseFloat(amenityObj.daytime_price || 0);
        const nightPrice = isAircon && amenityObj.nighttime_aircon_price ? parseFloat(amenityObj.nighttime_aircon_price) : parseFloat(amenityObj.nighttime_price || 0);

        const totalCost = (daySlots * dayPrice) + (nightSlots * nightPrice);

        if (midStaySlotsText) {
            const parts = [];
            if (daySlots > 0) parts.push(`${daySlots} Day${daySlots > 1 ? 's' : ''}`);
            if (nightSlots > 0) parts.push(`${nightSlots} Night${nightSlots > 1 ? 's' : ''}`);
            midStaySlotsText.textContent = `${totalSlots} session${totalSlots > 1 ? 's' : ''} (${parts.join(', ') || '0 sessions'})`;
        }

        if (midStayCostText) {
            midStayCostText.textContent = `₱${totalCost.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }

        if (submitAddAmenityBtn) submitAddAmenityBtn.disabled = false;
    };

    const populateMidStayAmenitySelect = (res) => {
        const selectEl = document.getElementById('midStayAmenitySelect');
        if (!selectEl) return;

        const occupiedTodayIds = new Set((window.OCCUPIED_TODAY_AMENITY_IDS || []).map(String));
        const existingAmenityIds = new Set(
            (res?.reservation_amenities || [])
                .filter((a) => (a.status || 'Active') !== 'Completed')
                .map((a) => String(a.amenity_id || a.amenity?.id))
                .filter(Boolean)
        );

        const allAmenities = window.ALL_AMENITIES || [];
        const pickable = [];
        const unavailable = [];

        allAmenities.forEach((amenity) => {
            const id = String(amenity.id);
            const name = amenity.amenities_name || amenity.name || 'Amenity';

            if (existingAmenityIds.has(id)) {
                unavailable.push({ name, reason: 'Already on this reservation' });
            } else if (occupiedTodayIds.has(id)) {
                unavailable.push({ name, reason: 'Occupied / unavailable today' });
            } else {
                pickable.push({ id, name });
            }
        });

        pickable.sort((a, b) => a.name.localeCompare(b.name));
        unavailable.sort((a, b) => a.name.localeCompare(b.name));

        selectEl.innerHTML = '<option value="">-- Choose an amenity --</option>';

        pickable.forEach(({ id, name }) => {
            const opt = document.createElement('option');
            opt.value = id;
            opt.textContent = name;
            selectEl.appendChild(opt);
        });

        unavailable.forEach(({ name, reason }) => {
            const opt = document.createElement('option');
            opt.value = '';
            opt.disabled = true;
            opt.textContent = `${name} (${reason})`;
            selectEl.appendChild(opt);
        });

        if (pickable.length === 0) {
            const emptyOpt = document.createElement('option');
            emptyOpt.value = '';
            emptyOpt.disabled = true;
            emptyOpt.textContent = unavailable.length
                ? 'No amenities available to add right now'
                : 'No amenities configured in the system';
            selectEl.appendChild(emptyOpt);
        }
    };

    const openAddAmenityMidStayModal = (reservationId) => {
        const res = (window.staffReservationData && window.staffReservationData[reservationId]) || reservationData[reservationId];
        if (!res) return;

        addAmenityCalState.resId = reservationId;
        addAmenityCalState.amenityId = null;
        addAmenityCalState.availabilityCache = {};
        addAmenityCalState.cachedMonths = {};

        const resIdInput = document.getElementById('addAmenityMidStayResId');
        const resIdEl = document.getElementById('addAmenityResId');
        if (resIdInput) resIdInput.value = reservationId;
        if (resIdEl) resIdEl.textContent = reservationId;

        const today = window.SERVER_TODAY || todayStr;
        const curSession = window.SERVER_CURRENT_SESSION || 'Daytime';
        const masterStartDate = res.reservation_date || today;
        const masterStartSlot = res.start_slot || 'Daytime';
        const masterEndDate = res.end_date || masterStartDate;
        const masterEndSlot = res.end_slot || res.start_slot || 'Daytime';

        const resolvedStart = resolveMidStayStart();
        const initialStartDate = (resolvedStart.date < masterStartDate) ? masterStartDate : resolvedStart.date;
        const initialStartSlot = (resolvedStart.date < masterStartDate) ? masterStartSlot : resolvedStart.slot;

        addAmenityCalState.startDate = initialStartDate;
        addAmenityCalState.startSlot = initialStartSlot;
        addAmenityCalState.selectedEndDate = masterEndDate;
        addAmenityCalState.selectedEndSlot = masterEndSlot;
        addAmenityCalState.masterStartDate = masterStartDate;
        addAmenityCalState.masterStartSlot = masterStartSlot;
        addAmenityCalState.masterEndDate = masterEndDate;
        addAmenityCalState.masterEndSlot = masterEndSlot;
        addAmenityCalState.selectionStep = 'start';

        // Reset inputs
        const selectEl = document.getElementById('midStayAmenitySelect');
        const airconCheck = document.getElementById('midStayIsAircon');
        const airconWrap = document.getElementById('midStayAirconWrapper');
        populateMidStayAmenitySelect(res);
        if (selectEl) selectEl.value = '';
        if (airconCheck) airconCheck.checked = false;
        if (airconWrap) airconWrap.classList.add('hidden');

        // Populate 5 years
        const currentYear = new Date().getFullYear();
        const yearSelect = document.getElementById('addAmenityCalYear');
        if (yearSelect) {
            yearSelect.innerHTML = '';
            for (let y = currentYear; y <= currentYear + 5; y++) {
                const opt = document.createElement('option');
                opt.value = y;
                opt.textContent = y;
                yearSelect.appendChild(opt);
            }
        }

        const initialDateObj = new Date(initialStartDate);
        addAmenityCalState.viewYear = !isNaN(initialDateObj.getFullYear()) ? initialDateObj.getFullYear() : currentYear;
        addAmenityCalState.viewMonth = !isNaN(initialDateObj.getMonth()) ? initialDateObj.getMonth() : new Date().getMonth();

        const warningEl = document.getElementById('addAmenityWarning');
        if (warningEl) warningEl.classList.add('hidden');
        syncAddAmenitySessionPills();
        recalcAddAmenityPrice();
        renderAddAmenityCalendarMonth();

        const modal = document.getElementById('addAmenityMidStayModal');
        if (modal) {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        }
    };

    const closeAddAmenityMidStayModal = () => {
        if (addAmenityMidStayModal) {
            addAmenityMidStayModal.classList.remove('is-open');
            addAmenityMidStayModal.setAttribute('aria-hidden', 'true');
        }
    };

    addAmenityCloseButtons.forEach(btn => btn.addEventListener('click', closeAddAmenityMidStayModal));

    midStayAmenitySelect?.addEventListener('change', async (e) => {
        const amId = e.target.value;
        addAmenityCalState.amenityId = amId;
        addAmenityCalState.availabilityCache = {};
        addAmenityCalState.cachedMonths = {};

        const amenityObj = window.ALL_AMENITIES?.find(a => String(a.id) === String(amId));
        if (amenityObj) {
            const hasAirconOption = parseFloat(amenityObj.daytime_aircon_price || 0) > 0 || parseFloat(amenityObj.nighttime_aircon_price || 0) > 0 || Boolean(amenityObj.has_aircon);
            if (hasAirconOption) {
                midStayAirconWrapper?.classList.remove('hidden');
            } else {
                midStayAirconWrapper?.classList.add('hidden');
                if (midStayIsAircon) midStayIsAircon.checked = false;
            }
        } else {
            midStayAirconWrapper?.classList.add('hidden');
            if (midStayIsAircon) midStayIsAircon.checked = false;
        }

        recalcAddAmenityPrice();
        renderAddAmenityCalendarMonth();
    });

    midStayIsAircon?.addEventListener('change', () => {
        recalcAddAmenityPrice();
    });

    addAmenityCalPrev?.addEventListener('click', () => {
        addAmenityCalState.viewMonth--;
        if (addAmenityCalState.viewMonth < 0) {
            addAmenityCalState.viewMonth = 11;
            addAmenityCalState.viewYear--;
        }
        renderAddAmenityCalendarMonth();
    });

    addAmenityCalNext?.addEventListener('click', () => {
        addAmenityCalState.viewMonth++;
        if (addAmenityCalState.viewMonth > 11) {
            addAmenityCalState.viewMonth = 0;
            addAmenityCalState.viewYear++;
        }
        renderAddAmenityCalendarMonth();
    });

    addAmenityCalYear?.addEventListener('change', (e) => {
        addAmenityCalState.viewYear = parseInt(e.target.value, 10);
        renderAddAmenityCalendarMonth();
    });

    addAmenityMidStayForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const resId = addAmenityCalState.resId;
        const res = reservationData[resId];
        const amId = addAmenityCalState.amenityId;
        const amenityObj = window.ALL_AMENITIES?.find(a => String(a.id) === String(amId));
        if (!res || !amenityObj) return;

        const startDate = addAmenityCalState.startDate;
        const startSlot = addAmenityCalState.startSlot;
        const endDate = addAmenityCalState.selectedEndDate;
        const endSlot = addAmenityCalState.selectedEndSlot;
        const isAircon = midStayIsAircon?.checked || false;

        const costStr = midStayCostText?.textContent?.replace('₱', '').replace(/,/g, '') || '0';
        const totalCost = parseFloat(costStr) || 0;

        currentPendingExtension = {
            type: 'add_amenity',
            reservationId: resId,
            amenityId: amId,
            startDate: startDate,
            startSlot: startSlot,
            endDate: endDate,
            endSlot: endSlot,
            isAircon: isAircon,
            quantity: 1,
            cost: totalCost,
            title: `Add ${amenityObj.amenities_name}`,
            scheduleText: `${formatDate(startDate)} (${startSlot}) to ${formatDate(endDate)} (${endSlot})`,
        };

        closeAddAmenityMidStayModal();
        openExtensionPaymentModal();
    });

    // ── Extension Payment Confirmation Modal Handlers ───────────────────────
    const extensionPaymentModal = document.getElementById('extensionPaymentModal');
    const extPayItemName = document.getElementById('extPayItemName');
    const extPayItemCost = document.getElementById('extPayItemCost');
    const extPayItemSchedule = document.getElementById('extPayItemSchedule');
    const extPayTotalAmount = document.getElementById('extPayTotalAmount');
    const confirmExtensionPaymentBtn = document.getElementById('confirmExtensionPaymentBtn');
    const extensionPaymentCloseButtons = document.querySelectorAll('[data-close-extension-payment-modal="true"]');

    const openExtensionPaymentModal = () => {
        if (!currentPendingExtension) return;

        if (extPayItemName) extPayItemName.textContent = currentPendingExtension.title;
        if (extPayItemCost) extPayItemCost.textContent = `₱${currentPendingExtension.cost.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        if (extPayItemSchedule) extPayItemSchedule.textContent = currentPendingExtension.scheduleText;
        if (extPayTotalAmount) extPayTotalAmount.textContent = `₱${currentPendingExtension.cost.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

        if (extensionPaymentModal) {
            extensionPaymentModal.classList.add('is-open');
            extensionPaymentModal.setAttribute('aria-hidden', 'false');
        }
    };

    const closeExtensionPaymentModal = () => {
        if (extensionPaymentModal) {
            extensionPaymentModal.classList.remove('is-open');
            extensionPaymentModal.setAttribute('aria-hidden', 'true');
        }
    };

    extensionPaymentCloseButtons.forEach(btn => btn.addEventListener('click', closeExtensionPaymentModal));

    confirmExtensionPaymentBtn?.addEventListener('click', async () => {
        if (!currentPendingExtension) return;

        const btn = confirmExtensionPaymentBtn;
        btn.disabled = true;
        btn.textContent = 'Processing payment...';

        const resId = currentPendingExtension.reservationId;

        try {
            if (currentPendingExtension.type === 'extend_amenity') {
                const raId = currentPendingExtension.raId;
                const response = await fetch(`/staff/reservations/${resId}/amenities/${raId}/extend`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        new_end_date: currentPendingExtension.newEndDate,
                        new_end_slot: currentPendingExtension.newEndSlot,
                    }),
                });

                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Failed to extend amenity.');
                }

                const res = reservationData[resId];
                if (res) {
                    const ra = res.reservation_amenities?.find(a => String(a.id) === String(raId));
                    if (ra) {
                        ra.end_date = data.new_end_date;
                        ra.end_slot = data.new_end_slot;
                        ra.price_at_booking = (parseFloat(ra.price_at_booking || 0) + parseFloat(data.added_cost || 0));
                    }
                    res.total_amount = data.new_total;
                    res.amount_paid = data.new_total;
                    if (data.checkout_at) {
                        res.checkout_at = data.checkout_at;
                    }
                }

                if (data.checkout_at) {
                    document.querySelectorAll(`[data-checkout-at][data-reservation-id="${resId}"]`).forEach(el => {
                        el.setAttribute('data-checkout-at', data.checkout_at);
                    });
                }

                closeExtensionPaymentModal();
                openReservationModal(resId);
                refreshCheckoutCountdowns();
                showToast(data.message || 'Amenity extended successfully.');
            } else if (currentPendingExtension.type === 'add_amenity') {
                const response = await fetch(`/staff/reservations/${resId}/amenities/add`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        amenity_id: currentPendingExtension.amenityId,
                        start_date: currentPendingExtension.startDate,
                        start_slot: currentPendingExtension.startSlot,
                        end_date: currentPendingExtension.endDate,
                        end_slot: currentPendingExtension.endSlot,
                        is_aircon: currentPendingExtension.isAircon,
                        quantity: currentPendingExtension.quantity,
                    }),
                });

                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Failed to add amenity.');
                }

                const res = reservationData[resId];
                if (res) {
                    if (!res.reservation_amenities) res.reservation_amenities = [];
                    const amenityRecord = {
                        ...data.amenity,
                        starts_at: data.starts_at || data.amenity?.starts_at,
                        checkout_at: data.checkout_at || data.amenity?.checkout_at,
                    };
                    res.reservation_amenities.push(amenityRecord);
                    res.total_amount = data.new_total;
                    res.amount_paid = data.new_total;
                    if (data.reservation_checkout_at || data.checkout_at) {
                        res.checkout_at = data.reservation_checkout_at || data.checkout_at;
                    }
                }

                if (data.checkout_at) {
                    document.querySelectorAll(`[data-checkout-at][data-reservation-id="${resId}"]`).forEach(el => {
                        el.setAttribute('data-checkout-at', data.checkout_at);
                    });
                }

                closeExtensionPaymentModal();
                openReservationModal(resId);
                refreshCheckoutCountdowns();
                showToast(data.message || 'Amenity added successfully.');
            }
        } catch (error) {
            alert(error.message || 'Transaction failed.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Confirm & Pay at Counter';
        }
    });

    // Register active handlers for delegated click events
    const checkInsHandlers = {
        openExtendStayModal,
        openAddAmenityMidStayModal,
        openExtendAmenityModal,
        openCompanionCheckoutModal: (id) => openCompanionCheckoutModal(id),
        openReservationModal: (id) => openReservationModal(id),
        handleAmenityCheckout,
        stopQrScanner: () => stopQrScanner?.(),
    };
    activeStaffCheckInsHandlers = checkInsHandlers;

    window.addEventListener('spa:leaving', () => {
        if (activeStaffCheckInsHandlers === checkInsHandlers) {
            activeStaffCheckInsHandlers = null;
        }
        stopQrScanner?.();
    }, { once: true });

    // Guest modal functionality
    const guestModal = document.getElementById('guestModal');
    const guestModalBody = document.getElementById('guestModalBody');
    const guestModalCloseButtons = document.querySelectorAll('[data-close-modal="true"]');
    const guestRows = document.querySelectorAll('#guestTableBody .guest-row');
    const guestCheckOutBtn = document.getElementById('guestCheckOutBtn');
    let currentCustomerId = null;

    const openGuestModal = (customerId) => {
        currentCustomerId = customerId;
        const customer = guestData[customerId];
        if (!customer) return;

        let html = '';

        // Find the active reservation for this guest
        const activeReservationGuest = customer.reservation_guests?.find(rg => {
            const reservation = rg.reservation;
            if (!reservation) return false;
            const status = (reservation.status || '').toLowerCase().replace(/ /g, '_');
            return status !== 'checked_out' && status !== 'checkedout' && status !== 'checked-out' && !rg.checked_out_at;
        });

        if (activeReservationGuest && activeReservationGuest.reservation) {
            const reservation = activeReservationGuest.reservation;
            const isMainGuest = activeReservationGuest.is_primary_guest;

            // Show guest's own info first
            html += `
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="margin-bottom: 0.75rem; font-weight: 600;">Guest Information</h4>
                    <div style="padding: 1rem; background-color: var(--hp-cream, #f5f5f5); border-radius: 0.5rem; border-left: 4px solid ${isMainGuest ? '#c8a45d' : 'var(--hp-green)'};">
                        <div style="margin-bottom: 0.5rem;">
                            <strong>${customer.first_name} ${customer.middle_name || ''} ${customer.last_name}</strong>
                            <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.6rem; border-radius: 999px; background-color: ${isMainGuest ? 'rgba(200, 164, 93, 0.15)' : 'rgba(26, 58, 31, 0.15)'}; color: ${isMainGuest ? '#c8a45d' : 'var(--hp-green)'}; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-left: 0.5rem;">${isMainGuest ? 'Main Guest' : 'Companion'}</span>
                        </div>
                        <div style="font-size: 0.85rem; color: #666; margin-bottom: 0.25rem;">
                            Age: ${customer.age || 'N/A'} | Gender: ${customer.gender || 'N/A'} | Status: ${customer.is_foreigner ? 'Foreigner' : 'Filipino'}
                        </div>
                        <div style="font-size: 0.85rem; color: #666;">
                            Phone: ${customer.phone || 'N/A'} | Email: ${customer.email || 'N/A'}
                        </div>
                    </div>
                </div>
            `;

            // Show reservation details
            const mainGuestEntry = reservation.reservation_guests?.find(rg => rg.is_primary_guest);
            const mainGuest = mainGuestEntry?.customer;
            const mainGuestName = mainGuest ? `${mainGuest.first_name} ${mainGuest.middle_name || ''} ${mainGuest.last_name}` : 'N/A';

            const expectedCheckout = formatExpectedCheckout(reservation);
            const startSlot = reservation.start_slot || 'Daytime';
            const endSlot = reservation.end_slot || startSlot;
            const isMultiDay = reservation.end_date && reservation.end_date !== reservation.reservation_date;

            html += `
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="margin-bottom: 0.75rem; font-weight: 600;">Reservation Details</h4>
                    ${reservation.checkout_at ? `<div class="resv-checkout-countdown resv-checkout-countdown--compact" data-checkout-at="${reservation.checkout_at}" data-checkout-state=""></div>` : ''}
                    <div style="padding: 1rem; background-color: var(--hp-cream, #f5f5f5); border-radius: 0.5rem; border-left: 4px solid #c8a45d;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Reservation ID:</span>
                            <strong>#${reservation.id}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Main Guest:</span>
                            <strong>${mainGuestName}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Reservation Stay:</span>
                            <strong>${isMultiDay ? `${formatDate(reservation.reservation_date)} (${startSlot}) – ${formatDate(reservation.end_date)} (${endSlot})` : `${formatDate(reservation.reservation_date)} (${startSlot})`}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Expected Check-out:</span>
                            <strong style="color: #1a5c3c;">${expectedCheckout.date} (${expectedCheckout.session}${expectedCheckout.time ? ` · ${expectedCheckout.time}` : ''})</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Check-in:</span>
                            <strong>${reservation.check_in || 'N/A'}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Check-out:</span>
                            <strong>${reservation.check_out || 'Not yet'}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Reservation Type:</span>
                            <strong>${reservation.reservation_type === 'walk_in' ? 'Walk-in' : 'Online'}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Status:</span>
                            <strong>${reservation.status || 'Active'}</strong>
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Show individual guest info if no active reservation
            html += `
                <div class="guest-card">
                    <div class="guest-card__grid">
                        <div>
                            <span class="guest-label">Name</span>
                            <span class="guest-value">${customer.first_name} ${customer.middle_name || ''} ${customer.last_name}</span>
                        </div>
                        <div>
                            <span class="guest-label">Age</span>
                            <span class="guest-value">${customer.age || 'N/A'}</span>
                        </div>
                        <div>
                            <span class="guest-label">Gender</span>
                            <span class="guest-value">${customer.gender || 'N/A'}</span>
                        </div>
                        <div>
                            <span class="guest-label">Status</span>
                            <span class="guest-value">${customer.is_foreigner ? 'Foreigner' : 'Filipino'}</span>
                        </div>
                        <div>
                            <span class="guest-label">Phone</span>
                            <span class="guest-value">${customer.phone || 'N/A'}</span>
                        </div>
                        <div>
                            <span class="guest-label">Email</span>
                            <span class="guest-value">${customer.email || 'N/A'}</span>
                        </div>
                    </div>
                </div>
                <div style="padding: 1rem; background-color: rgba(255, 193, 7, 0.15); border-radius: 0.5rem; margin-top: 1rem;">
                    <strong>No active reservation found for this guest.</strong>
                </div>
            `;
        }

        // Update modal role badge
        const roleBadge = document.getElementById('guestModalRole');
        if (roleBadge) {
            roleBadge.textContent = customer.is_foreigner ? 'Foreigner' : 'Local';
        }

        guestModalBody.innerHTML = html;
        refreshCheckoutCountdowns();
        guestModal.classList.add('is-open');
        guestModal.setAttribute('aria-hidden', 'false');
    };

    const closeGuestModal = () => {
        currentCustomerId = null;
        guestModal.classList.remove('is-open');
        guestModal.setAttribute('aria-hidden', 'true');
    };

    // Bulk Manage Modal logic
    let currentBulkResId = null;
    // Which bulk GROUP the manage modal is scoped to (empty = whole reservation).
    let currentBulkGender = '';
    let currentBulkAgeGroup = '';
    let currentBulkIsForeigner = null;
    const bulkGroupManageModal = document.getElementById('bulkGroupManageModal');
    const bulkManageResIdEl = document.getElementById('bulkManageResId');
    const bulkManageActiveCountEl = document.getElementById('bulkManageActiveCount');
    const bulkManageTotalCountEl = document.getElementById('bulkManageTotalCount');

    // Dual pool counters & checkouter elements
    const bulkManagePoolActiveCountEl = document.getElementById('bulkManagePoolActiveCount');
    const bulkManagePoolTotalCountEl = document.getElementById('bulkManagePoolTotalCount');
    const bulkManagePoolQtyInput = document.getElementById('bulkManagePoolQtyInput');
    const bulkManagePoolQtyMinus = document.getElementById('bulkManagePoolQtyMinus');
    const bulkManagePoolQtyPlus = document.getElementById('bulkManagePoolQtyPlus');
    const bulkManagePoolBtnDecrease = document.getElementById('bulkManagePoolBtnDecrease');
    const bulkManagePoolCheckOutBtn = document.getElementById('bulkManagePoolCheckOutBtn');
    const bulkManagePoolControls = document.getElementById('bulkManagePoolControls');
    const bulkManagePoolEmptyMsg = document.getElementById('bulkManagePoolEmptyMsg');

    const bulkManageNoPoolActiveCountEl = document.getElementById('bulkManageNoPoolActiveCount');
    const bulkManageNoPoolTotalCountEl = document.getElementById('bulkManageNoPoolTotalCount');
    const bulkManageNoPoolQtyInput = document.getElementById('bulkManageNoPoolQtyInput');
    const bulkManageNoPoolQtyMinus = document.getElementById('bulkManageNoPoolQtyMinus');
    const bulkManageNoPoolQtyPlus = document.getElementById('bulkManageNoPoolQtyPlus');
    const bulkManageNoPoolBtnDecrease = document.getElementById('bulkManageNoPoolBtnDecrease');
    const bulkManageNoPoolCheckOutBtn = document.getElementById('bulkManageNoPoolCheckOutBtn');
    const bulkManageNoPoolControls = document.getElementById('bulkManageNoPoolControls');
    const bulkManageNoPoolEmptyMsg = document.getElementById('bulkManageNoPoolEmptyMsg');

    const openBulkManageModal = (
        resId,
        active,
        total,
        demoText = '',
        bulkGender = '',
        bulkAgeGroup = '',
        bulkNationality = '',
        activePool = 0,
        totalPool = 0,
        activeNoPool = 0,
        totalNoPool = 0
    ) => {
        currentBulkResId = resId;
        currentBulkGender = bulkGender || '';
        currentBulkAgeGroup = bulkAgeGroup || '';
        currentBulkIsForeigner = bulkNationality === 'Foreigner' ? true : (bulkNationality ? false : null);

        const actP = parseInt(activePool, 10) || 0;
        const totP = parseInt(totalPool, 10) || 0;
        const actNP = parseInt(activeNoPool, 10) || 0;
        const totNP = parseInt(totalNoPool, 10) || 0;
        const actTotal = parseInt(active, 10) || (actP + actNP);
        const totTotal = parseInt(total, 10) || (totP + totNP);

        if (bulkManageResIdEl) bulkManageResIdEl.textContent = resId;
        if (bulkManageActiveCountEl) bulkManageActiveCountEl.textContent = actTotal;
        if (bulkManageTotalCountEl) bulkManageTotalCountEl.textContent = totTotal;

        // Populate Pool Pass section
        if (bulkManagePoolActiveCountEl) bulkManagePoolActiveCountEl.textContent = actP;
        if (bulkManagePoolTotalCountEl) bulkManagePoolTotalCountEl.textContent = totP;
        if (bulkManagePoolQtyInput) {
            bulkManagePoolQtyInput.value = actP > 0 ? 1 : 0;
            bulkManagePoolQtyInput.max = Math.max(actP, 1);
        }
        if (bulkManagePoolControls) bulkManagePoolControls.style.display = actP > 0 ? 'block' : 'none';
        if (bulkManagePoolEmptyMsg) bulkManagePoolEmptyMsg.style.display = actP === 0 ? 'block' : 'none';
        if (bulkManagePoolCheckOutBtn) bulkManagePoolCheckOutBtn.disabled = actP === 0;
        if (bulkManagePoolBtnDecrease) bulkManagePoolBtnDecrease.disabled = actP === 0;

        // Populate Standard (No Pool) section
        if (bulkManageNoPoolActiveCountEl) bulkManageNoPoolActiveCountEl.textContent = actNP;
        if (bulkManageNoPoolTotalCountEl) bulkManageNoPoolTotalCountEl.textContent = totNP;
        if (bulkManageNoPoolQtyInput) {
            bulkManageNoPoolQtyInput.value = actNP > 0 ? 1 : 0;
            bulkManageNoPoolQtyInput.max = Math.max(actNP, 1);
        }
        if (bulkManageNoPoolControls) bulkManageNoPoolControls.style.display = actNP > 0 ? 'block' : 'none';
        if (bulkManageNoPoolEmptyMsg) bulkManageNoPoolEmptyMsg.style.display = actNP === 0 ? 'block' : 'none';
        if (bulkManageNoPoolCheckOutBtn) bulkManageNoPoolCheckOutBtn.disabled = actNP === 0;
        if (bulkManageNoPoolBtnDecrease) bulkManageNoPoolBtnDecrease.disabled = actNP === 0;

        let demoHtml = demoText
            ? `<div style="font-size: 0.8rem; color: var(--hp-text-muted);">${demoText}</div>`
            : '';
        if (!demoHtml) {
            const res = reservationData[resId];
            if (res) {
                const bulkGuest = res.reservation_guests?.find(rg => {
                    const fn = (rg.customer?.first_name || '').toLowerCase();
                    return fn.startsWith('bulk') || fn.includes('companion');
                });
                if (bulkGuest && bulkGuest.customer) {
                    const c = bulkGuest.customer;
                    const gender = c.gender || 'N/A';
                    const nationality = c.is_foreigner ? 'Foreigner' : 'Filipino';
                    demoHtml = `<div style="font-size: 0.8rem; color: var(--hp-text-muted);">${gender} &bull; ${ageGroupLabel(c.age)} &bull; ${nationality}</div>`;
                }
            }
        }

        const demoEl = document.getElementById('bulkManageDemographics');
        if (demoEl) {
            demoEl.innerHTML = demoHtml;
        }

        if (bulkGroupManageModal) {
            bulkGroupManageModal.classList.add('is-open');
            bulkGroupManageModal.setAttribute('aria-hidden', 'false');
        }
    };

    const closeBulkManageModal = () => {
        currentBulkResId = null;
        if (bulkGroupManageModal) {
            bulkGroupManageModal.classList.remove('is-open');
            bulkGroupManageModal.setAttribute('aria-hidden', 'true');
        }
    };

    document.querySelectorAll('[data-close-bulk-manage-modal="true"]').forEach(btn => {
        btn.addEventListener('click', closeBulkManageModal);
    });

    // Check out bulk companions by pool access category ('with_pool' or 'without_pool')
    const bulkCheckOut = async (count, poolAccessType = 'any') => {
        if (!currentBulkResId) return;

        let activeCount = 0;
        if (poolAccessType === 'with_pool') {
            activeCount = Number(bulkManagePoolActiveCountEl?.textContent || 0);
        } else if (poolAccessType === 'without_pool') {
            activeCount = Number(bulkManageNoPoolActiveCountEl?.textContent || 0);
        } else {
            activeCount = Number(bulkManageActiveCountEl?.textContent || 0);
        }

        if (activeCount === 0) {
            showToast('No active companions remaining in this group.', 'error');
            return;
        }

        const qty = Math.min(Math.max(parseInt(count, 10) || 1, 1), activeCount);
        const groupLabel = poolAccessType === 'with_pool' ? 'with pool pass' : (poolAccessType === 'without_pool' ? 'without pool pass' : '');
        if (!confirm(`Check out ${qty} companion${qty === 1 ? '' : 's'} ${groupLabel} from this bulk group?`)) {
            return;
        }

        const submitBtn = poolAccessType === 'with_pool' ? bulkManagePoolCheckOutBtn : (poolAccessType === 'without_pool' ? bulkManageNoPoolCheckOutBtn : null);
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Checking out...';
        }

        try {
            const response = await fetch(`/staff/reservations/${currentBulkResId}/bulk-companions/check-out`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    count: qty,
                    pool_access_type: poolAccessType,
                    gender: currentBulkGender || null,
                    age_group: currentBulkAgeGroup || null,
                    is_foreigner: currentBulkIsForeigner,
                }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(payload.message || 'Unable to check out companions.');
            }

            const done = payload.checked_out ?? qty;
            const remaining = payload.remaining ?? 0;
            const message = remaining > 0
                ? `${done} companion${done === 1 ? '' : 's'} checked out. ${remaining} still inside.`
                : `${done} companion${done === 1 ? '' : 's'} checked out successfully.`;

            // Close bulk manage modal if open
            if (bulkManageModal) {
                bulkManageModal.classList.remove('is-open');
                bulkManageModal.setAttribute('aria-hidden', 'true');
            }

            // Update local reservation data
            if (currentBulkResId && window.staffReservationData && window.staffReservationData[currentBulkResId]) {
                const res = window.staffReservationData[currentBulkResId];
                let checkedCount = 0;
                (res.reservation_guests || []).forEach(g => {
                    if (!g.is_primary_guest && !g.checked_out_at && checkedCount < done) {
                        const isBulkGuest = !g.customer || !g.customer.first_name || g.customer.first_name.toLowerCase().includes('companion');
                        if (isBulkGuest) {
                            g.checked_out_at = new Date().toISOString();
                            checkedCount++;
                        }
                    }
                });
                if (currentReservationId === currentBulkResId) {
                    openReservationModal(currentBulkResId);
                }
            }

            showToast(message);
        } catch (err) {
            console.error(err);
            showToast(err.message || 'Unable to check out companions.', 'error');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = poolAccessType === 'with_pool' ? 'Check Out Pool' : 'Check Out Standard';
            }
        }
    };

    // Pool pass checkouter events
    bulkManagePoolBtnDecrease?.addEventListener('click', () => bulkCheckOut(1, 'with_pool'));
    bulkManagePoolCheckOutBtn?.addEventListener('click', () => {
        bulkCheckOut(bulkManagePoolQtyInput?.value || 1, 'with_pool');
    });
    bulkManagePoolQtyMinus?.addEventListener('click', () => {
        if (!bulkManagePoolQtyInput) return;
        const val = parseInt(bulkManagePoolQtyInput.value, 10) || 1;
        if (val > 1) bulkManagePoolQtyInput.value = val - 1;
    });
    bulkManagePoolQtyPlus?.addEventListener('click', () => {
        if (!bulkManagePoolQtyInput) return;
        const val = parseInt(bulkManagePoolQtyInput.value, 10) || 1;
        const max = parseInt(bulkManagePoolQtyInput.max || '50', 10) || 50;
        if (val < max) bulkManagePoolQtyInput.value = val + 1;
    });

    // Standard (no pool) checkouter events
    bulkManageNoPoolBtnDecrease?.addEventListener('click', () => bulkCheckOut(1, 'without_pool'));
    bulkManageNoPoolCheckOutBtn?.addEventListener('click', () => {
        bulkCheckOut(bulkManageNoPoolQtyInput?.value || 1, 'without_pool');
    });
    bulkManageNoPoolQtyMinus?.addEventListener('click', () => {
        if (!bulkManageNoPoolQtyInput) return;
        const val = parseInt(bulkManageNoPoolQtyInput.value, 10) || 1;
        if (val > 1) bulkManageNoPoolQtyInput.value = val - 1;
    });
    bulkManageNoPoolQtyPlus?.addEventListener('click', () => {
        if (!bulkManageNoPoolQtyInput) return;
        const val = parseInt(bulkManageNoPoolQtyInput.value, 10) || 1;
        const max = parseInt(bulkManageNoPoolQtyInput.max || '50', 10) || 50;
        if (val < max) bulkManageNoPoolQtyInput.value = val + 1;
    });

    // ==========================================
    // Unified Companion Checkout Modal System
    // ==========================================
    let currentCompanionCheckoutResId = null;
    const companionCheckoutModal = document.getElementById('reservationCompanionCheckoutModal');
    const companionCheckoutResIdEl = document.getElementById('companionCheckoutResId');
    const companionCheckoutBookerNameEl = document.getElementById('companionCheckoutBookerName');
    const companionCheckoutStatsPillsEl = document.getElementById('companionCheckoutStatsPills');
    const companionCheckoutModalBody = document.getElementById('companionCheckoutModalBody');
    const companionCheckoutSelectedSummary = document.getElementById('companionCheckoutSelectedSummary');
    const companionCheckoutSubmitBtn = document.getElementById('companionCheckoutSubmitBtn');

    const closeCompanionCheckoutModal = () => {
        const previousResId = currentCompanionCheckoutResId;
        currentCompanionCheckoutResId = null;
        if (companionCheckoutModal) {
            companionCheckoutModal.classList.remove('is-open');
            companionCheckoutModal.setAttribute('aria-hidden', 'true');
        }
        if (previousResId) {
            openReservationModal(previousResId);
        }
    };

    document.querySelectorAll('[data-close-companion-checkout-modal="true"]').forEach(btn => {
        btn.addEventListener('click', closeCompanionCheckoutModal);
    });

    const updateCompanionCheckoutSelectionSummary = () => {
        if (!companionCheckoutModalBody) return;

        // 1. Count selected single and primary guests
        const selectedSingleCheckboxes = Array.from(companionCheckoutModalBody.querySelectorAll('.companion-single-checkbox:checked'));
        const singleCount = selectedSingleCheckboxes.length;
        const hasPrimarySelected = selectedSingleCheckboxes.some(cb => cb.dataset.isPrimary === 'true');
        const namedCompanionSelectedCount = selectedSingleCheckboxes.filter(cb => cb.dataset.isPrimary !== 'true').length;

        // 2. Count selected bulk companions from stepper inputs
        let bulkCount = 0;
        companionCheckoutModalBody.querySelectorAll('.companion-bulk-stepper-input').forEach(input => {
            const val = parseInt(input.value, 10) || 0;
            if (val > 0) bulkCount += val;
        });

        const totalSelected = singleCount + bulkCount;
        if (companionCheckoutSelectedSummary) {
            const parts = [];
            if (hasPrimarySelected) parts.push('Main Guest');
            if (namedCompanionSelectedCount > 0) parts.push(`${namedCompanionSelectedCount} companion${namedCompanionSelectedCount === 1 ? '' : 's'}`);
            if (bulkCount > 0) parts.push(`${bulkCount} bulk companion${bulkCount === 1 ? '' : 's'}`);

            if (totalSelected === 0) {
                companionCheckoutSelectedSummary.innerHTML = `<span class="text-hp-text-muted">No guests selected for checkout</span>`;
            } else {
                companionCheckoutSelectedSummary.innerHTML = `<span>Selected: <strong class="text-hp-green">${parts.join(' + ')}</strong> (${totalSelected} total)</span>`;
            }
        }

        if (companionCheckoutSubmitBtn) {
            companionCheckoutSubmitBtn.disabled = (totalSelected === 0);
            companionCheckoutSubmitBtn.innerHTML = `
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                <span>Checkout Selected (${totalSelected})</span>
            `;
        }
    };

    const openCompanionCheckoutModal = (reservationId) => {
        const res = (window.staffReservationData && window.staffReservationData[reservationId]) || reservationData[reservationId];
        if (!res) {
            showToast('Reservation details not found.', 'error');
            return;
        }

        currentCompanionCheckoutResId = reservationId;
        if (companionCheckoutResIdEl) companionCheckoutResIdEl.textContent = res.id;
        if (companionCheckoutBookerNameEl) companionCheckoutBookerNameEl.textContent = res.booker_name || 'N/A';

        const guestsList = res.reservation_guests || [];
        const primaryGuest = guestsList.find(g => g.is_primary_guest);
        const companions = guestsList.filter(g => !g.is_primary_guest);

        const isBulk = (g) => {
            const fn = (g.customer?.first_name || '').toLowerCase().trim();
            return !fn || fn.startsWith('bulk') || fn.includes('companion') || fn.includes('reservation');
        };

        const singleCompanions = companions.filter(g => !isBulk(g));
        const bulkCompanions = companions.filter(g => isBulk(g));

        const activeSingle = singleCompanions.filter(g => !g.checked_out_at);
        const activeBulk = bulkCompanions.filter(g => !g.checked_out_at);
        const isPrimaryActive = primaryGuest && !primaryGuest.checked_out_at;

        const totalActiveGuests = guestsList.filter(g => !g.checked_out_at).length;
        const totalGuestsCount = guestsList.length;

        const totalPool = guestsList.filter(g => Boolean(g.has_pool_access)).length;
        const activePool = guestsList.filter(g => Boolean(g.has_pool_access) && !g.checked_out_at).length;
        const totalStandard = guestsList.filter(g => !g.has_pool_access).length;
        const activeStandard = guestsList.filter(g => !g.has_pool_access && !g.checked_out_at).length;

        // Render Stats Pills
        if (companionCheckoutStatsPillsEl) {
            companionCheckoutStatsPillsEl.innerHTML = `
                <span class="inline-flex items-center gap-1 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-700 dark:text-emerald-300">
                    <i class="bi bi-people-fill"></i> ${totalActiveGuests} / ${totalGuestsCount} Active Inside
                </span>
                <span class="inline-flex items-center gap-1 rounded-full border border-sky-500/30 bg-sky-500/10 px-3 py-1 text-xs font-bold text-sky-700 dark:text-sky-300">
                    <i class="bi bi-water"></i> ${activePool} / ${totalPool} Pool Pass
                </span>
                <span class="inline-flex items-center gap-1 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-bold text-amber-800 dark:text-amber-300">
                    <i class="bi bi-person-fill"></i> ${activeStandard} / ${totalStandard} Standard
                </span>
            `;
        }

        if (totalGuestsCount === 0) {
            companionCheckoutModalBody.innerHTML = `
                <div class="rounded-2xl border border-glass-border bg-glass p-8 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-white/10">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                    </div>
                    <h4 class="m-0 text-base font-bold text-hp-text">No Guests Found</h4>
                    <p class="m-0 mt-1 text-xs text-hp-text-muted">No guest records are registered under this reservation.</p>
                </div>
            `;
            updateCompanionCheckoutSelectionSummary();
            companionCheckoutModal.classList.add('is-open');
            companionCheckoutModal.setAttribute('aria-hidden', 'false');
            return;
        }

        let html = '';

        // Select All / Deselect All Bar
        if (totalActiveGuests > 0) {
            html += `
                <div class="flex items-center justify-between gap-3 rounded-xl border border-glass-border bg-glass p-3">
                    <div class="text-xs font-semibold text-hp-text">
                        <span>Select guests to check out:</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" id="companionCheckoutSelectAllBtn" class="cursor-pointer rounded-lg bg-hp-green/10 border border-hp-green/30 text-hp-green hover:bg-hp-green hover:text-white px-2.5 py-1 text-xs font-bold transition-all">
                            Select All Active
                        </button>
                        <button type="button" id="companionCheckoutDeselectAllBtn" class="cursor-pointer rounded-lg border border-glass-border bg-glass hover:bg-glass-hover text-hp-text px-2.5 py-1 text-xs font-semibold transition-all">
                            Clear Selection
                        </button>
                    </div>
                </div>
            `;
        }

        // Section 1: Main Guest (Primary Booker)
        if (primaryGuest) {
            const c = primaryGuest.customer || {};
            const fullName = [c.first_name, c.middle_name, c.last_name].filter(Boolean).join(' ').trim() || res.booker_name || 'Primary Booker';
            const isCheckedOut = Boolean(primaryGuest.checked_out_at);
            const hasPool = Boolean(primaryGuest.has_pool_access);

            html += `
                <div class="rounded-2xl border border-glass-border bg-glass p-4">
                    <div class="flex items-center justify-between gap-2 mb-3 pb-2 border-b border-glass-border/40">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-hp-text">Main / Primary Guest</span>
                            <span class="rounded-full ${isCheckedOut ? 'bg-slate-500/15 text-slate-600 dark:text-slate-300' : 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300'} text-[0.7rem] font-bold px-2 py-0.5">${isCheckedOut ? 'Checked Out' : 'Active Inside'}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-3 p-3 rounded-xl border ${isCheckedOut ? 'border-glass-border/30 bg-black/[0.02] dark:bg-white/[0.02] opacity-60' : 'border-emerald-500/30 bg-emerald-500/5 hover:border-emerald-500/50 transition-all'}">
                        <div class="flex items-center gap-3 min-w-0">
                            ${!isCheckedOut ? `
                                <input type="checkbox" class="companion-single-checkbox h-4 w-4 rounded accent-hp-green cursor-pointer" data-rg-id="${primaryGuest.id || primaryGuest.customer_id}" data-is-primary="true" data-guest-name="${escapeHtml(fullName)}" checked>
                            ` : `
                                <span class="h-4 w-4 inline-flex items-center justify-center text-slate-400 font-bold"><i class="bi bi-check-lg"></i></span>
                            `}
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-600 to-emerald-800 text-white text-xs font-bold shadow-2xs">
                                <i class="bi bi-star-fill"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-xs font-bold text-hp-text truncate">${escapeHtml(fullName)}</span>
                                    <span class="inline-flex items-center gap-0.5 rounded bg-emerald-500/20 text-emerald-800 dark:text-emerald-200 border border-emerald-500/40 px-1.5 py-0.2 text-[0.62rem] font-bold">Main Booker</span>
                                    ${hasPool ? `
                                        <span class="inline-flex items-center gap-0.5 rounded bg-sky-500/15 border border-sky-500/30 px-1.5 py-0.2 text-[0.62rem] font-bold text-sky-700 dark:text-sky-300">
                                            <i class="bi bi-water"></i> Pool Pass
                                        </span>
                                    ` : `
                                        <span class="inline-flex items-center gap-0.5 rounded bg-amber-500/15 border border-amber-500/30 px-1.5 py-0.2 text-[0.62rem] font-bold text-amber-800 dark:text-amber-300">
                                            <i class="bi bi-person-fill"></i> Standard
                                        </span>
                                    `}
                                </div>
                                <div class="text-[0.72rem] text-hp-text-muted mt-0.5">
                                    ${escapeHtml(c.phone || res.phone || 'No phone')} &bull; ${escapeHtml(c.email || res.email || 'No email')}
                                </div>
                            </div>
                        </div>
                        <div>
                            ${!isCheckedOut ? `
                                <button type="button" class="companion-single-quick-checkout-btn cursor-pointer rounded-lg bg-hp-green/10 border border-hp-green/30 text-hp-green hover:bg-hp-green hover:text-white px-2.5 py-1 text-xs font-bold transition-all shadow-2xs" data-rg-id="${primaryGuest.id || primaryGuest.customer_id}" data-guest-name="${escapeHtml(fullName)}">
                                    Check Out
                                </button>
                            ` : `
                                <span class="text-[0.7rem] font-semibold text-slate-500">Checked Out</span>
                            `}
                        </div>
                    </div>
                </div>
            `;
        }

        // Section 2: Single / Named Companions
        if (singleCompanions.length > 0) {
            html += `
                <div class="rounded-2xl border border-glass-border bg-glass p-4">
                    <div class="flex items-center justify-between gap-2 mb-3 pb-2 border-b border-glass-border/40">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-hp-text">Single / Named Companions</span>
                            <span class="rounded-full bg-hp-green/15 text-hp-green text-[0.7rem] font-bold px-2 py-0.5">${activeSingle.length} Active / ${singleCompanions.length} Total</span>
                        </div>
                    </div>
                    <div class="space-y-2.5">
            `;

            singleCompanions.forEach(g => {
                const c = g.customer || {};
                const fullName = [c.first_name, c.middle_name, c.last_name].filter(Boolean).join(' ').trim() || 'Companion';
                const isCheckedOut = Boolean(g.checked_out_at);
                const hasPool = Boolean(g.has_pool_access);

                html += `
                    <div class="flex items-center justify-between gap-3 p-3 rounded-xl border ${isCheckedOut ? 'border-glass-border/30 bg-black/[0.02] dark:bg-white/[0.02] opacity-60' : 'border-glass-border bg-hp-cream/60 dark:bg-white/5 hover:border-hp-green/40 transition-all'}">
                        <div class="flex items-center gap-3 min-w-0">
                            ${!isCheckedOut ? `
                                <input type="checkbox" class="companion-single-checkbox h-4 w-4 rounded accent-hp-green cursor-pointer" data-rg-id="${g.id || g.customer_id}" data-guest-name="${escapeHtml(fullName)}" checked>
                            ` : `
                                <span class="h-4 w-4 inline-flex items-center justify-center text-slate-400"><i class="bi bi-check-lg"></i></span>
                            `}
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full ${hasPool ? 'bg-sky-600 text-white' : 'bg-[#2f6f45] text-white'} text-xs font-bold shadow-2xs">
                                ${escapeHtml(c.first_name ? c.first_name[0] : 'C')}
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-xs font-bold text-hp-text truncate">${escapeHtml(fullName)}</span>
                                    ${hasPool ? `
                                        <span class="inline-flex items-center gap-0.5 rounded bg-sky-500/15 border border-sky-500/30 px-1.5 py-0.2 text-[0.62rem] font-bold text-sky-700 dark:text-sky-300">
                                            <i class="bi bi-water"></i> Pool Pass
                                        </span>
                                    ` : `
                                        <span class="inline-flex items-center gap-0.5 rounded bg-amber-500/15 border border-amber-500/30 px-1.5 py-0.2 text-[0.62rem] font-bold text-amber-800 dark:text-amber-300">
                                            <i class="bi bi-person-fill"></i> Standard
                                        </span>
                                    `}
                                </div>
                                <div class="text-[0.72rem] text-hp-text-muted mt-0.5">
                                    ${escapeHtml(c.gender || 'N/A')} &bull; Age ${escapeHtml(c.age || 'N/A')} &bull; ${c.is_foreigner ? 'Foreigner' : 'Filipino'}
                                </div>
                            </div>
                        </div>
                        <div>
                            ${!isCheckedOut ? `
                                <button type="button" class="companion-single-quick-checkout-btn cursor-pointer rounded-lg bg-hp-green/10 border border-hp-green/30 text-hp-green hover:bg-hp-green hover:text-white px-2.5 py-1 text-xs font-bold transition-all shadow-2xs" data-rg-id="${g.id || g.customer_id}" data-guest-name="${escapeHtml(fullName)}">
                                    Check Out
                                </button>
                            ` : `
                                <span class="text-[0.7rem] font-semibold text-slate-500">Checked Out</span>
                            `}
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        }

        // Section 3: Bulk Companion Groups
        if (bulkCompanions.length > 0) {
            const bulkGroupMap = {};
            bulkCompanions.forEach(g => {
                const c = g.customer || {};
                const gender = c.gender || 'Unknown';
                const ageGroup = ageGroupLabel(c.age);
                const nationality = c.is_foreigner ? 'Foreigner' : 'Filipino';
                const key = `${gender}|${ageGroup}|${nationality}`;
                if (!bulkGroupMap[key]) {
                    bulkGroupMap[key] = { gender, ageGroup, nationality, is_foreigner: Boolean(c.is_foreigner), members: [] };
                }
                bulkGroupMap[key].members.push(g);
            });

            const groupEntries = Object.entries(bulkGroupMap);

            html += `
                <div class="rounded-2xl border border-glass-border bg-glass p-4">
                    <div class="flex items-center justify-between gap-2 mb-3 pb-2 border-b border-glass-border/40">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-hp-text">Bulk Companion Groups</span>
                            <span class="rounded-full bg-sky-500/15 text-sky-700 dark:text-sky-300 text-[0.7rem] font-bold px-2 py-0.5">${activeBulk.length} Active / ${bulkCompanions.length} Total</span>
                        </div>
                    </div>
                    <div class="space-y-3">
            `;

            groupEntries.forEach(([key, group], idx) => {
                const totalInGroup = group.members.length;
                const activeInGroup = group.members.filter(g => !g.checked_out_at).length;
                const totalPoolInGroup = group.members.filter(g => Boolean(g.has_pool_access)).length;
                const activePoolInGroup = group.members.filter(g => Boolean(g.has_pool_access) && !g.checked_out_at).length;
                const totalNoPoolInGroup = group.members.filter(g => !g.has_pool_access).length;
                const activeNoPoolInGroup = group.members.filter(g => !g.has_pool_access && !g.checked_out_at).length;

                const isAllCheckedOut = activeInGroup === 0;

                html += `
                    <div class="rounded-xl border ${isAllCheckedOut ? 'border-glass-border/30 bg-black/[0.02] dark:bg-white/[0.02] opacity-60' : 'border-glass-border bg-hp-cream/60 dark:bg-white/5'} p-3.5" data-bulk-card-key="${escapeHtml(key)}">
                        <div class="flex items-center justify-between gap-2 flex-wrap mb-2">
                            <div class="flex items-center gap-2">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#0e7490] to-[#155e75] text-white text-xs shadow-2xs font-bold">
                                    <i class="bi bi-people-fill"></i>
                                </span>
                                <div>
                                    <div class="text-xs font-bold text-hp-text">${escapeHtml(group.gender)} &bull; ${escapeHtml(group.ageGroup)} &bull; ${escapeHtml(group.nationality)}</div>
                                    <div class="text-[0.7rem] text-hp-text-muted">${activeInGroup} / ${totalInGroup} inside</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                ${totalPoolInGroup > 0 ? `
                                    <span class="rounded bg-sky-500/15 border border-sky-500/30 px-2 py-0.5 text-[0.68rem] font-bold text-sky-700 dark:text-sky-300">
                                        <i class="bi bi-water"></i> ${activePoolInGroup}/${totalPoolInGroup} Pool
                                    </span>
                                ` : ''}
                                ${totalNoPoolInGroup > 0 ? `
                                    <span class="rounded bg-amber-500/15 border border-amber-500/30 px-2 py-0.5 text-[0.68rem] font-bold text-amber-800 dark:text-amber-300">
                                        <i class="bi bi-person-fill"></i> ${activeNoPoolInGroup}/${totalNoPoolInGroup} Standard
                                    </span>
                                ` : ''}
                            </div>
                        </div>
                `;

                if (isAllCheckedOut) {
                    html += `
                        <div class="text-xs text-hp-text-muted italic py-1 text-center">All companions in this group are checked out.</div>
                    `;
                } else {
                    html += `
                        <div class="mt-3 pt-3 border-t border-glass-border/40 space-y-2.5">
                    `;

                    // If group has active pool members
                    if (activePoolInGroup > 0) {
                        html += `
                            <div class="flex items-center justify-between gap-3 p-2.5 rounded-lg border border-sky-500/20 bg-sky-500/5 dark:bg-sky-950/20">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-sky-700 dark:text-sky-300"><i class="bi bi-water me-1"></i>With Pool Pass</div>
                                    <div class="text-[0.7rem] text-hp-text-muted">${activePoolInGroup} available to check out</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1">
                                        <button type="button" class="bulk-stepper-btn-minus h-7 w-7 flex items-center justify-center rounded-lg border border-sky-500/30 bg-glass text-xs font-bold text-hp-text hover:bg-sky-500/20 cursor-pointer transition-colors" data-target-input="bulk_pool_${idx}">−</button>
                                        <input type="number" id="bulk_pool_${idx}" class="companion-bulk-stepper-input h-7 w-12 border border-sky-500/30 rounded-lg bg-glass text-center text-xs font-bold text-hp-text" value="0" min="0" max="${activePoolInGroup}" data-gender="${escapeHtml(group.gender)}" data-age-group="${escapeHtml(group.ageGroup)}" data-is-foreigner="${group.is_foreigner ? '1' : '0'}" data-pool-type="with_pool" data-max="${activePoolInGroup}">
                                        <button type="button" class="bulk-stepper-btn-plus h-7 w-7 flex items-center justify-center rounded-lg border border-sky-500/30 bg-glass text-xs font-bold text-hp-text hover:bg-sky-500/20 cursor-pointer transition-colors" data-target-input="bulk_pool_${idx}">+</button>
                                    </div>
                                    <button type="button" class="bulk-max-btn rounded-lg bg-sky-500/10 border border-sky-500/30 text-sky-700 dark:text-sky-300 px-2 py-1 text-[0.7rem] font-bold hover:bg-sky-500/20 transition-all cursor-pointer" data-target-input="bulk_pool_${idx}" data-max="${activePoolInGroup}">
                                        All (${activePoolInGroup})
                                    </button>
                                </div>
                            </div>
                        `;
                    }

                    // If group has active standard (no pool) members
                    if (activeNoPoolInGroup > 0) {
                        html += `
                            <div class="flex items-center justify-between gap-3 p-2.5 rounded-lg border border-amber-500/20 bg-amber-500/5 dark:bg-amber-950/20">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-amber-800 dark:text-amber-300"><i class="bi bi-person-fill me-1"></i>Standard (No Pool)</div>
                                    <div class="text-[0.7rem] text-hp-text-muted">${activeNoPoolInGroup} available to check out</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1">
                                        <button type="button" class="bulk-stepper-btn-minus h-7 w-7 flex items-center justify-center rounded-lg border border-amber-500/30 bg-glass text-xs font-bold text-hp-text hover:bg-amber-500/20 cursor-pointer transition-colors" data-target-input="bulk_nopool_${idx}">−</button>
                                        <input type="number" id="bulk_nopool_${idx}" class="companion-bulk-stepper-input h-7 w-12 border border-amber-500/30 rounded-lg bg-glass text-center text-xs font-bold text-hp-text" value="0" min="0" max="${activeNoPoolInGroup}" data-gender="${escapeHtml(group.gender)}" data-age-group="${escapeHtml(group.ageGroup)}" data-is-foreigner="${group.is_foreigner ? '1' : '0'}" data-pool-type="without_pool" data-max="${activeNoPoolInGroup}">
                                        <button type="button" class="bulk-stepper-btn-plus h-7 w-7 flex items-center justify-center rounded-lg border border-amber-500/30 bg-glass text-xs font-bold text-hp-text hover:bg-amber-500/20 cursor-pointer transition-colors" data-target-input="bulk_nopool_${idx}">+</button>
                                    </div>
                                    <button type="button" class="bulk-max-btn rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 px-2 py-1 text-[0.7rem] font-bold hover:bg-amber-500/20 transition-all cursor-pointer" data-target-input="bulk_nopool_${idx}" data-max="${activeNoPoolInGroup}">
                                        All (${activeNoPoolInGroup})
                                    </button>
                                </div>
                            </div>
                        `;
                    }

                    html += `
                        </div>
                    `;
                }

                html += `
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        }

        companionCheckoutModalBody.innerHTML = html;

        // Bind Steppers & Select All Listeners inside Modal Body
        const selectAllBtn = document.getElementById('companionCheckoutSelectAllBtn');
        const deselectAllBtn = document.getElementById('companionCheckoutDeselectAllBtn');

        selectAllBtn?.addEventListener('click', () => {
            // Check all single and primary checkboxes
            companionCheckoutModalBody.querySelectorAll('.companion-single-checkbox').forEach(cb => {
                cb.checked = true;
            });
            // Set all bulk steppers to their max
            companionCheckoutModalBody.querySelectorAll('.companion-bulk-stepper-input').forEach(input => {
                const max = parseInt(input.dataset.max, 10) || 0;
                input.value = max;
            });
            updateCompanionCheckoutSelectionSummary();
        });

        deselectAllBtn?.addEventListener('click', () => {
            companionCheckoutModalBody.querySelectorAll('.companion-single-checkbox').forEach(cb => {
                cb.checked = false;
            });
            companionCheckoutModalBody.querySelectorAll('.companion-bulk-stepper-input').forEach(input => {
                input.value = 0;
            });
            updateCompanionCheckoutSelectionSummary();
        });

        // Single checkbox change
        companionCheckoutModalBody.querySelectorAll('.companion-single-checkbox').forEach(cb => {
            cb.addEventListener('change', updateCompanionCheckoutSelectionSummary);
        });

        // Bulk stepper buttons
        companionCheckoutModalBody.querySelectorAll('.bulk-stepper-btn-minus').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.targetInput);
                if (!input) return;
                const val = parseInt(input.value, 10) || 0;
                if (val > 0) input.value = val - 1;
                updateCompanionCheckoutSelectionSummary();
            });
        });

        companionCheckoutModalBody.querySelectorAll('.bulk-stepper-btn-plus').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.targetInput);
                if (!input) return;
                const val = parseInt(input.value, 10) || 0;
                const max = parseInt(input.dataset.max, 10) || 0;
                if (val < max) input.value = val + 1;
                updateCompanionCheckoutSelectionSummary();
            });
        });

        companionCheckoutModalBody.querySelectorAll('.bulk-max-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.targetInput);
                if (!input) return;
                const max = parseInt(btn.dataset.max, 10) || 0;
                input.value = (parseInt(input.value, 10) === max) ? 0 : max;
                updateCompanionCheckoutSelectionSummary();
            });
        });

        companionCheckoutModalBody.querySelectorAll('.companion-bulk-stepper-input').forEach(input => {
            input.addEventListener('input', () => {
                let val = parseInt(input.value, 10) || 0;
                const max = parseInt(input.dataset.max, 10) || 0;
                if (val < 0) val = 0;
                if (val > max) val = max;
                input.value = val;
                updateCompanionCheckoutSelectionSummary();
            });
        });

        // Quick individual checkout for single / primary guests
        companionCheckoutModalBody.querySelectorAll('.companion-single-quick-checkout-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const rgId = btn.dataset.rgId;
                const guestName = btn.dataset.guestName || 'Guest';
                if (!confirm(`Check out ${guestName} now?`)) return;

                btn.disabled = true;
                btn.textContent = 'Checking out...';

                try {
                    const response = await fetch(`/staff/reservation-guests/${rgId}/check-out`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(payload.message || 'Unable to check out guest.');
                    }

                    // Update local state for this guest
                    if (currentCompanionCheckoutResId && window.staffReservationData && window.staffReservationData[currentCompanionCheckoutResId]) {
                        const res = window.staffReservationData[currentCompanionCheckoutResId];
                        const targetGuest = (res.reservation_guests || []).find(g => String(g.id) === String(rgId));
                        if (targetGuest) targetGuest.checked_out_at = new Date().toISOString();
                    }

                    // Remove guest row from active guest table if present
                    const guestRow = document.querySelector(`tr.guest-row[data-guest-id="${rgId}"]`);
                    if (guestRow) guestRow.remove();

                    // Re-render open modal if active
                    if (currentReservationId === currentCompanionCheckoutResId) {
                        openReservationModal(currentCompanionCheckoutResId);
                    }

                    showToast(`${guestName} checked out successfully.`);
                } catch (err) {
                    showToast(err.message || 'Error checking out guest.', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Check Out';
                }
            });
        });

        // Initialize selection summary
        updateCompanionCheckoutSelectionSummary();

        // Close details modal if open and show group checkout modal
        if (reservationModal) {
            reservationModal.classList.remove('is-open');
            reservationModal.setAttribute('aria-hidden', 'true');
        }
        if (companionCheckoutModal) {
            companionCheckoutModal.classList.add('is-open');
            companionCheckoutModal.setAttribute('aria-hidden', 'false');
        }
    };

    // Primary Action: Checkout Selected Guests (Primary + Companions)
    companionCheckoutSubmitBtn?.addEventListener('click', async () => {
        if (!currentCompanionCheckoutResId || !companionCheckoutModalBody) return;

        // 1. Gather selected single and primary guest IDs
        const selectedSingleCheckboxes = Array.from(companionCheckoutModalBody.querySelectorAll('.companion-single-checkbox:checked'));
        const singleRgIds = selectedSingleCheckboxes.map(cb => cb.dataset.rgId).filter(Boolean);

        // 2. Gather bulk companion tasks
        const bulkTasks = [];
        let totalBulkCount = 0;

        companionCheckoutModalBody.querySelectorAll('.companion-bulk-stepper-input').forEach(input => {
            const qty = parseInt(input.value, 10) || 0;
            if (qty > 0) {
                totalBulkCount += qty;
                bulkTasks.push({
                    count: qty,
                    pool_access_type: input.dataset.poolType || 'any',
                    gender: input.dataset.gender || null,
                    age_group: input.dataset.ageGroup || null,
                    is_foreigner: input.dataset.isForeigner === '1' ? true : (input.dataset.isForeigner === '0' ? false : null),
                });
            }
        });

        const totalSelected = singleRgIds.length + totalBulkCount;
        if (totalSelected === 0) {
            showToast('Please select at least one guest or companion to check out.', 'error');
            return;
        }

        if (!confirm(`Are you sure you want to check out ${totalSelected} selected guest${totalSelected === 1 ? '' : 's'}?`)) {
            return;
        }

        const originalBtnHtml = companionCheckoutSubmitBtn.innerHTML;
        companionCheckoutSubmitBtn.disabled = true;
        companionCheckoutSubmitBtn.innerHTML = `
            <div class="h-4 w-4 rounded-full border-2 border-white/30 border-t-white animate-spin"></div>
            <span>Checking out ${totalSelected} guest(s)...</span>
        `;

        try {
            const checkoutPromises = [];

            // Single and Primary guest checkouts
            singleRgIds.forEach(rgId => {
                checkoutPromises.push(
                    fetch(`/staff/reservation-guests/${rgId}/check-out`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }).then(async r => {
                        const data = await r.json().catch(() => ({}));
                        if (!r.ok) throw new Error(data.message || `Failed to check out guest #${rgId}`);
                        return data;
                    })
                );
            });

            // Bulk companion group checkouts
            bulkTasks.forEach(task => {
                checkoutPromises.push(
                    fetch(`/staff/reservations/${currentCompanionCheckoutResId}/bulk-companions/check-out`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(task),
                    }).then(async r => {
                        const data = await r.json().catch(() => ({}));
                        if (!r.ok) throw new Error(data.message || `Failed to check out bulk companions`);
                        return data;
                    })
                );
            });

            const results = await Promise.allSettled(checkoutPromises);
            const failures = results.filter(r => r.status === 'rejected');

            if (failures.length > 0) {
                const errorMsg = failures.map(f => f.reason?.message || 'Error').join('; ');
                throw new Error(errorMsg || 'Some checkouts failed.');
            }

            if (companionCheckoutModal) {
                companionCheckoutModal.classList.remove('is-open');
                companionCheckoutModal.setAttribute('aria-hidden', 'true');
            }

            // Update in-memory guests
            if (currentCompanionCheckoutResId && window.staffReservationData && window.staffReservationData[currentCompanionCheckoutResId]) {
                const res = window.staffReservationData[currentCompanionCheckoutResId];
                singleRgIds.forEach(id => {
                    const g = (res.reservation_guests || []).find(rg => String(rg.id) === String(id));
                    if (g) g.checked_out_at = new Date().toISOString();
                });
                let bulkDone = 0;
                (res.reservation_guests || []).forEach(g => {
                    if (!g.is_primary_guest && !g.checked_out_at && bulkDone < totalBulkCount) {
                        const isBulkGuest = !g.customer || !g.customer.first_name || g.customer.first_name.toLowerCase().includes('companion');
                        if (isBulkGuest) {
                            g.checked_out_at = new Date().toISOString();
                            bulkDone++;
                        }
                    }
                });
            }

            // Remove selected single guest rows from table
            singleRgIds.forEach(id => {
                const gRow = document.querySelector(`tr.guest-row[data-guest-id="${id}"]`);
                if (gRow) gRow.remove();
            });

            // Re-open reservation details modal with updated guests
            if (currentCompanionCheckoutResId) {
                openReservationModal(currentCompanionCheckoutResId);
            }

            showToast(`Successfully checked out ${totalSelected} guest(s).`);
        } catch (err) {
            console.error('Group checkout error:', err);
            showToast(err.message || 'Error occurred during group checkout.', 'error');
            companionCheckoutSubmitBtn.disabled = false;
            companionCheckoutSubmitBtn.innerHTML = originalBtnHtml;
        }
    });

    guestRows.forEach(row => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('.btn-expand-row')) return;
            if (row.dataset.bulkGroup === 'true') {
                openBulkManageModal(
                    row.dataset.reservationId,
                    row.dataset.bulkActive,
                    row.dataset.bulkTotal,
                    row.dataset.bulkDemo,
                    row.dataset.bulkGender,
                    row.dataset.bulkAgeGroup,
                    row.dataset.bulkNationality,
                    row.dataset.bulkActivePool,
                    row.dataset.bulkTotalPool,
                    row.dataset.bulkActiveNoPool,
                    row.dataset.bulkTotalNoPool
                );
                return;
            }
            const customerId = row.dataset.customerId;
            openGuestModal(customerId);
        });
    });

    // Expandable Row Logic
    document.querySelectorAll('.btn-expand-row').forEach(expandBtn => {
        expandBtn.addEventListener('click', (e) => {
            e.stopPropagation();

            const tr = expandBtn.closest('tr');
            if (!tr) return;

            const isExpanded = expandBtn.classList.toggle('expanded');
            expandBtn.style.transform = isExpanded ? 'rotate(180deg)' : '';                // Guest Table Expand — toggle every companion under the main
            // guest together (single AND bulk groups).
            if (tr.classList.contains('guest-row--primary')) {
                tr.classList.toggle('is-expanded', isExpanded);
                const resId = tr.getAttribute('data-reservation-id');
                const companions = document.querySelectorAll(`.guest-row--companion[data-reservation-id="${resId}"]`);
                companions.forEach(c => {
                    c.style.display = isExpanded ? '' : 'none';
                });
            }

            // Reservation Table Expand
            if (tr.classList.contains('reservation-row')) {
                const resId = tr.getAttribute('data-reservation-id');
                let nestedRow = tr.nextElementSibling;

                if (isExpanded) {
                    if (!nestedRow || !nestedRow.classList.contains('reservation-nested-row')) {
                        // Generate it!
                        const reservation = reservationData[resId];
                        if (!reservation) return;

                        let guestsHtml = `<div style="padding: 1rem; background: rgba(0,0,0,0.02); border-radius: 0.5rem; display: flex; flex-direction: column; gap: 0.5rem; margin: 0.5rem 1rem;">`;
                        let bulkGuests = [];
                        let normalGuests = [];

                        reservation.reservation_guests.forEach(g => {
                            if (!g.customer) return;
                            const fn = (g.customer.first_name || '').toLowerCase();
                            if (fn.startsWith('bulk') || fn.includes('companion')) {
                                bulkGuests.push(g);
                            } else {
                                normalGuests.push(g);
                            }
                        });

                        normalGuests.forEach(g => {
                            if (g.checked_out_at) return;
                            const pill = g.is_primary_guest
                                ? `<span style="font-size: 0.65rem; background: var(--hp-gold); color: #fff; padding: 2px 6px; border-radius: 12px; margin-left: 8px;">MAIN</span>`
                                : `<span style="font-size: 0.65rem; background: var(--hp-green); color: #fff; padding: 2px 6px; border-radius: 12px; margin-left: 8px;">COMPANION</span>`;

                            const gHasPool = Boolean(g.has_pool_access);
                            const gHasAmenity = Boolean(reservation.reservation_amenities && reservation.reservation_amenities.length > 0);
                            const gGlowClass = gHasPool && gHasAmenity ? 'guest-avatar-glow--both' : (gHasPool ? 'guest-avatar-glow--pool' : (gHasAmenity ? 'guest-avatar-glow--amenity' : ''));
                            let poolBadge = '';
                            if (gHasPool && gHasAmenity) {
                                poolBadge = `<span style="font-size: 0.62rem; background: rgba(14,165,233,0.15); border: 1px solid rgba(14,165,233,0.3); color: #0369a1; padding: 1px 6px; border-radius: 8px; font-weight: 700; margin-left: 6px;"><i class="bi bi-water me-1"></i>Pool + <i class="bi bi-house-door-fill"></i></span>`;
                            } else if (gHasPool) {
                                poolBadge = `<span style="font-size: 0.62rem; background: rgba(14,165,233,0.15); border: 1px solid rgba(14,165,233,0.3); color: #0369a1; padding: 1px 6px; border-radius: 8px; font-weight: 700; margin-left: 6px;"><i class="bi bi-water me-1"></i>Pool</span>`;
                            } else if (gHasAmenity) {
                                poolBadge = `<span style="font-size: 0.62rem; background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.3); color: #b45309; padding: 1px 6px; border-radius: 8px; font-weight: 700; margin-left: 6px;"><i class="bi bi-house-door-fill me-1"></i>Amenity</span>`;
                            }

                            // Clicking a guest (main or single companion) opens
                            // the same detail modal as the guest table.
                            guestsHtml += `<div data-guest-id="${g.customer_id || ''}" title="View details" style="display: flex; align-items: center; font-size: 0.85rem; font-weight: 500; padding: 6px 8px; cursor: pointer; border-radius: 8px; transition: background 0.15s ease;" class="hover:bg-black/5 dark:hover:bg-white/5">
                                <span class="nested-guest-avatar ${gGlowClass}" style="width: 1.5rem; height: 1.5rem; border-radius: 50%; margin-right: 0.65rem; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; background: ${g.is_primary_guest ? 'linear-gradient(135deg, #178a52, #0e5c37)' : 'linear-gradient(135deg, #2f6f45, #178a52)'}; color: #fff; font-size: 0.6rem; font-weight: bold;">
                                    ${g.is_primary_guest ? '<i class="bi bi-star-fill"></i>' : '•'}
                                </span>
                                <span>${g.customer.first_name} ${g.customer.middle_name || ''} ${g.customer.last_name}</span>
                                ${pill}
                                ${poolBadge}
                                <span style="color: var(--hp-text-muted); font-size: 0.75rem; margin-left: auto;">${g.customer.gender || 'Unknown'} • ${g.customer.age || 'N/A'} yrs</span>
                             </div>`;
                        });

                        if (bulkGuests.length > 0) {
                            // ONE trigger per bulk group (same reservation,
                            // gender, age group and nationality) — groups are
                            // never merged together.
                            const bulkGroupMap = {};
                            bulkGuests.forEach(g => {
                                const c = g.customer || {};
                                const gender = c.gender || 'Unknown';
                                const ageGroup = ageGroupLabel(c.age);
                                const nationality = c.is_foreigner ? 'Foreigner' : 'Filipino';
                                const key = `${gender}|${ageGroup}|${nationality}`;
                                if (!bulkGroupMap[key]) {
                                    bulkGroupMap[key] = { gender, ageGroup, nationality, members: [] };
                                }
                                bulkGroupMap[key].members.push(g);
                            });

                            Object.values(bulkGroupMap).forEach(group => {
                                const totalBulk = group.members.length;
                                const activeBulk = group.members.filter(g => !g.checked_out_at).length;
                                const totalPool = group.members.filter(g => Boolean(g.has_pool_access)).length;
                                const activePool = group.members.filter(g => Boolean(g.has_pool_access) && !g.checked_out_at).length;
                                const totalNoPool = group.members.filter(g => !g.has_pool_access).length;
                                const activeNoPool = group.members.filter(g => !g.has_pool_access && !g.checked_out_at).length;

                                // Fully checked-out groups disappear from the
                                // dropdown — never show an empty group.
                                if (activeBulk === 0) return;

                                const groupHasPool = totalPool > 0;
                                const groupHasAmenity = Boolean(reservation.reservation_amenities && reservation.reservation_amenities.length > 0);
                                const groupGlowClass = groupHasPool && groupHasAmenity ? 'guest-avatar-glow--both' : (groupHasPool ? 'guest-avatar-glow--pool' : (groupHasAmenity ? 'guest-avatar-glow--amenity' : ''));

                                let poolBadge = '';
                                if (groupHasPool && groupHasAmenity) {
                                    poolBadge = `<span style="font-size: 0.62rem; background: rgba(14,165,233,0.15); border: 1px solid rgba(14,165,233,0.3); color: #0369a1; padding: 1px 6px; border-radius: 8px; font-weight: 700; margin-left: 6px;"><i class="bi bi-water me-1"></i>Pool + <i class="bi bi-house-door-fill"></i></span>`;
                                } else if (groupHasPool) {
                                    poolBadge = `<span style="font-size: 0.62rem; background: rgba(14,165,233,0.15); border: 1px solid rgba(14,165,233,0.3); color: #0369a1; padding: 1px 6px; border-radius: 8px; font-weight: 700; margin-left: 6px;"><i class="bi bi-water me-1"></i>Pool (${activePool}/${totalPool})</span>`;
                                } else if (groupHasAmenity) {
                                    poolBadge = `<span style="font-size: 0.62rem; background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.3); color: #b45309; padding: 1px 6px; border-radius: 8px; font-weight: 700; margin-left: 6px;"><i class="bi bi-house-door-fill me-1"></i>Amenity</span>`;
                                }

                                const demo = `${group.gender} · ${group.ageGroup} · ${group.nationality}`;
                                guestsHtml += `<div class="bulk-group-row-trigger hover:bg-black/5 dark:hover:bg-white/5"
                                    data-res-id="${resId}"
                                    data-bulk-active="${activeBulk}"
                                    data-bulk-total="${totalBulk}"
                                    data-bulk-active-pool="${activePool}"
                                    data-bulk-total-pool="${totalPool}"
                                    data-bulk-active-no-pool="${activeNoPool}"
                                    data-bulk-total-no-pool="${totalNoPool}"
                                    data-bulk-demo="${demo}"
                                    data-bulk-gender="${group.gender}"
                                    data-bulk-age-group="${group.ageGroup}"
                                    data-bulk-nationality="${group.nationality}"
                                    style="display: flex; align-items: center; font-size: 0.85rem; font-weight: 500; cursor: pointer; padding: 6px 8px; border-top: 1px solid rgba(0,0,0,0.05); margin-top: 4px; border-radius: 8px; transition: background 0.15s ease; color: var(--hp-green);"
                                >
                                    <span class="nested-guest-avatar ${groupGlowClass}" style="width: 1.5rem; height: 1.5rem; border-radius: 50%; margin-right: 0.65rem; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0e7490, #155e75); color: #fff; font-size: 0.6rem; font-weight: bold;">
                                        <i class="bi bi-people-fill"></i>
                                    </span>
                                    <span>Bulk Companions (#${resId})</span>
                                    <span style="font-size: 0.65rem; background: #0e7490; color: #fff; padding: 2px 6px; border-radius: 12px; margin-left: 8px;">${activeBulk}/${totalBulk} Checked In</span>
                                    ${poolBadge}
                                    <span style="color: var(--hp-text-muted); font-size: 0.75rem; margin-left: auto;">${demo}</span>
                                 </div>`;
                            });
                        }
                        guestsHtml += `</div>`;

                        nestedRow = document.createElement('tr');
                        nestedRow.className = 'reservation-nested-row';
                        nestedRow.innerHTML = `<td colspan="5" style="padding: 0;">${guestsHtml}</td>`;
                        tr.insertAdjacentElement('afterend', nestedRow);
                    }
                    nestedRow.style.display = '';
                } else {
                    if (nestedRow && nestedRow.classList.contains('reservation-nested-row')) {
                        nestedRow.style.display = 'none';
                    }
                }
            }
        });
    });

    // Delegated click for dynamic elements
    document.addEventListener('click', (e) => {
        const bulkTrigger = e.target.closest('.bulk-group-row-trigger');
        if (bulkTrigger) {
            openBulkManageModal(
                bulkTrigger.dataset.resId,
                bulkTrigger.dataset.bulkActive,
                bulkTrigger.dataset.bulkTotal,
                bulkTrigger.dataset.bulkDemo,
                bulkTrigger.dataset.bulkGender,
                bulkTrigger.dataset.bulkAgeGroup,
                bulkTrigger.dataset.bulkNationality,
                bulkTrigger.dataset.bulkActivePool,
                bulkTrigger.dataset.bulkTotalPool,
                bulkTrigger.dataset.bulkActiveNoPool,
                bulkTrigger.dataset.bulkTotalNoPool
            );
            return;
        }
        // Guest rows inside an expanded reservation: main guest + single
        // companions open the same guest detail modal as the guest table.
        const guestRow = e.target.closest('[data-guest-id]');
        if (guestRow && guestRow.dataset.guestId && guestData[guestRow.dataset.guestId]) {
            openGuestModal(guestRow.dataset.guestId);
        }
    });

    guestModalCloseButtons.forEach(button => {
        button.addEventListener('click', closeGuestModal);
    });

    // Guest checkout
    guestCheckOutBtn?.addEventListener('click', async () => {
        if (!currentCustomerId) return;

        if (!confirm('Check out this guest from all active reservations?')) return;

        const submitButton = guestCheckOutBtn;
        submitButton.disabled = true;
        submitButton.textContent = 'Checking out...';

        try {
            // Find the reservation guest entry for this customer
            const customer = guestData[currentCustomerId];
            if (!customer || !customer.reservation_guests || customer.reservation_guests.length === 0) {
                throw new Error('No active reservation found for this guest.');
            }

            const reservationGuest = customer.reservation_guests.find(rg => {
                const reservation = rg.reservation;
                if (!reservation) return false;
                const status = (reservation.status || '').toLowerCase().replace(/ /g, '_');
                return status !== 'checked_out' && status !== 'checkedout' && status !== 'checked-out';
            });

            if (!reservationGuest) {
                throw new Error('No active reservation found for this guest.');
            }

            const response = await fetch(`/staff/reservation-guests/${reservationGuest.id}/check-out`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(payload.message || 'Unable to check out this guest.');
            }

            closeGuestModal();

            // Remove or update guest row in the table
            const guestId = customer.id || reservationGuest.id;
            const guestRow = document.querySelector(`tr.guest-row[data-guest-id="${guestId}"]`) || document.querySelector(`tr.guest-row[data-reservation-guest-id="${reservationGuest.id}"]`);
            if (guestRow) {
                guestRow.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                guestRow.style.opacity = '0';
                guestRow.style.transform = 'scaleY(0)';
                setTimeout(() => {
                    guestRow.remove();
                    const remainingGuestRows = document.querySelectorAll('tr.guest-row').length;
                    const guestCountEl = document.getElementById('guestResultsCount');
                    if (guestCountEl) guestCountEl.textContent = `Showing ${remainingGuestRows} active guest${remainingGuestRows === 1 ? '' : 's'}`;
                }, 250);
            }

            showToast(`${customer.first_name || 'Guest'} checked out successfully.`);
        } catch (error) {
            window.alert(error.message || 'Unable to check out this guest.');
            submitButton.disabled = false;
            submitButton.textContent = 'Check Out';
        }
    });

    // ==========================================
    // Walk-In Guest Reservation & Multi-Day System
    // ==========================================
    // Currency formatting helper with thousands commas (e.g., 10000 -> 10,000.00, 5000 -> 5,000.00)
    const formatPeso = (val) => Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const addGuestModal = document.getElementById('addGuestModal');
    const addGuestCloseButtons = document.querySelectorAll('[data-close-add-modal="true"]');
    const openAddGuestButtons = document.querySelectorAll('[data-open-add-guest-modal="true"]');
    const primaryGuestSection = document.getElementById('primaryGuestSection');
    const chooseAmenitiesBtn = document.getElementById('chooseAmenitiesBtn');
    const includePool = document.getElementById('include_pool');
    const adultEntranceFee = document.getElementById('adultEntranceFee');
    const childEntranceFee = document.getElementById('childEntranceFee');
    const poolFee = document.getElementById('poolFee');
    const totalEntranceFee = document.getElementById('totalEntranceFee');
    const reservationTotal = document.getElementById('reservationTotal');
    const totalAmountInput = document.getElementById('totalAmountInput');
    const selectedAmenitiesContainer = document.getElementById('selectedAmenitiesContainer');
    const amenitiesHiddenInputs = document.getElementById('amenitiesHiddenInputs');
    const walkInAmenitiesSubtotal = document.getElementById('walkInAmenitiesSubtotal');
    const noAmenitiesNotice = document.getElementById('noAmenitiesNotice');

    const todayStr = window.SERVER_TODAY || (() => {
        const d = new Date();
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    })();

    const currentServerSession = window.SERVER_CURRENT_SESSION || 'Daytime';

    // Master walk-in schedule state (Walk-In Check-In starts TODAY at the active session)
    let walkInSchedule = {
        startDate: todayStr,
        endDate: todayStr,
        startSlot: currentServerSession,
        endSlot: currentServerSession,
        totalDays: 1,
        dayCount: currentServerSession === 'Daytime' ? 1 : 0,
        nightCount: currentServerSession === 'Nighttime' ? 1 : 0,
    };

    let selectedAmenities = [];

    // Park settings for pricing (loaded from server)
    let parkSettings = {
        daytime_adult_entrance_fee: 0,
        daytime_child_entrance_fee: 0,
        nighttime_adult_entrance_fee: 0,
        nighttime_child_entrance_fee: 0,
        day_pool_fee: 0,
        night_pool_fee: 0,
        daytime_start: '06:00',
        daytime_end: '18:00',
        nighttime_start: '18:00',
        nighttime_end: '06:00'
    };

    // Calculate slots count for a continuous range
    const calculateWalkInSlots = (startDate, endDate, startSlot = 'Daytime', endSlot = 'Daytime') => {
        if (!startDate) return { daysSpan: 1, dayCount: 1, nightCount: 0, totalSlots: 1 };
        const sD = new Date(startDate + 'T00:00:00');
        const eD = new Date((endDate || startDate) + 'T00:00:00');
        if (eD < sD) return { daysSpan: 1, dayCount: 1, nightCount: 0, totalSlots: 1 };

        let dayCount = 0;
        let nightCount = 0;
        const cur = new Date(sD);

        while (cur <= eD) {
            const isStartDay = cur.getTime() === sD.getTime();
            const isEndDay = cur.getTime() === eD.getTime();

            if (isStartDay && isEndDay) {
                if (startSlot === 'Daytime' && endSlot === 'Daytime') {
                    dayCount += 1;
                } else if (startSlot === 'Nighttime' && endSlot === 'Nighttime') {
                    nightCount += 1;
                } else if (startSlot === 'Daytime' && endSlot === 'Nighttime') {
                    dayCount += 1;
                    nightCount += 1;
                }
            } else if (isStartDay) {
                if (startSlot === 'Daytime') {
                    dayCount += 1;
                    nightCount += 1;
                } else {
                    nightCount += 1;
                }
            } else if (isEndDay) {
                if (endSlot === 'Daytime') {
                    dayCount += 1;
                } else {
                    dayCount += 1;
                    nightCount += 1;
                }
            } else {
                dayCount += 1;
                nightCount += 1;
            }

            cur.setDate(cur.getDate() + 1);
        }

        const daysSpan = Math.round((eD - sD) / (1000 * 60 * 60 * 24)) + 1;
        return {
            daysSpan: Math.max(1, daysSpan),
            dayCount,
            nightCount,
            totalSlots: dayCount + nightCount
        };
    };

    const formatDisplayDate = (dateStr) => {
        if (!dateStr) return '';
        const [y, m, d] = dateStr.split('-');
        const date = new Date(parseInt(y), parseInt(m) - 1, parseInt(d));
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    };

    const formatShortDate = (dateStr) => {
        if (!dateStr) return '';
        const [y, m, d] = dateStr.split('-');
        const date = new Date(parseInt(y), parseInt(m) - 1, parseInt(d));
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    };

    const getCheckoutDateAndDisplay = (endDateStr, endSlot) => {
        if (!endDateStr) {
            return {
                displayDate: '',
                time: '5:00 PM',
                sessionLabel: 'Daytime',
                fullHtml: '—',
                textSummary: '—'
            };
        }
        const [y, m, d] = endDateStr.split('-').map(Number);
        const dateObj = new Date(y, m - 1, d);
        const isNight = (endSlot || '').includes('Night');

        if (isNight) {
            // Overnight checkout is next morning at 8:00 AM
            dateObj.setDate(dateObj.getDate() + 1);
            const checkoutFmt = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            return {
                dateObj,
                displayDate: checkoutFmt,
                time: '8:00 AM',
                sessionLabel: 'Nighttime (Overnight)',
                fullHtml: `${checkoutFmt} <span class="text-emerald-700 dark:text-emerald-300 font-bold">· 8:00 AM (Next Morning)</span>`,
                textSummary: `${checkoutFmt} at 8:00 AM (Next Morning)`
            };
        } else {
            // Daytime checkout is same date at 5:00 PM
            const checkoutFmt = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            return {
                dateObj,
                displayDate: checkoutFmt,
                time: '5:00 PM',
                sessionLabel: 'Daytime',
                fullHtml: `${checkoutFmt} <span class="text-emerald-700 dark:text-emerald-300 font-bold">· 5:00 PM (Daytime)</span>`,
                textSummary: `${checkoutFmt} at 5:00 PM (Daytime)`
            };
        }
    };

    const syncMasterScheduleDisplay = () => {
        const counts = calculateWalkInSlots(walkInSchedule.startDate, walkInSchedule.endDate, walkInSchedule.startSlot, walkInSchedule.endSlot);
        walkInSchedule.totalDays = counts.daysSpan;
        walkInSchedule.dayCount = counts.dayCount;
        walkInSchedule.nightCount = counts.nightCount;

        // Hidden input sync
        const startInput = document.getElementById('walkInStartDate');
        const endInput = document.getElementById('walkInEndDate');
        const startSlotInput = document.getElementById('walkInStartSlot');
        const endSlotInput = document.getElementById('walkInEndSlot');
        const totalDaysInput = document.getElementById('walkInTotalDays');

        if (startInput) startInput.value = walkInSchedule.startDate;
        if (endInput) endInput.value = walkInSchedule.endDate;
        if (startSlotInput) startSlotInput.value = walkInSchedule.startSlot;
        if (endSlotInput) endSlotInput.value = walkInSchedule.endSlot;
        if (totalDaysInput) totalDaysInput.value = walkInSchedule.totalDays;

        // Visual summary sync
        const summaryText = document.getElementById('walkInScheduleSummaryText');
        const datesText = document.getElementById('walkInScheduleDatesText');
        const stayCompactText = document.getElementById('walkInStayCompactText');

        const sFmt = formatDisplayDate(walkInSchedule.startDate);
        const sShort = formatShortDate(walkInSchedule.startDate);
        const checkout = getCheckoutDateAndDisplay(walkInSchedule.endDate, walkInSchedule.endSlot);

        if (summaryText) {
            const spanLabel = counts.daysSpan === 1 ? '1 Day' : `${counts.daysSpan} Days`;
            const breakdown = `(${counts.dayCount}D ${counts.nightCount}N)`;
            summaryText.textContent = `${spanLabel} ${breakdown}`;
        }
        if (datesText) {
            datesText.textContent = `Check-In: ${sFmt} (${walkInSchedule.startSlot}) • Check-Out: ${checkout.textSummary}`;
        }
        if (stayCompactText) {
            stayCompactText.textContent = `${sShort} (${walkInSchedule.startSlot}) → Out: ${checkout.displayDate} (${checkout.time})`;
        }
    };

    // Walk-in Modal Tab Controller & Validation
    const switchWalkInTab = (targetTabId) => {
        if (!targetTabId) return;

        const tabBtns = document.querySelectorAll('.walkin-tab-btn');
        const tabPanes = document.querySelectorAll('.walkin-tab-pane');

        tabBtns.forEach(btn => {
            const isMatch = btn.dataset.walkinTarget === targetTabId;
            btn.classList.toggle('is-active', isMatch);
        });

        tabPanes.forEach(pane => {
            if (pane.id === targetTabId) {
                pane.style.display = 'flex';
            } else {
                pane.style.display = 'none';
            }
        });

        const tabStepMap = {
            'mainGuestTab': '1 of 4',
            'amenitiesTab': '2 of 4',
            'companionsTab': '3 of 4',
            'feesTab': '4 of 4'
        };
        const stepIndicator = document.getElementById('walkInActiveTabIndicator');
        if (stepIndicator && tabStepMap[targetTabId]) {
            stepIndicator.textContent = tabStepMap[targetTabId];
        }
    };

    const checkMainGuestValidation = () => {
        const firstName = document.getElementById('primary_first_name')?.value?.trim() || '';
        const lastName = document.getElementById('primary_last_name')?.value?.trim() || '';
        const statusBadge = document.getElementById('walkInMainGuestStatus');

        if (!statusBadge) return;

        const isComplete = firstName.length > 0 && lastName.length > 0;

        if (isComplete) {
            statusBadge.classList.remove('walkin-status-indicator--error');
            statusBadge.classList.add('walkin-status-indicator--valid');
            statusBadge.innerHTML = '<i class="bi bi-check-lg text-xs"></i>';
            statusBadge.title = 'Main guest details filled';
        } else {
            statusBadge.classList.remove('walkin-status-indicator--valid');
            statusBadge.classList.add('walkin-status-indicator--error');
            statusBadge.textContent = '!';
            statusBadge.title = 'Required main guest details missing';
        }
    };

    const openAddGuestModal = () => {
        if (!addGuestModal) return;
        addGuestModal.classList.add('is-open');
        addGuestModal.classList.remove('hidden');
        addGuestModal.setAttribute('aria-hidden', 'false');
        switchWalkInTab('mainGuestTab');
        checkMainGuestValidation();
        loadParkSettings();
        syncMasterScheduleDisplay();
        syncEntranceOptionUI();
        syncPoolOptionUI();
        renderSelectedAmenities();
        updateGrandTotal();
    };

    const closeAddGuestModal = () => {
        if (!addGuestModal) return;
        addGuestModal.classList.remove('is-open');
        addGuestModal.setAttribute('aria-hidden', 'true');
    };

    openAddGuestButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            openAddGuestModal();
        });
    });

    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-open-add-guest-modal="true"]');
        if (trigger) {
            e.preventDefault();
            openAddGuestModal();
        }

        const tabBtn = e.target.closest('.walkin-tab-btn');
        if (tabBtn && tabBtn.dataset.walkinTarget) {
            e.preventDefault();
            switchWalkInTab(tabBtn.dataset.walkinTarget);
        }

        const stepBtn = e.target.closest('.walkin-step-btn[data-step-to]');
        if (stepBtn && stepBtn.dataset.stepTo) {
            e.preventDefault();
            switchWalkInTab(stepBtn.dataset.stepTo);
        }
    });

    addGuestCloseButtons.forEach(button => {
        button.addEventListener('click', closeAddGuestModal);
    });

    ['input', 'change', 'blur'].forEach(ev => {
        document.getElementById('primary_first_name')?.addEventListener(ev, checkMainGuestValidation);
        document.getElementById('primary_last_name')?.addEventListener(ev, checkMainGuestValidation);
    });

    // Load park settings from server
    const loadParkSettings = async () => {
        try {
            const response = await fetch('/api/park-settings');
            if (response.ok) {
                const data = await response.json();
                parkSettings = { ...parkSettings, ...data };
                updateGrandTotal();
            }
        } catch (error) {
            console.error('Failed to load park settings:', error);
        }
    };

    // Pool Policy UI Controls
    const walkInEntranceOption = document.getElementById('walkInEntranceOption');
    const walkInEntranceOptionHelp = document.getElementById('walkInEntranceOptionHelp');
    const primaryGuestFreeEntranceWrap = document.getElementById('primaryGuestFreeEntranceWrap');
    const primaryGuestEntranceBadge = document.getElementById('primaryGuestEntranceBadge');
    const primaryIsFreeInput = document.getElementById('primary_is_free_entrance');
    const singleCompanionFreeEntranceWrap = document.getElementById('singleCompanionFreeEntranceWrap');
    const bulkCompanionFreeEntranceWrap = document.getElementById('bulkCompanionFreeEntranceWrap');
    const bulkCompanionFreeQty = document.getElementById('bulk_companion_free_quantity');
    const bulkFreeQtyHint = document.getElementById('bulkFreeQtyHint');

    const walkInPoolOption = document.getElementById('walkInPoolOption');
    const walkInPoolOptionHelp = document.getElementById('walkInPoolOptionHelp');
    const primaryGuestPoolWrap = document.getElementById('primaryGuestPoolWrap');
    const primaryHasPoolInput = document.getElementById('primary_has_pool_access');
    const primaryGuestPoolBadge = document.getElementById('primaryGuestPoolBadge');
    const singleCompanionPoolWrap = document.getElementById('singleCompanionPoolWrap');
    const companionHasPoolInput = document.getElementById('companion_has_pool_access');
    const bulkCompanionPoolWrap = document.getElementById('bulkCompanionPoolWrap');
    const bulkCompanionPoolQty = document.getElementById('bulk_companion_pool_quantity');
    const bulkCompanionQtyInput = document.getElementById('bulk_companion_quantity');
    const bulkPoolQtyHint = document.getElementById('bulkPoolQtyHint');

    const syncEntranceOptionUI = () => {
        const opt = walkInEntranceOption?.value || 'all_paid';
        const isSpecific = opt === 'specific';
        const isAllPaid = opt === 'all_paid';
        const isAllFree = opt === 'all_free';

        if (primaryGuestFreeEntranceWrap) {
            primaryGuestFreeEntranceWrap.style.display = isSpecific ? 'block' : 'none';
        }
        if (singleCompanionFreeEntranceWrap) {
            singleCompanionFreeEntranceWrap.style.display = isSpecific ? 'block' : 'none';
        }
        if (bulkCompanionFreeEntranceWrap) {
            bulkCompanionFreeEntranceWrap.style.display = isSpecific ? 'grid' : 'none';
        }

        if (walkInEntranceOptionHelp) {
            if (isAllPaid) {
                walkInEntranceOptionHelp.textContent = 'All guests will pay the standard entrance fee based on age.';
            } else if (isSpecific) {
                walkInEntranceOptionHelp.textContent = 'Custom: choose which individual guests or groups receive free entrance.';
            } else if (isAllFree) {
                walkInEntranceOptionHelp.textContent = 'Free Promo: entrance fee is waived (₱0.00) for all guests in this reservation.';
            }
        }

        if (primaryGuestEntranceBadge) {
            if (isAllFree) {
                primaryGuestEntranceBadge.classList.remove('hidden');
                primaryGuestEntranceBadge.innerHTML = '<i class="bi bi-ticket-perforated-fill me-1"></i>Free Entrance (All)';
            } else if (isSpecific && primaryIsFreeInput?.checked) {
                primaryGuestEntranceBadge.classList.remove('hidden');
                primaryGuestEntranceBadge.innerHTML = '<i class="bi bi-ticket-perforated-fill me-1"></i>Free Entrance';
            } else {
                primaryGuestEntranceBadge.classList.add('hidden');
            }
        }

        const entranceCompactText = document.getElementById('walkInEntranceCompactText');
        if (entranceCompactText) {
            if (isAllPaid) {
                entranceCompactText.textContent = 'Entrance: Standard Rate';
            } else if (isSpecific) {
                entranceCompactText.textContent = 'Entrance: Specific Free';
            } else if (isAllFree) {
                entranceCompactText.textContent = 'Entrance: Free Promo';
            }
        }

        syncBulkFreeQuantityMax();
        renderCompanions();
        updateGrandTotal();
    };

    const syncPoolOptionUI = () => {
        const opt = walkInPoolOption?.value || 'no_pool';
        const isSpecific = opt === 'specific';
        const isAllPaid = opt === 'all_paid';
        const isAllFree = opt === 'all_free';

        const poolCompactText = document.getElementById('walkInPoolCompactText');
        if (poolCompactText) {
            if (opt === 'no_pool') {
                poolCompactText.textContent = 'Pool: None';
            } else if (isSpecific) {
                poolCompactText.textContent = 'Pool: Specific Access';
            } else if (isAllPaid) {
                poolCompactText.textContent = 'Pool: All Paid';
            } else if (isAllFree) {
                poolCompactText.textContent = 'Pool: Free Promo';
            }
        }

        if (primaryGuestPoolWrap) {
            primaryGuestPoolWrap.style.display = isSpecific ? 'block' : 'none';
        }

        if (singleCompanionPoolWrap) {
            singleCompanionPoolWrap.style.display = isSpecific ? 'block' : 'none';
        }

        if (bulkCompanionPoolWrap) {
            bulkCompanionPoolWrap.style.display = isSpecific ? 'grid' : 'none';
        }

        if (walkInPoolOptionHelp) {
            if (opt === 'no_pool') {
                walkInPoolOptionHelp.textContent = 'No pool fee will be charged for any guest in this reservation.';
            } else if (isSpecific) {
                walkInPoolOptionHelp.textContent = 'Custom: specify which primary guest, companions, and group counts have pool access.';
            } else if (isAllPaid) {
                walkInPoolOptionHelp.textContent = 'Standard rate: all guests in this reservation will be charged for pool access.';
            } else if (isAllFree) {
                walkInPoolOptionHelp.textContent = 'Free Promo: all guests are granted pool access without additional charge.';
            }
        }

        if (primaryGuestPoolBadge) {
            if (isAllPaid) {
                primaryGuestPoolBadge.classList.remove('hidden');
                primaryGuestPoolBadge.innerHTML = '<i class="bi bi-water me-1"></i>Pool Pass (All)';
            } else if (isAllFree) {
                primaryGuestPoolBadge.classList.remove('hidden');
                primaryGuestPoolBadge.innerHTML = '<i class="bi bi-water me-1"></i>Free Promo Pool';
            } else if (isSpecific && primaryHasPoolInput?.checked) {
                primaryGuestPoolBadge.classList.remove('hidden');
                primaryGuestPoolBadge.innerHTML = '<i class="bi bi-water me-1"></i>Pool Pass';
            } else {
                primaryGuestPoolBadge.classList.add('hidden');
            }
        }

        const legacyInput = document.getElementById('include_pool_legacy');
        if (legacyInput) {
            legacyInput.value = (isAllPaid || isSpecific) ? '1' : '0';
        }

        renderCompanions();
        updateGrandTotal();
    };

    walkInEntranceOption?.addEventListener('change', syncEntranceOptionUI);
    walkInPoolOption?.addEventListener('change', syncPoolOptionUI);
    primaryHasPoolInput?.addEventListener('change', syncPoolOptionUI);
    primaryIsFreeInput?.addEventListener('change', syncEntranceOptionUI);

    const syncBulkPoolQuantityMax = () => {
        if (!bulkCompanionQtyInput || !bulkCompanionPoolQty) return;
        const total = Math.max(1, parseInt(bulkCompanionQtyInput.value, 10) || 1);
        bulkCompanionPoolQty.max = total;
        let currentPool = parseInt(bulkCompanionPoolQty.value, 10) || 0;
        if (currentPool > total) {
            currentPool = total;
            bulkCompanionPoolQty.value = total;
        }
        if (bulkPoolQtyHint) {
            bulkPoolQtyHint.textContent = `${currentPool} of ${total} with pool access`;
        }
    };
    bulkCompanionQtyInput?.addEventListener('input', syncBulkPoolQuantityMax);
    bulkCompanionPoolQty?.addEventListener('input', syncBulkPoolQuantityMax);

    const syncBulkFreeQuantityMax = () => {
        if (!bulkCompanionQtyInput || !bulkCompanionFreeQty) return;
        const total = Math.max(1, parseInt(bulkCompanionQtyInput.value, 10) || 1);
        bulkCompanionFreeQty.max = total;
        let currentFree = parseInt(bulkCompanionFreeQty.value, 10) || 0;
        if (currentFree > total) {
            currentFree = total;
            bulkCompanionFreeQty.value = total;
        }
        if (bulkFreeQtyHint) {
            bulkFreeQtyHint.textContent = `${currentFree} of ${total} with free entrance`;
        }
    };
    bulkCompanionQtyInput?.addEventListener('input', syncBulkFreeQuantityMax);
    bulkCompanionFreeQty?.addEventListener('input', syncBulkFreeQuantityMax);

    // Calculate entrance fee based on main guest, companions, and pool policies
    const calculateEntranceFee = () => {
        const counts = calculateWalkInSlots(walkInSchedule.startDate, walkInSchedule.endDate, walkInSchedule.startSlot, walkInSchedule.endSlot);
        const timeType = counts.nightCount > 0 && counts.dayCount > 0 ? 'daytonight' : (counts.nightCount > 0 ? 'nighttime' : 'daytime');
        const entranceOpt = walkInEntranceOption?.value || 'all_paid';
        const poolOpt = walkInPoolOption?.value || 'no_pool';

        const dayAdult = parseFloat(parkSettings.daytime_adult_entrance_fee) || 0;
        const dayChild = parseFloat(parkSettings.daytime_child_entrance_fee) || 0;
        const nightAdult = parseFloat(parkSettings.nighttime_adult_entrance_fee) || 0;
        const nightChild = parseFloat(parkSettings.nighttime_child_entrance_fee) || 0;
        const dayPool = parseFloat(parkSettings.day_pool_fee) || 0;
        const nightPool = parseFloat(parkSettings.night_pool_fee) || 0;

        let adultRate = dayAdult;
        let childRate = dayChild;
        let poolRate = dayPool;

        if (timeType === 'nighttime') {
            adultRate = nightAdult;
            childRate = nightChild;
            poolRate = nightPool;
        } else if (timeType === 'daytonight') {
            adultRate = dayAdult + nightAdult;
            childRate = dayChild + nightChild;
            poolRate = dayPool + nightPool;
        }

        // Count adults and children
        const primaryAgeInput = document.getElementById('primary_age');
        const primaryAgeVal = primaryAgeInput ? parseInt(primaryAgeInput.value) : null;
        let primaryIsChild = (primaryAgeVal !== null && !isNaN(primaryAgeVal)) ? primaryAgeVal <= 12 : false;

        let adultCount = primaryIsChild ? 0 : 1;
        let childCount = primaryIsChild ? 1 : 0;

        // Individual companions
        companions.forEach(c => {
            let isChild = false;
            if (c.age !== null && c.age !== undefined && c.age !== '') {
                const age = parseInt(c.age);
                if (!isNaN(age)) isChild = age <= 12;
            } else if (c.age_type === 'child') {
                isChild = true;
            }
            if (isChild) childCount++; else adultCount++;
        });

        // Bulk companion groups
        bulkCompanionGroups.forEach(g => {
            const qty = parseInt(g.quantity) || 1;
            const isChild = g.age_group === '0-12';
            if (isChild) {
                childCount += qty;
            } else {
                adultCount += qty;
            }
        });

        const totalGuests = adultCount + childCount;
        let payingAdultCount = adultCount;
        let payingChildCount = childCount;
        let freeEntranceCount = 0;

        if (entranceOpt === 'all_free') {
            payingAdultCount = 0;
            payingChildCount = 0;
            freeEntranceCount = totalGuests;
        } else if (entranceOpt === 'specific') {
            const primaryIsFree = Boolean(primaryIsFreeInput?.checked);
            payingAdultCount = (primaryIsChild || primaryIsFree) ? 0 : 1;
            payingChildCount = (!primaryIsChild || primaryIsFree) ? 0 : 1;
            freeEntranceCount = primaryIsFree ? 1 : 0;

            companions.forEach(c => {
                let isChild = false;
                if (c.age !== null && c.age !== undefined && c.age !== '') {
                    const age = parseInt(c.age);
                    if (!isNaN(age)) isChild = age <= 12;
                } else if (c.age_type === 'child') {
                    isChild = true;
                }
                if (c.has_free_entrance) {
                    freeEntranceCount++;
                } else {
                    if (isChild) payingChildCount++; else payingAdultCount++;
                }
            });

            bulkCompanionGroups.forEach(g => {
                const qty = parseInt(g.quantity) || 1;
                const isChild = g.age_group === '0-12';
                const fQty = Math.min(Math.max(0, parseInt(g.free_quantity, 10) || 0), qty);
                const payingQty = qty - fQty;
                freeEntranceCount += fQty;
                if (isChild) payingChildCount += payingQty; else payingAdultCount += payingQty;
            });
        }

        const totalAdultFee = payingAdultCount * adultRate;
        const totalChildFee = payingChildCount * childRate;

        // Pool Access Count calculation
        let poolCount = 0;
        if (poolOpt === 'all_paid' || poolOpt === 'all_free') {
            poolCount = totalGuests;
        } else if (poolOpt === 'specific') {
            const primaryPool = (primaryHasPoolInput?.checked) ? 1 : 0;
            const singleCompanionsPool = companions.filter(c => c.has_pool_access).length;
            const bulkCompanionsPool = bulkCompanionGroups.reduce((acc, g) => acc + (parseInt(g.pool_quantity) || 0), 0);
            poolCount = primaryPool + singleCompanionsPool + bulkCompanionsPool;
        } else {
            poolCount = 0;
        }

        let totalPoolFee = 0;
        if (poolOpt === 'all_paid' || poolOpt === 'specific') {
            totalPoolFee = poolCount * poolRate;
        }

        const totalEntrance = totalAdultFee + totalChildFee + totalPoolFee;

        if (adultEntranceFee) {
            if (entranceOpt === 'all_free') {
                adultEntranceFee.textContent = `₱0.00 (Free Promo • ${adultCount} Adult${adultCount === 1 ? '' : 's'})`;
            } else {
                const freeAdults = adultCount - payingAdultCount;
                const freeTxt = freeAdults > 0 ? ` • ${freeAdults} Free` : '';
                adultEntranceFee.textContent = `₱${formatPeso(totalAdultFee)} (${payingAdultCount} × ₱${formatPeso(adultRate)}${freeTxt})`;
            }
        }
        if (childEntranceFee) {
            if (entranceOpt === 'all_free') {
                childEntranceFee.textContent = `₱0.00 (Free Promo • ${childCount} Child${childCount === 1 ? '' : 'ren'})`;
            } else {
                const freeChildren = childCount - payingChildCount;
                const freeTxt = freeChildren > 0 ? ` • ${freeChildren} Free` : '';
                childEntranceFee.textContent = `₱${formatPeso(totalChildFee)} (${payingChildCount} × ₱${formatPeso(childRate)}${freeTxt})`;
            }
        }
        if (poolFee) {
            if (poolOpt === 'no_pool') {
                poolFee.textContent = '₱0.00 (No pool access)';
            } else if (poolOpt === 'all_free') {
                poolFee.textContent = `₱0.00 (Free Promo • All ${totalGuests} guests)`;
            } else if (poolOpt === 'all_paid') {
                poolFee.textContent = `₱${formatPeso(totalPoolFee)} (${poolCount} × ₱${formatPeso(poolRate)})`;
            } else {
                poolFee.textContent = `₱${formatPeso(totalPoolFee)} (${poolCount} of ${totalGuests} guests × ₱${formatPeso(poolRate)})`;
            }
        }
        if (totalEntranceFee) totalEntranceFee.textContent = `₱${formatPeso(totalEntrance)}`;

        return { totalEntrance, adultCount, childCount, payingAdultCount, payingChildCount, freeEntranceCount, totalPoolFee, poolCount, entranceOption: entranceOpt, poolOption: poolOpt, poolRate };
    };

    // Calculate Extra Head Fee for walk-in when amenity capacities are exceeded
    const calculateExtraHeadFee = () => {
        let extraHeadTotal = 0;
        const extraHeadBreakdown = [];
        if (selectedAmenities.length > 0) {
            const defaultAmenityId = String(selectedAmenities[0].amenity_id || '');
            const amenityCounts = {};

            // Primary guest
            const primaryAgeInput = document.getElementById('primary_age');
            const primaryAgeVal = primaryAgeInput ? parseInt(primaryAgeInput.value, 10) : null;
            const pAmId = defaultAmenityId;
            amenityCounts[pAmId] = (amenityCounts[pAmId] || 0) + 1;

            // Single companions
            companions.forEach(c => {
                const cAmId = String(c.amenity_id || defaultAmenityId);
                amenityCounts[cAmId] = (amenityCounts[cAmId] || 0) + 1;
            });

            // Bulk companion groups
            bulkCompanionGroups.forEach(g => {
                const bAmId = String(g.amenity_id || defaultAmenityId);
                const qty = parseInt(g.quantity, 10) || 1;
                amenityCounts[bAmId] = (amenityCounts[bAmId] || 0) + qty;
            });

            selectedAmenities.forEach(am => {
                const amId = String(am.amenity_id || '');
                const maxCap = (am.max_cap !== null && am.max_cap !== undefined && am.max_cap !== '') ? parseInt(am.max_cap, 10) : null;
                const addRate = parseFloat(am.additional_per_head) || 0;
                const count = amenityCounts[amId] || 0;

                if (maxCap !== null && !isNaN(maxCap) && count > maxCap) {
                    const excess = count - maxCap;
                    const fee = excess * addRate;
                    extraHeadTotal += fee;
                    extraHeadBreakdown.push({
                        amenity_id: amId,
                        amenity_name: am.amenity_name || 'Amenity',
                        assigned: count,
                        max_cap: maxCap,
                        excess,
                        add_rate: addRate,
                        fee
                    });
                }
            });
        }
        return { extraHeadTotal, extraHeadBreakdown };
    };

    // Calculate Grand Total (Entrance + Pool + Amenities + Extra Head Fee)
    const updateGrandTotal = () => {
        const { totalEntrance } = calculateEntranceFee();
        const amenitiesTotal = selectedAmenities.reduce((sum, a) => sum + (parseFloat(a.price_at_booking) || 0), 0);
        const { extraHeadTotal, extraHeadBreakdown } = calculateExtraHeadFee();
        const grandTotal = totalEntrance + amenitiesTotal + extraHeadTotal;

        if (walkInAmenitiesSubtotal) {
            walkInAmenitiesSubtotal.textContent = `₱${formatPeso(amenitiesTotal)}`;
        }
        const walkInExtraHeadSubtotal = document.getElementById('walkInExtraHeadSubtotal');
        const walkInExtraHeadFeeSummaryWrap = document.getElementById('walkInExtraHeadFeeSummaryWrap');
        if (walkInExtraHeadSubtotal) {
            walkInExtraHeadSubtotal.textContent = `₱${formatPeso(extraHeadTotal)}`;
        }
        if (walkInExtraHeadFeeSummaryWrap) {
            walkInExtraHeadFeeSummaryWrap.style.display = extraHeadTotal > 0 ? 'flex' : 'none';
        }
        if (reservationTotal) {
            reservationTotal.textContent = `₱${formatPeso(grandTotal)}`;
        }
        const sidebarFeesBadge = document.getElementById('walkInSidebarFeesBadge');
        if (sidebarFeesBadge) {
            sidebarFeesBadge.textContent = `₱${formatPeso(grandTotal)}`;
        }
        const sidebarGrandTotalText = document.getElementById('walkInSidebarGrandTotalText');
        if (sidebarGrandTotalText) {
            sidebarGrandTotalText.textContent = `₱${formatPeso(grandTotal)}`;
        }
        const topGrandTotalText = document.getElementById('walkInTopGrandTotalText');
        if (topGrandTotalText) {
            topGrandTotalText.textContent = `₱${formatPeso(grandTotal)}`;
        }
        if (totalAmountInput) {
            totalAmountInput.value = grandTotal;
        }

        const companionCountBadge = document.getElementById('walkInCompanionCountBadge');
        if (companionCountBadge) {
            const totalCompanions = companions.length + bulkCompanionGroups.reduce((acc, g) => acc + (parseInt(g.quantity) || 1), 0);
            companionCountBadge.textContent = `${totalCompanions} companion${totalCompanions === 1 ? '' : 's'}`;
        }
        const sidebarCompBadge = document.getElementById('walkInSidebarCompanionsBadge');
        if (sidebarCompBadge) {
            const totalCompanions = companions.length + bulkCompanionGroups.reduce((acc, g) => acc + (parseInt(g.quantity) || 1), 0);
            sidebarCompBadge.textContent = totalCompanions;
        }
        const sidebarAmBadge = document.getElementById('walkInSidebarAmenitiesBadge');
        if (sidebarAmBadge) {
            sidebarAmBadge.textContent = selectedAmenities.length;
        }

        return { entranceFeeTotal: totalEntrance, amenitiesTotal, extraHeadTotal, extraHeadBreakdown, grandTotal };
    };

    const primaryAgeInput = document.getElementById('primary_age');
    const primaryAgeBadge = document.getElementById('primaryAgeBadge');
    const syncPrimaryAgeBadge = () => {
        if (primaryAgeInput && primaryAgeBadge) {
            const ageVal = parseInt(primaryAgeInput.value, 10);
            if (!isNaN(ageVal) && ageVal <= 12) {
                primaryAgeBadge.textContent = 'Child Rate (0-12 yrs)';
                primaryAgeBadge.className = 'rounded-md border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-[0.7rem] font-bold text-amber-700 dark:text-amber-300';
            } else {
                primaryAgeBadge.textContent = 'Adult Rate (13+ yrs)';
                primaryAgeBadge.className = 'rounded-md border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[0.7rem] font-bold text-emerald-700 dark:text-emerald-300';
            }
        }
        updateGrandTotal();
    };
    primaryAgeInput?.addEventListener('input', syncPrimaryAgeBadge);

    // Expected Check-Out Datetime Calculation Helper
    const formatTime12h = (timeStr24) => {
        if (!timeStr24) return '6:00 PM';
        const [h, m] = timeStr24.split(':').map(Number);
        const period = h >= 12 ? 'PM' : 'AM';
        const h12 = h % 12 || 12;
        const mStr = String(m || 0).padStart(2, '0');
        return `${h12}:${mStr} ${period}`;
    };

    const computeExpectedCheckout = (endDateStr, endSlotStr) => {
        const dStr = endDateStr || todayStr;
        const cleanSlot = (endSlotStr || currentServerSession || 'Daytime').includes('Night') ? 'Nighttime' : 'Daytime';

        const dayEnd = parkSettings.daytime_end || '18:00';
        const nightEnd = parkSettings.nighttime_end || '06:00';

        const [y, m, d] = dStr.split('-').map(Number);
        const dateObj = new Date(y, m - 1, d);

        let checkoutDateObj = new Date(dateObj.getTime());
        let timeStr = '';

        if (cleanSlot === 'Nighttime') {
            checkoutDateObj.setDate(checkoutDateObj.getDate() + 1);
            timeStr = formatTime12h(nightEnd);
        } else {
            timeStr = formatTime12h(dayEnd);
        }

        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        const formattedDate = `${daysOfWeek[checkoutDateObj.getDay()]}, ${monthNames[checkoutDateObj.getMonth()]} ${checkoutDateObj.getDate()}, ${checkoutDateObj.getFullYear()}`;

        return `${formattedDate} at ${timeStr}`;
    };

    let isPaymentConfirmed = false;
    const paymentConfirmModal = document.getElementById('paymentConfirmModal');
    const confirmPaymentBtn = document.getElementById('confirmPaymentBtn');
    const cancelPaymentBtn = document.getElementById('cancelPaymentBtn');
    const paymentCloseButtons = document.querySelectorAll('[data-close-payment-modal="true"]');

    const openPaymentConfirmModal = () => {
        if (!paymentConfirmModal) return;

        const primaryFirstName = document.getElementById('primary_first_name')?.value?.trim() || 'Walk-In';
        const primaryMiddleName = document.getElementById('primary_middle_name')?.value?.trim() || '';
        const primaryLastName = document.getElementById('primary_last_name')?.value?.trim() || 'Guest';
        const primaryPhone = document.getElementById('primary_phone')?.value?.trim() || '';
        const primaryEmail = document.getElementById('primary_email')?.value?.trim() || '';
        const primaryAge = document.getElementById('primary_age')?.value?.trim() || '';
        const primaryGender = document.getElementById('primary_gender')?.value || '';
        const primaryIsForeigner = document.getElementById('primaryGuestIsForeigner')?.value === '1';

        const counts = calculateWalkInSlots(walkInSchedule.startDate, walkInSchedule.endDate, walkInSchedule.startSlot, walkInSchedule.endSlot);
        const timeType = counts.nightCount > 0 && counts.dayCount > 0 ? 'daytonight' : (counts.nightCount > 0 ? 'nighttime' : 'daytime');

        const dayAdult = parseFloat(parkSettings.daytime_adult_entrance_fee) || 0;
        const dayChild = parseFloat(parkSettings.daytime_child_entrance_fee) || 0;
        const nightAdult = parseFloat(parkSettings.nighttime_adult_entrance_fee) || 0;
        const nightChild = parseFloat(parkSettings.nighttime_child_entrance_fee) || 0;

        let adultRate = dayAdult;
        let childRate = dayChild;

        if (timeType === 'nighttime') {
            adultRate = nightAdult;
            childRate = nightChild;
        } else if (timeType === 'daytonight') {
            adultRate = dayAdult + nightAdult;
            childRate = dayChild + nightChild;
        }

        const { totalEntrance, adultCount, childCount, payingAdultCount, payingChildCount, freeEntranceCount, totalPoolFee, poolCount, poolOption: poolOpt, poolRate } = calculateEntranceFee();
        const totalGuests = adultCount + childCount;

        // 1. Expected Check-Out
        const expectedCheckoutStr = computeExpectedCheckout(walkInSchedule.endDate, walkInSchedule.endSlot);
        const sFmt = formatDisplayDate(walkInSchedule.startDate);
        const eFmt = formatDisplayDate(walkInSchedule.endDate);
        const spanLabel = counts.daysSpan === 1 ? '1 Day' : `${counts.daysSpan} Days`;

        const payConfirmExpectedCheckoutText = document.getElementById('payConfirmExpectedCheckoutText');
        const payConfirmStayScheduleBreakdown = document.getElementById('payConfirmStayScheduleBreakdown');
        if (payConfirmExpectedCheckoutText) {
            payConfirmExpectedCheckoutText.textContent = expectedCheckoutStr;
        }
        if (payConfirmStayScheduleBreakdown) {
            payConfirmStayScheduleBreakdown.textContent = `Check-In: ${sFmt} (${walkInSchedule.startSlot}) → Check-Out: ${eFmt} (${walkInSchedule.endSlot}) • ${spanLabel} (${counts.dayCount}D ${counts.nightCount}N)`;
        }

        // 2. Primary Guest & Party
        const payConfirmGuestName = document.getElementById('payConfirmGuestName');
        const payConfirmContactInfo = document.getElementById('payConfirmContactInfo');
        const payConfirmDemographics = document.getElementById('payConfirmDemographics');
        const payConfirmPartyCount = document.getElementById('payConfirmPartyCount');

        if (payConfirmGuestName) {
            payConfirmGuestName.textContent = `${primaryFirstName} ${primaryMiddleName} ${primaryLastName}`.replace(/\s+/g, ' ').trim();
        }
        if (payConfirmContactInfo) {
            const parts = [];
            if (primaryPhone) parts.push(`${primaryPhone}`);
            if (primaryEmail) parts.push(`${primaryEmail}`);
            payConfirmContactInfo.textContent = parts.join(' • ') || 'No contact info provided';
        }
        if (payConfirmDemographics) {
            const ageNum = parseInt(primaryAge, 10);
            const ageText = !isNaN(ageNum) ? `${ageNum} yrs (${ageNum <= 12 ? 'Child' : 'Adult'})` : 'Adult';
            const natText = primaryIsForeigner ? 'Foreigner' : 'Filipino';
            const genText = primaryGender || 'Unspecified';
            payConfirmDemographics.textContent = `${genText} • ${ageText} • ${natText}`;
        }
        if (payConfirmPartyCount) {
            const freePartyText = freeEntranceCount > 0 ? `, ${freeEntranceCount} Free Entrance` : '';
            payConfirmPartyCount.textContent = `${totalGuests} Guest${totalGuests === 1 ? '' : 's'} (${adultCount} Adult${adultCount === 1 ? '' : 's'}, ${childCount} Child${childCount === 1 ? '' : 'ren'}${freePartyText})`;
        }

        // 3. Entrance & Pool Fees
        const payConfirmAdultFee = document.getElementById('payConfirmAdultFee');
        const payConfirmChildFee = document.getElementById('payConfirmChildFee');
        const payConfirmPoolFee = document.getElementById('payConfirmPoolFee');
        const payConfirmEntranceTotal = document.getElementById('payConfirmEntranceTotal');

        if (payConfirmAdultFee) {
            const freeAdults = adultCount - payingAdultCount;
            const freeTxt = freeAdults > 0 ? ` (${freeAdults} Free)` : '';
            payConfirmAdultFee.textContent = `₱${formatPeso(payingAdultCount * adultRate)} (${payingAdultCount} × ₱${formatPeso(adultRate)}${freeTxt})`;
        }
        if (payConfirmChildFee) {
            const freeChildren = childCount - payingChildCount;
            const freeTxt = freeChildren > 0 ? ` (${freeChildren} Free)` : '';
            payConfirmChildFee.textContent = `₱${formatPeso(payingChildCount * childRate)} (${payingChildCount} × ₱${formatPeso(childRate)}${freeTxt})`;
        }
        if (payConfirmPoolFee) {
            if (poolOpt === 'no_pool') {
                payConfirmPoolFee.textContent = '₱0.00 (No pool access)';
            } else if (poolOpt === 'all_free') {
                payConfirmPoolFee.textContent = `₱0.00 (Free Promo • All ${totalGuests} guests)`;
            } else if (poolOpt === 'all_paid') {
                payConfirmPoolFee.textContent = `₱${formatPeso(totalPoolFee)} (All ${totalGuests} guests × ₱${formatPeso(poolRate)})`;
            } else {
                payConfirmPoolFee.textContent = `₱${formatPeso(totalPoolFee)} (Specific: ${poolCount} of ${totalGuests} guests × ₱${formatPeso(poolRate)})`;
            }
        }
        if (payConfirmEntranceTotal) payConfirmEntranceTotal.textContent = `₱${formatPeso(totalEntrance)}`;

        // 4. Amenities
        const payConfirmAmenitiesList = document.getElementById('payConfirmAmenitiesList');
        const payConfirmAmenitiesSubtotal = document.getElementById('payConfirmAmenitiesSubtotal');
        let amenitiesTotal = 0;

        if (payConfirmAmenitiesList) {
            payConfirmAmenitiesList.innerHTML = '';
            if (selectedAmenities.length === 0) {
                payConfirmAmenitiesList.innerHTML = '<p class="m-0 text-hp-text-muted italic">No amenities selected (Park Entrance Only)</p>';
            } else {
                selectedAmenities.forEach(am => {
                    const amPrice = parseFloat(am.price_at_booking) || 0;
                    amenitiesTotal += amPrice;
                    const amSFmt = formatDisplayDate(am.start_date);
                    const amEFmt = formatDisplayDate(am.end_date);
                    const item = document.createElement('div');
                    item.className = 'flex justify-between items-center border-b border-glass-border/30 pb-1';
                    item.innerHTML = `
                        <div>
                            <strong class="text-hp-text font-bold">${am.amenity_name}</strong>
                            <span class="text-[0.7rem] text-hp-text-muted"> (${amSFmt} ${am.start_slot} → ${amEFmt} ${am.end_slot}${am.is_aircon ? ' • Aircon' : ''})</span>
                        </div>
                        <strong class="text-hp-green font-bold">₱${formatPeso(amPrice)}</strong>
                    `;
                    payConfirmAmenitiesList.appendChild(item);
                });
            }
        }

        if (payConfirmAmenitiesSubtotal) {
            payConfirmAmenitiesSubtotal.textContent = `₱${formatPeso(amenitiesTotal)}`;
        }

        // 4b. Extra Head Fee Breakdown
        const { extraHeadTotal, extraHeadBreakdown } = calculateExtraHeadFee();
        const payConfirmExtraHeadWrap = document.getElementById('payConfirmExtraHeadWrap');
        const payConfirmExtraHeadSubtotal = document.getElementById('payConfirmExtraHeadSubtotal');
        const payConfirmExtraHeadList = document.getElementById('payConfirmExtraHeadList');

        if (payConfirmExtraHeadWrap) {
            if (extraHeadTotal > 0) {
                payConfirmExtraHeadWrap.style.display = 'grid';
                if (payConfirmExtraHeadSubtotal) {
                    payConfirmExtraHeadSubtotal.textContent = `₱${formatPeso(extraHeadTotal)}`;
                }
                if (payConfirmExtraHeadList) {
                    payConfirmExtraHeadList.innerHTML = extraHeadBreakdown.map(b => `
                        <div class="flex justify-between items-center border-b border-glass-border/20 pb-1">
                            <span>${b.amenity_name} (${b.excess} extra guest${b.excess > 1 ? 's' : ''} × ₱${formatPeso(b.add_rate)})</span>
                            <strong class="text-[#e65100] dark:text-[#ffb74d]">₱${formatPeso(b.fee)}</strong>
                        </div>
                    `).join('');
                }
            } else {
                payConfirmExtraHeadWrap.style.display = 'none';
            }
        }

        // 5. Grand Total
        const grandTotal = totalEntrance + amenitiesTotal + extraHeadTotal;
        const payConfirmGrandTotal = document.getElementById('payConfirmGrandTotal');
        if (payConfirmGrandTotal) {
            payConfirmGrandTotal.textContent = `₱${formatPeso(grandTotal)}`;
        }

        paymentConfirmModal.classList.add('is-open');
        paymentConfirmModal.classList.remove('hidden');
        paymentConfirmModal.setAttribute('aria-hidden', 'false');
    };

    const closePaymentConfirmModal = () => {
        if (!paymentConfirmModal) return;
        paymentConfirmModal.classList.remove('is-open');
        paymentConfirmModal.setAttribute('aria-hidden', 'true');
    };

    paymentCloseButtons.forEach(btn => btn.addEventListener('click', closePaymentConfirmModal));
    cancelPaymentBtn?.addEventListener('click', closePaymentConfirmModal);

    confirmPaymentBtn?.addEventListener('click', () => {
        isPaymentConfirmed = true;
        confirmPaymentBtn.disabled = true;
        confirmPaymentBtn.innerHTML = `
            <svg class="mr-2 h-4 w-4 inline animate-spin text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            Processing Check-In...
        `;
        addGuestForm?.submit();
    });

    // Form submit validation & sync
    const addGuestForm = document.getElementById('addGuestForm');
    addGuestForm?.addEventListener('submit', (e) => {
        if (!isPaymentConfirmed) {
            e.preventDefault();
            const primaryFirstName = document.getElementById('primary_first_name')?.value?.trim();
            const primaryLastName = document.getElementById('primary_last_name')?.value?.trim();

            if (!primaryFirstName || !primaryLastName) {
                alert('Please fill in the Primary Guest First Name and Last Name.');
                switchWalkInTab('mainGuestTab');
                checkMainGuestValidation();
                document.getElementById('primary_first_name')?.focus();
                return;
            }

            // Fill check_in hidden input
            const checkInInput = document.getElementById('check_in');
            if (checkInInput) {
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                checkInInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
            }

            updateGrandTotal();
            openPaymentConfirmModal();
        }
    });

    // Companion modal
    const companionModal = document.getElementById('companionModal');
    const companionCloseButtons = document.querySelectorAll('[data-close-companion-modal="true"]');
    const addCompanionBtn = document.getElementById('addCompanionBtn');
    const companionForm = document.getElementById('walkInCompanionForm') || document.getElementById('companionForm');
    const bulkCompanionForm = document.getElementById('walkInBulkCompanionForm') || document.getElementById('bulkCompanionForm');
    const companionList = document.getElementById('companionList');
    const companionHiddenFields = document.getElementById('companionHiddenFields');
    const companionAgeInput = document.getElementById('companion_age');
    const companionAgeTypeInput = document.getElementById('companion_age_type');
    const companionAgeComputedBadge = document.getElementById('companionAgeComputedBadge');

    // Companion age auto-type & badge sync listener
    const syncCompanionAgeBadge = () => {
        if (!companionAgeInput) return;
        const age = parseInt(companionAgeInput.value, 10);
        if (!isNaN(age) && age <= 12) {
            if (companionAgeTypeInput) companionAgeTypeInput.value = 'child';
            if (companionAgeComputedBadge) {
                companionAgeComputedBadge.textContent = 'Child Rate (0-12 yrs)';
                companionAgeComputedBadge.className = 'rounded-md border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-[0.7rem] font-bold text-amber-700 dark:text-amber-300';
            }
        } else {
            if (companionAgeTypeInput) companionAgeTypeInput.value = 'adult';
            if (companionAgeComputedBadge) {
                companionAgeComputedBadge.textContent = 'Adult Rate (13+ yrs)';
                companionAgeComputedBadge.className = 'rounded-md border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[0.7rem] font-bold text-emerald-700 dark:text-emerald-300';
            }
        }
    };
    companionAgeInput?.addEventListener('input', syncCompanionAgeBadge);

    let companions = [];
    let bulkCompanionGroups = [];

    // In-modal live preview elements
    const modalCompanionPreviewList = document.getElementById('modalCompanionPreviewList');
    const modalCompanionPreviewCountBadge = document.getElementById('modalCompanionPreviewCountBadge');
    const modalCompanionStagedCount = document.getElementById('modalCompanionStagedCount');
    const modalCompanionFooterSummary = document.getElementById('modalCompanionFooterSummary');
    const modalCompanionClearAllBtn = document.getElementById('modalCompanionClearAllBtn');
    const walkInCompanionCountBadge = document.getElementById('walkInCompanionCountBadge');

    const getTotalCompanionCount = () => {
        const singleCount = companions.length;
        const bulkCount = bulkCompanionGroups.reduce((acc, g) => acc + (parseInt(g.quantity, 10) || 0), 0);
        return singleCount + bulkCount;
    };

    // Modal Staged companion search and filter controls
    const toggleModalCompanionFilterBtn = document.getElementById('toggleModalCompanionFilterBtn');
    const modalCompanionFilterToolbar = document.getElementById('modalCompanionFilterToolbar');
    const modalCompanionSearchInput = document.getElementById('modalCompanionSearchInput');
    const modalCompanionFilterGender = document.getElementById('modalCompanionFilterGender');
    const modalCompanionFilterAgeGroup = document.getElementById('modalCompanionFilterAgeGroup');
    const modalCompanionFilterResetBtn = document.getElementById('modalCompanionFilterResetBtn');

    toggleModalCompanionFilterBtn?.addEventListener('click', () => {
        if (!modalCompanionFilterToolbar) return;
        const isHidden = modalCompanionFilterToolbar.classList.contains('hidden');
        if (isHidden) {
            modalCompanionFilterToolbar.classList.remove('hidden');
            modalCompanionFilterToolbar.classList.add('flex');
            toggleModalCompanionFilterBtn.classList.add('bg-hp-green/15', 'border-hp-green/40', 'text-hp-green');
            modalCompanionSearchInput?.focus();
        } else {
            modalCompanionFilterToolbar.classList.add('hidden');
            modalCompanionFilterToolbar.classList.remove('flex');
            toggleModalCompanionFilterBtn.classList.remove('bg-hp-green/15', 'border-hp-green/40', 'text-hp-green');
        }
    });

    modalCompanionSearchInput?.addEventListener('input', () => renderModalCompanionPreview());
    modalCompanionFilterGender?.addEventListener('change', () => renderModalCompanionPreview());
    modalCompanionFilterAgeGroup?.addEventListener('change', () => renderModalCompanionPreview());
    modalCompanionFilterResetBtn?.addEventListener('click', () => {
        if (modalCompanionSearchInput) modalCompanionSearchInput.value = '';
        if (modalCompanionFilterGender) modalCompanionFilterGender.value = '';
        if (modalCompanionFilterAgeGroup) modalCompanionFilterAgeGroup.value = '';
        renderModalCompanionPreview();
    });

    // Bulk quantity steppers in creator form
    const walkInBulkQtyMinusBtn = document.getElementById('walkInBulkQtyMinusBtn');
    const walkInBulkQtyPlusBtn = document.getElementById('walkInBulkQtyPlusBtn');

    walkInBulkQtyMinusBtn?.addEventListener('click', () => {
        if (!bulkCompanionQtyInput) return;
        const current = parseInt(bulkCompanionQtyInput.value, 10) || 1;
        if (current > 1) {
            bulkCompanionQtyInput.value = current - 1;
            syncBulkPoolQuantityMax();
            syncBulkFreeQuantityMax();
        }
    });

    walkInBulkQtyPlusBtn?.addEventListener('click', () => {
        if (!bulkCompanionQtyInput) return;
        const current = parseInt(bulkCompanionQtyInput.value, 10) || 1;
        if (current < 500) {
            bulkCompanionQtyInput.value = current + 1;
            syncBulkPoolQuantityMax();
            syncBulkFreeQuantityMax();
        }
    });

    const renderModalCompanionPreview = () => {
        if (!modalCompanionPreviewList) return;
        modalCompanionPreviewList.innerHTML = '';

        const totalCount = getTotalCompanionCount();
        const currentEntranceOpt = walkInEntranceOption?.value || 'all_paid';
        const currentPoolOpt = walkInPoolOption?.value || 'no_pool';

        if (modalCompanionPreviewCountBadge) {
            modalCompanionPreviewCountBadge.textContent = `${totalCount} companion${totalCount === 1 ? '' : 's'}`;
        }
        if (modalCompanionStagedCount) {
            modalCompanionStagedCount.textContent = `${totalCount}`;
        }
        if (modalCompanionFooterSummary) {
            modalCompanionFooterSummary.textContent = totalCount === 0 
                ? '0 companions added so far' 
                : `${totalCount} companion${totalCount === 1 ? '' : 's'} staged (Ready to apply)`;
        }
        if (modalCompanionClearAllBtn) {
            modalCompanionClearAllBtn.classList.toggle('hidden', totalCount === 0);
        }

        const searchTerm = (modalCompanionSearchInput?.value || '').trim().toLowerCase();
        const filterGender = (modalCompanionFilterGender?.value || '').trim();
        const filterAgeGroup = (modalCompanionFilterAgeGroup?.value || '').trim();

        if (modalCompanionFilterResetBtn) {
            const isFiltered = Boolean(searchTerm || filterGender || filterAgeGroup);
            modalCompanionFilterResetBtn.classList.toggle('hidden', !isFiltered);
            modalCompanionFilterResetBtn.classList.toggle('inline-flex', isFiltered);
        }

        let visibleModalCount = 0;

        // Render Single Companions in modal preview
        companions.forEach((companion, index) => {
            const fullName = `${companion.first_name || ''} ${companion.middle_name || ''} ${companion.last_name || ''}`.toLowerCase();
            const matchesSearch = !searchTerm || fullName.includes(searchTerm);
            const matchesGender = !filterGender || (companion.gender || '').toLowerCase() === filterGender.toLowerCase();

            let matchesAgeGroup = true;
            if (filterAgeGroup) {
                const parsedAge = parseInt(companion.age, 10);
                if (filterAgeGroup === '0-12') {
                    matchesAgeGroup = companion.age_type === 'child' || (!isNaN(parsedAge) && parsedAge <= 12);
                } else if (filterAgeGroup === '13-17') {
                    matchesAgeGroup = !isNaN(parsedAge) && parsedAge >= 13 && parsedAge <= 17;
                } else if (filterAgeGroup === '18-59') {
                    matchesAgeGroup = !isNaN(parsedAge) ? (parsedAge >= 18 && parsedAge <= 59) : companion.age_type === 'adult';
                } else if (filterAgeGroup === '60+') {
                    matchesAgeGroup = !isNaN(parsedAge) && parsedAge >= 60;
                }
            }

            if (!matchesSearch || !matchesGender || !matchesAgeGroup) {
                return;
            }
            visibleModalCount++;

            const nationality = companion.is_foreigner ? 'Foreigner' : 'Filipino';
            const rateLabel = companion.age_type === 'child' ? 'Child' : 'Adult';

            let poolBadgeHtml = '';
            if (currentPoolOpt === 'all_paid') {
                poolBadgeHtml = '<span class="inline-flex items-center gap-1 rounded bg-sky-500/15 text-sky-700 dark:text-sky-300 px-2 py-0.5 text-[0.65rem] font-bold"><i class="bi bi-water"></i> Pool Pass</span>';
            } else if (currentPoolOpt === 'all_free') {
                poolBadgeHtml = '<span class="inline-flex items-center gap-1 rounded bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[0.65rem] font-bold"><i class="bi bi-water"></i> Free Pool</span>';
            } else if (currentPoolOpt === 'specific') {
                poolBadgeHtml = companion.has_pool_access
                    ? `<button type="button" class="inline-flex items-center gap-1 rounded bg-sky-500/20 text-sky-800 dark:text-sky-300 border border-sky-500/30 px-2 py-0.5 text-[0.65rem] font-bold cursor-pointer hover:bg-sky-500/30 transition-colors" data-modal-toggle-pool="${index}" title="Click to remove pool pass"><i class="bi bi-water"></i> Pool Pass ✓</button>`
                    : `<button type="button" class="inline-flex items-center gap-1 rounded bg-gray-500/15 text-hp-text-muted border border-glass-border px-2 py-0.5 text-[0.65rem] font-medium cursor-pointer hover:bg-glass-hover transition-colors" data-modal-toggle-pool="${index}" title="Click to grant pool pass">+ Pool</button>`;
            }

            let freeBadgeHtml = '';
            if (currentEntranceOpt === 'all_free') {
                freeBadgeHtml = '<span class="inline-flex items-center gap-1 rounded bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 px-2 py-0.5 text-[0.65rem] font-bold"><i class="bi bi-ticket-perforated-fill"></i> Free Entrance</span>';
            } else if (currentEntranceOpt === 'specific') {
                freeBadgeHtml = companion.has_free_entrance
                    ? `<button type="button" class="inline-flex items-center gap-1 rounded bg-amber-500/20 text-amber-800 dark:text-amber-300 border border-amber-500/30 px-2 py-0.5 text-[0.65rem] font-bold cursor-pointer hover:bg-amber-500/30 transition-colors" data-modal-toggle-free="${index}" title="Click to remove free entrance"><i class="bi bi-ticket-perforated-fill"></i> Free Entrance ✓</button>`
                    : `<button type="button" class="inline-flex items-center gap-1 rounded bg-gray-500/15 text-hp-text-muted border border-glass-border px-2 py-0.5 text-[0.65rem] font-medium cursor-pointer hover:bg-glass-hover transition-colors" data-modal-toggle-free="${index}" title="Click to grant free entrance">+ Free</button>`;
            }

            let amenityBadgeHtml = '';
            if (selectedAmenities.length > 1 && companion.amenity_id) {
                const foundAm = selectedAmenities.find(a => String(a.amenity_id) === String(companion.amenity_id));
                if (foundAm) {
                    amenityBadgeHtml = `<span class="inline-flex items-center gap-1 rounded bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 px-1.5 py-0.5 text-[0.65rem] font-bold"><i class="bi bi-house-door-fill"></i> ${escapeHtml(foundAm.amenity_name)}</span>`;
                }
            }

            const item = document.createElement('div');
            item.className = 'group relative flex items-center justify-between gap-2.5 rounded-xl border border-glass-border bg-glass p-2.5 shadow-xs transition-all hover:border-hp-green/40 hover:bg-glass-hover';
            item.innerHTML = `
                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-hp-green/15 text-xs font-bold text-hp-green">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-xs font-bold text-hp-text dark:text-[#f3f4f6] truncate">${escapeHtml(companion.first_name)} ${escapeHtml(companion.last_name)}</span>
                            <span class="rounded bg-black/5 dark:bg-white/10 px-1.5 py-0.5 text-[0.65rem] font-medium text-hp-text-muted">${escapeHtml(companion.gender)}, ${companion.age ? escapeHtml(companion.age) + 'y (' + rateLabel + ')' : rateLabel}</span>
                            <span class="rounded bg-black/5 dark:bg-white/10 px-1.5 py-0.5 text-[0.65rem] font-medium text-hp-text-muted">${escapeHtml(nationality)}</span>
                            ${amenityBadgeHtml}
                            ${freeBadgeHtml}
                            ${poolBadgeHtml}
                        </div>
                    </div>
                </div>
                <button type="button" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-red-500/20 bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all duration-200 cursor-pointer shadow-xs active:scale-95" data-modal-remove-single="${index}" title="Remove staged companion">
                    <i class="bi bi-trash3 text-xs"></i>
                </button>
            `;
            modalCompanionPreviewList.appendChild(item);
        });

        // Render Bulk Companion Groups in modal preview
        bulkCompanionGroups.forEach((group, groupIndex) => {
            if (searchTerm) {
                return; // User is searching by single companion name
            }

            const matchesGender = !filterGender || (group.gender || '').toLowerCase() === filterGender.toLowerCase();
            const matchesAgeGroup = !filterAgeGroup || String(group.age_group || '') === filterAgeGroup;

            if (!matchesGender || !matchesAgeGroup) {
                return;
            }
            visibleModalCount++;

            const nationality = group.is_foreigner ? 'Foreigner' : 'Filipino';
            const rateLabel = (group.age_group === '0-12' || group.age_type === 'child') ? 'Child' : 'Adult';

            let bulkPoolBadgeHtml = '';
            if (currentPoolOpt === 'all_paid') {
                bulkPoolBadgeHtml = `<span class="inline-flex items-center gap-1 rounded bg-sky-500/15 text-sky-700 dark:text-sky-300 px-2 py-0.5 text-[0.65rem] font-bold"><i class="bi bi-water"></i> All with Pool</span>`;
            } else if (currentPoolOpt === 'all_free') {
                bulkPoolBadgeHtml = `<span class="inline-flex items-center gap-1 rounded bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[0.65rem] font-bold"><i class="bi bi-water"></i> All Free Pool</span>`;
            } else if (currentPoolOpt === 'specific') {
                const pQty = group.pool_quantity || 0;
                bulkPoolBadgeHtml = `
                    <div class="inline-flex items-center gap-1 rounded-lg bg-sky-500/15 border border-sky-500/30 px-1.5 py-0.5 text-[0.65rem] font-bold text-sky-800 dark:text-sky-300">
                        <i class="bi bi-water text-sky-600 dark:text-sky-400"></i>
                        <button type="button" class="flex h-4 w-4 items-center justify-center rounded bg-sky-600/20 text-sky-900 dark:text-white hover:bg-sky-600/40 text-[0.65rem] font-extrabold transition-colors cursor-pointer" data-modal-bulk-pool-dec="${groupIndex}">−</button>
                        <span class="px-1 min-w-[1.8rem] text-center font-bold text-[0.68rem]">${pQty} / ${group.quantity}</span>
                        <button type="button" class="flex h-4 w-4 items-center justify-center rounded bg-sky-600/20 text-sky-900 dark:text-white hover:bg-sky-600/40 text-[0.65rem] font-extrabold transition-colors cursor-pointer" data-modal-bulk-pool-inc="${groupIndex}">+</button>
                    </div>
                `;
            }

            let bulkFreeBadgeHtml = '';
            if (currentEntranceOpt === 'all_free') {
                bulkFreeBadgeHtml = `<span class="inline-flex items-center gap-1 rounded bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 px-1.5 py-0.5 text-[0.65rem] font-bold"><i class="bi bi-ticket-perforated-fill"></i> All ${group.quantity} Free</span>`;
            } else if (currentEntranceOpt === 'specific') {
                const fQty = Math.min(Math.max(0, parseInt(group.free_quantity, 10) || 0), group.quantity);
                group.free_quantity = fQty;
                bulkFreeBadgeHtml = `
                    <div class="inline-flex items-center gap-1 rounded-lg bg-amber-500/15 border border-amber-500/30 px-1.5 py-0.5 text-[0.65rem] font-bold text-amber-900 dark:text-amber-300">
                        <span><i class="bi bi-ticket-perforated-fill me-0.5"></i>Free:</span>
                        <button type="button" class="flex h-4 w-4 items-center justify-center rounded bg-amber-600/20 text-amber-900 dark:text-white hover:bg-amber-600/40 text-[0.65rem] font-extrabold transition-colors cursor-pointer" data-modal-bulk-free-dec="${groupIndex}">−</button>
                        <span class="px-1 min-w-[1.8rem] text-center font-bold text-[0.68rem]">${fQty} / ${group.quantity}</span>
                        <button type="button" class="flex h-4 w-4 items-center justify-center rounded bg-amber-600/20 text-amber-900 dark:text-white hover:bg-amber-600/40 text-[0.65rem] font-extrabold transition-colors cursor-pointer" data-modal-bulk-free-inc="${groupIndex}">+</button>
                    </div>
                `;
            }

            let bulkAmenityBadgeHtml = '';
            if (selectedAmenities.length > 1 && group.amenity_id) {
                const foundAm = selectedAmenities.find(a => String(a.amenity_id) === String(group.amenity_id));
                if (foundAm) {
                    bulkAmenityBadgeHtml = `<span class="inline-flex items-center gap-1 rounded bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 px-1.5 py-0.5 text-[0.65rem] font-bold"><i class="bi bi-house-door-fill"></i> ${escapeHtml(foundAm.amenity_name)}</span>`;
                }
            }

            const item = document.createElement('div');
            item.className = 'group relative flex items-center justify-between gap-2.5 rounded-xl border border-glass-border bg-glass p-2.5 shadow-xs transition-all hover:border-hp-green/40 hover:bg-glass-hover';
            item.innerHTML = `
                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-600/15 text-xs font-bold text-emerald-700 dark:text-emerald-300">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-xs font-bold text-hp-text dark:text-[#f3f4f6]">Bulk: ${group.quantity} guests</span>
                            <span class="rounded bg-black/5 dark:bg-white/10 px-1.5 py-0.5 text-[0.65rem] font-medium text-hp-text-muted">${escapeHtml(group.gender)}, Age ${escapeHtml(group.age_group)} (${rateLabel})</span>
                            <span class="rounded bg-black/5 dark:bg-white/10 px-1.5 py-0.5 text-[0.65rem] font-medium text-hp-text-muted">${escapeHtml(nationality)}</span>
                            ${bulkAmenityBadgeHtml}
                            ${bulkFreeBadgeHtml}
                            ${bulkPoolBadgeHtml}
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <div class="flex items-center rounded-lg border border-glass-border bg-glass p-0.5">
                        <button type="button" class="flex h-6 w-6 items-center justify-center rounded bg-black/5 dark:bg-white/10 text-xs font-extrabold text-hp-text hover:bg-black/10 transition-colors cursor-pointer" data-modal-bulk-qty-dec="${groupIndex}" title="Decrease quantity">−</button>
                        <input type="number" min="1" max="500" value="${group.quantity}" data-modal-bulk-qty-input="${groupIndex}" class="no-spinners w-9 border-0 bg-transparent text-center font-display text-xs font-bold text-hp-green-dark dark:text-hp-green focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" title="Type to change bulk quantity directly">
                        <button type="button" class="flex h-6 w-6 items-center justify-center rounded bg-black/5 dark:bg-white/10 text-xs font-extrabold text-hp-text hover:bg-black/10 transition-colors cursor-pointer" data-modal-bulk-qty-inc="${groupIndex}" title="Increase quantity">+</button>
                    </div>
                    <button type="button" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-red-500/20 bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all duration-200 cursor-pointer shadow-xs active:scale-95" data-modal-remove-bulk="${groupIndex}" title="Remove staged bulk group">
                        <i class="bi bi-trash3 text-xs"></i>
                    </button>
                </div>
            `;
            modalCompanionPreviewList.appendChild(item);
        });

        if (visibleModalCount === 0) {
            modalCompanionPreviewList.innerHTML = totalCount === 0
                ? '<div class="py-8 text-center text-xs text-hp-text-muted/70 italic"><i class="bi bi-person-plus text-2xl block mb-1 opacity-50"></i>No companions staged yet.<br>Fill the form on the left to add companions.</div>'
                : '<div class="py-6 text-center text-xs text-hp-text-muted/70 italic"><i class="bi bi-search text-xl block mb-1 opacity-50"></i>No staged companions match the search/filter criteria.</div>';
        }
    };

    const handleBulkQtyChange = (groupIndex, newQty) => {
        const parsed = parseInt(newQty, 10);
        if (isNaN(parsed) || parsed < 1) return;
        const clamped = Math.min(Math.max(1, parsed), 500);
        if (bulkCompanionGroups[groupIndex]) {
            const grp = bulkCompanionGroups[groupIndex];
            grp.quantity = clamped;
            const curEntranceOpt = walkInEntranceOption?.value || 'all_paid';
            const curPoolOpt = walkInPoolOption?.value || 'no_pool';
            if (curPoolOpt === 'all_paid' || curPoolOpt === 'all_free') {
                grp.pool_quantity = grp.quantity;
            } else {
                grp.pool_quantity = Math.min(grp.pool_quantity || 0, grp.quantity);
            }
            if (curEntranceOpt === 'all_free') {
                grp.free_quantity = grp.quantity;
            } else {
                grp.free_quantity = Math.min(grp.free_quantity || 0, grp.quantity);
            }
            renderCompanions();
            updateGrandTotal();
            renderModalCompanionPreview();
        }
    };

    const openCompanionModal = () => {
        // Setup Amenity select dropdowns for companion modals
        const singleAmWrap = document.getElementById('walkInSingleCompanionAmenityWrap');
        const singleAmSelect = document.getElementById('walkInCompanionAmenity');
        const bulkAmWrap = document.getElementById('walkInBulkCompanionAmenityWrap');
        const bulkAmSelect = document.getElementById('walkInBulkCompanionAmenity');

        if (selectedAmenities.length > 1) {
            let optionsHtml = '';
            selectedAmenities.forEach(am => {
                const max = (am.max_cap !== null && am.max_cap !== undefined && am.max_cap !== '') ? `Max: ${am.max_cap}` : 'No limit';
                const addFee = parseFloat(am.additional_per_head) > 0 ? ` (+₱${parseFloat(am.additional_per_head).toFixed(2)}/extra head)` : '';
                optionsHtml += `<option value="${am.amenity_id}">${escapeHtml(am.amenity_name)} (${max}${addFee})</option>`;
            });
            if (singleAmSelect) singleAmSelect.innerHTML = optionsHtml;
            if (bulkAmSelect) bulkAmSelect.innerHTML = optionsHtml;
            if (singleAmWrap) singleAmWrap.style.display = 'grid';
            if (bulkAmWrap) bulkAmWrap.style.display = 'grid';
        } else {
            if (singleAmWrap) singleAmWrap.style.display = 'none';
            if (bulkAmWrap) bulkAmWrap.style.display = 'none';
            if (selectedAmenities.length === 1) {
                const singleId = selectedAmenities[0].amenity_id;
                if (singleAmSelect) singleAmSelect.innerHTML = `<option value="${singleId}" selected>${escapeHtml(selectedAmenities[0].amenity_name)}</option>`;
                if (bulkAmSelect) bulkAmSelect.innerHTML = `<option value="${singleId}" selected>${escapeHtml(selectedAmenities[0].amenity_name)}</option>`;
            }
        }

        companionModal.classList.add('is-open');
        companionModal.classList.remove('hidden');
        companionModal.setAttribute('aria-hidden', 'false');
        syncCompanionAgeBadge();
        syncBulkPoolQuantityMax();
        syncPoolOptionUI();
        renderModalCompanionPreview();
    };

    const closeCompanionModal = () => {
        companionModal.classList.remove('is-open');
        companionModal.setAttribute('aria-hidden', 'true');
        // Reset forms
        companionForm?.reset();
        syncCompanionAgeBadge();
        bulkCompanionForm?.reset();
        syncBulkPoolQuantityMax();
    };

    addCompanionBtn?.addEventListener('click', () => {
        if (addCompanionBtn.disabled) return;
        addCompanionBtn.disabled = true;
        setTimeout(() => { addCompanionBtn.disabled = false; }, 1000);
        openCompanionModal();
    });

    companionCloseButtons.forEach(button => {
        button.addEventListener('click', closeCompanionModal);
    });

    // In-modal preview click listeners (Remove single, remove bulk, toggle pool, toggle free, inc/dec pool/free, inc/dec bulk qty)
    modalCompanionPreviewList?.addEventListener('click', (e) => {
        const removeSingleBtn = e.target.closest('[data-modal-remove-single]');
        if (removeSingleBtn) {
            const idx = parseInt(removeSingleBtn.dataset.modalRemoveSingle, 10);
            if (!isNaN(idx) && companions[idx]) {
                const comp = companions[idx];
                const name = comp ? `${comp.first_name} ${comp.last_name}` : 'this companion';
                openRemoveCompanionModal(
                    `Are you sure you want to remove <strong>${escapeHtml(name)}</strong> from staged companions?`,
                    () => {
                        companions.splice(idx, 1);
                        renderCompanions();
                        updateGrandTotal();
                        renderModalCompanionPreview();
                    }
                );
            }
            return;
        }

        const removeBulkBtn = e.target.closest('[data-modal-remove-bulk]');
        if (removeBulkBtn) {
            const bIdx = parseInt(removeBulkBtn.dataset.modalRemoveBulk, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const grp = bulkCompanionGroups[bIdx];
                const desc = grp ? `${grp.quantity} guests (${grp.gender}, Age ${grp.age_group})` : 'this bulk group';
                openRemoveCompanionModal(
                    `Are you sure you want to remove the staged bulk group with <strong>${escapeHtml(desc)}</strong>?`,
                    () => {
                        bulkCompanionGroups.splice(bIdx, 1);
                        renderCompanions();
                        updateGrandTotal();
                        renderModalCompanionPreview();
                    }
                );
            }
            return;
        }

        // Increase/Decrease Bulk Group Quantity inside modal preview
        const bulkQtyIncBtn = e.target.closest('[data-modal-bulk-qty-inc]');
        if (bulkQtyIncBtn) {
            const bIdx = parseInt(bulkQtyIncBtn.dataset.modalBulkQtyInc, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const grp = bulkCompanionGroups[bIdx];
                const curEntranceOpt = walkInEntranceOption?.value || 'all_paid';
                const curPoolOpt = walkInPoolOption?.value || 'no_pool';
                grp.quantity = (parseInt(grp.quantity, 10) || 1) + 1;
                if (curPoolOpt === 'all_paid' || curPoolOpt === 'all_free') {
                    grp.pool_quantity = grp.quantity;
                }
                if (curEntranceOpt === 'all_free') {
                    grp.free_quantity = grp.quantity;
                }
                renderCompanions();
                updateGrandTotal();
                renderModalCompanionPreview();
            }
            return;
        }

        const bulkQtyDecBtn = e.target.closest('[data-modal-bulk-qty-dec]');
        if (bulkQtyDecBtn) {
            const bIdx = parseInt(bulkQtyDecBtn.dataset.modalBulkQtyDec, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const grp = bulkCompanionGroups[bIdx];
                if ((grp.quantity || 1) > 1) {
                    grp.quantity -= 1;
                    grp.pool_quantity = Math.min(grp.pool_quantity || 0, grp.quantity);
                    grp.free_quantity = Math.min(grp.free_quantity || 0, grp.quantity);
                    renderCompanions();
                    updateGrandTotal();
                    renderModalCompanionPreview();
                }
            }
            return;
        }

        const togglePoolBtn = e.target.closest('[data-modal-toggle-pool]');
        if (togglePoolBtn) {
            const idx = parseInt(togglePoolBtn.dataset.modalTogglePool, 10);
            if (!isNaN(idx) && companions[idx]) {
                companions[idx].has_pool_access = !companions[idx].has_pool_access;
                renderCompanions();
                updateGrandTotal();
                renderModalCompanionPreview();
            }
            return;
        }

        const toggleFreeBtn = e.target.closest('[data-modal-toggle-free]');
        if (toggleFreeBtn) {
            const idx = parseInt(toggleFreeBtn.dataset.modalToggleFree, 10);
            if (!isNaN(idx) && companions[idx]) {
                companions[idx].has_free_entrance = !companions[idx].has_free_entrance;
                renderCompanions();
                updateGrandTotal();
                renderModalCompanionPreview();
            }
            return;
        }

        const bulkPoolIncBtn = e.target.closest('[data-modal-bulk-pool-inc]');
        if (bulkPoolIncBtn) {
            const bIdx = parseInt(bulkPoolIncBtn.dataset.modalBulkPoolInc, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const grp = bulkCompanionGroups[bIdx];
                if ((grp.pool_quantity || 0) < grp.quantity) {
                    grp.pool_quantity = (grp.pool_quantity || 0) + 1;
                    renderCompanions();
                    updateGrandTotal();
                    renderModalCompanionPreview();
                }
            }
            return;
        }

        const bulkPoolDecBtn = e.target.closest('[data-modal-bulk-pool-dec]');
        if (bulkPoolDecBtn) {
            const bIdx = parseInt(bulkPoolDecBtn.dataset.modalBulkPoolDec, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const grp = bulkCompanionGroups[bIdx];
                if ((grp.pool_quantity || 0) > 0) {
                    grp.pool_quantity = (grp.pool_quantity || 0) - 1;
                    renderCompanions();
                    updateGrandTotal();
                    renderModalCompanionPreview();
                }
            }
            return;
        }

        const bulkFreeIncBtn = e.target.closest('[data-modal-bulk-free-inc]');
        if (bulkFreeIncBtn) {
            const bIdx = parseInt(bulkFreeIncBtn.dataset.modalBulkFreeInc, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const grp = bulkCompanionGroups[bIdx];
                if ((grp.free_quantity || 0) < grp.quantity) {
                    grp.free_quantity = (grp.free_quantity || 0) + 1;
                    renderCompanions();
                    updateGrandTotal();
                    renderModalCompanionPreview();
                }
            }
            return;
        }

        const bulkFreeDecBtn = e.target.closest('[data-modal-bulk-free-dec]');
        if (bulkFreeDecBtn) {
            const bIdx = parseInt(bulkFreeDecBtn.dataset.modalBulkFreeDec, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const grp = bulkCompanionGroups[bIdx];
                if ((grp.free_quantity || 0) > 0) {
                    grp.free_quantity = (grp.free_quantity || 0) - 1;
                    renderCompanions();
                    updateGrandTotal();
                    renderModalCompanionPreview();
                }
            }
            return;
        }
    });

    // Listen for manual bulk quantity typing in staged modal preview
    modalCompanionPreviewList?.addEventListener('change', (e) => {
        const input = e.target.closest('[data-modal-bulk-qty-input]');
        if (input) {
            const bIdx = parseInt(input.dataset.modalBulkQtyInput, 10);
            handleBulkQtyChange(bIdx, input.value);
        }
    });

    modalCompanionClearAllBtn?.addEventListener('click', () => {
        if (companions.length === 0 && bulkCompanionGroups.length === 0) return;
        openRemoveCompanionModal(
            `Are you sure you want to <strong>clear all staged companions</strong>?`,
            () => {
                companions = [];
                bulkCompanionGroups = [];
                renderCompanions();
                updateGrandTotal();
                renderModalCompanionPreview();
            }
        );
    });

    // Tab switching for companion modal (Single vs Bulk)
    const companionTabs = document.querySelectorAll('[data-companion-tab]');
    const companionTabContents = document.querySelectorAll('[data-companion-content]');

    companionTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabType = tab.dataset.companionTab;

            // Update tab button styles
            companionTabs.forEach(t => {
                if (t.dataset.companionTab === tabType) {
                    t.classList.add('guest-form__tab--active', 'bg-hp-green', 'text-white', 'font-bold');
                    t.classList.remove('bg-transparent', 'text-hp-text', 'font-semibold');
                } else {
                    t.classList.remove('guest-form__tab--active', 'bg-hp-green', 'text-white', 'font-bold');
                    t.classList.add('bg-transparent', 'text-hp-text', 'font-semibold');
                }
            });

            // Update form section visibility
            companionTabContents.forEach(content => {
                if (content.dataset.companionContent === tabType) {
                    content.classList.add('guest-form--tab-content--active');
                    content.style.display = 'grid';
                } else {
                    content.classList.remove('guest-form--tab-content--active');
                    content.style.display = 'none';
                }
            });
        });
    });

    // Companion search and filter controls
    const companionSearchInput = document.getElementById('companionSearchInput');
    const companionFilterGender = document.getElementById('companionFilterGender');
    const companionFilterAgeGroup = document.getElementById('companionFilterAgeGroup');
    const companionFilterResetBtn = document.getElementById('companionFilterResetBtn');

    // Render companions
    const renderCompanions = () => {
        companionList.innerHTML = '';
        companionHiddenFields.innerHTML = '';
        const currentEntranceOpt = walkInEntranceOption?.value || 'all_paid';
        const currentPoolOpt = walkInPoolOption?.value || 'no_pool';

        const totalCount = getTotalCompanionCount();
        if (walkInCompanionCountBadge) {
            walkInCompanionCountBadge.textContent = totalCount === 0 
                ? '0 companions added' 
                : `${totalCount} companion${totalCount === 1 ? '' : 's'} added`;
        }
        const sidebarCompBadge = document.getElementById('walkInSidebarCompanionsBadge');
        if (sidebarCompBadge) {
            sidebarCompBadge.textContent = totalCount;
        }

        const searchTerm = (companionSearchInput?.value || '').trim().toLowerCase();
        const filterGender = (companionFilterGender?.value || '').trim();
        const filterAgeGroup = (companionFilterAgeGroup?.value || '').trim();

        if (companionFilterResetBtn) {
            const isFiltered = Boolean(searchTerm || filterGender || filterAgeGroup);
            companionFilterResetBtn.classList.toggle('hidden', !isFiltered);
            companionFilterResetBtn.classList.toggle('inline-flex', isFiltered);
        }

        let visibleCount = 0;

        // Render individual companions
        companions.forEach((companion, index) => {
            const nationality = companion.is_foreigner ? 'Foreigner' : 'Filipino';
            const rateLabel = companion.age_type === 'child' ? 'Child' : 'Adult';

            const hasPoolFlag = (currentPoolOpt === 'all_paid' || currentPoolOpt === 'all_free') ? '1' : (companion.has_pool_access ? '1' : '0');
            const isFreeEntranceFlag = (currentEntranceOpt === 'all_free') ? '1' : (currentEntranceOpt === 'specific' && companion.has_free_entrance ? '1' : '0');
            const companionAmenityVal = companion.amenity_id || (selectedAmenities[0]?.amenity_id || '');

            // ALWAYS inject hidden fields for all added companions
            companionHiddenFields.insertAdjacentHTML('beforeend', `
                <input type="hidden" name="companions[${index}][first_name]" value="${companion.first_name}">
                <input type="hidden" name="companions[${index}][middle_name]" value="${companion.middle_name || ''}">
                <input type="hidden" name="companions[${index}][last_name]" value="${companion.last_name}">
                <input type="hidden" name="companions[${index}][age]" value="${companion.age || ''}">
                <input type="hidden" name="companions[${index}][age_type]" value="${companion.age_type || 'adult'}">
                <input type="hidden" name="companions[${index}][gender]" value="${companion.gender || ''}">
                <input type="hidden" name="companions[${index}][is_foreigner]" value="${companion.is_foreigner ? '1' : '0'}">
                <input type="hidden" name="companions[${index}][phone]" value="${companion.phone || ''}">
                <input type="hidden" name="companions[${index}][email]" value="${companion.email || ''}">
                <input type="hidden" name="companions[${index}][has_pool_access]" value="${hasPoolFlag}">
                <input type="hidden" name="companions[${index}][is_free_entrance]" value="${isFreeEntranceFlag}">
                <input type="hidden" name="companions[${index}][amenity_id]" value="${companionAmenityVal}">
            `);

            // Apply search & gender filter for single companions
            const fullName = `${companion.first_name || ''} ${companion.middle_name || ''} ${companion.last_name || ''}`.toLowerCase();
            const matchesSearch = !searchTerm || fullName.includes(searchTerm);
            const matchesGender = !filterGender || (companion.gender || '').toLowerCase() === filterGender.toLowerCase();

            // If an age group filter is active, skip single companions that don't match child/adult or specific bounds
            let matchesAgeGroup = true;
            if (filterAgeGroup) {
                const parsedAge = parseInt(companion.age, 10);
                if (filterAgeGroup === '0-12') {
                    matchesAgeGroup = companion.age_type === 'child' || (!isNaN(parsedAge) && parsedAge <= 12);
                } else if (filterAgeGroup === '13-17') {
                    matchesAgeGroup = !isNaN(parsedAge) && parsedAge >= 13 && parsedAge <= 17;
                } else if (filterAgeGroup === '18-59') {
                    matchesAgeGroup = !isNaN(parsedAge) ? (parsedAge >= 18 && parsedAge <= 59) : companion.age_type === 'adult';
                } else if (filterAgeGroup === '60+') {
                    matchesAgeGroup = !isNaN(parsedAge) && parsedAge >= 60;
                }
            }

            if (!matchesSearch || !matchesGender || !matchesAgeGroup) {
                return;
            }
            visibleCount++;

            let poolBadgeHtml = '';
            if (currentPoolOpt === 'all_paid') {
                poolBadgeHtml = '<span class="inline-flex items-center gap-1 rounded bg-sky-500/15 text-sky-700 dark:text-sky-300 px-2 py-0.5 text-[0.7rem] font-bold"><i class="bi bi-water"></i> Pool Pass</span>';
            } else if (currentPoolOpt === 'all_free') {
                poolBadgeHtml = '<span class="inline-flex items-center gap-1 rounded bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[0.7rem] font-bold"><i class="bi bi-water"></i> Free Pool</span>';
            } else if (currentPoolOpt === 'specific') {
                poolBadgeHtml = companion.has_pool_access
                    ? `<button type="button" class="inline-flex items-center gap-1 rounded bg-sky-500/20 text-sky-800 dark:text-sky-300 border border-sky-500/30 px-2 py-0.5 text-[0.7rem] font-bold cursor-pointer hover:bg-sky-500/30 transition-colors" data-toggle-companion-pool="${index}" title="Click to remove pool access"><i class="bi bi-water"></i> Pool Pass <i class="bi bi-check-lg"></i></button>`
                    : `<button type="button" class="inline-flex items-center gap-1 rounded bg-gray-500/15 text-hp-text-muted border border-glass-border px-2 py-0.5 text-[0.7rem] font-medium cursor-pointer hover:bg-glass-hover transition-colors" data-toggle-companion-pool="${index}" title="Click to grant pool access">+ Pool Access</button>`;
            }

            let freeBadgeHtml = '';
            if (currentEntranceOpt === 'all_free') {
                freeBadgeHtml = '<span class="inline-flex items-center gap-1 rounded bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 px-2 py-0.5 text-[0.7rem] font-bold"><i class="bi bi-ticket-perforated-fill"></i> Free Entrance</span>';
            } else if (currentEntranceOpt === 'specific') {
                freeBadgeHtml = companion.has_free_entrance
                    ? `<button type="button" class="inline-flex items-center gap-1 rounded bg-amber-500/20 text-amber-800 dark:text-amber-300 border border-amber-500/30 px-2 py-0.5 text-[0.7rem] font-bold cursor-pointer hover:bg-amber-500/30 transition-colors" data-toggle-companion-free="${index}" title="Click to remove free entrance"><i class="bi bi-ticket-perforated-fill"></i> Free Entrance <i class="bi bi-check-lg"></i></button>`
                    : `<button type="button" class="inline-flex items-center gap-1 rounded bg-gray-500/15 text-hp-text-muted border border-glass-border px-2 py-0.5 text-[0.7rem] font-medium cursor-pointer hover:bg-glass-hover transition-colors" data-toggle-companion-free="${index}" title="Click to grant free entrance">+ Free Entrance</button>`;
            }

            let amenityBadgeHtml = '';
            if (selectedAmenities.length > 1 && companion.amenity_id) {
                const foundAm = selectedAmenities.find(a => String(a.amenity_id) === String(companion.amenity_id));
                if (foundAm) {
                    amenityBadgeHtml = `<span class="inline-flex items-center gap-1 rounded bg-hp-green/10 text-hp-green border border-hp-green/30 px-2 py-0.5 text-[0.7rem] font-bold"><i class="bi bi-house-door-fill"></i> ${escapeHtml(foundAm.amenity_name)}</span>`;
                }
            }

            const item = document.createElement('div');
            item.className = 'guest-companion-pill flex items-center justify-between gap-3 p-2.5 rounded-xl border border-glass-border bg-glass transition-all hover:border-hp-green/30';
            item.innerHTML = `
                <div class="flex items-center gap-2 flex-wrap min-w-0 flex-1">
                    <span class="guest-companion-pill__name text-sm font-medium text-hp-text truncate">${escapeHtml(companion.first_name)} ${escapeHtml(companion.last_name)} - ${nationality} - ${companion.age ? escapeHtml(companion.age) + ' yrs (' + rateLabel + ')' : rateLabel} - ${escapeHtml(companion.gender)}</span>
                    ${amenityBadgeHtml}
                    ${freeBadgeHtml}
                    ${poolBadgeHtml}
                </div>
                <button type="button" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl border border-red-500/20 bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all duration-200 cursor-pointer text-xs shadow-2xs" data-companion-index="${index}" title="Remove companion">
                    <i class="bi bi-trash3"></i>
                </button>
            `;
            companionList.appendChild(item);
        });

        // Render bulk companion groups
        bulkCompanionGroups.forEach((group, groupIndex) => {
            const nationality = group.is_foreigner ? 'Foreigner' : 'Filipino';
            const rateLabel = (group.age_group === '0-12' || group.age_type === 'child') ? 'Child' : 'Adult';
            const groupAmenityVal = group.amenity_id || (selectedAmenities[0]?.amenity_id || '');

            // ALWAYS inject hidden fields for all bulk group companions
            for (let i = 0; i < group.quantity; i++) {
                const companionIndex = companions.length + groupIndex * 1000 + i;
                const personHasPool = (currentPoolOpt === 'all_paid' || currentPoolOpt === 'all_free') || (currentPoolOpt === 'specific' && i < (group.pool_quantity || 0));
                const personIsFree = (currentEntranceOpt === 'all_free') || (currentEntranceOpt === 'specific' && i < (group.free_quantity || 0));

                companionHiddenFields.insertAdjacentHTML('beforeend', `
                    <input type="hidden" name="companions[${companionIndex}][first_name]" value="Companion">
                    <input type="hidden" name="companions[${companionIndex}][middle_name]" value="">
                    <input type="hidden" name="companions[${companionIndex}][last_name]" value="Guest">
                    <input type="hidden" name="companions[${companionIndex}][age_group]" value="${group.age_group}">
                    <input type="hidden" name="companions[${companionIndex}][age_type]" value="${group.age_type || (group.age_group === '0-12' ? 'child' : 'adult')}">
                    <input type="hidden" name="companions[${companionIndex}][gender]" value="${group.gender}">
                    <input type="hidden" name="companions[${companionIndex}][is_foreigner]" value="${group.is_foreigner ? '1' : '0'}">
                    <input type="hidden" name="companions[${companionIndex}][phone]" value="">
                    <input type="hidden" name="companions[${companionIndex}][email]" value="">
                    <input type="hidden" name="companions[${companionIndex}][has_pool_access]" value="${personHasPool ? '1' : '0'}">
                    <input type="hidden" name="companions[${companionIndex}][is_free_entrance]" value="${personIsFree ? '1' : '0'}">
                    <input type="hidden" name="companions[${companionIndex}][amenity_id]" value="${groupAmenityVal}">
                `);
            }

            // If user is searching by single companion name, hide bulk groups
            if (searchTerm) {
                return;
            }

            // Apply Gender and Age Group filters for bulk companions
            const matchesGender = !filterGender || (group.gender || '').toLowerCase() === filterGender.toLowerCase();
            const matchesAgeGroup = !filterAgeGroup || String(group.age_group || '') === filterAgeGroup;

            if (!matchesGender || !matchesAgeGroup) {
                return;
            }
            visibleCount++;

            let bulkPoolBadgeHtml = '';
            if (currentPoolOpt === 'all_paid') {
                bulkPoolBadgeHtml = `<span class="inline-flex items-center gap-1 rounded bg-sky-500/15 text-sky-700 dark:text-sky-300 px-2 py-0.5 text-[0.7rem] font-bold"><i class="bi bi-water"></i> All ${group.quantity} with Pool</span>`;
            } else if (currentPoolOpt === 'all_free') {
                bulkPoolBadgeHtml = `<span class="inline-flex items-center gap-1 rounded bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[0.7rem] font-bold"><i class="bi bi-water"></i> All ${group.quantity} Free Pool</span>`;
            } else if (currentPoolOpt === 'specific') {
                const pQty = group.pool_quantity || 0;
                bulkPoolBadgeHtml = `
                    <div class="inline-flex items-center gap-1 rounded-lg bg-sky-500/15 border border-sky-500/30 px-2 py-0.5 text-[0.72rem] font-bold text-sky-800 dark:text-sky-300">
                        <span><i class="bi bi-water me-1"></i>Pool:</span>
                        <button type="button" class="flex h-5 w-5 items-center justify-center rounded bg-sky-600/20 text-sky-900 dark:text-white hover:bg-sky-600/40 text-xs font-extrabold transition-colors cursor-pointer" data-bulk-pool-dec="${groupIndex}" title="Decrease pool access quantity">−</button>
                        <span class="px-1 min-w-[2.2rem] text-center font-bold text-xs">${pQty} / ${group.quantity}</span>
                        <button type="button" class="flex h-5 w-5 items-center justify-center rounded bg-sky-600/20 text-sky-900 dark:text-white hover:bg-sky-600/40 text-xs font-extrabold transition-colors cursor-pointer" data-bulk-pool-inc="${groupIndex}" title="Increase pool access quantity">+</button>
                    </div>
                `;
            }

            let bulkFreeBadgeHtml = '';
            if (currentEntranceOpt === 'all_free') {
                bulkFreeBadgeHtml = `<span class="inline-flex items-center gap-1 rounded bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 px-2 py-0.5 text-[0.7rem] font-bold"><i class="bi bi-ticket-perforated-fill"></i> All ${group.quantity} Free Entrance</span>`;
            } else if (currentEntranceOpt === 'specific') {
                const fQty = Math.min(Math.max(0, parseInt(group.free_quantity, 10) || 0), group.quantity);
                group.free_quantity = fQty;
                bulkFreeBadgeHtml = `
                    <div class="inline-flex items-center gap-1 rounded-lg bg-amber-500/15 border border-amber-500/30 px-2 py-0.5 text-[0.72rem] font-bold text-amber-900 dark:text-amber-300">
                        <span><i class="bi bi-ticket-perforated-fill me-1"></i>Free:</span>
                        <button type="button" class="flex h-5 w-5 items-center justify-center rounded bg-amber-600/20 text-amber-900 dark:text-white hover:bg-amber-600/40 text-xs font-extrabold transition-colors cursor-pointer" data-bulk-free-dec="${groupIndex}" title="Decrease free entrance quantity">−</button>
                        <span class="px-1 min-w-[2.2rem] text-center font-bold text-xs">${fQty} / ${group.quantity}</span>
                        <button type="button" class="flex h-5 w-5 items-center justify-center rounded bg-amber-600/20 text-amber-900 dark:text-white hover:bg-amber-600/40 text-xs font-extrabold transition-colors cursor-pointer" data-bulk-free-inc="${groupIndex}" title="Increase free entrance quantity">+</button>
                    </div>
                `;
            }

            let bulkAmenityBadgeHtml = '';
            if (selectedAmenities.length > 1 && group.amenity_id) {
                const foundAm = selectedAmenities.find(a => String(a.amenity_id) === String(group.amenity_id));
                if (foundAm) {
                    bulkAmenityBadgeHtml = `<span class="inline-flex items-center gap-1 rounded bg-hp-green/10 text-hp-green border border-hp-green/30 px-2 py-0.5 text-[0.7rem] font-bold"><i class="bi bi-house-door-fill"></i> ${escapeHtml(foundAm.amenity_name)}</span>`;
                }
            }

            const item = document.createElement('div');
            item.className = 'guest-companion-pill guest-companion-pill--bulk flex items-center justify-between gap-3 p-2.5 rounded-xl border border-glass-border bg-glass transition-all hover:border-hp-green/30';
            item.innerHTML = `
                <div class="flex items-center gap-2 flex-wrap min-w-0 flex-1">
                    <div class="inline-flex items-center gap-1 rounded-lg bg-teal-600/15 border border-teal-600/30 px-1.5 py-0.5 text-xs font-bold text-teal-800 dark:text-teal-300 shrink-0">
                        <span>Bulk:</span>
                        <button type="button" class="flex h-4 w-4 items-center justify-center rounded bg-teal-700/20 text-teal-900 dark:text-white hover:bg-teal-700/40 text-xs font-extrabold transition-colors cursor-pointer" data-bulk-qty-dec="${groupIndex}" title="Decrease group quantity">−</button>
                        <input type="number" min="1" max="500" value="${group.quantity}" data-bulk-qty-input="${groupIndex}" class="no-spinners w-12 text-center font-bold text-xs bg-white/90 dark:bg-[#1a1e1b] border border-teal-500/40 rounded px-1 py-0.5 text-hp-text focus:outline-none focus:border-hp-green shadow-inner [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" style="-webkit-appearance: none; -moz-appearance: textfield; appearance: textfield; margin: 0;" title="Type any quantity">
                        <button type="button" class="flex h-4 w-4 items-center justify-center rounded bg-teal-700/20 text-teal-900 dark:text-white hover:bg-teal-700/40 text-xs font-extrabold transition-colors cursor-pointer" data-bulk-qty-inc="${groupIndex}" title="Increase group quantity">+</button>
                    </div>
                    <span class="guest-companion-pill__name text-sm font-medium text-hp-text truncate">${escapeHtml(group.gender)} - ${nationality} - Age Group: ${group.age_group} (${rateLabel})</span>
                    ${bulkAmenityBadgeHtml}
                    ${bulkFreeBadgeHtml}
                    ${bulkPoolBadgeHtml}
                </div>
                <button type="button" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl border border-red-500/20 bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all duration-200 cursor-pointer text-xs shadow-2xs" data-bulk-index="${groupIndex}" title="Remove bulk group">
                    <i class="bi bi-trash3"></i>
                </button>
            `;
            companionList.appendChild(item);
        });

        if (companions.length === 0 && bulkCompanionGroups.length === 0) {
            companionList.innerHTML = '<p class="guest-empty text-xs text-hp-text-muted italic py-3 text-center">No companions added yet.</p>';
        } else if (visibleCount === 0) {
            companionList.innerHTML = '<p class="guest-empty text-xs text-hp-text-muted italic py-3 text-center">No companions match your search or filter criteria.</p>';
        }
    };

    // Companion filter toolbar toggle button
    const toggleCompanionFilterBtn = document.getElementById('toggleCompanionFilterBtn');
    const companionFilterToolbar = document.getElementById('companionFilterToolbar');

    toggleCompanionFilterBtn?.addEventListener('click', () => {
        if (!companionFilterToolbar) return;
        const isHidden = companionFilterToolbar.classList.contains('hidden');
        if (isHidden) {
            companionFilterToolbar.classList.remove('hidden');
            companionFilterToolbar.classList.add('flex');
            toggleCompanionFilterBtn.classList.add('bg-hp-green/15', 'border-hp-green/40', 'text-hp-green');
            companionSearchInput?.focus();
        } else {
            companionFilterToolbar.classList.add('hidden');
            companionFilterToolbar.classList.remove('flex');
            toggleCompanionFilterBtn.classList.remove('bg-hp-green/15', 'border-hp-green/40', 'text-hp-green');
        }
    });

    // Attach search and filter event listeners
    companionSearchInput?.addEventListener('input', renderCompanions);
    companionFilterGender?.addEventListener('change', renderCompanions);
    companionFilterAgeGroup?.addEventListener('change', renderCompanions);
    companionFilterResetBtn?.addEventListener('click', () => {
        if (companionSearchInput) companionSearchInput.value = '';
        if (companionFilterGender) companionFilterGender.value = '';
        if (companionFilterAgeGroup) companionFilterAgeGroup.value = '';
        renderCompanions();
    });

    // Duplicate companion warning modal controls
    const duplicateCompanionModal = document.getElementById('duplicateCompanionModal');
    const duplicateCompanionMessage = document.getElementById('duplicateCompanionMessage');

    const showDuplicateCompanionModal = (message) => {
        if (duplicateCompanionMessage && message) {
            duplicateCompanionMessage.textContent = message;
        }
        if (duplicateCompanionModal) {
            duplicateCompanionModal.classList.add('is-open');
            duplicateCompanionModal.classList.remove('hidden');
            duplicateCompanionModal.setAttribute('aria-hidden', 'false');
        }
    };

    const closeDuplicateCompanionModal = () => {
        if (duplicateCompanionModal) {
            duplicateCompanionModal.classList.remove('is-open');
            duplicateCompanionModal.classList.add('hidden');
            duplicateCompanionModal.setAttribute('aria-hidden', 'true');
        }
    };

    document.querySelectorAll('[data-close-duplicate-modal="true"]').forEach(btn => {
        btn.addEventListener('click', closeDuplicateCompanionModal);
    });

    // Remove Companion Confirmation Modal Controls
    const removeCompanionConfirmModal = document.getElementById('removeCompanionConfirmModal');
    const removeCompanionModalMessage = document.getElementById('removeCompanionModalMessage');
    const confirmRemoveCompanionBtn = document.getElementById('confirmRemoveCompanionBtn');
    let pendingRemoveAction = null;

    const openRemoveCompanionModal = (messageHtml, onConfirm) => {
        if (!removeCompanionConfirmModal) {
            if (typeof onConfirm === 'function') onConfirm();
            return;
        }
        if (removeCompanionModalMessage && messageHtml) {
            removeCompanionModalMessage.innerHTML = messageHtml;
        }
        pendingRemoveAction = onConfirm;
        removeCompanionConfirmModal.classList.add('is-open');
        removeCompanionConfirmModal.classList.remove('hidden');
        removeCompanionConfirmModal.setAttribute('aria-hidden', 'false');
    };

    const closeRemoveCompanionModal = () => {
        if (!removeCompanionConfirmModal) return;
        removeCompanionConfirmModal.classList.remove('is-open');
        removeCompanionConfirmModal.classList.add('hidden');
        removeCompanionConfirmModal.setAttribute('aria-hidden', 'true');
        pendingRemoveAction = null;
    };

    document.querySelectorAll('[data-close-remove-companion-modal="true"]').forEach(btn => {
        btn.addEventListener('click', closeRemoveCompanionModal);
    });

    confirmRemoveCompanionBtn?.addEventListener('click', () => {
        if (typeof pendingRemoveAction === 'function') {
            pendingRemoveAction();
        }
        closeRemoveCompanionModal();
    });

    // Single companion form submission with duplicate validation & 1s anti-double-click lock
    companionForm?.addEventListener('submit', (e) => {
        e.preventDefault();

        const formData = new FormData(companionForm);
        const firstName = (formData.get('first_name') || '').trim();
        const middleName = (formData.get('middle_name') || '').trim();
        const lastName = (formData.get('last_name') || '').trim();
        const ageVal = (formData.get('age') || '').trim();
        const parsedAge = parseInt(ageVal, 10);
        const autoAgeType = (!isNaN(parsedAge) && parsedAge <= 12) ? 'child' : 'adult';
        const genderVal = (formData.get('gender') || '').trim();
        const isForeignerVal = formData.get('is_foreigner') === '1';
        const currentEntranceOpt = walkInEntranceOption?.value || 'all_paid';
        const currentPoolOpt = walkInPoolOption?.value || 'no_pool';
        const chosenAmenityId = String(formData.get('amenity_id') || selectedAmenities[0]?.amenity_id || '');

        // Check if matching companion already exists in single companions
        const isDuplicateCompanion = companions.some(c => {
            const matchFirst = (c.first_name || '').trim().toLowerCase() === firstName.toLowerCase();
            const matchMiddle = (c.middle_name || '').trim().toLowerCase() === middleName.toLowerCase();
            const matchLast = (c.last_name || '').trim().toLowerCase() === lastName.toLowerCase();
            const matchGender = (c.gender || '').trim().toLowerCase() === genderVal.toLowerCase();
            const matchAge = String(c.age || '').trim() === ageVal;
            const matchNationality = Boolean(c.is_foreigner) === isForeignerVal;

            return matchFirst && matchMiddle && matchLast && matchGender && matchAge && matchNationality;
        });

        // Check if matching companion matches the primary walk-in guest
        const primaryFirst = (document.getElementById('primary_first_name')?.value || '').trim().toLowerCase();
        const primaryMiddle = (document.getElementById('primary_middle_name')?.value || '').trim().toLowerCase();
        const primaryLast = (document.getElementById('primary_last_name')?.value || '').trim().toLowerCase();
        const primaryGender = (document.getElementById('primary_gender')?.value || '').trim().toLowerCase();
        const primaryAge = String(document.getElementById('primary_age')?.value || '').trim();
        const primaryNationality = (document.getElementById('primaryIsForeigner')?.value || '0') === '1';

        const isDuplicatePrimary = firstName && lastName &&
            primaryFirst === firstName.toLowerCase() &&
            primaryMiddle === middleName.toLowerCase() &&
            primaryLast === lastName.toLowerCase() &&
            primaryGender === genderVal.toLowerCase() &&
            primaryAge === ageVal &&
            primaryNationality === isForeignerVal;

        if (isDuplicateCompanion || isDuplicatePrimary) {
            const displayFullName = `${firstName} ${middleName ? middleName + ' ' : ''}${lastName}`.trim() || 'Companion';
            showDuplicateCompanionModal(`A companion or guest named "${displayFullName}" with identical age (${ageVal || 'N/A'}), gender (${genderVal || 'N/A'}), and nationality is already added.`);
            return;
        }

        const submitBtn = companionForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            if (submitBtn.disabled) return;
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'pointer-events-none');
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'pointer-events-none');
            }, 1000);
        }

        const companionHasPool = (currentPoolOpt === 'all_paid' || currentPoolOpt === 'all_free')
            ? true
            : (currentPoolOpt === 'specific' ? (formData.get('has_pool_access') === '1' || companionHasPoolInput?.checked) : false);

        const isFreeEntrance = (currentEntranceOpt === 'all_free')
            ? true
            : (currentEntranceOpt === 'specific' ? (formData.get('is_free_entrance') === '1' || Boolean(document.getElementById('companion_is_free_entrance')?.checked)) : false);

        const companionData = {
            first_name: firstName || 'Companion',
            middle_name: middleName,
            last_name: lastName || 'Guest',
            age: ageVal,
            age_type: autoAgeType,
            gender: genderVal || 'Male',
            is_foreigner: isForeignerVal,
            phone: formData.get('phone'),
            email: formData.get('email'),
            has_pool_access: companionHasPool,
            has_free_entrance: isFreeEntrance,
            amenity_id: chosenAmenityId,
        };

        companions.push(companionData);
        renderCompanions();
        updateGrandTotal();
        renderModalCompanionPreview();
        companionForm.reset();
        syncCompanionAgeBadge();
    });

    // Bulk companion form submission with duplicate validation & 1s anti-double-click lock
    bulkCompanionForm?.addEventListener('submit', (e) => {
        e.preventDefault();

        const formData = new FormData(bulkCompanionForm);
        const gender = (formData.get('gender') || 'Male').trim();
        const ageGroup = (formData.get('age_group') || '18-59').trim();
        const isForeigner = formData.get('is_foreigner') === '1';
        const quantity = parseInt(formData.get('quantity'), 10) || 1;
        const ageType = (ageGroup === '0-12') ? 'child' : 'adult';
        const currentEntranceOpt = walkInEntranceOption?.value || 'all_paid';
        const currentPoolOpt = walkInPoolOption?.value || 'no_pool';
        const chosenAmenityId = String(formData.get('amenity_id') || selectedAmenities[0]?.amenity_id || '');

        // Check if matching bulk companion group already exists
        const isDuplicateBulk = bulkCompanionGroups.some(g => {
            const matchGender = (g.gender || '').trim().toLowerCase() === gender.toLowerCase();
            const matchAgeGroup = (g.age_group || '').trim().toLowerCase() === ageGroup.toLowerCase();
            const matchNationality = Boolean(g.is_foreigner) === isForeigner;
            const matchAmenity = selectedAmenities.length > 1 ? (String(g.amenity_id || '') === chosenAmenityId) : true;

            return matchGender && matchAgeGroup && matchNationality && matchAmenity;
        });

        if (isDuplicateBulk) {
            const nationalityLabel = isForeigner ? 'Foreigner' : 'Filipino';
            showDuplicateCompanionModal(`A bulk group with the same demographics (${gender}, Age Group: ${ageGroup}, ${nationalityLabel}) already exists. You can adjust its quantity directly in the preview list on the right.`);
            return;
        }

        const submitBtn = bulkCompanionForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            if (submitBtn.disabled) return;
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'pointer-events-none');
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'pointer-events-none');
            }, 1000);
        }

        const rawPoolQty = parseInt(formData.get('pool_access_quantity'), 10) || 0;
        let poolQty = Math.min(Math.max(0, rawPoolQty), quantity);
        if (currentPoolOpt === 'all_paid' || currentPoolOpt === 'all_free') {
            poolQty = quantity;
        } else if (currentPoolOpt === 'no_pool') {
            poolQty = 0;
        }

        const rawFreeQty = parseInt(formData.get('free_entrance_quantity'), 10) || 0;
        let freeQty = Math.min(Math.max(0, rawFreeQty), quantity);
        if (currentEntranceOpt === 'all_free') {
            freeQty = quantity;
        } else if (currentEntranceOpt === 'all_paid') {
            freeQty = 0;
        }

        bulkCompanionGroups.push({
            gender,
            age_group: ageGroup,
            age_type: ageType,
            is_foreigner: isForeigner,
            quantity: quantity,
            pool_quantity: poolQty,
            free_quantity: freeQty,
            has_free_entrance: freeQty === quantity,
            amenity_id: chosenAmenityId,
        });

        renderCompanions();
        updateGrandTotal();
        renderModalCompanionPreview();
        bulkCompanionForm.reset();
        syncBulkPoolQuantityMax();
        syncBulkFreeQuantityMax();
    });

    // Delete / Toggle companion handlers (both single & bulk)
    companionList?.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('[data-companion-index]') || e.target.closest('[data-bulk-index]') || e.target.closest('.guest-companion-pill__delete');
        if (removeBtn) {
            const index = removeBtn.dataset.companionIndex;
            const bulkIndex = removeBtn.dataset.bulkIndex;

            if (index !== undefined && index !== null && index !== '') {
                const idx = parseInt(index, 10);
                if (!isNaN(idx) && companions[idx]) {
                    const comp = companions[idx];
                    const name = comp ? `${comp.first_name} ${comp.last_name}` : 'this companion';
                    openRemoveCompanionModal(
                        `Are you sure you want to remove <strong>${escapeHtml(name)}</strong> from the companion list?`,
                        () => {
                            companions.splice(idx, 1);
                            renderCompanions();
                            updateGrandTotal();
                            renderModalCompanionPreview();
                        }
                    );
                }
            } else if (bulkIndex !== undefined && bulkIndex !== null && bulkIndex !== '') {
                const bIdx = parseInt(bulkIndex, 10);
                if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                    const grp = bulkCompanionGroups[bIdx];
                    const desc = grp ? `${grp.quantity} guests (${grp.gender}, Age ${grp.age_group})` : 'this bulk group';
                    openRemoveCompanionModal(
                        `Are you sure you want to remove the bulk companion group with <strong>${escapeHtml(desc)}</strong>?`,
                        () => {
                            bulkCompanionGroups.splice(bIdx, 1);
                            renderCompanions();
                            updateGrandTotal();
                            renderModalCompanionPreview();
                        }
                    );
                }
            }
            return;
        }

        // Click-to-increase bulk group quantity
        const bulkQtyIncBtn = e.target.closest('[data-bulk-qty-inc]');
        if (bulkQtyIncBtn) {
            const bIdx = parseInt(bulkQtyIncBtn.dataset.bulkQtyInc, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const group = bulkCompanionGroups[bIdx];
                const curEntranceOpt = walkInEntranceOption?.value || 'all_paid';
                const curPoolOpt = walkInPoolOption?.value || 'no_pool';
                group.quantity = (parseInt(group.quantity, 10) || 1) + 1;
                if (curPoolOpt === 'all_paid' || curPoolOpt === 'all_free') {
                    group.pool_quantity = group.quantity;
                }
                if (curEntranceOpt === 'all_free') {
                    group.free_quantity = group.quantity;
                }
                renderCompanions();
                updateGrandTotal();
                renderModalCompanionPreview();
            }
            return;
        }

        // Click-to-decrease bulk group quantity
        const bulkQtyDecBtn = e.target.closest('[data-bulk-qty-dec]');
        if (bulkQtyDecBtn) {
            const bIdx = parseInt(bulkQtyDecBtn.dataset.bulkQtyDec, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const group = bulkCompanionGroups[bIdx];
                if ((group.quantity || 1) > 1) {
                    group.quantity -= 1;
                    group.pool_quantity = Math.min(group.pool_quantity || 0, group.quantity);
                    group.free_quantity = Math.min(group.free_quantity || 0, group.quantity);
                    renderCompanions();
                    updateGrandTotal();
                    renderModalCompanionPreview();
                }
            }
            return;
        }

        // Click-to-toggle pool pass on single companion pill
        const togglePoolBtn = e.target.closest('[data-toggle-companion-pool]');
        if (togglePoolBtn) {
            const cIdx = parseInt(togglePoolBtn.dataset.toggleCompanionPool, 10);
            if (!isNaN(cIdx) && companions[cIdx]) {
                companions[cIdx].has_pool_access = !companions[cIdx].has_pool_access;
                renderCompanions();
                updateGrandTotal();
                renderModalCompanionPreview();
            }
            return;
        }

        // Click-to-toggle free entrance on single companion pill
        const toggleFreeBtn = e.target.closest('[data-toggle-companion-free]');
        if (toggleFreeBtn) {
            const cIdx = parseInt(toggleFreeBtn.dataset.toggleCompanionFree, 10);
            if (!isNaN(cIdx) && companions[cIdx]) {
                companions[cIdx].has_free_entrance = !companions[cIdx].has_free_entrance;
                renderCompanions();
                updateGrandTotal();
                renderModalCompanionPreview();
            }
            return;
        }

        // Click-to-increase bulk free entrance
        const bulkFreeIncBtn = e.target.closest('[data-bulk-free-inc]');
        if (bulkFreeIncBtn) {
            const bIdx = parseInt(bulkFreeIncBtn.dataset.bulkFreeInc, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const group = bulkCompanionGroups[bIdx];
                const cur = group.free_quantity || 0;
                if (cur < group.quantity) {
                    group.free_quantity = cur + 1;
                    renderCompanions();
                    updateGrandTotal();
                    renderModalCompanionPreview();
                }
            }
            return;
        }

        // Click-to-decrease bulk free entrance
        const bulkFreeDecBtn = e.target.closest('[data-bulk-free-dec]');
        if (bulkFreeDecBtn) {
            const bIdx = parseInt(bulkFreeDecBtn.dataset.bulkFreeDec, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const group = bulkCompanionGroups[bIdx];
                const cur = group.free_quantity || 0;
                if (cur > 0) {
                    group.free_quantity = cur - 1;
                    renderCompanions();
                    updateGrandTotal();
                    renderModalCompanionPreview();
                }
            }
            return;
        }

        // Click-to-increase bulk pool access
        const bulkIncBtn = e.target.closest('[data-bulk-pool-inc]');
        if (bulkIncBtn) {
            const bIdx = parseInt(bulkIncBtn.dataset.bulkPoolInc, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const group = bulkCompanionGroups[bIdx];
                const cur = group.pool_quantity || 0;
                if (cur < group.quantity) {
                    group.pool_quantity = cur + 1;
                    renderCompanions();
                    updateGrandTotal();
                    renderModalCompanionPreview();
                }
            }
            return;
        }

        // Click-to-decrease bulk pool access
        const bulkDecBtn = e.target.closest('[data-bulk-pool-dec]');
        if (bulkDecBtn) {
            const bIdx = parseInt(bulkDecBtn.dataset.bulkPoolDec, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const group = bulkCompanionGroups[bIdx];
                const cur = group.pool_quantity || 0;
                if (cur > 0) {
                    group.pool_quantity = cur - 1;
                    renderCompanions();
                    updateGrandTotal();
                    renderModalCompanionPreview();
                }
            }
            return;
        }
    });

    // Listen for manual bulk quantity typing on main companion list
    companionList?.addEventListener('change', (e) => {
        const input = e.target.closest('[data-bulk-qty-input]');
        if (input) {
            const bIdx = parseInt(input.dataset.bulkQtyInput, 10);
            handleBulkQtyChange(bIdx, input.value);
        }
    });

    primaryAgeInput?.addEventListener('change', updateGrandTotal);

    // ==========================================
    // Walk-In Range Calendar Modal Logic
    // ==========================================
    const walkInCalendarModal = document.getElementById('walkInCalendarModal');
    const walkInOpenCalendarBtn = document.getElementById('walkInOpenCalendarBtn');
    const walkInCalCloseButtons = document.querySelectorAll('[data-close-walkin-calendar="true"]');
    const walkInCalPrev = document.getElementById('walkInCalPrev');
    const walkInCalNext = document.getElementById('walkInCalNext');
    const walkInCalTitle = document.getElementById('walkInCalTitle');
    const walkInCalYear = document.getElementById('walkInCalYear');
    const walkInCalGrid = document.getElementById('walkInCalGrid');
    const walkInCalSummaryText = document.getElementById('walkInCalSummaryText');
    const walkInCalSpanText = document.getElementById('walkInCalSpanText');
    const walkInCalStepHelp = document.getElementById('walkInCalStepHelp');
    const walkInCalApplyBtn = document.getElementById('walkInCalApplyBtn');
    const walkInCalCurrentBadge = document.getElementById('walkInCalCurrentBadge');
    const walkInTopCheckoutTimeBadge = document.getElementById('walkInTopCheckoutTimeBadge');
    const walkInCalCheckoutDateText = document.getElementById('walkInCalCheckoutDateText');
    const walkInCalCheckoutSessionBadge = document.getElementById('walkInCalCheckoutSessionBadge');
    const walkInCalCheckoutSessionIcon = document.getElementById('walkInCalCheckoutSessionIcon');
    const walkInCalCheckoutSessionText = document.getElementById('walkInCalCheckoutSessionText');
    const walkInCalStartSlotText = document.getElementById('walkInCalStartSlotText');
    const walkInCalSpanDetailText = document.getElementById('walkInCalSpanDetailText');
    const walkInCalDaysBadgeText = document.getElementById('walkInCalDaysBadgeText');

    const walkInCalState = {
        viewYear: new Date().getFullYear(),
        viewMonth: new Date().getMonth(),
        selectedStartDate: todayStr,
        selectedEndDate: todayStr,
        selectedStartSlot: currentServerSession,
        selectedEndSlot: currentServerSession,
    };

    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    // Populate Year Dropdown (current year up to +5 years)
    const initWalkInCalYears = () => {
        if (!walkInCalYear) return;
        const currentYear = new Date().getFullYear();
        walkInCalYear.innerHTML = '';
        for (let y = currentYear; y <= currentYear + 5; y++) {
            const opt = document.createElement('option');
            opt.value = y;
            opt.textContent = y;
            walkInCalYear.appendChild(opt);
        }
    };
    initWalkInCalYears();

    const openWalkInCalendarModal = () => {
        if (!walkInCalendarModal) return;
        // Walk-in check-in is ALWAYS locked to TODAY and CURRENT SESSION
        walkInCalState.selectedStartDate = todayStr;
        walkInCalState.selectedStartSlot = currentServerSession;
        walkInCalState.selectedEndDate = walkInSchedule.endDate || todayStr;
        walkInCalState.selectedEndSlot = walkInSchedule.endSlot || currentServerSession;

        // If checkin is today nighttime and selected checkout is today, force nighttime checkout
        if (walkInCalState.selectedStartDate === walkInCalState.selectedEndDate && walkInCalState.selectedStartSlot === 'Nighttime') {
            walkInCalState.selectedEndSlot = 'Nighttime';
        }

        const [y, m] = walkInCalState.selectedStartDate.split('-');
        walkInCalState.viewYear = parseInt(y);
        walkInCalState.viewMonth = parseInt(m) - 1;

        walkInCalendarModal.classList.add('is-open');
        walkInCalendarModal.setAttribute('aria-hidden', 'false');

        syncWalkInSessionPills();
        renderWalkInCalendarMonth();
    };

    const closeWalkInCalendarModal = () => {
        if (!walkInCalendarModal) return;
        walkInCalendarModal.classList.remove('is-open');
        walkInCalendarModal.setAttribute('aria-hidden', 'true');
    };

    walkInOpenCalendarBtn?.addEventListener('click', openWalkInCalendarModal);
    walkInCalCloseButtons.forEach(btn => btn.addEventListener('click', closeWalkInCalendarModal));

    // Session pills toggle
    const syncWalkInSessionPills = () => {
        const isSameDayNighttime = (walkInCalState.selectedStartDate === walkInCalState.selectedEndDate && walkInCalState.selectedStartSlot === 'Nighttime');

        document.querySelectorAll('#walkInEndSlotGroup [data-slot-val]').forEach(btn => {
            const val = btn.dataset.slotVal;
            const isActive = (val === walkInCalState.selectedEndSlot);
            btn.dataset.active = isActive ? 'true' : 'false';
            btn.classList.toggle('is-active', isActive);

            if (isSameDayNighttime && val === 'Daytime') {
                btn.disabled = true;
                btn.classList.add('is-disabled');
                btn.title = 'Daytime checkout not available for same-day nighttime check-in';
            } else {
                btn.disabled = false;
                btn.classList.remove('is-disabled');
                btn.removeAttribute('title');
            }
        });
    };

    document.querySelectorAll('#walkInEndSlotGroup [data-slot-val]').forEach(btn => {
        btn.addEventListener('click', () => {
            const val = btn.dataset.slotVal;
            // If same day and checkin is Nighttime, cannot pick Daytime checkout
            if (walkInCalState.selectedStartDate === walkInCalState.selectedEndDate && walkInCalState.selectedStartSlot === 'Nighttime' && val === 'Daytime') {
                return;
            }
            walkInCalState.selectedEndSlot = val;
            syncWalkInSessionPills();
            updateWalkInCalModalSummary();
        });
    });

    const updateWalkInCalModalSummary = () => {
        const counts = calculateWalkInSlots(walkInCalState.selectedStartDate, walkInCalState.selectedEndDate, walkInCalState.selectedStartSlot, walkInCalState.selectedEndSlot);
        const sFmt = formatDisplayDate(walkInCalState.selectedStartDate);
        const checkout = getCheckoutDateAndDisplay(walkInCalState.selectedEndDate, walkInCalState.selectedEndSlot);
        const spanLabel = counts.daysSpan === 1 ? '1 Day' : `${counts.daysSpan} Days`;

        // Update prominent top summary cards
        const walkInTopCheckInText = document.getElementById('walkInTopCheckInText');
        const walkInTopCheckoutFullText = document.getElementById('walkInTopCheckoutFullText');
        const inTime = walkInCalState.selectedStartSlot === 'Nighttime' ? 'Nighttime (8:00 PM)' : 'Daytime (8:00 AM)';
        const outDesc = (walkInCalState.selectedEndSlot === 'Nighttime') ? '8:00 AM (Next Morning)' : '5:00 PM (Daytime)';
        if (walkInTopCheckInText) {
            walkInTopCheckInText.textContent = `${sFmt} · ${inTime}`;
        }
        if (walkInTopCheckoutFullText) {
            walkInTopCheckoutFullText.textContent = `${checkout.displayDate} · ${outDesc}`;
        }

        // Update footer and badges
        if (walkInCalCheckoutDateText) {
            walkInCalCheckoutDateText.textContent = `Check-Out: ${checkout.displayDate} · ${outDesc}`;
        }
        if (walkInCalCheckoutSessionText) {
            walkInCalCheckoutSessionText.textContent = checkout.sessionLabel;
        }
        if (walkInCalStartSlotText) {
            walkInCalStartSlotText.textContent = walkInCalState.selectedStartSlot;
        }
        if (walkInCalSpanDetailText) {
            walkInCalSpanDetailText.textContent = `${spanLabel} Stay (${counts.dayCount} Daytime, ${counts.nightCount} Nighttime)`;
        }
        if (walkInCalSummaryScheduleText) {
            walkInCalSummaryScheduleText.textContent = `${spanLabel} Stay (${counts.dayCount} Daytime, ${counts.nightCount} Nighttime) • Check-In: ${sFmt}`;
        }
        if (walkInCalDaysBadgeText) {
            walkInCalDaysBadgeText.textContent = `${spanLabel} Stay`;
        }
        if (walkInTopCheckoutTimeBadge) {
            walkInTopCheckoutTimeBadge.textContent = `${checkout.displayDate} · ${checkout.time}`;
        }

        if (walkInCalSummaryText) {
            walkInCalSummaryText.textContent = `Check-In Today (${walkInCalState.selectedStartSlot}) → Check-Out ${checkout.textSummary}`;
        }
        if (walkInCalSpanText) {
            walkInCalSpanText.textContent = `${spanLabel} Stay (${counts.dayCount} Daytime, ${counts.nightCount} Nighttime)`;
        }
        if (walkInCalCurrentBadge) {
            walkInCalCurrentBadge.textContent = `${counts.daysSpan}D Stay`;
        }
        if (walkInCalStepHelp) {
            walkInCalStepHelp.textContent = 'Click any date from Today onwards to set Check-Out Date';
        }
    };

    const renderWalkInCalendarMonth = () => {
        if (!walkInCalGrid) return;
        const year = walkInCalState.viewYear;
        const month = walkInCalState.viewMonth;

        if (walkInCalTitle) walkInCalTitle.textContent = `${monthNames[month]} ${year}`;
        if (walkInCalYear) walkInCalYear.value = year;

        walkInCalGrid.innerHTML = '';

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Empty padding cells
        for (let i = 0; i < firstDay; i++) {
            const empty = document.createElement('div');
            empty.className = 'edit-calendar__day edit-calendar__day--empty opacity-0 pointer-events-none';
            walkInCalGrid.appendChild(empty);
        }

        // Days
        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'edit-calendar__day flex flex-col items-center justify-center rounded-lg p-1.5 text-xs font-semibold transition-all duration-150 relative cursor-pointer border border-transparent';
            btn.textContent = d;
            btn.dataset.date = dateStr;

            const isPast = dateStr < todayStr;
            const isToday = dateStr === todayStr;
            const isEnd = dateStr === walkInCalState.selectedEndDate;
            const inRange = dateStr >= walkInCalState.selectedStartDate && dateStr <= walkInCalState.selectedEndDate;

            if (isPast) {
                btn.classList.add('is-disabled', 'opacity-30', 'cursor-not-allowed');
                btn.disabled = true;
            } else {
                btn.classList.add('is-available', 'hover:border-hp-green', 'hover:bg-hp-green/10');
            }

            if (inRange && !isPast) {
                btn.classList.add('is-selected', 'bg-hp-green', 'text-white', 'font-bold');
                btn.classList.remove('hover:bg-hp-green/10');
            }

            if (isToday) {
                const dot = document.createElement('span');
                dot.className = 'absolute bottom-1 h-1 w-1 rounded-full bg-emerald-400';
                btn.appendChild(dot);
            }

            btn.addEventListener('click', () => {
                if (btn.disabled) return;
                // Single click sets Check-Out Date (since check-in is fixed to today)
                walkInCalState.selectedEndDate = dateStr;

                // If checkin is today nighttime and selected checkout is today, force nighttime checkout
                if (walkInCalState.selectedStartDate === walkInCalState.selectedEndDate && walkInCalState.selectedStartSlot === 'Nighttime') {
                    walkInCalState.selectedEndSlot = 'Nighttime';
                }

                syncWalkInSessionPills();
                updateWalkInCalModalSummary();
                renderWalkInCalendarMonth();
            });

            walkInCalGrid.appendChild(btn);
        }

        updateWalkInCalModalSummary();
    };

    walkInCalPrev?.addEventListener('click', () => {
        walkInCalState.viewMonth--;
        if (walkInCalState.viewMonth < 0) {
            walkInCalState.viewMonth = 11;
            walkInCalState.viewYear--;
        }
        renderWalkInCalendarMonth();
    });

    walkInCalNext?.addEventListener('click', () => {
        walkInCalState.viewMonth++;
        if (walkInCalState.viewMonth > 11) {
            walkInCalState.viewMonth = 0;
            walkInCalState.viewYear++;
        }
        renderWalkInCalendarMonth();
    });

    walkInCalYear?.addEventListener('change', (e) => {
        walkInCalState.viewYear = parseInt(e.target.value);
        renderWalkInCalendarMonth();
    });

    walkInCalApplyBtn?.addEventListener('click', () => {
        walkInSchedule.startDate = walkInCalState.selectedStartDate;
        walkInSchedule.endDate = walkInCalState.selectedEndDate;
        walkInSchedule.startSlot = walkInCalState.selectedStartSlot;
        walkInSchedule.endSlot = walkInCalState.selectedEndSlot;

        syncMasterScheduleDisplay();

        // Clamp any existing selected amenities to stay inside the new master range
        selectedAmenities.forEach(am => {
            if (am.start_date < walkInSchedule.startDate || am.start_date > walkInSchedule.endDate) {
                am.start_date = walkInSchedule.startDate;
            }
            if (am.end_date < am.start_date || am.end_date > walkInSchedule.endDate) {
                am.end_date = walkInSchedule.endDate;
            }
            // Recalculate amenity price
            const counts = calculateWalkInSlots(am.start_date, am.end_date, am.start_slot, am.end_slot);
            am.total_days = counts.daysSpan;
            am.day_count = counts.dayCount;
            am.night_count = counts.nightCount;

            const dayP = am.is_aircon && am.daytime_aircon_price ? am.daytime_aircon_price : am.daytime_price;
            const nightP = am.is_aircon && am.nighttime_aircon_price ? am.nighttime_aircon_price : am.nighttime_price;
            am.price_at_booking = (counts.dayCount * dayP) + (counts.nightCount * nightP);
        });

        renderSelectedAmenities();
        updateGrandTotal();
        closeWalkInCalendarModal();
    });

    // ==========================================
    // Amenities Modal & Dynamic Availability Loading
    // ==========================================
    const amenityModal = document.getElementById('amenityModal');
    const amenityCloseButtons = document.querySelectorAll('[data-close-amenity-modal="true"]');
    const amenityModalStayBadge = document.getElementById('amenityModalStayBadge');
    const walkInAmenityCategorySelect = document.getElementById('walkInAmenityCategorySelect');
    const walkInAmenityStatusFilters = document.getElementById('walkInAmenityStatusFilters');
    const walkInAmenityFilterSummaryText = document.getElementById('walkInAmenityFilterSummaryText');

    let cachedAmenityList = [];
    let amenityStatusFilter = 'available'; // Default is 'available' per user request!
    let amenityCategoryFilter = 'all';

    // Categorization helper: groups like Payags, A-Houses, Cottages, etc.
    const categorizeAmenity = (name = '') => {
        const trimmed = (name || '').trim();
        const lower = trimmed.toLowerCase();
        if (lower.startsWith('a-house') || lower.startsWith('ahouse') || lower.includes('a-house')) return 'A-Houses';
        if (lower.startsWith('payag') || lower.includes('payag')) return 'Payags';
        if (lower.startsWith('cottage') || lower.includes('cottage')) return 'Cottages';
        if (lower.includes('function') || lower.includes('hall')) return 'Function Halls';
        if (lower.includes('room') || lower.includes('suite')) return 'Rooms';
        
        const words = trimmed.split(/[\s\-_0-9]/).filter(Boolean);
        if (words.length > 0 && words[0].length > 1) {
            const base = words[0].charAt(0).toUpperCase() + words[0].slice(1).toLowerCase();
            return base.endsWith('s') ? base : `${base}s`;
        }
        return 'Other Amenities';
    };

    const syncStatusFilterPills = () => {
        const pills = document.querySelectorAll('#walkInAmenityStatusFilters [data-filter-status]');
        pills.forEach(pill => {
            const st = pill.dataset.filterStatus;
            if (st === amenityStatusFilter) {
                pill.className = 'walkin-filter-pill is-active cursor-pointer rounded-lg px-3 py-1.5 text-xs font-bold transition-all bg-hp-green text-white shadow-xs';
            } else {
                pill.className = 'walkin-filter-pill cursor-pointer rounded-lg px-3 py-1.5 text-xs font-semibold text-hp-text transition-all bg-transparent hover:bg-black/5 dark:hover:bg-white/10';
            }
        });
    };

    // Render Categorized Amenities inside Choose Amenities Modal
    const renderCategorizedAmenities = () => {
        const container = document.getElementById('amenitiesContainer');
        if (!container) return;

        const counts = calculateWalkInSlots(walkInSchedule.startDate, walkInSchedule.endDate, walkInSchedule.startSlot, walkInSchedule.endSlot);
        const spanLabel = counts.daysSpan === 1 ? '1 Day' : `${counts.daysSpan} Days`;

        // Filter list based on active filters
        let filtered = cachedAmenityList;
        if (amenityStatusFilter === 'available') {
            filtered = filtered.filter(a => a.is_available);
        } else if (amenityStatusFilter === 'occupied') {
            filtered = filtered.filter(a => !a.is_available);
        }

        if (amenityCategoryFilter !== 'all') {
            filtered = filtered.filter(a => categorizeAmenity(a.amenities_name) === amenityCategoryFilter);
        }

        // Update footer summary text
        if (walkInAmenityFilterSummaryText) {
            const statusLabel = amenityStatusFilter === 'available' ? 'available' : (amenityStatusFilter === 'occupied' ? 'occupied' : 'total');
            const catLabel = amenityCategoryFilter === 'all' ? 'all categories' : amenityCategoryFilter;
            walkInAmenityFilterSummaryText.textContent = `Showing ${filtered.length} ${statusLabel} in ${catLabel}`;
        }

        // Empty state
        if (filtered.length === 0) {
            container.innerHTML = `
                <div class="py-12 text-center text-hp-text-muted">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-hp-green/10 text-xl text-hp-green">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <div class="text-sm font-bold text-hp-text dark:text-[#f3f4f6]">No amenities found</div>
                    <p class="text-xs text-hp-text-muted mt-1 max-w-[340px] mx-auto">No amenities match "${amenityStatusFilter}" in "${amenityCategoryFilter === 'all' ? 'All Categories' : amenityCategoryFilter}".</p>
                    <button type="button" class="mt-3.5 cursor-pointer rounded-xl border border-hp-green bg-hp-green/10 px-4 py-2 text-xs font-bold text-hp-green hover:bg-hp-green hover:text-white transition-all" id="walkInResetAmenityFiltersBtn">
                        Show All Available Amenities
                    </button>
                </div>
            `;
            const resetBtn = document.getElementById('walkInResetAmenityFiltersBtn');
            resetBtn?.addEventListener('click', () => {
                amenityStatusFilter = 'available';
                amenityCategoryFilter = 'all';
                if (walkInAmenityCategorySelect) walkInAmenityCategorySelect.value = 'all';
                syncStatusFilterPills();
                renderCategorizedAmenities();
            });
            return;
        }

        // Group by category
        const groups = {};
        filtered.forEach(amenity => {
            const cat = categorizeAmenity(amenity.amenities_name);
            if (!groups[cat]) groups[cat] = [];
            groups[cat].push(amenity);
        });

        // Preferred order of categories: A-Houses, Payags, Cottages, Rooms, Function Halls, Others
        const preferredOrder = ['A-Houses', 'Payags', 'Cottages', 'Rooms', 'Function Halls'];
        const sortedCategories = Object.keys(groups).sort((a, b) => {
            const idxA = preferredOrder.indexOf(a);
            const idxB = preferredOrder.indexOf(b);
            if (idxA !== -1 && idxB !== -1) return idxA - idxB;
            if (idxA !== -1) return -1;
            if (idxB !== -1) return 1;
            return a.localeCompare(b);
        });

        container.innerHTML = '';

        sortedCategories.forEach(cat => {
            const items = groups[cat];
            const groupWrap = document.createElement('div');
            groupWrap.className = 'amenity-category-section space-y-2.5';

            // Category Header
            const availableCount = items.filter(a => a.is_available).length;
            const header = document.createElement('div');
            header.className = 'flex items-center justify-between pb-1.5 border-b border-glass-border/60 sticky top-0 bg-hp-cream/90 dark:bg-[rgba(30,30,30,0.95)] backdrop-blur-sm z-10';
            header.innerHTML = `
                <div class="flex items-center gap-2">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-hp-green dark:text-emerald-400">${cat}</span>
                    <span class="rounded-full bg-hp-green/15 px-2 py-0.2 text-[0.68rem] font-bold text-hp-green dark:text-emerald-400">${items.length}</span>
                </div>
                <span class="text-[0.7rem] text-hp-text-muted">${availableCount} available</span>
            `;
            groupWrap.appendChild(header);

            // Cards Grid
            const cardsGrid = document.createElement('div');
            cardsGrid.className = 'grid gap-2.5';

            items.forEach(amenity => {
                const isAvailable = Boolean(amenity.is_available);
                const isAlreadySelected = selectedAmenities.some(a => String(a.amenity_id) === String(amenity.id));
                const hasFreeEntrance = Boolean(amenity.free_entrance || amenity.benefits?.free_entrance);
                const hasFreePool = Boolean(amenity.free_pool || amenity.benefits?.free_pool);

                const dayP = parseFloat(amenity.daytime_price) || 0;
                const nightP = parseFloat(amenity.nighttime_price) || 0;
                const calculatedPrice = (counts.dayCount * dayP) + (counts.nightCount * nightP);

                const card = document.createElement('div');
                card.className = `walkin-amenity-card flex flex-wrap items-center justify-between gap-3 rounded-xl border p-3.5 transition-all duration-200 ${isAvailable
                    ? (isAlreadySelected ? 'border-hp-green/60 bg-hp-green/10 shadow-xs' : 'border-glass-border bg-glass hover:border-hp-green/60 hover:bg-white/60 dark:hover:bg-white/5')
                    : 'border-red-300/40 bg-red-50/25 opacity-70 dark:border-red-500/20 dark:bg-red-500/5'
                }`;
                card.dataset.amenityId = amenity.id;

                card.innerHTML = `
                    <div class="flex-1 min-w-[200px]">
                        <div class="flex items-center gap-2 flex-wrap">
                            <strong class="text-sm font-bold text-hp-text dark:text-[#f3f4f6]">${amenity.amenities_name}</strong>
                            ${isAvailable
                                ? '<span class="rounded bg-emerald-500/10 px-2 py-0.5 text-[0.68rem] font-bold text-emerald-600 dark:text-emerald-400">Available</span>'
                                : '<span class="rounded bg-red-500/10 px-2 py-0.5 text-[0.68rem] font-bold text-red-600 dark:text-red-400">Occupied</span>'
                            }
                            ${hasFreeEntrance ? '<span class="rounded bg-amber-500/10 px-1.5 py-0.5 text-[0.65rem] font-bold text-amber-700 dark:text-amber-300"><i class="bi bi-ticket-perforated-fill me-1"></i>Free Entrance</span>' : ''}
                            ${hasFreePool ? '<span class="rounded bg-sky-500/10 px-1.5 py-0.5 text-[0.65rem] font-bold text-sky-700 dark:text-sky-300"><i class="bi bi-water me-1"></i>Free Pool</span>' : ''}
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-hp-text-muted">
                            <span>Capacity: ${amenity.minimum_capacity || 1}–${amenity.maximum_capacity || 10} guests</span>
                            <span>•</span>
                            <span>Day: ₱${formatPeso(dayP)}</span>
                            <span>•</span>
                            <span>Night: ₱${formatPeso(nightP)}</span>
                        </div>
                        ${isAvailable ? `
                            <div class="mt-1 text-xs font-semibold text-hp-green dark:text-emerald-400">
                                Stay Total: ₱${formatPeso(calculatedPrice)} <span class="font-normal text-hp-text-muted">(${spanLabel} • ${counts.dayCount}D ${counts.nightCount}N)</span>
                            </div>
                        ` : `
                            <div class="mt-1 text-xs text-red-500/80 font-medium">
                                Booked for this stay window
                            </div>
                        `}
                    </div>
                    <div>
                        ${isAlreadySelected ? `
                            <button type="button" class="rounded-xl border border-hp-green bg-hp-green px-4 py-2 text-xs font-bold text-white shadow-xs cursor-default flex items-center gap-1.5" disabled>
                                <i class="bi bi-check-lg"></i>
                                <span>Added</span>
                            </button>
                        ` : (isAvailable ? `
                            <button type="button" class="walkin-add-amenity-btn cursor-pointer rounded-xl border border-hp-green bg-hp-green/10 px-4 py-2 text-xs font-bold text-hp-green transition-all duration-150 hover:bg-hp-green hover:text-white shadow-xs" data-amenity-id="${amenity.id}">
                                + Add Amenity
                            </button>
                        ` : `
                            <button type="button" class="rounded-xl border border-glass-border bg-glass px-3.5 py-1.5 text-xs font-semibold text-hp-text-muted cursor-not-allowed opacity-60" disabled>
                                Unavailable
                            </button>
                        `)}
                    </div>
                `;

                cardsGrid.appendChild(card);
            });

            groupWrap.appendChild(cardsGrid);
            container.appendChild(groupWrap);
        });
    };

    const loadAvailableAmenitiesForStay = async () => {
        const container = document.getElementById('amenitiesContainer');
        if (!container) return;

        const counts = calculateWalkInSlots(walkInSchedule.startDate, walkInSchedule.endDate, walkInSchedule.startSlot, walkInSchedule.endSlot);
        const sFmt = formatDisplayDate(walkInSchedule.startDate);
        const eFmt = formatDisplayDate(walkInSchedule.endDate);
        const spanLabel = counts.daysSpan === 1 ? '1 Day' : `${counts.daysSpan} Days`;

        if (amenityModalStayBadge) {
            if (walkInSchedule.startDate === walkInSchedule.endDate) {
                amenityModalStayBadge.textContent = `${sFmt} (${walkInSchedule.startSlot}) • ${spanLabel}`;
            } else {
                amenityModalStayBadge.textContent = `${sFmt} (${walkInSchedule.startSlot}) → ${eFmt} (${walkInSchedule.endSlot}) • ${spanLabel}`;
            }
        }

        container.innerHTML = `
            <div class="flex items-center justify-center py-8 text-sm text-hp-text-muted">
                <svg class="mr-2 h-5 w-5 animate-spin text-hp-green" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                Checking amenity availability for selected stay...
            </div>
        `;

        try {
            const params = new URLSearchParams({
                start_date: walkInSchedule.startDate,
                end_date: walkInSchedule.endDate,
                start_slot: walkInSchedule.startSlot,
                end_slot: walkInSchedule.endSlot,
            });

            const res = await fetch(`/api/amenities/availability?${params.toString()}`);
            if (!res.ok) throw new Error('Failed to fetch availability');
            const data = await res.json();
            cachedAmenityList = data.amenities || [];

            // Update Counts on pills
            const availableCount = cachedAmenityList.filter(a => a.is_available).length;
            const occupiedCount = cachedAmenityList.filter(a => !a.is_available).length;
            const allCount = cachedAmenityList.length;

            const countAvailEl = document.getElementById('walkInCountAvailable');
            const countOccEl = document.getElementById('walkInCountOccupied');
            const countAllEl = document.getElementById('walkInCountAll');
            if (countAvailEl) countAvailEl.textContent = availableCount;
            if (countOccEl) countOccEl.textContent = occupiedCount;
            if (countAllEl) countAllEl.textContent = allCount;

            // Populate category dropdown
            if (walkInAmenityCategorySelect) {
                const uniqueCategories = Array.from(new Set(cachedAmenityList.map(a => categorizeAmenity(a.amenities_name)))).sort();
                const currentVal = walkInAmenityCategorySelect.value;
                walkInAmenityCategorySelect.innerHTML = '<option value="all">All Categories</option>';
                uniqueCategories.forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat;
                    opt.textContent = cat;
                    walkInAmenityCategorySelect.appendChild(opt);
                });
                if (uniqueCategories.includes(currentVal)) {
                    walkInAmenityCategorySelect.value = currentVal;
                    amenityCategoryFilter = currentVal;
                } else {
                    walkInAmenityCategorySelect.value = 'all';
                    amenityCategoryFilter = 'all';
                }
            }

            syncStatusFilterPills();
            renderCategorizedAmenities();
        } catch (err) {
            console.error('Failed to load available amenities:', err);
            container.innerHTML = '<p class="guest-empty px-4 py-8 text-center text-red-500">Failed to load amenity availability. Please close and retry.</p>';
        }
    };

    // Filter listeners
    walkInAmenityStatusFilters?.addEventListener('click', (e) => {
        const pill = e.target.closest('[data-filter-status]');
        if (pill) {
            amenityStatusFilter = pill.dataset.filterStatus;
            syncStatusFilterPills();
            renderCategorizedAmenities();
        }
    });

    walkInAmenityCategorySelect?.addEventListener('change', (e) => {
        amenityCategoryFilter = e.target.value;
        renderCategorizedAmenities();
    });

    const openAmenityModal = () => {
        if (!amenityModal) return;
        amenityModal.classList.add('is-open');
        amenityModal.setAttribute('aria-hidden', 'false');
        loadAvailableAmenitiesForStay();
    };

    const closeAmenityModal = () => {
        if (!amenityModal) return;
        amenityModal.classList.remove('is-open');
        amenityModal.setAttribute('aria-hidden', 'true');
    };

    chooseAmenitiesBtn?.addEventListener('click', openAmenityModal);
    amenityCloseButtons.forEach(btn => btn.addEventListener('click', closeAmenityModal));

    // Handle "+ Add Amenity" click from Choose Amenities Modal
    document.addEventListener('click', (e) => {
        const addBtn = e.target.closest('.walkin-add-amenity-btn');
        if (addBtn) {
            const amenityId = addBtn.dataset.amenityId;
            const allAmenities = window.ALL_AMENITIES || [];
            let amenity = allAmenities.find(a => String(a.id) === String(amenityId));
            if (!amenity && cachedAmenityList) {
                amenity = cachedAmenityList.find(a => String(a.id) === String(amenityId));
            }
            if (!amenity) return;

            // Check if already added
            if (selectedAmenities.some(a => String(a.amenity_id) === String(amenityId))) {
                alert(`${amenity.amenities_name} is already added to this reservation.`);
                return;
            }

            const counts = calculateWalkInSlots(walkInSchedule.startDate, walkInSchedule.endDate, walkInSchedule.startSlot, walkInSchedule.endSlot);
            const dayPrice = parseFloat(amenity.daytime_price) || 0;
            const nightPrice = parseFloat(amenity.nighttime_price) || 0;
            const price = (counts.dayCount * dayPrice) + (counts.nightCount * nightPrice);
            const freeEntrance = Boolean(amenity.free_entrance || amenity.benefits?.free_entrance);
            const freePool = Boolean(amenity.free_pool || amenity.benefits?.free_pool);

            selectedAmenities.push({
                amenity_id: amenity.id,
                amenity_name: amenity.amenities_name,
                min_cap: amenity.minimum_capacity,
                max_cap: amenity.maximum_capacity,
                additional_per_head: parseFloat(amenity.additional_per_head) || 0,
                start_date: walkInSchedule.startDate,
                end_date: walkInSchedule.endDate,
                start_slot: walkInSchedule.startSlot,
                end_slot: walkInSchedule.endSlot,
                is_aircon: false,
                free_entrance: freeEntrance,
                free_pool: freePool,
                quantity: 1,
                day_count: counts.dayCount,
                night_count: counts.nightCount,
                total_days: counts.daysSpan,
                price_at_booking: price,
                daytime_price: dayPrice,
                nighttime_price: nightPrice,
                daytime_aircon_price: amenity.daytime_aircon_price !== null ? parseFloat(amenity.daytime_aircon_price) : null,
                nighttime_aircon_price: amenity.nighttime_aircon_price !== null ? parseFloat(amenity.nighttime_aircon_price) : null,
            });

            // Auto-select free entrance and free pool if included in any selected amenity
            const hasFreeEnt = selectedAmenities.some(a => a.free_entrance);
            const hasFreeP = selectedAmenities.some(a => a.free_pool);

            if (hasFreeEnt && walkInEntranceOption) {
                walkInEntranceOption.value = 'all_free';
                walkInEntranceOption.dispatchEvent(new Event('change'));
            }
            if (hasFreeP && walkInPoolOption) {
                walkInPoolOption.value = 'all_free';
                walkInPoolOption.dispatchEvent(new Event('change'));
            }

            renderSelectedAmenities();
            updateGrandTotal();
            closeAmenityModal();
        }
    });

    // Render Selected Amenities Cards
    const renderSelectedAmenities = () => {
        if (!selectedAmenitiesContainer || !amenitiesHiddenInputs) return;

        selectedAmenitiesContainer.innerHTML = '';
        amenitiesHiddenInputs.innerHTML = '';

        const sidebarAmBadge = document.getElementById('walkInSidebarAmenitiesBadge');
        if (sidebarAmBadge) {
            sidebarAmBadge.textContent = selectedAmenities.length;
        }

        if (selectedAmenities.length === 0) {
            if (noAmenitiesNotice) noAmenitiesNotice.style.display = 'block';
            selectedAmenitiesContainer.appendChild(noAmenitiesNotice);
            updateGrandTotal();
            return;
        }

        if (noAmenitiesNotice) noAmenitiesNotice.style.display = 'none';

        selectedAmenities.forEach((am, index) => {
            const sFmt = formatDisplayDate(am.start_date);
            const eFmt = formatDisplayDate(am.end_date);
            const spanLabel = am.total_days === 1 ? '1 Day' : `${am.total_days} Days`;
            const maxCapText = (am.max_cap !== null && am.max_cap !== undefined && am.max_cap !== '')
                ? `Max ${am.max_cap} guests${parseFloat(am.additional_per_head) > 0 ? ` (+₱${formatPeso(am.additional_per_head)}/extra head)` : ''}`
                : 'No limit';

            const card = document.createElement('div');
            card.className = 'selected-amenity-card rounded-xl border border-glass-border bg-glass p-3.5 shadow-sm transition-all duration-200';
            card.dataset.index = index;

            card.innerHTML = `
                <div class="flex flex-wrap items-start justify-between gap-2 border-b border-glass-border/50 pb-2.5">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <strong class="text-sm font-bold text-hp-text dark:text-[#f3f4f6]">${am.amenity_name}</strong>
                            ${am.free_entrance ? '<span class="rounded bg-amber-500/10 px-1.5 py-0.5 text-[0.65rem] font-bold text-amber-700 dark:text-amber-300"><i class="bi bi-ticket-perforated-fill me-1"></i>Free Entrance</span>' : ''}
                            ${am.free_pool ? '<span class="rounded bg-sky-500/10 px-1.5 py-0.5 text-[0.65rem] font-bold text-sky-700 dark:text-sky-300"><i class="bi bi-water me-1"></i>Free Pool</span>' : ''}
                        </div>
                        <div class="text-xs text-hp-text-muted">Capacity: ${maxCapText}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-extrabold text-hp-green">₱${formatPeso(am.price_at_booking)}</div>
                        <button type="button" class="walkin-remove-amenity-btn text-[0.75rem] font-bold text-red-500 hover:text-red-700 transition-colors cursor-pointer" data-index="${index}">
                            Remove
                        </button>
                    </div>
                </div>

                <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2 text-xs">
                    <div class="flex items-center gap-1.5 rounded-lg border border-glass-border bg-hp-cream/60 px-2.5 py-1 text-hp-text dark:bg-white/5">
                        <svg class="h-3.5 w-3.5 text-hp-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="font-semibold">${sFmt} (${am.start_slot}) → ${eFmt} (${am.end_slot}) • ${spanLabel} (${am.day_count}D ${am.night_count}N)</span>
                    </div>
                </div>
            `;

            selectedAmenitiesContainer.appendChild(card);

            // Hidden inputs for backend submission
            const pricingTypeStr = am.total_days > 1
                ? `Continuous Stay (${am.total_days}D)`
                : ((am.start_slot === 'Daytime' && am.end_slot === 'Nighttime') ? 'DayToNight' : am.start_slot);

            amenitiesHiddenInputs.insertAdjacentHTML('beforeend', `
                <input type="hidden" name="selected_amenities[${index}][amenity_id]" value="${am.amenity_id}">
                <input type="hidden" name="selected_amenities[${index}][start_date]" value="${am.start_date}">
                <input type="hidden" name="selected_amenities[${index}][end_date]" value="${am.end_date}">
                <input type="hidden" name="selected_amenities[${index}][start_slot]" value="${am.start_slot}">
                <input type="hidden" name="selected_amenities[${index}][end_slot]" value="${am.end_slot}">
                <input type="hidden" name="selected_amenities[${index}][pricing_type]" value="${pricingTypeStr}">
                <input type="hidden" name="selected_amenities[${index}][price_at_booking]" value="${am.price_at_booking}">
                <input type="hidden" name="selected_amenities[${index}][is_aircon]" value="0">
                <input type="hidden" name="selected_amenities[${index}][quantity]" value="${am.quantity || 1}">
            `);
        });

        updateGrandTotal();
    };

    // Remove amenity card click
    selectedAmenitiesContainer?.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('.walkin-remove-amenity-btn');
        if (removeBtn) {
            const idx = parseInt(removeBtn.dataset.index);
            if (!isNaN(idx)) {
                selectedAmenities.splice(idx, 1);
                renderSelectedAmenities();
                updateGrandTotal();
            }
        }
    });

    // Aircon toggle from card
    selectedAmenitiesContainer?.addEventListener('change', (e) => {
        if (e.target.classList.contains('walkin-card-ac-toggle')) {
            const idx = parseInt(e.target.dataset.index);
            if (!isNaN(idx) && selectedAmenities[idx]) {
                selectedAmenities[idx].is_aircon = e.target.checked;
                const am = selectedAmenities[idx];
                const dayP = am.is_aircon && am.daytime_aircon_price ? am.daytime_aircon_price : am.daytime_price;
                const nightP = am.is_aircon && am.nighttime_aircon_price ? am.nighttime_aircon_price : am.nighttime_price;
                am.price_at_booking = (am.day_count * dayP) + (am.night_count * nightP);
                renderSelectedAmenities();
                updateGrandTotal();
            }
        }
    });

    // ==========================================
    // Walk-In Per-Amenity Schedule Customizer Modal
    // ==========================================
    const walkInAmenityScheduleModal = document.getElementById('walkInAmenityScheduleModal');
    const walkInAmenityScheduleTitle = document.getElementById('walkInAmenityScheduleTitle');
    const walkInAmenityScheduleAmenityId = document.getElementById('walkInAmenityScheduleAmenityId');
    const walkInAmenityScheduleRangeText = document.getElementById('walkInAmenityScheduleRangeText');
    const walkInAmenityStartDate = document.getElementById('walkInAmenityStartDate');
    const walkInAmenityStartSlot = document.getElementById('walkInAmenityStartSlot');
    const walkInAmenityEndDate = document.getElementById('walkInAmenityEndDate');
    const walkInAmenityEndSlot = document.getElementById('walkInAmenityEndSlot');
    const walkInAmenityAirconWrap = document.getElementById('walkInAmenityAirconWrap');
    const walkInAmenityAirconToggle = document.getElementById('walkInAmenityAirconToggle');
    const walkInAmenityAirconDiff = document.getElementById('walkInAmenityAirconDiff');
    const walkInAmenityDurationText = document.getElementById('walkInAmenityDurationText');
    const walkInAmenityMathText = document.getElementById('walkInAmenityMathText');
    const walkInAmenityTotalPrice = document.getElementById('walkInAmenityTotalPrice');
    const walkInAmenitySaveScheduleBtn = document.getElementById('walkInAmenitySaveScheduleBtn');
    const walkInAmenityScheduleCloseBtns = document.querySelectorAll('[data-close-walkin-amenity-schedule="true"]');

    let currentEditingAmenityIndex = null;

    const openAmenityScheduleModal = (index) => {
        if (!walkInAmenityScheduleModal || !selectedAmenities[index]) return;
        currentEditingAmenityIndex = index;
        const am = selectedAmenities[index];

        if (walkInAmenityScheduleTitle) walkInAmenityScheduleTitle.textContent = `Customize ${am.amenity_name} Stay`;
        if (walkInAmenityScheduleAmenityId) walkInAmenityScheduleAmenityId.value = am.amenity_id;

        const sFmt = formatDisplayDate(walkInSchedule.startDate);
        const eFmt = formatDisplayDate(walkInSchedule.endDate);
        if (walkInAmenityScheduleRangeText) {
            walkInAmenityScheduleRangeText.textContent = `${sFmt} (${walkInSchedule.startSlot}) to ${eFmt} (${walkInSchedule.endSlot})`;
        }

        // Set min/max constraints
        if (walkInAmenityStartDate) {
            walkInAmenityStartDate.min = walkInSchedule.startDate;
            walkInAmenityStartDate.max = walkInSchedule.endDate;
            walkInAmenityStartDate.value = am.start_date;
        }
        if (walkInAmenityEndDate) {
            walkInAmenityEndDate.min = am.start_date;
            walkInAmenityEndDate.max = walkInSchedule.endDate;
            walkInAmenityEndDate.value = am.end_date;
        }
        if (walkInAmenityStartSlot) walkInAmenityStartSlot.value = am.start_slot;
        if (walkInAmenityEndSlot) walkInAmenityEndSlot.value = am.end_slot;

        // Aircon wrap visibility
        const hasAc = am.daytime_aircon_price !== null || am.nighttime_aircon_price !== null;
        if (walkInAmenityAirconWrap) {
            walkInAmenityAirconWrap.style.display = hasAc ? 'flex' : 'none';
        }
        if (walkInAmenityAirconToggle) {
            walkInAmenityAirconToggle.checked = Boolean(am.is_aircon);
        }

        enforceWalkInAmenityModalConstraints();
        walkInAmenityScheduleModal.classList.add('is-open');
        walkInAmenityScheduleModal.setAttribute('aria-hidden', 'false');
    };

    const closeAmenityScheduleModal = () => {
        if (!walkInAmenityScheduleModal) return;
        walkInAmenityScheduleModal.classList.remove('is-open');
        walkInAmenityScheduleModal.setAttribute('aria-hidden', 'true');
        currentEditingAmenityIndex = null;
    };

    walkInAmenityScheduleCloseBtns.forEach(btn => btn.addEventListener('click', closeAmenityScheduleModal));

    selectedAmenitiesContainer?.addEventListener('click', (e) => {
        const customizeBtn = e.target.closest('.walkin-customize-amenity-btn');
        if (customizeBtn) {
            const idx = parseInt(customizeBtn.dataset.index);
            if (!isNaN(idx)) {
                openAmenityScheduleModal(idx);
            }
        }
    });

    const enforceWalkInAmenityModalConstraints = () => {
        if (currentEditingAmenityIndex === null || !selectedAmenities[currentEditingAmenityIndex]) return;
        const am = selectedAmenities[currentEditingAmenityIndex];

        const bStart = walkInSchedule.startDate;
        const bEnd = walkInSchedule.endDate;
        const bStartSlot = walkInSchedule.startSlot;
        const bEndSlot = walkInSchedule.endSlot;

        // Start Date clamping
        if (walkInAmenityStartDate) {
            walkInAmenityStartDate.min = bStart;
            walkInAmenityStartDate.max = bEnd;
            if (walkInAmenityStartDate.value < bStart) walkInAmenityStartDate.value = bStart;
            if (walkInAmenityStartDate.value > bEnd) walkInAmenityStartDate.value = bEnd;
        }

        const curStart = walkInAmenityStartDate?.value || bStart;

        // End Date clamping
        if (walkInAmenityEndDate) {
            walkInAmenityEndDate.min = curStart;
            walkInAmenityEndDate.max = bEnd;
            if (walkInAmenityEndDate.value < curStart) walkInAmenityEndDate.value = curStart;
            if (walkInAmenityEndDate.value > bEnd) walkInAmenityEndDate.value = bEnd;
        }

        const curEnd = walkInAmenityEndDate?.value || curStart;

        // Session slots constraints
        if (walkInAmenityStartSlot) {
            const dtOpt = walkInAmenityStartSlot.querySelector('option[value="Daytime"]');
            const allowDayStart = !(curStart === bStart && bStartSlot === 'Nighttime');
            if (dtOpt) dtOpt.disabled = !allowDayStart;
            if (!allowDayStart && walkInAmenityStartSlot.value === 'Daytime') {
                walkInAmenityStartSlot.value = 'Nighttime';
            }
        }

        const curStartSlot = walkInAmenityStartSlot?.value || 'Daytime';

        if (walkInAmenityEndSlot) {
            const ntOpt = walkInAmenityEndSlot.querySelector('option[value="Nighttime"]');
            const dtOpt = walkInAmenityEndSlot.querySelector('option[value="Daytime"]');

            const allowNightEnd = !(curEnd === bEnd && bEndSlot === 'Daytime');
            const isSameDayNightStart = (curStart === curEnd && curStartSlot === 'Nighttime');

            if (ntOpt) ntOpt.disabled = !allowNightEnd;
            if (dtOpt) dtOpt.disabled = isSameDayNightStart;

            if (!allowNightEnd && walkInAmenityEndSlot.value === 'Nighttime') {
                walkInAmenityEndSlot.value = 'Daytime';
            }
            if (isSameDayNightStart && walkInAmenityEndSlot.value === 'Daytime') {
                walkInAmenityEndSlot.value = 'Nighttime';
            }
        }

        const curEndSlot = walkInAmenityEndSlot?.value || 'Daytime';
        const isAircon = Boolean(walkInAmenityAirconToggle?.checked);

        // Recalculate duration and price
        const counts = calculateWalkInSlots(curStart, curEnd, curStartSlot, curEndSlot);
        const dayP = isAircon && am.daytime_aircon_price ? am.daytime_aircon_price : am.daytime_price;
        const nightP = isAircon && am.nighttime_aircon_price ? am.nighttime_aircon_price : am.nighttime_price;
        const totalP = (counts.dayCount * dayP) + (counts.nightCount * nightP);

        if (walkInAmenityDurationText) {
            const spanLabel = counts.daysSpan === 1 ? '1 Day' : `${counts.daysSpan} Days`;
            walkInAmenityDurationText.textContent = `${spanLabel} (${counts.dayCount}D ${counts.nightCount}N)`;
        }
        if (walkInAmenityMathText) {
            const parts = [];
            if (counts.dayCount > 0) parts.push(`${counts.dayCount}D × ₱${formatPeso(dayP)}`);
            if (counts.nightCount > 0) parts.push(`${counts.nightCount}N × ₱${formatPeso(nightP)}`);
            walkInAmenityMathText.textContent = parts.join(' + ') || '0 slots';
        }
        if (walkInAmenityTotalPrice) {
            walkInAmenityTotalPrice.textContent = `₱${formatPeso(totalP)}`;
        }
    };

    walkInAmenityStartDate?.addEventListener('change', enforceWalkInAmenityModalConstraints);
    walkInAmenityEndDate?.addEventListener('change', enforceWalkInAmenityModalConstraints);
    walkInAmenityStartSlot?.addEventListener('change', enforceWalkInAmenityModalConstraints);
    walkInAmenityEndSlot?.addEventListener('change', enforceWalkInAmenityModalConstraints);
    walkInAmenityAirconToggle?.addEventListener('change', enforceWalkInAmenityModalConstraints);

    walkInAmenitySaveScheduleBtn?.addEventListener('click', () => {
        if (currentEditingAmenityIndex === null || !selectedAmenities[currentEditingAmenityIndex]) return;
        const am = selectedAmenities[currentEditingAmenityIndex];

        enforceWalkInAmenityModalConstraints();

        const sDate = walkInAmenityStartDate.value;
        const eDate = walkInAmenityEndDate.value;
        const sSlot = walkInAmenityStartSlot.value;
        const eSlot = walkInAmenityEndSlot.value;
        const isAc = Boolean(walkInAmenityAirconToggle.checked);

        const counts = calculateWalkInSlots(sDate, eDate, sSlot, eSlot);
        const dayP = isAc && am.daytime_aircon_price ? am.daytime_aircon_price : am.daytime_price;
        const nightP = isAc && am.nighttime_aircon_price ? am.nighttime_aircon_price : am.nighttime_price;

        am.start_date = sDate;
        am.end_date = eDate;
        am.start_slot = sSlot;
        am.end_slot = eSlot;
        am.is_aircon = isAc;
        am.total_days = counts.daysSpan;
        am.day_count = counts.dayCount;
        am.night_count = counts.nightCount;
        am.price_at_booking = (counts.dayCount * dayP) + (counts.nightCount * nightP);

        renderSelectedAmenities();
        updateGrandTotal();
        closeAmenityScheduleModal();
    });

    // Guest filter toggle
    const guestFilterToggle = document.getElementById('guestFilterToggle');
    const guestFilterPanel = document.getElementById('guestFilterPanel');
    const guestReservationSelect = document.getElementById('guestReservationSelect');

    guestFilterToggle?.addEventListener('click', () => {
        const isExpanded = guestFilterToggle.getAttribute('aria-expanded') === 'true';
        guestFilterToggle.setAttribute('aria-expanded', !isExpanded);
        guestFilterPanel.hidden = isExpanded;
    });

    // Unified Guest Table Filter Function
    const guestSearchInput = document.getElementById('guestSearchInput');
    const guestRoleSelect = document.getElementById('guestRoleSelect');

    const applyGuestFilters = () => {
        const searchTerm = (guestSearchInput?.value || '').toLowerCase();
        const selectedRole = guestRoleSelect?.value || 'all';
        const selectedReservationId = guestReservationSelect?.value || '';

        const guestRows = document.querySelectorAll('#guestTableBody .guest-row');

        guestRows.forEach(row => {
            let show = true;

            // Search filter
            if (searchTerm) {
                const searchableText = row.getAttribute('data-search') || '';
                if (!searchableText.includes(searchTerm)) show = false;
            }

            // Role filter
            if (selectedRole !== 'all') {
                const isPrimary = row.getAttribute('data-is-primary') === 'true';
                if (selectedRole === 'primary' && !isPrimary) show = false;
                if (selectedRole === 'companion' && isPrimary) show = false;
            }

            // Reservation filter
            if (selectedReservationId) {
                if (row.getAttribute('data-reservation-id') !== selectedReservationId) show = false;
            }

            // Collapsed companions stay hidden until their primary is expanded
            if (show && row.classList.contains('guest-row--companion')) {
                const resId = row.getAttribute('data-reservation-id');
                const primaryRow = resId
                    ? document.querySelector(`.guest-row--primary[data-reservation-id="${resId}"]`)
                    : null;
                if (!primaryRow || !primaryRow.classList.contains('is-expanded')) show = false;
            }

            row.style.display = show ? '' : 'none';
        });

        // Update results count and empty state
        const visibleRows = Array.from(guestRows).filter(row => row.style.display !== 'none');
        const resultsCount = document.getElementById('guestResultsCount');
        if (resultsCount) {
            resultsCount.textContent = `Showing ${visibleRows.length} active guests`;
        }

        const emptyRow = document.getElementById('guestEmptyRow');
        if (emptyRow) {
            emptyRow.style.display = visibleRows.length === 0 ? '' : 'none';
        }
    };

    guestSearchInput?.addEventListener('input', applyGuestFilters);
    guestRoleSelect?.addEventListener('change', applyGuestFilters);
    guestReservationSelect?.addEventListener('change', applyGuestFilters);

    // ── Reservation table filters (Reservation Data View tab) ──────────────
    const resvFilterToggle = document.getElementById('resvFilterToggle');
    const resvFilterPanel = document.getElementById('resvFilterPanel');
    const resvSearchInput = document.getElementById('resvSearchInput');
    const resvTypeFilter = document.getElementById('resvTypeFilter');
    const resvDateFrom = document.getElementById('resvDateFrom');
    const resvDateTo = document.getElementById('resvDateTo');
    const resvFiltersClear = document.getElementById('resvFiltersClear');
    const resvResultsCount = document.getElementById('resvResultsCount');

    resvFilterToggle?.addEventListener('click', () => {
        const isExpanded = resvFilterToggle.getAttribute('aria-expanded') === 'true';
        resvFilterToggle.setAttribute('aria-expanded', String(!isExpanded));
        resvFilterPanel.hidden = isExpanded;
        resvFilterPanel?.classList.toggle('guest-toolbar--collapsed', isExpanded);
    });

    const applyResvFilters = () => {
        const searchTerm = (resvSearchInput?.value || '').toLowerCase();
        const typeValue = resvTypeFilter?.value || 'all';
        const fromValue = resvDateFrom?.value || '';
        const toValue = resvDateTo?.value || '';

        const resvRows = document.querySelectorAll('#checkInsReservationTableBody .reservation-row');
        let visibleCount = 0;

        resvRows.forEach((row) => {
            let show = true;

            if (searchTerm) {
                const searchable = row.getAttribute('data-reservation-search') || '';
                if (!searchable.includes(searchTerm)) show = false;
            }

            if (show && typeValue !== 'all') {
                if ((row.getAttribute('data-reservation-type') || '') !== typeValue) show = false;
            }

            if (show && fromValue) {
                const date = row.getAttribute('data-check-in-date') || '';
                if (date && date < fromValue) show = false;
            }

            if (show && toValue) {
                const date = row.getAttribute('data-check-in-date') || '';
                if (date && date > toValue) show = false;
            }

            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        if (resvResultsCount) {
            resvResultsCount.textContent = `Showing ${visibleCount} of ${resvRows.length} reservation${resvRows.length === 1 ? '' : 's'}`;
        }
    };

    [resvSearchInput, resvTypeFilter, resvDateFrom, resvDateTo].forEach((control) => {
        control?.addEventListener('input', applyResvFilters);
        control?.addEventListener('change', applyResvFilters);
    });

    resvFiltersClear?.addEventListener('click', () => {
        if (resvSearchInput) resvSearchInput.value = '';
        if (resvTypeFilter) resvTypeFilter.value = 'all';
        if (resvDateFrom) resvDateFrom.value = '';
        if (resvDateTo) resvDateTo.value = '';
        applyResvFilters();
    });

    applyResvFilters();

    // Scan QR modal
    const scanQrBtn = document.getElementById('scanQrBtn');
    const scanQrModal = document.getElementById('scanQrModal');
    const scanQrCloseButtons = document.querySelectorAll('[data-close-scan-modal="true"]');
    const stopQrBtn = document.getElementById('stopQrBtn');
    const qrCameraSelect = document.getElementById('qrCameraSelect');
    const qrScannerStatus = document.getElementById('qrScannerStatus');
    const qrScannerElement = document.getElementById('qrScanner');
    let html5QrCode = null;
    let qrScannerActive = false;

    const parseReservationId = (text) => {
        if (!text) return null;
        try {
            const normalized = text.trim();
            const maybeUrl = normalized.includes('reservation_id=') ? normalized : `reservation_id=${normalized}`;
            const query = maybeUrl.includes('?') ? maybeUrl.split('?')[1] : maybeUrl;
            const params = new URLSearchParams(query);
            const value = params.get('reservation_id');
            return value && /^[0-9]+$/.test(value) ? value : null;
        } catch (error) {
            return null;
        }
    };

    const populateCameraOptions = (cameras) => {
        if (!qrCameraSelect) return;
        qrCameraSelect.innerHTML = cameras.map((camera) => `
            <option value="${camera.id}">${camera.label || camera.id}</option>
        `).join('');
    };

    const stopQrScanner = async () => {
        if (!html5QrCode || !qrScannerActive) return;
        try {
            await html5QrCode.stop();
        } catch (error) {
            console.warn('QR scanner stop error', error);
        }
        try {
            html5QrCode.clear();
        } catch (e) { }
        qrScannerActive = false;
    };

    const closeScanQrModal = async () => {
        await stopQrScanner();
        if (scanQrModal) {
            scanQrModal.classList.remove('is-open');
            scanQrModal.setAttribute('aria-hidden', 'true');
        }
    };

    const startQrScanner = async (cameraId) => {
        if (!qrScannerElement || !qrScannerStatus) return;

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode('qrScanner');
        }

        await stopQrScanner();

        await html5QrCode.start(
            cameraId,
            {
                fps: 10,
                // Adaptive QR box: a fixed 250x250 box throws "QR box size
                // provided is larger than the viewfinder size" whenever the
                // scanner container renders narrower than 250px (small
                // windows/narrow layouts), which kills the scanner entirely.
                qrbox: (viewfinderWidth, viewfinderHeight) => {
                    const minDimension = Math.min(viewfinderWidth, viewfinderHeight);
                    const side = Math.min(250, Math.max(100, Math.floor(minDimension * 0.75)));
                    return { width: side, height: side };
                },
                experimentalFeatures: { useBarCodeDetectorIfSupported: true },
            },
            async (decodedText) => {
                const reservationId = parseReservationId(decodedText);
                if (!reservationId) {
                    qrScannerStatus.textContent = 'QR scanned, but not a recognizable reservation code.';
                    return;
                }

                qrScannerStatus.textContent = `Found reservation #${reservationId}. Looking up...`;
                await stopQrScanner();

                try {
                    const response = await fetch(`/staff/check-ins/lookup?reservation_id=${encodeURIComponent(reservationId)}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const body = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        qrScannerStatus.textContent = body.message || 'Reservation lookup failed.';
                        return;
                    }

                    if (body.reservation) {
                        if (window.staffReservationData) {
                            window.staffReservationData[reservationId] = body.reservation;
                        }
                        reservationData[reservationId] = body.reservation;
                        await closeScanQrModal();

                        // Check if reservation is already checked in
                        if (body.reservation.status === 'Checked In') {
                            const checkOutConfirm = confirm(
                                `Reservation #${reservationId} is already checked in.\n\nDo you want to check it out now?`
                            );
                            if (checkOutConfirm) {
                                try {
                                    const checkoutResponse = await fetch(`/staff/reservations/${reservationId}/check-out`, {
                                        method: 'POST',
                                        headers: {
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': csrfToken,
                                            'X-Requested-With': 'XMLHttpRequest',
                                        },
                                    });

                                    const checkoutPayload = await checkoutResponse.json().catch(() => ({}));
                                    if (!checkoutResponse.ok) {
                                        window.alert(checkoutPayload.message || 'Unable to check out this reservation.');
                                    } else {
                                        const resRow = document.querySelector(`tr.reservation-row[data-reservation-id="${reservationId}"]`);
                                        if (resRow) resRow.remove();
                                        if (window.staffReservationData && window.staffReservationData[reservationId]) {
                                            window.staffReservationData[reservationId].status = 'Checked Out';
                                        }
                                        refreshCheckoutCountdowns();
                                        showToast(`Reservation #${reservationId} checked out successfully.`);
                                    }
                                } catch (checkoutError) {
                                    window.alert('Unable to check out this reservation. Please try again.');
                                }
                            } else {
                                openReservationModal(reservationId);
                            }
                        } else {
                            openReservationModal(reservationId);
                        }
                    } else {
                        qrScannerStatus.textContent = 'Reservation not found for scanned QR code.';
                    }
                } catch (lookupError) {
                    qrScannerStatus.textContent = 'Unable to fetch reservation details. Try again.';
                }
            },
            (errorMessage) => {
                // scanning frame callback
            }
        );

        qrScannerActive = true;
        qrScannerStatus.textContent = 'Scanning for QR code. Hold the QR in front of the camera.';
    };

    const openScanQrModal = async () => {
        if (!scanQrModal || !qrScannerElement || !qrScannerStatus) return;
        scanQrModal.classList.add('is-open');
        scanQrModal.setAttribute('aria-hidden', 'false');
        qrScannerStatus.textContent = 'Initializing camera...';

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode('qrScanner');
        }

        try {
            // getUserMedia (and therefore Html5Qrcode.getCameras) only exists
            // in secure contexts. Surface a clear message instead of a cryptic
            // failure when the app is served over plain HTTP.
            if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                throw new Error('Camera access requires HTTPS or localhost. Open the app via http://localhost and allow camera permission.');
            }

            const cameras = await Html5Qrcode.getCameras();
            if (!cameras?.length) {
                throw new Error('No camera device found.');
            }

            populateCameraOptions(cameras);
            const preferredCamera = cameras.find((camera) => /back|rear|environment/i.test(camera.label));
            const externalCamera = cameras.find((camera) => !/front|integrated|face|webcam/i.test(camera.label));
            const cameraId = qrCameraSelect?.value || preferredCamera?.id || externalCamera?.id || cameras[0].id;
            if (qrCameraSelect) {
                qrCameraSelect.value = cameraId;
            }

            await startQrScanner(cameraId);
        } catch (error) {
            qrScannerStatus.textContent = `Camera error: ${error.message || 'Unable to access camera.'}`;
        }
    };

    // Both this script and its sibling page script can be loaded on the same
    // page (the check-ins page loads both), and both wire up the same
    // #scanQrBtn/#qrScanner elements. Two Html5Qrcode instances racing for
    // the same camera and container break scanning (duplicate video
    // elements, double lookups, stacked modals), so only the first
    // initializer may bind the QR controls. The claim is stored on the
    // element itself, so SPA navigations - which swap in a fresh DOM -
    // re-initialize correctly.
    if (scanQrBtn && scanQrBtn.dataset.qrScannerBound !== 'true') {
        scanQrBtn.dataset.qrScannerBound = 'true';

        qrCameraSelect?.addEventListener('change', async () => {
            const cameraId = qrCameraSelect.value;
            try {
                await startQrScanner(cameraId);
            } catch (error) {
                qrScannerStatus.textContent = `Camera error: ${error.message || 'Unable to start selected camera.'}`;
            }
        });

        scanQrBtn.addEventListener('click', () => {
            openScanQrModal();
        });

        stopQrBtn?.addEventListener('click', async () => {
            await closeScanQrModal();
        });

        scanQrCloseButtons.forEach(button => {
            button.addEventListener('click', async () => {
                await closeScanQrModal();
            });
        });
    }

    // Check-in modal
    const checkInModal = document.getElementById('checkInModal');
    const checkInCloseButtons = document.querySelectorAll('[data-close-check-in-modal="true"]');

    const closeCheckInModal = () => {
        checkInModal.classList.remove('is-open');
        checkInModal.setAttribute('aria-hidden', 'true');
    };

    checkInCloseButtons.forEach(button => {
        button.addEventListener('click', closeCheckInModal);
    });

    // Check-in companion modal
    const checkInCompanionModal = document.getElementById('checkInCompanionModal');
    const checkInCompanionCloseButtons = document.querySelectorAll('[data-close-check-in-companion-modal="true"]');
    const checkInAddCompanionBtn = document.getElementById('checkInAddCompanionBtn');
    const checkInCompanionForm = document.getElementById('checkInCompanionForm');
    const checkInCompanionList = document.getElementById('checkInCompanionList');
    const checkInCompanionHiddenFields = document.getElementById('checkInCompanionHiddenFields');
    const checkInCompanionIsForeigner = document.getElementById('checkInCompanionIsForeigner');
    const checkInPrimaryIsForeigner = document.getElementById('checkInPrimaryIsForeigner');

    const openCheckInCompanionModal = () => {
        checkInCompanionModal.classList.add('is-open');
        checkInCompanionModal.setAttribute('aria-hidden', 'false');
    };

    const closeCheckInCompanionModal = () => {
        checkInCompanionModal.classList.remove('is-open');
        checkInCompanionModal.setAttribute('aria-hidden', 'true');
    };

    checkInAddCompanionBtn?.addEventListener('click', openCheckInCompanionModal);
    checkInCompanionCloseButtons.forEach(button => {
        button.addEventListener('click', closeCheckInCompanionModal);
    });

    checkInCompanionForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(checkInCompanionForm);
        const companionData = Object.fromEntries(formData.entries());

        companionCount++;
        const companionHtml = `
            <div class="guest-companion-pill">
                <span class="guest-companion-pill__name">${companionData.first_name} ${companionData.last_name}</span>
                <button type="button" class="guest-companion-pill__delete" data-companion-index="${companionCount}">Remove</button>
            </div>
        `;
        checkInCompanionList.insertAdjacentHTML('beforeend', companionHtml);

        checkInCompanionForm.reset();
        closeCheckInCompanionModal();
    });

    // ── Add companion(s) to a checked-in reservation (reservation modal) ────
    const resAddCompanionModal = document.getElementById('reservationAddCompanionModal');
    const resAddCompanionFor = document.getElementById('reservationAddCompanionFor');
    const resAddCloseButtons = document.querySelectorAll('[data-close-reservation-add-companion="true"]');
    const resAddSingleForm = document.getElementById('reservationAddSingleForm');
    const resAddBulkForm = document.getElementById('reservationAddBulkForm');
    const resAddTabs = document.querySelectorAll('[data-res-add-tab]');
    const resAddContents = document.querySelectorAll('[data-res-add-content]');

    // --- Live fee summary for the reservation add-companion modal ---
    // Mirror the backend: 12 and below = child; the pricing period comes from
    // the reservation's stored entrance pricing_type (no amenity) or its first
    // amenity's pricing_type (with amenities).
    const resAddEffectivePeriod = () => {
        const res = reservationData[currentReservationId];
        if (!res) return 'daytime';
        const efPeriodMap = { Nighttime: 'nighttime', DayToNight: 'daytonight', NightToDay: 'daytonight' };
        if (res.entrance_fee && efPeriodMap[res.entrance_fee.pricing_type]) {
            return efPeriodMap[res.entrance_fee.pricing_type];
        }
        const amenityPeriodMap = {
            'Daytime': 'daytime', 'Daytime Aircon': 'daytime',
            'Nighttime': 'nighttime', 'Nighttime Aircon': 'nighttime',
            'DayToNight': 'daytonight', 'DayToNight Aircon': 'daytonight',
            'NightToDay': 'daytonight', 'NightToDay Aircon': 'daytonight',
        };
        const firstAmenity = (res.reservation_amenities || []).find(a => parseFloat(a.price) > 0);
        return amenityPeriodMap[firstAmenity?.pricing_type] || 'daytime';
    };

    const resAddRates = () => {
        const p = parkSettings || {};
        const period = resAddEffectivePeriod();
        const dayAdult = parseFloat(p.daytime_adult_entrance_fee) || 0;
        const dayChild = parseFloat(p.daytime_child_entrance_fee) || 0;
        const nightAdult = parseFloat(p.nighttime_adult_entrance_fee) || 0;
        const nightChild = parseFloat(p.nighttime_child_entrance_fee) || 0;
        const dayPool = parseFloat(p.day_pool_fee) || 0;
        const nightPool = parseFloat(p.night_pool_fee) || 0;
        if (period === 'nighttime') return { adult: nightAdult, child: nightChild, pool: nightPool };
        if (period === 'daytonight' || period === 'nighttoday') return { adult: dayAdult + nightAdult, child: dayChild + nightChild, pool: dayPool + nightPool };
        return { adult: dayAdult, child: dayChild, pool: dayPool };
    };

    const money = (n) => `₱${(parseFloat(n) || 0).toFixed(2)}`;

    const resAddUpdateSingleFees = () => {
        const ageVal = parseInt(resAddSingleForm?.querySelector('[name="age"]')?.value, 10);
        const rates = resAddRates();
        const hasAge = !Number.isNaN(ageVal);
        const isChild = hasAge && ageVal <= 12;
        const isFree = Boolean(resAddSingleForm?.querySelector('[name="is_free_entrance"]')?.checked);
        const adultCount = hasAge && !isChild ? 1 : 0;
        const childCount = hasAge && isChild ? 1 : 0;
        const payingAdultCount = isFree ? 0 : adultCount;
        const payingChildCount = isFree ? 0 : childCount;
        const poolOn = resAddSingleForm?.querySelector('[name="pool_access"]')?.checked;
        const adultFee = payingAdultCount * rates.adult;
        const childFee = payingChildCount * rates.child;
        const poolFee = poolOn ? rates.pool : 0;

        // Calculate Extra Head Fee if assigned amenity has capacity limit
        const res = reservationData[currentReservationId];
        const resAmenities = res?.reservation_amenities || [];
        const amId = String(resAddSingleForm?.querySelector('[name="amenity_id"]')?.value || resAmenities[0]?.amenity?.id || resAmenities[0]?.amenity_id || resAmenities[0]?.id || '');
        const foundAmenity = resAmenities.find(ra => String(ra.amenity?.id || ra.amenity_id || ra.id) === amId);
        let extraHeadFee = 0;
        if (foundAmenity) {
            const amData = foundAmenity.amenity || foundAmenity;
            const maxCap = (amData.maximum_capacity !== null && amData.maximum_capacity !== undefined && amData.maximum_capacity !== '') ? parseInt(amData.maximum_capacity, 10) : null;
            const addRate = parseFloat(amData.additional_per_head) || 0;
            const currentGuestCount = (res.reservation_guests || []).length;
            if (maxCap !== null && !isNaN(maxCap) && currentGuestCount >= maxCap) {
                extraHeadFee = addRate;
            }
        }

        const set = (id, text) => { const el = document.getElementById(id); if (el) el.textContent = text; };
        set('resaddAdultCount', adultCount);
        set('resaddAdultFee', isFree && adultCount > 0 ? '₱0.00 (Free)' : money(adultFee));
        set('resaddChildCount', childCount);
        set('resaddChildFee', isFree && childCount > 0 ? '₱0.00 (Free)' : money(childFee));
        set('resaddPoolCount', poolOn ? 1 : 0);
        set('resaddPoolFee', money(poolFee));
        const extraHeadRow = document.getElementById('resaddExtraHeadRow');
        if (extraHeadRow) {
            extraHeadRow.style.display = extraHeadFee > 0 ? 'flex' : 'none';
            set('resaddExtraHeadFee', money(extraHeadFee));
        }
        set('resaddTotalFee', money(adultFee + childFee + poolFee + extraHeadFee));
    };

    const resAddUpdateBulkFees = () => {
        const qty = Math.min(Math.max(parseInt(resAddBulkForm?.querySelector('[name="quantity"]')?.value, 10) || 1, 1), 500);
        const ageGroup = resAddBulkForm?.querySelector('[name="age_group"]')?.value || '18-59';
        const rates = resAddRates();
        const isChild = ageGroup === '0-12';
        const isFree = Boolean(resAddBulkForm?.querySelector('[name="is_free_entrance"]')?.checked);
        const adultCount = isChild ? 0 : qty;
        const childCount = isChild ? qty : 0;
        const payingAdultCount = isFree ? 0 : adultCount;
        const payingChildCount = isFree ? 0 : childCount;
        const poolOn = resAddBulkForm?.querySelector('[name="pool_access"]')?.checked;
        const poolCount = poolOn ? qty : 0;
        const adultFee = payingAdultCount * rates.adult;
        const childFee = payingChildCount * rates.child;
        const poolFee = poolCount * rates.pool;

        // Calculate Extra Head Fee if assigned amenity has capacity limit
        const res = reservationData[currentReservationId];
        const resAmenities = res?.reservation_amenities || [];
        const amId = String(resAddBulkForm?.querySelector('[name="amenity_id"]')?.value || resAmenities[0]?.amenity?.id || resAmenities[0]?.amenity_id || resAmenities[0]?.id || '');
        const foundAmenity = resAmenities.find(ra => String(ra.amenity?.id || ra.amenity_id || ra.id) === amId);
        let bulkExtraHeadFee = 0;
        if (foundAmenity) {
            const amData = foundAmenity.amenity || foundAmenity;
            const maxCap = (amData.maximum_capacity !== null && amData.maximum_capacity !== undefined && amData.maximum_capacity !== '') ? parseInt(amData.maximum_capacity, 10) : null;
            const addRate = parseFloat(amData.additional_per_head) || 0;
            let currentGuestCount = (res.reservation_guests || []).length;
            for (let i = 0; i < qty; i++) {
                if (maxCap !== null && !isNaN(maxCap) && currentGuestCount >= maxCap) {
                    bulkExtraHeadFee += addRate;
                }
                currentGuestCount++;
            }
        }

        const set = (id, text) => { const el = document.getElementById(id); if (el) el.textContent = text; };
        set('resaddBulkAdultCount', adultCount);
        set('resaddBulkAdultFee', isFree && adultCount > 0 ? `₱0.00 (All ${qty} Free)` : money(adultFee));
        set('resaddBulkChildCount', childCount);
        set('resaddBulkChildFee', isFree && childCount > 0 ? `₱0.00 (All ${qty} Free)` : money(childFee));
        set('resaddBulkPoolCount', poolCount);
        set('resaddBulkPoolFee', money(poolFee));
        const bulkExtraHeadRow = document.getElementById('resaddBulkExtraHeadRow');
        if (bulkExtraHeadRow) {
            bulkExtraHeadRow.style.display = bulkExtraHeadFee > 0 ? 'flex' : 'none';
            set('resaddBulkExtraHeadFee', money(bulkExtraHeadFee));
        }
        set('resaddBulkTotalFee', money(adultFee + childFee + poolFee + bulkExtraHeadFee));
        const poolLabel = document.getElementById('resaddBulkPoolLabel');
        if (poolLabel) poolLabel.textContent = `Include Pool Access (all ${qty})`;
        const freeLabel = document.getElementById('resaddBulkFreeEntranceLabel');
        if (freeLabel) freeLabel.innerHTML = `<i class="bi bi-ticket-perforated-fill me-1"></i>Free Entrance Fee (all ${qty})`;
    };

    const resAddBindFeeWatchers = () => {
        const singleAge = resAddSingleForm?.querySelector('[name="age"]');
        singleAge?.addEventListener('input', resAddUpdateSingleFees);
        resAddSingleForm?.querySelector('[name="pool_access"]')?.addEventListener('change', resAddUpdateSingleFees);
        resAddSingleForm?.querySelector('[name="is_free_entrance"]')?.addEventListener('change', resAddUpdateSingleFees);
        resAddSingleForm?.querySelector('[name="amenity_id"]')?.addEventListener('change', resAddUpdateSingleFees);
        resAddBulkForm?.querySelector('[name="age_group"]')?.addEventListener('change', resAddUpdateBulkFees);
        resAddBulkForm?.querySelector('[name="quantity"]')?.addEventListener('input', resAddUpdateBulkFees);
        resAddBulkForm?.querySelector('[name="pool_access"]')?.addEventListener('change', resAddUpdateBulkFees);
        resAddBulkForm?.querySelector('[name="is_free_entrance"]')?.addEventListener('change', resAddUpdateBulkFees);
        resAddBulkForm?.querySelector('[name="amenity_id"]')?.addEventListener('change', resAddUpdateBulkFees);
    };

    const openResAddCompanionModal = () => {
        if (resAddCompanionFor && currentReservationId) {
            resAddCompanionFor.textContent = `Reservation #${currentReservationId}`;
        }

        // Setup Amenity select dropdowns for companion modals
        const res = reservationData[currentReservationId];
        const resAmenities = res?.reservation_amenities || [];
        const singleWrap = document.getElementById('resaddSingleAmenityWrap');
        const singleSelect = document.getElementById('resadd_amenity');
        const bulkWrap = document.getElementById('resaddBulkAmenityWrap');
        const bulkSelect = document.getElementById('resadd_bulk_amenity');

        if (resAmenities.length > 1) {
            let optionsHtml = '';
            resAmenities.forEach(ra => {
                const am = ra.amenity || ra;
                const amId = String(am.id || ra.amenity_id || '');
                const name = am.amenities_name || ra.amenity_name || 'Amenity';
                const max = (am.maximum_capacity !== null && am.maximum_capacity !== undefined && am.maximum_capacity !== '') ? `Max: ${am.maximum_capacity}` : 'No limit';
                const addFee = parseFloat(am.additional_per_head) > 0 ? ` (+₱${parseFloat(am.additional_per_head).toFixed(2)}/extra head)` : '';
                optionsHtml += `<option value="${amId}">${escapeHtml(name)} (${max}${addFee})</option>`;
            });
            if (singleSelect) singleSelect.innerHTML = optionsHtml;
            if (bulkSelect) bulkSelect.innerHTML = optionsHtml;
            if (singleWrap) singleWrap.style.display = 'grid';
            if (bulkWrap) bulkWrap.style.display = 'block';
        } else {
            if (singleWrap) singleWrap.style.display = 'none';
            if (bulkWrap) bulkWrap.style.display = 'none';
            if (resAmenities.length === 1) {
                const am = resAmenities[0].amenity || resAmenities[0];
                const singleId = String(am.id || resAmenities[0].amenity_id || '');
                const singleName = am.amenities_name || resAmenities[0].amenity_name || 'Amenity';
                if (singleSelect) singleSelect.innerHTML = `<option value="${singleId}" selected>${escapeHtml(singleName)}</option>`;
                if (bulkSelect) bulkSelect.innerHTML = `<option value="${singleId}" selected>${escapeHtml(singleName)}</option>`;
            }
        }

        loadParkSettings().then(() => {
            resAddUpdateSingleFees();
            resAddUpdateBulkFees();
        });
        resAddCompanionModal.classList.add('is-open');
        resAddCompanionModal.setAttribute('aria-hidden', 'false');
    };

    const closeResAddCompanionModal = () => {
        resAddCompanionModal.classList.remove('is-open');
        resAddCompanionModal.setAttribute('aria-hidden', 'true');
        resAddSingleForm?.reset();
        resAddBulkForm?.reset();
    };

    reservationAddCompanionBtn?.addEventListener('click', openResAddCompanionModal);
    resAddCloseButtons.forEach(button => button.addEventListener('click', closeResAddCompanionModal));

    resAddTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabType = tab.dataset.resAddTab;
            resAddTabs.forEach(t => {
                if (t.dataset.resAddTab === tabType) {
                    t.classList.add('guest-form__tab--active', 'bg-hp-green', 'text-white', 'font-bold');
                    t.classList.remove('bg-transparent', 'text-hp-text', 'font-semibold');
                } else {
                    t.classList.remove('guest-form__tab--active', 'bg-hp-green', 'text-white', 'font-bold');
                    t.classList.add('bg-transparent', 'text-hp-text', 'font-semibold');
                }
            });
            resAddContents.forEach(content => {
                const active = content.dataset.resAddContent === tabType;
                content.classList.toggle('guest-form--tab-content--active', active);
                content.style.display = active ? 'grid' : 'none';
            });
        });
    });

    resAddBindFeeWatchers();

    const postCompanionsToReservation = async (companions, submitButton, originalText) => {
        if (!currentReservationId || !companions.length) return;
        submitButton.disabled = true;
        submitButton.textContent = 'Adding...';
        try {
            const response = await fetch(`/staff/reservations/${currentReservationId}/add-companion`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ companions }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const firstError = payload.errors ? Object.values(payload.errors)[0]?.[0] : null;
                throw new Error(payload.message || firstError || 'Unable to add companion.');
            }
            closeAddCompanionModal();

            // Append added companions into in-memory dataset
            if (window.staffReservationData && window.staffReservationData[currentReservationId]) {
                const res = window.staffReservationData[currentReservationId];
                if (!res.reservation_guests) res.reservation_guests = [];
                companions.forEach(c => {
                    res.reservation_guests.push({
                        id: 'temp_' + Date.now() + Math.random(),
                        customer: { first_name: c.first_name || 'Companion', last_name: c.last_name || '' },
                        has_pool_access: Boolean(c.has_pool_access),
                        is_primary_guest: false,
                        checked_out_at: null,
                    });
                });
                res.number_of_guests = (parseInt(res.number_of_guests || 0, 10) + companions.length);
                openReservationModal(currentReservationId);
            }

            showToast(`${payload.added || companions.length} companion${(payload.added || companions.length) > 1 ? 's' : ''} added to Reservation #${currentReservationId}.`);
        } catch (error) {
            showToast(error.message || 'Unable to add companion.', 'error');
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    };

    resAddSingleForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(resAddSingleForm);
        const firstName = (formData.get('first_name') || '').trim();
        const lastName = (formData.get('last_name') || '').trim();
        if (!firstName || !lastName) {
            showToast('First name and last name are required.', 'error');
            return;
        }
        const isFree = Boolean(resAddSingleForm.querySelector('[name="is_free_entrance"]')?.checked);
        const submitButton = e.submitter || resAddSingleForm.querySelector('[type="submit"]');
        const res = reservationData[currentReservationId];
        const resAmenities = res?.reservation_amenities || [];
        const amId = String(formData.get('amenity_id') || resAmenities[0]?.amenity?.id || resAmenities[0]?.amenity_id || resAmenities[0]?.id || '');

        postCompanionsToReservation([{
            first_name: firstName,
            middle_name: formData.get('middle_name'),
            last_name: lastName,
            age: formData.get('age'),
            gender: formData.get('gender'),
            is_foreigner: formData.get('is_foreigner') === '1',
            phone: formData.get('phone'),
            email: formData.get('email'),
            pool_access: formData.get('pool_access') === 'on',
            is_free_entrance: isFree,
            amenity_id: amId,
        }], submitButton, 'Add Companion');
    });

    resAddBulkForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(resAddBulkForm);
        const quantity = Math.min(Math.max(parseInt(formData.get('quantity'), 10) || 1, 1), 500);
        const poolOn = formData.get('pool_access') === 'on';
        const isFree = Boolean(resAddBulkForm.querySelector('[name="is_free_entrance"]')?.checked);
        const res = reservationData[currentReservationId];
        const resAmenities = res?.reservation_amenities || [];
        const amId = String(formData.get('amenity_id') || resAmenities[0]?.amenity?.id || resAmenities[0]?.amenity_id || resAmenities[0]?.id || '');

        const companions = [];
        for (let i = 0; i < quantity; i++) {
            companions.push({
                first_name: '',
                last_name: '',
                age_group: formData.get('age_group'),
                gender: formData.get('gender'),
                is_foreigner: formData.get('is_foreigner') === '1',
                phone: '',
                email: '',
                pool_access: poolOn,
                is_free_entrance: isFree,
                amenity_id: amId,
            });
        }
        const submitButton = e.submitter || resAddBulkForm.querySelector('[type="submit"]');
        postCompanionsToReservation(companions, submitButton, 'Add Bulk Companions');
    });

    // Primary guest nationality handling
    const primaryGuestForm = document.getElementById('primaryGuestForm');
    if (primaryGuestForm) {
        const primaryGuestIsForeigner = document.getElementById('primaryGuestIsForeigner');
        primaryGuestIsForeigner?.addEventListener('change', (e) => {
            // Handle any UI changes if needed
        });
    }

    // If a companion was just added, auto-open that reservation's detail
    // modal so the addition is immediately visible under its reservation.
    const justAddedRes = (() => {
        try {
            const v = sessionStorage.getItem('hpJustAddedCompanionRes');
            if (v) sessionStorage.removeItem('hpJustAddedCompanionRes');
            return v;
        } catch (e) { return null; }
    })();
    if (justAddedRes && (window.staffReservationData?.[justAddedRes] || reservationData[justAddedRes])) {
        setTimeout(() => openReservationModal(justAddedRes), 450);
    }

    // Success toasts — show anything queued for after a reload and convert
    // server-rendered flash banners (session('success')) into toasts.
    convertFlashToToast();
    showPendingToast();
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['staff_check_ins']());