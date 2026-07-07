<?php

declare(strict_types=1);

require_once __DIR__ . '/../php/config.php';

requireAdminAuth();

$pdo = getPDO();

if (isset($_GET['export']) && $_GET['export'] === 'leads') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=leads_export.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Name', 'Company', 'Email', 'Phone', 'Project Type', 'Budget', 'Timeline', 'Description', 'Created At']);

    $stmt = $pdo->query('SELECT name, company_name, email, phone, project_type, estimated_budget, timeline, project_description, created_at FROM leads ORDER BY id DESC');
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

$input = readJsonBody();
$action = (string)($input['action'] ?? '');

switch ($action) {
    case 'analytics':
        $totalChats = (int)$pdo->query('SELECT COUNT(*) FROM chat_messages')->fetchColumn();
        $totalConversations = (int)$pdo->query('SELECT COUNT(DISTINCT session_id) FROM chat_messages')->fetchColumn();
        $totalLeads = (int)$pdo->query('SELECT COUNT(*) FROM leads')->fetchColumn();
        $totalDocuments = (int)$pdo->query('SELECT COUNT(*) FROM uploaded_documents')->fetchColumn();
        $todaySessions = (int)$pdo->query('SELECT COUNT(DISTINCT session_id) FROM chat_messages WHERE DATE(created_at) = CURDATE()')->fetchColumn();
        jsonResponse([
            'total_chats' => $totalChats,
            'total_conversations' => $totalConversations,
            'total_leads' => $totalLeads,
            'total_documents' => $totalDocuments,
            'today_sessions' => $todaySessions,
        ]);
        break;

    case 'conversations':
        $q = trim((string)($input['q'] ?? ''));
        $params = [];
        $where = '';

        if ($q !== '') {
            $where = 'WHERE cm.session_id IN (
                SELECT DISTINCT session_id
                FROM chat_messages
                WHERE session_id LIKE :q OR content LIKE :q_content
            )';
            $params['q'] = '%' . $q . '%';
            $params['q_content'] = '%' . $q . '%';
        }

        $sql = "
            SELECT
                cm.session_id,
                COUNT(*) AS message_count,
                MIN(cm.created_at) AS started_at,
                MAX(cm.created_at) AS last_active,
                (
                    SELECT c2.content
                    FROM chat_messages c2
                    WHERE c2.session_id = cm.session_id
                    ORDER BY c2.id DESC
                    LIMIT 1
                ) AS last_message,
                (
                    SELECT c2.role
                    FROM chat_messages c2
                    WHERE c2.session_id = cm.session_id
                    ORDER BY c2.id DESC
                    LIMIT 1
                ) AS last_role,
                EXISTS(
                    SELECT 1 FROM leads l WHERE l.session_id = cm.session_id LIMIT 1
                ) AS has_lead
            FROM chat_messages cm
            {$where}
            GROUP BY cm.session_id
            ORDER BY last_active DESC
            LIMIT 200
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['message_count'] = (int)$row['message_count'];
            $row['has_lead'] = (int)$row['has_lead'] === 1;
            $row['status'] = resolveConversationStatus(
                (string)$row['last_message'],
                (string)$row['last_role'],
                (string)$row['last_active'],
                (bool)$row['has_lead']
            );
        }
        unset($row);

        jsonResponse(['rows' => $rows]);
        break;

    case 'conversation-detail':
        $sessionId = trim((string)($input['session_id'] ?? ''));
        if ($sessionId === '') {
            jsonResponse(['error' => 'session_id is required.'], 422);
        }

        $metaStmt = $pdo->prepare(
            'SELECT
                COUNT(*) AS message_count,
                MIN(created_at) AS started_at,
                MAX(created_at) AS last_active
             FROM chat_messages
             WHERE session_id = :session_id'
        );
        $metaStmt->execute(['session_id' => $sessionId]);
        $meta = $metaStmt->fetch() ?: [];

        $leadStmt = $pdo->prepare(
            'SELECT name, email, phone, project_type, created_at
             FROM leads WHERE session_id = :session_id LIMIT 1'
        );
        $leadStmt->execute(['session_id' => $sessionId]);
        $lead = $leadStmt->fetch() ?: null;

        $msgStmt = $pdo->prepare(
            'SELECT role, content, created_at
             FROM chat_messages
             WHERE session_id = :session_id
             ORDER BY id ASC'
        );
        $msgStmt->execute(['session_id' => $sessionId]);
        $messages = $msgStmt->fetchAll();

        if (!$messages) {
            jsonResponse(['error' => 'Conversation not found.'], 404);
        }

        $lastMessage = (string)($messages[count($messages) - 1]['content'] ?? '');
        $lastRole = (string)($messages[count($messages) - 1]['role'] ?? '');

        jsonResponse([
            'session_id' => $sessionId,
            'message_count' => (int)($meta['message_count'] ?? 0),
            'started_at' => (string)($meta['started_at'] ?? ''),
            'last_active' => (string)($meta['last_active'] ?? ''),
            'has_lead' => $lead !== null,
            'lead' => $lead,
            'status' => resolveConversationStatus(
                $lastMessage,
                $lastRole,
                (string)($meta['last_active'] ?? ''),
                $lead !== null
            ),
            'messages' => $messages,
        ]);
        break;

    case 'delete-conversation':
        $sessionId = trim((string)($input['session_id'] ?? ''));
        if ($sessionId === '') {
            jsonResponse(['error' => 'session_id is required.'], 422);
        }

        $pdo->beginTransaction();
        try {
            $delMsgs = $pdo->prepare('DELETE FROM chat_messages WHERE session_id = :session_id');
            $delMsgs->execute(['session_id' => $sessionId]);

            $delLeads = $pdo->prepare('DELETE FROM leads WHERE session_id = :session_id');
            $delLeads->execute(['session_id' => $sessionId]);

            $pdo->commit();
            jsonResponse([
                'success' => true,
                'deleted_messages' => $delMsgs->rowCount(),
                'deleted_leads' => $delLeads->rowCount(),
            ]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            jsonResponse(['error' => 'Could not delete conversation.'], 500);
        }
        break;

    case 'chats':
        $q = trim((string)($input['q'] ?? ''));
        if ($q !== '') {
            $stmt = $pdo->prepare('SELECT session_id, role, content, created_at FROM chat_messages WHERE content LIKE :q ORDER BY id DESC LIMIT 300');
            $stmt->execute(['q' => '%' . $q . '%']);
        } else {
            $stmt = $pdo->query('SELECT session_id, role, content, created_at FROM chat_messages ORDER BY id DESC LIMIT 300');
        }
        jsonResponse(['rows' => $stmt->fetchAll()]);
        break;

    case 'leads':
        $stmt = $pdo->query('SELECT name, company_name, email, phone, project_type, estimated_budget, timeline, project_description, created_at FROM leads ORDER BY id DESC LIMIT 300');
        jsonResponse(['rows' => $stmt->fetchAll()]);
        break;

    case 'faqs':
        $stmt = $pdo->query('SELECT id, question, answer FROM faqs ORDER BY id DESC LIMIT 200');
        jsonResponse(['rows' => $stmt->fetchAll()]);
        break;

    case 'save-faq':
        $question = trim((string)($input['question'] ?? ''));
        $answer = trim((string)($input['answer'] ?? ''));
        if ($question === '' || $answer === '') {
            jsonResponse(['error' => 'Question and answer are required.'], 422);
        }
        $stmt = $pdo->prepare('INSERT INTO faqs (question, answer, created_at) VALUES (:q, :a, NOW())');
        $stmt->execute(['q' => $question, 'a' => $answer]);

        // Make FAQs instantly available in RAG.
        $chunkStmt = $pdo->prepare('INSERT INTO knowledge_chunks (source_title, source_url, content, content_type, updated_at) VALUES (:t, :u, :c, :ct, NOW())');
        $chunkStmt->execute([
            't' => 'FAQ: ' . $question,
            'u' => '/faqs',
            'c' => $question . "\n" . $answer,
            'ct' => 'faq',
        ]);

        jsonResponse(['success' => true]);
        break;

    case 'settings-get':
        $_POST = [];
        jsonResponse([
            'chatbot_enabled' => (int)getSetting('chatbot_enabled', '1'),
            'system_prompt' => (string)getSetting('system_prompt', ''),
            'sales_email' => (string)getSetting('sales_email', 'sales@pentame.com'),
        ]);
        break;

    case 'settings-update':
        $updates = $input['updates'] ?? [];
        if (!is_array($updates)) {
            jsonResponse(['error' => 'Invalid updates payload.'], 422);
        }

        $stmt = $pdo->prepare('INSERT INTO chatbot_settings (setting_key, setting_value, updated_at) VALUES (:k, :v, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()');
        foreach ($updates as $k => $v) {
            $stmt->execute([
                'k' => (string)$k,
                'v' => is_scalar($v) ? (string)$v : json_encode($v),
            ]);
        }
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action.'], 422);
}

function resolveConversationStatus(
    string $lastMessage,
    string $lastRole,
    string $lastActive,
    bool $hasLead
): string {
    $lastMessageLower = strtolower($lastMessage);
    $errorHints = [
        'unable to respond',
        'quota exceeded',
        'invalid api key',
        'api key is invalid',
        'connection issue',
        'server configuration',
        'setup incomplete',
    ];

    foreach ($errorHints as $hint) {
        if (str_contains($lastMessageLower, $hint)) {
            return 'error';
        }
    }

    if ($lastActive !== '') {
        $lastTime = strtotime($lastActive);
        if ($lastTime !== false && (time() - $lastTime) <= 1800) {
            return 'active';
        }
    }

    if ($hasLead) {
        return 'lead';
    }

    return 'idle';
}
