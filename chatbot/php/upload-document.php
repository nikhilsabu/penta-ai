<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

validateChatbotApiKey();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Invalid request method.'], 405);
}

$sessionId = trim((string)($_POST['session_id'] ?? 'system'));
$category = trim((string)($_POST['category'] ?? 'general'));

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['error' => 'No file uploaded.'], 422);
}

$file = $_FILES['file'];
$maxBytes = 5 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    jsonResponse(['error' => 'File exceeds 5 MB limit.'], 422);
}
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['pdf', 'txt', 'md', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'webp'];

if (!in_array($ext, $allowed, true)) {
    jsonResponse(['error' => 'File type not allowed.'], 422);
}

$uploadDir = realpath(__DIR__ . '/../uploads');
if ($uploadDir === false) {
    jsonResponse(['error' => 'Upload directory is missing.'], 500);
}

$safeName = uniqid('doc_', true) . '.' . $ext;
$targetPath = $uploadDir . DIRECTORY_SEPARATOR . $safeName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    jsonResponse(['error' => 'Could not move uploaded file.'], 500);
}

$publicPath = 'uploads/' . $safeName;

$pdo = getPDO();
$stmt = $pdo->prepare(
    'INSERT INTO uploaded_documents (
        session_id, original_name, stored_name, file_path, mime_type, category, created_at
    ) VALUES (
        :session_id, :original_name, :stored_name, :file_path, :mime_type, :category, NOW()
    )'
);

$stmt->execute([
    'session_id' => $sessionId,
    'original_name' => $file['name'],
    'stored_name' => $safeName,
    'file_path' => $publicPath,
    'mime_type' => (string)$file['type'],
    'category' => $category,
]);

// For text-based files, auto-store chunks for RAG.
if (in_array($ext, ['txt', 'md'], true)) {
    $text = file_get_contents($targetPath) ?: '';
    $chunks = str_split($text, 1200);
    $chunkStmt = $pdo->prepare(
        'INSERT INTO knowledge_chunks (source_title, source_url, content, content_type, updated_at)
         VALUES (:source_title, :source_url, :content, :content_type, NOW())'
    );

    foreach ($chunks as $chunk) {
        if (trim($chunk) === '') {
            continue;
        }
        $chunkStmt->execute([
            'source_title' => $file['name'],
            'source_url' => $publicPath,
            'content' => $chunk,
            'content_type' => 'document',
        ]);
    }
}

jsonResponse([
    'success' => true,
    'message' => 'Document uploaded successfully.',
    'file' => $publicPath,
]);
