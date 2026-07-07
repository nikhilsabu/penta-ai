<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (!defined('CHATBOT_API_ROUTED')) {
    validateChatbotApiKey();
}

$input = readJsonBody();
$sessionId = trim((string)($input['session_id'] ?? ''));
$message = trim((string)($input['message'] ?? ''));
$history = $input['history'] ?? [];

if ($sessionId === '' || $message === '') {
    jsonResponse(['error' => 'session_id and message are required.'], 422);
}

checkRateLimit($sessionId);

if (OPENAI_API_KEY === '' || OPENAI_API_KEY === 'YOUR_OPENAI_API_KEY') {
    jsonResponse([
        'reply' => 'Chatbot setup incomplete: OpenAI API key is missing. Create chatbot/.env from chatbot/.env.example, add OPENAI_API_KEY, and try again.',
        'lead_capture' => false,
        'suggested_responses' => ['Contact Us', 'Book Consultation']
    ]);
}

if (!function_exists('curl_init')) {
    jsonResponse([
        'reply' => 'Server setup incomplete: PHP cURL extension is not enabled. Please enable cURL in PHP and restart Apache.',
        'lead_capture' => false,
        'suggested_responses' => ['Contact Us']
    ]);
}

try {
    $pdo = getPDO();

    $chatEnabled = (int)getSetting('chatbot_enabled', '1');
    if ($chatEnabled === 0) {
        jsonResponse([
            'reply' => 'The assistant is currently unavailable. Please contact Pentame through the contact page.',
            'lead_capture' => false,
            'suggested_responses' => []
        ]);
    }

    $defaultPrompt = "You are Pentame's AI Assistant.\n\nOnly answer questions related to Pentame.\nAnswer professionally.\nRecommend Pentame services.\nSuggest relevant pages.\nCapture leads when appropriate.\nNever invent company information.\nIf unsure, politely ask the visitor to contact Pentame.";
    $systemPrompt = (string)getSetting('system_prompt', $defaultPrompt);

    $ragRows = fetchRagContext($pdo, $message, 6);
    $ragText = '';
    foreach ($ragRows as $index => $row) {
        $ragText .= "[" . ($index + 1) . "] " . ($row['source_title'] ?: 'Untitled') . "\n";
        $ragText .= (string)$row['content'] . "\n";
        if (!empty($row['source_url'])) {
            $ragText .= "URL: " . $row['source_url'] . "\n";
        }
        $ragText .= "\n";
    }

    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
    ];

    if ($ragText !== '') {
        $messages[] = ['role' => 'system', 'content' => "Use this knowledge base context when relevant:\n" . $ragText];
    }

    if (is_array($history)) {
        $history = array_slice($history, -12);
        foreach ($history as $item) {
            if (!isset($item['role'], $item['content'])) {
                continue;
            }
            $role = $item['role'] === 'assistant' ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => (string)$item['content']];
        }
    }

    $messages[] = ['role' => 'user', 'content' => $message];

    $payload = [
        'model' => OPENAI_MODEL,
        'temperature' => 0.4,
        'messages' => $messages
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false || $status >= 400) {
        $fallbackReply = 'I am unable to respond right now. Please contact Pentame through the Contact page for immediate help.';

        if ($response !== false) {
            $errorPayload = json_decode((string)$response, true);
            $apiCode = (string)($errorPayload['error']['code'] ?? '');
            $apiType = (string)($errorPayload['error']['type'] ?? '');

            if ($apiCode === 'insufficient_quota' || $apiType === 'insufficient_quota') {
                $fallbackReply = 'OpenAI quota exceeded for this API key. Please add billing/credits in your OpenAI account, then try again.';
            } elseif ($apiCode === 'invalid_api_key' || $apiType === 'invalid_request_error') {
                $fallbackReply = 'OpenAI API key is invalid or expired. Please update OPENAI_API_KEY in chatbot/.env with a valid key.';
            }
        }

        $stmt = $pdo->prepare(
            'INSERT INTO chat_messages (session_id, role, content, raw_response, created_at)
             VALUES (:session_id, :role, :content, :raw_response, NOW())'
        );
        $stmt->execute([
            'session_id' => $sessionId,
            'role' => 'user',
            'content' => $message,
            'raw_response' => null,
        ]);
        $stmt->execute([
            'session_id' => $sessionId,
            'role' => 'assistant',
            'content' => $fallbackReply,
            'raw_response' => $error ?: $response,
        ]);

        jsonResponse([
            'reply' => $fallbackReply,
            'lead_capture' => shouldCaptureLead($message),
            'suggested_responses' => ['Talk to Sales', 'Contact Us', 'Book Consultation']
        ]);
    }

    $data = json_decode($response, true);
    $reply = $data['choices'][0]['message']['content'] ?? 'Please contact Pentame for more details.';

    $stmt = $pdo->prepare(
        'INSERT INTO chat_messages (session_id, role, content, raw_response, created_at)
         VALUES (:session_id, :role, :content, :raw_response, NOW())'
    );
    $stmt->execute([
        'session_id' => $sessionId,
        'role' => 'user',
        'content' => $message,
        'raw_response' => null,
    ]);
    $stmt->execute([
        'session_id' => $sessionId,
        'role' => 'assistant',
        'content' => $reply,
        'raw_response' => $response,
    ]);

    $suggested = ['Tell me about your services', 'Show Portfolio', 'Pricing', 'Book Consultation'];

    jsonResponse([
        'reply' => $reply,
        'lead_capture' => shouldCaptureLead($message),
        'suggested_responses' => $suggested,
    ]);
} catch (Throwable $e) {
    jsonResponse([
        'reply' => 'Server configuration issue detected. Please check database setup and API settings, then try again.',
        'lead_capture' => false,
        'suggested_responses' => ['Contact Us', 'Book Consultation'],
    ], 200);
}
