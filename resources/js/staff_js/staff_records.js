window.AppPage = window.AppPage || {};
window.AppPage['staff_records'] = function () {


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
    const bulkGroupData = window.staffBulkGroupData || {};

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
    const guestPageNumbers = document.getElementById('guestPageNumbers');
    const guestPageInput = document.getElementById('guestPageInput');

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
            const primaryEmail = primaryGuest?.customer?.email || '';
            const primaryPhone = primaryGuest?.customer?.phone || '';
            const amenities = (reservation?.reservation_amenities || []).map((amenity) => amenity.amenity?.amenities_name).join(', ') || 'None';

            const primaryGuestMarkup = primaryGuest?.customer
                ? `
                    <div class="guest-relationship-item guest-relationship-item--main">
                        <div class="guest-relationship-name">${escapeHtml(primaryName)}</div>
                        ${(primaryEmail || primaryPhone) ? `<div class="guest-relationship-meta" style="margin-top:0.25rem;font-size:0.8rem;color:var(--hp-text-muted);">${primaryEmail ? `Email: ${escapeHtml(primaryEmail)}` : ''}${primaryEmail && primaryPhone ? ' · ' : ''}${primaryPhone ? `Phone: ${escapeHtml(primaryPhone)}` : ''}</div>` : ''}
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
                        <span class="guest-label">Nationality</span>
                        <div class="guest-value">${customerData.is_foreigner ? 'Foreigner' : 'Filipino'}</div>
                    </div>
                    <div>
                        <span class="guest-label">Email</span>
                        <div class="guest-value" style="word-break:break-word;">${escapeHtml(customerData.email || 'N/A')}</div>
                    </div>
                    <div>
                        <span class="guest-label">Phone</span>
                        <div class="guest-value">${escapeHtml(customerData.phone || 'N/A')}</div>
                    </div>
                </div>
            </div>
            ${reservationDetails || '<div class="guest-card"><p class="guest-empty">No reservation details available.</p></div>'}
        `;

        guestModal.classList.add('is-open');
        guestModal.setAttribute('aria-hidden', 'false');
    };

    // Bulk companion group details: the merged row shows a summary of the
    // group, then every checked-out member individually with its customer id,
    // check-in and checked-out date-time.
    const openBulkGroupModal = (groupKey) => {
        const group = bulkGroupData[groupKey];

        if (!group) {
            modalBody.innerHTML = '<p class="guest-empty">No additional detail available.</p>';
            guestModal.classList.add('is-open');
            guestModal.setAttribute('aria-hidden', 'false');
            return;
        }

        const members = group.members || [];

        // Merge members that were checked out at the same exact time into one
        // row ("Customer #14, #15, #16") — hovering the ids lists each one.
        const checkoutBuckets = [];
        members.forEach((member) => {
            const key = member.checked_out_at || '';
            let bucket = checkoutBuckets.find((b) => b.key === key);
            if (!bucket) {
                bucket = { key, members: [] };
                checkoutBuckets.push(bucket);
            }
            bucket.members.push(member);
        });

        const membersHtml = checkoutBuckets.map((bucket) => {
            const first = bucket.members[0];
            const ids = bucket.members.map((m) => `#${m.customer_id}`).join(', ');
            const tooltip = bucket.members.map((m) =>
                `Customer #${m.customer_id} — Check-in: ${m.check_in ? formatDateTime(m.check_in) : 'N/A'} — Checked-out: ${m.checked_out_at ? formatDateTime(m.checked_out_at) : 'N/A'}`
            ).join('\n');

            return `
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:0.35rem 1.25rem;padding:0.6rem 0;border-bottom:1px solid rgba(0,0,0,0.08);">
                    <span data-tooltip="${escapeHtml(tooltip)}" style="min-width:8.5rem;font-weight:600;color:var(--hp-text);">
                        Customer ${escapeHtml(ids)}
                        <span style="margin-left:0.35rem;font-size:0.7rem;font-weight:700;color:var(--hp-green);background:rgba(46,125,85,0.1);border-radius:999px;padding:0.1rem 0.5rem;">${bucket.members.length}x</span>
                    </span>
                    <span style="font-size:0.82rem;color:var(--hp-text-muted);">Check-in: ${escapeHtml(first.check_in ? formatDateTime(first.check_in) : 'N/A')}</span>
                    <span style="font-size:0.82rem;color:var(--hp-text-muted);">Checked-out: ${escapeHtml(first.checked_out_at ? formatDateTime(first.checked_out_at) : 'N/A')}</span>
                </div>
            `;
        }).join('') || '<p class="guest-empty">No checked-out members in this group.</p>';

        modalBody.innerHTML = `
            <div class="guest-card">
                <div class="guest-card__grid" style="grid-template-columns:1fr 1fr;">
                    <div>
                        <span class="guest-label">Group</span>
                        <div class="guest-value">${escapeHtml(group.name)}</div>
                    </div>
                    <div>
                        <span class="guest-label">Reservation</span>
                        <div class="guest-value">#${escapeHtml(group.reservation_id)}</div>
                    </div>
                    <div>
                        <span class="guest-label">Age Group</span>
                        <div class="guest-value">${escapeHtml(group.age_group)}</div>
                    </div>
                    <div>
                        <span class="guest-label">Gender</span>
                        <div class="guest-value">${escapeHtml(group.gender)}</div>
                    </div>
                    <div>
                        <span class="guest-label">Nationality</span>
                        <div class="guest-value">${escapeHtml(group.nationality)}</div>
                    </div>
                    <div>
                        <span class="guest-label">Quantity</span>
                        <div class="guest-value">${escapeHtml(group.count)}x checked out</div>
                    </div>
                </div>
            </div>
            <div class="guest-card">
                <h4 style="margin:0 0 0.5rem;font-weight:600;font-size:0.95rem;color:var(--hp-text);">Checked-Out Members (${members.length})</h4>
                ${membersHtml}
            </div>
        `;

        guestModal.classList.add('is-open');
        guestModal.setAttribute('aria-hidden', 'false');
    };

    // ---- Pagination helpers ----
    const renderPageNumberButtons = (container, current, total, onSelect) => {
        if (!container) return;
        container.innerHTML = '';
        if (total <= 1) return;

        const buildBtn = (page) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = page;
            btn.className = page === current
                ? 'cursor-pointer rounded-lg bg-hp-green px-2.5 py-1.5 text-sm font-bold text-white transition-colors duration-200 hover:bg-hp-green-dark'
                : 'cursor-pointer rounded-lg border border-glass-border bg-glass px-2.5 py-1.5 text-sm font-semibold text-hp-text transition-colors duration-200 hover:bg-glass-hover';
            btn.addEventListener('click', () => onSelect(page));
            return btn;
        };

        const pages = [];
        for (let i = 1; i <= total; i++) {
            if (total <= 7 || i === 1 || i === total || Math.abs(i - current) <= 1) pages.push(i);
            else if (pages[pages.length - 1] !== -1) pages.push(-1);
        }
        pages.forEach((p) => {
            if (p === -1) {
                const span = document.createElement('span');
                span.className = 'px-1 text-sm text-hp-text-muted';
                span.textContent = '…';
                container.appendChild(span);
            } else {
                container.appendChild(buildBtn(p));
            }
        });
    };

    // ---- Guest pagination state ----
    const guestTableBodyEl = document.getElementById('guestTableBody');
    let guestPage = 1;
    let guestFilteredRows = [];

    const renderGuestPagination = () => {
        const perPage = Number(guestPerPage?.value || 10);
        const total = guestFilteredRows.length;
        const totalPages = Math.max(1, Math.ceil(total / perPage));
        guestPage = Math.min(Math.max(1, guestPage), totalPages);

        guestTableRows.forEach((row) => row.classList.add('is-hidden'));
        const start = (guestPage - 1) * perPage;
        const slice = guestFilteredRows.slice(start, start + perPage);
        slice.forEach((row) => row.classList.remove('is-hidden'));

        // Empty-state row when filters match nothing
        let emptyRow = document.getElementById('guestTableEmptyRow');
        if (total === 0) {
            if (!emptyRow && guestTableBodyEl) {
                emptyRow = document.createElement('tr');
                emptyRow.id = 'guestTableEmptyRow';
                emptyRow.innerHTML = '<td colspan="9" class="guest-empty px-4 py-8 text-center text-hp-text-muted">No records match your filters.</td>';
                guestTableBodyEl.appendChild(emptyRow);
            }
            if (emptyRow) emptyRow.style.display = '';
        } else if (emptyRow) {
            emptyRow.style.display = 'none';
        }

        if (guestResultsCount) {
            guestResultsCount.textContent = total === 0
                ? 'Showing 0 of 0 results'
                : `Showing ${start + 1} to ${start + slice.length} of ${total} results`;
        }
        renderPageNumberButtons(guestPageNumbers, guestPage, totalPages, (page) => {
            guestPage = page;
            renderGuestPagination();
        });
        if (guestPrevPage) guestPrevPage.disabled = guestPage <= 1;
        if (guestNextPage) guestNextPage.disabled = guestPage >= totalPages;
        if (guestPageInput) {
            guestPageInput.value = guestPage;
            guestPageInput.max = totalPages;
        }
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
            const leftCustomerId = Number(left.getAttribute('data-customer-id') || 0);
            const rightCustomerId = Number(right.getAttribute('data-customer-id') || 0);
            const leftResId = Number(left.getAttribute('data-reservation-id') || 0);
            const rightResId = Number(right.getAttribute('data-reservation-id') || 0);
            const leftAge = Number(left.getAttribute('data-age-value') || 999999);
            const rightAge = Number(right.getAttribute('data-age-value') || 999999);
            const leftGender = (left.getAttribute('data-gender') || '').toLowerCase();
            const rightGender = (right.getAttribute('data-gender') || '').toLowerCase();
            const leftNationality = (left.getAttribute('data-nationality') || '').toLowerCase();
            const rightNationality = (right.getAttribute('data-nationality') || '').toLowerCase();
            const leftStatus = (left.getAttribute('data-status') || '').toLowerCase();
            const rightStatus = (right.getAttribute('data-status') || '').toLowerCase();
            const leftCheckOut = left.getAttribute('data-checked-out') || '';
            const rightCheckOut = right.getAttribute('data-checked-out') || '';

            switch (sortValue) {
                case 'name-desc':
                    return rightName.localeCompare(leftName);
                case 'customer-id-asc':
                    return leftCustomerId - rightCustomerId;
                case 'customer-id-desc':
                    return rightCustomerId - leftCustomerId;
                case 'reservation-asc':
                    return leftResId - rightResId;
                case 'reservation-desc':
                    return rightResId - leftResId;
                case 'age-asc':
                    return leftAge - rightAge;
                case 'age-desc':
                    return rightAge - leftAge;
                case 'gender-asc':
                    return leftGender.localeCompare(rightGender);
                case 'gender-desc':
                    return rightGender.localeCompare(leftGender);
                case 'nationality-asc':
                    return leftNationality.localeCompare(rightNationality);
                case 'nationality-desc':
                    return rightNationality.localeCompare(leftNationality);
                case 'status-asc':
                    return leftStatus.localeCompare(rightStatus);
                case 'status-desc':
                    return rightStatus.localeCompare(leftStatus);
                case 'checkout-asc':
                    return leftCheckOut.localeCompare(rightCheckOut);
                case 'checkout-desc':
                    return rightCheckOut.localeCompare(leftCheckOut);
                case 'name-asc':
                default:
                    return leftName.localeCompare(rightName);
            }
        });

        guestFilteredRows = filteredRows;
        guestPage = 1;
        renderGuestPagination();
        updateGuestSortIndicators();
    };

    // Sort arrows on the table headers (kept in sync with the filter-panel sort select)
    const updateGuestSortIndicators = () => {
        const sortValue = sortSelect?.value ?? 'checkout-desc';
        const map = {
            name: ['name-asc', 'name-desc'],
            'customer-id': ['customer-id-asc', 'customer-id-desc'],
            reservation: ['reservation-asc', 'reservation-desc'],
            age: ['age-asc', 'age-desc'],
            gender: ['gender-asc', 'gender-desc'],
            nationality: ['nationality-asc', 'nationality-desc'],
            status: ['status-asc', 'status-desc'],
            'checked-out': ['checkout-asc', 'checkout-desc']
        };
        document.querySelectorAll('#guestTableWrap thead th.sortable').forEach((th) => {
            th.classList.remove('is-sorted-asc', 'is-sorted-desc');
            const pair = map[th.dataset.sort];
            if (pair && pair.includes(sortValue)) {
                th.classList.add(sortValue.endsWith('-asc') ? 'is-sorted-asc' : 'is-sorted-desc');
            }
        });
    };

    const guestHeaderSortMap = {
        name: 'name',
        'customer-id': 'customer-id',
        reservation: 'reservation',
        age: 'age',
        gender: 'gender',
        nationality: 'nationality',
        status: 'status',
        'checked-out': 'checkout'
    };
    document.querySelectorAll('#guestTableWrap thead th.sortable').forEach((th) => {
        th.addEventListener('click', () => {
            if (!sortSelect) return;
            const key = guestHeaderSortMap[th.dataset.sort];
            if (!key) return;
            const current = sortSelect.value;
            const dir = current === `${key}-asc` ? 'desc' : 'asc';
            sortSelect.value = `${key}-${dir}`;
            applyGuestFilters();
        });
    });



    guestTableRows.forEach((row) => {
        row.addEventListener('click', () => {
            if (row.dataset.bulkGroup === 'true') {
                openBulkGroupModal(row.dataset.bulkKey);
                return;
            }
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

    // Guest pagination controls
    const guestGoPageBtn = document.getElementById('guestGoPage');
    const guestPerPageSel = document.getElementById('guestPerPage');
    const guestPrevPageBtn = document.getElementById('guestPrevPage');
    const guestNextPageBtn = document.getElementById('guestNextPage');

    guestPrevPageBtn?.addEventListener('click', () => {
        if (guestPage > 1) { guestPage--; renderGuestPagination(); }
    });
    guestNextPageBtn?.addEventListener('click', () => {
        if (guestPage < Math.ceil(guestFilteredRows.length / Number(guestPerPageSel?.value || 10))) { guestPage++; renderGuestPagination(); }
    });
    guestGoPageBtn?.addEventListener('click', () => {
        const page = parseInt(guestPageInput?.value, 10);
        if (!isNaN(page) && page >= 1) { guestPage = page; renderGuestPagination(); }
    });
    guestPageInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); guestGoPageBtn?.click(); }
    });
    guestPerPageSel?.addEventListener('change', () => {
        guestPage = 1;
        renderGuestPagination();
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
    const reservationPageNumbers = document.getElementById('reservationPageNumbers');
    const reservationPageInput = document.getElementById('reservationPageInput');

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
                        <div style="font-size: 0.875rem; color: var(--hp-text-muted); margin-top: 0.25rem;">Email: ${escapeHtml(primaryGuest.customer.email || 'N/A')} | Phone: ${escapeHtml(primaryGuest.customer.phone || 'N/A')}</div>
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
                        count: 0,
                        emails: [],
                        phones: []
                    };
                }
                companionGroups[key].count++;
                if (c.customer.email) companionGroups[key].emails.push(c.customer.email);
                if (c.customer.phone) companionGroups[key].phones.push(c.customer.phone);
            });

            // De-duplicate contact details per group.
            Object.values(companionGroups).forEach((group) => {
                group.emails = [...new Set(group.emails)];
                group.phones = [...new Set(group.phones)];
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
                                ${group.emails.length ? `<div style="font-size: 0.875rem; color: var(--hp-text-muted); word-break: break-word;">Emails: ${group.emails.map((e) => escapeHtml(e)).join(', ')}</div>` : ''}
                                ${group.phones.length ? `<div style="font-size: 0.875rem; color: var(--hp-text-muted);">Phones: ${group.phones.map((p) => escapeHtml(p)).join(', ')}</div>` : ''}
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

    // ---- Reservation pagination state ----
    const reservationTableBodyEl = document.getElementById('reservationTableBody');
    let reservationPage = 1;
    let reservationFilteredRows = [];

    const renderReservationPagination = () => {
        const perPage = Number(reservationPerPage?.value || 10);
        const total = reservationFilteredRows.length;
        const totalPages = Math.max(1, Math.ceil(total / perPage));
        reservationPage = Math.min(Math.max(1, reservationPage), totalPages);

        reservationTableRows.forEach((row) => row.classList.add('is-hidden'));
        const start = (reservationPage - 1) * perPage;
        const slice = reservationFilteredRows.slice(start, start + perPage);
        slice.forEach((row) => row.classList.remove('is-hidden'));

        // Hide companion rows whose parent reservation is off-page / filtered out
        // (collapsed state is still governed by the expand button's inline style).
        const visibleIds = new Set(slice.map((row) => row.getAttribute('data-reservation-id')));
        document.querySelectorAll('.companion-row').forEach((row) => {
            const match = /companion-of-(\d+)/.exec(row.className);
            if (!match) return;
            if (visibleIds.has(match[1])) {
                row.classList.remove('is-hidden');
            } else {
                row.classList.add('is-hidden');
            }
        });

        // Empty-state row when filters match nothing
        let emptyRow = document.getElementById('reservationTableEmptyRow');
        if (total === 0) {
            if (!emptyRow && reservationTableBodyEl) {
                emptyRow = document.createElement('tr');
                emptyRow.id = 'reservationTableEmptyRow';
                emptyRow.innerHTML = '<td colspan="8" class="guest-empty px-4 py-8 text-center text-hp-text-muted">No reservations match your filters.</td>';
                reservationTableBodyEl.appendChild(emptyRow);
            }
            if (emptyRow) emptyRow.style.display = '';
        } else if (emptyRow) {
            emptyRow.style.display = 'none';
        }

        if (reservationResultsCount) {
            reservationResultsCount.textContent = total === 0
                ? 'Showing 0 of 0 reservations'
                : `Showing ${start + 1}-${start + slice.length} of ${total} reservations`;
        }
        renderPageNumberButtons(reservationPageNumbers, reservationPage, totalPages, (page) => {
            reservationPage = page;
            renderReservationPagination();
        });
        if (reservationPrevPage) reservationPrevPage.disabled = reservationPage <= 1;
        if (reservationNextPage) reservationNextPage.disabled = reservationPage >= totalPages;
        if (reservationPageInput) {
            reservationPageInput.value = reservationPage;
            reservationPageInput.max = totalPages;
        }
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

        reservationFilteredRows = filteredRows;
        reservationPage = 1;
        renderReservationPagination();
    };

    reservationTableRows.forEach((row) => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('.btn-expand-row')) return;
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

    // Reservation pagination controls
    const reservationGoPageBtn = document.getElementById('reservationGoPage');
    const reservationPerPageSel = document.getElementById('reservationPerPage');
    const reservationPrevPageBtn = document.getElementById('reservationPrevPage');
    const reservationNextPageBtn = document.getElementById('reservationNextPage');

    reservationPrevPageBtn?.addEventListener('click', () => {
        if (reservationPage > 1) { reservationPage--; renderReservationPagination(); }
    });
    reservationNextPageBtn?.addEventListener('click', () => {
        if (reservationPage < Math.ceil(reservationFilteredRows.length / Number(reservationPerPageSel?.value || 10))) { reservationPage++; renderReservationPagination(); }
    });
    reservationGoPageBtn?.addEventListener('click', () => {
        const page = parseInt(reservationPageInput?.value, 10);
        if (!isNaN(page) && page >= 1) { reservationPage = page; renderReservationPagination(); }
    });
    reservationPageInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); reservationGoPageBtn?.click(); }
    });
    reservationPerPageSel?.addEventListener('change', () => {
        reservationPage = 1;
        renderReservationPagination();
    });

    // Expandable Row Logic
    document.querySelectorAll('.btn-expand-row').forEach(expandBtn => {
        expandBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            
            const tr = expandBtn.closest('tr');
            if (!tr) return;
            
            const isExpanded = expandBtn.classList.toggle('expanded');
            expandBtn.style.transform = isExpanded ? 'rotate(180deg)' : '';

            if (tr.classList.contains('reservation-row')) {
                const resId = tr.getAttribute('data-reservation-id');
                const companions = document.querySelectorAll(`.companion-of-${resId}`);
                companions.forEach(c => {
                    c.style.display = isExpanded ? '' : 'none';
                });
            }
        });
    });

    // Initialize
    applyGuestFilters();
    applyReservationFilters();
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['staff_records']());