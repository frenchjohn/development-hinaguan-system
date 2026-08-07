window.AppPage = window.AppPage || {};
window.AppPage['staff_reports'] = function () {


    const customerFilter = document.getElementById('customerFilter');
    const amenityFilter = document.getElementById('amenityFilter');
    const statusFilter = document.getElementById('statusFilter');
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    const reservationReportTable = document.getElementById('reservationReportTable');
    const printButton = document.getElementById('printReportsButton');
    const printCustomerText = document.getElementById('printCustomerText');
    const printAmenityText = document.getElementById('printAmenityText');
    const printStatusText = document.getElementById('printStatusText');
    const printDateRangeText = document.getElementById('printDateRangeText');

    const matchesFilter = (row) => {
        const customer = row.dataset.customer?.toLowerCase() || '';
        const amenity = row.dataset.amenity?.toLowerCase() || '';
        const status = row.dataset.status?.toLowerCase() || '';
        const checkin = row.dataset.checkin;

        const customerMatch = customerFilter.value === 'all' || customer.includes(customerFilter.value.toLowerCase());
        const amenityMatch = amenityFilter.value === 'all' || amenity.includes(amenityFilter.value.toLowerCase());
        const statusMatch = statusFilter.value === 'all' || status === statusFilter.value.toLowerCase();

        let dateMatch = true;
        if (checkin) {
            // Compare date-only strings: new Date('YYYY-MM-DD') parses as UTC
            // midnight, which shifts boundary days for UTC+ timezones (PH).
            const checkinDay = String(checkin).slice(0, 10);
            dateMatch = (!dateFrom.value || checkinDay >= dateFrom.value) && (!dateTo.value || checkinDay <= dateTo.value);
        }

        return customerMatch && amenityMatch && statusMatch && dateMatch;
    };

    const updatePrintSummary = () => {
        printCustomerText.textContent = customerFilter.value === 'all' ? 'All customers' : customerFilter.value;
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

    const kpiReservations = document.getElementById('kpiReservations');
    const kpiRevenue = document.getElementById('kpiRevenue');

    const formatMoney = (value) => '₱' + Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const applyFilters = () => {
        const rows = reservationReportTable?.querySelectorAll('tbody tr') || [];
        let visibleCount = 0;
        let visibleRevenue = 0;

        rows.forEach((row) => {
            const visible = matchesFilter(row);
            row.style.display = visible ? '' : 'none';
            if (visible) {
                visibleCount += 1;
                visibleRevenue += Number(row.dataset.amount || 0);
            }
        });

        // Keep the KPI cards in sync with the visible rows.
        if (kpiReservations) kpiReservations.textContent = visibleCount;
        if (kpiRevenue) kpiRevenue.textContent = formatMoney(visibleRevenue);

        // Keep the summary chip in sync too.
        updateActiveText(visibleCount, rows.length);
    };

    [customerFilter, amenityFilter, statusFilter, dateFrom, dateTo].forEach((input) => {
        if (!input) return;
        input.addEventListener('change', () => {
            // Manual edits invalidate any active quick-range preset.
            document.querySelectorAll('.preset-chip').forEach(c => c.classList.remove('is-active'));
            applyFilters();
            updatePrintSummary();
        });
    });

    printButton?.addEventListener('click', () => window.print());

    // ---- Quick-range preset chips ----
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
            } else {
                from = null;
                to = null;
            }
            if (dateFrom) dateFrom.value = from ? isoDate(from) : '';
            if (dateTo) dateTo.value = to ? isoDate(to) : '';
            applyFilters();
            updatePrintSummary();
        });
    });

    // ---- Reset all filters ----
    if (resetFiltersBtn) {
        const defaultFrom = dateFrom?.value || '';
        const defaultTo = dateTo?.value || '';
        resetFiltersBtn.addEventListener('click', () => {
            presetChips.forEach(c => c.classList.remove('is-active'));
            if (customerFilter) customerFilter.value = 'all';
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

document.addEventListener('DOMContentLoaded', () => window.AppPage['staff_reports']());