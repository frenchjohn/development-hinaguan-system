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
    // TAB SWITCHING LOGIC
    // =====================
    const tabButtons = Array.from(document.querySelectorAll('.records-tab-btn'));
    const tabSections = Array.from(document.querySelectorAll('[data-tab-content]'));
    let currentActiveTab = 'guests';

    const setActiveTab = (tabName) => {
        currentActiveTab = tabName;
        tabButtons.forEach((button) => {
            const isActive = button.dataset.tab === tabName;
            button.classList.toggle('records-tab-btn--active', isActive);
            if (isActive) {
                button.className = 'records-tab-btn records-tab-btn--active cursor-pointer rounded-full border border-transparent bg-[#178a52] px-6 py-2 text-xs font-bold text-white shadow-sm transition-all hover:bg-[#126e41] focus:outline-none';
            } else {
                button.className = 'records-tab-btn cursor-pointer rounded-full border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-6 py-2 text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] shadow-sm transition-all hover:bg-[#f4f7f5] dark:hover:bg-[#141715] focus:outline-none';
            }
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            button.setAttribute('tabindex', isActive ? '0' : '-1');
        });

        tabSections.forEach((section) => {
            section.hidden = section.dataset.tabContent !== tabName;
        });

        if (tabName === 'guests') {
            updateCountersFromGuests(guestFilteredRows);
        } else {
            updateCountersFromReservations(reservationFilteredRows);
        }
    };

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            setActiveTab(button.dataset.tab);
        });
    });

    // =====================
    // GUEST TABLE LOGIC
    // =====================
    const guestModal = document.getElementById('guestModal');
    const modalBody = document.getElementById('guestModalBody');
    const closeButtons = document.querySelectorAll('[data-close-modal="true"]');
    const guestData = window.staffGuestData || {};
    const bulkGroupData = window.staffBulkGroupData || {};

    const searchInput = document.getElementById('guestSearchInput');
    const guestStatusFilter = document.getElementById('guestStatusFilter');
    const sortSelect = document.getElementById('guestSortSelect');
    const checkOutFrom = document.getElementById('guestCheckOutFrom');
    const checkOutTo = document.getElementById('guestCheckOutTo');
    const showCompanionsCheckbox = document.getElementById('showCompanionsCheckbox');
    const clearButton = document.getElementById('guestFiltersClear');
    const guestResultsCount = document.getElementById('guestResultsCount');
    const guestFilterToggle = document.getElementById('guestFilterToggle');
    const guestFilterPanel = document.getElementById('guestFilterPanel');
    const guestTableBody = document.getElementById('guestTableBody');
    const guestTableRows = Array.from(guestTableBody?.querySelectorAll('.guest-row') ?? []);
    const guestPageNumbers = document.getElementById('guestPageNumbers');
    const guestPageInput = document.getElementById('guestPageInput');
    const guestPerPage = document.getElementById('guestPerPage');
    const guestPrevPage = document.getElementById('guestPrevPage');
    const guestNextPage = document.getElementById('guestNextPage');

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
                <div>
                    <h4 class="m-0 text-base font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(fullName)}</h4>
                    <p class="m-0 text-xs text-[#5a6b5c] dark:text-[#a8b8a8]">Customer ID #${escapeHtml(customerData.id)} · ${customerData.is_foreigner ? 'Foreigner' : 'Filipino'}</p>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3 rounded-xl bg-[#f8faf9] dark:bg-[#141715] border border-[#e5e9e6] dark:border-[#282c29] text-xs">
                <div>
                    <span class="block text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Age</span>
                    <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(customerData.age ?? 'N/A')}</span>
                </div>
                <div>
                    <span class="block text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Gender</span>
                    <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(customerData.gender ?? 'N/A')}</span>
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
                    <span class="font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">Member #${idx + 1} (Customer #${escapeHtml(m.customer_id)})</span>
                    <span class="text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8]">${m.checked_out_at ? formatDateTime(m.checked_out_at) : 'Checked Out'}</span>
                </div>
            `).join('')
            : '<p class="text-xs text-[#889b8a]">No individual member records.</p>';

        modalBody.innerHTML = `
            <div class="flex items-center gap-3 pb-3 border-b border-[#e5e9e6] dark:border-[#282c29]">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#178a52] text-white shadow-sm">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" /></svg>
                </div>
                <div>
                    <h4 class="m-0 text-base font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(group.name)} (${group.count} Guests)</h4>
                    <p class="m-0 text-xs text-[#5a6b5c] dark:text-[#a8b8a8]">Reservation #${escapeHtml(group.reservation_id)} · Bulk Companion Group</p>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-3 p-3 rounded-xl bg-[#f8faf9] dark:bg-[#141715] border border-[#e5e9e6] dark:border-[#282c29] text-xs">
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
                ? 'cursor-pointer rounded-lg bg-[#178a52] px-2.5 py-1 text-xs font-bold text-white transition-colors hover:bg-[#126e41] border-0 shadow-sm'
                : 'cursor-pointer rounded-lg border border-[#dbe3de] dark:border-[#282c29] bg-white dark:bg-[#181b19] px-2.5 py-1 text-xs font-semibold text-[#0d2c1d] dark:text-[#f5f5f0] transition-colors hover:bg-[#f4f7f5] dark:hover:bg-[#141715] shadow-sm';
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

    // ---- Guest pagination state ----
    const guestTableBodyEl = document.getElementById('guestTableBody');
    let guestPage = 1;
    let guestFilteredRows = [];

    const renderGuestPagination = () => {
        const perPage = Number(guestPerPage?.value || 10);
        const total = guestFilteredRows.length;
        const totalPages = Math.max(1, Math.ceil(total / perPage));
        guestPage = Math.min(Math.max(1, guestPage), totalPages);

        guestTableRows.forEach((row) => row.classList.add('hidden'));
        const start = (guestPage - 1) * perPage;
        const slice = guestFilteredRows.slice(start, start + perPage);
        slice.forEach((row) => row.classList.remove('hidden'));

        // Empty-state row when filters match nothing
        let emptyRow = document.getElementById('guestTableEmptyRow');
        if (total === 0) {
            if (!emptyRow && guestTableBodyEl) {
                emptyRow = document.createElement('tr');
                emptyRow.id = 'guestTableEmptyRow';
                emptyRow.innerHTML = '<td colspan="9" class="px-4 py-8 text-center text-xs text-[#889b8a]">No guest records match your filters.</td>';
                guestTableBodyEl.appendChild(emptyRow);
            }
            if (emptyRow) emptyRow.style.display = '';
        } else if (emptyRow) {
            emptyRow.style.display = 'none';
        }

        if (guestResultsCount) {
            guestResultsCount.textContent = total === 0
                ? 'Showing 0 of 0 records'
                : `Showing ${start + 1} to ${start + slice.length} of ${total} records`;
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
        const statusFilterValue = (guestStatusFilter?.value ?? 'all').toLowerCase();
        const sortValue = sortSelect?.value ?? 'checkout-desc';
        const checkOutFromValue = checkOutFrom?.value ?? '';
        const checkOutToValue = checkOutTo?.value ?? '';
        const showCompanions = showCompanionsCheckbox?.checked ?? false;

        const filteredRows = guestTableRows.filter((row) => {
            // Default: Auto-hide companions unless showCompanions checkbox is checked
            if (!showCompanions) {
                const isPrimary = row.getAttribute('data-is-primary') === 'true';
                if (!isPrimary) return false;
            }

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

            const checkedOutDate = row.getAttribute('data-checked-out') || '';
            const checkedOutDateOnly = checkedOutDate.split(' ')[0];
            const matchesCheckOutFrom = !checkOutFromValue || !checkedOutDateOnly || checkedOutDateOnly >= checkOutFromValue;
            const matchesCheckOutTo = !checkOutToValue || !checkedOutDateOnly || checkedOutDateOnly <= checkOutToValue;
            return matchesSearch && matchesStatus && matchesCheckOutFrom && matchesCheckOutTo;
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

        if (currentActiveTab === 'guests') {
            updateCountersFromGuests(filteredRows);
        }
    };

    // Sort arrows on the table headers
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

    guestModal?.addEventListener('click', (event) => {
        if (event.target === guestModal || event.target.classList.contains('guest-modal__backdrop')) {
            guestModal.classList.remove('is-open');
            guestModal.setAttribute('aria-hidden', 'true');
        }
    });

    [searchInput, guestStatusFilter, sortSelect, checkOutFrom, checkOutTo, showCompanionsCheckbox].forEach((element) => {
        element?.addEventListener('input', applyGuestFilters);
        element?.addEventListener('change', applyGuestFilters);
    });

    clearButton?.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        if (guestStatusFilter) guestStatusFilter.value = 'all';
        if (sortSelect) sortSelect.value = 'checkout-desc';
        if (checkOutFrom) checkOutFrom.value = '';
        if (checkOutTo) checkOutTo.value = '';
        if (showCompanionsCheckbox) showCompanionsCheckbox.checked = false;
        applyGuestFilters();
    });

    guestFilterToggle?.addEventListener('click', () => {
        if (!guestFilterPanel) return;
        const isExpanded = guestFilterToggle.getAttribute('aria-expanded') === 'true';
        guestFilterPanel.hidden = isExpanded;
        guestFilterToggle.setAttribute('aria-expanded', String(!isExpanded));
        const icon = guestFilterToggle.querySelector('.guest-filter-toggle__icon');
        if (icon) icon.textContent = isExpanded ? '▾' : '▴';
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

        let html = `
            <!-- Header Bar -->
            <div class="flex items-center justify-between p-4 rounded-xl bg-[#f4f7f5] dark:bg-[#141715] border border-[#dbe3de] dark:border-[#282c29]">
                <div>
                    <span class="text-[0.7rem] font-bold uppercase tracking-wider text-[#5a6b5c] dark:text-[#a8b8a8]">Reservation Reference</span>
                    <div class="text-base font-extrabold text-[#0d2c1d] dark:text-[#f5f5f0] font-mono">#${escapeHtml(reservation.id)} <span class="text-xs font-semibold text-[#889b8a] ml-1 font-sans">(${escapeHtml(reservation.reservation_type || 'online')})</span></div>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border ${statusBadgeClass}">${escapeHtml(statusRaw)}</span>
            </div>

            <!-- Main Booker / Primary Guest -->
            <div class="p-4 rounded-xl bg-[#f8faf9] dark:bg-[#141715] border border-[#e5e9e6] dark:border-[#282c29] space-y-2">
                <h4 class="m-0 text-xs font-bold text-[#5a6b5c] dark:text-[#a8b8a8] uppercase">Main Booker / Primary Guest</h4>
                <div class="p-3 rounded-lg bg-white dark:bg-[#181b19] border border-[#dbe3de] dark:border-[#282c29]">
                    <div class="font-bold text-sm text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(reservation.booker_name || 'N/A')}</div>
                    <div class="text-[0.75rem] text-[#5a6b5c] dark:text-[#a8b8a8] mt-0.5">Email: ${escapeHtml(reservation.email || 'N/A')} · Phone: ${escapeHtml(reservation.phone || 'N/A')}</div>
                    ${primaryGuest && primaryGuest.customer ? `
                        <div class="text-[0.72rem] text-[#5a6b5c] dark:text-[#a8b8a8] mt-1">Age: ${escapeHtml(primaryGuest.customer.age || 'N/A')} · Gender: ${escapeHtml(primaryGuest.customer.gender || 'N/A')} · ${escapeHtml(primaryGuest.customer.is_foreigner ? 'Foreigner' : 'Filipino')}</div>
                        <div class="text-[0.72rem] font-medium text-[#178a52] dark:text-[#8fd0ab] mt-1">Checked Out: ${escapeHtml(primaryGuest.checked_out_at ? formatDateTime(primaryGuest.checked_out_at) : (reservation.check_out ? formatDateTime(reservation.check_out) : 'N/A'))}</div>
                    ` : ''}
                </div>
            </div>
        `;

        if (companions.length > 0) {
            const companionGroups = {};
            companions.forEach(c => {
                if (!c.customer) return;
                const age = c.customer.age || 'N/A';
                const gender = c.customer.gender || 'N/A';
                const nationality = c.customer.is_foreigner ? 'Foreigner' : 'Filipino';
                const isCheckedOut = Boolean(c.checked_out_at);
                const key = `${age}|${gender}|${nationality}|${isCheckedOut ? 'out' : 'in'}`;

                if (!companionGroups[key]) {
                    companionGroups[key] = { age, gender, nationality, isCheckedOut, count: 0 };
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
                                    <div class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(group.nationality)} · ${escapeHtml(group.gender)} (${escapeHtml(group.age)}) <span class="px-2 py-0.5 text-[0.65rem] font-bold rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 ml-1.5">${group.count}x</span></div>
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
            return matchesSearch && matchesStatus && matchesCheckOutFrom && matchesCheckOutTo;
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

        if (currentActiveTab === 'reservations') {
            updateCountersFromReservations(filteredRows);
        }
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

    // Initialize Default State
    applyGuestFilters();
    applyReservationFilters();
    setActiveTab('guests');
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['staff_records']());