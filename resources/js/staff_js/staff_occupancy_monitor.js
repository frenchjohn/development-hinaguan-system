window.AppPage = window.AppPage || {};
window.AppPage['staff_occupancy_monitor'] = function () {

    function toISODateString(d) {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    const todayStr = toISODateString(new Date());

    // State initialized from URL query params or inputs
    const urlParams = new URLSearchParams(window.location.search);
    let appliedStartDate = urlParams.get('start_date') || document.getElementById('modalStartDateInput')?.value || todayStr;
    let appliedEndDate   = urlParams.get('end_date')   || (document.getElementById('modalEndDateInput')?.value ? document.getElementById('modalEndDateInput').value : appliedStartDate);
    let appliedSession   = (urlParams.get('session')   || document.getElementById('modalSessionSelect')?.value || 'all').toLowerCase();
    let activeCategory   = 'all';
    let currentAbortController = null;

    // ------------------------------------------------------------
    // 1. DATE & SESSION FILTER MODAL CONTROLLER
    // ------------------------------------------------------------
    function getDateFilterModal() {
        return document.getElementById('dateFilterModal');
    }

    function openModal() {
        const modal = getDateFilterModal();
        if (!modal) return;

        const startInp = document.getElementById('modalStartDateInput');
        const endInp   = document.getElementById('modalEndDateInput');
        const sessSel  = document.getElementById('modalSessionSelect');

        if (startInp) startInp.value = appliedStartDate || todayStr;
        if (endInp)   endInp.value   = (appliedEndDate && appliedEndDate !== appliedStartDate) ? appliedEndDate : '';
        if (sessSel)  sessSel.value  = appliedSession || 'all';

        modal.classList.remove('hidden');
        modal.classList.add('flex', 'is-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        const modal = getDateFilterModal();
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex', 'is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    function applyPreset(preset) {
        const startInp = document.getElementById('modalStartDateInput');
        const endInp   = document.getElementById('modalEndDateInput');
        if (!startInp || !endInp) return;

        const now = new Date();
        if (preset === 'today') {
            startInp.value = todayStr;
            endInp.value = '';
        } else if (preset === 'tomorrow') {
            const tmr = new Date();
            tmr.setDate(tmr.getDate() + 1);
            startInp.value = toISODateString(tmr);
            endInp.value = '';
        } else if (preset === 'weekend') {
            const day = now.getDay();
            const diffToSat = (6 - day + 7) % 7;
            const sat = new Date(now);
            sat.setDate(now.getDate() + diffToSat);
            const sun = new Date(sat);
            sun.setDate(sat.getDate() + 1);
            startInp.value = toISODateString(sat);
            endInp.value = toISODateString(sun);
        } else if (preset === 'week') {
            const dayOfWeek = now.getDay();
            const start = new Date(now);
            start.setDate(now.getDate() - (dayOfWeek === 0 ? 6 : dayOfWeek - 1));
            const end = new Date(start);
            end.setDate(start.getDate() + 6);
            startInp.value = toISODateString(start);
            endInp.value = toISODateString(end);
        } else if (preset === 'month') {
            const start = new Date(now.getFullYear(), now.getMonth(), 1);
            const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            startInp.value = toISODateString(start);
            endInp.value = toISODateString(end);
        }
    }

    function resetModalInputs() {
        const startInp = document.getElementById('modalStartDateInput');
        const endInp   = document.getElementById('modalEndDateInput');
        const sessSel  = document.getElementById('modalSessionSelect');
        if (startInp) startInp.value = todayStr;
        if (endInp)   endInp.value   = '';
        if (sessSel)  sessSel.value  = 'all';
    }

    function applyDateFilter() {
        const startInp = document.getElementById('modalStartDateInput');
        const endInp   = document.getElementById('modalEndDateInput');
        const sessSel  = document.getElementById('modalSessionSelect');

        let startVal = (startInp?.value ?? '').trim() || todayStr;
        let endVal   = (endInp?.value   ?? '').trim() || startVal;

        if (startVal && endVal && startVal > endVal) {
            const temp = startVal;
            startVal = endVal;
            endVal = temp;
        }

        const sessionVal = (sessSel?.value ?? 'all').toLowerCase();
        closeModal();

        const url = new URL(window.location.href);
        url.searchParams.set('start_date', startVal);
        url.searchParams.set('end_date', endVal);
        if (sessionVal && sessionVal !== 'all') {
            url.searchParams.set('session', sessionVal);
        } else {
            url.searchParams.delete('session');
        }

        fetchOccupancyData(url, startVal, endVal, sessionVal);
    }

    function resetFiltersToToday() {
        const searchInp = document.getElementById('searchAmenities');
        const availSel  = document.getElementById('availabilityFilter');
        if (searchInp) {
            searchInp.value = '';
            toggleClearSearchBtn();
        }
        if (availSel) availSel.value = 'all';

        const url = new URL(window.location.href);
        url.searchParams.delete('start_date');
        url.searchParams.delete('end_date');
        url.searchParams.delete('session');

        fetchOccupancyData(url, todayStr, todayStr, 'all');
    }

    function updateTriggerButtonUI() {
        const isFiltered = Boolean(
            (appliedStartDate && appliedStartDate !== todayStr) ||
            (appliedEndDate && appliedEndDate !== appliedStartDate && appliedEndDate !== todayStr) ||
            (appliedSession && appliedSession !== 'all')
        );

        const dot = document.getElementById('dateFilterActiveDot');
        if (dot) {
            if (isFiltered) dot.classList.remove('hidden');
            else dot.classList.add('hidden');
        }

        const clearBtn = document.getElementById('clearFiltersBtn');
        if (clearBtn) {
            if (isFiltered) clearBtn.classList.remove('hidden');
            else clearBtn.classList.add('hidden');
        }

        const btnLabel = document.getElementById('dateFilterBtnLabel');
        if (!btnLabel) return;

        const parts = [];
        if (appliedStartDate && appliedEndDate) {
            if (appliedStartDate === appliedEndDate) {
                if (appliedStartDate === todayStr) {
                    if (appliedSession && appliedSession !== 'all') {
                        parts.push('Today');
                    }
                } else {
                    try {
                        const [y, m, d] = appliedStartDate.split('-');
                        const dObj = new Date(y, m - 1, d);
                        parts.push(dObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }));
                    } catch {
                        parts.push(appliedStartDate);
                    }
                }
            } else {
                try {
                    const [y1, m1, d1] = appliedStartDate.split('-');
                    const [y2, m2, d2] = appliedEndDate.split('-');
                    const d1Obj = new Date(y1, m1 - 1, d1);
                    const d2Obj = new Date(y2, m2 - 1, d2);
                    const s1 = d1Obj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    const s2 = d2Obj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    parts.push(`${s1} – ${s2}`);
                } catch {
                    parts.push(`${appliedStartDate} – ${appliedEndDate}`);
                }
            }
        } else if (appliedStartDate && appliedStartDate !== todayStr) {
            try {
                const [y, m, d] = appliedStartDate.split('-');
                const dObj = new Date(y, m - 1, d);
                parts.push(dObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }));
            } catch {
                parts.push(appliedStartDate);
            }
        }

        if (appliedSession === 'daytime') {
            parts.push('Daytime');
        } else if (appliedSession === 'nighttime' || appliedSession === 'overnight') {
            parts.push('Overnight');
        }

        btnLabel.textContent = parts.length > 0 ? parts.join(' • ') : 'Filter by Date & Session';
    }

    function toggleClearSearchBtn() {
        const searchInp = document.getElementById('searchAmenities');
        const clearBtn  = document.getElementById('clearSearchBtn');
        if (!clearBtn) return;
        if (searchInp && searchInp.value.trim().length > 0) {
            clearBtn.classList.remove('hidden');
        } else {
            clearBtn.classList.add('hidden');
        }
    }

    // ------------------------------------------------------------
    // 2. SEAMLESS AJAX DATA FETCH & DOM SWAP
    // ------------------------------------------------------------
    function fetchOccupancyData(url, newStart, newEnd, newSession) {
        if (currentAbortController) {
            currentAbortController.abort();
        }
        currentAbortController = new AbortController();
        const { signal } = currentAbortController;

        const container = document.getElementById('occupancyDataContainer');
        const btnLabel = document.getElementById('dateFilterBtnLabel');
        if (btnLabel) {
            btnLabel.dataset.prevText = btnLabel.textContent;
            btnLabel.textContent = 'Updating...';
        }

        fetch(url.toString(), {
            signal,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Network error: ' + response.status);
            return response.text();
        })
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // 1. Update KPI Counters Strip (on top)
            const newKpis = doc.getElementById('occupancyKpiStrip');
            const curKpis = document.getElementById('occupancyKpiStrip');
            if (curKpis && newKpis) {
                curKpis.innerHTML = newKpis.innerHTML;
            }

            // 2. Update Date Range & Active Status Badge (below counters)
            const newBadge = doc.getElementById('occupancyDateRangeBadge');
            const curBadge = document.getElementById('occupancyDateRangeBadge');
            if (curBadge && newBadge) {
                curBadge.innerHTML = newBadge.innerHTML;
            }

            // 3. Update Dynamic Occupancy Data Container (below toolbar)
            const newContainer = doc.getElementById('occupancyDataContainer');
            if (container && newContainer) {
                container.innerHTML = newContainer.innerHTML;
            }

            appliedStartDate = newStart;
            appliedEndDate   = newEnd;
            appliedSession   = newSession;

            window.history.pushState(null, '', url.toString());

            updateTriggerButtonUI();
            rebindContainerEvents();
        })
        .catch(err => {
            if (err.name === 'AbortError') return;
            console.error('Failed to update occupancy data:', err);
            if (btnLabel && btnLabel.dataset.prevText) {
                btnLabel.textContent = btnLabel.dataset.prevText;
            }
        });
    }

    // ------------------------------------------------------------
    // 3. CATEGORY PILL FILTERING
    // ------------------------------------------------------------
    function bindCategoryPills() {
        const categoryPills = document.querySelectorAll('[data-category-filter]');
        categoryPills.forEach(pill => {
            pill.onclick = () => {
                categoryPills.forEach(p => {
                    p.classList.remove('is-active', 'bg-hp-green', 'text-white', 'border-hp-green');
                    p.classList.add('bg-glass', 'text-hp-text', 'border-glass-border');
                });
                pill.classList.add('is-active', 'bg-hp-green', 'text-white', 'border-hp-green');
                pill.classList.remove('bg-glass', 'text-hp-text', 'border-glass-border');
                activeCategory = pill.dataset.categoryFilter || 'all';
                applyFilters();
            };
        });
    }

    // ------------------------------------------------------------
    // 4. CLIENT-SIDE AMENITY CARDS FILTER
    // ------------------------------------------------------------
    function applyFilters() {
        const searchInput = document.getElementById('searchAmenities');
        const availabilityFilter = document.getElementById('availabilityFilter');

        const searchTerm = (searchInput?.value || '').toLowerCase().trim();
        const selectedTimeSlot = appliedSession || 'all';
        const selectedAvailability = availabilityFilter?.value || 'all';
        const cards = document.querySelectorAll('.occupancy-card');

        cards.forEach(card => {
            const amenityName = (card.dataset.amenityName || '').toLowerCase();
            const cardCategory = (card.closest('.occupancy-category-group')?.dataset.category || '').toLowerCase();
            const matchesCategory = (activeCategory === 'all') || (cardCategory === activeCategory.toLowerCase());

            const availableSlotsArray = (card.dataset.availableSlots || '')
                .split(',')
                .map(s => s.trim().toLowerCase())
                .filter(Boolean);
            const unavailableSlotsArray = (card.dataset.unavailableSlots || '')
                .split(',')
                .map(s => s.trim().toLowerCase())
                .filter(Boolean);

            let occupied = [];
            let reserved = [];
            try {
                occupied = JSON.parse(card.dataset.occupiedJson || '[]');
                reserved = JSON.parse(card.dataset.reservedJson || '[]');
            } catch (e) {}

            const isOccupied = occupied.length > 0;
            const isReserved = reserved.length > 0;
            const isAvailable = availableSlotsArray.length > 0;

            const matchesSearch = !searchTerm || amenityName.includes(searchTerm);

            let matchesTimeSlotAndAvailability = true;
            if (selectedTimeSlot === 'all') {
                if (selectedAvailability === 'available') {
                    matchesTimeSlotAndAvailability = isAvailable && !isOccupied && !isReserved;
                } else if (selectedAvailability === 'occupied') {
                    matchesTimeSlotAndAvailability = isOccupied;
                } else if (selectedAvailability === 'reserved') {
                    matchesTimeSlotAndAvailability = isReserved;
                } else if (selectedAvailability === 'unavailable') {
                    matchesTimeSlotAndAvailability = isOccupied || isReserved || unavailableSlotsArray.length > 0;
                }
            } else {
                const isSlotAvailable = availableSlotsArray.includes(selectedTimeSlot);
                const isSlotUnavailable = unavailableSlotsArray.includes(selectedTimeSlot);

                const isSlotOccupied = occupied.some(item => {
                    const slots = (item.today_slots || []).map(s => s.toLowerCase());
                    return slots.includes(selectedTimeSlot) || (item.time_slot || '').toLowerCase().includes(selectedTimeSlot);
                });
                const isSlotReserved = reserved.some(item => {
                    const slots = (item.today_slots || []).map(s => s.toLowerCase());
                    return slots.includes(selectedTimeSlot) || (item.time_slot || '').toLowerCase().includes(selectedTimeSlot);
                });

                if (selectedAvailability === 'all') {
                    matchesTimeSlotAndAvailability = true;
                } else if (selectedAvailability === 'available') {
                    matchesTimeSlotAndAvailability = isSlotAvailable && !isSlotOccupied && !isSlotReserved;
                } else if (selectedAvailability === 'occupied') {
                    matchesTimeSlotAndAvailability = isSlotOccupied;
                } else if (selectedAvailability === 'reserved') {
                    matchesTimeSlotAndAvailability = isSlotReserved;
                } else if (selectedAvailability === 'unavailable') {
                    matchesTimeSlotAndAvailability = isSlotUnavailable || isSlotOccupied || isSlotReserved;
                }
            }

            if (matchesSearch && matchesTimeSlotAndAvailability && matchesCategory) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });

        // Update category group sections visibility
        const categoryGroups = document.querySelectorAll('.occupancy-category-group');
        let totalVisibleCards = 0;
        categoryGroups.forEach(group => {
            const groupCards = group.querySelectorAll('.occupancy-card');
            const visibleInGroup = Array.from(groupCards).filter(c => c.style.display !== 'none');
            totalVisibleCards += visibleInGroup.length;
            group.style.display = visibleInGroup.length > 0 ? '' : 'none';
        });

        // Global empty state
        const container = document.getElementById('occupancyGroupsContainer');
        let emptyState = document.querySelector('.occupancy-empty');

        if (totalVisibleCards === 0) {
            if (!emptyState && container) {
                emptyState = document.createElement('div');
                emptyState.className = 'occupancy-empty py-12 text-center text-hp-text-muted';
                emptyState.innerHTML = '<p class="text-sm font-semibold">No amenities match your filters</p>';
                container.appendChild(emptyState);
            }
            if (emptyState) {
                emptyState.style.display = 'block';
            }
        } else if (emptyState) {
            emptyState.style.display = 'none';
        }
    }

    // ------------------------------------------------------------
    // 5. AMENITY DETAIL MODAL CONTROLLER
    // ------------------------------------------------------------
    function getAmenityDetailModal() {
        return document.getElementById('amenityDetailModal');
    }

    function openDetailModal(card) {
        const detailModal = getAmenityDetailModal();
        if (!detailModal) return;

        const modalTitle = document.getElementById('modalAmenityTitle');
        const modalImg = document.getElementById('modalAmenityImg');
        const modalImgPlaceholder = document.getElementById('modalAmenityImgPlaceholder');
        const modalDesc = document.getElementById('modalAmenityDesc');
        const modalDaytimePrice = document.getElementById('modalDaytimePrice');
        const modalNighttimePrice = document.getElementById('modalNighttimePrice');
        const modalDayAircon = document.getElementById('modalDayAircon');
        const modalNightAircon = document.getElementById('modalNightAircon');
        const modalAddHead = document.getElementById('modalAddHead');
        const modalCapacity = document.getElementById('modalCapacity');
        const modalStatusList = document.getElementById('modalStatusList');

        const displayName = card.dataset.displayName || 'Amenity Details';
        const imageSrc = card.dataset.imageSrc || '';
        const description = card.dataset.description || 'No description available.';
        const daytimePrice = card.dataset.daytimePrice || 'N/A';
        const nighttimePrice = card.dataset.nighttimePrice || 'N/A';
        const daytimeAirconPrice = card.dataset.daytimeAirconPrice || 'N/A';
        const nighttimeAirconPrice = card.dataset.nighttimeAirconPrice || 'N/A';
        const additionalPerHead = card.dataset.additionalPerHead || 'N/A';
        const minCap = card.dataset.minCap || 'N/A';
        const maxCap = card.dataset.maxCap || 'N/A';

        let occupied = [];
        let reserved = [];
        try {
            occupied = JSON.parse(card.dataset.occupiedJson || '[]');
            reserved = JSON.parse(card.dataset.reservedJson || '[]');
        } catch (e) {}

        if (modalTitle) modalTitle.textContent = displayName;
        if (modalDesc) modalDesc.textContent = description;
        if (modalDaytimePrice) modalDaytimePrice.textContent = daytimePrice;
        if (modalNighttimePrice) modalNighttimePrice.textContent = nighttimePrice;
        if (modalDayAircon) modalDayAircon.textContent = daytimeAirconPrice;
        if (modalNightAircon) modalNightAircon.textContent = nighttimeAirconPrice;
        if (modalAddHead) modalAddHead.textContent = additionalPerHead;
        if (modalCapacity) modalCapacity.textContent = `${minCap} - ${maxCap} guests`;

        if (imageSrc) {
            if (modalImg) {
                modalImg.src = imageSrc;
                modalImg.style.display = 'block';
            }
            if (modalImgPlaceholder) modalImgPlaceholder.style.display = 'none';
        } else {
            if (modalImg) modalImg.style.display = 'none';
            if (modalImgPlaceholder) modalImgPlaceholder.style.display = 'flex';
        }

        if (modalStatusList) {
            modalStatusList.innerHTML = '';
            const formatSlotDisplay = (val) => {
                if (!val) return '';
                return String(val).replace(/nighttime/gi, 'Overnight');
            };

            if (occupied.length === 0 && reserved.length === 0) {
                modalStatusList.innerHTML = '<p class="status-empty">Available for booking today.</p>';
            } else {
                occupied.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'status-badge status-badge--occupied';
                    const guestCount = Number(item.guest_count ?? 0);
                    const sharedTag = item.is_shared_group ? ` &middot; <span class="rounded-full bg-amber-500/90 text-white px-2 py-0.5 text-xs font-bold shadow-sm">Shared Group (${item.total_amenities_count || 2} Amenities)</span>` : '';
                    const slotLabel = formatSlotDisplay(item.time_slot_label || item.time_slot);
                    div.innerHTML = `<strong>Occupied</strong> (Reservation #${item.reservation_id} - ${slotLabel})${sharedTag} &middot; ${guestCount} guest${guestCount === 1 ? '' : 's'} inside`;
                    modalStatusList.appendChild(div);
                });
                reserved.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'status-badge status-badge--reserved';
                    const sharedTag = item.is_shared_group ? ` &middot; <span class="rounded-full bg-amber-500/90 text-white px-2 py-0.5 text-xs font-bold shadow-sm">Shared Group (${item.total_amenities_count || 2} Amenities)</span>` : '';
                    const reservationDate = item.reservation_date || item.date || 'Today';
                    const slotLabel = formatSlotDisplay(item.time_slot_label || item.time_slot);
                    div.innerHTML = `<strong>Reserved</strong> (${reservationDate}) (Reservation #${item.reservation_id} - ${slotLabel})${sharedTag}`;
                    modalStatusList.appendChild(div);
                });
            }
        }

        detailModal.classList.add('is-open');
        detailModal.setAttribute('aria-hidden', 'false');
    }

    function closeDetailModal() {
        const detailModal = getAmenityDetailModal();
        if (!detailModal) return;
        detailModal.classList.remove('is-open');
        detailModal.setAttribute('aria-hidden', 'true');
    }

    // ------------------------------------------------------------
    // 6. CARD EVENTS & KPI ANIMATIONS
    // ------------------------------------------------------------
    function bindCardEvents() {
        const cards = document.querySelectorAll('.occupancy-card');
        cards.forEach(card => {
            card.onclick = function() {
                openDetailModal(this);
            };

            card.setAttribute('tabindex', '0');
            card.setAttribute('role', 'button');
            card.setAttribute('aria-label', `View details for ${card.querySelector('.occupancy-card__name')?.textContent || 'Amenity'}`);

            card.onkeydown = function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            };
        });
    }

    function animateKpiCounters() {
        const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.querySelectorAll('.occupancy-stat__value[data-count]').forEach(el => {
            const target = parseInt(el.dataset.count || '0', 10);
            if (reduceMotion || target <= 0 || isNaN(target)) {
                el.textContent = target;
                return;
            }
            const duration = 700;
            const start = performance.now();
            const tick = (now) => {
                const p = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.round(target * eased);
                if (p < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        });
    }

    function rebindContainerEvents() {
        bindCategoryPills();
        bindCardEvents();
        animateKpiCounters();
        applyFilters();
    }

    // ------------------------------------------------------------
    // 7. INPUT LISTENERS
    // ------------------------------------------------------------
    const searchInput = document.getElementById('searchAmenities');
    if (searchInput) {
        searchInput.oninput = () => {
            toggleClearSearchBtn();
            applyFilters();
        };
    }

    const availabilityFilter = document.getElementById('availabilityFilter');
    if (availabilityFilter) {
        availabilityFilter.onchange = () => {
            applyFilters();
        };
    }

    // ------------------------------------------------------------
    // 8. DELEGATED EVENT HANDLER (GLOBAL & ROBUST)
    // ------------------------------------------------------------
    if (!window.__staffOccupancyDelegatedBound) {
        window.__staffOccupancyDelegatedBound = true;

        document.addEventListener('click', (e) => {
            // A. Open date modal
            if (e.target.closest('#openDateFilterModalBtn')) {
                e.preventDefault();
                openModal();
                return;
            }

            // B. Close date modal
            if (e.target.closest('#closeDateFilterModalBtn') || e.target.closest('#modalCancelFilterBtn')) {
                e.preventDefault();
                closeModal();
                return;
            }

            // C. Apply date filter
            if (e.target.closest('#modalApplyFilterBtn')) {
                e.preventDefault();
                applyDateFilter();
                return;
            }

            // D. Reset inside date modal
            if (e.target.closest('#modalResetFilterBtn')) {
                e.preventDefault();
                resetModalInputs();
                return;
            }

            // E. Quick Presets
            const presetBtn = e.target.closest('[data-preset]');
            if (presetBtn) {
                e.preventDefault();
                applyPreset(presetBtn.dataset.preset);
                return;
            }

            // F. Clear filters (Reset to Today)
            if (e.target.closest('#clearFiltersBtn')) {
                e.preventDefault();
                resetFiltersToToday();
                return;
            }

            // G. Clear search button
            if (e.target.closest('#clearSearchBtn')) {
                e.preventDefault();
                const searchInp = document.getElementById('searchAmenities');
                if (searchInp) {
                    searchInp.value = '';
                    toggleClearSearchBtn();
                    applyFilters();
                    searchInp.focus();
                }
                return;
            }

            // H. Backdrop click on date modal
            const dateModal = getDateFilterModal();
            if (dateModal && e.target === dateModal) {
                closeModal();
                return;
            }

            // I. Close Amenity Detail modal
            if (
                e.target.closest('#closeAmenityDetailModal') ||
                e.target.closest('#closeAmenityDetailModalBtn') ||
                e.target.closest('#closeAmenityDetailModalFooter')
            ) {
                e.preventDefault();
                closeDetailModal();
                return;
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const dateModal = getDateFilterModal();
                if (dateModal && !dateModal.classList.contains('hidden')) {
                    closeModal();
                    return;
                }
                const detailModal = getAmenityDetailModal();
                if (detailModal && detailModal.classList.contains('is-open')) {
                    closeDetailModal();
                    return;
                }
            }
        });
    }

    // Initial run
    updateTriggerButtonUI();
    toggleClearSearchBtn();
    rebindContainerEvents();
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['staff_occupancy_monitor']());