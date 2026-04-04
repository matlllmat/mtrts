<?php
// modules/assets/_table.php
// Shared table partial used by list.php (initial render) and search_ajax.php (AJAX updates).
// Expects: $assets, $total, $current_page, $per_page to be set before inclusion.
$total_pages  = max(1, (int) ceil($total / $per_page));
$showing_from = $total > 0 ? ($current_page - 1) * $per_page + 1 : 0;
$showing_to   = min($current_page * $per_page, $total);
?>
<div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
  <div class="overflow-x-auto">
    <table class="w-full border-collapse">
      <thead>
        <tr class="bg-gray-50 border-b border-gray-200">
          <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500 whitespace-nowrap">Asset Tag</th>
          <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500 whitespace-nowrap">Manufacturer / Model</th>
          <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500 whitespace-nowrap">Category</th>
          <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500 whitespace-nowrap">Building</th>
          <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500 whitespace-nowrap">Floor</th>
          <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500 whitespace-nowrap">Room</th>
          <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500 whitespace-nowrap">Status</th>
          <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500 whitespace-nowrap">Warranty Expiry</th>
          <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500 whitespace-nowrap">Last Updated</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($assets)): ?>
          <tr>
            <td colspan="9" class="px-4 py-12 text-center text-gray-400 text-sm">
              No assets found matching your filters.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($assets as $a): ?>
            <tr class="row-link border-b border-gray-50"
                onclick="window.location='view.php?id=<?= $a['asset_id'] ?>'">
              <td class="px-4 py-3"><span class="asset-tag"><?= htmlspecialchars($a['asset_tag']) ?></span></td>
              <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($a['manufacturer'] . ' ' . $a['model']) ?></td>
              <td class="px-4 py-3"><?= cat_badge($a['category_name'] ?? '—') ?></td>
              <td class="px-4 py-3 text-gray-700 text-sm"><?= htmlspecialchars($a['building'] ?? '—') ?></td>
              <td class="px-4 py-3 text-gray-700 text-sm"><?= htmlspecialchars($a['floor'] ?? '—') ?></td>
              <td class="px-4 py-3 text-gray-700 text-sm"><?= htmlspecialchars($a['room'] ?? '—') ?></td>
              <td class="px-4 py-3"><?= status_badge($a['status']) ?></td>
              <td class="px-4 py-3 text-sm"><?= warranty_cell($a['warranty_end']) ?></td>
              <td class="px-4 py-3 text-xs text-gray-400"><?= time_ago($a['updated_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div class="pg-wrap">
    <span>
      <?php if ($total === 0): ?>
        No assets found
      <?php else: ?>
        Showing <?= $showing_from ?>–<?= $showing_to ?> of <?= $total ?> asset<?= $total !== 1 ? 's' : '' ?>
      <?php endif; ?>
    </span>
    <div class="pg-btns">
      <?php if ($current_page > 1): ?>
        <button class="pg-btn" onclick="goToPage(<?= $current_page - 1 ?>)">‹</button>
      <?php endif; ?>

      <?php
      $range = 2;
      $sp    = max(1, $current_page - $range);
      $ep    = min($total_pages, $current_page + $range);
      if ($sp > 1) {
          echo '<button class="pg-btn" onclick="goToPage(1)">1</button>';
          if ($sp > 2) echo '<span class="pg-btn" style="cursor:default;border:none">…</span>';
      }
      for ($i = $sp; $i <= $ep; $i++) {
          $cls = $i === $current_page ? ' pg-on' : '';
          echo "<button class=\"pg-btn{$cls}\" onclick=\"goToPage({$i})\">{$i}</button>";
      }
      if ($ep < $total_pages) {
          if ($ep < $total_pages - 1) echo '<span class="pg-btn" style="cursor:default;border:none">…</span>';
          echo "<button class=\"pg-btn\" onclick=\"goToPage({$total_pages})\">{$total_pages}</button>";
      }
      ?>

      <?php if ($current_page < $total_pages): ?>
        <button class="pg-btn" onclick="goToPage(<?= $current_page + 1 ?>)">›</button>
      <?php endif; ?>
    </div>
  </div>
</div>
