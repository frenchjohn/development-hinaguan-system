window.AppPage = window.AppPage || {};
window.AppPage['staff_dashboard'] = function () {
    // Animate horizontal bar fills with stagger.
    const animated = Array.from(document.querySelectorAll('.dash-hbar__fill'));
    requestAnimationFrame(() => {
        animated.forEach((el, i) => {
            el.style.transitionDelay = `${(i % 14) * 55}ms`;
            el.classList.add('is-animated');
        });
    });

    // Live clock update
    const clockEl = document.getElementById('sdLiveClock');
    if (clockEl) {
        setInterval(() => {
            const now = new Date();
            let h = now.getHours();
            const m = String(now.getMinutes()).padStart(2, '0');
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            clockEl.textContent = `${h}:${m} ${ampm}`;
        }, 10000);
    }

    // Active Overview Graph (Donut Gauge & Grouped Bar Chart)
    const aoControl = initActiveOverviewGraph();

    // Staff Operations Chart (Guest Traffic & Check-Ins / Desk Collections)
    const opsChartControl = initStaffOperationsChart();

    // Redraw on resize
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            opsChartControl?.redraw();
            aoControl?.redraw();
        }, 150);
    });

    // Redraw on theme change
    const observer = new MutationObserver(() => {
        setTimeout(() => {
            opsChartControl?.redraw();
            aoControl?.redraw();
        }, 100);
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
};

function initStaffOperationsChart() {
    const rawData = window.__sdOpsChartData || {};
    const canvas = document.getElementById('sdAreaChartCanvas');
    const container = document.getElementById('sdAreaChart');
    const tooltip = document.getElementById('sdChartTooltip');
    const tabTraffic = document.getElementById('sdTabTraffic');
    const tabCollections = document.getElementById('sdTabCollections');
    const titleEl = document.getElementById('sdChartTitle');
    const subtitleEl = document.getElementById('sdChartSubtitle');
    const legendEl = document.getElementById('sdChartLegend');

    const stat1Label = document.getElementById('sdStat1Label');
    const stat1Val = document.getElementById('sdStat1Val');
    const stat2Label = document.getElementById('sdStat2Label');
    const stat2Val = document.getElementById('sdStat2Val');
    const stat3Label = document.getElementById('sdStat3Label');
    const stat3Val = document.getElementById('sdStat3Val');

    if (!canvas || !container) return null;
    const ctx = canvas.getContext('2d');

    const labels = rawData.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const fullDates = rawData.fullDates || labels;
    const guests = rawData.guests || [0, 0, 0, 0, 0, 0, 0];
    const checkIns = rawData.checkIns || [0, 0, 0, 0, 0, 0, 0];
    const walkIns = rawData.walkIns || [0, 0, 0, 0, 0, 0, 0];
    const online = rawData.online || [0, 0, 0, 0, 0, 0, 0];
    const collections = rawData.deskCollections || [0, 0, 0, 0, 0, 0, 0];
    const totalCollections = rawData.totalCollections || collections;

    const n = labels.length;
    if (n === 0) return null;

    let currentMode = 'traffic';
    try {
        currentMode = localStorage.getItem('sd_ops_chart_mode') || 'traffic';
    } catch (e) {
        currentMode = 'traffic';
    }

    let hoveredIndex = null;

    function formatPeso(num) {
        return '₱' + Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function setMode(mode) {
        currentMode = mode;
        try {
            localStorage.setItem('sd_ops_chart_mode', mode);
        } catch (e) { }

        const isTraffic = mode === 'traffic';

        if (tabTraffic && tabCollections) {
            tabTraffic.className = isTraffic
                ? 'sd-mode-tab-btn inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-[0.68rem] font-bold transition-all bg-white text-hp-text shadow-sm dark:bg-[#222723] dark:text-[#f3f4f6]'
                : 'sd-mode-tab-btn inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-[0.68rem] font-semibold text-hp-text-muted transition-all hover:text-hp-text dark:hover:text-[#f3f4f6]';
            tabCollections.className = !isTraffic
                ? 'sd-mode-tab-btn inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-[0.68rem] font-bold transition-all bg-white text-hp-text shadow-sm dark:bg-[#222723] dark:text-[#f3f4f6]'
                : 'sd-mode-tab-btn inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-[0.68rem] font-semibold text-hp-text-muted transition-all hover:text-hp-text dark:hover:text-[#f3f4f6]';
        }

        if (titleEl) {
            titleEl.textContent = isTraffic ? 'Daily Guest Traffic & Check-Ins' : 'Front-Desk Collections & Settlements';
        }
        if (subtitleEl) {
            subtitleEl.textContent = isTraffic
                ? '7-day operational volume of admitted guests and front-desk check-ins'
                : '7-day staff cash and electronic payment collections (PHP)';
        }

        if (legendEl) {
            if (isTraffic) {
                legendEl.innerHTML = `
                    <span class="sd-legend-item inline-flex items-center gap-1.5 text-[0.7rem] font-semibold text-hp-text-muted">
                        <span class="h-2.5 w-2.5 rounded-full bg-[#16a34a] inline-block"></span> Guests Admitted
                    </span>
                    <span class="sd-legend-item inline-flex items-center gap-1.5 text-[0.7rem] font-semibold text-hp-text-muted">
                        <span class="h-2.5 w-2.5 rounded-sm bg-[#0d9488] inline-block"></span> Check-Ins
                    </span>
                `;
            } else {
                legendEl.innerHTML = `
                    <span class="sd-legend-item inline-flex items-center gap-1.5 text-[0.7rem] font-semibold text-hp-text-muted">
                        <span class="h-2.5 w-2.5 rounded-full bg-[#d97706] inline-block"></span> Desk Collections
                    </span>
                    <span class="sd-legend-item inline-flex items-center gap-1.5 text-[0.7rem] font-semibold text-hp-text-muted">
                        <span class="h-2.5 w-2.5 rounded-sm border border-[#d97706] inline-block"></span> Total Payments
                    </span>
                `;
            }
        }

        // Update summary strip
        if (stat1Label && stat1Val) {
            if (isTraffic) {
                stat1Label.textContent = '7-Day Guests';
                stat1Val.innerHTML = `${rawData.totalGuests ?? 0} <span class="text-[0.65rem] font-normal text-hp-text-muted">guests</span>`;
            } else {
                stat1Label.textContent = '7-Day Collections';
                stat1Val.textContent = formatPeso(rawData.totalCollectionsSum ?? 0);
            }
        }

        if (stat2Label && stat2Val) {
            if (isTraffic) {
                stat2Label.textContent = '7-Day Check-Ins';
                stat2Val.innerHTML = `${rawData.totalCheckIns ?? 0} <span class="text-[0.65rem] font-normal text-hp-text-muted">check-ins</span>`;
            } else {
                stat2Label.textContent = "Today's Desk Payments";
                const todayCol = collections[n - 1] || 0;
                stat2Val.textContent = formatPeso(todayCol);
            }
        }

        if (stat3Label && stat3Val) {
            if (isTraffic) {
                stat3Label.textContent = 'Peak Activity';
                stat3Val.textContent = rawData.peakDay || 'None';
            } else {
                stat3Label.textContent = 'Daily Average';
                const avgCol = (rawData.totalCollectionsSum || 0) / Math.max(1, n);
                stat3Val.textContent = formatPeso(avgCol);
            }
        }

        hideTooltip();
        render();
    }

    tabTraffic?.addEventListener('click', () => setMode('traffic'));
    tabCollections?.addEventListener('click', () => setMode('collections'));

    function render() {
        const dpr = window.devicePixelRatio || 1;
        const rect = container.getBoundingClientRect();
        const W = rect.width;
        const H = rect.height;

        canvas.width = W * dpr;
        canvas.height = H * dpr;
        canvas.style.width = W + 'px';
        canvas.style.height = H + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        const padL = currentMode === 'traffic' ? 36 : 60;
        const padR = 20;
        const padT = 20;
        const padB = 30;
        const chartW = Math.max(10, W - padL - padR);
        const chartH = Math.max(10, H - padT - padB);

        ctx.clearRect(0, 0, W, H);

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const isTraffic = currentMode === 'traffic';

        let maxVal = 1;
        if (isTraffic) {
            maxVal = Math.max(4, ...guests, ...checkIns);
        } else {
            maxVal = Math.max(500, ...collections, ...totalCollections);
        }

        // Ticks
        const tickCount = 4;
        let step = Math.ceil(maxVal / tickCount);
        if (!isTraffic) {
            if (step > 1000) step = Math.ceil(step / 500) * 500;
            else if (step > 100) step = Math.ceil(step / 100) * 100;
            else step = Math.max(50, Math.ceil(step / 50) * 50);
        }
        const yMax = Math.max(step * tickCount, maxVal);

        // Grid lines & Y labels
        ctx.strokeStyle = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
        ctx.lineWidth = 1;
        ctx.font = '500 10.5px Montserrat, sans-serif';
        ctx.fillStyle = isDark ? '#9baaa1' : '#6b7280';
        ctx.textAlign = 'right';

        for (let i = 0; i <= tickCount; i++) {
            const val = step * i;
            if (val > yMax) continue;
            const y = padT + chartH - (val / yMax) * chartH;
            ctx.beginPath();
            ctx.moveTo(padL, y);
            ctx.lineTo(W - padR, y);
            ctx.stroke();

            const lbl = isTraffic ? String(val) : '₱' + (val >= 1000 ? (val / 1000).toFixed(val % 1000 === 0 ? 0 : 1) + 'k' : val);
            ctx.fillText(lbl, padL - 8, y + 3.5);
        }

        // X positions
        const xStep = chartW / (n - 1 || 1);
        const xPositions = labels.map((_, i) => padL + i * xStep);

        // Draw x labels
        ctx.textAlign = 'center';
        ctx.font = '600 11px Montserrat, sans-serif';
        labels.forEach((label, i) => {
            ctx.fillStyle = (i === hoveredIndex)
                ? (isDark ? '#f3f4f6' : '#111827')
                : (isDark ? '#9baaa1' : '#6b7280');
            ctx.fillText(label, xPositions[i], H - 8);
        });

        // Helper: smooth bezier curve through points
        function drawSmoothCurve(pts) {
            if (pts.length < 2) return;
            ctx.moveTo(pts[0].x, pts[0].y);
            for (let i = 0; i < pts.length - 1; i++) {
                const cp = (pts[i + 1].x - pts[i].x) / 2.6;
                ctx.bezierCurveTo(
                    pts[i].x + cp, pts[i].y,
                    pts[i + 1].x - cp, pts[i + 1].y,
                    pts[i + 1].x, pts[i + 1].y
                );
            }
        }

        if (isTraffic) {
            // Data series 1: Guests Admitted (Area + Line)
            const guestPts = guests.map((val, i) => ({
                x: xPositions[i],
                y: padT + chartH - (val / yMax) * chartH
            }));

            // Area
            ctx.save();
            ctx.beginPath();
            drawSmoothCurve(guestPts);
            ctx.lineTo(xPositions[n - 1], padT + chartH);
            ctx.lineTo(xPositions[0], padT + chartH);
            ctx.closePath();
            const guestGrad = ctx.createLinearGradient(0, padT, 0, padT + chartH);
            guestGrad.addColorStop(0, isDark ? 'rgba(22,163,74,0.38)' : 'rgba(22,163,74,0.30)');
            guestGrad.addColorStop(1, isDark ? 'rgba(22,163,74,0.02)' : 'rgba(22,163,74,0.01)');
            ctx.fillStyle = guestGrad;
            ctx.fill();
            ctx.restore();

            // Line
            ctx.save();
            ctx.beginPath();
            drawSmoothCurve(guestPts);
            ctx.strokeStyle = isDark ? '#22c55e' : '#16a34a';
            ctx.lineWidth = 2.5;
            ctx.stroke();
            ctx.restore();

            // Data series 2: Check-Ins (Teal dotted line)
            const checkInPts = checkIns.map((val, i) => ({
                x: xPositions[i],
                y: padT + chartH - (val / yMax) * chartH
            }));

            ctx.save();
            ctx.beginPath();
            drawSmoothCurve(checkInPts);
            ctx.strokeStyle = isDark ? '#2dd4bf' : '#0d9488';
            ctx.lineWidth = 2;
            ctx.setLineDash([4, 3]);
            ctx.stroke();
            ctx.restore();

            // Draw Check-In Diamond / Square Nodes
            checkInPts.forEach((pt) => {
                ctx.fillStyle = isDark ? '#2dd4bf' : '#0d9488';
                ctx.fillRect(pt.x - 3, pt.y - 3, 6, 6);
            });

            // Draw Guest Circles
            guestPts.forEach((pt, i) => {
                ctx.beginPath();
                ctx.arc(pt.x, pt.y, (i === hoveredIndex ? 5.5 : 3.5), 0, Math.PI * 2);
                ctx.fillStyle = isDark ? '#22c55e' : '#16a34a';
                ctx.fill();
                ctx.strokeStyle = isDark ? '#181b19' : '#ffffff';
                ctx.lineWidth = 2;
                ctx.stroke();
            });

        } else {
            // Mode: Collections
            const colPts = collections.map((val, i) => ({
                x: xPositions[i],
                y: padT + chartH - (val / yMax) * chartH
            }));

            // Area
            ctx.save();
            ctx.beginPath();
            drawSmoothCurve(colPts);
            ctx.lineTo(xPositions[n - 1], padT + chartH);
            ctx.lineTo(xPositions[0], padT + chartH);
            ctx.closePath();
            const colGrad = ctx.createLinearGradient(0, padT, 0, padT + chartH);
            colGrad.addColorStop(0, isDark ? 'rgba(217,119,6,0.38)' : 'rgba(217,119,6,0.28)');
            colGrad.addColorStop(1, isDark ? 'rgba(217,119,6,0.02)' : 'rgba(217,119,6,0.01)');
            ctx.fillStyle = colGrad;
            ctx.fill();
            ctx.restore();

            // Line
            ctx.save();
            ctx.beginPath();
            drawSmoothCurve(colPts);
            ctx.strokeStyle = isDark ? '#f59e0b' : '#d97706';
            ctx.lineWidth = 2.5;
            ctx.stroke();
            ctx.restore();

            // Dots
            colPts.forEach((pt, i) => {
                ctx.beginPath();
                ctx.arc(pt.x, pt.y, (i === hoveredIndex ? 5.5 : 3.5), 0, Math.PI * 2);
                ctx.fillStyle = isDark ? '#f59e0b' : '#d97706';
                ctx.fill();
                ctx.strokeStyle = isDark ? '#181b19' : '#ffffff';
                ctx.lineWidth = 2;
                ctx.stroke();
            });
        }

        // Draw hover vertical crosshair if active
        if (hoveredIndex !== null && xPositions[hoveredIndex] !== undefined) {
            const hx = xPositions[hoveredIndex];
            ctx.save();
            ctx.beginPath();
            ctx.moveTo(hx, padT);
            ctx.lineTo(hx, padT + chartH);
            ctx.strokeStyle = isDark ? 'rgba(255,255,255,0.25)' : 'rgba(0,0,0,0.2)';
            ctx.lineWidth = 1;
            ctx.setLineDash([3, 3]);
            ctx.stroke();
            ctx.restore();
        }
    }

    function showTooltip(index, clientX, clientY) {
        if (!tooltip || index < 0 || index >= n) return;
        const isTraffic = currentMode === 'traffic';
        const dateText = fullDates[index] || labels[index];

        let html = `<div class="mb-1 text-[0.68rem] font-bold text-emerald-300 border-b border-white/10 pb-1">${dateText}</div>`;

        if (isTraffic) {
            const gCount = guests[index] || 0;
            const cCount = checkIns[index] || 0;
            const wCount = walkIns[index] || 0;
            const oCount = online[index] || 0;

            html += `
                <div class="flex items-center justify-between gap-4 text-xs">
                    <span class="inline-flex items-center gap-1.5 font-medium text-emerald-100">
                        <span class="h-2 w-2 rounded-full bg-[#16a34a]"></span> Guests Admitted:
                    </span>
                    <span class="font-extrabold text-white">${gCount}</span>
                </div>
                <div class="flex items-center justify-between gap-4 text-xs mt-0.5">
                    <span class="inline-flex items-center gap-1.5 font-medium text-emerald-100">
                        <span class="h-2 w-2 rounded-sm bg-[#0d9488]"></span> Check-Ins:
                    </span>
                    <span class="font-extrabold text-white">${cCount}</span>
                </div>
                <div class="mt-1 text-[0.62rem] text-emerald-200/70 border-t border-white/5 pt-0.5 flex justify-between gap-2">
                    <span>Walk-In: <strong>${wCount}</strong></span>
                    <span>Online: <strong>${oCount}</strong></span>
                </div>
            `;
        } else {
            const dCol = collections[index] || 0;
            const tCol = totalCollections[index] || dCol;

            html += `
                <div class="flex items-center justify-between gap-4 text-xs">
                    <span class="inline-flex items-center gap-1.5 font-medium text-amber-200">
                        <span class="h-2 w-2 rounded-full bg-[#d97706]"></span> Front-Desk:
                    </span>
                    <span class="font-extrabold text-white">${formatPeso(dCol)}</span>
                </div>
                <div class="flex items-center justify-between gap-4 text-xs mt-0.5">
                    <span class="inline-flex items-center gap-1.5 font-medium text-amber-200">
                        <span class="h-2 w-2 rounded-sm border border-amber-400"></span> Total Recorded:
                    </span>
                    <span class="font-bold text-white/90">${formatPeso(tCol)}</span>
                </div>
            `;
        }

        tooltip.innerHTML = html;
        tooltip.classList.remove('hidden');

        const rect = container.getBoundingClientRect();
        const posX = Math.max(70, Math.min(rect.width - 70, clientX - rect.left));
        const posY = Math.max(50, clientY - rect.top - 12);

        tooltip.style.left = posX + 'px';
        tooltip.style.top = posY + 'px';
    }

    function hideTooltip() {
        if (!tooltip) return;
        tooltip.classList.add('hidden');
        hoveredIndex = null;
    }

    function handlePointerMove(e) {
        const rect = canvas.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        const x = clientX - rect.left;

        const padL = currentMode === 'traffic' ? 36 : 60;
        const padR = 20;
        const chartW = Math.max(10, rect.width - padL - padR);
        const xStep = chartW / (n - 1 || 1);

        let nearestIdx = 0;
        let minDist = Infinity;
        for (let i = 0; i < n; i++) {
            const px = padL + i * xStep;
            const dist = Math.abs(x - px);
            if (dist < minDist) {
                minDist = dist;
                nearestIdx = i;
            }
        }

        if (minDist < xStep * 0.75) {
            hoveredIndex = nearestIdx;
            render();
            showTooltip(nearestIdx, clientX, clientY);
        } else {
            hideTooltip();
            render();
        }
    }

    canvas.addEventListener('mousemove', handlePointerMove);
    canvas.addEventListener('touchmove', handlePointerMove, { passive: true });
    canvas.addEventListener('mouseleave', () => {
        hideTooltip();
        render();
    });
    canvas.addEventListener('touchend', () => {
        hideTooltip();
        render();
    });

    setMode(currentMode);

    return {
        redraw: () => render(),
        setMode: (m) => setMode(m),
    };
}

function initActiveOverviewGraph() {
    const data = window.__sdActiveOverviewData;
    if (!data) return null;

    const donutCanvas = document.getElementById('activeOverviewDonutCanvas');
    const barCanvas = document.getElementById('activeOverviewBarCanvas');
    const splitView = document.getElementById('sdActiveOverviewSplitView');
    const columnsView = document.getElementById('sdActiveOverviewColumnsView');
    const tabSplit = document.getElementById('sdTabSplit');
    const tabColumns = document.getElementById('sdTabColumns');

    let currentMode = 'split';
    try {
        currentMode = localStorage.getItem('sd_ao_view') || 'split';
    } catch (e) {
        currentMode = 'split';
    }

    function setMode(mode) {
        currentMode = mode;
        try {
            localStorage.setItem('sd_ao_view', mode);
        } catch (e) { }

        if (mode === 'columns') {
            splitView?.classList.add('hidden');
            columnsView?.classList.remove('hidden');

            tabColumns?.classList.add('bg-white', 'text-hp-text', 'shadow-sm', 'dark:bg-[#222723]', 'dark:text-[#f3f4f6]', 'font-bold');
            tabColumns?.classList.remove('text-hp-text-muted', 'font-semibold');

            tabSplit?.classList.remove('bg-white', 'text-hp-text', 'shadow-sm', 'dark:bg-[#222723]', 'dark:text-[#f3f4f6]', 'font-bold');
            tabSplit?.classList.add('text-hp-text-muted', 'font-semibold');

            requestAnimationFrame(() => drawBarChart());
        } else {
            columnsView?.classList.add('hidden');
            splitView?.classList.remove('hidden');

            tabSplit?.classList.add('bg-white', 'text-hp-text', 'shadow-sm', 'dark:bg-[#222723]', 'dark:text-[#f3f4f6]', 'font-bold');
            tabSplit?.classList.remove('text-hp-text-muted', 'font-semibold');

            tabColumns?.classList.remove('bg-white', 'text-hp-text', 'shadow-sm', 'dark:bg-[#222723]', 'dark:text-[#f3f4f6]', 'font-bold');
            tabColumns?.classList.add('text-hp-text-muted', 'font-semibold');

            requestAnimationFrame(() => drawDonutChart());
        }
    }

    tabSplit?.addEventListener('click', () => setMode('split'));
    tabColumns?.addEventListener('click', () => setMode('columns'));

    function drawDonutChart() {
        if (!donutCanvas || donutCanvas.offsetParent === null) return;
        const ctx = donutCanvas.getContext('2d');
        const container = donutCanvas.parentElement;
        const dpr = window.devicePixelRatio || 1;
        const rect = container.getBoundingClientRect();
        const W = rect.width || 110;
        const H = rect.height || 110;

        donutCanvas.width = W * dpr;
        donutCanvas.height = H * dpr;
        donutCanvas.style.width = W + 'px';
        donutCanvas.style.height = H + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        ctx.clearRect(0, 0, W, H);

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const cx = W / 2;
        const cy = H / 2;
        const radius = Math.min(W, H) / 2 - 7;
        const strokeW = 9;

        // Base Track
        ctx.beginPath();
        ctx.arc(cx, cy, radius, 0, Math.PI * 2);
        ctx.lineWidth = strokeW;
        ctx.strokeStyle = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';
        ctx.stroke();

        const total = data.totalGuests;
        const walkIn = data.walkInGuests;
        const online = data.onlineGuests;

        if (total <= 0) {
            ctx.beginPath();
            ctx.arc(cx, cy, radius, 0, Math.PI * 2);
            ctx.lineWidth = strokeW;
            ctx.strokeStyle = isDark ? 'rgba(255, 255, 255, 0.12)' : 'rgba(0, 0, 0, 0.08)';
            ctx.stroke();
        } else {
            const startAngle = -Math.PI / 2;
            const walkInAngle = (walkIn / total) * Math.PI * 2;
            const gap = (walkIn > 0 && online > 0) ? 0.08 : 0;

            // Walk-in Arc
            if (walkIn > 0) {
                ctx.beginPath();
                const a1 = startAngle + (gap / 2);
                const a2 = startAngle + walkInAngle - (gap / 2);
                ctx.arc(cx, cy, radius, a1, Math.max(a1, a2));
                const grad = ctx.createLinearGradient(0, 0, W, H);
                grad.addColorStop(0, '#f59e0b');
                grad.addColorStop(1, '#fbbf24');
                ctx.strokeStyle = grad;
                ctx.lineWidth = strokeW;
                ctx.lineCap = 'round';
                ctx.stroke();
            }

            // Online Arc
            if (online > 0) {
                ctx.beginPath();
                const a1 = startAngle + walkInAngle + (gap / 2);
                const a2 = startAngle + Math.PI * 2 - (gap / 2);
                ctx.arc(cx, cy, radius, a1, Math.max(a1, a2));
                const grad = ctx.createLinearGradient(0, 0, W, H);
                grad.addColorStop(0, '#3b82f6');
                grad.addColorStop(1, '#60a5fa');
                ctx.strokeStyle = grad;
                ctx.lineWidth = strokeW;
                ctx.lineCap = 'round';
                ctx.stroke();
            }
        }

        // Inner Reservation Ring
        const innerR = radius - 10;
        const innerW = 4;
        ctx.beginPath();
        ctx.arc(cx, cy, innerR, 0, Math.PI * 2);
        ctx.lineWidth = innerW;
        ctx.strokeStyle = isDark ? 'rgba(255, 255, 255, 0.04)' : 'rgba(0, 0, 0, 0.03)';
        ctx.stroke();

        const resTotal = data.totalReservations;
        if (resTotal > 0) {
            const startAngle = -Math.PI / 2;
            const resWalkIn = data.walkInReservations;
            const resOnline = data.onlineReservations;
            const resWalkInAngle = (resWalkIn / resTotal) * Math.PI * 2;

            if (resWalkIn > 0) {
                ctx.beginPath();
                ctx.arc(cx, cy, innerR, startAngle, startAngle + resWalkInAngle);
                ctx.strokeStyle = 'rgba(245, 158, 11, 0.75)';
                ctx.lineWidth = innerW;
                ctx.stroke();
            }
            if (resOnline > 0) {
                ctx.beginPath();
                ctx.arc(cx, cy, innerR, startAngle + resWalkInAngle, startAngle + Math.PI * 2);
                ctx.strokeStyle = 'rgba(59, 130, 246, 0.75)';
                ctx.lineWidth = innerW;
                ctx.stroke();
            }
        }
    }

    function drawBarChart() {
        if (!barCanvas || barCanvas.offsetParent === null) return;
        const ctx = barCanvas.getContext('2d');
        const container = barCanvas.parentElement;
        const dpr = window.devicePixelRatio || 1;
        const rect = container.getBoundingClientRect();
        const W = rect.width || 300;
        const H = rect.height || 125;

        barCanvas.width = W * dpr;
        barCanvas.height = H * dpr;
        barCanvas.style.width = W + 'px';
        barCanvas.style.height = H + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        ctx.clearRect(0, 0, W, H);

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';

        const padL = 30;
        const padR = 15;
        const padT = 20;
        const padB = 25;
        const chartW = W - padL - padR;
        const chartH = H - padT - padB;

        const groups = [
            {
                name: 'Guests (On-Site)',
                bars: [
                    { label: 'Walk-in', val: data.walkInGuests, color: '#f59e0b', colorEnd: '#fbbf24' },
                    { label: 'Online', val: data.onlineGuests, color: '#3b82f6', colorEnd: '#60a5fa' }
                ]
            },
            {
                name: 'Reservations (Active)',
                bars: [
                    { label: 'Walk-in', val: data.walkInReservations, color: '#f59e0b', colorEnd: '#fbbf24' },
                    { label: 'Online', val: data.onlineReservations, color: '#3b82f6', colorEnd: '#60a5fa' }
                ]
            }
        ];

        const allVals = [
            data.walkInGuests, data.onlineGuests,
            data.walkInReservations, data.onlineReservations
        ];
        const rawMax = Math.max(1, ...allVals);
        const yMax = Math.ceil(rawMax * 1.25);

        // Y-axis grid lines
        ctx.strokeStyle = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';
        ctx.lineWidth = 1;
        ctx.font = '500 10px Montserrat, sans-serif';
        ctx.fillStyle = isDark ? '#9baaa1' : '#5c6b62';
        ctx.textAlign = 'right';

        const ticks = [0, Math.round(yMax / 2), yMax];
        ticks.forEach((tick) => {
            const y = padT + chartH - (tick / yMax) * chartH;
            ctx.beginPath();
            ctx.moveTo(padL, y);
            ctx.lineTo(W - padR, y);
            ctx.stroke();
            ctx.fillText(tick.toString(), padL - 6, y + 3);
        });

        // Grouped bars
        const groupW = chartW / groups.length;
        const barW = Math.min(26, (groupW - 40) / 2);
        const barGap = 6;

        groups.forEach((group, gIdx) => {
            const groupCenterX = padL + gIdx * groupW + groupW / 2;
            const totalBarsW = (group.bars.length * barW) + ((group.bars.length - 1) * barGap);
            const groupStartX = groupCenterX - (totalBarsW / 2);

            // Group Label
            ctx.textAlign = 'center';
            ctx.font = '600 10px Montserrat, sans-serif';
            ctx.fillStyle = isDark ? '#d1d5db' : '#374151';
            ctx.fillText(group.name, groupCenterX, H - 7);

            group.bars.forEach((bar, bIdx) => {
                const x = groupStartX + bIdx * (barW + barGap);
                const bH = (bar.val / yMax) * chartH;
                const y = padT + chartH - bH;

                const grad = ctx.createLinearGradient(x, y, x, padT + chartH);
                grad.addColorStop(0, bar.colorEnd);
                grad.addColorStop(1, bar.color);

                ctx.beginPath();
                const r = Math.min(4, barW / 2);
                if (bH > 0) {
                    ctx.moveTo(x + r, y);
                    ctx.lineTo(x + barW - r, y);
                    ctx.quadraticCurveTo(x + barW, y, x + barW, y + r);
                    ctx.lineTo(x + barW, padT + chartH);
                    ctx.lineTo(x, padT + chartH);
                    ctx.lineTo(x, y + r);
                    ctx.quadraticCurveTo(x, y, x + r, y);
                    ctx.closePath();
                    ctx.fillStyle = grad;
                    ctx.fill();
                } else {
                    ctx.strokeStyle = isDark ? 'rgba(255,255,255,0.2)' : 'rgba(0,0,0,0.15)';
                    ctx.lineWidth = 2;
                    ctx.moveTo(x, padT + chartH - 1);
                    ctx.lineTo(x + barW, padT + chartH - 1);
                    ctx.stroke();
                }

                ctx.textAlign = 'center';
                ctx.font = '700 10px Montserrat, sans-serif';
                ctx.fillStyle = isDark ? '#f3f4f6' : '#1f2937';
                ctx.fillText(bar.val.toString(), x + barW / 2, Math.max(padT - 3, y - 4));
            });
        });
    }

    setMode(currentMode);

    return {
        redraw: function () {
            if (currentMode === 'columns') {
                drawBarChart();
            } else {
                drawDonutChart();
            }
        }
    };
}

// Store raw revenue for chart scaling
(function () {
    const script = document.querySelector('script');
    if (window.__sdChartData) {
        // Re-extract raw revenue from the inline script data
        // The blade passes raw revenue values; we just need to also store them
    }
})();

document.addEventListener('DOMContentLoaded', () => window.AppPage['staff_dashboard']());