import { Html5Qrcode } from 'html5-qrcode';
import { queueToast, showPendingToast, convertFlashToToast } from './toast.js';

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
    const bulkCompanionBtnMinus = document.getElementById('bulkCompanionBtnMinus');
    const bulkCompanionBtnPlus = document.getElementById('bulkCompanionBtnPlus');
    const bulkQuantityInput = document.getElementById('bulkCompanionQuantity');

    if (bulkCompanionBtnMinus && bulkQuantityInput) {
        bulkCompanionBtnMinus.addEventListener('click', () => {
            let val = parseInt(bulkQuantityInput.value, 10) || 1;
            if (val > 1) {
                bulkQuantityInput.value = val - 1;
            }
        });
    }

    if (bulkCompanionBtnPlus && bulkQuantityInput) {
        bulkCompanionBtnPlus.addEventListener('click', () => {
            let val = parseInt(bulkQuantityInput.value, 10) || 1;
            if (val < parseInt(bulkQuantityInput.max || 50, 10)) {
                bulkQuantityInput.value = val + 1;
            }
        });
    }

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
    let rows = Array.from(tableBody?.querySelectorAll('.reservation-row') ?? []);
    const updateRowsReference = () => {
        rows = Array.from(tableBody?.querySelectorAll('.reservation-row') ?? []);
    };
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

    // Shared row markup (used by server rows, refresh + fallback renders)
    const buildRowCells = (reservation) => {
        const formatDate = (dateStr, endDateStr, totalDays) => {
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr;
            const sFormatted = date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            if (endDateStr && endDateStr !== dateStr) {
                const endDate = new Date(endDateStr);
                const eFormatted = endDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                const daysCount = totalDays || (Math.round((endDate - date) / (1000 * 60 * 60 * 24)) + 1);
                return `<div><span style="font-weight:600;">${escapeHtml(sFormatted)} – ${escapeHtml(eFormatted)}</span><div style="font-size:0.75rem;opacity:0.75;">(${daysCount} Days Stay)</div></div>`;
            }
            return escapeHtml(sFormatted);
        };
        return `
                <td class="py-3.5 px-3 w-20 whitespace-nowrap">
                    <span class="inline-flex items-center rounded-lg bg-[#e8f5e9] px-2 py-0.5 text-xs font-bold text-[#1b4332] font-mono dark:bg-[rgba(46,125,50,0.25)] dark:text-[#81c784]">#${escapeHtml(reservation.id)}</span>
                </td>
                <td>
                    <div class="resv-booker">
                        <span class="resv-avatar">${escapeHtml(getInitials(reservation.booker_name))}</span>
                        <div class="resv-booker__info">
                            <div class="guest-name">${escapeHtml(reservation.booker_name)}</div>
                            <div class="guest-meta">${escapeHtml(reservation.email)}</div>
                        </div>
                    </div>
                </td>
                <td>${formatDate(reservation.reservation_date, reservation.end_date, reservation.total_days)}</td>
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
        const allCompanions = getAllCheckInCompanions();

        allCompanions.forEach((companion, index) => {
            Object.entries(companion).forEach(([key, value]) => {
                const field = document.createElement('input');
                field.type = 'hidden';
                field.name = `check_in_companions[${index}][${key}]`;
                field.value = value;
                checkInCompanionHiddenFields.appendChild(field);
            });
        });

        updateCheckInFeeSummary();
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

        const { adultCount, childCount, adultRate, childRate, poolTotal, entranceTotal, total } = computeCheckInEntrance();
        const balance = Number(currentReservationData?.remaining_balance || 0);
        const poolChecked = document.getElementById('checkInIncludePool')?.checked || false;
        const grandTotal = total + balance;

        let html = `<p>Are you sure you want to check in <strong>Reservation #${escapeHtml(currentReservationData?.id || pendingReservationId)}</strong> now?</p>`;

        html += `
            <div style="margin-top: 1rem; border: 1px solid #e2e8f0; border-radius: 0.6rem; overflow: hidden;">
                <div style="padding: 0.65rem 0.9rem; background: #f8fafc; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">Payment Summary</div>
                <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.6rem 0.9rem; color: #334155;">
                    <span style="font-size: 0.85rem;">Entrance fee</span>
                    <strong style="font-size: 0.85rem;">₱${entranceTotal.toFixed(2)}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.6rem 0.9rem; border-top: 1px solid #eef2f7; color: #94a3b8; font-size: 0.8rem;">
                    <span>${adultCount} adult${adultCount === 1 ? '' : 's'} × ₱${adultRate.toFixed(2)} + ${childCount} child${childCount === 1 ? '' : 'ren'} × ₱${childRate.toFixed(2)}</span>
                    <span>₱${entranceTotal.toFixed(2)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.6rem 0.9rem; border-top: 1px solid #eef2f7; color: #334155;">
                    <span style="font-size: 0.85rem;">Pool access</span>
                    <strong style="font-size: 0.85rem;">${poolChecked ? `₱${poolTotal.toFixed(2)}` : 'Not included'}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.6rem 0.9rem; border-top: 1px solid #eef2f7; color: #334155;">
                    <span style="font-size: 0.85rem;">Remaining reservation balance</span>
                    <strong style="font-size: 0.85rem;">₱${balance.toFixed(2)}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.75rem 0.9rem; background: #f0fdf4; border-top: 1px solid #e2e8f0;">
                    <strong style="font-size: 0.95rem;">Total to pay</strong>
                    <strong style="font-size: 0.95rem; color: #16a34a;">₱${grandTotal.toFixed(2)}</strong>
                </div>
            </div>
            <p style="margin-top: 0.75rem; font-size: 0.8rem; color: #64748b;">This reservation is partially paid. Paying this total settles the remaining balance and marks the reservation as <strong>Paid</strong>.</p>
        `;

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

    // ── Entrance fee computation for online reservation check-in ──────────
    // Mirrors the server-side logic on the check-ins page: adult = over 12,
    // child = 12 and below. The entrance period follows the reservation's
    // first amenity (amenities carry their own pricing_type) and falls back
    // to the current park session when there are no amenities. Pool access is
    // a one-time fee per reservation, matching the server computation.
    let checkInParkSettings = {
        daytime_adult_entrance_fee: 0,
        daytime_child_entrance_fee: 0,
        nighttime_adult_entrance_fee: 0,
        nighttime_child_entrance_fee: 0,
        day_pool_fee: 0,
        night_pool_fee: 0,
        daytime_start: '06:00',
        daytime_end: '18:00',
        nighttime_start: '18:00',
        nighttime_end: '06:00',
    };
    try {
        const raw = JSON.parse(document.querySelector('.resv-metrics')?.dataset.parkSettings || '{}');
        checkInParkSettings = { ...checkInParkSettings, ...raw };
    } catch (e) { /* ignore */ }

    const parseCheckInTime = (timeStr) => {
        const [hours, minutes] = String(timeStr || '').split(':').map(Number);
        return (hours || 0) * 60 + (minutes || 0);
    };

    const getCurrentCheckInSession = () => {
        const now = new Date();
        const current = now.getHours() * 60 + now.getMinutes();
        const start = parseCheckInTime(checkInParkSettings.daytime_start);
        const end = parseCheckInTime(checkInParkSettings.daytime_end);
        return current >= start && current < end ? 'daytime' : 'nighttime';
    };

    const getCheckInEffectivePeriod = () => {
        const reservation = currentReservationData;
        const firstAmenity = (reservation?.reservation_amenities || [])[0];
        const pricingType = firstAmenity?.pricing_type || '';
        if (pricingType.includes('NightToDay') || pricingType.includes('DayToNight')) return 'daytonight';
        if (pricingType.includes('Nighttime')) return 'nighttime';
        if (pricingType.includes('Daytime')) return 'daytime';
        return getCurrentCheckInSession();
    };

    const getCheckInPeriodLabel = (period) => {
        const labels = { daytime: 'Daytime', nighttime: 'Nighttime', daytonight: 'Day to Night', nighttoday: 'Night to Day' };
        return labels[period] || period;
    };

    const computeCheckInEntrance = () => {
        const period = getCheckInEffectivePeriod();
        const ps = checkInParkSettings;

        let adultRate = 0;
        let childRate = 0;
        let poolRate = 0;
        if (period === 'nighttime') {
            adultRate = Number(ps.nighttime_adult_entrance_fee) || 0;
            childRate = Number(ps.nighttime_child_entrance_fee) || 0;
            poolRate = Number(ps.night_pool_fee) || 0;
        } else if (period === 'daytonight' || period === 'nighttoday') {
            adultRate = (Number(ps.daytime_adult_entrance_fee) || 0) + (Number(ps.nighttime_adult_entrance_fee) || 0);
            childRate = (Number(ps.daytime_child_entrance_fee) || 0) + (Number(ps.nighttime_child_entrance_fee) || 0);
            poolRate = (Number(ps.day_pool_fee) || 0) + (Number(ps.night_pool_fee) || 0);
        } else {
            adultRate = Number(ps.daytime_adult_entrance_fee) || 0;
            childRate = Number(ps.daytime_child_entrance_fee) || 0;
            poolRate = Number(ps.day_pool_fee) || 0;
        }

        let adultCount = 0;
        let childCount = 0;
        const guestMode = checkInForm?.querySelector('input[name="check_in_guest_mode"]:checked')?.value;
        if (guestMode === 'with_primary') {
            const primaryAge = parseInt(checkInForm?.querySelector('input[name="check_in_primary_guest[age]"]')?.value, 10);
            if (!isNaN(primaryAge) && primaryAge <= 12) childCount += 1;
            else adultCount += 1;
        }
        getAllCheckInCompanions().forEach((companion) => {
            const companionAge = parseInt(companion.age, 10);
            if (!isNaN(companionAge) && companionAge <= 12) childCount += 1;
            else adultCount += 1;
        });

        const entranceTotal = adultCount * adultRate + childCount * childRate;
        const poolChecked = document.getElementById('checkInIncludePool')?.checked || false;
        const poolTotal = poolChecked ? poolRate : 0;

        return {
            period,
            adultCount,
            childCount,
            adultRate,
            childRate,
            poolTotal,
            entranceTotal,
            total: entranceTotal + poolTotal,
        };
    };

    const updateCheckInFeeSummary = () => {
        const { period, adultCount, childCount, adultRate, childRate, poolTotal, entranceTotal, total } = computeCheckInEntrance();
        const balance = Number(currentReservationData?.remaining_balance || 0);

        const badge = document.getElementById('checkInEffectivePeriodBadge');
        if (badge) badge.textContent = getCheckInPeriodLabel(period);

        const adultEl = document.getElementById('checkInAdultSummary');
        if (adultEl) adultEl.textContent = `${adultCount} × ₱${adultRate.toFixed(2)}`;
        const childEl = document.getElementById('checkInChildSummary');
        if (childEl) childEl.textContent = `${childCount} × ₱${childRate.toFixed(2)}`;
        const poolEl = document.getElementById('checkInPoolSummary');
        if (poolEl) poolEl.textContent = `₱${poolTotal.toFixed(2)}`;
        const entranceEl = document.getElementById('checkInEntranceTotal');
        if (entranceEl) entranceEl.textContent = `₱${entranceTotal.toFixed(2)}`;
        const balanceEl = document.getElementById('checkInReservationBalance');
        if (balanceEl) balanceEl.textContent = `₱${balance.toFixed(2)}`;
        const grandEl = document.getElementById('checkInGrandTotal');
        if (grandEl) grandEl.textContent = `₱${(total + balance).toFixed(2)}`;
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

    // Merges individually-added companions with bulk groups expanded into
    // individual companion records (used both for the summary UI hidden fields
    // and for the check-in payload — previously bulk groups were never sent).
    const getAllCheckInCompanions = () => {
        const allCompanions = [...checkInCompanions];
        bulkCompanionGroups.forEach((group) => {
            for (let i = 0; i < group.quantity; i++) {
                allCompanions.push({
                    first_name: 'Companion',
                    middle_name: '',
                    last_name: `C${allCompanions.length}`,
                    age: String(getAgeFromGroup(group.age_group)),
                    gender: group.gender,
                    is_foreigner: !!group.is_foreigner,
                    phone: '',
                    email: '',
                });
            }
        });
        return allCompanions;
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
        updateCheckInFeeSummary();
    };

    checkInForm?.addEventListener('change', (e) => {
        if (e.target.name === 'check_in_guest_mode') {
            toggleCheckInPrimaryGuestSection();
        }
    });

    // Live entrance-fee totals as the staff edits the primary guest's age or
    // toggles pool access.
    checkInForm?.addEventListener('input', (e) => {
        if (e.target.name === 'check_in_primary_guest[age]') {
            updateCheckInFeeSummary();
        }
    });

    document.getElementById('checkInIncludePool')?.addEventListener('change', updateCheckInFeeSummary);

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
        bulkCompanionGroups = [];
        primaryGuestToUpdate = null;
        existingReservationGuests = [];

        const checkInTitle = document.getElementById('checkInModalTitle');
        if (checkInTitle) {
            checkInTitle.textContent = `Check In Reservation #${reservationId}`;
        }

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
        updateCheckInFeeSummary();
        if (checkInModal) {
            checkInModal.classList.add('is-open');
            checkInModal.setAttribute('aria-hidden', 'false');
        }
    };

    const closeCheckInModal = () => {
        pendingReservationId = null;
        checkInCompanions = [];
        bulkCompanionGroups = [];
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
                                        queueToast(`Reservation #${reservationId} checked out successfully.`);
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

        const resvModalTitle = document.getElementById('reservationModalTitle');
        if (resvModalTitle) {
            resvModalTitle.textContent = `Reservation Details #${reservation.id}`;
        }

        // Ensure modal body is visible and edit form is hidden
        modalBody.hidden = false;
        const editForm = document.getElementById('reservationModalEditForm');
        if (editForm) {
            editForm.hidden = true;
        }

        // Format reservation date to readable format (e.g. Aug 29, 2026)
        const formatDate = (dateStr) => {
            if (!dateStr) return 'N/A';
            const cleanStr = String(dateStr).trim();
            if (/^\d{4}-\d{2}-\d{2}$/.test(cleanStr)) {
                const [y, m, d] = cleanStr.split('-').map(Number);
                const dt = new Date(y, m - 1, d);
                return dt.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            }
            const date = new Date(cleanStr);
            if (isNaN(date.getTime())) return cleanStr.replace(/T.*$/, '').replace(/Z$/, '');
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        };

        const formatStayDate = (sDate, eDate, totalDays, startSlot, endSlot) => {
            if (!sDate) return 'N/A';
            const sFormatted = formatDate(sDate);
            const sSlot = startSlot ? ` (${startSlot})` : '';
            const eSlot = endSlot ? ` (${endSlot})` : '';
            if (eDate && eDate !== sDate) {
                const eFormatted = formatDate(eDate);
                const daysCount = totalDays || 'Multi-day';
                return `${sFormatted}${sSlot} – ${eFormatted}${eSlot} (${daysCount} Days Stay)`;
            }
            return `${sFormatted}${sSlot}`;
        };

        const formatExpectedCheckout = (res) => {
            if (!res) return { date: 'N/A', session: 'Daytime', time: '', fullText: 'N/A' };

            let session = res.end_slot || res.start_slot;
            if (!session && res.reservation_amenities && res.reservation_amenities.length > 0) {
                const lastAmenity = res.reservation_amenities[res.reservation_amenities.length - 1];
                session = lastAmenity.end_slot || (lastAmenity.pricing_type && lastAmenity.pricing_type.toLowerCase().includes('night') ? 'Nighttime' : 'Daytime');
            }
            session = session ? (session.toLowerCase().includes('night') ? 'Nighttime' : 'Daytime') : 'Daytime';

            let rawDate = res.end_date || res.reservation_date;
            let formattedDate = 'N/A';
            let formattedTime = session === 'Nighttime' ? '6:00 AM (Next Day)' : '6:00 PM';

            if (res.checkout_at) {
                const dt = new Date(res.checkout_at);
                if (!isNaN(dt.getTime())) {
                    formattedDate = dt.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                    formattedTime = dt.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                }
            }

            if (formattedDate === 'N/A' && rawDate) {
                formattedDate = formatDate(rawDate);
            }

            return {
                date: formattedDate,
                session: session,
                time: formattedTime,
                fullText: `${formattedDate} · ${session}${formattedTime ? ` (${formattedTime})` : ''}`
            };
        };

        const rawGuests = reservation.reservation_guests || reservation.reservationGuests || reservation.guests || [];
        const guests = rawGuests.map((guest) => {
            const name = [guest.customer?.first_name, guest.customer?.last_name].filter(Boolean).join(' ').trim() || (guest.is_primary_guest ? reservation.booker_name : 'Unnamed guest');
            const role = guest.is_primary_guest ? 'Primary Guest' : 'Companion';
            const email = guest.customer?.email || (guest.is_primary_guest ? reservation.email : 'No email');
            return `
                <div class="guest-relationship-item">
                    <div class="guest-relationship-label">${escapeHtml(role)}</div>
                    <div class="guest-relationship-name">${escapeHtml(name)}</div>
                    <div class="guest-meta">${escapeHtml(email || 'No email')}</div>
                </div>
            `;
        }).join('');

        const guestsListHtml = guests || `
            <div class="guest-relationship-item">
                <div class="guest-relationship-label">Primary Guest (Booker)</div>
                <div class="guest-relationship-name">${escapeHtml(reservation.booker_name || 'Booker')}</div>
                <div class="guest-meta">${escapeHtml(reservation.email || 'No email')} · ${escapeHtml(reservation.phone || 'No phone')}</div>
            </div>
        `;

        const rawAmenities = reservation.reservation_amenities || reservation.reservationAmenities || reservation.amenities || [];
        const amenities = rawAmenities.map((amenity) => {
            const name = amenity.amenity?.amenities_name || amenity.amenity_name || amenity.amenities_name || amenity.name || 'Unknown amenity';
            const sDate = amenity.start_date || reservation.reservation_date;
            const eDate = amenity.end_date || reservation.end_date || sDate;
            const sSlot = amenity.start_slot || reservation.start_slot || 'Daytime';
            const eSlot = amenity.end_slot || reservation.end_slot || sSlot;
            const hasRange = sDate && eDate && sDate !== eDate;
            const pricingType = amenity.pricing_type || (hasRange ? `Continuous Stay (${reservation.total_days || 1}D)` : sSlot);
            const scheduleBadge = hasRange
                ? `<span style="font-size:0.75rem;padding:2px 6px;border-radius:4px;background:rgba(26,92,60,0.1);color:#1a5c3c;font-weight:600;margin-left:4px;">${escapeHtml(formatDate(sDate))} (${escapeHtml(sSlot)}) – ${escapeHtml(formatDate(eDate))} (${escapeHtml(eSlot)})</span>`
                : (sSlot ? `<span style="font-size:0.75rem;padding:2px 6px;border-radius:4px;background:rgba(26,92,60,0.1);color:#1a5c3c;font-weight:600;margin-left:4px;">${escapeHtml(sSlot)}</span>` : '');
            const slotCountBadge = (amenity.day_slots_count || amenity.night_slots_count) ? `<span style="font-size:0.75rem;color:#666;margin-left:4px;">(${amenity.day_slots_count || 0}D ${amenity.night_slots_count || 0}N)</span>` : '';

            return `<li>${escapeHtml(name)} — ${escapeHtml(pricingType)}${scheduleBadge}${slotCountBadge} · ₱${Number(amenity.price_at_booking || 0).toFixed(2)}</li>`;
        }).join('');

        const expectedCheckout = formatExpectedCheckout(reservation);

        modalStatus.textContent = reservation.status;
        modalStatus.className = `guest-modal__role-badge reservation-status reservation-status--${String(reservation.status || '').toLowerCase()}`;
        modalBody.innerHTML = `
            <div class="guest-card">
                <div class="guest-card__grid">
                    <div>
                        <span class="guest-label">Reservation ID</span>
                        <div class="guest-value"><span class="inline-flex items-center rounded-lg bg-[#e8f5e9] px-2 py-0.5 text-xs font-bold text-[#1b4332] font-mono dark:bg-[rgba(46,125,50,0.25)] dark:text-[#81c784]">#${escapeHtml(reservation.id)}</span></div>
                    </div>
                    <div>
                        <span class="guest-label">Booker</span>
                        <div class="guest-value">${escapeHtml(reservation.booker_name || 'N/A')}</div>
                    </div>
                    <div>
                        <span class="guest-label">Contact</span>
                        <div class="guest-value">${escapeHtml(reservation.phone || 'N/A')}<br>${escapeHtml(reservation.email || 'N/A')}</div>
                    </div>
                    <div>
                        <span class="guest-label">Reservation Stay</span>
                        <div class="guest-value">${escapeHtml(formatStayDate(reservation.reservation_date, reservation.end_date, reservation.total_days, reservation.start_slot, reservation.end_slot))}</div>
                    </div>
                    <div>
                        <span class="guest-label">Expected Check-out</span>
                        <div class="guest-value" style="font-weight: 600; color: #1a5c3c;">${escapeHtml(expectedCheckout.fullText)}</div>
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
                    <div class="guest-relationship-list">${guestsListHtml}</div>
                </div>
                <div style="margin-top:0.75rem;">
                    <span class="guest-label">Reserved Amenities & Schedules</span>
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
        const resvModalTitle = document.getElementById('reservationModalTitle');
        if (resvModalTitle) {
            resvModalTitle.textContent = 'Reservation Details';
        }
        const editForm = document.getElementById('reservationModalEditForm');
        if (editForm) {
            editForm.hidden = true;
        }
        // Make sure the reschedule calendar modal never outlives its parent.
        if (typeof closeEditCalendarModal === 'function') {
            closeEditCalendarModal();
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
            row.setAttribute('data-search', `${reservation.id} #${reservation.id} ${(reservation.booker_name || '').toLowerCase()} ${(reservation.email || '').toLowerCase()} ${(reservation.phone || '').toLowerCase()} ${(reservation.status || '').toLowerCase()}`);
            row.setAttribute('tabindex', '0');
            row.setAttribute('role', 'button');
            row.setAttribute('aria-label', `View reservation details for ${reservation.booker_name} (#${reservation.id})`);

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

        // Update rows reference so applyFilters operates on fresh rows
        updateRowsReference();

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

    const ensureAmenitiesLoaded = async () => {
        if (Array.isArray(window.staffAmenitiesData) && window.staffAmenitiesData.length > 0) {
            return window.staffAmenitiesData;
        }
        try {
            const res = await fetch('/staff/amenities-list', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (res.ok) {
                const data = await res.json();
                if (Array.isArray(data.amenities) && data.amenities.length > 0) {
                    window.staffAmenitiesData = data.amenities;
                    return window.staffAmenitiesData;
                }
            }
        } catch (e) {
            console.error('Failed to fetch amenities list:', e);
        }
        return window.staffAmenitiesData || [];
    };

    const openEditForm = async (reservationId) => {
        if (!reservationData[reservationId] && window.staffReservationData?.[reservationId]) {
            reservationData[reservationId] = window.staffReservationData[reservationId];
        }
        const reservation = reservationData?.[reservationId];
        if (!reservation || !editForm) return;

        // Reset submit button state
        const submitButton = editReservationForm.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = 'Save Changes';
        }

        const sDate = formatDateForInput(reservation.reservation_date);
        const eDate = formatDateForInput(reservation.end_date) || sDate;
        const sSlot = reservation.start_slot || 'Daytime';
        const eSlot = reservation.end_slot || sSlot;

        // Populate form fields
        document.getElementById('editReservationId').value = reservation.id;
        document.getElementById('editBookerName').value = reservation.booker_name || '';
        document.getElementById('editEmail').value = reservation.email || '';
        document.getElementById('editPhone').value = reservation.phone || '';
        document.getElementById('editReservationDate').value = sDate;
        document.getElementById('editEndDate').value = eDate;
        document.getElementById('editStartSlot').value = sSlot;
        document.getElementById('editEndSlot').value = eSlot;
        document.getElementById('editGuests').value = reservation.number_of_guests || '';
        document.getElementById('editStatus').value = reservation.status || 'Pending';

        // Ensure active amenities are loaded
        await ensureAmenitiesLoaded();

        // Initialize state for the multi-day reschedule calendar
        initEditCalendar(reservationId);

        // Render booked amenities list for swapping and date editing
        renderEditAmenitiesList(reservation);

        // Update amenity select dropdown availability (disable unavailable amenities)
        updateAmenitySelectsAvailability(reservationId);

        // Update Stay Schedule Card in edit form
        updateEditFormScheduleCard();

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

    // ── Continuous Multi-Day Reschedule Calendar & Pricing Engine ────────
    let editCalState = {
        reservationId: null,
        month: null,
        year: null,
        startDate: '',
        endDate: '',
        startSlot: 'Daytime',
        endSlot: 'Daytime',
        selectingEnd: false,
        hoverDate: null,
        currentStartDate: '',
        currentEndDate: '',
        currentStartSlot: 'Daytime',
        currentEndSlot: 'Daytime',
        originalTotal: 0,
        amountPaid: 0,
        entranceFee: 0,
        amenities: [],
        availability: [],
    };

    const editCalGrid = document.getElementById('editCalGrid');
    const editCalTitle = document.getElementById('editCalTitle');
    const editCalPrev = document.getElementById('editCalPrev');
    const editCalNext = document.getElementById('editCalNext');
    const editCalTrigger = document.getElementById('editCalTrigger');
    const editCalTriggerValue = document.getElementById('editCalTriggerValue');
    const editCalTriggerSessions = document.getElementById('editCalTriggerSessions');
    const editStayDurationBadge = document.getElementById('editStayDurationBadge');
    const editPriceImpactCard = document.getElementById('editPriceImpactCard');
    const editPreviewTotal = document.getElementById('editPreviewTotal');
    const editPreviewPaid = document.getElementById('editPreviewPaid');
    const editPreviewBalance = document.getElementById('editPreviewBalance');
    const editPriceDiffBadge = document.getElementById('editPriceDiffBadge');
    const editCalendarModal = document.getElementById('editCalendarModal');
    const editCalModalCurrent = document.getElementById('editCalModalCurrent');
    const editCalCloseButtons = document.querySelectorAll('[data-close-edit-calendar="true"]');
    const editCalYear = document.getElementById('editCalYear');
    const editCalApplyBtn = document.getElementById('editCalApplyBtn');
    const editCalSummaryText = document.getElementById('editCalSummaryText');
    const editCalCostSummary = document.getElementById('editCalCostSummary');
    const editCalStepHelp = document.getElementById('editCalStepHelp');

    const editCalMaxYear = new Date().getFullYear() + 5;

    const todayISO = () => {
        const now = new Date();
        return now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
    };

    const formatDateForInput = (dateStr) => {
        if (!dateStr) return '';
        const cleanStr = String(dateStr).trim();
        if (/^\d{4}-\d{2}-\d{2}$/.test(cleanStr)) {
            return cleanStr;
        }
        const dt = new Date(cleanStr);
        if (isNaN(dt.getTime())) return cleanStr.replace(/T.*$/, '').replace(/Z$/, '');
        const y = dt.getFullYear();
        const m = String(dt.getMonth() + 1).padStart(2, '0');
        const d = String(dt.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    };

    const formatDateLong = (dateStr) => {
        if (!dateStr) return '';
        const [y, m, d] = dateStr.split('-').map(Number);
        const dt = new Date(y, m - 1, d);
        if (isNaN(dt.getTime())) return dateStr;
        return dt.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    };

    const calculateContinuousSlots = (startDateStr, endDateStr, startSlot = 'Daytime', endSlot = 'Daytime') => {
        if (!startDateStr) return { dayCount: 1, nightCount: 0, totalDays: 1 };
        const cleanStart = (startSlot || 'Daytime').includes('Night') ? 'Nighttime' : 'Daytime';
        const cleanEnd = (endSlot || 'Daytime').includes('Night') ? 'Nighttime' : 'Daytime';

        if (!endDateStr || startDateStr === endDateStr) {
            if (cleanStart === 'Daytime' && cleanEnd === 'Daytime') {
                return { dayCount: 1, nightCount: 0, totalDays: 1 };
            } else if (cleanStart === 'Nighttime' && cleanEnd === 'Nighttime') {
                return { dayCount: 0, nightCount: 1, totalDays: 1 };
            } else if (cleanStart === 'Daytime' && cleanEnd === 'Nighttime') {
                return { dayCount: 1, nightCount: 1, totalDays: 1 };
            } else {
                return { dayCount: 1, nightCount: 1, totalDays: 2 };
            }
        }

        const [sy, sm, sd] = startDateStr.split('-').map(Number);
        const [ey, em, ed] = endDateStr.split('-').map(Number);
        const start = new Date(sy, sm - 1, sd);
        const end = new Date(ey, em - 1, ed);

        let daysDiff = Math.round((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24));
        if (daysDiff < 0) daysDiff = 0;
        const totalDays = daysDiff + 1;

        let dayCount = 0;
        let nightCount = 0;

        for (let i = 0; i <= daysDiff; i++) {
            if (i === 0) {
                if (cleanStart === 'Daytime') {
                    dayCount++;
                    nightCount++;
                } else {
                    nightCount++;
                }
            } else if (i === daysDiff) {
                if (cleanEnd === 'Daytime') {
                    dayCount++;
                } else {
                    dayCount++;
                    nightCount++;
                }
            } else {
                dayCount++;
                nightCount++;
            }
        }

        return { dayCount, nightCount, totalDays };
    };

    const renderEditAmenitiesList = (reservation) => {
        const container = document.getElementById('editAmenitiesList');
        if (!container) return;

        container.innerHTML = '';
        const amenities = reservation?.reservation_amenities || reservation?.reservationAmenities || [];
        const allAmenities = window.staffAmenitiesData || [];

        if (!amenities.length) {
            container.innerHTML = '<p class="text-xs text-hp-text-muted">No amenities booked for this reservation.</p>';
            return;
        }

        const masterStart = document.getElementById('editReservationDate')?.value || formatDateForInput(reservation.reservation_date);
        const masterEnd = document.getElementById('editEndDate')?.value || formatDateForInput(reservation.end_date) || masterStart;

        amenities.forEach((ra, idx) => {
            const raId = ra.id;
            const currentAmenityId = String(ra.amenity_id || ra.amenity?.id || '');
            const raStart = formatDateForInput(ra.start_date) || masterStart;
            const raEnd = formatDateForInput(ra.end_date) || masterEnd;
            const raStartSlot = ra.start_slot || 'Daytime';
            const raEndSlot = ra.end_slot || raStartSlot;

            let optionsHtml = '';
            let matchedCurrent = false;

            allAmenities.forEach((a) => {
                const isCurrent = String(a.id) === currentAmenityId;
                if (isCurrent) matchedCurrent = true;
                const selected = isCurrent ? 'selected' : '';
                const dayP = Number(a.daytime_price || 0).toFixed(2);
                optionsHtml += `<option value="${escapeHtml(a.id)}" ${selected}>${escapeHtml(a.amenities_name)} (₱${dayP})</option>`;
            });

            // If the booked amenity is not in the active list (e.g. customized or disabled), add it so user can see it
            if (!matchedCurrent && currentAmenityId) {
                const currentName = ra.amenity?.amenities_name || 'Booked Amenity';
                const dayP = Number(ra.amenity?.daytime_price || ra.price_at_booking || 0).toFixed(2);
                optionsHtml = `<option value="${escapeHtml(currentAmenityId)}" selected>${escapeHtml(currentName)} (₱${dayP})</option>` + optionsHtml;
            }

            const item = document.createElement('div');
            item.className = 'edit-amenity-item rounded-xl border border-glass-border bg-hp-cream p-3 grid gap-3 dark:bg-white/5 dark:border-white/10';
            item.setAttribute('data-ra-id', raId);
            item.setAttribute('data-index', idx);

            item.innerHTML = `
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-hp-text dark:text-[#c8e6c8]">Booked Amenity #${idx + 1}</span>
                    <span class="edit-amenity-price-tag text-xs font-bold text-hp-green dark:text-[#81c784]">₱${Number(ra.price_at_booking || 0).toFixed(2)}</span>
                </div>
                <div class="grid gap-1">
                    <label class="text-[0.75rem] font-semibold text-hp-text">Change / Swap Amenity</label>
                    <select class="edit-amenity-select w-full rounded-xl border border-glass-border bg-glass px-3 py-2 text-xs text-hp-text transition-colors focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
                        ${optionsHtml}
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div class="grid gap-1">
                        <label class="text-[0.72rem] font-semibold text-hp-text-muted">Amenity Check-In Date & Session</label>
                        <div class="flex gap-1.5">
                            <input type="date" class="edit-amenity-start-date flex-1 rounded-lg border border-glass-border bg-glass px-2.5 py-1.5 text-xs text-hp-text focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]" value="${raStart}" min="${masterStart}" max="${masterEnd}">
                            <select class="edit-amenity-start-slot rounded-lg border border-glass-border bg-glass px-2 py-1.5 text-xs text-hp-text focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
                                <option value="Daytime" ${raStartSlot === 'Daytime' ? 'selected' : ''}>Daytime</option>
                                <option value="Nighttime" ${raStartSlot === 'Nighttime' ? 'selected' : ''}>Nighttime</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid gap-1">
                        <label class="text-[0.72rem] font-semibold text-hp-text-muted">Amenity Check-Out Date & Session</label>
                        <div class="flex gap-1.5">
                            <input type="date" class="edit-amenity-end-date flex-1 rounded-lg border border-glass-border bg-glass px-2.5 py-1.5 text-xs text-hp-text focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]" value="${raEnd}" min="${masterStart}" max="${masterEnd}">
                            <select class="edit-amenity-end-slot rounded-lg border border-glass-border bg-glass px-2 py-1.5 text-xs text-hp-text focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#c8e6c8]">
                                <option value="Daytime" ${raEndSlot === 'Daytime' ? 'selected' : ''}>Daytime</option>
                                <option value="Nighttime" ${raEndSlot === 'Nighttime' ? 'selected' : ''}>Nighttime</option>
                            </select>
                        </div>
                    </div>
                </div>
            `;

            item.querySelectorAll('select, input').forEach(input => {
                input.addEventListener('change', () => {
                    const sInput = item.querySelector('.edit-amenity-start-date');
                    const eInput = item.querySelector('.edit-amenity-end-date');
                    if (sInput && eInput && sInput.value > eInput.value) {
                        eInput.value = sInput.value;
                    }
                    updateEditFormScheduleCard();
                    updateAmenitySelectsAvailability();
                });
            });

            container.appendChild(item);
        });

        if (!allAmenities.length) {
            ensureAmenitiesLoaded().then((loaded) => {
                if (loaded && loaded.length > 0) {
                    renderEditAmenitiesList(reservation);
                    updateAmenitySelectsAvailability();
                    updateEditFormScheduleCard();
                }
            });
        }
    };

    const isRangesOverlapping = (s1, e1, slotS1, slotE1, s2, e2, slotS2, slotE2) => {
        if (!s1 || !s2) return false;
        const end1 = e1 || s1;
        const end2 = e2 || s2;

        const t1 = calculateContinuousSlotsTimeline(s1, end1, slotS1, slotE1);
        const t2 = calculateContinuousSlotsTimeline(s2, end2, slotS2, slotE2);

        const map1 = {};
        for (const [d, s] of t1) {
            map1[`${d}_${s}`] = true;
        }
        for (const [d, s] of t2) {
            if (map1[`${d}_${s}`]) return true;
        }
        return false;
    };

    const updateAmenitySelectsAvailability = async (reservationId) => {
        const amenityItems = document.querySelectorAll('.edit-amenity-item');
        if (!amenityItems.length) return;

        const resId = reservationId || document.getElementById('editReservationId')?.value;
        if (!resId) return;

        const ranges = [];
        amenityItems.forEach((item) => {
            const idx = item.getAttribute('data-index');
            const sDate = item.querySelector('.edit-amenity-start-date')?.value;
            const eDate = item.querySelector('.edit-amenity-end-date')?.value || sDate;
            const sSlot = item.querySelector('.edit-amenity-start-slot')?.value || 'Daytime';
            const eSlot = item.querySelector('.edit-amenity-end-slot')?.value || 'Daytime';

            if (sDate) {
                ranges.push({
                    index: Number(idx),
                    start_date: sDate,
                    end_date: eDate,
                    start_slot: sSlot,
                    end_slot: eSlot,
                });
            }
        });

        if (!ranges.length) return;

        try {
            const response = await fetch(`/staff/reservations/${resId}/check-amenities-availability`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ ranges }),
            });

            if (!response.ok) return;

            const data = await response.json();
            const availabilityMap = data.availability || {};
            const allAmenities = window.staffAmenitiesData || [];

            const selectedByRow = {};
            amenityItems.forEach((item) => {
                const idx = item.getAttribute('data-index');
                const select = item.querySelector('.edit-amenity-select');
                selectedByRow[idx] = {
                    amenityId: select?.value,
                    sDate: item.querySelector('.edit-amenity-start-date')?.value,
                    eDate: item.querySelector('.edit-amenity-end-date')?.value,
                    sSlot: item.querySelector('.edit-amenity-start-slot')?.value,
                    eSlot: item.querySelector('.edit-amenity-end-slot')?.value,
                };
            });

            amenityItems.forEach((item) => {
                const idx = item.getAttribute('data-index');
                const select = item.querySelector('.edit-amenity-select');
                if (!select) return;

                const currentSelected = select.value;
                const unavailableForThisRow = availabilityMap[idx] || [];

                const takenByOtherRows = [];
                Object.keys(selectedByRow).forEach((otherIdx) => {
                    if (String(otherIdx) !== String(idx)) {
                        const other = selectedByRow[otherIdx];
                        const thisR = ranges.find(r => String(r.index) === String(idx));
                        if (other.amenityId && thisR) {
                            if (isRangesOverlapping(thisR.start_date, thisR.end_date, thisR.start_slot, thisR.end_slot, other.sDate, other.eDate, other.sSlot, other.eSlot)) {
                                takenByOtherRows.push(String(other.amenityId));
                            }
                        }
                    }
                });

                Array.from(select.options).forEach((opt) => {
                    const optValue = String(opt.value);
                    const amenityModel = allAmenities.find(a => String(a.id) === optValue);
                    const isTakenInDb = unavailableForThisRow.includes(optValue);
                    const isTakenByRow = takenByOtherRows.includes(optValue);
                    const isCurrent = (optValue === String(currentSelected));

                    const isUnavailable = (isTakenInDb || isTakenByRow) && !isCurrent;

                    opt.disabled = isUnavailable;
                    if (isUnavailable) {
                        opt.textContent = `${amenityModel?.amenities_name || optValue} (Unavailable)`;
                        opt.title = 'This amenity is unavailable for the selected dates/slots';
                    } else {
                        const dayP = amenityModel ? Number(amenityModel.daytime_price || 0).toFixed(2) : '0.00';
                        opt.textContent = `${amenityModel?.amenities_name || optValue} (₱${dayP})`;
                        opt.removeAttribute('title');
                    }
                });
            });
        } catch (err) {
            console.error('Check amenity options availability failed:', err);
        }
    };

    const calculateReservationPricing = (startDateStr, endDateStr, startSlot, endSlot) => {
        const { dayCount, nightCount, totalDays } = calculateContinuousSlots(startDateStr, endDateStr, startSlot, endSlot);

        let amenityTotal = 0;
        const amenityItems = document.querySelectorAll('.edit-amenity-item');

        if (amenityItems && amenityItems.length > 0) {
            const allAmenities = window.staffAmenitiesData || [];
            const origReservation = reservationData[editCalState.reservationId];
            const origAmenities = origReservation?.reservation_amenities || origReservation?.reservationAmenities || [];

            amenityItems.forEach((item) => {
                const selectedAmenityId = item.querySelector('.edit-amenity-select')?.value;
                const aStartDate = item.querySelector('.edit-amenity-start-date')?.value || startDateStr;
                const aEndDate = item.querySelector('.edit-amenity-end-date')?.value || aStartDate;
                const aStartSlot = item.querySelector('.edit-amenity-start-slot')?.value || 'Daytime';
                const aEndSlot = item.querySelector('.edit-amenity-end-slot')?.value || 'Daytime';

                const amenityModel = allAmenities.find(a => String(a.id) === String(selectedAmenityId));
                const aCounts = calculateContinuousSlots(aStartDate, aEndDate, aStartSlot, aEndSlot);

                const raId = item.dataset.raId;
                const origRa = origAmenities.find(r => String(r.id) === String(raId));
                const hasAircon = origRa ? String(origRa.pricing_type || '').includes('Aircon') : false;

                const dayPrice = amenityModel ? (hasAircon && amenityModel.daytime_aircon_price ? Number(amenityModel.daytime_aircon_price) : Number(amenityModel.daytime_price || 0)) : 0;
                const nightPrice = amenityModel ? (hasAircon && amenityModel.nighttime_aircon_price ? Number(amenityModel.nighttime_aircon_price) : Number(amenityModel.nighttime_price || 0)) : 0;
                const qty = Math.max(1, Number(origRa?.quantity) || 1);

                const itemPrice = ((aCounts.dayCount * dayPrice) + (aCounts.nightCount * nightPrice)) * qty;
                amenityTotal += itemPrice;

                const priceTag = item.querySelector('.edit-amenity-price-tag');
                if (priceTag) priceTag.textContent = `₱${itemPrice.toFixed(2)}`;
            });
        } else if (editCalState.amenities && editCalState.amenities.length > 0) {
            editCalState.amenities.forEach((a) => {
                const qty = Math.max(1, Number(a.quantity) || 1);
                const dayPrice = Number(a.daytime_price) || 0;
                const nightPrice = Number(a.nighttime_price) || 0;
                const price = ((dayCount * dayPrice) + (nightCount * nightPrice)) * qty;
                amenityTotal += price;
            });
        } else {
            amenityTotal = editCalState.originalTotal;
        }

        const entranceFee = Number(editCalState.entranceFee) || 0;
        const newTotal = amenityTotal + entranceFee;
        const amountPaid = Number(editCalState.amountPaid) || 0;
        const newBalance = Math.max(0, newTotal - amountPaid);
        const totalDiff = newTotal - editCalState.originalTotal;

        return {
            newTotal,
            newBalance,
            totalDiff,
            dayCount,
            nightCount,
            totalDays,
            amountPaid,
        };
    };

    const updateEditFormScheduleCard = () => {
        const sDate = document.getElementById('editReservationDate')?.value || editCalState.startDate;
        const eDate = document.getElementById('editEndDate')?.value || editCalState.endDate || sDate;
        const sSlot = document.getElementById('editStartSlot')?.value || editCalState.startSlot || 'Daytime';
        const eSlot = document.getElementById('editEndSlot')?.value || editCalState.endSlot || sSlot;

        if (!sDate) return;

        const pricing = calculateReservationPricing(sDate, eDate, sSlot, eSlot);

        if (editCalTriggerValue) {
            editCalTriggerValue.textContent = (sDate === eDate)
                ? formatDateLong(sDate)
                : `${formatDateLong(sDate)} – ${formatDateLong(eDate)}`;
        }

        if (editCalTriggerSessions) {
            editCalTriggerSessions.textContent = (sDate === eDate)
                ? (sSlot === eSlot ? `${sSlot} Session` : `${sSlot} to ${eSlot}`)
                : `${sSlot} check-in → ${eSlot} check-out (${pricing.dayCount}D ${pricing.nightCount}N)`;
        }

        if (editStayDurationBadge) {
            editStayDurationBadge.textContent = `${pricing.totalDays} Day${pricing.totalDays > 1 ? 's' : ''} Stay`;
        }

        const hasScheduleChanged = (sDate !== editCalState.currentStartDate)
            || (eDate !== editCalState.currentEndDate)
            || (sSlot !== editCalState.currentStartSlot)
            || (eSlot !== editCalState.currentEndSlot);

        if (editPriceImpactCard) {
            if (hasScheduleChanged || pricing.totalDiff !== 0) {
                editPriceImpactCard.hidden = false;
                if (editPreviewTotal) editPreviewTotal.textContent = `₱${pricing.newTotal.toFixed(2)}`;
                if (editPreviewPaid) editPreviewPaid.textContent = `₱${pricing.amountPaid.toFixed(2)}`;
                if (editPreviewBalance) editPreviewBalance.textContent = `₱${pricing.newBalance.toFixed(2)}`;

                if (editPriceDiffBadge) {
                    if (pricing.totalDiff > 0) {
                        editPriceDiffBadge.innerHTML = `<span class="inline-block rounded-md bg-[#e65100]/10 px-2 py-0.5 text-[#e65100] dark:bg-[#ffb74d]/20 dark:text-[#ffb74d]">+₱${pricing.totalDiff.toFixed(2)} added to total & balance</span>`;
                    } else if (pricing.totalDiff < 0) {
                        editPriceDiffBadge.innerHTML = `<span class="inline-block rounded-md bg-hp-green/10 px-2 py-0.5 text-hp-green dark:bg-[#81c784]/20 dark:text-[#81c784]">-₱${Math.abs(pricing.totalDiff).toFixed(2)} reduced from total & balance</span>`;
                    } else {
                        editPriceDiffBadge.innerHTML = `<span class="text-hp-text-muted">Same total amount</span>`;
                    }
                }
            } else {
                editPriceImpactCard.hidden = true;
            }
        }
    };

    const populateEditCalYear = () => {
        if (!editCalYear) return;
        const currentYear = new Date().getFullYear();
        const fromYear = Math.min(currentYear, Number(editCalState.year) || currentYear);
        editCalYear.innerHTML = '';
        for (let year = fromYear; year <= editCalMaxYear; year++) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            editCalYear.appendChild(option);
        }
        editCalYear.value = String(editCalState.year);
    };

    const syncEditCalYearSelect = () => {
        if (editCalYear) editCalYear.value = String(editCalState.year);
    };

    const syncEditCalNextState = () => {
        if (!editCalNext) return;
        const atCap = editCalState.month === 12 && editCalState.year >= editCalMaxYear;
        editCalNext.disabled = atCap;
        editCalNext.classList.toggle('is-disabled', atCap);
    };

    const syncSessionButtonsUI = () => {
        document.querySelectorAll('#editStartSlotGroup .session-pill-btn').forEach((b) => {
            b.dataset.active = (b.dataset.slotVal === editCalState.startSlot) ? 'true' : 'false';
        });
        document.querySelectorAll('#editEndSlotGroup .session-pill-btn').forEach((b) => {
            b.dataset.active = (b.dataset.slotVal === editCalState.endSlot) ? 'true' : 'false';
        });
    };

    const updateEditCalendarCostSummary = () => {
        const sDate = editCalState.startDate;
        const eDate = editCalState.endDate || sDate;
        const sSlot = editCalState.startSlot;
        const eSlot = editCalState.endSlot;

        if (!sDate) {
            if (editCalSummaryText) editCalSummaryText.textContent = 'Please select a check-in date';
            if (editCalCostSummary) editCalCostSummary.textContent = '—';
            return;
        }

        const pricing = calculateReservationPricing(sDate, eDate, sSlot, eSlot);

        if (editCalSummaryText) {
            editCalSummaryText.textContent = (sDate === eDate)
                ? `${formatDateLong(sDate)} (${sSlot} to ${eSlot}) · 1 Day`
                : `${formatDateLong(sDate)} (${sSlot}) → ${formatDateLong(eDate)} (${eSlot}) · ${pricing.totalDays} Days (${pricing.dayCount}D ${pricing.nightCount}N)`;
        }

        if (editCalCostSummary) {
            const diffText = pricing.totalDiff > 0
                ? ` (+₱${pricing.totalDiff.toFixed(2)})`
                : (pricing.totalDiff < 0 ? ` (-₱${Math.abs(pricing.totalDiff).toFixed(2)})` : '');
            editCalCostSummary.textContent = `New Total: ₱${pricing.newTotal.toFixed(2)}${diffText} · Balance: ₱${pricing.newBalance.toFixed(2)}`;
        }
    };

    const calculateContinuousSlotsTimeline = (startDateStr, endDateStr, startSlot = 'Daytime', endSlot = 'Daytime') => {
        if (!startDateStr) return [];
        const cleanStart = (startSlot || 'Daytime').includes('Night') ? 'Nighttime' : 'Daytime';
        const cleanEnd = (endSlot || 'Daytime').includes('Night') ? 'Nighttime' : 'Daytime';

        const [sy, sm, sd] = startDateStr.split('-').map(Number);
        const [ey, em, ed] = (endDateStr || startDateStr).split('-').map(Number);
        const start = new Date(sy, sm - 1, sd);
        const end = new Date(ey, em - 1, ed);

        let daysDiff = Math.round((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24));
        if (daysDiff < 0) daysDiff = 0;

        const pairs = [];
        if (daysDiff === 0) {
            if (cleanStart === 'Daytime' && cleanEnd === 'Daytime') {
                pairs.push([startDateStr, 'Daytime']);
            } else if (cleanStart === 'Nighttime' && cleanEnd === 'Nighttime') {
                pairs.push([startDateStr, 'Nighttime']);
            } else if (cleanStart === 'Daytime' && cleanEnd === 'Nighttime') {
                pairs.push([startDateStr, 'Daytime']);
                pairs.push([startDateStr, 'Nighttime']);
            } else {
                pairs.push([startDateStr, 'Nighttime']);
                const nextDate = new Date(sy, sm - 1, sd + 1);
                const nextIso = `${nextDate.getFullYear()}-${String(nextDate.getMonth() + 1).padStart(2, '0')}-${String(nextDate.getDate()).padStart(2, '0')}`;
                pairs.push([nextIso, 'Daytime']);
            }
            return pairs;
        }

        for (let i = 0; i <= daysDiff; i++) {
            const curDt = new Date(sy, sm - 1, sd + i);
            const curIso = `${curDt.getFullYear()}-${String(curDt.getMonth() + 1).padStart(2, '0')}-${String(curDt.getDate()).padStart(2, '0')}`;
            if (i === 0) {
                if (cleanStart === 'Daytime') {
                    pairs.push([curIso, 'Daytime']);
                    pairs.push([curIso, 'Nighttime']);
                } else {
                    pairs.push([curIso, 'Nighttime']);
                }
            } else if (i === daysDiff) {
                if (cleanEnd === 'Daytime') {
                    pairs.push([curIso, 'Daytime']);
                } else {
                    pairs.push([curIso, 'Daytime']);
                    pairs.push([curIso, 'Nighttime']);
                }
            } else {
                pairs.push([curIso, 'Daytime']);
                pairs.push([curIso, 'Nighttime']);
            }
        }
        return pairs;
    };

    const isSlotAvailableOnDate = (iso, slotType = 'Daytime') => {
        if (!iso) return false;
        const today = todayISO();
        const isCurrentStayDate = editCalState.currentStartDate && editCalState.currentEndDate
            && (iso >= editCalState.currentStartDate && iso <= editCalState.currentEndDate);

        const entry = editCalState.availabilityMap?.[iso];
        if (!entry) {
            if (iso < today && !isCurrentStayDate) return false;
            return true;
        }

        if (entry.is_past && !isCurrentStayDate) {
            return false;
        }

        if (slotType === 'Daytime') {
            return Boolean(entry.daytime);
        }
        if (slotType === 'Nighttime') {
            return Boolean(entry.nighttime);
        }
        if (slotType === 'Both') {
            return Boolean(entry.daytime && entry.nighttime);
        }
        return Boolean(entry.daytime || entry.nighttime);
    };

    const isRangeValidAndAvailable = (sDate, eDate, sSlot = editCalState.startSlot, eSlot = editCalState.endSlot) => {
        if (!sDate) return false;
        const e = eDate || sDate;
        if (sDate > e) return false;

        const timeline = calculateContinuousSlotsTimeline(sDate, e, sSlot, eSlot);
        if (!timeline.length) return false;

        for (const [d, s] of timeline) {
            if (!isSlotAvailableOnDate(d, s)) {
                return false;
            }
        }
        return true;
    };

    const initEditCalendar = (reservationId) => {
        const reservation = reservationData?.[reservationId];
        if (!reservation) return;

        const currentStart = formatDateForInput(reservation.reservation_date);
        const currentEnd = formatDateForInput(reservation.end_date) || currentStart;
        const currentStartSlot = reservation.start_slot || 'Daytime';
        const currentEndSlot = reservation.end_slot || currentStartSlot;
        const parts = currentStart ? currentStart.split('-').map(Number) : null;

        editCalState = {
            reservationId,
            month: parts ? parts[1] : new Date().getMonth() + 1,
            year: parts ? parts[0] : new Date().getFullYear(),
            startDate: currentStart,
            endDate: currentEnd,
            startSlot: currentStartSlot,
            endSlot: currentEndSlot,
            selectingEnd: false,
            hoverDate: null,
            currentStartDate: currentStart,
            currentEndDate: currentEnd,
            currentStartSlot,
            currentEndSlot,
            originalTotal: Number(reservation.total_amount || 0),
            amountPaid: Number(reservation.amount_paid || 0),
            entranceFee: 0,
            amenities: [],
            availability: [],
            availabilityMap: {},
        };

        populateEditCalYear();
        syncSessionButtonsUI();
        syncEditCalNextState();

        if (editCalModalCurrent) {
            editCalModalCurrent.textContent = currentStart ? `Current: ${formatDateLong(currentStart)}${currentEnd !== currentStart ? ' – ' + formatDateLong(currentEnd) : ''}` : '';
            editCalModalCurrent.hidden = !currentStart;
        }

        if (editCalStepHelp) {
            editCalStepHelp.textContent = 'Click date to set check-in';
        }

        closeEditCalendarModal();
    };

    const loadEditCalendar = async () => {
        if (!editCalGrid || !editCalTitle || !editCalState.reservationId) return;

        const { reservationId, month, year } = editCalState;
        editCalGrid.classList.add('is-loading');
        editCalTitle.textContent = new Date(year, month - 1, 1).toLocaleDateString('en-US', { month: 'long' });
        syncEditCalYearSelect();
        syncEditCalNextState();

        try {
            const url = new URL(`/staff/reservations/${reservationId}/availability`, window.location.origin);
            url.searchParams.set('month', month);
            url.searchParams.set('year', year);

            const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Availability request failed');

            const payload = await response.json();
            editCalState.availability = payload.availability || [];
            if (!editCalState.availabilityMap) editCalState.availabilityMap = {};
            editCalState.availability.forEach((entry) => {
                editCalState.availabilityMap[entry.date] = entry;
            });

            if (payload.amenities) editCalState.amenities = payload.amenities;
            if (payload.entrance_fee !== undefined) editCalState.entranceFee = payload.entrance_fee;
            if (payload.amount_paid !== undefined) editCalState.amountPaid = payload.amount_paid;
            if (payload.total_amount !== undefined) editCalState.originalTotal = payload.total_amount;

            syncSessionButtonsUI();
            updateEditCalendarCostSummary();
            renderEditCalendar();
        } catch (error) {
            editCalGrid.innerHTML = '<p class="edit-calendar__error p-4 text-center text-xs text-red-500">Unable to load availability. Please try again.</p>';
        } finally {
            editCalGrid.classList.remove('is-loading');
        }
    };

    const renderEditCalendar = () => {
        if (!editCalGrid) return;

        const { month, year, startDate, endDate, selectingEnd, hoverDate, startSlot, endSlot } = editCalState;
        const daysInMonth = new Date(year, month, 0).getDate();
        const leading = new Date(year, month - 1, 1).getDay();
        const today = todayISO();

        editCalGrid.innerHTML = '';

        for (let i = 0; i < leading; i++) {
            const empty = document.createElement('div');
            empty.className = 'edit-calendar__day edit-calendar__day--empty opacity-0 pointer-events-none';
            editCalGrid.appendChild(empty);
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const iso = `${year}-${String(month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const entry = editCalState.availabilityMap?.[iso];

            let isAvailable = false;
            let isPast = false;

            if (!selectingEnd) {
                // Step 1: Selecting Check-in date
                isAvailable = isSlotAvailableOnDate(iso, startSlot);
                isPast = entry ? entry.is_past : (iso < today && iso !== editCalState.currentStartDate);
            } else {
                // Step 2: Selecting Check-out date
                if (iso < startDate) {
                    isAvailable = isSlotAvailableOnDate(iso, startSlot);
                    isPast = entry ? entry.is_past : (iso < today && iso !== editCalState.currentStartDate);
                } else {
                    isAvailable = isRangeValidAndAvailable(startDate, iso, startSlot, endSlot);
                    isPast = false;
                }
            }

            const isStart = iso === startDate;
            const isEnd = iso === endDate;
            const isInRange = startDate && endDate && iso > startDate && iso < endDate;
            const isHoverRange = selectingEnd && hoverDate && startDate && iso > startDate && iso <= hoverDate && isRangeValidAndAvailable(startDate, hoverDate, startSlot, endSlot);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'edit-calendar__day';
            btn.textContent = d;
            btn.setAttribute('data-date', iso);

            if (isStart && isEnd) {
                btn.classList.add('is-range-start', 'is-range-end');
            } else if (isStart) {
                btn.classList.add('is-range-start');
            } else if (isEnd) {
                btn.classList.add('is-range-end');
            } else if (isInRange) {
                btn.classList.add('is-in-range');
            } else if (isHoverRange) {
                btn.classList.add('is-range-hover');
            }

            if (!isAvailable || isPast) {
                btn.classList.add('is-disabled');
                btn.disabled = true;
                btn.setAttribute('title', isPast ? 'Past date' : 'Booked / Unavailable');
                btn.setAttribute('aria-label', `${iso} unavailable (booked)`);
            } else {
                btn.classList.add('is-available');
                btn.setAttribute('aria-label', `${iso} available`);

                // Hover preview when selecting end date
                btn.addEventListener('mouseenter', () => {
                    if (editCalState.selectingEnd && editCalState.startDate && iso >= editCalState.startDate) {
                        if (isRangeValidAndAvailable(editCalState.startDate, iso, editCalState.startSlot, editCalState.endSlot)) {
                            editCalState.hoverDate = iso;
                            document.querySelectorAll('#editCalGrid .edit-calendar__day').forEach((dayBtn) => {
                                const date = dayBtn.getAttribute('data-date');
                                if (!date) return;
                                const inHover = date > editCalState.startDate && date <= iso;
                                dayBtn.classList.toggle('is-range-hover', inHover);
                            });
                        }
                    }
                });

                // Range click handling
                btn.addEventListener('click', () => {
                    if (!editCalState.selectingEnd) {
                        // First click: select check-in date
                        editCalState.startDate = iso;
                        editCalState.endDate = iso;
                        editCalState.selectingEnd = true;
                        editCalState.hoverDate = null;
                        if (editCalStepHelp) {
                            editCalStepHelp.textContent = 'Now click check-out date (or same date for 1 day)';
                        }
                        renderEditCalendar();
                        updateEditCalendarCostSummary();
                    } else {
                        // Second click: select check-out date
                        if (iso < editCalState.startDate) {
                            // User clicked an earlier date; restart with this as start date
                            editCalState.startDate = iso;
                            editCalState.endDate = iso;
                            editCalState.selectingEnd = true;
                            editCalState.hoverDate = null;
                            if (editCalStepHelp) {
                                editCalStepHelp.textContent = 'Now click check-out date (or same date for 1 day)';
                            }
                            renderEditCalendar();
                            updateEditCalendarCostSummary();
                        } else {
                            // Check that no unavailable dates are crossed in this continuous stay
                            if (!isRangeValidAndAvailable(editCalState.startDate, iso, editCalState.startSlot, editCalState.endSlot)) {
                                window.alert('Cannot select a continuous date range crossing booked or unavailable dates. All days in the stay must be available.');
                                return;
                            }

                            editCalState.endDate = iso;
                            editCalState.selectingEnd = false;
                            editCalState.hoverDate = null;
                            if (editCalStepHelp) {
                                editCalStepHelp.textContent = 'Dates selected! Click Apply Schedule to confirm.';
                            }
                            renderEditCalendar();
                            updateEditCalendarCostSummary();
                        }
                    }
                });
            }

            editCalGrid.appendChild(btn);
        }
    };

    // Session buttons listener
    document.querySelectorAll('.session-pill-btn').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const type = btn.dataset.slotType;
            const val = btn.dataset.slotVal;
            if (type === 'start') {
                editCalState.startSlot = val;
                if (editCalState.startDate === editCalState.endDate && val === 'Nighttime' && editCalState.endSlot === 'Daytime') {
                    editCalState.endSlot = 'Nighttime';
                }
            } else if (type === 'end') {
                editCalState.endSlot = val;
                if (editCalState.startDate === editCalState.endDate && val === 'Daytime' && editCalState.startSlot === 'Nighttime') {
                    editCalState.startSlot = 'Daytime';
                }
            }

            // If current selection is no longer valid with new session slots, validate and adjust if needed
            if (editCalState.startDate && !isSlotAvailableOnDate(editCalState.startDate, editCalState.startSlot)) {
                editCalState.startDate = null;
                editCalState.endDate = null;
                editCalState.selectingEnd = false;
                if (editCalStepHelp) {
                    editCalStepHelp.textContent = 'Selected date is booked for this session. Please click an available check-in date.';
                }
            } else if (editCalState.startDate && editCalState.endDate && !isRangeValidAndAvailable(editCalState.startDate, editCalState.endDate, editCalState.startSlot, editCalState.endSlot)) {
                editCalState.endDate = editCalState.startDate;
                editCalState.selectingEnd = true;
                if (editCalStepHelp) {
                    editCalStepHelp.textContent = 'Now click check-out date (or same date for 1 day)';
                }
            }

            syncSessionButtonsUI();
            updateEditCalendarCostSummary();
            renderEditCalendar();
        });
    });

    // Apply schedule button
    editCalApplyBtn?.addEventListener('click', () => {
        if (!editCalState.startDate) {
            window.alert('Please select an available check-in date.');
            return;
        }

        const sDate = editCalState.startDate;
        const eDate = editCalState.endDate || sDate;
        const sSlot = editCalState.startSlot || 'Daytime';
        const eSlot = editCalState.endSlot || sSlot;

        if (!isRangeValidAndAvailable(sDate, eDate, sSlot, eSlot)) {
            window.alert('Cannot apply schedule: One or more selected dates or sessions are already booked. Please choose an available date range.');
            return;
        }

        const oldMasterStart = document.getElementById('editReservationDate')?.value || editCalState.currentStartDate;
        const newMasterStart = sDate;
        const newMasterEnd = eDate;

        let shiftDays = 0;
        if (oldMasterStart && newMasterStart && oldMasterStart !== newMasterStart) {
            const msOld = new Date(oldMasterStart).getTime();
            const msNew = new Date(newMasterStart).getTime();
            shiftDays = Math.round((msNew - msOld) / (1000 * 60 * 60 * 24));
        }

        document.getElementById('editReservationDate').value = sDate;
        document.getElementById('editEndDate').value = eDate;
        document.getElementById('editStartSlot').value = sSlot;
        document.getElementById('editEndSlot').value = eSlot;

        // Shift amenity dates automatically by the relative day offset
        if (shiftDays !== 0) {
            const amenityItems = document.querySelectorAll('.edit-amenity-item');
            amenityItems.forEach((item) => {
                const sInput = item.querySelector('.edit-amenity-start-date');
                const eInput = item.querySelector('.edit-amenity-end-date');

                if (sInput && sInput.value) {
                    const [y, m, d] = sInput.value.split('-').map(Number);
                    const dtS = new Date(y, m - 1, d + shiftDays);
                    let shiftedS = `${dtS.getFullYear()}-${String(dtS.getMonth() + 1).padStart(2, '0')}-${String(dtS.getDate()).padStart(2, '0')}`;
                    if (shiftedS < newMasterStart) shiftedS = newMasterStart;
                    if (shiftedS > newMasterEnd) shiftedS = newMasterEnd;
                    sInput.value = shiftedS;
                    sInput.min = newMasterStart;
                    sInput.max = newMasterEnd;
                }

                if (eInput && eInput.value) {
                    const [y, m, d] = eInput.value.split('-').map(Number);
                    const dtE = new Date(y, m - 1, d + shiftDays);
                    let shiftedE = `${dtE.getFullYear()}-${String(dtE.getMonth() + 1).padStart(2, '0')}-${String(dtE.getDate()).padStart(2, '0')}`;
                    if (shiftedE < (sInput ? sInput.value : newMasterStart)) shiftedE = sInput ? sInput.value : newMasterStart;
                    if (shiftedE > newMasterEnd) shiftedE = newMasterEnd;
                    eInput.value = shiftedE;
                    if (sInput) eInput.min = sInput.value;
                    eInput.max = newMasterEnd;
                }
            });
        }

        updateEditFormScheduleCard();
        updateAmenitySelectsAvailability();
        closeEditCalendarModal();
    });

    const closeEditCalendarModal = () => {
        if (editCalendarModal) {
            editCalendarModal.classList.remove('is-open');
            editCalendarModal.setAttribute('aria-hidden', 'true');
        }
    };

    const openEditCalendarModal = () => {
        if (!editCalendarModal) return;
        editCalendarModal.classList.add('is-open');
        editCalendarModal.setAttribute('aria-hidden', 'false');
        loadEditCalendar();
    };

    editCalTrigger?.addEventListener('click', () => {
        openEditCalendarModal();
    });

    editCalCloseButtons.forEach((button) => {
        button.addEventListener('click', closeEditCalendarModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && editCalendarModal && editCalendarModal.classList.contains('is-open')) {
            closeEditCalendarModal();
        }
    });

    editCalYear?.addEventListener('change', () => {
        editCalState.year = Number(editCalYear.value) || editCalState.year;
        loadEditCalendar();
    });

    editCalPrev?.addEventListener('click', () => {
        editCalState.month -= 1;
        if (editCalState.month < 1) {
            editCalState.month = 12;
            editCalState.year -= 1;
        }
        syncEditCalYearSelect();
        loadEditCalendar();
    });

    editCalNext?.addEventListener('click', () => {
        if (editCalState.month === 12 && editCalState.year >= editCalMaxYear) return;
        editCalState.month += 1;
        if (editCalState.month > 12) {
            editCalState.month = 1;
            editCalState.year += 1;
        }
        syncEditCalYearSelect();
        loadEditCalendar();
    });

    editReservationBtn?.addEventListener('click', () => {
        if (currentModalReservationId) {
            openEditForm(currentModalReservationId);
        }
    });

    cancelEditBtn?.addEventListener('click', closeEditForm);

    editReservationForm?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(editReservationForm);
        const reservationId = formData.get('reservation_id');
        const submitButton = editReservationForm.querySelector('button[type="submit"]');

        const amenitiesPayload = Array.from(document.querySelectorAll('.edit-amenity-item')).map(item => ({
            id: item.dataset.raId,
            amenity_id: item.querySelector('.edit-amenity-select')?.value,
            start_date: item.querySelector('.edit-amenity-start-date')?.value,
            end_date: item.querySelector('.edit-amenity-end-date')?.value,
            start_slot: item.querySelector('.edit-amenity-start-slot')?.value,
            end_slot: item.querySelector('.edit-amenity-end-slot')?.value,
        }));

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
                            end_date: formData.get('end_date'),
                            start_slot: formData.get('start_slot'),
                            end_slot: formData.get('end_slot'),
                            number_of_guests: formData.get('number_of_guests'),
                            status: formData.get('status'),
                            amenities: amenitiesPayload,
                        }),
                    });

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(payload.message || 'Unable to update reservation.');
                    }

                    const updated = payload.reservation || {};

                    // Update global reservationData cache
                    if (reservationData[reservationId]) {
                        reservationData[reservationId] = {
                            ...reservationData[reservationId],
                            ...updated,
                        };
                    }

                    // Update table row with new data
                    const tableRow = document.querySelector(`tr[data-reservation-id="${reservationId}"]`);
                    if (tableRow) {
                        const bName = updated.booker_name || formData.get('booker_name');
                        const bEmail = updated.email || formData.get('email');
                        const bPhone = updated.phone || formData.get('phone');
                        const sDate = updated.reservation_date || formData.get('reservation_date');
                        const eDate = updated.end_date || formData.get('end_date') || sDate;
                        const tDays = updated.total_days || 1;
                        const status = updated.status || formData.get('status');
                        const guests = updated.number_of_guests || formData.get('number_of_guests');

                        tableRow.setAttribute('data-booker-name', bName);
                        tableRow.setAttribute('data-email', bEmail);
                        tableRow.setAttribute('data-phone', bPhone);
                        tableRow.setAttribute('data-reservation-date', sDate);
                        tableRow.setAttribute('data-status', String(status).toLowerCase());
                        tableRow.setAttribute('data-guests', guests);
                        if (updated.total_amount !== undefined) {
                            tableRow.setAttribute('data-total-amount', updated.total_amount);
                        }

                        const cells = tableRow.querySelectorAll('td');
                        if (cells[0]) {
                            cells[0].innerHTML = `
                                <div class="resv-booker flex items-center gap-3">
                                    <span class="resv-avatar flex h-9 w-9 shrink-0 select-none items-center justify-center rounded-full bg-gradient-to-br from-hp-green to-hp-green-mid text-[0.78rem] font-bold uppercase tracking-[0.03em] text-white dark:from-[#2e7d55] dark:to-[#1c5c3c]">${escapeHtml(getInitials(bName))}</span>
                                    <div class="resv-booker__info flex min-w-0 flex-col gap-0.5">
                                        <div class="guest-name font-semibold text-hp-text">${escapeHtml(bName)}</div>
                                        <div class="guest-meta mt-0.5 text-[0.84rem] text-hp-text-muted">${escapeHtml(bEmail)}</div>
                                    </div>
                                </div>
                            `;
                        }
                        if (cells[1]) {
                            if (eDate && eDate !== sDate) {
                                cells[1].innerHTML = `
                                    <div>
                                        <span class="font-semibold text-hp-text">${formatDateLong(sDate)} – ${formatDateLong(eDate)}</span>
                                        <div class="text-[0.75rem] text-hp-text-muted">(${tDays} Days Stay)</div>
                                    </div>
                                `;
                            } else {
                                cells[1].textContent = formatDateLong(sDate);
                            }
                        }
                        if (cells[3]) {
                            cells[3].textContent = guests;
                        }
                        if (cells[4]) {
                            cells[4].innerHTML = `<span class="reservation-status reservation-status--${String(status).toLowerCase()} inline-flex items-center justify-center rounded-[0.4rem] px-3 py-1.5 text-[0.8rem] font-bold capitalize ${status === 'Pending' ? 'bg-[#fff3cd] text-[#856404] dark:bg-glass dark:text-[#ffd54f]' : 'bg-[#d4edda] text-[#155724] dark:bg-glass dark:text-[#81c784]'}">${status}</span>`;
                        }
                        if (cells[5] && updated.total_amount !== undefined) {
                            cells[5].textContent = `₱${Number(updated.total_amount).toFixed(2)}`;
                        }
                    }

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

    // Refresh table functionality
    refreshTableBtn?.addEventListener('click', async () => {
        try {
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

            if (data.reservations) {
                Object.assign(reservationData, data.reservations);
                renderTableFromData(reservationData);
            } else {
                throw new Error('No reservation data received');
            }
        } catch (error) {
            console.error('Error refreshing table:', error);
            window.alert('Failed to refresh table. Please try again.');
            renderTableFromData(reservationData);
        }
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

                    queueToast(`Reservation #${reservationId} checked out successfully.`);
                    window.location.reload();
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
                    companions: getAllCheckInCompanions(),
                    include_pool: formData.get('check_in_include_pool') || '0',
                }),
            });

            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(payload.message || 'Unable to check in this reservation.');
            }

            queueToast(`Reservation #${pendingReservationId} checked in successfully and marked as Paid.`);
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
        const header = ['Reservation ID', 'Booker', 'Email', 'Reservation Date', 'Session', 'Guests', 'Status', 'Amount'];
        const body = visibleRows.map(row => {
            const cells = row.querySelectorAll('td');
            return [
                `#${row.dataset.reservationId || ''}`,
                row.dataset.bookerName || '',
                row.dataset.email || '',
                cells[2]?.textContent.trim() || '',
                (cells[3]?.textContent || '').replace(/\s+/g, ' ').trim(),
                cells[4]?.textContent.trim() || '',
                cells[5]?.textContent.trim() || '',
                cells[6]?.textContent.trim() || '',
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

    // Success toasts — show anything queued for after a reload and convert
    // server-rendered flash banners (session('success')) into toasts.
    convertFlashToToast();
    showPendingToast();
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['staff_reservations']());