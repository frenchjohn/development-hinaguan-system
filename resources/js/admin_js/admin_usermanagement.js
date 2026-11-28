window.AppPage = window.AppPage || {};
window.AppPage['admin_usermanagement'] = function () {

    const modal = document.getElementById('userModal');
    const modalTitle = document.getElementById('userModalTitle');
    const form = document.getElementById('userForm');
    const formMethod = document.getElementById('formMethod');
    const userIdInput = document.getElementById('userId');
    const userNameInput = document.getElementById('user_name');
    const userEmailInput = document.getElementById('user_email');
    const userPasswordInput = document.getElementById('user_password');
    const userPasswordConfirmInput = document.getElementById('user_password_confirmation');
    const banStatusInput = document.getElementById('ban_status');
    const submitButton = document.getElementById('userSubmitButton');
    const editButton = document.getElementById('userEditButton');
    const deleteButton = document.getElementById('userDeleteButton');
    const banButton = document.getElementById('userBanButton');
    const openButton = document.querySelector('[data-open-user-modal]');
    const closeButtons = document.querySelectorAll('[data-close-user-modal]');

    const rows = Array.from(document.querySelectorAll('.user-row'));
    let selectedUserId = null;
    let editMode = false;

    const setFormEnabled = (enabled) => {
        [userNameInput, userEmailInput, userPasswordInput, userPasswordConfirmInput, banStatusInput].forEach((field) => {
            if (field) field.disabled = !enabled;
        });
    };

    const openModal = (mode = 'create') => {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        if (mode === 'create') {
            modalTitle.textContent = 'Create New Staff Account';
            form.reset();
            formMethod.value = 'POST';
            form.action = form.dataset.storeUrl;
            userIdInput.value = '';
            userPasswordInput.required = true;
            userPasswordConfirmInput.required = true;
            setFormEnabled(true);
            submitButton.style.display = 'inline-flex';
            editButton.style.display = 'none';
            deleteButton.style.display = 'none';
            banButton.style.display = 'none';
            selectedUserId = null;
            editMode = false;
        }
    };

    let isSubmitting = false;

    const closeModal = () => {
        if (isSubmitting) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    };

    const setCreateLoadingState = (loading) => {
        isSubmitting = loading;
        if (submitButton) {
            submitButton.disabled = loading;
            submitButton.classList.toggle('opacity-70', loading);
            submitButton.classList.toggle('cursor-not-allowed', loading);
            if (loading) {
                submitButton.innerHTML = '<svg class="mr-1.5 inline-block h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Creating...';
            } else {
                submitButton.textContent = 'Create Account';
            }
        }
        closeButtons.forEach((button) => {
            button.disabled = loading;
            button.classList.toggle('opacity-50', loading);
            button.classList.toggle('cursor-not-allowed', loading);
        });
    };

    const prepareEditView = (row) => {
        selectedUserId = row.dataset.userId;
        modalTitle.textContent = 'Edit Staff Account';
        userIdInput.value = selectedUserId;
        userNameInput.value = row.dataset.name;
        userEmailInput.value = row.dataset.email;
        banStatusInput.value = row.dataset.banStatus === 'banned' ? '1' : '0';
        formMethod.value = 'PUT';
        form.action = `${form.dataset.updateBaseUrl}/${selectedUserId}`;
        userPasswordInput.required = false;
        userPasswordConfirmInput.required = false;
        setFormEnabled(false);
        submitButton.style.display = 'none';
        editButton.style.display = 'inline-flex';
        editButton.textContent = 'Edit';
        deleteButton.style.display = 'inline-flex';
        banButton.style.display = 'inline-flex';
        banButton.textContent = row.dataset.banStatus === 'banned' ? 'Unban' : 'Ban';
        editMode = false;
    };

    openButton?.addEventListener('click', () => openModal('create'));
    closeButtons.forEach((button) => button.addEventListener('click', closeModal));

    if (form?.dataset.hasErrors === '1' && modal) {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        userPasswordInput.required = true;
        userPasswordConfirmInput.required = true;
    }

    // Show / hide password toggles
    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.togglePassword);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.querySelector('.eye-open')?.classList.toggle('hidden', show);
            btn.querySelector('.eye-closed')?.classList.toggle('hidden', !show);
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });

    // Validate password length (>= 8) and match before submitting create/edit
    const passwordHint = document.getElementById('userPasswordHint');
    const passwordConfirmHint = document.getElementById('userPasswordConfirmHint');
    const syncPasswordValidity = () => {
        if (!userPasswordConfirmInput || !userPasswordInput) return;
        const isCreate = (formMethod?.value || 'POST').toUpperCase() === 'POST';
        const passVal = userPasswordInput.value || '';
        const confirmVal = userPasswordConfirmInput.value || '';
        const tooShort = passVal.length > 0 && passVal.length < 8;
        const requiredButShort = isCreate && passVal.length < 8;

        if (tooShort || requiredButShort) {
            userPasswordInput.setCustomValidity('Password must be at least 8 characters.');
        } else {
            userPasswordInput.setCustomValidity('');
        }

        userPasswordInput.classList.toggle('!border-red-500', tooShort);
        if (passwordHint) {
            passwordHint.textContent = tooShort
                ? `Password is too short (${passVal.length}/8 characters)`
                : 'Minimum 8 characters';
            passwordHint.className = tooShort
                ? 'mt-1 mb-0 text-[11px] font-medium text-red-600'
                : 'mt-1 mb-0 text-[11px] text-[var(--ink-muted)]';
        }

        const mismatch = confirmVal.length > 0 && passVal !== confirmVal;
        userPasswordConfirmInput.setCustomValidity(mismatch ? 'Passwords do not match.' : '');
        userPasswordConfirmInput.classList.toggle('!border-red-500', mismatch);
        if (passwordConfirmHint) {
            passwordConfirmHint.classList.toggle('hidden', !mismatch);
        }
    };
    userPasswordInput?.addEventListener('input', syncPasswordValidity);
    userPasswordConfirmInput?.addEventListener('input', syncPasswordValidity);
    form?.addEventListener('submit', (event) => {
        if (isSubmitting) {
            event.preventDefault();
            return;
        }
        syncPasswordValidity();
        if (!form.reportValidity()) {
            event.preventDefault();
            return;
        }
        if ((formMethod?.value || 'POST').toUpperCase() === 'POST') {
            setCreateLoadingState(true);
        }
    });
    modal?.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    rows.forEach((row) => {
        row.addEventListener('click', (event) => {
            if (event.target.closest('[data-action]')) return;
            prepareEditView(row);
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        });
    });

    const confirmModal = document.getElementById('userActionConfirmModal');
    const confirmTitle = document.getElementById('userActionConfirmTitle');
    const confirmMessage = document.getElementById('userActionConfirmMessage');
    const confirmIconWrap = document.getElementById('userActionConfirmIconWrap');
    const confirmSvg = document.getElementById('userActionConfirmSvg');
    const confirmTargetWrap = document.getElementById('userActionConfirmTarget');
    const confirmAvatar = document.getElementById('userActionConfirmAvatar');
    const confirmTargetName = document.getElementById('userActionConfirmTargetName');
    const confirmTargetEmail = document.getElementById('userActionConfirmTargetEmail');
    const confirmBtn = document.getElementById('userActionConfirmBtn');
    const confirmCloseButtons = document.querySelectorAll('[data-close-action-confirm]');
    let pendingConfirmCallback = null;

    const getInitials = (name) => (name || '?')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0] || '')
        .join('')
        .toUpperCase();

    const closeActionConfirmModal = () => {
        if (!confirmModal) return;
        confirmModal.classList.add('hidden');
        confirmModal.classList.remove('flex');
        confirmModal.setAttribute('aria-hidden', 'true');
        pendingConfirmCallback = null;
    };

    const openActionConfirmModal = ({ title, message, confirmText, staffName, staffEmail, variant = 'warning', onConfirm }) => {
        if (!confirmModal) return;
        pendingConfirmCallback = onConfirm;
        if (confirmTitle) confirmTitle.textContent = title;
        if (confirmMessage) confirmMessage.textContent = message;
        if (confirmTargetWrap) {
            confirmTargetWrap.style.display = staffName ? 'flex' : 'none';
        }
        if (confirmAvatar) confirmAvatar.textContent = getInitials(staffName);
        if (confirmTargetName) confirmTargetName.textContent = staffName || '';
        if (confirmTargetEmail) {
            confirmTargetEmail.textContent = staffEmail || '';
            confirmTargetEmail.style.display = staffEmail ? 'block' : 'none';
        }
        if (confirmBtn) {
            confirmBtn.textContent = confirmText;
            confirmBtn.className = 'cursor-pointer rounded-lg border-0 px-4 py-2 text-xs font-semibold !text-white shadow-xs transition-colors ' + (
                variant === 'danger'
                    ? '!bg-red-600 hover:!bg-red-700'
                    : variant === 'success'
                        ? '!bg-[#1b5e3a] hover:!bg-[#14472c]'
                        : '!bg-amber-600 hover:!bg-amber-700'
            );
        }
        if (confirmIconWrap) {
            confirmIconWrap.className = 'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ' + (
                variant === 'danger'
                    ? 'bg-red-500/12 text-red-600'
                    : variant === 'success'
                        ? 'bg-emerald-500/12 text-emerald-700'
                        : 'bg-amber-500/12 text-amber-600'
            );
        }
        if (confirmSvg) {
            if (variant === 'danger') {
                confirmSvg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>';
            } else if (variant === 'success') {
                confirmSvg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';
            } else {
                confirmSvg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>';
            }
        }
        confirmModal.classList.remove('hidden');
        confirmModal.classList.add('flex');
        confirmModal.setAttribute('aria-hidden', 'false');
    };

    confirmCloseButtons.forEach((btn) => btn.addEventListener('click', closeActionConfirmModal));
    confirmBtn?.addEventListener('click', () => {
        const cb = pendingConfirmCallback;
        closeActionConfirmModal();
        if (typeof cb === 'function') cb();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && confirmModal && !confirmModal.classList.contains('hidden')) {
            closeActionConfirmModal();
        }
    });

    const submitStaffAction = (row, method, suffix) => {
        selectedUserId = row.dataset.userId;
        formMethod.value = method;
        form.action = `${form.dataset.updateBaseUrl}/${selectedUserId}${suffix || ''}`;
        form.submit();
    };

    rows.forEach((row) => {
        row.querySelectorAll('[data-action]').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.stopPropagation();
                const action = btn.dataset.action;
                const staffName = row.dataset.name || 'this staff member';
                const staffEmail = row.dataset.email || '';
                if (action === 'edit') {
                    prepareEditView(row);
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                } else if (action === 'ban') {
                    const isBanned = row.dataset.banStatus === 'banned';
                    const verb = isBanned ? 'Unban' : 'Ban';
                    openActionConfirmModal({
                        title: `${verb} Staff Account`,
                        message: isBanned
                            ? 'This staff member will be able to sign in again immediately.'
                            : 'This staff member will be signed out and blocked from accessing the portal.',
                        confirmText: `${verb} Account`,
                        staffName,
                        staffEmail,
                        variant: isBanned ? 'success' : 'warning',
                        onConfirm: () => submitStaffAction(row, 'PATCH', '/ban'),
                    });
                } else if (action === 'delete') {
                    openActionConfirmModal({
                        title: 'Delete Staff Account',
                        message: 'This permanently removes the staff account and cannot be undone.',
                        confirmText: 'Delete Account',
                        staffName,
                        staffEmail,
                        variant: 'danger',
                        onConfirm: () => submitStaffAction(row, 'DELETE', ''),
                    });
                }
            });
        });
    });

    // ---- Client-side search & filters (name/email, status, joined date range) ----
    const userSearch = document.getElementById('userSearch');
    const userStatusFilter = document.getElementById('userStatusFilter');
    const userJoinedFrom = document.getElementById('userJoinedFrom');
    const userJoinedTo = document.getElementById('userJoinedTo');
    const userFilterReset = document.getElementById('userFilterReset');
    const userCountText = document.getElementById('userCountText');
    const userTableBody = document.querySelector('.users-table tbody');

    const applyUserFilters = () => {
        if (!userTableBody) return;
        const q = (userSearch?.value || '').trim().toLowerCase();
        const statusVal = userStatusFilter?.value || '';
        const fromDate = userJoinedFrom?.value || '';
        const toDate = userJoinedTo?.value || '';
        const hasFilters = Boolean(q || statusVal || fromDate || toDate);

        if (userFilterReset) {
            userFilterReset.classList.toggle('hidden', !hasFilters);
            userFilterReset.classList.toggle('inline-flex', hasFilters);
        }

        let visible = 0;
        rows.forEach((row) => {
            const haystack = `${row.dataset.name || ''} ${row.dataset.email || ''}`.toLowerCase();
            const rowStatus = row.dataset.banStatus || '';
            const rowJoined = row.dataset.joined || '';

            const matchesSearch = !q || haystack.includes(q);
            const matchesStatus = !statusVal || rowStatus === statusVal;
            const matchesFrom = !fromDate || (rowJoined && rowJoined >= fromDate);
            const matchesTo = !toDate || (rowJoined && rowJoined <= toDate);

            const show = matchesSearch && matchesStatus && matchesFrom && matchesTo;
            row.style.display = show ? '' : 'none';
            if (show) visible += 1;
        });

        if (userCountText) userCountText.textContent = `${visible} of ${rows.length} staff account(s)`;

        let emptyRow = userTableBody.querySelector('.user-search-empty');
        if (visible === 0) {
            if (!emptyRow) {
                emptyRow = document.createElement('tr');
                emptyRow.className = 'user-search-empty';
                emptyRow.innerHTML = '<td colspan="5" class="table-empty">No staff accounts match your filters.</td>';
                userTableBody.appendChild(emptyRow);
            }
        } else if (emptyRow) {
            emptyRow.remove();
        }
    };

    userSearch?.addEventListener('input', applyUserFilters);
    userStatusFilter?.addEventListener('change', applyUserFilters);
    userJoinedFrom?.addEventListener('change', applyUserFilters);
    userJoinedTo?.addEventListener('change', applyUserFilters);
    userFilterReset?.addEventListener('click', () => {
        if (userSearch) userSearch.value = '';
        if (userStatusFilter) userStatusFilter.value = '';
        if (userJoinedFrom) userJoinedFrom.value = '';
        if (userJoinedTo) userJoinedTo.value = '';
        applyUserFilters();
    });

    editButton?.addEventListener('click', (event) => {
        event.preventDefault();
        if (!selectedUserId) return;

        if (!editMode) {
            setFormEnabled(true);
            editButton.textContent = 'Save Changes';
            editMode = true;
            return;
        }

        if (form && !form.reportValidity()) return;

        formMethod.value = 'PUT';
        form.action = `${form.dataset.updateBaseUrl}/${selectedUserId}`;
        form.submit();
    });

    deleteButton?.addEventListener('click', (event) => {
        event.preventDefault();
        if (!selectedUserId) return;
        const staffName = userNameInput?.value?.trim() || 'this staff account';
        const staffEmail = userEmailInput?.value?.trim() || '';
        openActionConfirmModal({
            title: 'Delete Staff Account',
            message: 'This permanently removes the staff account and cannot be undone.',
            confirmText: 'Delete Account',
            staffName,
            staffEmail,
            variant: 'danger',
            onConfirm: () => {
                formMethod.value = 'DELETE';
                form.action = `${form.dataset.updateBaseUrl}/${selectedUserId}`;
                form.submit();
            },
        });
    });

    banButton?.addEventListener('click', (event) => {
        event.preventDefault();
        if (!selectedUserId) return;
        const isBanned = banStatusInput?.value === '1';
        const verb = isBanned ? 'Unban' : 'Ban';
        const staffName = userNameInput?.value?.trim() || 'this staff account';
        const staffEmail = userEmailInput?.value?.trim() || '';
        openActionConfirmModal({
            title: `${verb} Staff Account`,
            message: isBanned
                ? 'This staff member will be able to sign in again immediately.'
                : 'This staff member will be signed out and blocked from accessing the portal.',
            confirmText: `${verb} Account`,
            staffName,
            staffEmail,
            variant: isBanned ? 'success' : 'warning',
            onConfirm: () => {
                formMethod.value = 'PATCH';
                form.action = `${form.dataset.updateBaseUrl}/${selectedUserId}/ban`;
                form.submit();
            },
        });
    });
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['admin_usermanagement']());