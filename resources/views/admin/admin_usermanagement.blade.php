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
        'resources/css/staff_css/staff_shared.css',
        'resources/css/admin_css/admin_shared.css',
        'resources/css/homepage.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/staff_sidemenu.css',
        'resources/css/chatbot.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/admin_js/admin_usermanagement.js',
        'resources/js/admin_chatbot.js',
    ])
</head>
<body class="antialiased admin-portal">
    <div class="dash-layout">
        <x-admin_sidemenu active="users" userName="{{ session('auth_user.name') ?? 'Admin User' }}" userRole="Admin" />

        <div class="dash-main">
            <!-- Page transition overlay with skeleton loading -->
            <main class="dash-content p-6">
                <x-header
                    title="User Management"
                    subtitle="Manage staff accounts and access"
                />
                <section class="mb-6 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="m-0 mb-[0.3rem] text-[0.8rem] font-bold uppercase tracking-[0.16em] text-[var(--hp-gold-dark)]">Manage Staff Accounts</p>
                        <h2 class="m-0 text-[1.5rem] font-bold text-[var(--hp-text)]">All staff users</h2>
                        <p class="m-0 mt-[0.35rem] text-[var(--hp-text-muted)]">Create, edit, ban, or remove staff accounts from the system.</p>
                    </div>
                    <button type="button" class="btn btn--primary" data-open-user-modal>New Staff Account</button>
                </section>

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
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[var(--border)] bg-[var(--surface)] px-[1.1rem] py-[0.9rem]">
                        <div class="relative min-w-[14rem] max-w-[24rem] flex-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="pointer-events-none absolute left-[0.8rem] top-1/2 h-[0.95rem] w-[0.95rem] -translate-y-1/2 text-[var(--ink-faint)]"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                            <input id="userSearch" type="search" placeholder="Search by name or email..." autocomplete="off" class="w-full rounded-[var(--radius-sm)] border border-[var(--border-strong)] bg-[var(--surface-2)] py-[0.55rem] pl-[2.2rem] pr-[0.9rem] text-[0.84rem] text-[var(--ink)] outline-none transition-all duration-200 focus:border-[var(--green)] focus:bg-[var(--surface)] focus:shadow-[0_0_0_3px_rgba(23,138,82,0.12)]">
                        </div>
                        <span class="whitespace-nowrap rounded-full border border-[var(--border)] bg-[var(--surface-2)] px-3 py-[0.28rem] text-[0.76rem] font-semibold text-[var(--ink-muted)]" id="userCountText">{{ $totalStaff }} staff account(s)</span>
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

    <div class="modal" id="userModal" aria-hidden="true">
        <div class="modal__backdrop" data-close-user-modal></div>
        <div class="modal__panel">
            <div class="modal__header">
                <h3 id="userModalTitle">Create New Staff Account</h3>
                <button type="button" class="modal__close" data-close-user-modal>&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}" class="modal-form" id="userForm" data-store-url="{{ route('admin.users.store') }}" data-update-base-url="{{ url('admin/users') }}">
                @csrf
                <input type="hidden" name="user_id" id="userId">
                <input type="hidden" name="_method" id="formMethod" value="POST">

                <div class="modal-form__section">
                    <h4 class="modal-form__section-title">Staff Details</h4>
                    <div class="modal-form__row">
                        <label for="user_name">Name <span>*</span></label>
                        <input id="user_name" name="name" type="text" value="{{ old('name') }}" required>
                    </div>

                    <div class="modal-form__row">
                        <label for="user_email">Email <span>*</span></label>
                        <input id="user_email" name="email" type="email" value="{{ old('email') }}" required>
                    </div>
                </div>

                <div class="modal-form__section">
                    <h4 class="modal-form__section-title">Access & Status</h4>
                    <div class="modal-form__row">
                        <label for="user_password">Password <span>*</span></label>
                        <input id="user_password" name="password" type="password">
                    </div>

                    <div class="modal-form__row">
                        <label for="user_password_confirmation">Confirm Password <span>*</span></label>
                        <input id="user_password_confirmation" name="password_confirmation" type="password">
                    </div>

                    <div class="modal-form__row">
                        <label for="ban_status">Account Status</label>
                        <select id="ban_status" name="ban_status">
                            <option value="0">Active</option>
                            <option value="1">Banned</option>
                        </select>
                    </div>
                </div>

                <div class="modal-form__actions">
                    <button type="button" class="btn btn--ghost" data-close-user-modal>Cancel</button>
                    <button type="button" class="btn btn--ghost" id="userEditButton" style="display:none;">Edit</button>
                    <button type="submit" class="btn btn--primary" id="userSubmitButton">Create Account</button>
                    <button type="button" class="btn btn--danger" id="userDeleteButton" style="display:none;">Delete</button>
                    <button type="submit" class="btn btn--warning" id="userBanButton" style="display:none;" formaction="" formmethod="POST">Ban/Unban</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Admin AI Intelligence Chatbot --}}
    <x-admin_chatbot />
</body>
</html>
