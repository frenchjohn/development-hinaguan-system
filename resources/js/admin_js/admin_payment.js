window.AppPage = window.AppPage || {};
window.AppPage['admin_payment'] = function () {
    // ─── Element refs ────────────────────────────────────────────────────────
    const searchInput           = document.getElementById('paymentSearchInput');
    const typeSelect            = document.getElementById('typeFilterSelect');
    const staffSelect           = document.getElementById('staffFilterSelect');
    const clearBtn              = document.getElementById('clearFiltersBtn');
    const clearBtnAlt           = document.getElementById('clearFiltersBtnAlt');
    const noResultsRow          = document.getElementById('paymentNoResultsRow');
    const visibleCountEl        = document.getElementById('visibleCount');
    const visibleRangeEl        = document.getElementById('visibleRange');
    const pageIndicatorEl       = document.getElementById('pageIndicator');
    const tableContainer        = document.getElementById('paymentTableContainer');

    // Date & Session Modal Trigger & Elements
    const openModalBtn          = document.getElementById('openDateFilterModalBtn');
    const closeModalBtn         = document.getElementById('closeDateFilterModalBtn');
    const cancelModalBtn        = document.getElementById('modalCancelFilterBtn');
    const applyModalBtn         = document.getElementById('modalApplyFilterBtn');
    const resetModalBtn         = document.getElementById('modalResetFilterBtn');
    const dateFilterModal       = document.getElementById('dateFilterModal');
    const dateFilterBtnLabel    = document.getElementById('dateFilterBtnLabel');
    const dateFilterActiveDot   = document.getElementById('dateFilterActiveDot');

    // Inside Modal
    const modalStartDateInput   = document.getElementById('modalStartDateInput');
    const modalEndDateInput     = document.getElementById('modalEndDateInput');
    const modalSessionSelect    = document.getElementById('modalSessionSelect');
    const presetTodayBtn        = document.getElementById('presetTodayBtn');
    const presetYesterdayBtn    = document.getElementById('presetYesterdayBtn');
    const presetThisWeekBtn     = document.getElementById('presetThisWeekBtn');
    const presetThisMonthBtn    = document.getElementById('presetThisMonthBtn');
    const presetLastMonthBtn    = document.getElementById('presetLastMonthBtn');

    // Pagination refs
    const paginationWrap        = document.getElementById('paymentPaginationWrap');
    const paginationRangeEl     = document.getElementById('paymentPaginationRange');
    const paginationTotalEl     = document.getElementById('paymentPaginationTotal');
    const prevPageBtn           = document.getElementById('paymentPrevPageBtn');
    const nextPageBtn           = document.getElementById('paymentNextPageBtn');
    const pageNumbersContainer  = document.getElementById('paymentPageNumbers');

    /** All data rows (static, rendered by Blade). */
    const allRows = Array.from(document.querySelectorAll('.payment-row'));
    const totalCount = allRows.length;

    /** Pagination & Filter state */
    const PAGE_SIZE = 100;
    let currentPage = window.__adminPaymentCurrentPage || 1;
    let matchedRows = [];

    // Applied Date & Session State
    let appliedStartDate = modalStartDateInput?.value?.trim() || '';
    let appliedEndDate   = modalEndDateInput?.value?.trim() || '';
    let appliedSession   = modalSessionSelect?.value || '';

    // ─── Quick Presets Helpers ───────────────────────────────────────────────

    function toISODateString(d) {
        const year  = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day   = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    presetTodayBtn?.addEventListener('click', () => {
        if (modalStartDateInput) modalStartDateInput.value = toISODateString(new Date());
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

    // ─── Modal Open & Close ──────────────────────────────────────────────────

    function openModal() {
        if (!dateFilterModal) return;

        if (modalStartDateInput) modalStartDateInput.value = appliedStartDate;
        if (modalEndDateInput)   modalEndDateInput.value   = appliedEndDate;
        if (modalSessionSelect)  modalSessionSelect.value  = appliedSession;

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

    // Reset inside modal
    resetModalBtn?.addEventListener('click', () => {
        if (modalStartDateInput) modalStartDateInput.value = '';
        if (modalEndDateInput)   modalEndDateInput.value   = '';
        if (modalSessionSelect)  modalSessionSelect.value  = '';
    });

    // Apply inside modal
    applyModalBtn?.addEventListener('click', () => {
        let startVal = (modalStartDateInput?.value ?? '').trim();
        let endVal   = (modalEndDateInput?.value   ?? '').trim();

        // If user provided both dates and swapped start/end, auto-correct
        if (startVal && endVal && startVal > endVal) {
            const temp = startVal;
            startVal = endVal;
            endVal = temp;
        }

        appliedStartDate = startVal;
        appliedEndDate   = endVal;
        appliedSession   = modalSessionSelect?.value ?? '';

        updateTriggerButtonUI();
        closeModal();
        applyFilters(true);
    });

    // ─── Trigger Button State Sync ───────────────────────────────────────────

    function updateTriggerButtonUI() {
        const hasDate = appliedStartDate !== '' || appliedEndDate !== '';
        const hasSession = appliedSession !== '';
        const isActive = hasDate || hasSession;

        if (!isActive) {
            if (dateFilterBtnLabel)  dateFilterBtnLabel.textContent = 'Date & Session';
            if (dateFilterActiveDot) dateFilterActiveDot.classList.add('hidden');
            openModalBtn?.classList.remove('border-emerald-500', 'bg-emerald-50/60', 'dark:bg-emerald-950/30', 'text-emerald-700', 'dark:text-emerald-300');
            return;
        }

        if (dateFilterActiveDot) dateFilterActiveDot.classList.remove('hidden');
        openModalBtn?.classList.add('border-emerald-500', 'bg-emerald-50/60', 'dark:bg-emerald-950/30', 'text-emerald-700', 'dark:text-emerald-300');

        const parts = [];
        if (appliedStartDate && appliedEndDate) {
            if (appliedStartDate === appliedEndDate) {
                try {
                    const [y, m, d] = appliedStartDate.split('-');
                    const dObj = new Date(y, m - 1, d);
                    parts.push(dObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                } catch {
                    parts.push(appliedStartDate);
                }
            } else {
                parts.push(`${appliedStartDate.slice(5)} to ${appliedEndDate.slice(5)}`);
            }
        } else if (appliedStartDate) {
            // Single date
            try {
                const [y, m, d] = appliedStartDate.split('-');
                const dObj = new Date(y, m - 1, d);
                parts.push(dObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
            } catch {
                parts.push(appliedStartDate);
            }
        } else if (appliedEndDate) {
            parts.push(`Until ${appliedEndDate.slice(5)}`);
        }

        if (appliedSession === 'daytime') {
            parts.push('Daytime');
        } else if (appliedSession === 'overnight') {
            parts.push('Overnight');
        }

        if (dateFilterBtnLabel) {
            dateFilterBtnLabel.textContent = parts.join(' • ') || 'Filtered';
        }
    }

    // ─── Pagination Helpers ──────────────────────────────────────────────────

    function getPageNumbers(current, total) {
        if (total <= 7) {
            return Array.from({ length: total }, (_, i) => i + 1);
        }
        if (current <= 4) {
            return [1, 2, 3, 4, 5, '...', total];
        }
        if (current >= total - 3) {
            return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
        }
        return [1, '...', current - 1, current, current + 1, '...', total];
    }

    function goToPage(page, scrollReset = true) {
        const totalPages = Math.max(1, Math.ceil(matchedRows.length / PAGE_SIZE));
        if (page < 1 || page > totalPages) return;
        currentPage = page;
        window.__adminPaymentCurrentPage = page;
        applyDisplay();
        if (scrollReset && tableContainer) {
            tableContainer.scrollTop = 0;
        }
    }

    function applyDisplay() {
        const totalFiltered = matchedRows.length;
        const totalPages = Math.max(1, Math.ceil(totalFiltered / PAGE_SIZE));

        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        const startIndex = (currentPage - 1) * PAGE_SIZE;
        const endIndex   = startIndex + PAGE_SIZE;

        allRows.forEach(row => {
            const matchedIdx = matchedRows.indexOf(row);
            if (matchedIdx !== -1 && matchedIdx >= startIndex && matchedIdx < endIndex) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        renderPagination(totalFiltered, totalPages, startIndex, endIndex);
    }

    function renderPagination(totalFiltered, totalPages, startIndex, endIndex) {
        if (!paginationWrap) return;

        if (totalFiltered === 0) {
            if (paginationRangeEl) paginationRangeEl.textContent = '0';
            if (paginationTotalEl) paginationTotalEl.textContent = '0';
            if (visibleRangeEl)    visibleRangeEl.textContent    = '0';
            if (pageIndicatorEl)   pageIndicatorEl.textContent   = '';
            if (prevPageBtn)       prevPageBtn.disabled          = true;
            if (nextPageBtn)       nextPageBtn.disabled          = true;
            if (pageNumbersContainer) pageNumbersContainer.innerHTML = '';
            return;
        }

        const actualEnd = Math.min(endIndex, totalFiltered);
        const rangeText = (startIndex + 1 === actualEnd)
            ? `${startIndex + 1}`
            : `${startIndex + 1}–${actualEnd}`;

        if (paginationRangeEl) paginationRangeEl.textContent = rangeText;
        if (paginationTotalEl) paginationTotalEl.textContent = totalFiltered;
        if (visibleRangeEl)    visibleRangeEl.textContent    = rangeText;
        if (pageIndicatorEl) {
            pageIndicatorEl.textContent = totalPages > 1 ? `(Page ${currentPage} of ${totalPages})` : '';
        }

        // Prev & Next states
        if (prevPageBtn) prevPageBtn.disabled = currentPage <= 1;
        if (nextPageBtn) nextPageBtn.disabled = currentPage >= totalPages;

        // Render page buttons with pure Tailwind utility classes
        if (pageNumbersContainer) {
            pageNumbersContainer.innerHTML = '';
            const pagesToShow = getPageNumbers(currentPage, totalPages);

            pagesToShow.forEach(p => {
                if (p === '...') {
                    const span = document.createElement('span');
                    span.className = 'px-1.5 text-xs text-[var(--ink-muted)] select-none';
                    span.textContent = '...';
                    pageNumbersContainer.appendChild(span);
                } else {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = p === currentPage
                        ? 'h-8 min-w-[2rem] px-2.5 inline-flex items-center justify-center text-xs font-bold rounded-lg bg-emerald-600 border border-emerald-600 text-white shadow-xs cursor-pointer'
                        : 'h-8 min-w-[2rem] px-2.5 inline-flex items-center justify-center text-xs font-semibold rounded-lg border border-[var(--border)] bg-gray-50 dark:bg-neutral-900/40 text-[var(--ink)] hover:bg-black/5 dark:hover:bg-white/5 cursor-pointer transition-all';
                    btn.textContent = p;
                    btn.addEventListener('click', () => goToPage(p));
                    pageNumbersContainer.appendChild(btn);
                }
            });
        }
    }

    // ─── Filter Matching ─────────────────────────────────────────────────────

    function applyFilters(resetPage = true) {
        const query    = (searchInput?.value ?? '').trim().toLowerCase();
        const typeVal  = typeSelect?.value ?? '';
        const staffVal = staffSelect?.value ?? '';

        matchedRows = [];

        allRows.forEach(row => {
            const rowSearch  = (row.dataset.search ?? '').toLowerCase();
            const rowType    = row.dataset.type ?? '';
            const rowActor   = row.dataset.actor ?? '';
            const rowDate    = row.dataset.date ?? '';     // "YYYY-MM-DD"
            const rowSession = row.dataset.session ?? '';  // "daytime" or "overnight"

            // 1. Search match
            const matchesSearch = query === '' || rowSearch.includes(query);

            // 2. Type match
            const matchesType = typeVal === '' || rowType === typeVal;

            // 3. Staff match
            const matchesStaff = staffVal === '' || rowActor === staffVal;

            // 4. Session match
            const matchesSession = appliedSession === '' || rowSession === appliedSession;

            // 5. Date match (Single or Range)
            let matchesDate = true;
            if (appliedStartDate && appliedEndDate) {
                matchesDate = rowDate >= appliedStartDate && rowDate <= appliedEndDate;
            } else if (appliedStartDate) {
                // Only start date picked: single date match
                matchesDate = rowDate === appliedStartDate;
            } else if (appliedEndDate) {
                matchesDate = rowDate <= appliedEndDate;
            }

            if (matchesSearch && matchesType && matchesStaff && matchesSession && matchesDate) {
                matchedRows.push(row);
            }
        });

        if (resetPage) {
            currentPage = 1;
            window.__adminPaymentCurrentPage = 1;
        } else if (window.__adminPaymentCurrentPage) {
            currentPage = window.__adminPaymentCurrentPage;
        }

        // Update header total count badge
        if (visibleCountEl) {
            visibleCountEl.textContent = matchedRows.length;
        }

        // Show/hide no results row
        if (noResultsRow) {
            const hasData = totalCount > 0;
            noResultsRow.classList.toggle('hidden', !hasData || matchedRows.length > 0);
        }

        applyDisplay();
    }

    // ─── Event wiring ────────────────────────────────────────────────────────

    searchInput?.addEventListener('input', () => applyFilters(true));
    typeSelect?.addEventListener('change', () => applyFilters(true));
    staffSelect?.addEventListener('change', () => applyFilters(true));

    prevPageBtn?.addEventListener('click', () => {
        if (currentPage > 1) {
            goToPage(currentPage - 1);
        }
    });

    nextPageBtn?.addEventListener('click', () => {
        const totalPages = Math.max(1, Math.ceil(matchedRows.length / PAGE_SIZE));
        if (currentPage < totalPages) {
            goToPage(currentPage + 1);
        }
    });

    function clearAllFilters() {
        window.__adminPaymentCurrentPage = 1;
        if (searchInput)   searchInput.value   = '';
        if (typeSelect)    typeSelect.value    = '';
        if (staffSelect)   staffSelect.value   = '';
        appliedStartDate = '';
        appliedEndDate   = '';
        appliedSession   = '';

        if (modalStartDateInput) modalStartDateInput.value = '';
        if (modalEndDateInput)   modalEndDateInput.value   = '';
        if (modalSessionSelect)  modalSessionSelect.value  = '';

        updateTriggerButtonUI();
        applyFilters(true);
    }

    clearBtn?.addEventListener('click', clearAllFilters);
    clearBtnAlt?.addEventListener('click', clearAllFilters);

    // ─── Init ────────────────────────────────────────────────────────────────
    updateTriggerButtonUI();
    applyFilters(false);
};

window.addEventListener('spa:leaving', () => {
    window.__adminPaymentCurrentPage = 1;
});

document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.AppPage?.['admin_payment'] === 'function') {
        window.AppPage['admin_payment']();
    }
});
