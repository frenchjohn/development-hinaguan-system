import { Html5Qrcode } from 'html5-qrcode';

window.AppPage = window.AppPage || {};
window.AppPage['staff_reservations'] = function () {

    const modal = document.getElementById('reservationModal');
    const modalBody = document.getElementById('reservationModalBody');
    const modalStatus = document.getElementById('reservationModalStatus');
    const closeButtons = document.querySelectorAll('[data-close-reservation-modal="true"]');
    const checkInModal = document.getElementById('checkInModal');
    const checkInForm = document.getElementById('checkInForm');
    const checkInCompanionModal = document.getElementById('checkInCompanionModal');
    const checkInCompanionForm = document.getElementById('checkInCompanionForm');
    const checkInCloseButtons = document.querySelectorAll('[data-close-check-in-modal="true"]');
    const scanQrBtn = document.getElementById('scanQrBtn');
    const scanQrModal = document.getElementById('scanQrModal');
    const stopQrBtn = document.getElementById('stopQrBtn');
    const qrScannerStatus = document.getElementById('qrScannerStatus');
    const qrScannerElement = document.getElementById('qrScanner');
    const scanQrCloseButtons = document.querySelectorAll('[data-close-scan-modal="true"]');
    const checkInCompanionCloseButtons = document.querySelectorAll('[data-close-check-in-companion-modal="true"]');
    const checkInAddCompanionBtn = document.getElementById('checkInAddCompanionBtn');
    const checkInBulkCompanionBtn = document.getElementById('checkInBulkCompanionBtn');
    const checkInCompanionList = document.getElementById('checkInCompanionList');
    const checkInCompanionHiddenFields = document.getElementById('checkInCompanionHiddenFields');
    const checkInPrimaryIsForeigner = document.getElementById('checkInPrimaryIsForeigner');
    const checkInCompanionIsForeigner = document.getElementById('checkInCompanionIsForeigner');
    const bulkCompanionModal = document.getElementById('bulkCompanionModal');
    const bulkCompanionForm = document.getElementById('bulkCompanionForm');
    const bulkCompanionCloseButtons = document.querySelectorAll('[data-close-bulk-companion-modal="true"]');
    const companionSummaryModal = document.getElementById('companionSummaryModal');
    const companionSummaryBody = document.getElementById('companionSummaryBody');
    const companionSummaryCloseButtons = document.querySelectorAll('[data-close-companion-summary="true"]');
    const proceedToCheckInBtn = document.getElementById('proceedToCheckInBtn');
    const checkInConfirmationModal = document.getElementById('checkInConfirmationModal');
    const checkInConfirmationBody = document.getElementById('checkInConfirmationBody');
    const checkInConfirmationCloseButtons = document.querySelectorAll('[data-close-check-in-confirmation="true"]');
    const confirmCheckInBtn = document.getElementById('confirmCheckInBtn');
    let currentReservationData = null;
    let countdownTimer = null;
    const tableBody = document.getElementById('reservationTableBody');
    const rows = Array.from(tableBody?.querySelectorAll('.reservation-row') ?? []);
    const searchInput = document.getElementById('reservationSearchInput');
    const sortSelect = document.getElementById('reservationSortSelect');
    const statusFilter = document.getElementById('reservationStatusFilter');
    const checkInFrom = document.getElementById('reservationDateFrom');
    const checkInTo = document.getElementById('reservationDateTo');
    const qrCameraSelect = document.getElementById('qrCameraSelect');
    const clearButton = document.getElementById('reservationFiltersClear');
    let html5QrCode = null;
    let qrScannerActive = false;
    const resultsCount = document.getElementById('reservationResultsCount');
    const filterToggle = document.getElementById('reservationFilterToggle');
    const filterPanel = document.getElementById('reservationFilterPanel');
    const refreshTableBtn = document.getElementById('refreshTableBtn');
    const reservationData = window.staffReservationData || {};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');

    const getInitials = (name) => String(name || '').trim().split(/\s+/)
        .filter(Boolean)
        .map(word => (word[0] || '').toUpperCase())
        .slice(0, 2)
        .join('') || '?';

    const renderTimeSlots = (reservation) => {
        const slots = reservation?.time_slots || [];
        if (!slots.length) return '<span class="text-muted">—</span>';
        return `<div class="time-slot-labels">${slots.map(slot => `<span class="time-slot-label time-slot-label--${String(slot).toLowerCase().replace(/\s+/g, '')}">${escapeHtml(slot)}</span>`).join('')}</div>`;
    };

    // Shared 7-column row markup (used by server rows, refresh + fallback renders)
    const buildRowCells = (reservation) => {
        const formatDate = (dateStr) => {
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr;
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        };
        return `
                <td>
                    <div class="resv-booker">
                        <span class="resv-avatar">${escapeHtml(getInitials(reservation.booker_name))}</span>
                        <div class="resv-booker__info">
                            <div class="guest-name">${escapeHtml(reservation.booker_name)}</div>
                            <div class="guest-meta">${escapeHtml(reservation.email)}</div>
                        </div>
                    </div>
                </td>
                <td>${escapeHtml(formatDate(reservation.reservation_date))}</td>
                <td>${renderTimeSlots(reservation)}</td>
                <td>${escapeHtml(reservation.number_of_guests)}</td>
                <td>
                    <span class="reservation-status reservation-status--${String(reservation.status || '').toLowerCase()}">${escapeHtml(reservation.status)}</span>
                </td>
                <td>₱${Number(reservation.total_amount || 0).toFixed(2)}</td>
                <td>
                    <button type="button" class="resv-row-action" aria-label="View reservation details">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </td>
            `;
    };

    let pendingReservationId = null;
    let checkInCompanions = [];
    let existingReservationGuests = [];
    let primaryGuestToUpdate = null;
    let currentModalReservationId = null;
    let companionCount = 0;
    let bulkCompanionGroups = [];

    const renderCheckInCompanions = () => {
        checkInCompanionList.innerHTML = '';
        checkInCompanionHiddenFields.innerHTML = '';

        // Render individual companions (single additions)
        if (checkInCompanions.length > 0) {
            checkInCompanions.forEach((companion, index) => {
                const nationality = companion.is_foreigner ? 'Foreigner' : 'Filipino';
                const item = document.createElement('div');
                item.className = 'guest-companion-pill';
                item.style.display = 'flex';
                item.style.alignItems = 'center';
                item.style.gap = '0.5rem';
                
                const infoSpan = document.createElement('span');
                infoSpan.textContent = `${companion.first_name} ${companion.last_name} - ${nationality} - ${companion.age || 'N/A'} - ${companion.gender}`;
                item.appendChild(infoSpan);
                
                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.className = 'guest-companion-pill__delete';
                deleteBtn.textContent = '×';
                deleteBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    checkInCompanions.splice(index, 1);
                    renderCheckInCompanions();
                });
                item.appendChild(deleteBtn);
                checkInCompanionList.appendChild(item);
            });
        }

        // Render bulk companion groups
        if (bulkCompanionGroups.length > 0) {
            bulkCompanionGroups.forEach((group, index) => {
                const nationality = group.is_foreigner ? 'Foreigner' : 'Filipino';
                const item = document.createElement('div');
                item.className = 'guest-companion-pill';
                item.style.display = 'flex';
                item.style.alignItems = 'center';
                item.style.gap = '0.5rem';
                
                const infoSpan = document.createElement('span');
                infoSpan.textContent = `${group.gender} - ${nationality} - ${group.age_group} - `;
                item.appendChild(infoSpan);
                
                const quantityInput = document.createElement('input');
                quantityInput.type = 'number';
                quantityInput.min = '1';
                quantityInput.max = '500';
                quantityInput.value = group.quantity;
                quantityInput.style.width = '60px';
                quantityInput.style.padding = '0.25rem';
                quantityInput.style.border = '1px solid #ccc';
                quantityInput.style.borderRadius = '0.25rem';
                quantityInput.addEventListener('change', (e) => {
                    const newQuantity = parseInt(e.target.value, 10) || 1;
                    group.quantity = Math.min(Math.max(newQuantity, 1), 500);
                    e.target.value = group.quantity;
                    renderCheckInCompanions();
                });
                item.appendChild(quantityInput);
                
                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.className = 'guest-companion-pill__delete';
                deleteBtn.textContent = '×';
                deleteBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    bulkCompanionGroups.splice(index, 1);
                    renderCheckInCompanions();
                });
                item.appendChild(deleteBtn);
                checkInCompanionList.appendChild(item);
            });
        }

        if (checkInCompanions.length === 0 && bulkCompanionGroups.length === 0) {
            checkInCompanionList.innerHTML = '<p class="guest-empty">No companions added yet.</p>';
            return;
        }

        // Generate individual companions from bulk groups for form submission
        const allCompanions = [...checkInCompanions];
        bulkCompanionGroups.forEach(group => {
            for (let i = 0; i < group.quantity; i++) {
                companionCount++;
                allCompanions.push({
                    first_name: `Reservation ${pendingReservationId || 'Guest'}`,
                    middle_name: '',
                    last_name: `C${companionCount}`,
                    age: group.age_group,
                    gender: group.gender,
                    is_foreigner: group.is_foreigner,
                    phone: '',
                    email: '',
                });
            }
        });

        allCompanions.forEach((companion, index) => {
            Object.entries(companion).forEach(([key, value]) => {
                const field = document.createElement('input');
                field.type = 'hidden';
                field.name = `check_in_companions[${index}][${key}]`;
                field.value = value;
                checkInCompanionHiddenFields.appendChild(field);
            });
        });
    };

    const openCheckInCompanionModal = () => {
        checkInCompanionForm.reset();
        if (checkInCompanionModal) {
            checkInCompanionModal.classList.add('is-open');
            checkInCompanionModal.setAttribute('aria-hidden', 'false');
        }
    };

    const closeCheckInCompanionModal = () => {
        if (checkInCompanionModal) {
            checkInCompanionModal.classList.remove('is-open');
            checkInCompanionModal.setAttribute('aria-hidden', 'true');
        }
    };

    const openBulkCompanionModal = () => {
        bulkCompanionForm.reset();
        if (bulkCompanionModal) {
            bulkCompanionModal.classList.add('is-open');
            bulkCompanionModal.setAttribute('aria-hidden', 'false');
        }
    };

    const closeBulkCompanionModal = () => {
        if (bulkCompanionModal) {
            bulkCompanionModal.classList.remove('is-open');
            bulkCompanionModal.setAttribute('aria-hidden', 'true');
        }
    };

    const openCompanionSummaryModal = () => {
        if (bulkCompanionGroups.length === 0) {
            // If no bulk companions, proceed directly to check-in confirmation
            openCheckInConfirmationModal();
            return;
        }

        let html = '<div style="margin-bottom: 1rem;"><h4>Companion Groups:</h4>';
        let totalCompanions = 0;
        
        bulkCompanionGroups.forEach(group => {
            const nationality = group.is_foreigner ? 'Foreigner' : 'Filipino';
            html += `
                <div style="padding: 0.5rem; background-color: #e5e5e5; border: 1px solid #d4d4d4; border-radius: 0.25rem; margin-bottom: 0.5rem; color: #000;">
                    <p><strong>Gender:</strong> ${group.gender} | <strong>Nationality:</strong> ${nationality} | <strong>Age Group:</strong> ${group.age_group} | <strong>Quantity:</strong> ${group.quantity}</p>
                </div>
            `;
            totalCompanions += group.quantity;
        });
        
        html += `<p><strong>Total Companions:</strong> ${totalCompanions}</p></div>`;
        html += '<p style="color: #666; font-size: 0.875rem;">Please review the companion groups above before checking in.</p>';
        
        companionSummaryBody.innerHTML = html;
        if (companionSummaryModal) {
            companionSummaryModal.classList.add('is-open');
            companionSummaryModal.setAttribute('aria-hidden', 'false');
        }
    };

    const closeCompanionSummaryModal = () => {
        if (companionSummaryModal) {
            companionSummaryModal.classList.remove('is-open');
            companionSummaryModal.setAttribute('aria-hidden', 'true');
        }
    };

    const openCheckInConfirmationModal = () => {
        // Clear any existing timer
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
        
        // Check if reservation date is today
        const today = new Date().toISOString().split('T')[0];
        const reservationDate = currentReservationData?.reservation_date ? 
            new Date(currentReservationData.reservation_date).toISOString().split('T')[0] : null;
        
        const isToday = reservationDate === today;
        
        let html = '<p>Are you sure you want to check in this reservation now?</p>';
        
        if (!isToday && reservationDate) {
            html += `
                <div style="margin-top: 1rem; padding: 0.75rem; background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 0.25rem; color: #856404;">
                    <strong>⚠️ Warning:</strong> This reservation is not scheduled for today.<br>
                    <strong>Reservation Date:</strong> ${reservationDate}<br>
                    <strong>Today:</strong> ${today}
                </div>
            `;
        }
        
        checkInConfirmationBody.innerHTML = html;
        
        // Handle button cooldown for non-today reservations
        if (!isToday && reservationDate && confirmCheckInBtn) {
            confirmCheckInBtn.disabled = true;
            let countdown = 10;
            confirmCheckInBtn.textContent = `Please wait ${countdown}s...`;
            
            countdownTimer = setInterval(() => {
                countdown--;
                if (countdown > 0) {
                    confirmCheckInBtn.textContent = `Please wait ${countdown}s...`;
                } else {
                    clearInterval(countdownTimer);
                    countdownTimer = null;
                    confirmCheckInBtn.disabled = false;
                    confirmCheckInBtn.textContent = 'Yes, Check In';
                }
            }, 1000);
        } else if (confirmCheckInBtn) {
            confirmCheckInBtn.disabled = false;
            confirmCheckInBtn.textContent = 'Yes, Check In';
        }
        
        if (checkInConfirmationModal) {
            checkInConfirmationModal.classList.add('is-open');
            checkInConfirmationModal.setAttribute('aria-hidden', 'false');
        }
    };

    const closeCheckInConfirmationModal = () => {
        // Clear any existing timer when closing modal
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
        
        if (checkInConfirmationModal) {
            checkInConfirmationModal.classList.remove('is-open');
            checkInConfirmationModal.setAttribute('aria-hidden', 'true');
        }
    };

    const getAgeFromGroup = (ageGroup) => {
        const ageMap = {
            '0-12': 6,
            '13-17': 15,
            '18-59': 30,
            '60+': 65
        };
        return ageMap[ageGroup] || 30;
    };

    const fillFormWithGuestData = (guestData, namePrefix) => {
        if (!guestData || !checkInForm) return;

        const firstNameInput = checkInForm.querySelector(`input[name="${namePrefix}[first_name]"]`);
        const middleNameInput = checkInForm.querySelector(`input[name="${namePrefix}[middle_name]"]`);
        const lastNameInput = checkInForm.querySelector(`input[name="${namePrefix}[last_name]"]`);
        const ageInput = checkInForm.querySelector(`input[name="${namePrefix}[age]"]`);
        const genderSelect = checkInForm.querySelector(`select[name="${namePrefix}[gender]"]`);
        const isForeignerSelect = checkInForm.querySelector(`select[name="${namePrefix}[is_foreigner]"]`);
        const phoneInput = checkInForm.querySelector(`input[name="${namePrefix}[phone]"]`);
        const emailInput = checkInForm.querySelector(`input[name="${namePrefix}[email]"]`);

        if (firstNameInput) firstNameInput.value = guestData.first_name || '';
        if (middleNameInput) middleNameInput.value = guestData.middle_name || '';
        if (lastNameInput) lastNameInput.value = guestData.last_name || '';
        if (ageInput) ageInput.value = guestData.age || '';
        if (genderSelect) genderSelect.value = guestData.gender || 'Male';
        
        if (isForeignerSelect) {
            isForeignerSelect.value = guestData.is_foreigner ? '1' : '0';
        }
        
        if (phoneInput) phoneInput.value = guestData.phone || '';
        if (emailInput) emailInput.value = guestData.email || '';
    };

    const toggleCheckInPrimaryGuestSection = () => {
        if (!checkInForm) return;
        const guestMode = checkInForm.querySelector('input[name="check_in_guest_mode"]:checked')?.value;
        const primarySection = document.getElementById('checkInPrimaryGuestSection');
        if (primarySection) {
            primarySection.style.display = guestMode === 'with_primary' ? 'block' : 'none';
        }
    };

    checkInForm?.addEventListener('change', (e) => {
        if (e.target.name === 'check_in_guest_mode') {
            toggleCheckInPrimaryGuestSection();
        }
    });

    checkInAddCompanionBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        openCheckInCompanionModal();
    });

    checkInBulkCompanionBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        openBulkCompanionModal();
    });

    bulkCompanionCloseButtons.forEach((button) => {
        button.addEventListener('click', closeBulkCompanionModal);
    });

    companionSummaryCloseButtons.forEach((button) => {
        button.addEventListener('click', closeCompanionSummaryModal);
    });

    checkInConfirmationCloseButtons.forEach((button) => {
        button.addEventListener('click', closeCheckInConfirmationModal);
    });

    bulkCompanionForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const formData = new FormData(bulkCompanionForm);
        const gender = formData.get('gender');
        const isForeigner = formData.get('is_foreigner') === '1';
        const ageGroup = formData.get('age_group');
        const quantity = parseInt(formData.get('quantity'), 10) || 1;

        // Check for duplicates
        const duplicateIndex = bulkCompanionGroups.findIndex(
            group => group.gender === gender && 
                    group.is_foreigner === isForeigner && 
                    group.age_group === ageGroup
        );

        if (duplicateIndex !== -1) {
            // Show duplicate modal
            alert('This companion group already exists. Please edit the existing group instead.');
            closeBulkCompanionModal();
            return;
        }

        // Directly add the companion group
        bulkCompanionGroups.push({
            gender,
            is_foreigner: isForeigner,
            age_group: ageGroup,
            quantity,
        });

        renderCheckInCompanions();
        closeBulkCompanionModal();
    });

    proceedToCheckInBtn?.addEventListener('click', () => {
        closeCompanionSummaryModal();
        openCheckInConfirmationModal();
    });

    confirmCheckInBtn?.addEventListener('click', () => {
        closeCheckInConfirmationModal();
        submitCheckInForm();
    });

    checkInCompanionForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(checkInCompanionForm);
        const companion = {
            first_name: formData.get('first_name'),
            middle_name: formData.get('middle_name'),
            last_name: formData.get('last_name'),
            age: formData.get('age'),
            gender: formData.get('gender'),
            is_foreigner: formData.get('is_foreigner') === '1',
            phone: formData.get('phone'),
            email: formData.get('email'),
        };
        checkInCompanions.push(companion);
        renderCheckInCompanions();
        closeCheckInCompanionModal();
    });

    checkInCompanionCloseButtons.forEach((button) => {
        button.addEventListener('click', closeCheckInCompanionModal);
    });

    const openCheckInModal = (reservationId) => {
        pendingReservationId = reservationId;
        checkInCompanions = [];
        primaryGuestToUpdate = null;
        existingReservationGuests = [];

        // Get reservation data
        const reservation = reservationData[reservationId];
        currentReservationData = reservation;
        
        if (reservation && reservation.reservation_guests) {
            existingReservationGuests = [...reservation.reservation_guests];

            // Find primary guest if it exists (only for updates, not for initial check-in)
            const primaryGuest = existingReservationGuests.find(g => g.is_primary_guest);
            if (primaryGuest && primaryGuest.customer) {
                primaryGuestToUpdate = primaryGuest;
            }
        }

        checkInForm.reset();
        
        // Always use the booker info as the main guest (booker is the primary)
        if (reservation) {
            const bookerData = {
                first_name: reservation.booker_name?.split(' ')[0] || '',
                last_name: reservation.booker_name?.split(' ').slice(1).join(' ') || '',
                email: reservation.email || '',
                phone: reservation.phone || '',
            };
            fillFormWithGuestData(bookerData, 'check_in_primary_guest');
        }

        checkInForm.querySelector('input[name="check_in_guest_mode"][value="with_primary"]').checked = true;
        toggleCheckInPrimaryGuestSection();
        renderCheckInCompanions();
        if (checkInModal) {
            checkInModal.classList.add('is-open');
            checkInModal.setAttribute('aria-hidden', 'false');
        }
    };

    const closeCheckInModal = () => {
        pendingReservationId = null;
        checkInCompanions = [];
        if (checkInModal) {
            checkInModal.classList.remove('is-open');
            checkInModal.setAttribute('aria-hidden', 'true');
        }
    };

    const parseReservationId = (text) => {
        if (!text) return null;
        try {
            const normalized = text.trim();
            const maybeUrl = normalized.includes('reservation_id=') ? normalized : `reservation_id=${normalized}`;
            const query = maybeUrl.includes('?') ? maybeUrl.split('?')[1] : maybeUrl;
            const params = new URLSearchParams(query);
            const value = params.get('reservation_id');
            return value && /^[0-9]+$/.test(value) ? value : null;
        } catch (error) {
            return null;
        }
    };

    const populateCameraOptions = (cameras) => {
        if (!qrCameraSelect) return;
        qrCameraSelect.innerHTML = cameras.map((camera) => `
            <option value="${camera.id}">${camera.label || camera.id}</option>
        `).join('');
    };

    const stopQrScanner = async () => {
        if (!html5QrCode || !qrScannerActive) return;
        try {
            await html5QrCode.stop();
        } catch (error) {
            console.warn('QR scanner stop error', error);
        }
        html5QrCode.clear();
        qrScannerActive = false;
    };

    const closeScanModal = async () => {
        await stopQrScanner();
        if (scanQrModal) {
            scanQrModal.classList.remove('is-open');
            scanQrModal.setAttribute('aria-hidden', 'true');
        }
    };

    const startQrScanner = async (cameraId) => {
        if (!qrScannerElement || !qrScannerStatus) return;

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode('qrScanner');
        }

        await stopQrScanner();

        await html5QrCode.start(
            cameraId,
            {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                experimentalFeatures: { useBarCodeDetectorIfSupported: true },
            },
            async (decodedText) => {
                const reservationId = parseReservationId(decodedText);
                if (!reservationId) {
                    qrScannerStatus.textContent = 'QR scanned, but not a recognizable reservation code.';
                    return;
                }

                qrScannerStatus.textContent = `Found reservation ${reservationId}. Looking up...`;
                await stopQrScanner();

                try {
                    const response = await fetch(`/staff/check-ins/lookup?reservation_id=${encodeURIComponent(reservationId)}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const body = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        qrScannerStatus.textContent = body.message || 'Reservation lookup failed.';
                        return;
                    }

                    if (body.reservation) {
                        reservationData[reservationId] = body.reservation;
                        await closeScanModal();

                        // Check if reservation is already checked in
                        if (body.reservation.status === 'Checked In') {
                            // Show checkout confirmation modal
                            const checkOutConfirm = confirm(
                                `Reservation ${reservationId} is already checked in.\n\nDo you want to check it out now?`
                            );
                            if (checkOutConfirm) {
                                // Auto checkout the reservation
                                try {
                                    const checkoutResponse = await fetch(`/staff/reservations/${reservationId}/check-out`, {
                                        method: 'POST',
                                        headers: {
                                            'Accept': 'application/json',
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': csrfToken,
                                            'X-Requested-With': 'XMLHttpRequest',
                                        },
                                    });

                                    const checkoutPayload = await checkoutResponse.json().catch(() => ({}));
                                    if (!checkoutResponse.ok) {
                                        window.alert(checkoutPayload.message || 'Unable to check out this reservation.');
                                    } else {
                                        window.alert('Reservation checked out successfully!');
                                        window.location.reload();
                                    }
                                } catch (checkoutError) {
                                    window.alert('Unable to check out this reservation. Please try again.');
                                }
                            } else {
                                // Open modal to view reservation details
                                openModal(reservationId);
                            }
                        } else {
                            // Proceed with normal check-in flow
                            openCheckInModal(reservationId);
                        }
                    } else {
                        qrScannerStatus.textContent = 'Reservation not found for scanned QR code.';
                    }
                } catch (lookupError) {
                    qrScannerStatus.textContent = 'Unable to fetch reservation details. Try again.';
                }
            },
            (errorMessage) => {
                qrScannerStatus.textContent = 'Scanning...';
            }
        );

        qrScannerActive = true;
        qrScannerStatus.textContent = 'Scanning for QR code. Hold the QR in front of the camera.';
    };

    const openScanModal = async () => {
        if (!scanQrModal || !qrScannerElement || !qrScannerStatus) return;
        scanQrModal.classList.add('is-open');
        scanQrModal.setAttribute('aria-hidden', 'false');
        qrScannerStatus.textContent = 'Initializing camera...';

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode('qrScanner');
        }

        try {
            const cameras = await Html5Qrcode.getCameras();
            if (!cameras?.length) {
                throw new Error('No camera device found.');
            }

            populateCameraOptions(cameras);
            const preferredCamera = cameras.find((camera) => /back|rear|environment/i.test(camera.label));
            const externalCamera = cameras.find((camera) => !/front|integrated|face|webcam/i.test(camera.label));
            const cameraId = qrCameraSelect?.value || preferredCamera?.id || externalCamera?.id || cameras[0].id;
            if (qrCameraSelect) {
                qrCameraSelect.value = cameraId;
            }

            await startQrScanner(cameraId);
        } catch (error) {
            qrScannerStatus.textContent = `Camera error: ${error.message || 'Unable to access camera.'}`;
        }
    };

    qrCameraSelect?.addEventListener('change', async () => {
        const cameraId = qrCameraSelect.value;
        try {
            await startQrScanner(cameraId);
        } catch (error) {
            qrScannerStatus.textContent = `Camera error: ${error.message || 'Unable to start selected camera.'}`;
        }
    });

    scanQrBtn?.addEventListener('click', () => {
        openScanModal();
    });

    stopQrBtn?.addEventListener('click', async () => {
        await closeScanModal();
    });

    scanQrCloseButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            await closeScanModal();
        });
    });

    // Bind beforeunload once so SPA re-inits don't stack handlers
    if (!window.__staffReservationsUnloadBound) {
        window.__staffReservationsUnloadBound = true;
        window.addEventListener('beforeunload', async () => {
            await stopQrScanner();
        });
        // Stop the camera when the SPA router swaps away from this page.
        window.addEventListener('spa:leaving', async () => {
            await stopQrScanner();
        });
    }

    const openModal = (reservationId) => {
        const reservation = reservationData?.[reservationId] ?? null;
        if (!reservation) {
            modalBody.innerHTML = '<p class="guest-empty">No reservation details available.</p>';
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            return;
        }

        // Store current reservation ID for edit functionality
        currentModalReservationId = reservationId;

        // Ensure modal body is visible and edit form is hidden
        modalBody.hidden = false;
        const editForm = document.getElementById('reservationModalEditForm');
        if (editForm) {
            editForm.hidden = true;
        }

        // Format reservation date to remove timezone suffix
        const formatDate = (dateStr) => {
            if (!dateStr) return 'N/A';
            return dateStr.replace(/T.*$/, '').replace(/Z$/, '');
        };

        const guests = (reservation.reservation_guests || []).map((guest) => {
            const name = [guest.customer?.first_name, guest.customer?.last_name].filter(Boolean).join(' ').trim() || 'Unnamed guest';
            const role = guest.is_primary_guest ? 'Primary Guest' : 'Companion';
            return `
                <div class="guest-relationship-item">
                    <div class="guest-relationship-label">${escapeHtml(role)}</div>
                    <div class="guest-relationship-name">${escapeHtml(name)}</div>
                    <div class="guest-meta">${escapeHtml(guest.customer?.email || 'No email')}</div>
                </div>
            `;
        }).join('');

        const amenities = (reservation.reservation_amenities || []).map((amenity) => {
            const name = amenity.amenity?.amenities_name || 'Unknown amenity';
            return `<li>${escapeHtml(name)} — ${escapeHtml(amenity.pricing_type)} · ₱${Number(amenity.price_at_booking || 0).toFixed(2)}</li>`;
        }).join('');

        modalStatus.textContent = reservation.status;
        modalStatus.className = `guest-modal__role-badge reservation-status reservation-status--${String(reservation.status || '').toLowerCase()}`;
        modalBody.innerHTML = `
            <div class="guest-card">
                <div class="guest-card__grid">
                    <div>
                        <span class="guest-label">Booker</span>
                        <div class="guest-value">${escapeHtml(reservation.booker_name || 'N/A')}</div>
                    </div>
                    <div>
                        <span class="guest-label">Contact</span>
                        <div class="guest-value">${escapeHtml(reservation.phone || 'N/A')}<br>${escapeHtml(reservation.email || 'N/A')}</div>
                    </div>
                    <div>
                        <span class="guest-label">Reservation date</span>
                        <div class="guest-value">${escapeHtml(formatDate(reservation.reservation_date))}</div>
                    </div>
                    <div>
                        <span class="guest-label">Guests</span>
                        <div class="guest-value">${escapeHtml(reservation.number_of_guests || 'N/A')}</div>
                    </div>
                    <div>
                        <span class="guest-label">Payment</span>
                        <div class="guest-value">₱${Number(reservation.total_amount || 0).toFixed(2)} · Paid ₱${Number(reservation.amount_paid || 0).toFixed(2)} · Balance ₱${Number(reservation.remaining_balance || 0).toFixed(2)}</div>
                    </div>
                    <div>
                        <span class="guest-label">Payment Status</span>
                        <div class="guest-value">${escapeHtml(reservation.payment_status || 'N/A')}</div>
                    </div>
                </div>
                <div style="margin-top:0.75rem;">
                    <div class="guest-relationship-header">Guests on this reservation</div>
                    <div class="guest-relationship-list">${guests || '<div class="guest-relationship-item"><div class="guest-relationship-name">No guest details listed.</div></div>'}</div>
                </div>
                <div style="margin-top:0.75rem;">
                    <span class="guest-label">Reserved Amenities</span>
                    <ul class="guest-list">${amenities || '<li>No amenities listed.</li>'}</ul>
                </div>
                <div class="guest-form__actions" style="margin-top:0.75rem;">
                    ${reservation.status === 'Checked In' ? `<button type="button" class="guest-form__button" id="reservationCheckOutBtn" data-reservation-checkout="${reservation.id}">Check Out</button>` : `<button type="button" class="guest-form__button" data-open-check-in-modal="${reservation.id}">Check In</button>`}
                </div>
            </div>
        `;

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    };

    const closeModal = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        const editForm = document.getElementById('reservationModalEditForm');
        if (editForm) {
            editForm.hidden = true;
        }
    };

    const renderTableFromData = (data) => {
        tableBody.innerHTML = '';
        Object.values(data).forEach((reservation) => {
            const row = document.createElement('tr');
            row.className = `guest-row reservation-row ${reservation.reservation_date === new Date().toISOString().split('T')[0] ? 'today-reservation' : ''}`;
            row.setAttribute('data-reservation-id', reservation.id);
            row.setAttribute('data-booker-name', reservation.booker_name);
            row.setAttribute('data-email', reservation.email);
            row.setAttribute('data-phone', reservation.phone);
            row.setAttribute('data-reservation-date', reservation.reservation_date);
            row.setAttribute('data-status', reservation.status.toLowerCase());
            row.setAttribute('data-guests', reservation.number_of_guests);
            row.setAttribute('data-total-amount', reservation.total_amount);
            row.setAttribute('data-search', `${(reservation.booker_name || '').toLowerCase()} ${(reservation.email || '').toLowerCase()} ${(reservation.phone || '').toLowerCase()} ${(reservation.status || '').toLowerCase()}`);
            row.setAttribute('tabindex', '0');
            row.setAttribute('role', 'button');
            row.setAttribute('aria-label', `View reservation details for ${reservation.booker_name}`);

            // Format date to readable format (e.g., September 2, 2023)
            const formatDate = (dateStr) => {
                if (!dateStr) return 'N/A';
                const date = new Date(dateStr);
                if (isNaN(date.getTime())) return dateStr;
                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                return date.toLocaleDateString('en-US', options);
            };
            
            row.innerHTML = buildRowCells(reservation);
            
            tableBody.appendChild(row);
            
            // Add click event listener
            row.addEventListener('click', () => openModal(reservation.id));
            row.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openModal(reservation.id);
                }
            });
        });

        // Update results count
        if (resultsCount) {
            resultsCount.textContent = `Showing ${Object.values(data).length} reservation${Object.values(data).length === 1 ? '' : 's'}`;
        }

        // Re-apply current filters
        applyFilters();
    };

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    // Edit reservation functionality
    const editReservationBtn = document.getElementById('editReservationBtn');
    const editForm = document.getElementById('reservationModalEditForm');
    const editReservationForm = document.getElementById('editReservationForm');
    const cancelEditBtn = document.getElementById('cancelEditBtn');

    // Confirmation modal functionality
    const confirmModal = document.getElementById('confirmModal');
    const confirmModalTitle = document.getElementById('confirmModalTitle');
    const confirmModalMessage = document.getElementById('confirmModalMessage');
    const confirmModalConfirm = document.getElementById('confirmModalConfirm');
    const confirmModalCancel = document.getElementById('confirmModalCancel');
    const confirmModalCloseButtons = document.querySelectorAll('[data-close-confirm-modal="true"]');

    let confirmCallback = null;

    const showConfirmModal = (title, message, callback) => {
        if (confirmModalTitle) confirmModalTitle.textContent = title;
        if (confirmModalMessage) confirmModalMessage.textContent = message;
        confirmCallback = callback;
        confirmModal.classList.add('is-open');
        confirmModal.setAttribute('aria-hidden', 'false');
    };

    const closeConfirmModal = () => {
        confirmModal.classList.remove('is-open');
        confirmModal.setAttribute('aria-hidden', 'true');
        confirmCallback = null;
    };

    confirmModalCloseButtons.forEach((button) => {
        button.addEventListener('click', closeConfirmModal);
    });

    confirmModalCancel?.addEventListener('click', closeConfirmModal);

    confirmModalConfirm?.addEventListener('click', () => {
        if (confirmCallback) {
            confirmCallback();
        }
        closeConfirmModal();
    });

    // Success modal functionality
    const successModal = document.getElementById('successModal');
    const successModalTitle = document.getElementById('successModalTitle');
    const successModalMessage = document.getElementById('successModalMessage');
    const successModalClose = document.getElementById('successModalClose');
    const successModalCloseButtons = document.querySelectorAll('[data-close-success-modal="true"]');

    const showSuccessModal = (message) => {
        if (successModalMessage) successModalMessage.textContent = message;
        successModal.classList.add('is-open');
        successModal.setAttribute('aria-hidden', 'false');
    };

    const closeSuccessModal = () => {
        successModal.classList.remove('is-open');
        successModal.setAttribute('aria-hidden', 'true');
    };

    successModalCloseButtons.forEach((button) => {
        button.addEventListener('click', closeSuccessModal);
    });

    successModalClose?.addEventListener('click', closeSuccessModal);

    const openEditForm = (reservationId) => {
        const reservation = reservationData?.[reservationId];
        if (!reservation || !editForm) return;

        // Reset submit button state
        const submitButton = editReservationForm.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = 'Save Changes';
        }

        // Format date for date input (YYYY-MM-DD)
        const formatDateForInput = (dateStr) => {
            if (!dateStr) return '';
            return dateStr.replace(/T.*$/, '').replace(/Z$/, '');
        };

        // Populate form fields
        document.getElementById('editReservationId').value = reservation.id;
        document.getElementById('editBookerName').value = reservation.booker_name || '';
        document.getElementById('editEmail').value = reservation.email || '';
        document.getElementById('editPhone').value = reservation.phone || '';
        document.getElementById('editReservationDate').value = formatDateForInput(reservation.reservation_date);
        document.getElementById('editGuests').value = reservation.number_of_guests || '';
        document.getElementById('editStatus').value = reservation.status || 'Pending';

        // Hide body, show edit form
        modalBody.hidden = true;
        editForm.hidden = false;
    };

    const closeEditForm = () => {
        if (editForm) {
            editForm.hidden = true;
        }
        if (modalBody) {
            modalBody.hidden = false;
        }
    };

    editReservationBtn?.addEventListener('click', () => {
        if (currentModalReservationId) {
            openEditForm(currentModalReservationId);
        }
    });

    // Refresh table functionality
    refreshTableBtn?.addEventListener('click', async () => {
        try {
            // Show skeleton loading
            const skeletonCount = Math.min(5, Object.keys(reservationData).length || 5);
            tableBody.innerHTML = '';
            for (let i = 0; i < skeletonCount; i++) {
                const skeletonRow = document.createElement('tr');
                skeletonRow.className = 'guest-row guest-row--skeleton';
                skeletonRow.innerHTML = `
                    <td>
                        <div class="skeleton skeleton-text skeleton-text--medium"></div>
                        <div class="skeleton skeleton-text skeleton-text--short"></div>
                    </td>
                    <td>
                        <div class="skeleton skeleton-text skeleton-text--short"></div>
                    </td>
                    <td>
                        <div class="skeleton skeleton-badge"></div>
                    </td>
                    <td>
                        <div class="skeleton skeleton-text skeleton-text--short"></div>
                    </td>
                    <td>
                        <div class="skeleton skeleton-badge"></div>
                    </td>
                    <td>
                        <div class="skeleton skeleton-text skeleton-text--short"></div>
                    </td>
                    <td>
                        <div class="skeleton resv-skeleton-action"></div>
                    </td>
                `;
                tableBody.appendChild(skeletonRow);
            }

            const response = await fetch('/staff/reservations/refresh', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to refresh reservations');
            }

            const data = await response.json();
            
            // Update reservation data
            if (data.reservations) {
                // Update global reservationData
                Object.assign(reservationData, data.reservations);
                
                // Convert object to array for iteration
                const reservationsArray = Object.values(data.reservations);
                
                // Format date to readable format (e.g., September 2, 2023)
                const formatDate = (dateStr) => {
                    if (!dateStr) return 'N/A';
                    const date = new Date(dateStr);
                    if (isNaN(date.getTime())) return dateStr;
                    const options = { year: 'numeric', month: 'long', day: 'numeric' };
                    return date.toLocaleDateString('en-US', options);
                };
                
                // Re-render table rows
                tableBody.innerHTML = '';
                reservationsArray.forEach((reservation) => {
                    const row = document.createElement('tr');
                    row.className = `guest-row reservation-row ${reservation.reservation_date === new Date().toISOString().split('T')[0] ? 'today-reservation' : ''}`;
                    row.setAttribute('data-reservation-id', reservation.id);
                    row.setAttribute('data-booker-name', reservation.booker_name);
                    row.setAttribute('data-email', reservation.email);
                    row.setAttribute('data-phone', reservation.phone);
                    row.setAttribute('data-reservation-date', reservation.reservation_date);
                    row.setAttribute('data-status', reservation.status.toLowerCase());
                    row.setAttribute('data-guests', reservation.number_of_guests);
                    row.setAttribute('data-total-amount', reservation.total_amount);
                    row.setAttribute('data-search', `${(reservation.booker_name || '').toLowerCase()} ${(reservation.email || '').toLowerCase()} ${(reservation.phone || '').toLowerCase()} ${(reservation.status || '').toLowerCase()}`);
                    row.setAttribute('tabindex', '0');
                    row.setAttribute('role', 'button');
                    row.setAttribute('aria-label', `View reservation details for ${reservation.booker_name}`);
                    
                    row.innerHTML = buildRowCells(reservation);
                    
                    tableBody.appendChild(row);
                    
                    // Add click event listener
                    row.addEventListener('click', () => openModal(reservation.id));
                    row.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            openModal(reservation.id);
                        }
                    });
                });

                // Update results count
                if (resultsCount) {
                    resultsCount.textContent = `Showing ${reservationsArray.length} reservation${reservationsArray.length === 1 ? '' : 's'}`;
                }

                // Re-apply current filters
                applyFilters();
            } else {
                throw new Error('No reservation data received');
            }
        } catch (error) {
            console.error('Error refreshing table:', error);
            window.alert('Failed to refresh table. Please try again.');
            // Re-render existing data to prevent empty table
            renderTableFromData(reservationData);
        }
    });

    cancelEditBtn?.addEventListener('click', closeEditForm);

    editReservationForm?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(editReservationForm);
        const reservationId = formData.get('reservation_id');
        const submitButton = editReservationForm.querySelector('button[type="submit"]');

        // Show custom confirmation modal
        showConfirmModal(
            'Save Changes',
            'Are you sure you want to save these changes to the reservation?',
            async () => {
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Saving...';
                }

                try {
                    const response = await fetch(`/staff/reservations/${reservationId}/update`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            booker_name: formData.get('booker_name'),
                            email: formData.get('email'),
                            phone: formData.get('phone'),
                            reservation_date: formData.get('reservation_date'),
                            number_of_guests: formData.get('number_of_guests'),
                            status: formData.get('status'),
                        }),
                    });

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(payload.message || 'Unable to update reservation.');
                    }

                    // Update local data
                    if (reservationData[reservationId]) {
                        reservationData[reservationId] = {
                            ...reservationData[reservationId],
                            booker_name: formData.get('booker_name'),
                            email: formData.get('email'),
                            phone: formData.get('phone'),
                            reservation_date: formData.get('reservation_date'),
                            number_of_guests: formData.get('number_of_guests'),
                            status: formData.get('status'),
                        };
                    }

                    // Update table row with new data
                    const tableRow = document.querySelector(`tr[data-reservation-id="${reservationId}"]`);
                    if (tableRow) {
                        tableRow.setAttribute('data-booker-name', formData.get('booker_name'));
                        tableRow.setAttribute('data-email', formData.get('email'));
                        tableRow.setAttribute('data-phone', formData.get('phone'));
                        tableRow.setAttribute('data-reservation-date', formData.get('reservation_date'));
                        tableRow.setAttribute('data-status', formData.get('status').toLowerCase());
                        tableRow.setAttribute('data-guests', formData.get('number_of_guests'));

                        // Format date to readable format (e.g., September 2, 2023)
                        const formatDate = (dateStr) => {
                            if (!dateStr) return 'N/A';
                            const date = new Date(dateStr);
                            if (isNaN(date.getTime())) return dateStr;
                            const options = { year: 'numeric', month: 'long', day: 'numeric' };
                            return date.toLocaleDateString('en-US', options);
                        };

                        // Update table cell content
                        const cells = tableRow.querySelectorAll('td');
                        if (cells[0]) {
                            cells[0].innerHTML = `
                                <div class="resv-booker">
                                    <span class="resv-avatar">${escapeHtml(getInitials(formData.get('booker_name')))}</span>
                                    <div class="resv-booker__info">
                                        <div class="guest-name">${escapeHtml(formData.get('booker_name'))}</div>
                                        <div class="guest-meta">${escapeHtml(formData.get('email'))}</div>
                                    </div>
                                </div>
                            `;
                        }
                        if (cells[1]) {
                            cells[1].textContent = formatDate(formData.get('reservation_date'));
                        }
                        if (cells[3]) {
                            cells[3].textContent = formData.get('number_of_guests');
                        }
                        if (cells[4]) {
                            const status = formData.get('status');
                            cells[4].innerHTML = `<span class="reservation-status reservation-status--${String(status || '').toLowerCase()}">${status}</span>`;
                        }
                    }

                    // Close modal after successful update
                    closeModal();
                    showSuccessModal('Reservation updated successfully!');
                } catch (error) {
                    window.alert(error.message || 'Unable to update reservation.');
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = 'Save Changes';
                    }
                }
            }
        );
    });

    checkInCloseButtons.forEach((button) => {
        button.addEventListener('click', closeCheckInModal);
    });

    const checkOutReservation = async (reservationId) => {
        showConfirmModal(
            'Check Out Reservation',
            'Are you sure you want to check out this reservation? All guests will be marked as checked out.',
            async () => {
                try {
                    const response = await fetch(`/staff/reservations/${reservationId}/check-out`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(payload.message || 'Unable to check out this reservation.');
                    }

                    showSuccessModal('Reservation checked out successfully!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } catch (error) {
                    window.alert(error.message || 'Unable to check out this reservation.');
                }
            }
        );
    };

    const allGuestsCheckedOut = (reservation) => {
        if (!reservation.reservation_guests || reservation.reservation_guests.length === 0) {
            return false;
        }
        return reservation.reservation_guests.every(guest => guest.checked_out_at);
    };

    modalBody.addEventListener('click', (event) => {
        const checkOutTrigger = event.target.closest('[data-reservation-checkout]');
        if (checkOutTrigger) {
            checkOutReservation(checkOutTrigger.getAttribute('data-reservation-checkout'));
            return;
        }

        const trigger = event.target.closest('[data-open-check-in-modal]');
        if (!trigger) {
            return;
        }

        openCheckInModal(trigger.getAttribute('data-open-check-in-modal'));
    });

    checkInForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!pendingReservationId) {
            return;
        }

        // Show companion summary modal instead of directly submitting
        openCompanionSummaryModal();
    });

    const submitCheckInForm = async () => {
        if (!pendingReservationId) {
            return;
        }

        const submitButton = checkInForm.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Checking in...';
        }

        const formData = new FormData(checkInForm);
        const guestMode = formData.get('check_in_guest_mode');
        const primaryGuest = guestMode === 'with_primary' ? {
            first_name: formData.get('check_in_primary_guest[first_name]'),
            middle_name: formData.get('check_in_primary_guest[middle_name]'),
            last_name: formData.get('check_in_primary_guest[last_name]'),
            age: formData.get('check_in_primary_guest[age]'),
            gender: formData.get('check_in_primary_guest[gender]'),
            is_foreigner: formData.get('check_in_primary_guest[is_foreigner]') === '1',
            phone: formData.get('check_in_primary_guest[phone]'),
            email: formData.get('check_in_primary_guest[email]'),
        } : null;

        try {
            const response = await fetch(`/staff/reservations/${pendingReservationId}/check-in`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    guest_mode: guestMode,
                    primary_guest: primaryGuest,
                    primary_guest_id: primaryGuestToUpdate?.customer_id || null,
                    companions: checkInCompanions,
                }),
            });

            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(payload.message || 'Unable to check in this reservation.');
            }

            window.location.reload();
        } catch (error) {
            window.alert(error.message || 'Unable to check in this reservation.');
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = 'Check In';
            }
        }
    };

    rows.forEach((row) => {
        const openForRow = () => {
            openModal(row.dataset.reservationId);
        };

        row.addEventListener('click', openForRow);
        row.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openForRow();
            }
        });
    });

    const applyFilters = () => {
        const query = searchInput?.value.trim().toLowerCase() || '';
        const sortValue = sortSelect?.value || 'date-asc';
        const statusValue = statusFilter?.value || 'all';
        const checkInFromValue = checkInFrom?.value || '';
        const checkInToValue = checkInTo?.value || '';

        let filteredRows = rows.filter((row) => {
            const searchText = (row.getAttribute('data-search') || '').toLowerCase();
            const matchesSearch = !query || searchText.includes(query);
            const matchesStatus = statusValue === 'all' || row.getAttribute('data-status') === statusValue;
            const reservationDate = row.getAttribute('data-reservation-date') || '';
            const matchesCheckInFrom = !checkInFromValue || !reservationDate || reservationDate >= checkInFromValue;
            const matchesCheckInTo = !checkInToValue || !reservationDate || reservationDate <= checkInToValue;
            return matchesSearch && matchesStatus && matchesCheckInFrom && matchesCheckInTo;
        });

        filteredRows.sort((left, right) => {
            const leftName = (left.getAttribute('data-booker-name') || '').trim().toLowerCase();
            const rightName = (right.getAttribute('data-booker-name') || '').trim().toLowerCase();
            const leftDate = left.getAttribute('data-reservation-date') || '';
            const rightDate = right.getAttribute('data-reservation-date') || '';
            const leftAmount = Number(left.getAttribute('data-total-amount') || 0);
            const rightAmount = Number(right.getAttribute('data-total-amount') || 0);

            switch (sortValue) {
                case 'date-desc':
                    return rightDate.localeCompare(leftDate);
                case 'name-asc':
                    return leftName.localeCompare(rightName);
                case 'name-desc':
                    return rightName.localeCompare(leftName);
                case 'amount-desc':
                    return rightAmount - leftAmount;
                case 'date-asc':
                default:
                    return leftDate.localeCompare(rightDate);
            }
        });

        rows.forEach((row) => {
            row.classList.add('is-hidden');
            row.style.display = 'none';
        });

        filteredRows.forEach((row) => {
            row.classList.remove('is-hidden');
            row.style.display = '';
            tableBody.appendChild(row);
        });

        if (resultsCount) {
            resultsCount.textContent = `Showing ${filteredRows.length} of ${rows.length} reservation${rows.length === 1 ? '' : 's'}`;
        }
    };

    [searchInput, sortSelect, statusFilter, checkInFrom, checkInTo].forEach((control) => {
        control?.addEventListener('input', applyFilters);
        control?.addEventListener('change', applyFilters);
    });

    clearButton?.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        if (sortSelect) sortSelect.value = 'date-asc';
        if (statusFilter) statusFilter.value = 'all';
        if (checkInFrom) checkInFrom.value = '';
        if (checkInTo) checkInTo.value = '';
        applyFilters();
    });

    filterToggle?.addEventListener('click', () => {
        const isExpanded = filterToggle.getAttribute('aria-expanded') === 'true';
        filterToggle.setAttribute('aria-expanded', String(!isExpanded));
        filterPanel?.toggleAttribute('hidden', isExpanded);
        filterPanel?.classList.toggle('guest-toolbar--collapsed', isExpanded);
    });

    // ------------------------------------------------------------
    // Live clock + session badge (park settings come from data-park-settings)
    // ------------------------------------------------------------
    const resvMetricsEl = document.querySelector('.resv-metrics');
    const resvTimeEl = document.getElementById('resvTime');
    const resvSessionEl = document.getElementById('resvSession');
    let resvParkSettings = {};
    try {
        resvParkSettings = JSON.parse(resvMetricsEl?.dataset.parkSettings || '{}');
    } catch (e) { /* ignore */ }

    const updateResvClock = () => {
        const now = new Date();
        if (resvTimeEl) {
            resvTimeEl.textContent = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        }
        if (resvSessionEl && resvParkSettings.nighttime_start && resvParkSettings.nighttime_end) {
            const cur = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
            const { nighttime_start: ns, nighttime_end: ne } = resvParkSettings;
            let period = 'Daytime';
            if (ns <= ne) {
                if (cur >= ns && cur <= ne) period = 'Nighttime';
            } else {
                if (cur >= ns || cur <= ne) period = 'Nighttime';
            }
            const label = period.toUpperCase();
            if (resvSessionEl.textContent !== label) {
                resvSessionEl.textContent = label;
                resvSessionEl.className = `resv-metric__badge resv-metric__badge--${period.toLowerCase()}`;
            }
        }
    };
    // Clear any previous timer so SPA re-inits don't stack intervals
    if (window.__resvClockTimer) clearInterval(window.__resvClockTimer);
    window.__resvClockTimer = setInterval(updateResvClock, 1000);
    updateResvClock();

    // ------------------------------------------------------------
    // CSV export (exports the currently visible/filtered rows)
    // ------------------------------------------------------------
    const exportCsvBtn = document.getElementById('exportCsvBtn');
    exportCsvBtn?.addEventListener('click', () => {
        const visibleRows = Array.from(document.querySelectorAll('#reservationTableBody .reservation-row'))
            .filter(row => row.style.display !== 'none');
        if (!visibleRows.length) {
            window.alert('No reservations to export.');
            return;
        }
        const header = ['Booker', 'Email', 'Reservation Date', 'Session', 'Guests', 'Status', 'Amount'];
        const body = visibleRows.map(row => {
            const cells = row.querySelectorAll('td');
            return [
                row.dataset.bookerName || '',
                row.dataset.email || '',
                cells[1]?.textContent.trim() || '',
                (cells[2]?.textContent || '').replace(/\s+/g, ' ').trim(),
                cells[3]?.textContent.trim() || '',
                cells[4]?.textContent.trim() || '',
                cells[5]?.textContent.trim() || '',
            ];
        });
        const csv = [header, ...body]
            .map(row => row.map(value => `"${String(value ?? '').replace(/"/g, '""')}"`).join(','))
            .join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `reservations-${new Date().toISOString().slice(0, 10)}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    });

    // ------------------------------------------------------------
    // Add Walk-in → Check-Ins page (where walk-in guests are created)
    // ------------------------------------------------------------
    document.getElementById('addWalkInBtn')?.addEventListener('click', () => {
        window.location.href = '/staff/check-ins';
    });

    applyFilters();
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['staff_reservations']());