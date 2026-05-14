<?php
// modules/assets/save.php — Handles POST for both create and update.
// Validates, writes to DB, logs changes, redirects.

$module = 'assets';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'modules/assets/index.php');
    exit;
}

// CSRF check
$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    die('Invalid CSRF token.');
}

$asset_id = (int)($_POST['asset_id'] ?? 0);
$is_edit  = $asset_id > 0;
$user_id  = (int)$_SESSION['user_id'];

// AJAX mode is requested by the create-time docs widget so it can chain
// the asset save into per-file uploads. When set, this endpoint returns
// JSON instead of issuing redirects.
$is_ajax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

// ── Retire shortcut ───────────────────────────────────────────
if (!empty($_POST['retire']) && $is_edit) {
    if (has_open_tickets($pdo, $asset_id)) {
        $_SESSION['flash_error'] = 'Cannot retire — asset has open tickets.';
        header('Location: ' . BASE_URL . "modules/assets/edit.php?id={$asset_id}");
        exit;
    }
    $old = get_asset_by_id($pdo, $asset_id);
    $pdo->prepare("UPDATE assets SET status = 'retired' WHERE asset_id = ?")->execute([$asset_id]);
    log_asset_change($pdo, $asset_id, 'status', $old['status'], 'retired', $user_id);
    header('Location: ' . BASE_URL . "modules/assets/view.php?id={$asset_id}&flash=retired");
    exit;
}

// ── Sanitize input ────────────────────────────────────────────
$d = sanitize_asset_post($_POST, $user_id);

// ── Validate ──────────────────────────────────────────────────
$errors = [];

if (empty($d['asset_tag'])) {
    $errors['asset_tag'] = 'Asset tag is required.';
} elseif (!$is_edit && asset_tag_exists($pdo, $d['asset_tag'])) {
    $errors['asset_tag'] = 'This asset tag is already in use.';
}

if (empty($d['manufacturer'])) {
    $errors['manufacturer'] = 'Manufacturer is required.';
}

if (empty($d['model'])) {
    $errors['model'] = 'Model is required.';
}

if (!$d['category_id']) {
    $errors['category_id'] = 'Please select a category.';
}

if (!empty($d['serial_number']) && !empty($d['manufacturer'])) {
    if (serial_exists($pdo, $d['serial_number'], $d['manufacturer'], $asset_id)) {
        $errors['serial_number'] = 'This serial number already exists for this manufacturer.';
    }
}

if (empty($d['install_date'])) {
    $errors['install_date'] = 'Install date is required.';
} elseif ($d['install_date'] > date('Y-m-d')) {
    $errors['install_date'] = 'Install date cannot be in the future.';
}

if (!$d['location_id']) {
    $errors['location_id'] = 'Please select a location (room).';
}

// Owner — typed name must resolve to an active user (or be blank).
$owner_search_raw = trim($_POST['owner_search'] ?? '');
if ($owner_search_raw !== '' && empty($d['owner_id'])) {
    $errors['owner_id'] = 'Owner not found — pick a name from the suggestions or leave the field blank.';
}
if (!empty($d['owner_id'])) {
    $owner_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_id = ? AND is_active = 1");
    $owner_check->execute([$d['owner_id']]);
    if ((int)$owner_check->fetchColumn() === 0) {
        $errors['owner_id'] = 'Selected owner is not an active user.';
        $d['owner_id'] = null;
    }
}

// Cost Center — typed name must resolve to an existing department (or be blank).
$dept_search_raw = trim($_POST['dept_search'] ?? '');
if ($dept_search_raw !== '' && empty($d['department_id'])) {
    $errors['department_id'] = 'Cost center not found — pick a name from the suggestions or leave the field blank.';
}
if (!empty($d['department_id'])) {
    $dept_check = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE department_id = ?");
    $dept_check->execute([$d['department_id']]);
    if ((int)$dept_check->fetchColumn() === 0) {
        $errors['department_id'] = 'Selected cost center does not exist.';
        $d['department_id'] = null;
    }
}

// Parent Asset — typed label must resolve to an existing asset (or be blank).
$parent_search_raw = trim($_POST['parent_search'] ?? '');
if ($parent_search_raw !== '' && empty($d['parent_asset_id'])) {
    $errors['parent_asset_id'] = 'Parent asset not found — pick a tag from the suggestions or leave the field blank.';
}
if (!empty($d['parent_asset_id'])) {
    $parent_check = $pdo->prepare("SELECT COUNT(*) FROM assets WHERE asset_id = ?");
    $parent_check->execute([$d['parent_asset_id']]);
    if ((int)$parent_check->fetchColumn() === 0) {
        $errors['parent_asset_id'] = 'Selected parent asset does not exist.';
        $d['parent_asset_id'] = null;
    }
}

// Cannot retire an asset that has open tickets (also blocks the regular
// edit-form path; the "Retire" shortcut button above checks separately).
if ($is_edit && $d['status'] === 'retired' && has_open_tickets($pdo, $asset_id)) {
    $errors['status'] = 'Cannot retire — asset has open tickets. Resolve or close them first.';
}

// Warranty cross-field checks — apply whenever any warranty field is provided.
if (!empty($d['warranty_start']) || !empty($d['warranty_end'])) {

    // Both dates must be present together.
    if (!empty($d['warranty_end']) && empty($d['warranty_start'])) {
        $errors['warranty_start'] = 'Warranty start is required when an end date is set.';
    }
    if (!empty($d['warranty_start']) && empty($d['warranty_end'])) {
        $errors['warranty_end'] = 'Warranty end is required when a start date is set.';
    }

    if (!empty($d['warranty_start']) && !empty($d['warranty_end'])) {
        // End must be strictly after start.
        if ($d['warranty_end'] <= $d['warranty_start']) {
            $errors['warranty_end'] = 'Warranty end must be after warranty start.';
        }
        // Start must be on or after install date.
        if (!empty($d['install_date']) && $d['warranty_start'] < $d['install_date']) {
            $errors['warranty_start'] = 'Warranty start cannot be before install date.';
        }
        // Belt-and-suspenders: end must also be on or after install date.
        if (!empty($d['install_date']) && $d['warranty_end'] < $d['install_date']) {
            $errors['warranty_end'] = 'Warranty end cannot be before install date.';
        }
    }
}

// ── Re-flash on error ─────────────────────────────────────────
if ($errors) {
    $_SESSION['form_data']   = $_POST;
    $_SESSION['form_errors'] = $errors;
    $redirect = $is_edit
        ? BASE_URL . "modules/assets/edit.php?id={$asset_id}"
        : BASE_URL . 'modules/assets/add.php';
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'redirect' => $redirect]);
        exit;
    }
    header("Location: {$redirect}");
    exit;
}

// ── Write to DB ───────────────────────────────────────────────
try {
    if ($is_edit) {
        $old = get_asset_by_id($pdo, $asset_id);
        update_asset($pdo, $asset_id, $d);
        
        // LOG AUDIT: Asset Update
        log_audit($pdo, 'UPDATE', 'asset', $asset_id, $old, $d);

        log_asset_changes($pdo, $asset_id, $old, $d, $user_id);
        upsert_warranty($pdo, $asset_id, $d);
        header('Location: ' . BASE_URL . "modules/assets/view.php?id={$asset_id}&flash=updated");
    } else {
        $new_id = create_asset($pdo, $d);

        // LOG AUDIT: Asset Creation
        log_audit($pdo, 'CREATE', 'asset', $new_id, null, $d);

        log_asset_change($pdo, $new_id, 'created', null, $d['asset_tag'], $user_id);
        upsert_warranty($pdo, $new_id, $d);
        $view_url = BASE_URL . "modules/assets/view.php?id={$new_id}&flash=created";
        if ($is_ajax) {
            // Skip CSRF regen so staged file uploads keep working with the
            // same token. It'll regenerate naturally on the next page load.
            header('Content-Type: application/json');
            echo json_encode([
                'success'  => true,
                'asset_id' => $new_id,
                'redirect' => $view_url,
            ]);
            exit;
        }
        // Regenerate CSRF after successful create
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        header("Location: {$view_url}");
    }
    exit;

} catch (PDOException $e) {
    // Surface as a form error — do not expose raw DB message
    $_SESSION['form_data']   = $_POST;
    $_SESSION['form_errors'] = ['asset_tag' => 'A database error occurred. Please try again.'];
    $redirect = $is_edit
        ? BASE_URL . "modules/assets/edit.php?id={$asset_id}"
        : BASE_URL . 'modules/assets/add.php';
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'redirect' => $redirect]);
        exit;
    }
    header("Location: {$redirect}");
    exit;
}
