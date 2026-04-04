<?php if ($expiring > 0): ?>
<div class="asset-banner banner-warn mb-4">
  <svg class="flex-shrink-0 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
  </svg>
  <span><strong><?= $expiring ?> asset<?= $expiring !== 1 ? 's' : '' ?></strong> have warranties expiring within 30 days.</span>
  <button class="banner-link text-red-700"
    onclick="document.getElementById('chip-expiring').click()">Review now →</button>
</div>
<?php endif; ?>

<!-- Page header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 mb-4 flex flex-wrap items-center justify-between gap-3">
  <div>
    <h2 class="text-xl font-bold text-gray-900 tracking-tight">Asset Registry</h2>
    <p class="text-sm text-gray-400 mt-0.5">Manage and track all media and AV equipment. Click any row to view details.</p>
  </div>
  <div class="flex gap-2 flex-wrap">
    <a href="add.php"
       class="inline-flex items-center gap-1.5 bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors duration-150">
      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
      Add Asset
    </a>
    <button type="button" disabled title="Coming soon"
      class="inline-flex items-center gap-1.5 border border-olfu-green text-olfu-green text-sm font-semibold px-4 py-2 rounded-lg opacity-50 cursor-not-allowed">
      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
      Import CSV
    </button>
    <button type="button" disabled title="Coming soon"
      class="inline-flex items-center gap-1.5 border border-gray-200 text-gray-600 text-sm font-semibold px-4 py-2 rounded-lg opacity-50 cursor-not-allowed">
      Bulk Update
    </button>
  </div>
</div>

<!-- Status chips -->
<div class="flex flex-wrap gap-2 mb-3" id="status-chips">
  <?php
  $chip_status = $filters['status'];
  $chip_defs = [
    ''         => ['All',              (int)$stats['total']],
    'active'   => ['Active',           (int)$stats['active']],
    'spare'    => ['Spare',            (int)$stats['spare']],
    'retired'  => ['Retired',          (int)$stats['retired']],
    'expiring' => ['⚠ Expiring Soon',  $expiring],
  ];
  foreach ($chip_defs as $val => $info):
    [$label, $count] = $info;
    $is_on = ($chip_status === $val);
  ?>
  <button type="button"
    id="chip-<?= $val === '' ? 'all' : $val ?>"
    onclick="setChip('<?= $val ?>')"
    class="chip <?= $is_on ? 'chip-on' : '' ?>">
    <?= $label ?> <span class="opacity-70 font-normal">(<?= $count ?>)</span>
  </button>
  <?php endforeach; ?>
</div>

<!-- Filter bar -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-4 py-3 mb-3 flex flex-wrap items-center gap-2">
  <div class="relative flex-1 min-w-48">
    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
    </svg>
    <input type="text" id="q-input" value="<?= htmlspecialchars($filters['q']) ?>"
           placeholder="Search by asset tag, model, serial number…"
           class="fin pl-8 text-sm" />
  </div>
  <select id="cat-select" class="fsel text-sm" style="width:auto;min-width:140px">
    <option value="">All Categories</option>
    <?php foreach ($categories as $c): ?>
      <option value="<?= $c['category_id'] ?>" <?= $filters['category_id'] == $c['category_id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($c['category_name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <select id="bld-select" class="fsel text-sm" style="width:auto;min-width:140px">
    <option value="">All Buildings</option>
    <?php foreach ($buildings as $b): ?>
      <option value="<?= htmlspecialchars($b) ?>" <?= $filters['building'] === $b ? 'selected' : '' ?>>
        <?= htmlspecialchars($b) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <select id="flr-select" class="fsel text-sm" style="width:auto;min-width:130px">
    <option value="">All Floors</option>
    <?php foreach ($floors as $f): ?>
      <option value="<?= htmlspecialchars($f) ?>" <?= $filters['floor'] === $f ? 'selected' : '' ?>>
        <?= htmlspecialchars($f) ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>

<!-- Table -->
<div id="asset-table-wrap">
  <?php require __DIR__ . '/_table.php'; ?>
</div>

<!-- Stats cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
  <?php
  $cards = [
    ['total',    (int)$stats['total'],   'Total Assets',        '+'.((int)$stats['total']).' registered',   'text-green-600', 'bg-green-100 text-green-700',
     'M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM16 3H8a2 2 0 00-2 2v2h12V5a2 2 0 00-2-2z'],
    ['active',   (int)$stats['active'],  'Active',              (int)$stats['total'] ? round((int)$stats['active']/(int)$stats['total']*100).'% of fleet' : '—', 'text-green-600', 'bg-blue-100 text-blue-700',
     'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
    ['expiring', $expiring,              'Expiring Warranties', 'Within 30 days',     'text-red-600',   'bg-amber-100 text-amber-700',
     'M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z'],
    ['retired',  (int)$stats['retired'], 'Retired',             'End-of-life assets', 'text-gray-400',  'bg-gray-100 text-gray-600',
     'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
  ];
  foreach ($cards as [,$num,$lbl,$hint,$hcls,$icls,$path]):
  ?>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-3">
    <div class="stat-ico <?= $icls ?>">
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="<?= $path ?>"/>
      </svg>
    </div>
    <div>
      <div class="text-2xl font-bold text-gray-900 tracking-tight leading-none"><?= $num ?></div>
      <div class="text-xs text-gray-500 mt-1"><?= $lbl ?></div>
      <div class="text-xs font-medium mt-1 <?= $hcls ?>"><?= $hint ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<script>
let _currentPage   = <?= $current_page ?>;
let _currentStatus = <?= json_encode($filters['status']) ?>;
let _debounce      = null;

function setChip(status) {
  _currentStatus = status;
  _currentPage   = 1;
  document.querySelectorAll('.chip').forEach(c => c.classList.remove('chip-on'));
  const id = status === '' ? 'chip-all' : 'chip-' + status;
  const el = document.getElementById(id);
  if (el) el.classList.add('chip-on');
  fetchAssets();
}

function goToPage(p) {
  _currentPage = p;
  fetchAssets();
}

function fetchAssets() {
  const params = new URLSearchParams({
    q:           document.getElementById('q-input').value,
    status:      _currentStatus,
    category_id: document.getElementById('cat-select').value,
    building:    document.getElementById('bld-select').value,
    floor:       document.getElementById('flr-select').value,
    p:           _currentPage,
  });
  const wrap = document.getElementById('asset-table-wrap');
  wrap.style.opacity = '.5';
  fetch('search_ajax.php?' + params.toString())
    .then(r => r.text())
    .then(html => { wrap.innerHTML = html; wrap.style.opacity = '1'; })
    .catch(() => { wrap.style.opacity = '1'; });
}

document.getElementById('q-input').addEventListener('input', () => {
  clearTimeout(_debounce);
  _currentPage = 1;
  _debounce = setTimeout(fetchAssets, 280);
});

['cat-select','bld-select','flr-select'].forEach(id => {
  document.getElementById(id).addEventListener('change', () => {
    _currentPage = 1;
    fetchAssets();
  });
});
</script>
