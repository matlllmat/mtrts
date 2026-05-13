<?php
// modules/inbox/mark_read.php
// POST-only AJAX endpoint — marks a single inbox_message as read.
//
// POST body: message_id=N
//
// HTTP responses:
//   405  {"error":"Method not allowed"}   — non-POST request
//   401  {"error":"Unauthorized"}          — no valid session
//   422  {"error":"Invalid message_id"}    — missing or non-positive integer
//   404  {"error":"Message not found"}     — no row with that ID in inbox_messages
//   403  {"error":"Forbidden"}             — message belongs to a different recipient
//   200  {"ok":true}                       — already read (no-op) or just marked read
//
// Requirements: 8.1, 8.2, 8.3, 8.4

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/functions.php';
header('Content-Type: application/json');

// 1. Method guard
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// 2. Session guard
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// 3. Validate message_id — must be a positive integer
$raw_id     = $_POST['message_id'] ?? '';
$message_id = filter_var($raw_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($message_id === false) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid message_id']);
    exit;
}

$message_id = (int) $message_id;

// 4. Fetch the message row directly (ownership check done here for correct HTTP codes)
$stmt = $pdo->prepare(
    "SELECT recipient_id, read_at FROM inbox_messages WHERE message_id = ?"
);
$stmt->execute([$message_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row === false) {
    http_response_code(404);
    echo json_encode(['error' => 'Message not found']);
    exit;
}

// 5. Ownership check — recipient must be the session user
if ((int) $row['recipient_id'] !== $user_id) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// 6. Already read — return 200 no-op
if ($row['read_at'] !== null) {
    echo json_encode(['ok' => true]);
    exit;
}

// 7. Mark as read via the shared helper
mark_inbox_message_read($pdo, $message_id, $user_id);

echo json_encode(['ok' => true]);
