<?php
/**
 * @var array        $categories
 * @var array        $form_data
 * @var array        $form_errors
 * @var string       $flash_error
 * @var string       $flash_ok
 * @var int          $edit_id
 * @var array|false  $edit_cat
 */

$is_editing = (bool) $edit_cat;
$fv = function (string $k, string $default = '') use ($form_data, $edit_cat) {
    if (array_key_exists($k, $form_data)) return (string) $form_data[$k];
    if ($edit_cat && array_key_exists($k, $edit_cat)) return (string) $edit_cat[$k];
    return $default;
};

$bulb_checked = false;
if (array_key_exists('has_bulb_hours', $form_data)) {
    $bulb_checked = !empty($form_data['has_bulb_hours']);
} elseif ($edit_cat) {
    $bulb_checked = (int) $edit_cat['has_bulb_hours'] === 1;
}

$csrf = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(16));
?>

<!-- Back row -->
<div class="flex items-center gap-2 mb-4">
  <a href="index.php"
     class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
    </svg>
    Back to Asset Registry
  </a>
  <span class="text-gray-300">/</span>
  <span class="text-sm font-semibold text-gray-700">Manage Categories</span>
</div>

<?php if ($flash_ok): ?>
<div class="asset-banner banner-info mb-4" style="background:#f0fdf4;border-color:#bbf7d0;border-left-color:#15803d;color:#15803d;">
  <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
  </svg>
  <span><?= htmlspecialchars($flash_ok) ?></span>
</div>
<?php endif; ?>

<?php if ($flash_error): ?>
<div class="asset-banner banner-warn mb-4">
  <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
  </svg>
  <span><?= htmlspecialchars($flash_error) ?></span>
</div>
<?php endif; ?>

<!-- Page header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 mb-4 flex flex-wrap items-center justify-between gap-3">
  <div>
    <h2 class="text-xl font-bold text-gray-900 tracking-tight">Manage Categories</h2>
    <p class="text-sm text-gray-400 mt-0.5">
      Add or update equipment types. Categories in use by assets cannot be deleted.
    </p>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-4 items-start">

  <!-- LEFT: categories table -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
        <span class="block w-0.5 h-4 bg-olfu-green rounded"></span>
        All Categories
        <span class="text-xs font-normal text-gray-400" id="cat-count">(<?= count($categories) ?>)</span>
      </h3>
    </div>
    <?php if ($categories): ?>
      <div class="px-4 py-3 border-b border-gray-100">
        <div class="relative">
          <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
          </svg>
          <input type="text" id="cat-search" placeholder="Filter by name or description…"
                 class="fin pr-8 text-sm" oninput="filterCategories()">
        </div>
      </div>
      <table class="w-full border-collapse text-sm">
        <thead>
          <tr class="border-b border-gray-100 bg-gray-50">
            <th class="py-2 px-4 text-left  text-xs font-bold uppercase tracking-wider text-gray-400">Name</th>
            <th class="py-2 px-4 text-left  text-xs font-bold uppercase tracking-wider text-gray-400">Description</th>
            <th class="py-2 px-4 text-center text-xs font-bold uppercase tracking-wider text-gray-400">Bulb&nbsp;Hours</th>
            <th class="py-2 px-4 text-center text-xs font-bold uppercase tracking-wider text-gray-400">Assets</th>
            <th class="py-2 px-4 text-right  text-xs font-bold uppercase tracking-wider text-gray-400">Actions</th>
          </tr>
        </thead>
        <tbody id="cat-tbody">
          <?php foreach ($categories as $c): ?>
            <?php $in_use = (int)$c['asset_count'] > 0; ?>
            <tr class="border-b border-gray-50 hover:bg-gray-50">
              <td class="py-2 px-4 text-gray-800 font-medium"><?= htmlspecialchars($c['category_name']) ?></td>
              <td class="py-2 px-4 text-gray-500 max-w-xs truncate">
                <?= $c['description'] ? htmlspecialchars($c['description']) : '<span class="vf-empty">—</span>' ?>
              </td>
              <td class="py-2 px-4 text-center">
                <?php if ((int)$c['has_bulb_hours'] === 1): ?>
                  <span class="asset-badge badge-active"><span class="bdot"></span>Yes</span>
                <?php else: ?>
                  <span class="text-gray-300 text-xs">No</span>
                <?php endif; ?>
              </td>
              <td class="py-2 px-4 text-center">
                <?php if ($in_use): ?>
                  <a href="index.php?category_id=<?= (int)$c['category_id'] ?>"
                     class="asset-tag hover:underline"
                     title="View assets in this category"><?= (int)$c['asset_count'] ?></a>
                <?php else: ?>
                  <span class="text-gray-300">0</span>
                <?php endif; ?>
              </td>
              <td class="py-2 px-4 text-right">
                <div class="inline-flex items-center gap-2">
                  <a href="categories.php?edit=<?= $c['category_id'] ?>"
                     class="text-xs font-semibold text-olfu-green hover:underline">Edit</a>
                  <?php if ($in_use): ?>
                    <span class="text-xs text-gray-300" title="Cannot delete — this category is in use by <?= (int)$c['asset_count'] ?> asset(s).">Delete</span>
                  <?php else: ?>
                    <form method="POST" action="category_save.php" class="inline"
                          onsubmit="return confirm('Delete this category? This cannot be undone.');">
                      <input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf) ?>">
                      <input type="hidden" name="action"      value="delete">
                      <input type="hidden" name="category_id" value="<?= $c['category_id'] ?>">
                      <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Delete</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p id="cat-no-match" class="hidden p-4 text-sm text-gray-400 italic text-center">No categories match your search.</p>
    <?php else: ?>
      <p class="p-6 text-sm text-gray-400 italic">No categories yet — add your first one using the form on the right.</p>
    <?php endif; ?>
  </div>

  <!-- RIGHT: add / edit form -->
  <div class="bg-white rounded-xl shadow-sm border <?= $is_editing ? 'border-amber-200 ring-1 ring-amber-100' : 'border-gray-100' ?> overflow-hidden">
    <!-- Mode header strip -->
    <div class="px-4 py-3 border-b <?= $is_editing ? 'border-amber-100 bg-amber-50/60' : 'border-gray-100' ?>">
      <h3 class="text-sm font-bold flex items-center gap-2 <?= $is_editing ? 'text-amber-800' : 'text-gray-900' ?>">
        <span class="block w-0.5 h-4 rounded <?= $is_editing ? 'bg-amber-500' : 'bg-olfu-green' ?>"></span>
        <?php if ($is_editing): ?>
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
          Edit Category
          <span class="ml-auto text-[10px] font-bold uppercase tracking-wider text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">Editing</span>
        <?php else: ?>
          <svg class="w-4 h-4 text-olfu-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
          </svg>
          Add Category
        <?php endif; ?>
      </h3>
    </div>

    <?php if ($is_editing): ?>
      <div class="mx-4 mt-3 mb-1 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800 leading-snug">
        <div class="font-semibold uppercase tracking-wider text-[10px] text-amber-700 mb-0.5">Editing existing category</div>
        <div><?= htmlspecialchars($edit_cat['category_name']) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="category_save.php" class="p-4 flex flex-col gap-3">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action"     value="<?= $is_editing ? 'update' : 'create' ?>">
      <?php if ($is_editing): ?>
        <input type="hidden" name="category_id" value="<?= $edit_id ?>">
      <?php endif; ?>

      <div>
        <label class="flbl" for="category_name">Name <span class="text-red-500">*</span></label>
        <input type="text" id="category_name" name="category_name"
               value="<?= htmlspecialchars($fv('category_name')) ?>"
               class="fin <?= !empty($form_errors['category_name']) ? 'fin-err' : '' ?>"
               placeholder="e.g. Projector" maxlength="100">
        <?php if (!empty($form_errors['category_name'])): ?>
          <p class="ferr-msg"><?= htmlspecialchars($form_errors['category_name']) ?></p>
        <?php else: ?>
          <p class="fhint">Must be unique.</p>
        <?php endif; ?>
      </div>

      <div>
        <label class="flbl" for="description">Description</label>
        <input type="text" id="description" name="description"
               value="<?= htmlspecialchars($fv('description')) ?>"
               class="fin" placeholder="Optional" maxlength="255">
      </div>

      <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
        <input type="checkbox" id="has_bulb_hours" name="has_bulb_hours" value="1"
               <?= $bulb_checked ? 'checked' : '' ?>
               class="mt-0.5 h-4 w-4 rounded border-gray-300 text-olfu-green focus:ring-olfu-green">
        <span>
          <span class="font-semibold">Track bulb hours</span>
          <span class="block text-xs text-gray-400">Enable for projectors and other lamp-based equipment.</span>
        </span>
      </label>

      <div class="flex flex-col gap-2 pt-2">
        <?php if ($is_editing): ?>
          <button type="submit"
                  class="w-full flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold text-sm py-2.5 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            Save Changes
          </button>
          <a href="categories.php"
             class="w-full flex items-center justify-center text-sm font-medium text-gray-500 hover:text-gray-800 py-2 rounded-lg hover:bg-gray-50 transition-colors">
            Cancel edit
          </a>
        <?php else: ?>
          <button type="submit"
                  class="w-full flex items-center justify-center gap-2 bg-olfu-green hover:bg-olfu-green-md text-white font-semibold text-sm py-2.5 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
            </svg>
            Add Category
          </button>
        <?php endif; ?>
      </div>
    </form>
  </div>

</div>

<script>
function filterCategories() {
  const q     = (document.getElementById('cat-search')?.value ?? '').trim().toLowerCase();
  const rows  = document.querySelectorAll('#cat-tbody tr');
  let visible = 0;
  rows.forEach(row => {
    const match = !q || row.textContent.toLowerCase().includes(q);
    row.style.display = match ? '' : 'none';
    if (match) visible++;
  });
  const nm = document.getElementById('cat-no-match');
  if (nm) nm.classList.toggle('hidden', visible > 0);
  const cnt = document.getElementById('cat-count');
  if (cnt) cnt.textContent = q ? `(${visible} of <?= count($categories) ?>)` : '(<?= count($categories) ?>)';
}
</script>
