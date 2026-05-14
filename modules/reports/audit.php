<?php
// modules/reports/audit.php
$module = 'reports';
$page_title = 'E-Discovery & Audit Logs';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/scope.php';

// E-Discovery Search Logic
$f = [
    'user_id'     => $_GET['user_id']     ?? '',
    'object_type' => $_GET['object_type'] ?? '',
    'date_from'   => $_GET['date_from']   ?? date('Y-m-d', strtotime('-30 days')),
    'date_to'     => $_GET['date_to']     ?? date('Y-m-d'),
    'q'           => $_GET['q']           ?? '',
];

$page = (int)($_GET['page'] ?? 1);
$per  = 50;

// RLS scope — applies the same auto-scoping as the dashboard so non-admin viewers
// only see audit rows they're permitted to inspect.
$viewer_id   = (int)$_SESSION['user_id'];
$viewer_role = (int)$_SESSION['role_id'];
$scope = resolve_report_scope($pdo, $viewer_id, $viewer_role, [
    'department_id' => $_GET['department_id'] ?? null,
    'building'      => $_GET['building']      ?? null,
]);

$logs = get_audit_logs($pdo, $f, $page, $per, $scope, $viewer_id);
$total_logs = count_audit_logs($pdo, $f, $scope, $viewer_id);
$total_pages = ceil($total_logs / $per);

// Lookups for filters
$users = $pdo->query("SELECT user_id, full_name FROM users ORDER BY full_name")->fetchAll();
$object_types = $pdo->query("SELECT DISTINCT object_type FROM audit_log ORDER BY object_type")->fetchAll(PDO::FETCH_COLUMN);

// Surface the active scope to the view so we can render a chip
$active_scope = $scope;

require __DIR__ . '/audit.view.php';
require_once __DIR__ . '/../../includes/footer.php';
