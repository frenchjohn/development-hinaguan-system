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
        :root {
            --surface-alt: #f4f7f4;
        }
        [data-theme="dark"] {
            --surface-alt: #202522;
        }
        body.admin-portal {
            background-color: #ebf3ec !important;
        }
        [data-theme="dark"] body.admin-portal {
            background-color: #0f1110 !important;
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
            background-image: linear-gradient(rgba(255, 255, 255, 0.4), rgba(255, 255, 255, 0.3)), url('{{ asset('storage/design_images/staff-admin-background-image.jpeg') }}') !important;
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
            background-color: #0f1110 !important;
            background-image: linear-gradient(rgba(15, 17, 16, 0.94), rgba(15, 17, 16, 0.97)), url('{{ asset('storage/design_images/staff-admin-background-image.jpeg') }}') !important;
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
            <x-header
                title="Guest Feedback"
                subtitle="Review, show, or hide visitor reviews"
            />

            <main class="dash-content p-6 space-y-5">

                {{-- Top KPI Strip --}}
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-white/10 dark:bg-[#181b19]">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Reviews</span>
                        <p id="fbStatTotal" class="m-0 mt-1.5 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $totalFeedbacks }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-white/10 dark:bg-[#181b19]">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Shown</span>
                        <p id="fbStatShown" class="m-0 mt-1.5 text-2xl font-bold tabular-nums text-emerald-700 dark:text-emerald-400">{{ $shownFeedbacks }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-white/10 dark:bg-[#181b19]">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Hidden</span>
                        <p id="fbStatHidden" class="m-0 mt-1.5 text-2xl font-bold tabular-nums text-amber-700 dark:text-amber-400">{{ $hiddenFeedbacks }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-white/10 dark:bg-[#181b19]">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Average Rating</span>
                        <p id="fbStatAvg" class="m-0 mt-1.5 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $averageStars }}</p>
                    </div>
                    <div class="col-span-2 rounded-xl border border-slate-200 bg-white p-4 shadow-xs md:col-span-1 dark:border-white/10 dark:bg-[#181b19]">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Reviews Today</span>
                        <p id="fbStatToday" class="m-0 mt-1.5 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $todayFeedbacks }}</p>
                    </div>
                </div>

                {{-- Review Insights Summary --}}
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-[#181b19]">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3.5 dark:border-white/10">
                        <div>
                            <h3 class="m-0 text-sm font-bold text-slate-900 dark:text-white">Review Insights</h3>
                            <p class="m-0 mt-0.5 text-xs text-slate-500 dark:text-slate-400">Sentiment summary across visitor reviews &middot; <span id="fbAiAnalyzedAt">Analyzed {{ $aiInsights['analyzed_at'] }}</span></p>
                        </div>
                        <button type="button" id="fbAiRefreshBtn" class="inline-flex h-8 cursor-pointer items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50 dark:border-white/15 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                            <span>Refresh</span>
                        </button>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
                        <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-3.5 dark:border-white/10 dark:bg-white/[0.02]">
                            <div class="mb-2 flex items-center justify-between text-xs font-semibold text-slate-600 dark:text-slate-300">
                                <span>Sentiment Split</span>
                                <span id="fbAiPositivePctBadge" class="font-bold text-emerald-700 dark:text-emerald-400">{{ $aiInsights['positive_percent'] }}% Positive</span>
                            </div>
                            <div class="mb-3 flex h-2.5 w-full overflow-hidden rounded bg-slate-200 dark:bg-white/10">
                                <div id="fbAiPosBar" class="h-full bg-emerald-600 transition-all duration-500" style="width: {{ $aiInsights['positive_percent'] }}%;" title="Positive: {{ $aiInsights['positive_count'] }} ({{ $aiInsights['positive_percent'] }}%)"></div>
                                <div id="fbAiNeuBar" class="h-full bg-amber-400 transition-all duration-500" style="width: {{ $aiInsights['neutral_percent'] }}%;" title="Neutral: {{ $aiInsights['neutral_count'] }} ({{ $aiInsights['neutral_percent'] }}%)"></div>
                                <div id="fbAiNegBar" class="h-full bg-rose-500 transition-all duration-500" style="width: {{ $aiInsights['negative_percent'] }}%;" title="Negative: {{ $aiInsights['negative_count'] }} ({{ $aiInsights['negative_percent'] }}%)"></div>
                            </div>
                            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-xs bg-emerald-600"></span> Pos <strong id="fbAiPosCount" class="tabular-nums text-slate-900 dark:text-white">{{ $aiInsights['positive_count'] }}</strong></span>
                                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-xs bg-amber-400"></span> Neu <strong id="fbAiNeuCount" class="tabular-nums text-slate-900 dark:text-white">{{ $aiInsights['neutral_count'] }}</strong></span>
                                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-xs bg-rose-500"></span> Neg <strong id="fbAiNegCount" class="tabular-nums text-slate-900 dark:text-white">{{ $aiInsights['negative_count'] }}</strong></span>
                            </div>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-3.5 dark:border-white/10 dark:bg-white/[0.02]">
                            <p class="m-0 mb-2 text-[11px] font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-400">Top Praises</p>
                            <ul id="fbAiPraisesList" class="m-0 list-disc space-y-1 pl-4 text-xs leading-relaxed text-slate-700 dark:text-slate-200">
                                @foreach ($aiInsights['top_praises'] as $praise)
                                    <li>{{ $praise }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-3.5 dark:border-white/10 dark:bg-white/[0.02]">
                            <p class="m-0 mb-2 text-[11px] font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400">Reported Concerns</p>
                            <ul id="fbAiIssuesList" class="m-0 list-disc space-y-1 pl-4 text-xs leading-relaxed text-slate-700 dark:text-slate-200">
                                @foreach ($aiInsights['top_issues'] as $issue)
                                    <li>{{ $issue }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </section>

                {{-- Feedback Table --}}
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs dark:border-white/10 dark:bg-[#181b19]">
                    <div class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4 dark:border-white/10">
                        <div class="min-w-[200px] flex-1">
                            <label for="feedbackSearch" class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Search Guest</label>
                            <input id="feedbackSearch" type="search" placeholder="Search by guest name..." autocomplete="off"
                                class="h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/15 dark:border-white/15 dark:bg-white/5 dark:text-white">
                        </div>
                        <div class="min-w-[130px]">
                            <label for="feedbackSentimentFilter" class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Sentiment</label>
                            <select id="feedbackSentimentFilter" class="h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-emerald-700 dark:border-white/15 dark:bg-white/5 dark:text-white">
                                <option value="all">All</option>
                                <option value="positive">Positive</option>
                                <option value="neutral">Neutral</option>
                                <option value="negative">Negative</option>
                            </select>
                        </div>
                        <div class="min-w-[120px]">
                            <label for="feedbackStarFilter" class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Rating</label>
                            <select id="feedbackStarFilter" class="h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-emerald-700 dark:border-white/15 dark:bg-white/5 dark:text-white">
                                <option value="all">All stars</option>
                                @for ($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}">{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="min-w-[120px]">
                            <label for="feedbackVisibilityFilter" class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Visibility</label>
                            <select id="feedbackVisibilityFilter" class="h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-emerald-700 dark:border-white/15 dark:bg-white/5 dark:text-white">
                                <option value="all">All</option>
                                <option value="shown">Shown</option>
                                <option value="hidden">Hidden</option>
                            </select>
                        </div>
                        <div class="min-w-[160px]">
                            <label for="feedbackSortFilter" class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Sort By</label>
                            <select id="feedbackSortFilter" class="h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none focus:border-emerald-700 dark:border-white/15 dark:bg-white/5 dark:text-white">
                                <option value="newest">Newest first</option>
                                <option value="oldest">Oldest first</option>
                                <option value="stars_high">Highest rating</option>
                                <option value="stars_low">Lowest rating</option>
                            </select>
                        </div>
                    </div>

                    <div class="max-h-[32rem] overflow-y-auto">
                        <table class="w-full min-w-[720px] border-collapse text-left">
                            <thead class="sticky top-0 z-10 border-b border-slate-200 bg-slate-50 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-white/10 dark:bg-[#141714] dark:text-slate-400">
                                <tr>
                                    <th class="px-4 py-3">Guest</th>
                                    <th class="px-4 py-3">Rating</th>
                                    <th class="px-4 py-3">Sentiment</th>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody id="feedbackTableBody" class="divide-y divide-slate-100 dark:divide-white/10">
                                @forelse ($feedbacks as $feedback)
                                    @php
                                        $imagesCount = $feedback->images ? $feedback->images->count() : 0;
                                        $imagesArray = $feedback->images ? $feedback->images->map(fn($img) => [
                                            'id' => $img->id,
                                            'url' => $img->image_url,
                                        ])->values()->all() : [];

                                        $sentiment = $feedback->ai_sentiment;
                                        $sentimentBadgeClass = match($sentiment) {
                                            'positive' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
                                            'negative' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border-rose-200 dark:border-rose-800',
                                            default => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border-amber-200 dark:border-amber-800',
                                        };
                                    @endphp
                                    <tr
                                        class="feedback-row cursor-pointer transition-colors hover:bg-slate-50 dark:hover:bg-white/[0.03]"
                                        data-feedback-id="{{ $feedback->id }}"
                                        data-guest-name="{{ strtolower($feedback->full_name) }}"
                                        data-full-name="{{ $feedback->full_name }}"
                                        data-initials="{{ $feedback->initials }}"
                                        data-is-anonymous="{{ $feedback->is_anonymous ? '1' : '0' }}"
                                        data-description="{{ $feedback->description }}"
                                        data-stars="{{ $feedback->stars }}"
                                        data-sentiment="{{ $feedback->ai_sentiment }}"
                                        data-sentiment-label="{{ $feedback->ai_sentiment_label }}"
                                        data-sentiment-emoji="{{ $feedback->ai_sentiment_emoji }}"
                                        data-visibility="{{ $feedback->is_shown ? 'shown' : 'hidden' }}"
                                        data-created-timestamp="{{ $feedback->created_at->timestamp }}"
                                        data-created-date="{{ $feedback->created_at->toDateString() }}"
                                        data-created-formatted="{{ $feedback->created_at->format('M j, Y') }}"
                                        tabindex="0"
                                        role="button"
                                        aria-label="View feedback from {{ $feedback->full_name }}"
                                    >
                                        <td class="px-4 py-3">
                                            <script type="application/json" class="fb-admin-row-data">
                                            {!! json_encode([
                                                'id' => $feedback->id,
                                                'fullName' => $feedback->full_name,
                                                'initials' => $feedback->initials,
                                                'isAnonymous' => (bool) $feedback->is_anonymous,
                                                'stars' => (int) $feedback->stars,
                                                'description' => $feedback->description,
                                                'createdFormatted' => $feedback->created_at->format('M j, Y'),
                                                'visibility' => $feedback->is_shown ? 'shown' : 'hidden',
                                                'sentiment' => $feedback->ai_sentiment,
                                                'sentimentLabel' => $feedback->ai_sentiment_label,
                                                'sentimentEmoji' => $feedback->ai_sentiment_emoji,
                                                'points' => $feedback->ai_points,
                                                'images' => $imagesArray,
                                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) !!}
                                            </script>
                                            <div class="flex items-center gap-3">
                                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[#155e3c] text-xs font-bold text-white">{{ $feedback->initials }}</span>
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        <p class="m-0 text-sm font-semibold text-slate-900 dark:text-white">{{ $feedback->full_name }}</p>
                                                        @if ($imagesCount > 0)
                                                            <span class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[11px] font-medium text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                                                                {{ $imagesCount }} photo{{ $imagesCount > 1 ? 's' : '' }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    @if ($feedback->description)
                                                        <p class="m-0 mt-0.5 max-w-sm truncate text-xs text-slate-500 dark:text-slate-400" title="{{ $feedback->description }}">
                                                            {{ $feedback->description }}
                                                        </p>
                                                    @endif
                                                    @if ($feedback->is_anonymous)
                                                        <p class="m-0 text-[11px] text-slate-400">Anonymous submission</p>
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
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-semibold {{ $sentimentBadgeClass }}">
                                                {{ $feedback->ai_sentiment_label }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500 dark:text-slate-400">{{ $feedback->created_at->format('M j, Y') }}</td>
                                        <td class="px-4 py-3">
                                            <span class="fb-status-badge inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-semibold {{ $feedback->is_shown ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400' }}">
                                                {{ $feedback->is_shown ? 'Shown' : 'Hidden' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="feedbackEmptyRow">
                                        <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">No guest feedback yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 dark:border-white/10">
                        <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                            <span>Showing</span>
                            <strong id="fbRangeText" class="font-semibold text-slate-900 dark:text-white">0</strong>
                            <span id="fbOfText">of 0 reviews</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center gap-2">
                                <label for="feedbackPerPage" class="text-xs text-slate-500 dark:text-slate-400">Rows</label>
                                <select id="feedbackPerPage" class="h-8 rounded-lg border border-slate-300 bg-white px-2 text-xs text-slate-900 outline-none focus:border-emerald-700 dark:border-white/15 dark:bg-white/5 dark:text-white">
                                    <option value="10" selected>10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                </select>
                            </div>
                            <button type="button" id="fbPrevPage" aria-label="Previous page" class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg border border-slate-300 bg-white text-slate-700 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/15 dark:bg-white/5 dark:text-slate-200">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                            </button>
                            <span id="fbPageInfo" class="min-w-[4.5rem] text-center text-xs font-semibold text-slate-700 dark:text-slate-200">Page 1 of 1</span>
                            <button type="button" id="fbNextPage" aria-label="Next page" class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg border border-slate-300 bg-white text-slate-700 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/15 dark:bg-white/5 dark:text-slate-200">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            </button>
                        </div>
                    </div>
                    <p id="feedbackNoResults" class="hidden px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">No feedback matches your filters.</p>
                </section>
            </main>
        </div>
    </div>

    {{-- Feedback detail modal --}}
    <div class="fixed inset-0 z-[2000] hidden items-center justify-center p-4 [&.is-open]:flex" id="feedbackDetailModal" aria-hidden="true" role="dialog" aria-labelledby="feedbackDetailName">
        <div class="absolute inset-0 bg-black/50" data-feedback-detail-cancel></div>
        <div class="relative flex max-h-[85vh] w-full max-w-xl flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-white/10 dark:bg-[#181b19]">
            <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 dark:border-white/10">
                <div class="flex items-center gap-3">
                    <span id="fbDetailAvatar" class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-[#155e3c] text-xs font-bold text-white"></span>
                    <div>
                        <h3 id="fbDetailName" class="m-0 text-base font-bold text-slate-900 dark:text-white"></h3>
                        <p id="fbDetailMeta" class="m-0 mt-0.5 text-xs text-slate-500 dark:text-slate-400"></p>
                    </div>
                </div>
                <button type="button" class="cursor-pointer rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-white/10 dark:hover:text-slate-200" data-feedback-detail-cancel aria-label="Close">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-4">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <div id="fbDetailStars" class="flex items-center gap-0.5 text-[#c8a45d]" aria-label="Rating"></div>
                        <span id="fbDetailRatingLabel" class="text-sm font-bold text-slate-900 dark:text-white"></span>
                    </div>
                    <span id="fbDetailAiSentimentBadge" class="inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-semibold"></span>
                </div>

                {{-- Sentiment Breakdown --}}
                <div id="fbDetailAiSection" class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-3.5 dark:border-white/10 dark:bg-white/[0.03]">
                    <div class="mb-2 border-b border-slate-200 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:border-white/10 dark:text-slate-400">
                        Sentiment Breakdown
                    </div>
                    <div id="fbDetailAiPointsList" class="space-y-1.5"></div>
                </div>

                <p class="m-0 mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Full Feedback</p>
                <p id="fbDetailDescription" class="m-0 whitespace-pre-line rounded-lg border border-slate-200 bg-slate-50 p-3.5 text-sm leading-relaxed text-slate-800 dark:border-white/10 dark:bg-white/[0.03] dark:text-slate-200"></p>

                {{-- Attached Photos Gallery in Admin Modal --}}
                <div id="fbDetailImagesSection" class="mt-4 hidden border-t border-slate-100 pt-4 dark:border-white/10">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="m-0 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Attached Photos</p>
                        <span id="fbDetailImagesCount" class="text-xs font-semibold text-slate-600 dark:text-slate-300"></span>
                    </div>
                    <div id="fbDetailImagesGrid" class="grid grid-cols-3 gap-2.5 sm:grid-cols-4"></div>
                </div>
            </div>

            <div class="border-t border-slate-100 px-5 py-4 dark:border-white/10">
                <div id="fbDeleteConfirmBox" class="mb-3 hidden rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-500/30 dark:bg-red-950/40">
                    <p class="m-0 mb-2 text-xs font-semibold text-red-700 dark:text-red-300">Delete this review permanently? This cannot be undone.</p>
                    <div class="flex justify-end gap-2">
                        <button type="button" id="fbCancelDelete" class="cursor-pointer rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700">Keep it</button>
                        <button type="button" id="fbConfirmDelete" class="cursor-pointer rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white">Yes, delete</button>
                    </div>
                </div>
                <div class="flex justify-between gap-3">
                    <button
                        type="button"
                        id="fbDetailToggleBtn"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50 dark:border-white/15 dark:bg-white/5 dark:text-slate-200"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <span id="fbDetailToggleLabel">Show on website</span>
                    </button>
                    <button
                        type="button"
                        id="fbDetailDeleteBtn"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-red-700"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Admin Photo Inspection Lightbox --}}
    <div class="fixed inset-0 z-[2500] hidden items-center justify-center p-5 [&.is-open]:flex" id="adminPhotoLightbox" aria-hidden="true" role="dialog" aria-label="Photo Preview">
        <div class="absolute inset-0 bg-black/85" data-admin-lightbox-close></div>
        <div class="relative z-10 flex max-h-[90vh] max-w-4xl flex-col items-center justify-center">
            <button type="button" class="absolute -top-10 right-0 grid h-8 w-8 cursor-pointer place-items-center rounded-full bg-white/20 text-white transition-colors hover:bg-red-600" data-admin-lightbox-close aria-label="Close">&times;</button>
            <img id="adminLightboxImg" src="" alt="Full size preview" class="max-h-[80vh] max-w-full rounded-lg object-contain shadow-2xl">
        </div>
    </div>
</body>
</html>
