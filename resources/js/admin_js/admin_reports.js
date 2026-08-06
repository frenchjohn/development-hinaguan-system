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
            const checkinDate = new Date(rowCheckin);
            const fromDate = new Date(dateFrom.value);
            const toDate = new Date(dateTo.value);
            dateMatch = (!dateFrom.value || checkinDate >= fromDate) && (!dateTo.value || checkinDate <= toDate);
        }

        return amenityMatch && statusMatch && dateMatch;
    };

    const applyFilters = () => {
        if (!reservationsTable) return;
        const rows = reservationsTable.querySelectorAll('tbody tr');
        rows.forEach((row) => {
            row.style.display = matchesFilter(row) ? '' : 'none';
        });
    };

    const printAmenityText = document.getElementById('printAmenityText');
    const printStatusText = document.getElementById('printStatusText');
    const printDateRangeText = document.getElementById('printDateRangeText');

    const updatePrintSummary = () => {
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

    [amenityFilter, statusFilter, dateFrom, dateTo].forEach((input) => {
        input.addEventListener('change', () => {
            applyFilters();
            updatePrintSummary();
        });
    });

    printButton.addEventListener('click', () => {
        window.print();
    });

    applyFilters();
    updatePrintSummary();
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['admin_reports']());