<?php
// modules/tickets/kb_ajax.php — AJAX endpoint for KB suggestions

$module = 'tickets';
require_once __DIR__ . '/../../config/auth_only.php';
require_once __DIR__ . '/functions.php';

$category_id = (int)($_GET['category_id'] ?? 0);
$kb_articles = get_recommended_kb_articles($pdo, $category_id ?: null);

if (empty($kb_articles)) {
    echo '<p class="text-xs text-gray-400 italic">No specific recommendations for this category yet.</p>';
} else {
    foreach ($kb_articles as $article) {
        ?>
        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm hover:border-olfu-green transition-all cursor-pointer group kb-article-card" 
             onclick="openKbModal('<?= htmlspecialchars($article['title'], ENT_QUOTES) ?>', `<?= htmlspecialchars($article['content'], ENT_QUOTES) ?>`)">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold text-olfu-green uppercase tracking-wider bg-green-50 px-2 py-0.5 rounded">Article</span>
            <svg class="w-4 h-4 text-gray-300 group-hover:text-olfu-green transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
          </div>
          <h4 class="text-sm font-bold text-gray-900 mt-2"><?= htmlspecialchars($article['title']) ?></h4>
          <p class="text-xs text-gray-500 mt-1 line-clamp-2"><?= strip_tags($article['content']) ?></p>
        </div>
        <?php
    }
}
