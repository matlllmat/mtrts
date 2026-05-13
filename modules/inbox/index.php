<?php
// modules/inbox/index.php — Full-page Gmail-style Email Inbox
$module = 'inbox';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';

// Only IT staff have access
if (!in_array((int)($_SESSION['role_id'] ?? 0), [1, 2, 3, 8], true)) {
    require_once __DIR__ . '/../denied.php';
    exit;
}

$q       = trim($_GET['q'] ?? '');
$filter  = in_array($_GET['filter'] ?? '', ['all','unread','today','week']) ? $_GET['filter'] : 'all';
$page    = max(1, (int)($_GET['p'] ?? 1));
$per     = 20;

$emails  = get_email_inbox_page($pdo, $q, $filter, $page, $per);
$total   = count_email_inbox_page($pdo, $q, $filter);
$pages   = max(1, (int) ceil($total / $per));
$unseen  = get_unseen_email_count($pdo);

require __DIR__ . '/index.view.php';
require_once __DIR__ . '/../../includes/footer.php';
