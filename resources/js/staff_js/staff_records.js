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

        const modalTitle = document.getElementById('reservationModalTitle');
        const modalIdBadge = document.getElementById('reservationModalIdBadge');
        const modalStatusBadge = document.getElementById('reservationModalStatusBadge');

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