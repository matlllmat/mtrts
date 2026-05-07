<?php
// modules/tickets/add.php
$module = 'tickets';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$is_staff = in_array($_SESSION['role_id'], [1, 2, 3, 4, 8]);

// Fetch logged-in user details for auto-fill
$stmt = $pdo->prepare("
    SELECT u.full_name, u.email, d.department_name 
    FROM users u 
    LEFT JOIN departments d ON u.department_id = d.department_id 
    WHERE u.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user_info = $stmt->fetch();

// Populate initial fields based on query string (e.g., from QR Code)
$t = [
    'ticket_id'        => 0,
    'requester_id'     => $_SESSION['user_id'],
    'full_name'        => $user_info['full_name'] ?? '',
    'email'            => $user_info['email'] ?? '',
    'department'       => $user_info['department_name'] ?? '',
    'asset_tag'        => $_GET['asset_tag'] ?? '',
    'title'            => '',
    'description'      => '',
    'impact'           => 'medium',
    'urgency'          => 'medium',
    'is_event_support' => 0,
    'category_id'      => $_GET['category_id'] ?? null,
    'location_id'      => $_GET['location_id'] ?? null,
    'asset_id'         => $_GET['asset_id'] ?? null,
    'preferred_window' => '',
    'model'            => '',
    'warranty_status'  => ''
];

// If passing asset_id, fetch asset to auto-fill category and location
if ($t['asset_id']) {
    $stmt = $pdo->prepare("SELECT category_id, location_id FROM assets WHERE asset_id = ?");
    $stmt->execute([$t['asset_id']]);
    if ($asset = $stmt->fetch()) {
        $t['category_id'] = $asset['category_id'];
        $t['location_id'] = $asset['location_id'];
    }
}

$categories = get_all_categories($pdo);
$locations  = get_all_locations($pdo);
$kb_articles = get_recommended_kb_articles($pdo, $t['category_id']);
// Only fetch assets if we don't have one pre-filled or maybe we just want to fetch a list
$assets     = $pdo->query("SELECT asset_id, asset_tag, manufacturer, model FROM assets WHERE status = 'active' ORDER BY asset_tag")->fetchAll();
$assignables= $is_staff ? $pdo->query("SELECT user_id, full_name, role_id FROM users WHERE role_id IN (2,3,4,8) AND is_active = 1 ORDER BY full_name")->fetchAll() : [];

$dynamic_fields = [];
$is_edit = false;

require __DIR__ . '/_form.view.php';
require_once __DIR__ . '/../../includes/footer.php';
