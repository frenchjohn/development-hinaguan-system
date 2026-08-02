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
            const saved = localStorage.getItem('guestChatbotState');
            if (saved) {
                return JSON.parse(saved);
            }
        } catch (e) {
            console.error('Error loading guest chatbot state:', e);
        }
        return { isOpen: false, messages: [], selectedModel: null };
    };

    // Save state to localStorage
    const saveState = (isOpen, messages, selectedModel) => {
        try {
            localStorage.setItem('guestChatbotState', JSON.stringify({ isOpen, messages, selectedModel }));
        } catch (e) {
            console.error('Error saving guest chatbot state:', e);
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
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <div class="chatbot-message__content">
                    <p>${escapeHtml(content)}</p>
                </div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="chatbot-message__content">
                    <p>${escapeHtml(content)}</p>
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
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
            </div>
            <div class="chatbot-message__content">
                <div class="chatbot-typing">
                    <span></span>
                    <span></span>
                    <span></span>
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
        const selectedModel = modelSelect ? modelSelect.value : 'openrouter/free';
        
        // Add user message
        addMessage(message, false);
        chatbotInput.value = '';
        
        // Show typing indicator
        showTyping();
        
        // Disable send button
        chatbotSend.disabled = true;
        
        try {
            const response = await fetch('/guest-chatbot', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message, model: selectedModel }),
            });
            
            const data = await response.json();
            
            // Hide typing indicator
            hideTyping();
            
            // Log for debugging
            console.log('Guest chatbot response:', data);
            
            // Add bot response
            if (data.reply) {
                addMessage(data.reply, true);
            } else {
                console.error('No reply in response:', data);
                addMessage('I apologize, but I encountered an error. Please try again.', true);
            }
        } catch (error) {
            console.error('Guest chatbot error:', error);
            hideTyping();
            addMessage('I apologize, but the service is temporarily unavailable. Please try again later.', true);
        } finally {
            // Re-enable send button
            chatbotSend.disabled = false;
            chatbotInput.focus();
        }
    });
});
