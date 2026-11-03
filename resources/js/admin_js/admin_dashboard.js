window.AppPage = window.AppPage || {};
window.AppPage['admin_dashboard'] = function () {
    // 1. Live clock ticker
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

    // 2. Executive Analytics Chart for Admin
    initAdminExecutiveChart();
};

function initAdminExecutiveChart() {
    const rawData = window.__sdAdminOpsChartData || {};
    const canvas = document.getElementById('sdAreaChartCanvas');
    const container = document.getElementById('sdAdminChartWrap') || (canvas ? canvas.parentElement : null);
    const tooltip = document.getElementById('sdAdminChartTooltip');
    const tabRevenue = document.getElementById('sdTabRevenue');
    const tabBookings = document.getElementById('sdTabBookings');
    const titleEl = document.getElementById('sdAdminChartTitle');
    const subtitleEl = document.getElementById('sdAdminChartSubtitle');
    const legendEl = document.getElementById('sdAdminChartLegend');
    const linkEl = document.getElementById('sdAdminChartLink');
    const linkLabel = document.getElementById('sdAdminChartLinkLabel');

    const stat1Label = document.getElementById('sdAdminStat1Label');
    const stat1Val = document.getElementById('sdAdminStat1Val');
    const stat2Label = document.getElementById('sdAdminStat2Label');
    const stat2Val = document.getElementById('sdAdminStat2Val');
    const stat3Label = document.getElementById('sdAdminStat3Label');
    const stat3Val = document.getElementById('sdAdminStat3Val');

    if (!canvas || !container) return;
    const ctx = canvas.getContext('2d');

    const labels = rawData.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const dates = rawData.dates || labels;
    const fullDates = rawData.fullDates || labels;
    const revenue = rawData.revenue || [0, 0, 0, 0, 0, 0, 0];
    const deskRevenue = rawData.deskRevenue || [0, 0, 0, 0, 0, 0, 0];
    const onlineRevenue = rawData.onlineRevenue || [0, 0, 0, 0, 0, 0, 0];
    const bookings = rawData.bookings || [0, 0, 0, 0, 0, 0, 0];
    const guests = rawData.guests || [0, 0, 0, 0, 0, 0, 0];

    const n = labels.length;
    if (n === 0) return;

    let currentMode = 'revenue';
    try {
        currentMode = localStorage.getItem('sd_admin_chart_mode') || 'revenue';
    } catch (e) {
        currentMode = 'revenue';
    }

    let hoveredIndex = null;

    function formatPeso(num) {
        return '₱' + Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatPesoShort(val) {
        if (val >= 1000000) return '₱' + (val / 1000000).toFixed(1) + 'M';
        if (val >= 1000) return '₱' + (val / 1000).toFixed(1) + 'k';
        return '₱' + Math.round(val);
    }

    function setMode(mode) {
        currentMode = mode;
        try {
            localStorage.setItem('sd_admin_chart_mode', mode);
        } catch (e) { }

        const isRevenue = mode === 'revenue';

        if (tabRevenue && tabBookings) {
            tabRevenue.className = isRevenue
                ? 'inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-[0.68rem] font-bold transition-all bg-white text-hp-text shadow-sm dark:bg-[#222723] dark:text-[#f3f4f6]'
                : 'inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-[0.68rem] font-semibold text-hp-text-muted transition-all hover:text-hp-text dark:hover:text-[#f3f4f6]';
            tabBookings.className = !isRevenue
                ? 'inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-[0.68rem] font-bold transition-all bg-white text-hp-text shadow-sm dark:bg-[#222723] dark:text-[#f3f4f6]'
                : 'inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-[0.68rem] font-semibold text-hp-text-muted transition-all hover:text-hp-text dark:hover:text-[#f3f4f6]';
        }

        if (titleEl) {
            titleEl.textContent = isRevenue ? 'Financial Revenue & Collections' : 'Booking Volume & Guest Demand';
        }
        if (subtitleEl) {
            subtitleEl.textContent = isRevenue
                ? '7-day daily financial revenue breakdown across front-desk and online channels (PHP)'
                : '7-day volume of confirmed reservations and park guest headcount';
        }

        if (legendEl) {
            legendEl.innerHTML = isRevenue
                ? `<span class="inline-flex items-center gap-1.5 text-[0.7rem] font-semibold text-hp-text-muted">
                        <span class="h-2.5 w-2.5 rounded-full bg-[#1c5c3c] dark:bg-emerald-400 inline-block"></span> Total Revenue
                   </span>
                   <span class="inline-flex items-center gap-1.5 text-[0.7rem] font-semibold text-hp-text-muted">
                        <span class="h-2 w-2 rounded-sm bg-[#c8a45d] inline-block"></span> Front-Desk
                   </span>`
                : `<span class="inline-flex items-center gap-1.5 text-[0.7rem] font-semibold text-hp-text-muted">
                        <span class="h-2.5 w-2.5 rounded-full bg-[#10b981] inline-block"></span> Guests Admitted
                   </span>
                   <span class="inline-flex items-center gap-1.5 text-[0.7rem] font-semibold text-hp-text-muted">
                        <span class="h-2 w-2 rounded-sm bg-[#0d9488] inline-block"></span> Bookings
                   </span>`;
        }

        if (linkEl && rawData.routes) {
            linkEl.href = isRevenue ? rawData.routes.payment : rawData.routes.reports;
            linkEl.title = isRevenue ? 'View Detailed Payments' : 'View Reports & Analytics';
            if (linkLabel) {
                linkLabel.textContent = isRevenue ? 'Payments' : 'Reports';
            }
        }

        // Summary Metric Strip
        if (stat1Label && stat1Val && stat2Label && stat2Val && stat3Label && stat3Val) {
            if (isRevenue) {
                stat1Label.textContent = '7-Day Revenue';
                stat1Val.textContent = formatPeso(rawData.totalRevenue || 0);

                stat2Label.textContent = 'Daily Average';
                stat2Val.innerHTML = `${formatPeso(rawData.avgDailyRevenue || 0)} <span class="text-[0.65rem] font-normal text-hp-text-muted">/ day</span>`;

                stat3Label.textContent = 'Peak Revenue';
                stat3Val.textContent = rawData.peakRevDay || 'None';
            } else {
                stat1Label.textContent = '7-Day Bookings';
                stat1Val.innerHTML = `${rawData.totalBookings || 0} <span class="text-[0.65rem] font-normal text-hp-text-muted">bookings</span>`;

                stat2Label.textContent = '7-Day Guests';
                stat2Val.innerHTML = `${rawData.totalGuests || 0} <span class="text-[0.65rem] font-normal text-hp-text-muted">guests</span>`;

                stat3Label.textContent = 'Peak Demand';
                stat3Val.textContent = rawData.peakBookDay || 'None';
            }
        }

        hideTooltip();
        drawChart();
    }

    if (tabRevenue) tabRevenue.addEventListener('click', () => setMode('revenue'));
    if (tabBookings) tabBookings.addEventListener('click', () => setMode('bookings'));

    function drawSmoothCurve(points, strokeColor, fillColor, lineWidth = 2.5, H = 250, padB = 30) {
        if (!points || points.length < 2) return;
        const baselineY = H - padB;

        // Path for fill
        if (fillColor) {
            ctx.save();
            ctx.beginPath();
            ctx.moveTo(points[0].x, points[0].y);
            for (let i = 0; i < points.length - 1; i++) {
                const cp = (points[i + 1].x - points[i].x) / 2.5;
                ctx.bezierCurveTo(
                    points[i].x + cp, points[i].y,
                    points[i + 1].x - cp, points[i + 1].y,
                    points[i + 1].x, points[i + 1].y
                );
            }
            ctx.lineTo(points[points.length - 1].x, baselineY);
            ctx.lineTo(points[0].x, baselineY);
            ctx.closePath();
            ctx.fillStyle = fillColor;
            ctx.fill();
            ctx.restore();
        }

        // Path for stroke
        ctx.save();
        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        for (let i = 0; i < points.length - 1; i++) {
            const cp = (points[i + 1].x - points[i].x) / 2.5;
            ctx.bezierCurveTo(
                points[i].x + cp, points[i].y,
                points[i + 1].x - cp, points[i + 1].y,
                points[i + 1].x, points[i + 1].y
            );
        }
        ctx.strokeStyle = strokeColor;
        ctx.lineWidth = lineWidth;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.stroke();
        ctx.restore();
    }

    function drawChart() {
        const dpr = window.devicePixelRatio || 1;
        // Read clientWidth from container; lock height to stable fixed 250px so it NEVER stretches!
        const W = container.clientWidth || 300;
        const H = 250;

        if (W <= 0) return;

        const targetW = Math.round(W * dpr);
        const targetH = Math.round(H * dpr);

        if (canvas.width !== targetW || canvas.height !== targetH) {
            canvas.width = targetW;
            canvas.height = targetH;
            canvas.style.width = W + 'px';
            canvas.style.height = H + 'px';
        }
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        const padL = currentMode === 'revenue' ? 62 : 44;
        const padR = 24;
        const padT = 20;
        const padB = 30;
        const chartW = Math.max(10, W - padL - padR);
        const chartH = Math.max(10, H - padT - padB);

        ctx.clearRect(0, 0, W, H);

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';

        // Select primary & secondary series
        const primarySeries = currentMode === 'revenue' ? revenue : guests;
        const secondarySeries = currentMode === 'revenue' ? deskRevenue : bookings;

        const maxVal = Math.max(1, ...primarySeries, ...secondarySeries);
        const tickCount = 4;
        const step = currentMode === 'revenue'
            ? Math.ceil(maxVal / tickCount / 100) * 100 || 100
            : Math.ceil(maxVal / tickCount) || 1;
        const yMax = step * tickCount || 1;

        // Draw horizontal grid lines & Y labels
        ctx.lineWidth = 1;
        ctx.font = '500 11px Montserrat, sans-serif';
        ctx.fillStyle = isDark ? '#9baaa1' : '#6b7280';
        ctx.textAlign = 'right';

        for (let i = 0; i <= tickCount; i++) {
            const val = step * i;
            const y = padT + chartH - (val / yMax) * chartH;

            ctx.strokeStyle = isDark ? 'rgba(255, 255, 255, 0.07)' : 'rgba(0, 0, 0, 0.06)';
            ctx.beginPath();
            ctx.moveTo(padL, y);
            ctx.lineTo(W - padR, y);
            ctx.stroke();

            const yText = currentMode === 'revenue' ? formatPesoShort(val) : val.toString();
            ctx.fillText(yText, padL - 8, y + 4);
        }

        // X Positions
        const xStep = chartW / (n - 1 || 1);
        const xPositions = labels.map((_, i) => padL + i * xStep);

        // Draw X-axis labels
        ctx.textAlign = 'center';
        ctx.font = '600 11px Montserrat, sans-serif';
        labels.forEach((label, i) => {
            const isHovered = hoveredIndex === i;
            ctx.fillStyle = isHovered
                ? (isDark ? '#34d399' : '#1c5c3c')
                : (isDark ? '#9baaa1' : '#6b7280');
            ctx.fillText(label, xPositions[i], H - 10);
        });

        // Compute points
        const primaryPts = primarySeries.map((val, i) => ({
            x: xPositions[i],
            y: padT + chartH - (val / yMax) * chartH
        }));
        const secondaryPts = secondarySeries.map((val, i) => ({
            x: xPositions[i],
            y: padT + chartH - (val / yMax) * chartH
        }));

        // Secondary curve
        if (currentMode === 'revenue') {
            // Front-Desk line (dashed subtle gold)
            ctx.save();
            ctx.setLineDash([4, 4]);
            drawSmoothCurve(secondaryPts, isDark ? '#e0b868' : '#b88b3d', null, 2, H, padB);
            ctx.restore();
        } else {
            // Bookings line (dashed teal)
            ctx.save();
            ctx.setLineDash([4, 4]);
            drawSmoothCurve(secondaryPts, isDark ? '#2dd4bf' : '#0d9488', null, 2, H, padB);
            ctx.restore();
        }

        // Primary curve with lush gradient fill
        const primaryGrad = ctx.createLinearGradient(0, padT, 0, padT + chartH);
        if (currentMode === 'revenue') {
            primaryGrad.addColorStop(0, isDark ? 'rgba(52, 211, 153, 0.35)' : 'rgba(28, 92, 60, 0.30)');
            primaryGrad.addColorStop(1, isDark ? 'rgba(52, 211, 153, 0.02)' : 'rgba(28, 92, 60, 0.01)');
            drawSmoothCurve(primaryPts, isDark ? '#34d399' : '#1c5c3c', primaryGrad, 2.8, H, padB);
        } else {
            primaryGrad.addColorStop(0, isDark ? 'rgba(16, 185, 129, 0.35)' : 'rgba(16, 185, 129, 0.28)');
            primaryGrad.addColorStop(1, isDark ? 'rgba(16, 185, 129, 0.02)' : 'rgba(16, 185, 129, 0.01)');
            drawSmoothCurve(primaryPts, isDark ? '#10b981' : '#059669', primaryGrad, 2.8, H, padB);
        }

        // Points
        primaryPts.forEach((pt, i) => {
            const isHovered = hoveredIndex === i;
            ctx.beginPath();
            ctx.arc(pt.x, pt.y, isHovered ? 5.5 : 3.5, 0, Math.PI * 2);
            ctx.fillStyle = isDark ? (currentMode === 'revenue' ? '#34d399' : '#10b981') : (currentMode === 'revenue' ? '#1c5c3c' : '#059669');
            ctx.fill();
            ctx.strokeStyle = isDark ? '#14211a' : '#ffffff';
            ctx.lineWidth = isHovered ? 2.5 : 2;
            ctx.stroke();

            if (isHovered) {
                ctx.beginPath();
                ctx.arc(pt.x, pt.y, 9, 0, Math.PI * 2);
                ctx.strokeStyle = isDark ? 'rgba(52, 211, 153, 0.4)' : 'rgba(28, 92, 60, 0.3)';
                ctx.lineWidth = 1.8;
                ctx.stroke();
            }
        });

        // Hover vertical crosshair
        if (hoveredIndex !== null && xPositions[hoveredIndex] !== undefined) {
            const hX = xPositions[hoveredIndex];
            ctx.save();
            ctx.setLineDash([3, 3]);
            ctx.strokeStyle = isDark ? 'rgba(255, 255, 255, 0.25)' : 'rgba(0, 0, 0, 0.2)';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(hX, padT);
            ctx.lineTo(hX, padT + chartH);
            ctx.stroke();
            ctx.restore();
        }
    }

    function updateTooltip(e) {
        if (!tooltip) return;
        const rect = canvas.getBoundingClientRect();
        const mouseX = e.clientX - rect.left;
        const mouseY = e.clientY - rect.top;

        const padL = currentMode === 'revenue' ? 62 : 44;
        const padR = 24;
        const chartW = Math.max(10, rect.width - padL - padR);
        const xStep = chartW / (n - 1 || 1);

        let closestIdx = Math.round((mouseX - padL) / xStep);
        if (closestIdx < 0) closestIdx = 0;
        if (closestIdx >= n) closestIdx = n - 1;

        if (hoveredIndex !== closestIdx) {
            hoveredIndex = closestIdx;
            drawChart();
        }

        const xPos = padL + closestIdx * xStep;
        const dateLabel = fullDates[closestIdx] || labels[closestIdx];

        let content = '';
        if (currentMode === 'revenue') {
            const tot = revenue[closestIdx] || 0;
            const desk = deskRevenue[closestIdx] || 0;
            const onl = onlineRevenue[closestIdx] || 0;
            const bks = bookings[closestIdx] || 0;
            const gst = guests[closestIdx] || 0;

            content = `
                <div class="text-[0.68rem] font-bold text-emerald-200 border-b border-white/10 pb-1 mb-1.5 flex items-center justify-between gap-3">
                    <span>${dateLabel}</span>
                    <span class="text-[0.6rem] uppercase tracking-wider text-white/60">Revenue</span>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="flex items-center justify-between gap-4 font-bold">
                        <span class="text-white/80">Total Collections:</span>
                        <span class="text-emerald-300 font-display text-sm">${formatPeso(tot)}</span>
                    </div>
                    <div class="flex items-center justify-between gap-4 text-[0.7rem] text-white/70">
                        <span class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-sm bg-[#c8a45d] inline-block"></span> Front-Desk:
                        </span>
                        <span class="font-medium text-white/90">${formatPeso(desk)}</span>
                    </div>
                    <div class="flex items-center justify-between gap-4 text-[0.7rem] text-white/70">
                        <span class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-sm bg-blue-400 inline-block"></span> Online Payments:
                        </span>
                        <span class="font-medium text-white/90">${formatPeso(onl)}</span>
                    </div>
                    <div class="flex items-center justify-between gap-4 text-[0.68rem] text-white/60 pt-1 border-t border-white/10">
                        <span>Activity:</span>
                        <span>${bks} bookings (${gst} guests)</span>
                    </div>
                </div>
            `;
        } else {
            const bks = bookings[closestIdx] || 0;
            const gst = guests[closestIdx] || 0;
            const tot = revenue[closestIdx] || 0;

            content = `
                <div class="text-[0.68rem] font-bold text-emerald-200 border-b border-white/10 pb-1 mb-1.5 flex items-center justify-between gap-3">
                    <span>${dateLabel}</span>
                    <span class="text-[0.6rem] uppercase tracking-wider text-white/60">Bookings</span>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="flex items-center justify-between gap-4 font-bold">
                        <span class="text-white/80">Guests Admitted:</span>
                        <span class="text-emerald-300 font-display text-sm">${gst} guests</span>
                    </div>
                    <div class="flex items-center justify-between gap-4 text-[0.7rem] text-white/70">
                        <span class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-sm bg-[#0d9488] inline-block"></span> Reservations:
                        </span>
                        <span class="font-medium text-white/90">${bks} bookings</span>
                    </div>
                    <div class="flex items-center justify-between gap-4 text-[0.68rem] text-white/60 pt-1 border-t border-white/10">
                        <span>Day Collections:</span>
                        <span class="text-amber-300 font-medium">${formatPeso(tot)}</span>
                    </div>
                </div>
            `;
        }

        tooltip.innerHTML = content;
        tooltip.classList.remove('hidden');

        // Position tooltip horizontally (centered on xPos with safe margins)
        const tipWidth = tooltip.offsetWidth || 190;
        const halfWidth = tipWidth / 2;
        let leftPx = xPos;
        if (leftPx - halfWidth < 12) leftPx = halfWidth + 12;
        if (leftPx + halfWidth > rect.width - 12) leftPx = rect.width - halfWidth - 12;

        // Position tooltip vertically relative to the target data point
        const tipHeight = tooltip.offsetHeight || 135;
        const primarySeries = currentMode === 'revenue' ? revenue : guests;
        const secondarySeries = currentMode === 'revenue' ? deskRevenue : bookings;
        const maxVal = Math.max(1, ...primarySeries, ...secondarySeries);
        const padT = 20;
        const padB = 30;
        const chartH = Math.max(10, 250 - padT - padB);
        const tickCount = 4;
        const step = currentMode === 'revenue'
            ? Math.ceil(maxVal / tickCount / 100) * 100 || 100
            : Math.ceil(maxVal / tickCount) || 1;
        const yMax = step * tickCount || 1;
        const currentVal = primarySeries[closestIdx] || 0;
        const pointY = padT + chartH - (currentVal / yMax) * chartH;

        // Flip below point if too close to top to avoid getting covered/clipped
        let topPx;
        if (pointY - tipHeight - 14 < 8) {
            topPx = pointY + 18;
        } else {
            topPx = pointY - tipHeight - 14;
        }

        // Clamp safely within visible vertical boundary
        topPx = Math.max(8, Math.min(270 - tipHeight - 8, topPx));

        tooltip.style.left = leftPx + 'px';
        tooltip.style.top = topPx + 'px';
    }

    function hideTooltip() {
        if (!tooltip) return;
        tooltip.classList.add('hidden');
        if (hoveredIndex !== null) {
            hoveredIndex = null;
            drawChart();
        }
    }

    canvas.addEventListener('mousemove', updateTooltip);
    canvas.addEventListener('mouseleave', hideTooltip);

    // Canvas click navigates to corresponding section
    canvas.addEventListener('click', () => {
        if (rawData.routes) {
            window.location.href = currentMode === 'revenue' ? rawData.routes.payment : rawData.routes.reports;
        }
    });

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(drawChart, 100);
    });

    const observer = new MutationObserver(() => {
        setTimeout(drawChart, 60);
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

    // Initial setup
    setMode(currentMode);
}

document.addEventListener('DOMContentLoaded', () => window.AppPage['admin_dashboard']());
