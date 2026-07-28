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

    // =====================
    // SHARED UTILITIES
    // =====================
    const formatDateTime = (dateString) => {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return dateString;
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
        } catch {
            return 'N/A';
        }
    };

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');

    // =====================
    // TAB SWITCHING LOGIC
    // =====================
    const tabButtons = Array.from(document.querySelectorAll('.records-tab-btn'));
    const tabSections = Array.from(document.querySelectorAll('[data-tab-content]'));

    const setActiveTab = (tabName) => {
        tabButtons.forEach((button) => {
            const isActive = button.dataset.tab === tabName;
            button.classList.toggle('records-tab-btn--active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            button.setAttribute('tabindex', isActive ? '0' : '-1');
        });

        tabSections.forEach((section) => {
            section.hidden = section.dataset.tabContent !== tabName;
        });
    };

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            setActiveTab(button.dataset.tab);
        });
    });

    setActiveTab('guests');

    // =====================
    // GUEST TABLE LOGIC
    // =====================
    const guestModal = document.getElementById('guestModal');
    const modalBody = document.getElementById('guestModalBody');
    const closeButtons = document.querySelectorAll('[data-close-modal="true"]');
    const guestData = window.staffGuestData || {};

    const searchInput = document.getElementById('guestSearchInput');
    const sortSelect = document.getElementById('guestSortSelect');
    const checkOutFrom = document.getElementById('guestCheckOutFrom');
    const checkOutTo = document.getElementById('guestCheckOutTo');
    const clearButton = document.getElementById('guestFiltersClear');
    const guestResultsCount = document.getElementById('guestResultsCount');
    const guestFilterToggle = document.getElementById('guestFilterToggle');
    const guestFilterPanel = document.getElementById('guestFilterPanel');
    const guestTableBody = document.getElementById('guestTableBody');
    const guestTableRows = Array.from(guestTableBody?.querySelectorAll('.guest-row') ?? []);

    const openGuestModal = (customerId) => {
        const customerData = guestData?.[customerId] ?? null;

        if (!customerData) {
            modalBody.innerHTML = '<p class="guest-empty">No additional detail available.</p>';
            guestModal.classList.add('is-open');
            guestModal.setAttribute('aria-hidden', 'false');
            return;
        }

        const reservations = customerData?.reservation_guests || [];
        const reservationDetails = reservations.map((entry) => {
            const reservation = entry.reservation || null;
            const reservationGuests = (reservation?.reservation_guests || []).filter((guest) => guest.customer);

            const primaryGuest = reservationGuests.find((guest) => guest.is_primary_guest) ?? null;
            const companions = reservationGuests.filter((guest) => !guest.is_primary_guest);
            const primaryName = primaryGuest?.customer ? [primaryGuest.customer.first_name, primaryGuest.customer.last_name].filter(Boolean).join(' ').trim() : 'N/A';
            const amenities = (reservation?.reservation_amenities || []).map((amenity) => amenity.amenity?.amenities_name).join(', ') || 'None';

            const primaryGuestMarkup = primaryGuest?.customer
                ? `
                    <div class="guest-relationship-item guest-relationship-item--main">
                        <div class="guest-relationship-name">${escapeHtml(primaryName)}</div>
                    </div>
                `
                : '';

            // Hide companions - only show main guest
            const companionMarkup = '';

            return `
                <div class="guest-card">
                    <div style="margin-bottom: 1rem;">
                        <span class="guest-label">Reservation ID</span><div class="guest-value">${escapeHtml(reservation?.id ?? 'N/A')}</div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <span class="guest-label">Check-in</span>
                            <div class="guest-value">${escapeHtml(reservation?.check_in ?? 'N/A')}</div>
                        </div>
                        <div>
                            <span class="guest-label">Check-out</span>
                            <div class="guest-value">${escapeHtml(entry?.checked_out_at ? formatDateTime(entry.checked_out_at) : 'Not yet')}</div>
                        </div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <span class="guest-label">Main Guest</span>
                        <div class="guest-relationship-list">
                            ${primaryGuestMarkup}
                            ${companionMarkup}
                        </div>
                    </div>
                    <div>
                        <span class="guest-label">Amenities</span>
                        <div class="guest-value">${escapeHtml(amenities)}</div>
                    </div>
                </div>
            `;
        }).join('');

        modalBody.innerHTML = `
            <div class="guest-card">
                <div class="guest-card__grid">
                    <div>
                        <span class="guest-label">Full Name</span>
                        <div class="guest-value">${escapeHtml(customerData.first_name)} ${escapeHtml(customerData.middle_name || '')} ${escapeHtml(customerData.last_name)}</div>
                    </div>
                    <div>
                        <span class="guest-label">Age</span>
                        <div class="guest-value">${customerData.age || 'N/A'}</div>
                    </div>
                    <div>
                        <span class="guest-label">Gender</span>
                        <div class="guest-value">${customerData.gender || 'N/A'}</div>
                    </div>
                    <div>
                        <span class="guest-label">Status</span>
                        <div class="guest-value">${customerData.is_foreigner ? 'Foreigner' : 'Filipino'}</div>
                    </div>
                </div>
            </div>
            ${reservationDetails || '<div class="guest-card"><p class="guest-empty">No reservation details available.</p></div>'}
        `;

        guestModal.classList.add('is-open');
        guestModal.setAttribute('aria-hidden', 'false');
    };

    const applyGuestFilters = () => {
        const query = searchInput?.value.trim().toLowerCase() ?? '';
        const sortValue = sortSelect?.value ?? 'checkout-desc';
        const checkOutFromValue = checkOutFrom?.value ?? '';
        const checkOutToValue = checkOutTo?.value ?? '';

        const filteredRows = guestTableRows.filter((row) => {
            const searchText = (row.getAttribute('data-search') || '').toLowerCase();
            const matchesSearch = !query || searchText.includes(query);
            const checkedOutDate = row.getAttribute('data-checked-out') || '';
            const checkedOutDateOnly = checkedOutDate.split(' ')[0];
            const matchesCheckOutFrom = !checkOutFromValue || !checkedOutDateOnly || checkedOutDateOnly >= checkOutFromValue;
            const matchesCheckOutTo = !checkOutToValue || !checkedOutDateOnly || checkedOutDateOnly <= checkOutToValue;
            return matchesSearch && matchesCheckOutFrom && matchesCheckOutTo;
        });

        filteredRows.sort((left, right) => {
            const leftName = (left.getAttribute('data-search') || '').toLowerCase();
            const rightName = (right.getAttribute('data-search') || '').toLowerCase();
            const leftAge = Number(left.getAttribute('data-age-value') || 999999);
            const rightAge = Number(right.getAttribute('data-age-value') || 999999);
            const leftCheckOut = left.getAttribute('data-checked-out') || '';
            const rightCheckOut = right.getAttribute('data-checked-out') || '';

            switch (sortValue) {
                case 'name-desc':
                    return rightName.localeCompare(leftName);
                case 'age-asc':
                    return leftAge - rightAge;
                case 'age-desc':
                    return rightAge - leftAge;
                case 'checkout-desc':
                    return rightCheckOut.localeCompare(leftCheckOut);
                case 'name-asc':
                default:
                    return leftName.localeCompare(rightName);
            }
        });

        guestTableRows.forEach((row) => {
            row.classList.add('is-hidden');
        });

        filteredRows.forEach((row) => {
            row.classList.remove('is-hidden');
        });

        if (guestResultsCount) {
            guestResultsCount.textContent = `Showing ${filteredRows.length} records`;
        }
    };

    guestTableRows.forEach((row) => {
        row.addEventListener('click', () => {
            const customerId = row.getAttribute('data-customer-id');
            openGuestModal(customerId);
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            guestModal.classList.remove('is-open');
            guestModal.setAttribute('aria-hidden', 'true');
        });
    });

    guestModal.addEventListener('click', (event) => {
        if (event.target === guestModal || event.target.classList.contains('guest-modal__backdrop')) {
            guestModal.classList.remove('is-open');
            guestModal.setAttribute('aria-hidden', 'true');
        }
    });

    [searchInput, sortSelect, checkOutFrom, checkOutTo].forEach((element) => {
        element?.addEventListener('input', applyGuestFilters);
        element?.addEventListener('change', applyGuestFilters);
    });

    clearButton?.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        if (sortSelect) sortSelect.value = 'checkout-desc';
        if (checkOutFrom) checkOutFrom.value = '';
        if (checkOutTo) checkOutTo.value = '';
        applyGuestFilters();
    });

    guestFilterToggle?.addEventListener('click', () => {
        if (!guestFilterPanel) return;
        const isExpanded = guestFilterToggle.getAttribute('aria-expanded') === 'true';
        guestFilterPanel.hidden = isExpanded;
        guestFilterToggle.setAttribute('aria-expanded', String(!isExpanded));
        guestFilterToggle.querySelector('.guest-filter-toggle__icon').textContent = isExpanded ? '▾' : '▴';
    });

    // ========================
    // RESERVATION TABLE LOGIC
    // ========================
    const reservationModal = document.getElementById('reservationModal');
    const reservationModalBody = document.getElementById('reservationModalBody');
    const reservationCloseButtons = document.querySelectorAll('[data-close-reservation-modal="true"]');
    const reservationData = window.staffReservationData || {};

    const reservationSearchInput = document.getElementById('reservationSearchInput');
    const reservationSortSelect = document.getElementById('reservationSortSelect');
    const reservationCheckOutFrom = document.getElementById('reservationCheckOutFrom');
    const reservationCheckOutTo = document.getElementById('reservationCheckOutTo');
    const reservationClearButton = document.getElementById('reservationFiltersClear');
    const reservationResultsCount = document.getElementById('reservationResultsCount');
    const reservationFilterToggle = document.getElementById('reservationFilterToggle');
    const reservationFilterPanel = document.getElementById('reservationFilterPanel');
    const reservationTableBody = document.getElementById('reservationTableBody');
    const reservationTableRows = Array.from(reservationTableBody?.querySelectorAll('.reservation-row') ?? []);

    const openReservationModal = (reservationId) => {
        const reservation = reservationData[reservationId];

        if (!reservation) {
            reservationModalBody.innerHTML = '<p class="guest-empty">No reservation details available.</p>';
            reservationModal.classList.add('is-open');
            reservationModal.setAttribute('aria-hidden', 'false');
            return;
        }

        const primaryGuest = reservation.reservation_guests.find(g => g.is_primary_guest);
        const companions = reservation.reservation_guests.filter(g => !g.is_primary_guest && g.checked_out_at);

        let html = `
            <div style="margin-bottom: 1.5rem;">
                <h4 style="margin-bottom: 0.5rem; font-weight: 600; color: var(--hp-text);">Main Guest</h4>
                <div style="padding: 1rem; background-color: var(--hp-cream); border-radius: 0.5rem;">
                    ${primaryGuest && primaryGuest.customer ? `
                        <div><strong>${escapeHtml(primaryGuest.customer.first_name)} ${escapeHtml(primaryGuest.customer.middle_name || '')} ${escapeHtml(primaryGuest.customer.last_name)}</strong></div>
                        <div style="font-size: 0.875rem; color: var(--hp-text-muted);">Age: ${escapeHtml(primaryGuest.customer.age || 'N/A')} | Gender: ${escapeHtml(primaryGuest.customer.gender || 'N/A')} | Status: ${escapeHtml(primaryGuest.customer.is_foreigner ? 'Foreigner' : 'Filipino')}</div>
                        <div style="font-size: 0.875rem; color: var(--hp-text-muted); margin-top: 0.5rem;">Checked Out: ${escapeHtml(primaryGuest.checked_out_at ? formatDateTime(primaryGuest.checked_out_at) : 'Not yet')}</div>
                    ` : '<div style="color: var(--hp-text);">No main guest assigned</div>'}
                </div>
            </div>
        `;

        if (companions.length > 0) {
            // Group companions by age, gender, and nationality
            const companionGroups = {};
            companions.forEach(c => {
                if (!c.customer) return;
                
                const age = c.customer.age || 'N/A';
                const gender = c.customer.gender || 'N/A';
                const nationality = c.customer.is_foreigner ? 'Foreigner' : 'Filipino';
                const key = `${age}|${gender}|${nationality}`;
                
                if (!companionGroups[key]) {
                    companionGroups[key] = {
                        age,
                        gender,
                        nationality,
                        count: 0
                    };
                }
                companionGroups[key].count++;
            });

            const groupEntries = Object.entries(companionGroups);
            
            if (groupEntries.length > 0) {
                html += `
                    <div style="margin-bottom: 1.5rem;">
                        <h4 style="margin-bottom: 0.5rem; font-weight: 600; color: var(--hp-text);">Companions (${companions.length})</h4>
                        ${groupEntries.map(([key, group]) => `
                            <div style="padding: 0.75rem; background-color: var(--hp-cream); border-radius: 0.5rem; margin-bottom: 0.5rem;">
                                <div><strong style="color: var(--hp-text);">Age: ${escapeHtml(group.age)}, Gender: ${escapeHtml(group.gender)}, Nationality: ${escapeHtml(group.nationality)}</strong></div>
                                <div style="font-size: 0.875rem; color: var(--hp-text-muted);">Quantity: ${group.count}</div>
                            </div>
                        `).join('')}
                    </div>
                `;
            }
        }

        if (reservation.reservation_amenities && reservation.reservation_amenities.length > 0) {
            html += `
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="margin-bottom: 0.5rem; font-weight: 600;">Amenities</h4>
                    <ul style="margin-left: 1.5rem; color: #666;">
                        ${reservation.reservation_amenities.map(a => `
                            <li>${escapeHtml(a.amenity?.amenities_name || a.amenity_name || 'Unknown')} (${escapeHtml(a.pricing_type)}) - ₱${parseFloat(a.price_at_booking || a.price || 0).toFixed(2)} x ${a.quantity}</li>
                        `).join('')}
                    </ul>
                </div>
            `;
        }

        html += `
            <div style="border-top: 1px solid #ddd; padding-top: 1rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <span class="guest-label">Reservation Date</span>
                        <div class="guest-value">${escapeHtml(reservation.reservation_date)}</div>
                    </div>
                    <div>
                        <span class="guest-label">Check-in</span>
                        <div class="guest-value">${escapeHtml(reservation.check_in)}</div>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <span class="guest-label">Check-out</span>
                        <div class="guest-value">${escapeHtml(reservation.check_out || 'Not checked out')}</div>
                    </div>
                    <div>
                        <span class="guest-label">Created</span>
                        <div class="guest-value">${escapeHtml(formatDateTime(reservation.created_at))}</div>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <span class="guest-label">Status</span>
                        <div class="guest-value"><span class="guest-pill">${escapeHtml(reservation.status)}</span></div>
                    </div>
                    <div>
                        <span class="guest-label">Type</span>
                        <div class="guest-value">${escapeHtml(reservation.reservation_type === 'walk_in' ? 'Walk-in' : 'Online')}</div>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                    <div>
                        <span class="guest-label">Total Amount</span>
                        <div class="guest-value">₱${parseFloat(reservation.total_amount || 0).toFixed(2)}</div>
                    </div>
                    <div>
                        <span class="guest-label">Amount Paid</span>
                        <div class="guest-value">₱${parseFloat(reservation.amount_paid || 0).toFixed(2)}</div>
                    </div>
                </div>
            </div>
        `;

        reservationModalBody.innerHTML = html;
        const modalTitle = document.getElementById('reservationModalTitle');
        if (modalTitle) modalTitle.textContent = `Reservation #${reservation.id}`;
        reservationModal.classList.add('is-open');
        reservationModal.setAttribute('aria-hidden', 'false');
    };

    const applyReservationFilters = () => {
        const query = reservationSearchInput?.value.trim().toLowerCase() ?? '';
        const sortValue = reservationSortSelect?.value ?? 'date-desc';
        const checkOutFromValue = reservationCheckOutFrom?.value ?? '';
        const checkOutToValue = reservationCheckOutTo?.value ?? '';

        const filteredRows = reservationTableRows.filter((row) => {
            const searchText = (row.getAttribute('data-search') || '').toLowerCase();
            const matchesSearch = !query || searchText.includes(query);
            const checkOutDate = row.getAttribute('data-check-out') || '';
            const checkOutDateOnly = checkOutDate.split(' ')[0];
            const matchesCheckOutFrom = !checkOutFromValue || !checkOutDateOnly || checkOutDateOnly >= checkOutFromValue;
            const matchesCheckOutTo = !checkOutToValue || !checkOutDateOnly || checkOutDateOnly <= checkOutToValue;
            return matchesSearch && matchesCheckOutFrom && matchesCheckOutTo;
        });

        filteredRows.sort((left, right) => {
            const leftName = (left.getAttribute('data-booker-name') || '').toLowerCase();
            const rightName = (right.getAttribute('data-booker-name') || '').toLowerCase();
            const leftAmount = Number(left.getAttribute('data-amount') || 0);
            const rightAmount = Number(right.getAttribute('data-amount') || 0);
            const leftCheckOut = left.getAttribute('data-check-out') || '';
            const rightCheckOut = right.getAttribute('data-check-out') || '';

            switch (sortValue) {
                case 'date-asc':
                    return leftCheckOut.localeCompare(rightCheckOut);
                case 'name-asc':
                    return leftName.localeCompare(rightName);
                case 'name-desc':
                    return rightName.localeCompare(leftName);
                case 'amount-desc':
                    return rightAmount - leftAmount;
                case 'date-desc':
                default:
                    return rightCheckOut.localeCompare(leftCheckOut);
            }
        });

        reservationTableRows.forEach((row) => {
            row.classList.add('is-hidden');
        });

        filteredRows.forEach((row) => {
            row.classList.remove('is-hidden');
        });

        if (reservationResultsCount) {
            reservationResultsCount.textContent = `Showing ${filteredRows.length} reservations`;
        }
    };

    reservationTableRows.forEach((row) => {
        row.addEventListener('click', () => {
            const reservationId = row.getAttribute('data-reservation-id');
            openReservationModal(reservationId);
        });
    });

    reservationCloseButtons.forEach((button) => {
        button.addEventListener('click', () => {
            reservationModal.classList.remove('is-open');
            reservationModal.setAttribute('aria-hidden', 'true');
        });
    });

    reservationModal.addEventListener('click', (event) => {
        if (event.target === reservationModal || event.target.classList.contains('guest-modal__backdrop')) {
            reservationModal.classList.remove('is-open');
            reservationModal.setAttribute('aria-hidden', 'true');
        }
    });

    [reservationSearchInput, reservationSortSelect, reservationCheckOutFrom, reservationCheckOutTo].forEach((element) => {
        element?.addEventListener('input', applyReservationFilters);
        element?.addEventListener('change', applyReservationFilters);
    });

    reservationClearButton?.addEventListener('click', () => {
        if (reservationSearchInput) reservationSearchInput.value = '';
        if (reservationSortSelect) reservationSortSelect.value = 'date-desc';
        if (reservationCheckOutFrom) reservationCheckOutFrom.value = '';
        if (reservationCheckOutTo) reservationCheckOutTo.value = '';
        applyReservationFilters();
    });

    reservationFilterToggle?.addEventListener('click', () => {
        if (!reservationFilterPanel) return;
        const isExpanded = reservationFilterToggle.getAttribute('aria-expanded') === 'true';
        reservationFilterPanel.hidden = isExpanded;
        reservationFilterToggle.setAttribute('aria-expanded', String(!isExpanded));
        reservationFilterToggle.querySelector('.guest-filter-toggle__icon').textContent = isExpanded ? '▾' : '▴';
    });

    // Initialize
    applyGuestFilters();
    applyReservationFilters();
});
