<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|playfair-display:600,700" rel="stylesheet">
    @vite([
        'resources/css/app.css',
        'resources/components/css_js/header.css',
        'resources/components/css_js/admin_sidemenu.css',
        'resources/css/admin_css/admin_usermanagement.css',
        'resources/components/css_js/header.js',
        'resources/components/css_js/sidemenu.js',
        'resources/js/admin_js/admin_usermanagement.js',
    ])
</head>
<body class="antialiased admin-portal">
    <div class="dash-layout">
        <x-admin_sidemenu active="users" userName="{{ session('auth_user.name') ?? 'Admin User' }}" userRole="Admin" />

        <div class="dash-main">
            <!-- Page transition overlay with skeleton loading -->
            <main class="dash-content">
                <x-header
                    title="User Management"
                    subtitle="Manage staff accounts and access"
                />
                <section class="users-head">
                    <div>
                        <p class="users-head__eyebrow">Manage Staff Accounts</p>
                        <h2 class="users-head__title">All staff users</h2>
                        <p class="users-head__text">Create, edit, ban, or remove staff accounts from the system.</p>
                    </div>
                    <button type="button" class="btn btn--primary" data-open-user-modal>New Staff Account</button>
                </section>

                <div class="users-stats">
                    <article class="users-stat">
                        <span class="users-stat__icon users-stat__icon--total">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </span>
                        <div class="users-stat__body">
                            <p class="users-stat__value">{{ $totalStaff }}</p>
                            <p class="users-stat__label">Total Staff Accounts</p>
                        </div>
                    </article>
                    <article class="users-stat">
                        <span class="users-stat__icon users-stat__icon--active">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div class="users-stat__body">
                            <p class="users-stat__value">{{ $activeStaff }}</p>
                            <p class="users-stat__label">Active Accounts</p>
                        </div>
                    </article>
                    <article class="users-stat">
                        <span class="users-stat__icon users-stat__icon--banned">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </span>
                        <div class="users-stat__body">
                            <p class="users-stat__value">{{ $bannedStaff }}</p>
                            <p class="users-stat__label">Banned Accounts</p>
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
                    <div class="users-toolbar">
                        <div class="users-toolbar__search">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                            <input id="userSearch" type="search" placeholder="Search by name or email..." autocomplete="off">
                        </div>
                        <span class="users-toolbar__count" id="userCountText">{{ $totalStaff }} staff account(s)</span>
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
</body>
</html>
