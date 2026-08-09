window.AppPage = window.AppPage || {};
window.AppPage['staff_check_ins'] = function () {


    const tabGuestBtn = document.getElementById('tabGuestBtn');
    const tabReservationBtn = document.getElementById('tabReservationBtn');
    const guestTableSection = document.getElementById('guestTableSection');
    const reservationTableSection = document.getElementById('reservationTableSection');
    const reservationTableBody = document.getElementById('reservationTableBody');
    const reservationModal = document.getElementById('reservationModal');
    const reservationModalBody = document.getElementById('reservationModalBody');
    const reservationCheckOutBtn = document.getElementById('reservationCheckOutBtn');
    const reservationCloseButtons = document.querySelectorAll('[data-close-reservation-modal="true"]');
    const checkOutConfirmModal = document.getElementById('checkOutConfirmModal');
    const confirmCheckOutBtn = document.getElementById('confirmCheckOutBtn');
    const checkOutConfirmCloseButtons = document.querySelectorAll('[data-close-check-out-confirm="true"]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const reservationData = window.staffReservationData || {};
    const guestData = window.staffGuestData || {};

    let currentReservationId = null;
    let companionCount = 0;

    // Initialize: show dashboard table by default
    const dashboardSection = document.getElementById('dashboardSection');
    
    if (dashboardSection && guestTableSection && reservationTableSection) {
        dashboardSection.style.display = 'none';
        guestTableSection.style.display = '';
        reservationTableSection.style.display = 'none';
        document.querySelectorAll('.checkins-tab').forEach(btn => btn.classList.remove('is-active'));
        document.querySelectorAll('.checkins-tab[data-tab-target="guest"]').forEach(btn => btn.classList.add('is-active'));
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
        // Table Pills: Separate Time Left and Status columns
        document.querySelectorAll('.table-time-left').forEach((el) => {
            const state = getCheckoutState(el.dataset.checkoutAt);
            const tr = el.closest('tr');
            
            if (!state.visible) {
                el.innerHTML = '—';
                return;
            }
            
            const clockIcon = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 0.9rem; height: 0.9rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;
            
            if (state.tone === 'due') {
                el.innerHTML = `<span style="display:inline-flex; align-items:center; gap:0.2rem; padding:0.2rem 0.5rem; border-radius:999px; font-size:0.75rem; font-weight:600; background: rgba(239, 68, 68, 0.1); color: #ef4444;">${clockIcon} 0m left</span>`;
            } else {
                let colorClass = '#16a34a'; // green
                let bgClass = 'rgba(22, 163, 74, 0.1)';
                if (state.tone === 'warn' || state.tone === 'near') {
                    colorClass = '#ea580c'; // orange
                    bgClass = 'rgba(234, 88, 12, 0.1)';
                }
                el.innerHTML = `<span style="display:inline-flex; align-items:center; gap:0.2rem; padding:0.2rem 0.5rem; border-radius:999px; font-size:0.75rem; font-weight:600; background: ${bgClass}; color: ${colorClass};">${clockIcon} ${formatTimeLeft(state.left)} left</span>`;
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
        
        document.querySelectorAll('.table-status-pill').forEach((el) => {
            const state = getCheckoutState(el.dataset.checkoutAt);
            const statusStr = el.dataset.status || '';
            
            if (statusStr === 'checked_out') {
                el.innerHTML = `<span style="display:inline-flex; align-items:center; padding:0.25rem 0.6rem; border-radius:999px; font-size:0.7rem; font-weight:700; background: rgba(107, 114, 128, 0.1); color: #6b7280;">Checked Out</span>`;
            } else if (state.tone === 'due') {
                el.innerHTML = `<span style="display:inline-flex; align-items:center; padding:0.25rem 0.6rem; border-radius:999px; font-size:0.7rem; font-weight:700; background: rgba(239, 68, 68, 0.1); color: #ef4444;">Checkout Time!</span>`;
            } else {
                el.innerHTML = `<span style="display:inline-flex; align-items:center; padding:0.25rem 0.6rem; border-radius:999px; font-size:0.7rem; font-weight:700; background: rgba(22, 163, 74, 0.1); color: #16a34a;">Checked In</span>`;
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
        const primaryGuest = reservation.reservation_guests.find(g => g.is_primary_guest);
        const companions = reservation.reservation_guests.filter(g => !g.is_primary_guest);

        // Does this reservation cover multiple amenity time periods?
        // (Daytime vs Daytime Aircon = same time; strip Aircon before comparing.)
        const validAmenities = (reservation.reservation_amenities || []).filter(a => a.price > 0);
        const uniquePricingTypes = [...new Set(validAmenities.map(a => String(a.pricing_type || 'N/A').replace(/\s*Aircon/gi, '').trim()))];
        const differentTime = validAmenities.length > 1 && uniquePricingTypes.length > 1;

        let html = `
            ${reservation.checkout_at ? `
                <div class="resv-checkout-countdown" data-checkout-at="${reservation.checkout_at}" data-checkout-state=""></div>
            ` : ''}
            <div style="margin-bottom: 1.5rem;">
                <h4 style="margin-bottom: 0.5rem; font-weight: 600;">Main Guest</h4>
                <div style="padding: 1rem; background-color: var(--hp-cream, #f5f5f5); border-radius: 0.5rem;">
                    ${primaryGuest && primaryGuest.customer ? `
                        <div><strong>${primaryGuest.customer.first_name} ${primaryGuest.customer.middle_name || ''} ${primaryGuest.customer.last_name}</strong></div>
                        <div style="font-size: 0.875rem; color: #666;">Age: ${primaryGuest.customer.age || 'N/A'} | Gender: ${primaryGuest.customer.gender || 'N/A'} | Status: ${primaryGuest.customer.is_foreigner ? 'Foreigner' : 'Filipino'}</div>
                        <div style="font-size: 0.875rem; color: #666;">Phone: ${primaryGuest.customer.phone || 'N/A'} | Email: ${primaryGuest.customer.email || 'N/A'}</div>
                    ` : '<div>No main guest assigned</div>'}
                </div>
            </div>
        `;

        if (companions.length > 0) {
            // DEBUG: Log all companions
            console.log('All companions:', companions);

            // Separate individual companions (with names) from bulk companions (generic names)
            const individualCompanions = companions.filter(c =>
                c.customer &&
                c.customer.first_name &&
                !c.customer.first_name.toLowerCase().includes('companion') &&
                !c.customer.first_name.toLowerCase().includes('reservation')
            );

            const bulkCompanions = companions.filter(c =>
                !c.customer ||
                !c.customer.first_name ||
                c.customer.first_name.toLowerCase().includes('companion') ||
                c.customer.first_name.toLowerCase().includes('reservation')
            );

            console.log('Individual companions:', individualCompanions);
            console.log('Bulk companions:', bulkCompanions);

            // Group bulk companions by gender, foreigner status, and age
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

            console.log('Bulk groups:', bulkGroups);

            html += `<div style="margin-bottom: 1.5rem;">`;

            // Display individual companions
            if (individualCompanions.length > 0) {
                html += `
                    <h4 style="margin-bottom: 0.5rem; font-weight: 600;">Companions (${individualCompanions.length})</h4>
                    ${individualCompanions.map(c => `
                        <div style="padding: 0.75rem; background-color: var(--hp-cream, #f5f5f5); border-radius: 0.5rem; margin-bottom: 0.5rem;">
                            <div><strong>${c.customer.first_name} ${c.customer.middle_name || ''} ${c.customer.last_name}</strong></div>
                            <div style="font-size: 0.875rem; color: #666;">Age: ${c.customer.age || 'N/A'} | Gender: ${c.customer.gender || 'N/A'} | Status: ${c.customer.is_foreigner ? 'Foreigner' : 'Filipino'}</div>
                            <div style="font-size: 0.875rem; color: #666;">Phone: ${c.customer.phone || 'N/A'} | Email: ${c.customer.email || 'N/A'}</div>
                        </div>
                    `).join('')}
                `;
            }

            // Display bulk companions as groups
            if (Object.keys(bulkGroups).length > 0) {
                html += `
                    <h4 style="margin-bottom: 0.5rem; font-weight: 600;">Bulk Companions</h4>
                    ${Object.values(bulkGroups).map(group => `
                        <div style="padding: 0.75rem; background-color: var(--hp-cream, #f5f5f5); border-radius: 0.5rem; margin-bottom: 0.5rem;">
                            <div><strong>${group.gender}/${group.status}/${group.age} = ${group.count}</strong></div>
                        </div>
                    `).join('')}
                `;
            }

            html += `</div>`;
        }

        if (reservation.reservation_amenities && reservation.reservation_amenities.length > 0) {
            if (validAmenities.length > 0) {
                const statusKey = String(reservation.status || '').toLowerCase().replace(/\s+/g, '_');
                const isCheckedIn = statusKey === 'checked_in';
                // Different time periods (daytime vs nighttime, etc.) => per-amenity checkout
                const showPerAmenityCheckout = isCheckedIn && differentTime;

                html += `
                    <div style="margin-bottom: 1.5rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                            <h4 style="margin: 0; font-weight: 600;">Amenities</h4>
                            ${showPerAmenityCheckout ? '<span class="resv-diff-time-label">Different amenity time</span>' : ''}
                        </div>
                        <div class="resv-amenity-list">
                            ${validAmenities.map(a => {
                                const amenityStatus = a.status || 'Active';
                                const isCompleted = amenityStatus === 'Completed';
                                return `
                                    <div class="resv-amenity-item ${isCompleted ? 'resv-amenity-item--completed' : ''}">
                                        <div class="resv-amenity-item__info">
                                            <div class="resv-amenity-item__name">${a.amenity_name || a.amenity_id || 'Unknown'}</div>
                                            <div class="resv-amenity-item__meta">${a.pricing_type || 'N/A'} · ₱${parseFloat(a.price).toFixed(2)} x ${a.quantity || 1}</div>
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

        const totalAmount = reservation.reservation_amenities.reduce((sum, a) => sum + (parseFloat(a.price) * a.quantity), 0);

        const mainGuestContact = primaryGuest?.customer ? {
            phone: primaryGuest.customer.phone || reservation.phone || 'N/A',
            email: primaryGuest.customer.email || reservation.email || 'N/A'
        } : {
            phone: reservation.phone || 'N/A',
            email: reservation.email || 'N/A'
        };

        html += `
            <div style="border-top: 1px solid #ddd; padding-top: 1rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>Reservation ID:</span>
                    <strong>#${reservation.id}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span>Reservation Date:</span>
                    <strong style="position: relative;">${reservation.reservation_date || 'N/A'}
                        ${differentTime ? '<span class="resv-date-badge">Mixed Time</span>' : ''}
                    </strong>
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
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>Number of Guests:</span>
                    <strong>${reservation.number_of_guests || reservation.reservation_guests.length}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>Contact (Phone):</span>
                    <strong>${mainGuestContact.phone}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>Contact (Email):</span>
                    <strong>${mainGuestContact.email}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>Total Amount:</span>
                    <strong>₱${parseFloat(reservation.total_amount || totalAmount).toFixed(2)}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>Amount Paid:</span>
                    <strong>₱${parseFloat(reservation.amount_paid || 0).toFixed(2)}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>Remaining Balance:</span>
                    <strong>₱${parseFloat(reservation.remaining_balance || 0).toFixed(2)}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Payment Status:</span>
                    <strong>${reservation.payment_status || 'Pending'}</strong>
                </div>
            </div>
        `;

        // Update modal status badge
        const statusBadge = document.getElementById('reservationModalStatus');
        if (statusBadge) {
            statusBadge.textContent = reservation.status || 'Active';
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

    // Reservation checkout
    reservationCheckOutBtn?.addEventListener('click', async () => {
        if (!currentReservationId) return;

        if (!confirm('Check out all guests in this reservation?')) return;

        const submitButton = reservationCheckOutBtn;
        submitButton.disabled = true;
        submitButton.textContent = 'Checking out...';

        try {
            const response = await fetch(`/staff/reservations/${currentReservationId}/check-out`, {
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
                throw new Error(payload.message || 'Unable to check out this reservation.');
            }

            window.location.reload();
        } catch (error) {
            window.alert(error.message || 'Unable to check out this reservation.');
            submitButton.disabled = false;
            submitButton.textContent = 'Check Out';
        }
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
        openCheckOutConfirmModal();
    });

    // Confirm checkout - actually perform the action
    confirmCheckOutBtn?.addEventListener('click', async () => {
        if (!currentReservationId) return;

        const submitButton = confirmCheckOutBtn;
        submitButton.disabled = true;
        submitButton.textContent = 'Checking out...';

        try {
            const response = await fetch(`/staff/reservations/${currentReservationId}/check-out`, {
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
                alert('Reservation checked out successfully!');
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

    guestRows.forEach(row => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('.btn-expand-row')) return;
            const customerId = row.dataset.customerId;
            openGuestModal(customerId);
        });
    });

    // Expandable Row Logic
    document.addEventListener('click', (e) => {
        const expandBtn = e.target.closest('.btn-expand-row');
        if (!expandBtn) return;
        
        e.stopPropagation();
        const tr = expandBtn.closest('tr');
        if (!tr) return;
        
        const isExpanded = expandBtn.classList.toggle('expanded');
        expandBtn.style.transform = isExpanded ? 'rotate(180deg)' : '';

        // Guest Table Expand
        if (tr.classList.contains('guest-row--primary')) {
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
                     reservation.reservation_guests.forEach(g => {
                         if (!g.customer) return;
                         const pill = g.is_primary_guest 
                            ? `<span style="font-size: 0.65rem; background: var(--hp-gold); color: #fff; padding: 2px 6px; border-radius: 12px; margin-left: 8px;">MAIN</span>`
                            : `<span style="font-size: 0.65rem; background: var(--hp-green); color: #fff; padding: 2px 6px; border-radius: 12px; margin-left: 8px;">COMPANION</span>`;
                         guestsHtml += `<div style="display: flex; align-items: center; font-size: 0.85rem; font-weight: 500;">
                            ${g.customer.first_name} ${g.customer.middle_name || ''} ${g.customer.last_name} ${pill}
                            <span style="color: #888; font-size: 0.75rem; margin-left: auto;">${g.customer.gender || 'Unknown'} • ${g.customer.age || 'N/A'} yrs</span>
                         </div>`;
                     });
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
        // Set default time period if no amenities selected
        if (selectedAmenities.length === 0) {
            setDefaultTimePeriod();
        }
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
                updateFeeDisplay();
            } else {
                console.error('Failed to load park settings. Status:', response.status);
            }
        } catch (error) {
            console.error('Failed to load park settings:', error);
        }
    };

    // Set default time period based on current time
    const setDefaultTimePeriod = () => {
        const now = new Date();
        const currentTime = now.getHours() * 60 + now.getMinutes();
        
        const daytimeStart = parseTime(parkSettings.daytime_start);
        const daytimeEnd = parseTime(parkSettings.daytime_end);
        const nighttimeStart = parseTime(parkSettings.nighttime_start);
        const nighttimeEnd = parseTime(parkSettings.nighttime_end);
        
        let defaultPeriod = 'daytime';
        
        if (currentTime >= daytimeStart && currentTime < daytimeEnd) {
            defaultPeriod = 'daytime';
        } else if (currentTime >= nighttimeStart || currentTime < nighttimeEnd) {
            defaultPeriod = 'nighttime';
        } else {
            defaultPeriod = 'daytonight';
        }
        
        if (timePeriod) {
            timePeriod.value = defaultPeriod;
        }
    };

    // Parse time string to minutes
    const parseTime = (timeStr) => {
        const [hours, minutes] = timeStr.split(':').map(Number);
        return hours * 60 + minutes;
    };

    // Update primary guest required status based on amenities
    const updatePrimaryGuestRequirement = () => {
        // Primary guest is always required now
        const primaryGuestInputs = primaryGuestSection?.querySelectorAll('input');
        
        if (primaryGuestInputs) {
            primaryGuestInputs.forEach(input => {
                input.setAttribute('required', 'required');
            });
        }
    };

    // Update fee display based on time period
    const updateFeeDisplay = () => {
        const timeType = timePeriod?.value || 'daytime';
        
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

    // Calculate entrance fee based on companions
    const calculateEntranceFee = () => {
        const timeType = timePeriod?.value || 'daytime';
        const includePoolChecked = includePool?.checked || false;
        
        // Calculate fee per person based on companions' age types
        let totalFee = 0;
        
        // Add main guest fee (default to adult for main guest)
        let mainGuestFee = 0;
        if (timeType === 'daytime') {
            mainGuestFee = parseFloat(parkSettings.daytime_adult_entrance_fee) || 0;
        } else if (timeType === 'nighttime') {
            mainGuestFee = parseFloat(parkSettings.nighttime_adult_entrance_fee) || 0;
        } else if (timeType === 'daytonight' || timeType === 'nighttoday') {
            mainGuestFee = (parseFloat(parkSettings.daytime_adult_entrance_fee) || 0) + (parseFloat(parkSettings.nighttime_adult_entrance_fee) || 0);
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
            const ageType = companion.age_type || 'adult';
            
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
        
        console.log('Total Fee Calculated:', totalFee);
        
        // Update total display
        if (totalEntranceFee) {
            totalEntranceFee.textContent = `₱${totalFee.toFixed(2)}`;
        }
        
        return totalFee;
    };

    // Listen for fee calculation changes
    timePeriod?.addEventListener('change', () => {
        updateFeeDisplay();
        calculateEntranceFee();
    });
    includePool?.addEventListener('change', calculateEntranceFee);

    // Auto-fill check-in time on form submit
    const addGuestForm = document.getElementById('addGuestForm');
    addGuestForm?.addEventListener('submit', (e) => {
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
            const pricingType = e.target.dataset.pricingType || 'standard';
            const price = parseFloat(e.target.dataset.price) || 0;

            if (e.target.checked) {
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
            calculateEntranceFee();
        }
    });

    const renderSelectedAmenities = () => {
        if (!selectedAmenitiesContainer) return;

        selectedAmenitiesContainer.innerHTML = '';

        selectedAmenities.forEach(amenity => {
            const pill = document.createElement('div');
            pill.className = 'guest-amenity-pill';
            pill.innerHTML = `
                <span>${amenity.amenity_name} — ${amenity.pricing_type} · ₱${amenity.price_at_booking.toFixed(2)}</span>
                <button type="button" class="guest-amenity-pill__remove" data-amenity-id="${amenity.amenity_id}">&times;</button>
            `;
            selectedAmenitiesContainer.appendChild(pill);
        });

        // Update total
        const total = selectedAmenities.reduce((sum, a) => sum + a.price_at_booking, 0);
        if (reservationTotal) {
            reservationTotal.textContent = `₱${total.toFixed(2)}`;
        }
        if (totalAmountInput) {
            totalAmountInput.value = total;
        }
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
            calculateEntranceFee();
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
    const companionIsForeigner = document.getElementById('companionIsForeigner');
    
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

    // Tab switching for companion modal
    const companionTabs = document.querySelectorAll('[data-companion-tab]');
    const companionTabContents = document.querySelectorAll('[data-companion-content]');

    companionTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabType = tab.dataset.companionTab;
            
            // Update tabs
            companionTabs.forEach(t => t.classList.remove('guest-form__tab--active'));
            tab.classList.add('guest-form__tab--active');
            
            // Update content
            companionTabContents.forEach(content => {
                content.classList.remove('guest-form--tab-content--active');
                if (content.dataset.companionContent === tabType) {
                    content.classList.add('guest-form--tab-content--active');
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
        calculateEntranceFee();
        companionForm.reset();
        closeCompanionModal();
    });

    // Bulk companion form submission
    bulkCompanionForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(bulkCompanionForm);
        const bulkData = Object.fromEntries(formData.entries());
        
        // Map age_group to age_type for pricing
        const ageGroup = bulkData.age_group || '18-59';
        let ageType = 'adult';
        if (ageGroup === '0-12' || ageGroup === '13-17') {
            ageType = 'child';
        } else if (ageGroup === '18-59' || ageGroup === '60+') {
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
        calculateEntranceFee();
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
            calculateEntranceFee();
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
            
            row.style.display = show ? '' : 'none';
        });
        
        // Update results count
        const visibleRows = Array.from(guestRows).filter(row => row.style.display !== 'none');
        const resultsCount = document.getElementById('guestResultsCount');
        if (resultsCount) {
            resultsCount.textContent = `Showing ${visibleRows.length} active guests`;
        }
    };

    guestSearchInput?.addEventListener('input', applyGuestFilters);
    guestRoleSelect?.addEventListener('change', applyGuestFilters);
    guestReservationSelect?.addEventListener('change', applyGuestFilters);

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
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['staff_check_ins']());