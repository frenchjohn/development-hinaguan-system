/**
 * Staff Shift & Activity Reports JavaScript
 * Hinaguan Nature Park - Staff Portal
 */

window.AppPage = window.AppPage || {};

window.AppPage['staff_reports'] = function () {
    // ------------------------------------------------------------
    // 1. DATE & SESSION FILTER MODAL CONTROLLER
    // ------------------------------------------------------------
    const openModalBtn          = document.getElementById('openDateFilterModalBtn');
    const closeModalBtn         = document.getElementById('closeDateFilterModalBtn');
    const cancelModalBtn        = document.getElementById('modalCancelFilterBtn');
    const applyModalBtn         = document.getElementById('modalApplyFilterBtn');
    const resetModalBtn         = document.getElementById('modalResetFilterBtn');
    const dateFilterModal       = document.getElementById('dateFilterModal');
    const dateFilterBtnLabel    = document.getElementById('dateFilterBtnLabel');
    const dateFilterActiveDot   = document.getElementById('dateFilterActiveDot');

    // Inside Modal Inputs & Presets
    const modalStartDateInput   = document.getElementById('modalStartDateInput');
    const modalEndDateInput     = document.getElementById('modalEndDateInput');
    const modalSessionSelect    = document.getElementById('modalSessionSelect');
    const presetTodayBtn        = document.getElementById('presetTodayBtn');
    const presetYesterdayBtn    = document.getElementById('presetYesterdayBtn');
    const presetThisWeekBtn     = document.getElementById('presetThisWeekBtn');
    const presetThisMonthBtn    = document.getElementById('presetThisMonthBtn');
    const presetLastMonthBtn    = document.getElementById('presetLastMonthBtn');

    // Applied Filter State
    let appliedStartDate = document.getElementById('dateFromInput')?.value || '';
    let appliedEndDate   = document.getElementById('dateToInput')?.value || '';
    let appliedSession   = document.getElementById('sessionInput')?.value || 'all';
    let appliedPreset    = document.getElementById('presetInput')?.value || 'today';

    function toISODateString(d) {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Modal Quick Preset Buttons
    presetTodayBtn?.addEventListener('click', () => {
        const today = new Date();
        if (modalStartDateInput) modalStartDateInput.value = toISODateString(today);
        if (modalEndDateInput)   modalEndDateInput.value   = '';
    });

    presetYesterdayBtn?.addEventListener('click', () => {
        const d = new Date();
        d.setDate(d.getDate() - 1);
        if (modalStartDateInput) modalStartDateInput.value = toISODateString(d);
        if (modalEndDateInput)   modalEndDateInput.value   = '';
    });

    presetThisWeekBtn?.addEventListener('click', () => {
        const now = new Date();
        const dayOfWeek = now.getDay();
        const start = new Date(now);
        start.setDate(now.getDate() - (dayOfWeek === 0 ? 6 : dayOfWeek - 1));
        const end = new Date(start);
        end.setDate(start.getDate() + 6);

        if (modalStartDateInput) modalStartDateInput.value = toISODateString(start);
        if (modalEndDateInput)   modalEndDateInput.value   = toISODateString(end);
    });

    presetThisMonthBtn?.addEventListener('click', () => {
        const now = new Date();
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        const end   = new Date(now.getFullYear(), now.getMonth() + 1, 0);

        if (modalStartDateInput) modalStartDateInput.value = toISODateString(start);
        if (modalEndDateInput)   modalEndDateInput.value   = toISODateString(end);
    });

    presetLastMonthBtn?.addEventListener('click', () => {
        const now = new Date();
        const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const end   = new Date(now.getFullYear(), now.getMonth(), 0);

        if (modalStartDateInput) modalStartDateInput.value = toISODateString(start);
        if (modalEndDateInput)   modalEndDateInput.value   = toISODateString(end);
    });

    // Date Filter Modal Open & Close
    function openModal() {
        if (!dateFilterModal) return;

        if (modalStartDateInput) modalStartDateInput.value = appliedStartDate;
        if (modalEndDateInput)   modalEndDateInput.value   = appliedEndDate;
        if (modalSessionSelect)  modalSessionSelect.value  = appliedSession || 'all';

        dateFilterModal.classList.remove('hidden');
        dateFilterModal.classList.add('flex');
    }

    function closeModal() {
        if (!dateFilterModal) return;
        dateFilterModal.classList.add('hidden');
        dateFilterModal.classList.remove('flex');
    }

    openModalBtn?.addEventListener('click', openModal);
    closeModalBtn?.addEventListener('click', closeModal);
    cancelModalBtn?.addEventListener('click', closeModal);

    // Close when clicking backdrop
    dateFilterModal?.addEventListener('click', (e) => {
        if (e.target === dateFilterModal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && dateFilterModal && !dateFilterModal.classList.contains('hidden')) {
            closeModal();
        }
    });

    // Reset inside modal
    resetModalBtn?.addEventListener('click', () => {
        if (modalStartDateInput) modalStartDateInput.value = '';
        if (modalEndDateInput)   modalEndDateInput.value   = '';
        if (modalSessionSelect)  modalSessionSelect.value  = 'all';
    });

    // Apply inside modal
    applyModalBtn?.addEventListener('click', () => {
        let startVal = (modalStartDateInput?.value ?? '').trim();
        let endVal   = (modalEndDateInput?.value   ?? '').trim();

        // Auto-correct if user provided start > end
        if (startVal && endVal && startVal > endVal) {
            const temp = startVal;
            startVal = endVal;
            endVal = temp;
        }

        appliedStartDate = startVal;
        appliedEndDate   = endVal;
        appliedSession   = modalSessionSelect?.value ?? 'all';
        appliedPreset    = (startVal || endVal) ? 'custom' : 'all';

        closeModal();
        applyFilters();
    });

    // ─── Trigger Button State Sync ───────────────────────────────────────────
    function updateTriggerButtonUI() {
        const hasDate = Boolean(appliedStartDate || appliedEndDate);
        const hasSession = Boolean(appliedSession && appliedSession !== 'all');
        const isCustomOrActive = hasDate || hasSession || (appliedPreset && appliedPreset !== 'today' && appliedPreset !== 'all');

        const btn = document.getElementById('openDateFilterModalBtn');
        const lbl = document.getElementById('dateFilterBtnLabel');
        const dot = document.getElementById('dateFilterActiveDot');

        if (!btn || !lbl) return;

        if (!isCustomOrActive) {
            lbl.textContent = 'Date & Session';
            if (dot) dot.classList.add('hidden');
            btn.classList.remove('border-emerald-500', 'bg-emerald-50/60', 'dark:bg-emerald-950/30', 'text-emerald-700', 'dark:text-emerald-300');
            return;
        }

        if (dot) dot.classList.remove('hidden');
        btn.classList.add('border-emerald-500', 'bg-emerald-50/60', 'dark:bg-emerald-950/30', 'text-emerald-700', 'dark:text-emerald-300');

        const parts = [];
        if (appliedStartDate && appliedEndDate) {
            if (appliedStartDate === appliedEndDate) {
                try {
                    const [y, m, d] = appliedStartDate.split('-');
                    const dObj = new Date(y, m - 1, d);
                    parts.push(dObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }));
                } catch {
                    parts.push(appliedStartDate);
                }
            } else {
                parts.push(`${appliedStartDate.slice(5)} to ${appliedEndDate.slice(5)}`);
            }
        } else if (appliedStartDate) {
            try {
                const [y, m, d] = appliedStartDate.split('-');
                const dObj = new Date(y, m - 1, d);
                parts.push(dObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }));
            } catch {
                parts.push(appliedStartDate);
            }
        } else if (appliedEndDate) {
            parts.push(`Until ${appliedEndDate.slice(5)}`);
        } else if (appliedPreset && appliedPreset !== 'all' && appliedPreset !== 'custom') {
            const formattedPreset = appliedPreset.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            parts.push(formattedPreset);
        }

        if (appliedSession === 'daytime') {
            parts.push('Daytime');
        } else if (appliedSession === 'nighttime' || appliedSession === 'overnight') {
            parts.push('Overnight');
        }

        lbl.textContent = parts.join(' • ') || 'Filtered';
    }

    // ------------------------------------------------------------
    // 2. SEAMLESS ASYNC FILTERING (NO FULL PAGE RELOAD)
    // ------------------------------------------------------------
    const actionSelect = document.getElementById('actionSelect');
    const reportFilterForm = document.getElementById('reportFilterForm');

    function applyFilters(overridePreset = null) {
        if (overridePreset) {
            appliedPreset = overridePreset;
        }

        const pInput = document.getElementById('presetInput');
        const dFrom = document.getElementById('dateFromInput');
        const dTo = document.getElementById('dateToInput');
        const sInput = document.getElementById('sessionInput');
        const aSelect = document.getElementById('actionSelect');

        if (pInput) pInput.value = appliedPreset;
        if (dFrom)  dFrom.value  = appliedStartDate;
        if (dTo)    dTo.value    = appliedEndDate;
        if (sInput) sInput.value = appliedSession;

        const params = new URLSearchParams();
        params.set('preset', appliedPreset || 'today');

        if (appliedSession && appliedSession !== 'all') {
            params.set('session', appliedSession);
        }
        if (aSelect && aSelect.value && aSelect.value !== 'all') {
            params.set('action', aSelect.value);
        }

        if (appliedPreset === 'custom' || (!['today', 'yesterday', 'this_week', 'this_month', 'last_month', 'all'].includes(appliedPreset) && (appliedStartDate || appliedEndDate))) {
            params.set('preset', 'custom');
            if (appliedStartDate) params.set('date_from', appliedStartDate);
            if (appliedEndDate)   params.set('date_to', appliedEndDate);
        }

        updateTriggerButtonUI();

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
        const refreshIcon = document.getElementById('refreshReportsIcon');
        const refreshBtnText = document.getElementById('refreshReportsBtnText');
        const refreshBtn = document.getElementById('manualRefreshReportsBtn');

        if (refreshIcon) {
            refreshIcon.classList.add('animate-spin');
        }
        if (refreshBtnText) {
            refreshBtnText.textContent = 'Updating...';
        }
        if (refreshBtn) {
            refreshBtn.classList.add('pointer-events-none', 'opacity-75');
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

            // 4. Swap reset filter button
            const curReset = document.getElementById('resetFilterContainer');
            const newReset = doc.getElementById('resetFilterContainer');
            if (curReset && newReset) {
                curReset.innerHTML = newReset.innerHTML;
            }

            // 5. Swap ledger modal subtitle
            const curModalSub = document.getElementById('ledgerModalSubtitle');
            const newModalSub = doc.getElementById('ledgerModalSubtitle');
            if (curModalSub && newModalSub) {
                curModalSub.innerHTML = newModalSub.innerHTML;
            }

            // 6. Swap ledger modal count display
            const curCountDisplay = document.getElementById('ledgerCountDisplay');
            const newCountDisplay = doc.getElementById('ledgerCountDisplay');
            if (curCountDisplay && newCountDisplay) {
                curCountDisplay.textContent = newCountDisplay.textContent;
            }

            // 7. Swap ledger modal table body
            const curTableBody = document.getElementById('ledgerTableBody');
            const newTableBody = doc.getElementById('ledgerTableBody');
            if (curTableBody && newTableBody) {
                curTableBody.innerHTML = newTableBody.innerHTML;
            }

            // 8. Swap ledger modal footer total
            const curFooterTotal = document.getElementById('ledgerFooterTotal');
            const newFooterTotal = doc.getElementById('ledgerFooterTotal');
            if (curFooterTotal && newFooterTotal) {
                curFooterTotal.innerHTML = newFooterTotal.innerHTML;
            }

            // 9. Swap handover slip preview
            const curHandoverPreview = document.getElementById('handoverSlipPreviewContent');
            const newHandoverPreview = doc.getElementById('handoverSlipPreviewContent');
            if (curHandoverPreview && newHandoverPreview) {
                curHandoverPreview.innerHTML = newHandoverPreview.innerHTML;
            }

            // 10. Swap printable slip
            const curPrintable = document.getElementById('printableHandoverSlip');
            const newPrintable = doc.getElementById('printableHandoverSlip');
            if (curPrintable && newPrintable) {
                curPrintable.innerHTML = newPrintable.innerHTML;
            }

            // 11. Update action dropdown from response
            const curAction = document.getElementById('actionSelect');
            const newAction = doc.getElementById('actionSelect');
            if (newAction && curAction) {
                curAction.value = newAction.value;
            }

            updateTriggerButtonUI();

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
            const refreshIcon = document.getElementById('refreshReportsIcon');
            const refreshBtnText = document.getElementById('refreshReportsBtnText');
            const refreshBtn = document.getElementById('manualRefreshReportsBtn');

            if (refreshIcon) {
                refreshIcon.classList.remove('animate-spin');
            }
            if (refreshBtnText) {
                refreshBtnText.textContent = 'Refresh';
            }
            if (refreshBtn) {
                refreshBtn.classList.remove('pointer-events-none', 'opacity-75');
            }
            if (dataContainer) {
                dataContainer.classList.remove('opacity-50', 'pointer-events-none');
            }
        });
    }

    // Reset button delegation (handles dynamic swap of reset button)
    document.addEventListener('click', function (e) {
        const resetBtn = e.target.closest('#resetStaffFiltersBtn');
        if (resetBtn) {
            e.preventDefault();
            appliedStartDate = '';
            appliedEndDate   = '';
            appliedSession   = 'all';
            appliedPreset    = 'today';

            const aSelect = document.getElementById('actionSelect');
            if (aSelect) aSelect.value = 'all';

            if (modalStartDateInput) modalStartDateInput.value = '';
            if (modalEndDateInput)   modalEndDateInput.value   = '';
            if (modalSessionSelect)  modalSessionSelect.value  = 'all';

            applyFilters('today');
        }
    });

    // Trigger async filter on Action change
    actionSelect?.addEventListener('change', function () {
        applyFilters();
    });

    // Form submit interception
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

            // Manual Refresh trigger
            if (e.target.closest('#manualRefreshReportsBtn')) {
                e.preventDefault();
                window.__staffReportsController?.fetchAndSwapReports(window.location.href, false);
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