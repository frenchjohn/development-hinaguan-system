<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Payment Transactions — Hinaguan Nature Park</title>
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
        'resources/js/admin_js/admin_payment.js',
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
        /* Payment row hover */
        .payment-row {
            transition: background-color 0.15s ease;
        }
        /* Amount emphasis */
        .amount-positive {
            font-weight: 700;
            color: #059669;
        }
        [data-theme="dark"] .amount-positive {
            color: #34d399;
        }
    </style>
</head>
<body class="antialiased admin-portal">
    <div class="dash-layout">
        <x-admin_sidemenu active="payment" userName="{{ session('auth_user.name') ?? 'Admin User' }}" userRole="Admin" />

        <div class="dash-main">
            <x-header
                title="Payment Transactions"
                subtitle="Complete history of all payment events across reservations"
            />

            <main class="dash-content p-6 space-y-6">

                {{-- ============================================================ --}}
                {{-- METRICS CARDS                                                 --}}
                {{-- ============================================================ --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

                    {{-- Total Collected --}}
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)]">
                        <span class="grid h-[2.8rem] w-[2.8rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                            <i class="bi bi-cash-stack text-xl"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="m-0 font-display text-[1.35rem] font-bold leading-none text-[var(--ink)] truncate">
                                ₱{{ number_format($totalCollected, 2) }}
                            </p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Total Collected</p>
                        </div>
                    </article>

                    {{-- Online Payments --}}
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)]">
                        <span class="grid h-[2.8rem] w-[2.8rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300">
                            <i class="bi bi-globe text-xl"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="m-0 font-display text-[1.35rem] font-bold leading-none text-[var(--ink)] truncate">
                                ₱{{ number_format($totalOnline, 2) }}
                            </p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Online Payments</p>
                        </div>
                    </article>

                    {{-- Staff-Handled --}}
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)]">
                        <span class="grid h-[2.8rem] w-[2.8rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">
                            <i class="bi bi-person-badge-fill text-xl"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="m-0 font-display text-[1.35rem] font-bold leading-none text-[var(--ink)] truncate">
                                ₱{{ number_format($totalStaffHandled, 2) }}
                            </p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Staff-Handled</p>
                        </div>
                    </article>

                    {{-- Total Transactions --}}
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)]">
                        <span class="grid h-[2.8rem] w-[2.8rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300">
                            <i class="bi bi-receipt-cutoff text-xl"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="m-0 font-display text-[1.35rem] font-bold leading-none text-[var(--ink)]">
                                {{ $transactionCount }}
                            </p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Total Transactions</p>
                        </div>
                    </article>
                </div>

                {{-- ============================================================ --}}
                {{-- MAIN TABLE SECTION                                            --}}
                {{-- ============================================================ --}}
                <section class="rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-5 shadow-[var(--shadow-sm)]">

                    {{-- Section header --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[var(--border)] pb-4 mb-4">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300">
                                <i class="bi bi-credit-card-2-front-fill text-sm"></i>
                            </span>
                            <div>
                                <h3 class="m-0 text-sm font-bold text-[var(--ink)]">Payment Transaction Log</h3>
                                <p class="m-0 text-[11px] text-[var(--ink-muted)]">
                                    Showing <span id="visibleCount" class="font-semibold text-[var(--ink)]">{{ $transactionCount }}</span> of {{ $transactionCount }} transactions
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- ============================================================ --}}
                    {{-- FILTER BAR                                                    --}}
                    {{-- ============================================================ --}}
                    <div class="flex flex-wrap items-center gap-2.5 mb-4">

                        {{-- Search --}}
                        <div class="relative flex-1 min-w-[180px] max-w-xs">
                            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-[var(--ink-muted)] pointer-events-none"></i>
                            <input
                                type="text"
                                id="paymentSearchInput"
                                placeholder="Search guest, title, reservation…"
                                class="h-9 w-full rounded-xl border border-[var(--border)] bg-gray-50 dark:bg-neutral-900/40 pl-8 pr-3 text-xs text-[var(--ink)] placeholder-[var(--ink-muted)] outline-none focus:border-emerald-500 transition-colors"
                            >
                        </div>

                        {{-- Type filter --}}
                        <div class="relative">
                            <i class="bi bi-funnel absolute left-3 top-1/2 -translate-y-1/2 text-xs text-[var(--ink-muted)] pointer-events-none"></i>
                            <select
                                id="typeFilterSelect"
                                class="h-9 appearance-none rounded-xl border border-[var(--border)] bg-gray-50 dark:bg-neutral-900/40 pl-8 pr-7 text-xs text-[var(--ink)] outline-none focus:border-emerald-500 cursor-pointer transition-colors"
                            >
                                <option value="">All Types</option>
                                <option value="walkin_created">Walk-In Check-In</option>
                                <option value="check_in">Check-In Payment</option>
                                <option value="online_reservation_created">Online Downpayment</option>
                                <option value="companion_added">Companion Added</option>
                                <option value="amenity_added">Amenity Added</option>
                                <option value="additional_charge_paid">Damage / Extra Charge</option>
                                <option value="stay_extended">Stay Extension</option>
                                <option value="amenity_extended">Amenity Extension</option>
                                <option value="check_out">Check-Out</option>
                                <option value="amenity_checked_out">Amenity Check-Out</option>
                            </select>
                        </div>

                        {{-- Date From --}}
                        <div class="relative">
                            <i class="bi bi-calendar-event absolute left-3 top-1/2 -translate-y-1/2 text-xs text-[var(--ink-muted)] pointer-events-none"></i>
                            <input
                                type="date"
                                id="dateFromInput"
                                title="Date from"
                                class="h-9 appearance-none rounded-xl border border-[var(--border)] bg-gray-50 dark:bg-neutral-900/40 pl-8 pr-3 text-xs text-[var(--ink)] outline-none focus:border-emerald-500 cursor-pointer transition-colors"
                            >
                        </div>

                        {{-- Date To --}}
                        <div class="relative">
                            <i class="bi bi-calendar-check absolute left-3 top-1/2 -translate-y-1/2 text-xs text-[var(--ink-muted)] pointer-events-none"></i>
                            <input
                                type="date"
                                id="dateToInput"
                                title="Date to"
                                class="h-9 appearance-none rounded-xl border border-[var(--border)] bg-gray-50 dark:bg-neutral-900/40 pl-8 pr-3 text-xs text-[var(--ink)] outline-none focus:border-emerald-500 cursor-pointer transition-colors"
                            >
                        </div>

                        {{-- Clear filters --}}
                        <button
                            type="button"
                            id="clearFiltersBtn"
                            class="h-9 flex items-center gap-1.5 px-3.5 rounded-xl border border-[var(--border)] bg-gray-50 dark:bg-neutral-900/40 text-xs font-semibold text-[var(--ink-muted)] hover:border-rose-400 hover:text-rose-600 dark:hover:text-rose-400 transition-all cursor-pointer"
                        >
                            <i class="bi bi-x-circle"></i>
                            <span>Clear</span>
                        </button>
                    </div>

                    {{-- ============================================================ --}}
                    {{-- TABLE                                                         --}}
                    {{-- ============================================================ --}}
                    <div class="overflow-x-auto rounded-xl border border-[var(--border)]">
                        <table class="w-full border-collapse text-left text-xs">
                            <thead>
                                <tr class="border-b border-[var(--border)] bg-gray-50 dark:bg-neutral-900/60 font-semibold text-[var(--ink-muted)]">
                                    <th class="py-3 px-4 whitespace-nowrap">Date &amp; Time</th>
                                    <th class="py-3 px-4 whitespace-nowrap">Reservation</th>
                                    <th class="py-3 px-4 min-w-[220px]">Transaction</th>
                                    <th class="py-3 px-4 text-right whitespace-nowrap">Amount</th>
                                    <th class="py-3 px-4 whitespace-nowrap">Handled By</th>
                                    <th class="py-3 px-4 min-w-[200px]">Details</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--border)]" id="paymentTableBody">

                                @forelse($transactions as $tx)
                                    <tr
                                        class="payment-row hover:bg-black/5 dark:hover:bg-white/5"
                                        data-type="{{ $tx['activity_type'] }}"
                                        data-date="{{ $tx['datetime_iso'] }}"
                                        data-search="{{ strtolower($tx['title'] . ' ' . $tx['actor_name'] . ' ' . ($tx['reservation_id'] ?? '')) }}"
                                    >
                                        {{-- Date & Time --}}
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            <p class="m-0 font-medium text-[var(--ink)]">{{ $tx['date'] }}</p>
                                            <p class="m-0 mt-0.5 text-[10px] text-[var(--ink-muted)]">{{ $tx['time'] }}</p>
                                        </td>

                                        {{-- Reservation --}}
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            @if($tx['reservation_id'])
                                                <span class="inline-flex items-center gap-1 rounded-lg bg-gray-100 dark:bg-neutral-800 px-2 py-0.5 text-xs font-bold text-[var(--ink)]">
                                                    <i class="bi bi-hash text-[10px] text-[var(--ink-muted)]"></i>{{ $tx['reservation_id'] }}
                                                </span>
                                            @else
                                                <span class="text-[var(--ink-muted)]">—</span>
                                            @endif
                                        </td>

                                        {{-- Transaction title + type badge --}}
                                        <td class="py-3 px-4">
                                            <div class="flex items-start gap-2 flex-wrap">
                                                {{-- Type badge --}}
                                                @php
                                                    $badgeMap = [
                                                        'walkin_created'              => ['label' => 'Walk-In',   'cls' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'],
                                                        'check_in'                    => ['label' => 'Check-In',  'cls' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'],
                                                        'online_reservation_created'  => ['label' => 'Online',    'cls' => 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300'],
                                                        'companion_added'             => ['label' => 'Companion', 'cls' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300'],
                                                        'amenity_added'               => ['label' => 'Amenity',   'cls' => 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300'],
                                                        'additional_charge_paid'      => ['label' => 'Charge',    'cls' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300'],
                                                        'stay_extended'               => ['label' => 'Extension', 'cls' => 'bg-teal-100 text-teal-800 dark:bg-teal-950/60 dark:text-teal-300'],
                                                        'amenity_extended'            => ['label' => 'Extension', 'cls' => 'bg-teal-100 text-teal-800 dark:bg-teal-950/60 dark:text-teal-300'],
                                                        'check_out'                   => ['label' => 'Check-Out', 'cls' => 'bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-gray-300'],
                                                        'amenity_checked_out'         => ['label' => 'Check-Out', 'cls' => 'bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-gray-300'],
                                                    ];
                                                    $badge = $badgeMap[$tx['activity_type']] ?? ['label' => ucfirst($tx['activity_type']), 'cls' => 'bg-gray-100 text-gray-700 dark:bg-neutral-800 dark:text-gray-300'];
                                                @endphp
                                                <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $badge['cls'] }}">
                                                    {{ $badge['label'] }}
                                                </span>
                                                <span class="text-[var(--ink)] font-medium leading-snug">{{ $tx['title'] }}</span>
                                            </div>
                                        </td>

                                        {{-- Amount --}}
                                        <td class="py-3 px-4 text-right whitespace-nowrap">
                                            @if($tx['payment_amount'] > 0)
                                                <span class="amount-positive">₱{{ number_format($tx['payment_amount'], 2) }}</span>
                                            @else
                                                <span class="text-[var(--ink-muted)]">₱0.00</span>
                                            @endif
                                        </td>

                                        {{-- Handled By --}}
                                        <td class="py-3 px-4 whitespace-nowrap">
                                            @if($tx['is_online'])
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 px-2.5 py-1 text-[10px] font-bold">
                                                    <i class="bi bi-globe2 text-xs"></i>
                                                    Online / Guest
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 text-xs text-[var(--ink)]">
                                                    <i class="bi bi-person-fill text-[var(--ink-muted)]"></i>
                                                    {{ $tx['actor_name'] }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Details --}}
                                        <td class="py-3 px-4">
                                            <p
                                                class="m-0 text-[var(--ink-muted)] text-[11px] leading-relaxed max-w-xs truncate"
                                                title="{{ $tx['description'] }}"
                                            >{{ $tx['description'] ?: '—' }}</p>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="paymentEmptyRow">
                                        <td colspan="6" class="py-14 text-center text-xs text-[var(--ink-muted)]">
                                            <div class="flex flex-col items-center justify-center gap-2">
                                                <i class="bi bi-receipt text-4xl opacity-30"></i>
                                                <p class="m-0 font-medium text-[var(--ink)]">No payment transactions found.</p>
                                                <p class="m-0 text-[11px] text-[var(--ink-muted)]">Transactions will appear here once payments are recorded.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse

                                {{-- JS-injected empty state (shown when filters yield no results) --}}
                                <tr id="paymentNoResultsRow" class="hidden">
                                    <td colspan="6" class="py-14 text-center text-xs text-[var(--ink-muted)]">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <i class="bi bi-search text-4xl opacity-30"></i>
                                            <p class="m-0 font-medium text-[var(--ink)]">No transactions match your filters.</p>
                                            <button
                                                type="button"
                                                id="clearFiltersBtnAlt"
                                                class="mt-1 text-xs text-emerald-600 dark:text-emerald-400 font-bold hover:underline cursor-pointer"
                                            >Clear filters to show all</button>
                                        </div>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </section>

            </main>
        </div>
    </div>

    <x-admin_chatbot />
</body>
</html>
