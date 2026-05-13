<?php
// public/email_submit_handler.php — POST handler for the public email gateway.
// Creates a ticket with channel='email'. NO authentication required.

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../modules/tickets/functions.php';
require_once __DIR__ . '/../config/sla.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: email_submit.php');
    exit;
}

$from_name   = trim($_POST['from_name']   ?? '');
$from_email  = trim($_POST['from_email']  ?? '');
$subject     = trim($_POST['subject']     ?? '');
$body        = trim($_POST['body']        ?? '');
$category_id = ((int)($_POST['category_id'] ?? 0)) ?: null;

// ── Validation ──────────────────────────────────────────────
if ($from_name === '' || $from_email === '' || $subject === '' || $body === '') {
    header('Location: email_submit.php?err=' . urlencode('All fields except attachments and category are required.'));
    exit;
}
if (!filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
    header('Location: email_submit.php?err=' . urlencode('Please enter a valid email address.'));
    exit;
}

// ── Look up sender by email ─────────────────────────────────
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$from_email]);
$matched_user_id = $stmt->fetchColumn();

$is_external = !$matched_user_id;
$requester_id = $matched_user_id ? (int)$matched_user_id : null;

// ── Build ticket data ───────────────────────────────────────
$d = [
    'requester_id'        => $requester_id,
    'title'               => mb_substr($subject, 0, 255),
    'description'         => $body,
    'impact'              => 'medium',
    'urgency'             => 'medium',
    'is_event_support'    => 0,
    'category_id'         => $category_id,
    'location_id'         => null,
    'asset_id'            => null,
    'request_type'        => 'repair',
    'preferred_window'    => null,
    'channel'             => 'email',
    'external_email_from' => $is_external ? mb_substr($from_email, 0, 150) : null,
    'external_name_from'  => $is_external ? mb_substr($from_name,  0, 100) : null,
    'dynamic_fields'      => [],
];

// ── Duplicate detection (only for registered users — asset_id is unknown here) ──
$dup_id = null;
if ($requester_id) {
    $dup_id = check_duplicate_ticket($pdo, $d);
}

try {
    $ticket_id = create_ticket($pdo, $d);

    if ($dup_id) {
        $pdo->prepare("UPDATE tickets SET status='cancelled', duplicate_of_id=? WHERE ticket_id=?")
            ->execute([$dup_id, $ticket_id]);
    } else {
        init_ticket_sla($pdo, $ticket_id);
    }

    // Attachments: uploaded_by must reference a valid user; use the matched user or fall back to system admin (user_id=1)
    $uploader_id = $requester_id ?: 1;
    [$ok, $upload_err] = handle_ticket_uploads_validated($pdo, $ticket_id, $uploader_id);
    if (!$ok) {
        // Roll back the ticket so the user can retry with valid attachments
        $pdo->prepare("DELETE FROM ticket_attachments WHERE ticket_id = ?")->execute([$ticket_id]);
        $pdo->prepare("DELETE FROM ticket_sla        WHERE ticket_id = ?")->execute([$ticket_id]);
        $pdo->prepare("DELETE FROM tickets           WHERE ticket_id = ?")->execute([$ticket_id]);
        header('Location: email_submit.php?err=' . urlencode($upload_err));
        exit;
    }

    // Notify IT managers + IT staff so they see the inbox bell badge climb
    require_once __DIR__ . '/../modules/notifications/functions.php';
    $stmt_num = $pdo->prepare("SELECT ticket_number FROM tickets WHERE ticket_id = ?");
    $stmt_num->execute([$ticket_id]);
    $ticket_num = $stmt_num->fetchColumn();

    $notif_targets = $pdo->query("SELECT user_id FROM users WHERE role_id IN (1, 2, 3, 8) AND is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($notif_targets as $tid) {
        notify_user(
            $pdo,
            (int) $tid,
            '📧 New Email Ticket: ' . $ticket_num,
            'From: ' . $from_name . ' <' . $from_email . '> — ' . mb_substr($subject, 0, 120),
            BASE_URL . 'modules/tickets/view.php?id=' . $ticket_id
        );
    }

    header('Location: email_submit.php?ok=1&tn=' . urlencode($ticket_num));
    exit;

} catch (Throwable $e) {
    header('Location: email_submit.php?err=' . urlencode('Could not submit your email. Please try again.'));
    exit;
}
