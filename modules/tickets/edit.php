<?php
// modules/tickets/edit.php
$module = 'tickets';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . BASE_URL . 'modules/tickets/index.php');
    exit;
}

$t = get_ticket_by_id($pdo, $id);
if (!$t) {
    header('Location: ' . BASE_URL . 'modules/tickets/index.php');
    exit;
}

$is_staff = in_array($_SESSION['role_id'], [1, 2, 3, 4, 8]);
if (!$is_staff && $t['requester_id'] != $_SESSION['user_id']) {
    require_once __DIR__ . '/../denied.php';
    exit;
}

$categories     = get_all_categories($pdo);
$locations      = get_all_locations($pdo);
$kb_articles    = get_recommended_kb_articles($pdo, $t['category_id']);
$assets         = $pdo->query("SELECT asset_id, asset_tag, serial_number, manufacturer, model FROM assets WHERE status IN ('active', 'spare') ORDER BY asset_tag")->fetchAll();
$dynamic_fields = get_ticket_dynamic_fields($pdo, $id);
$attachments    = get_ticket_attachments($pdo, $id);
$sla_policies   = $pdo->query("SELECT * FROM sla_policies WHERE is_active = 1 ORDER BY FIELD(priority, 'critical', 'high', 'medium', 'low'), policy_id ASC")->fetchAll();

$is_edit = true;

require __DIR__ . '/_form.view.php';
require_once __DIR__ . '/../../includes/footer.php';
