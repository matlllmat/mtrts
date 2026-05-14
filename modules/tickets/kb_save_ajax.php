<?php
// modules/tickets/kb_save_ajax.php
$module = 'tickets';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$category_id = (int)($_POST['category_id'] ?? 0);
if ($category_id === 999) $category_id = 0; // Treat virtual 'Others' as null

if (!$title || !$content) {
    echo json_encode(['ok' => false, 'error' => 'Title and content are required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO kb_articles (title, content, category_id, is_published, created_by) VALUES (?, ?, ?, 1, ?)");
    $stmt->execute([$title, $content, $category_id ?: null, $_SESSION['user_id']]);
    $id = $pdo->lastInsertId();
    echo json_encode(['ok' => true, 'id' => $id]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
