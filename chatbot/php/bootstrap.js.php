<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-store');

$config = [
    'apiKey' => CHATBOT_API_KEY,
    'apiBase' => 'php/api.php',
    'uploadBase' => 'php/upload-document.php',
];

echo 'window.PENTAME_CHATBOT_CONFIG=' . json_encode($config, JSON_UNESCAPED_SLASHES) . ';';
