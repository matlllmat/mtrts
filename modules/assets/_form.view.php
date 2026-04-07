<!-- Back row -->
<div class="flex items-center gap-2 mb-4">
  <a href="<?= $back_url ?>"
     class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
    </svg>
    <?= $is_edit ? 'Back to Asset' : 'Back to Asset Registry' ?>
  </a>
  <span class="text-gray-300">/</span>
  <span class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($page_heading) ?></span>
</div>

<?php if (!empty($errors)): ?>
<div class="asset-banner banner-warn mb-4">
  <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
  </svg>
  <span>Please fix the errors below before saving.</span>
</div>
<?php endif; ?>

<form method="POST" action="save.php" novalidate>
  <input type="hidden" name="asset_id"   value="<?= $edit_id ?>">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ??= bin2hex(random_bytes(16))) ?>">

  <div class="grid grid-cols-1 xl:grid-cols-[1fr_280px] gap-4 items-start">

    <!-- LEFT: main form -->
    <div class="flex flex-col gap-4">

      <!-- Core details -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
          <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
            <span class="block w-0.5 h-4 bg-olfu-green rounded"></span>
            Asset Details
          </h2>
        </div>
        <div class="p-6 grid grid-cols-2 gap-4">

          <!-- Asset Tag -->
          <div>
            <label class="flbl" for="asset_tag">Asset Tag <span class="text-red-500">*</span></label>
            <div class="flex gap-2">
              <input type="text" id="asset_tag" name="asset_tag"
                     value="<?= htmlspecialchars((string)$v('asset_tag')) ?>"
                     <?= $is_edit ? 'readonly class="fin fin-readonly flex-1"' : 'class="fin flex-1 ' . (!empty($errors['asset_tag']) ? 'fin-err' : '') . '"' ?>
                     placeholder="e.g. PRJ-4022-X" maxlength="50">
              <?php if (!$is_edit): ?>
              <button type="button" onclick="generateTag()"
                      class="px-3 py-2 text-xs font-semibold text-olfu-green border border-olfu-green rounded-lg hover:bg-green-50 whitespace-nowrap transition-colors">
                Auto-generate
              </button>
              <?php endif; ?>
            </div>
            <?php if (!empty($errors['asset_tag'])): ?>
              <p class="ferr-msg"><?= htmlspecialchars($errors['asset_tag']) ?></p>
            <?php else: ?>
              <p class="fhint">Must be unique across all assets.</p>
            <?php endif; ?>
          </div>

          <!-- Category -->
          <div>
            <label class="flbl" for="category_id">Category <span class="text-red-500">*</span></label>
            <select id="category_id" name="category_id" onchange="toggleBulbHours()"
                    class="fsel <?= !empty($errors['category_id']) ? 'fsel-err' : '' ?>">
              <option value="">Select category…</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['category_id'] ?>"
                        data-bulb="<?= $cat['has_bulb_hours'] ?>"
                        <?= (int)$v('category_id') === (int)$cat['category_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat['category_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['category_id'])): ?>
              <p class="ferr-msg"><?= htmlspecialchars($errors['category_id']) ?></p>
            <?php endif; ?>
          </div>

          <!-- Manufacturer -->
          <div>
            <label class="flbl" for="manufacturer">Manufacturer <span class="text-red-500">*</span></label>
            <input type="text" id="manufacturer" name="manufacturer"
                   value="<?= htmlspecialchars((string)$v('manufacturer')) ?>"
                   class="fin <?= !empty($errors['manufacturer']) ? 'fin-err' : '' ?>"
                   placeholder="e.g. Epson">
            <?php if (!empty($errors['manufacturer'])): ?>
              <p class="ferr-msg"><?= htmlspecialchars($errors['manufacturer']) ?></p>
            <?php endif; ?>
          </div>

          <!-- Model -->
          <div>
            <label class="flbl" for="model">Model <span class="text-red-500">*</span></label>
            <input type="text" id="model" name="model"
                   value="<?= htmlspecialchars((string)$v('model')) ?>"
                   class="fin <?= !empty($errors['model']) ? 'fin-err' : '' ?>"
                   placeholder="e.g. EB-2250U">
            <?php if (!empty($errors['model'])): ?>
              <p class="ferr-msg"><?= htmlspecialchars($errors['model']) ?></p>
            <?php endif; ?>
          </div>

          <!-- Serial Number -->
          <div>
            <label class="flbl" for="serial_number">Serial Number</label>
            <input type="text" id="serial_number" name="serial_number"
                   value="<?= htmlspecialchars((string)$v('serial_number')) ?>"
                   class="fin <?= !empty($errors['serial_number']) ? 'fin-err' : '' ?>"
                   placeholder="e.g. X8A72-00341">
            <?php if (!empty($errors['serial_number'])): ?>
              <p class="ferr-msg"><?= htmlspecialchars($errors['serial_number']) ?></p>
            <?php else: ?>
              <p class="fhint">Must be unique per manufacturer.</p>
            <?php endif; ?>
          </div>

          <!-- Status -->
          <div>
            <label class="flbl" for="status">Status <span class="text-red-500">*</span></label>
            <select id="status" name="status" class="fsel">
              <?php foreach (['active' => 'Active', 'spare' => 'Spare', 'retired' => 'Retired'] as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= $v('status', 'active') === $val ? 'selected' : '' ?>>
                  <?= $lbl ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Install Date -->
          <div>
            <label class="flbl" for="install_date">Install Date <span class="text-red-500">*</span></label>
            <input type="date" id="install_date" name="install_date"
                   value="<?= htmlspecialchars((string)$v('install_date')) ?>"
                   max="<?= date('Y-m-d') ?>"
                   class="fin <?= !empty($errors['install_date']) ? 'fin-err' : '' ?>">
            <?php if (!empty($errors['install_date'])): ?>
              <p class="ferr-msg"><?= htmlspecialchars($errors['install_date']) ?></p>
            <?php endif; ?>
          </div>

          <!-- Firmware Version -->
          <div>
            <label class="flbl" for="firmware_version">Firmware Version</label>
            <input type="text" id="firmware_version" name="firmware_version"
                   value="<?= htmlspecialchars((string)$v('firmware_version')) ?>"
                   class="fin" placeholder="e.g. 1.04.00">
          </div>

          <!-- Bulb Hours (shown conditionally) -->
          <div id="bulb-row" class="<?= !$is_edit || !$asset['has_bulb_hours'] ? 'hidden' : '' ?>">
            <label class="flbl" for="bulb_hours">Bulb Hours</label>
            <input type="number" id="bulb_hours" name="bulb_hours" min="0"
                   value="<?= htmlspecialchars((string)$v('bulb_hours')) ?>"
                   class="fin" placeholder="e.g. 1248">
          </div>

          <!-- Network Info -->
          <div>
            <label class="flbl" for="network_info">Network Info</label>
            <input type="text" id="network_info" name="network_info"
                   value="<?= htmlspecialchars((string)$v('network_info')) ?>"
                   class="fin" placeholder="IP / MAC address (optional)">
          </div>

        </div>
      </div>

      <!-- Location -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
          <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
            <span class="block w-0.5 h-4 bg-olfu-green rounded"></span>
            Location
          </h2>
        </div>
        <div class="p-6 grid grid-cols-3 gap-4">
          <div>
            <label class="flbl" for="building-sel">Building</label>
            <select id="building-sel" onchange="populateFloors()" class="fsel">
              <option value="">Select building…</option>
              <?php foreach (array_keys($loc_data) as $b): ?>
                <option value="<?= htmlspecialchars($b) ?>" <?= $current_building === $b ? 'selected' : '' ?>>
                  <?= htmlspecialchars($b) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="flbl" for="floor-sel">Floor</label>
            <select id="floor-sel" onchange="populateRooms()" class="fsel">
              <option value="">Select floor…</option>
            </select>
          </div>
          <div>
            <label class="flbl" for="location_id">Room <span class="text-red-500">*</span></label>
            <select id="location_id" name="location_id"
                    class="fsel <?= !empty($errors['location_id']) ? 'fsel-err' : '' ?>">
              <option value="">Select room…</option>
            </select>
            <?php if (!empty($errors['location_id'])): ?>
              <p class="ferr-msg"><?= htmlspecialchars($errors['location_id']) ?></p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Warranty -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
          <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
            <span class="block w-0.5 h-4 bg-olfu-green rounded"></span>
            Warranty & Contract
          </h2>
        </div>
        <div class="p-6 grid grid-cols-2 gap-4">
          <div>
            <label class="flbl" for="warranty_start">Warranty Start</label>
            <input type="date" id="warranty_start" name="warranty_start"
                   value="<?= htmlspecialchars((string)$wv('warranty_start')) ?>"
                   class="fin <?= !empty($errors['warranty_start']) ? 'fin-err' : '' ?>">
            <?php if (!empty($errors['warranty_start'])): ?>
              <p class="ferr-msg"><?= htmlspecialchars($errors['warranty_start']) ?></p>
            <?php endif; ?>
          </div>
          <div>
            <label class="flbl" for="warranty_end">Warranty End</label>
            <input type="date" id="warranty_end" name="warranty_end"
                   value="<?= htmlspecialchars((string)$wv('warranty_end')) ?>"
                   class="fin <?= !empty($errors['warranty_end']) ? 'fin-err' : '' ?>">
            <?php if (!empty($errors['warranty_end'])): ?>
              <p class="ferr-msg"><?= htmlspecialchars($errors['warranty_end']) ?></p>
            <?php endif; ?>
          </div>
          <div>
            <label class="flbl" for="coverage_type">Coverage Type</label>
            <select id="coverage_type" name="coverage_type" class="fsel">
              <?php foreach (['parts_and_labor' => 'Parts & Labor', 'parts' => 'Parts Only', 'labor' => 'Labor Only', 'onsite' => 'On-site'] as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= $wv('coverage_type') === $val ? 'selected' : '' ?>>
                  <?= $lbl ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="flbl" for="vendor_name">Vendor</label>
            <input type="text" id="vendor_name" name="vendor_name"
                   value="<?= htmlspecialchars((string)$wv('vendor_name')) ?>"
                   class="fin" placeholder="e.g. Epson Philippines">
          </div>
          <div class="col-span-2">
            <label class="flbl" for="contract_reference">Contract Reference</label>
            <input type="text" id="contract_reference" name="contract_reference"
                   value="<?= htmlspecialchars((string)$wv('contract_reference')) ?>"
                   class="fin" placeholder="e.g. EP-2022-1104">
          </div>
        </div>
      </div>

    </div><!-- /left column -->

    <!-- RIGHT: ownership + actions -->
    <div class="flex flex-col gap-4">

      <!-- Ownership card -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
          <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
            <span class="block w-0.5 h-4 bg-olfu-green rounded"></span>
            Ownership
          </h2>
        </div>
        <div class="p-4 flex flex-col gap-4">
          <div>
            <label class="flbl" for="owner_id">Owner</label>
            <select id="owner_id" name="owner_id" class="fsel">
              <option value="">— None —</option>
              <?php foreach ($owners as $o): ?>
                <option value="<?= $o['user_id'] ?>" <?= (int)$v('owner_id') === (int)$o['user_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($o['full_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="flbl" for="department_id">Cost Center</label>
            <select id="department_id" name="department_id" class="fsel">
              <option value="">— Not assigned —</option>
              <?php foreach ($departments as $dept): ?>
                <option value="<?= $dept['department_id'] ?>"
                  <?= (int)$v('department_id') === (int)$dept['department_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($dept['department_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="flbl" for="parent_asset_id">Parent Asset</label>
            <select id="parent_asset_id" name="parent_asset_id" class="fsel">
              <option value="">— None (top-level) —</option>
              <?php foreach ($parents as $p): ?>
                <option value="<?= $p['asset_id'] ?>" <?= (int)$v('parent_asset_id') === (int)$p['asset_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($p['asset_tag'] . ' — ' . $p['manufacturer'] . ' ' . $p['model']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <p class="fhint">Leave empty for top-level assets.</p>
          </div>
        </div>
      </div>

      <!-- Actions card -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 flex flex-col gap-2">
          <button type="submit"
                  class="w-full flex items-center justify-center gap-2 bg-olfu-green hover:bg-olfu-green-md text-white font-semibold text-sm py-2.5 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <?= $is_edit ? 'Save Changes' : 'Save Asset' ?>
          </button>
          <a href="<?= $back_url ?>"
             class="w-full flex items-center justify-center text-sm font-medium text-gray-500 hover:text-gray-800 py-2 rounded-lg hover:bg-gray-50 transition-colors">
            Cancel
          </a>
        </div>
        <?php if ($is_edit): ?>
        <div class="px-4 pb-4">
          <div class="border-t border-gray-100 pt-3">
            <p class="text-xs text-gray-400 mb-2">Danger Zone</p>
            <?php if (has_open_tickets($pdo, $edit_id)): ?>
              <div class="text-xs text-gray-400 bg-gray-50 rounded-lg px-3 py-2">
                Cannot retire — asset has open tickets.
              </div>
            <?php else: ?>
              <button type="button"
                      onclick="if(confirm('Retire this asset? This cannot be undone.')) document.getElementById('retire-form').submit()"
                      class="w-full text-sm font-semibold text-red-600 border border-red-200 hover:bg-red-50 py-2 rounded-lg transition-colors">
                Retire Asset
              </button>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

    </div><!-- /right -->

  </div><!-- /grid -->
</form>

<?php if ($is_edit): ?>
<form id="retire-form" method="POST" action="save.php" class="hidden">
  <input type="hidden" name="asset_id"   value="<?= $edit_id ?>">
  <input type="hidden" name="retire"     value="1">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
</form>
<?php endif; ?>

<script>
const locData = <?= json_encode($loc_data) ?>;
const initLoc = {
  building: <?= json_encode($current_building) ?>,
  floor:    <?= json_encode($current_floor) ?>,
  id:       <?= $current_loc_id ?>
};

function populateFloors(resetBelow = true) {
  const b    = document.getElementById('building-sel').value;
  const fsel = document.getElementById('floor-sel');
  fsel.innerHTML = '<option value="">Select floor…</option>';
  if (locData[b]) {
    Object.keys(locData[b]).forEach(f => {
      const opt = document.createElement('option');
      opt.value = f; opt.textContent = f;
      fsel.appendChild(opt);
    });
  }
  if (resetBelow) populateRooms(true);
}

function populateRooms(reset = false) {
  const b    = document.getElementById('building-sel').value;
  const f    = document.getElementById('floor-sel').value;
  const rsel = document.getElementById('location_id');
  rsel.innerHTML = '<option value="">Select room…</option>';
  if (locData[b] && locData[b][f]) {
    locData[b][f].forEach(r => {
      const opt = document.createElement('option');
      opt.value = r.id; opt.textContent = r.room;
      if (!reset && r.id === initLoc.id) opt.selected = true;
      rsel.appendChild(opt);
    });
  }
}

function toggleBulbHours() {
  const sel     = document.getElementById('category_id');
  const opt     = sel.options[sel.selectedIndex];
  const hasBulb = opt && opt.dataset.bulb === '1';
  document.getElementById('bulb-row').classList.toggle('hidden', !hasBulb);
}

function generateTag() {
  const cat    = document.getElementById('category_id');
  const catTxt = cat.options[cat.selectedIndex]?.text ?? '';
  const prefix = catTxt.substring(0, 3).toUpperCase().replace(/[^A-Z]/g, 'X');
  const num    = String(Math.floor(1000 + Math.random() * 8999));
  const suffix = String.fromCharCode(65 + Math.floor(Math.random() * 26));
  document.getElementById('asset_tag').value = (prefix || 'AST') + '-' + num + '-' + suffix;
}

document.addEventListener('DOMContentLoaded', () => {
  if (initLoc.building) {
    document.getElementById('building-sel').value = initLoc.building;
    populateFloors(false);
    document.getElementById('floor-sel').value = initLoc.floor;
    populateRooms(false);
  }
  toggleBulbHours();
});
</script>
