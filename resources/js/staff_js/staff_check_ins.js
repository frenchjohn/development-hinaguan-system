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

    const openAddGuestModal = () => {
        addGuestModal.classList.add('is-open');
        addGuestModal.setAttribute('aria-hidden', 'false');
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

    // Amenity modal
    const amenityModal = document.getElementById('amenityModal');
    const amenityCloseButtons = document.querySelectorAll('[data-close-amenity-modal="true"]');
    const chooseAmenitiesBtn = document.getElementById('chooseAmenitiesBtn');
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
    const companionList = document.getElementById('companionList');
    const companionHiddenFields = document.getElementById('companionHiddenFields');
    const companionIsForeigner = document.getElementById('companionIsForeigner');

    const openCompanionModal = () => {
        companionModal.classList.add('is-open');
        companionModal.setAttribute('aria-hidden', 'false');
    };

    const closeCompanionModal = () => {
        companionModal.classList.remove('is-open');
        companionModal.setAttribute('aria-hidden', 'true');
    };

    addCompanionBtn?.addEventListener('click', openCompanionModal);
    companionCloseButtons.forEach(button => {
        button.addEventListener('click', closeCompanionModal);
    });

    companionForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(companionForm);
        const companionData = Object.fromEntries(formData.entries());
        
        companionCount++;
        const companionHtml = `
            <div class="guest-companion-pill">
                <span class="guest-companion-pill__name">${companionData.first_name} ${companionData.last_name}</span>
                <button type="button" class="guest-companion-pill__delete" data-companion-index="${companionCount}">Remove</button>
            </div>
        `;
        companionList.insertAdjacentHTML('beforeend', companionHtml);
        
        // Add hidden fields
        const hiddenFieldsHtml = `
            <input type="hidden" name="companions[${companionCount}][first_name]" value="${companionData.first_name}">
            <input type="hidden" name="companions[${companionCount}][middle_name]" value="${companionData.middle_name || ''}">
            <input type="hidden" name="companions[${companionCount}][last_name]" value="${companionData.last_name}">
            <input type="hidden" name="companions[${companionCount}][age]" value="${companionData.age || ''}">
            <input type="hidden" name="companions[${companionCount}][gender]" value="${companionData.gender || ''}">
            <input type="hidden" name="companions[${companionCount}][is_foreigner]" value="${companionData.is_foreigner === '1' ? '1' : '0'}">
            <input type="hidden" name="companions[${companionCount}][phone]" value="${companionData.phone || ''}">
            <input type="hidden" name="companions[${companionCount}][email]" value="${companionData.email || ''}">
        `;
        companionHiddenFields.insertAdjacentHTML('beforeend', hiddenFieldsHtml);
        
        companionForm.reset();
        closeCompanionModal();
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
