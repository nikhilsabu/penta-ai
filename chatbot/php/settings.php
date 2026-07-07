<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (!defined('CHATBOT_API_ROUTED')) {
    validateChatbotApiKey();
}

$input = readJsonBody();
$settingsAction = (string)($input['settings_action'] ?? 'get');

$pdo = getPDO();

if ($settingsAction === 'get') {
    $stmt = $pdo->query('SELECT setting_key, setting_value FROM chatbot_settings');
    $rows = $stmt->fetchAll();

    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    jsonResponse([
        'chatbot_enabled' => (int)($settings['chatbot_enabled'] ?? 1),
        'system_prompt' => (string)($settings['system_prompt'] ?? ''),
        'sales_email' => (string)($settings['sales_email'] ?? 'sales@pentame.com'),
    ]);
}

jsonResponse(['error' => 'Settings updates require admin access.'], 403);
