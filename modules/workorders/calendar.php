<?php
// modules/workorders/calendar.php — Calendar view (day / week / month)
$module = 'workorders';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$view        = in_array($_GET['view'] ?? '', ['day','week','month']) ? $_GET['view'] : 'week';
$offset      = (int)($_GET['offset'] ?? $_GET['week'] ?? 0); // backward-compat with ?week=N
$filter_tech = (int)($_GET['tech'] ?? 0);
$filter_type = trim($_GET['type'] ?? '');

$now       = new DateTime();
$today_str = $now->format('Y-m-d');

if ($view === 'day') {
    $anchor = clone $now;
    if ($offset !== 0) $anchor->modify("$offset day");
    $start_dt   = clone $anchor; $start_dt->setTime(0,0,0);
    $end_dt     = clone $anchor; $end_dt->setTime(23,59,59);
    $view_label = $anchor->format('l, F j, Y');
    $days = [[
        'date'     => $anchor->format('Y-m-d'),
        'day'      => $anchor->format('D'),
        'num'      => (int)$anchor->format('j'),
        'is_today' => $anchor->format('Y-m-d') === $today_str,
    ]];
    $cal_weeks = null;

} elseif ($view === 'month') {
    $anchor = new DateTime('first day of this month midnight');
    if ($offset !== 0) $anchor->modify("$offset month");
    $month_str  = $anchor->format('Y-m');
    $start_dt   = clone $anchor;
    $end_dt     = clone $anchor;
    $end_dt->modify('last day of this month'); $end_dt->setTime(23,59,59);
    $view_label = $anchor->format('F Y');

    $grid_start = clone $anchor;
    $dow = (int)$grid_start->format('N'); // 1=Mon
    if ($dow > 1) $grid_start->modify('-' . ($dow - 1) . ' day');

    $cal_weeks = [];
    $curr = clone $grid_start;
    do {
        $week_row = [];
        for ($d = 0; $d < 7; $d++) {
            $week_row[] = [
                'date'     => $curr->format('Y-m-d'),
                'num'      => (int)$curr->format('j'),
                'in_month' => $curr->format('Y-m') === $month_str,
                'is_today' => $curr->format('Y-m-d') === $today_str,
            ];
            $curr->modify('+1 day');
        }
        $cal_weeks[] = $week_row;
    } while ($curr <= $end_dt && count($cal_weeks) < 6);

    $days = null;

} else { // week (default)
    $anchor = clone $now;
    $anchor->modify('monday this week');
    if ($offset !== 0) $anchor->modify($offset > 0 ? "+$offset week" : "$offset week");
    $start_dt   = clone $anchor;
    $end_dt     = clone $anchor;
    $end_dt->modify('+6 days'); $end_dt->setTime(23,59,59);
    $view_label = $start_dt->format('M j') . ' – ' . $end_dt->format('M j, Y');

    $days = [];
    $curr = clone $start_dt;
    for ($i = 0; $i < 7; $i++) {
        $days[] = [
            'date'     => $curr->format('Y-m-d'),
            'day'      => $curr->format('D'),
            'num'      => (int)$curr->format('j'),
            'is_today' => $curr->format('Y-m-d') === $today_str,
        ];
        $curr->modify('+1 day');
    }
    $cal_weeks = null;
}

$start_str = $start_dt->format('Y-m-d H:i:s');
$end_str   = $end_dt->format('Y-m-d H:i:s');

// Build dynamic WHERE
$cal_where  = "w.scheduled_start IS NOT NULL AND w.scheduled_end IS NOT NULL
      AND w.status NOT IN ('cancelled')
      AND w.scheduled_start <= ? AND w.scheduled_end >= ?";
$cal_params = [$end_str, $start_str];

if ($filter_tech > 0) { $cal_where .= " AND w.assigned_to = ?"; $cal_params[] = $filter_tech; }
if ($filter_type !== '') { $cal_where .= " AND w.wo_type = ?"; $cal_params[] = $filter_type; }

$stmt = $pdo->prepare("
    SELECT w.wo_id, w.wo_number, w.wo_type, w.status, w.scheduled_start, w.scheduled_end,
           u.full_name AS technician_name, t.ticket_number
    FROM work_orders w
    LEFT JOIN users u ON w.assigned_to = u.user_id
    LEFT JOIN tickets t ON w.ticket_id = t.ticket_id
    WHERE $cal_where
    ORDER BY w.scheduled_start ASC
");
$stmt->execute($cal_params);
$wos = $stmt->fetchAll();

// Index by date for month view
$wos_by_date = [];
foreach ($wos as $wo) {
    $d = (new DateTime($wo['scheduled_start']))->format('Y-m-d');
    $wos_by_date[$d][] = $wo;
}

$technicians    = get_all_technicians($pdo);
$hours          = range(8, 18);
$hour_first     = $hours[0];
$hour_last      = $hours[count($hours) - 1];

require __DIR__ . '/calendar.view.php';
require_once __DIR__ . '/../../includes/footer.php';
