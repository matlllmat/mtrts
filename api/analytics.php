<?php
// api/analytics.php
// REST BI Connector for external reporting tools (PowerBI, Tableau)

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modules/reports/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow external BI tools

// Security: Check for API Key or Session
// For simplicity, we'll check session, but in production, use a Bearer token
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2, 8])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized. BI access requires Administrator or Manager privileges.']);
    exit;
}

$start = $_GET['start'] ?? date('Y-m-d', strtotime('-90 days'));
$end = $_GET['end'] ?? date('Y-m-d');

$data = [
    'metadata' => [
        'generated_at' => date('c'),
        'range' => [$start, $end],
        'version' => '1.0'
    ],
    'sla_summary' => get_sla_compliance_stats($pdo, $start, $end),
    'operational_metrics' => get_operational_stats($pdo, $start, $end),
    'mttr_data' => get_mttr_stats($pdo, $start, $end),
    'asset_hotspots' => get_asset_hotspots($pdo, 20),
    'location_heatmap' => get_location_heatmap($pdo),
    'technician_performance' => get_technician_scorecards($pdo)
];

echo json_encode($data, JSON_PRETTY_PRINT);
