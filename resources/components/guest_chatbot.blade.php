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

    <!-- Centered Fullscreen Modal Backdrop -->
    <div class="chatbot-fullscreen-backdrop" id="chatbotFullscreenBackdrop" hidden></div>

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
                <button type="button" class="chatbot-fullscreen" id="chatbotFullscreen" aria-label="Expand to center modal" title="Expand to center modal">
                    <svg class="chatbot-fullscreen__icon--expand" id="chatbotExpandIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                    </svg>
                    <svg class="chatbot-fullscreen__icon--compress hidden" id="chatbotCompressIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 14h6m0 0v6m0-6L3 21m17-7h-6m0 0v6m0-6l7 7M10 4v6m0 0H4m6 0L3 3m10 7h6m-6 0V4m0 6l7-7"/>
                    </svg>
                </button>
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

        <!-- Docked Bottom Booking Action Bar (Always visible above chat input when in booking mode) -->
        <div class="chatbot-dock hidden border-t border-emerald-200/90 bg-emerald-50/95 dark:border-emerald-900/60 dark:bg-emerald-950/95 px-3 py-1.5 flex items-center justify-between gap-2 shadow-sm transition-all" id="chatbotDock">
            <div class="flex flex-col justify-center min-w-0 pr-1">
                <div class="flex items-center gap-1 leading-none mb-0.5">
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse flex-shrink-0"></span>
                    <span class="text-[9px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400" id="chatbotDockBadgeText">Booking Mode</span>
                </div>
                <span class="font-semibold text-xs text-emerald-950 dark:text-emerald-100 truncate" id="chatbotDockSchedule">Step 1: Pick Dates</span>
            </div>
            <div class="flex items-center gap-1.5 flex-shrink-0">
                <button type="button" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-[11px] shadow-sm transition-colors flex items-center gap-1 cursor-pointer" id="chatbotDockActionBtn">
                    <i class="bi bi-calendar3 text-[12px]" id="chatbotDockActionIcon"></i> <span id="chatbotDockActionText">Pick Dates</span>
                </button>
                <button type="button" class="px-2 py-1 rounded-lg bg-white/90 hover:bg-red-50 text-red-600 hover:text-red-700 border border-red-200/80 dark:bg-zinc-800 dark:hover:bg-red-950/40 dark:text-red-400 dark:border-red-900/60 font-semibold text-[11px] shadow-xs transition-colors flex items-center gap-1 cursor-pointer" id="chatbotDockCancelBtn" title="Cancel booking mode">
                    <i class="bi bi-x-circle text-[11px]"></i> <span>Cancel</span>
                </button>
            </div>
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
<!-- Step 0 Modal: Terms & Conditions and Agreement Modal -->
<div class="fixed inset-0 z-[100000] flex items-center justify-center bg-slate-950/70 backdrop-blur-md p-3 sm:p-5 hidden" id="chatbotTermsModal">
    <div class="relative w-full max-w-3xl max-h-[92vh] bg-white dark:bg-slate-900 rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-emerald-100 dark:border-slate-800" role="dialog" aria-modal="true" aria-labelledby="cbTermsModalTitle">
        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/95 dark:bg-slate-800/90 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-lg shrink-0">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-bold text-emerald-950 dark:text-emerald-300 m-0" id="cbTermsModalTitle">Terms &amp; Conditions and Park Policies</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 m-0">Please review and agree to our reservation policies before booking</p>
                </div>
            </div>
            <button type="button" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-700 transition cursor-pointer" id="cbTermsModalClose" aria-label="Close Terms &amp; Conditions" title="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Body / Content (10 Categories) -->
        <div class="p-5 sm:p-6 overflow-y-auto flex-1 min-h-0 space-y-5 text-slate-700 dark:text-slate-300 text-sm leading-relaxed" id="cbTermsModalBody">
            {{-- 1. Booking and Reservation Policy --}}
            <section class="cb-terms-section">
                <h4 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-700 pb-1.5 mb-2 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-xs font-bold">1</span>
                    Booking and Reservation Policy
                </h4>
                <ul class="list-disc pl-5 space-y-1.5 text-xs text-slate-600 dark:text-slate-300">
                    <li><strong class="text-slate-900 dark:text-white">Instant Reservation Confirmation:</strong> Your booking is instantly locked upon completion of the reservation procedure and downpayment submission.</li>
                    <li><strong class="text-slate-900 dark:text-white">Advance Reservations:</strong> Advance online booking is strongly encouraged to guarantee slot availability for cottages, huts, overnight stays, and private amenities.</li>
                    <li><strong class="text-slate-900 dark:text-white">Accurate Guest Information:</strong> Guests must supply valid, verifiable contact numbers, full names, and active email addresses to receive check-in passes and reservation notices.</li>
                    <li><strong class="text-slate-900 dark:text-white">Amenity Capacity Limits:</strong> Each park amenity has an enforced maximum capacity. Any extra visitors beyond the listed limit must be declared and settled at the entrance counter upon arrival.</li>
                </ul>
            </section>

            {{-- 2. Payment Policy --}}
            <section class="cb-terms-section">
                <h4 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-700 pb-1.5 mb-2 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-xs font-bold">2</span>
                    Payment Policy
                </h4>
                <ul class="list-disc pl-5 space-y-1.5 text-xs text-slate-600 dark:text-slate-300">
                    <li><strong class="text-slate-900 dark:text-white">50% Downpayment Required:</strong> A non-refundable 50% initial downpayment is mandatory to lock and guarantee your reservation slot.</li>
                    <li><strong class="text-slate-900 dark:text-white">Remaining 50% Balance:</strong> The remaining 50% balance must be settled upon arrival at the park entrance counter before access to reserved amenities is permitted.</li>
                    <li><strong class="text-slate-900 dark:text-white">Payment Channels:</strong> Online downpayments may be settled via GCash or supported bank transfers. Front-desk payments accept cash and verified e-wallets.</li>
                    <li><strong class="text-slate-900 dark:text-white">Proof of Payment:</strong> Guests paying online must enter the correct reference number and provide a legible payment screenshot for validation.</li>
                </ul>
            </section>

            {{-- 3. Cancellation Policy --}}
            <section class="cb-terms-section">
                <h4 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-700 pb-1.5 mb-2 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-xs font-bold">3</span>
                    Cancellation Policy
                </h4>
                <ul class="list-disc pl-5 space-y-1.5 text-xs text-slate-600 dark:text-slate-300">
                    <li><strong class="text-slate-900 dark:text-white">Strictly No Refund:</strong> All deposits, downpayments, and reservation fees are strictly final and non-refundable under normal booking conditions.</li>
                    <li><strong class="text-slate-900 dark:text-white">Rescheduling Requests:</strong> Rescheduling is allowed if requested at least forty-eight (48) hours prior to the reserved visit date, subject to slot availability and park management approval.</li>
                    <li><strong class="text-slate-900 dark:text-white">No-Show Policy:</strong> Guests who fail to arrive on their scheduled visit date without prior notice automatically forfeit their slot and deposit.</li>
                    <li><strong class="text-slate-900 dark:text-white">Force Majeure:</strong> In events of severe storms, typhoons, or natural calamities declared by local authorities, bookings may be rescheduled without extra penalty.</li>
                </ul>
            </section>

            {{-- 4. Check-in and Check-out Policy --}}
            <section class="cb-terms-section">
                <h4 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-700 pb-1.5 mb-2 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-xs font-bold">4</span>
                    Check-in and Check-out Policy
                </h4>
                <ul class="list-disc pl-5 space-y-1.5 text-xs text-slate-600 dark:text-slate-300">
                    <li><strong class="text-slate-900 dark:text-white">Park Visiting Hours:</strong> Daytime visits run from {{ $daytimeStartFormatted ?? '8:00 AM' }} to {{ $daytimeEndFormatted ?? '5:00 PM' }}. Overnight stays begin at {{ $nighttimeStartFormatted ?? '6:00 PM' }} with check-out at {{ $nighttimeEndFormatted ?? '8:00 AM' }} the following morning.</li>
                    <li><strong class="text-slate-900 dark:text-white">QR Pass &amp; Valid ID:</strong> Guests must present their generated Booking QR Code (digital or printed) along with at least one valid government or student ID at the entrance counter for express check-in.</li>
                    <li><strong class="text-slate-900 dark:text-white">Holding Period:</strong> Reserved facilities will be held for up to two (2) hours from the scheduled arrival time. Please notify the park in advance for late arrivals.</li>
                </ul>
            </section>

            {{-- 5. Guest Responsibilities --}}
            <section class="cb-terms-section">
                <h4 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-700 pb-1.5 mb-2 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-xs font-bold">5</span>
                    Guest Responsibilities
                </h4>
                <ul class="list-disc pl-5 space-y-1.5 text-xs text-slate-600 dark:text-slate-300">
                    <li><strong class="text-slate-900 dark:text-white">Clean As You Go (CLAYGO):</strong> Guests are requested to practice CLAYGO. Segregate and dispose of all food leftovers, bottles, and trash into labeled waste bins before departure.</li>
                    <li><strong class="text-slate-900 dark:text-white">Supervision of Minors:</strong> Minors and children must be supervised by a responsible adult at all times, especially near swimming pools and riverbanks.</li>
                    <li><strong class="text-slate-900 dark:text-white">Respectful Conduct:</strong> Courtesy and respect towards fellow park visitors, nature, and park personnel are required. Harassment or violent misconduct is cause for immediate ejection.</li>
                </ul>
            </section>

            {{-- 6. Property Damage and Accountability --}}
            <section class="cb-terms-section">
                <h4 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-700 pb-1.5 mb-2 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-xs font-bold">6</span>
                    Property Damage and Accountability
                </h4>
                <ul class="list-disc pl-5 space-y-1.5 text-xs text-slate-600 dark:text-slate-300">
                    <li><strong class="text-slate-900 dark:text-white">Damage Liability:</strong> Guests are held financially accountable for any damage, loss, or breakage caused to park property, furniture, or rental gear due to negligence or misuse.</li>
                    <li><strong class="text-slate-900 dark:text-white">Personal Belongings:</strong> Hinaguan Nature Park management is not liable for lost, stolen, or unattended personal items. Please safeguard your belongings.</li>
                    <li><strong class="text-slate-900 dark:text-white">Item Turnover:</strong> All rented park utensils, cooking equipment, sports materials, or life jackets must be returned in good condition.</li>
                </ul>
            </section>

            {{-- 7. Facility and Safety Rules --}}
            <section class="cb-terms-section">
                <h4 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-700 pb-1.5 mb-2 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-xs font-bold">7</span>
                    Facility and Safety Rules
                </h4>
                <ul class="list-disc pl-5 space-y-1.5 text-xs text-slate-600 dark:text-slate-300">
                    <li><strong class="text-slate-900 dark:text-white">Proper Swimming Pool Attire:</strong> Proper swimwear (rash guards, swim trunks, bathing suits) is required when entering swimming pools. Cotton shirts, denim pants, and undergarments are strictly prohibited in the pool.</li>
                    <li><strong class="text-slate-900 dark:text-white">Outside Food &amp; Corkage:</strong> Guests may bring outside food and non-alcoholic beverages with zero corkage fee. Free outdoor grilling stations are available (please bring your own charcoal and utensils).</li>
                    <li><strong class="text-slate-900 dark:text-white">Quiet Hours:</strong> Quiet hours are observed from 10:00 PM to 6:00 AM for the peace and comfort of overnight guests and nature. High-volume sound systems must be lowered.</li>
                    <li><strong class="text-slate-900 dark:text-white">Pet Policy:</strong> Pets are allowed but must remain leashed and supervised at all times. Owners must clean up after their pets immediately.</li>
                    <li><strong class="text-slate-900 dark:text-white">Designated Smoking Areas:</strong> Smoking and vaping are only permitted in designated outdoor smoking zones away from cottages and pools.</li>
                </ul>
            </section>

            {{-- 8. Weather and Riverfront Booking Policy --}}
            <section class="cb-terms-section">
                <h4 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-700 pb-1.5 mb-2 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-xs font-bold">8</span>
                    Weather and Riverfront Booking Policy
                </h4>
                <ul class="list-disc pl-5 space-y-1.5 text-xs text-slate-600 dark:text-slate-300">
                    <li><strong class="text-slate-900 dark:text-white">Riverfront Safety:</strong> River water levels and currents vary according to rainfall. Park management reserves the right to temporarily close river access during rapid water surges for safety.</li>
                    <li><strong class="text-slate-900 dark:text-white">Rain or Shine Operations:</strong> Outdoor amenities operate rain or shine unless severe flood alerts or official typhoon warnings are issued.</li>
                    <li><strong class="text-slate-900 dark:text-white">Life Vests:</strong> Use of personal flotation devices or life vests is strongly encouraged for non-swimmers and children near the river.</li>
                </ul>
            </section>

            {{-- 9. Privacy and Personal Information --}}
            <section class="cb-terms-section">
                <h4 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-700 pb-1.5 mb-2 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-xs font-bold">9</span>
                    Privacy and Personal Information
                </h4>
                <ul class="list-disc pl-5 space-y-1.5 text-xs text-slate-600 dark:text-slate-300">
                    <li><strong class="text-slate-900 dark:text-white">Data Privacy Compliance:</strong> In accordance with Republic Act 10173 (Data Privacy Act of 2012), guest information is gathered exclusively for reservation processing, QR pass generation, and park safety communication.</li>
                    <li><strong class="text-slate-900 dark:text-white">Confidentiality:</strong> Your personal information will never be shared, sold, or disclosed to unauthorized third parties without explicit consent.</li>
                    <li><strong class="text-slate-900 dark:text-white">Data Security:</strong> Reasonable administrative and technical security measures are deployed to safeguard your personal details from unauthorized access.</li>
                </ul>
            </section>

            {{-- 10. System Usage Rules --}}
            <section class="cb-terms-section">
                <h4 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-700 pb-1.5 mb-2 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-xs font-bold">10</span>
                    System Usage Rules
                </h4>
                <ul class="list-disc pl-5 space-y-1.5 text-xs text-slate-600 dark:text-slate-300">
                    <li><strong class="text-slate-900 dark:text-white">Legitimate Bookings Only:</strong> Creating bogus, spam, or speculative reservations without intention of payment will result in immediate IP banning and customer blacklisting.</li>
                    <li><strong class="text-slate-900 dark:text-white">Fair Use &amp; Security:</strong> Attempting to tamper with reservation forms, bypass security validations, or use automated bots is illegal and strictly prohibited.</li>
                    <li><strong class="text-slate-900 dark:text-white">Price &amp; Information Accuracy:</strong> While we endeavor to maintain up-to-date data, park management reserves the right to resolve any clerical errors in pricing or availability prior to booking confirmation.</li>
                </ul>
            </section>
        </div>

        <!-- Footer -->
        <div class="px-5 py-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/95 dark:bg-slate-800/90 shrink-0 flex flex-col sm:flex-row items-center justify-between gap-3" id="cbTermsModalFooter">
            <label class="inline-flex items-center gap-2.5 cursor-pointer select-none text-xs sm:text-sm font-medium text-slate-800 dark:text-slate-200 w-full sm:w-auto" for="cbAgreeTermsCheckbox">
                <input type="checkbox" id="cbAgreeTermsCheckbox" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300 dark:border-slate-700 dark:bg-slate-800 cursor-pointer">
                <span>I have read, understood, and agree to the <strong>Terms &amp; Conditions</strong>.</span>
            </label>

            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <button type="button" id="cbCancelTermsBtn" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-slate-600 hover:text-slate-800 dark:text-slate-300 dark:hover:text-white hover:bg-slate-200/70 dark:hover:bg-slate-800 transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" id="cbProceedTermsBtn" class="px-5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-emerald-700 hover:bg-emerald-800 text-white shadow-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-emerald-700 transition flex items-center gap-2 cursor-pointer" disabled>
                    <span>I Agree &amp; Continue</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

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

