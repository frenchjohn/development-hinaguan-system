
document.addEventListener('DOMContentLoaded', () => {
    const siteHeader = document.getElementById('amSiteHeader');
    const modal = document.getElementById('infoModal');
    const openButtons = document.querySelectorAll('[data-open-info-modal]');
    const closeButtons = document.querySelectorAll('[data-close-info-modal]');

    if (siteHeader) {
        const sync = () => document.documentElement.style.setProperty('--am-header-offset', `${siteHeader.offsetHeight}px`);
        sync();
        window.addEventListener('resize', sync, { passive: true });
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            const el = entry.target;
            setTimeout(() => el.classList.add('is-visible'), parseInt(el.dataset.delay || '0', 10));
            observer.unobserve(el);
        });
    }, { rootMargin: '0px 0px -6% 0px', threshold: 0.08 });
    document.querySelectorAll('[data-animate]').forEach((el) => observer.observe(el));
    document.querySelectorAll('.am-hero [data-animate]').forEach((el, i) => setTimeout(() => el.classList.add('is-visible'), 200 + i * 120));

    const lockScroll = (on) => { document.body.style.overflow = on ? 'hidden' : ''; };

    const openModal = (btn) => {
        document.getElementById('infoModalTitle').textContent = btn.dataset.name;
        document.getElementById('infoModalDescription').textContent = btn.dataset.description;
        document.getElementById('infoModalCapacity').textContent = btn.dataset.capacity;
        document.getElementById('infoModalDayPrice').textContent = btn.dataset.dayPrice;
        document.getElementById('infoModalNightPrice').textContent = btn.dataset.nightPrice;
        const img = document.getElementById('infoModalImage');
        if (btn.dataset.image) {
            img.style.backgroundImage = `url('${btn.dataset.image}')`;
            img.hidden = false;
        } else {
            img.hidden = true;
            img.style.backgroundImage = '';
        }
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        lockScroll(true);
    };

    const closeModal = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        lockScroll(false);
    };

    openButtons.forEach((btn) => btn.addEventListener('click', () => openModal(btn)));
    closeButtons.forEach((btn) => btn.addEventListener('click', closeModal));
    modal?.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
});
