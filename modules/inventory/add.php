<?php
// modules/inventory/add.php — New part form.

$module = 'inventory';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$is_edit  = false;
$part     = [];
$errors   = $_SESSION['inv_form_errors'] ?? [];
$old      = $_SESSION['inv_form_old'] ?? [];
unset($_SESSION['inv_form_errors'], $_SESSION['inv_form_old']);

require __DIR__ . '/_form.view.php';
require_once __DIR__ . '/../../includes/footer.php';
