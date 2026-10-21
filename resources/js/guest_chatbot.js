document.addEventListener('DOMContentLoaded', () => {
    const chatbotWidget = document.getElementById('chatbotWidget');
    const chatbotToggle = document.getElementById('chatbotToggle');
    const chatbotWindow = document.getElementById('chatbotWindow');
    const chatbotClose = document.getElementById('chatbotClose');
    const chatbotForm = document.getElementById('chatbotForm');
    const chatbotInput = document.getElementById('chatbotInput');
    const chatbotMessages = document.getElementById('chatbotMessages');
    const chatbotSend = document.getElementById('chatbotSend');

    // Top booking bar elements
    const chatbotBookingBar = document.getElementById('chatbotBookingBar');
    const chatbotBookingBarStepName = document.getElementById('chatbotBookingBarStepName');
    const chatbotBookingBarDesc = document.getElementById('chatbotBookingBarDesc');
    const chatbotBarActionBtn = document.getElementById('chatbotBarActionBtn');
    const chatbotBarActionBtnText = document.getElementById('chatbotBarActionBtnText');
    const chatbotBarViewPickBtn = document.getElementById('chatbotBarViewPickBtn');
    const chatbotBarViewPickIcon = document.getElementById('chatbotBarViewPickIcon');
    const chatbotBarViewPickText = document.getElementById('chatbotBarViewPickText');
    const chatbotBarCancelBtn = document.getElementById('chatbotBarCancelBtn');
    const cbBarStep1 = document.getElementById('cbBarStep1');
    const cbBarStep2 = document.getElementById('cbBarStep2');
    const cbBarStep3 = document.getElementById('cbBarStep3');
    const cbBarStep4 = document.getElementById('cbBarStep4');

    // Bottom Action Dock elements
    const chatbotDock = document.getElementById('chatbotDock');
    const chatbotDockSchedule = document.getElementById('chatbotDockSchedule');
    const chatbotDockActionBtn = document.getElementById('chatbotDockActionBtn');
    const chatbotDockActionIcon = document.getElementById('chatbotDockActionIcon');
    const chatbotDockActionText = document.getElementById('chatbotDockActionText');

    // Amenity Selection Modal elements
    const chatbotAmenityModal = document.getElementById('chatbotAmenityModal');
    const cbAmenityModalClose = document.getElementById('cbAmenityModalClose');
    const cbAmenityModalBody = document.getElementById('cbAmenityModalBody');
    const cbAmenityModalFooter = document.getElementById('cbAmenityModalFooter');
    const chatbotAmenityModalSubtitle = document.getElementById('chatbotAmenityModalSubtitle');

    // Date & Session Range Modal elements
    const chatbotDateModal = document.getElementById('chatbotDateModal');
    const cbDateModalClose = document.getElementById('cbDateModalClose');
    const cbDateModalBody = document.getElementById('cbDateModalBody');
    const cbDateModalFooter = document.getElementById('cbDateModalFooter');
    const chatbotDateModalSubtitle = document.getElementById('chatbotDateModalSubtitle');

    // Step 3: Primary Booker Information Modal elements
    const chatbotGuestModal = document.getElementById('chatbotGuestModal');
    const cbGuestModalClose = document.getElementById('cbGuestModalClose');
    const cbGuestModalBody = document.getElementById('cbGuestModalBody');
    const cbGuestModalFooter = document.getElementById('cbGuestModalFooter');
    const chatbotGuestModalSubtitle = document.getElementById('chatbotGuestModalSubtitle');

    // Escape HTML to prevent XSS
    const escapeHtml = (text) => {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    };

    const roundToTwo = (num) => Math.round((Number(num || 0) + Number.EPSILON) * 100) / 100;

    const CONFUSED_FALLBACK = "I'm sorry, i couldnt understand and im confuse can you state it again";
    const sanitizeChatbotContent = (text) => {
        if (!text) return '';
        if (/(?:user\s+safety|response\s+safety)/i.test(text)) {
            return CONFUSED_FALLBACK;
        }
        return text;
    };

    // Format bot responses with clean markdown rendering
    const formatBotMessage = (text) => {
        let formatted = escapeHtml(sanitizeChatbotContent(text));
        // Headers
        formatted = formatted.replace(/^###\s+(.*)$/gm, '<h5 class="font-bold text-sm text-emerald-800 dark:text-emerald-300 mt-2 mb-1">$1</h5>');
        formatted = formatted.replace(/^##\s+(.*)$/gm, '<h4 class="font-bold text-base text-emerald-800 dark:text-emerald-300 mt-2 mb-1">$1</h4>');
        // Convert **bold** to <strong>bold</strong>
        formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Convert *italic* to <em>italic</em>
        formatted = formatted.replace(/\*(.*?)\*/g, '<em>$1</em>');
        // Convert bullet points (- or *)
        formatted = formatted.replace(/^[-*]\s+(.*)$/gm, '• $1');
        // Convert numbered lists
        formatted = formatted.replace(/^(\d+)\.\s+(.*)$/gm, '$1. $2');
        // Convert newlines to <br>
        formatted = formatted.replace(/\n/g, '<br>');
        return formatted;
    };

    // ─────────────────────────────────────────────────────────────
    // LocalStorage State Management
    // ─────────────────────────────────────────────────────────────
    const STATE_KEY = 'guestChatbotState';
    const DRAFT_KEY = 'guestChatbotBookingDraft';

    const getInitialDraft = () => ({
        active: false,
        step: 'date_slot', // 'date_slot' | 'amenity' | 'guest_info' | 'review' | 'payment' | 'confirmed'
        startDate: null,
        endDate: null,
        startSlot: 'Daytime', // 'Daytime' | 'Nighttime'
        endSlot: 'Daytime',   // 'Daytime' | 'Nighttime'
        slot: 'Daytime',      // backward compatibility
        dayCount: 1,
        nightCount: 0,
        daysSpan: 1,
        amenities: [],        // array of selected amenities
        amenity: null,        // backward compatibility
        guest: {
            name: '',
            phone: '',
            email: '',
            guests_count: 1,
        },
        payment: {
            payment_intent_id: null,
            total_amount: 0,
            deposit_amount: 0,
            remaining_balance: 0,
            method: null,
            status: 'pending',
            reservation_id: null,
        }
    });

    const loadBookingDraft = () => {
        try {
            const saved = localStorage.getItem(DRAFT_KEY);
            if (saved) {
                const parsed = JSON.parse(saved);
                if (!Array.isArray(parsed.amenities)) {
                    parsed.amenities = parsed.amenity ? [parsed.amenity] : [];
                }
                if (!parsed.startSlot) parsed.startSlot = parsed.slot || 'Daytime';
                if (!parsed.endSlot) parsed.endSlot = parsed.slot || 'Daytime';
                if (!parsed.dayCount) parsed.dayCount = 1;
                if (!parsed.daysSpan) parsed.daysSpan = 1;
                return parsed;
            }
        } catch (e) {
            console.error('Error loading booking draft from localStorage:', e);
        }
        return getInitialDraft();
    };

    let bookingDraft = loadBookingDraft();

    const saveBookingDraft = () => {
        try {
            localStorage.setItem(DRAFT_KEY, JSON.stringify(bookingDraft));
        } catch (e) {
            console.error('Error saving booking draft to localStorage:', e);
        }
        updateBookingBarUI();
    };

    const clearBookingDraft = () => {
        stopPaymentPolling();
        isPaymentConfirmed = false;
        bookingSuccessCardRendered = false;
        isCheckingPayment = false;
        bookingDraft = getInitialDraft();
        localStorage.removeItem(DRAFT_KEY);
        closeDateModal();
        closeAmenityModal();
        closeGuestModal();
        updateBookingBarUI();
    };

    const calculateDraftTotal = () => {
        const list = bookingDraft.amenities && bookingDraft.amenities.length > 0
            ? bookingDraft.amenities
            : (bookingDraft.amenity ? [bookingDraft.amenity] : []);
        return list.reduce((sum, item) => sum + (Number(item.price) || 0), 0);
    };

    const updateBookingBarUI = () => {
        if (!chatbotBookingBar) return;

        if (!bookingDraft || !bookingDraft.active) {
            chatbotBookingBar.hidden = true;
            chatbotBookingBar.classList.add('hidden');
            if (chatbotDock) {
                chatbotDock.hidden = true;
                chatbotDock.classList.add('hidden');
            }
            return;
        }

        chatbotBookingBar.hidden = false;
        chatbotBookingBar.classList.remove('hidden');

        let stepTitle = 'Step 1: Pick Dates';
        let desc = 'Setting up reservation…';
        let actionLabel = 'Pick Dates';
        let actionIcon = 'bi-calendar3';

        const total = calculateDraftTotal();
        const deposit = Math.max(roundToTwo(total * 0.5), total > 0 ? 20.00 : 0);
        const amCount = bookingDraft.amenities ? bookingDraft.amenities.length : (bookingDraft.amenity ? 1 : 0);

        switch (bookingDraft.step) {
            case 'date_slot':
                stepTitle = 'Step 1: Dates & Sessions';
                desc = bookingDraft.startDate
                    ? `${bookingDraft.startDate} [${bookingDraft.startSlot || 'Daytime'}]`
                    : 'Pick your stay dates';
                actionLabel = bookingDraft.startDate ? 'Edit Dates' : 'Pick Dates';
                actionIcon = 'bi-calendar3';
                break;
            case 'amenity':
                stepTitle = 'Step 2: Choose Amenities';
                desc = amCount > 0
                    ? `${amCount} Amenit${amCount > 1 ? 'ies' : 'y'} Selected (₱${total.toFixed(2)})`
                    : `Schedule: ${bookingDraft.startDate} [${bookingDraft.startSlot || 'Daytime'}]`;
                actionLabel = amCount > 0 ? `Amenities (${amCount})` : 'Choose Amenities';
                actionIcon = amCount > 0 ? 'bi-card-checklist' : 'bi-houses';
                break;
            case 'guest_info':
                stepTitle = 'Step 3: Primary Booker Information';
                desc = bookingDraft.guest?.name
                    ? `Booker: ${bookingDraft.guest.name} (${bookingDraft.guest.guests_count || 1} pax)`
                    : `${amCount} Amenit${amCount > 1 ? 'ies' : 'y'} • Fill booker details`;
                actionLabel = bookingDraft.guest?.name ? 'Edit Booker' : 'Enter Info in Modal';
                actionIcon = 'bi-person-vcard';
                break;
            case 'review':
                stepTitle = 'Step 4: Review Booking';
                desc = `50% Deposit: ₱${deposit.toFixed(2)} (Total: ₱${total.toFixed(2)})`;
                actionLabel = 'Review & Pay';
                actionIcon = 'bi-credit-card';
                break;
            case 'payment':
                stepTitle = 'Step 4: Complete Downpayment';
                desc = `Pay ₱${deposit.toFixed(2)} via Xendit QR/E-Wallet`;
                actionLabel = 'Pay Deposit';
                actionIcon = 'bi-shield-check';
                break;
            case 'confirmed':
                stepTitle = 'Booking Confirmed!';
                desc = `Ref #${bookingDraft.confirmedReservationId || 'Online'}`;
                actionLabel = 'View Receipt';
                actionIcon = 'bi-receipt';
                break;
        }

        if (chatbotBookingBarStepName) chatbotBookingBarStepName.textContent = stepTitle;
        if (chatbotBookingBarDesc) chatbotBookingBarDesc.textContent = desc;

        if (chatbotBarActionBtn) {
            chatbotBarActionBtn.innerHTML = `<i class="bi ${actionIcon} me-1"></i> <span id="chatbotBarActionBtnText">${actionLabel}</span>`;
        }
        if (chatbotBarViewPickBtn) {
            chatbotBarViewPickBtn.innerHTML = `<i class="bi ${actionIcon} me-1" id="chatbotBarViewPickIcon"></i> <span id="chatbotBarViewPickText">${actionLabel}</span>`;
        }

        // Update Bottom Floating Dock (Always docked right above chat input)
        if (chatbotDock) {
            chatbotDock.hidden = false;
            chatbotDock.classList.remove('hidden');
            if (chatbotDockSchedule) {
                if (bookingDraft.step === 'amenity') {
                    chatbotDockSchedule.textContent = amCount > 0
                        ? `🏡 ${amCount} Amenit${amCount > 1 ? 'ies' : 'y'} (₱${total.toFixed(2)})`
                        : `📅 ${bookingDraft.startDate} [${bookingDraft.startSlot || 'Daytime'}]`;
                } else if (bookingDraft.step === 'date_slot') {
                    chatbotDockSchedule.textContent = bookingDraft.startDate
                        ? `📅 ${bookingDraft.startDate} [${bookingDraft.startSlot || 'Daytime'}]`
                        : '📅 Step 1: Pick Dates';
                } else {
                    chatbotDockSchedule.textContent = desc;
                }
            }
            if (chatbotDockActionBtn) {
                let dockAction = actionLabel;
                let dockIcon = actionIcon;
                if (bookingDraft.step === 'amenity' && amCount > 0) {
                    dockAction = 'Continue to Booker →';
                    dockIcon = 'bi-arrow-right-circle';
                }
                chatbotDockActionBtn.innerHTML = `<i class="bi ${dockIcon} text-[12px]" id="chatbotDockActionIcon"></i> <span id="chatbotDockActionText">${dockAction}</span>`;
            }
        }

        // Update Step Pills with Tailwind classes
        const curStep = bookingDraft.step;

        // Step 1: Dates
        if (cbBarStep1) {
            const isDone = Boolean(bookingDraft.startDate);
            const isActive = curStep === 'date_slot';
            cbBarStep1.className = isActive ? pillActiveCls : (isDone ? pillDoneCls : pillPendingCls);
            cbBarStep1.innerHTML = `
                <span class="w-4 h-4 rounded-full ${isActive ? 'bg-emerald-600 text-white' : (isDone ? 'bg-emerald-400 text-emerald-950' : 'bg-emerald-800 text-emerald-300')} flex items-center justify-center text-[10px] font-bold">
                    ${isDone ? '<i class="bi bi-check-lg"></i>' : '1'}
                </span>
                <span class="truncate">${isDone ? 'Dates ✓' : 'Dates'}</span>
            `;
        }

        // Step 2: Amenities
        if (cbBarStep2) {
            const isDone = amCount > 0;
            const isActive = curStep === 'amenity';
            cbBarStep2.className = isActive ? pillActiveCls : (isDone ? pillDoneCls : pillPendingCls);
            cbBarStep2.innerHTML = `
                <span class="w-4 h-4 rounded-full ${isActive ? 'bg-emerald-600 text-white' : (isDone ? 'bg-emerald-400 text-emerald-950' : 'bg-emerald-800 text-emerald-300')} flex items-center justify-center text-[10px] font-bold">
                    ${isDone ? '<i class="bi bi-check-lg"></i>' : '2'}
                </span>
                <span class="truncate">${isDone ? `${amCount} Amenity✓` : 'Amenities'}</span>
            `;
        }

        // Step 3: Guest Info
        if (cbBarStep3) {
            const isDone = Boolean(bookingDraft.guest?.name);
            const isActive = curStep === 'guest_info';
            cbBarStep3.className = isActive ? pillActiveCls : (isDone ? pillDoneCls : pillPendingCls);
            cbBarStep3.innerHTML = `
                <span class="w-4 h-4 rounded-full ${isActive ? 'bg-emerald-600 text-white' : (isDone ? 'bg-emerald-400 text-emerald-950' : 'bg-emerald-800 text-emerald-300')} flex items-center justify-center text-[10px] font-bold">
                    ${isDone ? '<i class="bi bi-check-lg"></i>' : '3'}
                </span>
                <span class="truncate">${isDone ? 'Guest ✓' : 'Guest Info'}</span>
            `;
        }

        // Step 4: Review & Pay
        if (cbBarStep4) {
            const isDone = curStep === 'confirmed';
            const isActive = curStep === 'review' || curStep === 'payment';
            cbBarStep4.className = isActive ? pillActiveCls : (isDone ? pillDoneCls : pillPendingCls);
            cbBarStep4.innerHTML = `
                <span class="w-4 h-4 rounded-full ${isActive ? 'bg-emerald-600 text-white' : (isDone ? 'bg-emerald-400 text-emerald-950' : 'bg-emerald-800 text-emerald-300')} flex items-center justify-center text-[10px] font-bold">
                    ${isDone ? '<i class="bi bi-check-lg"></i>' : '4'}
                </span>
                <span class="truncate">${isDone ? 'Paid ✓' : 'Review & Pay'}</span>
            `;
        }
    };

    // Load general chat state from localStorage
    const loadState = () => {
        try {
            const saved = localStorage.getItem(STATE_KEY);
            if (saved) {
                const parsed = JSON.parse(saved);
                if (Array.isArray(parsed.messages)) {
                    parsed.messages = parsed.messages.map(m => {
                        if (m.isBot) {
                            m.content = sanitizeChatbotContent(m.content);
                        }
                        return m;
                    });
                }
                return parsed;
            }
        } catch (e) {
            console.error('Error loading guest chatbot state:', e);
        }
        return { isOpen: false, messages: [], selectedModel: null };
    };

    const saveState = (isOpen, messages, selectedModel) => {
        try {
            localStorage.setItem(STATE_KEY, JSON.stringify({ isOpen, messages, selectedModel }));
        } catch (e) {
            console.error('Error saving guest chatbot state:', e);
        }
    };

    const initialState = loadState();
    let isOpen = initialState.isOpen || false;
    let messages = initialState.messages || [];
    let selectedModel = initialState.selectedModel || null;
    let paymentPollInterval = null;
    let isCheckingPayment = false;
    let isPaymentConfirmed = false;
    let bookingSuccessCardRendered = false;

    const stopPaymentPolling = () => {
        if (paymentPollInterval) {
            clearInterval(paymentPollInterval);
            paymentPollInterval = null;
        }
    };

    const getBotLogoUrl = () => {
        return chatbotWidget?.getAttribute('data-logo')
            || document.querySelector('.chatbot-avatar img')?.src
            || '/storage/design_images/main_logo.jpeg';
    };

    // Add plain text / markdown message to chat
    const addMessage = (content, isBot = true, shouldSave = true) => {
        if (isBot) {
            content = sanitizeChatbotContent(content);
        }

        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbot-message ${isBot ? 'chatbot-message--bot' : 'chatbot-message--user'}`;

        if (isBot) {
            messageDiv.innerHTML = `
                <div class="chatbot-message__avatar">
                    <img src="${getBotLogoUrl()}" alt="Hinaguan Logo" class="chatbot-avatar-img">
                </div>
                <div class="chatbot-message__body">
                    <div class="chatbot-message__meta">
                        <span class="chatbot-message__author">Bren</span>
                    </div>
                    <div class="chatbot-message__content">
                        <p>${formatBotMessage(content)}</p>
                    </div>
                </div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="chatbot-message__body">
                    <div class="chatbot-message__content">
                        <p>${escapeHtml(content)}</p>
                    </div>
                </div>
            `;
        }

        chatbotMessages.appendChild(messageDiv);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;

        if (shouldSave) {
            messages.push({ content, isBot });
            saveState(isOpen, messages, selectedModel);
        }

        return messageDiv;
    };

    // Add custom HTML card inside a bot message bubble
    const addCustomCardMessage = (textIntro, cardElement, shouldSaveText = true) => {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'chatbot-message chatbot-message--bot';

        const bodyDiv = document.createElement('div');
        bodyDiv.className = 'chatbot-message__body';

        bodyDiv.innerHTML = `
            <div class="chatbot-message__meta">
                <span class="chatbot-message__author">Bren</span>
            </div>
            <div class="chatbot-message__content">
                <p>${formatBotMessage(textIntro)}</p>
            </div>
        `;

        if (cardElement) {
            bodyDiv.appendChild(cardElement);
        }

        messageDiv.innerHTML = `
            <div class="chatbot-message__avatar">
                <img src="${getBotLogoUrl()}" alt="Hinaguan Logo" class="chatbot-avatar-img">
            </div>
        `;
        messageDiv.appendChild(bodyDiv);

        chatbotMessages.appendChild(messageDiv);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;

        if (shouldSaveText) {
            messages.push({ content: textIntro, isBot: true });
            saveState(isOpen, messages, selectedModel);
        }

        return messageDiv;
    };

    // Restore messages from localStorage
    if (messages.length > 0) {
        chatbotMessages.innerHTML = '';
        chatbotWidget.classList.add('is-conversation');
        messages.forEach(msg => {
            addMessage(msg.content, msg.isBot, false);
        });
    }

    // Restore window state
    if (isOpen) {
        chatbotWindow.hidden = false;
        chatbotWidget?.classList.add('is-open');
        chatbotToggle.setAttribute('aria-expanded', 'true');
        if (window.innerWidth <= 640 && window.visualViewport) {
            chatbotWindow.style.height = `${window.visualViewport.height}px`;
        }
        setTimeout(() => {
            if (chatbotMessages) chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        }, 500);
    }

    // Initialize booking bar on load
    updateBookingBarUI();

    // ─────────────────────────────────────────────────────────────
    // Conversational Booking Workflow & Conflict Prevention
    // ─────────────────────────────────────────────────────────────

    // Helper: format YYYY-MM-DD to readable date
    const formatDateReadable = (dateStr) => {
        if (!dateStr) return '';
        try {
            const parts = dateStr.split('-');
            if (parts.length === 3) {
                const date = new Date(parts[0], parts[1] - 1, parts[2]);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            }
        } catch (e) { }
        return dateStr;
    };

    // Helper: get tomorrow's date string YYYY-MM-DD
    const getOffsetDateString = (offsetDays = 0) => {
        const d = new Date();
        d.setDate(d.getDate() + offsetDays);
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };

    let awaitingBookingConfirmation = false;

    // Ask for Confirmation before entering Booking Mode
    const askBookingConfirmation = () => {
        if (bookingDraft && bookingDraft.active) {
            addMessage(`You are already in booking mode! Your current step is **${bookingDraft.step === 'amenity' ? 'Choose Amenities' : (bookingDraft.step === 'guest_info' ? 'Booker Details' : 'Pick Dates')}**. Click the button above to continue your booking or type 'cancel booking' to reset.`, true);
            return;
        }

        awaitingBookingConfirmation = true;

        const confirmCard = document.createElement('div');
        confirmCard.className = 'chatbot-booking-card';
        confirmCard.innerHTML = `
            <div class="chatbot-card-header">
                <span><i class="bi bi-calendar-check text-emerald-600 me-1.5"></i> Online Reservation Assistant</span>
                <span class="text-xs text-emerald-600 font-bold">Hinaguan Park</span>
            </div>
            <div style="font-size:0.8rem;color:#064e3b;line-height:1.45;margin-bottom:0.6rem;">
                I can guide you through reserving your stay:
                <ul style="margin:0.35rem 0 0 1rem;padding:0;list-style-type:disc;color:#047857;font-size:0.75rem;">
                    <li>Select check-in &amp; check-out dates and sessions</li>
                    <li>Browse live available cottages, payags, and A-houses</li>
                    <li>Provide guest details and receive your official digital pass</li>
                </ul>
            </div>
            <div style="display:flex;gap:0.4rem;flex-wrap:wrap;">
                <button type="button" class="chatbot-action-btn chatbot-action-btn--primary" id="cbConfirmStartBookingBtn" style="flex:1;">
                    <i class="bi bi-check-circle-fill me-1"></i> Yes, Let's Book Now
                </button>
                <button type="button" class="chatbot-action-btn chatbot-action-btn--secondary" id="cbDeclineStartBookingBtn" style="flex:1;">
                    <i class="bi bi-chat-dots me-1"></i> Not Yet, Ask Questions
                </button>
            </div>
        `;

        confirmCard.querySelector('#cbConfirmStartBookingBtn')?.addEventListener('click', () => {
            awaitingBookingConfirmation = false;
            const b = confirmCard.querySelector('#cbConfirmStartBookingBtn');
            if (b) {
                b.disabled = true;
                b.innerHTML = '<i class="bi bi-check-lg me-1"></i> Starting Booking…';
            }
            startBookingMode();
        });

        confirmCard.querySelector('#cbDeclineStartBookingBtn')?.addEventListener('click', () => {
            awaitingBookingConfirmation = false;
            const b = confirmCard.querySelector('#cbDeclineStartBookingBtn');
            if (b) b.disabled = true;
            addMessage("No problem at all! 😊 Feel free to ask about our cottages, payags, pool rates, day/night schedules, or park policies anytime.", true);
        });

        addCustomCardMessage(
            "I can certainly book a reservation for you right here in our chat! 🌿\n\nWould you like to start your booking now?",
            confirmCard
        );
    };

    // Start Booking Mode
    const startBookingMode = () => {
        stopPaymentPolling();
        isPaymentConfirmed = false;
        bookingSuccessCardRendered = false;
        isCheckingPayment = false;
        bookingDraft.active = true;
        bookingDraft.step = 'date_slot';
        saveBookingDraft();

        const card = createDateSlotCard();
        addCustomCardMessage(
            "Welcome to online booking at Hinaguan Nature Park. Let's arrange your reservation through our conversation.\n\nPlease choose your **Check-in & Check-out dates and session slots** in our schedule chooser modal:",
            card
        );

        // Open Date Selection Modal automatically for comfortable, spacious picking
        setTimeout(() => {
            openDateModal();
        }, 150);
    };

    // Step 1: Create Date & Slot In-Chat Card (Compact trigger for Date Modal)
    const createDateSlotCard = () => {
        const card = document.createElement('div');
        card.className = 'chatbot-booking-card';

        const renderCardContent = () => {
            const hasDate = Boolean(bookingDraft.startDate);
            const startFmt = formatDateReadable(bookingDraft.startDate || getOffsetDateString(1));
            const endFmt = formatDateReadable(bookingDraft.endDate || bookingDraft.startDate || getOffsetDateString(1));
            const startSlot = bookingDraft.startSlot || 'Daytime';
            const endSlot = bookingDraft.endSlot || startSlot;

            const isSameDay = (bookingDraft.startDate || getOffsetDateString(1)) === (bookingDraft.endDate || bookingDraft.startDate || getOffsetDateString(1));
            const scheduleDesc = isSameDay
                ? `${startFmt} (${startSlot}${startSlot !== endSlot ? ' → ' + endSlot : ''})`
                : `${startFmt} [${startSlot}] → ${endFmt} [${endSlot}]`;

            card.innerHTML = `
                <div class="chatbot-card-header">
                    <span><i class="bi bi-calendar-event text-emerald-600 me-1.5"></i> 1. Schedule (${hasDate ? 'Selected' : 'Pick Dates'})</span>
                    <span class="text-xs text-emerald-600 font-bold">Multi-stay Ready</span>
                </div>

                <div class="chatbot-duration-banner">
                    <span id="cbInChatDurationBannerText"><i class="bi bi-calendar-range me-1.5 text-emerald-700"></i> ${scheduleDesc}</span>
                    <span class="chatbot-badge chatbot-badge--avail">Live Rates</span>
                </div>

                <p style="font-size:0.72rem;color:#4b5563;margin:0.25rem 0 0.45rem;line-height:1.4;">
                    ${hasDate ? 'Schedule is saved. Click below to adjust dates/sessions or proceed to choose amenities.' : 'Open our schedule chooser dialog to pick check-in & check-out dates and daytime/nighttime sessions.'}
                </p>

                <button type="button" class="chatbot-action-btn chatbot-action-btn--primary" id="cbOpenDateModalBtn">
                    ${hasDate ? '<i class="bi bi-pencil-square me-1.5"></i> Modify Schedule (Modal)' : '<i class="bi bi-calendar2-range me-1.5"></i> Open Schedule Chooser (Modal)'}
                </button>

                ${hasDate ? `
                <button type="button" class="chatbot-action-btn chatbot-action-btn--secondary" id="cbInChatProceedDateBtn" style="margin-top:0.35rem;">
                    Check Available Amenities <i class="bi bi-arrow-right ms-1"></i>
                </button>
                ` : ''}
            `;

            card.querySelector('#cbOpenDateModalBtn')?.addEventListener('click', () => {
                openDateModal();
            });

            card.querySelector('#cbInChatProceedDateBtn')?.addEventListener('click', () => {
                const sDate = bookingDraft.startDate || getOffsetDateString(1);
                const eDate = bookingDraft.endDate || sDate;
                const sSlot = bookingDraft.startSlot || 'Daytime';
                const eSlot = bookingDraft.endSlot || sSlot;
                onDateSlotConfirmed(sDate, eDate, sSlot, eSlot);
            });
        };

        renderCardContent();
        return card;
    };

    // Step 1 Modal: Open Date & Session Range Choosing Modal (Image 2 Calendar Picker Style)
    const openDateModal = () => {
        if (!chatbotDateModal || !cbDateModalBody || !cbDateModalFooter) return;

        const todayStr = getOffsetDateString(0);
        let selectedStartDate = bookingDraft.startDate || todayStr;
        let selectedEndDate = bookingDraft.endDate || selectedStartDate;
        let selectedStartSlot = bookingDraft.startSlot || 'Daytime';
        let selectedEndSlot = bookingDraft.endSlot || 'Daytime';

        let selectingEnd = false;
        let hoverDate = null;

        // Start calendar view on month of selectedStartDate
        const startParts = selectedStartDate.split('-').map(Number);
        let currentYear = startParts[0] || new Date().getFullYear();
        let currentMonth = startParts[1] || (new Date().getMonth() + 1);

        // Update header current date pill
        const curDatePill = document.getElementById('cbCalModalCurrent');
        if (curDatePill) {
            curDatePill.textContent = 'Current: ' + formatDateReadable(todayStr);
        }

        // Close button in header
        const closeBtn = document.getElementById('cbDateModalClose');
        closeBtn?.replaceWith(closeBtn.cloneNode(true));
        document.getElementById('cbDateModalClose')?.addEventListener('click', closeDateModal);

        // Helper to compute actual checkout date string (Overnight ends next morning)
        const getActualCheckoutDateStr = (dateStr, slot) => {
            if (slot === 'Nighttime') {
                const parts = dateStr.split('-').map(Number);
                const d = new Date(parts[0], parts[1] - 1, parts[2]);
                d.setDate(d.getDate() + 1);
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${y}-${m}-${day}`;
            }
            return dateStr;
        };

        // Render modal structure
        cbDateModalBody.innerHTML = `
            <!-- Check-in & Check-out Summary Bar (Top) -->
            <div class="chatbot-date-summary-card">
                <div class="chatbot-summary-boxes">
                    <!-- Check-In Box -->
                    <div class="chatbot-summary-box chatbot-summary-box--in">
                        <div class="chatbot-summary-box__badge chatbot-summary-box__badge--in">IN</div>
                        <div class="chatbot-summary-box__content">
                            <div class="chatbot-summary-box__label">Check-In Session</div>
                            <div class="chatbot-summary-box__val" id="cbTopCheckInText">—</div>
                        </div>
                    </div>

                    <!-- Check-Out Box -->
                    <div class="chatbot-summary-box chatbot-summary-box--out">
                        <div class="chatbot-summary-box__badge chatbot-summary-box__badge--out">
                            <i class="bi bi-box-arrow-right"></i>
                        </div>
                        <div class="chatbot-summary-box__content">
                            <div class="chatbot-summary-box__label">Check-Out Date &amp; Time</div>
                            <div class="chatbot-summary-box__val" id="cbTopCheckoutFullText">—</div>
                        </div>
                    </div>
                </div>

                <!-- Session Pickers -->
                <div class="chatbot-sessions-bar">
                    <div id="cbStartSlotCol">
                        <span class="chatbot-sessions-col__label">Check-In Session:</span>
                        <div class="chatbot-sessions-col__btns chatbot-sessions-col__btns--start" id="cbStartSlotGroup">
                            <button type="button" class="session-pill-btn" data-slot-type="start" data-slot-val="Daytime">
                                <i class="bi bi-sun-fill text-amber-500"></i>
                                <span>Daytime (6 AM)</span>
                            </button>
                            <button type="button" class="session-pill-btn" data-slot-type="start" data-slot-val="Nighttime">
                                <i class="bi bi-moon-stars-fill text-indigo-400"></i>
                                <span>Overnight (6 PM)</span>
                            </button>
                            <button type="button" class="session-pill-btn session-pill-btn--wholeday" data-slot-type="start" data-slot-val="WholeDay" id="cbWholeDayBtn">
                                <span class="session-wholeday-icons">
                                    <i class="bi bi-brightness-high-fill text-amber-500"></i>
                                    <i class="bi bi-moon-stars-fill text-indigo-400"></i>
                                </span>
                                <span>Whole Day (24 hrs)</span>
                                <span class="session-wholeday-badge">24h</span>
                            </button>
                        </div>
                    </div>
                    <div id="cbEndSlotCol">
                        <span class="chatbot-sessions-col__label">Check-Out Session: <span id="cbCheckoutDisabledBadge" class="text-[0.62rem] text-slate-400 font-normal italic ms-1">(Auto for 1 day)</span></span>
                        <div class="chatbot-sessions-col__btns" id="cbEndSlotGroup">
                            <button type="button" class="session-pill-btn" data-slot-type="end" data-slot-val="Daytime">
                                <i class="bi bi-sun-fill text-amber-500"></i>
                                <span>Daytime (6 PM)</span>
                            </button>
                            <button type="button" class="session-pill-btn" data-slot-type="end" data-slot-val="Nighttime">
                                <i class="bi bi-moon-stars-fill text-indigo-400"></i>
                                <span>Overnight (6 AM)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar Component (Image 2 style) -->
            <div class="edit-calendar">
                <div class="edit-calendar__head">
                    <button type="button" class="edit-calendar__nav" id="cbCalPrev" aria-label="Previous month">&lsaquo;</button>
                    <div class="edit-calendar__title-wrap">
                        <div class="edit-calendar__title" id="cbCalTitle">&mdash;</div>
                        <select class="edit-calendar__year" id="cbCalYear" aria-label="Select year"></select>
                    </div>
                    <button type="button" class="edit-calendar__nav" id="cbCalNext" aria-label="Next month">&rsaquo;</button>
                </div>

                <div class="edit-calendar__weekdays">
                    <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                </div>

                <div class="edit-calendar__grid" id="cbCalGrid"></div>

                <div class="edit-calendar__legend">
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <span><span class="edit-calendar__legend-dot edit-calendar__legend-dot--selected"></span> Selected Range</span>
                        <span><span class="edit-calendar__legend-dot edit-calendar__legend-dot--unavailable"></span> Unavailable / Past</span>
                    </div>
                    <span id="cbCalStepHelp" class="edit-calendar__step-help">Click date to set check-in</span>
                </div>
            </div>
        `;

        // Render modal footer
        cbDateModalFooter.innerHTML = `
            <div class="chatbot-date-modal-footer">
                <div class="chatbot-date-footer-summary">
                    <div class="chatbot-date-footer-summary__out" id="cbCalCheckoutDateText">—</div>
                    <div class="chatbot-date-footer-summary__in" id="cbCalSummaryScheduleText">Check-In: —</div>
                    <div class="chatbot-date-footer-summary__badge" id="cbCalStayBadge">Daytime Stay</div>
                </div>
                <div style="display:flex;align-items:center;gap:0.5rem;flex-shrink:0;">
                    <button type="button" class="chatbot-action-btn chatbot-action-btn--secondary" id="cbCalCancelBtn" style="padding:0.5rem 0.85rem;font-size:0.8rem;">
                        Cancel
                    </button>
                    <button type="button" class="chatbot-action-btn chatbot-action-btn--primary" id="cbCalApplyBtn" style="padding:0.5rem 1.15rem;font-size:0.8rem;">
                        Confirm Dates &amp; Choose Amenities &rarr;
                    </button>
                </div>
            </div>
        `;

        // Elements
        const topCheckInText = cbDateModalBody.querySelector('#cbTopCheckInText');
        const topCheckoutFullText = cbDateModalBody.querySelector('#cbTopCheckoutFullText');
        const calTitle = cbDateModalBody.querySelector('#cbCalTitle');
        const calYear = cbDateModalBody.querySelector('#cbCalYear');
        const calPrev = cbDateModalBody.querySelector('#cbCalPrev');
        const calNext = cbDateModalBody.querySelector('#cbCalNext');
        const calGrid = cbDateModalBody.querySelector('#cbCalGrid');
        const calStepHelp = cbDateModalBody.querySelector('#cbCalStepHelp');
        const footerCheckoutText = cbDateModalFooter.querySelector('#cbCalCheckoutDateText');
        const footerScheduleText = cbDateModalFooter.querySelector('#cbCalSummaryScheduleText');
        const footerStayBadge = cbDateModalFooter.querySelector('#cbCalStayBadge');
        const cancelBtn = cbDateModalFooter.querySelector('#cbCalCancelBtn');
        const applyBtn = cbDateModalFooter.querySelector('#cbCalApplyBtn');
        const slotButtons = cbDateModalBody.querySelectorAll('.session-pill-btn');
        const startSlotGroup = cbDateModalBody.querySelector('#cbStartSlotGroup');
        const endSlotCol = cbDateModalBody.querySelector('#cbEndSlotCol');
        const wholeDayBtn = cbDateModalBody.querySelector('#cbWholeDayBtn');
        const checkoutDisabledBadge = cbDateModalBody.querySelector('#cbCheckoutDisabledBadge');

        cancelBtn?.addEventListener('click', closeDateModal);

        // Populate Year select (current year to +5 years)
        const baseYear = new Date().getFullYear();
        if (calYear) {
            calYear.innerHTML = '';
            for (let y = baseYear; y <= baseYear + 5; y++) {
                const opt = document.createElement('option');
                opt.value = y;
                opt.textContent = y;
                if (y === currentYear) opt.selected = true;
                calYear.appendChild(opt);
            }
            calYear.addEventListener('change', () => {
                currentYear = parseInt(calYear.value, 10);
                renderCalendar();
            });
        }

        // Navigation handlers
        calPrev?.addEventListener('click', () => {
            currentMonth--;
            if (currentMonth < 1) {
                currentMonth = 12;
                currentYear--;
            }
            if (calYear) calYear.value = currentYear;
            renderCalendar();
        });

        calNext?.addEventListener('click', () => {
            currentMonth++;
            if (currentMonth > 12) {
                currentMonth = 1;
                currentYear++;
            }
            if (calYear) calYear.value = currentYear;
            renderCalendar();
        });

        // Session button click handlers
        slotButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const slotType = btn.dataset.slotType;
                const slotVal = btn.dataset.slotVal;
                const isSingleDay = selectedStartDate === selectedEndDate;

                if (slotType === 'start') {
                    selectedStartSlot = slotVal;
                    if (isSingleDay) {
                        if (slotVal === 'Daytime') selectedEndSlot = 'Daytime';
                        else if (slotVal === 'Nighttime') selectedEndSlot = 'Nighttime';
                        else if (slotVal === 'WholeDay') selectedEndSlot = 'Nighttime';
                    }
                } else {
                    if (isSingleDay) return; // Checkout session disabled for 1 day
                    selectedEndSlot = slotVal;
                }
                updateSummaryAndControls();
            });
        });

        // Update top summary cards, session pills, and footer details
        const updateSummaryAndControls = () => {
            const isSingleDay = selectedStartDate === selectedEndDate;

            if (isSingleDay) {
                // Show Whole Day button and expand button group
                if (wholeDayBtn) wholeDayBtn.style.display = '';
                startSlotGroup?.classList.add('is-single-day');

                // Disable Checkout Session buttons
                endSlotCol?.classList.add('chatbot-sessions-col--disabled');
                if (checkoutDisabledBadge) checkoutDisabledBadge.style.display = '';

                // For 1 day, checkout session is auto-governed by check-in
                if (selectedStartSlot === 'Daytime') {
                    selectedEndSlot = 'Daytime';
                } else if (selectedStartSlot === 'Nighttime') {
                    selectedEndSlot = 'Nighttime';
                } else if (selectedStartSlot === 'WholeDay') {
                    selectedEndSlot = 'Nighttime';
                }
            } else {
                // Multi-day stay: hide Whole Day button and revert if it was selected
                if (wholeDayBtn) wholeDayBtn.style.display = 'none';
                startSlotGroup?.classList.remove('is-single-day');

                if (selectedStartSlot === 'WholeDay') {
                    selectedStartSlot = 'Daytime';
                }

                // Activate Checkout Session
                endSlotCol?.classList.remove('chatbot-sessions-col--disabled');
                if (checkoutDisabledBadge) checkoutDisabledBadge.style.display = 'none';
            }

            // Compute checkout date string
            let actualCheckoutDate = selectedEndDate;
            let inTimeStr = '';
            let outTimeStr = '';

            if (selectedStartSlot === 'WholeDay') {
                inTimeStr = 'Whole Day 24 hrs (6:00 AM)';
                actualCheckoutDate = getActualCheckoutDateStr(selectedStartDate, 'Nighttime');
                outTimeStr = '6:00 AM (Next Day · 24 hrs)';
            } else if (selectedStartSlot === 'Nighttime' && isSingleDay) {
                inTimeStr = 'Overnight (6:00 PM)';
                actualCheckoutDate = getActualCheckoutDateStr(selectedStartDate, 'Nighttime');
                outTimeStr = '6:00 AM (Overnight)';
            } else if (selectedStartSlot === 'Daytime' && isSingleDay) {
                inTimeStr = 'Daytime (6:00 AM)';
                actualCheckoutDate = selectedStartDate;
                outTimeStr = '6:00 PM (Daytime)';
            } else {
                // Multi-day
                inTimeStr = selectedStartSlot === 'Daytime' ? 'Daytime (6:00 AM)' : 'Overnight (6:00 PM)';
                actualCheckoutDate = getActualCheckoutDateStr(selectedEndDate, selectedEndSlot);
                outTimeStr = selectedEndSlot === 'Daytime' ? '6:00 PM (Daytime)' : '6:00 AM (Overnight)';
            }

            if (topCheckInText) {
                topCheckInText.textContent = `${formatDateReadable(selectedStartDate)} · ${inTimeStr}`;
            }
            if (topCheckoutFullText) {
                topCheckoutFullText.textContent = `${formatDateReadable(actualCheckoutDate)} · ${outTimeStr}`;
            }

            // Sync session button active states
            slotButtons.forEach(b => {
                const type = b.dataset.slotType;
                const val = b.dataset.slotVal;
                if (type === 'start') {
                    b.classList.toggle('is-selected', val === selectedStartSlot);
                } else if (type === 'end') {
                    b.classList.toggle('is-selected', val === selectedEndSlot);
                }
            });

            // Footer texts
            if (footerCheckoutText) {
                footerCheckoutText.textContent = `${formatDateReadable(actualCheckoutDate)} · ${outTimeStr}`;
            }
            if (footerScheduleText) {
                const startLabel = selectedStartSlot === 'WholeDay' ? 'Whole Day 24 hrs' : selectedStartSlot;
                footerScheduleText.textContent = `Check-In: ${formatDateReadable(selectedStartDate)} (${startLabel})`;
            }

            if (footerStayBadge) {
                if (selectedStartSlot === 'WholeDay') {
                    footerStayBadge.textContent = 'Whole Day Stay · 24 Hours (Day & Night)';
                } else if (isSingleDay) {
                    footerStayBadge.textContent = `Single Session Stay · 1 ${selectedStartSlot}`;
                } else {
                    const sD = new Date(selectedStartDate);
                    const eD = new Date(selectedEndDate);
                    const diffDays = Math.max(1, Math.round((eD - sD) / (1000 * 60 * 60 * 24)));
                    footerStayBadge.textContent = `${diffDays} Night${diffDays > 1 ? 's' : ''} Stay (${diffDays + 1} Days)`;
                }
            }

            // Step help text
            if (calStepHelp) {
                if (selectingEnd) {
                    calStepHelp.textContent = 'Click check-out date (or click same date for 1 day)';
                } else {
                    calStepHelp.textContent = 'Dates selected! Click any date to change check-in.';
                }
            }
        };

        // Render monthly calendar grid
        const renderCalendar = () => {
            if (!calGrid || !calTitle) return;

            const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();
            const leading = new Date(currentYear, currentMonth - 1, 1).getDay();
            const monthName = new Date(currentYear, currentMonth - 1, 1).toLocaleDateString('en-US', { month: 'long' });

            calTitle.textContent = monthName;

            // Nav button states
            const now = new Date();
            const thisYear = now.getFullYear();
            const thisMonth = now.getMonth() + 1;
            if (calPrev) {
                calPrev.disabled = (currentYear === thisYear && currentMonth <= thisMonth);
            }
            if (calNext) {
                calNext.disabled = (currentYear >= thisYear + 5 && currentMonth >= 12);
            }

            calGrid.innerHTML = '';

            // Leading empty days
            for (let i = 0; i < leading; i++) {
                const empty = document.createElement('div');
                empty.className = 'edit-calendar__day edit-calendar__day--empty';
                calGrid.appendChild(empty);
            }

            // Month days
            for (let d = 1; d <= daysInMonth; d++) {
                const iso = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                const isPast = iso < todayStr;

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'edit-calendar__day';
                btn.textContent = d;
                btn.dataset.date = iso;

                if (isPast) {
                    btn.classList.add('is-disabled');
                    btn.disabled = true;
                    btn.title = 'Past date';
                    btn.setAttribute('aria-label', `${iso} unavailable`);
                } else {
                    btn.classList.add('is-available');
                    btn.setAttribute('aria-label', `${iso} available`);

                    const isStart = iso === selectedStartDate;
                    const isEnd = iso === selectedEndDate;
                    const isInRange = selectedStartDate && selectedEndDate && iso > selectedStartDate && iso < selectedEndDate;
                    const isHoverRange = selectingEnd && hoverDate && selectedStartDate && iso > selectedStartDate && iso <= hoverDate;

                    if (isStart && isEnd) {
                        btn.classList.add('is-range-start', 'is-range-end');
                    } else if (isStart) {
                        btn.classList.add('is-range-start');
                    } else if (isEnd) {
                        btn.classList.add('is-range-end');
                    } else if (isInRange) {
                        btn.classList.add('is-in-range');
                    } else if (isHoverRange) {
                        btn.classList.add('is-range-hover');
                    }

                    // Hover preview
                    btn.addEventListener('mouseenter', () => {
                        if (selectingEnd && selectedStartDate && iso >= selectedStartDate) {
                            hoverDate = iso;
                            calGrid.querySelectorAll('.edit-calendar__day[data-date]').forEach(dayBtn => {
                                const dt = dayBtn.dataset.date;
                                if (!dt) return;
                                const inHov = dt > selectedStartDate && dt <= iso;
                                dayBtn.classList.toggle('is-range-hover', inHov);
                            });
                        }
                    });

                    // Click handler
                    btn.addEventListener('click', () => {
                        if (!selectingEnd) {
                            // Step 1: Select check-in date
                            selectedStartDate = iso;
                            selectedEndDate = iso;
                            selectingEnd = true;
                            hoverDate = null;
                        } else {
                            // Step 2: Select check-out date
                            if (iso < selectedStartDate) {
                                selectedStartDate = iso;
                                selectedEndDate = iso;
                                selectingEnd = true;
                                hoverDate = null;
                            } else {
                                selectedEndDate = iso;
                                selectingEnd = false;
                                hoverDate = null;
                            }
                        }
                        updateSummaryAndControls();
                        renderCalendar();
                    });
                }

                calGrid.appendChild(btn);
            }
        };

        calGrid?.addEventListener('mouseleave', () => {
            hoverDate = null;
            calGrid.querySelectorAll('.edit-calendar__day').forEach(b => b.classList.remove('is-range-hover'));
        });

        // Apply schedule
        applyBtn?.addEventListener('click', () => {
            if (!selectedStartDate) {
                alert('Please select a check-in date.');
                return;
            }
            if (!selectedEndDate) selectedEndDate = selectedStartDate;
            if (selectedEndDate < selectedStartDate) {
                selectedEndDate = selectedStartDate;
            }

            const isSingleDay = selectedStartDate === selectedEndDate;
            let confirmedStartSlot = selectedStartSlot;
            let confirmedEndSlot = selectedEndSlot;

            if (isSingleDay) {
                if (selectedStartSlot === 'WholeDay') {
                    confirmedStartSlot = 'Daytime';
                    confirmedEndSlot = 'Nighttime';
                } else {
                    confirmedStartSlot = selectedStartSlot;
                    confirmedEndSlot = selectedStartSlot;
                }
            }

            closeDateModal();
            onDateSlotConfirmed(selectedStartDate, selectedEndDate, confirmedStartSlot, confirmedEndSlot);
        });

        // Initialize view
        updateSummaryAndControls();
        renderCalendar();
        chatbotDateModal.hidden = false;
        chatbotDateModal.classList.remove('hidden');
    };

    const closeDateModal = () => {
        if (chatbotDateModal) {
            chatbotDateModal.hidden = true;
            chatbotDateModal.classList.add('hidden');
        }
    };

    // Date & Slot Confirmed -> Advance to Step 2 (Amenity)
    const onDateSlotConfirmed = async (startDate, endDate, startSlot, endSlot) => {
        bookingDraft.startDate = startDate;
        bookingDraft.endDate = endDate || startDate;
        bookingDraft.startSlot = startSlot;
        bookingDraft.endSlot = endSlot;
        bookingDraft.slot = startSlot;
        bookingDraft.step = 'amenity';
        saveBookingDraft();

        const startFmt = formatDateReadable(startDate);
        const endFmt = formatDateReadable(endDate);
        let scheduleDesc = '';
        if (startDate === endDate) {
            if (startSlot === 'Daytime' && endSlot === 'Nighttime') {
                scheduleDesc = `**${startFmt} [Whole Day 24 hrs (Day & Night)]**`;
            } else {
                scheduleDesc = `**${startFmt} [${startSlot}]**`;
            }
        } else {
            scheduleDesc = `**${startFmt} [${startSlot}] → ${endFmt} [${endSlot}]**`;
        }

        addMessage(`Great! I've set your schedule to ${scheduleDesc}. Checking our live database for available amenities...`, true);

        // Fetch live amenities with live availability and conflict prevention
        try {
            const res = await fetch(`/guest-chatbot/booking/amenities?start_date=${startDate}&end_date=${endDate || startDate}&start_slot=${startSlot}&end_slot=${endSlot}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (res.ok && data.success && Array.isArray(data.amenities)) {
                cachedAmenities = data.amenities;
                cachedAmenitiesMeta = data;
                bookingDraft.dayCount = data.day_count || 1;
                bookingDraft.nightCount = data.night_count || 0;
                bookingDraft.daysSpan = data.days_span || 1;
                saveBookingDraft();

                if (data.slot_passed) {
                    addMessage("⚠️ Note: Daytime session for today has already passed. Please choose Nighttime or pick an upcoming date.", true);
                    const retryCard = createDateSlotCard();
                    addCustomCardMessage("Please pick a different session slot or upcoming date in our date modal:", retryCard);
                    setTimeout(() => {
                        openDateModal();
                    }, 150);
                    return;
                }

                const card = createAmenitySelectionCard(data.amenities, data);
                addCustomCardMessage(
                    `Here are our live amenities for ${scheduleDesc}. Please pick your amenities in our visual chooser dialog:`,
                    card
                );

                // Seamlessly open the spacious visual amenity choosing modal
                setTimeout(() => {
                    openAmenityModal(data.amenities, data);
                }, 150);
            } else {
                addMessage("I couldn't fetch live amenities right now. Please try again or refresh.", true);
            }
        } catch (err) {
            console.error('Error fetching amenities availability:', err);
            addMessage("Network error fetching available amenities. Please check your connection.", true);
        }
    };

    // Step 2: Create Amenity Selection In-Chat Card (Compact trigger for modal)
    const createAmenitySelectionCard = (amenities, metaData) => {
        const card = document.createElement('div');
        card.className = 'chatbot-booking-card';

        const availableCount = amenities.filter(a => a.is_available).length;
        const totalCount = amenities.length;
        const staySummary = `${metaData.day_count} Day · ${metaData.night_count} Night`;

        const renderCardContent = () => {
            const list = (bookingDraft.amenities && bookingDraft.amenities.length > 0)
                ? bookingDraft.amenities
                : (bookingDraft.amenity ? [bookingDraft.amenity] : []);

            const count = list.length;
            const subtotal = list.reduce((sum, a) => sum + Number(a.price), 0);

            let chosenHtml = '';
            if (count > 0) {
                chosenHtml = `
                    <div style="font-size:0.72rem;color:#065f46;font-weight:700;margin:0.35rem 0 0.2rem;display:flex;align-items:center;gap:0.3rem;">
                        <i class="bi bi-check-circle-fill text-emerald-600"></i>
                        <span>Selected Amenities (${count}):</span>
                    </div>
                    <div class="chatbot-cart-chips" style="margin-bottom:0.45rem;">
                        ${list.map(a => `<span class="chatbot-cart-chip">${escapeHtml(a.name)} · ₱${Number(a.price).toFixed(2)}</span>`).join('')}
                    </div>
                `;
            } else {
                chosenHtml = `
                    <p style="font-size:0.72rem;color:#6b7280;margin:0.35rem 0;">
                        No amenities chosen yet. Open our visual chooser dialog to see photos, rates, and pick multiple amenities comfortably.
                    </p>
                `;
            }

            card.innerHTML = `
                <div class="chatbot-card-header">
                    <span><i class="bi bi-houses text-emerald-600 me-1.5"></i> 2. Choose Amenities (${staySummary})</span>
                    <span class="text-xs font-semibold text-emerald-600">${availableCount} Available</span>
                </div>
                ${chosenHtml}
                <button type="button" class="chatbot-action-btn chatbot-action-btn--primary" id="cbOpenModalBtn">
                    ${count > 0 ? `<i class="bi bi-pencil-square me-1"></i> Modify Amenities (${count} Selected)` : '<i class="bi bi-houses me-1"></i> Open Amenity Chooser Modal'}
                </button>
                ${count > 0 ? `
                <button type="button" class="chatbot-action-btn chatbot-action-btn--secondary" id="cbContinueWithPicksBtn" style="margin-top:0.3rem;">
                    Continue with ${count} Amenit${count > 1 ? 'ies' : 'y'} (₱${subtotal.toFixed(2)}) <i class="bi bi-arrow-right ms-1"></i>
                </button>
                ` : ''}
            `;

            card.querySelector('#cbOpenModalBtn')?.addEventListener('click', () => {
                openAmenityModal(amenities, metaData);
            });

            card.querySelector('#cbContinueWithPicksBtn')?.addEventListener('click', () => {
                onAmenitiesConfirmed(list);
            });
        };

        renderCardContent();
        return card;
    };

    let cachedAmenities = null;
    let cachedAmenitiesMeta = null;

    const triggerAmenitySelection = async () => {
        if (!bookingDraft.startDate) {
            openDateModal();
            return;
        }
        if (cachedAmenities && cachedAmenities.length > 0 && cachedAmenitiesMeta) {
            openAmenityModal(cachedAmenities, cachedAmenitiesMeta);
            return;
        }
        await onDateSlotConfirmed(
            bookingDraft.startDate,
            bookingDraft.endDate || bookingDraft.startDate,
            bookingDraft.startSlot || 'Daytime',
            bookingDraft.endSlot || bookingDraft.startSlot || 'Daytime'
        );
    };

    // Open Amenity Choosing Modal (Full Viewport Dialog)
    const openAmenityModal = (amenities, metaData) => {
        if (!chatbotAmenityModal || !cbAmenityModalBody || !cbAmenityModalFooter) return;
        cachedAmenities = amenities;
        cachedAmenitiesMeta = metaData;

        const startFmt = formatDateReadable(metaData.start_date || bookingDraft.startDate);
        const endFmt = formatDateReadable(metaData.end_date || bookingDraft.endDate || metaData.start_date);
        const startSlot = metaData.start_slot || bookingDraft.startSlot || 'Daytime';
        const endSlot = metaData.end_slot || bookingDraft.endSlot || startSlot;
        const staySummary = `${metaData.day_count} Day · ${metaData.night_count} Night`;

        if (chatbotAmenityModalSubtitle) {
            chatbotAmenityModalSubtitle.textContent = `Schedule: ${startFmt} [${startSlot}] → ${endFmt} [${endSlot}] (${staySummary})`;
        }

        const counts = {
            all: amenities.length,
            cottage: amenities.filter(a => a.category === 'Cottage').length,
            payag: amenities.filter(a => a.category === 'Payag').length,
            ahouse: amenities.filter(a => a.category === 'A-House').length,
            hall: amenities.filter(a => a.category === 'Function Hall').length,
        };

        const selectedIds = new Set(
            (bookingDraft.amenities && bookingDraft.amenities.length > 0)
                ? bookingDraft.amenities.map(a => String(a.id))
                : (bookingDraft.amenity ? [String(bookingDraft.amenity.id)] : [])
        );

        const getCategoryIconHtml = (category) => {
            switch (category) {
                case 'Payag': return '<i class="bi bi-tree text-emerald-600"></i>';
                case 'A-House': return '<i class="bi bi-house-door text-teal-600"></i>';
                case 'Function Hall': return '<i class="bi bi-building text-indigo-600"></i>';
                default: return '<i class="bi bi-house text-emerald-700"></i>';
            }
        };

        cbAmenityModalBody.innerHTML = `
            <!-- Category Filter Tabs -->
            <div class="chatbot-category-tabs" id="cbModalCategoryTabs">
                <button type="button" class="chatbot-tab-btn is-active" data-cat="all">
                    <i class="bi bi-grid-fill me-1"></i> All <span class="chatbot-tab-count">${counts.all}</span>
                </button>
                <button type="button" class="chatbot-tab-btn" data-cat="Cottage">
                    <i class="bi bi-house me-1"></i> Cottages <span class="chatbot-tab-count">${counts.cottage}</span>
                </button>
                <button type="button" class="chatbot-tab-btn" data-cat="Payag">
                    <i class="bi bi-tree me-1"></i> Payags <span class="chatbot-tab-count">${counts.payag}</span>
                </button>
                <button type="button" class="chatbot-tab-btn" data-cat="A-House">
                    <i class="bi bi-house-door me-1"></i> A-Houses <span class="chatbot-tab-count">${counts.ahouse}</span>
                </button>
                ${counts.hall > 0 ? `
                <button type="button" class="chatbot-tab-btn" data-cat="Function Hall">
                    <i class="bi bi-building me-1"></i> Function Halls <span class="chatbot-tab-count">${counts.hall}</span>
                </button>` : ''}
            </div>

            <!-- Responsive Amenities Grid -->
            <div class="chatbot-amenity-modal-grid" id="cbModalGrid"></div>
        `;

        cbAmenityModalFooter.innerHTML = `
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span id="cbModalCartCountLabel" style="font-size:0.8rem;font-weight:700;color:#065f46;display:flex;align-items:center;gap:0.35rem;">
                    <i class="bi bi-cart-check"></i>
                    <span>0 Amenities Selected</span>
                </span>
                <strong id="cbModalCartTotalLabel" style="font-size:0.95rem;color:#047857;">₱0.00</strong>
            </div>
            <div id="cbModalDepositNotice" style="font-size:0.75rem;color:#047857;display:flex;align-items:center;gap:0.35rem;">
                <i class="bi bi-shield-check text-emerald-600"></i>
                <span>50% Required Downpayment: <strong id="cbModalDepositLabel">₱0.00</strong></span>
            </div>
            <div class="chatbot-cart-chips" id="cbModalCartChips"></div>
            <div style="display:flex;gap:0.5rem;margin-top:0.35rem;">
                <button type="button" class="chatbot-action-btn chatbot-action-btn--secondary" id="cbModalCancelBtn" style="width:30%;">
                    <i class="bi bi-x-circle me-1"></i> Close
                </button>
                <button type="button" class="chatbot-action-btn chatbot-action-btn--primary" id="cbModalConfirmBtn" style="width:70%;" disabled>
                    Select Amenity to Continue <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        `;

        const gridContainer = cbAmenityModalBody.querySelector('#cbModalGrid');
        const cartCountLabel = cbAmenityModalFooter.querySelector('#cbModalCartCountLabel');
        const cartTotalLabel = cbAmenityModalFooter.querySelector('#cbModalCartTotalLabel');
        const cartDepositLabel = cbAmenityModalFooter.querySelector('#cbModalDepositLabel');
        const cartChipsContainer = cbAmenityModalFooter.querySelector('#cbModalCartChips');
        const confirmBtn = cbAmenityModalFooter.querySelector('#cbModalConfirmBtn');
        const cancelBtn = cbAmenityModalFooter.querySelector('#cbModalCancelBtn');
        const tabBtns = cbAmenityModalBody.querySelectorAll('#cbModalCategoryTabs .chatbot-tab-btn');

        let currentCat = 'all';

        const renderGrid = (activeCat = 'all') => {
            gridContainer.innerHTML = '';
            const filtered = activeCat === 'all'
                ? amenities
                : amenities.filter(a => a.category === activeCat);

            if (filtered.length === 0) {
                gridContainer.innerHTML = `<p style="font-size:0.8rem;color:#6b7280;text-align:center;padding:2rem;grid-column:1/-1;">No amenities found in this category.</p>`;
                return;
            }

            filtered.forEach(am => {
                const isSelected = selectedIds.has(String(am.id));
                const item = document.createElement('div');
                item.className = `chatbot-amenity-item ${am.is_available ? '' : 'is-occupied'} ${isSelected ? 'is-selected' : ''}`;
                item.dataset.amenityId = String(am.id);

                const badgeHtml = am.is_available
                    ? '<span class="chatbot-badge chatbot-badge--avail"><i class="bi bi-check-circle-fill me-1"></i> Available</span>'
                    : '<span class="chatbot-badge chatbot-badge--taken"><i class="bi bi-x-circle-fill me-1"></i> Booked</span>';

                let buttonHtml = '';
                if (!am.is_available) {
                    buttonHtml = `<button type="button" class="chatbot-amenity-select-btn" disabled title="Already booked on this schedule"><i class="bi bi-slash-circle me-1"></i> Unavailable</button>`;
                } else if (isSelected) {
                    buttonHtml = `<button type="button" class="chatbot-amenity-select-btn is-active" data-action="toggle"><i class="bi bi-check-lg me-1"></i> Selected</button>`;
                } else {
                    buttonHtml = `<button type="button" class="chatbot-amenity-select-btn" data-action="toggle"><i class="bi bi-plus-lg me-1"></i> Add</button>`;
                }

                const thumbHtml = am.image
                    ? `<img src="${escapeHtml(am.image)}" alt="${escapeHtml(am.name)}" loading="lazy">`
                    : `<div class="chatbot-amenity-thumb-placeholder">${getCategoryIconHtml(am.category)}</div>`;

                const inclusions = [];
                inclusions.push(`<span class="chatbot-feature-pill"><i class="bi bi-people me-1"></i> ${am.minimum_capacity || 1}–${am.maximum_capacity || 10} pax</span>`);
                if (am.is_aircon) inclusions.push('<span class="chatbot-feature-pill"><i class="bi bi-snow me-1"></i> Aircon</span>');
                if (am.free_entrance) inclusions.push('<span class="chatbot-feature-pill"><i class="bi bi-ticket-perforated me-1"></i> Free Entrance</span>');
                if (am.free_pool) inclusions.push('<span class="chatbot-feature-pill"><i class="bi bi-water me-1"></i> Free Pool</span>');

                item.innerHTML = `
                    <div class="chatbot-amenity-thumb">${thumbHtml}</div>
                    <div class="chatbot-amenity-item__info">
                        <div class="chatbot-amenity-item__name">
                            <span>${escapeHtml(am.name)}</span>
                            ${badgeHtml}
                        </div>
                        <div class="chatbot-amenity-item__meta">
                            <span class="chatbot-amenity-item__price">₱${Number(am.price).toFixed(2)}</span>
                            <span class="chatbot-amenity-item__subprice">(${staySummary})</span>
                        </div>
                        <div class="chatbot-amenity-features">
                            ${inclusions.join('')}
                        </div>
                    </div>
                    ${buttonHtml}
                `;

                if (am.is_available) {
                    const toggleBtn = item.querySelector('[data-action="toggle"]');
                    toggleBtn?.addEventListener('click', () => {
                        const amId = String(am.id);
                        if (selectedIds.has(amId)) {
                            selectedIds.delete(amId);
                            item.classList.remove('is-selected');
                            toggleBtn.classList.remove('is-active');
                            toggleBtn.innerHTML = '<i class="bi bi-plus-lg me-1"></i> Add';
                        } else {
                            selectedIds.add(amId);
                            item.classList.add('is-selected');
                            toggleBtn.classList.add('is-active');
                            toggleBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Selected';
                        }
                        updateModalFooterUI();
                    });
                }

                gridContainer.appendChild(item);
            });
        };

        const updateModalFooterUI = () => {
            const selectedAmenities = amenities.filter(a => selectedIds.has(String(a.id)));
            const count = selectedAmenities.length;
            const subtotal = selectedAmenities.reduce((sum, a) => sum + Number(a.price), 0);
            const deposit = subtotal * 0.5;

            if (cartCountLabel) {
                cartCountLabel.innerHTML = `
                    <i class="bi bi-cart-check"></i>
                    <span>${count === 1 ? '1 Amenity Selected' : `${count} Amenities Selected`}</span>
                `;
            }

            if (cartTotalLabel) {
                cartTotalLabel.textContent = `₱${subtotal.toFixed(2)}`;
            }

            if (cartDepositLabel) {
                cartDepositLabel.textContent = `₱${deposit.toFixed(2)}`;
            }

            if (cartChipsContainer) {
                cartChipsContainer.innerHTML = '';
                selectedAmenities.forEach(am => {
                    const chip = document.createElement('span');
                    chip.className = 'chatbot-cart-chip';
                    chip.innerHTML = `
                        <span>${escapeHtml(am.name)}</span>
                        <span class="chatbot-cart-chip__remove" title="Remove"><i class="bi bi-x"></i></span>
                    `;
                    chip.querySelector('.chatbot-cart-chip__remove')?.addEventListener('click', () => {
                        selectedIds.delete(String(am.id));
                        renderGrid(currentCat);
                        updateModalFooterUI();
                    });
                    cartChipsContainer.appendChild(chip);
                });
            }

            if (confirmBtn) {
                if (count > 0) {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = `Confirm ${count} Amenit${count > 1 ? 'ies' : 'y'} (₱${subtotal.toFixed(2)}) <i class="bi bi-arrow-right ms-1"></i>`;
                } else {
                    confirmBtn.disabled = true;
                    confirmBtn.innerHTML = `Select Amenity to Continue <i class="bi bi-arrow-right ms-1"></i>`;
                }
            }
        };

        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                tabBtns.forEach(b => b.classList.remove('is-active'));
                btn.classList.add('is-active');
                currentCat = btn.dataset.cat;
                renderGrid(currentCat);
            });
        });

        cancelBtn?.addEventListener('click', closeAmenityModal);

        confirmBtn?.addEventListener('click', () => {
            const selectedAmenities = amenities.filter(a => selectedIds.has(String(a.id)));
            if (selectedAmenities.length === 0) {
                alert('Please select at least one amenity.');
                return;
            }
            closeAmenityModal();
            onAmenitiesConfirmed(selectedAmenities);
        });

        renderGrid('all');
        updateModalFooterUI();
        chatbotAmenityModal.hidden = false;
        chatbotAmenityModal.classList.remove('hidden');
    };

    const closeAmenityModal = () => {
        if (chatbotAmenityModal) {
            chatbotAmenityModal.hidden = true;
            chatbotAmenityModal.classList.add('hidden');
        }
    };

    const closeGuestModal = () => {
        if (chatbotGuestModal) {
            chatbotGuestModal.hidden = true;
            chatbotGuestModal.classList.add('hidden');
        }
    };

    // Step 3 Modal: Open Primary Booker Information Modal (Full Viewport Dialog)
    const openGuestModal = () => {
        if (!chatbotGuestModal || !cbGuestModalBody || !cbGuestModalFooter) return;

        // If dates or amenities are not yet selected, guide user
        if (!bookingDraft.startDate) {
            openDateModal();
            return;
        }

        const amenitiesList = bookingDraft.amenities && bookingDraft.amenities.length > 0
            ? bookingDraft.amenities
            : (bookingDraft.amenity ? [bookingDraft.amenity] : []);

        if (amenitiesList.length === 0) {
            triggerAmenitySelection();
            return;
        }

        const combinedMaxCap = amenitiesList.reduce((sum, a) => sum + (Number(a.maximum_capacity) || 10), 0) || 10;
        const totalAmount = calculateDraftTotal();
        const depositAmount = Math.max(roundToTwo(totalAmount * 0.5), totalAmount > 0 ? 20.00 : 0);

        const startFmt = formatDateReadable(bookingDraft.startDate);
        const endFmt = formatDateReadable(bookingDraft.endDate || bookingDraft.startDate);
        const scheduleStr = bookingDraft.startDate === (bookingDraft.endDate || bookingDraft.startDate)
            ? `${startFmt} [${bookingDraft.startSlot || bookingDraft.slot}]`
            : `${startFmt} [${bookingDraft.startSlot || 'Daytime'}] → ${endFmt} [${bookingDraft.endSlot || 'Daytime'}]`;

        if (chatbotGuestModalSubtitle) {
            chatbotGuestModalSubtitle.textContent = `Schedule: ${scheduleStr} · ${amenitiesList.length} Amenit${amenitiesList.length > 1 ? 'ies' : 'y'} Selected (Max ${combinedMaxCap} Pax)`;
        }

        const savedGuest = bookingDraft.guest || {};
        const initialGuestsCount = Math.min(Math.max(1, savedGuest.guests_count || 2), combinedMaxCap);

        // Render Modal Body with Tailwind classes
        cbGuestModalBody.innerHTML = `
            <!-- Booking Summary Banner -->
            <div class="rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/70 dark:bg-emerald-950/40 p-4 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-emerald-200/60 dark:border-emerald-800/40 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-900/80 text-emerald-800 dark:text-emerald-200 border border-emerald-300 dark:border-emerald-700">
                            <i class="bi bi-calendar3"></i> ${escapeHtml(scheduleStr)}
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-teal-100 dark:bg-teal-900/80 text-teal-800 dark:text-teal-200 border border-teal-300 dark:border-teal-700">
                            <i class="bi bi-people-fill"></i> Up to ${combinedMaxCap} pax
                        </span>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-slate-500 dark:text-slate-400">Total:</span>
                        <strong class="text-sm font-bold text-emerald-900 dark:text-emerald-200 ml-1">₱${totalAmount.toFixed(2)}</strong>
                    </div>
                </div>

                <div>
                    <div class="text-xs font-semibold text-emerald-900 dark:text-emerald-300 mb-1.5 flex items-center gap-1.5">
                        <i class="bi bi-houses"></i> Selected Amenities (${amenitiesList.length}):
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        ${amenitiesList.map(a => `
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-emerald-200 dark:border-slate-700 shadow-xs">
                                <strong>${escapeHtml(a.name)}</strong>
                                <span class="text-emerald-600 dark:text-emerald-400 font-semibold">· ₱${Number(a.price).toFixed(2)}</span>
                                <span class="text-slate-400 text-[10px]">(${a.minimum_capacity || 1}–${a.maximum_capacity || 10} pax)</span>
                            </span>
                        `).join('')}
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-emerald-800 dark:text-emerald-300 bg-white/70 dark:bg-slate-900/60 rounded-lg px-3 py-2 border border-emerald-100 dark:border-slate-800">
                    <span class="flex items-center gap-1.5 font-medium">
                        <i class="bi bi-shield-check text-emerald-600 text-sm"></i> Required 50% Downpayment:
                    </span>
                    <strong class="text-emerald-700 dark:text-emerald-300 font-bold text-sm">₱${depositAmount.toFixed(2)}</strong>
                </div>
            </div>

            <!-- Error Notification Banner (Hidden by default) -->
            <div id="cbGuestModalError" class="hidden rounded-xl border border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-950/40 p-3 text-xs text-rose-700 dark:text-rose-300 flex items-start gap-2">
                <i class="bi bi-exclamation-circle-fill text-rose-500 text-sm shrink-0 mt-0.5"></i>
                <div id="cbGuestModalErrorMsg" class="font-medium"></div>
            </div>

            <!-- Form Inputs -->
            <div class="space-y-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 flex items-center justify-between" for="cbGuestModalName">
                        <span class="flex items-center gap-1.5"><i class="bi bi-person text-emerald-600"></i> Full Name (Primary Booker) <span class="text-rose-500">*</span></span>
                        <span class="text-[11px] font-normal text-slate-400">Must match valid Government/Student ID</span>
                    </label>
                    <input type="text" id="cbGuestModalName" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition" placeholder="e.g. Juan Dela Cruz" value="${escapeHtml(savedGuest.name || '')}" autocomplete="name">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5" for="cbGuestModalPhone">
                            <span class="flex items-center gap-1.5"><i class="bi bi-telephone text-emerald-600"></i> Mobile Number (PH) <span class="text-rose-500">*</span></span>
                        </label>
                        <input type="tel" id="cbGuestModalPhone" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition" placeholder="0917 123 4567" value="${escapeHtml(savedGuest.phone || '')}" autocomplete="tel">
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">For gate entry check-in &amp; SMS confirmation.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5" for="cbGuestModalEmail">
                            <span class="flex items-center gap-1.5"><i class="bi bi-envelope text-emerald-600"></i> Email Address <span class="text-rose-500">*</span></span>
                        </label>
                        <input type="email" id="cbGuestModalEmail" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition" placeholder="juan@example.com" value="${escapeHtml(savedGuest.email || '')}" autocomplete="email">
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Digital reservation pass &amp; QR will be sent here.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 flex items-center justify-between" for="cbGuestModalCount">
                        <span class="flex items-center gap-1.5"><i class="bi bi-people text-emerald-600"></i> Total Number of Guests <span class="text-rose-500">*</span></span>
                        <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">Max Capacity: ${combinedMaxCap} pax</span>
                    </label>
                    <div class="flex items-center gap-3">
                        <button type="button" id="cbGuestCountMinus" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-center font-bold text-base transition active:scale-95 cursor-pointer">
                            <i class="bi bi-dash"></i>
                        </button>
                        <input type="number" id="cbGuestModalCount" min="1" max="${combinedMaxCap}" value="${initialGuestsCount}" class="w-24 text-center px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        <button type="button" id="cbGuestCountPlus" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-center font-bold text-base transition active:scale-95 cursor-pointer">
                            <i class="bi bi-plus"></i>
                        </button>
                        <span class="text-xs text-slate-500 dark:text-slate-400">guest(s) joining the stay</span>
                    </div>
                </div>
            </div>
        `;

        // Render Modal Footer with Tailwind classes
        cbGuestModalFooter.innerHTML = `
            <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-2.5">
                <button type="button" id="cbGuestModalBackBtn" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold bg-slate-100 hover:bg-slate-200 active:scale-95 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition cursor-pointer flex items-center justify-center gap-1.5">
                    <i class="bi bi-arrow-left"></i> Back to Amenities
                </button>
                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <button type="button" id="cbGuestModalCancelBtn" class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="button" id="cbGuestModalSubmitBtn" class="flex-1 sm:flex-none px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white transition shadow-sm flex items-center justify-center gap-1.5 cursor-pointer">
                        Confirm &amp; Review Booking <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        `;

        const nameInput = cbGuestModalBody.querySelector('#cbGuestModalName');
        const phoneInput = cbGuestModalBody.querySelector('#cbGuestModalPhone');
        const emailInput = cbGuestModalBody.querySelector('#cbGuestModalEmail');
        const countInput = cbGuestModalBody.querySelector('#cbGuestModalCount');
        const countMinus = cbGuestModalBody.querySelector('#cbGuestCountMinus');
        const countPlus = cbGuestModalBody.querySelector('#cbGuestCountPlus');
        const errorBox = cbGuestModalBody.querySelector('#cbGuestModalError');
        const errorMsg = cbGuestModalBody.querySelector('#cbGuestModalErrorMsg');

        const backBtn = cbGuestModalFooter.querySelector('#cbGuestModalBackBtn');
        const cancelBtn = cbGuestModalFooter.querySelector('#cbGuestModalCancelBtn');
        const submitBtn = cbGuestModalFooter.querySelector('#cbGuestModalSubmitBtn');

        const showError = (message, inputElement) => {
            if (errorMsg && errorBox) {
                errorMsg.textContent = message;
                errorBox.classList.remove('hidden');
                errorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            if (inputElement) {
                inputElement.focus();
                inputElement.classList.add('ring-2', 'ring-rose-500', 'border-transparent');
                setTimeout(() => {
                    inputElement.classList.remove('ring-2', 'ring-rose-500', 'border-transparent');
                }, 2500);
            }
        };

        const hideError = () => {
            if (errorBox) errorBox.classList.add('hidden');
        };

        [nameInput, phoneInput, emailInput, countInput].forEach(inp => {
            inp?.addEventListener('input', hideError);
        });

        countMinus?.addEventListener('click', () => {
            hideError();
            let val = parseInt(countInput.value || '1', 10);
            if (val > 1) countInput.value = val - 1;
        });

        countPlus?.addEventListener('click', () => {
            hideError();
            let val = parseInt(countInput.value || '1', 10);
            if (val < combinedMaxCap) countInput.value = val + 1;
        });

        backBtn?.addEventListener('click', () => {
            closeGuestModal();
            triggerAmenitySelection();
        });

        cancelBtn?.addEventListener('click', closeGuestModal);

        submitBtn?.addEventListener('click', () => {
            const name = (nameInput?.value || '').trim();
            const phone = (phoneInput?.value || '').trim();
            const email = (emailInput?.value || '').trim();
            const count = parseInt(countInput?.value || '1', 10);

            if (!name || name.length < 2 || !/^[\p{L}\s\.\-]+$/u.test(name)) {
                showError('Please enter a valid full name (letters, spaces, dots, or hyphens only).', nameInput);
                return;
            }

            const cleanPhone = phone.replace(/[\s\-\(\)\+]/g, '');
            if (!/^(?:09|639|9)\d{9}$/.test(cleanPhone)) {
                showError('Please enter a valid Philippine mobile number (e.g. 09171234567 or +63 917 123 4567).', phoneInput);
                return;
            }

            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showError('Please enter a valid email address for receiving your digital booking pass.', emailInput);
                return;
            }

            if (isNaN(count) || count < 1) {
                showError('Number of guests must be at least 1.', countInput);
                return;
            }

            if (count > combinedMaxCap) {
                showError(`The selected amenities can only accommodate up to ${combinedMaxCap} guests. Please adjust guest count or add more amenities.`, countInput);
                return;
            }

            const normalizedPhone = cleanPhone.startsWith('639')
                ? '0' + cleanPhone.slice(2)
                : (cleanPhone.startsWith('9') ? '0' + cleanPhone : cleanPhone);

            closeGuestModal();
            onGuestInfoSubmitted(name, normalizedPhone, email, count);
        });

        // Open modal
        chatbotGuestModal.hidden = false;
        chatbotGuestModal.classList.remove('hidden');

        // Autofocus first appropriate field
        setTimeout(() => {
            if (!nameInput?.value) {
                nameInput?.focus();
            } else if (!phoneInput?.value) {
                phoneInput?.focus();
            } else if (!emailInput?.value) {
                emailInput?.focus();
            }
        }, 100);
    };

    cbAmenityModalClose?.addEventListener('click', closeAmenityModal);

    chatbotAmenityModal?.addEventListener('click', (e) => {
        if (e.target === chatbotAmenityModal) closeAmenityModal();
    });

    cbDateModalClose?.addEventListener('click', closeDateModal);

    chatbotDateModal?.addEventListener('click', (e) => {
        if (e.target === chatbotDateModal) closeDateModal();
    });

    cbGuestModalClose?.addEventListener('click', closeGuestModal);

    chatbotGuestModal?.addEventListener('click', (e) => {
        if (e.target === chatbotGuestModal) closeGuestModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (chatbotDateModal && !chatbotDateModal.hidden) {
                closeDateModal();
            }
            if (chatbotAmenityModal && !chatbotAmenityModal.hidden) {
                closeAmenityModal();
            }
            if (chatbotGuestModal && !chatbotGuestModal.hidden) {
                closeGuestModal();
            }
        }
    });

    // Amenities Confirmed -> Advance to Step 3 (Guest Info)
    const onAmenitiesConfirmed = (selectedAmenities) => {
        bookingDraft.amenities = selectedAmenities.map(am => ({
            id: String(am.id),
            name: am.name,
            category: am.category || 'Cottage',
            price: Number(am.price),
            daytime_price: Number(am.daytime_price),
            nighttime_price: Number(am.nighttime_price),
            minimum_capacity: Number(am.minimum_capacity) || 1,
            maximum_capacity: Number(am.maximum_capacity) || 10,
            is_aircon: Boolean(am.is_aircon),
            free_entrance: Boolean(am.free_entrance),
            free_pool: Boolean(am.free_pool),
            image: am.image || null,
        }));
        bookingDraft.amenity = bookingDraft.amenities[0]; // backward compatibility
        bookingDraft.step = 'guest_info';
        saveBookingDraft();

        const totalAmount = calculateDraftTotal();
        const amNames = bookingDraft.amenities.map(a => `**${a.name}**`).join(', ');

        const card = createGuestInfoCard();
        addCustomCardMessage(
            `You picked ${amNames} (Total: ₱${totalAmount.toFixed(2)})! 🌿\n\nNext, please provide your primary booker details to generate your digital passes:`,
            card
        );

        // Seamlessly open the Step 3 Primary Booker Information Modal
        setTimeout(() => {
            openGuestModal();
        }, 150);
    };

    // Shorthand for single selection
    const onAmenitySelected = (amenity) => {
        onAmenitiesConfirmed([amenity]);
    };

    // Attach quick amenity pick / browse chips under bot messages during Step 2
    const attachAmenityActionChips = (messageDiv, replyText) => {
        if (!messageDiv || !bookingDraft.active || bookingDraft.step !== 'amenity') return;

        const bodyDiv = messageDiv.querySelector('.chatbot-message__body');
        if (!bodyDiv) return;

        // Container for action chips
        const chipContainer = document.createElement('div');
        chipContainer.className = 'chatbot-amenity-recommend-bar mt-2 pt-2 border-t border-slate-200/80 dark:border-slate-800/80 flex flex-wrap items-center gap-1.5';

        // Extract mentioned amenities from reply text: e.g. "Cottage 1", "Payag 2", "A-House 1", "Function Hall"
        const regex = /\b(Riverside Cottage \d+|A-Frame (?:Riverside )?Cottage \d+|A-House \d+|Payag \d+|Cottage \d+|Function Hall)\b/gi;
        const matches = [...new Set((replyText.match(regex) || []).map(m => m.trim()))];

        if (matches.length > 0) {
            matches.slice(0, 3).forEach(name => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'px-2 py-1 rounded-md text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 hover:bg-emerald-600 hover:text-white transition-all flex items-center gap-1 cursor-pointer';
                btn.innerHTML = `<i class="bi bi-plus-circle text-[10px]"></i> Select ${escapeHtml(name)}`;
                btn.addEventListener('click', async () => {
                    btn.disabled = true;
                    btn.innerHTML = `<i class="bi bi-hourglass-split"></i> Adding…`;
                    try {
                        const startSlot = bookingDraft.startSlot || bookingDraft.slot || 'Daytime';
                        const endSlot = bookingDraft.endSlot || bookingDraft.slot || 'Daytime';
                        const res = await fetch(`/guest-chatbot/booking/amenities?start_date=${bookingDraft.startDate}&end_date=${bookingDraft.endDate}&start_slot=${startSlot}&end_slot=${endSlot}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        if (res.ok && data.success && Array.isArray(data.amenities)) {
                            const match = data.amenities.find(a => a.name.toLowerCase().includes(name.toLowerCase()) || name.toLowerCase().includes(a.name.toLowerCase()));
                            if (match) {
                                if (match.is_available) {
                                    const currentList = (bookingDraft.amenities && bookingDraft.amenities.length > 0) ? [...bookingDraft.amenities] : [];
                                    if (!currentList.some(a => String(a.id) === String(match.id))) {
                                        currentList.push(match);
                                    }
                                    onAmenitiesConfirmed(currentList);
                                    btn.innerHTML = `<i class="bi bi-check-lg"></i> Added!`;
                                    btn.className = 'px-2 py-1 rounded-md text-[11px] font-semibold bg-emerald-600 text-white cursor-default';
                                    return;
                                } else {
                                    alert(`${match.name} is already reserved for this schedule.`);
                                }
                            }
                        }
                    } catch (e) {
                        console.error('Error selecting amenity from chip:', e);
                    }
                    btn.disabled = false;
                    btn.innerHTML = `<i class="bi bi-plus-circle text-[10px]"></i> Select ${escapeHtml(name)}`;
                });
                chipContainer.appendChild(btn);
            });
        }

        // Always add a "Browse All Amenities" button
        const browseBtn = document.createElement('button');
        browseBtn.type = 'button';
        browseBtn.className = 'px-2 py-1 rounded-md text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-emerald-600 hover:text-white transition-all flex items-center gap-1 cursor-pointer';
        browseBtn.innerHTML = `<i class="bi bi-houses text-[11px]"></i> Browse Amenities (${bookingDraft.startDate || 'Calendar'})`;
        browseBtn.addEventListener('click', () => {
            triggerAmenitySelection();
        });
        chipContainer.appendChild(browseBtn);

        bodyDiv.appendChild(chipContainer);
    };

    // Step 3: Create Guest Info In-Chat Card (Compact trigger for Guest Modal)
    const createGuestInfoCard = () => {
        const card = document.createElement('div');
        card.className = 'chatbot-booking-card';

        const amenitiesList = bookingDraft.amenities && bookingDraft.amenities.length > 0
            ? bookingDraft.amenities
            : (bookingDraft.amenity ? [bookingDraft.amenity] : []);

        const combinedMaxCap = amenitiesList.reduce((sum, a) => sum + (Number(a.maximum_capacity) || 10), 0) || 10;
        const amNames = amenitiesList.map(a => a.name).join(', ') || 'Selected Amenity';
        const hasGuest = Boolean(bookingDraft.guest?.name);
        const guestName = bookingDraft.guest?.name || '';
        const guestPax = bookingDraft.guest?.guests_count || 1;

        card.innerHTML = `
            <div class="chatbot-card-header">
                <span><i class="bi bi-person-vcard text-emerald-600 me-1.5"></i> 3. Primary Booker Information</span>
                <span class="text-xs text-emerald-600 font-bold">${amenitiesList.length} Amenit${amenitiesList.length > 1 ? 'ies' : 'y'}</span>
            </div>
            <div style="font-size:0.75rem;color:#047857;margin-bottom:0.35rem;font-weight:600;">
                Selected: <strong>${escapeHtml(amNames)}</strong> (Max: ${combinedMaxCap} pax)
            </div>
            <p style="font-size:0.72rem;color:#4b5563;margin:0.2rem 0 0.45rem;line-height:1.4;">
                ${hasGuest
                    ? `Saved Booker: <strong>${escapeHtml(guestName)}</strong> (${guestPax} pax). Click below to edit details or proceed.`
                    : 'Please provide primary booker details to issue your digital pass, entry QR code, and confirmation.'}
            </p>
            <button type="button" class="chatbot-action-btn chatbot-action-btn--primary" id="cbOpenGuestModalBtn">
                <i class="bi bi-person-vcard me-1.5"></i> ${hasGuest ? 'Edit Booker Details (Modal)' : 'Enter Booker Information (Modal)'}
            </button>
            <div style="display:flex;gap:0.35rem;margin-top:0.35rem;">
                <button type="button" class="chatbot-action-btn chatbot-action-btn--secondary" id="cbCardBackAmenitiesBtn" style="flex:1;">
                    <i class="bi bi-houses me-1"></i> Change Amenities
                </button>
                ${hasGuest ? `
                <button type="button" class="chatbot-action-btn chatbot-action-btn--primary" id="cbCardProceedReviewBtn" style="flex:1;">
                    Review Booking <i class="bi bi-arrow-right ms-1"></i>
                </button>
                ` : ''}
            </div>
        `;

        card.querySelector('#cbOpenGuestModalBtn')?.addEventListener('click', () => {
            openGuestModal();
        });

        card.querySelector('#cbCardBackAmenitiesBtn')?.addEventListener('click', () => {
            triggerAmenitySelection();
        });

        card.querySelector('#cbCardProceedReviewBtn')?.addEventListener('click', () => {
            showBookingPicksSummary();
        });

        return card;
    };

    // Guest Info Submitted -> Advance to Step 4 (Review Summary)
    const onGuestInfoSubmitted = (name, phone, email, count) => {
        bookingDraft.guest = {
            name,
            phone,
            email,
            guests_count: count,
        };
        bookingDraft.step = 'review';
        saveBookingDraft();

        showBookingPicksSummary("Awesome! Your primary booker information is saved. Here is your final booking review before completing payment:");
    };

    // Step 4 / "Show Me What Amenity I Just Availed" Summary Card
    const showBookingPicksSummary = (customTitle = null) => {
        const list = bookingDraft.amenities && bookingDraft.amenities.length > 0
            ? bookingDraft.amenities
            : (bookingDraft.amenity ? [bookingDraft.amenity] : []);

        if (!bookingDraft || list.length === 0) {
            if (bookingDraft && bookingDraft.active && bookingDraft.startDate) {
                addMessage(`You haven't selected any amenities yet! Opening our live amenity catalog for ${bookingDraft.startDate} [${bookingDraft.startSlot || 'Daytime'}] 🌿`, true);
                triggerAmenitySelection();
                return;
            }
            addMessage("You haven't selected any amenities yet! Would you like to start a booking now? Type 'book now' or click 'Book via Chat'.", true);
            return;
        }

        const title = customTitle || "Here is the record of what you availed and selected for your stay:";
        const card = createSummaryReceiptCard();
        addCustomCardMessage(title, card);
    };

    // Create Summary / Receipt Card (Supports Multi-Amenity Breakdown)
    const createSummaryReceiptCard = () => {
        const card = document.createElement('div');
        card.className = 'chatbot-booking-card';

        const amenitiesList = bookingDraft.amenities && bookingDraft.amenities.length > 0
            ? bookingDraft.amenities
            : (bookingDraft.amenity ? [bookingDraft.amenity] : []);

        const guest = bookingDraft.guest || {};
        const totalAmount = calculateDraftTotal();
        const depositAmount = Math.max(roundToTwo(totalAmount * 0.5), totalAmount > 0 ? 20.00 : 0);
        const remainingBalance = Math.max(0, roundToTwo(totalAmount - depositAmount));

        const startFmt = formatDateReadable(bookingDraft.startDate);
        const endFmt = formatDateReadable(bookingDraft.endDate || bookingDraft.startDate);
        const scheduleStr = bookingDraft.startDate === (bookingDraft.endDate || bookingDraft.startDate)
            ? `${startFmt} [${bookingDraft.startSlot || bookingDraft.slot}]`
            : `Check-in: ${startFmt} [${bookingDraft.startSlot || 'Daytime'}] → Check-out: ${endFmt} [${bookingDraft.endSlot || 'Daytime'}]`;

        const stayMeta = `${bookingDraft.daysSpan || 1} Day(s) (${bookingDraft.dayCount || 1} Day session(s), ${bookingDraft.nightCount || 0} Night session(s))`;

        const amenityItemsHtml = amenitiesList.map(am => `
            <div class="chatbot-receipt__amenity-row">
                <span><i class="bi bi-tag text-emerald-600 me-1"></i> <strong>${escapeHtml(am.name)}</strong> (${am.minimum_capacity || 1}–${am.maximum_capacity || 10} pax)</span>
                <strong>₱${Number(am.price).toFixed(2)}</strong>
            </div>
        `).join('');

        card.innerHTML = `
            <div class="chatbot-card-header">
                <span><i class="bi bi-receipt text-emerald-600 me-1.5"></i> Booking Summary &amp; Record</span>
                <span class="chatbot-badge chatbot-badge--avail">Ready to Book</span>
            </div>
            <div class="chatbot-receipt">
                <div style="font-size:0.72rem;font-weight:700;color:#065f46;margin-bottom:0.25rem;">
                    Availed Amenities (${amenitiesList.length}):
                </div>
                <div class="chatbot-receipt__amenity-list">
                    ${amenityItemsHtml}
                </div>
                <div class="chatbot-receipt__row">
                    <span><i class="bi bi-calendar3 text-emerald-700 me-1"></i> Schedule:</span>
                    <span style="text-align:right;">${scheduleStr}</span>
                </div>
                <div class="chatbot-receipt__row">
                    <span><i class="bi bi-hourglass-split text-emerald-700 me-1"></i> Duration:</span>
                    <span>${stayMeta}</span>
                </div>
                <div class="chatbot-receipt__row">
                    <span><i class="bi bi-person text-emerald-700 me-1"></i> Booker:</span>
                    <span>${escapeHtml(guest.name || 'Guest')} (${guest.guests_count || 1} pax)</span>
                </div>
                <div class="chatbot-receipt__row">
                    <span><i class="bi bi-telephone text-emerald-700 me-1"></i> Contact:</span>
                    <span>${escapeHtml(guest.phone || '')}</span>
                </div>
                <div class="chatbot-receipt__row">
                    <span><i class="bi bi-envelope text-emerald-700 me-1"></i> Email:</span>
                    <span>${escapeHtml(guest.email || '')}</span>
                </div>
                <div class="chatbot-receipt__row">
                    <span><i class="bi bi-cash-stack text-emerald-700 me-1"></i> Total Stay Amount:</span>
                    <strong style="color:#047857;">₱${totalAmount.toFixed(2)}</strong>
                </div>
                <div class="chatbot-receipt__row chatbot-receipt__row--highlight">
                    <span><i class="bi bi-credit-card-2-front text-emerald-700 me-1"></i> Required 50% Downpayment:</span>
                    <span>₱${depositAmount.toFixed(2)}</span>
                </div>
                <div class="chatbot-receipt__row">
                    <span><i class="bi bi-wallet2 text-emerald-700 me-1"></i> Balance at Entrance:</span>
                    <span>₱${remainingBalance.toFixed(2)}</span>
                </div>
                <div class="chatbot-receipt__notice">
                    <i class="bi bi-exclamation-triangle-fill text-amber-500 me-1"></i> <strong>POLICY REMINDER:</strong> The 50% downpayment is <strong>STRICTLY NON-REFUNDABLE</strong> (no refund policy), but date rescheduling can be requested with prior notice.
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:0.35rem;margin-top:0.65rem;">
                <button type="button" class="chatbot-action-btn chatbot-action-btn--primary" id="cbProceedPaymentBtn">
                    <i class="bi bi-credit-card me-1.5"></i> Proceed to 50% Downpayment (₱${depositAmount.toFixed(2)})
                </button>
                <div style="display:flex;gap:0.35rem;flex-wrap:wrap;">
                    <button type="button" class="chatbot-action-btn chatbot-action-btn--secondary" id="cbChangeAmenityBtn" style="flex:1;">
                        <i class="bi bi-pencil-square me-1"></i> Change Amenities
                    </button>
                    <button type="button" class="chatbot-action-btn chatbot-action-btn--secondary" id="cbEditBookerBtn" style="flex:1;">
                        <i class="bi bi-person-gear me-1"></i> Edit Booker
                    </button>
                    <button type="button" class="chatbot-action-btn chatbot-action-btn--danger" id="cbCancelBookingBtn" style="width:auto;">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </button>
                </div>
            </div>
        `;

        const proceedBtn = card.querySelector('#cbProceedPaymentBtn');
        const changeBtn = card.querySelector('#cbChangeAmenityBtn');
        const editBookerBtn = card.querySelector('#cbEditBookerBtn');
        const cancelBtn = card.querySelector('#cbCancelBookingBtn');

        proceedBtn?.addEventListener('click', () => {
            proceedBtn.disabled = true;
            proceedBtn.textContent = 'Connecting to payment gateway…';
            initiateXenditPayment(proceedBtn);
        });

        changeBtn?.addEventListener('click', () => {
            triggerAmenitySelection();
        });

        editBookerBtn?.addEventListener('click', () => {
            openGuestModal();
        });

        cancelBtn?.addEventListener('click', () => {
            clearBookingDraft();
            addMessage("Booking mode cancelled. Your draft was cleared. Feel free to ask any other questions!", true);
        });

        return card;
    };

    // ─────────────────────────────────────────────────────────────
    // Xendit 50% Downpayment Integration (Multi-Amenity Array)
    // ─────────────────────────────────────────────────────────────

    // Step 5: Initiate Xendit Payment via create-intent (Supports Multi-Amenity Array)
    const initiateXenditPayment = async (actionButton) => {
        bookingDraft.step = 'payment';
        saveBookingDraft();

        const amenitiesList = bookingDraft.amenities && bookingDraft.amenities.length > 0
            ? bookingDraft.amenities
            : (bookingDraft.amenity ? [bookingDraft.amenity] : []);

        const isContinuous = (bookingDraft.startDate !== bookingDraft.endDate) || (bookingDraft.startSlot !== bookingDraft.endSlot);
        const slotType = isContinuous ? 'Continuous' : (bookingDraft.startSlot || bookingDraft.slot || 'Daytime');

        const payload = {
            booker_name: bookingDraft.guest.name,
            phone: bookingDraft.guest.phone,
            email: bookingDraft.guest.email,
            number_of_guests: bookingDraft.guest.guests_count,
            reservation_date: bookingDraft.startDate,
            end_date: bookingDraft.endDate || bookingDraft.startDate,
            start_slot: bookingDraft.startSlot || bookingDraft.slot || 'Daytime',
            end_slot: bookingDraft.endSlot || bookingDraft.slot || 'Daytime',
            check_in: bookingDraft.startDate,
            check_out: bookingDraft.endDate || bookingDraft.startDate,
            slot: slotType,
            total_days: bookingDraft.daysSpan || 1,
            amenities: amenitiesList.map(am => ({
                amenity_id: String(am.id),
                start_date: bookingDraft.startDate,
                end_date: bookingDraft.endDate || bookingDraft.startDate,
                start_slot: bookingDraft.startSlot || bookingDraft.slot || 'Daytime',
                end_slot: bookingDraft.endSlot || bookingDraft.slot || 'Daytime',
                pricing_type: slotType,
                price_at_booking: Number(am.price),
            }))
        };

        try {
            const response = await fetch('/reservation/create-intent', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json();

            if (response.ok && result.success) {
                bookingDraft.payment = {
                    payment_intent_id: result.payment_intent_id,
                    total_amount: result.total_amount,
                    deposit_amount: result.deposit_amount,
                    remaining_balance: result.remaining_balance,
                    status: 'pending',
                };
                saveBookingDraft();

                const paymentCard = createPaymentMethodCard(result);
                addCustomCardMessage(
                    `Your reservation session has been created! 🎉\n\nPlease select your preferred payment channel to pay the required **50% Downpayment (₱${Number(result.deposit_amount).toFixed(2)})**:`,
                    paymentCard
                );
            } else {
                if (actionButton) {
                    actionButton.disabled = false;
                    actionButton.textContent = 'Retry 50% Downpayment';
                }
                const msg = result.message || 'Could not initiate payment session.';
                addMessage(`⚠️ ${msg}`, true);
            }
        } catch (err) {
            console.error('Error in create-intent:', err);
            if (actionButton) {
                actionButton.disabled = false;
                actionButton.textContent = 'Retry 50% Downpayment';
            }
            addMessage('⚠️ Network error connecting to Xendit payment gateway. Please try again.', true);
        }
    };

    // Create Payment Methods Card (GCash, Maya, QR Ph)
    const createPaymentMethodCard = (intentData) => {
        const card = document.createElement('div');
        card.className = 'chatbot-booking-card';

        const deposit = Number(intentData.deposit_amount || 0).toFixed(2);

        card.innerHTML = `
            <div class="chatbot-card-header">
                <span><i class="bi bi-credit-card me-1 text-emerald-600"></i> Choose 50% Downpayment Method</span>
                <span class="font-bold text-emerald-600">₱${deposit}</span>
            </div>
            <div class="chatbot-payment-grid">
                <button type="button" class="chatbot-pay-btn chatbot-pay-btn--gcash" data-pay-method="gcash">
                    <span>Pay with GCash</span>
                    <span>₱${deposit} &rarr;</span>
                </button>
                <button type="button" class="chatbot-pay-btn chatbot-pay-btn--maya" data-pay-method="paymaya">
                    <span>Pay with Maya</span>
                    <span>₱${deposit} &rarr;</span>
                </button>
                <button type="button" class="chatbot-pay-btn chatbot-pay-btn--qrph" data-pay-method="qrph">
                    <span>Pay with QR Ph (Any Bank / E-Wallet)</span>
                    <span>₱${deposit} &rarr;</span>
                </button>
            </div>
            <div id="cbPaymentWaitingBox" hidden style="margin-top:0.6rem;text-align:center;">
                <div class="chatbot-typing" style="margin:0 auto 0.4rem;">
                    <span></span><span></span><span></span>
                </div>
                <p id="cbPaymentWaitingText" style="font-size:0.75rem;color:#047857;font-weight:600;">Processing payment…</p>
            </div>
        `;

        const buttons = card.querySelectorAll('[data-pay-method]');
        const waitingBox = card.querySelector('#cbPaymentWaitingBox');
        const waitingText = card.querySelector('#cbPaymentWaitingText');

        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                const method = btn.dataset.payMethod;
                buttons.forEach(b => b.disabled = true);
                if (waitingBox) waitingBox.hidden = false;
                if (waitingText) waitingText.textContent = `Connecting to ${method.toUpperCase()}…`;

                processXenditPayment(method, intentData.payment_intent_id, card);
            });
        });

        return card;
    };

    // Process payment with Xendit
    const processXenditPayment = async (methodType, paymentIntentId, cardElement) => {
        try {
            const response = await fetch('/reservation/process-payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    payment_intent_id: paymentIntentId,
                    payment_method_type: methodType,
                }),
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                addMessage(`⚠️ Payment initiation failed: ${result.message || 'Please try another method.'}`, true);
                return;
            }

            // Immediately succeeded (e.g. test simulator)
            if (result.status === 'succeeded') {
                handleSuccessfulBooking(result.reservation_id || paymentIntentId);
                return;
            }

            // QR Ph Flow: render the dynamic QR code directly inside chat!
            if (methodType === 'qrph' && result.qr_string) {
                const qrCard = document.createElement('div');
                qrCard.className = 'chatbot-qr-container';
                const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(result.qr_string)}`;

                qrCard.innerHTML = `
                    <div style="font-size:0.75rem;font-weight:700;color:#111827;margin-bottom:0.35rem;">
                        Scan QR Ph to Pay 50% Deposit
                    </div>
                    <img src="${qrUrl}" alt="QR Ph Payment Code" class="chatbot-qr-img">
                    <p class="chatbot-qr-caption">
                        Open GCash, Maya, ShopeePay, or your Mobile Banking app &rarr; Scan to pay <strong>₱${Number(bookingDraft.payment.deposit_amount).toFixed(2)}</strong>.
                    </p>
                    <div style="font-size:0.7rem;color:#047857;font-weight:600;margin-top:0.4rem;">
                        <i class="bi bi-arrow-repeat me-1 spin-icon"></i> Polling for automatic confirmation…
                    </div>
                `;

                addCustomCardMessage("Here is your dynamic QR Ph code. We'll automatically confirm your reservation as soon as payment is detected:", qrCard);
                startPaymentPolling(paymentIntentId);
                return;
            }

            // GCash / Maya flow: open redirect URL in popup window
            if (result.next_action?.redirect?.url) {
                const redirectUrl = result.next_action.redirect.url;
                const popup = window.open(
                    redirectUrl,
                    'hinaguan_xendit_payment',
                    'width=700,height=780,resizable=yes,scrollbars=yes,location=yes'
                );

                const listeningCard = document.createElement('div');
                listeningCard.className = 'chatbot-booking-card';
                listeningCard.innerHTML = `
                    <div style="font-size:0.8rem;font-weight:700;color:#065f46;margin-bottom:0.35rem;display:flex;align-items:center;gap:0.4rem;">
                        <span class="chatbot-booking-bar__pulse" aria-hidden="true"></span>
                        <span>Waiting for ${methodType.toUpperCase()} Confirmation…</span>
                    </div>
                    <p style="font-size:0.75rem;color:#374151;line-height:1.45;margin-bottom:0.5rem;">
                        Complete your 50% downpayment in the payment window that just opened. Don't close the chat &mdash; we are actively listening for confirmation and will confirm your reservation automatically once paid!
                    </p>
                    <div style="display:flex;flex-direction:column;gap:0.35rem;">
                        <button type="button" class="chatbot-action-btn chatbot-action-btn--primary" id="cbManualCheckPaymentBtn">
                            <i class="bi bi-arrow-repeat me-1.5"></i> I've Completed Payment (Check Now)
                        </button>
                        <a href="${redirectUrl}" target="_blank" class="chatbot-action-btn chatbot-action-btn--secondary" style="text-decoration:none;text-align:center;">
                            <i class="bi bi-box-arrow-up-right me-1.5"></i> Re-open Payment Window
                        </a>
                    </div>
                `;

                listeningCard.querySelector('#cbManualCheckPaymentBtn')?.addEventListener('click', async (e) => {
                    const btn = e.currentTarget;
                    btn.disabled = true;
                    btn.innerHTML = '<i class="bi bi-hourglass-split me-1.5"></i> Checking confirmation…';
                    await checkPaymentOnce(paymentIntentId);
                    setTimeout(() => {
                        if (!bookingSuccessCardRendered) {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="bi bi-arrow-repeat me-1.5"></i> Check Again';
                        }
                    }, 2000);
                });

                addCustomCardMessage(
                    `Complete your 50% downpayment in the payment window that just opened.\n\nDon't close the chat &mdash; we are actively listening for confirmation and will confirm your reservation automatically once paid!`,
                    listeningCard
                );

                startPaymentPolling(paymentIntentId);
            } else {
                startPaymentPolling(paymentIntentId);
            }
        } catch (err) {
            console.error('Error processing Xendit payment:', err);
            addMessage('Network error communicating with payment gateway.', true);
        }
    };

    // Listen for cross-window messages from Xendit payment return page
    window.addEventListener('message', (event) => {
        if (event.data && event.data.source === 'hinaguan-xendit') {
            if (event.data.status === 'success' && !bookingSuccessCardRendered) {
                const intentId = bookingDraft.payment?.payment_intent_id || event.data.ref_id;
                if (intentId) {
                    checkPaymentOnce(intentId);
                }
            }
        }
    });

    // Start payment polling with single-execution guard
    const startPaymentPolling = (paymentIntentId) => {
        stopPaymentPolling();
        if (bookingSuccessCardRendered || isPaymentConfirmed || !paymentIntentId) return;

        let pollCount = 0;
        const maxPolls = 120; // 5 minutes max

        // Immediate check once after 1.2s to pick up fast returns
        setTimeout(() => {
            if (!bookingSuccessCardRendered && !isPaymentConfirmed) {
                checkPaymentOnce(paymentIntentId);
            }
        }, 1200);

        paymentPollInterval = setInterval(async () => {
            if (bookingSuccessCardRendered || isPaymentConfirmed) {
                stopPaymentPolling();
                return;
            }

            pollCount++;
            if (pollCount > maxPolls) {
                stopPaymentPolling();
                addMessage("Payment session timed out. If you already completed payment, please check your email or contact park desk.", true);
                return;
            }

            await checkPaymentOnce(paymentIntentId);
        }, 2500);
    };

    const checkPaymentOnce = async (paymentIntentId) => {
        if (bookingSuccessCardRendered || isPaymentConfirmed || !paymentIntentId) {
            stopPaymentPolling();
            return;
        }
        if (isCheckingPayment) return;
        isCheckingPayment = true;

        try {
            const res = await fetch('/reservation/check-payment-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ payment_intent_id: paymentIntentId }),
            });

            const data = await res.json();

            if (res.ok && data.success) {
                if (data.status === 'succeeded' || data.payment_status === 'Partially Paid' || data.payment_status === 'Paid') {
                    stopPaymentPolling();
                    if (!bookingSuccessCardRendered) {
                        handleSuccessfulBooking(data.reservation_id);
                    }
                    return;
                } else if (['failed', 'cancelled', 'expired'].includes(data.status)) {
                    stopPaymentPolling();
                    addMessage(`⚠️ Payment was ${data.status}. No charge was completed. You can try again anytime.`, true);
                    return;
                }
            }
        } catch (e) {
            console.error('Polling error:', e);
        } finally {
            isCheckingPayment = false;
        }
    };

    // Step 6: Payment Success & Confirmed Reservation Card (Guaranteed Single Execution)
    const handleSuccessfulBooking = (reservationId) => {
        stopPaymentPolling();
        if (bookingSuccessCardRendered) return;
        bookingSuccessCardRendered = true;
        isPaymentConfirmed = true;

        // Snapshot details before resetting draft state
        const depositPaid = Number(bookingDraft?.payment?.deposit_amount || 0).toFixed(2);
        const balanceRemaining = Number(bookingDraft?.payment?.remaining_balance || 0).toFixed(2);
        const bookerName = bookingDraft?.guest?.name || 'Valued Guest';

        const amenitiesList = bookingDraft?.amenities && bookingDraft.amenities.length > 0
            ? [...bookingDraft.amenities]
            : (bookingDraft?.amenity ? [bookingDraft.amenity] : []);
        const amNames = amenitiesList.map(a => a.name).join(', ') || 'Selected Amenity';

        const startFmt = formatDateReadable(bookingDraft?.startDate);
        const endFmt = formatDateReadable(bookingDraft?.endDate || bookingDraft?.startDate);
        const scheduleStr = bookingDraft?.startDate === (bookingDraft?.endDate || bookingDraft?.startDate)
            ? `${startFmt} [${bookingDraft?.startSlot || bookingDraft?.slot || 'Daytime'}]`
            : `${startFmt} [${bookingDraft?.startSlot || 'Daytime'}] → ${endFmt} [${bookingDraft?.endSlot || 'Daytime'}]`;

        const successCard = document.createElement('div');
        successCard.className = 'chatbot-booking-card';
        successCard.style.borderColor = '#059669';
        successCard.style.background = 'linear-gradient(180deg, rgba(209, 250, 229, 0.5) 0%, rgba(240, 253, 244, 0.95) 100%)';

        successCard.innerHTML = `
            <div style="text-align:center;padding:0.4rem 0;">
                <div style="font-size:2rem;line-height:1;margin-bottom:0.35rem;color:#059669;"><i class="bi bi-patch-check-fill"></i></div>
                <h4 style="font-weight:800;font-size:1rem;color:#065f46;margin-bottom:0.2rem;">Reservation Confirmed!</h4>
                <p style="font-size:0.75rem;color:#047857;">Thank you, <strong>${escapeHtml(bookerName)}</strong>! Your 50% downpayment was received.</p>
            </div>
            <div class="chatbot-receipt" style="margin-top:0.35rem;">
                <div class="chatbot-receipt__row">
                    <span>Reference / ID:</span>
                    <strong>#${reservationId || 'HNP'}</strong>
                </div>
                <div class="chatbot-receipt__row">
                    <span>50% Downpayment Paid:</span>
                    <strong style="color:#059669;">₱${depositPaid} (Confirmed)</strong>
                </div>
                <div class="chatbot-receipt__row">
                    <span>Remaining Balance:</span>
                    <span>₱${balanceRemaining} (Pay at Entrance)</span>
                </div>
                <div class="chatbot-receipt__row">
                    <span>Schedule:</span>
                    <span style="text-align:right;">${scheduleStr}</span>
                </div>
                <div class="chatbot-receipt__row">
                    <span>Booked Amenities:</span>
                    <strong style="text-align:right;">${escapeHtml(amNames)}</strong>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:0.4rem;margin-top:0.65rem;">
                ${reservationId ? `
                <a href="/reservation/${reservationId}/download-pass" target="_blank" class="chatbot-action-btn chatbot-action-btn--primary" style="text-decoration:none;">
                    <i class="bi bi-qr-code me-1"></i> Download Digital QR Pass
                </a>
                <a href="/reservation/${reservationId}/download-receipt" target="_blank" class="chatbot-action-btn chatbot-action-btn--secondary" style="text-decoration:none;">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Download Receipt PDF
                </a>
                ` : ''}
                <button type="button" class="chatbot-action-btn chatbot-action-btn--secondary" id="cbDoneBookingBtn">
                    <i class="bi bi-check2-circle me-1"></i> Done &amp; Close Booking
                </button>
            </div>
        `;

        successCard.querySelector('#cbDoneBookingBtn')?.addEventListener('click', () => {
            clearBookingDraft();
            addMessage("You're all set! Present your digital pass upon arrival. Have a wonderful visit to Hinaguan Nature Park!", true);
        });

        addCustomCardMessage(
            "**PAYMENT SUCCESSFUL!** Your 50% downpayment has been confirmed and your booking is officially secured in our system.",
            successCard
        );

        // Clear draft from localStorage now that reservation is confirmed
        bookingDraft = getInitialDraft();
        localStorage.removeItem(DRAFT_KEY);
        updateBookingBarUI();
    };

    // Top Bar action buttons & Step navigation
    cbBarStep1?.addEventListener('click', () => {
        openDateModal();
    });

    cbBarStep2?.addEventListener('click', () => {
        triggerAmenitySelection();
    });

    cbBarStep3?.addEventListener('click', () => {
        openGuestModal();
    });

    cbBarStep4?.addEventListener('click', () => {
        if (!bookingDraft.startDate) {
            openDateModal();
        } else if (!bookingDraft.amenities || bookingDraft.amenities.length === 0) {
            triggerAmenitySelection();
        } else if (!bookingDraft.guest?.name) {
            openGuestModal();
        } else {
            showBookingPicksSummary();
        }
    });

    chatbotBarActionBtn?.addEventListener('click', () => {
        switch (bookingDraft.step) {
            case 'date_slot':
                openDateModal();
                break;
            case 'amenity':
                triggerAmenitySelection();
                break;
            case 'guest_info':
                openGuestModal();
                break;
            case 'review':
            case 'payment':
            case 'confirmed':
                showBookingPicksSummary();
                break;
            default:
                openDateModal();
                break;
        }
    });

    chatbotBarViewPickBtn?.addEventListener('click', () => {
        const amCount = bookingDraft.amenities ? bookingDraft.amenities.length : (bookingDraft.amenity ? 1 : 0);
        switch (bookingDraft.step) {
            case 'date_slot':
                openDateModal();
                break;
            case 'amenity':
                triggerAmenitySelection();
                break;
            case 'guest_info':
                openGuestModal();
                break;
            case 'review':
            case 'payment':
            case 'confirmed':
                showBookingPicksSummary();
                break;
            default:
                if (!bookingDraft.startDate) {
                    openDateModal();
                } else if (amCount === 0) {
                    triggerAmenitySelection();
                } else {
                    showBookingPicksSummary();
                }
                break;
        }
    });

    chatbotDockActionBtn?.addEventListener('click', () => {
        const amCount = bookingDraft.amenities ? bookingDraft.amenities.length : (bookingDraft.amenity ? 1 : 0);
        switch (bookingDraft.step) {
            case 'date_slot':
                openDateModal();
                break;
            case 'amenity':
                if (amCount > 0) {
                    openGuestModal();
                } else {
                    triggerAmenitySelection();
                }
                break;
            case 'guest_info':
                openGuestModal();
                break;
            case 'review':
            case 'payment':
            case 'confirmed':
                showBookingPicksSummary();
                break;
            default:
                openDateModal();
                break;
        }
    });

    chatbotBarCancelBtn?.addEventListener('click', () => {
        clearBookingDraft();
        addMessage("Booking mode cancelled. Your draft was cleared. Feel free to ask any other questions!", true);
    });

    // Delegated click listener for in-chat action buttons across deep conversation history
    document.addEventListener('click', (e) => {
        const cancelBtn = e.target.closest('#chatbotBarCancelBtn') || e.target.closest('#cbCancelBookingBtn');
        if (cancelBtn) {
            e.preventDefault();
            e.stopPropagation();
            clearBookingDraft();
            addMessage("Booking mode cancelled. Your draft was cleared. Feel free to ask any other questions!", true);
            return;
        }

        const dateBtn = e.target.closest('#cbOpenDateModalBtn');
        if (dateBtn) {
            e.preventDefault();
            openDateModal();
            return;
        }

        const amenityBtn = e.target.closest('#cbOpenModalBtn');
        if (amenityBtn) {
            e.preventDefault();
            triggerAmenitySelection();
            return;
        }

        const guestBtn = e.target.closest('#cbOpenGuestModalBtn');
        if (guestBtn) {
            e.preventDefault();
            openGuestModal();
            return;
        }

        const backAmenitiesBtn = e.target.closest('#cbCardBackAmenitiesBtn');
        if (backAmenitiesBtn) {
            e.preventDefault();
            triggerAmenitySelection();
            return;
        }

        const dockBtn = e.target.closest('#chatbotDockActionBtn');
        if (dockBtn) {
            e.preventDefault();
            const amCount = bookingDraft.amenities ? bookingDraft.amenities.length : (bookingDraft.amenity ? 1 : 0);
            if (bookingDraft.step === 'amenity') {
                if (amCount > 0) openGuestModal();
                else triggerAmenitySelection();
            } else if (bookingDraft.step === 'date_slot') {
                openDateModal();
            } else if (bookingDraft.step === 'guest_info') {
                openGuestModal();
            } else {
                showBookingPicksSummary();
            }
            return;
        }

        const viewPickBtn = e.target.closest('#chatbotBarViewPickBtn');
        if (viewPickBtn) {
            e.preventDefault();
            if (bookingDraft.step === 'amenity') {
                triggerAmenitySelection();
            } else if (bookingDraft.step === 'date_slot') {
                openDateModal();
            } else if (bookingDraft.step === 'guest_info') {
                openGuestModal();
            } else {
                showBookingPicksSummary();
            }
            return;
        }

        const confirmBookingBtn = e.target.closest('#cbConfirmStartBookingBtn');
        if (confirmBookingBtn) {
            e.preventDefault();
            awaitingBookingConfirmation = false;
            confirmBookingBtn.disabled = true;
            confirmBookingBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Starting Booking…';
            startBookingMode();
            return;
        }

        const declineBookingBtn = e.target.closest('#cbDeclineStartBookingBtn');
        if (declineBookingBtn) {
            e.preventDefault();
            awaitingBookingConfirmation = false;
            declineBookingBtn.disabled = true;
            addMessage("No problem at all! 😊 Feel free to ask about our cottages, payags, pool rates, day/night schedules, or park policies anytime.", true);
            return;
        }
    });

    // ─────────────────────────────────────────────────────────────
    // Form Input NLP / Message Interceptor
    // ─────────────────────────────────────────────────────────────

    // Check if input is asking about their pick
    const isShowPickQuery = (text) => {
        return /(?:show\s+(?:me\s+)?(?:what\s+(?:amenit(?:y|ies)?\s+)?i\s+(?:just\s+)?avail|my\s+(?:pick|picks|selection|draft|booking)|the\s+record)|what\s+did\s+i\s+pick|unsa\s+akong\s+gipili|patingin\s+ng\s+pinili\s*ko|view\s+pick)/i.test(text);
    };

    // Check if input is asking to open/reopen the amenity picker or view amenity catalog
    const isAmenityPickerQuery = (text) => {
        return /(?:(?:give|show|open|reopen|display|bring\s+up|launch|view|get)\s+(?:me\s+)?(?:the\s+)?(?:amenit(?:y|ies)?(?:\s+picker|\s+selector|\s+catalog|\s+list|\s+modal)?|cottages?|payags?|options)|amenit(?:y|ies)\s+picker|pick\s+(?:an\s+)?amenity|choose\s+(?:an\s+)?amenity|select\s+(?:an\s+)?amenity|pakita\s+ang\s+amenit|patingin\s+ng\s+amenit)/i.test(text);
    };

    // Check if input is starting booking
    const isStartBookingQuery = (text) => {
        return /(?:i\s+want\s+to\s+book|gusto\s+(?:ko|kami)\s+mag-?book|pwede\s+mag-?book|book\s+(?:a\s+)?(?:cottage|payag|a-?house|room|cabin|function\s*hall|reservation)|magpa-?reserve\s+ko|book\s+via\s+chat|\/book\b)/i.test(text)
            && !/(?:how\s+to|paano|unsaon)/i.test(text);
    };

    // Check if user replied affirmatively (yes, sure, sige, oo, etc.)
    const isAffirmativeQuery = (text) => {
        return /^(?:yes|yep|yeah|sure|ok|okay|sige|oo|opo|proceed|agree|let'?s\s+(?:book|go|start|do\s+it)|cge|g|game|yes\s+please|start|tara|pwede|sige\s+po)\b/i.test(text.trim());
    };

    // Check if user replied negatively (no, not yet, wait, etc.)
    const isNegativeQuery = (text) => {
        return /^(?:no|nope|not\s+yet|dili|ayaw|wag|wait|sandali|later|cancel|stop|ayaw\s+sa|dili\s+pa)\b/i.test(text.trim());
    };

    // Check if input is cancelling booking
    const isCancelBookingQuery = (text) => {
        return /(?:cancel\s+booking|reset\s+booking|stop\s+booking|exit\s+booking)/i.test(text);
    };

    // ─────────────────────────────────────────────────────────────
    // UI Interactions & Proactive Bubble
    // ─────────────────────────────────────────────────────────────
    const modelSelect = document.getElementById('chatbotModel');
    if (modelSelect) {
        if (selectedModel) modelSelect.value = selectedModel;
        if (!modelSelect.value || modelSelect.value !== 'openrouter/free') modelSelect.value = 'openrouter/free';
        selectedModel = modelSelect.value;
        saveState(isOpen, messages, selectedModel);
        modelSelect.addEventListener('change', (e) => {
            selectedModel = e.target.value;
            saveState(isOpen, messages, selectedModel);
        });
    }

    const modelSelector = document.getElementById('chatbotModelSelector');
    const modelHideBtn = document.getElementById('chatbotModelHide');
    modelHideBtn?.addEventListener('click', () => {
        if (modelSelector) modelSelector.hidden = true;
    });

    // Proactive greeting pop-up
    const chatbotProactiveBubble = document.getElementById('chatbotProactiveBubble');
    const proactiveCloseIcon = document.getElementById('proactiveCloseIcon');
    const proactiveBubbleCard = document.getElementById('proactiveBubbleCard');
    const storageKeyProactive = 'guest_chatbot_proactive_shown';
    let proactiveTimer = null;

    const dismissProactiveBubble = () => {
        if (!chatbotProactiveBubble || chatbotProactiveBubble.hidden) return;
        chatbotProactiveBubble.classList.add('is-hiding');
        setTimeout(() => {
            chatbotProactiveBubble.hidden = true;
            chatbotProactiveBubble.classList.remove('is-hiding');
        }, 280);
    };

    const showProactiveBubble = () => {
        if (!chatbotProactiveBubble || isOpen) return;
        chatbotProactiveBubble.hidden = false;
    };

    proactiveCloseIcon?.addEventListener('click', (e) => {
        e.stopPropagation();
        dismissProactiveBubble();
    });

    proactiveBubbleCard?.addEventListener('click', (e) => {
        if (e.target.closest('#proactiveCloseIcon')) return;
        dismissProactiveBubble();
        if (!isOpen) toggleChatbot();
    });

    const isHomepage = window.location.pathname === '/' || window.location.pathname === '' || !!document.getElementById('hpSiteHeader');
    if (isHomepage && !sessionStorage.getItem(storageKeyProactive)) {
        if (!isOpen) {
            proactiveTimer = setTimeout(() => {
                if (!isOpen && !sessionStorage.getItem(storageKeyProactive)) {
                    sessionStorage.setItem(storageKeyProactive, 'true');
                    showProactiveBubble();
                }
            }, 5000);
        } else {
            sessionStorage.setItem(storageKeyProactive, 'true');
        }
    }

    // Toggle chatbot window
    const toggleChatbot = () => {
        isOpen = !isOpen;
        chatbotWindow.hidden = !isOpen;
        chatbotWidget?.classList.toggle('is-open', isOpen);
        chatbotToggle.setAttribute('aria-expanded', isOpen);
        chatbotToggle.setAttribute('aria-label', isOpen ? 'Close chatbot' : 'Open chatbot');

        if (isOpen) {
            if (window.innerWidth <= 640 && window.visualViewport) {
                chatbotWindow.style.height = `${window.visualViewport.height}px`;
            }
            if (proactiveTimer) {
                clearTimeout(proactiveTimer);
                proactiveTimer = null;
            }
            sessionStorage.setItem(storageKeyProactive, 'true');
            dismissProactiveBubble();
            chatbotInput?.focus();
            setTimeout(() => {
                if (chatbotMessages) chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
            }, 50);
        } else {
            chatbotWindow.style.height = '';
        }

        saveState(isOpen, messages, selectedModel);
    };

    chatbotToggle?.addEventListener('click', toggleChatbot);
    chatbotClose?.addEventListener('click', toggleChatbot);

    // Delete conversation confirmation modal
    const chatbotClear = document.getElementById('chatbotClear');
    const chatbotDeleteModal = document.getElementById('chatbotDeleteModal');
    const chatbotCancelDelete = document.getElementById('chatbotCancelDelete');
    const chatbotConfirmDelete = document.getElementById('chatbotConfirmDelete');
    let conversationCleared = false;

    const openDeleteModal = () => {
        if (!chatbotDeleteModal) return;
        chatbotDeleteModal.hidden = false;
        chatbotCancelDelete?.focus();
    };

    const closeDeleteModal = () => {
        if (!chatbotDeleteModal) return;
        chatbotDeleteModal.hidden = true;
        chatbotInput?.focus();
    };

    chatbotClear?.addEventListener('click', openDeleteModal);
    chatbotCancelDelete?.addEventListener('click', closeDeleteModal);
    chatbotDeleteModal?.addEventListener('click', (e) => {
        if (e.target === chatbotDeleteModal) closeDeleteModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && chatbotDeleteModal && !chatbotDeleteModal.hidden) closeDeleteModal();
    });

    chatbotConfirmDelete?.addEventListener('click', () => {
        closeDeleteModal();
        conversationCleared = true;
        messages = [];
        clearBookingDraft();
        saveState(isOpen, messages, selectedModel);
        chatbotMessages.innerHTML = '';
        chatbotWidget.classList.remove('is-conversation');
        addMessage("Hello! I'm Bren, your Bren AI Assistant. I can help you with our amenities, rates, live availability, and online reservations. What would you like to know?", true, false);
        chatbotInput?.focus();
    });

    // Quick-reply chips
    document.querySelectorAll('[data-quick-reply]').forEach((chip) => {
        chip.addEventListener('click', () => {
            if (!chatbotInput || !chatbotForm) return;
            chatbotInput.value = chip.dataset.quickReply || '';
            if (typeof chatbotForm.requestSubmit === 'function') {
                chatbotForm.requestSubmit();
            } else {
                chatbotForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            }
        });
    });

    // Close on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (chatbotProactiveBubble && !chatbotProactiveBubble.hidden) dismissProactiveBubble();
            if (isOpen) toggleChatbot();
        }
    });

    // Typing indicator
    const showTyping = () => {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'chatbot-message chatbot-message--bot chatbot-message--typing';
        typingDiv.id = 'chatbotTyping';
        typingDiv.innerHTML = `
            <div class="chatbot-message__avatar">
                <img src="${getBotLogoUrl()}" alt="Hinaguan Logo" class="chatbot-avatar-img">
            </div>
            <div class="chatbot-message__body">
                <div class="chatbot-message__content">
                    <div class="chatbot-typing">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        `;
        chatbotMessages.appendChild(typingDiv);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    };

    const hideTyping = () => {
        const typingDiv = document.getElementById('chatbotTyping');
        if (typingDiv) typingDiv.remove();
    };

    // ─────────────────────────────────────────────────────────────
    // Form Submit Handler
    // ─────────────────────────────────────────────────────────────
    chatbotForm?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const rawMessage = chatbotInput.value.trim();
        if (!rawMessage) return;

        // Command to show/toggle AI Model selector
        if (rawMessage.toLowerCase() === '/aimodel') {
            chatbotInput.value = '';
            if (modelSelector) modelSelector.hidden = !modelSelector.hidden;
            return;
        }

        // Add user message to conversation
        addMessage(rawMessage, false);
        chatbotInput.value = '';
        chatbotWidget.classList.add('is-conversation');
        conversationCleared = false;

        // 1. Direct Interception: Show Picks ("show me what amenity i just avail before it book")
        if (isShowPickQuery(rawMessage)) {
            showBookingPicksSummary();
            return;
        }

        // 1.5 Direct Interception: Amenity Picker Query ("give me the amenity picker again", etc.)
        if (isAmenityPickerQuery(rawMessage)) {
            if (bookingDraft.active && bookingDraft.startDate) {
                addMessage(`Sure thing! Opening our live amenity catalog for ${bookingDraft.startDate} [${bookingDraft.startSlot || 'Daytime'}] 🌿`, true);
                triggerAmenitySelection();
                return;
            } else {
                startBookingMode();
                return;
            }
        }

        // 2. Direct Interception: Start Booking Mode (Asks for confirmation first)
        if (isStartBookingQuery(rawMessage)) {
            askBookingConfirmation();
            return;
        }

        // 2.5 Awaiting Booking Confirmation Answer ("yes", "sure", "sige", "oo", etc.)
        if (awaitingBookingConfirmation) {
            if (isAffirmativeQuery(rawMessage)) {
                awaitingBookingConfirmation = false;
                startBookingMode();
                return;
            } else if (isNegativeQuery(rawMessage)) {
                awaitingBookingConfirmation = false;
                addMessage("No problem at all! 😊 Take your time. Feel free to ask about our amenities, rates, swimming pool, or operating hours anytime.", true);
                return;
            }
            awaitingBookingConfirmation = false;
        }

        // 3. Direct Interception: Cancel Booking Mode
        if (isCancelBookingQuery(rawMessage)) {
            clearBookingDraft();
            addMessage("Booking mode has been cancelled. Your draft was cleared. How else may I assist you with Hinaguan Nature Park?", true);
            return;
        }

        // 4. Conversational Amenity Name Picking if in 'amenity' step
        if (bookingDraft.active && bookingDraft.step === 'amenity') {
            const lower = rawMessage.toLowerCase();
            // Check if user typed an amenity name like "cottage 1", "payag 2", "a-house 3"
            if (/(?:cottage|payag|a-?house|cabin|function\s*hall)\s*\d*/i.test(lower)) {
                showTyping();
                try {
                    const startSlot = bookingDraft.startSlot || bookingDraft.slot || 'Daytime';
                    const endSlot = bookingDraft.endSlot || bookingDraft.slot || 'Daytime';
                    const res = await fetch(`/guest-chatbot/booking/amenities?start_date=${bookingDraft.startDate}&end_date=${bookingDraft.endDate}&start_slot=${startSlot}&end_slot=${endSlot}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    hideTyping();

                    if (res.ok && data.success && Array.isArray(data.amenities)) {
                        // Find matching amenity
                        const match = data.amenities.find(a => lower.includes(a.name.toLowerCase()));
                        if (match) {
                            if (match.is_available) {
                                const currentList = (bookingDraft.amenities && bookingDraft.amenities.length > 0)
                                    ? [...bookingDraft.amenities]
                                    : [];
                                if (!currentList.some(a => String(a.id) === String(match.id))) {
                                    currentList.push(match);
                                }
                                onAmenitiesConfirmed(currentList);
                                return;
                            } else {
                                const availables = data.amenities.filter(a => a.is_available).map(a => a.name).join(', ');
                                addMessage(`⚠️ I checked our live system and **${match.name}** is already booked for this schedule.\n\nTo prevent booking conflicts, here are available options: ${availables}.`, true);
                                return;
                            }
                        }
                    }
                } catch (err) {
                    hideTyping();
                }
            }
        }

        // Default: Send to AI Concierge endpoint
        const currentModel = modelSelect ? modelSelect.value : 'openrouter/free';

        const historyPayload = messages
            .filter(m => !/(?:user\s+safety|response\s+safety)/i.test(m.content) && !m.content.includes("im confuse can you state it again"))
            .slice(-6)
            .map(m => ({
                role: m.isBot ? 'assistant' : 'user',
                content: m.content
            }));

        showTyping();
        chatbotSend.disabled = true;

        try {
            const response = await fetch('/guest-chatbot', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    message: rawMessage,
                    history: historyPayload,
                    model: currentModel,
                    booking_draft: bookingDraft,
                }),
            });

            const data = await response.json();
            hideTyping();

            if (conversationCleared) return;

            // Handle backend booking action
            if (data.booking_action === 'confirm_booking_prompt') {
                askBookingConfirmation();
                return;
            } else if (data.booking_action === 'enter_booking_mode' && !bookingDraft.active) {
                startBookingMode();
                return;
            } else if (data.booking_action === 'show_picks') {
                showBookingPicksSummary();
                return;
            } else if (data.booking_action === 'cancel_booking') {
                clearBookingDraft();
            }

            if (data.reply) {
                const msgEl = addMessage(data.reply, true);
                if (bookingDraft.active && bookingDraft.step === 'amenity' && msgEl) {
                    attachAmenityActionChips(msgEl, data.reply);
                }
            } else {
                addMessage('I apologize, but I encountered an error. Please try again.', true);
            }
        } catch (error) {
            console.error('Guest chatbot error:', error);
            hideTyping();
            if (conversationCleared) return;
            addMessage('I apologize, but the service is temporarily unavailable. Please try again later.', true);
        } finally {
            chatbotSend.disabled = false;
            chatbotInput?.focus();
        }
    });
});
