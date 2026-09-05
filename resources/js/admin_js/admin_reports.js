window.AppPage = window.AppPage || {};
window.AppPage['admin_reports'] = function () {
    const data = window.reportData || {};
    const rawRows = data.rawRows || [];
    const allAmenities = data.allAmenities || [];
    const reservationsList = data.reservations || [];

    const printButton = document.getElementById('printReportsButton');
    const exportCsvBtn = document.getElementById('exportCsvBtn');
    const amenityFilter = document.getElementById('amenityFilter');
    const statusFilter = document.getElementById('statusFilter');
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    const reservationsTable = document.getElementById('reservationsTable');
    const activeFilterText = document.getElementById('activeFilterText');
    const resetFiltersBtn = document.getElementById('resetFiltersBtn');
    const presetChips = document.querySelectorAll('.preset-chip');

    const isoDate = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

    // =========================================================
    // DAILY AMENITY & ROOM OCCUPANCY MONITORING MATRIX ENGINE
    // =========================================================
    const matrixTableBody = document.getElementById('matrixTableBody');
    const matrixTableFoot = document.getElementById('matrixTableFoot');
    const matrixAmenitySubHeaderRow = document.getElementById('matrixAmenitySubHeaderRow');
    const matrixSuperHeaderAmenity = document.getElementById('matrixSuperHeaderAmenity');
    const matrixDateFromInput = document.getElementById('matrixDateFrom');
    const matrixDateToInput = document.getElementById('matrixDateTo');
    const matrixApplyDateBtn = document.getElementById('matrixApplyDateBtn');
    const matrixPresetBtns = document.querySelectorAll('.matrix-tab-item[data-matrix-preset]');
    const matrixAmenityCheckboxes = document.querySelectorAll('.matrix-amenity-checkbox');
    const matrixToggleAllCheckbox = document.getElementById('matrixToggleAllCheckbox');
    const matrixCategoryCheckboxes = document.querySelectorAll('.matrix-category-cb');
    const matrixSelectAllBtn = document.getElementById('matrixSelectAllAmenitiesBtn');
    const matrixClearAllBtn = document.getElementById('matrixClearAllAmenitiesBtn');
    const exportMatrixCsvBtn = document.getElementById('exportMatrixCsvBtn');
    const printMatrixPdfBtn = document.getElementById('printMatrixPdfBtn');
    const matrixSheetMonthTabs = document.getElementById('matrixSheetMonthTabs');
    const matrixSubtitlePeriod = document.getElementById('matrixSubtitlePeriod');
    const matrixTotalRoomsLabel = document.getElementById('matrixTotalRoomsLabel');
    const matrixActiveMonthLabel = document.getElementById('matrixActiveMonthLabel');
    const matrixActiveMonthBanner = document.getElementById('matrixActiveMonthBanner');
    const matrixActiveDateRangePill = document.getElementById('matrixActiveDateRangePill');
    const subTabRoomsMatrix = document.getElementById('subTabRoomsMatrix');
    const subTabGuestsMatrix = document.getElementById('subTabGuestsMatrix');
    const matrixRoomsContainer = document.getElementById('matrixRoomsContainer');
    const matrixGuestsContainer = document.getElementById('matrixGuestsContainer');
    const matrixRoomsControlsRow = document.getElementById('matrixRoomsControlsRow');
    const matrixGuestsTableBody = document.getElementById('matrixGuestsTableBody');
    const matrixGuestsTableFoot = document.getElementById('matrixGuestsTableFoot');
    const matrixCurrentViewLabel = document.getElementById('matrixCurrentViewLabel');

    let currentMatrixView = 'rooms'; // 'rooms' or 'guests'

    // Selected amenities state (defaults to all)
    let selectedAmenityIds = new Set(allAmenities.map(a => String(a.id)));

    // Date range helper for Month calculations
    const getMonthRange = (year, monthIndex) => {
        const start = new Date(year, monthIndex, 1);
        const end = new Date(year, monthIndex + 1, 0);
        return { start: isoDate(start), end: isoDate(end) };
    };

    const now = new Date();
    let currentMatrixStartDate = getMonthRange(now.getFullYear(), now.getMonth()).start;
    let currentMatrixEndDate = getMonthRange(now.getFullYear(), now.getMonth()).end;

    if (matrixDateFromInput) matrixDateFromInput.value = currentMatrixStartDate;
    if (matrixDateToInput) matrixDateToInput.value = currentMatrixEndDate;

    const getDatesInRange = (startDateStr, endDateStr) => {
        const dates = [];
        if (!startDateStr || !endDateStr) return dates;
        const curr = new Date(startDateStr + 'T00:00:00');
        const end = new Date(endDateStr + 'T00:00:00');
        
        let count = 0;
        while (curr <= end && count <= 366) {
            dates.push(isoDate(curr));
            curr.setDate(curr.getDate() + 1);
            count++;
        }
        return dates;
    };

    const computeMatrixData = (dateList, activeAmenityIds) => {
        const amenityIdList = Array.from(activeAmenityIds);
        const dayNames = ['SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'];
        
        const rows = [];
        const columnTotals = {};
        amenityIdList.forEach(id => columnTotals[id] = 0);
        let grandTotalCheckIn = 0;
        let grandTotalOvernight = 0;
        let grandTotalRoomsOccupied = 0;

        dateList.forEach(dateStr => {
            const dateObj = new Date(dateStr + 'T00:00:00');
            const dayNum = dateObj.getDate();
            const dayName = dayNames[dateObj.getDay()];

            const amenityGuestsMap = {};
            amenityIdList.forEach(id => amenityGuestsMap[id] = 0);

            let dayGuestsCheckIn = 0;
            let dayGuestsOvernight = 0;

            reservationsList.forEach(r => {
                // Strictly include actual stays that have Checked In, Checked Out, or Completed
                // Do NOT include Pending, Confirmed, Cancelled, or No Show
                const statusNormalized = String(r.status || '').trim().toLowerCase();
                const isExcluded = statusNormalized === 'pending' || 
                                   statusNormalized === 'confirmed' || 
                                   statusNormalized === 'cancelled' || 
                                   statusNormalized === 'cancel' || 
                                   statusNormalized === 'no show' || 
                                   statusNormalized === 'no_show';
                const isEligibleStay = statusNormalized === 'checked in' || 
                                       statusNormalized === 'checked-in' || 
                                       statusNormalized === 'checked out' || 
                                       statusNormalized === 'checked-out' || 
                                       statusNormalized === 'checkedout' || 
                                       statusNormalized === 'completed' || 
                                       (Boolean(r.check_in) && !isExcluded);
                if (isExcluded || !isEligibleStay) return;

                const resCheckIn = r.check_in ? String(r.check_in).slice(0, 10) : null;
                const resEnd = r.end_date ? String(r.end_date).slice(0, 10) : null;
                const totalDays = Number(r.total_days) || 1;
                const guests = Number(r.guests) || 0;

                if (!resCheckIn) return;

                let isOccupiedOnThisDate = false;

                // Multi-day stay rule: covers from resCheckIn to resEnd inclusive
                if (resEnd && resEnd > resCheckIn && totalDays > 1) {
                    if (dateStr >= resCheckIn && dateStr <= resEnd) {
                        isOccupiedOnThisDate = true;
                    }
                } else {
                    // Single-day or overnight checkout next morning rule: only counts on check-in day
                    if (dateStr === resCheckIn) {
                        isOccupiedOnThisDate = true;
                    }
                }

                if (isOccupiedOnThisDate) {
                    let hasMatchingActiveAmenity = false;
                    if (r.amenities && r.amenities.length > 0) {
                        r.amenities.forEach(ra => {
                            const aId = String(ra.amenity_id);
                            if (activeAmenityIds.has(aId)) {
                                amenityGuestsMap[aId] = (amenityGuestsMap[aId] || 0) + guests;
                                hasMatchingActiveAmenity = true;
                            }
                        });
                    }

                    // Strictly only include in Check-In and Overnight counts if at least one booked amenity is currently selected/active
                    if (hasMatchingActiveAmenity) {
                        if (dateStr === resCheckIn) {
                            dayGuestsCheckIn += guests;
                        }
                        if (r.reservation_type === 'overnight' || totalDays >= 1) {
                            dayGuestsOvernight += guests;
                        }
                    }
                }
            });

            // Calculate distinct rooms occupied on this day
            let dayRoomsOccupied = 0;
            amenityIdList.forEach(id => {
                if (amenityGuestsMap[id] > 0) {
                    dayRoomsOccupied += 1;
                }
                columnTotals[id] = (columnTotals[id] || 0) + (amenityGuestsMap[id] || 0);
            });

            grandTotalCheckIn += dayGuestsCheckIn;
            grandTotalOvernight += dayGuestsOvernight;
            grandTotalRoomsOccupied += dayRoomsOccupied;

            rows.push({
                dateStr,
                dayNum,
                dayName,
                amenityGuestsMap,
                dayGuestsCheckIn,
                dayGuestsOvernight,
                dayRoomsOccupied
            });
        });

        return {
            rows,
            columnTotals,
            grandTotalCheckIn,
            grandTotalOvernight,
            grandTotalRoomsOccupied
        };
    };

    const renderAmenityMatrix = () => {
        if (!matrixTableBody) return;

        const visibleAmenities = allAmenities.filter(a => selectedAmenityIds.has(String(a.id)));
        
        if (matrixTotalRoomsLabel) {
            matrixTotalRoomsLabel.textContent = `${visibleAmenities.length} of ${allAmenities.length} Rooms Active`;
        }

        // Update superheader colspan
        if (matrixSuperHeaderAmenity) {
            matrixSuperHeaderAmenity.colSpan = Math.max(visibleAmenities.length, 1);
        }

        // Update subheader columns
        if (matrixAmenitySubHeaderRow) {
            if (visibleAmenities.length === 0) {
                matrixAmenitySubHeaderRow.innerHTML = '<th class="py-2 px-3 border border-white/20 text-center italic text-xs text-white">No amenities selected</th>';
            } else {
                matrixAmenitySubHeaderRow.innerHTML = visibleAmenities.map(a => `
                    <th class="sticky top-8 z-10 py-1.5 px-2.5 border border-white/20 bg-[#246b47] text-white text-[0.68rem] font-bold tracking-wide align-middle min-w-[85px] box-border" data-col-amenity-id="${a.id}">
                        ${a.name.toUpperCase()}
                    </th>
                `).join('');
            }
        }

        const dateList = getDatesInRange(currentMatrixStartDate, currentMatrixEndDate);

        // Format Month and Date Range Labels
        const dStart = new Date(currentMatrixStartDate + 'T00:00:00');
        const dEnd = new Date(currentMatrixEndDate + 'T00:00:00');
        const startMonthName = dStart.toLocaleDateString('en-US', { month: 'long' });
        const startYear = dStart.getFullYear();
        const endMonthName = dEnd.toLocaleDateString('en-US', { month: 'long' });
        const endYear = dEnd.getFullYear();

        let monthLabelText = '';
        let bannerText = '';
        if (startMonthName === endMonthName && startYear === endYear) {
            monthLabelText = `${startMonthName.toUpperCase()} ${startYear}`;
            bannerText = `FOR THE MONTH OF: ${startMonthName.toUpperCase()} ${startYear}`;
        } else {
            monthLabelText = `${dStart.toLocaleDateString('en-US', { month: 'short' }).toUpperCase()} ${startYear} – ${dEnd.toLocaleDateString('en-US', { month: 'short' }).toUpperCase()} ${endYear}`;
            bannerText = `PERIOD: ${monthLabelText}`;
        }

        if (matrixActiveMonthLabel) {
            matrixActiveMonthLabel.textContent = monthLabelText;
        }

        if (matrixSubtitlePeriod) {
            const startFmt = dStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const endFmt = dEnd.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            matrixSubtitlePeriod.textContent = `${startFmt} to ${endFmt} (${dateList.length} days)`;
        }

        if (dateList.length === 0) {
            matrixTableBody.innerHTML = `
                <tr>
                    <td colspan="${visibleAmenities.length + 5}" class="py-10 text-center text-sm text-hp-text-muted">
                        No valid date range selected.
                    </td>
                </tr>
            `;
            if (matrixTableFoot) matrixTableFoot.innerHTML = '';
            return;
        }

        const { rows, columnTotals, grandTotalCheckIn, grandTotalOvernight, grandTotalRoomsOccupied } = computeMatrixData(dateList, selectedAmenityIds);

        // Render Table Body Rows
        matrixTableBody.innerHTML = rows.map(r => {
            const amenityCells = visibleAmenities.map(a => {
                const count = r.amenityGuestsMap[String(a.id)] || 0;
                if (count > 0) {
                    return `<td class="py-2 px-2 text-center border-r border-b border-gray-200/80 dark:border-white/10 font-bold text-hp-text"><span class="inline-flex items-center justify-center min-w-[26px] h-6 px-1.5 rounded-md font-bold bg-[#1c5c3c]/15 text-[#1c5c3c] dark:bg-[#4c9a5f]/25 dark:text-[#6ab88c] text-xs">${count}</span></td>`;
                }
                return `<td class="py-2 px-2 text-center border-r border-b border-gray-200/80 dark:border-white/10 text-hp-text-muted/30 font-normal text-xs">-</td>`;
            }).join('');

            return `
                <tr class="hover:bg-glass-hover/50 transition-colors">
                    <td class="sticky left-0 z-10 w-[65px] min-w-[65px] max-w-[65px] py-2.5 px-3 font-semibold text-hp-text bg-white dark:bg-[#161b18] border-r border-b border-gray-200/80 dark:border-white/10 whitespace-nowrap text-center text-xs box-border">${r.dayNum}</td>
                    <td class="sticky left-[65px] z-10 w-[105px] min-w-[105px] max-w-[105px] py-2.5 px-3 font-medium text-hp-text-muted bg-white dark:bg-[#161b18] border-r border-b border-gray-200/80 dark:border-white/10 whitespace-nowrap text-left text-xs shadow-[3px_0_6px_-2px_rgba(0,0,0,0.12)] dark:shadow-[3px_0_6px_-2px_rgba(0,0,0,0.4)] box-border">${r.dayName}</td>
                    ${amenityCells}
                    <td class="py-2.5 px-3 font-bold text-hp-text text-center border-r border-b border-gray-200/80 dark:border-white/10 bg-[#1c5c3c]/5 dark:bg-[#1c5c3c]/10 text-xs">${r.dayGuestsCheckIn > 0 ? r.dayGuestsCheckIn : '-'}</td>
                    <td class="py-2.5 px-3 font-bold text-hp-text text-center border-r border-b border-gray-200/80 dark:border-white/10 bg-[#1c5c3c]/5 dark:bg-[#1c5c3c]/10 text-xs">${r.dayGuestsOvernight > 0 ? r.dayGuestsOvernight : '-'}</td>
                    <td class="py-2.5 px-3 font-bold text-hp-text text-center border-r border-b border-gray-200/80 dark:border-white/10 bg-[#1c5c3c]/5 dark:bg-[#1c5c3c]/10 text-xs">${r.dayRoomsOccupied > 0 ? r.dayRoomsOccupied : '0'}</td>
                </tr>
            `;
        }).join('');

        // Render Table Footer Summary
        if (matrixTableFoot) {
            const footerAmenityCells = visibleAmenities.map(a => {
                const total = columnTotals[String(a.id)] || 0;
                return `<td class="py-3 px-2 text-center font-bold text-hp-green-mid border-r border-b border-gray-200/80 dark:border-white/10 text-xs">${total}</td>`;
            }).join('');

            matrixTableFoot.innerHTML = `
                <tr class="bg-[#eaf3ed] dark:bg-[#1a231d]">
                    <td class="sticky left-0 z-10 w-[65px] min-w-[65px] py-3 px-3 font-bold uppercase tracking-wider text-hp-text bg-[#e2ede4] dark:bg-[#1a231d] border-r border-b border-gray-200/80 dark:border-white/10 text-center text-xs box-border"></td>
                    <td class="sticky left-[65px] z-10 w-[105px] min-w-[105px] py-3 px-3 text-left font-bold uppercase tracking-wider text-hp-text bg-[#e2ede4] dark:bg-[#1a231d] border-r border-b border-gray-200/80 dark:border-white/10 text-xs shadow-[3px_0_6px_-2px_rgba(0,0,0,0.12)] dark:shadow-[3px_0_6px_-2px_rgba(0,0,0,0.4)] box-border">TOTAL</td>
                    ${footerAmenityCells}
                    <td class="py-3 px-3 text-center font-bold text-hp-green-mid text-xs border-r border-b border-gray-200/80 dark:border-white/10">${grandTotalCheckIn}</td>
                    <td class="py-3 px-3 text-center font-bold text-hp-green-mid text-xs border-r border-b border-gray-200/80 dark:border-white/10">${grandTotalOvernight}</td>
                    <td class="py-3 px-3 text-center font-bold text-hp-green-mid text-xs border-r border-b border-gray-200/80 dark:border-white/10">${grandTotalRoomsOccupied}</td>
                </tr>
            `;
        }

        // Seamlessly sync subheader top offset to match superheader height perfectly
        syncMatrixHeaderTops();
    };

    const syncMatrixHeaderTops = () => {
        if (!matrixSuperHeaderAmenity) return;
        const superHdrHeight = matrixSuperHeaderAmenity.offsetHeight || matrixSuperHeaderAmenity.getBoundingClientRect().height;
        if (superHdrHeight > 0) {
            const subRowThs = document.querySelectorAll('#matrixAmenitySubHeaderRow th');
            subRowThs.forEach(th => {
                th.style.top = `${Math.round(superHdrHeight)}px`;
            });
        }
    };

    window.addEventListener('resize', syncMatrixHeaderTops);
    window.addEventListener('load', syncMatrixHeaderTops);
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(syncMatrixHeaderTops);
    }

    const computeGuestsDemographicsData = (dateList) => {
        const dayNames = ['SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'];
        const rows = [];
        let grandTotalMale = 0;
        let grandTotalFemale = 0;
        let grandTotalForeigner = 0;
        let grandTotalAllGuests = 0;

        dateList.forEach(dateStr => {
            const dateObj = new Date(dateStr + 'T00:00:00');
            const dayNum = dateObj.getDate();
            const dayName = dayNames[dateObj.getDay()];

            let dayMale = 0;
            let dayFemale = 0;
            let dayForeigner = 0;

            reservationsList.forEach(r => {
                const statusNormalized = String(r.status || '').trim().toLowerCase();
                const isExcluded = statusNormalized === 'pending' || 
                                   statusNormalized === 'confirmed' || 
                                   statusNormalized === 'cancelled' || 
                                   statusNormalized === 'cancel' || 
                                   statusNormalized === 'no show' || 
                                   statusNormalized === 'no_show';
                const isEligibleStay = statusNormalized === 'checked in' || 
                                       statusNormalized === 'checked-in' || 
                                       statusNormalized === 'checked out' || 
                                       statusNormalized === 'checked-out' || 
                                       statusNormalized === 'checkedout' || 
                                       statusNormalized === 'completed' || 
                                       (Boolean(r.check_in) && !isExcluded);
                if (isExcluded || !isEligibleStay) return;

                const resCheckIn = r.check_in ? String(r.check_in).slice(0, 10) : null;
                const resEnd = r.end_date ? String(r.end_date).slice(0, 10) : null;
                const totalDays = Number(r.total_days) || 1;

                if (!resCheckIn) return;

                let isOccupiedOnThisDate = false;
                if (resEnd && resEnd > resCheckIn && totalDays > 1) {
                    if (dateStr >= resCheckIn && dateStr <= resEnd) {
                        isOccupiedOnThisDate = true;
                    }
                } else {
                    if (dateStr === resCheckIn) {
                        isOccupiedOnThisDate = true;
                    }
                }

                if (!isOccupiedOnThisDate) return;

                const male = Number(r.male_count) || 0;
                const female = Number(r.female_count) || 0;
                const foreigner = Number(r.foreigner_count) || 0;
                const totalGuests = Number(r.guests) || 0;

                if (male + female + foreigner > 0) {
                    dayMale += male;
                    dayFemale += female;
                    dayForeigner += foreigner;
                } else if (totalGuests > 0) {
                    dayMale += Math.ceil(totalGuests / 2);
                    dayFemale += Math.floor(totalGuests / 2);
                }
            });

            const dayTotal = dayMale + dayFemale + dayForeigner;
            grandTotalMale += dayMale;
            grandTotalFemale += dayFemale;
            grandTotalForeigner += dayForeigner;
            grandTotalAllGuests += dayTotal;

            rows.push({
                dateStr,
                dayNum,
                dayName,
                dayMale,
                dayFemale,
                dayForeigner,
                dayTotal
            });
        });

        return {
            rows,
            grandTotalMale,
            grandTotalFemale,
            grandTotalForeigner,
            grandTotalAllGuests
        };
    };

    const renderGuestsDemographicsMatrix = () => {
        if (!matrixGuestsTableBody) return;

        const dateList = getDatesInRange(currentMatrixStartDate, currentMatrixEndDate);
        const dStart = new Date(currentMatrixStartDate + 'T00:00:00');
        const dEnd = new Date(currentMatrixEndDate + 'T00:00:00');
        const startMonthName = dStart.toLocaleDateString('en-US', { month: 'long' });
        const startYear = dStart.getFullYear();
        const endMonthName = dEnd.toLocaleDateString('en-US', { month: 'long' });
        const endYear = dEnd.getFullYear();

        let monthLabelText = (startMonthName === endMonthName && startYear === endYear)
            ? `${startMonthName.toUpperCase()} ${startYear}`
            : `${dStart.toLocaleDateString('en-US', { month: 'short' }).toUpperCase()} ${startYear} – ${dEnd.toLocaleDateString('en-US', { month: 'short' }).toUpperCase()} ${endYear}`;

        if (matrixActiveMonthLabel) matrixActiveMonthLabel.textContent = `GUESTS - ${monthLabelText}`;
        if (matrixSubtitlePeriod) {
            const startFmt = dStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const endFmt = dEnd.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            matrixSubtitlePeriod.textContent = `${startFmt} to ${endFmt} (${dateList.length} days)`;
        }

        if (dateList.length === 0) {
            matrixGuestsTableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="py-10 text-center text-sm text-hp-text-muted">
                        No valid date range selected.
                    </td>
                </tr>
            `;
            if (matrixGuestsTableFoot) matrixGuestsTableFoot.innerHTML = '';
            return;
        }

        const { rows, grandTotalMale, grandTotalFemale, grandTotalForeigner, grandTotalAllGuests } = computeGuestsDemographicsData(dateList);

        matrixGuestsTableBody.innerHTML = rows.map(r => `
            <tr class="hover:bg-glass-hover/50 transition-colors">
                <td class="sticky left-0 z-10 w-[80px] min-w-[80px] max-w-[80px] py-2.5 px-3 font-semibold text-hp-text bg-white dark:bg-[#161b18] border-r border-b border-gray-200/80 dark:border-white/10 whitespace-nowrap text-center text-xs box-border">${r.dayNum}</td>
                <td class="sticky left-[80px] z-10 w-[110px] min-w-[110px] max-w-[110px] py-2.5 px-3 font-medium text-hp-text-muted bg-white dark:bg-[#161b18] border-r border-b border-gray-200/80 dark:border-white/10 whitespace-nowrap text-left text-xs shadow-[3px_0_6px_-2px_rgba(0,0,0,0.12)] dark:shadow-[3px_0_6px_-2px_rgba(0,0,0,0.4)] box-border">${r.dayName}</td>
                <td class="py-2.5 px-3 font-bold text-hp-text text-center border-r border-b border-gray-200/80 dark:border-white/10 text-xs">${r.dayMale > 0 ? `<span class="text-blue-600 dark:text-blue-400 font-bold">${r.dayMale}</span>` : '-'}</td>
                <td class="py-2.5 px-3 font-bold text-hp-text text-center border-r border-b border-gray-200/80 dark:border-white/10 text-xs">${r.dayFemale > 0 ? `<span class="text-pink-600 dark:text-pink-400 font-bold">${r.dayFemale}</span>` : '-'}</td>
                <td class="py-2.5 px-3 font-bold text-hp-text text-center border-r border-b border-gray-200/80 dark:border-white/10 text-xs">${r.dayForeigner > 0 ? `<span class="text-amber-600 dark:text-amber-400 font-bold">${r.dayForeigner}</span>` : '-'}</td>
                <td class="py-2.5 px-3 font-extrabold text-hp-text text-center border-r border-b border-gray-200/80 dark:border-white/10 bg-[#1c5c3c]/5 dark:bg-[#1c5c3c]/10 text-xs">${r.dayTotal > 0 ? r.dayTotal : '0'}</td>
            </tr>
        `).join('');

        if (matrixGuestsTableFoot) {
            matrixGuestsTableFoot.innerHTML = `
                <tr class="bg-[#eaf3ed] dark:bg-[#1a231d]">
                    <td class="sticky left-0 z-10 w-[80px] min-w-[80px] py-3 px-3 font-bold uppercase tracking-wider text-hp-text bg-[#e2ede4] dark:bg-[#1a231d] border-r border-b border-gray-200/80 dark:border-white/10 text-center text-xs box-border"></td>
                    <td class="sticky left-[80px] z-10 w-[110px] min-w-[110px] py-3 px-3 text-left font-bold uppercase tracking-wider text-hp-text bg-[#e2ede4] dark:bg-[#1a231d] border-r border-b border-gray-200/80 dark:border-white/10 text-xs shadow-[3px_0_6px_-2px_rgba(0,0,0,0.12)] dark:shadow-[3px_0_6px_-2px_rgba(0,0,0,0.4)] box-border">TOTAL</td>
                    <td class="py-3 px-3 text-center font-bold text-blue-700 dark:text-blue-400 text-xs border-r border-b border-gray-200/80 dark:border-white/10">${grandTotalMale}</td>
                    <td class="py-3 px-3 text-center font-bold text-pink-700 dark:text-pink-400 text-xs border-r border-b border-gray-200/80 dark:border-white/10">${grandTotalFemale}</td>
                    <td class="py-3 px-3 text-center font-bold text-amber-700 dark:text-amber-400 text-xs border-r border-b border-gray-200/80 dark:border-white/10">${grandTotalForeigner}</td>
                    <td class="py-3 px-3 text-center font-extrabold text-hp-green-mid text-xs border-r border-b border-gray-200/80 dark:border-white/10">${grandTotalAllGuests}</td>
                </tr>
            `;
        }
    };

    const setSubTabActive = (activeBtn, inactiveBtn) => {
        if (activeBtn) {
            activeBtn.classList.remove('text-hp-text-muted', 'hover:bg-glass-hover');
            activeBtn.classList.add('is-active', 'bg-[#1c5c3c]', 'text-white', 'shadow-xs');
        }
        if (inactiveBtn) {
            inactiveBtn.classList.remove('is-active', 'bg-[#1c5c3c]', 'text-white', 'shadow-xs');
            inactiveBtn.classList.add('text-hp-text-muted', 'hover:bg-glass-hover');
        }
    };

    const renderCurrentMatrixView = () => {
        if (currentMatrixView === 'rooms') {
            setSubTabActive(subTabRoomsMatrix, subTabGuestsMatrix);
            if (matrixRoomsContainer) matrixRoomsContainer.classList.remove('hidden');
            if (matrixGuestsContainer) matrixGuestsContainer.classList.add('hidden');
            if (matrixRoomsControlsRow) matrixRoomsControlsRow.classList.remove('hidden');
            if (matrixCurrentViewLabel) matrixCurrentViewLabel.textContent = 'Monthly Room Occupancy';
            renderAmenityMatrix();
        } else {
            setSubTabActive(subTabGuestsMatrix, subTabRoomsMatrix);
            if (matrixRoomsContainer) matrixRoomsContainer.classList.add('hidden');
            if (matrixGuestsContainer) matrixGuestsContainer.classList.remove('hidden');
            if (matrixRoomsControlsRow) matrixRoomsControlsRow.classList.add('hidden');
            if (matrixCurrentViewLabel) matrixCurrentViewLabel.textContent = 'Number of Guests (Demographics)';
            renderGuestsDemographicsMatrix();
        }
    };

    if (subTabRoomsMatrix) {
        subTabRoomsMatrix.addEventListener('click', () => {
            currentMatrixView = 'rooms';
            renderMonthQuickTabs();
            renderCurrentMatrixView();
        });
    }

    if (subTabGuestsMatrix) {
        subTabGuestsMatrix.addEventListener('click', () => {
            currentMatrixView = 'guests';
            renderMonthQuickTabs();
            renderCurrentMatrixView();
        });
    }

    const renderMonthQuickTabs = () => {
        if (!matrixSheetMonthTabs) return;

        const tabs = [];
        const d = new Date();
        for (let i = 5; i >= 0; i--) {
            const targetDate = new Date(d.getFullYear(), d.getMonth() - i, 1);
            const mName = targetDate.toLocaleDateString('en-US', { month: 'long' });
            const year = targetDate.getFullYear();
            const range = getMonthRange(year, targetDate.getMonth());

            const isCurrentRange = range.start === currentMatrixStartDate && range.end === currentMatrixEndDate;

            tabs.push({
                label: `MONTHLY REPORT - ${mName.toUpperCase()}`,
                view: 'rooms',
                range,
                isActive: isCurrentRange && currentMatrixView === 'rooms'
            });

            tabs.push({
                label: `GUESTS - ${mName.toUpperCase()}`,
                view: 'guests',
                range,
                isActive: isCurrentRange && currentMatrixView === 'guests'
            });
        }

        matrixSheetMonthTabs.innerHTML = tabs.map(t => `
            <button type="button" class="matrix-sheet-tab rounded-lg border border-glass-border px-3 py-1.5 text-[0.72rem] font-bold tracking-wide transition-all hover:bg-glass-hover cursor-pointer ${t.isActive ? 'bg-[#1c5c3c] text-white border-[#1c5c3c] shadow-xs' : 'bg-glass text-hp-text'}" 
                data-start="${t.range.start}" data-end="${t.range.end}" data-view="${t.view}">
                ${t.label}
            </button>
        `).join('');

        matrixSheetMonthTabs.querySelectorAll('.matrix-sheet-tab').forEach(btn => {
            btn.addEventListener('click', () => {
                currentMatrixStartDate = btn.dataset.start;
                currentMatrixEndDate = btn.dataset.end;
                currentMatrixView = btn.dataset.view;

                if (matrixDateFromInput) matrixDateFromInput.value = currentMatrixStartDate;
                if (matrixDateToInput) matrixDateToInput.value = currentMatrixEndDate;
                
                updatePresetButtonStyles(null);
                renderMonthQuickTabs();
                renderCurrentMatrixView();
            });
        });
    };

    // Matrix Quick Preset Buttons
    const updatePresetButtonStyles = (activeBtn) => {
        matrixPresetBtns.forEach(p => {
            p.classList.remove('is-active', 'bg-[#1c5c3c]', 'text-white', 'border-[#1c5c3c]');
            p.classList.add('bg-white', 'text-gray-700', 'border-gray-300');
        });
        if (activeBtn) {
            activeBtn.classList.add('is-active', 'bg-[#1c5c3c]', 'text-white', 'border-[#1c5c3c]');
            activeBtn.classList.remove('bg-white', 'text-gray-700', 'border-gray-300');
        }
    };

    // Set initial active preset style
    const initialActivePreset = document.querySelector('.matrix-tab-item[data-matrix-preset="1m"]');
    if (initialActivePreset) {
        updatePresetButtonStyles(initialActivePreset);
    }

    matrixPresetBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            updatePresetButtonStyles(btn);

            const preset = btn.dataset.matrixPreset;
            const today = new Date();

            if (preset === 'today') {
                currentMatrixStartDate = isoDate(today);
                currentMatrixEndDate = isoDate(today);
            } else if (preset === '7d') {
                const past7 = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 6);
                currentMatrixStartDate = isoDate(past7);
                currentMatrixEndDate = isoDate(today);
            } else if (preset === '1m') {
                const range = getMonthRange(today.getFullYear(), today.getMonth());
                currentMatrixStartDate = range.start;
                currentMatrixEndDate = range.end;
            } else if (preset === 'last_month') {
                const range = getMonthRange(today.getFullYear(), today.getMonth() - 1);
                currentMatrixStartDate = range.start;
                currentMatrixEndDate = range.end;
            } else if (preset === '3m') {
                const past3m = new Date(today.getFullYear(), today.getMonth() - 2, 1);
                const rangeEnd = getMonthRange(today.getFullYear(), today.getMonth()).end;
                currentMatrixStartDate = isoDate(past3m);
                currentMatrixEndDate = rangeEnd;
            } else if (preset === '1y') {
                currentMatrixStartDate = `${today.getFullYear()}-01-01`;
                currentMatrixEndDate = `${today.getFullYear()}-12-31`;
            } else if (preset === 'all') {
                currentMatrixStartDate = '2026-01-01';
                currentMatrixEndDate = `${today.getFullYear()}-12-31`;
            }

            if (matrixDateFromInput) matrixDateFromInput.value = currentMatrixStartDate;
            if (matrixDateToInput) matrixDateToInput.value = currentMatrixEndDate;

            renderMonthQuickTabs();
            renderCurrentMatrixView();
        });
    });

    if (matrixApplyDateBtn) {
        matrixApplyDateBtn.addEventListener('click', () => {
            if (matrixDateFromInput && matrixDateFromInput.value) {
                currentMatrixStartDate = matrixDateFromInput.value;
            }
            if (matrixDateToInput && matrixDateToInput.value) {
                currentMatrixEndDate = matrixDateToInput.value;
            }
            updatePresetButtonStyles(null);
            renderMonthQuickTabs();
            renderCurrentMatrixView();
        });
    }

    // Modal Elements for Columns Customizer
    const matrixColumnsModal = document.getElementById('matrixColumnsModal');
    const openMatrixColumnsModalBtn = document.getElementById('openMatrixColumnsModalBtn');
    const closeMatrixColumnsModalBtn = document.getElementById('closeMatrixColumnsModalBtn');
    const cancelMatrixColumnsModalBtn = document.getElementById('cancelMatrixColumnsModalBtn');
    const applyMatrixColumnsModalBtn = document.getElementById('applyMatrixColumnsModalBtn');
    const matrixAmenitySearchInput = document.getElementById('matrixAmenitySearchInput');
    const matrixActiveColumnsBadge = document.getElementById('matrixActiveColumnsBadge');
    const matrixColumnsSummaryText = document.getElementById('matrixColumnsSummaryText');
    const matrixModalSelectedCount = document.getElementById('matrixModalSelectedCount');
    const matrixQuickResetAllBtn = document.getElementById('matrixQuickResetAllBtn');

    const openColumnsModal = () => {
        if (!matrixColumnsModal) return;
        matrixColumnsModal.classList.remove('opacity-0', 'pointer-events-none');
        matrixColumnsModal.classList.add('opacity-100', 'pointer-events-auto');
        const card = matrixColumnsModal.querySelector('.matrix-modal-card');
        if (card) {
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        }
        if (matrixAmenitySearchInput) {
            matrixAmenitySearchInput.value = '';
            filterAmenityCards('');
            setTimeout(() => matrixAmenitySearchInput.focus(), 50);
        }
        updateMasterAndCategoryCheckboxes();
    };

    const closeColumnsModal = () => {
        if (!matrixColumnsModal) return;
        const card = matrixColumnsModal.querySelector('.matrix-modal-card');
        if (card) {
            card.classList.remove('scale-100');
            card.classList.add('scale-95');
        }
        matrixColumnsModal.classList.remove('opacity-100', 'pointer-events-auto');
        matrixColumnsModal.classList.add('opacity-0', 'pointer-events-none');
    };

    const filterAmenityCards = (query) => {
        const q = (query || '').toLowerCase().trim();
        const cards = document.querySelectorAll('.amenity-item-card');
        const categoryBlocks = document.querySelectorAll('.category-group-block');

        cards.forEach(card => {
            const name = card.dataset.amenityName || '';
            const match = !q || name.includes(q);
            card.style.display = match ? 'flex' : 'none';
        });

        categoryBlocks.forEach(block => {
            const visibleCards = block.querySelectorAll('.amenity-item-card:not([style*="display: none"])');
            block.style.display = visibleCards.length > 0 ? 'block' : 'none';
        });
    };

    openMatrixColumnsModalBtn?.addEventListener('click', openColumnsModal);
    closeMatrixColumnsModalBtn?.addEventListener('click', closeColumnsModal);
    cancelMatrixColumnsModalBtn?.addEventListener('click', closeColumnsModal);
    applyMatrixColumnsModalBtn?.addEventListener('click', () => {
        closeColumnsModal();
        renderAmenityMatrix();
    });

    matrixColumnsModal?.addEventListener('click', (e) => {
        if (e.target === matrixColumnsModal) closeColumnsModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && matrixColumnsModal && !matrixColumnsModal.classList.contains('pointer-events-none')) {
            closeColumnsModal();
        }
    });

    matrixAmenitySearchInput?.addEventListener('input', (e) => {
        filterAmenityCards(e.target.value);
    });

    // Amenity Checkboxes Event Handlers
    const updateMasterAndCategoryCheckboxes = () => {
        const total = matrixAmenityCheckboxes.length;
        const checkedCount = selectedAmenityIds.size;

        if (matrixActiveColumnsBadge) {
            matrixActiveColumnsBadge.textContent = `${checkedCount} of ${total} Active`;
        }
        if (matrixModalSelectedCount) {
            matrixModalSelectedCount.textContent = checkedCount;
        }
        if (matrixColumnsSummaryText) {
            if (checkedCount === total) {
                matrixColumnsSummaryText.textContent = `Showing all ${total} amenities`;
            } else if (checkedCount === 0) {
                matrixColumnsSummaryText.textContent = `No amenities selected (0 visible)`;
            } else {
                matrixColumnsSummaryText.textContent = `Custom view: ${checkedCount} of ${total} amenities active`;
            }
        }

        if (matrixToggleAllCheckbox) {
            matrixToggleAllCheckbox.checked = checkedCount === total;
            matrixToggleAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < total;
        }

        matrixCategoryCheckboxes.forEach(catCb => {
            const cat = catCb.dataset.category;
            const catCbs = Array.from(matrixAmenityCheckboxes).filter(cb => cb.dataset.category === cat);
            const catChecked = catCbs.filter(cb => selectedAmenityIds.has(String(cb.value))).length;
            catCb.checked = catChecked === catCbs.length && catCbs.length > 0;
            catCb.indeterminate = catChecked > 0 && catChecked < catCbs.length;
        });
    };

    if (matrixToggleAllCheckbox) {
        matrixToggleAllCheckbox.addEventListener('change', () => {
            const checked = matrixToggleAllCheckbox.checked;
            matrixAmenityCheckboxes.forEach(cb => {
                cb.checked = checked;
                if (checked) {
                    selectedAmenityIds.add(String(cb.value));
                } else {
                    selectedAmenityIds.delete(String(cb.value));
                }
            });
            matrixCategoryCheckboxes.forEach(catCb => catCb.checked = checked);
            updateMasterAndCategoryCheckboxes();
            renderAmenityMatrix();
        });
    }

    matrixCategoryCheckboxes.forEach(catCb => {
        catCb.addEventListener('change', () => {
            const cat = catCb.dataset.category;
            const checked = catCb.checked;
            matrixAmenityCheckboxes.forEach(cb => {
                if (cb.dataset.category === cat) {
                    cb.checked = checked;
                    if (checked) {
                        selectedAmenityIds.add(String(cb.value));
                    } else {
                        selectedAmenityIds.delete(String(cb.value));
                    }
                }
            });
            updateMasterAndCategoryCheckboxes();
            renderAmenityMatrix();
        });
    });

    matrixAmenityCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            const id = String(cb.value);
            if (cb.checked) {
                selectedAmenityIds.add(id);
            } else {
                selectedAmenityIds.delete(id);
            }
            updateMasterAndCategoryCheckboxes();
            renderAmenityMatrix();
        });
    });

    if (matrixSelectAllBtn) {
        matrixSelectAllBtn.addEventListener('click', () => {
            matrixAmenityCheckboxes.forEach(cb => {
                cb.checked = true;
                selectedAmenityIds.add(String(cb.value));
            });
            updateMasterAndCategoryCheckboxes();
            renderAmenityMatrix();
        });
    }

    if (matrixClearAllBtn) {
        matrixClearAllBtn.addEventListener('click', () => {
            matrixAmenityCheckboxes.forEach(cb => {
                cb.checked = false;
                selectedAmenityIds.delete(String(cb.value));
            });
            updateMasterAndCategoryCheckboxes();
            renderAmenityMatrix();
        });
    }

    if (matrixQuickResetAllBtn) {
        matrixQuickResetAllBtn.addEventListener('click', () => {
            matrixAmenityCheckboxes.forEach(cb => {
                cb.checked = true;
                selectedAmenityIds.add(String(cb.value));
            });
            updateMasterAndCategoryCheckboxes();
            renderAmenityMatrix();
        });
    }

    // Matrix CSV Export
    if (exportMatrixCsvBtn) {
        exportMatrixCsvBtn.addEventListener('click', () => {
            const dateList = getDatesInRange(currentMatrixStartDate, currentMatrixEndDate);
            if (dateList.length === 0) {
                alert('No data to export.');
                return;
            }

            const dStart = new Date(currentMatrixStartDate + 'T00:00:00');
            const dEnd = new Date(currentMatrixEndDate + 'T00:00:00');
            const startMonthName = dStart.toLocaleDateString('en-US', { month: 'long' });
            const startYear = dStart.getFullYear();
            const endMonthName = dEnd.toLocaleDateString('en-US', { month: 'long' });
            const endYear = dEnd.getFullYear();
            const monthLabelText = (startMonthName === endMonthName && startYear === endYear)
                ? `${startMonthName.toUpperCase()} ${startYear}`
                : `${dStart.toLocaleDateString('en-US', { month: 'short' }).toUpperCase()} ${startYear} - ${dEnd.toLocaleDateString('en-US', { month: 'short' }).toUpperCase()} ${endYear}`;

            if (currentMatrixView === 'guests') {
                const { rows, grandTotalMale, grandTotalFemale, grandTotalForeigner, grandTotalAllGuests } = computeGuestsDemographicsData(dateList);
                let csv = '\uFEFF';
                csv += 'ESTABLISHMENT NAME:,HINAGUAN NATURE PARK\n';
                csv += `NUMBER OF GUESTS - ${monthLabelText}\n`;
                csv += `REPORTING PERIOD:,${currentMatrixStartDate} to ${currentMatrixEndDate}\n\n`;

                csv += 'DATE,DAY,MALE,FEMALE,FOREIGNER,TOTAL\n';
                rows.forEach(r => {
                    csv += [r.dayNum, `"${r.dayName}"`, r.dayMale, r.dayFemale, r.dayForeigner, r.dayTotal].join(',') + '\n';
                });
                csv += ['TOTAL', '', grandTotalMale, grandTotalFemale, grandTotalForeigner, grandTotalAllGuests].join(',') + '\n';

                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.setAttribute('href', url);
                link.setAttribute('download', `Hinaguan_Park_Number_of_Guests_${currentMatrixStartDate}_${currentMatrixEndDate}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                return;
            }

            // Room / Amenity Matrix CSV Export
            const visibleAmenities = allAmenities.filter(a => selectedAmenityIds.has(String(a.id)));
            const { rows, columnTotals, grandTotalCheckIn, grandTotalOvernight, grandTotalRoomsOccupied } = computeMatrixData(dateList, selectedAmenityIds);

            let csv = '\uFEFF';
            csv += 'ESTABLISHMENT NAME:,HINAGUAN NATURE PARK\n';
            csv += `TOTAL NUMBER OF ROOMS:,${visibleAmenities.length} ROOMS\n`;
            csv += `FOR THE MONTH OF:,${monthLabelText}\n`;
            csv += `REPORTING PERIOD:,${currentMatrixStartDate} to ${currentMatrixEndDate}\n\n`;

            const headerCols = ['DATE', 'DAY', ...visibleAmenities.map(a => `"${a.name.replace(/"/g, '""')}"`), 'NUMBER OF GUEST CHECK IN', 'NUMBER OF GUESTS STAYED OVERNIGHT', 'NUMBER OF ROOMS OCCUPIED'];
            csv += headerCols.join(',') + '\n';

            rows.forEach(r => {
                const amenityValues = visibleAmenities.map(a => r.amenityGuestsMap[String(a.id)] || 0);
                const line = [r.dayNum, `"${r.dayName}"`, ...amenityValues, r.dayGuestsCheckIn, r.dayGuestsOvernight, r.dayRoomsOccupied];
                csv += line.join(',') + '\n';
            });

            const totalAmenityValues = visibleAmenities.map(a => columnTotals[String(a.id)] || 0);
            const totalRow = ['TOTAL', '', ...totalAmenityValues, grandTotalCheckIn, grandTotalOvernight, grandTotalRoomsOccupied];
            csv += totalRow.join(',') + '\n';

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', `Hinaguan_Park_Amenity_Monitoring_Matrix_${currentMatrixStartDate}_${currentMatrixEndDate}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    }

    // Matrix Print / PDF Export
    if (printMatrixPdfBtn) {
        printMatrixPdfBtn.addEventListener('click', () => {
            window.print();
        });
    }

    // Initial render of Matrix Table and Tabs
    renderMonthQuickTabs();
    renderCurrentMatrixView();

    const matchesFilter = (row) => {
        const amenity = (row.amenities || row.dataset?.amenity || '').toLowerCase();
        const status = (row.status || row.dataset?.status || '').toLowerCase();
        const checkin = row.check_in || row.dataset?.checkin;

        const amenityMatch = !amenityFilter || amenityFilter.value === 'all' || amenity.includes(amenityFilter.value.toLowerCase());
        const statusMatch = !statusFilter || statusFilter.value === 'all' || status === statusFilter.value.toLowerCase();

        let dateMatch = true;
        if (checkin) {
            const checkinDay = String(checkin).slice(0, 10);
            dateMatch = (!dateFrom || !dateFrom.value || checkinDay >= dateFrom.value) && (!dateTo || !dateTo.value || checkinDay <= dateTo.value);
        }
        return amenityMatch && statusMatch && dateMatch;
    };

    const getFilteredRows = () => {
        return rawRows.filter(matchesFilter);
    };

    const formatMoney = (val) => '₱' + Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const renderTopAmenities = (filteredRows) => {
        const container = document.getElementById('topAmenitiesContainer');
        if (!container) return;

        const counts = {};
        filteredRows.forEach(row => {
            if (row.amenities && row.amenities !== 'None') {
                const amenities = row.amenities.split(', ');
                amenities.forEach(a => {
                    counts[a] = (counts[a] || 0) + 1;
                });
            }
        });

        const sorted = Object.entries(counts).sort((a, b) => b[1] - a[1]).slice(0, 5);
        const max = sorted.length > 0 ? sorted[0][1] : 1;

        container.innerHTML = '';
        if (sorted.length === 0) {
            container.innerHTML = '<p class="py-4 text-center text-xs text-hp-text-muted">No amenities reserved in this period.</p>';
            return;
        }

        sorted.forEach(([name, count]) => {
            const pct = Math.round((count / max) * 100);
            container.innerHTML += `
                <div class="flex flex-col gap-1">
                    <div class="flex justify-between text-xs font-semibold">
                        <span class="text-hp-text">${name}</span>
                        <span class="text-hp-green-mid font-bold">${count}</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-glass-hover">
                        <div class="h-full rounded-full bg-[#1c5c3c] transition-all duration-500" style="width: ${pct}%"></div>
                    </div>
                </div>
            `;
        });
    };

    const renderPeakDays = (filteredRows) => {
        const container = document.getElementById('peakDaysContainer');
        if (!container) return;

        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const counts = { 'Sun': 0, 'Mon': 0, 'Tue': 0, 'Wed': 0, 'Thu': 0, 'Fri': 0, 'Sat': 0 };

        filteredRows.forEach(row => {
            if (row.check_in) {
                const d = new Date(row.check_in);
                if (!isNaN(d.getTime())) {
                    counts[days[d.getDay()]] += 1;
                }
            }
        });

        const max = Math.max(...Object.values(counts), 1);
        container.innerHTML = '';

        days.forEach(day => {
            const pct = Math.round((counts[day] / max) * 100);
            container.innerHTML += `
                <div class="flex flex-1 flex-col items-center gap-2">
                    <span class="text-[0.7rem] font-bold text-hp-text">${counts[day]}</span>
                    <div class="h-24 w-full max-w-[28px] overflow-hidden rounded-t-lg bg-glass-hover flex items-end">
                        <div class="w-full rounded-t-lg bg-[#1c5c3c] transition-all duration-500" style="height: ${pct}%"></div>
                    </div>
                    <span class="text-[0.7rem] font-semibold text-hp-text-muted">${day}</span>
                </div>
            `;
        });
    };

    let revenueChart = null;
    let donutChart = null;

    const updateCharts = (filteredRows) => {
        if (typeof Chart === 'undefined') return;

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const textColor = isDark ? '#9baaa1' : '#5c6b62';
        const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.07)';

        Chart.defaults.color = textColor;
        Chart.defaults.font.family = "'Montserrat', sans-serif";

        // 1. Revenue Chart (grouped by check-in date)
        const dateRevenueMap = {};
        filteredRows.forEach(r => {
            if (r.check_in) {
                const dateKey = String(r.check_in).slice(0, 10);
                dateRevenueMap[dateKey] = (dateRevenueMap[dateKey] || 0) + Number(r.amount || 0);
            }
        });

        const sortedDates = Object.keys(dateRevenueMap).sort();
        const labels = sortedDates.length > 0 ? sortedDates : ['No Data'];
        const values = sortedDates.length > 0 ? sortedDates.map(d => dateRevenueMap[d]) : [0];

        const ctxRev = document.getElementById('revenueChart');
        if (ctxRev) {
            if (revenueChart) revenueChart.destroy();
            const gradient = ctxRev.getContext('2d').createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, isDark ? 'rgba(28,92,60,0.5)' : 'rgba(28,92,60,0.35)');
            gradient.addColorStop(1, isDark ? 'rgba(28,92,60,0.05)' : 'rgba(28,92,60,0.02)');

            revenueChart = new Chart(ctxRev, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: values,
                        borderColor: isDark ? '#4c9a5f' : '#1c5c3c',
                        backgroundColor: gradient,
                        borderWidth: 2.5,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: isDark ? '#4c9a5f' : '#1c5c3c',
                        pointBorderColor: '#ffffff',
                        pointRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false } },
                        y: {
                            grid: { color: gridColor },
                            ticks: {
                                callback: (v) => '₱' + (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v)
                            }
                        }
                    }
                }
            });
        }

        // 2. Status Donut Chart
        const statusMap = { 'Pending': 0, 'Confirmed': 0, 'Checked In': 0, 'Checked Out': 0, 'Cancelled': 0 };
        filteredRows.forEach(r => {
            const st = r.status || 'Pending';
            statusMap[st] = (statusMap[st] || 0) + 1;
        });

        const statusColors = {
            'Pending': '#c8a45d',
            'Confirmed': '#4c9a5f',
            'Checked In': '#2f6f45',
            'Checked Out': '#9ca3af',
            'Cancelled': '#d64550',
        };

        const ctxDonut = document.getElementById('statusDonutChart');
        if (ctxDonut) {
            if (donutChart) donutChart.destroy();
            donutChart = new Chart(ctxDonut, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(statusMap),
                    datasets: [{
                        data: Object.values(statusMap),
                        backgroundColor: Object.keys(statusMap).map(k => statusColors[k] || '#c8a45d'),
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: { legend: { display: false } }
                }
            });
        }

        // Legend list
        const legendContainer = document.getElementById('donutLegendContainer');
        if (legendContainer) {
            legendContainer.innerHTML = '';
            Object.entries(statusMap).forEach(([st, cnt]) => {
                const col = statusColors[st] || '#c8a45d';
                legendContainer.innerHTML += `
                    <div class="flex items-center justify-between text-xs">
                        <span class="flex items-center gap-2 text-hp-text">
                            <span class="h-2.5 w-2.5 rounded-full" style="background: ${col}"></span>
                            ${st}
                        </span>
                        <strong class="font-bold text-hp-text">${cnt}</strong>
                    </div>
                `;
            });
        }
    };

    const updatePrintLabels = (filteredRows) => {
        const printAmenityLabel = document.getElementById('printAmenityLabel');
        const printStatusLabel = document.getElementById('printStatusLabel');
        const printDateRangeLabel = document.getElementById('printDateRangeLabel');
        const printKpiRes = document.getElementById('printKpiRes');
        const printKpiRev = document.getElementById('printKpiRev');

        if (printAmenityLabel) {
            printAmenityLabel.textContent = amenityFilter && amenityFilter.value !== 'all' ? amenityFilter.value : 'All Amenities';
        }
        if (printStatusLabel) {
            printStatusLabel.textContent = statusFilter && statusFilter.value !== 'all' ? statusFilter.value : 'All Statuses';
        }
        if (printDateRangeLabel) {
            if (dateFrom && dateFrom.value && dateTo && dateTo.value) {
                printDateRangeLabel.textContent = `${dateFrom.value} to ${dateTo.value}`;
            } else if (dateFrom && dateFrom.value) {
                printDateRangeLabel.textContent = `From ${dateFrom.value}`;
            } else if (dateTo && dateTo.value) {
                printDateRangeLabel.textContent = `Until ${dateTo.value}`;
            } else {
                printDateRangeLabel.textContent = 'All Time';
            }
        }
        if (printKpiRes) printKpiRes.textContent = filteredRows.length;
        if (printKpiRev) {
            const totalRev = filteredRows.reduce((acc, r) => acc + Number(r.amount || 0), 0);
            printKpiRev.textContent = formatMoney(totalRev);
        }
    };

    const applyFilters = () => {
        const filteredRows = getFilteredRows();

        // Update DOM Table Rows
        if (reservationsTable) {
            const rows = reservationsTable.querySelectorAll('tbody tr');
            let visible = 0;
            rows.forEach((row) => {
                const show = matchesFilter({
                    amenities: row.dataset.amenity,
                    status: row.dataset.status,
                    check_in: row.dataset.checkin
                });
                row.style.display = show ? '' : 'none';
                if (show) visible += 1;
            });

            if (activeFilterText) {
                activeFilterText.textContent = visible === rows.length
                    ? 'Showing all reservations'
                    : `Showing ${visible} of ${rows.length} reservations`;
            }
        }

        // Update KPIs
        const kpiRes = document.getElementById('kpiReservations');
        const kpiRev = document.getElementById('kpiRevenue');
        if (kpiRes) kpiRes.textContent = filteredRows.length;
        if (kpiRev) {
            const totalRev = filteredRows.reduce((acc, r) => acc + Number(r.amount || 0), 0);
            kpiRev.textContent = formatMoney(totalRev);
        }

        // Update Print Labels
        updatePrintLabels(filteredRows);

        // Update Charts & Visualizers
        renderTopAmenities(filteredRows);
        renderPeakDays(filteredRows);
        updateCharts(filteredRows);
    };

    // Quick range presets
    presetChips.forEach(chip => {
        chip.addEventListener('click', () => {
            presetChips.forEach(c => c.classList.remove('is-active'));
            chip.classList.add('is-active');
            const preset = chip.dataset.preset;
            const now = new Date();
            let from = null;
            let to = new Date();

            if (preset === 'today') {
                from = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            } else if (preset === '7d') {
                from = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 6);
            } else if (preset === '30d') {
                from = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 29);
            } else if (preset === 'month') {
                from = new Date(now.getFullYear(), now.getMonth(), 1);
            } else if (preset === 'all') {
                from = null;
                to = null;
            }

            if (dateFrom) dateFrom.value = from ? isoDate(from) : '';
            if (dateTo) dateTo.value = to ? isoDate(to) : '';
            applyFilters();
        });
    });

    [amenityFilter, statusFilter, dateFrom, dateTo].forEach((input) => {
        input?.addEventListener('change', () => {
            presetChips.forEach(c => c.classList.remove('is-active'));
            applyFilters();
        });
    });

    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', () => {
            presetChips.forEach(c => c.classList.remove('is-active'));
            const defaultAllChip = document.querySelector('.preset-chip[data-preset="all"]');
            if (defaultAllChip) defaultAllChip.classList.add('is-active');

            if (amenityFilter) amenityFilter.value = 'all';
            if (statusFilter) statusFilter.value = 'all';
            if (dateFrom) dateFrom.value = '';
            if (dateTo) dateTo.value = '';
            applyFilters();
        });
    }

    // CSV Export
    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', () => {
            const filteredRows = getFilteredRows();
            if (filteredRows.length === 0) {
                alert('No data available for CSV export.');
                return;
            }

            let csvContent = 'data:text/csv;charset=utf-8,';
            csvContent += 'ID,Customer,Amenities,Check-In Date,Status,Payment Status,Amount,Guests\n';

            filteredRows.forEach(r => {
                const line = [
                    r.id,
                    `"${(r.customer_name || '').replace(/"/g, '""')}"`,
                    `"${(r.amenities || '').replace(/"/g, '""')}"`,
                    r.check_in || '',
                    r.status || '',
                    r.payment_status || '',
                    r.amount || 0,
                    r.guests || 0
                ].join(',');
                csvContent += line + '\n';
            });

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement('a');
            link.setAttribute('href', encodedUri);
            link.setAttribute('download', `hinaguan_admin_report_${isoDate(new Date())}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    }

    // ==========================================
    // SECTION SWITCHING (Matrix vs Standard vs AI Studio)
    // ==========================================
    const tabMatrix = document.getElementById('tabMatrixReports');
    const tabStandard = document.getElementById('tabStandardReports');
    const tabAi = document.getElementById('tabAiReports');
    const sectionMatrix = document.getElementById('matrixReportsSection');
    const sectionStandard = document.getElementById('standardReportsSection');
    const sectionAi = document.getElementById('aiReportsSection');

    const tabActiveClasses = ['is-active', 'bg-[#1c5c3c]', 'text-white', 'shadow-md', 'shadow-[#1c5c3c]/20'];
    const tabInactiveClasses = ['text-hp-text-muted', 'hover:text-hp-text', 'hover:bg-glass-hover'];

    const setTabButtonActive = (activeBtn, allBtns) => {
        allBtns.forEach(btn => {
            if (!btn) return;
            if (btn === activeBtn) {
                btn.classList.remove(...tabInactiveClasses);
                btn.classList.add(...tabActiveClasses);
            } else {
                btn.classList.remove(...tabActiveClasses);
                btn.classList.add(...tabInactiveClasses);
            }
        });
    };

    const switchSection = (mode) => {
        const tabs = [tabMatrix, tabStandard, tabAi];
        [sectionMatrix, sectionStandard, sectionAi].forEach(s => s?.classList.add('hidden'));

        if (mode === 'ai') {
            setTabButtonActive(tabAi, tabs);
            sectionAi?.classList.remove('hidden');
            localStorage.setItem('admin_reports_active_tab', 'ai');
        } else if (mode === 'standard') {
            setTabButtonActive(tabStandard, tabs);
            sectionStandard?.classList.remove('hidden');
            localStorage.setItem('admin_reports_active_tab', 'standard');
            // Trigger chart resize if needed
            if (revenueChart) revenueChart.resize();
            if (donutChart) donutChart.resize();
        } else {
            setTabButtonActive(tabMatrix, tabs);
            sectionMatrix?.classList.remove('hidden');
            localStorage.setItem('admin_reports_active_tab', 'matrix');
        }
    };

    tabMatrix?.addEventListener('click', () => switchSection('matrix'));
    tabStandard?.addEventListener('click', () => switchSection('standard'));
    tabAi?.addEventListener('click', () => switchSection('ai'));

    // Check saved tab state or hash
    const savedTab = localStorage.getItem('admin_reports_active_tab');
    if (window.location.hash === '#ai' || savedTab === 'ai') {
        switchSection('ai');
    } else if (window.location.hash === '#standard' || window.location.hash === '#ledger' || savedTab === 'standard') {
        switchSection('standard');
    } else {
        switchSection('matrix');
    }

    // ==========================================
    // AI REPORT STUDIO CONTROLLER
    // ==========================================
    const aiForm = document.getElementById('aiReportForm');
    const aiQueryInput = document.getElementById('aiQueryInput');
    const aiSubmitBtn = document.getElementById('aiSubmitBtn');
    const aiSubmitIcon = document.getElementById('aiSubmitIcon');
    const aiSubmitText = document.getElementById('aiSubmitText');
    const aiClearBtn = document.getElementById('aiClearBtn');
    const aiEmptyState = document.getElementById('aiEmptyState');
    const aiLoadingState = document.getElementById('aiLoadingState');
    const aiLoadingText = document.getElementById('aiLoadingText');
    const aiReportResults = document.getElementById('aiReportResults');
    const aiPresetCards = document.querySelectorAll('.ai-preset-card');
    const aiSuggestBtns = document.querySelectorAll('.ai-suggest-btn');

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Suggestion buttons fill
    aiSuggestBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            if (aiQueryInput) {
                aiQueryInput.value = btn.dataset.fill || btn.textContent.trim();
                aiQueryInput.focus();
            }
        });
    });

    // Clear input
    aiClearBtn?.addEventListener('click', () => {
        if (aiQueryInput) {
            aiQueryInput.value = '';
            aiQueryInput.focus();
        }
    });

    // Preset cards trigger immediate generation
    aiPresetCards.forEach(card => {
        card.addEventListener('click', () => {
            const prompt = card.dataset.aiPrompt || '';
            if (aiQueryInput) {
                aiQueryInput.value = prompt;
            }
            generateAiReport(prompt);
        });
    });

    // Form submit
    aiForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const query = aiQueryInput?.value?.trim() || '';
        if (!query) {
            aiQueryInput?.focus();
            return;
        }
        generateAiReport(query);
    });

    const generateAiReport = async (query) => {
        if (!query) return;

        // UI Loading state
        if (aiEmptyState) aiEmptyState.classList.add('hidden');
        if (aiReportResults) aiReportResults.classList.add('hidden');
        if (aiLoadingState) aiLoadingState.classList.remove('hidden');

        if (aiSubmitBtn) {
            aiSubmitBtn.disabled = true;
            if (aiSubmitText) aiSubmitText.textContent = 'Generating Analysis...';
            if (aiSubmitIcon) aiSubmitIcon.classList.add('animate-spin');
        }

        // Animated rotating loading messages
        const loadingMessages = [
            'Querying real-time park database...',
            'Mining reservations, guest demographics, and payments...',
            'Calculating revenue KPIs and financial forecasts...',
            'Formulating strategic executive insights...'
        ];
        let msgIndex = 0;
        const msgInterval = setInterval(() => {
            msgIndex = (msgIndex + 1) % loadingMessages.length;
            if (aiLoadingText) aiLoadingText.textContent = loadingMessages[msgIndex];
        }, 2200);

        try {
            const response = await fetch('/admin/api/reports/ai-analyze', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ query }),
            });

            clearInterval(msgInterval);

            const result = await response.json();

            if (!response.ok || !result.success || !result.data) {
                throw new Error(result.error || result.message || 'Failed to generate AI report');
            }

            renderAiReport(result.data, query);
        } catch (err) {
            clearInterval(msgInterval);
            console.error('AI Report Generation Error:', err);
            if (aiLoadingState) aiLoadingState.classList.add('hidden');
            if (aiReportResults) {
                aiReportResults.classList.remove('hidden');
                aiReportResults.innerHTML = `
                    <div class="rounded-3xl border border-red-500/20 bg-red-500/10 p-6 text-center shadow-glass">
                        <div class="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-red-500/20 text-red-600 dark:text-red-400">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <h4 class="m-0 mb-1 text-base font-bold text-hp-text">Analysis Generation Error</h4>
                        <p class="m-0 mb-4 text-xs text-hp-text-muted max-w-md mx-auto">${err.message || 'Unable to complete AI analysis right now. Please try again.'}</p>
                        <button type="button" class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white transition-all hover:bg-emerald-700" onclick="window.retryAiReport()">
                            Retry Query
                        </button>
                    </div>
                `;
            }
        } finally {
            if (aiLoadingState) aiLoadingState.classList.add('hidden');
            if (aiSubmitBtn) {
                aiSubmitBtn.disabled = false;
                if (aiSubmitText) aiSubmitText.textContent = 'Generate AI Analysis';
                if (aiSubmitIcon) aiSubmitIcon.classList.remove('animate-spin');
            }
        }
    };

    window.retryAiReport = () => {
        const query = aiQueryInput?.value?.trim();
        if (query) generateAiReport(query);
    };

    const renderAiReport = (report, originalQuery) => {
        if (!aiReportResults) return;

        aiReportResults.classList.remove('hidden');

        const dateStr = new Date().toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        // 1. Key Metrics HTML
        let metricsHtml = '';
        if (Array.isArray(report.key_metrics) && report.key_metrics.length > 0) {
            metricsHtml = `
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-${Math.min(report.key_metrics.length, 4)}">
                    ${report.key_metrics.map(m => {
                        let changeClass = 'text-hp-text-muted bg-glass';
                        if (m.change_type === 'positive') changeClass = 'text-emerald-700 bg-emerald-500/10 border-emerald-500/20';
                        else if (m.change_type === 'negative') changeClass = 'text-rose-700 bg-rose-500/10 border-rose-500/20';

                        return `
                            <article class="flex flex-col justify-between rounded-2xl border border-glass-border bg-glass p-5 shadow-glass">
                                <span class="text-xs font-bold uppercase tracking-wider text-hp-text-muted">${m.label || 'Metric'}</span>
                                <div class="my-2 text-2xl md:text-3xl font-display font-bold text-hp-text">${m.value || '0'}</div>
                                ${m.subtext ? `<span class="inline-block self-start rounded-lg border border-glass-border px-2.5 py-1 text-[0.7rem] font-semibold ${changeClass}">${m.subtext}</span>` : ''}
                            </article>
                        `;
                    }).join('')}
                </div>
            `;
        }

        // 2. Analytical Insights HTML
        let insightsHtml = '';
        if (Array.isArray(report.insights) && report.insights.length > 0) {
            insightsHtml = `
                <section class="rounded-3xl border border-glass-border bg-glass p-6 shadow-glass">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </div>
                            <h3 class="m-0 text-base font-bold text-hp-text">Analytical Findings & Key Observations</h3>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        ${report.insights.map(item => {
                            let badgeBg = 'bg-glass text-hp-text-muted';
                            if (item.badge === 'High Impact') badgeBg = 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30';
                            else if (item.badge === 'Trend') badgeBg = 'bg-blue-500/15 text-blue-700 dark:text-blue-300 border border-blue-500/30';
                            else if (item.badge === 'Opportunity') badgeBg = 'bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/30';
                            else if (item.badge === 'Alert') badgeBg = 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30';

                            return `
                                <div class="flex flex-col justify-between rounded-2xl border border-glass-border bg-glass-hover/40 p-4 transition-all hover:bg-glass-hover">
                                    <div>
                                        <div class="mb-2 flex items-center justify-between gap-2">
                                            <h4 class="m-0 text-sm font-bold text-hp-text">${item.headline || 'Key Insight'}</h4>
                                            ${item.badge ? `<span class="rounded-full px-2 py-0.5 text-[0.65rem] font-bold ${badgeBg}">${item.badge}</span>` : ''}
                                        </div>
                                        <p class="m-0 text-xs leading-relaxed text-hp-text-muted">${item.description || ''}</p>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </section>
            `;
        }

        // 3. Breakdown Table HTML
        let tableHtml = '';
        if (report.table_data && Array.isArray(report.table_data.headers) && Array.isArray(report.table_data.rows)) {
            tableHtml = `
                <section class="rounded-3xl border border-glass-border bg-glass p-6 shadow-glass">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </div>
                            <h3 class="m-0 text-base font-bold text-hp-text">${report.table_data.title || 'Structured Breakdown Table'}</h3>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-glass-border text-[0.7rem] uppercase tracking-wider text-hp-text-muted">
                                    ${report.table_data.headers.map(h => `<th class="py-3 px-4 font-bold">${h}</th>`).join('')}
                                </tr>
                            </thead>
                            <tbody>
                                ${report.table_data.rows.map(row => `
                                    <tr class="border-b border-glass-border/40 hover:bg-glass-hover/50">
                                        ${row.map((cell, idx) => `<td class="py-3 px-4 ${idx === 0 ? 'font-semibold text-hp-text' : 'text-hp-text-muted'}">${cell}</td>`).join('')}
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </section>
            `;
        }

        // 4. Strategic Recommendations HTML
        let recsHtml = '';
        if (Array.isArray(report.recommendations) && report.recommendations.length > 0) {
            recsHtml = `
                <section class="rounded-3xl border border-glass-border bg-glass p-6 shadow-glass">
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                            </div>
                            <h3 class="m-0 text-base font-bold text-hp-text">Actionable Executive Recommendations</h3>
                        </div>
                    </div>
                    <div class="flex flex-col gap-3">
                        ${report.recommendations.map((rec, idx) => `
                            <div class="flex items-start gap-4 rounded-2xl border border-glass-border bg-glass-hover/40 p-4 transition-all hover:border-teal-500/30">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-teal-500/20 text-xs font-bold text-teal-700 dark:text-teal-300">
                                    ${idx + 1}
                                </div>
                                <div class="flex flex-col">
                                    <h4 class="m-0 mb-1 text-sm font-bold text-hp-text">${rec.action || 'Strategic Action'}</h4>
                                    <p class="m-0 text-xs leading-relaxed text-hp-text-muted">${rec.detail || ''}</p>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </section>
            `;
        }

        // Assemble Full AI Report dynamically - only include components if present
        let sections = [];

        // Header and Executive Summary
        let summaryInner = report.executive_summary ? `
            <div class="pt-6">
                <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-5">
                    <div class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Executive Summary
                    </div>
                    <p class="m-0 text-sm leading-relaxed text-hp-text font-medium">${report.executive_summary}</p>
                </div>
            </div>
        ` : '';

        sections.push(`
            <section class="rounded-3xl border border-glass-border bg-glass p-6 md:p-8 shadow-glass">
                <div class="flex flex-wrap items-center justify-between gap-4 ${summaryInner ? 'border-b border-glass-border pb-6' : ''}">
                    <div>
                        <div class="mb-2 flex items-center gap-2">
                            <span class="rounded-full bg-emerald-500/15 border border-emerald-500/30 px-3 py-0.5 text-xs font-bold text-emerald-700 dark:text-emerald-400">✨ AI Executive Intelligence</span>
                            <span class="text-xs text-hp-text-muted">• Generated ${dateStr}</span>
                        </div>
                        <h2 class="m-0 text-2xl md:text-3xl font-display font-bold text-hp-text">${report.title || 'Executive Analysis Report'}</h2>
                        <p class="m-0 mt-1 text-xs text-hp-text-muted italic">Query: "${originalQuery}"</p>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <button type="button" id="copyAiReportBtn" class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-glass-border bg-glass px-3.5 py-2 text-xs font-semibold text-hp-text transition-all hover:bg-glass-hover">
                            <svg class="h-4 w-4 text-hp-green-mid" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                            <span id="copyAiReportText">Copy Report</span>
                        </button>
                        <button type="button" id="printAiReportBtn" class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-glass-border bg-glass px-3.5 py-2 text-xs font-semibold text-hp-text transition-all hover:bg-glass-hover">
                            <svg class="h-4 w-4 text-hp-green-mid" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Print Report
                        </button>
                    </div>
                </div>

                ${summaryInner}
            </section>
        `);

        if (metricsHtml) sections.push(metricsHtml);
        if (insightsHtml) sections.push(insightsHtml);
        if (tableHtml) sections.push(tableHtml);
        if (recsHtml) sections.push(recsHtml);

        // Follow-up Prompt Box
        sections.push(`
            <div class="rounded-3xl border border-glass-border bg-glass p-5 shadow-glass">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <div>
                            <h4 class="m-0 text-sm font-bold text-hp-text">Want to dig deeper into this data?</h4>
                            <p class="m-0 text-xs text-hp-text-muted">Ask a follow-up question or explore another scenario</p>
                        </div>
                    </div>
                    <button type="button" class="cursor-pointer shrink-0 rounded-xl border border-glass-border bg-glass px-4 py-2 text-xs font-semibold text-hp-text hover:bg-glass-hover" onclick="window.scrollTo({ top: document.getElementById('aiReportForm').offsetTop - 80, behavior: 'smooth' }); document.getElementById('aiQueryInput').focus();">
                        Ask Follow-up ↑
                    </button>
                </div>
            </div>
        `);

        aiReportResults.innerHTML = sections.join('\n');

        // Copy button handling
        const copyBtn = document.getElementById('copyAiReportBtn');
        const copyText = document.getElementById('copyAiReportText');
        copyBtn?.addEventListener('click', () => {
            let textToCopy = `HINAGUAN NATURE PARK - AI EXECUTIVE REPORT\n`;
            textToCopy += `Title: ${report.title || 'Executive Analysis'}\n`;
            textToCopy += `Date: ${dateStr}\n\n`;
            if (report.executive_summary) {
                textToCopy += `EXECUTIVE SUMMARY:\n${report.executive_summary}\n\n`;
            }

            if (Array.isArray(report.key_metrics) && report.key_metrics.length > 0) {
                textToCopy += `KEY METRICS:\n`;
                report.key_metrics.forEach(m => {
                    textToCopy += `• ${m.label}: ${m.value} (${m.subtext || ''})\n`;
                });
                textToCopy += `\n`;
            }

            if (Array.isArray(report.insights) && report.insights.length > 0) {
                textToCopy += `KEY INSIGHTS:\n`;
                report.insights.forEach(i => {
                    textToCopy += `• ${i.headline}: ${i.description}\n`;
                });
                textToCopy += `\n`;
            }

            if (Array.isArray(report.recommendations) && report.recommendations.length > 0) {
                textToCopy += `STRATEGIC RECOMMENDATIONS:\n`;
                report.recommendations.forEach((r, idx) => {
                    textToCopy += `${idx + 1}. ${r.action}: ${r.detail}\n`;
                });
            }

            navigator.clipboard.writeText(textToCopy).then(() => {
                if (copyText) copyText.textContent = '✓ Copied!';
                setTimeout(() => {
                    if (copyText) copyText.textContent = 'Copy Report';
                }, 2000);
            });
        });

        // Print button handling
        const printBtn = document.getElementById('printAiReportBtn');
        printBtn?.addEventListener('click', () => {
            window.print();
        });
    };

    applyFilters();
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['admin_reports']());