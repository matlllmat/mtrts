<?php
// modules/reports/api_stats.php
$module = 'reports';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

// Only staff/managers/technicians can access analytics. Technicians (role 4) see
// their own scorecard via auto-scoping; faculty/students still get 403.
if (!in_array($_SESSION['role_id'], [1, 2, 3, 4, 8])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$start_date = $_GET['start'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end'] ?? date('Y-m-d');

// RLS scope (auto-applied for it_manager/it_staff/technician; admin/super_admin may drill via filters)
$viewer_id   = (int)$_SESSION['user_id'];
$viewer_role = (int)$_SESSION['role_id'];
$scope = resolve_report_scope($pdo, $viewer_id, $viewer_role, [
    'location_id'   => $_GET['location_id']   ?? null,
    'department_id' => $_GET['department_id'] ?? null,
    'building'      => $_GET['building']      ?? null,
]);

$type = $_GET['type'] ?? 'all';

if (isset($_GET['drilldown'])) {
    echo json_encode(get_drilldown_tickets($pdo, $_GET['drilldown'], $start_date, $end_date, $scope));
    exit;
}

$data = [];

switch ($type) {
    case 'sla':
        $data = get_sla_compliance_stats($pdo, $start_date, $end_date, $scope);
        break;
    case 'mttr':
        $data = get_mttr_stats($pdo, $start_date, $end_date, $scope);
        break;
    case 'hotspots':
        $data = get_asset_hotspots($pdo, (int)($_GET['limit'] ?? 10), $scope);
        break;
    case 'scorecards':
        $data = get_technician_scorecards($pdo, $scope);
        break;
    case 'all':
    default:
        $data = [
            'sla'          => get_sla_compliance_stats($pdo, $start_date, $end_date, $scope),
            'mttr'         => get_mttr_stats($pdo, $start_date, $end_date, $scope),
            'trends'       => get_resolution_trends($pdo, $start_date, $end_date, $scope),
            'hotspots'     => get_asset_hotspots($pdo, 5, $scope),
            'scorecards'   => get_technician_scorecards($pdo, $scope),
            'operational'  => get_operational_stats($pdo, $start_date, $end_date, $scope),
            'heatmap'      => get_location_heatmap($pdo, $scope),
            'warranty'     => get_warranty_exposure($pdo),
            'escalations'  => get_active_escalations($pdo, $scope),
            'aging'        => get_ticket_aging($pdo, $scope),
            'cost'         => get_cost_stats($pdo, $start_date, $end_date, $scope),
            'time_heatmap' => get_time_heatmap($pdo, $scope),
            'scope'        => $scope,
        ];
        break;
}

echo json_encode($data);
