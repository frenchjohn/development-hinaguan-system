window.AppPage = window.AppPage || {};
window.AppPage['staff_occupancy_monitor'] = function () {

    // Filter functionality
    const filterToggleBtn = document.getElementById('filterToggleBtn');
    const filterPanel = document.getElementById('filterPanel');
    const searchInput = document.getElementById('searchAmenities');
    const timeSlotFilter = document.getElementById('timeSlotFilter');
    const availabilityFilter = document.getElementById('availabilityFilter');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    const occupancyCards = document.querySelectorAll('.occupancy-card');

    // Toggle filter panel
    filterToggleBtn?.addEventListener('click', function() {
        filterPanel.classList.toggle('is-open');
    });

    // Clear filters
    clearFiltersBtn?.addEventListener('click', function() {
        if (searchInput) searchInput.value = '';
        if (timeSlotFilter) timeSlotFilter.value = 'all';
        if (availabilityFilter) availabilityFilter.value = 'all';
        applyFilters();
    });

    // Apply filters
    function applyFilters() {
        const searchTerm = searchInput?.value.toLowerCase() || '';
        const selectedTimeSlot = timeSlotFilter?.value || 'all';
        const selectedAvailability = availabilityFilter?.value || 'all';

        occupancyCards.forEach(card => {
            const amenityName = card.dataset.amenityName || '';
            const availableSlots = card.dataset.availableSlots || '';
            const unavailableSlots = card.dataset.unavailableSlots || '';

            // Search filter
            const matchesSearch = amenityName.includes(searchTerm);

            // Time slot filter
            let matchesTimeSlot = true;
            if (selectedTimeSlot !== 'all') {
                const availableSlotsArray = availableSlots.split(',').filter(s => s.trim());

                if (selectedTimeSlot === 'daynight') {
                    matchesTimeSlot = availableSlotsArray.includes('daytime') || availableSlotsArray.includes('nighttime');
                } else {
                    matchesTimeSlot = availableSlotsArray.includes(selectedTimeSlot);
                }
            }

            // Availability filter
            let matchesAvailability = true;
            if (selectedAvailability !== 'all' && selectedTimeSlot !== 'all') {
                const availableSlotsArray = availableSlots.split(',').filter(s => s.trim());
                const unavailableSlotsArray = unavailableSlots.split(',').filter(s => s.trim());

                if (selectedTimeSlot === 'daynight') {
                    const isDaytimeAvailable = availableSlotsArray.includes('daytime');
                    const isNighttimeAvailable = availableSlotsArray.includes('nighttime');
                    const isDaytimeUnavailable = unavailableSlotsArray.includes('daytime');
                    const isNighttimeUnavailable = unavailableSlotsArray.includes('nighttime');

                    if (selectedAvailability === 'available') {
                        matchesAvailability = isDaytimeAvailable || isNighttimeAvailable;
                    } else if (selectedAvailability === 'unavailable') {
                        matchesAvailability = isDaytimeUnavailable || isNighttimeUnavailable;
                    }
                } else {
                    if (selectedAvailability === 'available') {
                        matchesAvailability = availableSlotsArray.includes(selectedTimeSlot);
                    } else if (selectedAvailability === 'unavailable') {
                        matchesAvailability = unavailableSlotsArray.includes(selectedTimeSlot);
                    }
                }
            } else if (selectedAvailability !== 'all' && selectedTimeSlot === 'all') {
                const availableSlotsArray = availableSlots.split(',').filter(s => s.trim());

                if (selectedAvailability === 'available') {
                    matchesAvailability = availableSlotsArray.length > 0;
                } else if (selectedAvailability === 'unavailable') {
                    const unavailableSlotsArray = unavailableSlots.split(',').filter(s => s.trim());
                    matchesAvailability = unavailableSlotsArray.length > 0;
                }
            }

            // Show/hide card based on filters
            if (matchesSearch && matchesTimeSlot && matchesAvailability) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });

        // Check if any cards are visible
        const visibleCards = Array.from(occupancyCards).filter(card => card.style.display !== 'none');
        const emptyState = document.querySelector('.occupancy-empty');

        if (visibleCards.length === 0 && emptyState) {
            emptyState.style.display = 'flex';
            emptyState.querySelector('p').textContent = 'No amenities match your filters';
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
                    div.innerHTML = `<strong>Occupied</strong> (Reservation #${item.reservation_id} - ${item.time_slot})`;
                    modalStatusList.appendChild(div);
                });
                reserved.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'status-badge status-badge--reserved';
                    div.innerHTML = `<strong>Reserved Today</strong> (Reservation #${item.reservation_id} - ${item.time_slot})`;
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
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['staff_occupancy_monitor']());