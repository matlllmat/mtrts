<?php
// Status color map: [tailwind bg+text classes, hex accent for border]
$ev_colors = [
    'new'         => ['bg-blue-100 text-blue-900',    '#3b82f6'],
    'scheduled'   => ['bg-purple-100 text-purple-900','#8b5cf6'],
    'assigned'    => ['bg-indigo-100 text-indigo-900','#6366f1'],
    'in_progress' => ['bg-amber-100 text-amber-900',  '#f59e0b'],
    'on_hold'     => ['bg-red-100 text-red-900',      '#ef4444'],
    'resolved'    => ['bg-green-100 text-green-900',  '#22c55e'],
    'closed'      => ['bg-gray-100 text-gray-500',    '#9ca3af'],
];

$nav_extra    = http_build_query(array_filter(['tech' => $filter_tech ?: null, 'type' => $filter_type ?: null]));
$nav_extra_str = $nav_extra ? '&' . $nav_extra : '';
$prev_href    = "?view={$view}&offset=" . ($offset - 1) . $nav_extra_str;
$today_href   = "?view={$view}&offset=0" . $nav_extra_str;
$next_href    = "?view={$view}&offset=" . ($offset + 1) . $nav_extra_str;
$today_mid    = new DateTime('midnight');
?>

<!-- Back -->
<div class="flex items-center gap-2 mb-4">
  <a href="index.php" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
    </svg>
    Back to List
  </a>
</div>

<!-- Header card -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 mb-3 flex flex-wrap items-center justify-between gap-3">
  <div>
    <h2 class="text-xl font-bold text-gray-900 tracking-tight">Work Order Schedule</h2>
    <p class="text-sm text-gray-400 mt-0.5"><?= htmlspecialchars($view_label) ?></p>
  </div>
  <div class="flex items-center gap-3 flex-wrap">
    <!-- View toggle -->
    <div class="flex border border-gray-200 rounded-lg overflow-hidden text-sm font-medium">
      <?php foreach (['day' => 'Day', 'week' => 'Week', 'month' => 'Month'] as $v => $lbl): ?>
      <a href="?view=<?= $v ?>&offset=0<?= $nav_extra_str ?>"
         class="px-3 py-1.5 <?= $view === $v ? 'bg-olfu-green text-white' : 'text-gray-600 hover:bg-gray-50' ?> transition-colors">
        <?= $lbl ?>
      </a>
      <?php endforeach; ?>
    </div>
    <!-- Nav arrows -->
    <div class="flex gap-1.5">
      <a href="<?= $prev_href ?>" class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition-colors">◀</a>
      <a href="<?= $today_href ?>" class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition-colors <?= $offset === 0 ? 'bg-gray-100 font-semibold' : '' ?>">Today</a>
      <a href="<?= $next_href ?>" class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm hover:bg-gray-50 transition-colors">▶</a>
    </div>
  </div>
</div>

<!-- Filter bar -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-4 py-3 mb-3 flex flex-wrap items-center gap-2">
  <select id="f-tech" onchange="applyCalFilter()" class="fsel text-sm" style="width:auto;min-width:160px">
    <option value="">All Technicians</option>
    <?php foreach ($technicians as $t): ?>
      <option value="<?= $t['user_id'] ?>" <?= $filter_tech == $t['user_id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($t['full_name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <select id="f-type" onchange="applyCalFilter()" class="fsel text-sm" style="width:auto;min-width:140px">
    <option value="">All Types</option>
    <option value="diagnosis"   <?= $filter_type === 'diagnosis'   ? 'selected' : '' ?>>Diagnosis</option>
    <option value="repair"      <?= $filter_type === 'repair'      ? 'selected' : '' ?>>Repair</option>
    <option value="maintenance" <?= $filter_type === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
    <option value="follow_up"   <?= $filter_type === 'follow_up'   ? 'selected' : '' ?>>Follow-up</option>
  </select>
  <?php if ($filter_tech || $filter_type): ?>
    <a href="?view=<?= $view ?>&offset=<?= $offset ?>" class="text-xs text-gray-400 hover:text-gray-700 underline">Clear filters</a>
  <?php endif; ?>
</div>

<script>
function applyCalFilter() {
  const params = new URLSearchParams({
    view:   '<?= $view ?>',
    offset: <?= $offset ?>,
    tech:   document.getElementById('f-tech').value,
    type:   document.getElementById('f-type').value,
  });
  window.location = 'calendar.php?' + params.toString();
}
</script>

<?php if ($view === 'month'): ?>
<!-- ══ MONTH VIEW ══════════════════════════════════════════════ -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
  <!-- Day-of-week header -->
  <div class="grid grid-cols-7 border-b border-gray-100">
    <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dow_label): ?>
    <div class="text-center py-2.5 text-xs font-bold text-gray-500 uppercase tracking-wider border-r border-gray-100 last:border-0">
      <?= $dow_label ?>
    </div>
    <?php endforeach; ?>
  </div>
  <!-- Week rows -->
  <?php foreach ($cal_weeks as $week_row): ?>
  <div class="grid grid-cols-7 border-b border-gray-100 last:border-0" style="min-height:7rem;">
    <?php foreach ($week_row as $day):
      $cell_offset_val = (int)round(((new DateTime($day['date']))->getTimestamp() - $today_mid->getTimestamp()) / 86400);
      $day_wos = $wos_by_date[$day['date']] ?? [];
    ?>
    <div class="border-r border-gray-100 last:border-0 p-1.5
                <?= !$day['in_month'] ? 'bg-gray-50/60' : '' ?>
                <?= $day['is_today'] ? 'bg-green-50' : '' ?>">
      <!-- Day number — clicking drills into day view -->
      <a href="?view=day&offset=<?= $cell_offset_val ?><?= $nav_extra_str ?>"
         class="inline-flex items-center justify-center w-7 h-7 rounded-full text-sm font-semibold mb-1
                <?= $day['is_today'] ? 'bg-olfu-green text-white' : (!$day['in_month'] ? 'text-gray-300 hover:bg-gray-100' : 'text-gray-700 hover:bg-gray-100') ?>">
        <?= $day['num'] ?>
      </a>
      <!-- WO chips (max 3 visible) -->
      <?php foreach (array_slice($day_wos, 0, 3) as $dwo):
        [$ev_cls, $ev_accent] = $ev_colors[$dwo['status']] ?? $ev_colors['closed'];
      ?>
      <a href="view.php?id=<?= $dwo['wo_id'] ?>"
         class="block text-[10px] truncate rounded px-1 py-0.5 mb-0.5 <?= $ev_cls ?>"
         style="border-left:2px solid <?= $ev_accent ?>; line-height:1.5;"
         title="<?= htmlspecialchars($dwo['wo_number'] . ' — ' . ($dwo['technician_name'] ?: 'Unassigned')) ?>">
        <?= htmlspecialchars($dwo['wo_number']) ?>
      </a>
      <?php endforeach; ?>
      <?php if (count($day_wos) > 3): ?>
      <a href="?view=day&offset=<?= $cell_offset_val ?><?= $nav_extra_str ?>"
         class="text-[10px] text-gray-400 hover:text-gray-600">
        +<?= count($day_wos) - 3 ?> more
      </a>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
</div>

<?php else: ?>
<!-- ══ DAY / WEEK VIEW ════════════════════════════════════════= -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
<div class="flex flex-col" style="min-width:<?= $view === 'day' ? '420px' : '800px' ?>;">
  <!-- Column headers -->
  <div class="flex border-b border-gray-100">
    <div class="w-16 flex-shrink-0 bg-gray-50 border-r border-gray-100"></div>
    <?php foreach($days as $d): ?>
    <div class="flex-1 text-center py-3 border-r border-gray-100 last:border-0 <?= $d['is_today'] ? 'bg-green-50' : '' ?>">
      <div class="text-xs uppercase font-bold text-gray-400 tracking-wider"><?= $d['day'] ?></div>
      <div class="text-lg font-bold <?= $d['is_today'] ? 'text-olfu-green' : 'text-gray-900' ?>"><?= $d['num'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Hour grid -->
  <div class="flex relative" style="height:<?= count($hours) * 4 ?>rem;">
    <!-- Time column -->
    <div class="w-16 flex-shrink-0 flex flex-col border-r border-gray-100 bg-gray-50 absolute left-0 top-0 bottom-0 z-10">
      <?php foreach($hours as $h): ?>
      <div class="h-16 border-b border-gray-100 text-xs text-gray-400 text-right pr-2 pt-1">
        <?= $h > 12 ? $h-12 : $h ?><?= $h >= 12 ? 'pm' : 'am' ?>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Day columns -->
    <div class="flex-1 flex ml-16">
      <?php foreach($days as $d): ?>
      <div class="flex-1 relative border-r border-gray-100 last:border-0 <?= $d['is_today'] ? 'bg-green-50/30' : '' ?>">
        <!-- Grid lines -->
        <?php foreach($hours as $h): ?>
        <div class="h-16 border-b border-gray-100/60"></div>
        <?php endforeach; ?>

        <!-- WO event blocks -->
        <?php foreach($wos as $wo):
          $ws = new DateTime($wo['scheduled_start']);
          $we = new DateTime($wo['scheduled_end']);
          if ($ws->format('Y-m-d') !== $d['date']) continue;

          $start_h = (int)$ws->format('H') + ((int)$ws->format('i') / 60);
          $end_h   = (int)$we->format('H') + ((int)$we->format('i') / 60);
          $start_h = max($start_h, $hour_first);
          $end_h   = min($end_h,   $hour_last + 1);
          if ($end_h <= $hour_first || $start_h >= $hour_last + 1) continue;

          $top    = ($start_h - $hour_first) * 4;
          $height = max(($end_h - $start_h) * 4, 2.5); // min 2.5rem so short WOs are always visible
          [$ev_cls, $ev_accent] = $ev_colors[$wo['status']] ?? $ev_colors['closed'];
        ?>
        <a href="view.php?id=<?= $wo['wo_id'] ?>"
           class="absolute left-0.5 right-0.5 rounded overflow-hidden shadow-sm z-20 transition-all hover:shadow-md hover:brightness-95 <?= $ev_cls ?>"
           style="border-left:3px solid <?= $ev_accent ?>; top:<?= $top ?>rem; height:<?= $height ?>rem;"
           title="<?= htmlspecialchars($wo['wo_number'] . ' — ' . ($wo['technician_name'] ?: 'Unassigned') . ' (' . $wo['status'] . ')') ?>">
          <div class="px-1.5 pt-1 overflow-hidden h-full">
            <div class="text-[11px] font-bold truncate leading-tight"><?= htmlspecialchars($wo['wo_number']) ?></div>
            <?php if ($height >= 2.5): ?>
            <div class="text-[10px] truncate opacity-80 leading-tight"><?= htmlspecialchars($wo['technician_name'] ?: 'Unassigned') ?></div>
            <?php endif; ?>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
</div>
<?php endif; ?>
