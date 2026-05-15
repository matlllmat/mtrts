<?php
// modules/assets/bulk_update.php — Handles bulk field updates on multiple assets.
// POST-only. Returns JSON. Called by the bulk update modal in index.view.php.

$module = 'assets';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// CSRF check
$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$asset_ids   = array_values(array_filter(array_map('intval', (array)($_POST['asset_ids'] ?? []))));
$fields_json = $_POST['fields'] ?? '{}';
$user_id     = (int)$_SESSION['user_id'];

if (empty($asset_ids)) {
    echo json_encode(['success' => false, 'message' => 'No assets selected.']);
    exit;
}

$fields_raw = json_decode($fields_json, true);
if (!is_array($fields_raw) || empty($fields_raw)) {
    echo json_encode(['success' => false, 'message' => 'No fields to update.']);
    exit;
}

// Whitelist — only these columns may be bulk-updated
$col_map = [
    'status'        => 'status',
    'location_id'   => 'location_id',
    'owner_id'      => 'owner_id',
    'department_id' => 'department_id',
];

// Validate + sanitize all submitted fields
$updates = [];
foreach ($fields_raw as $field => $value) {
    if (!array_key_exists($field, $col_map)) continue;

    if ($field === 'status') {
        if (!in_array($value, ['active', 'spare', 'retired'], true)) continue;
        $updates[$field] = $value;
    } else {
        // Nullable FK columns — empty string means clear (NULL)
        $updates[$field] = ($value === '' || $value === null) ? null : (int)$value;
    }
}

if (empty($updates)) {
    echo json_encode(['success' => false, 'message' => 'No valid fields to update.']);
    exit;
}

$updated = 0;
$skipped = [];

foreach ($asset_ids as $asset_id) {
    $asset = get_asset_by_id($pdo, $asset_id);
    if (!$asset) continue;

    $apply = $updates;

    // Business rule: cannot retire an asset that has open tickets;
    // skip the status change but still apply any other requested fields.
    if (isset($apply['status']) && $apply['status'] === 'retired' && has_open_tickets($pdo, $asset_id)) {
        $skipped[] = $asset['asset_tag'];
        unset($apply['status']);
        if (empty($apply)) continue;
    }

    // Build UPDATE
    $set_parts = [];
    $set_vals  = [];
    foreach ($apply as $col => $val) {
        $set_parts[] = "`{$col_map[$col]}` = ?";
        $set_vals[]  = $val;
    }
    $set_vals[] = $asset_id;
    $pdo->prepare("UPDATE assets SET " . implode(', ', $set_parts) . " WHERE asset_id = ?")
        ->execute($set_vals);

    // Audit log per changed field
    foreach ($apply as $col => $val) {
        $old_val = isset($asset[$col_map[$col]]) ? (string)$asset[$col_map[$col]] : null;
        log_asset_change($pdo, $asset_id, $col, $old_val ?: null, $val !== null ? (string)$val : null, $user_id);
    }

    // Sync open ticket locations when location changed to a real value
    if (isset($apply['location_id']) && $apply['location_id'] !== null) {
        $old_loc = (int)($asset['location_id'] ?? 0);
        if ($old_loc !== (int)$apply['location_id']) {
            sync_open_ticket_locations($pdo, $asset_id, (int)$apply['location_id'], $user_id);
        }
    }

    $updated++;
}

echo json_encode([
    'success' => true,
    'updated' => $updated,
    'skipped' => $skipped,
]);
