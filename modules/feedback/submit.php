<?php
// modules/feedback/submit.php — Feedback submission controller
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../notifications/functions.php';

// ── Resolve wo_id ─────────────────────────────────────────────
$wo_id = (int)($_GET['wo_id'] ?? 0);
if (!$wo_id) {
    require_once __DIR__ . '/../denied.php';
    exit;
}

// ── Load Work Order ───────────────────────────────────────────
$wo = get_wo_for_feedback($pdo, $wo_id);
if ($wo === null || !in_array($wo['status'], ['resolved', 'closed'], true)) {
    require_once __DIR__ . '/../denied.php';
    exit;
}

// ── Requester-only access ─────────────────────────────────────
if ((int)$_SESSION['user_id'] !== (int)$wo['requester_id']) {
    require_once __DIR__ . '/../denied.php';
    exit;
}

// ── Initialise shared view variables ─────────────────────────
$error = null;

// ── POST: handle form submission ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)($_POST['rating'] ?? 0);

    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating between 1 and 5.';
        $existing_feedback = get_feedback_for_wo($pdo, $wo_id);
        $submitted = false;
        require __DIR__ . '/submit.view.php';
        exit;
    }

    // Save the feedback record
    $comment = trim($_POST['comment'] ?? '') ?: null;
    save_feedback($pdo, $wo_id, (int)$_SESSION['user_id'], $rating, $comment);

    // Send Feedback_Notification to the technician (if assigned)
    if ($wo['assigned_to'] !== null) {
        notify_user(
            $pdo,
            (int)$wo['assigned_to'],
            "A requester has rated your completed job",
            "{$wo['wo_number']} received a {$rating}-star rating. View the Ratings tab for details.",
            BASE_URL . "modules/technician/view.php?id={$wo_id}#ratings",
            "feedback_received_{$wo_id}"
        );
    }

    // PRG redirect
    header('Location: ' . BASE_URL . "modules/feedback/submit.php?wo_id={$wo_id}&submitted=1");
    exit;
}

// ── GET: render the feedback form ─────────────────────────────
$existing_feedback = get_feedback_for_wo($pdo, $wo_id);
$submitted = isset($_GET['submitted']);

require __DIR__ . '/submit.view.php';
