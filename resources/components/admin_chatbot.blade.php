<!-- Admin Chatbot Widget -->
<div class="chatbot-widget chatbot-widget--admin" id="chatbotWidget" data-user-id="{{ session('auth_user.id', 0) }}">

    <!-- Responsive AI Proactive Speech Bubble Pop-up -->
    <aside class="chatbot-proactive-bubble" id="chatbotProactiveBubble" hidden aria-live="polite" role="dialog" aria-label="Admin AI Proactive Notice">
        <div class="chatbot-proactive-bubble__card">
            <div class="chatbot-proactive-bubble__header">
                <div class="chatbot-proactive-bubble__avatar">
                    <img src="{{ asset('storage/design_images/main_logo.jpeg') }}" alt="Hinaguan Admin AI">
                    <span class="chatbot-proactive-bubble__pulse" aria-hidden="true"></span>
                </div>
                <div class="chatbot-proactive-bubble__meta">
                    <span class="chatbot-proactive-bubble__name">Admin Intelligence</span>
                    <span class="chatbot-proactive-bubble__tag" id="proactiveHeadline">Notice</span>
                </div>
                <button type="button" class="chatbot-proactive-bubble__close-icon" id="proactiveCloseIcon" aria-label="Dismiss message" title="Dismiss">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="chatbot-proactive-bubble__body">
                <p class="chatbot-proactive-bubble__message" id="proactiveMessageText"></p>
                <p class="chatbot-proactive-bubble__followup" id="proactiveFollowupText"></p>
            </div>

            <div class="chatbot-proactive-bubble__actions">
                <button type="button" class="chatbot-proactive-bubble__action-btn" id="proactiveActionBtn">
                    <span id="proactiveActionLabel">Briefing</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" class="chatbot-proactive-bubble__action-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/>
                    </svg>
                </button>
                <button type="button" class="chatbot-proactive-bubble__nevermind-btn" id="proactiveNevermindBtn">
                    Nevermind
                </button>
            </div>
        </div>
        <div class="chatbot-proactive-bubble__tail" aria-hidden="true"></div>
    </aside>

    <button class="chatbot-toggle" id="chatbotToggle" aria-label="Open Admin AI Assistant" aria-expanded="false">
        <span class="chatbot-toggle__label">Admin Intelligence</span>
        <svg class="chatbot-toggle__icon chatbot-toggle__icon--chat" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693l-1.57-.393m15.6 0l1.134 4.536a.75.75 0 01-.728.932H3.794a.75.75 0 01-.728-.932L4.2 15.3"/>
        </svg>
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
                    <h4 class="chatbot-header__title">HinaguanBot <span class="text-xs px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-600 dark:text-amber-300 font-semibold uppercase tracking-wider ml-1">Admin</span></h4>
                    <p class="chatbot-header__subtitle"><span class="chatbot-header__dot" aria-hidden="true"></span> Analytics &amp; Operations Copilot</p>
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

        <div class="chatbot-quick" id="chatbotQuick">
            <span class="chatbot-quick__label">Admin Intelligence Queries</span>
            <div class="chatbot-quick__chips">
                <button type="button" class="chatbot-chip" data-quick-reply="What are the recent activities and who performed them?">Recent Activities</button>
                <button type="button" class="chatbot-chip" data-quick-reply="Show me sales and revenue breakdown">Sales &amp; Revenue</button>
                <button type="button" class="chatbot-chip" data-quick-reply="How many kids and seniors checked in this week?">Kids &amp; Seniors Demographics</button>
                <button type="button" class="chatbot-chip" data-quick-reply="Overview of staff accounts and activity">Staff Accounts</button>
                <button type="button" class="chatbot-chip" data-quick-reply="What are the most booked amenities and total revenue?">Top Amenities</button>
            </div>
        </div>

        <div class="chatbot-messages" id="chatbotMessages">
            <div class="chatbot-message chatbot-message--bot">
                <div class="chatbot-message__avatar">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693l-1.57-.393m15.6 0l1.134 4.536a.75.75 0 01-.728.932H3.794a.75.75 0 01-.728-.932L4.2 15.3"/>
                    </svg>
                </div>
                <div class="chatbot-message__body">
                    <div class="chatbot-message__meta">
                        <span class="chatbot-message__author">HinaguanBot (Admin)</span>
                    </div>
                    <div class="chatbot-message__content">
                        <p>Welcome, Administrator! I am your <strong>Admin Intelligence Assistant</strong>. I can mine revenue metrics, guest demographics (kids, teens, adults, seniors), staff account status, and query our live <strong>Recent Activity audit log</strong> (e.g., <em>"When did reservation 4 extend stay and who did it?"</em>). How may I assist you?</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="chatbot-model-selector">
            <svg class="chatbot-model-selector__icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4M4 19h4M13 3l2.5 6.5L22 12l-6.5 2.5L13 21l-2.5-6.5L4 12l6.5-2.5L13 3z"/>
            </svg>
            <label for="chatbotModel" class="chatbot-model-label">AI Model</label>
            <select id="chatbotModel" class="chatbot-model-select">
                <option value="openrouter/free" selected>OpenRouter Free (Auto)</option>
                <option value="meta-llama/llama-3.3-70b-instruct:free">Llama 3.3 70B (Free)</option>
                <option value="meta-llama/llama-3-8b-instruct:free">Llama 3 8B (Free)</option>
                <option value="google/gemma-3-4b-it:free">Gemma 3 4B (Free)</option>
            </select>
        </div>

        <div class="chatbot-input-wrapper">
            <form class="chatbot-form" id="chatbotForm">
                <input
                    type="text"
                    class="chatbot-input"
                    id="chatbotInput"
                    placeholder="Ask about revenue, activities, staff, or demographics…"
                    autocomplete="off"
                    aria-label="Admin query input"
                >
                <button type="submit" class="chatbot-send" id="chatbotSend" aria-label="Send message">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </form>
            <p class="chatbot-input-wrapper__hint">Admin AI Intelligence &middot; Hinaguan Nature Park</p>
        </div>

        <!-- Delete Conversation Confirmation Modal -->
        <div class="chatbot-modal-overlay" id="chatbotDeleteModal" hidden>
            <div class="chatbot-modal" role="dialog" aria-modal="true" aria-labelledby="adminChatbotDeleteTitle">
                <div class="chatbot-modal__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h4 class="chatbot-modal__title" id="adminChatbotDeleteTitle">Delete Conversation</h4>
                <p class="chatbot-modal__text">Are you sure you want to delete the conversation? You won't be able to retrieve it again.</p>
                <div class="chatbot-modal__actions">
                    <button type="button" class="chatbot-modal__btn chatbot-modal__btn--cancel" id="chatbotCancelDelete">Cancel</button>
                    <button type="button" class="chatbot-modal__btn chatbot-modal__btn--danger" id="chatbotConfirmDelete">Yes, Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>
