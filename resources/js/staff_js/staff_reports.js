/**
 * Staff Shift & Activity Reports JavaScript
 * Hinaguan Nature Park - Staff Portal
 */

window.AppPage = window.AppPage || {};

window.AppPage['staff_reports'] = function () {
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
            const curSession = document.getElementById('sessionSelect');
            const newSession = doc.getElementById('sessionSelect');
            if (newSession && curSession) {
                curSession.value = newSession.value;
            }
            const curAction = document.getElementById('actionSelect');
            const newAction = doc.getElementById('actionSelect');
            if (newAction && curAction) {
                curAction.value = newAction.value;
            }
            const curPreset = document.getElementById('presetInput');
            const newPreset = doc.getElementById('presetInput');
            if (newPreset && curPreset) {
                curPreset.value = newPreset.value;
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

    // ------------------------------------------------------------
    // 3. SHIFT ACTIVITY & PAYMENT LEDGER MODAL
    // ------------------------------------------------------------
    const ledgerModal = document.getElementById('ledgerModal');
    const closeLedgerModalBtn = document.getElementById('closeLedgerModalBtn');
    const closeLedgerModalBtnFooter = document.getElementById('closeLedgerModalBtnFooter');

    const openLedgerModal = function () {
        const m = document.getElementById('ledgerModal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
        m.setAttribute('aria-hidden', 'false');
        const searchInput = document.getElementById('ledgerSearchInput');
        if (searchInput) {
            setTimeout(() => {
                searchInput.focus({ preventScroll: true });
            }, 80);
        }
    };

    const closeLedgerModal = function () {
        const m = document.getElementById('ledgerModal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
        m.setAttribute('aria-hidden', 'true');
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
        const m = document.getElementById('handoverModal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
        m.setAttribute('aria-hidden', 'false');
    };

    const closeHandoverModal = function () {
        const m = document.getElementById('handoverModal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
        m.setAttribute('aria-hidden', 'true');
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

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Expose controller methods for delegated global listeners
    window.__staffReportsController = {
        applyFilters,
        openLedgerModal,
        closeLedgerModal,
        openHandoverModal,
        closeHandoverModal,
        fetchAndSwapReports,
    };

    // Attach document/window level listeners only once
    if (!window.__staffReportsGlobalListenersAttached) {
        window.__staffReportsGlobalListenersAttached = true;

        // Delegated click handling for period pills, reset, and modal triggers
        document.addEventListener('click', function (e) {
            // Period pill click
            const pill = e.target.closest('[data-preset]');
            if (pill) {
                e.preventDefault();
                const targetPreset = pill.getAttribute('data-preset');
                window.__staffReportsController?.applyFilters(targetPreset);
                return;
            }

            // Reset filters click
            const resetBtn = e.target.closest('[data-reset-filter]');
            if (resetBtn) {
                e.preventDefault();
                const sSelect = document.getElementById('sessionSelect');
                const aSelect = document.getElementById('actionSelect');
                const dFrom = document.getElementById('dateFromInput');
                const dTo = document.getElementById('dateToInput');
                if (sSelect) sSelect.value = 'all';
                if (aSelect) aSelect.value = 'all';
                if (dFrom) dFrom.value = '';
                if (dTo) dTo.value = '';
                window.__staffReportsController?.applyFilters('today');
                return;
            }

            // Ledger Modal triggers
            if (e.target.closest('#openLedgerBtn') || e.target.closest('.open-ledger-trigger')) {
                e.preventDefault();
                window.__staffReportsController?.openLedgerModal();
                return;
            }

            // Handover Modal triggers
            if (e.target.closest('#openHandoverModalBtn') || e.target.closest('.open-handover-trigger')) {
                e.preventDefault();
                window.__staffReportsController?.openHandoverModal();
                return;
            }
        });

        // Browser back/forward navigation within reports page
        window.addEventListener('popstate', function () {
            if (window.location.pathname === '/staff/reports') {
                window.__staffReportsController?.fetchAndSwapReports(window.location.href, false);
            }
        });

        // Global Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const lm = document.getElementById('ledgerModal');
                if (lm && !lm.classList.contains('hidden')) {
                    window.__staffReportsController?.closeLedgerModal();
                }
                const hm = document.getElementById('handoverModal');
                if (hm && !hm.classList.contains('hidden')) {
                    window.__staffReportsController?.closeHandoverModal();
                }
            }
        });
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.AppPage['staff_reports'] === 'function') {
            window.AppPage['staff_reports']();
        }
    });
} else {
    if (typeof window.AppPage['staff_reports'] === 'function') {
        window.AppPage['staff_reports']();
    }
}