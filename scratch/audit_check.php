<?php
$pdo = new PDO('mysql:host=localhost;dbname=mtrts_sql', 'root', '');
$stats = [];

// 1. Total Tickets (excluding cancelled for success metrics, but keeping intake)
$stats['total_intake'] = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
$stats['total_active'] = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status != 'cancelled'")->fetchColumn();

// 2. SLA Breakdowns
$stats['resolved_with_sla'] = $pdo->query("
    SELECT COUNT(*) FROM tickets t 
    JOIN ticket_sla ts ON t.ticket_id = ts.ticket_id 
    WHERE t.status IN ('resolved', 'closed') AND ts.sla_id IS NOT NULL
")->fetchColumn();

$stats['resolved_met_sla'] = $pdo->query("
    SELECT COUNT(*) FROM tickets t 
    JOIN ticket_sla ts ON t.ticket_id = ts.ticket_id 
    WHERE t.status IN ('resolved', 'closed') AND ts.is_resolution_breached = 0 AND ts.sla_id IS NOT NULL
")->fetchColumn();

$stats['open_breached'] = $pdo->query("
    SELECT COUNT(*) FROM tickets t 
    JOIN ticket_sla ts ON t.ticket_id = ts.ticket_id 
    WHERE t.status NOT IN ('resolved', 'closed', 'cancelled') AND ts.is_resolution_breached = 1
")->fetchColumn();

$stats['no_deadline'] = $pdo->query("
    SELECT COUNT(*) FROM tickets t 
    LEFT JOIN ticket_sla ts ON t.ticket_id = ts.ticket_id 
    WHERE ts.sla_id IS NULL AND t.status != 'cancelled'
")->fetchColumn();

// 3. Technician Check (Tech Reyes)
$stats['tech_reyes'] = $pdo->query("
    SELECT 
        COUNT(wo_id) as total,
        SUM(status IN ('resolved', 'closed')) as done
    FROM work_orders 
    WHERE assigned_to = (SELECT user_id FROM users WHERE full_name = 'Tech Reyes')
")->fetch(PDO::FETCH_ASSOC);

// 4. Audit Log Integrity
try {
    // Just get the last 5 rows without guessing the ID column name
    $stats['latest_audit'] = $pdo->query("SELECT * FROM audit_log LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $stats['audit_columns'] = array_keys($stats['latest_audit'][0] ?? []);
} catch (Exception $e) {
    $stats['audit_error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($stats, JSON_PRETTY_PRINT);
