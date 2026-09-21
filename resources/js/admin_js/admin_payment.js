document.addEventListener('DOMContentLoaded', () => {
    // ─── Element refs ────────────────────────────────────────────────────────
    const searchInput      = document.getElementById('paymentSearchInput');
    const typeSelect       = document.getElementById('typeFilterSelect');
    const dateFromInput    = document.getElementById('dateFromInput');
    const dateToInput      = document.getElementById('dateToInput');
    const clearBtn         = document.getElementById('clearFiltersBtn');
    const clearBtnAlt      = document.getElementById('clearFiltersBtnAlt');
    const noResultsRow     = document.getElementById('paymentNoResultsRow');
    const visibleCountEl   = document.getElementById('visibleCount');

    /** All data rows (static, rendered by Blade). */
    const allRows = Array.from(document.querySelectorAll('.payment-row'));
    const totalCount = allRows.length;

    // ─── Core filter logic ───────────────────────────────────────────────────

    /**
     * Reads the current filter values, shows/hides each `.payment-row`,
     * and updates the visible-count badge.
     */
    function applyFilters() {
        const query    = (searchInput?.value ?? '').trim().toLowerCase();
        const typeVal  = typeSelect?.value ?? '';
        const dateFrom = dateFromInput?.value ?? '';   // "YYYY-MM-DD" or ""
        const dateTo   = dateToInput?.value ?? '';     // "YYYY-MM-DD" or ""

        let visibleCount = 0;

        allRows.forEach(row => {
            const rowSearch = (row.dataset.search ?? '').toLowerCase();
            const rowType   = row.dataset.type ?? '';
            const rowDate   = row.dataset.date ?? '';  // "YYYY-MM-DD"

            // 1. Search match
            const matchesSearch = query === '' || rowSearch.includes(query);

            // 2. Type match
            const matchesType = typeVal === '' || rowType === typeVal;

            // 3. Date range — ISO string comparison works correctly for YYYY-MM-DD
            const matchesFrom = dateFrom === '' || rowDate >= dateFrom;
            const matchesTo   = dateTo   === '' || rowDate <= dateTo;

            const visible = matchesSearch && matchesType && matchesFrom && matchesTo;

            row.style.display = visible ? '' : 'none';

            if (visible) visibleCount++;
        });

        // Update count badge
        if (visibleCountEl) {
            visibleCountEl.textContent = visibleCount;
        }

        // Show "no results" placeholder only when rows exist but none match
        if (noResultsRow) {
            const hasData = totalCount > 0;
            noResultsRow.classList.toggle('hidden', !hasData || visibleCount > 0);
        }
    }

    // ─── Event wiring ────────────────────────────────────────────────────────

    searchInput?.addEventListener('input', applyFilters);
    typeSelect?.addEventListener('change', applyFilters);
    dateFromInput?.addEventListener('change', applyFilters);
    dateToInput?.addEventListener('change', applyFilters);

    function clearAllFilters() {
        if (searchInput)   searchInput.value   = '';
        if (typeSelect)    typeSelect.value     = '';
        if (dateFromInput) dateFromInput.value  = '';
        if (dateToInput)   dateToInput.value    = '';
        applyFilters();
    }

    clearBtn?.addEventListener('click', clearAllFilters);
    clearBtnAlt?.addEventListener('click', clearAllFilters);

    // ─── Init ────────────────────────────────────────────────────────────────
    // Run once on load so the visible count label is accurate immediately.
    applyFilters();
});
