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

    const closeModal = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
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
                if (action === 'edit') {
                    prepareEditView(row);
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                } else if (action === 'ban') {
                    const verb = row.dataset.banStatus === 'banned' ? 'Unban' : 'Ban';
                    if (!window.confirm(`${verb} ${row.dataset.name}?`)) return;
                    submitStaffAction(row, 'PATCH', '/ban');
                } else if (action === 'delete') {
                    if (!window.confirm(`Delete ${row.dataset.name}'s account? This cannot be undone.`)) return;
                    submitStaffAction(row, 'DELETE', '');
                }
            });
        });
    });

    // ---- Client-side search over name/email ----
    const userSearch = document.getElementById('userSearch');
    const userCountText = document.getElementById('userCountText');
    if (userSearch) {
        const userTableBody = document.querySelector('.users-table tbody');
        const totalUsers = rows.length;
        userSearch.addEventListener('input', () => {
            const q = userSearch.value.trim().toLowerCase();
            let visible = 0;
            rows.forEach((row) => {
                const haystack = `${row.dataset.name || ''} ${row.dataset.email || ''}`.toLowerCase();
                const show = !q || haystack.includes(q);
                row.style.display = show ? '' : 'none';
                if (show) visible += 1;
            });
            if (userCountText) userCountText.textContent = `${visible} of ${totalUsers} staff account(s)`;
            let emptyRow = userTableBody.querySelector('.user-search-empty');
            if (visible === 0) {
                if (!emptyRow) {
                    emptyRow = document.createElement('tr');
                    emptyRow.className = 'user-search-empty';
                    emptyRow.innerHTML = '<td colspan="5" class="table-empty">No staff match your search.</td>';
                    userTableBody.appendChild(emptyRow);
                }
            } else if (emptyRow) {
                emptyRow.remove();
            }
        });
    }

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
        const shouldDelete = window.confirm('Delete this staff account?');
        if (!shouldDelete) return;
        formMethod.value = 'DELETE';
        form.action = `${form.dataset.updateBaseUrl}/${selectedUserId}`;
        form.submit();
    });

    banButton?.addEventListener('click', (event) => {
        event.preventDefault();
        if (!selectedUserId) return;
        formMethod.value = 'PATCH';
        form.action = `${form.dataset.updateBaseUrl}/${selectedUserId}/ban`;
        form.submit();
    });
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['admin_usermanagement']());