document.addEventListener('DOMContentLoaded', () => {
    const siteHeader = document.getElementById('amSiteHeader');
    const modal = document.getElementById('infoModal');
    const closeDetailModalBackdrop = document.getElementById('closeAmenityDetailModal');
    const closeDetailModalBtn = document.getElementById('closeAmenityDetailModalBtn');
    const closeDetailModalFooter = document.getElementById('closeAmenityDetailModalFooter');
    const occupancyCards = document.querySelectorAll('.occupancy-card');

    if (siteHeader) {
        const sync = () => document.documentElement.style.setProperty('--am-header-offset', `${siteHeader.offsetHeight}px`);
        sync();
        window.addEventListener('resize', sync, { passive: true });
    }

    // Filter functionality
    const searchInput = document.getElementById('searchAmenities');
    const categoryFilter = document.getElementById('categoryFilter');
    const timeSlotFilter = document.getElementById('timeSlotFilter');
    const availabilityFilter = document.getElementById('availabilityFilter');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    const categorySections = document.querySelectorAll('.amenity-category-section');
    const noResultsState = document.getElementById('noResultsState');

    function applyFilters() {
        const searchTerm = searchInput?.value.toLowerCase().trim() || '';
        const selectedCategory = categoryFilter?.value || 'all';
        const selectedTimeSlot = timeSlotFilter?.value || 'all';
        const selectedAvailability = availabilityFilter?.value || 'all';

        let totalVisibleCards = 0;

        categorySections.forEach(section => {
            const sectionCategory = section.dataset.category || '';
            const cards = section.querySelectorAll('.occupancy-card');
            let visibleInSection = 0;

            cards.forEach(card => {
                const amenityName = card.dataset.amenityName || '';
                const cardCategory = card.dataset.category || '';
                const availableSlots = card.dataset.availableSlots || '';
                const occupiedJson = card.dataset.occupiedJson || '[]';
                const reservedJson = card.dataset.reservedJson || '[]';

                let occupied = [];
                let reserved = [];
                try {
                    occupied = JSON.parse(occupiedJson);
                    reserved = JSON.parse(reservedJson);
                } catch (e) {}

                // Search filter
                const matchesSearch = amenityName.includes(searchTerm);

                // Category filter
                const matchesCategory = (selectedCategory === 'all') || (cardCategory === selectedCategory);

                // Time slot filter
                let matchesTimeSlot = true;
                if (selectedTimeSlot !== 'all') {
                    const availableSlotsArray = availableSlots.split(',').map(s => s.trim());
                    matchesTimeSlot = availableSlotsArray.includes(selectedTimeSlot);
                }

                // Availability filter
                let matchesAvailability = true;
                if (selectedAvailability === 'available') {
                    matchesAvailability = occupied.length === 0 && reserved.length === 0;
                } else if (selectedAvailability === 'occupied') {
                    matchesAvailability = occupied.length > 0;
                } else if (selectedAvailability === 'reserved') {
                    matchesAvailability = reserved.length > 0;
                }

                if (matchesSearch && matchesCategory && matchesTimeSlot && matchesAvailability) {
                    card.style.display = '';
                    visibleInSection++;
                    totalVisibleCards++;
                } else {
                    card.style.display = 'none';
                }
            });

            // If no cards match in this section, hide the section header and section itself
            if (visibleInSection > 0) {
                section.style.display = '';
            } else {
                section.style.display = 'none';
            }
        });

        // Show/hide empty state
        if (noResultsState) {
            if (totalVisibleCards === 0) {
                noResultsState.classList.remove('hidden');
                noResultsState.classList.add('flex');
            } else {
                noResultsState.classList.add('hidden');
                noResultsState.classList.remove('flex');
            }
        }
    }

    searchInput?.addEventListener('input', applyFilters);
    categoryFilter?.addEventListener('change', applyFilters);
    timeSlotFilter?.addEventListener('change', applyFilters);
    availabilityFilter?.addEventListener('change', applyFilters);
    clearFiltersBtn?.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        if (categoryFilter) categoryFilter.value = 'all';
        if (timeSlotFilter) timeSlotFilter.value = 'all';
        if (availabilityFilter) availabilityFilter.value = 'all';
        applyFilters();
    });

    // Modal Details Renderer
    const modalCategory = document.getElementById('infoModalCategory');
    const modalTitle = document.getElementById('infoModalTitle');
    const modalImg = document.getElementById('infoModalImage');
    const modalImgPlaceholder = document.getElementById('infoModalImgPlaceholder');
    const modalStatusBadge = document.getElementById('infoModalStatusBadge');
    const modalDesc = document.getElementById('infoModalDescription');
    const modalDaytimePrice = document.getElementById('infoModalDayPrice');
    const modalNighttimePrice = document.getElementById('infoModalNightPrice');
    const modalBenefitsWrap = document.getElementById('infoModalBenefitsWrap');
    const modalAddHead = document.getElementById('infoModalAddHead');
    const modalCapacity = document.getElementById('infoModalCapacity');
    const modalStatusList = document.getElementById('modalStatusList');

    const lockScroll = (on) => { document.body.style.overflow = on ? 'hidden' : ''; };

    const openModal = (card) => {
        if (!modal) return;

        const displayName = card.dataset.displayName || 'Amenity Details';
        const categoryName = card.dataset.categoryName || 'Park Facility';
        const imageSrc = card.dataset.imageSrc || '';
        const description = card.dataset.description || 'No description available.';
        const daytimePrice = card.dataset.daytimePrice || 'N/A';
        const nighttimePrice = card.dataset.nighttimePrice || 'N/A';
        const isAircon = card.dataset.isAircon === '1';
        const freeEntrance = card.dataset.freeEntrance === '1';
        const freePool = card.dataset.freePool === '1';
        const additionalPerHead = card.dataset.additionalPerHead || 'N/A';
        const minCap = card.dataset.minCap || 'N/A';
        const maxCap = card.dataset.maxCap || 'N/A';
        const statusBadge = card.dataset.statusBadge || 'available';

        let occupied = [];
        let reserved = [];
        try {
            occupied = JSON.parse(card.dataset.occupiedJson || '[]');
            reserved = JSON.parse(card.dataset.reservedJson || '[]');
        } catch (e) {}

        if (modalCategory) modalCategory.textContent = categoryName;
        if (modalTitle) modalTitle.textContent = displayName;
        if (modalDesc) modalDesc.textContent = description;
        if (modalDaytimePrice) modalDaytimePrice.textContent = daytimePrice;
        if (modalNighttimePrice) modalNighttimePrice.textContent = nighttimePrice;
        if (modalAddHead) modalAddHead.textContent = additionalPerHead;
        if (modalCapacity) modalCapacity.textContent = `${minCap} - ${maxCap} guests`;

        // Status Badge Overlay on Modal Image
        if (modalStatusBadge) {
            if (occupied.length > 0) {
                modalStatusBadge.innerHTML = '<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider shadow-md bg-red-600 text-white"><span class="h-1.5 w-1.5 rounded-full bg-white animate-pulse"></span>Occupied</span>';
            } else if (reserved.length > 0) {
                modalStatusBadge.innerHTML = '<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider shadow-md bg-amber-500 text-white"><span class="h-1.5 w-1.5 rounded-full bg-white animate-pulse"></span>Reserved</span>';
            } else {
                modalStatusBadge.innerHTML = '<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider shadow-md bg-emerald-600 text-white"><span class="h-1.5 w-1.5 rounded-full bg-white"></span>Available</span>';
            }
        }

        // White-theme included benefits badges
        if (modalBenefitsWrap) {
            modalBenefitsWrap.innerHTML = '';
            if (isAircon) {
                modalBenefitsWrap.innerHTML += '<span class="inline-flex items-center gap-1.5 rounded-full border border-cyan-200 bg-cyan-50 px-2.5 py-1 text-xs font-bold text-cyan-800"><i class="bi bi-snow"></i> Air-conditioned</span>';
            }
            if (freePool) {
                modalBenefitsWrap.innerHTML += '<span class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-800"><i class="bi bi-water"></i> Free Pool Access</span>';
            }
            if (freeEntrance) {
                modalBenefitsWrap.innerHTML += '<span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800"><i class="bi bi-ticket-perforated-fill"></i> Free Entrance</span>';
            }
        }

        // Modal Left Image Handling
        if (modalImg) {
            if (imageSrc) {
                modalImg.src = imageSrc;
                modalImg.style.display = 'block';
                if (modalImgPlaceholder) modalImgPlaceholder.style.display = 'none';

                modalImg.onerror = () => {
                    modalImg.style.display = 'none';
                    if (modalImgPlaceholder) modalImgPlaceholder.style.display = 'flex';
                };
            } else {
                modalImg.src = '';
                modalImg.style.display = 'none';
                if (modalImgPlaceholder) modalImgPlaceholder.style.display = 'flex';
            }
        }

        // White-theme Status & Schedule List
        if (modalStatusList) {
            modalStatusList.innerHTML = '';
            if (occupied.length === 0 && reserved.length === 0) {
                modalStatusList.innerHTML = '<div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-3 text-xs font-semibold text-emerald-800 flex items-center gap-2"><i class="bi bi-check-circle-fill text-emerald-600 text-sm"></i> Available for booking today.</div>';
            } else {
                occupied.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'rounded-xl border border-red-200 bg-red-50/80 p-3 text-xs text-gray-800 flex flex-wrap items-center justify-between gap-2';
                    const guestCount = Number(item.guest_count ?? 0);
                    const sharedTag = item.is_shared_group ? ` &middot; <span class="rounded-full bg-amber-500 text-white px-2 py-0.5 text-[0.62rem] font-bold shadow-sm">Shared Group (${item.total_amenities_count || 2} Amenities)</span>` : '';
                    div.innerHTML = `<div><strong class="text-red-700 font-bold">Occupied</strong> (Reservation #${item.reservation_id} &middot; ${item.time_slot_label || item.time_slot})${sharedTag}</div><span class="font-bold bg-red-600 text-white px-2.5 py-0.5 rounded-full text-[0.68rem]">${guestCount} guest${guestCount === 1 ? '' : 's'} inside</span>`;
                    modalStatusList.appendChild(div);
                });
                reserved.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'rounded-xl border border-amber-200 bg-amber-50/80 p-3 text-xs text-gray-800 flex flex-wrap items-center justify-between gap-2';
                    const sharedTag = item.is_shared_group ? ` &middot; <span class="rounded-full bg-amber-500 text-white px-2 py-0.5 text-[0.62rem] font-bold shadow-sm">Shared Group (${item.total_amenities_count || 2} Amenities)</span>` : '';
                    div.innerHTML = `<div><strong class="text-amber-700 font-bold">Reserved Today</strong> (Reservation #${item.reservation_id} &middot; ${item.time_slot_label || item.time_slot})${sharedTag}</div>`;
                    modalStatusList.appendChild(div);
                });
            }
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        lockScroll(true);
    };

    const closeModal = () => {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        lockScroll(false);
    };

    occupancyCards.forEach(card => {
        card.addEventListener('click', () => openModal(card));
    });

    [closeDetailModalBackdrop, closeDetailModalBtn, closeDetailModalFooter].forEach(btn => {
        btn?.addEventListener('click', closeModal);
    });

    // Count-up animation for KPI stats
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
});
