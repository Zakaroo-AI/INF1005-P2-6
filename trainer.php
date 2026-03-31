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
        background-color: #666;
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
        background-color: #ffebee !important;
        color: #c62828 !important;
        border-color: #ef5350 !important;
    }
</style>

<script>
    const API_ENDPOINT = '/api/trainer-chat.php'; // Backend endpoint
    let isConnected = false;
    let conversationHistory = [];

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', async () => {
        const form = document.getElementById('chatForm');
        const input = document.getElementById('trainerInput');
        const sendBtn = document.getElementById('sendBtn');
        const formNote = document.getElementById('formNote');

        // Check if backend is available
        try {
            const response = await fetch(API_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'health_check' })
            });

            if (response.ok) {
                isConnected = true;
                input.disabled = false;
                sendBtn.disabled = false;
                formNote.textContent = 'Backend connected ✓';
                formNote.style.color = '#28a745';
                document.getElementById('statusBadge').textContent = 'Connected';
            } else {
                throw new Error('Backend not responding');
            }
        } catch (error) {
            console.error('Backend connection error:', error);
            formNote.innerHTML = '<span style="color: #dc3545;">⚠ Backend unavailable. Using demo mode.</span>';
            // Still enable input for demo mode
            input.disabled = false;
            sendBtn.disabled = false;
        }

        form.addEventListener('submit', (e) => handleSubmit(e));
    });

    async function handleSubmit(event) {
        event.preventDefault();

        const input = document.getElementById('trainerInput');
        const chatBody = document.getElementById('chatBody');
        const message = input.value.trim();

        if (!message) return false;

        // Add user message to chat
        addMessageToChat('user', message);
        conversationHistory.push({ role: 'user', content: message });
        input.value = '';

        // Show typing indicator
        const typingRow = showTypingIndicator();

        try {
            let response;

            if (isConnected) {
                // Send to real backend
                response = await fetch(API_ENDPOINT, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'chat',
                        message: message,
                        history: conversationHistory
                    })
                });

                if (!response.ok) {
                    throw new Error(`Server error: ${response.status}`);
                }

                const data = await response.json();

                if (data.error) {
                    throw new Error(data.error);
                }

                const reply = data.reply;
                removeTypingIndicator(typingRow);
                addMessageToChat('trainer', reply);
                conversationHistory.push({ role: 'assistant', content: reply });

            } else {
                // Demo mode fallback
                await simulateDemoResponse(message, typingRow);
            }

        } catch (error) {
            console.error('Error:', error);
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

    async function simulateDemoResponse(message, typingRow) {
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 1200));

        const lower = message.toLowerCase();
        let reply = "That sounds interesting! Once the backend is connected, I'll have more detailed knowledge about that. For now, I can help with general Pokémon questions!";

        if (lower.includes('charizard')) {
            reply = "Charizard is one of the most iconic Pokémon! There are many valuable Charizard cards, especially the 1st Edition Base Set Charizard which can be worth thousands depending on condition. What specific Charizard card are you asking about?";
        } else if (lower.includes('pikachu')) {
            reply = "Pikachu is legendary! Prices vary wildly depending on which card—Shadowless, 1st Edition, or newer versions. A PSA 10 Pikachu can be worth anywhere from $50 to several thousand dollars. Which Pikachu are you interested in?";
        } else if (lower.includes('price') || lower.includes('worth')) {
            reply = "Card values depend on several factors: the card name, set, rarity symbol, edition (1st Edition is usually most valuable), condition, and grading (PSA, BGS, etc.). What card are you curious about?";
        } else if (lower.includes('prismatic') || lower.includes('evolution')) {
            reply = "Prismatic Evolution cards are part of newer sets! These tend to be more affordable than vintage cards. Could you tell me the specific card name and set so I can give you better pricing info?";
        } else if (lower.includes('pokemon') || lower.includes('pokémon') || lower.includes('card') || lower.includes('set') || lower.includes('rarity')) {
            reply = "Great Pokémon question! Once the AI backend is fully integrated, I'll have access to a database of card prices, rarity info, and collecting tips. What would you like to know?";
        }

        removeTypingIndicator(typingRow);
        addMessageToChat('trainer', reply);
        conversationHistory.push({ role: 'assistant', content: reply });
    }
</script>

<?php require_once 'includes/footer.php'; ?>