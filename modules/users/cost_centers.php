<?php
// modules/users/cost_centers.php — Manage Cost Centers (departments).

$module = 'users';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$departments = get_departments_with_counts($pdo);

$edit_id  = (int)($_GET['edit'] ?? 0);
$edit_dept = $edit_id ? get_department_by_id($pdo, $edit_id) : false;

$form_data   = $_SESSION['dept_form_data']   ?? [];
$form_errors = $_SESSION['dept_form_errors'] ?? [];
$flash_ok    = $_SESSION['dept_flash_ok']    ?? '';
$flash_error = $_SESSION['dept_flash_error'] ?? '';
unset($_SESSION['dept_form_data'], $_SESSION['dept_form_errors'],
      $_SESSION['dept_flash_ok'],  $_SESSION['dept_flash_error']);

require __DIR__ . '/cost_centers.view.php';
require_once __DIR__ . '/../../includes/footer.php';
