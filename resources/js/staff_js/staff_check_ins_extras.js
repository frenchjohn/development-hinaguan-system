import { Html5Qrcode } from 'html5-qrcode';
import { showToast, queueToast, showPendingToast, convertFlashToToast } from './toast.js';

export function initStaffCheckInsExtras(ctx) {
    const {
        pageSignal,
        reservationAddCompanionBtn,
        csrfToken,
        reservationData,
        escapeHtml,
        formatReservationId,
        openReservationModal,
        buildContinuousTimeline,
        formatPeso,
        chooseAmenitiesBtn,
        selectedAmenitiesContainer,
        amenitiesHiddenInputs,
        noAmenitiesNotice,
        todayStr,
        currentServerSession,
        walkInSchedule,
        selectedAmenities,
        calculateWalkInSlots,
        formatDisplayDate,
        formatShortDate,
        getCheckoutDateAndDisplay,
        syncMasterScheduleDisplay,
        syncWalkInAmenityDropdowns,
        syncMainGuestCardUI,
        loadParkSettings,
        updateGrandTotal,
        companions,
        getGenderBadgeHtml,
        groupEditAccessRow,
        groupEditPoolWrap,
    } = ctx;

    // ==========================================
    // Walk-In Range Calendar Modal Logic
    // ==========================================
    const walkInCalendarModal = document.getElementById('walkInCalendarModal');
    const walkInOpenCalendarBtn = document.getElementById('walkInOpenCalendarBtn');
    const walkInCalCloseButtons = document.querySelectorAll('[data-close-walkin-calendar="true"]');
    const walkInCalPrev = document.getElementById('walkInCalPrev');
    const walkInCalNext = document.getElementById('walkInCalNext');
    const walkInCalTitle = document.getElementById('walkInCalTitle');
    const walkInCalYear = document.getElementById('walkInCalYear');
    const walkInCalGrid = document.getElementById('walkInCalGrid');
    const walkInCalSummaryText = document.getElementById('walkInCalSummaryText');
    const walkInCalSpanText = document.getElementById('walkInCalSpanText');
    const walkInCalStepHelp = document.getElementById('walkInCalStepHelp');
    const walkInCalApplyBtn = document.getElementById('walkInCalApplyBtn');
    const walkInCalCurrentBadge = document.getElementById('walkInCalCurrentBadge');
    const walkInTopCheckoutTimeBadge = document.getElementById('walkInTopCheckoutTimeBadge');
    const walkInCalCheckoutDateText = document.getElementById('walkInCalCheckoutDateText');
    const walkInCalCheckoutSessionBadge = document.getElementById('walkInCalCheckoutSessionBadge');
    const walkInCalCheckoutSessionIcon = document.getElementById('walkInCalCheckoutSessionIcon');
    const walkInCalCheckoutSessionText = document.getElementById('walkInCalCheckoutSessionText');
    const walkInCalStartSlotText = document.getElementById('walkInCalStartSlotText');
    const walkInCalSpanDetailText = document.getElementById('walkInCalSpanDetailText');
    const walkInCalDaysBadgeText = document.getElementById('walkInCalDaysBadgeText');

    const walkInCalState = {
        viewYear: new Date().getFullYear(),
        viewMonth: new Date().getMonth(),
        selectedStartDate: todayStr,
        selectedEndDate: todayStr,
        selectedStartSlot: currentServerSession,
        selectedEndSlot: currentServerSession,
        availabilityMap: {},
        isLoadingAvailability: false,
    };

    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    // Populate Year Dropdown (current year up to +5 years)
    const initWalkInCalYears = () => {
        if (!walkInCalYear) return;
        const currentYear = new Date().getFullYear();
        walkInCalYear.innerHTML = '';
        for (let y = currentYear; y <= currentYear + 5; y++) {
            const opt = document.createElement('option');
            opt.value = y;
            opt.textContent = y;
            walkInCalYear.appendChild(opt);
        }
    };
    initWalkInCalYears();

    const isWalkInSlotAvailable = (dateStr, slot) => {
        if (!dateStr || dateStr < todayStr) return false;
        if (dateStr === todayStr && slot === 'Daytime' && currentServerSession === 'Nighttime') return false;
        if (!selectedAmenities || selectedAmenities.length === 0) return true;

        const entry = walkInCalState.availabilityMap?.[dateStr];
        if (entry) {
            const slotKey = slot === 'Daytime' ? 'daytime' : 'nighttime';
            return Boolean(entry[slotKey]);
        }
        return true;
    };

    const checkWalkInRangeConflict = (startDate, endDate, startSlot = 'Daytime', endSlot = 'Daytime') => {
        if (!startDate || !endDate || startDate > endDate) {
            return { hasConflict: true, reason: 'Check-out date must be on or after check-in date.' };
        }
        if (!selectedAmenities || selectedAmenities.length === 0) {
            return { hasConflict: false };
        }

        const timeline = buildContinuousTimeline(startDate, endDate, startSlot, endSlot);
        if (!timeline.length) {
            return { hasConflict: true, reason: 'Invalid stay range.' };
        }

        for (const [d, s] of timeline) {
            if (!isWalkInSlotAvailable(d, s)) {
                const isAtEnd = (d === endDate && s === endSlot);
                return {
                    hasConflict: true,
                    conflictDate: d,
                    conflictSlot: s,
                    isDirectEndConflict: isAtEnd,
                    isIntermediateConflict: !isAtEnd,
                    reason: isAtEnd
                        ? `Selected check-out date (${formatShortDate(d)} ${s}) is occupied or reserved for your selected amenities.`
                        : `Cannot pass through occupied or reserved date (${formatShortDate(d)} ${s}). All dates throughout the stay must be available.`
                };
            }
        }

        return { hasConflict: false };
    };

    const ensureWalkInMonthAvailability = async (year, month, amenityIds) => {
        try {
            const url = new URL('/reservation/availability/calendar', window.location.origin);
            url.searchParams.set('amenity_ids', amenityIds.join(','));
            url.searchParams.set('month', month);
            url.searchParams.set('year', year);

            const response = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
            if (response.ok) {
                const data = await response.json();
                if (Array.isArray(data.availability)) {
                    data.availability.forEach(entry => {
                        walkInCalState.availabilityMap[entry.date] = entry;
                    });
                }
            }
        } catch (err) {
            console.error('Error fetching availability for month:', year, month, err);
        }
    };

    const loadWalkInCalAvailability = async (year = walkInCalState.viewYear, month = walkInCalState.viewMonth) => {
        if (!selectedAmenities || selectedAmenities.length === 0) {
            walkInCalState.availabilityMap = {};
            renderWalkInCalendarMonth();
            return;
        }

        const amenityIds = [...new Set(selectedAmenities.map(a => a.amenity_id).filter(Boolean))];
        if (amenityIds.length === 0) {
            walkInCalState.availabilityMap = {};
            renderWalkInCalendarMonth();
            return;
        }

        walkInCalState.isLoadingAvailability = true;
        if (walkInCalGrid) {
            walkInCalGrid.classList.add('opacity-50', 'pointer-events-none');
        }

        if (!walkInCalState.availabilityMap) walkInCalState.availabilityMap = {};

        try {
            const promises = [ensureWalkInMonthAvailability(year, month, amenityIds)];
            
            if (walkInCalState.selectedStartDate) {
                const [sY, sM] = walkInCalState.selectedStartDate.split('-').map(Number);
                const sMonthIdx = sM - 1;
                if (sY !== year || sMonthIdx !== month) {
                    promises.push(ensureWalkInMonthAvailability(sY, sMonthIdx, amenityIds));
                }
            }

            await Promise.all(promises);
        } catch (err) {
            console.error('Failed to load walk-in calendar availability:', err);
        } finally {
            walkInCalState.isLoadingAvailability = false;
            if (walkInCalGrid) {
                walkInCalGrid.classList.remove('opacity-50', 'pointer-events-none');
            }
            renderWalkInCalendarMonth();
        }
    };

    const openWalkInCalendarModal = () => {
        if (!walkInCalendarModal) return;
        // Walk-in check-in is ALWAYS locked to TODAY and CURRENT SESSION
        walkInCalState.selectedStartDate = todayStr;
        walkInCalState.selectedStartSlot = currentServerSession;
        walkInCalState.selectedEndDate = walkInSchedule.endDate || todayStr;
        walkInCalState.selectedEndSlot = walkInSchedule.endSlot || currentServerSession;

        // If checkin is today nighttime and selected checkout is today, force nighttime checkout
        if (walkInCalState.selectedStartDate === walkInCalState.selectedEndDate && walkInCalState.selectedStartSlot === 'Nighttime') {
            walkInCalState.selectedEndSlot = 'Nighttime';
        }

        const [y, m] = walkInCalState.selectedStartDate.split('-');
        walkInCalState.viewYear = parseInt(y);
        walkInCalState.viewMonth = parseInt(m) - 1;

        walkInCalendarModal.classList.add('is-open');
        walkInCalendarModal.setAttribute('aria-hidden', 'false');

        syncWalkInSessionPills();
        renderWalkInCalendarMonth();
        loadWalkInCalAvailability(walkInCalState.viewYear, walkInCalState.viewMonth);
    };

    const closeWalkInCalendarModal = () => {
        if (!walkInCalendarModal) return;
        walkInCalendarModal.classList.remove('is-open');
        walkInCalendarModal.setAttribute('aria-hidden', 'true');
    };

    walkInOpenCalendarBtn?.addEventListener('click', openWalkInCalendarModal);
    walkInCalCloseButtons.forEach(btn => btn.addEventListener('click', closeWalkInCalendarModal));

    // Session pills toggle
    const syncWalkInSessionPills = () => {
        const isSameDayNighttime = (walkInCalState.selectedStartDate === walkInCalState.selectedEndDate && walkInCalState.selectedStartSlot === 'Nighttime');

        document.querySelectorAll('#walkInEndSlotGroup [data-slot-val]').forEach(btn => {
            const val = btn.dataset.slotVal;
            const isActive = (val === walkInCalState.selectedEndSlot);
            btn.dataset.active = isActive ? 'true' : 'false';
            btn.classList.toggle('is-active', isActive);

            let isDisabled = false;
            let disableReason = '';

            if (isSameDayNighttime && val === 'Daytime') {
                isDisabled = true;
                disableReason = 'Daytime checkout not available for same-day nighttime check-in';
            } else if (selectedAmenities && selectedAmenities.length > 0) {
                const conflict = checkWalkInRangeConflict(walkInCalState.selectedStartDate, walkInCalState.selectedEndDate, walkInCalState.selectedStartSlot, val);
                if (conflict.hasConflict) {
                    isDisabled = true;
                    disableReason = conflict.reason;
                }
            }

            if (isDisabled) {
                btn.disabled = true;
                btn.classList.add('is-disabled', 'opacity-50', 'cursor-not-allowed');
                btn.title = disableReason;
            } else {
                btn.disabled = false;
                btn.classList.remove('is-disabled', 'opacity-50', 'cursor-not-allowed');
                btn.removeAttribute('title');
            }
        });
    };

    document.querySelectorAll('#walkInEndSlotGroup [data-slot-val]').forEach(btn => {
        btn.addEventListener('click', () => {
            const val = btn.dataset.slotVal;
            // If same day and checkin is Nighttime, cannot pick Daytime checkout
            if (walkInCalState.selectedStartDate === walkInCalState.selectedEndDate && walkInCalState.selectedStartSlot === 'Nighttime' && val === 'Daytime') {
                return;
            }
            if (selectedAmenities && selectedAmenities.length > 0) {
                const conflict = checkWalkInRangeConflict(walkInCalState.selectedStartDate, walkInCalState.selectedEndDate, walkInCalState.selectedStartSlot, val);
                if (conflict.hasConflict) {
                    window.alert(conflict.reason || 'This session is already booked for one or more selected amenities.');
                    return;
                }
            }
            walkInCalState.selectedEndSlot = val;
            syncWalkInSessionPills();
            updateWalkInCalModalSummary();
            renderWalkInCalendarMonth();
        });
    });

    const updateWalkInCalModalSummary = () => {
        const counts = calculateWalkInSlots(walkInCalState.selectedStartDate, walkInCalState.selectedEndDate, walkInCalState.selectedStartSlot, walkInCalState.selectedEndSlot);
        const sFmt = formatDisplayDate(walkInCalState.selectedStartDate);
        const checkout = getCheckoutDateAndDisplay(walkInCalState.selectedEndDate, walkInCalState.selectedEndSlot);
        const spanLabel = counts.daysSpan === 1 ? '1 Day' : `${counts.daysSpan} Days`;

        // Update prominent top summary cards
        const walkInTopCheckInText = document.getElementById('walkInTopCheckInText');
        const walkInTopCheckoutFullText = document.getElementById('walkInTopCheckoutFullText');
        const inTime = walkInCalState.selectedStartSlot === 'Nighttime' ? 'Nighttime (8:00 PM)' : 'Daytime (8:00 AM)';
        const outDesc = (walkInCalState.selectedEndSlot === 'Nighttime') ? '8:00 AM (Next Morning)' : '5:00 PM (Daytime)';
        if (walkInTopCheckInText) {
            walkInTopCheckInText.textContent = `${sFmt} · ${inTime}`;
        }
        if (walkInTopCheckoutFullText) {
            walkInTopCheckoutFullText.textContent = `${checkout.displayDate} · ${outDesc}`;
        }

        // Attached amenities notice
        const attachedNotice = document.getElementById('walkInCalAttachedNotice');
        if (attachedNotice) {
            if (selectedAmenities && selectedAmenities.length > 0) {
                attachedNotice.textContent = `Syncing stay with ${selectedAmenities.length} availed amenity (${selectedAmenities.map(a => a.amenity_name).join(', ')}). Occupied/reserved dates are blocked.`;
                attachedNotice.classList.remove('hidden');
            } else {
                attachedNotice.classList.add('hidden');
            }
        }

        // Update footer and badges
        if (walkInCalCheckoutDateText) {
            walkInCalCheckoutDateText.textContent = `Check-Out: ${checkout.displayDate} · ${outDesc}`;
        }
        if (walkInCalCheckoutSessionText) {
            walkInCalCheckoutSessionText.textContent = checkout.sessionLabel;
        }
        if (walkInCalStartSlotText) {
            walkInCalStartSlotText.textContent = walkInCalState.selectedStartSlot;
        }
        if (walkInCalSpanDetailText) {
            walkInCalSpanDetailText.textContent = `${spanLabel} Stay (${counts.dayCount} Daytime, ${counts.nightCount} Nighttime)`;
        }
        if (walkInCalSummaryScheduleText) {
            walkInCalSummaryScheduleText.textContent = `${spanLabel} Stay (${counts.dayCount} Daytime, ${counts.nightCount} Nighttime) • Check-In: ${sFmt}`;
        }
        if (walkInCalDaysBadgeText) {
            walkInCalDaysBadgeText.textContent = `${spanLabel} Stay`;
        }
        if (walkInTopCheckoutTimeBadge) {
            walkInTopCheckoutTimeBadge.textContent = `${checkout.displayDate} · ${checkout.time}`;
        }

        if (walkInCalSummaryText) {
            walkInCalSummaryText.textContent = `Check-In Today (${walkInCalState.selectedStartSlot}) → Check-Out ${checkout.textSummary}`;
        }
        if (walkInCalSpanText) {
            walkInCalSpanText.textContent = `${spanLabel} Stay (${counts.dayCount} Daytime, ${counts.nightCount} Nighttime)`;
        }
        if (walkInCalCurrentBadge) {
            walkInCalCurrentBadge.textContent = `${counts.daysSpan}D Stay`;
        }
        if (walkInCalStepHelp) {
            walkInCalStepHelp.textContent = 'Click any date from Today onwards to set Check-Out Date';
        }
    };

    const renderWalkInCalendarMonth = () => {
        if (!walkInCalGrid) return;
        const year = walkInCalState.viewYear;
        const month = walkInCalState.viewMonth;

        if (walkInCalTitle) walkInCalTitle.textContent = `${monthNames[month]} ${year}`;
        if (walkInCalYear) walkInCalYear.value = year;

        walkInCalGrid.innerHTML = '';

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Empty padding cells
        for (let i = 0; i < firstDay; i++) {
            const empty = document.createElement('div');
            empty.className = 'edit-calendar__day edit-calendar__day--empty opacity-0 pointer-events-none';
            walkInCalGrid.appendChild(empty);
        }

        // Days
        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'edit-calendar__day flex flex-col items-center justify-center rounded-lg p-1.5 text-xs font-semibold transition-all duration-150 relative cursor-pointer border border-transparent';
            btn.textContent = d;
            btn.dataset.date = dateStr;

            const isPast = dateStr < todayStr;
            const isToday = dateStr === todayStr;
            const inRange = dateStr >= walkInCalState.selectedStartDate && dateStr <= walkInCalState.selectedEndDate;

            if (isPast) {
                btn.classList.add('is-disabled', 'opacity-30', 'cursor-not-allowed');
                btn.disabled = true;
            } else {
                // Check if this date can be picked as Check-Out Date
                let canPickDate = true;
                let conflictReason = '';

                if (selectedAmenities && selectedAmenities.length > 0) {
                    const conflictDay = checkWalkInRangeConflict(walkInCalState.selectedStartDate, dateStr, walkInCalState.selectedStartSlot, 'Daytime');
                    const conflictNight = checkWalkInRangeConflict(walkInCalState.selectedStartDate, dateStr, walkInCalState.selectedStartSlot, 'Nighttime');

                    const dayPossible = !(dateStr === walkInCalState.selectedStartDate && walkInCalState.selectedStartSlot === 'Nighttime') && !conflictDay.hasConflict;
                    const nightPossible = !conflictNight.hasConflict;

                    if (!dayPossible && !nightPossible) {
                        canPickDate = false;
                        conflictReason = conflictNight.reason || conflictDay.reason || 'Booked / Occupied';
                    }
                }

                if (!canPickDate) {
                    btn.disabled = true;
                    btn.classList.add('is-disabled', 'cursor-not-allowed', 'opacity-40', 'bg-rose-50/70', 'text-rose-400', 'border-rose-200', 'dark:bg-rose-950/30', 'dark:text-rose-400');
                    btn.title = conflictReason;

                    const bookedTag = document.createElement('span');
                    bookedTag.className = 'text-[9px] font-bold text-rose-500 uppercase tracking-tighter leading-none mt-0.5 scale-90';
                    bookedTag.textContent = 'Booked';
                    btn.appendChild(bookedTag);
                } else {
                    btn.classList.add('is-available', 'hover:border-hp-green', 'hover:bg-hp-green/10');
                }
            }

            if (inRange && !isPast && !btn.disabled) {
                btn.classList.add('is-selected', 'bg-hp-green', 'text-white', 'font-bold');
                btn.classList.remove('hover:bg-hp-green/10');
            }

            if (isToday) {
                const dot = document.createElement('span');
                dot.className = 'absolute bottom-1 h-1 w-1 rounded-full bg-emerald-400';
                btn.appendChild(dot);
            }

            btn.addEventListener('click', () => {
                if (btn.disabled) return;

                let targetSlot = walkInCalState.selectedEndSlot;
                if (walkInCalState.selectedStartDate === dateStr && walkInCalState.selectedStartSlot === 'Nighttime') {
                    targetSlot = 'Nighttime';
                } else if (selectedAmenities && selectedAmenities.length > 0) {
                    const testCur = checkWalkInRangeConflict(walkInCalState.selectedStartDate, dateStr, walkInCalState.selectedStartSlot, targetSlot);
                    if (testCur.hasConflict) {
                        const altSlot = targetSlot === 'Nighttime' ? 'Daytime' : 'Nighttime';
                        const testAlt = checkWalkInRangeConflict(walkInCalState.selectedStartDate, dateStr, walkInCalState.selectedStartSlot, altSlot);
                        if (!testAlt.hasConflict) {
                            targetSlot = altSlot;
                        } else {
                            window.alert(testCur.reason || 'This date cannot be picked because one or more availed amenities are occupied or reserved.');
                            return;
                        }
                    }
                }

                // Single click sets Check-Out Date (since check-in is fixed to today)
                walkInCalState.selectedEndDate = dateStr;
                walkInCalState.selectedEndSlot = targetSlot;

                syncWalkInSessionPills();
                updateWalkInCalModalSummary();
                renderWalkInCalendarMonth();
            });

            walkInCalGrid.appendChild(btn);
        }

        updateWalkInCalModalSummary();
    };

    walkInCalPrev?.addEventListener('click', () => {
        walkInCalState.viewMonth--;
        if (walkInCalState.viewMonth < 0) {
            walkInCalState.viewMonth = 11;
            walkInCalState.viewYear--;
        }
        renderWalkInCalendarMonth();
        loadWalkInCalAvailability(walkInCalState.viewYear, walkInCalState.viewMonth);
    });

    walkInCalNext?.addEventListener('click', () => {
        walkInCalState.viewMonth++;
        if (walkInCalState.viewMonth > 11) {
            walkInCalState.viewMonth = 0;
            walkInCalState.viewYear++;
        }
        renderWalkInCalendarMonth();
        loadWalkInCalAvailability(walkInCalState.viewYear, walkInCalState.viewMonth);
    });

    walkInCalYear?.addEventListener('change', (e) => {
        walkInCalState.viewYear = parseInt(e.target.value);
        renderWalkInCalendarMonth();
        loadWalkInCalAvailability(walkInCalState.viewYear, walkInCalState.viewMonth);
    });

    walkInCalApplyBtn?.addEventListener('click', () => {
        if (selectedAmenities && selectedAmenities.length > 0) {
            const conflict = checkWalkInRangeConflict(walkInCalState.selectedStartDate, walkInCalState.selectedEndDate, walkInCalState.selectedStartSlot, walkInCalState.selectedEndSlot);
            if (conflict.hasConflict) {
                window.alert(`Cannot apply schedule: ${conflict.reason}`);
                return;
            }
        }

        walkInSchedule.startDate = walkInCalState.selectedStartDate;
        walkInSchedule.endDate = walkInCalState.selectedEndDate;
        walkInSchedule.startSlot = walkInCalState.selectedStartSlot;
        walkInSchedule.endSlot = walkInCalState.selectedEndSlot;

        syncMasterScheduleDisplay();

        // Automatically match ALL availed amenities to the master stay date and sessions
        selectedAmenities.forEach(am => {
            am.start_date = walkInSchedule.startDate;
            am.end_date = walkInSchedule.endDate;
            am.start_slot = walkInSchedule.startSlot;
            am.end_slot = walkInSchedule.endSlot;

            // Recalculate amenity price
            const counts = calculateWalkInSlots(am.start_date, am.end_date, am.start_slot, am.end_slot);
            am.total_days = counts.daysSpan;
            am.day_count = counts.dayCount;
            am.night_count = counts.nightCount;

            const dayP = am.is_aircon && am.daytime_aircon_price ? am.daytime_aircon_price : am.daytime_price;
            const nightP = am.is_aircon && am.nighttime_aircon_price ? am.nighttime_aircon_price : am.nighttime_price;
            const qty = am.quantity || 1;
            am.price_at_booking = ((counts.dayCount * dayP) + (counts.nightCount * nightP)) * qty;
        });

        renderSelectedAmenities();
        updateGrandTotal();
        closeWalkInCalendarModal();
    });

    // ==========================================
    // Amenities Modal & Dynamic Availability Loading
    // ==========================================
    const amenityModal = document.getElementById('amenityModal');
    const amenityCloseButtons = document.querySelectorAll('[data-close-amenity-modal="true"]');
    const amenityModalStayBadge = document.getElementById('amenityModalStayBadge');
    const walkInAmenityCategorySelect = document.getElementById('walkInAmenityCategorySelect');
    const walkInAmenityStatusFilters = document.getElementById('walkInAmenityStatusFilters');
    const walkInAmenityFilterSummaryText = document.getElementById('walkInAmenityFilterSummaryText');

    let cachedAmenityList = [];
    let amenityStatusFilter = 'available'; // Default is 'available' per user request!
    let amenityCategoryFilter = 'all';

    // Categorization helper: groups like Payags, A-Houses, Cottages, etc.
    const categorizeAmenity = (name = '') => {
        const trimmed = (name || '').trim();
        const lower = trimmed.toLowerCase();
        if (lower.startsWith('a-house') || lower.startsWith('ahouse') || lower.includes('a-house')) return 'A-Houses';
        if (lower.startsWith('payag') || lower.includes('payag')) return 'Payags';
        if (lower.startsWith('cottage') || lower.includes('cottage')) return 'Cottages';
        if (lower.includes('function') || lower.includes('hall')) return 'Function Halls';
        if (lower.includes('room') || lower.includes('suite')) return 'Rooms';

        const words = trimmed.split(/[\s\-_0-9]/).filter(Boolean);
        if (words.length > 0 && words[0].length > 1) {
            const base = words[0].charAt(0).toUpperCase() + words[0].slice(1).toLowerCase();
            return base.endsWith('s') ? base : `${base}s`;
        }
        return 'Other Amenities';
    };

    const syncStatusFilterPills = () => {
        const pills = document.querySelectorAll('#walkInAmenityStatusFilters [data-filter-status]');
        pills.forEach(pill => {
            const st = pill.dataset.filterStatus;
            if (st === amenityStatusFilter) {
                pill.className = 'walkin-filter-pill is-active cursor-pointer rounded-lg px-3 py-1.5 text-xs font-bold transition-all bg-hp-green text-white shadow-xs';
            } else {
                pill.className = 'walkin-filter-pill cursor-pointer rounded-lg px-3 py-1.5 text-xs font-semibold text-hp-text transition-all bg-transparent hover:bg-black/5 dark:hover:bg-white/10';
            }
        });
    };

    // Render Categorized Amenities inside Choose Amenities Modal
    const renderCategorizedAmenities = () => {
        const container = document.getElementById('amenitiesContainer');
        if (!container) return;
        const prevScrollTop = container.scrollTop;

        const counts = calculateWalkInSlots(walkInSchedule.startDate, walkInSchedule.endDate, walkInSchedule.startSlot, walkInSchedule.endSlot);
        const spanLabel = counts.daysSpan === 1 ? '1 Day' : `${counts.daysSpan} Days`;

        // Filter list based on active filters
        let filtered = cachedAmenityList;
        if (amenityStatusFilter === 'available') {
            filtered = filtered.filter(a => a.is_available);
        } else if (amenityStatusFilter === 'occupied') {
            filtered = filtered.filter(a => !a.is_available);
        }

        if (amenityCategoryFilter !== 'all') {
            filtered = filtered.filter(a => categorizeAmenity(a.amenities_name) === amenityCategoryFilter);
        }

        // Update footer summary text
        if (walkInAmenityFilterSummaryText) {
            const statusLabel = amenityStatusFilter === 'available' ? 'available' : (amenityStatusFilter === 'occupied' ? 'occupied' : 'total');
            const catLabel = amenityCategoryFilter === 'all' ? 'all categories' : amenityCategoryFilter;
            walkInAmenityFilterSummaryText.textContent = `Showing ${filtered.length} ${statusLabel} in ${catLabel}`;
        }

        // Empty state
        if (filtered.length === 0) {
            container.innerHTML = `
                <div class="py-12 text-center text-hp-text-muted">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-hp-green/10 text-xl text-hp-green">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <div class="text-sm font-bold text-hp-text dark:text-[#f3f4f6]">No amenities found</div>
                    <p class="text-xs text-hp-text-muted mt-1 max-w-[340px] mx-auto">No amenities match "${amenityStatusFilter}" in "${amenityCategoryFilter === 'all' ? 'All Categories' : amenityCategoryFilter}".</p>
                    <button type="button" class="mt-3.5 cursor-pointer rounded-xl border border-hp-green bg-hp-green/10 px-4 py-2 text-xs font-bold text-hp-green hover:bg-hp-green hover:text-white transition-all" id="walkInResetAmenityFiltersBtn">
                        Show All Available Amenities
                    </button>
                </div>
            `;
            const resetBtn = document.getElementById('walkInResetAmenityFiltersBtn');
            resetBtn?.addEventListener('click', () => {
                amenityStatusFilter = 'available';
                amenityCategoryFilter = 'all';
                if (walkInAmenityCategorySelect) walkInAmenityCategorySelect.value = 'all';
                syncStatusFilterPills();
                renderCategorizedAmenities();
            });
            return;
        }

        // Group by category
        const groups = {};
        filtered.forEach(amenity => {
            const cat = categorizeAmenity(amenity.amenities_name);
            if (!groups[cat]) groups[cat] = [];
            groups[cat].push(amenity);
        });

        // Preferred order of categories: A-Houses, Payags, Cottages, Rooms, Function Halls, Others
        const preferredOrder = ['A-Houses', 'Payags', 'Cottages', 'Rooms', 'Function Halls'];
        const sortedCategories = Object.keys(groups).sort((a, b) => {
            const idxA = preferredOrder.indexOf(a);
            const idxB = preferredOrder.indexOf(b);
            if (idxA !== -1 && idxB !== -1) return idxA - idxB;
            if (idxA !== -1) return -1;
            if (idxB !== -1) return 1;
            return a.localeCompare(b);
        });

        container.innerHTML = '';

        sortedCategories.forEach(cat => {
            const items = groups[cat];
            const groupWrap = document.createElement('div');
            groupWrap.className = 'amenity-category-section space-y-2.5';

            // Category Header
            const availableCount = items.filter(a => a.is_available).length;
            const header = document.createElement('div');
            header.className = 'flex items-center justify-between pb-1.5 border-b border-glass-border/60 sticky top-0 bg-hp-cream/90 dark:bg-[rgba(30,30,30,0.95)] backdrop-blur-sm z-10';
            header.innerHTML = `
                <div class="flex items-center gap-2">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-hp-green dark:text-emerald-400">${cat}</span>
                    <span class="rounded-full bg-hp-green/15 px-2 py-0.2 text-[0.68rem] font-bold text-hp-green dark:text-emerald-400">${items.length}</span>
                </div>
                <span class="text-[0.7rem] text-hp-text-muted">${availableCount} available</span>
            `;
            groupWrap.appendChild(header);

            // Cards Grid
            const cardsGrid = document.createElement('div');
            cardsGrid.className = 'grid gap-2.5';

            items.forEach(amenity => {
                const isAvailable = Boolean(amenity.is_available);
                const isAlreadySelected = selectedAmenities.some(a => String(a.amenity_id) === String(amenity.id));
                const hasFreeEntrance = Boolean(amenity.free_entrance || amenity.benefits?.free_entrance);
                const hasFreePool = Boolean(amenity.free_pool || amenity.benefits?.free_pool);

                const dayP = parseFloat(amenity.daytime_price) || 0;
                const nightP = parseFloat(amenity.nighttime_price) || 0;
                const calculatedPrice = (counts.dayCount * dayP) + (counts.nightCount * nightP);

                const card = document.createElement('div');
                card.className = `walkin-amenity-card flex flex-wrap items-center justify-between gap-3 rounded-xl border p-3.5 transition-all duration-200 ${isAvailable
                    ? (isAlreadySelected ? 'border-hp-green/60 bg-hp-green/10 shadow-xs' : 'border-glass-border bg-glass hover:border-hp-green/60 hover:bg-white/60 dark:hover:bg-white/5')
                    : 'border-red-300/40 bg-red-50/25 opacity-70 dark:border-red-500/20 dark:bg-red-500/5'
                    }`;
                card.dataset.amenityId = amenity.id;

                card.innerHTML = `
                    <div class="flex-1 min-w-[200px]">
                        <div class="flex items-center gap-2 flex-wrap">
                            <strong class="text-sm font-bold text-hp-text dark:text-[#f3f4f6]">${amenity.amenities_name}</strong>
                            ${isAvailable
                        ? '<span class="rounded bg-emerald-500/10 px-2 py-0.5 text-[0.68rem] font-bold text-emerald-600 dark:text-emerald-400">Available</span>'
                        : '<span class="rounded bg-red-500/10 px-2 py-0.5 text-[0.68rem] font-bold text-red-600 dark:text-red-400">Occupied</span>'
                    }
                            ${hasFreeEntrance ? '<span class="rounded bg-amber-500/10 px-1.5 py-0.5 text-[0.65rem] font-bold text-amber-700 dark:text-amber-300"><i class="bi bi-ticket-perforated-fill me-1"></i>Free Entrance</span>' : ''}
                            ${hasFreePool ? '<span class="rounded bg-sky-500/10 px-1.5 py-0.5 text-[0.65rem] font-bold text-sky-700 dark:text-sky-300"><i class="bi bi-water me-1"></i>Free Pool</span>' : ''}
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-hp-text-muted">
                            <span>Capacity: ${amenity.minimum_capacity || 1}–${amenity.maximum_capacity || 10} guests</span>
                            <span>•</span>
                            <span>Day: ₱${formatPeso(dayP)}</span>
                            <span>•</span>
                            <span>Night: ₱${formatPeso(nightP)}</span>
                        </div>
                        ${isAvailable ? `
                            <div class="mt-1 text-xs font-semibold text-hp-green dark:text-emerald-400">
                                Stay Total: ₱${formatPeso(calculatedPrice)} <span class="font-normal text-hp-text-muted">(${spanLabel} • ${counts.dayCount}D ${counts.nightCount}N)</span>
                            </div>
                        ` : `
                            <div class="mt-1 text-xs text-red-500/80 font-medium">
                                Booked for this stay window
                            </div>
                        `}
                    </div>
                    <div>
                        ${isAlreadySelected ? `
                            <button type="button" class="walkin-remove-modal-amenity-btn cursor-pointer rounded-xl border border-hp-green bg-hp-green px-4 py-2 text-xs font-bold text-white shadow-xs flex items-center gap-1.5 transition-all duration-150 hover:bg-red-600 hover:border-red-600 group" data-amenity-id="${amenity.id}" title="Click to remove from selection">
                                <i class="bi bi-check-lg group-hover:hidden"></i>
                                <i class="bi bi-x-lg hidden group-hover:inline-block"></i>
                                <span class="group-hover:hidden">Added</span>
                                <span class="hidden group-hover:inline">Remove</span>
                            </button>
                        ` : (isAvailable ? `
                            <button type="button" class="walkin-add-amenity-btn cursor-pointer rounded-xl border border-hp-green bg-hp-green/10 px-4 py-2 text-xs font-bold text-hp-green transition-all duration-150 hover:bg-hp-green hover:text-white shadow-xs" data-amenity-id="${amenity.id}">
                                + Add Amenity
                            </button>
                        ` : `
                            <button type="button" class="rounded-xl border border-glass-border bg-glass px-3.5 py-1.5 text-xs font-semibold text-hp-text-muted cursor-not-allowed opacity-60" disabled>
                                Unavailable
                            </button>
                        `)}
                    </div>
                `;

                cardsGrid.appendChild(card);
            });

            groupWrap.appendChild(cardsGrid);
            container.appendChild(groupWrap);
        });

        if (prevScrollTop) {
            container.scrollTop = prevScrollTop;
        }
    };

    const loadAvailableAmenitiesForStay = async () => {
        const container = document.getElementById('amenitiesContainer');
        if (!container) return;

        const counts = calculateWalkInSlots(walkInSchedule.startDate, walkInSchedule.endDate, walkInSchedule.startSlot, walkInSchedule.endSlot);
        const sFmt = formatDisplayDate(walkInSchedule.startDate);
        const eFmt = formatDisplayDate(walkInSchedule.endDate);
        const spanLabel = counts.daysSpan === 1 ? '1 Day' : `${counts.daysSpan} Days`;

        if (amenityModalStayBadge) {
            if (walkInSchedule.startDate === walkInSchedule.endDate) {
                amenityModalStayBadge.textContent = `${sFmt} (${walkInSchedule.startSlot}) • ${spanLabel}`;
            } else {
                amenityModalStayBadge.textContent = `${sFmt} (${walkInSchedule.startSlot}) → ${eFmt} (${walkInSchedule.endSlot}) • ${spanLabel}`;
            }
        }

        container.innerHTML = `
            <div class="flex items-center justify-center py-8 text-sm text-hp-text-muted">
                <svg class="mr-2 h-5 w-5 animate-spin text-hp-green" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                Checking amenity availability for selected stay...
            </div>
        `;

        try {
            const params = new URLSearchParams({
                start_date: walkInSchedule.startDate,
                end_date: walkInSchedule.endDate,
                start_slot: walkInSchedule.startSlot,
                end_slot: walkInSchedule.endSlot,
            });

            const res = await fetch(`/api/amenities/availability?${params.toString()}`);
            if (!res.ok) throw new Error('Failed to fetch availability');
            const data = await res.json();
            cachedAmenityList = data.amenities || [];

            // Update Counts on pills
            const availableCount = cachedAmenityList.filter(a => a.is_available).length;
            const occupiedCount = cachedAmenityList.filter(a => !a.is_available).length;
            const allCount = cachedAmenityList.length;

            const countAvailEl = document.getElementById('walkInCountAvailable');
            const countOccEl = document.getElementById('walkInCountOccupied');
            const countAllEl = document.getElementById('walkInCountAll');
            if (countAvailEl) countAvailEl.textContent = availableCount;
            if (countOccEl) countOccEl.textContent = occupiedCount;
            if (countAllEl) countAllEl.textContent = allCount;

            // Populate category dropdown
            if (walkInAmenityCategorySelect) {
                const uniqueCategories = Array.from(new Set(cachedAmenityList.map(a => categorizeAmenity(a.amenities_name)))).sort();
                const currentVal = walkInAmenityCategorySelect.value;
                walkInAmenityCategorySelect.innerHTML = '<option value="all">All Categories</option>';
                uniqueCategories.forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat;
                    opt.textContent = cat;
                    walkInAmenityCategorySelect.appendChild(opt);
                });
                if (uniqueCategories.includes(currentVal)) {
                    walkInAmenityCategorySelect.value = currentVal;
                    amenityCategoryFilter = currentVal;
                } else {
                    walkInAmenityCategorySelect.value = 'all';
                    amenityCategoryFilter = 'all';
                }
            }

            syncStatusFilterPills();
            renderCategorizedAmenities();
        } catch (err) {
            console.error('Failed to load available amenities:', err);
            container.innerHTML = '<p class="guest-empty px-4 py-8 text-center text-red-500">Failed to load amenity availability. Please close and retry.</p>';
        }
    };

    // Filter listeners
    walkInAmenityStatusFilters?.addEventListener('click', (e) => {
        const pill = e.target.closest('[data-filter-status]');
        if (pill) {
            amenityStatusFilter = pill.dataset.filterStatus;
            syncStatusFilterPills();
            renderCategorizedAmenities();
        }
    });

    walkInAmenityCategorySelect?.addEventListener('change', (e) => {
        amenityCategoryFilter = e.target.value;
        renderCategorizedAmenities();
    });

    const openAmenityModal = () => {
        if (!amenityModal) return;
        amenityModal.classList.add('is-open');
        amenityModal.setAttribute('aria-hidden', 'false');
        loadAvailableAmenitiesForStay();
    };

    const closeAmenityModal = () => {
        if (!amenityModal) return;
        amenityModal.classList.remove('is-open');
        amenityModal.setAttribute('aria-hidden', 'true');
    };

    chooseAmenitiesBtn?.addEventListener('click', openAmenityModal);
    amenityCloseButtons.forEach(btn => btn.addEventListener('click', closeAmenityModal));

    // Handle "+ Add Amenity" and "Added (Remove)" click from Choose Amenities Modal
    document.addEventListener('click', (e) => {
        const removeModalBtn = e.target.closest('.walkin-remove-modal-amenity-btn');
        if (removeModalBtn) {
            if (removeModalBtn.disabled) return;
            removeModalBtn.disabled = true;
            const amenityId = removeModalBtn.dataset.amenityId;
            const idx = selectedAmenities.findIndex(a => String(a.amenity_id) === String(amenityId));
            if (idx !== -1) {
                const removedName = selectedAmenities[idx].amenity_name;
                selectedAmenities.splice(idx, 1);
                renderSelectedAmenities();
                updateGrandTotal();
                renderCategorizedAmenities();
                showToast(`Removed ${removedName} from walk-in.`);
            } else {
                removeModalBtn.disabled = false;
            }
            return;
        }

        const addBtn = e.target.closest('.walkin-add-amenity-btn');
        if (addBtn) {
            if (addBtn.disabled) return;
            addBtn.disabled = true;
            const amenityId = addBtn.dataset.amenityId;
            const allAmenities = window.ALL_AMENITIES || [];
            let amenity = allAmenities.find(a => String(a.id) === String(amenityId));
            if (!amenity && cachedAmenityList) {
                amenity = cachedAmenityList.find(a => String(a.id) === String(amenityId));
            }
            if (!amenity) {
                addBtn.disabled = false;
                return;
            }

            // Check if already added
            if (selectedAmenities.some(a => String(a.amenity_id) === String(amenityId))) {
                showToast(`${amenity.amenities_name} is already added to this reservation.`, 'error');
                addBtn.disabled = false;
                return;
            }

            const counts = calculateWalkInSlots(walkInSchedule.startDate, walkInSchedule.endDate, walkInSchedule.startSlot, walkInSchedule.endSlot);
            const dayPrice = parseFloat(amenity.daytime_price) || 0;
            const nightPrice = parseFloat(amenity.nighttime_price) || 0;
            const price = (counts.dayCount * dayPrice) + (counts.nightCount * nightPrice);
            const freeEntrance = Boolean(amenity.free_entrance || amenity.benefits?.free_entrance);
            const freePool = Boolean(amenity.free_pool || amenity.benefits?.free_pool);

            selectedAmenities.push({
                amenity_id: amenity.id,
                amenity_name: amenity.amenities_name,
                min_cap: amenity.minimum_capacity,
                max_cap: amenity.maximum_capacity,
                additional_per_head: parseFloat(amenity.additional_per_head) || 0,
                start_date: walkInSchedule.startDate,
                end_date: walkInSchedule.endDate,
                start_slot: walkInSchedule.startSlot,
                end_slot: walkInSchedule.endSlot,
                is_aircon: false,
                free_entrance: freeEntrance,
                free_pool: freePool,
                quantity: 1,
                day_count: counts.dayCount,
                night_count: counts.nightCount,
                total_days: counts.daysSpan,
                price_at_booking: price,
                daytime_price: dayPrice,
                nighttime_price: nightPrice,
                daytime_aircon_price: amenity.daytime_aircon_price !== null ? parseFloat(amenity.daytime_aircon_price) : null,
                nighttime_aircon_price: amenity.nighttime_aircon_price !== null ? parseFloat(amenity.nighttime_aircon_price) : null,
            });

            renderSelectedAmenities();
            updateGrandTotal();
            renderCategorizedAmenities();
            showToast(`Added ${amenity.amenities_name} to walk-in.`);
        }
    }, { signal: pageSignal });

    // Render Selected Amenities Cards
    const renderSelectedAmenities = () => {
        if (!selectedAmenitiesContainer || !amenitiesHiddenInputs) return;

        selectedAmenitiesContainer.innerHTML = '';
        amenitiesHiddenInputs.innerHTML = '';

        const sidebarAmBadge = document.getElementById('walkInSidebarAmenitiesBadge');
        if (sidebarAmBadge) {
            sidebarAmBadge.textContent = selectedAmenities.length;
        }

        if (selectedAmenities.length === 0) {
            if (noAmenitiesNotice) noAmenitiesNotice.style.display = 'block';
            selectedAmenitiesContainer.appendChild(noAmenitiesNotice);
            syncWalkInAmenityDropdowns();
            syncMainGuestCardUI();
            updateGrandTotal();
            return;
        }

        if (noAmenitiesNotice) noAmenitiesNotice.style.display = 'none';

        selectedAmenities.forEach((am, index) => {
            const sFmt = formatDisplayDate(am.start_date);
            const eFmt = formatDisplayDate(am.end_date);
            const spanLabel = am.total_days === 1 ? '1 Day' : `${am.total_days} Days`;
            const maxCapText = (am.max_cap !== null && am.max_cap !== undefined && am.max_cap !== '')
                ? `Max ${am.max_cap} guests${parseFloat(am.additional_per_head) > 0 ? ` (+₱${formatPeso(am.additional_per_head)}/extra head)` : ''}`
                : 'No limit';

            const card = document.createElement('div');
            card.className = 'selected-amenity-card rounded-xl border border-glass-border bg-glass p-3.5 shadow-sm transition-all duration-200';
            card.dataset.index = index;

            card.innerHTML = `
                <div class="flex flex-wrap items-start justify-between gap-2 border-b border-glass-border/50 pb-2.5">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <strong class="text-sm font-bold text-hp-text dark:text-[#f3f4f6]">${am.amenity_name}</strong>
                            ${am.free_entrance ? '<span class="rounded bg-amber-500/10 px-1.5 py-0.5 text-[0.65rem] font-bold text-amber-700 dark:text-amber-300"><i class="bi bi-ticket-perforated-fill me-1"></i>Free Entrance</span>' : ''}
                            ${am.free_pool ? '<span class="rounded bg-sky-500/10 px-1.5 py-0.5 text-[0.65rem] font-bold text-sky-700 dark:text-sky-300"><i class="bi bi-water me-1"></i>Free Pool</span>' : ''}
                        </div>
                        <div class="text-xs text-hp-text-muted">Capacity: ${maxCapText}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-extrabold text-hp-green">₱${formatPeso(am.price_at_booking)}</div>
                        <button type="button" class="walkin-remove-amenity-btn text-[0.75rem] font-bold text-red-500 hover:text-red-700 transition-colors cursor-pointer" data-index="${index}">
                            Remove
                        </button>
                    </div>
                </div>

                <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2 text-xs">
                    <div class="flex items-center gap-1.5 rounded-lg border border-glass-border bg-hp-cream/60 px-2.5 py-1 text-hp-text dark:bg-white/5">
                        <svg class="h-3.5 w-3.5 text-hp-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="font-semibold">${sFmt} (${am.start_slot}) → ${eFmt} (${am.end_slot}) • ${spanLabel} (${am.day_count}D ${am.night_count}N)</span>
                    </div>
                </div>
            `;

            selectedAmenitiesContainer.appendChild(card);

            // Hidden inputs for backend submission
            const pricingTypeStr = am.total_days > 1
                ? `Continuous Stay (${am.total_days}D)`
                : ((am.start_slot === 'Daytime' && am.end_slot === 'Nighttime') ? 'DayToNight' : am.start_slot);

            amenitiesHiddenInputs.insertAdjacentHTML('beforeend', `
                <input type="hidden" name="selected_amenities[${index}][amenity_id]" value="${am.amenity_id}">
                <input type="hidden" name="selected_amenities[${index}][start_date]" value="${am.start_date}">
                <input type="hidden" name="selected_amenities[${index}][end_date]" value="${am.end_date}">
                <input type="hidden" name="selected_amenities[${index}][start_slot]" value="${am.start_slot}">
                <input type="hidden" name="selected_amenities[${index}][end_slot]" value="${am.end_slot}">
                <input type="hidden" name="selected_amenities[${index}][pricing_type]" value="${pricingTypeStr}">
                <input type="hidden" name="selected_amenities[${index}][price_at_booking]" value="${am.price_at_booking}">
                <input type="hidden" name="selected_amenities[${index}][is_aircon]" value="0">
                <input type="hidden" name="selected_amenities[${index}][quantity]" value="${am.quantity || 1}">
            `);
        });

        syncWalkInAmenityDropdowns();
        syncMainGuestCardUI();
        updateGrandTotal();
    };

    // Remove amenity card click
    selectedAmenitiesContainer?.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('.walkin-remove-amenity-btn');
        if (removeBtn) {
            const idx = parseInt(removeBtn.dataset.index);
            if (!isNaN(idx)) {
                selectedAmenities.splice(idx, 1);
                renderSelectedAmenities();
                updateGrandTotal();
                renderCategorizedAmenities();
            }
        }
    });

    // Aircon toggle from card
    selectedAmenitiesContainer?.addEventListener('change', (e) => {
        if (e.target.classList.contains('walkin-card-ac-toggle')) {
            const idx = parseInt(e.target.dataset.index);
            if (!isNaN(idx) && selectedAmenities[idx]) {
                selectedAmenities[idx].is_aircon = e.target.checked;
                const am = selectedAmenities[idx];
                const dayP = am.is_aircon && am.daytime_aircon_price ? am.daytime_aircon_price : am.daytime_price;
                const nightP = am.is_aircon && am.nighttime_aircon_price ? am.nighttime_aircon_price : am.nighttime_price;
                am.price_at_booking = (am.day_count * dayP) + (am.night_count * nightP);
                renderSelectedAmenities();
                updateGrandTotal();
            }
        }
    });

    // ==========================================
    // Walk-In Per-Amenity Schedule Customizer Modal
    // ==========================================
    const walkInAmenityScheduleModal = document.getElementById('walkInAmenityScheduleModal');
    const walkInAmenityScheduleTitle = document.getElementById('walkInAmenityScheduleTitle');
    const walkInAmenityScheduleAmenityId = document.getElementById('walkInAmenityScheduleAmenityId');
    const walkInAmenityScheduleRangeText = document.getElementById('walkInAmenityScheduleRangeText');
    const walkInAmenityStartDate = document.getElementById('walkInAmenityStartDate');
    const walkInAmenityStartSlot = document.getElementById('walkInAmenityStartSlot');
    const walkInAmenityEndDate = document.getElementById('walkInAmenityEndDate');
    const walkInAmenityEndSlot = document.getElementById('walkInAmenityEndSlot');
    const walkInAmenityAirconWrap = document.getElementById('walkInAmenityAirconWrap');
    const walkInAmenityAirconToggle = document.getElementById('walkInAmenityAirconToggle');
    const walkInAmenityAirconDiff = document.getElementById('walkInAmenityAirconDiff');
    const walkInAmenityDurationText = document.getElementById('walkInAmenityDurationText');
    const walkInAmenityMathText = document.getElementById('walkInAmenityMathText');
    const walkInAmenityTotalPrice = document.getElementById('walkInAmenityTotalPrice');
    const walkInAmenitySaveScheduleBtn = document.getElementById('walkInAmenitySaveScheduleBtn');
    const walkInAmenityScheduleCloseBtns = document.querySelectorAll('[data-close-walkin-amenity-schedule="true"]');

    let currentEditingAmenityIndex = null;

    const openAmenityScheduleModal = (index) => {
        if (!walkInAmenityScheduleModal || !selectedAmenities[index]) return;
        currentEditingAmenityIndex = index;
        const am = selectedAmenities[index];

        if (walkInAmenityScheduleTitle) walkInAmenityScheduleTitle.textContent = `Customize ${am.amenity_name} Stay`;
        if (walkInAmenityScheduleAmenityId) walkInAmenityScheduleAmenityId.value = am.amenity_id;

        const sFmt = formatDisplayDate(walkInSchedule.startDate);
        const eFmt = formatDisplayDate(walkInSchedule.endDate);
        if (walkInAmenityScheduleRangeText) {
            walkInAmenityScheduleRangeText.textContent = `${sFmt} (${walkInSchedule.startSlot}) to ${eFmt} (${walkInSchedule.endSlot})`;
        }

        // Set min/max constraints
        if (walkInAmenityStartDate) {
            walkInAmenityStartDate.min = walkInSchedule.startDate;
            walkInAmenityStartDate.max = walkInSchedule.endDate;
            walkInAmenityStartDate.value = am.start_date;
        }
        if (walkInAmenityEndDate) {
            walkInAmenityEndDate.min = am.start_date;
            walkInAmenityEndDate.max = walkInSchedule.endDate;
            walkInAmenityEndDate.value = am.end_date;
        }
        if (walkInAmenityStartSlot) walkInAmenityStartSlot.value = am.start_slot;
        if (walkInAmenityEndSlot) walkInAmenityEndSlot.value = am.end_slot;

        // Aircon wrap visibility
        const hasAc = am.daytime_aircon_price !== null || am.nighttime_aircon_price !== null;
        if (walkInAmenityAirconWrap) {
            walkInAmenityAirconWrap.style.display = hasAc ? 'flex' : 'none';
        }
        if (walkInAmenityAirconToggle) {
            walkInAmenityAirconToggle.checked = Boolean(am.is_aircon);
        }

        enforceWalkInAmenityModalConstraints();

        walkInAmenityScheduleModal.classList.add('is-open');
        walkInAmenityScheduleModal.setAttribute('aria-hidden', 'false');
    };

    const enforceWalkInAmenityModalConstraints = () => {
        if (currentEditingAmenityIndex === null || !selectedAmenities[currentEditingAmenityIndex]) return;
        const am = selectedAmenities[currentEditingAmenityIndex];

        const bStart = walkInSchedule.startDate;
        const bEnd = walkInSchedule.endDate;
        const bStartSlot = walkInSchedule.startSlot;
        const bEndSlot = walkInSchedule.endSlot;

        // Start Date clamping
        if (walkInAmenityStartDate) {
            walkInAmenityStartDate.min = bStart;
            walkInAmenityStartDate.max = bEnd;
            if (walkInAmenityStartDate.value < bStart) walkInAmenityStartDate.value = bStart;
            if (walkInAmenityStartDate.value > bEnd) walkInAmenityStartDate.value = bEnd;
        }

        const curStart = walkInAmenityStartDate?.value || bStart;

        // End Date clamping
        if (walkInAmenityEndDate) {
            walkInAmenityEndDate.min = curStart;
            walkInAmenityEndDate.max = bEnd;
            if (walkInAmenityEndDate.value < curStart) walkInAmenityEndDate.value = curStart;
            if (walkInAmenityEndDate.value > bEnd) walkInAmenityEndDate.value = bEnd;
        }

        const curEnd = walkInAmenityEndDate?.value || curStart;

        // Session slots constraints
        if (walkInAmenityStartSlot) {
            const dtOpt = walkInAmenityStartSlot.querySelector('option[value="Daytime"]');
            const allowDayStart = !(curStart === bStart && bStartSlot === 'Nighttime');
            if (dtOpt) dtOpt.disabled = !allowDayStart;
            if (!allowDayStart && walkInAmenityStartSlot.value === 'Daytime') {
                walkInAmenityStartSlot.value = 'Nighttime';
            }
        }

        const curStartSlot = walkInAmenityStartSlot?.value || 'Daytime';

        if (walkInAmenityEndSlot) {
            const ntOpt = walkInAmenityEndSlot.querySelector('option[value="Nighttime"]');
            const dtOpt = walkInAmenityEndSlot.querySelector('option[value="Daytime"]');

            const allowNightEnd = !(curEnd === bEnd && bEndSlot === 'Daytime');
            const isSameDayNightStart = (curStart === curEnd && curStartSlot === 'Nighttime');

            if (ntOpt) ntOpt.disabled = !allowNightEnd;
            if (dtOpt) dtOpt.disabled = isSameDayNightStart;

            if (!allowNightEnd && walkInAmenityEndSlot.value === 'Nighttime') {
                walkInAmenityEndSlot.value = 'Daytime';
            }
            if (isSameDayNightStart && walkInAmenityEndSlot.value === 'Daytime') {
                walkInAmenityEndSlot.value = 'Nighttime';
            }
        }

        const curEndSlot = walkInAmenityEndSlot?.value || 'Daytime';
        const isAircon = Boolean(walkInAmenityAirconToggle?.checked);

        // Recalculate duration and price
        const counts = calculateWalkInSlots(curStart, curEnd, curStartSlot, curEndSlot);
        const dayP = isAircon && am.daytime_aircon_price ? am.daytime_aircon_price : am.daytime_price;
        const nightP = isAircon && am.nighttime_aircon_price ? am.nighttime_aircon_price : am.nighttime_price;
        const totalP = (counts.dayCount * dayP) + (counts.nightCount * nightP);

        if (walkInAmenityDurationText) {
            const spanLabel = counts.daysSpan === 1 ? '1 Day' : `${counts.daysSpan} Days`;
            walkInAmenityDurationText.textContent = `${spanLabel} (${counts.dayCount}D ${counts.nightCount}N)`;
        }
        if (walkInAmenityMathText) {
            const parts = [];
            if (counts.dayCount > 0) parts.push(`${counts.dayCount}D × ₱${formatPeso(dayP)}`);
            if (counts.nightCount > 0) parts.push(`${counts.nightCount}N × ₱${formatPeso(nightP)}`);
            walkInAmenityMathText.textContent = parts.join(' + ') || '0 slots';
        }
        if (walkInAmenityTotalPrice) {
            walkInAmenityTotalPrice.textContent = `₱${formatPeso(totalP)}`;
        }
    };

    walkInAmenityStartDate?.addEventListener('change', enforceWalkInAmenityModalConstraints);
    walkInAmenityEndDate?.addEventListener('change', enforceWalkInAmenityModalConstraints);
    walkInAmenityStartSlot?.addEventListener('change', enforceWalkInAmenityModalConstraints);
    walkInAmenityEndSlot?.addEventListener('change', enforceWalkInAmenityModalConstraints);
    walkInAmenityAirconToggle?.addEventListener('change', enforceWalkInAmenityModalConstraints);

    walkInAmenitySaveScheduleBtn?.addEventListener('click', () => {
        if (currentEditingAmenityIndex === null || !selectedAmenities[currentEditingAmenityIndex]) return;
        const am = selectedAmenities[currentEditingAmenityIndex];

        enforceWalkInAmenityModalConstraints();

        const sDate = walkInAmenityStartDate.value;
        const eDate = walkInAmenityEndDate.value;
        const sSlot = walkInAmenityStartSlot.value;
        const eSlot = walkInAmenityEndSlot.value;
        const isAc = Boolean(walkInAmenityAirconToggle.checked);

        const counts = calculateWalkInSlots(sDate, eDate, sSlot, eSlot);
        const dayP = isAc && am.daytime_aircon_price ? am.daytime_aircon_price : am.daytime_price;
        const nightP = isAc && am.nighttime_aircon_price ? am.nighttime_aircon_price : am.nighttime_price;

        am.start_date = sDate;
        am.end_date = eDate;
        am.start_slot = sSlot;
        am.end_slot = eSlot;
        am.is_aircon = isAc;
        am.total_days = counts.daysSpan;
        am.day_count = counts.dayCount;
        am.night_count = counts.nightCount;
        am.price_at_booking = (counts.dayCount * dayP) + (counts.nightCount * nightP);

        renderSelectedAmenities();
        updateGrandTotal();
        closeAmenityScheduleModal();
    });

    // ── Reservation table filters & sorting (Reservation Data View tab) ──────────────
    const resvFilterToggle = document.getElementById('resvFilterToggle');
    const resvFilterChevron = document.getElementById('resvFilterChevron');
    const resvFilterPanel = document.getElementById('resvFilterPanel');
    const resvFilterCloseBtn = document.getElementById('resvFilterCloseBtn');
    const resvPanelResetBtn = document.getElementById('resvPanelResetBtn');
    const resvQuickSortPill = document.getElementById('resvQuickSortPill');
    const resvCurrentSortLabel = document.getElementById('resvCurrentSortLabel');
    const resvSortDropdownContainer = document.getElementById('resvSortDropdownContainer');
    const resvSortDropdownMenu = document.getElementById('resvSortDropdownMenu');
    const resvSortDropdownChevron = document.getElementById('resvSortDropdownChevron');
    const resvSortOptions = document.querySelectorAll('.resv-sort-option');
    const resvSearchInput = document.getElementById('resvSearchInput');
    const resvTypeFilter = document.getElementById('resvTypeFilter');
    const resvStatusFilter = document.getElementById('resvStatusFilter');
    const resvAmenityFilter = document.getElementById('resvAmenityFilter');
    const resvSortSelect = document.getElementById('resvSortSelect');
    const resvDateFrom = document.getElementById('resvDateFrom');
    const resvDateTo = document.getElementById('resvDateTo');
    const resvFiltersClear = document.getElementById('resvFiltersClear');
    const resvResultsCount = document.getElementById('resvResultsCount');
    const resvActiveFilterCount = document.getElementById('resvActiveFilterCount');
    const resvQuickFilters = document.querySelectorAll('.resv-quick-filter');
    const resvAmenityFilters = document.querySelectorAll('.resv-amenity-filter');

    const openFilterPanel = () => {
        closeSortDropdown();
        if (resvFilterPanel) {
            resvFilterPanel.hidden = false;
            resvFilterPanel.removeAttribute('hidden');
            resvFilterPanel.classList.remove('guest-toolbar--collapsed');
        }
        if (resvFilterToggle) {
            resvFilterToggle.setAttribute('aria-expanded', 'true');
        }
        if (resvFilterChevron) {
            resvFilterChevron.classList.add('rotate-180');
        }
    };

    const closeFilterPanel = () => {
        // Keep filter panel permanently open
        if (resvFilterPanel) {
            resvFilterPanel.hidden = false;
            resvFilterPanel.removeAttribute('hidden');
            resvFilterPanel.classList.remove('guest-toolbar--collapsed');
        }
        if (resvFilterToggle) {
            resvFilterToggle.setAttribute('aria-expanded', 'true');
        }
        if (resvFilterChevron) {
            resvFilterChevron.classList.add('rotate-180');
        }
    };

    // ── Dedicated Sort Dropdown Handlers ─────────────────────────────
    const openSortDropdown = () => {
        if (resvSortDropdownMenu) {
            resvSortDropdownMenu.classList.remove('hidden');
        }
        if (resvQuickSortPill) {
            resvQuickSortPill.setAttribute('aria-expanded', 'true');
        }
        if (resvSortDropdownChevron) {
            resvSortDropdownChevron.classList.add('rotate-180');
        }
        updateSortDropdownActiveOption();
        const activeOption = resvSortDropdownMenu?.querySelector('.resv-sort-option.font-bold');
        if (activeOption) {
            activeOption.scrollIntoView({ block: 'nearest' });
        }
    };

    const closeSortDropdown = () => {
        if (resvSortDropdownMenu) {
            resvSortDropdownMenu.classList.add('hidden');
        }
        if (resvQuickSortPill) {
            resvQuickSortPill.setAttribute('aria-expanded', 'false');
        }
        if (resvSortDropdownChevron) {
            resvSortDropdownChevron.classList.remove('rotate-180');
        }
    };

    const toggleSortDropdown = () => {
        const isOpened = resvSortDropdownMenu && !resvSortDropdownMenu.classList.contains('hidden');
        if (isOpened) {
            closeSortDropdown();
        } else {
            closeFilterPanel();
            openSortDropdown();
        }
    };

    const updateSortDropdownActiveOption = () => {
        const currentSort = resvSortSelect?.value || 'nearest_checkout';
        resvSortOptions.forEach((opt) => {
            const isMatch = opt.getAttribute('data-sort-val') === currentSort;
            const checkIcon = opt.querySelector('.sort-check');
            if (isMatch) {
                opt.classList.add('bg-emerald-50', 'dark:bg-emerald-950/40', 'text-emerald-700', 'dark:text-emerald-300', 'font-bold');
                opt.classList.remove('font-medium');
                checkIcon?.classList.remove('hidden');
            } else {
                opt.classList.remove('bg-emerald-50', 'dark:bg-emerald-950/40', 'text-emerald-700', 'dark:text-emerald-300', 'font-bold');
                opt.classList.add('font-medium');
                checkIcon?.classList.add('hidden');
            }
        });
    };

    resvQuickSortPill?.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleSortDropdown();
    });

    resvSortOptions.forEach((optBtn) => {
        optBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const chosenSort = optBtn.getAttribute('data-sort-val');
            if (chosenSort && resvSortSelect) {
                resvSortSelect.value = chosenSort;
                applyResvFilters();
            }
            closeSortDropdown();
        });
    });

    document.addEventListener('click', (e) => {
        if (resvSortDropdownContainer && !resvSortDropdownContainer.contains(e.target)) {
            closeSortDropdown();
        }
    }, { signal: pageSignal });

    resvFilterToggle?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        closeSortDropdown();
        const isExpanded = resvFilterToggle.getAttribute('aria-expanded') === 'true';
        if (isExpanded) {
            closeFilterPanel();
        } else {
            openFilterPanel();
        }
    });

    resvFilterCloseBtn?.addEventListener('click', () => {
        closeFilterPanel();
    });

    resvPanelResetBtn?.addEventListener('click', () => {
        resvFiltersClear?.click();
    });

    const applyResvFilters = () => {
        const searchTerm = (resvSearchInput?.value || '').trim().toLowerCase();
        const typeValue = resvTypeFilter?.value || 'all';
        const statusValue = resvStatusFilter?.value || 'all';
        const amenityValue = resvAmenityFilter?.value || 'all';
        const fromValue = resvDateFrom?.value || '';
        const toValue = resvDateTo?.value || '';
        const sortValue = resvSortSelect?.value || 'nearest_checkout';

        // Update Sort label on top bar
        if (resvCurrentSortLabel && resvSortSelect) {
            const selectedOpt = resvSortSelect.options[resvSortSelect.selectedIndex];
            if (selectedOpt) {
                const cleanLabel = selectedOpt.text.replace(/\s*\(Default\)$/i, '').trim();
                resvCurrentSortLabel.textContent = cleanLabel;
            }
        }
        updateSortDropdownActiveOption();

        const tbody = document.getElementById('checkInsReservationTableBody');
        if (!tbody) return;

        const resvRows = Array.from(tbody.querySelectorAll('.reservation-row'));
        let visibleCount = 0;
        let activeFilterCount = 0;

        if (typeValue !== 'all') activeFilterCount++;
        if (statusValue !== 'all') activeFilterCount++;
        if (amenityValue !== 'all') activeFilterCount++;
        if (fromValue) activeFilterCount++;
        if (toValue) activeFilterCount++;
        if (sortValue !== 'nearest_checkout') activeFilterCount++;

        if (resvActiveFilterCount) {
            if (activeFilterCount > 0) {
                resvActiveFilterCount.textContent = String(activeFilterCount);
                resvActiveFilterCount.classList.remove('hidden');
                resvActiveFilterCount.classList.add('inline-flex');
            } else {
                resvActiveFilterCount.classList.add('hidden');
                resvActiveFilterCount.classList.remove('inline-flex');
            }
        }

        // 1. Filtering
        resvRows.forEach((row) => {
            let show = true;

            if (searchTerm) {
                const searchable = (row.getAttribute('data-reservation-search') || '').toLowerCase();
                if (!searchable.includes(searchTerm)) show = false;
            }

            if (show && typeValue !== 'all') {
                if ((row.getAttribute('data-reservation-type') || '') !== typeValue) show = false;
            }

            if (show && statusValue !== 'all') {
                const rowStatus = row.getAttribute('data-checkout-status') || 'normal';
                if (rowStatus !== statusValue) show = false;
            }

            if (show && amenityValue !== 'all') {
                const hasAmenities = row.getAttribute('data-has-amenities') === 'true';
                if (amenityValue === 'visit_only' && hasAmenities) show = false;
                if (amenityValue === 'with_amenities' && !hasAmenities) show = false;
            }

            if (show && fromValue) {
                const date = row.getAttribute('data-check-in-date') || '';
                if (date && date < fromValue) show = false;
            }

            if (show && toValue) {
                const date = row.getAttribute('data-check-in-date') || '';
                if (date && date > toValue) show = false;
            }

            row.style.display = show ? '' : 'none';

            // Also synchronize nested row visibility
            const resId = row.getAttribute('data-reservation-id');
            const nestedRow = tbody.querySelector(`.reservation-nested-row[data-parent-reservation-id="${resId}"]`);
            if (nestedRow) {
                if (!show) {
                    nestedRow.style.display = 'none';
                } else if (row.classList.contains('row-is-expanded')) {
                    nestedRow.style.display = '';
                }
            }

            if (show) visibleCount++;
        });

        // 2. Sorting
        resvRows.sort((a, b) => {
            const idA = parseInt(a.getAttribute('data-reservation-id') || '0', 10);
            const idB = parseInt(b.getAttribute('data-reservation-id') || '0', 10);
            const coA = parseFloat(a.getAttribute('data-checkout-timestamp') || '9999999999');
            const coB = parseFloat(b.getAttribute('data-checkout-timestamp') || '9999999999');
            const ciA = parseFloat(a.getAttribute('data-checkin-timestamp') || '0');
            const ciB = parseFloat(b.getAttribute('data-checkin-timestamp') || '0');
            const nameA = (a.getAttribute('data-primary-name') || '').trim();
            const nameB = (b.getAttribute('data-primary-name') || '').trim();
            const amenityA = (a.getAttribute('data-amenities-name') || '').trim();
            const amenityB = (b.getAttribute('data-amenities-name') || '').trim();
            const amtA = parseFloat(a.getAttribute('data-amount-total') || '0');
            const amtB = parseFloat(b.getAttribute('data-amount-total') || '0');
            const guestsA = parseInt(a.getAttribute('data-guest-count') || '0', 10);
            const guestsB = parseInt(b.getAttribute('data-guest-count') || '0', 10);

            switch (sortValue) {
                case 'nearest_checkout':
                    if (coA !== coB) return coA - coB;
                    return idB - idA;
                case 'latest_checkout':
                    if (coA === 9999999999 && coB !== 9999999999) return 1;
                    if (coB === 9999999999 && coA !== 9999999999) return -1;
                    if (coA !== coB) return coB - coA;
                    return idB - idA;
                case 'checkin_newest':
                    if (ciA !== ciB) return ciB - ciA;
                    return idB - idA;
                case 'checkin_oldest':
                    if (ciA !== ciB) return ciA - ciB;
                    return idA - idB;
                case 'id_desc':
                    return idB - idA;
                case 'id_asc':
                    return idA - idB;
                case 'guest_name':
                    return nameA.localeCompare(nameB) || (idB - idA);
                case 'guest_count':
                    if (guestsA !== guestsB) return guestsB - guestsA;
                    return idB - idA;
                default:
                    return coA - coB;
            }
        });

        // Re-append sorted rows keeping their respective nested row attached right below.
        // Use only the data-parent-reservation-id selector (not nextElementSibling) to
        // avoid attaching wrong nested rows after the sort has reordered siblings.
        resvRows.forEach((row) => {
            tbody.appendChild(row);
            const resId = row.getAttribute('data-reservation-id');
            const nestedRow = tbody.querySelector(`.reservation-nested-row[data-parent-reservation-id="${resId}"]`);
            if (nestedRow) {
                tbody.appendChild(nestedRow);
            }
        });

        // 3. Update Result Counter
        if (resvResultsCount) {
            resvResultsCount.textContent = `Showing ${visibleCount} of ${resvRows.length} reservation${resvRows.length === 1 ? '' : 's'}`;
        }

        // 4. Update Clear/Reset Button visibility
        if (resvFiltersClear) {
            const isFiltered = Boolean(searchTerm || typeValue !== 'all' || statusValue !== 'all' || amenityValue !== 'all' || fromValue || toValue || sortValue !== 'nearest_checkout');
            resvFiltersClear.classList.toggle('hidden', !isFiltered);
        }
    };

    [resvSearchInput, resvTypeFilter, resvStatusFilter, resvAmenityFilter, resvSortSelect, resvDateFrom, resvDateTo].forEach((control) => {
        control?.addEventListener('input', applyResvFilters);
        control?.addEventListener('change', applyResvFilters);
    });

    resvAmenityFilters.forEach((chip) => {
        chip.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const filterVal = chip.getAttribute('data-amenity-filter') || 'all';
            resvAmenityFilters.forEach((c) => {
                const isMatch = c === chip;
                c.classList.toggle('active', isMatch);
                c.classList.toggle('bg-white', isMatch);
                c.classList.toggle('dark:bg-[#1e2220]', isMatch);
                c.classList.toggle('shadow-xs', isMatch);
                c.classList.toggle('font-bold', isMatch);
                c.classList.toggle('text-hp-text', isMatch);
                c.classList.toggle('font-semibold', !isMatch);
                c.classList.toggle('text-hp-text-muted', !isMatch);
            });
            if (resvAmenityFilter) {
                resvAmenityFilter.value = filterVal;
            }
            applyResvFilters();
        });
    });

    resvAmenityFilter?.addEventListener('change', () => {
        const val = resvAmenityFilter.value || 'all';
        resvAmenityFilters.forEach((c) => {
            const isMatch = c.getAttribute('data-amenity-filter') === val;
            c.classList.toggle('active', isMatch);
            c.classList.toggle('bg-white', isMatch);
            c.classList.toggle('dark:bg-[#1e2220]', isMatch);
            c.classList.toggle('shadow-xs', isMatch);
            c.classList.toggle('font-bold', isMatch);
            c.classList.toggle('text-hp-text', isMatch);
            c.classList.toggle('font-semibold', !isMatch);
            c.classList.toggle('text-hp-text-muted', !isMatch);
        });
        applyResvFilters();
    });

    resvStatusFilter?.addEventListener('change', () => {
        const val = resvStatusFilter.value || 'all';
        resvQuickFilters.forEach((c) => {
            const isMatch = c.getAttribute('data-status-filter') === val;
            c.classList.toggle('active', isMatch);
            c.classList.toggle('bg-white', isMatch);
            c.classList.toggle('dark:bg-[#1e2220]', isMatch);
            c.classList.toggle('shadow-xs', isMatch);
            c.classList.toggle('font-bold', isMatch);
            c.classList.toggle('text-hp-text', isMatch);
            c.classList.toggle('font-semibold', !isMatch);
            c.classList.toggle('text-hp-text-muted', !isMatch);
        });
        applyResvFilters();
    });

    resvQuickFilters.forEach((chip) => {
        chip.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const filterVal = chip.getAttribute('data-status-filter') || 'all';
            resvQuickFilters.forEach((c) => {
                c.classList.toggle('active', c === chip);
                c.classList.toggle('bg-white', c === chip);
                c.classList.toggle('dark:bg-[#1e2220]', c === chip);
                c.classList.toggle('shadow-xs', c === chip);
                c.classList.toggle('font-bold', c === chip);
                c.classList.toggle('text-hp-text', c === chip);
                c.classList.toggle('font-semibold', c !== chip);
                c.classList.toggle('text-hp-text-muted', c !== chip);
            });
            if (resvStatusFilter) {
                resvStatusFilter.value = filterVal;
            }
            applyResvFilters();
        });
    });

    resvFiltersClear?.addEventListener('click', () => {
        if (resvSearchInput) resvSearchInput.value = '';
        if (resvTypeFilter) resvTypeFilter.value = 'all';
        if (resvStatusFilter) resvStatusFilter.value = 'all';
        if (resvAmenityFilter) resvAmenityFilter.value = 'all';
        if (resvSortSelect) resvSortSelect.value = 'nearest_checkout';
        if (resvDateFrom) resvDateFrom.value = '';
        if (resvDateTo) resvDateTo.value = '';
        if (resvCurrentSortLabel) resvCurrentSortLabel.textContent = 'Nearest to Checkout';

        resvAmenityFilters.forEach((c) => {
            const isAll = c.getAttribute('data-amenity-filter') === 'all';
            c.classList.toggle('active', isAll);
            c.classList.toggle('bg-white', isAll);
            c.classList.toggle('dark:bg-[#1e2220]', isAll);
            c.classList.toggle('shadow-xs', isAll);
            c.classList.toggle('font-bold', isAll);
            c.classList.toggle('text-hp-text', isAll);
            c.classList.toggle('font-semibold', !isAll);
            c.classList.toggle('text-hp-text-muted', !isAll);
        });

        resvQuickFilters.forEach((c) => {
            const isAll = c.getAttribute('data-status-filter') === 'all';
            c.classList.toggle('active', isAll);
            c.classList.toggle('bg-white', isAll);
            c.classList.toggle('dark:bg-[#1e2220]', isAll);
            c.classList.toggle('shadow-xs', isAll);
            c.classList.toggle('font-bold', isAll);
            c.classList.toggle('text-hp-text', isAll);
            c.classList.toggle('font-semibold', !isAll);
            c.classList.toggle('text-hp-text-muted', !isAll);
        });

        applyResvFilters();
    });

    applyResvFilters();

    // Scan QR modal
    const scanQrBtn = document.getElementById('scanQrBtn');
    const scanQrModal = document.getElementById('scanQrModal');
    const scanQrCloseButtons = document.querySelectorAll('[data-close-scan-modal="true"]');
    const stopQrBtn = document.getElementById('stopQrBtn');
    const qrCameraSelect = document.getElementById('qrCameraSelect');
    const qrScannerStatus = document.getElementById('qrScannerStatus');
    const qrScannerElement = document.getElementById('qrScanner');
    let html5QrCode = null;
    let qrScannerActive = false;

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
        } catch (e) { }
        qrScannerActive = false;
    };

    const closeScanQrModal = async () => {
        await stopQrScanner();
        if (scanQrModal) {
            scanQrModal.classList.remove('is-open');
            scanQrModal.setAttribute('aria-hidden', 'true');
            window.dispatchEvent(new CustomEvent('modal:closed'));
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

                qrScannerStatus.textContent = `Found reservation #${formatReservationId(reservationId)}. Looking up...`;
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
                        if (window.staffReservationData) {
                            window.staffReservationData[reservationId] = body.reservation;
                        }
                        reservationData[reservationId] = body.reservation;
                        await closeScanQrModal();

                        // Open the modal of that reservation
                        openReservationModal(reservationId);
                    } else {
                        qrScannerStatus.textContent = 'Reservation not found for scanned QR code.';
                    }
                } catch (lookupError) {
                    qrScannerStatus.textContent = 'Unable to fetch reservation details. Try again.';
                }
            },
            (errorMessage) => {
                // scanning frame callback
            }
        );

        qrScannerActive = true;
        qrScannerStatus.textContent = 'Scanning for QR code. Hold the QR in front of the camera.';
    };

    const openScanQrModal = async () => {
        if (!scanQrModal || !qrScannerElement || !qrScannerStatus) return;
        scanQrModal.classList.add('is-open');
        scanQrModal.classList.remove('hidden');
        scanQrModal.style.display = 'flex';
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
            openScanQrModal();
        });

        stopQrBtn?.addEventListener('click', async () => {
            await closeScanQrModal();
        });

        scanQrCloseButtons.forEach(button => {
            button.addEventListener('click', async () => {
                await closeScanQrModal();
            });
        });
    }

    // Check-in modal
    const checkInModal = document.getElementById('checkInModal');
    const checkInCloseButtons = document.querySelectorAll('[data-close-check-in-modal="true"]');

    const closeCheckInModal = () => {
        checkInModal.classList.remove('is-open');
        checkInModal.setAttribute('aria-hidden', 'true');
    };

    checkInCloseButtons.forEach(button => {
        button.addEventListener('click', closeCheckInModal);
    });

    // Check-in companion modal
    const checkInCompanionModal = document.getElementById('checkInCompanionModal');
    const checkInCompanionCloseButtons = document.querySelectorAll('[data-close-check-in-companion-modal="true"]');
    const checkInAddCompanionBtn = document.getElementById('checkInAddCompanionBtn');
    const checkInCompanionForm = document.getElementById('checkInCompanionForm');
    const checkInCompanionList = document.getElementById('checkInCompanionList');
    const checkInCompanionHiddenFields = document.getElementById('checkInCompanionHiddenFields');
    const checkInCompanionIsForeigner = document.getElementById('checkInCompanionIsForeigner');
    const checkInPrimaryIsForeigner = document.getElementById('checkInPrimaryIsForeigner');

    const openCheckInCompanionModal = () => {
        checkInCompanionModal.classList.add('is-open');
        checkInCompanionModal.setAttribute('aria-hidden', 'false');
    };

    const closeCheckInCompanionModal = () => {
        checkInCompanionModal.classList.remove('is-open');
        checkInCompanionModal.setAttribute('aria-hidden', 'true');
    };

    checkInAddCompanionBtn?.addEventListener('click', openCheckInCompanionModal);
    checkInCompanionCloseButtons.forEach(button => {
        button.addEventListener('click', closeCheckInCompanionModal);
    });

    checkInCompanionForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(checkInCompanionForm);
        const companionData = Object.fromEntries(formData.entries());

        const nextCompanionIdx = ctx.incrementCompanionCount();
        const companionHtml = `
            <div class="guest-companion-pill">
                <span class="guest-companion-pill__name">${companionData.first_name} ${companionData.last_name}</span>
                <button type="button" class="guest-companion-pill__delete" data-companion-index="${nextCompanionIdx}">Remove</button>
            </div>
        `;
        checkInCompanionList.insertAdjacentHTML('beforeend', companionHtml);

        checkInCompanionForm.reset();
        closeCheckInCompanionModal();
    });

    // ── Add companion(s) to a checked-in reservation (reservation modal) ────
    const resAddCompanionModal = document.getElementById('reservationAddCompanionModal');
    const resAddCompanionFor = document.getElementById('reservationAddCompanionFor');
    const resAddCloseButtons = document.querySelectorAll('[data-close-reservation-add-companion="true"]');
    const resAddBulkForm = document.getElementById('reservationAddBulkForm');
    const resAddPreviewList = document.getElementById('reservationAddCompanionPreviewList');
    const resAddPreviewCount = document.getElementById('reservationAddCompanionPreviewCount');
    const resAddCompanionClearBtn = document.getElementById('resAddCompanionClearBtn');
    const resAddCompanionFooterSummary = document.getElementById('resAddCompanionFooterSummary');
    const resAddAllCompanionsBtn = document.getElementById('reservationAddAllCompanionsBtn');
    const resAddBulkQtyMinus = document.getElementById('resAddBulkQtyMinus');
    const resAddBulkQtyPlus = document.getElementById('resAddBulkQtyPlus');
    const resAddBulkFreeMinus = document.getElementById('resAddBulkFreeMinus');
    const resAddBulkFreePlus = document.getElementById('resAddBulkFreePlus');
    const resAddBulkPoolMinus = document.getElementById('resAddBulkPoolMinus');
    const resAddBulkPoolPlus = document.getElementById('resAddBulkPoolPlus');
    let resAddStagedCompanions = [];
    let resAddSyncAmenityBenefits = () => {};
    let resAddSyncAmenityDropdown = () => {};

    // Group edit modal elements
    const resAddGroupEditModal = document.getElementById('resAddGroupEditModal');
    const resAddGroupEditForm = document.getElementById('resAddGroupEditForm');
    const resAddGroupEditIndex = document.getElementById('resAddGroupEditIndex');
    const resAddGroupEditDemographicsBadge = document.getElementById('resAddGroupEditDemographicsBadge');
    const resAddGroupEditAmenityBadge = document.getElementById('resAddGroupEditAmenityBadge');
    const resAddGroupEditQuantityInput = document.getElementById('resAddGroupEditQuantityInput');
    const resAddGroupEditQtyMinusBtn = document.getElementById('resAddGroupEditQtyMinusBtn');
    const resAddGroupEditQtyPlusBtn = document.getElementById('resAddGroupEditQtyPlusBtn');
    const resAddGroupEditFreeInput = document.getElementById('resAddGroupEditFreeInput');
    const resAddGroupEditFreeHint = document.getElementById('resAddGroupEditFreeHint');
    const resAddGroupEditFreeMinusBtn = document.getElementById('resAddGroupEditFreeMinusBtn');
    const resAddGroupEditFreePlusBtn = document.getElementById('resAddGroupEditFreePlusBtn');
    const resAddGroupEditFreeZeroBtn = document.getElementById('resAddGroupEditFreeZeroBtn');
    const resAddGroupEditFreeAllBtn = document.getElementById('resAddGroupEditFreeAllBtn');
    const resAddGroupEditPoolInput = document.getElementById('resAddGroupEditPoolInput');
    const resAddGroupEditPoolHint = document.getElementById('resAddGroupEditPoolHint');
    const resAddGroupEditPoolMinusBtn = document.getElementById('resAddGroupEditPoolMinusBtn');
    const resAddGroupEditPoolPlusBtn = document.getElementById('resAddGroupEditPoolPlusBtn');
    const resAddGroupEditPoolZeroBtn = document.getElementById('resAddGroupEditPoolZeroBtn');
    const resAddGroupEditPoolAllBtn = document.getElementById('resAddGroupEditPoolAllBtn');

    // Live calculation elements
    const resAddCalcPeriodBadge = document.getElementById('resAddCalcPeriodBadge');
    const resAddCalcAdultCount = document.getElementById('resAddCalcAdultCount');
    const resAddCalcAdultSubtotal = document.getElementById('resAddCalcAdultSubtotal');
    const resAddCalcChildCount = document.getElementById('resAddCalcChildCount');
    const resAddCalcChildSubtotal = document.getElementById('resAddCalcChildSubtotal');
    const resAddCalcFreeRow = document.getElementById('resAddCalcFreeRow');
    const resAddCalcFreeCount = document.getElementById('resAddCalcFreeCount');
    const resAddCalcPoolCount = document.getElementById('resAddCalcPoolCount');
    const resAddCalcPoolSubtotal = document.getElementById('resAddCalcPoolSubtotal');
    const resAddCalcFreePoolRow = document.getElementById('resAddCalcFreePoolRow');
    const resAddCalcFreePoolCount = document.getElementById('resAddCalcFreePoolCount');
    const resAddCalcExtraHeadRow = document.getElementById('resAddCalcExtraHeadRow');
    const resAddCalcExtraHeadCount = document.getElementById('resAddCalcExtraHeadCount');
    const resAddCalcExtraHeadSubtotal = document.getElementById('resAddCalcExtraHeadSubtotal');
    const resAddCalcGrandTotal = document.getElementById('resAddCalcGrandTotal');
    const resAddFooterTotalPayment = document.getElementById('resAddFooterTotalPayment');

    // Add companion confirmation modal elements
    const resAddConfirmModal = document.getElementById('reservationAddCompanionConfirmModal');
    const resAddConfirmCloseButtons = document.querySelectorAll('[data-close-res-add-confirm="true"]');
    const resAddConfirmSubmitBtn = document.getElementById('resAddConfirmSubmitBtn');
    const resAddConfirmResNumber = document.getElementById('resAddConfirmResNumber');
    const resAddConfirmBookerName = document.getElementById('resAddConfirmBookerName');
    const resAddConfirmTotalGuests = document.getElementById('resAddConfirmTotalGuests');
    const resAddConfirmCompanionsSummary = document.getElementById('resAddConfirmCompanionsSummary');
    const resAddConfirmPeriodBadge = document.getElementById('resAddConfirmPeriodBadge');
    const resAddConfirmAdultLabel = document.getElementById('resAddConfirmAdultLabel');
    const resAddConfirmAdultAmount = document.getElementById('resAddConfirmAdultAmount');
    const resAddConfirmChildLabel = document.getElementById('resAddConfirmChildLabel');
    const resAddConfirmChildAmount = document.getElementById('resAddConfirmChildAmount');
    const resAddConfirmFreeRow = document.getElementById('resAddConfirmFreeRow');
    const resAddConfirmFreeLabel = document.getElementById('resAddConfirmFreeLabel');
    const resAddConfirmPoolLabel = document.getElementById('resAddConfirmPoolLabel');
    const resAddConfirmPoolAmount = document.getElementById('resAddConfirmPoolAmount');
    const resAddConfirmFreePoolRow = document.getElementById('resAddConfirmFreePoolRow');
    const resAddConfirmFreePoolLabel = document.getElementById('resAddConfirmFreePoolLabel');
    const resAddConfirmExtraHeadRow = document.getElementById('resAddConfirmExtraHeadRow');
    const resAddConfirmExtraHeadLabel = document.getElementById('resAddConfirmExtraHeadLabel');
    const resAddConfirmExtraHeadAmount = document.getElementById('resAddConfirmExtraHeadAmount');
    const resAddConfirmGrandTotal = document.getElementById('resAddConfirmGrandTotal');
    const resAddConfirmPaymentBadge = document.getElementById('resAddConfirmPaymentBadge');

    const resAddCalculatePayment = () => {
        const res = (window.staffReservationData && window.staffReservationData[ctx.getCurrentReservationId()])
            || (typeof reservationData !== 'undefined' ? reservationData[ctx.getCurrentReservationId()] : null);

        let effectivePeriod = 'daytime';
        const entranceFeePricing = res?.entrance_fee?.pricing_type;
        if (entranceFeePricing === 'Nighttime') {
            effectivePeriod = 'nighttime';
        } else if (entranceFeePricing === 'DayToNight' || entranceFeePricing === 'NightToDay') {
            effectivePeriod = 'daytonight';
        } else if (res?.reservation_amenities && res.reservation_amenities.length > 0) {
            const firstAmenityPricing = String(res.reservation_amenities[0]?.pricing_type || '');
            if (firstAmenityPricing.includes('NightToDay') || firstAmenityPricing.includes('DayToNight')) {
                effectivePeriod = 'daytonight';
            } else if (firstAmenityPricing.includes('Nighttime') || firstAmenityPricing.endsWith('Night')) {
                effectivePeriod = 'nighttime';
            } else {
                effectivePeriod = 'daytime';
            }
        } else if (res?.start_slot === 'Nighttime' || res?.end_slot === 'Nighttime') {
            effectivePeriod = 'nighttime';
        }

        const activeSettings = {
            ...ctx.getParkSettings(),
            ...(window.parkSettings || {})
        };

        const dayAdult = parseFloat(activeSettings.daytime_adult_entrance_fee) || 0;
        const dayChild = parseFloat(activeSettings.daytime_child_entrance_fee) || 0;
        const nightAdult = parseFloat(activeSettings.nighttime_adult_entrance_fee) || 0;
        const nightChild = parseFloat(activeSettings.nighttime_child_entrance_fee) || 0;
        const dayPool = parseFloat(activeSettings.day_pool_fee) || 0;
        const nightPool = parseFloat(activeSettings.nightpool_fee || activeSettings.night_pool_fee) || 0;

        let adultRate = dayAdult;
        let childRate = dayChild;
        let poolRate = dayPool;
        let periodName = 'Daytime';

        if (effectivePeriod === 'nighttime') {
            adultRate = nightAdult;
            childRate = nightChild;
            poolRate = nightPool;
            periodName = 'Nighttime';
        } else if (effectivePeriod === 'daytonight') {
            adultRate = dayAdult + nightAdult;
            childRate = dayChild + nightChild;
            poolRate = dayPool + nightPool;
            periodName = 'Day & Night';
        }

        const resAmenities = res?.reservation_amenities || [];

        let adultCount = 0;
        let childCount = 0;
        let payingAdultCount = 0;
        let payingChildCount = 0;
        let freeCount = 0;
        let poolCount = 0;
        let payingPoolCount = 0;
        let freePoolCount = 0;
        let totalGuests = 0;
        const amenityNamesSet = new Set();

        resAddStagedCompanions.forEach(group => {
            const qty = Math.max(parseInt(group.quantity, 10) || 1, 1);
            let freePasses = Math.min(Math.max(0, parseInt(group.free_passes, 10) || 0), qty);
            let poolPasses = Math.min(Math.max(0, parseInt(group.pool_passes, 10) || 0), qty);

            // Apply amenity benefits if companion group is assigned to an amenity with benefits
            let hasFreeEntranceBenefit = false;
            let hasFreePoolBenefit = false;
            if (group.amenity_id === 'all') {
                hasFreeEntranceBenefit = resAmenities.some(ra => Boolean(ra.free_entrance || ra.amenity?.benefits?.free_entrance));
                hasFreePoolBenefit = resAmenities.some(ra => Boolean(ra.free_pool || ra.amenity?.benefits?.free_pool));
            } else if (group.amenity_id && group.amenity_id !== 'no_amenity') {
                const ra = resAmenities.find(a => String(a.amenity_id || a.amenity?.id || a.id) === String(group.amenity_id));
                if (ra) {
                    hasFreeEntranceBenefit = Boolean(ra.free_entrance || ra.amenity?.benefits?.free_entrance);
                    hasFreePoolBenefit = Boolean(ra.free_pool || ra.amenity?.benefits?.free_pool);
                }
            }

            if (hasFreeEntranceBenefit) {
                freePasses = qty;
            }
            if (hasFreePoolBenefit) {
                poolPasses = qty;
            }

            const isChild = (group.age_group === '0-12' || parseInt(group.age, 10) <= 12);

            totalGuests += qty;
            freeCount += freePasses;
            poolCount += poolPasses;

            if (hasFreePoolBenefit) {
                freePoolCount += poolPasses;
            } else {
                payingPoolCount += poolPasses;
            }

            if (group.amenity_name) {
                amenityNamesSet.add(group.amenity_name);
            }

            if (isChild) {
                childCount += qty;
                payingChildCount += Math.max(0, qty - freePasses);
            } else {
                adultCount += qty;
                payingAdultCount += Math.max(0, qty - freePasses);
            }
        });

        // Extra head capacity fee: ONLY charged for companions assigned to an amenity whose capacity is exceeded
        let extraHeadCount = 0;
        let extraHeadFee = 0;
        if (resAmenities.length > 0) {
            const amenityCounts = {};
            resAmenities.forEach(ra => {
                const aId = String(ra.amenity_id || ra.amenity?.id || ra.id);
                amenityCounts[aId] = (res?.reservation_guests || []).filter(g => {
                    if (g.checked_out_at) return false;
                    const gAmId = g.amenity_id ? String(g.amenity_id) : '';
                    if (gAmId) return gAmId === aId;
                    if (resAmenities.length === 1) return true;
                    return false;
                }).length;
            });

            resAddStagedCompanions.forEach(group => {
                const qty = Math.max(parseInt(group.quantity, 10) || 1, 1);
                // "No amenity" and "all" do NOT take up specific amenity capacity and do NOT incur extra head fee!
                if (!group.amenity_id || group.amenity_id === 'all' || group.amenity_id === 'no_amenity') {
                    return;
                }
                const cAmId = String(group.amenity_id);
                const ra = resAmenities.find(a => String(a.amenity_id || a.amenity?.id || a.id) === cAmId);
                const am = ra?.amenity || ra;

                if (am) {
                    const realAmId = String(ra.amenity_id || am.id || cAmId);
                    const maxCap = (am.maximum_capacity !== null && am.maximum_capacity !== undefined && am.maximum_capacity !== '')
                        ? parseInt(am.maximum_capacity, 10) : null;
                    const perHeadFee = parseFloat(am.additional_per_head) || 0;
                    const curCount = amenityCounts[realAmId] ?? 0;

                    for (let i = 0; i < qty; i++) {
                        if (maxCap !== null && (curCount + i) >= maxCap) {
                            extraHeadFee += perHeadFee;
                            extraHeadCount++;
                        }
                    }
                    amenityCounts[realAmId] = curCount + qty;
                }
            });
        }

        const adultSubtotal = Math.round(payingAdultCount * adultRate * 100) / 100;
        const childSubtotal = Math.round(payingChildCount * childRate * 100) / 100;
        const entranceSubtotal = Math.round((adultSubtotal + childSubtotal) * 100) / 100;
        const poolSubtotal = Math.round(payingPoolCount * poolRate * 100) / 100;
        const extraHeadSubtotal = Math.round(extraHeadFee * 100) / 100;
        const totalPayment = Math.round((entranceSubtotal + poolSubtotal + extraHeadSubtotal) * 100) / 100;

        return {
            effectivePeriod,
            periodName,
            adultRate,
            childRate,
            poolRate,
            totalGuests,
            adultCount,
            childCount,
            payingAdultCount,
            payingChildCount,
            freeCount,
            poolCount,
            payingPoolCount,
            freePoolCount,
            extraHeadCount,
            adultSubtotal,
            childSubtotal,
            entranceSubtotal,
            poolSubtotal,
            extraHeadSubtotal,
            totalPayment,
            amenityNames: Array.from(amenityNamesSet).join(', ')
        };
    };

    const resAddUpdateCalculationUI = () => {
        const calc = resAddCalculatePayment();
        if (resAddCalcPeriodBadge) resAddCalcPeriodBadge.textContent = calc.periodName;
        if (resAddCalcAdultCount) resAddCalcAdultCount.textContent = `${calc.payingAdultCount} paying`;
        if (resAddCalcAdultSubtotal) resAddCalcAdultSubtotal.textContent = `₱${calc.adultSubtotal.toFixed(2)}`;
        if (resAddCalcChildCount) resAddCalcChildCount.textContent = `${calc.payingChildCount} paying`;
        if (resAddCalcChildSubtotal) resAddCalcChildSubtotal.textContent = `₱${calc.childSubtotal.toFixed(2)}`;
        if (resAddCalcFreeRow) resAddCalcFreeRow.style.display = calc.freeCount > 0 ? 'flex' : 'none';
        if (resAddCalcFreeCount) resAddCalcFreeCount.textContent = calc.freeCount;
        if (resAddCalcPoolCount) {
            resAddCalcPoolCount.textContent = `${calc.payingPoolCount} paying${calc.freePoolCount > 0 ? ` (${calc.freePoolCount} free)` : ''}`;
        }
        if (resAddCalcPoolSubtotal) resAddCalcPoolSubtotal.textContent = `₱${calc.poolSubtotal.toFixed(2)}`;
        if (resAddCalcFreePoolRow) resAddCalcFreePoolRow.style.display = calc.freePoolCount > 0 ? 'flex' : 'none';
        if (resAddCalcFreePoolCount) resAddCalcFreePoolCount.textContent = calc.freePoolCount;
        if (resAddCalcExtraHeadRow) resAddCalcExtraHeadRow.style.display = calc.extraHeadCount > 0 ? 'flex' : 'none';
        if (resAddCalcExtraHeadCount) resAddCalcExtraHeadCount.textContent = calc.extraHeadCount;
        if (resAddCalcExtraHeadSubtotal) resAddCalcExtraHeadSubtotal.textContent = `₱${calc.extraHeadSubtotal.toFixed(2)}`;
        if (resAddCalcGrandTotal) resAddCalcGrandTotal.textContent = `₱${calc.totalPayment.toFixed(2)}`;
        if (resAddFooterTotalPayment) resAddFooterTotalPayment.textContent = `₱${calc.totalPayment.toFixed(2)}`;
        return calc;
    };

    const openResAddConfirmModal = () => {
        if (!ctx.getCurrentReservationId() || !resAddStagedCompanions.length) return;

        const res = (window.staffReservationData && window.staffReservationData[ctx.getCurrentReservationId()])
            || (typeof reservationData !== 'undefined' ? reservationData[ctx.getCurrentReservationId()] : null);
        const calc = resAddCalculatePayment();

        if (resAddConfirmResNumber) resAddConfirmResNumber.textContent = `#${ctx.getCurrentReservationId()}`;
        if (resAddConfirmBookerName) resAddConfirmBookerName.textContent = res?.booker_name || 'Guest';
        if (resAddConfirmTotalGuests) resAddConfirmTotalGuests.textContent = `${calc.totalGuests} Guest${calc.totalGuests === 1 ? '' : 's'}`;

        const summaryParts = [];
        if (calc.adultCount > 0) summaryParts.push(`${calc.adultCount} Adult${calc.adultCount === 1 ? '' : 's'}`);
        if (calc.childCount > 0) summaryParts.push(`${calc.childCount} Child${calc.childCount === 1 ? '' : 'ren'}`);
        if (calc.freeCount > 0) summaryParts.push(`${calc.freeCount} Free Entrance`);
        if (calc.payingPoolCount > 0) summaryParts.push(`${calc.payingPoolCount} Paying Pool`);
        if (calc.freePoolCount > 0) summaryParts.push(`${calc.freePoolCount} Free Pool`);
        if (calc.amenityNames) summaryParts.push(`Assigned: ${calc.amenityNames}`);
        if (resAddConfirmCompanionsSummary) resAddConfirmCompanionsSummary.textContent = summaryParts.join(' • ');

        if (resAddConfirmPeriodBadge) resAddConfirmPeriodBadge.textContent = calc.periodName;
        if (resAddConfirmAdultLabel) resAddConfirmAdultLabel.textContent = `Adult Entrance (${calc.payingAdultCount} × ₱${calc.adultRate.toFixed(2)}):`;
        if (resAddConfirmAdultAmount) resAddConfirmAdultAmount.textContent = `₱${calc.adultSubtotal.toFixed(2)}`;
        if (resAddConfirmChildLabel) resAddConfirmChildLabel.textContent = `Child Entrance (${calc.payingChildCount} × ₱${calc.childRate.toFixed(2)}):`;
        if (resAddConfirmChildAmount) resAddConfirmChildAmount.textContent = `₱${calc.childSubtotal.toFixed(2)}`;

        if (resAddConfirmFreeRow) resAddConfirmFreeRow.style.display = calc.freeCount > 0 ? 'flex' : 'none';
        if (resAddConfirmFreeLabel) resAddConfirmFreeLabel.textContent = `Free Entrance (${calc.freeCount} passes):`;

        if (resAddConfirmPoolLabel) resAddConfirmPoolLabel.textContent = `Pool Access (${calc.payingPoolCount} × ₱${calc.poolRate.toFixed(2)}):`;
        if (resAddConfirmPoolAmount) resAddConfirmPoolAmount.textContent = `₱${calc.poolSubtotal.toFixed(2)}`;

        if (resAddConfirmFreePoolRow) resAddConfirmFreePoolRow.style.display = calc.freePoolCount > 0 ? 'flex' : 'none';
        if (resAddConfirmFreePoolLabel) resAddConfirmFreePoolLabel.textContent = `Free Pool Access (${calc.freePoolCount} passes from amenity benefit):`;

        if (resAddConfirmExtraHeadRow) resAddConfirmExtraHeadRow.style.display = calc.extraHeadCount > 0 ? 'flex' : 'none';
        if (resAddConfirmExtraHeadLabel) resAddConfirmExtraHeadLabel.textContent = `Extra Capacity (${calc.extraHeadCount} × head fee):`;
        if (resAddConfirmExtraHeadAmount) resAddConfirmExtraHeadAmount.textContent = `₱${calc.extraHeadSubtotal.toFixed(2)}`;

        if (resAddConfirmGrandTotal) resAddConfirmGrandTotal.textContent = `₱${calc.totalPayment.toFixed(2)}`;
        if (resAddConfirmPaymentBadge) {
            resAddConfirmPaymentBadge.textContent = calc.totalPayment > 0 ? 'To Collect at Counter' : 'No Payment Required (Free)';
            if (calc.totalPayment > 0) {
                resAddConfirmPaymentBadge.className = 'mt-1.5 inline-block rounded-full bg-hp-green px-3.5 py-1 text-[0.72rem] font-bold uppercase tracking-wider text-white';
            } else {
                resAddConfirmPaymentBadge.className = 'mt-1.5 inline-block rounded-full bg-emerald-700/80 px-3.5 py-1 text-[0.72rem] font-bold uppercase tracking-wider text-white';
            }
        }

        if (resAddConfirmSubmitBtn) {
            resAddConfirmSubmitBtn.disabled = false;
            resAddConfirmSubmitBtn.innerHTML = '<i class="bi bi-check2-circle text-base"></i> <span>Confirm & Add Companions</span>';
        }

        resAddConfirmModal?.classList.add('is-open');
        resAddConfirmModal?.classList.remove('hidden');
        resAddConfirmModal?.setAttribute('aria-hidden', 'false');
    };

    const closeResAddConfirmModal = () => {
        resAddConfirmModal?.classList.remove('is-open');
        resAddConfirmModal?.classList.add('hidden');
        resAddConfirmModal?.setAttribute('aria-hidden', 'true');
    };

    const resAddSyncBulkHints = () => {
        const qtyInput = document.getElementById('resadd_bulk_quantity');
        const qty = Math.min(Math.max(parseInt(qtyInput?.value, 10) || 1, 1), 500);
        const freeInput = document.getElementById('resadd_bulk_free_passes');
        const poolInput = document.getElementById('resadd_bulk_pool_passes');
        const freeHint = document.getElementById('resAddBulkFreeHint');
        const poolHint = document.getElementById('resAddBulkPoolHint');

        let freeVal = parseInt(freeInput?.value, 10) || 0;
        if (freeVal > qty) freeVal = qty;
        if (freeVal < 0) freeVal = 0;
        if (freeInput) {
            freeInput.value = freeVal;
            freeInput.max = qty;
        }
        if (freeHint) {
            freeHint.textContent = `${freeVal} of ${qty} with free entrance`;
        }

        let poolVal = parseInt(poolInput?.value, 10) || 0;
        if (poolVal > qty) poolVal = qty;
        if (poolVal < 0) poolVal = 0;
        if (poolInput) {
            poolInput.value = poolVal;
            poolInput.max = qty;
        }
        if (poolHint) {
            poolHint.textContent = `${poolVal} of ${qty} with pool access`;
        }
    };

    const resAddRenderPreview = () => {
        if (!resAddPreviewList || !resAddPreviewCount) return;

        const totalGuests = resAddStagedCompanions.reduce((acc, c) => acc + (parseInt(c.quantity, 10) || 1), 0);
        const totalPool = resAddStagedCompanions.reduce((acc, c) => acc + (parseInt(c.pool_passes, 10) || 0), 0);

        resAddPreviewCount.textContent = `${totalGuests} companion${totalGuests === 1 ? '' : 's'}${totalPool > 0 ? ` (${totalPool} pool)` : ''}`;
        if (resAddCompanionFooterSummary) {
            resAddCompanionFooterSummary.textContent = totalGuests === 0
                ? '0 companions added so far'
                : `${totalGuests} companion${totalGuests === 1 ? '' : 's'} staged (Ready to apply)`;
        }
        if (resAddCompanionClearBtn) {
            resAddCompanionClearBtn.classList.toggle('hidden', totalGuests === 0);
        }
        if (resAddAllCompanionsBtn) {
            resAddAllCompanionsBtn.disabled = totalGuests === 0;
            resAddAllCompanionsBtn.innerHTML = '<i class="bi bi-receipt text-base"></i> <span>Review & Confirm</span>';
        }

        // Update live payment calculation card and footer
        resAddUpdateCalculationUI();

        if (resAddStagedCompanions.length === 0) {
            resAddPreviewList.innerHTML = '<p class="m-auto max-w-[240px] text-center text-xs italic text-hp-text-muted py-6">Fill the form on the left to preview companions.</p>';
            return;
        }

        resAddPreviewList.innerHTML = resAddStagedCompanions.map((group, groupIndex) => {
            const qty = Math.max(parseInt(group.quantity, 10) || 1, 1);
            group.quantity = qty;
            const nationality = group.is_foreigner ? 'Foreigner' : 'Filipino';
            const rateLabel = (group.age_group === '0-12' || group.age_type === 'child') ? 'Child' : 'Adult';
            const genderBadge = getGenderBadgeHtml(group.gender);

            let freeBadgeHtml = '';
            const fQty = Math.min(Math.max(0, parseInt(group.free_passes, 10) || 0), qty);
            group.free_passes = fQty;
            if (fQty === qty && fQty > 0) {
                freeBadgeHtml = `<span class="inline-flex h-7 w-7 items-center justify-center rounded-xl border border-amber-500/30 bg-amber-500/15 text-amber-600 dark:text-amber-400 shadow-2xs shrink-0" title="Free Entrance for all ${qty} guests"><i class="bi bi-ticket-perforated-fill text-xs"></i></span>`;
            } else if (fQty > 0) {
                freeBadgeHtml = `<div class="inline-flex items-center gap-1 h-7 rounded-xl border border-amber-500/30 bg-amber-500/15 px-2 text-xs font-bold text-amber-700 dark:text-amber-300 shadow-2xs shrink-0" title="Free entrance passes for ${fQty} of ${qty} guests">
                    <i class="bi bi-ticket-perforated-fill text-xs"></i>
                    <span>${fQty}/${qty}</span>
                </div>`;
            }

            let poolBadgeHtml = '';
            const pQty = Math.min(Math.max(0, parseInt(group.pool_passes, 10) || 0), qty);
            group.pool_passes = pQty;
            if (pQty === qty && pQty > 0) {
                poolBadgeHtml = `<span class="inline-flex h-7 w-7 items-center justify-center rounded-xl border border-sky-500/30 bg-sky-500/15 text-sky-600 dark:text-sky-400 shadow-2xs shrink-0" title="Pool Pass Included for all ${qty} guests"><i class="bi bi-water text-xs"></i></span>`;
            } else if (pQty > 0) {
                poolBadgeHtml = `<div class="inline-flex items-center gap-1 h-7 rounded-xl border border-sky-500/30 bg-sky-500/15 px-2 text-xs font-bold text-sky-700 dark:text-sky-300 shadow-2xs shrink-0" title="Pool passes for ${pQty} of ${qty} guests">
                    <i class="bi bi-water text-xs"></i>
                    <span>${pQty}/${qty}</span>
                </div>`;
            }

            let amenityBenefitBadge = '';
            if (group.has_free_entrance_benefit || group.has_free_pool_benefit) {
                const ben = [];
                if (group.has_free_entrance_benefit) ben.push('Free Entrance');
                if (group.has_free_pool_benefit) ben.push('Free Pool');
                amenityBenefitBadge = `
                    <span class="inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded bg-emerald-500/15 border border-emerald-500/30 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800 dark:text-emerald-300" title="${escapeHtml(ben.join(' & '))} Included with Amenity">
                        <i class="bi bi-gift-fill text-[10px]"></i> ${escapeHtml(ben.join(' & '))}
                    </span>
                `;
            }

            const amenityHtml = group.amenity_name ? `
                <span class="text-neutral-300 dark:text-neutral-600 shrink-0">&bull;</span>
                <span class="inline-flex shrink-0 items-center gap-1 whitespace-nowrap text-[11px] font-semibold text-emerald-700 dark:text-emerald-400" title="${escapeHtml(group.amenity_name)}"><i class="bi bi-geo-alt-fill shrink-0 text-[10px]"></i> <span>${escapeHtml(group.amenity_name)}</span></span>
            ` : '';

            return `
                <div class="flex min-w-full w-max items-center justify-between gap-3 rounded-lg border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 px-2.5 py-2 transition-colors hover:border-emerald-600/40">
                    <div class="flex shrink-0 items-center gap-1.5 whitespace-nowrap text-[11px] text-neutral-700 dark:text-neutral-200">
                        <span class="shrink-0 font-bold text-neutral-900 dark:text-white">${qty} ${qty === 1 ? 'guest' : 'guests'}</span>
                        <span class="shrink-0 text-neutral-300 dark:text-neutral-600">&bull;</span>
                        <span class="shrink-0">${genderBadge}</span>
                        <span class="shrink-0 text-neutral-300 dark:text-neutral-600">&bull;</span>
                        <span class="shrink-0">${escapeHtml(group.age_group)} (${rateLabel})</span>
                        <span class="shrink-0 text-neutral-300 dark:text-neutral-600">&bull;</span>
                        <span class="shrink-0">${nationality}</span>
                        ${amenityHtml}
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5">
                        ${amenityBenefitBadge}
                        ${freeBadgeHtml}
                        ${poolBadgeHtml}
                        <div class="flex items-center gap-1 border-l border-neutral-200 dark:border-neutral-800 pl-1.5">
                            <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300 hover:border-emerald-600 hover:text-emerald-700 transition-colors cursor-pointer text-xs" data-res-add-open-group-edit="${groupIndex}" title="Edit group">
                                <i class="bi bi-pencil-fill text-[11px]"></i>
                            </button>
                            <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg border border-red-200 dark:border-red-900/50 bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 hover:bg-red-600 hover:text-white transition-colors cursor-pointer text-xs" data-res-add-remove-index="${groupIndex}" title="Remove companion group">
                                <i class="bi bi-trash3 text-[11px]"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    };

    const resAddClearPreview = () => {
        resAddStagedCompanions = [];
        resAddRenderPreview();
        resAddSyncAmenityDropdown(false);
    };

    const resAddStageBulk = () => {
        if (!resAddBulkForm) return;
        const formData = new FormData(resAddBulkForm);
        const quantity = Math.min(Math.max(parseInt(formData.get('quantity'), 10) || 1, 1), 500);
        const freePassesInput = document.getElementById('resadd_bulk_free_passes');
        const rawFreePasses = freePassesInput ? parseInt(freePassesInput.value, 10) : 0;
        let freePasses = Math.min(Math.max(isNaN(rawFreePasses) ? 0 : rawFreePasses, 0), quantity);
        const poolPassesInput = document.getElementById('resadd_bulk_pool_passes');
        const rawPoolPasses = poolPassesInput ? parseInt(poolPassesInput.value, 10) : 0;
        const bulkPoolCheckbox = document.getElementById('resadd_bulk_has_pool');
        const bulkPoolChecked = Boolean(bulkPoolCheckbox?.checked || formData.get('bulk_has_pool_access') === '1');
        let poolPasses = 0;

        const gender = formData.get('gender') || 'Male';
        const isForeigner = formData.get('is_foreigner') === '1';
        const ageGroup = formData.get('age_group') || '18-59';
        const ageMidpoint = { '0-12': 6, '13-17': 15, '18-59': 30, '60+': 65, 'Kids (0-12)': 6, 'Teens (13-17)': 15, 'Adults (18-59)': 30, 'Seniors (60+)': 65 };
        const approxAge = ageMidpoint[ageGroup] || 30;
        const amenityId = String(formData.get('amenity_id') || '');

        let amenityName = '';
        let hasFreeEntranceBenefit = false;
        let hasFreePoolBenefit = false;
        if (amenityId === 'all') {
            amenityName = 'All Availed Amenities (No Extra Head Charge)';
            const res = (window.staffReservationData && window.staffReservationData[ctx.getCurrentReservationId()])
                || (typeof reservationData !== 'undefined' ? reservationData[ctx.getCurrentReservationId()] : null);
            const resAmenities = res?.reservation_amenities || [];
            const nonChargeable = resAmenities.filter(ra => (parseFloat(ra.amenity?.additional_per_head || ra.additional_per_head) || 0) <= 0);
            hasFreeEntranceBenefit = nonChargeable.some(ra => Boolean(ra.free_entrance || ra.amenity?.benefits?.free_entrance));
            hasFreePoolBenefit = nonChargeable.some(ra => Boolean(ra.free_pool || ra.amenity?.benefits?.free_pool));
        } else if (amenityId && amenityId !== 'no_amenity') {
            const res = (window.staffReservationData && window.staffReservationData[ctx.getCurrentReservationId()])
                || (typeof reservationData !== 'undefined' ? reservationData[ctx.getCurrentReservationId()] : null);
            const resAmenities = res?.reservation_amenities || [];
            const found = resAmenities.find(ra => String(ra.amenity_id || ra.amenity?.id || ra.id) === amenityId);
            if (found) {
                amenityName = found.amenity?.amenities_name || found.amenity_name || '';
                hasFreeEntranceBenefit = Boolean(found.free_entrance || found.amenity?.benefits?.free_entrance);
                hasFreePoolBenefit = Boolean(found.free_pool || found.amenity?.benefits?.free_pool);
            }
        }

        if (hasFreeEntranceBenefit) {
            freePasses = quantity;
        }
        if (hasFreePoolBenefit || bulkPoolChecked) {
            poolPasses = quantity;
        } else {
            poolPasses = 0;
        }
        if (hasFreePoolBenefit) {
            poolPasses = quantity;
        }
        if (!amenityId || amenityId === 'no_amenity') {
            hasFreeEntranceBenefit = false;
            hasFreePoolBenefit = false;
        }

        // Prevent repeating same companion group: merge into existing group if same characteristics
        const existing = resAddStagedCompanions.find(c =>
            c.is_group &&
            c.gender === gender &&
            Boolean(c.is_foreigner) === isForeigner &&
            c.age_group === ageGroup &&
            String(c.amenity_id || '') === amenityId
        );

        if (existing) {
            existing.quantity = Math.min(existing.quantity + quantity, 500);
            existing.free_passes = Math.min(existing.quantity, (existing.free_passes || 0) + freePasses);
            existing.pool_passes = Math.min(existing.quantity, (existing.pool_passes || 0) + poolPasses);
            if (hasFreeEntranceBenefit) existing.has_free_entrance_benefit = true;
            if (hasFreePoolBenefit) existing.has_free_pool_benefit = true;
            showToast(`Updated existing ${gender} (${ageGroup}) group (+${quantity} guests).`);
        } else {
            resAddStagedCompanions.push({
                is_group: true,
                gender,
                age_group: ageGroup,
                age: approxAge,
                is_foreigner: isForeigner,
                amenity_id: amenityId,
                amenity_name: amenityName,
                has_free_entrance_benefit: hasFreeEntranceBenefit,
                has_free_pool_benefit: hasFreePoolBenefit,
                quantity,
                free_passes: freePasses,
                pool_passes: poolPasses,
            });
        }

        resAddRenderPreview();

        // Reset bulk inputs back to defaults
        const qtyInput = document.getElementById('resadd_bulk_quantity');
        if (qtyInput) qtyInput.value = '1';
        if (freePassesInput) freePassesInput.value = '0';
        if (poolPassesInput) poolPassesInput.value = '0';
        resAddSyncAmenityDropdown(false);
        resAddSyncAmenityBenefits(true);
        resAddSyncBulkHints();
    };

    const resAddRemoveStaged = (index) => {
        resAddStagedCompanions.splice(index, 1);
        resAddRenderPreview();
        resAddSyncAmenityDropdown(true);
    };

    // ── Group Edit Modal Logic for Reservation ──
    const openResAddGroupEditModal = (groupIndex) => {
        const group = resAddStagedCompanions[groupIndex];
        if (!group || !resAddGroupEditModal) return;

        if (resAddGroupEditIndex) resAddGroupEditIndex.value = groupIndex;
        const nationality = group.is_foreigner ? 'Foreigner' : 'Filipino';
        const rateLabel = (group.age_group === '0-12' || group.age_type === 'child') ? 'Child' : 'Adult';

        if (resAddGroupEditDemographicsBadge) {
            resAddGroupEditDemographicsBadge.innerHTML = `
                ${getGenderBadgeHtml(group.gender)}
                <span class="text-xs font-bold text-hp-text dark:text-gray-100">Age ${escapeHtml(group.age_group)} <span class="font-normal text-hp-text-muted">(${rateLabel})</span></span>
                <span class="text-hp-text-muted/40">•</span>
                <span class="inline-flex items-center text-xs font-medium text-hp-text-muted">${nationality}</span>
            `;
            resAddGroupEditDemographicsBadge.className = 'flex flex-wrap items-center gap-2';
        }

        if (resAddGroupEditAmenityBadge) {
            if (group.amenity_name) {
                resAddGroupEditAmenityBadge.innerHTML = `<i class="bi bi-geo-alt-fill me-1"></i>${escapeHtml(group.amenity_name)}`;
                resAddGroupEditAmenityBadge.classList.remove('hidden');
            } else {
                resAddGroupEditAmenityBadge.classList.add('hidden');
            }
        }

        const qty = parseInt(group.quantity, 10) || 1;
        if (resAddGroupEditQuantityInput) {
            resAddGroupEditQuantityInput.value = qty;
        }

        const curFree = Math.min(Math.max(0, parseInt(group.free_passes, 10) || 0), qty);
        if (resAddGroupEditFreeInput) {
            resAddGroupEditFreeInput.value = curFree;
            resAddGroupEditFreeInput.max = qty;
        }
        if (resAddGroupEditFreeHint) {
            resAddGroupEditFreeHint.textContent = `${curFree} of ${qty}`;
        }

        const curPool = Math.min(Math.max(0, parseInt(group.pool_passes, 10) || 0), qty);
        if (resAddGroupEditPoolInput) {
            resAddGroupEditPoolInput.value = curPool;
            resAddGroupEditPoolInput.max = qty;
        }
        if (resAddGroupEditPoolHint) {
            resAddGroupEditPoolHint.textContent = `${curPool} of ${qty}`;
        }

        // Entrance fee is automated: hide free entrance wrapper
        const groupEditFreeWrap = document.getElementById('resAddGroupEditFreeWrap');
        if (groupEditFreeWrap) groupEditFreeWrap.style.display = 'none';

        // Check if group has free pool benefit
        let hasFreePoolBenefit = Boolean(group.has_free_pool_benefit);
        if (!hasFreePoolBenefit && group.amenity_id) {
            const res = (window.staffReservationData && window.staffReservationData[ctx.getCurrentReservationId()])
                || (typeof reservationData !== 'undefined' ? reservationData[ctx.getCurrentReservationId()] : null);
            const resAmenities = res?.reservation_amenities || [];
            const found = resAmenities.find(ra => String(ra.amenity_id || ra.amenity?.id || ra.id) === String(group.amenity_id));
            if (found && (found.free_pool || found.amenity?.benefits?.free_pool)) {
                hasFreePoolBenefit = true;
            }
        }

        const groupEditAccessRow = document.getElementById('resAddGroupEditAccessRow');
        const groupEditPoolWrap = document.getElementById('resAddGroupEditPoolWrap');
        if (hasFreePoolBenefit) {
            if (groupEditPoolWrap) groupEditPoolWrap.style.display = 'none';
            if (groupEditAccessRow) groupEditAccessRow.style.display = 'none';
        } else {
            if (groupEditPoolWrap) groupEditPoolWrap.style.display = 'flex';
            if (groupEditAccessRow) groupEditAccessRow.style.display = 'grid';
        }

        resAddGroupEditModal.classList.add('is-open');
        resAddGroupEditModal.classList.remove('hidden');
        resAddGroupEditModal.setAttribute('aria-hidden', 'false');
    };

    const closeResAddGroupEditModal = () => {
        if (!resAddGroupEditModal) return;
        resAddGroupEditModal.classList.remove('is-open');
        resAddGroupEditModal.classList.add('hidden');
        resAddGroupEditModal.setAttribute('aria-hidden', 'true');
    };

    document.querySelectorAll('[data-close-res-group-edit-modal="true"]').forEach(btn => {
        btn.addEventListener('click', closeResAddGroupEditModal);
    });

    const syncResAddGroupEditHints = () => {
        const qty = Math.max(1, parseInt(resAddGroupEditQuantityInput?.value, 10) || 1);
        if (resAddGroupEditFreeInput) {
            resAddGroupEditFreeInput.max = qty;
            let freeVal = parseInt(resAddGroupEditFreeInput.value, 10) || 0;
            if (freeVal > qty) freeVal = qty;
            if (freeVal < 0) freeVal = 0;
            resAddGroupEditFreeInput.value = freeVal;
            if (resAddGroupEditFreeHint) {
                resAddGroupEditFreeHint.textContent = `${freeVal} of ${qty}`;
            }
        }
        if (resAddGroupEditPoolInput) {
            resAddGroupEditPoolInput.max = qty;
            let poolVal = parseInt(resAddGroupEditPoolInput.value, 10) || 0;
            if (poolVal > qty) poolVal = qty;
            if (poolVal < 0) poolVal = 0;
            resAddGroupEditPoolInput.value = poolVal;
            if (resAddGroupEditPoolHint) {
                resAddGroupEditPoolHint.textContent = `${poolVal} of ${qty}`;
            }
        }
    };

    resAddGroupEditQtyMinusBtn?.addEventListener('click', () => {
        if (!resAddGroupEditQuantityInput) return;
        const cur = parseInt(resAddGroupEditQuantityInput.value, 10) || 1;
        if (cur > 1) {
            resAddGroupEditQuantityInput.value = cur - 1;
            syncResAddGroupEditHints();
        }
    });

    resAddGroupEditQtyPlusBtn?.addEventListener('click', () => {
        if (!resAddGroupEditQuantityInput) return;
        const cur = parseInt(resAddGroupEditQuantityInput.value, 10) || 1;
        if (cur < 500) {
            resAddGroupEditQuantityInput.value = cur + 1;
            syncResAddGroupEditHints();
        }
    });

    resAddGroupEditQuantityInput?.addEventListener('input', syncResAddGroupEditHints);

    resAddGroupEditFreeMinusBtn?.addEventListener('click', () => {
        if (!resAddGroupEditFreeInput) return;
        const cur = parseInt(resAddGroupEditFreeInput.value, 10) || 0;
        if (cur > 0) {
            resAddGroupEditFreeInput.value = cur - 1;
            syncResAddGroupEditHints();
        }
    });

    resAddGroupEditFreePlusBtn?.addEventListener('click', () => {
        if (!resAddGroupEditFreeInput || !resAddGroupEditQuantityInput) return;
        const max = Math.max(1, parseInt(resAddGroupEditQuantityInput.value, 10) || 1);
        const cur = parseInt(resAddGroupEditFreeInput.value, 10) || 0;
        if (cur < max) {
            resAddGroupEditFreeInput.value = cur + 1;
            syncResAddGroupEditHints();
        }
    });

    resAddGroupEditFreeInput?.addEventListener('input', syncResAddGroupEditHints);

    resAddGroupEditFreeZeroBtn?.addEventListener('click', () => {
        if (!resAddGroupEditFreeInput) return;
        resAddGroupEditFreeInput.value = 0;
        syncResAddGroupEditHints();
    });

    resAddGroupEditFreeAllBtn?.addEventListener('click', () => {
        if (!resAddGroupEditFreeInput || !resAddGroupEditQuantityInput) return;
        const max = Math.max(1, parseInt(resAddGroupEditQuantityInput.value, 10) || 1);
        resAddGroupEditFreeInput.value = max;
        syncResAddGroupEditHints();
    });

    resAddGroupEditPoolMinusBtn?.addEventListener('click', () => {
        if (!resAddGroupEditPoolInput) return;
        const cur = parseInt(resAddGroupEditPoolInput.value, 10) || 0;
        if (cur > 0) {
            resAddGroupEditPoolInput.value = cur - 1;
            syncResAddGroupEditHints();
        }
    });

    resAddGroupEditPoolPlusBtn?.addEventListener('click', () => {
        if (!resAddGroupEditPoolInput || !resAddGroupEditQuantityInput) return;
        const max = Math.max(1, parseInt(resAddGroupEditQuantityInput.value, 10) || 1);
        const cur = parseInt(resAddGroupEditPoolInput.value, 10) || 0;
        if (cur < max) {
            resAddGroupEditPoolInput.value = cur + 1;
            syncResAddGroupEditHints();
        }
    });

    resAddGroupEditPoolInput?.addEventListener('input', syncResAddGroupEditHints);

    resAddGroupEditPoolZeroBtn?.addEventListener('click', () => {
        if (!resAddGroupEditPoolInput) return;
        resAddGroupEditPoolInput.value = 0;
        syncResAddGroupEditHints();
    });

    resAddGroupEditPoolAllBtn?.addEventListener('click', () => {
        if (!resAddGroupEditPoolInput || !resAddGroupEditQuantityInput) return;
        const max = Math.max(1, parseInt(resAddGroupEditQuantityInput.value, 10) || 1);
        resAddGroupEditPoolInput.value = max;
        syncResAddGroupEditHints();
    });

    resAddGroupEditForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const gIdx = parseInt(resAddGroupEditIndex?.value, 10);
        if (isNaN(gIdx) || !resAddStagedCompanions[gIdx]) return;

        const group = resAddStagedCompanions[gIdx];
        const newQty = Math.max(1, parseInt(resAddGroupEditQuantityInput?.value, 10) || 1);
        group.quantity = newQty;

        // Automated entrance: free if amenity includes free entrance, otherwise 0
        if (group.has_free_entrance_benefit) {
            group.free_passes = newQty;
        } else {
            group.free_passes = 0;
        }

        // Pool passes: free if amenity includes free pool, otherwise from pool input
        if (group.has_free_pool_benefit) {
            group.pool_passes = newQty;
        } else {
            group.pool_passes = Math.min(Math.max(0, parseInt(resAddGroupEditPoolInput?.value, 10) || 0), newQty);
        }

        resAddRenderPreview();
        resAddSyncAmenityDropdown(true);
        closeResAddGroupEditModal();
        showToast('Companion group updated.');
    });

    // ── Amenity Benefit Synchronization for Active Reservation Add Companion ──
    resAddSyncAmenityBenefits = (isAmenityChange = false) => {
        const qtyInput = document.getElementById('resadd_bulk_quantity');
        const freeInput = document.getElementById('resadd_bulk_free_passes');
        const poolInput = document.getElementById('resadd_bulk_pool_passes');
        const bulkSelect = document.getElementById('resadd_bulk_amenity');
        const benefitBadge = document.getElementById('resaddBulkAmenityBenefitBadge');
        const benefitText = document.getElementById('resaddBulkAmenityBenefitText');
        const bulkAccessRow = document.getElementById('resAddBulkAccessRow');
        const bulkPoolWrap = document.getElementById('resAddBulkPoolWrap');
        const bulkFreeEntranceWrap = document.getElementById('resAddBulkFreeEntranceWrap');

        const res = (window.staffReservationData && window.staffReservationData[ctx.getCurrentReservationId()])
            || (typeof reservationData !== 'undefined' ? reservationData[ctx.getCurrentReservationId()] : null);
        const resAmenities = res?.reservation_amenities || [];
        const selectedAmId = String(bulkSelect?.value || '');

        let hasFreeEnt = false;
        let hasFreePl = false;
        if (selectedAmId === 'all') {
            const nonChargeable = resAmenities.filter(ra => (parseFloat(ra.amenity?.additional_per_head || ra.additional_per_head) || 0) <= 0);
            hasFreeEnt = nonChargeable.some(ra => Boolean(ra.free_entrance || ra.amenity?.benefits?.free_entrance));
            hasFreePl = nonChargeable.some(ra => Boolean(ra.free_pool || ra.amenity?.benefits?.free_pool));
        } else if (selectedAmId && selectedAmId !== 'no_amenity') {
            const found = resAmenities.find(ra => String(ra.amenity_id || ra.amenity?.id || ra.id) === selectedAmId);
            hasFreeEnt = Boolean(found && (found.free_entrance || found.amenity?.benefits?.free_entrance));
            hasFreePl = Boolean(found && (found.free_pool || found.amenity?.benefits?.free_pool));
        }

        const qty = Math.max(parseInt(qtyInput?.value, 10) || 1, 1);

        // Entrance fee is automated: hide entrance checkbox/counter permanently
        if (bulkFreeEntranceWrap) {
            bulkFreeEntranceWrap.style.display = 'none';
        }
        if (freeInput) {
            freeInput.value = hasFreeEnt ? qty : 0;
            freeInput.max = qty;
        }

        // Pool Access:
        const bulkPoolCheckbox = document.getElementById('resadd_bulk_has_pool');
        const bulkPoolBadge = document.getElementById('resAddBulkPoolBenefitBadge');
        const bulkPoolSubtext = document.getElementById('resAddBulkPoolSubtext');

        if (bulkPoolWrap) bulkPoolWrap.style.display = 'flex';
        if (bulkAccessRow) bulkAccessRow.style.display = 'grid';

        if (bulkPoolCheckbox) {
            if (hasFreePl) {
                bulkPoolCheckbox.checked = true;
                bulkPoolCheckbox.disabled = true;
                if (bulkPoolBadge) bulkPoolBadge.classList.remove('hidden');
                if (bulkPoolSubtext) bulkPoolSubtext.textContent = 'Complimentary with assigned amenity';
                if (poolInput) poolInput.value = qty;
            } else {
                bulkPoolCheckbox.disabled = false;
                if (bulkPoolCheckbox.dataset.manuallyChecked !== 'true') {
                    bulkPoolCheckbox.checked = false;
                }
                if (bulkPoolBadge) bulkPoolBadge.classList.add('hidden');
                if (bulkPoolSubtext) bulkPoolSubtext.textContent = 'Standard pool access fee applies (₱40/guest)';
                if (poolInput) poolInput.value = bulkPoolCheckbox.checked ? qty : 0;
            }
        }

        if (hasFreeEnt || hasFreePl) {
            const parts = [];
            if (hasFreeEnt) parts.push('Free Entrance');
            if (hasFreePl) parts.push('Free Pool Access');
            if (benefitBadge) {
                benefitBadge.classList.remove('hidden');
                if (benefitText) benefitText.textContent = `Includes ${parts.join(' & ')}`;
            }
        } else {
            if (benefitBadge) benefitBadge.classList.add('hidden');
        }

        resAddSyncBulkHints();
    };

    // ── Dropdown and Live Counter Synchronization for Active Reservation Add Companion ──
    resAddSyncAmenityDropdown = (preserveSelection = false) => {
        const res = (window.staffReservationData && window.staffReservationData[ctx.getCurrentReservationId()])
            || (typeof reservationData !== 'undefined' ? reservationData[ctx.getCurrentReservationId()] : null);
        const resAmenities = res?.reservation_amenities || [];
        const bulkWrap = document.getElementById('resaddBulkAmenityWrap');
        const bulkSelect = document.getElementById('resadd_bulk_amenity');
        if (!bulkSelect) return;

        if (!resAmenities.length) {
            bulkSelect.innerHTML = '<option value="" selected>No amenity</option>';
            bulkSelect.value = '';
            if (bulkWrap) bulkWrap.style.display = 'none';
            return;
        }

        if (bulkWrap) bulkWrap.style.display = 'block';

        // ONLY include amenities with additional per head charges in the capacity tracking feature
        const amenitiesWithExtraFee = resAmenities.filter(ra => {
            const am = ra.amenity || ra;
            return (parseFloat(am.additional_per_head) || 0) > 0;
        });

        const amenityStats = [];

        amenitiesWithExtraFee.forEach(ra => {
            const am = ra.amenity || ra;
            const amId = String(ra.amenity_id || am.id || ra.id || '');
            const name = am.amenities_name || ra.amenity_name || 'Amenity';
            const maxCap = (am.maximum_capacity !== null && am.maximum_capacity !== undefined && am.maximum_capacity !== '')
                ? parseInt(am.maximum_capacity, 10) : null;
            const perHeadFee = parseFloat(am.additional_per_head) || 0;

            // Checked-in guests currently assigned to this amenity
            const checkedInCount = (res?.reservation_guests || []).filter(g => {
                if (g.checked_out_at) return false;
                const gAmId = g.amenity_id ? String(g.amenity_id) : '';
                if (gAmId) return gAmId === amId;
                if (resAmenities.length === 1) return true;
                return false;
            }).length;

            // Staged companions assigned to this amenity
            const stagedCount = resAddStagedCompanions
                .filter(c => String(c.amenity_id || '') === amId)
                .reduce((sum, c) => sum + (parseInt(c.quantity, 10) || 1), 0);

            const totalAssigned = checkedInCount + stagedCount;
            const isFull = maxCap !== null && totalAssigned >= maxCap;

            amenityStats.push({
                amId,
                name,
                maxCap,
                perHeadFee,
                hasPerHeadCharge: true,
                totalAssigned,
                isFull,
                freeEntrance: Boolean(ra.free_entrance || am.benefits?.free_entrance),
                freePool: Boolean(ra.free_pool || am.benefits?.free_pool),
            });
        });

        const nonChargeableAmenities = resAmenities.filter(ra => {
            const am = ra.amenity || {};
            return (parseFloat(am.additional_per_head || ra.additional_per_head) || 0) <= 0;
        });

        // Determine ideal default:
        // 1. If there are amenities with additional per head charges: default is the first non-full one
        // 2. If all full or none: default is 'all' (if non-chargeable amenities exist) or '' (no amenity)
        let idealDefault = nonChargeableAmenities.length > 0 ? 'all' : '';
        if (amenityStats.length > 0) {
            const availableWithCharge = amenityStats.find(a => !a.isFull);
            if (availableWithCharge) {
                idealDefault = availableWithCharge.amId;
            } else {
                idealDefault = nonChargeableAmenities.length > 0 ? 'all' : '';
            }
        } else {
            idealDefault = nonChargeableAmenities.length > 0 ? 'all' : '';
        }

        let optionsHtml = '';
        if (amenityStats.length > 0) {
            amenityStats.forEach(item => {
                let capStr = '';
                if (item.maxCap !== null) {
                    capStr = item.isFull ? `${item.totalAssigned}/${item.maxCap} - Full` : `${item.totalAssigned}/${item.maxCap}`;
                } else {
                    capStr = `${item.totalAssigned} assigned`;
                }

                const feeStr = ` • +₱${item.perHeadFee.toFixed(2)}/extra head`;
                const benefits = [];
                if (item.freeEntrance) benefits.push('Free Entrance');
                if (item.freePool) benefits.push('Free Pool');
                const benStr = benefits.length > 0 ? ` • ${benefits.join(' + ')}` : '';

                optionsHtml += `<option value="${item.amId}">${escapeHtml(item.name)} (${capStr}${feeStr}${benStr})</option>`;
            });
        }

        if (nonChargeableAmenities.length > 0) {
            optionsHtml += '<option value="all">All Availed Amenities (No Extra Head Charge)</option>';
        }
        optionsHtml += '<option value="">No amenity</option>';

        const currentVal = bulkSelect.value;
        bulkSelect.innerHTML = optionsHtml;

        if (preserveSelection && currentVal) {
            const currentItem = amenityStats.find(a => a.amId === currentVal);
            if (currentItem && currentItem.isFull) {
                bulkSelect.value = idealDefault;
            } else if (currentVal === 'all' || currentItem) {
                bulkSelect.value = currentVal;
            } else {
                bulkSelect.value = idealDefault;
            }
        } else {
            bulkSelect.value = idealDefault;
        }

        resAddSyncAmenityBenefits(false);
    };

    // ── Watchers for Bulk Add Creator Steppers ──
    const resAddBindBulkWatchers = () => {
        const qtyInput = document.getElementById('resadd_bulk_quantity');
        const freeInput = document.getElementById('resadd_bulk_free_passes');
        const poolInput = document.getElementById('resadd_bulk_pool_passes');
        const bulkSelect = document.getElementById('resadd_bulk_amenity');

        bulkSelect?.addEventListener('change', () => resAddSyncAmenityBenefits(true));

        qtyInput?.addEventListener('input', () => {
            resAddSyncAmenityBenefits(false);
            resAddSyncBulkHints();
        });
        freeInput?.addEventListener('input', resAddSyncBulkHints);
        poolInput?.addEventListener('input', resAddSyncBulkHints);

        resAddBulkQtyMinus?.addEventListener('click', () => {
            if (!qtyInput) return;
            const cur = parseInt(qtyInput.value, 10) || 1;
            if (cur > 1) {
                qtyInput.value = cur - 1;
                resAddSyncAmenityBenefits(false);
                resAddSyncBulkHints();
            }
        });

        resAddBulkQtyPlus?.addEventListener('click', () => {
            if (!qtyInput) return;
            const cur = parseInt(qtyInput.value, 10) || 1;
            if (cur < 500) {
                qtyInput.value = cur + 1;
                resAddSyncAmenityBenefits(false);
                resAddSyncBulkHints();
            }
        });

        resAddBulkFreeMinus?.addEventListener('click', () => {
            if (!freeInput) return;
            const cur = parseInt(freeInput.value, 10) || 0;
            if (cur > 0) {
                freeInput.value = cur - 1;
                resAddSyncBulkHints();
            }
        });

        resAddBulkFreePlus?.addEventListener('click', () => {
            if (!freeInput || !qtyInput) return;
            const max = Math.max(1, parseInt(qtyInput.value, 10) || 1);
            const cur = parseInt(freeInput.value, 10) || 0;
            if (cur < max) {
                freeInput.value = cur + 1;
                resAddSyncBulkHints();
            }
        });

        resAddBulkPoolMinus?.addEventListener('click', () => {
            if (!poolInput) return;
            const cur = parseInt(poolInput.value, 10) || 0;
            if (cur > 0) {
                poolInput.value = cur - 1;
                resAddSyncBulkHints();
            }
        });

        resAddBulkPoolPlus?.addEventListener('click', () => {
            if (!poolInput || !qtyInput) return;
            const max = Math.max(1, parseInt(qtyInput.value, 10) || 1);
            const cur = parseInt(poolInput.value, 10) || 0;
            if (cur < max) {
                poolInput.value = cur + 1;
                resAddSyncBulkHints();
            }
        });
    };

    const openResAddCompanionModal = (resId) => {
        if (!resAddCompanionModal) return;
        if (typeof resId === 'string' || typeof resId === 'number') {
            ctx.setCurrentReservationId(String(resId));
        }
        try {
            if (window.parkSettings) {
                ctx.mergeParkSettings(window.parkSettings);
            }
            loadParkSettings();

            const activeResId = ctx.getCurrentReservationId();
            if (resAddCompanionFor && activeResId) {
                resAddCompanionFor.textContent = `Reservation #${formatReservationId(activeResId)}`;
            }

            if (resAddAllCompanionsBtn) {
                resAddAllCompanionsBtn.innerHTML = '<i class="bi bi-receipt text-base"></i> <span>Review & Confirm</span>';
                resAddAllCompanionsBtn.disabled = true;
            }

            // Reset bulk inputs back to initial fresh defaults
            const qtyInput = document.getElementById('resadd_bulk_quantity');
            const freeInput = document.getElementById('resadd_bulk_free_passes');
            const poolInput = document.getElementById('resadd_bulk_pool_passes');
            if (qtyInput) qtyInput.value = '1';
            if (freeInput) {
                freeInput.value = '0';
                freeInput.max = '1';
            }
            if (poolInput) {
                poolInput.value = '0';
                poolInput.max = '1';
            }

            resAddClearPreview();
            resAddSyncAmenityDropdown(false);
            resAddUpdateCalculationUI();
        } catch (err) {
            console.error('[AddCompanion] sync error:', err);
        }
        resAddCompanionModal.classList.add('is-open');
        resAddCompanionModal.classList.remove('hidden');
        resAddCompanionModal.style.display = 'flex';
        resAddCompanionModal.setAttribute('aria-hidden', 'false');
    };

    const closeResAddCompanionModal = () => {
        closeResAddConfirmModal();
        resAddCompanionModal.classList.remove('is-open');
        resAddCompanionModal.classList.add('hidden');
        resAddCompanionModal.style.display = 'none';
        resAddCompanionModal.setAttribute('aria-hidden', 'true');
        resAddBulkForm?.reset();
        const qtyInput = document.getElementById('resadd_bulk_quantity');
        if (qtyInput) qtyInput.value = '1';
        const freeInput = document.getElementById('resadd_bulk_free_passes');
        if (freeInput) freeInput.value = '0';
        const poolInput = document.getElementById('resadd_bulk_pool_passes');
        if (poolInput) poolInput.value = '0';
        const benefitBadge = document.getElementById('resaddBulkAmenityBenefitBadge');
        if (benefitBadge) benefitBadge.classList.add('hidden');
        resAddClearPreview();
        resAddSyncAmenityBenefits(true);
        if (resAddAllCompanionsBtn) {
            resAddAllCompanionsBtn.innerHTML = '<i class="bi bi-receipt text-base"></i> <span>Review & Confirm</span>';
            resAddAllCompanionsBtn.disabled = true;
        }
    };

    reservationAddCompanionBtn?.addEventListener('click', openResAddCompanionModal);
    resAddCloseButtons.forEach(button => button.addEventListener('click', closeResAddCompanionModal));
    resAddCompanionClearBtn?.addEventListener('click', resAddClearPreview);

    resAddBindBulkWatchers();

    resAddPreviewList?.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-res-add-remove-index]');
        if (removeButton) {
            const index = parseInt(removeButton.dataset.resAddRemoveIndex, 10);
            if (!isNaN(index) && resAddStagedCompanions[index]) {
                resAddRemoveStaged(index);
            }
            return;
        }

        const editButton = event.target.closest('[data-res-add-open-group-edit]');
        if (editButton) {
            const index = parseInt(editButton.dataset.resAddOpenGroupEdit, 10);
            if (!isNaN(index) && resAddStagedCompanions[index]) {
                openResAddGroupEditModal(index);
            }
            return;
        }
    });

    const postCompanionsToReservation = async (stagedGroups, submitButton, originalText) => {
        if (!ctx.getCurrentReservationId() || !stagedGroups.length) return;

        // Flatten staged groups into individual companion records
        const flattenedCompanions = [];
        stagedGroups.forEach(group => {
            const qty = Math.max(parseInt(group.quantity, 10) || 1, 1);
            const freeCount = Math.min(Math.max(parseInt(group.free_passes, 10) || 0, 0), qty);
            const poolCount = Math.min(Math.max(parseInt(group.pool_passes, 10) || 0, 0), qty);

            for (let i = 0; i < qty; i++) {
                flattenedCompanions.push({
                    first_name: group.first_name || 'Companion',
                    last_name: group.last_name || 'Guest',
                    gender: group.gender || 'Male',
                    age_group: group.age_group || '18-59',
                    age: group.age || 30,
                    is_foreigner: Boolean(group.is_foreigner),
                    amenity_id: group.amenity_id || null,
                    is_free_entrance: i < freeCount,
                    free_entrance: i < freeCount,
                    has_pool_access: i < poolCount,
                    pool_access: i < poolCount,
                });
            }
        });

        if (!flattenedCompanions.length) return;

        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="inline-block animate-spin mr-1.5"><i class="bi bi-arrow-repeat"></i></span> Adding...';
        try {
            const response = await fetch(`/staff/reservations/${ctx.getCurrentReservationId()}/add-companion`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ companions: flattenedCompanions }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const firstError = payload.errors ? Object.values(payload.errors)[0]?.[0] : null;
                throw new Error(payload.message || firstError || 'Unable to add companion.');
            }

            // Sync updated reservation into in-memory dataset
            const targetRes = (window.staffReservationData && window.staffReservationData[ctx.getCurrentReservationId()])
                || (typeof reservationData !== 'undefined' && reservationData ? reservationData[ctx.getCurrentReservationId()] : null);

            if (targetRes) {
                if (payload.reservation_guests && Array.isArray(payload.reservation_guests)) {
                    targetRes.reservation_guests = payload.reservation_guests;
                } else {
                    if (!targetRes.reservation_guests) targetRes.reservation_guests = [];
                    const ageGroupMidpoint = { '0-12': 6, '13-17': 15, '18-59': 30, '60+': 65, 'Kids (0-12)': 6, 'Teens (13-17)': 15, 'Adults (18-59)': 30, 'Seniors (60+)': 65 };
                    flattenedCompanions.forEach(c => {
                        const poolAccess = Boolean(c.has_pool_access || c.pool_access);
                        const ageVal = c.age ? parseInt(c.age, 10) : (ageGroupMidpoint[c.age_group] || 30);
                        targetRes.reservation_guests.push({
                            id: 'temp_' + Date.now() + Math.random(),
                            customer: {
                                first_name: c.first_name || 'Companion',
                                last_name: c.last_name || 'Guest',
                                gender: c.gender || 'Male',
                                age: ageVal,
                                is_foreigner: Boolean(c.is_foreigner),
                                phone: c.phone || null,
                                email: c.email || null,
                            },
                            has_pool_access: poolAccess,
                            amenity_id: (c.amenity_id && c.amenity_id !== 'all' && c.amenity_id !== 'no_amenity') ? c.amenity_id : null,
                            is_primary_guest: false,
                            checked_out_at: null,
                        });
                    });
                }

                if (payload.number_of_guests) {
                    targetRes.number_of_guests = payload.number_of_guests;
                } else {
                    targetRes.number_of_guests = (parseInt(targetRes.number_of_guests || 0, 10) + flattenedCompanions.length);
                }

                if (payload.total_amount !== undefined) targetRes.total_amount = payload.total_amount;
                if (payload.amount_paid !== undefined) targetRes.amount_paid = payload.amount_paid;
                if (payload.remaining_balance !== undefined) targetRes.remaining_balance = payload.remaining_balance;

                if (window.staffReservationData) window.staffReservationData[ctx.getCurrentReservationId()] = targetRes;
                if (typeof reservationData !== 'undefined' && reservationData) reservationData[ctx.getCurrentReservationId()] = targetRes;

                openReservationModal(ctx.getCurrentReservationId());
            }

            closeResAddConfirmModal();
            closeResAddCompanionModal();
            const totalPay = payload.total_payment !== undefined ? parseFloat(payload.total_payment) : null;
            const payText = totalPay !== null ? ` Total Payment: ₱${totalPay.toFixed(2)}.` : '';
            showToast(`${payload.added || flattenedCompanions.length} companion${(payload.added || flattenedCompanions.length) > 1 ? 's' : ''} added to Reservation #${formatReservationId(ctx.getCurrentReservationId())}.${payText}`);
        } catch (error) {
            showToast(error.message || 'Unable to add companion.', 'error');
        } finally {
            if (submitButton) {
                submitButton.disabled = resAddStagedCompanions.length === 0;
                submitButton.innerHTML = `<i class="bi bi-check2-circle text-base"></i> <span>${originalText || 'Confirm & Add Companions'}</span>`;
            }
        }
    };

    resAddBulkForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        resAddStageBulk();
    });

    document.getElementById('resadd_bulk_has_pool')?.addEventListener('change', (e) => {
        if (!e.target.disabled) {
            e.target.dataset.manuallyChecked = e.target.checked ? 'true' : 'false';
            const poolInput = document.getElementById('resadd_bulk_pool_passes');
            const qtyInput = document.getElementById('resadd_bulk_quantity');
            const qty = Math.max(parseInt(qtyInput?.value, 10) || 1, 1);
            if (poolInput) {
                poolInput.value = e.target.checked ? qty : 0;
            }
        }
    });

    resAddAllCompanionsBtn?.addEventListener('click', () => {
        if (!resAddStagedCompanions.length) return;
        openResAddConfirmModal();
    });

    resAddConfirmSubmitBtn?.addEventListener('click', () => {
        postCompanionsToReservation(resAddStagedCompanions, resAddConfirmSubmitBtn, 'Confirm & Add Companions');
    });

    resAddConfirmCloseButtons.forEach(button => {
        button.addEventListener('click', closeResAddConfirmModal);
    });

    // Primary guest nationality handling
    const primaryGuestForm = document.getElementById('primaryGuestForm');
    if (primaryGuestForm) {
        const primaryGuestIsForeigner = document.getElementById('primaryGuestIsForeigner');
        primaryGuestIsForeigner?.addEventListener('change', (e) => {
            // Handle any UI changes if needed
        });
    }

    // If a companion was just added, auto-open that reservation's detail
    // modal so the addition is immediately visible under its reservation.
    const justAddedRes = (() => {
        try {
            const v = sessionStorage.getItem('hpJustAddedCompanionRes');
            if (v) sessionStorage.removeItem('hpJustAddedCompanionRes');
            return v;
        } catch (e) { return null; }
    })();
    if (justAddedRes && (window.staffReservationData?.[justAddedRes] || reservationData[justAddedRes])) {
        setTimeout(() => openReservationModal(justAddedRes), 450);
    }

    // Auto-open reservation modal if reservation_id is in URL (e.g. redirected from QR scanner or reservations page)
    const checkInsUrlParams = new URLSearchParams(window.location.search);
    const targetUrlResId = checkInsUrlParams.get('reservation_id') || checkInsUrlParams.get('id');
    if (targetUrlResId) {
        const cleanTargetId = String(targetUrlResId).replace(/\D/g, '');
        if (cleanTargetId) {
            const autoOpenTargetReservation = async () => {
                if (!reservationData[cleanTargetId] && !window.staffReservationData?.[cleanTargetId]) {
                    try {
                        const res = await fetch(`/staff/check-ins/lookup?reservation_id=${encodeURIComponent(cleanTargetId)}`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const body = await res.json().catch(() => ({}));
                        if (res.ok && body.reservation) {
                            if (!window.staffReservationData) window.staffReservationData = {};
                            window.staffReservationData[cleanTargetId] = body.reservation;
                            reservationData[cleanTargetId] = body.reservation;
                        }
                    } catch (_) {}
                }

                if (window.staffReservationData?.[cleanTargetId] || reservationData[cleanTargetId]) {
                    const row = document.querySelector(`tr[data-reservation-id="${cleanTargetId}"]`);
                    if (row) {
                        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        row.classList.add('bg-emerald-100/80', 'dark:bg-emerald-950/60');
                        setTimeout(() => row.classList.remove('bg-emerald-100/80', 'dark:bg-emerald-950/60'), 3000);
                    }
                    openReservationModal(cleanTargetId);
                }

                try {
                    const cleanUrl = window.location.pathname;
                    window.history.replaceState({ spa: true, url: cleanUrl }, '', cleanUrl);
                } catch (_) {}
            };
            setTimeout(autoOpenTargetReservation, 200);
        }
    }

    // Expose openReservationModal globally for hardware scanner and cross-module calls
    window.openReservationModal = openReservationModal;

    // Listen for direct hardware scan event on this page
    if (!window.__staffCheckInsScannerBound) {
        window.__staffCheckInsScannerBound = true;
        window.addEventListener('scanner:scanned', (e) => {
            const resId = e.detail?.reservationId;
            if (resId && (window.location.pathname === '/staff/check-ins' || window.location.pathname.endsWith('/staff/check-ins'))) {
                const cleanId = String(resId).replace(/\D/g, '');
                if (cleanId) {
                    if (window.staffReservationData?.[cleanId] || reservationData[cleanId]) {
                        openReservationModal(cleanId);
                    } else {
                        fetch(`/staff/check-ins/lookup?reservation_id=${encodeURIComponent(cleanId)}`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        }).then(r => r.json()).then(body => {
                            if (body.reservation) {
                                if (!window.staffReservationData) window.staffReservationData = {};
                                window.staffReservationData[cleanId] = body.reservation;
                                reservationData[cleanId] = body.reservation;
                                openReservationModal(cleanId);
                            }
                        }).catch(() => {});
                    }
                }
            }
        });
    }
    return {
        monthNames,
        renderSelectedAmenities,
        resvResultsCount,
        stopQrScanner,
        openScanQrModal,
        closeScanQrModal,
        openResAddCompanionModal,
        closeResAddCompanionModal,
    };
}
