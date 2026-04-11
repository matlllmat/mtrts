<?php
// modules/workorders/assign.php — AJAX POST: quick reassign a work order.
// Returns JSON {success: bool, message?: string}

$module = 'workorders';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$wo_id  = (int)($_POST['wo_id'] ?? 0);
$to     = (int)($_POST['assigned_to'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$by     = $_SESSION['user_id'];

if ($wo_id < 1 || $to < 1) {
    echo json_encode(['success' => false, 'message' => 'Work order and technician are required.']);
    exit;
}

// Verify WO exists
$wo = get_wo_by_id($pdo, $wo_id);
if (!$wo) {
    echo json_encode(['success' => false, 'message' => 'Work order not found.']);
    exit;
}

reassign_wo($pdo, $wo_id, $to, $by, $reason ?: 'Quick reassignment from detail page');

// Notify new technician
require_once __DIR__ . '/../notifications/functions.php';
notify_user(
    $pdo,
    $to,
    'Work Order Reassigned: ' . $wo['wo_number'],
    'You have been reassigned to work order ' . $wo['wo_number'] . '.',
    BASE_URL . 'modules/workorders/view.php?id=' . $wo_id
);

echo json_encode(['success' => true]);
