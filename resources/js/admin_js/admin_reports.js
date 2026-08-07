window.AppPage = window.AppPage || {};
window.AppPage['admin_reports'] = function () {


    const printButton = document.getElementById('printReportsButton');
    const amenityFilter = document.getElementById('amenityFilter');
    const statusFilter = document.getElementById('statusFilter');
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    const reservationsTable = document.getElementById('reservationsTable');

    // Tab functionality
    const tabButtons = document.querySelectorAll('.reports-tab');
    const tabContents = document.querySelectorAll('.reports-tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabId = button.dataset.tab;

            // Remove active class from all tabs
            tabButtons.forEach(btn => btn.classList.remove('reports-tab--active'));
            tabContents.forEach(content => content.classList.remove('reports-tab-content--active'));

            // Add active class to clicked tab
            button.classList.add('reports-tab--active');

            // Show corresponding content
            const targetContent = document.getElementById(`tab-${tabId}`);
            if (targetContent) {
                targetContent.classList.add('reports-tab-content--active');
            }
        });
    });

    const matchesFilter = (row) => {
        const rowAmenity = row.dataset.amenity?.toLowerCase() || '';
        const rowStatus = row.dataset.status?.toLowerCase() || '';
        const rowCheckin = row.dataset.checkin;

        const amenityMatch = amenityFilter.value === 'all' || rowAmenity.includes(amenityFilter.value.toLowerCase());
        const statusMatch = statusFilter.value === 'all' || rowStatus === statusFilter.value.toLowerCase();

        let dateMatch = true;
        if (rowCheckin) {
            // Compare date-only strings: new Date('YYYY-MM-DD') parses as UTC
            // midnight, which shifts boundary days for UTC+ timezones (PH).
            const checkinDay = String(rowCheckin).slice(0, 10);
            dateMatch = (!dateFrom.value || checkinDay >= dateFrom.value) && (!dateTo.value || checkinDay <= dateTo.value);
        }

        return amenityMatch && statusMatch && dateMatch;
    };

    const applyFilters = () => {
        if (!reservationsTable) return;
        const rows = reservationsTable.querySelectorAll('tbody tr');
        let visible = 0;
        rows.forEach((row) => {
            const show = matchesFilter(row);
            row.style.display = show ? '' : 'none';
            if (show) visible += 1;
        });
        updateActiveText(visible, rows.length);
    };

    const printAmenityText = document.getElementById('printAmenityText');
    const printStatusText = document.getElementById('printStatusText');
    const printDateRangeText = document.getElementById('printDateRangeText');

    const updatePrintSummary = () => {
        if (!printAmenityText) return;
        printAmenityText.textContent = amenityFilter.value === 'all' ? 'All amenities' : amenityFilter.value;
        printStatusText.textContent = statusFilter.value === 'all' ? 'All statuses' : statusFilter.value;

        if (!dateFrom.value && !dateTo.value) {
            printDateRangeText.textContent = 'All dates';
        } else if (!dateFrom.value) {
            printDateRangeText.textContent = `Until ${dateTo.value}`;
        } else if (!dateTo.value) {
            printDateRangeText.textContent = `From ${dateFrom.value}`;
        } else {
            printDateRangeText.textContent = `${dateFrom.value} — ${dateTo.value}`;
        }
    };

    // ---- Quick-range preset chips + live count ----
    const presetChips = document.querySelectorAll('.preset-chip');
    const activeFilterText = document.getElementById('activeFilterText');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');

    // Local (not UTC) date formatting — toISOString() shifts dates back a day
    // for timezones east of UTC (e.g. the Philippines, UTC+8).
    const isoDate = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

    const updateActiveText = (visible, total) => {
        if (!activeFilterText) return;
        activeFilterText.textContent = visible === total
            ? 'Showing all reservations'
            : `Showing ${visible} of ${total} reservations`;
    };

    presetChips.forEach(chip => {
        chip.addEventListener('click', () => {
            presetChips.forEach(c => c.classList.remove('is-active'));
            chip.classList.add('is-active');
            const preset = chip.dataset.preset;
            const now = new Date();
            let from = null;
            let to = new Date();
            if (preset === 'today') {
                from = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            } else if (preset === '7d') {
                from = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 6);
            } else if (preset === '30d') {
                from = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 29);
            } else if (preset === 'month') {
                from = new Date(now.getFullYear(), now.getMonth(), 1);
            } else if (preset === 'all') {
                from = null;
                to = null;
            }
            if (dateFrom) dateFrom.value = from ? isoDate(from) : '';
            if (dateTo) dateTo.value = to ? isoDate(to) : '';
            applyFilters();
            updatePrintSummary();
        });
    });

    [amenityFilter, statusFilter, dateFrom, dateTo].forEach((input) => {
        input?.addEventListener('change', () => {
            // Manual edits invalidate any active quick-range preset.
            presetChips.forEach(c => c.classList.remove('is-active'));
            applyFilters();
            updatePrintSummary();
        });
    });

    printButton?.addEventListener('click', () => {
        window.print();
    });

    // ---- Reset all filters ----
    if (resetFiltersBtn) {
        const defaultFrom = dateFrom?.value || '';
        const defaultTo = dateTo?.value || '';
        resetFiltersBtn.addEventListener('click', () => {
            presetChips.forEach(c => c.classList.remove('is-active'));
            if (amenityFilter) amenityFilter.value = 'all';
            if (statusFilter) statusFilter.value = 'all';
            if (dateFrom) dateFrom.value = defaultFrom;
            if (dateTo) dateTo.value = defaultTo;
            applyFilters();
            updatePrintSummary();
        });
    }

    applyFilters();
    updatePrintSummary();
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['admin_reports']());