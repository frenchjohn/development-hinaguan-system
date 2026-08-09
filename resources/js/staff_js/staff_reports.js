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

    initCharts();
    applyFilters();
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['staff_reports']());