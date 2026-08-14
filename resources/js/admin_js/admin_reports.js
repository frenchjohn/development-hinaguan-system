window.AppPage = window.AppPage || {};
window.AppPage['admin_reports'] = function () {
    const data = window.reportData || {};
    const rawRows = data.rawRows || [];

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
            'Checked Out': '#94a3b8',
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

    // Print PDF
    if (printButton) {
        printButton.addEventListener('click', () => {
            window.print();
        });
    }

    applyFilters();
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['admin_reports']());