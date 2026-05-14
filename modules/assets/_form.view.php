<?php
/**
 * @var callable   $v
 * @var callable   $wv
 * @var bool       $is_edit
 * @var int        $edit_id
 * @var array      $errors
 * @var array      $categories
 * @var array      $owners
 * @var array      $parents
 * @var array      $departments
 * @var array      $loc_data
 * @var int        $current_loc_id
 * @var string     $current_building
 * @var string     $current_floor
 * @var string     $page_heading
 * @var string     $back_url
 * @var array      $warranty_docs
 * @var array|null $asset
 */
?>
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
            <select id="status" name="status"
                    class="fsel <?= !empty($errors['status']) ? 'fsel-err' : '' ?>">
              <?php foreach (['active' => 'Active', 'spare' => 'Spare', 'retired' => 'Retired'] as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= $v('status', 'active') === $val ? 'selected' : '' ?>>
                  <?= $lbl ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['status'])): ?>
              <p class="ferr-msg"><?= htmlspecialchars($errors['status']) ?></p>
            <?php endif; ?>
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
          <div id="bulb-row" class="<?= !$is_edit || !($asset['has_bulb_hours'] ?? false) ? 'hidden' : '' ?>">
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

          <!-- Warranty & Contract Documents -->
          <div class="col-span-2 pt-4 border-t border-gray-100">
            <p class="flbl mb-1">Attached Documents</p>
            <p class="text-xs text-gray-500 italic">
              Warranty, contract, and other files are managed in the
              <strong>Documents</strong> section below.
            </p>
          </div>
        </div>
      </div>

      <!-- Documents card (multi-type) -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
            <span class="block w-0.5 h-4 bg-olfu-green rounded"></span>
            Documents
            <span class="text-xs font-normal text-gray-400">— Manual, Wiring, Config Backup, etc.</span>
          </h2>
        </div>
        <div class="p-6">

          <?php if (!$is_edit): ?>
            <!-- CREATE mode: stage files in JS, uploaded after asset is saved -->
            <p class="text-xs text-gray-500 italic mb-3">
              Attach any related files now — they'll be uploaded automatically once the asset is saved.
              You can also add more later from the asset's view page.
            </p>
            <div id="cd-staged" class="flex flex-col gap-2 mb-3">
              <p id="cd-empty" class="text-sm text-gray-400 italic">No documents queued yet.</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
              <select id="cd-type" class="fsel" style="width:auto">
                <option value="">General</option>
                <option value="manual">Manual</option>
                <option value="warranty">Warranty</option>
                <option value="contract">Contract</option>
                <option value="wiring_diagram">Wiring Diagram</option>
                <option value="config_backup">Config Backup</option>
              </select>
              <button type="button" onclick="document.getElementById('cd-file-input').click()"
                      class="text-sm border border-dashed border-gray-300 rounded-lg px-3 py-1.5 text-gray-500 hover:border-olfu-green hover:text-olfu-green transition-colors">
                + Add to queue
              </button>
              <input type="file" id="cd-file-input" class="hidden"
                     accept=".pdf,.jpg,.jpeg,.png,.dwg,.zip"
                     onchange="cdFileSelected(this.files)">
              <span id="cd-msg" class="text-xs text-gray-400"></span>
            </div>
            <p class="text-xs text-gray-400 mt-2">PDF, JPG, PNG, DWG, ZIP — Max 50 MB per file.</p>

          <?php else: ?>
            <!-- EDIT mode: live multi-type upload + per-row delete -->
            <div id="ed-list" class="flex flex-col gap-2 mb-3">
              <?php
              $all_docs = get_asset_documents($pdo, $edit_id);
              if ($all_docs):
                foreach ($all_docs as $ed):
                  $ed_ic_colors = [
                    'pdf'  => 'bg-red-100 text-red-600',
                    'jpg'  => 'bg-blue-100 text-blue-600',
                    'jpeg' => 'bg-blue-100 text-blue-600',
                    'png'  => 'bg-blue-100 text-blue-600',
                    'dwg'  => 'bg-purple-100 text-purple-600',
                    'zip'  => 'bg-yellow-100 text-yellow-600',
                  ];
                  $ed_ic = $ed_ic_colors[$ed['file_type']] ?? 'bg-gray-100 text-gray-600';
                  $ed_sz = $ed['file_size_kb'] >= 1024
                      ? round($ed['file_size_kb'] / 1024, 1) . ' MB'
                      : number_format($ed['file_size_kb']) . ' KB';
                  $ed_type_labels = [
                    'warranty'       => ['Warranty',  'bg-blue-100 text-blue-700'],
                    'contract'       => ['Contract',  'bg-purple-100 text-purple-700'],
                    'manual'         => ['Manual',    'bg-gray-100 text-gray-600'],
                    'wiring_diagram' => ['Wiring',    'bg-yellow-100 text-yellow-700'],
                    'config_backup'  => ['Config',    'bg-orange-100 text-orange-700'],
                  ];
                  $ed_type = !empty($ed['document_type']) ? ($ed_type_labels[$ed['document_type']] ?? null) : null;
              ?>
                <div class="doc-row" data-doc-id="<?= $ed['document_id'] ?>">
                  <div class="doc-ic <?= $ed_ic ?>"><?= strtoupper(htmlspecialchars($ed['file_type'])) ?></div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1 group">
                      <span class="doc-name text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($ed['document_name']) ?></span>
                      <button type="button" onclick="edRename(<?= $ed['document_id'] ?>, this)"
                              class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-gray-700 transition-all flex-shrink-0" title="Rename">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                      </button>
                    </div>
                    <div class="doc-meta flex items-center gap-2">
                      <?php if ($ed_type): ?>
                        <span class="text-xs font-semibold <?= $ed_type[1] ?> px-1.5 py-0 rounded-full"><?= $ed_type[0] ?></span>
                      <?php endif; ?>
                      <?= (new DateTime($ed['uploaded_at']))->format('M j, Y') ?>
                      · <?= $ed_sz ?>
                    </div>
                  </div>
                  <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">v<?= $ed['version'] ?></span>
                    <a href="doc_download.php?id=<?= $ed['document_id'] ?>"
                       class="text-olfu-green hover:text-olfu-green-md transition-colors" title="Download">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                      </svg>
                    </a>
                    <button type="button" onclick="edDeleteDoc(<?= $ed['document_id'] ?>, this)"
                            class="text-gray-400 hover:text-red-500 transition-colors" title="Delete">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                    </button>
                  </div>
                </div>
              <?php endforeach; else: ?>
                <p id="ed-empty" class="text-sm text-gray-400 italic">No documents attached yet.</p>
              <?php endif; ?>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
              <select id="ed-type" class="fsel" style="width:auto">
                <option value="">General</option>
                <option value="manual">Manual</option>
                <option value="warranty">Warranty</option>
                <option value="contract">Contract</option>
                <option value="wiring_diagram">Wiring Diagram</option>
                <option value="config_backup">Config Backup</option>
              </select>
              <button type="button" onclick="document.getElementById('ed-file-input').click()"
                      class="text-sm border border-dashed border-gray-300 rounded-lg px-3 py-1.5 text-gray-500 hover:border-olfu-green hover:text-olfu-green transition-colors">
                + Upload Document
              </button>
              <input type="file" id="ed-file-input" class="hidden"
                     accept=".pdf,.jpg,.jpeg,.png,.dwg,.zip"
                     onchange="edFileSelected(this.files)">
              <span id="ed-msg" class="text-xs hidden"></span>
            </div>
            <p class="text-xs text-gray-400 mt-2">PDF, JPG, PNG, DWG, ZIP — Max 50 MB per file.</p>
          <?php endif; ?>

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
            <label class="flbl" for="owner_search">Owner</label>
            <?php
              $current_owner_id   = (int)$v('owner_id');
              $current_owner_name = '';
              foreach ($owners as $o) {
                  if ((int)$o['user_id'] === $current_owner_id) {
                      $current_owner_name = $o['full_name'];
                      break;
                  }
              }
              // If the user typed a name that didn't resolve on the last submit, preserve it
              if (!$current_owner_name && !empty($old['owner_search'])) {
                  $current_owner_name = (string) $old['owner_search'];
              }
            ?>
            <input type="text" id="owner_search" name="owner_search"
                   value="<?= htmlspecialchars($current_owner_name) ?>"
                   class="fin <?= !empty($errors['owner_id']) ? 'fin-err' : '' ?>"
                   list="owner-suggest" autocomplete="off"
                   placeholder="Type to search…"
                   oninput="resolveOwner()">
            <input type="hidden" id="owner_id" name="owner_id" value="<?= $current_owner_id ?: '' ?>">
            <datalist id="owner-suggest">
              <?php foreach ($owners as $o): ?>
                <option value="<?= htmlspecialchars($o['full_name']) ?>" data-uid="<?= (int)$o['user_id'] ?>"></option>
              <?php endforeach; ?>
            </datalist>
            <?php if (!empty($errors['owner_id'])): ?>
              <p class="ferr-msg"><?= htmlspecialchars($errors['owner_id']) ?></p>
            <?php else: ?>
              <p class="fhint">Leave blank for no owner — typed names must match an active user.</p>
            <?php endif; ?>
          </div>
          <div>
            <label class="flbl" for="dept_search">Cost Center</label>
            <?php
              $current_dept_id   = (int)$v('department_id');
              $current_dept_name = '';
              foreach ($departments as $dept) {
                  if ((int)$dept['department_id'] === $current_dept_id) {
                      $current_dept_name = $dept['department_name'];
                      break;
                  }
              }
              if (!$current_dept_name && !empty($old['dept_search'])) {
                  $current_dept_name = (string) $old['dept_search'];
              }
            ?>
            <input type="text" id="dept_search" name="dept_search"
                   value="<?= htmlspecialchars($current_dept_name) ?>"
                   class="fin <?= !empty($errors['department_id']) ? 'fin-err' : '' ?>"
                   list="dept-suggest" autocomplete="off"
                   placeholder="Type to search…"
                   oninput="resolveDept()">
            <input type="hidden" id="department_id" name="department_id" value="<?= $current_dept_id ?: '' ?>">
            <datalist id="dept-suggest">
              <?php foreach ($departments as $dept): ?>
                <option value="<?= htmlspecialchars($dept['department_name']) ?>" data-did="<?= (int)$dept['department_id'] ?>"></option>
              <?php endforeach; ?>
            </datalist>
            <?php if (!empty($errors['department_id'])): ?>
              <p class="ferr-msg"><?= htmlspecialchars($errors['department_id']) ?></p>
            <?php else: ?>
              <p class="fhint">Leave blank if not assigned — typed names must match an existing cost center.</p>
            <?php endif; ?>
          </div>
          <div>
            <label class="flbl" for="parent_search">Parent Asset</label>
            <?php
              $current_parent_id   = (int)$v('parent_asset_id');
              $current_parent_name = '';
              foreach ($parents as $p) {
                  if ((int)$p['asset_id'] === $current_parent_id) {
                      $current_parent_name = $p['asset_tag'] . ' — ' . $p['manufacturer'] . ' ' . $p['model'];
                      break;
                  }
              }
              if (!$current_parent_name && !empty($old['parent_search'])) {
                  $current_parent_name = (string) $old['parent_search'];
              }
            ?>
            <input type="text" id="parent_search" name="parent_search"
                   value="<?= htmlspecialchars($current_parent_name) ?>"
                   class="fin <?= !empty($errors['parent_asset_id']) ? 'fin-err' : '' ?>"
                   list="parent-suggest" autocomplete="off"
                   placeholder="Type asset tag to search…"
                   oninput="resolveParent()">
            <input type="hidden" id="parent_asset_id" name="parent_asset_id" value="<?= $current_parent_id ?: '' ?>">
            <datalist id="parent-suggest">
              <?php foreach ($parents as $p): ?>
                <option value="<?= htmlspecialchars($p['asset_tag'] . ' — ' . $p['manufacturer'] . ' ' . $p['model']) ?>" data-pid="<?= (int)$p['asset_id'] ?>"></option>
              <?php endforeach; ?>
            </datalist>
            <?php if (!empty($errors['parent_asset_id'])): ?>
              <p class="ferr-msg"><?= htmlspecialchars($errors['parent_asset_id']) ?></p>
            <?php else: ?>
              <p class="fhint">Leave blank for top-level assets — typed tags must match an existing asset.</p>
            <?php endif; ?>
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
  const cat = document.getElementById('category_id');
  if (!cat.value) {
    // Visually flag the category select and focus it
    cat.classList.add('fsel-err');
    cat.focus();
    let hint = document.getElementById('tag-gen-hint');
    if (!hint) {
      hint = document.createElement('p');
      hint.id        = 'tag-gen-hint';
      hint.className = 'ferr-msg';
      hint.textContent = 'Select a category first — the tag prefix is derived from it.';
      document.getElementById('asset_tag').closest('div').appendChild(hint);
    }
    cat.addEventListener('change', () => {
      cat.classList.remove('fsel-err');
      document.getElementById('tag-gen-hint')?.remove();
    }, { once: true });
    return;
  }
  const catTxt = cat.options[cat.selectedIndex]?.text ?? '';
  const prefix = catTxt.substring(0, 3).toUpperCase().replace(/[^A-Z]/g, 'X');
  const num    = String(Math.floor(1000 + Math.random() * 8999));
  const suffix = String.fromCharCode(65 + Math.floor(Math.random() * 26));
  document.getElementById('asset_tag').value = (prefix || 'AST') + '-' + num + '-' + suffix;
}

// ── Create-time document staging ───────────────────────────────
// Files are kept in memory; on form submit we save the asset first,
// then upload each staged file against the new asset_id.
const cdStaged = [];        // [{file: File, type: string, name: string}]
const cdAccept = ['pdf','jpg','jpeg','png','dwg','zip'];
const cdMaxKB  = 51200;     // 50 MB

const _csrfCurrent = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;

function cdFileSelected(files) {
  if (!files.length) return;
  const file = files[0];
  const ext  = (file.name.split('.').pop() || '').toLowerCase();
  const msg  = document.getElementById('cd-msg');
  msg.classList.remove('text-red-500');
  msg.classList.add('text-gray-400');

  if (!cdAccept.includes(ext)) {
    msg.textContent = 'File type not allowed. Accepted: ' + cdAccept.join(', ') + '.';
    msg.classList.add('text-red-500');
    document.getElementById('cd-file-input').value = '';
    return;
  }
  if (file.size / 1024 > cdMaxKB) {
    msg.textContent = 'File exceeds 50 MB limit.';
    msg.classList.add('text-red-500');
    document.getElementById('cd-file-input').value = '';
    return;
  }

  cdStaged.push({
    file: file,
    type: document.getElementById('cd-type').value,
    name: file.name,
  });
  document.getElementById('cd-file-input').value = '';
  msg.textContent = '';
  cdRenderStaged();
}

function cdRemoveStaged(idx) {
  cdStaged.splice(idx, 1);
  cdRenderStaged();
}

function cdStartRename(idx, btn) {
  const wrap   = document.getElementById('cd-staged');
  const rows   = wrap.querySelectorAll(':scope > div');
  const row    = rows[idx];
  if (!row) return;
  const nameEl = row.querySelector('.cd-name');
  const current = cdStaged[idx].name;

  const input = document.createElement('input');
  input.type      = 'text';
  input.value     = current;
  input.className = 'text-sm text-gray-800 border border-olfu-green rounded px-1.5 py-0.5 flex-1 outline-none min-w-0';
  nameEl.replaceWith(input);
  btn.style.visibility = 'hidden';
  input.focus();
  input.select();

  const commit = () => {
    const v = input.value.trim();
    if (v) cdStaged[idx].name = v;
    cdRenderStaged();
  };
  input.addEventListener('blur', commit);
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter')  { e.preventDefault(); input.blur(); }
    if (e.key === 'Escape') { input.value = current; input.blur(); }
  });
}

function cdEscape(s) {
  const d = document.createElement('div'); d.textContent = s; return d.innerHTML;
}

function cdRenderStaged() {
  const wrap = document.getElementById('cd-staged');
  if (!wrap) return;
  if (cdStaged.length === 0) {
    wrap.innerHTML = '<p id="cd-empty" class="text-sm text-gray-400 italic">No documents queued yet.</p>';
    return;
  }
  const typeLabels = {
    warranty: 'Warranty', contract: 'Contract', manual: 'Manual',
    wiring_diagram: 'Wiring', config_backup: 'Config'
  };
  const typeColors = {
    warranty: 'bg-blue-100 text-blue-700',
    contract: 'bg-purple-100 text-purple-700',
    manual:   'bg-gray-100 text-gray-600',
    wiring_diagram: 'bg-yellow-100 text-yellow-700',
    config_backup:  'bg-orange-100 text-orange-700',
  };
  wrap.innerHTML = cdStaged.map((it, i) => {
    const sizeKB = Math.ceil(it.file.size / 1024);
    const sz = sizeKB >= 1024 ? (sizeKB / 1024).toFixed(1) + ' MB' : sizeKB.toLocaleString() + ' KB';
    const lbl = typeLabels[it.type] || 'General';
    const cls = typeColors[it.type] || 'bg-gray-100 text-gray-600';
    return `
      <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg border border-gray-200">
        <span class="text-xs font-semibold ${cls} px-2 py-0.5 rounded-full flex-shrink-0">${lbl}</span>
        <div class="flex-1 min-w-0 flex items-center gap-1 group">
          <span class="cd-name text-sm text-gray-700 truncate">${cdEscape(it.name)}</span>
          <button type="button" onclick="cdStartRename(${i}, this)"
                  class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-gray-600 transition-all flex-shrink-0" title="Rename">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
          </button>
        </div>
        <span class="text-xs text-gray-400 flex-shrink-0">${sz}</span>
        <button type="button" onclick="cdRemoveStaged(${i})"
                class="text-gray-400 hover:text-red-500 transition-colors" title="Remove from queue">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>`;
  }).join('');
}

// Intercept the main asset form submit only when files are staged.
document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('form[action="save.php"]');
  if (!form) return;

  form.addEventListener('submit', async function (e) {
    if (cdStaged.length === 0) return; // normal submit
    e.preventDefault();

    // Disable submit button to prevent double-clicks
    const submitBtn = form.querySelector('button[type="submit"]');
    const origLabel = submitBtn?.innerHTML;
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Saving asset…';
    }

    const fd = new FormData(form);
    let data;
    try {
      const r = await fetch(form.action, {
        method:  'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body:    fd,
      });
      data = await r.json();
    } catch (err) {
      alert('Network error while saving the asset. Please try again.');
      if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = origLabel; }
      return;
    }

    if (!data.success) {
      // Server returned validation errors — navigate to the redirect URL so
      // the normal form-reflash mechanism (session-stored errors) can render.
      window.location = data.redirect;
      return;
    }

    // Asset created. Upload each staged file one by one.
    if (submitBtn) submitBtn.innerHTML = `Uploading files (0/${cdStaged.length})…`;
    let uploaded = 0, failed = [];
    for (let i = 0; i < cdStaged.length; i++) {
      const it = cdStaged[i];
      const docFd = new FormData();
      docFd.append('csrf_token',    _csrfCurrent);
      docFd.append('asset_id',      data.asset_id);
      docFd.append('file',          it.file);
      docFd.append('document_name', it.name);
      docFd.append('document_type', it.type);
      try {
        const r = await fetch('doc_upload.php', { method: 'POST', body: docFd });
        const j = await r.json();
        if (j.success) uploaded++;
        else failed.push(`${it.name}: ${j.message || 'failed'}`);
      } catch {
        failed.push(`${it.name}: network error`);
      }
      if (submitBtn) submitBtn.innerHTML = `Uploading files (${i + 1}/${cdStaged.length})…`;
    }

    if (failed.length) {
      alert(`Asset saved, but ${failed.length} document(s) failed to upload:\n\n${failed.join('\n')}\n\nYou can upload them manually from the asset page.`);
    }
    window.location = data.redirect;
  });
});

// ── Edit-mode live document upload + delete ────────────────────
function edFileSelected(files) {
  if (!files.length) return;
  const file = files[0];
  const ext  = (file.name.split('.').pop() || '').toLowerCase();
  const msg  = document.getElementById('ed-msg');

  function showMsg(text, cls) {
    msg.textContent = text;
    msg.className   = 'text-xs ' + cls;
    msg.classList.remove('hidden');
  }

  const accept = ['pdf','jpg','jpeg','png','dwg','zip'];
  if (!accept.includes(ext)) {
    showMsg('File type not allowed.', 'text-red-500');
    document.getElementById('ed-file-input').value = '';
    return;
  }
  if (file.size / 1024 > 51200) {
    showMsg('File exceeds 50 MB limit.', 'text-red-500');
    document.getElementById('ed-file-input').value = '';
    return;
  }

  showMsg('Uploading…', 'text-gray-500');
  const fd = new FormData();
  fd.append('csrf_token',    _csrfCurrent);
  fd.append('asset_id',      <?= $is_edit ? (int)$edit_id : 0 ?>);
  fd.append('file',          file);
  fd.append('document_type', document.getElementById('ed-type').value);

  fetch('doc_upload.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      document.getElementById('ed-file-input').value = '';
      if (!d.success) {
        showMsg(d.message || 'Upload failed.', 'text-red-500');
        return;
      }
      showMsg('Uploaded successfully.', 'text-green-600');
      edAppendRow(d);
    })
    .catch(() => showMsg('Network error.', 'text-red-500'));
}

function edAppendRow(d) {
  const list  = document.getElementById('ed-list');
  document.getElementById('ed-empty')?.remove();

  const icColors = {
    pdf: 'bg-red-100 text-red-600', jpg: 'bg-blue-100 text-blue-600',
    jpeg:'bg-blue-100 text-blue-600', png:'bg-blue-100 text-blue-600',
    dwg: 'bg-purple-100 text-purple-600', zip:'bg-yellow-100 text-yellow-600',
  };
  const typeLabels = {
    warranty:       ['Warranty',  'bg-blue-100 text-blue-700'],
    contract:       ['Contract',  'bg-purple-100 text-purple-700'],
    manual:         ['Manual',    'bg-gray-100 text-gray-600'],
    wiring_diagram: ['Wiring',    'bg-yellow-100 text-yellow-700'],
    config_backup:  ['Config',    'bg-orange-100 text-orange-700'],
  };
  const ic = icColors[d.file_type] || 'bg-gray-100 text-gray-600';
  const sz = d.file_size_kb >= 1024
    ? (d.file_size_kb / 1024).toFixed(1) + ' MB'
    : d.file_size_kb.toLocaleString() + ' KB';
  const tInfo = typeLabels[d.document_type];
  const tBadge = tInfo
    ? `<span class="text-xs font-semibold ${tInfo[1]} px-1.5 py-0 rounded-full">${tInfo[0]}</span>`
    : '';

  const row = document.createElement('div');
  row.className     = 'doc-row';
  row.dataset.docId = d.document_id;
  row.innerHTML = `
    <div class="doc-ic ${ic}">${d.file_type.toUpperCase()}</div>
    <div class="flex-1 min-w-0">
      <div class="flex items-center gap-1 group">
        <span class="doc-name text-sm font-medium text-gray-800 truncate">${d.document_name}</span>
        <button type="button" onclick="edRename(${d.document_id}, this)"
                class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-gray-700 transition-all flex-shrink-0" title="Rename">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
        </button>
      </div>
      <div class="doc-meta" style="display:flex;align-items:center;gap:6px">${tBadge}${d.uploaded_at} · ${sz}</div>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0">
      <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">v${d.version}</span>
      <a href="doc_download.php?id=${d.document_id}"
         class="text-olfu-green hover:text-olfu-green-md transition-colors" title="Download">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
      </a>
      <button type="button" onclick="edDeleteDoc(${d.document_id}, this)"
              class="text-gray-400 hover:text-red-500 transition-colors" title="Delete">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
        </svg>
      </button>
    </div>`;
  list.prepend(row);
}

function edRename(docId, btn) {
  const row      = btn.closest('.doc-row');
  const nameSpan = row.querySelector('.doc-name');
  const current  = nameSpan.textContent.trim();

  const input = document.createElement('input');
  input.type      = 'text';
  input.value     = current;
  input.className = 'text-sm font-medium text-gray-800 border border-olfu-green rounded px-1.5 py-0.5 w-full outline-none';
  nameSpan.replaceWith(input);
  btn.classList.add('hidden');
  input.focus();
  input.select();

  const commit = () => {
    const newName = input.value.trim();
    if (!newName || newName === current) {
      input.replaceWith(nameSpan);
      btn.classList.remove('hidden');
      return;
    }
    const fd = new FormData();
    fd.append('csrf_token',    _csrfCurrent);
    fd.append('document_id',   docId);
    fd.append('document_name', newName);
    fetch('doc_rename.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.success) nameSpan.textContent = data.document_name;
        input.replaceWith(nameSpan);
        btn.classList.remove('hidden');
      });
  };
  input.addEventListener('blur', commit);
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter')  { e.preventDefault(); input.blur(); }
    if (e.key === 'Escape') { input.value = current; input.blur(); }
  });
}

function edDeleteDoc(docId, btn) {
  if (!confirm('Delete this document? This cannot be undone.')) return;
  btn.disabled = true;
  const fd = new FormData();
  fd.append('csrf_token',  _csrfCurrent);
  fd.append('document_id', docId);
  fetch('doc_delete.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      if (!d.success) {
        btn.disabled = false;
        alert(d.message || 'Delete failed.');
        return;
      }
      const row = document.querySelector(`#ed-list [data-doc-id="${docId}"]`);
      row?.remove();
      if (!document.querySelector('#ed-list .doc-row')) {
        const p = document.createElement('p');
        p.id = 'ed-empty';
        p.className = 'text-sm text-gray-400 italic';
        p.textContent = 'No documents attached yet.';
        document.getElementById('ed-list').prepend(p);
      }
    });
}

// Owner combobox — keep hidden owner_id in sync with the visible search box.
// Build a case-insensitive name→user_id lookup once.
const ownerLookup = {};
document.querySelectorAll('#owner-suggest option').forEach(opt => {
  ownerLookup[opt.value.trim().toLowerCase()] = opt.dataset.uid;
});

function resolveOwner() {
  const search = document.getElementById('owner_search');
  const hidden = document.getElementById('owner_id');
  const typed  = search.value.trim().toLowerCase();
  if (!typed) {
    hidden.value = '';
    search.classList.remove('fin-err');
    return;
  }
  const uid = ownerLookup[typed];
  hidden.value = uid || '';
  // Soft hint: if name doesn't match any active user yet, mark red
  search.classList.toggle('fin-err', !uid);
}

// Cost Center combobox — same pattern as owner.
const deptLookup = {};
document.querySelectorAll('#dept-suggest option').forEach(opt => {
  deptLookup[opt.value.trim().toLowerCase()] = opt.dataset.did;
});

function resolveDept() {
  const search = document.getElementById('dept_search');
  const hidden = document.getElementById('department_id');
  const typed  = search.value.trim().toLowerCase();
  if (!typed) {
    hidden.value = '';
    search.classList.remove('fin-err');
    return;
  }
  const did = deptLookup[typed];
  hidden.value = did || '';
  search.classList.toggle('fin-err', !did);
}

// Parent Asset combobox — same pattern.
const parentLookup = {};
document.querySelectorAll('#parent-suggest option').forEach(opt => {
  parentLookup[opt.value.trim().toLowerCase()] = opt.dataset.pid;
});

function resolveParent() {
  const search = document.getElementById('parent_search');
  const hidden = document.getElementById('parent_asset_id');
  const typed  = search.value.trim().toLowerCase();
  if (!typed) {
    hidden.value = '';
    search.classList.remove('fin-err');
    return;
  }
  const pid = parentLookup[typed];
  hidden.value = pid || '';
  search.classList.toggle('fin-err', !pid);
}

document.addEventListener('DOMContentLoaded', () => {
  if (initLoc.building) {
    document.getElementById('building-sel').value = initLoc.building;
    populateFloors(false);
    document.getElementById('floor-sel').value = initLoc.floor;
    populateRooms(false);
  }
  toggleBulbHours();
  resolveOwner();
  resolveDept();
  resolveParent();
});
</script>

