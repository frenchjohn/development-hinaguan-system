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

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

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

    const formatGroupCheckOut = (members, fallbackCheckout = null) => {
        if (!members || members.length === 0) {
            return fallbackCheckout ? formatDateTime(fallbackCheckout) : 'Completed at checkout';
        }
        const counts = {};
        members.forEach(m => {
            const raw = m.checked_out_at || fallbackCheckout;
            const formatted = raw ? formatDateTime(raw) : 'Completed at checkout';
            counts[formatted] = (counts[formatted] || 0) + 1;
        });
        const entries = Object.entries(counts);
        if (entries.length <= 1) {
            return entries.length === 1 ? entries[0][0] : (fallbackCheckout ? formatDateTime(fallbackCheckout) : 'Completed at checkout');
        }
        return entries.map(([dateStr, count]) => `${count}x (${dateStr})`).join(', ');
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
        const groupCheckoutSummary = formatGroupCheckOut(members, group.checked_out_at);
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
            <div class="p-3.5 rounded-xl bg-[#f4f7f5] dark:bg-[#141715] border border-[#dbe3de] dark:border-[#282c29] flex items-center justify-between gap-3 text-xs flex-wrap">
                <div class="flex items-center gap-2 text-[#5a6b5c] dark:text-[#a8b8a8]">
                    <svg class="h-4 w-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                    <span class="font-bold uppercase tracking-wider text-[0.7rem]">Group Check-Out:</span>
                </div>
                <div class="font-bold text-xs text-[#0d2c1d] dark:text-[#f5f5f0]">
                    ${escapeHtml(groupCheckoutSummary)}
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

    const reopenConfirmModal = document.getElementById('reopenConfirmModal');
    const reopenSuccessModal = document.getElementById('reopenSuccessModal');
    let activeReopeningRes = null;

    // Move modals to be direct children of <body>. The dashboard layout has
    // ancestor elements (.dash-content / .dash-main) that establish their own
    // CSS stacking context, which caps these fixed-position modals below the
    // sticky header no matter how high their own z-index is set. Re-parenting
    // them to <body> escapes that trap entirely (a standard "portal" pattern).
    [guestModal, reservationModal, reopenConfirmModal, reopenSuccessModal].forEach((modal) => {
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    });

    // Close listeners for Reopen Confirm Modal
    const closeReopenConfirmModal = () => {
        if (!reopenConfirmModal) return;
        reopenConfirmModal.classList.remove('is-open');
        reopenConfirmModal.setAttribute('aria-hidden', 'true');
        const errBox = document.getElementById('reopenConfirmError');
        if (errBox) {
            errBox.textContent = '';
            errBox.classList.add('hidden');
        }
    };
    document.querySelectorAll('[data-close-reopen-confirm="true"]').forEach(btn => btn.addEventListener('click', closeReopenConfirmModal));
    reopenConfirmModal?.addEventListener('click', (e) => {
        if (e.target === reopenConfirmModal || e.target.hasAttribute('data-close-reopen-confirm') || e.target.classList.contains('guest-modal__backdrop')) {
            closeReopenConfirmModal();
        }
    });

    // Close listeners for Reopen Success Modal
    const closeReopenSuccessModal = () => {
        if (!reopenSuccessModal) return;
        reopenSuccessModal.classList.remove('is-open');
        reopenSuccessModal.setAttribute('aria-hidden', 'true');
    };
    document.querySelectorAll('[data-close-reopen-success="true"]').forEach(btn => btn.addEventListener('click', closeReopenSuccessModal));
    reopenSuccessModal?.addEventListener('click', (e) => {
        if (e.target === reopenSuccessModal || e.target.hasAttribute('data-close-reopen-success') || e.target.classList.contains('guest-modal__backdrop')) {
            closeReopenSuccessModal();
        }
    });

    // Confirm Reopen Action Submission Listener
    const confirmReopenActionBtn = document.getElementById('confirmReopenActionBtn');
    confirmReopenActionBtn?.addEventListener('click', async () => {
        if (!activeReopeningRes) return;
        const resId = activeReopeningRes.id;
        const origContent = confirmReopenActionBtn.innerHTML;
        const errBox = document.getElementById('reopenConfirmError');

        confirmReopenActionBtn.disabled = true;
        confirmReopenActionBtn.innerHTML = `
            <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
            <span>Reopening...</span>
        `;

        try {
            const response = await fetch(`/staff/reservations/${resId}/reopen`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(data.message || 'Failed to reopen reservation.');
            }

            // Close Confirm Modal and Details Modal
            closeReopenConfirmModal();
            reservationModal.classList.remove('is-open');
            reservationModal.setAttribute('aria-hidden', 'true');

            // Remove reservation row & companion rows from records table since it is now Pending
            const row = document.querySelector(`tr.reservation-row[data-reservation-id="${resId}"]`);
            if (row) {
                const companions = document.querySelectorAll(`.companion-of-${resId}`);
                companions.forEach(c => c.remove());
                const rowIndex = reservationTableRows.indexOf(row);
                if (rowIndex > -1) {
                    reservationTableRows.splice(rowIndex, 1);
                }
                row.remove();
            }

            applyReservationFilters();
            updateCountersFromReservations(reservationTableRows);

            // Open Success Modal
            const successMsgEl = document.getElementById('reopenSuccessMessage');
            if (successMsgEl) {
                successMsgEl.textContent = data.message || `Reservation #${resId} has been returned to Pending status and is now listed on the active Staff Reservations page.`;
            }
            if (reopenSuccessModal) {
                if (reopenSuccessModal.parentElement !== document.body) {
                    document.body.appendChild(reopenSuccessModal);
                }
                reopenSuccessModal.style.zIndex = '1500';
                reopenSuccessModal.classList.add('is-open');
                reopenSuccessModal.setAttribute('aria-hidden', 'false');
            }
        } catch (err) {
            if (errBox) {
                errBox.textContent = err.message || 'An error occurred while reopening the reservation.';
                errBox.classList.remove('hidden');
            } else {
                showNoRecordsModal(err.message || 'An error occurred while reopening the reservation.', 'Error');
            }
        } finally {
            confirmReopenActionBtn.disabled = false;
            confirmReopenActionBtn.innerHTML = origContent;
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

        const modalTitle = document.getElementById('reservationModalTitle');
        const modalIdBadge = document.getElementById('reservationModalIdBadge');
        const modalStatusBadge = document.getElementById('reservationModalStatusBadge');
        const modalReopenBtn = document.getElementById('modalReopenBtn');

        if (modalReopenBtn) {
            modalReopenBtn.classList.add('hidden');
            modalReopenBtn.style.display = 'none';
            modalReopenBtn.onclick = null;
        }

        if (!reservation) {
            if (modalIdBadge) modalIdBadge.textContent = `#${reservationId || 'N/A'}`;
            if (modalTitle) modalTitle.textContent = 'Reservation Details';
            if (modalStatusBadge) modalStatusBadge.className = 'hidden';
            reservationModalBody.innerHTML = '<p class="text-center py-8 text-xs text-[#889b8a]">No detailed record found for this reservation.</p>';
            reservationModal.classList.add('is-open');
            reservationModal.setAttribute('aria-hidden', 'false');
            return;
        }

        const primaryGuest = (reservation.reservation_guests || []).find(g => g.is_primary_guest);
        const companions = (reservation.reservation_guests || []).filter(g => !g.is_primary_guest);
        const allGuests = reservation.reservation_guests || [];
        const amenities = reservation.reservation_amenities || [];
        const charges = reservation.reservation_charges || [];

        const statusRaw = (reservation.status || 'Checked Out').trim();
        let statusBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40';
        if (statusRaw.toLowerCase().includes('cancel')) {
            statusBadgeClass = 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/40';
        } else if (statusRaw.toLowerCase().includes('no show') || statusRaw.toLowerCase().includes('noshow')) {
            statusBadgeClass = 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800/50 dark:text-slate-300 dark:border-slate-700';
        }

        if (modalIdBadge) modalIdBadge.textContent = `#${reservation.id}`;
        if (modalTitle) modalTitle.textContent = `${reservation.booker_name || 'Reservation Record'}`;
        if (modalStatusBadge) {
            modalStatusBadge.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border ${statusBadgeClass}`;
            modalStatusBadge.textContent = statusRaw;
        }

        const poolAccessCount = parseInt(reservation.pool_access_count || 0, 10);
        const poolFee = parseFloat(reservation.pool_fee || 0);
        const hasPoolAccess = (poolAccessCount > 0) || (poolFee > 0) || (reservation.pool_option === 'with_pool');

        // Post-checkout / additional charges total
        const chargesTotal = charges.reduce((sum, c) => sum + (parseFloat(c.amount) || 0), 0);
        const hasCharges = charges.length > 0;

        // Amenities total
        const amenitiesTotal = amenities.reduce((sum, a) => {
            const price = parseFloat(a.subtotal || (parseFloat(a.price || a.price_at_booking || 0) * parseInt(a.quantity || 1, 10))) || 0;
            return sum + price;
        }, 0);

        // Entrance fee total
        const entranceFeeTotal = parseFloat(reservation.entrance_fee?.total_entrance_fee || 0);
        const baseEntranceFee = parseFloat(reservation.entrance_fee?.base_entrance_fee || 0);
        const addHeadFee = parseFloat(reservation.entrance_fee?.additional_guest_fee || 0);

        // Grand totals
        const totalAmount = parseFloat(reservation.total_amount || 0);
        const amountPaid = parseFloat(reservation.amount_paid || 0);
        const remainingBal = parseFloat(reservation.remaining_balance ?? (totalAmount - amountPaid));

        // Format dates helper
        const formatStayDate = (d) => d ? formatDateTime(d) : 'N/A';

        const checkInDisplay = formatStayDate(reservation.check_in || reservation.reservation_date);
        const checkOutDisplay = formatStayDate(reservation.check_out || reservation.end_date);

        const leadGuests = allGuests.filter(g => g.is_primary_guest);
        const companionGuestsList = allGuests.filter(g => !g.is_primary_guest);

        // Companions checkout summary for the group
        const companionsCheckoutSummary = reservation.companions_checkout_summary ||
            (companionGuestsList.length > 0 ? formatGroupCheckOut(companionGuestsList, reservation.check_out) : null);

        // Check if there are bulk companion groups
        const bulkGroups = (reservation.bulk_groups && reservation.bulk_groups.length > 0)
            ? reservation.bulk_groups
            : (() => {
                const bGroups = {};
                companionGuestsList.forEach(g => {
                    const nameLower = (g.name || g.first_name || '').toLowerCase();
                    const isBulk = nameLower.startsWith('bulk') || nameLower.includes('companion');
                    if (isBulk) {
                        const key = `${g.age || 'N/A'}-${g.gender || 'N/A'}-${g.is_foreigner ? 'F' : 'P'}`;
                        if (!bGroups[key]) {
                            bGroups[key] = {
                                name: 'Bulk Companions',
                                count: 0,
                                age_group: g.age || 'N/A',
                                gender: g.gender || 'N/A',
                                nationality: g.is_foreigner ? 'Foreigner' : 'Filipino',
                                has_pool_access: false,
                                members: [],
                            };
                        }
                        bGroups[key].count++;
                        if (g.has_pool_access) bGroups[key].has_pool_access = true;
                        bGroups[key].members.push(g);
                    }
                });
                return Object.values(bGroups);
            })();

        const regularCompanions = companionGuestsList.filter(g => {
            const nameLower = (g.name || g.first_name || '').toLowerCase();
            return !nameLower.startsWith('bulk') && !nameLower.includes('companion');
        });

        let html = `
            <!-- Section Switcher Tabs -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 w-full shrink-0 border-b border-[#e8eee9] dark:border-[#282c29] pb-3.5">
                <button type="button" class="resv-modal-tab active inline-flex items-center justify-center gap-1.5 cursor-pointer rounded-xl bg-[#178a52] text-white shadow-sm py-2 px-2.5 text-xs font-bold transition-all border border-transparent" data-target-pane="paneOverview">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                    <span>Overview</span>
                </button>
                <button type="button" class="resv-modal-tab inline-flex items-center justify-center gap-1.5 cursor-pointer rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-[#f8faf9] dark:bg-[#141715] hover:bg-[#eef4f0] dark:hover:bg-[#1f2621] text-[#5a6b5c] dark:text-[#a8b8a8] hover:text-[#0d2c1d] dark:hover:text-white py-2 px-2.5 text-xs font-bold transition-all" data-target-pane="paneGuests">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                    <span>Guests (${allGuests.length})</span>
                </button>
                <button type="button" class="resv-modal-tab inline-flex items-center justify-center gap-1.5 cursor-pointer rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-[#f8faf9] dark:bg-[#141715] hover:bg-[#eef4f0] dark:hover:bg-[#1f2621] text-[#5a6b5c] dark:text-[#a8b8a8] hover:text-[#0d2c1d] dark:hover:text-white py-2 px-2.5 text-xs font-bold transition-all" data-target-pane="paneAmenities">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                    <span>Amenities (${amenities.length})</span>
                </button>
                <button type="button" class="resv-modal-tab inline-flex items-center justify-center gap-1.5 cursor-pointer rounded-xl border border-[#dbe3de] dark:border-[#282c29] bg-[#f8faf9] dark:bg-[#141715] hover:bg-[#eef4f0] dark:hover:bg-[#1f2621] text-[#5a6b5c] dark:text-[#a8b8a8] hover:text-[#0d2c1d] dark:hover:text-white py-2 px-2.5 text-xs font-bold transition-all" data-target-pane="paneBilling">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                    <span>Billing & Charges</span>
                    ${hasCharges ? '<span class="inline-block h-2 w-2 shrink-0 rounded-full bg-amber-500 animate-pulse" title="Has post-checkout charges"></span>' : ''}
                </button>
            </div>

            <!-- Tab Panes Container -->
            <div class="resv-modal-panes space-y-4">
                <!-- ════════════════════════════════════════════════════════ -->
                <!-- PANE 1: OVERVIEW & STAY                                 -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div id="paneOverview" class="resv-modal-pane space-y-4">
                    <!-- Key Information 2-Card Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <!-- Booker Card -->
                        <div class="p-4 rounded-xl bg-[#f8faf9] dark:bg-[#141715] border border-[#dbe3de] dark:border-[#282c29] space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[0.7rem] font-bold uppercase tracking-wider text-[#5a6b5c] dark:text-[#a8b8a8]">Booker Details</span>
                                <span class="px-2 py-0.5 rounded-md text-[0.68rem] font-bold capitalize bg-[#e8eee9] dark:bg-[#202722] text-[#2c5f3e] dark:text-[#8fd0ab]">${escapeHtml(reservation.reservation_type || 'online')} Booking</span>
                            </div>
                            <div>
                                <div class="text-base font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(reservation.booker_name || 'N/A')}</div>
                                <div class="text-xs text-[#5a6b5c] dark:text-[#a8b8a8] mt-0.5">${escapeHtml(reservation.phone || 'No phone')} · ${escapeHtml(reservation.email || 'No email')}</div>
                            </div>
                            <div class="pt-2 border-t border-[#e8eee9] dark:border-[#282c29] flex items-center justify-between text-[0.72rem] text-[#5a6b5c] dark:text-[#a8b8a8]">
                                <span>Total Party Size:</span>
                                <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${reservation.number_of_guests || allGuests.length || 1} Guests</span>
                            </div>
                        </div>

                        <!-- Schedule Card -->
                        <div class="p-4 rounded-xl bg-[#f8faf9] dark:bg-[#141715] border border-[#dbe3de] dark:border-[#282c29] space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[0.7rem] font-bold uppercase tracking-wider text-[#5a6b5c] dark:text-[#a8b8a8]">Stay Timestamps</span>
                                <span class="px-2 py-0.5 rounded-md text-[0.68rem] font-bold bg-[#e8eee9] dark:bg-[#202722] text-[#5a6b5c] dark:text-[#a8b8a8]">${escapeHtml(reservation.start_slot || 'Daytime')}</span>
                            </div>
                            <div class="space-y-1.5">
                                <div class="flex items-start gap-2">
                                    <svg class="h-4 w-4 text-emerald-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                                    <div>
                                        <span class="block text-[0.68rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Check-In Date & Time</span>
                                        <span class="font-bold text-xs text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(checkInDisplay)}</span>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2">
                                    <svg class="h-4 w-4 text-rose-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                                    <div>
                                        <span class="block text-[0.68rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Check-Out Date & Time</span>
                                        <span class="font-bold text-xs text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(checkOutDisplay)}</span>
                                    </div>
                                </div>
                                ${companionsCheckoutSummary ? `
                                    <div class="flex items-start gap-2 pt-1.5 border-t border-[#e8eee9] dark:border-[#282c29]">
                                        <svg class="h-4 w-4 text-amber-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                                        <div>
                                            <span class="block text-[0.68rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Companions Check-Out</span>
                                            <span class="font-bold text-xs text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(companionsCheckoutSummary)}</span>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>

                    <!-- Access & Admission Summary Banner -->
                    <div class="p-4 rounded-xl ${hasPoolAccess ? 'bg-[#f0f9ff] dark:bg-[#082f49]/30 border border-[#bae6fd] dark:border-[#0369a1]/40' : 'bg-[#f4f7f5] dark:bg-[#141715] border border-[#dbe3de] dark:border-[#282c29]'} flex items-center justify-between gap-3 flex-wrap">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${hasPoolAccess ? 'bg-[#0284c7] text-white' : 'bg-[#178a52] text-white'}">
                                ${hasPoolAccess ? `
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 16.5c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 20.25c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0" /></svg>
                                ` : `
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                `}
                            </div>
                            <div>
                                <div class="font-bold text-xs ${hasPoolAccess ? 'text-[#0369a1] dark:text-[#38bdf8]' : 'text-[#0d2c1d] dark:text-[#f5f5f0]'}">
                                    ${hasPoolAccess ? `Pool Access Included (${poolAccessCount} passes)` : 'Standard Park Entrance (No Pool Access)'}
                                </div>
                                <div class="text-[0.72rem] text-[#5a6b5c] dark:text-[#a8b8a8] mt-0.5">
                                    Admission Type: ${escapeHtml(reservation.entrance_fee?.pricing_type || 'Standard')} · ${hasPoolAccess ? `₱${poolFee.toFixed(2)} total pool charge` : 'Eco-park entrance privilege'}
                                </div>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold ${hasPoolAccess ? 'bg-[#0284c7]/10 text-[#0284c7] dark:bg-[#38bdf8]/15 dark:text-[#38bdf8]' : 'bg-[#178a52]/10 text-[#178a52] dark:bg-[#8fd0ab]/15 dark:text-[#8fd0ab]'}">
                            ${hasPoolAccess ? 'Pool Access Valid' : 'Park Admission Valid'}
                        </span>
                    </div>

                    <!-- Quick Ledger 4-Column Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3.5 rounded-xl bg-[#f4f7f5] dark:bg-[#141715] border border-[#dbe3de] dark:border-[#282c29] text-xs">
                        <div>
                            <span class="block text-[0.68rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Base Booking</span>
                            <span class="font-bold text-sm text-[#0d2c1d] dark:text-[#f5f5f0]">₱${totalAmount.toFixed(2)}</span>
                        </div>
                        <div>
                            <span class="block text-[0.68rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Total Paid</span>
                            <span class="font-bold text-sm text-emerald-700 dark:text-emerald-400">₱${amountPaid.toFixed(2)}</span>
                        </div>
                        <div>
                            <span class="block text-[0.68rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Post-Checkout Fees</span>
                            <span class="font-bold text-sm ${chargesTotal > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-[#889b8a]'}">₱${chargesTotal.toFixed(2)}</span>
                        </div>
                        <div>
                            <span class="block text-[0.68rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Remaining Balance</span>
                            <span class="font-bold text-sm ${remainingBal > 0 ? 'text-rose-700 dark:text-rose-400' : 'text-emerald-700 dark:text-emerald-400'}">₱${remainingBal.toFixed(2)}</span>
                        </div>
                    </div>
                </div>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- PANE 2: GUESTS & CHECK-IN / CHECK-OUT                   -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div id="paneGuests" class="resv-modal-pane space-y-3 hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#5a6b5c] dark:text-[#a8b8a8]">Guest List & Visit Timeline (${allGuests.length})</span>
                        <span class="text-[0.72rem] text-[#718774] dark:text-[#889b8a]">Individual check-in & check-out records</span>
                    </div>

                    ${(companionsCheckoutSummary && companionGuestsList.length > 1) ? `
                        <div class="p-3 rounded-xl bg-[#f0f7f3] dark:bg-[#142319] border border-emerald-200/70 dark:border-emerald-800/40 flex items-center justify-between gap-2 flex-wrap text-xs">
                            <div class="flex items-center gap-2 text-emerald-800 dark:text-emerald-300">
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                                <span class="font-bold text-[0.72rem] uppercase tracking-wider">Companion Group Check-Out</span>
                            </div>
                            <span class="font-bold text-xs text-emerald-900 dark:text-emerald-200">${escapeHtml(companionsCheckoutSummary)}</span>
                        </div>
                    ` : ''}

                    <div class="space-y-2.5 max-h-[440px] overflow-y-auto pr-1">
                        ${allGuests.length === 0 ? `
                            <div class="p-6 text-center text-xs text-[#889b8a] rounded-xl bg-[#f8faf9] dark:bg-[#141715] border border-[#dbe3de] dark:border-[#282c29]">
                                No guest entries recorded for this reservation.
                            </div>
                        ` : `
                            <!-- Lead Guests / Primary Booker -->
                            ${leadGuests.map((g, index) => {
                                const guestCheckIn = formatStayDate(g.check_in || reservation.check_in || reservation.reservation_date);
                                const guestCheckOut = g.checked_out_at ? formatDateTime(g.checked_out_at) : (reservation.check_out ? formatDateTime(reservation.check_out) : 'Completed at checkout');
                                const guestHasPool = Boolean(g.has_pool_access);

                                return `
                                    <div class="p-3.5 rounded-xl bg-[#f8faf9] dark:bg-[#141715] border border-emerald-600/40 dark:border-emerald-600/30 space-y-2.5">
                                        <div class="flex items-start justify-between gap-2 flex-wrap">
                                            <div class="flex items-center gap-2.5">
                                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#178a52] text-white font-bold text-xs">
                                                    ★
                                                </span>
                                                <div>
                                                    <div class="font-bold text-xs text-[#0d2c1d] dark:text-[#f5f5f0] flex items-center gap-2 flex-wrap">
                                                        <span>${escapeHtml(g.name || 'Primary Booker')}</span>
                                                        <span class="px-2 py-0.5 rounded-md text-[0.65rem] font-bold bg-[#178a52]/15 text-[#178a52] dark:bg-[#8fd0ab]/20 dark:text-[#8fd0ab]">Primary Booker</span>
                                                    </div>
                                                    <div class="text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8] mt-0.5">
                                                        Age: ${escapeHtml(g.age || 'N/A')} · Gender: ${escapeHtml(g.gender || 'N/A')} · ${g.is_foreigner ? 'Foreigner' : 'Filipino'}
                                                        ${(g.phone || g.email) ? ` · ${escapeHtml(g.phone || g.email)}` : ''}
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                ${guestHasPool ? `
                                                    <span class="inline-flex items-center gap-1 rounded-md bg-[#e0f2fe] dark:bg-[#082f49] px-2 py-0.5 text-[0.65rem] font-bold text-[#0284c7] dark:text-[#38bdf8] border border-[#bae6fd] dark:border-[#0369a1]/40">
                                                        <svg class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 16.5c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 20.25c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0" /></svg>
                                                        Pool Access
                                                    </span>
                                                ` : `
                                                    <span class="inline-flex items-center rounded-md bg-[#f0f4f1] dark:bg-[#202722] px-2 py-0.5 text-[0.65rem] font-medium text-[#5a6b5c] dark:text-[#a8b8a8]">
                                                        Entrance Only
                                                    </span>
                                                `}
                                            </div>
                                        </div>

                                        <!-- Guest Specific Check-in & Check-out Timestamps -->
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2 border-t border-[#e8eee9] dark:border-[#282c29] text-[0.72rem]">
                                            <div class="flex items-center gap-1.5 text-[#5a6b5c] dark:text-[#a8b8a8]">
                                                <svg class="h-3.5 w-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                                                <span class="font-medium">Check-In:</span>
                                                <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(guestCheckIn)}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5 text-[#5a6b5c] dark:text-[#a8b8a8]">
                                                <svg class="h-3.5 w-3.5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                                                <span class="font-medium">Check-Out:</span>
                                                <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(guestCheckOut)}</span>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }).join('')}

                            <!-- Bulk Companion Groups -->
                            ${bulkGroups.map((bg, bgIdx) => {
                                const bgCheckIn = formatStayDate(bg.members?.[0]?.check_in || reservation.check_in);
                                const bgCheckOut = bg.formatted_checkout || formatGroupCheckOut(bg.members, reservation.check_out);

                                return `
                                    <div class="p-3.5 rounded-xl bg-[#f8faf9] dark:bg-[#141715] border border-emerald-600/30 dark:border-emerald-700/30 space-y-2.5">
                                        <div class="flex items-start justify-between gap-2 flex-wrap">
                                            <div class="flex items-center gap-2.5">
                                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white font-bold text-xs" title="Bulk Companion Group">
                                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" /></svg>
                                                </span>
                                                <div>
                                                    <div class="font-bold text-xs text-[#0d2c1d] dark:text-[#f5f5f0] flex items-center gap-2 flex-wrap">
                                                        <span>${escapeHtml(bg.name || 'Bulk Companions')}</span>
                                                        <span class="px-2 py-0.5 rounded-full text-[0.65rem] font-bold bg-[#e0eae2] text-[#2c5f3e] dark:bg-[#1a3324] dark:text-[#8fd0ab]">${bg.count}x</span>
                                                    </div>
                                                    <div class="text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8] mt-0.5">
                                                        Age: ${escapeHtml(bg.age_group || 'N/A')} · Gender: ${escapeHtml(bg.gender || 'N/A')} · ${escapeHtml(bg.nationality || 'Filipino')}
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                ${bg.has_pool_access ? `
                                                    <span class="inline-flex items-center gap-1 rounded-md bg-[#e0f2fe] dark:bg-[#082f49] px-2 py-0.5 text-[0.65rem] font-bold text-[#0284c7] dark:text-[#38bdf8] border border-[#bae6fd] dark:border-[#0369a1]/40">
                                                        <svg class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 16.5c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 20.25c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0" /></svg>
                                                        Pool Access (${bg.pool_access_count || bg.count}x)
                                                    </span>
                                                ` : `
                                                    <span class="inline-flex items-center rounded-md bg-[#f0f4f1] dark:bg-[#202722] px-2 py-0.5 text-[0.65rem] font-medium text-[#5a6b5c] dark:text-[#a8b8a8]">
                                                        Entrance Only
                                                    </span>
                                                `}
                                            </div>
                                        </div>

                                        <!-- Group Check-in & Aggregated Check-out -->
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2 border-t border-[#e8eee9] dark:border-[#282c29] text-[0.72rem]">
                                            <div class="flex items-center gap-1.5 text-[#5a6b5c] dark:text-[#a8b8a8]">
                                                <svg class="h-3.5 w-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                                                <span class="font-medium">Check-In:</span>
                                                <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(bgCheckIn)}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5 text-[#5a6b5c] dark:text-[#a8b8a8]">
                                                <svg class="h-3.5 w-3.5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                                                <span class="font-medium">Check-Out:</span>
                                                <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0] leading-snug">${escapeHtml(bgCheckOut)}</span>
                                            </div>
                                        </div>

                                        <!-- Member breakdown list -->
                                        ${(bg.members && bg.members.length > 0) ? `
                                            <div class="pt-2 border-t border-[#e8eee9] dark:border-[#282c29] space-y-1.5">
                                                <div class="text-[0.68rem] font-bold uppercase tracking-wider text-[#5a6b5c] dark:text-[#a8b8a8]">Group Members (${bg.members.length}):</div>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 max-h-36 overflow-y-auto pr-1">
                                                    ${bg.members.map((m, mIdx) => `
                                                        <div class="p-1.5 rounded-lg bg-[#f4f7f5] dark:bg-[#141715] border border-[#e5e9e6] dark:border-[#282c29] flex items-center justify-between text-[0.68rem]">
                                                            <span class="font-medium text-[#0d2c1d] dark:text-[#f5f5f0]">Member #${mIdx + 1} (Customer #${escapeHtml(m.customer_id)})</span>
                                                            <span class="text-[#5a6b5c] dark:text-[#a8b8a8]">${m.checked_out_at ? formatDateTime(m.checked_out_at) : (reservation.check_out ? formatDateTime(reservation.check_out) : 'Completed at checkout')}</span>
                                                        </div>
                                                    `).join('')}
                                                </div>
                                            </div>
                                        ` : ''}
                                    </div>
                                `;
                            }).join('')}

                            <!-- Regular Individual Companions -->
                            ${regularCompanions.map((g, index) => {
                                const guestCheckIn = formatStayDate(g.check_in || reservation.check_in || reservation.reservation_date);
                                const guestCheckOut = g.checked_out_at ? formatDateTime(g.checked_out_at) : (reservation.check_out ? formatDateTime(reservation.check_out) : 'Completed at checkout');
                                const guestHasPool = Boolean(g.has_pool_access);

                                return `
                                    <div class="p-3.5 rounded-xl bg-[#f8faf9] dark:bg-[#141715] border border-[#dbe3de] dark:border-[#282c29] space-y-2.5">
                                        <div class="flex items-start justify-between gap-2 flex-wrap">
                                            <div class="flex items-center gap-2.5">
                                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#e8eee9] dark:bg-[#202722] text-[#5a6b5c] dark:text-[#a8b8a8] font-bold text-xs">
                                                    ${leadGuests.length + index + 1}
                                                </span>
                                                <div>
                                                    <div class="font-bold text-xs text-[#0d2c1d] dark:text-[#f5f5f0] flex items-center gap-2 flex-wrap">
                                                        <span>${escapeHtml(g.name || 'Companion')}</span>
                                                        <span class="px-1.5 py-0.5 rounded text-[0.65rem] font-medium bg-[#f0f4f1] text-[#5a6b5c] dark:bg-[#202722] dark:text-[#a8b8a8]">Companion</span>
                                                    </div>
                                                    <div class="text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8] mt-0.5">
                                                        Age: ${escapeHtml(g.age || 'N/A')} · Gender: ${escapeHtml(g.gender || 'N/A')} · ${g.is_foreigner ? 'Foreigner' : 'Filipino'}
                                                        ${(g.phone || g.email) ? ` · ${escapeHtml(g.phone || g.email)}` : ''}
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                ${guestHasPool ? `
                                                    <span class="inline-flex items-center gap-1 rounded-md bg-[#e0f2fe] dark:bg-[#082f49] px-2 py-0.5 text-[0.65rem] font-bold text-[#0284c7] dark:text-[#38bdf8] border border-[#bae6fd] dark:border-[#0369a1]/40">
                                                        <svg class="h-2.5 w-2.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 16.5c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0M2.25 20.25c1.5 1 3 1 4.5 0s3-1 4.5 0 3 1 4.5 0 3-1 4.5 0" /></svg>
                                                        Pool Access
                                                    </span>
                                                ` : `
                                                    <span class="inline-flex items-center rounded-md bg-[#f0f4f1] dark:bg-[#202722] px-2 py-0.5 text-[0.65rem] font-medium text-[#5a6b5c] dark:text-[#a8b8a8]">
                                                        Entrance Only
                                                    </span>
                                                `}
                                            </div>
                                        </div>

                                        <!-- Guest Specific Check-in & Check-out Timestamps -->
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2 border-t border-[#e8eee9] dark:border-[#282c29] text-[0.72rem]">
                                            <div class="flex items-center gap-1.5 text-[#5a6b5c] dark:text-[#a8b8a8]">
                                                <svg class="h-3.5 w-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                                                <span class="font-medium">Check-In:</span>
                                                <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(guestCheckIn)}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5 text-[#5a6b5c] dark:text-[#a8b8a8]">
                                                <svg class="h-3.5 w-3.5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                                                <span class="font-medium">Check-Out:</span>
                                                <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(guestCheckOut)}</span>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        `}
                    </div>
                </div>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- PANE 3: AMENITIES & ACCESS                              -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div id="paneAmenities" class="resv-modal-pane space-y-4 hidden">
                    <!-- Reserved Amenities List -->
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-[#5a6b5c] dark:text-[#a8b8a8]">Reserved Amenities & Facilities (${amenities.length})</span>
                            <span class="text-[0.72rem] text-emerald-700 dark:text-emerald-400 font-bold">Subtotal: ₱${amenitiesTotal.toFixed(2)}</span>
                        </div>

                        ${amenities.length === 0 ? `
                            <div class="p-6 text-center text-xs text-[#889b8a] rounded-xl bg-[#f8faf9] dark:bg-[#141715] border border-[#dbe3de] dark:border-[#282c29]">
                                No separate amenities (cottages, gazebos, tables) were booked for this reservation.
                            </div>
                        ` : `
                            <div class="space-y-2">
                                ${amenities.map(a => {
                                    const price = parseFloat(a.price || a.price_at_booking || 0);
                                    const qty = parseInt(a.quantity || 1, 10);
                                    const subtotal = parseFloat(a.subtotal || (price * qty));
                                    return `
                                        <div class="flex items-center justify-between p-3 rounded-xl bg-[#f8faf9] dark:bg-[#141715] border border-[#dbe3de] dark:border-[#282c29] text-xs">
                                            <div class="space-y-0.5">
                                                <div class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0] flex items-center gap-2">
                                                    <span>${escapeHtml(a.amenity?.amenities_name || a.amenity_name || 'Amenity')}</span>
                                                    <span class="px-2 py-0.5 rounded text-[0.65rem] font-medium bg-[#e8eee9] dark:bg-[#202722] text-[#5a6b5c] dark:text-[#a8b8a8]">${escapeHtml(a.pricing_type || 'Per Slot')}</span>
                                                </div>
                                                ${a.time_slot ? `<div class="text-[0.7rem] text-[#718774] dark:text-[#889b8a]">Slot / Time: ${escapeHtml(a.time_slot)}</div>` : ''}
                                            </div>
                                            <div class="text-right">
                                                <div class="font-bold text-sm text-emerald-700 dark:text-emerald-400">₱${subtotal.toFixed(2)}</div>
                                                <div class="text-[0.68rem] text-[#889b8a]">₱${price.toFixed(2)} × ${qty}</div>
                                            </div>
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                        `}
                    </div>

                    <!-- Admission & Pool Access Passes Breakdown -->
                    <div class="p-4 rounded-xl bg-[#f4f7f5] dark:bg-[#141715] border border-[#dbe3de] dark:border-[#282c29] space-y-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#5a6b5c] dark:text-[#a8b8a8]">Admission & Pool Access Breakdown</span>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                            <div class="p-2.5 rounded-lg bg-white dark:bg-[#181b19] border border-[#dbe3de] dark:border-[#282c29]">
                                <span class="block text-[0.68rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Base Entrance</span>
                                <span class="font-bold text-sm text-[#0d2c1d] dark:text-[#f5f5f0]">₱${baseEntranceFee.toFixed(2)}</span>
                                <div class="text-[0.68rem] text-[#889b8a] mt-0.5">${reservation.entrance_fee?.adult_count || 0} Adults, ${reservation.entrance_fee?.child_count || 0} Children</div>
                            </div>
                            <div class="p-2.5 rounded-lg bg-white dark:bg-[#181b19] border border-[#dbe3de] dark:border-[#282c29]">
                                <span class="block text-[0.68rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Additional Headcount</span>
                                <span class="font-bold text-sm text-[#0d2c1d] dark:text-[#f5f5f0]">₱${addHeadFee.toFixed(2)}</span>
                                <div class="text-[0.68rem] text-[#889b8a] mt-0.5">Extra guest fees</div>
                            </div>
                            <div class="p-2.5 rounded-lg bg-white dark:bg-[#181b19] border border-[#dbe3de] dark:border-[#282c29]">
                                <span class="block text-[0.68rem] text-[#5a6b5c] dark:text-[#a8b8a8]">Pool Access Passes</span>
                                <span class="font-bold text-sm text-[#0284c7] dark:text-[#38bdf8]">₱${poolFee.toFixed(2)}</span>
                                <div class="text-[0.68rem] text-[#889b8a] mt-0.5">${poolAccessCount} swimmers pass</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- PANE 4: BILLING & CHARGES (POST-CHECKOUT FEES)          -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div id="paneBilling" class="resv-modal-pane space-y-4 hidden">
                    <!-- Base Booking Breakdown -->
                    <div class="p-4 rounded-xl bg-[#f8faf9] dark:bg-[#141715] border border-[#dbe3de] dark:border-[#282c29] space-y-2.5">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#5a6b5c] dark:text-[#a8b8a8]">Base Booking Charges</span>
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between text-[#5a6b5c] dark:text-[#a8b8a8]">
                                <span>Total Entrance Admission:</span>
                                <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">₱${entranceFeeTotal.toFixed(2)}</span>
                            </div>
                            ${poolFee > 0 ? `
                                <div class="flex justify-between text-[#5a6b5c] dark:text-[#a8b8a8]">
                                    <span>Pool Access Passes (${poolAccessCount}x):</span>
                                    <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">₱${poolFee.toFixed(2)}</span>
                                </div>
                            ` : ''}
                            ${amenitiesTotal > 0 ? `
                                <div class="flex justify-between text-[#5a6b5c] dark:text-[#a8b8a8]">
                                    <span>Reserved Amenities Subtotal:</span>
                                    <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">₱${amenitiesTotal.toFixed(2)}</span>
                                </div>
                            ` : ''}
                            <div class="pt-2 border-t border-[#e8eee9] dark:border-[#282c29] flex justify-between font-bold text-xs text-[#0d2c1d] dark:text-[#f5f5f0]">
                                <span>Base Booking Total:</span>
                                <span>₱${totalAmount.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Post-Checkout / Additional Charges Section -->
                    <div class="p-4 rounded-xl ${hasCharges ? 'bg-amber-500/5 dark:bg-amber-500/10 border border-amber-500/30 dark:border-amber-500/25' : 'bg-[#f8faf9] dark:bg-[#141715] border border-[#dbe3de] dark:border-[#282c29]'} space-y-3">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg ${hasCharges ? 'bg-amber-500 text-white' : 'bg-[#178a52] text-white'}">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </span>
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider ${hasCharges ? 'text-amber-800 dark:text-amber-300' : 'text-[#0d2c1d] dark:text-[#f5f5f0]'}">Additional Fees / Charges After Checkout</span>
                                    <div class="text-[0.68rem] text-[#718774] dark:text-[#889b8a]">Damages, extra hours, or fees incurred during/after stay</div>
                                </div>
                            </div>
                            <span class="font-bold text-xs ${hasCharges ? 'text-amber-700 dark:text-amber-400' : 'text-emerald-700 dark:text-emerald-400'}">
                                ${hasCharges ? `+ ₱${chargesTotal.toFixed(2)}` : '₱0.00'}
                            </span>
                        </div>

                        ${!hasCharges ? `
                            <div class="p-3 rounded-lg bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200/60 dark:border-emerald-800/40 text-xs text-emerald-800 dark:text-emerald-300 flex items-center gap-2">
                                <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span>No additional fees, penalties, or damage charges were recorded for this reservation.</span>
                            </div>
                        ` : `
                            <div class="space-y-2">
                                ${charges.map(c => {
                                    const amt = parseFloat(c.amount || 0);
                                    return `
                                        <div class="p-3 rounded-lg bg-white dark:bg-[#181b19] border border-amber-500/20 dark:border-amber-500/20 text-xs flex items-center justify-between gap-2 flex-wrap">
                                            <div>
                                                <div class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0] flex items-center gap-1.5 flex-wrap">
                                                    <span>${escapeHtml(c.description || 'Additional charge')}</span>
                                                    <span class="px-1.5 py-0.5 rounded text-[0.65rem] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">${escapeHtml(c.charge_type || 'Fee')}</span>
                                                    <span class="px-1.5 py-0.5 rounded text-[0.65rem] font-bold ${c.status === 'Paid' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'}">${escapeHtml(c.status || 'Paid')}</span>
                                                </div>
                                                ${c.created_at ? `<div class="text-[0.68rem] text-[#889b8a] mt-0.5">Assessed: ${escapeHtml(c.created_at)}</div>` : ''}
                                            </div>
                                            <span class="font-bold text-sm text-amber-700 dark:text-amber-400">+ ₱${amt.toFixed(2)}</span>
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                        `}
                    </div>

                    <!-- Final Settlement Card -->
                    <div class="p-4 rounded-xl bg-[#f4f7f5] dark:bg-[#141715] border border-[#dbe3de] dark:border-[#282c29] space-y-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-[#5a6b5c] dark:text-[#a8b8a8]">Settlement & Balance</span>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between text-[#5a6b5c] dark:text-[#a8b8a8]">
                                <span>Final Total Billed (Base + Additional):</span>
                                <span class="font-bold text-base text-[#0d2c1d] dark:text-[#f5f5f0]">₱${(totalAmount + chargesTotal).toFixed(2)}</span>
                            </div>
                            <div class="flex justify-between text-[#5a6b5c] dark:text-[#a8b8a8]">
                                <span>Total Amount Paid:</span>
                                <span class="font-bold text-base text-emerald-700 dark:text-emerald-400">₱${amountPaid.toFixed(2)}</span>
                            </div>
                            <div class="flex justify-between text-[#5a6b5c] dark:text-[#a8b8a8]">
                                <span>Remaining Balance:</span>
                                <span class="font-bold text-sm ${remainingBal > 0 ? 'text-rose-700 dark:text-rose-400' : 'text-emerald-700 dark:text-emerald-400'}">
                                    ₱${remainingBal.toFixed(2)} ${remainingBal <= 0 ? '· Fully Settled' : '· Pending'}
                                </span>
                            </div>
                            <div class="pt-2 border-t border-[#e8eee9] dark:border-[#282c29] flex justify-between items-center text-[0.72rem] text-[#5a6b5c] dark:text-[#a8b8a8]">
                                <span>Payment Method: <span class="font-bold text-[#0d2c1d] dark:text-[#f5f5f0]">${escapeHtml(reservation.payment_method || 'Cash')}</span></span>
                                <span class="px-2 py-0.5 rounded-full text-[0.68rem] font-bold ${statusBadgeClass}">${escapeHtml(reservation.payment_status || 'Paid')}</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        `;

        reservationModalBody.innerHTML = html;

        // Bind Tab Switching Click Handlers
        const tabBtns = reservationModalBody.querySelectorAll('.resv-modal-tab');
        const tabPanes = reservationModalBody.querySelectorAll('.resv-modal-pane');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target-pane');
                tabBtns.forEach(b => {
                    const isActive = (b === btn);
                    b.classList.toggle('active', isActive);
                    b.classList.toggle('bg-[#178a52]', isActive);
                    b.classList.toggle('text-white', isActive);
                    b.classList.toggle('shadow-sm', isActive);
                    b.classList.toggle('border-transparent', isActive);
                    b.classList.toggle('bg-[#f8faf9]', !isActive);
                    b.classList.toggle('dark:bg-[#141715]', !isActive);
                    b.classList.toggle('text-[#5a6b5c]', !isActive);
                    b.classList.toggle('dark:text-[#a8b8a8]', !isActive);
                    b.classList.toggle('border-[#dbe3de]', !isActive);
                    b.classList.toggle('dark:border-[#282c29]', !isActive);
                });
                tabPanes.forEach(pane => {
                    pane.classList.toggle('hidden', pane.id !== targetId);
                });
            });
        });

        // Wire Single Reopen Button Inside Reservation Modal Footer
        // STRICT RULE: ONLY Cancelled or No Show reservations are eligible to reopen. NEVER Checked Out!
        const modalFooterInfo = document.getElementById('reservationModalFooterInfo');

        const row = document.querySelector(`tr.reservation-row[data-reservation-id="${reservation.id}"]`);
        const rowStatus = row ? (row.getAttribute('data-status') || '').trim().toLowerCase() : '';
        const normStatus = (reservation.status || '').trim().toLowerCase();

        const isCancelled = normStatus.includes('cancel') || rowStatus.includes('cancel');
        const isNoShow = normStatus.includes('no show') || normStatus.includes('noshow') || rowStatus.includes('no show') || rowStatus.includes('noshow');
        const isCheckedOut = normStatus.includes('checked out') || normStatus.includes('checked_out') || rowStatus.includes('checked out') || rowStatus.includes('checked_out') || Boolean(reservation.check_out);

        const isReopenable = (isCancelled || isNoShow) && !isCheckedOut;

        if (modalReopenBtn) {
            if (isReopenable) {
                modalReopenBtn.classList.remove('hidden');
                modalReopenBtn.style.display = 'inline-flex';
                modalReopenBtn.onclick = (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    activeReopeningRes = reservation;

                    // Populate Reopen Confirmation Modal
                    const resIdEl = document.getElementById('reopenConfirmResId');
                    const bookerEl = document.getElementById('reopenConfirmBookerName');
                    const schedEl = document.getElementById('reopenConfirmSchedule');
                    const stayDaysEl = document.getElementById('reopenConfirmStayDays');
                    const checkInEl = document.getElementById('reopenConfirmCheckIn');
                    const checkInSlotEl = document.getElementById('reopenConfirmCheckInSlot');
                    const checkOutEl = document.getElementById('reopenConfirmCheckOut');
                    const checkOutSlotEl = document.getElementById('reopenConfirmCheckOutSlot');
                    const errBox = document.getElementById('reopenConfirmError');

                if (resIdEl) resIdEl.textContent = `#${reservation.id}`;
                if (bookerEl) bookerEl.textContent = reservation.booker_name || 'Guest';

                // Helpers for formatting date & time
                const formatDateStr = (rawDate) => {
                    if (!rawDate) return 'N/A';
                    try {
                        const d = new Date(rawDate);
                        if (isNaN(d.getTime())) return String(rawDate);
                        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    } catch {
                        return String(rawDate);
                    }
                };

                const formatTimeStr = (rawDateTime) => {
                    if (!rawDateTime) return '';
                    try {
                        const d = new Date(rawDateTime);
                        if (isNaN(d.getTime())) return '';
                        if (String(rawDateTime).includes('T') || String(rawDateTime).includes(':')) {
                            return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                        }
                        return '';
                    } catch {
                        return '';
                    }
                };

                const checkInDate = reservation.check_in || reservation.reservation_date;
                const checkOutDate = reservation.check_out || reservation.end_date || reservation.reservation_date;

                const checkInDateFormatted = formatDateStr(checkInDate);
                const checkInTime = formatTimeStr(reservation.check_in);
                const checkInSlotText = checkInTime ? `${reservation.start_slot || 'Daytime'} · ${checkInTime}` : (reservation.start_slot || 'Daytime');

                const checkOutDateFormatted = formatDateStr(checkOutDate);
                const checkOutTime = formatTimeStr(reservation.check_out);
                const checkOutSlotText = checkOutTime ? `${reservation.end_slot || reservation.start_slot || 'Daytime'} · ${checkOutTime}` : (reservation.end_slot || reservation.start_slot || 'Daytime');

                const totalDays = parseInt(reservation.total_days || '1', 10);
                const daysLabel = totalDays > 1 ? `${totalDays} Days Stay` : '1 Day Stay';

                if (stayDaysEl) stayDaysEl.textContent = daysLabel;
                if (checkInEl) checkInEl.textContent = checkInDateFormatted;
                if (checkInSlotEl) checkInSlotEl.textContent = checkInSlotText;
                if (checkOutEl) checkOutEl.textContent = checkOutDateFormatted;
                if (checkOutSlotEl) checkOutSlotEl.textContent = checkOutSlotText;
                if (schedEl) {
                    schedEl.textContent = `${checkInDateFormatted} (${reservation.start_slot || 'Daytime'}) – ${checkOutDateFormatted} (${reservation.end_slot || reservation.start_slot || 'Daytime'})`;
                }

                if (errBox) {
                    errBox.textContent = '';
                    errBox.classList.add('hidden');
                }

                if (reopenConfirmModal) {
                    if (reopenConfirmModal.parentElement !== document.body) {
                        document.body.appendChild(reopenConfirmModal);
                    }
                    reopenConfirmModal.style.zIndex = '1400';
                    reopenConfirmModal.classList.add('is-open');
                    reopenConfirmModal.setAttribute('aria-hidden', 'false');
                }
            };
        } else {
            modalReopenBtn.classList.add('hidden');
            modalReopenBtn.style.display = 'none';
            modalReopenBtn.onclick = null;
        }
    }

        if (modalFooterInfo) {
            modalFooterInfo.innerHTML = isReopenable
                ? '<span class="text-amber-600 dark:text-amber-400 font-semibold flex items-center gap-1.5"><svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>Eligible to Reopen (Returns to Pending)</span>'
                : '<span class="text-[#889b8a]">Hinaguan Nature Park Archive Record</span>';
        }

        guestModal.classList.remove('is-open');
        reservationModal.classList.add('is-open');
        reservationModal.setAttribute('aria-hidden', 'false');
    };

    // ---- Reservation pagination state ----
    const reservationTableBodyEl = document.getElementById('reservationTableBody');
    let reservationPage = 1;
    let reservationFilteredRows = [];

    const renderReservationPagination = () => {
        const perPage = Number(reservationPerPage?.value || 100);
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
    let currentTypeTab = 'online';

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
            if (e.target.closest('.btn-expand-row') || e.target.closest('.btn-reopen-reservation')) return;
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
        if (reservationPage < Math.ceil(reservationFilteredRows.length / Number(reservationPerPageSel?.value || 100))) { reservationPage++; renderReservationPagination(); }
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

    // ============================================================
    // NO RECORDS MODAL MESSAGE (1.5s Fade In and Fade Out, No Buttons)
    // ============================================================
    const noRecordsModal = document.getElementById('noRecordsModal');
    const noRecordsModalCard = document.getElementById('noRecordsModalCard');
    const noRecordsModalBackdrop = document.getElementById('noRecordsModalBackdrop');
    const noRecordsModalTitle = document.getElementById('noRecordsModalTitle');
    const noRecordsModalMsg = document.getElementById('noRecordsModalMsg');
    let noRecordsTimer = null;

    function showNoRecordsModal(message = 'There are currently no records available in the table.', title = 'No Records to Print') {
        if (!noRecordsModal || !noRecordsModalCard) return;

        if (noRecordsTimer) {
            clearTimeout(noRecordsTimer);
            noRecordsTimer = null;
        }

        if (noRecordsModalTitle && title) {
            noRecordsModalTitle.textContent = title;
        }
        if (noRecordsModalMsg && message) {
            noRecordsModalMsg.textContent = message;
        }

        if (noRecordsModal.parentElement !== document.body) {
            document.body.appendChild(noRecordsModal);
        }

        // Reset animation classes
        noRecordsModalCard.classList.remove('animate-modal-fade-15');
        if (noRecordsModalBackdrop) {
            noRecordsModalBackdrop.classList.remove('animate-backdrop-fade-15');
        }

        // Make modal visible
        noRecordsModal.style.display = 'flex';

        // Force reflow
        void noRecordsModalCard.offsetWidth;

        // Apply 1.5s fade-in & fade-out CSS keyframe animation
        noRecordsModalCard.classList.add('animate-modal-fade-15');
        if (noRecordsModalBackdrop) {
            noRecordsModalBackdrop.classList.add('animate-backdrop-fade-15');
        }

        // Automatically hide modal when 1.5s animation completes
        noRecordsTimer = setTimeout(() => {
            noRecordsModal.style.display = 'none';
            noRecordsModalCard.classList.remove('animate-modal-fade-15');
            if (noRecordsModalBackdrop) {
                noRecordsModalBackdrop.classList.remove('animate-backdrop-fade-15');
            }
            noRecordsTimer = null;
        }, 1500);
    }

    // ============================================================
    // PRINT AS PDF (MINIMAL, CLEAN, MONOCHROME, STRICT TO FILTERS)
    // ============================================================
    const printRecordsAsPdf = () => {
        // Strictly print what is currently visible on screen (respects active tab, search, status, dates, and pagination)
        const visibleRows = Array.from(reservationTableBodyEl ? reservationTableBodyEl.querySelectorAll('tr.reservation-row:not(.hidden)') : []);

        if (visibleRows.length === 0) {
            showNoRecordsModal('There are no records currently visible to print. Please adjust your filters or search query.', 'No Records to Print');
            return;
        }

        const typeLabel = currentTypeTab === 'walk_in' ? 'Walk-in Reservations' : 'Online Reservations';
        const statusSelected = reservationStatusFilter ? reservationStatusFilter.options[reservationStatusFilter.selectedIndex]?.text : 'All Statuses';
        const dateFromVal = reservationCheckOutFrom?.value || '';
        const dateToVal = reservationCheckOutTo?.value || '';
        let dateRangeStr = 'All Dates';
        if (dateFromVal && dateToVal) {
            dateRangeStr = `${dateFromVal} to ${dateToVal}`;
        } else if (dateFromVal) {
            dateRangeStr = `From ${dateFromVal}`;
        } else if (dateToVal) {
            dateRangeStr = `Until ${dateToVal}`;
        }
        const searchVal = reservationSearchInput?.value.trim() || '';
        const perPage = Number(reservationPerPageSel?.value || 100);
        const totalFiltered = reservationFilteredRows.length;
        const totalPages = Math.max(1, Math.ceil(totalFiltered / perPage));

        const now = new Date();
        const formattedDate = now.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        const formattedTime = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });

        let totalGuests = 0;
        let totalPaid = 0;
        let totalAmountSum = 0;

        let rowsHtml = '';
        visibleRows.forEach((row, index) => {
            const resId = row.getAttribute('data-reservation-id') || '';

            // Booker & Contact
            const bookerEl = row.querySelector('td:nth-child(2) .font-bold');
            const bookerName = bookerEl ? bookerEl.textContent.trim() : (row.getAttribute('data-booker-name') || 'N/A');
            const contactEl = row.querySelector('td:nth-child(2) div:nth-child(2)');
            const contactInfo = contactEl ? contactEl.textContent.trim() : '';

            // Guests
            const guestCount = parseInt(row.getAttribute('data-guest-count') || '1', 10);
            totalGuests += guestCount;

            // Status
            const statusEl = row.querySelector('td:nth-child(4) span');
            const statusText = statusEl ? statusEl.textContent.trim() : (row.getAttribute('data-status') || 'Checked Out');

            // Check-in / Schedule
            const checkInCell = row.querySelector('td:nth-child(5)');
            const checkInDate = checkInCell?.querySelector('.font-bold')?.textContent.trim() || '';
            const checkInSlot = checkInCell?.querySelector('div:nth-child(2)')?.textContent.trim() || '';
            const checkInCombined = checkInDate ? (checkInSlot ? `${checkInDate} · ${checkInSlot}` : checkInDate) : (checkInCell?.textContent.trim() || 'N/A');

            // Check-out / Activity
            const checkOutCell = row.querySelector('td:nth-child(6)');
            const checkOutDate = checkOutCell?.querySelector('.font-bold')?.textContent.trim() || '';
            const checkOutSlot = checkOutCell?.querySelector('div:nth-child(2)')?.textContent.trim() || '';
            const checkOutCombined = checkOutDate ? (checkOutSlot ? `${checkOutDate} · ${checkOutSlot}` : checkOutDate) : (checkOutCell?.textContent.trim() || 'N/A');

            // Paid & Total
            const paidCell = row.querySelector('td:nth-child(7)');
            const paidText = paidCell?.querySelector('div:nth-child(1)')?.textContent.trim() || '₱0.00';
            const totalText = paidCell?.querySelector('div:nth-child(2)')?.textContent.trim() || paidText;

            const rawPaid = parseFloat(row.getAttribute('data-amount') || '0');
            totalPaid += isNaN(rawPaid) ? 0 : rawPaid;

            const totalMatch = totalText.match(/[\d,]+(?:\.\d+)?/);
            const rawTotal = totalMatch ? parseFloat(totalMatch[0].replace(/,/g, '')) : rawPaid;
            totalAmountSum += isNaN(rawTotal) ? 0 : rawTotal;

            rowsHtml += `
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 8px 6px; text-align: center; font-size: 8pt; color: #94a3b8; vertical-align: middle;">${index + 1}</td>
                    <td style="padding: 8px 8px; font-family: monospace; font-size: 8.5pt; color: #334155; vertical-align: middle;">#${escapeHtml(resId)}</td>
                    <td style="padding: 8px 8px; vertical-align: middle;">
                        <div style="font-size: 8.5pt; font-weight: 500; color: #0f172a;">${escapeHtml(bookerName)}</div>
                        ${contactInfo ? `<div style="font-size: 7.5pt; color: #64748b; margin-top: 1.5px;">${escapeHtml(contactInfo)}</div>` : ''}
                    </td>
                    <td style="padding: 8px 6px; text-align: center; font-size: 8.5pt; color: #334155; vertical-align: middle;">${guestCount}</td>
                    <td style="padding: 8px 8px; text-align: center; font-size: 8pt; color: #334155; vertical-align: middle;">${escapeHtml(statusText)}</td>
                    <td style="padding: 8px 8px; font-size: 8pt; color: #334155; vertical-align: middle;">${escapeHtml(checkInCombined)}</td>
                    <td style="padding: 8px 8px; font-size: 8pt; color: #334155; vertical-align: middle;">${escapeHtml(checkOutCombined)}</td>
                    <td style="padding: 8px 8px; text-align: right; font-size: 8.5pt; font-weight: 500; color: #0f172a; vertical-align: middle;">${escapeHtml(paidText)}</td>
                    <td style="padding: 8px 8px; text-align: right; font-size: 8pt; color: #64748b; vertical-align: middle;">${escapeHtml(totalText.replace(/^of\s*/i, ''))}</td>
                </tr>
            `;

            // If user has expanded companions on screen for this reservation, include them
            const companionRows = Array.from(document.querySelectorAll(`.companion-of-${resId}:not(.hidden)`))
                .filter(c => c.style.display !== 'none');

            if (companionRows.length > 0) {
                companionRows.forEach(cRow => {
                    const cNameEl = cRow.querySelector('.guest-name span') || cRow.querySelector('.font-bold');
                    const cName = cNameEl ? cNameEl.textContent.trim() : 'Companion';
                    const cMetaEl = cRow.querySelector('.guest-meta');
                    const cMeta = cMetaEl ? cMetaEl.textContent.trim() : '';
                    const cAge = cRow.querySelector('td:nth-child(2)')?.textContent.trim() || '';
                    const cNation = cRow.querySelector('td:nth-child(3)')?.textContent.trim() || '';
                    const cCheck = cRow.querySelector('td:nth-child(4)')?.textContent.trim() || '';

                    rowsHtml += `
                        <tr style="border-bottom: 1px solid #f1f5f9; background-color: transparent;">
                            <td style="text-align: center; font-size: 7pt; color: #cbd5e1; vertical-align: middle;">&bull;</td>
                            <td style="font-size: 7.5pt; color: #94a3b8; text-align: right; padding-right: 6px; vertical-align: middle;">&mdash;</td>
                            <td style="padding: 6px 8px; font-size: 8pt; vertical-align: middle;">
                                <span style="font-weight: 500; color: #334155;">${escapeHtml(cName)}</span>
                                ${cMeta ? `<span style="font-size: 7.5pt; color: #94a3b8;"> (${escapeHtml(cMeta)})</span>` : ''}
                            </td>
                            <td style="padding: 6px 6px; text-align: center; font-size: 8pt; color: #64748b; vertical-align: middle;">${escapeHtml(cAge)}</td>
                            <td style="padding: 6px 8px; text-align: center; font-size: 8pt; color: #64748b; vertical-align: middle;">${escapeHtml(cNation)}</td>
                            <td colspan="2" style="padding: 6px 8px; font-size: 8pt; color: #64748b; vertical-align: middle;">${escapeHtml(cCheck)}</td>
                            <td colspan="2" style="padding: 6px 8px; text-align: right; font-size: 7.5pt; color: #94a3b8; vertical-align: middle;">(included)</td>
                        </tr>
                    `;
                });
            }
        });

        const printDoc = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Records - ${escapeHtml(typeLabel)}</title>
    <style>
        @page {
            size: landscape;
            margin: 10mm 12mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #1e293b;
            font-size: 8.5pt;
            line-height: 1.4;
            -webkit-font-smoothing: antialiased;
        }
        .report-header {
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .report-title h1 {
            margin: 0;
            font-size: 15pt;
            font-weight: 600;
            letter-spacing: -0.2px;
            color: #0f172a;
        }
        .report-title p {
            margin: 3px 0 0 0;
            font-size: 9.5pt;
            font-weight: 400;
            color: #64748b;
        }
        .report-meta {
            text-align: right;
            font-size: 8pt;
            color: #64748b;
            line-height: 1.45;
        }
        .filter-summary {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 20px;
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
            padding: 7px 0;
            margin-bottom: 14px;
            font-size: 8pt;
            color: #475569;
        }
        .filter-summary-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .filter-summary-item .label {
            color: #94a3b8;
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .filter-summary-item .val {
            font-weight: 500;
            color: #1e293b;
        }
        table.records-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            page-break-inside: auto;
        }
        table.records-table thead {
            display: table-header-group;
        }
        table.records-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        table.records-table th {
            border-top: 1px solid #1e293b;
            border-bottom: 1px solid #1e293b;
            background: transparent;
            color: #0f172a;
            font-weight: 600;
            font-size: 8pt;
            padding: 8px 8px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        table.records-table tfoot td {
            border-top: 1px solid #1e293b;
            border-bottom: 1px solid #1e293b;
            font-weight: 600;
            background: transparent;
            padding: 8px 8px;
            font-size: 8.5pt;
            color: #0f172a;
        }
        .report-footer {
            margin-top: 16px;
            padding-top: 8px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            font-size: 7.5pt;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <div class="report-title">
            <h1>Hinaguan Nature Park</h1>
            <p>Staff Records Archive &mdash; ${escapeHtml(typeLabel)}</p>
        </div>
        <div class="report-meta">
            <div>Printed: ${formattedDate} &bull; ${formattedTime}</div>
            <div>Staff Management Portal</div>
            <div>Page ${reservationPage} of ${totalPages}</div>
        </div>
    </div>

    <div class="filter-summary">
        <div class="filter-summary-item"><span class="label">Status:</span> <span class="val">${escapeHtml(statusSelected)}</span></div>
        <div class="filter-summary-item"><span class="label">Date Range:</span> <span class="val">${escapeHtml(dateRangeStr)}</span></div>
        ${searchVal ? `<div class="filter-summary-item"><span class="label">Search:</span> <span class="val">"${escapeHtml(searchVal)}"</span></div>` : ''}
        <div class="filter-summary-item"><span class="label">Showing:</span> <span class="val">${visibleRows.length} of ${totalFiltered} records</span></div>
    </div>

    <table class="records-table">
        <thead>
            <tr>
                <th style="width: 32px; text-align: center;">#</th>
                <th style="width: 75px;">RES ID</th>
                <th>MAIN BOOKER & CONTACT</th>
                <th style="width: 55px; text-align: center;">GUESTS</th>
                <th style="width: 90px; text-align: center;">STATUS</th>
                <th style="width: 145px;">SCHEDULE / CHECK-IN</th>
                <th style="width: 145px;">CHECK-OUT / ACTIVITY</th>
                <th style="width: 85px; text-align: right;">PAID</th>
                <th style="width: 85px; text-align: right;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            ${rowsHtml}
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: left;">SUMMARY (Visible ${visibleRows.length} Records)</td>
                <td style="text-align: center;">${totalGuests}</td>
                <td colspan="3"></td>
                <td style="text-align: right;">₱${totalPaid.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                <td style="text-align: right;">₱${totalAmountSum.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
            </tr>
        </tfoot>
    </table>

    <div class="report-footer">
        <div>Hinaguan Nature Park Management System &bull; Staff Records Archive Report</div>
        <div>Confidential &bull; Filter-dependent print record</div>
    </div>
</body>
</html>`;

        const iframe = document.createElement('iframe');
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        document.body.appendChild(iframe);

        const doc = iframe.contentWindow.document;
        doc.open();
        doc.write(printDoc);
        doc.close();

        iframe.contentWindow.focus();
        setTimeout(() => {
            iframe.contentWindow.print();
            setTimeout(() => {
                iframe.remove();
            }, 1000);
        }, 300);
    };

    const printRecordsPdfBtn = document.getElementById('printRecordsPdfBtn');
    printRecordsPdfBtn?.addEventListener('click', printRecordsAsPdf);

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