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
    
    // Track charges in memory only (not persisted to DB yet)
    let tempCharges = [];
    let tempAmenities = [];
    
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
        <div class="guest-modal__content relative z-[1] flex flex-col overflow-hidden rounded-2xl bg-glass shadow-glass dark:bg-[rgba(30,30,30,0.95)]" role="dialog" aria-modal="true" aria-labelledby="reservationChargesModalTitle" style="width: min(94vw, 780px) !important; max-width: 780px !important; height: min(92vh, 880px) !important; min-height: min(86vh, 760px) !important; max-height: 94vh !important; padding: 0 !important; display: flex !important; flex-direction: column !important;">
            <button type="button" class="guest-modal__close absolute right-4 top-4 cursor-pointer border-0 bg-transparent text-2xl text-hp-text z-10 hover:text-red-500 transition-colors" data-charge-close="true" aria-label="Close charges">&times;</button>
            <div class="guest-modal__header p-5 pb-3 flex items-center justify-between border-b border-[rgba(13,44,29,0.1)] dark:border-white/10 shrink-0">
                <div class="flex items-center gap-2.5">
                    <h3 id="reservationChargesModalTitle" class="guest-modal__title m-0 font-display text-xl text-hp-text">Additional Charges</h3>
                    <span class="guest-modal__role-badge inline-flex items-center rounded-full px-3 py-0.5 text-xs font-bold uppercase tracking-[0.04em] bg-amber-500/15 text-amber-700 dark:text-amber-400">Checkout Review</span>
                </div>
            </div>
            
            <div class="guest-modal__body p-6 overflow-y-auto flex-1 space-y-4" style="display: flex !important; flex-direction: column !important; flex: 1 1 0% !important; min-height: 0 !important; overflow-y: auto !important; padding: 1.5rem 1.75rem !important;">
                <p class="text-xs text-hp-text-muted m-0 shrink-0">Review any damage, cleaning, lost-item, or other charges before checkout.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 flex-1 min-h-0">
                    <!-- LEFT SIDE: Charge Maker Form -->
                    <div class="flex flex-col">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-hp-text mb-2.5">Add Charge</h4>
                        <form id="reservationChargeForm" class="grid gap-3 rounded-xl border border-glass-border bg-glass/60 p-4 dark:border-white/10 dark:bg-white/5">
                            <label class="grid gap-1 text-xs font-semibold text-hp-text dark:text-gray-200">
                                Amenity
                                <select name="amenity_id" id="chargeAmenity" class="rounded-lg border border-glass-border bg-glass px-3 py-2 text-xs text-hp-text dark:border-white/10 dark:bg-white/10"></select>
                            </label>
                            <label class="grid gap-1 text-xs font-semibold text-hp-text dark:text-gray-200">
                                Charge type
                                <select name="charge_type" class="rounded-lg border border-glass-border bg-glass px-3 py-2 text-xs text-hp-text dark:border-white/10 dark:bg-white/10">
                                    <option value="damage">Damage</option>
                                    <option value="cleaning">Cleaning</option>
                                    <option value="lost">Lost item</option>
                                    <option value="others">Others</option>
                                </select>
                            </label>
                            <label class="grid gap-1 text-xs font-semibold text-hp-text dark:text-gray-200">
                                Description <span class="text-hp-text-muted">(optional)</span>
                                <textarea name="description" rows="2" class="rounded-lg border border-glass-border bg-glass px-3 py-2 text-xs text-hp-text dark:border-white/10 dark:bg-white/10 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none" placeholder="Describe the charge (optional)"></textarea>
                            </label>
                            <label class="grid gap-1 text-xs font-semibold text-hp-text dark:text-gray-200">
                                Amount
                                <input name="amount" type="number" min="0.01" step="0.01" required class="rounded-lg border border-glass-border bg-glass px-3 py-2 text-xs text-hp-text dark:border-white/10 dark:bg-white/10 placeholder:text-hp-text-muted/60 focus:border-hp-green focus:outline-none" placeholder="0.00">
                            </label>
                            <button type="submit" class="w-full rounded-xl border-0 bg-hp-green px-4 py-2 text-xs font-bold text-white transition-colors duration-200 hover:bg-hp-green-dark cursor-pointer shadow-xs">Add Charge</button>
                        </form>
                    </div>
                    
                    <!-- RIGHT SIDE: Charges List & Total -->
                    <div class="flex flex-col min-h-0">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-hp-text mb-2.5">Charges List</h4>
                        <div id="reservationChargesList" class="grid gap-2 rounded-xl border border-glass-border bg-glass/60 p-3 dark:border-white/10 dark:bg-white/5 mb-3 flex-1 min-h-[160px] max-h-[340px] overflow-y-auto"></div>
                        <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-3.5 mt-auto">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-hp-text dark:text-white">Total Charges</span>
                                <strong id="reservationChargesTotal" class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400">₱0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- FOOTER -->
            <div class="guest-form__actions p-4 px-6 border-t border-[rgba(13,44,29,0.1)] dark:border-white/10 bg-hp-cream/90 dark:bg-[#1a201c] backdrop-blur-md shrink-0 flex items-center justify-end gap-3" id="chargeModalActions">
                <button type="button" data-charge-close="true" class="guest-form__button--secondary cursor-pointer rounded-xl border border-glass-border bg-glass px-4 py-2 text-xs font-semibold text-hp-text transition-all duration-200 hover:bg-glass-hover hover:border-glass-border-strong">Cancel</button>
                <button type="button" id="proceedCheckout" class="guest-form__button cursor-pointer rounded-xl border-0 bg-hp-green px-5 py-2 text-xs font-semibold text-white transition-colors duration-200 hover:bg-hp-green-dark">Checkout</button>
            </div>
        </div>`;

    const form = modal.querySelector('#reservationChargeForm');
    const list = modal.querySelector('#reservationChargesList');
    const total = modal.querySelector('#reservationChargesTotal');
    const amenity = modal.querySelector('#chargeAmenity');
    const proceedBtn = modal.querySelector('#proceedCheckout');
    
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

    const render = () => {
        const formAmount = parseFloat(form.querySelector('[name="amount"]')?.value || 0);
        const unpaidTotal = tempCharges.reduce((sum, c) => sum + Number(c.amount || 0), 0);
        const combinedTotal = unpaidTotal + (formAmount > 0 ? formAmount : 0);
        
        list.innerHTML = tempCharges.length ? tempCharges.map((charge, idx) => `
            <div class="flex items-start justify-between gap-3 rounded-lg border border-glass-border bg-white/70 dark:bg-white/5 p-2.5 text-xs shadow-2xs">
                <div>
                    <strong class="text-hp-text dark:text-white uppercase font-bold text-[0.72rem]">${charge.charge_type.toUpperCase()}</strong>
                    ${charge.amenity_name ? `<p class="m-0 text-hp-text-muted text-[0.68rem]">${charge.amenity_name}</p>` : ''}
                    ${charge.description ? `<p class="m-0 text-hp-text-muted text-[0.68rem] italic line-clamp-1">${charge.description}</p>` : ''}
                </div>
                <div class="flex gap-2 items-center shrink-0">
                    <strong class="text-hp-green dark:text-emerald-400 text-xs font-bold">₱${Number(charge.amount).toFixed(2)}</strong>
                    <button type="button" data-idx="${idx}" class="delete-charge-btn text-red-500 hover:text-red-700 font-bold text-base leading-none cursor-pointer border-0 bg-transparent p-0" title="Remove charge">×</button>
                </div>
            </div>
        `).join('') : '<p class="text-xs text-hp-text-muted text-center py-6">No charges added yet.</p>';
        
        total.textContent = `₱${unpaidTotal.toFixed(2)}`;
        
        // Attach delete handlers - no confirmation needed
        list.querySelectorAll('.delete-charge-btn').forEach(btn => {
            btn.onclick = (e) => {
                e.preventDefault();
                const idx = parseInt(btn.dataset.idx);
                tempCharges.splice(idx, 1);
                render();
            };
        });
        
        // Update button text based on charges
        if (combinedTotal > 0) {
            proceedBtn.textContent = `Pay & Checkout (₱${combinedTotal.toFixed(2)})`;
            proceedBtn.dataset.hasCharges = 'true';
        } else {
            proceedBtn.textContent = 'Checkout';
            proceedBtn.dataset.hasCharges = 'false';
        }
    };

    // Load amenities
    const response = await fetch(`/staff/reservations/${reservationId}/charges`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    const payload = await response.json();
    if (!response.ok) throw new Error(payload.message || 'Unable to load data.');
    tempAmenities = payload.amenities || [];
    
    // Initialize amenity dropdown
    amenity.innerHTML = '<option value="">No amenity</option>' + tempAmenities.map((item) => `<option value="${item.id}">${item.name}</option>`).join('');
    render();

    // Listen to form input changes to update Pay & Checkout button live
    const amountInput = form.querySelector('[name="amount"]');
    amountInput?.addEventListener('input', () => {
        render();
    });
    
    // Open modal with top stacking
    document.body.appendChild(modal); // Ensure last in DOM
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

    form.onsubmit = async (event) => {
        event.preventDefault();
        const formData = new FormData(form);
        const amenityId = formData.get('amenity_id');
        const parsedAmount = parseFloat(formData.get('amount') || 0);
        if (!parsedAmount || parsedAmount <= 0) return;
        
        tempCharges.push({
            amenity_id: amenityId && amenityId !== '' ? amenityId : null,
            amenity_name: amenityId ? tempAmenities.find(a => a.id == amenityId)?.name : null,
            charge_type: formData.get('charge_type') || 'damage',
            description: formData.get('description') || '',
            amount: parsedAmount,
        });
        
        form.reset();
        render();
    };

    proceedBtn.onclick = async () => {
        // Automatically add any charge that the user typed into the form but didn't click "Add Charge"
        const formAmount = parseFloat(form.querySelector('[name="amount"]')?.value || 0);
        if (formAmount > 0) {
            const formData = new FormData(form);
            const amenityId = formData.get('amenity_id');
            tempCharges.push({
                amenity_id: amenityId && amenityId !== '' ? amenityId : null,
                amenity_name: amenityId ? tempAmenities.find(a => a.id == amenityId)?.name : null,
                charge_type: formData.get('charge_type') || 'damage',
                description: formData.get('description') || '',
                amount: formAmount,
            });
            form.reset();
            render();
        }

        const unpaidTotal = tempCharges.reduce((sum, c) => sum + Number(c.amount || 0), 0);
        const hasCharges = unpaidTotal > 0;
        const message = hasCharges 
            ? `Total charges: <strong>₱${unpaidTotal.toFixed(2)}</strong><br><br>Has this been paid already?`
            : `No additional charges?`;
        
        const confirmed = await showConfirmModal(
            hasCharges ? 'Confirm Payment & Checkout' : 'Confirm Checkout',
            message
        );
        
        if (!confirmed) return;
        
        close();
        showLoadingScreen('Processing checkout...');
        
        try {
            const savedCharges = [...tempCharges];

            // Save all charges to database only after confirmation
            for (const charge of savedCharges) {
                const chargePayload = {
                    amenity_id: charge.amenity_id || null,
                    charge_type: charge.charge_type,
                    description: charge.description,
                    amount: charge.amount,
                };
                const chargeResponse = await fetch(`/staff/reservations/${reservationId}/charges`, {
                    method: 'POST',
                    headers: { 
                        Accept: 'application/json', 
                        'X-CSRF-TOKEN': getCsrf(), 
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(chargePayload)
                });
                const chargeResult = await chargeResponse.json().catch(() => ({}));
                if (!chargeResponse.ok) {
                    throw new Error(chargeResult.message || 'Failed to save charge to database.');
                }
            }
            
            // Mark all charges as paid if there are any
            if (hasCharges && unpaidTotal > 0) {
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
            
            // Clear temp charges for next time modal opens
            tempCharges = [];
            
            await onPaid?.();
        } catch (error) {
            console.error('Error during checkout charges:', error);
            window.alert(error.message || 'An error occurred while saving charges.');
        } finally {
            hideLoadingScreen();
        }
    };
};
