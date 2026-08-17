document.addEventListener('DOMContentLoaded', () => {
    const layout = document.querySelector('.dash-layout');

    const closeSidebar = () => layout?.classList.remove('sidebar-open');

    window.addEventListener('resize', () => {
        if (window.innerWidth > 992) {
            closeSidebar();
        }
    });

    // ------------------------------------------------------------
    // Weather forecast widget. Uses document-level event delegation so
    // it keeps working after SPA page swaps re-render the header.
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
    // Per-Account Read Tracking + Server-Sent Events (SSE) Stream
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
                bg: 'bg-emerald-500/15 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M18 11l2 2 4-4"/></svg>'
            };
        } else if (t === 'check_out' || t === 'amenity_checked_out') {
            return {
                bg: 'bg-blue-500/15 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>'
            };
        } else if (t === 'stay_extended' || t === 'amenity_extended') {
            return {
                bg: 'bg-amber-500/15 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
            };
        } else if (t === 'amenity_added') {
            return {
                bg: 'bg-teal-500/15 dark:bg-teal-500/20 text-teal-600 dark:text-teal-400',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>'
            };
        } else if (t === 'companion_added') {
            return {
                bg: 'bg-indigo-500/15 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>'
            };
        } else if (t.startsWith('rule_')) {
            return {
                bg: 'bg-purple-500/15 dark:bg-purple-500/20 text-purple-600 dark:text-purple-400',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
            };
        } else if (t.startsWith('staff_')) {
            return {
                bg: 'bg-cyan-500/15 dark:bg-cyan-500/20 text-cyan-600 dark:text-cyan-400',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>'
            };
        } else if (t.includes('cancel')) {
            return {
                bg: 'bg-rose-500/15 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>'
            };
        } else {
            return {
                bg: 'bg-[#4c9a5f]/15 dark:bg-[#4c9a5f]/20 text-[#2f6f45] dark:text-[#8fd0ab]',
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

        if (badge) {
            if (unreadCount > 0) {
                badge.textContent = unreadCount > 99 ? '99+' : unreadCount.toString();
                badge.classList.remove('hidden');
                badge.style.display = 'flex';
            } else {
                badge.classList.add('hidden');
                badge.style.display = 'none';
            }
        }

        if (pill) {
            if (unreadCount > 0) {
                pill.textContent = `${unreadCount} new`;
                pill.classList.remove('hidden');
            } else {
                pill.classList.add('hidden');
            }
        }

        // Update item badges in DOM based on this account's lastSeenId
        const lastSeen = getLastSeenId();
        document.querySelectorAll('.notif-item').forEach((item) => {
            const actId = parseInt(item.getAttribute('data-activity-id') || '0', 10);
            const newBadge = item.querySelector('.notif-new-badge');
            if (newBadge) {
                if (actId > lastSeen) {
                    newBadge.classList.remove('hidden');
                } else {
                    newBadge.classList.add('hidden');
                }
            }
        });
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
        pendingSeenId = 0;
        updateUnreadStateUI(0);
    };

    // Auto mark as read on modal/dropdown close
    const handleNotifDropdownClose = () => {
        if (pendingSeenId > 0) {
            const currentLastSeen = getLastSeenId();
            if (pendingSeenId > currentLastSeen) {
                setLastSeenId(pendingSeenId);
            }
            pendingSeenId = 0;
            updateUnreadStateUI(0);
        }
    };

    const setNotifOpen = (open) => {
        const btn = notifBellBtn();
        const panel = notifDropdown();
        if (!btn || !panel) return;

        const isCurrentlyOpen = panel.classList.contains('is-open');

        if (open && !isCurrentlyOpen) {
            // Opening: record the current highest activity ID viewed
            pendingSeenId = getHighestRenderedId();
            btn.classList.add('is-active');
            btn.setAttribute('aria-expanded', 'true');
            panel.classList.add('is-open');
            panel.setAttribute('aria-hidden', 'false');
            // Close weather dropdown if open
            setWeatherOpen(false);
        } else if (!open && isCurrentlyOpen) {
            // Closing: auto mark as read when closed
            btn.classList.remove('is-active');
            btn.setAttribute('aria-expanded', 'false');
            panel.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');
            handleNotifDropdownClose();
        }
    };

    // Render single activity item HTML
    const createActivityItemElement = (act, isNew) => {
        const meta = getActivityIconAndColor(act.type);
        const item = document.createElement('div');
        item.className = 'notif-item flex items-start gap-3 p-3.5 transition-colors hover:bg-[#f4f7f5] dark:hover:bg-[#12281c] cursor-pointer';
        item.setAttribute('data-activity-id', act.id);

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

    // Real-Time Event Stream (Server-Sent Events)
    const initActivityEventStream = () => {
        if (typeof window.EventSource === 'undefined') return;

        if (window.__activityEventSource) {
            try {
                window.__activityEventSource.close();
            } catch (e) {}
            window.__activityEventSource = null;
        }

        const lastSeen = getLastSeenId();
        const streamUrl = `/api/activity-notifications/stream?latest_id=${knownLatestId}&last_seen_id=${lastSeen}`;
        
        try {
            const evtSource = new EventSource(streamUrl);
            window.__activityEventSource = evtSource;

            evtSource.addEventListener('new_activity', (e) => {
                try {
                    const act = JSON.parse(e.data);
                    if (!act || !act.id) return;

                    const listEl = notifList();
                    if (!listEl) return;

                    const existingItem = listEl.querySelector(`[data-activity-id="${act.id}"]`);
                    if (existingItem) return;

                    const emptyState = document.getElementById('notifEmptyState');
                    if (emptyState) emptyState.remove();

                    const currentLastSeen = getLastSeenId();
                    const isNew = act.id > currentLastSeen;
                    const itemEl = createActivityItemElement(act, isNew);
                    listEl.prepend(itemEl);

                    knownLatestId = Math.max(knownLatestId, act.id);

                    const panel = notifDropdown();
                    if (panel) {
                        panel.setAttribute('data-latest-id', knownLatestId);
                        if (panel.classList.contains('is-open')) {
                            pendingSeenId = Math.max(pendingSeenId, act.id);
                        }
                    }

                    const unread = countCurrentUnreadFromDOM();
                    updateUnreadStateUI(unread);
                } catch (err) {
                    console.debug('SSE parse error', err);
                }
            });

            evtSource.onerror = () => {
                // EventSource auto reconnects
            };
        } catch (err) {
            console.debug('Failed to initialize activity event stream', err);
        }
    };

    // Initial setup on page load for the active account
    // Never auto-mark as read on page load. Only calculate and display current unread status.
    const initialUnread = countCurrentUnreadFromDOM();
    updateUnreadStateUI(initialUnread);

    // Connect to real-time event stream
    initActivityEventStream();

    // Multi-tab sync per account:
    window.addEventListener('storage', (e) => {
        const { storageKey } = getUserMeta();
        if (e.key === storageKey) {
            const unread = countCurrentUnreadFromDOM();
            updateUnreadStateUI(unread);
        }
    });

    // Clean up stream on page unload
    window.addEventListener('beforeunload', () => {
        if (window.__activityEventSource) {
            try {
                window.__activityEventSource.close();
            } catch (e) {}
        }
    });

    // ------------------------------------------------------------
    // Event Listeners for Notification & Weather Dropdowns
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

        // Weather toggle
        const weatherToggle = e.target.closest('#weatherBtn');
        if (weatherToggle) {
            e.preventDefault();
            const panel = weatherDropdown();
            // Close notif if open (auto marks as read)
            setNotifOpen(false);
            setWeatherOpen(panel ? !panel.classList.contains('is-open') : false);
            return;
        }

        const tab = e.target.closest('[data-weather-tab]');
        if (tab) {
            const index = tab.dataset.weatherTab;
            document.querySelectorAll('[data-weather-tab]').forEach((t) => {
                const active = t === tab;
                t.classList.toggle('is-active', active);
                t.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            document.querySelectorAll('[data-weather-hours]').forEach((panel) => {
                panel.classList.toggle('is-active', panel.dataset.weatherHours === index);
            });
            return;
        }

        if (e.target.closest('[data-weather-close]')) {
            setWeatherOpen(false);
            return;
        }

        // Clicking anywhere outside notif container closes notif dropdown (auto marks as read)
        const notifContainer = document.getElementById('headerNotifContainer');
        if (notifContainer && !e.target.closest('#headerNotifContainer')) {
            setNotifOpen(false);
        }

        // Clicking anywhere outside weather wrap closes weather dropdown
        const weatherWrap = weatherDropdown();
        if (weatherWrap && weatherWrap.classList.contains('is-open') && !e.target.closest('#weatherBtn') && !e.target.closest('#weatherDropdown')) {
            setWeatherOpen(false);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (notifDropdown()?.classList.contains('is-open')) {
                setNotifOpen(false);
            }
            if (weatherDropdown()?.classList.contains('is-open')) {
                setWeatherOpen(false);
            }
        }
    });

    // ------------------------------------------------------------
    // Live day + time on the weather pill
    // ------------------------------------------------------------
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
