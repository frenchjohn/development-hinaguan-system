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

    const chargeCategories = [
        { type: 'damage', label: 'Damage', hint: 'Property, furniture, fixtures', placeholder: 'e.g. Broken chair, damaged lamp' },
        { type: 'cleaning', label: 'Cleaning', hint: 'Excess trash, stains, sanitizing', placeholder: 'e.g. Heavy stain cleanup, left waste' },
        { type: 'lost', label: 'Lost Item', hint: 'Keys, towels, rented gear', placeholder: 'e.g. Missing key, lost pool towel' },
        { type: 'others', label: 'Others', hint: 'Late checkout, noise, incidental', placeholder: 'e.g. Late checkout fee, noise penalty' },
    ];

    const categoryRowsHtml = chargeCategories.map((cat) => `
        <div class="charge-corner-card grid grid-cols-1 gap-3 border border-slate-200 bg-white p-3.5 transition-colors sm:grid-cols-12 sm:items-center dark:border-white/10 dark:bg-white/[0.03]" data-card-type="${cat.type}">
            <div class="sm:col-span-4">
                <div class="flex items-center gap-2">
                    <h4 class="m-0 text-sm font-bold text-slate-900 dark:text-white">${cat.label}</h4>
                    <span class="active-pill hidden rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">Active</span>
                </div>
                <span class="block text-xs text-slate-500 dark:text-slate-400">${cat.hint}</span>
            </div>
            <div class="sm:col-span-3">
                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-500 sm:hidden dark:text-slate-400">Amount (₱)</label>
                <div class="relative flex items-center">
                    <span class="pointer-events-none absolute left-3 text-xs font-bold text-slate-400">₱</span>
                    <input type="number" step="0.01" min="0" data-charge-type="${cat.type}"
                        class="charge-amount-input h-9 w-full rounded-lg border border-slate-300 bg-white pl-7 pr-2.5 font-mono text-sm font-bold text-slate-900 transition-colors focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/15 dark:border-white/15 dark:bg-white/5 dark:text-white"
                        placeholder="0.00">
                </div>
            </div>
            <div class="sm:col-span-5">
                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-500 sm:hidden dark:text-slate-400">Details (optional)</label>
                <input type="text" data-charge-desc="${cat.type}"
                    class="charge-desc-input h-9 w-full rounded-lg border border-slate-300 bg-white px-3 text-xs text-slate-900 placeholder:text-slate-400 transition-colors focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/15 dark:border-white/15 dark:bg-white/5 dark:text-white"
                    placeholder="${cat.placeholder}">
            </div>
        </div>
    `).join('');

    modal.innerHTML = `
        <div class="guest-modal__backdrop absolute inset-0 bg-black/55 dark:bg-black/75" data-charge-close="true"></div>
        <div class="guest-modal__content relative z-[1] flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-white/10 dark:bg-[#181b19]" role="dialog" aria-modal="true" aria-labelledby="reservationChargesModalTitle" style="width: min(94vw, 760px) !important; max-width: 760px !important; max-height: 92vh !important; padding: 0 !important; display: flex !important; flex-direction: column !important;">
            
            <!-- HEADER -->
            <div class="flex shrink-0 items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-white/10">
                <div>
                    <h3 id="reservationChargesModalTitle" class="m-0 text-base font-bold text-slate-900 dark:text-white">Additional Charges</h3>
                    <p class="m-0 mt-0.5 text-xs text-slate-500 dark:text-slate-400">Declare any incidental fees or penalties before checkout.</p>
                </div>
                <button type="button" class="cursor-pointer rounded-md border-0 bg-transparent p-1 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-white/10 dark:hover:text-slate-200" data-charge-close="true" aria-label="Close charges">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <!-- BODY -->
            <div class="flex-1 space-y-4 overflow-y-auto p-5 [scrollbar-width:thin]" style="min-height: 0;">
                <div class="hidden grid-cols-12 gap-3 px-3.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500 sm:grid dark:text-slate-400">
                    <div class="col-span-4">Category</div>
                    <div class="col-span-3">Amount (₱)</div>
                    <div class="col-span-5">Details (Optional)</div>
                </div>

                <div class="overflow-hidden rounded-lg border border-slate-200 divide-y divide-slate-200 dark:divide-white/10 dark:border-white/10 [&>.charge-corner-card]:border-0">
                    ${categoryRowsHtml}
                </div>

                <!-- TOTAL SUMMARY -->
                <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                    <div>
                        <span class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">Total Additional Charges</span>
                        <p class="m-0 mt-0.5 text-xs text-slate-500 dark:text-slate-400" id="chargeSummarySubtext">No additional charges declared</p>
                    </div>
                    <strong id="chargeTotalDisplay" class="text-xl font-bold tabular-nums text-emerald-800 dark:text-emerald-400">₱0.00</strong>
                </div>
            </div>
            
            <!-- FOOTER ACTIONS -->
            <div class="flex shrink-0 items-center justify-end gap-2.5 border-t border-slate-100 bg-slate-50/70 px-5 py-3.5 dark:border-white/10 dark:bg-white/[0.02]" id="chargeModalActions">
                <button type="button" data-charge-close="true" class="cursor-pointer rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-50 dark:border-white/15 dark:bg-transparent dark:text-slate-300 dark:hover:bg-white/5">Cancel</button>
                <button type="button" id="proceedCheckout" class="cursor-pointer rounded-lg border-0 bg-[#155e3c] px-5 py-2 text-xs font-semibold text-white transition-colors hover:bg-[#114b30]">Checkout</button>
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

                card?.classList.add('bg-emerald-50/40', 'dark:bg-emerald-950/20');
                card?.classList.remove('bg-white', 'dark:bg-white/[0.03]');
                if (pill) pill.classList.remove('hidden');
            } else {
                card?.classList.remove('bg-emerald-50/40', 'dark:bg-emerald-950/20');
                card?.classList.add('bg-white', 'dark:bg-white/[0.03]');
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
