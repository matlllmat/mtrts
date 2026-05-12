<?php
// modules/inventory/adjust.php — Stock adjustment form + POST handler.

$module = 'inventory';
require_once __DIR__ . '/../../config/guard.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/_styles.php';

$id = (int)($_GET['id'] ?? $_POST['part_id'] ?? 0);
$part = $id > 0 ? get_part($pdo, $id) : null;
if (!$part) { header('Location: index.php'); exit; }

$error = null;
$ok    = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $direction = $_POST['direction'] ?? 'add';
    $qty       = (int)($_POST['qty'] ?? 0);
    $reason    = trim($_POST['reason'] ?? '');
    if ($qty <= 0)         $error = 'Quantity must be greater than zero.';
    elseif ($reason === '') $error = 'Please provide a reason for the adjustment.';
    else {
        try {
            $delta = $direction === 'subtract' ? -$qty : $qty;
            $result = adjust_stock($pdo, $id, $delta, $reason, (int)$_SESSION['user_id']);
            header('Location: index.php?adjusted=1&id=' . $id);
            exit;
        } catch (Throwable $e) {
            $error = 'Adjustment failed: ' . $e->getMessage();
        }
    }
}

$history = get_part_audit_history($pdo, $id, 15);
?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 mb-4 flex items-center justify-between gap-3">
  <div>
    <h2 class="text-xl font-bold text-gray-900 tracking-tight">Work Orders</h2>
    <p class="text-sm text-gray-400 mt-0.5">Adjust Stock · <?= htmlspecialchars($part['part_name']) ?> <span class="wo-tag ml-1"><?= htmlspecialchars($part['part_number']) ?></span></p>
  </div>
  <a href="index.php" class="text-sm font-semibold text-gray-500 hover:text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-100">← Back</a>
</div>

<?php if ($error): ?>
<div class="wo-banner banner-warn mb-4">
  <svg class="flex-shrink-0 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01"/></svg>
  <span><?= htmlspecialchars($error) ?></span>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="sdiv mb-4" style="padding-top:0">Adjustment</div>
    <form method="POST" class="space-y-4">
      <input type="hidden" name="part_id" value="<?= (int)$part['part_id'] ?>">
      <div class="grid grid-cols-2 gap-3">
        <label class="flex items-center gap-2 border-2 border-gray-200 hover:border-green-400 rounded-lg p-3 cursor-pointer">
          <input type="radio" name="direction" value="add" checked class="text-olfu-green">
          <span class="font-semibold text-gray-700">+ Add stock</span>
        </label>
        <label class="flex items-center gap-2 border-2 border-gray-200 hover:border-red-400 rounded-lg p-3 cursor-pointer">
          <input type="radio" name="direction" value="subtract" class="text-red-600">
          <span class="font-semibold text-gray-700">− Remove stock</span>
        </label>
      </div>
      <div>
        <label class="flbl">Quantity <span class="text-red-400">*</span></label>
        <input type="number" name="qty" min="1" value="1" class="fin" required>
      </div>
      <div>
        <label class="flbl">Reason <span class="text-red-400">*</span></label>
        <textarea name="reason" rows="3" class="fin" placeholder="e.g. Received PO #1234, Damaged unit removed, Stock count correction" required></textarea>
      </div>
      <div class="flex items-center justify-end gap-3 pt-2">
        <a href="index.php" class="text-sm font-semibold text-gray-500 hover:text-gray-800 px-6 py-2.5 rounded-xl hover:bg-gray-100">Cancel</a>
        <button type="submit" class="bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-6 py-2.5 rounded-xl">Save Adjustment</button>
      </div>
    </form>
  </div>

  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="sdiv mb-3" style="padding-top:0">Current Stock</div>
    <div class="rp-row"><span class="rp-lbl">On Hand</span><span class="rp-val"><?= (int)$part['quantity_on_hand'] ?></span></div>
    <div class="rp-row"><span class="rp-lbl">Reorder Level</span><span class="rp-val"><?= (int)$part['reorder_level'] ?></span></div>
    <div class="rp-row"><span class="rp-lbl">Unit Cost</span><span class="rp-val"><?= $part['unit_cost'] !== null ? '₱' . number_format((float)$part['unit_cost'], 2) : '—' ?></span></div>
    <div class="rp-row"><span class="rp-lbl">Storage</span><span class="rp-val"><?= htmlspecialchars($part['storage_location'] ?? '—') ?></span></div>

    <?php if ($history): ?>
    <div class="sdiv mb-2 mt-5">Recent History</div>
    <?php foreach ($history as $h): ?>
      <div class="audit-row">
        <span class="<?= $h['quantity_change'] > 0 ? 'audit-delta-pos' : 'audit-delta-neg' ?>">
          <?= $h['quantity_change'] > 0 ? '+' : '' ?><?= (int)$h['quantity_change'] ?>
        </span>
        <span class="ml-2 text-gray-500">→ <?= (int)$h['new_quantity'] ?></span>
        <?php if ($h['reason']): ?><div class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($h['reason']) ?></div><?php endif; ?>
        <div class="text-xs text-gray-400 mt-0.5"><?= (new DateTime($h['changed_at']))->format('M j, g:ia') ?></div>
      </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
