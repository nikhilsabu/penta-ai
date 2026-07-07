<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$input = readJsonBody();
$action = (string)($input['action'] ?? $_GET['action'] ?? '');

if ($action === '') {
    jsonResponse(['error' => 'action is required.'], 422);
}

validateChatbotApiKey();
define('CHATBOT_API_ROUTED', true);

switch ($action) {
    case 'send-message':
        require __DIR__ . '/send-message.php';
        break;

    case 'chat-history':
        require __DIR__ . '/chat-history.php';
        break;

    case 'save-lead':
        require __DIR__ . '/save-lead.php';
        break;

    case 'settings':
        require __DIR__ . '/settings.php';
        break;

    default:
        jsonResponse(['error' => 'Unknown action.'], 422);
}
