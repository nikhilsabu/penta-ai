<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (!defined('CHATBOT_API_ROUTED')) {
    validateChatbotApiKey();
}

$input = readJsonBody();
$sessionId = trim((string)($input['session_id'] ?? ''));

$requiredFields = [
    'name',
    'company_name',
    'email',
    'phone',
    'project_type',
    'estimated_budget',
    'timeline',
    'project_description',
];

$errors = [];
foreach ($requiredFields as $field) {
    if (trim((string)($input[$field] ?? '')) === '') {
        $errors[] = $field;
    }
}

if ($sessionId === '') {
    $errors[] = 'session_id';
}

if (!empty($errors)) {
    jsonResponse([
        'error' => 'Missing required fields.',
        'fields' => $errors,
    ], 422);
}

if (!filter_var((string)$input['email'], FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Invalid email address.'], 422);
}

$pdo = getPDO();
$stmt = $pdo->prepare(
    'INSERT INTO leads (
        session_id, name, company_name, email, phone,
        project_type, estimated_budget, timeline, project_description, created_at
    ) VALUES (
        :session_id, :name, :company_name, :email, :phone,
        :project_type, :estimated_budget, :timeline, :project_description, NOW()
    )'
);

$stmt->execute([
    'session_id' => $sessionId,
    'name' => trim((string)$input['name']),
    'company_name' => trim((string)$input['company_name']),
    'email' => trim((string)$input['email']),
    'phone' => trim((string)$input['phone']),
    'project_type' => trim((string)$input['project_type']),
    'estimated_budget' => trim((string)$input['estimated_budget']),
    'timeline' => trim((string)$input['timeline']),
    'project_description' => trim((string)$input['project_description']),
]);

$sendEmail = (int)getSetting('send_lead_email', '0') === 1;
if ($sendEmail) {
    $to = (string)getSetting('sales_email', 'sales@pentame.com');
    $subject = 'New Pentame Chatbot Lead';
    $body = "Name: {$input['name']}\n"
        . "Company: {$input['company_name']}\n"
        . "Email: {$input['email']}\n"
        . "Phone: {$input['phone']}\n"
        . "Project Type: {$input['project_type']}\n"
        . "Budget: {$input['estimated_budget']}\n"
        . "Timeline: {$input['timeline']}\n"
        . "Description: {$input['project_description']}\n";
    @mail($to, $subject, $body);
}

jsonResponse([
    'success' => true,
    'message' => 'Thank you. Your details are received. Our team will contact you soon.'
]);
