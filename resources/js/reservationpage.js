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

    const cancelConfirmModal = document.getElementById('cancelConfirmModal');

    const gridSkeleton = document.getElementById('gridSkeleton');

    const datePickerModal = document.getElementById('datePickerModal');

    const reservationDateTrigger = document.getElementById('reservationDateTrigger');

    const modalClose = document.querySelectorAll('[data-close-modal]');

    const multiSelectionToggle = document.getElementById('multiSelectionToggle');

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

    const updateSlotButtonsForDate = (date) => {
        const slotDaytime = document.getElementById('slotDaytime');
        const slotNighttime = document.getElementById('slotNighttime');
        const slotDayToNight = document.getElementById('slotDayToNight');
        const slotNightToDay = document.getElementById('slotNightToDay');

        if (!slotDaytime || !slotNighttime || !slotDayToNight) return;

        const dateToEvaluate = date || (dateInput ? dateInput.value : '') || window.PARK_TODAY_DATE;
        const isTodayNight = isNighttimeForToday(dateToEvaluate);

        if (isTodayNight) {
            slotDaytime.disabled = true;
            slotDaytime.classList.add('is-disabled-slot');
            slotDaytime.setAttribute('title', 'Daytime session for today has already passed. Please select Nighttime or an upcoming date.');

            slotDayToNight.disabled = true;
            slotDayToNight.classList.add('is-disabled-slot');
            slotDayToNight.setAttribute('title', 'Whole Day is unavailable for today as daytime has concluded.');

            slotNighttime.disabled = false;
            slotNighttime.classList.remove('is-disabled-slot');
            slotNighttime.removeAttribute('title');

            if (slotNightToDay) {
                slotNightToDay.disabled = false;
                slotNightToDay.classList.remove('is-disabled-slot');
                slotNightToDay.removeAttribute('title');
            }

            if (selectedSlot === 'Daytime' || selectedSlot === 'DayToNight') {
                setActiveSlot('Nighttime');
            }
        } else {
            slotDaytime.disabled = false;
            slotDaytime.classList.remove('is-disabled-slot');
            slotDaytime.removeAttribute('title');

            slotDayToNight.disabled = false;
            slotDayToNight.classList.remove('is-disabled-slot');
            slotDayToNight.removeAttribute('title');

            slotNighttime.disabled = false;
            slotNighttime.classList.remove('is-disabled-slot');
            slotNighttime.removeAttribute('title');

            if (slotNightToDay) {
                slotNightToDay.disabled = false;
                slotNightToDay.classList.remove('is-disabled-slot');
                slotNightToDay.removeAttribute('title');
            }
        }
    };

    const updateModalSlotButtonsForDate = (date) => {
        const modalSlotDaytime = document.getElementById('modalSlotDaytime');
        const modalSlotNighttime = document.getElementById('modalSlotNighttime');
        const modalSlotDayToNight = document.getElementById('modalSlotDayToNight');
        const modalSlotNightToDay = document.getElementById('modalSlotNightToDay');

        if (!modalSlotDaytime || !modalSlotNighttime || !modalSlotDayToNight) return;

        const dateToEvaluate = date || (dateInput ? dateInput.value : '') || window.PARK_TODAY_DATE;
        const isTodayNight = isNighttimeForToday(dateToEvaluate);

        if (isTodayNight) {
            modalSlotDaytime.disabled = true;
            modalSlotDaytime.classList.add('is-disabled-slot');
            modalSlotDaytime.setAttribute('title', 'Daytime session for today has already passed.');

            modalSlotDayToNight.disabled = true;
            modalSlotDayToNight.classList.add('is-disabled-slot');
            modalSlotDayToNight.setAttribute('title', 'Whole Day is unavailable for today as daytime has concluded.');

            modalSlotNighttime.disabled = false;
            modalSlotNighttime.classList.remove('is-disabled-slot');
            modalSlotNighttime.removeAttribute('title');

            if (modalSlotNightToDay) {
                modalSlotNightToDay.disabled = false;
                modalSlotNightToDay.classList.remove('is-disabled-slot');
                modalSlotNightToDay.removeAttribute('title');
            }

            if (modalSlotDaytime.classList.contains('is-active') || modalSlotDayToNight.classList.contains('is-active')) {
                setActiveModalSlot('Nighttime');
            }
        } else {
            modalSlotDaytime.disabled = false;
            modalSlotDaytime.classList.remove('is-disabled-slot');
            modalSlotDaytime.removeAttribute('title');

            modalSlotDayToNight.disabled = false;
            modalSlotDayToNight.classList.remove('is-disabled-slot');
            modalSlotDayToNight.removeAttribute('title');

            modalSlotNighttime.disabled = false;
            modalSlotNighttime.classList.remove('is-disabled-slot');
            modalSlotNighttime.removeAttribute('title');

            if (modalSlotNightToDay) {
                modalSlotNightToDay.disabled = false;
                modalSlotNightToDay.classList.remove('is-disabled-slot');
                modalSlotNightToDay.removeAttribute('title');
            }
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
        const isAircon = choice === 'with';
        const { dayCount, nightCount } = calculateContinuousSlots(startDateStr, endDateStr, startSlot, endSlot);

        const dayPrice = isAircon
            ? Number(card.dataset.daytimeAirconPrice || card.dataset.daytimePrice || 0)
            : Number(card.dataset.daytimePrice || 0);

        const nightPrice = isAircon
            ? Number(card.dataset.nighttimeAirconPrice || card.dataset.nighttimePrice || 0)
            : Number(card.dataset.nighttimePrice || 0);

        return (dayCount * dayPrice) + (nightCount * nightPrice);
    };

    const getWeekday = (dateString) => {

        const date = new Date(dateString);

        return date.toLocaleDateString(undefined, { weekday: 'long' });

    };

    const updateReservationDay = () => {

        if (!reservationDay || !dateInput.value) return;

        reservationDay.textContent = getWeekday(dateInput.value);

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

            const response = await fetch(url.toString(), {

                headers: { Accept: 'application/json' },

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

        calendarSlot = 'Daytime';
        calendarStayMode = 'single';
        calendarRangeStart = null;
        calendarRangeEnd = null;
        calendarRangeStartSlot = 'Daytime';
        calendarRangeEndSlot = 'Daytime';

        // Reset mode tabs
        const avModeSingle = document.getElementById('avModeSingle');
        const avModeRange = document.getElementById('avModeRange');
        if (avModeSingle) avModeSingle.classList.add('is-active');
        if (avModeRange) avModeRange.classList.remove('is-active');

        const avSingleSlotToggle = document.getElementById('avSingleSlotToggle');
        const avRangeSlotWrap = document.getElementById('avRangeSlotWrap');
        const avRangeSummaryBar = document.getElementById('avRangeSummaryBar');
        const avRangeActions = document.getElementById('avRangeActions');

        if (avSingleSlotToggle) avSingleSlotToggle.style.display = 'flex';
        if (avRangeSlotWrap) avRangeSlotWrap.style.display = 'none';
        if (avRangeSummaryBar) avRangeSummaryBar.style.display = 'none';
        if (avRangeActions) avRangeActions.style.display = 'none';

        availabilitySlotButtons.forEach(button => {
            button.classList.toggle('is-active', button.dataset.slotToggle === calendarSlot);
        });

        // Initialize month and year dropdowns
        const calendarMonthSelect = document.getElementById('calendarMonth');
        const calendarYearSelect = document.getElementById('calendarYear');

        const fetchCalendarData = async () => {
            const selectedMonth = calendarMonthSelect ? calendarMonthSelect.value : new Date().getMonth();
            const selectedYear = calendarYearSelect ? calendarYearSelect.value : new Date().getFullYear();

            availabilityCalendar.classList.add('is-loading');

            try {
                const url = new URL('/reservation/availability/calendar', window.location.origin);
                url.searchParams.set('amenity_ids', targetAmenityIds);
                url.searchParams.set('slot', calendarSlot);
                url.searchParams.set('month', selectedMonth);
                url.searchParams.set('year', selectedYear);

                const response = await fetch(url.toString(), {
                    headers: { Accept: 'application/json' },
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

            calendarMonthSelect.onchange = fetchCalendarData;
            calendarYearSelect.onchange = fetchCalendarData;
        }

        calendarAvailability = [];
        renderAvailabilityCalendar();

        availabilityModal.classList.add('is-open');
        availabilityModal.setAttribute('aria-hidden', 'false');
        updateOverlayScrollLock();

        fetchCalendarData();
    };

    // Mode toggles inside availability modal
    const avModeSingle = document.getElementById('avModeSingle');
    const avModeRange = document.getElementById('avModeRange');
    const avSingleSlotToggle = document.getElementById('avSingleSlotToggle');
    const avRangeSlotWrap = document.getElementById('avRangeSlotWrap');
    const avRangeSummaryBar = document.getElementById('avRangeSummaryBar');
    const avRangeActions = document.getElementById('avRangeActions');
    const avRangeStartLabel = document.getElementById('avRangeStartLabel');
    const avRangeEndLabel = document.getElementById('avRangeEndLabel');
    const avRangeDaysBadge = document.getElementById('avRangeDaysBadge');
    const avApplyRangeBtn = document.getElementById('avApplyRangeBtn');

    if (avModeSingle && avModeRange) {
        avModeSingle.addEventListener('click', () => {
            calendarStayMode = 'single';
            avModeSingle.classList.add('is-active');
            avModeRange.classList.remove('is-active');
            if (avSingleSlotToggle) avSingleSlotToggle.style.display = 'flex';
            if (avRangeSlotWrap) avRangeSlotWrap.style.display = 'none';
            if (avRangeSummaryBar) avRangeSummaryBar.style.display = 'none';
            if (avRangeActions) avRangeActions.style.display = 'none';
            calendarRangeStart = null;
            calendarRangeEnd = null;
            renderAvailabilityCalendar();
        });

        avModeRange.addEventListener('click', () => {
            calendarStayMode = 'range';
            avModeRange.classList.add('is-active');
            avModeSingle.classList.remove('is-active');
            if (avSingleSlotToggle) avSingleSlotToggle.style.display = 'none';
            if (avRangeSlotWrap) avRangeSlotWrap.style.display = 'grid';
            if (avRangeSummaryBar) avRangeSummaryBar.style.display = 'flex';
            if (avRangeActions) avRangeActions.style.display = 'block';
            calendarRangeStart = null;
            calendarRangeEnd = null;
            if (avRangeStartLabel) avRangeStartLabel.textContent = 'Pick start date';
            if (avRangeEndLabel) avRangeEndLabel.textContent = 'Pick end date';
            if (avRangeDaysBadge) avRangeDaysBadge.textContent = '1 Day';
            renderAvailabilityCalendar();
        });
    }

    // Availability range slot toggles
    document.querySelectorAll('[data-av-start-slot]').forEach(btn => {
        btn.addEventListener('click', () => {
            const slot = btn.dataset.avStartSlot;
            if (calendarRangeStart && isNighttimeForToday(calendarRangeStart) && slot === 'Daytime') {
                return;
            }
            calendarRangeStartSlot = slot;
            document.querySelectorAll('[data-av-start-slot]').forEach(b => {
                b.classList.toggle('is-active', b.dataset.avStartSlot === calendarRangeStartSlot);
            });
            updateAvRangeDisplay();
            renderAvailabilityCalendar();
        });
    });

    document.querySelectorAll('[data-av-end-slot]').forEach(btn => {
        btn.addEventListener('click', () => {
            calendarRangeEndSlot = btn.dataset.avEndSlot;
            document.querySelectorAll('[data-av-end-slot]').forEach(b => {
                b.classList.toggle('is-active', b.dataset.avEndSlot === calendarRangeEndSlot);
            });
            updateAvRangeDisplay();
            renderAvailabilityCalendar();
        });
    });

    const updateAvRangeDisplay = () => {
        if (!calendarRangeStart) return;
        const startObj = new Date(calendarRangeStart + 'T00:00:00');
        const endObj = calendarRangeEnd ? new Date(calendarRangeEnd + 'T00:00:00') : startObj;
        const { dayCount, nightCount, totalDays } = calculateContinuousSlots(calendarRangeStart, calendarRangeEnd || calendarRangeStart, calendarRangeStartSlot, calendarRangeEndSlot);

        if (calendarRangeStart && isNighttimeForToday(calendarRangeStart)) {
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

        if (avRangeStartLabel) {
            avRangeStartLabel.textContent = `${startObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} (${calendarRangeStartSlot})`;
        }
        if (avRangeEndLabel) {
            avRangeEndLabel.textContent = calendarRangeEnd
                ? `${endObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} (${calendarRangeEndSlot})`
                : 'Select end date';
        }
        if (avRangeDaysBadge) {
            avRangeDaysBadge.textContent = `${totalDays} Day${totalDays > 1 ? 's' : ''} (${dayCount}D ${nightCount}N)`;
        }
    };

    if (avApplyRangeBtn) {
        avApplyRangeBtn.addEventListener('click', () => {
            if (!calendarRangeStart) return;
            const targetCard = (multiSelectionEnabled && selectedCards.length > 0)
                ? selectedCards[0]
                : calendarSourceCard;
            if (!targetCard) return;

            const finalEnd = calendarRangeEnd || calendarRangeStart;
            mainStartDate = calendarRangeStart;
            mainEndDate = finalEnd;
            mainStartSlot = calendarRangeStartSlot;
            mainEndSlot = calendarRangeEndSlot;
            stayMode = 'range';

            if (isNighttimeForToday(mainStartDate) && mainStartSlot === 'Daytime') {
                mainStartSlot = 'Nighttime';
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
            if (reservationDateTrigger) {
                const sObj = new Date(mainStartDate + 'T00:00:00');
                const eObj = new Date(mainEndDate + 'T00:00:00');
                reservationDateTrigger.textContent = `${sObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} – ${eObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
            }

            closeAvailabilityModal();
            refreshAvailability();
            window.setTimeout(() => {
                openModal(targetCard);
            }, 100);
        });
    }

    const closeAvailabilityModal = () => {
        if (!availabilityModal) return;
        availabilityModal.classList.remove('is-open');
        availabilityModal.setAttribute('aria-hidden', 'true');
        updateOverlayScrollLock();
    };

    const isRangeAvailable = (startDate, endDate, startSlot, endSlot, availList) => {
        if (!availList || availList.length === 0) return true;
        const startObj = new Date(startDate + 'T00:00:00');
        const endObj = new Date(endDate + 'T00:00:00');
        const daysDiff = Math.round((endObj - startObj) / (1000 * 60 * 60 * 24));
        if (daysDiff < 0) return false;

        const cleanStartSlot = (startSlot === 'DayToNight' || startSlot === 'daytonight' || (startSlot && startSlot.startsWith('Day'))) ? 'Daytime' : 'Nighttime';
        const cleanEndSlot = (endSlot === 'DayToNight' || endSlot === 'daytonight' || (endSlot && endSlot.includes('Night'))) ? 'Nighttime' : 'Daytime';

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

        availabilityCalendar.innerHTML = '';

        ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach((weekday) => {
            const label = document.createElement('span');
            label.className = 'rp-calendar__weekday';
            label.textContent = weekday;
            availabilityCalendar.appendChild(label);
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
            availabilityCalendar.appendChild(spacer);
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const days = Array.from({ length: daysInMonth }, (_, index) => {
            const date = new Date(selectedYear, selectedMonth, index + 1);
            const isoDate = date.getFullYear() + '-' + 
                String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                String(date.getDate()).padStart(2, '0');

            let slotKey = calendarSlot.toLowerCase();
            let isAvailable = false;

            const entry = calendarAvailability.find((e) => e.date === isoDate);

            if (entry) {
                if (calendarStayMode === 'range') {
                    if (!calendarRangeStart) {
                        // Picking start date
                        if (calendarRangeStartSlot === 'Nighttime') {
                            isAvailable = entry.nighttime === true;
                        } else {
                            isAvailable = entry.daytime === true;
                        }
                    } else {
                        // Picking end date
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
                } else {
                    if (slotKey === 'daytime') {
                        isAvailable = entry.daytime === true;
                    } else if (slotKey === 'nighttime') {
                        isAvailable = entry.nighttime === true;
                    } else if (slotKey === 'daytonight') {
                        isAvailable = entry.daytonight === true;
                    } else if (slotKey === 'nighttoday') {
                        isAvailable = entry.nighttoday === true;
                    }
                }
            }

            const isPast = date < today;
            const isToday = isoDate === (window.PARK_TODAY_DATE || (new Date().getFullYear() + '-' + String(new Date().getMonth() + 1).padStart(2, '0') + '-' + String(new Date().getDate()).padStart(2, '0')));
            if (isToday && isNighttimeForToday(isoDate)) {
                if (calendarStayMode === 'range') {
                    if (!calendarRangeStart && calendarRangeStartSlot === 'Daytime') {
                        isAvailable = false;
                    }
                } else {
                    if (slotKey === 'daytime' || slotKey === 'daytonight') {
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

            // Range highlighting classes
            if (calendarStayMode === 'range') {
                if (calendarRangeStart === isoDate) {
                    dayButton.classList.add('is-range-start');
                }
                if (calendarRangeEnd === isoDate) {
                    dayButton.classList.add('is-range-end');
                }
                if (calendarRangeStart && calendarRangeEnd && isoDate > calendarRangeStart && isoDate < calendarRangeEnd) {
                    dayButton.classList.add('is-in-range');
                }
            }

            dayButton.innerHTML = `
                <span class="rp-calendar__day-num">${date.getDate()}</span>
                <span class="rp-calendar__day-month">${date.toLocaleDateString('en', { month: 'short' })}</span>
            `;

            dayButton.addEventListener('click', () => {
                if (!isAvailable) return;

                if (calendarStayMode === 'single') {
                    mainStartDate = isoDate;
                    mainEndDate = isoDate;
                    mainStartSlot = calendarSlot;
                    mainEndSlot = calendarSlot;
                    stayMode = 'single';

                    if (isNighttimeForToday(isoDate) && (mainStartSlot === 'Daytime' || mainStartSlot === 'DayToNight')) {
                        mainStartSlot = 'Nighttime';
                        mainEndSlot = 'Nighttime';
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

                    if (dateInput) dateInput.value = isoDate;
                    updateReservationDay();
                    updateSlotButtonsForDate(isoDate);
                    selectedSlot = isNighttimeForToday(isoDate) && (calendarSlot === 'Daytime' || calendarSlot === 'DayToNight')
                        ? 'Nighttime'
                        : calendarSlot;

                    slotButtons.forEach(button => {
                        button.classList.toggle('is-active', button.dataset.slot === selectedSlot);
                    });

                    closeAvailabilityModal();

                    window.setTimeout(() => {
                        refreshAvailability();
                        const targetCard = (multiSelectionEnabled && selectedCards.length > 0)
                            ? selectedCards[0]
                            : calendarSourceCard;
                        if (targetCard) {
                            openModal(targetCard);
                        }
                    }, 100);
                } else {
                    // Multi-day stay range pick
                    if (!calendarRangeStart || (calendarRangeStart && calendarRangeEnd)) {
                        calendarRangeStart = isoDate;
                        calendarRangeEnd = null;
                    } else if (calendarRangeStart && !calendarRangeEnd) {
                        if (isoDate < calendarRangeStart) {
                            calendarRangeStart = isoDate;
                            calendarRangeEnd = null;
                        } else {
                            calendarRangeEnd = isoDate;
                        }
                    }
                    updateAvRangeDisplay();
                    renderAvailabilityCalendar();
                }
            });

            return dayButton;
        });

        days.forEach((day) => availabilityCalendar.appendChild(day));
    };



    const isAvailableForSlot = (card, dateString, slot) => {

        if (!dateString || !slot) {

            return true;

        }



        return !occupiedAmenityIds.includes(card.dataset.amenityId);

    };



    const applyFilters = () => {

        let visibleCount = 0;

        const mode = filterType.value;

        const min = Number(filterMin.value);

        const max = Number(filterMax.value);



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



            const visible = filterMatch;

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



        if (grid) {

            grid.style.display = visibleCount > 0 ? 'grid' : 'none';

        }



        if (emptyState) {

            emptyState.style.display = visibleCount > 0 ? 'none' : 'block';

        }

    };



    const setActiveSlot = (slot) => {

        selectedSlot = slot;

        slotButtons.forEach(button => {

            button.classList.toggle('is-active', button.dataset.slot === slot);

        });

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
            if (modalMultiAmenityContainer) modalMultiAmenityContainer.style.display = 'none';

            const { dayCount, nightCount, totalDays } = calculateContinuousSlots(sDate, eDate, sSlot, eSlot);
            const singlePrice = getAmenityContinuousPrice(card, choice, sDate, eDate, sSlot, eSlot);

            const dayPrice = isAircon ? Number(card.dataset.daytimeAirconPrice || card.dataset.daytimePrice || 0) : Number(card.dataset.daytimePrice || 0);
            const nightPrice = isAircon ? Number(card.dataset.nighttimeAirconPrice || card.dataset.nighttimePrice || 0) : Number(card.dataset.nighttimePrice || 0);

            modalPriceLabel.textContent = isAircon ? 'Aircon package' : 'Standard package';
            modalPriceValue.textContent = `₱${singlePrice.toFixed(2)}`;
            modalPriceHint.textContent = `${totalDays} Day${totalDays > 1 ? 's' : ''} Stay (${dayCount} Daytime${dayCount > 1 ? 's' : ''} × ₱${dayPrice.toFixed(2)} + ${nightCount} Nighttime${nightCount > 1 ? 's' : ''} × ₱${nightPrice.toFixed(2)}) = ₱${singlePrice.toFixed(2)}`;

            const salePercentage = parseFloat(card.dataset.salePercentage) || 0;
            if (salePercentage > 0) {
                const originalPrice = isAircon
                    ? (card.dataset.originalDaytimeAirconPrice || card.dataset.originalNighttimeAirconPrice || singlePrice)
                    : (card.dataset.originalDaytimePrice || card.dataset.originalNighttimePrice || singlePrice);
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

            const { dayCount, nightCount, totalDays } = calculateContinuousSlots(sDate, eDate, sSlot, eSlot);
            const choiceLabel = `${choice === 'with' ? 'With Aircon' : 'Standard'} · ${totalDays}D (${dayCount}D ${nightCount}N)`;

            const line = document.createElement('li');
            line.className = 'rp-selection-sheet__item';
            line.innerHTML = `
                <div class="rp-selection-sheet__item-main">
                    <strong>${card.dataset.name || 'Selected amenity'}</strong>
                    <span>${choiceLabel}</span>
                </div>
                <div class="rp-selection-sheet__item-price">₱${price.toFixed(2)}</div>
            `;
            selectionSummaryList.appendChild(line);
            parts.push(`₱${price.toFixed(2)}`);
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
        const count = selectedCards.length;
        const total = getSelectionTotal();

        if (selectionFloatingBar) {
            selectionFloatingBar.hidden = !multiSelectionEnabled || count === 0;
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
            card.classList.toggle('is-selected', multiSelectionEnabled && isSelected);
            const overlay = card.querySelector('.rp-card__overlay');
            if (overlay) {
                overlay.classList.toggle('is-selected', multiSelectionEnabled && isSelected);
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

        if (multiSelectionEnabled && hasAircon) {
            openMultiAirconModal(card);
            return;
        }

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

        const hasAircon = card.dataset.hasAircon === '1';
        if (hasAircon && (!multiSelectionEnabled || selectedCards.length <= 1)) {
            airconChoice.innerHTML = `
                <button type="button" class="rp-choice-btn ${currentChoice === 'with' ? 'is-selected' : ''}" data-aircon-choice="with">With Aircon</button>
                <button type="button" class="rp-choice-btn ${currentChoice === 'without' ? 'is-selected' : ''}" data-aircon-choice="without">Without Aircon</button>
            `;
            airconChoice.style.display = 'flex';
            renderBookingSelection(card, currentChoice);
        } else {
            airconChoice.innerHTML = '';
            airconChoice.style.display = 'none';
            renderBookingSelection(card, 'without');
        }

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



    if (dateInput) {

        if (preselectedDate) {

            dateInput.value = preselectedDate;

            updateReservationDay();

            refreshAvailability();

            loadWeatherPreview(dateInput.value);

            // If date is preselected, hide CTA and show controls

            if (dateCtaSection) {

                dateCtaSection.hidden = true;

            }

            if (dateControlsSection) {

                dateControlsSection.hidden = false;

            }

            if (slotControlsSection) {

                slotControlsSection.hidden = false;

            }

        }

        

        // Handle date selection to show controls

        const handleDateSelection = () => {

            if (dateInput.value) {

                if (dateCtaSection) {

                    dateCtaSection.hidden = true;

                }

                if (dateControlsSection) {

                    dateControlsSection.hidden = false;

                }

                if (slotControlsSection) {

                    slotControlsSection.hidden = false;

                }

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
    const dpModeSingle = document.getElementById('dpModeSingle');
    const dpModeRange = document.getElementById('dpModeRange');
    const dpSingleSlotWrap = document.getElementById('dpSingleSlotWrap');
    const dpRangeSlotWrap = document.getElementById('dpRangeSlotWrap');
    const dpRangeSummaryBar = document.getElementById('dpRangeSummaryBar');
    const dpRangeActions = document.getElementById('dpRangeActions');
    const dpRangeStartLabel = document.getElementById('dpRangeStartLabel');
    const dpRangeEndLabel = document.getElementById('dpRangeEndLabel');
    const dpRangeDaysBadge = document.getElementById('dpRangeDaysBadge');
    const dpApplyRangeBtn = document.getElementById('dpApplyRangeBtn');

    if (dpModeSingle && dpModeRange) {
        dpModeSingle.addEventListener('click', () => {
            dpStayMode = 'single';
            dpModeSingle.classList.add('is-active');
            dpModeRange.classList.remove('is-active');
            if (dpSingleSlotWrap) dpSingleSlotWrap.style.display = 'block';
            if (dpRangeSlotWrap) dpRangeSlotWrap.style.display = 'none';
            if (dpRangeSummaryBar) dpRangeSummaryBar.style.display = 'none';
            if (dpRangeActions) dpRangeActions.style.display = 'none';
            dpRangeStart = null;
            dpRangeEnd = null;
            renderDatePickerDays();
        });

        dpModeRange.addEventListener('click', () => {
            dpStayMode = 'range';
            dpModeRange.classList.add('is-active');
            dpModeSingle.classList.remove('is-active');
            if (dpSingleSlotWrap) dpSingleSlotWrap.style.display = 'none';
            if (dpRangeSlotWrap) dpRangeSlotWrap.style.display = 'grid';
            if (dpRangeSummaryBar) dpRangeSummaryBar.style.display = 'flex';
            if (dpRangeActions) dpRangeActions.style.display = 'block';
            dpRangeStart = null;
            dpRangeEnd = null;
            if (dpRangeStartLabel) dpRangeStartLabel.textContent = 'Select start date';
            if (dpRangeEndLabel) dpRangeEndLabel.textContent = 'Select end date';
            if (dpRangeDaysBadge) dpRangeDaysBadge.textContent = '1 Day';
            renderDatePickerDays();
        });
    }

    // Range slot toggles
    document.querySelectorAll('[data-range-start-slot]').forEach(btn => {
        btn.addEventListener('click', () => {
            const slot = btn.dataset.rangeStartSlot;
            if (dpRangeStart && isNighttimeForToday(dpRangeStart) && slot === 'Daytime') {
                return;
            }
            dpRangeStartSlot = slot;
            document.querySelectorAll('[data-range-start-slot]').forEach(b => {
                b.classList.toggle('is-active', b.dataset.rangeStartSlot === dpRangeStartSlot);
            });
            updateDpRangeDisplay();
            renderDatePickerDays();
        });
    });

    document.querySelectorAll('[data-range-end-slot]').forEach(btn => {
        btn.addEventListener('click', () => {
            dpRangeEndSlot = btn.dataset.rangeEndSlot;
            document.querySelectorAll('[data-range-end-slot]').forEach(b => {
                b.classList.toggle('is-active', b.dataset.rangeEndSlot === dpRangeEndSlot);
            });
            updateDpRangeDisplay();
            renderDatePickerDays();
        });
    });

    const updateDpRangeDisplay = () => {
        if (!dpRangeStart) return;
        const startObj = new Date(dpRangeStart + 'T00:00:00');
        const endObj = dpRangeEnd ? new Date(dpRangeEnd + 'T00:00:00') : startObj;
        const { dayCount, nightCount, totalDays } = calculateContinuousSlots(dpRangeStart, dpRangeEnd || dpRangeStart, dpRangeStartSlot, dpRangeEndSlot);

        if (dpRangeStart && isNighttimeForToday(dpRangeStart)) {
            const dayBtn = document.querySelector('[data-range-start-slot="Daytime"]');
            if (dayBtn) {
                dayBtn.disabled = true;
                dayBtn.classList.add('is-disabled-slot');
            }
            if (dpRangeStartSlot === 'Daytime') {
                dpRangeStartSlot = 'Nighttime';
                document.querySelectorAll('[data-range-start-slot]').forEach(b => {
                    b.classList.toggle('is-active', b.dataset.rangeStartSlot === dpRangeStartSlot);
                });
            }
        } else {
            const dayBtn = document.querySelector('[data-range-start-slot="Daytime"]');
            if (dayBtn) {
                dayBtn.disabled = false;
                dayBtn.classList.remove('is-disabled-slot');
            }
        }

        if (dpRangeStartLabel) {
            dpRangeStartLabel.textContent = `${startObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} (${dpRangeStartSlot})`;
        }
        if (dpRangeEndLabel) {
            dpRangeEndLabel.textContent = dpRangeEnd
                ? `${endObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} (${dpRangeEndSlot})`
                : 'Select end date';
        }
        if (dpRangeDaysBadge) {
            dpRangeDaysBadge.textContent = `${totalDays} Day${totalDays > 1 ? 's' : ''} (${dayCount}D ${nightCount}N)`;
        }
    };

    if (dpApplyRangeBtn) {
        dpApplyRangeBtn.addEventListener('click', () => {
            if (!dpRangeStart) return;
            const finalEnd = dpRangeEnd || dpRangeStart;
            mainStartDate = dpRangeStart;
            mainEndDate = finalEnd;
            mainStartSlot = dpRangeStartSlot;
            mainEndSlot = dpRangeEndSlot;
            stayMode = 'range';

            if (isNighttimeForToday(mainStartDate) && mainStartSlot === 'Daytime') {
                mainStartSlot = 'Nighttime';
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
            if (reservationDateTrigger) {
                const sObj = new Date(mainStartDate + 'T00:00:00');
                const eObj = new Date(mainEndDate + 'T00:00:00');
                const { dayCount, nightCount, totalDays } = calculateContinuousSlots(mainStartDate, mainEndDate, mainStartSlot, mainEndSlot);
                reservationDateTrigger.textContent = `${sObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} – ${eObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} · ${totalDays}D (${dayCount}D ${nightCount}N)`;
            }

            closeDatePickerModal();
            updateReservationDay();
            refreshAvailability();
            fetchWeatherForDate(mainStartDate);

            const targetCard = (multiSelectionEnabled && selectedCards.length > 0)
                ? selectedCards[0]
                : (calendarSourceCard || activeAmenity);
            if (targetCard) {
                window.setTimeout(() => {
                    openModal(targetCard);
                }, 100);
            }
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
            }
            url.searchParams.set('month', selectedMonth);
            url.searchParams.set('year', selectedYear);

            const response = await fetch(url.toString(), {
                headers: { Accept: 'application/json' },
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

        const today = new Date();
        const currentMonth = today.getMonth();
        const currentYear = today.getFullYear();

        if (datePickerMonth) datePickerMonth.value = currentMonth;

        if (datePickerYear) {
            datePickerYear.innerHTML = '';
            for (let year = currentYear; year <= currentYear + 2; year++) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                datePickerYear.appendChild(option);
            }
            datePickerYear.value = currentYear;
        }

        if (datePickerMonth) datePickerMonth.onchange = fetchDatePickerAvailability;
        if (datePickerYear) datePickerYear.onchange = fetchDatePickerAvailability;

        fetchDatePickerAvailability();

        datePickerModal.classList.add('is-open');
        datePickerModal.setAttribute('aria-hidden', 'false');
        updateOverlayScrollLock();
    };

    const closeDatePickerModal = () => {
        if (!datePickerModal) return;
        datePickerModal.classList.remove('is-open');
        datePickerModal.setAttribute('aria-hidden', 'true');
        updateOverlayScrollLock();
    };

    const renderDatePickerDays = () => {
        if (!datePickerDays || !datePickerMonth || !datePickerYear) return;

        const selectedMonth = parseInt(datePickerMonth.value);
        const selectedYear = parseInt(datePickerYear.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const daysInMonth = new Date(selectedYear, selectedMonth + 1, 0).getDate();
        const firstDayOfMonth = new Date(selectedYear, selectedMonth, 1).getDay();

        datePickerDays.innerHTML = '';

        ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach((weekday) => {
            const label = document.createElement('span');
            label.className = 'rp-calendar__weekday';
            label.textContent = weekday;
            datePickerDays.appendChild(label);
        });

        for (let i = 0; i < firstDayOfMonth; i++) {
            const emptyCell = document.createElement('button');
            emptyCell.type = 'button';
            emptyCell.className = 'rp-calendar__day rp-calendar__day--empty';
            emptyCell.disabled = true;
            datePickerDays.appendChild(emptyCell);
        }

        updateModalSlotButtonsForDate();

        const days = Array.from({ length: daysInMonth }, (_, index) => {
            const date = new Date(selectedYear, selectedMonth, index + 1);
            const isoDate = date.getFullYear() + '-' +
                String(date.getMonth() + 1).padStart(2, '0') + '-' +
                String(date.getDate()).padStart(2, '0');

            const isPast = date < today;
            const isToday = date.getTime() === today.getTime();

            let isAvailable = !isPast;

            const modalSlotDaytime = document.getElementById('modalSlotDaytime');
            const modalSlotNighttime = document.getElementById('modalSlotNighttime');
            const modalSlotDayToNight = document.getElementById('modalSlotDayToNight');
            const modalSlotNightToDay = document.getElementById('modalSlotNightToDay');

            let currentModalSlot = 'Daytime';
            if (modalSlotDaytime && modalSlotDaytime.classList.contains('is-active')) {
                currentModalSlot = 'Daytime';
            } else if (modalSlotNighttime && modalSlotNighttime.classList.contains('is-active')) {
                currentModalSlot = 'Nighttime';
            } else if (modalSlotDayToNight && modalSlotDayToNight.classList.contains('is-active')) {
                currentModalSlot = 'DayToNight';
            } else if (modalSlotNightToDay && modalSlotNightToDay.classList.contains('is-active')) {
                currentModalSlot = 'NightToDay';
            }

            // Check availability from fetched availability array
            if (datePickerAvailability.length > 0) {
                const entry = datePickerAvailability.find((e) => e.date === isoDate);
                if (entry) {
                    if (dpStayMode === 'range') {
                        if (!dpRangeStart) {
                            // Picking start date
                            if (dpRangeStartSlot === 'Nighttime') {
                                isAvailable = entry.nighttime === true;
                            } else {
                                isAvailable = entry.daytime === true;
                            }
                        } else {
                            // Picking end date
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
                    } else {
                        const slotKey = currentModalSlot.toLowerCase();
                        if (slotKey === 'daytime') {
                            isAvailable = entry.daytime === true;
                        } else if (slotKey === 'nighttime') {
                            isAvailable = entry.nighttime === true;
                        } else if (slotKey === 'daytonight') {
                            isAvailable = entry.daytonight === true;
                        } else if (slotKey === 'nighttoday') {
                            isAvailable = entry.nighttoday === true;
                        }
                    }
                }
            }

            if (isToday) {
                const isNight = isNighttimeForToday(isoDate);
                if (isNight) {
                    if (dpStayMode === 'range') {
                        if (!dpRangeStart && dpRangeStartSlot === 'Daytime') {
                            isAvailable = false;
                        }
                    } else {
                        const slotKey = currentModalSlot.toLowerCase();
                        if (slotKey === 'daytime' || slotKey === 'daytonight') {
                            isAvailable = false;
                        }
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

            // Range highlighting classes
            if (dpStayMode === 'range') {
                if (dpRangeStart === isoDate) {
                    dayButton.classList.add('is-range-start');
                }
                if (dpRangeEnd === isoDate) {
                    dayButton.classList.add('is-range-end');
                }
                if (dpRangeStart && dpRangeEnd && isoDate > dpRangeStart && isoDate < dpRangeEnd) {
                    dayButton.classList.add('is-in-range');
                }
            }

            dayButton.innerHTML = `
                <span class="rp-calendar__day-num">${date.getDate()}</span>
                <span class="rp-calendar__day-month">${date.toLocaleDateString('en', { month: 'short' })}</span>
            `;

            if (isAvailable) {
                dayButton.addEventListener('click', () => {
                    if (dpStayMode === 'single') {
                        if (dateInput) {
                            dateInput.value = isoDate;
                        }
                        mainStartDate = isoDate;
                        mainEndDate = isoDate;
                        stayMode = 'single';

                        const chosenSlot = isNighttimeForToday(isoDate) && (currentModalSlot === 'Daytime' || currentModalSlot === 'DayToNight')
                            ? 'Nighttime'
                            : currentModalSlot;

                        mainStartSlot = chosenSlot;
                        mainEndSlot = chosenSlot;
                        updateSlotButtonsForDate(isoDate);
                        setActiveSlot(chosenSlot);

                        if (reservationDateTrigger) {
                            const formattedDate = date.toLocaleDateString('en-US', {
                                weekday: 'long',
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            });
                            reservationDateTrigger.textContent = formattedDate;
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

                        closeDatePickerModal();
                        updateReservationDay();
                        refreshAvailability();
                        fetchWeatherForDate(isoDate);

                        const targetCard = (multiSelectionEnabled && selectedCards.length > 0)
                            ? selectedCards[0]
                            : (calendarSourceCard || activeAmenity);
                        if (targetCard) {
                            window.setTimeout(() => {
                                openModal(targetCard);
                            }, 100);
                        }
                    } else {
                        // Multi-day date range click
                        if (!dpRangeStart || (dpRangeStart && dpRangeEnd)) {
                            dpRangeStart = isoDate;
                            dpRangeEnd = null;
                        } else if (dpRangeStart && !dpRangeEnd) {
                            if (isoDate < dpRangeStart) {
                                dpRangeStart = isoDate;
                                dpRangeEnd = null;
                            } else {
                                dpRangeEnd = isoDate;
                            }
                        }
                        updateDpRangeDisplay();
                        renderDatePickerDays();
                    }
                });
            }

            return dayButton;
        });

        days.forEach(day => datePickerDays.appendChild(day));
    };

    // Event listeners for date picker
    if (reservationDateTrigger) {
        reservationDateTrigger.addEventListener('click', openDatePickerModal);
    }

    // Event listeners for modal slot buttons
    const modalSlotDaytime = document.getElementById('modalSlotDaytime');
    const modalSlotNighttime = document.getElementById('modalSlotNighttime');
    const modalSlotDayToNight = document.getElementById('modalSlotDayToNight');
    const modalSlotNightToDay = document.getElementById('modalSlotNightToDay');

    if (modalSlotDaytime) {
        modalSlotDaytime.addEventListener('click', () => setActiveModalSlot('Daytime'));
    }

    if (modalSlotNighttime) {
        modalSlotNighttime.addEventListener('click', () => setActiveModalSlot('Nighttime'));
    }

    if (modalSlotDayToNight) {
        modalSlotDayToNight.addEventListener('click', () => setActiveModalSlot('DayToNight'));
    }

    if (modalSlotNightToDay) {
        modalSlotNightToDay.addEventListener('click', () => setActiveModalSlot('NightToDay'));
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

    const setActiveModalSlot = (slot) => {
        const modalSlotDaytime = document.getElementById('modalSlotDaytime');
        const modalSlotNighttime = document.getElementById('modalSlotNighttime');
        const modalSlotDayToNight = document.getElementById('modalSlotDayToNight');
        const modalSlotNightToDay = document.getElementById('modalSlotNightToDay');

        if (modalSlotDaytime) modalSlotDaytime.classList.toggle('is-active', slot === 'Daytime');
        if (modalSlotNighttime) modalSlotNighttime.classList.toggle('is-active', slot === 'Nighttime');
        if (modalSlotDayToNight) modalSlotDayToNight.classList.toggle('is-active', slot === 'DayToNight');
        if (modalSlotNightToDay) modalSlotNightToDay.classList.toggle('is-active', slot === 'NightToDay');

        renderDatePickerDays();
    };

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



    [filterMin, filterMax].forEach(input => {

        input.addEventListener('input', () => {

            if (filterType.value !== 'all') {

                applyFilters();

            }

        });

    });



    if (multiSelectionToggle) {
        multiSelectionToggle.addEventListener('change', () => {
            multiSelectionEnabled = multiSelectionToggle.checked;
            if (!multiSelectionEnabled) {
                selectedCards = [];
                multiSelectionChoices = {};
                for (const k in amenityStayConfig) delete amenityStayConfig[k];
            }
            updateSelectionUi();
        });
    }

    cards.forEach(card => {
        card.addEventListener('click', (e) => {
            if (multiSelectionEnabled && !e.target.closest('[data-open-modal]')) {
                toggleCardSelection(card);
            }
        });
    });

    // Handle "Pick a Date" CTA button
    const pickDateBtn = document.getElementById('pickDateBtn');
    const dateCtaSection = document.getElementById('dateCtaSection');
    const dateControlsSection = document.getElementById('dateControlsSection');
    const slotControlsSection = document.getElementById('slotControlsSection');

    if (pickDateBtn) {
        pickDateBtn.addEventListener('click', () => {
            if (dateCtaSection) {
                dateCtaSection.hidden = true;
            }
            if (dateControlsSection) {
                dateControlsSection.hidden = false;
            }
            if (slotControlsSection) {
                slotControlsSection.hidden = true;
            }
            openDatePickerModal();
        });
    }

    document.querySelectorAll('[data-open-modal]').forEach(button => {
        button.addEventListener('click', (e) => {
            if (isLoadingAvailability) {
                return;
            }

            const card = button.closest('.rp-card');
            if (!card) {
                return;
            }

            if (multiSelectionEnabled) {
                toggleCardSelection(card);
                return;
            }

            if (card.classList.contains('is-booked')) {
                return;
            }

            // If no date selected yet, open the calendar modal first
            if (!dateInput || !dateInput.value) {
                openAvailabilityModal(card);
                return;
            }

            // Date is already selected, open the booking details modal
            openModal(card);
        });
    });

    if (selectionCheckoutBtn) {
        selectionCheckoutBtn.addEventListener('click', () => {
            if (multiSelectionEnabled && selectedCards.length > 0) {
                if (!dateInput || !dateInput.value) {
                    openDatePickerModal();
                    return;
                }
            }

            openSelectionSheet();
        });
    }



    if (selectionContinueBtn) {

        selectionContinueBtn.addEventListener('click', () => {

            const firstCard = selectedCards[0];

            closeSelectionSheet();

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



                const response = await fetch(url.toString(), {

                    headers: { Accept: 'application/json' },

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



    // Success modal functionality

    const successModal = document.getElementById('reservationSuccessModal');

    const successConfirmBtn = document.getElementById('successConfirmBtn');

    const successCloseButtons = document.querySelectorAll('[data-close-success-modal]');



    const handleSuccessConfirm = () => {
        if (successConfirmBtn) {
            successConfirmBtn.disabled = true;
            successConfirmBtn.innerHTML = '<span class="rp-btn-spinner"></span> Refreshing page…';
        }
        window.location.reload();
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

    