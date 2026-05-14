<?php
// modules/workorders/admin/skills.php — CRUD for the skills catalog.

$module = 'workorders';
require_once __DIR__ . '/../../../config/guard.php';
require_once __DIR__ . '/../_styles.php';
require_once __DIR__ . '/functions.php';

$edit_id = (int)($_GET['edit'] ?? 0);
$editing = $edit_id ? get_skill_by_id($pdo, $edit_id) : null;
$skills  = get_all_skills($pdo, false);

$csrf = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(16));
$current = 'skills';
?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <!-- Form -->
  <div class="lg:col-span-1">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <div class="sdiv mb-4" style="padding-top:0"><?= $editing ? 'Edit Skill' : 'New Skill' ?></div>
      <form method="POST" action="save.php" class="space-y-3">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="skill_save">
        <?php if ($editing): ?><input type="hidden" name="skill_id" value="<?= (int)$editing['skill_id'] ?>"><?php endif; ?>

        <div>
          <label class="flbl">Code <span class="text-red-400">*</span></label>
          <input type="text" name="skill_code" class="fin" required maxlength="64"
                 placeholder="projector_repair"
                 value="<?= htmlspecialchars($editing['skill_code'] ?? '') ?>">
          <p class="fhint">Short, snake_case, unique. Used by auto-assignment.</p>
        </div>
        <div>
          <label class="flbl">Name <span class="text-red-400">*</span></label>
          <input type="text" name="skill_name" class="fin" required maxlength="120"
                 value="<?= htmlspecialchars($editing['skill_name'] ?? '') ?>">
        </div>
        <div>
          <label class="flbl">Description</label>
          <textarea name="description" rows="3" class="fin"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
        </div>
        <div class="flex items-center gap-2">
          <input type="checkbox" name="is_active" value="1" id="sk-active" class="w-4 h-4 rounded border-gray-300 text-olfu-green"
                 <?= (!$editing || (int)$editing['is_active'] === 1) ? 'checked' : '' ?>>
          <label for="sk-active" class="text-sm text-gray-700 font-medium">Active</label>
        </div>
        <div class="flex items-center justify-end gap-2 pt-2">
          <?php if ($editing): ?>
            <a href="skills.php" class="text-sm text-gray-500 hover:text-gray-800 px-4 py-2 rounded-lg">Cancel</a>
          <?php endif; ?>
          <button type="submit" class="bg-olfu-green text-white text-sm font-bold px-5 py-2 rounded-lg hover:bg-olfu-green-md">
            <?= $editing ? 'Update' : 'Create' ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- List -->
  <div class="lg:col-span-2">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 border-b border-gray-100">
            <th class="py-2 px-2">Code</th>
            <th class="py-2 px-2">Name</th>
            <th class="py-2 px-2">Active</th>
            <th class="py-2 px-2 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$skills): ?>
          <tr><td colspan="4" class="text-center py-6 text-gray-400">No skills defined yet.</td></tr>
        <?php else: foreach ($skills as $s): ?>
          <tr class="border-b border-gray-50 hover:bg-gray-50">
            <td class="py-2 px-2 font-mono text-xs text-emerald-700"><?= htmlspecialchars($s['skill_code']) ?></td>
            <td class="py-2 px-2 font-medium text-gray-800"><?= htmlspecialchars($s['skill_name']) ?></td>
            <td class="py-2 px-2"><?= (int)$s['is_active'] ? '<span class="text-emerald-700 text-xs font-semibold">Yes</span>' : '<span class="text-gray-400 text-xs">No</span>' ?></td>
            <td class="py-2 px-2 text-right">
              <a href="skills.php?edit=<?= (int)$s['skill_id'] ?>" class="text-xs font-bold text-emerald-700 hover:underline mr-2">Edit</a>
              <form method="POST" action="save.php" class="inline" onsubmit="return confirm('Delete this skill? Existing technician and category links will be removed.');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="skill_delete">
                <input type="hidden" name="skill_id" value="<?= (int)$s['skill_id'] ?>">
                <button type="submit" class="text-xs font-bold text-red-500 hover:underline">Delete</button>
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
