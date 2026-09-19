/**
 * Staff Shift & Activity Reports JavaScript
 * Hinaguan Nature Park - Staff Portal
 */

document.addEventListener('DOMContentLoaded', function () {
    // ------------------------------------------------------------
    // 1. COLLAPSIBLE FILTER ACCORDION (Default is closed)
    // ------------------------------------------------------------
    const filterToggleBtn = document.getElementById('filterToggleBtn');
    const filterContent = document.getElementById('filterContent');
    const filterChevron = document.getElementById('filterChevron');

    if (filterToggleBtn && filterContent) {
        filterToggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const isClosed = filterContent.classList.contains('hidden');
            if (isClosed) {
                filterContent.classList.remove('hidden');
                filterChevron?.classList.add('rotate-180');
            } else {
                filterContent.classList.add('hidden');
                filterChevron?.classList.remove('rotate-180');
            }
        });
    }

    // Auto-switch preset input to 'custom' when dates change
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

    // ------------------------------------------------------------
    // 2. SHIFT ACTIVITY & PAYMENT LEDGER MODAL
    // ------------------------------------------------------------
    const ledgerModal = document.getElementById('ledgerModal');
    const openLedgerBtn = document.getElementById('openLedgerBtn');
    const closeLedgerModalBtn = document.getElementById('closeLedgerModalBtn');
    const closeLedgerModalBtnFooter = document.getElementById('closeLedgerModalBtnFooter');
    const openLedgerTriggers = document.querySelectorAll('.open-ledger-trigger');

    const openLedgerModal = function () {
        if (!ledgerModal) return;
        ledgerModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        const searchInput = document.getElementById('ledgerSearchInput');
        if (searchInput) {
            setTimeout(() => searchInput.focus(), 100);
        }
    };

    const closeLedgerModal = function () {
        if (!ledgerModal) return;
        ledgerModal.classList.add('hidden');
        document.body.style.overflow = '';
    };

    openLedgerBtn?.addEventListener('click', openLedgerModal);
    openLedgerTriggers.forEach(btn => btn.addEventListener('click', openLedgerModal));
    closeLedgerModalBtn?.addEventListener('click', closeLedgerModal);
    closeLedgerModalBtnFooter?.addEventListener('click', closeLedgerModal);

    // Close when clicking modal backdrop
    ledgerModal?.addEventListener('click', function (e) {
        if (e.target === ledgerModal) {
            closeLedgerModal();
        }
    });

    // Instant Search inside Ledger Modal
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

            if (countDisplay) {
                countDisplay.textContent = `Showing ${visibleCount} of ${rows.length} transaction(s)`;
            }

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

    // ------------------------------------------------------------
    // 3. HANDOVER SLIP PREVIEW & PRINT MODAL
    // ------------------------------------------------------------
    const handoverModal = document.getElementById('handoverModal');
    const openHandoverModalBtn = document.getElementById('openHandoverModalBtn');
    const closeHandoverModalBtn = document.getElementById('closeHandoverModalBtn');
    const closeHandoverModalBtnFooter = document.getElementById('closeHandoverModalBtnFooter');
    const printHandoverSlipBtn = document.getElementById('printHandoverSlipBtn');

    const openHandoverModal = function () {
        if (!handoverModal) return;
        handoverModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };

    const closeHandoverModal = function () {
        if (!handoverModal) return;
        handoverModal.classList.add('hidden');
        document.body.style.overflow = '';
    };

    openHandoverModalBtn?.addEventListener('click', openHandoverModal);
    closeHandoverModalBtn?.addEventListener('click', closeHandoverModal);
    closeHandoverModalBtnFooter?.addEventListener('click', closeHandoverModal);

    // Close when clicking modal backdrop
    handoverModal?.addEventListener('click', function (e) {
        if (e.target === handoverModal) {
            closeHandoverModal();
        }
    });

    // Trigger Print
    printHandoverSlipBtn?.addEventListener('click', function () {
        window.print();
    });

    // ------------------------------------------------------------
    // 4. GLOBAL KEYBOARD SHORTCUTS
    // ------------------------------------------------------------
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (ledgerModal && !ledgerModal.classList.contains('hidden')) {
                closeLedgerModal();
            }
            if (handoverModal && !handoverModal.classList.contains('hidden')) {
                closeHandoverModal();
            }
        }
    });

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});