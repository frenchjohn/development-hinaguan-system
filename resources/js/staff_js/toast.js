// Shared toast helper for the staff portal. Lightweight, dependency-free:
// a fixed stack bottom-right, fade/slide in, auto-dismiss, and a
// sessionStorage bridge so a toast can survive a page reload (queue before
// reloading, showPendingToast() after the page comes back).

const TOAST_KEY = 'hpPendingToast';

const ensureContainer = () => {
    let container = document.getElementById('hpToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'hpToastContainer';
        container.setAttribute('aria-live', 'polite');
        container.style.cssText =
            'position:fixed;bottom:1.25rem;right:1.25rem;z-index:99999;display:flex;flex-direction:column;gap:0.5rem;max-width:min(22rem,calc(100vw - 2rem));pointer-events:none;';
        document.body.appendChild(container);
    }
    return container;
};

const toastColors = {
    success: { bg: '#15803d', icon: '#dcfce7' },
    error: { bg: '#b91c1c', icon: '#fee2e2' },
    info: { bg: '#1e40af', icon: '#dbeafe' },
};

const toastIcon = (type) => {
    const stroke = toastColors[type]?.icon || '#fff';
    if (type === 'error') {
        return `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="${stroke}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>`;
    }
    if (type === 'info') {
        return `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="${stroke}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;
    }
    return `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="${stroke}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;
};

export const showToast = (message, type = 'success', duration = 3200) => {
    const container = ensureContainer();
    const colors = toastColors[type] || toastColors.success;

    const toast = document.createElement('div');
    toast.className = 'hp-toast';
    toast.style.cssText =
        'display:flex;align-items:flex-start;gap:0.6rem;padding:0.7rem 0.95rem;border-radius:0.7rem;' +
        `background:${colors.bg};color:#fff;font:600 0.85rem/1.45 Poppins,system-ui,sans-serif;` +
        'box-shadow:0 10px 28px rgba(0,0,0,0.28);opacity:0;transform:translateY(8px);' +
        'transition:opacity .25s ease,transform .25s ease;pointer-events:auto;';

    toast.innerHTML = `<span style="display:flex;align-items:center;justify-content:center;flex-shrink:0;width:1.4rem;height:1.4rem;margin-top:1px;">${toastIcon(type)}</span><span style="min-width:0;flex:1;">${message}</span>`;

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.setAttribute('aria-label', 'Dismiss notification');
    closeBtn.style.cssText =
        'flex-shrink:0;cursor:pointer;border:0;background:transparent;color:rgba(255,255,255,0.85);' +
        'font-size:1.1rem;line-height:1;padding:0 0.1rem;margin-left:0.25rem;';
    closeBtn.textContent = '×';
    closeBtn.addEventListener('click', () => dismiss());
    toast.appendChild(closeBtn);

    container.appendChild(toast);

    // Force a reflow so the enter transition plays.
    requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    });

    let timer = null;
    const dismiss = () => {
        if (timer) clearTimeout(timer);
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(8px)';
        setTimeout(() => toast.remove(), 250);
    };

    timer = setTimeout(dismiss, duration);
};

// Queue a toast that should appear AFTER a page reload (e.g. store the message
// before calling location.reload()). The fresh page shows it via showPendingToast().
export const queueToast = (message, type = 'success') => {
    try {
        sessionStorage.setItem(TOAST_KEY, JSON.stringify({ message: String(message), type: type || 'success' }));
    } catch (e) { /* storage unavailable — ignore */ }
};

export const showPendingToast = () => {
    let pending = null;
    try {
        pending = sessionStorage.getItem(TOAST_KEY);
        if (pending) sessionStorage.removeItem(TOAST_KEY);
    } catch (e) { /* ignore */ }
    if (!pending) return;
    try {
        const parsed = JSON.parse(pending);
        showToast(parsed.message, parsed.type || 'success');
    } catch (e) {
        showToast(pending);
    }
};

// Convert a server-rendered flash banner (e.g. session('success') after a
// redirect) into a toast, then hide the banner.
export const convertFlashToToast = () => {
    document.querySelectorAll('[data-page-flash]').forEach((el) => {
        const type = el.getAttribute('data-page-flash') === 'error' ? 'error' : 'success';
        const message = el.textContent.trim();
        if (message) showToast(message, type);
        el.hidden = true;
    });
};
