<?php
// modules/tickets/category_save_ajax.php
$module = 'tickets';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

$category_name = trim($_POST['category_name'] ?? '');
$has_bulb_hours = (int)($_POST['has_bulb_hours'] ?? 0);

if (!$category_name) {
    echo json_encode(['ok' => false, 'error' => 'Category name is required.']);
    exit;
}

try {
    // Corrected table name to asset_categories
    $stmt = $pdo->prepare("INSERT INTO asset_categories (category_name, has_bulb_hours) VALUES (?, ?)");
    $stmt->execute([$category_name, $has_bulb_hours]);
    $id = $pdo->lastInsertId();
    echo json_encode(['ok' => true, 'id' => $id]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
