document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const modal = document.getElementById('reviewModal');
    const form = document.getElementById('feedbackForm');
    const fullNameInput = document.getElementById('feedbackFullName');
    const anonymousCheckbox = document.getElementById('feedbackAnonymous');
    const starsHidden = document.getElementById('feedbackStars');
    const starHint = document.getElementById('feedbackStarHint');
    const starButtons = Array.from(document.querySelectorAll('.fb-star-input__btn'));
    const submitBtn = document.getElementById('feedbackSubmitBtn');
    const searchInput = document.getElementById('feedbackListSearch');
    const starFilter = document.getElementById('feedbackListStarFilter');
    const reviewCards = Array.from(document.querySelectorAll('.fb-review-card'));
    const noFilterResults = document.getElementById('feedbackNoFilterResults');
    const emptyState = document.getElementById('feedbackEmptyState');
    const summaryCountEl = document.getElementById('fbSummaryCount');

    let selectedStars = 0;

    /* ── Modal open / close ── */

    const openModal = () => {
        if (!modal) return;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        window.setTimeout(() => fullNameInput?.focus(), 250);
    };

    const closeModal = () => {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    document.querySelectorAll('[data-open-review-modal]').forEach((btn) => {
        btn.addEventListener('click', openModal);
    });

    document.querySelectorAll('[data-close-review-modal]').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal?.classList.contains('is-open')) {
            closeModal();
        }
    });

    /* ── Anonymous toggle ── */

    const syncAnonymousState = () => {
        const isAnonymous = anonymousCheckbox?.checked;
        if (!fullNameInput) return;
        fullNameInput.disabled = isAnonymous;
        fullNameInput.classList.toggle('is-disabled', isAnonymous);
        if (isAnonymous) {
            fullNameInput.value = '';
        }
    };

    anonymousCheckbox?.addEventListener('change', syncAnonymousState);
    syncAnonymousState();

    /* ── Star picker ── */

    const renderStars = (value) => {
        selectedStars = value;
        if (starsHidden) starsHidden.value = value > 0 ? String(value) : '';
        starButtons.forEach((btn) => {
            const star = parseInt(btn.dataset.star, 10);
            btn.classList.toggle('is-active', star <= value);
        });
        if (starHint) {
            starHint.textContent = value > 0 ? `You selected ${value} star${value > 1 ? 's' : ''}.` : 'Tap a star to rate your visit.';
            starHint.classList.remove('is-error');
        }
    };

    starButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            renderStars(parseInt(btn.dataset.star, 10));
        });
        btn.addEventListener('mouseenter', () => {
            const hover = parseInt(btn.dataset.star, 10);
            starButtons.forEach((b) => {
                const star = parseInt(b.dataset.star, 10);
                b.classList.toggle('is-active', star <= hover);
            });
        });
        btn.addEventListener('mouseleave', () => {
            renderStars(selectedStars);
        });
    });

    /* ── List filters ── */

    const filterReviews = () => {
        const query = searchInput?.value.trim().toLowerCase() || '';
        const starValue = starFilter?.value || 'all';
        let visibleCount = 0;

        reviewCards.forEach((card) => {
            const name = card.dataset.guestName || '';
            const stars = card.dataset.stars || '';
            const matchesName = !query || name.includes(query);
            const matchesStars = starValue === 'all' || stars === starValue;
            const show = matchesName && matchesStars;
            card.classList.toggle('hidden', !show);
            if (show) visibleCount += 1;
        });

        if (noFilterResults) {
            const hasCards = reviewCards.length > 0;
            noFilterResults.classList.toggle('hidden', !hasCards || visibleCount > 0);
        }
    };

    searchInput?.addEventListener('input', filterReviews);
    starFilter?.addEventListener('change', filterReviews);

    /* ── Live summary counters ── */

    const updateSummaryCount = () => {
        if (!summaryCountEl) return;
        summaryCountEl.textContent = String(reviewCards.length);
        summaryCountEl.parentElement?.querySelector('.fb-summary__label')
            ?.replaceChildren(`Review${reviewCards.length === 1 ? '' : 's'} shared`);
    };

    /* ── Prepend newly submitted review ── */

    const escapeHtml = (str) => String(str ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');

    const prependReviewCard = (feedback) => {
        if (!feedback) return;
        const list = document.getElementById('feedbackReviewList');
        if (!list) return;

        emptyState?.remove();

        const article = document.createElement('article');
        article.className = 'fb-review-card';
        article.dataset.guestName = (feedback.full_name || '').toLowerCase();
        article.dataset.stars = String(feedback.stars);

        const starsHtml = Array.from({ length: 5 }, (_, i) => {
            const filled = i + 1 <= feedback.stars ? 'is-filled' : '';
            return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="${filled}"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>`;
        }).join('');

        article.innerHTML = `
            <div class="fb-review-card__top">
                <span class="fb-review-card__avatar" aria-hidden="true">${escapeHtml(feedback.initials || 'G')}</span>
                <div class="fb-review-card__meta">
                    <h3 class="fb-review-card__name">${escapeHtml(feedback.full_name)}</h3>
                    <time class="fb-review-card__date">${escapeHtml(feedback.created_at || 'Just now')}</time>
                </div>
                <div class="fb-review-card__badge" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <span>${Number(feedback.stars)}.0</span>
                </div>
            </div>
            <div class="fb-review-card__stars" aria-label="${Number(feedback.stars)} out of 5 stars">${starsHtml}</div>
            <p class="fb-review-card__text"></p>
        `;

        article.querySelector('.fb-review-card__text').textContent = feedback.description;
        list.prepend(article);
        reviewCards.unshift(article);
        updateSummaryCount();
        filterReviews();
    };

    /* ── Submit ── */

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const isAnonymous = anonymousCheckbox?.checked;
        const fullName = fullNameInput?.value.trim() || '';
        const description = document.getElementById('feedbackDescription')?.value.trim() || '';
        const stars = parseInt(starsHidden?.value || '0', 10);

        if (!isAnonymous && !fullName) {
            alert('Please enter your full name or choose anonymous feedback.');
            fullNameInput?.focus();
            return;
        }

        if (!stars || stars < 1 || stars > 5) {
            if (starHint) {
                starHint.textContent = 'Please select a star rating before submitting.';
                starHint.classList.add('is-error');
            }
            return;
        }

        if (!description) {
            alert('Please write your feedback before submitting.');
            document.getElementById('feedbackDescription')?.focus();
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';

        try {
            const response = await fetch('/feedback', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    full_name: fullName,
                    is_anonymous: isAnonymous,
                    description,
                    stars,
                }),
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Unable to submit feedback.');
            }

            form.reset();
            renderStars(0);
            syncAnonymousState();
            prependReviewCard(data.feedback);
            closeModal();
        } catch (error) {
            alert(error.message || 'Unable to submit feedback. Please try again.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Send Feedback';
        }
    });
});
