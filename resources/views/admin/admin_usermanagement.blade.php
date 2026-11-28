<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>User Management — Hinaguan Nature Park</title>
    <script>
        // Prevent flash of wrong theme by setting theme immediately
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
        'resources/css/chatbot.css',
        'resources/css/staff_css/staff_shared.css',
        'resources/css/admin_css/admin_shared.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/admin_js/admin_usermanagement.js',
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
<body class="antialiased admin-portal">
    <div class="dash-layout">
        <x-admin_sidemenu active="users" userName="{{ session('auth_user.name') ?? 'Admin User' }}" userRole="Admin" />

        <div class="dash-main">
            <x-header
                title="User Management"
                subtitle="Manage staff accounts and access"
            />
            <!-- Page transition overlay with skeleton loading -->
            <main class="dash-content p-6">
                <div class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--border-strong)] hover:shadow-[var(--shadow-md)]">
                        <span class="grid h-[2.6rem] w-[2.6rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-[var(--green-soft)] text-[var(--green)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-[1.25rem] w-[1.25rem]"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </span>
                        <div>
                            <p class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]">{{ $totalStaff }}</p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Total Staff Accounts</p>
                        </div>
                    </article>
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--border-strong)] hover:shadow-[var(--shadow-md)]">
                        <span class="grid h-[2.6rem] w-[2.6rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-[var(--green-soft)] text-[var(--green-deep)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-[1.25rem] w-[1.25rem]"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div>
                            <p class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]">{{ $activeStaff }}</p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Active Accounts</p>
                        </div>
                    </article>
                    <article class="flex items-center gap-[0.9rem] rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] p-[1.1rem_1.25rem] shadow-[var(--shadow-sm)] transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--border-strong)] hover:shadow-[var(--shadow-md)]">
                        <span class="grid h-[2.6rem] w-[2.6rem] shrink-0 place-items-center rounded-[var(--radius-md)] bg-[var(--danger-soft)] text-[var(--danger)]">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-[1.25rem] w-[1.25rem]"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </span>
                        <div>
                            <p class="m-0 font-display text-[1.45rem] font-bold leading-none text-[var(--ink)]">{{ $bannedStaff }}</p>
                            <p class="m-0 mt-[0.3rem] text-[0.72rem] font-semibold uppercase tracking-[0.04em] text-[var(--ink-muted)]">Banned Accounts</p>
                        </div>
                    </article>
                </div>

                @if(session('success'))
                    <div class="alert alert--success">{{ session('success') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert--error">
                        <strong>Unable to save the staff account:</strong>
                        <ul class="alert__list">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section class="users-table-wrap">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--border)] bg-[var(--surface)] px-[1.1rem] py-3.5">
                        <div class="flex flex-wrap items-center gap-2.5 flex-1 min-w-0">
                            <div class="relative w-full sm:w-64">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ink-faint)]"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                                <input id="userSearch" type="search" placeholder="Search by name or email..." autocomplete="off" class="h-10 w-full rounded-[var(--radius-sm)] border border-[var(--border-strong)] bg-[var(--surface-2)] pl-9 pr-3 text-[0.84rem] text-[var(--ink)] outline-none transition-all duration-200 focus:border-[var(--green)] focus:bg-[var(--surface)] focus:shadow-[0_0_0_3px_rgba(23,138,82,0.12)]">
                            </div>

                            <select id="userStatusFilter" aria-label="Filter by status" class="h-10 rounded-[var(--radius-sm)] border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 text-[0.84rem] text-[var(--ink)] outline-none transition-all duration-200 focus:border-[var(--green)] focus:bg-[var(--surface)]">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="banned">Banned</option>
                            </select>

                            <div class="flex h-10 items-center gap-1.5 rounded-[var(--radius-sm)] border border-[var(--border-strong)] bg-[var(--surface-2)] px-2.5">
                                <span class="text-[0.72rem] font-semibold uppercase tracking-wide text-[var(--ink-muted)]">Joined</span>
                                <input id="userJoinedFrom" type="date" aria-label="Joined from date" class="h-7 bg-transparent text-[0.8rem] text-[var(--ink)] outline-none">
                                <span class="text-xs text-[var(--ink-faint)]">–</span>
                                <input id="userJoinedTo" type="date" aria-label="Joined to date" class="h-7 bg-transparent text-[0.8rem] text-[var(--ink)] outline-none">
                            </div>

                            <button type="button" id="userFilterReset" class="hidden h-10 cursor-pointer items-center rounded-[var(--radius-sm)] border border-[var(--border-strong)] bg-transparent px-3 text-xs font-semibold text-[var(--ink-muted)] hover:bg-[var(--surface-2)] hover:text-[var(--ink)]">
                                Clear
                            </button>
                        </div>

                        <button type="button" class="btn btn--primary !m-0 inline-flex h-10 shrink-0 items-center gap-2" data-open-user-modal>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <span>New Staff Account</span>
                        </button>
                    </div>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Joined</th>
                                <th>Status</th>
                                <th class="users-table__actions-head">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staffAccounts as $staff)
                                @php $initials = strtoupper(implode('', array_map(fn ($w) => $w[0] ?? '', array_slice(preg_split('/\s+/', trim($staff->name ?? '?')), 0, 2)))); @endphp
                                <tr
                                    class="user-row"
                                    data-user-id="{{ $staff->id }}"
                                    data-name="{{ e($staff->name) }}"
                                    data-email="{{ e($staff->email) }}"
                                    data-joined="{{ $staff->created_at ? \Carbon\Carbon::parse($staff->created_at)->format('Y-m-d') : '' }}"
                                    data-ban-status="{{ $staff->ban_status ? 'banned' : 'active' }}"
                                >
                                    <td>
                                        <span class="cell-person">
                                            <span class="cell-person__avatar">{{ $initials ?: '?' }}</span>
                                            <span class="cell-person__name">{{ $staff->name }}</span>
                                        </span>
                                    </td>
                                    <td class="mono-cell">{{ $staff->email }}</td>
                                    <td class="mono-cell">{{ $staff->created_at ? \Carbon\Carbon::parse($staff->created_at)->format('M d, Y') : '—' }}</td>
                                    <td>
                                        <span class="status-pill {{ $staff->ban_status ? 'status-pill--banned' : 'status-pill--active' }}">
                                            {{ $staff->ban_status ? 'Banned' : 'Active' }}
                                        </span>
                                    </td>
                                    <td class="users-table__actions">
                                        <button type="button" class="row-action row-action--edit" data-action="edit" title="Edit account">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button type="button" class="row-action row-action--ban" data-action="ban" title="{{ $staff->ban_status ? 'Unban account' : 'Ban account' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        </button>
                                        <button type="button" class="row-action row-action--delete" data-action="delete" title="Delete account">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="table-empty">No staff accounts found yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            </main>
        </div>
    </div>

    <div class="modal !items-center !justify-center !p-4" id="userModal" aria-hidden="true">
        <div class="modal__backdrop" data-close-user-modal></div>
        <div class="relative z-10 my-auto w-full max-w-[540px] overflow-hidden rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] shadow-xl">
            <div class="flex items-center justify-between border-b border-[var(--border)] px-6 py-4">
                <h3 id="userModalTitle" class="m-0 font-display text-lg font-bold text-[var(--ink)]">Create New Staff Account</h3>
                <button type="button" data-close-user-modal aria-label="Close" class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border-0 bg-transparent text-[var(--ink-muted)] transition-colors hover:bg-[var(--surface-2)] hover:text-[var(--ink)]">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}" id="userForm" data-store-url="{{ route('admin.users.store') }}" data-update-base-url="{{ url('admin/users') }}" data-has-errors="{{ $errors->any() ? '1' : '0' }}">
                @csrf
                <input type="hidden" name="user_id" id="userId">
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" id="ban_status" name="ban_status" value="0">

                <div class="space-y-4 px-6 py-5">
                    @if($errors->any())
                        <div class="rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-xs text-red-700">
                            @foreach ($errors->all() as $error)
                                <p class="m-0">{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="user_name" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Name <span class="text-red-500">*</span></label>
                            <input id="user_name" name="name" type="text" value="{{ old('name') }}" placeholder="e.g. Juan Dela Cruz" required class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]">
                        </div>

                        <div>
                            <label for="user_email" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Email <span class="text-red-500">*</span></label>
                            <input id="user_email" name="email" type="email" value="{{ old('email') }}" placeholder="staff@hinaguan.com" required class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="user_password" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input id="user_password" name="password" type="password" minlength="8" placeholder="At least 8 characters" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] py-2 pl-3 pr-9 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]">
                                <button type="button" data-toggle-password="user_password" aria-label="Show password" class="absolute right-2.5 top-1/2 -translate-y-1/2 cursor-pointer border-0 bg-transparent p-1 text-[var(--ink-muted)] hover:text-[var(--ink)]">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4 eye-open"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="hidden h-4 w-4 eye-closed"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                                </button>
                            </div>
                            <p id="userPasswordHint" class="mt-1 mb-0 text-[11px] text-[var(--ink-muted)]">Minimum 8 characters</p>
                        </div>

                        <div>
                            <label for="user_password_confirmation" class="mb-1.5 block text-xs font-semibold text-[var(--ink)]">Confirm Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input id="user_password_confirmation" name="password_confirmation" type="password" minlength="8" placeholder="Re-enter password" class="w-full rounded-lg border border-[var(--border-strong)] bg-[var(--surface-2)] py-2 pl-3 pr-9 text-sm text-[var(--ink)] outline-none transition-colors focus:border-[var(--green)] focus:bg-[var(--surface)]">
                                <button type="button" data-toggle-password="user_password_confirmation" aria-label="Show confirm password" class="absolute right-2.5 top-1/2 -translate-y-1/2 cursor-pointer border-0 bg-transparent p-1 text-[var(--ink-muted)] hover:text-[var(--ink)]">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4 eye-open"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="hidden h-4 w-4 eye-closed"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                                </button>
                            </div>
                            <p id="userPasswordConfirmHint" class="mt-1 mb-0 text-[11px] text-red-600 hidden">Passwords do not match</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2.5 border-t border-[var(--border)] bg-[var(--surface-2)] px-6 py-3.5">
                    <button type="button" class="btn btn--ghost" data-close-user-modal>Cancel</button>
                    <button type="button" class="btn btn--ghost" id="userEditButton" style="display:none;">Edit</button>
                    <button type="submit" class="btn btn--primary" id="userSubmitButton">Create Account</button>
                    <button type="button" class="btn btn--danger" id="userDeleteButton" style="display:none;">Delete</button>
                    <button type="submit" class="btn btn--warning" id="userBanButton" style="display:none;" formaction="" formmethod="POST">Ban/Unban</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Ban / Unban / Delete Confirmation Modal --}}
    <div id="userActionConfirmModal" aria-hidden="true" class="fixed inset-0 z-[1300] hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/45 backdrop-blur-[2px]" data-close-action-confirm></div>
        <div role="dialog" aria-modal="true" aria-labelledby="userActionConfirmTitle" class="relative z-10 w-full max-w-[440px] overflow-hidden rounded-[var(--radius-lg)] border border-[var(--border)] bg-[var(--surface)] shadow-xl">
            <div class="flex items-center justify-between border-b border-[var(--border)] px-5 py-4">
                <div class="flex items-center gap-3">
                    <span id="userActionConfirmIconWrap" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-500/12 text-amber-600">
                        <svg id="userActionConfirmSvg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-5 w-5"></svg>
                    </span>
                    <h3 id="userActionConfirmTitle" class="m-0 font-display text-base font-bold text-[var(--ink)]">Confirm Action</h3>
                </div>
                <button type="button" data-close-action-confirm aria-label="Close" class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border-0 bg-transparent text-[var(--ink-muted)] transition-colors hover:bg-[var(--surface-2)] hover:text-[var(--ink)]">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-5 py-4 space-y-3">
                <div id="userActionConfirmTarget" class="flex items-center gap-3 rounded-lg border border-[var(--border)] bg-[var(--surface-2)] px-3.5 py-2.5">
                    <span id="userActionConfirmAvatar" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#1b5e3a] text-xs font-bold text-white uppercase"></span>
                    <div class="min-w-0">
                        <p id="userActionConfirmTargetName" class="m-0 truncate text-sm font-semibold text-[var(--ink)]"></p>
                        <p id="userActionConfirmTargetEmail" class="m-0 truncate text-xs text-[var(--ink-muted)]"></p>
                    </div>
                </div>
                <p id="userActionConfirmMessage" class="m-0 text-sm leading-relaxed text-[var(--ink-muted)]"></p>
            </div>
            <div class="flex items-center justify-end gap-2.5 border-t border-[var(--border)] bg-[var(--surface-2)] px-5 py-3.5">
                <button type="button" data-close-action-confirm class="btn btn--ghost cursor-pointer">Cancel</button>
                <button type="button" id="userActionConfirmBtn" class="cursor-pointer rounded-lg border-0 px-4 py-2 text-xs font-semibold text-white shadow-xs transition-colors">Confirm</button>
            </div>
        </div>
    </div>

    {{-- Admin AI Intelligence Chatbot --}}
    <x-admin_chatbot />
</body>
</html>
