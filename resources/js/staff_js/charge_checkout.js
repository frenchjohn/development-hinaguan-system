const getCsrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

const showLoadingScreen = (message = 'Processing checkout...') => {
    const loadingId = 'checkoutLoadingOverlay';
    let loading = document.getElementById(loadingId);
    if (!loading) {
        loading = document.createElement('div');
        loading.id = loadingId;
        loading.className = 'fixed inset-0 z-[4000] flex items-center justify-center backdrop-blur-sm';
        loading.style.backgroundColor = 'rgba(0,0,0,0.5)';
        loading.innerHTML = `
            <style>@keyframes checkout-progress { 0% { transform: translateX(-120%); } 50% { transform: translateX(100%); } 100% { transform: translateX(360%); } }</style>
            <div class="relative w-full max-w-sm rounded-2xl bg-white px-7 py-6 shadow-2xl dark:bg-[#1d241f]">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300" aria-hidden="true">
                        <i class="bi bi-receipt-cutoff text-xl"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-lg font-bold text-gray-900 dark:text-white">${message}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Please wait while we process your checkout</p>
                    </div>
                </div>
                <div class="mt-6 h-2 overflow-hidden rounded-full bg-emerald-100 dark:bg-emerald-950/70" aria-hidden="true">
                    <div class="h-full w-1/3 animate-[checkout-progress_1.4s_ease-in-out_infinite] rounded-full bg-emerald-600 dark:bg-emerald-400"></div>
                </div>
                <div class="mt-3 flex items-center gap-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                    <span>Saving checkout details</span>
                    <span class="inline-flex gap-0.5" aria-hidden="true"><span class="animate-pulse">.</span><span class="animate-pulse [animation-delay:150ms]">.</span><span class="animate-pulse [animation-delay:300ms]">.</span></span>
                </div>
            </div>`;
        document.body.appendChild(loading);
    }
    return loadingId;
};

const hideLoadingScreen = () => {
    const loading = document.getElementById('checkoutLoadingOverlay');
    if (loading) loading.remove();
};

const showConfirmModal = (title, message) => {
    return new Promise((resolve) => {
        const confirmId = 'chargeConfirmModal_' + Math.random().toString(36).substr(2, 9);
        const confirmModal = document.createElement('div');
        confirmModal.id = confirmId;
        confirmModal.className = 'fixed inset-0 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4';
        confirmModal.setAttribute('style', 'z-index: 3500 !important;');
        confirmModal.innerHTML = `
            <div class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl bg-hp-cream p-6 shadow-2xl dark:bg-[#1a201c] border border-glass-border">
                <h3 class="m-0 font-display text-lg font-bold text-hp-text dark:text-[#f3f4f6]">${title}</h3>
                <p class="mt-3 text-xs leading-relaxed text-hp-text-muted">${message}</p>
                <div class="mt-6 flex gap-3 justify-end">
                    <button type="button" class="cancel-btn guest-form__button--secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2 text-xs font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong">Cancel</button>
                    <button type="button" class="confirm-btn guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2 text-xs font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark">Yes, Proceed</button>
                </div>
            </div>`;
        document.body.appendChild(confirmModal);
        confirmModal.querySelector('.cancel-btn').onclick = () => {
            confirmModal.remove();
            resolve(false);
        };
        confirmModal.querySelector('.confirm-btn').onclick = () => {
            confirmModal.remove();
            resolve(true);
        };
    });
};

export const openChargeCheckout = async (reservationId, onPaid, onReady) => {
    const modalId = 'reservationChargesModal';
    let modal = document.getElementById(modalId);
    
    if (!modal) {
        modal = document.createElement('div');
        modal.id = modalId;
        modal.className = 'guest-modal fixed inset-0 items-center justify-center';
        modal.setAttribute('aria-hidden', 'true');
        modal.setAttribute('style', 'z-index: 2500 !important; display: none;');
        document.body.appendChild(modal);
    } else {
        modal.setAttribute('style', 'z-index: 2500 !important;');
        document.body.appendChild(modal);
    }

    modal.innerHTML = `
        <div class="guest-modal__backdrop absolute inset-0 bg-black/60 dark:bg-black/80 backdrop-blur-sm" data-charge-close="true"></div>
        <div class="guest-modal__content relative z-[1] flex flex-col overflow-hidden rounded-2xl sm:rounded-3xl bg-hp-cream dark:bg-[#181c19] shadow-2xl border border-glass-border dark:border-white/10" role="dialog" aria-modal="true" aria-labelledby="reservationChargesModalTitle" style="width: min(94vw, 760px) !important; max-width: 760px !important; max-height: 92vh !important; padding: 0 !important; display: flex !important; flex-direction: column !important;">
            
            <!-- HEADER -->
            <div class="p-4 sm:p-5 pb-3 sm:pb-4 flex items-center justify-between border-b border-[rgba(13,44,29,0.1)] dark:border-white/10 shrink-0 bg-white/40 dark:bg-white/5">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-amber-500/15 text-amber-700 dark:text-amber-400 flex items-center justify-center font-bold text-base shrink-0">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 id="reservationChargesModalTitle" class="m-0 font-display text-base sm:text-lg font-bold text-hp-text dark:text-[#f3f4f6]">Additional Charges</h3>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/20">Checkout Review</span>
                        </div>
                        <p class="text-[11px] sm:text-xs text-hp-text-muted m-0 mt-0.5">Declare any incidental fees or penalties incurred by the guest before checkout.</p>
                    </div>
                </div>
                <button type="button" class="cursor-pointer border-0 bg-transparent text-2xl text-hp-text-muted hover:text-red-500 transition-colors p-1 leading-none rounded-lg" data-charge-close="true" aria-label="Close charges">&times;</button>
            </div>
            
            <!-- BODY: 4 CORNERS / 2 PER ROW -->
            <div class="p-4 sm:p-6 overflow-y-auto flex-1 space-y-4 [scrollbar-width:thin]" style="min-height: 0;">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4">
                    
                    <!-- 1. DAMAGE -->
                    <div class="charge-corner-card relative flex flex-col justify-between rounded-2xl border-2 border-glass-border dark:border-white/10 bg-white/70 dark:bg-white/5 p-3.5 sm:p-4 transition-all duration-200 hover:shadow-sm" data-card-type="damage">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-amber-500/15 text-amber-700 dark:text-amber-400 flex items-center justify-center text-xs font-bold">
                                        <i class="bi bi-hammer"></i>
                                    </span>
                                    <div>
                                        <h4 class="m-0 text-xs sm:text-sm font-bold text-hp-text dark:text-[#f3f4f6]">Damage</h4>
                                        <span class="text-[10px] text-hp-text-muted block">Property, furniture, fixtures</span>
                                    </div>
                                </div>
                                <span class="active-pill hidden px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-500/20 text-amber-800 dark:text-amber-300">Active</span>
                            </div>

                            <div class="mt-2.5 space-y-2">
                                <div>
                                    <label class="block text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-hp-text dark:text-[#e5e7eb] mb-1">
                                        Amount (₱)
                                    </label>
                                    <div class="relative flex items-center">
                                        <span class="absolute left-3 text-xs font-bold text-hp-text-muted pointer-events-none">₱</span>
                                        <input type="number" step="0.01" min="0" data-charge-type="damage" class="charge-amount-input w-full pl-7 pr-3 py-2 text-xs sm:text-sm font-mono font-bold rounded-xl border border-glass-border bg-white dark:bg-[#1a201c] text-hp-text dark:text-[#f3f4f6] focus:border-amber-500 focus:outline-none transition-colors" placeholder="0.00">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] sm:text-[11px] font-medium text-hp-text-muted mb-1">
                                        Description / Details <span class="text-[9px] opacity-70">(optional)</span>
                                    </label>
                                    <input type="text" data-charge-desc="damage" class="charge-desc-input w-full px-3 py-1.5 text-xs rounded-xl border border-glass-border bg-white dark:bg-[#1a201c] text-hp-text dark:text-[#f3f4f6] focus:border-amber-500 focus:outline-none placeholder:text-hp-text-muted/60 transition-colors" placeholder="e.g. Broken chair, damaged lamp">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. CLEANING -->
                    <div class="charge-corner-card relative flex flex-col justify-between rounded-2xl border-2 border-glass-border dark:border-white/10 bg-white/70 dark:bg-white/5 p-3.5 sm:p-4 transition-all duration-200 hover:shadow-sm" data-card-type="cleaning">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-sky-500/15 text-sky-700 dark:text-sky-400 flex items-center justify-center text-xs font-bold">
                                        <i class="bi bi-stars"></i>
                                    </span>
                                    <div>
                                        <h4 class="m-0 text-xs sm:text-sm font-bold text-hp-text dark:text-[#f3f4f6]">Cleaning</h4>
                                        <span class="text-[10px] text-hp-text-muted block">Excess trash, stains, sanitizing</span>
                                    </div>
                                </div>
                                <span class="active-pill hidden px-2 py-0.5 rounded-full text-[9px] font-bold bg-sky-500/20 text-sky-800 dark:text-sky-300">Active</span>
                            </div>

                            <div class="mt-2.5 space-y-2">
                                <div>
                                    <label class="block text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-hp-text dark:text-[#e5e7eb] mb-1">
                                        Amount (₱)
                                    </label>
                                    <div class="relative flex items-center">
                                        <span class="absolute left-3 text-xs font-bold text-hp-text-muted pointer-events-none">₱</span>
                                        <input type="number" step="0.01" min="0" data-charge-type="cleaning" class="charge-amount-input w-full pl-7 pr-3 py-2 text-xs sm:text-sm font-mono font-bold rounded-xl border border-glass-border bg-white dark:bg-[#1a201c] text-hp-text dark:text-[#f3f4f6] focus:border-sky-500 focus:outline-none transition-colors" placeholder="0.00">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] sm:text-[11px] font-medium text-hp-text-muted mb-1">
                                        Description / Details <span class="text-[9px] opacity-70">(optional)</span>
                                    </label>
                                    <input type="text" data-charge-desc="cleaning" class="charge-desc-input w-full px-3 py-1.5 text-xs rounded-xl border border-glass-border bg-white dark:bg-[#1a201c] text-hp-text dark:text-[#f3f4f6] focus:border-sky-500 focus:outline-none placeholder:text-hp-text-muted/60 transition-colors" placeholder="e.g. Heavy stain cleanup, left waste">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. LOST ITEM -->
                    <div class="charge-corner-card relative flex flex-col justify-between rounded-2xl border-2 border-glass-border dark:border-white/10 bg-white/70 dark:bg-white/5 p-3.5 sm:p-4 transition-all duration-200 hover:shadow-sm" data-card-type="lost">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-rose-500/15 text-rose-700 dark:text-rose-400 flex items-center justify-center text-xs font-bold">
                                        <i class="bi bi-tag"></i>
                                    </span>
                                    <div>
                                        <h4 class="m-0 text-xs sm:text-sm font-bold text-hp-text dark:text-[#f3f4f6]">Lost Item</h4>
                                        <span class="text-[10px] text-hp-text-muted block">Keys, towels, rented gear</span>
                                    </div>
                                </div>
                                <span class="active-pill hidden px-2 py-0.5 rounded-full text-[9px] font-bold bg-rose-500/20 text-rose-800 dark:text-rose-300">Active</span>
                            </div>

                            <div class="mt-2.5 space-y-2">
                                <div>
                                    <label class="block text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-hp-text dark:text-[#e5e7eb] mb-1">
                                        Amount (₱)
                                    </label>
                                    <div class="relative flex items-center">
                                        <span class="absolute left-3 text-xs font-bold text-hp-text-muted pointer-events-none">₱</span>
                                        <input type="number" step="0.01" min="0" data-charge-type="lost" class="charge-amount-input w-full pl-7 pr-3 py-2 text-xs sm:text-sm font-mono font-bold rounded-xl border border-glass-border bg-white dark:bg-[#1a201c] text-hp-text dark:text-[#f3f4f6] focus:border-rose-500 focus:outline-none transition-colors" placeholder="0.00">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] sm:text-[11px] font-medium text-hp-text-muted mb-1">
                                        Description / Details <span class="text-[9px] opacity-70">(optional)</span>
                                    </label>
                                    <input type="text" data-charge-desc="lost" class="charge-desc-input w-full px-3 py-1.5 text-xs rounded-xl border border-glass-border bg-white dark:bg-[#1a201c] text-hp-text dark:text-[#f3f4f6] focus:border-rose-500 focus:outline-none placeholder:text-hp-text-muted/60 transition-colors" placeholder="e.g. Missing key, lost pool towel">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. OTHERS -->
                    <div class="charge-corner-card relative flex flex-col justify-between rounded-2xl border-2 border-glass-border dark:border-white/10 bg-white/70 dark:bg-white/5 p-3.5 sm:p-4 transition-all duration-200 hover:shadow-sm" data-card-type="others">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-purple-500/15 text-purple-700 dark:text-purple-400 flex items-center justify-center text-xs font-bold">
                                        <i class="bi bi-three-dots"></i>
                                    </span>
                                    <div>
                                        <h4 class="m-0 text-xs sm:text-sm font-bold text-hp-text dark:text-[#f3f4f6]">Others</h4>
                                        <span class="text-[10px] text-hp-text-muted block">Late checkout, noise, incidental</span>
                                    </div>
                                </div>
                                <span class="active-pill hidden px-2 py-0.5 rounded-full text-[9px] font-bold bg-purple-500/20 text-purple-800 dark:text-purple-300">Active</span>
                            </div>

                            <div class="mt-2.5 space-y-2">
                                <div>
                                    <label class="block text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-hp-text dark:text-[#e5e7eb] mb-1">
                                        Amount (₱)
                                    </label>
                                    <div class="relative flex items-center">
                                        <span class="absolute left-3 text-xs font-bold text-hp-text-muted pointer-events-none">₱</span>
                                        <input type="number" step="0.01" min="0" data-charge-type="others" class="charge-amount-input w-full pl-7 pr-3 py-2 text-xs sm:text-sm font-mono font-bold rounded-xl border border-glass-border bg-white dark:bg-[#1a201c] text-hp-text dark:text-[#f3f4f6] focus:border-purple-500 focus:outline-none transition-colors" placeholder="0.00">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] sm:text-[11px] font-medium text-hp-text-muted mb-1">
                                        Description / Details <span class="text-[9px] opacity-70">(optional)</span>
                                    </label>
                                    <input type="text" data-charge-desc="others" class="charge-desc-input w-full px-3 py-1.5 text-xs rounded-xl border border-glass-border bg-white dark:bg-[#1a201c] text-hp-text dark:text-[#f3f4f6] focus:border-purple-500 focus:outline-none placeholder:text-hp-text-muted/60 transition-colors" placeholder="e.g. Late checkout fee, noise penalty">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- TOTAL SUMMARY STRIP -->
                <div class="rounded-2xl border border-emerald-500/25 bg-emerald-500/10 dark:bg-emerald-950/30 p-3.5 sm:p-4 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-hp-text dark:text-[#f3f4f6] block">Total Additional Charges</span>
                        <p class="m-0 text-[11px] text-hp-text-muted" id="chargeSummarySubtext">No additional charges declared</p>
                    </div>
                    <div class="text-right">
                        <strong id="chargeTotalDisplay" class="text-xl sm:text-2xl font-extrabold text-emerald-700 dark:text-emerald-400 font-display tabular-nums">₱0.00</strong>
                    </div>
                </div>

            </div>
            
            <!-- FOOTER ACTIONS -->
            <div class="p-3.5 sm:p-4 px-4 sm:px-6 border-t border-[rgba(13,44,29,0.1)] dark:border-white/10 bg-hp-cream/90 dark:bg-[#1a201c] backdrop-blur-md shrink-0 flex items-center justify-end gap-3" id="chargeModalActions">
                <button type="button" data-charge-close="true" class="cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2 text-xs font-semibold text-hp-text hover:bg-glass-hover transition-all">Cancel</button>
                <button type="button" id="proceedCheckout" class="cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2 text-xs sm:text-sm font-bold text-white transition-all hover:bg-hp-green-dark shadow-xs">Checkout</button>
            </div>
        </div>`;

    const close = () => { 
        modal.classList.remove('is-open');
        modal.classList.add('hidden'); 
        modal.classList.remove('flex'); 
        modal.setAttribute('aria-hidden', 'true');
        modal.style.display = 'none';
        modal.style.opacity = '0';
        modal.style.visibility = 'hidden';
        modal.style.pointerEvents = 'none';
    };
    modal.querySelectorAll('[data-charge-close]').forEach((button) => button.onclick = close);

    const amountInputs = modal.querySelectorAll('.charge-amount-input');
    const descInputs = modal.querySelectorAll('.charge-desc-input');
    const totalDisplay = modal.querySelector('#chargeTotalDisplay');
    const subtext = modal.querySelector('#chargeSummarySubtext');
    const proceedBtn = modal.querySelector('#proceedCheckout');

    const computeTotals = () => {
        let total = 0;
        let activeTypes = [];

        amountInputs.forEach((input) => {
            const val = parseFloat(input.value || 0);
            const type = input.dataset.chargeType;
            const card = modal.querySelector(`.charge-corner-card[data-card-type="${type}"]`);
            const pill = card?.querySelector('.active-pill');

            if (val > 0) {
                total += val;
                const formattedType = type === 'damage' ? 'Damage' : (type === 'cleaning' ? 'Cleaning' : (type === 'lost' ? 'Lost Item' : 'Others'));
                activeTypes.push(`${formattedType} (₱${val.toFixed(2)})`);

                card?.classList.add('border-emerald-600', 'shadow-xs', 'bg-emerald-50/20');
                card?.classList.remove('border-glass-border', 'dark:border-white/10');
                if (pill) pill.classList.remove('hidden');
            } else {
                card?.classList.remove('border-emerald-600', 'shadow-xs', 'bg-emerald-50/20');
                card?.classList.add('border-glass-border', 'dark:border-white/10');
                if (pill) pill.classList.add('hidden');
            }
        });

        total = Math.round(total * 100) / 100;
        totalDisplay.textContent = `₱${total.toFixed(2)}`;

        if (total > 0) {
            subtext.textContent = `${activeTypes.length} charge${activeTypes.length > 1 ? 's' : ''}: ${activeTypes.join(', ')}`;
            proceedBtn.textContent = `Pay & Checkout (₱${total.toFixed(2)})`;
            proceedBtn.classList.remove('bg-hp-green');
            proceedBtn.classList.add('bg-emerald-700');
        } else {
            subtext.textContent = 'No additional charges declared';
            proceedBtn.textContent = 'Checkout';
            proceedBtn.classList.add('bg-hp-green');
            proceedBtn.classList.remove('bg-emerald-700');
        }

        return total;
    };

    // Listen to real-time input changes on all amount inputs
    amountInputs.forEach(input => {
        input.addEventListener('input', computeTotals);
    });

    // Load any existing charges from database for this reservation
    try {
        const response = await fetch(`/staff/reservations/${reservationId}/charges`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (response.ok) {
            const payload = await response.json();
            const existingCharges = payload.charges || [];

            existingCharges.forEach((c) => {
                if (c.status === 'unpaid' || c.status === 'paid') {
                    const type = c.charge_type;
                    const amountInput = modal.querySelector(`[data-charge-type="${type}"]`);
                    const descInput = modal.querySelector(`[data-charge-desc="${type}"]`);
                    if (amountInput && (parseFloat(amountInput.value || 0) === 0)) {
                        amountInput.value = parseFloat(c.amount || 0).toFixed(2);
                        if (descInput && c.description) {
                            descInput.value = c.description;
                        }
                    }
                }
            });
            computeTotals();
        }
    } catch (e) {
        console.warn('Failed to pre-load reservation charges:', e);
    }

    // Open modal with top stacking
    document.body.appendChild(modal);
    modal.classList.add('is-open');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
    modal.style.display = 'flex';
    modal.style.opacity = '1';
    modal.style.visibility = 'visible';
    modal.style.pointerEvents = 'auto';
    modal.style.zIndex = '2500';
    onReady?.();

    proceedBtn.onclick = async () => {
        const total = computeTotals();
        const chargesToSubmit = [];

        ['damage', 'cleaning', 'lost', 'others'].forEach(type => {
            const amountEl = modal.querySelector(`[data-charge-type="${type}"]`);
            const descEl = modal.querySelector(`[data-charge-desc="${type}"]`);
            const amt = parseFloat(amountEl?.value || 0);
            if (amt > 0) {
                chargesToSubmit.push({
                    charge_type: type,
                    amount: amt,
                    description: descEl?.value?.trim() || null,
                });
            }
        });

        const hasCharges = total > 0 && chargesToSubmit.length > 0;
        const confirmMessage = hasCharges
            ? `Total additional charges to collect: <strong class="text-emerald-700 dark:text-emerald-400">₱${total.toFixed(2)}</strong><br><br>Has this payment been collected and received from the guest?`
            : `No additional charges have been declared.<br><br>Are you sure you want to proceed with guest checkout?`;

        const confirmed = await showConfirmModal(
            hasCharges ? 'Confirm Payment & Checkout' : 'Confirm Checkout',
            confirmMessage
        );

        if (!confirmed) return;

        close();
        showLoadingScreen('Processing checkout...');

        try {
            // Save each charge directly to database
            for (const charge of chargesToSubmit) {
                const chargeResponse = await fetch(`/staff/reservations/${reservationId}/charges`, {
                    method: 'POST',
                    headers: { 
                        Accept: 'application/json', 
                        'X-CSRF-TOKEN': getCsrf(), 
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        charge_type: charge.charge_type,
                        amount: charge.amount,
                        description: charge.description
                    })
                });

                const chargeResult = await chargeResponse.json().catch(() => ({}));
                if (!chargeResponse.ok) {
                    throw new Error(chargeResult.message || 'Failed to record additional charge.');
                }
            }

            // Settle / mark all charges as paid
            if (hasCharges) {
                const payResponse = await fetch(`/staff/reservations/${reservationId}/charges/pay`, {
                    method: 'POST',
                    headers: { 
                        Accept: 'application/json', 
                        'X-CSRF-TOKEN': getCsrf(), 
                        'X-Requested-With': 'XMLHttpRequest' 
                    }
                });

                const payResult = await payResponse.json().catch(() => ({}));
                if (!payResponse.ok) {
                    throw new Error(payResult.message || 'Failed to process charge payment.');
                }
            }

            // Trigger caller's completion callback (finishes checkout process)
            await onPaid?.();
        } catch (error) {
            hideLoadingScreen();
            console.error('Error during checkout charges:', error);
            window.alert(error.message || 'An error occurred while saving charges.');
        } finally {
            hideLoadingScreen();
        }
    };
};
