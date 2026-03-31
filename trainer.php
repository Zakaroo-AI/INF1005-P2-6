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
                <span class="trainer-status">DS Link Active</span>
            </div>

            <div class="trainer-chat-body" id="chatBody">
                <div class="chat-row trainer-msg">
                    <div class="chat-bubble">
                        Yo trainer! I’m Trainer Dex. Ask me about Pokémon, card prices, card facts, rarity, or collecting tips.
                    </div>
                </div>

                <div class="chat-row trainer-msg">
                    <div class="chat-bubble">
                        If it’s not Pokémon-related, I’ll have to pass — I’m just a Pokémon trainer, not a professor of everything.
                    </div>
                </div>

                <div class="chat-row user-msg">
                    <div class="chat-bubble">
                        How much is a Charizard card worth?
                    </div>
                </div>

                <div class="chat-row trainer-msg">
                    <div class="chat-bubble">
                        That depends on the exact card, set, rarity, condition, and grading. Show me the card name or set and I’ll help narrow it down.
                    </div>
                </div>
            </div>

            <form class="trainer-chat-input" onsubmit="return fakeTrainerReply(event)">
                <div class="input-group">
                    <input
                        type="text"
                        id="trainerInput"
                        class="form-control"
                        placeholder="Ask about Pokémon cards, prices, or facts..."
                        maxlength="200"
                        autocomplete="off"
                    >
                    <button class="btn btn-warning fw-bold px-4" type="submit">
                        <i class="bi bi-send-fill me-1"></i>Send
                    </button>
                </div>
                <small class="text-muted d-block mt-2">
                    Demo UI for now — backend AI integration can be connected later.
                </small>
            </form>
        </div>
    </div>
</section>

<script>
function fakeTrainerReply(event) {
    event.preventDefault();

    const input = document.getElementById('trainerInput');
    const chatBody = document.getElementById('chatBody');
    const message = input.value.trim();

    if (!message) return false;

    const userRow = document.createElement('div');
    userRow.className = 'chat-row user-msg';
    userRow.innerHTML = `<div class="chat-bubble"></div>`;
    userRow.querySelector('.chat-bubble').textContent = message;
    chatBody.appendChild(userRow);

    const lower = message.toLowerCase();
    let reply = "I’m just a Pokémon trainer, so I can only help with Pokémon, cards, card pricing, rarity, sets, and collecting info.";

    if (
        lower.includes('pokemon') ||
        lower.includes('pokémon') ||
        lower.includes('pikachu') ||
        lower.includes('charizard') ||
        lower.includes('card') ||
        lower.includes('price') ||
        lower.includes('rarity') ||
        lower.includes('set') ||
        lower.includes('trainer')
    ) {
        reply = "That sounds like a Pokémon question. Once the AI backend is connected, I’ll be able to answer that properly for you.";
    }

    const trainerRow = document.createElement('div');
    trainerRow.className = 'chat-row trainer-msg';
    trainerRow.innerHTML = `<div class="chat-bubble"></div>`;
    trainerRow.querySelector('.chat-bubble').textContent = reply;

    setTimeout(() => {
        chatBody.appendChild(trainerRow);
        chatBody.scrollTop = chatBody.scrollHeight;
    }, 350);

    input.value = '';
    chatBody.scrollTop = chatBody.scrollHeight;
    return false;
}
</script>

<?php require_once 'includes/footer.php'; ?>