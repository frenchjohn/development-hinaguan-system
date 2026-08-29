window.AppPage = window.AppPage || {};
window.AppPage['admin_settings'] = function () {
    // Drill-down navigation functionality
    const settingsMenu = document.getElementById('settingsMenu');
    const menuCards = document.querySelectorAll('.admin-settings__menu-card, #settingsMenu [data-target]');
    const contentSections = document.querySelectorAll('.admin-settings__content');
    const backButtons = document.querySelectorAll('.admin-settings__back-btn');

    // Handle menu card clicks
    menuCards.forEach(card => {
        card.addEventListener('click', () => {
            const targetId = card.getAttribute('data-target');

            // Hide menu
            if (settingsMenu) {
                settingsMenu.classList.add('admin-settings__menu--hidden');
            }

            // Show target content
            contentSections.forEach(section => {
                section.classList.add('admin-settings__content--hidden');
            });
            const targetContent = document.getElementById(targetId);
            if (targetContent) {
                targetContent.classList.remove('admin-settings__content--hidden');
            }
        });
    });

    // Handle back button clicks
    backButtons.forEach(backBtn => {
        backBtn.addEventListener('click', () => {
            // Hide all content sections
            contentSections.forEach(section => {
                section.classList.add('admin-settings__content--hidden');
            });

            // Show menu
            if (settingsMenu) {
                settingsMenu.classList.remove('admin-settings__menu--hidden');
            }
        });
    });

    // Park Settings Edit functionality
    const editParkSettingsBtn = document.getElementById('editParkSettingsBtn');
    const cancelParkSettingsBtn = document.getElementById('cancelParkSettingsBtn');
    const parkSettingsForm = document.getElementById('parkSettingsForm');
    const parkSettingsFormActions = document.getElementById('parkSettingsFormActions');
    const parkSettingsInputs = parkSettingsForm ? parkSettingsForm.querySelectorAll('input, select, textarea') : [];
    const parkSettingsSuccessModal = document.getElementById('parkSettingsSuccessModal');
    const closeParkSettingsSuccessModal = document.getElementById('closeParkSettingsSuccessModal');

    const parkStatusOpen = document.getElementById('park_status_open');
    const parkStatusClosed = document.getElementById('park_status_closed');
    const closeDescriptionWrapper = document.getElementById('closeDescriptionWrapper');
    const closeDescriptionInput = document.getElementById('close_description');

    // Handle Park Status Radio Toggle
    const updateCloseDescriptionVisibility = () => {
        if (parkStatusClosed?.checked) {
            closeDescriptionWrapper?.classList.remove('hidden');
        } else {
            closeDescriptionWrapper?.classList.add('hidden');
            if (closeDescriptionInput) {
                closeDescriptionInput.value = '';
            }
        }
    };

    parkStatusOpen?.addEventListener('change', updateCloseDescriptionVisibility);
    parkStatusClosed?.addEventListener('change', updateCloseDescriptionVisibility);

    // Store original values for cancel functionality
    let originalParkSettingsValues = {};
    let originalParkStatus = 'open';

    editParkSettingsBtn?.addEventListener('click', () => {
        // Store original values
        parkSettingsInputs.forEach(input => {
            if (input.type === 'radio') {
                if (input.checked) {
                    originalParkStatus = input.value;
                }
            } else {
                originalParkSettingsValues[input.id || input.name] = input.value;
            }
            input.disabled = false;
        });

        // Show form actions, hide edit button
        parkSettingsFormActions?.classList.remove('admin-settings__form-actions--hidden');
        editParkSettingsBtn?.classList.add('admin-settings__btn--hidden');
    });

    cancelParkSettingsBtn?.addEventListener('click', () => {
        // Restore original values
        parkSettingsInputs.forEach(input => {
            if (input.type === 'radio') {
                input.checked = (input.value === originalParkStatus);
            } else {
                input.value = originalParkSettingsValues[input.id || input.name] || '';
            }
            input.disabled = true;
        });

        updateCloseDescriptionVisibility();

        // Hide form actions, show edit button
        parkSettingsFormActions?.classList.add('admin-settings__form-actions--hidden');
        editParkSettingsBtn?.classList.remove('admin-settings__btn--hidden');
    });

    // Handle park settings form submission
    parkSettingsForm?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(parkSettingsForm);

        try {
            const response = await fetch(parkSettingsForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (response.ok) {
                const data = await response.json();

                // Update original values
                originalParkStatus = parkStatusClosed?.checked ? 'closed' : 'open';
                parkSettingsInputs.forEach(input => {
                    if (input.type !== 'radio') {
                        originalParkSettingsValues[input.id || input.name] = input.value;
                    }
                    input.disabled = true;
                });

                // Show success modal
                if (parkSettingsSuccessModal) {
                    parkSettingsSuccessModal.style.display = 'flex';
                }

                parkSettingsFormActions?.classList.add('admin-settings__form-actions--hidden');
                editParkSettingsBtn?.classList.remove('admin-settings__btn--hidden');

                // If header status pill exists, update it dynamically
                const headerStatusBadge = document.querySelector('[data-park-status-badge]');
                if (headerStatusBadge && data.park_status) {
                    if (data.park_status === 'closed') {
                        headerStatusBadge.className = 'dash-header__status-badge dash-header__status-badge--closed';
                        headerStatusBadge.setAttribute('data-status', 'closed');
                        headerStatusBadge.innerHTML = `
                            <span class="dash-header__status-dot"></span>
                            <span class="font-semibold">Park Closed</span>
                        `;
                        const tooltip = headerStatusBadge.parentElement?.querySelector('[data-park-status-tooltip]');
                        if (tooltip) {
                            tooltip.textContent = data.close_description || 'The park is temporarily closed.';
                        }
                    } else {
                        headerStatusBadge.className = 'dash-header__status-badge';
                        headerStatusBadge.setAttribute('data-status', 'open');
                        headerStatusBadge.innerHTML = `
                            <span class="dash-header__status-dot"></span>
                            <span class="font-semibold">Park Open</span>
                        `;
                        const tooltip = headerStatusBadge.parentElement?.querySelector('[data-park-status-tooltip]');
                        if (tooltip) {
                            tooltip.textContent = 'The park is currently open to visitors and guests.';
                        }
                    }
                }
            } else {
                // Handle error
                const errorData = await response.json();
                console.error('Error updating park settings:', errorData);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });

    // Close success modal
    closeParkSettingsSuccessModal?.addEventListener('click', () => {
        if (parkSettingsSuccessModal) {
            parkSettingsSuccessModal.style.display = 'none';
        }
    });

    // Close modal when clicking outside
    parkSettingsSuccessModal?.addEventListener('click', (e) => {
        if (e.target === parkSettingsSuccessModal) {
            parkSettingsSuccessModal.style.display = 'none';
        }
    });

    const changePasswordForm = document.getElementById('changePasswordForm');
    const changeEmailForm = document.getElementById('changeEmailForm');
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const toggleEmailBtn = document.getElementById('toggleEmailBtn');
    const cancelPasswordBtn = document.getElementById('cancelPasswordBtn');
    const cancelEmailBtn = document.getElementById('cancelEmailBtn');
    const otpPasswordModal = document.getElementById('otpPasswordModal');
    const otpEmailModal = document.getElementById('otpEmailModal');
    const verifyPasswordOtpBtn = document.getElementById('verifyPasswordOtpBtn');
    const verifyEmailOtpBtn = document.getElementById('verifyEmailOtpBtn');
    const cancelPasswordOtpBtn = document.getElementById('cancelPasswordOtpBtn');
    const cancelEmailOtpBtn = document.getElementById('cancelEmailOtpBtn');
    const otpPasswordCodeInput = document.getElementById('otpPasswordCode');
    const otpEmailCodeInput = document.getElementById('otpEmailCode');

    // Get submit buttons
    const passwordSubmitBtn = changePasswordForm ? changePasswordForm.querySelector('button[type="submit"]') : null;
    const emailSubmitBtn = changeEmailForm ? changeEmailForm.querySelector('button[type="submit"]') : null;

    let pendingPasswordData = null;
    let pendingEmailData = null;
    let isPasswordLoading = false;
    let isEmailLoading = false;

    // ===== PASSWORD SECTION =====

    // Toggle password form visibility
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', function () {
            changePasswordForm?.classList.toggle('admin-settings__form--hidden');
            togglePasswordBtn.classList.toggle('active');
        });
    }

    // Cancel password form
    if (cancelPasswordBtn) {
        cancelPasswordBtn.addEventListener('click', function () {
            changePasswordForm?.classList.add('admin-settings__form--hidden');
            togglePasswordBtn?.classList.remove('active');
            changePasswordForm?.reset();
            clearErrors(['currentPasswordError', 'newPasswordError', 'confirmPasswordError']);
        });
    }

    // Handle Change Password Form
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            // Clear previous errors
            clearErrors(['currentPasswordError', 'newPasswordError', 'confirmPasswordError']);

            // Validation
            if (!currentPassword || !newPassword || !confirmPassword) {
                showError('currentPasswordError', 'All fields are required');
                return;
            }

            if (newPassword.length < 8) {
                showError('newPasswordError', 'Password must be at least 8 characters');
                return;
            }

            if (newPassword !== confirmPassword) {
                showError('confirmPasswordError', 'Passwords do not match');
                return;
            }

            // Send OTP
            try {
                isPasswordLoading = true;
                if (passwordSubmitBtn) {
                    passwordSubmitBtn.disabled = true;
                    passwordSubmitBtn.classList.add('loading');
                }
                const originalText = passwordSubmitBtn ? passwordSubmitBtn.textContent : 'Send OTP Code';
                if (passwordSubmitBtn) passwordSubmitBtn.textContent = 'Sending...';

                const response = await fetch('/admin/send-password-otp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || '',
                    },
                    body: JSON.stringify({
                        current_password: currentPassword,
                        new_password: newPassword,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    if (data.errors) {
                        Object.keys(data.errors).forEach((key) => {
                            showError(key + 'Error', data.errors[key][0]);
                        });
                    } else {
                        showError('currentPasswordError', data.message || 'Failed to send OTP');
                    }
                    isPasswordLoading = false;
                    if (passwordSubmitBtn) {
                        passwordSubmitBtn.disabled = false;
                        passwordSubmitBtn.classList.remove('loading');
                        passwordSubmitBtn.textContent = originalText;
                    }
                    return;
                }

                // Store password data for OTP verification
                pendingPasswordData = {
                    current_password: currentPassword,
                    new_password: newPassword,
                };

                // Show OTP Modal
                if (otpPasswordModal) otpPasswordModal.style.display = 'flex';
                if (otpPasswordCodeInput) otpPasswordCodeInput.focus();
                isPasswordLoading = false;
                if (passwordSubmitBtn) {
                    passwordSubmitBtn.disabled = false;
                    passwordSubmitBtn.classList.remove('loading');
                    passwordSubmitBtn.textContent = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                showError('currentPasswordError', 'An error occurred. Please try again.');
                isPasswordLoading = false;
                if (passwordSubmitBtn) {
                    passwordSubmitBtn.disabled = false;
                    passwordSubmitBtn.classList.remove('loading');
                    passwordSubmitBtn.textContent = 'Send OTP Code';
                }
            }
        });
    }

    // Handle Password OTP Verification
    if (verifyPasswordOtpBtn) {
        verifyPasswordOtpBtn.addEventListener('click', async function () {
            const otpCode = otpPasswordCodeInput ? otpPasswordCodeInput.value.trim() : '';

            // Clear previous errors
            const errEl = document.getElementById('otpPasswordError');
            if (errEl) errEl.textContent = '';

            if (!otpCode || otpCode.length !== 6 || isNaN(otpCode)) {
                showError('otpPasswordError', 'Please enter a valid 6-digit OTP code');
                return;
            }

            try {
                verifyPasswordOtpBtn.disabled = true;
                verifyPasswordOtpBtn.classList.add('loading');

                const response = await fetch('/admin/verify-password-otp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || '',
                    },
                    body: JSON.stringify({
                        otp_code: otpCode,
                        new_password: pendingPasswordData.new_password,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    showError('otpPasswordError', data.message || 'OTP verification failed');
                    verifyPasswordOtpBtn.disabled = false;
                    verifyPasswordOtpBtn.classList.remove('loading');
                    return;
                }

                // Success
                if (otpPasswordModal) otpPasswordModal.style.display = 'none';
                changePasswordForm?.reset();
                changePasswordForm?.classList.add('admin-settings__form--hidden');
                togglePasswordBtn?.classList.remove('active');
                pendingPasswordData = null;
                if (otpPasswordCodeInput) otpPasswordCodeInput.value = '';
                verifyPasswordOtpBtn.disabled = false;
                verifyPasswordOtpBtn.classList.remove('loading');
                showSuccessMessage('Password changed successfully!');
            } catch (error) {
                console.error('Error:', error);
                showError('otpPasswordError', 'An error occurred. Please try again.');
                verifyPasswordOtpBtn.disabled = false;
                verifyPasswordOtpBtn.classList.remove('loading');
            }
        });
    }

    // Handle Cancel Password OTP
    if (cancelPasswordOtpBtn) {
        cancelPasswordOtpBtn.addEventListener('click', function () {
            if (otpPasswordModal) otpPasswordModal.style.display = 'none';
            if (otpPasswordCodeInput) otpPasswordCodeInput.value = '';
            const errEl = document.getElementById('otpPasswordError');
            if (errEl) errEl.textContent = '';
            pendingPasswordData = null;
        });
    }

    // ===== EMAIL SECTION =====

    // Toggle email form visibility
    if (toggleEmailBtn) {
        toggleEmailBtn.addEventListener('click', function () {
            changeEmailForm?.classList.toggle('admin-settings__form--hidden');
            toggleEmailBtn.classList.toggle('active');
        });
    }

    // Cancel email form
    if (cancelEmailBtn) {
        cancelEmailBtn.addEventListener('click', function () {
            changeEmailForm?.classList.add('admin-settings__form--hidden');
            toggleEmailBtn?.classList.remove('active');
            changeEmailForm?.reset();
            const errEl = document.getElementById('newEmailError');
            if (errEl) errEl.textContent = '';
        });
    }

    // Handle Change Email Form
    if (changeEmailForm) {
        changeEmailForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const newEmail = document.getElementById('newEmail').value;

            // Clear previous errors
            const errEl = document.getElementById('newEmailError');
            if (errEl) errEl.textContent = '';

            // Validation
            if (!newEmail) {
                showError('newEmailError', 'Email is required');
                return;
            }

            if (!isValidEmail(newEmail)) {
                showError('newEmailError', 'Please enter a valid email address');
                return;
            }

            // Send OTP
            try {
                isEmailLoading = true;
                if (emailSubmitBtn) {
                    emailSubmitBtn.disabled = true;
                    emailSubmitBtn.classList.add('loading');
                }
                const originalText = emailSubmitBtn ? emailSubmitBtn.textContent : 'Send OTP Code';
                if (emailSubmitBtn) emailSubmitBtn.textContent = 'Sending...';

                const response = await fetch('/admin/send-email-otp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || '',
                    },
                    body: JSON.stringify({
                        new_email: newEmail,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    showError('newEmailError', data.message || 'Failed to send OTP');
                    isEmailLoading = false;
                    if (emailSubmitBtn) {
                        emailSubmitBtn.disabled = false;
                        emailSubmitBtn.classList.remove('loading');
                        emailSubmitBtn.textContent = originalText;
                    }
                    return;
                }

                // Store email data for OTP verification
                pendingEmailData = {
                    new_email: newEmail,
                };

                // Show OTP Modal
                if (otpEmailModal) otpEmailModal.style.display = 'flex';
                if (otpEmailCodeInput) otpEmailCodeInput.focus();
                isEmailLoading = false;
                if (emailSubmitBtn) {
                    emailSubmitBtn.disabled = false;
                    emailSubmitBtn.classList.remove('loading');
                    emailSubmitBtn.textContent = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                showError('newEmailError', 'An error occurred. Please try again.');
                isEmailLoading = false;
                if (emailSubmitBtn) {
                    emailSubmitBtn.disabled = false;
                    emailSubmitBtn.classList.remove('loading');
                    emailSubmitBtn.textContent = 'Send OTP Code';
                }
            }
        });
    }

    // Handle Email OTP Verification
    if (verifyEmailOtpBtn) {
        verifyEmailOtpBtn.addEventListener('click', async function () {
            const otpCode = otpEmailCodeInput ? otpEmailCodeInput.value.trim() : '';

            // Clear previous errors
            const errEl = document.getElementById('otpEmailError');
            if (errEl) errEl.textContent = '';

            if (!otpCode || otpCode.length !== 6 || isNaN(otpCode)) {
                showError('otpEmailError', 'Please enter a valid 6-digit OTP code');
                return;
            }

            try {
                verifyEmailOtpBtn.disabled = true;
                verifyEmailOtpBtn.classList.add('loading');

                const response = await fetch('/admin/verify-email-otp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || '',
                    },
                    body: JSON.stringify({
                        otp_code: otpCode,
                        new_email: pendingEmailData.new_email,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    showError('otpEmailError', data.message || 'OTP verification failed');
                    verifyEmailOtpBtn.disabled = false;
                    verifyEmailOtpBtn.classList.remove('loading');
                    return;
                }

                // Success
                if (otpEmailModal) otpEmailModal.style.display = 'none';
                const curDisplay = document.getElementById('currentEmailDisplay');
                if (curDisplay) curDisplay.textContent = pendingEmailData.new_email;
                changeEmailForm?.reset();
                changeEmailForm?.classList.add('admin-settings__form--hidden');
                toggleEmailBtn?.classList.remove('active');
                pendingEmailData = null;
                if (otpEmailCodeInput) otpEmailCodeInput.value = '';
                verifyEmailOtpBtn.disabled = false;
                verifyEmailOtpBtn.classList.remove('loading');
                showSuccessMessage('Email changed successfully!');
            } catch (error) {
                console.error('Error:', error);
                showError('otpEmailError', 'An error occurred. Please try again.');
                verifyEmailOtpBtn.disabled = false;
                verifyEmailOtpBtn.classList.remove('loading');
            }
        });
    }

    // Handle Cancel Email OTP
    if (cancelEmailOtpBtn) {
        cancelEmailOtpBtn.addEventListener('click', function () {
            if (otpEmailModal) otpEmailModal.style.display = 'none';
            if (otpEmailCodeInput) otpEmailCodeInput.value = '';
            const errEl = document.getElementById('otpEmailError');
            if (errEl) errEl.textContent = '';
            pendingEmailData = null;
        });
    }

    // ===== PARK RULES MANAGEMENT SECTION =====
    const viewParkRuleModal = document.getElementById('viewParkRuleModal');
    const viewRuleIdBadge = document.getElementById('viewRuleIdBadge');
    const viewRuleModalTitle = document.getElementById('viewRuleModalTitle');
    const viewRuleModalUpdated = document.getElementById('viewRuleModalUpdated');
    const viewRuleModalDesc = document.getElementById('viewRuleModalDesc');
    const viewModalEditBtn = document.getElementById('viewModalEditBtn');
    const viewModalDeleteBtn = document.getElementById('viewModalDeleteBtn');
    const closeViewRuleModalBtn = document.getElementById('closeViewRuleModalBtn');
    const closeViewRuleModalXBtn = document.getElementById('closeViewRuleModalXBtn');

    const parkRuleModal = document.getElementById('parkRuleModal');
    const parkRuleModalTitle = document.getElementById('parkRuleModalTitle');
    const parkRuleModalSubtitle = document.getElementById('parkRuleModalSubtitle');
    const addRuleBtn = document.getElementById('addRuleBtn');
    const cancelRuleModalBtn = document.getElementById('cancelRuleModalBtn');
    const parkRuleForm = document.getElementById('parkRuleForm');
    const ruleIdInput = document.getElementById('ruleIdInput');
    const ruleNameInput = document.getElementById('ruleNameInput');
    const ruleDescInput = document.getElementById('ruleDescInput');
    const saveRuleSubmitBtn = document.getElementById('saveRuleSubmitBtn');
    const parkRulesGrid = document.getElementById('parkRulesGrid');
    const emptyParkRulesState = document.getElementById('emptyParkRulesState');

    const deleteParkRuleModal = document.getElementById('deleteParkRuleModal');
    const deleteRuleConfirmText = document.getElementById('deleteRuleConfirmText');
    const deleteRuleIdInput = document.getElementById('deleteRuleIdInput');
    const confirmDeleteRuleBtn = document.getElementById('confirmDeleteRuleBtn');
    const cancelDeleteRuleBtn = document.getElementById('cancelDeleteRuleBtn');

    let activeRuleData = null;

    // Open Rule Details / View Modal on Click
    parkRulesGrid?.addEventListener('click', (e) => {
        const item = e.target.closest('.park-rule-item');
        if (!item) return;

        const ruleId = item.getAttribute('data-rule-id');
        const ruleName = item.getAttribute('data-rule-name') || '';
        const ruleDesc = item.getAttribute('data-rule-desc') || '';
        const ruleUpdated = item.getAttribute('data-rule-updated') || 'Recently';

        activeRuleData = {
            id: ruleId,
            name: ruleName,
            description: ruleDesc,
            updated: ruleUpdated,
        };

        if (viewRuleIdBadge) viewRuleIdBadge.textContent = `#${ruleId}`;
        if (viewRuleModalTitle) viewRuleModalTitle.textContent = ruleName;
        if (viewRuleModalUpdated) viewRuleModalUpdated.textContent = `Updated ${ruleUpdated}`;
        if (viewRuleModalDesc) viewRuleModalDesc.textContent = ruleDesc;

        if (viewParkRuleModal) viewParkRuleModal.style.display = 'flex';
    });

    // Close View Modal
    const closeViewModal = () => {
        if (viewParkRuleModal) viewParkRuleModal.style.display = 'none';
    };

    closeViewRuleModalBtn?.addEventListener('click', closeViewModal);
    closeViewRuleModalXBtn?.addEventListener('click', closeViewModal);
    viewParkRuleModal?.addEventListener('click', (e) => {
        if (e.target === viewParkRuleModal) closeViewModal();
    });

    // Switch from View Modal to Edit Modal
    viewModalEditBtn?.addEventListener('click', () => {
        if (!activeRuleData) return;
        closeViewModal();

        if (ruleIdInput) ruleIdInput.value = activeRuleData.id;
        if (ruleNameInput) ruleNameInput.value = activeRuleData.name;
        if (ruleDescInput) ruleDescInput.value = activeRuleData.description;
        if (parkRuleModalTitle) parkRuleModalTitle.textContent = 'Edit Park Rule';
        if (parkRuleModalSubtitle) parkRuleModalSubtitle.textContent = `Update the configuration for Rule #${activeRuleData.id}.`;
        clearErrors(['ruleNameError', 'ruleDescError']);

        if (parkRuleModal) parkRuleModal.style.display = 'flex';
        ruleNameInput?.focus();
    });

    // Switch from View Modal to Delete Modal
    viewModalDeleteBtn?.addEventListener('click', () => {
        if (!activeRuleData) return;
        closeViewModal();

        if (deleteRuleIdInput) deleteRuleIdInput.value = activeRuleData.id;
        if (deleteRuleConfirmText) deleteRuleConfirmText.textContent = `Are you sure you want to delete "${activeRuleData.name}" (Rule #${activeRuleData.id})? This action cannot be undone.`;

        if (deleteParkRuleModal) deleteParkRuleModal.style.display = 'flex';
    });

    // Open Add Rule Modal
    addRuleBtn?.addEventListener('click', () => {
        if (!parkRuleModal) return;
        parkRuleForm?.reset();
        if (ruleIdInput) ruleIdInput.value = '';
        if (parkRuleModalTitle) parkRuleModalTitle.textContent = 'Add Park Rule';
        if (parkRuleModalSubtitle) parkRuleModalSubtitle.textContent = 'Create a new operational rule or guideline for Hinaguan Nature Park.';
        clearErrors(['ruleNameError', 'ruleDescError']);
        parkRuleModal.style.display = 'flex';
        ruleNameInput?.focus();
    });

    // Close Add / Edit Rule Modal
    cancelRuleModalBtn?.addEventListener('click', () => {
        if (parkRuleModal) parkRuleModal.style.display = 'none';
        parkRuleForm?.reset();
        clearErrors(['ruleNameError', 'ruleDescError']);
    });

    parkRuleModal?.addEventListener('click', (e) => {
        if (e.target === parkRuleModal) {
            parkRuleModal.style.display = 'none';
            parkRuleForm?.reset();
        }
    });

    // Handle Add / Edit Rule Form Submission
    parkRuleForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErrors(['ruleNameError', 'ruleDescError']);

        const ruleId = ruleIdInput ? ruleIdInput.value.trim() : '';
        const ruleName = ruleNameInput ? ruleNameInput.value.trim() : '';
        const ruleDesc = ruleDescInput ? ruleDescInput.value.trim() : '';

        if (!ruleName) {
            showError('ruleNameError', 'Rule name is required.');
            return;
        }
        if (!ruleDesc) {
            showError('ruleDescError', 'Rule description is required.');
            return;
        }

        const isEditing = Boolean(ruleId);
        const url = isEditing ? `/admin/settings/rules/${ruleId}` : '/admin/settings/rules';
        const method = isEditing ? 'PUT' : 'POST';

        if (saveRuleSubmitBtn) {
            saveRuleSubmitBtn.disabled = true;
            saveRuleSubmitBtn.classList.add('loading');
        }

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    rule_name: ruleName,
                    rule_descriptions: ruleDesc,
                }),
            });

            const data = await response.json();

            if (response.ok) {
                if (parkRuleModal) parkRuleModal.style.display = 'none';
                parkRuleForm.reset();
                showSuccessMessage(data.message || (isEditing ? 'Park rule updated successfully!' : 'Park rule created successfully!'));

                if (isEditing) {
                    // Update existing item in DOM
                    const card = document.getElementById(`parkRuleCard_${ruleId}`);
                    if (card) {
                        const nameEl = card.querySelector('.rule-name-display');
                        if (nameEl) nameEl.textContent = data.rule.rule_name;
                        card.setAttribute('data-rule-name', data.rule.rule_name);
                        card.setAttribute('data-rule-desc', data.rule.rule_descriptions);
                        card.setAttribute('data-rule-updated', 'Just now');
                    }
                } else {
                    // Create new compact item in DOM
                    if (emptyParkRulesState) emptyParkRulesState.remove();
                    const newRule = data.rule;
                    const newItem = document.createElement('div');
                    newItem.className = 'park-rule-item group relative flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[rgba(13,44,29,0.1)] bg-white px-4 py-3.5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--hp-green)] hover:shadow-md dark:border-white/10 dark:bg-white/5 dark:hover:border-[var(--hp-gold)]';
                    newItem.id = `parkRuleCard_${newRule.id}`;
                    newItem.setAttribute('data-rule-id', newRule.id);
                    newItem.setAttribute('data-rule-name', newRule.rule_name);
                    newItem.setAttribute('data-rule-desc', newRule.rule_descriptions);
                    newItem.setAttribute('data-rule-updated', 'Just now');
                    newItem.innerHTML = `
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[rgba(26,58,31,0.08)] text-xs font-bold text-[var(--hp-green)] transition-colors group-hover:bg-[var(--hp-green)] group-hover:text-white dark:bg-[rgba(200,164,93,0.15)] dark:text-[var(--hp-gold)] dark:group-hover:bg-[var(--hp-gold)] dark:group-hover:text-black">
                                #${newRule.id}
                            </span>
                            <span class="truncate text-sm font-semibold text-[var(--hp-text)] rule-name-display">
                                ${escapeHtml(newRule.rule_name)}
                            </span>
                        </div>
                        <div class="flex items-center text-[var(--hp-text-muted)] group-hover:text-[var(--hp-green)] dark:group-hover:text-[var(--hp-gold)] transition-colors">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    `;
                    parkRulesGrid?.prepend(newItem);
                }
            } else {
                if (data.errors) {
                    if (data.errors.rule_name) showError('ruleNameError', data.errors.rule_name[0]);
                    if (data.errors.rule_descriptions) showError('ruleDescError', data.errors.rule_descriptions[0]);
                } else {
                    showError('ruleNameError', data.message || 'Failed to save park rule.');
                }
            }
        } catch (error) {
            console.error('Error saving park rule:', error);
            showError('ruleNameError', 'Network error occurred while saving.');
        } finally {
            if (saveRuleSubmitBtn) {
                saveRuleSubmitBtn.disabled = false;
                saveRuleSubmitBtn.classList.remove('loading');
            }
        }
    });

    // Cancel Delete Modal
    cancelDeleteRuleBtn?.addEventListener('click', () => {
        if (deleteParkRuleModal) deleteParkRuleModal.style.display = 'none';
        if (deleteRuleIdInput) deleteRuleIdInput.value = '';
    });

    deleteParkRuleModal?.addEventListener('click', (e) => {
        if (e.target === deleteParkRuleModal) {
            deleteParkRuleModal.style.display = 'none';
        }
    });

    // Confirm Delete Rule
    confirmDeleteRuleBtn?.addEventListener('click', async () => {
        const ruleId = deleteRuleIdInput ? deleteRuleIdInput.value.trim() : '';
        if (!ruleId) return;

        if (confirmDeleteRuleBtn) {
            confirmDeleteRuleBtn.disabled = true;
            confirmDeleteRuleBtn.classList.add('loading');
        }

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch(`/admin/settings/rules/${ruleId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json();

            if (response.ok) {
                if (deleteParkRuleModal) deleteParkRuleModal.style.display = 'none';
                const card = document.getElementById(`parkRuleCard_${ruleId}`);
                if (card) {
                    card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => card.remove(), 300);
                }
                showSuccessMessage(data.message || 'Park rule deleted successfully!');
            } else {
                alert(data.message || 'Failed to delete park rule.');
            }
        } catch (error) {
            console.error('Error deleting park rule:', error);
            alert('A network error occurred while deleting.');
        } finally {
            if (confirmDeleteRuleBtn) {
                confirmDeleteRuleBtn.disabled = false;
                confirmDeleteRuleBtn.classList.remove('loading');
            }
        }
    });

    // ===== HELPER FUNCTIONS =====

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showError(elementId, message) {
        const errorElement = document.getElementById(elementId);
        if (errorElement) {
            errorElement.textContent = message;
        }
    }

    function clearErrors(errorIds) {
        errorIds.forEach((id) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = '';
            }
        });
    }

    // ===== PARK EVENTS MANAGEMENT SECTION =====
    const viewParkEventModal = document.getElementById('viewParkEventModal');
    const viewEventIdBadge = document.getElementById('viewEventIdBadge');
    const viewEventModalTitle = document.getElementById('viewEventModalTitle');
    const viewEventModalMeta = document.getElementById('viewEventModalMeta');
    const viewEventModalDateDisplay = document.getElementById('viewEventModalDateDisplay');
    const viewEventModalDayDisplay = document.getElementById('viewEventModalDayDisplay');
    const viewEventModalTimeDisplay = document.getElementById('viewEventModalTimeDisplay');
    const viewEventModalDesc = document.getElementById('viewEventModalDesc');
    const viewEventModalEditBtn = document.getElementById('viewEventModalEditBtn');
    const viewEventModalDeleteBtn = document.getElementById('viewEventModalDeleteBtn');
    const closeViewEventModalBtn = document.getElementById('closeViewEventModalBtn');
    const closeViewEventModalXBtn = document.getElementById('closeViewEventModalXBtn');

    const parkEventModal = document.getElementById('parkEventModal');
    const parkEventModalTitle = document.getElementById('parkEventModalTitle');
    const parkEventModalSubtitle = document.getElementById('parkEventModalSubtitle');
    const addEventBtn = document.getElementById('addEventBtn');
    const cancelEventModalBtn = document.getElementById('cancelEventModalBtn');
    const closeAddEventModalXBtn = document.getElementById('closeAddEventModalXBtn');
    const parkEventForm = document.getElementById('parkEventForm');
    const eventIdInput = document.getElementById('eventIdInput');
    const eventTitleInput = document.getElementById('eventTitleInput');
    const eventDateInput = document.getElementById('eventDateInput');
    const eventDayInput = document.getElementById('eventDayInput');
    const eventTimeInput = document.getElementById('eventTimeInput');
    const eventDescInput = document.getElementById('eventDescInput');
    const saveEventSubmitBtn = document.getElementById('saveEventSubmitBtn');

    const deleteParkEventModal = document.getElementById('deleteParkEventModal');
    const deleteEventConfirmText = document.getElementById('deleteEventConfirmText');
    const deleteEventIdInput = document.getElementById('deleteEventIdInput');
    const confirmDeleteEventBtn = document.getElementById('confirmDeleteEventBtn');
    const cancelDeleteEventBtn = document.getElementById('cancelDeleteEventBtn');

    const backToMenuFromEvents = document.getElementById('backToMenuFromEvents');
    const parkEventsGrid = document.getElementById('parkEventsGrid');

    let currentViewingEventData = null;

    // Helper: Calculate Day of Week from Date String
    function getDayOfWeek(dateString) {
        if (!dateString) return '';
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const dateObj = new Date(dateString + 'T00:00:00');
        return days[dateObj.getDay()] || '';
    }

    // Auto-fill day when date changes in Add/Edit modal
    if (eventDateInput && eventDayInput) {
        eventDateInput.addEventListener('change', function () {
            if (this.value) {
                eventDayInput.value = getDayOfWeek(this.value);
            }
        });
    }

    // Back to Menu from Event Settings
    if (backToMenuFromEvents) {
        backToMenuFromEvents.addEventListener('click', function () {
            const eventSettingsView = document.getElementById('event-settings');
            const menuCardsGrid = document.getElementById('menuCardsGrid');
            if (eventSettingsView) eventSettingsView.classList.add('admin-settings__content--hidden');
            if (menuCardsGrid) menuCardsGrid.classList.remove('admin-settings__menu-grid--hidden');
        });
    }

    // Open View Event Modal when clicking an event card
    function attachEventCardListeners() {
        document.querySelectorAll('.park-event-item').forEach(card => {
            card.onclick = function () {
                const eventId = this.getAttribute('data-event-id');
                const title = this.getAttribute('data-event-title');
                const date = this.getAttribute('data-event-date');
                const day = this.getAttribute('data-event-day') || getDayOfWeek(date);
                const time = this.getAttribute('data-event-time') || '';
                const desc = this.getAttribute('data-event-desc');
                const updated = this.getAttribute('data-event-updated');

                currentViewingEventData = { id: eventId, title, date, day, time, desc, updated };

                if (viewEventIdBadge) viewEventIdBadge.textContent = `#${eventId}`;
                if (viewEventModalTitle) viewEventModalTitle.textContent = title;
                if (viewEventModalMeta) viewEventModalMeta.textContent = `${date} · ${day}${time ? ' · ' + time : ''}`;
                if (viewEventModalDateDisplay) viewEventModalDateDisplay.textContent = date || 'Not set';
                if (viewEventModalDayDisplay) viewEventModalDayDisplay.textContent = day || 'Not set';
                if (viewEventModalTimeDisplay) viewEventModalTimeDisplay.textContent = time || 'Not specified';
                if (viewEventModalDesc) viewEventModalDesc.textContent = desc;

                if (viewParkEventModal) viewParkEventModal.style.display = 'flex';
            };
        });
    }
    attachEventCardListeners();

    // Close View Event Modal
    function closeViewEventModal() {
        if (viewParkEventModal) viewParkEventModal.style.display = 'none';
        currentViewingEventData = null;
    }
    if (closeViewEventModalBtn) closeViewEventModalBtn.addEventListener('click', closeViewEventModal);
    if (closeViewEventModalXBtn) closeViewEventModalXBtn.addEventListener('click', closeViewEventModal);

    // Open Add Event Modal
    if (addEventBtn) {
        addEventBtn.addEventListener('click', function () {
            if (parkEventForm) parkEventForm.reset();
            if (eventIdInput) eventIdInput.value = '';
            if (parkEventModalTitle) parkEventModalTitle.textContent = 'Add Park Event';
            if (parkEventModalSubtitle) parkEventModalSubtitle.textContent = 'Schedule a new event for Hinaguan Nature Park.';
            if (saveEventSubmitBtn) saveEventSubmitBtn.textContent = 'Save Event';
            clearEventErrors();
            if (parkEventModal) parkEventModal.style.display = 'flex';
        });
    }

    // Open Edit Event Modal from View Modal
    if (viewEventModalEditBtn) {
        viewEventModalEditBtn.addEventListener('click', function () {
            if (!currentViewingEventData) return;
            closeViewEventModal();

            clearEventErrors();
            if (eventIdInput) eventIdInput.value = currentViewingEventData.id;
            if (eventTitleInput) eventTitleInput.value = currentViewingEventData.title;
            if (eventDateInput) eventDateInput.value = currentViewingEventData.date;
            if (eventDayInput) eventDayInput.value = currentViewingEventData.day || getDayOfWeek(currentViewingEventData.date);
            if (eventTimeInput) eventTimeInput.value = currentViewingEventData.time || '';
            if (eventDescInput) eventDescInput.value = currentViewingEventData.desc;

            if (parkEventModalTitle) parkEventModalTitle.textContent = 'Edit Park Event';
            if (parkEventModalSubtitle) parkEventModalSubtitle.textContent = 'Update details for this scheduled park event.';
            if (saveEventSubmitBtn) saveEventSubmitBtn.textContent = 'Update Event';

            if (parkEventModal) parkEventModal.style.display = 'flex';
        });
    }

    // Close Add/Edit Event Modal
    function closeAddEditEventModal() {
        if (parkEventModal) parkEventModal.style.display = 'none';
        if (parkEventForm) parkEventForm.reset();
        clearEventErrors();
    }
    if (cancelEventModalBtn) cancelEventModalBtn.addEventListener('click', closeAddEditEventModal);
    if (closeAddEventModalXBtn) closeAddEventModalXBtn.addEventListener('click', closeAddEditEventModal);

    function clearEventErrors() {
        ['eventTitleError', 'eventDateError', 'eventDayError', 'eventTimeError', 'eventDescError'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = '';
        });
    }

    // Submit Add/Edit Event Form
    if (parkEventForm) {
        parkEventForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            clearEventErrors();

            const isEdit = !!eventIdInput.value;
            const eventId = eventIdInput.value;
            const url = isEdit ? `/admin/settings/events/${eventId}` : '/admin/settings/events';
            const method = isEdit ? 'PUT' : 'POST';

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                document.querySelector('input[name="_token"]')?.value;

            const payload = {
                title: eventTitleInput.value.trim(),
                date: eventDateInput.value,
                day: eventDayInput.value.trim() || getDayOfWeek(eventDateInput.value),
                time: eventTimeInput ? eventTimeInput.value.trim() : '',
                event: eventDescInput.value.trim(),
            };

            if (!payload.title) {
                const el = document.getElementById('eventTitleError');
                if (el) el.textContent = 'Event title is required.';
                return;
            }
            if (!payload.date) {
                const el = document.getElementById('eventDateError');
                if (el) el.textContent = 'Event date is required.';
                return;
            }
            if (!payload.event) {
                const el = document.getElementById('eventDescError');
                if (el) el.textContent = 'Event description is required.';
                return;
            }

            if (saveEventSubmitBtn) {
                saveEventSubmitBtn.disabled = true;
                saveEventSubmitBtn.classList.add('loading');
            }

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (!response.ok) {
                    if (data.errors) {
                        if (data.errors.title) document.getElementById('eventTitleError').textContent = data.errors.title[0];
                        if (data.errors.date) document.getElementById('eventDateError').textContent = data.errors.date[0];
                        if (data.errors.time) document.getElementById('eventTimeError').textContent = data.errors.time[0];
                        if (data.errors.event) document.getElementById('eventDescError').textContent = data.errors.event[0];
                    } else {
                        showSuccessMessage(data.message || 'Failed to save event.');
                    }
                    return;
                }

                closeAddEditEventModal();
                showSuccessMessage(isEdit ? 'Event updated successfully!' : 'Event created successfully!');

                const event = data.event;
                const monthName = new Date(event.date + 'T00:00:00').toLocaleString('en-US', { month: 'short' }).toUpperCase();
                const dayNum = new Date(event.date + 'T00:00:00').getDate();
                const timeText = event.time ? ` · ${event.time}` : '';

                if (isEdit) {
                    const card = document.getElementById(`parkEventCard_${event.id}`);
                    if (card) {
                        card.setAttribute('data-event-title', event.title);
                        card.setAttribute('data-event-date', event.date);
                        card.setAttribute('data-event-day', event.day);
                        card.setAttribute('data-event-time', event.time || '');
                        card.setAttribute('data-event-desc', event.event);
                        card.setAttribute('data-event-updated', event.updated_at);

                        const titleEl = card.querySelector('.event-title-display');
                        if (titleEl) titleEl.textContent = event.title;

                        const metaEl = card.querySelector('.min-w-0 span:last-child');
                        if (metaEl) metaEl.textContent = `${event.day}${timeText} · ${event.event.substring(0, 35)}`;

                        const monthEl = card.querySelector('.flex-col span:first-child');
                        if (monthEl) monthEl.textContent = monthName;

                        const dayNumEl = card.querySelector('.flex-col span:last-child');
                        if (dayNumEl) dayNumEl.textContent = dayNum;
                    }
                } else {
                    const emptyState = document.getElementById('emptyParkEventsState');
                    if (emptyState) emptyState.remove();

                    const newCard = document.createElement('div');
                    newCard.className = 'park-event-item group relative flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-[rgba(13,44,29,0.1)] bg-white px-4 py-3.5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--hp-green)] hover:shadow-md dark:border-white/10 dark:bg-white/5 dark:hover:border-[var(--hp-gold)]';
                    newCard.id = `parkEventCard_${event.id}`;
                    newCard.setAttribute('data-event-id', event.id);
                    newCard.setAttribute('data-event-title', event.title);
                    newCard.setAttribute('data-event-date', event.date);
                    newCard.setAttribute('data-event-day', event.day);
                    newCard.setAttribute('data-event-time', event.time || '');
                    newCard.setAttribute('data-event-desc', event.event);
                    newCard.setAttribute('data-event-updated', event.updated_at);

                    newCard.innerHTML = `
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex flex-col items-center justify-center shrink-0 w-12 py-1 rounded-lg bg-[rgba(26,58,31,0.08)] text-[var(--hp-green)] transition-colors group-hover:bg-[var(--hp-green)] group-hover:text-white dark:bg-[rgba(200,164,93,0.15)] dark:text-[var(--hp-gold)] dark:group-hover:bg-[var(--hp-gold)] dark:group-hover:text-black">
                                <span class="text-[0.65rem] font-bold uppercase tracking-wider">${monthName}</span>
                                <span class="text-base font-extrabold leading-none">${dayNum}</span>
                            </div>
                            <div class="min-w-0">
                                <span class="truncate block text-sm font-semibold text-[var(--hp-text)] event-title-display">${event.title}</span>
                                <span class="text-xs text-[var(--hp-text-muted)] block truncate">${event.day}${timeText} · ${event.event.substring(0, 35)}</span>
                            </div>
                        </div>
                        <div class="flex items-center text-[var(--hp-text-muted)] group-hover:text-[var(--hp-green)] dark:group-hover:text-[var(--hp-gold)] transition-colors">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    `;
                    if (parkEventsGrid) parkEventsGrid.prepend(newCard);
                    attachEventCardListeners();
                }
            } catch (err) {
                console.error('Error saving event:', err);
                showSuccessMessage('An unexpected error occurred.');
            } finally {
                if (saveEventSubmitBtn) {
                    saveEventSubmitBtn.disabled = false;
                    saveEventSubmitBtn.classList.remove('loading');
                }
            }
        });
    }

    // Delete Event Logic
    if (viewEventModalDeleteBtn) {
        viewEventModalDeleteBtn.addEventListener('click', function () {
            if (!currentViewingEventData) return;
            const eventId = currentViewingEventData.id;
            const title = currentViewingEventData.title;

            closeViewEventModal();

            if (deleteEventIdInput) deleteEventIdInput.value = eventId;
            if (deleteEventConfirmText) deleteEventConfirmText.textContent = `Are you sure you want to delete "${title}"? This action cannot be undone.`;
            if (deleteParkEventModal) deleteParkEventModal.style.display = 'flex';
        });
    }

    if (cancelDeleteEventBtn) {
        cancelDeleteEventBtn.addEventListener('click', function () {
            if (deleteParkEventModal) deleteParkEventModal.style.display = 'none';
            if (deleteEventIdInput) deleteEventIdInput.value = '';
        });
    }

    if (confirmDeleteEventBtn) {
        confirmDeleteEventBtn.addEventListener('click', async function () {
            const eventId = deleteEventIdInput ? deleteEventIdInput.value : '';
            if (!eventId) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                document.querySelector('input[name="_token"]')?.value;

            confirmDeleteEventBtn.disabled = true;

            try {
                const res = await fetch(`/admin/settings/events/${eventId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                if (res.ok) {
                    if (deleteParkEventModal) deleteParkEventModal.style.display = 'none';
                    const card = document.getElementById(`parkEventCard_${eventId}`);
                    if (card) card.remove();

                    if (parkEventsGrid && parkEventsGrid.children.length === 0) {
                        parkEventsGrid.innerHTML = `
                            <div class="col-span-full py-10 text-center text-sm text-[var(--hp-text-muted)]" id="emptyParkEventsState">
                                No park events created yet. Click "Add Event" above to create one.
                            </div>
                        `;
                    }
                    showSuccessMessage('Park event deleted successfully.');
                } else {
                    showSuccessMessage('Failed to delete park event.');
                }
            } catch (err) {
                console.error('Delete error:', err);
                showSuccessMessage('An error occurred while deleting.');
            } finally {
                confirmDeleteEventBtn.disabled = false;
            }
        });
    }

    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    function showSuccessMessage(message) {
        const successDiv = document.createElement('div');
        successDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #10b981;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.35);
            z-index: 2000;
            animation: slideIn 0.3s ease;
            font-weight: 600;
            font-size: 0.9rem;
        `;
        successDiv.textContent = message;
        document.body.appendChild(successDiv);

        setTimeout(() => {
            successDiv.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                successDiv.remove();
            }, 300);
        }, 3000);
    }

    // Allow OTP input only for numbers
    if (otpPasswordCodeInput) {
        otpPasswordCodeInput.addEventListener('keypress', function (e) {
            if (!/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });
    }

    if (otpEmailCodeInput) {
        otpEmailCodeInput.addEventListener('keypress', function (e) {
            if (!/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.AppPage['admin_settings']());
} else {
    window.AppPage['admin_settings']();
}