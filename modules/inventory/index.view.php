<?php
// modules/inventory/index.view.php
$total_pages = max(1, (int)ceil($total / $per_page));
function inv_stock_pill(array $p): string {
    if ((int)$p['quantity_on_hand'] <= 0) return '<span class="stock-pill stock-out"><span class="bdot"></span>Out</span>';
    if ((int)$p['quantity_on_hand'] <= (int)$p['reorder_level']) return '<span class="stock-pill stock-low"><span class="bdot"></span>Low</span>';
    return '<span class="stock-pill stock-ok"><span class="bdot"></span>OK</span>';
}
function inv_row_class(array $p): string {
    if ((int)$p['quantity_on_hand'] <= 0) return 'row-out';
    if ((int)$p['quantity_on_hand'] <= (int)$p['reorder_level']) return 'row-low';
    return '';
}
?>

<?php if ($alert_n > 0): ?>
<div class="wo-banner banner-info mb-4">
  <svg class="flex-shrink-0 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
  </svg>
  <span><strong><?= $alert_n ?></strong> unacknowledged low-stock alert<?= $alert_n !== 1 ? 's' : '' ?>.</span>
  <a href="alerts.php" class="banner-link text-amber-800">Review now →</a>
</div>
<?php endif; ?>

<!-- Header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 mb-4 flex flex-wrap items-center justify-between gap-3">
  <div>
    <h2 class="text-xl font-bold text-gray-900 tracking-tight">Work Orders</h2>
    <p class="text-sm text-gray-400 mt-0.5">Inventory &amp; Parts Management — Track stock levels and alerts.</p>
  </div>
  <div class="flex gap-2 flex-wrap">
    <a href="<?= htmlspecialchars(rtrim(BASE_URL, '/')) ?>/modules/workorders/index.php"
       class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 text-sm font-semibold px-4 py-2 rounded-lg transition-colors duration-150">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
      </svg>
      Back to Work Orders
    </a>
    <a href="alerts.php"
       class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 text-sm font-semibold px-4 py-2 rounded-lg transition-colors duration-150">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0"/>
      </svg>
      Alerts<?= $alert_n > 0 ? ' <span class="ml-1 text-xs bg-amber-100 text-amber-700 rounded-full px-1.5 py-0.5">' . $alert_n . '</span>' : '' ?>
    </a>
    <a href="add.php"
       class="inline-flex items-center gap-1.5 bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors duration-150">
      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
      Add Part
    </a>
  </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
  <div class="inv-stat">
    <div class="stat-ico bg-green-50 text-green-700"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>
    <div><div class="inv-stat-lbl">Total Parts</div><div class="inv-stat-val"><?= (int)$stats['total'] ?></div></div>
  </div>
  <div class="inv-stat">
    <div class="stat-ico bg-amber-50 text-amber-700"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01"/></svg></div>
    <div><div class="inv-stat-lbl">Low Stock</div><div class="inv-stat-val"><?= (int)$stats['low_stock'] ?></div></div>
  </div>
  <div class="inv-stat">
    <div class="stat-ico bg-red-50 text-red-700"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></div>
    <div><div class="inv-stat-lbl">Out of Stock</div><div class="inv-stat-val"><?= (int)$stats['out_of_stock'] ?></div></div>
  </div>
  <div class="inv-stat">
    <div class="stat-ico bg-blue-50 text-blue-700"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8v8m0 0v2m0-10V6"/></svg></div>
    <div><div class="inv-stat-lbl">Stock Value</div><div class="inv-stat-val">₱<?= number_format($stats['total_value'], 2) ?></div></div>
  </div>
</div>

<!-- Stock-state chips -->
<div class="flex flex-wrap gap-2 mb-3">
  <?php
    $stock_filter = $filters['stock'];
    $chip_defs = [
      ''    => ['All',        (int)$stats['total']],
      'ok'  => ['In Stock',   (int)$stats['ok_stock']],
      'low' => ['Low Stock',  (int)$stats['low_stock']],
      'out' => ['Out of Stock',(int)$stats['out_of_stock']],
    ];
    foreach ($chip_defs as $val => $info):
      [$label, $count] = $info;
      $is_on = ($stock_filter === $val);
      $params = $_GET; $params['stock'] = $val; unset($params['p']);
  ?>
  <a href="?<?= http_build_query($params) ?>" class="chip <?= $is_on ? 'chip-on' : '' ?>">
    <?= $label ?> <span class="opacity-70 font-normal">(<?= $count ?>)</span>
  </a>
  <?php endforeach; ?>
</div>

<!-- Filter bar -->
<form method="GET" class="bg-white rounded-xl shadow-sm border border-gray-100 px-4 py-3 mb-3 flex flex-wrap items-center gap-2">
  <input type="hidden" name="stock" value="<?= htmlspecialchars($filters['stock']) ?>">
  <div class="relative flex-1 min-w-48">
    <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
    <input type="text" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="Search by name, part #, manufacturer…" class="fin pr-8 text-sm" />
  </div>
  <select name="category" class="fsel text-sm" style="width:auto;min-width:150px" onchange="this.form.submit()">
    <option value="">All Categories</option>
    <?php foreach ($categories as $c): ?>
      <option value="<?= htmlspecialchars($c['category']) ?>" <?= $filters['category'] === $c['category'] ? 'selected' : '' ?>>
        <?= htmlspecialchars(ucfirst($c['category'])) ?> (<?= (int)$c['n'] ?>)
      </option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-4 py-2 rounded-lg">Search</button>
</form>

<!-- Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
  <table class="w-full border-collapse text-sm">
    <thead>
      <tr class="border-b border-gray-100 bg-gray-50">
        <th class="py-3 px-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Part</th>
        <th class="py-3 px-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Part #</th>
        <th class="py-3 px-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Category</th>
        <th class="py-3 px-4 text-right text-xs font-bold uppercase tracking-wider text-gray-500">On Hand</th>
        <th class="py-3 px-4 text-right text-xs font-bold uppercase tracking-wider text-gray-500">Reorder At</th>
        <th class="py-3 px-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Status</th>
        <th class="py-3 px-4 text-right text-xs font-bold uppercase tracking-wider text-gray-500">Unit Cost</th>
        <th class="py-3 px-4 text-center text-xs font-bold uppercase tracking-wider text-gray-500">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$parts): ?>
        <tr><td colspan="8" class="py-8 px-4 text-center text-sm text-gray-400 italic">No parts match the current filters.</td></tr>
      <?php else: foreach ($parts as $p): ?>
        <tr class="border-b border-gray-50 <?= inv_row_class($p) ?>">
          <td class="py-2.5 px-4 font-medium text-gray-800">
            <?= htmlspecialchars($p['part_name']) ?>
            <?php if ($p['manufacturer']): ?><div class="text-xs text-gray-400"><?= htmlspecialchars($p['manufacturer']) ?></div><?php endif; ?>
          </td>
          <td class="py-2.5 px-4 wo-tag"><?= htmlspecialchars($p['part_number']) ?></td>
          <td class="py-2.5 px-4 text-gray-600"><?= htmlspecialchars(ucfirst($p['category'] ?? '—')) ?></td>
          <td class="py-2.5 px-4 text-right font-semibold text-gray-800"><?= (int)$p['quantity_on_hand'] ?></td>
          <td class="py-2.5 px-4 text-right text-gray-500"><?= (int)$p['reorder_level'] ?></td>
          <td class="py-2.5 px-4"><?= inv_stock_pill($p) ?></td>
          <td class="py-2.5 px-4 text-right text-gray-600"><?= $p['unit_cost'] !== null ? '₱' . number_format((float)$p['unit_cost'], 2) : '—' ?></td>
          <td class="py-2.5 px-4 text-center">
            <a href="adjust.php?id=<?= (int)$p['part_id'] ?>" class="text-xs font-semibold text-amber-700 hover:underline mr-2">Adjust</a>
            <a href="edit.php?id=<?= (int)$p['part_id'] ?>" class="text-xs font-semibold text-olfu-green hover:underline">Edit</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

  <?php if ($total > $per_page): ?>
  <div class="pg-wrap">
    <div>Showing <?= ($current_page - 1) * $per_page + 1 ?>–<?= min($current_page * $per_page, $total) ?> of <?= $total ?></div>
    <div class="pg-btns">
      <?php
        $base = $_GET; unset($base['p']);
        $qs = http_build_query($base);
        $win = 2;
        for ($i = max(1, $current_page - $win); $i <= min($total_pages, $current_page + $win); $i++):
      ?>
        <a href="?<?= $qs ?>&p=<?= $i ?>" class="pg-btn <?= $i === $current_page ? 'pg-on' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
