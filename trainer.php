<?php
$pageTitle = 'PokéTrainer AI';
require_once 'includes/header.php';
?>

<section class="trainer-page py-5">
    <div class="container">
        <div class="trainer-hero text-center mb-4">
            <span class="trainer-chip mb-3 d-inline-block">
                <i class="bi bi-joystick me-2"></i>PokéMart AI Feature
            </span>
            <h1 class="fw-bold mb-3">PokéTrainer AI</h1>
            <p class="text-muted mb-0">
                Chat with your retro-style Pokémon trainer for card info, Pokémon facts, and card pricing help.
            </p>
        </div>

        <div class="trainer-chat-shell mx-auto">
            <div class="trainer-chat-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="trainer-avatar">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                    <div>
                        <div class="fw-bold">Trainer Dex</div>
                        <small class="text-light-emphasis">Online • Pokémon specialist</small>
                    </div>
                </div>
                <span class="trainer-status" id="statusBadge">DS Link Active</span>
            </div>

            <div class="trainer-chat-body" id="chatBody">
                <div class="chat-row trainer-msg">
                    <div class="chat-bubble">
                        Yo trainer! I'm Trainer Dex. Ask me about Pokémon, card prices, card facts, rarity, or collecting tips.
                    </div>
                </div>

                <div class="chat-row trainer-msg">
                    <div class="chat-bubble">
                        If it's not Pokémon-related, I'll have to pass — I'm just a Pokémon trainer, not a professor of everything.
                    </div>
                </div>
            </div>

            <form class="trainer-chat-input" id="chatForm">
                <div class="input-group">
                    <input
                        type="text"
                        id="trainerInput"
                        class="form-control"
                        placeholder="Ask about Pokémon cards, prices, or facts..."
                        maxlength="200"
                        autocomplete="off"
                        disabled
                    >
                    <button class="btn btn-warning fw-bold px-4" type="submit" id="sendBtn">
                        <i class="bi bi-send-fill me-1"></i>Send
                    </button>
                </div>
                <small class="text-muted d-block mt-2" id="formNote">
                    Connecting to AI backend...
                </small>
            </form>
        </div>
    </div>
</section>

<style>
    .typing-indicator {
        display: flex;
        gap: 4px;
        padding: 8px 12px;
    }
    .typing-indicator span {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: linear-gradient(180deg, #3b5cff 0%, #1e2f79 100%);
        box-shadow: 0 0 0 2px rgba(170, 192, 255, 0.35);
        animation: typing 1.4s infinite;
    }
    .typing-indicator span:nth-child(2) {
        animation-delay: 0.2s;
    }
    .typing-indicator span:nth-child(3) {
        animation-delay: 0.4s;
    }
    @keyframes typing {
        0%, 60%, 100% {
            opacity: 0.5;
        }
        30% {
            opacity: 1;
        }
    }
    .error-bubble {
        background: linear-gradient(180deg, #fff8f8 0%, #ffeaea 100%) !important;
        color: #8f1d1d !important;
        border-color: #ef5350 !important;
        box-shadow: 0 14px 28px rgba(174, 32, 18, 0.14) !important;
    }
</style>

<script>
    const API_ENDPOINT = '/api/trainer-chat.php';
    let isConnected = false;
    let conversationHistory = [];

    document.addEventListener('DOMContentLoaded', async () => {
        const form = document.getElementById('chatForm');
        const input = document.getElementById('trainerInput');
        const sendBtn = document.getElementById('sendBtn');
        const formNote = document.getElementById('formNote');
        const statusBadge = document.getElementById('statusBadge');

        try {
            const response = await fetch(API_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'health_check' })
            });
            const data = await response.json().catch(() => null);

            if (response.ok && data?.success) {
                isConnected = true;
                input.disabled = false;
                sendBtn.disabled = false;
                formNote.textContent = data.message || 'Backend connected ✓';
                formNote.style.color = '#28a745';
                statusBadge.textContent = 'Connected';
            } else {
                throw new Error(data?.message || data?.error || 'Backend not responding');
            }
        } catch (error) {
            isConnected = false;
            input.disabled = true;
            sendBtn.disabled = true;
            formNote.textContent = error.message || 'Trainer service is unavailable right now.';
            formNote.style.color = '#b42318';
            statusBadge.textContent = 'Unavailable';
        }

        form.addEventListener('submit', (e) => handleSubmit(e));
    });

    async function handleSubmit(event) {
        event.preventDefault();

        const input = document.getElementById('trainerInput');
        const chatBody = document.getElementById('chatBody');
        const message = input.value.trim();

        if (!message) return false;

        if (!isConnected) {
            return false;
        }

        addMessageToChat('user', message);
        const requestHistory = [...conversationHistory];
        conversationHistory.push({ role: 'user', content: message });
        input.value = '';

        const typingRow = showTypingIndicator();

        try {
            const response = await fetch(API_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'chat',
                    message: message,
                    history: requestHistory
                });
            });

            const data = await response.json().catch(() => null);

            if (!response.ok) {
                throw new Error(data?.error || data?.message || `Server error: ${response.status}`);
            }

            if (data.error) {
                throw new Error(data.error);
            }

            const reply = data.reply;
            removeTypingIndicator(typingRow);
            addMessageToChat('trainer', reply);
            conversationHistory.push({ role: 'assistant', content: reply });
        } catch (error) {
            removeTypingIndicator(typingRow);
            addMessageToChat('trainer', `Error: ${error.message}. Please try again.`, true);
        }

        chatBody.scrollTop = chatBody.scrollHeight;
        return false;
    }

    function addMessageToChat(sender, message, isError = false) {
        const chatBody = document.getElementById('chatBody');
        const row = document.createElement('div');
        row.className = `chat-row ${sender === 'user' ? 'user-msg' : 'trainer-msg'}`;

        const bubble = document.createElement('div');
        bubble.className = isError ? 'chat-bubble error-bubble' : 'chat-bubble';
        bubble.textContent = message;

        row.appendChild(bubble);
        chatBody.appendChild(row);
        chatBody.scrollTop = chatBody.scrollHeight;

        return row;
    }

    function showTypingIndicator() {
        const chatBody = document.getElementById('chatBody');
        const row = document.createElement('div');
        row.className = 'chat-row trainer-msg';
        row.id = 'typing-indicator';

        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';
        bubble.innerHTML = '<div class="typing-indicator"><span></span><span></span><span></span></div>';

        row.appendChild(bubble);
        chatBody.appendChild(row);
        chatBody.scrollTop = chatBody.scrollHeight;

        return row;
    }

    function removeTypingIndicator(row) {
        if (row && row.parentNode) {
            row.parentNode.removeChild(row);
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>
