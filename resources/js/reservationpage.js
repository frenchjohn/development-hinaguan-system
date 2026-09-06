// Store per-amenity pricing type choices
const amenityPricingTypes = {};

document.addEventListener('DOMContentLoaded', () => {

    const siteHeader = document.getElementById('rpSiteHeader');

    const menuToggle = document.querySelector('.rp-menu-toggle');

    const mobileNav = document.querySelector('.rp-mobile-nav');

    const mobileLinks = mobileNav?.querySelectorAll('a');

    const animatedElements = document.querySelectorAll('[data-animate]');



    const syncHeaderOffset = () => {

        if (!siteHeader) return;

        document.documentElement.style.setProperty('--rp-header-offset', `${siteHeader.offsetHeight}px`);

    };



    syncHeaderOffset();

    window.addEventListener('resize', syncHeaderOffset, { passive: true });



    const updateOverlayScrollLock = () => {

        const hasOpenOverlay = Boolean(

            document.querySelector('.rp-modal.is-open, .rp-selection-sheet.is-open, .rp-mobile-nav.is-open')

        );

        document.body.style.overflow = hasOpenOverlay ? 'hidden' : '';

    };



    const closeMobileNav = () => {

        mobileNav?.classList.remove('is-open');

        menuToggle?.setAttribute('aria-expanded', 'false');

        updateOverlayScrollLock();

    };



    menuToggle?.addEventListener('click', () => {

        const isOpen = mobileNav?.classList.toggle('is-open');

        menuToggle.setAttribute('aria-expanded', String(isOpen));

        updateOverlayScrollLock();

    });



    mobileLinks?.forEach((link) => {

        link.addEventListener('click', closeMobileNav);

    });



    const animateObserver = new IntersectionObserver(

        (entries) => {

            entries.forEach((entry) => {

                if (!entry.isIntersecting) return;



                const el = entry.target;

                const delay = parseInt(el.dataset.delay ?? '0', 10);



                window.setTimeout(() => {

                    el.classList.add('is-visible');

                }, delay);



                animateObserver.unobserve(el);

            });

        },

        {

            rootMargin: '0px 0px -6% 0px',

            threshold: 0.08,

        }

    );



    animatedElements.forEach((el) => animateObserver.observe(el));



    document.querySelectorAll('.rp-hero [data-animate]').forEach((el, index) => {

        window.setTimeout(() => {

            el.classList.add('is-visible');

        }, 200 + index * 120);

    });



    const filterType = document.getElementById('filterType');

    const filterMin = document.getElementById('filterMin');

    const filterMax = document.getElementById('filterMax');

    const cards = Array.from(document.querySelectorAll('.rp-card'));

    const grid = document.getElementById('amenityGrid');

    const emptyState = document.getElementById('emptyState');

    const modal = document.getElementById('amenityModal');

    const modalClose = document.querySelectorAll('[data-close-modal]');

    const cancelConfirmModal = document.getElementById('cancelConfirmModal');

    // ── Terms & Policy Modal ──
    const termsPolicyModal = document.getElementById('termsPolicyModal');
    const agreeTermsCheckbox = document.getElementById('agreeTermsCheckbox');
    const proceedTermsBtn = document.getElementById('proceedTermsBtn');
    const openTermsPolicyBtn = document.getElementById('openTermsPolicyBtn');
    const closeTermsPolicyModalBtn = document.getElementById('closeTermsPolicyModalBtn');
    const termsPolicyBackdrop = document.getElementById('termsPolicyBackdrop');

    const openTermsModal = () => {
        if (!termsPolicyModal) return;
        termsPolicyModal.classList.add('is-open');
        termsPolicyModal.setAttribute('aria-hidden', 'false');
        updateOverlayScrollLock();
    };

    const closeTermsModal = () => {
        if (!termsPolicyModal) return;
        termsPolicyModal.classList.remove('is-open');
        termsPolicyModal.setAttribute('aria-hidden', 'true');
        updateOverlayScrollLock();
    };

    if (termsPolicyModal) {
        // Open modal automatically at the start of guest reservation page
        openTermsModal();

        // Checkbox listener to toggle Proceed button disabled state
        agreeTermsCheckbox?.addEventListener('change', () => {
            if (proceedTermsBtn) {
                proceedTermsBtn.disabled = !agreeTermsCheckbox.checked;
            }
        });

        // Proceed button click
        proceedTermsBtn?.addEventListener('click', () => {
            if (agreeTermsCheckbox && !agreeTermsCheckbox.checked) {
                return;
            }
            closeTermsModal();
        });

        // Reopen trigger buttons
        document.querySelectorAll('[data-open-terms-modal]').forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                openTermsModal();
            });
        });

        // Close buttons and backdrop
        document.querySelectorAll('[data-close-terms-modal]').forEach(btn => {
            btn.addEventListener('click', closeTermsModal);
        });

        // ESC key handler
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && termsPolicyModal.classList.contains('is-open')) {
                closeTermsModal();
            }
        });
    }

    const gridSkeleton = document.getElementById('gridSkeleton');

    const datePickerModal = document.getElementById('datePickerModal');

    const reservationDateTrigger = document.getElementById('reservationDateTrigger');

    const multiSelectionToggle = document.getElementById('multiSelectionToggle');

    const multiSelectionToggleBtn = document.getElementById('multiSelectionToggleBtn');

    const selectionFloatingBar = document.getElementById('selectionFloatingBar');

    const selectionCountLabel = document.getElementById('selectionCountLabel');

    const selectionCheckoutBtn = document.getElementById('selectionCheckoutBtn');

    const selectionSheet = document.getElementById('selectionSheet');

    const selectionSummaryList = document.getElementById('selectionSummaryList');

    const selectionContinueBtn = document.getElementById('selectionContinueBtn');

    const selectionCloseButtons = document.querySelectorAll('[data-close-selection]');

    const selectionMathText = document.getElementById('selectionMathText');

    const selectionTotalPrice = document.getElementById('selectionTotalPrice');

    const modalName = document.getElementById('modalName');

    const modalDate = document.getElementById('modalDate');

    const modalSlot = document.getElementById('modalSlot');

    const modalCapacity = document.getElementById('modalCapacity');

    const modalPriceLabel = document.getElementById('modalPriceLabel');

    const modalPriceValue = document.getElementById('modalPriceValue');

    const modalPriceHint = document.getElementById('modalPriceHint');

    const modalSaleInfo = document.getElementById('modalSaleInfo');

    const modalOriginalPrice = document.getElementById('modalOriginalPrice');

    const modalSalePercentage = document.getElementById('modalSalePercentage');

    const modalDescription = document.getElementById('modalDescription');

    const airconChoice = document.getElementById('airconChoice');

    const multiAirconModal = document.getElementById('multiAirconModal');

    const multiAirconName = document.getElementById('multiAirconName');

    const multiAirconDate = document.getElementById('multiAirconDate');

    const multiAirconSlot = document.getElementById('multiAirconSlot');

    const multiAirconCapacity = document.getElementById('multiAirconCapacity');

    const multiAirconPriceValue = document.getElementById('multiAirconPriceValue');

    const multiAirconPriceHint = document.getElementById('multiAirconPriceHint');

    const multiAirconDescription = document.getElementById('multiAirconDescription');

    const multiAirconChoice = document.getElementById('multiAirconChoice');

    const bookingForm = document.getElementById('bookingForm');

    const bookingNotice = document.getElementById('bookingNotice');

    const dateInput = document.getElementById('reservation_date');

    const reservationDay = document.getElementById('reservationDay');

    const weatherPreview = document.getElementById('reservationWeatherPreview');

    const slotButtons = document.querySelectorAll('[data-slot]');

    const availabilityModal = document.getElementById('availabilityModal');

    const availabilityCalendar = document.getElementById('availabilityCalendar');

    const availabilityModalTitle = document.getElementById('availabilityModalTitle');

    const availabilitySlotButtons = document.querySelectorAll('[data-slot-toggle]');

    const availabilityCloseButtons = document.querySelectorAll('[data-close-availability-modal]');

    // Amenity Info Modal elements
    const amenityInfoModal = document.getElementById('amenityInfoModal');
    const infoModalCategory = document.getElementById('infoModalCategory');
    const infoModalName = document.getElementById('infoModalName');
    const infoModalImage = document.getElementById('infoModalImage');
    const infoModalCapacityText = document.getElementById('infoModalCapacityText');
    const infoModalSaleTag = document.getElementById('infoModalSaleTag');
    const infoModalBenefits = document.getElementById('infoModalBenefits');
    const infoModalDayPrice = document.getElementById('infoModalDayPrice');
    const infoModalNightPrice = document.getElementById('infoModalNightPrice');
    const infoModalOrigDayPrice = document.getElementById('infoModalOrigDayPrice');
    const infoModalOrigNightPrice = document.getElementById('infoModalOrigNightPrice');
    const infoModalExtraFee = document.getElementById('infoModalExtraFee');
    const infoModalExtraFeeValue = document.getElementById('infoModalExtraFeeValue');
    const infoModalDescription = document.getElementById('infoModalDescription');
    const infoModalBookBtn = document.getElementById('infoModalBookBtn');
    const infoModalCloseButtons = document.querySelectorAll('[data-close-amenity-info-modal]');
    let infoModalActiveCard = null;

    const urlParams = new URLSearchParams(window.location.search);

    const preselectedAmenityId = urlParams.get('amenity');

    const preselectedDate = urlParams.get('date');

    const availabilityLoading = document.getElementById('availabilityLoading');



    if (!grid || cards.length === 0) {

        return;

    }



    const isNighttimeForToday = (dateStr) => {
        const today = window.PARK_TODAY_DATE || (new Date().getFullYear() + '-' + String(new Date().getMonth() + 1).padStart(2, '0') + '-' + String(new Date().getDate()).padStart(2, '0'));
        if (!dateStr || dateStr !== today) return false;

        if (typeof window.PARK_IS_NIGHTTIME_NOW === 'boolean') {
            return window.PARK_IS_NIGHTTIME_NOW;
        }

        const now = new Date();
        const currentHour = now.getHours();
        const currentMinutes = now.getMinutes();
        const nowTotalMinutes = currentHour * 60 + currentMinutes;

        let dayStartMinutes = 6 * 60; // 06:00
        let dayEndMinutes = 18 * 60; // 18:00

        if (window.PARK_DAYTIME_START && window.PARK_DAYTIME_START.includes(':')) {
            const [h, m] = window.PARK_DAYTIME_START.split(':').map(Number);
            dayStartMinutes = h * 60 + (m || 0);
        }
        if (window.PARK_DAYTIME_END && window.PARK_DAYTIME_END.includes(':')) {
            const [h, m] = window.PARK_DAYTIME_END.split(':').map(Number);
            dayEndMinutes = h * 60 + (m || 0);
        }

        return nowTotalMinutes >= dayEndMinutes || nowTotalMinutes < dayStartMinutes;
    };

    const initialDateToCheck = preselectedDate || window.PARK_TODAY_DATE || (new Date().getFullYear() + '-' + String(new Date().getMonth() + 1).padStart(2, '0') + '-' + String(new Date().getDate()).padStart(2, '0'));
    let selectedSlot = isNighttimeForToday(initialDateToCheck) ? 'Nighttime' : 'Daytime';

    let stayMode = 'single'; // 'single' or 'range'

    let mainStartDate = '';

    let mainEndDate = '';

    let mainStartSlot = selectedSlot;

    let mainEndSlot = selectedSlot;

    const amenityStayConfig = {};

    let activeAmenity = null;

    let pendingMultiAmenity = null;

    let multiSelectionEnabled = false;

    let selectedCards = [];

    let multiSelectionChoices = {};

    let occupiedAmenityIds = [];

    let isLoadingAvailability = false;

    let availabilityRequestId = 0;

    let calendarAmenityId = null;

    let calendarAmenityName = '';

    let calendarAvailability = [];

    let calendarSlot = selectedSlot;

    let calendarStayMode = 'single';

    let calendarRangeStart = null;

    let calendarRangeEnd = null;

    let calendarRangeStartSlot = selectedSlot;

    let calendarRangeEndSlot = selectedSlot;

    let calendarSourceCard = null;

    // Date range picker modal state
    let dpStayMode = 'single';
    let dpRangeStart = null;
    let dpRangeEnd = null;
    let dpRangeStartSlot = selectedSlot;
    let dpRangeEndSlot = selectedSlot;

    // ── Check-in / Check-out Datetime Preview Formatting ──────────────────────
    const formatTimeLabel = (timeStr) => {
        if (!timeStr) return '';
        const [hStr, mStr] = timeStr.split(':');
        let h = parseInt(hStr, 10);
        const m = mStr ? mStr.padStart(2, '0') : '00';
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return `${h}:${m} ${ampm}`;
    };

    const getParkTimes = () => {
        const dayStart = formatTimeLabel(window.PARK_DAYTIME_START || '08:00');
        const dayEnd = formatTimeLabel(window.PARK_DAYTIME_END || '17:00');
        const nightStart = formatTimeLabel(window.PARK_NIGHTTIME_START || window.PARK_DAYTIME_END || '17:00');
        const nightEnd = formatTimeLabel(window.PARK_NIGHTTIME_END || window.PARK_DAYTIME_START || '08:00');
        return { dayStart, dayEnd, nightStart, nightEnd };
    };

    const syncModalSessionButtonLabels = () => {
        const { dayStart, dayEnd, nightStart, nightEnd } = getParkTimes();
        const mapping = [
            { id: 'dpCheckInDaytimeTimeLabel', text: dayStart },
            { id: 'dpCheckInOvernightTimeLabel', text: nightStart },
            { id: 'dpCheckOutDaytimeTimeLabel', text: dayEnd },
            { id: 'dpCheckOutOvernightTimeLabel', text: `${nightEnd} next day` },
            { id: 'avCheckInDaytimeTimeLabel', text: dayStart },
            { id: 'avCheckInOvernightTimeLabel', text: nightStart },
            { id: 'avCheckOutDaytimeTimeLabel', text: dayEnd },
            { id: 'avCheckOutOvernightTimeLabel', text: `${nightEnd} next day` }
        ];
        mapping.forEach(({ id, text }) => {
            const el = document.getElementById(id);
            if (el) el.textContent = text;
        });
    };

    const getNextDateStr = (dateStr) => {
        if (!dateStr) return '';
        const d = new Date(dateStr + 'T00:00:00');
        d.setDate(d.getDate() + 1);
        return d.getFullYear() + '-' +
            String(d.getMonth() + 1).padStart(2, '0') + '-' +
            String(d.getDate()).padStart(2, '0');
    };

    const formatDateShort = (dateStr) => {
        if (!dateStr) return '';
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    };

    const computeCheckInOutPreview = (startDate, endDate, startSlot, endSlot, isRange) => {
        if (!startDate) {
            return {
                checkInDate: 'Select date',
                checkOutDate: 'Select date',
                checkIn: 'Select date',
                checkOut: 'Select date',
                summary: 'Select date'
            };
        }
        const finalEnd = endDate || startDate;
        const finalStartSlot = startSlot || 'Daytime';
        const finalEndSlot = endSlot || finalStartSlot;

        const { dayStart, dayEnd, nightStart, nightEnd } = getParkTimes();

        let checkInDateStr = startDate;
        let checkInTimeStr = finalStartSlot === 'Nighttime' ? `${nightStart} (Evening)` : `${dayStart} (Morning)`;

        let checkOutDateStr = finalEnd;
        let checkOutTimeStr = finalEndSlot === 'Nighttime' ? `${nightEnd} (Next morning)` : `${dayEnd} (Evening)`;

        if (finalEndSlot === 'Nighttime') {
            checkOutDateStr = getNextDateStr(finalEnd);
        }

        const sObj = new Date(startDate + 'T00:00:00');
        const eObj = new Date(finalEnd + 'T00:00:00');
        const sFormatted = sObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const eFormatted = eObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const checkOutDisplayFormatted = (new Date(checkOutDateStr + 'T00:00:00')).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

        const { dayCount, nightCount, totalDays } = calculateContinuousSlots(startDate, finalEnd, finalStartSlot, finalEndSlot);
        let summaryText = `${totalDays} Day${totalDays > 1 ? 's' : ''} (${dayCount}D ${nightCount}N)`;
        if (startDate === finalEnd) {
            if (finalStartSlot === finalEndSlot) {
                summaryText = `1 Day · ${finalStartSlot === 'Nighttime' ? 'Overnight' : 'Daytime'}`;
            } else if (finalStartSlot === 'Daytime' && finalEndSlot === 'Nighttime') {
                summaryText = `1 Day · Daytime → Overnight (1D 1N)`;
            } else {
                summaryText = `1 Day · ${dayCount}D ${nightCount}N`;
            }
        }

        const checkInTime = finalStartSlot === 'Nighttime' ? nightStart : dayStart;
        const checkInSession = finalStartSlot === 'Nighttime' ? '(Evening)' : '(Morning)';
        const checkOutTime = finalEndSlot === 'Nighttime' ? nightEnd : dayEnd;
        const checkOutSession = finalEndSlot === 'Nighttime' ? '(Next morning)' : '(Evening)';

        return {
            checkInDate: sFormatted,
            checkOutDate: checkOutDisplayFormatted,
            checkInTime: checkInTime,
            checkInSession: checkInSession,
            checkOutTime: checkOutTime,
            checkOutSession: checkOutSession,
            checkIn: `${sFormatted} · ${checkInTimeStr}`,
            checkOut: `${checkOutDisplayFormatted} · ${checkOutTimeStr}`,
            summary: summaryText
        };
    };

    const updateMainDateTimePreview = () => {
        const sDate = mainStartDate || (dateInput ? dateInput.value : '');
        const eDate = mainEndDate || sDate;
        const sSlot = mainStartSlot || selectedSlot;
        const eSlot = mainEndSlot || selectedSlot;
        const isRange = stayMode === 'range' && sDate && eDate && (sDate !== eDate || sSlot !== eSlot);

        const mainCheckInDate = document.getElementById('mainCheckInDate');
        const mainCheckInTime = document.getElementById('mainCheckInTime');
        const mainCheckInSession = document.getElementById('mainCheckInSession');
        const mainCheckOutDate = document.getElementById('mainCheckOutDate');
        const mainCheckOutTime = document.getElementById('mainCheckOutTime');
        const mainCheckOutSession = document.getElementById('mainCheckOutSession');
        const mainPreviewCheckIn = document.getElementById('mainPreviewCheckIn');
        const mainPreviewCheckOut = document.getElementById('mainPreviewCheckOut');
        const reservationDateText = document.getElementById('reservationDateText');

        if (!sDate) {
            if (reservationDateText) reservationDateText.textContent = 'Select reservation date';
            if (mainCheckInDate) mainCheckInDate.textContent = '—';
            if (mainCheckInTime) mainCheckInTime.textContent = '—';
            if (mainCheckInSession) mainCheckInSession.textContent = '';
            if (mainCheckOutDate) mainCheckOutDate.textContent = '—';
            if (mainCheckOutTime) mainCheckOutTime.textContent = '—';
            if (mainCheckOutSession) mainCheckOutSession.textContent = '';
            if (mainPreviewCheckIn) mainPreviewCheckIn.textContent = '—';
            if (mainPreviewCheckOut) mainPreviewCheckOut.textContent = '—';

            const weatherIcon = document.getElementById('weatherIcon');
            const weatherCondition = document.getElementById('weatherCondition');
            const weatherTemp = document.getElementById('weatherTemp');
            const weatherSkeleton = document.getElementById('weatherSkeleton');
            const weatherEmpty = document.getElementById('weatherEmpty');

            if (weatherIcon) weatherIcon.hidden = true;
            if (weatherSkeleton) weatherSkeleton.hidden = true;
            if (weatherEmpty) weatherEmpty.hidden = true;
            if (weatherCondition) weatherCondition.textContent = 'No date selected';
            if (weatherTemp) weatherTemp.textContent = 'Select a date above to view forecast';
            return;
        }

        const preview = computeCheckInOutPreview(sDate, eDate, sSlot, eSlot, isRange);
        if (mainCheckInDate) mainCheckInDate.textContent = preview.checkInDate;
        if (mainCheckInTime) mainCheckInTime.textContent = preview.checkInTime;
        if (mainCheckInSession) mainCheckInSession.textContent = preview.checkInSession;
        if (mainCheckOutDate) mainCheckOutDate.textContent = preview.checkOutDate;
        if (mainCheckOutTime) mainCheckOutTime.textContent = preview.checkOutTime;
        if (mainCheckOutSession) mainCheckOutSession.textContent = preview.checkOutSession;
        if (mainPreviewCheckIn) mainPreviewCheckIn.textContent = preview.checkIn;
        if (mainPreviewCheckOut) mainPreviewCheckOut.textContent = preview.checkOut;

        if (reservationDateText) {
            const sObj = new Date(sDate + 'T00:00:00');
            const eObj = new Date(eDate + 'T00:00:00');
            const { totalDays } = calculateContinuousSlots(sDate, eDate, sSlot, eSlot);
            if (sDate === eDate) {
                reservationDateText.textContent = sObj.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            } else {
                reservationDateText.textContent = `${sObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} – ${eObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} (${totalDays} Days)`;
            }
        }
    };

    const updateSlotButtonsForDate = (date) => {
        const slotDaytime = document.getElementById('slotDaytime');
        const slotNighttime = document.getElementById('slotNighttime');

        if (!slotDaytime || !slotNighttime) return;

        const dateToEvaluate = date || (dateInput ? dateInput.value : '') || window.PARK_TODAY_DATE;
        const isTodayNight = isNighttimeForToday(dateToEvaluate);

        if (isTodayNight) {
            slotDaytime.disabled = true;
            slotDaytime.classList.add('is-disabled-slot');
            slotDaytime.setAttribute('title', 'Daytime session for today has already passed. Please select Overnight or an upcoming date.');

            slotNighttime.disabled = false;
            slotNighttime.classList.remove('is-disabled-slot');
            slotNighttime.removeAttribute('title');

            if (selectedSlot === 'Daytime' || selectedSlot === 'DayToNight') {
                setActiveSlot('Nighttime');
            }
        } else {
            slotDaytime.disabled = false;
            slotDaytime.classList.remove('is-disabled-slot');
            slotDaytime.removeAttribute('title');

            slotNighttime.disabled = false;
            slotNighttime.classList.remove('is-disabled-slot');
            slotNighttime.removeAttribute('title');
        }

        updateMainDateTimePreview();
    };

    const updateModalSlotButtonsForDate = (date) => {
        const modalSlotDaytime = document.getElementById('modalSlotDaytime');
        const modalSlotNighttime = document.getElementById('modalSlotNighttime');

        if (!modalSlotDaytime || !modalSlotNighttime) return;

        const dateToEvaluate = date || (dateInput ? dateInput.value : '') || window.PARK_TODAY_DATE;
        const isTodayNight = isNighttimeForToday(dateToEvaluate);

        if (isTodayNight) {
            modalSlotDaytime.disabled = true;
            modalSlotDaytime.classList.add('is-disabled-slot');
            modalSlotDaytime.setAttribute('title', 'Daytime session for today has already passed. Please select Overnight.');

            modalSlotNighttime.disabled = false;
            modalSlotNighttime.classList.remove('is-disabled-slot');
            modalSlotNighttime.removeAttribute('title');

            if (modalSlotDaytime.classList.contains('is-active')) {
                setActiveModalSlot('Nighttime');
            }
        } else {
            modalSlotDaytime.disabled = false;
            modalSlotDaytime.classList.remove('is-disabled-slot');
            modalSlotDaytime.removeAttribute('title');

            modalSlotNighttime.disabled = false;
            modalSlotNighttime.classList.remove('is-disabled-slot');
            modalSlotNighttime.removeAttribute('title');
        }
    };

    const calculateContinuousSlots = (startDateStr, endDateStr, startSlot = 'Daytime', endSlot = 'Daytime') => {
        if (!startDateStr) return { dayCount: 1, nightCount: 0, totalDays: 1 };
        const start = new Date(startDateStr + 'T00:00:00');
        const end = endDateStr ? new Date(endDateStr + 'T00:00:00') : new Date(startDateStr + 'T00:00:00');

        let daysDiff = Math.round((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24));
        if (daysDiff < 0) daysDiff = 0;
        const totalDays = daysDiff + 1;

        const cleanStart = (startSlot === 'DayToNight' || startSlot === 'daytonight' || (startSlot && startSlot.startsWith('Day'))) ? 'Daytime' : 'Nighttime';
        const cleanEnd = (endSlot === 'DayToNight' || endSlot === 'daytonight' || (endSlot && endSlot.includes('Night'))) ? 'Nighttime' : 'Daytime';

        if (daysDiff === 0) {
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

    const getAmenityContinuousPrice = (card, choice, startDateStr, endDateStr, startSlot = 'Daytime', endSlot = 'Daytime') => {
        const { dayCount, nightCount } = calculateContinuousSlots(startDateStr, endDateStr, startSlot, endSlot);

        const dayPrice = Number(card.dataset.daytimePrice || 0);
        const nightPrice = Number(card.dataset.nighttimePrice || 0);

        return (dayCount * dayPrice) + (nightCount * nightPrice);
    };

    const getWeekday = (dateString) => {

        const date = new Date(dateString);

        return date.toLocaleDateString(undefined, { weekday: 'long' });

    };

    const updateReservationDay = () => {

        if (reservationDay && dateInput && dateInput.value) {
            reservationDay.textContent = getWeekday(dateInput.value);
        }

        updateMainDateTimePreview();

    };



    const renderWeatherPreview = (forecast) => {

        if (!weatherPreview) return;



        if (!forecast || !forecast.available) {

            weatherPreview.innerHTML = '<p class="rp-weather-preview__empty">No info about the weather.</p>';

            return;

        }



        const icon = forecast.icon

            ? `<img src="${forecast.icon}" alt="${forecast.condition || 'Weather'}" class="rp-weather-preview__icon">`

            : '';

        const tempRange = forecast.is_current && forecast.temp_c !== null && forecast.feelslike_c !== null

            ? `Now ${Math.round(forecast.temp_c)}°C · Feels like ${Math.round(forecast.feelslike_c)}°C`

            : (forecast.max_temp_c !== null && forecast.min_temp_c !== null

                ? `High ${Math.round(forecast.max_temp_c)}°C · Low ${Math.round(forecast.min_temp_c)}°C`

                : 'Forecast available for this date');

        const rainHint = forecast.chance_of_rain !== null && forecast.chance_of_rain !== undefined

            ? `<span class="rp-weather-preview__rain">Rain chance: ${forecast.chance_of_rain}%</span>`

            : '';



        weatherPreview.innerHTML = `

            <div class="rp-weather-preview__wrap">

                ${icon}

                <div class="rp-weather-preview__content">

                    <strong>${forecast.condition || 'Forecast available'}</strong>

                    <span>${tempRange}</span>

                    ${rainHint}

                </div>

            </div>

        `;

    };



    const loadWeatherPreview = async (dateString) => {

        if (!weatherPreview || !dateString) return;



        const minDate = dateInput?.dataset.minDate;



        if (minDate && dateString < minDate) {

            dateInput.value = minDate;

        }



        if (!dateInput.value) return;



        try {

            const url = new URL('/reservation/weather-preview', window.location.origin);

            url.searchParams.set('date', dateInput.value);



            const response = await fetch(url.toString(), {

                headers: { Accept: 'application/json' },

            });



            if (!response.ok) {

                throw new Error('Weather preview request failed');

            }



            const payload = await response.json();

            renderWeatherPreview(payload);

        } catch (error) {

            renderWeatherPreview({ available: false });

        }

    };



    const setAvailabilityLoading = (loading) => {

        isLoadingAvailability = loading;

        if (grid) {

            grid.classList.toggle('is-busy', loading);

            grid.hidden = loading;

        }

        if (gridSkeleton) {
            gridSkeleton.hidden = !loading;
        }

        if (availabilityLoading) {

            availabilityLoading.hidden = true;

        }



        cards.forEach(card => {

            card.classList.toggle('is-disabled', loading);

        });



        slotButtons.forEach(button => {

            button.disabled = loading;

        });

    };



    const syncReservationDate = () => {
        if (!dateInput) return;

        const minDate = dateInput.dataset.minDate;

        if (minDate && dateInput.value && dateInput.value < minDate) {
            dateInput.value = minDate;
        }

        updateReservationDay();
        updateSlotButtonsForDate(dateInput.value);
        refreshAvailability();
        loadWeatherPreview(dateInput.value);
    };



    const refreshAvailability = async () => {

        if (!dateInput || !dateInput.value || !selectedSlot) {

            occupiedAmenityIds = [];

            applyFilters();

            return;

        }

        const requestId = ++availabilityRequestId;

        setAvailabilityLoading(true);

        cards.forEach(card => {

            card.style.display = 'none';

        });

        if (emptyState) {

            emptyState.style.display = 'none';

        }

        try {

            const url = new URL('/reservation/availability', window.location.origin);

            const startD = mainStartDate || dateInput.value;
            const endD = mainEndDate || mainStartDate || dateInput.value;

            url.searchParams.set('start_date', startD);
            url.searchParams.set('end_date', endD);
            url.searchParams.set('start_slot', mainStartSlot || selectedSlot);
            url.searchParams.set('end_slot', mainEndSlot || selectedSlot);
            url.searchParams.set('date', startD);
            url.searchParams.set('slot', selectedSlot);
            url.searchParams.set('_t', Date.now());

            const response = await fetch(url.toString(), {
                cache: 'no-store',
                headers: { Accept: 'application/json', 'Cache-Control': 'no-cache' },
            });

            if (!response.ok) {

                throw new Error('Availability request failed');

            }

            const payload = await response.json();

            if (requestId === availabilityRequestId) {

                occupiedAmenityIds = payload.occupied_amenity_ids || [];

            }

        } catch (error) {

            if (requestId === availabilityRequestId) {

                occupiedAmenityIds = [];

            }

        }

        if (requestId === availabilityRequestId) {

            applyFilters();

            setAvailabilityLoading(false);

        }

    };

    const openAvailabilityModal = async (card) => {
        if (!availabilityModal || !card) return;

        syncModalSessionButtonLabels();

        calendarSourceCard = card;
        calendarAmenityId = card.dataset.amenityId;
        calendarAmenityName = card.dataset.name || 'Amenity';

        const isMulti = multiSelectionEnabled && selectedCards.length > 0;
        const targetAmenityIds = isMulti
            ? selectedCards.map(c => c.dataset.amenityId).join(',')
            : calendarAmenityId;

        availabilityModalTitle.textContent = isMulti
            ? 'Selected amenities availability'
            : `${calendarAmenityName} availability`;

        const isRange = Boolean(mainStartDate && mainEndDate && mainStartDate !== mainEndDate);
        calendarRangeStart = mainStartDate || (dateInput ? dateInput.value : '') || null;
        calendarRangeEnd = isRange ? (mainEndDate || null) : (calendarRangeStart || null);
        calendarRangeStartSlot = mainStartSlot || selectedSlot || 'Daytime';
        calendarRangeEndSlot = mainEndSlot || selectedSlot || 'Daytime';

        updateAvRangeDisplay();

        // Initialize month and year dropdowns
        const calendarMonthSelect = document.getElementById('calendarMonth');
        const calendarYearSelect = document.getElementById('calendarYear');

        const fetchCalendarData = async () => {
            const selectedMonth = calendarMonthSelect ? calendarMonthSelect.value : new Date().getMonth();
            const selectedYear = calendarYearSelect ? calendarYearSelect.value : new Date().getFullYear();

            availabilityCalendar.classList.add('is-loading');

            try {
                const url = new URL('/reservation/availability/calendar', window.location.origin);
                url.searchParams.set('amenity_id', targetAmenityIds);
                url.searchParams.set('amenity_ids', targetAmenityIds);
                url.searchParams.set('slot', calendarRangeStartSlot || 'Daytime');
                url.searchParams.set('month', selectedMonth);
                url.searchParams.set('year', selectedYear);
                url.searchParams.set('_t', Date.now());

                const response = await fetch(url.toString(), {
                    cache: 'no-store',
                    headers: { Accept: 'application/json', 'Cache-Control': 'no-cache' },
                });

                if (!response.ok) {
                    throw new Error('Calendar availability request failed');
                }

                const payload = await response.json();
                calendarAvailability = payload.availability || [];
                renderAvailabilityCalendar();
            } catch (error) {
                calendarAvailability = [];
                renderAvailabilityCalendar();
            } finally {
                availabilityCalendar.classList.remove('is-loading');
            }
        };

        if (calendarMonthSelect && calendarYearSelect) {
            const today = new Date();
            if (calendarRangeStart) {
                const [initY, initM] = calendarRangeStart.split('-').map(Number);
                calendarMonthSelect.value = initM - 1;
                calendarYearSelect.innerHTML = '';
                for (let year = today.getFullYear(); year <= today.getFullYear() + 4; year++) {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    calendarYearSelect.appendChild(option);
                }
                calendarYearSelect.value = initY;
            } else {
                calendarMonthSelect.value = today.getMonth();
                const currentYear = today.getFullYear();
                calendarYearSelect.innerHTML = '';
                for (let year = currentYear; year <= currentYear + 4; year++) {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    calendarYearSelect.appendChild(option);
                }
                calendarYearSelect.value = currentYear;
            }

            calendarMonthSelect.onchange = fetchCalendarData;
            calendarYearSelect.onchange = fetchCalendarData;
        }

        calendarAvailability = [];
        if (availabilityCalendar) {
            availabilityCalendar.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 2rem; opacity: 0.7;">Loading availability…</div>';
        }

        availabilityModal.classList.add('is-open');
        availabilityModal.setAttribute('aria-hidden', 'false');
        updateOverlayScrollLock();

        fetchCalendarData();
    };

    // Mode toggles inside availability modal
    const avConfirmDateBtn = document.getElementById('avConfirmDateBtn');

    // Availability range slot toggles
    document.querySelectorAll('[data-av-start-slot]').forEach(btn => {
        btn.addEventListener('click', () => {
            const slot = btn.dataset.avStartSlot;
            const curDate = calendarRangeStart || (dateInput ? dateInput.value : '') || window.PARK_TODAY_DATE;
            if (curDate && isNighttimeForToday(curDate) && slot === 'Daytime') {
                return;
            }
            calendarRangeStartSlot = slot;
            const curEndDate = calendarRangeEnd || curDate;
            const isSingleDay = Boolean(!calendarRangeEnd || calendarRangeStart === calendarRangeEnd || curDate === curEndDate);
            if (isSingleDay && calendarRangeStartSlot === 'Nighttime') {
                calendarRangeEndSlot = 'Nighttime';
            }
            updateAvRangeDisplay();
            renderAvailabilityCalendar();
        });
    });

    document.querySelectorAll('[data-av-end-slot]').forEach(btn => {
        btn.addEventListener('click', () => {
            const curDate = calendarRangeStart || (dateInput ? dateInput.value : '');
            const curEndDate = calendarRangeEnd || curDate;
            const isSingleDay = Boolean(!calendarRangeEnd || calendarRangeStart === calendarRangeEnd || curDate === curEndDate);
            if (isSingleDay && calendarRangeStartSlot === 'Nighttime' && btn.dataset.avEndSlot === 'Daytime') {
                return;
            }
            calendarRangeEndSlot = btn.dataset.avEndSlot;
            updateAvRangeDisplay();
            renderAvailabilityCalendar();
        });
    });

    const updateAvRangeDisplay = () => {
        const curDate = calendarRangeStart || (dateInput ? dateInput.value : '');
        const curEndDate = calendarRangeEnd || curDate;
        const isSingleDay = Boolean(!calendarRangeEnd || calendarRangeStart === calendarRangeEnd || curDate === curEndDate);

        // Single-day overnight check-in conflicts with daytime check-out: disable daytime check-out and default to overnight
        const avEndDaytimeBtn = document.querySelector('[data-av-end-slot="Daytime"]');
        if (isSingleDay && calendarRangeStartSlot === 'Nighttime') {
            if (avEndDaytimeBtn) {
                avEndDaytimeBtn.disabled = true;
                avEndDaytimeBtn.classList.add('is-disabled-slot');
                avEndDaytimeBtn.setAttribute('title', 'Daytime check-out is not available when checking in overnight on the same day');
            }
            calendarRangeEndSlot = 'Nighttime';
        } else {
            if (avEndDaytimeBtn) {
                avEndDaytimeBtn.disabled = false;
                avEndDaytimeBtn.classList.remove('is-disabled-slot');
                avEndDaytimeBtn.removeAttribute('title');
            }
        }

        const isRange = Boolean((calendarRangeStart && calendarRangeEnd && calendarRangeStart !== calendarRangeEnd) || (calendarRangeStartSlot !== calendarRangeEndSlot));

        document.querySelectorAll('[data-av-start-slot]').forEach(b => {
            b.classList.toggle('is-active', b.dataset.avStartSlot === calendarRangeStartSlot);
        });
        document.querySelectorAll('[data-av-end-slot]').forEach(b => {
            b.classList.toggle('is-active', b.dataset.avEndSlot === calendarRangeEndSlot);
        });

        if (curDate && isNighttimeForToday(curDate)) {
            const dayBtn = document.querySelector('[data-av-start-slot="Daytime"]');
            if (dayBtn) {
                dayBtn.disabled = true;
                dayBtn.classList.add('is-disabled-slot');
            }
            if (calendarRangeStartSlot === 'Daytime') {
                calendarRangeStartSlot = 'Nighttime';
                document.querySelectorAll('[data-av-start-slot]').forEach(b => {
                    b.classList.toggle('is-active', b.dataset.avStartSlot === calendarRangeStartSlot);
                });
            }
        } else {
            const dayBtn = document.querySelector('[data-av-start-slot="Daytime"]');
            if (dayBtn) {
                dayBtn.disabled = false;
                dayBtn.classList.remove('is-disabled-slot');
            }
        }

        const preview = computeCheckInOutPreview(curDate, curEndDate, calendarRangeStartSlot, calendarRangeEndSlot, isRange);

        const avCheckInDate = document.getElementById('avCheckInDate');
        const avCheckOutDate = document.getElementById('avCheckOutDate');
        const avCheckInPreviewText = document.getElementById('avCheckInPreviewText');
        const avCheckOutPreviewText = document.getElementById('avCheckOutPreviewText');
        const avStaySummaryBadge = document.getElementById('avStaySummaryBadge');

        if (avCheckInDate) avCheckInDate.textContent = curDate ? preview.checkInDate : 'Pick a date';
        if (avCheckOutDate) avCheckOutDate.textContent = curDate ? preview.checkOutDate : 'Pick a date';
        if (avCheckInPreviewText) avCheckInPreviewText.textContent = preview.checkIn;
        if (avCheckOutPreviewText) avCheckOutPreviewText.textContent = preview.checkOut;
        if (avStaySummaryBadge) avStaySummaryBadge.textContent = preview.summary;
    };

    if (avConfirmDateBtn) {
        avConfirmDateBtn.addEventListener('click', () => {
            const chosenStart = calendarRangeStart || (dateInput ? dateInput.value : '');
            if (!chosenStart) return;

            const targetCard = (multiSelectionEnabled && selectedCards.length > 0)
                ? selectedCards[0]
                : calendarSourceCard;

            const finalEnd = calendarRangeEnd || chosenStart;
            mainStartDate = chosenStart;
            mainEndDate = finalEnd;
            mainStartSlot = calendarRangeStartSlot;
            mainEndSlot = calendarRangeEndSlot;
            stayMode = (finalEnd && finalEnd !== chosenStart) ? 'range' : (mainStartSlot !== mainEndSlot ? 'range' : 'single');
            selectedSlot = mainStartSlot;

            if (isNighttimeForToday(mainStartDate) && mainStartSlot === 'Daytime') {
                mainStartSlot = 'Nighttime';
                selectedSlot = 'Nighttime';
            }

            if (multiSelectionEnabled && selectedCards.length > 0) {
                selectedCards.forEach(c => {
                    const aId = c.dataset.amenityId;
                    amenityStayConfig[aId] = {
                        ...(amenityStayConfig[aId] || {}),
                        startDate: mainStartDate,
                        endDate: mainEndDate,
                        startSlot: mainStartSlot,
                        endSlot: mainEndSlot,
                        choice: amenityStayConfig[aId]?.choice || multiSelectionChoices[aId] || 'without'
                    };
                });
            }

            if (dateInput) {
                dateInput.value = mainStartDate;
            }
            updateSlotButtonsForDate(mainStartDate);
            updateReservationDay();
            syncDateSections();

            if (reservationDateTrigger) {
                const sObj = new Date(mainStartDate + 'T00:00:00');
                const eObj = new Date(mainEndDate + 'T00:00:00');
                const { dayCount, nightCount, totalDays } = calculateContinuousSlots(mainStartDate, mainEndDate, mainStartSlot, mainEndSlot);
                if (mainStartDate === mainEndDate && mainStartSlot === mainEndSlot) {
                    reservationDateTrigger.textContent = sObj.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                } else {
                    reservationDateTrigger.textContent = `${sObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} – ${eObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} · ${totalDays}D (${dayCount}D ${nightCount}N)`;
                }
            }

            closeAvailabilityModal();
            refreshAvailability();
            fetchWeatherForDate(mainStartDate);

            if (targetCard) {
                window.setTimeout(() => {
                    openModal(targetCard);
                }, 100);
            }
        });
    }

    const closeAvailabilityModal = () => {
        if (!availabilityModal) return;
        availabilityModal.classList.remove('is-open');
        availabilityModal.setAttribute('aria-hidden', 'true');
        updateOverlayScrollLock();
        calendarSourceCard = null;
        calendarAmenityId = null;
        activeAmenity = null;
    };

    const isRangeAvailable = (startDate, endDate, startSlot, endSlot, availList) => {
        if (!availList || availList.length === 0) return true;
        const startObj = new Date(startDate + 'T00:00:00');
        const endObj = new Date(endDate + 'T00:00:00');
        const daysDiff = Math.round((endObj - startObj) / (1000 * 60 * 60 * 24));
        if (daysDiff < 0) return false;

        const cleanStartSlot = (startSlot === 'Nighttime' || (startSlot && startSlot.includes('Night'))) ? 'Nighttime' : 'Daytime';
        const cleanEndSlot = (endSlot === 'Nighttime' || (endSlot && endSlot.includes('Night'))) ? 'Nighttime' : 'Daytime';

        for (let i = 0; i <= daysDiff; i++) {
            const curDate = new Date(startObj);
            curDate.setDate(curDate.getDate() + i);
            const iso = curDate.getFullYear() + '-' +
                String(curDate.getMonth() + 1).padStart(2, '0') + '-' +
                String(curDate.getDate()).padStart(2, '0');

            const entry = availList.find(e => e.date === iso);
            if (!entry) continue;

            if (daysDiff === 0) {
                if (cleanStartSlot === 'Daytime' && cleanEndSlot === 'Daytime') {
                    if (entry.daytime !== true) return false;
                } else if (cleanStartSlot === 'Nighttime' && cleanEndSlot === 'Nighttime') {
                    if (entry.nighttime !== true) return false;
                } else if (cleanStartSlot === 'Daytime' && cleanEndSlot === 'Nighttime') {
                    if (entry.daytime !== true || entry.nighttime !== true) return false;
                } else {
                    if (entry.nighttime !== true) return false;
                }
            } else if (i === 0) {
                if (cleanStartSlot === 'Daytime') {
                    if (entry.daytime !== true || entry.nighttime !== true) return false;
                } else {
                    if (entry.nighttime !== true) return false;
                }
            } else if (i === daysDiff) {
                if (cleanEndSlot === 'Daytime') {
                    if (entry.daytime !== true) return false;
                } else {
                    if (entry.daytime !== true || entry.nighttime !== true) return false;
                }
            } else {
                if (entry.daytime !== true || entry.nighttime !== true) return false;
            }
        }
        return true;
    };

    const renderAvailabilityCalendar = () => {
        if (!availabilityCalendar || !calendarAmenityId) return;

        const fragment = document.createDocumentFragment();

        ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach((weekday) => {
            const label = document.createElement('span');
            label.className = 'rp-calendar__weekday';
            label.textContent = weekday;
            fragment.appendChild(label);
        });

        const calendarMonthSelect = document.getElementById('calendarMonth');
        const calendarYearSelect = document.getElementById('calendarYear');

        let selectedMonth, selectedYear;

        if (calendarMonthSelect && calendarYearSelect && calendarMonthSelect.value !== '' && calendarYearSelect.value !== '') {
            selectedMonth = parseInt(calendarMonthSelect.value);
            selectedYear = parseInt(calendarYearSelect.value);
        } else {
            const today = new Date();
            selectedMonth = today.getMonth();
            selectedYear = today.getFullYear();
        }

        const firstDate = new Date(selectedYear, selectedMonth, 1);
        const startOffset = firstDate.getDay();
        const daysInMonth = new Date(selectedYear, selectedMonth + 1, 0).getDate();

        for (let i = 0; i < startOffset; i += 1) {
            const spacer = document.createElement('span');
            spacer.className = 'rp-calendar__day rp-calendar__day--empty';
            spacer.setAttribute('aria-hidden', 'true');
            fragment.appendChild(spacer);
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const days = Array.from({ length: daysInMonth }, (_, index) => {
            const date = new Date(selectedYear, selectedMonth, index + 1);
            const isoDate = date.getFullYear() + '-' +
                String(date.getMonth() + 1).padStart(2, '0') + '-' +
                String(date.getDate()).padStart(2, '0');

            let isAvailable = false;
            const entry = calendarAvailability.find((e) => e.date === isoDate);

            if (entry) {
                if (!calendarRangeStart) {
                    if (calendarRangeStartSlot === 'Nighttime') {
                        isAvailable = entry.nighttime === true;
                    } else {
                        isAvailable = entry.daytime === true;
                    }
                } else {
                    if (isoDate < calendarRangeStart) {
                        if (calendarRangeStartSlot === 'Nighttime') {
                            isAvailable = entry.nighttime === true;
                        } else {
                            isAvailable = entry.daytime === true;
                        }
                    } else {
                        isAvailable = isRangeAvailable(calendarRangeStart, isoDate, calendarRangeStartSlot, calendarRangeEndSlot, calendarAvailability);
                    }
                }
            }

            const isPast = date < today;
            const isToday = isoDate === (window.PARK_TODAY_DATE || (new Date().getFullYear() + '-' + String(new Date().getMonth() + 1).padStart(2, '0') + '-' + String(new Date().getDate()).padStart(2, '0')));
            if (isToday && isNighttimeForToday(isoDate)) {
                if (!calendarRangeStart && calendarRangeStartSlot === 'Daytime') {
                    isAvailable = false;
                }
            }

            if (isPast) {
                isAvailable = false;
            }

            const dayButton = document.createElement('button');
            dayButton.type = 'button';
            dayButton.className = `rp-calendar__day ${isAvailable ? 'is-available' : 'is-disabled'}`;
            dayButton.disabled = !isAvailable;
            dayButton.setAttribute('data-date', isoDate);

            // Highlight selected date(s)
            if (calendarRangeStart === isoDate) {
                dayButton.classList.add('is-range-start');
            }
            if (calendarRangeEnd === isoDate && calendarRangeEnd !== calendarRangeStart) {
                dayButton.classList.add('is-range-end');
            }
            if (calendarRangeStart && calendarRangeEnd && isoDate > calendarRangeStart && isoDate < calendarRangeEnd) {
                dayButton.classList.add('is-in-range');
            }

            dayButton.innerHTML = `
                <span class="rp-calendar__day-num">${date.getDate()}</span>
                <span class="rp-calendar__day-month">${date.toLocaleDateString('en', { month: 'short' })}</span>
            `;

            dayButton.addEventListener('click', () => {
                if (!isAvailable) return;

                if (!calendarRangeStart || (calendarRangeStart && calendarRangeEnd)) {
                    calendarRangeStart = isoDate;
                    calendarRangeEnd = null;
                } else if (calendarRangeStart && !calendarRangeEnd) {
                    if (isoDate === calendarRangeStart) {
                        calendarRangeEnd = calendarRangeStart;
                    } else if (isoDate < calendarRangeStart) {
                        calendarRangeStart = isoDate;
                        calendarRangeEnd = null;
                    } else {
                        calendarRangeEnd = isoDate;
                    }
                }
                updateAvRangeDisplay();
                renderAvailabilityCalendar();
            });

            return dayButton;
        });

        days.forEach((day) => fragment.appendChild(day));
        availabilityCalendar.replaceChildren(fragment);
    };



    const isAvailableForSlot = (card, dateString, slot) => {

        if (!dateString || !slot) {

            return true;

        }



        return !occupiedAmenityIds.includes(card.dataset.amenityId);

    };



    // Toggle "How to Book" section
    const toggleStepsBtn = document.getElementById('toggleStepsBtn');
    const toggleStepsText = document.getElementById('toggleStepsText');
    const bookingSteps = document.getElementById('bookingSteps');

    if (toggleStepsBtn && bookingSteps) {
        toggleStepsBtn.addEventListener('click', () => {
            const isHidden = bookingSteps.hasAttribute('hidden');
            if (isHidden) {
                bookingSteps.removeAttribute('hidden');
                bookingSteps.classList.add('is-open');
                toggleStepsBtn.classList.add('is-active');
                toggleStepsBtn.setAttribute('aria-expanded', 'true');
                if (toggleStepsText) toggleStepsText.textContent = 'Hide How to Book';
            } else {
                bookingSteps.setAttribute('hidden', '');
                bookingSteps.classList.remove('is-open');
                toggleStepsBtn.classList.remove('is-active');
                toggleStepsBtn.setAttribute('aria-expanded', 'false');
                if (toggleStepsText) toggleStepsText.textContent = 'Show How to Book';
            }
        });
    }

    let activeCategory = 'all';

    const categoryPills = document.querySelectorAll('.rp-category-pill');
    categoryPills.forEach(pill => {
        pill.addEventListener('click', () => {
            categoryPills.forEach(p => p.classList.remove('is-active'));
            pill.classList.add('is-active');
            activeCategory = pill.dataset.categoryFilter || 'all';
            applyFilters();
        });
    });

    const applyFilters = () => {
        let visibleCount = 0;
        const mode = filterType ? filterType.value : 'all';
        const min = filterMin ? Number(filterMin.value) : NaN;
        const max = filterMax ? Number(filterMax.value) : NaN;



        cards.forEach(card => {

            const slotMatch = isAvailableForSlot(card, dateInput.value, selectedSlot);

            let filterMatch = true;



            if (mode === 'capacity') {

                const minValue = Number(card.dataset.minCapacity);

                const maxValue = Number(card.dataset.maxCapacity);

                const validMin = Number.isFinite(min) ? maxValue >= min : true;

                const validMax = Number.isFinite(max) ? minValue <= max : true;

                filterMatch = validMin && validMax;

            } else if (mode === 'price') {

                const minValue = Number(card.dataset.minPrice);

                const maxValue = Number(card.dataset.maxPrice);

                const validMin = Number.isFinite(min) ? maxValue >= min : true;

                const validMax = Number.isFinite(max) ? minValue <= max : true;

                filterMatch = validMin && validMax;

            }

            const categoryMatch = (activeCategory === 'all') || (card.dataset.category === activeCategory);

            const visible = filterMatch && categoryMatch;

            const isBooked = !slotMatch;

            card.style.display = visible ? '' : 'none';

            card.classList.toggle('is-booked', visible && isBooked);

            const overlay = card.querySelector('.rp-card__overlay');

            if (overlay) {

                overlay.classList.toggle('is-booked', visible && isBooked);

                overlay.querySelector('span') && (overlay.querySelector('span').textContent = visible && isBooked ? `${card.dataset.name} — Already booked` : card.dataset.name);

            }

            if (visible) {

                visibleCount += 1;

            }

        });

        // Toggle category group containers based on whether they have visible cards
        document.querySelectorAll('.rp-category-group').forEach(group => {
            const groupCards = group.querySelectorAll('.rp-card');
            const hasVisible = Array.from(groupCards).some(c => c.style.display !== 'none');
            group.style.display = hasVisible ? '' : 'none';
        });

        if (grid) {

            grid.style.display = visibleCount > 0 ? '' : 'none';

        }



        if (emptyState) {

            emptyState.style.display = visibleCount > 0 ? 'none' : 'block';

        }

    };



    const setActiveSlot = (slot) => {
        selectedSlot = slot;
        mainStartSlot = slot;
        mainEndSlot = slot;

        slotButtons.forEach(button => {
            button.classList.toggle('is-active', button.dataset.slot === slot);
        });

        updateMainDateTimePreview();
        refreshAvailability();
    };



    const updateRangeInputs = () => {

        const mode = filterType.value;

        if (mode === 'all') {

            filterMin.disabled = true;

            filterMax.disabled = true;

            filterMin.value = '';

            filterMax.value = '';

            applyFilters();

            return;

        }



        filterMin.disabled = false;
        filterMax.disabled = false;
        applyFilters();
    };

    const getAmenityPrice = (card, choice, pricingType = selectedSlot, startDate = null, endDate = null, startSlot = null, endSlot = null) => {
        const sDate = startDate || mainStartDate || dateInput.value;
        const eDate = endDate || mainEndDate || sDate;
        const sSlot = startSlot || mainStartSlot || pricingType || selectedSlot;
        const eSlot = endSlot || mainEndSlot || pricingType || selectedSlot;

        return getAmenityContinuousPrice(card, choice, sDate, eDate, sSlot, eSlot);
    };

    const getSelectionTotal = () => {
        return selectedCards.reduce((total, card) => {
            const amenityId = card.dataset.amenityId;
            const config = amenityStayConfig[amenityId] || {};
            const choice = config.choice || multiSelectionChoices[amenityId] || 'without';
            const sDate = config.startDate || mainStartDate || dateInput.value;
            const eDate = config.endDate || mainEndDate || sDate;
            const sSlot = config.startSlot || mainStartSlot || selectedSlot;
            const eSlot = config.endSlot || mainEndSlot || selectedSlot;

            return total + getAmenityContinuousPrice(card, choice, sDate, eDate, sSlot, eSlot);
        }, 0);
    };

    const renderBookingSelection = (card, choice) => {
        const modalMultiAmenityContainer = document.getElementById('modalMultiAmenityContainer');
        const modalMultiAmenityList = document.getElementById('modalMultiAmenityList');
        const modalMetaBlock = document.getElementById('modalMetaBlock');

        const sDate = mainStartDate || dateInput.value;
        const eDate = mainEndDate || sDate;
        const sSlot = mainStartSlot || selectedSlot;
        const eSlot = mainEndSlot || selectedSlot;

        const isAircon = choice === 'with';

        if (multiSelectionEnabled && selectedCards.length > 0) {
            if (modalMetaBlock) modalMetaBlock.style.display = selectedCards.length > 1 ? 'none' : 'grid';
            if (modalMultiAmenityContainer) modalMultiAmenityContainer.style.display = 'block';
            if (airconChoice) airconChoice.style.display = 'none';
            if (modalDescription) modalDescription.innerHTML = '';

            // Render per-amenity summary cards with "Edit Dates" button
            if (modalMultiAmenityList) {
                modalMultiAmenityList.innerHTML = selectedCards.map(c => {
                    const amenityId = c.dataset.amenityId;
                    if (!amenityStayConfig[amenityId]) {
                        amenityStayConfig[amenityId] = {
                            startDate: sDate,
                            endDate: eDate,
                            startSlot: sSlot,
                            endSlot: eSlot,
                            choice: multiSelectionChoices[amenityId] || 'without'
                        };
                    }
                    const cfg = amenityStayConfig[amenityId];
                    const hasAc = c.dataset.hasAircon === '1';
                    const itemChoice = cfg.choice === 'with';

                    const { dayCount, nightCount, totalDays } = calculateContinuousSlots(cfg.startDate, cfg.endDate, cfg.startSlot, cfg.endSlot);
                    const itemPrice = getAmenityContinuousPrice(c, cfg.choice, cfg.startDate, cfg.endDate, cfg.startSlot, cfg.endSlot);

                    const sObj = new Date(cfg.startDate + 'T00:00:00');
                    const eObj = new Date(cfg.endDate + 'T00:00:00');
                    const sDateFormatted = sObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    const eDateFormatted = eObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });

                    const dateRangeDisplay = (cfg.startDate === cfg.endDate)
                        ? `${sDateFormatted} · 1 Day (${cfg.startSlot === cfg.endSlot ? cfg.startSlot : cfg.startSlot + ' → ' + cfg.endSlot})`
                        : `${sDateFormatted} (${cfg.startSlot}) → ${eDateFormatted} (${cfg.endSlot}) · ${totalDays} Days (${dayCount}D ${nightCount}N)`;

                    const packageBadge = hasAc
                        ? (itemChoice ? '<span class="rp-item-badge rp-item-badge--ac">With Aircon</span>' : '<span class="rp-item-badge">Standard</span>')
                        : '';

                    return `
                        <div class="rp-selected-amenity-card" data-amenity-id="${amenityId}">
                            <div class="rp-selected-amenity-info">
                                <div class="rp-selected-amenity-header">
                                    <h4 class="rp-selected-amenity-title">${c.dataset.name}</h4>
                                    ${packageBadge}
                                </div>
                                <div class="rp-selected-amenity-meta">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span>${dateRangeDisplay}</span>
                                </div>
                            </div>
                            <div class="rp-selected-amenity-actions">
                                <span class="rp-selected-amenity-price" id="cfgPrice_${amenityId}">₱${itemPrice.toFixed(2)}</span>
                                <button type="button" class="rp-edit-schedule-btn" data-edit-amenity-id="${amenityId}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                    Edit Dates
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');

                // Attach click listeners to "Edit Dates" buttons
                modalMultiAmenityList.querySelectorAll('[data-edit-amenity-id]').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const amenityId = btn.dataset.editAmenityId;
                        openEditAmenityScheduleModal(amenityId);
                    });
                });
            }

            const grandTotal = getSelectionTotal();
            modalPriceLabel.textContent = 'Total for all amenities';
            modalPriceValue.textContent = `₱${grandTotal.toFixed(2)}`;
            modalPriceHint.textContent = selectedCards.map(c => {
                const cCfg = amenityStayConfig[c.dataset.amenityId] || {};
                const cPrice = getAmenityContinuousPrice(c, cCfg.choice || 'without', cCfg.startDate || sDate, cCfg.endDate || sDate, cCfg.startSlot || sSlot, cCfg.endSlot || eSlot);
                return `${c.dataset.name}: ₱${cPrice.toFixed(2)}`;
            }).join(' + ');

        } else {
            // Single Amenity Mode
            if (modalMetaBlock) modalMetaBlock.style.display = 'grid';
            if (modalMultiAmenityContainer) modalMultiAmenityContainer.style.display = 'none';

            const { dayCount, nightCount, totalDays } = calculateContinuousSlots(sDate, eDate, sSlot, eSlot);
            const singlePrice = getAmenityContinuousPrice(card, choice, sDate, eDate, sSlot, eSlot);

            const dayPrice = Number(card.dataset.daytimePrice || 0);
            const nightPrice = Number(card.dataset.nighttimePrice || 0);

            modalPriceLabel.textContent = 'Package price';
            modalPriceValue.textContent = `₱${singlePrice.toFixed(2)}`;
            modalPriceHint.textContent = `${totalDays} Day${totalDays > 1 ? 's' : ''} Stay (${dayCount} Daytime${dayCount > 1 ? 's' : ''} × ₱${dayPrice.toFixed(2)} + ${nightCount} Nighttime${nightCount > 1 ? 's' : ''} × ₱${nightPrice.toFixed(2)}) = ₱${singlePrice.toFixed(2)}`;

            const salePercentage = parseFloat(card.dataset.salePercentage) || 0;
            if (salePercentage > 0) {
                const originalPrice = card.dataset.originalDaytimePrice || card.dataset.originalNighttimePrice || singlePrice;
                modalSaleInfo.style.display = 'flex';
                modalOriginalPrice.textContent = `₱${parseFloat(originalPrice).toFixed(2)}`;
                modalSalePercentage.textContent = `${salePercentage}% OFF`;
            } else {
                modalSaleInfo.style.display = 'none';
            }

            modalDescription.textContent = card.dataset.description || 'No additional details available.';
        }

        bookingForm.classList.remove('is-hidden');

        const checkInInput = document.getElementById('bookingCheckIn');
        const checkOutInput = document.getElementById('bookingCheckOut');
        const bookingReservationDate = document.getElementById('bookingReservationDate');
        const bookingEndDate = document.getElementById('bookingEndDate');
        const bookingStartSlot = document.getElementById('bookingStartSlot');
        const bookingEndSlot = document.getElementById('bookingEndSlot');
        const bookingTotalDays = document.getElementById('bookingTotalDays');

        const { totalDays } = calculateContinuousSlots(sDate, eDate, sSlot, eSlot);

        if (checkInInput) checkInInput.value = sDate;
        if (checkOutInput) checkOutInput.value = eDate;
        if (bookingReservationDate) bookingReservationDate.value = sDate;
        if (bookingEndDate) bookingEndDate.value = eDate;
        if (bookingStartSlot) bookingStartSlot.value = sSlot;
        if (bookingEndSlot) bookingEndSlot.value = eSlot;
        if (bookingTotalDays) bookingTotalDays.value = totalDays;

        const guestInput = bookingForm.querySelector('input[name="number_of_guests"]');
        if (guestInput) guestInput.value = card.dataset.minCapacity || '1';
    };

    const updateSelectionSummary = () => {
        if (!selectionSummaryList || !selectionMathText || !selectionTotalPrice) return;

        if (selectedCards.length === 0) {
            selectionMathText.textContent = 'No items selected';
            selectionTotalPrice.textContent = '₱0.00';
            selectionSummaryList.innerHTML = '<li class="rp-selection-sheet__empty">Select an amenity to review it here.</li>';
            return;
        }

        let total = 0;
        const parts = [];
        selectionSummaryList.innerHTML = '';

        selectedCards.forEach(card => {
            const amenityId = card.dataset.amenityId;
            const cfg = amenityStayConfig[amenityId] || {};
            const choice = cfg.choice || multiSelectionChoices[amenityId] || 'without';
            const sDate = cfg.startDate || mainStartDate || dateInput.value;
            const eDate = cfg.endDate || mainEndDate || sDate;
            const sSlot = cfg.startSlot || mainStartSlot || selectedSlot;
            const eSlot = cfg.endSlot || mainEndSlot || selectedSlot;

            const price = getAmenityContinuousPrice(card, choice, sDate, eDate, sSlot, eSlot);
            total += price;

            let choiceLabel = '';
            if (sDate) {
                const { dayCount, nightCount, totalDays } = calculateContinuousSlots(sDate, eDate, sSlot, eSlot);
                choiceLabel = `${choice === 'with' ? 'With Aircon' : 'Standard'} · ${totalDays}D (${dayCount}D ${nightCount}N)`;
            } else {
                const cap = `${card.dataset.minCapacity || 1}–${card.dataset.maxCapacity || 2} pax`;
                choiceLabel = `${cap} · Standard Rate`;
            }

            const line = document.createElement('li');
            line.className = 'rp-selection-sheet__item';
            line.innerHTML = `
                <div class="rp-selection-sheet__item-main">
                    <strong>${card.dataset.name || 'Selected amenity'}</strong>
                    <span>${choiceLabel}</span>
                </div>
                <div class="rp-selection-sheet__item-right">
                    <span class="rp-selection-sheet__item-price">₱${price.toFixed(2)}</span>
                    <button type="button" class="rp-selection-sheet__item-remove" data-remove-amenity-id="${amenityId}" title="Remove this amenity">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            `;
            selectionSummaryList.appendChild(line);
            parts.push(`₱${price.toFixed(2)}`);
        });

        selectionSummaryList.querySelectorAll('[data-remove-amenity-id]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const aId = btn.dataset.removeAmenityId;
                const card = selectedCards.find(c => c.dataset.amenityId === aId);
                if (card) {
                    toggleCardSelection(card);
                    if (selectedCards.length === 0) {
                        closeSelectionSheet();
                    } else {
                        updateSelectionSummary();
                    }
                }
            });
        });

        selectionMathText.textContent = parts.join(' + ');
        selectionTotalPrice.textContent = `₱${total.toFixed(2)}`;
    };

    const openSelectionSheet = () => {
        updateSelectionSummary();
        if (selectionSheet) {
            selectionSheet.classList.add('is-open');
            selectionSheet.setAttribute('aria-hidden', 'false');
            updateOverlayScrollLock();
        }
    };

    const closeSelectionSheet = () => {
        if (selectionSheet) {
            selectionSheet.classList.remove('is-open');
            selectionSheet.setAttribute('aria-hidden', 'true');
            updateOverlayScrollLock();
        }
    };

    const updateSelectionUi = () => {
        multiSelectionEnabled = selectedCards.length > 0;
        const count = selectedCards.length;
        const total = getSelectionTotal();

        if (selectionFloatingBar) {
            selectionFloatingBar.hidden = count === 0;
        }

        if (selectionCountLabel) {
            selectionCountLabel.textContent = count === 1 ? '1 amenity selected' : `${count} amenities selected`;
        }

        if (selectionCheckoutBtn) {
            selectionCheckoutBtn.textContent = count === 1 ? 'Review selection' : 'Review selections';
        }

        const summaryHint = selectionFloatingBar?.querySelector('.rp-floating-actions__copy span');
        if (summaryHint) {
            summaryHint.textContent = count === 0 ? 'Tap to review your picks' : `₱${total.toFixed(2)} total`;
        }

        cards.forEach(card => {
            const isSelected = selectedCards.includes(card);
            card.classList.toggle('is-selected', isSelected);
            const overlay = card.querySelector('.rp-card__overlay');
            if (overlay) {
                overlay.classList.toggle('is-selected', isSelected);
            }
            const selectBtn = card.querySelector('[data-card-select]');
            if (selectBtn) {
                selectBtn.setAttribute('aria-pressed', String(isSelected));
                const textEl = selectBtn.querySelector('.rp-card__select-text');
                if (textEl) {
                    textEl.textContent = isSelected ? 'Selected' : 'Select';
                }
            }
        });
    };

    const renderMultiAirconSelection = (card, choice) => {
        const sDate = mainStartDate || dateInput?.value || '';
        const eDate = mainEndDate || sDate;
        const sSlot = mainStartSlot || selectedSlot;
        const eSlot = mainEndSlot || selectedSlot;

        const basePrice = getAmenityContinuousPrice(card, 'without', sDate, eDate, sSlot, eSlot);
        const airconPrice = getAmenityContinuousPrice(card, 'with', sDate, eDate, sSlot, eSlot);
        const selectedPrice = choice === 'with' ? airconPrice : basePrice;
        const isAircon = choice === 'with';

        if (multiAirconName) multiAirconName.textContent = card.dataset.name || 'Amenity name';

        if (multiAirconDate && sDate) {
            const sObj = new Date(sDate + 'T00:00:00');
            const eObj = new Date(eDate + 'T00:00:00');
            const sStr = sObj.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
            const eStr = eObj.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
            const { totalDays } = calculateContinuousSlots(sDate, eDate, sSlot, eSlot);
            multiAirconDate.textContent = (sDate === eDate) ? `${sStr} (1 Day)` : `${sStr} – ${eStr} (${totalDays} Days)`;
        } else if (multiAirconDate) {
            multiAirconDate.textContent = 'Select a date';
        }

        if (multiAirconSlot) multiAirconSlot.textContent = (sDate === eDate && sSlot === eSlot) ? sSlot : `${sSlot} → ${eSlot}`;
        if (multiAirconCapacity) multiAirconCapacity.textContent = `${card.dataset.minCapacity}–${card.dataset.maxCapacity} guests`;
        if (multiAirconPriceValue) multiAirconPriceValue.textContent = `₱${selectedPrice.toFixed(2)}`;
        if (multiAirconPriceHint) multiAirconPriceHint.textContent = isAircon
            ? 'With air-conditioning included in this package.'
            : 'Standard package without air-conditioning.';
        if (multiAirconDescription) multiAirconDescription.textContent = card.dataset.description || 'No additional details available.';

        if (multiAirconChoice) {
            const baseDisplay = `₱${basePrice.toFixed(2)}`;
            const airconDisplay = airconPrice ? `₱${airconPrice.toFixed(2)}` : 'N/A';
            multiAirconChoice.innerHTML = `
                <button type="button" class="rp-choice-btn ${choice === 'with' ? 'is-selected' : ''}" data-aircon-choice="with" data-price="${airconPrice}">
                    <span>With Aircon</span>
                    <span class="rp-choice-btn__price">${airconDisplay}</span>
                </button>
                <button type="button" class="rp-choice-btn ${choice === 'without' ? 'is-selected' : ''}" data-aircon-choice="without" data-price="${basePrice}">
                    <span>Without Aircon</span>
                    <span class="rp-choice-btn__price">${baseDisplay}</span>
                </button>
            `;
        }
    };

    const openMultiAirconModal = (card) => {
        pendingMultiAmenity = card;
        const currentChoice = multiSelectionChoices[card.dataset.amenityId] || 'without';
        renderMultiAirconSelection(card, currentChoice);
        if (multiAirconModal) {
            multiAirconModal.classList.add('is-open');
            multiAirconModal.setAttribute('aria-hidden', 'false');
            updateOverlayScrollLock();
        }
        if (selectionFloatingBar) {
            selectionFloatingBar.hidden = true;
        }
    };

    const closeMultiAirconModal = () => {
        if (multiAirconModal) {
            multiAirconModal.classList.remove('is-open');
            multiAirconModal.setAttribute('aria-hidden', 'true');
            updateOverlayScrollLock();
        }
        if (selectionFloatingBar && multiSelectionEnabled) {
            const count = selectedCards.length;
            selectionFloatingBar.hidden = count === 0;
        }
    };

    const editAmenityScheduleModal = document.getElementById('editAmenityScheduleModal');
    const editScheduleAmenityId = document.getElementById('editScheduleAmenityId');
    const editScheduleAmenityName = document.getElementById('editScheduleAmenityName');
    const editScheduleStartDate = document.getElementById('editScheduleStartDate');
    const editScheduleStartSlot = document.getElementById('editScheduleStartSlot');
    const editScheduleEndDate = document.getElementById('editScheduleEndDate');
    const editScheduleEndSlot = document.getElementById('editScheduleEndSlot');
    const editScheduleAirconWrap = document.getElementById('editScheduleAirconWrap');
    const editScheduleAllowedRangeHint = document.getElementById('editScheduleAllowedRangeHint');
    const editScheduleRangeText = document.getElementById('editScheduleRangeText');
    const editScheduleAirconToggle = document.getElementById('editScheduleAirconToggle');
    const editScheduleAirconDiff = document.getElementById('editScheduleAirconDiff');
    const editScheduleDurationText = document.getElementById('editScheduleDurationText');
    const editScheduleMathText = document.getElementById('editScheduleMathText');
    const editScheduleTotalPrice = document.getElementById('editScheduleTotalPrice');
    const saveScheduleBtn = document.getElementById('saveScheduleBtn');

    const getMasterBounds = () => {
        const bStart = mainStartDate || dateInput?.value || '';
        const bEnd = mainEndDate || bStart;
        const bStartSlot = mainStartSlot || selectedSlot || 'Daytime';
        const bEndSlot = mainEndSlot || selectedSlot || 'Daytime';
        return { bStart, bEnd, bStartSlot, bEndSlot };
    };

    const enforceAmenityScheduleConstraints = () => {
        const { bStart, bEnd, bStartSlot, bEndSlot } = getMasterBounds();
        if (!bStart) return;

        // 1. Min / Max for Start Date
        if (editScheduleStartDate) {
            editScheduleStartDate.min = bStart;
            editScheduleStartDate.max = bEnd;
            if (editScheduleStartDate.value && editScheduleStartDate.value < bStart) {
                editScheduleStartDate.value = bStart;
            } else if (editScheduleStartDate.value && editScheduleStartDate.value > bEnd) {
                editScheduleStartDate.value = bEnd;
            }
        }

        const curStartDate = editScheduleStartDate?.value || bStart;

        // 2. Min / Max for End Date
        if (editScheduleEndDate) {
            editScheduleEndDate.min = curStartDate;
            editScheduleEndDate.max = bEnd;
            if (editScheduleEndDate.value && editScheduleEndDate.value < curStartDate) {
                editScheduleEndDate.value = curStartDate;
            } else if (editScheduleEndDate.value && editScheduleEndDate.value > bEnd) {
                editScheduleEndDate.value = bEnd;
            }
        }

        const curEndDate = editScheduleEndDate?.value || curStartDate;

        // 3. Start Slot dropdown option constraints
        if (editScheduleStartSlot) {
            const daytimeStartOpt = editScheduleStartSlot.querySelector('option[value="Daytime"]');
            const nighttimeStartOpt = editScheduleStartSlot.querySelector('option[value="Nighttime"]');

            // If start date is on master start date and master starts at Nighttime, Daytime start is disallowed
            const allowDaytimeStart = !(curStartDate === bStart && bStartSlot === 'Nighttime');

            if (daytimeStartOpt) {
                daytimeStartOpt.disabled = !allowDaytimeStart;
                daytimeStartOpt.hidden = !allowDaytimeStart;
            }
            if (nighttimeStartOpt) {
                nighttimeStartOpt.disabled = false;
                nighttimeStartOpt.hidden = false;
            }

            if (!allowDaytimeStart && editScheduleStartSlot.value === 'Daytime') {
                editScheduleStartSlot.value = 'Nighttime';
            }
        }

        const curStartSlot = editScheduleStartSlot?.value || 'Daytime';

        // 4. End Slot dropdown option constraints
        if (editScheduleEndSlot) {
            const daytimeEndOpt = editScheduleEndSlot.querySelector('option[value="Daytime"]');
            const nighttimeEndOpt = editScheduleEndSlot.querySelector('option[value="Nighttime"]');

            // If end date is on master end date and master ends at Daytime, Nighttime end is disallowed
            const allowNighttimeEnd = !(curEndDate === bEnd && bEndSlot === 'Daytime');

            // If start and end are on the same day and start slot is Nighttime, Daytime end is disallowed
            const allowDaytimeEnd = !(curStartDate === curEndDate && curStartSlot === 'Nighttime');

            if (daytimeEndOpt) {
                daytimeEndOpt.disabled = !allowDaytimeEnd;
                daytimeEndOpt.hidden = !allowDaytimeEnd;
            }
            if (nighttimeEndOpt) {
                nighttimeEndOpt.disabled = !allowNighttimeEnd;
                nighttimeEndOpt.hidden = !allowNighttimeEnd;
            }

            if (!allowDaytimeEnd && editScheduleEndSlot.value === 'Daytime') {
                editScheduleEndSlot.value = 'Nighttime';
            } else if (!allowNighttimeEnd && editScheduleEndSlot.value === 'Nighttime') {
                editScheduleEndSlot.value = 'Daytime';
            }
        }
    };

    const updateEditScheduleDisplay = () => {
        enforceAmenityScheduleConstraints();

        const amenityId = editScheduleAmenityId?.value;
        if (!amenityId) return;
        const card = cards.find(c => c.dataset.amenityId === amenityId);
        if (!card) return;

        const { bStart, bEnd } = getMasterBounds();
        let sDate = editScheduleStartDate?.value || bStart || '';
        let eDate = editScheduleEndDate?.value || sDate;
        if (bStart && sDate < bStart) sDate = bStart;
        if (bEnd && sDate > bEnd) sDate = bEnd;
        if (sDate && eDate < sDate) eDate = sDate;
        if (bEnd && eDate > bEnd) eDate = bEnd;

        const sSlot = editScheduleStartSlot?.value || 'Daytime';
        const eSlot = editScheduleEndSlot?.value || 'Daytime';
        const choice = editScheduleAirconToggle && editScheduleAirconToggle.checked ? 'with' : 'without';

        const { dayCount, nightCount, totalDays } = calculateContinuousSlots(sDate, eDate, sSlot, eSlot);
        const itemPrice = getAmenityContinuousPrice(card, choice, sDate, eDate, sSlot, eSlot);

        const dayPrice = choice === 'with' ? Number(card.dataset.daytimeAirconPrice || card.dataset.daytimePrice || 0) : Number(card.dataset.daytimePrice || 0);
        const nightPrice = choice === 'with' ? Number(card.dataset.nighttimeAirconPrice || card.dataset.nighttimePrice || 0) : Number(card.dataset.nighttimePrice || 0);

        if (editScheduleDurationText) {
            editScheduleDurationText.textContent = (sDate === eDate && sSlot === eSlot)
                ? `1 Day (${sSlot})`
                : `${totalDays} Day${totalDays > 1 ? 's' : ''} (${dayCount}D ${nightCount}N)`;
        }
        if (editScheduleMathText) {
            editScheduleMathText.textContent = `${dayCount} Daytime${dayCount > 1 ? 's' : ''} × ₱${dayPrice.toFixed(0)} + ${nightCount} Nighttime${nightCount > 1 ? 's' : ''} × ₱${nightPrice.toFixed(0)}`;
        }
        if (editScheduleTotalPrice) {
            editScheduleTotalPrice.textContent = `₱${itemPrice.toFixed(2)}`;
        }
    };

    const openEditAmenityScheduleModal = (amenityId) => {
        const card = cards.find(c => c.dataset.amenityId === amenityId);
        if (!card || !editAmenityScheduleModal) return;

        const { bStart, bEnd, bStartSlot, bEndSlot } = getMasterBounds();
        const sDate = bStart;
        const eDate = bEnd || sDate;
        const sSlot = bStartSlot;
        const eSlot = bEndSlot;

        if (!amenityStayConfig[amenityId]) {
            amenityStayConfig[amenityId] = {
                startDate: sDate,
                endDate: eDate,
                startSlot: sSlot,
                endSlot: eSlot,
                choice: multiSelectionChoices[amenityId] || 'without'
            };
        }

        const cfg = amenityStayConfig[amenityId];
        const hasAc = card.dataset.hasAircon === '1';

        // Clamp cfg to master bounds
        let initStartDate = cfg.startDate || sDate;
        let initEndDate = cfg.endDate || eDate || initStartDate;
        if (sDate && initStartDate < sDate) initStartDate = sDate;
        if (eDate && initStartDate > eDate) initStartDate = eDate;
        if (initStartDate && initEndDate < initStartDate) initEndDate = initStartDate;
        if (eDate && initEndDate > eDate) initEndDate = eDate;

        let initStartSlot = cfg.startSlot || sSlot;
        let initEndSlot = cfg.endSlot || eSlot;
        if (initStartDate === sDate && sSlot === 'Nighttime') initStartSlot = 'Nighttime';
        if (initEndDate === eDate && eSlot === 'Daytime') initEndSlot = 'Daytime';
        if (initStartDate === initEndDate && initStartSlot === 'Nighttime') initEndSlot = 'Nighttime';

        if (editScheduleAmenityId) editScheduleAmenityId.value = amenityId;
        if (editScheduleAmenityName) editScheduleAmenityName.textContent = card.dataset.name || 'Amenity';

        if (editScheduleAllowedRangeHint && editScheduleRangeText) {
            if (bStart) {
                const sObj = new Date(bStart + 'T00:00:00');
                const eObj = new Date(bEnd + 'T00:00:00');
                const sFormatted = sObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                const eFormatted = eObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

                if (bStart === bEnd) {
                    editScheduleRangeText.textContent = `${sFormatted} (${bStartSlot})`;
                } else {
                    editScheduleRangeText.textContent = `${sFormatted} (${bStartSlot}) → ${eFormatted} (${bEndSlot})`;
                }
                editScheduleAllowedRangeHint.style.display = 'flex';
            } else {
                editScheduleAllowedRangeHint.style.display = 'none';
            }
        }

        if (editScheduleStartDate) {
            editScheduleStartDate.value = initStartDate;
        }
        if (editScheduleStartSlot) editScheduleStartSlot.value = initStartSlot;
        if (editScheduleEndDate) {
            editScheduleEndDate.value = initEndDate;
        }
        if (editScheduleEndSlot) editScheduleEndSlot.value = initEndSlot;

        if (editScheduleAirconWrap) {
            editScheduleAirconWrap.style.display = hasAc ? 'block' : 'none';
        }
        if (editScheduleAirconToggle) {
            editScheduleAirconToggle.checked = cfg.choice === 'with';
        }
        if (editScheduleAirconDiff && hasAc) {
            const dayAcDiff = Number(card.dataset.daytimeAirconPrice || 0) - Number(card.dataset.daytimePrice || 0);
            const nightAcDiff = Number(card.dataset.nighttimeAirconPrice || 0) - Number(card.dataset.nighttimePrice || 0);
            const diff = Math.max(dayAcDiff, nightAcDiff);
            editScheduleAirconDiff.textContent = diff > 0 ? `+₱${diff.toFixed(0)} / slot` : 'AC Included';
        }

        updateEditScheduleDisplay();

        editAmenityScheduleModal.classList.add('is-open');
        editAmenityScheduleModal.setAttribute('aria-hidden', 'false');
        updateOverlayScrollLock();
    };

    const closeEditAmenityScheduleModal = () => {
        if (editAmenityScheduleModal) {
            editAmenityScheduleModal.classList.remove('is-open');
            editAmenityScheduleModal.setAttribute('aria-hidden', 'true');
            updateOverlayScrollLock();
        }
    };

    const toggleCardSelection = (card) => {
        if (!card) return;
        const amenityId = card.dataset.amenityId;
        const exists = selectedCards.includes(card);
        const hasAircon = card.dataset.hasAircon === '1';

        if (exists) {
            selectedCards = selectedCards.filter(item => item !== card);
            delete multiSelectionChoices[amenityId];
            delete amenityStayConfig[amenityId];
            updateSelectionUi();
            updateSelectionSummary();
            return;
        }

        const sDate = mainStartDate || dateInput?.value || '';
        const eDate = mainEndDate || sDate;
        const sSlot = mainStartSlot || selectedSlot;
        const eSlot = mainEndSlot || selectedSlot;

        selectedCards.push(card);
        multiSelectionChoices[amenityId] = 'without';
        amenityStayConfig[amenityId] = {
            startDate: sDate,
            endDate: eDate,
            startSlot: sSlot,
            endSlot: eSlot,
            choice: 'without'
        };

        updateSelectionUi();
        updateSelectionSummary();
    };

    const openModal = (card) => {
        activeAmenity = card;
        bookingNotice.textContent = '';
        const currentChoice = multiSelectionChoices[card.dataset.amenityId] || 'without';

        if (multiSelectionEnabled && selectedCards.length > 0) {
            const allNames = selectedCards.map(c => c.dataset.name).join(' + ');
            modalName.textContent = allNames;
        } else {
            modalName.textContent = card.dataset.name;
        }

        const sDate = mainStartDate || dateInput.value;
        const eDate = mainEndDate || sDate;
        const sSlot = mainStartSlot || selectedSlot;
        const eSlot = mainEndSlot || selectedSlot;
        const { dayCount, nightCount, totalDays } = calculateContinuousSlots(sDate, eDate, sSlot, eSlot);

        if (sDate) {
            const sObj = new Date(sDate + 'T00:00:00');
            const eObj = new Date(eDate + 'T00:00:00');
            const sStr = sObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const eStr = eObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            modalDate.textContent = (sDate === eDate) ? `${sStr} (1 Day)` : `${sStr} – ${eStr} (${totalDays} Days)`;
        } else {
            modalDate.textContent = 'Select a date';
        }

        modalSlot.textContent = (sDate === eDate && sSlot === eSlot)
            ? sSlot
            : `${sSlot} → ${eSlot} (${dayCount}D ${nightCount}N)`;

        modalCapacity.textContent = `${card.dataset.minCapacity}–${card.dataset.maxCapacity} guests`;

        const modalRates = document.getElementById('modalRates');
        if (modalRates) {
            const dPrice = parseFloat(card.dataset.daytimePrice || 0);
            const nPrice = parseFloat(card.dataset.nighttimePrice || 0);
            if (dPrice > 0 && nPrice > 0 && dPrice !== nPrice) {
                modalRates.textContent = `Daytime: ₱${dPrice.toLocaleString()} · Overnight: ₱${nPrice.toLocaleString()}`;
            } else {
                modalRates.textContent = `₱${(dPrice || nPrice).toLocaleString()}`;
            }
        }

        const modalBenefits = document.getElementById('modalBenefits');
        if (modalBenefits) {
            modalBenefits.innerHTML = '';
            if (card.dataset.isAircon === '1') {
                modalBenefits.innerHTML += '<span class="inline-flex items-center gap-1.5 rounded-full border border-cyan-500/40 bg-cyan-950/70 px-2.5 py-1 text-xs font-bold text-cyan-300"><i class="bi bi-snow"></i> Aircon Included</span>';
            }
            if (card.dataset.freePool === '1') {
                modalBenefits.innerHTML += '<span class="inline-flex items-center gap-1.5 rounded-full border border-blue-500/40 bg-blue-950/70 px-2.5 py-1 text-xs font-bold text-blue-300"><i class="bi bi-water"></i> Free Pool Access</span>';
            }
            if (card.dataset.freeEntrance === '1') {
                modalBenefits.innerHTML += '<span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/40 bg-emerald-950/70 px-2.5 py-1 text-xs font-bold text-emerald-300"><i class="bi bi-ticket-perforated-fill"></i> Free Entrance</span>';
            }
        }

        if (airconChoice) {
            airconChoice.innerHTML = '';
            airconChoice.style.display = 'none';
        }
        renderBookingSelection(card, 'without');

        if (selectionFloatingBar) {
            selectionFloatingBar.hidden = true;
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    };

    const closeModal = () => {
        // Show confirmation modal instead of directly closing
        if (cancelConfirmModal) {
            cancelConfirmModal.classList.add('is-open');
            cancelConfirmModal.setAttribute('aria-hidden', 'false');
            updateOverlayScrollLock();
        }

        if (selectionFloatingBar && multiSelectionEnabled) {
            const count = selectedCards.length;
            selectionFloatingBar.hidden = count === 0;
        }
    };



    if (dateControlsSection) {
        dateControlsSection.hidden = false;
    }

    if (dateInput) {

        if (preselectedDate) {

            dateInput.value = preselectedDate;

            updateReservationDay();

            refreshAvailability();

            loadWeatherPreview(dateInput.value);

        } else {

            updateMainDateTimePreview();

        }

        const handleDateSelection = () => {
            if (dateControlsSection) {
                dateControlsSection.hidden = false;
            }
        };

        dateInput.addEventListener('change', () => {
            syncReservationDate();
            handleDateSelection();
        });

        dateInput.addEventListener('input', () => {
            syncReservationDate();
            handleDateSelection();
        });

    }



    updateReservationDay();
    updateSlotButtonsForDate(dateInput ? dateInput.value : '');
    applyFilters();



    // Hide skeleton when page is loaded
    if (gridSkeleton) {
        window.addEventListener('load', () => {
            setTimeout(() => {
                gridSkeleton.hidden = true;
            }, 500);
        });
    }



    // Date picker modal logic
    const datePickerMonth = document.getElementById('datePickerMonth');
    const datePickerYear = document.getElementById('datePickerYear');
    const datePickerDays = document.getElementById('datePickerDays');
    const dpConfirmDateBtn = document.getElementById('dpConfirmDateBtn');

    // Session buttons on Check-in and Check-out cards inside date picker modal
    document.querySelectorAll('[data-dp-start-slot]').forEach(btn => {
        btn.addEventListener('click', () => {
            const slot = btn.dataset.dpStartSlot;
            const curDate = dpRangeStart || (dateInput ? dateInput.value : '') || window.PARK_TODAY_DATE;
            if (curDate && isNighttimeForToday(curDate) && slot === 'Daytime') {
                return;
            }
            dpRangeStartSlot = slot;
            const curEndDate = dpRangeEnd || curDate;
            const isSingleDay = Boolean(!dpRangeEnd || dpRangeStart === dpRangeEnd || curDate === curEndDate);
            if (isSingleDay && dpRangeStartSlot === 'Nighttime') {
                dpRangeEndSlot = 'Nighttime';
            }
            updateDpRangeDisplay();
            renderDatePickerDays();
        });
    });

    document.querySelectorAll('[data-dp-end-slot]').forEach(btn => {
        btn.addEventListener('click', () => {
            const curDate = dpRangeStart || (dateInput ? dateInput.value : '');
            const curEndDate = dpRangeEnd || curDate;
            const isSingleDay = Boolean(!dpRangeEnd || dpRangeStart === dpRangeEnd || curDate === curEndDate);
            if (isSingleDay && dpRangeStartSlot === 'Nighttime' && btn.dataset.dpEndSlot === 'Daytime') {
                return;
            }
            dpRangeEndSlot = btn.dataset.dpEndSlot;
            updateDpRangeDisplay();
            renderDatePickerDays();
        });
    });

    const updateDpRangeDisplay = () => {
        const curDate = dpRangeStart || (dateInput ? dateInput.value : '');
        const curEndDate = dpRangeEnd || curDate;
        const isSingleDay = Boolean(!dpRangeEnd || dpRangeStart === dpRangeEnd || curDate === curEndDate);

        // Single-day overnight check-in conflicts with daytime check-out: disable daytime check-out and default to overnight
        const dpEndDaytimeBtn = document.querySelector('[data-dp-end-slot="Daytime"]');
        if (isSingleDay && dpRangeStartSlot === 'Nighttime') {
            if (dpEndDaytimeBtn) {
                dpEndDaytimeBtn.disabled = true;
                dpEndDaytimeBtn.classList.add('is-disabled-slot');
                dpEndDaytimeBtn.setAttribute('title', 'Daytime check-out is not available when checking in overnight on the same day');
            }
            dpRangeEndSlot = 'Nighttime';
        } else {
            if (dpEndDaytimeBtn) {
                dpEndDaytimeBtn.disabled = false;
                dpEndDaytimeBtn.classList.remove('is-disabled-slot');
                dpEndDaytimeBtn.removeAttribute('title');
            }
        }

        const isRange = Boolean((dpRangeStart && dpRangeEnd && dpRangeStart !== dpRangeEnd) || (dpRangeStartSlot !== dpRangeEndSlot));

        document.querySelectorAll('[data-dp-start-slot]').forEach(b => {
            b.classList.toggle('is-active', b.dataset.dpStartSlot === dpRangeStartSlot);
        });
        document.querySelectorAll('[data-dp-end-slot]').forEach(b => {
            b.classList.toggle('is-active', b.dataset.dpEndSlot === dpRangeEndSlot);
        });

        if (curDate && isNighttimeForToday(curDate)) {
            const dayBtn = document.querySelector('[data-dp-start-slot="Daytime"]');
            if (dayBtn) {
                dayBtn.disabled = true;
                dayBtn.classList.add('is-disabled-slot');
            }
            if (dpRangeStartSlot === 'Daytime') {
                dpRangeStartSlot = 'Nighttime';
                document.querySelectorAll('[data-dp-start-slot]').forEach(b => {
                    b.classList.toggle('is-active', b.dataset.dpStartSlot === dpRangeStartSlot);
                });
            }
        } else {
            const dayBtn = document.querySelector('[data-dp-start-slot="Daytime"]');
            if (dayBtn) {
                dayBtn.disabled = false;
                dayBtn.classList.remove('is-disabled-slot');
            }
        }

        const preview = computeCheckInOutPreview(curDate, curEndDate, dpRangeStartSlot, dpRangeEndSlot, isRange);

        const dpCheckInDate = document.getElementById('dpCheckInDate');
        const dpCheckOutDate = document.getElementById('dpCheckOutDate');
        const dpCheckInPreviewText = document.getElementById('dpCheckInPreviewText');
        const dpCheckOutPreviewText = document.getElementById('dpCheckOutPreviewText');
        const dpStaySummaryBadge = document.getElementById('dpStaySummaryBadge');

        if (dpCheckInDate) dpCheckInDate.textContent = curDate ? preview.checkInDate : 'Select date';
        if (dpCheckOutDate) dpCheckOutDate.textContent = curDate ? preview.checkOutDate : 'Select date';
        if (dpCheckInPreviewText) dpCheckInPreviewText.textContent = preview.checkIn;
        if (dpCheckOutPreviewText) dpCheckOutPreviewText.textContent = preview.checkOut;
        if (dpStaySummaryBadge) dpStaySummaryBadge.textContent = preview.summary;
    };

    if (dpConfirmDateBtn) {
        dpConfirmDateBtn.addEventListener('click', () => {
            const chosenStart = dpRangeStart || (dateInput ? dateInput.value : '');
            if (!chosenStart) return;

            const finalEnd = dpRangeEnd || chosenStart;
            mainStartDate = chosenStart;
            mainEndDate = finalEnd;
            mainStartSlot = dpRangeStartSlot;
            mainEndSlot = dpRangeEndSlot;
            stayMode = (finalEnd && finalEnd !== chosenStart) ? 'range' : (mainStartSlot !== mainEndSlot ? 'range' : 'single');
            selectedSlot = mainStartSlot;

            if (isNighttimeForToday(mainStartDate) && mainStartSlot === 'Daytime') {
                mainStartSlot = 'Nighttime';
                selectedSlot = 'Nighttime';
            }

            if (multiSelectionEnabled && selectedCards.length > 0) {
                selectedCards.forEach(c => {
                    const aId = c.dataset.amenityId;
                    amenityStayConfig[aId] = {
                        ...(amenityStayConfig[aId] || {}),
                        startDate: mainStartDate,
                        endDate: mainEndDate,
                        startSlot: mainStartSlot,
                        endSlot: mainEndSlot,
                        choice: amenityStayConfig[aId]?.choice || multiSelectionChoices[aId] || 'without'
                    };
                });
            }

            if (dateInput) {
                dateInput.value = mainStartDate;
            }
            updateSlotButtonsForDate(mainStartDate);
            updateReservationDay();
            syncDateSections();

            if (reservationDateTrigger) {
                const sObj = new Date(mainStartDate + 'T00:00:00');
                const eObj = new Date(mainEndDate + 'T00:00:00');
                const { dayCount, nightCount, totalDays } = calculateContinuousSlots(mainStartDate, mainEndDate, mainStartSlot, mainEndSlot);
                if (mainStartDate === mainEndDate && mainStartSlot === mainEndSlot) {
                    reservationDateTrigger.textContent = sObj.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                } else {
                    reservationDateTrigger.textContent = `${sObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} – ${eObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} · ${totalDays}D (${dayCount}D ${nightCount}N)`;
                }
            }

            closeDatePickerModal();
            refreshAvailability();
            fetchWeatherForDate(mainStartDate);
        });
    }

    let datePickerAvailability = [];

    const fetchDatePickerAvailability = async () => {
        if (!datePickerDays || !datePickerMonth || !datePickerYear) return;

        const selectedMonth = datePickerMonth.value;
        const selectedYear = datePickerYear.value;

        let amenityIds = '';
        if (multiSelectionEnabled && selectedCards.length > 0) {
            amenityIds = selectedCards.map(c => c.dataset.amenityId).join(',');
        } else if (activeAmenity) {
            amenityIds = activeAmenity.dataset.amenityId;
        }

        datePickerDays.classList.add('is-loading');

        try {
            const url = new URL('/reservation/availability/calendar', window.location.origin);
            if (amenityIds) {
                url.searchParams.set('amenity_ids', amenityIds);
                url.searchParams.set('amenity_id', amenityIds);
            }
            url.searchParams.set('month', selectedMonth);
            url.searchParams.set('year', selectedYear);
            url.searchParams.set('_t', Date.now());

            const response = await fetch(url.toString(), {
                cache: 'no-store',
                headers: { Accept: 'application/json', 'Cache-Control': 'no-cache' },
            });

            if (!response.ok) {
                throw new Error('Date picker availability request failed');
            }

            const payload = await response.json();
            datePickerAvailability = payload.availability || [];
            renderDatePickerDays();
        } catch (error) {
            datePickerAvailability = [];
            renderDatePickerDays();
        } finally {
            datePickerDays.classList.remove('is-loading');
        }
    };

    const openDatePickerModal = () => {
        if (!datePickerModal) return;

        syncModalSessionButtonLabels();

        const today = new Date();
        const currentMonth = today.getMonth();
        const currentYear = today.getFullYear();

        const isRange = Boolean(mainStartDate && mainEndDate && mainStartDate !== mainEndDate);
        dpRangeStart = mainStartDate || (dateInput ? dateInput.value : '') || null;
        dpRangeEnd = isRange ? (mainEndDate || null) : (dpRangeStart || null);
        dpRangeStartSlot = mainStartSlot || selectedSlot || 'Daytime';
        dpRangeEndSlot = mainEndSlot || selectedSlot || 'Daytime';

        if (datePickerMonth && datePickerYear) {
            if (dpRangeStart) {
                const [initY, initM] = dpRangeStart.split('-').map(Number);
                datePickerMonth.value = initM - 1;
                datePickerYear.innerHTML = '';
                for (let year = currentYear; year <= currentYear + 2; year++) {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    datePickerYear.appendChild(option);
                }
                datePickerYear.value = initY;
            } else {
                datePickerMonth.value = currentMonth;
                datePickerYear.innerHTML = '';
                for (let year = currentYear; year <= currentYear + 2; year++) {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    datePickerYear.appendChild(option);
                }
                datePickerYear.value = currentYear;
            }

            datePickerMonth.onchange = fetchDatePickerAvailability;
            datePickerYear.onchange = fetchDatePickerAvailability;
        }

        updateDpRangeDisplay();
        fetchDatePickerAvailability();

        datePickerModal.classList.add('is-open');
        datePickerModal.setAttribute('aria-hidden', 'false');
        updateOverlayScrollLock();
    };

    const syncDateSections = () => {
        const dateCta = document.getElementById('dateCtaSection');
        const dateControls = document.getElementById('dateControlsSection');

        if (dateCta) {
            dateCta.hidden = true;
            dateCta.style.display = 'none';
        }
        if (dateControls) {
            dateControls.hidden = false;
        }
        updateMainDateTimePreview();
    };

    const closeDatePickerModal = () => {
        if (!datePickerModal) return;
        datePickerModal.classList.remove('is-open');
        datePickerModal.setAttribute('aria-hidden', 'true');
        updateOverlayScrollLock();
        syncDateSections();
        calendarSourceCard = null;
        calendarAmenityId = null;
        activeAmenity = null;
    };

    const renderDatePickerDays = () => {
        if (!datePickerDays || !datePickerMonth || !datePickerYear) return;

        const selectedMonth = parseInt(datePickerMonth.value);
        const selectedYear = parseInt(datePickerYear.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const daysInMonth = new Date(selectedYear, selectedMonth + 1, 0).getDate();
        const firstDayOfMonth = new Date(selectedYear, selectedMonth, 1).getDay();

        const fragment = document.createDocumentFragment();

        ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach((weekday) => {
            const label = document.createElement('span');
            label.className = 'rp-calendar__weekday';
            label.textContent = weekday;
            fragment.appendChild(label);
        });

        for (let i = 0; i < firstDayOfMonth; i++) {
            const emptyCell = document.createElement('button');
            emptyCell.type = 'button';
            emptyCell.className = 'rp-calendar__day rp-calendar__day--empty';
            emptyCell.disabled = true;
            fragment.appendChild(emptyCell);
        }

        const days = Array.from({ length: daysInMonth }, (_, index) => {
            const date = new Date(selectedYear, selectedMonth, index + 1);
            const isoDate = date.getFullYear() + '-' +
                String(date.getMonth() + 1).padStart(2, '0') + '-' +
                String(date.getDate()).padStart(2, '0');

            const isPast = date < today;
            const isToday = date.getTime() === today.getTime();

            let isAvailable = false;

            // Check availability from fetched availability array
            if (datePickerAvailability.length > 0) {
                const entry = datePickerAvailability.find((e) => e.date === isoDate);
                if (entry) {
                    if (!dpRangeStart) {
                        if (dpRangeStartSlot === 'Nighttime') {
                            isAvailable = entry.nighttime === true;
                        } else {
                            isAvailable = entry.daytime === true;
                        }
                    } else {
                        if (isoDate < dpRangeStart) {
                            if (dpRangeStartSlot === 'Nighttime') {
                                isAvailable = entry.nighttime === true;
                            } else {
                                isAvailable = entry.daytime === true;
                            }
                        } else {
                            isAvailable = isRangeAvailable(dpRangeStart, isoDate, dpRangeStartSlot, dpRangeEndSlot, datePickerAvailability);
                        }
                    }
                }
            } else {
                isAvailable = !isPast;
            }

            if (isToday) {
                const isNight = isNighttimeForToday(isoDate);
                if (isNight) {
                    if (!dpRangeStart && dpRangeStartSlot === 'Daytime') {
                        isAvailable = false;
                    }
                }
            }

            if (isPast) {
                isAvailable = false;
            }

            const dayButton = document.createElement('button');
            dayButton.type = 'button';
            dayButton.className = `rp-calendar__day ${isAvailable ? 'is-available' : 'is-disabled'}`;
            dayButton.disabled = !isAvailable;
            dayButton.setAttribute('data-date', isoDate);

            // Highlight selected date(s)
            if (dpRangeStart === isoDate) {
                dayButton.classList.add('is-range-start');
            }
            if (dpRangeEnd === isoDate && dpRangeEnd !== dpRangeStart) {
                dayButton.classList.add('is-range-end');
            }
            if (dpRangeStart && dpRangeEnd && isoDate > dpRangeStart && isoDate < dpRangeEnd) {
                dayButton.classList.add('is-in-range');
            }

            dayButton.innerHTML = `
                <span class="rp-calendar__day-num">${date.getDate()}</span>
                <span class="rp-calendar__day-month">${date.toLocaleDateString('en', { month: 'short' })}</span>
            `;

            if (isAvailable) {
                dayButton.addEventListener('click', () => {
                    if (!dpRangeStart || (dpRangeStart && dpRangeEnd)) {
                        dpRangeStart = isoDate;
                        dpRangeEnd = null;
                    } else if (dpRangeStart && !dpRangeEnd) {
                        if (isoDate === dpRangeStart) {
                            dpRangeEnd = dpRangeStart;
                        } else if (isoDate < dpRangeStart) {
                            dpRangeStart = isoDate;
                            dpRangeEnd = null;
                        } else {
                            dpRangeEnd = isoDate;
                        }
                    }
                    updateDpRangeDisplay();
                    renderDatePickerDays();
                });
            }

            return dayButton;
        });

        days.forEach(day => fragment.appendChild(day));
        datePickerDays.replaceChildren(fragment);
    };

    // Event listeners for date picker modal
    if (reservationDateTrigger) {
        reservationDateTrigger.addEventListener('click', openDatePickerModal);
    }

    const datePickerCloseButtons = document.querySelectorAll('[data-close-date-picker]');
    datePickerCloseButtons.forEach(button => {
        button.addEventListener('click', closeDatePickerModal);
    });

    if (datePickerModal) {
        datePickerModal.addEventListener('click', (event) => {
            if (event.target === datePickerModal) {
                closeDatePickerModal();
            }
        });
    }

    // Weather fetch function
    const fetchWeatherForDate = async (date) => {
        const weatherPreview = document.getElementById('reservationWeatherPreview');
        const weatherIcon = document.getElementById('weatherIcon');
        const weatherCondition = document.getElementById('weatherCondition');
        const weatherTemp = document.getElementById('weatherTemp');
        const weatherEmpty = document.getElementById('weatherEmpty');
        const weatherSkeleton = document.getElementById('weatherSkeleton');

        if (!weatherPreview) return;

        // Show skeleton and hide other content
        weatherPreview.hidden = false;
        weatherIcon.hidden = true;
        weatherCondition.textContent = '';
        weatherTemp.textContent = '';
        weatherEmpty.hidden = true;
        if (weatherSkeleton) weatherSkeleton.hidden = false;

        try {
            const response = await fetch(`/reservation/weather-preview?date=${date}`);
            const data = await response.json();

            // Hide skeleton
            if (weatherSkeleton) weatherSkeleton.hidden = true;

            if (data.available) {
                weatherIcon.src = data.icon || '';
                weatherIcon.alt = data.condition || '';
                weatherIcon.hidden = !data.icon;
                weatherCondition.textContent = data.condition || '';
                weatherTemp.textContent = `High ${Math.round(data.max_temp_c)}°C · Low ${Math.round(data.min_temp_c)}°C`;
                weatherEmpty.hidden = true;
            } else {
                weatherIcon.hidden = true;
                weatherCondition.textContent = '';
                weatherTemp.textContent = '';
                weatherEmpty.textContent = data.message || 'No weather info yet';
                weatherEmpty.hidden = false;
            }
        } catch (error) {
            console.error('Error fetching weather:', error);
            // Hide skeleton
            if (weatherSkeleton) weatherSkeleton.hidden = true;
            weatherIcon.hidden = true;
            weatherCondition.textContent = '';
            weatherTemp.textContent = '';
            weatherEmpty.textContent = 'No weather info yet';
            weatherEmpty.hidden = false;
        }
    };



    if (preselectedAmenityId) {

        const preselectedCard = cards.find(card => card.dataset.amenityId === preselectedAmenityId);

        if (preselectedCard) {

            preselectedCard.scrollIntoView({ behavior: 'smooth', block: 'center' });

            preselectedCard.classList.add('is-highlighted');

            setTimeout(() => preselectedCard.classList.remove('is-highlighted'), 2200);

        }

    }

    // Fetch weather if date is preselected
    if (preselectedDate && dateInput) {
        dateInput.value = preselectedDate;
        fetchWeatherForDate(preselectedDate);
    }



    slotButtons.forEach(button => {

        button.addEventListener('click', () => setActiveSlot(button.dataset.slot));

    });



    if (filterType) {
        filterType.addEventListener('change', () => updateRangeInputs());
    }

    [filterMin, filterMax].filter(Boolean).forEach(input => {
        input.addEventListener('input', () => {
            if (filterType && filterType.value !== 'all') {
                applyFilters();
            }
        });
    });



    const setMultiSelection = (enabled) => {
        multiSelectionEnabled = enabled;
        if (!multiSelectionEnabled) {
            selectedCards = [];
            multiSelectionChoices = {};
            for (const k in amenityStayConfig) delete amenityStayConfig[k];
        }
        updateSelectionUi();
    };

    // Card select button event listener
    document.querySelectorAll('[data-card-select]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const card = btn.closest('.rp-card');
            if (card && !card.classList.contains('is-booked')) {
                toggleCardSelection(card);
            }
        });
    });

    cards.forEach(card => {
        card.addEventListener('click', (e) => {
            if (e.target.closest('[data-card-select]')) return;
            if (e.target.closest('[data-open-modal]')) return;
            if (card.classList.contains('is-booked')) return;
            openAmenityInfoModal(card);
        });
    });

    // Handle "Pick a Date" CTA button
    const pickDateBtn = document.getElementById('pickDateBtn');

    if (pickDateBtn) {
        pickDateBtn.addEventListener('click', () => {
            openDatePickerModal();
        });
    }

    // Direct unified reservation page setup
    syncDateSections();
    applyFilters();

    const closeAmenityInfoModal = () => {
        if (amenityInfoModal) {
            amenityInfoModal.classList.remove('is-open');
            amenityInfoModal.setAttribute('aria-hidden', 'true');
            updateOverlayScrollLock();
        }
        infoModalActiveCard = null;
        activeAmenity = null;
        calendarSourceCard = null;
        calendarAmenityId = null;
    };

    const openAmenityInfoModal = (card) => {
        if (!amenityInfoModal || !card) return;
        infoModalActiveCard = card;

        // Populate Category & Title
        if (infoModalCategory) {
            infoModalCategory.textContent = card.dataset.category || 'Amenity Overview';
        }
        if (infoModalName) {
            infoModalName.textContent = card.dataset.name || 'Amenity Name';
        }

        // Image background
        if (infoModalImage) {
            const cardImgEl = card.querySelector('.rp-card__image');
            if (cardImgEl && cardImgEl.style.backgroundImage) {
                infoModalImage.style.backgroundImage = cardImgEl.style.backgroundImage;
                infoModalImage.classList.remove('rp-card__image--empty');
            } else {
                infoModalImage.style.backgroundImage = '';
                infoModalImage.classList.add('rp-card__image--empty');
            }
        }

        // Capacity
        if (infoModalCapacityText) {
            const minCap = card.dataset.minCapacity || '0';
            const maxCap = card.dataset.maxCapacity || minCap;
            infoModalCapacityText.textContent = `${minCap}–${maxCap} pax`;
        }

        // Sale Tag
        const salePercentage = parseFloat(card.dataset.salePercentage || 0);
        if (infoModalSaleTag) {
            if (salePercentage > 0) {
                infoModalSaleTag.textContent = `${salePercentage}% OFF`;
                infoModalSaleTag.style.display = 'inline-block';
            } else {
                infoModalSaleTag.style.display = 'none';
            }
        }

        // Inclusions / Benefits
        if (infoModalBenefits) {
            infoModalBenefits.innerHTML = '';
            const isAircon = card.dataset.isAircon === '1';
            const freePool = card.dataset.freePool === '1';
            const freeEntrance = card.dataset.freeEntrance === '1';

            if (isAircon) {
                infoModalBenefits.innerHTML += `
                    <span class="rp-info-modal__badge rp-info-modal__badge--aircon">
                        <i class="bi bi-snow"></i> Air-conditioned
                    </span>
                `;
            }
            if (freePool) {
                infoModalBenefits.innerHTML += `
                    <span class="rp-info-modal__badge rp-info-modal__badge--pool">
                        <i class="bi bi-water"></i> Free Pool Access
                    </span>
                `;
            }
            if (freeEntrance) {
                infoModalBenefits.innerHTML += `
                    <span class="rp-info-modal__badge rp-info-modal__badge--entrance">
                        <i class="bi bi-ticket-perforated-fill"></i> Free Park Entrance
                    </span>
                `;
            }

            if (!isAircon && !freePool && !freeEntrance) {
                infoModalBenefits.innerHTML = '<span class="text-xs text-white/50 italic">Standard park guidelines apply.</span>';
            }
        }

        // Pricing: Daytime and Nighttime / Overnight
        const dayPrice = parseFloat(card.dataset.daytimePrice || 0);
        const nightPrice = parseFloat(card.dataset.nighttimePrice || 0);
        const origDayPrice = parseFloat(card.dataset.originalDaytimePrice || 0);
        const origNightPrice = parseFloat(card.dataset.originalNighttimePrice || 0);

        if (infoModalDayPrice) {
            infoModalDayPrice.textContent = `₱${dayPrice.toLocaleString()}`;
        }
        if (infoModalNightPrice) {
            infoModalNightPrice.textContent = `₱${nightPrice.toLocaleString()}`;
        }

        if (infoModalOrigDayPrice) {
            if (salePercentage > 0 && origDayPrice > dayPrice) {
                infoModalOrigDayPrice.textContent = `₱${origDayPrice.toLocaleString()}`;
                infoModalOrigDayPrice.style.display = 'block';
            } else {
                infoModalOrigDayPrice.style.display = 'none';
            }
        }

        if (infoModalOrigNightPrice) {
            if (salePercentage > 0 && origNightPrice > nightPrice) {
                infoModalOrigNightPrice.textContent = `₱${origNightPrice.toLocaleString()}`;
                infoModalOrigNightPrice.style.display = 'block';
            } else {
                infoModalOrigNightPrice.style.display = 'none';
            }
        }

        // Extra fee per additional head
        const additionalFee = parseFloat(card.dataset.additional || 0);
        if (infoModalExtraFee && infoModalExtraFeeValue) {
            if (additionalFee > 0) {
                infoModalExtraFeeValue.textContent = `₱${additionalFee.toLocaleString()}`;
                infoModalExtraFee.style.display = 'inline-flex';
            } else {
                infoModalExtraFee.style.display = 'none';
            }
        }

        // Description
        if (infoModalDescription) {
            const desc = card.dataset.description;
            infoModalDescription.textContent = desc && desc.trim() ? desc : 'Relax and unwind with this serene amenity at Hinaguan Nature Park. Perfect for family gatherings, friends, or restful getaways.';
        }

        // Selection state on primary button
        if (infoModalBookBtn) {
            const isSelected = selectedCards.includes(card);
            const btnSpan = infoModalBookBtn.querySelector('span') || infoModalBookBtn;
            if (btnSpan) {
                btnSpan.innerHTML = isSelected
                    ? '<i class="bi bi-dash-circle me-1"></i> Remove from selection'
                    : '<i class="bi bi-check2-circle me-1"></i> Select this amenity';
            }
            if (isSelected) {
                infoModalBookBtn.classList.add('rp-info-modal__book-btn--remove');
            } else {
                infoModalBookBtn.classList.remove('rp-info-modal__book-btn--remove');
            }
        }

        amenityInfoModal.classList.add('is-open');
        amenityInfoModal.setAttribute('aria-hidden', 'false');
        updateOverlayScrollLock();
    };

    document.querySelectorAll('[data-open-modal]').forEach(button => {
        button.addEventListener('click', (e) => {
            if (isLoadingAvailability) {
                return;
            }

            const card = button.closest('.rp-card');
            if (!card || card.classList.contains('is-booked')) {
                return;
            }

            // Always open amenity overview info modal so user can view details and select/deselect
            openAmenityInfoModal(card);
        });
    });

    // "Select this amenity" button inside Amenity Info Modal
    if (infoModalBookBtn) {
        infoModalBookBtn.addEventListener('click', () => {
            const targetCard = infoModalActiveCard;
            if (!targetCard) return;

            toggleCardSelection(targetCard);
            closeAmenityInfoModal();
        });
    }

    // Close Amenity Info Modal on cancel/close buttons
    infoModalCloseButtons.forEach(btn => {
        btn.addEventListener('click', closeAmenityInfoModal);
    });

    if (amenityInfoModal) {
        amenityInfoModal.addEventListener('click', (e) => {
            if (e.target === amenityInfoModal) {
                closeAmenityInfoModal();
            }
        });
    }

    if (selectionCheckoutBtn) {
        selectionCheckoutBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (selectedCards.length > 0) {
                openSelectionSheet();
            }
        });
    }

    if (selectionFloatingBar) {
        selectionFloatingBar.addEventListener('click', (e) => {
            if (e.target.closest('#selectionCheckoutBtn')) return;
            if (selectedCards.length > 0) {
                openSelectionSheet();
            }
        });
    }

    if (selectionContinueBtn) {
        selectionContinueBtn.addEventListener('click', () => {
            if (selectedCards.length === 0) return;

            const firstCard = selectedCards[0];
            closeSelectionSheet();

            // After reviewing amenities, if no date selected yet, open date picker
            if (!dateInput || !dateInput.value) {
                openDatePickerModal();
                return;
            }

            if (firstCard) {
                openModal(firstCard);
            }
        });
    }



    airconChoice.addEventListener('click', (event) => {

        const button = event.target.closest('[data-aircon-choice]');

        if (!button || !activeAmenity) {

            return;

        }

        const choice = button.dataset.airconChoice;

        const amenityId = activeAmenity.dataset.amenityId;

        if (amenityId) {

            multiSelectionChoices[amenityId] = choice;

        }

        if (multiSelectionEnabled && !selectedCards.includes(activeAmenity)) {

            selectedCards.push(activeAmenity);

        }

        renderBookingSelection(activeAmenity, choice);

        updateSelectionUi();

        updateSelectionSummary();

        if (multiSelectionEnabled) {

            closeModal();

        }

        airconChoice.querySelectorAll('[data-aircon-choice]').forEach(btn => {

            btn.classList.toggle('is-selected', btn.dataset.airconChoice === choice);

        });

    });



    if (multiAirconChoice) {

        multiAirconChoice.addEventListener('click', (event) => {

            const button = event.target.closest('[data-aircon-choice]');

            if (!button || !pendingMultiAmenity) {

                return;

            }

            const choice = button.dataset.airconChoice;

            renderMultiAirconSelection(pendingMultiAmenity, choice);

            multiAirconChoice.querySelectorAll('[data-aircon-choice]').forEach(btn => {

                btn.classList.toggle('is-selected', btn.dataset.airconChoice === choice);

            });

        });

    }



    const multiAirconConfirmBtn = document.getElementById('multiAirconConfirmBtn');

    if (multiAirconConfirmBtn) {

        multiAirconConfirmBtn.addEventListener('click', () => {

            if (!pendingMultiAmenity) return;

            const selectedBtn = multiAirconChoice?.querySelector('[data-aircon-choice].is-selected');

            const choice = selectedBtn ? selectedBtn.dataset.airconChoice : 'without';

            const amenityId = pendingMultiAmenity.dataset.amenityId;

            if (amenityId) {

                multiSelectionChoices[amenityId] = choice;

            }

            if (!selectedCards.includes(pendingMultiAmenity)) {

                selectedCards.push(pendingMultiAmenity);

            }

            updateSelectionUi();

            updateSelectionSummary();

            closeMultiAirconModal();
        });
    }

    // ── Edit Individual Amenity Schedule Listeners ───────────────────────────
    [editScheduleStartDate, editScheduleEndDate, editScheduleStartSlot, editScheduleEndSlot, editScheduleAirconToggle].forEach(ctrl => {
        ctrl?.addEventListener('change', updateEditScheduleDisplay);
        ctrl?.addEventListener('input', updateEditScheduleDisplay);
    });

    if (editScheduleStartDate) {
        editScheduleStartDate.addEventListener('change', () => {
            if (editScheduleEndDate && (!editScheduleEndDate.value || editScheduleEndDate.value < editScheduleStartDate.value)) {
                editScheduleEndDate.value = editScheduleStartDate.value;
            }
            if (editScheduleEndDate) {
                editScheduleEndDate.min = editScheduleStartDate.value;
            }
            updateEditScheduleDisplay();
        });
    }

    if (saveScheduleBtn) {
        saveScheduleBtn.addEventListener('click', () => {
            const amenityId = editScheduleAmenityId?.value;
            if (!amenityId) return;

            const card = cards.find(c => c.dataset.amenityId === amenityId);
            if (!card) return;

            enforceAmenityScheduleConstraints();

            const { bStart, bEnd } = getMasterBounds();
            let startDate = editScheduleStartDate?.value || bStart || '';
            let endDate = editScheduleEndDate?.value || startDate;

            if (bStart && startDate < bStart) startDate = bStart;
            if (bEnd && startDate > bEnd) startDate = bEnd;
            if (startDate && endDate < startDate) endDate = startDate;
            if (bEnd && endDate > bEnd) endDate = bEnd;

            const startSlot = editScheduleStartSlot?.value || 'Daytime';
            const endSlot = editScheduleEndSlot?.value || 'Daytime';
            const choice = editScheduleAirconToggle && editScheduleAirconToggle.checked ? 'with' : 'without';

            amenityStayConfig[amenityId] = {
                startDate,
                endDate,
                startSlot,
                endSlot,
                choice
            };
            multiSelectionChoices[amenityId] = choice;

            closeEditAmenityScheduleModal();

            // Re-render display inside the main booking modal
            if (activeAmenity) {
                openModal(activeAmenity);
            }
            updateSelectionUi();
            updateSelectionSummary();
        });
    }

    document.querySelectorAll('[data-close-edit-schedule-modal]').forEach(btn => {
        btn.addEventListener('click', closeEditAmenityScheduleModal);
    });

    if (editAmenityScheduleModal) {
        editAmenityScheduleModal.addEventListener('click', (e) => {
            if (e.target === editAmenityScheduleModal) {
                closeEditAmenityScheduleModal();
            }
        });
    }




    const submitButton = bookingForm.querySelector('button[type="submit"]');

    let isSubmitting = false;



    const setSubmittingState = (submitting) => {

        isSubmitting = submitting;

        bookingForm.querySelectorAll('input, button').forEach((element) => {

            element.disabled = submitting;

        });



        if (submitButton) {

            submitButton.disabled = submitting;

            submitButton.textContent = submitting ? 'Reserving…' : 'Reserve prototype';

            submitButton.classList.toggle('is-loading', submitting);

        }

    };



    // ── PayMongo Payment Gateway State & Methods ─────────────────────────────
    const paymongoPaymentModal = document.getElementById('paymongoPaymentModal');
    const pmSummaryTotal = document.getElementById('pmSummaryTotal');
    const pmSummaryDeposit = document.getElementById('pmSummaryDeposit');
    const pmSummaryBalance = document.getElementById('pmSummaryBalance');
    const pmNotice = document.getElementById('pmNotice');
    const pmIframeContainer = document.getElementById('pmIframeContainer');
    const pmAuthIframe = document.getElementById('pmAuthIframe');
    const pmStatusBox = document.getElementById('pmStatusBox');
    const pmStatusText = document.getElementById('pmStatusText');
    const pmCloseButtons = document.querySelectorAll('[data-close-payment-modal]');

    let currentReservationId = null;
    let currentPaymentIntentId = null;
    let currentClientKey = null;
    let paymentPollInterval = null;
    let paymentCountdownInterval = null;

    const stopPaymentTimer = () => {
        if (paymentCountdownInterval) {
            clearInterval(paymentCountdownInterval);
            paymentCountdownInterval = null;
        }
        const timerBoxEl = document.getElementById('pmTimerBox');
        if (timerBoxEl) timerBoxEl.hidden = true;
    };

    const startPaymentTimer = (durationSeconds = 600) => {
        stopPaymentTimer();

        let remaining = durationSeconds;
        const countdownEl = document.getElementById('pmCountdown');
        const timerBoxEl = document.getElementById('pmTimerBox');

        if (timerBoxEl) {
            timerBoxEl.hidden = false;
            timerBoxEl.classList.remove('is-urgent');
        }

        const updateDisplay = () => {
            const mins = Math.floor(remaining / 60);
            const secs = remaining % 60;
            const formatted = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
            if (countdownEl) countdownEl.textContent = formatted;

            if (remaining <= 120 && timerBoxEl) {
                timerBoxEl.classList.add('is-urgent');
            }
        };

        updateDisplay();

        paymentCountdownInterval = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
                stopPaymentTimer();
                showPaymentFailedModal(
                    'Payment Session Expired',
                    'The 10-minute payment window has passed. The reservation was not created and no charge was made.'
                );
            } else {
                updateDisplay();
            }
        }, 1000);
    };

    const showPaymentFailedModal = (title, message) => {
        stopPaymentTimer();
        if (paymentPollInterval) {
            clearInterval(paymentPollInterval);
            paymentPollInterval = null;
        }

        closePaymentModal();

        const failedModal = document.getElementById('paymentFailedModal');
        const titleEl = document.getElementById('paymentFailedTitle');
        const messageEl = document.getElementById('paymentFailedMessage');

        if (titleEl) titleEl.textContent = title || 'Payment Failed';
        if (messageEl) messageEl.textContent = message || 'Payment could not be processed. Please try again.';

        if (failedModal) {
            failedModal.classList.add('is-open');
            failedModal.setAttribute('aria-hidden', 'false');
            updateOverlayScrollLock();
        }
    };

    // Retry button listener
    const paymentFailedRetryBtn = document.getElementById('paymentFailedRetryBtn');
    if (paymentFailedRetryBtn) {
        paymentFailedRetryBtn.addEventListener('click', () => {
            const failedModal = document.getElementById('paymentFailedModal');
            if (failedModal) {
                failedModal.classList.remove('is-open');
                failedModal.setAttribute('aria-hidden', 'true');
                updateOverlayScrollLock();
            }
            if (pmAuthIframe) pmAuthIframe.src = 'about:blank';
            if (pmIframeContainer) pmIframeContainer.hidden = true;
            if (pmStatusBox) pmStatusBox.hidden = true;
            if (bookingNotice) bookingNotice.textContent = '';
        });
    }

    const closeFailedModalBackdrop = document.querySelector('[data-close-failed-modal]');
    if (closeFailedModalBackdrop) {
        closeFailedModalBackdrop.addEventListener('click', () => {
            const failedModal = document.getElementById('paymentFailedModal');
            if (failedModal) {
                failedModal.classList.remove('is-open');
                failedModal.setAttribute('aria-hidden', 'true');
                updateOverlayScrollLock();
            }
        });
    }

    const pmStepSelect = document.getElementById('pmStepSelect');
    const pmStepProcess = document.getElementById('pmStepProcess');
    const pmStepEyebrow = document.getElementById('pmStepEyebrow');
    const pmStepTitle = document.getElementById('pmStepTitle');
    const pmProceedToStep3Btn = document.getElementById('pmProceedToStep3Btn');
    const pmBackToStep2Btn = document.getElementById('pmBackToStep2Btn');

    const goToStep2 = () => {
        stopPaymentTimer();
        if (paymentPollInterval) {
            clearInterval(paymentPollInterval);
            paymentPollInterval = null;
        }
        if (pmAuthIframe) pmAuthIframe.src = 'about:blank';
        if (pmIframeContainer) pmIframeContainer.hidden = true;
        if (pmStatusBox) pmStatusBox.hidden = true;
        if (pmNotice) pmNotice.textContent = '';

        if (pmStepSelect) pmStepSelect.hidden = false;
        if (pmStepProcess) pmStepProcess.hidden = true;

        if (pmStepEyebrow) pmStepEyebrow.textContent = 'Step 2 of 3 · Select Payment Method';
        if (pmStepTitle) pmStepTitle.textContent = 'Choose Payment Option';
    };

    const goToStep3 = (method) => {
        const selectedMethod = method || 'gcash';

        if (pmStepSelect) pmStepSelect.hidden = true;
        if (pmStepProcess) pmStepProcess.hidden = false;

        if (pmStepEyebrow) pmStepEyebrow.textContent = 'Step 3 of 3 · Payment Authorization';
        if (pmStepTitle) pmStepTitle.textContent = 'Complete Deposit Payment';

        // Highlight selected tab & panel
        payTabs.forEach(t => t.classList.toggle('is-active', t.dataset.pmTab === selectedMethod));
        payPanels.forEach(p => {
            const isActive = p.dataset.pmPanel === selectedMethod;
            p.classList.toggle('is-active', isActive);
            p.style.display = isActive ? 'block' : 'none';
        });

        if (selectedMethod === 'card') {
            if (pmStatusBox) pmStatusBox.hidden = true;
            stopPaymentTimer();
        } else {
            processPayMongoPayment(selectedMethod);
        }
    };

    if (pmProceedToStep3Btn) {
        pmProceedToStep3Btn.addEventListener('click', () => {
            const activeTab = document.querySelector('[data-pm-tab].is-active');
            const method = activeTab ? activeTab.dataset.pmTab : 'gcash';
            goToStep3(method);
        });
    }

    if (pmBackToStep2Btn) {
        pmBackToStep2Btn.addEventListener('click', () => {
            goToStep2();
        });
    }

    const closePaymentModal = () => {
        if (paymongoPaymentModal) {
            paymongoPaymentModal.classList.remove('is-open');
            paymongoPaymentModal.setAttribute('aria-hidden', 'true');
            updateOverlayScrollLock();
        }
        goToStep2();
    };

    pmCloseButtons.forEach(btn => {
        btn.addEventListener('click', closePaymentModal);
    });

    // Tab switching in payment modal
    const payTabs = document.querySelectorAll('[data-pm-tab]');
    const payPanels = document.querySelectorAll('[data-pm-panel]');

    payTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetTab = tab.dataset.pmTab;

            payTabs.forEach(t => t.classList.toggle('is-active', t.dataset.pmTab === targetTab));
            payPanels.forEach(p => {
                const isActive = p.dataset.pmPanel === targetTab;
                p.classList.toggle('is-active', isActive);
                p.style.display = isActive ? 'block' : 'none';
            });

            if (pmNotice) pmNotice.textContent = '';
        });
    });

    // Start polling payment intent status
    const startPaymentPolling = (paymentIntentId) => {
        const intentId = paymentIntentId || currentPaymentIntentId;
        if (!intentId) return;

        if (paymentPollInterval) {
            clearInterval(paymentPollInterval);
        }

        if (pmStatusBox) pmStatusBox.hidden = false;
        if (pmStatusText) pmStatusText.textContent = 'Waiting for payment authorization…';
        startPaymentTimer(600);

        paymentPollInterval = setInterval(async () => {
            try {
                const res = await fetch('/reservation/check-payment-status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        payment_intent_id: intentId,
                    }),
                });

                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    console.warn('Status check warning:', res.status, errData);
                    return;
                }

                const data = await res.json();
                if (data.success) {
                    if (data.status === 'succeeded' || data.payment_status === 'Partially Paid') {
                        stopPaymentTimer();
                        clearInterval(paymentPollInterval);
                        paymentPollInterval = null;

                        closePaymentModal();
                        if (bookingForm) bookingForm.reset();

                        const successModal = document.getElementById('reservationSuccessModal');
                        if (successModal) {
                            successModal.classList.add('is-open');
                            successModal.setAttribute('aria-hidden', 'false');
                            updateOverlayScrollLock();
                        }
                    } else if (['failed', 'cancelled', 'expired'].includes(data.status)) {
                        showPaymentFailedModal(
                            data.status === 'expired' ? 'Payment Session Expired' : 'Payment Failed or Cancelled',
                            data.message || `Payment status: ${data.status}. No reservation was created and no charge was made.`
                        );
                    }
                }
            } catch (err) {
                console.error('Polling error:', err);
            }
        }, 3000);
    };

    // Listen for postMessage from payment return page iframe/popup
    window.addEventListener('message', (event) => {
        if (event.data && event.data.source === 'hinaguan-paymongo') {
            if (event.data.status === 'success' && currentPaymentIntentId) {
                startPaymentPolling(currentPaymentIntentId);
            }
        }
    });

    // Process Payment Method Attachment (GCash, Maya, Card, QR Ph)
    const processPayMongoPayment = async (methodType, extraData = {}) => {
        if (!currentPaymentIntentId) {
            if (pmNotice) pmNotice.textContent = 'Missing payment session. Please try again.';
            return;
        }

        if (pmNotice) pmNotice.textContent = '';
        if (pmStatusBox) pmStatusBox.hidden = false;
        if (pmStatusText) pmStatusText.textContent = 'Processing payment details…';

        const payload = {
            payment_intent_id: currentPaymentIntentId,
            client_key: currentClientKey,
            payment_method_type: methodType,
            ...extraData,
        };

        try {
            const response = await fetch('/reservation/process-payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                if (pmStatusBox) pmStatusBox.hidden = true;
                if (pmNotice) pmNotice.textContent = result.message || 'Payment processing failed.';
                return;
            }

            if (result.status === 'succeeded') {
                closePaymentModal();
                if (bookingForm) bookingForm.reset();

                const successModal = document.getElementById('reservationSuccessModal');
                if (successModal) {
                    successModal.classList.add('is-open');
                    successModal.setAttribute('aria-hidden', 'false');
                    updateOverlayScrollLock();
                }
                return;
            }

            if (result.next_action && result.next_action.redirect && result.next_action.redirect.url) {
                const redirectUrl = result.next_action.redirect.url;
                if (pmIframeContainer && pmAuthIframe) {
                    pmIframeContainer.hidden = false;
                    pmAuthIframe.src = redirectUrl;
                }
                startPaymentPolling(currentPaymentIntentId);
            } else {
                startPaymentPolling(currentPaymentIntentId);
            }
        } catch (error) {
            if (pmStatusBox) pmStatusBox.hidden = true;
            if (pmNotice) pmNotice.textContent = 'Network error while contacting payment gateway.';
        }
    };

    // Payment action button listeners
    const pmPayGcashBtn = document.getElementById('pmPayGcashBtn');
    if (pmPayGcashBtn) {
        pmPayGcashBtn.addEventListener('click', () => processPayMongoPayment('gcash'));
    }

    const pmPayMayaBtn = document.getElementById('pmPayMayaBtn');
    if (pmPayMayaBtn) {
        pmPayMayaBtn.addEventListener('click', () => processPayMongoPayment('paymaya'));
    }

    const pmGenerateQrBtn = document.getElementById('pmGenerateQrBtn');
    if (pmGenerateQrBtn) {
        pmGenerateQrBtn.addEventListener('click', () => processPayMongoPayment('qrph'));
    }

    // Card Form submit listener
    const pmCardForm = document.getElementById('pmCardForm');
    if (pmCardForm) {
        pmCardForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const cardNumber = document.getElementById('pmCardNumber')?.value.replace(/\s+/g, '') || '';
            const cardExpiry = document.getElementById('pmCardExpiry')?.value || '';
            const cvc = document.getElementById('pmCardCvc')?.value || '';

            const [expMonthStr, expYearStr] = cardExpiry.split('/');
            const expMonth = parseInt(expMonthStr, 10) || 0;
            let expYear = parseInt(expYearStr, 10) || 0;
            if (expYear < 100) expYear += 2000;

            processPayMongoPayment('card', {
                card_number: cardNumber,
                exp_month: expMonth,
                exp_year: expYear,
                cvc: cvc,
            });
        });
    }

    // Auto format Card Expiry (MM/YY) and Card Number
    const pmCardExpiryInput = document.getElementById('pmCardExpiry');
    if (pmCardExpiryInput) {
        pmCardExpiryInput.addEventListener('input', (e) => {
            let val = e.target.value.replace(/\D/g, '');
            if (val.length >= 2) {
                val = val.substring(0, 2) + ' / ' + val.substring(2, 4);
            }
            e.target.value = val;
        });
    }

    const pmCardNumberInput = document.getElementById('pmCardNumber');
    if (pmCardNumberInput) {
        pmCardNumberInput.addEventListener('input', (e) => {
            let val = e.target.value.replace(/\D/g, '');
            val = val.match(/.{1,4}/g)?.join(' ') || val;
            e.target.value = val.substring(0, 19);
        });
    }


    bookingForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!activeAmenity || isSubmitting) {
            if (!activeAmenity) {
                bookingNotice.textContent = 'Please select an amenity first.';
            }
            return;
        }

        const formData = new FormData(bookingForm);

        const sDate = mainStartDate || dateInput.value;
        const eDate = mainEndDate || sDate;
        const sSlot = mainStartSlot || selectedSlot;
        const eSlot = mainEndSlot || selectedSlot;
        const { totalDays } = calculateContinuousSlots(sDate, eDate, sSlot, eSlot);

        // Build amenities array for continuous multi-stay reservation
        let amenitiesArray = [];
        if (multiSelectionEnabled && selectedCards.length > 0) {
            amenitiesArray = selectedCards.map(card => {
                const amenityId = card.dataset.amenityId;
                const cfg = amenityStayConfig[amenityId] || {};
                const choice = cfg.choice || multiSelectionChoices[amenityId] || 'without';
                const itemSDate = cfg.startDate || sDate;
                const itemEDate = cfg.endDate || eDate || itemSDate;
                const itemSSlot = cfg.startSlot || sSlot;
                const itemESlot = cfg.endSlot || eSlot;
                const price = getAmenityContinuousPrice(card, choice, itemSDate, itemEDate, itemSSlot, itemESlot);
                const { dayCount, nightCount } = calculateContinuousSlots(itemSDate, itemEDate, itemSSlot, itemESlot);
                const pricingType = choice === 'with' ? `${itemSSlot} Aircon` : itemSSlot;

                return {
                    amenity_id: amenityId,
                    start_date: itemSDate,
                    end_date: itemEDate,
                    start_slot: itemSSlot,
                    end_slot: itemESlot,
                    pricing_type: pricingType,
                    price_at_booking: price,
                    day_slots_count: dayCount,
                    night_slots_count: nightCount,
                };
            });
        } else {
            const choice = (airconChoice && airconChoice.querySelector('[data-aircon-choice="with"]')?.classList.contains('is-selected')) ? 'with' : (multiSelectionChoices[activeAmenity.dataset.amenityId] || 'without');
            const price = getAmenityContinuousPrice(activeAmenity, choice, sDate, eDate, sSlot, eSlot);
            const { dayCount, nightCount } = calculateContinuousSlots(sDate, eDate, sSlot, eSlot);
            const pricingType = choice === 'with' ? `${sSlot} Aircon` : sSlot;

            amenitiesArray = [{
                amenity_id: activeAmenity.dataset.amenityId,
                start_date: sDate,
                end_date: eDate,
                start_slot: sSlot,
                end_slot: eSlot,
                pricing_type: pricingType,
                price_at_booking: price,
                day_slots_count: dayCount,
                night_slots_count: nightCount,
            }];
        }

        const payload = {
            booker_name: formData.get('booker_name'),
            phone: formData.get('phone'),
            email: formData.get('email'),
            number_of_guests: Number(formData.get('number_of_guests')),
            reservation_date: sDate,
            end_date: eDate,
            start_slot: sSlot,
            end_slot: eSlot,
            check_in: sDate,
            check_out: eDate,
            slot: sSlot,
            total_days: totalDays,
            amenities: amenitiesArray,
        };

        setSubmittingState(true);
        bookingNotice.textContent = 'Preparing payment options…';

        try {
            const response = await fetch('/reservation/create-intent', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json();

            if (response.ok && result.success) {
                bookingNotice.textContent = '';

                currentReservationId = result.reservation_id;
                currentPaymentIntentId = result.payment_intent_id;
                currentClientKey = result.client_key;

                if (pmSummaryTotal) pmSummaryTotal.textContent = `₱${Number(result.total_amount).toFixed(2)}`;
                if (pmSummaryDeposit) pmSummaryDeposit.textContent = `₱${Number(result.deposit_amount).toFixed(2)}`;
                if (pmSummaryBalance) pmSummaryBalance.textContent = `₱${Number(result.remaining_balance).toFixed(2)}`;

                // Close amenity modal
                if (modal) {
                    modal.classList.remove('is-open');
                    modal.setAttribute('aria-hidden', 'true');
                }

                // Open PayMongo Payment Modal (Defaults to Step 2)
                if (paymongoPaymentModal) {
                    goToStep2();
                    paymongoPaymentModal.classList.add('is-open');
                    paymongoPaymentModal.setAttribute('aria-hidden', 'false');
                    updateOverlayScrollLock();
                }
            } else {
                if (response.status === 409) {
                    const errorModal = document.getElementById('reservationErrorModal');
                    if (errorModal) {
                        const errorMessage = errorModal.querySelector('.rp-modal__error-message');
                        if (errorMessage) {
                            errorMessage.textContent = result.message || 'Your Reservation sounds like someone already booked that amenity on that slot, check and book again';
                        }
                        errorModal.classList.add('is-open');
                        errorModal.setAttribute('aria-hidden', 'false');
                        updateOverlayScrollLock();
                    }
                } else {
                    bookingNotice.textContent = result.message || 'Reservation could not be initialized.';
                }
            }
        } catch (error) {
            bookingNotice.textContent = 'Reservation could not be initialized. Please try again.';
        } finally {
            setSubmittingState(false);
        }
    });



    modalClose.forEach(button => {

        button.addEventListener('click', closeModal);

    });



    selectionCloseButtons.forEach(button => {

        button.addEventListener('click', closeSelectionSheet);

    });

    // Error modal close button
    const errorConfirmBtn = document.getElementById('errorConfirmBtn');
    if (errorConfirmBtn) {
        errorConfirmBtn.addEventListener('click', () => {
            const errorModal = document.getElementById('reservationErrorModal');
            if (errorModal) {
                errorModal.classList.remove('is-open');
                errorModal.setAttribute('aria-hidden', 'true');
                updateOverlayScrollLock();
                // Hide the booking notice text since reservation failed
                if (bookingNotice) {
                    bookingNotice.textContent = '';
                }
            }
        });
    }

    // Error modal backdrop click
    const errorModalBackdrop = document.querySelector('[data-close-error-modal]');
    if (errorModalBackdrop) {
        errorModalBackdrop.addEventListener('click', () => {
            const errorModal = document.getElementById('reservationErrorModal');
            if (errorModal) {
                errorModal.classList.remove('is-open');
                errorModal.setAttribute('aria-hidden', 'true');
                updateOverlayScrollLock();
            }
        });
    }



    availabilityCloseButtons.forEach(button => {

        button.addEventListener('click', closeAvailabilityModal);

    });



    // Cancel confirmation modal handlers
    const cancelConfirmCloseButtons = document.querySelectorAll('[data-close-cancel-confirm]');
    const confirmCancelBtn = document.getElementById('confirmCancelBtn');

    const closeCancelConfirmModal = () => {
        if (cancelConfirmModal) {
            cancelConfirmModal.classList.remove('is-open');
            cancelConfirmModal.setAttribute('aria-hidden', 'true');
            updateOverlayScrollLock();
        }
    };

    cancelConfirmCloseButtons.forEach(button => {
        button.addEventListener('click', closeCancelConfirmModal);
    });

    if (confirmCancelBtn) {
        confirmCancelBtn.addEventListener('click', () => {
            // Close confirmation modal
            closeCancelConfirmModal();

            // Close amenity modal
            if (modal) {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                updateOverlayScrollLock();
            }

            // Show skeleton for refresh and hide actual grid
            if (gridSkeleton) {
                gridSkeleton.hidden = false;
            }
            if (grid) {
                grid.hidden = true;
            }

            // Refresh the page after a brief delay for UX
            setTimeout(() => {
                window.location.reload();
            }, 300);
        });
    }

    if (cancelConfirmModal) {
        cancelConfirmModal.addEventListener('click', (event) => {
            if (event.target === cancelConfirmModal) {
                closeCancelConfirmModal();
            }
        });
    }



    if (availabilityModal) {

        availabilityModal.addEventListener('click', (event) => {

            if (event.target === availabilityModal) {

                closeAvailabilityModal();

            }

        });

    }



    availabilitySlotButtons.forEach(button => {

        button.addEventListener('click', async () => {

            calendarSlot = button.dataset.slotToggle;

            availabilitySlotButtons.forEach(slotButton => {

                slotButton.classList.toggle('is-active', slotButton.dataset.slotToggle === calendarSlot);

            });

            // Add loading state
            availabilityCalendar.classList.add('is-loading');

            try {

                const url = new URL('/reservation/availability/calendar', window.location.origin);
                url.searchParams.set('amenity_id', calendarAmenityId || '');
                url.searchParams.set('amenity_ids', calendarAmenityId || '');
                url.searchParams.set('slot', calendarSlot);

                // Include month and year from dropdowns
                const calendarMonthSelect = document.getElementById('calendarMonth');
                const calendarYearSelect = document.getElementById('calendarYear');

                if (calendarMonthSelect && calendarYearSelect && calendarMonthSelect.value !== '' && calendarYearSelect.value !== '') {
                    url.searchParams.set('month', calendarMonthSelect.value);
                    url.searchParams.set('year', calendarYearSelect.value);
                } else {
                    // Default to current month/year
                    const today = new Date();
                    url.searchParams.set('month', today.getMonth());
                    url.searchParams.set('year', today.getFullYear());
                }

                url.searchParams.set('_t', Date.now());

                const response = await fetch(url.toString(), {
                    cache: 'no-store',
                    headers: { Accept: 'application/json', 'Cache-Control': 'no-cache' },
                });



                if (!response.ok) {

                    throw new Error('Calendar availability request failed');

                }



                const payload = await response.json();

                calendarAvailability = payload.availability || [];
            } catch (error) {
                calendarAvailability = [];
            } finally {
                // Remove loading state and render calendar
                availabilityCalendar.classList.remove('is-loading');
                renderAvailabilityCalendar();
            }

        });

    });



    if (multiAirconModal) {

        document.querySelectorAll('[data-close-multi-aircon-modal]').forEach(button => {

            button.addEventListener('click', closeMultiAirconModal);

        });

    }



    if (multiAirconModal) {

        multiAirconModal.addEventListener('click', (event) => {

            if (event.target === multiAirconModal) {

                closeMultiAirconModal();

            }

        });

    }



    if (selectionSheet) {

        selectionSheet.addEventListener('click', (event) => {

            if (event.target === selectionSheet) {

                closeSelectionSheet();

            }

        });

    }



    modal.addEventListener('click', (event) => {

        if (event.target === modal) {

            closeModal();

        }

    });



    // Success modal functionality & Scroll-gated unlock
    const successModal = document.getElementById('reservationSuccessModal');
    const successScrollWrap = document.getElementById('successModalScrollBody');
    const successScrollHint = document.getElementById('successModalScrollHint');
    const successScrollHintText = document.getElementById('successModalScrollHintText');
    const successConfirmBtn = document.getElementById('successConfirmBtn');
    const successConfirmBtnText = document.getElementById('successConfirmBtnText');

    let successModalUnlocked = false;

    const unlockSuccessModal = () => {
        if (successModalUnlocked) return;
        successModalUnlocked = true;
        if (successConfirmBtn) {
            successConfirmBtn.disabled = false;
        }
        if (successConfirmBtnText) {
            successConfirmBtnText.textContent = 'Got it!';
        }
        if (successScrollHint) {
            successScrollHint.classList.add('is-completed');
            if (successScrollHintText) {
                successScrollHintText.textContent = '✓ All notices reviewed';
            }
        }
    };

    const checkSuccessScroll = () => {
        if (!successScrollWrap || successModalUnlocked) return;
        const scrollBottom = successScrollWrap.scrollHeight - successScrollWrap.scrollTop;
        const isAtBottom = scrollBottom <= successScrollWrap.clientHeight + 16;
        if (isAtBottom) {
            unlockSuccessModal();
        }
    };

    const resetSuccessModalState = () => {
        successModalUnlocked = false;
        if (successScrollWrap) {
            successScrollWrap.scrollTop = 0;
            // If content doesn't overflow (e.g. huge screen), unlock immediately
            if (successScrollWrap.scrollHeight <= successScrollWrap.clientHeight + 10) {
                unlockSuccessModal();
                if (successScrollHint) successScrollHint.style.display = 'none';
                return;
            }
        }

        if (successConfirmBtn) {
            successConfirmBtn.disabled = true;
        }
        if (successConfirmBtnText) {
            successConfirmBtnText.textContent = 'Scroll down to unlock (Got it!)';
        }
        if (successScrollHint) {
            successScrollHint.style.display = 'inline-flex';
            successScrollHint.classList.remove('is-completed');
            if (successScrollHintText) {
                successScrollHintText.textContent = 'Scroll down to review all notices';
            }
        }
    };

    if (successScrollWrap) {
        successScrollWrap.addEventListener('scroll', checkSuccessScroll, { passive: true });
    }

    if (successScrollHint && successScrollWrap) {
        successScrollHint.addEventListener('click', () => {
            successScrollWrap.scrollTo({ top: successScrollWrap.scrollHeight, behavior: 'smooth' });
        });
        successScrollHint.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                successScrollWrap.scrollTo({ top: successScrollWrap.scrollHeight, behavior: 'smooth' });
            }
        });
    }

    if (successModal) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    if (successModal.classList.contains('is-open')) {
                        setTimeout(resetSuccessModalState, 50);
                    }
                }
            });
        });
        observer.observe(successModal, { attributes: true });
    }

    const handleSuccessConfirm = () => {
        if (successConfirmBtn && !successConfirmBtn.disabled) {
            successConfirmBtn.disabled = true;
            successConfirmBtn.innerHTML = '<span class="rp-btn-spinner"></span> Refreshing page…';
            window.location.reload();
        }
    };

    if (successConfirmBtn) {
        successConfirmBtn.addEventListener('click', handleSuccessConfirm);
    }

});



// Scroll-to-booking hint: floats at the bottom while the booking area is
// below the fold, scrolls down to it on click, and reappears on scroll up.

(() => {

    const hint = document.getElementById('scrollHint');

    // Scroll target: the always-visible steps section (the CTA section gets

    // hidden once a date is picked, so it cannot anchor the reappear logic).

    const bookingAnchor = document.getElementById('bookingSteps')

        || document.getElementById('reservationGridShell')

        || document.getElementById('dateCtaSection');

    const floatingBar = document.getElementById('selectionFloatingBar');



    if (!hint || !bookingAnchor) return;



    // Show while near the top (hero is compact, so scroll-position beats

    // anchor geometry — the steps section is already inside the top 60% of

    // the viewport on load). Hide once the user scrolls down past half a

    // viewport or the selection bar is floating; reappear on scroll up.

    const updateHint = () => {

        const scrolledDown = window.scrollY > window.innerHeight * 0.5;

        const barVisible = floatingBar && !floatingBar.hidden;

        hint.classList.toggle('is-hidden', barVisible || scrolledDown);

    };



    hint.addEventListener('click', () => {

        bookingAnchor.scrollIntoView({ behavior: 'smooth', block: 'start' });

    });



    window.addEventListener('scroll', updateHint, { passive: true });

    window.addEventListener('resize', updateHint, { passive: true });

    updateHint();

})();

