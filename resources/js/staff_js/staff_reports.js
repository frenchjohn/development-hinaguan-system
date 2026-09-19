/**
 * Staff Shift & Activity Reports JavaScript
 * Hinaguan Nature Park - Staff Portal
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Instant Ledger Table Search
    const searchInput = document.getElementById('ledgerSearchInput');
    const tableBody = document.getElementById('ledgerTableBody');
    const countDisplay = document.getElementById('ledgerCountDisplay');

    if (searchInput && tableBody) {
        const rows = Array.from(tableBody.querySelectorAll('.ledger-row'));

        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            let visibleCount = 0;

            rows.forEach(function (row) {
                const searchData = row.getAttribute('data-search') || '';
                if (!query || searchData.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Update count indicator
            if (countDisplay) {
                countDisplay.textContent = `Showing ${visibleCount} of ${rows.length} transaction(s)`;
            }

            // Show empty search state if none found
            let emptyMsgRow = document.getElementById('searchEmptyRow');
            if (visibleCount === 0 && rows.length > 0) {
                if (!emptyMsgRow) {
                    emptyMsgRow = document.createElement('tr');
                    emptyMsgRow.id = 'searchEmptyRow';
                    emptyMsgRow.innerHTML = `
                        <td colspan="6" class="py-6 text-center text-hp-text-muted text-xs">
                            No transactions matching "<strong>${escapeHtml(query)}</strong>".
                        </td>
                    `;
                    tableBody.appendChild(emptyMsgRow);
                }
            } else if (emptyMsgRow) {
                emptyMsgRow.remove();
            }
        });
    }

    // 2. Preset Date Chips auto-updating inputs when custom dates changed
    const dateFrom = document.getElementById('dateFromInput');
    const dateTo = document.getElementById('dateToInput');
    const presetInput = document.getElementById('presetInput');

    if (dateFrom && dateTo && presetInput) {
        const onCustomDateChange = function () {
            presetInput.value = 'custom';
            document.querySelectorAll('.report-chip').forEach(function (chip) {
                chip.classList.remove('is-active');
            });
        };
        dateFrom.addEventListener('change', onCustomDateChange);
        dateTo.addEventListener('change', onCustomDateChange);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});