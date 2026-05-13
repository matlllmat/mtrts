<?php
// modules/inbox/index.php — Full-page Inbox (unified for IT roles, personal for others)
$module = 'inbox';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';

$user_id = (int)($_SESSION['user_id'] ?? 0);
$is_it_role = in_array((int)($_SESSION['role_id'] ?? 0), [1, 2, 3, 8], true);

$q      = trim($_GET['q'] ?? '');
$filter = in_array($_GET['filter'] ?? '', ['all', 'unread', 'today', 'week']) ? $_GET['filter'] : 'all';
$page   = max(1, (int)($_GET['p'] ?? 1));
$per    = 20;

try {
    if ($is_it_role) {
        $items  = get_unified_inbox_page($pdo, $user_id, $q, $filter, $page, $per);
        $total  = count_unified_inbox_page($pdo, $user_id, $q, $filter);
        $unseen = get_unseen_email_count($pdo);
    } else {
        $items  = get_inbox_messages_page($pdo, $user_id, $q, $filter, $page, $per);
        $total  = count_inbox_messages_page($pdo, $user_id, $q, $filter);
        $unseen = 0;
    }
} catch (Throwable $e) {
    $items        = [];
    $total        = 0;
    $unseen       = 0;
    $inbox_error  = $e->getMessage();
}

$pages        = max(1, (int) ceil($total / $per));
$unread_count = get_unread_inbox_count($pdo, $user_id);

require __DIR__ . '/index.view.php';
require_once __DIR__ . '/../../includes/footer.php';
