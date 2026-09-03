window.AppPage = window.AppPage || {};
window.AppPage['admin_feedback'] = function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const bodyEl = document.body;
    const todayDate = bodyEl?.dataset.today || '';

    const feedbackSearch = document.getElementById('feedbackSearch');
    const feedbackSentimentFilter = document.getElementById('feedbackSentimentFilter');
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

    // AI Executive Insights elements
    const aiRefreshBtn = document.getElementById('fbAiRefreshBtn');
    const aiPositivePctBadge = document.getElementById('fbAiPositivePctBadge');
    const aiPosBar = document.getElementById('fbAiPosBar');
    const aiNeuBar = document.getElementById('fbAiNeuBar');
    const aiNegBar = document.getElementById('fbAiNegBar');
    const aiPosCount = document.getElementById('fbAiPosCount');
    const aiNeuCount = document.getElementById('fbAiNeuCount');
    const aiNegCount = document.getElementById('fbAiNegCount');
    const aiPraisesList = document.getElementById('fbAiPraisesList');
    const aiIssuesList = document.getElementById('fbAiIssuesList');
    const aiAnalyzedAt = document.getElementById('fbAiAnalyzedAt');

    // Detail Modal elements
    const detailModal = document.getElementById('feedbackDetailModal');
    const detailAvatar = document.getElementById('fbDetailAvatar');
    const detailName = document.getElementById('fbDetailName');
    const detailMeta = document.getElementById('fbDetailMeta');
    const detailStars = document.getElementById('fbDetailStars');
    const detailRatingLabel = document.getElementById('fbDetailRatingLabel');
    const detailAiSentimentBadge = document.getElementById('fbDetailAiSentimentBadge');
    const detailAiSection = document.getElementById('fbDetailAiSection');
    const detailAiPointsList = document.getElementById('fbDetailAiPointsList');
    const detailDescription = document.getElementById('fbDetailDescription');
    const detailImagesSection = document.getElementById('fbDetailImagesSection');
    const detailImagesCount = document.getElementById('fbDetailImagesCount');
    const detailImagesGrid = document.getElementById('fbDetailImagesGrid');
    const detailToggleBtn = document.getElementById('fbDetailToggleBtn');
    const detailToggleLabel = document.getElementById('fbDetailToggleLabel');
    const detailDeleteBtn = document.getElementById('fbDetailDeleteBtn');
    const deleteConfirmBox = document.getElementById('fbDeleteConfirmBox');
    const cancelDeleteBtn = document.getElementById('fbCancelDelete');
    const confirmDeleteBtn = document.getElementById('fbConfirmDelete');

    // Admin Lightbox
    const adminLightbox = document.getElementById('adminPhotoLightbox');
    const adminLightboxImg = document.getElementById('adminLightboxImg');

    let rows = Array.from(document.querySelectorAll('.feedback-row'));
    let page = 1;
    let activeId = null;

    /* ── Helpers ── */

    const getRow = (id) => rows.find((r) => r.dataset.feedbackId === String(id));

    const getRowData = (row) => {
        const script = row.querySelector('.fb-admin-row-data');
        if (script) {
            try {
                return JSON.parse(script.textContent);
            } catch (_) {}
        }
        return {
            id: row.dataset.feedbackId,
            fullName: 'Guest',
            initials: 'G',
            stars: parseInt(row.dataset.stars, 10) || 5,
            description: '',
            createdFormatted: '',
            visibility: row.dataset.visibility || 'shown',
            sentiment: row.dataset.sentiment || 'neutral',
            sentimentLabel: 'Neutral',
            sentimentEmoji: '🟡',
            points: [],
            images: [],
        };
    };

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
        const sentimentVal = feedbackSentimentFilter?.value || 'all';
        const starFilterVal = feedbackStarFilter?.value || 'all';
        const visibilityVal = feedbackVisibilityFilter?.value || 'all';
        const sortBy = feedbackSortFilter?.value || 'newest';
        const perPage = parseInt(perPageSelect?.value || '10', 10);

        // Filter
        const filtered = rows.filter((row) => {
            const name = row.dataset.guestName || '';
            const sentiment = row.dataset.sentiment || 'neutral';
            const stars = row.dataset.stars || '';
            const visibility = row.dataset.visibility || '';
            return (!query || name.includes(query))
                && (sentimentVal === 'all' || sentiment === sentimentVal)
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
    feedbackSentimentFilter?.addEventListener('change', () => { page = 1; applyView(); });
    feedbackStarFilter?.addEventListener('change', () => { page = 1; applyView(); });
    feedbackVisibilityFilter?.addEventListener('change', () => { page = 1; applyView(); });
    feedbackSortFilter?.addEventListener('change', () => { page = 1; applyView(); });
    perPageSelect?.addEventListener('change', () => { page = 1; applyView(); });
    prevBtn?.addEventListener('click', () => { page -= 1; applyView(); });
    nextBtn?.addEventListener('click', () => { page += 1; applyView(); });

    /* ── Refresh AI Insights ── */

    aiRefreshBtn?.addEventListener('click', async () => {
        aiRefreshBtn.disabled = true;
        const originalHtml = aiRefreshBtn.innerHTML;
        aiRefreshBtn.innerHTML = `
            <svg class="h-3.5 w-3.5 animate-spin text-[#c8a45d]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
            <span>Analyzing...</span>
        `;

        try {
            const response = await fetch('/admin/feedback/ai-insights/refresh', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
            });

            const data = await response.json();
            if (!response.ok || !data.insights) throw new Error(data.message || 'Failed to refresh AI analysis.');

            const ins = data.insights;

            if (aiPositivePctBadge) aiPositivePctBadge.textContent = `${ins.positive_percent}% Positive`;
            if (aiPosBar) {
                aiPosBar.style.width = `${ins.positive_percent}%`;
                aiPosBar.title = `Positive: ${ins.positive_count} (${ins.positive_percent}%)`;
            }
            if (aiNeuBar) {
                aiNeuBar.style.width = `${ins.neutral_percent}%`;
                aiNeuBar.title = `Neutral: ${ins.neutral_count} (${ins.neutral_percent}%)`;
            }
            if (aiNegBar) {
                aiNegBar.style.width = `${ins.negative_percent}%`;
                aiNegBar.title = `Negative: ${ins.negative_count} (${ins.negative_percent}%)`;
            }
            if (aiPosCount) aiPosCount.textContent = String(ins.positive_count);
            if (aiNeuCount) aiNeuCount.textContent = String(ins.neutral_count);
            if (aiNegCount) aiNegCount.textContent = String(ins.negative_count);

            if (aiPraisesList && Array.isArray(ins.top_praises)) {
                aiPraisesList.innerHTML = ins.top_praises.map((p) => `<li>${p}</li>`).join('');
            }
            if (aiIssuesList && Array.isArray(ins.top_issues)) {
                aiIssuesList.innerHTML = ins.top_issues.map((iss) => `<li>${iss}</li>`).join('');
            }
            if (aiAnalyzedAt) aiAnalyzedAt.textContent = `Analyzed ${ins.analyzed_at || 'Just now'}`;

        } catch (error) {
            alert(error.message || 'Unable to refresh AI insights.');
        } finally {
            aiRefreshBtn.disabled = false;
            aiRefreshBtn.innerHTML = originalHtml;
        }
    });

    /* ── Lightbox for Admin ── */

    const openAdminLightbox = (url) => {
        if (!adminLightbox || !adminLightboxImg || !url) return;
        adminLightboxImg.src = url;
        adminLightbox.classList.add('is-open');
        adminLightbox.setAttribute('aria-hidden', 'false');
    };

    const closeAdminLightbox = () => {
        if (!adminLightbox) return;
        adminLightbox.classList.remove('is-open');
        adminLightbox.setAttribute('aria-hidden', 'true');
    };

    document.querySelectorAll('[data-admin-lightbox-close]').forEach((el) => {
        el.addEventListener('click', closeAdminLightbox);
    });

    /* ── Detail modal ── */

    const hideDeleteConfirm = () => deleteConfirmBox?.classList.add('hidden');

    const openDetailModal = (row) => {
        const data = getRowData(row);
        activeId = data.id;
        const stars = parseInt(data.stars, 10) || 0;
        const visibility = data.visibility;

        if (detailAvatar) detailAvatar.textContent = data.initials || 'G';
        if (detailName) detailName.textContent = data.fullName || 'Guest';
        if (detailMeta) {
            detailMeta.textContent = [
                data.createdFormatted || '',
                data.isAnonymous ? 'Anonymous submission' : '',
            ].filter(Boolean).join(' · ');
        }
        if (detailStars) {
            detailStars.innerHTML = Array.from({ length: 5 }, (_, i) => starSvg(i < stars)).join('');
        }
        if (detailRatingLabel) detailRatingLabel.textContent = `${stars} out of 5`;

        // AI Sentiment badge in modal
        if (detailAiSentimentBadge) {
            detailAiSentimentBadge.innerHTML = `<span>${data.sentimentEmoji || '🟡'}</span> <span>${data.sentimentLabel || 'Neutral'}</span>`;
            detailAiSentimentBadge.className = 'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold';
            if (data.sentiment === 'positive') {
                detailAiSentimentBadge.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-200', 'dark:bg-emerald-950/40', 'dark:text-emerald-400', 'dark:border-emerald-800');
            } else if (data.sentiment === 'negative') {
                detailAiSentimentBadge.classList.add('bg-rose-50', 'text-rose-700', 'border-rose-200', 'dark:bg-rose-950/40', 'dark:text-rose-400', 'dark:border-rose-800');
            } else {
                detailAiSentimentBadge.classList.add('bg-amber-50', 'text-amber-700', 'border-amber-200', 'dark:bg-amber-950/40', 'dark:text-amber-400', 'dark:border-amber-800');
            }
        }

        // Render AI Sentiment Breakdown points (Clean & Direct)
        const points = Array.isArray(data.points) ? data.points : [];
        if (detailAiSection && detailAiPointsList) {
            if (points.length > 0) {
                detailAiSection.classList.remove('hidden');
                detailAiPointsList.innerHTML = points.map((pt) => {
                    const isPos = pt.type === 'positive';
                    const isFlag = pt.type === 'flagged';
                    const cardBorder = isPos
                        ? 'border-emerald-200 bg-emerald-50/70 text-emerald-950 dark:border-emerald-800/80 dark:bg-emerald-950/40 dark:text-emerald-300'
                        : (isFlag
                            ? 'border-rose-300 bg-rose-50 text-rose-950 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200'
                            : 'border-rose-200 bg-rose-50/70 text-rose-950 dark:border-rose-800/80 dark:bg-rose-950/40 dark:text-rose-300');
                    const badgeText = isPos ? 'POSITIVE' : (isFlag ? 'FLAGGED' : 'NEGATIVE');
                    const badgeClass = isPos
                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300'
                        : 'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300';

                    return `
                        <div class="rounded-lg border p-2.5 text-xs ${cardBorder}">
                            <div class="mb-1 flex items-center justify-between font-bold">
                                <span class="flex items-center gap-1.5">
                                    <span>${pt.emoji || (isPos ? '🟢' : '🔴')}</span>
                                    <span>${pt.topic || (isPos ? 'Compliment' : 'Issue')}</span>
                                </span>
                                <span class="rounded px-1.5 py-0.5 text-[0.65rem] font-bold tracking-wider ${badgeClass}">${badgeText}</span>
                            </div>
                            <p class="m-0 mb-1 text-[0.75rem] italic opacity-90">"${pt.snippet}"</p>
                            <p class="m-0 text-xs font-normal leading-relaxed">${pt.how || pt.reason}</p>
                        </div>
                    `;
                }).join('');
            } else {
                detailAiSection.classList.add('hidden');
                detailAiPointsList.innerHTML = '';
            }
        }

        if (detailDescription) detailDescription.textContent = data.description || '';

        // Photos gallery in modal
        const images = Array.isArray(data.images) ? data.images : [];
        if (detailImagesSection && detailImagesGrid) {
            if (images.length > 0) {
                detailImagesSection.classList.remove('hidden');
                if (detailImagesCount) detailImagesCount.textContent = `${images.length} photo${images.length > 1 ? 's' : ''}`;
                detailImagesGrid.innerHTML = images.map((img) => {
                    const url = typeof img === 'string' ? img : (img.url || img.image_url);
                    return `
                        <button type="button" class="group relative aspect-square w-full overflow-hidden rounded-lg border border-[var(--border)] bg-[var(--surface-alt)] transition-all hover:scale-105 hover:border-[var(--green)]" data-full-image="${url}">
                            <img src="${url}" alt="Feedback photo" class="h-full w-full object-cover">
                            <span class="absolute inset-0 grid place-items-center bg-black/40 opacity-0 transition-opacity group-hover:opacity-100">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6" /></svg>
                            </span>
                        </button>
                    `;
                }).join('');

                detailImagesGrid.querySelectorAll('[data-full-image]').forEach((btn) => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        openAdminLightbox(btn.dataset.fullImage);
                    });
                });
            } else {
                detailImagesSection.classList.add('hidden');
                detailImagesGrid.innerHTML = '';
            }
        }

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
        if (event.key === 'Escape') {
            if (adminLightbox?.classList.contains('is-open')) {
                closeAdminLightbox();
            } else if (detailModal?.classList.contains('is-open')) {
                closeDetailModal();
            }
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
