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
        fetch('api/chat.php', {
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
        div.classList.add('message', type);

        // Simple Markdown parsing for bold text
        const formattedText = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        div.innerHTML = formattedText;

        messagesContainer.appendChild(div);
        scrollToBottom();
    }

    function showTyping() {
        const div = document.createElement('div');
        div.id = 'typing-indicator';
        div.classList.add('typing-indicator');
        div.innerHTML = `
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
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
