<?php
// modules/reports/sla_policies.php — SLA Policy Editor
$module = 'reports';
$page_title = 'SLA Policy Editor';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/sla.php';
require_once __DIR__ . '/../../modules/tickets/functions.php';

// Only admins, IT managers, super admins
if (!in_array($_SESSION['role_id'], [1, 2, 8])) {
    require_once __DIR__ . '/../denied.php';
    exit;
}

$policies    = get_all_sla_policies($pdo);
$categories  = get_all_categories($pdo);
$locations   = get_all_locations($pdo);

require __DIR__ . '/sla_policies.view.php';
require_once __DIR__ . '/../../includes/footer.php';
