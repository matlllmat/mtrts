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

<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-5 mb-5 flex items-center justify-between gap-3">
  <div class="flex items-center gap-4">
    <div class="w-12 h-12 rounded-2xl bg-green-50 flex items-center justify-center text-olfu-green">
      <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
      </svg>
    </div>
    <div>
      <h2 class="text-xl font-bold text-gray-900 tracking-tight leading-tight">Work Orders</h2>
      <div class="flex items-center gap-2 mt-0.5">
        <span class="text-sm text-gray-500 font-medium">Adjust Stock</span>
        <span class="text-gray-300">·</span>
        <span class="text-sm text-gray-900 font-semibold"><?= htmlspecialchars($part['part_name']) ?></span>
        <span class="wo-tag ml-1"><?= htmlspecialchars($part['part_number']) ?></span>
      </div>
    </div>
  </div>
  <a href="index.php" class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 hover:text-gray-900 transition-colors px-4 py-2 rounded-lg hover:bg-gray-100">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    Back
  </a>
</div>

<?php if ($error): ?>
<div class="wo-banner banner-warn mb-5">
  <svg class="flex-shrink-0 w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
  <span><?= htmlspecialchars($error) ?></span>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <!-- Left: Adjustment Form -->
  <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/30">
      <h3 class="text-sm font-bold text-gray-800">New Adjustment</h3>
    </div>
    
    <form method="POST" class="p-6 space-y-6" id="adj-form">
      <input type="hidden" name="part_id" value="<?= (int)$part['part_id'] ?>">
      
      <div>
        <label class="flbl mb-3">Adjustment Type <span class="text-red-400">*</span></label>
        <div class="flex gap-4">
          <label class="adj-type-card selected-add" id="card-add">
            <input type="radio" name="direction" value="add" checked onchange="updateCards()">
            <div class="adj-type-icon">+</div>
            <div>
              <p class="text-sm font-bold text-gray-800">Add Stock</p>
              <p class="text-[11px] text-gray-400">Increase inventory count</p>
            </div>
          </label>
          <label class="adj-type-card" id="card-sub">
            <input type="radio" name="direction" value="subtract" onchange="updateCards()">
            <div class="adj-type-icon">−</div>
            <div>
              <p class="text-sm font-bold text-gray-800">Remove Stock</p>
              <p class="text-[11px] text-gray-400">Decrease inventory count</p>
            </div>
          </label>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="flbl">Quantity to Adjust <span class="text-red-400">*</span></label>
          <div class="relative">
            <input type="number" name="qty" min="1" value="1" class="fin pl-10" required>
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
            </svg>
          </div>
        </div>
      </div>

      <div>
        <label class="flbl">Reason for Adjustment <span class="text-red-400">*</span></label>
        <textarea name="reason" rows="3" class="fin" 
                  placeholder="e.g. Received PO #1234, Stock count correction, or Damaged unit removed…" required></textarea>
      </div>

      <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-50">
        <a href="index.php" class="text-sm font-semibold text-gray-500 hover:text-gray-800 px-6 py-2.5 rounded-xl hover:bg-gray-100 transition-colors">
          Cancel
        </a>
        <button type="submit" class="bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-bold px-8 py-2.5 rounded-xl shadow-md shadow-green-900/10 transition-all active:scale-[0.98]">
          Save Adjustment
        </button>
      </div>
    </form>
  </div>

  <!-- Right: Stock & History -->
  <div class="space-y-6">
    <!-- Stock Summary Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-50 bg-gray-50/30">
        <h3 class="text-sm font-bold text-gray-800">Current Status</h3>
      </div>
      <div class="p-5 space-y-1">
        <div class="rp-row">
          <span class="rp-lbl">On Hand</span>
          <span class="text-lg font-extrabold text-gray-900"><?= (int)$part['quantity_on_hand'] ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Reorder Level</span>
          <span class="rp-val"><?= (int)$part['reorder_level'] ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Unit Cost</span>
          <span class="rp-val"><?= $part['unit_cost'] !== null ? '₱' . number_format((float)$part['unit_cost'], 2) : '—' ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Storage</span>
          <span class="rp-val"><?= htmlspecialchars($part['storage_location'] ?? '—') ?></span>
        </div>
      </div>
    </div>

    <!-- History Card -->
    <?php if ($history): ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-50 bg-gray-50/30">
        <h3 class="text-sm font-bold text-gray-800">Recent Activity</h3>
      </div>
      <div class="p-4 space-y-4">
        <?php foreach ($history as $h): ?>
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg <?= $h['quantity_change'] > 0 ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600' ?> flex items-center justify-center flex-shrink-0 mt-0.5">
              <span class="text-xs font-bold"><?= $h['quantity_change'] > 0 ? '+' : '' ?><?= (int)$h['quantity_change'] ?></span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-bold text-gray-800"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $h['change_type']))) ?></span>
                <span class="text-[10px] text-gray-400"><?= (new DateTime($h['changed_at']))->format('M j, g:ia') ?></span>
              </div>
              <p class="text-[11px] text-gray-500 line-clamp-2 mt-0.5"><?= htmlspecialchars($h['reason'] ?: 'No reason provided') ?></p>
              <div class="text-[10px] text-gray-400 font-medium mt-1">→ <?= (int)$h['new_quantity'] ?> on hand</div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
function updateCards() {
  const addCard = document.getElementById('card-add');
  const subCard = document.getElementById('card-sub');
  const radios = document.getElementsByName('direction');
  
  if (radios[0].checked) {
    addCard.classList.add('selected-add');
    subCard.classList.remove('selected-sub');
  } else {
    addCard.classList.remove('selected-add');
    subCard.classList.add('selected-sub');
  }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
