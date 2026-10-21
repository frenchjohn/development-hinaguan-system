<!-- Guest Chatbot Widget -->
<div class="chatbot-widget" id="chatbotWidget" data-logo="{{ asset('storage/design_images/main_logo.jpeg') }}">

    <!-- Minimal AI Text Bubble Pop-up -->
    <aside class="chatbot-proactive-bubble chatbot-proactive-bubble--simple" id="chatbotProactiveBubble" hidden aria-live="polite" role="dialog" aria-label="Chatbot Greeting">
        <div class="chatbot-proactive-bubble__card chatbot-proactive-bubble__card--simple" id="proactiveBubbleCard" role="button" tabindex="0" title="Click to chat">
            <span class="chatbot-proactive-bubble__simple-text"><i class="bi bi-chat-dots-fill text-emerald-600 me-1"></i> Need assistance? Ask Bren!</span>
            <button type="button" class="chatbot-proactive-bubble__close-icon" id="proactiveCloseIcon" aria-label="Dismiss message" title="Dismiss">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="chatbot-proactive-bubble__tail" aria-hidden="true"></div>
    </aside>

    <button class="chatbot-toggle" id="chatbotToggle" aria-label="Open chatbot" aria-expanded="false">
        <span class="chatbot-toggle__label">Bren AI Assistant</span>
        <div class="chatbot-toggle__icon chatbot-toggle__icon--chat chatbot-toggle__logo-wrap">
            <img src="{{ asset('storage/design_images/main_logo.jpeg') }}" alt="Hinaguan Nature Park Logo" class="chatbot-toggle__logo-img">
        </div>
        <svg class="chatbot-toggle__icon chatbot-toggle__icon--close" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>

    <div class="chatbot-window" id="chatbotWindow" hidden>
        <div class="chatbot-header">
            <div class="chatbot-header__content">
                <div class="chatbot-avatar">
                    <img src="{{ asset('storage/design_images/main_logo.jpeg') }}" alt="Hinaguan Nature Park logo">
                    <span class="chatbot-avatar__status" aria-hidden="true"></span>
                </div>
                <div>
                    <h4 class="chatbot-header__title">Bren</h4>
                    <p class="chatbot-header__subtitle"><span class="chatbot-header__dot" aria-hidden="true"></span> Bren AI Assistant &middot; Online</p>
                </div>
            </div>
            <div class="chatbot-header__actions">
                <button type="button" class="chatbot-clear" id="chatbotClear" aria-label="Delete conversation" title="Delete conversation">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
                <button class="chatbot-close" id="chatbotClose" aria-label="Close chatbot">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Conversational Booking Mode Bar -->
        <div class="chatbot-booking-bar" id="chatbotBookingBar" hidden>
            <div class="chatbot-booking-bar__content">
                <span class="chatbot-booking-bar__tag"><span class="chatbot-booking-bar__pulse" aria-hidden="true"></span> Booking Mode</span>
                <span class="chatbot-booking-bar__desc" id="chatbotBookingBarDesc">Setting up your reservation</span>
            </div>
            <div class="chatbot-booking-bar__actions">
                <button type="button" class="chatbot-booking-bar__btn chatbot-booking-bar__btn--pick" id="chatbotBarViewPickBtn" title="View your current selection"><i class="bi bi-card-checklist me-1"></i> My Selection</button>
                <button type="button" class="chatbot-booking-bar__btn chatbot-booking-bar__btn--exit" id="chatbotBarCancelBtn" title="Exit booking mode"><i class="bi bi-x-circle me-1"></i> Cancel</button>
            </div>
        </div>

        <div class="chatbot-quick" id="chatbotQuick">
            <span class="chatbot-quick__label">Ask Bren</span>
            <div class="chatbot-quick__chips">
                <button type="button" class="chatbot-chip chatbot-chip--booking" data-quick-reply="I want to book an online reservation"><i class="bi bi-calendar-plus me-1 text-emerald-600"></i> Book via Chat</button>
                <button type="button" class="chatbot-chip" data-quick-reply="What amenities are available today and what are their rates?">Available Amenities</button>
                <button type="button" class="chatbot-chip" data-quick-reply="Suggest the best amenity for our group of 10 people">Suggest for 10 Pax</button>
                <button type="button" class="chatbot-chip" data-quick-reply="How do I book an amenity online?">How to Book Online</button>
                <button type="button" class="chatbot-chip" data-quick-reply="What are the entrance fees and operating sessions?">Entrance Fees &amp; Hours</button>
            </div>
        </div>

        <div class="chatbot-messages" id="chatbotMessages">
            <div class="chatbot-message chatbot-message--bot">
                <div class="chatbot-message__avatar">
                    <img src="{{ asset('storage/design_images/main_logo.jpeg') }}" alt="Hinaguan Logo" class="chatbot-avatar-img">
                </div>
                <div class="chatbot-message__body">
                    <div class="chatbot-message__meta">
                        <span class="chatbot-message__author">Bren</span>
                    </div>
                    <div class="chatbot-message__content">
                        <p>Hello and welcome to <strong>Hinaguan Nature Park</strong>! I am <strong>Bren</strong>, your <strong>Bren AI Assistant</strong>. I can suggest the best amenities for your group size, check live availability &amp; expected checkout times, and guide you through online or walk-in booking. How may I help you today?</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="chatbot-model-selector" id="chatbotModelSelector" hidden>
            <svg class="chatbot-model-selector__icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4M4 19h4M13 3l2.5 6.5L22 12l-6.5 2.5L13 21l-2.5-6.5L4 12l6.5-2.5L13 3z"/>
            </svg>
            <label for="chatbotModel" class="chatbot-model-label">AI Model</label>
            <select id="chatbotModel" class="chatbot-model-select">
                <option value="openrouter/free" selected>OpenRouter Free (Auto)</option>
            </select>
            <button type="button" class="chatbot-model-selector__hide" id="chatbotModelHide" aria-label="Hide AI Model Selector" title="Hide AI Model Selector">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="chatbot-input-wrapper">
            <form class="chatbot-form" id="chatbotForm">
                <input
                    type="text"
                    class="chatbot-input"
                    id="chatbotInput"
                    placeholder="Type your message…"
                    autocomplete="off"
                    aria-label="Chat message input"
                >
                <button type="submit" class="chatbot-send" id="chatbotSend" aria-label="Send message">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </form>
            <p class="chatbot-input-wrapper__hint">AI may occasionally get things wrong &mdash; please verify details with the park.</p>
        </div>

        <!-- Delete Conversation Confirmation Modal -->
        <div class="chatbot-modal-overlay" id="chatbotDeleteModal" hidden>
            <div class="chatbot-modal" role="dialog" aria-modal="true" aria-labelledby="guestChatbotDeleteTitle">
                <div class="chatbot-modal__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h4 class="chatbot-modal__title" id="guestChatbotDeleteTitle">Delete Conversation</h4>
                <p class="chatbot-modal__text">Are you sure you want to delete the conversation? You won't be able to retrieve it again.</p>
                <div class="chatbot-modal__actions">
                    <button type="button" class="chatbot-modal__btn chatbot-modal__btn--cancel" id="chatbotCancelDelete">Cancel</button>
                    <button type="button" class="chatbot-modal__btn chatbot-modal__btn--danger" id="chatbotConfirmDelete">Yes, Delete</button>
                </div>
            </div>
        </div>
    </div> <!-- /.chatbot-window -->
</div> <!-- /.chatbot-widget -->

<!-- Modals outside the chatbox, centered directly in the browser viewport using Tailwind CSS -->
<!-- Step 1 Modal: Date & Session Range Selection Modal -->
<div class="fixed inset-0 z-[100000] flex items-center justify-center bg-slate-950/70 backdrop-blur-md p-3 sm:p-5 hidden" id="chatbotDateModal">
    <div class="relative w-full max-w-4xl max-h-[92vh] bg-white dark:bg-slate-900 rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-emerald-100 dark:border-slate-800" role="dialog" aria-modal="true" aria-labelledby="chatbotDateModalTitle">
        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/95 dark:bg-slate-800/90 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-lg shrink-0">
                    <i class="bi bi-calendar3"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-bold text-emerald-950 dark:text-emerald-300 m-0" id="chatbotDateModalTitle">Step 1: Select Dates &amp; Sessions</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 m-0" id="chatbotDateModalSubtitle">Select continuous stay dates (up to 5 years ahead)</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="hidden sm:inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800" id="cbCalModalCurrent">Current: Oct 20, 2026</span>
                <button type="button" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer" id="cbDateModalClose" aria-label="Close calendar" title="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        <div class="p-4 sm:p-5 overflow-y-auto flex-1 min-h-0 space-y-4" id="cbDateModalBody"></div>
        <div class="px-5 py-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/95 dark:bg-slate-800/90 shrink-0" id="cbDateModalFooter"></div>
    </div>
</div>

<!-- Step 2 Modal: Amenity Selection Modal -->
<div class="fixed inset-0 z-[100000] flex items-center justify-center bg-slate-950/70 backdrop-blur-md p-3 sm:p-5 hidden" id="chatbotAmenityModal">
    <div class="relative w-full max-w-4xl max-h-[92vh] bg-white dark:bg-slate-900 rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-emerald-100 dark:border-slate-800" role="dialog" aria-modal="true" aria-labelledby="chatbotAmenityModalTitle">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/95 dark:bg-slate-800/90 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-lg shrink-0">
                    <i class="bi bi-houses"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-bold text-emerald-950 dark:text-emerald-300 m-0" id="chatbotAmenityModalTitle">Step 2: Choose Amenities</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 m-0" id="chatbotAmenityModalSubtitle">Select one or more cottages, payags, or halls for your stay</p>
                </div>
            </div>
            <button type="button" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer" id="cbAmenityModalClose" aria-label="Close modal" title="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="p-4 sm:p-5 overflow-y-auto flex-1 min-h-0 space-y-4" id="cbAmenityModalBody"></div>
        <div class="px-5 py-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/95 dark:bg-slate-800/90 shrink-0" id="cbAmenityModalFooter"></div>
    </div>
</div>

<!-- Step 3 Modal: Primary Booker Information Modal -->
<div class="fixed inset-0 z-[100000] flex items-center justify-center bg-slate-950/70 backdrop-blur-md p-3 sm:p-5 hidden" id="chatbotGuestModal">
    <div class="relative w-full max-w-2xl max-h-[92vh] bg-white dark:bg-slate-900 rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-emerald-100 dark:border-slate-800" role="dialog" aria-modal="true" aria-labelledby="chatbotGuestModalTitle">
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/95 dark:bg-slate-800/90 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-lg shrink-0">
                    <i class="bi bi-person-vcard"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-bold text-emerald-950 dark:text-emerald-300 m-0" id="chatbotGuestModalTitle">Step 3: Primary Booker Information</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 m-0" id="chatbotGuestModalSubtitle">Enter details for your reservation pass &amp; booking confirmation</p>
                </div>
            </div>
            <button type="button" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer" id="cbGuestModalClose" aria-label="Close guest information modal" title="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="p-4 sm:p-5 overflow-y-auto flex-1 min-h-0 space-y-4" id="cbGuestModalBody"></div>
        <div class="px-5 py-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/95 dark:bg-slate-800/90 shrink-0" id="cbGuestModalFooter"></div>
    </div>
</div>

