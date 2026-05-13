<!-- Page header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 mb-4 flex flex-wrap items-center justify-between gap-3">
  <div>
    <h2 class="text-xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
      <svg class="w-6 h-6 text-olfu-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
      </svg>
      Email Inbox
    </h2>
    <p class="text-sm text-gray-400 mt-0.5">Tickets received via the email gateway. <strong class="text-gray-700"><?= $unseen ?></strong> unread.</p>
  </div>
  <a href="<?= BASE_URL ?>public/email_submit.php" target="_blank"
     class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
    Open email gateway →
  </a>
</div>

<!-- Filter chips + search -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-4 py-3 mb-3 flex flex-wrap items-center gap-2">
  <?php
  $chip_defs = ['all'=>'All', 'unread'=>'Unread', 'today'=>'Today', 'week'=>'This Week'];
  foreach ($chip_defs as $val => $label):
    $is_on = ($filter === $val);
    $qs = http_build_query(array_filter(['filter'=>$val==='all'?null:$val, 'q'=>$q ?: null]));
  ?>
    <a href="?<?= $qs ?>"
       class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors <?= $is_on ? 'bg-olfu-green text-white border-olfu-green' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' ?>">
      <?= $label ?>
    </a>
  <?php endforeach; ?>
  <form method="get" class="ml-auto flex items-center gap-2">
    <?php if ($filter !== 'all'): ?><input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>"><?php endif; ?>
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
           placeholder="Search by sender or subject…"
           class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-olfu-green/30 min-w-[220px]" />
    <button class="bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors">Search</button>
  </form>
</div>

<!-- Inbox list -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
  <?php if (empty($emails)): ?>
    <div class="py-16 text-center text-gray-400 text-sm">
      <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.235 2.235 0 0 0-.1.661Z"/>
      </svg>
      No emails match this filter.
    </div>
  <?php else: ?>
    <div class="divide-y divide-gray-100">
      <?php foreach ($emails as $e):
        $unread  = $e['email_seen_at'] === null;
        $initial = strtoupper(mb_substr($e['sender_name'] ?? '?', 0, 1));
      ?>
        <a href="<?= BASE_URL ?>modules/tickets/view.php?id=<?= (int)$e['ticket_id'] ?>"
           class="flex items-start gap-3 px-5 py-4 hover:bg-gray-50 transition-colors <?= $unread ? 'bg-green-50/40' : '' ?>">
          <div class="w-10 h-10 rounded-full bg-olfu-green text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
            <?= htmlspecialchars($initial) ?>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-0.5">
              <span class="text-sm font-semibold <?= $unread ? 'text-gray-900' : 'text-gray-600' ?> truncate"><?= htmlspecialchars($e['sender_name'] ?: 'Unknown sender') ?></span>
              <?php if ($e['sender_email']): ?>
                <span class="text-xs text-gray-400 truncate">&lt;<?= htmlspecialchars($e['sender_email']) ?>&gt;</span>
              <?php endif; ?>
              <span class="ml-auto text-xs text-gray-400 flex-shrink-0"><?= inbox_time_ago($e['created_at']) ?></span>
            </div>
            <div class="text-sm <?= $unread ? 'font-semibold text-gray-900' : 'text-gray-700' ?> truncate"><?= htmlspecialchars($e['title']) ?></div>
            <div class="text-xs text-gray-500 truncate mt-0.5"><?= htmlspecialchars(mb_substr((string)$e['description'], 0, 140)) ?></div>
            <div class="flex items-center gap-2 mt-1.5">
              <span class="text-[10px] font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded"><?= htmlspecialchars($e['ticket_number']) ?></span>
              <?php if ($unread): ?>
                <span class="text-[10px] font-bold text-white bg-olfu-green px-2 py-0.5 rounded">NEW</span>
              <?php endif; ?>
              <span class="text-[10px] font-medium text-gray-500 capitalize"><?= htmlspecialchars($e['status']) ?></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div class="flex items-center justify-between mt-3 px-2 text-sm text-gray-500">
  <span>Page <?= $page ?> of <?= $pages ?> (<?= $total ?> total)</span>
  <div class="flex gap-1.5">
    <?php
    $qs_base = http_build_query(array_filter(['filter'=>$filter==='all'?null:$filter, 'q'=>$q ?: null]));
    $qs_base = $qs_base ? '&' . $qs_base : '';
    ?>
    <?php if ($page > 1): ?>
      <a href="?p=<?= $page-1 ?><?= $qs_base ?>" class="px-3 py-1.5 border border-gray-200 rounded hover:bg-gray-50">◀ Prev</a>
    <?php endif; ?>
    <?php if ($page < $pages): ?>
      <a href="?p=<?= $page+1 ?><?= $qs_base ?>" class="px-3 py-1.5 border border-gray-200 rounded hover:bg-gray-50">Next ▶</a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
