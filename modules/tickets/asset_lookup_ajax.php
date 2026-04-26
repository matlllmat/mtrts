<?php
// modules/tickets/asset_lookup_ajax.php
// Returns asset details by asset_tag (used by QR scanner)
$module = 'tickets';
require_once __DIR__ . '/../../config/auth_only.php';

header('Content-Type: application/json');

$asset_tag = trim($_GET['tag'] ?? '');

if (!$asset_tag) {
    echo json_encode(['found' => false]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT a.asset_id, a.asset_tag, a.manufacturer, a.model, a.status,
           c.category_name, c.category_id,
           l.building, l.floor, l.room, l.location_id
    FROM assets a
    LEFT JOIN asset_categories c ON a.category_id = c.category_id
    LEFT JOIN locations l ON a.location_id = l.location_id
    WHERE a.asset_tag = ?
    LIMIT 1
");
$stmt->execute([$asset_tag]);
$asset = $stmt->fetch(PDO::FETCH_ASSOC);

if ($asset) {
    echo json_encode([
        'found' => true,
        'asset_id' => (int)$asset['asset_id'],
        'asset_tag' => $asset['asset_tag'],
        'manufacturer' => $asset['manufacturer'],
        'model' => $asset['model'],
        'status' => $asset['status'],
        'category_name' => $asset['category_name'],
        'category_id' => (int)$asset['category_id'],
        'location' => $asset['building'] . ' - ' . $asset['floor'] . ' - ' . $asset['room'],
        'location_id' => (int)$asset['location_id'],
    ]);
} else {
    echo json_encode(['found' => false]);
}
