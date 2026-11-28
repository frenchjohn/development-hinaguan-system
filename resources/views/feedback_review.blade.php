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
        .vr-star-btn {
            color: #d6d0c0;
            transition: color 0.15s ease, transform 0.15s ease;
        }
        .vr-star-btn:hover,
        .vr-star-btn.is-active {
            color: #c8a45d;
        }
        .vr-star-btn:hover {
            transform: scale(1.08);
        }
    </style>
</head>
<body class="antialiased bg-[#f7f5ef] text-[#1c261f]">

    <header class="sticky top-0 z-40 border-b border-white/10 bg-[#123322] text-[#f6f4ec]">
        <div class="mx-auto flex h-14 max-w-5xl items-center justify-between px-5">
            <a href="{{ route('feedback') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-[#f6f4ec]/90 no-underline hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                <span>View All Reviews</span>
            </a>
            <a href="{{ route('home') }}" class="text-xs font-semibold tracking-wide text-[#e6c888] no-underline hover:text-white">
                Hinaguan Nature Park
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-4xl px-5 py-10 sm:py-12">
        @if ($status === 'already_submitted')
            {{-- State 1: Feedback was already submitted with this link --}}
            <div class="mx-auto max-w-xl rounded-xl border border-[#e2ddd0] bg-white p-6 shadow-xs sm:p-8">
                <div class="border-b border-[#efeae0] pb-4">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Verified Review Recorded</span>
                    <h1 class="mt-1 mb-0 font-['Playfair_Display',Georgia,serif] text-2xl font-bold text-[#123322]">Thank you for your feedback</h1>
                    <p class="mt-1.5 mb-0 text-sm text-[#556254]">
                        You already submitted your review for this visit
                        @if($tokenRecord && $tokenRecord->submitted_at)
                            on <strong class="text-[#123322]">{{ $tokenRecord->submitted_at->format('F j, Y \a\t g:i A') }}</strong>
                        @endif.
                        Each invitation link can only be used once.
                    </p>
                </div>

                @if ($tokenRecord && $tokenRecord->feedback)
                    <div class="mt-5 rounded-lg border border-[#e6e1d2] bg-[#faf8f3] p-4">
                        <div class="flex items-center gap-1">
                            @for ($s = 1; $s <= 5; $s++)
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" style="{{ $s <= $tokenRecord->feedback->stars ? 'fill:#c8a45d;' : 'fill:#d9d2bd;' }}">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            @endfor
                            <span class="ml-1.5 text-xs font-bold text-[#123322]">{{ $tokenRecord->feedback->stars }}.0 / 5</span>
                        </div>
                        <p class="mt-2.5 mb-3 text-sm leading-relaxed text-[#1f2937]">&ldquo;{{ $tokenRecord->feedback->description }}&rdquo;</p>
                        <div class="flex items-center justify-between border-t border-[#ebe5d7] pt-2.5 text-xs text-[#6b7280]">
                            <span>Reviewer: <strong class="text-[#123322]">{{ $tokenRecord->feedback->full_name }}</strong></span>
                            <span>{{ $tokenRecord->feedback->created_at->format('M j, Y') }}</span>
                        </div>
                    </div>
                @endif

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <a href="{{ route('feedback') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#123322] px-4 py-2.5 text-xs font-semibold text-white no-underline transition-colors hover:bg-[#1a4730]">
                        Browse All Reviews
                    </a>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-[#123322] no-underline transition-colors hover:bg-slate-50">
                        Return to Home
                    </a>
                </div>
            </div>

        @elseif ($status === 'expired')
            {{-- State 2: Link is older than 24 hours --}}
            <div class="mx-auto max-w-xl rounded-xl border border-[#e2ddd0] bg-white p-6 shadow-xs sm:p-8">
                <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Invitation Expired</span>
                <h1 class="mt-1 mb-0 font-['Playfair_Display',Georgia,serif] text-2xl font-bold text-[#123322]">This feedback link has expired</h1>
                <p class="mt-2 mb-0 text-sm leading-relaxed text-[#556254]">
                    This feedback link expired
                    @if($tokenRecord && $tokenRecord->expires_at)
                        on <strong class="text-[#123322]">{{ $tokenRecord->expires_at->format('F j, Y \a\t g:i A') }}</strong>
                    @endif.
                    Checkout feedback invitations remain active for 24 hours.
                </p>
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <a href="{{ route('feedback') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#123322] px-4 py-2.5 text-xs font-semibold text-white no-underline transition-colors hover:bg-[#1a4730]">
                        Browse Guest Reviews
                    </a>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-[#123322] no-underline transition-colors hover:bg-slate-50">
                        Return to Home
                    </a>
                </div>
            </div>

        @elseif ($status === 'invalid')
            {{-- State 3: Invalid token --}}
            <div class="mx-auto max-w-xl rounded-xl border border-[#e2ddd0] bg-white p-6 shadow-xs sm:p-8">
                <span class="text-[11px] font-bold uppercase tracking-wider text-red-700">Invalid Link</span>
                <h1 class="mt-1 mb-0 font-['Playfair_Display',Georgia,serif] text-2xl font-bold text-[#123322]">Invitation not found</h1>
                <p class="mt-2 mb-0 text-sm leading-relaxed text-[#556254]">
                    We could not verify this feedback invitation. Please make sure you opened the complete link from your checkout receipt email.
                </p>
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <a href="{{ route('feedback') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#123322] px-4 py-2.5 text-xs font-semibold text-white no-underline transition-colors hover:bg-[#1a4730]">
                        Browse Guest Reviews
                    </a>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-[#123322] no-underline transition-colors hover:bg-slate-50">
                        Return to Home
                    </a>
                </div>
            </div>

        @else
            {{-- State 4: Valid Token, ready for feedback submission --}}
            @php
                $guestFullName = '';
                if ($customer) {
                    $guestFullName = trim("{$customer->first_name} {$customer->last_name}");
                } elseif ($reservation) {
                    $guestFullName = trim($reservation->booker_name ?? '');
                }
            @endphp

            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
                {{-- Left Column: Visit Summary --}}
                <aside class="rounded-xl border border-[#e2ddd0] bg-white p-5 shadow-xs lg:col-span-4">
                    <div class="border-b border-[#efeae0] pb-4">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800">Verified Visit</span>
                        <h1 class="mt-1 mb-0 font-['Playfair_Display',Georgia,serif] text-xl font-bold text-[#123322]">How was your stay?</h1>
                        <p class="mt-1.5 mb-0 text-xs leading-relaxed text-[#556254]">
                            Your feedback helps us improve Hinaguan Nature Park for future guests.
                        </p>
                    </div>

                    <dl class="mt-4 space-y-2.5 text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-[#6b7280]">Guest</dt>
                            <dd class="m-0 font-semibold text-[#123322]">{{ $guestFullName ?: 'Verified Visitor' }}</dd>
                        </div>
                        @if ($reservation)
                            <div class="flex items-center justify-between gap-2">
                                <dt class="text-[#6b7280]">Reservation</dt>
                                <dd class="m-0 font-semibold text-[#123322]">#{{ $reservation->formatted_id }}</dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between gap-2">
                            <dt class="text-[#6b7280]">Link Validity</dt>
                            <dd class="m-0 font-semibold text-amber-800">24h &middot; Single-use</dd>
                        </div>
                    </dl>

                    <p class="mt-4 mb-0 rounded-lg border border-[#ebe5d7] bg-[#faf8f3] px-3 py-2.5 text-[11px] leading-relaxed text-[#556254]">
                        Linked to your checkout receipt. Once submitted, this invitation link cannot be reused.
                    </p>
                </aside>

                {{-- Right Column: Review Form --}}
                <section class="rounded-xl border border-[#e2ddd0] bg-white p-6 shadow-xs lg:col-span-8 sm:p-7">
                    <form id="verifiedFeedbackForm" class="space-y-5" novalidate enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="token" value="{{ $tokenRecord->token }}">

                        <div class="space-y-2">
                            <label for="feedbackFullName" class="block text-xs font-semibold uppercase tracking-wide text-[#374151]">Your Name</label>
                            <input type="text" id="feedbackFullName" name="full_name" maxlength="255" value="{{ $guestFullName }}" placeholder="Enter your full name" autocomplete="name"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-[#123322] placeholder:text-slate-400 focus:border-[#123322] focus:outline-none focus:ring-2 focus:ring-[#123322]/15">

                            <label class="inline-flex cursor-pointer items-center gap-2 pt-0.5 text-xs text-[#4b5563]">
                                <input type="checkbox" id="feedbackAnonymous" name="is_anonymous" value="1" class="h-4 w-4 rounded border-slate-300 text-[#123322] focus:ring-[#123322]">
                                <span>Display as <strong class="text-[#123322]">Anonymous Guest</strong> on public reviews</span>
                            </label>
                        </div>

                        <div class="border-t border-[#efeae0] pt-5">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-[#374151]">Overall Rating <span class="text-red-600">*</span></label>
                            <div class="mt-2 flex items-center gap-1.5" id="feedbackStarInput" role="radiogroup" aria-label="Star rating">
                                @for ($s = 1; $s <= 5; $s++)
                                    <button type="button" class="fb-star-input__btn vr-star-btn cursor-pointer border-0 bg-transparent p-1" data-star="{{ $s }}" aria-label="{{ $s }} star{{ $s > 1 ? 's' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-7 w-7 fill-current"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    </button>
                                @endfor
                            </div>
                            <input type="hidden" name="stars" id="feedbackStars" value="">
                            <p class="mt-1 mb-0 text-xs text-[#6b7280] [&.is-error]:font-medium [&.is-error]:text-red-600" id="feedbackStarHint">Select a rating from 1 to 5 stars.</p>
                        </div>

                        <div class="border-t border-[#efeae0] pt-5">
                            <div class="mb-1.5 flex items-center justify-between">
                                <label for="feedbackDescription" class="block text-xs font-semibold uppercase tracking-wide text-[#374151]">Your Review <span class="text-red-600">*</span></label>
                                <span id="charCount" class="text-[11px] tabular-nums text-[#6b7280]">0 / 2000 characters</span>
                            </div>
                            <textarea id="feedbackDescription" name="description" rows="4" maxlength="2000" required
                                placeholder="Share what you enjoyed most or any suggestions for our team..."
                                class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-[#123322] placeholder:text-slate-400 focus:border-[#123322] focus:outline-none focus:ring-2 focus:ring-[#123322]/15"></textarea>
                        </div>

                        <div class="border-t border-[#efeae0] pt-5">
                            <div class="mb-1.5 flex items-center justify-between">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-[#374151]">Photos <span class="font-normal normal-case text-[#6b7280]">(Optional, up to 5)</span></label>
                                <span class="text-[11px] tabular-nums text-[#6b7280]" id="feedbackImageCountText">0 / 5 photos</span>
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

                        <div class="flex justify-end border-t border-[#efeae0] pt-5">
                            <button type="submit" id="feedbackSubmitBtn"
                                class="inline-flex w-full cursor-pointer items-center justify-center rounded-lg bg-[#123322] px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#1a4730] disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto">
                                Submit Verified Review
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        @endif
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
