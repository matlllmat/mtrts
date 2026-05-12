<?php
// modules/inventory/alerts.php — Low-stock alerts dashboard.

$module = 'inventory';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alert_id'])) {
    acknowledge_alert($pdo, (int)$_POST['alert_id'], (int)$_SESSION['user_id']);
    header('Location: alerts.php?ack=1');
    exit;
}

$show_all = !empty($_GET['all']);
$alerts   = get_low_stock_alerts($pdo, !$show_all);
?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 mb-4 flex items-center justify-between gap-3">
  <div>
    <h2 class="text-xl font-bold text-gray-900 tracking-tight">Work Orders</h2>
    <p class="text-sm text-gray-400 mt-0.5">Low-Stock Alerts &amp; Review</p>
  </div>
  <div class="flex gap-2">
    <a href="?<?= $show_all ? '' : 'all=1' ?>" class="text-sm font-semibold text-gray-600 hover:text-gray-900 px-4 py-2 rounded-lg border border-gray-200">
      <?= $show_all ? 'Show Unacknowledged Only' : 'Show All' ?>
    </a>
    <a href="index.php" class="text-sm font-semibold text-gray-500 hover:text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-100">← Back</a>
  </div>
</div>

<?php if (!$alerts): ?>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center">
    <p class="text-sm text-gray-400 italic">No <?= $show_all ? '' : 'unacknowledged ' ?>alerts at the moment.</p>
  </div>
<?php else: ?>
  <?php foreach ($alerts as $a): ?>
    <div class="alert-card <?= $a['alert_type'] === 'out_of_stock' ? 'alert-out' : '' ?>">
      <div class="flex-1">
        <div class="flex items-center gap-2 flex-wrap">
          <span class="font-semibold text-gray-800"><?= htmlspecialchars($a['part_name']) ?></span>
          <span class="wo-tag text-xs"><?= htmlspecialchars($a['part_number']) ?></span>
          <?php if ($a['alert_type'] === 'out_of_stock'): ?>
            <span class="stock-pill stock-out"><span class="bdot"></span>Out of stock</span>
          <?php else: ?>
            <span class="stock-pill stock-low"><span class="bdot"></span>Low stock</span>
          <?php endif; ?>
          <?php if ($a['is_acknowledged']): ?>
            <span class="wo-badge badge-closed">Acknowledged</span>
          <?php endif; ?>
        </div>
        <div class="text-xs text-gray-500 mt-1">
          Current on hand: <strong><?= (int)$a['current_on_hand'] ?></strong> · Reorder at: <?= (int)$a['reorder_level'] ?> · Triggered <?= (new DateTime($a['created_at']))->format('M j, g:ia') ?>
          <?php if ($a['is_acknowledged'] && $a['ack_by_name']): ?>
            · Acknowledged by <?= htmlspecialchars($a['ack_by_name']) ?> on <?= (new DateTime($a['acknowledged_at']))->format('M j, g:ia') ?>
          <?php endif; ?>
        </div>
      </div>
      <?php if (!$a['is_acknowledged']): ?>
      <form method="POST" class="m-0">
        <input type="hidden" name="alert_id" value="<?= (int)$a['alert_id'] ?>">
        <button type="submit" class="bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-4 py-2 rounded-lg">Acknowledge</button>
      </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
