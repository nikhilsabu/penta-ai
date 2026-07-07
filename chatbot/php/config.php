<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function loadEnvFile(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        $pair = explode('=', $trimmed, 2);
        if (count($pair) !== 2) {
            continue;
        }

        $key = trim($pair[0]);
        $value = trim($pair[1]);

        if ($key === '') {
            continue;
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function envValue(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return (string)$value;
    }

    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return (string)$_ENV[$key];
    }

    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return (string)$_SERVER[$key];
    }

    return $default;
}

loadEnvFile(__DIR__ . '/../.env');

if (!defined('DB_HOST')) {
    define('DB_HOST', envValue('DB_HOST', '127.0.0.1'));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', envValue('DB_NAME', 'pentame_chatbot'));
}
if (!defined('DB_USER')) {
    define('DB_USER', envValue('DB_USER', 'root'));
}
if (!defined('DB_PASS')) {
    define('DB_PASS', envValue('DB_PASS', ''));
}
if (!defined('OPENAI_API_KEY')) {
    define('OPENAI_API_KEY', envValue('OPENAI_API_KEY', ''));
}
if (!defined('OPENAI_MODEL')) {
    define('OPENAI_MODEL', envValue('OPENAI_MODEL', 'gpt-4o-mini'));
}
if (!defined('CHATBOT_API_KEY')) {
    define('CHATBOT_API_KEY', envValue('CHATBOT_API_KEY', ''));
}
if (!defined('ADMIN_PASSWORD')) {
    define('ADMIN_PASSWORD', envValue('ADMIN_PASSWORD', ''));
}
if (!defined('ALLOWED_ORIGINS')) {
    define('ALLOWED_ORIGINS', envValue('ALLOWED_ORIGINS', '*'));
}

applyCorsHeaders();

function applyCorsHeaders(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowed = array_map('trim', explode(',', ALLOWED_ORIGINS));

    if (in_array('*', $allowed, true)) {
        header('Access-Control-Allow-Origin: *');
        return;
    }

    if ($origin !== '' && in_array($origin, $allowed, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }
}

function getRequestApiKey(): string
{
    $headerKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if ($headerKey !== '') {
        return (string)$headerKey;
    }

    if (isset($_POST['api_key'])) {
        return trim((string)$_POST['api_key']);
    }

    $input = readJsonBody();
    return trim((string)($input['api_key'] ?? ''));
}

function validateChatbotApiKey(): void
{
    if (CHATBOT_API_KEY === '') {
        return;
    }

    $provided = getRequestApiKey();
    if ($provided === '' || !hash_equals(CHATBOT_API_KEY, $provided)) {
        jsonResponse(['error' => 'Invalid or missing API key.'], 401);
    }
}

function requireAdminAuth(): void
{
    if (ADMIN_PASSWORD === '') {
        jsonResponse(['error' => 'Admin password is not configured.'], 503);
    }

    if (empty($_SESSION['admin_authenticated'])) {
        jsonResponse(['error' => 'Unauthorized.'], 401);
    }
}

function isAdminAuthenticated(): bool
{
    return !empty($_SESSION['admin_authenticated']);
}

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function readJsonBody(): array
{
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $raw = file_get_contents('php://input');
    if (!$raw) {
        $cached = [];
        return $cached;
    }

    $data = json_decode($raw, true);
    $cached = is_array($data) ? $data : [];
    return $cached;
}

function checkRateLimit(string $sessionId, int $maxPerHour = 60): void
{
    if ($sessionId === '') {
        return;
    }

    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM chat_messages
         WHERE session_id = :session_id
           AND role = :role
           AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)'
    );
    $stmt->execute(['session_id' => $sessionId, 'role' => 'user']);
    $count = (int)$stmt->fetchColumn();

    if ($count >= $maxPerHour) {
        jsonResponse([
            'error' => 'Rate limit exceeded. Please try again later.',
            'reply' => 'You have sent too many messages. Please wait a while and try again.',
        ], 429);
    }
}

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function getSetting(string $key, $default = null)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT setting_value FROM chatbot_settings WHERE setting_key = :key LIMIT 1');
    $stmt->execute(['key' => $key]);
    $value = $stmt->fetchColumn();

    return $value === false ? $default : $value;
}

function shouldCaptureLead(string $message): bool
{
    $keywords = ['pricing', 'price', 'quote', 'project', 'budget', 'cost', 'proposal', 'hire', 'services'];
    $msg = strtolower($message);

    foreach ($keywords as $keyword) {
        if (strpos($msg, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

function fetchRagContext(PDO $pdo, string $query, int $limit = 5): array
{
    $query = trim($query);
    if ($query === '') {
        return [];
    }

    $stmt = $pdo->prepare(
        'SELECT source_title, source_url, content, content_type
         FROM knowledge_chunks
         WHERE MATCH(content) AGAINST(:q IN NATURAL LANGUAGE MODE)
         ORDER BY updated_at DESC
         LIMIT :lim'
    );
    $stmt->bindValue(':q', $query, PDO::PARAM_STR);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);

    try {
        $stmt->execute();
        $rows = $stmt->fetchAll();
    } catch (PDOException $e) {
        $fallback = $pdo->prepare(
            'SELECT source_title, source_url, content, content_type
             FROM knowledge_chunks
             WHERE content LIKE :like
             ORDER BY updated_at DESC
             LIMIT :lim'
        );
        $fallback->bindValue(':like', '%' . $query . '%', PDO::PARAM_STR);
        $fallback->bindValue(':lim', $limit, PDO::PARAM_INT);
        $fallback->execute();
        $rows = $fallback->fetchAll();
    }

    return $rows ?: [];
}
