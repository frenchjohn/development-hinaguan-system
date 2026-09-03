<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Guest Reviews — Hinaguan Nature Park</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('storage/design_images/main_logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700|poppins:300,400,500,600,700" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/css/feedback.css',
        'resources/css/chatbot.css',
        'resources/js/feedback.js',
        'resources/js/guest_chatbot.js',
    ])
</head>
<body class="antialiased fb-page">

    <div class="fb-site-header" id="fbSiteHeader">
        <div class="fb-topbar {{ ($parkSettings->park_status ?? 'open') === 'closed' ? 'fb-topbar--closed' : '' }}">
            <div class="fb-topbar__inner">
                @if (($parkSettings->park_status ?? 'open') === 'closed')
                    <p class="fb-topbar__text">Park Closed — {{ $parkSettings->close_description ?: 'Temporarily closed' }}</p>
                @else
                    <p class="fb-topbar__text">
                        <strong>Now Open!</strong>
                        &nbsp;|&nbsp; Call: {{ $parkSettings->contact_number ?? '0917 861 8383' }}
                    </p>
                @endif
            </div>
        </div>
        <header class="fb-header">
            <div class="fb-header__inner">
                <a href="{{ route('home') }}" class="fb-logo">
                    <span class="fb-logo__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-1.5 2.5-4 5-4 8a4 4 0 108 0c0-3-2.5-5.5-4-8z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8M10 18h4"/></svg>
                    </span>
                    <span class="fb-logo__text">
                        <span class="fb-logo__name">Hinaguan Nature Park</span>
                        <span class="fb-logo__location">Jasaan, Misamis Oriental</span>
                    </span>
                </a>
                <nav class="fb-nav">
                    <a href="{{ route('home') }}" class="fb-nav__back">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                        Back to Home
                    </a>
                    <button type="button" id="fbOpenReviewModalTop" class="fb-btn fb-btn--book">Write a Review</button>
                </nav>
            </div>
        </header>
    </div>

    <main class="fb-main">
        <div class="fb-container">
            <header class="fb-hero">
                <span class="fb-hero__label">Guest Reviews</span>
                <h1 class="fb-hero__title">Stories From Our Guests</h1>
                <p class="fb-hero__desc">Every visit leaves a mark. Read honest experiences from people who explored Hinaguan Nature Park — and share your own.</p>

                @if (session('success'))
                    <div class="fb-alert fb-alert--success" role="status">{{ session('success') }}</div>
                @endif
            </header>

            <section class="fb-summary" aria-label="Review summary">
                <div class="fb-summary__score">
                    <span class="fb-summary__avg" id="fbSummaryAvg">{{ $feedbacks->count() ? number_format($feedbacks->avg('stars'), 1) : '&ndash;' }}</span>
                    <span class="fb-summary__stars" aria-hidden="true">
                        @for ($s = 1; $s <= 5; $s++)
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="{{ $feedbacks->count() && $s <= ceil($feedbacks->avg('stars')) ? 'is-filled' : '' }}"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        @endfor
                    </span>
                    <span class="fb-summary__label">Average rating</span>
                </div>
                <div class="fb-summary__divider"></div>
                <div class="fb-summary__count">
                    <span class="fb-summary__number" id="fbSummaryCount">{{ $feedbacks->count() }}</span>
                    <span class="fb-summary__label">Review{{ $feedbacks->count() === 1 ? '' : 's' }} shared</span>
                </div>
                <button type="button" class="fb-btn fb-btn--gold fb-summary__cta" data-open-review-modal>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                    Write a Review
                </button>
            </section>

            <div class="fb-toolbar">
                <input type="search" id="feedbackListSearch" placeholder="Search by name..." class="fb-filter-input" autocomplete="off">
                <select id="feedbackListStarFilter" class="fb-filter-select" aria-label="Filter by star rating">
                    <option value="all">All stars</option>
                    @for ($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}">{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                    @endfor
                </select>
            </div>

            <section class="fb-review-grid" id="feedbackReviewList" aria-live="polite">
                @forelse ($feedbacks as $feedback)
                    @php
                        $imagesCount = $feedback->images ? $feedback->images->count() : 0;
                        $imagesJson = $imagesCount > 0 ? $feedback->images->map(fn($img) => [
                            'id' => $img->id,
                            'url' => $img->image_url,
                        ])->values()->toJson() : '[]';
                    @endphp
                    <article
                        class="fb-review-card"
                        data-guest-name="{{ strtolower($feedback->full_name) }}"
                        data-stars="{{ $feedback->stars }}"
                        tabindex="0"
                        role="button"
                        aria-label="View full review by {{ $feedback->full_name }}"
                    >
                        <script type="application/json" class="fb-card-data">
                            {!! json_encode([
                                'fullName' => $feedback->full_name,
                                'initials' => $feedback->initials,
                                'date' => $feedback->created_at->format('M j, Y'),
                                'stars' => $feedback->stars,
                                'description' => $feedback->description,
                                'images' => $feedback->images ? $feedback->images->map(fn($img) => [
                                    'id' => $img->id,
                                    'url' => $img->image_url,
                                ])->values() : [],
                            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
                        </script>

                        <div class="fb-review-card__top">
                            <span class="fb-review-card__avatar" aria-hidden="true">{{ $feedback->initials }}</span>
                            <div class="fb-review-card__meta">
                                <h3 class="fb-review-card__name">{{ $feedback->full_name }}</h3>
                                <time class="fb-review-card__date" datetime="{{ $feedback->created_at->toDateString() }}">{{ $feedback->created_at->format('M j, Y') }}</time>
                            </div>
                            <div class="fb-review-card__badge" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                <span>{{ $feedback->stars }}.0</span>
                            </div>
                        </div>
                        <div class="fb-review-card__stars" aria-label="{{ $feedback->stars }} out of 5 stars">
                            @for ($s = 1; $s <= 5; $s++)
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="{{ $s <= $feedback->stars ? 'is-filled' : '' }}"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            @endfor
                        </div>
                        <p class="fb-review-card__text">{{ $feedback->description }}</p>

                        @if ($imagesCount > 0)
                            <div class="fb-review-card__gallery" aria-label="Attached review photos">
                                @if ($imagesCount === 1)
                                    <button type="button" class="fb-gallery-thumb fb-gallery-thumb--single" data-img-index="0" aria-label="View photo by {{ $feedback->full_name }}">
                                        <img src="{{ $feedback->images[0]->image_url }}" alt="Review photo by {{ $feedback->full_name }}" loading="lazy">
                                    </button>
                                @elseif ($imagesCount === 2)
                                    <div class="fb-gallery-grid fb-gallery-grid--2">
                                        <button type="button" class="fb-gallery-thumb" data-img-index="0" aria-label="View photo 1 by {{ $feedback->full_name }}">
                                            <img src="{{ $feedback->images[0]->image_url }}" alt="Review photo 1 by {{ $feedback->full_name }}" loading="lazy">
                                        </button>
                                        <button type="button" class="fb-gallery-thumb" data-img-index="1" aria-label="View photo 2 by {{ $feedback->full_name }}">
                                            <img src="{{ $feedback->images[1]->image_url }}" alt="Review photo 2 by {{ $feedback->full_name }}" loading="lazy">
                                        </button>
                                    </div>
                                @else
                                    <div class="fb-gallery-grid fb-gallery-grid--multiple">
                                        <button type="button" class="fb-gallery-thumb" data-img-index="0" aria-label="View photo 1 by {{ $feedback->full_name }}">
                                            <img src="{{ $feedback->images[0]->image_url }}" alt="Review photo 1 by {{ $feedback->full_name }}" loading="lazy">
                                        </button>
                                        <button type="button" class="fb-gallery-thumb fb-gallery-thumb--overlay" data-img-index="1" aria-label="View {{ $imagesCount - 1 }} more photos">
                                            <img src="{{ $feedback->images[1]->image_url }}" alt="Review photo 2 by {{ $feedback->full_name }}" loading="lazy">
                                            <span class="fb-gallery-thumb__badge">+{{ $imagesCount - 1 }}</span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="fb-review-card__footer">
                            <span class="fb-review-card__readmore">Click to read full review &rarr;</span>
                        </div>
                    </article>
                @empty
                    <p class="fb-empty" id="feedbackEmptyState">No reviews yet. Be the first to share your experience!</p>
                @endforelse
            </section>
            <p class="fb-empty hidden" id="feedbackNoFilterResults">No reviews match your filters.</p>
        </div>
    </main>

    <button type="button" class="fb-fab" data-open-review-modal aria-label="Write a review">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5V19a2 2 0 01-2 2H5a2 2 0 01-2-2V6.5A2 2 0 015 4.5h8.5"/></svg>
    </button>

    {{-- Full Review Detail Modal for Guests --}}
    <div class="fb-modal" id="reviewDetailModal" aria-hidden="true" role="dialog" aria-labelledby="reviewDetailName">
        <div class="fb-modal__backdrop" data-close-review-detail></div>
        <div class="fb-modal__panel fb-modal__panel--detail" role="document">
            <button type="button" class="fb-modal__close" data-close-review-detail aria-label="Close">&times;</button>

            <div class="fb-detail-header">
                <span class="fb-review-card__avatar fb-detail-avatar" id="reviewDetailAvatar" aria-hidden="true"></span>
                <div class="fb-review-card__meta">
                    <h3 class="fb-review-card__name fb-detail-name" id="reviewDetailName"></h3>
                    <time class="fb-review-card__date" id="reviewDetailDate"></time>
                </div>
                <div class="fb-review-card__badge" id="reviewDetailBadge">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <span id="reviewDetailStarsBadge"></span>
                </div>
            </div>

            <div class="fb-review-card__stars fb-detail-stars" id="reviewDetailStars" aria-label="Rating"></div>

            <div class="fb-detail-body">
                <p class="fb-detail-text" id="reviewDetailDescription"></p>

                <div class="fb-detail-gallery hidden" id="reviewDetailGallerySection">
                    <div class="fb-detail-gallery__header">
                        <span class="fb-detail-gallery__title">Attached Photos</span>
                        <span class="fb-detail-gallery__count" id="reviewDetailGalleryCount"></span>
                    </div>
                    <div class="fb-detail-gallery__grid" id="reviewDetailGalleryGrid"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Write a Review Modal --}}
    <div class="fb-modal" id="reviewModal" aria-hidden="true" role="dialog" aria-labelledby="reviewModalTitle">
        <div class="fb-modal__backdrop" data-close-review-modal></div>
        <div class="fb-modal__panel" role="document">
            <button type="button" class="fb-modal__close" data-close-review-modal aria-label="Close">&times;</button>

            <h2 id="reviewModalTitle" class="fb-panel__title">Write a Review</h2>
            <p class="fb-panel__subtitle">Your feedback helps us improve and inspires future guests.</p>

            <form id="feedbackForm" class="fb-form" novalidate enctype="multipart/form-data">
                @csrf
                <div class="fb-field">
                    <label for="feedbackFullName" class="fb-label">Full Name</label>
                    <input type="text" id="feedbackFullName" name="full_name" maxlength="255" placeholder="Enter your full name" class="fb-input" autocomplete="name">
                </div>

                <label class="fb-checkbox">
                    <input type="checkbox" id="feedbackAnonymous" name="is_anonymous" value="1">
                    <span class="fb-checkbox__box" aria-hidden="true"></span>
                    <span>Submit as anonymous feedback</span>
                </label>

                <div class="fb-field">
                    <label class="fb-label">Your Rating</label>
                    <div class="fb-star-input" id="feedbackStarInput" role="radiogroup" aria-label="Star rating">
                        @for ($s = 1; $s <= 5; $s++)
                            <button type="button" class="fb-star-input__btn" data-star="{{ $s }}" aria-label="{{ $s }} star{{ $s > 1 ? 's' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            </button>
                        @endfor
                    </div>
                    <input type="hidden" name="stars" id="feedbackStars" value="">
                    <p class="fb-field-hint" id="feedbackStarHint">Tap a star to rate your visit.</p>
                </div>

                <div class="fb-field">
                    <label for="feedbackDescription" class="fb-label">Your Feedback</label>
                    <textarea id="feedbackDescription" name="description" rows="4" maxlength="2000" required placeholder="Tell us about your experience at Hinaguan Nature Park..." class="fb-textarea"></textarea>
                </div>

                <div class="fb-field">
                    <div class="fb-field__header">
                        <label class="fb-label">Attach Photos <span class="fb-label__optional">(Optional, max 5)</span></label>
                        <span class="fb-uploader__count" id="feedbackImageCountText">0 / 5 photos</span>
                    </div>
                    <div class="fb-uploader" id="feedbackUploader">
                        <input type="file" id="feedbackImagesInput" name="images[]" multiple accept="image/png,image/jpeg,image/jpg,image/webp,image/avif" class="fb-uploader__input">
                        <label for="feedbackImagesInput" class="fb-uploader__dropzone" id="feedbackDropzone">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="fb-uploader__icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                            </svg>
                            <span class="fb-uploader__text"><strong>Choose photos</strong> or drag & drop</span>
                            <span class="fb-uploader__hint">PNG, JPG, WEBP up to 5MB</span>
                        </label>
                        <div class="fb-uploader__previews" id="feedbackPreviewsContainer"></div>
                    </div>
                </div>

                <button type="submit" class="fb-submit" id="feedbackSubmitBtn">Send Feedback</button>
            </form>
        </div>
    </div>

    {{-- Photo Lightbox Modal --}}
    <div class="fb-lightbox" id="feedbackLightbox" aria-hidden="true" role="dialog" aria-label="Photo preview">
        <div class="fb-lightbox__backdrop" data-close-lightbox></div>
        
        {{-- Floating Top Header --}}
        <div class="fb-lightbox__header">
            <div class="fb-lightbox__guest-info">
                <span class="fb-lightbox__avatar" id="fbLightboxAvatar">G</span>
                <div>
                    <span class="fb-lightbox__guest-name" id="fbLightboxGuestName">Guest</span>
                    <span class="fb-lightbox__sub">Review Photo</span>
                </div>
            </div>
            
            <div class="fb-lightbox__actions">
                <span class="fb-lightbox__counter-badge" id="fbLightboxCounter">1 / 1</span>
                <button type="button" class="fb-lightbox__close-btn" data-close-lightbox aria-label="Close photo preview">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Floating Previous Button --}}
        <button type="button" class="fb-lightbox__nav fb-lightbox__nav--prev" id="fbLightboxPrev" aria-label="Previous photo">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        </button>

        {{-- Main Immersive Stage --}}
        <div class="fb-lightbox__stage">
            <img id="fbLightboxImage" src="" alt="Full size preview" class="fb-lightbox__img">
        </div>

        {{-- Floating Next Button --}}
        <button type="button" class="fb-lightbox__nav fb-lightbox__nav--next" id="fbLightboxNext" aria-label="Next photo">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        </button>

        {{-- Bottom Filmstrip Thumbnails --}}
        <div class="fb-lightbox__filmstrip" id="fbLightboxFilmstrip"></div>
    </div>

    <x-guest_chatbot />
</body>
</html>
