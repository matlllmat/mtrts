<?php
// modules/workorders/auto_assign.php
// AJAX POST: auto-assigns an existing work order to the best-scored technician.
// Returns JSON {success: bool, message: string, user_id?: int, full_name?: string}

$module = 'workorders';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$wo_id = (int)($_POST['wo_id'] ?? 0);
$by    = (int)$_SESSION['user_id'];

if ($wo_id < 1) {
    echo json_encode(['success' => false, 'message' => 'Work order id is required.']);
    exit;
}

$wo = get_wo_by_id($pdo, $wo_id);
if (!$wo) {
    echo json_encode(['success' => false, 'message' => 'Work order not found.']);
    exit;
}

$best = auto_assign_technician(
    $pdo,
    (int)$wo['ticket_id'],
    $wo['scheduled_start'] ?: null,
    $wo['scheduled_end']   ?: null,
    $wo_id
);

if (!$best) {
    echo json_encode([
        'success' => false,
        'message' => 'No qualified technician available (no skill/location match, or everyone has a schedule conflict).'
    ]);
    exit;
}

reassign_wo($pdo, $wo_id, $best, $by, 'Auto-assigned (skill+location match)');

// Notify the newly-assigned tech
require_once __DIR__ . '/../notifications/functions.php';
$view_link = BASE_URL . 'modules/workorders/view.php?id=' . $wo_id;
$stmt_role = $pdo->prepare("SELECT role_id, full_name FROM users WHERE user_id = ?");
$stmt_role->execute([$best]);
$row = $stmt_role->fetch(PDO::FETCH_ASSOC);
if (($row['role_id'] ?? 0) == 4) {
    $view_link = BASE_URL . 'modules/technician/view.php?id=' . $wo_id;
}

notify_user(
    $pdo,
    $best,
    'Work Order Auto-Assigned: ' . $wo['wo_number'],
    'You have been auto-assigned to work order ' . $wo['wo_number'] . ' based on skill and location.',
    $view_link
);

echo json_encode([
    'success'   => true,
    'message'   => 'Auto-assigned to ' . ($row['full_name'] ?? 'technician #' . $best),
    'user_id'   => $best,
    'full_name' => $row['full_name'] ?? null,
]);
