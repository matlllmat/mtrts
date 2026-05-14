<?php
// modules/workorders/admin/technician_skills.php — assign skills + proficiency per technician.

$module = 'workorders';
require_once __DIR__ . '/../../../config/guard.php';
require_once __DIR__ . '/../_styles.php';
require_once __DIR__ . '/functions.php';

$mx     = get_technician_skill_matrix($pdo);
$techs  = $mx['technicians'];
$skills = $mx['skills'];
$map    = $mx['map'];

$selected = (int)($_GET['user_id'] ?? 0);
if (!$selected && $techs) $selected = (int)$techs[0]['user_id'];
$current_skills = $map[$selected] ?? [];

$csrf = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(16));
$current = 'tech';
?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <!-- Tech picker -->
  <div class="lg:col-span-1">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
      <div class="sdiv mb-3" style="padding-top:0">Technicians</div>
      <?php if (!$techs): ?>
        <p class="text-sm text-gray-400 italic">No active IT staff or technicians.</p>
      <?php else: ?>
      <ul class="space-y-1">
      <?php foreach ($techs as $t):
        $count = isset($map[$t['user_id']]) ? count($map[$t['user_id']]) : 0;
        $on    = ((int)$t['user_id'] === $selected);
      ?>
        <li>
          <a href="?user_id=<?= (int)$t['user_id'] ?>"
             class="flex items-center justify-between px-3 py-2 rounded-lg text-sm <?= $on ? 'bg-olfu-green text-white' : 'hover:bg-gray-50 text-gray-700' ?>">
            <span class="font-medium"><?= htmlspecialchars($t['full_name']) ?></span>
            <span class="text-xs <?= $on ? 'text-white/80' : 'text-gray-400' ?>"><?= $count ?> skill<?= $count === 1 ? '' : 's' ?></span>
          </a>
        </li>
      <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>

  <!-- Skill matrix -->
  <div class="lg:col-span-2">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <?php if (!$selected): ?>
        <p class="text-sm text-gray-400">Select a technician on the left to manage their skills.</p>
      <?php elseif (!$skills): ?>
        <p class="text-sm text-gray-400">No skills defined yet. <a href="skills.php" class="text-emerald-700 font-bold hover:underline">Create some →</a></p>
      <?php else: ?>
        <form method="POST" action="save.php" class="space-y-4">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="action" value="tech_skills_save">
          <input type="hidden" name="user_id" value="<?= $selected ?>">

          <div class="sdiv" style="padding-top:0">Skills &amp; Proficiency</div>
          <p class="text-xs text-gray-500 -mt-2">Check skills the technician can perform, then choose proficiency.</p>

          <div class="space-y-2">
            <?php foreach ($skills as $s):
              $prof = $current_skills[$s['skill_id']] ?? 0;
              $checked = $prof > 0;
            ?>
              <div class="flex items-center gap-3 py-1.5 border-b border-gray-50">
                <input type="checkbox"
                       id="sk-<?= (int)$s['skill_id'] ?>"
                       data-skill="<?= (int)$s['skill_id'] ?>"
                       class="tech-skill-cb w-4 h-4 rounded border-gray-300 text-olfu-green"
                       <?= $checked ? 'checked' : '' ?>>
                <label for="sk-<?= (int)$s['skill_id'] ?>" class="flex-1 text-sm font-medium text-gray-800">
                  <?= htmlspecialchars($s['skill_name']) ?>
                  <span class="text-xs text-gray-400 font-mono ml-2"><?= htmlspecialchars($s['skill_code']) ?></span>
                </label>
                <select name="skills[<?= (int)$s['skill_id'] ?>]"
                        class="fsel text-xs w-36"
                        <?= $checked ? '' : 'disabled' ?>>
                  <option value="1" <?= $prof === 1 ? 'selected' : '' ?>>1 — Basic</option>
                  <option value="2" <?= $prof === 2 ? 'selected' : '' ?>>2 — Intermediate</option>
                  <option value="3" <?= $prof === 3 ? 'selected' : '' ?>>3 — Expert</option>
                </select>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="flex justify-end pt-2">
            <button type="submit" class="bg-olfu-green text-white text-sm font-bold px-6 py-2.5 rounded-lg hover:bg-olfu-green-md">
              Save Skills
            </button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Toggle proficiency selects with their checkboxes
document.querySelectorAll('.tech-skill-cb').forEach(cb => {
  cb.addEventListener('change', () => {
    const sel = cb.closest('div').querySelector('select');
    if (!sel) return;
    sel.disabled = !cb.checked;
    // If unchecked, ensure no value is submitted (disabled selects are not posted)
  });
});
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
