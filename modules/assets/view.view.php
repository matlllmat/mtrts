<!-- Back + breadcrumb row -->
<div class="flex items-center gap-2 mb-4">
  <a href="index.php"
     class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition-colors">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
    </svg>
    Back to Asset Registry
  </a>
  <span class="text-gray-300">/</span>
  <span class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($asset['asset_tag']) ?></span>
</div>

<?php if ($show_warn_banner): ?>
<div class="asset-banner banner-info mb-4">
  <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
  </svg>
  <span>Warranty expiring in <strong><?= $wp['days_left'] ?> day<?= $wp['days_left'] !== 1 ? 's' : '' ?></strong> — consider renewing or flagging for review.</span>
</div>
<?php elseif ($expired_banner): ?>
<div class="asset-banner banner-warn mb-4">
  <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
  </svg>
  <span>Warranty has <strong>expired</strong> as of <?= htmlspecialchars((new DateTime($warranty['warranty_end']))->format('M j, Y')) ?>.</span>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 xl:grid-cols-[1fr_260px] gap-4 items-start">

  <!-- LEFT COLUMN -->
  <div class="flex flex-col gap-4">

    <!-- Asset info card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
            <span class="block w-0.5 h-4 bg-olfu-green rounded"></span>
            Asset Information
          </h2>
          <p class="text-xs text-gray-400 mt-0.5">
            Viewing <?= htmlspecialchars($asset['asset_tag']) ?> — <?= htmlspecialchars($asset['manufacturer'] . ' ' . $asset['model']) ?>
          </p>
        </div>
        <div class="flex gap-2 flex-wrap">
          <a href="edit.php?id=<?= $id ?>"
             class="inline-flex items-center gap-1.5 bg-amber-50 border border-amber-300 text-amber-700 hover:bg-amber-100 text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Edit
          </a>
        </div>
      </div>

      <div class="p-6">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
          <div>
            <div class="vf-lbl">Asset Tag</div>
            <div class="vf-val vf-mono"><?= htmlspecialchars($asset['asset_tag']) ?></div>
          </div>
          <div>
            <div class="vf-lbl">Category</div>
            <div class="vf-val"><?= cat_badge($asset['category_name'] ?? '—') ?></div>
          </div>
          <div>
            <div class="vf-lbl">Status</div>
            <div class="vf-val"><?= status_badge($asset['status']) ?></div>
          </div>
          <div>
            <div class="vf-lbl">Manufacturer</div>
            <div class="vf-val"><?= htmlspecialchars($asset['manufacturer']) ?></div>
          </div>
          <div>
            <div class="vf-lbl">Model</div>
            <div class="vf-val"><?= htmlspecialchars($asset['model']) ?></div>
          </div>
          <div>
            <div class="vf-lbl">Serial Number</div>
            <?php if ($asset['serial_number']): ?>
              <div class="vf-val vf-mono"><?= htmlspecialchars($asset['serial_number']) ?></div>
            <?php else: ?>
              <div class="vf-val vf-empty">Not set</div>
            <?php endif; ?>
          </div>

          <!-- Location -->
          <div class="col-span-2 md:col-span-3">
            <div class="sdiv">Location</div>
            <div class="grid grid-cols-3 gap-4">
              <div>
                <div class="vf-lbl">Building</div>
                <div class="vf-val"><?= $asset['building'] ? htmlspecialchars($asset['building']) : '<span class="vf-empty">Not assigned</span>' ?></div>
              </div>
              <div>
                <div class="vf-lbl">Floor</div>
                <div class="vf-val"><?= $asset['floor'] ? htmlspecialchars($asset['floor']) : '<span class="vf-empty">Not assigned</span>' ?></div>
              </div>
              <div>
                <div class="vf-lbl">Room</div>
                <div class="vf-val"><?= $asset['room'] ? htmlspecialchars($asset['room']) : '<span class="vf-empty">Not assigned</span>' ?></div>
              </div>
            </div>
          </div>

          <div>
            <div class="vf-lbl">Install Date</div>
            <div class="vf-val"><?= $asset['install_date'] ? (new DateTime($asset['install_date']))->format('M j, Y') : '<span class="vf-empty">—</span>' ?></div>
          </div>
          <div>
            <div class="vf-lbl">Firmware Version</div>
            <?php if ($asset['firmware_version']): ?>
              <div class="vf-val"><?= htmlspecialchars($asset['firmware_version']) ?></div>
            <?php else: ?>
              <div class="vf-empty">Not set</div>
            <?php endif; ?>
          </div>
          <?php if ($asset['has_bulb_hours']): ?>
          <div>
            <div class="vf-lbl">Bulb Hours</div>
            <div class="vf-val"><?= $asset['bulb_hours'] !== null ? number_format($asset['bulb_hours']) . ' hrs' : '<span class="vf-empty">Not set</span>' ?></div>
          </div>
          <?php endif; ?>
          <div>
            <div class="vf-lbl">Network Info</div>
            <?php if ($asset['network_info']): ?>
              <div class="vf-val vf-mono"><?= htmlspecialchars($asset['network_info']) ?></div>
            <?php else: ?>
              <div class="vf-empty">Not set</div>
            <?php endif; ?>
          </div>
          <div>
            <div class="vf-lbl">Parent Asset</div>
            <?php if ($asset['parent_tag']): ?>
              <a href="view.php?id=<?= $asset['parent_asset_id'] ?>"
                 class="vf-val vf-mono hover:underline"><?= htmlspecialchars($asset['parent_tag']) ?></a>
            <?php else: ?>
              <div class="vf-empty">None</div>
            <?php endif; ?>
          </div>
          <div>
            <div class="vf-lbl">Cost Center</div>
            <div class="vf-val"><?= $asset['cost_center'] ? htmlspecialchars($asset['cost_center']) : '<span class="vf-empty">Not set</span>' ?></div>
          </div>
          <div>
            <div class="vf-lbl">Owner</div>
            <div class="vf-val"><?= $asset['owner_name'] ? htmlspecialchars($asset['owner_name']) : '<span class="vf-empty">Not assigned</span>' ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="tab-nav">
        <?php
        $tabs = ['warranty' => 'Warranty & Contract', 'documents' => 'Documents', 'history' => 'Repair History', 'children' => 'Child Assets (' . count($children) . ')'];
        foreach ($tabs as $key => $label):
        ?>
        <button class="tab-btn <?= $active_tab === $key ? 'tab-on' : '' ?>"
                onclick="switchTab('<?= $key ?>')">
          <?= htmlspecialchars($label) ?>
        </button>
        <?php endforeach; ?>
      </div>

      <!-- Warranty tab -->
      <div id="tab-warranty" class="p-5 <?= $active_tab !== 'warranty' ? 'hidden' : '' ?>">
        <?php if ($warranty): ?>
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <div class="vf-lbl">Warranty Start</div>
              <div class="vf-val"><?= (new DateTime($warranty['warranty_start']))->format('M j, Y') ?></div>
            </div>
            <div>
              <div class="vf-lbl">Warranty End</div>
              <div class="vf-val <?= $wp['expired'] ? 'text-red-600' : ($wp['days_left'] <= 30 ? 'text-amber-600' : '') ?>">
                <?= (new DateTime($warranty['warranty_end']))->format('M j, Y') ?>
                <?php if ($wp['expired']): ?><span class="ml-1 text-xs">(Expired)</span><?php endif; ?>
              </div>
            </div>
            <div>
              <div class="vf-lbl">Coverage Type</div>
              <div class="vf-val"><?= coverage_label($warranty['coverage_type']) ?></div>
            </div>
            <div>
              <div class="vf-lbl">Vendor / Contract</div>
              <div class="vf-val">
                <?= $warranty['vendor_name'] ? htmlspecialchars($warranty['vendor_name']) : '<span class="vf-empty">—</span>' ?>
                <?php if ($warranty['contract_reference']): ?>
                  <span class="text-gray-400"> — <?= htmlspecialchars($warranty['contract_reference']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="mt-3">
            <div class="flex justify-between text-xs text-gray-400 mb-1">
              <span><?= (new DateTime($warranty['warranty_start']))->format('M Y') ?></span>
              <span class="font-semibold <?= $wp['expired'] ? 'text-red-600' : ($wp['days_left'] <= 30 ? 'text-amber-600' : 'text-green-600') ?>">
                <?= $wp['expired'] ? 'Expired' : $wp['days_left'] . ' days remaining' ?>
              </span>
            </div>
            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full rounded-full <?= $wp['color'] ?>" style="width:<?= $wp['pct'] ?>%"></div>
            </div>
          </div>
        <?php else: ?>
          <p class="text-sm text-gray-400 italic">No warranty information recorded for this asset.</p>
          <a href="edit.php?id=<?= $id ?>" class="text-sm text-olfu-green hover:underline mt-2 inline-block">Add warranty info →</a>
        <?php endif; ?>
      </div>

      <!-- Documents tab -->
      <div id="tab-documents" class="p-5 <?= $active_tab !== 'documents' ? 'hidden' : '' ?>">
        <?php if ($documents): ?>
          <div class="flex flex-col gap-2 mb-4">
            <?php foreach ($documents as $doc): ?>
              <div class="doc-row">
                <div class="doc-ic"><?= strtoupper(htmlspecialchars($doc['file_type'])) ?></div>
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($doc['document_name']) ?></div>
                  <div class="doc-meta"><?= (new DateTime($doc['uploaded_at']))->format('M j, Y') ?> · <?= number_format($doc['file_size_kb']) ?> KB</div>
                </div>
                <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full flex-shrink-0">v<?= $doc['version'] ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-sm text-gray-400 italic mb-4">No documents uploaded yet.</p>
        <?php endif; ?>
        <div class="upzone">
          <svg class="w-6 h-6 mx-auto mb-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
          </svg>
          <p class="text-sm font-semibold text-gray-600">Drag & drop or <span class="text-olfu-green underline cursor-pointer">browse</span></p>
          <p class="text-xs text-gray-400 mt-1">PDF, JPG, PNG, DWG, ZIP — Max 50 MB</p>
        </div>
      </div>

      <!-- Repair history tab -->
      <div id="tab-history" class="p-5 <?= $active_tab !== 'history' ? 'hidden' : '' ?>">
        <?php if ($history): ?>
          <table class="w-full border-collapse text-sm">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Ticket ID</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Date</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Issue</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Status</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">WO #</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($history as $h): ?>
                <tr class="border-b border-gray-50">
                  <td class="py-2 px-3 asset-tag"><?= htmlspecialchars($h['ticket_id']) ?></td>
                  <td class="py-2 px-3 text-gray-600"><?= htmlspecialchars($h['date']) ?></td>
                  <td class="py-2 px-3 text-gray-700"><?= htmlspecialchars($h['summary']) ?></td>
                  <td class="py-2 px-3"><?= status_badge($h['status']) ?></td>
                  <td class="py-2 px-3 asset-tag"><?= htmlspecialchars($h['wo_number']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-sm text-gray-400 italic">No repair history yet. Ticket data will appear here once Module 1 is connected.</p>
        <?php endif; ?>
      </div>

      <!-- Child assets tab -->
      <div id="tab-children" class="p-5 <?= $active_tab !== 'children' ? 'hidden' : '' ?>">
        <div class="flex justify-end mb-3">
          <a href="add.php?parent_id=<?= $id ?>"
             class="inline-flex items-center gap-1.5 border border-olfu-green text-olfu-green text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-green-50 transition-colors">
            + Add Child Asset
          </a>
        </div>
        <?php if ($children): ?>
          <table class="w-full border-collapse text-sm">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Asset Tag</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Model</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Category</th>
                <th class="py-2 px-3 text-left text-xs font-bold uppercase tracking-wider text-gray-400">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($children as $ch): ?>
                <tr class="row-link border-b border-gray-50"
                    onclick="window.location='view.php?id=<?= $ch['asset_id'] ?>'">
                  <td class="py-2 px-3"><span class="asset-tag"><?= htmlspecialchars($ch['asset_tag']) ?></span></td>
                  <td class="py-2 px-3 text-gray-700"><?= htmlspecialchars($ch['model']) ?></td>
                  <td class="py-2 px-3"><?= cat_badge($ch['category_name'] ?? '—') ?></td>
                  <td class="py-2 px-3"><?= status_badge($ch['status']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-sm text-gray-400 italic">No child assets linked to this asset.</p>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /left column -->

  <!-- RIGHT PANEL -->
  <div class="flex flex-col gap-3">

    <!-- Quick info -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="rp-hdr">Asset Quick Info</div>
      <div class="px-4 py-2">
        <div class="rp-row">
          <span class="rp-lbl">Asset Tag</span>
          <span class="rp-val asset-tag"><?= htmlspecialchars($asset['asset_tag']) ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Status</span>
          <span><?= status_badge($asset['status']) ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Open Tickets</span>
          <span class="rp-val text-gray-400 italic text-xs">Module 1 pending</span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Building</span>
          <span class="rp-val"><?= htmlspecialchars($asset['building'] ?? '—') ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Floor</span>
          <span class="rp-val"><?= htmlspecialchars($asset['floor'] ?? '—') ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Room</span>
          <span class="rp-val"><?= htmlspecialchars($asset['room'] ?? '—') ?></span>
        </div>
        <?php if ($asset['has_bulb_hours'] && $asset['bulb_hours'] !== null): ?>
        <div class="rp-row">
          <span class="rp-lbl">Bulb Hours</span>
          <span class="rp-val"><?= number_format($asset['bulb_hours']) ?> hrs</span>
        </div>
        <?php endif; ?>
        <div class="rp-row">
          <span class="rp-lbl">Child Assets</span>
          <span class="rp-val"><?= count($children) ?></span>
        </div>
      </div>
    </div>

    <!-- Warranty status -->
    <?php if ($warranty): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="rp-hdr">Warranty Status</div>
      <div class="px-4 py-3">
        <p class="text-sm text-gray-600 mb-3">
          Ends <strong class="<?= $wp['expired'] ? 'text-red-600' : ($wp['days_left'] <= 30 ? 'text-amber-600' : 'text-gray-800') ?>">
            <?= (new DateTime($warranty['warranty_end']))->format('M j, Y') ?>
          </strong>
        </p>
        <div class="flex justify-between text-xs text-gray-400 mb-1">
          <span><?= (new DateTime($warranty['warranty_start']))->format('M Y') ?></span>
          <span class="font-semibold <?= $wp['expired'] ? 'text-red-600' : ($wp['days_left'] <= 30 ? 'text-amber-600' : 'text-green-600') ?>">
            <?= $wp['expired'] ? 'Expired' : $wp['days_left'] . ' days left' ?>
          </span>
        </div>
        <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
          <div class="h-full rounded-full <?= $wp['color'] ?>" style="width:<?= $wp['pct'] ?>%"></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Audit stamp -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="rp-hdr">Record Info</div>
      <div class="px-4 py-2">
        <div class="rp-row">
          <span class="rp-lbl">Created</span>
          <span class="rp-val text-xs"><?= (new DateTime($asset['created_at']))->format('M j, Y') ?></span>
        </div>
        <div class="rp-row">
          <span class="rp-lbl">Last Updated</span>
          <span class="rp-val text-xs"><?= time_ago($asset['updated_at']) ?></span>
        </div>
      </div>
    </div>

  </div><!-- /right panel -->

</div><!-- /grid -->

<script>
function switchTab(key) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('tab-on'));
  document.querySelectorAll('[id^="tab-"]').forEach(p => p.classList.add('hidden'));
  event.currentTarget.classList.add('tab-on');
  const panel = document.getElementById('tab-' + key);
  if (panel) panel.classList.remove('hidden');
}
</script>
