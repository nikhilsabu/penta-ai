<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (!defined('CHATBOT_API_ROUTED')) {
    validateChatbotApiKey();
}

$input = readJsonBody();
$sessionId = trim((string)($input['session_id'] ?? ''));

if ($sessionId === '') {
    jsonResponse(['history' => []]);
}

$pdo = getPDO();
$stmt = $pdo->prepare(
    'SELECT role, content, created_at
     FROM chat_messages
     WHERE session_id = :session_id
     ORDER BY id ASC
     LIMIT 200'
);
$stmt->execute(['session_id' => $sessionId]);
$history = $stmt->fetchAll();

jsonResponse(['history' => $history]);
