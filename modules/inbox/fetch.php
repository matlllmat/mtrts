<?php
// modules/inbox/fetch.php
// AJAX — returns JSON: { count: int, items: [...] }
// Called by the navbar inbox icon polling every 30 seconds.
// Serves all eight roles (Requirements 2.1, 2.6, 2.7).

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$role_id = (int) ($_SESSION['role_id'] ?? 0);
$it_roles = [1, 2, 3, 8];

try {
    if (in_array($role_id, $it_roles, true)) {
        // IT roles: combine unseen email count + unread inbox_messages count
        $email_count = get_unseen_email_count($pdo);
        $msg_count   = get_unread_inbox_count($pdo, $user_id);
        $count       = $email_count + $msg_count;

        // Fetch both email tickets and in-app messages, merge by timestamp
        $email_raw = get_recent_email_inbox($pdo, 10);
        $msg_raw   = get_recent_inbox_messages($pdo, $user_id, 10);

        $email_items = array_map(function ($t) {
            $preview = trim((string) ($t['description'] ?? ''));
            if (mb_strlen($preview) > 100) {
                $preview = mb_substr($preview, 0, 100) . '…';
            }
            return [
                'ticket_id'   => (int) $t['ticket_id'],
                'message_id'  => null,
                'sender_name' => $t['sender_name'] ?: 'Unknown sender',
                'subject'     => $t['title'],
                'preview'     => $preview,
                'is_unread'   => $t['email_seen_at'] === null,
                'created_at'  => $t['created_at'],
            ];
        }, $email_raw);

        $msg_items = array_map(function ($m) {
            return [
                'ticket_id'   => null,
                'message_id'  => (int) $m['message_id'],
                'sender_name' => $m['sender_name'] ?: 'Unknown sender',
                'subject'     => $m['subject'],
                'preview'     => $m['preview'],
                'is_unread'   => $m['read_at'] === null,
                'created_at'  => $m['sent_at'],
            ];
        }, $msg_raw);

        // Merge and sort by created_at descending, keep top 10
        $merged = array_merge($email_items, $msg_items);
        usort($merged, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
        $items = array_slice($merged, 0, 10);
    } else {
        // Non-IT roles: only their own inbox_messages (Requirements 2.2, 2.3, 2.6)
        $count = get_unread_inbox_count($pdo, $user_id);
        $raw   = get_recent_inbox_messages($pdo, $user_id, 10);

        $items = array_map(function ($m) {
            return [
                'message_id'  => (int) $m['message_id'],
                'sender_name' => $m['sender_name'] ?: 'Unknown sender',
                'subject'     => $m['subject'],
                'preview'     => $m['preview'],
                'is_unread'   => $m['read_at'] === null,
                'created_at'  => $m['sent_at'],
            ];
        }, $raw);
    }
} catch (Throwable $e) {
    // On any DB or runtime error, return a safe empty payload so the navbar JS never throws
    // (Requirement 2.7)
    echo json_encode(['count' => 0, 'items' => []]);
    exit;
}

echo json_encode([
    'count' => $count,
    'items' => $items,
]);
