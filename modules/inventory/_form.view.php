<?php
// modules/inventory/_form.view.php — Shared add/edit form.
$v = function(string $k, $default = '') use ($old, $part) {
    if (array_key_exists($k, $old))  return htmlspecialchars((string)$old[$k]);
    if (array_key_exists($k, $part)) return htmlspecialchars((string)$part[$k]);
    return htmlspecialchars((string)$default);
};
$e = fn(string $k) => isset($errors[$k]) ? 'fin-err' : '';
$_categories = get_part_categories($pdo);

// Stock status helper for edit mode chip
$_stock_class = '';
$_stock_label = '';
if ($is_edit && isset($part['quantity_on_hand'])) {
    $qty = (int)$part['quantity_on_hand'];
    $rl  = (int)$part['reorder_level'];
    if ($qty <= 0)       { $_stock_class = 'stock-out'; $_stock_label = 'Out of Stock'; }
    elseif ($qty <= $rl) { $_stock_class = 'stock-low'; $_stock_label = 'Low Stock'; }
    else                 { $_stock_class = 'stock-ok';  $_stock_label = 'In Stock'; }
}
?>

<!-- Back link -->
<div class="flex items-center gap-2 mb-4">
  <a href="index.php"
     class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
    </svg>
    Back to Inventory
  </a>
</div>

<!-- Page header card -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-5 mb-5">
  <h2 class="text-xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
    <span class="block w-1 h-6 bg-olfu-green rounded-full flex-shrink-0"></span>
    Work Orders
  </h2>
  <p class="text-sm text-gray-500 mt-1 font-medium">
    <?= $is_edit ? 'Edit Part — ' . htmlspecialchars($part['part_name'] ?? '') : 'Add New Part' ?>
  </p>
  <p class="text-xs text-gray-400 mt-1">
    <?= $is_edit
      ? 'Update part details in the inventory catalog. To change stock levels, use the Adjust action.'
      : 'Register a new part in the inventory catalog. Initial stock is recorded in the audit log.' ?>
  </p>
</div>

<!-- Error banner -->
<?php if ($errors): ?>
<div class="wo-banner banner-warn mb-5">
  <svg class="flex-shrink-0 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
  </svg>
  <span>Please correct the errors highlighted below before saving.</span>
</div>
<?php endif; ?>

<form method="POST" action="save.php" class="grid grid-cols-1 lg:grid-cols-3 gap-5">
  <input type="hidden" name="part_id" value="<?= $is_edit ? (int)$part['part_id'] : '' ?>">

  <!-- ── Left: core fields (2/3) ─────────────────────── -->
  <div class="lg:col-span-2 space-y-5">

    <!-- Part Details card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <!-- Card header -->
      <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gray-50/50">
        <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0">
          <svg class="w-5 h-5 text-olfu-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a2 2 0 012-2h2z"/>
          </svg>
        </div>
        <div>
          <p class="text-sm font-bold text-gray-800 tracking-tight">Part Details</p>
          <p class="text-[11px] text-gray-400 font-medium">Identification and categorization</p>
        </div>
      </div>

      <!-- Fields -->
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

          <!-- Part Number -->
          <div>
            <label class="flbl flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
              </svg>
              Part Number <span class="text-red-400">*</span>
            </label>
            <input type="text" name="part_number" value="<?= $v('part_number') ?>"
                   class="fin <?= $e('part_number') ?>" placeholder="e.g. PN-00142" required>
            <?php if (isset($errors['part_number'])): ?>
              <p class="ferr-msg">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                <?= htmlspecialchars($errors['part_number']) ?>
              </p>
            <?php endif; ?>
          </div>

          <!-- Part Name -->
          <div>
            <label class="flbl flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2m0 0V3h8v1m0 0H8"/>
              </svg>
              Part Name <span class="text-red-400">*</span>
            </label>
            <input type="text" name="part_name" value="<?= $v('part_name') ?>"
                   class="fin <?= $e('part_name') ?>" placeholder="e.g. HDMI Cable 2m" required>
            <?php if (isset($errors['part_name'])): ?>
              <p class="ferr-msg">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                <?= htmlspecialchars($errors['part_name']) ?>
              </p>
            <?php endif; ?>
          </div>

          <!-- Manufacturer -->
          <div>
            <label class="flbl flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
              Manufacturer
            </label>
            <input type="text" name="manufacturer" value="<?= $v('manufacturer') ?>"
                   class="fin" placeholder="e.g. Sony, Dell, Logitech">
          </div>

          <!-- Category -->
          <div>
            <label class="flbl flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
              </svg>
              Category
            </label>
            <input type="text" name="category" value="<?= $v('category') ?>"
                   class="fin" placeholder="e.g. cables, projector, audio…"
                   list="category-list" autocomplete="off">
            <datalist id="category-list">
              <?php foreach ($_categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>">
              <?php endforeach; ?>
            </datalist>
          </div>

          <!-- Description -->
          <div class="md:col-span-2">
            <label class="flbl flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              Description
            </label>
            <textarea name="description" rows="2" class="fin"
                      placeholder="Brief description of this part…"><?= $v('description') ?></textarea>
          </div>

          <!-- Compatible With -->
          <div class="md:col-span-2">
            <label class="flbl flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
              </svg>
              Compatible With
            </label>
            <input type="text" name="compatible_with" value="<?= $v('compatible_with') ?>"
                   class="fin" placeholder="Models, brands, or assets this part fits">
            <p class="fhint">Optional — helps technicians find the right part during pre-check.</p>
          </div>

        </div>
      </div>
    </div><!-- /Part Details card -->

    <!-- Stock History (edit mode only) -->
    <?php if ($is_edit && !empty($history)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-gray-50/50">
        <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0">
          <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <div>
          <p class="text-sm font-bold text-gray-800 tracking-tight">Stock History</p>
          <p class="text-[11px] text-gray-400 font-medium">Last 25 audit entries</p>
        </div>
      </div>
      <div class="p-4">
        <?php foreach ($history as $h): ?>
          <div class="audit-row flex items-center justify-between">
            <div>
              <span class="<?= $h['quantity_change'] > 0 ? 'audit-delta-pos' : 'audit-delta-neg' ?>">
                <?= $h['quantity_change'] > 0 ? '+' : '' ?><?= (int)$h['quantity_change'] ?>
              </span>
              <span class="ml-2 text-gray-500 text-xs">→ <?= (int)$h['new_quantity'] ?> on hand</span>
              <span class="ml-2 wo-badge badge-type"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $h['change_type']))) ?></span>
              <?php if ($h['reason']): ?><div class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($h['reason']) ?></div><?php endif; ?>
            </div>
            <div class="text-xs text-gray-400 text-right flex-shrink-0 ml-3">
              <?= htmlspecialchars($h['user_name'] ?? '—') ?><br>
              <?= (new DateTime($h['changed_at']))->format('M j, g:ia') ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /left column -->

  <!-- ── Right: stock, pricing, actions (1/3) ───────── -->
  <div class="space-y-4">

    <!-- Stock & Pricing card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <!-- Card header -->
      <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 bg-gray-50/50">
        <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0">
          <svg class="w-5 h-5 text-olfu-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
          </svg>
        </div>
        <div>
          <p class="text-sm font-bold text-gray-800 tracking-tight">Stock &amp; Pricing</p>
          <p class="text-[11px] text-gray-400 font-medium">Quantity, pricing, and location</p>
        </div>
      </div>

      <div class="p-5 space-y-4">

        <!-- Edit mode: quick status chip -->
        <?php if ($is_edit): ?>
        <div class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2.5 border border-gray-100">
          <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Current Stock</span>
          <div class="flex items-center gap-2">
            <span class="stock-pill <?= $_stock_class ?>">
              <span class="bdot"></span><?= $_stock_label ?>
            </span>
            <span class="text-sm font-bold text-gray-800"><?= (int)$part['quantity_on_hand'] ?></span>
          </div>
        </div>
        <?php endif; ?>

        <!-- Initial Qty (add) / Current On Hand (edit) -->
        <?php if (!$is_edit): ?>
          <div>
            <label class="flbl flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
              </svg>
              Initial Quantity
            </label>
            <input type="number" name="initial_qty" value="<?= $v('initial_qty', '0') ?>"
                   min="0" class="fin">
            <p class="fhint">Starting on-hand count. Use Adjust later to change stock.</p>
          </div>
        <?php else: ?>
          <div>
            <label class="flbl flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
              </svg>
              On Hand
            </label>
            <input type="number" value="<?= (int)$part['quantity_on_hand'] ?>" class="fin" readonly>
            <p class="fhint">
              <a href="adjust.php?id=<?= (int)$part['part_id'] ?>"
                 class="text-olfu-green font-semibold hover:underline">Adjust stock →</a>
            </p>
          </div>
        <?php endif; ?>

        <!-- Reorder Level -->
        <div>
          <label class="flbl flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            Reorder Level <span class="text-red-400">*</span>
          </label>
          <input type="number" name="reorder_level" value="<?= $v('reorder_level', '5') ?>"
                 min="0" class="fin <?= $e('reorder_level') ?>" required>
          <p class="fhint">Alert fires when on-hand falls to or below this number.</p>
          <?php if (isset($errors['reorder_level'])): ?>
            <p class="ferr-msg">
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
              <?= htmlspecialchars($errors['reorder_level']) ?>
            </p>
          <?php endif; ?>
        </div>

        <!-- Unit Cost -->
        <div>
          <label class="flbl flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Unit Cost (₱)
          </label>
          <input type="number" step="0.01" name="unit_cost" value="<?= $v('unit_cost') ?>"
                 min="0" class="fin" placeholder="0.00">
        </div>

        <!-- Unit Price -->
        <div>
          <label class="flbl flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Unit Price (₱)
          </label>
          <input type="number" step="0.01" name="unit_price" value="<?= $v('unit_price') ?>"
                 min="0" class="fin" placeholder="0.00">
        </div>

        <!-- Storage Location -->
        <div>
          <label class="flbl flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Storage Location
          </label>
          <input type="text" name="storage_location" value="<?= $v('storage_location') ?>"
                 class="fin" placeholder="Cabinet A-2, Bin 7…">
        </div>

      </div>
    </div><!-- /Stock & Pricing card -->

    <!-- Save actions card -->
    <div class="bg-gray-50 border border-gray-200 rounded-xl px-5 py-4 flex items-center justify-end gap-3">
      <a href="index.php"
         class="text-sm font-semibold text-gray-500 hover:text-gray-800 px-5 py-2.5 rounded-lg hover:bg-gray-200 transition-colors">
        Cancel
      </a>
      <button type="submit"
              class="inline-flex items-center gap-2 bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <?php if ($is_edit): ?>
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
          <?php else: ?>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
          <?php endif; ?>
        </svg>
        <?= $is_edit ? 'Save Changes' : 'Create Part' ?>
      </button>
    </div>

  </div><!-- /right column -->

</form>
