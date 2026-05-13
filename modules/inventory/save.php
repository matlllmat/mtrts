<?php
// modules/inventory/save.php — POST handler for add/edit part.

$module = 'inventory';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

$part_id = (int)($_POST['part_id'] ?? 0);
$data = [
    'part_number'      => trim($_POST['part_number'] ?? ''),
    'part_name'        => trim($_POST['part_name'] ?? ''),
    'description'      => $_POST['description'] ?? '',
    'manufacturer'     => $_POST['manufacturer'] ?? '',
    'category'         => $_POST['category'] ?? '',
    'compatible_with'  => $_POST['compatible_with'] ?? '',
    'reorder_level'    => $_POST['reorder_level'] ?? 5,
    'unit_cost'        => $_POST['unit_cost'] ?? '',
    'unit_price'       => $_POST['unit_price'] ?? '',
    'storage_location' => $_POST['storage_location'] ?? '',
    'initial_qty'      => $_POST['initial_qty'] ?? 0,
];

$errors = [];
if ($data['part_number'] === '') $errors['part_number'] = 'Part number is required.';
if ($data['part_name']   === '') $errors['part_name']   = 'Part name is required.';

if (!$errors) {
    $check = $pdo->prepare("SELECT part_id FROM parts_inventory WHERE part_number = ? AND part_id <> ?");
    $check->execute([$data['part_number'], $part_id]);
    if ($check->fetchColumn()) $errors['part_number'] = 'Part number already exists.';
}

if ($errors) {
    $_SESSION['inv_form_errors'] = $errors;
    $_SESSION['inv_form_old']    = $data;
    header('Location: ' . ($part_id > 0 ? "edit.php?id={$part_id}" : 'add.php'));
    exit;
}

try {
    $new_id = save_part($pdo, $data, $part_id > 0 ? $part_id : null, (int)$_SESSION['user_id']);
    header('Location: index.php?saved=1&id=' . $new_id);
    exit;
} catch (Throwable $e) {
    $_SESSION['inv_form_errors'] = ['part_number' => 'Save failed: ' . $e->getMessage()];
    $_SESSION['inv_form_old']    = $data;
    header('Location: ' . ($part_id > 0 ? "edit.php?id={$part_id}" : 'add.php'));
    exit;
}
