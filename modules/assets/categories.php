<?php
// modules/assets/categories.php — Manage asset categories.
// Logic only: guard, data fetch, then hands off to the view.

$module = 'assets';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$categories = get_categories_with_counts($pdo);

$form_data   = $_SESSION['cat_form_data']   ?? [];
$form_errors = $_SESSION['cat_form_errors'] ?? [];
$flash_error = $_SESSION['cat_flash_error'] ?? '';
$flash_ok    = $_SESSION['cat_flash_ok']    ?? '';
unset(
    $_SESSION['cat_form_data'],
    $_SESSION['cat_form_errors'],
    $_SESSION['cat_flash_error'],
    $_SESSION['cat_flash_ok']
);

$edit_id  = (int)($_GET['edit'] ?? 0);
$edit_cat = $edit_id ? get_category_by_id($pdo, $edit_id) : false;

require __DIR__ . '/categories.view.php';
require_once __DIR__ . '/../../includes/footer.php';
