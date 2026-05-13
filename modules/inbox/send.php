<?php
// modules/inbox/send.php
// POST-only AJAX endpoint: validate, insert inbox_messages row, fire notification.
// Requirements: 7.1–7.9, 6.1–6.3, 6.5

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../notifications/functions.php';
header('Content-Type: application/json');

// ── 1. Method check ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ── 2. Session / auth check ───────────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$sender_id = (int) $_SESSION['user_id'];

// ── 3. Validate recipient_id — must map to an active user ─────────────────────
$recipient_id = isset($_POST['recipient_id']) ? (int) $_POST['recipient_id'] : 0;

if ($recipient_id <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid recipient']);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? AND is_active = 1");
$stmt->execute([$recipient_id]);
if (!$stmt->fetch()) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid recipient']);
    exit;
}

// ── 4. Validate body — must be non-empty after trim ───────────────────────────
$body_raw = trim($_POST['body'] ?? '');

if ($body_raw === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Message body is required']);
    exit;
}

// ── 5. Validate subject — default to "(no subject)" if blank; max 255 chars ───
$subject_raw = trim($_POST['subject'] ?? '');

if ($subject_raw === '') {
    $subject_raw = '(no subject)';
}

if (mb_strlen($subject_raw) > 255) {
    http_response_code(422);
    echo json_encode(['error' => 'Subject too long']);
    exit;
}

// ── 6. Validate body length — max 10,000 chars ────────────────────────────────
if (mb_strlen($body_raw) > 10000) {
    http_response_code(422);
    echo json_encode(['error' => 'Message body too long']);
    exit;
}

// ── 7. Validate wo_id if provided and non-null ────────────────────────────────
$wo_id = null;
if (isset($_POST['wo_id']) && $_POST['wo_id'] !== '' && $_POST['wo_id'] !== null) {
    $wo_id_input = (int) $_POST['wo_id'];
    if ($wo_id_input > 0) {
        $stmt = $pdo->prepare("SELECT wo_id FROM work_orders WHERE wo_id = ?");
        $stmt->execute([$wo_id_input]);
        if (!$stmt->fetch()) {
            http_response_code(422);
            echo json_encode(['error' => 'Invalid wo_id']);
            exit;
        }
        $wo_id = $wo_id_input;
    }
}

// ── 8. Validate ticket_id if provided and non-null ───────────────────────────
$ticket_id = null;
if (isset($_POST['ticket_id']) && $_POST['ticket_id'] !== '' && $_POST['ticket_id'] !== null) {
    $ticket_id_input = (int) $_POST['ticket_id'];
    if ($ticket_id_input > 0) {
        $stmt = $pdo->prepare("SELECT ticket_id FROM tickets WHERE ticket_id = ?");
        $stmt->execute([$ticket_id_input]);
        if (!$stmt->fetch()) {
            http_response_code(422);
            echo json_encode(['error' => 'Invalid ticket_id']);
            exit;
        }
        $ticket_id = $ticket_id_input;
    }
}

// ── 9. Sanitise subject and body before insert ────────────────────────────────
$subject = htmlspecialchars($subject_raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$body    = htmlspecialchars($body_raw,    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// ── 10. Insert the message ────────────────────────────────────────────────────
$message_id = insert_inbox_message($pdo, $sender_id, $recipient_id, $subject, $body, $wo_id, $ticket_id);

if ($message_id === 0) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send message']);
    exit;
}

// ── 11. Fire notification to recipient ───────────────────────────────────────
// Fetch sender's full name for the notification title
$sender_stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
$sender_stmt->execute([$sender_id]);
$sender_name = $sender_stmt->fetchColumn() ?: 'Unknown';

$title   = "New message from {$sender_name}";
$preview = mb_strlen($body_raw) > 100
    ? mb_substr($body_raw, 0, 100) . '…'
    : $body_raw;
$link    = BASE_URL . 'modules/inbox/index.php';

notify_user($pdo, $recipient_id, $title, $preview, $link, "tech_msg_{$message_id}");

// ── 12. Success response ──────────────────────────────────────────────────────
http_response_code(200);
echo json_encode(['success' => true, 'message_id' => $message_id]);
