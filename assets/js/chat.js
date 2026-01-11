document.addEventListener('DOMContentLoaded', function () {
    const chatBtn = document.getElementById('chat-widget-btn');
    const chatWindow = document.getElementById('chat-widget-window');
    const closeBtn = document.getElementById('chat-close');
    const sendBtn = document.getElementById('chat-send-btn');
    const inputField = document.getElementById('chat-input');
    const messagesContainer = document.getElementById('chat-messages');

    // Toggle Chat
    chatBtn.addEventListener('click', () => {
        chatWindow.classList.toggle('open');
        if (chatWindow.classList.contains('open')) {
            inputField.focus();
        }
    });

    closeBtn.addEventListener('click', () => {
        chatWindow.classList.remove('open');
    });

    // Send Message
    function sendMessage() {
        const message = inputField.value.trim();
        if (!message) return;

        // Add User Message
        addMessage(message, 'user');
        inputField.value = '';

        // Show Typing Indicator
        showTyping();

        // Call API
        fetch(APP_BASE_PATH + 'api/chat.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: message })
        })
            .then(response => response.json())
            .then(data => {
                removeTyping();
                if (data.error) {
                    addMessage(data.error, 'error');
                } else {
                    addMessage(data.reply, 'ai');
                }
            })
            .catch(error => {
                removeTyping();
                addMessage('Failed to connect to AI.', 'error');
                console.error('Error:', error);
            });
    }

    sendBtn.addEventListener('click', sendMessage);
    inputField.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    // Helper Functions
    function addMessage(text, type) {
        const div = document.createElement('div');
        // Base classes
        div.className = 'max-w-[80%] p-3 rounded-xl text-sm leading-relaxed relative break-words';

        if (type === 'user') {
            div.classList.add('self-end', 'bg-green-700', 'text-white', 'rounded-br-sm');
        } else if (type === 'ai') {
            div.classList.add('self-start', 'bg-white', 'border', 'border-gray-200', 'text-gray-800', 'rounded-bl-sm');
        } else if (type === 'error') {
            div.classList.add('self-center', 'bg-red-100', 'text-red-600', 'text-xs');
        }

        // Simple Markdown parsing for bold text
        const formattedText = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        div.innerHTML = formattedText;

        messagesContainer.appendChild(div);
        scrollToBottom();
    }

    function showTyping() {
        const div = document.createElement('div');
        div.id = 'typing-indicator';
        div.className = 'flex gap-1 p-3 bg-white rounded-xl w-fit border border-gray-200';
        div.innerHTML = `
            <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: -0.32s"></div>
            <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: -0.16s"></div>
            <div class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce"></div>
        `;
        messagesContainer.appendChild(div);
        scrollToBottom();
    }

    function removeTyping() {
        const indicator = document.getElementById('typing-indicator');
        if (indicator) indicator.remove();
    }

    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // Expose sendSuggestion to global scope
    window.sendSuggestion = function (btn) {
        const text = btn.textContent;
        inputField.value = text;
        sendMessage();

        // Optional: Hide suggestions after use
        // const suggestions = btn.closest('.chat-suggestions');
        // if(suggestions) suggestions.style.display = 'none';
    };
});
