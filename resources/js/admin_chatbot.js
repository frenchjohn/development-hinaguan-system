document.addEventListener('DOMContentLoaded', () => {
    const chatbotWidget = document.getElementById('chatbotWidget');
    const chatbotToggle = document.getElementById('chatbotToggle');
    const chatbotWindow = document.getElementById('chatbotWindow');
    const chatbotClose = document.getElementById('chatbotClose');
    const chatbotForm = document.getElementById('chatbotForm');
    const chatbotInput = document.getElementById('chatbotInput');
    const chatbotMessages = document.getElementById('chatbotMessages');
    const chatbotSend = document.getElementById('chatbotSend');
    const chatbotClear = document.getElementById('chatbotClear');

    if (!chatbotWidget || !chatbotToggle || !chatbotWindow) return;

    // Escape HTML to prevent XSS
    const escapeHtml = (text) => {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    const getCsrfToken = () => {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || document.querySelector('input[name="_token"]')?.value
            || '';
    };

    // Format bot responses with enhanced clean markdown rendering
    const formatBotMessage = (text) => {
        let formatted = escapeHtml(text);
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

    // Load preferences from localStorage
    const loadPreferences = () => {
        try {
            const saved = localStorage.getItem('adminChatbotPrefs');
            if (saved) {
                return JSON.parse(saved);
            }
        } catch (e) {
            console.error('Error loading admin chatbot prefs:', e);
        }
        return { isOpen: false, selectedModel: null };
    };

    const savePreferences = (isOpen, selectedModel) => {
        try {
            localStorage.setItem('adminChatbotPrefs', JSON.stringify({ isOpen, selectedModel }));
        } catch (e) {
            console.error('Error saving admin chatbot prefs:', e);
        }
    };

    const prefs = loadPreferences();
    let isOpen = prefs.isOpen || false;
    let selectedModel = prefs.selectedModel || null;
    let conversationCleared = false;
    let clearArmTimer = null;
    let messages = [];

    // Add message to chat UI
    const addMessage = (content, isBot = true) => {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbot-message ${isBot ? 'chatbot-message--bot' : 'chatbot-message--user'}`;

        if (isBot) {
            messageDiv.innerHTML = `
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
    };

    const showWelcomeMessage = () => {
        chatbotMessages.innerHTML = '';
        chatbotWidget.classList.remove('is-conversation');
        addMessage('Welcome, Administrator! I am your Admin Intelligence Assistant. Ask me anything about recent operational activities, who performed check-ins or extensions, revenue analytics, guest demographics, or staff accounts.', true);
    };

    // Load database history
    const loadDatabaseHistory = async () => {
        try {
            const res = await fetch('/admin-chatbot/history', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (res.ok) {
                const data = await res.json();
                const dbMessages = data.messages || [];
                if (dbMessages.length > 0) {
                    chatbotMessages.innerHTML = '';
                    chatbotWidget.classList.add('is-conversation');
                    messages = dbMessages;
                    dbMessages.forEach((msg) => {
                        addMessage(msg.content, msg.isBot);
                    });
                } else {
                    showWelcomeMessage();
                }
            }
        } catch (e) {
            console.error('Failed to load admin chatbot history from database:', e);
        }
    };

    // Restore window state
    if (isOpen) {
        chatbotWindow.hidden = false;
        chatbotToggle.setAttribute('aria-expanded', 'true');
        chatbotWidget.classList.add('is-open');
        setTimeout(() => {
            if (chatbotMessages) chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        }, 300);
    }

    // Restore model selection
    const modelSelect = document.getElementById('chatbotModel');
    if (modelSelect && selectedModel) {
        modelSelect.value = selectedModel;
    }

    modelSelect?.addEventListener('change', (e) => {
        selectedModel = e.target.value;
        savePreferences(isOpen, selectedModel);
    });

    // Toggle chatbot
    const toggleChatbot = () => {
        isOpen = !isOpen;
        chatbotWindow.hidden = !isOpen;
        chatbotToggle.setAttribute('aria-expanded', isOpen.toString());
        chatbotWidget.classList.toggle('is-open', isOpen);
        savePreferences(isOpen, selectedModel);

        if (isOpen) {
            chatbotInput?.focus();
            setTimeout(() => {
                if (chatbotMessages) {
                    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
                }
            }, 50);
        }
    };

    chatbotToggle?.addEventListener('click', toggleChatbot);
    chatbotClose?.addEventListener('click', toggleChatbot);

    // Delete conversation from DB and UI
    const disarmClear = () => {
        if (!chatbotClear) return;
        chatbotClear.classList.remove('is-armed');
        chatbotClear.setAttribute('aria-label', 'Delete conversation');
    };

    chatbotClear?.addEventListener('click', async () => {
        if (!chatbotClear.classList.contains('is-armed')) {
            chatbotClear.classList.add('is-armed');
            chatbotClear.setAttribute('aria-label', 'Click again to delete');
            clearArmTimer = setTimeout(disarmClear, 2500);
            return;
        }

        clearTimeout(clearArmTimer);
        disarmClear();
        conversationCleared = true;
        messages = [];

        try {
            await fetch('/admin-chatbot/clear', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({}),
                __skipBusy: true,
            });
        } catch (e) {
            console.error('Error clearing admin chatbot history in database:', e);
        }

        showWelcomeMessage();
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

    // Escape to close
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isOpen) {
            toggleChatbot();
        }
    });

    // Typing indicator
    const showTyping = () => {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'chatbot-message chatbot-message--bot chatbot-message--typing';
        typingDiv.id = 'chatbotTyping';
        typingDiv.innerHTML = `
            <div class="chatbot-message__avatar">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693l-1.57-.393m15.6 0l1.134 4.536a.75.75 0 01-.728.932H3.794a.75.75 0 01-.728-.932L4.2 15.3"/>
                </svg>
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
        if (typingDiv) {
            typingDiv.remove();
        }
    };

    // Form submit
    chatbotForm?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const message = chatbotInput.value.trim();
        if (!message) return;

        const modelSelect = document.getElementById('chatbotModel');
        const activeModel = modelSelect ? modelSelect.value : 'openrouter/free';

        // Add user message to UI immediately
        addMessage(message, false);
        messages.push({ content: message, isBot: false });
        chatbotInput.value = '';

        chatbotWidget.classList.add('is-conversation');
        conversationCleared = false;

        showTyping();
        chatbotSend.disabled = true;

        try {
            const response = await fetch('/admin-chatbot', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    message,
                    model: activeModel
                }),
                __skipBusy: true,
            });

            const data = await response.json();

            hideTyping();

            if (conversationCleared) return;

            if (data.reply) {
                addMessage(data.reply, true);
                messages.push({ content: data.reply, isBot: true });
            } else {
                addMessage('I apologize, but I encountered an error processing your query. Please try again.', true);
            }
        } catch (error) {
            console.error('Admin Chatbot error:', error);
            hideTyping();
            if (conversationCleared) return;
            addMessage('I apologize, but the admin intelligence service is temporarily unavailable.', true);
        } finally {
            chatbotSend.disabled = false;
            chatbotInput?.focus();
        }
    });

    // Fetch conversation from database
    loadDatabaseHistory();
});
