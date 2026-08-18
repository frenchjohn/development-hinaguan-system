<!-- Guest Chatbot Widget -->
<div class="chatbot-widget" id="chatbotWidget">

    <button class="chatbot-toggle" id="chatbotToggle" aria-label="Open chatbot" aria-expanded="false">
        <span class="chatbot-toggle__label">Ask HinaguanBot</span>
        <svg class="chatbot-toggle__icon chatbot-toggle__icon--chat" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14.5v.01"/>
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
                    <h4 class="chatbot-header__title">HinaguanBot</h4>
                    <p class="chatbot-header__subtitle"><span class="chatbot-header__dot" aria-hidden="true"></span> Online &middot; replies instantly</p>
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
            <span class="chatbot-quick__label">Ask HinaguanBot</span>
            <div class="chatbot-quick__chips">
                <button type="button" class="chatbot-chip" data-quick-reply="What amenities are available today and what are their rates?">Available Amenities</button>
                <button type="button" class="chatbot-chip" data-quick-reply="Suggest the best amenity for our group of 10 people">Suggest for 10 Pax</button>
                <button type="button" class="chatbot-chip" data-quick-reply="How do I book an amenity online?">How to Book Online</button>
                <button type="button" class="chatbot-chip" data-quick-reply="How does walk-in booking work at the counter?">Walk-in Guide</button>
                <button type="button" class="chatbot-chip" data-quick-reply="What are the entrance fees and operating sessions?">Entrance Fees &amp; Hours</button>
            </div>
        </div>

        <div class="chatbot-messages" id="chatbotMessages">
            <div class="chatbot-message chatbot-message--bot">
                <div class="chatbot-message__avatar">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-1.5 2.5-4 5-4 8a4 4 0 108 0c0-3-2.5-5.5-4-8z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8M10 18h4"/>
                    </svg>
                </div>
                <div class="chatbot-message__body">
                    <div class="chatbot-message__meta">
                        <span class="chatbot-message__author">HinaguanBot</span>
                    </div>
                    <div class="chatbot-message__content">
                        <p>Hello and welcome to <strong>Hinaguan Nature Park</strong>! &#127807; I can suggest the best amenities for your group size, check live availability &amp; expected checkout times, and guide you through online or walk-in booking. How may I help you today?</p>
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
    </div>
</div>
