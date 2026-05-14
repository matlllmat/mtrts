<?php
// modules/workorders/suggest.php
// AJAX GET: returns top-3 ranked technician candidates for a given ticket as JSON.
// Used by the WO create/edit form's "Suggest" panel.

$module = 'workorders';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

$ticket_id     = (int)($_GET['ticket_id'] ?? 0);
$start         = trim($_GET['start'] ?? '');
$end           = trim($_GET['end'] ?? '');
$exclude_wo_id = (int)($_GET['wo_id'] ?? 0);

if ($ticket_id < 1) {
    echo json_encode(['candidates' => [], 'message' => 'ticket_id required']);
    exit;
}

$top = get_qualified_technicians(
    $pdo,
    $ticket_id,
    $start !== '' ? $start : null,
    $end   !== '' ? $end   : null,
    $exclude_wo_id,
    3
);

echo json_encode(['candidates' => $top], JSON_UNESCAPED_UNICODE);
