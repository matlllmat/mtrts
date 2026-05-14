<?php
// modules/users/cost_center_save.php — Handles POST for cost center create / update / delete.

$module = 'users';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'modules/users/cost_centers.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    die('Invalid CSRF token.');
}

$action  = $_POST['action']  ?? '';
$dept_id = (int)($_POST['department_id'] ?? 0);
$redirect = BASE_URL . 'modules/users/cost_centers.php';

// ── Delete ────────────────────────────────────────────────────
if ($action === 'delete') {
    if (!$dept_id) {
        $_SESSION['dept_flash_error'] = 'Missing cost center ID.';
        header("Location: {$redirect}");
        exit;
    }
    if (count_department_usage($pdo, $dept_id) > 0) {
        $_SESSION['dept_flash_error'] = 'Cannot delete — this cost center is assigned to users or assets.';
        header("Location: {$redirect}");
        exit;
    }
    try {
        delete_department($pdo, $dept_id);
        $_SESSION['dept_flash_ok'] = 'Cost center deleted.';
    } catch (PDOException) {
        $_SESSION['dept_flash_error'] = 'Database error while deleting. Please try again.';
    }
    header("Location: {$redirect}");
    exit;
}

// ── Create / Update ───────────────────────────────────────────
$name = trim($_POST['department_name'] ?? '');
$errors = [];

if ($name === '') {
    $errors['department_name'] = 'Name is required.';
} elseif (strlen($name) > 100) {
    $errors['department_name'] = 'Name must be 100 characters or fewer.';
}

$is_update = ($action === 'update');

if (!$errors && department_name_exists($pdo, $name, $is_update ? $dept_id : 0)) {
    $errors['department_name'] = 'A cost center with this name already exists.';
}

if ($errors) {
    $_SESSION['dept_form_data']   = $_POST;
    $_SESSION['dept_form_errors'] = $errors;
    $back = $is_update ? "{$redirect}?edit={$dept_id}" : $redirect;
    header("Location: {$back}");
    exit;
}

try {
    if ($is_update) {
        if (!$dept_id || !get_department_by_id($pdo, $dept_id)) {
            $_SESSION['dept_flash_error'] = 'Cost center not found.';
            header("Location: {$redirect}");
            exit;
        }
        update_department($pdo, $dept_id, $name);
        $_SESSION['dept_flash_ok'] = 'Cost center updated.';
    } else {
        create_department($pdo, $name);
        $_SESSION['dept_flash_ok'] = 'Cost center added.';
    }
} catch (PDOException) {
    $_SESSION['dept_form_data']   = $_POST;
    $_SESSION['dept_form_errors'] = ['department_name' => 'Database error. Please try again.'];
    $back = $is_update ? "{$redirect}?edit={$dept_id}" : $redirect;
    header("Location: {$back}");
    exit;
}

header("Location: {$redirect}");
exit;
