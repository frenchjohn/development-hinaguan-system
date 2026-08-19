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

    // Proactive Speech Bubble Elements
    const chatbotProactiveBubble = document.getElementById('chatbotProactiveBubble');
    const proactiveHeadline = document.getElementById('proactiveHeadline');
    const proactiveMessageText = document.getElementById('proactiveMessageText');
    const proactiveFollowupText = document.getElementById('proactiveFollowupText');
    const proactiveActionBtn = document.getElementById('proactiveActionBtn');
    const proactiveActionLabel = document.getElementById('proactiveActionLabel');
    const proactiveNevermindBtn = document.getElementById('proactiveNevermindBtn');
    const proactiveCloseIcon = document.getElementById('proactiveCloseIcon');

    let currentProactiveData = null;

    if (!chatbotWidget || !chatbotToggle || !chatbotWindow) return;

    // Escape HTML to prevent XSS
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

    const getCsrfToken = () => {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || document.querySelector('input[name="_token"]')?.value
            || '';
    };

    // Load window state (open / model preference) from localStorage
    const loadPreferences = () => {
        try {
            const saved = localStorage.getItem('staffChatbotPrefs');
            if (saved) {
                return JSON.parse(saved);
            }
        } catch (e) {
            console.error('Error loading chatbot prefs:', e);
        }
        return { isOpen: false, selectedModel: null };
    };

    const savePreferences = (isOpen, selectedModel) => {
        try {
            localStorage.setItem('staffChatbotPrefs', JSON.stringify({ isOpen, selectedModel }));
        } catch (e) {
            console.error('Error saving chatbot prefs:', e);
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-1.5 2.5-4 5-4 8a4 4 0 108 0c0-3-2.5-5.5-4-8z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8M10 18h4"/>
                    </svg>
                </div>
                <div class="chatbot-message__body">
                    <div class="chatbot-message__meta">
                        <span class="chatbot-message__author">HinaguanBot (Staff)</span>
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
        addMessage('Hello! I\'m HinaguanBot, your staff assistant. I can walk you through check-ins, guest records, reservations, and park operations. How can I help your shift today?', true);
    };

    // Load database history
    const loadDatabaseHistory = async () => {
        try {
            const res = await fetch('/chatbot/history', {
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
            console.error('Failed to load staff chatbot history from database:', e);
        }
    };

    // Restore window state
    if (isOpen) {
        chatbotWindow.hidden = false;
        chatbotToggle.setAttribute('aria-expanded', 'true');
        setTimeout(() => {
            if (chatbotMessages) chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
        }, 300);
    }

    // Restore selected model
    const modelSelect = document.getElementById('chatbotModel');
    if (selectedModel && modelSelect) {
        modelSelect.value = selectedModel;
    }

    modelSelect?.addEventListener('change', (e) => {
        selectedModel = e.target.value;
        savePreferences(isOpen, selectedModel);
    });

    const currentUserId = chatbotWidget?.getAttribute('data-user-id') || '0';
    const storageKeyDismissed = `staff_chatbot_proactive_dismissed_${currentUserId}`;
    const storageKeyResId = `staff_last_announced_res_id_${currentUserId}`;
    const storageKeyAnnouncedKeys = `staff_announced_keys_${currentUserId}`;

    // Dismiss Proactive Speech Bubble
    const dismissProactiveBubble = () => {
        if (!chatbotProactiveBubble || chatbotProactiveBubble.hidden) return;
        chatbotProactiveBubble.classList.add('is-hiding');
        sessionStorage.setItem(storageKeyDismissed, Date.now().toString());
        setTimeout(() => {
            chatbotProactiveBubble.hidden = true;
            chatbotProactiveBubble.classList.remove('is-hiding');
        }, 280);
    };

    // Show Proactive Speech Bubble
    const showProactiveBubble = (data) => {
        if (!chatbotProactiveBubble || isOpen) return;
        currentProactiveData = data;
        if (proactiveHeadline) proactiveHeadline.textContent = data.headline || 'Notice';
        if (proactiveMessageText) proactiveMessageText.textContent = data.message || '';
        if (proactiveFollowupText) proactiveFollowupText.textContent = data.follow_up || '';
        if (proactiveActionLabel) proactiveActionLabel.textContent = data.action_button_text || 'Explore';
        chatbotProactiveBubble.hidden = false;
    };

    // Check & trigger proactive AI greeting
    const checkProactiveGreeting = async (force = false) => {
        if (isOpen && !force) return;
        
        if (force) {
            sessionStorage.removeItem(storageKeyDismissed);
        }

        const lastDismissed = sessionStorage.getItem(storageKeyDismissed);
        const now = Date.now();
        if (!force && lastDismissed && (now - parseInt(lastDismissed, 10)) < 180000) {
            return; // 3 minute cooldown after user dismissal
        }

        const lastAnnounced = sessionStorage.getItem(storageKeyResId) || '0';
        const announcedKeysRaw = sessionStorage.getItem(storageKeyAnnouncedKeys) || '';
        const url = `/chatbot/proactive?last_announced_res_id=${encodeURIComponent(lastAnnounced)}&announced_keys=${encodeURIComponent(announcedKeysRaw)}${force ? '&force=1' : ''}`;

        try {
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (res.ok) {
                const data = await res.json();
                if (Array.isArray(data.announced_keys)) {
                    sessionStorage.setItem(storageKeyAnnouncedKeys, data.announced_keys.join(','));
                } else if (data.announced_key) {
                    const keysSet = new Set(announcedKeysRaw.split(',').filter(Boolean));
                    keysSet.add(data.announced_key);
                    sessionStorage.setItem(storageKeyAnnouncedKeys, Array.from(keysSet).join(','));
                }
                if (data.announced_res_id) {
                    sessionStorage.setItem(storageKeyResId, data.announced_res_id.toString());
                }
                if (data.has_message) {
                    showProactiveBubble(data);
                    // Ensure it is in the chat list as well
                    const fullSpeech = data.full_speech || `${data.message}\n\n${data.follow_up}`;
                    const exists = messages.some(m => m.content === fullSpeech);
                    if (!exists) {
                        addMessage(fullSpeech, true);
                        messages.push({ content: fullSpeech, isBot: true });
                        chatbotWidget.classList.add('is-conversation');
                    }
                }
            }
        } catch (e) {
            console.debug('Staff proactive greeting check skipped:', e);
        }
    };

    window.checkStaffChatbotProactive = checkProactiveGreeting;
    window.addEventListener('activity:new', () => checkProactiveGreeting(true));

    // Toggle chatbot window
    const toggleChatbot = () => {
        isOpen = !isOpen;
        chatbotWindow.hidden = !isOpen;
        chatbotToggle.setAttribute('aria-expanded', isOpen);
        chatbotToggle.setAttribute('aria-label', isOpen ? 'Close chatbot' : 'Open chatbot');
        
        if (isOpen) {
            dismissProactiveBubble();
            chatbotInput.focus();
            setTimeout(() => {
                if (chatbotMessages) {
                    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
                }
            }, 50);
        }
        
        savePreferences(isOpen, selectedModel);
    };

    chatbotToggle?.addEventListener('click', toggleChatbot);
    chatbotClose?.addEventListener('click', toggleChatbot);

    // Proactive bubble button listeners
    proactiveNevermindBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        dismissProactiveBubble();
    });

    proactiveCloseIcon?.addEventListener('click', (e) => {
        e.stopPropagation();
        dismissProactiveBubble();
    });

    proactiveActionBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        const prompt = currentProactiveData?.quick_action_prompt;
        dismissProactiveBubble();
        if (!isOpen) toggleChatbot();
        if (prompt && chatbotInput && chatbotForm) {
            chatbotInput.value = prompt;
            setTimeout(() => {
                if (typeof chatbotForm.requestSubmit === 'function') {
                    chatbotForm.requestSubmit();
                } else {
                    chatbotForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                }
            }, 150);
        }
    });

    chatbotProactiveBubble?.querySelector('.chatbot-proactive-bubble__body')?.addEventListener('click', () => {
        dismissProactiveBubble();
        if (!isOpen) toggleChatbot();
    });

    // Delete conversation confirmation modal workflow
    const chatbotDeleteModal = document.getElementById('chatbotDeleteModal');
    const chatbotCancelDelete = document.getElementById('chatbotCancelDelete');
    const chatbotConfirmDelete = document.getElementById('chatbotConfirmDelete');

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

    chatbotConfirmDelete?.addEventListener('click', async () => {
        closeDeleteModal();
        conversationCleared = true;
        messages = [];

        try {
            await fetch('/chatbot/clear', {
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
            console.error('Error clearing chatbot history in database:', e);
        }

        showWelcomeMessage();
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
            const response = await fetch('/chatbot', {
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
                addMessage('I apologize, but I encountered an error. Please try again.', true);
            }
        } catch (error) {
            console.error('Chatbot error:', error);
            hideTyping();
            if (conversationCleared) return;
            addMessage('I apologize, but the service is temporarily unavailable. Please try again later.', true);
        } finally {
            chatbotSend.disabled = false;
            chatbotInput.focus();
        }
    });

    // Fetch conversation from database and check proactive greeting
    loadDatabaseHistory().then(() => {
        setTimeout(() => {
            checkProactiveGreeting();
        }, 2200);
    });

    // Periodic proactive check every 90 seconds
    setInterval(() => {
        if (document.visibilityState === 'visible' && !isOpen) {
            checkProactiveGreeting();
        }
    }, 90000);
});
