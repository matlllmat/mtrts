<?php
// modules/workorders/admin/location_teams.php — map technicians to buildings and/or specific rooms.

$module = 'workorders';
require_once __DIR__ . '/../../../config/guard.php';
require_once __DIR__ . '/../_styles.php';
require_once __DIR__ . '/functions.php';

$mx        = get_location_team_matrix($pdo);
$techs     = $mx['technicians'];
$buildings = $mx['buildings'];
$rows      = $mx['assignments'];
$locations = get_all_locations_list($pdo);

$csrf = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(16));
$current = 'loc';
?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <!-- Add form -->
  <div class="lg:col-span-1">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <div class="sdiv mb-4" style="padding-top:0">Add Coverage</div>
      <form method="POST" action="save.php" class="space-y-3">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="location_add">

        <div>
          <label class="flbl">Technician <span class="text-red-400">*</span></label>
          <select name="user_id" class="fsel" required>
            <option value="">— Select technician —</option>
            <?php foreach ($techs as $t): ?>
              <option value="<?= (int)$t['user_id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="flbl">Building</label>
          <input list="bld-list" name="building" class="fin" placeholder="e.g., Building A">
          <datalist id="bld-list">
            <?php foreach ($buildings as $b): ?>
              <option value="<?= htmlspecialchars($b) ?>">
            <?php endforeach; ?>
          </datalist>
          <p class="fhint">Building-wide coverage. Type a new one or pick an existing.</p>
        </div>

        <div>
          <label class="flbl">Specific Room (optional)</label>
          <select name="location_id" class="fsel">
            <option value="">— Any room in building —</option>
            <?php foreach ($locations as $l): ?>
              <option value="<?= (int)$l['location_id'] ?>">
                <?= htmlspecialchars($l['building']) ?> / <?= htmlspecialchars($l['floor']) ?> / <?= htmlspecialchars($l['room']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <p class="fhint">Picking a room gives a stronger location match (+30 vs +20).</p>
        </div>

        <div class="flex items-center gap-2">
          <input type="checkbox" name="is_primary" value="1" id="lt-primary" class="w-4 h-4 rounded border-gray-300 text-olfu-green">
          <label for="lt-primary" class="text-sm text-gray-700 font-medium">Primary technician for this location</label>
        </div>

        <div class="flex justify-end pt-2">
          <button type="submit" class="bg-olfu-green text-white text-sm font-bold px-5 py-2 rounded-lg hover:bg-olfu-green-md">
            Add Assignment
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Existing list -->
  <div class="lg:col-span-2">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
      <div class="sdiv mb-2 px-2" style="padding-top:0">Current Coverage</div>
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 border-b border-gray-100">
            <th class="py-2 px-2">Technician</th>
            <th class="py-2 px-2">Coverage</th>
            <th class="py-2 px-2">Primary</th>
            <th class="py-2 px-2 text-right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="4" class="text-center py-6 text-gray-400">No location assignments yet.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr class="border-b border-gray-50 hover:bg-gray-50">
              <td class="py-2 px-2 font-medium text-gray-800"><?= htmlspecialchars($r['full_name']) ?></td>
              <td class="py-2 px-2 text-gray-700">
                <?php if ($r['location_id']): ?>
                  <span class="font-mono text-xs text-emerald-700">Room</span>
                  <?= htmlspecialchars($r['loc_building']) ?> / <?= htmlspecialchars($r['floor']) ?> / <?= htmlspecialchars($r['room']) ?>
                <?php elseif ($r['building']): ?>
                  <span class="font-mono text-xs text-blue-700">Bldg</span>
                  <?= htmlspecialchars($r['building']) ?>
                <?php else: ?>
                  <span class="text-gray-400 italic">—</span>
                <?php endif; ?>
              </td>
              <td class="py-2 px-2">
                <?= (int)$r['is_primary'] ? '<span class="text-emerald-700 text-xs font-semibold">Yes</span>' : '<span class="text-gray-400 text-xs">No</span>' ?>
              </td>
              <td class="py-2 px-2 text-right">
                <form method="POST" action="save.php" class="inline" onsubmit="return confirm('Remove this assignment?');">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="action" value="location_delete">
                  <input type="hidden" name="assignment_id" value="<?= (int)$r['assignment_id'] ?>">
                  <button type="submit" class="text-xs font-bold text-red-500 hover:underline">Remove</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
