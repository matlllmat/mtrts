<?php
// modules/tickets/check_duplicate_ajax.php
$module = 'tickets';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

$asset_id = (int)($_GET['asset_id'] ?? 0);
$description = trim($_GET['description'] ?? '');

if (!$asset_id || strlen($description) < 10) {
    echo json_encode(['duplicate' => false]);
    exit;
}

$dup_id = check_duplicate_ticket($pdo, $asset_id, $description);

if ($dup_id) {
    $dup = get_ticket_by_id($pdo, $dup_id);
    echo json_encode([
        'duplicate' => true,
        'ticket_id' => $dup_id,
        'ticket_number' => $dup['ticket_number'] ?? '',
        'title' => $dup['title'] ?? '',
        'status' => $dup['status'] ?? '',
    ]);
} else {
    echo json_encode(['duplicate' => false]);
}
