document.addEventListener('DOMContentLoaded', () => {
    const layout = document.querySelector('.dash-layout');

    const closeSidebar = () => layout?.classList.remove('sidebar-open');

    window.addEventListener('resize', () => {
        if (window.innerWidth > 992) {
            closeSidebar();
        }
    });

    // ------------------------------------------------------------
    // Weather forecast widget
    // ------------------------------------------------------------
    const weatherBtn = () => document.getElementById('weatherBtn');
    const weatherDropdown = () => document.getElementById('weatherDropdown');

    const setWeatherOpen = (open) => {
        const btn = weatherBtn();
        const panel = weatherDropdown();
        if (!btn || !panel) return;
        btn.classList.toggle('is-active', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel.classList.toggle('is-open', open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
    };

    // ------------------------------------------------------------
    // Activity Log Notifications System (Admin & Staff)
    // Non-blocking real-time updates + Independent per-account tracking
    // ------------------------------------------------------------
    const notifBellBtn = () => document.getElementById('notifBellBtn');
    const notifDropdown = () => document.getElementById('notifDropdown');
    const notifBadge = () => document.getElementById('notifBadge');
    const notifUnreadPill = () => document.getElementById('notifUnreadPill');
    const notifList = () => document.getElementById('notifList');

    const getUserMeta = () => {
        const panel = notifDropdown();
        const userType = panel ? (panel.getAttribute('data-user-type') || 'user') : 'user';
        const userId = panel ? (panel.getAttribute('data-user-id') || '0') : '0';
        const serverLastSeen = panel ? parseInt(panel.getAttribute('data-last-seen-id') || '0', 10) : 0;
        const storageKey = `hinaguan_last_seen_${userType}_${userId}`;
        return { userType, userId, serverLastSeen, storageKey };
    };

    const getLastSeenId = () => {
        const { storageKey, serverLastSeen } = getUserMeta();
        const val = localStorage.getItem(storageKey);
        if (val !== null && val !== '') {
            const parsed = parseInt(val, 10);
            if (!isNaN(parsed)) {
                return Math.max(parsed, serverLastSeen);
            }
        }
        return serverLastSeen;
    };

    const setLastSeenId = async (id) => {
        if (id > 0) {
            const { storageKey } = getUserMeta();
            localStorage.setItem(storageKey, id.toString());
            const panel = notifDropdown();
            if (panel) panel.setAttribute('data-last-seen-id', id.toString());

            // Persist to server per-account in database
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                await fetch('/api/activity-notifications/mark-read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ last_seen_id: id })
                });
            } catch (err) {
                console.debug('Failed to sync last_seen_id to server', err);
            }
        }
    };

    const getHighestRenderedId = () => {
        let maxId = 0;
        document.querySelectorAll('.notif-item').forEach((item) => {
            const actId = parseInt(item.getAttribute('data-activity-id') || '0', 10);
            if (actId > maxId) maxId = actId;
        });
        const panel = notifDropdown();
        const serverLatest = panel ? parseInt(panel.getAttribute('data-latest-id') || '0', 10) : 0;
        return Math.max(maxId, serverLatest);
    };

    let pendingSeenId = 0;
    let knownLatestId = getHighestRenderedId();

    const getActivityIconAndColor = (type) => {
        const t = (type || '').toLowerCase();
        if (t === 'check_in' || t === 'checked_in') {
            return {
                label: 'Check-In',
                bg: 'bg-emerald-500/15 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400',
                badgeBg: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M18 11l2 2 4-4"/></svg>'
            };
        } else if (t === 'check_out' || t === 'amenity_checked_out') {
            return {
                label: 'Check-Out',
                bg: 'bg-blue-500/15 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400',
                badgeBg: 'bg-blue-500/15 text-blue-700 dark:text-blue-300',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>'
            };
        } else if (t === 'stay_extended' || t === 'amenity_extended') {
            return {
                label: 'Extension',
                bg: 'bg-amber-500/15 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400',
                badgeBg: 'bg-amber-500/15 text-amber-700 dark:text-amber-300',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
            };
        } else if (t === 'amenity_added') {
            return {
                label: 'Amenity Added',
                bg: 'bg-teal-500/15 dark:bg-teal-500/20 text-teal-600 dark:text-teal-400',
                badgeBg: 'bg-teal-500/15 text-teal-700 dark:text-teal-300',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>'
            };
        } else if (t === 'companion_added') {
            return {
                label: 'Companion Added',
                bg: 'bg-indigo-500/15 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400',
                badgeBg: 'bg-indigo-500/15 text-indigo-700 dark:text-indigo-300',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>'
            };
        } else if (t.startsWith('rule_')) {
            return {
                label: 'Rule Update',
                bg: 'bg-purple-500/15 dark:bg-purple-500/20 text-purple-600 dark:text-purple-400',
                badgeBg: 'bg-purple-500/15 text-purple-700 dark:text-purple-300',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
            };
        } else if (t.startsWith('staff_')) {
            return {
                label: 'Staff Action',
                bg: 'bg-cyan-500/15 dark:bg-cyan-500/20 text-cyan-600 dark:text-cyan-400',
                badgeBg: 'bg-cyan-500/15 text-cyan-700 dark:text-cyan-300',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>'
            };
        } else if (t.includes('cancel')) {
            return {
                label: 'Cancellation',
                bg: 'bg-rose-500/15 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400',
                badgeBg: 'bg-rose-500/15 text-rose-700 dark:text-rose-300',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>'
            };
        } else {
            return {
                label: 'Activity',
                bg: 'bg-[#4c9a5f]/15 dark:bg-[#4c9a5f]/20 text-[#2f6f45] dark:text-[#8fd0ab]',
                badgeBg: 'bg-[#4c9a5f]/15 text-[#2f6f45] dark:text-[#8fd0ab]',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
            };
        }
    };

    const escapeHtml = (text) => {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    const updateUnreadStateUI = (unreadCount) => {
        const badge = notifBadge();
        const pill = notifUnreadPill();
        const lastSeen = getLastSeenId();

        let calculatedUnread = 0;
        document.querySelectorAll('.notif-item').forEach((item) => {
            const actId = parseInt(item.getAttribute('data-activity-id') || '0', 10);
            const newBadge = item.querySelector('.notif-new-badge');
            if (actId > lastSeen) {
                calculatedUnread++;
                if (newBadge) newBadge.classList.remove('hidden');
            } else {
                if (newBadge) newBadge.classList.add('hidden');
            }
        });

        const effectiveCount = typeof unreadCount === 'number' ? unreadCount : calculatedUnread;

        if (badge) {
            if (effectiveCount > 0) {
                badge.textContent = effectiveCount > 99 ? '99+' : effectiveCount.toString();
                badge.classList.remove('hidden');
                badge.style.display = 'flex';
            } else {
                badge.classList.add('hidden');
                badge.style.display = 'none';
            }
        }

        if (pill) {
            if (effectiveCount > 0) {
                pill.textContent = `${effectiveCount} new`;
                pill.classList.remove('hidden');
            } else {
                pill.classList.add('hidden');
            }
        }
    };

    const countCurrentUnreadFromDOM = () => {
        const lastSeen = getLastSeenId();
        let unread = 0;
        document.querySelectorAll('.notif-item').forEach((item) => {
            const actId = parseInt(item.getAttribute('data-activity-id') || '0', 10);
            if (actId > lastSeen) unread++;
        });
        return unread;
    };

    const markAllAsRead = () => {
        const targetId = getHighestRenderedId();
        if (targetId > 0) {
            setLastSeenId(targetId);
        }
        document.querySelectorAll('.notif-new-badge').forEach(b => b.classList.add('hidden'));
        updateUnreadStateUI(0);
    };

    const setNotifOpen = (open) => {
        const btn = notifBellBtn();
        const panel = notifDropdown();
        if (!btn || !panel) return;

        const isCurrentlyOpen = panel.classList.contains('is-open');

        if (open && !isCurrentlyOpen) {
            btn.classList.add('is-active');
            btn.setAttribute('aria-expanded', 'true');
            panel.classList.add('is-open');
            panel.setAttribute('aria-hidden', 'false');
            setWeatherOpen(false);
        } else if (!open && isCurrentlyOpen) {
            btn.classList.remove('is-active');
            btn.setAttribute('aria-expanded', 'false');
            panel.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');
        }
    };

    // Render single activity item HTML (Dropdown item)
    const createActivityItemElement = (act, isNew) => {
        const meta = getActivityIconAndColor(act.type);
        const item = document.createElement('div');
        item.className = 'notif-item flex items-start gap-3 p-3.5 transition-colors hover:bg-[#f4f7f5] dark:hover:bg-[#12281c] cursor-pointer';
        item.setAttribute('data-activity-id', act.id);
        item.setAttribute('data-activity-title', act.title || '');
        item.setAttribute('data-activity-desc', act.description || '');
        item.setAttribute('data-activity-type', act.type || '');
        item.setAttribute('data-actor-name', act.actor_name || '');
        item.setAttribute('data-actor-role', act.actor_role || '');
        item.setAttribute('data-reservation-id', act.reservation_id || '');
        item.setAttribute('data-activity-time', act.created_at_full || act.created_at_formatted || '');
        item.setAttribute('data-activity-relative', act.created_at_human || 'Recently');

        const newBadgeHtml = isNew
            ? `<span class="notif-new-badge text-[0.6rem] font-extrabold uppercase px-1.5 py-0.2 rounded-md bg-red-600 text-white tracking-wide shadow-sm">NEW</span>`
            : `<span class="notif-new-badge hidden text-[0.6rem] font-extrabold uppercase px-1.5 py-0.2 rounded-md bg-red-600 text-white tracking-wide shadow-sm">NEW</span>`;

        const actorHtml = act.actor_name
            ? `<div class="mt-1 flex items-center gap-1.5 text-[0.66rem] text-[var(--hp-green)] dark:text-[var(--hp-gold)] font-medium">
                 <span>By: ${escapeHtml(act.actor_name)} (${escapeHtml(act.actor_role ? act.actor_role.charAt(0).toUpperCase() + act.actor_role.slice(1) : 'Staff')})</span>
               </div>`
            : '';

        item.innerHTML = `
            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 ${meta.bg}">
                ${meta.svg}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-1 mb-0.5">
                    <span class="text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] truncate">${escapeHtml(act.title)}</span>
                    <div class="flex items-center gap-1 shrink-0">
                        ${newBadgeHtml}
                        <span class="text-[0.68rem] text-[#5a6b5c] dark:text-[#a8b8a8]">${escapeHtml(act.created_at_human || 'now')}</span>
                    </div>
                </div>
                <p class="m-0 text-[0.74rem] leading-[1.35] text-[#5a6b5c] dark:text-[#a8b8a8] line-clamp-2">${escapeHtml(act.description)}</p>
                ${actorHtml}
            </div>
        `;
        return item;
    };

    // ------------------------------------------------------------
    // Notification Detail Modal Logic
    // ------------------------------------------------------------
    const notifDetailModal = () => document.getElementById('notifDetailModal');
    const notifDetailModalCard = () => document.getElementById('notifDetailModalCard');

    const openNotifDetail = (act) => {
        const modal = notifDetailModal();
        const card = notifDetailModalCard();
        if (!modal || !card) return;

        const actId = parseInt(act.id, 10);
        if (actId > 0) {
            const currentLastSeen = getLastSeenId();
            if (actId > currentLastSeen) {
                setLastSeenId(actId);
            }
            const itemEl = document.querySelector(`.notif-item[data-activity-id="${actId}"]`);
            if (itemEl) {
                const newBadge = itemEl.querySelector('.notif-new-badge');
                if (newBadge) newBadge.classList.add('hidden');
            }
            const unread = countCurrentUnreadFromDOM();
            updateUnreadStateUI(unread);
        }

        const meta = getActivityIconAndColor(act.type || '');

        const iconEl = document.getElementById('notifDetailIcon');
        const badgeEl = document.getElementById('notifDetailTypeBadge');
        const titleEl = document.getElementById('notifDetailTitle');
        const descEl = document.getElementById('notifDetailDesc');
        const timeEl = document.getElementById('notifDetailTime');
        const actorEl = document.getElementById('notifDetailActor');
        const resWrapEl = document.getElementById('notifDetailResWrap');
        const resEl = document.getElementById('notifDetailRes');

        if (iconEl) {
            iconEl.className = `w-10 h-10 rounded-xl flex items-center justify-center shrink-0 ${meta.bg}`;
            iconEl.innerHTML = meta.svg;
        }

        if (badgeEl) {
            badgeEl.className = `inline-block text-[0.68rem] font-extrabold uppercase px-2 py-0.5 rounded-full ${meta.badgeBg} tracking-wider mb-1`;
            badgeEl.textContent = meta.label;
        }

        if (titleEl) titleEl.textContent = act.title || 'Activity Notification';
        if (descEl) descEl.textContent = act.description || 'No additional details provided.';

        const timeText = act.created_at_full || act.created_at_formatted || act.time || 'Recently';
        const relativeText = act.created_at_human || act.relative || '';
        if (timeEl) timeEl.textContent = relativeText ? `${timeText} (${relativeText})` : timeText;

        const actorName = act.actor_name || 'System';
        const actorRole = act.actor_role ? (act.actor_role.charAt(0).toUpperCase() + act.actor_role.slice(1)) : 'Staff';
        if (actorEl) actorEl.textContent = `${actorName} · ${actorRole}`;

        if (act.reservation_id) {
            if (resWrapEl) resWrapEl.classList.remove('hidden');
            if (resEl) resEl.textContent = `#RES-${act.reservation_id}`;
        } else {
            if (resWrapEl) resWrapEl.classList.add('hidden');
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');

        requestAnimationFrame(() => {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        });
    };

    const closeNotifDetail = () => {
        const modal = notifDetailModal();
        const card = notifDetailModalCard();
        if (!modal || !card) return;

        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');
        }, 150);
    };

    // ------------------------------------------------------------
    // All Notifications Modal (Search, Date Filter, Full View)
    // ------------------------------------------------------------
    const allNotifsModal = () => document.getElementById('allNotifsModal');
    const allNotifsModalCard = () => document.getElementById('allNotifsModalCard');

    let allNotifsSearchTimer = null;

    const openAllNotifsModal = () => {
        setNotifOpen(false); // Close dropdown

        const modal = allNotifsModal();
        const card = allNotifsModalCard();
        if (!modal || !card) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');

        requestAnimationFrame(() => {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        });

        loadAllNotifications();
    };

    const closeAllNotifsModal = () => {
        const modal = allNotifsModal();
        const card = allNotifsModalCard();
        if (!modal || !card) return;

        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');
        }, 150);
    };

    const computeDateRangeFromPreset = (preset) => {
        const today = new Date();
        const formatDate = (d) => {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        if (preset === 'today') {
            const str = formatDate(today);
            return { start_date: str, end_date: str };
        } else if (preset === 'yesterday') {
            const y = new Date(today);
            y.setDate(y.getDate() - 1);
            const str = formatDate(y);
            return { start_date: str, end_date: str };
        } else if (preset === 'week') {
            const w = new Date(today);
            w.setDate(w.getDate() - 7);
            return { start_date: formatDate(w), end_date: formatDate(today) };
        } else if (preset === 'month') {
            const m = new Date(today);
            m.setDate(m.getDate() - 30);
            return { start_date: formatDate(m), end_date: formatDate(today) };
        }
        return { start_date: '', end_date: '' };
    };

    const loadAllNotifications = async () => {
        const container = document.getElementById('allNotifsListContainer');
        const countLabel = document.getElementById('allNotifsCountLabel');
        if (!container) return;

        const searchInput = document.getElementById('allNotifsSearchInput');
        const typeSelect = document.getElementById('allNotifsTypeSelect');
        const datePreset = document.getElementById('allNotifsDatePreset');
        const startDateInput = document.getElementById('allNotifsStartDate');
        const endDateInput = document.getElementById('allNotifsEndDate');

        const search = searchInput ? searchInput.value.trim() : '';
        const type = typeSelect ? typeSelect.value : 'all';
        const preset = datePreset ? datePreset.value : 'all';

        let startDate = '';
        let endDate = '';

        if (preset === 'custom') {
            startDate = startDateInput ? startDateInput.value : '';
            endDate = endDateInput ? endDateInput.value : '';
        } else {
            const range = computeDateRangeFromPreset(preset);
            startDate = range.start_date;
            endDate = range.end_date;
        }

        container.innerHTML = `
            <div class="py-16 text-center text-[#5a6b5c] dark:text-[#a8b8a8]">
                <span class="inline-block w-6 h-6 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mb-2"></span>
                <p class="m-0 text-xs">Loading activity logs…</p>
            </div>
        `;
        if (countLabel) countLabel.textContent = 'Loading…';

        try {
            const lastSeen = getLastSeenId();
            const params = new URLSearchParams({
                last_seen_id: lastSeen,
                search: search,
                type: type,
                start_date: startDate,
                end_date: endDate
            });

            const res = await fetch(`/api/activity-notifications/all?${params.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                __skipBusy: true
            });
            if (!res.ok) throw new Error('Failed to fetch');

            const data = await res.json();
            const activities = data.activities || [];

            if (countLabel) {
                countLabel.textContent = `Showing ${activities.length} ${activities.length === 1 ? 'notification' : 'notifications'}`;
            }

            if (!activities.length) {
                container.innerHTML = `
                    <div class="py-16 px-4 text-center text-[#5a6b5c] dark:text-[#a8b8a8]">
                        <svg class="w-10 h-10 mx-auto mb-2 opacity-40 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <p class="m-0 text-sm font-semibold text-[#0d2c1d] dark:text-[#f5f5f0]">No notifications found</p>
                        <p class="m-0 text-xs mt-1">Try adjusting your search terms or date filter</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = '';
            activities.forEach((act) => {
                const meta = getActivityIconAndColor(act.type);
                const isNew = act.is_new;

                const card = document.createElement('div');
                card.className = 'notif-modal-item group flex items-start gap-3.5 p-3.5 rounded-xl bg-white dark:bg-[#0d2116] border border-[#dbe3de] dark:border-[#1a3d2a] hover:border-[#178a52] dark:hover:border-[#2f9e63] hover:shadow-md transition-all cursor-pointer';

                const newBadgeHtml = isNew
                    ? `<span class="text-[0.6rem] font-extrabold uppercase px-1.5 py-0.5 rounded-md bg-red-600 text-white tracking-wide shadow-sm">NEW</span>`
                    : '';

                const resBadgeHtml = act.reservation_id
                    ? `<span class="inline-flex items-center gap-1 text-[0.68rem] font-bold px-2 py-0.5 rounded-md bg-emerald-500/10 text-[#178a52] dark:text-[#8fd0ab]">#RES-${act.reservation_id}</span>`
                    : '';

                card.innerHTML = `
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 ${meta.bg}">
                        ${meta.svg}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-xs font-bold text-[#0d2c1d] dark:text-[#f5f5f0] truncate">${escapeHtml(act.title)}</span>
                                <span class="text-[0.65rem] font-extrabold uppercase px-1.5 py-0.5 rounded-full ${meta.badgeBg}">${meta.label}</span>
                                ${newBadgeHtml}
                            </div>
                            <span class="text-[0.7rem] text-[#5a6b5c] dark:text-[#a8b8a8] shrink-0">${escapeHtml(act.created_at_formatted || act.created_at_human || '')}</span>
                        </div>
                        <p class="m-0 text-xs text-[#5a6b5c] dark:text-[#a8b8a8] leading-relaxed line-clamp-2">${escapeHtml(act.description)}</p>
                        <div class="mt-2 flex items-center justify-between gap-2">
                            <span class="text-[0.68rem] text-[var(--hp-green)] dark:text-[var(--hp-gold)] font-medium">By: ${escapeHtml(act.actor_name || 'Staff')} (${escapeHtml(act.actor_role ? act.actor_role.charAt(0).toUpperCase() + act.actor_role.slice(1) : 'Staff')})</span>
                            ${resBadgeHtml}
                        </div>
                    </div>
                `;

                card.addEventListener('click', () => {
                    openNotifDetail(act);
                });

                container.appendChild(card);
            });
        } catch (err) {
            container.innerHTML = `
                <div class="py-12 text-center text-red-500 text-xs">
                    Failed to load notifications. Please try again.
                </div>
            `;
            if (countLabel) countLabel.textContent = 'Error';
        }
    };

    // ------------------------------------------------------------
    // Ultra-Fast Non-Blocking Activity Heartbeat (1ms check, max 20 dropdown items)
    // ------------------------------------------------------------
    let heartbeatTimer = null;

    const checkNewActivities = async () => {
        if (document.hidden) return;

        const lastSeen = getLastSeenId();
        try {
            const url = `/api/activity-notifications?check_only=1&latest_id=${knownLatestId}&last_seen_id=${lastSeen}`;
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                __skipBusy: true
            });
            if (!res.ok) return;
            const data = await res.json();

            if (data.has_new && data.latest_id > knownLatestId) {
                const fetchUrl = `/api/activity-notifications?since_id=${knownLatestId}&last_seen_id=${lastSeen}`;
                const fetchRes = await fetch(fetchUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    __skipBusy: true
                });
                if (!fetchRes.ok) return;
                const fetchData = await fetchRes.json();
                const activities = fetchData.activities || [];

                const listEl = notifList();
                if (listEl && activities.length > 0) {
                    const emptyState = document.getElementById('notifEmptyState');
                    if (emptyState) emptyState.remove();

                    activities.slice().reverse().forEach((act) => {
                        const existing = listEl.querySelector(`[data-activity-id="${act.id}"]`);
                        if (!existing) {
                            const isNew = act.id > lastSeen;
                            const itemEl = createActivityItemElement(act, isNew);
                            listEl.prepend(itemEl);
                        }
                    });

                    // Strict limit: keep maximum 20 items in dropdown
                    while (listEl.children.length > 20) {
                        listEl.removeChild(listEl.lastElementChild);
                    }
                }

                knownLatestId = Math.max(knownLatestId, data.latest_id);
                const panel = notifDropdown();
                if (panel) {
                    panel.setAttribute('data-latest-id', knownLatestId);
                }

                // Notify Staff Chatbot Proactive Speech immediately
                if (typeof window.checkStaffChatbotProactive === 'function') {
                    window.checkStaffChatbotProactive(true);
                }
                window.dispatchEvent(new CustomEvent('activity:new', { detail: { activities, latestId: data.latest_id } }));
            }

            if (typeof data.unread_count === 'number') {
                updateUnreadStateUI(data.unread_count);
            } else {
                const unread = countCurrentUnreadFromDOM();
                updateUnreadStateUI(unread);
            }
        } catch (err) {
            console.debug('Activity heartbeat check error', err);
        }
    };

    const startHeartbeat = () => {
        stopHeartbeat();
        heartbeatTimer = setInterval(checkNewActivities, 4000);
    };

    const stopHeartbeat = () => {
        if (heartbeatTimer) {
            clearInterval(heartbeatTimer);
            heartbeatTimer = null;
        }
    };

    // Globally exposed sync function for SPA page transitions
    window.syncNotificationUI = () => {
        knownLatestId = getHighestRenderedId();
        const unread = countCurrentUnreadFromDOM();
        updateUnreadStateUI(unread);
    };

    // Initial setup on page load for the active account
    window.syncNotificationUI();

    // Start non-blocking heartbeat
    startHeartbeat();

    // Listen to SPA page swaps
    window.addEventListener('spa:navigated', () => {
        if (typeof window.syncNotificationUI === 'function') {
            window.syncNotificationUI();
        }
    });

    // Heartbeat triggers immediately when switching back to tab
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            checkNewActivities();
            startHeartbeat();
        } else {
            stopHeartbeat();
        }
    });

    window.addEventListener('focus', checkNewActivities);

    // Multi-tab sync per account:
    window.addEventListener('storage', (e) => {
        const { storageKey } = getUserMeta();
        if (e.key === storageKey) {
            const unread = countCurrentUnreadFromDOM();
            updateUnreadStateUI(unread);
        }
    });

    // ------------------------------------------------------------
    // Event Delegation for All Clicks & Modals
    // ------------------------------------------------------------
    document.addEventListener('click', (e) => {
        // Notification bell toggle
        const notifToggle = e.target.closest('#notifBellBtn');
        if (notifToggle) {
            e.preventDefault();
            const panel = notifDropdown();
            setNotifOpen(panel ? !panel.classList.contains('is-open') : false);
            return;
        }

        // Mark all as read button
        if (e.target.closest('#markAllNotifsReadBtn')) {
            e.preventDefault();
            markAllAsRead();
            return;
        }

        // See all previous notifications modal button
        if (e.target.closest('#openAllNotifsModalBtn')) {
            e.preventDefault();
            openAllNotifsModal();
            return;
        }

        // Close all notifications modal
        if (e.target.closest('[data-close-all-notifs]')) {
            e.preventDefault();
            closeAllNotifsModal();
            return;
        }

        // Close notification detail modal
        if (e.target.closest('[data-close-notif-detail]')) {
            e.preventDefault();
            closeNotifDetail();
            return;
        }

        // Click on dropdown notification item -> open detail modal
        const notifItem = e.target.closest('#notifList .notif-item');
        if (notifItem) {
            e.preventDefault();
            const actData = {
                id: notifItem.getAttribute('data-activity-id'),
                title: notifItem.getAttribute('data-activity-title'),
                description: notifItem.getAttribute('data-activity-desc'),
                type: notifItem.getAttribute('data-activity-type'),
                actor_name: notifItem.getAttribute('data-actor-name'),
                actor_role: notifItem.getAttribute('data-actor-role'),
                reservation_id: notifItem.getAttribute('data-reservation-id'),
                created_at_full: notifItem.getAttribute('data-activity-time'),
                created_at_human: notifItem.getAttribute('data-activity-relative')
            };
            openNotifDetail(actData);
            return;
        }

        // Weather toggle
        const weatherToggle = e.target.closest('#weatherBtn');
        if (weatherToggle) {
            e.preventDefault();
            const panel = weatherDropdown();
            setNotifOpen(false);
            setWeatherOpen(panel ? !panel.classList.contains('is-open') : false);
            return;
        }

        // Weather Tab Switching (Today / Wed / Thu)
        const tab = e.target.closest('[data-weather-tab]');
        if (tab) {
            e.preventDefault();
            const index = tab.dataset.weatherTab;
            document.querySelectorAll('[data-weather-tab]').forEach((t) => {
                const active = t === tab;
                t.classList.toggle('is-active', active);
                t.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            document.querySelectorAll('[data-weather-hours]').forEach((panel) => {
                const active = panel.dataset.weatherHours === index;
                panel.classList.toggle('is-active', active);
                panel.classList.toggle('hidden', !active);
            });
            return;
        }

        if (e.target.closest('[data-weather-close]')) {
            setWeatherOpen(false);
            return;
        }

        // Backdrop click for notif detail modal
        const detailModal = notifDetailModal();
        if (detailModal && e.target === detailModal) {
            closeNotifDetail();
            return;
        }

        // Backdrop click for all notifications modal
        const allModal = allNotifsModal();
        if (allModal && e.target === allModal) {
            closeAllNotifsModal();
            return;
        }

        // Clicking outside dropdown closes it
        const notifContainer = document.getElementById('headerNotifContainer');
        if (notifContainer && !e.target.closest('#headerNotifContainer') && !e.target.closest('#allNotifsModal') && !e.target.closest('#notifDetailModal')) {
            setNotifOpen(false);
        }

        const weatherWrap = weatherDropdown();
        if (weatherWrap && weatherWrap.classList.contains('is-open') && !e.target.closest('#weatherBtn') && !e.target.closest('#weatherDropdown')) {
            setWeatherOpen(false);
        }
    });

    // ------------------------------------------------------------
    // Filter controls for All Notifications Modal
    // ------------------------------------------------------------
    document.addEventListener('input', (e) => {
        if (e.target && e.target.id === 'allNotifsSearchInput') {
            const clearBtn = document.getElementById('allNotifsSearchClear');
            if (clearBtn) {
                clearBtn.classList.toggle('hidden', e.target.value.length === 0);
            }
            clearTimeout(allNotifsSearchTimer);
            allNotifsSearchTimer = setTimeout(loadAllNotifications, 250);
        }
    });

    document.addEventListener('click', (e) => {
        if (e.target && e.target.id === 'allNotifsSearchClear') {
            const searchInput = document.getElementById('allNotifsSearchInput');
            if (searchInput) {
                searchInput.value = '';
                e.target.classList.add('hidden');
                loadAllNotifications();
            }
        }
        if (e.target && e.target.id === 'allNotifsApplyCustomDate') {
            loadAllNotifications();
        }
    });

    document.addEventListener('change', (e) => {
        if (e.target && e.target.id === 'allNotifsTypeSelect') {
            loadAllNotifications();
        }
        if (e.target && e.target.id === 'allNotifsDatePreset') {
            const customWrap = document.getElementById('allNotifsCustomDateWrap');
            if (customWrap) {
                customWrap.classList.toggle('hidden', e.target.value !== 'custom');
            }
            if (e.target.value !== 'custom') {
                loadAllNotifications();
            }
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (notifDetailModal() && !notifDetailModal().classList.contains('hidden')) {
                closeNotifDetail();
                return;
            }
            if (allNotifsModal() && !allNotifsModal().classList.contains('hidden')) {
                closeAllNotifsModal();
                return;
            }
            if (notifDropdown()?.classList.contains('is-open')) {
                setNotifOpen(false);
            }
            if (weatherDropdown()?.classList.contains('is-open')) {
                setWeatherOpen(false);
            }
        }
    });

    // Live clock
    const updateWeatherClock = () => {
        const dayEl = document.getElementById('weatherClockDay');
        const timeEl = document.getElementById('weatherClockTime');
        if (!dayEl && !timeEl) return;
        const now = new Date();
        const day = now.toLocaleDateString('en-US', { weekday: 'long' });
        const time = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        if (dayEl && dayEl.textContent !== day) dayEl.textContent = day;
        if (timeEl && timeEl.textContent !== time) timeEl.textContent = time;
    };

    if (!window.__weatherClockInterval) {
        window.__weatherClockInterval = setInterval(updateWeatherClock, 1000);
    }
    updateWeatherClock();
});
