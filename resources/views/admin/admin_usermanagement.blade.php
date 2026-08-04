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
<body class="antialiased">
    <div class="dash-layout">
        <x-admin_sidemenu active="users" userName="{{ session('auth_user.name') ?? 'Admin User' }}" userRole="Admin" />

        <div class="dash-main">
            <!-- Page transition overlay with skeleton loading -->
            <div class="page-transition-overlay" id="pageTransitionOverlay">
                <div class="page-transition-skeleton">
                    <div class="skeleton skeleton--title page-transition-skeleton__header"></div>
                    <div class="page-transition-skeleton__stats">
                        <div class="skeleton skeleton--card page-transition-skeleton__stat"></div>
                        <div class="skeleton skeleton--card page-transition-skeleton__stat"></div>
                        <div class="skeleton skeleton--card page-transition-skeleton__stat"></div>
                    </div>
                    <div class="page-transition-skeleton__grid">
                        <div class="skeleton skeleton--panel page-transition-skeleton__panel"></div>
                        <div class="skeleton skeleton--panel page-transition-skeleton__panel"></div>
                    </div>
                </div>
            </div>
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

                @if(session('success'))
                    <div class="alert alert--success">{{ session('success') }}</div>
                @endif

                <section class="users-table-wrap">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staffAccounts as $staff)
                                <tr
                                    class="user-row"
                                    data-user-id="{{ $staff->id }}"
                                    data-name="{{ e($staff->name) }}"
                                    data-email="{{ e($staff->email) }}"
                                    data-ban-status="{{ $staff->ban_status ? 'banned' : 'active' }}"
                                >
                                    <td>{{ $staff->name }}</td>
                                    <td>{{ $staff->email }}</td>
                                    <td>
                                        <span class="badge {{ $staff->ban_status ? 'badge--disabled' : 'badge--enabled' }}">
                                            {{ $staff->ban_status ? 'Banned' : 'Active' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="table-empty">No staff accounts found yet.</td>
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
                        <input id="user_name" name="name" type="text" required disabled>
                    </div>

                    <div class="modal-form__row">
                        <label for="user_email">Email <span>*</span></label>
                        <input id="user_email" name="email" type="email" required disabled>
                    </div>
                </div>

                <div class="modal-form__section">
                    <h4 class="modal-form__section-title">Access & Status</h4>
                    <div class="modal-form__row">
                        <label for="user_password">Password <span>*</span></label>
                        <input id="user_password" name="password" type="password" disabled>
                    </div>

                    <div class="modal-form__row">
                        <label for="user_password_confirmation">Confirm Password <span>*</span></label>
                        <input id="user_password_confirmation" name="password_confirmation" type="password" disabled>
                    </div>

                    <div class="modal-form__row">
                        <label for="ban_status">Account Status</label>
                        <select id="ban_status" name="ban_status" disabled>
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
