<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SMS Announcements & Broadcasts — Hinaguan Nature Park</title>
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <link rel="icon" type="image/jpeg" href="{{ asset('storage/design_images/main_logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700|playfair-display:400,500,600,700|poppins:300,400,500,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite([
        'resources/css/app.css',
        'resources/css/homepage.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/staff_sidemenu.css',
        'resources/css/chatbot.css',
        'resources/css/staff_css/staff_shared.css',
        'resources/css/admin_css/admin_shared.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/admin_js/admin_announcement.js',
        'resources/js/admin_chatbot.js',
    ])
    <style>
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
        .live-indicator-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #10b981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-green 1.8s infinite;
        }
        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        .audience-card {
            cursor: pointer;
            transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
        }
        .audience-card:hover {
            transform: translateY(-2px);
        }
        .audience-card.is-active-audience {
            border-color: #10b981 !important;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.4), var(--shadow-sm) !important;
            background-color: rgba(16, 185, 129, 0.08) !important;
        }
        .reservation-row.is-selected {
            background-color: rgba(110, 159, 84, 0.12) !important;
            border-left: 3px solid #6E9F54;
        }
        [data-theme="dark"] .reservation-row.is-selected {
            background-color: rgba(110, 159, 84, 0.18) !important;
            border-left: 3px solid #4ade80;
        }
        /* Mobile phone preview shell */
        .phone-mockup {
            background: linear-gradient(145deg, #1e293b, #0f172a);
            border-radius: 26px;
            padding: 10px;
            box-shadow: 0 12px 28px -4px rgba(0, 0, 0, 0.35);
        }
        .phone-screen {
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
        }
        [data-theme="dark"] .phone-screen {
            background: #0f172a;
        }
    </style>
</head>
<body class="antialiased admin-portal">
    <div class="dash-layout">
        <x-admin_sidemenu active="announcements" userName="{{ session('auth_user.name') ?? 'Admin User' }}" userRole="Admin" />

        <div class="dash-main">
            <x-header
                title="Guest SMS Announcements"
                subtitle="Broadcast targeted SMS notifications and manage park-wide announcement logs"
            />

            <main class="dash-content p-6 space-y-6">
                {{-- Banner feedback container --}}
                <div id="alertFeedbackContainer" class="hidden"></div>

                {{-- TOP SECTION: METRICS & PRIMARY BROADCAST CTA --}}
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-[var(--surface)] p-5 rounded-[var(--radius-lg)] border border-[var(--border)] shadow-[var(--shadow-sm)]">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">SMS Communications Hub</span>
                        <h2 class="m-0 text-xl font-display font-bold text-[var(--ink)]">Guest Announcement Center</h2>
                        <p class="m-0 mt-1 text-xs text-[var(--ink-muted)]">
                            Instantly notify in-park active guests, send checkout reminders, or broadcast weather and park advisories.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <button
                            type="button"
                            id="openWizardModalBtn"
                            class="flex items-center gap-2.5 px-5 py-3 text-xs font-bold rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-md hover:from-emerald-700 hover:to-teal-800 hover:scale-[1.02] active:scale-[0.98] transition-all cursor-pointer"
                        >
                            <i class="bi bi-send-plus-fill text-sm"></i>
                            <span>Send SMS Announcement</span>
                        </button>
                    </div>
                </div>

                {{-- KPI METRICS CARDS --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {{-- Active In-Park Reservations --}}
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)]">
                        <span class="relative grid h-[2.8rem] w-[2.8rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-[var(--green-soft)] text-[var(--green)]">
                            <i class="bi bi-person-check-fill text-xl"></i>
                            <span class="absolute top-1 right-1 live-indicator-dot"></span>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline gap-2">
                                <p class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]">{{ $activeCount }}</p>
                                <span class="text-[0.72rem] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">In-Park</span>
                            </div>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Active Reservations</p>
                        </div>
                    </article>

                    {{-- Total Active Guests Inside --}}
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)]">
                        <span class="grid h-[2.8rem] w-[2.8rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                            <i class="bi bi-people-fill text-xl"></i>
                        </span>
                        <div>
                            <p class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]">{{ $activeGuestsOnSite }}</p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">In-House Guests (Total)</p>
                        </div>
                    </article>

                    {{-- Active Companions --}}
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)]">
                        <span class="grid h-[2.8rem] w-[2.8rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">
                            <i class="bi bi-person-plus-fill text-xl"></i>
                        </span>
                        <div>
                            <p class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]">{{ $activeCompanionsOnSite }}</p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Active Companions</p>
                        </div>
                    </article>

                    {{-- Total SMS Sent --}}
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)]">
                        <span class="grid h-[2.8rem] w-[2.8rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">
                            <i class="bi bi-chat-left-dots-fill text-xl"></i>
                        </span>
                        <div>
                            <p class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]" id="metricSmsSentCount">{{ $totalSmsSentCount }}</p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Total SMS Delivered</p>
                        </div>
                    </article>
                </div>

                {{-- MAIN PAGE BODY: RECENT SMS BROADCAST HISTORY TABLE --}}
                <section class="rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-sm)]">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[var(--border)] pb-4 mb-4">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300">
                                <i class="bi bi-clock-history text-sm"></i>
                            </span>
                            <div>
                                <h3 class="m-0 text-sm font-bold text-[var(--ink)]">Recent SMS Broadcast History</h3>
                                <p class="m-0 text-[11px] text-[var(--ink-muted)]">Log of sent SMS notifications with recipient metrics and status</p>
                            </div>
                        </div>

                        {{-- History Search & Action --}}
                        <div class="flex items-center gap-2">
                            <div class="relative w-56">
                                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-[var(--ink-muted)]"></i>
                                <input
                                    type="text"
                                    id="historySearchInput"
                                    placeholder="Search past announcements..."
                                    class="h-8 w-full rounded-xl border border-[var(--border)] bg-gray-50 dark:bg-neutral-900/40 pl-8 pr-3 text-xs text-[var(--ink)] placeholder-[var(--ink-muted)] outline-none focus:border-emerald-500"
                                />
                            </div>
                            <button
                                type="button"
                                class="open-wizard-trigger px-3.5 py-1.5 text-xs font-bold rounded-xl border border-emerald-600/30 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-600 hover:text-white transition-all cursor-pointer flex items-center gap-1.5"
                            >
                                <i class="bi bi-plus-lg"></i>
                                <span>New SMS</span>
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-[var(--border)]">
                        <table class="w-full border-collapse text-left text-xs" id="historyTable">
                            <thead>
                                <tr class="border-b border-[var(--border)] bg-gray-50 dark:bg-neutral-900/60 font-semibold text-[var(--ink-muted)]">
                                    <th class="py-3 px-3">Date & Time</th>
                                    <th class="py-3 px-3">Subject / Title</th>
                                    <th class="py-3 px-4 min-w-[320px]">SMS Message Content</th>
                                    <th class="py-3 px-3 text-center">Recipients</th>
                                    <th class="py-3 px-3 text-center">Status</th>
                                    <th class="py-3 px-3 text-right">Sent By</th>
                                    <th class="py-3 px-3 w-16 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--border)]" id="announcementsHistoryBody">
                                @forelse($recentAnnouncements as $item)
                                    <tr class="history-row hover:bg-black/5 dark:hover:bg-white/5 transition-colors" data-announcement-id="{{ $item->id }}">
                                        <td class="py-3 px-3 text-[var(--ink-muted)] whitespace-nowrap">
                                            {{ $item->created_at->format('M d, Y h:i A') }}
                                        </td>
                                        <td class="py-3 px-3 font-semibold text-[var(--ink)] whitespace-nowrap">
                                            {{ $item->title ?: 'SMS Broadcast' }}
                                        </td>
                                        <td class="py-3 px-4 text-[var(--ink)]">
                                            <p class="m-0 leading-relaxed font-mono text-[11px] max-w-xl truncate" title="{{ $item->message }}">
                                                {{ $item->message }}
                                            </p>
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-950/50 px-2 py-0.5 text-xs font-bold text-emerald-700 dark:text-emerald-300">
                                                <i class="bi bi-phone"></i> {{ $item->recipient_count }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            @if($item->delivery_status === 'sent')
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300 px-2 py-0.5 text-[10px] font-bold uppercase">Sent</span>
                                            @elseif($item->delivery_status === 'simulated')
                                                <span class="inline-flex items-center rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300 px-2 py-0.5 text-[10px] font-bold uppercase" title="Simulated (Missing PhilSMS token in env)">Simulated</span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 px-2 py-0.5 text-[10px] font-bold uppercase">{{ $item->delivery_status }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-3 text-right text-[var(--ink-muted)]">
                                            {{ $item->created_by ?? 'Admin' }}
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            <button type="button" class="delete-history-btn text-rose-500 hover:text-rose-700 p-1 rounded hover:bg-rose-50 dark:hover:bg-rose-950/40 cursor-pointer" data-id="{{ $item->id }}" title="Delete log">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="emptyHistoryRow">
                                        <td colspan="7" class="py-12 text-center text-xs text-[var(--ink-muted)]">
                                            <div class="flex flex-col items-center justify-center gap-2">
                                                <i class="bi bi-chat-left-dots text-3xl opacity-40"></i>
                                                <p class="m-0 font-medium text-[var(--ink)]">No previous SMS broadcasts found.</p>
                                                <button type="button" class="open-wizard-trigger mt-1 text-xs text-emerald-600 font-bold hover:underline cursor-pointer">
                                                    Click here to send your first SMS announcement
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- 3-STEP WIZARD MODAL: SEND SMS ANNOUNCEMENT --}}
    {{-- ========================================================================= --}}
    <div id="broadcastWizardModal" class="hidden fixed inset-0 z-[2000] items-center justify-center p-3 sm:p-5 bg-black/60 backdrop-blur-sm transition-opacity">
        <div class="relative w-full max-w-4xl rounded-2xl bg-white dark:bg-[#111e16] border border-gray-200 dark:border-[#22392b] shadow-2xl overflow-hidden flex flex-col max-h-[92vh]">
            {{-- Modal Top Navigation / Stepper --}}
            <div class="px-6 pt-5 pb-4 border-b border-gray-100 dark:border-white/10 bg-gradient-to-r from-emerald-900/10 via-transparent to-transparent shrink-0">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
                            <i class="bi bi-megaphone-fill text-sm"></i>
                        </span>
                        <div>
                            <h3 class="m-0 text-base font-bold text-[var(--ink)]">SMS Announcement Broadcast</h3>
                            <p class="m-0 text-xs text-[var(--ink-muted)]" id="stepDescriptionText">Step 1: Pick audience group & fine-tune reservations</p>
                        </div>
                    </div>
                    <button type="button" class="close-wizard-btn flex h-8 w-8 items-center justify-center rounded-xl text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-black/5 dark:hover:bg-white/5 cursor-pointer">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>

                {{-- Step Progress Indicator (Display-only: advance using Next button) --}}
                <div class="grid grid-cols-3 gap-2 text-center text-xs select-none">
                    {{-- Step 1 Tab --}}
                    <div class="wizard-step-tab pointer-events-none flex items-center justify-center gap-2 py-2 px-3 rounded-xl border border-emerald-500/40 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-800 dark:text-emerald-200 font-bold transition-all" data-step="1">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-600 text-white text-[11px]">1</span>
                        <span>1. Select Recipients</span>
                    </div>

                    {{-- Step 2 Tab --}}
                    <div class="wizard-step-tab pointer-events-none flex items-center justify-center gap-2 py-2 px-3 rounded-xl border border-[var(--border)] bg-gray-50 dark:bg-neutral-900/40 text-[var(--ink-muted)] font-medium transition-all" data-step="2">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-gray-300 dark:bg-neutral-700 text-[var(--ink)] text-[11px]">2</span>
                        <span>2. Compose SMS</span>
                    </div>

                    {{-- Step 3 Tab --}}
                    <div class="wizard-step-tab pointer-events-none flex items-center justify-center gap-2 py-2 px-3 rounded-xl border border-[var(--border)] bg-gray-50 dark:bg-neutral-900/40 text-[var(--ink-muted)] font-medium transition-all" data-step="3">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-gray-300 dark:bg-neutral-700 text-[var(--ink)] text-[11px]">3</span>
                        <span>3. Review & Send</span>
                    </div>
                </div>
            </div>

            {{-- Modal Body Pages Container --}}
            <div class="p-6 overflow-y-auto flex-1">
                {{-- ------------------------------------------------------------- --}}
                {{-- STEP 1: TARGET AUDIENCE & RECIPIENTS PREVIEW TABLE            --}}
                {{-- ------------------------------------------------------------- --}}
                <div id="wizardStep1" class="wizard-step-page space-y-4">
                    {{-- Audience Preset Cards --}}
                    <div>
                        <label class="block text-xs font-bold text-[var(--ink)] mb-2">
                            Choose Target Group Preset:
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5" id="audienceCardsGrid">
                            {{-- Active In-Park --}}
                            <div class="audience-card is-active-audience rounded-xl border border-[var(--border)] bg-[var(--surface)] p-3 shadow-2xs flex flex-col justify-between" data-audience="active">
                                <div class="flex items-center justify-between">
                                    <span class="relative h-6 w-6 grid place-items-center rounded-lg bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 text-xs">
                                        <i class="bi bi-person-check-fill"></i>
                                        <span class="absolute -top-0.5 -right-0.5 live-indicator-dot"></span>
                                    </span>
                                    <span class="audience-badge rounded-full bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 px-1.5 py-0.2 text-[9px] font-bold">
                                        Active
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <div class="font-display font-bold text-lg leading-none text-[var(--ink)]">{{ $activeCount }}</div>
                                    <div class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400 mt-0.5">In-Park Only</div>
                                </div>
                            </div>

                            {{-- Confirmed --}}
                            <div class="audience-card rounded-xl border border-[var(--border)] bg-[var(--surface)] p-3 shadow-2xs flex flex-col justify-between" data-audience="confirmed">
                                <div class="flex items-center justify-between">
                                    <span class="h-6 w-6 grid place-items-center rounded-lg bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 text-xs">
                                        <i class="bi bi-calendar-check"></i>
                                    </span>
                                    <span class="audience-badge hidden rounded-full bg-blue-500/20 text-blue-700 dark:text-blue-300 px-1.5 py-0.2 text-[9px] font-bold">
                                        Target
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <div class="font-display font-bold text-lg leading-none text-[var(--ink)]">{{ $confirmedCount }}</div>
                                    <div class="text-[11px] font-bold text-blue-700 dark:text-blue-400 mt-0.5">Confirmed</div>
                                </div>
                            </div>

                            {{-- Pending --}}
                            <div class="audience-card rounded-xl border border-[var(--border)] bg-[var(--surface)] p-3 shadow-2xs flex flex-col justify-between" data-audience="pending">
                                <div class="flex items-center justify-between">
                                    <span class="h-6 w-6 grid place-items-center rounded-lg bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 text-xs">
                                        <i class="bi bi-hourglass-split"></i>
                                    </span>
                                    <span class="audience-badge hidden rounded-full bg-amber-500/20 text-amber-700 dark:text-amber-300 px-1.5 py-0.2 text-[9px] font-bold">
                                        Target
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <div class="font-display font-bold text-lg leading-none text-[var(--ink)]">{{ $pendingCount }}</div>
                                    <div class="text-[11px] font-bold text-amber-700 dark:text-amber-400 mt-0.5">Pending</div>
                                </div>
                            </div>

                            {{-- Checked Out --}}
                            <div class="audience-card rounded-xl border border-[var(--border)] bg-[var(--surface)] p-3 shadow-2xs flex flex-col justify-between" data-audience="checked_out">
                                <div class="flex items-center justify-between">
                                    <span class="h-6 w-6 grid place-items-center rounded-lg bg-gray-100 text-gray-800 dark:bg-neutral-800 dark:text-gray-300 text-xs">
                                        <i class="bi bi-box-arrow-right"></i>
                                    </span>
                                    <span class="audience-badge hidden rounded-full bg-gray-500/20 text-gray-700 dark:text-gray-300 px-1.5 py-0.2 text-[9px] font-bold">
                                        Target
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <div class="font-display font-bold text-lg leading-none text-[var(--ink)]">{{ $checkedOutCount }}</div>
                                    <div class="text-[11px] font-bold text-gray-700 dark:text-gray-300 mt-0.5">Checked Out</div>
                                </div>
                            </div>

                            {{-- All Reservations --}}
                            <div class="audience-card rounded-xl border border-[var(--border)] bg-[var(--surface)] p-3 shadow-2xs flex flex-col justify-between" data-audience="all">
                                <div class="flex items-center justify-between">
                                    <span class="h-6 w-6 grid place-items-center rounded-lg bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 text-xs">
                                        <i class="bi bi-broadcast-pin"></i>
                                    </span>
                                    <span class="audience-badge hidden rounded-full bg-purple-500/20 text-purple-700 dark:text-purple-300 px-1.5 py-0.2 text-[9px] font-bold">
                                        Target
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <div class="font-display font-bold text-lg leading-none text-[var(--ink)]">{{ $totalCount }}</div>
                                    <div class="text-[11px] font-bold text-purple-700 dark:text-purple-400 mt-0.5">All Reservations</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Target Selection Summary Card --}}
                    <div class="rounded-2xl border border-[var(--border)] bg-gradient-to-br from-white via-emerald-50/20 to-teal-50/30 dark:from-neutral-900/60 dark:via-emerald-950/20 dark:to-neutral-900/40 p-5 shadow-sm">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Current Recipient Pool</span>
                                <div class="flex items-baseline gap-2 mt-1">
                                    <h4 class="m-0 text-2xl font-display font-bold text-[var(--ink)]">
                                        <span id="selectedRatioDisplay">{{ $activeCount }} / {{ $activeCount }}</span>
                                    </h4>
                                    <span class="text-xs font-semibold text-[var(--ink-muted)]">Reservations Selected</span>
                                </div>
                                <p class="m-0 mt-1 text-xs text-[var(--ink-muted)]">
                                    Target: <span class="font-bold text-[var(--ink)]" id="selectedTargetGroupLabel">Active In-Park Guests</span>
                                </p>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <button
                                    type="button"
                                    id="openCustomizerModalBtn"
                                    class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl border border-emerald-500/40 bg-white dark:bg-neutral-800 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 shadow-xs hover:scale-[1.02] active:scale-[0.98] transition-all cursor-pointer"
                                >
                                    <i class="bi bi-sliders text-sm"></i>
                                    <span>Customize / Select Specific Reservations</span>
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 pt-3.5 border-t border-[var(--border)] flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-[var(--ink-muted)]">
                            <span class="flex items-center gap-1.5">
                                <i class="bi bi-info-circle text-emerald-600"></i>
                                <span>All reservations in the chosen group are included by default. Click customize to select or unselect individuals.</span>
                            </span>
                            <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400" id="selectionStatusBadge">
                                Group Preset Active
                            </span>
                        </div>
                    </div>
                </div>

                {{-- ------------------------------------------------------------- --}}
                {{-- STEP 2: COMPOSE SMS MESSAGE & PHONE MOCKUP PREVIEW            --}}
                {{-- ------------------------------------------------------------- --}}
                <div id="wizardStep2" class="wizard-step-page hidden space-y-4">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        {{-- Left 8 cols: Form inputs --}}
                        <div class="lg:col-span-8 space-y-4">
                            {{-- Quick Presets --}}
                            <div>
                                <label class="block text-xs font-bold text-[var(--ink)] mb-1.5">
                                    <i class="bi bi-lightning-charge-fill text-amber-500"></i> Click a Quick Preset to Auto-Fill:
                                </label>
                                <div class="flex flex-wrap gap-1.5">
                                    <button type="button" class="template-chip px-3 py-1 text-xs font-semibold rounded-lg border border-[var(--border)] bg-gray-50 dark:bg-neutral-800 text-[var(--ink)] hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 cursor-pointer" data-template="welcome">
                                        Rules & Welcome
                                    </button>
                                    <button type="button" class="template-chip px-3 py-1 text-xs font-semibold rounded-lg border border-[var(--border)] bg-gray-50 dark:bg-neutral-800 text-[var(--ink)] hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 cursor-pointer" data-template="weather">
                                        Weather Advisory
                                    </button>
                                    <button type="button" class="template-chip px-3 py-1 text-xs font-semibold rounded-lg border border-[var(--border)] bg-gray-50 dark:bg-neutral-800 text-[var(--ink)] hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 cursor-pointer" data-template="pool">
                                        Pool Schedule
                                    </button>
                                    <button type="button" class="template-chip px-3 py-1 text-xs font-semibold rounded-lg border border-[var(--border)] bg-gray-50 dark:bg-neutral-800 text-[var(--ink)] hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 cursor-pointer" data-template="checkout">
                                        Checkout Reminder
                                    </button>
                                    <button type="button" class="template-chip px-3 py-1 text-xs font-semibold rounded-lg border border-[var(--border)] bg-gray-50 dark:bg-neutral-800 text-[var(--ink)] hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 cursor-pointer" data-template="activity">
                                        Park Activity
                                    </button>
                                </div>
                            </div>

                            {{-- Title --}}
                            <div>
                                <label for="smsAnnouncementTitle" class="block text-xs font-semibold text-[var(--ink)] mb-1">
                                    Announcement Title (Internal Reference Note)
                                </label>
                                <input
                                    type="text"
                                    id="smsAnnouncementTitle"
                                    placeholder="e.g. Weather Advisory / Pool Maintenance Schedule"
                                    class="h-9 w-full rounded-xl border border-[var(--border)] bg-white dark:bg-neutral-900/60 px-3 text-xs text-[var(--ink)] outline-none focus:border-emerald-500"
                                    maxlength="100"
                                >
                            </div>

                            {{-- Message Textarea --}}
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label for="smsMessageTextarea" class="text-xs font-bold text-[var(--ink)]">
                                        SMS Message Content <span class="text-rose-500">*</span>
                                    </label>
                                    <div id="smsCharCounter" class="text-[11px] font-mono font-medium text-[var(--ink-muted)]">
                                        <span id="charCount">0</span> / 160 chars (1 SMS credit)
                                    </div>
                                </div>
                                <textarea
                                    id="smsMessageTextarea"
                                    rows="5"
                                    placeholder="Type the message here that all selected guests will receive on their mobile phones..."
                                    class="w-full rounded-xl border border-[var(--border)] bg-white dark:bg-neutral-900/60 p-3 font-mono text-xs text-[var(--ink)] outline-none focus:border-emerald-500 resize-y"
                                    maxlength="1000"
                                ></textarea>
                                <p class="mt-1 text-[11px] text-[var(--ink-muted)]">
                                    Messages will be transmitted directly via PhilSMS gateway. Standard SMS is 160 characters per SMS credit.
                                </p>
                            </div>
                        </div>

                        {{-- Right 4 cols: Phone mockup preview --}}
                        <div class="lg:col-span-4 flex flex-col items-center">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[var(--ink-muted)] mb-2 flex items-center gap-1">
                                <i class="bi bi-phone"></i> Guest Screen Preview
                            </span>
                            <div class="phone-mockup w-full max-w-[260px]">
                                <div class="phone-screen p-3 flex flex-col justify-between min-h-[220px]">
                                    <div class="flex items-center justify-between text-[9px] font-semibold text-gray-500 dark:text-gray-400 pb-2 border-b border-gray-100 dark:border-gray-800">
                                        <span>Hinaguan Nature Park</span>
                                        <span class="text-emerald-600 dark:text-emerald-400">SMS Alert</span>
                                    </div>

                                    <div class="my-auto py-3">
                                        <div class="rounded-2xl rounded-tl-sm bg-gray-100 dark:bg-neutral-800 p-3 text-xs leading-relaxed text-gray-900 dark:text-gray-100 shadow-sm font-sans break-words" id="liveSmsPreviewBubble">
                                            Hinaguan Nature Park: Welcome! Please be reminded of our park guidelines and quiet hours from 10 PM. Enjoy your stay!
                                        </div>
                                        <div class="text-right mt-1 text-[9px] text-gray-400" id="liveSmsPreviewTime">
                                            Just now
                                        </div>
                                    </div>

                                    <div class="pt-2 border-t border-gray-100 dark:border-gray-800 text-[9px] text-gray-400 text-center">
                                        Automated Guest Notification
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ------------------------------------------------------------- --}}
                {{-- STEP 3: FINAL REVIEW & DISPATCH CONFIRMATION                  --}}
                {{-- ------------------------------------------------------------- --}}
                <div id="wizardStep3" class="wizard-step-page hidden space-y-4">
                    <div class="rounded-2xl border border-emerald-500/30 bg-emerald-50/70 dark:bg-emerald-950/40 p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
                                <i class="bi bi-check2-circle text-xl"></i>
                            </span>
                            <div>
                                <h4 class="m-0 text-sm font-bold text-emerald-950 dark:text-emerald-100">Ready to Broadcast SMS Announcement</h4>
                                <p class="m-0 text-xs text-emerald-800/80 dark:text-emerald-300/80">Please review your target recipients and message body before dispatching.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3.5 rounded-xl bg-white/80 dark:bg-neutral-900/80 border border-emerald-200 dark:border-emerald-900 mb-4">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--ink-muted)]">Target Audience</span>
                                <div class="font-bold text-xs text-[var(--ink)] mt-0.5" id="reviewAudienceName">Active In-Park Guests</div>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--ink-muted)]">Total Reservations</span>
                                <div class="font-bold text-xs text-[var(--ink)] mt-0.5" id="reviewReservationsCount">0 Selected</div>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[var(--ink-muted)]">Valid Mobile Recipients</span>
                                <div class="font-bold text-xs text-emerald-700 dark:text-emerald-300 mt-0.5" id="reviewValidPhoneCount">0 Numbers</div>
                            </div>
                        </div>

                        {{-- Final message preview --}}
                        <div>
                            <span class="text-xs font-bold text-[var(--ink)] block mb-1.5">SMS Message Body to be dispatched:</span>
                            <div class="rounded-xl border border-[var(--border)] bg-white dark:bg-neutral-900 p-4 font-mono text-xs text-[var(--ink)] leading-relaxed shadow-sm break-words" id="reviewMessageText">
                                —
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal Footer with Wizard Controls --}}
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 dark:border-white/10 bg-gray-50/50 dark:bg-black/20 shrink-0">
                <button type="button" class="close-wizard-btn px-4 py-2 text-xs font-semibold rounded-xl border border-[var(--border)] bg-white dark:bg-neutral-800 text-[var(--ink)] hover:bg-gray-100 cursor-pointer">
                    Cancel
                </button>

                <div class="flex items-center gap-2.5">
                    {{-- Back Button --}}
                    <button
                        type="button"
                        id="wizardBackBtn"
                        class="hidden px-4 py-2 text-xs font-semibold rounded-xl border border-[var(--border)] bg-white dark:bg-neutral-800 text-[var(--ink)] hover:bg-gray-100 cursor-pointer"
                    >
                        <i class="bi bi-arrow-left mr-1"></i> Back
                    </button>

                    {{-- Next / Send Button --}}
                    <button
                        type="button"
                        id="wizardNextBtn"
                        class="flex items-center gap-2 px-5 py-2 text-xs font-bold rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-md hover:from-emerald-700 hover:to-teal-800 cursor-pointer"
                    >
                        <span>Next: Compose Message</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- RECIPIENTS CUSTOMIZER MODAL (Open via Customize button in Step 1)         --}}
    {{-- ========================================================================= --}}
    <div id="recipientsCustomizerModal" class="hidden fixed inset-0 z-[2100] items-center justify-center p-3 sm:p-5 bg-black/60 backdrop-blur-sm transition-opacity">
        <div class="relative w-full max-w-4xl rounded-2xl bg-white dark:bg-[#111e16] border border-gray-200 dark:border-[#22392b] shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/10 bg-gray-50/50 dark:bg-black/20">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                        <i class="bi bi-list-check text-base"></i>
                    </span>
                    <div>
                        <h3 class="m-0 text-sm font-bold text-[var(--ink)]">Customize Recipients Roster</h3>
                        <p class="m-0 text-xs text-[var(--ink-muted)]">Check or uncheck specific reservations to fine-tune your broadcast list</p>
                    </div>
                </div>
                <button type="button" class="close-customizer-modal flex h-8 w-8 items-center justify-center rounded-xl text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-black/5 dark:hover:bg-white/5 cursor-pointer">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            {{-- Toolbar & Search --}}
            <div class="px-6 py-3 border-b border-gray-100 dark:border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white dark:bg-[#111e16]">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-[var(--ink)]">
                        Selected: <span id="customizerSelectedCount" class="text-emerald-600 font-extrabold">{{ $activeCount }}</span> / <span id="customizerTotalCount">{{ $activeCount }}</span>
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <div class="relative w-52">
                        <i class="bi bi-search absolute left-2.5 top-1/2 -translate-y-1/2 text-[10px] text-[var(--ink-muted)]"></i>
                        <input
                            type="text"
                            id="modalSearchInput"
                            placeholder="Search guest, phone, or ID..."
                            class="h-7 w-full rounded-lg border border-[var(--border)] bg-white dark:bg-neutral-900/60 pl-7 pr-2 text-xs text-[var(--ink)] outline-none focus:border-emerald-500"
                        />
                    </div>
                    <button type="button" id="modalSelectAllBtn" class="h-7 px-2.5 text-[11px] font-semibold rounded-lg border border-[var(--border)] bg-gray-50 dark:bg-neutral-800 text-[var(--ink)] hover:bg-gray-100 cursor-pointer">
                        Select All
                    </button>
                    <button type="button" id="modalClearBtn" class="h-7 px-2 text-[11px] font-semibold rounded-lg text-[var(--ink-muted)] hover:text-rose-600 cursor-pointer">
                        Deselect All
                    </button>
                </div>
            </div>

            {{-- Table --}}
            <div class="p-6 overflow-y-auto flex-1">
                <div class="overflow-x-auto rounded-xl border border-[var(--border)] bg-white dark:bg-[#0e1711]">
                    <table class="w-full border-collapse text-left text-xs" id="modalReservationsTable">
                        <thead>
                            <tr class="sticky top-0 z-10 border-b border-[var(--border)] bg-gray-100 dark:bg-neutral-900 font-semibold text-[var(--ink-muted)] uppercase text-[10px]">
                                <th class="py-2.5 px-3 w-8 text-center">
                                    <input type="checkbox" id="modalMasterCheckbox" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer h-3.5 w-3.5">
                                </th>
                                <th class="py-2.5 px-3 w-16">ID</th>
                                <th class="py-2.5 px-3 min-w-[160px]">Main Guest</th>
                                <th class="py-2.5 px-3 min-w-[120px]">Mobile Phone</th>
                                <th class="py-2.5 px-3 min-w-[130px]">Companions</th>
                                <th class="py-2.5 px-3 text-center w-24">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--border)]" id="modalTableBody">
                            @foreach($reservations as $res)
                                <tr
                                    class="reservation-row hover:bg-black/5 dark:hover:bg-white/5 transition-colors {{ $res['is_active'] ? 'is-selected' : '' }}"
                                    data-id="{{ $res['id'] }}"
                                    data-active="{{ $res['is_active'] ? '1' : '0' }}"
                                    data-status="{{ $res['status'] }}"
                                    data-guest-name="{{ strtolower($res['main_guest_name']) }}"
                                    data-phone="{{ $res['phone'] }}"
                                    data-display-phone="{{ $res['display_phone'] }}"
                                    data-companions-count="{{ $res['companion_count'] }}"
                                    data-companions-json="{{ htmlspecialchars(json_encode($res['companions']), ENT_QUOTES, 'UTF-8') }}"
                                >
                                    <td class="py-2 px-3 text-center align-middle">
                                        <input
                                            type="checkbox"
                                            class="reservation-select-cb rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer h-3.5 w-3.5"
                                            value="{{ $res['id'] }}"
                                            data-id="{{ $res['id'] }}"
                                            data-guest="{{ $res['main_guest_name'] }}"
                                            data-phone="{{ $res['phone'] }}"
                                            data-display-phone="{{ $res['display_phone'] }}"
                                            {{ $res['is_active'] ? 'checked' : '' }}
                                        >
                                    </td>
                                    <td class="py-2 px-3 align-middle font-mono font-bold text-[var(--ink)]">
                                        #{{ $res['id'] }}
                                    </td>
                                    <td class="py-2 px-3 align-middle font-medium text-[var(--ink)]">
                                        <div class="truncate max-w-[160px]" title="{{ $res['main_guest_name'] }}">
                                            {{ $res['main_guest_name'] }}
                                        </div>
                                    </td>
                                    <td class="py-2 px-3 align-middle font-mono text-[var(--ink)]">
                                        @if(!empty($res['phone']))
                                            <span class="select-all">{{ $res['display_phone'] }}</span>
                                        @else
                                            <span class="text-rose-500 font-sans text-[10px]">No Phone</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-3 align-middle">
                                        @if($res['companion_count'] > 0)
                                            <button
                                                type="button"
                                                class="view-companions-btn inline-flex items-center gap-1 rounded-full border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/40 px-2 py-0.5 text-[10px] font-semibold text-amber-800 dark:text-amber-300 hover:bg-amber-100 cursor-pointer"
                                                data-res-id="{{ $res['id'] }}"
                                                data-guest="{{ $res['main_guest_name'] }}"
                                                data-phone="{{ $res['display_phone'] }}"
                                                data-total-guests="{{ $res['number_of_guests'] }}"
                                                data-companions-count="{{ $res['companion_count'] }}"
                                            >
                                                <i class="bi bi-people-fill"></i>
                                                <span>{{ $res['companion_count'] }} Companion{{ $res['companion_count'] > 1 ? 's' : '' }}</span>
                                            </button>
                                        @else
                                            <span class="text-gray-400 text-[10px]">Solo</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-3 align-middle text-center">
                                        @if($res['is_active'])
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 dark:bg-emerald-950 px-2 py-0.5 text-[10px] font-bold text-emerald-800 dark:text-emerald-300">
                                                <span class="live-indicator-dot"></span> In-Park
                                            </span>
                                        @else
                                            <span class="text-[10px] text-gray-500">{{ $res['status'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Footer with Pagination --}}
            <div class="flex items-center justify-between px-6 py-3.5 border-t border-gray-100 dark:border-white/10 bg-gray-50/50 dark:bg-black/20">
                <div class="flex items-center gap-3">
                    <button type="button" id="paginationPrevBtn" class="h-7 px-3 text-[11px] font-semibold rounded-lg border border-[var(--border)] bg-white dark:bg-neutral-800 text-[var(--ink)] hover:bg-gray-100 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed" disabled>
                        <i class="bi bi-chevron-left mr-0.5"></i> Prev
                    </button>
                    <span class="text-xs font-medium text-[var(--ink-muted)]">
                        Page <span id="paginationCurrentPage" class="font-bold text-[var(--ink)]">1</span> of <span id="paginationTotalPages" class="font-bold text-[var(--ink)]">1</span>
                    </span>
                    <button type="button" id="paginationNextBtn" class="h-7 px-3 text-[11px] font-semibold rounded-lg border border-[var(--border)] bg-white dark:bg-neutral-800 text-[var(--ink)] hover:bg-gray-100 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed" disabled>
                        Next <i class="bi bi-chevron-right ml-0.5"></i>
                    </button>
                </div>
                <button type="button" class="close-customizer-modal px-5 py-2 text-xs font-bold rounded-xl bg-emerald-600 text-white shadow hover:bg-emerald-700 cursor-pointer transition-colors">
                    Apply Selection & Return
                </button>
            </div>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- COMPANIONS BREAKDOWN MODAL                                                --}}
    {{-- ========================================================================= --}}
    <div id="companionsModal" class="hidden fixed inset-0 z-[2200] items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity">
        <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-[#111e16] border border-gray-200 dark:border-[#22392b] shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-white/10">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300">
                        <i class="bi bi-people-fill"></i>
                    </span>
                    <div>
                        <h3 class="m-0 text-sm font-bold text-[var(--ink)]" id="companionModalTitle">Companions Breakdown</h3>
                        <p class="m-0 text-xs text-[var(--ink-muted)]" id="companionModalSubtitle">Reservation details</p>
                    </div>
                </div>
                <button type="button" class="close-companions-modal flex h-8 w-8 items-center justify-center rounded-xl text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-black/5 dark:hover:bg-white/5 cursor-pointer">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            <div class="p-6 overflow-y-auto space-y-4 flex-1">
                {{-- Main Guest Card --}}
                <div class="rounded-xl border border-[var(--border)] bg-gray-50/70 dark:bg-neutral-900/40 p-3.5 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Primary Guest</span>
                        <h4 class="m-0 text-sm font-bold text-[var(--ink)]" id="companionMainGuestName">—</h4>
                        <p class="m-0 mt-0.5 text-xs font-mono text-[var(--ink-muted)]" id="companionMainGuestPhone">—</p>
                    </div>
                    <span class="rounded-full bg-emerald-100 dark:bg-emerald-950/60 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700 dark:text-emerald-300">
                        Booker
                    </span>
                </div>

                <div>
                    <h5 class="m-0 mb-2.5 text-xs font-bold text-[var(--ink-muted)] uppercase tracking-wider">
                        Companions List (<span id="companionModalCount">0</span>)
                    </h5>
                    <div id="companionsCardsList" class="space-y-2">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between px-6 py-3.5 border-t border-gray-100 dark:border-white/10 bg-gray-50/40 dark:bg-black/20">
                <span class="text-[11px] text-[var(--ink-muted)]">Hinaguan Nature Park Guest Roster</span>
                <button type="button" class="close-companions-modal px-4 py-1.5 text-xs font-semibold rounded-xl border border-[var(--border)] bg-white dark:bg-neutral-800 text-[var(--ink)] hover:bg-gray-100 dark:hover:bg-neutral-700 transition-colors cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- Admin Chatbot Component --}}
    <x-admin_chatbot />
</body>
</html>
