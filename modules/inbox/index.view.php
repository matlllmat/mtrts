<?php
/**
 * modules/inbox/index.view.php
 *
 * Dual-mode inbox view.
 *
 * Variables provided by index.php:
 *   $is_it_role   bool    — true for roles 1, 2, 3, 8
 *   $items        array   — unified rows (IT) or message-only rows (non-IT)
 *   $unread_count int     — unread inbox_messages for the current user
 *   $unseen       int     — unseen email tickets (IT only; 0 for non-IT)
 *   $page         int     — current page (1-based)
 *   $pages        int     — total pages
 *   $total        int     — total matching rows
 *   $q            string  — current search query
 *   $filter       string  — current filter chip value
 *
 * Requirements: 5.2, 5.3, 5.5, 5.6, 5.7, 5.8
 */

// is_it query-string flag appended to every chip / search link — NOT USED, removed
?>

<?php if (!empty($inbox_error)): ?>
<div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:10px 16px;border-radius:8px;margin-bottom:12px;font-size:13px;">
  <strong>Inbox error:</strong> <?= htmlspecialchars($inbox_error) ?>
</div>
<?php endif; ?>

<!-- ── Page header ──────────────────────────────────────────────────────────── -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 mb-4 flex flex-wrap items-center justify-between gap-3">
  <div>
    <h2 class="text-xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
      <?php if ($is_it_role): ?>
        <!-- envelope icon for IT unified inbox -->
        <svg class="w-6 h-6 text-olfu-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
        </svg>
        Email Inbox
      <?php else: ?>
        <!-- chat-bubble icon for non-IT messages inbox -->
        <svg class="w-6 h-6 text-olfu-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
        </svg>
        Messages
      <?php endif; ?>
    </h2>
    <?php if ($is_it_role): ?>
      <p class="text-sm text-gray-400 mt-0.5">
        Unified inbox — emails &amp; in-app messages.
        <strong class="text-gray-700"><?= (int)$unseen ?></strong> unread email<?= $unseen !== 1 ? 's' : '' ?>,
        <strong class="text-gray-700"><?= (int)$unread_count ?></strong> unread message<?= $unread_count !== 1 ? 's' : '' ?>.
      </p>
    <?php else: ?>
      <p class="text-sm text-gray-400 mt-0.5">
        Messages sent to you.
        <?php if ($unread_count > 0): ?>
          <strong class="text-gray-700"><?= (int)$unread_count ?></strong> unread.
        <?php else: ?>
          All caught up!
        <?php endif; ?>
      </p>
    <?php endif; ?>
  </div>

  <?php if ($is_it_role): ?>
    <a href="<?= BASE_URL ?>public/email_submit.php" target="_blank"
       class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
      </svg>
      Open email gateway →
    </a>
  <?php endif; ?>
</div>

<!-- ── Filter chips + search ────────────────────────────────────────────────── -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-4 py-3 mb-3 flex flex-wrap items-center gap-2">
  <?php
  $chip_defs = ['all' => 'All', 'unread' => 'Unread', 'today' => 'Today', 'week' => 'This Week'];
  foreach ($chip_defs as $val => $label):
    $is_on = ($filter === $val);
    $chip_params = array_filter([
      'filter' => $val === 'all' ? null : $val,
      'q'      => $q ?: null,
    ]);
    $chip_qs = http_build_query($chip_params);
  ?>
    <a href="?<?= $chip_qs ?>"
       class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors <?= $is_on ? 'bg-olfu-green text-white border-olfu-green' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' ?>">
      <?= $label ?>
    </a>
  <?php endforeach; ?>

  <form method="get" class="ml-auto flex items-center gap-2">
    <?php if ($filter !== 'all'): ?>
      <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
    <?php endif; ?>
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
           placeholder="Search by sender or subject…"
           class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-olfu-green/30 min-w-[220px]" />
    <button class="bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors">Search</button>
  </form>
</div>

<!-- ── Inbox list ────────────────────────────────────────────────────────────── -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

  <?php if (empty($items)): ?>
    <!-- Empty state -->
    <div class="py-16 text-center text-gray-400 text-sm">
      <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.235 2.235 0 0 0-.1.661Z"/>
      </svg>
      No items match this filter.
    </div>

  <?php elseif ($is_it_role): ?>
    <!-- ── IT role: unified list (emails + messages) ─────────────────────── -->
    <div class="divide-y divide-gray-100">
      <?php foreach ($items as $row):
        $row_type  = $row['row_type'] ?? 'email';   // 'email' | 'message'
        $is_email  = ($row_type === 'email');

        // Unread detection differs by type
        $unread = $is_email
          ? ($row['read_at'] === null)               // email_seen_at aliased to read_at in UNION
          : ($row['read_at'] === null);

        $initial   = strtoupper(mb_substr($row['sender_name'] ?? '?', 0, 1));
        $timestamp = inbox_time_ago($row['ts'] ?? $row['created_at'] ?? '');

        // Build the href
        if ($is_email) {
          $href = BASE_URL . 'modules/tickets/view.php?id=' . (int)$row['ticket_id'];
          $onclick = '';   // email rows navigate directly
        } else {
          $msg_id = (int)$row['message_id'];
          $href   = BASE_URL . 'modules/inbox/index.php';
          // mark_read via fetch() before navigating
          $onclick = "onclick=\"(function(e){e.preventDefault();var h=this.href;fetch('" . BASE_URL . "modules/inbox/mark_read.php',{method:'POST',body:new URLSearchParams({message_id:" . $msg_id . "})}).finally(function(){window.location.href=h;});}).call(this,event)\"";
        }
      ?>
        <a href="<?= htmlspecialchars($href) ?>"
           <?= $onclick ?>
           class="flex items-start gap-3 px-5 py-4 hover:bg-gray-50 transition-colors <?= $unread ? 'bg-green-50/40' : '' ?>">

          <!-- Avatar initial -->
          <div class="w-10 h-10 rounded-full bg-olfu-green text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
            <?= htmlspecialchars($initial) ?>
          </div>

          <div class="flex-1 min-w-0">
            <!-- Row 1: sender + type badge + timestamp -->
            <div class="flex items-center gap-2 mb-0.5">
              <span class="text-sm font-semibold <?= $unread ? 'text-gray-900' : 'text-gray-600' ?> truncate">
                <?= htmlspecialchars($row['sender_name'] ?: 'Unknown sender') ?>
              </span>

              <!-- Type label badge (Requirement 5.8) -->
              <?php if ($is_email): ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 border border-gray-200 flex-shrink-0">Email</span>
              <?php else: ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-700 border border-green-200 flex-shrink-0">Message</span>
              <?php endif; ?>

              <span class="ml-auto text-xs text-gray-400 flex-shrink-0"><?= htmlspecialchars($timestamp) ?></span>
            </div>

            <!-- Row 2: subject (bold when unread — Requirement 5.5) -->
            <div class="text-sm <?= $unread ? 'font-semibold text-gray-900' : 'text-gray-700' ?> truncate">
              <?= htmlspecialchars($row['subject'] ?? '') ?>
            </div>

            <!-- Row 3: body preview -->
            <div class="text-xs text-gray-500 truncate mt-0.5">
              <?= htmlspecialchars(mb_substr((string)($row['preview'] ?? ''), 0, 120)) ?>
            </div>

            <!-- Row 4: meta chips -->
            <div class="flex items-center gap-2 mt-1.5">
              <?php if ($is_email && !empty($row['ticket_number'])): ?>
                <span class="text-[10px] font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                  <?= htmlspecialchars($row['ticket_number']) ?>
                </span>
              <?php endif; ?>
              <?php if ($unread): ?>
                <span class="text-[10px] font-bold text-white bg-olfu-green px-2 py-0.5 rounded">NEW</span>
              <?php endif; ?>
              <?php if ($is_email && !empty($row['status'])): ?>
                <span class="text-[10px] font-medium text-gray-500 capitalize"><?= htmlspecialchars($row['status']) ?></span>
              <?php endif; ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

  <?php else: ?>
    <!-- ── Non-IT role: messages-only list ───────────────────────────────── -->
    <div class="divide-y divide-gray-100">
      <?php foreach ($items as $row):
        // Non-IT rows come from get_inbox_messages_page():
        //   message_id, sender_name, subject, body, sent_at, read_at
        $msg_id   = (int)($row['message_id'] ?? 0);
        $unread   = ($row['read_at'] === null);
        $initial  = strtoupper(mb_substr($row['sender_name'] ?? '?', 0, 1));
        $ts_raw   = $row['sent_at'] ?? '';
        $timestamp = $ts_raw ? inbox_time_ago($ts_raw) : '';
        $preview  = mb_substr((string)($row['body'] ?? ''), 0, 120);
      ?>
        <a href="<?= BASE_URL ?>modules/inbox/index.php"
           onclick="(function(e){e.preventDefault();var h=this.href;fetch('<?= BASE_URL ?>modules/inbox/mark_read.php',{method:'POST',body:new URLSearchParams({message_id:<?= $msg_id ?>})}).finally(function(){window.location.href=h;});}).call(this,event)"
           class="flex items-start gap-3 px-5 py-4 hover:bg-gray-50 transition-colors <?= $unread ? 'bg-green-50/40' : '' ?>">

          <!-- Avatar initial -->
          <div class="w-10 h-10 rounded-full bg-olfu-green text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
            <?= htmlspecialchars($initial) ?>
          </div>

          <div class="flex-1 min-w-0">
            <!-- Row 1: sender full name + timestamp (Requirement 5.2) -->
            <div class="flex items-center gap-2 mb-0.5">
              <span class="text-sm font-semibold <?= $unread ? 'text-gray-900' : 'text-gray-600' ?> truncate">
                <?= htmlspecialchars($row['sender_name'] ?: 'Unknown sender') ?>
              </span>
              <!-- Relative timestamp (Requirement 5.2) -->
              <span class="ml-auto text-xs text-gray-400 flex-shrink-0"><?= htmlspecialchars($timestamp) ?></span>
            </div>

            <!-- Row 2: subject — bold when unread (Requirements 5.2, 5.5) -->
            <div class="text-sm <?= $unread ? 'font-semibold text-gray-900' : 'text-gray-700' ?> truncate">
              <?= htmlspecialchars($row['subject'] ?? '') ?>
            </div>

            <!-- Row 3: body preview truncated to 120 chars (Requirement 5.2) -->
            <div class="text-xs text-gray-500 truncate mt-0.5">
              <?= htmlspecialchars($preview) ?>
            </div>

            <!-- Row 4: unread indicator chip (Requirement 5.5) -->
            <?php if ($unread): ?>
              <div class="mt-1.5">
                <span class="text-[10px] font-bold text-white bg-olfu-green px-2 py-0.5 rounded">NEW</span>
              </div>
            <?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<!-- ── Pagination ────────────────────────────────────────────────────────────── -->
<?php if ($pages > 1): ?>
<div class="flex items-center justify-between mt-3 px-2 text-sm text-gray-500">
  <span>Page <?= (int)$page ?> of <?= (int)$pages ?> (<?= (int)$total ?> total)</span>
  <div class="flex gap-1.5">
    <?php
    $pag_params = array_filter([
      'filter' => $filter === 'all' ? null : $filter,
      'q'      => $q ?: null,
    ]);
    $pag_qs = ($pag_params ? '&' . http_build_query($pag_params) : '');
    ?>
    <?php if ($page > 1): ?>
      <a href="?p=<?= $page - 1 ?><?= $pag_qs ?>"
         class="px-3 py-1.5 border border-gray-200 rounded hover:bg-gray-50">◀ Prev</a>
    <?php endif; ?>
    <?php if ($page < $pages): ?>
      <a href="?p=<?= $page + 1 ?><?= $pag_qs ?>"
         class="px-3 py-1.5 border border-gray-200 rounded hover:bg-gray-50">Next ▶</a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
