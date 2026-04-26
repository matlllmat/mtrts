<?php
// modules/workorders/check_conflicts_ajax.php
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

$assigned_to = (int)($_GET['assigned_to'] ?? 0);
$start       = $_GET['start'] ?? '';
$end         = $_GET['end'] ?? '';
$wo_id       = (int)($_GET['wo_id'] ?? 0);

if (!$assigned_to || !$start || !$end) {
    echo json_encode(['conflict' => false]);
    exit;
}

$conflict = check_wo_conflict($pdo, $assigned_to, $start, $end, $wo_id);

if ($conflict) {
    $c_start = (new DateTime($conflict['scheduled_start']))->format('M j, g:ia');
    $c_end   = (new DateTime($conflict['scheduled_end']))->format('M j, g:ia');
    echo json_encode([
        'conflict' => true,
        'message'  => "Conflict: Technician is already booked for {$conflict['wo_number']} from $c_start to $c_end."
    ]);
} else {
    echo json_encode(['conflict' => false]);
}
