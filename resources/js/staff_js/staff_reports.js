window.AppPage = window.AppPage || {};
window.AppPage['staff_reports'] = function () {
    const data = window.reportData || {};
    const rawRows = data.rawRows || [];

    const customerFilter = document.getElementById('customerFilter');
    const amenityFilter = document.getElementById('amenityFilter');
    const statusFilter = document.getElementById('statusFilter');
    const dateFrom = document.getElementById('dateFrom');
    const dateTo = document.getElementById('dateTo');
    
    // Filter Toggle
    const filterToggleBtn = document.getElementById('filterToggleBtn');
    const reportsFilters = document.getElementById('reportsFilters');
    if (filterToggleBtn && reportsFilters) {
        filterToggleBtn.addEventListener('click', () => {
            reportsFilters.classList.toggle('is-open');
        });
    }

    const matchesFilter = (row) => {
        const customer = (row.customer_name || '').toLowerCase();
        const amenity = (row.amenities || '').toLowerCase();
        const status = (row.status || '').toLowerCase();
        const checkin = row.check_in;

        const customerMatch = !customerFilter || customerFilter.value === 'all' || customer.includes(customerFilter.value.toLowerCase());
        const amenityMatch = !amenityFilter || amenityFilter.value === 'all' || amenity.includes(amenityFilter.value.toLowerCase());
        const statusMatch = !statusFilter || statusFilter.value === 'all' || status === statusFilter.value.toLowerCase();

        let dateMatch = true;
        if (checkin) {
            const checkinDay = String(checkin).slice(0, 10);
            dateMatch = (!dateFrom || !dateFrom.value || checkinDay >= dateFrom.value) && (!dateTo || !dateTo.value || checkinDay <= dateTo.value);
        }
        return customerMatch && amenityMatch && statusMatch && dateMatch;
    };

    const formatMoney = (value) => '₱' + Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

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
            container.innerHTML = '<p style="color:var(--hp-text-muted);font-size:0.85rem;text-align:center;padding:1rem;">No amenities reserved in this period.</p>';
            return;
        }

        sorted.forEach(([name, count]) => {
            const pct = Math.round((count / max) * 100);
            container.innerHTML += `
                <div class="reports-bar-item">
                    <div class="reports-bar-item__head">
                        <span class="reports-bar-item__label">${name}</span>
                        <span class="reports-bar-item__value">${count}</span>
                    </div>
                    <div class="reports-bar-item__track">
                        <div class="reports-bar-item__fill" style="width: ${pct}%"></div>
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
                counts[days[d.getDay()]] += 1;
            }
        });

        const max = Math.max(...Object.values(counts), 1);
        container.innerHTML = '';

        days.forEach(day => {
            const pct = Math.round((counts[day] / max) * 100);
            container.innerHTML += `
                <div class="reports-bar-item">
                    <div class="reports-bar-item__head">
                        <span class="reports-bar-item__label">${day}</span>
                        <span class="reports-bar-item__value">${counts[day]}</span>
                    </div>
                    <div class="reports-bar-item__track">
                        <div class="reports-bar-item__fill" style="width: ${pct}%"></div>
                    </div>
                </div>
            `;
        });
    };

    let revenueChart = null;
    let donutChart = null;

    const initCharts = () => {
        if (typeof Chart === 'undefined') return;

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const textColor = isDark ? '#9baaa1' : '#5c6b62';
        const gridColor = isDark ? '#26362d' : '#e4ebe6';
        Chart.defaults.color = textColor;
        Chart.defaults.font.family = "'Montserrat', sans-serif";

        const ctxRev = document.getElementById('revenueChart');
        if (ctxRev && data.monthlyLabels) {
            const gradient = ctxRev.getContext('2d').createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(46, 125, 85, 0.4)');
            gradient.addColorStop(1, 'rgba(46, 125, 85, 0.0)');

            revenueChart = new Chart(ctxRev, {
                type: 'line',
                data: {
                    labels: data.monthlyLabels,
                    datasets: [{
                        label: 'Revenue',
                        data: data.monthlyRevenue,
                        borderColor: '#2e7d55',
                        backgroundColor: gradient,
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#2e7d55',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => formatMoney(context.raw)
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: {
                            grid: { color: gridColor },
                            border: { display: false },
                            ticks: {
                                callback: (value) => value >= 1000 ? '₱' + (value/1000) + 'k' : '₱' + value
                            }
                        }
                    }
                }
            });
        }

        const ctxDonut = document.getElementById('statusDonutChart');
        if (ctxDonut && data.statusCounts) {
            const statusLabels = Object.keys(data.statusCounts);
            const statusValues = Object.values(data.statusCounts);
            
            const colorMap = {
                'Pending': '#d3a94e',
                'Confirmed': '#2e9d68',
                'Checked In': '#178a52',
                'Checked Out': '#93a297',
                'Cancelled': '#cf4b47'
            };
            const bgColors = statusLabels.map(l => colorMap[l] || '#93a297');

            const total = statusValues.reduce((a, b) => a + b, 0);
            const donutTotalCount = document.getElementById('donutTotalCount');
            if (donutTotalCount) donutTotalCount.textContent = total;

            donutChart = new Chart(ctxDonut, {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusValues,
                        backgroundColor: bgColors,
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '75%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => ` ${context.label}: ${context.raw} (${Math.round(context.raw/total*100)}%)`
                            }
                        }
                    }
                }
            });

            const legendContainer = document.getElementById('donutLegendContainer');
            if (legendContainer) {
                legendContainer.innerHTML = '';
                statusLabels.forEach((label, i) => {
                    const val = statusValues[i];
                    const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                    legendContainer.innerHTML += `
                        <div class="reports-donut-legend__item">
                            <span class="reports-donut-legend__label">
                                <i class="reports-donut-legend__dot" style="background:${bgColors[i]}"></i>
                                ${label}
                            </span>
                            <div>
                                <span class="reports-donut-legend__value">${val}</span>
                                <span class="reports-donut-legend__pct">${pct}%</span>
                            </div>
                        </div>
                    `;
                });
            }
        }
    };

    const renderTable = (filteredRows) => {
        const tbody = document.getElementById('reportsReservationTableBody');
        if (!tbody) return;

        tbody.innerHTML = '';
        if (filteredRows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;">No reservations found matching the filters.</td></tr>';
            return;
        }

        filteredRows.forEach(row => {
            const tr = document.createElement('tr');
            tr.className = 'reservation-row';
            tr.dataset.reservationId = row.id;

            const checkInDate = new Date(row.check_in || row.reservation_date);
            const dateStr = checkInDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            
            const guests = row.reservation_guests || [];
            const primaryGuest = guests.find(g => g.is_primary_guest);
            const primaryCustomer = primaryGuest ? primaryGuest.customer : null;
            
            let guestInitials = '?';
            if (primaryCustomer) {
                guestInitials = (primaryCustomer.first_name?.[0] || '') + (primaryCustomer.last_name?.[0] || '');
                if (!guestInitials) guestInitials = '?';
            } else if (row.customer_name) {
                guestInitials = row.customer_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() || '?';
            }

            const formatVal = (v) => v || '-';
            const guestCount = row.number_of_guests || 0;
            const remainingGuests = guests.filter(g => !g.checked_out_at).length;

            tr.innerHTML = `
                <td>
                    <span class="font-medium text-dark">#${row.id}</span>
                </td>
                <td>
                    <div class="guest-info">
                        <div class="guest-avatar">${guestInitials}</div>
                        <div>
                            <div class="guest-name">${row.customer_name || 'Walk-in'}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div style="font-weight: 500;">${dateStr}</div>
                </td>
                <td>
                    <span class="guest-name" style="font-size: 0.85rem;">${row.amenities || 'None'}</span>
                </td>
                <td>
                    <div class="guest-count-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z" />
                        </svg>
                        ${remainingGuests} / ${guestCount}
                    </div>
                </td>
                <td>
                    <span class="status-pill status-${(row.status || '').toLowerCase().replace(' ', '-')}">${row.status || ''}</span>
                    <div style="font-size: 0.75rem; margin-top: 0.25rem; color: var(--hp-text-muted);">
                        ${row.payment_status}
                    </div>
                </td>
                <td style="text-align: right;">
                    <button class="expand-btn" aria-label="Expand details">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </td>
            `;

            let guestsHtml = '';
            const isBulkCompanionName = (name) => {
                const n = (name || '').toLowerCase().trim();
                return n.startsWith('bulk') || n.includes('companion');
            };

            const bulkAgeGroupLabel = (age) => {
                if (age === null || age === undefined || age === '') return 'Unknown';
                const a = parseInt(age, 10);
                if (isNaN(a)) return String(age);
                if (a <= 12) return '0-12';
                if (a <= 17) return '13-17';
                if (a <= 59) return '18-59';
                return '60+';
            };

            const regularGuests = [];
            const bulkGroups = {};

            guests.forEach(guest => {
                const c = guest.customer || {};
                const name = trimString(`${c.first_name || ''} ${c.middle_name || ''} ${c.last_name || ''}`);
                if (!guest.is_primary_guest && isBulkCompanionName(c.first_name || name)) {
                    const ageGrp = bulkAgeGroupLabel(c.age);
                    const gender = c.gender || 'N/A';
                    const nat = c.is_foreigner ? 'Foreigner' : 'Filipino';
                    const key = `${gender}|${ageGrp}|${nat}`;
                    if (!bulkGroups[key]) {
                        bulkGroups[key] = {
                            name: 'Bulk Companions',
                            gender,
                            ageGroup: ageGrp,
                            nationality: nat,
                            isForeigner: !!c.is_foreigner,
                            count: 0,
                            checkedOutCount: 0,
                        };
                    }
                    bulkGroups[key].count++;
                    if (guest.checked_out_at) bulkGroups[key].checkedOutCount++;
                } else {
                    regularGuests.push(guest);
                }
            });

            // Render bulk companion groups
            Object.values(bulkGroups).forEach(group => {
                const demo = [group.gender, group.ageGroup ? group.ageGroup + ' yrs' : null, group.nationality].filter(Boolean).join(' &bull; ');
                const isAllCheckedOut = group.checkedOutCount === group.count;
                const statusText = isAllCheckedOut ? 'Checked Out' : (group.checkedOutCount > 0 ? `${group.checkedOutCount}/${group.count} Checked Out` : 'Active');
                const statusClass = isAllCheckedOut ? 'checked-out' : 'active';
                guestsHtml += `
                    <tr>
                        <td>
                            <div class="guest-info">
                                <div class="guest-avatar" style="width:28px;height:28px;font-size:0.7rem;background:linear-gradient(135deg, #178a52, #0e5c37);color:#fff;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;margin:auto;"><path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" /><path d="M5.082 14.254a8.287 8.287 0 00-1.308 5.135 9.687 9.687 0 01-1.764-.44l-.115-.04a.563.563 0 01-.373-.487l-.01-.121a3.75 3.75 0 016.576-3.036 7.525 7.525 0 00-3.006-1.011zM18.918 14.254a8.287 8.287 0 011.308 5.135 9.687 9.687 0 001.764-.44l.115-.04a.563.563 0 00.373-.487l.01-.121a3.75 3.75 0 00-6.576-3.036 7.525 7.525 0 013.006-1.011z" /></svg>
                                </div>
                                <div>
                                    <div class="guest-name" style="font-size:0.85rem;">
                                        ${group.name}
                                        <span class="guest-companion-count ml-1.5 inline-flex items-center gap-1 rounded-full bg-hp-green/10 px-2 py-0.5 align-middle text-[0.65rem] font-bold text-hp-green dark:bg-hp-green/25 dark:text-[#6ab88c]">${group.count}x</span>
                                    </div>
                                    <div class="guest-meta" style="font-size:0.75rem;color:var(--hp-text-muted);">Bulk companion group</div>
                                </div>
                            </div>
                        </td>
                        <td><span style="font-size:0.8rem;color:var(--hp-text-muted);">${demo || '-'}</span></td>
                        <td></td><td></td><td></td><td></td>
                        <td style="text-align:right;">
                            <span class="status-pill status-${statusClass}" style="transform:scale(0.85);transform-origin:right center;">
                                ${statusText}
                            </span>
                        </td>
                    </tr>
                `;
            });

            // Render regular guests
            regularGuests.forEach(guest => {
                const c = guest.customer || {};
                const name = trimString(`${c.first_name || ''} ${c.middle_name || ''} ${c.last_name || ''}`) || 'Walk-in Guest';
                const demo = [c.gender, c.age ? c.age + ' yrs' : null, c.is_foreigner ? 'Foreigner' : 'Filipino'].filter(Boolean).join(' &bull; ');
                guestsHtml += `
                    <tr>
                        <td>
                            <div class="guest-info">
                                <div class="guest-avatar" style="width:28px;height:28px;font-size:0.7rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;margin:auto;"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd"/></svg>
                                </div>
                                <div>
                                    <div class="guest-name" style="font-size:0.85rem;">${name}</div>
                                </div>
                            </div>
                        </td>
                        <td><span style="font-size:0.8rem;color:var(--hp-text-muted);">${demo || '-'}</span></td>
                        <td></td><td></td><td></td><td></td>
                        <td style="text-align:right;">
                            <span class="status-pill status-${guest.checked_out_at ? 'checked-out' : 'active'}" style="transform:scale(0.85);transform-origin:right center;">
                                ${guest.checked_out_at ? 'Checked Out' : 'Active'}
                            </span>
                        </td>
                    </tr>
                `;
            });

            if (!guestsHtml) {
                guestsHtml = `<tr><td colspan="7" style="text-align:center;padding:1rem;color:var(--hp-text-muted);">No guests found.</td></tr>`;
            }

            const expandTr = document.createElement('tr');
            expandTr.className = 'reservation-details-row';
            expandTr.innerHTML = `
                <td colspan="7">
                    <div class="reservation-details-content">
                        <table class="guest-table" style="background:transparent;box-shadow:none;border:none;">
                            <tbody style="background:transparent;">
                                ${guestsHtml}
                            </tbody>
                        </table>
                    </div>
                </td>
            `;

            tbody.appendChild(tr);
            tbody.appendChild(expandTr);

            // Toggle Expand
            tr.addEventListener('click', (e) => {
                if(e.target.closest('button') && !e.target.closest('.expand-btn')) return;
                const isExpanded = tr.classList.contains('is-expanded');
                if (isExpanded) {
                    tr.classList.remove('is-expanded');
                    expandTr.classList.remove('is-visible');
                } else {
                    tr.classList.add('is-expanded');
                    expandTr.classList.add('is-visible');
                }
            });
        });
    };

    const trimString = (str) => {
        return String(str || '').replace(/\s+/g, ' ').trim();
    };

    const applyFilters = () => {
        let visibleCount = 0;
        let visibleRevenue = 0;
        let visibleGuests = 0;
        
        const filteredRows = rawRows.filter(row => {
            if (matchesFilter(row)) {
                visibleCount++;
                visibleRevenue += Number(row.total_amount || 0);
                visibleGuests += Number(row.number_of_guests || 0);
                return true;
            }
            return false;
        });

        const kpiReservations = document.getElementById('kpiReservations');
        const kpiRevenue = document.getElementById('kpiRevenue');
        if (kpiReservations) kpiReservations.textContent = visibleCount;
        if (kpiRevenue) kpiRevenue.textContent = formatMoney(visibleRevenue);

        renderTopAmenities(filteredRows);
        renderPeakDays(filteredRows);
        renderTable(filteredRows);
    };

    [customerFilter, amenityFilter, statusFilter, dateFrom, dateTo].forEach((input) => {
        if (!input) return;
        input.addEventListener('change', () => {
            document.querySelectorAll('.preset-chip').forEach(c => c.classList.remove('is-active'));
            applyFilters();
        });
    });

    const resetFiltersBtn = document.getElementById('resetFiltersBtn');
    if (resetFiltersBtn) {
        const defaultFrom = dateFrom?.value || '';
        const defaultTo = dateTo?.value || '';
        resetFiltersBtn.addEventListener('click', () => {
            document.querySelectorAll('.preset-chip').forEach(c => c.classList.remove('is-active'));
            if (customerFilter) customerFilter.value = 'all';
            if (amenityFilter) amenityFilter.value = 'all';
            if (statusFilter) statusFilter.value = 'all';
            if (dateFrom) dateFrom.value = defaultFrom;
            if (dateTo) dateTo.value = defaultTo;
            applyFilters();
        });
    }

    const presetChips = document.querySelectorAll('.preset-chip');
    const isoDate = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

    presetChips.forEach(chip => {
        chip.addEventListener('click', () => {
            presetChips.forEach(c => c.classList.remove('is-active'));
            chip.classList.add('is-active');
            const preset = chip.dataset.preset;
            const now = new Date();
            let from = null, to = new Date();
            if (preset === 'today') {
                from = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            } else if (preset === '7d') {
                from = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 6);
            } else if (preset === '30d') {
                from = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 29);
            } else if (preset === 'month') {
                from = new Date(now.getFullYear(), now.getMonth(), 1);
            } else {
                from = null;
                to = null;
            }
            if (dateFrom) dateFrom.value = from ? isoDate(from) : '';
            if (dateTo) dateTo.value = to ? isoDate(to) : '';
            applyFilters();
        });
    });

    // ==========================================
    // SECTION SWITCHING (Standard vs AI Studio)
    // ==========================================
    const tabStandard = document.getElementById('tabStandardReports');
    const tabAi = document.getElementById('tabAiReports');
    const sectionStandard = document.getElementById('standardReportsSection');
    const sectionAi = document.getElementById('aiReportsSection');

    const switchSection = (mode) => {
        if (mode === 'ai') {
            tabStandard?.classList.remove('is-active');
            tabAi?.classList.add('is-active');
            sectionStandard?.classList.add('hidden');
            sectionAi?.classList.remove('hidden');
            localStorage.setItem('staff_reports_active_tab', 'ai');
        } else {
            tabAi?.classList.remove('is-active');
            tabStandard?.classList.add('is-active');
            sectionAi?.classList.add('hidden');
            sectionStandard?.classList.remove('hidden');
            localStorage.setItem('staff_reports_active_tab', 'standard');
            if (revenueChart) revenueChart.resize();
            if (statusDonutChart) statusDonutChart.resize();
        }
    };

    tabStandard?.addEventListener('click', () => switchSection('standard'));
    tabAi?.addEventListener('click', () => switchSection('ai'));

    const savedTab = localStorage.getItem('staff_reports_active_tab');
    if (savedTab === 'ai' || window.location.hash === '#ai') {
        switchSection('ai');
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
            'Calculating KPIs and operational breakdowns...',
            'Formulating strategic staff intelligence...'
        ];
        let msgIndex = 0;
        const msgInterval = setInterval(() => {
            msgIndex = (msgIndex + 1) % loadingMessages.length;
            if (aiLoadingText) aiLoadingText.textContent = loadingMessages[msgIndex];
        }, 2200);

        try {
            const response = await fetch('/staff/api/reports/ai-analyze', {
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
            console.error('Staff AI Report Error:', err);
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
                        <button type="button" class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white transition-all hover:bg-emerald-700" onclick="window.retryStaffAiReport()">
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

    window.retryStaffAiReport = () => {
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
                            <h3 class="m-0 text-base font-bold text-hp-text">Operational Findings & Observations</h3>
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
                                            <h4 class="m-0 text-sm font-bold text-hp-text">${item.headline || 'Key Observation'}</h4>
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
        if (report.table_data && Array.isArray(report.table_data.headers) && Array.isArray(report.table_data.rows) && report.table_data.rows.length > 0) {
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
                            <h3 class="m-0 text-base font-bold text-hp-text">Operational Action Recommendations</h3>
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

        // Assemble Full AI Report dynamically
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
                            <span class="rounded-full bg-emerald-500/15 border border-emerald-500/30 px-3 py-0.5 text-xs font-bold text-emerald-700 dark:text-emerald-400">✨ AI Staff Intelligence</span>
                            <span class="text-xs text-hp-text-muted">• Generated ${dateStr}</span>
                        </div>
                        <h2 class="m-0 text-2xl md:text-3xl font-display font-bold text-hp-text">${report.title || 'Staff Analysis Report'}</h2>
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
                            <h4 class="m-0 text-sm font-bold text-hp-text">Want to explore more operational details?</h4>
                            <p class="m-0 text-xs text-hp-text-muted">Ask a follow-up question or analyze another scenario</p>
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
            let textToCopy = `HINAGUAN NATURE PARK - STAFF AI REPORT\n`;
            textToCopy += `Title: ${report.title || 'Staff Analysis'}\n`;
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
                textToCopy += `OPERATIONAL RECOMMENDATIONS:\n`;
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

    initCharts();
    applyFilters();
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['staff_reports']());