<?php
$module = 'reports';
$page_title = 'SLA & Performance Analytics';
require_once __DIR__ . '/../../config/guard.php';

// The frontend will load data via AJAX from api_stats.php and api_audit.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/scope.php';

$sla_policies = $pdo->query("SELECT * FROM sla_policies WHERE is_active = 1 ORDER BY FIELD(priority, 'critical', 'high', 'medium', 'low'), policy_id ASC")->fetchAll();

// Resolve RLS scope from the user's role + any optional filter params they passed.
// Threaded into the dashboard via JS (fetchStats sends location_id/department_id/building).
$viewer_id  = (int)$_SESSION['user_id'];
$viewer_role = (int)$_SESSION['role_id'];
$initial_scope = resolve_report_scope($pdo, $viewer_id, $viewer_role, [
    'location_id'   => $_GET['location_id']   ?? null,
    'department_id' => $_GET['department_id'] ?? null,
    'building'      => $_GET['building']      ?? null,
]);

// Filter UI lookups
$departments = $pdo->query("SELECT department_id, department_name FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);
$buildings   = $pdo->query("SELECT DISTINCT building FROM locations WHERE building <> '' ORDER BY building")->fetchAll(PDO::FETCH_COLUMN);

// For the UI: hide scope filters for hard-scoped roles (3/4)
$show_scope_filters = in_array($viewer_role, [1, 2, 8], true);

require __DIR__ . '/index.view.php';
require_once __DIR__ . '/../../includes/footer.php';
