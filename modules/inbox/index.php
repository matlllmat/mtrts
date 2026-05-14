<?php
// modules/inbox/index.php — Full-page Inbox (unified for IT roles, personal for others)
$module = 'inbox';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';

$user_id    = (int)($_SESSION['user_id'] ?? 0);
$role_id    = (int)($_SESSION['role_id'] ?? 0);
$is_it_role = in_array($role_id, [1, 2, 3, 8], true);

$q      = trim($_GET['q'] ?? '');
$filter = in_array($_GET['filter'] ?? '', ['all', 'unread', 'today', 'week']) ? $_GET['filter'] : 'all';
$page   = max(1, (int)($_GET['p'] ?? 1));
$per    = 20;
$tab    = in_array($_GET['tab'] ?? '', ['inbox', 'sent']) ? ($_GET['tab'] ?? 'inbox') : 'inbox';

// ── Thread view: ?thread=<message_id> ────────────────────────────────────────
$thread_id  = isset($_GET['thread']) ? (int)$_GET['thread'] : 0;
$thread     = [];
$thread_msg = null;   // anchor message for the reply form

if ($thread_id > 0) {
    $thread = get_inbox_thread($pdo, $thread_id, $user_id);
    if (!empty($thread)) {
        // Mark the anchor message as read if the current user is the recipient
        $anchor = get_inbox_message($pdo, $thread_id, $user_id);
        if ($anchor && (int)$anchor['recipient_id'] === $user_id && $anchor['read_at'] === null) {
            mark_inbox_message_read($pdo, $thread_id, $user_id);
        }
        $thread_msg = $anchor ?: $thread[0];
    }
}

// ── List view ─────────────────────────────────────────────────────────────────
$items  = [];
$total  = 0;
$unseen = 0;

try {
    if ($thread_id === 0) {
        if ($tab === 'sent') {
            $items = get_sent_messages_page($pdo, $user_id, $q, $filter, $page, $per);
            $total = count_sent_messages_page($pdo, $user_id, $q, $filter);
        } elseif ($is_it_role) {
            $items  = get_unified_inbox_page($pdo, $user_id, $q, $filter, $page, $per);
            $total  = count_unified_inbox_page($pdo, $user_id, $q, $filter);
            $unseen = get_unseen_email_count($pdo);
        } else {
            $items = get_inbox_messages_page($pdo, $user_id, $q, $filter, $page, $per);
            $total = count_inbox_messages_page($pdo, $user_id, $q, $filter);
        }
    }
} catch (Throwable $e) {
    $items       = [];
    $total       = 0;
    $unseen      = 0;
    $inbox_error = $e->getMessage();
}

$pages        = max(1, (int) ceil($total / $per));
$unread_count = get_unread_inbox_count($pdo, $user_id);

require __DIR__ . '/index.view.php';
require_once __DIR__ . '/../../includes/footer.php';
