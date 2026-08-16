import { showToast, queueToast, showPendingToast, convertFlashToToast } from './toast.js';

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
    const reservationCloseButtons = document.querySelectorAll('[data-close-reservation-modal="true"]');
    const checkOutConfirmModal = document.getElementById('checkOutConfirmModal');
    const confirmCheckOutBtn = document.getElementById('confirmCheckOutBtn');
    const checkOutConfirmCloseButtons = document.querySelectorAll('[data-close-check-out-confirm="true"]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const reservationData = window.staffReservationData || {};
    const guestData = window.staffGuestData || {};

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
        if(dashboardSection) dashboardSection.style.display = target === 'dashboard' ? '' : 'none';
        if(guestTableSection) guestTableSection.style.display = target === 'guest' ? '' : 'none';
        if(reservationTableSection) reservationTableSection.style.display = target === 'reservation' ? '' : 'none';
        
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

    // Returns { visible, tone, left } for a checkout timestamp (ISO or empty).
    const getCheckoutState = (iso) => {
        if (!iso) return { visible: false, tone: '', left: 0 };
        const target = new Date(iso).getTime();
        if (Number.isNaN(target)) return { visible: false, tone: '', left: 0 };
        const remaining = target - Date.now();
        if (remaining <= 0) return { visible: true, tone: 'due', left: 0 };
        const tone = remaining <= CHECKOUT_WARN_MS ? 'warn' : (remaining <= CHECKOUT_NEAR_MS ? 'near' : 'far');
        return { visible: true, tone, left: remaining };
    };

    // Always-visible countdowns: reservation timer + per-amenity timers in the modal.
    const renderCountdownEl = (el) => {
        if (!el) return;
        const state = getCheckoutState(el.dataset.checkoutAt);
        if (!state.visible) {
            el.textContent = '';
            el.style.display = 'none';
            el.removeAttribute('data-checkout-state');
            return;
        }
        el.style.display = '';
        el.textContent = state.tone === 'due'
            ? 'Time to Checked Out'
            : `${formatTimeLeft(state.left)} left before check out`;
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
        let formattedTime = session === 'Nighttime' ? '6:00 AM (Next Day)' : '6:00 PM';

        if (res.checkout_at) {
            const dt = new Date(res.checkout_at);
            if (!isNaN(dt.getTime())) {
                formattedDate = dt.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                formattedTime = dt.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            }
        }

        if (formattedDate === 'N/A' && rawDate) {
            formattedDate = formatDate(rawDate);
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
        const reservation = reservationData[reservationId];

        if (!reservation) return;

        // Build modal content
        const guestsList = reservation.reservation_guests || [];
        const primaryGuest = guestsList.find(g => g.is_primary_guest);
        const companions = guestsList.filter(g => !g.is_primary_guest);

        // Does this reservation cover multiple amenity time periods?
        // (Daytime vs Daytime Aircon = same time; strip Aircon before comparing.)
        const validAmenities = (reservation.reservation_amenities || []).filter(a => a.price > 0 || a.price_at_booking > 0 || a.amenity_name || a.amenity);
        const uniquePricingTypes = [...new Set(validAmenities.map(a => String(a.pricing_type || 'N/A').replace(/\s*Aircon/gi, '').trim()))];
        const differentTime = validAmenities.length > 1 && uniquePricingTypes.length > 1;

        const expectedCheckout = formatExpectedCheckout(reservation);
        const startSlot = reservation.start_slot || 'Daytime';
        const endSlot = reservation.end_slot || startSlot;
        const isMultiDay = reservation.end_date && reservation.end_date !== reservation.reservation_date;

        let html = `
            <div class="ci-design-box">
                <div class="ci-col">
                    <span class="ci-label">BOOKER</span>
                    <div class="ci-value">${reservation.booker_name || 'N/A'}</div>
                </div>
                <div class="ci-col ci-border-left">
                    <span class="ci-label">CONTACT</span>
                    <div class="ci-value" style="font-weight: 500;">
                        ${reservation.phone || 'N/A'}<br>
                        ${reservation.email || 'N/A'}
                    </div>
                </div>
                <div class="ci-col ci-border-left">
                    <span class="ci-label">RESERVATION STAY</span>
                    <div class="ci-value" style="display:flex;align-items:center;gap:4px;flex-wrap:wrap;">
                        ${isMultiDay 
                            ? `${formatDate(reservation.reservation_date)} (${startSlot}) – ${formatDate(reservation.end_date)} (${endSlot}) <span class="resv-date-badge" style="margin-left:4px;">${reservation.total_days || 2} Days</span>` 
                            : `${formatDate(reservation.reservation_date)} (${startSlot})`}
                        ${differentTime ? '<span class="resv-date-badge" style="margin-left:4px;">Mixed Time</span>' : ''}
                    </div>
                </div>
                <div class="ci-col ci-border-left">
                    <span class="ci-label">GUESTS</span>
                    <div class="ci-value">${reservation.number_of_guests || (reservation.reservation_guests ? reservation.reservation_guests.length : 0)}</div>
                </div>
            </div>

            <!-- Expected Checkout Card -->
            <div class="ci-design-box" style="margin-top: 1rem; background: rgba(26,92,60,0.06); border: 1px solid rgba(26,92,60,0.25); border-radius: 0.75rem; padding: 0.85rem 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <span class="ci-label" style="color: #1a5c3c; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">Expected Check-out</span>
                    <div style="font-size: 1.05rem; font-weight: 700; color: #113824; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; margin-top: 3px;">
                        <span>${expectedCheckout.date}</span>
                        <span style="background: #1a5c3c; color: #ffffff; font-size: 0.78rem; font-weight: 700; padding: 3px 10px; border-radius: 999px;">${expectedCheckout.session}</span>
                        ${expectedCheckout.time ? `<span style="font-size: 0.88rem; font-weight: 500; color: #355e46;">at ${expectedCheckout.time}</span>` : ''}
                    </div>
                </div>
                ${(String(reservation.status || '').toLowerCase().includes('checked') && reservation.checkout_at) ? `<div class="resv-checkout-countdown" data-checkout-at="${reservation.checkout_at}" data-checkout-state=""></div>` : ''}
            </div>

            <div class="ci-design-box" style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div style="display:flex; gap: 2rem; flex-wrap: wrap;">
                    <div class="ci-col">
                        <span class="ci-label" style="text-transform: none;">Total Due:</span>
                        <div class="ci-value-lg">₱${parseFloat(reservation.total_amount || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</div>
                    </div>
                    <div class="ci-col ci-border-left">
                        <span class="ci-label" style="text-transform: none;">Paid to Date:</span>
                        <div class="ci-value-lg">₱${parseFloat(reservation.amount_paid || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</div>
                    </div>
                    <div class="ci-col ci-border-left">
                        <span class="ci-label" style="text-transform: none;">Balance Due:</span>
                        <div class="ci-value-lg">₱${parseFloat(reservation.remaining_balance || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</div>
                    </div>
                </div>
                <div>
                    <span class="ci-pill ${reservation.payment_status?.toLowerCase() === 'paid' ? 'ci-pill-green' : 'ci-pill-orange'}">
                        ${(reservation.payment_status || 'PENDING').toUpperCase()}
                    </span>
                </div>
            </div>

            ${(() => {
                // Highlight the reservation's time period(s): the amenity
                // pricing types when amenities were availed (each one shown,
                // so mixed periods are visible), otherwise the entrance fee's
                // stored time period.
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
                <div style="margin-top: 0.5rem; display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; background: rgba(194,146,29,0.10); border: 1px solid rgba(194,146,29,0.35); border-radius: 0.6rem; padding: 0.55rem 1rem;">
                    <span style="font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em; color: #a1760f;">Time Period:</span>
                    ${periods.map(t => `<span style="background: var(--hp-gold); color: #fff; font-weight: 700; font-size: 0.78rem; padding: 3px 10px; border-radius: 999px;">${t}</span>`).join('')}
                    ${differentTime ? '<span style="font-size: 0.75rem; font-weight: 600; color: #a1760f;">Mixed time periods</span>' : ''}
                </div>
                `;
            })()}

            ${reservation.entrance_fee ? `
                <div class="ci-design-box" style="margin-top: 0.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; padding: 0.65rem 1rem;">
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
        `;

        if (companions.length >= 0) {
            // Only guests still inside count — checked-out companions (and
            // fully checked-out bulk groups) must not appear as empty entries.
            const activeCompanions = companions.filter(c => !c.checked_out_at);
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
                    bulkGroups[key] = { gender, status, age, count: 0 };
                }
                bulkGroups[key].count++;
            });

            html += `
                <div style="margin-top:1.5rem;">
                    <h3 class="ci-section-title">GUESTS ON THIS RESERVATION</h3>
                    <div class="ci-guest-grid">
                        ${primaryGuest && primaryGuest.customer ? `
                            <div class="ci-guest-card">
                                <div class="ci-guest-icon">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                </div>
                                <div class="ci-guest-info">
                                    <div class="ci-guest-role">PRIMARY GUEST</div>
                                    <div class="ci-guest-name">${primaryGuest.customer.first_name} ${primaryGuest.customer.middle_name || ''} ${primaryGuest.customer.last_name}</div>
                                    <div class="ci-guest-meta">${primaryGuest.customer.age || 'N/A'} yrs - ${primaryGuest.customer.gender || 'N/A'} - ${primaryGuest.customer.is_foreigner ? 'Foreigner' : 'Filipino'}</div>
                                </div>
                            </div>
                        ` : '<div class="ci-guest-card"><div class="ci-guest-info"><div class="ci-guest-name">No main guest assigned</div></div></div>'}
            `;

            // Compact companion summary — just the counts so the card stays
            // short (details live in the guest table / reservation dropdown).
            if (bulkCompanions.length > 0 || individualCompanions.length > 0) {
                const totalCompanions = bulkCompanions.length + individualCompanions.length;
                const singleCount = individualCompanions.length;
                const bulkCount = bulkCompanions.length;
                let summaryLines = '';
                if (singleCount > 0) {
                    summaryLines += `<div class="ci-guest-meta" style="color: #333;">Single companions: <strong>${singleCount}</strong></div>`;
                }
                if (bulkCount > 0) {
                    const groupSummary = Object.values(bulkGroups)
                        .map(g => `${g.gender} · ${ageGroupLabel(g.age)} ×${g.count}`)
                        .join(' · ');
                    summaryLines += `<div class="ci-guest-meta" style="color: #333;">Bulk companions: <strong>${bulkCount}</strong>${groupSummary ? ` <span style="color: #888; font-size: 0.78rem;">(${groupSummary})</span>` : ''}</div>`;
                }
                html += `
                    <div class="ci-guest-card" style="align-items: flex-start;">
                        <div class="ci-guest-icon" style="margin-top: 0.2rem;">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                        </div>
                        <div class="ci-guest-info" style="width: 100%;">
                            <div class="ci-guest-role">COMPANIONS (${totalCompanions})</div>
                            ${summaryLines}
                        </div>
                    </div>
                `;
            }

            html += `
                    </div>
                </div>
            `;
        }

        if (reservation.reservation_amenities && reservation.reservation_amenities.length > 0) {
            if (validAmenities.length > 0) {
                const statusKey = String(reservation.status || '').toLowerCase().replace(/\s+/g, '_');
                const isCheckedIn = statusKey === 'checked_in';
                const showPerAmenityCheckout = isCheckedIn && differentTime;

                html += `
                <div style="margin-top:0.75rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span class="ci-modal-label" style="margin: 0;">Reserved Amenities</span>
                        ${showPerAmenityCheckout ? '<span class="resv-diff-time-label">Different amenity time</span>' : ''}
                    </div>
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
                                        ${!isCompleted && a.checkout_at ? `<div class="resv-amenity-countdown" data-checkout-at="${a.checkout_at}" data-checkout-state=""></div>` : ''}
                                    </div>
                                    <div class="resv-amenity-item__actions">
                                        ${isCompleted
                                            ? '<span class="resv-amenity-status resv-amenity-status--completed">Completed</span>'
                                            : (showPerAmenityCheckout
                                                ? `<button type="button" class="resv-amenity-checkout-btn" data-reservation-amenity-id="${a.id || ''}" data-reservation-id="${reservation.id}">Check Out</button>`
                                                : '<span class="resv-amenity-status resv-amenity-status--active">Active</span>')
                                        }
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
                `;
            }
        }
        
        html += `</div>`; // Close guest-card

        // Update modal status badge
        const statusBadge = document.getElementById('reservationModalStatus');
        if (statusBadge) {
            statusBadge.textContent = reservation.status || 'Active';
        }

        // Only checked-in reservations can accept new companions mid-stay.
        if (reservationAddCompanionBtn) {
            const statusKey = String(reservation.status || '').toLowerCase().replace(/\s+/g, '_');
            const isCheckedIn = statusKey === 'checked_in' || statusKey === 'active';
            reservationAddCompanionBtn.classList.toggle('hidden', !isCheckedIn);
        }

        reservationModalBody.innerHTML = html;
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

    // Reservation row click handlers
    const reservationRows = reservationTableBody?.querySelectorAll('.reservation-row') ?? [];
    reservationRows.forEach(row => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('.btn-expand-row')) return;
            const reservationId = row.dataset.reservationId;
            openReservationModal(reservationId);
        });
    });

    // Modal close handlers
    reservationCloseButtons.forEach(button => {
        button.addEventListener('click', closeReservationModal);
    });

    checkOutConfirmCloseButtons.forEach(button => {
        button.addEventListener('click', closeCheckOutConfirmModal);
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
                closeCheckOutConfirmModal();
                closeReservationModal();
                queueToast(`Reservation #${pendingCheckOutReservationId} checked out successfully.`);
                location.reload();
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

    // Per-amenity check out (delegated so it survives modal re-renders, and
    // guarded so SPA re-inits don't stack duplicate listeners)
    if (!window.__staffCheckInsAmenityCheckoutBound) {
        window.__staffCheckInsAmenityCheckoutBound = true;
        document.getElementById('reservationModalBody')?.addEventListener('click', async (e) => {
            const btn = e.target.closest('.resv-amenity-checkout-btn');
            if (!btn) return;
            e.stopPropagation();

            const reservationAmenityId = btn.dataset.reservationAmenityId;
            const reservationId = btn.dataset.reservationId;
            if (!reservationAmenityId) return;

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
                const amenity = reservationData[reservationId]?.reservation_amenities?.find(a => String(a.id) === String(reservationAmenityId));
                if (amenity) amenity.status = 'Completed';
                openReservationModal(reservationId);
                showToast('Amenity checked out successfully.');
            } catch (error) {
                window.alert(error.message || 'Unable to check out this amenity.');
                btn.disabled = false;
                btn.textContent = 'Check Out';
            }
        });
    }

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
    const btnDecrease = document.getElementById('bulkManageBtnDecrease');
    const btnIncrease = document.getElementById('bulkManageBtnIncrease');

    const openBulkManageModal = (resId, active, total, demoText = '', bulkGender = '', bulkAgeGroup = '', bulkNationality = '') => {
        currentBulkResId = resId;
        currentBulkGender = bulkGender || '';
        currentBulkAgeGroup = bulkAgeGroup || '';
        currentBulkIsForeigner = bulkNationality === 'Foreigner' ? true : (bulkNationality ? false : null);
        bulkManageResIdEl.textContent = resId;
        bulkManageActiveCountEl.textContent = active;
        bulkManageTotalCountEl.textContent = total;

        // Reset the quantity stepper for the next use.
        const qtyInput = document.getElementById('bulkManageQtyInput');
        if (qtyInput) {
            qtyInput.value = 1;
            qtyInput.max = Math.max(active, 1);
        }

        // The row/dropdown trigger already knows this group's demographics —
        // use them directly (falling back to the reservation's first bulk
        // guest when no group was specified).
        let demoHtml = demoText
            ? `<div style="font-size: 0.8rem; color: var(--hp-text-muted); margin-bottom: 1rem;">${demoText}</div>`
            : '';
        if (!demoHtml) {
            const res = reservationData[resId];
            if (res) {
                const bulkGuest = res.reservation_guests.find(rg => {
                    const fn = (rg.customer?.first_name || '').toLowerCase();
                    return fn.startsWith('bulk') || fn.includes('companion');
                });
                if (bulkGuest && bulkGuest.customer) {
                    const c = bulkGuest.customer;
                    const gender = c.gender || 'N/A';
                    const nationality = c.is_foreigner ? 'Foreigner' : 'Filipino';
                    demoHtml = `<div style="font-size: 0.8rem; color: var(--hp-text-muted); margin-bottom: 1rem;">${gender} &bull; ${ageGroupLabel(c.age)} &bull; ${nationality}</div>`;
                }
            }
        }

        const demoEl = document.getElementById('bulkManageDemographics');
        if (demoEl) {
            demoEl.innerHTML = demoHtml;
        }

        bulkGroupManageModal.classList.add('is-open');
        bulkGroupManageModal.setAttribute('aria-hidden', 'false');
    };

    const closeBulkManageModal = () => {
        currentBulkResId = null;
        bulkGroupManageModal.classList.remove('is-open');
        bulkGroupManageModal.setAttribute('aria-hidden', 'true');
    };

    document.querySelectorAll('[data-close-bulk-manage-modal="true"]').forEach(btn => {
        btn.addEventListener('click', closeBulkManageModal);
    });
    
    // Check out one or several bulk companions at once. Shared by the minus
    // button (1x) and the quantity stepper + Check Out button (Nx). Shows a
    // toast on success instead of silently reloading.
    const bulkCheckOut = async (count) => {
        if (!currentBulkResId) return;
        const activeCount = Number(bulkManageActiveCountEl?.textContent || 0);
        if (activeCount === 0) {
            showToast('All bulk companions are already checked out.', 'error');
            return;
        }

        const qty = Math.min(Math.max(parseInt(count, 10) || 1, 1), activeCount);
        if (!confirm(`Check out ${qty} companion${qty === 1 ? '' : 's'} from this bulk group?`)) {
            return;
        }

        const submitBtn = document.getElementById('bulkManageCheckOutBtn');
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
                ? `${done} bulk companion${done === 1 ? '' : 's'} checked out. ${remaining} still inside.`
                : `${done} bulk companion${done === 1 ? '' : 's'} checked out successfully.`;
            queueToast(message);
            window.location.reload();
        } catch (err) {
            console.error(err);
            showToast(err.message || 'Unable to check out companions.', 'error');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Check Out';
            }
        }
    };

    btnDecrease?.addEventListener('click', () => bulkCheckOut(1));

    const bulkManageQtyMinus = document.getElementById('bulkManageQtyMinus');
    const bulkManageQtyPlus = document.getElementById('bulkManageQtyPlus');
    const bulkManageQtyInput = document.getElementById('bulkManageQtyInput');
    const bulkManageCheckOutBtn = document.getElementById('bulkManageCheckOutBtn');

    bulkManageQtyMinus?.addEventListener('click', () => {
        if (!bulkManageQtyInput) return;
        const val = parseInt(bulkManageQtyInput.value, 10) || 1;
        if (val > 1) bulkManageQtyInput.value = val - 1;
    });
    bulkManageQtyPlus?.addEventListener('click', () => {
        if (!bulkManageQtyInput) return;
        const val = parseInt(bulkManageQtyInput.value, 10) || 1;
        const max = parseInt(bulkManageQtyInput.max || '50', 10) || 50;
        if (val < max) bulkManageQtyInput.value = val + 1;
    });
    bulkManageCheckOutBtn?.addEventListener('click', () => {
        bulkCheckOut(bulkManageQtyInput?.value || 1);
    });

    guestRows.forEach(row => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('.btn-expand-row')) return;
            if (row.dataset.bulkGroup === 'true') {
                openBulkManageModal(row.dataset.reservationId, row.dataset.bulkActive, row.dataset.bulkTotal, row.dataset.bulkDemo, row.dataset.bulkGender, row.dataset.bulkAgeGroup, row.dataset.bulkNationality);
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
                             // Clicking a guest (main or single companion) opens
                             // the same detail modal as the guest table.
                             guestsHtml += `<div data-guest-id="${g.customer_id || ''}" title="View details" style="display: flex; align-items: center; font-size: 0.85rem; font-weight: 500; padding: 4px 0; cursor: pointer; border-radius: 6px; transition: background 0.15s ease;">
                                <span style="width: 0.55rem; height: 0.55rem; border-radius: 50%; margin-right: 0.5rem; flex-shrink: 0; background: ${g.is_primary_guest ? 'var(--hp-gold)' : 'var(--hp-green)'};"></span>
                                ${g.customer.first_name} ${g.customer.middle_name || ''} ${g.customer.last_name} ${pill}
                                <span style="color: #888; font-size: 0.75rem; margin-left: auto;">${g.customer.gender || 'Unknown'} • ${g.customer.age || 'N/A'} yrs</span>
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
                                 const activeBulk = group.members.filter(g => !g.checked_out_at).length;
                                 // Fully checked-out groups disappear from the
                                 // dropdown — never show an empty group.
                                 if (activeBulk === 0) return;
                                 const demo = `${group.gender} · ${group.ageGroup} · ${group.nationality}`;
                                 guestsHtml += `<div class="bulk-group-row-trigger" data-res-id="${resId}" data-bulk-active="${activeBulk}" data-bulk-total="${group.members.length}" data-bulk-demo="${demo}" data-bulk-gender="${group.gender}" data-bulk-age-group="${group.ageGroup}" data-bulk-nationality="${group.nationality}" style="display: flex; align-items: center; font-size: 0.85rem; font-weight: 500; cursor: pointer; padding: 4px 0; border-top: 1px solid rgba(0,0,0,0.05); margin-top: 4px; color: var(--hp-green);">
                                    <span style="width: 0.55rem; height: 0.55rem; border-radius: 50%; margin-right: 0.5rem; flex-shrink: 0; background: #0e7490;"></span>
                                    Bulk Companions (#${resId}) <span style="font-size: 0.65rem; background: #0e7490; color: #fff; padding: 2px 6px; border-radius: 12px; margin-left: 8px;">${activeBulk}/${group.members.length} Checked In</span>
                                    <span style="color: #888; font-size: 0.75rem; margin-left: auto;">${demo}</span>
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
            openBulkManageModal(bulkTrigger.dataset.resId, bulkTrigger.dataset.bulkActive, bulkTrigger.dataset.bulkTotal, bulkTrigger.dataset.bulkDemo, bulkTrigger.dataset.bulkGender, bulkTrigger.dataset.bulkAgeGroup, bulkTrigger.dataset.bulkNationality);
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

            queueToast(`${customer.first_name || 'Guest'} checked out successfully.`);
            window.location.reload();
        } catch (error) {
            window.alert(error.message || 'Unable to check out this guest.');
            submitButton.disabled = false;
            submitButton.textContent = 'Check Out';
        }
    });

    // ==========================================
    // Walk-In Guest Reservation & Multi-Day System
    // ==========================================
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

        const sFmt = formatDisplayDate(walkInSchedule.startDate);
        const eFmt = formatDisplayDate(walkInSchedule.endDate);

        if (summaryText) {
            const spanLabel = counts.daysSpan === 1 ? '1 Day' : `${counts.daysSpan} Days`;
            const breakdown = `(${counts.dayCount}D ${counts.nightCount}N)`;
            summaryText.textContent = `${spanLabel} ${breakdown}`;
        }
        if (datesText) {
            if (walkInSchedule.startDate === walkInSchedule.endDate) {
                datesText.textContent = `${sFmt} (${walkInSchedule.startSlot}${walkInSchedule.startSlot !== walkInSchedule.endSlot ? ' to ' + walkInSchedule.endSlot : ''})`;
            } else {
                datesText.textContent = `${sFmt} (${walkInSchedule.startSlot}) → ${eFmt} (${walkInSchedule.endSlot})`;
            }
        }
    };

    const openAddGuestModal = () => {
        if (!addGuestModal) return;
        addGuestModal.classList.add('is-open');
        addGuestModal.classList.remove('hidden');
        addGuestModal.setAttribute('aria-hidden', 'false');
        loadParkSettings();
        syncMasterScheduleDisplay();
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
    });

    addGuestCloseButtons.forEach(button => {
        button.addEventListener('click', closeAddGuestModal);
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

    // Calculate entrance fee based on main guest, companions, and age types
    const calculateEntranceFee = () => {
        const counts = calculateWalkInSlots(walkInSchedule.startDate, walkInSchedule.endDate, walkInSchedule.startSlot, walkInSchedule.endSlot);
        const timeType = counts.nightCount > 0 && counts.dayCount > 0 ? 'daytonight' : (counts.nightCount > 0 ? 'nighttime' : 'daytime');
        const includePoolChecked = includePool?.checked || false;

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
            if (g.age_group === '0-12') {
                childCount += qty;
            } else {
                adultCount += qty;
            }
        });

        const totalGuests = adultCount + childCount;
        const totalAdultFee = adultCount * adultRate;
        const totalChildFee = childCount * childRate;
        const totalPoolFee = includePoolChecked ? totalGuests * poolRate : 0;
        const totalEntrance = totalAdultFee + totalChildFee + totalPoolFee;

        if (adultEntranceFee) adultEntranceFee.textContent = `₱${totalAdultFee.toFixed(2)} (${adultCount} × ₱${adultRate.toFixed(2)})`;
        if (childEntranceFee) childEntranceFee.textContent = `₱${totalChildFee.toFixed(2)} (${childCount} × ₱${childRate.toFixed(2)})`;
        if (poolFee) poolFee.textContent = includePoolChecked ? `₱${totalPoolFee.toFixed(2)} (${totalGuests} × ₱${poolRate.toFixed(2)})` : '₱0.00';
        if (totalEntranceFee) totalEntranceFee.textContent = `₱${totalEntrance.toFixed(2)}`;

        return { totalEntrance, adultCount, childCount, totalPoolFee };
    };

    // Calculate Grand Total (Entrance + Pool + Amenities)
    const updateGrandTotal = () => {
        const { totalEntrance } = calculateEntranceFee();
        const amenitiesTotal = selectedAmenities.reduce((sum, a) => sum + (parseFloat(a.price_at_booking) || 0), 0);
        const grandTotal = totalEntrance + amenitiesTotal;

        if (walkInAmenitiesSubtotal) {
            walkInAmenitiesSubtotal.textContent = `₱${amenitiesTotal.toFixed(2)}`;
        }
        if (reservationTotal) {
            reservationTotal.textContent = `₱${grandTotal.toFixed(2)}`;
        }
        if (totalAmountInput) {
            totalAmountInput.value = grandTotal;
        }

        const companionCountBadge = document.getElementById('walkInCompanionCountBadge');
        if (companionCountBadge) {
            const totalCompanions = companions.length + bulkCompanionGroups.reduce((acc, g) => acc + (parseInt(g.quantity) || 1), 0);
            companionCountBadge.textContent = `${totalCompanions} companion${totalCompanions === 1 ? '' : 's'}`;
        }

        return { entranceFeeTotal: totalEntrance, amenitiesTotal, grandTotal };
    };

    includePool?.addEventListener('change', updateGrandTotal);

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
        const includePoolChecked = includePool?.checked || false;

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

        const { totalEntrance, adultCount, childCount, totalPoolFee } = calculateEntranceFee();
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
            if (primaryPhone) parts.push(`📞 ${primaryPhone}`);
            if (primaryEmail) parts.push(`✉️ ${primaryEmail}`);
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
            payConfirmPartyCount.textContent = `${totalGuests} Guest${totalGuests === 1 ? '' : 's'} (${adultCount} Adult${adultCount === 1 ? '' : 's'}, ${childCount} Child${childCount === 1 ? '' : 'ren'})`;
        }

        // 3. Entrance & Pool Fees
        const payConfirmAdultFee = document.getElementById('payConfirmAdultFee');
        const payConfirmChildFee = document.getElementById('payConfirmChildFee');
        const payConfirmPoolFee = document.getElementById('payConfirmPoolFee');
        const payConfirmEntranceTotal = document.getElementById('payConfirmEntranceTotal');

        if (payConfirmAdultFee) payConfirmAdultFee.textContent = `₱${(adultCount * adultRate).toFixed(2)} (${adultCount} × ₱${adultRate.toFixed(2)})`;
        if (payConfirmChildFee) payConfirmChildFee.textContent = `₱${(childCount * childRate).toFixed(2)} (${childCount} × ₱${childRate.toFixed(2)})`;
        if (payConfirmPoolFee) payConfirmPoolFee.textContent = includePoolChecked ? `₱${totalPoolFee.toFixed(2)} (${totalGuests} × ₱${poolRate.toFixed(2)})` : '₱0.00 (No pool access)';
        if (payConfirmEntranceTotal) payConfirmEntranceTotal.textContent = `₱${totalEntrance.toFixed(2)}`;

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
                        <strong class="text-hp-green font-bold">₱${amPrice.toFixed(2)}</strong>
                    `;
                    payConfirmAmenitiesList.appendChild(item);
                });
            }
        }

        if (payConfirmAmenitiesSubtotal) {
            payConfirmAmenitiesSubtotal.textContent = `₱${amenitiesTotal.toFixed(2)}`;
        }

        // 5. Grand Total
        const grandTotal = totalEntrance + amenitiesTotal;
        const payConfirmGrandTotal = document.getElementById('payConfirmGrandTotal');
        if (payConfirmGrandTotal) {
            payConfirmGrandTotal.textContent = `₱${grandTotal.toFixed(2)}`;
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
    const companionForm = document.getElementById('companionForm');
    const bulkCompanionForm = document.getElementById('bulkCompanionForm');
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

    const openCompanionModal = () => {
        companionModal.classList.add('is-open');
        companionModal.classList.remove('hidden');
        companionModal.setAttribute('aria-hidden', 'false');
        syncCompanionAgeBadge();
    };

    const closeCompanionModal = () => {
        companionModal.classList.remove('is-open');
        companionModal.setAttribute('aria-hidden', 'true');
        // Reset forms
        companionForm?.reset();
        syncCompanionAgeBadge();
        bulkCompanionForm?.reset();
    };

    addCompanionBtn?.addEventListener('click', openCompanionModal);
    companionCloseButtons.forEach(button => {
        button.addEventListener('click', closeCompanionModal);
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

    // Render companions
    const renderCompanions = () => {
        companionList.innerHTML = '';
        companionHiddenFields.innerHTML = '';

        // Render individual companions
        companions.forEach((companion, index) => {
            const nationality = companion.is_foreigner ? 'Foreigner' : 'Filipino';
            const rateLabel = companion.age_type === 'child' ? 'Child' : 'Adult';
            const item = document.createElement('div');
            item.className = 'guest-companion-pill flex items-center justify-between gap-2 p-2.5 rounded-xl border border-glass-border bg-glass mb-2';
            item.innerHTML = `
                <span class="guest-companion-pill__name text-sm font-medium text-hp-text">${companion.first_name} ${companion.last_name} - ${nationality} - ${companion.age ? companion.age + ' yrs (' + rateLabel + ')' : rateLabel} - ${companion.gender}</span>
                <button type="button" class="guest-companion-pill__delete text-xs font-bold text-red-500 hover:text-red-700 transition-colors cursor-pointer" data-companion-index="${index}">Remove</button>
            `;
            companionList.appendChild(item);

            // Add hidden fields
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
            `);
        });

        // Render bulk companion groups
        bulkCompanionGroups.forEach((group, groupIndex) => {
            const nationality = group.is_foreigner ? 'Foreigner' : 'Filipino';
            const rateLabel = (group.age_group === '0-12' || group.age_type === 'child') ? 'Child' : 'Adult';
            const item = document.createElement('div');
            item.className = 'guest-companion-pill guest-companion-pill--bulk flex items-center justify-between gap-2 p-2.5 rounded-xl border border-glass-border bg-glass mb-2';
            item.innerHTML = `
                <span class="guest-companion-pill__name text-sm font-medium text-hp-text">Bulk: ${group.quantity} × ${group.gender} - ${nationality} - Age Group: ${group.age_group} (${rateLabel})</span>
                <button type="button" class="guest-companion-pill__delete text-xs font-bold text-red-500 hover:text-red-700 transition-colors cursor-pointer" data-bulk-index="${groupIndex}">Remove</button>
            `;
            companionList.appendChild(item);

            // Add hidden fields for each companion in the group
            for (let i = 0; i < group.quantity; i++) {
                const companionIndex = companions.length + groupIndex * 1000 + i;
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
                `);
            }
        });

        if (companions.length === 0 && bulkCompanionGroups.length === 0) {
            companionList.innerHTML = '<p class="guest-empty text-xs text-hp-text-muted italic py-2">No companions added yet.</p>';
        }
    };

    // Single companion form submission
    companionForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const formData = new FormData(companionForm);
        const ageVal = formData.get('age');
        const parsedAge = parseInt(ageVal, 10);
        const autoAgeType = (!isNaN(parsedAge) && parsedAge <= 12) ? 'child' : 'adult';

        const companionData = {
            first_name: formData.get('first_name') || 'Companion',
            middle_name: formData.get('middle_name'),
            last_name: formData.get('last_name') || 'Guest',
            age: ageVal,
            age_type: autoAgeType,
            gender: formData.get('gender') || 'Male',
            is_foreigner: formData.get('is_foreigner') === '1',
            phone: formData.get('phone'),
            email: formData.get('email'),
        };
        
        companions.push(companionData);
        renderCompanions();
        updateGrandTotal();
        companionForm.reset();
        syncCompanionAgeBadge();
        closeCompanionModal();
    });

    // Bulk companion form submission
    bulkCompanionForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(bulkCompanionForm);
        const gender = formData.get('gender') || 'Male';
        const ageGroup = formData.get('age_group') || '18-59';
        const isForeigner = formData.get('is_foreigner') === '1';
        const quantity = parseInt(formData.get('quantity'), 10) || 1;
        const ageType = (ageGroup === '0-12') ? 'child' : 'adult';
        
        bulkCompanionGroups.push({
            gender,
            age_group: ageGroup,
            age_type: ageType,
            is_foreigner: isForeigner,
            quantity: quantity,
        });
        
        renderCompanions();
        updateGrandTotal();
        bulkCompanionForm.reset();
        closeCompanionModal();
    });

    // Delete companion handlers (both single & bulk)
    companionList?.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('.guest-companion-pill__delete');
        if (!removeBtn) return;
        
        const index = removeBtn.dataset.companionIndex;
        const bulkIndex = removeBtn.dataset.bulkIndex;
        
        if (index !== undefined && index !== null && index !== '') {
            companions.splice(parseInt(index, 10), 1);
        } else if (bulkIndex !== undefined && bulkIndex !== null && bulkIndex !== '') {
            bulkCompanionGroups.splice(parseInt(bulkIndex, 10), 1);
        }
        
        renderCompanions();
        updateGrandTotal();
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
        document.querySelectorAll('#walkInEndSlotGroup [data-slot-val]').forEach(btn => {
            const val = btn.dataset.slotVal;
            btn.dataset.active = (val === walkInCalState.selectedEndSlot) ? 'true' : 'false';
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
        const eFmt = formatDisplayDate(walkInCalState.selectedEndDate);

        if (walkInCalSummaryText) {
            if (walkInCalState.selectedStartDate === walkInCalState.selectedEndDate) {
                walkInCalSummaryText.textContent = `Today: ${sFmt} (${walkInCalState.selectedStartSlot}${walkInCalState.selectedStartSlot !== walkInCalState.selectedEndSlot ? ' to ' + walkInCalState.selectedEndSlot : ''})`;
            } else {
                walkInCalSummaryText.textContent = `Check-In Today (${walkInCalState.selectedStartSlot}) → Check-Out ${eFmt} (${walkInCalState.selectedEndSlot})`;
            }
        }
        if (walkInCalSpanText) {
            const span = counts.daysSpan === 1 ? '1 Day' : `${counts.daysSpan} Days`;
            walkInCalSpanText.textContent = `${span} Stay (${counts.dayCount} Daytime, ${counts.nightCount} Nighttime)`;
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
            const list = data.amenities || [];

            if (list.length === 0) {
                container.innerHTML = '<p class="guest-empty px-4 py-8 text-center text-hp-text-muted">No amenities found in the system.</p>';
                return;
            }

            container.innerHTML = '';
            list.forEach(amenity => {
                const isAvailable = Boolean(amenity.is_available);
                const isAlreadySelected = selectedAmenities.some(a => String(a.amenity_id) === String(amenity.id));
                const hasAc = amenity.daytime_aircon_price !== null || amenity.nighttime_aircon_price !== null;

                const dayP = parseFloat(amenity.daytime_price) || 0;
                const nightP = parseFloat(amenity.nighttime_price) || 0;
                const calculatedPrice = (counts.dayCount * dayP) + (counts.nightCount * nightP);

                const card = document.createElement('div');
                card.className = `walkin-amenity-card flex flex-wrap items-center justify-between gap-3 rounded-xl border p-3.5 transition-all duration-200 ${
                    isAvailable
                        ? (isAlreadySelected ? 'border-hp-green/60 bg-hp-green/5' : 'border-glass-border bg-glass hover:border-hp-green')
                        : 'border-red-300/40 bg-red-50/20 opacity-60 dark:border-red-500/20 dark:bg-red-500/5'
                }`;
                card.dataset.amenityId = amenity.id;

                card.innerHTML = `
                    <div class="flex-1 min-w-[200px]">
                        <div class="flex items-center gap-2">
                            <strong class="text-sm font-bold text-hp-text dark:text-[#c8e6c8]">${amenity.amenities_name}</strong>
                            ${isAvailable
                                ? '<span class="rounded bg-emerald-500/10 px-2 py-0.5 text-[0.68rem] font-bold text-emerald-600 dark:text-emerald-400">Available</span>'
                                : '<span class="rounded bg-red-500/10 px-2 py-0.5 text-[0.68rem] font-bold text-red-600 dark:text-red-400">Booked for this stay</span>'
                            }
                            ${hasAc ? '<span class="rounded bg-blue-500/10 px-1.5 py-0.5 text-[0.65rem] font-bold text-blue-600 dark:text-blue-400">AC Option</span>' : ''}
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-hp-text-muted">
                            <span>Capacity: ${amenity.minimum_capacity || 1}–${amenity.maximum_capacity || 10} guests</span>
                            <span>•</span>
                            <span>Day: ₱${dayP.toFixed(2)}</span>
                            <span>•</span>
                            <span>Night: ₱${nightP.toFixed(2)}</span>
                        </div>
                        ${isAvailable ? `
                            <div class="mt-1.5 text-xs font-semibold text-hp-green">
                                Stay Total: ₱${calculatedPrice.toFixed(2)} <span class="font-normal text-hp-text-muted">(${spanLabel} • ${counts.dayCount}D ${counts.nightCount}N)</span>
                            </div>
                        ` : ''}
                    </div>
                    <div>
                        ${isAlreadySelected ? `
                            <button type="button" class="rounded-xl border border-hp-green bg-hp-green px-3.5 py-1.5 text-xs font-bold text-white cursor-default" disabled>
                                ✓ Added
                            </button>
                        ` : (isAvailable ? `
                            <button type="button" class="walkin-add-amenity-btn cursor-pointer rounded-xl border border-hp-green bg-hp-green/10 px-3.5 py-1.5 text-xs font-bold text-hp-green transition-all duration-150 hover:bg-hp-green hover:text-white" data-amenity-id="${amenity.id}">
                                + Add Amenity
                            </button>
                        ` : `
                            <button type="button" class="rounded-xl border border-glass-border bg-glass px-3.5 py-1.5 text-xs font-semibold text-hp-text-muted cursor-not-allowed opacity-60" disabled>
                                Unavailable
                            </button>
                        `)}
                    </div>
                `;

                container.appendChild(card);
            });
        } catch (err) {
            console.error('Failed to load available amenities:', err);
            container.innerHTML = '<p class="guest-empty px-4 py-8 text-center text-red-500">Failed to load amenity availability. Please close and retry.</p>';
        }
    };

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
            const amenity = allAmenities.find(a => String(a.id) === String(amenityId));
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

            selectedAmenities.push({
                amenity_id: amenity.id,
                amenity_name: amenity.amenities_name,
                min_cap: amenity.minimum_capacity,
                max_cap: amenity.maximum_capacity,
                start_date: walkInSchedule.startDate,
                end_date: walkInSchedule.endDate,
                start_slot: walkInSchedule.startSlot,
                end_slot: walkInSchedule.endSlot,
                is_aircon: false,
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

        if (selectedAmenities.length === 0) {
            if (noAmenitiesNotice) noAmenitiesNotice.style.display = 'block';
            selectedAmenitiesContainer.appendChild(noAmenitiesNotice);
            updateGrandTotal();
            return;
        }

        if (noAmenitiesNotice) noAmenitiesNotice.style.display = 'none';

        selectedAmenities.forEach((am, index) => {
            const hasAcOption = am.daytime_aircon_price !== null || am.nighttime_aircon_price !== null;
            const sFmt = formatDisplayDate(am.start_date);
            const eFmt = formatDisplayDate(am.end_date);
            const spanLabel = am.total_days === 1 ? '1 Day' : `${am.total_days} Days`;

            const card = document.createElement('div');
            card.className = 'selected-amenity-card rounded-xl border border-glass-border bg-glass p-3.5 shadow-sm transition-all duration-200';
            card.dataset.index = index;

            card.innerHTML = `
                <div class="flex flex-wrap items-start justify-between gap-2 border-b border-glass-border/50 pb-2.5">
                    <div>
                        <strong class="text-sm font-bold text-hp-text dark:text-[#c8e6c8]">${am.amenity_name}</strong>
                        <div class="text-xs text-hp-text-muted">Capacity: ${am.min_cap || 1}–${am.max_cap || 10} guests</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-extrabold text-hp-green">₱${parseFloat(am.price_at_booking).toFixed(2)}</div>
                        <button type="button" class="walkin-remove-amenity-btn text-[0.75rem] font-bold text-red-500 hover:text-red-700 transition-colors" data-index="${index}">
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
                    <button type="button" class="walkin-customize-amenity-btn cursor-pointer rounded-lg border border-hp-green/40 bg-hp-green/10 px-2.5 py-1 text-xs font-bold text-hp-green hover:bg-hp-green hover:text-white transition-all" data-index="${index}">
                        Customize Stay
                    </button>
                </div>

                ${hasAcOption ? `
                    <div class="mt-2 flex items-center gap-2 border-t border-glass-border/40 pt-2 text-xs">
                        <label class="flex cursor-pointer items-center gap-1.5 text-hp-text">
                            <input type="checkbox" class="walkin-card-ac-toggle h-3.5 w-3.5 accent-hp-green" data-index="${index}" ${am.is_aircon ? 'checked' : ''}>
                            <span class="font-semibold">Air-Conditioned</span>
                        </label>
                    </div>
                ` : ''}
            `;

            selectedAmenitiesContainer.appendChild(card);

            // Hidden inputs for backend submission
            const pricingTypeStr = am.total_days > 1
                ? `Continuous Stay (${am.total_days}D)${am.is_aircon ? ' Aircon' : ''}`
                : ((am.start_slot === 'Daytime' && am.end_slot === 'Nighttime') ? (am.is_aircon ? 'DayToNight Aircon' : 'DayToNight') : (am.is_aircon ? `${am.start_slot} Aircon` : am.start_slot));

            amenitiesHiddenInputs.insertAdjacentHTML('beforeend', `
                <input type="hidden" name="selected_amenities[${index}][amenity_id]" value="${am.amenity_id}">
                <input type="hidden" name="selected_amenities[${index}][start_date]" value="${am.start_date}">
                <input type="hidden" name="selected_amenities[${index}][end_date]" value="${am.end_date}">
                <input type="hidden" name="selected_amenities[${index}][start_slot]" value="${am.start_slot}">
                <input type="hidden" name="selected_amenities[${index}][end_slot]" value="${am.end_slot}">
                <input type="hidden" name="selected_amenities[${index}][pricing_type]" value="${pricingTypeStr}">
                <input type="hidden" name="selected_amenities[${index}][price_at_booking]" value="${am.price_at_booking}">
                <input type="hidden" name="selected_amenities[${index}][is_aircon]" value="${am.is_aircon ? '1' : '0'}">
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
            if (counts.dayCount > 0) parts.push(`${counts.dayCount}D × ₱${dayP.toFixed(2)}`);
            if (counts.nightCount > 0) parts.push(`${counts.nightCount}N × ₱${nightP.toFixed(2)}`);
            walkInAmenityMathText.textContent = parts.join(' + ') || '0 slots';
        }
        if (walkInAmenityTotalPrice) {
            walkInAmenityTotalPrice.textContent = `₱${totalP.toFixed(2)}`;
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
    
    const openScanQrModal = () => {
        scanQrModal.classList.add('is-open');
        scanQrModal.setAttribute('aria-hidden', 'false');
    };
    
    const closeScanQrModal = () => {
        scanQrModal.classList.remove('is-open');
        scanQrModal.setAttribute('aria-hidden', 'true');
    };
    
    scanQrBtn?.addEventListener('click', openScanQrModal);
    scanQrCloseButtons.forEach(button => {
        button.addEventListener('click', closeScanQrModal);
    });

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
        const adultCount = hasAge && !isChild ? 1 : 0;
        const childCount = hasAge && isChild ? 1 : 0;
        const poolOn = resAddSingleForm?.querySelector('[name="pool_access"]')?.checked;
        const adultFee = adultCount * rates.adult;
        const childFee = childCount * rates.child;
        const poolFee = poolOn ? rates.pool : 0;
        const set = (id, text) => { const el = document.getElementById(id); if (el) el.textContent = text; };
        set('resaddAdultCount', adultCount);
        set('resaddAdultFee', money(adultFee));
        set('resaddChildCount', childCount);
        set('resaddChildFee', money(childFee));
        set('resaddPoolCount', poolOn ? 1 : 0);
        set('resaddPoolFee', money(poolFee));
        set('resaddTotalFee', money(adultFee + childFee + poolFee));
    };

    const resAddUpdateBulkFees = () => {
        const qty = Math.min(Math.max(parseInt(resAddBulkForm?.querySelector('[name="quantity"]')?.value, 10) || 1, 1), 500);
        const ageGroup = resAddBulkForm?.querySelector('[name="age_group"]')?.value || '18-59';
        const rates = resAddRates();
        const isChild = ageGroup === '0-12';
        const adultCount = isChild ? 0 : qty;
        const childCount = isChild ? qty : 0;
        const poolOn = resAddBulkForm?.querySelector('[name="pool_access"]')?.checked;
        const poolCount = poolOn ? qty : 0;
        const adultFee = adultCount * rates.adult;
        const childFee = childCount * rates.child;
        const poolFee = poolCount * rates.pool;
        const set = (id, text) => { const el = document.getElementById(id); if (el) el.textContent = text; };
        set('resaddBulkAdultCount', adultCount);
        set('resaddBulkAdultFee', money(adultFee));
        set('resaddBulkChildCount', childCount);
        set('resaddBulkChildFee', money(childFee));
        set('resaddBulkPoolCount', poolCount);
        set('resaddBulkPoolFee', money(poolFee));
        set('resaddBulkTotalFee', money(adultFee + childFee + poolFee));
        const poolLabel = document.getElementById('resaddBulkPoolLabel');
        if (poolLabel) poolLabel.textContent = `Include Pool Access (all ${qty})`;
    };

    const resAddBindFeeWatchers = () => {
        const singleAge = resAddSingleForm?.querySelector('[name="age"]');
        singleAge?.addEventListener('input', resAddUpdateSingleFees);
        resAddSingleForm?.querySelector('[name="pool_access"]')?.addEventListener('change', resAddUpdateSingleFees);
        resAddBulkForm?.querySelector('[name="age_group"]')?.addEventListener('change', resAddUpdateBulkFees);
        resAddBulkForm?.querySelector('[name="quantity"]')?.addEventListener('input', resAddUpdateBulkFees);
        resAddBulkForm?.querySelector('[name="pool_access"]')?.addEventListener('change', resAddUpdateBulkFees);
    };

    const openResAddCompanionModal = () => {
        if (resAddCompanionFor && currentReservationId) {
            resAddCompanionFor.textContent = `Reservation #${currentReservationId}`;
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
            queueToast(`${payload.added || companions.length} companion${(payload.added || companions.length) > 1 ? 's' : ''} added to Reservation #${currentReservationId}.`);
            // Remember which reservation was just updated so the reloaded page
            // can auto-open its detail modal — the user sees the addition
            // immediately instead of hunting for it.
            try {
                sessionStorage.setItem('hpJustAddedCompanionRes', String(currentReservationId));
            } catch (e) { /* storage unavailable — modal just won't auto-open */ }
            window.location.reload();
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
        const submitButton = e.submitter || resAddSingleForm.querySelector('[type="submit"]');
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
        }], submitButton, 'Add Companion');
    });

    resAddBulkForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(resAddBulkForm);
        const quantity = Math.min(Math.max(parseInt(formData.get('quantity'), 10) || 1, 1), 500);
        const poolOn = formData.get('pool_access') === 'on';
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

    // Remove companion buttons (delegated listener — bind once so SPA re-inits don't stack)
    if (!window.__staffCheckInsDocClickBound) {
        window.__staffCheckInsDocClickBound = true;
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('guest-companion-pill__delete')) {
                e.target.closest('.guest-companion-pill').remove();
            }
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
    if (justAddedRes && reservationData[justAddedRes]) {
        setTimeout(() => openReservationModal(justAddedRes), 450);
    }

    // Success toasts — show anything queued for after a reload and convert
    // server-rendered flash banners (session('success')) into toasts.
    convertFlashToToast();
    showPendingToast();
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['staff_check_ins']());