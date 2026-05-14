<?php
// modules/workorders/admin/_nav.php — sub-tab nav for the four admin pages.
// Expects $current = 'skills' | 'tech' | 'cat' | 'loc'
$current = $current ?? '';
$tabs = [
    ['key' => 'skills', 'href' => 'skills.php',             'label' => 'Skills'],
    ['key' => 'tech',   'href' => 'technician_skills.php',  'label' => 'Technician Skills'],
    ['key' => 'cat',    'href' => 'category_skills.php',    'label' => 'Category Skills'],
    ['key' => 'loc',    'href' => 'location_teams.php',     'label' => 'Location Teams'],
];
$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);
?>
<div class="flex items-center gap-2 mb-4">
  <a href="../index.php" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
    </svg>
    Back to Work Orders
  </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 mb-4">
  <h2 class="text-xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
    <span class="block w-0.5 h-5 bg-olfu-green rounded"></span>
    Auto-Assignment Admin
  </h2>
  <p class="text-sm text-gray-400 mt-0.5">Configure technician skills, category requirements, and building/room coverage.</p>
  <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-100 pt-3">
    <?php foreach ($tabs as $t): $on = ($current === $t['key']); ?>
      <a href="<?= $t['href'] ?>" class="px-3 py-1.5 rounded-lg text-sm font-semibold <?= $on ? 'bg-olfu-green text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100' ?>"><?= $t['label'] ?></a>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($flash): ?>
<div class="wo-banner <?= ($flash['type'] ?? '') === 'error' ? 'banner-warn' : 'banner-info' ?> mb-4">
  <span><?= htmlspecialchars($flash['msg']) ?></span>
</div>
<?php endif; ?>
