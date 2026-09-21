/**
 * Admin SMS Announcements & Broadcasts
 * 3-Step Wizard Flow with Recipients Customizer Modal
 * Hinaguan Nature Park
 */

document.addEventListener('DOMContentLoaded', () => {
    // CSRF Token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // =========================================================================
    // 1. WIZARD MODAL CONTROLS & STEPPER (Next/Back only — no tab clicking)
    // =========================================================================
    const broadcastWizardModal = document.getElementById('broadcastWizardModal');
    const openWizardModalBtn = document.getElementById('openWizardModalBtn');
    const openWizardTriggers = document.querySelectorAll('.open-wizard-trigger');
    const closeWizardBtns = document.querySelectorAll('.close-wizard-btn');

    const wizardStepTabs = document.querySelectorAll('.wizard-step-tab');
    const wizardStepPages = document.querySelectorAll('.wizard-step-page');
    const stepDescriptionText = document.getElementById('stepDescriptionText');
    const wizardBackBtn = document.getElementById('wizardBackBtn');
    const wizardNextBtn = document.getElementById('wizardNextBtn');

    let currentStep = 1;

    function openWizard(step = 1) {
        if (!broadcastWizardModal) return;
        broadcastWizardModal.classList.remove('hidden');
        broadcastWizardModal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        goToStep(step);
    }

    function closeWizard() {
        if (!broadcastWizardModal) return;
        broadcastWizardModal.classList.add('hidden');
        broadcastWizardModal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    if (openWizardModalBtn) {
        openWizardModalBtn.addEventListener('click', () => openWizard(1));
    }

    openWizardTriggers.forEach(btn => {
        btn.addEventListener('click', () => openWizard(1));
    });

    closeWizardBtns.forEach(btn => {
        btn.addEventListener('click', closeWizard);
    });

    broadcastWizardModal?.addEventListener('click', (e) => {
        if (e.target === broadcastWizardModal) closeWizard();
    });

    function goToStep(step) {
        if (step < 1 || step > 3) return;

        // Validate before advancing forward
        if (step > currentStep) {
            if (currentStep === 1 && selectedReservations.size === 0) {
                alert('Please select at least one reservation / guest recipient before proceeding.');
                return;
            }
            if (currentStep === 2) {
                const msg = (smsMessageTextarea?.value || '').trim();
                if (msg.length < 3) {
                    alert('Please compose an SMS message of at least 3 characters before proceeding.');
                    smsMessageTextarea?.focus();
                    return;
                }
            }
        }

        currentStep = step;

        // Show/hide step pages
        wizardStepPages.forEach(page => page.classList.add('hidden'));
        const activePage = document.getElementById(`wizardStep${step}`);
        if (activePage) activePage.classList.remove('hidden');

        // Update tab indicators (display-only, no click)
        wizardStepTabs.forEach(tab => {
            const tabStep = parseInt(tab.getAttribute('data-step'), 10);
            const badge = tab.querySelector('span:first-child');
            if (tabStep === step) {
                tab.className = tab.className.replace(/border-\[var\(--border\)\] bg-gray-50 dark:bg-neutral-900\/40 text-\[var\(--ink-muted\)\] font-medium/g, '');
                tab.className = tab.className.replace(/border-emerald-300 dark:border-emerald-800 bg-transparent text-emerald-700 dark:text-emerald-400 font-medium/g, '');
                tab.className = tab.className.replace(/border-emerald-500\/40 bg-emerald-50 dark:bg-emerald-950\/50 text-emerald-800 dark:text-emerald-200 font-bold/g, '');
                tab.classList.add('border-emerald-500/40', 'bg-emerald-50', 'dark:bg-emerald-950/50', 'text-emerald-800', 'dark:text-emerald-200', 'font-bold');
                tab.classList.remove('border-[var(--border)]', 'bg-gray-50', 'dark:bg-neutral-900/40', 'text-[var(--ink-muted)]', 'font-medium', 'border-emerald-300', 'dark:border-emerald-800', 'bg-transparent', 'text-emerald-700', 'dark:text-emerald-400');
                if (badge) {
                    badge.className = 'flex h-5 w-5 items-center justify-center rounded-full bg-emerald-600 text-white text-[11px]';
                    badge.textContent = tabStep;
                }
            } else if (tabStep < step) {
                tab.classList.remove('border-emerald-500/40', 'bg-emerald-50', 'dark:bg-emerald-950/50', 'text-emerald-800', 'dark:text-emerald-200', 'font-bold', 'border-[var(--border)]', 'bg-gray-50', 'dark:bg-neutral-900/40', 'text-[var(--ink-muted)]', 'font-medium');
                tab.classList.add('border-emerald-300', 'dark:border-emerald-800', 'bg-transparent', 'text-emerald-700', 'dark:text-emerald-400', 'font-medium');
                if (badge) {
                    badge.className = 'flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 text-[11px]';
                    badge.innerHTML = '<i class="bi bi-check text-xs"></i>';
                }
            } else {
                tab.classList.remove('border-emerald-500/40', 'bg-emerald-50', 'dark:bg-emerald-950/50', 'text-emerald-800', 'dark:text-emerald-200', 'font-bold', 'border-emerald-300', 'dark:border-emerald-800', 'bg-transparent', 'text-emerald-700', 'dark:text-emerald-400');
                tab.classList.add('border-[var(--border)]', 'bg-gray-50', 'dark:bg-neutral-900/40', 'text-[var(--ink-muted)]', 'font-medium');
                if (badge) {
                    badge.className = 'flex h-5 w-5 items-center justify-center rounded-full bg-gray-300 dark:bg-neutral-700 text-[var(--ink)] text-[11px]';
                    badge.textContent = tabStep;
                }
            }
        });

        // Update description text and footer buttons
        if (step === 1) {
            if (stepDescriptionText) stepDescriptionText.textContent = 'Step 1: Pick audience group & fine-tune reservations';
            if (wizardBackBtn) wizardBackBtn.classList.add('hidden');
            if (wizardNextBtn) {
                wizardNextBtn.innerHTML = '<span>Next: Compose Message</span> <i class="bi bi-arrow-right"></i>';
                wizardNextBtn.className = 'flex items-center gap-2 px-5 py-2 text-xs font-bold rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-md hover:from-emerald-700 hover:to-teal-800 cursor-pointer';
            }
            updateStep1SummaryCard();
        } else if (step === 2) {
            if (stepDescriptionText) stepDescriptionText.textContent = 'Step 2: Compose SMS message with live smartphone preview';
            if (wizardBackBtn) wizardBackBtn.classList.remove('hidden');
            if (wizardNextBtn) {
                wizardNextBtn.innerHTML = '<span>Next: Review & Confirm</span> <i class="bi bi-arrow-right"></i>';
                wizardNextBtn.className = 'flex items-center gap-2 px-5 py-2 text-xs font-bold rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-md hover:from-emerald-700 hover:to-teal-800 cursor-pointer';
            }
            updateCharCounterAndPreview();
        } else if (step === 3) {
            if (stepDescriptionText) stepDescriptionText.textContent = 'Step 3: Final review and instant SMS dispatch';
            if (wizardBackBtn) wizardBackBtn.classList.remove('hidden');
            if (wizardNextBtn) {
                wizardNextBtn.innerHTML = '<i class="bi bi-send-fill"></i> <span>Broadcast SMS Now</span>';
                wizardNextBtn.className = 'flex items-center gap-2 px-6 py-2 text-xs font-bold rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white shadow-lg cursor-pointer';
            }
            populateReviewStep();
        }
    }

    wizardBackBtn?.addEventListener('click', () => {
        if (currentStep > 1) goToStep(currentStep - 1);
    });

    wizardNextBtn?.addEventListener('click', () => {
        if (currentStep === 1) goToStep(2);
        else if (currentStep === 2) goToStep(3);
        else if (currentStep === 3) dispatchSmsBroadcast();
    });

    // No tab click navigation — tabs are pointer-events-none in HTML

    // =========================================================================
    // 2. STEP 1: AUDIENCE PRESET CARDS + SUMMARY CARD
    // =========================================================================
    const audienceCards = document.querySelectorAll('.audience-card');
    const selectedRatioDisplay = document.getElementById('selectedRatioDisplay');
    const selectedTargetGroupLabel = document.getElementById('selectedTargetGroupLabel');
    const selectionStatusBadge = document.getElementById('selectionStatusBadge');

    let currentAudience = 'active';
    let selectedReservations = new Map(); // id -> { id, guest, phone, displayPhone }

    // Check if a row matches the given audience key
    function rowMatchesAudience(row, audienceKey) {
        const isActive = row.getAttribute('data-active') === '1';
        const status = row.getAttribute('data-status') || '';
        if (audienceKey === 'active') return isActive;
        if (audienceKey === 'confirmed') return (status === 'Confirmed');
        if (audienceKey === 'pending') return (status === 'Pending');
        if (audienceKey === 'checked_out') return (status === 'Checked Out');
        if (audienceKey === 'all') return true;
        return true; // 'custom' shows all rows
    }

    // Compute total rows matching an audience key from the customizer table
    function getAudienceMatchCount(audienceKey) {
        const rows = modalTableBody?.querySelectorAll('.reservation-row') || [];
        let count = 0;
        rows.forEach(row => {
            if (rowMatchesAudience(row, audienceKey)) count++;
        });
        return count;
    }

    function getAudienceLabel(key) {
        if (key === 'active') return 'Active In-Park Guests';
        if (key === 'confirmed') return 'Confirmed Bookings';
        if (key === 'pending') return 'Pending Reservations';
        if (key === 'checked_out') return 'Checked Out Guests';
        if (key === 'all') return 'All Reservations';
        return 'Custom Selection';
    }

    function selectAudience(audienceKey) {
        currentAudience = audienceKey;

        // Highlight card
        audienceCards.forEach(card => {
            const key = card.getAttribute('data-audience');
            const badge = card.querySelector('.audience-badge');
            if (key === audienceKey) {
                card.classList.add('is-active-audience');
                if (badge) {
                    badge.classList.remove('hidden');
                    badge.textContent = 'Active';
                }
            } else {
                card.classList.remove('is-active-audience');
                if (badge) badge.classList.add('hidden');
            }
        });

        // Collect matching rows from the customizer table
        selectedReservations.clear();
        const rows = modalTableBody?.querySelectorAll('.reservation-row') || [];

        rows.forEach(row => {
            const id = row.getAttribute('data-id');
            const guest = row.getAttribute('data-guest-name');
            const phone = row.getAttribute('data-phone');
            const displayPhone = row.getAttribute('data-display-phone');

            if (rowMatchesAudience(row, audienceKey) && id) {
                selectedReservations.set(id, { id, guest, phone, displayPhone });
            }
        });

        // Reset pagination and sync UI
        currentPage = 1;
        updateRowCheckboxesAndHighlights();
        updateCustomizerCounts();
        updateStep1SummaryCard();
    }

    function updateStep1SummaryCard() {
        const totalSelected = selectedReservations.size;
        const totalPool = getAudienceMatchCount(currentAudience === 'custom' ? 'all' : currentAudience);

        if (selectedRatioDisplay) {
            selectedRatioDisplay.textContent = `${totalSelected} / ${totalPool}`;
        }
        if (selectedTargetGroupLabel) {
            selectedTargetGroupLabel.textContent = getAudienceLabel(currentAudience);
        }
        if (selectionStatusBadge) {
            if (currentAudience === 'custom') {
                selectionStatusBadge.textContent = 'Custom Selection';
                selectionStatusBadge.className = 'text-[11px] font-bold text-amber-600 dark:text-amber-400';
            } else {
                selectionStatusBadge.textContent = 'Group Preset Active';
                selectionStatusBadge.className = 'text-[11px] font-bold text-emerald-700 dark:text-emerald-400';
            }
        }
    }

    audienceCards.forEach(card => {
        card.addEventListener('click', () => {
            const key = card.getAttribute('data-audience');
            if (key) selectAudience(key);
        });
    });

    // =========================================================================
    // 3. RECIPIENTS CUSTOMIZER MODAL (opened from Step 1 Customize button)
    //    - Filters rows by audience (only shows matching rows)
    //    - Pagination at 100 rows per page
    // =========================================================================
    const recipientsCustomizerModal = document.getElementById('recipientsCustomizerModal');
    const openCustomizerModalBtn = document.getElementById('openCustomizerModalBtn');
    const closeCustomizerBtns = document.querySelectorAll('.close-customizer-modal');
    const modalTableBody = document.getElementById('modalTableBody');
    const modalMasterCheckbox = document.getElementById('modalMasterCheckbox');
    const modalSearchInput = document.getElementById('modalSearchInput');
    const modalSelectAllBtn = document.getElementById('modalSelectAllBtn');
    const modalClearBtn = document.getElementById('modalClearBtn');
    const customizerSelectedCount = document.getElementById('customizerSelectedCount');
    const customizerTotalCount = document.getElementById('customizerTotalCount');

    // Pagination elements
    const paginationPrevBtn = document.getElementById('paginationPrevBtn');
    const paginationNextBtn = document.getElementById('paginationNextBtn');
    const paginationCurrentPageEl = document.getElementById('paginationCurrentPage');
    const paginationTotalPagesEl = document.getElementById('paginationTotalPages');

    const ROWS_PER_PAGE = 100;
    let currentPage = 1;

    /**
     * Get the list of rows that should be visible in the customizer table,
     * filtering by BOTH the audience preset AND the search query.
     */
    function getFilteredRows() {
        const allRows = Array.from(modalTableBody?.querySelectorAll('.reservation-row') || []);
        const query = (modalSearchInput?.value || '').trim().toLowerCase();

        return allRows.filter(row => {
            // Audience filter: only show rows belonging to selected audience
            // 'custom' and 'all' show everything
            if (currentAudience !== 'custom' && currentAudience !== 'all') {
                if (!rowMatchesAudience(row, currentAudience)) return false;
            }

            // Search filter
            if (query.length > 0) {
                const guest = row.getAttribute('data-guest-name') || '';
                const phone = row.getAttribute('data-phone') || '';
                const id = row.getAttribute('data-id') || '';
                if (!id.includes(query) && !guest.includes(query) && !phone.includes(query)) return false;
            }

            return true;
        });
    }

    /**
     * Apply audience + search filters, then paginate at ROWS_PER_PAGE.
     */
    function applyFiltersAndPagination() {
        if (!modalTableBody) return;

        const allRows = Array.from(modalTableBody.querySelectorAll('.reservation-row'));
        const filteredRows = getFilteredRows();
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / ROWS_PER_PAGE));

        // Clamp currentPage
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIdx = (currentPage - 1) * ROWS_PER_PAGE;
        const endIdx = startIdx + ROWS_PER_PAGE;
        const pageRows = new Set(filteredRows.slice(startIdx, endIdx));

        // Show/hide rows
        allRows.forEach(row => {
            row.style.display = pageRows.has(row) ? '' : 'none';
        });

        // Update pagination controls
        if (paginationCurrentPageEl) paginationCurrentPageEl.textContent = currentPage;
        if (paginationTotalPagesEl) paginationTotalPagesEl.textContent = totalPages;
        if (paginationPrevBtn) paginationPrevBtn.disabled = (currentPage <= 1);
        if (paginationNextBtn) paginationNextBtn.disabled = (currentPage >= totalPages);

        updateRowCheckboxesAndHighlights();
    }

    paginationPrevBtn?.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            applyFiltersAndPagination();
        }
    });

    paginationNextBtn?.addEventListener('click', () => {
        const filteredRows = getFilteredRows();
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / ROWS_PER_PAGE));
        if (currentPage < totalPages) {
            currentPage++;
            applyFiltersAndPagination();
        }
    });

    function openCustomizerModal() {
        if (!recipientsCustomizerModal) return;
        // Reset to page 1 on open, apply filters
        currentPage = 1;
        applyFiltersAndPagination();
        recipientsCustomizerModal.classList.remove('hidden');
        recipientsCustomizerModal.classList.add('flex');
    }

    function closeCustomizerModal() {
        if (!recipientsCustomizerModal) return;
        recipientsCustomizerModal.classList.add('hidden');
        recipientsCustomizerModal.classList.remove('flex');
        // Sync summary card with latest selection
        updateStep1SummaryCard();
    }

    openCustomizerModalBtn?.addEventListener('click', openCustomizerModal);

    closeCustomizerBtns.forEach(btn => {
        btn.addEventListener('click', closeCustomizerModal);
    });

    recipientsCustomizerModal?.addEventListener('click', (e) => {
        if (e.target === recipientsCustomizerModal) closeCustomizerModal();
    });

    // Search re-filters and resets to page 1
    modalSearchInput?.addEventListener('input', () => {
        currentPage = 1;
        applyFiltersAndPagination();
        updateCustomizerCounts();
    });

    function updateRowCheckboxesAndHighlights() {
        const rows = modalTableBody?.querySelectorAll('.reservation-row') || [];
        rows.forEach(row => {
            const id = row.getAttribute('data-id');
            const cb = row.querySelector('.reservation-select-cb');
            if (selectedReservations.has(id)) {
                row.classList.add('is-selected');
                if (cb) cb.checked = true;
            } else {
                row.classList.remove('is-selected');
                if (cb) cb.checked = false;
            }
        });
        updateModalMasterCheckbox();
    }

    function updateModalMasterCheckbox() {
        if (!modalMasterCheckbox || !modalTableBody) return;
        const visibleRows = Array.from(modalTableBody.querySelectorAll('.reservation-row')).filter(r => r.style.display !== 'none');
        if (visibleRows.length === 0) {
            modalMasterCheckbox.checked = false;
            modalMasterCheckbox.indeterminate = false;
            return;
        }

        let checkedCount = 0;
        visibleRows.forEach(row => {
            if (selectedReservations.has(row.getAttribute('data-id'))) checkedCount++;
        });

        if (checkedCount === visibleRows.length) {
            modalMasterCheckbox.checked = true;
            modalMasterCheckbox.indeterminate = false;
        } else if (checkedCount > 0) {
            modalMasterCheckbox.checked = false;
            modalMasterCheckbox.indeterminate = true;
        } else {
            modalMasterCheckbox.checked = false;
            modalMasterCheckbox.indeterminate = false;
        }
    }

    function updateCustomizerCounts() {
        const totalSelected = selectedReservations.size;
        const filteredRows = getFilteredRows();

        if (customizerSelectedCount) customizerSelectedCount.textContent = totalSelected;
        if (customizerTotalCount) customizerTotalCount.textContent = filteredRows.length;
    }

    // Row checkbox toggle
    modalTableBody?.addEventListener('change', (e) => {
        if (e.target.classList.contains('reservation-select-cb')) {
            const cb = e.target;
            const id = cb.getAttribute('data-id');

            currentAudience = 'custom';
            audienceCards.forEach(c => c.classList.remove('is-active-audience'));

            if (cb.checked) {
                selectedReservations.set(id, {
                    id,
                    guest: cb.getAttribute('data-guest') || `Reservation #${id}`,
                    phone: cb.getAttribute('data-phone') || '',
                    displayPhone: cb.getAttribute('data-display-phone') || 'No phone'
                });
            } else {
                selectedReservations.delete(id);
            }

            updateRowCheckboxesAndHighlights();
            updateCustomizerCounts();
        }
    });

    // Master checkbox — operates on visible (current page) rows only
    modalMasterCheckbox?.addEventListener('change', () => {
        const visibleRows = Array.from(modalTableBody.querySelectorAll('.reservation-row')).filter(r => r.style.display !== 'none');
        currentAudience = 'custom';
        audienceCards.forEach(c => c.classList.remove('is-active-audience'));

        visibleRows.forEach(row => {
            const id = row.getAttribute('data-id');
            const cb = row.querySelector('.reservation-select-cb');
            if (modalMasterCheckbox.checked) {
                selectedReservations.set(id, {
                    id,
                    guest: cb?.getAttribute('data-guest') || `Reservation #${id}`,
                    phone: cb?.getAttribute('data-phone') || '',
                    displayPhone: cb?.getAttribute('data-display-phone') || 'No phone'
                });
            } else {
                selectedReservations.delete(id);
            }
        });

        updateRowCheckboxesAndHighlights();
        updateCustomizerCounts();
    });

    // Select All button — selects ALL filtered rows (not just current page)
    modalSelectAllBtn?.addEventListener('click', () => {
        const filteredRows = getFilteredRows();
        currentAudience = 'custom';
        audienceCards.forEach(c => c.classList.remove('is-active-audience'));

        filteredRows.forEach(row => {
            const id = row.getAttribute('data-id');
            const cb = row.querySelector('.reservation-select-cb');
            if (cb) {
                selectedReservations.set(id, {
                    id,
                    guest: cb.getAttribute('data-guest') || `Reservation #${id}`,
                    phone: cb.getAttribute('data-phone') || '',
                    displayPhone: cb.getAttribute('data-display-phone') || 'No phone'
                });
            }
        });

        updateRowCheckboxesAndHighlights();
        updateCustomizerCounts();
    });

    // Deselect All button
    modalClearBtn?.addEventListener('click', () => {
        currentAudience = 'custom';
        audienceCards.forEach(c => c.classList.remove('is-active-audience'));
        selectedReservations.clear();
        updateRowCheckboxesAndHighlights();
        updateCustomizerCounts();
    });

    // =========================================================================
    // 4. STEP 2: COMPOSE SMS & LIVE PHONE MOCKUP PREVIEW
    // =========================================================================
    const templateChips = document.querySelectorAll('.template-chip');
    const smsAnnouncementTitle = document.getElementById('smsAnnouncementTitle');
    const smsMessageTextarea = document.getElementById('smsMessageTextarea');
    const charCountEl = document.getElementById('charCount');
    const smsCharCounter = document.getElementById('smsCharCounter');
    const liveSmsPreviewBubble = document.getElementById('liveSmsPreviewBubble');
    const liveSmsPreviewTime = document.getElementById('liveSmsPreviewTime');

    const templates = {
        welcome: "Hinaguan Nature Park: Welcome! Please be reminded of our park guidelines and quiet hours from 10 PM. Enjoy your stay!",
        weather: "Hinaguan Nature Park Advisory: Due to changing weather conditions, please exercise caution around river/pool areas. Stay safe!",
        pool: "Hinaguan Nature Park Notice: The swimming pool is open until 10:00 PM for all guests with pool access wristbands.",
        checkout: "Hinaguan Nature Park Reminder: Standard check-out time is at 12:00 PM. Please visit the front desk for checkout assistance.",
        activity: "Hinaguan Nature Park Announcement: A scheduled park activity is taking place today! Inquire at reception for details."
    };

    function updateCharCounterAndPreview() {
        if (!smsMessageTextarea) return;
        const text = smsMessageTextarea.value;
        const length = text.length;

        let credits = 1;
        if (length > 160) credits = Math.ceil(length / 153);

        if (smsCharCounter) {
            smsCharCounter.innerHTML = `<span id="charCount">${length}</span> / 160 chars (${credits} SMS credit${credits > 1 ? 's' : ''})`;
            if (length > 160) {
                smsCharCounter.classList.add('text-amber-600', 'font-bold');
            } else {
                smsCharCounter.classList.remove('text-amber-600', 'font-bold');
            }
        }

        if (liveSmsPreviewBubble) {
            liveSmsPreviewBubble.textContent = text.trim() || 'Hinaguan Nature Park: Welcome! Please be reminded of our park guidelines and quiet hours from 10 PM. Enjoy your stay!';
        }

        if (liveSmsPreviewTime) {
            liveSmsPreviewTime.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
    }

    smsMessageTextarea?.addEventListener('input', updateCharCounterAndPreview);

    templateChips.forEach(chip => {
        chip.addEventListener('click', () => {
            const key = chip.getAttribute('data-template');
            if (templates[key] && smsMessageTextarea) {
                smsMessageTextarea.value = templates[key];
                updateCharCounterAndPreview();
                smsMessageTextarea.focus();
            }
        });
    });

    // =========================================================================
    // 5. STEP 3: REVIEW & DISPATCH SMS
    // =========================================================================
    const reviewAudienceName = document.getElementById('reviewAudienceName');
    const reviewReservationsCount = document.getElementById('reviewReservationsCount');
    const reviewValidPhoneCount = document.getElementById('reviewValidPhoneCount');
    const reviewMessageText = document.getElementById('reviewMessageText');
    const alertFeedbackContainer = document.getElementById('alertFeedbackContainer');
    const metricSmsSentCount = document.getElementById('metricSmsSentCount');

    function populateReviewStep() {
        let validCount = 0;
        selectedReservations.forEach(item => {
            if (item.phone && item.phone.trim().length > 5) validCount++;
        });

        if (reviewAudienceName) reviewAudienceName.textContent = getAudienceLabel(currentAudience);
        if (reviewReservationsCount) reviewReservationsCount.textContent = `${selectedReservations.size} Selected`;
        if (reviewValidPhoneCount) reviewValidPhoneCount.textContent = `${validCount} Numbers`;
        if (reviewMessageText) reviewMessageText.textContent = smsMessageTextarea?.value || '—';
    }

    async function dispatchSmsBroadcast() {
        const messageText = (smsMessageTextarea?.value || '').trim();
        const titleText = (smsAnnouncementTitle?.value || '').trim();

        if (messageText.length < 3) {
            alert('Please enter an SMS announcement message of at least 3 characters.');
            goToStep(2);
            return;
        }

        const reservationIds = Array.from(selectedReservations.keys()).map(id => parseInt(id, 10));
        if (reservationIds.length === 0) {
            alert('No reservations selected. Please pick recipients in Step 1.');
            goToStep(1);
            return;
        }

        if (!confirm(`Dispatch this SMS message to ${reservationIds.length} reservation(s)?`)) return;

        if (wizardNextBtn) {
            wizardNextBtn.disabled = true;
            wizardNextBtn.innerHTML = '<span class="inline-block animate-spin mr-2"><i class="bi bi-arrow-repeat"></i></span> <span>Broadcasting SMS...</span>';
        }

        try {
            const response = await fetch('/admin/announcements/send-sms', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    reservation_ids: reservationIds,
                    message: messageText,
                    title: titleText || 'Guest SMS Announcement',
                    category: 'sms_broadcast'
                })
            });

            const data = await response.json();

            if (data.success) {
                closeWizard();
                showNotification(data.message || 'SMS broadcast dispatched successfully!', 'success');

                if (data.announcement) prependHistoryRow(data.announcement);

                if (metricSmsSentCount) {
                    const current = parseInt(metricSmsSentCount.textContent.trim(), 10) || 0;
                    metricSmsSentCount.textContent = current + (data.recipients_count || reservationIds.length);
                }

                // Reset
                if (smsMessageTextarea) smsMessageTextarea.value = '';
                if (smsAnnouncementTitle) smsAnnouncementTitle.value = '';
                updateCharCounterAndPreview();
                goToStep(1);
            } else {
                alert(data.message || 'Failed to broadcast SMS announcement.');
            }
        } catch (err) {
            console.error(err);
            alert('A network error occurred while broadcasting SMS.');
        } finally {
            if (wizardNextBtn) {
                wizardNextBtn.disabled = false;
                wizardNextBtn.innerHTML = '<i class="bi bi-send-fill"></i> <span>Broadcast SMS Now</span>';
            }
        }
    }

    function showNotification(message, type = 'success') {
        if (!alertFeedbackContainer) return;
        const bgClass = type === 'success'
            ? 'bg-emerald-50 border-emerald-300 text-emerald-800 dark:bg-emerald-950/60 dark:border-emerald-800 dark:text-emerald-200'
            : 'bg-rose-50 border-rose-300 text-rose-800 dark:bg-rose-950/60 dark:border-rose-800 dark:text-rose-200';
        const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';

        alertFeedbackContainer.innerHTML = `
            <div class="flex items-center justify-between p-3.5 rounded-xl border ${bgClass} text-xs font-semibold shadow-sm transition-all">
                <div class="flex items-center gap-2">
                    <i class="bi ${icon} text-base"></i>
                    <span>${message}</span>
                </div>
                <button type="button" class="text-sm opacity-70 hover:opacity-100 cursor-pointer p-1" onclick="this.parentElement.remove()">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        `;
        alertFeedbackContainer.classList.remove('hidden');
        setTimeout(() => { alertFeedbackContainer?.classList.add('hidden'); }, 7000);
    }

    // =========================================================================
    // 6. RECENT SMS BROADCAST HISTORY & ACTIONS
    // =========================================================================
    const historySearchInput = document.getElementById('historySearchInput');
    const announcementsHistoryBody = document.getElementById('announcementsHistoryBody');
    const emptyHistoryRow = document.getElementById('emptyHistoryRow');

    function prependHistoryRow(ann) {
        if (!announcementsHistoryBody) return;
        if (emptyHistoryRow) emptyHistoryRow.remove();

        const dateStr = new Date().toLocaleString('en-US', {
            month: 'short', day: 'numeric', year: 'numeric',
            hour: 'numeric', minute: 'numeric', hour12: true
        });

        const tr = document.createElement('tr');
        tr.className = 'history-row hover:bg-black/5 dark:hover:bg-white/5 transition-colors';
        tr.setAttribute('data-announcement-id', ann.id);

        tr.innerHTML = `
            <td class="py-3 px-3 text-[var(--ink-muted)] whitespace-nowrap">${dateStr}</td>
            <td class="py-3 px-3 font-semibold text-[var(--ink)] whitespace-nowrap">${escapeHtml(ann.title || 'SMS Broadcast')}</td>
            <td class="py-3 px-4 text-[var(--ink)]">
                <p class="m-0 leading-relaxed font-mono text-[11px] max-w-xl truncate" title="${escapeHtml(ann.message)}">${escapeHtml(ann.message)}</p>
            </td>
            <td class="py-3 px-3 text-center">
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-950/50 px-2 py-0.5 text-xs font-bold text-emerald-700 dark:text-emerald-300">
                    <i class="bi bi-phone"></i> ${ann.recipient_count || 0}
                </span>
            </td>
            <td class="py-3 px-3 text-center">
                <span class="inline-flex items-center rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300 px-2 py-0.5 text-[10px] font-bold uppercase">${escapeHtml(ann.delivery_status || 'Sent')}</span>
            </td>
            <td class="py-3 px-3 text-right text-[var(--ink-muted)]">${escapeHtml(ann.created_by || 'Admin')}</td>
            <td class="py-3 px-3 text-center">
                <button type="button" class="delete-history-btn text-rose-500 hover:text-rose-700 p-1 rounded hover:bg-rose-50 dark:hover:bg-rose-950/40 cursor-pointer" data-id="${ann.id}" title="Delete log">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        announcementsHistoryBody.prepend(tr);
    }

    historySearchInput?.addEventListener('input', () => {
        const query = (historySearchInput.value || '').toLowerCase().trim();
        document.querySelectorAll('.history-row').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
        });
    });

    document.getElementById('historyTable')?.addEventListener('click', async (e) => {
        const delBtn = e.target.closest('.delete-history-btn');
        if (delBtn) {
            const id = delBtn.getAttribute('data-id');
            if (!confirm('Are you sure you want to delete this SMS broadcast log entry?')) return;
            try {
                const res = await fetch(`/admin/announcements/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    document.querySelector(`tr[data-announcement-id="${id}"]`)?.remove();
                    showNotification('Announcement record deleted.', 'success');
                }
            } catch (err) {
                console.error(err);
                alert('Failed to delete announcement record.');
            }
        }
    });

    // =========================================================================
    // 7. COMPANIONS BREAKDOWN MODAL
    // =========================================================================
    const companionsModal = document.getElementById('companionsModal');
    const closeCompanionsBtns = document.querySelectorAll('.close-companions-modal');
    const companionModalTitle = document.getElementById('companionModalTitle');
    const companionMainGuestName = document.getElementById('companionMainGuestName');
    const companionMainGuestPhone = document.getElementById('companionMainGuestPhone');
    const companionModalCount = document.getElementById('companionModalCount');
    const companionsCardsList = document.getElementById('companionsCardsList');

    // Listen on the customizer modal table body for companion clicks
    modalTableBody?.addEventListener('click', (e) => {
        const btn = e.target.closest('.view-companions-btn');
        if (btn) {
            const row = btn.closest('.reservation-row');
            const resId = btn.getAttribute('data-res-id');
            const guest = btn.getAttribute('data-guest');
            const phone = btn.getAttribute('data-phone');
            const companionsJson = row?.getAttribute('data-companions-json');

            let companions = [];
            try { companions = JSON.parse(companionsJson || '[]'); } catch { companions = []; }

            if (companionModalTitle) companionModalTitle.textContent = `Companions Breakdown — Reservation #${resId}`;
            if (companionMainGuestName) companionMainGuestName.textContent = guest;
            if (companionMainGuestPhone) companionMainGuestPhone.textContent = phone ? `Mobile: ${phone}` : 'No phone registered';
            if (companionModalCount) companionModalCount.textContent = companions.length;

            if (companionsCardsList) {
                companionsCardsList.innerHTML = '';
                if (companions.length === 0) {
                    companionsCardsList.innerHTML = `
                        <div class="py-6 text-center text-xs text-[var(--ink-muted)] border border-dashed border-[var(--border)] rounded-xl">
                            No companions recorded for this reservation.
                        </div>`;
                } else {
                    companions.forEach((comp, idx) => {
                        const card = document.createElement('div');
                        card.className = 'flex items-center justify-between p-3 rounded-xl border border-[var(--border)] bg-white dark:bg-neutral-900/60 shadow-2xs';

                        const poolBadge = comp.has_pool_access
                            ? '<span class="inline-flex items-center gap-1 rounded-full bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 px-2 py-0.5 text-[10px] font-bold"><i class="bi bi-water"></i> Pool Access</span>'
                            : '<span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-neutral-800 text-gray-500 px-2 py-0.5 text-[10px]">No Pool</span>';

                        const checkoutBadge = comp.is_checked_out
                            ? `<span class="text-[10px] text-gray-500 font-medium">Checked out: ${escapeHtml(comp.checked_out_at || '')}</span>`
                            : '<span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 dark:text-emerald-400"><span class="live-indicator-dot"></span> In-Park</span>';

                        card.innerHTML = `
                            <div class="flex items-center gap-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 text-xs font-bold">${idx + 1}</span>
                                <div>
                                    <div class="text-xs font-bold text-[var(--ink)]">${escapeHtml(comp.name)}</div>
                                    <div class="text-[10px] text-[var(--ink-muted)] flex items-center gap-2">
                                        <span>Age: ${comp.age || '—'}</span><span>•</span><span>Gender: ${escapeHtml(comp.gender || '—')}</span>
                                        ${comp.is_foreigner ? '<span>• <span class="font-bold text-purple-600">Foreigner</span></span>' : ''}
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1">${poolBadge}${checkoutBadge}</div>`;
                        companionsCardsList.appendChild(card);
                    });
                }
            }

            companionsModal?.classList.remove('hidden');
            companionsModal?.classList.add('flex');
        }
    });

    closeCompanionsBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            companionsModal?.classList.add('hidden');
            companionsModal?.classList.remove('flex');
        });
    });

    companionsModal?.addEventListener('click', (e) => {
        if (e.target === companionsModal) {
            companionsModal.classList.add('hidden');
            companionsModal.classList.remove('flex');
        }
    });

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize: pre-select active in-park guests
    selectAudience('active');
    updateCharCounterAndPreview();
});
