<?php
// modules/inbox/fetch.php
// AJAX — returns JSON: { count: int, items: [...] }
// Called by the navbar inbox icon polling every 30 seconds.

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/functions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Only IT staff triage the email inbox
if (!in_array((int)($_SESSION['role_id'] ?? 0), [1, 2, 3, 8], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$count = get_unseen_email_count($pdo);
$items = get_recent_email_inbox($pdo, 10);

header('Content-Type: application/json');
echo json_encode([
    'count' => $count,
    'items' => array_map(function ($t) {
        $preview = trim((string) ($t['description'] ?? ''));
        if (mb_strlen($preview) > 100) {
            $preview = mb_substr($preview, 0, 100) . '…';
        }
        return [
            'ticket_id'     => (int) $t['ticket_id'],
            'ticket_number' => $t['ticket_number'],
            'sender_name'   => $t['sender_name'] ?: 'Unknown sender',
            'sender_email'  => $t['sender_email'] ?: '',
            'subject'       => $t['title'],
            'preview'       => $preview,
            'is_unread'     => $t['email_seen_at'] === null,
            'created_at'    => $t['created_at'],
        ];
    }, $items),
]);
