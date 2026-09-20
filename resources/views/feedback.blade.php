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
        <header class="fb-header">
            <div class="fb-header__inner">
                <a href="{{ route('home') }}" class="fb-nav__back">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                    <span>Back to Home</span>
                </a>
            </div>
        </header>
    </div>

    <main class="fb-main">
        <div class="fb-container">
            @php
                $totalFeedbacks = $feedbacks->count();
                $avgRating = $totalFeedbacks > 0 ? number_format($feedbacks->avg('stars'), 1) : '5.0';
                $count5 = $feedbacks->where('stars', 5)->count();
                $count4 = $feedbacks->where('stars', 4)->count();
                $count3 = $feedbacks->where('stars', 3)->count();
                $count2 = $feedbacks->where('stars', 2)->count();
                $count1 = $feedbacks->where('stars', 1)->count();
                $countMedia = $feedbacks->filter(fn($f) => $f->images && $f->images->count() > 0)->count();
                $countComments = $feedbacks->filter(fn($f) => !empty(trim($f->description)))->count();
            @endphp

            <header class="fb-hero">
                <span class="fb-hero__label">Guest Reviews</span>
                <h1 class="fb-hero__title">Stories From Our Guests</h1>
                <p class="fb-hero__desc">Every visit leaves a mark. Read honest experiences from people who explored Hinaguan Nature Park — and share your own.</p>

                @if (session('success'))
                    <div class="fb-alert fb-alert--success" role="status">{{ session('success') }}</div>
                @endif
            </header>

            {{-- Shopee-style Rating Summary & Filter Box --}}
            <section class="shopee-rating-card" aria-label="Review summary and filters">
                <div class="shopee-rating-card__left">
                    <div class="shopee-rating-card__score-wrap">
                        <span class="shopee-rating-card__score" id="fbSummaryAvg">{{ $avgRating }}</span>
                        <span class="shopee-rating-card__score-sub">out of 5</span>
                    </div>
                    <div class="shopee-rating-card__stars" aria-hidden="true">
                        @for ($s = 1; $s <= 5; $s++)
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="{{ $totalFeedbacks && $s <= ceil($feedbacks->avg('stars')) ? 'is-filled' : '' }}"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        @endfor
                    </div>
                    <span class="shopee-rating-card__total-text">
                        <span id="fbSummaryCount">{{ $totalFeedbacks }}</span> Total Review{{ $totalFeedbacks === 1 ? '' : 's' }}
                    </span>
                </div>

                <div class="shopee-rating-card__right">
                    <div class="shopee-filter-pills" role="tablist" aria-label="Filter reviews">
                        <button type="button" class="shopee-pill is-active" data-shopee-filter="all">All (<span class="shopee-pill__count" id="countPillAll">{{ $totalFeedbacks }}</span>)</button>
                        <button type="button" class="shopee-pill" data-shopee-filter="5">5 Star (<span class="shopee-pill__count" id="countPill5">{{ $count5 }}</span>)</button>
                        <button type="button" class="shopee-pill" data-shopee-filter="4">4 Star (<span class="shopee-pill__count" id="countPill4">{{ $count4 }}</span>)</button>
                        <button type="button" class="shopee-pill" data-shopee-filter="3">3 Star (<span class="shopee-pill__count" id="countPill3">{{ $count3 }}</span>)</button>
                        <button type="button" class="shopee-pill" data-shopee-filter="2">2 Star (<span class="shopee-pill__count" id="countPill2">{{ $count2 }}</span>)</button>
                        <button type="button" class="shopee-pill" data-shopee-filter="1">1 Star (<span class="shopee-pill__count" id="countPill1">{{ $count1 }}</span>)</button>
                        <button type="button" class="shopee-pill" data-shopee-filter="media">With Media (<span class="shopee-pill__count" id="countPillMedia">{{ $countMedia }}</span>)</button>
                        <button type="button" class="shopee-pill" data-shopee-filter="comments">With Comments (<span class="shopee-pill__count" id="countPillComments">{{ $countComments }}</span>)</button>
                    </div>

                    <div class="shopee-rating-card__actions">
                        <div class="shopee-search-wrap">
                            <svg class="shopee-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="search" id="feedbackListSearch" placeholder="Search guest reviews..." class="shopee-search-input" autocomplete="off">
                        </div>
                        <button type="button" class="fb-btn fb-btn--gold shopee-write-btn" data-open-review-modal>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                            Write a Review
                        </button>
                    </div>
                </div>
            </section>

            <section class="fb-review-grid" id="feedbackReviewList" aria-live="polite">
                @forelse ($feedbacks as $feedback)
                    @php
                        $imagesCount = $feedback->images ? $feedback->images->count() : 0;
                        $hasComment = !empty(trim($feedback->description));
                        $reviewerName = $feedback->full_name ?: 'Anonymous Guest';
                    @endphp
                    <article
                        class="fb-review-card shopee-review-card"
                        data-guest-name="{{ strtolower($reviewerName) }}"
                        data-stars="{{ $feedback->stars }}"
                        data-has-media="{{ $imagesCount > 0 ? '1' : '0' }}"
                        data-has-comment="{{ $hasComment ? '1' : '0' }}"
                        tabindex="0"
                        role="button"
                        aria-label="View full review by {{ $reviewerName }}"
                    >
                        <script type="application/json" class="fb-card-data">
                            {!! json_encode([
                                'fullName' => $reviewerName,
                                'rawName' => $feedback->full_name,
                                'initials' => $feedback->initials,
                                'date' => $feedback->created_at->format('M j, Y'),
                                'stars' => $feedback->stars,
                                'description' => $feedback->description,
                                'replied' => $feedback->replied,
                                'images' => $feedback->images ? $feedback->images->map(fn($img) => [
                                    'id' => $img->id,
                                    'url' => $img->image_url,
                                ])->values() : [],
                            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
                        </script>

                        <div class="fb-review-card__top">
                            <span class="fb-review-card__avatar" aria-hidden="true">{{ $feedback->initials }}</span>
                            <div class="fb-review-card__meta">
                                <div class="shopee-review-card__user-row">
                                    <h3 class="fb-review-card__name">{{ $reviewerName }}</h3>
                                </div>
                                <time class="fb-review-card__date" datetime="{{ $feedback->created_at->toDateString() }}">
                                    {{ $feedback->created_at->format('Y-m-d H:i') }} | Hinaguan Nature Park Experience
                                </time>
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
                                    <button type="button" class="fb-gallery-thumb fb-gallery-thumb--single" data-img-index="0" aria-label="View photo by {{ $reviewerName }}">
                                        <img src="{{ $feedback->images[0]->image_url }}" alt="Review photo by {{ $reviewerName }}" loading="lazy">
                                    </button>
                                @elseif ($imagesCount === 2)
                                    <div class="fb-gallery-grid fb-gallery-grid--2">
                                        <button type="button" class="fb-gallery-thumb" data-img-index="0" aria-label="View photo 1 by {{ $reviewerName }}">
                                            <img src="{{ $feedback->images[0]->image_url }}" alt="Review photo 1 by {{ $reviewerName }}" loading="lazy">
                                        </button>
                                        <button type="button" class="fb-gallery-thumb" data-img-index="1" aria-label="View photo 2 by {{ $reviewerName }}">
                                            <img src="{{ $feedback->images[1]->image_url }}" alt="Review photo 2 by {{ $reviewerName }}" loading="lazy">
                                        </button>
                                    </div>
                                @else
                                    <div class="fb-gallery-grid fb-gallery-grid--multiple">
                                        <button type="button" class="fb-gallery-thumb" data-img-index="0" aria-label="View photo 1 by {{ $reviewerName }}">
                                            <img src="{{ $feedback->images[0]->image_url }}" alt="Review photo 1 by {{ $reviewerName }}" loading="lazy">
                                        </button>
                                        <button type="button" class="fb-gallery-thumb fb-gallery-thumb--overlay" data-img-index="1" aria-label="View {{ $imagesCount - 1 }} more photos">
                                            <img src="{{ $feedback->images[1]->image_url }}" alt="Review photo 2 by {{ $reviewerName }}" loading="lazy">
                                            <span class="fb-gallery-thumb__badge">+{{ $imagesCount - 1 }}</span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Shopee-style Management / Seller Response Box --}}
                        @if ($feedback->replied)
                            <div class="fb-shopee-reply" aria-label="Park Management Response">
                                <div class="fb-shopee-reply__header">
                                    <svg class="fb-shopee-reply__badge-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="fb-shopee-reply__title">Park Management Response:</span>
                                </div>
                                <p class="fb-shopee-reply__text">{{ $feedback->replied }}</p>
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

                {{-- Shopee Management Response in Modal --}}
                <div class="fb-shopee-reply fb-detail-reply hidden" id="reviewDetailReplySection" aria-label="Park Management Response">
                    <div class="fb-shopee-reply__header">
                        <svg class="fb-shopee-reply__badge-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        <span class="fb-shopee-reply__title">Park Management Response:</span>
                    </div>
                    <p class="fb-shopee-reply__text" id="reviewDetailReplyText"></p>
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

    {{-- Feedback Moderation Warning Modal --}}
    <div class="fb-modal fb-modal--warning" id="feedbackWarningModal" aria-hidden="true" role="alertdialog" aria-labelledby="feedbackWarningTitle" aria-describedby="feedbackWarningMessage">
        <div class="fb-modal__backdrop" data-close-feedback-warning></div>
        <div class="fb-modal__panel fb-warning-panel" role="document">
            <button type="button" class="fb-modal__close" data-close-feedback-warning aria-label="Close warning">&times;</button>
            <div class="fb-warning-icon" aria-hidden="true">!</div>
            <h2 id="feedbackWarningTitle" class="fb-panel__title">Feedback not sent</h2>
            <p id="feedbackWarningMessage" class="fb-panel__subtitle">Please revise your feedback and try again.</p>
            <button type="button" class="fb-submit fb-warning-panel__button" data-close-feedback-warning>Review Feedback</button>
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
