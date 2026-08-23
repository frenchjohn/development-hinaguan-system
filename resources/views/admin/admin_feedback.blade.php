<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Guest Feedback — Hinaguan Nature Park</title>
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link rel="icon" type="image/jpeg" href="{{ asset('storage/design_images/main_logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700|poppins:300,400,500,600,700" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/css/homepage.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/staff_sidemenu.css',
        'resources/css/staff_css/staff_shared.css',
        'resources/css/admin_css/admin_shared.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/admin_js/admin_feedback.js',
    ])
    <style>
        body.admin-portal {
            background-color: #ebf3ec !important;
        }
        [data-theme="dark"] body.admin-portal {
            background-color: #06120a !important;
        }
        body.admin-portal .dash-layout,
        body.admin-portal .dash-main,
        body.admin-portal .dash-content {
            background: transparent !important;
            background-color: transparent !important;
            background-image: none !important;
        }
        body.admin-portal .dash-main {
            position: relative !important;
            min-height: 100vh;
            z-index: 0;
        }
        body.admin-portal .dash-main::before {
            content: '' !important;
            display: block !important;
            position: fixed !important;
            top: 0 !important;
            left: var(--dash-sidebar-w, 10rem) !important;
            right: 0 !important;
            bottom: 0 !important;
            width: auto !important;
            height: 100vh !important;
            z-index: -1 !important;
            pointer-events: none !important;
            background-color: #ebf3ec !important;
            background-image: url('{{ asset('storage/design_images/staff-admin-background-image.jpeg') }}') !important;
            background-size: 100% 100% !important;
            background-position: center center !important;
            background-repeat: no-repeat !important;
            filter: none !important;
            -webkit-filter: none !important;
            opacity: 1 !important;
            transition: left 0.25s ease !important;
        }
        .dash-layout.sidebar-collapsed .dash-main::before {
            left: 0 !important;
        }
        @media (max-width: 992px) {
            body.admin-portal .dash-main::before {
                left: 0 !important;
            }
        }
        [data-theme="dark"] body.admin-portal .dash-main::before {
            background-color: #06120a !important;
            background-image: linear-gradient(rgba(6, 18, 10, 0.88), rgba(6, 18, 10, 0.92)), url('{{ asset('storage/design_images/staff-admin-background-image.jpeg') }}') !important;
            filter: none !important;
            -webkit-filter: none !important;
            opacity: 1 !important;
        }
        body.admin-portal .dash-content {
            position: relative !important;
            z-index: 1 !important;
        }
        body.admin-portal [class*="backdrop-blur"] {
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
    </style>
</head>
<body class="antialiased admin-portal"
    data-today="{{ now()->toDateString() }}">
    <div class="dash-layout">
        <x-admin_sidemenu active="feedback" userName="{{ session('auth_user.name') ?? 'Admin User' }}" userRole="Admin" />

        <div class="dash-main">
            <main class="dash-content p-6">
                <x-header
                    title="Guest Feedback"
                    subtitle="Review, show, or hide visitor reviews"
                />

                <section class="mb-7 flex flex-wrap items-start justify-between gap-6">
                    <div>
                        <p class="mb-3 inline-flex rounded-full bg-[rgba(200,164,93,0.12)] px-[0.95rem] py-[0.45rem] text-[0.85rem] font-bold uppercase tracking-[0.14em] text-[var(--hp-gold-dark)]">Manage Reviews</p>
                        <h2 class="m-0 text-[1.85rem] font-bold text-[var(--hp-text)]">All guest feedback</h2>
                        <p class="m-0 mt-[0.65rem] max-w-[38rem] leading-[1.75] text-[var(--hp-text-muted)]">Click any row to read the full review, then show, hide, or delete it from there.</p>
                    </div>
                </section>

                <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)]">
                        <span class="grid h-[2.6rem] w-[2.6rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-[var(--green-soft)] text-[var(--green)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-[1.25rem] w-[1.25rem]"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                        </span>
                        <div>
                            <p id="fbStatTotal" class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]">{{ $totalFeedbacks }}</p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Total Reviews</p>
                        </div>
                    </article>
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)]">
                        <span class="grid h-[2.6rem] w-[2.6rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-[var(--green-soft)] text-[var(--green)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-[1.25rem] w-[1.25rem]"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </span>
                        <div>
                            <p id="fbStatShown" class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]">{{ $shownFeedbacks }}</p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Shown</p>
                        </div>
                    </article>
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)]">
                        <span class="grid h-[2.6rem] w-[2.6rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-[var(--warn-soft)] text-[var(--warn)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-[1.25rem] w-[1.25rem]"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </span>
                        <div>
                            <p id="fbStatHidden" class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]">{{ $hiddenFeedbacks }}</p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Hidden</p>
                        </div>
                    </article>
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)]">
                        <span class="grid h-[2.6rem] w-[2.6rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-[rgba(200,164,93,0.15)] text-[#c8a45d]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-[1.25rem] w-[1.25rem]"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        </span>
                        <div>
                            <p id="fbStatAvg" class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]">{{ $averageStars }}</p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Average Rating</p>
                        </div>
                    </article>
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)]">
                        <span class="grid h-[2.6rem] w-[2.6rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-[rgba(59,130,246,0.12)] text-[#3b82f6]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-[1.25rem] w-[1.25rem]"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4.5 2.25M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div>
                            <p id="fbStatToday" class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]">{{ $todayFeedbacks }}</p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Reviews Today</p>
                        </div>
                    </article>
                </div>

                <section class="rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-sm)] overflow-hidden">
                    <div class="flex flex-wrap items-end gap-4 border-b border-[var(--border)] p-4">
                        <div class="min-w-[180px] flex-1">
                            <label for="feedbackSearch" class="mb-1 block text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Search by name</label>
                            <input id="feedbackSearch" type="search" placeholder="Search guest name..." autocomplete="off" class="w-full rounded-lg border border-[var(--border)] bg-[var(--surface-alt)] px-3 py-2 text-sm text-[var(--ink)] outline-none focus:border-[var(--green)]">
                        </div>
                        <div class="min-w-[120px]">
                            <label for="feedbackStarFilter" class="mb-1 block text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Star rating</label>
                            <select id="feedbackStarFilter" class="w-full rounded-lg border border-[var(--border)] bg-[var(--surface-alt)] px-3 py-2 text-sm text-[var(--ink)] outline-none focus:border-[var(--green)]">
                                <option value="all">All stars</option>
                                @for ($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}">{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="min-w-[120px]">
                            <label for="feedbackVisibilityFilter" class="mb-1 block text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Visibility</label>
                            <select id="feedbackVisibilityFilter" class="w-full rounded-lg border border-[var(--border)] bg-[var(--surface-alt)] px-3 py-2 text-sm text-[var(--ink)] outline-none focus:border-[var(--green)]">
                                <option value="all">All</option>
                                <option value="shown">Shown</option>
                                <option value="hidden">Hidden</option>
                            </select>
                        </div>
                        <div class="min-w-[160px]">
                            <label for="feedbackSortFilter" class="mb-1 block text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Sort by</label>
                            <select id="feedbackSortFilter" class="w-full rounded-lg border border-[var(--border)] bg-[var(--surface-alt)] px-3 py-2 text-sm text-[var(--ink)] outline-none focus:border-[var(--green)]">
                                <option value="newest">Newest to oldest</option>
                                <option value="oldest">Oldest to newest</option>
                                <option value="stars_high">Stars: highest to lowest</option>
                                <option value="stars_low">Stars: lowest to highest</option>
                            </select>
                        </div>
                    </div>

                    <div class="max-h-[32rem] overflow-y-auto">
                        <table class="w-full min-w-[720px] border-collapse text-left">
                            <thead class="sticky top-0 z-10 bg-[var(--surface-alt)] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">
                                <tr>
                                    <th class="px-4 py-3">Guest</th>
                                    <th class="px-4 py-3">Rating</th>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody id="feedbackTableBody">
                                @forelse ($feedbacks as $feedback)
                                    <tr
                                        class="feedback-row cursor-pointer border-t border-[var(--border)] transition-colors hover:bg-[var(--surface-alt)]/70"
                                        data-feedback-id="{{ $feedback->id }}"
                                        data-guest-name="{{ strtolower($feedback->full_name) }}"
                                        data-stars="{{ $feedback->stars }}"
                                        data-visibility="{{ $feedback->is_shown ? 'shown' : 'hidden' }}"
                                        data-created-timestamp="{{ $feedback->created_at->timestamp }}"
                                        data-created-date="{{ $feedback->created_at->toDateString() }}"
                                        data-created-formatted="{{ $feedback->created_at->format('M j, Y') }}"
                                        data-full-name="{{ $feedback->full_name }}"
                                        data-initials="{{ $feedback->initials }}"
                                        data-is-anonymous="{{ $feedback->is_anonymous ? '1' : '0' }}"
                                        data-description="{{ $feedback->description }}"
                                        tabindex="0"
                                        role="button"
                                        aria-label="View feedback from {{ $feedback->full_name }}"
                                    >
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-gradient-to-br from-[#6E9F54] to-[#244A2D] text-xs font-bold text-white">{{ $feedback->initials }}</span>
                                                <div>
                                                    <p class="m-0 text-sm font-semibold text-[var(--ink)]">{{ $feedback->full_name }}</p>
                                                    @if ($feedback->is_anonymous)
                                                        <p class="m-0 text-[0.72rem] text-[var(--ink-muted)]">Anonymous submission</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-0.5 text-[#c8a45d]" aria-label="{{ $feedback->stars }} out of 5 stars">
                                                @for ($s = 1; $s <= 5; $s++)
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4 {{ $s <= $feedback->stars ? 'fill-current' : 'fill-none stroke-current' }}" stroke-width="1.5"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                                @endfor
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-[var(--ink-muted)]">{{ $feedback->created_at->format('M j, Y') }}</td>
                                        <td class="px-4 py-3">
                                            <span class="fb-status-badge inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $feedback->is_shown ? 'bg-[var(--green-soft)] text-[var(--green)]' : 'bg-[var(--warn-soft)] text-[var(--warn)]' }}">
                                                {{ $feedback->is_shown ? 'Shown' : 'Hidden' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="feedbackEmptyRow">
                                        <td colspan="4" class="px-4 py-10 text-center text-sm text-[var(--ink-muted)]">No guest feedback yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[var(--border)] px-4 py-3">
                        <div class="flex items-center gap-2 text-sm text-[var(--ink-muted)]">
                            <span>Showing</span>
                            <strong id="fbRangeText" class="font-semibold text-[var(--ink)]">0</strong>
                            <span id="fbOfText">of 0 reviews</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <label for="feedbackPerPage" class="text-sm text-[var(--ink-muted)]">Rows per page</label>
                                <select id="feedbackPerPage" class="rounded-lg border border-[var(--border)] bg-[var(--surface-alt)] px-2 py-1.5 text-sm text-[var(--ink)] outline-none focus:border-[var(--green)]">
                                    <option value="10" selected>10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                </select>
                            </div>
                            <button type="button" id="fbPrevPage" aria-label="Previous page" class="grid h-8 w-8 place-items-center rounded-lg border border-[var(--border)] bg-[var(--surface-alt)] text-[var(--ink)] transition-colors hover:border-[var(--green)] disabled:cursor-not-allowed disabled:opacity-40">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                            </button>
                            <span id="fbPageInfo" class="min-w-[5rem] text-center text-sm font-semibold text-[var(--ink)]">Page 1 of 1</span>
                            <button type="button" id="fbNextPage" aria-label="Next page" class="grid h-8 w-8 place-items-center rounded-lg border border-[var(--border)] bg-[var(--surface-alt)] text-[var(--ink)] transition-colors hover:border-[var(--green)] disabled:cursor-not-allowed disabled:opacity-40">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            </button>
                        </div>
                    </div>
                    <p id="feedbackNoResults" class="hidden px-4 py-8 text-center text-sm text-[var(--ink-muted)]">No feedback matches your filters.</p>
                </section>
            </main>
        </div>
    </div>

    {{-- Feedback detail modal --}}
    <div class="fixed inset-0 z-[2000] hidden items-center justify-center p-5 [&.is-open]:flex" id="feedbackDetailModal" aria-hidden="true" role="dialog" aria-labelledby="feedbackDetailTitle">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" data-feedback-detail-cancel></div>
        <div class="relative flex max-h-[85vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl border border-[var(--border)] bg-[var(--surface)] shadow-xl">
            <div class="flex items-start justify-between gap-4 border-b border-[var(--border)] px-6 py-5">
                <div class="flex items-center gap-3">
                    <span id="fbDetailAvatar" class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-gradient-to-br from-[#6E9F54] to-[#244A2D] text-sm font-bold text-white"></span>
                    <div>
                        <h3 id="fbDetailName" class="m-0 text-base font-bold text-[var(--ink)]"></h3>
                        <p id="fbDetailMeta" class="m-0 mt-0.5 text-xs text-[var(--ink-muted)]"></p>
                    </div>
                </div>
                <button type="button" class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[var(--surface-alt)] text-lg leading-none text-[var(--ink-muted)] transition-colors hover:bg-[var(--danger-soft)] hover:text-[var(--danger)]" data-feedback-detail-cancel aria-label="Close">&times;</button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5">
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <div id="fbDetailStars" class="flex items-center gap-0.5 text-[#c8a45d]" aria-label="Rating"></div>
                    <span id="fbDetailRatingLabel" class="text-sm font-bold text-[var(--ink)]"></span>
                </div>
                <p class="m-0 mb-2 text-[0.72rem] font-semibold uppercase tracking-[0.06em] text-[var(--ink-muted)]">Feedback</p>
                <p id="fbDetailDescription" class="m-0 whitespace-pre-line rounded-xl bg-[var(--surface-alt)] p-4 text-sm leading-relaxed text-[var(--ink)]"></p>
            </div>

            <div class="border-t border-[var(--border)] px-6 py-4">
                <div id="fbDeleteConfirmBox" class="mb-3 hidden rounded-xl border border-[var(--danger)]/30 bg-[var(--danger-soft)] p-3">
                    <p class="m-0 mb-2 text-sm font-semibold text-[var(--danger)]">Delete this review permanently? This cannot be undone.</p>
                    <div class="flex justify-end gap-2">
                        <button type="button" id="fbCancelDelete" class="rounded-lg bg-[var(--surface-alt)] px-3 py-1.5 text-xs font-semibold text-[var(--ink)]">Keep it</button>
                        <button type="button" id="fbConfirmDelete" class="rounded-lg bg-[var(--danger)] px-3 py-1.5 text-xs font-semibold text-white">Yes, delete</button>
                    </div>
                </div>
                <div class="flex justify-between gap-3">
                    <button
                        type="button"
                        id="fbDetailToggleBtn"
                        class="inline-flex items-center gap-2 rounded-lg border border-[var(--border)] bg-[var(--surface-alt)] px-4 py-2 text-sm font-semibold text-[var(--ink)] transition-colors hover:border-[var(--green)]"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <span id="fbDetailToggleLabel">Show on website</span>
                    </button>
                    <button
                        type="button"
                        id="fbDetailDeleteBtn"
                        class="inline-flex items-center gap-2 rounded-lg bg-[var(--danger)] px-4 py-2 text-sm font-semibold text-white transition-opacity hover:opacity-90"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
