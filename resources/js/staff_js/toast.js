// Shared toast helper for the staff portal. Lightweight, dependency-free:
// a fixed stack bottom-right, fade/slide in, auto-dismiss, deduplication,
// maximum stack limits, and a sessionStorage bridge so a toast can survive
// a page reload (queue before reloading, showPendingToast() after the page comes back).

const TOAST_KEY = 'hpPendingToast';
const MAX_VISIBLE_TOASTS = 3;

// Map of currently active toasts: key -> { el, timer, count, countBadge, isDismissing, dismiss }
const activeToasts = new Map();

const ensureContainer = () => {
    let container = document.getElementById('hpToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'hpToastContainer';
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-atomic', 'true');
        container.style.cssText =
            'position:fixed;bottom:1.25rem;right:1.25rem;z-index:99999;display:flex;flex-direction:column;align-items:flex-end;gap:0.5rem;max-width:min(24rem,calc(100vw - 2rem));pointer-events:none;';
        document.body.appendChild(container);
    }
    return container;
};

const toastColors = {
    success: { bg: '#15803d', icon: '#dcfce7' },
    error: { bg: '#b91c1c', icon: '#fee2e2' },
    info: { bg: '#1e40af', icon: '#dbeafe' },
    warning: { bg: '#b45309', icon: '#fef3c7' },
};

const toastIcon = (type) => {
    const stroke = toastColors[type]?.icon || '#fff';
    if (type === 'error') {
        return `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="${stroke}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>`;
    }
    if (type === 'info') {
        return `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="${stroke}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;
    }
    if (type === 'warning') {
        return `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="${stroke}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;
    }
    return `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="${stroke}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;
};

export const showToast = (message, type = 'success', duration = 3200) => {
    if (!message) return;
    const cleanMsg = String(message).trim();
    if (!cleanMsg) return;

    const normalizedType = toastColors[type] ? type : 'success';
    const key = `${normalizedType}:::${cleanMsg}`;

    // 1. Deduplication: If identical toast is already active in container, refresh and pulse it
    if (activeToasts.has(key)) {
        const existing = activeToasts.get(key);
        if (existing && existing.el && document.body.contains(existing.el) && !existing.isDismissing) {
            existing.count = (existing.count || 1) + 1;
            if (existing.countBadge) {
                existing.countBadge.textContent = `×${existing.count}`;
                existing.countBadge.style.display = 'inline-flex';
            }
            // Reset auto-dismiss timer
            if (existing.timer) clearTimeout(existing.timer);
            existing.timer = setTimeout(existing.dismiss, duration);

            // Subtle bounce/pulse to give visual feedback that action was registered
            existing.el.style.transform = 'scale(1.04)';
            setTimeout(() => {
                if (!existing.isDismissing && document.body.contains(existing.el)) {
                    existing.el.style.transform = 'scale(1)';
                }
            }, 120);

            return existing.el;
        }
    }

    const container = ensureContainer();
    const colors = toastColors[normalizedType] || toastColors.success;

    // 2. Limit maximum visible toasts (evict oldest if full)
    const activeEntries = Array.from(activeToasts.entries()).filter(([, item]) => !item.isDismissing && document.body.contains(item.el));
    if (activeEntries.length >= MAX_VISIBLE_TOASTS) {
        const excess = activeEntries.length - MAX_VISIBLE_TOASTS + 1;
        for (let i = 0; i < excess; i++) {
            const [oldKey, oldItem] = activeEntries[i];
            activeToasts.delete(oldKey);
            if (typeof oldItem.dismiss === 'function') {
                oldItem.dismiss(true);
            }
        }
    }

    // 3. Create toast element
    const toast = document.createElement('div');
    toast.className = 'hp-toast';
    toast.style.cssText =
        'display:flex;align-items:center;gap:0.6rem;padding:0.7rem 0.95rem;border-radius:0.75rem;' +
        `background:${colors.bg};color:#fff;font:600 0.85rem/1.45 Poppins,system-ui,sans-serif;` +
        'box-shadow:0 10px 28px rgba(0,0,0,0.28);opacity:0;transform:translateY(10px) scale(0.97);' +
        'transition:opacity .22s cubic-bezier(0.16, 1, 0.3, 1),transform .22s cubic-bezier(0.16, 1, 0.3, 1);' +
        'pointer-events:auto;user-select:none;word-break:break-word;';

    const iconWrap = document.createElement('span');
    iconWrap.style.cssText = 'display:flex;align-items:center;justify-content:center;flex-shrink:0;width:1.4rem;height:1.4rem;';
    iconWrap.innerHTML = toastIcon(normalizedType);

    const msgSpan = document.createElement('span');
    msgSpan.style.cssText = 'min-width:0;flex:1;';
    msgSpan.textContent = cleanMsg;

    const countBadge = document.createElement('span');
    countBadge.style.cssText =
        'display:none;align-items:center;justify-content:center;margin-left:0.45rem;padding:0.1rem 0.45rem;' +
        'border-radius:9999px;background:rgba(255,255,255,0.25);font-size:0.72rem;font-weight:700;line-height:1;';

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.setAttribute('aria-label', 'Dismiss notification');
    closeBtn.style.cssText =
        'flex-shrink:0;cursor:pointer;border:0;background:transparent;color:rgba(255,255,255,0.85);' +
        'font-size:1.15rem;line-height:1;padding:0 0.2rem;margin-left:0.35rem;transition:color 0.15s ease;';
    closeBtn.textContent = '×';
    closeBtn.addEventListener('mouseenter', () => { closeBtn.style.color = '#fff'; });
    closeBtn.addEventListener('mouseleave', () => { closeBtn.style.color = 'rgba(255,255,255,0.85)'; });

    toast.appendChild(iconWrap);
    toast.appendChild(msgSpan);
    toast.appendChild(countBadge);
    toast.appendChild(closeBtn);

    container.appendChild(toast);

    // Force reflow for smooth enter animation
    requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0) scale(1)';
    });

    let timer = null;
    let isDismissing = false;

    const dismiss = (immediate = false) => {
        if (isDismissing) return;
        isDismissing = true;
        activeToasts.delete(key);
        if (timer) clearTimeout(timer);

        if (immediate) {
            toast.remove();
            return;
        }

        toast.style.opacity = '0';
        toast.style.transform = 'translateY(8px) scale(0.95)';
        setTimeout(() => toast.remove(), 220);
    };

    closeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        dismiss();
    });

    // Pause auto-dismiss on hover
    toast.addEventListener('mouseenter', () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
    });

    toast.addEventListener('mouseleave', () => {
        if (!isDismissing) {
            timer = setTimeout(dismiss, 1200);
        }
    });

    timer = setTimeout(dismiss, duration);

    activeToasts.set(key, {
        el: toast,
        timer,
        count: 1,
        countBadge,
        isDismissing,
        dismiss
    });

    return toast;
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

if (typeof window !== 'undefined') {
    window.showToast = showToast;
    window.queueToast = queueToast;
    window.showPendingToast = showPendingToast;
    window.convertFlashToToast = convertFlashToToast;
}
