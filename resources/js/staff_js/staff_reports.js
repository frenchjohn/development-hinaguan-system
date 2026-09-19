/**
 * Staff Shift & Activity Reports JavaScript
 * Hinaguan Nature Park - Staff Portal
 */

function initStaffReports() {
    // ------------------------------------------------------------
    // 1. COLLAPSIBLE CUSTOM DATE RANGE TOGGLE
    // ------------------------------------------------------------
    const toggleDateRangeBtn = document.getElementById('toggleDateRangeBtn');
    const customDateRangeRow = document.getElementById('customDateRangeRow');
    const dateRangeChevron = document.getElementById('dateRangeChevron');

    if (toggleDateRangeBtn && customDateRangeRow) {
        toggleDateRangeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const isHidden = customDateRangeRow.classList.contains('hidden');
            if (isHidden) {
                customDateRangeRow.classList.remove('hidden');
                customDateRangeRow.classList.add('flex');
                dateRangeChevron?.classList.add('rotate-180');
            } else {
                customDateRangeRow.classList.add('hidden');
                customDateRangeRow.classList.remove('flex');
                dateRangeChevron?.classList.remove('rotate-180');
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
        };
        dateFrom.addEventListener('change', onCustomDateChange);
        dateTo.addEventListener('change', onCustomDateChange);
    }

    // ------------------------------------------------------------
    // 2. SEAMLESS ASYNC FILTERING (NO FULL PAGE RELOAD)
    // ------------------------------------------------------------
    const sessionSelect = document.getElementById('sessionSelect');
    const actionSelect = document.getElementById('actionSelect');
    const reportFilterForm = document.getElementById('reportFilterForm');

    function applyFilters(overridePreset = null) {
        const pInput = document.getElementById('presetInput');
        const sSelect = document.getElementById('sessionSelect');
        const aSelect = document.getElementById('actionSelect');
        const dFrom = document.getElementById('dateFromInput');
        const dTo = document.getElementById('dateToInput');

        if (overridePreset && pInput) {
            pInput.value = overridePreset;
        }

        const currentPreset = pInput ? pInput.value : 'today';
        const params = new URLSearchParams();
        params.set('preset', currentPreset);

        if (sSelect && sSelect.value) {
            params.set('session', sSelect.value);
        }
        if (aSelect && aSelect.value) {
            params.set('action', aSelect.value);
        }

        if (currentPreset === 'custom') {
            if (dFrom && dFrom.value) params.set('date_from', dFrom.value);
            if (dTo && dTo.value) params.set('date_to', dTo.value);
        }

        const targetUrl = window.location.pathname + '?' + params.toString();
        fetchAndSwapReports(targetUrl, true);
    }

    let currentAbortController = null;

    function fetchAndSwapReports(url, pushState = true) {
        if (currentAbortController) {
            currentAbortController.abort();
        }
        currentAbortController = new AbortController();
        const { signal } = currentAbortController;

        const dataContainer = document.getElementById('reportsDataContainer');
        if (dataContainer) {
            dataContainer.classList.add('opacity-50', 'pointer-events-none');
        }

        fetch(url, {
            signal,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response failed: ' + response.status);
            return response.text();
        })
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // 1. Swap main data container (KPI cards + Shift Reconciliation)
            const newDataContainer = doc.getElementById('reportsDataContainer');
            if (dataContainer && newDataContainer) {
                dataContainer.innerHTML = newDataContainer.innerHTML;
            }

            // 2. Swap viewing header text
            const curViewing = document.getElementById('filterViewingText');
            const newViewing = doc.getElementById('filterViewingText');
            if (curViewing && newViewing) {
                curViewing.innerHTML = newViewing.innerHTML;
            }

            // 3. Swap ledger badge count
            const curBadge = document.getElementById('ledgerCountBadge');
            const newBadge = doc.getElementById('ledgerCountBadge');
            if (curBadge && newBadge) {
                curBadge.textContent = newBadge.textContent;
            }

            // 4. Swap period pills
            const curPills = document.getElementById('periodPillsContainer');
            const newPills = doc.getElementById('periodPillsContainer');
            if (curPills && newPills) {
                curPills.innerHTML = newPills.innerHTML;
            }

            // 5. Swap reset filter button
            const curReset = document.getElementById('resetFilterContainer');
            const newReset = doc.getElementById('resetFilterContainer');
            if (curReset && newReset) {
                curReset.innerHTML = newReset.innerHTML;
            }

            // 6. Swap ledger modal subtitle
            const curModalSub = document.getElementById('ledgerModalSubtitle');
            const newModalSub = doc.getElementById('ledgerModalSubtitle');
            if (curModalSub && newModalSub) {
                curModalSub.innerHTML = newModalSub.innerHTML;
            }

            // 7. Swap ledger modal count display
            const curCountDisplay = document.getElementById('ledgerCountDisplay');
            const newCountDisplay = doc.getElementById('ledgerCountDisplay');
            if (curCountDisplay && newCountDisplay) {
                curCountDisplay.textContent = newCountDisplay.textContent;
            }

            // 8. Swap ledger modal table body
            const curTableBody = document.getElementById('ledgerTableBody');
            const newTableBody = doc.getElementById('ledgerTableBody');
            if (curTableBody && newTableBody) {
                curTableBody.innerHTML = newTableBody.innerHTML;
            }

            // 9. Swap ledger modal footer total
            const curFooterTotal = document.getElementById('ledgerFooterTotal');
            const newFooterTotal = doc.getElementById('ledgerFooterTotal');
            if (curFooterTotal && newFooterTotal) {
                curFooterTotal.innerHTML = newFooterTotal.innerHTML;
            }

            // 10. Swap handover slip preview
            const curHandoverPreview = document.getElementById('handoverSlipPreviewContent');
            const newHandoverPreview = doc.getElementById('handoverSlipPreviewContent');
            if (curHandoverPreview && newHandoverPreview) {
                curHandoverPreview.innerHTML = newHandoverPreview.innerHTML;
            }

            // 11. Swap printable slip
            const curPrintable = document.getElementById('printableHandoverSlip');
            const newPrintable = doc.getElementById('printableHandoverSlip');
            if (curPrintable && newPrintable) {
                curPrintable.innerHTML = newPrintable.innerHTML;
            }

            // 12. Update dropdown selections and inputs from response doc
            const newSessionSelect = doc.getElementById('sessionSelect');
            if (newSessionSelect && sessionSelect) {
                sessionSelect.value = newSessionSelect.value;
            }
            const newActionSelect = doc.getElementById('actionSelect');
            if (newActionSelect && actionSelect) {
                actionSelect.value = newActionSelect.value;
            }
            const newPresetInput = doc.getElementById('presetInput');
            if (newPresetInput && presetInput) {
                presetInput.value = newPresetInput.value;
            }

            // Refresh search rows in ledger modal
            initLedgerSearch();

            if (pushState) {
                window.history.pushState({ url: url }, '', url);
            }
        })
        .catch(err => {
            if (err.name === 'AbortError') {
                return;
            }
            console.error('Error fetching filtered reports:', err);
        })
        .finally(() => {
            if (dataContainer && (!currentAbortController || !currentAbortController.signal.aborted)) {
                dataContainer.classList.remove('opacity-50', 'pointer-events-none');
            }
        });
    }

    // Trigger async filter on Shift or Action change
    sessionSelect?.addEventListener('change', function () {
        applyFilters();
    });

    actionSelect?.addEventListener('change', function () {
        applyFilters();
    });

    // Form submit interception (e.g. Filter button or Apply Range)
    reportFilterForm?.addEventListener('submit', function (e) {
        e.preventDefault();
        applyFilters();
    });

    // Delegated click handling for period pills and reset buttons
    document.addEventListener('click', function (e) {
        // Period pill click
        const pill = e.target.closest('[data-preset]');
        if (pill) {
            e.preventDefault();
            const targetPreset = pill.getAttribute('data-preset');
            applyFilters(targetPreset);
            return;
        }

        // Reset filters click
        const resetBtn = e.target.closest('[data-reset-filter]');
        if (resetBtn) {
            e.preventDefault();
            if (sessionSelect) sessionSelect.value = 'all';
            if (actionSelect) actionSelect.value = 'all';
            if (dateFrom) dateFrom.value = '';
            if (dateTo) dateTo.value = '';
            applyFilters('today');
            return;
        }

        // Ledger Modal triggers (delegated to support dynamically swapped elements)
        if (e.target.closest('#openLedgerBtn') || e.target.closest('.open-ledger-trigger')) {
            e.preventDefault();
            openLedgerModal();
            return;
        }

        // Handover Modal triggers
        if (e.target.closest('#openHandoverModalBtn') || e.target.closest('.open-handover-trigger')) {
            e.preventDefault();
            openHandoverModal();
            return;
        }
    });

    // Handle browser back/forward navigation
    window.addEventListener('popstate', function () {
        fetchAndSwapReports(window.location.href, false);
    });

    // ------------------------------------------------------------
    // 3. SHIFT ACTIVITY & PAYMENT LEDGER MODAL
    // ------------------------------------------------------------
    const ledgerModal = document.getElementById('ledgerModal');
    const closeLedgerModalBtn = document.getElementById('closeLedgerModalBtn');
    const closeLedgerModalBtnFooter = document.getElementById('closeLedgerModalBtnFooter');

    const openLedgerModal = function () {
        if (!ledgerModal) return;
        ledgerModal.classList.remove('hidden');
        ledgerModal.classList.add('flex');
        ledgerModal.setAttribute('aria-hidden', 'false');
        const searchInput = document.getElementById('ledgerSearchInput');
        if (searchInput) {
            setTimeout(() => {
                searchInput.focus({ preventScroll: true });
            }, 80);
        }
    };

    const closeLedgerModal = function () {
        if (!ledgerModal) return;
        ledgerModal.classList.add('hidden');
        ledgerModal.classList.remove('flex');
        ledgerModal.setAttribute('aria-hidden', 'true');
    };

    closeLedgerModalBtn?.addEventListener('click', closeLedgerModal);
    closeLedgerModalBtnFooter?.addEventListener('click', closeLedgerModal);

    // Close when clicking modal backdrop
    ledgerModal?.addEventListener('click', function (e) {
        if (e.target === ledgerModal || e.target.hasAttribute('data-close-ledger-modal')) {
            closeLedgerModal();
        }
    });

    // Instant Search inside Ledger Modal
    let ledgerSearchHandler = null;

    function initLedgerSearch() {
        const searchInput = document.getElementById('ledgerSearchInput');
        const tableBody = document.getElementById('ledgerTableBody');
        const countDisplay = document.getElementById('ledgerCountDisplay');

        if (!searchInput || !tableBody) return;

        const rows = Array.from(tableBody.querySelectorAll('.ledger-row'));

        // Remove previous listener if exists
        if (ledgerSearchHandler) {
            searchInput.removeEventListener('input', ledgerSearchHandler);
        }

        ledgerSearchHandler = function () {
            const query = searchInput.value.trim().toLowerCase();
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
        };

        searchInput.addEventListener('input', ledgerSearchHandler);

        // If search input already has a value, re-apply filter on new rows
        if (searchInput.value.trim() !== '') {
            ledgerSearchHandler();
        }
    }

    initLedgerSearch();

    // ------------------------------------------------------------
    // 4. HANDOVER SLIP PREVIEW & PRINT MODAL
    // ------------------------------------------------------------
    const handoverModal = document.getElementById('handoverModal');
    const closeHandoverModalBtn = document.getElementById('closeHandoverModalBtn');
    const closeHandoverModalBtnFooter = document.getElementById('closeHandoverModalBtnFooter');
    const printHandoverSlipBtn = document.getElementById('printHandoverSlipBtn');

    const openHandoverModal = function () {
        if (!handoverModal) return;
        handoverModal.classList.remove('hidden');
        handoverModal.classList.add('flex');
        handoverModal.setAttribute('aria-hidden', 'false');
    };

    const closeHandoverModal = function () {
        if (!handoverModal) return;
        handoverModal.classList.add('hidden');
        handoverModal.classList.remove('flex');
        handoverModal.setAttribute('aria-hidden', 'true');
    };

    closeHandoverModalBtn?.addEventListener('click', closeHandoverModal);
    closeHandoverModalBtnFooter?.addEventListener('click', closeHandoverModal);

    // Close when clicking modal backdrop
    handoverModal?.addEventListener('click', function (e) {
        if (e.target === handoverModal || e.target.hasAttribute('data-close-handover-modal')) {
            closeHandoverModal();
        }
    });

    // Trigger Print
    printHandoverSlipBtn?.addEventListener('click', function () {
        window.print();
    });

    // ------------------------------------------------------------
    // 5. GLOBAL KEYBOARD SHORTCUTS
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
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStaffReports);
} else {
    initStaffReports();
}