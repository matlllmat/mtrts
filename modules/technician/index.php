<?php
$module = 'technician';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';

// Everyone can see all work orders (queue-without-claim).
$hasListFn = function_exists('get_all_queue_work_orders');
tech_dbg('H_BOOT', 'modules/technician/index.php:8', 'Index boot', ['has_get_all_queue_work_orders' => $hasListFn]);

$all_work_orders = get_all_queue_work_orders($pdo);
$my_work_orders  = get_assigned_work_orders($pdo, $_SESSION['user_id']);

// Debug: Check work order data
tech_dbg('H_WO_DATA', 'modules/technician/index.php', 'Work order data debug', [
    'total_work_orders' => count($all_work_orders),
    'sample_wo' => array_map(function($wo) {
        return [
            'wo_id' => $wo['wo_id'],
            'wo_number' => $wo['wo_number'],
            'assigned_to' => $wo['assigned_to'],
            'assigned_to_name' => $wo['assigned_to_name'] ?? 'NULL',
            'status' => $wo['status']
        ];
    }, array_slice($all_work_orders, 0, 3))
]);

$role_name = function_exists('current_role_name') ? (current_role_name($pdo) ?: '') : '';
$is_admin = tech_is_admin_role($pdo);
tech_dbg('H1', 'modules/technician/index.php:15', 'Technician index loaded', [
    'has_global_current_role_name' => function_exists('current_role_name'),
    'role_name' => $role_name,
    'is_admin' => $is_admin,
    'has_role_queue_schema' => technician_has_role_queue_schema($pdo),
]);

require __DIR__ . '/index.view.php';
require_once __DIR__ . '/../../includes/footer.php';
?>