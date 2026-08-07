document.addEventListener('DOMContentLoaded', () => {
    const chatbotWidget = document.getElementById('chatbotWidget');
    const chatbotToggle = document.getElementById('chatbotToggle');
    const chatbotWindow = document.getElementById('chatbotWindow');
    const chatbotClose = document.getElementById('chatbotClose');
    const chatbotForm = document.getElementById('chatbotForm');
    const chatbotInput = document.getElementById('chatbotInput');
    const chatbotMessages = document.getElementById('chatbotMessages');
    const chatbotSend = document.getElementById('chatbotSend');

    // Escape HTML to prevent XSS
    const escapeHtml = (text) => {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    // Load state from localStorage
    const loadState = () => {
        try {
            const saved = localStorage.getItem('chatbotState');
            if (saved) {
                return JSON.parse(saved);
            }
        } catch (e) {
            console.error('Error loading chatbot state:', e);
        }
        return { isOpen: false, messages: [], selectedModel: null };
    };

    // Save state to localStorage
    const saveState = (isOpen, messages, selectedModel) => {
        try {
            localStorage.setItem('chatbotState', JSON.stringify({ isOpen, messages, selectedModel }));
        } catch (e) {
            console.error('Error saving chatbot state:', e);
        }
    };

    // Initialize state
    const initialState = loadState();
    let isOpen = initialState.isOpen || false;
    let messages = initialState.messages || [];
    let selectedModel = initialState.selectedModel || null;

    // Add message to chat
    const addMessage = (content, isBot = true, shouldSave = true) => {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbot-message ${isBot ? 'chatbot-message--bot' : 'chatbot-message--user'}`;

        if (isBot) {
            messageDiv.innerHTML = `
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
                        <p>${escapeHtml(content)}</p>
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

        // Save to localStorage
        if (shouldSave) {
            messages.push({ content, isBot });
            saveState(isOpen, messages, selectedModel);
        }
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
        chatbotToggle.setAttribute('aria-expanded', 'true');
        
        // Scroll to bottom after window is visible with longer delay
        setTimeout(() => {
            if (chatbotMessages) {
                chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
            }
        }, 500);
    }

    // Restore selected model
    const modelSelect = document.getElementById('chatbotModel');
    if (selectedModel && modelSelect) {
        modelSelect.value = selectedModel;
    }

    // Save selected model when changed
    modelSelect?.addEventListener('change', (e) => {
        selectedModel = e.target.value;
        saveState(isOpen, messages, selectedModel);
    });

    // Toggle chatbot window
    const toggleChatbot = () => {
        isOpen = !isOpen;
        chatbotWindow.hidden = !isOpen;
        chatbotToggle.setAttribute('aria-expanded', isOpen);
        chatbotToggle.setAttribute('aria-label', isOpen ? 'Close chatbot' : 'Open chatbot');
        
        if (isOpen) {
            chatbotInput.focus();
            // Scroll to bottom when opening
            setTimeout(() => {
                if (chatbotMessages) {
                    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
                }
            }, 50);
        }
        
        saveState(isOpen, messages, selectedModel);
    };

    chatbotToggle?.addEventListener('click', toggleChatbot);
    chatbotClose?.addEventListener('click', toggleChatbot);

    // Delete conversation (two-tap confirm to avoid accidental clears)
    const chatbotClear = document.getElementById('chatbotClear');
    let clearArmed = false;
    let clearArmTimer = null;
    // Set when the conversation is wiped; a reply still in flight must not resurrect it
    let conversationCleared = false;
    const disarmClear = () => {
        clearArmed = false;
        clearArmTimer = null;
        chatbotClear?.classList.remove('is-armed');
        chatbotClear?.setAttribute('aria-label', 'Delete conversation');
    };
    chatbotClear?.addEventListener('click', () => {
        if (!clearArmed) {
            clearArmed = true;
            chatbotClear.classList.add('is-armed');
            chatbotClear.setAttribute('aria-label', 'Click again to delete');
            clearArmTimer = setTimeout(disarmClear, 2500);
            return;
        }
        // Confirm: wipe the conversation
        clearTimeout(clearArmTimer);
        disarmClear();
        conversationCleared = true;
        messages = [];
        saveState(isOpen, messages, selectedModel);
        chatbotMessages.innerHTML = '';
        // Bring back the welcome message + quick-reply chips
        chatbotWidget.classList.remove('is-conversation');
        addMessage('Hello! I\'m HinaguanBot, your staff assistant. I can walk you through check-ins, guest records, reservations, and park settings. How can I help you today?', true, false);
        chatbotInput.focus();
    });

    // Quick-reply chips: send the preset question
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
        if (e.key === 'Escape' && isOpen) {
            toggleChatbot();
        }
    });

    // Show typing indicator
    const showTyping = () => {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'chatbot-message chatbot-message--bot chatbot-message--typing';
        typingDiv.id = 'chatbotTyping';
        typingDiv.innerHTML = `
            <div class="chatbot-message__avatar">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-1.5 2.5-4 5-4 8a4 4 0 108 0c0-3-2.5-5.5-4-8z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8M10 18h4"/>
                </svg>
            </div>                <div class="chatbot-message__body">
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

    // Remove typing indicator
    const hideTyping = () => {
        const typingDiv = document.getElementById('chatbotTyping');
        if (typingDiv) {
            typingDiv.remove();
        }
    };

    // Handle form submission
    chatbotForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const message = chatbotInput.value.trim();
        if (!message) return;
        
        // Get selected model
        const modelSelect = document.getElementById('chatbotModel');
        const selectedModel = modelSelect ? modelSelect.value : 'meta-llama/llama-3-8b-instruct:free';
        
        // Add user message
        addMessage(message, false);
        chatbotInput.value = '';
        
        // Conversation started — hide the quick-reply chips
        chatbotWidget.classList.add('is-conversation');
        conversationCleared = false;
        
        // Show typing indicator
        showTyping();
        
        // Disable send button
        chatbotSend.disabled = true;
        
        try {
            const response = await fetch('/chatbot', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message, model: selectedModel }),
                // AI replies can take a while; don't count this as a page task
                // so the busy guard never blocks navigation mid-chat.
                __skipBusy: true,
            });
            
            const data = await response.json();
            
            // Hide typing indicator
            hideTyping();
            
            // A wiped conversation must not be resurrected by a late reply
            if (conversationCleared) return;
            
            // Add bot response
            if (data.reply) {
                addMessage(data.reply, true);
            } else {
                addMessage('I apologize, but I encountered an error. Please try again.', true);
            }
        } catch (error) {
            console.error('Chatbot error:', error);
            hideTyping();
            if (conversationCleared) return;
            addMessage('I apologize, but the service is temporarily unavailable. Please try again later.', true);
        } finally {
            // Re-enable send button
            chatbotSend.disabled = false;
            chatbotInput.focus();
        }
    });
});
