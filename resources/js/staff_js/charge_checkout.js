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
        confirmModal.className = 'fixed inset-0 z-[3000] flex items-center justify-center bg-black/60 p-4';
        confirmModal.innerHTML = `
            <div class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl dark:bg-[#1d241f]">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">${title}</h2>
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">${message}</p>
                <div class="mt-6 flex gap-3 justify-end">
                    <button type="button" class="cancel-btn rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 dark:border-white/10 dark:text-gray-200">Cancel</button>
                    <button type="button" class="confirm-btn rounded-lg bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-800">Yes, Proceed</button>
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

export const openChargeCheckout = async (reservationId, onPaid) => {
    const modalId = 'reservationChargesModal';
    let modal = document.getElementById(modalId);
    
    // Track charges in memory only (not persisted to DB yet)
    let tempCharges = [];
    let tempAmenities = [];
    
    if (!modal) {
        modal = document.createElement('div');
        modal.id = modalId;
        modal.className = 'fixed inset-0 z-[2000] hidden items-center justify-center bg-black/60 p-4';
        modal.innerHTML = `
            <div class="relative max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl dark:bg-[#1d241f]">
                <button type="button" data-charge-close class="absolute right-4 top-4 text-2xl text-gray-500 hover:text-red-500" aria-label="Close charges">&times;</button>
                <h2 class="m-0 text-xl font-bold text-gray-900 dark:text-white">Additional Charges</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">Review any damage, cleaning, lost-item, or other charges before checkout.</p>
                
                <div class="mt-5 grid grid-cols-2 gap-6">
                    <!-- LEFT SIDE: Charge Maker Form -->
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">Add Charge</h3>
                        <form id="reservationChargeForm" class="grid gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                            <label class="grid gap-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                Amenity
                                <select name="amenity_id" id="chargeAmenity" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-white/10"></select>
                            </label>
                            <label class="grid gap-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                Charge type
                                <select name="charge_type" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-white/10">
                                    <option value="damage">Damage</option>
                                    <option value="cleaning">Cleaning</option>
                                    <option value="lost">Lost item</option>
                                    <option value="others">Others</option>
                                </select>
                            </label>
                            <label class="grid gap-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                Description <span class="text-gray-400">(optional)</span>
                                <textarea name="description" rows="2" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-white/10" placeholder="Describe the charge (optional)"></textarea>
                            </label>
                            <label class="grid gap-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                Amount
                                <input name="amount" type="number" min="0.01" step="0.01" required class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-white/10 dark:bg-white/10" placeholder="0.00">
                            </label>
                            <button type="submit" class="w-full rounded-lg bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-800">Add Charge</button>
                        </form>
                    </div>
                    
                    <!-- RIGHT SIDE: Charges List & Total -->
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">Charges List</h3>
                        <div id="reservationChargesList" class="grid gap-2 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5 mb-4 max-h-64 overflow-y-auto"></div>
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/30 dark:bg-emerald-950/20">
                            <div class="flex items-center justify-between">
                                <strong class="text-gray-900 dark:text-white">Total Charges</strong>
                                <strong id="reservationChargesTotal" class="text-2xl text-emerald-700 dark:text-emerald-300">₱0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- BUTTONS -->
                <div class="mt-6 flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-white/10">
                    <button type="button" data-charge-close class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 dark:border-white/10 dark:text-gray-200">Cancel</button>
                    <button type="button" id="proceedCheckout" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-800">Checkout</button>
                </div>
            </div>`;
        document.body.appendChild(modal);
    }

    const form = modal.querySelector('#reservationChargeForm');
    const list = modal.querySelector('#reservationChargesList');
    const total = modal.querySelector('#reservationChargesTotal');
    const amenity = modal.querySelector('#chargeAmenity');
    const proceedBtn = modal.querySelector('#proceedCheckout');
    const close = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };
    modal.querySelectorAll('[data-charge-close]').forEach((button) => button.onclick = close);

    const render = () => {
        const unpaidTotal = tempCharges.reduce((sum, c) => sum + Number(c.amount || 0), 0);
        
        list.innerHTML = tempCharges.length ? tempCharges.map((charge, idx) => `<div class="flex items-start justify-between gap-3 rounded-lg border border-gray-200 p-3 text-sm dark:border-white/10"><div><strong>${charge.charge_type.toUpperCase()}</strong>${charge.amenity_name ? `<p class="m-0 text-gray-500">${charge.amenity_name}</p>` : ''}</div><div class="flex gap-2 items-center"><strong>₱${Number(charge.amount).toFixed(2)}</strong><button type="button" data-idx="${idx}" class="delete-charge-btn text-red-500 hover:text-red-700 font-bold text-lg leading-none">×</button></div></div>`).join('') : '<p class="text-sm text-gray-500">No charges added yet.</p>';
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
        if (unpaidTotal > 0) {
            proceedBtn.textContent = 'Pay & Checkout';
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
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    form.onsubmit = async (event) => {
        event.preventDefault();
        const formData = new FormData(form);
        const amenityId = formData.get('amenity_id');
        
        tempCharges.push({
            amenity_id: amenityId || null,
            amenity_name: amenityId ? tempAmenities.find(a => a.id == amenityId)?.name : null,
            charge_type: formData.get('charge_type'),
            description: formData.get('description'),
            amount: formData.get('amount'),
        });
        
        form.reset();
        render();
    };

    proceedBtn.onclick = async () => {
        const hasCharges = proceedBtn.dataset.hasCharges === 'true';
        const unpaidTotal = tempCharges.reduce((sum, c) => sum + Number(c.amount || 0), 0);
        const message = hasCharges 
            ? `Total charges: <strong>₱${unpaidTotal.toFixed(2)}</strong><br><br>Has this been paid already?`
            : `No additional charges?`;
        
        const confirmed = await showConfirmModal(
            hasCharges ? 'Confirm Payment' : 'Confirm Checkout',
            message
        );
        
        if (!confirmed) return;
        
        close();
        showLoadingScreen('Processing checkout...');
        
        try {
            // Save all charges to database only after confirmation
            for (const charge of tempCharges) {
                await fetch(`/staff/reservations/${reservationId}/charges`, {
                    method: 'POST',
                    headers: { 
                        Accept: 'application/json', 
                        'X-CSRF-TOKEN': getCsrf(), 
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(charge)
                });
            }
            
            // Mark all charges as paid if there are any
            if (hasCharges && unpaidTotal > 0) {
                await fetch(`/staff/reservations/${reservationId}/charges/pay`, {
                    method: 'POST',
                    headers: { 
                        Accept: 'application/json', 
                        'X-CSRF-TOKEN': getCsrf(), 
                        'X-Requested-With': 'XMLHttpRequest' 
                    }
                });
            }
            
            // Clear temp charges for next time modal opens
            tempCharges = [];
            
            await onPaid?.();
        } finally {
            hideLoadingScreen();
        }
    };
};
