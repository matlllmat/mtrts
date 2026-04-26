<?php
// modules/workorders/add.php — Create new work order.
// Logic only: guard, load lookups, require the shared form view.

$module = 'workorders';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$is_edit     = false;
$wo          = [];
$ticket_id   = (int)($_GET['ticket_id'] ?? 0);
if ($ticket_id > 0) {
    $wo['ticket_id'] = $ticket_id;
}
$errors      = $_SESSION['wo_errors'] ?? [];
$old         = $_SESSION['wo_old'] ?? [];
unset($_SESSION['wo_errors'], $_SESSION['wo_old']);

$technicians = get_all_technicians($pdo);
$tickets     = get_available_tickets($pdo);

require __DIR__ . '/_form.view.php';
require_once __DIR__ . '/../../includes/footer.php';
