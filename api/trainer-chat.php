<?php
/**
 * PokéTrainer AI Backend API
 * Connects to Anthropic Claude API for intelligent Pokémon card responses
 * 
 * Endpoint: /api/trainer-chat.php
 * Methods: POST
 */

// Prevent output buffering issues
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load configuration
$apiKey = getenv('ANTHROPIC_API_KEY');
if (!$apiKey) {
    // Fallback: check if it's defined in a config file
    $apiKey = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : null;
}

if (!$apiKey) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Anthropic API key not configured. Please set ANTHROPIC_API_KEY environment variable.',
        'success' => false
    ]);
    exit;
}

// Get POST data
$inputData = json_decode(file_get_contents('php://input'), true);

if (!$inputData) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid JSON input',
        'success' => false
    ]);
    exit;
}

$action = $inputData['action'] ?? null;

// Health check endpoint
if ($action === 'health_check') {
    echo json_encode([
        'success' => true,
        'status' => 'connected',
        'message' => 'Backend AI is ready'
    ]);
    exit;
}

// Chat endpoint
if ($action === 'chat') {
    $userMessage = $inputData['message'] ?? '';
    $history = $inputData['history'] ?? [];

    if (empty($userMessage)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Message cannot be empty',
            'success' => false
        ]);
        exit;
    }

    try {
        $response = callAnthropicAPI($apiKey, $userMessage, $history);
        echo json_encode([
            'success' => true,
            'reply' => $response,
            'message' => 'Response generated successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => $e->getMessage(),
            'success' => false
        ]);
    }
    exit;
}

// Invalid action
http_response_code(400);
echo json_encode([
    'error' => 'Invalid action. Use "chat" or "health_check"',
    'success' => false
]);

/**
 * Call Anthropic Claude API with system prompt for Pokémon expertise
 */
function callAnthropicAPI($apiKey, $userMessage, $conversationHistory = [])
{
    $systemPrompt = <<<'PROMPT'
You are Trainer Dex, a knowledgeable Pokémon TCG (Trading Card Game) expert and retro Pokémon trainer. You embody the personality of a nostalgic Game Boy-era trainer who knows everything about Pokémon cards, pricing, rarity, sets, and collecting.

Your personality:
- Enthusiastic about Pokémon and cards
- Uses casual, friendly language ("Yo!", "That's sick!", "Legendary!")
- Stays in character as a trainer, not a generic AI
- References classic Pokémon and retro Pokédex knowledge

Your expertise covers:
- Pokémon card identification and rarity
- Card pricing and valuation (Base Set, Shadowless, 1st Edition, modern sets, etc.)
- Grading systems (PSA, BGS, etc.)
- Card condition factors
- Collecting tips and strategies
- Pokémon game knowledge and facts
- Set information and release dates

Rules:
1. ONLY answer Pokémon-related questions (cards, games, creatures, TCG, merchandise)
2. For non-Pokémon questions, politely decline: "I'm just a Pokémon trainer, not a professor of everything!"
3. When asked about card prices, always mention that value depends on: card name, set, rarity, condition, and grading
4. Provide helpful, accurate information within your knowledge
5. Keep responses concise but engaging (2-3 sentences typically)
6. Never pretend to have real-time pricing data - suggest checking TCGPlayer, eBay sold listings, or PSA values
7. Use enthusiasm to match the retro Pokémon vibe

Example responses:
- User: "How much is a Charizard card?" → Mention that it depends on edition/condition, ask which specific Charizard
- User: "What's the best Pokémon?" → Give a fun opinion while acknowledging it's subjective
- User: "Write me a poem about Python" → "I'm just a Pokémon trainer, not a poet! Stick to Pokémon questions!"
PROMPT;

    $messages = [];

    // Add conversation history
    foreach ($conversationHistory as $msg) {
        $messages[] = [
            'role' => $msg['role'] === 'assistant' ? 'assistant' : 'user',
            'content' => $msg['content']
        ];
    }

    // Add current user message
    $messages[] = [
        'role' => 'user',
        'content' => $userMessage
    ];

    $requestBody = [
        'model' => 'claude-opus-4-1',
        'max_tokens' => 300,
        'system' => $systemPrompt,
        'messages' => $messages
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.anthropic.com/v1/messages',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01'
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($requestBody),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception('Network error: ' . $curlError);
    }

    $responseData = json_decode($response, true);

    if ($httpCode !== 200) {
        $errorMessage = $responseData['error']['message'] ?? 'Unknown error from Anthropic API';
        throw new Exception('API Error (' . $httpCode . '): ' . $errorMessage);
    }

    if (!isset($responseData['content'][0]['text'])) {
        throw new Exception('Invalid response format from Anthropic API');
    }

    return $responseData['content'][0]['text'];
}

?>