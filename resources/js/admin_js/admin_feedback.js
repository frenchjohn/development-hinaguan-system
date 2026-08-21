window.AppPage = window.AppPage || {};
window.AppPage['admin_feedback'] = function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const bodyEl = document.body;
    const todayDate = bodyEl?.dataset.today || '';

    const feedbackSearch = document.getElementById('feedbackSearch');
    const feedbackStarFilter = document.getElementById('feedbackStarFilter');
    const feedbackVisibilityFilter = document.getElementById('feedbackVisibilityFilter');
    const feedbackSortFilter = document.getElementById('feedbackSortFilter');
    const perPageSelect = document.getElementById('feedbackPerPage');
    const prevBtn = document.getElementById('fbPrevPage');
    const nextBtn = document.getElementById('fbNextPage');
    const pageInfo = document.getElementById('fbPageInfo');
    const rangeText = document.getElementById('fbRangeText');
    const ofText = document.getElementById('fbOfText');
    const tableBody = document.getElementById('feedbackTableBody');
    const emptyRow = document.getElementById('feedbackEmptyRow');
    const noResults = document.getElementById('feedbackNoResults');

    const detailModal = document.getElementById('feedbackDetailModal');
    const detailAvatar = document.getElementById('fbDetailAvatar');
    const detailName = document.getElementById('fbDetailName');
    const detailMeta = document.getElementById('fbDetailMeta');
    const detailStars = document.getElementById('fbDetailStars');
    const detailRatingLabel = document.getElementById('fbDetailRatingLabel');
    const detailDescription = document.getElementById('fbDetailDescription');
    const detailToggleBtn = document.getElementById('fbDetailToggleBtn');
    const detailToggleLabel = document.getElementById('fbDetailToggleLabel');
    const detailDeleteBtn = document.getElementById('fbDetailDeleteBtn');
    const deleteConfirmBox = document.getElementById('fbDeleteConfirmBox');
    const cancelDeleteBtn = document.getElementById('fbCancelDelete');
    const confirmDeleteBtn = document.getElementById('fbConfirmDelete');

    let rows = Array.from(document.querySelectorAll('.feedback-row'));
    let page = 1;
    let activeId = null;

    /* ── Helpers ── */

    const getRow = (id) => rows.find((r) => r.dataset.feedbackId === String(id));

    const starSvg = (filled) => `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4 ${filled ? 'fill-current' : 'fill-none stroke-current'}" stroke-width="1.5"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>`;

    const updateStats = () => {
        const total = rows.length;
        const shown = rows.filter((r) => r.dataset.visibility === 'shown').length;
        const hidden = total - shown;
        const todayCount = rows.filter((r) => r.dataset.createdDate === todayDate).length;
        const avg = total > 0
            ? (rows.reduce((sum, r) => sum + (parseInt(r.dataset.stars, 10) || 0), 0) / total).toFixed(1)
            : '0';

        const set = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        };
        set('fbStatTotal', total);
        set('fbStatShown', shown);
        set('fbStatHidden', hidden);
        set('fbStatAvg', avg);
        set('fbStatToday', todayCount);
    };

    /* ── Filter + sort + paginate ── */

    const applyView = () => {
        if (!tableBody) return;

        const query = feedbackSearch?.value.trim().toLowerCase() || '';
        const starFilterVal = feedbackStarFilter?.value || 'all';
        const visibilityVal = feedbackVisibilityFilter?.value || 'all';
        const sortBy = feedbackSortFilter?.value || 'newest';
        const perPage = parseInt(perPageSelect?.value || '10', 10);

        // Filter
        const filtered = rows.filter((row) => {
            const name = row.dataset.guestName || '';
            const stars = row.dataset.stars || '';
            const visibility = row.dataset.visibility || '';
            return (!query || name.includes(query))
                && (starFilterVal === 'all' || stars === starFilterVal)
                && (visibilityVal === 'all' || visibility === visibilityVal);
        });

        // Sort (newest first is default DOM order; re-append sorted)
        filtered.sort((a, b) => {
            switch (sortBy) {
                case 'oldest':
                    return (parseInt(a.dataset.createdTimestamp, 10) || 0) - (parseInt(b.dataset.createdTimestamp, 10) || 0);
                case 'stars_high':
                    return (parseInt(b.dataset.stars, 10) || 0) - (parseInt(a.dataset.stars, 10) || 0)
                        || (parseInt(b.dataset.createdTimestamp, 10) || 0) - (parseInt(a.dataset.createdTimestamp, 10) || 0);
                case 'stars_low':
                    return (parseInt(a.dataset.stars, 10) || 0) - (parseInt(b.dataset.stars, 10) || 0)
                        || (parseInt(b.dataset.createdTimestamp, 10) || 0) - (parseInt(a.dataset.createdTimestamp, 10) || 0);
                case 'newest':
                default:
                    return (parseInt(b.dataset.createdTimestamp, 10) || 0) - (parseInt(a.dataset.createdTimestamp, 10) || 0);
            }
        }).forEach((row) => tableBody.appendChild(row));

        // Paginate
        const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
        page = Math.min(page, totalPages);
        const startIdx = (page - 1) * perPage;
        const visibleIds = new Set(filtered.slice(startIdx, startIdx + perPage).map((r) => r.dataset.feedbackId));

        rows.forEach((row) => row.classList.toggle('hidden', !visibleIds.has(row.dataset.feedbackId)));

        if (emptyRow && rows.length === 0) emptyRow.classList.remove('hidden');
        else emptyRow?.classList.add('hidden');

        if (noResults) {
            noResults.classList.toggle('hidden', filtered.length > 0 || rows.length === 0);
        }

        // Footer info
        const from = filtered.length === 0 ? 0 : startIdx + 1;
        const to = Math.min(startIdx + perPage, filtered.length);
        if (rangeText) rangeText.textContent = `${from}\u2013${to}`;
        if (ofText) ofText.textContent = `of ${filtered.length} review${filtered.length === 1 ? '' : 's'}`;
        if (pageInfo) pageInfo.textContent = `Page ${page} of ${totalPages}`;
        if (prevBtn) prevBtn.disabled = page <= 1;
        if (nextBtn) nextBtn.disabled = page >= totalPages;
    };

    feedbackSearch?.addEventListener('input', () => { page = 1; applyView(); });
    feedbackStarFilter?.addEventListener('change', () => { page = 1; applyView(); });
    feedbackVisibilityFilter?.addEventListener('change', () => { page = 1; applyView(); });
    feedbackSortFilter?.addEventListener('change', () => { page = 1; applyView(); });
    perPageSelect?.addEventListener('change', () => { page = 1; applyView(); });
    prevBtn?.addEventListener('click', () => { page -= 1; applyView(); });
    nextBtn?.addEventListener('click', () => { page += 1; applyView(); });

    /* ── Detail modal ── */

    const hideDeleteConfirm = () => deleteConfirmBox?.classList.add('hidden');

    const openDetailModal = (row) => {
        activeId = row.dataset.feedbackId;
        const stars = parseInt(row.dataset.stars, 10) || 0;
        const visibility = row.dataset.visibility;

        if (detailAvatar) detailAvatar.textContent = row.dataset.initials || 'G';
        if (detailName) detailName.textContent = row.dataset.fullName || 'Guest';
        if (detailMeta) {
            detailMeta.textContent = [
                row.dataset.createdFormatted || '',
                row.dataset.isAnonymous === '1' ? 'Anonymous submission' : '',
            ].filter(Boolean).join(' · ');
        }
        if (detailStars) {
            detailStars.innerHTML = Array.from({ length: 5 }, (_, i) => starSvg(i < stars)).join('');
        }
        if (detailRatingLabel) detailRatingLabel.textContent = `${stars} out of 5`;
        if (detailDescription) detailDescription.textContent = row.dataset.description || '';
        syncToggleButton(visibility);
        hideDeleteConfirm();
        if (detailDeleteBtn) {
            detailDeleteBtn.disabled = false;
            detailDeleteBtn.textContent = 'Delete';
        }

        detailModal?.classList.add('is-open');
        detailModal?.setAttribute('aria-hidden', 'false');
    };

    const closeDetailModal = () => {
        activeId = null;
        hideDeleteConfirm();
        detailModal?.classList.remove('is-open');
        detailModal?.setAttribute('aria-hidden', 'true');
    };

    const syncToggleButton = (visibility) => {
        const shown = visibility === 'shown';
        if (detailToggleLabel) detailToggleLabel.textContent = shown ? 'Hide from website' : 'Show on website';
        if (detailToggleBtn) {
            detailToggleBtn.classList.toggle('!border-[var(--green)]', shown);
            detailToggleBtn.classList.toggle('text-[var(--green)]', shown);
        }
    };

    document.querySelectorAll('[data-feedback-detail-cancel]').forEach((el) => {
        el.addEventListener('click', closeDetailModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && detailModal?.classList.contains('is-open')) {
            closeDetailModal();
        }
    });

    rows.forEach((row) => {
        row.addEventListener('click', () => openDetailModal(row));
        row.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openDetailModal(row);
            }
        });
    });

    /* ── Toggle visibility inside modal ── */

    detailToggleBtn?.addEventListener('click', async () => {
        if (!activeId) return;
        const row = getRow(activeId);
        if (!row) return;

        const nextShown = row.dataset.visibility !== 'shown';

        detailToggleBtn.disabled = true;

        try {
            const response = await fetch(`/admin/feedback/${activeId}/visibility`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ is_shown: nextShown }),
            });

            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Failed to update visibility.');

            row.dataset.visibility = nextShown ? 'shown' : 'hidden';
            const badge = row.querySelector('.fb-status-badge');
            if (badge) {
                badge.textContent = nextShown ? 'Shown' : 'Hidden';
                badge.classList.toggle('bg-[var(--green-soft)]', nextShown);
                badge.classList.toggle('text-[var(--green)]', nextShown);
                badge.classList.toggle('bg-[var(--warn-soft)]', !nextShown);
                badge.classList.toggle('text-[var(--warn)]', !nextShown);
            }

            syncToggleButton(nextShown ? 'shown' : 'hidden');
            updateStats();
            applyView();
        } catch (error) {
            alert(error.message || 'Unable to update visibility.');
        } finally {
            detailToggleBtn.disabled = false;
        }
    });

    /* ── Delete inside modal (with inline confirm) ── */

    detailDeleteBtn?.addEventListener('click', () => {
        deleteConfirmBox?.classList.remove('hidden');
    });

    cancelDeleteBtn?.addEventListener('click', hideDeleteConfirm);

    confirmDeleteBtn?.addEventListener('click', async () => {
        if (!activeId) return;
        const row = getRow(activeId);
        if (!row) return;

        confirmDeleteBtn.disabled = true;
        confirmDeleteBtn.textContent = 'Deleting...';

        try {
            const response = await fetch(`/admin/feedback/${activeId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
            });

            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Failed to delete feedback.');

            rows = rows.filter((r) => r !== row);
            row.remove();

            updateStats();
            applyView();
            closeDetailModal();
        } catch (error) {
            alert(error.message || 'Unable to delete feedback.');
        } finally {
            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.textContent = 'Yes, delete';
        }
    });

    updateStats();
    applyView();
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['admin_feedback']());
