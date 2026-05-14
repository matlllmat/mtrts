<?php
/**
 * modules/inbox/index.view.php
 *
 * Three modes:
 *   1. Thread view  — ?thread=<message_id>  (shows conversation + reply form)
 *   2. Sent tab     — ?tab=sent             (outbox for all roles)
 *   3. List view    — default               (inbox list)
 */
?>

<?php if (!empty($inbox_error)): ?>
<div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:10px 16px;border-radius:8px;margin-bottom:12px;font-size:13px;">
  <strong>Inbox error:</strong> <?= htmlspecialchars($inbox_error) ?>
</div>
<?php endif; ?>

<?php /* ══════════════════════════════════════════════════════════
         MODE 1 — THREAD VIEW
         ══════════════════════════════════════════════════════════ */
if ($thread_id > 0 && !empty($thread)):
    $other_user_id   = ((int)$thread_msg['sender_id'] === $user_id)
                       ? (int)$thread_msg['recipient_id']
                       : (int)$thread_msg['sender_id'];
    $other_user_name = ((int)$thread_msg['sender_id'] === $user_id)
                       ? ($thread_msg['recipient_name'] ?? 'Unknown')
                       : ($thread_msg['sender_name']   ?? 'Unknown');
    $reply_subject   = preg_match('/^Re:/i', $thread_msg['subject'] ?? '')
                       ? $thread_msg['subject']
                       : 'Re: ' . ($thread_msg['subject'] ?? '');
    $wo_id_ctx     = (int)($thread_msg['wo_id']     ?? 0);
    $ticket_id_ctx = (int)($thread_msg['ticket_id'] ?? 0);
?>

<!-- Back link -->
<div class="mb-4">
  <a href="?<?= $tab === 'sent' ? 'tab=sent' : '' ?>"
     class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 font-medium transition-colors">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
    </svg>
    Back to Messages
  </a>
</div>

<!-- Thread header -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 mb-4">
  <h2 class="text-lg font-bold text-gray-900 truncate"><?= htmlspecialchars($thread_msg['subject'] ?? '(no subject)') ?></h2>
  <p class="text-sm text-gray-400 mt-0.5">
    Conversation with <strong class="text-gray-700"><?= htmlspecialchars($other_user_name) ?></strong>
    &middot; <?= count($thread) ?> message<?= count($thread) !== 1 ? 's' : '' ?>
  </p>
</div>

<!-- Thread messages -->
<div class="space-y-3 mb-4">
<?php foreach ($thread as $msg):
    $is_mine  = ((int)$msg['sender_id'] === $user_id);
    $initials = strtoupper(mb_substr($is_mine ? ($_SESSION['full_name'] ?? 'Me') : $other_user_name, 0, 1));
    $ts       = inbox_time_ago($msg['sent_at'] ?? '');
?>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <!-- Message header -->
    <div class="flex items-center gap-3 px-5 py-3 border-b border-gray-50 <?= $is_mine ? 'bg-green-50/40' : 'bg-gray-50/60' ?>">
      <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0 text-white
                  <?= $is_mine ? 'bg-olfu-green' : 'bg-gray-400' ?>">
        <?= htmlspecialchars($initials) ?>
      </div>
      <div class="flex-1 min-w-0">
        <span class="text-sm font-semibold text-gray-800">
          <?= $is_mine ? 'You' : htmlspecialchars($msg['sender_name'] ?? 'Unknown') ?>
        </span>
        <span class="text-xs text-gray-400 ml-2"><?= htmlspecialchars($ts) ?></span>
      </div>
      <?php if (!$is_mine && $msg['read_at'] === null): ?>
        <span class="text-[10px] font-bold text-white bg-olfu-green px-2 py-0.5 rounded">NEW</span>
      <?php endif; ?>
    </div>
    <!-- Message body -->
    <div class="px-5 py-4 text-sm text-gray-700 leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($msg['body'] ?? '') ?></div>
  </div>
<?php endforeach; ?>
</div>

<!-- Reply form -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-5">
  <h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
    <svg class="w-4 h-4 text-olfu-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
    </svg>
    Reply to <?= htmlspecialchars($other_user_name) ?>
  </h3>

  <div id="replyError"   class="hidden mb-3 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-4 py-2"></div>
  <div id="replySuccess" class="hidden mb-3 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-2">
    ✓ Reply sent successfully.
  </div>

  <div class="mb-3">
    <label class="block text-xs font-semibold text-gray-500 mb-1">Subject</label>
    <input type="text" id="replySubject" maxlength="255"
           value="<?= htmlspecialchars($reply_subject) ?>"
           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-olfu-green/30 focus:border-olfu-green">
  </div>

  <div class="mb-3">
    <label class="block text-xs font-semibold text-gray-500 mb-1">Message <span class="text-red-500">*</span></label>
    <textarea id="replyBody" rows="5" maxlength="10000"
              placeholder="Type your reply…"
              oninput="document.getElementById('replyCharCount').textContent=this.value.length+' / 10,000'"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm resize-y focus:outline-none focus:ring-2 focus:ring-olfu-green/30 focus:border-olfu-green font-inherit"></textarea>
    <div id="replyCharCount" class="text-right text-xs text-gray-400 mt-1">0 / 10,000</div>
  </div>

  <button type="button" id="replyBtn" onclick="sendReply()"
          class="inline-flex items-center gap-2 bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
    </svg>
    <span id="replyBtnLabel">Send Reply</span>
  </button>
</div>

<script>
(function () {
  const RECIPIENT_ID = <?= (int)$other_user_id ?>;
  const WO_ID        = <?= $wo_id_ctx ?>;
  const TICKET_ID    = <?= $ticket_id_ctx ?>;
  const BASE         = '<?= BASE_URL ?>';
  const THREAD_ID    = <?= $thread_id ?>;

  window.sendReply = function () {
    const subject = document.getElementById('replySubject').value.trim();
    const body    = document.getElementById('replyBody').value.trim();
    const btn     = document.getElementById('replyBtn');
    const label   = document.getElementById('replyBtnLabel');
    const errEl   = document.getElementById('replyError');
    const okEl    = document.getElementById('replySuccess');

    errEl.classList.add('hidden');
    okEl.classList.add('hidden');

    if (!body) {
      errEl.textContent = 'Message body is required.';
      errEl.classList.remove('hidden');
      return;
    }

    btn.disabled      = true;
    label.textContent = 'Sending…';

    fetch(BASE + 'modules/inbox/send.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        recipient_id: RECIPIENT_ID,
        subject:      subject || '(no subject)',
        body:         body,
        wo_id:        WO_ID   || '',
        ticket_id:    TICKET_ID || '',
      }).toString(),
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        okEl.classList.remove('hidden');
        document.getElementById('replyBody').value = '';
        document.getElementById('replyCharCount').textContent = '0 / 10,000';
        // Reload thread after short delay so new message appears
        setTimeout(() => { window.location.href = '?thread=' + THREAD_ID; }, 900);
      } else {
        errEl.textContent = data.error || 'Failed to send reply.';
        errEl.classList.remove('hidden');
        btn.disabled      = false;
        label.textContent = 'Send Reply';
      }
    })
    .catch(() => {
      errEl.textContent = 'Network error. Please try again.';
      errEl.classList.remove('hidden');
      btn.disabled      = false;
      label.textContent = 'Send Reply';
    });
  };
})();
</script>

<?php /* ══════════════════════════════════════════════════════════
         MODE 2 & 3 — LIST VIEW (inbox + sent tabs)
         ══════════════════════════════════════════════════════════ */
else: ?>

<!-- ── Page header ──────────────────────────────────────────────────────────── -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-4 mb-4 flex flex-wrap items-center justify-between gap-3">
  <div>
    <h2 class="text-xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
      <?php if ($is_it_role): ?>
        <svg class="w-6 h-6 text-olfu-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
        </svg>
        <?= $tab === 'sent' ? 'Sent Messages' : 'Email Inbox' ?>
      <?php else: ?>
        <svg class="w-6 h-6 text-olfu-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
        </svg>
        <?= $tab === 'sent' ? 'Sent Messages' : 'Messages' ?>
      <?php endif; ?>
    </h2>
    <?php if ($tab === 'sent'): ?>
      <p class="text-sm text-gray-400 mt-0.5">Messages you have sent.</p>
    <?php elseif ($is_it_role): ?>
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

  <?php if ($is_it_role && $tab !== 'sent'): ?>
    <a href="<?= BASE_URL ?>public/email_submit.php" target="_blank"
       class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
      </svg>
      Open email gateway →
    </a>
  <?php endif; ?>
</div>

<!-- ── Inbox / Sent tab switcher ────────────────────────────────────────────── -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 px-4 py-3 mb-3 flex flex-wrap items-center gap-2">

  <!-- Tab: Inbox -->
  <a href="?"
     class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors
            <?= $tab === 'inbox' ? 'bg-olfu-green text-white border-olfu-green' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' ?>">
    Inbox
    <?php if ($unread_count > 0 && $tab !== 'sent'): ?>
      <span class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full bg-white/30 text-[10px] font-bold"><?= $unread_count ?></span>
    <?php endif; ?>
  </a>

  <!-- Tab: Sent -->
  <a href="?tab=sent"
     class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors
            <?= $tab === 'sent' ? 'bg-olfu-green text-white border-olfu-green' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' ?>">
    Sent
  </a>

  <!-- Filter chips (only for inbox tab) -->
  <?php if ($tab === 'inbox'): ?>
    <?php
    $chip_defs = ['all' => 'All', 'unread' => 'Unread', 'today' => 'Today', 'week' => 'This Week'];
    foreach ($chip_defs as $val => $label):
      $is_on = ($filter === $val);
      $chip_params = array_filter(['filter' => $val === 'all' ? null : $val, 'q' => $q ?: null]);
      $chip_qs = http_build_query($chip_params);
    ?>
      <a href="?<?= $chip_qs ?>"
         class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors
                <?= $is_on ? 'bg-gray-700 text-white border-gray-700' : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50' ?>">
        <?= $label ?>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- Search -->
  <form method="get" class="ml-auto flex items-center gap-2">
    <?php if ($tab === 'sent'): ?>
      <input type="hidden" name="tab" value="sent">
    <?php endif; ?>
    <?php if ($filter !== 'all' && $tab === 'inbox'): ?>
      <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
    <?php endif; ?>
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
           placeholder="Search…"
           class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-olfu-green/30 min-w-[200px]" />
    <button class="bg-olfu-green hover:bg-olfu-green-md text-white text-sm font-semibold px-3 py-1.5 rounded-lg transition-colors">Search</button>
  </form>
</div>

<!-- ── Message list ──────────────────────────────────────────────────────────── -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

  <?php if (empty($items)): ?>
    <div class="py-16 text-center text-gray-400 text-sm">
      <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.235 2.235 0 0 0-.1.661Z"/>
      </svg>
      No items match this filter.
    </div>

  <?php elseif ($tab === 'sent'): ?>
    <!-- ── Sent messages list ─────────────────────────────────────────────── -->
    <div class="divide-y divide-gray-100">
      <?php foreach ($items as $row):
        $msg_id    = (int)($row['message_id'] ?? 0);
        $initial   = strtoupper(mb_substr($row['recipient_name'] ?? '?', 0, 1));
        $timestamp = inbox_time_ago($row['sent_at'] ?? '');
        $preview   = mb_substr((string)($row['body'] ?? ''), 0, 120);
        $read_by_recipient = ($row['read_at'] !== null);
      ?>
        <a href="?tab=sent&thread=<?= $msg_id ?>"
           class="flex items-start gap-3 px-5 py-4 hover:bg-gray-50 transition-colors">
          <div class="w-10 h-10 rounded-full bg-gray-400 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
            <?= htmlspecialchars($initial) ?>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-0.5">
              <span class="text-sm font-semibold text-gray-700 truncate">
                To: <?= htmlspecialchars($row['recipient_name'] ?? 'Unknown') ?>
              </span>
              <span class="ml-auto text-xs text-gray-400 flex-shrink-0"><?= htmlspecialchars($timestamp) ?></span>
            </div>
            <div class="text-sm text-gray-700 truncate"><?= htmlspecialchars($row['subject'] ?? '') ?></div>
            <div class="text-xs text-gray-500 truncate mt-0.5"><?= htmlspecialchars($preview) ?></div>
            <div class="mt-1.5 flex items-center gap-2">
              <?php if ($read_by_recipient): ?>
                <span class="text-[10px] text-gray-400 flex items-center gap-1">
                  <svg class="w-3 h-3 text-olfu-green" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                  </svg>
                  Read
                </span>
              <?php else: ?>
                <span class="text-[10px] text-gray-400">Delivered</span>
              <?php endif; ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

  <?php elseif ($is_it_role): ?>
    <!-- ── IT role: unified list (emails + messages) ─────────────────────── -->
    <div class="divide-y divide-gray-100">
      <?php foreach ($items as $row):
        $row_type = $row['row_type'] ?? 'email';
        $is_email = ($row_type === 'email');
        $unread   = ($row['read_at'] === null);
        $initial  = strtoupper(mb_substr($row['sender_name'] ?? '?', 0, 1));
        $timestamp = inbox_time_ago($row['ts'] ?? $row['created_at'] ?? '');

        if ($is_email) {
          $href    = BASE_URL . 'modules/tickets/view.php?id=' . (int)$row['ticket_id'];
          $onclick = '';
        } else {
          $msg_id  = (int)$row['message_id'];
          $href    = BASE_URL . 'modules/inbox/index.php?thread=' . $msg_id;
          $onclick = "onclick=\"(function(e){e.preventDefault();var h=this.href;fetch('" . BASE_URL . "modules/inbox/mark_read.php',{method:'POST',body:new URLSearchParams({message_id:" . $msg_id . "})}).finally(function(){window.location.href=h;});}).call(this,event)\"";
        }
      ?>
        <a href="<?= htmlspecialchars($href) ?>"
           <?= $onclick ?>
           class="flex items-start gap-3 px-5 py-4 hover:bg-gray-50 transition-colors <?= $unread ? 'bg-green-50/40' : '' ?>">
          <div class="w-10 h-10 rounded-full bg-olfu-green text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
            <?= htmlspecialchars($initial) ?>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-0.5">
              <span class="text-sm font-semibold <?= $unread ? 'text-gray-900' : 'text-gray-600' ?> truncate">
                <?= htmlspecialchars($row['sender_name'] ?: 'Unknown sender') ?>
              </span>
              <?php if ($is_email): ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 border border-gray-200 flex-shrink-0">Email</span>
              <?php else: ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-700 border border-green-200 flex-shrink-0">Message</span>
              <?php endif; ?>
              <span class="ml-auto text-xs text-gray-400 flex-shrink-0"><?= htmlspecialchars($timestamp) ?></span>
            </div>
            <div class="text-sm <?= $unread ? 'font-semibold text-gray-900' : 'text-gray-700' ?> truncate">
              <?= htmlspecialchars($row['subject'] ?? '') ?>
            </div>
            <div class="text-xs text-gray-500 truncate mt-0.5">
              <?= htmlspecialchars(mb_substr((string)($row['preview'] ?? ''), 0, 120)) ?>
            </div>
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
    <!-- ── Non-IT role: messages-only inbox list ─────────────────────────── -->
    <div class="divide-y divide-gray-100">
      <?php foreach ($items as $row):
        $msg_id   = (int)($row['message_id'] ?? 0);
        $unread   = ($row['read_at'] === null);
        $initial  = strtoupper(mb_substr($row['sender_name'] ?? '?', 0, 1));
        $timestamp = inbox_time_ago($row['sent_at'] ?? '');
        $preview  = mb_substr((string)($row['body'] ?? ''), 0, 120);
      ?>
        <a href="?thread=<?= $msg_id ?>"
           onclick="(function(e){e.preventDefault();var h=this.href;fetch('<?= BASE_URL ?>modules/inbox/mark_read.php',{method:'POST',body:new URLSearchParams({message_id:<?= $msg_id ?>})}).finally(function(){window.location.href=h;});}).call(this,event)"
           class="flex items-start gap-3 px-5 py-4 hover:bg-gray-50 transition-colors <?= $unread ? 'bg-green-50/40' : '' ?>">
          <div class="w-10 h-10 rounded-full bg-olfu-green text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
            <?= htmlspecialchars($initial) ?>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-0.5">
              <span class="text-sm font-semibold <?= $unread ? 'text-gray-900' : 'text-gray-600' ?> truncate">
                <?= htmlspecialchars($row['sender_name'] ?: 'Unknown sender') ?>
              </span>
              <span class="ml-auto text-xs text-gray-400 flex-shrink-0"><?= htmlspecialchars($timestamp) ?></span>
            </div>
            <div class="text-sm <?= $unread ? 'font-semibold text-gray-900' : 'text-gray-700' ?> truncate">
              <?= htmlspecialchars($row['subject'] ?? '') ?>
            </div>
            <div class="text-xs text-gray-500 truncate mt-0.5"><?= htmlspecialchars($preview) ?></div>
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
      'tab'    => $tab === 'sent' ? 'sent' : null,
      'filter' => ($filter === 'all' || $tab === 'sent') ? null : $filter,
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

<?php endif; /* end list/thread mode */ ?>
