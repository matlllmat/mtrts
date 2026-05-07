<?php
// modules/reports/export.php
// Supports CSV and Excel (XML Spreadsheet) export
$module = 'reports';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

if (!in_array($_SESSION['role_id'], [1, 2, 3, 8])) {
    http_response_code(403);
    die('Unauthorized');
}

$start  = $_GET['start']  ?? date('Y-m-d', strtotime('-30 days'));
$end    = $_GET['end']    ?? date('Y-m-d');
$format = $_GET['format'] ?? 'csv';   // csv | excel

$stmt = $pdo->prepare("
    SELECT 
        t.ticket_number, t.title, t.status, t.priority, t.request_type,
        c.category_name, a.asset_tag, l.building, l.room,
        r.full_name AS requester, u.full_name AS assignee,
        t.created_at, t.resolved_at, t.closed_at,
        ts.response_due, ts.responded_at, ts.diagnosis_due, ts.diagnosed_at,
        ts.resolution_due, ts.resolved_at AS sla_resolved_at,
        ts.is_response_breached, ts.is_diagnosis_breached, ts.is_resolution_breached,
        ts.total_paused_minutes, ts.escalation_level,
        sp.policy_name AS sla_policy
    FROM tickets t
    LEFT JOIN ticket_sla ts ON t.ticket_id = ts.ticket_id
    LEFT JOIN sla_policies sp ON ts.policy_id = sp.policy_id
    LEFT JOIN assets a ON t.asset_id = a.asset_id
    LEFT JOIN asset_categories c ON t.category_id = c.category_id
    LEFT JOIN locations l ON t.location_id = l.location_id
    LEFT JOIN users r ON t.requester_id = r.user_id
    LEFT JOIN users u ON t.assigned_to = u.user_id
    WHERE t.created_at BETWEEN ? AND ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$start . ' 00:00:00', $end . ' 23:59:59']);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$columns = [
    'Ticket #', 'Title', 'Status', 'Priority', 'Request Type',
    'Category', 'Asset Tag', 'Building', 'Room',
    'Requester', 'Assignee',
    'Created At', 'Resolved At', 'Closed At',
    'SLA Policy', 'Response Due', 'Responded At', 'Diagnosis Due', 'Diagnosed At',
    'Resolution Due', 'SLA Resolved At',
    'Response Breached', 'Diagnosis Breached', 'Resolution Breached',
    'Total Paused (min)', 'Escalation Level'
];

if ($format === 'excel') {
    // ── Excel XML Spreadsheet (opens natively in Excel, no library needed) ──
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="mtrts_report_' . $start . '_to_' . $end . '.xls"');
    
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
           xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
    echo '<Styles>
        <Style ss:ID="header"><Font ss:Bold="1" ss:Size="11"/><Interior ss:Color="#1a5c2a" ss:Pattern="Solid"/><Font ss:Color="#FFFFFF" ss:Bold="1"/></Style>
        <Style ss:ID="breach"><Interior ss:Color="#FEE2E2" ss:Pattern="Solid"/></Style>
    </Styles>';
    echo '<Worksheet ss:Name="SLA Report"><Table>';
    
    // Header row
    echo '<Row>';
    foreach ($columns as $col) {
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">' . htmlspecialchars($col) . '</Data></Cell>';
    }
    echo '</Row>';
    
    // Data rows
    foreach ($rows as $row) {
        $is_breached = $row['is_response_breached'] || $row['is_resolution_breached'];
        $style = $is_breached ? ' ss:StyleID="breach"' : '';
        echo '<Row>';
        foreach (array_values($row) as $val) {
            echo '<Cell' . $style . '><Data ss:Type="String">' . htmlspecialchars($val ?? '') . '</Data></Cell>';
        }
        echo '</Row>';
    }
    
    echo '</Table></Worksheet></Workbook>';
    
} else {
    // ── CSV Export ──
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="mtrts_report_' . $start . '_to_' . $end . '.csv"');
    
    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, $columns);
    
    foreach ($rows as $row) {
        $row['is_response_breached']   = $row['is_response_breached'] ? 'Yes' : 'No';
        $row['is_diagnosis_breached']  = $row['is_diagnosis_breached'] ? 'Yes' : 'No';
        $row['is_resolution_breached'] = $row['is_resolution_breached'] ? 'Yes' : 'No';
        fputcsv($output, array_values($row));
    }
    fclose($output);
}
