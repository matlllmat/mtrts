<?php
require 'c:/xampp/htdocs/mtrts/config/db.php';
require 'c:/xampp/htdocs/mtrts/modules/technician/functions.php';
$pdo = get_db_connection();
$wo = $pdo->query('SELECT wo_id FROM work_orders LIMIT 1')->fetch();
if ($wo) {
    session_start();
    $_SESSION['user_id'] = 1;
    add_work_order_note($pdo, $wo['wo_id'], 'TESTING NOTE', false);
    echo 'Note Added.' . PHP_EOL;
    print_r(get_work_order_notes($pdo, $wo['wo_id']));
}
