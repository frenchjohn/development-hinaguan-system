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
    const timeSlotFilter = document.getElementById('timeSlotFilter');
    const availabilityFilter = document.getElementById('availabilityFilter');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');

    function applyFilters() {
        const searchTerm = searchInput?.value.toLowerCase().trim() || '';
        const selectedTimeSlot = timeSlotFilter?.value || 'all';
        const selectedAvailability = availabilityFilter?.value || 'all';

        occupancyCards.forEach(card => {
            const amenityName = card.dataset.amenityName || '';
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

            if (matchesSearch && matchesTimeSlot && matchesAvailability) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    searchInput?.addEventListener('input', applyFilters);
    timeSlotFilter?.addEventListener('change', applyFilters);
    availabilityFilter?.addEventListener('change', applyFilters);
    clearFiltersBtn?.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        if (timeSlotFilter) timeSlotFilter.value = 'all';
        if (availabilityFilter) availabilityFilter.value = 'all';
        applyFilters();
    });

    // Modal Details Renderer
    const modalTitle = document.getElementById('infoModalTitle');
    const modalImg = document.getElementById('infoModalImage');
    const modalImgPlaceholder = document.getElementById('infoModalImgPlaceholder');
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
        if (modalAddHead) modalAddHead.textContent = additionalPerHead;
        if (modalCapacity) modalCapacity.textContent = `${minCap} - ${maxCap} guests`;

        if (modalBenefitsWrap) {
            modalBenefitsWrap.innerHTML = '';
            if (isAircon) {
                modalBenefitsWrap.innerHTML += '<span class="inline-flex items-center gap-1.5 rounded-full border border-cyan-500/40 bg-cyan-950/70 px-2.5 py-1 text-xs font-bold text-cyan-300"><i class="bi bi-snow"></i> Air-conditioned</span>';
            }
            if (freePool) {
                modalBenefitsWrap.innerHTML += '<span class="inline-flex items-center gap-1.5 rounded-full border border-blue-500/40 bg-blue-950/70 px-2.5 py-1 text-xs font-bold text-blue-300"><i class="bi bi-water"></i> Free Pool Access</span>';
            }
            if (freeEntrance) {
                modalBenefitsWrap.innerHTML += '<span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/40 bg-emerald-950/70 px-2.5 py-1 text-xs font-bold text-emerald-300"><i class="bi bi-ticket-perforated-fill"></i> Free Entrance</span>';
            }
        }

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
                modalStatusList.innerHTML = '<p class="text-xs font-semibold text-emerald-400">Available for booking today.</p>';
            } else {
                occupied.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'rounded-lg border border-red-500/40 bg-red-950/70 p-2.5 text-xs text-white flex flex-wrap items-center justify-between gap-2';
                    const guestCount = Number(item.guest_count ?? 0);
                    const sharedTag = item.is_shared_group ? ` &middot; <span class="rounded-full bg-amber-500/90 text-white px-2 py-0.5 text-[0.62rem] font-bold shadow-sm">Shared Group (${item.total_amenities_count || 2} Amenities)</span>` : '';
                    div.innerHTML = `<div><strong class="text-red-400">Occupied</strong> (Reservation #${item.reservation_id} &middot; ${item.time_slot_label || item.time_slot})${sharedTag}</div><span class="font-bold bg-white/10 px-2 py-0.5 rounded-full text-white text-[0.68rem]">${guestCount} guest${guestCount === 1 ? '' : 's'} inside</span>`;
                    modalStatusList.appendChild(div);
                });
                reserved.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'rounded-lg border border-amber-500/40 bg-amber-950/70 p-2.5 text-xs text-white flex flex-wrap items-center justify-between gap-2';
                    const sharedTag = item.is_shared_group ? ` &middot; <span class="rounded-full bg-amber-500/90 text-white px-2 py-0.5 text-[0.62rem] font-bold shadow-sm">Shared Group (${item.total_amenities_count || 2} Amenities)</span>` : '';
                    div.innerHTML = `<div><strong class="text-amber-400">Reserved Today</strong> (Reservation #${item.reservation_id} &middot; ${item.time_slot_label || item.time_slot})${sharedTag}</div>`;
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
