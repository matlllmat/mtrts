<?php
// modules/workorders/admin/category_skills.php — define which skills are required per asset category.

$module = 'workorders';
require_once __DIR__ . '/../../../config/guard.php';
require_once __DIR__ . '/../_styles.php';
require_once __DIR__ . '/functions.php';

$mx     = get_category_skill_matrix($pdo);
$cats   = $mx['categories'];
$skills = $mx['skills'];
$map    = $mx['map'];

$selected = (int)($_GET['category_id'] ?? 0);
if (!$selected && $cats) $selected = (int)$cats[0]['category_id'];
$selected_skills = $map[$selected] ?? [];

$csrf = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(16));
$current = 'cat';
?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <!-- Category picker -->
  <div class="lg:col-span-1">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
      <div class="sdiv mb-3" style="padding-top:0">Asset Categories</div>
      <?php if (!$cats): ?>
        <p class="text-sm text-gray-400 italic">No categories defined.</p>
      <?php else: ?>
      <ul class="space-y-1">
      <?php foreach ($cats as $c):
        $count = isset($map[$c['category_id']]) ? count($map[$c['category_id']]) : 0;
        $on    = ((int)$c['category_id'] === $selected);
      ?>
        <li>
          <a href="?category_id=<?= (int)$c['category_id'] ?>"
             class="flex items-center justify-between px-3 py-2 rounded-lg text-sm <?= $on ? 'bg-olfu-green text-white' : 'hover:bg-gray-50 text-gray-700' ?>">
            <span class="font-medium"><?= htmlspecialchars($c['category_name']) ?></span>
            <span class="text-xs <?= $on ? 'text-white/80' : 'text-gray-400' ?>"><?= $count ?> req'd</span>
          </a>
        </li>
      <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>

  <!-- Required skills checklist -->
  <div class="lg:col-span-2">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <?php if (!$selected): ?>
        <p class="text-sm text-gray-400">Select a category on the left to assign required skills.</p>
      <?php elseif (!$skills): ?>
        <p class="text-sm text-gray-400">No skills defined. <a href="skills.php" class="text-emerald-700 font-bold hover:underline">Create some →</a></p>
      <?php else: ?>
        <form method="POST" action="save.php" class="space-y-4">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="action" value="category_skills_save">
          <input type="hidden" name="category_id" value="<?= $selected ?>">

          <div class="sdiv" style="padding-top:0">Required Skills</div>
          <p class="text-xs text-gray-500 -mt-2">Skills checked here are needed to repair this category. Auto-assignment rewards matches.</p>

          <div class="space-y-2">
            <?php foreach ($skills as $s):
              $on = isset($selected_skills[$s['skill_id']]);
            ?>
              <div class="flex items-center gap-3 py-1.5 border-b border-gray-50">
                <input type="checkbox"
                       id="csk-<?= (int)$s['skill_id'] ?>"
                       name="skills[<?= (int)$s['skill_id'] ?>]"
                       value="1"
                       class="w-4 h-4 rounded border-gray-300 text-olfu-green"
                       <?= $on ? 'checked' : '' ?>>
                <label for="csk-<?= (int)$s['skill_id'] ?>" class="flex-1 text-sm font-medium text-gray-800">
                  <?= htmlspecialchars($s['skill_name']) ?>
                  <span class="text-xs text-gray-400 font-mono ml-2"><?= htmlspecialchars($s['skill_code']) ?></span>
                </label>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="flex justify-end pt-2">
            <button type="submit" class="bg-olfu-green text-white text-sm font-bold px-6 py-2.5 rounded-lg hover:bg-olfu-green-md">
              Save Requirements
            </button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
