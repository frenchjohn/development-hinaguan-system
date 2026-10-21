<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verified Guest Feedback — Hinaguan Nature Park</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('storage/design_images/main_logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700|poppins:300,400,500,600,700" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/css/feedback.css',
    ])
    <style>
        .vr-wrapper {
            max-width: 44rem;
            margin: 2.5rem auto 4rem;
            padding: 0 1.25rem;
        }
        .vr-card {
            background: #ffffff;
            border: 1px solid var(--fb-line, #e6e1d2);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(11, 36, 24, 0.07);
            overflow: hidden;
        }
        .vr-card__header {
            background: linear-gradient(135deg, #123322 0%, #0b2418 100%);
            color: #f6f4ec;
            padding: 2.25rem 2rem 2rem;
            text-align: center;
            position: relative;
        }
        .vr-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(200, 164, 93, 0.2);
            border: 1px solid rgba(200, 164, 93, 0.45);
            color: #e6c888;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            margin-bottom: 0.9rem;
        }
        .vr-title {
            font-family: var(--fb-font-display, 'Playfair Display', Georgia, serif);
            font-size: 1.85rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 0.5rem;
            line-height: 1.25;
        }
        .vr-subtitle {
            font-size: 0.9rem;
            color: rgba(246, 244, 236, 0.82);
            margin: 0;
            line-height: 1.5;
        }
        .vr-meta-strip {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 0.75rem 1.25rem;
            background: #fdfbf7;
            border-bottom: 1px solid #ebe5d7;
            padding: 0.85rem 1.5rem;
            font-size: 0.82rem;
            color: #556254;
        }
        .vr-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .vr-meta-item strong {
            color: #123322;
        }
        .vr-card__body {
            padding: 2.25rem 2rem;
        }
        .vr-status-box {
            text-align: center;
            padding: 3rem 1.5rem;
        }
        .vr-status-icon {
            width: 4.5rem;
            height: 4.5rem;
            margin: 0 auto 1.25rem;
            border-radius: 50%;
            display: grid;
            place-items: center;
        }
        .vr-status-icon--success {
            background: #dcfce7;
            color: #15803d;
        }
        .vr-status-icon--warning {
            background: #fef3c7;
            color: #b45309;
        }
        .vr-status-icon--danger {
            background: #fee2e2;
            color: #b91c1c;
        }
        .vr-status-title {
            font-family: var(--fb-font-display, 'Playfair Display', Georgia, serif);
            font-size: 1.65rem;
            color: #123322;
            margin: 0 0 0.75rem;
        }
        .vr-status-text {
            color: #556254;
            font-size: 0.95rem;
            line-height: 1.6;
            max-width: 30rem;
            margin: 0 auto 1.75rem;
        }
        .vr-actions {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .vr-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .vr-btn--primary {
            background: #123322;
            color: #f6f4ec;
            border: 1px solid #123322;
        }
        .vr-btn--primary:hover {
            background: #1a4730;
            color: #ffffff;
        }
        .vr-btn--outline {
            background: transparent;
            color: #123322;
            border: 1px solid #cbd5e1;
        }
        .vr-btn--outline:hover {
            border-color: #123322;
            background: #f8fafc;
        }
        .vr-submitted-preview {
            background: #f8faf7;
            border: 1px solid #e2ebd8;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem auto;
            max-width: 32rem;
            text-align: left;
        }
        .vr-submitted-preview__stars {
            display: flex;
            gap: 0.25rem;
            color: #c8a45d;
            margin-bottom: 0.75rem;
        }
        .vr-submitted-preview__stars svg {
            width: 1.25rem;
            height: 1.25rem;
            fill: currentColor;
        }
        .vr-submitted-preview__text {
            color: #1f2937;
            font-size: 0.92rem;
            line-height: 1.55;
            margin: 0 0 1rem;
        }
        .vr-timer-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
            border-radius: 6px;
            padding: 0.4rem 0.8rem;
            font-size: 0.78rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .vr-notice-bar {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.82rem;
            color: #166534;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
    </style>
</head>
<body class="antialiased fb-page">

    <div class="fb-site-header" id="fbSiteHeader">
        <header class="fb-header">
            <div class="fb-header__inner">
                <a href="{{ route('feedback') }}" class="fb-nav__back">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                    <span>View All Reviews</span>
                </a>
                <a href="{{ route('home') }}" style="color:#e6c888; font-size:0.82rem; font-weight:600; text-decoration:none;">
                    Hinaguan Nature Park
                </a>
            </div>
        </header>
    </div>

    <main style="padding-top: 5rem;">
        <div class="vr-wrapper">

            @if ($status === 'already_submitted')
                {{-- State 1: Feedback was already submitted with this link --}}
                <div class="vr-card">
                    <div class="vr-card__header">
                        <span class="vr-badge">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                            Verified Review Received
                        </span>
                        <h1 class="vr-title">Feedback Already Submitted</h1>
                        <p class="vr-subtitle">Thank you for sharing your experience with Hinaguan Nature Park.</p>
                    </div>

                    <div class="vr-card__body vr-status-box">
                        <div class="vr-status-icon vr-status-icon--success">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-9 h-9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h2 class="vr-status-title">Review Recorded</h2>
                        <p class="vr-status-text">
                            You have already submitted your review for this visit
                            @if($tokenRecord && $tokenRecord->submitted_at)
                                on <strong>{{ $tokenRecord->submitted_at->format('F j, Y \a\t g:i A') }}</strong>
                            @endif.
                            To ensure authentic, reliable ratings, each visit invitation link can only be used once.
                        </p>

                        @if ($tokenRecord && $tokenRecord->feedback)
                            <div class="vr-submitted-preview">
                                <div class="vr-submitted-preview__stars">
                                    @for ($s = 1; $s <= 5; $s++)
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="{{ $s <= $tokenRecord->feedback->stars ? 'fill:#c8a45d;' : 'fill:#d9d2bd;' }}">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                        </svg>
                                    @endfor
                                    <span style="font-size:0.85rem; font-weight:700; color:#123322; margin-left:0.35rem;">
                                        {{ $tokenRecord->feedback->stars }}.0 / 5
                                    </span>
                                </div>
                                <p class="vr-submitted-preview__text">
                                    &ldquo;{{ $tokenRecord->feedback->description }}&rdquo;
                                </p>
                                <div style="font-size:0.8rem; color:#6b7280; display:flex; justify-content:space-between; align-items:center;">
                                    <span>Reviewer: <strong>{{ $tokenRecord->feedback->full_name }}</strong></span>
                                    <span>{{ $tokenRecord->feedback->created_at->format('M j, Y') }}</span>
                                </div>
                            </div>
                        @endif

                        <div class="vr-actions">
                            <a href="{{ route('feedback') }}" class="vr-btn vr-btn--primary">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                                Browse All Reviews
                            </a>
                            <a href="{{ route('home') }}" class="vr-btn vr-btn--outline">
                                Return to Home
                            </a>
                        </div>
                    </div>
                </div>

            @elseif ($status === 'expired')
                {{-- State 2: Link is older than 24 hours (1 day) --}}
                <div class="vr-card">
                    <div class="vr-card__header">
                        <span class="vr-badge" style="border-color:rgba(239,68,68,0.4); color:#fca5a5;">
                            Invitation Expired
                        </span>
                        <h1 class="vr-title">Link Has Expired</h1>
                        <p class="vr-subtitle">Feedback invitations are valid for 24 hours after checkout.</p>
                    </div>

                    <div class="vr-card__body vr-status-box">
                        <div class="vr-status-icon vr-status-icon--warning">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-9 h-9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h2 class="vr-status-title">24-Hour Window Passed</h2>
                        <p class="vr-status-text">
                            This feedback link expired
                            @if($tokenRecord && $tokenRecord->expires_at)
                                on <strong>{{ $tokenRecord->expires_at->format('F j, Y \a\t g:i A') }}</strong>
                            @endif.
                            To keep our reviews timely and reflective of current visitor experiences, checkout feedback links are only active for 24 hours.
                        </p>

                        <div class="vr-actions">
                            <a href="{{ route('feedback') }}" class="vr-btn vr-btn--primary">
                                Browse Guest Reviews
                            </a>
                            <a href="{{ route('home') }}" class="vr-btn vr-btn--outline">
                                Return to Home
                            </a>
                        </div>
                    </div>
                </div>

            @elseif ($status === 'invalid')
                {{-- State 3: Invalid token --}}
                <div class="vr-card">
                    <div class="vr-card__header">
                        <h1 class="vr-title">Invalid Link</h1>
                        <p class="vr-subtitle">We couldn't verify this feedback invitation.</p>
                    </div>

                    <div class="vr-card__body vr-status-box">
                        <div class="vr-status-icon vr-status-icon--danger">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-9 h-9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                        <h2 class="vr-status-title">Invitation Not Found</h2>
                        <p class="vr-status-text">
                            Please check that you opened the full link from your checkout receipt email. If you believe this is an error, feel free to contact park support.
                        </p>

                        <div class="vr-actions">
                            <a href="{{ route('feedback') }}" class="vr-btn vr-btn--primary">
                                Browse Guest Reviews
                            </a>
                            <a href="{{ route('home') }}" class="vr-btn vr-btn--outline">
                                Return to Home
                            </a>
                        </div>
                    </div>
                </div>

            @else
                {{-- State 4: Valid Token, ready for feedback submission --}}
                <div class="vr-card">
                    <div class="vr-card__header">
                        <span class="vr-badge">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                            Verified Guest Review
                        </span>
                        <h1 class="vr-title">How Was Your Experience?</h1>
                        <p class="vr-subtitle">Your genuine review helps us enhance the park and inspires future nature seekers.</p>
                    </div>

                    @php
                        $guestFullName = '';
                        if ($customer) {
                            $guestFullName = trim("{$customer->first_name} {$customer->last_name}");
                        } elseif ($reservation) {
                            $guestFullName = trim($reservation->booker_name ?? '');
                        }
                    @endphp

                    <div class="vr-meta-strip">
                        <span class="vr-meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-emerald-700"><path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z"/></svg>
                            Guest: <strong>{{ $guestFullName ?: 'Verified Visitor' }}</strong>
                        </span>
                        @if ($reservation)
                            <span class="vr-meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-emerald-700"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd"/></svg>
                                Reservation: <strong>#{{ $reservation->id }}</strong>
                            </span>
                        @endif
                        <span class="vr-meta-item" style="color:#b45309;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd"/></svg>
                            Link valid for 24h &bull; Single-use
                        </span>
                    </div>

                    <div class="vr-card__body">
                        <div class="vr-notice-bar">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 flex-shrink-0"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/></svg>
                            <span>This review is linked to your checkout receipt. Once submitted, this invitation link cannot be reused.</span>
                        </div>

                        <form id="verifiedFeedbackForm" class="fb-form" novalidate enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="token" value="{{ $tokenRecord->token }}">

                            <div class="fb-field">
                                <label for="feedbackFullName" class="fb-label">Your Name</label>
                                <input type="text" id="feedbackFullName" name="full_name" maxlength="255" value="{{ $guestFullName }}" placeholder="Enter your full name" class="fb-input" autocomplete="name">
                            </div>

                            <label class="fb-checkbox" style="margin-bottom: 1.5rem;">
                                <input type="checkbox" id="feedbackAnonymous" name="is_anonymous" value="1">
                                <span class="fb-checkbox__box" aria-hidden="true"></span>
                                <span>Display as <strong>Anonymous Guest</strong> on public reviews</span>
                            </label>

                            <div class="fb-field">
                                <label class="fb-label">Your Overall Rating <span style="color:#dc2626;">*</span></label>
                                <div class="fb-star-input" id="feedbackStarInput" role="radiogroup" aria-label="Star rating">
                                    @for ($s = 1; $s <= 5; $s++)
                                        <button type="button" class="fb-star-input__btn" data-star="{{ $s }}" aria-label="{{ $s }} star{{ $s > 1 ? 's' : '' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                        </button>
                                    @endfor
                                </div>
                                <input type="hidden" name="stars" id="feedbackStars" value="">
                                <p class="fb-field-hint" id="feedbackStarHint">Tap a star to rate your stay (1 to 5 stars).</p>
                            </div>

                            <div class="fb-field">
                                <div class="fb-field__header">
                                    <label for="feedbackDescription" class="fb-label">Your Review <span style="color:#dc2626;">*</span></label>
                                    <span id="charCount" style="font-size:0.75rem; color:#6b7280;">0 / 2000 characters</span>
                                </div>
                                <textarea id="feedbackDescription" name="description" rows="5" maxlength="2000" required placeholder="Tell us about your experience at Hinaguan Nature Park, what you enjoyed most, or any suggestions for us..." class="fb-textarea"></textarea>
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
                                        <span class="fb-uploader__text"><strong>Choose photos</strong> or drag &amp; drop</span>
                                        <span class="fb-uploader__hint">PNG, JPG, WEBP up to 5MB</span>
                                    </label>
                                    <div class="fb-uploader__previews" id="feedbackPreviewsContainer"></div>
                                </div>
                            </div>

                            <button type="submit" class="fb-submit" id="feedbackSubmitBtn">
                                Submit Verified Review
                            </button>
                        </form>
                    </div>
                </div>
            @endif

        </div>
    </main>

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

    @if ($status === 'valid')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('verifiedFeedbackForm');
            const submitBtn = document.getElementById('feedbackSubmitBtn');
            const starsHidden = document.getElementById('feedbackStars');
            const starButtons = Array.from(document.querySelectorAll('.fb-star-input__btn'));
            const starHint = document.getElementById('feedbackStarHint');
            const descInput = document.getElementById('feedbackDescription');
            const charCount = document.getElementById('charCount');
            const fullNameInput = document.getElementById('feedbackFullName');
            const anonymousCheckbox = document.getElementById('feedbackAnonymous');
            const warningModal = document.getElementById('feedbackWarningModal');
            const warningMessage = document.getElementById('feedbackWarningMessage');
            const imagesInput = document.getElementById('feedbackImagesInput');
            const dropzone = document.getElementById('feedbackDropzone');
            const previewsContainer = document.getElementById('feedbackPreviewsContainer');
            const imageCountText = document.getElementById('feedbackImageCountText');

            let selectedStars = 0;
            let selectedFiles = [];
            const MAX_FILES = 5;
            const MAX_FILE_SIZE = 5 * 1024 * 1024;

            // Character counter
            descInput?.addEventListener('input', () => {
                const len = descInput.value.length;
                if (charCount) charCount.textContent = `${len} / 2000 characters`;
            });

            // Star Rating System
            const renderStars = (rating) => {
                starButtons.forEach((btn) => {
                    const s = parseInt(btn.dataset.star, 10);
                    btn.classList.toggle('is-active', s <= rating);
                });
            };

            starButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    selectedStars = parseInt(btn.dataset.star, 10);
                    starsHidden.value = String(selectedStars);
                    renderStars(selectedStars);
                    if (starHint) {
                        starHint.classList.remove('is-error');
                        const labels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Exceptional!'];
                        starHint.textContent = `${selectedStars} star${selectedStars > 1 ? 's' : ''} (${labels[selectedStars]})`;
                    }
                });

                btn.addEventListener('mouseenter', () => {
                    const hover = parseInt(btn.dataset.star, 10);
                    starButtons.forEach((b) => {
                        const s = parseInt(b.dataset.star, 10);
                        b.classList.toggle('is-active', s <= hover);
                    });
                });

                btn.addEventListener('mouseleave', () => {
                    renderStars(selectedStars);
                });
            });

            // File Uploads
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
                        selectedFiles.splice(index, 1);
                        updateFilePreviews();
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

            imagesInput?.addEventListener('change', (e) => {
                if (e.target.files) addFiles(Array.from(e.target.files));
                imagesInput.value = '';
            });

            if (dropzone) {
                ['dragenter', 'dragover'].forEach((ev) => {
                    dropzone.addEventListener(ev, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.add('is-dragover');
                    });
                });
                ['dragleave', 'drop'].forEach((ev) => {
                    dropzone.addEventListener(ev, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.remove('is-dragover');
                    });
                });
                dropzone.addEventListener('drop', (e) => {
                    if (e.dataTransfer?.files) addFiles(Array.from(e.dataTransfer.files));
                });
            }

            // Warning modal
            const showWarning = (msg) => {
                if (!warningModal || !warningMessage) {
                    alert(msg);
                    return;
                }
                warningMessage.textContent = msg;
                warningModal.classList.add('is-open');
                warningModal.setAttribute('aria-hidden', 'false');
            };

            const closeWarning = () => {
                if (!warningModal) return;
                warningModal.classList.remove('is-open');
                warningModal.setAttribute('aria-hidden', 'true');
            };

            document.querySelectorAll('[data-close-feedback-warning]').forEach((el) => {
                el.addEventListener('click', closeWarning);
            });

            // Form submission
            form?.addEventListener('submit', async (e) => {
                e.preventDefault();

                const isAnonymous = anonymousCheckbox?.checked;
                const fullName = fullNameInput?.value.trim() || '';
                const description = descInput?.value.trim() || '';
                const stars = parseInt(starsHidden?.value || '0', 10);

                if (!isAnonymous && !fullName) {
                    showWarning('Please enter your full name or select the anonymous feedback option.');
                    fullNameInput?.focus();
                    return;
                }

                if (!stars || stars < 1 || stars > 5) {
                    if (starHint) {
                        starHint.textContent = 'Please choose a star rating (1 to 5) before submitting.';
                        starHint.classList.add('is-error');
                    }
                    showWarning('Please select a rating between 1 and 5 stars.');
                    return;
                }

                if (!description) {
                    showWarning('Please write your feedback description before submitting.');
                    descInput?.focus();
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.textContent = 'Submitting Your Review...';

                const formData = new FormData(form);
                formData.set('full_name', fullName);
                formData.set('is_anonymous', isAnonymous ? '1' : '0');
                formData.set('description', description);
                formData.set('stars', String(stars));

                // Re-append selected files
                formData.delete('images[]');
                selectedFiles.forEach((file) => {
                    formData.append('images[]', file);
                });

                try {
                    const response = await fetch("{{ route('feedback.review.store', ['token' => $tokenRecord->token]) }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                            Accept: 'application/json',
                        },
                        body: formData,
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'Unable to submit review.');
                    }

                    // Reload page to show already-submitted state
                    window.location.reload();
                } catch (err) {
                    showWarning(err.message || 'An error occurred while submitting. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Verified Review';
                }
            });
        });
    </script>
    @endif

</body>
</html>
