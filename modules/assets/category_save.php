<?php
// modules/assets/category_save.php — Handles POST for create / update / delete.

$module = 'assets';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'modules/assets/categories.php');
    exit;
}

// CSRF
$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    die('Invalid CSRF token.');
}

$action = $_POST['action']      ?? '';
$cat_id = (int) ($_POST['category_id'] ?? 0);

$redirect = BASE_URL . 'modules/assets/categories.php';

// ── Delete ────────────────────────────────────────────────────
if ($action === 'delete') {
    if (!$cat_id) {
        $_SESSION['cat_flash_error'] = 'Missing category ID.';
        header("Location: {$redirect}");
        exit;
    }
    if (count_assets_in_category($pdo, $cat_id) > 0) {
        $_SESSION['cat_flash_error'] = 'Cannot delete — this category is assigned to one or more assets.';
        header("Location: {$redirect}");
        exit;
    }
    try {
        delete_category($pdo, $cat_id);
        $_SESSION['cat_flash_ok'] = 'Category deleted.';
    } catch (PDOException) {
        $_SESSION['cat_flash_error'] = 'Database error while deleting. Please try again.';
    }
    header("Location: {$redirect}");
    exit;
}

// ── Create / Update ───────────────────────────────────────────
$name        = trim($_POST['category_name'] ?? '');
$description = trim($_POST['description']   ?? '');
$has_bulb    = !empty($_POST['has_bulb_hours']);

$errors = [];
if ($name === '') $errors['category_name'] = 'Name is required.';

$is_update = ($action === 'update');

if (!$errors && category_name_exists($pdo, $name, $is_update ? $cat_id : 0)) {
    $errors['category_name'] = 'A category with this name already exists.';
}

if ($errors) {
    $_SESSION['cat_form_data']   = $_POST;
    $_SESSION['cat_form_errors'] = $errors;
    $back = $is_update ? "{$redirect}?edit={$cat_id}" : $redirect;
    header("Location: {$back}");
    exit;
}

try {
    if ($is_update) {
        if (!$cat_id || !get_category_by_id($pdo, $cat_id)) {
            $_SESSION['cat_flash_error'] = 'Category not found.';
            header("Location: {$redirect}");
            exit;
        }
        update_category($pdo, $cat_id, $name, $has_bulb, $description ?: null);
        $_SESSION['cat_flash_ok'] = 'Category updated.';
    } else {
        create_category($pdo, $name, $has_bulb, $description ?: null);
        $_SESSION['cat_flash_ok'] = 'Category added.';
    }
} catch (PDOException) {
    $_SESSION['cat_form_data']   = $_POST;
    $_SESSION['cat_form_errors'] = ['category_name' => 'Database error. Please try again.'];
    $back = $is_update ? "{$redirect}?edit={$cat_id}" : $redirect;
    header("Location: {$back}");
    exit;
}

header("Location: {$redirect}");
exit;
