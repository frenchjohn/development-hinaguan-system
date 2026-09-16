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

    // Area chart rendering
    const chartData = window.__sdChartData;
    const canvas = document.getElementById('sdAreaChartCanvas');
    let drawChart = function () {};

    if (chartData && canvas) {
        const ctx = canvas.getContext('2d');
        const container = canvas.parentElement;

        drawChart = function () {
        const dpr = window.devicePixelRatio || 1;
        const rect = container.getBoundingClientRect();
        const W = rect.width;
        const H = rect.height;

        canvas.width = W * dpr;
        canvas.height = H * dpr;
        canvas.style.width = W + 'px';
        canvas.style.height = H + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        const labels = chartData.labels;
        const bookings = chartData.bookings;
        // Normalize revenue relative to booking scale for overlay
        const revenueRaw = window.__sdChartData_revenueRaw || chartData.revenue;

        const n = labels.length;
        if (n === 0) return;

        const padL = 35;
        const padR = 15;
        const padT = 20;
        const padB = 30;
        const chartW = W - padL - padR;
        const chartH = H - padT - padB;

        // Compute max for y-axis (bookings scale)
        const maxBooking = Math.max(1, ...bookings);
        // Revenue max for its own scale
        const maxRevenue = Math.max(1, ...revenueRaw);

        // Nice y-axis ticks for bookings
        const tickCount = 5;
        const step = Math.ceil(maxBooking / tickCount);
        const yMax = step * tickCount;

        // Clear
        ctx.clearRect(0, 0, W, H);

        // Draw grid lines and y-axis labels
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        ctx.strokeStyle = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.07)';
        ctx.lineWidth = 1;
        ctx.font = '500 11px Montserrat, sans-serif';
        ctx.fillStyle = isDark ? '#9baaa1' : '#5c6b62';
        ctx.textAlign = 'right';

        for (let i = 0; i <= tickCount; i++) {
            const val = step * i;
            const y = padT + chartH - (val / yMax) * chartH;
            ctx.beginPath();
            ctx.moveTo(padL, y);
            ctx.lineTo(W - padR, y);
            ctx.stroke();
            ctx.fillText(val.toString(), padL - 6, y + 4);
        }

        // X positions
        const xStep = chartW / (n - 1 || 1);
        const xPositions = labels.map((_, i) => padL + i * xStep);

        // Draw x labels
        ctx.textAlign = 'center';
        ctx.font = '600 11px Montserrat, sans-serif';
        ctx.fillStyle = isDark ? '#9baaa1' : '#5c6b62';
        labels.forEach((label, i) => {
            ctx.fillText(label, xPositions[i], H - 8);
        });

        // Helper: smooth curve through points
        function drawSmoothLine(points) {
            if (points.length < 2) return;
            ctx.moveTo(points[0].x, points[0].y);
            for (let i = 0; i < points.length - 1; i++) {
                const cp = (points[i + 1].x - points[i].x) / 2.5;
                ctx.bezierCurveTo(
                    points[i].x + cp, points[i].y,
                    points[i + 1].x - cp, points[i + 1].y,
                    points[i + 1].x, points[i + 1].y
                );
            }
        }

        // Booking points
        const bookingPts = bookings.map((val, i) => ({
            x: xPositions[i],
            y: padT + chartH - (val / yMax) * chartH
        }));

        // Revenue points (scaled to their own max, but mapped to chartH)
        const revenuePts = revenueRaw.map((val, i) => ({
            x: xPositions[i],
            y: padT + chartH - (val / (maxRevenue || 1)) * chartH * 0.85
        }));

        // Draw Revenue area (behind bookings — gold/tan)
        ctx.save();
        ctx.beginPath();
        drawSmoothLine(revenuePts);
        ctx.lineTo(xPositions[n - 1], padT + chartH);
        ctx.lineTo(xPositions[0], padT + chartH);
        ctx.closePath();
        const revGrad = ctx.createLinearGradient(0, padT, 0, padT + chartH);
        revGrad.addColorStop(0, isDark ? 'rgba(200,164,93,0.35)' : 'rgba(200,164,93,0.35)');
        revGrad.addColorStop(1, isDark ? 'rgba(200,164,93,0.05)' : 'rgba(200,164,93,0.05)');
        ctx.fillStyle = revGrad;
        ctx.fill();
        ctx.restore();

        // Revenue line
        ctx.save();
        ctx.beginPath();
        drawSmoothLine(revenuePts);
        ctx.strokeStyle = isDark ? '#d4b06a' : '#c8a45d';
        ctx.lineWidth = 2;
        ctx.stroke();
        ctx.restore();

        // Draw Bookings area (green)
        ctx.save();
        ctx.beginPath();
        drawSmoothLine(bookingPts);
        ctx.lineTo(xPositions[n - 1], padT + chartH);
        ctx.lineTo(xPositions[0], padT + chartH);
        ctx.closePath();
        const bookGrad = ctx.createLinearGradient(0, padT, 0, padT + chartH);
        bookGrad.addColorStop(0, isDark ? 'rgba(28,92,60,0.5)' : 'rgba(28,92,60,0.45)');
        bookGrad.addColorStop(1, isDark ? 'rgba(28,92,60,0.05)' : 'rgba(28,92,60,0.05)');
        ctx.fillStyle = bookGrad;
        ctx.fill();
        ctx.restore();

        // Bookings line
        ctx.save();
        ctx.beginPath();
        drawSmoothLine(bookingPts);
        ctx.strokeStyle = isDark ? '#4c9a5f' : '#1c5c3c';
        ctx.lineWidth = 2.5;
        ctx.stroke();
        ctx.restore();

        // Draw dots for bookings
        bookingPts.forEach((pt) => {
            ctx.beginPath();
            ctx.arc(pt.x, pt.y, 3.5, 0, Math.PI * 2);
            ctx.fillStyle = isDark ? '#4c9a5f' : '#1c5c3c';
            ctx.fill();
            ctx.strokeStyle = isDark ? '#14211a' : '#ffffff';
            ctx.lineWidth = 2;
            ctx.stroke();
        });
        };

        drawChart();
    }

    // Redraw on resize
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            drawChart();
            aoControl?.redraw();
        }, 150);
    });

    // Redraw on theme change
    const observer = new MutationObserver(() => {
        setTimeout(() => {
            drawChart();
            aoControl?.redraw();
        }, 100);
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
};

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
        } catch (e) {}

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