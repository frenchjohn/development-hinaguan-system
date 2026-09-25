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

    // Format bot responses with clean markdown rendering
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

    const getBotLogoUrl = () => {
        return chatbotWidget?.getAttribute('data-logo')
            || document.querySelector('.chatbot-avatar img')?.src
            || '/storage/design_images/main_logo.jpeg';
    };

    // Add message to chat
    const addMessage = (content, isBot = true, shouldSave = true) => {
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
    if (modelSelect) {
        if (selectedModel) {
            modelSelect.value = selectedModel;
        }
        if (!modelSelect.value || modelSelect.value !== 'openrouter/free') {
            modelSelect.value = 'openrouter/free';
        }
        selectedModel = modelSelect.value;
        saveState(isOpen, messages, selectedModel);
    }

    // Save selected model when changed
    modelSelect?.addEventListener('change', (e) => {
        selectedModel = e.target.value;
        saveState(isOpen, messages, selectedModel);
    });

    // Model selector toggle / hide handlers
    const modelSelector = document.getElementById('chatbotModelSelector');
    const modelHideBtn = document.getElementById('chatbotModelHide');
    modelHideBtn?.addEventListener('click', () => {
        if (modelSelector) {
            modelSelector.hidden = true;
        }
    });

    // ---------------------------------------------------------
    // AI Proactive Speech Bubble Pop-up (Homepage Greeting)
    // ---------------------------------------------------------
    const chatbotProactiveBubble = document.getElementById('chatbotProactiveBubble');
    const proactiveCloseIcon = document.getElementById('proactiveCloseIcon');
    const proactiveBubbleCard = document.getElementById('proactiveBubbleCard');
    const storageKeyProactive = 'guest_chatbot_proactive_shown';
    let proactiveTimer = null;

    // Dismiss Proactive Speech Bubble with smooth exit animation
    const dismissProactiveBubble = () => {
        if (!chatbotProactiveBubble || chatbotProactiveBubble.hidden) return;
        chatbotProactiveBubble.classList.add('is-hiding');
        setTimeout(() => {
            chatbotProactiveBubble.hidden = true;
            chatbotProactiveBubble.classList.remove('is-hiding');
        }, 280);
    };

    // Show Proactive Speech Bubble
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

    // Only talk first once per session when arriving on the homepage after a 5s delay
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
        chatbotToggle.setAttribute('aria-expanded', isOpen);
        chatbotToggle.setAttribute('aria-label', isOpen ? 'Close chatbot' : 'Open chatbot');
        
        if (isOpen) {
            if (proactiveTimer) {
                clearTimeout(proactiveTimer);
                proactiveTimer = null;
            }
            sessionStorage.setItem(storageKeyProactive, 'true');
            dismissProactiveBubble();
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

    // Delete conversation confirmation modal workflow
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
        if (e.target === chatbotDeleteModal) {
            closeDeleteModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && chatbotDeleteModal && !chatbotDeleteModal.hidden) {
            closeDeleteModal();
        }
    });

    chatbotConfirmDelete?.addEventListener('click', () => {
        closeDeleteModal();
        conversationCleared = true;
        messages = [];
        saveState(isOpen, messages, selectedModel);
        chatbotMessages.innerHTML = '';
        // Bring back the welcome message + quick-reply chips
        chatbotWidget.classList.remove('is-conversation');
        addMessage('Hello! I\'m Bren, your Bren AI Assistant 🌿 I can help you with our amenities, rates, and how to book your visit. What would you like to know?', true, false);
        chatbotInput?.focus();
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
        if (e.key === 'Escape') {
            if (chatbotProactiveBubble && !chatbotProactiveBubble.hidden) {
                dismissProactiveBubble();
            }
            if (isOpen) {
                toggleChatbot();
            }
        }
    });

    // Show typing indicator
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

        // Command to show/toggle AI Model selector
        if (message.toLowerCase() === '/aimodel') {
            chatbotInput.value = '';
            if (modelSelector) {
                modelSelector.hidden = !modelSelector.hidden;
            }
            return;
        }
        
        // Get selected model
        const modelSelect = document.getElementById('chatbotModel');
        const selectedModel = modelSelect ? modelSelect.value : 'openrouter/free';
        
        // Prepare multi-turn history before adding new user message to state
        const historyPayload = messages.slice(-6).map(m => ({
            role: m.isBot ? 'assistant' : 'user',
            content: m.content
        }));

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
            const response = await fetch('/guest-chatbot', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    message,
                    history: historyPayload,
                    model: selectedModel
                }),
            });
            
            const data = await response.json();
            
            // Hide typing indicator
            hideTyping();
            
            // Log for debugging
            console.log('Guest chatbot response:', data);
            
            // A wiped conversation must not be resurrected by a late reply
            if (conversationCleared) return;
            
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
            if (conversationCleared) return;
            addMessage('I apologize, but the service is temporarily unavailable. Please try again later.', true);
        } finally {
            // Re-enable send button
            chatbotSend.disabled = false;
            chatbotInput.focus();
        }
    });
});
