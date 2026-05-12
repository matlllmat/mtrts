<?php
// modules/inventory/edit.php — Edit existing part.

$module = 'inventory';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$id = (int)($_GET['id'] ?? 0);
$part = $id > 0 ? get_part($pdo, $id) : null;
if (!$part) { header('Location: index.php'); exit; }

$is_edit  = true;
$errors   = $_SESSION['inv_form_errors'] ?? [];
$old      = $_SESSION['inv_form_old'] ?? [];
unset($_SESSION['inv_form_errors'], $_SESSION['inv_form_old']);

$history = get_part_audit_history($pdo, $id, 25);

require __DIR__ . '/_form.view.php';
require_once __DIR__ . '/../../includes/footer.php';
