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
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
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

    // ============================================================
    // DYNAMIC STATS COUNTER UPDATERS
    // ============================================================
    const counterGuestRecords = document.getElementById('counterGuestRecords');
    const counterCheckedOut = document.getElementById('counterCheckedOut');
    const counterNoShow = document.getElementById('counterNoShow');
    const counterCancelled = document.getElementById('counterCancelled');
    const counterRevenueCollected = document.getElementById('counterRevenueCollected');

    const updateCountersFromGuests = (rows) => {
        let totalGuests = 0;
        let checkedOutCount = 0;
        let noShowCount = 0;
        let cancelledCount = 0;
        const uniqueResIds = new Set();
        const resAmounts = window.staffReservationAmounts || {};

        rows.forEach((row) => {
            const guestCount = parseInt(row.getAttribute('data-guest-count') || '1', 10);
            const count = isNaN(guestCount) ? 1 : guestCount;
            totalGuests += count;

            const status = (row.getAttribute('data-status') || '').toLowerCase();
            if (status.includes('cancel')) {
                cancelledCount += count;
            } else if (status.includes('no show') || status.includes('noshow')) {
                noShowCount += count;
            } else {
                checkedOutCount += count;
            }

            const resId = row.getAttribute('data-reservation-id');
            if (resId && resId !== '' && resId !== '0') {
                uniqueResIds.add(resId);
            }
        });

        let totalRevenue = 0;
        uniqueResIds.forEach((resId) => {
            if (resAmounts[resId] !== undefined) {
                totalRevenue += parseFloat(resAmounts[resId]) || 0;
            }
        });

        if (counterGuestRecords) counterGuestRecords.textContent = totalGuests.toLocaleString();
        if (counterCheckedOut) counterCheckedOut.textContent = checkedOutCount.toLocaleString();
        if (counterNoShow) counterNoShow.textContent = noShowCount.toLocaleString();
        if (counterCancelled) counterCancelled.textContent = cancelledCount.toLocaleString();
        if (counterRevenueCollected) counterRevenueCollected.textContent = '₱' + Math.round(totalRevenue).toLocaleString();
    };

    const updateCountersFromReservations = (rows) => {
        let totalGuests = 0;
        let totalRevenue = 0;
        let checkedOutCount = 0;
        let noShowCount = 0;
        let cancelledCount = 0;

        rows.forEach((row) => {
            const count = parseInt(row.getAttribute('data-guest-count') || '1', 10);
            totalGuests += isNaN(count) ? 1 : count;

            const amount = parseFloat(row.getAttribute('data-amount') || '0');
            totalRevenue += isNaN(amount) ? 0 : amount;

            const status = (row.getAttribute('data-status') || '').toLowerCase();
            if (status.includes('cancel')) {
                cancelledCount++;
            } else if (status.includes('no show') || status.includes('noshow')) {
                noShowCount++;
            } else {
                checkedOutCount++;
            }
        });

        if (counterGuestRecords) counterGuestRecords.textContent = totalGuests.toLocaleString();
        if (counterCheckedOut) counterCheckedOut.textContent = checkedOutCount.toLocaleString();
        if (counterNoShow) counterNoShow.textContent = noShowCount.toLocaleString();
        if (counterCancelled) counterCancelled.textContent = cancelledCount.toLocaleString();
        if (counterRevenueCollected) counterRevenueCollected.textContent = '₱' + Math.round(totalRevenue).toLocaleString();
    };

    // =====================
    // GUEST MODAL (shared for companion row details)
    // =====================
    const guestModal = document.getElementById('guestModal');
    const modalBody = document.getElementById('guestModalBody');
    const closeButtons = document.querySelectorAll('[data-close-modal="true"]');
    const guestData = window.staffGuestData || {};
    const bulkGroupData = window.staffBulkGroupData || {};



    const openGuestModal = (customerId) => {
        const customerData = guestData?.[customerId] ?? null;

        if (!customerData) {
            modalBody.innerHTML = '<p class="guest-empty text-center py-6 text-xs text-[#889b8a]">No additional detail available.</p>';
            guestModal.classList.add('is-open');
            guestModal.setAttribute('aria-hidden', 'false');
            return;
        }

        const fullName = [customerData.first_name, customerData.middle_name, customerData.last_name].filter(Boolean).join(' ');
        const reservations = customerData?.reservation_guests || [];
        const reservationDetails = reservations.map((entry) => {
            const reservation = entry.reservation || null;
            const reservationGuests = (reservation?.reservation_guests || []).filter((guest) => guest.customer);

            const primaryGuest = reservationGuests.find((guest) => guest.is_primary_guest) ?? null;
            const primaryName = primaryGuest?.customer ? [primaryGuest.customer.first_name, primaryGuest.customer.last_name].filter(Boolean).join(' ').trim() : 'N/A';
            const primaryEmail = primaryGuest?.customer?.email || '';
            const primaryPhone = primaryGuest?.customer?.phone || '';
            const amenities = (reservation?.reservation_amenities || []).map((amenity) => amenity.amenity?.amenities_name).join(', ') || 'None';

            const resStatus = (reservation?.status || (entry?.checked_out_at ? 'Checked Out' : 'N/A')).trim();
            let statusBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40';
            if (resStatus.toLowerCase().includes('cancel')) {
                statusBadgeClass = 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/40';
            } else if (resStatus.toLowerCase().includes('no show') || resStatus.toLowerCase().includes('noshow')) {
                statusBadgeClass = 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800/50 dark:text-slate-300 dark:border-slate-700';
            }

            const primaryGuestMarkup = primaryGuest?.customer
                ? `
                    <div class="p-3 rounded-xl bg-[#f8faf9] dark:bg-[#141715] border border-[#e5e9e6] dark:border-[#282c29]">
                        <div class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(primaryName)}</div>
                        ${(primaryEmail || primaryPhone) ? `<div class="text-[0.75rem] text-[#5a6b5c] dark:text-[#a8b8a8] mt-0.5">${primaryEmail ? `Email: ${escapeHtml(primaryEmail)}` : ''}${primaryEmail && primaryPhone ? ' · ' : ''}${primaryPhone ? `Phone: ${escapeHtml(primaryPhone)}` : ''}</div>` : ''}
                    </div>
                `
                : '';

            return `
                <div class="p-4 rounded-xl bg-[#f4f7f5] dark:bg-[#141715] border border-[#dbe3de] dark:border-[#282c29] space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-[0.7rem] font-bold text-[#5a6b5c] dark:text-[#a8b8a8] uppercase">Reservation Reference</span>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.68rem] font-bold border ${statusBadgeClass}">${escapeHtml(resStatus)}</span>
                            <span class="font-bold text-xs text-[#178a52] dark:text-[#8fd0ab]">#${escapeHtml(reservation?.id ?? 'N/A')}</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <span class="block text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Check-in</span>
                            <div class="font-medium text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(reservation?.check_in ?? 'N/A')}</div>
                        </div>
                        <div>
                            <span class="block text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Check-out / Date</span>
                            <div class="font-medium text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(entry?.checked_out_at ? formatDateTime(entry.checked_out_at) : (reservation?.check_out ? formatDateTime(reservation.check_out) : 'N/A'))}</div>
                        </div>
                    </div>
                    <div>
                        <span class="block text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8] mb-1">Main Guest</span>
                        ${primaryGuestMarkup}
                    </div>
                    <div class="text-xs">
                        <span class="block text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Amenities</span>
                        <div class="font-medium text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(amenities)}</div>
                    </div>
                </div>
            `;
        }).join('');

        modalBody.innerHTML = `
            <div class="flex items-center gap-3 pb-3 border-b border-[#e5e9e6] dark:border-[#282c29]">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#178a52] text-white font-bold text-base shadow-sm">
                    ${escapeHtml(customerData.first_name ? customerData.first_name[0] : 'G')}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h4 class="m-0 text-base font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(fullName)}</h4>
                        ${customerData.has_pool_access ? `
                            <span class="inline-flex items-center gap-1 rounded-md bg-[#e0f2fe] dark:bg-[#082f49] px-2 py-0.5 text-[0.68rem] font-bold text-[#0284c7] dark:text-[#38bdf8] border border-[#bae6fd] dark:border-[#0369a1]/40 shadow-2xs" title="Availing Pool Access">
                                <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 16.5c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 20.25c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0" /></svg>
                                Pool Access
                            </span>
                        ` : ''}
                    </div>
                    <p class="m-0 text-xs text-[#5a6b5c] dark:text-[#a8b8a8]">Customer ID #${escapeHtml(customerData.id)} · ${customerData.is_foreigner ? 'Foreigner' : 'Filipino'}</p>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 p-3 rounded-xl bg-[#f8faf9] dark:bg-[#141715] border border-[#e5e9e6] dark:border-[#282c29] text-xs">
                <div>
                    <span class="block text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Age</span>
                    <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(customerData.age ?? 'N/A')}</span>
                </div>
                <div>
                    <span class="block text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Gender</span>
                    <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(customerData.gender ?? 'N/A')}</span>
                </div>
                <div>
                    <span class="block text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Pool Access</span>
                    <span class="font-bold ${customerData.has_pool_access ? 'text-[#0284c7] dark:text-[#38bdf8]' : 'text-[#0d2c1d] dark:text-[#f5f5f0]'}">${customerData.has_pool_access ? '✓ Availing Pool' : 'No'}</span>
                </div>
                <div>
                    <span class="block text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Email</span>
                    <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0] truncate block">${escapeHtml(customerData.email || 'N/A')}</span>
                </div>
                <div>
                    <span class="block text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Phone</span>
                    <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(customerData.phone || 'N/A')}</span>
                </div>
            </div>
            <div class="space-y-3">
                <h5 class="m-0 text-xs font-bold text-[#5a6b5c] dark:text-[#a8b8a8] uppercase">Stay History (${reservations.length})</h5>
                ${reservationDetails || '<p class="text-xs text-[#889b8a]">No reservation history found.</p>'}
            </div>
        `;

        const modalTitle = document.getElementById('guestModalTitle');
        if (modalTitle) modalTitle.textContent = `${fullName} (Customer #${customerData.id})`;
        guestModal.classList.add('is-open');
        guestModal.setAttribute('aria-hidden', 'false');
    };

    const openBulkGroupModal = (bulkKey, fallbackGroupData = null) => {
        const group = (bulkKey ? bulkGroupData?.[bulkKey] : null) || fallbackGroupData;

        if (!group) {
            modalBody.innerHTML = '<p class="text-center py-6 text-xs text-[#889b8a]">Bulk companion group data not found.</p>';
            guestModal.classList.add('is-open');
            guestModal.setAttribute('aria-hidden', 'false');
            return;
        }

        const members = group.members || [];
        const membersHtml = members.length
            ? members.map((m, idx) => `
                <div class="flex items-center justify-between p-2.5 rounded-lg bg-[#f8faf9] dark:bg-[#141715] border border-[#e5e9e6] dark:border-[#282c29] text-xs">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">Member #${idx + 1} (Customer #${escapeHtml(m.customer_id)})</span>
                        ${m.has_pool_access ? `
                            <span class="inline-flex items-center gap-0.5 rounded bg-[#e0f2fe] dark:bg-[#082f49] px-1.5 py-0.2 text-[0.65rem] font-bold text-[#0284c7] dark:text-[#38bdf8] border border-[#bae6fd] dark:border-[#0369a1]/40">
                                Pool Access
                            </span>
                        ` : ''}
                    </div>
                    <span class="text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">${m.checked_out_at ? formatDateTime(m.checked_out_at) : 'Checked Out'}</span>
                </div>
            `).join('')
            : '<p class="text-xs text-[#889b8a]">No individual member records.</p>';

        modalBody.innerHTML = `
            <div class="flex items-center gap-3 pb-3 border-b border-[#e5e9e6] dark:border-[#282c29]">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#178a52] text-white shadow-sm">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" /></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h4 class="m-0 text-base font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(group.name)} (${group.count} Guests)</h4>
                        ${group.has_pool_access ? `
                            <span class="inline-flex items-center gap-1 rounded-md bg-[#e0f2fe] dark:bg-[#082f49] px-2 py-0.5 text-[0.68rem] font-bold text-[#0284c7] dark:text-[#38bdf8] border border-[#bae6fd] dark:border-[#0369a1]/40 shadow-2xs" title="Availing Pool Access">
                                <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 16.5c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 20.25c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0" /></svg>
                                Pool (${group.pool_access_count || group.count}x)
                            </span>
                        ` : ''}
                    </div>
                    <p class="m-0 text-xs text-[#5a6b5c] dark:text-[#a8b8a8]">Reservation #${escapeHtml(group.reservation_id)} · Bulk Companion Group</p>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3 rounded-xl bg-[#f8faf9] dark:bg-[#141715] border border-[#e5e9e6] dark:border-[#282c29] text-xs">
                <div>
                    <span class="block text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Age Group</span>
                    <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(group.age_group)}</span>
                </div>
                <div>
                    <span class="block text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Gender</span>
                    <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(group.gender)}</span>
                </div>
                <div>
                    <span class="block text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Nationality</span>
                    <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(group.nationality)}</span>
                </div>
                <div>
                    <span class="block text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Pool Access</span>
                    <span class="font-bold ${group.has_pool_access ? 'text-[#0284c7] dark:text-[#38bdf8]' : 'text-[#0d2c1d] dark:text-[#f5f5f0]'}">${group.has_pool_access ? `✓ Yes (${group.pool_access_count || group.count}x)` : 'No'}</span>
                </div>
            </div>
            <div class="space-y-2">
                <h5 class="m-0 text-xs font-bold text-[#5a6b5c] dark:text-[#a8b8a8] uppercase">Checked-Out Group Members (${members.length})</h5>
                <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                    ${membersHtml}
                </div>
            </div>
        `;

        const modalTitle = document.getElementById('guestModalTitle');
        if (modalTitle) modalTitle.textContent = `${group.name} (${group.count}x)`;
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
                ? 'cursor-pointer rounded-xl bg-[#178a52] px-3 py-1.5 text-xs font-bold text-white transition-colors hover:bg-[#126e41] border-0 shadow-xs'
                : 'cursor-pointer rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-3 py-1.5 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] transition-colors hover:bg-[#f4f7f5] dark:hover:bg-[#202722] shadow-xs';
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
                span.className = 'px-1 text-xs text-[#889b8a]';
                span.textContent = '…';
                container.appendChild(span);
            } else {
                container.appendChild(buildBtn(p));
            }
        });
    };

    // Guest table rows removed — close buttons still needed for modals
    closeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            guestModal.classList.remove('is-open');
            guestModal.setAttribute('aria-hidden', 'true');
        });
    });

    guestModal?.addEventListener('click', (event) => {
        if (event.target === guestModal || event.target.classList.contains('guest-modal__backdrop')) {
            guestModal.classList.remove('is-open');
            guestModal.setAttribute('aria-hidden', 'true');
        }
    });

    // ========================
    // RESERVATION TABLE LOGIC
    // ========================
    const reservationModal = document.getElementById('reservationModal');
    const reservationModalBody = document.getElementById('reservationModalBody');
    const reservationCloseButtons = document.querySelectorAll('[data-close-reservation-modal="true"]');

    // Move modals to be direct children of <body>. The dashboard layout has
    // ancestor elements (.dash-content / .dash-main) that establish their own
    // CSS stacking context, which caps these fixed-position modals below the
    // sticky header no matter how high their own z-index is set. Re-parenting
    // them to <body> escapes that trap entirely (a standard "portal" pattern).
    [guestModal, reservationModal].forEach((modal) => {
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    });
    const reservationData = window.staffReservationData || {};

    const reservationSearchInput = document.getElementById('reservationSearchInput');
    const reservationStatusFilter = document.getElementById('reservationStatusFilter');
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
    const reservationPerPage = document.getElementById('reservationPerPage');
    const reservationPrevPage = document.getElementById('reservationPrevPage');
    const reservationNextPage = document.getElementById('reservationNextPage');

    const openReservationModal = (reservationId) => {
        const reservation = reservationData[reservationId];

        if (!reservation) {
            reservationModalBody.innerHTML = '<p class="text-center py-6 text-xs text-[#889b8a]">No reservation details available.</p>';
            reservationModal.classList.add('is-open');
            reservationModal.setAttribute('aria-hidden', 'false');
            return;
        }

        const primaryGuest = (reservation.reservation_guests || []).find(g => g.is_primary_guest);
        const companions = (reservation.reservation_guests || []).filter(g => !g.is_primary_guest);

        const statusRaw = (reservation.status || 'Checked Out').trim();
        let statusBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40';
        if (statusRaw.toLowerCase().includes('cancel')) {
            statusBadgeClass = 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/40';
        } else if (statusRaw.toLowerCase().includes('no show') || statusRaw.toLowerCase().includes('noshow')) {
            statusBadgeClass = 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800/50 dark:text-slate-300 dark:border-slate-700';
        }

        const hasPoolAccess = (reservation.pool_access_count > 0) || (parseFloat(reservation.pool_fee || 0) > 0) || (reservation.pool_option === 'with_pool');

        let html = `
            <!-- Header Bar -->
            <div class="flex items-center justify-between p-4 rounded-xl bg-[#f4f7f5] dark:bg-[#141715] border border-[#dbe3de] dark:border-[#282c29]">
                <div>
                    <span class="text-[0.7rem] font-bold uppercase tracking-wider text-[#5a6b5c] dark:text-[#a8b8a8]">Reservation Reference</span>
                    <div class="text-base font-extrabold text-[#0d2c1d] dark:text-[#f5f5f0] font-mono flex items-center gap-2 flex-wrap">
                        <span>#${escapeHtml(reservation.id)} <span class="text-xs font-semibold text-[#889b8a] font-sans">(${escapeHtml(reservation.reservation_type || 'online')})</span></span>
                        ${hasPoolAccess ? `
                            <span class="inline-flex items-center gap-1 rounded-md bg-[#e0f2fe] dark:bg-[#082f49] px-2 py-0.5 text-[0.68rem] font-bold text-[#0284c7] dark:text-[#38bdf8] border border-[#bae6fd] dark:border-[#0369a1]/40 shadow-2xs font-sans" title="Pool Access Included">
                                <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 16.5c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 20.25c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0" /></svg>
                                Pool (${reservation.pool_access_count || 1}x)
                            </span>
                        ` : ''}
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border ${statusBadgeClass}">${escapeHtml(statusRaw)}</span>
            </div>

            <!-- Main Booker / Primary Guest -->
            <div class="p-4 rounded-xl bg-[#f8faf9] dark:bg-[#141715] border border-[#e5e9e6] dark:border-[#282c29] space-y-2">
                <h4 class="m-0 text-xs font-bold text-[#5a6b5c] dark:text-[#a8b8a8] uppercase">Main Booker / Primary Guest</h4>
                <div class="p-3 rounded-lg bg-white dark:bg-[#181b19] border border-[#dbe3de] dark:border-[#282c29]">
                    <div class="font-bold text-sm text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(reservation.booker_name || 'N/A')}</div>
                    <div class="text-[0.75rem] text-[#5a6b5c] dark:text-[#a8b8a8] mt-0.5">Email: ${escapeHtml(reservation.email || 'N/A')} · Phone: ${escapeHtml(reservation.phone || 'N/A')}</div>
                    ${primaryGuest && (primaryGuest.customer || primaryGuest.name) ? `
                        <div class="text-[0.72rem] text-[#5a6b5c] dark:text-[#a8b8a8] mt-1 flex items-center gap-1.5 flex-wrap">
                            <span>Age: ${escapeHtml(primaryGuest.customer?.age || primaryGuest.age || 'N/A')} · Gender: ${escapeHtml(primaryGuest.customer?.gender || primaryGuest.gender || 'N/A')} · ${escapeHtml((primaryGuest.customer?.is_foreigner ?? false) ? 'Foreigner' : 'Filipino')}</span>
                            ${primaryGuest.has_pool_access ? `
                                <span class="inline-flex items-center gap-0.5 rounded bg-[#e0f2fe] dark:bg-[#082f49] px-1.5 py-0.2 text-[0.62rem] font-bold text-[#0284c7] dark:text-[#38bdf8] border border-[#bae6fd] dark:border-[#0369a1]/40" title="Availing Pool Access">
                                    Pool Access
                                </span>
                            ` : ''}
                        </div>
                        <div class="text-[0.72rem] font-medium text-[#178a52] dark:text-[#8fd0ab] mt-1">Checked Out: ${escapeHtml(primaryGuest.checked_out_at ? formatDateTime(primaryGuest.checked_out_at) : (reservation.check_out ? formatDateTime(reservation.check_out) : 'N/A'))}</div>
                    ` : ''}
                </div>
            </div>
        `;

        if (companions.length > 0) {
            const companionGroups = {};
            companions.forEach(c => {
                const cust = c.customer || c;
                const age = cust.age || 'N/A';
                const gender = cust.gender || 'N/A';
                const nationality = (cust.is_foreigner ?? false) ? 'Foreigner' : 'Filipino';
                const isCheckedOut = Boolean(c.checked_out_at);
                const hasPool = Boolean(c.has_pool_access);
                const key = `${age}|${gender}|${nationality}|${isCheckedOut ? 'out' : 'in'}|${hasPool ? 'pool' : 'nopool'}`;

                if (!companionGroups[key]) {
                    companionGroups[key] = { age, gender, nationality, isCheckedOut, hasPool, count: 0 };
                }
                companionGroups[key].count++;
            });

            const groupEntries = Object.entries(companionGroups);
            if (groupEntries.length > 0) {
                html += `
                    <div class="p-4 rounded-xl bg-[#f8faf9] dark:bg-[#141715] border border-[#e5e9e6] dark:border-[#282c29] space-y-2">
                        <h4 class="m-0 text-xs font-bold text-[#5a6b5c] dark:text-[#a8b8a8] uppercase">Companions & Guests (${companions.length})</h4>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            ${groupEntries.map(([key, group]) => `
                                <div class="flex items-center justify-between p-2.5 rounded-lg bg-white dark:bg-[#181b19] border border-[#dbe3de] dark:border-[#282c29] text-xs">
                                    <div class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0] flex items-center gap-1.5 flex-wrap">
                                        <span>${escapeHtml(group.nationality)} · ${escapeHtml(group.gender)} (${escapeHtml(group.age)})</span>
                                        <span class="px-2 py-0.5 text-[0.65rem] font-bold rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">${group.count}x</span>
                                        ${group.hasPool ? `
                                            <span class="inline-flex items-center gap-0.5 rounded bg-[#e0f2fe] dark:bg-[#082f49] px-1.5 py-0.2 text-[0.62rem] font-bold text-[#0284c7] dark:text-[#38bdf8] border border-[#bae6fd] dark:border-[#0369a1]/40">
                                                Pool Access
                                            </span>
                                        ` : ''}
                                    </div>
                                    <span class="text-[0.7rem] text-[#889b8a]">${group.isCheckedOut ? 'Checked Out' : '—'}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }
        }

        if (reservation.reservation_amenities && reservation.reservation_amenities.length > 0) {
            html += `
                <div class="p-4 rounded-xl bg-[#f8faf9] dark:bg-[#141715] border border-[#e5e9e6] dark:border-[#282c29] space-y-2">
                    <h4 class="m-0 text-xs font-bold text-[#5a6b5c] dark:text-[#a8b8a8] uppercase">Reserved Amenities</h4>
                    <div class="space-y-1.5 text-xs">
                        ${reservation.reservation_amenities.map(a => `
                            <div class="flex items-center justify-between p-2.5 rounded-lg bg-white dark:bg-[#181b19] border border-[#dbe3de] dark:border-[#282c29]">
                                <span class="font-medium text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(a.amenity?.amenities_name || a.amenity_name || 'Amenity')} <span class="text-[#889b8a]">(${escapeHtml(a.pricing_type || 'Flat')})</span></span>
                                <span class="font-bold text-emerald-700 dark:text-emerald-300">₱${parseFloat(a.price_at_booking || a.price || 0).toFixed(2)} x ${a.quantity}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        html += `
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3.5 rounded-xl bg-[#f4f7f5] dark:bg-[#141715] border border-[#dbe3de] dark:border-[#282c29] text-xs">
                <div>
                    <span class="block text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Stay Schedule</span>
                    <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(reservation.reservation_date ? formatDateTime(reservation.reservation_date) : 'N/A')}</span>
                </div>
                <div>
                    <span class="block text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Check-Out Date</span>
                    <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(reservation.check_out ? formatDateTime(reservation.check_out) : (reservation.end_date ? formatDateTime(reservation.end_date) : 'N/A'))}</span>
                </div>
                <div>
                    <span class="block text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Total Amount</span>
                    <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">₱${parseFloat(reservation.total_amount || 0).toFixed(2)}</span>
                </div>
                <div>
                    <span class="block text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Amount Paid</span>
                    <span class="font-bold text-emerald-700 dark:text-emerald-300">₱${parseFloat(reservation.amount_paid || 0).toFixed(2)}</span>
                </div>
            </div>
        `;

        reservationModalBody.innerHTML = html;
        const modalTitle = document.getElementById('reservationModalTitle');
        if (modalTitle) modalTitle.textContent = `Reservation #${reservation.id} Archive Details`;
        guestModal.classList.remove('is-open');
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

        reservationTableRows.forEach((row) => row.classList.add('hidden'));
        const start = (reservationPage - 1) * perPage;
        const slice = reservationFilteredRows.slice(start, start + perPage);
        slice.forEach((row) => row.classList.remove('hidden'));

        // Hide companion rows whose parent reservation is off-page
        const visibleIds = new Set(slice.map((row) => row.getAttribute('data-reservation-id')));
        document.querySelectorAll('.companion-row').forEach((row) => {
            const match = /companion-of-(\d+)/.exec(row.className);
            if (!match) return;
            if (visibleIds.has(match[1])) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });

        // Show/hide section group headers based on which types are visible on this page
        const hasWalkIn = slice.some(r => r.getAttribute('data-reservation-type') === 'walk_in');
        const hasOnline = slice.some(r => r.getAttribute('data-reservation-type') === 'online');
        const walkInHeader = document.getElementById('sectionHeaderWalkIn');
        const onlineHeader = document.getElementById('sectionHeaderOnline');
        if (walkInHeader) walkInHeader.style.display = hasWalkIn || total === 0 ? '' : 'none';
        if (onlineHeader) onlineHeader.style.display = hasOnline || total === 0 ? '' : 'none';

        // Empty-state row when filters match nothing
        let emptyRow = document.getElementById('reservationTableEmptyRow');
        if (total === 0) {
            if (!emptyRow && reservationTableBodyEl) {
                emptyRow = document.createElement('tr');
                emptyRow.id = 'reservationTableEmptyRow';
                emptyRow.innerHTML = '<td colspan="8" class="px-4 py-8 text-center text-xs text-[#889b8a]">No reservation archive records match your filters.</td>';
                reservationTableBodyEl.appendChild(emptyRow);
            }
            if (emptyRow) emptyRow.style.display = '';
        } else if (emptyRow) {
            emptyRow.style.display = 'none';
        }

        if (reservationResultsCount) {
            reservationResultsCount.textContent = total === 0
                ? 'Showing 0 of 0 reservations'
                : `Showing ${start + 1} to ${start + slice.length} of ${total} reservations`;
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

    // Which reservation type tab is active ('walk_in' or 'online')
    let currentTypeTab = 'walk_in';

    // Hide static section-header rows — not needed with tab switching
    document.querySelectorAll('.reservation-section-header').forEach(r => r.style.display = 'none');

    // Only show the empty-placeholder row that matches the active tab
    const syncEmptyPlaceholders = () => {
        document.querySelectorAll('.walk-in-empty-placeholder').forEach(r => {
            r.style.display = currentTypeTab === 'walk_in' ? '' : 'none';
        });
        document.querySelectorAll('.online-empty-placeholder').forEach(r => {
            r.style.display = currentTypeTab === 'online' ? '' : 'none';
        });
    };
    syncEmptyPlaceholders();

    // Tab button click handlers
    document.querySelectorAll('.resv-type-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            currentTypeTab = btn.getAttribute('data-resv-type');
            document.querySelectorAll('.resv-type-tab').forEach(b => {
                const active = b.getAttribute('data-resv-type') === currentTypeTab;
                if (active) {
                    b.className = 'resv-type-tab resv-type-tab--active inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-[#178a52] px-4 py-2 text-xs font-bold text-white shadow-sm transition-all';
                } else {
                    b.className = 'resv-type-tab inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-[#dbe3de] bg-white px-4 py-2 text-xs font-bold text-[#0d2c1d] shadow-sm transition-all hover:bg-[#f4f7f5] dark:border-[#282c29] dark:bg-[#181b19] dark:text-[#f5f5f0] dark:hover:bg-[#141715]';
                }
            });
            syncEmptyPlaceholders();
            reservationPage = 1;
            applyReservationFilters();
        });
    });

    const applyReservationFilters = () => {
        const query = reservationSearchInput?.value.trim().toLowerCase() ?? '';
        const statusFilterValue = (reservationStatusFilter?.value ?? 'all').toLowerCase();
        const sortValue = reservationSortSelect?.value ?? 'date-desc';
        const checkOutFromValue = reservationCheckOutFrom?.value ?? '';
        const checkOutToValue = reservationCheckOutTo?.value ?? '';

        const filteredRows = reservationTableRows.filter((row) => {
            const searchText = (row.getAttribute('data-search') || '').toLowerCase();
            const matchesSearch = !query || searchText.includes(query);

            const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();
            let matchesStatus = true;
            if (statusFilterValue !== 'all') {
                if (statusFilterValue === 'no show') {
                    matchesStatus = rowStatus.includes('no show') || rowStatus.includes('noshow');
                } else if (statusFilterValue === 'cancelled') {
                    matchesStatus = rowStatus.includes('cancel');
                } else if (statusFilterValue === 'checked out') {
                    matchesStatus = rowStatus.includes('checked out') || rowStatus.includes('checkedout');
                } else {
                    matchesStatus = rowStatus === statusFilterValue;
                }
            }

            const checkOutDate = row.getAttribute('data-check-out') || '';
            const checkOutDateOnly = checkOutDate.split(' ')[0];
            const matchesCheckOutFrom = !checkOutFromValue || !checkOutDateOnly || checkOutDateOnly >= checkOutFromValue;
            const matchesCheckOutTo = !checkOutToValue || !checkOutDateOnly || checkOutDateOnly <= checkOutToValue;
            const rowType = row.getAttribute('data-reservation-type') ?? '';
            const matchesType = rowType === currentTypeTab;
            return matchesSearch && matchesStatus && matchesCheckOutFrom && matchesCheckOutTo && matchesType;
        });

        filteredRows.sort((left, right) => {
            const leftName = (left.getAttribute('data-booker-name') || '').toLowerCase();
            const rightName = (right.getAttribute('data-booker-name') || '').toLowerCase();
            const leftResId = Number(left.getAttribute('data-reservation-id') || 0);
            const rightResId = Number(right.getAttribute('data-reservation-id') || 0);
            const leftAmount = Number(left.getAttribute('data-amount') || 0);
            const rightAmount = Number(right.getAttribute('data-amount') || 0);
            const leftCheckOut = left.getAttribute('data-check-out') || '';
            const rightCheckOut = right.getAttribute('data-check-out') || '';

            switch (sortValue) {
                case 'date-asc':
                    return leftCheckOut.localeCompare(rightCheckOut);
                case 'res-id-desc':
                    return rightResId - leftResId;
                case 'res-id-asc':
                    return leftResId - rightResId;
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

        updateCountersFromReservations(filteredRows);
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

    reservationModal?.addEventListener('click', (event) => {
        if (event.target === reservationModal || event.target.classList.contains('guest-modal__backdrop')) {
            reservationModal.classList.remove('is-open');
            reservationModal.setAttribute('aria-hidden', 'true');
        }
    });

    [reservationSearchInput, reservationStatusFilter, reservationSortSelect, reservationCheckOutFrom, reservationCheckOutTo].forEach((element) => {
        element?.addEventListener('input', applyReservationFilters);
        element?.addEventListener('change', applyReservationFilters);
    });

    reservationClearButton?.addEventListener('click', () => {
        if (reservationSearchInput) reservationSearchInput.value = '';
        if (reservationStatusFilter) reservationStatusFilter.value = 'all';
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
        const icon = reservationFilterToggle.querySelector('.guest-filter-toggle__icon');
        if (icon) icon.textContent = isExpanded ? '▾' : '▴';
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

    // Companion Row Click -> Open Guest Modal or Bulk Group Modal
    document.querySelectorAll('.companion-row').forEach((row) => {
        row.addEventListener('click', (e) => {
            e.stopPropagation();
            if (row.dataset.bulkGroup === 'true') {
                const bulkKey = row.dataset.bulkKey;
                if (bulkKey && bulkGroupData?.[bulkKey]) {
                    openBulkGroupModal(bulkKey);
                    return;
                }
                let members = [];
                try {
                    members = JSON.parse(row.getAttribute('data-bulk-members') || '[]');
                } catch {
                    members = [];
                }
                const fallbackGroup = {
                    name: row.getAttribute('data-bulk-name') || 'Bulk Companions',
                    count: parseInt(row.getAttribute('data-bulk-count') || String(members.length || 1), 10),
                    reservation_id: row.getAttribute('data-reservation-id') || '',
                    age_group: row.getAttribute('data-bulk-age') || 'N/A',
                    gender: row.getAttribute('data-bulk-gender') || 'N/A',
                    nationality: row.getAttribute('data-bulk-nationality') || 'Filipino',
                    members: members,
                };
                openBulkGroupModal(null, fallbackGroup);
                return;
            }
            const customerId = row.getAttribute('data-customer-id');
            if (customerId) {
                openGuestModal(customerId);
            }
        });
    });

    // Initialize
    applyReservationFilters();
    updateCountersFromReservations(reservationTableRows);
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['staff_records']());