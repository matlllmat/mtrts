<?php
// modules/reports/export.php
$module = 'reports';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

if (!in_array($_SESSION['role_id'], [1, 2, 3, 8])) {
    http_response_code(403);
    die('Unauthorized');
}

$start = $_GET['start'] ?? date('Y-m-d', strtotime('-30 days'));
$end = $_GET['end'] ?? date('Y-m-d');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="mtrts_sla_report_' . $start . '_to_' . $end . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Ticket ID', 'Status', 'Priority', 'Category', 'Asset Tag', 'Created At', 'Resolved At', 'Response SLA Breached', 'Resolution SLA Breached']);

$stmt = $pdo->prepare("
    SELECT 
        t.ticket_id, t.status, t.priority, c.category_name, a.asset_tag, 
        t.created_at, ts.resolved_at, 
        ts.is_response_breached, ts.is_resolution_breached
    FROM tickets t
    LEFT JOIN ticket_sla ts ON t.ticket_id = ts.ticket_id
    LEFT JOIN assets a ON t.asset_id = a.asset_id
    LEFT JOIN asset_categories c ON t.category_id = c.category_id
    WHERE t.created_at BETWEEN ? AND ?
");
$stmt->execute([$start . ' 00:00:00', $end . ' 23:59:59']);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['ticket_id'],
        $row['status'],
        ucfirst($row['priority']),
        $row['category_name'],
        $row['asset_tag'],
        $row['created_at'],
        $row['resolved_at'],
        $row['is_response_breached'] ? 'Yes' : 'No',
        $row['is_resolution_breached'] ? 'Yes' : 'No'
    ]);
}
fclose($output);
