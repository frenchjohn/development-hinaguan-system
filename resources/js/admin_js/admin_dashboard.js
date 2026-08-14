window.AppPage = window.AppPage || {};
window.AppPage['admin_dashboard'] = function () {
    // Live clock ticker
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

    // Area chart rendering for Revenue & Bookings
    const chartData = window.__sdChartData;
    if (!chartData) return;

    const canvas = document.getElementById('sdAreaChartCanvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const container = canvas.parentElement;

    function drawChart() {
        const dpr = window.devicePixelRatio || 1;
        const rect = container.getBoundingClientRect();
        const W = rect.width;
        const H = rect.height;

        if (W === 0 || H === 0) return;

        canvas.width = W * dpr;
        canvas.height = H * dpr;
        canvas.style.width = W + 'px';
        canvas.style.height = H + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        const labels = chartData.labels || [];
        const bookings = chartData.bookings || [];
        const n = labels.length;
        if (n === 0) return;

        const padL = 40;
        const padR = 20;
        const padT = 20;
        const padB = 30;
        const chartW = W - padL - padR;
        const chartH = H - padT - padB;

        const maxBooking = Math.max(1, ...bookings);
        const tickCount = 5;
        const step = Math.ceil(maxBooking / tickCount);
        const yMax = step * tickCount || 1;

        ctx.clearRect(0, 0, W, H);

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
            const labelVal = val >= 1000 ? (val / 1000).toFixed(1) + 'k' : val.toString();
            ctx.fillText(labelVal, padL - 6, y + 4);
        }

        const xStep = chartW / (n - 1 || 1);
        const xPositions = labels.map((_, i) => padL + i * xStep);

        ctx.textAlign = 'center';
        ctx.font = '600 11px Montserrat, sans-serif';
        ctx.fillStyle = isDark ? '#9baaa1' : '#5c6b62';
        labels.forEach((label, i) => {
            ctx.fillText(label, xPositions[i], H - 8);
        });

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

        const bookingPts = bookings.map((val, i) => ({
            x: xPositions[i],
            y: padT + chartH - (val / yMax) * chartH
        }));

        // Fill area under curve
        ctx.save();
        ctx.beginPath();
        drawSmoothLine(bookingPts);
        ctx.lineTo(xPositions[n - 1], padT + chartH);
        ctx.lineTo(xPositions[0], padT + chartH);
        ctx.closePath();
        const bookGrad = ctx.createLinearGradient(0, padT, 0, padT + chartH);
        bookGrad.addColorStop(0, isDark ? 'rgba(28,92,60,0.5)' : 'rgba(28,92,60,0.35)');
        bookGrad.addColorStop(1, isDark ? 'rgba(28,92,60,0.05)' : 'rgba(28,92,60,0.02)');
        ctx.fillStyle = bookGrad;
        ctx.fill();
        ctx.restore();

        // Stroke curve
        ctx.save();
        ctx.beginPath();
        drawSmoothLine(bookingPts);
        ctx.strokeStyle = isDark ? '#4c9a5f' : '#1c5c3c';
        ctx.lineWidth = 2.5;
        ctx.stroke();
        ctx.restore();

        // Draw points
        bookingPts.forEach((pt) => {
            ctx.beginPath();
            ctx.arc(pt.x, pt.y, 4, 0, Math.PI * 2);
            ctx.fillStyle = isDark ? '#4c9a5f' : '#1c5c3c';
            ctx.fill();
            ctx.strokeStyle = isDark ? '#14211a' : '#ffffff';
            ctx.lineWidth = 2;
            ctx.stroke();
        });
    }

    drawChart();

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(drawChart, 150);
    });

    const observer = new MutationObserver(() => {
        setTimeout(drawChart, 100);
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
};

document.addEventListener('DOMContentLoaded', () => window.AppPage['admin_dashboard']());