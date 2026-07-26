document.addEventListener('DOMContentLoaded', function() {
    // Occupancy card click handlers
    const occupancyCards = document.querySelectorAll('.occupancy-card');

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
