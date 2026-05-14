<?php
// modules/reports/calendar.php
$module = 'reports';
$page_title = 'Operating Calendar';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/../../config/db.php';

// Fetch all holidays
$holidays = $pdo->query("SELECT * FROM holidays ORDER BY holiday_date ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch business hours
$biz_hours = $pdo->query("SELECT * FROM business_hours ORDER BY day_of_week ASC")->fetchAll(PDO::FETCH_ASSOC);

// For the visual calendar, we'll default to the current month
$year  = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$month = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('m');

// Fetch work orders scheduled this month
$_days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$wo_stmt = $pdo->prepare("
    SELECT w.wo_id, w.wo_number, w.scheduled_start, w.status, u.full_name AS tech_name
    FROM work_orders w
    LEFT JOIN users u ON w.assigned_to = u.user_id
    WHERE DATE(w.scheduled_start) BETWEEN ? AND ?
      AND w.scheduled_start IS NOT NULL
      AND w.status NOT IN ('cancelled')
    ORDER BY w.scheduled_start ASC
");
$wo_stmt->execute([
    sprintf('%04d-%02d-01', $year, $month),
    sprintf('%04d-%02d-%02d', $year, $month, $_days_in_month),
]);
$wo_by_date = [];
foreach ($wo_stmt->fetchAll(PDO::FETCH_ASSOC) as $_wo) {
    $wo_by_date[date('Y-m-d', strtotime($_wo['scheduled_start']))][] = $_wo;
}

require __DIR__ . '/calendar.view.php';
require_once __DIR__ . '/../../includes/footer.php';
