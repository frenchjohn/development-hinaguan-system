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
            const checkinDate = new Date(checkin);
            const fromDate = dateFrom.value ? new Date(dateFrom.value) : null;
            const toDate = dateTo.value ? new Date(dateTo.value) : null;
            dateMatch = (!fromDate || checkinDate >= fromDate) && (!toDate || checkinDate <= toDate);
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

    const applyFilters = () => {
        const rows = reservationReportTable?.querySelectorAll('tbody tr') || [];
        rows.forEach((row) => {
            row.style.display = matchesFilter(row) ? '' : 'none';
        });
    };

    [customerFilter, amenityFilter, statusFilter, dateFrom, dateTo].forEach((input) => {
        if (!input) return;
        input.addEventListener('change', () => {
            applyFilters();
            updatePrintSummary();
        });
    });

    printButton?.addEventListener('click', () => window.print());

    applyFilters();
    updatePrintSummary();
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['staff_reports']());