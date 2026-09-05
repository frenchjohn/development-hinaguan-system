import { Html5Qrcode } from 'html5-qrcode';
import { showToast, queueToast, showPendingToast, convertFlashToToast } from './toast.js';

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
        const todayStr = new Date().toISOString().split('T')[0];
        const resDateStr = reservation.reservation_date ? String(reservation.reservation_date).split('T')[0] : '';
        const isToday = resDateStr === todayStr;
        const statusLower = String(reservation.status || '').toLowerCase();
        const isPendingOrConfirmed = ['pending', 'confirmed'].includes(statusLower);
        const isPastArrival = resDateStr && resDateStr < todayStr && isPendingOrConfirmed;

        let daysOverdue = 0;
        if (isPastArrival) {
            const rDate = new Date(resDateStr);
            const tDate = new Date(todayStr);
            daysOverdue = Math.max(1, Math.round((tDate - rDate) / (1000 * 60 * 60 * 24)));
        }

        const formatDate = (dateStr, endDateStr, totalDays) => {
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr;
            const sFormatted = date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            if (endDateStr && endDateStr !== dateStr) {
                const endDate = new Date(endDateStr);
                const eFormatted = endDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                const daysCount = totalDays || (Math.round((endDate - date) / (1000 * 60 * 60 * 24)) + 1);
                return `<div><span style="font-weight:600;" class="${isPastArrival ? 'text-[#dc2626] dark:text-[#f87171]' : ''}">${escapeHtml(sFormatted)} – ${escapeHtml(eFormatted)}</span><div style="font-size:0.75rem;opacity:0.75;">(${daysCount} Days Stay)</div>${isPastArrival ? `<div style="font-size:0.68rem;font-weight:600;color:#dc2626;margin-top:2px;"><i class="bi bi-exclamation-triangle-fill me-1"></i>Overdue Arrival</div>` : ''}</div>`;
            }
            return `<div><span style="font-weight:600;" class="${isPastArrival ? 'text-[#dc2626] dark:text-[#f87171]' : ''}">${escapeHtml(sFormatted)}</span><div style="font-size:0.75rem;opacity:0.75;">(1 Day Stay)</div>${isPastArrival ? `<div style="font-size:0.68rem;font-weight:600;color:#dc2626;margin-top:2px;"><i class="bi bi-exclamation-triangle-fill me-1"></i>Overdue Arrival</div>` : ''}</div>`;
        };

        const badgeHtml = isToday
            ? `<span class="today-reservation-badge ml-1.5 inline-block rounded-md bg-[#ff9800] px-2 py-0.5 text-[0.65rem] font-bold tracking-wide text-white dark:bg-[#ffb74d]">TODAY</span>`
            : (isPastArrival
                ? `<span class="past-reservation-badge ml-1.5 inline-flex items-center gap-1 rounded-md bg-[#ef4444] px-2 py-0.5 text-[0.65rem] font-bold tracking-wide text-white shadow-sm dark:bg-[#dc2626]" title="Arrival date was ${escapeHtml(resDateStr)} (${daysOverdue} days overdue)">
                    <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                    PAST ARRIVAL (${daysOverdue}d ago)
                </span>`
                : '');

        return `
                <td class="py-3.5 px-3 w-20 whitespace-nowrap">
                    <span class="inline-flex items-center rounded-lg bg-[#e8f5e9] px-2 py-0.5 text-xs font-bold text-[#1b4332] font-mono dark:bg-[rgba(46,125,50,0.25)] dark:text-[#9ca3af]">#${escapeHtml(reservation.id)}</span>
                </td>
                <td>
                    <div class="resv-booker flex items-center gap-3">
                        <span class="resv-avatar">${escapeHtml(getInitials(reservation.booker_name))}</span>
                        <div class="resv-booker__info">
                            <div class="guest-name font-bold text-sm text-[#183d28] dark:text-[#e8f5e9] flex items-center gap-1.5 flex-wrap">
                                <span>${escapeHtml(reservation.booker_name)}</span>
                                ${badgeHtml}
                            </div>
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

    const checkInEntranceOption = document.getElementById('checkInEntranceOption');
    const checkInEntranceOptionHelp = document.getElementById('checkInEntranceOptionHelp');
    const checkInPoolOption = document.getElementById('checkInPoolOption');
    const checkInPoolOptionHelp = document.getElementById('checkInPoolOptionHelp');
    const checkInPrimaryGuestPoolWrap = document.getElementById('checkInPrimaryGuestPoolWrap');
    const checkInPrimaryGuestHasPool = document.getElementById('checkInPrimaryGuestHasPool');
    const checkInCompanionPoolWrap = document.getElementById('checkInCompanionPoolWrap');
    const checkInCompanionHasPool = document.getElementById('checkInCompanionHasPool');
    const checkInBulkCompanionPoolWrap = document.getElementById('checkInBulkCompanionPoolWrap');
    const checkInBulkCompanionQuantity = document.getElementById('checkInBulkCompanionQuantity');
    const checkInBulkPoolQuantity = document.getElementById('checkInBulkPoolQuantity');
    const checkInBulkPoolQtyHint = document.getElementById('checkInBulkPoolQtyHint');
    const checkInBulkQtyMinusBtn = document.getElementById('checkInBulkQtyMinusBtn');
    const checkInBulkQtyPlusBtn = document.getElementById('checkInBulkQtyPlusBtn');

    const syncCheckInEntranceOptionUI = () => {
        const opt = checkInEntranceOption?.value || 'all_paid';
        if (checkInEntranceOptionHelp) {
            if (opt === 'all_free') {
                checkInEntranceOptionHelp.textContent = 'All guests receive complimentary entrance at no additional charge (Promo • ₱0.00).';
            } else {
                checkInEntranceOptionHelp.textContent = 'All guests will pay the standard entrance fee based on age.';
            }
        }
    };

    const syncCheckInPoolOptionUI = () => {
        const opt = checkInPoolOption?.value || 'no_pool';
        const isSpecific = opt === 'specific';
        const isAllPaid = opt === 'all_paid';
        const isAllFree = opt === 'all_free';
        const isNoPool = opt === 'no_pool';

        if (checkInPrimaryGuestPoolWrap) {
            checkInPrimaryGuestPoolWrap.style.display = isSpecific ? 'flex' : 'none';
        }
        if (checkInCompanionPoolWrap) {
            checkInCompanionPoolWrap.style.display = isSpecific ? 'flex' : 'none';
        }
        if (checkInBulkCompanionPoolWrap) {
            checkInBulkCompanionPoolWrap.style.display = isSpecific ? 'flex' : 'none';
        }

        if (checkInPoolOptionHelp) {
            if (isNoPool) {
                checkInPoolOptionHelp.textContent = 'No pool fee will be charged for any guest in this reservation.';
            } else if (isSpecific) {
                checkInPoolOptionHelp.textContent = 'Pool fee will only be charged for individual guests or bulk group counts selected below.';
            } else if (isAllPaid) {
                checkInPoolOptionHelp.textContent = 'Standard pool fee will be applied to all guests in this reservation.';
            } else if (isAllFree) {
                checkInPoolOptionHelp.textContent = 'All guests receive complimentary pool access at no additional charge (Promo • ₱0.00).';
            }
        }

        const legacyInput = document.getElementById('checkInIncludePoolLegacy');
        if (legacyInput) {
            legacyInput.value = (isAllPaid || isAllFree || isSpecific) ? '1' : '0';
        }
    };

    const syncCheckInBulkPoolMax = () => {
        if (!checkInBulkCompanionQuantity || !checkInBulkPoolQuantity) return;
        const maxVal = parseInt(checkInBulkCompanionQuantity.value, 10) || 1;
        checkInBulkPoolQuantity.max = maxVal;
        const curPoolVal = parseInt(checkInBulkPoolQuantity.value, 10) || 0;
        const clampedVal = Math.min(Math.max(0, curPoolVal), maxVal);
        checkInBulkPoolQuantity.value = clampedVal;
        if (checkInBulkPoolQtyHint) {
            checkInBulkPoolQtyHint.textContent = `${clampedVal} of ${maxVal}`;
        }
    };

    checkInBulkCompanionQuantity?.addEventListener('input', syncCheckInBulkPoolMax);
    checkInBulkCompanionQuantity?.addEventListener('change', syncCheckInBulkPoolMax);
    checkInBulkPoolQuantity?.addEventListener('input', syncCheckInBulkPoolMax);
    checkInBulkPoolQuantity?.addEventListener('change', syncCheckInBulkPoolMax);

    checkInBulkQtyMinusBtn?.addEventListener('click', () => {
        if (!checkInBulkCompanionQuantity) return;
        const cur = parseInt(checkInBulkCompanionQuantity.value, 10) || 1;
        if (cur > 1) {
            checkInBulkCompanionQuantity.value = cur - 1;
            syncCheckInBulkPoolMax();
        }
    });

    checkInBulkQtyPlusBtn?.addEventListener('click', () => {
        if (!checkInBulkCompanionQuantity) return;
        const cur = parseInt(checkInBulkCompanionQuantity.value, 10) || 1;
        if (cur < 500) {
            checkInBulkCompanionQuantity.value = cur + 1;
            syncCheckInBulkPoolMax();
        }
    });

    const getAgeFromGroup = (ageGroup) => {
        const ageMap = {
            '0-12': 6,
            '13-17': 15,
            '18-59': 30,
            '60+': 65
        };
        return ageMap[ageGroup] || 30;
    };

    const getTotalCheckInCompanionCount = () => {
        const singleCount = checkInCompanions.length;
        const bulkCount = bulkCompanionGroups.reduce((acc, g) => acc + (parseInt(g.quantity, 10) || 0), 0);
        return singleCount + bulkCount;
    };

    // Modal Staged companion search and filter controls
    const checkInModalCompanionPreviewList = document.getElementById('checkInModalCompanionPreviewList');
    const checkInModalCompanionPreviewCountBadge = document.getElementById('checkInModalCompanionPreviewCountBadge');
    const checkInModalCompanionFooterSummary = document.getElementById('checkInModalCompanionFooterSummary');
    const checkInModalCompanionClearAllBtn = document.getElementById('checkInModalCompanionClearAllBtn');
    const toggleCheckInModalCompanionFilterBtn = document.getElementById('toggleCheckInModalCompanionFilterBtn');
    const checkInModalCompanionFilterToolbar = document.getElementById('checkInModalCompanionFilterToolbar');
    const checkInModalCompanionSearchInput = document.getElementById('checkInModalCompanionSearchInput');
    const checkInModalCompanionFilterGender = document.getElementById('checkInModalCompanionFilterGender');
    const checkInModalCompanionFilterAgeGroup = document.getElementById('checkInModalCompanionFilterAgeGroup');
    const checkInModalCompanionFilterResetBtn = document.getElementById('checkInModalCompanionFilterResetBtn');

    toggleCheckInModalCompanionFilterBtn?.addEventListener('click', () => {
        if (!checkInModalCompanionFilterToolbar) return;
        const isHidden = checkInModalCompanionFilterToolbar.classList.contains('hidden');
        if (isHidden) {
            checkInModalCompanionFilterToolbar.classList.remove('hidden');
            checkInModalCompanionFilterToolbar.classList.add('flex');
            toggleCheckInModalCompanionFilterBtn.classList.add('bg-hp-green/15', 'border-hp-green/40', 'text-hp-green');
            checkInModalCompanionSearchInput?.focus();
        } else {
            checkInModalCompanionFilterToolbar.classList.add('hidden');
            checkInModalCompanionFilterToolbar.classList.remove('flex');
            toggleCheckInModalCompanionFilterBtn.classList.remove('bg-hp-green/15', 'border-hp-green/40', 'text-hp-green');
        }
    });

    checkInModalCompanionSearchInput?.addEventListener('input', () => renderCheckInModalCompanionPreview());
    checkInModalCompanionFilterGender?.addEventListener('change', () => renderCheckInModalCompanionPreview());
    checkInModalCompanionFilterAgeGroup?.addEventListener('change', () => renderCheckInModalCompanionPreview());
    checkInModalCompanionFilterResetBtn?.addEventListener('click', () => {
        if (checkInModalCompanionSearchInput) checkInModalCompanionSearchInput.value = '';
        if (checkInModalCompanionFilterGender) checkInModalCompanionFilterGender.value = '';
        if (checkInModalCompanionFilterAgeGroup) checkInModalCompanionFilterAgeGroup.value = '';
        renderCheckInModalCompanionPreview();
    });

    const handleCheckInBulkQtyChange = (bIdx, rawVal) => {
        if (isNaN(bIdx) || !bulkCompanionGroups[bIdx]) return;
        const group = bulkCompanionGroups[bIdx];
        let val = parseInt(rawVal, 10);
        if (isNaN(val) || val < 1) val = 1;
        if (val > 500) val = 500;
        group.quantity = val;
        const curPoolOpt = checkInPoolOption?.value || 'no_pool';
        if (curPoolOpt === 'all_paid' || curPoolOpt === 'all_free') {
            group.pool_quantity = val;
        } else if (curPoolOpt === 'specific') {
            group.pool_quantity = Math.min(group.pool_quantity || 0, val);
        } else {
            group.pool_quantity = 0;
        }
        renderCheckInCompanions();
        renderCheckInModalCompanionPreview();
    };

    const renderCheckInModalCompanionPreview = () => {
        if (!checkInModalCompanionPreviewList) return;
        checkInModalCompanionPreviewList.innerHTML = '';

        const totalCount = getTotalCheckInCompanionCount();
        const currentPoolOpt = checkInPoolOption?.value || 'no_pool';
        const resAmenities = currentReservationData?.reservation_amenities || [];

        if (checkInModalCompanionPreviewCountBadge) {
            checkInModalCompanionPreviewCountBadge.textContent = `${totalCount} companion${totalCount === 1 ? '' : 's'}`;
        }
        if (checkInModalCompanionFooterSummary) {
            checkInModalCompanionFooterSummary.textContent = totalCount === 0 
                ? '0 companions added so far' 
                : `${totalCount} companion${totalCount === 1 ? '' : 's'} staged (Ready to apply)`;
        }
        if (checkInModalCompanionClearAllBtn) {
            checkInModalCompanionClearAllBtn.classList.toggle('hidden', totalCount === 0);
        }

        const searchTerm = (checkInModalCompanionSearchInput?.value || '').trim().toLowerCase();
        const filterGender = (checkInModalCompanionFilterGender?.value || '').trim();
        const filterAgeGroup = (checkInModalCompanionFilterAgeGroup?.value || '').trim();

        if (checkInModalCompanionFilterResetBtn) {
            const isFiltered = Boolean(searchTerm || filterGender || filterAgeGroup);
            checkInModalCompanionFilterResetBtn.classList.toggle('hidden', !isFiltered);
            checkInModalCompanionFilterResetBtn.classList.toggle('inline-flex', isFiltered);
        }

        let visibleModalCount = 0;

        // Render Single Companions in modal preview
        checkInCompanions.forEach((companion, index) => {
            const fullName = `${companion.first_name || ''} ${companion.middle_name || ''} ${companion.last_name || ''}`.toLowerCase();
            const matchesSearch = !searchTerm || fullName.includes(searchTerm);
            const matchesGender = !filterGender || (companion.gender || '').toLowerCase() === filterGender.toLowerCase();

            let matchesAgeGroup = true;
            if (filterAgeGroup) {
                const parsedAge = parseInt(companion.age, 10);
                if (filterAgeGroup === '0-12') {
                    matchesAgeGroup = !isNaN(parsedAge) && parsedAge <= 12;
                } else if (filterAgeGroup === '13-17') {
                    matchesAgeGroup = !isNaN(parsedAge) && parsedAge >= 13 && parsedAge <= 17;
                } else if (filterAgeGroup === '18-59') {
                    matchesAgeGroup = !isNaN(parsedAge) && parsedAge >= 18 && parsedAge <= 59;
                } else if (filterAgeGroup === '60+') {
                    matchesAgeGroup = !isNaN(parsedAge) && parsedAge >= 60;
                }
            }

            if (!matchesSearch || !matchesGender || !matchesAgeGroup) {
                return;
            }

            visibleModalCount++;
            const nationality = companion.is_foreigner ? 'Foreigner' : 'Filipino';
            const parsedAge = parseInt(companion.age, 10);
            const rateLabel = (!isNaN(parsedAge) && parsedAge <= 12) ? 'Child' : 'Adult';

            let poolBadge = '';
            if (currentPoolOpt === 'all_paid') {
                poolBadge = '<span class="inline-flex items-center gap-1 rounded bg-sky-500/15 text-sky-700 dark:text-sky-300 px-2 py-0.5 text-[0.65rem] font-bold"><i class="bi bi-water"></i> Pool Pass</span>';
            } else if (currentPoolOpt === 'all_free') {
                poolBadge = '<span class="inline-flex items-center gap-1 rounded bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[0.65rem] font-bold"><i class="bi bi-water"></i> Free Pool</span>';
            } else if (currentPoolOpt === 'specific') {
                poolBadge = companion.has_pool_access
                    ? `<button type="button" class="inline-flex items-center gap-1 rounded bg-sky-500/20 text-sky-800 dark:text-sky-300 border border-sky-500/30 px-1.5 py-0.5 text-[0.65rem] font-bold cursor-pointer hover:bg-sky-500/30 transition-colors" data-modal-toggle-companion-pool="${index}" title="Click to remove pool pass"><i class="bi bi-water"></i> Pool Pass ✓</button>`
                    : `<button type="button" class="inline-flex items-center gap-1 rounded bg-gray-500/15 text-hp-text-muted border border-glass-border px-1.5 py-0.5 text-[0.65rem] font-medium cursor-pointer hover:bg-glass-hover transition-colors" data-modal-toggle-companion-pool="${index}" title="Click to grant pool pass">+ Pool Pass</button>`;
            }

            let amenityBadge = '';
            if (resAmenities.length > 1) {
                const foundAm = resAmenities.find(ra => String(ra.amenity?.id || ra.amenity_id) === String(companion.amenity_id));
                const amName = foundAm?.amenity?.amenities_name || 'Amenity';
                amenityBadge = `<span class="inline-flex items-center gap-1 rounded bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 px-1.5 py-0.5 text-[0.65rem] font-bold"><i class="bi bi-house-door-fill"></i> ${escapeHtml(amName)}</span>`;
            }

            const item = document.createElement('div');
            item.className = 'group relative flex items-center justify-between gap-2.5 rounded-xl border border-glass-border bg-glass p-2.5 shadow-xs transition-all hover:border-hp-green/40 hover:bg-glass-hover';
            item.innerHTML = `
                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-hp-green/15 text-xs font-bold text-hp-green">
                        ${escapeHtml(getInitials(`${companion.first_name || ''} ${companion.last_name || ''}`))}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-xs font-bold text-hp-text dark:text-[#f3f4f6] truncate">${escapeHtml(companion.first_name)} ${escapeHtml(companion.last_name)}</span>
                            <span class="rounded bg-black/5 dark:bg-white/10 px-1.5 py-0.5 text-[0.65rem] font-medium text-hp-text-muted">${escapeHtml(companion.gender || 'Male')}, ${escapeHtml(companion.age || '—')} yrs (${rateLabel})</span>
                            <span class="rounded bg-black/5 dark:bg-white/10 px-1.5 py-0.5 text-[0.65rem] font-medium text-hp-text-muted">${escapeHtml(nationality)}</span>
                            ${amenityBadge}
                            ${poolBadge}
                        </div>
                    </div>
                </div>
                <button type="button" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-red-500/20 bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all duration-200 cursor-pointer shadow-xs active:scale-95" data-modal-remove-single="${index}" title="Remove staged companion">
                    <i class="bi bi-trash3 text-xs"></i>
                </button>
            `;
            checkInModalCompanionPreviewList.appendChild(item);
        });

        // Render Bulk Companion Groups in modal preview
        bulkCompanionGroups.forEach((group, index) => {
            const matchesSearch = !searchTerm;
            const matchesGender = !filterGender || (group.gender || '').toLowerCase() === filterGender.toLowerCase();
            const matchesAgeGroup = !filterAgeGroup || group.age_group === filterAgeGroup;

            if (!matchesSearch || !matchesGender || !matchesAgeGroup) {
                return;
            }

            visibleModalCount++;
            const nationality = group.is_foreigner ? 'Foreigner' : 'Filipino';
            const rateLabel = (group.age_group === '0-12') ? 'Child' : 'Adult';

            let poolBadge = '';
            if (currentPoolOpt === 'all_paid') {
                poolBadge = `<span class="inline-flex items-center gap-1 rounded bg-sky-500/15 text-sky-700 dark:text-sky-300 px-2 py-0.5 text-[0.65rem] font-bold"><i class="bi bi-water"></i> All with Pool</span>`;
            } else if (currentPoolOpt === 'all_free') {
                poolBadge = `<span class="inline-flex items-center gap-1 rounded bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[0.65rem] font-bold"><i class="bi bi-water"></i> All Free Pool</span>`;
            } else if (currentPoolOpt === 'specific') {
                const pQty = group.pool_quantity || 0;
                poolBadge = `
                    <div class="inline-flex items-center gap-1 rounded-lg bg-sky-500/15 border border-sky-500/30 px-1.5 py-0.5 text-[0.65rem] font-bold text-sky-800 dark:text-sky-300">
                        <i class="bi bi-water text-sky-600 dark:text-sky-400"></i>
                        <button type="button" class="flex h-4 w-4 items-center justify-center rounded bg-sky-600/20 text-sky-900 dark:text-white hover:bg-sky-600/40 text-[0.65rem] font-extrabold transition-colors cursor-pointer" data-modal-bulk-pool-dec="${index}" title="Decrease pool pass">−</button>
                        <span class="px-1 min-w-[1.8rem] text-center font-bold text-[0.68rem]">${pQty} / ${group.quantity}</span>
                        <button type="button" class="flex h-4 w-4 items-center justify-center rounded bg-sky-600/20 text-sky-900 dark:text-white hover:bg-sky-600/40 text-[0.65rem] font-extrabold transition-colors cursor-pointer" data-modal-bulk-pool-inc="${index}" title="Increase pool pass">+</button>
                    </div>
                `;
            }

            let amenityBadge = '';
            if (resAmenities.length > 1) {
                const foundAm = resAmenities.find(ra => String(ra.amenity?.id || ra.amenity_id) === String(group.amenity_id));
                const amName = foundAm?.amenity?.amenities_name || 'Amenity';
                amenityBadge = `<span class="inline-flex items-center gap-1 rounded bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 px-1.5 py-0.5 text-[0.65rem] font-bold">🏠 ${escapeHtml(amName)}</span>`;
            }

            const item = document.createElement('div');
            item.className = 'group relative flex items-center justify-between gap-2.5 rounded-xl border border-glass-border bg-glass p-2.5 shadow-xs transition-all hover:border-hp-green/40 hover:bg-glass-hover';
            item.innerHTML = `
                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-600/15 text-xs font-bold text-emerald-700 dark:text-emerald-300">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-xs font-bold text-hp-text dark:text-[#f3f4f6]">Bulk: ${group.quantity} guests</span>
                            <span class="rounded bg-black/5 dark:bg-white/10 px-1.5 py-0.5 text-[0.65rem] font-medium text-hp-text-muted">${escapeHtml(group.gender)}, Age ${escapeHtml(group.age_group)} (${rateLabel})</span>
                            <span class="rounded bg-black/5 dark:bg-white/10 px-1.5 py-0.5 text-[0.65rem] font-medium text-hp-text-muted">${escapeHtml(nationality)}</span>
                            ${amenityBadge}
                            ${poolBadge}
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <div class="flex items-center rounded-lg border border-glass-border bg-glass p-0.5">
                        <button type="button" class="flex h-6 w-6 items-center justify-center rounded bg-black/5 dark:bg-white/10 text-xs font-extrabold text-hp-text hover:bg-black/10 transition-colors cursor-pointer" data-modal-bulk-qty-dec="${index}" title="Decrease quantity">−</button>
                        <input type="number" min="1" max="500" value="${group.quantity}" data-modal-bulk-qty-input="${index}" class="no-spinners w-9 border-0 bg-transparent text-center font-display text-xs font-bold text-hp-green-dark dark:text-hp-green focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" title="Type to change bulk quantity directly">
                        <button type="button" class="flex h-6 w-6 items-center justify-center rounded bg-black/5 dark:bg-white/10 text-xs font-extrabold text-hp-text hover:bg-black/10 transition-colors cursor-pointer" data-modal-bulk-qty-inc="${index}" title="Increase quantity">+</button>
                    </div>
                    <button type="button" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-red-500/20 bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all duration-200 cursor-pointer shadow-xs active:scale-95" data-modal-remove-bulk="${index}" title="Remove staged bulk group">
                        <i class="bi bi-trash3 text-xs"></i>
                    </button>
                </div>
            `;
            checkInModalCompanionPreviewList.appendChild(item);
        });

        if (visibleModalCount === 0) {
            checkInModalCompanionPreviewList.innerHTML = totalCount === 0
                ? '<div class="py-8 text-center text-xs text-hp-text-muted/70 italic"><i class="bi bi-person-plus text-2xl block mb-1 opacity-50"></i>No companions staged yet.<br>Fill the form on the left to add companions.</div>'
                : '<div class="py-6 text-center text-xs text-hp-text-muted/70 italic"><i class="bi bi-search text-xl block mb-1 opacity-50"></i>No staged companions match the search/filter criteria.</div>';
        }
    };

    // In-modal preview click listeners (Remove single, remove bulk, toggle pool, inc/dec pool/free, inc/dec bulk qty)
    checkInModalCompanionPreviewList?.addEventListener('click', (e) => {
        const removeSingleBtn = e.target.closest('[data-modal-remove-single]');
        if (removeSingleBtn) {
            const idx = parseInt(removeSingleBtn.dataset.modalRemoveSingle, 10);
            if (!isNaN(idx) && checkInCompanions[idx]) {
                const comp = checkInCompanions[idx];
                const name = comp ? `${comp.first_name} ${comp.last_name}` : 'this companion';
                openRemoveCompanionModal(
                    `Are you sure you want to remove <strong>${escapeHtml(name)}</strong> from staged companions?`,
                    () => {
                        checkInCompanions.splice(idx, 1);
                        renderCheckInCompanions();
                        renderCheckInModalCompanionPreview();
                    }
                );
            }
            return;
        }

        const removeBulkBtn = e.target.closest('[data-modal-remove-bulk]');
        if (removeBulkBtn) {
            const bIdx = parseInt(removeBulkBtn.dataset.modalRemoveBulk, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const grp = bulkCompanionGroups[bIdx];
                const desc = grp ? `${grp.quantity} guests (${grp.gender}, Age ${grp.age_group})` : 'this bulk group';
                openRemoveCompanionModal(
                    `Are you sure you want to remove the staged bulk group with <strong>${escapeHtml(desc)}</strong>?`,
                    () => {
                        bulkCompanionGroups.splice(bIdx, 1);
                        renderCheckInCompanions();
                        renderCheckInModalCompanionPreview();
                    }
                );
            }
            return;
        }

        // Increase/Decrease Bulk Group Quantity inside modal preview
        const bulkQtyIncBtn = e.target.closest('[data-modal-bulk-qty-inc]');
        if (bulkQtyIncBtn) {
            const bIdx = parseInt(bulkQtyIncBtn.dataset.modalBulkQtyInc, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const group = bulkCompanionGroups[bIdx];
                handleCheckInBulkQtyChange(bIdx, (parseInt(group.quantity, 10) || 1) + 1);
            }
            return;
        }

        const bulkQtyDecBtn = e.target.closest('[data-modal-bulk-qty-dec]');
        if (bulkQtyDecBtn) {
            const bIdx = parseInt(bulkQtyDecBtn.dataset.modalBulkQtyDec, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const group = bulkCompanionGroups[bIdx];
                handleCheckInBulkQtyChange(bIdx, (parseInt(group.quantity, 10) || 1) - 1);
            }
            return;
        }

        // Toggle Single Companion Pool Pass inside modal preview
        const togglePoolBtn = e.target.closest('[data-modal-toggle-companion-pool]');
        if (togglePoolBtn) {
            const idx = parseInt(togglePoolBtn.dataset.modalToggleCompanionPool, 10);
            if (!isNaN(idx) && checkInCompanions[idx]) {
                checkInCompanions[idx].has_pool_access = !checkInCompanions[idx].has_pool_access;
                renderCheckInCompanions();
                renderCheckInModalCompanionPreview();
            }
            return;
        }

        // Increase/Decrease Bulk Pool Passes inside modal preview
        const bulkPoolIncBtn = e.target.closest('[data-modal-bulk-pool-inc]');
        if (bulkPoolIncBtn) {
            const bIdx = parseInt(bulkPoolIncBtn.dataset.modalBulkPoolInc, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const group = bulkCompanionGroups[bIdx];
                const cur = group.pool_quantity || 0;
                if (cur < group.quantity) {
                    group.pool_quantity = cur + 1;
                    renderCheckInCompanions();
                    renderCheckInModalCompanionPreview();
                }
            }
            return;
        }

        const bulkPoolDecBtn = e.target.closest('[data-modal-bulk-pool-dec]');
        if (bulkPoolDecBtn) {
            const bIdx = parseInt(bulkPoolDecBtn.dataset.modalBulkPoolDec, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const group = bulkCompanionGroups[bIdx];
                const cur = group.pool_quantity || 0;
                if (cur > 0) {
                    group.pool_quantity = cur - 1;
                    renderCheckInCompanions();
                    renderCheckInModalCompanionPreview();
                }
            }
            return;
        }
    });

    checkInModalCompanionPreviewList?.addEventListener('change', (e) => {
        const input = e.target.closest('[data-modal-bulk-qty-input]');
        if (input) {
            const bIdx = parseInt(input.dataset.modalBulkQtyInput, 10);
            handleCheckInBulkQtyChange(bIdx, input.value);
        }
    });

    checkInModalCompanionClearAllBtn?.addEventListener('click', () => {
        if (checkInCompanions.length === 0 && bulkCompanionGroups.length === 0) return;
        openRemoveCompanionModal(
            `Are you sure you want to <strong>clear all staged companions</strong>?`,
            () => {
                checkInCompanions = [];
                bulkCompanionGroups = [];
                renderCheckInCompanions();
                renderCheckInModalCompanionPreview();
            }
        );
    });

    // Main Check-In Card Search & Filter controls
    const checkInCompanionCountBadge = document.getElementById('checkInCompanionCountBadge');
    const toggleCheckInCompanionFilterBtn = document.getElementById('toggleCheckInCompanionFilterBtn');
    const checkInCompanionFilterToolbar = document.getElementById('checkInCompanionFilterToolbar');
    const checkInCompanionSearchInput = document.getElementById('checkInCompanionSearchInput');
    const checkInCompanionFilterGender = document.getElementById('checkInCompanionFilterGender');
    const checkInCompanionFilterAgeGroup = document.getElementById('checkInCompanionFilterAgeGroup');
    const checkInCompanionFilterResetBtn = document.getElementById('checkInCompanionFilterResetBtn');

    toggleCheckInCompanionFilterBtn?.addEventListener('click', () => {
        if (!checkInCompanionFilterToolbar) return;
        const isHidden = checkInCompanionFilterToolbar.classList.contains('hidden');
        if (isHidden) {
            checkInCompanionFilterToolbar.classList.remove('hidden');
            checkInCompanionFilterToolbar.classList.add('flex');
            toggleCheckInCompanionFilterBtn.classList.add('bg-hp-green/15', 'border-hp-green/40', 'text-hp-green');
            checkInCompanionSearchInput?.focus();
        } else {
            checkInCompanionFilterToolbar.classList.add('hidden');
            checkInCompanionFilterToolbar.classList.remove('flex');
            toggleCheckInCompanionFilterBtn.classList.remove('bg-hp-green/15', 'border-hp-green/40', 'text-hp-green');
        }
    });

    checkInCompanionSearchInput?.addEventListener('input', () => renderCheckInCompanions());
    checkInCompanionFilterGender?.addEventListener('change', () => renderCheckInCompanions());
    checkInCompanionFilterAgeGroup?.addEventListener('change', () => renderCheckInCompanions());
    checkInCompanionFilterResetBtn?.addEventListener('click', () => {
        if (checkInCompanionSearchInput) checkInCompanionSearchInput.value = '';
        if (checkInCompanionFilterGender) checkInCompanionFilterGender.value = '';
        if (checkInCompanionFilterAgeGroup) checkInCompanionFilterAgeGroup.value = '';
        renderCheckInCompanions();
    });

    // Merges individually-added companions with bulk groups expanded into
    // individual companion records (used both for the summary UI hidden fields
    // and for the check-in payload).
    const getAllCheckInCompanions = () => {
        const opt = checkInPoolOption?.value || 'no_pool';
        const allCompanions = [];
        const defaultAmId = String(currentReservationData?.reservation_amenities?.[0]?.amenity?.id || currentReservationData?.reservation_amenities?.[0]?.amenity_id || '');

        checkInCompanions.forEach((c) => {
            const hasPool = (opt === 'all_paid' || opt === 'all_free') ? true : (opt === 'specific' ? !!c.has_pool_access : false);
            allCompanions.push({
                ...c,
                has_pool_access: hasPool,
                amenity_id: c.amenity_id || defaultAmId,
            });
        });

        bulkCompanionGroups.forEach((group) => {
            const poolLimit = (opt === 'all_paid' || opt === 'all_free') ? group.quantity : (opt === 'specific' ? (group.pool_quantity || 0) : 0);
            for (let i = 0; i < group.quantity; i++) {
                allCompanions.push({
                    first_name: 'Companion',
                    middle_name: '',
                    last_name: `C${allCompanions.length + 1}`,
                    age: String(getAgeFromGroup(group.age_group)),
                    age_group: group.age_group,
                    gender: group.gender,
                    is_foreigner: !!group.is_foreigner,
                    phone: '',
                    email: '',
                    has_pool_access: i < poolLimit,
                    amenity_id: group.amenity_id || defaultAmId,
                });
            }
        });
        return allCompanions;
    };

    const renderCheckInCompanions = () => {
        if (!checkInCompanionList) return;
        checkInCompanionList.innerHTML = '';
        if (checkInCompanionHiddenFields) checkInCompanionHiddenFields.innerHTML = '';

        const totalCount = getTotalCheckInCompanionCount();
        const currentPoolOpt = checkInPoolOption?.value || 'no_pool';
        const resAmenities = currentReservationData?.reservation_amenities || [];

        if (checkInCompanionCountBadge) {
            checkInCompanionCountBadge.textContent = `${totalCount} companion${totalCount === 1 ? '' : 's'} added`;
        }

        const searchTerm = (checkInCompanionSearchInput?.value || '').trim().toLowerCase();
        const filterGender = (checkInCompanionFilterGender?.value || '').trim();
        const filterAgeGroup = (checkInCompanionFilterAgeGroup?.value || '').trim();

        if (checkInCompanionFilterResetBtn) {
            const isFiltered = Boolean(searchTerm || filterGender || filterAgeGroup);
            checkInCompanionFilterResetBtn.classList.toggle('hidden', !isFiltered);
            checkInCompanionFilterResetBtn.classList.toggle('inline-flex', isFiltered);
        }

        let visibleCount = 0;

        // Render individual companions (single additions)
        checkInCompanions.forEach((companion, index) => {
            const fullName = `${companion.first_name || ''} ${companion.middle_name || ''} ${companion.last_name || ''}`.toLowerCase();
            const matchesSearch = !searchTerm || fullName.includes(searchTerm);
            const matchesGender = !filterGender || (companion.gender || '').toLowerCase() === filterGender.toLowerCase();

            let matchesAgeGroup = true;
            if (filterAgeGroup) {
                const parsedAge = parseInt(companion.age, 10);
                if (filterAgeGroup === '0-12') {
                    matchesAgeGroup = !isNaN(parsedAge) && parsedAge <= 12;
                } else if (filterAgeGroup === '13-17') {
                    matchesAgeGroup = !isNaN(parsedAge) && parsedAge >= 13 && parsedAge <= 17;
                } else if (filterAgeGroup === '18-59') {
                    matchesAgeGroup = !isNaN(parsedAge) && parsedAge >= 18 && parsedAge <= 59;
                } else if (filterAgeGroup === '60+') {
                    matchesAgeGroup = !isNaN(parsedAge) && parsedAge >= 60;
                }
            }

            if (!matchesSearch || !matchesGender || !matchesAgeGroup) {
                return;
            }

            visibleCount++;
            const nationality = companion.is_foreigner ? 'Foreigner' : 'Filipino';
            const parsedAge = parseInt(companion.age, 10);
            const rateLabel = (!isNaN(parsedAge) && parsedAge <= 12) ? 'Child' : 'Adult';

            let poolBadgeHtml = '';
            if (currentPoolOpt === 'all_paid') {
                poolBadgeHtml = '<span class="inline-flex items-center gap-1 rounded bg-sky-500/15 text-sky-700 dark:text-sky-300 px-2 py-0.5 text-[0.7rem] font-bold"><i class="bi bi-water"></i> Pool Pass</span>';
            } else if (currentPoolOpt === 'all_free') {
                poolBadgeHtml = '<span class="inline-flex items-center gap-1 rounded bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[0.7rem] font-bold"><i class="bi bi-water"></i> Free Pool</span>';
            } else if (currentPoolOpt === 'specific') {
                poolBadgeHtml = companion.has_pool_access
                    ? `<button type="button" class="inline-flex items-center gap-1 rounded bg-sky-500/20 text-sky-800 dark:text-sky-300 border border-sky-500/30 px-2 py-0.5 text-[0.7rem] font-bold cursor-pointer hover:bg-sky-500/30 transition-colors" data-toggle-companion-pool="${index}" title="Click to remove pool access"><i class="bi bi-water"></i> Pool Pass ✓</button>`
                    : `<button type="button" class="inline-flex items-center gap-1 rounded bg-gray-500/15 text-hp-text-muted border border-glass-border px-2 py-0.5 text-[0.7rem] font-medium cursor-pointer hover:bg-glass-hover transition-colors" data-toggle-companion-pool="${index}" title="Click to grant pool access">+ Pool Access</button>`;
            }

            let amenityBadgeHtml = '';
            if (resAmenities.length > 1) {
                const foundAm = resAmenities.find(ra => String(ra.amenity?.id || ra.amenity_id) === String(companion.amenity_id));
                const amName = foundAm?.amenity?.amenities_name || 'Amenity';
                amenityBadgeHtml = `<span class="inline-flex items-center gap-1 rounded bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 px-2 py-0.5 text-[0.7rem] font-bold"><i class="bi bi-house-door-fill"></i> ${escapeHtml(amName)}</span>`;
            }

            const item = document.createElement('div');
            item.className = 'guest-companion-pill flex items-center justify-between gap-2 p-2.5 rounded-xl border border-glass-border bg-glass mb-2 shadow-xs';
            item.innerHTML = `
                <div class="flex items-center gap-2 flex-wrap min-w-0 flex-1">
                    <span class="guest-companion-pill__name text-sm font-medium text-hp-text">${escapeHtml(companion.first_name)} ${escapeHtml(companion.last_name)} - ${escapeHtml(nationality)} - ${companion.age ? companion.age + ' yrs (' + rateLabel + ')' : rateLabel} - ${escapeHtml(companion.gender)}</span>
                    ${amenityBadgeHtml}
                    ${poolBadgeHtml}
                </div>
                <button type="button" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-red-500/20 bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all duration-200 cursor-pointer shadow-xs active:scale-95" data-companion-index="${index}" title="Remove companion">
                    <i class="bi bi-trash3 text-xs"></i>
                </button>
            `;
            checkInCompanionList.appendChild(item);
        });

        // Render bulk companion groups
        bulkCompanionGroups.forEach((group, index) => {
            const matchesSearch = !searchTerm;
            const matchesGender = !filterGender || (group.gender || '').toLowerCase() === filterGender.toLowerCase();
            const matchesAgeGroup = !filterAgeGroup || group.age_group === filterAgeGroup;

            if (!matchesSearch || !matchesGender || !matchesAgeGroup) {
                return;
            }

            visibleCount++;
            const nationality = group.is_foreigner ? 'Foreigner' : 'Filipino';
            const rateLabel = (group.age_group === '0-12') ? 'Child' : 'Adult';

            let bulkPoolBadgeHtml = '';
            if (currentPoolOpt === 'all_paid') {
                bulkPoolBadgeHtml = `<span class="inline-flex items-center gap-1 rounded bg-sky-500/15 text-sky-700 dark:text-sky-300 px-2 py-0.5 text-[0.7rem] font-bold"><i class="bi bi-water"></i> All ${group.quantity} with Pool</span>`;
            } else if (currentPoolOpt === 'all_free') {
                bulkPoolBadgeHtml = `<span class="inline-flex items-center gap-1 rounded bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 px-2 py-0.5 text-[0.7rem] font-bold"><i class="bi bi-water"></i> All ${group.quantity} Free Pool</span>`;
            } else if (currentPoolOpt === 'specific') {
                const pQty = group.pool_quantity || 0;
                bulkPoolBadgeHtml = `
                    <div class="inline-flex items-center gap-1 rounded-lg bg-sky-500/15 border border-sky-500/30 px-2 py-0.5 text-[0.72rem] font-bold text-sky-800 dark:text-sky-300">
                        <span class="inline-flex items-center gap-1"><i class="bi bi-water text-sky-600 dark:text-sky-400"></i> Pool:</span>
                        <button type="button" class="flex h-5 w-5 items-center justify-center rounded bg-sky-600/20 text-sky-900 dark:text-white hover:bg-sky-600/40 text-xs font-extrabold transition-colors cursor-pointer" data-bulk-pool-dec="${index}" title="Decrease pool access quantity">−</button>
                        <span class="px-1 min-w-[2.2rem] text-center font-bold text-xs">${pQty} / ${group.quantity}</span>
                        <button type="button" class="flex h-5 w-5 items-center justify-center rounded bg-sky-600/20 text-sky-900 dark:text-white hover:bg-sky-600/40 text-xs font-extrabold transition-colors cursor-pointer" data-bulk-pool-inc="${index}" title="Increase pool access quantity">+</button>
                    </div>
                `;
            }

            let bulkAmenityBadgeHtml = '';
            if (resAmenities.length > 1) {
                const foundAm = resAmenities.find(ra => String(ra.amenity?.id || ra.amenity_id) === String(group.amenity_id));
                const amName = foundAm?.amenity?.amenities_name || 'Amenity';
                bulkAmenityBadgeHtml = `<span class="inline-flex items-center gap-1 rounded bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30 px-2 py-0.5 text-[0.7rem] font-bold"><i class="bi bi-house-door-fill"></i> ${escapeHtml(amName)}</span>`;
            }

            const item = document.createElement('div');
            item.className = 'guest-companion-pill guest-companion-pill--bulk flex items-center justify-between gap-2 p-2.5 rounded-xl border border-glass-border bg-glass mb-2 shadow-xs';
            item.innerHTML = `
                <div class="flex items-center gap-2 flex-wrap min-w-0 flex-1">
                    <span class="guest-companion-pill__name text-sm font-medium text-hp-text">Bulk: ${group.quantity} × ${escapeHtml(group.gender)} - ${escapeHtml(nationality)} - Age Group: ${escapeHtml(group.age_group)} (${rateLabel})</span>
                    ${bulkAmenityBadgeHtml}
                    ${bulkPoolBadgeHtml}
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <div class="flex items-center rounded-lg border border-glass-border bg-glass p-0.5">
                        <button type="button" class="flex h-6 w-6 items-center justify-center rounded bg-black/5 dark:bg-white/10 text-xs font-extrabold text-hp-text hover:bg-black/10 transition-colors cursor-pointer" data-bulk-qty-dec="${index}" title="Decrease quantity">−</button>
                        <input type="number" min="1" max="500" value="${group.quantity}" data-bulk-qty-input="${index}" class="no-spinners w-9 border-0 bg-transparent text-center font-display text-xs font-bold text-hp-green-dark dark:text-hp-green focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" title="Type to change bulk quantity directly">
                        <button type="button" class="flex h-6 w-6 items-center justify-center rounded bg-black/5 dark:bg-white/10 text-xs font-extrabold text-hp-text hover:bg-black/10 transition-colors cursor-pointer" data-bulk-qty-inc="${index}" title="Increase quantity">+</button>
                    </div>
                    <button type="button" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-red-500/20 bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all duration-200 cursor-pointer shadow-xs active:scale-95" data-bulk-index="${index}" title="Remove bulk group">
                        <i class="bi bi-trash3 text-xs"></i>
                    </button>
                </div>
            `;
            checkInCompanionList.appendChild(item);
        });

        if (visibleCount === 0) {
            checkInCompanionList.innerHTML = totalCount === 0
                ? '<p class="guest-empty text-xs text-hp-text-muted italic py-2">No companions added yet.</p>'
                : '<p class="guest-empty text-xs text-hp-text-muted italic py-2">No companions match the active filters.</p>';
        }

        // Generate individual companions from bulk groups for form submission
        const allCompanions = getAllCheckInCompanions();

        if (checkInCompanionHiddenFields) {
            allCompanions.forEach((companion, index) => {
                Object.entries(companion).forEach(([key, value]) => {
                    const field = document.createElement('input');
                    field.type = 'hidden';
                    field.name = `check_in_companions[${index}][${key}]`;
                    field.value = (typeof value === 'boolean') ? (value ? '1' : '0') : (value ?? '');
                    checkInCompanionHiddenFields.appendChild(field);
                });
            });
        }

        updateCheckInFeeSummary();
    };

    const openCheckInCompanionModal = () => {
        checkInCompanionForm?.reset();
        checkInBulkCompanionForm?.reset();
        syncCheckInPoolOptionUI();
        syncCheckInBulkPoolMax();
        renderCheckInModalCompanionPreview();
        if (checkInCompanionModal) {
            checkInCompanionModal.classList.add('is-open');
            checkInCompanionModal.classList.remove('hidden');
            checkInCompanionModal.setAttribute('aria-hidden', 'false');
        }
    };

    const closeCheckInCompanionModal = () => {
        if (checkInCompanionModal) {
            checkInCompanionModal.classList.remove('is-open');
            checkInCompanionModal.classList.add('hidden');
            checkInCompanionModal.setAttribute('aria-hidden', 'true');
        }
    };

    const openCompanionSummaryModal = () => {
        if (bulkCompanionGroups.length === 0) {
            // If no bulk companions, proceed directly to check-in confirmation
            openCheckInConfirmationModal();
            return;
        }

        const opt = checkInPoolOption?.value || 'no_pool';
        let html = '<div style="margin-bottom: 1rem;"><h4>Companion Groups:</h4>';
        let totalCompanions = 0;

        bulkCompanionGroups.forEach(group => {
            const nationality = group.is_foreigner ? 'Foreigner' : 'Filipino';
            let poolGroupNote = '';
            if (opt === 'all_paid' || opt === 'all_free') {
                poolGroupNote = ` | <strong>Pool:</strong> All ${group.quantity}`;
            } else if (opt === 'specific') {
                poolGroupNote = ` | <strong>Pool:</strong> ${group.pool_quantity || 0} of ${group.quantity}`;
            }
            html += `
                <div style="padding: 0.5rem; background-color: #e5e5e5; border: 1px solid #d4d4d4; border-radius: 0.25rem; margin-bottom: 0.5rem; color: #000;">
                    <p><strong>Gender:</strong> ${group.gender} | <strong>Nationality:</strong> ${nationality} | <strong>Age Group:</strong> ${group.age_group} | <strong>Quantity:</strong> ${group.quantity}${poolGroupNote}</p>
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

        const { adultCount, childCount, adultRate, childRate, entranceOpt, poolOpt, poolCount, poolRate, poolTotal, entranceTotal, extraHeadTotal, extraHeadBreakdown, total } = computeCheckInEntrance();
        const balance = Number(currentReservationData?.remaining_balance || 0);
        const grandTotal = total + balance;

        let poolText = 'Not included (₱0.00)';
        if (poolOpt === 'all_free') {
            poolText = `Free Promo (${poolCount} guests • ₱0.00)`;
        } else if (poolOpt === 'all_paid' || poolOpt === 'specific') {
            poolText = `${poolCount} guest${poolCount === 1 ? '' : 's'} × ₱${poolRate.toFixed(2)} = ₱${poolTotal.toFixed(2)}`;
        }

        let extraHeadHtml = '';
        if (extraHeadTotal > 0) {
            const breakdownLines = extraHeadBreakdown.map(b => `${escapeHtml(b.amenity_name)}: ${b.excess} extra × ₱${b.add_rate.toFixed(2)} = ₱${b.fee.toFixed(2)}`).join('<br>');
            extraHeadHtml = `
                <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.6rem 0.9rem; border-top: 1px solid #eef2f7; color: #b45309; background: #fffbeb;">
                    <span style="font-size: 0.85rem; font-weight: 600;">Extra Head Fee (${extraHeadBreakdown.reduce((s, b) => s + b.excess, 0)} extra)</span>
                    <strong style="font-size: 0.85rem;">₱${extraHeadTotal.toFixed(2)}</strong>
                </div>
                <div style="padding: 0.4rem 0.9rem; font-size: 0.75rem; color: #92400e; background: #fffbeb; border-top: 1px dashed #fde68a;">
                    ${breakdownLines}
                </div>
            `;
        }

        let html = `<p>Are you sure you want to check in <strong>Reservation #${escapeHtml(currentReservationData?.id || pendingReservationId)}</strong> now?</p>`;

        html += `
            <div style="margin-top: 1rem; border: 1px solid #f3f4f6; border-radius: 0.6rem; overflow: hidden;">
                <div style="padding: 0.65rem 0.9rem; background: #f8fafc; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">Payment Summary</div>
                <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.6rem 0.9rem; color: #334155;">
                    <span style="font-size: 0.85rem;">Entrance fee</span>
                    <strong style="font-size: 0.85rem;">${entranceOpt === 'all_free' ? 'Free Promo (₱0.00)' : '₱' + entranceTotal.toFixed(2)}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.6rem 0.9rem; border-top: 1px solid #eef2f7; color: #9ca3af; font-size: 0.8rem;">
                    <span>${adultCount} adult${adultCount === 1 ? '' : 's'}${entranceOpt === 'all_free' ? ' (Free Promo)' : ' × ₱' + adultRate.toFixed(2)} + ${childCount} child${childCount === 1 ? '' : 'ren'}${entranceOpt === 'all_free' ? ' (Free Promo)' : ' × ₱' + childRate.toFixed(2)}</span>
                    <span>₱${entranceTotal.toFixed(2)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.6rem 0.9rem; border-top: 1px solid #eef2f7; color: #334155;">
                    <span style="font-size: 0.85rem;">Pool access</span>
                    <strong style="font-size: 0.85rem;">${poolText}</strong>
                </div>
                ${extraHeadHtml}
                <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.6rem 0.9rem; border-top: 1px solid #eef2f7; color: #334155;">
                    <span style="font-size: 0.85rem;">Remaining reservation balance</span>
                    <strong style="font-size: 0.85rem;">₱${balance.toFixed(2)}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.75rem 0.9rem; background: #f0fdf4; border-top: 1px solid #f3f4f6;">
                    <strong style="font-size: 0.95rem;">Total to pay</strong>
                    <strong style="font-size: 0.95rem; color: #16a34a;">₱${grandTotal.toFixed(2)}</strong>
                </div>
            </div>
            <p style="margin-top: 0.75rem; font-size: 0.8rem; color: #64748b;">This reservation will be marked as <strong>Checked In</strong> and paid.</p>
        `;

        if (!isToday && reservationDate) {
            html += `
                <div style="margin-top: 1rem; padding: 0.75rem; background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 0.25rem; color: #856404;">
                    <strong><i class="bi bi-exclamation-triangle-fill me-1"></i>Warning:</strong> This reservation is not scheduled for today.<br>
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
        let poolCount = 0;

        const poolOpt = checkInPoolOption?.value || 'no_pool';
        const guestMode = checkInForm?.querySelector('input[name="check_in_guest_mode"]:checked')?.value;
        const hasPrimary = guestMode === 'with_primary';

        if (hasPrimary) {
            const primaryAge = parseInt(checkInForm?.querySelector('input[name="check_in_primary_guest[age]"]')?.value, 10);
            if (!isNaN(primaryAge) && primaryAge <= 12) childCount += 1;
            else adultCount += 1;

            if (poolOpt === 'all_paid' || poolOpt === 'all_free') {
                poolCount += 1;
            } else if (poolOpt === 'specific') {
                if (checkInPrimaryGuestHasPool?.checked) {
                    poolCount += 1;
                }
            }
        }

        const companions = getAllCheckInCompanions();
        companions.forEach((companion) => {
            const companionAge = parseInt(companion.age, 10);
            if (!isNaN(companionAge) && companionAge <= 12) childCount += 1;
            else adultCount += 1;

            if (companion.has_pool_access) {
                poolCount += 1;
            }
        });

        const entranceOpt = checkInEntranceOption?.value || 'all_paid';
        const entranceTotal = entranceOpt === 'all_free' ? 0 : (adultCount * adultRate + childCount * childRate);
        let poolTotal = 0;
        if (poolOpt === 'all_paid' || poolOpt === 'specific') {
            poolTotal = poolCount * poolRate;
        } else {
            poolTotal = 0;
        }

        // Calculate Extra Head Fee for booked amenities exceeding maximum capacity
        let extraHeadTotal = 0;
        const extraHeadBreakdown = [];
        const resAmenities = currentReservationData?.reservation_amenities || [];
        if (resAmenities.length > 0) {
            const defaultAmenityId = String(resAmenities[0].amenity?.id || resAmenities[0].amenity_id || '');
            const amenityCounts = {};

            if (hasPrimary) {
                const pAmenityId = String(checkInForm?.querySelector('[name="check_in_primary_guest[amenity_id]"]')?.value || defaultAmenityId);
                amenityCounts[pAmenityId] = (amenityCounts[pAmenityId] || 0) + 1;
            }

            companions.forEach(c => {
                const cAmenityId = String(c.amenity_id || defaultAmenityId);
                amenityCounts[cAmenityId] = (amenityCounts[cAmenityId] || 0) + 1;
            });

            resAmenities.forEach(ra => {
                const am = ra.amenity || {};
                const amId = String(am.id || ra.amenity_id || '');
                const maxCap = (am.maximum_capacity !== null && am.maximum_capacity !== undefined && am.maximum_capacity !== '') ? parseInt(am.maximum_capacity, 10) : null;
                const addRate = parseFloat(am.additional_per_head) || 0;
                const count = amenityCounts[amId] || 0;

                if (maxCap !== null && !isNaN(maxCap) && count > maxCap) {
                    const excess = count - maxCap;
                    const fee = excess * addRate;
                    extraHeadTotal += fee;
                    extraHeadBreakdown.push({
                        amenity_name: am.amenities_name || 'Amenity',
                        assigned: count,
                        max_cap: maxCap,
                        excess,
                        add_rate: addRate,
                        fee
                    });
                }
            });
        }

        return {
            period,
            adultCount,
            childCount,
            adultRate,
            childRate,
            entranceOpt,
            poolOpt,
            poolCount,
            poolRate,
            poolTotal,
            entranceTotal,
            extraHeadTotal,
            extraHeadBreakdown,
            total: entranceTotal + poolTotal + extraHeadTotal,
        };
    };

    const updateCheckInFeeSummary = () => {
        const { period, adultCount, childCount, adultRate, childRate, entranceOpt, poolOpt, poolCount, poolRate, poolTotal, entranceTotal, extraHeadTotal, extraHeadBreakdown, total } = computeCheckInEntrance();
        const balance = Number(currentReservationData?.remaining_balance || 0);

        const badge = document.getElementById('checkInEffectivePeriodBadge');
        if (badge) badge.textContent = getCheckInPeriodLabel(period);

        const adultEl = document.getElementById('checkInAdultSummary');
        if (adultEl) {
            if (entranceOpt === 'all_free') {
                adultEl.textContent = `${adultCount} × ₱0.00 (Promo)`;
            } else {
                adultEl.textContent = `${adultCount} × ₱${adultRate.toFixed(2)}`;
            }
        }
        const childEl = document.getElementById('checkInChildSummary');
        if (childEl) {
            if (entranceOpt === 'all_free') {
                childEl.textContent = `${childCount} × ₱0.00 (Promo)`;
            } else {
                childEl.textContent = `${childCount} × ₱${childRate.toFixed(2)}`;
            }
        }
        const poolEl = document.getElementById('checkInPoolSummary');
        if (poolEl) {
            if (poolOpt === 'no_pool') {
                poolEl.textContent = '₱0.00';
            } else if (poolOpt === 'all_free') {
                poolEl.textContent = '₱0.00 (Promo)';
            } else {
                poolEl.textContent = `₱${poolTotal.toFixed(2)} (${poolCount} pool pass${poolCount === 1 ? '' : 'es'})`;
            }
        }
        const extraHeadEl = document.getElementById('checkInExtraHeadSummary');
        if (extraHeadEl) {
            if (extraHeadTotal > 0) {
                const totalExcess = extraHeadBreakdown.reduce((sum, b) => sum + b.excess, 0);
                extraHeadEl.textContent = `₱${extraHeadTotal.toFixed(2)} (${totalExcess} extra)`;
            } else {
                extraHeadEl.textContent = '₱0.00';
            }
        }
        const entranceEl = document.getElementById('checkInEntranceTotal');
        if (entranceEl) entranceEl.textContent = `₱${(entranceTotal + poolTotal).toFixed(2)}`;
        const extraHeadTotalEl = document.getElementById('checkInExtraHeadTotal');
        if (extraHeadTotalEl) {
            extraHeadTotalEl.textContent = `₱${extraHeadTotal.toFixed(2)}`;
        }
        const balanceEl = document.getElementById('checkInReservationBalance');
        if (balanceEl) balanceEl.textContent = `₱${balance.toFixed(2)}`;
        const grandEl = document.getElementById('checkInGrandTotal');
        if (grandEl) grandEl.textContent = `₱${(total + balance).toFixed(2)}`;
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

    checkInForm?.addEventListener('input', (e) => {
        if (e.target.name === 'check_in_primary_guest[age]') {
            updateCheckInFeeSummary();
        }
    });

    checkInPoolOption?.addEventListener('change', () => {
        syncCheckInPoolOptionUI();
        renderCheckInCompanions();
        updateCheckInFeeSummary();
    });

    checkInPrimaryGuestHasPool?.addEventListener('change', () => {
        updateCheckInFeeSummary();
    });

    // Duplicate companion warning modal controls
    const duplicateCompanionModal = document.getElementById('duplicateCompanionModal');
    const duplicateCompanionMessage = document.getElementById('duplicateCompanionMessage');

    const showDuplicateCompanionModal = (message) => {
        if (duplicateCompanionMessage && message) {
            duplicateCompanionMessage.textContent = message;
        }
        if (duplicateCompanionModal) {
            duplicateCompanionModal.classList.add('is-open');
            duplicateCompanionModal.classList.remove('hidden');
            duplicateCompanionModal.setAttribute('aria-hidden', 'false');
        }
    };

    const closeDuplicateCompanionModal = () => {
        if (duplicateCompanionModal) {
            duplicateCompanionModal.classList.remove('is-open');
            duplicateCompanionModal.classList.add('hidden');
            duplicateCompanionModal.setAttribute('aria-hidden', 'true');
        }
    };

    document.querySelectorAll('[data-close-duplicate-modal="true"]').forEach(btn => {
        btn.addEventListener('click', closeDuplicateCompanionModal);
    });

    // Remove Companion Confirmation Modal Controls
    const removeCompanionConfirmModal = document.getElementById('removeCompanionConfirmModal');
    const removeCompanionModalMessage = document.getElementById('removeCompanionModalMessage');
    const confirmRemoveCompanionBtn = document.getElementById('confirmRemoveCompanionBtn');
    let pendingRemoveAction = null;

    const openRemoveCompanionModal = (messageHtml, onConfirm) => {
        if (!removeCompanionConfirmModal) {
            if (typeof onConfirm === 'function') onConfirm();
            return;
        }
        if (removeCompanionModalMessage && messageHtml) {
            removeCompanionModalMessage.innerHTML = messageHtml;
        }
        pendingRemoveAction = onConfirm;
        removeCompanionConfirmModal.classList.add('is-open');
        removeCompanionConfirmModal.classList.remove('hidden');
        removeCompanionConfirmModal.setAttribute('aria-hidden', 'false');
    };

    const closeRemoveCompanionModal = () => {
        if (!removeCompanionConfirmModal) return;
        removeCompanionConfirmModal.classList.remove('is-open');
        removeCompanionConfirmModal.classList.add('hidden');
        removeCompanionConfirmModal.setAttribute('aria-hidden', 'true');
        pendingRemoveAction = null;
    };

    document.querySelectorAll('[data-close-remove-companion-modal="true"]').forEach(btn => {
        btn.addEventListener('click', closeRemoveCompanionModal);
    });

    confirmRemoveCompanionBtn?.addEventListener('click', () => {
        if (typeof pendingRemoveAction === 'function') {
            pendingRemoveAction();
        }
        closeRemoveCompanionModal();
    });

    // Tab switching for companion modal (Single vs Bulk)
    const checkInCompanionTabs = document.querySelectorAll('[data-checkin-companion-tab]');
    const checkInCompanionTabContents = document.querySelectorAll('[data-checkin-companion-content]');

    checkInCompanionTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetTab = tab.dataset.checkinCompanionTab;
            checkInCompanionTabs.forEach(t => {
                t.classList.remove('guest-form__tab--active', 'bg-hp-green', 'text-white', 'shadow-xs');
                t.classList.add('bg-transparent', 'text-hp-text');
            });
            tab.classList.add('guest-form__tab--active', 'bg-hp-green', 'text-white', 'shadow-xs');
            tab.classList.remove('bg-transparent', 'text-hp-text');

            checkInCompanionTabContents.forEach(content => {
                if (content.dataset.checkinCompanionContent === targetTab) {
                    content.classList.remove('hidden');
                    content.classList.add('guest-form--tab-content--active');
                } else {
                    content.classList.add('hidden');
                    content.classList.remove('guest-form--tab-content--active');
                }
            });
        });
    });

    checkInAddCompanionBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        if (checkInAddCompanionBtn.disabled) return;
        checkInAddCompanionBtn.disabled = true;
        checkInAddCompanionBtn.classList.add('opacity-50', 'pointer-events-none');
        setTimeout(() => {
            checkInAddCompanionBtn.disabled = false;
            checkInAddCompanionBtn.classList.remove('opacity-50', 'pointer-events-none');
        }, 1000);
        openCheckInCompanionModal();
    });

    companionSummaryCloseButtons.forEach((button) => {
        button.addEventListener('click', closeCompanionSummaryModal);
    });

    checkInConfirmationCloseButtons.forEach((button) => {
        button.addEventListener('click', closeCheckInConfirmationModal);
    });

    proceedToCheckInBtn?.addEventListener('click', () => {
        closeCompanionSummaryModal();
        openCheckInConfirmationModal();
    });

    confirmCheckInBtn?.addEventListener('click', () => {
        closeCheckInConfirmationModal();
        submitCheckInForm();
    });

    // Single companion form submission with duplicate validation & 1s anti-double-click lock
    checkInCompanionForm?.addEventListener('submit', (e) => {
        e.preventDefault();

        const formData = new FormData(checkInCompanionForm);
        const firstName = (formData.get('first_name') || '').trim();
        const middleName = (formData.get('middle_name') || '').trim();
        const lastName = (formData.get('last_name') || '').trim();
        const ageVal = (formData.get('age') || '').trim();
        const parsedAge = parseInt(ageVal, 10);
        const genderVal = (formData.get('gender') || '').trim();
        const isForeigner = formData.get('is_foreigner') === '1';
        const currentPoolOpt = checkInPoolOption?.value || 'no_pool';
        const cAmenityId = String(formData.get('amenity_id') || currentReservationData?.reservation_amenities?.[0]?.amenity?.id || currentReservationData?.reservation_amenities?.[0]?.amenity_id || '');

        // Check duplicate against primary guest
        const primaryFirst = (checkInForm?.querySelector('input[name="check_in_primary_guest[first_name]"]')?.value || '').trim();
        const primaryLast = (checkInForm?.querySelector('input[name="check_in_primary_guest[last_name]"]')?.value || '').trim();
        const primaryAge = parseInt(checkInForm?.querySelector('input[name="check_in_primary_guest[age]"]')?.value, 10);
        const primaryGender = checkInForm?.querySelector('select[name="check_in_primary_guest[gender]"]')?.value;
        const primaryIsForeigner = checkInForm?.querySelector('select[name="check_in_primary_guest[is_foreigner]"]')?.value === '1';

        const isMatchPrimary = firstName.toLowerCase() === primaryFirst.toLowerCase() &&
            lastName.toLowerCase() === primaryLast.toLowerCase() &&
            parsedAge === primaryAge &&
            genderVal.toLowerCase() === (primaryGender || '').toLowerCase() &&
            isForeigner === primaryIsForeigner;

        if (isMatchPrimary) {
            showDuplicateCompanionModal(`A guest with the name "${firstName} ${lastName}" is already registered as the primary guest.`);
            return;
        }

        // Check duplicate against staged single companions
        const isDuplicateSingle = checkInCompanions.some(c => {
            const sameFirst = (c.first_name || '').trim().toLowerCase() === firstName.toLowerCase();
            const sameMiddle = (c.middle_name || '').trim().toLowerCase() === middleName.toLowerCase();
            const sameLast = (c.last_name || '').trim().toLowerCase() === lastName.toLowerCase();
            const sameAge = parseInt(c.age, 10) === parsedAge;
            const sameGender = (c.gender || '').toLowerCase() === genderVal.toLowerCase();
            const sameNat = Boolean(c.is_foreigner) === isForeigner;
            return sameFirst && sameMiddle && sameLast && sameAge && sameGender && sameNat;
        });

        if (isDuplicateSingle) {
            showDuplicateCompanionModal(`A companion with the name "${firstName} ${lastName}" and identical demographic info is already in the list.`);
            return;
        }

        // 1-second anti-double-click lock on submit button
        const submitBtn = checkInCompanionForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'pointer-events-none');
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'pointer-events-none');
            }, 1000);
        }

        const companionHasPool = (currentPoolOpt === 'all_paid' || currentPoolOpt === 'all_free')
            ? true
            : (currentPoolOpt === 'specific' ? (formData.get('has_pool_access') === '1' || checkInCompanionHasPool?.checked) : false);

        const companion = {
            first_name: firstName || 'Companion',
            middle_name: middleName,
            last_name: lastName || 'Guest',
            age: ageVal,
            gender: genderVal || 'Male',
            is_foreigner: isForeigner,
            phone: (formData.get('phone') || '').trim(),
            email: (formData.get('email') || '').trim(),
            has_pool_access: companionHasPool,
            amenity_id: cAmenityId,
        };

        checkInCompanions.push(companion);
        renderCheckInCompanions();
        renderCheckInModalCompanionPreview();

        checkInCompanionForm.reset();
        if (checkInCompanionIsForeigner) checkInCompanionIsForeigner.value = '0';
        syncCheckInPoolOptionUI();
    });

    // Bulk companion form submission with duplicate validation & 1s anti-double-click lock
    checkInBulkCompanionForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        e.stopPropagation();

        const formData = new FormData(checkInBulkCompanionForm);
        const gender = formData.get('gender') || 'Male';
        const isForeigner = formData.get('is_foreigner') === '1';
        const ageGroup = formData.get('age_group') || '18-59';
        const quantity = parseInt(formData.get('quantity'), 10) || 1;
        const currentPoolOpt = checkInPoolOption?.value || 'no_pool';
        const bAmenityId = String(formData.get('amenity_id') || currentReservationData?.reservation_amenities?.[0]?.amenity?.id || currentReservationData?.reservation_amenities?.[0]?.amenity_id || '');

        const rawPoolQty = parseInt(formData.get('pool_access_quantity'), 10) || 0;
        let poolQty = Math.min(Math.max(0, rawPoolQty), quantity);
        if (currentPoolOpt === 'all_paid' || currentPoolOpt === 'all_free') {
            poolQty = quantity;
        } else if (currentPoolOpt === 'no_pool') {
            poolQty = 0;
        }

        // Check for duplicate bulk group with exact same demographic profile & amenity
        const duplicateGroup = bulkCompanionGroups.find(
            group => group.gender === gender &&
                group.is_foreigner === isForeigner &&
                group.age_group === ageGroup &&
                String(group.amenity_id || '') === bAmenityId
        );

        if (duplicateGroup) {
            showDuplicateCompanionModal(`A bulk companion group for "${gender}, Age ${ageGroup} (${isForeigner ? 'Foreigner' : 'Filipino'})" already exists. Please adjust its quantity on the staged preview instead of creating a duplicate.`);
            return;
        }

        // 1-second anti-double-click lock on submit button
        const submitBtn = checkInBulkCompanionForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'pointer-events-none');
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'pointer-events-none');
            }, 1000);
        }

        bulkCompanionGroups.push({
            gender,
            is_foreigner: isForeigner,
            age_group: ageGroup,
            quantity,
            pool_quantity: poolQty,
            amenity_id: bAmenityId,
        });

        renderCheckInCompanions();
        renderCheckInModalCompanionPreview();

        checkInBulkCompanionForm.reset();
        if (checkInBulkCompanionQuantity) checkInBulkCompanionQuantity.value = '1';
        syncCheckInBulkPoolMax();
    });

    // Delete / Toggle companion handlers on the main check-in card
    checkInCompanionList?.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('[data-companion-index]') || e.target.closest('[data-bulk-index]') || e.target.closest('.guest-companion-pill__delete');
        if (removeBtn) {
            const index = removeBtn.dataset.companionIndex;
            const bulkIndex = removeBtn.dataset.bulkIndex;

            if (index !== undefined && index !== null && index !== '') {
                const idx = parseInt(index, 10);
                if (!isNaN(idx) && checkInCompanions[idx]) {
                    const comp = checkInCompanions[idx];
                    const name = comp ? `${comp.first_name} ${comp.last_name}` : 'this companion';
                    openRemoveCompanionModal(
                        `Are you sure you want to remove <strong>${escapeHtml(name)}</strong> from the companion list?`,
                        () => {
                            checkInCompanions.splice(idx, 1);
                            renderCheckInCompanions();
                            renderCheckInModalCompanionPreview();
                        }
                    );
                }
            } else if (bulkIndex !== undefined && bulkIndex !== null && bulkIndex !== '') {
                const bIdx = parseInt(bulkIndex, 10);
                if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                    const grp = bulkCompanionGroups[bIdx];
                    const desc = grp ? `${grp.quantity} guests (${grp.gender}, Age ${grp.age_group})` : 'this bulk group';
                    openRemoveCompanionModal(
                        `Are you sure you want to remove the bulk companion group with <strong>${escapeHtml(desc)}</strong>?`,
                        () => {
                            bulkCompanionGroups.splice(bIdx, 1);
                            renderCheckInCompanions();
                            renderCheckInModalCompanionPreview();
                        }
                    );
                }
            }
            return;
        }

        // Stepper: Increase Bulk Group Quantity
        const bulkQtyIncBtn = e.target.closest('[data-bulk-qty-inc]');
        if (bulkQtyIncBtn) {
            const bIdx = parseInt(bulkQtyIncBtn.dataset.bulkQtyInc, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const group = bulkCompanionGroups[bIdx];
                handleCheckInBulkQtyChange(bIdx, (parseInt(group.quantity, 10) || 1) + 1);
            }
            return;
        }

        // Stepper: Decrease Bulk Group Quantity
        const bulkQtyDecBtn = e.target.closest('[data-bulk-qty-dec]');
        if (bulkQtyDecBtn) {
            const bIdx = parseInt(bulkQtyDecBtn.dataset.bulkQtyDec, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const group = bulkCompanionGroups[bIdx];
                handleCheckInBulkQtyChange(bIdx, (parseInt(group.quantity, 10) || 1) - 1);
            }
            return;
        }

        // Toggle single companion pool pass
        const togglePoolBtn = e.target.closest('[data-toggle-companion-pool]');
        if (togglePoolBtn) {
            const cIdx = parseInt(togglePoolBtn.dataset.toggleCompanionPool, 10);
            if (!isNaN(cIdx) && checkInCompanions[cIdx]) {
                checkInCompanions[cIdx].has_pool_access = !checkInCompanions[cIdx].has_pool_access;
                renderCheckInCompanions();
                renderCheckInModalCompanionPreview();
            }
            return;
        }

        // Increment bulk group pool count
        const bulkIncBtn = e.target.closest('[data-bulk-pool-inc]');
        if (bulkIncBtn) {
            const bIdx = parseInt(bulkIncBtn.dataset.bulkPoolInc, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const group = bulkCompanionGroups[bIdx];
                const cur = group.pool_quantity || 0;
                if (cur < group.quantity) {
                    group.pool_quantity = cur + 1;
                    renderCheckInCompanions();
                    renderCheckInModalCompanionPreview();
                }
            }
            return;
        }

        // Decrement bulk group pool count
        const bulkDecBtn = e.target.closest('[data-bulk-pool-dec]');
        if (bulkDecBtn) {
            const bIdx = parseInt(bulkDecBtn.dataset.bulkPoolDec, 10);
            if (!isNaN(bIdx) && bulkCompanionGroups[bIdx]) {
                const group = bulkCompanionGroups[bIdx];
                const cur = group.pool_quantity || 0;
                if (cur > 0) {
                    group.pool_quantity = cur - 1;
                    renderCheckInCompanions();
                    renderCheckInModalCompanionPreview();
                }
            }
            return;
        }
    });

    checkInCompanionList?.addEventListener('change', (e) => {
        const input = e.target.closest('[data-bulk-qty-input]');
        if (input) {
            const bIdx = parseInt(input.dataset.bulkQtyInput, 10);
            handleCheckInBulkQtyChange(bIdx, input.value);
        }
    });

    checkInEntranceOption?.addEventListener('change', () => {
        syncCheckInEntranceOptionUI();
        updateCheckInFeeSummary();
    });

    checkInPoolOption?.addEventListener('change', () => {
        syncCheckInPoolOptionUI();
        renderCheckInCompanions();
        updateCheckInFeeSummary();
    });

    checkInPrimaryGuestHasPool?.addEventListener('change', () => {
        updateCheckInFeeSummary();
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

        // Setup Amenity select dropdowns for companion modals
        const companionAmenityWrap = document.getElementById('checkInCompanionAmenityWrap');
        const companionAmenitySelect = document.getElementById('checkInCompanionAmenity');
        const bulkAmenityWrap = document.getElementById('checkInBulkCompanionAmenityWrap');
        const bulkAmenitySelect = document.getElementById('checkInBulkCompanionAmenity');

        const resAmenities = reservation?.reservation_amenities || [];
        if (resAmenities.length > 1) {
            let optionsHtml = '';
            resAmenities.forEach(ra => {
                const am = ra.amenity || {};
                const amId = String(am.id || ra.amenity_id || '');
                const name = am.amenities_name || 'Amenity';
                const max = (am.maximum_capacity !== null && am.maximum_capacity !== undefined && am.maximum_capacity !== '') ? `Max: ${am.maximum_capacity}` : 'No limit';
                const addFee = parseFloat(am.additional_per_head) > 0 ? ` (+₱${parseFloat(am.additional_per_head).toFixed(2)}/extra head)` : '';
                optionsHtml += `<option value="${amId}">${escapeHtml(name)} (${max}${addFee})</option>`;
            });
            if (companionAmenitySelect) companionAmenitySelect.innerHTML = optionsHtml;
            if (bulkAmenitySelect) bulkAmenitySelect.innerHTML = optionsHtml;
            if (companionAmenityWrap) companionAmenityWrap.style.display = 'grid';
            if (bulkAmenityWrap) bulkAmenityWrap.style.display = 'flex';
        } else {
            if (companionAmenityWrap) companionAmenityWrap.style.display = 'none';
            if (bulkAmenityWrap) bulkAmenityWrap.style.display = 'none';
            if (resAmenities.length === 1) {
                const singleAmId = String(resAmenities[0].amenity?.id || resAmenities[0].amenity_id || '');
                const singleAmName = resAmenities[0].amenity?.amenities_name || 'Amenity';
                if (companionAmenitySelect) companionAmenitySelect.innerHTML = `<option value="${singleAmId}" selected>${escapeHtml(singleAmName)}</option>`;
                if (bulkAmenitySelect) bulkAmenitySelect.innerHTML = `<option value="${singleAmId}" selected>${escapeHtml(singleAmName)}</option>`;
            }
        }

        checkInForm.reset();

        // Check if any booked amenity includes free entrance or free pool benefits
        const hasFreeEntrance = resAmenities.some(ra => {
            const am = ra.amenity || {};
            const fullAm = (window.ALL_AMENITIES || []).find(a => String(a.id) === String(am.id || ra.amenity_id));
            return Boolean(am.free_entrance || am.benefits?.free_entrance || fullAm?.benefits?.free_entrance || fullAm?.free_entrance);
        });
        const hasFreePool = resAmenities.some(ra => {
            const am = ra.amenity || {};
            const fullAm = (window.ALL_AMENITIES || []).find(a => String(a.id) === String(am.id || ra.amenity_id));
            return Boolean(am.free_pool || am.benefits?.free_pool || fullAm?.benefits?.free_pool || fullAm?.free_pool);
        });

        if (checkInEntranceOption) {
            checkInEntranceOption.value = hasFreeEntrance ? 'all_free' : 'all_paid';
        }
        syncCheckInEntranceOptionUI();

        if (checkInPoolOption) {
            checkInPoolOption.value = hasFreePool ? 'all_free' : 'no_pool';
        }
        if (checkInPrimaryGuestHasPool) {
            checkInPrimaryGuestHasPool.checked = false;
        }
        syncCheckInPoolOptionUI();

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
        try {
            html5QrCode.clear();
        } catch (clearError) { /* element already cleared */ }
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
                // Adaptive QR box: a fixed 250x250 box throws "QR box size
                // provided is larger than the viewfinder size" whenever the
                // scanner container renders narrower than 250px (small
                // windows/narrow layouts), which kills the scanner entirely.
                qrbox: (viewfinderWidth, viewfinderHeight) => {
                    const minDimension = Math.min(viewfinderWidth, viewfinderHeight);
                    const side = Math.min(250, Math.max(100, Math.floor(minDimension * 0.75)));
                    return { width: side, height: side };
                },
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
                                        if (window.reservationsData && window.reservationsData[reservationId]) {
                                            window.reservationsData[reservationId].status = 'Completed';
                                        }
                                        const resRow = document.querySelector(`tr[data-reservation-id="${reservationId}"]`);
                                        if (resRow) {
                                            const statusPill = resRow.querySelector('.status-pill, .badge-status');
                                            if (statusPill) {
                                                statusPill.textContent = 'Completed';
                                                statusPill.className = 'status-pill status-pill--completed';
                                            }
                                        }
                                        closeModal();
                                        window.dispatchEvent(new CustomEvent('app:data-mutated'));
                                        showToast(`Reservation #${reservationId} checked out successfully.`);
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
            () => {
                // Per-frame "no QR detected" callback - intentionally silent
                // so the status message is not overwritten while scanning.
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
            // getUserMedia (and therefore Html5Qrcode.getCameras) only exists
            // in secure contexts. Surface a clear message instead of a cryptic
            // failure when the app is served over plain HTTP.
            if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                throw new Error('Camera access requires HTTPS or localhost. Open the app via http://localhost and allow camera permission.');
            }

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

    // Both this script and its sibling page script can be loaded on the same
    // page (the check-ins page loads both), and both wire up the same
    // #scanQrBtn/#qrScanner elements. Two Html5Qrcode instances racing for
    // the same camera and container break scanning (duplicate video
    // elements, double lookups, stacked modals), so only the first
    // initializer may bind the QR controls. The claim is stored on the
    // element itself, so SPA navigations - which swap in a fresh DOM -
    // re-initialize correctly.
    if (scanQrBtn && scanQrBtn.dataset.qrScannerBound !== 'true') {
        scanQrBtn.dataset.qrScannerBound = 'true';

        qrCameraSelect?.addEventListener('change', async () => {
            const cameraId = qrCameraSelect.value;
            try {
                await startQrScanner(cameraId);
            } catch (error) {
                qrScannerStatus.textContent = `Camera error: ${error.message || 'Unable to start selected camera.'}`;
            }
        });

        scanQrBtn.addEventListener('click', () => {
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
    }

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
            resvModalTitle.textContent = 'Reservation Details';
        }
        const resvModalIdBadge = document.getElementById('reservationModalIdBadge');
        if (resvModalIdBadge) {
            resvModalIdBadge.textContent = `#${reservation.id}`;
        }

        // Ensure modal body is visible and edit form is hidden
        modalBody.hidden = false;
        const editForm = document.getElementById('reservationModalEditForm');
        if (editForm) {
            editForm.hidden = true;
        }

        // Format reservation date to readable format (e.g. Sep 9, 2026)
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
            const sSlot = startSlot || 'Daytime';
            const eSlot = endSlot || sSlot;
            if (eDate && eDate !== sDate) {
                const eFormatted = formatDate(eDate);
                const daysCount = totalDays || 'Multi-day';
                return `${sFormatted} (${sSlot}) – ${eFormatted} (${eSlot}) (${daysCount} Days Stay)`;
            }
            return `${sFormatted} (${sSlot})`;
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
            let formattedTime = session === 'Nighttime' ? '8:00 AM' : '5:00 PM';

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
        const rawAmenities = reservation.reservation_amenities || reservation.reservationAmenities || reservation.amenities || [];

        const printReservationReceipt = (res) => {
            if (!res) return;
            const printWindow = window.open('', '_blank', 'width=800,height=900');
            if (!printWindow) {
                window.print();
                return;
            }

            const totalAmount = Number(res.total_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const amountPaid = Number(res.amount_paid || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const balance = Number(res.remaining_balance || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const rAmenities = res.reservation_amenities || res.reservationAmenities || res.amenities || [];
            const amenitiesTableHtml = rAmenities.map((a) => {
                const name = a.amenity?.amenities_name || a.amenity_name || a.amenities_name || a.name || 'Amenity';
                const price = Number(a.price_at_booking || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const slot = a.start_slot || res.start_slot || 'Daytime';
                return `<tr><td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; font-weight: 500;">${escapeHtml(name)} <span style="font-size: 11px; color: #6b7280;">(${escapeHtml(slot)})</span></td><td style="text-align: right; padding: 10px 0; border-bottom: 1px solid #e5e7eb; font-weight: 600;">₱${price}</td></tr>`;
            }).join('') || '<tr><td colspan="2" style="padding: 10px 0; color: #6b7280;">No amenities listed</td></tr>';

            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Reservation Receipt #${res.id} - Hinaguan Nature Park</title>
                    <style>
                        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 40px; color: #1c2b22; background: #fff; }
                        .receipt { max-width: 620px; margin: 0 auto; border: 1px solid #e5e7eb; padding: 32px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
                        .header { text-align: center; border-bottom: 2px dashed #1c5c3c; padding-bottom: 20px; margin-bottom: 24px; }
                        .header h1 { margin: 0; color: #1c5c3c; font-size: 24px; font-weight: 800; letter-spacing: 0.05em; }
                        .header p { margin: 4px 0 0; color: #6b7280; font-size: 13px; }
                        .badge { display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 700; margin-top: 10px; background: rgba(28,92,60,0.1); color: #1c5c3c; }
                        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 24px; font-size: 13px; background: #f9fafb; padding: 16px; border-radius: 12px; }
                        .label { color: #6b7280; font-size: 11px; text-transform: uppercase; font-weight: bold; letter-spacing: 0.04em; }
                        .val { font-weight: 600; color: #111827; margin-top: 2px; font-size: 13px; }
                        .table-wrap { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 13px; }
                        .summary { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 18px; margin-top: 20px; }
                        .summary-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 13px; color: #374151; }
                        .summary-row.total { font-weight: 800; font-size: 16px; border-top: 1px solid #86efac; padding-top: 10px; margin-top: 8px; color: #166534; }
                        .footer { text-align: center; margin-top: 32px; font-size: 11px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 18px; }
                        @media print { body { margin: 0; } .receipt { border: none; box-shadow: none; padding: 0; } }
                    </style>
                </head>
                <body>
                    <div class="receipt">
                        <div class="header">
                            <h1>HINAGUAN NATURE PARK</h1>
                            <p>Riverside Eco-Park & Sanctuary · Official Receipt</p>
                            <span class="badge">RESERVATION #${res.id}</span>
                        </div>
                        <div class="details-grid">
                            <div><div class="label">Booker Name</div><div class="val">${escapeHtml(res.booker_name || 'N/A')}</div></div>
                            <div><div class="label">Status</div><div class="val">${escapeHtml(res.status)}</div></div>
                            <div><div class="label">Contact Phone</div><div class="val">${escapeHtml(res.phone || 'N/A')}</div></div>
                            <div><div class="label">Contact Email</div><div class="val">${escapeHtml(res.email || 'N/A')}</div></div>
                            <div><div class="label">Check-In Date</div><div class="val">${escapeHtml(formatDate(res.reservation_date))} (${escapeHtml(res.start_slot || 'Daytime')})</div></div>
                            <div><div class="label">Number of Guests</div><div class="val">${escapeHtml(res.number_of_guests || 1)} Guests</div></div>
                        </div>
                        <table class="table-wrap">
                            <thead><tr style="text-align: left; border-bottom: 2px solid #e5e7eb;"><th style="padding-bottom: 8px; color: #4b5563;">Reserved Amenities</th><th style="text-align: right; padding-bottom: 8px; color: #4b5563;">Amount</th></tr></thead>
                            <tbody>${amenitiesTableHtml}</tbody>
                        </table>
                        <div class="summary">
                            <div class="summary-row"><span>Total Booking Cost:</span><span>₱${totalAmount}</span></div>
                            <div class="summary-row" style="color: #166534; font-weight: 600;"><span>Amount Paid:</span><span>₱${amountPaid}</span></div>
                            <div class="summary-row total"><span>Remaining Balance:</span><span>₱${balance}</span></div>
                        </div>
                        <div class="footer">
                            <p>Thank you for choosing Hinaguan Nature Park!</p>
                            <p>For any inquiries, please contact our support desk.</p>
                        </div>
                    </div>
                    <script>
                        window.onload = function() { window.print(); }
                    </script>
                </body>
                </html>
            `);
            printWindow.document.close();
        };

        const expectedCheckout = formatExpectedCheckout(reservation);

        const todayStr = new Date().toISOString().split('T')[0];
        const resDateStr = reservation.reservation_date ? String(reservation.reservation_date).split('T')[0] : '';
        const statusLower = String(reservation.status || '').toLowerCase();
        const isPendingOrConfirmed = ['pending', 'confirmed'].includes(statusLower);
        const isPastArrival = resDateStr && resDateStr < todayStr && isPendingOrConfirmed;

        let overdueBannerHtml = '';
        if (isPastArrival) {
            const rDate = new Date(resDateStr);
            const tDate = new Date(todayStr);
            const daysOverdue = Math.max(1, Math.round((tDate - rDate) / (1000 * 60 * 60 * 24)));
            overdueBannerHtml = `
                <div class="mb-4 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-2.5">
                    <svg class="w-4 h-4 shrink-0 text-rose-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <strong>Arrival Reminder:</strong> Scheduled arrival was <strong>${escapeHtml(formatStayDate(reservation.reservation_date, reservation.end_date, reservation.total_days, reservation.start_slot, reservation.end_slot))}</strong> (${daysOverdue} ${daysOverdue === 1 ? 'day' : 'days'} ago). The guest has not yet checked in.
                    </div>
                </div>
            `;
        }

        const sSlot = reservation.start_slot || 'Daytime';
        const isNight = sSlot.toLowerCase().includes('night');
        const slotPill = isNight
            ? `<span class="inline-flex items-center gap-1 rounded-full bg-[#1b4332] text-white px-2.5 py-0.5 text-[11px] font-semibold"><svg class="w-3 h-3 text-emerald-300" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg> Nighttime</span>`
            : `<span class="inline-flex items-center gap-1 rounded-full bg-amber-500 text-white px-2.5 py-0.5 text-[11px] font-semibold"><svg class="w-3 h-3 text-amber-100" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/></svg> Daytime</span>`;

        const totalAmountNum = Number(reservation.total_amount || 0);
        const amountPaidNum = Number(reservation.amount_paid || 0);
        const balanceNum = Number(reservation.remaining_balance || 0);
        const isFullyPaid = balanceNum <= 0 && totalAmountNum > 0;
        const isPartial = amountPaidNum > 0 && balanceNum > 0;

        let paymentStatusBadgeClass = 'bg-[#fef3c7] text-[#92400e] border-[#fde68a]';
        let paymentStatusLabel = reservation.payment_status || (isFullyPaid ? 'Paid in Full' : (isPartial ? 'Partially Paid' : 'Payment Pending'));

        if (isFullyPaid) {
            paymentStatusBadgeClass = 'bg-[#dcfce7] text-[#166534] border-[#bbf7d0]';
        } else if (isPartial) {
            paymentStatusBadgeClass = 'bg-[#fef3c7] text-[#92400e] border-[#fde68a]';
        } else {
            paymentStatusBadgeClass = 'bg-[#ffe4e6] text-[#9f1239] border-[#fecdd3]';
        }

        const formattedTotal = totalAmountNum.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const formattedPaid = amountPaidNum.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const formattedBalance = balanceNum.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Update Top Header Status Badge
        const modalStatus = document.getElementById('reservationModalStatus');
        if (modalStatus) {
            const statusText = reservation.status || 'Pending';
            modalStatus.innerHTML = `
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>${escapeHtml(statusText)}</span>
            `;
            const sLower = statusText.toLowerCase();
            if (sLower === 'confirmed' || sLower === 'checked in' || sLower === 'pending') {
                modalStatus.className = 'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold bg-[#e8f5e9] text-[#1b4332] border border-[#c8e6c9]/60 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/40';
            } else if (sLower === 'cancelled' || sLower === 'rejected') {
                modalStatus.className = 'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/40';
            } else {
                modalStatus.className = 'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold bg-gray-100 text-gray-800 border border-gray-200 dark:bg-white/10 dark:text-gray-300 dark:border-white/10';
            }
        }

        // Generate Amenities items HTML if present
        const amenitiesCardsHtml = rawAmenities.map((amenity) => {
            const name = amenity.amenity?.amenities_name || amenity.amenity_name || amenity.amenities_name || amenity.name || 'Amenity';
            const aSDate = amenity.start_date || reservation.reservation_date;
            const aEDate = amenity.end_date || reservation.end_date || aSDate;
            const aSSlot = amenity.start_slot || reservation.start_slot || 'Daytime';
            const aESlot = amenity.end_slot || reservation.end_slot || aSSlot;
            const aHasRange = aSDate && aEDate && aSDate !== aEDate;
            const pricingType = amenity.pricing_type || (aHasRange ? `Continuous Stay (${reservation.total_days || 1}D)` : aSSlot);

            return `
                <div class="flex items-center justify-between p-2.5 rounded-xl border border-gray-100 dark:border-white/10 bg-white dark:bg-white/5 text-xs">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-[#e8f5e9] text-[#1b4332] dark:bg-emerald-950/60 dark:text-emerald-300 flex items-center justify-center text-xs font-bold shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        </div>
                        <div>
                            <span class="font-bold text-gray-900 dark:text-white">${escapeHtml(name)}</span>
                            <span class="text-gray-400 ml-1.5 font-normal">(${escapeHtml(pricingType)})</span>
                        </div>
                    </div>
                    <div class="font-bold text-gray-900 dark:text-white">
                        ₱${Number(amenity.price_at_booking || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                    </div>
                </div>
            `;
        }).join('');

        // Construct the 100% pixel-accurate modal matching screenshot
        modalBody.innerHTML = `
            <div class="space-y-4">
                ${overdueBannerHtml}

                <!-- Main White Spacious Content Card -->
                <div class="rounded-2xl border border-gray-100 dark:border-white/10 bg-white dark:bg-[#1c221e] p-5 sm:p-6">
                    
                    <!-- Row 1: Reservation ID & Booker -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-8">
                        <div>
                            <div class="text-xs font-medium text-gray-400 dark:text-gray-400">Reservation ID</div>
                            <div class="text-base font-bold text-[#1b4332] dark:text-emerald-400 font-mono mt-0.5">#${reservation.id}</div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-400 dark:text-gray-400">Booker</div>
                            <div class="flex items-center gap-1.5 text-sm font-bold text-gray-900 dark:text-white mt-0.5">
                                <svg class="w-4 h-4 text-[#1b4332] dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span class="truncate">${escapeHtml(reservation.booker_name || 'Unnamed Booker')}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Contact & Reservation Stay -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-8 mt-4">
                        <div>
                            <div class="text-xs font-medium text-gray-400 dark:text-gray-400 mb-1">Contact</div>
                            <div class="flex items-center gap-2 text-xs font-semibold text-gray-800 dark:text-gray-200">
                                <svg class="w-3.5 h-3.5 text-[#1b4332] dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <span>${escapeHtml(reservation.phone || 'No phone')}</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs font-medium text-gray-700 dark:text-gray-300 mt-1">
                                <svg class="w-3.5 h-3.5 text-[#1b4332] dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span class="truncate">${escapeHtml(reservation.email || 'No email')}</span>
                            </div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-400 dark:text-gray-400 mb-1">Reservation Stay</div>
                            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-gray-800 dark:text-gray-200 mt-0.5">
                                <svg class="w-4 h-4 text-[#1b4332] dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <span>${escapeHtml(formatDate(reservation.reservation_date))} (${escapeHtml(sSlot)})</span>
                                ${slotPill}
                            </div>
                        </div>
                    </div>

                    <!-- Divider Line -->
                    <div class="border-t border-gray-100 dark:border-white/10 my-4"></div>

                    <!-- Row 3: Expected Check-out & Guests -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-8">
                        <div>
                            <div class="text-xs font-medium text-gray-400 dark:text-gray-400 mb-1">Expected Check-out</div>
                            <div class="flex items-center gap-2 text-xs font-bold text-[#1b4332] dark:text-emerald-400">
                                <svg class="w-4 h-4 text-[#1b4332] dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <span>${escapeHtml(expectedCheckout.fullText)}</span>
                            </div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-400 dark:text-gray-400 mb-1">Guests</div>
                            <div class="flex items-center gap-2 text-xs font-bold text-gray-900 dark:text-white">
                                <svg class="w-4 h-4 text-[#1b4332] dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>${escapeHtml(reservation.number_of_guests || 1)} Guest${(reservation.number_of_guests > 1) ? 's' : ''}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Divider Line -->
                    <div class="border-t border-gray-100 dark:border-white/10 my-4"></div>

                    <!-- Row 4: Clean & Redesigned Payment Section -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-8">
                        <div>
                            <div class="text-xs font-medium text-gray-400 dark:text-gray-400 mb-1">Payment</div>
                            <div class="flex items-center gap-2 text-xs">
                                <svg class="w-4 h-4 text-[#1b4332] dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                <div class="flex flex-wrap items-center gap-x-2.5 gap-y-0.5">
                                    <span class="font-bold text-gray-900 dark:text-white">₱${formattedPaid} <span class="font-normal text-gray-400 text-[11px]">· Paid</span></span>
                                    <span class="font-bold ${balanceNum > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-emerald-700 dark:text-emerald-400'}">₱${formattedBalance} <span class="font-normal text-gray-400 text-[11px]">· Balance</span></span>
                                </div>
                            </div>
                            <div class="text-[11px] text-gray-400 font-medium mt-1 ml-6">Total: ₱${formattedTotal}</div>
                        </div>
                        <div>
                            <div class="text-xs font-medium text-gray-400 dark:text-gray-400 mb-1">Payment Status</div>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold border ${paymentStatusBadgeClass}">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    <span>${escapeHtml(paymentStatusLabel)}</span>
                                </span>
                                ${reservation.payment_method ? `<span class="text-[11px] text-gray-400 font-mono uppercase">(${escapeHtml(reservation.payment_method)})</span>` : ''}
                            </div>
                        </div>
                    </div>

                    <!-- Row 5: Guests on this Reservation Sub-Card -->
                    <div class="mt-5 rounded-xl border border-gray-100 dark:border-white/10 bg-gray-50/50 dark:bg-white/5 p-4">
                        <div class="text-xs font-bold text-gray-800 dark:text-gray-200 mb-3">Guests on this Reservation</div>
                        
                        <div class="flex items-center justify-between p-3 rounded-xl border border-gray-100 dark:border-white/10 bg-white dark:bg-white/5 shadow-2xs">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-full bg-[#e8f5e9] text-[#1b4332] dark:bg-emerald-950/60 dark:text-emerald-300 flex items-center justify-center font-bold text-xs shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[10px] uppercase font-semibold text-gray-400 tracking-wider">Primary Guest</div>
                                    <div class="text-xs font-bold text-gray-900 dark:text-white truncate">${escapeHtml(reservation.booker_name || 'Booker')} <span class="font-normal text-gray-500 text-[11px]">Booker</span></div>
                                    <div class="text-[11px] text-gray-500 truncate">${escapeHtml(reservation.email || 'No email')}</div>
                                </div>
                            </div>
                            <div class="shrink-0 ml-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#e8f5e9] text-[#1b4332] border border-[#c8e6c9] dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">Primary</span>
                            </div>
                        </div>

                        ${rawGuests.filter(g => !g.is_primary_guest).map(g => {
                            const gName = [g.customer?.first_name, g.customer?.last_name].filter(Boolean).join(' ').trim() || 'Companion Guest';
                            const gEmail = g.customer?.email || 'No email';
                            return `
                                <div class="flex items-center justify-between p-3 rounded-xl border border-gray-100 dark:border-white/10 bg-white dark:bg-white/5 shadow-2xs mt-2">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-8 h-8 rounded-full bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300 flex items-center justify-center text-xs font-bold shrink-0">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-[10px] uppercase font-semibold text-gray-400 tracking-wider">Companion</div>
                                            <div class="text-xs font-bold text-gray-900 dark:text-white truncate">${escapeHtml(gName)}</div>
                                            <div class="text-[11px] text-gray-500 truncate">${escapeHtml(gEmail)}</div>
                                        </div>
                                    </div>
                                    <div class="shrink-0 ml-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-600 border border-gray-200 dark:bg-white/10 dark:text-gray-300 dark:border-white/10">Guest</span>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>

                    ${rawAmenities.length > 0 ? `
                        <!-- Reserved Amenities List -->
                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-white/10">
                            <div class="text-xs font-bold text-gray-800 dark:text-gray-200 mb-2.5 flex items-center justify-between">
                                <span>Reserved Amenities</span>
                                <span class="text-[11px] text-[#1b4332] dark:text-emerald-300 font-semibold">${rawAmenities.length} unit${rawAmenities.length !== 1 ? 's' : ''}</span>
                            </div>
                            <div class="grid gap-2">
                                ${amenitiesCardsHtml}
                            </div>
                        </div>
                    ` : ''}

                </div>

                ${(reservation.status === 'Checked In' || (reservation.status !== 'Checked Out' && reservation.status !== 'Cancelled')) ? `
                    <!-- Actions Footer (Check In / Check Out) -->
                    <div class="flex items-center justify-end gap-2.5 pt-1">
                        ${reservation.status === 'Checked In'
                            ? `<button type="button" class="cursor-pointer rounded-xl border-0 bg-emerald-600 px-6 py-2.5 text-xs font-bold text-white transition-all hover:bg-emerald-700 shadow-xs" id="reservationCheckOutBtn" data-reservation-checkout="${reservation.id}">Check Out</button>`
                            : `<button type="button" class="cursor-pointer rounded-xl border-0 bg-[#1b4332] px-6 py-2.5 text-xs font-bold text-white transition-all hover:bg-[#2d6a4f] shadow-xs" data-open-check-in-modal="${reservation.id}">Check In</button>`
                        }
                    </div>
                ` : ''}
            </div>
        `;

        // Hook up close buttons inside modalBody if any
        modalBody.querySelectorAll('[data-close-reservation-modal="true"]').forEach((btn) => {
            btn.addEventListener('click', closeModal);
        });

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
        const todayStr = new Date().toISOString().split('T')[0];
        Object.values(data).forEach((reservation) => {
            const resDateStr = reservation.reservation_date ? String(reservation.reservation_date).split('T')[0] : '';
            const isToday = resDateStr === todayStr;
            const statusLower = String(reservation.status || '').toLowerCase();
            const isPendingOrConfirmed = ['pending', 'confirmed'].includes(statusLower);
            const isPastArrival = resDateStr && resDateStr < todayStr && isPendingOrConfirmed;

            const row = document.createElement('tr');
            row.className = `guest-row reservation-row ${isToday ? 'today-reservation' : ''} ${isPastArrival ? 'past-reservation' : ''}`;
            row.setAttribute('data-reservation-id', reservation.id);
            row.setAttribute('data-booker-name', reservation.booker_name);
            row.setAttribute('data-email', reservation.email);
            row.setAttribute('data-phone', reservation.phone);
            row.setAttribute('data-reservation-date', reservation.reservation_date);
            row.setAttribute('data-status', reservation.status.toLowerCase());
            row.setAttribute('data-guests', reservation.number_of_guests);
            row.setAttribute('data-total-amount', reservation.total_amount);
            row.setAttribute('data-is-past', isPastArrival ? '1' : '0');
            row.setAttribute('data-search', `${reservation.id} #${reservation.id} ${(reservation.booker_name || '').toLowerCase()} ${(reservation.email || '').toLowerCase()} ${(reservation.phone || '').toLowerCase()} ${(reservation.status || '').toLowerCase()} ${isPastArrival ? 'past overdue' : ''} ${isToday ? 'today' : ''}`);
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
                    <span class="text-xs font-bold text-hp-text dark:text-[#f3f4f6]">Booked Amenity #${idx + 1}</span>
                    <span class="edit-amenity-price-tag text-xs font-bold text-hp-green dark:text-[#9ca3af]">₱${Number(ra.price_at_booking || 0).toFixed(2)}</span>
                </div>
                <div class="grid gap-1">
                    <label class="text-[0.75rem] font-semibold text-hp-text">Change / Swap Amenity</label>
                    <select class="edit-amenity-select w-full rounded-xl border border-glass-border bg-glass px-3 py-2 text-xs text-hp-text transition-colors focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                        ${optionsHtml}
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div class="grid gap-1">
                        <label class="text-[0.72rem] font-semibold text-hp-text-muted">Amenity Check-In Date & Session</label>
                        <div class="flex gap-1.5">
                            <input type="date" class="edit-amenity-start-date flex-1 rounded-lg border border-glass-border bg-glass px-2.5 py-1.5 text-xs text-hp-text focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]" value="${raStart}" min="${masterStart}" max="${masterEnd}">
                            <select class="edit-amenity-start-slot rounded-lg border border-glass-border bg-glass px-2 py-1.5 text-xs text-hp-text focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
                                <option value="Daytime" ${raStartSlot === 'Daytime' ? 'selected' : ''}>Daytime</option>
                                <option value="Nighttime" ${raStartSlot === 'Nighttime' ? 'selected' : ''}>Nighttime</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid gap-1">
                        <label class="text-[0.72rem] font-semibold text-hp-text-muted">Amenity Check-Out Date & Session</label>
                        <div class="flex gap-1.5">
                            <input type="date" class="edit-amenity-end-date flex-1 rounded-lg border border-glass-border bg-glass px-2.5 py-1.5 text-xs text-hp-text focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]" value="${raEnd}" min="${masterStart}" max="${masterEnd}">
                            <select class="edit-amenity-end-slot rounded-lg border border-glass-border bg-glass px-2 py-1.5 text-xs text-hp-text focus:border-hp-green focus:outline-none dark:border-white/10 dark:bg-white/5 dark:text-[#f3f4f6]">
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
                        editPriceDiffBadge.innerHTML = `<span class="inline-block rounded-md bg-hp-green/10 px-2 py-0.5 text-hp-green dark:bg-[#81c784]/20 dark:text-[#9ca3af]">-₱${Math.abs(pricing.totalDiff).toFixed(2)} reduced from total & balance</span>`;
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
                        const fullRes = reservationData[reservationId] || { id: reservationId, ...updated };
                        const bName = fullRes.booker_name || formData.get('booker_name');
                        const bEmail = fullRes.email || formData.get('email');
                        const bPhone = fullRes.phone || formData.get('phone');
                        const sDate = fullRes.reservation_date || formData.get('reservation_date');
                        const status = fullRes.status || formData.get('status');
                        const guests = fullRes.number_of_guests || formData.get('number_of_guests');
                        const todayStr = new Date().toISOString().split('T')[0];
                        const resDateStr = sDate ? String(sDate).split('T')[0] : '';
                        const isToday = resDateStr === todayStr;
                        const statusLower = String(status || '').toLowerCase();
                        const isPendingOrConfirmed = ['pending', 'confirmed'].includes(statusLower);
                        const isPastArrival = resDateStr && resDateStr < todayStr && isPendingOrConfirmed;

                        tableRow.className = `guest-row reservation-row ${isToday ? 'today-reservation' : ''} ${isPastArrival ? 'past-reservation' : ''} cursor-pointer select-none transition-colors duration-150 hover:bg-[#f7faf6] focus-visible:bg-[#f7faf6] focus-visible:outline-none dark:hover:bg-[#242a26] dark:focus-visible:bg-[#242a26]`;
                        tableRow.setAttribute('data-booker-name', bName);
                        tableRow.setAttribute('data-email', bEmail);
                        tableRow.setAttribute('data-phone', bPhone);
                        tableRow.setAttribute('data-reservation-date', sDate);
                        tableRow.setAttribute('data-status', String(status).toLowerCase());
                        tableRow.setAttribute('data-guests', guests);
                        if (fullRes.total_amount !== undefined) {
                            tableRow.setAttribute('data-total-amount', fullRes.total_amount);
                        }
                        tableRow.setAttribute('data-is-past', isPastArrival ? '1' : '0');
                        tableRow.setAttribute('data-search', `${reservationId} #${reservationId} ${(bName || '').toLowerCase()} ${(bEmail || '').toLowerCase()} ${(bPhone || '').toLowerCase()} ${(status || '').toLowerCase()} ${isPastArrival ? 'past overdue' : ''} ${isToday ? 'today' : ''}`);

                        tableRow.innerHTML = buildRowCells(fullRes);
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

                    if (window.reservationsData && window.reservationsData[reservationId]) {
                        window.reservationsData[reservationId].status = 'Completed';
                    }
                    const resRow = document.querySelector(`tr[data-reservation-id="${reservationId}"]`);
                    if (resRow) {
                        const statusPill = resRow.querySelector('.status-pill, .badge-status');
                        if (statusPill) {
                            statusPill.textContent = 'Completed';
                            statusPill.className = 'status-pill status-pill--completed';
                        }
                    }
                    closeModal();
                    window.dispatchEvent(new CustomEvent('app:data-mutated'));
                    showToast(`Reservation #${reservationId} checked out successfully.`);
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
        const poolOpt = checkInPoolOption?.value || 'no_pool';
        const primaryHasPool = (poolOpt === 'all_paid' || poolOpt === 'all_free')
            ? true
            : (poolOpt === 'specific' ? !!checkInPrimaryGuestHasPool?.checked : false);

        const primaryGuest = guestMode === 'with_primary' ? {
            first_name: formData.get('check_in_primary_guest[first_name]'),
            middle_name: formData.get('check_in_primary_guest[middle_name]'),
            last_name: formData.get('check_in_primary_guest[last_name]'),
            age: formData.get('check_in_primary_guest[age]'),
            gender: formData.get('check_in_primary_guest[gender]'),
            is_foreigner: formData.get('check_in_primary_guest[is_foreigner]') === '1',
            phone: formData.get('check_in_primary_guest[phone]'),
            email: formData.get('check_in_primary_guest[email]'),
            has_pool_access: primaryHasPool,
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
                    pool_option: poolOpt,
                    include_pool: (poolOpt === 'all_paid' || poolOpt === 'specific' || poolOpt === 'all_free') ? '1' : '0',
                }),
            });

            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(payload.message || 'Unable to check in this reservation.');
            }

            if (window.reservationsData && window.reservationsData[pendingReservationId]) {
                window.reservationsData[pendingReservationId].status = 'Checked In';
                window.reservationsData[pendingReservationId].payment_status = 'Paid';
            }
            const resRow = document.querySelector(`tr[data-reservation-id="${pendingReservationId}"]`);
            if (resRow) {
                const statusPill = resRow.querySelector('.status-pill, .badge-status');
                if (statusPill) {
                    statusPill.textContent = 'Checked In';
                    statusPill.className = 'status-pill status-pill--checked-in';
                }
                const paymentPill = resRow.querySelector('.payment-status-pill, .badge-payment');
                if (paymentPill) {
                    paymentPill.textContent = 'PAID';
                }
            }
            closeCheckInModal();
            closeCompanionSummaryModal?.();
            closeModal?.();
            window.dispatchEvent(new CustomEvent('app:data-mutated'));
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
            const matchesStatus = statusValue === 'all'
                || (statusValue === 'today' && row.classList.contains('today-reservation'))
                || (statusValue === 'past' && (row.classList.contains('past-reservation') || row.getAttribute('data-is-past') === '1'))
                || row.getAttribute('data-status') === statusValue;
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