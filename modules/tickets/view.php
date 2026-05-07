<?php
// modules/tickets/view.php — Ticket Details page
$module = 'tickets';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . 'modules/tickets/index.php');
    exit;
}

$ticket = get_ticket_by_id($pdo, $id);
if (!$ticket) {
    header('Location: ' . BASE_URL . 'modules/tickets/index.php');
    exit;
}

// Role checks
$is_staff = in_array($_SESSION['role_id'], [1, 2, 3, 4, 8]);
if (!$is_staff && $ticket['requester_id'] != $_SESSION['user_id']) {
    // Regular users can only view their own tickets
    require_once __DIR__ . '/../denied.php';
    exit;
}

$attachments    = get_ticket_attachments($pdo, $id);
$comments       = get_ticket_comments($pdo, $id, $is_staff);
$dynamic_fields = get_ticket_dynamic_fields($pdo, $id);

// If staff, get assignable users for the assign form
$assignables = [];
if ($is_staff) {
    $assignables = $pdo->query("SELECT user_id, full_name, role_id FROM users WHERE role_id IN (2,3,4,8) AND is_active = 1 ORDER BY full_name")->fetchAll();
}

// See if there's an existing Work Order for this ticket
$related_wos = [];
if ($is_staff) {
    $stmt = $pdo->prepare("SELECT wo_id, wo_number, status, scheduled_start FROM work_orders WHERE ticket_id = ?");
    $stmt->execute([$id]);
    $related_wos = $stmt->fetchAll();
}

// Duplicate handling
$original_ticket = null;
if (!empty($ticket['duplicate_of_id'])) {
    $original_ticket = get_ticket_by_id($pdo, (int)$ticket['duplicate_of_id']);
}
$stmt_dups = $pdo->prepare("SELECT ticket_id, ticket_number, status FROM tickets WHERE duplicate_of_id = ?");
$stmt_dups->execute([$id]);
$duplicates = $stmt_dups->fetchAll();

require __DIR__ . '/view.view.php';
require_once __DIR__ . '/../../includes/footer.php';
