document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const modal = document.getElementById('reviewModal');
    const warningModal = document.getElementById('feedbackWarningModal');
    const warningMessage = document.getElementById('feedbackWarningMessage');
    const form = document.getElementById('feedbackForm');
    const fullNameInput = document.getElementById('feedbackFullName');
    const anonymousCheckbox = document.getElementById('feedbackAnonymous');
    const starsHidden = document.getElementById('feedbackStars');
    const starHint = document.getElementById('feedbackStarHint');
    const starButtons = Array.from(document.querySelectorAll('.fb-star-input__btn'));
    const submitBtn = document.getElementById('feedbackSubmitBtn');
    const searchInput = document.getElementById('feedbackListSearch');
    const starFilter = document.getElementById('feedbackListStarFilter');
    let reviewCards = Array.from(document.querySelectorAll('.fb-review-card'));
    const noFilterResults = document.getElementById('feedbackNoFilterResults');
    const emptyState = document.getElementById('feedbackEmptyState');
    const summaryCountEl = document.getElementById('fbSummaryCount');

    // Uploader elements
    const imagesInput = document.getElementById('feedbackImagesInput');
    const dropzone = document.getElementById('feedbackDropzone');
    const previewsContainer = document.getElementById('feedbackPreviewsContainer');
    const imageCountText = document.getElementById('feedbackImageCountText');

    // Review Detail Modal elements
    const detailModal = document.getElementById('reviewDetailModal');
    const detailAvatar = document.getElementById('reviewDetailAvatar');
    const detailName = document.getElementById('reviewDetailName');
    const detailDate = document.getElementById('reviewDetailDate');
    const detailStarsBadge = document.getElementById('reviewDetailStarsBadge');
    const detailStars = document.getElementById('reviewDetailStars');
    const detailDescription = document.getElementById('reviewDetailDescription');
    const detailGallerySection = document.getElementById('reviewDetailGallerySection');
    const detailGalleryCount = document.getElementById('reviewDetailGalleryCount');
    const detailGalleryGrid = document.getElementById('reviewDetailGalleryGrid');

    // Lightbox elements
    const lightbox = document.getElementById('feedbackLightbox');
    const lightboxImg = document.getElementById('fbLightboxImage');
    const lightboxAvatar = document.getElementById('fbLightboxAvatar');
    const lightboxGuestName = document.getElementById('fbLightboxGuestName');
    const lightboxCounter = document.getElementById('fbLightboxCounter');
    const lightboxPrev = document.getElementById('fbLightboxPrev');
    const lightboxNext = document.getElementById('fbLightboxNext');
    const lightboxFilmstrip = document.getElementById('fbLightboxFilmstrip');

    let selectedStars = 0;
    let selectedFiles = []; // Array of File objects
    const MAX_FILES = 5;
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    // Active lightbox state
    let activeLightboxImages = [];
    let activeLightboxIndex = 0;
    let activeLightboxGuestName = '';
    let activeLightboxInitials = '';

    /* ── Write Review Modal open / close ── */

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

    const showFeedbackWarning = (message) => {
        if (!warningModal || !warningMessage) return;

        warningMessage.textContent = message;
        warningModal.classList.add('is-open');
        warningModal.setAttribute('aria-hidden', 'false');
        window.setTimeout(() => warningModal.querySelector('[data-close-feedback-warning]')?.focus(), 50);
    };

    const closeFeedbackWarning = () => {
        if (!warningModal) return;
        warningModal.classList.remove('is-open');
        warningModal.setAttribute('aria-hidden', 'true');
        document.getElementById('feedbackDescription')?.focus();
    };

    document.querySelectorAll('[data-close-feedback-warning]').forEach((el) => {
        el.addEventListener('click', closeFeedbackWarning);
    });

    document.querySelectorAll('[data-open-review-modal]').forEach((btn) => {
        btn.addEventListener('click', openModal);
    });

    document.querySelectorAll('[data-close-review-modal]').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    // Auto-open review modal if navigated from "Write a Review" button
    const feedbackUrlParams = new URLSearchParams(window.location.search);
    if (feedbackUrlParams.get('write') === 'true' || feedbackUrlParams.get('write') === '1' || window.location.hash === '#write' || window.location.hash === '#write-review') {
        window.setTimeout(() => openModal(), 200);
    }

    /* ── Card Data Extractor ── */

    const getCardData = (card) => {
        if (!card) return {};

        const dataScript = card.querySelector('.fb-card-data');
        if (dataScript) {
            try {
                const parsed = JSON.parse(dataScript.textContent);
                if (parsed && typeof parsed === 'object') {
                    return parsed;
                }
            } catch (e) {
                console.error('Failed to parse fb-card-data', e);
            }
        }

        let images = [];
        try {
            images = JSON.parse(card.dataset.images || '[]');
        } catch (_) {
            images = [];
        }

        if (!images.length) {
            const cardImgs = card.querySelectorAll('.fb-review-card__gallery img');
            if (cardImgs.length > 0) {
                images = Array.from(cardImgs).map((img, i) => ({
                    id: i + 1,
                    url: img.src,
                }));
            }
        }

        return {
            fullName: card.dataset.fullName || card.querySelector('.fb-review-card__name')?.textContent?.trim() || 'Guest',
            initials: card.dataset.initials || card.querySelector('.fb-review-card__avatar')?.textContent?.trim() || 'G',
            date: card.dataset.date || card.querySelector('.fb-review-card__date')?.textContent?.trim() || '',
            stars: parseInt(card.dataset.stars || '5', 10),
            description: card.dataset.description || card.querySelector('.fb-review-card__text')?.textContent?.trim() || '',
            replied: card.dataset.replied || card.querySelector('.fb-shopee-reply__text')?.textContent?.trim() || '',
            images: images,
        };
    };

    /* ── Review Detail Modal open / close ── */

    const openReviewDetail = (card) => {
        if (!detailModal || !card) return;

        const data = getCardData(card);
        const fullName = data.fullName || 'Guest';
        const initials = data.initials || 'G';
        const date = data.date || '';
        const stars = parseInt(data.stars || '5', 10);
        const description = data.description || '';
        const images = data.images || [];
        const replied = data.replied || '';

        if (detailAvatar) detailAvatar.textContent = initials;
        if (detailName) detailName.textContent = fullName;
        if (detailDate) detailDate.textContent = date;
        if (detailStarsBadge) detailStarsBadge.textContent = `${stars}.0`;

        if (detailStars) {
            detailStars.innerHTML = Array.from({ length: 5 }, (_, i) => {
                const filled = i + 1 <= stars ? 'is-filled' : '';
                return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="${filled}"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>`;
            }).join('');
        }

        if (detailDescription) {
            detailDescription.textContent = description;
        }

        // Show / hide Shopee management reply in modal
        const detailReplySection = document.getElementById('reviewDetailReplySection');
        const detailReplyText = document.getElementById('reviewDetailReplyText');
        if (detailReplySection && detailReplyText) {
            if (replied) {
                detailReplyText.textContent = replied;
                detailReplySection.classList.remove('hidden');
                detailReplySection.style.display = 'block';
            } else {
                detailReplySection.classList.add('hidden');
                detailReplySection.style.display = 'none';
                detailReplyText.textContent = '';
            }
        }

        if (detailGallerySection && detailGalleryGrid) {
            if (images.length > 0) {
                detailGallerySection.classList.remove('hidden');
                detailGallerySection.style.display = 'block';
                if (detailGalleryCount) {
                    detailGalleryCount.textContent = `${images.length} photo${images.length > 1 ? 's' : ''}`;
                }

                detailGalleryGrid.innerHTML = images.map((img, idx) => {
                    const url = typeof img === 'string' ? img : (img.url || img.image_url);
                    return `
                        <button type="button" class="fb-detail-gallery__item" data-detail-img-index="${idx}" aria-label="View photo ${idx + 1}">
                            <img src="${url}" alt="Review photo ${idx + 1} by ${fullName}" loading="lazy">
                        </button>
                    `;
                }).join('');

                detailGalleryGrid.querySelectorAll('[data-detail-img-index]').forEach((btn) => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const idx = parseInt(btn.dataset.detailImgIndex || '0', 10);
                        openLightbox(images, idx, fullName, initials);
                    });
                });
            } else {
                detailGallerySection.classList.add('hidden');
                detailGallerySection.style.display = 'none';
                detailGalleryGrid.innerHTML = '';
            }
        }

        detailModal.classList.add('is-open');
        detailModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    const closeReviewDetail = () => {
        if (!detailModal) return;
        detailModal.classList.remove('is-open');
        detailModal.setAttribute('aria-hidden', 'true');
        if (!modal?.classList.contains('is-open') && !lightbox?.classList.contains('is-open')) {
            document.body.style.overflow = '';
        }
    };

    document.querySelectorAll('[data-close-review-detail]').forEach((el) => {
        el.addEventListener('click', closeReviewDetail);
    });

    /* ── Escape key listener ── */

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (lightbox?.classList.contains('is-open')) {
                closeLightbox();
            } else if (warningModal?.classList.contains('is-open')) {
                closeFeedbackWarning();
            } else if (detailModal?.classList.contains('is-open')) {
                closeReviewDetail();
            } else if (modal?.classList.contains('is-open')) {
                closeModal();
            }
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

    /* ── File Upload handling ── */

    const updateFilePreviews = () => {
        if (!previewsContainer || !imageCountText) return;

        previewsContainer.innerHTML = '';
        imageCountText.textContent = `${selectedFiles.length} / ${MAX_FILES} photos`;

        selectedFiles.forEach((file, index) => {
            const previewEl = document.createElement('div');
            previewEl.className = 'fb-upload-preview';

            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.alt = file.name;
            img.onload = () => URL.revokeObjectURL(img.src);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'fb-upload-preview__remove';
            removeBtn.innerHTML = '&times;';
            removeBtn.title = 'Remove photo';
            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                removeFile(index);
            });

            previewEl.appendChild(img);
            previewEl.appendChild(removeBtn);
            previewsContainer.appendChild(previewEl);
        });
    };

    const addFiles = (newFiles) => {
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/avif'];

        for (const file of newFiles) {
            if (selectedFiles.length >= MAX_FILES) {
                alert(`You can only upload a maximum of ${MAX_FILES} photos.`);
                break;
            }

            if (!validTypes.includes(file.type)) {
                alert(`"${file.name}" is not a supported image format. Please use JPG, PNG, or WEBP.`);
                continue;
            }

            if (file.size > MAX_FILE_SIZE) {
                alert(`"${file.name}" is too large. Images must be under 5MB each.`);
                continue;
            }

            selectedFiles.push(file);
        }

        updateFilePreviews();
    };

    const removeFile = (index) => {
        selectedFiles.splice(index, 1);
        updateFilePreviews();
    };

    imagesInput?.addEventListener('change', (e) => {
        if (e.target.files) {
            addFiles(Array.from(e.target.files));
        }
        imagesInput.value = '';
    });

    // Drag and drop support
    if (dropzone) {
        ['dragenter', 'dragover'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone.addEventListener('drop', (e) => {
            if (e.dataTransfer?.files) {
                addFiles(Array.from(e.dataTransfer.files));
            }
        });
    }

    /* ── Lightbox viewer ── */

    const openLightbox = (images, initialIndex = 0, guestName = 'Guest', initials = 'G') => {
        if (!images || images.length === 0 || !lightbox) return;

        activeLightboxImages = images;
        activeLightboxIndex = Math.max(0, Math.min(initialIndex, images.length - 1));
        activeLightboxGuestName = guestName;
        activeLightboxInitials = initials;

        renderLightboxStage();
        lightbox.classList.add('is-open');
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    const closeLightbox = () => {
        if (!lightbox) return;
        lightbox.classList.remove('is-open');
        lightbox.setAttribute('aria-hidden', 'true');
        if (!modal?.classList.contains('is-open') && !detailModal?.classList.contains('is-open')) {
            document.body.style.overflow = '';
        }
    };

    const renderLightboxStage = () => {
        if (!activeLightboxImages.length) return;

        const current = activeLightboxImages[activeLightboxIndex];
        const url = typeof current === 'string' ? current : (current.url || current.image_url);

        if (lightboxImg) {
            lightboxImg.src = url;
            lightboxImg.alt = `Photo by ${activeLightboxGuestName} (${activeLightboxIndex + 1} of ${activeLightboxImages.length})`;
        }

        if (lightboxAvatar) {
            lightboxAvatar.textContent = activeLightboxInitials || 'G';
        }

        if (lightboxGuestName) {
            lightboxGuestName.textContent = activeLightboxGuestName || 'Guest';
        }

        if (lightboxCounter) {
            lightboxCounter.textContent = `${activeLightboxIndex + 1} / ${activeLightboxImages.length}`;
        }

        if (lightboxPrev) {
            lightboxPrev.disabled = activeLightboxImages.length <= 1;
        }

        if (lightboxNext) {
            lightboxNext.disabled = activeLightboxImages.length <= 1;
        }

        if (lightboxFilmstrip) {
            if (activeLightboxImages.length > 1) {
                lightboxFilmstrip.innerHTML = activeLightboxImages.map((img, idx) => {
                    const imgUrl = typeof img === 'string' ? img : (img.url || img.image_url);
                    const isActive = idx === activeLightboxIndex ? 'is-active' : '';
                    return `
                        <button type="button" class="fb-lightbox__filmstrip-item ${isActive}" data-film-index="${idx}" aria-label="Go to photo ${idx + 1}">
                            <img src="${imgUrl}" alt="Photo ${idx + 1}" loading="lazy">
                        </button>
                    `;
                }).join('');

                lightboxFilmstrip.querySelectorAll('[data-film-index]').forEach((btn) => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        activeLightboxIndex = parseInt(btn.dataset.filmIndex, 10);
                        renderLightboxStage();
                    });
                });
            } else {
                lightboxFilmstrip.innerHTML = '';
            }
        }
    };

    const showPrevLightbox = () => {
        if (activeLightboxImages.length <= 1) return;
        activeLightboxIndex = (activeLightboxIndex - 1 + activeLightboxImages.length) % activeLightboxImages.length;
        renderLightboxStage();
    };

    const showNextLightbox = () => {
        if (activeLightboxImages.length <= 1) return;
        activeLightboxIndex = (activeLightboxIndex + 1) % activeLightboxImages.length;
        renderLightboxStage();
    };

    lightboxPrev?.addEventListener('click', showPrevLightbox);
    lightboxNext?.addEventListener('click', showNextLightbox);

    document.querySelectorAll('[data-close-lightbox]').forEach((el) => {
        el.addEventListener('click', closeLightbox);
    });

    document.addEventListener('keydown', (e) => {
        if (!lightbox?.classList.contains('is-open')) return;
        if (e.key === 'ArrowLeft') showPrevLightbox();
        if (e.key === 'ArrowRight') showNextLightbox();
    });

    /* ── Click handling on review cards & gallery thumbs ── */

    document.addEventListener('click', (e) => {
        // If clicking a gallery thumbnail directly on the card
        const thumb = e.target.closest('.fb-gallery-thumb');
        if (thumb) {
            const card = thumb.closest('.fb-review-card');
            if (card) {
                const data = getCardData(card);
                if (data.images && data.images.length) {
                    const index = parseInt(thumb.dataset.imgIndex || '0', 10);
                    openLightbox(data.images, index, data.fullName, data.initials);
                    return;
                }
            }
        }

        // If clicking anywhere else on the review card
        const card = e.target.closest('.fb-review-card');
        if (card) {
            openReviewDetail(card);
        }
    });

    document.addEventListener('keydown', (e) => {
        if ((e.key === 'Enter' || e.key === ' ') && document.activeElement?.classList.contains('fb-review-card')) {
            e.preventDefault();
            openReviewDetail(document.activeElement);
        }
    });

    /* ── Shopee filter pills & search ── */

    const shopeePills = Array.from(document.querySelectorAll('.shopee-pill'));
    let currentShopeeFilter = 'all';

    shopeePills.forEach((pill) => {
        pill.addEventListener('click', () => {
            shopeePills.forEach((p) => p.classList.remove('is-active'));
            pill.classList.add('is-active');
            currentShopeeFilter = pill.dataset.shopeeFilter || 'all';
            filterReviews();
        });
    });

    const filterReviews = () => {
        const query = searchInput?.value.trim().toLowerCase() || '';
        let visibleCount = 0;

        reviewCards = Array.from(document.querySelectorAll('.fb-review-card'));

        reviewCards.forEach((card) => {
            const guestName = card.dataset.guestName || '';
            const text = card.querySelector('.fb-review-card__text')?.textContent?.toLowerCase() || '';
            const replyText = card.querySelector('.fb-shopee-reply__text')?.textContent?.toLowerCase() || '';
            const matchesSearch = !query || guestName.includes(query) || text.includes(query) || replyText.includes(query);

            const stars = card.dataset.stars || '';
            const hasMedia = card.dataset.hasMedia === '1';
            const hasComment = card.dataset.hasComment === '1';

            let matchesFilter = true;
            if (currentShopeeFilter === 'all') {
                matchesFilter = true;
            } else if (['1', '2', '3', '4', '5'].includes(currentShopeeFilter)) {
                matchesFilter = stars === currentShopeeFilter;
            } else if (currentShopeeFilter === 'media') {
                matchesFilter = hasMedia;
            } else if (currentShopeeFilter === 'comments') {
                matchesFilter = hasComment;
            }

            const show = matchesSearch && matchesFilter;
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
        reviewCards = Array.from(document.querySelectorAll('.fb-review-card'));
        const total = reviewCards.length;
        let starSum = 0;
        let count5 = 0, count4 = 0, count3 = 0, count2 = 0, count1 = 0;
        let countMedia = 0, countComments = 0;

        reviewCards.forEach((card) => {
            const s = parseInt(card.dataset.stars || '5', 10);
            starSum += s;
            if (s === 5) count5++;
            else if (s === 4) count4++;
            else if (s === 3) count3++;
            else if (s === 2) count2++;
            else if (s === 1) count1++;
            if (card.dataset.hasMedia === '1') countMedia++;
            if (card.dataset.hasComment === '1') countComments++;
        });

        const avg = total > 0 ? (starSum / total).toFixed(1) : '5.0';
        const summaryAvgEl = document.getElementById('fbSummaryAvg');
        if (summaryAvgEl) summaryAvgEl.textContent = avg;
        if (summaryCountEl) summaryCountEl.textContent = String(total);

        const pillAll = document.getElementById('countPillAll');
        if (pillAll) pillAll.textContent = String(total);
        const pill5 = document.getElementById('countPill5');
        if (pill5) pill5.textContent = String(count5);
        const pill4 = document.getElementById('countPill4');
        if (pill4) pill4.textContent = String(count4);
        const pill3 = document.getElementById('countPill3');
        if (pill3) pill3.textContent = String(count3);
        const pill2 = document.getElementById('countPill2');
        if (pill2) pill2.textContent = String(count2);
        const pill1 = document.getElementById('countPill1');
        if (pill1) pill1.textContent = String(count1);
        const pillMedia = document.getElementById('countPillMedia');
        if (pillMedia) pillMedia.textContent = String(countMedia);
        const pillComments = document.getElementById('countPillComments');
        if (pillComments) pillComments.textContent = String(countComments);
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

        const images = feedback.images || [];
        const imagesCount = images.length;
        const imagesJson = JSON.stringify(images);
        const hasComment = Boolean(feedback.description && feedback.description.trim());

        const article = document.createElement('article');
        article.className = 'fb-review-card shopee-review-card';
        article.tabIndex = 0;
        article.setAttribute('role', 'button');
        article.setAttribute('aria-label', `View full review by ${feedback.full_name}`);
        article.dataset.guestName = ((feedback.full_name || '') + ' ' + (feedback.raw_name || '')).toLowerCase();
        article.dataset.fullName = feedback.full_name || 'Guest';
        article.dataset.initials = feedback.initials || 'G';
        article.dataset.date = feedback.created_at || 'Just now';
        article.dataset.stars = String(feedback.stars);
        article.dataset.hasMedia = imagesCount > 0 ? '1' : '0';
        article.dataset.hasComment = hasComment ? '1' : '0';
        article.dataset.description = feedback.description || '';
        article.dataset.replied = feedback.replied || '';
        article.dataset.images = imagesJson;

        const starsHtml = Array.from({ length: 5 }, (_, i) => {
            const filled = i + 1 <= feedback.stars ? 'is-filled' : '';
            return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="${filled}"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>`;
        }).join('');

        let galleryHtml = '';
        if (imagesCount === 1) {
            galleryHtml = `
                <div class="fb-review-card__gallery" aria-label="Attached review photos">
                    <button type="button" class="fb-gallery-thumb fb-gallery-thumb--single" data-img-index="0" aria-label="View photo by ${escapeHtml(feedback.full_name)}">
                        <img src="${escapeHtml(images[0].image_url || images[0].url)}" alt="Review photo by ${escapeHtml(feedback.full_name)}" loading="lazy">
                    </button>
                </div>
            `;
        } else if (imagesCount === 2) {
            galleryHtml = `
                <div class="fb-review-card__gallery" aria-label="Attached review photos">
                    <div class="fb-gallery-grid fb-gallery-grid--2">
                        <button type="button" class="fb-gallery-thumb" data-img-index="0" aria-label="View photo 1 by ${escapeHtml(feedback.full_name)}">
                            <img src="${escapeHtml(images[0].image_url || images[0].url)}" alt="Review photo 1 by ${escapeHtml(feedback.full_name)}" loading="lazy">
                        </button>
                        <button type="button" class="fb-gallery-thumb" data-img-index="1" aria-label="View photo 2 by ${escapeHtml(feedback.full_name)}">
                            <img src="${escapeHtml(images[1].image_url || images[1].url)}" alt="Review photo 2 by ${escapeHtml(feedback.full_name)}" loading="lazy">
                        </button>
                    </div>
                </div>
            `;
        } else if (imagesCount >= 3) {
            galleryHtml = `
                <div class="fb-review-card__gallery" aria-label="Attached review photos">
                    <div class="fb-gallery-grid fb-gallery-grid--multiple">
                        <button type="button" class="fb-gallery-thumb" data-img-index="0" aria-label="View photo 1 by ${escapeHtml(feedback.full_name)}">
                            <img src="${escapeHtml(images[0].image_url || images[0].url)}" alt="Review photo 1 by ${escapeHtml(feedback.full_name)}" loading="lazy">
                        </button>
                        <button type="button" class="fb-gallery-thumb fb-gallery-thumb--overlay" data-img-index="1" aria-label="View ${imagesCount - 1} more photos">
                            <img src="${escapeHtml(images[1].image_url || images[1].url)}" alt="Review photo 2 by ${escapeHtml(feedback.full_name)}" loading="lazy">
                            <span class="fb-gallery-thumb__badge">+${imagesCount - 1}</span>
                        </button>
                    </div>
                </div>
            `;
        }

        const replyHtml = feedback.replied ? `
            <div class="fb-shopee-reply" aria-label="Park Management Response">
                <div class="fb-shopee-reply__header">
                    <svg class="fb-shopee-reply__badge-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                    <span class="fb-shopee-reply__title">Park Management Response:</span>
                </div>
                <p class="fb-shopee-reply__text">${escapeHtml(feedback.replied)}</p>
            </div>
        ` : '';

        article.innerHTML = `
            <div class="fb-review-card__top">
                <span class="fb-review-card__avatar" aria-hidden="true">${escapeHtml(feedback.initials || 'G')}</span>
                <div class="fb-review-card__meta">
                    <div class="shopee-review-card__user-row">
                        <h3 class="fb-review-card__name">${escapeHtml(feedback.full_name)}</h3>
                    </div>
                    <time class="fb-review-card__date">${escapeHtml(feedback.created_at || 'Just now')} | Hinaguan Nature Park Experience</time>
                </div>
                <div class="fb-review-card__badge" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <span>${Number(feedback.stars)}.0</span>
                </div>
            </div>
            <div class="fb-review-card__stars" aria-label="${Number(feedback.stars)} out of 5 stars">${starsHtml}</div>
            <p class="fb-review-card__text"></p>
            ${galleryHtml}
            ${replyHtml}
            <div class="fb-review-card__footer">
                <span class="fb-review-card__readmore">Click to read full review &rarr;</span>
            </div>
        `;

        const dataScript = document.createElement('script');
        dataScript.type = 'application/json';
        dataScript.className = 'fb-card-data';
        dataScript.textContent = JSON.stringify({
            fullName: feedback.full_name || 'Guest',
            rawName: feedback.raw_name || feedback.full_name || 'Guest',
            initials: feedback.initials || 'G',
            date: feedback.created_at || 'Just now',
            stars: feedback.stars,
            description: feedback.description || '',
            replied: feedback.replied || '',
            images: images.map((img, i) => ({
                id: img.id || (i + 1),
                url: img.image_url || img.url,
            })),
        });
        article.appendChild(dataScript);

        article.querySelector('.fb-review-card__text').textContent = feedback.description;
        list.prepend(article);
        reviewCards.unshift(article);
        updateSummaryCount();
        filterReviews();
    };

    /* ── Submit Form with FormData ── */

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const isAnonymous = anonymousCheckbox?.checked;
        const fullName = fullNameInput?.value.trim() || '';
        const description = document.getElementById('feedbackDescription')?.value.trim() || '';
        const stars = parseInt(starsHidden?.value || '0', 10);

        if (!isAnonymous && !fullName) {
            showFeedbackWarning('Please enter your full name or choose anonymous feedback.');
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
            showFeedbackWarning('Please write your feedback before submitting.');
            document.getElementById('feedbackDescription')?.focus();
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';

        const formData = new FormData();
        formData.append('full_name', fullName);
        formData.append('is_anonymous', isAnonymous ? '1' : '0');
        formData.append('description', description);
        formData.append('stars', String(stars));

        selectedFiles.forEach((file) => {
            formData.append('images[]', file);
        });

        try {
            const response = await fetch('/feedback', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: formData,
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Unable to submit feedback.');
            }

            form.reset();
            selectedFiles = [];
            updateFilePreviews();
            renderStars(0);
            syncAnonymousState();
            prependReviewCard(data.feedback);
            closeModal();
        } catch (error) {
            showFeedbackWarning(error.message || 'Unable to submit feedback. Please try again.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Send Feedback';
        }
    });
});
