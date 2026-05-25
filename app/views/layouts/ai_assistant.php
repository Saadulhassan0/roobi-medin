<!-- Floating AI Assistant Button -->
<div class="ai-fab" id="ai-fab" title="MedIn AI Assistant">
    <i class="fas fa-robot"></i>
</div>

<!-- AI Chat Panel -->
<div class="ai-panel" id="ai-panel">
    <div class="ai-header">
        <h3><i class="fas fa-bolt"></i> MedIn AI</h3>
        <button class="ai-close" id="ai-close"><i class="fas fa-times"></i></button>
    </div>
    
    <div class="ai-body" id="ai-chat-body" style="scroll-behavior: smooth;">
        <div class="ai-message system">
            Hello, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>! I am your medical AI assistant. How can I help you manage your dashboard today?
        </div>
        <!-- More chat messages would populate here -->
    </div>
    
    <div class="ai-input-area" style="position: relative; z-index: 10;">
        <input type="text" id="ai-input" placeholder="Ask AI for insights..." style="pointer-events: auto; z-index: 10;">
        <button id="ai-send-btn" style="pointer-events: auto; z-index: 10;"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<style>
.ai-message {
    padding: 10px 15px;
    border-radius: 12px;
    margin-bottom: 10px;
    max-width: 85%;
    font-size: 0.9rem;
    line-height: 1.4;
    word-wrap: break-word;
}
.ai-message.system {
    background: rgba(34, 211, 238, 0.1);
    color: var(--accent-color);
    border: 1px solid rgba(34, 211, 238, 0.2);
    align-self: flex-start;
}
.ai-message.user {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    border: 1px solid var(--glass-border-light);
    align-self: flex-end;
    margin-left: auto;
}
.ai-body {
    display: flex;
    flex-direction: column;
}
.ai-typing {
    font-style: italic;
    color: var(--text-secondary);
    font-size: 0.8rem;
    padding: 5px 10px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('ai-input');
    const sendBtn = document.getElementById('ai-send-btn');
    const chatBody = document.getElementById('ai-chat-body');
    
    if (!input || !sendBtn || !chatBody) return;

    function appendMessage(text, sender) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `ai-message ${sender}`;
        msgDiv.textContent = text;
        chatBody.appendChild(msgDiv);
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    function appendTyping() {
        const div = document.createElement('div');
        div.className = 'ai-message system ai-typing';
        div.id = 'ai-typing-indicator';
        div.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> AI is typing...';
        chatBody.appendChild(div);
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    function removeTyping() {
        const ind = document.getElementById('ai-typing-indicator');
        if (ind) ind.remove();
    }

    async function sendMessage() {
        const text = input.value.trim();
        if (!text) return;
        
        // Disable input
        input.value = '';
        input.disabled = true;
        sendBtn.disabled = true;
        
        appendMessage(text, 'user');
        appendTyping();
        
        try {
            const res = await fetch('../../api/ai_chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            });
            const result = await res.json();
            
            removeTyping();
            
            if (result.success) {
                appendMessage(result.reply, 'system');
            } else {
                appendMessage("I'm sorry, I encountered an error connecting to the AI server.", 'system');
            }
        } catch (err) {
            removeTyping();
            appendMessage("Network error. Please try again later.", 'system');
        } finally {
            input.disabled = false;
            sendBtn.disabled = false;
            input.focus();
        }
    }

    sendBtn.addEventListener('click', sendMessage);
    
    input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
});
</script>
