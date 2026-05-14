<?php
/**
 * @var array        $locations
 * @var array        $form_data
 * @var array        $form_errors
 * @var string       $flash_error
 * @var string       $flash_ok
 * @var int          $edit_id
 * @var array|false  $edit_loc
 */

$bld_floor_map = $bld_floor_map ?? [];
$is_editing = (bool) $edit_loc;
$fv = function (string $k, string $default = '') use ($form_data, $edit_loc) {
    if (array_key_exists($k, $form_data)) return (string) $form_data[$k];
    if ($edit_loc && array_key_exists($k, $edit_loc)) return (string) $edit_loc[$k];
    return $default;
};

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
  <span class="text-sm font-semibold text-gray-700">Manage Locations</span>
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
    <h2 class="text-xl font-bold text-gray-900 tracking-tight">Manage Locations</h2>
    <p class="text-sm text-gray-400 mt-0.5">
      Add, rename, or remove rooms. Locations in use by assets cannot be deleted.
    </p>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-4 items-start">

  <!-- LEFT: locations table -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
        <span class="block w-0.5 h-4 bg-olfu-green rounded"></span>
        All Locations
        <span class="text-xs font-normal text-gray-400" id="loc-count">(<?= count($locations) ?>)</span>
      </h3>
    </div>
    <?php if ($locations): ?>
      <div class="px-4 py-3 border-b border-gray-100">
        <div class="relative">
          <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
          </svg>
          <input type="text" id="loc-search" placeholder="Filter by building, floor, or room…"
                 class="fin pr-8 text-sm" oninput="filterLocations()">
        </div>
      </div>
      <table class="w-full border-collapse text-sm">
        <thead>
          <tr class="border-b border-gray-100 bg-gray-50">
            <th class="py-2 px-4 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Building</th>
            <th class="py-2 px-4 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Floor</th>
            <th class="py-2 px-4 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Room</th>
            <th class="py-2 px-4 text-center text-xs font-bold uppercase tracking-wider text-gray-400">Assets</th>
            <th class="py-2 px-4 text-right text-xs font-bold uppercase tracking-wider text-gray-400">Actions</th>
          </tr>
        </thead>
        <tbody id="loc-tbody">
          <?php foreach ($locations as $loc): ?>
            <?php $in_use = (int)$loc['asset_count'] > 0; ?>
            <tr class="border-b border-gray-50 hover:bg-gray-50">
              <td class="py-2 px-4 text-gray-700"><?= htmlspecialchars($loc['building']) ?></td>
              <td class="py-2 px-4 text-gray-700"><?= htmlspecialchars($loc['floor']) ?></td>
              <td class="py-2 px-4 text-gray-700"><?= htmlspecialchars($loc['room']) ?></td>
              <td class="py-2 px-4 text-center">
                <?php if ($in_use): ?>
                  <a href="index.php?building=<?= urlencode($loc['building']) ?>&floor=<?= urlencode($loc['floor']) ?>"
                     class="asset-tag hover:underline"
                     title="View assets at this location"><?= (int)$loc['asset_count'] ?></a>
                <?php else: ?>
                  <span class="text-gray-300">0</span>
                <?php endif; ?>
              </td>
              <td class="py-2 px-4 text-right">
                <div class="inline-flex items-center gap-2">
                  <a href="locations.php?edit=<?= $loc['location_id'] ?>"
                     class="text-xs font-semibold text-olfu-green hover:underline">Edit</a>
                  <?php if ($in_use): ?>
                    <span class="text-xs text-gray-300" title="Cannot delete — this location is in use by <?= (int)$loc['asset_count'] ?> asset(s).">Delete</span>
                  <?php else: ?>
                    <form method="POST" action="location_save.php" class="inline"
                          onsubmit="return confirm('Delete this location? This cannot be undone.');">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                      <input type="hidden" name="action"     value="delete">
                      <input type="hidden" name="location_id" value="<?= $loc['location_id'] ?>">
                      <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">Delete</button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p id="loc-no-match" class="hidden p-4 text-sm text-gray-400 italic text-center">No locations match your search.</p>
    <?php else: ?>
      <p class="p-6 text-sm text-gray-400 italic">No locations yet — add your first one using the form on the right.</p>
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
          Edit Location
          <span class="ml-auto text-[10px] font-bold uppercase tracking-wider text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">Editing</span>
        <?php else: ?>
          <svg class="w-4 h-4 text-olfu-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
          </svg>
          Add Location
        <?php endif; ?>
      </h3>
    </div>

    <?php if ($is_editing): ?>
      <!-- Edit-mode banner showing what's being changed -->
      <div class="mx-4 mt-3 mb-1 px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800 leading-snug">
        <div class="font-semibold uppercase tracking-wider text-[10px] text-amber-700 mb-0.5">Editing existing location</div>
        <div>
          <?= htmlspecialchars($edit_loc['building']) ?>
          <span class="text-amber-400">/</span>
          <?= htmlspecialchars($edit_loc['floor']) ?>
          <span class="text-amber-400">/</span>
          <?= htmlspecialchars($edit_loc['room']) ?>
        </div>
      </div>
    <?php endif; ?>

    <form method="POST" action="location_save.php" class="p-4 flex flex-col gap-3">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="action"     value="<?= $is_editing ? 'update' : 'create' ?>">
      <?php if ($is_editing): ?>
        <input type="hidden" name="location_id" value="<?= $edit_id ?>">
      <?php endif; ?>

      <div>
        <label class="flbl" for="building">Building <span class="text-red-500">*</span></label>
        <input type="text" id="building" name="building"
               value="<?= htmlspecialchars($fv('building')) ?>"
               class="fin <?= !empty($form_errors['building']) ? 'fin-err' : '' ?>"
               placeholder="e.g. CAS Building" maxlength="100"
               list="bld-suggest" autocomplete="off"
               oninput="updateFloorSuggestions()">
        <datalist id="bld-suggest">
          <?php foreach (array_keys($bld_floor_map) as $bld_name): ?>
            <option value="<?= htmlspecialchars($bld_name) ?>"></option>
          <?php endforeach; ?>
        </datalist>
        <?php if (!empty($form_errors['building'])): ?>
          <p class="ferr-msg"><?= htmlspecialchars($form_errors['building']) ?></p>
        <?php else: ?>
          <p class="fhint">Pick an existing building or type a new one.</p>
        <?php endif; ?>
      </div>

      <div>
        <label class="flbl" for="floor">Floor <span class="text-red-500">*</span></label>
        <input type="text" id="floor" name="floor"
               value="<?= htmlspecialchars($fv('floor')) ?>"
               class="fin <?= !empty($form_errors['floor']) ? 'fin-err' : '' ?>"
               placeholder="e.g. 1st Floor" maxlength="50"
               list="floor-suggest" autocomplete="off">
        <datalist id="floor-suggest"></datalist>
        <?php if (!empty($form_errors['floor'])): ?>
          <p class="ferr-msg"><?= htmlspecialchars($form_errors['floor']) ?></p>
        <?php else: ?>
          <p class="fhint">Suggestions update to match the chosen building.</p>
        <?php endif; ?>
      </div>

      <div>
        <label class="flbl" for="room">Room <span class="text-red-500">*</span></label>
        <input type="text" id="room" name="room"
               value="<?= htmlspecialchars($fv('room')) ?>"
               class="fin <?= !empty($form_errors['room']) ? 'fin-err' : '' ?>"
               placeholder="e.g. Room 101" maxlength="100">
        <?php if (!empty($form_errors['room'])): ?>
          <p class="ferr-msg"><?= htmlspecialchars($form_errors['room']) ?></p>
        <?php else: ?>
          <p class="fhint">Building + Floor + Room must be unique.</p>
        <?php endif; ?>
      </div>

      <div class="flex flex-col gap-2 pt-2">
        <?php if ($is_editing): ?>
          <button type="submit"
                  class="w-full flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold text-sm py-2.5 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            Save Changes
          </button>
          <a href="locations.php"
             class="w-full flex items-center justify-center text-sm font-medium text-gray-500 hover:text-gray-800 py-2 rounded-lg hover:bg-gray-50 transition-colors">
            Cancel edit
          </a>
        <?php else: ?>
          <button type="submit"
                  class="w-full flex items-center justify-center gap-2 bg-olfu-green hover:bg-olfu-green-md text-white font-semibold text-sm py-2.5 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
            </svg>
            Add Location
          </button>
        <?php endif; ?>
      </div>
    </form>
  </div>

</div>

<script>
const bldFloorMap = <?= json_encode($bld_floor_map, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

function filterLocations() {
  const q     = (document.getElementById('loc-search')?.value ?? '').trim().toLowerCase();
  const rows  = document.querySelectorAll('#loc-tbody tr');
  let visible = 0;
  rows.forEach(row => {
    const match = !q || row.textContent.toLowerCase().includes(q);
    row.style.display = match ? '' : 'none';
    if (match) visible++;
  });
  const nm = document.getElementById('loc-no-match');
  if (nm) nm.classList.toggle('hidden', visible > 0);
  const cnt = document.getElementById('loc-count');
  if (cnt) cnt.textContent = q ? `(${visible} of <?= count($locations) ?>)` : '(<?= count($locations) ?>)';
}

function updateFloorSuggestions() {
  const bld   = document.getElementById('building').value.trim();
  const list  = document.getElementById('floor-suggest');
  list.innerHTML = '';
  const floors = bldFloorMap[bld] || [];
  floors.forEach(f => {
    const opt = document.createElement('option');
    opt.value = f;
    list.appendChild(opt);
  });
}

// Populate suggestions on initial load (e.g. when editing or re-flashing the form)
document.addEventListener('DOMContentLoaded', updateFloorSuggestions);
</script>
