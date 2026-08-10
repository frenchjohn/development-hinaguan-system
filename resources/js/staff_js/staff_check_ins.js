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
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
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
        const validAmenities = (reservation.reservation_amenities || []).filter(a => a.price > 0);
        const uniquePricingTypes = [...new Set(validAmenities.map(a => String(a.pricing_type || 'N/A').replace(/\s*Aircon/gi, '').trim()))];
        const differentTime = validAmenities.length > 1 && uniquePricingTypes.length > 1;

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
                    <span class="ci-label">RESERVATION DATE</span>
                    <div class="ci-value" style="display:flex;align-items:center;gap:4px;">
                        ${reservation.reservation_date || 'N/A'}
                        ${differentTime ? '<span class="resv-date-badge" style="margin-left:8px;">Mixed Time</span>' : ''}
                    </div>
                </div>
                <div class="ci-col ci-border-left">
                    <span class="ci-label">GUESTS</span>
                    <div class="ci-value">${reservation.number_of_guests || (reservation.reservation_guests ? reservation.reservation_guests.length : 0)}</div>
                </div>
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

            // Display individual companions exactly like bulk companions if requested, but user wanted BULK separated. I will group bulk companions into a single card!
            if (Object.keys(bulkGroups).length > 0 || individualCompanions.length > 0) {
                let bulkTotal = bulkCompanions.length + individualCompanions.length;
                html += `
                    <div class="ci-guest-card" style="align-items: flex-start;">
                        <div class="ci-guest-icon" style="margin-top: 0.2rem;">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                        </div>
                        <div class="ci-guest-info" style="width: 100%;">
                            <div class="ci-guest-role">COMPANIONS (${bulkTotal})</div>
                            <div class="ci-guest-bulk-list">
                `;
                
                let compIndex = 1;
                individualCompanions.forEach(c => {
                    html += `<div class="ci-guest-meta" style="color: #333;">Companion ${compIndex}: ${c.customer.first_name} ${c.customer.last_name} (${c.customer.age || 'N/A'} yrs - ${c.customer.gender || 'N/A'} - ${c.customer.is_foreigner ? 'Foreigner' : 'Filipino'})</div>`;
                    compIndex++;
                });

                Object.values(bulkGroups).forEach(group => {
                    for(let i=0; i<group.count; i++) {
                        html += `<div class="ci-guest-meta" style="color: #333;">Companion ${compIndex}: ${ageGroupLabel(group.age)} - ${group.gender} - ${group.status}</div>`;
                        compIndex++;
                    }
                });

                html += `
                            </div>
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
                            return `
                                <div class="resv-amenity-item ${isCompleted ? 'resv-amenity-item--completed' : ''}">
                                    <div class="resv-amenity-item__info">
                                        <div class="resv-amenity-item__name">${a.amenity ? a.amenity.amenities_name : (a.amenity_name || a.amenity_id || 'Unknown amenity')}</div>
                                        <div class="resv-amenity-item__meta">${a.pricing_type || 'N/A'} · ₱${parseFloat(a.price || a.price_at_booking || 0).toFixed(2)} x ${a.quantity || 1}</div>
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
            expandBtn.style.transform = isExpanded ? 'rotate(180deg)' : '';                // Guest Table Expand — toggle only single companions (bulk
                // groups have their own row and must not be hidden here).
                if (tr.classList.contains('guest-row--primary')) {
                    tr.classList.toggle('is-expanded', isExpanded);
                    const resId = tr.getAttribute('data-reservation-id');
                    const companions = document.querySelectorAll(`.guest-row--companion:not(.guest-row--bulk-group)[data-reservation-id="${resId}"]`);
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

    // Add guest modal
    const addGuestModal = document.getElementById('addGuestModal');
    const addGuestCloseButtons = document.querySelectorAll('[data-close-add-modal="true"]');
    const openAddGuestButtons = document.querySelectorAll('[data-open-add-guest-modal="true"]');
    const primaryGuestSection = document.getElementById('primaryGuestSection');
    const amenitySection = document.getElementById('amenitySection');
    const chooseAmenitiesBtn = document.getElementById('chooseAmenitiesBtn');
    const timePeriod = document.getElementById('time_period');
    const includePool = document.getElementById('include_pool');
    const adultEntranceFee = document.getElementById('adultEntranceFee');
    const childEntranceFee = document.getElementById('childEntranceFee');
    const poolFee = document.getElementById('poolFee');
    const totalEntranceFee = document.getElementById('totalEntranceFee');
    
    // Park settings for pricing (will be loaded from server)
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

    const openAddGuestModal = () => {
        addGuestModal.classList.add('is-open');
        addGuestModal.setAttribute('aria-hidden', 'false');
        // Load park settings
        loadParkSettings();
        // Rebuild time period options for the current session
        updateTimePeriodOptions();
    };

    const closeAddGuestModal = () => {
        addGuestModal.classList.remove('is-open');
        addGuestModal.setAttribute('aria-hidden', 'true');
    };

    openAddGuestButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            openAddGuestModal();
        });
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
                console.log('Loaded park settings:', data);
                parkSettings = { ...parkSettings, ...data };
                // Update fee display with loaded settings
                updateTimePeriodOptions();
            } else {
                console.error('Failed to load park settings. Status:', response.status);
            }
        } catch (error) {
            console.error('Failed to load park settings:', error);
        }
    };

    // Current session (daytime vs nighttime) based on park settings
    const getCurrentSession = () => {
        const now = new Date();
        const currentTime = now.getHours() * 60 + now.getMinutes();

        const daytimeStart = parseTime(parkSettings.daytime_start);
        const daytimeEnd = parseTime(parkSettings.daytime_end);

        return currentTime >= daytimeStart && currentTime < daytimeEnd ? 'daytime' : 'nighttime';
    };

    // Effective period for entrance pricing: with amenities the first selected
    // amenity's period wins; otherwise the time period select drives it.
    const getEffectiveTimeType = () => {
        if (selectedAmenities.length > 0) {
            const pricingType = selectedAmenities[0].pricing_type || 'Daytime';
            if (pricingType.includes('NightToDay') || pricingType.includes('DayToNight')) return 'daytonight';
            if (pricingType.includes('Nighttime')) return 'nighttime';
            return 'daytime';
        }
        return timePeriod?.value || 'daytime';
    };

    // Rebuild the time period select with only periods valid for the current
    // session. Daytime session → Daytime/Nighttime/DayToNight (no NightToDay
    // since it's not night yet); Nighttime session → Nighttime/NightToDay only.
    const updateTimePeriodOptions = () => {
        if (!timePeriod) return;

        const session = getCurrentSession();
        const options = session === 'nighttime'
            ? [['nighttime', 'Nighttime'], ['nighttoday', 'Night to Day']]
            : [['daytime', 'Daytime'], ['nighttime', 'Nighttime'], ['daytonight', 'Day to Night']];

        const previous = timePeriod.value;
        timePeriod.innerHTML = options.map(([value, label]) => `<option value="${value}">${label}</option>`).join('');
        timePeriod.value = options.some(([value]) => value === previous)
            ? previous
            : (session === 'nighttime' ? 'nighttime' : 'daytime');

        // The time period only matters when no amenity is availed — with
        // amenities the amenity rows carry the period instead.
        timePeriod.disabled = selectedAmenities.length > 0;

        updateFeeDisplay();
    };

    // Parse time string to minutes
    const parseTime = (timeStr) => {
        const [hours, minutes] = timeStr.split(':').map(Number);
        return hours * 60 + minutes;
    };

    // Update primary guest required status based on amenities
    const updatePrimaryGuestRequirement = () => {
        const primaryGuestInputs = primaryGuestSection?.querySelectorAll('input');

        if (primaryGuestInputs) {
            primaryGuestInputs.forEach(input => {
                // Only first + last name are truly required. Forcing required on
                // middle name/phone/email silently blocks the first submit with
                // a native tooltip and the payment modal never opens.
                if (input.id === 'primary_first_name' || input.id === 'primary_last_name') {
                    input.setAttribute('required', 'required');
                } else {
                    input.removeAttribute('required');
                }
            });
        }
    };

    // Update fee display based on time period
    const updateFeeDisplay = () => {
        const timeType = getEffectiveTimeType();
        
        let adultFee = 0;
        let childFee = 0;
        let poolFeeValue = 0;
        
        if (timeType === 'daytime') {
            adultFee = parseFloat(parkSettings.daytime_adult_entrance_fee) || 0;
            childFee = parseFloat(parkSettings.daytime_child_entrance_fee) || 0;
            poolFeeValue = parseFloat(parkSettings.day_pool_fee) || 0;
        } else if (timeType === 'nighttime') {
            adultFee = parseFloat(parkSettings.nighttime_adult_entrance_fee) || 0;
            childFee = parseFloat(parkSettings.nighttime_child_entrance_fee) || 0;
            poolFeeValue = parseFloat(parkSettings.night_pool_fee) || 0;
        } else if (timeType === 'daytonight' || timeType === 'nighttoday') {
            adultFee = (parseFloat(parkSettings.daytime_adult_entrance_fee) || 0) + (parseFloat(parkSettings.nighttime_adult_entrance_fee) || 0);
            childFee = (parseFloat(parkSettings.daytime_child_entrance_fee) || 0) + (parseFloat(parkSettings.nighttime_child_entrance_fee) || 0);
            poolFeeValue = (parseFloat(parkSettings.day_pool_fee) || 0) + (parseFloat(parkSettings.night_pool_fee) || 0);
        }
        
        console.log('Time Type:', timeType);
        console.log('Adult Fee:', adultFee);
        console.log('Child Fee:', childFee);
        console.log('Pool Fee:', poolFeeValue);
        console.log('Park Settings:', parkSettings);
        
        if (adultEntranceFee) {
            adultEntranceFee.textContent = `₱${adultFee.toFixed(2)}`;
        }
        if (childEntranceFee) {
            childEntranceFee.textContent = `₱${childFee.toFixed(2)}`;
        }
        if (poolFee) {
            poolFee.textContent = `₱${poolFeeValue.toFixed(2)}`;
        }
    };

    // Calculate entrance fee based on main guest, companions, and age types
    const calculateEntranceFee = () => {
        const timeType = getEffectiveTimeType();
        const includePoolChecked = includePool?.checked || false;
        
        let totalFee = 0;
        
        // Add main guest fee (check age if primary_age input has value)
        const primaryAgeInput = document.getElementById('primary_age');
        let primaryAgeVal = primaryAgeInput ? parseInt(primaryAgeInput.value) : null;
        let mainGuestAgeType = (primaryAgeVal !== null && !isNaN(primaryAgeVal) && primaryAgeVal <= 12) ? 'child' : 'adult';
        
        let mainGuestFee = 0;
        if (timeType === 'daytime') {
            mainGuestFee = mainGuestAgeType === 'child' ? (parseFloat(parkSettings.daytime_child_entrance_fee) || 0) : (parseFloat(parkSettings.daytime_adult_entrance_fee) || 0);
        } else if (timeType === 'nighttime') {
            mainGuestFee = mainGuestAgeType === 'child' ? (parseFloat(parkSettings.nighttime_child_entrance_fee) || 0) : (parseFloat(parkSettings.nighttime_adult_entrance_fee) || 0);
        } else if (timeType === 'daytonight' || timeType === 'nighttoday') {
            const daytimeFee = mainGuestAgeType === 'child' ? (parseFloat(parkSettings.daytime_child_entrance_fee) || 0) : (parseFloat(parkSettings.daytime_adult_entrance_fee) || 0);
            const nighttimeFee = mainGuestAgeType === 'child' ? (parseFloat(parkSettings.nighttime_child_entrance_fee) || 0) : (parseFloat(parkSettings.nighttime_adult_entrance_fee) || 0);
            mainGuestFee = daytimeFee + nighttimeFee;
        }
        
        // Add pool fee for main guest if checked
        if (includePoolChecked) {
            if (timeType === 'daytime') {
                mainGuestFee += parseFloat(parkSettings.day_pool_fee) || 0;
            } else if (timeType === 'nighttime') {
                mainGuestFee += parseFloat(parkSettings.night_pool_fee) || 0;
            } else if (timeType === 'daytonight' || timeType === 'nighttoday') {
                mainGuestFee += (parseFloat(parkSettings.day_pool_fee) || 0) + (parseFloat(parkSettings.night_pool_fee) || 0);
            }
        }
        
        totalFee += mainGuestFee;
        
        // Calculate fees for companions
        companions.forEach(companion => {
            let companionFee = 0;
            let ageType = companion.age_type;
            if (companion.age !== null && companion.age !== undefined && companion.age !== '') {
                const compAge = parseInt(companion.age);
                if (!isNaN(compAge)) {
                    ageType = compAge <= 12 ? 'child' : 'adult';
                }
            }
            if (!ageType) ageType = 'adult';
            
            if (timeType === 'daytime') {
                companionFee = ageType === 'adult' ? (parseFloat(parkSettings.daytime_adult_entrance_fee) || 0) : (parseFloat(parkSettings.daytime_child_entrance_fee) || 0);
            } else if (timeType === 'nighttime') {
                companionFee = ageType === 'adult' ? (parseFloat(parkSettings.nighttime_adult_entrance_fee) || 0) : (parseFloat(parkSettings.nighttime_child_entrance_fee) || 0);
            } else if (timeType === 'daytonight' || timeType === 'nighttoday') {
                const daytimeFee = ageType === 'adult' ? (parseFloat(parkSettings.daytime_adult_entrance_fee) || 0) : (parseFloat(parkSettings.daytime_child_entrance_fee) || 0);
                const nighttimeFee = ageType === 'adult' ? (parseFloat(parkSettings.nighttime_adult_entrance_fee) || 0) : (parseFloat(parkSettings.nighttime_child_entrance_fee) || 0);
                companionFee = daytimeFee + nighttimeFee;
            }
            
            // Add pool fee for companion if checked
            if (includePoolChecked) {
                if (timeType === 'daytime') {
                    companionFee += parseFloat(parkSettings.day_pool_fee) || 0;
                } else if (timeType === 'nighttime') {
                    companionFee += parseFloat(parkSettings.night_pool_fee) || 0;
                } else if (timeType === 'daytonight' || timeType === 'nighttoday') {
                    companionFee += (parseFloat(parkSettings.day_pool_fee) || 0) + (parseFloat(parkSettings.night_pool_fee) || 0);
                }
            }
            
            totalFee += companionFee;
        });
        
        // Calculate fees for bulk companions
        bulkCompanionGroups.forEach(group => {
            let groupFee = 0;
            const ageType = group.age_type || 'adult';
            
            if (timeType === 'daytime') {
                groupFee = ageType === 'adult' ? (parseFloat(parkSettings.daytime_adult_entrance_fee) || 0) : (parseFloat(parkSettings.daytime_child_entrance_fee) || 0);
            } else if (timeType === 'nighttime') {
                groupFee = ageType === 'adult' ? (parseFloat(parkSettings.nighttime_adult_entrance_fee) || 0) : (parseFloat(parkSettings.nighttime_child_entrance_fee) || 0);
            } else if (timeType === 'daytonight' || timeType === 'nighttoday') {
                const daytimeFee = ageType === 'adult' ? (parseFloat(parkSettings.daytime_adult_entrance_fee) || 0) : (parseFloat(parkSettings.daytime_child_entrance_fee) || 0);
                const nighttimeFee = ageType === 'adult' ? (parseFloat(parkSettings.nighttime_adult_entrance_fee) || 0) : (parseFloat(parkSettings.nighttime_child_entrance_fee) || 0);
                groupFee = daytimeFee + nighttimeFee;
            }
            
            // Add pool fee for bulk companions if checked
            if (includePoolChecked) {
                if (timeType === 'daytime') {
                    groupFee += parseFloat(parkSettings.day_pool_fee) || 0;
                } else if (timeType === 'nighttime') {
                    groupFee += parseFloat(parkSettings.night_pool_fee) || 0;
                } else if (timeType === 'daytonight' || timeType === 'nighttoday') {
                    groupFee += (parseFloat(parkSettings.day_pool_fee) || 0) + (parseFloat(parkSettings.night_pool_fee) || 0);
                }
            }
            
            totalFee += groupFee * group.quantity;
        });
        
        // Update total entrance fee display
        if (totalEntranceFee) {
            totalEntranceFee.textContent = `₱${totalFee.toFixed(2)}`;
        }
        
        return totalFee;
    };

    // Calculate Combined Grand Total (Entrance Fees + Amenities)
    const updateGrandTotal = () => {
        const entranceFeeTotal = calculateEntranceFee();
        const amenitiesTotal = selectedAmenities.reduce((sum, a) => sum + (parseFloat(a.price_at_booking) || 0), 0);
        const grandTotal = entranceFeeTotal + amenitiesTotal;
        
        if (reservationTotal) {
            reservationTotal.textContent = `₱${grandTotal.toFixed(2)}`;
        }
        if (totalAmountInput) {
            totalAmountInput.value = grandTotal;
        }
        return { entranceFeeTotal, amenitiesTotal, grandTotal };
    };

    // Listen for fee calculation changes
    timePeriod?.addEventListener('change', () => {
        updateFeeDisplay();
        updateGrandTotal();
    });
    includePool?.addEventListener('change', () => {
        updateGrandTotal();
    });
    
    // Listen for primary age input changes to update entrance fee dynamically
    const primaryAgeInput = document.getElementById('primary_age');
    primaryAgeInput?.addEventListener('input', () => {
        updateGrandTotal();
    });
    primaryAgeInput?.addEventListener('change', () => {
        updateGrandTotal();
    });

    // Payment Confirmation Modal elements
    const paymentConfirmModal = document.getElementById('paymentConfirmModal');
    const payConfirmGuestName = document.getElementById('payConfirmGuestName');
    const payConfirmEntranceTotal = document.getElementById('payConfirmEntranceTotal');
    const payConfirmAmenitiesTotal = document.getElementById('payConfirmAmenitiesTotal');
    const payConfirmGrandTotal = document.getElementById('payConfirmGrandTotal');
    const cancelPaymentBtn = document.getElementById('cancelPaymentBtn');
    const confirmPaymentBtn = document.getElementById('confirmPaymentBtn');
    const closePaymentButtons = document.querySelectorAll('[data-close-payment-modal="true"]');

    let isPaymentConfirmed = false;

    // Add Guest Form Submit Handler with Payment Confirmation
    const addGuestForm = document.getElementById('addGuestForm');
    addGuestForm?.addEventListener('submit', (e) => {
        if (!isPaymentConfirmed) {
            e.preventDefault();

            try {
                const primaryFirstName = document.getElementById('primary_first_name')?.value?.trim();
                const primaryLastName = document.getElementById('primary_last_name')?.value?.trim();

                if (!primaryFirstName || !primaryLastName) {
                    alert('Please fill in the Primary Guest First Name and Last Name.');
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

                // Update Grand Total
                const { entranceFeeTotal, amenitiesTotal, grandTotal } = updateGrandTotal();

                if (!Number.isFinite(grandTotal)) {
                    alert('Unable to calculate the total amount. Please check the guest details and try again.');
                    return;
                }

                // Populate Payment Confirmation Modal
                if (payConfirmGuestName) payConfirmGuestName.textContent = `${primaryFirstName} ${primaryLastName}`;
                if (payConfirmEntranceTotal) payConfirmEntranceTotal.textContent = `₱${entranceFeeTotal.toFixed(2)}`;
                if (payConfirmAmenitiesTotal) payConfirmAmenitiesTotal.textContent = `₱${amenitiesTotal.toFixed(2)}`;
                if (payConfirmGrandTotal) payConfirmGrandTotal.textContent = `₱${grandTotal.toFixed(2)}`;

                // Open Payment Modal
                if (paymentConfirmModal) {
                    paymentConfirmModal.classList.add('is-open');
                    paymentConfirmModal.setAttribute('aria-hidden', 'false');
                }
            } catch (error) {
                console.error('Add Guest submit error:', error);
                alert('Something went wrong while preparing the payment confirmation: ' + (error && error.message ? error.message : error));
            }
        }
    });

    const closePaymentModal = () => {
        if (paymentConfirmModal) {
            paymentConfirmModal.classList.remove('is-open');
            paymentConfirmModal.setAttribute('aria-hidden', 'true');
        }
    };

    cancelPaymentBtn?.addEventListener('click', closePaymentModal);
    closePaymentButtons.forEach(btn => btn.addEventListener('click', closePaymentModal));

    confirmPaymentBtn?.addEventListener('click', () => {
        isPaymentConfirmed = true;
        closePaymentModal();
        addGuestForm?.submit();
    });

    // Amenity modal
    const amenityModal = document.getElementById('amenityModal');
    const amenityCloseButtons = document.querySelectorAll('[data-close-amenity-modal="true"]');
    const selectedAmenitiesContainer = document.getElementById('selectedAmenitiesContainer');
    const reservationTotal = document.getElementById('reservationTotal');
    const totalAmountInput = document.getElementById('totalAmountInput');
    const amenitiesContainer = document.getElementById('amenitiesContainer');
    
    let selectedAmenities = [];

    const openAmenityModal = () => {
        amenityModal.classList.add('is-open');
        amenityModal.setAttribute('aria-hidden', 'false');
    };

    const closeAmenityModal = () => {
        amenityModal.classList.remove('is-open');
        amenityModal.setAttribute('aria-hidden', 'true');
    };

    chooseAmenitiesBtn?.addEventListener('click', openAmenityModal);
    amenityCloseButtons.forEach(button => {
        button.addEventListener('click', closeAmenityModal);
    });

    // Amenity checkbox changes
    amenitiesContainer?.addEventListener('change', (e) => {
        if (e.target.classList.contains('amenity-checkbox')) {
            const amenityId = e.target.dataset.amenityId;
            const amenityName = e.target.dataset.amenityName;
            
            // Find selected option pricing
            const parentLabel = e.target.closest('label');
            const selectEl = parentLabel?.querySelector('.guest-amenity-option__select');
            let pricingType = 'Daytime';
            let price = 0;

            if (selectEl) {
                selectEl.disabled = !e.target.checked;
                const opt = selectEl.options[selectEl.selectedIndex];
                if (opt) {
                    pricingType = opt.value;
                    price = parseFloat(opt.dataset.price) || 0;
                }
            }

            if (e.target.checked) {
                // Remove existing if any
                selectedAmenities = selectedAmenities.filter(a => a.amenity_id != amenityId);
                selectedAmenities.push({
                    amenity_id: amenityId,
                    amenity_name: amenityName,
                    pricing_type: pricingType,
                    price_at_booking: price,
                });
            } else {
                selectedAmenities = selectedAmenities.filter(a => a.amenity_id != amenityId);
            }

            renderSelectedAmenities();
            updatePrimaryGuestRequirement();
            updateGrandTotal();
        } else if (e.target.classList.contains('guest-amenity-option__select')) {
            const parentLabel = e.target.closest('label');
            const checkbox = parentLabel?.querySelector('.amenity-checkbox');
            if (checkbox && checkbox.checked) {
                const amenityId = checkbox.dataset.amenityId;
                const amenityName = checkbox.dataset.amenityName;
                const opt = e.target.options[e.target.selectedIndex];
                const pricingType = opt ? opt.value : 'Daytime';
                const price = opt ? (parseFloat(opt.dataset.price) || 0) : 0;

                selectedAmenities = selectedAmenities.filter(a => a.amenity_id != amenityId);
                selectedAmenities.push({
                    amenity_id: amenityId,
                    amenity_name: amenityName,
                    pricing_type: pricingType,
                    price_at_booking: price,
                });

                renderSelectedAmenities();
                updatePrimaryGuestRequirement();
                updateGrandTotal();
            }
        }
    });

    const renderSelectedAmenities = () => {
        if (!selectedAmenitiesContainer) return;

        // Re-evaluate time period availability now that the amenity set changed
        updateTimePeriodOptions();
        selectedAmenitiesContainer.innerHTML = '';

        selectedAmenities.forEach((amenity, index) => {
            const pill = document.createElement('div');
            pill.className = 'guest-amenity-pill flex items-center justify-between rounded-lg border border-glass-border bg-glass px-3 py-2 text-sm text-hp-text';
            pill.innerHTML = `
                <span><strong>${amenity.amenity_name}</strong> — ${amenity.pricing_type} · ₱${amenity.price_at_booking.toFixed(2)}</span>
                <button type="button" class="guest-amenity-pill__remove text-hp-text-muted hover:text-red-500 font-bold ml-2" data-amenity-id="${amenity.amenity_id}">&times;</button>
            `;
            selectedAmenitiesContainer.appendChild(pill);

            // Hidden inputs so the form submits the selected amenities
            const hidden = document.createElement('div');
            hidden.innerHTML = `
                <input type="hidden" name="selected_amenities[${index}][amenity_id]" value="${amenity.amenity_id}">
                <input type="hidden" name="selected_amenities[${index}][pricing_type]" value="${amenity.pricing_type}">
                <input type="hidden" name="selected_amenities[${index}][price_at_booking]" value="${amenity.price_at_booking}">
            `;
            selectedAmenitiesContainer.appendChild(hidden);
        });

        updateGrandTotal();
    };

    // Remove amenity from selected list
    selectedAmenitiesContainer?.addEventListener('click', (e) => {
        if (e.target.classList.contains('guest-amenity-pill__remove')) {
            const amenityId = e.target.dataset.amenityId;
            selectedAmenities = selectedAmenities.filter(a => a.amenity_id != amenityId);
            
            // Uncheck the checkbox
            const checkbox = amenitiesContainer?.querySelector(`input[data-amenity-id="${amenityId}"]`);
            if (checkbox) {
                checkbox.checked = false;
            }

            renderSelectedAmenities();
            updatePrimaryGuestRequirement();
            updateGrandTotal();
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
    const companionAgeTypeSelect = document.getElementById('companion_age_type');
    
    // Companion age auto-type listener
    companionAgeInput?.addEventListener('input', () => {
        const age = parseInt(companionAgeInput.value);
        if (!isNaN(age) && companionAgeTypeSelect) {
            companionAgeTypeSelect.value = age <= 12 ? 'child' : 'adult';
        }
    });

    let companions = [];
    let bulkCompanionGroups = [];

    const openCompanionModal = () => {
        companionModal.classList.add('is-open');
        companionModal.setAttribute('aria-hidden', 'false');
    };

    const closeCompanionModal = () => {
        companionModal.classList.remove('is-open');
        companionModal.setAttribute('aria-hidden', 'true');
        // Reset forms
        companionForm?.reset();
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
            const item = document.createElement('div');
            item.className = 'guest-companion-pill';
            item.innerHTML = `
                <span class="guest-companion-pill__name">${companion.first_name} ${companion.last_name} - ${nationality} - ${companion.age || 'N/A'} - ${companion.gender}</span>
                <button type="button" class="guest-companion-pill__delete" data-companion-index="${index}">Remove</button>
            `;
            companionList.appendChild(item);

            // Add hidden fields
            companionHiddenFields.insertAdjacentHTML('beforeend', `
                <input type="hidden" name="companions[${index}][first_name]" value="${companion.first_name}">
                <input type="hidden" name="companions[${index}][middle_name]" value="${companion.middle_name || ''}">
                <input type="hidden" name="companions[${index}][last_name]" value="${companion.last_name}">
                <input type="hidden" name="companions[${index}][age]" value="${companion.age || ''}">
                <input type="hidden" name="companions[${index}][gender]" value="${companion.gender || ''}">
                <input type="hidden" name="companions[${index}][is_foreigner]" value="${companion.is_foreigner ? '1' : '0'}">
                <input type="hidden" name="companions[${index}][phone]" value="${companion.phone || ''}">
                <input type="hidden" name="companions[${index}][email]" value="${companion.email || ''}">
            `);
        });

        // Render bulk companion groups
        bulkCompanionGroups.forEach((group, groupIndex) => {
            const nationality = group.is_foreigner ? 'Foreigner' : 'Filipino';
            const item = document.createElement('div');
            item.className = 'guest-companion-pill guest-companion-pill--bulk';
            item.innerHTML = `
                <span class="guest-companion-pill__name">${group.gender} - ${nationality} - Age Group: ${group.age_group} - Qty: ${group.quantity}</span>
                <button type="button" class="guest-companion-pill__delete" data-bulk-index="${groupIndex}">Remove</button>
            `;
            companionList.appendChild(item);

            // Add hidden fields for each companion in the group
            for (let i = 0; i < group.quantity; i++) {
                const companionIndex = companions.length + groupIndex * 1000 + i;
                companionHiddenFields.insertAdjacentHTML('beforeend', `
                    <input type="hidden" name="companions[${companionIndex}][first_name]" value="">
                    <input type="hidden" name="companions[${companionIndex}][middle_name]" value="">
                    <input type="hidden" name="companions[${companionIndex}][last_name]" value="">
                    <input type="hidden" name="companions[${companionIndex}][age_group]" value="${group.age_group}">
                    <input type="hidden" name="companions[${companionIndex}][gender]" value="${group.gender}">
                    <input type="hidden" name="companions[${companionIndex}][is_foreigner]" value="${group.is_foreigner ? '1' : '0'}">
                    <input type="hidden" name="companions[${companionIndex}][phone]" value="">
                    <input type="hidden" name="companions[${companionIndex}][email]" value="">
                `);
            }
        });

        if (companions.length === 0 && bulkCompanionGroups.length === 0) {
            companionList.innerHTML = '<p class="guest-empty">No companions added yet.</p>';
        }
    };

    // Single companion form submission
    companionForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const formData = new FormData(companionForm);
        const companionData = {
            first_name: formData.get('first_name'),
            middle_name: formData.get('middle_name'),
            last_name: formData.get('last_name'),
            age: formData.get('age'),
            age_type: formData.get('age_type'),
            gender: formData.get('gender'),
            is_foreigner: formData.get('is_foreigner') === '1',
            phone: formData.get('phone'),
            email: formData.get('email'),
        };
        
        companions.push(companionData);
        renderCompanions();
        updateGrandTotal();
        companionForm.reset();
        closeCompanionModal();
    });

    // Bulk Stepper logic
    const bulkBtnMinus = document.getElementById('bulkBtnMinus');
    const bulkBtnPlus = document.getElementById('bulkBtnPlus');
    const bulkQuantity = document.getElementById('bulkCompanionQuantity');

    if (bulkBtnMinus && bulkBtnPlus && bulkQuantity) {
        bulkBtnMinus.addEventListener('click', () => {
            let val = parseInt(bulkQuantity.value, 10) || 1;
            if (val > parseInt(bulkQuantity.min || 1, 10)) {
                bulkQuantity.value = val - 1;
            }
        });
        bulkBtnPlus.addEventListener('click', () => {
            let val = parseInt(bulkQuantity.value, 10) || 1;
            if (val < parseInt(bulkQuantity.max || 50, 10)) {
                bulkQuantity.value = val + 1;
            }
        });
    }

    // Bulk companion form submission
    bulkCompanionForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(bulkCompanionForm);
        const bulkData = Object.fromEntries(formData.entries());
        
        // Map age_group to age_type for pricing
        const ageGroup = bulkData.age_group || '18-59';
        let ageType = 'adult';
        if (ageGroup === '0-12') {
            ageType = 'child';
        } else {
            ageType = 'adult';
        }
        
        bulkCompanionGroups.push({
            gender: bulkData.gender,
            age_group: ageGroup,
            age_type: ageType,
            is_foreigner: bulkData.is_foreigner === '1',
            quantity: parseInt(bulkData.quantity) || 1,
        });
        
        renderCompanions();
        updateGrandTotal();
        bulkCompanionForm.reset();
        closeCompanionModal();
    });

    // Delete companion handlers
    companionList?.addEventListener('click', (e) => {
        if (e.target.classList.contains('guest-companion-pill__delete')) {
            const index = e.target.dataset.companionIndex;
            const bulkIndex = e.target.dataset.bulkIndex;
            
            if (index !== undefined) {
                companions.splice(index, 1);
            } else if (bulkIndex !== undefined) {
                bulkCompanionGroups.splice(bulkIndex, 1);
            }
            
            renderCompanions();
            updateGrandTotal();
        }
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

    const openResAddCompanionModal = () => {
        if (resAddCompanionFor && currentReservationId) {
            resAddCompanionFor.textContent = `Reservation #${currentReservationId}`;
        }
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
        }], submitButton, 'Add Companion');
    });

    resAddBulkForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(resAddBulkForm);
        const quantity = Math.min(Math.max(parseInt(formData.get('quantity'), 10) || 1, 1), 500);
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