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
            localStorage.setItem('admin_reports_active_tab', 'ai');
        } else {
            tabAi?.classList.remove('is-active');
            tabStandard?.classList.add('is-active');
            sectionAi?.classList.add('hidden');
            sectionStandard?.classList.remove('hidden');
            localStorage.setItem('admin_reports_active_tab', 'standard');
            // Trigger chart resize if needed
            if (revenueChart) revenueChart.resize();
            if (statusDonutChart) statusDonutChart.resize();
        }
    };

    tabStandard?.addEventListener('click', () => switchSection('standard'));
    tabAi?.addEventListener('click', () => switchSection('ai'));

    // Check saved tab state or hash
    const savedTab = localStorage.getItem('admin_reports_active_tab');
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