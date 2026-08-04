document.addEventListener('DOMContentLoaded', () => {
    // Page transition with skeleton loading
    const pageTransitionOverlay = document.getElementById('pageTransitionOverlay');
    
    // Handle outgoing page transition (when clicking navigation links)
    const transitionLinks = document.querySelectorAll('[data-page-transition]');
    transitionLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetUrl = this.href;
            
            // Immediately update active state on sidemenu
            const allLinks = document.querySelectorAll('.dash-sidebar__link');
            allLinks.forEach(l => l.classList.remove('is-active'));
            this.classList.add('is-active');
            
            // Show skeleton overlay immediately
            if (pageTransitionOverlay) {
                pageTransitionOverlay.classList.add('is-active');
                
                // Navigate after a short delay to allow the overlay to appear
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 100);
            } else {
                // Fallback if overlay doesn't exist
                window.location.href = targetUrl;
            }
        });
    });

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

    // Initialize: show reservation table by default
    if (guestTableSection && reservationTableSection) {
        guestTableSection.style.display = 'none';
        reservationTableSection.style.display = '';
        if (tabGuestBtn && tabReservationBtn) {
            tabGuestBtn.style.backgroundColor = 'var(--hp-cream)';
            tabGuestBtn.style.color = 'var(--hp-text)';
            tabGuestBtn.style.boxShadow = 'none';
            tabGuestBtn.style.transform = 'none';
            tabReservationBtn.style.backgroundColor = 'var(--hp-green-dark)';
            tabReservationBtn.style.color = 'white';
            tabReservationBtn.style.boxShadow = '0 4px 12px rgba(13, 44, 29, 0.3)';
            tabReservationBtn.style.transform = 'translateY(-2px)';
        }
    }

    // Tab switching
    const switchToGuest = () => {
        guestTableSection.style.display = '';
        reservationTableSection.style.display = 'none';
        tabGuestBtn.style.backgroundColor = 'var(--hp-green-dark)';
        tabGuestBtn.style.color = 'white';
        tabGuestBtn.style.boxShadow = '0 4px 12px rgba(13, 44, 29, 0.3)';
        tabGuestBtn.style.transform = 'translateY(-2px)';
        tabReservationBtn.style.backgroundColor = 'var(--hp-cream)';
        tabReservationBtn.style.color = 'var(--hp-text)';
        tabReservationBtn.style.boxShadow = 'none';
        tabReservationBtn.style.transform = 'none';
    };

    const switchToReservation = () => {
        guestTableSection.style.display = 'none';
        reservationTableSection.style.display = '';
        tabGuestBtn.style.backgroundColor = 'var(--hp-cream)';
        tabGuestBtn.style.color = 'var(--hp-text)';
        tabGuestBtn.style.boxShadow = 'none';
        tabGuestBtn.style.transform = 'none';
        tabReservationBtn.style.backgroundColor = 'var(--hp-green-dark)';
        tabReservationBtn.style.color = 'white';
        tabReservationBtn.style.boxShadow = '0 4px 12px rgba(13, 44, 29, 0.3)';
        tabReservationBtn.style.transform = 'translateY(-2px)';
    };

    tabGuestBtn?.addEventListener('click', switchToGuest);
    tabReservationBtn?.addEventListener('click', switchToReservation);

    // Reservation modal functions
    const openReservationModal = (reservationId) => {
        currentReservationId = reservationId;
        const reservation = reservationData[reservationId];

        if (!reservation) return;

        // Build modal content
        const primaryGuest = reservation.reservation_guests.find(g => g.is_primary_guest);
        const companions = reservation.reservation_guests.filter(g => !g.is_primary_guest);

        let html = `
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
            const validAmenities = reservation.reservation_amenities.filter(a => a.price > 0);
            if (validAmenities.length > 0) {
                html += `
                    <div style="margin-bottom: 1.5rem;">
                        <h4 style="margin-bottom: 0.5rem; font-weight: 600;">Amenities</h4>
                        <ul style="margin-left: 1.5rem; color: #666;">
                            ${validAmenities.map(a => `
                                <li>${a.amenity_name || a.amenity_id || 'Unknown'} (${a.pricing_type || 'N/A'}) - ₱${parseFloat(a.price).toFixed(2)} x ${a.quantity || 1}</li>
                            `).join('')}
                        </ul>
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
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>Reservation Date:</span>
                    <strong>${reservation.reservation_date || 'N/A'}</strong>
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
        row.addEventListener('click', () => {
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
        guestModal.classList.add('is-open');
        guestModal.setAttribute('aria-hidden', 'false');
    };

    const closeGuestModal = () => {
        currentCustomerId = null;
        guestModal.classList.remove('is-open');
        guestModal.setAttribute('aria-hidden', 'true');
    };

    guestRows.forEach(row => {
        row.addEventListener('click', () => {
            const customerId = row.dataset.customerId;
            openGuestModal(customerId);
        });
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
    const guestModeRadios = document.querySelectorAll('input[name="guest_mode"]');
    const primaryGuestSection = document.getElementById('primaryGuestSection');
    const amenitySection = document.getElementById('amenitySection');
    const chooseAmenitiesBtn = document.getElementById('chooseAmenitiesBtn');
    const visitOnlyOptions = document.getElementById('visitOnlyOptions');
    const visitAmenityOptions = document.getElementById('visitAmenityOptions');
    const visitTimeType = document.getElementById('visit_time_type');
    const visitIncludePool = document.getElementById('visit_include_pool');
    const entranceFeeDisplay = document.getElementById('entranceFeeDisplay');
    
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
                parkSettings = { ...parkSettings, ...data };
                // Set default time period based on current time
                setDefaultTimePeriod();
                // Calculate initial fee
                calculateEntranceFee();
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
            defaultPeriod = 'daynight';
        }
        
        if (visitTimeType) {
            visitTimeType.value = defaultPeriod;
        }
    };

    // Parse time string to minutes
    const parseTime = (timeStr) => {
        const [hours, minutes] = timeStr.split(':').map(Number);
        return hours * 60 + minutes;
    };

    // Calculate entrance fee based on companions
    const calculateEntranceFee = () => {
        const timeType = visitTimeType?.value || 'daytime';
        const includePool = visitIncludePool?.checked || false;
        
        // Calculate fee per person based on companions' age types
        let totalFee = 0;
        
        // Add main guest fee (default to adult for main guest)
        let mainGuestFee = 0;
        if (timeType === 'daytime') {
            mainGuestFee = parkSettings.daytime_adult_entrance_fee;
        } else if (timeType === 'nighttime') {
            mainGuestFee = parkSettings.nighttime_adult_entrance_fee;
        } else if (timeType === 'daynight') {
            mainGuestFee = parkSettings.daytime_adult_entrance_fee + parkSettings.nighttime_adult_entrance_fee;
        }
        
        // Add pool fee for main guest if checked
        if (includePool) {
            if (timeType === 'daytime') {
                mainGuestFee += parkSettings.day_pool_fee;
            } else if (timeType === 'nighttime') {
                mainGuestFee += parkSettings.night_pool_fee;
            } else if (timeType === 'daynight') {
                mainGuestFee += parkSettings.day_pool_fee + parkSettings.night_pool_fee;
            }
        }
        
        totalFee += mainGuestFee;
        
        // Calculate fees for companions
        companions.forEach(companion => {
            let companionFee = 0;
            const ageType = companion.age_type || 'adult';
            
            if (timeType === 'daytime') {
                companionFee = ageType === 'adult' ? parkSettings.daytime_adult_entrance_fee : parkSettings.daytime_child_entrance_fee;
            } else if (timeType === 'nighttime') {
                companionFee = ageType === 'adult' ? parkSettings.nighttime_adult_entrance_fee : parkSettings.nighttime_child_entrance_fee;
            } else if (timeType === 'daynight') {
                const daytimeFee = ageType === 'adult' ? parkSettings.daytime_adult_entrance_fee : parkSettings.daytime_child_entrance_fee;
                const nighttimeFee = ageType === 'adult' ? parkSettings.nighttime_adult_entrance_fee : parkSettings.nighttime_child_entrance_fee;
                companionFee = daytimeFee + nighttimeFee;
            }
            
            // Add pool fee for companion if checked
            if (includePool) {
                if (timeType === 'daytime') {
                    companionFee += parkSettings.day_pool_fee;
                } else if (timeType === 'nighttime') {
                    companionFee += parkSettings.night_pool_fee;
                } else if (timeType === 'daynight') {
                    companionFee += parkSettings.day_pool_fee + parkSettings.night_pool_fee;
                }
            }
            
            totalFee += companionFee;
        });
        
        // Calculate fees for bulk companions
        bulkCompanionGroups.forEach(group => {
            let groupFee = 0;
            const ageType = group.age_type || 'adult';
            
            if (timeType === 'daytime') {
                groupFee = ageType === 'adult' ? parkSettings.daytime_adult_entrance_fee : parkSettings.daytime_child_entrance_fee;
            } else if (timeType === 'nighttime') {
                groupFee = ageType === 'adult' ? parkSettings.nighttime_adult_entrance_fee : parkSettings.nighttime_child_entrance_fee;
            } else if (timeType === 'daynight') {
                const daytimeFee = ageType === 'adult' ? parkSettings.daytime_adult_entrance_fee : parkSettings.daytime_child_entrance_fee;
                const nighttimeFee = ageType === 'adult' ? parkSettings.nighttime_adult_entrance_fee : parkSettings.nighttime_child_entrance_fee;
                groupFee = daytimeFee + nighttimeFee;
            }
            
            // Add pool fee for bulk companions if checked
            if (includePool) {
                if (timeType === 'daytime') {
                    groupFee += parkSettings.day_pool_fee;
                } else if (timeType === 'nighttime') {
                    groupFee += parkSettings.night_pool_fee;
                } else if (timeType === 'daynight') {
                    groupFee += parkSettings.day_pool_fee + parkSettings.night_pool_fee;
                }
            }
            
            totalFee += groupFee * group.quantity;
        });
        
        if (entranceFeeDisplay) {
            entranceFeeDisplay.textContent = `₱${totalFee.toFixed(2)}`;
        }
        
        return totalFee;
    };

    // Guest mode switching
    const handleGuestModeChange = () => {
        const selectedMode = document.querySelector('input[name="guest_mode"]:checked')?.value;
        
        if (selectedMode === 'visitors_only') {
            // Visit Only: Hide primary guest, hide amenity button, show visit-only options
            if (primaryGuestSection) {
                primaryGuestSection.style.display = 'none';
            }
            if (chooseAmenitiesBtn) {
                chooseAmenitiesBtn.classList.add('hidden');
            }
            if (visitOnlyOptions) {
                visitOnlyOptions.style.display = 'block';
            }
            if (visitAmenityOptions) {
                visitAmenityOptions.style.display = 'none';
            }
            if (amenitySection) {
                amenitySection.style.display = 'none';
            }
            // Calculate fee
            calculateEntranceFee();
        } else {
            // Visit & Amenity: Show primary guest, show amenity button, hide visit-only options
            if (primaryGuestSection) {
                primaryGuestSection.style.display = 'block';
            }
            if (chooseAmenitiesBtn) {
                chooseAmenitiesBtn.classList.remove('hidden');
            }
            if (visitOnlyOptions) {
                visitOnlyOptions.style.display = 'none';
            }
            if (visitAmenityOptions) {
                visitAmenityOptions.style.display = 'block';
            }
            if (amenitySection) {
                amenitySection.style.display = 'block';
            }
        }
    };

    guestModeRadios.forEach(radio => {
        radio.addEventListener('change', handleGuestModeChange);
    });

    // Listen for fee calculation changes
    visitTimeType?.addEventListener('change', calculateEntranceFee);
    visitIncludePool?.addEventListener('change', calculateEntranceFee);

    // Initialize guest mode state
    handleGuestModeChange();

    // Visit Only Check-In Modal
    const visitOnlyCheckInModal = document.getElementById('visitOnlyCheckInModal');
    const visitOnlyCheckInBtn = document.getElementById('visitOnlyCheckInBtn');
    const visitAmenitySubmitBtn = document.getElementById('visitAmenitySubmitBtn');
    const proceedCheckInBtn = document.getElementById('proceedCheckInBtn');
    const visitCheckInCloseButtons = document.querySelectorAll('[data-close-visit-check-in-modal="true"]');
    
    const summaryTimeType = document.getElementById('summaryTimeType');
    const summaryPool = document.getElementById('summaryPool');
    const summaryCompanions = document.getElementById('summaryCompanions');
    const summaryTotal = document.getElementById('summaryTotal');

    // Update submit button based on guest mode
    const updateSubmitButton = () => {
        const selectedMode = document.querySelector('input[name="guest_mode"]:checked')?.value;
        if (selectedMode === 'visitors_only') {
            visitOnlyCheckInBtn.style.display = 'inline-flex';
            visitAmenitySubmitBtn.style.display = 'none';
        } else {
            visitOnlyCheckInBtn.style.display = 'none';
            visitAmenitySubmitBtn.style.display = 'inline-flex';
        }
    };

    guestModeRadios.forEach(radio => {
        radio.addEventListener('change', updateSubmitButton);
    });
    updateSubmitButton();

    // Open check-in confirmation modal
    visitOnlyCheckInBtn?.addEventListener('click', () => {
        // Calculate total with companions
        const totalCost = calculateEntranceFee();
        const totalCompanions = companions.length + bulkCompanionGroups.reduce((sum, group) => sum + group.quantity, 0);
        
        // Update summary
        if (summaryTimeType) {
            const timeMap = { daytime: 'Daytime', nighttime: 'Nighttime', daynight: 'Day & Night' };
            summaryTimeType.textContent = timeMap[visitTimeType?.value] || 'Daytime';
        }
        if (summaryPool) {
            summaryPool.textContent = visitIncludePool?.checked ? 'Yes' : 'No';
        }
        if (summaryCompanions) {
            summaryCompanions.textContent = totalCompanions;
        }
        if (summaryTotal) {
            summaryTotal.textContent = `₱${totalCost.toFixed(2)}`;
        }
        
        // Store total for submission
        visitOnlyCheckInBtn.dataset.totalCost = totalCost;
        
        visitOnlyCheckInModal.classList.add('is-open');
        visitOnlyCheckInModal.setAttribute('aria-hidden', 'false');
    });

    // Close check-in modal
    visitCheckInCloseButtons.forEach(button => {
        button.addEventListener('click', () => {
            visitOnlyCheckInModal.classList.remove('is-open');
            visitOnlyCheckInModal.setAttribute('aria-hidden', 'true');
        });
    });

    // Proceed with check-in
    proceedCheckInBtn?.addEventListener('click', async () => {
        const totalCost = parseFloat(visitOnlyCheckInBtn.dataset.totalCost) || 0;
        
        // Prepare data for submission
        const formData = new FormData();
        formData.append('guest_mode', 'visitors_only');
        formData.append('age_type', visitAgeType?.value || 'adult');
        formData.append('time_type', visitTimeType?.value || 'daytime');
        formData.append('include_pool', visitIncludePool?.checked ? '1' : '0');
        formData.append('total_amount', totalCost);
        
        // Add companions
        companions.forEach((companion, index) => {
            formData.append(`companions[${index}][first_name]`, companion.first_name);
            formData.append(`companions[${index}][middle_name]`, companion.middle_name || '');
            formData.append(`companions[${index}][last_name]`, companion.last_name);
            formData.append(`companions[${index}][age]`, companion.age || '');
            formData.append(`companions[${index}][gender]`, companion.gender || '');
            formData.append(`companions[${index}][is_foreigner]`, companion.is_foreigner ? '1' : '0');
            formData.append(`companions[${index}][phone]`, companion.phone || '');
            formData.append(`companions[${index}][email]`, companion.email || '');
        });
        
        bulkCompanionGroups.forEach((group, groupIndex) => {
            for (let i = 0; i < group.quantity; i++) {
                const companionIndex = companions.length + groupIndex * 1000 + i;
                formData.append(`companions[${companionIndex}][age]`, group.age);
                formData.append(`companions[${companionIndex}][gender]`, group.gender);
                formData.append(`companions[${companionIndex}][is_foreigner]`, group.is_foreigner ? '1' : '0');
            }
        });
        
        try {
            const response = await fetch('/staff/checkins/visit-only-check-in', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
                // Close modals
                visitOnlyCheckInModal.classList.remove('is-open');
                visitOnlyCheckInModal.setAttribute('aria-hidden', 'true');
                addGuestModal.classList.remove('is-open');
                addGuestModal.setAttribute('aria-hidden', 'true');
                
                // Reset form
                document.getElementById('addGuestForm')?.reset();
                companions = [];
                bulkCompanionGroups = [];
                renderCompanions();
                
                alert('Check-in successful!');
                location.reload();
            } else {
                throw new Error(data.message || 'Check-in failed');
            }
        } catch (error) {
            console.error('Check-in error:', error);
            alert('Error during check-in: ' + error.message);
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

    // chooseAmenitiesBtn is already defined above in guest mode section
    chooseAmenitiesBtn?.addEventListener('click', openAmenityModal);
    amenityCloseButtons.forEach(button => {
        button.addEventListener('click', closeAmenityModal);
    });

    // Amity checkbox handling
    amenitiesContainer?.addEventListener('change', (e) => {
        if (e.target.classList.contains('amenity-checkbox')) {
            const select = e.target.closest('.guest-amenity-option').querySelector('.guest-amenity-option__select');
            select.disabled = !e.target.checked;
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
                <span class="guest-companion-pill__name">${group.gender} - ${nationality} - Age: ${group.age} - Qty: ${group.quantity}</span>
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
                    <input type="hidden" name="companions[${companionIndex}][age]" value="${group.age}">
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
        
        bulkCompanionGroups.push({
            gender: bulkData.gender,
            age: bulkData.age,
            age_type: bulkData.age_type,
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

    // Reservation filter functionality
    guestReservationSelect?.addEventListener('change', () => {
        const selectedReservationId = guestReservationSelect.value;
        const guestRows = document.querySelectorAll('#guestTableBody .guest-row');
        
        guestRows.forEach(row => {
            if (selectedReservationId === '') {
                // Show all guests when "All Reservations" is selected
                row.style.display = '';
            } else {
                // Check if this guest belongs to the selected reservation
                const rowReservationId = row.dataset.reservationId;
                if (rowReservationId === selectedReservationId) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
        
        // Update results count
        const visibleRows = Array.from(guestRows).filter(row => row.style.display !== 'none');
        const resultsCount = document.getElementById('guestResultsCount');
        if (resultsCount) {
            resultsCount.textContent = `Showing ${visibleRows.length} active guests`;
        }
    });

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

    // Remove companion buttons
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('guest-companion-pill__delete')) {
            e.target.closest('.guest-companion-pill').remove();
        }
    });
});
