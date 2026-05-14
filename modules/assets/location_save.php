<?php
// modules/assets/location_save.php — Handles POST for create / update / delete.

$module = 'assets';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'modules/assets/locations.php');
    exit;
}

// CSRF
$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    die('Invalid CSRF token.');
}

$action  = $_POST['action']  ?? '';
$loc_id  = (int) ($_POST['location_id'] ?? 0);

$redirect = BASE_URL . 'modules/assets/locations.php';

// ── Delete ────────────────────────────────────────────────────
if ($action === 'delete') {
    if (!$loc_id) {
        $_SESSION['loc_flash_error'] = 'Missing location ID.';
        header("Location: {$redirect}");
        exit;
    }
    if (count_assets_at_location($pdo, $loc_id) > 0) {
        $_SESSION['loc_flash_error'] = 'Cannot delete — this location is assigned to one or more assets.';
        header("Location: {$redirect}");
        exit;
    }
    try {
        delete_location($pdo, $loc_id);
        $_SESSION['loc_flash_ok'] = 'Location deleted.';
    } catch (PDOException) {
        $_SESSION['loc_flash_error'] = 'Database error while deleting. Please try again.';
    }
    header("Location: {$redirect}");
    exit;
}

// ── Create / Update ───────────────────────────────────────────
$building = trim($_POST['building'] ?? '');
$floor    = trim($_POST['floor']    ?? '');
$room     = trim($_POST['room']     ?? '');

$errors = [];
if ($building === '') $errors['building'] = 'Building is required.';
if ($floor === '')    $errors['floor']    = 'Floor is required.';
if ($room === '')     $errors['room']     = 'Room is required.';

$is_update = ($action === 'update');

if (!$errors && location_exists($pdo, $building, $floor, $room, $is_update ? $loc_id : 0)) {
    $errors['room'] = 'A location with this building / floor / room already exists.';
}

if ($errors) {
    $_SESSION['loc_form_data']   = $_POST;
    $_SESSION['loc_form_errors'] = $errors;
    $back = $is_update ? "{$redirect}?edit={$loc_id}" : $redirect;
    header("Location: {$back}");
    exit;
}

try {
    if ($is_update) {
        if (!$loc_id || !get_location_by_id($pdo, $loc_id)) {
            $_SESSION['loc_flash_error'] = 'Location not found.';
            header("Location: {$redirect}");
            exit;
        }
        update_location($pdo, $loc_id, $building, $floor, $room);
        $_SESSION['loc_flash_ok'] = 'Location updated.';
    } else {
        create_location($pdo, $building, $floor, $room);
        $_SESSION['loc_flash_ok'] = 'Location added.';
    }
} catch (PDOException) {
    $_SESSION['loc_form_data']   = $_POST;
    $_SESSION['loc_form_errors'] = ['room' => 'Database error. Please try again.'];
    $back = $is_update ? "{$redirect}?edit={$loc_id}" : $redirect;
    header("Location: {$back}");
    exit;
}

header("Location: {$redirect}");
exit;
