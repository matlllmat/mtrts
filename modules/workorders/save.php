<?php
// modules/workorders/save.php — POST handler for create/edit work orders.
// No HTML output. Validates, saves, redirects.

$module = 'workorders';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$wo_id   = (int)($_POST['wo_id'] ?? 0);
$is_edit = $wo_id > 0;
$data    = sanitize_wo_post($_POST, $user_id);
$errors  = [];

// ── Validation ────────────────────────────────────────────────

if (empty($data['wo_type'])) {
    $errors['wo_type'] = 'Work order type is required.';
}

// Scheduled end must be after start
if ($data['scheduled_start'] && $data['scheduled_end']) {
    if (strtotime($data['scheduled_end']) <= strtotime($data['scheduled_start'])) {
        $errors['scheduled_end'] = 'Scheduled end must be after the start.';
    }
}

// On hold requires a reason
if ($is_edit && $data['status'] === 'on_hold' && empty($data['on_hold_reason'])) {
    $errors['on_hold_reason'] = 'Please select a reason for putting this on hold.';
}

// Double booking prevention
if ($data['assigned_to'] && $data['scheduled_start'] && $data['scheduled_end']) {
    $conflict = check_wo_conflict($pdo, $data['assigned_to'], $data['scheduled_start'], $data['scheduled_end'], $wo_id);
    if ($conflict) {
        $c_start = (new DateTime($conflict['scheduled_start']))->format('M j, g:ia');
        $c_end = (new DateTime($conflict['scheduled_end']))->format('M j, g:ia');
        $errors['assigned_to'] = "Conflict: Technician is already booked for {$conflict['wo_number']} from $c_start to $c_end.";
    }
}

if ($is_edit) {
    $old_wo = get_wo_by_id($pdo, $wo_id);
    
    // Checklist enforcement: If resolving, all mandatory items must be done
    if ($data['status'] === 'resolved') {
        $checklist = get_wo_checklist($pdo, $wo_id, $old_wo['category_id'] ?? null);
        $incomplete = 0;
        foreach ($checklist as $item) {
            if ($item['is_mandatory'] && !$item['is_done']) {
                $incomplete++;
            }
        }
        if ($incomplete > 0) {
            $errors['status'] = "Cannot resolve: $incomplete mandatory checklist item(s) are not complete.";
        }
    }
}

// ── If errors, redirect back ──────────────────────────────────

if ($errors) {
    $_SESSION['wo_errors'] = $errors;
    $_SESSION['wo_old']    = $_POST;
    $back = $is_edit ? 'edit.php?id=' . $wo_id : 'add.php';
    header('Location: ' . $back);
    exit;
}

// ── Save ──────────────────────────────────────────────────────

if ($is_edit) {
    // Track assignment change for notification
    $old_wo = get_wo_by_id($pdo, $wo_id);
    $old_assignee = $old_wo['assigned_to'] ?? null;

    update_work_order($pdo, $wo_id, $data);

    // If assignment changed, log it and notify
    if ($data['assigned_to'] && $data['assigned_to'] != $old_assignee) {
        $pdo->prepare("
            INSERT INTO wo_assignment_log (wo_id, assigned_from, assigned_to, assigned_by, reason)
            VALUES (?,?,?,?,?)
        ")->execute([
            $wo_id,
            $old_assignee ?: null,
            $data['assigned_to'],
            $user_id,
            'Updated via edit form',
        ]);

        // Notify new technician
        $wo_num = $old_wo['wo_number'] ?? '';
        require_once __DIR__ . '/../notifications/functions.php';
        notify_user(
            $pdo,
            (int)$data['assigned_to'],
            'Work Order Assigned: ' . $wo_num,
            'You have been assigned to work order ' . $wo_num . '.',
            BASE_URL . 'modules/workorders/view.php?id=' . $wo_id
        );
    }
} else {
    $wo_id = create_work_order($pdo, $data);

    // Notify assigned technician
    if ($data['assigned_to']) {
        $wo_num = generate_wo_number($pdo); // Already incremented, get current
        $wo_row = get_wo_by_id($pdo, $wo_id);
        require_once __DIR__ . '/../notifications/functions.php';
        notify_user(
            $pdo,
            (int)$data['assigned_to'],
            'New Work Order: ' . ($wo_row['wo_number'] ?? ''),
            'You have been assigned a new work order.',
            BASE_URL . 'modules/workorders/view.php?id=' . $wo_id
        );
    }
}

header('Location: ' . BASE_URL . 'modules/workorders/view.php?id=' . $wo_id);
exit;
