<?php
// includes/navbar.php
// Outputs: the sidebar <aside> + the topbar + opens <main>.
// Relies on: $pdo, $_SESSION, $page, $module_labels, $page_title (all set before this is included).

$user_modules = get_user_modules($pdo, $_SESSION['role_id']);

// User initials for avatar (up to 2 chars)
$initials = '';
foreach (explode(' ', trim($_SESSION['full_name'] ?? 'User')) as $word) {
    if ($word !== '') {
        $initials .= strtoupper($word[0]);
        if (strlen($initials) >= 2) break;
    }
}
if ($initials === '') $initials = 'U';

// Active state helpers
$is_dashboard = empty($page);
$current_page = $page ?? '';

$active_cls    = 'flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-semibold text-olfu-green bg-green-50';
$inactive_cls  = 'flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors duration-150';
$logout_cls    = 'flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-red-50 hover:text-red-600 transition-colors duration-150';

// ── Icons (Heroicons outline 24px) ──────────────────────────────
function mtrts_icon(string $path, string $extra_class = ''): string {
    $cls = trim('w-5 h-5 flex-shrink-0 ' . $extra_class);
    return '<svg xmlns="http://www.w3.org/2000/svg" class="' . $cls . '" fill="none"'
         . ' viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">'
         . '<path stroke-linecap="round" stroke-linejoin="round" d="' . $path . '" />'
         . '</svg>';
}

$icons = [
    'dashboard'  => mtrts_icon('M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z'),
    'tickets'    => mtrts_icon('M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.235 2.235 0 0 0-.1.661Z'),
    'assets'     => mtrts_icon('m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z'),
    'workorders' => mtrts_icon('M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z'),
    'technician' => mtrts_icon('M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l5.654-4.654m5.879-4.252a3.376 3.376 0 0 0-4.773 0 3.376 3.376 0 0 0 0 4.773'),
    'reports'    => mtrts_icon('M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z'),
    'users'      => mtrts_icon('M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'),
    'info'       => mtrts_icon('m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z'),
    'logout'     => mtrts_icon('M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9'),
    'bell'       => mtrts_icon('M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0'),
    'chevron'    => mtrts_icon('m8.25 4.5 7.5 7.5-7.5 7.5'),
];
?>

<!-- ── SIDEBAR ───────────────────────────────────────────────── -->
<aside class="w-64 bg-white border-r border-gray-100 flex flex-col flex-shrink-0">

  <!-- Brand -->
  <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 flex-shrink-0">
    <div class="w-9 h-9 rounded-lg bg-olfu-green flex items-center justify-center flex-shrink-0">
      <span class="text-white text-xs font-bold tracking-tight">MT</span>
    </div>
    <div class="min-w-0">
      <p class="text-sm font-bold text-gray-900 leading-tight">MTRTS</p>
      <p class="text-xs text-gray-400 leading-tight">Media Tech Repair</p>
    </div>
  </div>

  <!-- Nav -->
  <nav class="flex-1 overflow-y-auto px-3 py-4">
    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest px-4 mb-2">Main</p>

    <div class="space-y-0.5">

      <!-- Dashboard — always visible -->
      <a href="<?= BASE_URL ?>index.php" class="<?= $is_dashboard ? $active_cls : $inactive_cls ?>">
        <?= $icons['dashboard'] ?>
        <span>Dashboard</span>
      </a>

      <!-- Module links — gated by role -->
      <?php foreach ($module_labels as $slug => $label): ?>
        <?php if (!in_array($slug, $user_modules, true)) continue; ?>
        <a href="<?= BASE_URL ?>modules/<?= $slug ?>/index.php"
           class="<?= (!$is_dashboard && $current_page === $slug) ? $active_cls : $inactive_cls ?>">
          <?= $icons[$slug] ?? '' ?>
          <span><?= htmlspecialchars($label) ?></span>
        </a>
      <?php endforeach; ?>

    </div>
  </nav>

  <!-- Bottom links -->
  <div class="px-3 py-4 border-t border-gray-100 space-y-0.5 flex-shrink-0">
    <a href="#" class="<?= $inactive_cls ?>">
      <?= $icons['info'] ?>
      <span>About the Developers</span>
    </a>
    <a href="logout.php" class="<?= $logout_cls ?>">
      <?= $icons['logout'] ?>
      <span>Logout</span>
    </a>
  </div>

</aside>

<!-- ── MAIN CONTENT AREA ─────────────────────────────────────── -->
<div class="flex-1 flex flex-col overflow-hidden">

  <!-- Top bar -->
  <header class="bg-white border-b border-gray-100 px-6 h-14 flex items-center justify-between flex-shrink-0">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-1.5 text-sm">
      <span class="text-gray-400 font-medium">MTRTS</span>
      <?= mtrts_icon('m8.25 4.5 7.5 7.5-7.5 7.5', 'text-gray-300') ?>
      <span class="text-gray-700 font-semibold"><?= htmlspecialchars($page_title) ?></span>
    </nav>

    <!-- Right: notification bell + user avatar -->
    <div class="flex items-center gap-2">
      <button type="button"
              title="Notifications"
              class="relative w-9 h-9 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-500 transition-colors duration-150">
        <?= $icons['bell'] ?>
        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></span>
      </button>

      <div class="w-9 h-9 rounded-full bg-olfu-green flex items-center justify-center cursor-default flex-shrink-0"
           title="<?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>">
        <span class="text-white text-sm font-bold leading-none"><?= htmlspecialchars($initials) ?></span>
      </div>
    </div>

  </header>

  <!-- Module content is rendered here by index.php -->
  <main class="flex-1 overflow-y-auto p-6">
