document.addEventListener('DOMContentLoaded', function() {
    // Page transition with skeleton loading
    const pageTransitionOverlay = document.getElementById('pageTransitionOverlay');
    
    // Handle outgoing page transition (when clicking navigation links)
    const transitionLinks = document.querySelectorAll('[data-page-transition]');
    transitionLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetUrl = this.href;
            
            // Immediately update active state on sidemenu
            const allLinks = document.querySelectorAll('.dash-sidebar__link');
            allLinks.forEach(l => l.classList.remove('is-active'));
            this.classList.add('is-active');
            
            // Show skeleton overlay immediately
            if (pageTransitionOverlay) {
                pageTransitionOverlay.classList.add('is-active');
                
                // Navigate after a short delay to allow the overlay to appear
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 100);
            } else {
                // Fallback if overlay doesn't exist
                window.location.href = targetUrl;
            }
        });
    });

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
                const unavailableSlotsArray = unavailableSlots.split(',').filter(s => s.trim());
                
                // DayNight covers both daytime and nighttime
                if (selectedTimeSlot === 'daynight') {
                    // Show amenity if it's available in either daytime or nighttime
                    matchesTimeSlot = availableSlotsArray.includes('daytime') || availableSlotsArray.includes('nighttime');
                } else {
                    // For daytime or nighttime, check if available in that specific slot
                    matchesTimeSlot = availableSlotsArray.includes(selectedTimeSlot);
                }
            }

            // Availability filter
            let matchesAvailability = true;
            if (selectedAvailability !== 'all' && selectedTimeSlot !== 'all') {
                const availableSlotsArray = availableSlots.split(',').filter(s => s.trim());
                const unavailableSlotsArray = unavailableSlots.split(',').filter(s => s.trim());
                
                if (selectedTimeSlot === 'daynight') {
                    // For DayNight, check availability in both daytime and nighttime
                    const isDaytimeAvailable = availableSlotsArray.includes('daytime');
                    const isNighttimeAvailable = availableSlotsArray.includes('nighttime');
                    const isDaytimeUnavailable = unavailableSlotsArray.includes('daytime');
                    const isNighttimeUnavailable = unavailableSlotsArray.includes('nighttime');
                    
                    if (selectedAvailability === 'available') {
                        // Show if available in at least one slot
                        matchesAvailability = isDaytimeAvailable || isNighttimeAvailable;
                    } else if (selectedAvailability === 'unavailable') {
                        // Show if unavailable in at least one slot
                        matchesAvailability = isDaytimeUnavailable || isNighttimeUnavailable;
                    }
                } else {
                    // For specific time slot, check availability in that slot
                    if (selectedAvailability === 'available') {
                        matchesAvailability = availableSlotsArray.includes(selectedTimeSlot);
                    } else if (selectedAvailability === 'unavailable') {
                        matchesAvailability = unavailableSlotsArray.includes(selectedTimeSlot);
                    }
                }
            } else if (selectedAvailability !== 'all' && selectedTimeSlot === 'all') {
                // When time slot is "all", availability filter applies to overall availability
                const availableSlotsArray = availableSlots.split(',').filter(s => s.trim());
                
                if (selectedAvailability === 'available') {
                    // Show if available in at least one time slot
                    matchesAvailability = availableSlotsArray.length > 0;
                } else if (selectedAvailability === 'unavailable') {
                    // Show if unavailable in at least one time slot
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

    // Occupancy card click handlers
    occupancyCards.forEach(card => {
        card.addEventListener('click', function() {
            const amenityId = this.dataset.amenityId;
            // TODO: Implement amenity details modal or navigation
            console.log('Amenity clicked:', amenityId);
        });
    });

    // Add keyboard navigation for accessibility
    occupancyCards.forEach(card => {
        card.setAttribute('tabindex', '0');
        card.setAttribute('role', 'button');
        card.setAttribute('aria-label', `View details for ${card.querySelector('.occupancy-card__name').textContent}`);

        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
});
