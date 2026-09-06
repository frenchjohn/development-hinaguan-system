window.AppPage = window.AppPage || {};
window.AppPage['staff_occupancy_monitor'] = function () {

    // Filter functionality
    const filterToggleBtn = document.getElementById('filterToggleBtn');
    const filterPanel = document.getElementById('filterPanel');
    const searchInput = document.getElementById('searchAmenities');
    const startDateFilter = document.getElementById('startDateFilter');
    const endDateFilter = document.getElementById('endDateFilter');
    const timeSlotFilter = document.getElementById('timeSlotFilter');
    const availabilityFilter = document.getElementById('availabilityFilter');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    const todayFilterBtn = document.getElementById('todayFilterBtn');
    const tomorrowFilterBtn = document.getElementById('tomorrowFilterBtn');
    const thisWeekendFilterBtn = document.getElementById('thisWeekendFilterBtn');
    const nextWeekFilterBtn = document.getElementById('nextWeekFilterBtn');
    const occupancyFilterForm = document.getElementById('occupancyFilterForm');
    const occupancyCards = document.querySelectorAll('.occupancy-card');

    // Quick Date Helpers
    const formatDate = (date) => {
        const d = new Date(date);
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${d.getFullYear()}-${month}-${day}`;
    };

    // Toggle filter panel
    filterToggleBtn?.addEventListener('click', function() {
        if (!filterPanel) return;
        filterPanel.classList.toggle('hidden');
        filterPanel.classList.toggle('is-open');
    });

    todayFilterBtn?.addEventListener('click', () => {
        const todayStr = formatDate(new Date());
        if (startDateFilter) startDateFilter.value = todayStr;
        if (endDateFilter) endDateFilter.value = todayStr;
        occupancyFilterForm?.submit();
    });

    tomorrowFilterBtn?.addEventListener('click', () => {
        const tmr = new Date();
        tmr.setDate(tmr.getDate() + 1);
        const tmrStr = formatDate(tmr);
        if (startDateFilter) startDateFilter.value = tmrStr;
        if (endDateFilter) endDateFilter.value = tmrStr;
        occupancyFilterForm?.submit();
    });

    thisWeekendFilterBtn?.addEventListener('click', () => {
        const now = new Date();
        const day = now.getDay();
        const diffToSat = (6 - day + 7) % 7;
        const sat = new Date(now);
        sat.setDate(now.getDate() + diffToSat);
        const sun = new Date(sat);
        sun.setDate(sat.getDate() + 1);

        if (startDateFilter) startDateFilter.value = formatDate(sat);
        if (endDateFilter) endDateFilter.value = formatDate(sun);
        occupancyFilterForm?.submit();
    });

    nextWeekFilterBtn?.addEventListener('click', () => {
        const now = new Date();
        const day = now.getDay();
        const diffToNextMon = (8 - day) % 7 || 7;
        const nextMon = new Date(now);
        nextMon.setDate(now.getDate() + diffToNextMon);
        const nextSun = new Date(nextMon);
        nextSun.setDate(nextMon.getDate() + 6);

        if (startDateFilter) startDateFilter.value = formatDate(nextMon);
        if (endDateFilter) endDateFilter.value = formatDate(nextSun);
        occupancyFilterForm?.submit();
    });

    startDateFilter?.addEventListener('change', () => {
        if (startDateFilter.value && endDateFilter && (!endDateFilter.value || endDateFilter.value < startDateFilter.value)) {
            endDateFilter.value = startDateFilter.value;
        }
    });

    // Clear filters
    clearFiltersBtn?.addEventListener('click', function() {
        if (searchInput) searchInput.value = '';
        if (timeSlotFilter) timeSlotFilter.value = 'all';
        if (availabilityFilter) availabilityFilter.value = 'all';
        activeCategory = 'all';
        categoryPills.forEach((p, idx) => {
            if (idx === 0) {
                p.classList.add('is-active', 'bg-hp-green', 'text-white', 'border-hp-green');
                p.classList.remove('bg-glass', 'text-hp-text', 'border-glass-border');
            } else {
                p.classList.remove('is-active', 'bg-hp-green', 'text-white', 'border-hp-green');
                p.classList.add('bg-glass', 'text-hp-text', 'border-glass-border');
            }
        });
        const todayStr = formatDate(new Date());
        if (startDateFilter && (startDateFilter.value !== todayStr || endDateFilter?.value !== todayStr)) {
            startDateFilter.value = todayStr;
            if (endDateFilter) endDateFilter.value = todayStr;
            occupancyFilterForm?.submit();
        } else {
            applyFilters();
        }
    });

    // Category Pill filtering
    const categoryPills = document.querySelectorAll('[data-category-filter]');
    let activeCategory = 'all';

    categoryPills.forEach(pill => {
        pill.addEventListener('click', () => {
            categoryPills.forEach(p => {
                p.classList.remove('is-active', 'bg-hp-green', 'text-white', 'border-hp-green');
                p.classList.add('bg-glass', 'text-hp-text', 'border-glass-border');
            });
            pill.classList.add('is-active', 'bg-hp-green', 'text-white', 'border-hp-green');
            pill.classList.remove('bg-glass', 'text-hp-text', 'border-glass-border');
            activeCategory = pill.dataset.categoryFilter || 'all';
            applyFilters();
        });
    });

    // Apply filters
    function applyFilters() {
        const searchTerm = (searchInput?.value || '').toLowerCase().trim();
        const selectedTimeSlot = timeSlotFilter?.value || 'all';
        const selectedAvailability = availabilityFilter?.value || 'all';

        occupancyCards.forEach(card => {
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

            // 1. Search Filter
            const matchesSearch = !searchTerm || amenityName.includes(searchTerm);

            // 2. Time Slot & Availability Filter
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
                // Specific slot: daytime or nighttime
                const isSlotAvailable = availableSlotsArray.includes(selectedTimeSlot);
                const isSlotUnavailable = unavailableSlotsArray.includes(selectedTimeSlot);

                // Check if occupied or reserved specifically for this slot
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

            // Show/hide card based on filters
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

    // Event listeners for filter inputs
    searchInput?.addEventListener('input', applyFilters);
    timeSlotFilter?.addEventListener('change', applyFilters);
    availabilityFilter?.addEventListener('change', applyFilters);

    // Amenity Detail Modal elements
    const detailModal = document.getElementById('amenityDetailModal');
    const closeDetailModalBackdrop = document.getElementById('closeAmenityDetailModal');
    const closeDetailModalBtn = document.getElementById('closeAmenityDetailModalBtn');
    const closeDetailModalFooter = document.getElementById('closeAmenityDetailModalFooter');

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

    const openDetailModal = (card) => {
        if (!detailModal) return;

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
            modalImg.src = imageSrc;
            modalImg.style.display = 'block';
            if (modalImgPlaceholder) modalImgPlaceholder.style.display = 'none';
        } else {
            modalImg.style.display = 'none';
            if (modalImgPlaceholder) modalImgPlaceholder.style.display = 'flex';
        }

        if (modalStatusList) {
            modalStatusList.innerHTML = '';
            if (occupied.length === 0 && reserved.length === 0) {
                modalStatusList.innerHTML = '<p class="status-empty">Available for booking today.</p>';
            } else {
                occupied.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'status-badge status-badge--occupied';
                    const guestCount = Number(item.guest_count ?? 0);
                    const sharedTag = item.is_shared_group ? ` &middot; <span class="rounded-full bg-amber-500/90 text-white px-2 py-0.5 text-xs font-bold shadow-sm">Shared Group (${item.total_amenities_count || 2} Amenities)</span>` : '';
                    div.innerHTML = `<strong>Occupied</strong> (Reservation #${item.reservation_id} - ${item.time_slot_label || item.time_slot})${sharedTag} &middot; ${guestCount} guest${guestCount === 1 ? '' : 's'} inside`;
                    modalStatusList.appendChild(div);
                });
                reserved.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'status-badge status-badge--reserved';
                    const sharedTag = item.is_shared_group ? ` &middot; <span class="rounded-full bg-amber-500/90 text-white px-2 py-0.5 text-xs font-bold shadow-sm">Shared Group (${item.total_amenities_count || 2} Amenities)</span>` : '';
                    div.innerHTML = `<strong>Reserved Today</strong> (Reservation #${item.reservation_id} - ${item.time_slot_label || item.time_slot})${sharedTag}`;
                    modalStatusList.appendChild(div);
                });
            }
        }

        detailModal.classList.add('is-open');
        detailModal.setAttribute('aria-hidden', 'false');
    };

    const closeDetailModal = () => {
        if (!detailModal) return;
        detailModal.classList.remove('is-open');
        detailModal.setAttribute('aria-hidden', 'true');
    };

    [closeDetailModalBackdrop, closeDetailModalBtn, closeDetailModalFooter].forEach(btn => {
        btn?.addEventListener('click', closeDetailModal);
    });

    // Occupancy card click handlers
    occupancyCards.forEach(card => {
        card.addEventListener('click', function() {
            openDetailModal(this);
        });
    });

    // Add keyboard navigation for accessibility
    occupancyCards.forEach(card => {
        card.setAttribute('tabindex', '0');
        card.setAttribute('role', 'button');
        card.setAttribute('aria-label', `View details for ${card.querySelector('.occupancy-card__name')?.textContent || 'Amenity'}`);

        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });

    // Count-up animation for the KPI values
    const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    document.querySelectorAll('.occupancy-stat__value[data-count]').forEach(el => {
        const target = parseInt(el.dataset.count || '0', 10);
        if (reduceMotion || target <= 0 || isNaN(target)) return;
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
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['staff_occupancy_monitor']());